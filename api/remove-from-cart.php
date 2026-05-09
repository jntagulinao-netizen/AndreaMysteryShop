<?php
header('Content-Type: application/json; charset=utf-8');
session_start();
require __DIR__ . '/../dbConnection.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Please login to update cart']);
    exit;
}
$userId = intval($_SESSION['user_id']);
$cartItemId = isset($_POST['cart_item_id']) ? intval($_POST['cart_item_id']) : 0;

if (!$cartItemId) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid cart_item_id']);
    exit;
}

$cartStmt = $conn->prepare('SELECT cart_id FROM carts WHERE user_id = ? LIMIT 1');
$cartStmt->bind_param('i', $userId);
$cartStmt->execute();
$res = $cartStmt->get_result();
$cart = $res->fetch_assoc();
if (!$cart) {
    // Create cart if it doesn't exist
    $insertStmt = $conn->prepare('INSERT INTO carts (user_id) VALUES (?)');
    $insertStmt->bind_param('i', $userId);
    if ($insertStmt->execute()) {
        $cartId = $conn->insert_id;
        $insertStmt->close();
    } else {
        http_response_code(400);
        echo json_encode(['error' => 'Failed to create cart']);
        exit;
    }
} else {
    $cartId = $cart['cart_id'];
}

$delStmt = $conn->prepare('DELETE FROM cart_items WHERE cart_id = ? AND cart_item_id = ?');
$delStmt->bind_param('ii', $cartId, $cartItemId);
$delStmt->execute();

echo json_encode(['success' => true]);
$conn->close();
