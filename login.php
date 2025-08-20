<?php
require_once 'includes/session.php';
require_once 'config/database.php';

// Store the return URL before any output
if (empty($_SESSION['return_url']) && !empty($_SERVER['HTTP_REFERER'])) {
    $referer = parse_url($_SERVER['HTTP_REFERER'], PHP_URL_PATH);
    // Only store if it's not the login page and not an AJAX request
    $disallowedReferers = ['login.php', 'index.php', '/', 'register.php', 'forgot_password.php', 'reset_password.php', 'loginOTP.php'];
    $refererBase = basename($referer);
    if (!in_array($refererBase, $disallowedReferers, true) && empty($_SERVER['HTTP_X_REQUESTED_WITH'])) {
        $_SESSION['return_url'] = $referer;
    }
}

$error = '';
$success = '';

// Check for account deletion success
if (isset($_GET['deleted'])) {
    $success = 'Your account has been successfully deleted. We\'re sorry to see you go!';
}

// Check for registration success
if (isset($_GET['registered'])) {
    $success = 'Registration successful! You can now log in with your credentials.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    
    $user = fetchOne("SELECT * FROM users WHERE email = ?", [$email]);
    
    if ($user && password_verify($password, $user['password'])) {
        // Update last login time
        executeQuery("UPDATE users SET last_login = CURRENT_TIMESTAMP WHERE id = ?", [$user['id']]);
        
        // Set cookies for authentication
        $cookie_expiry = time() + (86400 * 30); // 30 days
        setcookie('user_id', $user['id'], $cookie_expiry, '/');
        setcookie('user_name', $user['name'], $cookie_expiry, '/');
        setcookie('user_email', $user['email'], $cookie_expiry, '/');
        setcookie('user_role', $user['role'], $cookie_expiry, '/');
        
        // Check for return URL in session, validate, or redirect to dashboard
        $returnUrl = $_SESSION['return_url'] ?? '';
        unset($_SESSION['return_url']); // Clear the return URL after using it

        // Disallow redirecting back to public or auth pages; default to dashboard
        $disallowedTargets = ['', '/', 'index.php', 'login.php', 'register.php', 'forgot_password.php', 'reset_password.php', 'admin/adminlogin.php', 'loginOTP.php'];
        $target = 'dashboard.php';
        if (!empty($returnUrl)) {
            $path = parse_url($returnUrl, PHP_URL_PATH);
            $base = ltrim($path ?? '', '/');
            if ($base !== '' && !in_array($base, $disallowedTargets, true)) {
                $target = $returnUrl;
            }
        }

        // Redirect to the intended page or dashboard
        header('Location: ' . $target);
        exit();
    } else {
        $error = 'Invalid email or password';
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Enrollment System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        body {
            height: 100vh;
            display: flex;
            align-items: center;
            /* Gradient + background image to mirror index hero */
            background-image: linear-gradient(135deg, rgba(79,70,229,0.9) 0%, rgba(124,58,237,0.75) 35%, rgba(236,72,153,0.6) 70%, rgba(6,182,212,0.6) 100%), url('image/heroimg.jpg');
            background-size: cover;
            background-position: right center;
            background-repeat: no-repeat;
        }
        .login-container {
            max-width: 400px;
            width: 100%;
            margin: 0 auto;
            padding: 20px;
            background: #ffffff;
            border-radius: 10px;
            /* Subtle gradient border */
            border: 1px solid transparent;
            background-image: linear-gradient(#ffffff, #ffffff), linear-gradient(135deg, #06B6D4, #3B82F6, #8B5CF6, #EC4899);
            background-origin: border-box;
            background-clip: padding-box, border-box;
            box-shadow: 0 12px 30px -10px rgba(0, 0, 0, 0.25);
        }
        .form-floating {
            margin-bottom: 1rem;
        }
        .btn-gradient-hover {
            position: relative;
            overflow: hidden;
            transition: all 0.3s ease;
            border: none;
        }
        .btn-gradient-hover::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(135deg, #06B6D4 0%, #3B82F6 33%, #8B5CF6 66%, #EC4899 100%);
            transition: left 0.5s ease;
            z-index: -1;
        }
        .btn-gradient-hover:hover::before {
            left: 0;
        }
        .btn-gradient-hover:hover {
            color: white !important;
            transform: translateY(-2px);
            box-shadow: 0 10px 25px -5px rgba(79, 70, 229, 0.4);
        }
        /* Gradient text utility for heading */
        .gradient-text {
            background: linear-gradient(90deg, #C084FC, #60A5FA, #34D399);
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="login-container">
            <div class="text-center mb-4">
                <h2 class="gradient-text">Enrollment System</h2>
                <p class="text-muted">Sign in to your account</p>
            </div>
            
            <?php if ($error): ?>
                <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="alert alert-success"><?php echo $success; ?></div>
            <?php endif; ?>
            
            <form method="POST" action="login.php">
                <div class="form-floating">
                    <input type="email" class="form-control" id="email" name="email" placeholder="name@example.com" required>
                    <label for="email">Email address</label>
                </div>
                <div class="form-floating">
                    <input type="password" class="form-control" id="password" name="password" placeholder="Password" required>
                    <label for="password">Password</label>
                </div>
                
                <button class="w-100 btn btn-lg btn-primary btn-gradient-hover" type="submit">Sign in</button>
            </form>
            
            <div class="text-center mt-3">
                <p class="text-muted">
                    <a href="loginOTP.php" class="text-decoration-none">Login with OTP (email code)</a>
                </p>
                <p class="text-muted">
                    Don't have an account? <a href="register.php" class="text-decoration-none">Register here</a> or contact admin.
                </p>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>