<?php
session_start();
// ensure OTP has been verified
if (!isset($_SESSION['reset_verified']) || !$_SESSION['reset_verified'] || !isset($_SESSION['reset_email'])) {
    header('Location: LogIn.php?error=' . urlencode('Unauthorized access')); 
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Create New Password — LUXE</title>
    <link rel="stylesheet" href="main.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        /* reuse styles */
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
                <h2 style="margin-top:12px;margin-bottom:6px">Create a new password</h2>
                <p style="margin:0;color:rgba(255,255,255,0.9);max-width:380px">Please choose a strong password and confirm it to finish resetting your account.</p>
            </div>
        </div>
        <div class="panel" role="region" aria-label="Reset password panel">
            <h1>New Password</h1>
            <form method="post" action="reset_password_process.php" autocomplete="off">
                <div class="form-row"><label for="new_pass">Create new password</label><input id="new_pass" name="new_password" type="password" minlength="8" required></div>
                <div id="pwdRules" style="font-size:13px;color:#6b7280;margin-top:4px;">
                    <div><span id="ruleLength" class="rule-icon">✗</span> At least 8 characters</div>
                    <div><span id="ruleUpper" class="rule-icon">✗</span> One uppercase letter</div>
                    <div><span id="ruleSpecial" class="rule-icon">✗</span> One special character (@,#,!, etc.)</div>
                </div>
                <div class="form-row"><label for="confirm_pass">Confirm new password</label><input id="confirm_pass" name="confirm_password" type="password" minlength="8" required></div>
                <div id="pwdMatch" style="font-size:13px;color:#6b7280;margin-top:4px;"><span class="rule-icon" id="ruleMatch">✗</span> Passwords match</div>
                <div class="row-actions">
                    <button class="btn-primary" type="submit">Update Password</button>
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

            // password rule hooks
            const pwd = document.getElementById('new_pass');
            const confirm = document.getElementById('confirm_pass');
            if (pwd) pwd.addEventListener('input', updateRules);
            if (confirm) confirm.addEventListener('input', updateRules);
        });

        function updateRules() {
            const pwd = document.getElementById('new_pass');
            const confirm = document.getElementById('confirm_pass');
            const val = pwd ? pwd.value : '';
            const rules = {
                length: val.length >= 8,
                upper: /[A-Z]/.test(val),
                special: /[^A-Za-z0-9]/.test(val)
            };
            document.getElementById('ruleLength').textContent = rules.length ? '✓' : '✗';
            document.getElementById('ruleLength').style.color = rules.length ? 'green' : 'red';
            document.getElementById('ruleUpper').textContent = rules.upper ? '✓' : '✗';
            document.getElementById('ruleUpper').style.color = rules.upper ? 'green' : 'red';
            document.getElementById('ruleSpecial').textContent = rules.special ? '✓' : '✗';
            document.getElementById('ruleSpecial').style.color = rules.special ? 'green' : 'red';
            const match = val && confirm && val === confirm.value;
            document.getElementById('ruleMatch').textContent = match ? '✓' : '✗';
            document.getElementById('ruleMatch').style.color = match ? 'green' : 'red';
        }
    </script>
</body>
</html>