<?php
header('Content-Type: application/json; charset=utf-8');
session_start();
require __DIR__ . '/../dbConnection.php';
require_once __DIR__ . '/auction_helpers.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

$role = $_SESSION['user_role'] ?? 'user';
if ($role !== 'admin') {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Admin access required']);
    exit;
}

$adminUserId = (int)$_SESSION['user_id'];
$status = trim((string)($_GET['status'] ?? 'all'));
$search = trim((string)($_GET['search'] ?? ''));

auction_sync_statuses($conn);

$where = 'WHERE l.admin_user_id = ?';
$params = [$adminUserId];
$types = 'i';

if ($status !== 'all') {
    $allowed = ['scheduled', 'active', 'ended', 'sold', 'cancelled'];
    if (!in_array($status, $allowed, true)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Invalid status']);
        exit;
    }
    $where .= ' AND l.auction_status = ?';
    $params[] = $status;
    $types .= 's';
}

if ($search !== '') {
    $where .= ' AND l.item_name LIKE ?';
    $params[] = '%' . $search . '%';
    $types .= 's';
}

$sql = 'SELECT l.auction_id, l.item_name, l.starting_bid, l.current_bid, l.reserve_price, l.bid_increment, l.start_at, l.end_at, l.auction_status, l.published_at, l.closed_at, l.winner_user_id, l.sold_price, c.category_name, u.full_name AS winner_name FROM auction_listings l LEFT JOIN categories c ON c.category_id = l.category_id LEFT JOIN users u ON u.user_id = l.winner_user_id ' . $where . ' ORDER BY FIELD(l.auction_status, \'active\', \'scheduled\', \'ended\', \'sold\', \'cancelled\'), l.end_at ASC, l.auction_id DESC';

$stmt = $conn->prepare($sql);
if (!$stmt) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Failed to load auctions']);
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

    $listings[] = [
        'auction_id' => $auctionId,
        'item_name' => (string)($row['item_name'] ?? ''),
        'category_name' => (string)($row['category_name'] ?? 'No Category'),
        'starting_bid' => (float)($row['starting_bid'] ?? 0),
        'current_bid' => $row['current_bid'] !== null ? (float)$row['current_bid'] : null,
        'reserve_price' => $row['reserve_price'] !== null ? (float)$row['reserve_price'] : null,
        'bid_increment' => (float)($row['bid_increment'] ?? 0),
        'start_at' => (string)($row['start_at'] ?? ''),
        'end_at' => (string)($row['end_at'] ?? ''),
        'auction_status' => (string)($row['auction_status'] ?? 'scheduled'),
        'bid_count' => $bidCount,
        'published_at' => (string)($row['published_at'] ?? ''),
        'closed_at' => (string)($row['closed_at'] ?? ''),
        'winner_user_id' => $row['winner_user_id'] !== null ? (int)$row['winner_user_id'] : null,
        'winner_name' => (string)($row['winner_name'] ?? ''),
        'sold_price' => $row['sold_price'] !== null ? (float)$row['sold_price'] : null,
        'cover_image' => auction_cover_image($conn, $auctionId)
    ];
}
$stmt->close();

echo json_encode(['success' => true, 'listings' => $listings]);
