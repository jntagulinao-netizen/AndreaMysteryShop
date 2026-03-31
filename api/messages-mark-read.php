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

$input = json_decode(file_get_contents('php://input'), true) ?? [];
$conversationId = (int)($input['conversation_id'] ?? 0);
if ($conversationId <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid conversation']);
    exit;
}

$userId = (int)$_SESSION['user_id'];
$role = $_SESSION['user_role'] ?? 'user';
$isAdmin = strtolower((string)$role) === 'admin';

try {
    $accessStmt = $conn->prepare('SELECT user_id FROM conversations WHERE conversation_id = ? LIMIT 1');
    if (!$accessStmt) {
        throw new Exception('Failed to prepare access query');
    }
    $accessStmt->bind_param('i', $conversationId);
    $accessStmt->execute();
    $accessRes = $accessStmt->get_result();
    $conversation = $accessRes ? $accessRes->fetch_assoc() : null;
    $accessStmt->close();

    if (!$conversation) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Conversation not found']);
        exit;
    }

    if (!$isAdmin && (int)$conversation['user_id'] !== $userId) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Forbidden']);
        exit;
    }

    if ($isAdmin) {
        $stmt = $conn->prepare('UPDATE messages SET is_read = 1 WHERE conversation_id = ? AND sender_role IN ("user", "system")');
    } else {
        $stmt = $conn->prepare('UPDATE messages SET is_read = 1 WHERE conversation_id = ? AND sender_role IN ("admin", "system")');
    }

    if (!$stmt) {
        throw new Exception('Failed to prepare mark-read query');
    }

    $stmt->bind_param('i', $conversationId);
    $stmt->execute();
    $stmt->close();

    echo json_encode(['success' => true]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Failed to mark messages as read']);
}
