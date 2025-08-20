<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start([
        'cookie_lifetime' => 86400,
        'cookie_secure'   => isset($_SERVER['HTTPS']),
        'cookie_httponly' => true,
        'use_strict_mode' => true
    ]);
}

require_once '../config/database.php';

// Debug session info
error_log('Login page accessed. Session ID: ' . session_id());
error_log('Current session data: ' . print_r($_SESSION, true));

$error = '';

// Debug output
if (isset($_POST['username'])) {
    error_log('Login attempt for user: ' . $_POST['username']);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    
    if (!empty($username) && !empty($password)) {
        $stmt = $pdo->prepare("SELECT * FROM admins WHERE username = ?");
        $stmt->execute([$username]);
        $admin = $stmt->fetch();
        
        if ($admin && password_verify($password, $admin['password'])) {
            // Update last login time
            $updateStmt = $pdo->prepare("UPDATE admins SET last_login = NOW() WHERE id = ?");
            $updateStmt->execute([$admin['id']]);
            
            // Regenerate session ID to prevent session fixation
            session_regenerate_id(true);
            
            // Set session variables
            $_SESSION['admin_id'] = $admin['id'];
            $_SESSION['admin_role'] = $admin['role'];
            $_SESSION['admin_username'] = $admin['username'];
            $_SESSION['admin_fullname'] = $admin['full_name'];
            
            // Debug information
            error_log('Login successful for admin: ' . $admin['username']);
            error_log('Session data after login: ' . print_r($_SESSION, true));
            
            // Redirect to admin dashboard (absolute path for reliability)
            $redirect_url = '/php-enrollment-system/admin/index.php';
            
            // Write and close session
            session_write_close();
            
            error_log('Attempting to redirect to: ' . $redirect_url);
            
            // Ensure no output before header
            if (!headers_sent()) {
                header('Location: ' . $redirect_url);
                exit();
            } else {
                // Fallback with JavaScript if headers already sent
                echo "<script>window.location.href = '$redirect_url';</script>";
                echo '<noscript><meta http-equiv="refresh" content="0;url=' . htmlspecialchars($redirect_url, ENT_QUOTES) . '"></noscript>';
                exit();
            }
            
            // If we get here, the redirect failed
            die('Redirect failed. Please <a href="index.php">click here</a> to continue.');
        } else {
            $error = 'Invalid username or password';
        }
    } else {
        $error = 'Please fill in all fields';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css">
    <style>
        html, body { height: 100%; }
        body {
            margin: 0;
            background: linear-gradient(135deg, #f8f9fa 0%, #eef2f7 100%);
            display: flex;
            justify-content: center;
            align-items: center;
        }
        .container { width: 100%; padding: 0 15px; }
        .login-container {
            max-width: 520px;
            width: 100%;
            padding: 32px 28px;
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 8px 30px rgba(0,0,0,0.08);
            margin: 0 auto;
        }
        .brand-icon { color: #0d6efd; }
        .form-label { font-weight: 500; }
        /* Animated gradient button for login */
        .btn-gradient {
            background-image: linear-gradient(45deg, #0d6efd, #6610f2, #0d6efd);
            background-size: 200% 200%;
            color: #fff !important;
            border: 0;
            transition: background-position .5s ease, box-shadow .2s ease;
        }
        .btn-gradient:hover, .btn-gradient:focus {
            background-position: 100% 0;
            box-shadow: 0 6px 16px rgba(13, 110, 253, 0.35);
        }
        .btn-gradient:active {
            transform: translateY(0.5px);
            box-shadow: 0 4px 12px rgba(13, 110, 253, 0.25);
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="login-container">
            <div class="text-center mb-2">
                <div class="display-6"><i class="bi bi-shield-lock brand-icon"></i></div>
            </div>
            <h2 class="text-center mb-3">Admin Login</h2>
            <p class="text-center text-muted mb-4">Sign in to continue to the dashboard</p>
            <?php if ($error): ?>
                <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            <form method="POST" action="/php-enrollment-system/admin/adminlogin.php">
                <div class="mb-3">
                    <label for="username" class="form-label">Username</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-person"></i></span>
                        <input type="text" class="form-control" id="username" name="username" placeholder="Enter your username" required>
                    </div>
                </div>
                <div class="mb-3">
                    <label for="password" class="form-label">Password</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-lock"></i></span>
                        <input type="password" class="form-control" id="password" name="password" placeholder="Enter your password" required>
                        <button type="button" class="btn btn-outline-secondary" id="togglePassword" tabindex="-1">
                            <i class="bi bi-eye" id="togglePasswordIcon"></i>
                        </button>
                    </div>
                </div>
                <button type="submit" class="btn btn-primary btn-gradient w-100 shadow-sm"><i class="bi bi-box-arrow-in-right me-1"></i>Login</button>
            </form>
            <div class="text-center mt-3 text-muted small">© <?php echo date('Y'); ?> Enrollment System</div>
        </div>
    </div>
    <script>
        (function(){
            const pwd = document.getElementById('password');
            const btn = document.getElementById('togglePassword');
            const icon = document.getElementById('togglePasswordIcon');
            if (btn && pwd && icon) {
                btn.addEventListener('click', function(){
                    const isText = pwd.getAttribute('type') === 'text';
                    pwd.setAttribute('type', isText ? 'password' : 'text');
                    icon.classList.toggle('bi-eye');
                    icon.classList.toggle('bi-eye-slash');
                });
            }
        })();
    </script>
</body>
</html>
