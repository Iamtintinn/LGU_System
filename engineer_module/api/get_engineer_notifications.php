<?php
// API endpoint for fetching engineer notifications
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', 'php_errors.log');

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With');

session_start();
require_once '../../config/auth.php';
require_once '../../config/database.php';

// Require engineer role
$auth->requireRole('engineer');

// Initialize database connection
$db = new Database();
$conn = $db->getConnection();

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

try {
    $userId = $_SESSION['user_id'];
    
    // Fetch notifications for the current engineer
    $query = "
        SELECT n.*, u.name as sender_name
        FROM notifications n
        LEFT JOIN users u ON n.sender_id = u.id
        WHERE n.user_id = ? 
        AND (n.type = 'lgu_inspection' OR n.type = 'repair_update' OR n.type = 'system')
        ORDER BY n.created_at DESC
        LIMIT 50
    ";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param('i', $userId);
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
        'notifications' => $notifications,
        'unread_count' => count(array_filter($notifications, fn($n) => !$n['read_status']))
    ]);
    
} catch (Exception $e) {
    error_log("Error fetching engineer notifications: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Failed to fetch notifications'
    ]);
}
?>
