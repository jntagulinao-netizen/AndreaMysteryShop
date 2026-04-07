<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: LogIn.php');
    exit;
}
$role = $_SESSION['user_role'] ?? 'user';
if ($role !== 'admin') {
    header('Location: user_dashboard.php');
    exit;
}

require_once 'dbConnection.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = trim((string)($_POST['action'] ?? ''));
    $reviewId = intval($_POST['review_id'] ?? 0);

    if ($reviewId <= 0) {
        $_SESSION['admin_reviews_flash'] = ['type' => 'error', 'message' => 'Invalid review selected.'];
        header('Location: admin_manage_reviews.php');
        exit;
    }

    if ($action === 'delete') {
        $deleteStmt = $conn->prepare('DELETE FROM reviews WHERE review_id = ? LIMIT 1');
        if ($deleteStmt) {
            $deleteStmt->bind_param('i', $reviewId);
            $ok = $deleteStmt->execute();
            $affected = $deleteStmt->affected_rows;
            $deleteStmt->close();

            if ($ok && $affected > 0) {
                $_SESSION['admin_reviews_flash'] = ['type' => 'success', 'message' => 'Review deleted successfully.'];
            } else {
                $_SESSION['admin_reviews_flash'] = ['type' => 'error', 'message' => 'Review not found or already deleted.'];
            }
        } else {
            $_SESSION['admin_reviews_flash'] = ['type' => 'error', 'message' => 'Failed to prepare delete action.'];
        }

        header('Location: admin_manage_reviews.php');
        exit;
    }

    if ($action === 'reply') {
        $replyText = trim((string)($_POST['admin_reply'] ?? ''));
      $focusPid = max(0, intval($_POST['focus_product_id'] ?? 0));
      $focusRid = max(0, intval($_POST['focus_review_id'] ?? $reviewId));
      $redirectSearch = trim((string)($_POST['return_search'] ?? ''));
      $redirectSort = strtolower(trim((string)($_POST['return_sort'] ?? 'newest')));
      if (!in_array($redirectSort, ['newest', 'oldest'], true)) {
        $redirectSort = 'newest';
      }
      $redirectPage = max(1, intval($_POST['return_page'] ?? 1));
      $redirectPerPage = intval($_POST['return_per_page'] ?? 0);
      if (!in_array($redirectPerPage, [4, 10], true)) {
        $redirectPerPage = 10;
      }
      $replyRedirectUrl = 'admin_manage_reviews.php';
      $replyRedirectQuery = [
        'page' => $redirectPage,
        'sort' => $redirectSort,
        'per_page' => $redirectPerPage
      ];
      if ($redirectSearch !== '') {
        $replyRedirectQuery['search'] = $redirectSearch;
      }
      if ($focusPid > 0) {
        $replyRedirectQuery['focus_product_id'] = $focusPid;
      }
      if ($focusRid > 0) {
        $replyRedirectQuery['focus_review_id'] = $focusRid;
      }
      if (!empty($replyRedirectQuery)) {
        $replyRedirectUrl .= '?' . http_build_query($replyRedirectQuery);
      }

      if ($replyText === '') {
        $_SESSION['admin_reviews_flash'] = ['type' => 'error', 'message' => 'Please enter a reply before sending.'];
        header('Location: ' . $replyRedirectUrl);
        exit;
      }
        if (strlen($replyText) > 2000) {
            $_SESSION['admin_reviews_flash'] = ['type' => 'error', 'message' => 'Reply must be 2000 characters or less.'];
        header('Location: ' . $replyRedirectUrl);
            exit;
        }

        $replyValue = $replyText === '' ? null : $replyText;
        $replyAt = $replyText === '' ? null : date('Y-m-d H:i:s');

        $replyBy = $replyText === '' ? null : intval($_SESSION['user_id'] ?? 0);
        if ($replyBy !== null && $replyBy <= 0) {
          $replyBy = null;
        }

        $replyStmt = $conn->prepare('UPDATE reviews SET admin_reply = ?, admin_reply_at = ?, admin_reply_by = ? WHERE review_id = ? LIMIT 1');
        if ($replyStmt) {
          $replyStmt->bind_param('ssii', $replyValue, $replyAt, $replyBy, $reviewId);
            $ok = $replyStmt->execute();
            $replyStmt->close();

            if ($ok) {
                $_SESSION['admin_reviews_flash'] = ['type' => 'success', 'message' => $replyText === '' ? 'Reply removed.' : 'Reply saved successfully.'];
            } else {
                $_SESSION['admin_reviews_flash'] = ['type' => 'error', 'message' => 'Failed to save reply.'];
            }
        } else {
            $_SESSION['admin_reviews_flash'] = ['type' => 'error', 'message' => 'Failed to prepare reply action.'];
        }

        header('Location: ' . $replyRedirectUrl);
        exit;
    }

    $_SESSION['admin_reviews_flash'] = ['type' => 'error', 'message' => 'Unsupported action.'];
    header('Location: admin_manage_reviews.php');
    exit;
}

$flash = $_SESSION['admin_reviews_flash'] ?? null;
unset($_SESSION['admin_reviews_flash']);

$searchTerm = trim((string)($_GET['search'] ?? ''));
$focusProductId = max(0, intval($_GET['focus_product_id'] ?? 0));
$focusReviewId = max(0, intval($_GET['focus_review_id'] ?? 0));
$sortOrder = strtolower(trim((string)($_GET['sort'] ?? 'newest')));
if (!in_array($sortOrder, ['newest', 'oldest'], true)) {
  $sortOrder = 'newest';
}

$currentPage = max(1, intval($_GET['page'] ?? 1));
$requestedPerPage = intval($_GET['per_page'] ?? 0);
$perPage = in_array($requestedPerPage, [4, 10], true) ? $requestedPerPage : 10;

