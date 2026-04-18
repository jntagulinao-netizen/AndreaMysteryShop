<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

require_once '../dbConnection.php';

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

if (!isset($input['order_id'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Order ID is required']);
    exit;
}

$orderId = intval($input['order_id']);
$userId = intval($_SESSION['user_id']);

// First, verify that the order belongs to the user and is still pending
$verifyQuery = 'SELECT order_id, status, delivery_slot_id FROM orders WHERE order_id = ? AND user_id = ? AND status = ?';
$verifyStmt = $conn->prepare($verifyQuery);
$status = 'pending';
$verifyStmt->bind_param('iis', $orderId, $userId, $status);
$verifyStmt->execute();
$result = $verifyStmt->get_result();

if ($result->num_rows === 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Order not found or cannot be cancelled']);
    $verifyStmt->close();
    exit;
}

$order = $result->fetch_assoc();
$deliverySlotId = $order['delivery_slot_id'];
$verifyStmt->close();

// Start transaction for atomic operation
$conn->begin_transaction();

try {
    // Update the order status to cancelled
    $updateQuery = 'UPDATE orders SET status = ? WHERE order_id = ? AND user_id = ?';
    $updateStmt = $conn->prepare($updateQuery);
    $newStatus = 'cancelled';
    $updateStmt->bind_param('sii', $newStatus, $orderId, $userId);
    
    if (!$updateStmt->execute()) {
        throw new Exception('Failed to update order status');
    }
    
    // Restore product stocks from order items
    $stockRestoreQuery = 'UPDATE products p 
                         INNER JOIN order_items oi ON p.product_id = oi.product_id 
                         SET p.product_stock = p.product_stock + oi.quantity, 
                             p.order_count = GREATEST(0, p.order_count - oi.quantity)
                         WHERE oi.order_id = ?';
    $stockStmt = $conn->prepare($stockRestoreQuery);
    if (!$stockStmt) {
        throw new Exception('Failed to prepare stock restore query');
    }
    $stockStmt->bind_param('i', $orderId);
    if (!$stockStmt->execute()) {
        throw new Exception('Failed to restore product stocks');
    }
    $stockStmt->close();
    
    // If order had a delivery slot, decrement the count
    if ($deliverySlotId) {
        $slotUpdateStmt = $conn->prepare('UPDATE delivery_slots SET current_orders = GREATEST(0, current_orders - 1) WHERE slot_id = ?');
        if (!$slotUpdateStmt) {
            throw new Exception('Failed to prepare slot update');
        }
        $slotUpdateStmt->bind_param('i', $deliverySlotId);
        if (!$slotUpdateStmt->execute()) {
            throw new Exception('Failed to update delivery slot count');
        }
        $slotUpdateStmt->close();
    }
    
    $conn->commit();
    echo json_encode(['success' => true, 'message' => 'Order cancelled successfully']);
    
} catch (Exception $e) {
    $conn->rollback();
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

$updateStmt->close();
$conn->close();
?>
