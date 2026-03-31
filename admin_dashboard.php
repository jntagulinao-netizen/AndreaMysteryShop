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

$metrics = [
  'pending_orders' => 0,
  'processing_orders' => 0,
  'shipped_orders' => 0,
  'revenue' => 0.0
];

$metricSql = 'SELECT
  SUM(CASE WHEN status = "pending" THEN 1 ELSE 0 END) AS pending_orders,
  SUM(CASE WHEN status = "processing" THEN 1 ELSE 0 END) AS processing_orders,
  SUM(CASE WHEN status = "shipped" THEN 1 ELSE 0 END) AS shipped_orders,
  SUM(CASE WHEN status IN ("delivered", "received", "reviewed") THEN total_amount ELSE 0 END) AS revenue
  FROM orders';
$metricResult = $conn->query($metricSql);
if ($metricResult && $metricResult->num_rows > 0) {
  $metrics = array_merge($metrics, $metricResult->fetch_assoc());
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
  <title>Admin Dashboard - Andrea Mystery Shop</title>
  <link rel="stylesheet" href="main.css">
  <style>
    * { margin: 0; padding: 0; box-sizing: border-box; }
    html, body { margin: 0; padding: 0; }
    body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f5f5f5; padding-bottom: 78px; }

    .page-container { width: calc(100% - 48px); max-width: none; margin: 0 auto; padding: 16px 0; }

    .page-header {
      position: sticky;
      top: 0;
      background: #fff;
      z-index: 120;
      display: flex;
      align-items: center;
      gap: 12px;
      padding: 12px 16px;
      border-radius: 12px;
      margin-bottom: 8px;
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

    .stats-strip {
      display: grid;
      grid-template-columns: repeat(4, 1fr);
      gap: 8px;
      margin-bottom: 12px;
    }
    .stats-card {
      background: #fff;
      border-radius: 10px;
      padding: 10px 8px;
      border: 1px solid #ececec;
      text-align: center;
    }
    .stats-card.clickable { cursor: pointer; }
    .stats-card.clickable:hover { border-color: #e22a39; }
    .stats-value { font-size: 18px; font-weight: 700; color: #333; }
    .stats-label { font-size: 12px; color: #777; margin-top: 2px; }

    .status-card {
      background: #fff;
      border: 1px solid #f0e9e5;
      border-radius: 12px;
      color: #333;
      padding: 14px;
      cursor: pointer;
      transition: transform .2s ease, box-shadow .2s ease;
      display: flex;
      flex-direction: column;
      align-items: center;
      text-align: center;
    }
    .status-card:hover { transform: translateY(-2px); box-shadow: 0 4px 16px rgba(0,0,0,.08); }
    .status-card svg { width: 32px; height: 32px; margin-bottom: 8px; color: #e22a39; stroke-width: 1.5; }
    .status-card strong { display: block; font-size: 16px; margin-bottom: 6px; }
    .status-card span { font-size: 12px; color: #777; }
    .view-history { margin: 8px 0 12px; }

    .mobile-bottom-nav { position: fixed; bottom: 0; left: 0; right: 0; z-index: 999; background: #fff; border-top: 1px solid #ddd; }
    .mobile-bottom-nav.fixed { display: flex; }
    .mobile-nav-inner { display: flex; justify-content: space-around; align-items: center; padding: 0 6px; width: 100%; height: 50px; }
    .mobile-nav-inner a { text-decoration: none; color: #555; font-size: 11px; display: flex; flex-direction: column; align-items: center; gap: 4px; position: relative; }
    .mobile-nav-inner a svg { width: 20px; height: 20px; stroke-width: 1.5; }
    .mobile-nav-inner a.active { color: #e22a39; }
    .mobile-nav-msg-badge {
      position: absolute;
      top: -4px;
      right: -6px;
      min-width: 16px;
      height: 16px;
      border-radius: 999px;
      background: #e22a39;
      color: #fff;
      border: 1px solid #fff;
      font-size: 10px;
      font-weight: 700;
      line-height: 1;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      padding: 0 4px;
    }
    

    @media (max-width: 768px) {
      .page-container { width: calc(100% - 24px); }
      .stats-strip { grid-template-columns: repeat(2, 1fr); }
    }
  </style>
</head>
<body>
  <div class="page-container">
    <div class="page-header">
      <div class="header-title">Admin Dashboard</div>
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
        
          <a href="logout.php">Logout</a>
        </div>
      </div>
    </div>

    <div class="stats-strip">
      <div class="stats-card clickable" onclick="window.location.href='admin_orders.php?status=pending'"><div class="stats-value"><?php echo intval($metrics['pending_orders'] ?? 0); ?></div><div class="stats-label">To Process</div></div>
      <div class="stats-card clickable" onclick="window.location.href='admin_orders.php?status=processing'"><div class="stats-value"><?php echo intval($metrics['processing_orders'] ?? 0); ?></div><div class="stats-label">Shipping Prep</div></div>
      <div class="stats-card clickable" onclick="window.location.href='admin_orders.php?status=shipped'"><div class="stats-value"><?php echo intval($metrics['shipped_orders'] ?? 0); ?></div><div class="stats-label">In Transit</div></div>
      <div class="stats-card"><div class="stats-value">₱<?php echo format_peso_display($metrics['revenue'] ?? 0); ?></div><div class="stats-label">Revenue</div></div>
    </div>

    <div class="status-card view-history" onclick="location.href='admin_orders.php'">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"><path d="M3 12a9 9 0 0 1 9-9 9.75 9.75 0 0 1 6.74 2.74L21 8"/><path d="M21 3v5h-5"/><path d="M21 12a9 9 0 0 1-9 9 9.75 9.75 0 0 1-6.74-2.74L3 16"/><path d="M3 21v-5h5"/></svg>
      <strong>View Admin Orders</strong>
      <span>See all orders and statuses</span>
    </div>

    <div class="status-card view-history" onclick="location.href='admin_add_product.php'">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"><path d="M12 5v14"/><path d="M5 12h14"/><rect x="3" y="3" width="18" height="18" rx="2" ry="2"/></svg>
      <strong>Add Product</strong>
      <span>Create product, upload up to 8 images and 1 video</span>
    </div>
  </div>

  <nav class="mobile-bottom-nav fixed">
    <div class="mobile-nav-inner">
      <a href="admin_dashboard.php" class="active">
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
      <a href="admin_add_product.php">
        <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M12 5v14"></path><path d="M5 12h14"></path><rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect></svg>
        <span>Add</span>
      </a>
    </div>
  </nav>

  <script>
    function setAdminMessagesBadge(count) {
      const links = document.querySelectorAll('.mobile-nav-inner a[href="messages.php"]');
      links.forEach((link) => {
        let badge = link.querySelector('.mobile-nav-msg-badge');
        if (count > 0) {
          if (!badge) {
            badge = document.createElement('span');
            badge.className = 'mobile-nav-msg-badge';
            link.appendChild(badge);
          }
          badge.textContent = count > 99 ? '99+' : String(count);
        } else if (badge) {
          badge.remove();
        }
      });
    }

    async function refreshAdminMessagesBadge() {
      try {
        const res = await fetch('api/messages-get-conversations.php', { cache: 'no-store' });
        const data = await res.json();
        if (!res.ok || !data.success || !Array.isArray(data.conversations)) {
          return;
        }
        const unreadCount = data.conversations.reduce((sum, conversation) => {
          return sum + Number(conversation.unread_count || 0);
        }, 0);
        setAdminMessagesBadge(unreadCount);
      } catch (error) {
      }
    }

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

    refreshAdminMessagesBadge();
    setInterval(refreshAdminMessagesBadge, 12000);
  </script>
</body>
</html>
