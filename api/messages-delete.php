<?php
header('Content-Type: application/json; charset=utf-8');
session_start();
require __DIR__ . '/../dbConnection.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$messageId = (int)($_POST['message_id'] ?? 0);
if ($messageId <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid message ID']);
    exit;
}

$userId = (int)$_SESSION['user_id'];
$role = $_SESSION['user_role'] ?? 'user';
$isAdmin = strtolower((string)$role) === 'admin';

// Only allow sender or admin to delete
$stmt = $conn->prepare('SELECT sender_id, sender_role FROM messages WHERE message_id = ? LIMIT 1');
$stmt->bind_param('i', $messageId);
$stmt->execute();
$res = $stmt->get_result();
$row = $res ? $res->fetch_assoc() : null;
$stmt->close();

if (!$row) {
    http_response_code(404);
    echo json_encode(['success' => false, 'message' => 'Message not found']);
    exit;
}

if (!$isAdmin && (int)$row['sender_id'] !== $userId) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Forbidden']);
    exit;
}

$delStmt = $conn->prepare('DELETE FROM messages WHERE message_id = ?');
$delStmt->bind_param('i', $messageId);
$ok = $delStmt->execute();
$delStmt->close();

if ($ok) {
    echo json_encode(['success' => true]);
} else {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Failed to delete message']);
}
