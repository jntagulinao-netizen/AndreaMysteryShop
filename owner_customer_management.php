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

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['customer_action'], $_POST['customer_id'])) {
    $customerId = intval($_POST['customer_id']);
    $action = $_POST['customer_action'] === 'block' ? 'block' : ($_POST['customer_action'] === 'unblock' ? 'unblock' : '');
    if ($customerId > 0 && $action) {
        $newStatus = $action === 'block' ? 'inactive' : 'active';
        $updateStmt = $conn->prepare('UPDATE users SET status = ? WHERE user_id = ? AND LOWER(role) = "user"');
        if ($updateStmt) {
            $updateStmt->bind_param('si', $newStatus, $customerId);
            if ($updateStmt->execute()) {
                $_SESSION['customer_action_message'] = 'Customer account has been ' . ($action === 'block' ? 'blocked' : 'unblocked') . ' successfully.';
            } else {
                $_SESSION['customer_action_message'] = 'Unable to update the customer account status. Please try again.';
            }
            $updateStmt->close();
        } else {
            $_SESSION['customer_action_message'] = 'Unable to prepare account update. Please contact support.';
        }
    } else {
        $_SESSION['customer_action_message'] = 'Invalid customer action request.';
    }
    header('Location: owner_customer_management.php');
    exit;
}

$customerActionMessage = $_SESSION['customer_action_message'] ?? '';
unset($_SESSION['customer_action_message']);

$customerStatsSql = 'SELECT
  COUNT(*) AS total_customers,
  SUM(CASE WHEN created_at >= DATE_FORMAT(CURDATE(), "%Y-%m-01") THEN 1 ELSE 0 END) AS new_signups_month
  FROM users
  WHERE LOWER(role) = "user"';
$customerMetrics = ['total_customers' => 0, 'new_signups_month' => 0];
$customerResult = $conn->query($customerStatsSql);
if ($customerResult && $customerResult->num_rows > 0) {
    $customerMetrics = array_merge($customerMetrics, $customerResult->fetch_assoc());
}

$customerSignupChart = [
  ['label' => 'New This Month', 'value' => intval($customerMetrics['new_signups_month'])],
  ['label' => 'Existing Customers', 'value' => max(0, intval($customerMetrics['total_customers']) - intval($customerMetrics['new_signups_month']))]
];

