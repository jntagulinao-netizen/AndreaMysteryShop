<?php
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Sign in — LUXE</title>
    <link rel="stylesheet" href="main.css">
    <style>
        :root{--accent:#df0c0c}
        body{background:#f5f7fb}
        .auth-hero{display:grid;grid-template-columns:1fr;min-height:80vh;align-items:center;gap:24px;padding:24px}
        @media(min-width:900px){.auth-hero{grid-template-columns:1fr 480px;min-height:84vh;padding:48px}}
        .hero-bg{position:relative;border-radius:14px;overflow:hidden;min-height:360px;background:#333}
        .hero-bg img{width:100%;height:100%;object-fit:cover;display:block;filter:brightness(.55) saturate(.95)}
        .hero-content{position:absolute;left:24px;bottom:24px;color:#fff;max-width:60%}
        .brand{display:flex;align-items:center;gap:12px;color:#fff;font-weight:700}
        .panel{background:#fff;padding:28px;border-radius:12px;box-shadow:0 10px 40px rgba(2,6,23,0.08);z-index:70;position:relative;}
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
          /* Footer */
        .site-footer { background:#0f1724; color:#d1d5db; padding:40px 16px; }
        .site-footer .footer-grid { display:grid; grid-template-columns:1fr; gap:18px; max-width:1200px; margin:0 auto; }
        @media (min-width:640px) { .site-footer .footer-grid { grid-template-columns:repeat(2,1fr); } }
        @media (min-width:1024px) { .site-footer .footer-grid { grid-template-columns:repeat(4,1fr); } }
        .site-footer a { color: #9ca3af; text-decoration:none; }
        .site-footer a:hover { color:#fff; }

        /* Footer icon sizing */
        .site-footer svg { width:18px; height:18px; display:inline-block; }
        @media (min-width:640px) { .site-footer svg { width:20px; height:20px; } }
        @media (min-width:1024px) { .site-footer svg { width:22px; height:22px; } }

        /* Sweet Alert Styles */
        .swal-overlay {
          position: fixed;
          inset: 0;
          background: rgba(15, 23, 42, 0.45);
          display: none;
          align-items: center;
          justify-content: center;
          z-index: 2000;
          padding: 20px;
        }
        .swal-overlay.show { display: flex; }
        .swal-card {
          width: 100%;
          max-width: 360px;
          background: #fff;
          border-radius: 14px;
          border: 1px solid #dde5ee;
          box-shadow: 0 18px 40px rgba(15, 23, 42, 0.25);
          text-align: center;
          padding: 20px 18px 16px;
          animation: swalIn .16s ease-out;
        }
        @keyframes swalIn {
          from { opacity: 0; transform: translateY(8px) scale(0.98); }
          to { opacity: 1; transform: translateY(0) scale(1); }
        }
        .swal-icon {
          width: 52px;
          height: 52px;
          border-radius: 50%;
          margin: 0 auto 10px;
          display: flex;
          align-items: center;
          justify-content: center;
          font-size: 28px;
          font-weight: 700;
        }
        .swal-icon.success { background: #e9f9ef; color: #0c8f3f; }
        .swal-icon.error { background: #ffe9e9; color: #c41e3a; }
        .swal-icon.warning { background: #fff6e5; color: #bb6a00; }
        .swal-title { font-size: 20px; font-weight: 700; color: #152033; margin-bottom: 8px; }
        .swal-text { font-size: 14px; color: #5f6d7f; margin-bottom: 14px; line-height: 1.45; }
        .swal-actions { display: grid; grid-template-columns: 1fr 1fr; gap: 8px; }
        .swal-btn {
          border: none;
          border-radius: 10px;
          font-size: 14px;
          font-weight: 700;
          width: 100%;
          height: 42px;
          cursor: pointer;
        }
        .swal-btn.primary { background: #2d68d8; color: #fff; }
        .swal-btn.primary:hover { background: #1f56bf; }
        .swal-btn.secondary { background: #f2f5fb; color: #44546a; border: 1px solid #d5deea; }
        .swal-btn.secondary:hover { background: #e9eef7; }

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

     <!-- Mobile bottom navigation (Lazada-style) -->
    <nav class="mobile-bottom-nav fixed">
        <div class="mobile-nav-inner">
            <a href="homePage.php" class="active">
                <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M3 10.5L12 3l9 7.5V21a1 1 0 0 1-1 1h-5v-7H9v7H4a1 1 0 0 1-1-1V10.5z" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"></path></svg>
                <span>Home</span>
            </a>
            <a href="about.php">
                <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor"><circle cx="12" cy="8" r="3" stroke-width="1.5"></circle><path d="M6 20v-1a4 4 0 014-4h4a4 4 0 014 4v1" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"></path></svg>
                <span>About</span>
            </a>
            <a href="contact.php">
                <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M21 8V7a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v1" stroke-width="1.5"></path><rect x="3" y="8" width="18" height="11" rx="2" ry="2" stroke-width="1.5"></rect></svg>
                <span>Contact</span>
            </a>
            <a href="privacy.php">
                <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M12 2l7 4v6c0 5-3.58 9-7 10-3.42-1-7-5-7-10V6l7-4z" stroke-width="1.5"></path></svg>
                <span>Privacy</span>
            </a>
        </div>
    </nav>

    <main class="auth-hero">
        <div class="hero-bg">
            <img src="heroBg.jpg" alt="Luxury products background">
            <div class="hero-content">
                <div class="brand"><img src="logo.jpg" alt="logo" style="width:48px;height:48px;object-fit:cover;border-radius:8px">Andrea Mystery Shop</div>
                <h2 style="margin-top:12px;margin-bottom:6px">Welcome to Andrea Mystery Shop</h2>
                <p style="margin:0;color:rgba(255,255,255,0.9);max-width:380px">Discover premium products and exclusive offers. Sign in to access your account and orders.</p>
            </div>
        </div>

        <div id="authPanel" class="panel" role="region" aria-label="Authentication panel">
            <div id="signInView">
                <h1>Sign in to your account</h1>
                <p class="lead">Enter your credentials below.</p>
                <form method="post" action="login_process.php" autocomplete="off" name="loginForm">
                    <div class="form-row"><label for="email">Email</label><input id="email" name="email" type="email" placeholder="you@example.com" autocomplete="new-password" required></div>
                    <div class="form-row"><label for="password">Password</label><input id="password" name="password" type="password" placeholder="Enter your password" autocomplete="new-password" required></div>
                    <div class="row-actions">
                        <button class="btn-primary" type="submit">Sign in</button>
                        <button type="button" class="alt-btn" onclick="showRegister()">Create account</button>
                    </div>
                </form>
                <p class="small-note">
                    Forgot your password? <a href="forgot_password.php" class="linkish">Reset it</a>
                </p>
               
            </div>

            <div id="registerView" style="display:none">
                <h1>Create an account</h1>
                <p class="lead">Quick and secure. We'll send a verification email.</p>
                <form method="post" action="send_otp.php">
                    <div class="form-row"><label for="name">Full name</label><input id="name" name="name" type="text" placeholder="John Doe" required></div>
                    <div class="form-row"><label for="reg_email">Email</label><input id="reg_email" name="email" type="email" placeholder="you@example.com" required></div>
                    <div class="form-row"><label for="reg_pass">Password</label><input id="reg_pass" name="password" type="password" minlength="8" placeholder="Minimum 8 characters" required></div>
                    <div id="pwdRules" style="font-size:13px;color:#6b7280;margin-top:4px;">
                        <div><span id="ruleLength" class="rule-icon">✗</span> At least 8 characters</div>
                        <div><span id="ruleUpper" class="rule-icon">✗</span> One uppercase letter</div>
                        <div><span id="ruleSpecial" class="rule-icon">✗</span> One special character (@,#,!, etc.)</div>
                    </div>
                    <div class="form-row"><label for="reg_confirm">Confirm password</label><input id="reg_confirm" name="confirm_password" type="password" minlength="8" placeholder="Re-enter your password" required></div>
                    <div id="pwdMatch" style="font-size:13px;color:#6b7280;margin-top:4px;"><span class="rule-icon" id="ruleMatch">✗</span> Passwords match</div>
                    <div class="row-actions">
                        <button class="btn-primary" type="submit">Create account</button>
                        <button type="button" class="alt-btn" onclick="showSignIn()">Back to sign in</button>
                    </div>
                </form>
                <p class="small-note">Already have an account? <a href="#" class="linkish" onclick="showSignIn();return false;">Sign in</a></p>
            </div>
        </div>
    </main>

    
    <!-- Footer -->
    <footer class="site-footer">
        <div class="container">
            <div class="footer-grid">
                <div>
                    <div style="display:flex;align-items:center;gap:10px;margin-bottom:12px;">
                        <img src="logo.jpg" alt="Andrea Mystery Shop" style="width:32px;height:32px;border-radius:8px;object-fit:cover;display:block;" />
                        <span style="font-weight:700;font-size:16px;color:#fff">Andrea Mystery Shop</span>
                    </div>
                    <p style="color:#9ca3af;font-size:13px;margin:0">Your destination for premium lifestyle products.</p>
                </div>
                <div>
                    <h4 style="color:#fff;font-weight:700;margin-bottom:8px">Quick Links</h4>
                    <ul style="list-style:none;padding:0;margin:0;color:#9ca3af;font-size:13px;line-height:1.9">
                        <li><a href="HomePage.php" style="color:inherit;text-decoration:none;opacity:0.9">Home</a></li>
                        <li><a href="about.php" style="color:inherit;text-decoration:none;opacity:0.9">About</a></li>
                        <li><a href="contact.php" style="color:inherit;text-decoration:none;opacity:0.9">Contact Us</a></li>
                    </ul>
                </div>
                <div>
                    <h4 style="color:#fff;font-weight:700;margin-bottom:8px">Legal</h4>
                    <ul style="list-style:none;padding:0;margin:0;color:#9ca3af;font-size:13px;line-height:1.9">
                        <li><a href="privacy.php" style="color:inherit;text-decoration:none;opacity:0.9">Data Privacy</a></li>
                        <li><a href="#" style="color:inherit;text-decoration:none;opacity:0.9">Terms of Service</a></li>
                        <li><a href="#" style="color:inherit;text-decoration:none;opacity:0.9">Return Policy</a></li>
                    </ul>
                </div>
                <div>
                    <h4 style="color:#fff;font-weight:700;margin-bottom:8px">Follow Us</h4>
                    <div style="display:flex;gap:12px;align-items:center">
                        <a href="#" style="color:inherit;opacity:0.9"><svg fill="currentColor" viewBox="0 0 24 24" style="width:18px;height:18px"><path d="M18 2h-3a5 5 0 00-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 011-1h3z"></path></svg></a>
                        <a href="#" style="color:inherit;opacity:0.9"><svg fill="none" stroke="currentColor" viewBox="0 0 24 24" style="width:18px;height:18px"><rect x="2" y="2" width="20" height="20" rx="5" ry="5" stroke-width="2"></rect><path d="M16 11.37A4 4 0 1112.63 8 4 4 0 0116 11.37z" stroke-width="2"></path></svg></a>
                        <a href="#" style="color:inherit;opacity:0.9"><svg fill="currentColor" viewBox="0 0 24 24" style="width:18px;height:18px"><path d="M23 3a10.9 10.9 0 01-3.14 1.53 4.48 4.48 0 00-7.86 3v1A10.66 10.66 0 013 4s-4 9 5 13a11.64 11.64 0 01-7 2c9 5 20 0 20-11.5a4.5 4.5 0 00-.08-.83A7.72 7.72 0 0023 3z"></path></svg></a>
                    </div>
                </div>
            </div>
            <div style="border-top:1px solid rgba(255,255,255,0.06);margin-top:20px;padding-top:16px;text-align:center;color:#9ca3af;font-size:13px">
                
            </div>
        </div>
    </footer>

    <script>
        // Clear autofilled fields only on initial page load
        document.addEventListener('DOMContentLoaded', function() {
            const email = document.getElementById('email');
            const password = document.getElementById('password');
            
            // Remove any autofilled values from browser
            if (email) {
                email.value = '';
                email.removeAttribute('value');
            }
            if (password) {
                password.value = '';
                password.removeAttribute('value');
            }

            // hook password rule updates for registration
            const pwd = document.getElementById('reg_pass');
            const confirm = document.getElementById('reg_confirm');
            if (pwd) {
                pwd.addEventListener('input', updateRegRules);
            }
            if (confirm) {
                confirm.addEventListener('input', updateRegRules);
            }
        });
        // continued below to include param handling and alerts

        function showRegister() {
            document.getElementById('signInView').style.display = 'none';
            document.getElementById('registerView').style.display = 'block';
        }

        function showSignIn() {
            document.getElementById('registerView').style.display = 'none';
            document.getElementById('signInView').style.display = 'block';
        }

        function showAuthSweetAlert(options) {
          const overlay = document.getElementById('authSwal');
          const icon = document.getElementById('authSwalIcon');
          const titleEl = document.getElementById('authSwalTitle');
          const textEl = document.getElementById('authSwalText');
          const actions = document.getElementById('authSwalActions');
          const confirmBtn = document.getElementById('authSwalConfirm');
          const cancelBtn = document.getElementById('authSwalCancel');
          if (!overlay || !icon || !titleEl || !textEl || !actions || !confirmBtn || !cancelBtn) {
            return;
          }

          const type = options.type || 'success';
          const hasCancel = !!options.showCancel;

          icon.className = `swal-icon ${type}`;
          icon.textContent = type === 'error' ? '!' : (type === 'warning' ? '⚠' : '✓');
          titleEl.textContent = options.title || 'Notice';
          textEl.textContent = options.text || '';

          confirmBtn.textContent = options.confirmText || 'OK';
          cancelBtn.textContent = options.cancelText || 'Cancel';
          cancelBtn.style.display = hasCancel ? 'block' : 'none';
          actions.style.gridTemplateColumns = hasCancel ? '1fr 1fr' : 'auto';

          confirmBtn.onclick = () => {
            overlay.classList.remove('show');
            if (typeof options.onConfirm === 'function') {
              options.onConfirm();
            }
          };

          cancelBtn.onclick = () => {
            overlay.classList.remove('show');
            if (typeof options.onCancel === 'function') {
              options.onCancel();
            }
          };

          overlay.classList.add('show');
        }

        function updateRegRules() {
            const pwd = document.getElementById('reg_pass');
            const confirm = document.getElementById('reg_confirm');
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

        // parse query parameters for later use
        document.addEventListener('DOMContentLoaded', function() {
            const p = new URLSearchParams(window.location.search);

            // Handle password reset success
            if (p.get('password_reset') === '1') {
                showAuthSweetAlert({
                    type: 'success',
                    title: 'Password Updated',
                    text: 'Your password has been reset. Please sign in with your new password.',
                    confirmText: 'Got It',
                    onConfirm: () => {
                        showSignIn();
                    }
                });
                return;
            }

            // Handle login errors
            <?php if(isset($_SESSION['login_err'])): ?>
                showAuthSweetAlert({
                    type: 'error',
                    title: 'Login Failed',
                    text: <?php echo json_encode($_SESSION['login_err']); ?>,
                    confirmText: 'Try Again'
                });
                <?php unset($_SESSION['login_err']); ?>
            <?php endif; ?>

            // Handle registration errors
            <?php if(isset($_SESSION['register_err'])): ?>
                showRegister();
                setTimeout(function() {
                    showAuthSweetAlert({
                        type: 'error',
                        title: 'Registration Error',
                        text: <?php echo json_encode($_SESSION['register_err']); ?>,
                        confirmText: 'Try Again'
                    });
                }, 100);
                <?php unset($_SESSION['register_err']); ?>
            <?php endif; ?>

            // Handle registration success
            if (p.get('registered') === '1') {
                showAuthSweetAlert({
                    type: 'success',
                    title: 'Account Created!',
                    text: 'Your account is ready. Please sign in with your credentials.',
                    confirmText: 'Sign In',
                    onConfirm: () => {
                        showSignIn();
                    }
                });
            }

            // Handle other errors from URL parameter
            const err = p.get('error');
            if (err) {
                showAuthSweetAlert({
                    type: 'error',
                    title: 'Error',
                    text: decodeURIComponent(err),
                    confirmText: 'Close'
                });
            }
        });
        // end DOMContentLoaded listener

    </script>

    <!-- Local Sweet Alert -->
    <div id="authSwal" class="swal-overlay" role="dialog" aria-modal="true" aria-live="polite">
      <div class="swal-card">
        <div id="authSwalIcon" class="swal-icon success">✓</div>
        <div id="authSwalTitle" class="swal-title">Success</div>
        <div id="authSwalText" class="swal-text"></div>
        <div id="authSwalActions" class="swal-actions">
          <button id="authSwalCancel" type="button" class="swal-btn secondary" style="display:none;">Cancel</button>
          <button id="authSwalConfirm" type="button" class="swal-btn primary">OK</button>
        </div>
      </div>
    </div>
    
</body>
</html>
