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

if (isset($_GET['lock']) && $_GET['lock'] === '1') {
    unset($_SESSION['owner_admin_access_unlocked']);
    header('Location: owner_admin_access.php');
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

$orderStatus = [
  'pending_orders' => 0,
  'processing_orders' => 0,
  'shipped_orders' => 0,
  'delivered_orders' => 0,
  'pickups' => 0,
  'picked_up_orders' => 0
];
$orderStatusSql = 'SELECT
  SUM(CASE WHEN status = "pending" THEN 1 ELSE 0 END) AS pending_orders,
  SUM(CASE WHEN status = "processing" THEN 1 ELSE 0 END) AS processing_orders,
  SUM(CASE WHEN status = "shipped" THEN 1 ELSE 0 END) AS shipped_orders,
  SUM(CASE WHEN status IN ("delivered", "received", "reviewed") THEN 1 ELSE 0 END) AS delivered_orders,
  SUM(CASE WHEN delivery_type = "pickup" THEN 1 ELSE 0 END) AS pickups,
  SUM(CASE WHEN delivery_type = "pickup" AND status IN ("delivered", "received", "reviewed") THEN 1 ELSE 0 END) AS picked_up_orders
  FROM orders
  WHERE archived = 0 AND binned = 0';
$statusResult = $conn->query($orderStatusSql);
if ($statusResult && $statusResult->num_rows > 0) {
  $orderStatus = array_merge($orderStatus, $statusResult->fetch_assoc());
}

$quickStats = [
  'today_orders' => 0,
  'week_revenue' => 0.0,
  'month_revenue' => 0.0,
  'year_revenue' => 0.0
];
$quickStatsSql = 'SELECT
  SUM(CASE WHEN DATE(order_date) = CURDATE() THEN 1 ELSE 0 END) AS today_orders,
  SUM(CASE WHEN YEARWEEK(order_date, 1) = YEARWEEK(CURDATE(), 1) AND status IN ("delivered", "received", "reviewed") THEN total_amount ELSE 0 END) AS week_revenue,
  SUM(CASE WHEN MONTH(order_date) = MONTH(CURDATE()) AND YEAR(order_date) = YEAR(CURDATE()) AND status IN ("delivered", "received", "reviewed") THEN total_amount ELSE 0 END) AS month_revenue,
  SUM(CASE WHEN YEAR(order_date) = YEAR(CURDATE()) AND status IN ("delivered", "received", "reviewed") THEN total_amount ELSE 0 END) AS year_revenue
  FROM orders
  WHERE archived = 0 AND binned = 0';
$quickResult = $conn->query($quickStatsSql);
if ($quickResult && $quickResult->num_rows > 0) {
  $quickStats = array_merge($quickStats, $quickResult->fetch_assoc());
}

$customerMetrics = [
  'total_customers' => 0,
  'new_signups_month' => 0
];
$customerSql = 'SELECT
  COUNT(*) AS total_customers,
  SUM(CASE WHEN created_at >= DATE_FORMAT(CURDATE(), "%Y-%m-01") THEN 1 ELSE 0 END) AS new_signups_month
  FROM users
  WHERE LOWER(role) = "user"';
$customerResult = $conn->query($customerSql);
if ($customerResult && $customerResult->num_rows > 0) {
  $customerMetrics = array_merge($customerMetrics, $customerResult->fetch_assoc());
}

$auctionSummary = [
  'live_auctions' => 0,
  'ended_today' => 0,
  'upcoming' => 0
];
$auctionSql = 'SELECT
  SUM(auction_status = "active") AS live_auctions,
  SUM(auction_status IN ("ended", "sold") AND DATE(end_at) = CURDATE()) AS ended_today,
  SUM(auction_status = "scheduled") AS upcoming
  FROM auction_listings';
$auctionResult = $conn->query($auctionSql);
if ($auctionResult && $auctionResult->num_rows > 0) {
  $auctionSummary = array_merge($auctionSummary, $auctionResult->fetch_assoc());
}

$topSellers = [];
$topSellersSql = 'SELECT COALESCE(p.product_name, "Unknown Item") AS product_name, SUM(oi.quantity) AS total_qty
  FROM order_items oi
  LEFT JOIN products p ON oi.product_id = p.product_id
  GROUP BY oi.product_id
  ORDER BY total_qty DESC
  LIMIT 6';
$topResult = $conn->query($topSellersSql);
if ($topResult) {
  while ($row = $topResult->fetch_assoc()) {
    $topSellers[] = [
      'product_name' => $row['product_name'] ?? 'Unknown Item',
      'total_qty' => intval($row['total_qty'] ?? 0)
    ];
  }
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
  LIMIT 5';
$orderFeedResult = $conn->query($recentOrdersSql);
if ($orderFeedResult) {
  while ($row = $orderFeedResult->fetch_assoc()) {
    $activityFeed[] = [
      'timestamp' => $row['order_date'],
      'title' => 'Order #' . intval($row['order_id']) . ' placed',
      'subtitle' => trim($row['customer_name'] ?: 'Customer'),
      'note' => 'Status: ' . ($row['status'] ?? 'unknown') . ' · Items: ' . trim($row['products'] ?: 'No details'),
      'timestamp_label' => date('M j, H:i', strtotime($row['order_date']))
    ];
  }
}
$recentCustomersSql = 'SELECT full_name, email, created_at FROM users WHERE LOWER(role) = "user" ORDER BY created_at DESC LIMIT 4';
$customerFeedResult = $conn->query($recentCustomersSql);
if ($customerFeedResult) {
  while ($row = $customerFeedResult->fetch_assoc()) {
    $activityFeed[] = [
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
$activityFeed = array_slice($activityFeed, 0, 6);

function buildReportSeries($conn, $unit) {
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
      WHERE archived = 0 AND binned = 0 AND status IN ("delivered", "received", "reviewed") AND order_date >= DATE_SUB(CURDATE(), INTERVAL 28 DAY)
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
      WHERE archived = 0 AND binned = 0 AND status IN ("delivered", "received", "reviewed") AND order_date >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
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
      WHERE archived = 0 AND binned = 0 AND status IN ("delivered", "received", "reviewed") AND order_date >= DATE_SUB(CURDATE(), INTERVAL 5 YEAR)
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

$reportData = [
  'quickStats' => [
    ['label' => "Today's Orders", 'value' => intval($quickStats['today_orders'] ?? 0)],
    ['label' => 'This Week Revenue', 'value' => '₱' . format_peso_display($quickStats['week_revenue'] ?? 0)],
    ['label' => 'This Month Revenue', 'value' => '₱' . format_peso_display($quickStats['month_revenue'] ?? 0)],
    ['label' => 'This Year Revenue', 'value' => '₱' . format_peso_display($quickStats['year_revenue'] ?? 0)]
  ],
  'orderStatus' => [
    ['label' => 'Pending', 'value' => intval($orderStatus['pending_orders'] ?? 0)],
    ['label' => 'Processing', 'value' => intval($orderStatus['processing_orders'] ?? 0)],
    ['label' => 'Shipped', 'value' => intval($orderStatus['shipped_orders'] ?? 0)],
    ['label' => 'Delivered', 'value' => intval($orderStatus['delivered_orders'] ?? 0)],
    ['label' => 'Pickups', 'value' => intval($orderStatus['pickups'] ?? 0)]
  ],
  'topSellers' => array_map(function ($item) {
    return ['product_name' => $item['product_name'], 'total_qty' => $item['total_qty']];
  }, $topSellers),
  'auctionSummary' => [
    ['label' => 'Live Auctions', 'value' => intval($auctionSummary['live_auctions'] ?? 0)],
    ['label' => 'Ended Today', 'value' => intval($auctionSummary['ended_today'] ?? 0)],
    ['label' => 'Upcoming', 'value' => intval($auctionSummary['upcoming'] ?? 0)]
  ],
  'customerMetrics' => [
    ['label' => 'Total Customers', 'value' => intval($customerMetrics['total_customers'] ?? 0)],
    ['label' => 'New Signups This Month', 'value' => intval($customerMetrics['new_signups_month'] ?? 0)]
  ],
  'revenueReports' => [
    'weeks' => buildReportSeries($conn, 'weeks'),
    'months' => buildReportSeries($conn, 'months'),
    'years' => buildReportSeries($conn, 'years')
  ],
  'orderStatusChart' => [
    ['label' => 'Pending', 'value' => intval($orderStatus['pending_orders'] ?? 0)],
    ['label' => 'Processing', 'value' => intval($orderStatus['processing_orders'] ?? 0)],
    ['label' => 'Shipped', 'value' => intval($orderStatus['shipped_orders'] ?? 0)],
    ['label' => 'Delivered', 'value' => intval($orderStatus['delivered_orders'] ?? 0)],
    ['label' => 'Pickups', 'value' => intval($orderStatus['pickups'] ?? 0)],
    ['label' => 'Picked Up', 'value' => intval($orderStatus['picked_up_orders'] ?? 0)]
  ],
  'auctionChart' => [
    ['label' => 'Live', 'value' => intval($auctionSummary['live_auctions'] ?? 0)],
    ['label' => 'Ended Today', 'value' => intval($auctionSummary['ended_today'] ?? 0)],
    ['label' => 'Upcoming', 'value' => intval($auctionSummary['upcoming'] ?? 0)]
  ],
  'customerChart' => [
    ['label' => 'Total Customers', 'value' => intval($customerMetrics['total_customers'] ?? 0)],
    ['label' => 'New This Month', 'value' => intval($customerMetrics['new_signups_month'] ?? 0)]
  ],
  'topSellerChart' => array_map(function ($item) {
    return ['label' => $item['product_name'], 'value' => intval($item['total_qty'])];
  }, $topSellers),
  'customerPieChart' => [
    ['label' => 'New This Month', 'value' => intval($customerMetrics['new_signups_month'] ?? 0)],
    ['label' => 'Existing Customers', 'value' => max(0, intval($customerMetrics['total_customers'] ?? 0) - intval($customerMetrics['new_signups_month'] ?? 0))]
  ]
];
?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Owner Administrative Page</title>
  <link rel="stylesheet" href="main.css">
  <style>
    * { box-sizing: border-box; }
    body { margin: 0; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f3f4f6; padding-bottom: 70px; }

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
      gap: 12px;
      padding: 12px 16px;
      min-height: 58px;
      border-radius: 12px;
      border: 1px solid #eee;
    }
    .back-arrow { cursor: pointer; font-size: 24px; color: #333; padding: 4px; line-height: 1; }
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
      min-width: 170px;
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

    .wrap { width: calc(100% - 48px); margin: 0 auto; padding: 84px 0 18px; }
    .hero {
      background: linear-gradient(135deg, #0f172a, #1e293b);
      color: #fff;
      border-radius: 14px;
      padding: 18px;
      margin-bottom: 24px;
      box-shadow: 0 22px 48px rgba(15, 23, 42, 0.15);
      border: 1px solid rgba(255, 255, 255, 0.08);
    }
    .hero h1 { margin: 0; font-size: 26px; }
    .actions { margin-top: 16px; display: flex; gap: 10px; flex-wrap: wrap; }
    .section-card {
      background: #fff;
      border: 1px solid #d1d5db;
      border-radius: 18px;
      padding: 20px;
      box-shadow: 0 14px 30px rgba(15, 23, 42, 0.06);
      margin-bottom: 22px;
    }
    .section-card + .section-card { border-top: 1px solid rgba(226, 232, 240, 0.75); }
    .btn { border: 1px solid #d1d5db; background: #fff; color: #1f2937; border-radius: 8px; padding: 10px 14px; font-weight: 700; text-decoration: none; display: inline-flex; align-items: center; gap: 8px; }
    .btn.primary { background: #f3f4f6; color: #1f2937; border-color: #cbd5e1; }
    .btn.primary:hover { background: #e2e8f0; }
    .btn.warn { background: #ef4444; color: #fff; border-color: #ef4444; }

    .stats-grid {
      display: grid;
      grid-template-columns: repeat(4, minmax(0, 1fr));
      gap: 12px;
      margin-bottom: 18px;
    }
    .stat-card {
      background: #fff;
      border: 1px solid #e5e7eb;
      border-radius: 16px;
      padding: 18px 16px;
      box-shadow: 0 10px 24px rgba(15, 23, 42, 0.06);
    }
    .stat-card strong { display: block; font-size: 28px; color: #111827; margin-bottom: 6px; }
    .stat-card span { font-size: 13px; color: #6b7280; }

    .section-card {
      background: #fff;
      border: 1px solid #e5e7eb;
      border-radius: 22px;
      padding: 24px;
      box-shadow: 0 14px 30px rgba(15, 23, 42, 0.06);
      margin-bottom: 26px;
      position: relative;
      overflow: hidden;
    }
    .section-card::before {
      content: '';
      position: absolute;
      left: 24px;
      right: 24px;
      top: 0;
      height: 2px;
      background: rgba(56, 139, 253, 0.22);
      border-radius: 999px;
    }
    .section-card h2 { margin: 0; font-size: 20px; color: #111827; padding-bottom: 8px; border-bottom: 1px solid #e5e7eb; display: inline-block; }
    .section-card .section-row { display: grid; grid-template-columns: 1.4fr 1fr; gap: 18px; align-items: start; }
    .section-card .section-row-full { display: grid; gap: 18px; }
    .section-actions { display: flex; gap: 10px; flex-wrap: wrap; align-items: center; margin-bottom: 18px; padding-bottom: 16px; border-bottom: 1px solid #f1f5f9; }
    .pill-group { display: flex; gap: 8px; flex-wrap: wrap; }
    .pill { padding: 8px 12px; border-radius: 999px; background: #f8fafc; color: #334155; border: 1px solid #e2e8f0; cursor: pointer; font-size: 13px; }
    .pill.active { background: #0f172a; color: #fff; border-color: #0f172a; }

    .chart-bar-area { display: flex; align-items: flex-end; gap: 12px; padding: 14px 0; min-height: 172px; }
    .chart-bar { flex: 1; display: flex; flex-direction: column; align-items: center; gap: 10px; }
    .chart-bar-inner {
      width: 100%;
      background: linear-gradient(180deg, #e5e7eb 0%, #f8fafc 100%);
      border-radius: 16px 16px 0 0;
      overflow: hidden;
      position: relative;
      min-height: 120px;
      border: 1px solid #d1d5db;
    }
    .chart-bar-inner span {
      position: absolute;
      bottom: 0;
      left: 0;
      width: 100%;
      border-radius: 16px 16px 0 0;
      transition: height .32s ease, background .2s ease;
    }
    .chart-pie { display: flex; flex-wrap: wrap; gap: 20px; align-items: center; justify-content: space-between; padding: 14px 0; }
    .pie-chart {
      width: 180px;
      height: 180px;
      border-radius: 50%;
      background: #f8fafc;
      display: grid;
      place-items: center;
      box-shadow: inset 0 0 0 1px #e5e7eb;
      position: relative;
    }
    .pie-chart-inner {
      width: 94%;
      height: 94%;
      border-radius: 50%;
      background: conic-gradient(#2563eb 0%, #93c5fd 100%);
      display: grid;
      place-items: center;
    }
    .pie-chart-center {
      width: 80px;
      height: 80px;
      border-radius: 50%;
      background: #fff;
      display: grid;
      place-items: center;
      text-align: center;
      box-shadow: 0 0 0 8px rgba(255,255,255,0.6);
    }
    .pie-chart-center strong { font-size: 18px; color: #111827; display: block; }
    .pie-chart-center span { font-size: 11px; color: #64748b; }
    .pie-legend { display: grid; gap: 8px; flex: 1 1 220px; }
    .pie-legend-item { display: flex; align-items: center; gap: 8px; font-size: 13px; color: #334155; }
    .pie-legend-color { width: 14px; height: 14px; border-radius: 4px; display: inline-block; flex-shrink: 0; }
    .empty-chart { width: 100%; min-height: 140px; display: grid; place-items: center; color: #64748b; font-size: 13px; background: #f8fafc; border: 1px solid #e5e7eb; border-radius: 16px; }
    .chart-label { font-size: 12px; color: #64748b; text-align: center; }
    .chart-value { font-size: 12px; color: #0f172a; font-weight: 700; }

    .status-grid, .summary-grid { display: grid; gap: 12px; }
    .status-grid { grid-template-columns: repeat(5, minmax(0, 1fr)); }
    .status-card {
      background: #fff;
      border: 1px solid #e5e7eb;
      border-radius: 16px;
      padding: 16px;
      text-align: center;
      box-shadow: 0 10px 24px rgba(15, 23, 42, 0.04);
    }
    .status-card strong { display: block; font-size: 24px; color: #111827; margin-bottom: 6px; }
    .status-card small { display: block; color: #64748b; font-size: 12px; }

    .summary-grid { grid-template-columns: repeat(3, minmax(0, 1fr)); }
    .summary-card { background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 16px; padding: 16px; }
    .summary-card strong { display: block; font-size: 22px; color: #111827; margin-bottom: 6px; }
    .summary-card div { color: #475569; font-size: 13px; }

    .top-sellers { list-style: none; margin: 0; padding: 0; display: grid; gap: 10px; }
    .top-sellers li { display: flex; justify-content: space-between; gap: 10px; background: #fff; border: 1px solid #e5e7eb; border-radius: 14px; padding: 12px 14px; }
    .top-sellers strong { font-size: 15px; color: #111827; }
    .top-sellers span { font-size: 13px; color: #475569; }

    .timeline { list-style: none; margin: 0; padding: 0; }
    .timeline li { position: relative; padding: 14px 0 14px 24px; border-left: 2px solid #e2e8f0; }
    .timeline li::before { content: '';
      position: absolute;
      left: -8px;
      top: 18px;
      width: 14px;
      height: 14px;
      border-radius: 999px;
      background: #fff;
      border: 2px solid #2563eb;
    }
    .timeline-item-title { font-size: 14px; color: #111827; font-weight: 700; }
    .timeline-item-meta { font-size: 12px; color: #64748b; margin: 6px 0 2px; }
    .timeline-item-note { font-size: 13px; color: #475569; }

    @media (max-width: 1080px) {
      .stats-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
      .status-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
      .summary-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
      .section-card .section-row { grid-template-columns: 1fr; }
    }
    @media (max-width: 720px) {
      .page-header { top: 8px; width: calc(100% - 24px); }
      .wrap { width: calc(100% - 24px); padding-top: 74px; }
      .header-meta { display: none; }
      .stats-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
      .chart-bar-area { min-height: 140px; }
    }
  </style>
</head>
<body>
  <div class="page-header">
    <div class="back-arrow" onclick="window.location.href='admin_profile.php'">‹</div>
    <div class="header-title">Owner Administrative Dashboard</div>
    <div class="header-meta">Updated <?php echo date('d/m/Y H:i:s'); ?></div>
    <div class="topbar-menu">
      <button type="button" class="menu-trigger" onclick="toggleTopbarMenu(event)">...</button>
      <div class="menu-dropdown" id="topbarMenuDropdown">
        <a href="owner_revenue_analytics.php">Revenue Analytics</a>
        <a href="owner_order_status.php">Order Status Overview</a>
        <a href="owner_top_selling_products.php">Top Selling Products</a>
        <a href="owner_recent_activity.php">Recent Activity Feed</a>
        <a href="owner_customer_management.php">Customer Management</a>
        <a href="owner_auction_summary.php">Auction Summary</a>
        <a href="admin_dashboard.php">Return to Admin Dashboard</a>
      </div>
    </div>
  </div>

  <div class="wrap">
    <section class="hero">
      <h1>Owner Analytical Dashboard</h1>
      <div class="actions">
        <a class="btn" href="admin_profile.php">Admin Profile</a>
        <a class="btn" href="admin_dashboard.php">Admin Dashboard</a>
        <a class="btn warn" href="owner_administrative_page.php?lock=1">Lock Owner Access</a>
      </div>
    </section>

    <div class="stats-grid">
      <div class="stat-card"><strong><?php echo intval($quickStats['today_orders'] ?? 0); ?></strong><span>Today's Orders</span></div>
      <div class="stat-card"><strong>₱<?php echo format_peso_display($quickStats['week_revenue'] ?? 0); ?></strong><span>This Week Revenue</span></div>
      <div class="stat-card"><strong>₱<?php echo format_peso_display($quickStats['month_revenue'] ?? 0); ?></strong><span>This Month Revenue</span></div>
      <div class="stat-card"><strong>₱<?php echo format_peso_display($quickStats['year_revenue'] ?? 0); ?></strong><span>This Year Revenue</span></div>
    </div>

    <section id="revenueAnalytics" class="section-card">
      <div class="section-actions">
        <div>
          <h2>Revenue Analytics</h2>
          <div style="color:#64748b;font-size:13px;">Visual graphs display revenue trends across weekly, monthly, and yearly periods.</div>
        </div>
        <div class="pill-group">
          <button type="button" class="pill active" data-mode="weeks" onclick="switchReportMode('weeks', this)">Weeks</button>
          <button type="button" class="pill" data-mode="months" onclick="switchReportMode('months', this)">Months</button>
          <button type="button" class="pill" data-mode="years" onclick="switchReportMode('years', this)">Years</button>
        </div>
        <div style="margin-left:auto;"><a class="btn primary" href="owner_revenue_analytics.php">View More</a></div>
      </div>
      <div class="chart-bar-area" id="reportChartArea"></div>
      <div class="chart-label" id="reportChartSubtitle">Showing the last weeks revenue totals.</div>
    </section>

    <section id="recentActivity" class="section-card">
      <div class="section-actions">
        <div>
          <h2>Recent Activity Feed</h2>
          <div style="color:#64748b;font-size:13px;">Chronological timeline of orders, new registrations, and recent system actions alongside the graph.</div>
        </div>
        <div style="margin-left:auto;"><a class="btn primary" href="owner_recent_activity.php">View More</a></div>
      </div>
      <ul class="timeline">
        <?php foreach ($activityFeed as $activity): ?>
        <li>
          <div class="timeline-item-title"><?php echo htmlspecialchars($activity['title']); ?></div>
          <div class="timeline-item-meta"><?php echo htmlspecialchars($activity['subtitle']); ?> · <?php echo htmlspecialchars($activity['timestamp_label']); ?></div>
          <div class="timeline-item-note"><?php echo htmlspecialchars($activity['note']); ?></div>
        </li>
        <?php endforeach; ?>
        <?php if (empty($activityFeed)): ?>
        <li><div class="timeline-item-title">No recent activities available</div><div class="timeline-item-meta">Start processing orders or acquiring customers to populate this feed.</div></li>
        <?php endif; ?>
      </ul>
    </section>
  </div>

  <nav class="mobile-bottom-nav fixed">
    <div class="mobile-nav-inner">
      <a href="owner_administrative_page.php" class="active">
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
      <a href="owner_auction_summary.php">
        <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M12 3l2.9 5.9 6.5.9-4.7 4.5 1.1 6.4-5.8-3.1-5.8 3.1 1.1-6.4-4.7-4.5 6.5-.9z"></path></svg>
        <span>Auction</span>
      </a>
    </div>
  </nav>

  <script>
    const reportData = <?php echo json_encode($reportData, JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT); ?>;
    const reportModes = {
      weeks: reportData.revenueReports.weeks,
      months: reportData.revenueReports.months,
      years: reportData.revenueReports.years,
    };

    const chartColors = ['#2563eb', '#0ea5e9', '#22c55e', '#f97316', '#f43f5e', '#a855f7', '#eab308'];

    function renderReportChart(mode) {
      const chartArea = document.getElementById('reportChartArea');
      const chartSubtitle = document.getElementById('reportChartSubtitle');
      const values = reportModes[mode] || [];
      chartArea.innerHTML = '';
      const maxValue = Math.max(...values.map(item => item.value), 1);
      values.forEach((item, index) => {
        const bar = document.createElement('div');
        bar.className = 'chart-bar';
        const inner = document.createElement('div');
        inner.className = 'chart-bar-inner';
        const fill = document.createElement('span');
        fill.style.height = `${Math.round((item.value / maxValue) * 100)}%`;
        // Use a unique gradient for each bar for colorfulness
        const color1 = chartColors[index % chartColors.length];
        const color2 = chartColors[(index + 1) % chartColors.length];
        fill.style.background = `linear-gradient(180deg, ${color1} 0%, ${color2} 100%)`;
        fill.setAttribute('title', item.label + ': ₱' + item.value.toLocaleString());
        inner.appendChild(fill);
        bar.appendChild(inner);
        const value = document.createElement('div');
        value.className = 'chart-value';
        value.textContent = '₱' + item.value.toLocaleString();
        const label = document.createElement('div');
        label.className = 'chart-label';
        label.textContent = item.label;
        bar.appendChild(value);
        bar.appendChild(label);
        chartArea.appendChild(bar);
      });
      chartSubtitle.textContent = `Showing the last ${mode} revenue totals.`;
    }

    function switchReportMode(mode, button) {
      document.querySelectorAll('.pill').forEach(el => el.classList.remove('active'));
      button.classList.add('active');
      renderReportChart(mode);
    }

    function renderSimpleBarChart(areaId, subtitleId, values, subtitleText) {
      const chartArea = document.getElementById(areaId);
      const chartSubtitle = document.getElementById(subtitleId);
      if (!chartArea || !chartSubtitle) return;
      chartArea.innerHTML = '';
      chartSubtitle.textContent = subtitleText;
      const maxValue = Math.max(...values.map(item => item.value), 1);
      values.forEach((item, index) => {
        const bar = document.createElement('div');
        bar.className = 'chart-bar';
        const inner = document.createElement('div');
        inner.className = 'chart-bar-inner';
        const fill = document.createElement('span');
        const fillPercent = maxValue === 0 ? 4 : Math.max(4, Math.round((item.value / maxValue) * 100));
        fill.style.height = `${fillPercent}%`;
        fill.style.background = chartColors[index % chartColors.length];
        fill.setAttribute('title', item.label + ': ' + item.value.toLocaleString());
        inner.appendChild(fill);
        bar.appendChild(inner);
        const value = document.createElement('div');
        value.className = 'chart-value';
        value.textContent = item.value.toLocaleString();
        const label = document.createElement('div');
        label.className = 'chart-label';
        label.textContent = item.label;
        bar.appendChild(value);
        bar.appendChild(label);
        chartArea.appendChild(bar);
      });
      if (values.length === 0) {
        chartArea.innerHTML = '<div class="empty-chart">No data available for this chart.</div>';
      }
    }

    function renderPieChart(areaId, subtitleId, values, subtitleText) {
      const chartArea = document.getElementById(areaId);
      const chartSubtitle = document.getElementById(subtitleId);
      if (!chartArea || !chartSubtitle) return;
      chartArea.innerHTML = '';
      chartSubtitle.textContent = subtitleText;
      const total = values.reduce((sum, item) => sum + item.value, 0);
      if (total <= 0) {
        chartArea.innerHTML = '<div class="empty-chart">No data available for this chart.</div>';
        return;
      }
      let offset = 0;
      const slices = values.map((item, index) => {
        const color = chartColors[index % chartColors.length];
        const percentage = (item.value / total) * 100;
        const slice = `${color} ${offset}% ${offset + percentage}%`;
        offset += percentage;
        return slice;
      });
      const pieWrapper = document.createElement('div');
      pieWrapper.className = 'chart-pie';
      const pie = document.createElement('div');
      pie.className = 'pie-chart';
      const inner = document.createElement('div');
      inner.className = 'pie-chart-inner';
      inner.style.background = `conic-gradient(${slices.join(', ')})`;
      const center = document.createElement('div');
      center.className = 'pie-chart-center';
      center.innerHTML = `<strong>Totals</strong><span>${total.toLocaleString()}</span>`;
      inner.appendChild(center);
      pie.appendChild(inner);
      const legend = document.createElement('div');
      legend.className = 'pie-legend';
      values.forEach((item, index) => {
        const entry = document.createElement('div');
        entry.className = 'pie-legend-item';
        const dot = document.createElement('span');
        dot.className = 'pie-legend-color';
        dot.style.background = chartColors[index % chartColors.length];
        entry.appendChild(dot);
        const labelText = document.createElement('span');
        labelText.textContent = `${item.label}: ${item.value.toLocaleString()}`;
        entry.appendChild(labelText);
        legend.appendChild(entry);
      });
      chartArea.appendChild(pie);
      chartArea.appendChild(legend);
    }

    function downloadReportCsv(event) {
      event.preventDefault();
      const rows = [];
      rows.push(['Owner Administrative Report']);
      rows.push([]);
      rows.push(['Quick Stats']);
      rows.push(['Metric', 'Value']);
      reportData.quickStats.forEach(row => rows.push([row.label, row.value]));
      rows.push([]);
      rows.push(['Order Status Overview']);
      rows.push(['Label', 'Value']);
      reportData.orderStatus.forEach(row => rows.push([row.label, row.value]));
      rows.push([]);
      rows.push(['Top Selling Products']);
      rows.push(['Product', 'Quantity Sold']);
      reportData.topSellers.forEach(item => rows.push([item.product_name, item.total_qty]));
      rows.push([]);
      rows.push(['Auction Summary']);
      rows.push(['Label', 'Value']);
      reportData.auctionSummary.forEach(row => rows.push([row.label, row.value]));
      rows.push([]);
      rows.push(['Customer Growth']);
      rows.push(['Label', 'Value']);
      reportData.customerMetrics.forEach(row => rows.push([row.label, row.value]));

      const csvContent = rows.map(r => r.map(c => `"${String(c).replace(/"/g, '""')}"`).join(',')).join('\r\n');
      const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
      const url = URL.createObjectURL(blob);
      const link = document.createElement('a');
      link.href = url;
      link.download = 'owner_dashboard_report.csv';
      document.body.appendChild(link);
      link.click();
      document.body.removeChild(link);
      URL.revokeObjectURL(url);
    }

    function downloadSectionCsv(event, section) {
      event.preventDefault();
      const rows = [];
      if (section === 'orderStatus') {
        rows.push(['Order Status Overview']);
        rows.push(['Label', 'Value']);
        reportData.orderStatus.forEach(row => rows.push([row.label, row.value]));
      } else if (section === 'auctionSummary') {
        rows.push(['Auction Summary']);
        rows.push(['Label', 'Value']);
        reportData.auctionSummary.forEach(row => rows.push([row.label, row.value]));
      } else if (section === 'topSellers') {
        rows.push(['Top Selling Products']);
        rows.push(['Product', 'Quantity Sold']);
        reportData.topSellers.forEach(item => rows.push([item.product_name, item.total_qty]));
      } else if (section === 'customerMetrics') {
        rows.push(['Customer Management']);
        rows.push(['Label', 'Value']);
        reportData.customerMetrics.forEach(row => rows.push([row.label, row.value]));
      } else {
        rows.push(['Owner Administrative Report']);
      }

      const csvContent = rows.map(r => r.map(c => `"${String(c).replace(/"/g, '""')}"`).join(',')).join('\r\n');
      const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
      const url = URL.createObjectURL(blob);
      const link = document.createElement('a');
      link.href = url;
      link.download = `owner_dashboard_${section}.csv`;
      document.body.appendChild(link);
      link.click();
      document.body.removeChild(link);
      URL.revokeObjectURL(url);
    }

    document.addEventListener('click', (event) => {
      const dropdown = document.getElementById('topbarMenuDropdown');
      const menu = document.querySelector('.topbar-menu');
      if (dropdown && menu && !menu.contains(event.target)) {
        dropdown.classList.remove('active');
      }
    });

    function toggleTopbarMenu(event) {
      event.stopPropagation();
      const dropdown = document.getElementById('topbarMenuDropdown');
      if (dropdown) {
        dropdown.classList.toggle('active');
      }
    }

    renderReportChart('weeks');
  </script>
</body>
</html>
