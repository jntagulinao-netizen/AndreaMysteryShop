<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: LogIn.php');
    exit;
}
$role = $_SESSION['user_role'] ?? 'user';
if ($role !== 'admin' || empty($_SESSION['owner_reset_mode']) || empty($_SESSION['owner_reset_verified'])) {
    header('Location: owner_admin_access.php');
    exit;
}

require_once 'dbConnection.php';

$userId = (int)$_SESSION['user_id'];
$newPin = trim((string)($_POST['new_pin'] ?? ''));
$confirmPin = trim((string)($_POST['confirm_pin'] ?? ''));

if (!preg_match('/^\d{4}$/', $newPin) || !preg_match('/^\d{4}$/', $confirmPin)) {
    $_SESSION['owner_new_pin_error'] = 'PIN must be exactly 4 digits.';
    header('Location: owner_new_pin.php');
    exit;
}

if ($newPin !== $confirmPin) {
    $_SESSION['owner_new_pin_error'] = 'PIN confirmation does not match.';
    header('Location: owner_new_pin.php');
    exit;
}

$hash = password_hash($newPin, PASSWORD_DEFAULT);
$saveStmt = $conn->prepare('UPDATE admin_owner_security SET access_code_hash = ?, reset_otp = NULL, reset_otp_expires = NULL, reset_otp_verified = 0 WHERE user_id = ?');
if (!$saveStmt) {
    $_SESSION['owner_new_pin_error'] = 'Unable to save new PIN right now. Please try again.';
    header('Location: owner_new_pin.php');
    exit;
}

$saveStmt->bind_param('si', $hash, $userId);
if (!$saveStmt->execute()) {
    $saveStmt->close();
    $_SESSION['owner_new_pin_error'] = 'Unable to save new PIN right now. Please try again.';
    header('Location: owner_new_pin.php');
    exit;
}
$saveStmt->close();

unset($_SESSION['owner_reset_mode']);
unset($_SESSION['owner_reset_verified']);
unset($_SESSION['reset_otp']);
unset($_SESSION['reset_email']);
unset($_SESSION['reset_name']);
$_SESSION['owner_admin_flash_message'] = 'New PIN saved successfully.';

header('Location: owner_admin_access.php');
exit;
