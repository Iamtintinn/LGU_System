<?php
// Notifications page for citizen users
session_start();
require_once '../config/auth.php';

// Set timezone to ensure accurate time calculations
date_default_timezone_set('Asia/Manila');

// Require login to access this page
if (!$auth->isLoggedIn()) {
    header('Location: ../login.php');
    exit;
}

// Get current user ID
$userId = $auth->getUserId();

// Handle marking notifications as read
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['notification_id'])) {
    $notificationId = filter_input(INPUT_POST, 'notification_id', FILTER_VALIDATE_INT);
    if ($notificationId) {
        $auth->markNotificationRead($notificationId, $userId);
    }
}

// Get all notifications (read and unread)
try {
    $database = new Database();
    $conn = $database->getConnection();
    
    $stmt = $conn->prepare("
        SELECT id, title, message, type, is_read, created_at, read_at 
        FROM notifications 
        WHERE user_id = ? 
        ORDER BY created_at DESC
    ");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    
    $result = $stmt->get_result();
    $notifications = [];
    
    while ($row = $result->fetch_assoc()) {
        $notifications[] = $row;
    }
    
    $stmt->close();
} catch (Exception $e) {
    error_log("Error fetching notifications: " . $e->getMessage());
    $notifications = [];
}

// Get unread count
$unreadCount = $auth->getUnreadNotificationCount($userId);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notifications | LGU Citizen Portal</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        @import url("https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap");

        :root {
            --primary: #2563eb;
            --primary-hover: #1d4ed8;
            --glass-bg: rgba(255, 255, 255, 0.85);
            --glass-border: rgba(255, 255, 255, 0.4);
            --text-main: #1e293b;
            --text-muted: #64748b;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: "Inter", sans-serif;
        }

        body {
            height: 100vh;
            background: url('../user_and_access_management_module/assets/img/cityhall.jpeg') center/cover no-repeat fixed;
            position: relative;
            overflow: hidden;
            color: var(--text-main);
        }

        body::before {
            content: "";
            position: absolute;
            inset: 0;
            backdrop-filter: blur(8px);
            background: rgba(15, 23, 42, 0.45);
            z-index: 0;
        }

        /* Modal overlay to prevent navigation */
        .modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.7);
            backdrop-filter: blur(5px);
            z-index: 1000;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .main-content {
            position: relative;
            margin: 0;
            height: auto;
            padding: 0;
            display: flex;
            flex-direction: column;
            overflow-y: auto;
            z-index: 1;
            max-width: 90vw;
            max-height: 90vh;
        }

        .module-header {
            color: white;
            margin-bottom: 30px;
        }

        .module-header h1 {
            font-size: 2.2rem;
            font-weight: 700;
            margin-bottom: 8px;
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .module-header p {
            font-size: 1rem;
            opacity: 0.9;
            letter-spacing: 0.5px;
        }

        .header-divider {
            border: none;
            height: 1px;
            background: rgba(255, 255, 255, 0.3);
            margin: 15px 0;
        }

        .notifications-container {
            background: var(--glass-bg);
            backdrop-filter: blur(20px);
            border-radius: 20px;
            border: 1px solid var(--glass-border);
            padding: 30px;
            box-shadow: 0 25px 50px rgba(0, 0, 0, 0.3);
            max-width: 800px;
            max-height: 80vh;
            margin: 0 auto;
            position: relative;
            overflow: hidden;
        }

        .modal-close-btn {
            position: absolute;
            top: 15px;
            right: 15px;
            background: rgba(255, 255, 255, 0.2);
            border: 1px solid rgba(255, 255, 255, 0.3);
            color: white;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            font-size: 18px;
            transition: all 0.3s;
            z-index: 10;
        }

        .modal-close-btn:hover {
            background: rgba(255, 255, 255, 0.3);
            transform: scale(1.1);
        }

        .notifications-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
            padding-bottom: 15px;
            border-bottom: 1px solid #e5e7eb;
        }

        .notifications-title {
            font-size: 1.5rem;
            font-weight: 700;
            color: #1e293b;
        }

        .unread-badge {
            background: #ef4444;
            color: white;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.875rem;
            font-weight: 600;
        }

        .mark-all-read-btn {
            background: var(--primary);
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 8px;
            cursor: pointer;
            font-size: 0.875rem;
            font-weight: 500;
            transition: background-color 0.2s;
        }

        .mark-all-read-btn:hover {
            background: var(--primary-hover);
        }

        .notification-list {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }

        .notification-item {
            background: white;
            border-radius: 12px;
            padding: 20px;
            border-left: 4px solid #e5e7eb;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
            transition: all 0.2s;
            cursor: pointer;
        }

        .notification-item:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }

        .notification-item.unread {
            background: #eff6ff;
            border-left-color: var(--primary);
        }

        .notification-item.permission_granted {
            border-left-color: #10b981;
        }

        .notification-item.permission_granted.unread {
            background: #ecfdf5;
            border-left-color: #059669;
        }

        .notification-header-info {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 10px;
        }

        .notification-title {
            font-weight: 600;
            color: #1f2937;
            font-size: 1.1rem;
        }

        .notification-time {
            color: #9ca3af;
            font-size: 0.875rem;
        }

        .notification-message {
            color: #6b7280;
            line-height: 1.5;
            margin-bottom: 10px;
        }

        .notification-type-badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
        }

        .type-permission_granted {
            background: #dcfce7;
            color: #16a34a;
        }

        .type-info {
            background: #dbeafe;
            color: #2563eb;
        }

        .type-warning {
            background: #fef3c7;
            color: #d97706;
        }

        .type-error {
            background: #fee2e2;
            color: #dc2626;
        }

        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #6b7280;
        }

        .empty-state i {
            font-size: 4rem;
            margin-bottom: 20px;
            opacity: 0.3;
        }

        .empty-state h3 {
            font-size: 1.5rem;
            margin-bottom: 10px;
            color: #374151;
        }

        .empty-state p {
            font-size: 1rem;
        }

        .read-indicator {
            display: inline-block;
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: #10b981;
            margin-left: 10px;
        }

        .unread-indicator {
            background: #ef4444;
        }
    </style>
