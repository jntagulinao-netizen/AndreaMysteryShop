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
$userId = intval($_SESSION['user_id']);

// Handle profile update
$updateMessage = '';
$updateError = '';

// Define profile pictures directory
define('PROFILE_PICTURES_DIR', __DIR__ . '/profile_pictures/');
if (!is_dir(PROFILE_PICTURES_DIR)) {
    mkdir(PROFILE_PICTURES_DIR, 0755, true);
}

// Handle profile picture upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'upload_picture') {
    if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['profile_picture'];
        $allowed = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        $maxSize = 5 * 1024 * 1024; // 5MB

        if (!in_array($file['type'], $allowed)) {
            $updateError = 'Invalid file type. Only JPG, PNG, GIF, and WEBP are allowed.';
        } elseif ($file['size'] > $maxSize) {
            $updateError = 'File size exceeds 5MB limit.';
        } else {
            try {
                // Get existing profile picture to delete
                $oldPicStmt = $conn->prepare('SELECT profile_picture FROM customer_profiles WHERE user_id = ?');
                $oldPicStmt->bind_param('i', $userId);
                $oldPicStmt->execute();
                $oldPicResult = $oldPicStmt->get_result();
                $oldPic = $oldPicResult->fetch_assoc();
                $oldPicStmt->close();

                // Delete old file if exists
                if ($oldPic && $oldPic['profile_picture']) {
                    $oldPath = PROFILE_PICTURES_DIR . basename($oldPic['profile_picture']);
                    if (file_exists($oldPath)) {
                        unlink($oldPath);
                    }
                }

                // Generate unique filename
                $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
                $newFilename = 'user_' . $userId . '_' . time() . '.' . $ext;
                $newPath = PROFILE_PICTURES_DIR . $newFilename;

                if (move_uploaded_file($file['tmp_name'], $newPath)) {
                    $conn->begin_transaction();

                    // Check if customer_profiles exists
                    $checkStmt = $conn->prepare('SELECT user_id FROM customer_profiles WHERE user_id = ?');
                    $checkStmt->bind_param('i', $userId);
                    $checkStmt->execute();
                    $checkResult = $checkStmt->get_result();
                    $hasProfile = $checkResult->num_rows > 0;
                    $checkStmt->close();

                    if ($hasProfile) {
                        $picUpdateStmt = $conn->prepare('UPDATE customer_profiles SET profile_picture = ? WHERE user_id = ?');
                        $picUpdateStmt->bind_param('si', $newFilename, $userId);
                        $picUpdateStmt->execute();
                        $picUpdateStmt->close();
                    } else {
                        $picInsertStmt = $conn->prepare('INSERT INTO customer_profiles (user_id, profile_picture) VALUES (?, ?)');
                        $picInsertStmt->bind_param('is', $userId, $newFilename);
                        $picInsertStmt->execute();
                        $picInsertStmt->close();
                    }

                    $conn->commit();
                    $updateMessage = 'Profile picture uploaded successfully!';
                } else {
                    $updateError = 'Error uploading file.';
                }
            } catch (Exception $e) {
                $conn->rollback();
                $updateError = 'Error: ' . $e->getMessage();
            }
        }
    }
}

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_profile') {
    $fullName = trim($_POST['full_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone_number'] ?? '');
    $gender = trim($_POST['gender'] ?? '');
    $birthday = trim($_POST['birthday'] ?? '');

    if (!$fullName) {
        $updateError = 'Full name is required.';
    } elseif (!$email) {
        $updateError = 'Email is required.';
    } else {
        try {
            $conn->begin_transaction();
            
            // Update users table
            $userUpdateStmt = $conn->prepare('UPDATE users SET full_name = ?, email = ? WHERE user_id = ?');
            $userUpdateStmt->bind_param('ssi', $fullName, $email, $userId);
            $userUpdateStmt->execute();
            $userUpdateStmt->close();

            // Check if customer_profiles exists
            $checkStmt = $conn->prepare('SELECT user_id FROM customer_profiles WHERE user_id = ?');
            $checkStmt->bind_param('i', $userId);
            $checkStmt->execute();
            $checkResult = $checkStmt->get_result();
            $hasProfile = $checkResult->num_rows > 0;
            $checkStmt->close();

            if ($hasProfile) {
                // Update customer_profiles
                $profileUpdateStmt = $conn->prepare('UPDATE customer_profiles SET phone_number = ?, gender = ?, birthday = ? WHERE user_id = ?');
                $profileUpdateStmt->bind_param('sssi', $phone, $gender, $birthday, $userId);
                $profileUpdateStmt->execute();
                $profileUpdateStmt->close();
            } else {
                // Insert into customer_profiles
                $profileInsertStmt = $conn->prepare('INSERT INTO customer_profiles (user_id, phone_number, gender, birthday) VALUES (?, ?, ?, ?)');
                $profileInsertStmt->bind_param('isss', $userId, $phone, $gender, $birthday);
                $profileInsertStmt->execute();
                $profileInsertStmt->close();
            }

            $conn->commit();
            $updateMessage = 'Profile updated successfully!';
            $_SESSION['user_name'] = $fullName;
        } catch (Exception $e) {
            $conn->rollback();
            $updateError = 'Error updating profile: ' . $e->getMessage();
        }
    }
}

