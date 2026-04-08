<?php
header('Content-Type: application/json; charset=utf-8');
require __DIR__ . '/../dbConnection.php';
require_once __DIR__ . '/auction_helpers.php';

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

echo json_encode([
    'success' => true,
    'run_at' => date('c'),
    'before' => $beforeCounts,
    'after' => $afterCounts
]);
