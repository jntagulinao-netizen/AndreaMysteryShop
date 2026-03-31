<?php
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Forgot Password — LUXE</title>
    <link rel="stylesheet" href="main.css">
    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        /* reuse styles from LogIn.php */
        :root{--accent:#df0c0c}
        body{background:#f5f7fb}
        .auth-hero{display:grid;grid-template-columns:1fr;min-height:80vh;align-items:center;gap:24px;padding:24px}
        @media(min-width:900px){.auth-hero{grid-template-columns:1fr 480px;min-height:84vh;padding:48px}}
        .hero-bg{position:relative;border-radius:14px;overflow:hidden;min-height:360px;background:#333}
        .hero-bg img{width:100%;height:100%;object-fit:cover;display:block;filter:brightness(.55) saturate(.95)}
        .hero-content{position:absolute;left:24px;bottom:24px;color:#fff;max-width:60%}
        .brand{display:flex;align-items:center;gap:12px;color:#fff;font-weight:700}
        .panel{background:#fff;padding:28px;border-radius:12px;box-shadow:0 10px 40px rgba(2,6,23,0.08)}
        .panel h1{margin:0 0 8px;font-size:20px}
        .panel p.lead{margin:0 0 16px;color:#6b7280}
        .form-row{display:flex;flex-direction:column;gap:8px;margin-bottom:12px}
        input{padding:12px;border-radius:10px;border:1px solid #e6e9ef}
        .btn-primary{background:var(--accent);color:#fff;padding:10px 14px;border-radius:10px;border:0;cursor:pointer;font-weight:700}
        .muted{color:#6b7280;font-size:14px}
        .switch{display:flex;justify-content:space-between;align-items:center;margin-top:12px}
        .linkish{color:var(--accent);text-decoration:none;font-weight:600}
        .small-note{font-size:13px;color:#9ca3af;margin-top:10px}
        .row-actions{display:flex;gap:8px;align-items:center}
        .alt-btn{background:transparent;border:1px solid #e6e9ef;padding:10px 12px;border-radius:10px;cursor:pointer}
    </style>
</head>
<body>
    <nav class="navbar">
        <div class="nav-wrapper">
            <a href="homePage.php" class="logo"><img src="logo.jpg" class="logo-img" alt="logo"><span>Andrea Mystery Shop</span></a>
            <div class="right-nav">
                <div class="nav-desktop">
                    <a href="homePage.php">Home</a>
                    <a href="about.php">About</a>
                    <a href="contact.php">Contact Us</a>
                    <a href="privacy.php">Data Privacy</a>
                </div>
                <div class="nav-actions"><a href="LogIn.php"><button>Login</button></a></div>
            </div>
        </div>
    </nav>

    <main class="auth-hero">
        <div class="hero-bg">
            <img src="heroBg.jpg" alt="Luxury products background">
            <div class="hero-content">
                <div class="brand"><img src="logo.jpg" alt="logo" style="width:48px;height:48px;object-fit:cover;border-radius:8px">Andrea Mystery Shop</div>
                <h2 style="margin-top:12px;margin-bottom:6px">Forgot your password?</h2>
                <p style="margin:0;color:rgba(255,255,255,0.9);max-width:380px">Enter the email associated with your account and we'll send you a one-time code to reset your password.</p>
            </div>
        </div>

        <div class="panel" role="region" aria-label="Password reset">
            <h1>Reset Password</h1>
            <p class="lead">We'll send a verification code to your email.</p>
            <form method="post" action="send_reset_otp.php" autocomplete="off">
                <div class="form-row"><label for="email">Email</label><input id="email" name="email" type="email" placeholder="you@example.com" required></div>
                <div class="row-actions">
                    <button class="btn-primary" type="submit">Send Code</button>
                    <button type="button" class="alt-btn" onclick="location.href='LogIn.php'">Back to login</button>
                </div>
            </form>
        </div>
    </main>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            if (typeof Swal === 'undefined') return;
            <?php if(isset($_SESSION['reset_err'])): ?>
                Swal.fire({icon:'error', title:'Error', text: <?= json_encode($_SESSION['reset_err']) ?>, confirmButtonColor:'#df0c0c'});
                <?php unset($_SESSION['reset_err']); ?>
            <?php endif;?>
        });
    </script>
</body>
</html>