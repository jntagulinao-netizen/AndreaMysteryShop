<?php
session_start();
header('Content-Type: application/json');
include 'dbConnection.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'phpmailer/PHPMailer.php';
require 'phpmailer/SMTP.php';
require 'phpmailer/Exception.php';

// determine whether this is a registration or reset flow
$email = '';
$fullname = '';
$isReset = false;
if (isset($_SESSION['reset_email'])) {
    $email = $_SESSION['reset_email'];
    $fullname = $_SESSION['reset_name'] ?? '';
    $isReset = true;
} else {
    $email = isset($_SESSION['email']) ? $_SESSION['email'] : '';
    $fullname = isset($_SESSION['fullname']) ? $_SESSION['fullname'] : '';
}

if (!$email) {
    echo json_encode(['status' => 'error', 'message' => 'No email in session. Please restart the flow.']);
    exit();
}

// Generate new OTP
$otp = rand(111111, 999999);
if ($isReset) {
    $_SESSION['reset_otp'] = $otp;
} else {
    $_SESSION['otp'] = $otp;
}

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
    $mail->Subject = $isReset ? 'Password Reset OTP (resend)' : 'Your Andrea Mystery Shop OTP (resend)';

        // Use same styled HTML template as send_otp.php
        $mailBody = <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <title>Verify Your Account</title>
    <style>
        body { margin: 0; padding: 0; font-family: "Helvetica Neue", Helvetica, Arial, sans-serif; color: #333; background-color: #fff; }
        .container { margin: 0 auto; width: 100%; max-width: 600px; padding: 0 0px; padding-bottom: 10px; border-radius: 5px; line-height: 1.8; }
        .header { border-bottom: 1px solid #eee; }
        .header a { font-size: 1.4em; color: #000; text-decoration: none; font-weight: 600; }
        .otp { background: linear-gradient(to right, #00bc69 0, #00bc88 50%, #00bca8 100%); margin: 0 auto; width: max-content; padding: 10px 15px; color: #fff; border-radius: 4px; font-weight: bold; font-size: 1.5em; }
        .footer { color: #aaa; font-size: 0.8em; line-height: 1; font-weight: 300; }
        .email-info { color: #666666; font-weight: 400; font-size: 13px; line-height: 18px; padding-bottom: 6px; }
        .email-info a { text-decoration: none; color: #00bc69; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <a>Verify Your Andrea Mystery Shop Account</a>
        </div>
        <br />
        <strong>Dear {$fullname},</strong>
        <p>
            Welcome to Andrea Mystery Shop! We're thrilled you've joined our community of mystery shoppers.
        </p>
        <p>
            For security purposes, please verify your identity by providing the following One-Time Password (OTP).
            <br />
            <b>Your One-Time Password (OTP) verification code is:</b>
        </p>
        <div style="text-align: center;">
            <div class="otp">{$otp}</div>
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
            <span>This email was sent to <a href="mailto:{$email}">{$email}</a></span>
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
                error_log('resend_otp: PHPMailer send failed: ' . $mail->ErrorInfo);
                // Fallback: save the same HTML email for development/debugging
                @file_put_contents(__DIR__ . '/last_otp_email.html', $mailBody);
                @file_put_contents(__DIR__ . '/last_otp_email_mime.txt', "To: $email\nSubject: Your OTP Code\n\nHello $fullname\nYour OTP code is: $otp\n");
                $_SESSION['otp_debug'] = 'OTP saved to last_otp_email.html (resend fallback).';
                echo json_encode(['status' => 'ok', 'message' => 'OTP saved to last_otp_email.html (development).']);
                exit();
        }

        // Success
        unset($_SESSION['otp_debug']);
        echo json_encode(['status' => 'ok', 'message' => 'OTP resent successfully.']);
        exit();

} catch (Exception $e) {
        error_log('resend_otp exception: ' . $e->getMessage());
        @file_put_contents(__DIR__ . '/last_otp_email.html', $mailBody);
        @file_put_contents(__DIR__ . '/last_otp_email_mime.txt', "To: $email\nSubject: Your OTP Code\n\nHello $fullname\nYour OTP code is: $otp\n");
        $_SESSION['otp_debug'] = 'OTP saved to last_otp_email.html (exception fallback).';
        echo json_encode(['status' => 'ok', 'message' => 'OTP saved to last_otp_email.html (exception).']);
        exit();
}


?>
