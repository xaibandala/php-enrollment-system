<?php
// TEMP: enable error display for debugging this blank page. Remove after fixing.
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
require_once 'includes/session.php';
require_once 'config/database.php';
require_once 'includes/mailer.php';

// If already logged in (cookie-based), go to dashboard
if (isUserLoggedIn()) {
    header('Location: dashboard.php');
    exit();
}

// Store the return URL (similar to login.php), avoid looping to auth/public pages
if (empty($_SESSION['return_url']) && !empty($_SERVER['HTTP_REFERER'])) {
    $referer = parse_url($_SERVER['HTTP_REFERER'], PHP_URL_PATH);
    $disallowedReferers = [
        'login.php', 'index.php', '/', 'register.php',
        'forgot_password.php', 'reset_password.php', 'loginOTP.php',
        'admin/adminlogin.php'
    ];
    $refererBase = basename($referer);
    if (!in_array($refererBase, $disallowedReferers, true) && empty($_SERVER['HTTP_X_REQUESTED_WITH'])) {
        $_SESSION['return_url'] = $referer;
    }
}

$error = '';
$success = '';
$step = 'request'; // or 'verify'
$otpSessionKey = 'login_otp';

// Optional resend cooldown (seconds)
$RESEND_COOLDOWN = 60; // 1 minute cooldown
// OTP expiry window
$OTP_EXPIRY_SECONDS = 10 * 60; // 10 minutes

// Flash helpers (Post/Redirect/Get)
if (!function_exists('set_flash')) {
    function set_flash($type, $message, $step = null) {
        $_SESSION['flash_' . $type] = $message;
        if ($step !== null) {
            $_SESSION['flash_step'] = $step;
        }
    }
    function get_flash($type) {
        $key = 'flash_' . $type;
        if (!empty($_SESSION[$key])) {
            $msg = $_SESSION[$key];
            unset($_SESSION[$key]);
            return $msg;
        }
        return '';
    }
}

