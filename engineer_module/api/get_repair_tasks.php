<?php
// API endpoint to get repair tasks data
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
    $database = new Database();
    $conn = $database->getConnection();
    
    // Debug: Log connection success
    error_log("Database connection successful for repair tasks");
    
    // Get repair tasks with inspection information
    $stmt = $conn->prepare("
        SELECT 
            rt.task_id,
            rt.inspection_id,
            rt.assigned_to,
            rt.status,
            rt.priority,
            rt.estimated_cost,
            rt.created_date,
            rt.estimated_completion,
            rt.actual_completion,
            rt.notes,
            i.location as inspection_location,
            i.description as inspection_description
        FROM repair_tasks rt
        LEFT JOIN inspections i ON rt.inspection_id = i.inspection_id
        ORDER BY rt.created_date DESC
    ");
    
    if (!$stmt) {
        error_log("Statement preparation failed for repair tasks: " . $conn->error);
        throw new Exception("Failed to prepare repair tasks statement");
    }
    
    $stmt->execute();
    $tasks = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    
    error_log("Found " . count($tasks) . " repair tasks");
    
    // Format data for JSON response
    $formattedTasks = array_map(function($task) {
        return [
            'task_id' => $task['task_id'],
            'inspection_id' => $task['inspection_id'],
            'assigned_to' => $task['assigned_to'],
            'status' => $task['status'],
            'priority' => $task['priority'],
            'estimated_cost' => $task['estimated_cost'] ? (float)$task['estimated_cost'] : null,
            'created_date' => date('M j, Y', strtotime($task['created_date'])),
            'estimated_completion' => $task['estimated_completion'] ? date('M j, Y', strtotime($task['estimated_completion'])) : null,
            'actual_completion' => $task['actual_completion'] ? date('M j, Y', strtotime($task['actual_completion'])) : null,
            'notes' => $task['notes'],
            'inspection_location' => $task['inspection_location'],
            'inspection_description' => $task['inspection_description']
        ];
    }, $tasks);
    
    echo json_encode($formattedTasks);
    
} catch (Exception $e) {
    error_log("Error in get_repair_tasks.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
?>
