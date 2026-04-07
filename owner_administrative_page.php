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
?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Owner Administrative Page - Mock</title>
  <link rel="stylesheet" href="main.css">
  <style>
    * { box-sizing: border-box; }
    body { margin: 0; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f3f4f6; padding-bottom: 78px; }

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
      margin-bottom: 12px;
    }
    .hero h1 { margin: 0 0 6px; font-size: 24px; }
    .hero p { margin: 0; color: #e5e7eb; }
    .actions { margin-top: 12px; display: flex; gap: 8px; flex-wrap: wrap; }
    .btn { border: 1px solid #d1d5db; background: #fff; color: #1f2937; border-radius: 8px; padding: 10px 12px; font-weight: 700; text-decoration: none; }
    .btn.warn { background: #ef4444; color: #fff; border-color: #ef4444; }

    .grid {
      display: grid;
      grid-template-columns: repeat(3, minmax(0, 1fr));
      gap: 10px;
    }
    .tile {
      background: #fff;
      border: 1px solid #e5e7eb;
      border-radius: 12px;
      padding: 14px;
      min-height: 120px;
    }
    .tile h3 { margin: 0 0 8px; font-size: 16px; color: #111827; }
    .tile p { margin: 0; color: #4b5563; font-size: 13px; line-height: 1.4; }

    .mobile-bottom-nav { position: fixed; bottom: 0; left: 0; right: 0; z-index: 999; background: #fff; border-top: 1px solid #ddd; }
    .mobile-bottom-nav.fixed { display: flex; }
    .mobile-nav-inner { display: flex; justify-content: space-around; align-items: center; padding: 0 6px; width: 100%; height: 50px; }
    .mobile-nav-inner a { text-decoration: none; color: #555; font-size: 11px; display: flex; flex-direction: column; align-items: center; gap: 4px; }
    .mobile-nav-inner a svg { width: 20px; height: 20px; stroke-width: 1.5; }
    .mobile-nav-inner a.active { color: #e22a39; }

    @media (max-width: 900px) {
      .grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
    }
    @media (max-width: 768px) {
      .page-header { top: 8px; width: calc(100% - 24px); }
      .wrap { width: calc(100% - 24px); padding-top: 74px; }
      .header-meta { display: none; }
      .grid { grid-template-columns: 1fr; }
    }
  </style>
</head>
<body>
  <div class="page-header">
    <div class="back-arrow" onclick="window.location.href='admin_profile.php'">‹</div>
    <div class="header-title">Owner Administrative</div>
    <div class="header-meta">Updated <?php echo date('d/m/Y H:i:s'); ?></div>
    <div class="topbar-menu">
      <button type="button" class="menu-trigger" onclick="toggleTopbarMenu(event)">...</button>
      <div class="menu-dropdown" id="topbarMenuDropdown">
        <a href="admin_dashboard.php">Admin Dashboard</a>
        <a href="messages.php">Messages</a>
        <a href="admin_orders.php">Admin Orders</a>
        <a href="admin_my_products.php">My Products</a>
        <a href="admin_product_drafts.php">Product Drafts</a>
        <a href="admin_my_products.php?view=archived">Archived Products</a>
        <a href="admin_manage_reviews.php">Manage Reviews</a>
        <a href="admin_profile.php">Admin Profile</a>
        <a href="logout.php">Logout</a>
      </div>
    </div>
  </div>

  <div class="wrap">
    <section class="hero">
      <h1>Owner Administrative Page (Mock)</h1>
      <p>This is a placeholder landing page for owner-only administrative controls.</p>
      <div class="actions">
        <a class="btn" href="admin_profile.php">Back to Admin Profile</a>
        <a class="btn" href="admin_dashboard.php">Back to Dashboard</a>
        <a class="btn warn" href="owner_administrative_page.php?lock=1">Lock Owner Access</a>
      </div>
    </section>

    <section class="grid">
      <article class="tile">
        <h3>System Health</h3>
        <p>Mock card: monitor uptime, queue health, and service-level alerts for critical platform functions.</p>
      </article>
      <article class="tile">
        <h3>Admin Governance</h3>
        <p>Mock card: review administrator actions, role grants, and sensitive-operation audit entries.</p>
      </article>
      <article class="tile">
        <h3>Financial Overview</h3>
        <p>Mock card: high-level revenue snapshots and reconciliation statuses for executive review.</p>
      </article>
      <article class="tile">
        <h3>Catalog Controls</h3>
        <p>Mock card: owner-only overrides for catalog publishing, moderation, and emergency product holds.</p>
      </article>
      <article class="tile">
        <h3>Support Escalations</h3>
        <p>Mock card: urgent customer or order cases requiring owner-level intervention and resolution.</p>
      </article>
      <article class="tile">
        <h3>Compliance Center</h3>
        <p>Mock card: privacy, policy, and incident review placeholders for future compliance workflows.</p>
      </article>
    </section>
  </div>

  <nav class="mobile-bottom-nav fixed">
    <div class="mobile-nav-inner">
      <a href="admin_dashboard.php">
        <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M3 10.5L12 3l9 7.5V21a1 1 0 0 1-1 1h-5v-7H9v7H4a1 1 0 0 1-1-1V10.5z" stroke-linecap="round" stroke-linejoin="round"></path></svg>
        <span>Home</span>
      </a>
      <a href="admin_orders.php">
        <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M21 8V7a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v1"></path><rect x="3" y="8" width="18" height="11" rx="2" ry="2"></rect></svg>
        <span>Orders</span>
      </a>
      <a href="admin_my_products.php">
        <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M3 7l9-4 9 4-9 4-9-4z"></path><path d="M3 17l9 4 9-4"></path><path d="M3 12l9 4 9-4"></path></svg>
        <span>Products</span>
      </a>
      <a href="messages.php">
        <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path></svg>
        <span>Messages</span>
      </a>
      <a href="admin_profile.php" class="active">
        <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M12 12c2.5 0 4.5-2 4.5-4.5S14.5 3 12 3 7.5 5 7.5 7.5 9.5 12 12 12z"></path><path d="M4 21c0-4.5 4-8 8-8s8 3.5 8 8"></path></svg>
        <span>Profile</span>
      </a>
    </div>
  </nav>

  <script>
    function toggleTopbarMenu(event) {
      event.stopPropagation();
      const dropdown = document.getElementById('topbarMenuDropdown');
      if (dropdown) {
        dropdown.classList.toggle('active');
      }
    }

    document.addEventListener('click', (event) => {
      const dropdown = document.getElementById('topbarMenuDropdown');
      const menu = document.querySelector('.topbar-menu');
      if (dropdown && menu && !menu.contains(event.target)) {
        dropdown.classList.remove('active');
      }
    });
  </script>
</body>
</html>
