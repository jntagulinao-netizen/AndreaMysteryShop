<?php
header('Content-Type: application/json; charset=utf-8');
session_start();
require __DIR__ . '/../dbConnection.php';
require_once __DIR__ . '/auction_helpers.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Please login first']);
    exit;
}

$role = $_SESSION['user_role'] ?? 'user';
if ($role !== 'user') {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Only buyer accounts can view bid history']);
    exit;
}

$userId = (int)$_SESSION['user_id'];
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 200;
if ($limit <= 0 || $limit > 500) {
    $limit = 200;
}

auction_sync_statuses($conn);

$hasOrderLinkTable = false;
$tableCheck = $conn->query("SHOW TABLES LIKE 'auction_order_links'");
if ($tableCheck && $tableCheck->num_rows > 0) {
    $hasOrderLinkTable = true;
}

$sql = $hasOrderLinkTable
    ? 'SELECT b.bid_id, b.auction_id, b.bid_amount, b.bid_status, b.created_at, l.item_name, l.auction_status, l.start_at, l.end_at, l.current_bid, l.sold_price, l.winner_user_id, c.category_name, aol.order_id FROM auction_bids b INNER JOIN auction_listings l ON l.auction_id = b.auction_id LEFT JOIN categories c ON c.category_id = l.category_id LEFT JOIN auction_order_links aol ON aol.auction_id = l.auction_id WHERE b.user_id = ? ORDER BY b.bid_id DESC LIMIT ?'
    : 'SELECT b.bid_id, b.auction_id, b.bid_amount, b.bid_status, b.created_at, l.item_name, l.auction_status, l.start_at, l.end_at, l.current_bid, l.sold_price, l.winner_user_id, c.category_name, NULL AS order_id FROM auction_bids b INNER JOIN auction_listings l ON l.auction_id = b.auction_id LEFT JOIN categories c ON c.category_id = l.category_id WHERE b.user_id = ? ORDER BY b.bid_id DESC LIMIT ?';

$stmt = $conn->prepare($sql);
if (!$stmt) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Failed to load bid history']);
    exit;
}

$stmt->bind_param('ii', $userId, $limit);
$stmt->execute();
$res = $stmt->get_result();

$rows = [];
$topBidderByAuction = [];
while ($row = $res->fetch_assoc()) {
    $auctionId = (int)($row['auction_id'] ?? 0);
    $coverImage = auction_cover_image($conn, $auctionId);
    if (!array_key_exists($auctionId, $topBidderByAuction)) {
        $topBidderByAuction[$auctionId] = 0;
        $topStmt = $conn->prepare('SELECT user_id FROM auction_bids WHERE auction_id = ? ORDER BY bid_amount DESC, bid_id DESC LIMIT 1');
        if ($topStmt) {
            $topStmt->bind_param('i', $auctionId);
            $topStmt->execute();
            $topRes = $topStmt->get_result();
            $topRow = $topRes ? $topRes->fetch_assoc() : null;
            $topBidderByAuction[$auctionId] = (int)($topRow['user_id'] ?? 0);
            $topStmt->close();
        }
    }

    $auctionStatus = (string)($row['auction_status'] ?? 'scheduled');
    $isCurrentHighest = $auctionStatus === 'active' && $topBidderByAuction[$auctionId] === $userId;

    $rows[] = [
        'bid_id' => (int)($row['bid_id'] ?? 0),
        'auction_id' => $auctionId,
        'item_name' => (string)($row['item_name'] ?? 'Auction Item'),
        'category_name' => (string)($row['category_name'] ?? 'No Category'),
        'auction_status' => $auctionStatus,
        'start_at' => (string)($row['start_at'] ?? ''),
        'end_at' => (string)($row['end_at'] ?? ''),
        'bid_amount' => (float)($row['bid_amount'] ?? 0),
        'bid_status' => (string)($row['bid_status'] ?? 'valid'),
        'created_at' => (string)($row['created_at'] ?? ''),
        'current_bid' => $row['current_bid'] !== null ? (float)$row['current_bid'] : null,
        'sold_price' => $row['sold_price'] !== null ? (float)$row['sold_price'] : null,
        'winner_user_id' => $row['winner_user_id'] !== null ? (int)$row['winner_user_id'] : null,
        'is_winner' => $row['winner_user_id'] !== null && (int)$row['winner_user_id'] === $userId,
        'is_current_highest' => $isCurrentHighest,
        'checked_out' => $row['order_id'] !== null,
        'order_id' => $row['order_id'] !== null ? (int)$row['order_id'] : null,
        'cover_image' => $coverImage
    ];
}
$stmt->close();

echo json_encode([
    'success' => true,
    'bids' => $rows
]);
