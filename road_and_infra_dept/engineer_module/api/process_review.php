<?php
// API endpoint to process inspection reviews (approve/reject)
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

// Start session and include required files
session_start();
require_once '../../config/database.php';

// Check if user is logged in and has engineer role
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'engineer') {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized access']);
    exit;
}

// Get POST data
$data = json_decode(file_get_contents('php://input'), true);

if (!$data || !isset($data['inspection_id']) || !isset($data['action'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing required fields']);
    exit;
}

$inspectionId = $data['inspection_id'];
$action = $data['action']; // 'approve' or 'reject'
$priority = $data['priority'] ?? null;
$estimatedCost = $data['estimated_cost'] ?? null;
$notes = $data['notes'] ?? null;

try {
    $database = new Database();
    $conn = $database->getConnection();
    
    // Start transaction
    $conn->begin_transaction();
    
    if ($action === 'approve') {
        // Update inspection status to approved
        $stmt = $conn->prepare("
            UPDATE inspections 
            SET status = 'approved', 
                reviewed_by = ?, 
                review_date = CURDATE(),
                review_notes = ?,
                priority = ?,
                estimated_cost = ?
            WHERE inspection_id = ?
        ");
        $stmt->bind_param("sssds", $_SESSION['user_id'], $notes, $priority, $estimatedCost, $inspectionId);
        
        if (!$stmt->execute()) {
            throw new Exception("Failed to update inspection: " . $conn->error);
        }
        
        // Create repair task
        $taskId = 'REP-' . str_pad(mt_rand(1, 9999), 4, '0', STR_PAD_LEFT);
        $taskStmt = $conn->prepare("
            INSERT INTO repair_tasks (task_id, inspection_id, assigned_to, status, priority, estimated_cost, created_date, created_by)
            VALUES (?, ?, 'Maintenance Team A', 'pending', ?, ?, CURDATE(), ?)
        ");
        $taskStmt->bind_param("ssdsi", $taskId, $inspectionId, $priority, $estimatedCost, $_SESSION['user_id']);
        
        if (!$taskStmt->execute()) {
            throw new Exception("Failed to create repair task: " . $conn->error);
        }
        
        $response = [
            'success' => true,
            'message' => 'Inspection approved and repair task created successfully',
            'task_id' => $taskId,
            'new_status' => 'approved'
        ];
        
    } elseif ($action === 'reject') {
        // Update inspection status to rejected
        $stmt = $conn->prepare("
            UPDATE inspections 
            SET status = 'rejected', 
                reviewed_by = ?, 
                review_date = CURDATE(),
                review_notes = ?
            WHERE inspection_id = ?
        ");
        $stmt->bind_param("sss", $_SESSION['user_id'], $notes, $inspectionId);
        
        if (!$stmt->execute()) {
            throw new Exception("Failed to reject inspection: " . $conn->error);
        }
        
        $response = [
            'success' => true,
            'message' => 'Inspection rejected successfully',
            'new_status' => 'rejected'
        ];
        
    } else {
        throw new Exception("Invalid action specified");
    }
    
    // Commit transaction
    $conn->commit();
    
    echo json_encode($response);
    
} catch (Exception $e) {
    // Rollback transaction on error
    if (isset($conn)) {
        $conn->rollback();
    }
    
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>
