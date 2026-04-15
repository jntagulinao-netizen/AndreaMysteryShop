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

$showLoginSplash = !empty($_SESSION['login_success']);
unset($_SESSION['login_success']);

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
      margin-bottom: 17px;
      background: #ffffff;
      border: 1px solid #e5e7eb;
      border-radius: 14px;
      padding: 10px;
      box-shadow: 0 6px 16px rgba(15, 23, 42, 0.06);
    }
    .stats-card {
      background: #f9fafb;
      border-radius: 10px;
      padding: 13px 10px;
      border: 1px solid #e6e8eb;
      text-align: center;
    }
    .stats-card.clickable { cursor: pointer; }
    .stats-card.clickable:hover { border-color: #e22a39; }
    .stats-value { font-size: 21px; font-weight: 700; color: #333; }
    .stats-label { font-size: 13px; color: #777; margin-top: 3px; }

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

    .basic-functions {
      background: #f1f1f1;
      border: 1px solid #ececec;
      border-radius: 16px;
      padding: 16px 14px 12px;
      margin: 8px 0 12px;
    }
    .basic-functions h2 {
      margin: 0 0 14px;
      font-size: 22px;
      font-weight: 700;
      color: #2f2f2f;
    }
    .functions-grid {
      display: grid;
      grid-template-columns: repeat(3, minmax(0, 1fr));
      gap: 10px;
    }
    .function-tile {
      text-decoration: none;
      color: #3d4550;
      border-radius: 12px;
      padding: 10px 8px;
      display: flex;
      flex-direction: column;
      align-items: center;
      gap: 8px;
      position: relative;
      transition: transform .18s ease, background .18s ease;
    }
    .function-tile:hover {
      transform: translateY(-2px);
      background: #ffffff;
    }
    .function-icon {
      width: 44px;
      height: 44px;
      border-radius: 12px;
      display: inline-flex;
      align-items: center;
      justify-content: center;
    }
    .function-icon svg {
      width: 24px;
      height: 24px;
      stroke: #fff;
      stroke-width: 1.9;
      fill: none;
      stroke-linecap: round;
      stroke-linejoin: round;
    }
    .icon-message { background: linear-gradient(135deg, #3b82f6, #2563eb); }
    .icon-orders { background: linear-gradient(135deg, #8b5cf6, #7c3aed); }
    .icon-products { background: linear-gradient(135deg, #f59e0b, #f97316); }
    .icon-drafts { background: linear-gradient(135deg, #10b981, #0ea5a5); }
    .icon-auction-live { background: linear-gradient(135deg, #ef4444, #f59e0b); }
    .icon-archived { background: linear-gradient(135deg, #64748b, #475569); }
    .icon-reviews { background: linear-gradient(135deg, #06b6d4, #0e7490); }
    .icon-add { background: linear-gradient(135deg, #ef4444, #e11d48); }
    .icon-schedule { background: linear-gradient(135deg, #8b5cf6, #7c3aed); }
    .function-label {
      font-size: 12px;
      font-weight: 700;
      color: #4b5563;
      text-align: center;
      line-height: 1.25;
      min-height: 30px;
      display: flex;
      align-items: center;
      justify-content: center;
    }
    .function-msg-badge {
      position: absolute;
      top: 6px;
      right: 8px;
      min-width: 18px;
      height: 18px;
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
      z-index: 2;
    }

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

    .login-splash {
      position: fixed;
      inset: 0;
      z-index: 60000;
      display: flex;
      align-items: center;
      justify-content: center;
      overflow: hidden;
      background: radial-gradient(circle at 50% 22%, #f7fbff 0%, #edf4fb 52%, #e6eff8 100%);
      transition: opacity 0.42s ease;
    }
    .login-splash.is-hiding {
      opacity: 0;
      pointer-events: none;
    }
    .login-splash-cinematic {
      position: absolute;
      inset: -8%;
      pointer-events: none;
    }
    .cinema-bar {
      position: absolute;
      top: -20%;
      height: 140%;
      width: 18vw;
      background: linear-gradient(180deg, rgba(61, 196, 184, 0), rgba(61, 196, 184, 0.24), rgba(237, 90, 169, 0));
      filter: blur(10px);
      opacity: 0;
      transform: skewX(-12deg) translateX(-20vw);
      animation: cinemaSweep 2.2s ease-out forwards;
    }
    .cinema-bar.bar-a { left: 20%; animation-delay: 0.1s; }
    .cinema-bar.bar-b { left: 42%; animation-delay: 0.28s; }
    .cinema-bar.bar-c { left: 64%; animation-delay: 0.46s; }

    .login-splash-skip {
      position: absolute;
      top: max(12px, env(safe-area-inset-top));
      right: 14px;
      border: 1px solid rgba(32, 93, 131, 0.24);
      background: rgba(255,255,255,0.7);
      color: #1f3f57;
      border-radius: 999px;
      padding: 7px 14px;
      font-size: 12px;
      font-weight: 700;
      letter-spacing: 0.03em;
      cursor: pointer;
      backdrop-filter: blur(6px);
    }

    .login-splash-inner {
      position: relative;
      display: flex;
      flex-direction: column;
      align-items: center;
      gap: 10px;
      text-align: center;
      padding: 28px 26px;
      border-radius: 18px;
      background: linear-gradient(160deg, rgba(255,255,255,0.92), rgba(250,253,255,0.84));
      border: 1px solid rgba(51, 121, 162, 0.14);
      box-shadow: 0 24px 55px rgba(41, 86, 116, 0.16);
      animation: splashPopIn 0.74s cubic-bezier(0.2, 0.8, 0.2, 1) both;
      max-width: min(88vw, 420px);
    }
    .login-splash-logo {
      width: min(38vw, 180px);
      height: auto;
      object-fit: contain;
      filter: drop-shadow(0 12px 24px rgba(30, 115, 135, 0.22));
      animation: splashLogoPulse 1.5s ease-in-out infinite alternate;
    }
    .login-splash-title {
      color: #1e3b4f;
      font-weight: 800;
      font-size: clamp(18px, 2.5vw, 24px);
      letter-spacing: 0.03em;
    }
    .login-splash-text {
      color: #3f5f75;
      font-weight: 600;
      font-size: clamp(13px, 2vw, 16px);
      letter-spacing: 0.02em;
    }
    .login-splash-progress {
      width: min(68vw, 260px);
      height: 4px;
      border-radius: 999px;
      background: rgba(46, 113, 148, 0.15);
      overflow: hidden;
      margin-top: 4px;
    }
    .login-splash-progress > span {
      display: block;
      width: 0;
      height: 100%;
      border-radius: inherit;
      background: linear-gradient(90deg, #2fb7ad, #da4ca8);
      animation: splashLoad 2.25s linear forwards;
    }
    .login-splash-hint {
      color: rgba(53, 89, 111, 0.9);
      font-size: 12px;
      letter-spacing: 0.02em;
    }

    @keyframes splashPopIn {
      0% { opacity: 0; transform: translateY(12px) scale(0.92); }
      100% { opacity: 1; transform: translateY(0) scale(1); }
    }
    @keyframes splashLogoPulse {
      0% { transform: scale(0.98); }
      100% { transform: scale(1.03); }
    }
    @keyframes splashLoad {
      0% { width: 0; }
      100% { width: 100%; }
    }
    @keyframes cinemaSweep {
      0% { opacity: 0; transform: skewX(-12deg) translateX(-24vw); }
      18% { opacity: 1; }
      100% { opacity: 0; transform: skewX(-12deg) translateX(32vw); }
    }
    

    @media (max-width: 768px) {
      .page-container { width: calc(100% - 24px); }
      .stats-strip { grid-template-columns: repeat(2, 1fr); }
      .stats-card { padding: 10px 8px; }
      .stats-value { font-size: 18px; }
      .stats-label { font-size: 12px; margin-top: 2px; }
      .basic-functions { padding: 14px 10px 10px; }
      .basic-functions h2 { font-size: 18px; margin-bottom: 10px; }
      .functions-grid { grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 6px; }
      .function-tile { padding: 8px 4px; gap: 6px; }
      .function-icon { width: 38px; height: 38px; border-radius: 10px; }
      .function-icon svg { width: 20px; height: 20px; }
      .function-label { font-size: 11px; min-height: 28px; }
      .function-msg-badge { display: none; }
    }

    @media (prefers-reduced-motion: reduce) {
      .login-splash-inner,
      .login-splash-logo,
      .login-splash-progress > span,
      .cinema-bar {
        animation: none;
      }
      .login-splash {
        transition: none;
      }
    }

    @media (max-width: 560px) {
      .login-splash-inner { padding: 22px 18px; }
      .login-splash-title { font-size: 18px; }
      .login-splash-hint { font-size: 11px; }
    }
  </style>
</head>
<body>
  <?php if ($showLoginSplash): ?>
  <div id="loginSplash" class="login-splash" role="status" aria-live="polite" aria-label="Opening admin dashboard" tabindex="0">
    <div class="login-splash-cinematic" aria-hidden="true">
      <span class="cinema-bar bar-a"></span>
      <span class="cinema-bar bar-b"></span>
      <span class="cinema-bar bar-c"></span>
    </div>
    <button type="button" class="login-splash-skip" id="loginSplashSkip">Skip</button>
    <div class="login-splash-inner" id="loginSplashInner">
      <img src="logo-removebg-preview.png" alt="Andrea Mystery Shop" class="login-splash-logo">
      <div class="login-splash-title">Admin Control Center</div>
      <div class="login-splash-text">Preparing your dashboard view</div>
      <div class="login-splash-progress"><span></span></div>
      <div class="login-splash-hint">Tap anywhere to continue</div>
    </div>
  </div>
  <?php endif; ?>

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
          <a href="admin_orders_receive_pickup.php">Receive / Pickup Orders</a>
          <a href="admin_my_products.php">My Products</a>
          <a href="admin_product_drafts.php">Product Drafts</a>
          <a href="admin_auction_drafts.php">Auction Drafts</a>
          <a href="admin_live_auctions.php">Live Auctions</a>
          <a href="admin_add_auction.php">Add Auction Item</a>
          <a href="admin_my_products.php?view=archived">Archived Products</a>
          <a href="admin_manage_reviews.php">Manage Reviews</a>
          <a href="admin_profile.php">Admin Profile</a>
          <a href="admin_delivery_slots.php">Delivery Slots</a>
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

    <section class="basic-functions">
      <h2>Basic Function</h2>
      <div class="functions-grid">
        <a class="function-tile" id="desktopMessagesTile" href="messages.php" title="Messages">
          <span class="function-icon icon-message">
            <svg viewBox="0 0 24 24"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path></svg>
          </span>
          <span class="function-label">Messages</span>
        </a>
        <a class="function-tile" href="admin_orders.php" title="Admin Orders">
          <span class="function-icon icon-orders">
            <svg viewBox="0 0 24 24"><rect x="3" y="8" width="18" height="11" rx="2" ry="2"></rect><path d="M3 8V7a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2v1"></path><path d="M8 12h8"></path></svg>
          </span>
          <span class="function-label">Admin Orders</span>
        </a>
        <a class="function-tile" href="admin_orders_receive_pickup.php" title="Receive & Pickup Orders">
          <span class="function-icon icon-orders">
            <svg viewBox="0 0 24 24"><path d="M5 13l3 3 7-7"></path><path d="M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0z"></path></svg>
          </span>
          <span class="function-label">Receive / Pickup Orders</span>
        </a>
        <a class="function-tile" href="admin_my_products.php" title="My Products">
          <span class="function-icon icon-products">
            <svg viewBox="0 0 24 24"><path d="M3 7l9-4 9 4-9 4-9-4z"></path><path d="M3 12l9 4 9-4"></path><path d="M3 17l9 4 9-4"></path></svg>
          </span>
          <span class="function-label">My Products</span>
        </a>
        <a class="function-tile" href="admin_product_drafts.php" title="Product Drafts">
          <span class="function-icon icon-drafts">
            <svg viewBox="0 0 24 24"><path d="M4 4h16v16H4z"></path><path d="M8 8h8"></path><path d="M8 12h8"></path><path d="M8 16h5"></path></svg>
          </span>
          <span class="function-label">Product Drafts</span>
        </a>
        <a class="function-tile" href="admin_auction_drafts.php" title="Auction Drafts">
          <span class="function-icon icon-orders">
            <svg viewBox="0 0 24 24"><path d="M12 3l2.9 5.9 6.5.9-4.7 4.5 1.1 6.4-5.8-3.1-5.8 3.1 1.1-6.4-4.7-4.5 6.5-.9z"></path></svg>
          </span>
          <span class="function-label">Auction Drafts</span>
        </a>
        <a class="function-tile" href="admin_live_auctions.php" title="Live Auctions">
          <span class="function-icon icon-auction-live">
            <svg viewBox="0 0 24 24"><path d="M14.5 5.5l4 4"></path><path d="M5.5 14.5l4 4"></path><path d="M4 20l6.5-6.5"></path><path d="M9.5 10.5l6-6 4 4-6 6"></path><path d="M12 7l5 5"></path><path d="M2 22h8"></path></svg>
          </span>
          <span class="function-label">Live Auctions</span>
        </a>
        <a class="function-tile" href="admin_my_products.php?view=archived" title="Archived Products">
          <span class="function-icon icon-archived">
            <svg viewBox="0 0 24 24"><path d="M3 7h18"></path><path d="M5 7l1 12h12l1-12"></path><path d="M9 11v5"></path><path d="M15 11v5"></path><path d="M10 7V5h4v2"></path></svg>
          </span>
          <span class="function-label">Archived Products</span>
        </a>
        <a class="function-tile" href="admin_manage_reviews.php" title="Manage Reviews">
          <span class="function-icon icon-reviews">
            <svg viewBox="0 0 24 24"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path><path d="M8 9h8"></path><path d="M8 13h6"></path></svg>
          </span>
          <span class="function-label">Manage Reviews</span>
        </a>
        <a class="function-tile" href="admin_add_product.php" title="Add Product">
          <span class="function-icon icon-add">
            <svg viewBox="0 0 24 24"><path d="M12 5v14"></path><path d="M5 12h14"></path><rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect></svg>
          </span>
          <span class="function-label">Add Product</span>
        </a>
        <a class="function-tile" href="admin_delivery_slots.php" title="Delivery Slots">
          <span class="function-icon icon-schedule">
            <svg viewBox="0 0 24 24"><path d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
          </span>
          <span class="function-label">Delivery Slots</span>
        </a>
      </div>
    </section>
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
      <a href="admin_profile.php">
        <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M12 12c2.5 0 4.5-2 4.5-4.5S14.5 3 12 3 7.5 5 7.5 7.5 9.5 12 12 12z"></path><path d="M4 21c0-4.5 4-8 8-8s8 3.5 8 8"></path></svg>
        <span>Profile</span>
      </a>
    </div>
  </nav>

  <script>
    <?php if ($showLoginSplash): ?>
    (function () {
      var splash = document.getElementById('loginSplash');
      var skipBtn = document.getElementById('loginSplashSkip');
      var isClosed = false;
      if (!splash) return;

      function closeSplash() {
        if (isClosed) return;
        isClosed = true;
        splash.classList.add('is-hiding');
        window.setTimeout(function () {
          if (splash && splash.parentNode) {
            splash.parentNode.removeChild(splash);
          }
        }, 420);
      }

      if (skipBtn) {
        skipBtn.addEventListener('click', closeSplash);
      }

      splash.addEventListener('click', function (event) {
        if (skipBtn && event.target === skipBtn) return;
        closeSplash();
      });

      splash.addEventListener('keydown', function (event) {
        if (event.key === 'Enter' || event.key === ' ' || event.key === 'Escape') {
          event.preventDefault();
          closeSplash();
        }
      });

      window.setTimeout(closeSplash, 2400);
    })();
    <?php endif; ?>

    function setAdminMessagesBadge(count) {
      const desktopTile = document.getElementById('desktopMessagesTile');
      if (desktopTile) {
        let desktopBadge = desktopTile.querySelector('.function-msg-badge');
        if (count > 0) {
          if (!desktopBadge) {
            desktopBadge = document.createElement('span');
            desktopBadge.className = 'function-msg-badge';
            desktopTile.appendChild(desktopBadge);
          }
          desktopBadge.textContent = count > 99 ? '99+' : String(count);
        } else if (desktopBadge) {
          desktopBadge.remove();
        }
      }

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
