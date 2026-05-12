<?php
header('Content-Type: application/json; charset=utf-8');

// This is a cron job endpoint - validate it's being called from trusted source or with secret key
// In production, add: require_once 'auth-cron.php'; to validate cron request

require_once '../dbConnection.php';
require_once '../message_helpers.php';

try {
    error_log('Starting cron-process-expired-auction-offers.php');
    
    // Find all expired secondary offers joined from auction_order_links
    $expiredQuery = 'SELECT aol.auction_order_id AS link_id, aol.user_id, aol.auction_id, aol.original_winner_user_id, aol.secondary_offer_expires_at, al.item_name
                     FROM auction_order_links aol
                     LEFT JOIN auction_listings al ON aol.auction_id = al.auction_id
                     WHERE aol.status = ? AND aol.secondary_offer_expires_at IS NOT NULL
                     AND aol.secondary_offer_expires_at < NOW()
                     ORDER BY aol.secondary_offer_expires_at ASC
                     LIMIT 100';

    $expiredStmt = $conn->prepare($expiredQuery);
    if (!$expiredStmt) {
        throw new Exception('Failed to prepare expired offers query');
    }

    $status = 'secondary_offer';
    $expiredStmt->bind_param('s', $status);
    $expiredStmt->execute();
    $result = $expiredStmt->get_result();
    
    $processedCount = 0;
    $movedToNextBidderCount = 0;
    $unclaimedCount = 0;

    while ($offer = $result->fetch_assoc()) {
        $conn->begin_transaction();

        try {
            $linkId = (int)$offer['link_id'];
            $currentBidderId = (int)$offer['user_id'];
            $auctionId = (int)($offer['auction_id'] ?? 0);
            $itemName = $offer['item_name'] ?? 'Auction Item';

            // Find next bidder in queue
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
                    $nextBidsStmt->bind_param('iii', $auctionId, $auctionId, $currentBidderId);
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
                // Cancel current offer and create new one for next bidder
                // Mark current link as declined
                $cancelLinkStmt = $conn->prepare('UPDATE auction_order_links SET status = ? WHERE auction_order_id = ?');
                $declinedStatus = 'secondary_offer_declined';
                $cancelLinkStmt->bind_param('si', $declinedStatus, $linkId);
                $cancelLinkStmt->execute();
                $cancelLinkStmt->close();

                // Create new secondary offer for next bidder
                $newOfferExpiresAt = date('Y-m-d H:i:s', time() + (48 * 3600));

                // Create new secondary offer for next bidder (without creating order)
                $newOfferExpiresAt = date('Y-m-d H:i:s', time() + (48 * 3600));

                // Get auction info for the notification
                $auctionStmt = $conn->prepare('SELECT sold_price, current_bid, starting_bid FROM auction_listings WHERE auction_id = ?');
                $totalAmount = 0.0;
                if ($auctionStmt) {
                    $auctionStmt->bind_param('i', $auctionId);
                    $auctionStmt->execute();
                    $auctionRes = $auctionStmt->get_result();
                    if ($auctionRes && ($auctionRow = $auctionRes->fetch_assoc())) {
                        $soldPrice = $auctionRow['sold_price'] !== null ? (float)$auctionRow['sold_price'] : null;
                        $currentBid = $auctionRow['current_bid'] !== null ? (float)$auctionRow['current_bid'] : null;
                        $startingBid = (float)($auctionRow['starting_bid'] ?? 0);
                        $totalAmount = $soldPrice !== null ? $soldPrice : ($currentBid !== null ? $currentBid : $startingBid);
                    }
                    $auctionStmt->close();
                }

                $linkInsert = $conn->prepare('INSERT INTO auction_order_links (auction_id, order_id, user_id, secondary_offer_expires_at, original_winner_user_id, status, created_at) VALUES (?, NULL, ?, ?, ?, ?, NOW())');
                if ($linkInsert) {
                    $linkStmtStatus = 'secondary_offer';
                    $linkInsert->bind_param('iisis', $auctionId, $nextBidderId, $newOfferExpiresAt, $currentBidderId, $linkStmtStatus);
                    $linkInsert->execute();
                    $linkInsert->close();

                    // Notify next bidder
                    $notificationMsg = "Your bid for '$itemName' is now active! Previous bidder declined. Complete checkout within 48 hours.";
                    sendPrivateMessage($auctionId, $nextBidderId, 'secondary_auction_offer', $notificationMsg, $conn, null);

                    $movedToNextBidderCount++;
                    error_log("Cron: Created secondary offer for bidder $nextBidderId for auction $auctionId");
                }
            } else {
                // No more bidders - mark link as declined
                $declineLinkStmt = $conn->prepare('UPDATE auction_order_links SET status = ? WHERE auction_order_id = ?');
                $declinedStatus = 'secondary_offer_declined';
                $declineLinkStmt->bind_param('si', $declinedStatus, $linkId);
                $declineLinkStmt->execute();
                $declineLinkStmt->close();

                if ($auctionId > 0) {
                    $relistStmt = $conn->prepare('UPDATE auction_listings SET auction_status = ? WHERE auction_id = ?');
                    $relistStatus = 'relisting';
                    $relistStmt->bind_param('si', $relistStatus, $auctionId);
                    $relistStmt->execute();
                    $relistStmt->close();

                    // Notify owner (use original_winner_user_id if present)
                    sendPrivateMessage(
                        $auctionId,
                        (int)$offer['original_winner_user_id'],
                        'auction_relisting',
                        "Auction '$itemName' has been declined by all bidders and is ready for re-listing.",
                        $conn
                    );
                }

                $unclaimedCount++;
                error_log("Cron: No more bidders for auction $auctionId - marked for relisting");
            }

            $conn->commit();
            $processedCount++;

        } catch (Exception $e) {
            $conn->rollback();
            error_log("Error processing expired offer $offerId: " . $e->getMessage());
        }
    }

    $expiredStmt->close();

    echo json_encode([
        'success' => true,
        'message' => 'Cron job completed',
        'processed_count' => $processedCount,
        'moved_to_next_bidder' => $movedToNextBidderCount,
        'unclaimed_auctions' => $unclaimedCount
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Cron job failed: ' . $e->getMessage()
    ]);
    error_log('Error in cron-process-expired-auction-offers.php: ' . $e->getMessage());
}

$conn->close();
?>
