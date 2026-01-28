/**
 * Notification System for LGU Road and Infrastructure Department
 * Handles real-time notification display and management
 */

class NotificationManager {
    constructor() {
        this.userId = null;
        this.notifications = [];
        this.unreadCount = 0;
        this.pollingInterval = null;
        this.init();
    }

    init() {
        // Get current user ID from page data or session
        const userInfo = document.querySelector('[data-user-info]');
        if (userInfo) {
            try {
                const user = JSON.parse(userInfo.dataset.userInfo);
                this.userId = user.id;
            } catch (e) {
                console.error('Error parsing user info:', e);
                return;
            }
        }

        if (this.userId) {
            this.createNotificationUI();
            this.startPolling();
            this.loadNotifications();
        }
    }

    createNotificationUI() {
        // Create notification container
        const notificationContainer = document.createElement('div');
        notificationContainer.id = 'notification-container';
        notificationContainer.innerHTML = `
            <div class="notification-dropdown">
                <button class="notification-btn" onclick="notificationManager.toggleDropdown()">
                    <i class="fas fa-bell"></i>
                    <span class="notification-badge" id="notification-badge">0</span>
                </button>
                <div class="notification-dropdown-content" id="notification-dropdown">
                    <div class="notification-header">
                        <h4>Notifications</h4>
                        <button class="mark-all-read-btn" onclick="notificationManager.markAllAsRead()">Mark all as read</button>
                    </div>
                    <div class="notification-list" id="notification-list">
                        <div class="notification-loading">Loading notifications...</div>
                    </div>
                    <div class="notification-footer">
                        <a href="#" onclick="notificationManager.viewAllNotifications()">View all notifications</a>
                    </div>
                </div>
            </div>
        `;

        // Add styles
        const styles = `
            <style>
                .notification-dropdown {
                    position: relative;
                    display: inline-block;
                }
                
                .notification-btn {
                    background: none;
                    border: none;
                    color: white;
                    font-size: 18px;
                    cursor: pointer;
                    position: relative;
                    padding: 8px;
                    border-radius: 4px;
                    transition: background-color 0.3s;
                }
                
                .notification-btn:hover {
                    background-color: rgba(255, 255, 255, 0.1);
                }
                
                .notification-badge {
                    position: absolute;
                    top: 0;
                    right: 0;
                    background: #ef4444;
                    color: white;
                    border-radius: 50%;
                    width: 18px;
                    height: 18px;
                    font-size: 11px;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    font-weight: bold;
                }
                
                .notification-dropdown-content {
                    display: none;
                    position: absolute;
                    right: 0;
                    top: 100%;
                    background: white;
                    min-width: 350px;
                    max-width: 400px;
                    box-shadow: 0 8px 16px rgba(0,0,0,0.2);
                    border-radius: 8px;
                    z-index: 1000;
                    max-height: 400px;
                    overflow: hidden;
                }
                
                .notification-dropdown-content.show {
                    display: block;
                }
                
                .notification-header {
                    padding: 15px;
                    border-bottom: 1px solid #e5e7eb;
                    display: flex;
                    justify-content: space-between;
                    align-items: center;
                    background: #f9fafb;
                    border-radius: 8px 8px 0 0;
                }
                
                .notification-header h4 {
                    margin: 0;
                    color: #1f2937;
                    font-size: 16px;
                }
                
                .mark-all-read-btn {
                    background: none;
                    border: none;
                    color: #3b82f6;
                    cursor: pointer;
                    font-size: 12px;
                    text-decoration: underline;
                }
                
                .mark-all-read-btn:hover {
                    color: #2563eb;
                }
                
                .notification-list {
                    max-height: 300px;
                    overflow-y: auto;
                }
                
                .notification-item {
                    padding: 15px;
                    border-bottom: 1px solid #f3f4f6;
                    cursor: pointer;
                    transition: background-color 0.2s;
                }
                
                .notification-item:hover {
                    background-color: #f9fafb;
                }
                
                .notification-item.unread {
                    background-color: #eff6ff;
                    border-left: 3px solid #3b82f6;
                }
                
                .notification-title {
                    font-weight: 600;
                    color: #1f2937;
                    margin-bottom: 5px;
                    font-size: 14px;
                }
                
                .notification-message {
                    color: #6b7280;
                    font-size: 13px;
                    margin-bottom: 5px;
                }
                
                .notification-time {
                    color: #9ca3af;
                    font-size: 11px;
                }
                
                .notification-type-permission_granted {
                    border-left-color: #10b981;
                }
                
                .notification-type-permission_granted .notification-title {
                    color: #059669;
                }
                
                .notification-footer {
                    padding: 10px 15px;
                    text-align: center;
                    border-top: 1px solid #e5e7eb;
                    background: #f9fafb;
                }
                
                .notification-footer a {
                    color: #3b82f6;
                    text-decoration: none;
                    font-size: 13px;
                }
                
                .notification-footer a:hover {
                    text-decoration: underline;
                }
                
                .notification-loading {
                    padding: 20px;
                    text-align: center;
                    color: #6b7280;
                }
                
                .notification-empty {
                    padding: 20px;
                    text-align: center;
                    color: #6b7280;
                }
            </style>
        `;

        document.head.insertAdjacentHTML('beforeend', styles);
        
        // Add to page (preferably in header/navbar area)
        const targetLocation = document.querySelector('.sidebar-header, .main-header, header');
        if (targetLocation) {
            targetLocation.appendChild(notificationContainer);
        } else {
            document.body.appendChild(notificationContainer);
        }
    }

