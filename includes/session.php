<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start([
        'cookie_lifetime' => 86400, // 24 hours
        'cookie_secure'   => isset($_SERVER['HTTPS']),
        'cookie_httponly' => true,
        'use_strict_mode' => true
    ]);
}

function isLoggedIn() {
    return isset($_SESSION['admin_id']);
}

function isAdmin() {
    return isset($_SESSION['admin_role']) && $_SESSION['admin_role'] === 'admin';
}

function requireLogin() {
    if (!isLoggedIn()) {
        $_SESSION['return_url'] = $_SERVER['REQUEST_URI'];
        header('Location: /php-enrollment-system/admin/adminlogin.php');
        exit();
    }
}

// USER authentication helpers (non-admin)
function isUserLoggedIn() {
    // Your app uses cookies for user auth in login.php
    return isset($_COOKIE['user_id']) && !empty($_COOKIE['user_id']);
}

function requireUser() {
    if (!isUserLoggedIn()) {
        $_SESSION['return_url'] = $_SERVER['REQUEST_URI'];
        header('Location: /php-enrollment-system/login.php');
        exit();
    }
}

function requireAdmin() {
    requireLogin();
    if (!isAdmin()) {
        header('Location: /php-enrollment-system/dashboard.php');
        exit();
    }
}

// For flash messages
function setFlash($type, $message) {
    if (!isset($_SESSION['flash_messages'])) {
        $_SESSION['flash_messages'] = [];
    }
    $_SESSION['flash_messages'][] = ['type' => $type, 'message' => $message];
}

function getFlash() {
    if (!empty($_SESSION['flash_messages'])) {
        $flash = $_SESSION['flash_messages'];
        unset($_SESSION['flash_messages']);
        return $flash;
    }
    return [];
}

// Function to logout
function logout() {
    // Unset all session variables
    $_SESSION = [];
    
    // Delete the session cookie
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    
    // Destroy the session
    session_destroy();
    
    // Redirect to login page
    header('Location: /php-enrollment-system/admin/adminlogin.php');
    exit();
}
?>
