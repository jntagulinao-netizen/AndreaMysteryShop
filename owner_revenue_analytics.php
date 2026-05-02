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

function format_peso_display($amount) {
    $value = (float)$amount;
    if (floor($value) == $value) {
        return number_format($value, 0, '.', ',');
    }
    return rtrim(rtrim(number_format($value, 2, '.', ','), '0'), '.');
}

$whereClause = '';
if (!empty($selectedMonth) && !empty($selectedYear)) {
    $whereClause = " AND YEAR(order_date) = $selectedYear AND MONTH(order_date) = $selectedMonth";
}

$quickStatsSql = 'SELECT
  COUNT(*) AS total_orders,
  SUM(total_amount) AS total_revenue
  FROM orders
  WHERE archived = 0 AND binned = 0 AND status IN ("delivered", "received", "reviewed")' . $whereClause;
$quickStats = ['total_orders' => 0, 'total_revenue' => 0.0];
$quickResult = $conn->query($quickStatsSql);
if ($quickResult && $quickResult->num_rows > 0) {
    $quickStats = array_merge($quickStats, $quickResult->fetch_assoc());
}

function buildReportSeries($conn, $unit, $whereClause = '') {
    $series = [];
    $now = new DateTime();
    if ($unit === 'weeks') {
        $weekStart = new DateTime('monday this week');
        $labels = [];
        for ($i = 3; $i >= 0; $i--) {
            $week = clone $weekStart;
            $week->modify("-{$i} weeks");
            $labels[] = $week;
        }
        $sql = 'SELECT YEARWEEK(order_date, 1) AS week_id, SUM(total_amount) AS total_value
          FROM orders
          WHERE archived = 0 AND binned = 0 AND status IN ("delivered", "received", "reviewed") AND order_date >= DATE_SUB(CURDATE(), INTERVAL 28 DAY)' . $whereClause . '
          GROUP BY YEARWEEK(order_date, 1)
          ORDER BY week_id ASC';
        $result = $conn->query($sql);
        $map = [];
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $map[intval($row['week_id'])] = floatval($row['total_value']);
            }
        }
        foreach ($labels as $week) {
            $weekId = intval($week->format('oW'));
            $series[] = ['label' => $week->format('M j'), 'value' => intval($map[$weekId] ?? 0)];
        }
        return $series;
    }
    if ($unit === 'months') {
        $labels = [];
        for ($i = 5; $i >= 0; $i--) {
            $month = (clone $now)->modify("-{$i} months");
            $labels[] = $month;
        }
        $sql = 'SELECT YEAR(order_date) AS yr, MONTH(order_date) AS mon, SUM(total_amount) AS total_value
          FROM orders
          WHERE archived = 0 AND binned = 0 AND status IN ("delivered", "received", "reviewed") AND order_date >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)' . $whereClause . '
          GROUP BY yr, mon
          ORDER BY yr, mon ASC';
        $result = $conn->query($sql);
        $map = [];
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $key = intval($row['yr']) . '-' . str_pad(intval($row['mon']), 2, '0', STR_PAD_LEFT);
                $map[$key] = floatval($row['total_value']);
            }
        }
        foreach ($labels as $month) {
            $key = $month->format('Y-m');
            $series[] = ['label' => $month->format('M Y'), 'value' => intval($map[$key] ?? 0)];
        }
        return $series;
    }
    if ($unit === 'years') {
        $labels = [];
        $year = intval($now->format('Y'));
        for ($i = 4; $i >= 0; $i--) {
            $labels[] = $year - $i;
        }
        $sql = 'SELECT YEAR(order_date) AS yr, SUM(total_amount) AS total_value
          FROM orders
          WHERE archived = 0 AND binned = 0 AND status IN ("delivered", "received", "reviewed") AND order_date >= DATE_SUB(CURDATE(), INTERVAL 5 YEAR)' . $whereClause . '
          GROUP BY yr
          ORDER BY yr ASC';
        $result = $conn->query($sql);
        $map = [];
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $map[intval($row['yr'])] = floatval($row['total_value']);
            }
        }
        foreach ($labels as $yearLabel) {
            $series[] = ['label' => (string)$yearLabel, 'value' => intval($map[$yearLabel] ?? 0)];
        }
        return $series;
    }
    return [];
}

