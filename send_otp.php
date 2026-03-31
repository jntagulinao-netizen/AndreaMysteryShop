<?php
session_start();
include 'dbConnection.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'phpmailer/PHPMailer.php';
require 'phpmailer/SMTP.php';
require 'phpmailer/Exception.php';

// Get POST data (safe checks) - accept multiple form field name variants
$fullname = '';
if (isset($_POST['fullname'])) {
    $fullname = trim($_POST['fullname']);
} elseif (isset($_POST['name'])) {
    $fullname = trim($_POST['name']);
}
$email = isset($_POST['email']) ? trim($_POST['email']) : '';
$password = isset($_POST['password']) ? $_POST['password'] : '';
$confirmPassword = '';
if (isset($_POST['confirmPassword'])) {
    $confirmPassword = $_POST['confirmPassword'];
} elseif (isset($_POST['confirm_password'])) {
    $confirmPassword = $_POST['confirm_password'];
}

// Validate inputs
if ($fullname === '' || $email === '' || $password === '' || $confirmPassword === '') {
    $_SESSION['register_err'] = 'All fields are required!';
    header('Location: LogIn.php?tab=register');
    exit();
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $_SESSION['register_err'] = 'Invalid email format!';
    header('Location: LogIn.php?tab=register');
    exit();
}
if ($password !== $confirmPassword) {
    $_SESSION['register_err'] = 'Passwords do not match!';
    header('Location: LogIn.php?tab=register');
    exit();
}
// enforce complexity: at least 8 chars, one uppercase, one special
if (!preg_match('/^(?=.*[A-Z])(?=.*[^A-Za-z0-9]).{8,}$/', $password)) {
    $_SESSION['register_err'] = 'Password must be at least 8 characters, include an uppercase letter and a special character.';
    header('Location: LogIn.php?tab=register');
    exit();
} 

// Check if email exists (match `users` table schema)
$checkSql = "SELECT user_id FROM users WHERE email = ? LIMIT 1";
if ($stmt = $conn->prepare($checkSql)) {
    $stmt->bind_param('s', $email);
    if (!$stmt->execute()) {
        error_log('DB execute failed (check email): ' . $stmt->error);
        $stmt->close();
        $_SESSION['register_err'] = 'Database error occurred. Please try again.';
        header('Location: LogIn.php?tab=register');
        exit();
    }
    $stmt->store_result();
    if ($stmt->num_rows > 0) {
        $stmt->close();
        $_SESSION['register_err'] = 'Email already exists! Try logging in or use a different email.';
        header('Location: LogIn.php?tab=register');
        exit();
    }
    $stmt->close();
} else {
    error_log('DB prepare failed (check email): ' . $conn->error);
    $_SESSION['register_err'] = 'Database error occurred. Please try again.';
    header('Location: LogIn.php?tab=register');
    exit();
}

// Generate OTP
$otp = rand(111111, 999999);

// Debug log: start
error_log('send_otp invoked: fullname=' . $fullname . ' email=' . $email);

// Save temporary data in session
$_SESSION['otp'] = $otp;
$_SESSION['fullname'] = $fullname;
$_SESSION['email'] = $email;
$_SESSION['password'] = password_hash($password, PASSWORD_DEFAULT);

// Send OTP via PHPMailer
$mail = new PHPMailer(true);

