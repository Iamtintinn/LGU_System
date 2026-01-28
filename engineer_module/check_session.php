<?php
session_start();
require_once '../../config/auth.php';
require_once '../../config/database.php';

echo "<h2>Session and Authentication Check</h2>";

echo "<h3>Session Status:</h3>";
echo "<p>User ID: " . (isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 'Not set') . "</p>";
echo "<p>User Role: " . (isset($_SESSION['role']) ? $_SESSION['role'] : 'Not set') . "</p>";
echo "<p>Session ID: " . session_id() . "</p>";

echo "<h3>Auth Object:</h3>";
if (isset($auth)) {
    echo "<p>Auth object loaded successfully</p>";
} else {
    echo "<p style='color: red;'>Auth object not found</p>";
}

echo "<h3>Database Connection:</h3>";
try {
    $database = new Database();
    $conn = $database->getConnection();
    echo "<p style='color: green;'>✅ Database connection successful</p>";
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Database connection failed: " . $e->getMessage() . "</p>";
}

echo "<h3>Current User:</h3>";
if (isset($_SESSION['user_id'])) {
    try {
        $stmt = $conn->prepare("SELECT first_name, last_name, role FROM users WHERE id = ?");
        $stmt->bind_param("i", $_SESSION['user_id']);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();
        
        if ($user) {
            echo "<p>Name: {$user['first_name']} {$user['last_name']}</p>";
            echo "<p>Role: {$user['role']}</p>";
            echo "<p style='color: " . ($user['role'] === 'engineer' ? 'green' : 'orange') . ";'>Engineer Access: " . ($user['role'] === 'engineer' ? '✅ Granted' : '❌ Denied') . "</p>";
        } else {
            echo "<p style='color: red;'>User not found in database</p>";
        }
    } catch (Exception $e) {
        echo "<p style='color: red;'>Error fetching user: " . $e->getMessage() . "</p>";
    }
} else {
    echo "<p style='color: red;'>No user logged in</p>";
}

echo "<hr>";
echo "<h3>Test API Access:</h3>";
echo "<p><a href='api/get_inspections_no_auth.php' target='_blank'>Test API without auth</a></p>";
echo "<p><a href='api/get_inspections.php' target='_blank'>Test API with auth</a></p>";

echo "<hr>";
echo "<p><a href='inspection_workflow.php'>← Back to Inspection Workflow</a></p>";
?>
