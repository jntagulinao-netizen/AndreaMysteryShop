<?php
header('Content-Type: application/json; charset=utf-8');
session_start();
require __DIR__ . '/../dbConnection.php';
require_once __DIR__ . '/auction_helpers.php';

$auctionId = isset($_GET['auction_id']) ? (int)$_GET['auction_id'] : 0;
if ($auctionId <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid auction ID']);
    exit;
}

auction_sync_statuses($conn, $auctionId);

$currentUserId = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : 0;

$hasOrderLinkTable = false;
$orderLinkTableCheck = $conn->query("SHOW TABLES LIKE 'auction_order_links'");
if ($orderLinkTableCheck && $orderLinkTableCheck->num_rows > 0) {
    $hasOrderLinkTable = true;
}

$detailSql = $hasOrderLinkTable
    ? 'SELECT l.auction_id, l.item_name, COALESCE(NULLIF(l.item_description, \'\'), p.product_description, \'\') AS item_description, l.condition_grade, l.starting_bid, l.current_bid, l.reserve_price, l.bid_increment, l.start_at, l.end_at, l.auction_status, l.winner_user_id, l.sold_price, l.closed_at, c.category_name, u.full_name AS winner_name, aol.order_id AS linked_order_id FROM auction_listings l LEFT JOIN categories c ON c.category_id = l.category_id LEFT JOIN users u ON u.user_id = l.winner_user_id LEFT JOIN products p ON p.product_id = l.auction_product_id LEFT JOIN auction_order_links aol ON aol.auction_id = l.auction_id WHERE l.auction_id = ? LIMIT 1'
    : 'SELECT l.auction_id, l.item_name, COALESCE(NULLIF(l.item_description, \'\'), p.product_description, \'\') AS item_description, l.condition_grade, l.starting_bid, l.current_bid, l.reserve_price, l.bid_increment, l.start_at, l.end_at, l.auction_status, l.winner_user_id, l.sold_price, l.closed_at, c.category_name, u.full_name AS winner_name, NULL AS linked_order_id FROM auction_listings l LEFT JOIN categories c ON c.category_id = l.category_id LEFT JOIN users u ON u.user_id = l.winner_user_id LEFT JOIN products p ON p.product_id = l.auction_product_id WHERE l.auction_id = ? LIMIT 1';

$stmt = $conn->prepare($detailSql);
if (!$stmt) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Failed to load auction']);
    exit;
}
$stmt->bind_param('i', $auctionId);
$stmt->execute();
$res = $stmt->get_result();
$row = $res ? $res->fetch_assoc() : null;
$stmt->close();

if (!$row) {
    http_response_code(404);
    echo json_encode(['success' => false, 'error' => 'Auction not found']);
    exit;
}

$media = ['images' => [], 'video' => ''];
$mediaStmt = $conn->prepare('SELECT media_type, file_path, is_pinned FROM auction_listing_media WHERE auction_id = ? ORDER BY is_pinned DESC, sort_order ASC, media_id ASC');
if ($mediaStmt) {
    $mediaStmt->bind_param('i', $auctionId);
    $mediaStmt->execute();
    $mediaRes = $mediaStmt->get_result();
    while ($m = $mediaRes->fetch_assoc()) {
        $type = (string)($m['media_type'] ?? '');
        $path = (string)($m['file_path'] ?? '');
        if ($path === '') {
            continue;
        }
        if ($type === 'image') {
            $media['images'][] = [
                'path' => $path,
                'is_pinned' => (int)($m['is_pinned'] ?? 0) === 1
            ];
        } elseif ($type === 'video' && $media['video'] === '') {
            $media['video'] = $path;
        }
    }
    $mediaStmt->close();
}

$bidCount = 0;
$countStmt = $conn->prepare('SELECT COUNT(*) AS bid_count FROM auction_bids WHERE auction_id = ?');
if ($countStmt) {
    $countStmt->bind_param('i', $auctionId);
    $countStmt->execute();
    $countRes = $countStmt->get_result();
    $countRow = $countRes ? $countRes->fetch_assoc() : null;
    $bidCount = (int)($countRow['bid_count'] ?? 0);
    $countStmt->close();
}

$recentBids = [];
$bidStmt = $conn->prepare('SELECT b.bid_amount, b.created_at, u.full_name FROM auction_bids b LEFT JOIN users u ON u.user_id = b.user_id WHERE b.auction_id = ? ORDER BY b.bid_id DESC LIMIT 12');
if ($bidStmt) {
    $bidStmt->bind_param('i', $auctionId);
    $bidStmt->execute();
    $bidRes = $bidStmt->get_result();
    while ($b = $bidRes->fetch_assoc()) {
        $recentBids[] = [
            'bid_amount' => (float)($b['bid_amount'] ?? 0),
            'created_at' => (string)($b['created_at'] ?? ''),
            'bidder' => auction_mask_name((string)($b['full_name'] ?? 'Bidder'))
        ];
    }
    $bidStmt->close();
}

echo json_encode([
    'success' => true,
    'auction' => [
        'auction_id' => (int)$row['auction_id'],
        'item_name' => (string)($row['item_name'] ?? ''),
        'item_description' => (string)($row['item_description'] ?? ''),
        'condition_grade' => (string)($row['condition_grade'] ?? ''),
        'category_name' => (string)($row['category_name'] ?? 'No Category'),
        'starting_bid' => (float)($row['starting_bid'] ?? 0),
        'current_bid' => $row['current_bid'] !== null ? (float)$row['current_bid'] : null,
        'reserve_price' => $row['reserve_price'] !== null ? (float)$row['reserve_price'] : null,
        'bid_increment' => (float)($row['bid_increment'] ?? 0),
        'start_at' => (string)($row['start_at'] ?? ''),
        'end_at' => (string)($row['end_at'] ?? ''),
        'auction_status' => (string)($row['auction_status'] ?? 'scheduled'),
        'bid_count' => $bidCount,
        'winner_user_id' => $row['winner_user_id'] !== null ? (int)$row['winner_user_id'] : null,
        'is_winner' => $row['winner_user_id'] !== null && (int)$row['winner_user_id'] === $currentUserId,
        'checked_out' => $row['linked_order_id'] !== null,
        'order_id' => $row['linked_order_id'] !== null ? (int)$row['linked_order_id'] : null,
        'winner_name' => $row['winner_name'] !== null ? auction_mask_name((string)$row['winner_name']) : '',
        'sold_price' => $row['sold_price'] !== null ? (float)$row['sold_price'] : null,
        'closed_at' => (string)($row['closed_at'] ?? ''),
        'media' => $media
    ],
    'recent_bids' => $recentBids
]);