$userStmt = $conn->prepare('SELECT full_name, email FROM users WHERE user_id = ?');
$userStmt->bind_param('i', $userId);
$userStmt->execute();
$userResult = $userStmt->get_result();
$user = $userResult->fetch_assoc() ?: ['full_name' => 'User', 'email' => ''];
$userStmt->close();

// Fetch customer_profiles
$profileStmt = $conn->prepare('SELECT phone_number, gender, birthday, profile_picture FROM customer_profiles WHERE user_id = ?');
$profileStmt->bind_param('i', $userId);
$profileStmt->execute();
$profileResult = $profileStmt->get_result();
$profile = $profileResult->fetch_assoc() ?: ['phone_number' => '', 'gender' => '', 'birthday' => '', 'profile_picture' => ''];
$profileStmt->close();

$orderStmt = $conn->prepare('SELECT order_id, recipient_id, order_date, status, payment_method, total_amount, archived, binned FROM orders WHERE user_id = ? ORDER BY order_date DESC');
$orderStmt->bind_param('i', $userId);
$orderStmt->execute();
$orderResult = $orderStmt->get_result();
$orders = [];
while ($row = $orderResult->fetch_assoc()) {
    $orders[] = $row;
}
$orderStmt->close();

$statusCounts = [
    'pending' => 0,
    'processing' => 0,
    'shipped' => 0,
    'delivered' => 0,
    'received' => 0,
    'reviewed' => 0,
    'cancelled' => 0,
];
$toRateCount = 0;
foreach ($orders as $order) {
    $normalizedStatus = strtolower(trim((string)($order['status'] ?? '')));
    if ($normalizedStatus === 'completed') {
        $normalizedStatus = 'delivered';
    }

    if (isset($statusCounts[$normalizedStatus])) {
        $statusCounts[$normalizedStatus]++;
    }

    $isArchived = intval($order['archived'] ?? 0) === 1;
    $isBinned = intval($order['binned'] ?? 0) === 1;
    if (!$isArchived && !$isBinned && $normalizedStatus === 'received') {
        $toRateCount++;
    }
}

$statusDisplay = [
    'pending' => 'Pending',
    'processing' => 'Processing',
    'shipped' => 'Shipped',
    'delivered' => 'Delivered',
    'cancelled' => 'Cancelled',
];

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