$reviews = [];
$reviewIds = [];

$listSql = 'SELECT
    r.review_id,
    r.product_id,
    r.rating,
    r.review_text,
    r.admin_reply,
    r.admin_reply_at,
    r.created_at,
    r.is_anonymous,
    r.review_image,
    r.review_image_type,
    COALESCE(NULLIF(TRIM(u.full_name), ""), "Anonymous User") AS user_name,
    COALESCE(NULLIF(TRIM(p.product_name), ""), CONCAT("Product #", r.product_id)) AS product_name
FROM reviews r
LEFT JOIN users u ON u.user_id = r.user_id
LEFT JOIN products p ON p.product_id = r.product_id
ORDER BY r.created_at DESC
LIMIT 300';

$listResult = $conn->query($listSql);
if ($listResult) {
    while ($row = $listResult->fetch_assoc()) {
        $rid = intval($row['review_id']);
        $reviews[$rid] = [
            'review_id' => $rid,
            'product_id' => intval($row['product_id']),
            'product_name' => $row['product_name'],
            'rating' => intval($row['rating']),
            'review_text' => trim((string)$row['review_text']),
            'admin_reply' => trim((string)($row['admin_reply'] ?? '')),
            'admin_reply_at' => $row['admin_reply_at'] ?? null,
            'created_at' => $row['created_at'],
            'is_anonymous' => intval($row['is_anonymous'] ?? 0) === 1,
            'user_name' => $row['user_name'],
            'media_type' => null,
            'media_url' => null,
            'media_files' => []
        ];
        $reviewIds[] = $rid;
    }
}

$mediaTableExists = false;
$tableCheck = $conn->query("SHOW TABLES LIKE 'review_media_files'");
if ($tableCheck && $tableCheck->num_rows > 0) {
    $mediaTableExists = true;
}

if ($mediaTableExists && !empty($reviewIds)) {
    $placeholders = implode(',', array_fill(0, count($reviewIds), '?'));
    $mediaSql = "SELECT review_id, media_id, media_type
                 FROM review_media_files
                 WHERE review_id IN ($placeholders)
                 ORDER BY review_id ASC, media_id ASC";

    $mediaStmt = $conn->prepare($mediaSql);
    if ($mediaStmt) {
        $types = str_repeat('i', count($reviewIds));
        $mediaStmt->bind_param($types, ...$reviewIds);
        $mediaStmt->execute();
        $mediaResult = $mediaStmt->get_result();
        while ($m = $mediaResult->fetch_assoc()) {
            $rid = intval($m['review_id']);
            if (!isset($reviews[$rid])) {
                continue;
            }
          $mediaUrl = 'api/get-review-media.php?media_id=' . intval($m['media_id']);
          $mediaType = (string)($m['media_type'] ?? '');
          $reviews[$rid]['media_files'][] = [
            'url' => $mediaUrl,
            'media_type' => $mediaType
          ];

          if (empty($reviews[$rid]['media_url'])) {
            $reviews[$rid]['media_type'] = $mediaType;
            $reviews[$rid]['media_url'] = $mediaUrl;
          }
        }
        $mediaStmt->close();
    }
}

if (!empty($reviews)) {
    foreach ($reviews as $rid => $review) {
        if (!empty($review['media_url'])) {
            continue;
        }
        $legacyStmt = $conn->prepare('SELECT review_image, review_image_type FROM reviews WHERE review_id = ? LIMIT 1');
        if (!$legacyStmt) {
            continue;
        }
        $legacyStmt->bind_param('i', $rid);
        $legacyStmt->execute();
        $legacyRes = $legacyStmt->get_result();
        $legacyRow = $legacyRes ? $legacyRes->fetch_assoc() : null;
        $legacyStmt->close();

        if (!$legacyRow || empty($legacyRow['review_image']) || empty($legacyRow['review_image_type'])) {
            continue;
        }

        $legacyMediaType = (string)$legacyRow['review_image_type'];
        $legacyMediaUrl = 'api/get-review-media.php?review_id=' . $rid;
        $reviews[$rid]['media_type'] = $legacyMediaType;
        $reviews[$rid]['media_url'] = $legacyMediaUrl;
        $reviews[$rid]['media_files'][] = [
          'url' => $legacyMediaUrl,
          'media_type' => $legacyMediaType
        ];
    }
}

$reviews = array_values($reviews);

$productsWithReviews = [];
foreach ($reviews as $review) {
  $pid = intval($review['product_id']);
  $createdTs = strtotime((string)($review['created_at'] ?? '')) ?: 0;
  if (!isset($productsWithReviews[$pid])) {
    $productsWithReviews[$pid] = [
      'product_id' => $pid,
      'product_name' => $review['product_name'],
      'review_count' => 0,
      'rating_total' => 0,
      'latest_review_ts' => $createdTs,
      'preview_media_url' => null,
      'reviews' => []
    ];
  }

  $productsWithReviews[$pid]['review_count']++;
  $productsWithReviews[$pid]['rating_total'] += intval($review['rating']);
  if ($createdTs > intval($productsWithReviews[$pid]['latest_review_ts'])) {
    $productsWithReviews[$pid]['latest_review_ts'] = $createdTs;
  }

  if ($productsWithReviews[$pid]['preview_media_url'] === null && !empty($review['media_files']) && is_array($review['media_files'])) {
    foreach ($review['media_files'] as $mediaFile) {
      if (stripos((string)($mediaFile['media_type'] ?? ''), 'image') !== false && !empty($mediaFile['url'])) {
        $productsWithReviews[$pid]['preview_media_url'] = (string)$mediaFile['url'];
        break;
      }
    }
  }

  $productsWithReviews[$pid]['reviews'][] = $review;
}

