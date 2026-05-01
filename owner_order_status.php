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

$orderStatusSql = 'SELECT
  SUM(CASE WHEN status = "pending" THEN 1 ELSE 0 END) AS pending_orders,
  SUM(CASE WHEN status = "processing" THEN 1 ELSE 0 END) AS processing_orders,
  SUM(CASE WHEN status = "shipped" THEN 1 ELSE 0 END) AS shipped_orders,
  SUM(CASE WHEN status IN ("delivered", "received", "reviewed") THEN 1 ELSE 0 END) AS delivered_orders,
  SUM(CASE WHEN delivery_type = "pickup" THEN 1 ELSE 0 END) AS pickups,
  SUM(CASE WHEN delivery_type = "pickup" AND status IN ("delivered", "received", "reviewed") THEN 1 ELSE 0 END) AS picked_up_orders
  FROM orders
  WHERE archived = 0 AND binned = 0';
$orderStatus = ['pending_orders' => 0, 'processing_orders' => 0, 'shipped_orders' => 0, 'delivered_orders' => 0, 'pickups' => 0, 'picked_up_orders' => 0];
$statusResult = $conn->query($orderStatusSql);
if ($statusResult && $statusResult->num_rows > 0) {
    $orderStatus = array_merge($orderStatus, $statusResult->fetch_assoc());
}

$orderDetails = [];
$orderDetailsSql = 'SELECT order_id, user_id, status, delivery_type, total_amount, order_date
  FROM orders
  WHERE archived = 0 AND binned = 0
  ORDER BY order_date DESC
  LIMIT 10';
$orderDetailsResult = $conn->query($orderDetailsSql);
if ($orderDetailsResult) {
    while ($row = $orderDetailsResult->fetch_assoc()) {
        $orderDetails[] = [
            'order_id' => intval($row['order_id'] ?? 0),
            'status' => $row['status'] ?? 'unknown',
            'delivery_type' => $row['delivery_type'] ?? 'standard',
            'total_amount' => floatval($row['total_amount'] ?? 0),
            'order_date' => $row['order_date'] ?? ''
        ];
    }
}

$orderStatusChart = [
  ['label' => 'Pending', 'value' => intval($orderStatus['pending_orders'])],
  ['label' => 'Processing', 'value' => intval($orderStatus['processing_orders'])],
  ['label' => 'Shipped', 'value' => intval($orderStatus['shipped_orders'])],
  ['label' => 'Delivered', 'value' => intval($orderStatus['delivered_orders'])],
  ['label' => 'Pickup Orders', 'value' => intval($orderStatus['pickups'])],
  ['label' => 'Completed Pickups', 'value' => intval($orderStatus['picked_up_orders'])]
];

