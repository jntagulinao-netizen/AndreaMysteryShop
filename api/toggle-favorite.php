<?php
header('Content-Type: application/json; charset=utf-8');
session_start();
require __DIR__ . '/../dbConnection.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Please login first']);
    exit;
}

$userId = (int)$_SESSION['user_id'];
$method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'POST');
$payload = $method === 'POST' ? json_decode(file_get_contents('php://input'), true) : [];
$productId = 0;

if ($method === 'GET') {
    $productId = isset($_GET['product_id']) ? (int)$_GET['product_id'] : 0;
} else {
    $productId = isset($payload['product_id']) ? (int)$payload['product_id'] : 0;
    if ($productId <= 0 && isset($_POST['product_id'])) {
        $productId = (int)$_POST['product_id'];
    }
}

if ($productId <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid product id']);
    exit;
}

$createFavoritesTableSql = "CREATE TABLE IF NOT EXISTS user_favorites (
    favorite_id INT(11) NOT NULL AUTO_INCREMENT,
    user_id INT(11) NOT NULL,
    product_id INT(11) NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (favorite_id),
    UNIQUE KEY uniq_user_product (user_id, product_id),
    KEY idx_favorite_user (user_id),
    KEY idx_favorite_product (product_id),
    CONSTRAINT fk_user_favorites_user FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    CONSTRAINT fk_user_favorites_product FOREIGN KEY (product_id) REFERENCES products(product_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci";

if (!$conn->query($createFavoritesTableSql)) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Unable to prepare favorites table']);
    exit;
}

$productStmt = $conn->prepare('SELECT product_id FROM products WHERE product_id = ? LIMIT 1');
$productStmt->bind_param('i', $productId);
$productStmt->execute();
$productResult = $productStmt->get_result();
if (!$productResult || $productResult->num_rows === 0) {
    http_response_code(404);
    echo json_encode(['success' => false, 'error' => 'Product not found']);
    exit;
}
$productStmt->close();

$checkStmt = $conn->prepare('SELECT favorite_id FROM user_favorites WHERE user_id = ? AND product_id = ? LIMIT 1');
$checkStmt->bind_param('ii', $userId, $productId);
$checkStmt->execute();
$checkResult = $checkStmt->get_result();
$existing = $checkResult ? $checkResult->fetch_assoc() : null;
$checkStmt->close();

if ($method === 'GET') {
    echo json_encode([
        'success' => true,
        'product_id' => $productId,
        'is_favorite' => $existing ? 1 : 0
    ]);
    $conn->close();
    exit;
}

$isFavorite = 0;

if ($existing) {
    $deleteStmt = $conn->prepare('DELETE FROM user_favorites WHERE favorite_id = ? LIMIT 1');
    $favoriteId = (int)$existing['favorite_id'];
    $deleteStmt->bind_param('i', $favoriteId);
    $deleteStmt->execute();
    $deleteStmt->close();
    $isFavorite = 0;
} else {
    $insertStmt = $conn->prepare('INSERT INTO user_favorites (user_id, product_id) VALUES (?, ?)');
    $insertStmt->bind_param('ii', $userId, $productId);
    $insertStmt->execute();
    $insertStmt->close();
    $isFavorite = 1;
}

echo json_encode([
    'success' => true,
    'product_id' => $productId,
    'is_favorite' => $isFavorite
]);
$conn->close();
