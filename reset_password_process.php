<?php
session_start();
include 'dbConnection.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: LogIn.php');
    exit();
}

if (!isset($_SESSION['reset_verified']) || !$_SESSION['reset_verified'] || !isset($_SESSION['reset_email'])) {
    $_SESSION['reset_err'] = 'Unauthorized operation.';
    header('Location: LogIn.php');
    exit();
}

$new = $_POST['new_password'] ?? '';
$confirm = $_POST['confirm_password'] ?? '';
if (!$new || !$confirm) {
    $_SESSION['reset_err'] = 'Please fill out both password fields.';
    header('Location: reset_password.php');
    exit();
}
if ($new !== $confirm) {
    $_SESSION['reset_err'] = 'Passwords do not match.';
    header('Location: reset_password.php');
    exit();
}
// length already checked above but make explicit
if (strlen($new) < 8) {
    $_SESSION['reset_err'] = 'Password must be at least 8 characters.';
    header('Location: reset_password.php');
    exit();
}
// complexity rules
if (!preg_match('/^(?=.*[A-Z])(?=.*[^A-Za-z0-9]).{8,}$/', $new)) {
    $_SESSION['reset_err'] = 'Password must include at least one uppercase letter and one special character.';
    header('Location: reset_password.php');
    exit();
}

$email = $_SESSION['reset_email'];
$hash = password_hash($new, PASSWORD_DEFAULT);

$updateSql = 'UPDATE users SET password = ? WHERE email = ?';
if ($stmt = $conn->prepare($updateSql)) {
    $stmt->bind_param('ss', $hash, $email);
    if ($stmt->execute()) {
        $stmt->close();
        // clear session state completely
        session_unset();
        session_destroy();
        header('Location: LogIn.php?password_reset=1');
        exit();
    } else {
        error_log('DB execute failed (update password): ' . $stmt->error);
        $stmt->close();
        $_SESSION['reset_err'] = 'Database error. Please try again.';
        header('Location: reset_password.php');
        exit();
    }
} else {
    error_log('DB prepare failed (update password): ' . $conn->error);
    $_SESSION['reset_err'] = 'Database error. Please try again.';
    header('Location: reset_password.php');
    exit();
}
