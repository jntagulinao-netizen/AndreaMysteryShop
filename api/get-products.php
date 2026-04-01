<?php
session_start();
header('Content-Type: application/json; charset=utf-8');
require __DIR__ . '/../dbConnection.php';

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
$favoritesTableReady = $conn->query($createFavoritesTableSql) === true;

$currentUserId = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : 0;
$favoriteSelect = '0 AS is_favorite';
$favoriteJoin = '';
if ($favoritesTableReady) {
    $favoriteSelect = 'CASE WHEN uf.favorite_id IS NULL THEN 0 ELSE 1 END AS is_favorite';
    $favoriteJoin = $currentUserId > 0
        ? "LEFT JOIN user_favorites uf ON uf.product_id = p.product_id AND uf.user_id = {$currentUserId}"
        : "LEFT JOIN user_favorites uf ON 1 = 0";
}

$includeArchived = isset($_GET['include_archived']) && $_GET['include_archived'] === '1' && (($_SESSION['user_role'] ?? '') === 'admin');

$archiveFilter = $includeArchived ? '' : 'WHERE p.archived = 0';

$sql = "SELECT p.product_id, p.product_name, p.product_description, p.price, p.product_stock, p.category_id, c.category_name, p.parent_product_id, p.archived,
                     (SELECT IFNULL(SUM(oi.quantity), 0)
                            FROM order_items oi
                            JOIN orders o ON o.order_id = oi.order_id
                         WHERE oi.product_id = p.product_id
                             AND o.status <> 'cancelled') AS order_count,
           p.average_rating,
           (SELECT COUNT(*) FROM reviews WHERE product_id = p.product_id) AS review_count,
           $favoriteSelect,
                     IFNULL(GROUP_CONCAT(pi.image_url ORDER BY pi.is_pinned DESC, pi.image_id ASC SEPARATOR '|||'), '') AS image_urls,
                     (SELECT piv.image_url
                            FROM product_images piv
                         WHERE piv.product_id = p.product_id
                             AND LOWER(piv.image_url) REGEXP '\\.(mp4|webm|mov)$'
                         ORDER BY piv.image_id DESC
                         LIMIT 1) AS video_url
    FROM products p
    LEFT JOIN categories c ON p.category_id = c.category_id
    $favoriteJoin
    LEFT JOIN product_images pi ON pi.product_id = p.product_id
        AND LOWER(pi.image_url) REGEXP '\\.(jpg|jpeg|png|gif|webp)$'
    $archiveFilter
        GROUP BY p.product_id, p.product_name, p.product_description, p.price, p.product_stock, p.category_id, c.category_name, p.average_rating, p.parent_product_id, p.archived";

$result = $conn->query($sql);
if (!$result) {
    http_response_code(500);
    echo json_encode(['error' => $conn->error]);
    exit;
}

$products = [];
while ($row = $result->fetch_assoc()) {
    $images = [];
    if (!empty($row['image_urls'])) {
        $images = array_values(array_filter(explode('|||', $row['image_urls']), function($url) {
            return trim($url) !== '';
        }));
    }

    if (empty($images)) {
        $images = ['https://via.placeholder.com/900x600?text=No+Image'];
    }

    $reviewCount = (int)($row['review_count'] ?? 0);
    // Prevent stale average_rating from showing on products with no reviews.
    $rating = $reviewCount > 0 ? (float)($row['average_rating'] ?? 0) : 0.0;

    $products[] = [
        'id' => (int)$row['product_id'], 
        'parent_product_id' => isset($row['parent_product_id']) ? (int)$row['parent_product_id'] : null,
        'archived' => (int)($row['archived'] ?? 0),
        'name' => $row['product_name'],
        'desc' => $row['product_description'],
        'price' => (float)$row['price'],
        'stock' => (int)$row['product_stock'],
        'orderCount' => (int)($row['order_count'] ?? 0),
        'category' => (int)$row['category_id'],
        'categoryName' => $row['category_name'],
        'image' => $images,
        'video_url' => trim((string)($row['video_url'] ?? '')),
        'rating' => $rating,
        'reviewCount' => $reviewCount,
        'is_favorite' => (int)($row['is_favorite'] ?? 0),
        'reviews' => []
    ];
}

echo json_encode($products);
$conn->close();
