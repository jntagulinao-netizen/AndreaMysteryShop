<?php
session_start();
include 'dbConnection.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $enteredOtp = trim($_POST['otp']);

    $isOwnerReset = !empty($_SESSION['owner_reset_mode']);

    // decide which flow we're in (registration vs reset)
    $isReset = false;
    if ($isOwnerReset && isset($_SESSION['reset_otp'])) {
        $expectedOtp = $_SESSION['reset_otp'];
        $isReset = true;
    } elseif (isset($_SESSION['reset_otp'])) {
        $expectedOtp = $_SESSION['reset_otp'];
        $isReset = true;
    } elseif (isset($_SESSION['otp'])) {
        $expectedOtp = $_SESSION['otp'];
    } else {
        $expectedOtp = null;
    }

    if (!$expectedOtp) {
        $redirect = 'verify_otp.php?error=' . urlencode('OTP expired. Please start over.');
        if ($isOwnerReset) {
            $redirect = 'verify_otp.php?error=' . urlencode('OTP expired. Please request a new code.');
        } elseif ($isReset) {
            $redirect = 'LogIn.php?error=' . urlencode('OTP expired. Please try again.');
        }
        header("Location: $redirect");
        exit();
    }

    if ($enteredOtp == $expectedOtp) {
        if ($isOwnerReset) {
            unset($_SESSION['reset_otp']);
            $_SESSION['owner_reset_verified'] = true;
            header('Location: owner_new_pin.php');
            exit();
        }

        if ($isReset) {
            unset($_SESSION['reset_otp']);
            $_SESSION['reset_verified'] = true;
            // keep reset_email in session for next step
            header('Location: reset_password.php');
            exit();
        }

        // registration path
        $fullname = $_SESSION['fullname'];
        $email = $_SESSION['email'];
        $password = $_SESSION['password'];

        $insertSql = 'INSERT INTO users (full_name, email, password) VALUES (?, ?, ?)';
        if ($stmt = $conn->prepare($insertSql)) {
            $stmt->bind_param('sss', $fullname, $email, $password);
            if ($stmt->execute()) {
                $stmt->close();
                session_destroy();
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
        header("Location: verify_otp.php?error=Invalid OTP! Try again.");
        exit();
    }
}
