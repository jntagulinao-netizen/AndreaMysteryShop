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
$payload = json_decode(file_get_contents('php://input'), true);
$productId = isset($payload['product_id']) ? (int)$payload['product_id'] : 0;

if ($productId <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid product id']);
    exit;
}

$createRecentTableSql = "CREATE TABLE IF NOT EXISTS user_recent_views (
    view_id INT(11) NOT NULL AUTO_INCREMENT,
    user_id INT(11) NOT NULL,
    product_id INT(11) NOT NULL,
    viewed_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (view_id),
    UNIQUE KEY uniq_recent_user_product (user_id, product_id),
    KEY idx_recent_user (user_id),
    KEY idx_recent_product (product_id),
    KEY idx_recent_viewed_at (viewed_at),
    CONSTRAINT fk_recent_views_user FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    CONSTRAINT fk_recent_views_product FOREIGN KEY (product_id) REFERENCES products(product_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci";

if (!$conn->query($createRecentTableSql)) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Unable to prepare recent views table']);
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

$upsertStmt = $conn->prepare('INSERT INTO user_recent_views (user_id, product_id) VALUES (?, ?) ON DUPLICATE KEY UPDATE viewed_at = CURRENT_TIMESTAMP');
$upsertStmt->bind_param('ii', $userId, $productId);
$upsertStmt->execute();
$upsertStmt->close();

echo json_encode([
    'success' => true,
    'product_id' => $productId
]);
$conn->close();