foreach ($productsWithReviews as &$product) {
  $count = max(1, intval($product['review_count']));
  $product['avg_rating'] = round(floatval($product['rating_total']) / $count, 1);
  if (empty($product['preview_media_url'])) {
    $product['preview_media_url'] = 'logo.jpg';
  }
}
unset($product);

$productsWithReviews = array_values($productsWithReviews);
$totalProductsWithReviews = count($productsWithReviews);

if ($focusProductId > 0) {
  $productsWithReviews = array_values(array_filter($productsWithReviews, static function (array $product) use ($focusProductId): bool {
    return intval($product['product_id'] ?? 0) === $focusProductId;
  }));
}

if ($searchTerm !== '') {
  $needle = strtolower($searchTerm);
  $productsWithReviews = array_values(array_filter($productsWithReviews, static function (array $product) use ($needle): bool {
    if (stripos((string)($product['product_name'] ?? ''), $needle) !== false) {
      return true;
    }

    foreach (($product['reviews'] ?? []) as $review) {
      if (stripos((string)($review['review_text'] ?? ''), $needle) !== false) {
        return true;
      }
      if (stripos((string)($review['user_name'] ?? ''), $needle) !== false) {
        return true;
      }
    }

    return false;
  }));
}

usort($productsWithReviews, static function (array $a, array $b) use ($sortOrder): int {
  $left = intval($a['latest_review_ts'] ?? 0);
  $right = intval($b['latest_review_ts'] ?? 0);
  if ($left === $right) {
    return 0;
  }
  if ($sortOrder === 'oldest') {
    return $left <=> $right;
  }
  return $right <=> $left;
});

$filteredProductsCount = count($productsWithReviews);
$totalPages = max(1, (int)ceil($filteredProductsCount / $perPage));
if ($currentPage > $totalPages) {
  $currentPage = $totalPages;
}

$offset = ($currentPage - 1) * $perPage;
$pagedProductsWithReviews = array_slice($productsWithReviews, $offset, $perPage);