    toggleDropdown() {
        const dropdown = document.getElementById('notification-dropdown');
        dropdown.classList.toggle('show');
        
        if (dropdown.classList.contains('show')) {
            this.loadNotifications();
        }
    }

    startPolling() {
        // Poll for new notifications every 30 seconds
        this.pollingInterval = setInterval(() => {
            this.loadNotifications(true);
        }, 30000);
    }

    async loadNotifications(silent = false) {
        if (!this.userId) return;

        try {
            const response = await fetch(`../api/notifications.php?action=get_unread&user_id=${this.userId}`);
            const data = await response.json();
            
            if (data.success) {
                this.notifications = data.notifications;
                this.unreadCount = data.unread_count;
                this.updateUI();
                
                if (!silent && data.notifications.length > 0) {
                    this.showNewNotificationAlert(data.notifications[0]);
                }
            }
        } catch (error) {
            console.error('Error loading notifications:', error);
            if (!silent) {
                this.showError();
            }
        }
    }

    updateUI() {
        // Update badge
        const badge = document.getElementById('notification-badge');
        if (badge) {
            badge.textContent = this.unreadCount;
            badge.style.display = this.unreadCount > 0 ? 'flex' : 'none';
        }

        // Update notification list
        const list = document.getElementById('notification-list');
        if (list) {
            if (this.notifications.length === 0) {
                list.innerHTML = '<div class="notification-empty">No new notifications</div>';
            } else {
                list.innerHTML = this.notifications.map(notification => `
                    <div class="notification-item unread notification-type-${notification.type}" 
                         onclick="notificationManager.markAsRead(${notification.id})">
                        <div class="notification-title">${notification.title}</div>
                        <div class="notification-message">${notification.message}</div>
                        <div class="notification-time">${this.formatTime(notification.created_at)}</div>
                    </div>
                `).join('');
            }
        }
    }

    async markAsRead(notificationId) {
        try {
            const response = await fetch('../api/notifications.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `action=mark_read&notification_id=${notificationId}&user_id=${this.userId}`
            });
            
            const data = await response.json();
            if (data.success) {
                this.loadNotifications();
            }
        } catch (error) {
            console.error('Error marking notification as read:', error);
        }
    }

    async markAllAsRead() {
        try {
            const response = await fetch('../api/notifications.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `action=mark_all_read&user_id=${this.userId}`
            });
            
            const data = await response.json();
            if (data.success) {
                this.loadNotifications();
            }
        } catch (error) {
            console.error('Error marking all notifications as read:', error);
        }
    }

    showNewNotificationAlert(notification) {
        // Show a toast notification for new notifications
        if (window.showNotification) {
            window.showNotification(notification.message, 'info', 8000);
        }
    }

    showError() {
        const list = document.getElementById('notification-list');
        if (list) {
            list.innerHTML = '<div class="notification-empty">Error loading notifications</div>';
        }
    }

    formatTime(timestamp) {
        const date = new Date(timestamp);
        const now = new Date();
        const diff = Math.floor((now - date) / 1000); // seconds

        if (diff < 60) return 'Just now';
        if (diff < 3600) return Math.floor(diff / 60) + ' minutes ago';
        if (diff < 86400) return Math.floor(diff / 3600) + ' hours ago';
        if (diff < 604800) return Math.floor(diff / 86400) + ' days ago';
        
        return date.toLocaleDateString();
    }

    viewAllNotifications() {
        // Redirect to a full notifications page (to be implemented)
        window.location.href = '../citizen_module/notifications.php';
    }
}

// Initialize notification manager when DOM is ready
document.addEventListener('DOMContentLoaded', function() {
    window.notificationManager = new NotificationManager();
});

// Close dropdown when clicking outside
document.addEventListener('click', function(event) {
    const dropdown = document.getElementById('notification-dropdown');
    const notificationBtn = document.querySelector('.notification-btn');
    
    if (dropdown && !dropdown.contains(event.target) && !notificationBtn.contains(event.target)) {
        dropdown.classList.remove('show');
    }
});
