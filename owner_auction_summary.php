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
    $whereClause = " WHERE YEAR(start_at) = $selectedYear AND MONTH(start_at) = $selectedMonth";
}

$auctionSummary = ['live_auctions' => 0, 'ended_today' => 0, 'upcoming' => 0];
$auctionSql = 'SELECT
  SUM(auction_status = "active") AS live_auctions,
  SUM(auction_status IN ("ended", "sold") AND DATE(end_at) = CURDATE()) AS ended_today,
  SUM(auction_status = "scheduled") AS upcoming
  FROM auction_listings' . $whereClause;
$auctionResult = $conn->query($auctionSql);
if ($auctionResult && $auctionResult->num_rows > 0) {
    $auctionSummary = array_merge($auctionSummary, $auctionResult->fetch_assoc());
}

$auctionOrderChart = [
    ['label' => 'Ordered', 'value' => 0],
    ['label' => 'Non-ordered', 'value' => 0]
];
$orderChartSql = 'SELECT
  SUM(IF(aol.order_id IS NOT NULL, 1, 0)) AS ordered_count,
  SUM(IF(aol.order_id IS NULL, 1, 0)) AS non_ordered_count
  FROM auction_listings l
  LEFT JOIN auction_order_links aol ON aol.auction_id = l.auction_id' . $whereClause;
$orderChartResult = $conn->query($orderChartSql);
if ($orderChartResult && $orderChartResult->num_rows > 0) {
    $orderCounts = $orderChartResult->fetch_assoc();
    $auctionOrderChart = [
        ['label' => 'Ordered', 'value' => intval($orderCounts['ordered_count'] ?? 0)],
        ['label' => 'Non-ordered', 'value' => intval($orderCounts['non_ordered_count'] ?? 0)]
    ];
}

$auctionList = [];
$auctionListSql = 'SELECT l.auction_id, l.item_name, l.auction_status, l.start_at, l.end_at, l.current_bid, l.starting_bid, IF(aol.order_id IS NOT NULL, 1, 0) AS has_order
  FROM auction_listings l
  LEFT JOIN auction_order_links aol ON aol.auction_id = l.auction_id' . $whereClause . '
  ORDER BY l.start_at DESC
  LIMIT 10';
