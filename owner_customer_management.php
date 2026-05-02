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
$selectedTab = $_GET['tab'] ?? 'recent';

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
        $updateStmt = $conn->prepare('UPDATE users SET status = ? WHERE user_id = ? AND (LOWER(role) = "user" OR LOWER(role) = "admin")');
        if ($updateStmt) {
            $updateStmt->bind_param('si', $newStatus, $customerId);
            if ($updateStmt->execute()) {
                $_SESSION['customer_action_message'] = 'Account has been ' . ($action === 'block' ? 'blocked' : 'unblocked') . ' successfully.';
            } else {
                $_SESSION['customer_action_message'] = 'Unable to update the account status. Please try again.';
            }
            $updateStmt->close();
        } else {
            $_SESSION['customer_action_message'] = 'Unable to prepare account update. Please contact support.';
        }
    } else {
        $_SESSION['customer_action_message'] = 'Invalid account action request.';
    }
    header('Location: owner_customer_management.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['make_admin_id'])) {
    $makeAdminId = intval($_POST['make_admin_id']);
    if ($makeAdminId > 0) {
        $roleStmt = $conn->prepare('UPDATE users SET role = "admin" WHERE user_id = ? AND LOWER(role) = "user"');
        if ($roleStmt) {
            $roleStmt->bind_param('i', $makeAdminId);
            if ($roleStmt->execute()) {
                $_SESSION['customer_action_message'] = 'Customer account has been converted to admin successfully.';
            } else {
                $_SESSION['customer_action_message'] = 'Unable to convert customer to admin. Please try again.';
            }
            $roleStmt->close();
        } else {
            $_SESSION['customer_action_message'] = 'Unable to prepare admin conversion. Please contact support.';
        }
    } else {
        $_SESSION['customer_action_message'] = 'Invalid admin conversion request.';
    }
    header('Location: owner_customer_management.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['make_user_id'])) {
    $makeUserId = intval($_POST['make_user_id']);
    if ($makeUserId > 0) {
        $roleStmt = $conn->prepare('UPDATE users SET role = "user", is_owner = 0 WHERE user_id = ? AND LOWER(role) = "admin"');
        if ($roleStmt) {
            $roleStmt->bind_param('i', $makeUserId);
            if ($roleStmt->execute()) {
                $_SESSION['customer_action_message'] = 'Admin account has been demoted to user successfully.';
            } else {
                $_SESSION['customer_action_message'] = 'Unable to demote admin to user. Please try again.';
            }
            $roleStmt->close();
        } else {
            $_SESSION['customer_action_message'] = 'Unable to prepare demotion. Please contact support.';
        }
    } else {
        $_SESSION['customer_action_message'] = 'Invalid demotion request.';
    }
    header('Location: owner_customer_management.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['make_owner_id'])) {
    $makeOwnerId = intval($_POST['make_owner_id']);
    if ($makeOwnerId > 0) {
        $targetStmt = $conn->prepare('SELECT user_id FROM users WHERE user_id = ? AND LOWER(role) = "admin" LIMIT 1');
        if ($targetStmt) {
            $targetStmt->bind_param('i', $makeOwnerId);
            $targetStmt->execute();
            $targetResult = $targetStmt->get_result();
            if ($targetResult && $targetResult->num_rows > 0) {
                $_SESSION['owner_new_pin_target_id'] = $makeOwnerId;
                $_SESSION['owner_reset_mode'] = true;
                $_SESSION['owner_reset_verified'] = true;
                $targetStmt->close();
                header('Location: owner_new_pin.php');
                exit;
            }
            $targetStmt->close();
        }
    }
    $_SESSION['customer_action_message'] = 'Unable to set this admin account as owner. Please try again.';
    header('Location: owner_customer_management.php');
    exit;
}

$customerActionMessage = $_SESSION['customer_action_message'] ?? '';
unset($_SESSION['customer_action_message']);

$whereClause = '';
$caseCondition = "created_at >= DATE_FORMAT(CURDATE(), '%Y-%m-01')";
if (!empty($selectedMonth) && !empty($selectedYear)) {
    $whereClause = " AND YEAR(created_at) = $selectedYear AND MONTH(created_at) = $selectedMonth";
    $monthStart = sprintf('%04d-%02d-01', $selectedYear, $selectedMonth);
    $caseCondition = "created_at >= '$monthStart'";
}

$customerStatsSql = 'SELECT
  COUNT(*) AS total_customers,
  SUM(CASE WHEN ' . $caseCondition . ' THEN 1 ELSE 0 END) AS new_signups_month
  FROM users
  WHERE LOWER(role) = "user"' . $whereClause;
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
$roleCondition = $selectedTab === 'admins' ? "LOWER(role) = 'admin'" : "LOWER(role) = 'user'";
$recentCustomersSql = 'SELECT user_id, full_name, email, created_at, LOWER(role) AS role, COALESCE(status, "active") AS status, COALESCE(is_owner, 0) AS is_owner FROM users WHERE ' . $roleCondition . ' AND user_id != ? AND email != \'andreamysteryshop@gmail.com\'' . $whereClause;
if ($selectedTab === 'recent') {
    $recentCustomersSql .= ' AND created_at >= DATE_SUB(CURDATE(), INTERVAL 15 DAY)';
} elseif ($selectedTab === 'all') {
    // No additional condition
} elseif ($selectedTab === 'admins') {
    // Show all admin accounts matching the selected filters
}
$recentCustomersSql .= ' ORDER BY created_at DESC';
if ($selectedTab === 'recent') {
    $recentCustomersSql .= ' LIMIT 50'; // Show more for recent
} else {
    $recentCustomersSql .= ' LIMIT 100'; // Paginate or limit for all
}
$recentCustomersResult = $conn->prepare($recentCustomersSql);
$recentCustomersResult->bind_param('i', $userId);
$recentCustomersResult->execute();
$recentCustomersResult = $recentCustomersResult->get_result();
if ($recentCustomersResult) {
    while ($row = $recentCustomersResult->fetch_assoc()) {
        $recentCustomers[] = [
            'user_id' => intval($row['user_id'] ?? 0),
            'full_name' => $row['full_name'] ?? '',
            'email' => $row['email'] ?? '',
            'created_at' => $row['created_at'] ?? '',
            'status' => $row['status'] ?? 'active',
            'role' => $row['role'] ?? 'user',
            'is_owner' => intval($row['is_owner'] ?? 0)
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
    body { margin: 0; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f3f4f6; padding-bottom: 578px; }
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
    .chart-pie { display: flex; flex-wrap: wrap; gap: 20px; align-items: center; justify-content: center; padding: 14px 0; }
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

    .tabs { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 10px; margin-bottom: 16px; }
    .tabs a.btn { width: 100%; }

    @media (max-width: 720px) {
      .modal-card { width: calc(100% - 32px); max-width: 100%; border-radius: 20px; padding: 18px; }
      .modal-header { flex-direction: column; align-items: stretch; }
      .detail-grid { grid-template-columns: 1fr; }
      .modal-card .btn { width: 100%; }
      .modal-card .actions { width: 100%; display: flex; flex-direction: column; align-items: stretch; }
      .modal-card .actions .btn { width: 100%; }
    }
  </style>
</head>
<body>
  <div class="page-header">
    <a class="back-arrow" href="owner_administrative_page.php">‹</a>
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
      <h1>Customer Management</h1>
      <p>Track total buyers and new customer growth with a dedicated exportable customer report.</p>
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
          <button type="button" class="btn primary" onclick="downloadCustomerCsv()">Export Customer CSV</button>
          <a class="btn" href="owner_administrative_page.php">Back to Overview</a>
        </div>
      </form>
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
      <div class="section-actions"><div><h2>Customer List</h2></div></div>
      <div class="tabs">
        <a href="?tab=recent<?= $selectedMonth ? '&month=' . $selectedMonth : '' ?><?= $selectedYear ? '&year=' . $selectedYear : '' ?>" class="btn<?= $selectedTab === 'recent' ? ' primary' : '' ?>" style="text-decoration: none;">Recent Signups (15 days)</a>
        <a href="?tab=all<?= $selectedMonth ? '&month=' . $selectedMonth : '' ?><?= $selectedYear ? '&year=' . $selectedYear : '' ?>" class="btn<?= $selectedTab === 'all' ? ' primary' : '' ?>" style="text-decoration: none;">All Customers</a>
        <a href="?tab=admins<?= $selectedMonth ? '&month=' . $selectedMonth : '' ?><?= $selectedYear ? '&year=' . $selectedYear : '' ?>" class="btn<?= $selectedTab === 'admins' ? ' primary' : '' ?>" style="text-decoration: none;">Admin Accounts</a>
      </div>
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
                <td><?php
                  $status = ucfirst($customer['status']);
                  if ($customer['role'] === 'admin') {
                    $status = 'Admin' . ($customer['is_owner'] ? ' (Owner)' : '');
                  }
                  echo htmlspecialchars($status);
                ?></td>
                <td><button class="btn" type="button" onclick="showCustomerDetails(<?= $customer['user_id'] ?>)">View Details</button></td>
              </tr>
            <?php endforeach; ?>
            <?php if (empty($recentCustomers)): ?>
              <tr><td colspan="5">No customers found.</td></tr>
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
      <div class="actions" style="margin-top:24px;justify-content:flex-end;gap:10px;flex-wrap:wrap;">
        <form id="customerActionForm" method="post" style="display:flex;gap:10px;flex-wrap:wrap;">
          <input type="hidden" name="customer_id" id="detailCustomerId" value="">
          <input type="hidden" name="customer_action" id="detailCustomerAction" value="">
          <button class="btn warn" id="detailActionButton" type="button" onclick="confirmAction('customerActionForm', 'Are you sure you want to ' + (document.getElementById('detailCustomerAction').value === 'block' ? 'block' : 'unblock') + ' this account?')">Block Account</button>
        </form>
        <form id="makeAdminForm" method="post" style="display:none;align-items:center;">
          <input type="hidden" name="make_admin_id" id="makeAdminId" value="">
          <button class="btn" type="button" onclick="confirmAction('makeAdminForm', 'Are you sure you want to promote this customer to admin?')" id="makeAdminButton">Switch to Admin</button>
        </form>
        <form id="makeUserForm" method="post" style="display:none;align-items:center;">
          <input type="hidden" name="make_user_id" id="makeUserId" value="">
          <button class="btn warn" type="button" onclick="confirmAction('makeUserForm', 'Are you sure you want to demote this admin to user?')" id="makeUserButton">Demote to User</button>
        </form>
        <form id="makeOwnerForm" method="post" style="display:none;align-items:center;">
          <input type="hidden" name="make_owner_id" id="makeOwnerId" value="">
          <button class="btn primary" type="button" onclick="confirmAction('makeOwnerForm', 'Are you sure you want to make this admin an owner?')" id="makeOwnerButton">Make Owner</button>
        </form>
        <button class="btn" type="button" onclick="hideCustomerDetails()">Close</button>
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
      const actionButton = document.getElementById('detailActionButton');
      const actionForm = document.getElementById('customerActionForm');
      const makeAdminForm = document.getElementById('makeAdminForm');
      const makeUserForm = document.getElementById('makeUserForm');
      const makeOwnerForm = document.getElementById('makeOwnerForm');
      const makeAdminIdInput = document.getElementById('makeAdminId');
      const makeUserIdInput = document.getElementById('makeUserId');
      const makeOwnerIdInput = document.getElementById('makeOwnerId');

      actionForm.style.display = 'flex';
      const nextAction = customer.status.toLowerCase() === 'inactive' ? 'unblock' : 'block';
      document.getElementById('detailCustomerAction').value = nextAction;
      actionButton.textContent = nextAction === 'block' ? 'Block Account' : 'Unblock Account';
      actionButton.className = nextAction === 'block' ? 'btn warn' : 'btn primary';

      if (customer.role === 'admin') {
        makeAdminForm.style.display = 'none';
        makeUserForm.style.display = 'flex';
        makeUserIdInput.value = customer.user_id;
        if (customer.is_owner === 1) {
          makeOwnerForm.style.display = 'none';
          document.getElementById('detailSubtitle').textContent = 'This admin account is an owner. You can demote to user.';
        } else {
          makeOwnerForm.style.display = 'flex';
          makeOwnerIdInput.value = customer.user_id;
          document.getElementById('detailSubtitle').textContent = 'This admin can be promoted to owner or demoted to user.';
        }
      } else {
        makeOwnerForm.style.display = 'none';
        makeUserForm.style.display = 'none';
        if (customer.role === 'user') {
          makeAdminForm.style.display = 'flex';
          makeAdminIdInput.value = customer.user_id;
          document.getElementById('detailSubtitle').textContent = 'This customer can be promoted to admin.';
        } else {
          makeAdminForm.style.display = 'none';
          document.getElementById('detailSubtitle').textContent = 'Manage this account and block or restore access as needed.';
        }
      }
    }

    function hideCustomerDetails() {
      document.getElementById('customerDetailOverlay').style.display = 'none';
    }

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
      return openLocalSweetAlert({ type: 'warning', title, text, confirmText, cancelText, showCancel: true });
    }

    async function confirmAction(formId, message) {
      const confirmed = await showLocalConfirm('Confirm Action', message, 'Yes', 'No');
      if (confirmed) {
        document.getElementById(formId).submit();
      }
    }

    renderCustomerChart();
    const notificationElement = document.getElementById('customerNotification');
    if (notificationElement) {
      notificationElement.classList.add('flash');
      setTimeout(() => {
        notificationElement.classList.remove('flash');
      }, 1200);
    }

    function downloadCustomerCsv() {
      const month = document.querySelector('select[name="month"]').value;
      const year = document.querySelector('select[name="year"]').value;
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
      if (rows.length <= 7) {
        alert('No data available for the selected period.');
        return;
      }
      const csvContent = rows.map((row) => row.map((cell) => `"${String(cell).replace(/"/g, '""')}"`).join(',')).join('\r\n');
      const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
      const url = URL.createObjectURL(blob);
      const link = document.createElement('a');
      let filename = 'owner_customer_management.csv';
      if (month && year) {
        filename = `owner_customer_management_${year}_${month}.csv`;
      }
      if ('<?= $selectedTab ?>' === 'recent') {
        filename = filename.replace('.csv', '_recent.csv');
      } else if ('<?= $selectedTab ?>' === 'admins') {
        filename = filename.replace('.csv', '_admins.csv');
      } else {
        filename = filename.replace('.csv', '_all.csv');
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
      <a href="owner_customer_management.php" class="active">
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
