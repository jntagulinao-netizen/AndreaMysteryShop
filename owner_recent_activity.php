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

$selectedMonth = $_GET['month'] ?? '';
$selectedYear = $_GET['year'] ?? '';

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

$whereClause = '';
if (!empty($selectedMonth) && !empty($selectedYear)) {
    $whereClause = " AND YEAR(o.order_date) = $selectedYear AND MONTH(o.order_date) = $selectedMonth";
}

$signupWhereClause = '';
if (!empty($selectedMonth) && !empty($selectedYear)) {
    $signupWhereClause = " AND YEAR(created_at) = $selectedYear AND MONTH(created_at) = $selectedMonth";
}

$activityFeed = [];
$recentOrdersSql = 'SELECT o.order_id, o.order_date, o.status, u.full_name AS customer_name,
  GROUP_CONCAT(DISTINCT COALESCE(p.product_name, "Item") SEPARATOR ", ") AS products
  FROM orders o
  LEFT JOIN users u ON o.user_id = u.user_id
  LEFT JOIN order_items oi ON o.order_id = oi.order_id
  LEFT JOIN products p ON oi.product_id = p.product_id
  WHERE o.archived = 0 AND o.binned = 0' . $whereClause . '
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
$recentCustomersSql = 'SELECT full_name, email, created_at FROM users WHERE LOWER(role) = "user"' . $signupWhereClause . ' ORDER BY created_at DESC LIMIT 6';
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
    body { margin: 0; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f3f4f6; padding-bottom: 78px; }
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
    .activity-item strong { display: block; margin-bottom: 10px; font-size: 15px; color: #0f172a; }
    .activity-item span { display: block; font-size: 13px; color: #475569; margin-bottom: 10px; }
    .activity-item .note { font-size: 13px; color: #334155; margin-bottom: 10px; }
    .activity-item .time { margin-top: 10px; font-size: 12px; color: #64748b; }
    .tabs {
      display: flex;
      gap: 8px;
      margin: 16px 0 18px;
      flex-wrap: wrap;
    }
    .tab-button {
      border: 1px solid #d1d5db;
      border-radius: 12px;
      background: #fff;
      color: #111827;
      padding: 10px 14px;
      font-weight: 700;
      cursor: pointer;
      transition: background .18s ease, border-color .18s ease, color .18s ease;
    }
    .tab-button.active {
      background: #0f172a;
      border-color: #0f172a;
      color: #fff;
    }
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
    .swal-overlay {
      position: fixed;
      inset: 0;
      background: rgba(15, 23, 42, 0.45);
      display: none;
      align-items: center;
      justify-content: center;
      z-index: 2000;
      padding: 20px;
    }
    .swal-overlay.show { display: flex; }
    .swal-card {
      width: 100%;
      max-width: 360px;
      background: #fff;
      border-radius: 14px;
      border: 1px solid #dde5ee;
      box-shadow: 0 18px 40px rgba(15, 23, 42, 0.25);
      text-align: center;
      padding: 20px 18px 16px;
      animation: swalIn .16s ease-out;
    }
    @keyframes swalIn {
      from { opacity: 0; transform: translateY(8px) scale(0.98); }
      to { opacity: 1; transform: translateY(0) scale(1); }
    }
    .swal-icon {
      width: 52px;
      height: 52px;
      border-radius: 50%;
      margin: 0 auto 10px;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 28px;
      font-weight: 700;
    }
    .swal-icon.success { background: #e9f9ef; color: #0c8f3f; }
    .swal-icon.error { background: #ffecee; color: #c62839; }
    .swal-icon.warning { background: #fff6e5; color: #bb6a00; }
    .swal-title { font-size: 20px; font-weight: 700; color: #152033; margin-bottom: 8px; }
    .swal-text { font-size: 14px; color: #5f6d7f; margin-bottom: 14px; line-height: 1.45; }
    .swal-actions { display: grid; grid-template-columns: 1fr; gap: 8px; }
    .swal-actions.two { grid-template-columns: 1fr 1fr; }
    .swal-btn {
      border: none;
      border-radius: 10px;
      font-size: 14px;
      font-weight: 700;
      width: 100%;
      height: 42px;
      cursor: pointer;
    }
    .swal-btn.primary { background: #2d68d8; color: #fff; }
    .swal-btn.primary:hover { background: #1f56bf; }
    .swal-btn.secondary { background: #f2f5fb; color: #44546a; border: 1px solid #d5deea; }
    .swal-btn.secondary:hover { background: #e9eef7; }
  </style>
</head>
<body>
  <div class="page-header">
    <a class="back-arrow" href="owner_administrative_page.php">‹</a>
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
  <div id="localSwal" class="swal-overlay" role="dialog" aria-modal="true" aria-live="polite">
    <div class="swal-card">
      <div id="localSwalIcon" class="swal-icon success">✓</div>
      <div id="localSwalTitle" class="swal-title">Success</div>
      <div id="localSwalText" class="swal-text"></div>
      <div id="localSwalActions" class="swal-actions">
        <button id="localSwalCancel" type="button" class="swal-btn secondary" style="display:none;">Cancel</button>
        <button id="localSwalConfirm" type="button" class="swal-btn primary">OK</button>
      </div>
    </div>
  </div>
  <div class="wrap">
    <div class="hero">
      <h1>Recent Activity Feed</h1>
      <p>Monitor the latest order and customer events with exportable activity details.</p>
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
          <button type="button" class="btn primary" onclick="downloadActivityCsv()">Export Activity CSV</button>
          <a class="btn" href="owner_administrative_page.php">Back to Overview</a>
        </div>
      </form>
    </div>

    <section class="section-card">
      <div class="section-actions"><div><h2>Activity Summary</h2><div style="color:#64748b;font-size:13px;">Latest order and signup volume in the activity stream.</div></div></div>
      <div id="activityChartArea" class="chart-pie"></div>
      <div class="chart-label" id="activityChartSubtitle"></div>
    </section>

    <section class="section-card">
      <div class="section-actions">
        <div>
          <h2>Latest Events</h2>
        </div>
        <div style="display: flex; gap: 10px; align-items: center; flex-wrap: wrap;">
          <div class="tabs" role="tablist" aria-label="Latest events tabs">
            <button type="button" class="tab-button active" data-tab="all">All</button>
            <button type="button" class="tab-button" data-tab="order">Orders</button>
            <button type="button" class="tab-button" data-tab="customer">Users</button>
          </div>
          <select id="sortFilter" onchange="handleActivitySortChange(this.value)" style="padding: 8px 12px; border: 1px solid #d1d5db; border-radius: 8px; background: #fff; color: #111827; font-size: 13px; cursor: pointer;">
            <option value="newest">Newest to Oldest</option>
            <option value="oldest">Oldest to Newest</option>
          </select>
        </div>
      </div>
      <div class="activity-list" id="activityList">
        <?php if (empty($activityFeed)): ?>
          <div class="activity-item">No recent activity available.</div>
        <?php endif; ?>
      </div>
      <div id="activityPagination" style="display: flex; justify-content: center; align-items: center; gap: 8px; margin-top: 16px; flex-wrap: wrap;">
      </div>
    </section>
  </div>

  <script>
    const chartData = <?php echo json_encode(['activityChart' => $activityChart, 'activityFeed' => $activityFeed], JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT); ?>;
    const chartColors = ['#2563eb', '#22c55e'];
    
    let currentActivityPage = 1;
    const itemsPerPage = 6;
    let currentActivityTab = 'all';
    let currentActivitySort = 'newest';

    function openLocalSweetAlert(options = {}) {
      const overlay = document.getElementById('localSwal');
      const iconEl = document.getElementById('localSwalIcon');
      const titleEl = document.getElementById('localSwalTitle');
      const textEl = document.getElementById('localSwalText');
      const actions = document.getElementById('localSwalActions');
      const confirmBtn = document.getElementById('localSwalConfirm');
      const cancelBtn = document.getElementById('localSwalCancel');
      if (!overlay || !iconEl || !titleEl || !textEl || !actions || !confirmBtn || !cancelBtn) return Promise.resolve(true);

      const type = options.type || 'success';
      const isError = type === 'error';
      const isWarning = type === 'warning';
      const hasCancel = !!options.showCancel;
      iconEl.className = `swal-icon ${isError ? 'error' : isWarning ? 'warning' : 'success'}`;
      iconEl.textContent = isError ? '!' : isWarning ? '⚠' : '✓';
      titleEl.textContent = options.title || 'Notice';
      if (options.html) {
        textEl.innerHTML = options.html;
      } else {
        textEl.textContent = options.text || '';
      }

      confirmBtn.textContent = options.confirmText || 'OK';
      cancelBtn.textContent = options.cancelText || 'Cancel';
      cancelBtn.style.display = hasCancel ? 'block' : 'none';
      actions.className = hasCancel ? 'swal-actions two' : 'swal-actions';

      return new Promise((resolve) => {
        const cleanup = () => {
          overlay.classList.remove('show');
          confirmBtn.onclick = null;
          cancelBtn.onclick = null;
          overlay.onclick = null;
        };

        confirmBtn.onclick = () => {
          cleanup();
          if (typeof options.onConfirm === 'function') options.onConfirm();
          resolve(true);
        };
        cancelBtn.onclick = () => {
          cleanup();
          if (typeof options.onCancel === 'function') options.onCancel();
          resolve(false);
        };
        overlay.onclick = (event) => {
          if (event.target === overlay && hasCancel) {
            cleanup();
            resolve(false);
          }
        };

        overlay.classList.add('show');
      });
    }

    function showLocalSweetAlert(type, title, text) {
      return openLocalSweetAlert({ type, title, text, confirmText: 'OK' });
    }

    function showLocalConfirm(title, text, confirmText = 'OK', cancelText = 'Cancel') {
      return openLocalSweetAlert({ type: 'success', title, text, confirmText, cancelText, showCancel: true });
    }

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
      openLocalSweetAlert({
        title: event.title,
        type: icon === 'success' ? 'success' : icon === 'warning' ? 'warning' : 'success',
        html: `
          <p style="margin:0 0 10px;font-weight:700;">${event.subtitle}</p>
          <p style="margin:0 0 8px;">${event.note}</p>
          <p style="margin:0;color:#6b7280;font-size:13px;">${event.timestamp_label}</p>
        `,
        confirmText: 'Close'
      });
    }

    function attachActivityCardHandlers() {
      document.querySelectorAll('.activity-item[data-event-index]').forEach(item => {
        item.addEventListener('click', () => showActivityDetails(item.dataset.eventIndex));
        item.addEventListener('keypress', (event) => {
          if (event.key === 'Enter' || event.key === ' ') {
            event.preventDefault();
            showActivityDetails(item.dataset.eventIndex);
          }
        });
      });
    }

    function getFilteredActivities() {
      let filtered = chartData.activityFeed;
      
      // Apply tab filter
      if (currentActivityTab !== 'all') {
        filtered = filtered.filter(event => event.type === currentActivityTab);
      }
      
      // Apply sort
      const sorted = [...filtered];
      if (currentActivitySort === 'oldest') {
        sorted.reverse();
      }
      
      return sorted;
    }

    function renderPaginatedActivities() {
      const filtered = getFilteredActivities();
      const totalItems = filtered.length;
      const totalPages = Math.ceil(totalItems / itemsPerPage);
      
      if (totalItems === 0) {
        document.getElementById('activityList').innerHTML = '<div class="activity-item">No recent activity available for this category.</div>';
        document.getElementById('activityPagination').innerHTML = '';
        return;
      }
      
      const startIdx = (currentActivityPage - 1) * itemsPerPage;
      const endIdx = Math.min(startIdx + itemsPerPage, totalItems);
      const pageItems = filtered.slice(startIdx, endIdx);
      
      let html = '';
      pageItems.forEach(event => {
        const eventIdx = chartData.activityFeed.findIndex(e => e.timestamp === event.timestamp && e.type === event.type);
        html += `<div class="activity-item" role="button" tabindex="0" data-event-index="${eventIdx}" data-event-type="${event.type}">
          <strong>${event.title}</strong>
          <span>${event.subtitle}</span>
          <div class="note">${event.note}</div>
          <div class="time">${event.timestamp_label}</div>
        </div>`;
      });
      document.getElementById('activityList').innerHTML = html;
      attachActivityCardHandlers();
      
      // Render pagination controls
      if (totalPages > 1) {
        let paginationHtml = `<button onclick="goToActivityPage(${Math.max(1, currentActivityPage - 1)})" ${currentActivityPage === 1 ? 'disabled' : ''} style="padding: 8px 12px; border: 1px solid #d1d5db; border-radius: 8px; background: ${currentActivityPage === 1 ? '#f3f4f6' : '#fff'}; color: #111827; cursor: ${currentActivityPage === 1 ? 'not-allowed' : 'pointer'}; font-size: 13px;">← Prev</button>`;
        
        for (let p = 1; p <= totalPages; p++) {
          const isActive = p === currentActivityPage;
          paginationHtml += `<button onclick="goToActivityPage(${p})" style="padding: 8px 12px; border: 1px solid ${isActive ? '#0f172a' : '#d1d5db'}; border-radius: 8px; background: ${isActive ? '#0f172a' : '#fff'}; color: ${isActive ? '#fff' : '#111827'}; cursor: pointer; font-size: 13px; font-weight: ${isActive ? '700' : '400'};\">${p}</button>`;
        }
        
        paginationHtml += `<button onclick="goToActivityPage(${Math.min(totalPages, currentActivityPage + 1)})" ${currentActivityPage === totalPages ? 'disabled' : ''} style="padding: 8px 12px; border: 1px solid #d1d5db; border-radius: 8px; background: ${currentActivityPage === totalPages ? '#f3f4f6' : '#fff'}; color: #111827; cursor: ${currentActivityPage === totalPages ? 'not-allowed' : 'pointer'}; font-size: 13px;">Next →</button>`;
        
        document.getElementById('activityPagination').innerHTML = paginationHtml;
      } else {
        document.getElementById('activityPagination').innerHTML = '';
      }
    }

    function goToActivityPage(page) {
      const filtered = getFilteredActivities();
      const totalPages = Math.ceil(filtered.length / itemsPerPage);
      if (page >= 1 && page <= totalPages) {
        currentActivityPage = page;
        renderPaginatedActivities();
      }
    }

    function applyActivityTabFilter(filter) {
      currentActivityTab = filter;
      currentActivityPage = 1;
      renderPaginatedActivities();
    }

    function handleActivitySortChange(value) {
      currentActivitySort = value;
      currentActivityPage = 1;
      renderPaginatedActivities();
    }

    function attachTabHandlers() {
      document.querySelectorAll('.tab-button').forEach(button => {
        button.addEventListener('click', () => {
          document.querySelectorAll('.tab-button').forEach(btn => btn.classList.remove('active'));
          button.classList.add('active');
          applyActivityTabFilter(button.dataset.tab);
        });
      });
    }

    renderActivityChart();
    renderPaginatedActivities();
    attachTabHandlers();

    function downloadActivityCsv() {
      const month = document.querySelector('select[name="month"]').value;
      const year = document.querySelector('select[name="year"]').value;
      const rows = [
        ['Recent Activity Feed'],
        [],
        ['Title', 'Subtitle', 'Note', 'Timestamp'],
      ];
      chartData.activityFeed.forEach(event => {
        rows.push([event.title, event.subtitle, event.note, event.timestamp_label]);
      });
      if (rows.length <= 4) {
        alert('No data available for the selected period.');
        return;
      }
      const csvContent = rows.map((row) => row.map((cell) => `"${String(cell).replace(/"/g, '""')}"`).join(',')).join('\r\n');
      const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
      const url = URL.createObjectURL(blob);
      const link = document.createElement('a');
      let filename = 'owner_recent_activity.csv';
      if (month && year) {
        filename = `owner_recent_activity_${year}_${month}.csv`;
      }
      link.href = url;
      link.download = filename;
      document.body.appendChild(link);
      link.click();
      document.body.removeChild(link);
      URL.revokeObjectURL(url);
      showLocalSweetAlert('success', 'Export started', 'Your activity CSV download should begin shortly.');
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
      <a href="owner_top_selling_products.php">
        <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M3 7l9-4 9 4-9 4-9-4z"></path><path d="M3 17l9 4 9-4"></path><path d="M3 12l9 4 9-4"></path></svg>
        <span>Top Products</span>
      </a>
      <a href="owner_recent_activity.php" class="active">
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
