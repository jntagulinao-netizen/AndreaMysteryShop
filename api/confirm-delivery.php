<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized - no session']);
    exit;
}

require_once '../dbConnection.php';
require_once '../message_helpers.php';

function getPrimaryOrderProductName(mysqli $conn, int $orderId): string {
    if ($orderId <= 0) {
        return 'your order';
    }

    $stmt = $conn->prepare('SELECT p.product_name FROM order_items oi LEFT JOIN products p ON oi.product_id = p.product_id WHERE oi.order_id = ? ORDER BY oi.order_item_id ASC LIMIT 1');
    if (!$stmt) {
        return 'your order';
    }

    $stmt->bind_param('i', $orderId);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result ? $result->fetch_assoc() : null;
    $stmt->close();

    $name = trim((string)($row['product_name'] ?? ''));
    return $name !== '' ? $name : 'your order';
}

$input = json_decode(file_get_contents('php://input'), true);
if (!isset($input['order_id'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Order ID is required']);
    exit;
}

$orderId = intval($input['order_id']);
$userId = intval($_SESSION['user_id']);

$verifyQuery = 'SELECT order_id, status, delivery_type FROM orders WHERE order_id = ? AND user_id = ?';
$verifyStmt = $conn->prepare($verifyQuery);
$verifyStmt->bind_param('ii', $orderId, $userId);
$verifyStmt->execute();
$result = $verifyStmt->get_result();

if ($result->num_rows === 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Order not found for this user']);
    $verifyStmt->close();
    exit;
}

$currentOrder = $result->fetch_assoc();
$currentStatus = $currentOrder['status'];
$deliveryType = $currentOrder['delivery_type'];
$verifyStmt->close();

if ($currentStatus === 'pickup') {
    $newStatus = 'pickedup';
} elseif ($currentStatus === 'delivered' || $currentStatus === 'pickedup') {
    $newStatus = 'received';
} else {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => "Order status is '{$currentStatus}', not eligible for confirmation. Delivery type: '{$deliveryType}'"]);
    $conn->close();
    exit;
}

$updateQuery = 'UPDATE orders SET status = ? WHERE order_id = ? AND user_id = ?';
$updateStmt = $conn->prepare($updateQuery);
if (!$updateStmt) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Failed to prepare update statement']);
    $conn->close();
    exit;
}
$updateStmt->bind_param('sii', $newStatus, $orderId, $userId);

if ($updateStmt->execute()) {
    $message = ($currentStatus === 'delivered') ? 'Order received confirmed' : 'Order pickup confirmed';

    if ($newStatus === 'received') {
        $adminId = messageGetDefaultAdminId($conn);
        $conversationId = messageEnsureConversation($conn, $userId, $orderId, $adminId);
        if ($conversationId > 0) {
            $primaryProductName = getPrimaryOrderProductName($conn, $orderId);
            $gratitudeMessage = 'Thank you for confirming receipt of ' . $primaryProductName . '. We appreciate your support and hope you enjoy your purchase from Andrea Mystery Shop!';
            messageInsert($conn, $conversationId, 0, 'system', $gratitudeMessage, 'status_notice', 'received');
        }
    }

    echo json_encode(['success' => true, 'message' => $message]);
} else {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Failed to update order status: ' . $updateStmt->error]);
}

$updateStmt->close();
$conn->close();
?>