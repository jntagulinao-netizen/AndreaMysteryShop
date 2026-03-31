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
$quantity = isset($_POST['quantity']) ? intval($_POST['quantity']) : 1;

if (!$cartItemId || $quantity < 0) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid parameters (cart_item_id and quantity required)']);
    exit;
}

$cartStmt = $conn->prepare('SELECT cart_id FROM carts WHERE user_id = ? LIMIT 1');
$cartStmt->bind_param('i', $userId);
$cartStmt->execute();
$res = $cartStmt->get_result();
$cart = $res->fetch_assoc();
if (!$cart) {
    http_response_code(400);
    echo json_encode(['error' => 'Cart not found']);
    exit;
}
$cartId = $cart['cart_id'];

if ($quantity === 0) {
    $delStmt = $conn->prepare('DELETE FROM cart_items WHERE cart_id = ? AND cart_item_id = ?');
    $delStmt->bind_param('ii', $cartId, $cartItemId);
    $delStmt->execute();
} else {
    $upStmt = $conn->prepare('UPDATE cart_items SET quantity = ? WHERE cart_id = ? AND cart_item_id = ?');
    $upStmt->bind_param('iii', $quantity, $cartId, $cartItemId);
    $upStmt->execute();
}

echo json_encode(['success' => true]);
$conn->close();
