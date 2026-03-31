<?php
session_start();
include 'dbConnection.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'phpmailer/PHPMailer.php';
require 'phpmailer/SMTP.php';
require 'phpmailer/Exception.php';

$email = isset($_POST['email']) ? trim($_POST['email']) : '';

if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $_SESSION['reset_err'] = 'Please provide a valid email address.';
    header('Location: forgot_password.php');
    exit();
}

// look up user
$sql = "SELECT user_id, full_name FROM users WHERE email = ? LIMIT 1";
if ($stmt = $conn->prepare($sql)) {
    $stmt->bind_param('s', $email);
    if (!$stmt->execute()) {
        error_log('DB execute failed (reset lookup): ' . $stmt->error);
        $stmt->close();
        $_SESSION['reset_err'] = 'Database error. Please try again.';
        header('Location: forgot_password.php');
        exit();
    }
    $stmt->store_result();
    if ($stmt->num_rows === 0) {
        $stmt->close();
        // for security, avoid indicating if email doesn't exist?
        $_SESSION['reset_err'] = 'No account found with that email.';
        header('Location: forgot_password.php');
        exit();
    }
    $stmt->bind_result($user_id, $fullname);
    $stmt->fetch();
    $stmt->close();
} else {
    error_log('DB prepare failed (reset lookup): ' . $conn->error);
    $_SESSION['reset_err'] = 'Database error. Please try again.';
    header('Location: forgot_password.php');
    exit();
}

// generate otp
$otp = rand(111111, 999999);

// store in session for later verification
$_SESSION['reset_otp'] = $otp;
$_SESSION['reset_email'] = $email;
$_SESSION['reset_name'] = $fullname;

// send email using same template as registration but adjusted subject
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
    $mail->Debugoutput = function($str, $level) { error_log("PHPMailer debug: [level $level] $str"); };

    $mail->setFrom('andreamysteryshop@gmail.com', 'Andrea Mystery Shop');
    $mail->addAddress($email);
    $mail->Subject = 'Password Reset Code';

    $mailBody = <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>Password Reset</title>
  <style>/* same styles as registration email */
    body{margin:0;padding:0;font-family:"Helvetica Neue",Helvetica,Arial,sans-serif;color:#333;background:#fff}
    .container{margin:0 auto;width:100%;max-width:600px;padding:0 0px;padding-bottom:10px;border-radius:5px;line-height:1.8}
    .header{border-bottom:1px solid #eee}
    .header a{font-size:1.4em;color:#000;text-decoration:none;font-weight:600}
    .otp{background:linear-gradient(to right,#00bc69 0,#00bc88 50%,#00bca8 100%);margin:0 auto;width:max-content;padding:10px 15px;color:#fff;border-radius:4px;font-weight:bold;font-size:1.5em}
    .footer{color:#aaa;font-size:0.8em;line-height:1;font-weight:300}
    .email-info{color:#666;font-weight:400;font-size:13px;line-height:18px;padding-bottom:6px}
    .email-info a{text-decoration:none;color:#00bc69}
  </style>
</head>
<body>
  <div class="container">
    <div class="header">
      <a>Password Reset Code</a>
    </div>
    <br />
    <strong>Dear {$fullname},</strong>
    <p>We received a request to reset the password for your Andrea Mystery Shop account.</p>
    <p>Your One-Time Password (OTP) is:</p>
    <div style="text-align:center"><div class="otp">{$otp}</div></div>
    <p style="font-size:0.9em">
      The code is valid for 3 minutes. If you did not request this, please ignore this email.
      Do not share this code with anyone.
    </p>
    <hr style="border:none;border-top:0.5px solid #131111"/>
    <div class="footer">
      <p>This email can't receive replies.</p>
      <p>Contact support if you need assistance.</p>
    </div>
  </div>
</body>
</html>
HTML;

    $mail->Body = $mailBody;
    $mail->isHTML(true);
    $mail->send();
} catch (Exception $e) {
    error_log('Reset OTP email failed: ' . $mail->ErrorInfo);
    // still allow user to proceed; they can try again
}

// redirect to OTP verification page
header('Location: verify_otp.php');
exit();
