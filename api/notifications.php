<?php
// Notifications API for LGU Road and Infrastructure Department
require_once '../config/auth.php';

header('Content-Type: application/json');

// Allow only POST requests for write operations
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'mark_read':
            markNotificationRead();
            break;
        case 'mark_all_read':
            markAllNotificationsRead();
            break;
        default:
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $action = $_GET['action'] ?? '';
    
    switch ($action) {
        case 'get_unread':
            getUnreadNotifications();
            break;
        case 'get_count':
            getNotificationCount();
            break;
        default:
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
}

function getUnreadNotifications() {
    $userId = filter_input(INPUT_GET, 'user_id', FILTER_VALIDATE_INT);
    
    if (!$userId) {
        echo json_encode(['success' => false, 'message' => 'User ID required']);
        return;
    }
    
    global $auth;
    $notifications = $auth->getUnreadNotifications($userId, 20);
    $unreadCount = $auth->getUnreadNotificationCount($userId);
    
    echo json_encode([
        'success' => true,
        'notifications' => $notifications,
        'unread_count' => $unreadCount
    ]);
}

function getNotificationCount() {
    $userId = filter_input(INPUT_GET, 'user_id', FILTER_VALIDATE_INT);
    
    if (!$userId) {
        echo json_encode(['success' => false, 'message' => 'User ID required']);
        return;
    }
    
    global $auth;
    $count = $auth->getUnreadNotificationCount($userId);
    
    echo json_encode([
        'success' => true,
        'count' => $count
    ]);
}

function markNotificationRead() {
    $notificationId = filter_input(INPUT_POST, 'notification_id', FILTER_VALIDATE_INT);
    $userId = filter_input(INPUT_POST, 'user_id', FILTER_VALIDATE_INT);
    
    if (!$notificationId || !$userId) {
        echo json_encode(['success' => false, 'message' => 'Notification ID and User ID required']);
        return;
    }
    
    global $auth;
    $result = $auth->markNotificationRead($notificationId, $userId);
    
    echo json_encode([
        'success' => $result,
        'message' => $result ? 'Notification marked as read' : 'Failed to mark notification as read'
    ]);
}

function markAllNotificationsRead() {
    $userId = filter_input(INPUT_POST, 'user_id', FILTER_VALIDATE_INT);
    
    if (!$userId) {
        echo json_encode(['success' => false, 'message' => 'User ID required']);
        return;
    }
    
    global $auth;
    $result = $auth->markAllNotificationsRead($userId);
    
    echo json_encode([
        'success' => $result,
        'message' => $result ? 'All notifications marked as read' : 'Failed to mark notifications as read'
    ]);
}
?>
