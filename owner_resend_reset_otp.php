<?php
session_start();
include 'dbConnection.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'phpmailer/PHPMailer.php';
require 'phpmailer/SMTP.php';
require 'phpmailer/Exception.php';

if (empty($_SESSION['owner_reset_mode']) || empty($_SESSION['reset_email']) || empty($_SESSION['user_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['status' => 'error', 'message' => 'Owner OTP session expired.']);
    exit();
}

$userId = (int)$_SESSION['user_id'];
$email = (string)$_SESSION['reset_email'];
$name = (string)($_SESSION['reset_name'] ?? 'Owner');

$otp = (string)random_int(100000, 999999);
$expiresAt = date('Y-m-d H:i:s', time() + 300);

$securityStmt = $conn->prepare('UPDATE admin_owner_security SET reset_otp = ?, reset_otp_expires = ?, reset_otp_verified = 0 WHERE user_id = ?');
if ($securityStmt) {
    $securityStmt->bind_param('ssi', $otp, $expiresAt, $userId);
    $securityStmt->execute();
    $securityStmt->close();
}

$_SESSION['reset_otp'] = $otp;

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
    $safeName = htmlspecialchars($name, ENT_QUOTES, 'UTF-8');
    $mail->Body = '<!doctype html><html><body style="font-family:Arial,sans-serif;color:#1f2937">'
        . '<h2 style="margin:0 0 12px">Owner Administrative Access OTP</h2>'
        . '<p>Hello ' . $safeName . ',</p>'
        . '<p>Your new OTP is:</p>'
        . '<p style="font-size:28px;font-weight:700;letter-spacing:4px;color:#0f62ce">' . $otp . '</p>'
        . '<p>This OTP expires in 5 minutes.</p>'
        . '</body></html>';
    $mail->send();
    header('Content-Type: application/json');
    echo json_encode(['status' => 'ok', 'message' => 'OTP resent to your email.']);
    exit();
} catch (Exception $e) {
    error_log('Owner OTP resend failed: ' . $mail->ErrorInfo);
    header('Content-Type: application/json');
    echo json_encode(['status' => 'error', 'message' => 'Failed to resend OTP.']);
    exit();
}
