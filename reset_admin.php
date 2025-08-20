<?php
require_once 'config/database.php';

// New admin credentials
$username = 'admin';
$password = 'admin123';
$email = 'admin@example.com';
$fullName = 'System Administrator';

// Hash the password
$hashedPassword = password_hash($password, PASSWORD_DEFAULT);

try {
    // Check if admin exists
    $stmt = $pdo->prepare("SELECT id FROM admins WHERE username = ?");
    $stmt->execute([$username]);
    $admin = $stmt->fetch();
    
    if ($admin) {
        // Update existing admin
        $stmt = $pdo->prepare("UPDATE admins SET password = ?, email = ?, full_name = ?, role = 'super_admin' WHERE username = ?");
        $stmt->execute([$hashedPassword, $email, $fullName, $username]);
        echo "<p>Existing admin user updated successfully!</p>";
    } else {
        // Create new admin
        $stmt = $pdo->prepare("INSERT INTO admins (username, email, password, full_name, role) VALUES (?, ?, ?, ?, 'super_admin')");
        $stmt->execute([$username, $email, $hashedPassword, $fullName]);
        echo "<p>New admin user created successfully!</p>";
    }
    
    // Show the updated admin user
    $stmt = $pdo->prepare("SELECT * FROM admins WHERE username = ?");
    $stmt->execute([$username]);
    $admin = $stmt->fetch();
    
    echo "<h3>Admin User Details:</h3>";
    echo "<pre>";
    print_r($admin);
    echo "</pre>";
    
    echo "<p>You can now log in with:</p>";
    echo "<p>Username: <strong>" . htmlspecialchars($username) . "</strong></p>";
    echo "<p>Password: <strong>" . htmlspecialchars($password) . "</strong></p>";
    
} catch (PDOException $e) {
    die("<p style='color: red;'>Error: " . $e->getMessage() . "</p>");
}

// Create a link back to the admin login
echo "<p><a href='/php-enrollment-system/admin/adminlogin.php'>Go to Admin Login</a></p>";
?>
