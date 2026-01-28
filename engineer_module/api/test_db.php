<?php
// Simple database connection test
session_start();
require_once '../../config/database.php';

echo "<h2>Database Connection Test</h2>";

try {
    $database = new Database();
    $conn = $database->getConnection();
    echo "<p style='color: green;'>✅ Database connection successful!</p>";
    
    // Test if inspections table exists
    $result = $conn->query("SHOW TABLES LIKE 'inspections'");
    if ($result->num_rows > 0) {
        echo "<p style='color: green;'>✅ Inspections table exists!</p>";
        
        // Count inspections
        $count = $conn->query("SELECT COUNT(*) as count FROM inspections");
        $row = $count->fetch_assoc();
        echo "<p style='color: blue;'>📊 Found {$row['count']} inspections</p>";
        
        // Show sample data
        $sample = $conn->query("SELECT inspection_id, location, status FROM inspections LIMIT 3");
        echo "<h3>Sample Data:</h3>";
        echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
        echo "<tr><th>ID</th><th>Location</th><th>Status</th></tr>";
        while ($row = $sample->fetch_assoc()) {
            echo "<tr><td>{$row['inspection_id']}</td><td>{$row['location']}</td><td>{$row['status']}</td></tr>";
        }
        echo "</table>";
        
    } else {
        echo "<p style='color: red;'>❌ Inspections table not found!</p>";
        echo "<p>Please run the SQL setup file first.</p>";
    }
    
    // Test if users table exists
    $result = $conn->query("SHOW TABLES LIKE 'users'");
    if ($result->num_rows > 0) {
        echo "<p style='color: green;'>✅ Users table exists!</p>";
        
        $count = $conn->query("SELECT COUNT(*) as count FROM users");
        $row = $count->fetch_assoc();
        echo "<p style='color: blue;'>👥 Found {$row['count']} users</p>";
    } else {
        echo "<p style='color: orange;'>⚠️ Users table not found!</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Database error: " . $e->getMessage() . "</p>";
}

echo "<hr>";
echo "<h3>Session Info:</h3>";
echo "<p>User ID: " . (isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 'Not logged in') . "</p>";
echo "<p>User Role: " . (isset($_SESSION['role']) ? $_SESSION['role'] : 'Not set') . "</p>";

echo "<hr>";
echo "<p><a href='../inspection_workflow.php'>← Back to Inspection Workflow</a></p>";
?>
