<?php
header('Content-Type: application/json; charset=utf-8');
session_start();
require __DIR__ . '/../dbConnection.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$userId = (int)$_SESSION['user_id'];
$role = $_SESSION['user_role'] ?? 'user';
$isAdmin = strtolower((string)$role) === 'admin';

try {
    if ($isAdmin) {
        $sql = 'SELECT c.conversation_id, c.user_id, c.admin_id, c.order_id, c.subject, c.last_message_at,
                       u.full_name AS user_name,
                       m.message_text AS last_message,
                       m.created_at AS last_message_created_at,
                       (SELECT COUNT(*) FROM messages um WHERE um.conversation_id = c.conversation_id AND um.is_read = 0 AND um.sender_role IN ("user", "system")) AS unread_count
                FROM conversations c
                LEFT JOIN users u ON u.user_id = c.user_id
                LEFT JOIN messages m ON m.message_id = (
                    SELECT m2.message_id FROM messages m2 WHERE m2.conversation_id = c.conversation_id ORDER BY m2.created_at DESC, m2.message_id DESC LIMIT 1
                )
                ORDER BY COALESCE(c.last_message_at, c.updated_at, c.created_at) DESC';
        $stmt = $conn->prepare($sql);
    } else {
        $sql = 'SELECT c.conversation_id, c.user_id, c.admin_id, c.order_id, c.subject, c.last_message_at,
                       m.message_text AS last_message,
                       m.created_at AS last_message_created_at,
                      (SELECT COUNT(*) FROM messages um WHERE um.conversation_id = c.conversation_id AND um.is_read = 0 AND um.sender_role IN ("admin", "system")) AS unread_count
                FROM conversations c
                LEFT JOIN orders o ON o.order_id = c.order_id
                LEFT JOIN messages m ON m.message_id = (
                    SELECT m2.message_id FROM messages m2 WHERE m2.conversation_id = c.conversation_id ORDER BY m2.created_at DESC, m2.message_id DESC LIMIT 1
                )
                WHERE c.user_id = ? AND (c.order_id IS NULL OR (o.order_id IS NOT NULL AND o.binned = 0))
                ORDER BY COALESCE(c.last_message_at, c.updated_at, c.created_at) DESC';
        $stmt = $conn->prepare($sql);
        if ($stmt) {
            $stmt->bind_param('i', $userId);
        }
    }

    if (!$stmt) {
        throw new Exception('Failed to prepare query');
    }

    $stmt->execute();
    $result = $stmt->get_result();

    $conversations = [];
    while ($row = $result->fetch_assoc()) {
        $conversations[] = [
            'conversation_id' => (int)$row['conversation_id'],
            'user_id' => (int)$row['user_id'],
            'admin_id' => isset($row['admin_id']) ? (int)$row['admin_id'] : null,
            'order_id' => isset($row['order_id']) ? (int)$row['order_id'] : null,
            'subject' => $row['subject'] ?? null,
            'user_name' => $row['user_name'] ?? null,
            'last_message' => $row['last_message'] ?? '',
            'last_message_at' => $row['last_message_created_at'] ?? $row['last_message_at'],
            'unread_count' => (int)($row['unread_count'] ?? 0)
        ];
    }

    $stmt->close();

    echo json_encode([
        'success' => true,
        'conversations' => $conversations,
        'role' => $isAdmin ? 'admin' : 'user'
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Failed to load conversations']);
}
