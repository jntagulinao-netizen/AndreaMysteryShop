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
    $whereClause = " AND YEAR(order_date) = $selectedYear AND MONTH(order_date) = $selectedMonth";
}

$orderStatusSql = 'SELECT
  SUM(CASE WHEN status = "pending" THEN 1 ELSE 0 END) AS pending_orders,
  SUM(CASE WHEN status = "processing" THEN 1 ELSE 0 END) AS processing_orders,
  SUM(CASE WHEN status = "shipped" THEN 1 ELSE 0 END) AS shipped_orders,
  SUM(CASE WHEN status IN ("delivered", "received", "reviewed") THEN 1 ELSE 0 END) AS delivered_orders,
  SUM(CASE WHEN delivery_type = "pickup" THEN 1 ELSE 0 END) AS pickups,
  SUM(CASE WHEN delivery_type = "pickup" AND status IN ("delivered", "received", "reviewed") THEN 1 ELSE 0 END) AS picked_up_orders
  FROM orders
  WHERE archived = 0 AND binned = 0' . $whereClause;
$orderStatus = ['pending_orders' => 0, 'processing_orders' => 0, 'shipped_orders' => 0, 'delivered_orders' => 0, 'pickups' => 0, 'picked_up_orders' => 0];
$statusResult = $conn->query($orderStatusSql);
if ($statusResult && $statusResult->num_rows > 0) {
    $orderStatus = array_merge($orderStatus, $statusResult->fetch_assoc());
}

$orderDetails = [];
$orderDetailsSql = 'SELECT o.order_id, o.user_id, o.recipient_id, o.status, o.delivery_type, o.total_amount, o.order_date,
  COALESCE((SELECT uu.full_name FROM users uu WHERE uu.user_id = o.user_id AND LOWER(uu.role) = "user" LIMIT 1), CONCAT("Customer #", o.user_id)) AS customer_full_name,
  COALESCE(u.full_name, CONCAT("Customer #", o.user_id)) AS customer_name,
    COALESCE(r.recipient_name, "") AS recipient_name
  FROM orders o
  LEFT JOIN users u ON u.user_id = o.user_id AND LOWER(u.role) = "user"
  LEFT JOIN recipients r ON r.recipient_id = o.recipient_id AND r.user_id = o.user_id
  WHERE o.archived = 0 AND o.binned = 0' . $whereClause . '
  ORDER BY o.order_date DESC';
$orderDetailsResult = $conn->query($orderDetailsSql);
if ($orderDetailsResult) {
    while ($row = $orderDetailsResult->fetch_assoc()) {
        $orderDetails[] = [
            'order_id' => intval($row['order_id'] ?? 0),
          'user_id' => intval($row['user_id'] ?? 0),
          'recipient_id' => intval($row['recipient_id'] ?? 0),
            'customer_full_name' => $row['customer_full_name'] ?? '',
          'customer_name' => $row['customer_name'] ?? '',
          'recipient_name' => $row['recipient_name'] ?? '',
            'status' => $row['status'] ?? 'unknown',
            'delivery_type' => $row['delivery_type'] ?? 'standard',
            'total_amount' => floatval($row['total_amount'] ?? 0),
            'order_date' => $row['order_date'] ?? ''
        ];
    }
}

$orderActors = [];
$orderActorsSql = 'SELECT o.order_id, m.message_text, m.sender_id, m.sender_role, m.created_at, COALESCE(u.full_name, CONCAT("Admin #", m.sender_id)) AS actor_name
  FROM orders o
  LEFT JOIN conversations c ON c.order_id = o.order_id
  LEFT JOIN messages m ON m.conversation_id = c.conversation_id AND m.message_type = "status_notice"
  LEFT JOIN users u ON u.user_id = m.sender_id
  WHERE o.archived = 0 AND o.binned = 0' . $whereClause . '
  ORDER BY o.order_id DESC, m.created_at ASC, m.message_id ASC';