</head>
<body>
    <!-- Modal Overlay -->
    <div class="modal-overlay">
        <main class="main-content">
            <div class="notifications-container">
                <!-- Close Button -->
                <button class="modal-close-btn" onclick="closeModal()" title="Close Notifications">
                    <i class="fas fa-times"></i>
                </button>
                
                <header class="module-header">
                    <h1><i class="fas fa-bell"></i> Notifications</h1>
                    <p>Stay updated with your account activities and permissions</p>
                    <hr class="header-divider">
                </header>
                <div class="notifications-header">
                    <div>
                        <h2 class="notifications-title">All Notifications</h2>
                        <?php if ($unreadCount > 0): ?>
                            <span class="unread-badge"><?php echo $unreadCount; ?> unread</span>
                        <?php endif; ?>
                    </div>
                    <?php if ($unreadCount > 0): ?>
                        <form method="POST" style="display: inline;">
                            <input type="hidden" name="mark_all_read" value="1">
                            <button type="submit" class="mark-all-read-btn" onclick="this.form.submit()">Mark All as Read</button>
                        </form>
                    <?php endif; ?>
                </div>

                <div class="notification-list">
                    <?php if (empty($notifications)): ?>
                        <div class="empty-state">
                            <i class="fas fa-bell-slash"></i>
                            <h3>No Notifications</h3>
                            <p>You don't have any notifications yet. We'll notify you when there are important updates.</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($notifications as $notification): ?>
                            <div class="notification-item <?php echo $notification['is_read'] ? '' : 'unread'; ?> <?php echo 'notification-type-' . $notification['type']; ?>">
                                <div class="notification-header-info">
                                    <h3 class="notification-title">
                                        <?php echo htmlspecialchars($notification['title']); ?>
                                        <?php if ($notification['is_read']): ?>
                                            <span class="read-indicator"></span>
                                        <?php else: ?>
                                            <span class="read-indicator unread-indicator"></span>
                                        <?php endif; ?>
                                    </h3>
                                    <span class="notification-time" data-timestamp="<?php echo htmlspecialchars($notification['created_at']); ?>" title="Created: <?php echo htmlspecialchars($notification['created_at']); ?>"><?php echo formatTime($notification['created_at']); ?></span>
                                </div>
                                <p class="notification-message"><?php echo htmlspecialchars($notification['message']); ?></p>
                                <span class="notification-type-badge type-<?php echo $notification['type']; ?>">
                                    <?php echo formatType($notification['type']); ?>
                                </span>
                                
                                <?php if (!$notification['is_read']): ?>
                                    <form method="POST" style="margin-top: 15px;">
                                        <input type="hidden" name="notification_id" value="<?php echo $notification['id']; ?>">
                                        <button type="submit" style="background: #f3f4f6; color: #374151; border: none; padding: 6px 12px; border-radius: 6px; cursor: pointer; font-size: 0.875rem;">
                                            Mark as Read
                                        </button>
                                    </form>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>

    <script>
        function closeModal() {
            // Redirect back to the previous page or dashboard
            window.location.href = 'index.php';
        }

        // Close modal on Escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeModal();
            }
        });

        // Close modal on overlay click
        document.querySelector('.modal-overlay').addEventListener('click', function(e) {
            if (e.target === this) {
                closeModal();
            }
        });

        // Real-time time updates
        function updateNotificationTimes() {
            const timeElements = document.querySelectorAll('.notification-time');
            timeElements.forEach(element => {
                const timestamp = element.dataset.timestamp;
                if (timestamp) {
                    element.textContent = formatTimeClient(timestamp);
                }
            });
        }

        // Client-side time formatting (mirrors PHP function)
        function formatTimeClient(timestamp) {
            const date = new Date(timestamp);
            const now = new Date();
            const diff = Math.floor((now - date) / 1000); // seconds
            
            if (diff < 60) {
                return diff <= 1 ? 'Just now' : diff + ' seconds ago';
            } else if (diff < 3600) {
                const minutes = Math.floor(diff / 60);
                return minutes + ' minute' + (minutes > 1 ? 's' : '') + ' ago';
            } else if (diff < 86400) {
                const hours = Math.floor(diff / 3600);
                const remainingMinutes = Math.floor((diff % 3600) / 60);
                if (remainingMinutes > 0) {
                    return hours + ' hour' + (hours > 1 ? 's' : '') + ' ' + remainingMinutes + ' minute' + (remainingMinutes > 1 ? 's' : '') + ' ago';
                }
                return hours + ' hour' + (hours > 1 ? 's' : '') + ' ago';
            } else if (diff < 172800) { // Less than 2 days
                return 'Yesterday at ' + date.toLocaleTimeString('en-US', { hour: 'numeric', minute: '2-digit', hour12: true });
            } else if (diff < 604800) { // Less than 7 days
                const days = Math.floor(diff / 86400);
                return days + ' day' + (days > 1 ? 's' : '') + ' ago at ' + date.toLocaleTimeString('en-US', { hour: 'numeric', minute: '2-digit', hour12: true });
            } else {
                return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' }) + ' at ' + date.toLocaleTimeString('en-US', { hour: 'numeric', minute: '2-digit', hour12: true });
            }
        }

        // Store timestamps and update times every 30 seconds
        document.addEventListener('DOMContentLoaded', function() {
            // Store timestamps in data attributes
            const timeElements = document.querySelectorAll('.notification-time');
            timeElements.forEach(element => {
                element.dataset.timestamp = element.textContent;
            });
            
            // Update times every 30 seconds
            setInterval(updateNotificationTimes, 30000);
        });
    </script>

    <?php
    function formatTime($timestamp) {
        $date = new DateTime($timestamp);
        $now = new DateTime();
        
        // Debug: Show actual times for troubleshooting
        // error_log("Notification time: " . $date->format('Y-m-d H:i:s'));
        // error_log("Current time: " . $now->format('Y-m-d H:i:s'));
        
        $diff = $date->diff($now);
        
        // Calculate total seconds for more precision
        $totalSeconds = ($diff->days * 24 * 60 * 60) + ($diff->h * 60 * 60) + ($diff->i * 60) + $diff->s;
        
        // Debug: Show difference
        // error_log("Total seconds difference: " . $totalSeconds);
        
        if ($totalSeconds < 60) {
            return $totalSeconds <= 1 ? 'Just now' : $totalSeconds . ' seconds ago';
        } elseif ($totalSeconds < 3600) {
            $minutes = floor($totalSeconds / 60);
            return $minutes . ' minute' . ($minutes > 1 ? 's' : '') . ' ago';
        } elseif ($totalSeconds < 86400) {
            $hours = floor($totalSeconds / 3600);
            $remainingMinutes = floor(($totalSeconds % 3600) / 60);
            if ($remainingMinutes > 0) {
                return $hours . ' hour' . ($hours > 1 ? 's' : '') . ' ' . $remainingMinutes . ' minute' . ($remainingMinutes > 1 ? 's' : '') . ' ago';
            }
            return $hours . ' hour' . ($hours > 1 ? 's' : '') . ' ago';
        } elseif ($diff->days == 1) {
            return 'Yesterday at ' . $date->format('g:i A');
        } elseif ($diff->days < 7) {
            return $diff->days . ' day' . ($diff->days > 1 ? 's' : '') . ' ago at ' . $date->format('g:i A');
        } else {
            return $date->format('M j, Y \a\t g:i A');
        }
    }

    function formatType($type) {
        $types = [
            'permission_granted' => 'Permission Granted',
            'info' => 'Information',
            'warning' => 'Warning',
            'error' => 'Error',
            'success' => 'Success'
        ];
        return $types[$type] ?? ucfirst($type);
    }

    // Handle mark all as read
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['mark_all_read'])) {
        $auth->markAllNotificationsRead($userId);
        // Redirect to prevent form resubmission
        header("Location: " . $_SERVER['PHP_SELF']);
        exit;
    }
    ?>
</body>
</html>
