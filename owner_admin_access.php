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

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once 'phpmailer/PHPMailer.php';
require_once 'phpmailer/SMTP.php';
require_once 'phpmailer/Exception.php';

$userId = (int)$_SESSION['user_id'];
$flashMessage = '';
$flashError = '';

if (!empty($_SESSION['owner_admin_flash_message'])) {
  $flashMessage = (string)$_SESSION['owner_admin_flash_message'];
  unset($_SESSION['owner_admin_flash_message']);
}
if (!empty($_SESSION['owner_admin_flash_error'])) {
  $flashError = (string)$_SESSION['owner_admin_flash_error'];
  unset($_SESSION['owner_admin_flash_error']);
}

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

$userStmt = $conn->prepare('SELECT full_name, email FROM users WHERE user_id = ? LIMIT 1');
$userStmt->bind_param('i', $userId);
$userStmt->execute();
$userRes = $userStmt->get_result();
$user = $userRes ? ($userRes->fetch_assoc() ?: ['full_name' => 'Owner', 'email' => '']) : ['full_name' => 'Owner', 'email' => ''];
$userStmt->close();

function owner_send_otp_email(string $email, string $name, string $otp): bool {
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'andreamysteryshop@gmail.com';
        $mail->Password = 'djdimmouiwecwoxg';
        $mail->SMTPSecure = 'tls';
        $mail->Port = 587;
        $mail->SMTPDebug = 0;

        $mail->setFrom('andreamysteryshop@gmail.com', 'Andrea Mystery Shop');
        $mail->addAddress($email);
        $mail->isHTML(true);
        $mail->Subject = 'Owner Administrative Access OTP';

        $safeName = htmlspecialchars($name ?: 'Owner', ENT_QUOTES, 'UTF-8');
        $mail->Body = '<!doctype html><html><body style="font-family:Arial,sans-serif;color:#1f2937">'
            . '<h2 style="margin:0 0 12px">Owner Administrative Access OTP</h2>'
            . '<p>Hello ' . $safeName . ',</p>'
            . '<p>Your OTP to reset the 4-digit owner access code is:</p>'
            . '<p style="font-size:28px;font-weight:700;letter-spacing:4px;color:#dc2626">' . $otp . '</p>'
            . '<p>This OTP expires in 5 minutes. If you did not request this, you can ignore this message.</p>'
            . '</body></html>';

        return $mail->send();
    } catch (Exception $e) {
        error_log('Owner OTP email failed: ' . $mail->ErrorInfo);
        return false;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = trim((string)($_POST['action'] ?? ''));

    $securityStmt = $conn->prepare('SELECT access_code_hash, reset_otp, reset_otp_expires, reset_otp_verified FROM admin_owner_security WHERE user_id = ? LIMIT 1');
    $securityStmt->bind_param('i', $userId);
    $securityStmt->execute();
    $securityRes = $securityStmt->get_result();
    $security = $securityRes ? ($securityRes->fetch_assoc() ?: []) : [];
    $securityStmt->close();

    if ($action === 'create_code') {
        $code = trim((string)($_POST['new_code'] ?? ''));
        $confirm = trim((string)($_POST['confirm_code'] ?? ''));

        if (!preg_match('/^\d{4}$/', $code)) {
            $flashError = 'Access code must be exactly 4 digits.';
        } elseif ($code !== $confirm) {
            $flashError = 'Code confirmation does not match.';
        } else {
            $hash = password_hash($code, PASSWORD_DEFAULT);
            $saveStmt = $conn->prepare('UPDATE admin_owner_security SET access_code_hash = ?, reset_otp = NULL, reset_otp_expires = NULL, reset_otp_verified = 0 WHERE user_id = ?');
            $saveStmt->bind_param('si', $hash, $userId);
            $saveStmt->execute();
            $saveStmt->close();
            $flashMessage = '4-digit owner access code has been created.';
        }
    }

    if ($action === 'verify_code') {
        $code = trim((string)($_POST['access_code'] ?? ''));
        $hash = (string)($security['access_code_hash'] ?? '');

        if ($hash === '') {
            $flashError = 'Owner access code is not set yet. Create one first.';
        } elseif (!preg_match('/^\d{4}$/', $code)) {
            $flashError = 'Enter a valid 4-digit code.';
        } elseif (!password_verify($code, $hash)) {
            $flashError = 'Invalid code.';
        } else {
            $_SESSION['owner_admin_access_unlocked'] = 1;
            header('Location: owner_administrative_page.php');
            exit;
        }
    }

    if ($action === 'send_reset_otp') {
        $otp = (string)random_int(100000, 999999);
        $expiresAt = date('Y-m-d H:i:s', time() + 300);

        $otpStmt = $conn->prepare('UPDATE admin_owner_security SET reset_otp = ?, reset_otp_expires = ?, reset_otp_verified = 0 WHERE user_id = ?');
        $otpStmt->bind_param('ssi', $otp, $expiresAt, $userId);
        $otpStmt->execute();
        $otpStmt->close();

        $mailOk = owner_send_otp_email((string)($user['email'] ?? ''), (string)($user['full_name'] ?? 'Owner'), $otp);
        if ($mailOk) {
            $flashMessage = 'OTP sent to your email.';
        } else {
            $flashError = 'Failed to send OTP email. Please try again.';
        }
    }

    if ($action === 'verify_reset_otp') {
        $otp = trim((string)($_POST['otp_code'] ?? ''));
        $savedOtp = (string)($security['reset_otp'] ?? '');
        $expires = (string)($security['reset_otp_expires'] ?? '');
        $isExpired = ($expires === '' || strtotime($expires) < time());

        if (!preg_match('/^\d{6}$/', $otp)) {
            $flashError = 'OTP must be a 6-digit code.';
        } elseif ($savedOtp === '' || $isExpired) {
            $flashError = 'OTP expired. Please request a new OTP.';
        } elseif (!hash_equals($savedOtp, $otp)) {
            $flashError = 'Invalid OTP code.';
        } else {
            $verifyStmt = $conn->prepare('UPDATE admin_owner_security SET reset_otp_verified = 1 WHERE user_id = ?');
            $verifyStmt->bind_param('i', $userId);
            $verifyStmt->execute();
            $verifyStmt->close();
            $flashMessage = 'OTP verified. You can now set a new 4-digit code.';
        }
    }

    if ($action === 'save_new_code') {
        $verified = (int)($security['reset_otp_verified'] ?? 0) === 1;
        $newCode = trim((string)($_POST['new_code'] ?? ''));
        $confirmCode = trim((string)($_POST['confirm_code'] ?? ''));

        if (!$verified) {
            $flashError = 'Please verify OTP first.';
        } elseif (!preg_match('/^\d{4}$/', $newCode)) {
            $flashError = 'New access code must be exactly 4 digits.';
        } elseif ($newCode !== $confirmCode) {
            $flashError = 'Code confirmation does not match.';
        } else {
            $newHash = password_hash($newCode, PASSWORD_DEFAULT);
            $saveNewStmt = $conn->prepare('UPDATE admin_owner_security SET access_code_hash = ?, reset_otp = NULL, reset_otp_expires = NULL, reset_otp_verified = 0 WHERE user_id = ?');
            $saveNewStmt->bind_param('si', $newHash, $userId);
            $saveNewStmt->execute();
            $saveNewStmt->close();
            $flashMessage = 'Owner access code updated successfully.';
        }
    }
}

