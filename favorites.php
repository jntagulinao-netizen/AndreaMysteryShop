<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: LogIn.php');
    exit;
}
$role = $_SESSION['user_role'] ?? 'user';
if ($role === 'admin') {
    header('Location: admin_dashboard.php');
    exit;
}

require_once 'dbConnection.php';
$userId = (int)($_SESSION['user_id'] ?? 0);

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
$conn->query($createFavoritesTableSql);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'remove_favorite') {
    $productId = (int)($_POST['product_id'] ?? 0);
    if ($productId > 0) {
        $removeStmt = $conn->prepare('DELETE FROM user_favorites WHERE user_id = ? AND product_id = ? LIMIT 1');
        $removeStmt->bind_param('ii', $userId, $productId);
        $removeStmt->execute();
        $removeStmt->close();
    }
    header('Location: favorites.php');
    exit;
}

function normalize_favorite_image_url($rawUrl) {
    $fallback = 'https://via.placeholder.com/600x600?text=No+Image';
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

    $url = trim($url);
    return $url !== '' ? $url : $fallback;
}

function format_peso_favorite($amount) {
    $value = (float)$amount;
    if (floor($value) == $value) {
        return number_format($value, 0, '.', ',');
    }
    return rtrim(rtrim(number_format($value, 2, '.', ','), '0'), '.');
}

$favoritesSql = "SELECT
        p.product_id,
        p.product_name,
        p.product_description,
        p.price,
        p.product_stock,
    p.average_rating,
    (SELECT COUNT(*) FROM reviews r WHERE r.product_id = p.product_id) AS review_count,
    (SELECT IFNULL(SUM(oi.quantity), 0)
       FROM order_items oi
       JOIN orders o ON o.order_id = oi.order_id
      WHERE oi.product_id = p.product_id
        AND o.status <> 'cancelled') AS order_count,
        p.archived,
        IFNULL((
            SELECT pi.image_url
            FROM product_images pi
            WHERE pi.product_id = p.product_id
              AND LOWER(pi.image_url) REGEXP '\\.(jpg|jpeg|png|gif|webp)$'
            ORDER BY pi.is_pinned DESC, pi.image_id ASC
            LIMIT 1
        ), '') AS product_image
    FROM user_favorites uf
    JOIN products p ON p.product_id = uf.product_id
    WHERE uf.user_id = ?
      AND p.archived = 0
    ORDER BY uf.created_at DESC";

$favoritesStmt = $conn->prepare($favoritesSql);
$favoritesStmt->bind_param('i', $userId);
$favoritesStmt->execute();
$favoritesResult = $favoritesStmt->get_result();

