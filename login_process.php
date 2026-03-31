<?php
session_start();
require_once 'dbConnection.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: LogIn.php');
    exit;
}

$email = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';

if (!$email || !$password) {
    $_SESSION['login_err'] = 'Please provide both email and password.';
    header('Location: LogIn.php');
    exit;
}

// retrieve user record according to provided schema
$stmt = $conn->prepare('SELECT user_id, full_name, password, role, status FROM users WHERE email = ?');
$stmt->bind_param('s', $email);
$stmt->execute();
$stmt->store_result();
if ($stmt->num_rows === 0) {
    // Debug: email not found
    error_log("Login attempt: Email '$email' not found in database");
    $_SESSION['login_err'] = 'Email not found. Please check your email address.';
    header('Location: LogIn.php');
    exit;
}
$stmt->bind_result($user_id, $full_name, $hash, $role, $status);
$stmt->fetch();
$stmt->close();

// Debug: check password
$password_matches = password_verify($password, $hash);
error_log("Login attempt: Email='$email', Hash exists=" . (!empty($hash) ? 'yes' : 'no') . ", Password match=$password_matches");

if (!$password_matches) {
    $_SESSION['login_err'] = 'Password is incorrect. Please try again.';
    header('Location: LogIn.php');
    exit;
}

if (strtolower($status) !== 'active') {
    $_SESSION['login_err'] = 'Your account is not active. Contact support.';
    header('Location: LogIn.php');
    exit;
}

// set session values
$_SESSION['user_id'] = $user_id;
$_SESSION['user_name'] = $full_name;
$_SESSION['user_role'] = $role;
$_SESSION['login_success'] = true;

// Determine redirect URL based on role
$r = strtolower($role);
if ($r === 'admin') {
    $redirect = 'admin_dashboard.php';
} elseif ($r === 'user') {
    $redirect = 'user_dashboard.php';
} else {
    $redirect = 'homePage.php';
}

// Return JSON for AJAX or redirect with success flag
if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
    header('Content-Type: application/json');
    echo json_encode(['success' => true, 'redirect' => $redirect]);
} else {
    header('Location: LogIn.php?login_success=1');
    exit;
    }
