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
    body { margin: 0; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f3f4f6; }
    .wrap { width: calc(100% - 48px); margin: 18px auto; }
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

    @media (max-width: 900px) {
      .grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
    }
    @media (max-width: 768px) {
      .wrap { width: calc(100% - 24px); }
      .grid { grid-template-columns: 1fr; }
    }
  </style>
</head>
<body>
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
</body>
</html>
