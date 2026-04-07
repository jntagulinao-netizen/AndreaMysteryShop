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
$isOwnerStmt = $conn->prepare("SELECT is_owner FROM users WHERE user_id = ? AND LOWER(role) = 'admin' LIMIT 1");
$isOwnerAdmin = false;
if ($isOwnerStmt) {
    $isOwnerStmt->bind_param('i', $userId);
    $isOwnerStmt->execute();
    $isOwnerResult = $isOwnerStmt->get_result();
    if ($isOwnerResult && ($ownerRow = $isOwnerResult->fetch_assoc())) {
        $isOwnerAdmin = ((int)($ownerRow['is_owner'] ?? 0) === 1);
    }
    $isOwnerStmt->close();
}
if (!$isOwnerAdmin || empty($_SESSION['owner_reset_mode']) || empty($_SESSION['owner_reset_verified'])) {
    header('Location: owner_admin_access.php');
    exit;
}

$flashError = '';
if (!empty($_SESSION['owner_new_pin_error'])) {
    $flashError = (string)$_SESSION['owner_new_pin_error'];
    unset($_SESSION['owner_new_pin_error']);
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>Create New Owner PIN</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet"/>
  <link rel="icon" type="image/png" href="logo.jpg"/>
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <style>
    :root {
      --bg-deep: #141926;
      --bg-mid: #222c41;
      --bg-light: #2f4161;
      --brand: #f3b33d;
      --brand-soft: #ffe2a0;
      --text-main: #f8faff;
      --text-muted: #d0d9ee;
      --surface: rgba(255, 255, 255, 0.08);
      --surface-strong: rgba(255, 255, 255, 0.16);
    }

    * { box-sizing: border-box; }

    body {
      margin: 0;
      min-height: 100vh;
      font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
      color: var(--text-main);
      display: flex;
      align-items: center;
      justify-content: center;
      background:
        radial-gradient(circle at 12% 16%, rgba(243, 179, 61, 0.24), transparent 34%),
        radial-gradient(circle at 84% 82%, rgba(99, 143, 220, 0.3), transparent 42%),
        linear-gradient(160deg, var(--bg-deep) 0%, var(--bg-mid) 58%, var(--bg-light) 100%);
      overflow: hidden;
    }

    .shell {
      position: relative;
      width: min(480px, 100%);
      padding: 24px 18px;
    }

    .shell::before {
      content: '';
      position: absolute;
      inset: 0;
      background-image:
        linear-gradient(rgba(255, 255, 255, 0.04) 1px, transparent 1px),
        linear-gradient(90deg, rgba(255, 255, 255, 0.04) 1px, transparent 1px);
      background-size: 30px 30px;
      opacity: 0.22;
      pointer-events: none;
      z-index: 0;
    }

    .brand,
    .panel {
      position: relative;
      z-index: 1;
    }

    .brand {
      display: flex;
      flex-direction: column;
      align-items: center;
      text-align: center;
      margin-bottom: 16px;
    }

    .brand img {
      width: 72px;
      height: 72px;
      border-radius: 18px;
      object-fit: cover;
      border: 2px solid rgba(255, 255, 255, 0.35);
      box-shadow: 0 12px 28px rgba(0, 0, 0, 0.35);
      background: #fff;
    }

    .brand h1 {
      margin: 11px 0 2px;
      font-size: 31px;
      font-weight: 800;
      line-height: 1.05;
      letter-spacing: 0.2px;
    }

    .brand .kicker {
      margin: 0;
      font-size: 11px;
      text-transform: uppercase;
      letter-spacing: 1.8px;
      color: var(--brand-soft);
      font-weight: 700;
    }

    .brand p {
      margin: 10px 0 0;
      font-size: 20px;
      font-weight: 600;
      color: var(--text-muted);
    }

    .panel {
      background: linear-gradient(180deg, rgba(255, 255, 255, 0.12) 0%, rgba(255, 255, 255, 0.08) 100%);
      border: 1px solid rgba(255, 255, 255, 0.18);
      border-radius: 20px;
      padding: 17px;
      box-shadow: 0 18px 42px rgba(3, 7, 18, 0.42);
      backdrop-filter: blur(8px);
    }

    .panel h2 {
      margin: 0 0 6px;
      font-size: 18px;
      color: #fff;
    }

    .panel p {
      margin: 0 0 14px;
      color: var(--text-muted);
      font-size: 13px;
      line-height: 1.45;
    }

    .step-label {
      font-size: 11px;
      font-weight: 800;
      letter-spacing: 0.14em;
      text-transform: uppercase;
      color: var(--brand-soft);
      margin-bottom: 10px;
    }

    .pin-grid {
      display: grid;
      grid-template-columns: repeat(4, minmax(0, 1fr));
      gap: 10px;
      margin-bottom: 12px;
    }

    .pin-digit {
      width: 100%;
      height: 52px;
      border: 1px solid rgba(255, 255, 255, 0.24);
      border-radius: 13px;
      background: rgba(255, 255, 255, 0.08);
      text-align: center;
      font-size: 22px;
      font-weight: 800;
      color: #fff;
      outline: none;
      transition: border-color .15s ease, background .15s ease, transform .15s ease;
    }

    .pin-digit:focus {
      border-color: rgba(243, 179, 61, 0.95);
      background: rgba(243, 179, 61, 0.18);
      transform: translateY(-1px);
      box-shadow: 0 0 0 3px rgba(243, 179, 61, 0.22);
    }

    .actions {
      display: flex;
      gap: 8px;
      flex-wrap: wrap;
    }

    .btn-blue,
    .btn-light {
      border-radius: 11px;
      padding: 10px 14px;
      font-weight: 700;
      text-decoration: none;
      display: inline-block;
      font-size: 13px;
      transition: transform .15s ease, border-color .15s ease, background .15s ease;
    }

    .btn-blue {
      border: 1px solid rgba(243, 179, 61, 0.88);
      background: linear-gradient(180deg, rgba(243, 179, 61, 0.32) 0%, rgba(243, 179, 61, 0.22) 100%);
      color: #fff;
    }

    .btn-light {
      border: 1px solid rgba(255, 255, 255, 0.3);
      background: rgba(255, 255, 255, 0.06);
      color: #ecf1ff;
    }

    .btn-blue:hover,
    .btn-light:hover {
      transform: translateY(-1px);
      background: rgba(243, 179, 61, 0.24);
      border-color: rgba(243, 179, 61, 0.84);
      color: #fff;
    }

    .alertBox {
      padding: 10px 12px;
      border-radius: 11px;
      margin-bottom: 12px;
      font-size: 13px;
      color: #fff;
    }

    .alertBox.error {
      background: rgba(220, 38, 38, 0.22);
      border: 1px solid rgba(248, 113, 113, 0.45);
    }

    .alertBox.success {
      background: rgba(22, 163, 74, 0.22);
      border: 1px solid rgba(74, 222, 128, 0.45);
    }

    @media (max-width: 430px) {
      .shell { padding: 16px 14px; }
      .brand img { width: 64px; height: 64px; border-radius: 16px; }
      .brand h1 { font-size: 28px; }
      .brand p { font-size: 18px; }
      .panel { padding: 14px; }
      .pin-digit { height: 48px; font-size: 20px; }
      .actions { flex-direction: column; }
      .btn-blue,
      .btn-light { width: 100%; text-align: center; }
    }
  </style>
</head>
<body>
  <div class="shell">
    <div class="brand">
      <img src="logo.jpg" alt="Administrative System">
      <h1>Administrative System</h1>
      <p class="kicker">Andrea Mystery Shop Owner Access</p>
      <p>Create New PIN</p>
    </div>

    <div class="panel">
      <h2>Set a new 4-digit PIN</h2>
      <p>Enter a new PIN and confirm it to finish resetting your owner access code.</p>

      <?php if ($flashError !== ''): ?><div class="alertBox error"><?php echo htmlspecialchars($flashError); ?></div><?php endif; ?>

      <form method="POST" action="owner_new_pin_process.php" id="pinForm">
        <div class="step-label">New 4-Digit Code</div>
        <div class="pin-grid" data-group="new">
          <input class="pin-digit" type="text" maxlength="1" inputmode="numeric" autocomplete="off">
          <input class="pin-digit" type="text" maxlength="1" inputmode="numeric" autocomplete="off">
          <input class="pin-digit" type="text" maxlength="1" inputmode="numeric" autocomplete="off">
          <input class="pin-digit" type="text" maxlength="1" inputmode="numeric" autocomplete="off">
        </div>
        <input type="hidden" name="new_pin" id="newPinHidden">

        <div class="step-label" style="margin-top:8px;">Confirm New Code</div>
        <div class="pin-grid" data-group="confirm">
          <input class="pin-digit" type="text" maxlength="1" inputmode="numeric" autocomplete="off">
          <input class="pin-digit" type="text" maxlength="1" inputmode="numeric" autocomplete="off">
          <input class="pin-digit" type="text" maxlength="1" inputmode="numeric" autocomplete="off">
          <input class="pin-digit" type="text" maxlength="1" inputmode="numeric" autocomplete="off">
        </div>
        <input type="hidden" name="confirm_pin" id="confirmPinHidden">

        <div class="actions mt-2">
          <button type="submit" class="btn-blue">Save New PIN</button>
          <a href="owner_admin_access.php" class="btn-light">Back</a>
        </div>
      </form>
    </div>
  </div>

  <script>
    (function () {
      function bindRow(groupSelector, hiddenInputId) {
        var group = document.querySelector(groupSelector);
        var hidden = document.getElementById(hiddenInputId);
        if (!group || !hidden) return;
        var inputs = Array.prototype.slice.call(group.querySelectorAll('.pin-digit'));

        function sync() {
          hidden.value = inputs.map(function (input) { return String(input.value || '').replace(/\D/g, ''); }).join('').slice(0, inputs.length);
        }

        inputs.forEach(function (input, index) {
          input.addEventListener('input', function () {
            input.value = String(input.value || '').replace(/\D/g, '').slice(0,1);
            if (input.value && index < inputs.length - 1) {
              inputs[index + 1].focus();
            }
            sync();
          });
          input.addEventListener('keydown', function (event) {
            if (event.key === 'Backspace' && !input.value && index > 0) {
              inputs[index - 1].focus();
            }
          });
        });
        sync();
      }
      bindRow('[data-group="new"]', 'newPinHidden');
      bindRow('[data-group="confirm"]', 'confirmPinHidden');

      document.getElementById('pinForm').addEventListener('submit', function (event) {
        var newPin = document.getElementById('newPinHidden').value;
        var confirmPin = document.getElementById('confirmPinHidden').value;
        if (newPin.length !== 4 || confirmPin.length !== 4) {
          event.preventDefault();
          Swal.fire({icon:'warning', title:'Incomplete', text:'Please enter both 4-digit codes.'});
          return;
        }
      });

      <?php if ($flashError !== '' && isset($_SESSION['owner_new_pin_error'])): ?>
      <?php endif; ?>
    })();
  </script>
</body>
</html>
