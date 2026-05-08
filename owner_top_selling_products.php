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

$userId = (int)$_SESSION['user_id'];
$isOwnerAdmin = false;
$isOwnerStmt = $conn->prepare("SELECT is_owner FROM users WHERE user_id = ? AND LOWER(role) = 'admin' LIMIT 1");
if ($isOwnerStmt) {
    $isOwnerStmt->bind_param('i', $userId);
    $isOwnerStmt->execute();
    $isOwnerResult = $isOwnerStmt->get_result();
    if ($isOwnerResult && ($isOwnerRow = $isOwnerResult->fetch_assoc())) {
        $isOwnerAdmin = ((int)($isOwnerRow['is_owner'] ?? 0) === 1);
    }
    $isOwnerStmt->close();
}
if (!$isOwnerAdmin) {
    header('Location: admin_profile.php');
    exit;
}

$selectedMonth = $_GET['month'] ?? '';
$selectedYear = $_GET['year'] ?? '';

if ((int)($_SESSION['owner_admin_access_unlocked'] ?? 0) !== 1) {
    header('Location: owner_admin_access.php');
    exit;
}

function format_peso_display($amount) {
    $value = (float)$amount;
    if (floor($value) == $value) {
        return number_format($value, 0, '.', ',');
    }
    return rtrim(rtrim(number_format($value, 2, '.', ','), '0'), '.');
}

function format_peso_short($amount) {
    $value = floatval($amount);
    if ($value >= 1000000) {
        return number_format($value / 1000000, 1, '.', ',') . 'M';
    }
    if ($value >= 1000) {
        return number_format($value / 1000, 1, '.', ',') . 'k';
    }
    return format_peso_display($value);
}

$topSellers = [];
$totalProducts = 0;
$totalSales = 0;
$totalRevenue = 0.0;

$whereClause = "o.archived = 0 AND o.binned = 0 AND o.status IN ('delivered', 'received', 'reviewed')";
$bindParams = [];
$bindTypes = '';
if (!empty($selectedMonth) && !empty($selectedYear)) {
    $whereClause .= " AND YEAR(o.order_date) = ? AND MONTH(o.order_date) = ?";
    $bindParams = [$selectedYear, $selectedMonth];
    $bindTypes = 'ii';
}

$topSellersSql = 'SELECT
  COALESCE(p.product_name, "Unknown Item") AS product_name,
  p.product_id,
  (SELECT image_url
     FROM product_images pi
     WHERE pi.product_id = p.product_id
       AND LOWER(pi.image_url) REGEXP "\\.(jpg|jpeg|png|gif|webp)$"
     ORDER BY pi.is_pinned DESC, pi.image_id ASC
     LIMIT 1) AS image_url,
  SUM(oi.quantity) AS total_qty,
  SUM(oi.quantity * oi.price) AS total_revenue,
  COUNT(DISTINCT oi.order_id) AS order_count
  FROM order_items oi
  LEFT JOIN products p ON oi.product_id = p.product_id
  LEFT JOIN orders o ON oi.order_id = o.order_id
  WHERE ' . $whereClause . '
  GROUP BY oi.product_id
  ORDER BY total_qty DESC
  LIMIT 10';

$topStmt = $conn->prepare($topSellersSql);
if (!empty($bindParams)) {
    $topStmt->bind_param($bindTypes, ...$bindParams);
}
$topStmt->execute();
$topResult = $topStmt->get_result();

$metricsSql = 'SELECT
  COUNT(DISTINCT oi.product_id) AS total_products,
  SUM(oi.quantity) AS total_sales,
  SUM(oi.quantity * oi.price) AS total_revenue
  FROM order_items oi
  LEFT JOIN orders o ON oi.order_id = o.order_id
  WHERE ' . $whereClause;
$metricsStmt = $conn->prepare($metricsSql);
if (!empty($bindParams)) {
    $metricsStmt->bind_param($bindTypes, ...$bindParams);
}
$metricsStmt->execute();
$metricsResult = $metricsStmt->get_result();