// Allow returning to the email request step explicitly
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['reset'])) {
    unset($_SESSION[$otpSessionKey]);
    set_flash('success', 'You can enter a different email.', 'request');
    header('Location: loginOTP.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'send_otp') {
        $email = trim($_POST['email'] ?? '');
        if (empty($email)) {
            set_flash('error', 'Please enter your email address', 'request');
            header('Location: loginOTP.php');
            exit();
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            set_flash('error', 'Please enter a valid email address', 'request');
            header('Location: loginOTP.php');
            exit();
        } else {
            $user = fetchOne("SELECT id, name, email, role FROM users WHERE email = ?", [$email]);

            // Message when user exists vs does not exist
            $genericMsg = 'If an account exists with that email, an OTP has been sent.';
            if ($user) {
                // Resend cooldown
                $now = time();
                $existing = $_SESSION[$otpSessionKey] ?? null;
                if ($existing && ($existing['email'] ?? '') === $user['email'] && ($existing['last_sent'] ?? 0) > $now - $RESEND_COOLDOWN) {
                    $remaining = max(0, $RESEND_COOLDOWN - ($now - $existing['last_sent']));
                    set_flash('error', "Please wait {$remaining}s before requesting another OTP.", 'request');
                    header('Location: loginOTP.php');
                    exit();
                } else {
                    $otp = str_pad((string)random_int(0, 999999), 6, '0', STR_PAD_LEFT);
                    $expiresAt = $now + $OTP_EXPIRY_SECONDS; // 10 minutes
                    $_SESSION[$otpSessionKey] = [
                        'user_id' => $user['id'],
                        'email' => $user['email'],
                        'name' => $user['name'],
                        'role' => $user['role'],
                        'otp' => $otp,
                        'expires' => $expiresAt,
                        'attempts' => 0,
                        'last_sent' => $now,
                    ];
                    // Send OTP (log failures but keep UI generic)
                    $sent = sendLoginOtpEmail($user['email'], $otp, $user['name']);
                    if (!$sent) {
                        error_log('OTP email failed to send to ' . $user['email']);
                    }
                    set_flash('success', 'An OTP has been sent to your email.', 'verify');
                    header('Location: loginOTP.php');
                    exit();
                }
            } else {
                // Explicitly inform the user that the account does not exist
                set_flash('error', 'Account does not exist', 'request');
                header('Location: loginOTP.php');
                exit();
            }
        }
    } elseif ($action === 'verify_otp') {
        $code = preg_replace('/\D/', '', $_POST['otp'] ?? '');
        $sessionOtp = $_SESSION[$otpSessionKey] ?? null;

        if (!$sessionOtp) {
            set_flash('error', 'Your session expired. Please request a new OTP.', 'request');
            header('Location: loginOTP.php');
            exit();
        } else {
            // Rate limit attempts
            $_SESSION[$otpSessionKey]['attempts'] = ($sessionOtp['attempts'] ?? 0) + 1;
            if ($_SESSION[$otpSessionKey]['attempts'] > 5) {
                unset($_SESSION[$otpSessionKey]);
                set_flash('error', 'Too many attempts. Please request a new OTP.', 'request');
                header('Location: loginOTP.php');
                exit();
            } else {
                $now = time();
                if ($now > ($sessionOtp['expires'] ?? 0)) {
                    unset($_SESSION[$otpSessionKey]);
                    set_flash('error', 'OTP expired. Please request a new one.', 'request');
                    header('Location: loginOTP.php');
                    exit();
                } elseif (!hash_equals($sessionOtp['otp'], $code)) {
                    set_flash('error', 'Invalid code. Please try again.', 'verify');
                    header('Location: loginOTP.php');
                    exit();
                } else {
                    // Success: log user in like login.php
                    executeQuery("UPDATE users SET last_login = CURRENT_TIMESTAMP WHERE id = ?", [$sessionOtp['user_id']]);

                    $cookie_expiry = time() + (86400 * 30); // 30 days
                    setcookie('user_id', $sessionOtp['user_id'], $cookie_expiry, '/');
                    setcookie('user_name', $sessionOtp['name'], $cookie_expiry, '/');
                    setcookie('user_email', $sessionOtp['email'], $cookie_expiry, '/');
                    setcookie('user_role', $sessionOtp['role'], $cookie_expiry, '/');

                    unset($_SESSION[$otpSessionKey]);

                    // Redirect based on role
                    if (isset($_SESSION['return_url'])) { unset($_SESSION['return_url']); }
                    $role = $sessionOtp['role'] ?? '';
                    if ($role === 'admin') {
                        header('Location: admin/index.php');
                    } else {
                        header('Location: dashboard.php'); // students
                    }
                    exit();
                }
            }
        }
    } elseif ($action === 'resend_otp') {
        $existing = $_SESSION[$otpSessionKey] ?? null;
        if (!$existing) {
            set_flash('error', 'Your session expired. Please request a new OTP.', 'request');
            header('Location: loginOTP.php');
            exit();
        } else {
            $now = time();
            if (($existing['last_sent'] ?? 0) > $now - $RESEND_COOLDOWN) {
                $remaining = max(0, $RESEND_COOLDOWN - ($now - $existing['last_sent']));
                set_flash('error', "Please wait {$remaining}s before requesting another OTP.", 'verify');
                header('Location: loginOTP.php');
                exit();
            } else {
                $otp = str_pad((string)random_int(0, 999999), 6, '0', STR_PAD_LEFT);
                $_SESSION[$otpSessionKey]['otp'] = $otp;
                $_SESSION[$otpSessionKey]['expires'] = $now + $OTP_EXPIRY_SECONDS;
                $_SESSION[$otpSessionKey]['attempts'] = 0;
                $_SESSION[$otpSessionKey]['last_sent'] = $now;
                $sent = sendLoginOtpEmail($existing['email'], $otp, $existing['name']);
                if (!$sent) {
                    error_log('Resend OTP email failed to send to ' . $existing['email']);
                }
                set_flash('success', 'A new OTP has been sent to your email.', 'verify');
                header('Location: loginOTP.php');
                exit();
            }
        }
    }
}

