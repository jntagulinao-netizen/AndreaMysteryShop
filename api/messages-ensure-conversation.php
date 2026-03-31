<?php
header('Content-Type: application/json; charset=utf-8');
session_start();
require __DIR__ . '/../dbConnection.php';
require __DIR__ . '/../message_helpers.php';

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
$orderId = isset($input['order_id']) ? (int)$input['order_id'] : 0;
$requestedUserId = isset($input['user_id']) ? (int)$input['user_id'] : 0;

if ($orderId <= 0 && $requestedUserId <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'order_id or user_id is required']);
    exit;
}

$currentUserId = (int)$_SESSION['user_id'];
$role = $_SESSION['user_role'] ?? 'user';
$isAdmin = strtolower((string)$role) === 'admin';

try {
    $targetUserId = $requestedUserId;

    if ($isAdmin) {
        if ($targetUserId <= 0 && $orderId > 0) {
            $ownerStmt = $conn->prepare('SELECT user_id FROM orders WHERE order_id = ? LIMIT 1');
            if (!$ownerStmt) {
                throw new Exception('Failed to prepare owner query');
            }
            $ownerStmt->bind_param('i', $orderId);
            $ownerStmt->execute();
            $ownerRes = $ownerStmt->get_result();
            $ownerRow = $ownerRes ? $ownerRes->fetch_assoc() : null;
            $ownerStmt->close();

            $targetUserId = (int)($ownerRow['user_id'] ?? 0);
        }

        if ($targetUserId <= 0) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'User is required']);
            exit;
        }

        $conversationId = messageEnsureConversation($conn, $targetUserId, $orderId > 0 ? $orderId : null, $currentUserId);
    } else {
        $targetUserId = $currentUserId;

        if ($orderId > 0) {
            $orderStmt = $conn->prepare('SELECT order_id FROM orders WHERE order_id = ? AND user_id = ? AND binned = 0 LIMIT 1');
            if (!$orderStmt) {
                throw new Exception('Failed to prepare order validation query');
            }
            $orderStmt->bind_param('ii', $orderId, $targetUserId);
            $orderStmt->execute();
            $orderRes = $orderStmt->get_result();
            $orderRow = $orderRes ? $orderRes->fetch_assoc() : null;
            $orderStmt->close();

            if (!$orderRow) {
                http_response_code(403);
                echo json_encode(['success' => false, 'message' => 'Order not accessible']);
                exit;
            }
        }

        $conversationId = messageEnsureConversation($conn, $targetUserId, $orderId > 0 ? $orderId : null, messageGetDefaultAdminId($conn));
    }

    if ($conversationId <= 0) {
        throw new Exception('Unable to ensure conversation');
    }

    echo json_encode([
        'success' => true,
        'conversation_id' => $conversationId
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Failed to ensure conversation']);
}