$favorites = [];
while ($row = $favoritesResult->fetch_assoc()) {
    $reviewCount = (int)($row['review_count'] ?? 0);
    $favorites[] = [
        'product_id' => (int)$row['product_id'],
        'product_name' => $row['product_name'] ?? 'Product',
        'product_description' => $row['product_description'] ?? '',
        'price' => (float)($row['price'] ?? 0),
        'product_stock' => (int)($row['product_stock'] ?? 0),
        'rating' => $reviewCount > 0 ? (float)($row['average_rating'] ?? 0) : 0.0,
        'review_count' => $reviewCount,
        'order_count' => (int)($row['order_count'] ?? 0),
        'product_image' => normalize_favorite_image_url($row['product_image'] ?? ''),
    ];
}
$favoritesStmt->close();
$favoritesJson = json_encode($favorites, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Favorite Products - Andrea Mystery Shop</title>
    <link rel="stylesheet" href="main.css">
    <link rel="stylesheet" href="assets/css/user_dashboard_shared.css?v=20260401-1">
    <style>
        html, body { margin: 0; padding: 0; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f6f6f6; color: #1f2937; }
        .page { width: calc(100% - 48px); max-width: none; margin: 0 auto; padding: 18px 0 84px; }
        .header {
            position: sticky;
            top: 8px;
            z-index: 20;
            background: #fff;
            border: 1px solid #ececec;
            border-radius: 14px;
            padding: 12px 14px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            box-shadow: 0 8px 18px rgba(15, 23, 42, 0.06);
        }
        .header-left { display: flex; align-items: center; gap: 10px; }
        .back-btn {
            width: 36px;
            height: 36px;
            border-radius: 10px;
            border: 1px solid #e8e8e8;
            background: #fff;
            font-size: 24px;
            line-height: 1;
            color: #374151;
            cursor: pointer;
        }
        .title-wrap h1 { margin: 0; font-size: 20px; }
        .title-wrap p { margin: 2px 0 0; font-size: 13px; color: #6b7280; }
        .heart-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 8px 12px;
            border-radius: 999px;
            border: 1px solid #f3b1bb;
            background: #fff1f4;
            color: #d92d4d;
            font-weight: 700;
            font-size: 13px;
        }

        .favorites-grid {
            margin-top: 16px;
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
            gap: 14px;
        }
        .favorite-item {
            display: flex;
            flex-direction: column;
            flex: 1;
        }
        .favorites-grid .product-card { min-height: auto; }
        .favorites-grid .product-image { height: auto; flex: 0 0 auto; }
        .favorites-grid .main-img { width: 100%; aspect-ratio: 1; height: auto; object-fit: cover; }
        .favorites-grid .product-info { padding: 10px; gap: 6px; }
        .favorites-grid .product-name { font-size: 13px; line-height: 1.3; min-height: 34px; }
        .favorites-grid .product-rating { font-size: 12px; margin-bottom: 0; }
        .favorites-grid .product-reviews-meta { font-size: 11px; }
        .favorites-grid .product-price { font-size: 18px; line-height: 1; }
        .favorites-grid .product-stock-meta,
        .favorites-grid .product-orders-meta { font-size: 12px; margin-top: 0; }
        .favorite-actions {
            display: grid;
            grid-template-columns: 1fr;
            gap: 6px;
            margin-top: 8px;
        }
        .action-remove {
            border-radius: 10px;
            height: 38px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 13px;
            font-weight: 700;
            text-decoration: none;
            cursor: pointer;
        }
        .action-remove {
            border: 1px solid #f2b4bd;
            background: #fff3f5;
            color: #d22f4c;
        }

        .empty {
            margin-top: 16px;
            background: #fff;
            border: 1px solid #ececec;
            border-radius: 14px;
            padding: 42px 18px;
            text-align: center;
        }
        .empty h2 { margin: 0 0 8px; font-size: 22px; }
        .empty p { margin: 0 0 16px; color: #6b7280; }
        .empty a {
            display: inline-block;
            border-radius: 10px;
            background: #e22a39;
            color: #fff;
            text-decoration: none;
            padding: 10px 16px;
            font-weight: 700;
        }

        @media (max-width: 768px) {
            .page { width: calc(100% - 24px); padding-top: 10px; }
            .title-wrap h1 { font-size: 18px; }
            .heart-badge { font-size: 12px; padding: 7px 10px; }
            .favorites-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 10px; }
            .favorites-grid .product-info { padding: 8px; }
            .favorites-grid .product-name { font-size: 11px; min-height: 28px; }
            .favorites-grid .product-rating { font-size: 11px; }
            .favorites-grid .product-price { font-size: 15px; }
            .favorites-grid .product-stock-meta,
            .favorites-grid .product-orders-meta { font-size: 11px; }
        }
    </style>
</head>
<body>
    <div class="page">
        <div class="header">
            <div class="header-left">
                <button type="button" class="back-btn" onclick="window.location.href='account.php'">‹</button>
                <div class="title-wrap">
                    <h1>Favorite Products</h1>
                    <p>Your saved items</p>
                </div>
            </div>
            <div class="heart-badge">♥ Favorites</div>
        </div>

        <?php if (empty($favorites)): ?>
            <section class="empty">
                <h2>No favorites yet</h2>
                <p>Tap the heart icon on products to save them here.</p>
                <a href="user_dashboard.php">Browse Products</a>
            </section>
        <?php else: ?>
            <section class="favorites-grid" id="favoritesGrid"></section>
        <?php endif; ?>
    </div>

    <script src="assets/js/user_dashboard_reusable_ui.js?v=20260401-1"></script>
    <?php if (!empty($favorites)): ?>
        <script>
            const favoriteProducts = <?php echo $favoritesJson ?: '[]'; ?>;

            function formatPesoFavorite(value) {
                const amount = Number(value || 0);
                if (Number.isNaN(amount)) return '0';
                if (Math.floor(amount) === amount) {
                    return amount.toLocaleString('en-US', { maximumFractionDigits: 0 });
                }
                return amount.toLocaleString('en-US', { minimumFractionDigits: 0, maximumFractionDigits: 2 });
            }

            function openProductModal(productId) {
                window.location.href = 'user_dashboard.php?product_id=' + Number(productId || 0);
            }

            (function renderFavoriteCards() {
                const grid = document.getElementById('favoritesGrid');
                if (!grid || typeof DashboardReusableUI === 'undefined' || !Array.isArray(favoriteProducts)) return;

                grid.innerHTML = favoriteProducts.map((item) => {
                    const stock = Number(item.product_stock || 0);
                    const reviewCount = Number(item.review_count || 0);
                    const avgRating = reviewCount > 0 ? Number(item.rating || 0).toFixed(1) : '0.0';

                    const cardProduct = {
                        id: Number(item.product_id),
                        name: item.product_name || 'Product',
                        reviewCount,
                        groupStock: stock,
                        groupOrderCount: Number(item.order_count || 0)
                    };

                    const cardHtml = DashboardReusableUI.renderProductCard(cardProduct, {
                        isOutOfStock: stock <= 0,
                        avgRating,
                        variantCount: 0,
                        priceDisplay: '₱' + formatPesoFavorite(item.price),
                        productImage: item.product_image || 'https://via.placeholder.com/600x600?text=No+Image'
                    });

                    return `
                        <article class="favorite-item">
                            ${cardHtml}
                            <div class="favorite-actions">
                                <form method="POST" style="margin:0;">
                                    <input type="hidden" name="action" value="remove_favorite">
                                    <input type="hidden" name="product_id" value="${Number(item.product_id)}">
                                    <button type="submit" class="action-remove">Remove ♥</button>
                                </form>
                            </div>
                        </article>
                    `;
                }).join('');
            })();
        </script>
    <?php endif; ?>
</body>
</html>
