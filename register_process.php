<?php
session_start();
require_once __DIR__ . '/dbConnection.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: LogIn.php');
    exit;
}

$name = '';
// support either `full_name` or `name` from a form
if (isset($_POST['full_name'])) {
    $name = trim($_POST['full_name']);
} elseif (isset($_POST['name'])) {
    $name = trim($_POST['name']);
}

$email = isset($_POST['email']) ? trim($_POST['email']) : '';
$password = isset($_POST['password']) ? $_POST['password'] : '';
$confirm = isset($_POST['confirm_password']) ? $_POST['confirm_password'] : '';

// Basic validation
if ($name === '' || $email === '' || $password === '' || $confirm === '') {
    $_SESSION['register_err'] = 'Please fill in all required fields.';
    header('Location: LogIn.php');
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $_SESSION['register_err'] = 'Invalid email address.';
    header('Location: LogIn.php');
    exit;
}

if ($password !== $confirm) {
    $_SESSION['register_err'] = 'Passwords do not match.';
    header('Location: LogIn.php');
    exit;
}

if (strlen($password) < 8) {
    $_SESSION['register_err'] = 'Password must be at least 8 characters.';
    header('Location: LogIn.php');
    exit;
}

// Check for existing user by email
$checkSql = 'SELECT user_id FROM users WHERE email = ? LIMIT 1';
if ($stmt = $conn->prepare($checkSql)) {
    $stmt->bind_param('s', $email);
    $stmt->execute();
    $stmt->store_result();
    if ($stmt->num_rows > 0) {
        $stmt->close();
        $_SESSION['register_err'] = 'An account with that email already exists.';
        header('Location: LogIn.php');
        exit;
    }
    $stmt->close();
} else {
    error_log('DB prepare failed (check email): ' . $conn->error);
    $_SESSION['register_err'] = 'Database error. Please try again later.';
    header('Location: LogIn.php');
    exit;
}

// Insert new user. Match your `users` table columns: `full_name`, `email`, `password`.
$password_hash = password_hash($password, PASSWORD_DEFAULT);
$insertSql = 'INSERT INTO users (full_name, email, password) VALUES (?, ?, ?)';
if ($stmt = $conn->prepare($insertSql)) {
    $stmt->bind_param('sss', $name, $email, $password_hash);
    if ($stmt->execute()) {
        $stmt->close();
        header('Location: LogIn.php?registered=1');
        exit;
    } else {
        error_log('DB execute failed (insert user): ' . $stmt->error);
        $stmt->close();
        $_SESSION['register_err'] = 'Failed to create account. Please try again later.';
        header('Location: LogIn.php');
        exit;
    }
} else {
    error_log('DB prepare failed (insert user): ' . $conn->error);
    $_SESSION['register_err'] = 'Database error. Please try again later.';
    header('Location: LogIn.php');
    exit;
}

?>