try {
    $mail->isSMTP();
    $mail->Host = 'smtp.gmail.com';
    $mail->SMTPAuth = true;
    $mail->Username = 'andreamysteryshop@gmail.com';  // SMTP username (email)
    $mail->Password = 'djdimmouiwecwoxg';     // App Password
    $mail->SMTPSecure = 'tls';
    $mail->Port = 587;

    // Disable verbose SMTP debug for normal operation
    $mail->SMTPDebug = 0;
    $mail->Debugoutput = function($str, $level) { error_log("PHPMailer debug: [level $level] $str"); };

    $mail->setFrom('andreamysteryshop@gmail.com', 'Andrea Mystery Shop');
    $mail->addAddress($email);

    $mail->Subject = 'Verify Your Andrea Mystery Shop Account';
    
    // Use styled HTML email template with injected OTP and user details
    $mailBody = <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>Verify Your Account</title>
  <style>
    body {
      margin: 0;
      padding: 0;
      font-family: "Helvetica Neue", Helvetica, Arial, sans-serif;
      color: #333;
      background-color: #fff;
    }
    .container {
      margin: 0 auto;
      width: 100%;
      max-width: 600px;
      padding: 0 0px;
      padding-bottom: 10px;
      border-radius: 5px;
      line-height: 1.8;
    }
    .header {
      border-bottom: 1px solid #eee;
    }
    .header a {
      font-size: 1.4em;
      color: #000;
      text-decoration: none;
      font-weight: 600;
    }
    .otp {
      background: linear-gradient(to right, #00bc69 0, #00bc88 50%, #00bca8 100%);
      margin: 0 auto;
      width: max-content;
      padding: 10px 15px;
      color: #fff;
      border-radius: 4px;
      font-weight: bold;
      font-size: 1.5em;
    }
    .footer {
      color: #aaa;
      font-size: 0.8em;
      line-height: 1;
      font-weight: 300;
    }
    .email-info {
      color: #666666;
      font-weight: 400;
      font-size: 13px;
      line-height: 18px;
      padding-bottom: 6px;
    }
    .email-info a {
      text-decoration: none;
      color: #00bc69;
    }
  </style>
</head>
<body>
  <div class="container">
    <div class="header">
      <a>Verify Your Andrea Mystery Shop Account</a>
    </div>
    <br />
    <strong>Dear $fullname,</strong>
    <p>
      Welcome to Andrea Mystery Shop! We're thrilled you've joined our community of mystery shoppers.
    </p>
    <p>
      For security purposes, please verify your identity by providing the following One-Time Password (OTP).
      <br />
      <b>Your One-Time Password (OTP) verification code is:</b>
    </p>
    <div style="text-align: center;">
      <div class="otp">$otp</div>
    </div>
    <p style="font-size: 0.9em">
      <strong>One-Time Password (OTP) is valid for 3 minutes.</strong>
      <br /><br />
      If you did not initiate this request, please disregard this message. Please ensure the confidentiality of your OTP and do not share it with anyone.<br />
      <strong>Do not forward or give this code to anyone.</strong>
      <br /><br />
      If you have any questions or need assistance, our support team is here to help.
      <br /><br />
      <strong>Thank you for using Andrea Mystery Shop.</strong>
      <br /><br />
      Best regards,<br />
      <strong>Andrea Mystery Shop Team</strong>
    </p>
    <hr style="border: none; border-top: 0.5px solid #131111" />
    <div class="footer">
      <p>This email can't receive replies.</p>
      <p>For more information about Andrea Mystery Shop and your account, please contact support.</p>
    </div>
  </div>
  <div style="text-align: center">
    <div class="email-info">
      <span>This email was sent to <a href="mailto:$email">$email</a></span>
    </div>
    <div class="email-info">
      Andrea Mystery Shop | Support
    </div>
    <div class="email-info">
      &copy; 2024 Andrea Mystery Shop. All rights reserved.
    </div>
  </div>
</body>
</html>
HTML;
    $mail->isHTML(true);
    $mail->Body = $mailBody;

    if (!$mail->send()) {
        error_log('PHPMailer send failed: ' . $mail->ErrorInfo);
        // Fallback: save the email to a local file for development/debugging
        $html = "<html><body><p>Hello " . htmlspecialchars($fullname) . ",</p><p>Your OTP code is: <strong>" . htmlspecialchars($otp) . "</strong></p><p>Use this to complete your registration.</p></body></html>";
        @file_put_contents(__DIR__ . '/last_otp_email.html', $html);
        @file_put_contents(__DIR__ . '/last_otp_email_mime.txt', "To: $email\nSubject: Your OTP Code\n\nHello $fullname\nYour OTP code is: $otp\n");
        $_SESSION['otp_debug'] = 'OTP saved to last_otp_email.html (development fallback).';
        error_log('send_otp: fallback saved OTP to last_otp_email.html for ' . $email);
        // keep OTP in session for verification
        header('Location: verify_otp.php');
        exit();
    }

    // Redirect to OTP verification page
    unset($_SESSION['otp_debug']);
    error_log('send_otp: mail->send() succeeded for ' . $email);
    header('Location: verify_otp.php');
    exit();

} catch (Exception $e) {
    error_log('PHPMailer exception: ' . $e->getMessage());
    // Fallback: save the email to a local file for development/debugging
    $html = "<html><body><p>Hello " . htmlspecialchars($fullname) . ",</p><p>Your OTP code is: <strong>" . htmlspecialchars($otp) . "</strong></p><p>Use this to complete your registration.</p></body></html>";
    @file_put_contents(__DIR__ . '/last_otp_email.html', $html);
    @file_put_contents(__DIR__ . '/last_otp_email_mime.txt', "To: $email\nSubject: Your OTP Code\n\nHello $fullname\nYour OTP code is: $otp\n");
    $_SESSION['otp_debug'] = 'OTP saved to last_otp_email.html (PHPMailer exception fallback).';
    header('Location: verify_otp.php');
    exit();
}
