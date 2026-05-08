<?php
header('Content-Type: application/json; charset=utf-8');
require __DIR__ . '/../dbConnection.php';
require_once __DIR__ . '/auction_helpers.php';
require_once __DIR__ . '/../message_helpers.php';

$cliMode = (PHP_SAPI === 'cli');
$configuredToken = getenv('AUCTION_CRON_TOKEN') ?: '';
$requestToken = isset($_GET['token']) ? trim((string)$_GET['token']) : '';

if (!$cliMode) {
    if ($configuredToken === '') {
        http_response_code(503);
        echo json_encode([
            'success' => false,
            'error' => 'Cron token is not configured. Set AUCTION_CRON_TOKEN in environment.'
        ]);
        exit;
    }

    if (!hash_equals($configuredToken, $requestToken)) {
        http_response_code(401);
        echo json_encode(['success' => false, 'error' => 'Unauthorized cron request']);
        exit;
    }
}

function auto_confirm_delivered_orders(mysqli $conn): int {
    $ordersToConfirm = [];
    $selectSql = 'SELECT order_id, user_id FROM orders WHERE status = "delivered" AND archived = 0 AND binned = 0 AND order_date <= DATE_SUB(NOW(), INTERVAL 2 DAY)';
    $selectStmt = $conn->prepare($selectSql);
    if ($selectStmt) {
        $selectStmt->execute();
        $res = $selectStmt->get_result();
        while ($row = $res->fetch_assoc()) {
            $ordersToConfirm[] = [
                'order_id' => (int)$row['order_id'],
                'user_id' => (int)$row['user_id']
            ];
        }
        $selectStmt->close();
    }

    if (empty($ordersToConfirm)) {
        return 0;
    }

    $updateStmt = $conn->prepare('UPDATE orders SET status = "received" WHERE order_id = ? AND status = "delivered"');
    if (!$updateStmt) {
        return 0;
    }

    $confirmedCount = 0;
    foreach ($ordersToConfirm as $orderData) {
        $updateStmt->bind_param('i', $orderData['order_id']);
        if ($updateStmt->execute() && $updateStmt->affected_rows > 0) {
            $confirmedCount++;
            $conversationId = messageEnsureConversation($conn, $orderData['user_id'], $orderData['order_id']);
            if ($conversationId > 0) {
                messageInsert(
                    $conn,
                    $conversationId,
                    0,
                    'system',
                    'Order automatically confirmed as received after 2 days without customer confirmation.',
                    'status_notice',
                    'received'
                );
            }
        }
    }
    $updateStmt->close();

    return $confirmedCount;
}

$beforeCounts = [
    'scheduled' => 0,
    'active' => 0,
    'ended' => 0,
    'sold' => 0,
    'cancelled' => 0
];

$afterCounts = $beforeCounts;

function readAuctionStateCounts(mysqli $conn): array {
    $counts = [
        'scheduled' => 0,
        'active' => 0,
        'ended' => 0,
        'sold' => 0,
        'cancelled' => 0
    ];

    $stmt = $conn->prepare('SELECT auction_status, COUNT(*) AS total FROM auction_listings GROUP BY auction_status');
    if (!$stmt) {
        return $counts;
    }
    $stmt->execute();
    $res = $stmt->get_result();
    while ($row = $res->fetch_assoc()) {
        $key = (string)($row['auction_status'] ?? '');
        if (array_key_exists($key, $counts)) {
            $counts[$key] = (int)($row['total'] ?? 0);
        }
    }
    $stmt->close();

    return $counts;
}

$beforeCounts = readAuctionStateCounts($conn);
auction_sync_statuses($conn);
$afterCounts = readAuctionStateCounts($conn);
$autoConfirmed = auto_confirm_delivered_orders($conn);

echo json_encode([
    'success' => true,
    'run_at' => date('c'),
    'before' => $beforeCounts,
    'after' => $afterCounts,
    'auto_confirmed_orders' => $autoConfirmed
]);
