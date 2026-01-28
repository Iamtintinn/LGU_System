<?php
// Simple test file for the backend API
header('Content-Type: application/json');

// Test database connection
require_once '../config/database.php';

try {
    $db = new Database();
    $conn = $db->getConnection();
    
    // Test query
    $result = $conn->query("SELECT COUNT(*) as user_count FROM users");
    $row = $result->fetch_assoc();
    
    echo json_encode([
        'status' => 'success',
        'message' => 'Backend API is working',
        'database_connection' => 'OK',
        'user_count' => $row['user_count']
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'Database connection failed',
        'error' => $e->getMessage()
    ]);
}
?>
