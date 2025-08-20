<?php
// Only define constants if they're not already defined
if (!defined('DB_HOST')) define('DB_HOST', 'localhost');
if (!defined('DB_USER')) define('DB_USER', 'root');
if (!defined('DB_PASS')) define('DB_PASS', '');  // Update with your database password
if (!defined('DB_NAME')) define('DB_NAME', 'enrollment_system');

// Only create a new connection if it doesn't exist
if (!isset($pdo)) {
    try {
        $pdo = new PDO(
            "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
            DB_USER,
            DB_PASS,
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]
        );
    } catch (PDOException $e) {
        die("Database connection failed: " . $e->getMessage());
    }
}

// Database helper functions
if (!function_exists('executeQuery')) {
    /**
     * Execute a database query with parameters
     * @param string $sql SQL query
     * @param array $params Parameters for prepared statement
     * @return PDOStatement|false
     */
    function executeQuery($sql, $params = []) {
        global $pdo;
        
        try {
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt;
        } catch (PDOException $e) {
            error_log("Database Error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Fetch a single row from database
     * @param string $sql SQL query
     * @param array $params Parameters for prepared statement
     * @return array|false
     */
    function fetchOne($sql, $params = []) {
        $stmt = executeQuery($sql, $params);
        return $stmt ? $stmt->fetch(PDO::FETCH_ASSOC) : false;
    }

    /**
     * Fetch all rows from database
     * @param string $sql SQL query
     * @param array $params Parameters for prepared statement
     * @return array
     */
    function fetchAll($sql, $params = []) {
        $stmt = executeQuery($sql, $params);
        return $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
    }
}

return $pdo;
?>
