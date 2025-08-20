<?php
// Public logout: clear user cookies and session, then go to homepage
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Clear user auth cookies
$cookies = ['user_id', 'user_name', 'user_email', 'user_role'];
foreach ($cookies as $c) {
    if (isset($_COOKIE[$c])) {
        setcookie($c, '', time() - 3600, '/');
        unset($_COOKIE[$c]);
    }
}

// Clear session array
$_SESSION = [];

// Delete the session cookie
if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params['path'], $params['domain'],
        $params['secure'], $params['httponly']
    );
}

// Destroy the session
session_destroy();

// Redirect to landing page
header('Location: /php-enrollment-system/index.php');
exit();
?>
