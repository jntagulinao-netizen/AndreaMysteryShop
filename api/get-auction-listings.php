<?php
header('Content-Type: application/json; charset=utf-8');
session_start();
require __DIR__ . '/../dbConnection.php';
require_once __DIR__ . '/auction_helpers.php';

auction_sync_statuses($conn);

$status = trim((string)($_GET['status'] ?? 'live'));
$search = trim((string)($_GET['search'] ?? ''));
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 40;
if ($limit <= 0 || $limit > 100) {
    $limit = 40;
}

$where = '';
$params = [];
$types = '';

if ($status === 'all') {
    $where = 'WHERE l.auction_status IN (\'scheduled\', \'active\', \'ended\', \'sold\')';
} elseif ($status === 'ended') {
    $where = 'WHERE l.auction_status IN (\'ended\', \'sold\')';
} else {
    $where = 'WHERE l.auction_status IN (\'scheduled\', \'active\')';
}

if ($search !== '') {
    $where .= ' AND l.item_name LIKE ?';
    $params[] = '%' . $search . '%';
    $types .= 's';
}

$sql = 'SELECT l.auction_id, l.item_name, COALESCE(NULLIF(l.item_description, \'\'), p.product_description, \'\') AS item_description, l.starting_bid, l.current_bid, l.reserve_price, l.bid_increment, l.start_at, l.end_at, l.auction_status, l.winner_user_id, l.sold_price, l.updated_at, c.category_name FROM auction_listings l LEFT JOIN categories c ON c.category_id = l.category_id LEFT JOIN products p ON p.product_id = l.auction_product_id ' . $where . ' ORDER BY FIELD(l.auction_status, \'active\', \'scheduled\', \'sold\', \'ended\'), l.end_at ASC LIMIT ?';
$params[] = $limit;
$types .= 'i';

$stmt = $conn->prepare($sql);
if (!$stmt) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Failed to load listings']);
    exit;
}

$stmt->bind_param($types, ...$params);
$stmt->execute();
$res = $stmt->get_result();

$listings = [];
while ($row = $res->fetch_assoc()) {
    $auctionId = (int)$row['auction_id'];

    $countStmt = $conn->prepare('SELECT COUNT(*) AS bid_count FROM auction_bids WHERE auction_id = ?');
    $bidCount = 0;
    if ($countStmt) {
        $countStmt->bind_param('i', $auctionId);
        $countStmt->execute();
        $countRes = $countStmt->get_result();
        $countRow = $countRes ? $countRes->fetch_assoc() : null;
        $bidCount = (int)($countRow['bid_count'] ?? 0);
        $countStmt->close();
    }

    $cover = auction_cover_image($conn, $auctionId);
    $coverVideo = auction_cover_video($conn, $auctionId);

    $listings[] = [
        'auction_id' => $auctionId,
        'item_name' => (string)($row['item_name'] ?? ''),
        'item_description' => trim((string)($row['item_description'] ?? '')),
        'category_name' => (string)($row['category_name'] ?? 'No Category'),
        'starting_bid' => (float)($row['starting_bid'] ?? 0),
        'current_bid' => $row['current_bid'] !== null ? (float)$row['current_bid'] : null,
        'reserve_price' => $row['reserve_price'] !== null ? (float)$row['reserve_price'] : null,
        'bid_increment' => (float)($row['bid_increment'] ?? 0),
        'start_at' => (string)($row['start_at'] ?? ''),
        'end_at' => (string)($row['end_at'] ?? ''),
        'auction_status' => (string)($row['auction_status'] ?? 'scheduled'),
        'bid_count' => $bidCount,
        'cover_image' => $cover,
        'cover_video' => $coverVideo,
        'winner_user_id' => $row['winner_user_id'] !== null ? (int)$row['winner_user_id'] : null,
        'sold_price' => $row['sold_price'] !== null ? (float)$row['sold_price'] : null
    ];
}
$stmt->close();

echo json_encode(['success' => true, 'listings' => $listings]);
