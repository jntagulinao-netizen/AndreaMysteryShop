<?php
session_start();

// Check if logout is confirmed
if (!isset($_GET['confirmed'])) {
    // Show a simple local confirmation dialog (no external libraries)
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Logout Confirmation</title>
        <style>
            * { box-sizing: border-box; }
            body {
                margin: 0;
                font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
                background: #f5f5f5;
                min-height: 100vh;
            }
            .overlay {
                position: fixed;
                inset: 0;
                background: rgba(0, 0, 0, 0.45);
                display: flex;
                align-items: center;
                justify-content: center;
                padding: 16px;
            }
            .dialog {
                width: 100%;
                max-width: 340px;
                background: #fff;
                border-radius: 12px;
                padding: 18px;
                border: 1px solid #e5e7eb;
                box-shadow: 0 16px 32px rgba(0, 0, 0, 0.18);
                text-align: center;
            }
            .dialog h1 {
                margin: 0 0 8px;
                font-size: 20px;
                color: #1f2937;
            }
            .dialog p {
                margin: 0 0 14px;
                font-size: 14px;
                color: #4b5563;
                line-height: 1.45;
            }
            .actions {
                display: grid;
                grid-template-columns: 1fr 1fr;
                gap: 8px;
            }
            .btn {
                border: none;
                border-radius: 8px;
                height: 40px;
                font-size: 14px;
                font-weight: 700;
                cursor: pointer;
            }
            .btn-cancel {
                background: #f3f4f6;
                color: #374151;
                border: 1px solid #d1d5db;
            }
            .btn-confirm {
                background: #df0c0c;
                color: #fff;
            }
            .btn-confirm:hover { background: #c70b0b; }
            .btn-cancel:hover { background: #e5e7eb; }
        </style>
    </head>
    <body>
        <div class="overlay">
            <div class="dialog" role="dialog" aria-modal="true" aria-labelledby="logout-title" aria-describedby="logout-text">
                <h1 id="logout-title">Logout Confirmation</h1>
                <p id="logout-text">Are you sure you want to logout?</p>
                <div class="actions">
                    <button type="button" class="btn btn-cancel" id="cancelBtn">Cancel</button>
                    <button type="button" class="btn btn-confirm" id="confirmBtn">Yes, Logout</button>
                </div>
            </div>
        </div>
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                var confirmBtn = document.getElementById('confirmBtn');
                var cancelBtn = document.getElementById('cancelBtn');

                if (!confirmBtn || !cancelBtn) {
                    window.location.href = 'logout.php?confirmed=1';
                    return;
                }

                confirmBtn.addEventListener('click', function() {
                    window.location.href = 'logout.php?confirmed=1';
                });

                cancelBtn.addEventListener('click', function() {
                    history.back();
                });
            });
        </script>
    </body>
    </html>
    <?php
    exit;
}

// Perform actual logout
$_SESSION = [];
if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params['path'], $params['domain'], $params['secure'], $params['httponly']
    );
}
session_destroy();
header('Location: LogIn.php?logged_out=1');
exit;
