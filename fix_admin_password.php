<?php
require_once 'config/database.php';

// New password (same as before, but we'll hash it properly)
$username = 'admin';
$password = 'admin123';

// Generate a new hash for the password
$hashedPassword = password_hash($password, PASSWORD_DEFAULT);

try {
    // Update the admin password
    $stmt = $pdo->prepare("UPDATE admins SET password = ? WHERE username = ?");
    $stmt->execute([$hashedPassword, $username]);
    
    // Verify the update
    $stmt = $pdo->prepare("SELECT username, password FROM admins WHERE username = ?");
    $stmt->execute([$username]);
    $admin = $stmt->fetch();
    
    echo "<h2>Admin Password Updated</h2>";
    echo "<p>Username: " . htmlspecialchars($admin['username']) . "</p>";
    echo "<p>New password hash: " . htmlspecialchars($admin['password']) . "</p>";
    
    // Test the password verification
    if (password_verify($password, $admin['password'])) {
        echo "<p style='color: green; font-weight: bold;'>✅ Password verification successful!</p>";
        echo "<p>You can now log in with:</p>";
        echo "<p>Username: <strong>admin</strong></p>";
        echo "<p>Password: <strong>admin123</strong></p>";
    } else {
        echo "<p style='color: red; font-weight: bold;'>❌ Password verification failed!</p>";
    }
    
    // Add a link to the admin login
    echo "<p><a href='/php-enrollment-system/admin/adminlogin.php' style='display: inline-block; margin-top: 20px; padding: 10px 15px; background-color: #4CAF50; color: white; text-decoration: none; border-radius: 4px;'>Go to Admin Login</a></p>";
    
} catch (PDOException $e) {
    die("<p style='color: red;'>Error: " . $e->getMessage() . "</p>");
}
?>
