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

$activityFeed = [];
$recentOrdersSql = 'SELECT o.order_id, o.order_date, o.status, u.full_name AS customer_name,
  GROUP_CONCAT(DISTINCT COALESCE(p.product_name, "Item") SEPARATOR ", ") AS products
  FROM orders o
  LEFT JOIN users u ON o.user_id = u.user_id
  LEFT JOIN order_items oi ON o.order_id = oi.order_id
  LEFT JOIN products p ON oi.product_id = p.product_id
  WHERE o.archived = 0 AND o.binned = 0
  GROUP BY o.order_id
  ORDER BY o.order_date DESC
  LIMIT 6';
$orderFeedResult = $conn->query($recentOrdersSql);
if ($orderFeedResult) {
    while ($row = $orderFeedResult->fetch_assoc()) {
        $activityFeed[] = [
            'type' => 'order',
            'timestamp' => $row['order_date'],
            'title' => 'Order #' . intval($row['order_id']) . ' placed',
            'subtitle' => trim($row['customer_name'] ?: 'Customer'),
            'note' => 'Status: ' . ($row['status'] ?? 'unknown') . ' · Items: ' . trim($row['products'] ?: 'No details'),
            'timestamp_label' => date('M j, H:i', strtotime($row['order_date']))
        ];
    }
}
$recentCustomersSql = 'SELECT full_name, email, created_at FROM users WHERE LOWER(role) = "user" ORDER BY created_at DESC LIMIT 6';
$customerFeedResult = $conn->query($recentCustomersSql);
if ($customerFeedResult) {
    while ($row = $customerFeedResult->fetch_assoc()) {
        $activityFeed[] = [
            'type' => 'customer',
            'timestamp' => $row['created_at'],
            'title' => trim($row['full_name'] ?: 'New customer'),
            'subtitle' => 'New signup',
            'note' => 'Email: ' . ($row['email'] ?? 'N/A'),
            'timestamp_label' => date('M j, H:i', strtotime($row['created_at']))
        ];
    }
}

