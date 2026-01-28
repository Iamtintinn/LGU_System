<?php
// API endpoint for getting notifications
session_start();
require_once '../config/auth.php';
require_once '../config/database.php';

// Require LGU officer or admin role
$auth->requireAnyRole(['lgu_officer', 'admin']);

// Initialize database connection
$db = new Database();
$conn = $db->getConnection();

header('Content-Type: application/json');

try {
    $user_id = $_SESSION['user_id'];
    
    // Get notifications for the current user
    $stmt = $conn->prepare("
        SELECT n.*, u.name as sender_name 
        FROM notifications n 
        LEFT JOIN users u ON n.sender_id = u.id 
        WHERE n.user_id = ? 
        ORDER BY n.created_at DESC 
        LIMIT 50
    ");
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $notifications = [];
    while ($row = $result->fetch_assoc()) {
        $notifications[] = [
            'id' => $row['id'],
            'type' => $row['type'],
            'title' => $row['title'],
            'message' => $row['message'],
            'data' => $row['data'],
            'created_at' => $row['created_at'],
            'read_status' => (bool)$row['read_status'],
            'sender_name' => $row['sender_name']
        ];
    }
    
    echo json_encode([
        'success' => true,
        'notifications' => $notifications
    ]);
    
} catch (Exception $e) {
    error_log("Error getting notifications: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Failed to load notifications'
    ]);
}

$stmt->close();
$conn->close();
?>
