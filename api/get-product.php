<?php
header('Content-Type: application/json; charset=utf-8');
require __DIR__ . '/../dbConnection.php';

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($id <= 0) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid product ID']);
    exit;
}

$stmt = $conn->prepare("SELECT product_id, product_name, product_description, price, product_stock, category_id FROM products WHERE product_id = ?");
$stmt->bind_param('i', $id);
$stmt->execute();
$res = $stmt->get_result();
$product = $res->fetch_assoc();
if (!$product) {
    http_response_code(404);
    echo json_encode(['error' => 'Product not found']);
    exit;
}

$images = [];
$imgStmt = $conn->prepare("SELECT image_url FROM product_images WHERE product_id = ? ORDER BY image_id ASC");
$imgStmt->bind_param('i', $id);
$imgStmt->execute();
$imgRes = $imgStmt->get_result();
while ($imgRow = $imgRes->fetch_assoc()) {
    $images[] = $imgRow['image_url'];
}
if (empty($images)) {
    $images[] = 'https://via.placeholder.com/900x600?text=No+Image';
}

$product['image'] = $images;
$product['id'] = (int)$product['product_id'];
$product['name'] = $product['product_name'];
$product['desc'] = $product['product_description'];
$product['stock'] = (int)$product['product_stock'];
$product['price'] = (float)$product['price'];
$product['category'] = 'Unknown';
unset($product['product_id'], $product['product_name'], $product['product_description'], $product['product_stock']);

echo json_encode($product);
$conn->close();
