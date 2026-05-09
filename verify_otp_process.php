<?php
session_start();
include 'dbConnection.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $enteredOtp = isset($_POST['otp']) ? $_POST['otp'] : '';
    $enteredOtp = preg_replace('/\s+/', '', trim($enteredOtp));
    $enteredOtp = (string)$enteredOtp;
    
    error_log('=== OTP Verification Debug ===');
    error_log('Entered OTP: "' . $enteredOtp . '" (length: ' . strlen($enteredOtp) . ')');

    $isOwnerReset = !empty($_SESSION['owner_reset_mode']);
    $isReset = false;
    $expectedOtp = null;
    
    if ($isOwnerReset && isset($_SESSION['reset_otp'])) {
        $expectedOtp = (string)$_SESSION['reset_otp'];
        $isReset = true;
        error_log('Owner reset flow - reset_otp set');
    } elseif (isset($_SESSION['reset_otp']) && !$isOwnerReset) {
        $expectedOtp = (string)$_SESSION['reset_otp'];
        $isReset = true;
        error_log('User reset flow - reset_otp set');
    } elseif (isset($_SESSION['otp']) && !$isReset) {
        $expectedOtp = (string)$_SESSION['otp'];
        error_log('Registration flow - otp set');
    } else {
        $expectedOtp = null;
        error_log('No OTP found in session');
    }

    error_log('Expected OTP: "' . ($expectedOtp ?? 'NULL') . '" (length: ' . (isset($expectedOtp) ? strlen($expectedOtp) : 0) . ')');

    if (!$expectedOtp) {
        $redirect = 'verify_otp.php?error=' . urlencode('OTP expired. Please start over.');
        if ($isOwnerReset) {
            $redirect = 'verify_otp.php?error=' . urlencode('OTP expired. Please request a new code.');
        } elseif ($isReset) {
            $redirect = 'LogIn.php?error=' . urlencode('OTP expired. Please try again.');
        }
        error_log('OTP verification failed: OTP expired');
        header("Location: $redirect");
        exit();
    }

    $match = ($enteredOtp === $expectedOtp);
    error_log('OTP Match Result: ' . ($match ? 'YES' : 'NO'));
    error_log('Details - Entered: "' . $enteredOtp . '" vs Expected: "' . $expectedOtp . '"');
    
    if ($match) {
        if ($isOwnerReset) {
            unset($_SESSION['reset_otp']);
            $_SESSION['owner_reset_verified'] = true;
            error_log('Owner reset verified');
            header('Location: owner_new_pin.php');
            exit();
        }

        if ($isReset) {
            unset($_SESSION['reset_otp']);
            $_SESSION['reset_verified'] = true;
            error_log('User reset verified');
            header('Location: reset_password.php');
            exit();
        }

        error_log('Registration verification - creating user account');
        $fullname = $_SESSION['fullname'] ?? '';
        $email = $_SESSION['email'] ?? '';
        $password = $_SESSION['password'] ?? '';

        $insertSql = 'INSERT INTO users (full_name, email, password) VALUES (?, ?, ?)';
        if ($stmt = $conn->prepare($insertSql)) {
            $stmt->bind_param('sss', $fullname, $email, $password);
            if ($stmt->execute()) {
                $new_user_id = $conn->insert_id;
                error_log('User created with ID: ' . $new_user_id);
                $stmt->close();
                
                $cartCreated = false;
                $maxRetries = 3;
                for ($attempt = 0; $attempt < $maxRetries; $attempt++) {
                    $cartSql = 'INSERT INTO carts (user_id) VALUES (?)';
                    if ($cartStmt = $conn->prepare($cartSql)) {
                        $cartStmt->bind_param('i', $new_user_id);
                        if ($cartStmt->execute()) {
                            $cartStmt->close();
                            $cartCreated = true;
                            error_log('Cart created for user_id: ' . $new_user_id);
                            break;
                        } else {
                            error_log('DB execute failed (create cart, attempt ' . ($attempt + 1) . '): ' . $cartStmt->error);
                            $cartStmt->close();
                        }
                    } else {
                        error_log('DB prepare failed (create cart, attempt ' . ($attempt + 1) . '): ' . $conn->error);
                    }
                }
                
                if (!$cartCreated) {
                    error_log('Failed to create cart for user_id: ' . $new_user_id);
                }
                
                session_destroy();
                error_log('Registration complete');
                header('Location: LogIn.php?registered=1');
                exit();
            } else {
                error_log('DB execute failed (insert users): ' . $stmt->error);
                $stmt->close();
                header('Location: verify_otp.php?error=' . urlencode('Database error'));
                exit();
            }
        } else {
            error_log('DB prepare failed (insert users): ' . $conn->error);
            header('Location: verify_otp.php?error=' . urlencode('Database error'));
            exit();
        }

    } else {
        error_log('OTP verification FAILED: Mismatch');
        header("Location: verify_otp.php?error=Invalid OTP! Try again.");
        exit();
    }
}
