<?php
// API endpoint to get publications for sidebar widget
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

// Start session and include required files
session_start();
require_once '../../config/database.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

try {
    $database = new Database();
    $conn = $database->getConnection();
    
    // Get recent publications (last 30 days)
    $stmt = $conn->prepare("
        SELECT 
            id,
            publication_id,
            road_name,
            issue_summary,
            issue_type,
            severity_public,
            status_public,
            publication_date,
            published_by,
            last_updated
        FROM public_publications 
        WHERE is_published = 1 
        AND archived = 0 
        AND publication_date >= DATE_SUB(NOW(), INTERVAL 30 DAY)
        ORDER BY publication_date DESC
        LIMIT 10
    ");
    
    $stmt->execute();
    $publications = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    
    // Format data for JSON response
    $formattedPublications = array_map(function($pub) {
        return [
            'id' => (int)$pub['id'],
            'publication_id' => $pub['publication_id'],
            'road_name' => $pub['road_name'],
            'issue_summary' => $pub['issue_summary'],
            'issue_type' => $pub['issue_type'],
            'severity_public' => $pub['severity_public'],
            'status_public' => $pub['status_public'],
            'publication_date' => $pub['publication_date'],
            'published_by' => (int)$pub['published_by'],
            'last_updated' => $pub['last_updated']
        ];
    }, $publications);
    
    echo json_encode($formattedPublications);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
?>
