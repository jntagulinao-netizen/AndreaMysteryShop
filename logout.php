<?php
session_start();

// Check if logout is confirmed
if (!isset($_GET['confirmed'])) {
    // Show confirmation page with SweetAlert
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Logout Confirmation</title>
        <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    </head>
    <body>
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        icon: 'question',
                        title: 'Logout Confirmation',
                        text: 'Are you sure you want to logout?',
                        showCancelButton: true,
                        confirmButtonColor: '#df0c0c',
                        cancelButtonColor: '#6b7280',
                        confirmButtonText: 'Yes, Logout',
                        cancelButtonText: 'Cancel'
                    }).then(function(result) {
                        if (result.isConfirmed) {
                            // Redirect to logout with confirmation
                            window.location.href = 'logout.php?confirmed=1';
                        } else {
                            // Redirect back to previous page or dashboard
                            history.back();
                        }
                    });
                } else {
                    alert('SweetAlert2 failed to load. Proceeding with logout...');
                    window.location.href = 'logout.php?confirmed=1';
                }
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
