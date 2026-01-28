<?php
session_start();
require_once '../config/auth.php';
require_once '../config/database.php';

// Require engineer role
$auth->requireRole('engineer');

// Initialize database connection
$db = new Database();
$conn = $db->getConnection();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notifications | Engineer Portal</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        @import url("https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap");

        :root {
            --primary: #2563eb;
            --primary-hover: #1d4ed8;
            --success: #16a34a;
            --danger: #dc2626;
            --warning: #f59e0b;
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

        .main-content {
            position: relative;
            margin-left: 250px;
            height: 100vh;
            padding: 30px 40px;
            display: flex;
            flex-direction: column;
            overflow-y: auto;
            z-index: 1;
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
            backdrop-filter: blur(10px);
            border-radius: 20px;
            border: 1px solid var(--glass-border);
            padding: 30px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            margin-bottom: 30px;
        }

        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .section-title {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--text-main);
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .notification-item {
            background: white;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 15px;
            border-left: 4px solid var(--primary);
            transition: all 0.3s ease;
            cursor: pointer;
        }

        .notification-item:hover {
            transform: translateX(5px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }

        .notification-item.unread {
            background: linear-gradient(135deg, #f8fafc 0%, #ffffff 100%);
            border-left-color: var(--primary);
        }

        .notification-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 12px;
        }

        .notification-title {
            font-size: 1.1rem;
            font-weight: 600;
            color: var(--text-main);
            margin-bottom: 4px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .notification-meta {
            display: flex;
            align-items: center;
            gap: 15px;
            font-size: 0.85rem;
            color: var(--text-muted);
        }

        .notification-message {
            color: var(--text-main);
            line-height: 1.6;
            margin-bottom: 12px;
        }

        .notification-actions {
            display: flex;
            gap: 10px;
            margin-top: 15px;
        }

        .btn {
            padding: 8px 16px;
            border: none;
            border-radius: 8px;
            font-size: 0.85rem;
            font-weight: 500;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            transition: all 0.2s ease;
            text-decoration: none;
        }

        .btn-primary {
            background: var(--primary);
            color: white;
        }

        .btn-primary:hover {
            background: var(--primary-hover);
            transform: translateY(-2px);
        }

        .btn-secondary {
            background: #6b7280;
            color: white;
        }

        .btn-secondary:hover {
            background: #4b5563;
            transform: translateY(-2px);
        }

        .empty-state {
            text-align: center;
            padding: 60px;
            color: var(--text-muted);
        }

        .empty-state i {
            font-size: 3rem;
            margin-bottom: 15px;
            display: block;
            opacity: 0.5;
        }

        .loading {
            text-align: center;
            padding: 40px;
            color: var(--text-muted);
        }

        .loading i {
            font-size: 2rem;
            margin-bottom: 15px;
            display: block;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }
    </style>
</head>
<body>
    <?php include 'sidebar_engineer.php'; ?>

    <div class="main-content">
        <header class="module-header">
            <h1><i class="fas fa-bell"></i> Notifications</h1>
            <p>View and manage your notifications</p>
            <hr class="header-divider">
        </header>

        <div class="notifications-container">
            <div class="section-header">
                <h2 class="section-title">
                    <i class="fas fa-list"></i>
                    Recent Notifications
                </h2>
                <button class="btn btn-primary" onclick="markAllAsRead()">
                    <i class="fas fa-check-double"></i>
                    Mark All as Read
                </button>
            </div>

            <div id="notificationsList">
                <div class="loading">
                    <i class="fas fa-spinner"></i>
                    <p>Loading notifications...</p>
                </div>
            </div>
        </div>
    </div>

    <script>
        let notificationsData = [];

        // Load notifications when page loads
        document.addEventListener('DOMContentLoaded', function() {
            loadNotifications();
        });

        // Load notifications from API
        async function loadNotifications() {
            try {
                const response = await fetch('../api/get_engineer_notifications.php');
                const result = await response.json();
                
                if (result.success) {
                    notificationsData = result.notifications || [];
                    renderNotifications(notificationsData);
                } else {
                    throw new Error(result.message || 'Failed to load notifications');
                }
            } catch (error) {
                console.error('Error loading notifications:', error);
                showErrorState();
            }
        }

        // Render notifications
        function renderNotifications(notifications) {
            const container = document.getElementById('notificationsList');
            
            if (notifications.length === 0) {
                container.innerHTML = `
                    <div class="empty-state">
                        <i class="fas fa-bell-slash"></i>
                        <h3>No notifications</h3>
                        <p>You don't have any notifications at the moment.</p>
                    </div>
                `;
                return;
            }

            container.innerHTML = notifications.map(notification => `
                <div class="notification-item ${notification.read_status ? '' : 'unread'}" onclick="handleNotificationClick('${notification.id}', '${notification.data}')">
                    <div class="notification-header">
                        <div class="notification-title">
                            <i class="fas fa-${getNotificationIcon(notification.type)}"></i>
                            ${notification.title}
                        </div>
                        <div class="notification-meta">
                            <span><i class="fas fa-clock"></i> ${formatDate(notification.created_at)}</span>
                            ${notification.read_status ? '<span><i class="fas fa-check-circle"></i> Read</span>' : '<span><i class="fas fa-circle"></i> Unread</span>'}
                        </div>
                    </div>
                    <div class="notification-message">
                        ${notification.message}
                    </div>
                    ${!notification.read_status ? `
                    <div class="notification-actions">
                        <button class="btn btn-primary" onclick="markAsRead('${notification.id}')">
                            <i class="fas fa-check"></i>
                            Mark as Read
                        </button>
                    </div>
                    ` : ''}
                </div>
            `).join('');
        }

        // Get notification icon based on type
        function getNotificationIcon(type) {
            const icons = {
                'inspection_report': 'file-alt',
                'lgu_inspection': 'building',
                'repair_update': 'tools',
                'system': 'info-circle'
            };
            return icons[type] || 'bell';
        }

        // Format date
        function formatDate(dateString) {
            const date = new Date(dateString);
            const now = new Date();
            const diffMs = now - date;
            const diffMins = Math.floor(diffMs / 60000);
            const diffHours = Math.floor(diffMs / 3600000);
            const diffDays = Math.floor(diffMs / 86400000);

            if (diffMins < 60) {
                return `${diffMins} minutes ago`;
            } else if (diffHours < 24) {
                return `${diffHours} hours ago`;
            } else if (diffDays < 7) {
                return `${diffDays} days ago`;
            } else {
                return date.toLocaleDateString();
            }
        }

        // Handle notification click
        function handleNotificationClick(notificationId, data) {
            try {
                const notificationData = JSON.parse(data);
                
                if (notificationData.inspection_id) {
                    // Open inspection modal
                    window.parent.postMessage({
                        action: 'openInspection',
                        inspectionId: notificationData.inspection_id
                    }, '*');
                } else if (notificationData.task_id) {
                    // Open repair task modal
                    window.parent.postMessage({
                        action: 'openRepairTask',
                        taskId: notificationData.task_id
                    }, '*');
                }
            } catch (error) {
                console.error('Error handling notification click:', error);
            }
        }

        // Mark notification as read
        async function markAsRead(notificationId) {
            try {
                const response = await fetch('../api/mark_engineer_notification_read.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        notification_id: notificationId
                    })
                });

                const result = await response.json();
                
                if (result.success) {
                    // Update UI
                    const notification = notificationsData.find(n => n.id == notificationId);
                    if (notification) {
                        notification.read_status = 1;
                        renderNotifications(notificationsData);
                    }
                } else {
                    throw new Error(result.message || 'Failed to mark as read');
                }
            } catch (error) {
                console.error('Error marking notification as read:', error);
            }
        }

        // Mark all notifications as read
        async function markAllAsRead() {
            try {
                const response = await fetch('../api/mark_engineer_notification_read.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        action: 'mark_all_read'
                    })
                });

                const result = await response.json();
                
                if (result.success) {
                    // Update UI
                    notificationsData.forEach(notification => {
                        notification.read_status = 1;
                    });
                    renderNotifications(notificationsData);
                } else {
                    throw new Error(result.message || 'Failed to mark all as read');
                }
            } catch (error) {
                console.error('Error marking all as read:', error);
            }
        }

        // Show error state
        function showErrorState() {
            const container = document.getElementById('notificationsList');
            container.innerHTML = `
                <div class="empty-state">
                    <i class="fas fa-exclamation-triangle"></i>
                    <h3>Error loading notifications</h3>
                    <p>Please try refreshing the page.</p>
                    <button class="btn btn-primary" onclick="loadNotifications()">
                        <i class="fas fa-redo"></i>
                        Retry
                    </button>
                </div>
            `;
        }

        // Listen for messages from parent window
        window.addEventListener('message', function(event) {
            if (event.data.action === 'refreshNotifications') {
                loadNotifications();
            }
        });
    </script>
</body>
</html>
