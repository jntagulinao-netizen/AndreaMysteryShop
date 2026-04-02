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
$conn->query($createRecentTableSql);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'clear_recent') {
        $clearStmt = $conn->prepare('DELETE FROM user_recent_views WHERE user_id = ?');
        $clearStmt->bind_param('i', $userId);
        $clearStmt->execute();
        $clearStmt->close();
        header('Location: recent_views.php');
        exit;
    }
}

function normalize_recent_image_url($rawUrl) {
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

function format_peso_recent($amount) {
    $value = (float)$amount;
    if (floor($value) == $value) {
        return number_format($value, 0, '.', ',');
    }
    return rtrim(rtrim(number_format($value, 2, '.', ','), '0'), '.');
}

$recentSql = "SELECT
        p.product_id,
        p.product_name,
        p.price,
        p.product_stock,
    p.average_rating,
    (SELECT COUNT(*) FROM reviews r WHERE r.product_id = p.product_id) AS review_count,
    (SELECT IFNULL(SUM(oi.quantity), 0)
       FROM order_items oi
       JOIN orders o ON o.order_id = oi.order_id
      WHERE oi.product_id = p.product_id
        AND o.status <> 'cancelled') AS order_count,
        urv.viewed_at,
        IFNULL((
            SELECT pi.image_url
            FROM product_images pi
            WHERE pi.product_id = p.product_id
              AND LOWER(pi.image_url) REGEXP '\\.(jpg|jpeg|png|gif|webp)$'
            ORDER BY pi.is_pinned DESC, pi.image_id ASC
            LIMIT 1
        ), '') AS product_image
    FROM user_recent_views urv
    JOIN products p ON p.product_id = urv.product_id
    WHERE urv.user_id = ?
      AND p.archived = 0
    ORDER BY urv.viewed_at DESC
    LIMIT 100";

$recentStmt = $conn->prepare($recentSql);
$recentStmt->bind_param('i', $userId);
$recentStmt->execute();
$recentResult = $recentStmt->get_result();

$recentItems = [];
while ($row = $recentResult->fetch_assoc()) {
    $reviewCount = (int)($row['review_count'] ?? 0);
    $recentItems[] = [
        'product_id' => (int)$row['product_id'],
        'product_name' => $row['product_name'] ?? 'Product',
        'price' => (float)($row['price'] ?? 0),
        'product_stock' => (int)($row['product_stock'] ?? 0),
        'rating' => $reviewCount > 0 ? (float)($row['average_rating'] ?? 0) : 0.0,
        'review_count' => $reviewCount,
        'order_count' => (int)($row['order_count'] ?? 0),
        'viewed_at' => $row['viewed_at'] ?? '',
        'product_image' => normalize_recent_image_url($row['product_image'] ?? ''),
    ];
}
$recentStmt->close();
$recentItemsJson = json_encode($recentItems, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recently Viewed - Andrea Mystery Shop</title>
    <link rel="stylesheet" href="main.css">
    <link rel="stylesheet" href="assets/css/user_dashboard_shared.css?v=20260401-1">
    <style>
        html, body { margin: 0; padding: 0; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f4f4f4; color: #1f2937; }
        main { margin: 0 auto 40px; width: 100%; max-width: none; padding: 84px 24px 0; }
        .page-header {
            position: fixed;
            top: 8px;
            left: 24px;
            right: 24px;
            z-index: 20;
            background: #fff;
            border: 1px solid #ececec;
            border-radius: 14px;
            padding: 12px 14px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            box-shadow: 0 8px 18px rgba(15, 23, 42, 0.06);
            margin-bottom: 14px;
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
        .page-title { margin: 0; font-size: 24px; font-weight: 800; letter-spacing: 0.2px; }
        .top-actions { display: flex; align-items: center; gap: 10px; }
        .clear-btn {
            border: 1px solid #efb3bc;
            background: #fff2f5;
            color: #d5334f;
            border-radius: 10px;
            height: 36px;
            padding: 0 12px;
            font-weight: 700;
            cursor: pointer;
        }
        .products-grid .product-name { font-size: 13px; line-height: 1.25; min-height: 34px; }
        .products-grid .product-rating { font-size: 12px; }
        .products-grid .product-reviews-meta { font-size: 11px; }
        .products-grid .product-price { font-size: 18px; }
        .products-grid .product-stock-meta,
        .products-grid .product-orders-meta { font-size: 12px; }

        .empty-state {
            background: #fff;
            border: 1px solid #ececec;
            border-radius: 14px;
            padding: 40px 18px;
            text-align: center;
            margin-top: 14px;
        }
        .empty-state h2 { margin: 0 0 8px; font-size: 22px; }
        .empty-state p { margin: 0 0 16px; color: #6b7280; }
        .empty-state a {
            display: inline-block;
            border-radius: 10px;
            background: #e22a39;
            color: #fff;
            text-decoration: none;
            padding: 10px 16px;
            font-weight: 700;
        }

        @media (max-width: 768px) {
            main { padding: 78px 12px 0; }
            .page-header {
                left: 12px;
                right: 12px;
            }
            .page-title { font-size: 16px; }
            .products-grid { grid-template-columns: repeat(2, minmax(140px, 1fr)); gap: 12px; }
            .products-grid .product-name { font-size: 11px; min-height: 28px; }
            .products-grid .product-rating { font-size: 11px; }
            .products-grid .product-price { font-size: 15px; }
            .products-grid .product-stock-meta,
            .products-grid .product-orders-meta { font-size: 11px; }
        }
    </style>
</head>
<body>
    <main>
        <div class="page-header">
            <div class="header-left">
                <button type="button" class="back-btn" onclick="window.location.href='account.php'">‹</button>
                <h1 class="page-title">Recently Viewed</h1>
            </div>
            <div class="top-actions">
                <?php if (!empty($recentItems)): ?>
                    <form method="POST" style="margin:0;">
                        <input type="hidden" name="action" value="clear_recent">
                        <button type="submit" class="clear-btn">Clear All</button>
                    </form>
                <?php endif; ?>
            </div>
        </div>

        <?php if (empty($recentItems)): ?>
            <section class="empty-state">
                <h2>No recently viewed products</h2>
                <p>Open product details in the shop and they will appear here.</p>
                <a href="user_dashboard.php">Browse Products</a>
            </section>
        <?php else: ?>
            <div class="products-grid" id="recentProductsGrid"></div>
        <?php endif; ?>
    </main>

    <script src="assets/js/user_dashboard_reusable_ui.js?v=20260401-1"></script>
    <?php if (!empty($recentItems)): ?>
        <script>
            const recentProducts = <?php echo $recentItemsJson ?: '[]'; ?>;

            function formatPesoRecent(value) {
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

            (function renderRecentCards() {
                const grid = document.getElementById('recentProductsGrid');
                if (!grid || typeof DashboardReusableUI === 'undefined') return;

                grid.innerHTML = recentProducts.map((item) => {
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

                    return DashboardReusableUI.renderProductCard(cardProduct, {
                        isOutOfStock: stock <= 0,
                        avgRating,
                        variantCount: 0,
                        priceDisplay: '₱' + formatPesoRecent(item.price),
                        productImage: item.product_image || 'https://via.placeholder.com/600x600?text=No+Image'
                    });
                }).join('');
            })();
        </script>
    <?php endif; ?>
</body>
</html>
