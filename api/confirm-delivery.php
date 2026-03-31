<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

require_once '../dbConnection.php';

$input = json_decode(file_get_contents('php://input'), true);
if (!isset($input['order_id'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Order ID is required']);
    exit;
}

$orderId = intval($input['order_id']);
$userId = intval($_SESSION['user_id']);

$verifyQuery = 'SELECT order_id, status FROM orders WHERE order_id = ? AND user_id = ? AND status = ?';
$verifyStmt = $conn->prepare($verifyQuery);
$status = 'delivered';
$verifyStmt->bind_param('iis', $orderId, $userId, $status);
$verifyStmt->execute();
$result = $verifyStmt->get_result();

if ($result->num_rows === 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Order not found or not deliverable']);
    $verifyStmt->close();
    exit;
}
$verifyStmt->close();

$updateQuery = 'UPDATE orders SET status = ? WHERE order_id = ? AND user_id = ?';
$updateStmt = $conn->prepare($updateQuery);
$newStatus = 'received';
$updateStmt->bind_param('sii', $newStatus, $orderId, $userId);

if ($updateStmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'Order received confirmed']);
} else {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Failed to update order status']);
}

$updateStmt->close();
$conn->close();
?>