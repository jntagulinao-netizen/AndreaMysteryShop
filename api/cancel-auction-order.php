<?php
header('Content-Type: application/json; charset=utf-8');
session_start();

require_once '../dbConnection.php';
require_once '../message_helpers.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
if (!is_array($input)) {
    $input = $_POST;
}

$orderId = isset($input['order_id']) ? (int)$input['order_id'] : 0;
$userId = (int)$_SESSION['user_id'];
$cancellationReason = trim((string)($input['reason'] ?? 'User requested cancellation'));

if ($orderId <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Order ID is required']);
    exit;
}

try {
    $conn->begin_transaction();

    $verifyQuery = 'SELECT o.order_id, o.user_id, o.recipient_id, o.delivery_slot_id, o.total_amount,
                           aol.auction_id, aol.status AS link_status,
                           al.item_name
                    FROM orders o
                    JOIN auction_order_links aol ON aol.order_id = o.order_id
                    LEFT JOIN auction_listings al ON aol.auction_id = al.auction_id
                    WHERE o.order_id = ? AND o.user_id = ? AND aol.status = "linked"
                    FOR UPDATE';

    $verifyStmt = $conn->prepare($verifyQuery);
    if (!$verifyStmt) {
        throw new Exception('Failed to prepare verification query: ' . $conn->error);
    }

    $verifyStmt->bind_param('ii', $orderId, $userId);
    $verifyStmt->execute();
    $result = $verifyStmt->get_result();

    if ($result->num_rows === 0) {
        $verifyStmt->close();
        $conn->rollback();
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Order not found or cannot be cancelled']);
        exit;
    }

    $order = $result->fetch_assoc();
    $verifyStmt->close();

    $auctionId = (int)($order['auction_id'] ?? 0);
    $deliverySlotId = (int)($order['delivery_slot_id'] ?? 0);
    $itemName = trim((string)($order['item_name'] ?? 'Auction Item'));
    $originalRecipientId = (int)($order['recipient_id'] ?? 0);
    $originalTotalAmount = (float)($order['total_amount'] ?? 0);

    $nextBidderId = null;
    if ($auctionId > 0) {
        $bidsQuery = 'SELECT b.user_id, b.bid_amount
                      FROM auction_bids b
                      INNER JOIN (
                          SELECT auction_id, user_id, MAX(bid_id) AS max_bid_id
                          FROM auction_bids
                          WHERE auction_id = ?
                          GROUP BY auction_id, user_id
                      ) latest ON latest.max_bid_id = b.bid_id
                      LEFT JOIN auction_order_links existing
                        ON existing.auction_id = b.auction_id
                       AND existing.user_id = b.user_id
                      WHERE b.auction_id = ?
                        AND b.user_id <> ?
                        AND existing.auction_order_id IS NULL
                      ORDER BY b.bid_amount DESC, b.bid_id DESC
                      LIMIT 1';

        $bidsStmt = $conn->prepare($bidsQuery);
        if (!$bidsStmt) {
            throw new Exception('Failed to query auction bids: ' . $conn->error);
        }

        $bidsStmt->bind_param('iii', $auctionId, $auctionId, $userId);
        $bidsStmt->execute();
        $bidsRes = $bidsStmt->get_result();
        if ($bidsRes && ($bidRow = $bidsRes->fetch_assoc())) {
            $nextBidderId = (int)$bidRow['user_id'];
        }
        $bidsStmt->close();
    }

    $updateStmt = $conn->prepare('UPDATE orders SET status = ? WHERE order_id = ? AND user_id = ?');
    if (!$updateStmt) {
        throw new Exception('Failed to prepare order update: ' . $conn->error);
    }
    $cancelledStatus = 'cancelled';
    $updateStmt->bind_param('sii', $cancelledStatus, $orderId, $userId);
    $updateStmt->execute();
    $updateStmt->close();

    $linkCancelStmt = $conn->prepare('UPDATE auction_order_links SET status = ? WHERE order_id = ? AND auction_id = ?');
    if (!$linkCancelStmt) {
        throw new Exception('Failed to prepare auction link update: ' . $conn->error);
    }
    $linkCancelStmt->bind_param('sii', $cancelledStatus, $orderId, $auctionId);
    $linkCancelStmt->execute();
    $linkCancelStmt->close();

    if ($deliverySlotId > 0) {
        $slotUpdateStmt = $conn->prepare('UPDATE delivery_slots SET current_orders = GREATEST(0, current_orders - 1) WHERE slot_id = ?');
        if (!$slotUpdateStmt) {
            throw new Exception('Failed to update delivery slot: ' . $conn->error);
        }
        $slotUpdateStmt->bind_param('i', $deliverySlotId);
        $slotUpdateStmt->execute();
        $slotUpdateStmt->close();
    }

    $secondaryOfferId = 0;
    $notificationMessage = null;
    $newOrderId = 0;

    if ($nextBidderId > 0 && $auctionId > 0) {
        // Check if there's already a pending secondary offer for this auction
        $existingOfferCheck = $conn->prepare('SELECT COUNT(*) as offer_count FROM auction_order_links WHERE auction_id = ? AND status = ?');
        if ($existingOfferCheck) {
            $existingOfferCheck->bind_param('is', $auctionId, $offerStatus);
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
                    'message' => 'Auction order cancelled successfully',
                    'cancelled_order_id' => $orderId,
                    'secondary_offer_created' => false,
                    'reason' => $cancellationReason
                ]);
                exit;
            }
        }

        $offerExpiresAt = date('Y-m-d H:i:s', time() + (48 * 3600));
        $offerStatus = 'secondary_offer';
        $deliveryType = 'delivery';

        // Create auction_order_links record without creating an order
        $linkStmt = $conn->prepare('INSERT INTO auction_order_links (auction_id, order_id, user_id, secondary_offer_expires_at, original_winner_user_id, status, created_at) VALUES (?, NULL, ?, ?, ?, ?, NOW())');
        if (!$linkStmt) {
            throw new Exception('Failed to prepare auction_order_links insert: ' . $conn->error);
        }
        $linkStatus = 'secondary_offer';
        $linkStmt->bind_param('iisis', $auctionId, $nextBidderId, $offerExpiresAt, $userId, $linkStatus);
        if (!$linkStmt->execute()) {
            throw new Exception('Failed to insert auction_order_links: ' . $linkStmt->error);
        }
        $secondaryOfferId = (int)$conn->insert_id;
        $linkStmt->close();

        $notificationMessage = "The winning bidder for '$itemName' has cancelled their order. Your bid is now active. Complete checkout within 48 hours to secure this item. Offer expires at: $offerExpiresAt";
        $messageResult = sendPrivateMessage(
            $auctionId,
            $nextBidderId,
            'secondary_auction_offer',
            $notificationMessage,
            $conn,
            null // No order ID since no order was created
        );

        if (empty($messageResult['success'])) {
            error_log('Failed to send secondary offer notification to user ' . $nextBidderId . ': ' . ($messageResult['message'] ?? 'unknown error'));
        }
    }

    $conn->commit();

    echo json_encode([
        'success' => true,
        'message' => 'Auction order cancelled successfully',
        'cancelled_order_id' => $orderId,
        'secondary_offer_created' => $secondaryOfferId > 0,
        'secondary_offer_id' => $secondaryOfferId > 0 ? $secondaryOfferId : null,
        'next_bidder_id' => $nextBidderId,
        'notification' => $notificationMessage,
        'reason' => $cancellationReason
    ]);
} catch (Throwable $e) {
    if ($conn->errno) {
        $conn->rollback();
    }
    http_response_code(500);
    error_log('Error in cancel-auction-order.php: ' . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Error cancelling auction order: ' . $e->getMessage()
    ]);
}

$conn->close();
