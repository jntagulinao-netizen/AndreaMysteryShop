<?php
header('Content-Type: application/json; charset=utf-8');
session_start();
require __DIR__ . '/../dbConnection.php';

function normalize_cart_image_url($rawUrl) {
    $fallback = 'https://via.placeholder.com/160x160?text=No+Image';
    $url = trim((string)$rawUrl);
    if ($url === '' || strtolower($url) === 'null') {
        return $fallback;
    }

    if (preg_match('/^(https?:)?\/\//i', $url) || stripos($url, 'data:') === 0 || stripos($url, 'blob:') === 0) {
        return $url;
    }

    $url = str_replace('\\\\', '/', $url);
    $url = str_replace('\\', '/', $url);
    $url = preg_replace('#^[A-Za-z]:/xampp/htdocs/AndreaMysteryShop/#i', '', $url);
    $url = preg_replace('#^/xampp/htdocs/AndreaMysteryShop/#i', '', $url);
    $url = preg_replace('#^\./#', '', $url);

    $workspacePos = stripos($url, 'AndreaMysteryShop/');
    if ($workspacePos !== false) {
        $url = substr($url, $workspacePos + strlen('AndreaMysteryShop/'));
    }

    $url = ltrim($url);
    return $url !== '' ? $url : $fallback;
}

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['items'=>[],'total'=>0]);
    exit;
}
$userId = intval($_SESSION['user_id']);

$stmt = $conn->prepare("SELECT c.cart_id FROM carts c WHERE c.user_id = ? LIMIT 1");
$stmt->bind_param('i', $userId);
$stmt->execute();
$res = $stmt->get_result();
$cart = $res->fetch_assoc();
if (!$cart) {
    echo json_encode(['items'=>[],'total'=>0]);
    exit;
}
$cartId = $cart['cart_id'];

$stmt = $conn->prepare("SELECT ci.cart_item_id, ci.product_id, ci.quantity, p.product_name, p.price, p.product_description, p.product_stock,
                                                         IFNULL((SELECT image_url
                                                                         FROM product_images pi
                                                                         WHERE pi.product_id = p.product_id
                                                                             AND LOWER(pi.image_url) REGEXP '\\.(jpg|jpeg|png|gif|webp)$'
                                                                         ORDER BY pi.is_pinned DESC, pi.image_id ASC
                                                                         LIMIT 1), '') as image_url
                      FROM cart_items ci
                      JOIN products p ON p.product_id = ci.product_id
                      WHERE ci.cart_id = ?");
$stmt->bind_param('i', $cartId);
$stmt->execute();
$res = $stmt->get_result();
$items = [];
$total = 0;
while ($row = $res->fetch_assoc()) {
    $imageUrl = normalize_cart_image_url($row['image_url']);
    $items[] = [
        'id' => (int)$row['cart_item_id'],
        'cart_item_id' => (int)$row['cart_item_id'],
        'product_id' => (int)$row['product_id'],
        'name' => $row['product_name'],
        'price' => (float)$row['price'],
        'quantity' => (int)$row['quantity'],
        'stock' => (int)$row['product_stock'],
        'image' => [$imageUrl],
        'image_url' => $imageUrl,
    ];
    $total += $row['price'] * $row['quantity'];
}

echo json_encode(['items'=>$items,'total'=>round($total,2)]);
$conn->close();