// Pull flash messages (if any) and desired step
$error = get_flash('error');
$success = get_flash('success');
if (!empty($_SESSION['flash_step'])) {
    $step = $_SESSION['flash_step'];
    unset($_SESSION['flash_step']);
} else {
    // Decide initial step if session already has an OTP
    if (!empty($_SESSION[$otpSessionKey])) {
        $step = 'verify';
    }
}
$sessionEmail = $_SESSION[$otpSessionKey]['email'] ?? '';
// Compute remaining timers for UI
$__now = time();
$__session = $_SESSION[$otpSessionKey] ?? null;
$cooldownRemaining = 0;
$expiryRemaining = 0;
if ($__session) {
    $cooldownRemaining = max(0, $RESEND_COOLDOWN - ($__now - (int)($__session['last_sent'] ?? 0)));
    $expiryRemaining = max(0, (int)($__session['expires'] ?? 0) - $__now);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login with OTP - Enrollment System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        body {
            height: 100vh;
            display: flex;
            align-items: center;
            background-image: linear-gradient(135deg, rgba(79,70,229,0.9) 0%, rgba(124,58,237,0.75) 35%, rgba(236,72,153,0.6) 70%, rgba(6,182,212,0.6) 100%), url('image/heroimg.jpg');
            background-size: cover;
            background-position: right center;
            background-repeat: no-repeat;
        }
        .otp-container {
            max-width: 450px;
            width: 100%;
            margin: 0 auto;
            padding: 30px;
            background: #ffffff;
            border-radius: 10px;
            border: 1px solid transparent;
            background-image: linear-gradient(#ffffff, #ffffff), linear-gradient(135deg, #06B6D4, #3B82F6, #8B5CF6, #EC4899);
            background-origin: border-box;
            background-clip: padding-box, border-box;
            box-shadow: 0 12px 30px -10px rgba(0, 0, 0, 0.25);
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
        .btn-gradient-hover:hover::before { left: 0; }
        .btn-gradient-hover:hover {
            color: white !important;
            transform: translateY(-2px);
            box-shadow: 0 10px 25px -5px rgba(79, 70, 229, 0.4);
        }
        .gradient-text {
            background: linear-gradient(90deg, #C084FC, #60A5FA, #34D399);
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
        }
        .otp-input { letter-spacing: 0.3em; text-align: center; }
    </style>
</head>
<body>
    <div class="container">
        <div class="otp-container">
            <div class="text-center mb-4">
                <h2 class="gradient-text">Login with OTP</h2>
                <p class="text-muted">Enter your email to receive a one-time passcode</p>
            </div>

            <?php if (!empty($error)): ?>
                <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <?php if (!empty($success)): ?>
                <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
            <?php endif; ?>

            <?php if ($step === 'request'): ?>
                <form method="POST" action="loginOTP.php">
                    <input type="hidden" name="action" value="send_otp">
                    <div class="form-floating mb-3">
                        <input type="email" class="form-control" id="email" name="email"
                               placeholder="name@example.com" value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" required>
                        <label for="email">Email address</label>
                    </div>
                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-primary btn-gradient-hover">
                            <i class="bi bi-shield-lock me-1"></i> Send OTP
                        </button>
                    </div>
                    <div class="text-center mt-3">
                        <a href="login.php" class="text-decoration-none">
                            <i class="bi bi-arrow-left me-1"></i> Back to Password Login
                        </a>
                    </div>
                </form>
            <?php else: ?>
                <form method="POST" action="loginOTP.php">
                    <input type="hidden" name="action" value="verify_otp">
                    <?php if (!empty($sessionEmail)): ?>
                    <div class="form-floating mb-3">
                        <input type="email" class="form-control" id="email_display" value="<?php echo htmlspecialchars($sessionEmail); ?>" disabled>
                        <label for="email_display">Email address</label>
                    </div>
                    <?php if ($expiryRemaining > 0): ?>
                    <div class="mb-2 text-muted small">
                        OTP expires in <span id="expiryTimer" data-remaining="<?php echo (int)$expiryRemaining; ?>"></span>
                    </div>
                    <?php endif; ?>
                    <?php endif; ?>
                    <div class="form-floating mb-3">
                        <input type="text" inputmode="numeric" maxlength="6" class="form-control otp-input" id="otp" name="otp"
                               placeholder="123456" required>
                        <label for="otp">Enter 6-digit OTP</label>
                    </div>
                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-success btn-gradient-hover">
                            <i class="bi bi-box-arrow-in-right me-1"></i> Verify & Login
                        </button>
                    </div>
                </form>
                <form method="POST" action="loginOTP.php" class="mt-3">
                    <input type="hidden" name="action" value="resend_otp">
                    <button type="submit" class="btn btn-outline-primary w-100" id="resendBtn" data-remaining="<?php echo (int)$cooldownRemaining; ?>">
                        <i class="bi bi-arrow-repeat me-1"></i> <span id="resendText">Resend OTP</span>
                    </button>
                </form>
                <div class="text-center mt-3">
                    <a href="loginOTP.php?reset=1" class="text-decoration-none">
                        <i class="bi bi-arrow-left me-1"></i> Use a different email
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    (function(){
        function fmt(seconds){
            seconds = Math.max(0, Math.floor(seconds));
            var m = Math.floor(seconds / 60);
            var s = seconds % 60;
            return (m < 10 ? '0'+m : m) + ':' + (s < 10 ? '0'+s : s);
        }

        // Resend cooldown
        var resendBtn = document.getElementById('resendBtn');
        if (resendBtn) {
            var remain = parseInt(resendBtn.getAttribute('data-remaining') || '0', 10);
            var txt = document.getElementById('resendText');
            function tick(){
                if (remain > 0){
                    resendBtn.disabled = true;
                    if (txt) txt.textContent = 'Resend OTP (' + fmt(remain) + ')';
                    remain -= 1;
                } else {
                    resendBtn.disabled = false;
                    if (txt) txt.textContent = 'Resend OTP';
                    clearInterval(timer);
                }
            }
            tick();
            var timer = setInterval(tick, 1000);
        }

        // Expiry countdown
        var expiryEl = document.getElementById('expiryTimer');
        if (expiryEl){
            var eremain = parseInt(expiryEl.getAttribute('data-remaining') || '0', 10);
            function etick(){
                if (eremain >= 0){
                    expiryEl.textContent = fmt(eremain);
                    eremain -= 1;
                } else {
                    clearInterval(etimer);
                }
            }
            etick();
            var etimer = setInterval(etick, 1000);
        }
    })();
    </script>
</body>
</html>