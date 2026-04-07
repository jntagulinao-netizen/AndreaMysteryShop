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

$isOwnerStmt = $conn->prepare("SELECT is_owner FROM users WHERE user_id = ? AND LOWER(role) = 'admin' LIMIT 1");
$isOwnerAdmin = false;
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

$createOwnerSecuritySql = "CREATE TABLE IF NOT EXISTS `admin_owner_security` (
  `user_id` int(11) NOT NULL,
  `access_code_hash` varchar(255) DEFAULT NULL,
  `reset_otp` varchar(6) DEFAULT NULL,
  `reset_otp_expires` datetime DEFAULT NULL,
  `reset_otp_verified` tinyint(1) NOT NULL DEFAULT 0,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`user_id`),
  CONSTRAINT `fk_admin_owner_security_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci";
$conn->query($createOwnerSecuritySql);

$seedStmt = $conn->prepare('INSERT IGNORE INTO admin_owner_security (user_id) VALUES (?)');
if ($seedStmt) {
    $seedStmt->bind_param('i', $userId);
    $seedStmt->execute();
    $seedStmt->close();
}

$otp = (string)random_int(100000, 999999);
$expiresAt = date('Y-m-d H:i:s', time() + 300);

$otpStmt = $conn->prepare('UPDATE admin_owner_security SET reset_otp = ?, reset_otp_expires = ?, reset_otp_verified = 0 WHERE user_id = ?');
$otpStmt->bind_param('ssi', $otp, $expiresAt, $userId);
$otpStmt->execute();
$otpStmt->close();

$_SESSION['reset_otp'] = $otp;
$_SESSION['reset_email'] = $user['email'] ?? '';
$_SESSION['reset_name'] = $user['full_name'] ?? 'Owner';
$_SESSION['owner_reset_mode'] = true;
unset($_SESSION['owner_reset_verified']);

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
    $mail->addAddress((string)($user['email'] ?? ''));
    $mail->isHTML(true);
    $mail->Subject = 'Owner Administrative Access OTP';
    $safeName = htmlspecialchars((string)($user['full_name'] ?? 'Owner'), ENT_QUOTES, 'UTF-8');
    $mail->Body = '<!doctype html><html><body style="font-family:Arial,sans-serif;color:#1f2937">'
        . '<h2 style="margin:0 0 12px">Owner Administrative Access OTP</h2>'
        . '<p>Hello ' . $safeName . ',</p>'
        . '<p>Your OTP to continue to your administrative access screen is:</p>'
        . '<p style="font-size:28px;font-weight:700;letter-spacing:4px;color:#0f62ce">' . $otp . '</p>'
        . '<p>This OTP expires in 5 minutes. If you did not request this, you can ignore this message.</p>'
        . '</body></html>';
    $mail->send();
} catch (Exception $e) {
    error_log('Owner OTP email failed: ' . $mail->ErrorInfo);
}

header('Location: verify_otp.php');
exit;