$orderActorsResult = $conn->query($orderActorsSql);
if ($orderActorsResult) {
  while ($row = $orderActorsResult->fetch_assoc()) {
    $orderId = intval($row['order_id'] ?? 0);
    if ($orderId <= 0 || trim((string)($row['message_text'] ?? '')) === '') {
      continue;
    }
    if (!isset($orderActors[$orderId])) {
      $orderActors[$orderId] = [];
    }
    $orderActors[$orderId][] = [
      'message_text' => $row['message_text'] ?? '',
      'actor_name' => $row['actor_name'] ?? 'Unknown admin',
      'sender_role' => $row['sender_role'] ?? 'system',
      'created_at' => $row['created_at'] ?? ''
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
    #recentOrdersBody tr { transition: background-color .18s ease, transform .18s ease, box-shadow .18s ease; }
    #recentOrdersBody tr:hover { background: #f8fafc; box-shadow: inset 0 0 0 1px #dbe4f0; }
    #recentOrdersBody tr:focus-visible { outline: 2px solid #0f172a; outline-offset: -2px; background: #eef2ff; }
    #recentOrdersBody tr td:first-child { font-weight: 700; color: #0f172a; }
    .topbar { display: flex; justify-content: space-between; align-items: center; gap: 10px; flex-wrap: wrap; margin-bottom: 18px; }
    .topbar-menu { position: relative; }
    .menu-trigger { border: 1px solid #d1d5db; border-radius: 12px; background: #fff; color: #111827; padding: 10px 14px; cursor: pointer; }
    .menu-dropdown { position: absolute; top: calc(100% + 8px); right: 0; background: #fff; border: 1px solid #e2e8f0; border-radius: 14px; box-shadow: 0 16px 35px rgba(15, 23, 42, 0.12); display: none; min-width: 210px; z-index: 100; }
    .menu-dropdown.active { display: block; }
    .menu-dropdown a { display: block; padding: 10px 12px; color: #111827; text-decoration: none; border-bottom: 1px solid #f1f5f9; font-size: 13px; }
    .menu-dropdown a:last-child { border-bottom: none; }
    .menu-dropdown a:hover { background: #f8fafc; }
    .modal-overlay { position: fixed; inset: 0; background: rgba(15, 23, 42, 0.56); display: none; align-items: center; justify-content: center; padding: 16px; z-index: 220; }
    .modal-overlay.open { display: flex; }
    .modal-card { width: min(760px, 100%); background: #fff; border-radius: 18px; border: 1px solid #e5e7eb; box-shadow: 0 24px 60px rgba(15, 23, 42, 0.24); overflow: hidden; }
    .modal-header { display: flex; align-items: flex-start; justify-content: space-between; gap: 12px; padding: 18px 20px; border-bottom: 1px solid #e5e7eb; }
    .modal-header h3 { margin: 0; font-size: 18px; color: #0f172a; }
    .modal-header p { margin: 4px 0 0; color: #64748b; font-size: 13px; }
    .modal-close { border: 1px solid #d1d5db; background: #fff; color: #111827; border-radius: 10px; width: 38px; height: 38px; cursor: pointer; font-size: 20px; line-height: 1; }
    .modal-body { padding: 20px; }
    .detail-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 14px; margin-bottom: 18px; }
    .detail-box { background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 14px; padding: 14px; }
    .detail-box strong { display: block; color: #475569; font-size: 12px; margin-bottom: 6px; text-transform: uppercase; letter-spacing: .02em; }
    .detail-box span { color: #0f172a; font-size: 14px; font-weight: 700; }
    .action-history { display: grid; gap: 10px; }
    .action-history-item { border: 1px solid #e2e8f0; border-radius: 14px; padding: 12px 14px; background: #fff; }
    .action-history-item strong { display: block; color: #0f172a; margin-bottom: 4px; }
    .action-history-item small { color: #64748b; display: block; margin-bottom: 6px; }
    .action-history-item p { margin: 0; color: #334155; font-size: 13px; line-height: 1.45; }
    .no-history { color: #64748b; font-size: 13px; padding: 8px 0; }
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
          <button type="button" class="btn primary" onclick="downloadOrderStatusCsv()">Export Status CSV</button>
          <a class="btn" href="owner_administrative_page.php">Back to Overview</a>
        </div>
      </form>
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
        <div style="margin-left: auto; display: flex; gap: 8px;">
          <select id="statusFilter" onchange="handleStatusFilterChange(this.value)" style="padding: 8px 12px; border: 1px solid #d1d5db; border-radius: 8px; background: #fff; color: #111827; font-size: 13px; cursor: pointer;">
            <option value="">All Statuses</option>
            <option value="pending">Pending</option>
            <option value="processing">Processing</option>
             <option value="pickup">Pickup</option>
            <option value="shipped">Shipped</option>
            <option value="delivered">Delivered</option>
            <option value="pickedup">Hand over</option>
             <option value="received">Received</option>
            <option value="reviewed">Reviewed</option>
            <option value="cancelled">Cancelled</option>
           
           
          </select>
          <select id="sortFilter" onchange="handleSortChange(this.value)" style="padding: 8px 12px; border: 1px solid #d1d5db; border-radius: 8px; background: #fff; color: #111827; font-size: 13px; cursor: pointer;">
            <option value="newest">Newest to Oldest</option>
            <option value="oldest">Oldest to Newest</option>
          </select>
        </div>
      </div>
      <div class="table-wrap">
        <table>
          <thead>
            <tr><th>Order ID</th><th>Status</th><th>Delivery</th><th>Total</th><th>Date</th></tr>
          </thead>
          <tbody id="recentOrdersBody">
            <?php if (empty($orderDetails)): ?>
              <tr><td colspan="5">No recent orders found.</td></tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
      <div id="paginationControls" style="display: flex; justify-content: center; align-items: center; gap: 8px; margin-top: 16px; flex-wrap: wrap;">
      </div>
    </section>
  </div>

  <div id="orderDetailOverlay" class="modal-overlay" role="dialog" aria-modal="true" aria-labelledby="orderDetailTitle">
    <div class="modal-card">
      <div class="modal-header">
        <div>
          <h3 id="orderDetailTitle">Order Details</h3>
          <p id="orderDetailSubtitle">Click an order to see its status and who handled each action.</p>
        </div>
        <button type="button" class="modal-close" onclick="closeOrderDetails()">×</button>
      </div>
      <div class="modal-body">
        <div class="detail-grid">
          <div class="detail-box"><strong>Order ID</strong><span id="detailOrderId"></span></div>
          <div class="detail-box"><strong>Received By</strong><span id="detailCustomerName"></span></div>
          <div class="detail-box"><strong>Status</strong><span id="detailOrderStatus"></span></div>
          <div class="detail-box"><strong>Delivery Type</strong><span id="detailDeliveryType"></span></div>
          <div class="detail-box"><strong>Total</strong><span id="detailOrderTotal"></span></div>
          <div class="detail-box"><strong>Order Date</strong><span id="detailOrderDate"></span></div>
        </div>
        <div style="margin-bottom: 10px; font-weight: 700; color: #0f172a;">Admin Actions</div>
        <div id="detailActionHistory" class="action-history"></div>
      </div>
    </div>
  </div>

  <script>
    const allOrdersData = <?php echo json_encode($orderDetails, JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT); ?>;
    const orderActionData = <?php echo json_encode($orderActors, JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT); ?>;
    const chartData = <?php echo json_encode(['orderStatusChart' => $orderStatusChart], JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT); ?>;
    const chartColors = ['#2563eb', '#0ea5e9', '#22c55e', '#f97316', '#f43f5e', '#a855f7'];
    
    let currentSortOrder = 'newest';
    let currentStatusFilter = '';
    let currentPage = 1;
    const itemsPerPage = 6;
    
    function getSortedOrders() {
      let sorted = [...allOrdersData];
      
      if (currentStatusFilter) {
        sorted = sorted.filter(order => order.status === currentStatusFilter);
      }
      
      if (currentSortOrder === 'oldest') {
        sorted.reverse();
      }
      return sorted;
    }
    
    function renderPaginatedOrders() {
      const sorted = getSortedOrders();
      const totalItems = sorted.length;
      const totalPages = Math.ceil(totalItems / itemsPerPage);
      
      if (totalPages === 0 || totalItems === 0) {
        document.getElementById('recentOrdersBody').innerHTML = '<tr><td colspan="5">No recent orders found.</td></tr>';
        document.getElementById('paginationControls').innerHTML = '';
        return;
      }
      
      const startIndex = (currentPage - 1) * itemsPerPage;
      const endIndex = Math.min(startIndex + itemsPerPage, totalItems);
      const pageOrders = sorted.slice(startIndex, endIndex);
      
      let tableHtml = '';
      pageOrders.forEach(order => {
        tableHtml += `<tr role="button" tabindex="0" onclick="openOrderDetails(${order.order_id})" onkeypress="if(event.key==='Enter'||event.key===' '){event.preventDefault();openOrderDetails(${order.order_id});}" style="cursor:pointer;">
          <td>#${order.order_id}</td>
          <td>${order.status}</td>
          <td>${order.delivery_type}</td>
          <td>₱${formatPesoDisplay(order.total_amount)}</td>
          <td>${order.order_date}</td>
        </tr>`;
      });
      document.getElementById('recentOrdersBody').innerHTML = tableHtml;
      
      let paginationHtml = '';
      if (totalPages > 1) {
        paginationHtml += `<button onclick="goToPage(${Math.max(1, currentPage - 1)})" ${currentPage === 1 ? 'disabled' : ''} style="padding: 8px 12px; border: 1px solid #d1d5db; border-radius: 8px; background: ${currentPage === 1 ? '#f3f4f6' : '#fff'}; color: #111827; cursor: ${currentPage === 1 ? 'not-allowed' : 'pointer'}; font-size: 13px;">← Prev</button>`;
        
        for (let p = 1; p <= totalPages; p++) {
          const isActive = p === currentPage;
          paginationHtml += `<button onclick="goToPage(${p})" style="padding: 8px 12px; border: 1px solid ${isActive ? '#0f172a' : '#d1d5db'}; border-radius: 8px; background: ${isActive ? '#0f172a' : '#fff'}; color: ${isActive ? '#fff' : '#111827'}; cursor: pointer; font-size: 13px; font-weight: ${isActive ? '700' : '400'};">${p}</button>`;
        }
        
        paginationHtml += `<button onclick="goToPage(${Math.min(totalPages, currentPage + 1)})" ${currentPage === totalPages ? 'disabled' : ''} style="padding: 8px 12px; border: 1px solid #d1d5db; border-radius: 8px; background: ${currentPage === totalPages ? '#f3f4f6' : '#fff'}; color: #111827; cursor: ${currentPage === totalPages ? 'not-allowed' : 'pointer'}; font-size: 13px;">Next →</button>`;
      }
      document.getElementById('paginationControls').innerHTML = paginationHtml;
    }
    
    function goToPage(page) {
      const sorted = getSortedOrders();
      const totalPages = Math.ceil(sorted.length / itemsPerPage);
      if (page >= 1 && page <= totalPages) {
        currentPage = page;
        renderPaginatedOrders();
      }
    }
    
    function handleSortChange(value) {
      currentSortOrder = value;
      currentPage = 1;
      renderPaginatedOrders();
    }
    
    function handleStatusFilterChange(value) {
      currentStatusFilter = value;
      currentPage = 1;
      renderPaginatedOrders();
    }

    function classifyOrderAction(messageText, currentStatus) {
      const text = String(messageText || '').toLowerCase();
      if (text.includes('cancelled')) return 'Cancelled By';
      if (text.includes('received')) return 'Received By';
      if (text.includes('picked up')) return 'Hand over by';
      if (text.includes('ready for pickup') || text.includes('pickup')) return 'Ready for Pickup By';
      if (text.includes('shipped')) return 'Shipped By';
      if (text.includes('delivered')) return 'Delivered By';
      if (text.includes('processing')) return 'Processing By';
      if (text.includes('archived')) return 'Archived By';
      if (text.includes('bin')) return 'Moved To Bin By';
      return currentStatus ? `${currentStatus.charAt(0).toUpperCase() + currentStatus.slice(1)} By` : 'Updated By';
    }

    function openOrderDetails(orderId) {
      const order = allOrdersData.find(item => parseInt(item.order_id, 10) === parseInt(orderId, 10));
      if (!order) {
        return;
      }

      document.getElementById('detailOrderId').textContent = `#${order.order_id}`;
      const receivedName = ['received', 'reviewed'].includes(order.status)
        ? (order.recipient_name || order.customer_full_name || order.customer_name || `Customer #${order.user_id}`)
        : '';
      document.getElementById('detailCustomerName').textContent = receivedName || '';
      document.getElementById('detailOrderStatus').textContent = order.status.charAt(0).toUpperCase() + order.status.slice(1);
      document.getElementById('detailDeliveryType').textContent = order.delivery_type.charAt(0).toUpperCase() + order.delivery_type.slice(1);
      document.getElementById('detailOrderTotal').textContent = `₱${formatPesoDisplay(order.total_amount)}`;
      document.getElementById('detailOrderDate').textContent = order.order_date;

      const history = orderActionData[order.order_id] || [];
      const historyContainer = document.getElementById('detailActionHistory');
      if (!history.length) {
        historyContainer.innerHTML = '<div class="no-history">No admin action history recorded for this order yet.</div>';
      } else {
        const filteredHistory = history.filter(entry => {
          const label = classifyOrderAction(entry.message_text, order.status);
          if (label === 'Received By' && !['received', 'reviewed'].includes(order.status)) {
            return false;
          }
          // Exclude "Reviewed By" actions from display
          if (label === 'Reviewed By') {
            return false;
          }
          return true;
        });
        const actionMap = {};
        filteredHistory.forEach(entry => {
          const label = classifyOrderAction(entry.message_text, order.status);
          if (!actionMap[label]) {
            actionMap[label] = entry;
          }
        });
        // If the order is received/reviewed, ensure Received By is present
        if (['received', 'reviewed'].includes(order.status) && !actionMap['Received By']) {
          actionMap['Received By'] = {
            message_text: 'Order received',
            actor_name: order.recipient_name || order.customer_full_name || order.customer_name || `Customer #${order.user_id}`,
            sender_role: 'user',
            created_at: order.order_date
          };
        }
        // If the order is delivered (and not pickup), ensure Delivered By is present when the order has reached that stage
        if (order.delivery_type !== 'pickup' && ['delivered', 'received', 'reviewed'].includes(order.status) && !actionMap['Delivered By']) {
          const deliveredEntry = filteredHistory.find(entry => classifyOrderAction(entry.message_text, order.status) === 'Delivered By');
          actionMap['Delivered By'] = deliveredEntry || {
            message_text: 'Order delivered',
            actor_name: filteredHistory.find(entry => entry.sender_role === 'admin')?.actor_name || 'Unknown admin',
            sender_role: 'admin',
            created_at: order.order_date
          };
        }
        const statusOrder = ['pending', 'processing', 'shipped', 'delivered', 'received', 'pickup', 'pickedup', 'cancelled', 'reviewed'];
        // For pickup orders, use a different status order (exclude Delivered)
        const statusOrderPickup = ['pending', 'processing', 'pickup', 'pickedup', 'received', 'cancelled', 'reviewed'];
        
        const labelToStatus = {
          'Processing By': 'processing',
          'Shipped By': 'shipped',
          'Delivered By': 'delivered',
          'Received By': 'received',
          'Ready for Pickup By': 'pickup',
          'Hand over by': 'pickedup',
          'Cancelled By': 'cancelled',
          'Archived By': 'archived',
          'Moved To Bin By': 'binned'
        };
        
        const currentStatusOrder = order.delivery_type === 'pickup' ? statusOrderPickup : statusOrder;
        // Remove Delivered By from actionMap for pickup orders
        if (order.delivery_type === 'pickup' && actionMap['Delivered By']) {
          delete actionMap['Delivered By'];
        }
        
        const actions = Object.values(actionMap).sort((a, b) => {
          const labelA = classifyOrderAction(a.message_text, order.status);
          const labelB = classifyOrderAction(b.message_text, order.status);
          const statusA = labelToStatus[labelA] || 'unknown';
          const statusB = labelToStatus[labelB] || 'unknown';
          const indexA = currentStatusOrder.indexOf(statusA);
          const indexB = currentStatusOrder.indexOf(statusB);
          if (indexA !== indexB) {
            return indexA - indexB;
          }
          return new Date(a.created_at) - new Date(b.created_at);
        });
        historyContainer.innerHTML = actions.map(entry => {
          const label = classifyOrderAction(entry.message_text, order.status);
          const timestamp = entry.created_at ? new Date(entry.created_at).toLocaleString() : 'Unknown time';
          let actionName;
          if (label === 'Received By') {
            actionName = order.recipient_name || order.customer_full_name || order.customer_name || `Customer #${order.user_id}`;
          } else if (label === 'Cancelled By') {
            actionName = entry.sender_role === 'admin' ? (entry.actor_name || 'Unknown admin') : (order.customer_full_name || order.customer_name || `Customer #${order.user_id}`);
          } else {
            actionName = entry.actor_name || 'Unknown admin';
          }
          return `<div class="action-history-item">
            <strong>${label}: ${actionName}</strong>
            <small>${timestamp}</small>
            <p>${entry.message_text}</p>
          </div>`;
        }).join('');
      }

      document.getElementById('orderDetailOverlay').classList.add('open');
    }

    function closeOrderDetails() {
      document.getElementById('orderDetailOverlay').classList.remove('open');
    }

    document.getElementById('orderDetailOverlay').addEventListener('click', (event) => {
      if (event.target.id === 'orderDetailOverlay') {
        closeOrderDetails();
      }
    });

    document.addEventListener('keydown', (event) => {
      if (event.key === 'Escape') {
        closeOrderDetails();
      }
    });
    
    function formatPesoDisplay(amount) {
      const value = parseFloat(amount);
      if (Math.floor(value) === value) {
        return value.toLocaleString('en-US', {minimumFractionDigits: 0, maximumFractionDigits: 0});
      }
      return value.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2}).replace(/\.?0+$/, '');
    }
    
    document.addEventListener('DOMContentLoaded', function() {
      renderPaginatedOrders();
    });

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

    function downloadOrderStatusCsv() {
      const month = document.querySelector('select[name="month"]').value;
      const year = document.querySelector('select[name="year"]').value;
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
      if (rows.length <= 9) {
        alert('No data available for the selected period.');
        return;
      }
      const csvContent = rows.map((row) => row.map((cell) => `"${String(cell).replace(/"/g, '""')}"`).join(',')).join('\r\n');
      const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
      const url = URL.createObjectURL(blob);
      const link = document.createElement('a');
      let filename = 'owner_order_status.csv';
      if (month && year) {
        filename = `owner_order_status_${year}_${month}.csv`;
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