usort($activityFeed, function ($a, $b) {
    return strcmp($b['timestamp'], $a['timestamp']);
});
$activityFeed = array_slice($activityFeed, 0, 12);
$activityTotals = ['orders' => 0, 'customers' => 0];
foreach ($activityFeed as $event) {
    if ($event['type'] === 'order') {
        $activityTotals['orders']++;
    } elseif ($event['type'] === 'customer') {
        $activityTotals['customers']++;
    }
}
$activityChart = [
    ['label' => 'Orders', 'value' => $activityTotals['orders']],
    ['label' => 'New Customers', 'value' => $activityTotals['customers']]
];
?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Owner Recent Activity Feed</title>
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
    .activity-list { display: grid; gap: 12px; }
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
    .activity-item { background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 16px; padding: 16px; }
    .activity-item strong { display: block; margin-bottom: 8px; font-size: 15px; color: #0f172a; }
    .activity-item span { display: block; font-size: 13px; color: #475569; margin-bottom: 6px; }
    .activity-item .note { font-size: 13px; color: #334155; }
    .activity-item .time { margin-top: 10px; font-size: 12px; color: #64748b; }
    .topbar-menu { position: relative; }
    .menu-trigger { border: 1px solid #d1d5db; border-radius: 12px; background: #fff; color: #111827; padding: 10px 14px; cursor: pointer; }
    .menu-dropdown { position: absolute; top: calc(100% + 8px); right: 0; background: #fff; border: 1px solid #e2e8f0; border-radius: 14px; box-shadow: 0 16px 35px rgba(15, 23, 42, 0.12); display: none; min-width: 210px; z-index: 100; }
    .menu-dropdown.active { display: block; }
    .menu-dropdown a { display: block; padding: 10px 12px; color: #111827; text-decoration: none; border-bottom: 1px solid #f1f5f9; font-size: 13px; }
    .menu-dropdown a:last-child { border-bottom: none; }
    .menu-dropdown a:hover { background: #f8fafc; }
    .activity-item { cursor: pointer; transition: transform .12s ease, box-shadow .12s ease; }
    .activity-item:hover { transform: translateY(-2px); box-shadow: 0 18px 32px rgba(15, 23, 42, 0.12); }
    .activity-item:focus { outline: 2px solid #2563eb; outline-offset: 3px; }
  </style>
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body>
  <div class="page-header">
    <a class="back-arrow" href="owner_administrative_page.php">←</a>
    <div class="header-content">
      <div class="header-title">Recent Activity Feed</div>
      <div class="header-meta">Browse recent orders and user signups sorted by time.</div>
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
      <h1>Recent Activity Feed</h1>
      <p>Monitor the latest order and customer events with exportable activity details.</p>
      <div class="actions">
        <a class="btn primary" href="owner_recent_activity.php" onclick="event.preventDefault(); downloadActivityCsv(event)">Export Activity CSV</a>
        <a class="btn" href="owner_administrative_page.php">Back to Overview</a>
      </div>
    </div>

    <section class="section-card">
      <div class="section-actions"><div><h2>Activity Summary</h2><div style="color:#64748b;font-size:13px;">Latest order and signup volume in the activity stream.</div></div></div>
      <div id="activityChartArea" class="chart-pie"></div>
      <div class="chart-label" id="activityChartSubtitle"></div>
    </section>

    <section class="section-card">
      <div class="section-actions"><div><h2>Latest Events</h2></div></div>
      <div class="activity-list">
        <?php foreach ($activityFeed as $index => $event): ?>
          <div class="activity-item" role="button" tabindex="0" data-event-index="<?= $index ?>">
            <strong><?= htmlspecialchars($event['title']) ?></strong>
            <span><?= htmlspecialchars($event['subtitle']) ?></span>
            <div class="note"><?= htmlspecialchars($event['note']) ?></div>
            <div class="time"><?= htmlspecialchars($event['timestamp_label']) ?></div>
          </div>
        <?php endforeach; ?>
        <?php if (empty($activityFeed)): ?>
          <div class="activity-item">No recent activity available.</div>
        <?php endif; ?>
      </div>
    </section>
  </div>

  <script>
    const chartData = <?php echo json_encode(['activityChart' => $activityChart, 'activityFeed' => $activityFeed], JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT); ?>;
    const chartColors = ['#2563eb', '#22c55e'];

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

    function renderActivityChart() {
      renderPieChart('activityChartArea', 'activityChartSubtitle', chartData.activityChart, 'Order vs new customer event share.');
    }

    function showActivityDetails(index) {
      const event = chartData.activityFeed[parseInt(index, 10)];
      if (!event) return;
      const icon = event.type === 'order' ? 'info' : 'success';
      Swal.fire({
        title: event.title,
        icon,
        html: `
          <p style="margin:0 0 10px;font-weight:700;">${event.subtitle}</p>
          <p style="margin:0 0 8px;">${event.note}</p>
          <p style="margin:0;color:#6b7280;font-size:13px;">${event.timestamp_label}</p>
        `,
        showCloseButton: true,
        confirmButtonText: 'Close',
        width: 560
      });
    }

    function attachActivityCardHandlers() {
      document.querySelectorAll('.activity-item').forEach(item => {
        item.addEventListener('click', () => showActivityDetails(item.dataset.eventIndex));
        item.addEventListener('keypress', (event) => {
          if (event.key === 'Enter' || event.key === ' ') {
            event.preventDefault();
            showActivityDetails(item.dataset.eventIndex);
          }
        });
      });
    }

    renderActivityChart();
    attachActivityCardHandlers();

    function downloadActivityCsv(event) {
      event.preventDefault();
      const rows = [
        ['Recent Activity Feed'],
        [],
        ['Title', 'Subtitle', 'Note', 'Timestamp'],
      ];
      <?php foreach ($activityFeed as $event): ?>
        rows.push(['<?= addslashes($event['title']) ?>', '<?= addslashes($event['subtitle']) ?>', '<?= addslashes($event['note']) ?>', '<?= addslashes($event['timestamp_label']) ?>']);
      <?php endforeach; ?>
      const csvContent = rows.map((row) => row.map((cell) => `"${String(cell).replace(/"/g, '""')}"`).join(',')).join('\r\n');
      const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
      const url = URL.createObjectURL(blob);
      const link = document.createElement('a');
      link.href = url;
      link.download = 'owner_recent_activity.csv';
      document.body.appendChild(link);
      link.click();
      document.body.removeChild(link);
      URL.revokeObjectURL(url);
      Swal.fire({
        title: 'Export started',
        text: 'Your activity CSV download should begin shortly.',
        icon: 'success',
        timer: 1800,
        showConfirmButton: false
      });
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
