<?php
header('Content-Type: application/json; charset=utf-8');
session_start();

require_once '../dbConnection.php';
require_once '../message_helpers.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

if (!isset($input['offer_id']) || !isset($input['action'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Offer ID and action are required']);
    exit;
}

$offerId = intval($input['offer_id']);
$userId = intval($_SESSION['user_id']);
$action = trim(strtolower($input['action'])); // 'accept' or 'decline'

if (!in_array($action, ['accept', 'decline'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Action must be "accept" or "decline"']);
    exit;
}

try {
    $conn->begin_transaction();

    // Verify offer belongs to user and is still valid from auction_order_links
    $verifyQuery = 'SELECT aol.auction_order_id, aol.auction_id, aol.secondary_offer_expires_at, aol.original_winner_user_id, aol.status AS link_status, al.item_name, al.current_bid
                    FROM auction_order_links aol
                    LEFT JOIN auction_listings al ON aol.auction_id = al.auction_id
                    WHERE aol.auction_order_id = ? AND aol.user_id = ? AND aol.status = ?
                    FOR UPDATE';

    $verifyStmt = $conn->prepare($verifyQuery);
    if (!$verifyStmt) {
        throw new Exception('Failed to prepare verification query');
    }

    $offerStatus = 'secondary_offer';
    $verifyStmt->bind_param('iis', $offerId, $userId, $offerStatus);
    $verifyStmt->execute();
    $result = $verifyStmt->get_result();

    if ($result->num_rows === 0) {
        $verifyStmt->close();
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Offer not found or has expired']);
        $conn->rollback();
        exit;
    }

    $offer = $result->fetch_assoc();
    $verifyStmt->close();

    // Check if offer has actually expired
    $expiresAt = $offer['secondary_offer_expires_at'];
    if ($expiresAt && strtotime($expiresAt) < time()) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Offer has expired']);
        $conn->rollback();
        exit;
    }

    $auctionId = (int)($offer['auction_id'] ?? 0);
    $itemName = $offer['item_name'] ?? 'Auction Item';
    $linkId = (int)($offer['auction_order_id'] ?? 0);

    if ($action === 'accept') {
        // Update auction winner to the secondary bidder
        if ($auctionId > 0) {
            $updateAuctionStmt = $conn->prepare('UPDATE auction_listings 
                                               SET winner_user_id = ? 
                                               WHERE auction_id = ?');
            if ($updateAuctionStmt) {
                $updateAuctionStmt->bind_param('ii', $userId, $auctionId);
                $updateAuctionStmt->execute();
                $updateAuctionStmt->close();
            }
            // Update link status to accepted (no order update)
            if ($linkId > 0) {
                $linkUpdate = $conn->prepare('UPDATE auction_order_links SET status = ?, secondary_offer_expires_at = NULL WHERE auction_order_id = ?');
                if ($linkUpdate) {
                    $pendingStatus = 'linked';
                    $linkUpdate->bind_param('si', $pendingStatus, $linkId);
                    $linkUpdate->execute();
                    $linkUpdate->close();
                }
            }
        }

        // Send confirmation message
        $confirmMsg = "You have accepted the secondary offer for '$itemName'. " .
                     "Proceed to checkout to complete your purchase.";
        
        sendPrivateMessage(
            $auctionId,
            $userId,
            'secondary_offer_accepted',
            $confirmMsg,
            $conn,
            $offerId
        );

        // Notify original winner
        $notifyMsg = "Secondary bidder has accepted the offer for '$itemName' and is proceeding to checkout.";
        
        sendPrivateMessage(
            $auctionId,
            (int)($offer['original_winner_user_id'] ?? 0),
            'secondary_offer_accepted_notification',
            $notifyMsg,
            $conn,
            $offerId
        );

        $conn->commit();

        echo json_encode([
            'success' => true,
            'message' => 'Offer accepted! Proceed to checkout.',
            'offer_id' => $offerId,
            'auction_id' => $auctionId,
            'new_status' => 'pending'
        ]);

    } else if ($action === 'decline') {
        // Mark offer as declined, find next bidder
        $linkDeclinedStatus = 'secondary_offer_declined';
        
        if ($linkId > 0) {
            $updateLinkStmt = $conn->prepare('UPDATE auction_order_links SET status = ?, secondary_offer_expires_at = NULL WHERE auction_order_id = ?');
            if ($updateLinkStmt) {
                $updateLinkStmt->bind_param('si', $linkDeclinedStatus, $linkId);
                $updateLinkStmt->execute();
                $updateLinkStmt->close();
            }
        }

        // Find next bidder
        $nextBidderId = null;
        if ($auctionId > 0) {
            $nextBidsQuery = 'SELECT ab.user_id
                              FROM auction_bids ab
                              INNER JOIN (
                                  SELECT auction_id, user_id, MAX(bid_id) AS max_bid_id
                                  FROM auction_bids
                                  WHERE auction_id = ?
                                  GROUP BY auction_id, user_id
                              ) latest ON latest.max_bid_id = ab.bid_id
                              LEFT JOIN auction_order_links aol ON aol.auction_id = ab.auction_id AND aol.user_id = ab.user_id
                              WHERE ab.auction_id = ? AND ab.user_id <> ? AND aol.auction_order_id IS NULL
                              ORDER BY ab.bid_amount DESC, ab.bid_id DESC
                              LIMIT 1';

            $nextBidsStmt = $conn->prepare($nextBidsQuery);
            if ($nextBidsStmt) {
                $nextBidsStmt->bind_param('iii', $auctionId, $auctionId, $userId);
                $nextBidsStmt->execute();
                $nextBidsRes = $nextBidsStmt->get_result();
                
                if ($nextBidsRes && $nextBidsRes->num_rows > 0) {
                    $nextBidRow = $nextBidsRes->fetch_assoc();
                    $nextBidderId = (int)$nextBidRow['user_id'];
                }
                $nextBidsStmt->close();
            }
        }

        if ($nextBidderId && $auctionId > 0) {
            // Check if there's already a pending secondary offer for this auction
            $existingOfferCheck = $conn->prepare('SELECT COUNT(*) as offer_count FROM auction_order_links WHERE auction_id = ? AND status = ?');
            if ($existingOfferCheck) {
                $newOfferStatus = 'secondary_offer';
                $existingOfferCheck->bind_param('is', $auctionId, $newOfferStatus);
                $existingOfferCheck->execute();
                $existingOfferRes = $existingOfferCheck->get_result();
                $existingOfferRow = $existingOfferRes ? $existingOfferRes->fetch_assoc() : null;
                $existingOfferCount = (int)($existingOfferRow['offer_count'] ?? 0);
                $existingOfferCheck->close();
                
                if ($existingOfferCount > 0) {
                    // There's already a pending secondary offer, don't create another one
                    $conn->commit();
                    echo json_encode([
                        'success' => true,
                        'message' => 'Offer declined. Moving to next bidder.',
                        'offer_id' => $offerId,
                        'next_bidder_id' => null
                    ]);
                    exit;
                }
            }

            $newOfferExpiresAt = date('Y-m-d H:i:s', time() + (48 * 3600));

            // Create auction_order_links record without creating an order (similar to cancel-auction-order.php)
            $linkInsert = $conn->prepare('INSERT INTO auction_order_links (auction_id, order_id, user_id, secondary_offer_expires_at, original_winner_user_id, status, created_at) VALUES (?, NULL, ?, ?, ?, ?, NOW())');
            if ($linkInsert) {
                $linkStatus = 'secondary_offer';
                $originalWinnerId = (int)($offer['original_winner_user_id'] ?? 0);
                $linkInsert->bind_param('iisis', $auctionId, $nextBidderId, $newOfferExpiresAt, $originalWinnerId, $linkStatus);
                $linkInsert->execute();
                $newLinkId = (int)$conn->insert_id;
                $linkInsert->close();

                $notificationMsg = "The previous bidder declined the secondary offer for '$itemName'. Your bid is now active for 48 hours.";
                sendPrivateMessage($auctionId, $nextBidderId, 'secondary_auction_offer', $notificationMsg, $conn, $newLinkId);
            }
        } else {
            // No more bidders - mark auction for relisting
            if ($auctionId > 0) {
                $relistStmt = $conn->prepare('UPDATE auction_listings SET auction_status = ? WHERE auction_id = ?');
                if ($relistStmt) {
                    $relistStatus = 'relisting';
                    $relistStmt->bind_param('si', $relistStatus, $auctionId);
                    $relistStmt->execute();
                    $relistStmt->close();

                    $ownerMsg = "'$itemName' auction has been declined by all bidders and is ready for re-listing.";
                    sendPrivateMessage($auctionId, (int)($offer['original_winner_user_id'] ?? 0), 'auction_relisting', $ownerMsg, $conn, $offerId);
                }
            }
        }

        $conn->commit();

        echo json_encode([
            'success' => true,
            'message' => 'Offer declined. Moving to next bidder.',
            'offer_id' => $offerId,
            'next_bidder_id' => $nextBidderId
        ]);
    }

} catch (Exception $e) {
    $conn->rollback();
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error processing offer: ' . $e->getMessage()
    ]);
    error_log('Error in handle-secondary-offer.php: ' . $e->getMessage());
}

$conn->close();
?>