function buildAdminReviewPageUrl(int $page, string $searchTerm, string $sortOrder, int $perPage, int $focusProductId = 0, int $focusReviewId = 0): string {
  $query = [
    'page' => max(1, $page),
    'sort' => $sortOrder,
    'per_page' => $perPage
  ];
  if ($searchTerm !== '') {
    $query['search'] = $searchTerm;
  }
  if ($focusProductId > 0) {
    $query['focus_product_id'] = $focusProductId;
  }
  if ($focusReviewId > 0) {
    $query['focus_review_id'] = $focusReviewId;
  }
  return 'admin_manage_reviews.php?' . http_build_query($query);
}
?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Manage Reviews - Andrea Mystery Shop</title>
  <link rel="stylesheet" href="main.css">
  <link rel="stylesheet" href="assets/css/reusable_catalog_modal_reviews.css">
  <style>
    * { margin: 0; padding: 0; box-sizing: border-box; }
    html, body { margin: 0; padding: 0; }
    body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f5f5f5; padding-bottom: 78px; }
    .page-container { width: calc(100% - 48px); max-width: none; margin: 0 auto; padding: 16px 0; }

    .page-header {
      position: sticky;
      top: 0;
      z-index: 120;
      display: flex;
      align-items: center;
      gap: 12px;
      padding: 12px 16px;
      background: #fff;
      border-radius: 12px;
      margin-bottom: 8px;
    }
    .back-arrow { cursor: pointer; font-size: 24px; color: #333; line-height: 1; }
    .header-title { font-size: 18px; font-weight: 600; color: #333; flex: 1; }
    .header-meta { font-size: 12px; color: #777; }

    .topbar-menu { position: relative; }
    .menu-trigger {
      width: 34px;
      height: 34px;
      border: 1px solid #ddd;
      border-radius: 8px;
      background: #fff;
      color: #333;
      font-size: 18px;
      cursor: pointer;
      line-height: 1;
    }
    .menu-dropdown {
      position: absolute;
      top: calc(100% + 6px);
      right: 0;
      min-width: 190px;
      background: #fff;
      border: 1px solid #ddd;
      border-radius: 10px;
      box-shadow: 0 8px 20px rgba(0, 0, 0, 0.12);
      display: none;
      z-index: 130;
      overflow: hidden;
    }
    .menu-dropdown.active { display: block; }
    .menu-dropdown a {
      display: block;
      padding: 10px 12px;
      color: #333;
      text-decoration: none;
      font-size: 13px;
      border-bottom: 1px solid #f0f0f0;
    }
    .menu-dropdown a:last-child { border-bottom: none; }
    .menu-dropdown a:hover { background: #f8f8f8; }

    .flash { border-radius: 10px; padding: 10px 12px; margin-bottom: 12px; font-size: 14px; }
    .flash.success { background: #edf8f1; color: #146c2e; border: 1px solid #b7e4c1; }
    .flash.error { background: #fff0f0; color: #8f1414; border: 1px solid #ffc6c6; }

    .local-swal-toast {
      position: fixed;
      top: 88px;
      left: 50%;
      transform: translateX(-50%) translateY(-12px);
      min-width: 240px;
      max-width: 88vw;
      background: #fff;
      border: 1px solid #e8e8e8;
      border-radius: 12px;
      box-shadow: 0 12px 30px rgba(0,0,0,0.18);
      padding: 10px 14px;
      z-index: 35000;
      opacity: 0;
      transition: all 0.22s ease;
      pointer-events: none;
    }
    .local-swal-toast.show {
      opacity: 1;
      transform: translateX(-50%) translateY(0);
    }
    .local-swal-toast .toast-title {
      font-size: 13px;
      font-weight: 700;
      color: #222;
      margin-bottom: 2px;
    }
    .local-swal-toast .toast-text {
      font-size: 12px;
      color: #666;
      line-height: 1.35;
      white-space: pre-wrap;
      word-break: break-word;
    }
    .local-swal-toast.success { border-color: #d8f2df; }
    .local-swal-toast.success .toast-title { color: #17863c; }
    .local-swal-toast.error { border-color: #ffd9de; }
    .local-swal-toast.error .toast-title { color: #c62839; }
    .local-swal-toast.warning { border-color: #ffe6bf; }
    .local-swal-toast.warning .toast-title { color: #b96a00; }

    .summary-card {
      background: #fff;
      border: 1px solid #e5e7eb;
      border-radius: 12px;
      padding: 12px 14px;
      margin-bottom: 12px;
      font-size: 14px;
      color: #555;
    }

    .reviews-toolbar {
      display: flex;
      flex-wrap: wrap;
      gap: 10px;
      align-items: center;
      background: #fff;
      border: 1px solid #e5e7eb;
      border-radius: 12px;
      padding: 10px;
      margin-bottom: 12px;
    }
    .reviews-toolbar input[type="search"],
    .reviews-toolbar select {
      height: 38px;
      border: 1px solid #d1d5db;
      border-radius: 8px;
      padding: 0 10px;
      font-size: 14px;
      color: #111827;
      background: #fff;
    }
    .reviews-toolbar input[type="search"] {
      min-width: 240px;
      flex: 1;
    }
    .pagination-wrap {
      display: flex;
      justify-content: center;
      align-items: center;
      gap: 8px;
      margin-top: 14px;
      flex-wrap: wrap;
    }
    .pagination-link {
      min-width: 36px;
      height: 36px;
      border-radius: 8px;
      border: 1px solid #d1d5db;
      background: #fff;
      color: #374151;
      text-decoration: none;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      padding: 0 10px;
      font-size: 13px;
      font-weight: 700;
    }
    .pagination-link.active {
      border-color: #1d4ed8;
      background: #1d4ed8;
      color: #fff;
    }
    .pagination-link.disabled {
      pointer-events: none;
      opacity: 0.5;
    }

    .reviews-catalog { margin-top: 6px; }
    .reviews-catalog .products-grid {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
      gap: 14px;
    }
    .reviews-catalog .product-card { min-height: 390px; }
    .reviews-catalog .product-image { height: 190px; flex: 0 0 190px; }
    .reviews-catalog .product-image .main-img { width: 100%; height: 100%; object-fit: cover; }
    .reviews-catalog .product-name { min-height: 44px; }
    .review-admin-snippet {
      margin-top: 8px;
      color: #555;
      font-size: 13px;
      line-height: 1.4;
      display: -webkit-box;
      -webkit-line-clamp: 2;
      line-clamp: 2;
      -webkit-box-orient: vertical;
      overflow: hidden;
    }

    .admin-review-modal-body { padding: 0 40px 40px; }
    .product-review-list {
      display: flex;
      flex-direction: column;
      gap: 12px;
      margin-top: 12px;
    }
    .product-review-item {
      border: 1px solid #eceff2;
      border-radius: 10px;
      padding: 12px;
      background: #fff;
    }
    .product-review-item.focused-review {
      border-color: #2d68d8;
      box-shadow: 0 0 0 3px rgba(45, 104, 216, 0.16);
    }
    .admin-review-meta {
      font-size: 13px;
      color: #6b7280;
      margin: 8px 0 12px;
    }
    .admin-review-rating {
      display: inline-block;
      background: #fff4e5;
      border: 1px solid #ffd8a8;
      color: #b45309;
      padding: 4px 8px;
      border-radius: 999px;
      font-size: 12px;
      font-weight: 700;
      margin-bottom: 12px;
    }

    .review-text {
      background: #f9fafb;
      border: 1px solid #eceff2;
      border-radius: 10px;
      padding: 10px;
      font-size: 14px;
      color: #374151;
      line-height: 1.45;
      margin-bottom: 10px;
      white-space: pre-wrap;
      word-break: break-word;
    }

    .media-preview { margin-bottom: 10px; }
    .media-preview a {
      display: inline-block;
      text-decoration: none;
      color: #2563eb;
      font-size: 13px;
      font-weight: 600;
    }

    .modal-media-wrap { margin: 0 0 12px; }
    .modal-media-wrap .review-image { max-width: 100%; max-height: 320px; border-radius: 8px; }
    .modal-media-grid {
      display: flex;
      flex-wrap: wrap;
      gap: 8px;
    }
    .modal-media-grid .review-media-clickable {
      width: 96px;
      height: 96px;
      overflow: hidden;
      border-radius: 8px;
      border: 1px solid #e5e7eb;
      background: #f3f4f6;
    }
    .modal-media-grid .review-media-clickable .review-image {
      width: 100%;
      height: 100%;
      max-width: none;
      max-height: none;
      object-fit: cover;
      border-radius: 0;
      border: none;
      margin: 0;
      display: block;
    }

    .reply-inline {
      display: flex;
      align-items: flex-start;
      gap: 8px;
      margin-bottom: 8px;
    }

    .reply-form textarea {
      flex: 1;
      min-height: 40px;
      max-height: 260px;
      resize: none;
      border: 1px solid #d1d5db;
      border-radius: 8px;
      padding: 9px 10px;
      font-size: 14px;
      font-family: inherit;
      line-height: 1.35;
      overflow: hidden;
    }

    .reply-meta {
      font-size: 12px;
      color: #6b7280;
      margin-bottom: 10px;
    }

    .card-actions {
      display: flex;
      gap: 8px;
      flex-wrap: wrap;
    }

    .btn {
      border: none;
      border-radius: 8px;
      padding: 9px 12px;
      font-size: 13px;
      font-weight: 600;
      cursor: pointer;
    }
    .btn-save { background: #1d4ed8; color: #fff; }
    .btn-save:hover { background: #1e40af; }
    .btn-send-inline { min-width: 70px; }
    .btn-delete { background: #e22a39; color: #fff; }
    .btn-delete:hover { background: #c20000; }

    .delete-form { margin-top: 8px; }

    .empty-state {
      background: #fff;
      border: 1px dashed #cbd5e1;
      border-radius: 12px;
      padding: 30px 16px;
      text-align: center;
      color: #6b7280;
      font-size: 14px;
    }

    @media (max-width: 768px) {
      .page-container { width: calc(100% - 24px); }
      .local-swal-toast {
        top: calc(16px + env(safe-area-inset-top));
        max-width: calc(100vw - 20px);
      }
      .reviews-catalog .products-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 10px; }
      .reviews-catalog .product-card { min-height: 340px; }
      .reviews-catalog .product-image { height: 150px; flex: 0 0 150px; }
      .admin-review-modal-body { padding: 0 16px 80px; }
      .reply-inline { gap: 6px; }
      .btn-send-inline { min-width: 62px; }
      .reviews-toolbar input[type="search"] { min-width: 0; width: 100%; }
    }
  </style>
</head>
<body>
  <div class="page-container">
    <div class="page-header">
      <div class="back-arrow" onclick="window.location.href='admin_dashboard.php'">‹</div>
      <div class="header-title">Manage Reviews</div>
      <div class="header-meta">Updated <?php echo date('d/m/Y H:i:s'); ?></div>
      <div class="topbar-menu">
        <button type="button" class="menu-trigger" onclick="toggleTopbarMenu(event)">...</button>
        <div class="menu-dropdown" id="topbarMenuDropdown">
          <a href="admin_dashboard.php">Admin Dashboard</a>
          <a href="messages.php">Messages</a>
          <a href="admin_orders.php">Admin Orders</a>
          <a href="admin_my_products.php">My Products</a>
          <a href="admin_product_drafts.php">Product Drafts</a>
          <a href="admin_my_products.php?view=archived">Archived Products</a>
          <a href="admin_manage_reviews.php">Manage Reviews</a>
          <a href="admin_profile.php">Admin Profile</a>
          <a href="logout.php">Logout</a>
        </div>
      </div>
    </div>

    <?php if ($flash): ?>
      <div
        id="adminReviewsFlashPayload"
        data-type="<?php echo htmlspecialchars((string)($flash['type'] ?? 'success'), ENT_QUOTES, 'UTF-8'); ?>"
        data-message="<?php echo htmlspecialchars((string)($flash['message'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"
        style="display:none;"
      ></div>
    <?php endif; ?>

    <div class="summary-card">
      Products with reviews: <strong><?php echo intval($totalProductsWithReviews); ?></strong>
      &nbsp;|&nbsp;
      Filtered: <strong><?php echo intval($filteredProductsCount); ?></strong>
      &nbsp;|&nbsp;
      Total reviews: <strong><?php echo count($reviews); ?></strong>
      &nbsp;|&nbsp;
      Page: <strong><?php echo intval($currentPage); ?>/<?php echo intval($totalPages); ?></strong>
    </div>

    <form method="get" class="reviews-toolbar" id="reviewsFilterForm">
      <input type="search" name="search" id="reviewsSearchInput" placeholder="Search product, reviewer, or review text" value="<?php echo htmlspecialchars($searchTerm, ENT_QUOTES, 'UTF-8'); ?>">
      <select name="sort" id="reviewsSortSelect">
        <option value="newest" <?php echo $sortOrder === 'newest' ? 'selected' : ''; ?>>Newest to oldest</option>
        <option value="oldest" <?php echo $sortOrder === 'oldest' ? 'selected' : ''; ?>>Oldest to newest</option>
      </select>
      <input type="hidden" name="per_page" id="reviewsPerPageInput" value="<?php echo intval($perPage); ?>">
      <input type="hidden" name="focus_product_id" id="reviewsFocusProductInput" value="<?php echo intval($focusProductId); ?>">
      <input type="hidden" name="focus_review_id" id="reviewsFocusReviewInput" value="<?php echo intval($focusReviewId); ?>">
    </form>

    <?php if (empty($pagedProductsWithReviews)): ?>
      <div class="empty-state">No user reviews found yet.</div>
    <?php else: ?>
      <div class="reviews-catalog">
        <div class="products-grid">
          <?php foreach ($pagedProductsWithReviews as $product): ?>
            <div class="product-card" onclick="openReviewModal(<?php echo intval($product['product_id']); ?>)">
              <div class="product-image product-image-relative">
                <img src="<?php echo htmlspecialchars($product['preview_media_url']); ?>" alt="<?php echo htmlspecialchars($product['product_name']); ?>" class="main-img" />
                <span class="product-variant-badge"><?php echo intval($product['review_count']); ?> reviews</span>
              </div>
              <div class="product-info">
                <div class="product-name"><?php echo htmlspecialchars($product['product_name']); ?></div>
                <div class="product-rating">★ <?php echo htmlspecialchars(number_format(floatval($product['avg_rating']), 1)); ?> <span class="product-reviews-meta">(<?php echo intval($product['review_count']); ?> reviews)</span></div>
                <div class="product-stock-meta in">Click to manage replies and deletes</div>
                <div class="review-admin-snippet">
                  <?php
                    $latest = $product['reviews'][0]['review_text'] ?? '';
                    echo htmlspecialchars($latest);
                  ?>
                </div>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
        <?php if ($totalPages > 1): ?>
          <div class="pagination-wrap">
            <?php $prevPage = max(1, $currentPage - 1); ?>
            <?php $nextPage = min($totalPages, $currentPage + 1); ?>
            <a class="pagination-link <?php echo $currentPage <= 1 ? 'disabled' : ''; ?>" href="<?php echo htmlspecialchars(buildAdminReviewPageUrl($prevPage, $searchTerm, $sortOrder, $perPage, $focusProductId, $focusReviewId), ENT_QUOTES, 'UTF-8'); ?>">Prev</a>
            <?php for ($p = 1; $p <= $totalPages; $p++): ?>
              <a class="pagination-link <?php echo $p === $currentPage ? 'active' : ''; ?>" href="<?php echo htmlspecialchars(buildAdminReviewPageUrl($p, $searchTerm, $sortOrder, $perPage, $focusProductId, $focusReviewId), ENT_QUOTES, 'UTF-8'); ?>"><?php echo intval($p); ?></a>
            <?php endfor; ?>
            <a class="pagination-link <?php echo $currentPage >= $totalPages ? 'disabled' : ''; ?>" href="<?php echo htmlspecialchars(buildAdminReviewPageUrl($nextPage, $searchTerm, $sortOrder, $perPage, $focusProductId, $focusReviewId), ENT_QUOTES, 'UTF-8'); ?>">Next</a>
          </div>
        <?php endif; ?>
      </div>
    <?php endif; ?>
  </div>

  <div class="product-modal" id="reviewAdminModal">
    <div class="product-detail">
      <span class="close-product" onclick="closeReviewModal()">&times;</span>
      <div class="admin-review-modal-body">
        <h2 class="product-title" id="modalProductName">Review Details</h2>
        <div class="admin-review-meta" id="modalReviewMeta"></div>
        <div class="admin-review-rating" id="modalReviewRating">Average: 0.0/5</div>
        <div class="product-review-list" id="modalProductReviewsList"></div>
      </div>
    </div>
  </div>

  <script src="assets/js/user_dashboard_reusable_ui.js?v=20260406-2"></script>
  <script>
    const productReviews = <?php echo json_encode($pagedProductsWithReviews, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE); ?>;
    const focusProductId = <?php echo intval($focusProductId); ?>;
    const focusReviewId = <?php echo intval($focusReviewId); ?>;
    const returnSearch = <?php echo json_encode($searchTerm, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE); ?>;
    const returnSort = <?php echo json_encode($sortOrder, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE); ?>;
    const returnPage = <?php echo intval($currentPage); ?>;
    const returnPerPage = <?php echo intval($perPage); ?>;
    const productReviewMap = {};
    productReviews.forEach((p) => { productReviewMap[Number(p.product_id)] = p; });

    function toggleTopbarMenu(event) {
      event.stopPropagation();
      const dropdown = document.getElementById('topbarMenuDropdown');
      if (dropdown) {
        dropdown.classList.toggle('active');
      }
    }

    document.addEventListener('click', (event) => {
      const dropdown = document.getElementById('topbarMenuDropdown');
      const menu = document.querySelector('.topbar-menu');
      if (dropdown && menu && !menu.contains(event.target)) {
        dropdown.classList.remove('active');
      }
    });

    function escapeHtml(value) {
      return String(value ?? '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
    }

    function maskReviewerName(name) {
      const raw = String(name || '').trim();
      if (!raw) return 'U***r';
      if (raw.length === 1) return `${raw}***`;
      if (raw.length === 2) return `${raw.charAt(0)}***${raw.charAt(1)}`;
      return `${raw.slice(0, 2)}***${raw.slice(-1)}`;
    }

    function autoGrowTextarea(el) {
      if (!el) return;
      el.style.height = 'auto';
      el.style.height = Math.min(el.scrollHeight, 260) + 'px';
    }

    function updateReplyButtonState(textarea) {
      if (!textarea) return;
      const form = textarea.closest('.reply-form');
      if (!form) return;
      const button = form.querySelector('button[type="submit"]');
      if (!button) return;
      const hasText = textarea.value.trim().length > 0;
      button.disabled = !hasText;
      button.style.opacity = hasText ? '1' : '0.6';
      button.style.cursor = hasText ? 'pointer' : 'not-allowed';
    }

    function wireInlineReviewMediaClicks(scope) {
      if (!scope) return;
      const mediaNodes = scope.querySelectorAll('.js-review-media-open');
      mediaNodes.forEach((node) => {
        if (node.dataset.mediaClickBound === '1') return;
        node.dataset.mediaClickBound = '1';

        const openMedia = (event) => {
          if (event) {
            event.preventDefault();
            event.stopPropagation();
          }
          const mediaUrl = String(node.getAttribute('data-review-media-url') || '').trim();
          const mediaType = String(node.getAttribute('data-review-media-type') || '').trim();
          if (!mediaUrl) return;

          if (typeof window.openReviewMediaLightbox === 'function') {
            window.openReviewMediaLightbox(mediaUrl, mediaType);
            return;
          }

          window.open(mediaUrl, '_blank', 'noopener,noreferrer');
        };

        node.addEventListener('click', openMedia);
        node.addEventListener('keydown', (event) => {
          if (event.key === 'Enter' || event.key === ' ') {
            openMedia(event);
          }
        });
      });
    }

    function handleReplyFormSubmit(event) {
      event.preventDefault();
      const form = event.target;
      const textarea = form.querySelector('textarea[name="admin_reply"]');
      
      if (!textarea) return;

      const replyText = textarea.value.trim();
      if (replyText.length === 0) {
        showLocalSweetAlert('warning', 'Input Required', 'Please enter some text before sending a reply.', 1700);
        return;
      }

      form.submit();
    }

    function renderReviewItems(reviews) {
      return reviews.map((review) => {
        const reviewId = Number(review.review_id) || 0;
        const isFocused = focusReviewId > 0 && reviewId === focusReviewId;
        const mediaFiles = Array.isArray(review.media_files)
          ? review.media_files.filter((item) => item && item.url)
          : (review.media_url ? [{ url: review.media_url, media_type: review.media_type || '' }] : []);

        let mediaNode = '';
        if (mediaFiles.length > 0) {
          if (window.DashboardReusableUI && typeof window.DashboardReusableUI.renderReviewMediaNode === 'function') {
            if (mediaFiles.length > 1) {
              mediaNode = `<div class="modal-media-grid">${mediaFiles.map((item) => window.DashboardReusableUI.renderReviewMediaNode({
                url: item.url,
                media_type: item.media_type || ''
              }, 'review-media-tile')).join('')}</div>`;
            } else {
              mediaNode = window.DashboardReusableUI.renderReviewMediaNode({
                url: mediaFiles[0].url,
                media_type: mediaFiles[0].media_type || ''
              }, 'review-media-single');
            }
          } else {
            mediaNode = mediaFiles.map((item) => '<a href="' + escapeHtml(item.url) + '" target="_blank" rel="noopener noreferrer">View attached media</a>').join('<br>');
          }
        }

        const replyMeta = review.admin_reply_at
          ? ('Last reply saved: ' + escapeHtml(review.admin_reply_at))
          : 'No admin reply yet.';

        return `
          <div class="product-review-item ${isFocused ? 'focused-review' : ''}" data-review-id="${reviewId}">
            <div class="admin-review-meta">
              By: ${escapeHtml(review.is_anonymous ? maskReviewerName(review.user_name) : review.user_name)}
              | ${escapeHtml(review.created_at)}
              | Review #${reviewId}
            </div>
            <div class="admin-review-rating">Rating: ${Number(review.rating)}/5</div>
            <div class="review-text">${escapeHtml(review.review_text || '')}</div>
            ${mediaNode ? `<div class="modal-media-wrap">${mediaNode}</div>` : ''}

            <form method="post" class="reply-form">
              <input type="hidden" name="action" value="reply">
              <input type="hidden" name="review_id" value="${Number(review.review_id)}">
              <input type="hidden" name="focus_product_id" value="${Number(review.product_id || 0)}">
              <input type="hidden" name="focus_review_id" value="${Number(review.review_id || 0)}">
              <input type="hidden" name="return_search" value="${escapeHtml(returnSearch || '')}">
              <input type="hidden" name="return_sort" value="${escapeHtml(returnSort || 'newest')}">
              <input type="hidden" name="return_page" value="${Number(returnPage) || 1}">
              <input type="hidden" name="return_per_page" value="${Number(returnPerPage) || 10}">
              <div class="reply-inline">
                <textarea name="admin_reply" maxlength="2000" rows="1" class="admin-reply-input" placeholder="Write your admin reply here...">${escapeHtml(review.admin_reply || '')}</textarea>
                <button type="submit" class="btn btn-save btn-send-inline" disabled>Send</button>
              </div>
              <div class="reply-meta">${replyMeta}</div>
            </form>

            <form method="post" class="delete-form" onsubmit="return confirmDeleteReview(event, this);">
              <input type="hidden" name="action" value="delete">
              <input type="hidden" name="review_id" value="${Number(review.review_id)}">
              <div class="card-actions">
                <button type="submit" class="btn btn-delete">Delete Review</button>
              </div>
            </form>
          </div>`;
      }).join('');
    }

    function openReviewModal(productId) {
      const product = productReviewMap[Number(productId)];
      if (!product) return;

      const modal = document.getElementById('reviewAdminModal');
      const productName = document.getElementById('modalProductName');
      const reviewMeta = document.getElementById('modalReviewMeta');
      const reviewRating = document.getElementById('modalReviewRating');
      const reviewList = document.getElementById('modalProductReviewsList');

      productName.textContent = product.product_name || ('Product #' + product.product_id);
      reviewMeta.textContent = 'Product ID: ' + product.product_id + ' | Reviews: ' + Number(product.review_count);
      reviewRating.textContent = 'Average: ' + Number(product.avg_rating).toFixed(1) + '/5';
      reviewList.innerHTML = renderReviewItems(product.reviews || []);

      wireInlineReviewMediaClicks(reviewList);

      reviewList.querySelectorAll('.admin-reply-input').forEach((textarea) => {
        autoGrowTextarea(textarea);
        textarea.addEventListener('input', function () {
          autoGrowTextarea(this);
          updateReplyButtonState(this);
        });
        updateReplyButtonState(textarea);
      });

      reviewList.querySelectorAll('.reply-form').forEach((form) => {
        form.addEventListener('submit', handleReplyFormSubmit);
      });

      modal.classList.add('show');
      document.body.style.overflow = 'hidden';

      if (focusReviewId > 0) {
        const focused = reviewList.querySelector(`.product-review-item[data-review-id="${focusReviewId}"]`);
        if (focused && typeof focused.scrollIntoView === 'function') {
          focused.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }
      }
    }

    function closeReviewModal() {
      const modal = document.getElementById('reviewAdminModal');
      if (!modal) return;
      modal.classList.remove('show');
      document.body.style.overflow = '';
    }

    let modalBackdropPointerDown = false;

    function wireReviewModalBackdropClose() {
      const modal = document.getElementById('reviewAdminModal');
      if (!modal) return;

      const detail = modal.querySelector('.product-detail');
      if (detail) {
        detail.addEventListener('click', (event) => {
          event.stopPropagation();
        });
        detail.addEventListener('pointerdown', (event) => {
          event.stopPropagation();
        });
      }

      modal.addEventListener('pointerdown', (event) => {
        modalBackdropPointerDown = event.target === modal;
      });

      modal.addEventListener('pointerup', (event) => {
        const isBackdropTap = modalBackdropPointerDown && event.target === modal;
        modalBackdropPointerDown = false;
        if (isBackdropTap) {
          closeReviewModal();
        }
      });

      modal.addEventListener('pointercancel', () => {
        modalBackdropPointerDown = false;
      });
    }

    let activeLocalSwalToast = null;
    let activeLocalSwalTimer = null;

    function showLocalSweetAlert(type = 'success', title = 'Notice', text = '', duration = 1200) {
      const normalizedType = String(type || 'success').toLowerCase();

      if (activeLocalSwalTimer) {
        window.clearTimeout(activeLocalSwalTimer);
        activeLocalSwalTimer = null;
      }
      if (activeLocalSwalToast && activeLocalSwalToast.parentNode) {
        activeLocalSwalToast.parentNode.removeChild(activeLocalSwalToast);
        activeLocalSwalToast = null;
      }

      const toast = document.createElement('div');
      toast.className = `local-swal-toast ${normalizedType}`;
      toast.innerHTML = `<div class="toast-title">${escapeHtml(title)}</div><div class="toast-text">${escapeHtml(text)}</div>`;
      document.body.appendChild(toast);
      activeLocalSwalToast = toast;

      requestAnimationFrame(() => toast.classList.add('show'));

      activeLocalSwalTimer = window.setTimeout(() => {
        toast.classList.remove('show');
        window.setTimeout(() => {
          if (toast.parentNode) toast.parentNode.removeChild(toast);
          if (activeLocalSwalToast === toast) {
            activeLocalSwalToast = null;
          }
        }, 220);
      }, Number(duration) > 0 ? Number(duration) : 1200);
    }

    function showLocalConfirmModal(title = 'Confirm', text = '', confirmText = 'Continue', cancelText = 'Cancel') {
      return new Promise((resolve) => {
        const overlay = document.createElement('div');
        overlay.className = 'local-confirm-overlay';

        const card = document.createElement('div');
        card.className = 'local-confirm-card';
        card.innerHTML = `
          <div class="local-confirm-title">${escapeHtml(title)}</div>
          <div class="local-confirm-text">${escapeHtml(text)}</div>
          <div class="local-confirm-actions">
            <button type="button" data-role="cancel" class="local-confirm-btn local-confirm-cancel">${escapeHtml(cancelText)}</button>
            <button type="button" data-role="confirm" class="local-confirm-btn local-confirm-submit">${escapeHtml(confirmText)}</button>
          </div>
        `;

        const cleanup = (result) => {
          if (overlay.parentNode) {
            overlay.parentNode.removeChild(overlay);
          }
          resolve(result);
        };

        const cancelBtn = card.querySelector('[data-role="cancel"]');
        const confirmBtn = card.querySelector('[data-role="confirm"]');
        if (cancelBtn) cancelBtn.onclick = () => cleanup(false);
        if (confirmBtn) {
          confirmBtn.focus();
          confirmBtn.onclick = () => cleanup(true);
        }

        overlay.onclick = (event) => {
          if (event.target === overlay) cleanup(false);
        };

        overlay.appendChild(card);
        document.body.appendChild(overlay);
      });
    }

    function confirmDeleteReview(event, formEl) {
      event.preventDefault();
      showLocalConfirmModal('Delete Review', 'Delete this review permanently? This action cannot be undone.', 'Delete', 'Cancel')
        .then((ok) => {
          if (ok && formEl) {
            formEl.submit();
          }
        });
      return false;
    }

    document.addEventListener('keydown', (event) => {
      if (event.key === 'Escape') {
        closeReviewModal();
      }
    });

    (function wireSearchAutoReset() {
      const form = document.getElementById('reviewsFilterForm');
      const searchInput = document.getElementById('reviewsSearchInput');
      const sortSelect = document.getElementById('reviewsSortSelect');
      const perPageInput = document.getElementById('reviewsPerPageInput');
      const focusProductInput = document.getElementById('reviewsFocusProductInput');
      const focusReviewInput = document.getElementById('reviewsFocusReviewInput');
      if (!form || !searchInput) return;

      const syncFocusInputs = () => {
        if (focusProductInput) {
          focusProductInput.value = String(Number(focusProductId) || 0);
        }
        if (focusReviewInput) {
          focusReviewInput.value = String(Number(focusReviewId) || 0);
        }
      };

      const preferredPerPage = window.innerWidth >= 992 ? 10 : 4;
      if (perPageInput && Number(perPageInput.value) !== preferredPerPage) {
        syncFocusInputs();
        perPageInput.value = String(preferredPerPage);
        form.submit();
        return;
      }

      searchInput.addEventListener('input', () => {
        if (searchInput.value.trim() === '') {
          syncFocusInputs();
          form.submit();
        }
      });

      if (sortSelect) {
        sortSelect.addEventListener('change', () => {
          syncFocusInputs();
          form.submit();
        });
      }
    })();

    window.openReviewModal = openReviewModal;
    window.closeReviewModal = closeReviewModal;
    window.confirmDeleteReview = confirmDeleteReview;
    wireReviewModalBackdropClose();
    if (window.DashboardReusableUI && typeof window.DashboardReusableUI.renderReviewMediaNode === 'function') {
      // no-op: forces reusable UI load path for media interactions
    }

    (function showFlashAsLocalSweetAlert() {
      const flashNode = document.getElementById('adminReviewsFlashPayload');
      if (!flashNode) return;
      const type = String(flashNode.getAttribute('data-type') || 'success').toLowerCase();
      const message = String(flashNode.getAttribute('data-message') || '').trim();
      if (!message) return;
      const title = type === 'error' ? 'Action Failed' : 'Action Successful';
      const duration = type === 'error' ? 2000 : 1300;
      showLocalSweetAlert(type, title, message, duration);
    })();

    (function autoOpenFocusedReview() {
      if (focusProductId <= 0) return;
      if (!productReviewMap[focusProductId]) return;
      openReviewModal(focusProductId);

      // Keep deep-link auto-open for first load only; prevent re-open on refresh.
      try {
        const url = new URL(window.location.href);
        url.searchParams.delete('focus_product_id');
        url.searchParams.delete('focus_review_id');
        window.history.replaceState({}, '', url.toString());
      } catch (e) {
        // no-op
      }
    })();
  </script>
</body>
</html>
