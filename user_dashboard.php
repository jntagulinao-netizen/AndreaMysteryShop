<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: LogIn.php');
    exit;
}
// restrict this page to regular users only; redirect admins to admin panel
$role = $_SESSION['user_role'] ?? 'user';
if ($role === 'admin') {
  header('Location: admin_dashboard.php');
  exit;
}
if ($role !== 'user') {
  echo 'Access denied.'; exit;
}

$showLoginSplash = !empty($_SESSION['login_success']);
unset($_SESSION['login_success']);
?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>User Dashboard - LUXE</title>
  <link rel="stylesheet" href="main.css?v=20260331-1">
        <link rel="stylesheet" href="assets/css/reusable_catalog_modal_reviews.css?v=20260404-2">
    <link rel="stylesheet" href="assets/css/user_dashboard_search.css?v=20260331-1">
    <link rel="stylesheet" href="assets/css/user_dashboard_cart.css?v=20260407-3">
    <link rel="stylesheet" href="assets/css/user_dashboard_checkout.css?v=20260407-1">
    <link rel="stylesheet" href="assets/css/user_dashboard_shared.css?v=20260409-2">
</head>
<body>
    <?php if ($showLoginSplash): ?>
    <div id="loginSplash" class="login-splash" role="status" aria-live="polite" aria-label="Opening dashboard" tabindex="0">
      <div class="login-splash-cinematic" aria-hidden="true">
        <span class="cinema-bar bar-a"></span>
        <span class="cinema-bar bar-b"></span>
        <span class="cinema-bar bar-c"></span>
      </div>
      <button type="button" class="login-splash-skip" id="loginSplashSkip">Skip</button>
      <div class="login-splash-inner" id="loginSplashInner">
        <img src="logo-removebg-preview.png" alt="Andrea Mystery Shop" class="login-splash-logo">
        <div class="login-splash-title">Andrea Mystery Shop</div>
        <div class="login-splash-text">The mystery opens now</div>
        <div class="login-splash-progress"><span></span></div>
        <div class="login-splash-hint">Tap anywhere to continue</div>
        </div>
    </div>
    <?php endif; ?>

    <?php include __DIR__ . '/partials/user_dashboard/topbar_search.php'; ?>

   <!-- Mobile bottom navigation (Lazada-style) -->
    <nav class="mobile-bottom-nav fixed">
        <div class="mobile-nav-inner">
            <a href="user_dashboard.php" class="active">
                <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M3 10.5L12 3l9 7.5V21a1 1 0 0 1-1 1h-5v-7H9v7H4a1 1 0 0 1-1-1V10.5z" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"></path></svg>
                <span>Home</span>
            </a>
            <a href="auction.php">
              <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M14.5 5.5l4 4"></path><path d="M5.5 14.5l4 4"></path><path d="M4 20l6.5-6.5"></path><path d="M9.5 10.5l6-6 4 4-6 6"></path><path d="M12 7l5 5"></path><path d="M2 22h8"></path></svg>
              <span>Auctions</span>
            </a>
             <a href="category_products.php">
                <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polygon points="16 8 12 11 8 16 16 8"/></svg>
                <span>Explore</span>
            </a>
            <a href="account.php">
                <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M12 12c2.5 0 4.5-2 4.5-4.5S14.5 3 12 3 7.5 5 7.5 7.5 9.5 12 12 12z"/><path d="M4 21c0-4.5 4-8 8-8s8 3.5 8 8"/></svg>
                <span>Account</span>
            </a>
        </div>
    </nav>

    <?php include __DIR__ . '/partials/user_dashboard/catalog_section.php'; ?>

    <?php include __DIR__ . '/partials/user_dashboard/cart_section.php'; ?>
    <?php include __DIR__ . '/partials/user_dashboard/checkout_section.php'; ?>

    <?php include __DIR__ . '/partials/user_dashboard/product_modal_section.php'; ?>

    <script src="assets/js/user_dashboard_reusable_ui.js?v=20260406-2"></script>
    <script src="assets/js/user_dashboard_cart.js?v=20260407-3"></script>
    <script src="assets/js/user_dashboard_helpers.js?v=20260401-2"></script>
    <script src="assets/js/user_dashboard_recipients.js?v=20260401-2"></script>
    <script src="assets/js/user_dashboard_search.js"></script>
    <script src="assets/js/user_dashboard_app.js?v=20260409-2"></script>
    <script src="assets/js/user_dashboard_app_init.js?v=20260409-2"></script>
    <?php if ($showLoginSplash): ?>
    <script>
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
    </script>
    <?php endif; ?>
</body>
</html>


