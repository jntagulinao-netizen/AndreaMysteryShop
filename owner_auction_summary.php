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

$auctionList = [];
$auctionListSql = 'SELECT auction_id, item_name, auction_status, start_at, end_at, current_bid, starting_bid
  FROM auction_listings' . $whereClause . '
  ORDER BY start_at DESC
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
            'highest_bid' => floatval($row['current_bid'] ?? $row['starting_bid'] ?? 0)
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
      <div class="section-actions"><div><h2>Recent Auction Listings</h2></div></div>
      <div class="table-wrap">
        <table>
          <thead>
            <tr><th>ID</th><th>Title</th><th>Status</th><th>Start</th><th>End</th><th>Highest Bid</th></tr>
          </thead>
          <tbody>
            <?php foreach ($auctionList as $auction): ?>
              <tr>
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
