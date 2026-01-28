<?php
// API endpoint to get inspections data for the workflow - NO AUTH for testing
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

// Bypass authentication for testing
// session_start();
// require_once '../../config/database.php';
// require_once '../../config/auth.php';

// Check if user is logged in and has engineer role
// if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'engineer') {
//     http_response_code(401);
//     echo json_encode(['error' => 'Unauthorized access']);
//     exit;
// }

error_log("get_inspections_no_auth.php called");

try {
    require_once '../../config/database.php';
    $database = new Database();
    $conn = $database->getConnection();
    
    error_log("Database connection successful");
    
    // Get all inspections with inspector information
    $stmt = $conn->prepare("
        SELECT 
            i.inspection_id,
            i.location,
            i.inspection_date,
            i.description,
            i.severity,
            i.status,
            i.review_date,
            i.review_notes,
            i.priority,
            i.estimated_cost,
            i.photos,
            i.estimated_damage,
            u.first_name as inspector_first_name,
            u.last_name as inspector_last_name,
            reviewer.first_name as reviewer_first_name,
            reviewer.last_name as reviewer_last_name
        FROM inspections i
        LEFT JOIN users u ON i.inspector_id = u.id
        LEFT JOIN users reviewer ON i.reviewed_by = reviewer.id
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
        if ($inspection['photos']) {
            $photos = json_decode($inspection['photos'], true) ?: [];
        }
        
        return [
            'inspection_id' => $inspection['inspection_id'],
            'location' => $inspection['location'],
            'date' => date('M j, Y', strtotime($inspection['inspection_date'])),
            'inspector' => trim($inspection['inspector_first_name'] . ' ' . $inspection['inspector_last_name']),
            'description' => $inspection['description'],
            'severity' => $inspection['severity'],
            'status' => $inspection['status'],
            'review_date' => $inspection['review_date'] ? date('M j, Y', strtotime($inspection['review_date'])) : null,
            'reviewed_by' => ($inspection['reviewed_by'] && isset($inspection['reviewer_first_name']) && isset($inspection['reviewer_last_name'])) 
                ? trim($inspection['reviewer_first_name'] . ' ' . $inspection['reviewer_last_name']) 
                : null,
            'review_notes' => $inspection['review_notes'],
            'priority' => $inspection['priority'],
            'estimated_cost' => $inspection['estimated_cost'] ? (float)$inspection['estimated_cost'] : null,
            'photos' => $photos,
            'estimated_damage' => $inspection['estimated_damage']
        ];
    }, $inspections);
    
    echo json_encode($formattedInpections);
    
} catch (Exception $e) {
    error_log("Error in get_inspections_no_auth.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
?>
