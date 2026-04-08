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

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

$startAt = trim((string)($_POST['start_at'] ?? ''));
$endAt = trim((string)($_POST['end_at'] ?? ''));
$draftId = isset($_POST['draft_id']) ? (int)$_POST['draft_id'] : 0;

if ($startAt === '' || $endAt === '') {
    echo json_encode([
        'success' => true,
        'conflict' => false,
        'message' => 'Schedule is incomplete so no overlap check was needed.'
    ]);
    exit;
}

if (auction_has_schedule_conflict($conn, $startAt, $endAt, $draftId)) {
    echo json_encode([
        'success' => true,
        'conflict' => true,
        'message' => 'Another auction already overlaps with this schedule. Choose a different time slot.'
    ]);
    exit;
}

echo json_encode([
    'success' => true,
    'conflict' => false,
    'message' => 'Schedule is available.'
]);
