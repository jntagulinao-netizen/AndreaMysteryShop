<?php
header('Content-Type: application/json; charset=utf-8');
session_start();
require __DIR__ . '/../dbConnection.php';
require_once __DIR__ . '/auction_helpers.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Please log in to place a bid']);
    exit;
}

$role = $_SESSION['user_role'] ?? 'user';
if ($role !== 'user') {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Only buyer accounts can place bids']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

$userId = (int)$_SESSION['user_id'];
$auctionId = isset($_POST['auction_id']) ? (int)$_POST['auction_id'] : 0;
$bidAmountRaw = trim((string)($_POST['bid_amount'] ?? ''));

if ($auctionId <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid auction ID']);
    exit;
}
if ($bidAmountRaw === '' || !is_numeric($bidAmountRaw)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Bid amount is required']);
    exit;
}

$bidAmount = round((float)$bidAmountRaw, 2);
if ($bidAmount <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Bid amount must be greater than zero']);
    exit;
}

try {
    $conn->begin_transaction();

    auction_sync_statuses($conn, $auctionId);

    $lockStmt = $conn->prepare('SELECT auction_id, start_at, end_at, auction_status, starting_bid, current_bid, bid_increment, winner_user_id FROM auction_listings WHERE auction_id = ? FOR UPDATE');
    if (!$lockStmt) {
        throw new Exception('Failed to prepare auction lock');
    }
    $lockStmt->bind_param('i', $auctionId);
    $lockStmt->execute();
    $lockRes = $lockStmt->get_result();
    $auction = $lockRes ? $lockRes->fetch_assoc() : null;
    $lockStmt->close();

    if (!$auction) {
        throw new Exception('Auction not found');
    }

    $startAt = (string)($auction['start_at'] ?? '');
    $endAt = (string)($auction['end_at'] ?? '');
    $now = auction_now_string();

    $status = (string)($auction['auction_status'] ?? 'scheduled');
    if ($status === 'scheduled' && $startAt !== '' && $endAt !== '' && $now >= $startAt && $now < $endAt) {
        $activateStmt = $conn->prepare('UPDATE auction_listings SET auction_status = \'active\', published_at = COALESCE(published_at, ?) WHERE auction_id = ?');
        if ($activateStmt) {
            $activateStmt->bind_param('si', $now, $auctionId);
            $activateStmt->execute();
            $activateStmt->close();
            $status = 'active';
        }
    }

    if ($status !== 'active') {
        throw new Exception('This auction is not currently active');
    }

    if ($startAt !== '' && $now < $startAt) {
        throw new Exception('Bidding has not started yet');
    }
    if ($endAt !== '' && $now >= $endAt) {
        throw new Exception('This auction has already ended');
    }

    $currentBid = $auction['current_bid'] !== null ? (float)$auction['current_bid'] : null;
    $startingBid = (float)($auction['starting_bid'] ?? 0);
    $bidIncrement = (float)($auction['bid_increment'] ?? 0);

    $base = $currentBid !== null ? $currentBid : $startingBid;
    $minimumRequired = $currentBid !== null ? ($base + max(0.01, $bidIncrement)) : $base;
    $minimumRequired = round($minimumRequired, 2);

    if ($bidAmount < $minimumRequired) {
        throw new Exception('Bid is too low. Minimum required is ' . number_format($minimumRequired, 2));
    }

    $outbidStmt = $conn->prepare('UPDATE auction_bids SET bid_status = \'outbid\' WHERE auction_id = ? AND bid_status = \'valid\'');
    if ($outbidStmt) {
        $outbidStmt->bind_param('i', $auctionId);
        $outbidStmt->execute();
        $outbidStmt->close();
    }

    $insertBidStmt = $conn->prepare('INSERT INTO auction_bids (auction_id, user_id, bid_amount, bid_status) VALUES (?, ?, ?, \'valid\')');
    if (!$insertBidStmt) {
        throw new Exception('Failed to save bid');
    }
    $insertBidStmt->bind_param('iid', $auctionId, $userId, $bidAmount);
    $insertBidStmt->execute();
    $insertBidStmt->close();

    $updateAuctionStmt = $conn->prepare('UPDATE auction_listings SET current_bid = ?, winner_user_id = NULL, sold_price = NULL WHERE auction_id = ?');
    if ($updateAuctionStmt) {
        $updateAuctionStmt->bind_param('di', $bidAmount, $auctionId);
        $updateAuctionStmt->execute();
        $updateAuctionStmt->close();
    }

    $conn->commit();

    echo json_encode([
        'success' => true,
        'auction_id' => $auctionId,
        'current_bid' => $bidAmount,
        'message' => 'Bid placed successfully'
    ]);
} catch (Exception $e) {
    $conn->rollback();
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
