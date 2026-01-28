<?php
// API endpoint for marking notifications as read
session_start();
require_once '../config/auth.php';
require_once '../config/database.php';

// Require LGU officer or admin role
$auth->requireAnyRole(['lgu_officer', 'admin']);

// Initialize database connection
$db = new Database();
$conn = $db->getConnection();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

try {
    $input = json_decode(file_get_contents('php://input'), true);
    $notification_id = $input['notification_id'] ?? '';
    
    if (empty($notification_id)) {
        throw new Exception('Notification ID is required');
    }
    
    // Mark notification as read
    $stmt = $conn->prepare("UPDATE notifications SET read_status = 1 WHERE id = ? AND user_id = ?");
    $stmt->bind_param('ii', $notification_id, $_SESSION['user_id']);
    $stmt->execute();
    
    if ($stmt->affected_rows > 0) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Notification not found']);
    }
    
} catch (Exception $e) {
    error_log("Error marking notification as read: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Failed to mark notification as read'
    ]);
}

$stmt->close();
$conn->close();
?>
