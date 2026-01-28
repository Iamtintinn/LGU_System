<?php
// API endpoint to get inspections data for workflow
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

// Start session and include required files
session_start();
require_once '../../config/database.php';
require_once '../../config/auth.php';

// Check if user is logged in and has engineer role
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'engineer') {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized access']);
    exit;
}

try {
    $db = new Database();
    $conn = $db->getConnection();
    
    error_log("Database connection successful");
    
    // Get all inspections with basic information
    $stmt = $conn->prepare("
        SELECT 
            i.inspection_id,
            i.location,
            i.inspection_date,
            i.description,
            i.severity,
            i.status,
            i.priority,
            i.estimated_cost,
            i.photos,
            u.first_name as inspector_name
        FROM inspections i
        LEFT JOIN users u ON i.inspector_id = u.id
        ORDER BY i.inspection_date DESC
    ");
    
    if (!$stmt) {
        error_log("Statement preparation failed: " . $conn->error);
        throw new Exception("Failed to prepare statement");
    }
    
    $stmt->execute();
    $inspections = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    
    error_log("Found " . count($inspections) . " inspections");
    
    // Format data for JSON response
    $formattedInpections = array_map(function($inspection) {
        // Parse photos JSON if it exists
        $photos = [];
        if (!empty($inspection['photos'])) {
            $decoded_photos = json_decode($inspection['photos'], true);
            $photos = is_array($decoded_photos) ? $decoded_photos : [];
        }
        
        return [
            'inspection_id' => $inspection['inspection_id'],
            'location' => $inspection['location'],
            'date' => date('M d, Y', strtotime($inspection['inspection_date'])),
            'inspector' => $inspection['inspector_name'],
            'description' => $inspection['description'],
            'severity' => $inspection['severity'],
            'status' => $inspection['status'],
            'priority' => $inspection['priority'],
            'estimated_cost' => (float)$inspection['estimated_cost'],
            'photos' => $photos
        ];
    }, $inspections);
    
    echo json_encode($formattedInpections);
    
} catch (Exception $e) {
    error_log("Error in get_inspections.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Failed to load inspections: ' . $e->getMessage()]);
}

$stmt->close();
$conn->close();
?>