if ($metricsResult && $metricsRow = $metricsResult->fetch_assoc()) {
    $totalProducts = intval($metricsRow['total_products'] ?? 0);
    $totalSales = intval($metricsRow['total_sales'] ?? 0);
    $totalRevenue = floatval($metricsRow['total_revenue'] ?? 0);
}

if ($topResult) {
    while ($row = $topResult->fetch_assoc()) {
        $topSellers[] = [
            'product_id' => intval($row['product_id'] ?? 0),
            'product_name' => $row['product_name'] ?? 'Unknown Item',
            'image_url' => $row['image_url'] ?? '',
            'total_qty' => intval($row['total_qty'] ?? 0),
            'total_revenue' => floatval($row['total_revenue'] ?? 0),
            'order_count' => intval($row['order_count'] ?? 0)
        ];
    }
}
$topSellersRevenue = $topSellers;
usort($topSellersRevenue, function ($a, $b) {
    return $b['total_revenue'] <=> $a['total_revenue'];
});
$topSellersPerformance = array_slice($topSellers, 0, 5);
$topRevenueBreakdown = array_slice($topSellersRevenue, 0, 3);
$topSellingChart = array_map(function ($item) {
    return ['label' => $item['product_name'], 'value' => $item['total_qty']];
}, $topSellers);
$maxTopQty = 1;
if (!empty($topSellers)) {
    $maxTopQty = max(array_column($topSellers, 'total_qty')) ?: 1;
}
?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Owner Top Selling Products</title>
  <link rel="stylesheet" href="main.css">
  <style>
    * { box-sizing: border-box; }
    body {
      margin: 0;
      font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
      background: #f3f4f6;
      color: #111827;
      padding-bottom: 78px;
    }
    .page-header {
      position: fixed;
      top: 16px;
      left: 50%;
      transform: translateX(-50%);
      width: calc(100% - 48px);
      background: #fff;
      z-index: 120;
      display: flex;
      align-items: center;
      gap: 10px;
      padding: 14px 18px;
      border-radius: 14px;
      border: 1px solid #e5e7eb;
    }
    .back-arrow {
      cursor: pointer;
      font-size: 22px;
      color: #111827;
      text-decoration: none;
    }
    .header-content {
      flex: 1;
      min-width: 0;
      display: flex;
      flex-direction: column;
      justify-content: center;
    }
    .header-title {
      font-size: 18px;
      font-weight: 700;
      color: #111827;
    }
    .header-meta {
      font-size: 12px;
      color: #6b7280;
    }
    .wrap {
      width: calc(100% - 48px);
      margin: 0 auto;
      padding: 90px 0 18px;
    }
    .hero {
      background: linear-gradient(135deg, #0f172a, #1e293b);
      color: #fff;
      border-radius: 16px;
      padding: 20px;
      margin-bottom: 18px;
      box-shadow: 0 18px 40px rgba(15, 23, 42, 0.12);
    }
    .hero h1 {
      margin: 0 0 6px;
      font-size: 28px;
    }
    .hero p {
      margin: 0;
      color: #cbd5e1;
      font-size: 14px;
    }
    .filters {
      display: flex;
      flex-wrap: wrap;
      gap: 10px;
      margin-bottom: 18px;
    }
    .actions {
      display: flex;
      flex-wrap: wrap;
      gap: 10px;
      margin-top: 14px;
    }
    .btn {
      display: inline-flex;
      align-items: center;
      justify-content: center;
      gap: 8px;
      border-radius: 10px;
      padding: 10px 14px;
      text-decoration: none;
      font-weight: 700;
      border: 1px solid #d1d5db;
      background: #fff;
      color: #111827;
    }
    .btn.primary {
      background: #0f172a;
      border-color: #0f172a;
      color: #fff;
    }
    .stats-grid {
      display: grid;
      grid-template-columns: repeat(3, minmax(0, 1fr));
      gap: 16px;
      margin: 22px 0 18px;
    }
    .summary-card {
      background: #ffffff;
      border: 1px solid rgba(148, 163, 184, 0.24);
      border-radius: 18px;
      padding: 22px 24px;
      box-shadow: 0 18px 35px rgba(15, 23, 42, 0.06);
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 16px;
    }
    .summary-copy { min-width: 0; }
    .summary-copy span {
      display: block;
      color: #6b7280;
      font-size: 13px;
      margin-bottom: 10px;
    }
    .summary-copy strong {
      display: block;
      font-size: 28px;
      color: #111827;
      line-height: 1;
    }
    .summary-icon {
      width: 46px;
      height: 46px;
      border-radius: 14px;
      display: grid;
      place-items: center;
      background: rgba(99, 102, 241, 0.12);
      color: #4338ca;
      font-size: 18px;
    }
    .section-card {
      background: #ffffff;
      border: 1px solid rgba(148, 163, 184, 0.24);
      border-radius: 22px;
      padding: 24px;
      box-shadow: 0 18px 35px rgba(15, 23, 42, 0.06);
      margin-bottom: 18px;
    }
    .section-card h2 {
      margin: 0 0 8px;
      font-size: 20px;
      color: #111827;
    }
    .section-meta {
      color: #6b7280;
      font-size: 13px;
      margin-top: 4px;
    }
    .top-products-list {
      display: grid;
      gap: 14px;
    }
    .top-product-row {
      padding: 18px 20px;
      border-radius: 18px;
      border: 1px solid rgba(148, 163, 184, 0.24);
      background: #f8fafc;
      min-width: 0;
    }
    .top-product-main {
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 16px;
      flex-wrap: wrap;
      min-width: 0;
    }
    .top-product-left {
      display: flex;
      align-items: center;
      gap: 14px;
      min-width: 0;
      flex: 1 1 0;
    }
    .rank-badge {
      min-width: 40px;
      min-height: 40px;
      border-radius: 14px;
      display: grid;
      place-items: center;
      background: #1d4ed8;
      color: #ffffff;
      font-weight: 700;
    }
    .product-thumb {
      width: 48px;
      height: 48px;
      border-radius: 16px;
      background: #e5e7eb;
      display: grid;
      place-items: center;
      color: #111827;
      font-weight: 700;
      font-size: 18px;
      overflow: hidden;
    }
    .product-thumb img { width: 100%; height: 100%; object-fit: cover; }
    .product-content { min-width: 0; }
    .product-name {
      font-size: 15px;
      font-weight: 700;
      color: #111827;
      margin-bottom: 4px;
      overflow: hidden;
      text-overflow: ellipsis;
      white-space: nowrap;
    }
    .product-subtitle { font-size: 12px; color: #6b7280; }
    .product-revenue {
      font-size: 14px;
      font-weight: 700;
      color: #111827;
      white-space: nowrap;
    }
    .product-progress {
      width: 100%;
      height: 10px;
      margin-top: 10px;
      border-radius: 999px;
      background: #e5e7eb;
      overflow: hidden;
    }
    .progress-fill {
      height: 100%;
      border-radius: 999px;
      background: linear-gradient(90deg, #3b82f6, #2563eb);
      transition: width 0.45s ease;
    }
    .mini-cards {
      display: grid;
      grid-template-columns: 1.45fr 1fr;
      gap: 18px;
      margin-top: 4px;
    }
    .performance-list, .breakdown-list { display: grid; gap: 14px; }
    .performance-row, .breakdown-row {
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 12px;
      padding: 16px 18px;
      border-radius: 16px;
      background: #f8fafc;
      border: 1px solid rgba(148, 163, 184, 0.24);
    }
    .item-left {
      display: flex;
      align-items: center;
      gap: 12px;
      min-width: 0;
    }
    .item-thumb {
      width: 42px;
      height: 42px;
      border-radius: 14px;
      background: #e5e7eb;
      display: grid;
      place-items: center;
      color: #111827;
      font-size: 14px;
      overflow: hidden;
    }
    .item-thumb img { width: 100%; height: 100%; object-fit: cover; }
    .item-copy { min-width: 0; }
    .item-title {
      font-size: 14px;
      font-weight: 700;
      color: #111827;
      white-space: nowrap;
      overflow: hidden;
      text-overflow: ellipsis;
    }
    .item-subtitle { font-size: 12px; color: #6b7280; }
    .trend-pill {
      display: inline-flex;
      align-items: center;
      gap: 6px;
      padding: 6px 10px;
      border-radius: 999px;
      background: rgba(59, 130, 246, 0.12);
      color: #1d4ed8;
      font-size: 12px;
      font-weight: 700;
    }
    .breakdown-value {
      font-size: 14px;
      font-weight: 700;
      color: #111827;
    }
    .topbar-menu { position: relative; }
    .menu-trigger {
      border: 1px solid rgba(148, 163, 184, 0.4);
      border-radius: 12px;
      background: #ffffff;
      color: #111827;
      padding: 10px 14px;
      cursor: pointer;
      box-shadow: 0 8px 20px rgba(15, 23, 42, 0.05);
    }
    .menu-dropdown {
      position: absolute;
      top: calc(100% + 8px);
      right: 0;
      background: #ffffff;
      border: 1px solid rgba(148, 163, 184, 0.24);
      border-radius: 14px;
      box-shadow: 0 16px 35px rgba(15, 23, 42, 0.08);
      display: none;
      min-width: 220px;
      z-index: 100;
    }
    .menu-dropdown.active { display: block; }
    .menu-dropdown a {
      display: block;
      padding: 10px 14px;
      color: #111827;
      text-decoration: none;
      border-bottom: 1px solid rgba(148, 163, 184, 0.18);
      font-size: 13px;
    }
    .menu-dropdown a:last-child { border-bottom: none; }
    .menu-dropdown a:hover { background: #f3f4f6; }
    @media (max-width: 1024px) {
      .stats-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
      .mini-cards { grid-template-columns: 1fr; }
      .page-header { width: calc(100% - 24px); left: 50%; transform: translateX(-50%); }
      .wrap { width: calc(100% - 24px); }
    }
    @media (max-width: 820px) {
      .top-product-main,
      .performance-row,
      .breakdown-row { flex-direction: column; align-items: stretch; }
      .top-product-row,
      .performance-row,
      .breakdown-row { padding: 16px; }
      .product-revenue,
      .breakdown-value,
      .trend-pill { margin-top: 10px; }
      .page-header { flex-wrap: wrap; padding: 16px; }
      .header-content { margin-top: 8px; }
      .topbar-menu { width: auto; margin-left: auto; }
      .menu-trigger { width: auto; }
      .stats-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
      .wrap { padding: 130px 16px 18px; }
      .product-name { white-space: normal; }
    }
    @media (max-width: 560px) {
      .page-header { top: 12px; left: 50%; transform: translateX(-50%); }
      .hero { padding: 20px; }
      .hero h1 { font-size: 24px; }
      .section-card { padding: 20px; }
      .top-product-row,
      .performance-row,
      .breakdown-row { padding: 14px; }
      .summary-card { padding: 18px 20px; }
      .product-thumb { width: 42px; height: 42px; }
      .rank-badge {
        min-width: 32px;
        min-height: 32px;
        font-size: 14px;
      }
      .item-thumb { width: 38px; height: 38px; }
      .top-product-left { gap: 10px; }
      .product-name,
      .item-title { font-size: 13px; }
      .product-subtitle,
      .item-subtitle,
      .section-meta { font-size: 11px; }
      .stats-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
    }
  </style>
</head>
<body>
  <div class="page-header">
    <a class="back-arrow" href="owner_administrative_page.php">‹</a>
    <div class="header-content">
      <div class="header-title">Top Selling Products</div>
      <div class="header-meta">View the best-selling inventory and export product sales metrics.</div>
    </div>
    <div class="topbar-menu">
      <button class="menu-trigger" type="button" onclick="document.querySelector('.menu-dropdown').classList.toggle('active')">Menu</button>
      <div class="menu-dropdown">
        <a href="owner_revenue_analytics.php">Revenue Analytics</a>
        <a href="owner_order_status.php">Order Status Overview</a>
        <a href="owner_top_selling_products.php">Top Selling Products</a>
        <a href="owner_recent_activity.php">Recent Activity Feed</a>
        <a href="owner_customer_management.php">Customer Management</a>
        <a href="owner_auction_summary.php">Auction Summary</a>
        <a href="owner_administrative_page.php?lock=1">Lock Owner Access</a>
      </div>
    </div>
  </div>
  <div class="wrap">
    <div class="hero">
      <h1>Top Selling Products</h1>
      <p>Analyze your highest-selling products and order counts with a detailed exportable report.</p>
      <form method="GET" style="margin-top: 18px;">
        <div class="filters" style="display: flex; flex-wrap: wrap; gap: 10px; margin-bottom: 18px;">
          <select name="month">
            <option value="">All Months</option>
            <?php for ($m=1; $m<=12; $m++): ?>
              <option value="<?= $m ?>" <?= $selectedMonth == $m ? 'selected' : '' ?>><?= date('F', mktime(0,0,0,$m,1)) ?></option>
            <?php endfor; ?>
          </select>
          <select name="year">
            <option value="">All Years</option>
            <?php $currentYear = date('Y'); for ($y=2020; $y<=$currentYear; $y++): ?>
              <option value="<?= $y ?>" <?= $selectedYear == $y ? 'selected' : '' ?>><?= $y ?></option>
            <?php endfor; ?>
          </select>
          <button type="submit" class="btn">Apply Filters</button>
        </div>
        <div class="actions">
          <button type="button" class="btn primary" onclick="downloadTopSellersCsv()">Export Products CSV</button>
          <a class="btn" href="owner_administrative_page.php">Back to Overview</a>
        </div>
      </form>
    </div>

    <div class="stats-grid">
      <div class="summary-card">
        <div class="summary-copy">
          <span>Total Products</span>
          <strong><?= intval($totalProducts) ?></strong>
        </div>
       
      </div>
      <div class="summary-card">
        <div class="summary-copy">
          <span>Total Sales</span>
          <strong><?= number_format($totalSales) ?></strong>
        </div>
       
      </div>
      <div class="summary-card">
        <div class="summary-copy">
          <span>Total Revenue</span>
          <strong>₱<?= format_peso_short($totalRevenue) ?></strong>
        </div>
        
      </div>
    </div>

    <section class="section-card">
      <div class="section-actions">
        <div>
          <h2>Top Products</h2>
          <div class="section-meta">Best-selling SKUs sorted by quantity sold.</div>
        </div>
      </div>
      <div class="top-products-list" id="topProductsList">
        <?php if (empty($topSellers)): ?>
          <div class="empty-chart">No top selling products were found.</div>
        <?php endif; ?>
      </div>
      <div id="topProductsPagination" style="display: flex; justify-content: center; align-items: center; gap: 8px; margin-top: 16px; flex-wrap: wrap;">
      </div>
    </section>

    <div class="mini-cards">
      <section class="section-card">
        <div class="section-actions">
          <div>
            <h2>Product Performance</h2>
            <div class="section-meta">Top sellers by sold units.</div>
          </div>
        </div>
        <div class="performance-list" id="performanceList">
          <?php if (empty($topSellersPerformance)): ?>
            <div class="empty-chart">No product performance data available.</div>
          <?php endif; ?>
        </div>
        <div id="performancePagination" style="display: flex; justify-content: center; align-items: center; gap: 8px; margin-top: 16px; flex-wrap: wrap;">
        </div>
      </section>

      <section class="section-card">
        <div class="section-actions">
          <div>
            <h2>Revenue Breakdown</h2>
            <div class="section-meta">Top revenue generators from your catalog.</div>
          </div>
        </div>
        <div class="breakdown-list" id="breakdownList">
          <?php if (empty($topRevenueBreakdown)): ?>
            <div class="empty-chart">No revenue breakdown data available.</div>
          <?php endif; ?>
        </div>
        <div id="breakdownPagination" style="display: flex; justify-content: center; align-items: center; gap: 8px; margin-top: 16px; flex-wrap: wrap;">
        </div>
      </section>
    </div>
  </div>

  <script>
    const topProductsData = <?php echo json_encode($topSellers, JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT); ?>;
    const performanceData = <?php echo json_encode($topSellersPerformance, JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT); ?>;
    const breakdownData = <?php echo json_encode($topRevenueBreakdown, JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT); ?>;
    const maxQty = <?php echo json_encode($maxTopQty); ?>;
    
    const paginationState = {
      topProducts: { current: 1, perPage: 5 },
      performance: { current: 1, perPage: 5 },
      breakdown: { current: 1, perPage: 5 }
    };
    
    function renderTopProducts() {
      const state = paginationState.topProducts;
      const total = topProductsData.length;
      if (total === 0) {
        document.getElementById('topProductsList').innerHTML = '<div class="empty-chart">No top selling products were found.</div>';
        document.getElementById('topProductsPagination').innerHTML = '';
        return;
      }
      
      const totalPages = Math.ceil(total / state.perPage);
      const startIdx = (state.current - 1) * state.perPage;
      const endIdx = Math.min(startIdx + state.perPage, total);
      const pageItems = topProductsData.slice(startIdx, endIdx);
      
      let html = '';
      pageItems.forEach((product, i) => {
        const realIdx = startIdx + i;
        const fillWidth = maxQty > 0 ? Math.round((product.total_qty / maxQty) * 100) : 0;
        const initial = product.product_name.charAt(0).toUpperCase();
        const thumb = product.image_url ? `<img src="${product.image_url}" alt="${product.product_name}">` : initial;
        html += `<div class="top-product-row">
          <div class="top-product-main">
            <div class="top-product-left">
              <div class="rank-badge">${realIdx + 1}</div>
              <div class="product-thumb">${thumb}</div>
              <div class="product-content">
                <div class="product-name">${product.product_name}</div>
                <div class="product-subtitle">${product.total_qty.toLocaleString()} sales</div>
              </div>
            </div>
            <div class="product-revenue">₱${formatPesoDisplay(product.total_revenue)}</div>
          </div>
          <div class="product-progress">
            <div class="progress-fill" style="width: ${fillWidth}%;"></div>
          </div>
        </div>`;
      });
      document.getElementById('topProductsList').innerHTML = html;
      renderPaginationControls('topProductsPagination', state.current, totalPages, 'topProducts');
    }
    
    function renderPerformance() {
      const state = paginationState.performance;
      const total = performanceData.length;
      if (total === 0) {
        document.getElementById('performanceList').innerHTML = '<div class="empty-chart">No product performance data available.</div>';
        document.getElementById('performancePagination').innerHTML = '';
        return;
      }
      
      const totalPages = Math.ceil(total / state.perPage);
      const startIdx = (state.current - 1) * state.perPage;
      const endIdx = Math.min(startIdx + state.perPage, total);
      const pageItems = performanceData.slice(startIdx, endIdx);
      
      const performanceTotal = performanceData.reduce((sum, p) => sum + p.total_qty, 0);
      
      let html = '';
      pageItems.forEach(product => {
        const share = performanceTotal ? Math.round(product.total_qty / performanceTotal * 100 * 10) / 10 : 0;
        const initial = product.product_name.charAt(0).toUpperCase();
        const thumb = product.image_url ? `<img src="${product.image_url}" alt="${product.product_name}">` : initial;
        html += `<div class="performance-row">
          <div class="item-left">
            <div class="item-thumb">${thumb}</div>
            <div class="item-copy">
              <div class="item-title">${product.product_name}</div>
              <div class="item-subtitle">${product.total_qty.toLocaleString()} units</div>
            </div>
          </div>
          <div class="trend-pill">+${share}%</div>
        </div>`;
      });
      document.getElementById('performanceList').innerHTML = html;
      renderPaginationControls('performancePagination', state.current, totalPages, 'performance');
    }
    
    function renderBreakdown() {
      const state = paginationState.breakdown;
      const total = breakdownData.length;
      if (total === 0) {
        document.getElementById('breakdownList').innerHTML = '<div class="empty-chart">No revenue breakdown data available.</div>';
        document.getElementById('breakdownPagination').innerHTML = '';
        return;
      }
      
      const totalPages = Math.ceil(total / state.perPage);
      const startIdx = (state.current - 1) * state.perPage;
      const endIdx = Math.min(startIdx + state.perPage, total);
      const pageItems = breakdownData.slice(startIdx, endIdx);
      
      let html = '';
      pageItems.forEach(product => {
        const initial = product.product_name.charAt(0).toUpperCase();
        const thumb = product.image_url ? `<img src="${product.image_url}" alt="${product.product_name}">` : initial;
        html += `<div class="breakdown-row">
          <div class="item-left">
            <div class="item-thumb">${thumb}</div>
            <div class="item-copy">
              <div class="item-title">${product.product_name}</div>
              <div class="item-subtitle">Top revenue generator</div>
            </div>
          </div>
          <div class="breakdown-value">₱${formatPesoDisplay(product.total_revenue)}</div>
        </div>`;
      });
      document.getElementById('breakdownList').innerHTML = html;
      renderPaginationControls('breakdownPagination', state.current, totalPages, 'breakdown');
    }
    
    function renderPaginationControls(containerId, current, totalPages, section) {
      if (totalPages <= 1) {
        document.getElementById(containerId).innerHTML = '';
        return;
      }
      
      let html = `<button onclick="goToPage('${section}', ${Math.max(1, current - 1)})" ${current === 1 ? 'disabled' : ''} style="padding: 8px 12px; border: 1px solid #d1d5db; border-radius: 8px; background: ${current === 1 ? '#f3f4f6' : '#fff'}; color: #111827; cursor: ${current === 1 ? 'not-allowed' : 'pointer'}; font-size: 13px;">← Prev</button>`;
      
      for (let p = 1; p <= totalPages; p++) {
        const isActive = p === current;
        html += `<button onclick="goToPage('${section}', ${p})" style="padding: 8px 12px; border: 1px solid ${isActive ? '#0f172a' : '#d1d5db'}; border-radius: 8px; background: ${isActive ? '#0f172a' : '#fff'}; color: ${isActive ? '#fff' : '#111827'}; cursor: pointer; font-size: 13px; font-weight: ${isActive ? '700' : '400'};">${p}</button>`;
      }
      
      html += `<button onclick="goToPage('${section}', ${Math.min(totalPages, current + 1)})" ${current === totalPages ? 'disabled' : ''} style="padding: 8px 12px; border: 1px solid #d1d5db; border-radius: 8px; background: ${current === totalPages ? '#f3f4f6' : '#fff'}; color: #111827; cursor: ${current === totalPages ? 'not-allowed' : 'pointer'}; font-size: 13px;">Next →</button>`;
      
      document.getElementById(containerId).innerHTML = html;
    }
    
    function goToPage(section, page) {
      if (section === 'topProducts') {
        const total = topProductsData.length;
        const totalPages = Math.ceil(total / paginationState.topProducts.perPage);
        if (page >= 1 && page <= totalPages) {
          paginationState.topProducts.current = page;
          renderTopProducts();
        }
      } else if (section === 'performance') {
        const total = performanceData.length;
        const totalPages = Math.ceil(total / paginationState.performance.perPage);
        if (page >= 1 && page <= totalPages) {
          paginationState.performance.current = page;
          renderPerformance();
        }
      } else if (section === 'breakdown') {
        const total = breakdownData.length;
        const totalPages = Math.ceil(total / paginationState.breakdown.perPage);
        if (page >= 1 && page <= totalPages) {
          paginationState.breakdown.current = page;
          renderBreakdown();
        }
      }
    }
    
    function formatPesoDisplay(amount) {
      const value = parseFloat(amount);
      if (Math.floor(value) === value) {
        return value.toLocaleString('en-US', {minimumFractionDigits: 0, maximumFractionDigits: 0});
      }
      return value.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2}).replace(/\.?0+$/, '');
    }
    
    document.addEventListener('DOMContentLoaded', function() {
      renderTopProducts();
      renderPerformance();
      renderBreakdown();
    });
    
    function downloadTopSellersCsv() {
      const month = document.querySelector('select[name="month"]').value;
      const year = document.querySelector('select[name="year"]').value;
      const rows = [
        ['Top Selling Products'],
        [],
        ['Rank', 'Product', 'Quantity Sold', 'Orders'],
      ];
      topProductsData.forEach((product, index) => {
        rows.push([index + 1, product.product_name, product.total_qty, product.order_count]);
      });
      if (rows.length <= 3) {
        alert('No data available for the selected period.');
        return;
      }
      const csvContent = rows.map((row) => row.map((cell) => `"${String(cell).replace(/"/g, '""')}"`).join(',')).join('\r\n');
      const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
      const url = URL.createObjectURL(blob);
      const link = document.createElement('a');
      let filename = 'owner_top_selling_products.csv';
      if (month && year) {
        filename = `owner_top_selling_products_${year}_${month}.csv`;
      }
      link.href = url;
      link.download = filename;
      document.body.appendChild(link);
      link.click();
      document.body.removeChild(link);
      URL.revokeObjectURL(url);
    }
    document.addEventListener('click', (event) => {
      const menu = document.querySelector('.menu-dropdown');
      const trigger = document.querySelector('.menu-trigger');
      if (menu && trigger && !trigger.contains(event.target) && !menu.contains(event.target)) {
        menu.classList.remove('active');
      }
    });
  </script>
  <nav class="mobile-bottom-nav fixed">
    <div class="mobile-nav-inner">
      <a href="owner_administrative_page.php">
        <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M3 10.5L12 3l9 7.5V21a1 1 0 0 1-1 1h-5v-7H9v7H4a1 1 0 0 1-1-1V10.5z" stroke-linecap="round" stroke-linejoin="round"></path></svg>
        <span>Home</span>
      </a>
      <a href="owner_top_selling_products.php" class="active">
        <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M3 7l9-4 9 4-9 4-9-4z"></path><path d="M3 17l9 4 9-4"></path><path d="M3 12l9 4 9-4"></path></svg>
        <span>Top Products</span>
      </a>
      <a href="owner_recent_activity.php">
        <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor"><circle cx="12" cy="12" r="10"></circle><polyline points="12,6 12,12 16,14"></polyline></svg>
        <span>Activity</span>
      </a>
      <a href="owner_customer_management.php">
        <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
        <span>Customers</span>
      </a>
      <a href="owner_auction_summary.php">
        <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M12 3l2.9 5.9 6.5.9-4.7 4.5 1.1 6.4-5.8-3.1-5.8 3.1 1.1-6.4-4.7-4.5 6.5-.9z"></path></svg>
        <span>Auction</span>
      </a>
    </div>
  </nav>
</body>
</html>