$currentSecurityStmt = $conn->prepare('SELECT access_code_hash, reset_otp_expires, reset_otp_verified FROM admin_owner_security WHERE user_id = ? LIMIT 1');
$currentSecurityStmt->bind_param('i', $userId);
$currentSecurityStmt->execute();
$currentSecurityRes = $currentSecurityStmt->get_result();
$currentSecurity = $currentSecurityRes ? ($currentSecurityRes->fetch_assoc() ?: []) : [];
$currentSecurityStmt->close();

$hasAccessCode = !empty($currentSecurity['access_code_hash']);
$otpVerified = (int)($currentSecurity['reset_otp_verified'] ?? 0) === 1;
$forgotPanelOpen = !empty($currentSecurity['reset_otp']) || $otpVerified || $flashMessage !== '' || $flashError !== '';
?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Owner Access - Andrea Mystery Shop</title>
  <link rel="stylesheet" href="main.css">
  <style>
    :root {
      --bg-deep: #131722;
      --bg-mid: #21293b;
      --bg-light: #2f3f5f;
      --brand: #f3b33d;
      --brand-soft: #ffd67d;
      --text-main: #f7f9ff;
      --text-dim: #cfd6ea;
      --surface: rgba(255, 255, 255, 0.08);
      --surface-strong: rgba(255, 255, 255, 0.14);
    }

    * { box-sizing: border-box; }

    body {
      margin: 0;
      font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
      color: var(--text-main);
      overflow: hidden;
      background:
        radial-gradient(circle at 14% 18%, rgba(243, 179, 61, 0.24), transparent 32%),
        radial-gradient(circle at 82% 85%, rgba(92, 132, 208, 0.32), transparent 40%),
        linear-gradient(165deg, var(--bg-deep) 0%, var(--bg-mid) 58%, var(--bg-light) 100%);
    }

    .pin-shell {
      position: relative;
      height: 100vh;
      min-height: 100svh;
      width: 100%;
      padding: 18px 20px 14px;
      display: flex;
      flex-direction: column;
      justify-content: space-between;
      overflow: hidden;
    }

    .pin-shell::before {
      content: '';
      position: absolute;
      inset: 0;
      background-image:
        linear-gradient(rgba(255, 255, 255, 0.04) 1px, transparent 1px),
        linear-gradient(90deg, rgba(255, 255, 255, 0.04) 1px, transparent 1px);
      background-size: 28px 28px;
      opacity: 0.24;
      pointer-events: none;
    }

    .pin-top,
    .pin-input-wrap {
      position: relative;
      z-index: 1;
    }

    .pin-top {
      text-align: center;
      padding-top: 4px;
    }

    .brand-logo {
      width: 72px;
      height: 72px;
      object-fit: cover;
      border-radius: 18px;
      margin: 0 auto 10px;
      display: block;
      border: 2px solid rgba(255, 255, 255, 0.36);
      box-shadow: 0 12px 28px rgba(0, 0, 0, 0.32);
      background: #fff;
    }

    .brand-text {
      margin: 0;
      font-size: 30px;
      line-height: 1.08;
      font-weight: 800;
      letter-spacing: 0.3px;
      color: #ffffff;
    }

    .brand-tagline {
      margin: 6px 0 0;
      font-size: 11px;
      letter-spacing: 1.8px;
      font-weight: 700;
      text-transform: uppercase;
      color: var(--brand-soft);
    }

    .pin-subtext {
      margin: 14px 0 8px;
      font-size: 20px;
      letter-spacing: 0.2px;
      color: var(--text-dim);
      font-weight: 600;
    }

    .pin-dots {
      display: flex;
      justify-content: center;
      gap: 10px;
      margin: 10px 0 10px;
    }

    .pin-dots span {
      width: 12px;
      height: 12px;
      border-radius: 999px;
      border: 1px solid rgba(255, 255, 255, 0.7);
      background: transparent;
      display: inline-block;
      transition: transform .15s ease, background .15s ease;
    }

    .pin-dots span.filled {
      background: var(--brand);
      border-color: var(--brand);
      transform: scale(1.08);
    }

    .pin-alert {
      margin: 0 auto 10px;
      max-width: 360px;
      font-size: 13px;
      line-height: 1.35;
      padding: 10px 13px;
      border-radius: 12px;
      backdrop-filter: blur(6px);
    }

    .pin-alert.success {
      background: rgba(22, 163, 74, 0.2);
      border: 1px solid rgba(34, 197, 94, 0.42);
    }

    .pin-alert.error {
      background: rgba(220, 38, 38, 0.2);
      border: 1px solid rgba(248, 113, 113, 0.42);
    }

    .hidden-form { display: none; }

    .pin-input-wrap {
      flex: 1;
      display: flex;
      flex-direction: column;
      justify-content: center;
      width: 100%;
      max-width: 372px;
      margin: 0 auto;
      gap: 18px;
    }

    .keypad {
      width: 100%;
      display: grid;
      grid-template-columns: repeat(3, minmax(0, 1fr));
      gap: 12px;
      justify-items: center;
    }

    .key {
      width: 96px;
      height: 72px;
      border-radius: 22px;
      border: 1px solid rgba(255, 255, 255, 0.16);
      background: linear-gradient(165deg, rgba(255, 255, 255, 0.16) 0%, rgba(255, 255, 255, 0.06) 100%);
      color: #fff;
      font-size: 36px;
      font-weight: 700;
      cursor: pointer;
      transition: transform .15s ease, border-color .15s ease, background .15s ease;
      box-shadow: 0 10px 24px rgba(3, 7, 18, 0.35);
      backdrop-filter: blur(6px);
    }

    .key:hover {
      transform: translateY(-2px);
      border-color: rgba(243, 179, 61, 0.9);
      background: linear-gradient(165deg, rgba(243, 179, 61, 0.26) 0%, rgba(243, 179, 61, 0.12) 100%);
    }

    .key:active {
      transform: scale(0.97);
      background: linear-gradient(165deg, rgba(243, 179, 61, 0.3) 0%, rgba(243, 179, 61, 0.16) 100%);
    }

    .key.empty {
      visibility: hidden;
    }

    .key-delete {
      width: 96px;
      height: 72px;
      border-radius: 22px;
      border: 1px solid rgba(255, 255, 255, 0.2);
      background: linear-gradient(165deg, rgba(255, 255, 255, 0.14) 0%, rgba(255, 255, 255, 0.05) 100%);
      cursor: pointer;
      position: relative;
      font-size: 0;
      box-shadow: 0 10px 24px rgba(3, 7, 18, 0.35);
      transition: transform .15s ease, border-color .15s ease, background .15s ease;
    }

    .key-delete:hover {
      transform: translateY(-2px);
      border-color: rgba(243, 179, 61, 0.9);
      background: linear-gradient(165deg, rgba(243, 179, 61, 0.22) 0%, rgba(243, 179, 61, 0.1) 100%);
    }

    .key-delete:active {
      transform: scale(0.97);
    }

    .key-delete::before,
    .key-delete::after {
      content: '';
      position: absolute;
      top: 50%;
      left: 50%;
      width: 22px;
      height: 2px;
      background: #f6f8ff;
      transform-origin: center;
    }

    .key-delete::before {
      transform: translate(-50%, -50%) rotate(45deg);
    }

    .key-delete::after {
      transform: translate(-50%, -50%) rotate(-45deg);
    }

    .pin-bottom {
      display: flex;
      justify-content: center;
      align-items: center;
      gap: 12px;
      margin-top: 2px;
    }

    .pin-bottom a,
    .forgot-trigger {
      color: #edf2ff;
      text-decoration: none;
      font-size: 13px;
      font-weight: 600;
      padding: 10px 14px;
      border-radius: 999px;
      border: 1px solid rgba(255, 255, 255, 0.2);
      background: rgba(255, 255, 255, 0.06);
      transition: background .15s ease, border-color .15s ease, transform .15s ease;
    }

    .pin-bottom a:hover,
    .forgot-trigger:hover {
      background: rgba(243, 179, 61, 0.18);
      border-color: rgba(243, 179, 61, 0.76);
      transform: translateY(-1px);
    }

    .pin-bottom a:active,
    .forgot-trigger:active {
      transform: scale(0.98);
    }

    .forgot-trigger {
      background: rgba(255, 255, 255, 0.06);
    }

    @media (max-height: 740px) {
      .pin-shell { padding-top: 12px; padding-bottom: 10px; }
      .brand-logo { width: 62px; height: 62px; margin-bottom: 8px; }
      .brand-text { font-size: 26px; }
      .pin-subtext { margin-top: 10px; font-size: 18px; }
      .pin-input-wrap { gap: 14px; }
      .key,
      .key-delete { width: 90px; height: 66px; border-radius: 20px; }
      .key { font-size: 32px; }
    }

    @media (max-width: 430px) {
      .pin-shell { padding-left: 14px; padding-right: 14px; }
      .brand-logo { width: 58px; height: 58px; border-radius: 16px; }
      .brand-text { font-size: 24px; }
      .brand-tagline { letter-spacing: 1.6px; }
      .pin-subtext { font-size: 17px; }
      .pin-input-wrap { max-width: 320px; gap: 12px; }
      .key,
      .key-delete { width: 82px; height: 62px; border-radius: 18px; }
      .key { font-size: 30px; }
      .pin-bottom { gap: 10px; flex-wrap: wrap; }
      .pin-bottom a,
      .forgot-trigger { padding: 9px 12px; }
    }
  </style>
