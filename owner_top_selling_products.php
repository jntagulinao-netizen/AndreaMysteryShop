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

if ((int)($_SESSION['owner_admin_access_unlocked'] ?? 0) !== 1) {
    header('Location: owner_admin_access.php');
    exit;
}

$topSellers = [];
$topSellersSql = 'SELECT COALESCE(p.product_name, "Unknown Item") AS product_name, SUM(oi.quantity) AS total_qty, COUNT(DISTINCT oi.order_id) AS order_count
  FROM order_items oi
  LEFT JOIN products p ON oi.product_id = p.product_id
  GROUP BY oi.product_id
  ORDER BY total_qty DESC
  LIMIT 10';
$topResult = $conn->query($topSellersSql);
if ($topResult) {
    while ($row = $topResult->fetch_assoc()) {
        $topSellers[] = [
            'product_name' => $row['product_name'] ?? 'Unknown Item',
            'total_qty' => intval($row['total_qty'] ?? 0),
            'order_count' => intval($row['order_count'] ?? 0)
        ];
    }
}

$topSellingChart = array_map(function ($item) {
    return ['label' => $item['product_name'], 'value' => $item['total_qty']];
}, $topSellers);
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
    body { margin: 0; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f3f4f6; padding-bottom: 70px; }
    .page-header { position: fixed; top: 16px; left: 50%; transform: translateX(-50%); width: calc(100% - 48px); background: #fff; z-index: 120; display: flex; align-items: center; gap: 10px; padding: 14px 18px; border-radius: 14px; border: 1px solid #e5e7eb; }
    .back-arrow { cursor: pointer; font-size: 22px; color: #111827; }
    .header-content { flex: 1; min-width: 0; display: flex; flex-direction: column; justify-content: center; }
    .header-title { font-size: 18px; font-weight: 700; color: #111827; }
    .header-meta { font-size: 12px; color: #6b7280; }
    .wrap { width: calc(100% - 48px); margin: 0 auto; padding: 90px 0 18px; }
    .hero { background: linear-gradient(135deg, #0f172a, #1e293b); color: #fff; border-radius: 16px; padding: 20px; margin-bottom: 18px; box-shadow: 0 18px 40px rgba(15, 23, 42, 0.12); }
    .hero h1 { margin: 0 0 6px; font-size: 28px; }
    .hero p { margin: 0; color: #cbd5e1; font-size: 14px; }
    .actions { display: flex; flex-wrap: wrap; gap: 10px; margin-top: 14px; }
    .btn { display: inline-flex; align-items: center; justify-content: center; gap: 8px; border-radius: 10px; padding: 10px 14px; text-decoration: none; font-weight: 700; border: 1px solid #d1d5db; background: #fff; color: #111827; }
    .btn.primary { background: #0f172a; border-color: #0f172a; color: #fff; }
    .section-card { background: #fff; border: 1px solid #e5e7eb; border-radius: 18px; padding: 20px; box-shadow: 0 10px 24px rgba(15, 23, 42, 0.04); margin-bottom: 18px; }
    .section-card h2 { margin: 0 0 12px; font-size: 18px; color: #0f172a; }
    .section-actions { display: flex; justify-content: space-between; gap: 10px; align-items: center; margin-bottom: 16px; }
    .chart-bar-area { 
      display: flex; 
      align-items: flex-end; 
      gap: 16px; 
      padding: 20px 0; 
      min-height: 220px; 
      overflow-x: auto;
      justify-content: center;
    }
    .chart-bar { 
      flex: 1 1 100px; 
      max-width: 120px;
      display: flex; 
      flex-direction: column; 
      align-items: center; 
      gap: 12px; 
    }
    .chart-bar-inner { 
      width: 100%; 
      background: linear-gradient(180deg, #e5e7eb 0%, #f8fafc 100%); 
      border-radius: 12px 12px 0 0; 
      overflow: hidden; 
      position: relative; 
      display: flex; 
      align-items: flex-end; 
      justify-content: center; 
      min-height: 140px; 
      border: 1px solid #e2e8f0;
      border-bottom: none;
    }
    .chart-bar-inner span { 
      display: block; 
      width: 100%; 
      border-radius: 12px 12px 0 0; 
      transition: height .32s ease, background .2s ease; 
      min-height: 4px; 
    }
    .chart-label { 
      font-size: 11px; 
      color: #64748b; 
      text-align: center; 
      max-width: 100px;
      overflow: hidden;
      text-overflow: ellipsis;
      white-space: nowrap;
    }
    .chart-value { 
      font-size: 14px; 
      color: #0f172a; 
      font-weight: 700; 
    }
    .empty-chart { width: 100%; min-height: 140px; display: grid; place-items: center; color: #64748b; font-size: 13px; background: #f8fafc; border: 1px solid #e5e7eb; border-radius: 16px; }
    .table-wrap { overflow-x: auto; }
    table { width: 100%; border-collapse: collapse; }
    th, td { padding: 12px 10px; border-bottom: 1px solid #e2e8f0; text-align: left; font-size: 13px; color: #334155; }
    th { background: #f8fafc; font-weight: 700; }
    .topbar-menu { position: relative; }
    .menu-trigger { border: 1px solid #d1d5db; border-radius: 12px; background: #fff; color: #111827; padding: 10px 14px; cursor: pointer; }
    .menu-dropdown { position: absolute; top: calc(100% + 8px); right: 0; background: #fff; border: 1px solid #e2e8f0; border-radius: 14px; box-shadow: 0 16px 35px rgba(15, 23, 42, 0.12); display: none; min-width: 210px; z-index: 100; }
    .menu-dropdown.active { display: block; }
    .menu-dropdown a { display: block; padding: 10px 12px; color: #111827; text-decoration: none; border-bottom: 1px solid #f1f5f9; font-size: 13px; }
    .menu-dropdown a:last-child { border-bottom: none; }
    .menu-dropdown a:hover { background: #f8fafc; }
  </style>
</head>
<body>
  <div class="page-header">
    <a class="back-arrow" href="owner_administrative_page.php">←</a>
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
      <div class="actions">
        <a class="btn primary" href="owner_top_selling_products.php" onclick="event.preventDefault(); downloadTopSellersCsv(event)">Export Products CSV</a>
        <a class="btn" href="owner_administrative_page.php">Back to Overview</a>
      </div>
    </div>

    <section class="section-card">
      <div class="section-actions">
        <div><h2>Product Performance</h2></div>
      </div>
      <div id="topSellingChartArea" class="chart-bar-area"></div>
      <div class="chart-label" id="topSellingChartSubtitle"></div>
    </section>

    <section class="section-card">
      <div class="section-actions">
        <div><h2>Product Performance</h2></div>
      </div>
      <div class="table-wrap">
        <table>
          <thead>
            <tr><th>#</th><th>Product</th><th>Quantity Sold</th><th>Orders</th></tr>
          </thead>
          <tbody>
            <?php foreach ($topSellers as $index => $product): ?>
              <tr>
                <td><?= $index + 1 ?></td>
                <td><?= htmlspecialchars($product['product_name']) ?></td>
                <td><?= intval($product['total_qty']) ?></td>
                <td><?= intval($product['order_count']) ?></td>
              </tr>
            <?php endforeach; ?>
            <?php if (empty($topSellers)): ?>
              <tr><td colspan="4">No top selling products were found.</td></tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </section>
  </div>

  <script>
    const chartData = <?php echo json_encode(['topSellingChart' => $topSellingChart], JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT); ?>;
    const chartColors = ['#2563eb', '#0ea5e9', '#22c55e', '#f97316', '#f43f5e', '#a855f7', '#0f172a', '#64748b', '#06b6d4', '#eab308'];

    function renderSimpleBarChart(areaId, subtitleId, values, subtitleText) {
      const chartArea = document.getElementById(areaId);
      const chartSubtitle = document.getElementById(subtitleId);
      if (!chartArea || !chartSubtitle) return;
      chartArea.innerHTML = '';
      chartSubtitle.textContent = subtitleText;
      
      if (values.length === 0) {
        chartArea.innerHTML = '<div class="empty-chart">No data available for this chart.</div>';
        return;
      }
      
      const maxValue = Math.max(...values.map(item => item.value), 1);
      const container = document.createElement('div');
      container.style.cssText = 'display:flex;align-items:flex-end;gap:16px;width:100%;overflow-x:auto;padding:10px 0;';
      
      values.forEach((item, index) => {
        const bar = document.createElement('div');
        bar.style.cssText = 'flex:1 1 80px;max-width:100px;display:flex;flex-direction:column;align-items:center;gap:8px;';
        
        // Value on top
        const valueEl = document.createElement('div');
        valueEl.style.cssText = 'font-size:14px;font-weight:700;color:#0f172a;';
        valueEl.textContent = item.value.toLocaleString();
        
        // Bar container
        const barContainer = document.createElement('div');
        barContainer.style.cssText = 'width:100%;height:160px;background:#f1f5f9;border-radius:8px 8px 0 0;display:flex;align-items:flex-end;justify-content:center;position:relative;overflow:hidden;';
        
        // Bar fill
        const fill = document.createElement('div');
        const fillPercent = Math.max(5, Math.round((item.value / maxValue) * 100));
        fill.style.cssText = `width:70%;height:${fillPercent}%;background:linear-gradient(180deg, ${chartColors[index % chartColors.length]}, ${chartColors[index % chartColors.length]}cc);border-radius:8px 8px 0 0;transition:height 0.5s ease;cursor:pointer;`;
        fill.setAttribute('title', item.label + ': ' + item.value.toLocaleString() + ' units');
        
        // Hover effect
        fill.addEventListener('mouseenter', () => {
          fill.style.transform = 'scaleY(1.02)';
          fill.style.filter = 'brightness(1.1)';
        });
        fill.addEventListener('mouseleave', () => {
          fill.style.transform = 'scaleY(1)';
          fill.style.filter = 'brightness(1)';
        });
        
        barContainer.appendChild(fill);
        
        // Label at bottom
        const labelEl = document.createElement('div');
        labelEl.style.cssText = 'font-size:11px;color:#64748b;text-align:center;max-width:90px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;';
        labelEl.textContent = item.label;
        labelEl.setAttribute('title', item.label);
        
        bar.appendChild(valueEl);
        bar.appendChild(barContainer);
        bar.appendChild(labelEl);
        container.appendChild(bar);
      });
      
      chartArea.appendChild(container);
    }

    function renderTopSellingChart() {
      renderSimpleBarChart('topSellingChartArea', 'topSellingChartSubtitle', chartData.topSellingChart, 'Product quantity sold by SKU.');
    }

    renderTopSellingChart();

    function downloadTopSellersCsv(event) {
      event.preventDefault();
      const rows = [
        ['Top Selling Products'],
        [],
        ['Rank', 'Product', 'Quantity Sold', 'Orders'],
      ];
      <?php foreach ($topSellers as $index => $product): ?>
        rows.push([<?= $index + 1 ?>, '<?= addslashes($product['product_name']) ?>', <?= intval($product['total_qty']) ?>, <?= intval($product['order_count']) ?>]);
      <?php endforeach; ?>
      const csvContent = rows.map((row) => row.map((cell) => `"${String(cell).replace(/"/g, '""')}"`).join(',')).join('\r\n');
      const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
      const url = URL.createObjectURL(blob);
      const link = document.createElement('a');
      link.href = url;
      link.download = 'owner_top_selling_products.csv';
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
</body>
</html>
