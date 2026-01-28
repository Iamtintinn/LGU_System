<?php
// Direct API test without session requirements
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

echo json_encode(['test' => 'API is reachable']);

// Now test the database connection directly
try {
    require_once '../../config/database.php';
    $database = new Database();
    $conn = $database->getConnection();
    
    // Simple test query
    $result = $conn->query("SELECT COUNT(*) as count FROM inspections");
    $row = $result->fetch_assoc();
    
    echo json_encode([
        'database_connected' => true,
        'inspections_count' => $row['count'],
        'tables_exist' => true
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'database_connected' => false,
        'error' => $e->getMessage()
    ]);
}
?>