</head>
<body>
  <div class="pin-shell">
    <div class="pin-top">
      <img src="logo.jpg" alt="Administrative System" class="brand-logo">
      <h1 class="brand-text">Administrative System</h1>
      <p class="brand-tagline">Andrea Mystery Shop Owner Access</p>
      <p class="pin-subtext" id="pinSubtext"><?php echo $hasAccessCode ? 'Enter your 4-digit code' : 'Create your 4-digit code'; ?></p>

      <?php if ($flashMessage !== ''): ?>
        <div class="pin-alert success"><?php echo htmlspecialchars($flashMessage); ?></div>
      <?php endif; ?>
      <?php if ($flashError !== ''): ?>
        <div class="pin-alert error"><?php echo htmlspecialchars($flashError); ?></div>
      <?php endif; ?>

      <div class="pin-dots" id="pinDots">
        <span></span><span></span><span></span><span></span>
      </div>
    </div>

    <form id="pinSubmitForm" method="POST" class="hidden-form">
      <input type="hidden" name="action" id="pinAction" value="<?php echo $hasAccessCode ? 'verify_code' : 'create_code'; ?>">
      <input type="hidden" name="access_code" id="pinAccessCode" value="">
      <input type="hidden" name="new_code" id="pinNewCode" value="">
      <input type="hidden" name="confirm_code" id="pinConfirmCode" value="">
    </form>

    <div class="pin-input-wrap">
      <div class="keypad">
        <button type="button" class="key" data-key="1">1</button>
        <button type="button" class="key" data-key="2">2</button>
        <button type="button" class="key" data-key="3">3</button>
        <button type="button" class="key" data-key="4">4</button>
        <button type="button" class="key" data-key="5">5</button>
        <button type="button" class="key" data-key="6">6</button>
        <button type="button" class="key" data-key="7">7</button>
        <button type="button" class="key" data-key="8">8</button>
        <button type="button" class="key" data-key="9">9</button>
        <button type="button" class="key empty" aria-hidden="true"></button>
        <button type="button" class="key" data-key="0">0</button>
        <button type="button" class="key-delete" id="keyDelete" aria-label="Delete"></button>
      </div>

      <div class="pin-bottom">
        <a href="admin_profile.php">Back to Profile</a>
        <a class="forgot-trigger" href="owner_send_reset_otp.php">Forgot Code?</a>
      </div>
    </div>
  </div>

  <script>
    (function () {
      var hasAccessCode = <?php echo $hasAccessCode ? 'true' : 'false'; ?>;
      var pin = '';
      var createStep = 1;
      var firstCreateCode = '';
      var dots = Array.prototype.slice.call(document.querySelectorAll('#pinDots span'));
      var subtext = document.getElementById('pinSubtext');

      function renderDots() {
        dots.forEach(function (dot, idx) {
          dot.classList.toggle('filled', idx < pin.length);
        });
      }

      function resetPin() {
        pin = '';
        renderDots();
      }

      function submitPin() {
        var form = document.getElementById('pinSubmitForm');
        var actionField = document.getElementById('pinAction');
        var accessField = document.getElementById('pinAccessCode');
        var newField = document.getElementById('pinNewCode');
        var confirmField = document.getElementById('pinConfirmCode');

        if (hasAccessCode) {
          actionField.value = 'verify_code';
          accessField.value = pin;
          form.submit();
          return;
        }

        if (createStep === 1) {
          firstCreateCode = pin;
          createStep = 2;
          resetPin();
          if (subtext) {
            subtext.textContent = 'Confirm your 4-digit code';
          }
          return;
        }

        actionField.value = 'create_code';
        newField.value = firstCreateCode;
        confirmField.value = pin;
        form.submit();
      }

      document.querySelectorAll('.key[data-key]').forEach(function (btn) {
        btn.addEventListener('click', function () {
          if (pin.length >= 4) {
            return;
          }
          pin += String(btn.getAttribute('data-key') || '');
          renderDots();
          if (pin.length === 4) {
            window.setTimeout(submitPin, 120);
          }
        });
      });

      var deleteBtn = document.getElementById('keyDelete');
      if (deleteBtn) {
        deleteBtn.addEventListener('click', function () {
          if (!pin.length) {
            return;
          }
          pin = pin.slice(0, -1);
          renderDots();
        });
      }

      renderDots();
    })();
  </script>
</body>
</html>
