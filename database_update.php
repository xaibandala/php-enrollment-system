<?php
/**
 * Database Update Script
 * 
 * This script updates the database schema to the latest version.
 * Make sure to back up your database before running this script.
 */

require_once 'config/database.php';

try {
    // Check if the script is accessed via command line or web
    $isCli = php_sapi_name() === 'cli';
    
    if (!$isCli) {
        // Only allow access from localhost for web access
        if ($_SERVER['REMOTE_ADDR'] !== '127.0.0.1' && $_SERVER['REMOTE_ADDR'] !== '::1') {
            die('Access denied. This script can only be run from localhost.');
        }
        
        // Start output buffering
        ob_start();
        echo "<pre>\n";
        echo "=== Database Update Tool ===\n\n";
    }
    
    // Function to output messages
    function output($message) {
        global $isCli;
        if ($isCli) {
            echo "$message\n";
        } else {
            echo htmlspecialchars($message) . "\n";
        }
    }
    
    // Check database connection
    output("Checking database connection...");
    
    // Test connection
    $pdo->query("SELECT 1");
    output("✓ Database connection successful");
    
    // Check if users table exists
    $usersTableExists = $pdo->query("SHOW TABLES LIKE 'users'")->rowCount() > 0;
    
    if (!$usersTableExists) {
        die("Error: Users table not found. Please run the database.sql file to create the initial database structure.");
    }
    
    // Check if password_reset_tokens table exists
    $tokensTableExists = $pdo->query("SHOW TABLES LIKE 'password_reset_tokens'")->rowCount() > 0;
    
    if ($tokensTableExists) {
        output("✓ Password reset tokens table already exists");
    } else {
        // Create password_reset_tokens table
        output("Creating password_reset_tokens table...");
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS password_reset_tokens (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL,
                token VARCHAR(255) NOT NULL,
                expires_at TIMESTAMP NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                used TINYINT(1) DEFAULT 0,
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
                INDEX idx_token (token),
                INDEX idx_user (user_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ");
        output("✓ Created password_reset_tokens table");
    }
    
    // Check if last_login column exists in users table
    $lastLoginColumnExists = false;
    $columns = $pdo->query("SHOW COLUMNS FROM users LIKE 'last_login'")->fetchAll();
    $lastLoginColumnExists = count($columns) > 0;
    
    if ($lastLoginColumnExists) {
        output("✓ last_login column already exists in users table");
    } else {
        // Add last_login column
        output("Adding last_login column to users table...");
        $pdo->exec("ALTER TABLE users ADD COLUMN last_login TIMESTAMP NULL DEFAULT NULL AFTER role");
        output("✓ Added last_login column to users table");
    }
    
    // Check if registration_date column exists in users table
    $regDateColumnExists = false;
    $columns = $pdo->query("SHOW COLUMNS FROM users LIKE 'registration_date'")->fetchAll();
    $regDateColumnExists = count($columns) > 0;
    
    if ($regDateColumnExists) {
        output("✓ registration_date column already exists in users table");
    } else {
        // Add registration_date column
        output("Adding registration_date column to users table...");
        $pdo->exec("ALTER TABLE users ADD COLUMN registration_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP AFTER last_login");
        
        // Update existing users with current timestamp
        $pdo->exec("UPDATE users SET registration_date = created_at WHERE registration_date IS NULL");
        output("✓ Added registration_date column to users table");
    }
    
    // Add indexes if they don't exist
    output("Checking indexes...");
    $indexes = [
        'idx_email' => 'email',
        'idx_role' => 'role'
    ];
    
    foreach ($indexes as $indexName => $column) {
        $indexExists = $pdo->query("SHOW INDEX FROM users WHERE Key_name = '$indexName'")->rowCount() > 0;
        
        if (!$indexExists) {
            output("Adding $indexName index on $column column...");
            $pdo->exec("CREATE INDEX $indexName ON users($column)");
            output("✓ Added $indexName index");
        } else {
            output("✓ $indexName index already exists");
        }
    }
    
    // Add date_of_birth column if it doesn't exist
    $dateOfBirthColumnExists = $pdo->query("SHOW COLUMNS FROM users LIKE 'date_of_birth'")->rowCount() > 0;
    if (!$dateOfBirthColumnExists) {
        output("Adding date_of_birth column to users table...");
        // First add the column as nullable
        $pdo->exec("ALTER TABLE users ADD COLUMN date_of_birth DATE NULL AFTER password");
        output("✓ Added date_of_birth column to users table");
    }

    // Add address-related columns if they don't exist
    $addressColumns = [
        'address' => 'TEXT NOT NULL',
        'city' => 'VARCHAR(100) NOT NULL',
        'state' => 'VARCHAR(100) NOT NULL',
        'postal_code' => 'VARCHAR(20) NOT NULL',
        'country' => 'VARCHAR(100) NOT NULL'
    ];

    foreach ($addressColumns as $column => $definition) {
        $columnExists = $pdo->query("SHOW COLUMNS FROM users LIKE '$column'")->rowCount() > 0;
        if (!$columnExists) {
            output("Adding $column column to users table...");
            $pdo->exec("ALTER TABLE users ADD COLUMN $column $definition");
            output("✓ Added $column column to users table");
        }
    }

    // Set default values for existing records
    $pdo->exec("UPDATE users SET 
        date_of_birth = '2000-01-01'
        WHERE date_of_birth IS NULL
    ") or die(print_r($pdo->errorInfo(), true));
    
    // Now set the column to NOT NULL after updating all records
    $pdo->exec("ALTER TABLE users MODIFY COLUMN date_of_birth DATE NOT NULL");
    
    // Update other columns
    $pdo->exec("UPDATE users SET 
        address = COALESCE(address, 'Not specified'),
        city = COALESCE(city, 'Not specified'),
        state = COALESCE(state, 'Not specified'),
        postal_code = COALESCE(postal_code, '00000'),
        country = COALESCE(country, 'Not specified')
    ") or die(print_r($pdo->errorInfo(), true));
    output("✓ Set default values for new columns");

    // Update existing users with registration dates...
    output("Updating existing users with registration dates...");
    $updated = $pdo->exec("UPDATE users SET registration_date = created_at WHERE registration_date IS NULL");
    output("✓ Updated $updated users with registration dates");
    
    output("\nDatabase update completed successfully!");
    
    if (!$isCli) {
        echo "</pre>";
        ob_end_flush();
    }
    
} catch (PDOException $e) {
    $error = "Database Error: " . $e->getMessage();
    
    if (isset($isCli) && $isCli) {
        echo "$error\n";
    } else {
        echo "<div style='color: red; font-weight: bold;'>$error</div>";
    }
    
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    
    exit(1);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Database Update Tool</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            margin: 0;
            padding: 20px;
            background-color: #f5f5f5;
        }
        .container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            padding: 20px;
            border-radius: 5px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        h1 {
            color: #333;
            border-bottom: 1px solid #eee;
            padding-bottom: 10px;
        }
        pre {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 4px;
            border: 1px solid #dee2e6;
            overflow-x: auto;
        }
        .success {
            color: #155724;
            background-color: #d4edda;
            border-color: #c3e6cb;
            padding: 10px;
            margin: 10px 0;
            border-radius: 4px;
        }
        .btn {
            display: inline-block;
            font-weight: 400;
            text-align: center;
            white-space: nowrap;
            vertical-align: middle;
            user-select: none;
            border: 1px solid transparent;
            padding: 0.375rem 0.75rem;
            font-size: 1rem;
            line-height: 1.5;
            border-radius: 0.25rem;
            transition: color 0.15s ease-in-out, background-color 0.15s ease-in-out, 
                        border-color 0.15s ease-in-out, box-shadow 0.15s ease-in-out;
            text-decoration: none;
            margin-top: 10px;
        }
        .btn-primary {
            color: #fff;
            background-color: #007bff;
            border-color: #007bff;
        }
        .btn-primary:hover {
            background-color: #0069d9;
            border-color: #0062cc;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Database Update Tool</h1>
        
        <?php if ($_SERVER['REQUEST_METHOD'] !== 'POST'): ?>
            <p>This tool will update your database schema to the latest version.</p>
            <p><strong>Important:</strong> Please make sure to back up your database before proceeding.</p>
            
            <form method="POST" action="">
                <button type="submit" class="btn btn-primary">Run Database Update</button>
            </form>
        <?php endif; ?>
    </div>
</body>
</html>