$auctionListResult = $conn->query($auctionListSql);
if ($auctionListResult) {
    while ($row = $auctionListResult->fetch_assoc()) {
        $auctionList[] = [
            'auction_id' => intval($row['auction_id'] ?? 0),
            'item_name' => $row['item_name'] ?? 'Auction Item',
            'auction_status' => $row['auction_status'] ?? 'unknown',
            'start_at' => $row['start_at'] ?? '',
            'end_at' => $row['end_at'] ?? '',
            'highest_bid' => floatval($row['current_bid'] ?? $row['starting_bid'] ?? 0),
            'has_order' => intval($row['has_order'] ?? 0)
        ];
    }
}

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
  <title>Owner Auction Summary</title>
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
    .stat-grid { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 14px; margin-bottom: 18px; }
    .stat-card { background: #fff; border: 1px solid #e5e7eb; border-radius: 16px; padding: 18px; box-shadow: 0 10px 24px rgba(15, 23, 42, 0.06); }
    .stat-card strong { display: block; margin-bottom: 6px; font-size: 24px; }
    .stat-card span { color: #475569; font-size: 13px; }
    .section-card { background: #fff; border: 1px solid #e5e7eb; border-radius: 18px; padding: 20px; box-shadow: 0 10px 24px rgba(15, 23, 42, 0.04); margin-bottom: 18px; }
    .section-card h2 { margin: 0 0 12px; font-size: 18px; color: #0f172a; }
    .section-actions { display: flex; justify-content: space-between; gap: 10px; align-items: center; margin-bottom: 16px; }
    .section-tabs { display: flex; gap: 10px; flex-wrap: wrap; margin-top: 8px; }
    .section-tab-button { border: 1px solid #d1d5db; border-radius: 12px; background: #fff; color: #111827; padding: 10px 14px; font-weight: 700; cursor: pointer; transition: background .18s ease, border-color .18s ease, color .18s ease; }
    .section-tab-button.active { background: #0f172a; border-color: #0f172a; color: #fff; }
    .chart-pie { display: flex; flex-wrap: wrap; gap: 30px; align-items: center; justify-content: center; padding: 20px 0; }
    .pie-chart { width: 160px; height: 160px; min-width: 160px; border-radius: 50%; background: #f8fafc; display: grid; place-items: center; box-shadow: inset 0 0 0 1px #e5e7eb; position: relative; }
    .pie-chart-inner { width: 94%; height: 94%; border-radius: 50%; background: conic-gradient(#2563eb 0%, #93c5fd 100%); display: grid; place-items: center; }
    .pie-chart-center { width: 70px; height: 70px; min-width: 70px; border-radius: 50%; background: #fff; display: grid; place-items: center; text-align: center; box-shadow: 0 0 0 6px rgba(255,255,255,0.8); }
    .pie-chart-center strong { font-size: 18px; color: #111827; display: block; }
    .pie-chart-center span { font-size: 11px; color: #64748b; }
    .pie-legend { display: grid; gap: 10px; flex: 1 1 200px; max-width: 300px; }
    .pie-legend-item { display: flex; align-items: center; gap: 10px; font-size: 13px; color: #334155; }
    .pie-legend-color { width: 12px; height: 12px; min-width: 12px; border-radius: 4px; display: inline-block; flex-shrink: 0; }
    .chart-label { color: #64748b; font-size: 13px; text-align: left; margin-top: 10px; }
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
    <a class="back-arrow" href="owner_administrative_page.php">‹</a>
    <div class="header-content">
      <div class="header-title">Auction Summary</div>
      <div class="header-meta">Review auction activity with live counts and recent listings.</div>
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
      <h1>Auction Summary</h1>
      <p>Get quick auction health metrics with details on active, scheduled and recently ended auctions.</p>
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
          <button type="button" class="btn primary" onclick="downloadAuctionCsv()">Export Auction CSV</button>
          <a class="btn" href="owner_administrative_page.php">Back to Overview</a>
        </div>
      </form>
    </div>

    <div class="stat-grid">
      <div class="stat-card"><strong><?= intval($auctionSummary['live_auctions']) ?></strong><span>Active Auctions</span></div>
      <div class="stat-card"><strong><?= intval($auctionSummary['ended_today']) ?></strong><span>Ended Today</span></div>
      <div class="stat-card"><strong><?= intval($auctionSummary['upcoming']) ?></strong><span>Upcoming Auctions</span></div>
    </div>

    <section class="section-card">
      <div class="section-actions"><div><h2>Auction Order Breakdown</h2><div style="color:#64748b;font-size:13px;">Ordered vs non-ordered auction items for the selected period.</div></div></div>
      <div id="auctionOrderChartArea" class="chart-pie"></div>
      <div class="chart-label" id="auctionOrderChartSubtitle"></div>
    </section>

    <section class="section-card">
      <div class="section-actions">
        <div>
          <h2>Recent Auction Listings</h2>
          <div class="section-tabs" role="tablist" aria-label="Auction listing tabs">
            <button type="button" class="section-tab-button active" data-filter="all">All</button>
            <button type="button" class="section-tab-button" data-filter="ordered">Ordered</button>
            <button type="button" class="section-tab-button" data-filter="not-ordered">Non-ordered</button>
          </div>
        </div>
      </div>
      <div class="table-wrap">
        <table>
          <thead>
            <tr><th>ID</th><th>Title</th><th>Status</th><th>Start</th><th>End</th><th>Highest Bid</th></tr>
          </thead>
          <tbody>
            <?php foreach ($auctionList as $auction): ?>
              <tr data-order-status="<?= $auction['has_order'] ? 'ordered' : 'not-ordered' ?>">
                <td>#<?= $auction['auction_id'] ?></td>
                <td><?= htmlspecialchars($auction['item_name']) ?></td>
                <td><?= htmlspecialchars($auction['auction_status']) ?></td>
                <td><?= htmlspecialchars($auction['start_at']) ?></td>
                <td><?= htmlspecialchars($auction['end_at']) ?></td>
                <td>₱<?= format_peso_display($auction['highest_bid']) ?></td>
              </tr>
            <?php endforeach; ?>
            <?php if (empty($auctionList)): ?>
              <tr><td colspan="6">No auction listings found.</td></tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </section>
  </div>

  <script>
    const chartData = <?php echo json_encode(['auctionOrderChart' => $auctionOrderChart], JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT); ?>;
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

      const container = document.createElement('div');
      container.style.cssText = 'display:flex;flex-wrap:wrap;gap:30px;align-items:center;justify-content:center;width:100%;padding:20px 0;';
      const pie = document.createElement('div');
      pie.style.cssText = 'width:160px;height:160px;min-width:160px;border-radius:50%;background:#f8fafc;display:grid;place-items:center;box-shadow:inset 0 0 0 1px #e5e7eb;position:relative;';
      const inner = document.createElement('div');
      inner.style.cssText = 'width:94%;height:94%;border-radius:50%;background:conic-gradient(' + slices.join(', ') + ');display:grid;place-items:center;';
      const center = document.createElement('div');
      center.style.cssText = 'width:70px;height:70px;min-width:70px;border-radius:50%;background:#fff;display:grid;place-items:center;text-align:center;box-shadow:0 0 0 6px rgba(255,255,255,0.8);';
      center.innerHTML = `<strong style="font-size:18px;color:#111827;display:block;">${total}</strong><span style="font-size:11px;color:#64748b;">Total</span>`;
      inner.appendChild(center);
      pie.appendChild(inner);
      container.appendChild(pie);
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

    function renderAuctionOrderChart() {
      renderPieChart('auctionOrderChartArea', 'auctionOrderChartSubtitle', chartData.auctionOrderChart, 'Ordered vs non-ordered auction items.');
    }

    renderAuctionOrderChart();

    function downloadAuctionCsv() {
      const month = document.querySelector('select[name="month"]').value;
      const year = document.querySelector('select[name="year"]').value;
      const rows = [
        ['Auction Summary'],
        [],
        ['Label', 'Value'],
        ['Active Auctions', <?= intval($auctionSummary['live_auctions']) ?>],
        ['Ended Today', <?= intval($auctionSummary['ended_today']) ?>],
        ['Upcoming Auctions', <?= intval($auctionSummary['upcoming']) ?>],
        [],
        ['ID', 'Title', 'Status', 'Start', 'End', 'Highest Bid']
      ];
      <?php foreach ($auctionList as $auction): ?>
        rows.push(['#<?= $auction['auction_id'] ?>', '<?= addslashes($auction['item_name']) ?>', '<?= addslashes($auction['auction_status']) ?>', '<?= addslashes($auction['start_at']) ?>', '<?= addslashes($auction['end_at']) ?>', '₱<?= format_peso_display($auction['highest_bid']) ?>']);
      <?php endforeach; ?>
      if (rows.length <= 7) {
        alert('No data available for the selected period.');
        return;
      }
      const csvContent = rows.map((row) => row.map((cell) => `"${String(cell).replace(/"/g, '""')}"`).join(',')).join('\r\n');
      const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
      const url = URL.createObjectURL(blob);
      const link = document.createElement('a');
      let filename = 'owner_auction_summary.csv';
      if (month && year) {
        filename = `owner_auction_summary_${year}_${month}.csv`;
      }
      link.href = url;
      link.download = filename;
      document.body.appendChild(link);
      link.click();
      document.body.removeChild(link);
      URL.revokeObjectURL(url);
    }
    function applyAuctionTabFilter(filter) {
      document.querySelectorAll('tbody tr[data-order-status]').forEach(row => {
        const status = row.dataset.orderStatus;
        row.style.display = filter === 'all' || status === filter ? '' : 'none';
      });
      const hasVisible = Array.from(document.querySelectorAll('tbody tr[data-order-status]')).some(row => row.style.display !== 'none');
      const emptyRow = document.querySelector('tbody tr.empty-row');
      if (!hasVisible) {
        if (!emptyRow) {
          const row = document.createElement('tr');
          row.className = 'empty-row';
          row.innerHTML = '<td colspan="6" style="padding:14px 10px;text-align:center;color:#64748b;">No matching auction listings found.</td>';
          document.querySelector('tbody').appendChild(row);
        }
      } else if (emptyRow) {
        emptyRow.remove();
      }
    }

    document.querySelectorAll('.section-tab-button').forEach(button => {
      button.addEventListener('click', () => {
        document.querySelectorAll('.section-tab-button').forEach(btn => btn.classList.remove('active'));
        button.classList.add('active');
        applyAuctionTabFilter(button.dataset.filter);
      });
    });

    applyAuctionTabFilter('all');

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
      <a href="owner_recent_activity.php">
        <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor"><circle cx="12" cy="12" r="10"></circle><polyline points="12,6 12,12 16,14"></polyline></svg>
        <span>Activity</span>
      </a>
      <a href="owner_customer_management.php">
        <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
        <span>Customers</span>
      </a>
      <a href="owner_auction_summary.php" class="active">
        <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M12 3l2.9 5.9 6.5.9-4.7 4.5 1.1 6.4-5.8-3.1-5.8 3.1 1.1-6.4-4.7-4.5 6.5-.9z"></path></svg>
        <span>Auction</span>
      </a>
    </div>
  </nav>
</body>
</html>
