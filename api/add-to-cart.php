<?php
header('Content-Type: application/json; charset=utf-8');
session_start();
require __DIR__ . '/../dbConnection.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Please login to add items to cart']);
    exit;
}
$userId = intval($_SESSION['user_id']);
$productId = isset($_POST['product_id']) ? intval($_POST['product_id']) : 0;
$quantity = isset($_POST['quantity']) ? max(1, intval($_POST['quantity'])) : 1;

if ($productId <= 0) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid product id']);
    exit;
}

// Ensure product stock available
$prodStmt = $conn->prepare('SELECT product_stock FROM products WHERE product_id = ?');
$prodStmt->bind_param('i', $productId);
$prodStmt->execute();
$prodRes = $prodStmt->get_result();
$prod = $prodRes->fetch_assoc();
if (!$prod) {
    http_response_code(404);
    echo json_encode(['error' => 'Product not found']);
    exit;
}
if ($prod['product_stock'] < $quantity) {
    http_response_code(400);
    echo json_encode(['error' => 'Not enough stock']);
    exit;
}

// get or create cart
$cartStmt = $conn->prepare('SELECT cart_id FROM carts WHERE user_id = ? LIMIT 1');
$cartStmt->bind_param('i', $userId);
$cartStmt->execute();
$cartRes = $cartStmt->get_result();
$cart = $cartRes->fetch_assoc();
if (!$cart) {
    $insertStmt = $conn->prepare('INSERT INTO carts (user_id) VALUES (?)');
    $insertStmt->bind_param('i', $userId);
    $insertStmt->execute();
    $cartId = $conn->insert_id;
} else {
    $cartId = $cart['cart_id'];
}

// update or insert cart item
$existsStmt = $conn->prepare('SELECT cart_item_id, quantity FROM cart_items WHERE cart_id = ? AND product_id = ? LIMIT 1');
$existsStmt->bind_param('ii', $cartId, $productId);
$existsStmt->execute();
$existsRes = $existsStmt->get_result();
$exists = $existsRes->fetch_assoc();

if ($exists) {
    $newQty = $exists['quantity'] + $quantity;
    $updateStmt = $conn->prepare('UPDATE cart_items SET quantity = ? WHERE cart_item_id = ?');
    $updateStmt->bind_param('ii', $newQty, $exists['cart_item_id']);
    $updateStmt->execute();
} else {
    $insertItemStmt = $conn->prepare('INSERT INTO cart_items (cart_id, product_id, quantity) VALUES (?, ?, ?)');
    $insertItemStmt->bind_param('iii', $cartId, $productId, $quantity);
    $insertItemStmt->execute();
}

echo json_encode(['success' => true]);
$conn->close();
