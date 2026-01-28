<?php
// API endpoint for marking engineer notifications as read
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', 'php_errors.log');

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With');

session_start();
require_once '../../config/auth.php';
require_once '../../config/database.php';

// Require engineer role
$auth->requireRole('engineer');

// Initialize database connection
$db = new Database();
$conn = $db->getConnection();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

// Get JSON input
$json_input = file_get_contents('php://input');
$data = json_decode($json_input, true);

if (!$data || !isset($data['notification_id'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Missing notification ID']);
    exit;
}

try {
    $notificationId = $data['notification_id'];
    $userId = $_SESSION['user_id'];
    
    // Mark notification as read
    $stmt = $conn->prepare("
        UPDATE notifications 
        SET read_status = 1 
        WHERE id = ? AND user_id = ?
    ");
    $stmt->bind_param('ii', $notificationId, $userId);
    $stmt->execute();
    
    if ($stmt->affected_rows > 0) {
        echo json_encode([
            'success' => true,
            'message' => 'Notification marked as read'
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'error' => 'Notification not found or already read'
        ]);
    }
    
} catch (Exception $e) {
    error_log("Error marking engineer notification as read: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Failed to update notification'
    ]);
}
?>
