<?php
require_once 'config/database.php';

try {
    // Test database connection
    $pdo = new PDO("mysql:host=localhost;dbname=enrollment_system", 'root', '');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Check if courses table exists and get count
    $stmt = $pdo->query("SHOW TABLES LIKE 'courses'");
    if ($stmt->rowCount() > 0) {
        // Get course count
        $count = $pdo->query("SELECT COUNT(*) FROM courses")->fetchColumn();
        echo "Found $count courses in the database.\n";
        
        // List all courses if any exist
        if ($count > 0) {
            $courses = $pdo->query("SELECT id, title, instructor, created_at FROM courses ORDER BY created_at DESC")->fetchAll(PDO::FETCH_ASSOC);
            echo "\nList of courses:\n";
            echo str_pad("ID", 5) . str_pad("Title", 40) . str_pad("Instructor", 20) . "Created At\n";
            echo str_repeat("-", 80) . "\n";
            
            foreach ($courses as $course) {
                echo str_pad($course['id'], 5) . 
                     str_pad(substr($course['title'], 0, 37) . (strlen($course['title']) > 37 ? '...' : ''), 40) . 
                     str_pad($course['instructor'], 20) . 
                     $course['created_at'] . "\n";
            }
        } else {
            echo "The courses table exists but is empty.\n";
        }
    } else {
        echo "The 'courses' table does not exist in the database.\n";
    }
} catch (PDOException $e) {
    echo "Database error: " . $e->getMessage() . "\n";
}
?>
