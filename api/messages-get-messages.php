<?php
header('Content-Type: application/json; charset=utf-8');
session_start();
require __DIR__ . '/../dbConnection.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$conversationId = (int)($_GET['conversation_id'] ?? 0);
if ($conversationId <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid conversation']);
    exit;
}

$userId = (int)$_SESSION['user_id'];
$role = $_SESSION['user_role'] ?? 'user';
$isAdmin = strtolower((string)$role) === 'admin';

try {
    $accessSql = 'SELECT c.conversation_id, c.user_id, c.order_id, c.subject, o.binned, u.full_name AS user_name
                  FROM conversations c
                  LEFT JOIN orders o ON o.order_id = c.order_id
                  LEFT JOIN users u ON u.user_id = c.user_id
                  WHERE c.conversation_id = ? LIMIT 1';
    $accessStmt = $conn->prepare($accessSql);
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

    if (!$isAdmin) {
        if ((int)$conversation['user_id'] !== $userId) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Forbidden']);
            exit;
        }
        if (!is_null($conversation['order_id']) && (int)($conversation['binned'] ?? 0) === 1) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Conversation unavailable']);
            exit;
        }
    }

    $msgStmt = $conn->prepare('SELECT message_id, sender_id, sender_role, message_text, media_path, media_type, media_mime, media_size, media_original_name, message_type, is_read, created_at FROM messages WHERE conversation_id = ? ORDER BY created_at ASC, message_id ASC');
    if (!$msgStmt) {
        throw new Exception('Failed to prepare message query');
    }
    $msgStmt->bind_param('i', $conversationId);
    $msgStmt->execute();
    $msgRes = $msgStmt->get_result();

    $messages = [];
    while ($row = $msgRes->fetch_assoc()) {
        $messages[] = [
            'message_id' => (int)$row['message_id'],
            'sender_id' => (int)$row['sender_id'],
            'sender_role' => $row['sender_role'],
            'message_text' => $row['message_text'],
            'media_path' => $row['media_path'],
            'media_type' => $row['media_type'],
            'media_mime' => $row['media_mime'],
            'media_size' => $row['media_size'],
            'media_original_name' => $row['media_original_name'],
            'message_type' => $row['message_type'],
            'is_read' => (int)$row['is_read'],
            'created_at' => $row['created_at'],
            // Indicate if this message is sent by the current user
            'is_sent_by_me' => ((int)$row['sender_id'] === $userId),
            // Indicate if this message is read (for the recipient)
            'is_read_visible' => ((int)$row['is_read'] === 1)
        ];
    }
    $msgStmt->close();

    if ($isAdmin) {
        $markStmt = $conn->prepare('UPDATE messages SET is_read = 1 WHERE conversation_id = ? AND sender_role IN ("user", "system")');
    } else {
        $markStmt = $conn->prepare('UPDATE messages SET is_read = 1 WHERE conversation_id = ? AND sender_role IN ("admin", "system")');
    }
    if ($markStmt) {
        $markStmt->bind_param('i', $conversationId);
        $markStmt->execute();
        $markStmt->close();
    }

    echo json_encode([
        'success' => true,
        'conversation' => [
            'conversation_id' => (int)$conversation['conversation_id'],
            'user_id' => (int)$conversation['user_id'],
            'order_id' => is_null($conversation['order_id']) ? null : (int)$conversation['order_id'],
            'subject' => $conversation['subject'],
            'user_name' => $conversation['user_name'] ?? null
        ],
        'messages' => $messages
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Failed to load messages']);
}