$recentCustomers = [];
$recentCustomersSql = 'SELECT user_id, full_name, email, created_at, COALESCE(status, "active") AS status FROM users WHERE LOWER(role) = "user" ORDER BY created_at DESC LIMIT 10';
$recentCustomersResult = $conn->query($recentCustomersSql);
if ($recentCustomersResult) {
    while ($row = $recentCustomersResult->fetch_assoc()) {
        $recentCustomers[] = [
            'user_id' => intval($row['user_id'] ?? 0),
            'full_name' => $row['full_name'] ?? '',
            'email' => $row['email'] ?? '',
            'created_at' => $row['created_at'] ?? '',
            'status' => $row['status'] ?? 'active'
        ];
    }
}
?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Owner Customer Management</title>
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
    .stat-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 14px; margin-bottom: 18px; }
    .stat-card { background: #fff; border: 1px solid #e5e7eb; border-radius: 16px; padding: 18px; box-shadow: 0 10px 24px rgba(15, 23, 42, 0.06); }
    .stat-card strong { display: block; margin-bottom: 6px; font-size: 24px; }
    .stat-card span { color: #475569; font-size: 13px; }
    .section-card { background: #fff; border: 1px solid #e5e7eb; border-radius: 18px; padding: 20px; box-shadow: 0 10px 24px rgba(15, 23, 42, 0.04); margin-bottom: 18px; }
    .section-card h2 { margin: 0 0 12px; font-size: 18px; color: #0f172a; }
    .section-actions { display: flex; justify-content: space-between; gap: 10px; align-items: center; margin-bottom: 16px; }
    .chart-pie { display: flex; flex-wrap: wrap; gap: 20px; align-items: center; justify-content: space-between; padding: 14px 0; }
    .modal-overlay { position: fixed; inset: 0; background: rgba(15, 23, 42, 0.55); display: grid; place-items: center; padding: 16px; z-index: 200; }
    .modal-card { width: min(720px, 100%); background: #fff; border-radius: 24px; padding: 28px; box-shadow: 0 24px 60px rgba(15, 23, 42, 0.25); border: 1px solid rgba(226, 232, 240, 0.8); position: relative; }
    .modal-header { display: flex; align-items: start; justify-content: space-between; gap: 16px; margin-bottom: 22px; }
    .modal-header h2 { margin: 0; font-size: 22px; color: #111827; }
    .modal-close { background: transparent; border: none; font-size: 28px; line-height: 1; color: #64748b; cursor: pointer; font-weight: 700; }
    .detail-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 18px; }
    .detail-grid strong { display: block; margin-bottom: 8px; color: #111827; }
    .detail-grid p { margin: 0; color: #334155; }
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
    .topbar-menu { position: relative; }
    .menu-trigger { border: 1px solid #d1d5db; border-radius: 12px; background: #fff; color: #111827; padding: 10px 14px; cursor: pointer; }
    .menu-dropdown { position: absolute; top: calc(100% + 8px); right: 0; background: #fff; border: 1px solid #e2e8f0; border-radius: 14px; box-shadow: 0 16px 35px rgba(15, 23, 42, 0.12); display: none; min-width: 210px; z-index: 100; }
    .menu-dropdown.active { display: block; }
    .menu-dropdown a { display: block; padding: 10px 12px; color: #111827; text-decoration: none; border-bottom: 1px solid #f1f5f9; font-size: 13px; }
    .menu-dropdown a:last-child { border-bottom: none; }
    .menu-dropdown a:hover { background: #f8fafc; }
    .notification-banner { border-left: 4px solid #2563eb; margin-bottom: 20px; padding: 16px 18px; background: #eff6ff; border-radius: 16px; box-shadow: 0 10px 25px rgba(37, 99, 235, 0.08); animation: banner-pop 0.5s ease forwards; }
    .notification-banner p { margin: 0; color: #111827; font-weight: 700; }
    .notification-banner.flash { animation: banner-pop 0.5s ease forwards; }
    @keyframes banner-pop {
      0% { transform: scale(0.98); opacity: 0.8; }
      50% { transform: scale(1.02); opacity: 1; }
      100% { transform: scale(1); opacity: 1; }
    }
  </style>
</head>
<body>
  <div class="page-header">
    <a class="back-arrow" href="owner_administrative_page.php">←</a>
    <div class="header-content">
      <div class="header-title">Customer Management</div>
      <div class="header-meta">Manage customer metrics and most recent user signups.</div>
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
      <h1>Customer Management</h1>
      <p>Track total buyers and new customer growth with a dedicated exportable customer report.</p>
      <div class="actions">
        <a class="btn primary" href="owner_customer_management.php" onclick="event.preventDefault(); downloadCustomerCsv(event)">Export Customer CSV</a>
        <a class="btn" href="owner_administrative_page.php">Back to Overview</a>
      </div>
    </div>

    <?php if ($customerActionMessage): ?>
      <div class="notification-banner" id="customerNotification">
        <p><?php echo htmlspecialchars($customerActionMessage); ?></p>
      </div>
    <?php endif; ?>

    <div class="stat-grid">
      <div class="stat-card"><strong><?= intval($customerMetrics['total_customers']) ?></strong><span>Total Customers</span></div>
      <div class="stat-card"><strong><?= intval($customerMetrics['new_signups_month']) ?></strong><span>New This Month</span></div>
    </div>

    <section class="section-card">
      <div class="section-actions"><div><h2>Customer Growth Mix</h2><div style="color:#64748b;font-size:13px;">Breakdown of new vs existing customers for the current pool.</div></div></div>
      <div id="customerSignupChartArea" class="chart-pie"></div>
      <div class="chart-label" id="customerSignupChartSubtitle"></div>
    </section>

    <section class="section-card">
      <div class="section-actions"><div><h2>Recent Customer Signups</h2></div></div>
      <div class="table-wrap">
        <table>
          <thead>
            <tr><th>Name</th><th>Email</th><th>Joined</th><th>Status</th><th>Action</th></tr>
          </thead>
          <tbody>
            <?php foreach ($recentCustomers as $customer): ?>
              <tr>
                <td><?= htmlspecialchars($customer['full_name']) ?></td>
                <td><?= htmlspecialchars($customer['email']) ?></td>
                <td><?= htmlspecialchars($customer['created_at']) ?></td>
                <td><?= htmlspecialchars(ucfirst($customer['status'])) ?></td>
                <td><button class="btn" type="button" onclick="showCustomerDetails(<?= $customer['user_id'] ?>)">View Details</button></td>
              </tr>
            <?php endforeach; ?>
            <?php if (empty($recentCustomers)): ?>
              <tr><td colspan="5">No recent customer signups found.</td></tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </section>

  </div>

  <div class="modal-overlay" id="customerDetailOverlay" style="display:none;">
    <div class="modal-card" role="dialog" aria-modal="true" aria-labelledby="detailTitle">
      <div class="modal-header">
        <div>
          <h2 id="detailTitle">Customer Details</h2>
          <p id="detailSubtitle" style="color:#64748b;font-size:13px;margin:6px 0 0;">Manage this customer account and block or restore access as needed.</p>
        </div>
        <button class="modal-close" type="button" onclick="hideCustomerDetails()">×</button>
      </div>
      <div class="detail-grid">
        <div><strong>Name</strong><p id="detailName"></p></div>
        <div><strong>Email</strong><p id="detailEmail"></p></div>
        <div><strong>Joined</strong><p id="detailJoined"></p></div>
        <div><strong>Status</strong><p id="detailStatus"></p></div>
      </div>
      <div class="actions" style="margin-top:24px;justify-content:flex-end;">
        <form id="customerActionForm" method="post" style="display:flex;gap:10px;flex-wrap:wrap;">
          <input type="hidden" name="customer_id" id="detailCustomerId" value="">
          <input type="hidden" name="customer_action" id="detailCustomerAction" value="">
          <button class="btn warn" id="detailActionButton" type="submit">Block Account</button>
          <button class="btn" type="button" onclick="hideCustomerDetails()">Close</button>
        </form>
      </div>
    </div>
  </div>

  <script>
    const chartData = <?php echo json_encode(['customerSignupChart' => $customerSignupChart, 'customers' => $recentCustomers], JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT); ?>;
    const chartColors = ['#2563eb', '#22c55e'];

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
        const percentage = (item.value / total) * 100;
        const color = chartColors[index % chartColors.length];
        const slice = `${color} ${offset}% ${offset + percentage}%`;
        offset += percentage;
        return slice;
      });
      const pie = document.createElement('div');
      pie.className = 'pie-chart';
      const inner = document.createElement('div');
      inner.className = 'pie-chart-inner';
      inner.style.background = `conic-gradient(${slices.join(', ')})`;
      const center = document.createElement('div');
      center.className = 'pie-chart-center';
      center.innerHTML = `<strong>${total}</strong><span>Total</span>`;
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
        const labelText = document.createElement('span');
        labelText.textContent = `${item.label}: ${item.value}`;
        entry.appendChild(dot);
        entry.appendChild(labelText);
        legend.appendChild(entry);
      });
      chartArea.appendChild(pie);
      chartArea.appendChild(legend);
    }

    function renderCustomerChart() {
      renderPieChart('customerSignupChartArea', 'customerSignupChartSubtitle', chartData.customerSignupChart, 'New vs existing customer mix.');
    }

    function showCustomerDetails(userId) {
      const customer = chartData.customers.find(item => parseInt(item.user_id, 10) === parseInt(userId, 10));
      if (!customer) {
        return;
      }
      document.getElementById('customerDetailOverlay').style.display = 'grid';
      document.getElementById('detailName').textContent = customer.full_name;
      document.getElementById('detailEmail').textContent = customer.email;
      document.getElementById('detailJoined').textContent = customer.created_at;
      document.getElementById('detailStatus').textContent = customer.status.charAt(0).toUpperCase() + customer.status.slice(1);
      document.getElementById('detailCustomerId').value = customer.user_id;
      const nextAction = customer.status.toLowerCase() === 'inactive' ? 'unblock' : 'block';
      document.getElementById('detailCustomerAction').value = nextAction;
      const actionButton = document.getElementById('detailActionButton');
      actionButton.textContent = nextAction === 'block' ? 'Block Account' : 'Unblock Account';
      actionButton.className = nextAction === 'block' ? 'btn warn' : 'btn primary';
      document.getElementById('detailSubtitle').textContent = 'Manage this customer account and block or restore access as needed.';
    }

    function hideCustomerDetails() {
      document.getElementById('customerDetailOverlay').style.display = 'none';
    }

    renderCustomerChart();
    const notificationElement = document.getElementById('customerNotification');
    if (notificationElement) {
      notificationElement.classList.add('flash');
      setTimeout(() => {
        notificationElement.classList.remove('flash');
      }, 1200);
    }

    function downloadCustomerCsv(event) {
      event.preventDefault();
      const rows = [
        ['Customer Management'],
        [],
        ['Metric', 'Value'],
        ['Total Customers', <?= intval($customerMetrics['total_customers']) ?>],
        ['New Signups This Month', <?= intval($customerMetrics['new_signups_month']) ?>],
        [],
        ['Name', 'Email', 'Joined']
      ];
      <?php foreach ($recentCustomers as $customer): ?>
        rows.push(['<?= addslashes($customer['full_name']) ?>', '<?= addslashes($customer['email']) ?>', '<?= addslashes($customer['created_at']) ?>']);
      <?php endforeach; ?>
      const csvContent = rows.map((row) => row.map((cell) => `"${String(cell).replace(/"/g, '""')}"`).join(',')).join('\r\n');
      const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
      const url = URL.createObjectURL(blob);
      const link = document.createElement('a');
      link.href = url;
      link.download = 'owner_customer_management.csv';
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