function format_peso_display($amount) {
    $value = (float)$amount;
    if (floor($value) == $value) {
        return number_format($value, 0, '.', ',');
    }
    return rtrim(rtrim(number_format($value, 2, '.', ','), '0'), '.');
}
?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Owner Order Status Overview</title>
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
    .stat-grid { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 14px; margin-bottom: 18px; }
    .stat-card { background: #fff; border: 1px solid #e5e7eb; border-radius: 16px; padding: 18px; box-shadow: 0 10px 24px rgba(15, 23, 42, 0.06); }
    .stat-card strong { display: block; margin-bottom: 6px; font-size: 24px; }
    .stat-card span { color: #475569; font-size: 13px; }
    .section-card { background: #fff; border: 1px solid #e5e7eb; border-radius: 18px; padding: 20px; box-shadow: 0 10px 24px rgba(15, 23, 42, 0.04); margin-bottom: 18px; }
    .section-card h2 { margin: 0 0 12px; font-size: 18px; color: #0f172a; }
    .section-actions { display: flex; justify-content: space-between; gap: 10px; align-items: center; margin-bottom: 16px; }
    .chart-pie { display: flex; flex-wrap: wrap; gap: 30px; align-items: center; justify-content: center; padding: 20px 0; }
    .pie-chart { width: 160px; height: 160px; min-width: 160px; border-radius: 50%; background: #f8fafc; display: grid; place-items: center; box-shadow: inset 0 0 0 1px #e5e7eb; position: relative; }
    .pie-chart-inner { width: 94%; height: 94%; border-radius: 50%; background: conic-gradient(#2563eb 0%, #93c5fd 100%); display: grid; place-items: center; }
    .pie-chart-center { width: 70px; height: 70px; min-width: 70px; border-radius: 50%; background: #fff; display: grid; place-items: center; text-align: center; box-shadow: 0 0 0 6px rgba(255,255,255,0.8); }
    .pie-chart-center strong { font-size: 16px; color: #111827; display: block; }
    .pie-chart-center span { font-size: 10px; color: #64748b; }
    .pie-legend { display: grid; gap: 10px; flex: 1 1 200px; max-width: 300px; }
    .pie-legend-item { display: flex; align-items: center; gap: 10px; font-size: 13px; color: #334155; }
    .pie-legend-color { width: 12px; height: 12px; min-width: 12px; border-radius: 4px; display: inline-block; flex-shrink: 0; }
    .empty-chart { width: 100%; min-height: 140px; display: grid; place-items: center; color: #64748b; font-size: 13px; background: #f8fafc; border: 1px solid #e5e7eb; border-radius: 16px; }
    .table-wrap { overflow-x: auto; }
    table { width: 100%; border-collapse: collapse; }
    th, td { padding: 12px 10px; border-bottom: 1px solid #e2e8f0; text-align: left; font-size: 13px; color: #334155; }
    th { background: #f8fafc; font-weight: 700; }
    .topbar { display: flex; justify-content: space-between; align-items: center; gap: 10px; flex-wrap: wrap; margin-bottom: 18px; }
    .topbar-menu { position: relative; }
    .menu-trigger { border: 1px solid #d1d5db; border-radius: 12px; background: #fff; color: #111827; padding: 10px 14px; cursor: pointer; }
    .menu-dropdown { position: absolute; top: calc(100% + 8px); right: 0; background: #fff; border: 1px solid #e2e8f0; border-radius: 14px; box-shadow: 0 16px 35px rgba(15, 23, 42, 0.12); display: none; min-width: 210px; z-index: 100; }
    .menu-dropdown.active { display: block; }
    .menu-dropdown a { display: block; padding: 10px 12px; color: #111827; text-decoration: none; border-bottom: 1px solid #f1f5f9; font-size: 13px; }
    .menu-dropdown a:last-child { border-bottom: none; }
    .menu-dropdown a:hover { background: #f8fafc; }
    @media(max-width: 980px) { .stat-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); } }
    @media(max-width: 720px) { .stat-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); } }
  </style>
</head>
<body>
  <div class="page-header">
    <a class="back-arrow" href="owner_administrative_page.php">←</a>
    <div class="header-content">
      <div class="header-title">Order Status Overview</div>
      <div class="header-meta">Detailed order status counts with recent order activity.</div>
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
      <h1>Order Status Overview</h1>
      <p>Track order distribution, pickup statistics, and recent fulfillment activity from a single owner page.</p>
      <div class="actions">
        <a class="btn primary" href="owner_order_status.php" onclick="event.preventDefault(); downloadOrderStatusCsv(event)">Export Status CSV</a>
        <a class="btn" href="owner_administrative_page.php">Back to Overview</a>
      </div>
    </div>

    <div class="stat-grid">
      <div class="stat-card"><strong><?= intval($orderStatus['pending_orders']) ?></strong><span>Pending Orders</span></div>
      <div class="stat-card"><strong><?= intval($orderStatus['processing_orders']) ?></strong><span>Processing Orders</span></div>
      <div class="stat-card"><strong><?= intval($orderStatus['shipped_orders']) ?></strong><span>Shipped Orders</span></div>
      <div class="stat-card"><strong><?= intval($orderStatus['delivered_orders']) ?></strong><span>Delivered Orders</span></div>
      <div class="stat-card"><strong><?= intval($orderStatus['pickups']) ?></strong><span>Pickup Orders</span></div>
      <div class="stat-card"><strong><?= intval($orderStatus['picked_up_orders']) ?></strong><span>Completed Pickups</span></div>
    </div>

    <section class="section-card">
      <div class="section-actions"><div><h2>Order Status Distribution</h2><div style="color:#64748b;font-size:13px;">Visual order state breakdown to surface fulfillment hotspots.</div></div></div>
      <div id="orderStatusChartArea" class="chart-pie"></div>
      <div class="chart-label" id="orderStatusChartSubtitle"></div>
    </section>

    <section class="section-card">
      <div class="section-actions">
        <div><h2>Recent Orders</h2></div>
      </div>
      <div class="table-wrap">
        <table>
          <thead>
            <tr><th>Order ID</th><th>Status</th><th>Delivery</th><th>Total</th><th>Date</th></tr>
          </thead>
          <tbody>
            <?php foreach ($orderDetails as $order): ?>
              <tr>
                <td>#<?= $order['order_id'] ?></td>
                <td><?= htmlspecialchars($order['status']) ?></td>
                <td><?= htmlspecialchars($order['delivery_type']) ?></td>
                <td>₱<?= format_peso_display($order['total_amount']) ?></td>
                <td><?= htmlspecialchars($order['order_date']) ?></td>
              </tr>
            <?php endforeach; ?>
            <?php if (empty($orderDetails)): ?>
              <tr><td colspan="5">No recent orders found.</td></tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </section>
  </div>

  <script>
    const chartData = <?php echo json_encode(['orderStatusChart' => $orderStatusChart], JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT); ?>;
    const chartColors = ['#2563eb', '#0ea5e9', '#22c55e', '#f97316', '#f43f5e', '#a855f7'];

    function renderPieChart(areaId, subtitleId, values, subtitleText) {
      const chartArea = document.getElementById(areaId);
      const chartSubtitle = document.getElementById(subtitleId);
      if (!chartArea || !chartSubtitle) return;
      chartArea.innerHTML = '';
      chartSubtitle.textContent = subtitleText;
      const total = values.reduce((sum, item) => sum + item.value, 0);
      if (total <= 0) {
        chartArea.innerHTML = '<div class="empty-chart" style="width:100%;min-height:140px;display:flex;align-items:center;justify-content:center;color:#64748b;font-size:13px;background:#f8fafc;border:1px solid #e5e7eb;border-radius:12px;">No data available for this chart.</div>';
        return;
      }
      let offset = 0;
      const slices = values.map((item, index) => {
        const percentage = (item.value / total) * 100;
        const color = chartColors[index % chartColors.length];
        const slice = `${color} ${offset}% ${offset + percentage}%`;
        offset += percentage;
        return slice;
      });
      
      // Container
      const container = document.createElement('div');
      container.style.cssText = 'display:flex;flex-wrap:wrap;gap:30px;align-items:center;justify-content:center;width:100%;padding:20px 0;';
      
      // Pie chart
      const pie = document.createElement('div');
      pie.style.cssText = 'width:160px;height:160px;min-width:160px;border-radius:50%;background:#f8fafc;display:grid;place-items:center;box-shadow:inset 0 0 0 1px #e5e7eb;position:relative;';
      const inner = document.createElement('div');
      inner.style.cssText = 'width:94%;height:94%;border-radius:50%;background:conic-gradient(' + slices.join(', ') + ');display:grid;place-items:center;';
      const center = document.createElement('div');
      center.style.cssText = 'width:70px;height:70px;min-width:70px;border-radius:50%;background:#fff;display:grid;place-items:center;text-align:center;box-shadow:0 0 0 6px rgba(255,255,255,0.8);';
      center.innerHTML = `<strong style="font-size:16px;color:#111827;display:block;">${total}</strong><span style="font-size:10px;color:#64748b;">Total</span>`;
      inner.appendChild(center);
      pie.appendChild(inner);
      container.appendChild(pie);
      
      // Legend
      const legend = document.createElement('div');
      legend.style.cssText = 'display:grid;gap:10px;flex:1 1 200px;max-width:300px;';
      values.forEach((item, index) => {
        const entry = document.createElement('div');
        entry.style.cssText = 'display:flex;align-items:center;gap:10px;font-size:13px;color:#334155;';
        const dot = document.createElement('span');
        dot.style.cssText = 'width:12px;height:12px;min-width:12px;border-radius:4px;background:' + chartColors[index % chartColors.length] + ';';
        const labelText = document.createElement('span');
        labelText.textContent = item.label + ': ' + item.value;
        entry.appendChild(dot);
        entry.appendChild(labelText);
        legend.appendChild(entry);
      });
      container.appendChild(legend);
      
      chartArea.appendChild(container);
    }

    function renderOrderStatusChart() {
      renderPieChart('orderStatusChartArea', 'orderStatusChartSubtitle', chartData.orderStatusChart, 'Order status distribution.');
    }

    renderOrderStatusChart();

    function downloadOrderStatusCsv(event) {
      event.preventDefault();
      const rows = [
        ['Order Status Overview'],
        [],
        ['Label', 'Value'],
        ['Pending', <?= intval($orderStatus['pending_orders']) ?>],
        ['Processing', <?= intval($orderStatus['processing_orders']) ?>],
        ['Shipped', <?= intval($orderStatus['shipped_orders']) ?>],
        ['Delivered', <?= intval($orderStatus['delivered_orders']) ?>],
        ['Pickup Orders', <?= intval($orderStatus['pickups']) ?>],
        ['Completed Pickups', <?= intval($orderStatus['picked_up_orders']) ?>],
        [],
        ['Order ID', 'Status', 'Delivery', 'Total', 'Date']
      ];
      <?php foreach ($orderDetails as $order): ?>
        rows.push(['#<?= $order['order_id'] ?>', '<?= addslashes($order['status']) ?>', '<?= addslashes($order['delivery_type']) ?>', '₱<?= format_peso_display($order['total_amount']) ?>', '<?= addslashes($order['order_date']) ?>']);
      <?php endforeach; ?>
      const csvContent = rows.map((row) => row.map((cell) => `"${String(cell).replace(/"/g, '""')}"`).join(',')).join('\r\n');
      const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
      const url = URL.createObjectURL(blob);
      const link = document.createElement('a');
      link.href = url;
      link.download = 'owner_order_status.csv';
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