function normalize_account_recent_image($rawUrl) {
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

function format_account_recent_price($amount) {
    $value = (float)$amount;
    if (floor($value) == $value) {
        return number_format($value, 0, '.', ',');
    }
    return rtrim(rtrim(number_format($value, 2, '.', ','), '0'), '.');
}

$recentPreviewSql = "SELECT
        p.product_id,
        p.product_name,
        p.price,
    p.average_rating,
    (SELECT COUNT(*) FROM reviews r WHERE r.product_id = p.product_id) AS review_count,
    (SELECT IFNULL(SUM(oi.quantity), 0)
       FROM order_items oi
       JOIN orders o ON o.order_id = oi.order_id
      WHERE oi.product_id = p.product_id
        AND o.status <> 'cancelled') AS order_count,
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
    LIMIT 3";

$recentPreviewStmt = $conn->prepare($recentPreviewSql);
$recentPreviewStmt->bind_param('i', $userId);
$recentPreviewStmt->execute();
$recentPreviewResult = $recentPreviewStmt->get_result();
$recentPreviewItems = [];
while ($row = $recentPreviewResult->fetch_assoc()) {
    $reviewCount = (int)($row['review_count'] ?? 0);
    $recentPreviewItems[] = [
        'product_id' => (int)$row['product_id'],
        'product_name' => $row['product_name'] ?? 'Product',
        'price' => (float)($row['price'] ?? 0),
        'rating' => $reviewCount > 0 ? (float)($row['average_rating'] ?? 0) : 0.0,
        'review_count' => $reviewCount,
        'order_count' => (int)($row['order_count'] ?? 0),
        'product_image' => normalize_account_recent_image($row['product_image'] ?? ''),
    ];
}
$recentPreviewStmt->close();

$recentCountStmt = $conn->prepare('SELECT COUNT(*) AS total_recent FROM user_recent_views WHERE user_id = ?');
$recentCountStmt->bind_param('i', $userId);
$recentCountStmt->execute();
$recentCountResult = $recentCountStmt->get_result();
$recentCountRow = $recentCountResult ? $recentCountResult->fetch_assoc() : null;
$recentTotalCount = (int)($recentCountRow['total_recent'] ?? 0);
$recentCountStmt->close();
$recentPreviewJson = json_encode($recentPreviewItems, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Account - Andrea Mystery Shop</title>
    <link rel="stylesheet" href="main.css">
    <link rel="stylesheet" href="assets/css/user_dashboard_shared.css?v=20260401-1">
    <style>
        html, body { margin: 0; padding: 0; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f8f8f8; padding-bottom: 78px; }
        .container { width: calc(100% - 48px); max-width: none; margin: 0 auto; padding: 104px 0 16px 0; }
        .hero { position: fixed; top: 12px; left: 50%; transform: translateX(-50%); width: calc(100% - 48px); max-width: none; margin: 0; background: #fff; border-radius: 14px; padding: 14px 16px; border: 1px solid #eee; box-shadow: 0 8px 20px rgba(0,0,0,.05); display: flex; justify-content: space-between; align-items: center; min-height: 62px; z-index: 1000; box-sizing: border-box; }
        .hero-text { flex: 1; }
        .hero h1 { margin: 0; font-size: 1.3rem; }
        .hero p { margin: 4px 0 0 0; color: #555; }
        .hero-actions { display: flex; align-items: center; gap: 10px; }
        .hero-profile-trigger { display: flex; align-items: center; justify-content: center; gap: 8px; cursor: pointer; }
        .hero-profile-trigger span { font-size: 0.85rem; color: #555; font-weight: 600; }
        .logout-btn { display: inline-flex; align-items: center; gap: 6px; text-decoration: none; border: 1px solid #e22a39; color: #e22a39; background: #fff; border-radius: 10px; padding: 9px 12px; font-size: 13px; font-weight: 700; transition: background .2s ease, color .2s ease, transform .2s ease; }
        .logout-btn:hover { background: #e22a39; color: #fff; transform: translateY(-1px); }
        .logout-btn svg { width: 16px; height: 16px; }
        .profile-section { display: none; }
        .profile-section.show { display: block; }

        /* Profile Section */
        .profile-section { display: none; background: #fff; border-radius: 14px; padding: 16px; border: 1px solid #eee; box-shadow: 0 8px 20px rgba(0,0,0,.05); margin-top: 18px; }
        .profile-section.show { display: block; }
        .profile-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 14px; }
        .profile-header h2 { margin: 0; font-size: 1.1rem; color: #333; }
        .upload-btn { border: 1px solid #e22a39; background: #fff; color: #e22a39; padding: 8px 12px; border-radius: 8px; cursor: pointer; font-size: 13px; font-weight: 600; }
        .upload-btn:hover { background: #e22a39; color: #fff; }

        /* Profile Picture Section */
        .profile-picture-container { position: relative; width: 100px; height: 100px; margin: 0; }
        .hero-profile-trigger .profile-picture-container { margin: 0; }
        .profile-picture { width: 100%; height: 100%; border-radius: 50%; object-fit: cover; border: 2px solid #e22a39; background: #f0f0f0; cursor: pointer; }
        .picture-overlay { position: absolute; top: 0; left: 0; width: 100%; height: 100%; border-radius: 50%; background: rgba(0,0,0,0.3); display: flex; align-items: center; justify-content: center; opacity: 0; cursor: pointer; transition: opacity .2s ease; }
        .profile-picture-container:hover .picture-overlay { opacity: 1; }
        .pencil-icon { width: 32px; height: 32px; color: #fff; stroke-width: 2; stroke-linecap: round; stroke-linejoin: round; }
        #profilePictureInput { position: absolute; top: 0; left: 0; width: 100%; height: 100%; opacity: 0; cursor: pointer; }

        .profile-grid { display: none !important; }
        .profile-grid.show { display: grid !important; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 14px; }
        .profile-field { padding: 12px; background: #f9f9f9; border-radius: 10px; border: 1px solid #f0f0f0; }
        .profile-field label { display: block; font-size: 12px; font-weight: 600; color: #666; margin-bottom: 4px; text-transform: uppercase; }
        .profile-field input, .profile-field select { width: 100%; border: 1px solid #ddd; border-radius: 8px; padding: 8px 10px; font-size: 14px; box-sizing: border-box; }
        .profile-field input:focus, .profile-field select:focus { outline: none; border-color: #e22a39; box-shadow: 0 0 0 2px rgba(226, 42, 57, 0.1); }
        .profile-field.view { background: transparent; border: none; padding: 0; }
        .profile-field.view input, .profile-field.view select { background: transparent; border: none; padding: 0; font-weight: 500; color: #333; cursor: default; }
        .profile-field.view input:disabled, .profile-field.view select:disabled { color: #333; }
        .profile-actions { display: flex; gap: 10px; margin-top: 16px; }
        .profile-actions button { flex: 1; padding: 10px; border: none; border-radius: 8px; font-weight: 600; cursor: pointer; font-size: 13px; }
        .btn-save { background: #e22a39; color: #fff; }
        .btn-save:hover { background: #c20000; }
        .btn-cancel { background: #f0f0f0; color: #333; border: 1px solid #ddd; }
        .btn-cancel:hover { background: #e8e8e8; }
        .profile-actions { display: none; }
        .profile-actions.show { display: flex; }
        .alert { padding: 12px 14px; border-radius: 8px; margin-bottom: 14px; font-size: 13px; }
        .alert-success { background: #e6f9fd; border-left: 4px solid #0f9c71; color: #0f5541; }
        .alert-error { background: #fff1f1; border-left: 4px solid #c13030; color: #8b0000; }

        .card-grid { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 10px; margin-top: 16px; }
        .status-card { background: #fff; border: 1px solid #f0e9e5; border-radius: 12px; color: #333; padding: 14px; cursor: pointer; transition: transform .2s ease, box-shadow .2s ease; display: flex; flex-direction: column; align-items: center; text-align: center; }
        .status-card:hover { transform: translateY(-2px); box-shadow: 0 4px 16px rgba(0,0,0,.08); }
        .status-card svg { width: 32px; height: 32px; margin-bottom: 8px; color: #e22a39; stroke-width: 1.5; }
        .status-card strong { display: block; font-size: 16px; margin-bottom: 6px; }
        .status-card span { font-size: 12px; color: #777; }
        .card-grid .view-history { grid-column: 1 / -1; }
        .card-grid .view-recent { grid-column: 1 / -1; }
        .recent-preview { margin-top: 14px; background: #fff; border: 1px solid #eee; border-radius: 14px; padding: 12px; }
        .recent-preview-head { display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px; }
        .recent-preview-title { margin: 0; font-size: 18px; font-weight: 800; letter-spacing: 0.2px; color: #222; }
        .recent-preview-more { text-decoration: none; color: #4b5563; font-weight: 700; font-size: 13px; }
        .recent-preview-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(140px, 1fr)); gap: 10px; }
        .recent-preview-grid .product-card { min-height: auto; }
        .recent-preview-grid .product-image { height: auto; flex: 0 0 auto; }
        .recent-preview-grid .main-img { width: 100%; aspect-ratio: 1; height: auto; object-fit: cover; }
        .recent-preview-grid .product-info { padding: 6px; gap: 4px; }
        .recent-preview-grid .product-name { margin: 0 0 4px; font-size: 10px; line-height: 1.2; min-height: 24px; }
        .recent-preview-grid .product-rating { font-size: 10px; margin-bottom: 0; }
        .recent-preview-grid .product-reviews-meta { font-size: 9px; }
        .recent-preview-grid .product-price { margin: 0; font-size: 14px; line-height: 1; }
        .recent-preview-grid .product-stock-meta,
        .recent-preview-grid .product-orders-meta { font-size: 10px; margin-top: 0; }
        .recent-preview-card { text-decoration: none; color: inherit; background: #fff; border: 1px solid #f0f0f0; border-radius: 10px; overflow: hidden; display: flex; flex-direction: column; }
        .recent-preview-thumb { width: 100%; aspect-ratio: 1; object-fit: cover; display: block; background: #f0f0f0; }
        .recent-preview-body { padding: 6px; flex: 1; display: flex; flex-direction: column; }
        .recent-preview-name { margin: 0 0 4px; font-size: 10px; line-height: 1.2; min-height: 24px; display: -webkit-box; -webkit-line-clamp: 2; line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }
        .recent-preview-price { margin: 0; font-size: 14px; font-weight: 800; color: #e22a39; line-height: 1; }
        .recent-preview-empty { font-size: 13px; color: #777; padding: 10px 2px 4px; }
        .back-to-shopping-section { text-align: center; padding: 60px 20px; margin-top: 18px; background: #fff; border-radius: 14px; border: 1px solid #eee; }
        .back-to-shopping-section svg { width: 96px; height: 96px; margin: 0 auto 16px; color: #d1d5db; stroke-width: 1.5; }
        .back-to-shopping-section h2 { font-size: 24px; font-weight: bold; margin-bottom: 8px; color: #333; }
        .back-to-shopping-section p { color: #666; margin-bottom: 24px; }
        .back-to-shopping-btn { display: inline-block; padding: 10px 20px; background: #e22a39; color: #fff; border: none; border-radius: 8px; cursor: pointer; font-size: 14px; font-weight: 600; text-decoration: none; transition: background 0.2s ease; }
        .back-to-shopping-btn:hover { background: #c20000; }

        @media (max-width: 768px) {
            .container { width: calc(100% - 24px); padding-top: 96px; }
            .hero { width: calc(100% - 24px); }
            .hero { top: 8px; }
            .card-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
            .profile-grid { grid-template-columns: 1fr; }
            .hero-actions { gap: 8px; }
            .logout-btn { padding: 8px 10px; font-size: 12px; }
            .recent-preview-title { font-size: 16px; }
            .recent-preview-more { font-size: 11px; }
            .recent-preview-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 8px; }
            .recent-preview-grid .product-info { padding: 4px; }
            .recent-preview-grid .product-name { font-size: 9px; min-height: 20px; }
            .recent-preview-grid .product-rating { font-size: 9px; }
            .recent-preview-grid .product-price { font-size: 12px; }
            .recent-preview-grid .product-stock-meta,
            .recent-preview-grid .product-orders-meta { font-size: 9px; }
            .recent-preview-price { font-size: 12px; }
            .recent-preview-name { font-size: 9px; min-height: 20px; }
            .recent-preview-body { padding: 4px; }
            .back-to-shopping-section { margin-bottom: 60px; }
        }

        .histories { margin-top: 18px; }
        .histories .tabs { display: grid; grid-template-columns: repeat(6, minmax(0, 1fr)); gap: 8px; margin-bottom: 12px; }
        .tab-btn { padding: 8px 10px; border: 1px solid #ddd; border-radius: 8px; background: #fff; font-size: 12px; color: #333; text-align: center; cursor: pointer; }
        .tab-btn.active { border-color: #e22a39; background: #ffe8e8; color: #c20000; font-weight: 700; }

        .orders-list { background: #fff; border: 1px solid #eee; border-radius: 12px; padding: 12px; }
        .order-item { border-bottom: 1px solid #f2f2f2; padding: 10px 0; }
        .order-item:last-child { border-bottom: none; }
        .order-item h4 { margin: 0 0 4px; font-size: 14px; }
        .order-item p { margin: 2px 0; font-size: 12px; color: #555; }

        .status-pill { font-size: 11px; font-weight: 700; padding: 3px 8px; border-radius: 999px; display: inline-block; }
        .status-pending { background: #fff4e5; color: #b35f00; border: 1px solid #ffd8b0; }
        .status-processing { background: #f0f7ff; color: #1a62c3; border: 1px solid #cce2ff; }
        .status-shipped { background: #f6fffb; color: #0f9c71; border: 1px solid #c5f3e5; }
        .status-delivered { background: #e6f9fd; color: #1f8798; border: 1px solid #bfeefd; }
        .status-cancelled { background: #fff1f1; color: #c13030; border: 1px solid #fccfcf; }

        .mobile-bottom-nav.fixed { position: fixed; bottom: 0; left: 0; right: 0; z-index: 999; background: #fff; border-top: 1px solid #ddd; }
        .mobile-bottom-nav .mobile-nav-inner { display: flex; justify-content: space-around; align-items: center; padding: 0 6px; }
        .mobile-bottom-nav a { text-decoration: none; color: #555; font-size: 11px; display:flex; flex-direction:column; align-items:center; gap: 4px; }
        .mobile-bottom-nav a .icon { width: 20px; height: 20px; }
        .mobile-bottom-nav a.active, .mobile-bottom-nav a:hover { color: #e22a39; }
        .mobile-bottom-nav a span { font-size: 11px; }
    </style>
</head>
<body>
    <div class="container">
        <section class="hero">
            <div class="hero-text">
                <h1>My Account</h1>
                <p>Hello, <strong><?php echo htmlspecialchars($user['full_name']); ?></strong>!</p>
            </div>
            <div class="hero-actions">
                <div class="hero-profile-trigger" onclick="toggleEditMode()" title="Edit profile">
                    <div class="profile-picture-container" style="width: 60px; height: 60px;">
                        <form id="pictureUploadForm" method="POST" enctype="multipart/form-data" style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; z-index: 1;">
                            <input type="hidden" name="action" value="upload_picture" />
                            <input type="file" id="profilePictureInput" name="profile_picture" accept="image/*" onchange="this.form.submit()" style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; opacity: 0; cursor: pointer;" />
                        </form>
                        <img id="profileImg" class="profile-picture" src="<?php 
                            $picPath = $profile['profile_picture'] ? 'profile_pictures/' . htmlspecialchars($profile['profile_picture']) : '';
                            echo $picPath ? $picPath : 'data:image/svg+xml,%3Csvg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 24 24%22 fill=%22%23ccc%22%3E%3Cpath d=%22M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z%22/%3E%3C/svg%3E'; 
                        ?>" alt="Profile Picture" />
                        <div class="picture-overlay" style="z-index: 2;">
                            <svg class="pencil-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                                <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                            </svg>
                        </div>
                    </div>
                </div>
                <a href="logout.php" class="logout-btn" title="Log out">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path>
                        <polyline points="16 17 21 12 16 7"></polyline>
                        <line x1="21" y1="12" x2="9" y2="12"></line>
                    </svg>
                    <span>Logout</span>
                </a>
            </div>
        </section>

        <?php if ($updateMessage): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($updateMessage); ?></div>
        <?php endif; ?>

        <?php if ($updateError): ?>
            <div class="alert alert-error"><?php echo htmlspecialchars($updateError); ?></div>
        <?php endif; ?>

        <section class="profile-section">
            <div class="profile-header">
                <h2>Profile Information</h2>
                <button type="button" class="upload-btn" onclick="document.getElementById('profilePictureInput').click()">Upload Profile Picture</button>
            </div>

            <form method="POST" id="profileForm">
                <input type="hidden" name="action" value="update_profile" />
                <div class="profile-grid">
                    <div class="profile-field view">
                        <label>Full Name</label>
                        <input type="text" name="full_name" value="<?php echo htmlspecialchars($user['full_name']); ?>" disabled />
                    </div>
                    <div class="profile-field view">
                        <label>Email Address</label>
                        <input type="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" disabled />
                    </div>
                    <div class="profile-field view">
                        <label>Phone Number</label>
                        <input type="tel" name="phone_number" value="<?php echo htmlspecialchars($profile['phone_number']); ?>" disabled placeholder="Not provided" />
                    </div>
                    <div class="profile-field view">
                        <label>Gender</label>
                        <select name="gender" disabled>
                            <option value="">Not specified</option>
                            <option value="Male" <?php echo $profile['gender'] === 'Male' ? 'selected' : ''; ?>>Male</option>
                            <option value="Female" <?php echo $profile['gender'] === 'Female' ? 'selected' : ''; ?>>Female</option>
                            <option value="Other" <?php echo $profile['gender'] === 'Other' ? 'selected' : ''; ?>>Other</option>
                        </select>
                    </div>
                    <div class="profile-field view">
                        <label>Birthday</label>
                        <input type="date" name="birthday" value="<?php echo htmlspecialchars($profile['birthday']); ?>" disabled />
                    </div>
                </div>
                <div class="profile-actions" id="profileActions">
                    <button type="submit" class="btn-save">Save Changes</button>
                    <button type="button" class="btn-cancel" onclick="toggleEditMode()">Cancel</button>
                </div>
            </form>
        </section>

        <section class="histories">
            <h2 style="font-size: 1.1rem; margin: 16px 0 8px;">My Purchases</h2>
            <div class="card-grid">
                <div class="status-card" onclick="applyFilter('pending')">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="7" width="20" height="14" rx="2" ry="2"/><path d="M16 4V2M8 4V2M2 11h20"/></svg>
                    <strong>To Pay</strong>
                    <span><?php echo $statusCounts['pending']; ?> order(s)</span>
                </div>
                <div class="status-card" onclick="applyFilter('processing')">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"><path d="M14 18v-2m0-4V6m-4 6h8a2 2 0 002-2V8a2 2 0 00-2-2h-8a2 2 0 00-2 2v4a2 2 0 002 2zm0 0h8a2 2 0 012 2v4a2 2 0 01-2 2h-8a2 2 0 01-2-2v-4a2 2 0 012-2z"/><circle cx="9" cy="9" r="1"/></svg>
                    <strong>To Ship</strong>
                    <span><?php echo $statusCounts['processing']; ?> order(s)</span>
                </div>
                <div class="status-card" onclick="applyFilter('shipped')">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"><path d="M21 16V8a2 2 0 00-1-1.73l-7-4a2 2 0 00-2 0l-7 4A2 2 0 003 8v8a2 2 0 001 1.73l7 4a2 2 0 002 0l7-4A2 2 0 0021 16z"/><polyline points="3.27 6.96 12 12.01 20.73 6.96"/><line x1="12" y1="22.08" x2="12" y2="12"/></svg>
                    <strong>To Receive</strong>
                    <span><?php echo $statusCounts['shipped']; ?> order(s)</span>
                </div>
                <div class="status-card" onclick="applyFilter('delivered-unreviewed')">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
                    <strong>To Rate</strong>
                    <span><?php echo $toRateCount; ?> order(s)</span>
                </div>
                <div class="status-card view-history" onclick="location.href='favorites.php'">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78L12 21.23l8.84-8.84a5.5 5.5 0 0 0 0-7.78z"/></svg>
                    <strong>Favorite Products</strong>
                    <span>See all your saved items</span>
                </div>
            </div>

            <div class="recent-preview">
                <div class="recent-preview-head">
                    <h3 class="recent-preview-title">Recently Viewed</h3>
                    <?php if (!empty($recentPreviewItems)): ?>
                        <a href="recent_views.php" class="recent-preview-more">View More ›</a>
                    <?php endif; ?>
                </div>

                <?php if (empty($recentPreviewItems)): ?>
                    <div class="recent-preview-empty">No recently viewed products yet.</div>
                <?php else: ?>
                    <div class="recent-preview-grid" id="recentPreviewGrid"></div>
                <?php endif; ?>
            </div>
        </section>

        <section class="back-to-shopping-section">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"><path d="M9 10L5 6m0 0l4-4m-4 4h14a2 2 0 0 1 2 2v8a2 2 0 0 1-2 2H5"/></svg>
            <h2>Ready for more?</h2>
            <p>Continue exploring our collection</p>
            <a href="user_dashboard.php" class="back-to-shopping-btn">Continue Shopping</a>
        </section>
    </div>

    <nav class="mobile-bottom-nav fixed">
        <div class="mobile-nav-inner">
            <a href="user_dashboard.php">
                <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M3 10.5L12 3l9 7.5V21a1 1 0 0 1-1 1h-5v-7H9v7H4a1 1 0 0 1-1-1V10.5z" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"></path></svg>
                <span>Home</span>
            </a>
            <a href="auction.php">
              <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M14.5 5.5l4 4"></path><path d="M5.5 14.5l4 4"></path><path d="M4 20l6.5-6.5"></path><path d="M9.5 10.5l6-6 4 4-6 6"></path><path d="M12 7l5 5"></path><path d="M2 22h8"></path></svg>
              <span>Auctions</span>
            </a>
             <a href="category_products.php">
                <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polygon points="16 8 12 11 8 16 16 8"/></svg>
                <span>Explore</span>
            </a>
            <a href="account.php" class="active">
                <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M12 12c2.5 0 4.5-2 4.5-4.5S14.5 3 12 3 7.5 5 7.5 7.5 9.5 12 12 12z"/><path d="M4 21c0-4.5 4-8 8-8s8 3.5 8 8"/></svg>
                <span>Account</span>
            </a>
        </div>
    </nav>

    <script src="assets/js/user_dashboard_reusable_ui.js?v=20260401-1"></script>
    <script>
        const recentPreviewProducts = <?php echo $recentPreviewJson ?: '[]'; ?>;

        function formatPesoAccountRecent(value) {
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

        (function renderRecentPreviewCards() {
            const grid = document.getElementById('recentPreviewGrid');
            if (!grid || typeof DashboardReusableUI === 'undefined' || !Array.isArray(recentPreviewProducts)) return;

            grid.innerHTML = recentPreviewProducts.map((item) => {
                const reviewCount = Number(item.review_count || 0);
                const avgRating = reviewCount > 0 ? Number(item.rating || 0).toFixed(1) : '0.0';
                const cardProduct = {
                    id: Number(item.product_id),
                    name: item.product_name || 'Product',
                    reviewCount,
                    groupStock: 1,
                    groupOrderCount: Number(item.order_count || 0)
                };

                return DashboardReusableUI.renderProductCard(cardProduct, {
                    isOutOfStock: false,
                    avgRating,
                    variantCount: 0,
                    priceDisplay: '₱' + formatPesoAccountRecent(item.price),
                    productImage: item.product_image || 'https://via.placeholder.com/600x600?text=No+Image'
                });
            }).join('');
        })();

        function toggleEditMode() {
            const profileSection = document.querySelector('.profile-section');
            const profileGrid = document.querySelector('.profile-grid');
            const profileFields = document.querySelectorAll('.profile-field');
            const profileActions = document.getElementById('profileActions');

            profileSection.classList.toggle('show');
            profileGrid.classList.toggle('show');

            profileFields.forEach(field => {
                field.classList.toggle('view');
                const input = field.querySelector('input, select');
                if (input) {
                    input.disabled = !input.disabled;
                }
            });

            profileActions.classList.toggle('show');
        }

        function applyFilter(status) {
            window.location.href = 'purchase_history.php?status=' + encodeURIComponent(status);
        }
    </script>
</body>
</html>