$revenueData = [
    'total_orders' => intval($quickStats['total_orders'] ?? 0),
    'total_revenue' => floatval($quickStats['total_revenue'] ?? 0),
    'series' => [
        'weeks' => buildReportSeries($conn, 'weeks', $whereClause),
        'months' => buildReportSeries($conn, 'months', $whereClause),
        'years' => buildReportSeries($conn, 'years', $whereClause)
    ]
];
?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Owner Revenue Analytics</title>
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
    .stats-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 14px; margin-bottom: 18px; }
    .stat-card { background: #fff; border: 1px solid #e5e7eb; border-radius: 16px; padding: 18px; box-shadow: 0 10px 24px rgba(15, 23, 42, 0.06); }
    .stat-card strong { display: block; margin-bottom: 6px; font-size: 24px; color: #111827; }
    .stat-card span { color: #475569; font-size: 13px; }
    .section-card { background: #fff; border: 1px solid #e5e7eb; border-radius: 18px; padding: 20px; box-shadow: 0 10px 24px rgba(15, 23, 42, 0.04); margin-bottom: 18px; }
    .section-card h2 { margin: 0 0 12px; font-size: 18px; color: #0f172a; }
    .section-actions { display: flex; justify-content: space-between; flex-wrap: wrap; gap: 10px; align-items: center; margin-bottom: 16px; }
    .pill-group { display: flex; gap: 10px; flex-wrap: wrap; }
    .pill { padding: 10px 14px; border-radius: 999px; border: 1px solid #cbd5e1; background: #fff; color: #334155; cursor: pointer; font-size: 13px; }
    .pill.active { background: #0f172a; color: #fff; border-color: #0f172a; }
    .chart-bar-area { display: flex; align-items: flex-end; gap: 16px; padding: 20px 0; min-height: 220px; justify-content: center; }
    .chart-bar { display: flex; flex-direction: column; align-items: center; gap: 12px; flex: 1 1 80px; max-width: 120px; }
    .chart-bar-inner { width: 100%; min-height: 140px; border-radius: 12px 12px 0 0; background: linear-gradient(180deg, #e2e8f0, #f8fafc); display: flex; align-items: flex-end; justify-content: center; overflow: hidden; border: 1px solid #e2e8f0; border-bottom: none; }
    .chart-bar-value { display: block; width: 100%; background: linear-gradient(180deg, #0f172a, #2563eb); border-radius: 12px 12px 0 0; transition: height 0.5s ease; }
    .chart-bar-label { font-size: 11px; color: #64748b; text-align: center; }
    .chart-bar-tooltip { position: absolute; top: -35px; left: 50%; transform: translateX(-50%); background: #0f172a; color: #fff; padding: 6px 10px; border-radius: 6px; font-size: 12px; white-space: nowrap; opacity: 0; transition: opacity 0.2s; pointer-events: none; }
    .chart-bar:hover .chart-bar-tooltip { opacity: 1; }
    .table-wrap { overflow-x: auto; }
    table { width: 100%; border-collapse: collapse; }
    table th, table td { padding: 12px 10px; border-bottom: 1px solid #e2e8f0; text-align: left; font-size: 13px; color: #334155; }
    table th { background: #f8fafc; font-weight: 700; }
    .topbar { display: flex; justify-content: space-between; align-items: center; gap: 10px; flex-wrap: wrap; margin-bottom: 18px; }
    .topbar-menu { position: relative; }
    .menu-trigger { border: 1px solid #d1d5db; border-radius: 12px; background: #fff; color: #111827; padding: 10px 14px; cursor: pointer; }
    .menu-dropdown { position: absolute; top: calc(100% + 8px); right: 0; background: #fff; border: 1px solid #e2e8f0; border-radius: 14px; box-shadow: 0 16px 35px rgba(15, 23, 42, 0.12); display: none; min-width: 210px; z-index: 100; }
    .menu-dropdown.active { display: block; }
    .menu-dropdown a { display: block; padding: 10px 12px; color: #111827; text-decoration: none; border-bottom: 1px solid #f1f5f9; font-size: 13px; }
    .menu-dropdown a:last-child { border-bottom: none; }
    .menu-dropdown a:hover { background: #f8fafc; }
    @media(max-width: 980px) { .stats-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); } }
    @media(max-width: 720px) { .stats-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); } }
  </style>
</head>
<body>
  <div class="page-header">
    <a class="back-arrow" href="owner_administrative_page.php">←</a>
    <div class="header-content">
      <div class="header-title">Revenue Analytics</div>
      <div class="header-meta">Owner-only analytics and export for revenue performance.</div>
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
      <h1>Owner Revenue Analytics</h1>
      <p>Review sales performance across daily, weekly, monthly and annual revenue trends with export support.</p>
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
          <button type="button" class="btn primary" onclick="downloadRevenueCsv()">Export Revenue CSV</button>
          <a class="btn" href="owner_administrative_page.php">Back to Overview</a>
        </div>
      </form>
    </div>

    <div class="stats-grid">
      <div class="stat-card"><strong><?= intval($revenueData['total_orders']) ?></strong><span>Total Orders</span></div>
      <div class="stat-card"><strong>₱<?= format_peso_display($revenueData['total_revenue']) ?></strong><span>Total Revenue</span></div>
    </div>

    <section class="section-card">
      <div class="section-actions">
        <div><h2>Revenue Trend</h2></div>
        <div class="pill-group">
          <button class="pill active" type="button" onclick="setChartRange('weeks')">Last 4 Weeks</button>
          <button class="pill" type="button" onclick="setChartRange('months')">Last 6 Months</button>
          <button class="pill" type="button" onclick="setChartRange('years')">Last 5 Years</button>
        </div>
      </div>
      <div id="revenueChart" class="chart-bar-area"></div>
    </section>

    <section class="section-card">
      <div class="section-actions">
        <div><h2>Revenue Breakdown</h2></div>
      </div>
      <div class="table-wrap">
        <table>
          <thead>
            <tr><th>Metric</th><th>Value</th></tr>
          </thead>
          <tbody>
            <tr><td>Total Orders</td><td><?= intval($revenueData['total_orders']) ?></td></tr>
            <tr><td>Total Revenue</td><td>₱<?= format_peso_display($revenueData['total_revenue']) ?></td></tr>
          </tbody>
        </table>
      </div>
    </section>
  </div>

  <script>
    const reportSeries = <?= json_encode($revenueData['series'], JSON_UNESCAPED_UNICODE) ?>;
    let currentRange = 'weeks';

    function setChartRange(range) {
      currentRange = range;
      document.querySelectorAll('.pill').forEach((pill) => {
        pill.classList.toggle('active', pill.textContent.includes(range === 'weeks' ? '4 Weeks' : range === 'months' ? '6 Months' : '5 Years'));
      });
      renderRevenueChart();
    }

    function renderRevenueChart() {
      const container = document.getElementById('revenueChart');
      const series = reportSeries[currentRange] || [];
      container.innerHTML = '';
      
      if (series.length === 0) {
        container.innerHTML = '<div style="width:100%;min-height:180px;display:flex;align-items:center;justify-content:center;color:#64748b;font-size:14px;background:#f8fafc;border-radius:12px;">No revenue data available</div>';
        return;
      }
      
      const maxValue = Math.max(...series.map((item) => item.value), 1);
      const wrapper = document.createElement('div');
      wrapper.style.cssText = 'display:flex;align-items:flex-end;gap:16px;width:100%;padding:10px 0;';
      
      series.forEach((item, index) => {
        const bar = document.createElement('div');
        bar.style.cssText = 'flex:1 1 60px;max-width:100px;display:flex;flex-direction:column;align-items:center;gap:8px;';
        
        // Value on top
        const valueEl = document.createElement('div');
        valueEl.style.cssText = 'font-size:14px;font-weight:700;color:#0f172a;';
        valueEl.textContent = '₱' + item.value.toLocaleString();
        
        // Bar container
        const barContainer = document.createElement('div');
        barContainer.style.cssText = 'width:100%;height:180px;background:#f1f5f9;border-radius:10px;display:flex;align-items:flex-end;justify-content:center;position:relative;overflow:hidden;';
        
        // Bar fill
        const fill = document.createElement('div');
        const fillPercent = Math.max(8, Math.round((item.value / maxValue) * 100));
        fill.style.cssText = `width:70%;height:${fillPercent}%;background:linear-gradient(180deg, #0f172a, #2563eb);border-radius:10px 10px 0 0;transition:height 0.5s ease;cursor:pointer;`;
        fill.setAttribute('title', item.label + ': ₱' + item.value.toLocaleString());
        
        // Hover effect
        fill.addEventListener('mouseenter', () => {
          fill.style.filter = 'brightness(1.15)';
        });
        fill.addEventListener('mouseleave', () => {
          fill.style.filter = 'brightness(1)';
        });
        
        barContainer.appendChild(fill);
        
        // Label at bottom
        const labelEl = document.createElement('div');
        labelEl.style.cssText = 'font-size:11px;color:#64748b;text-align:center;';
        labelEl.textContent = item.label;
        
        bar.appendChild(valueEl);
        bar.appendChild(barContainer);
        bar.appendChild(labelEl);
        wrapper.appendChild(bar);
      });
      
      container.appendChild(wrapper);
    }

    function downloadRevenueCsv() {
      const month = document.querySelector('select[name="month"]').value;
      const year = document.querySelector('select[name="year"]').value;
      const rows = [
        ['Revenue Analytics'],
        [],
        ['Metric', 'Value'],
        ['Total Orders', <?= intval($revenueData['total_orders']) ?>],
        ['Total Revenue', '₱<?= format_peso_display($revenueData['total_revenue']) ?>'],
        [],
        ['Range', 'Value']
      ];
      (reportSeries[currentRange] || []).forEach((item) => rows.push([item.label, item.value]));
      if (rows.length <= 6) {
        alert('No data available for the selected period.');
        return;
      }
      const csvContent = rows.map((row) => row.map((cell) => `"${String(cell).replace(/"/g, '""')}"`).join(',')).join('\r\n');
      const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
      const url = URL.createObjectURL(blob);
      const link = document.createElement('a');
      let filename = 'owner_revenue_analytics.csv';
      if (month && year) {
        filename = `owner_revenue_analytics_${year}_${month}.csv`;
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

    renderRevenueChart();
  </script>
</body>
</html>
