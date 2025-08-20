<?php
require_once 'config/database.php';

// Check if admin exists
$stmt = $pdo->query("SELECT * FROM admins WHERE username = 'admin'");
$admin = $stmt->fetch();

echo "<h2>Admin User Check</h2>";

if ($admin) {
    echo "<p>Admin user found:</p>";
    echo "<pre>";
    print_r($admin);
    echo "</pre>";
    
    // Test password verification
    $password = 'admin123';
    if (password_verify($password, $admin['password'])) {
        echo "<p style='color: green;'>Password verification successful!</p>";
    } else {
        echo "<p style='color: red;'>Password verification failed!</p>";
        echo "<p>Stored hash: " . $admin['password'] . "</p>";
        echo "<p>Test password: " . $password . "</p>";
        echo "<p>Hash of test password: " . password_hash($password, PASSWORD_DEFAULT) . "</p>";
    }
} else {
    echo "<p style='color: red;'>No admin user found with username 'admin'</p>";
    
    // Check if admins table exists
    $tableExists = $pdo->query("SHOW TABLES LIKE 'admins'")->rowCount() > 0;
    echo "<p>Admins table exists: " . ($tableExists ? 'Yes' : 'No') . "</p>";
    
    if ($tableExists) {
        // Show all admin users
        $stmt = $pdo->query("SELECT * FROM admins");
        $allAdmins = $stmt->fetchAll();
        
        if (count($allAdmins) > 0) {
            echo "<p>All admin users in database:</p>";
            echo "<pre>";
            print_r($allAdmins);
            echo "</pre>";
        } else {
            echo "<p>No admin users found in the admins table.</p>";
        }
    }
}

// Show all tables in the database
echo "<h3>All Tables in Database:</h3>";
$tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
echo "<pre>";
print_r($tables);
echo "</pre>";
?>
