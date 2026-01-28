<?php
// Citizen Dashboard Main Page
session_start();

$basePath = '';
$loginUrl = 'login.php';

if (isset($_SERVER['SCRIPT_NAME']) && basename($_SERVER['SCRIPT_NAME']) === 'index.php') {
    $basePath = 'lgu-portal/public/';
    $loginUrl = 'index.php';
    $employeeUrl = 'lgu-portal/public/employee.php';
}

require_once '../config/auth.php';
$auth->requireAnyRole(['citizen', 'admin']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Citizen Dashboard | LGU Portal</title>
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

        /* Main Content */
        .main-content {
            position: relative;
            margin-left: 250px;
            height: 100vh;
            padding: 40px 60px;
            display: flex;
            flex-direction: column;
            overflow-y: auto;
            z-index: 1;
        }

        /* Header */
        .dashboard-header {
            color: white;
            margin-bottom: 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .dashboard-header-left h1 {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 10px;
        }

        .dashboard-header-left p {
            font-size: 1.1rem;
            opacity: 0.9;
        }

        .dashboard-header-right {
            display: flex;
            align-items: center;
            gap: 20px;
            position: absolute;
            right: 40px;
            top: 30px;
        }

        .notification-top-right {
            position: relative;
        }

        .notification-btn-top {
            background: rgba(255, 255, 255, 0.2);
            border: 1px solid rgba(255, 255, 255, 0.3);
            color: white;
            padding: 12px;
            border-radius: 12px;
            cursor: pointer;
            font-size: 18px;
            transition: all 0.3s;
            backdrop-filter: blur(10px);
        }

        .notification-btn-top:hover {
            background: rgba(255, 255, 255, 0.3);
            transform: translateY(-2px);
        }

        .notification-badge-top {
            position: absolute;
            top: -5px;
            right: -5px;
            background: #ef4444;
            color: white;
            border-radius: 50%;
            width: 20px;
            height: 20px;
            font-size: 11px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            border: 2px solid white;
        }

        .header-divider {
            border: none;
            height: 1px;
            background: rgba(255, 255, 255, 0.3);
            margin: 20px 0;
        }

        /* Welcome Section */
        .welcome-card {
            background: var(--glass-bg);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            border: 1px solid var(--glass-border);
            padding: 30px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
            margin-bottom: 30px;
        }

        .welcome-card h2 {
            color: #1e40af;
            font-size: 1.8rem;
            font-weight: 700;
            margin-bottom: 15px;
        }

        .welcome-card p {
            color: var(--text-muted);
            font-size: 1rem;
            line-height: 1.6;
        }

        /* Quick Access Grid */
        .quick-access-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 25px;
            margin-bottom: 30px;
        }

        .access-card {
            background: var(--glass-bg);
            backdrop-filter: blur(10px);
            border-radius: 16px;
            border: 1px solid var(--glass-border);
            padding: 25px;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            text-decoration: none;
            color: inherit;
        }

        .access-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 12px 35px rgba(0, 0, 0, 0.25);
        }

        .access-card .icon {
            width: 60px;
            height: 60px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 20px;
        }

        .access-card .icon i {
            font-size: 1.5rem;
            color: white;
        }

        .access-card h3 {
            color: #1e40af;
            font-size: 1.3rem;
            font-weight: 600;
            margin-bottom: 10px;
        }

        .access-card p {
            color: var(--text-muted);
            font-size: 0.95rem;
            line-height: 1.5;
        }

        /* Stats Overview */
        .stats-container {
            background: var(--glass-bg);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            border: 1px solid var(--glass-border);
            padding: 30px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
            margin-bottom: 30px;
        }

        .stats-container h2 {
            color: #1e40af;
            font-size: 1.5rem;
            font-weight: 700;
            margin-bottom: 25px;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
        }

        .stat-item {
            text-align: center;
            padding: 20px;
            background: #f8fafc;
            border-radius: 12px;
            border: 1px solid #e2e8f0;
        }

        .stat-item .number {
            font-size: 2rem;
            font-weight: 700;
            color: #1e40af;
            margin-bottom: 8px;
        }

        .stat-item .label {
            font-size: 0.9rem;
            color: var(--text-muted);
            font-weight: 500;
        }

        /* Recent Updates */
        .updates-container {
            background: var(--glass-bg);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            border: 1px solid var(--glass-border);
            padding: 30px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
        }

        .updates-container h2 {
            color: #1e40af;
            font-size: 1.5rem;
            font-weight: 700;
            margin-bottom: 25px;
        }

        .update-item {
            padding: 15px 0;
            border-bottom: 1px solid rgba(0,0,0,0.05);
        }

        .update-item:last-child {
            border-bottom: none;
        }

        .update-item h4 {
            color: #1e40af;
            font-size: 1.1rem;
            font-weight: 600;
            margin-bottom: 5px;
        }

        .update-item p {
            color: var(--text-muted);
            font-size: 0.9rem;
            margin-bottom: 5px;
        }

        .update-item .date {
            color: #94a3b8;
            font-size: 0.8rem;
        }

        /* Scrollbar Styling */
        .main-content::-webkit-scrollbar {
            width: 8px;
        }

        .main-content::-webkit-scrollbar-track {
            background: rgba(0, 0, 0, 0.05);
        }

        .main-content::-webkit-scrollbar-thumb {
            background: rgba(37, 99, 235, 0.5);
            border-radius: 4px;
        }

        .main-content::-webkit-scrollbar-thumb:hover {
            background: var(--primary);
        }
    </style>
</head>
<body>
    <?php 
    // Add user info data for JavaScript
    $userInfo = [
        'id' => $_SESSION['user_id'] ?? 0,
        'first_name' => $_SESSION['first_name'] ?? '',
        'last_name' => $_SESSION['last_name'] ?? '',
        'email' => $_SESSION['email'] ?? '',
        'role' => $_SESSION['role'] ?? ''
    ];
    ?>
    <div data-user-info='<?php echo json_encode($userInfo); ?>' style="display: none;"></div>
    
    <?php include '../sidebar/sidebar_citizen.php'; ?>

    <main class="main-content">
        <header class="dashboard-header">
            <div class="dashboard-header-left">
                <h1>Citizen Dashboard</h1>
                <p>Welcome to the LGU Road and Infrastructure Citizen Portal</p>
            </div>
            <div class="dashboard-header-right">
                <div class="notification-top-right">
                    <button class="notification-btn-top" onclick="window.location.href='notifications.php'">
                        <i class="fas fa-bell"></i>
                        <span class="notification-badge-top" id="notification-badge-top" style="display: none;">0</span>
                    </button>
                </div>
            </div>
            <hr class="header-divider">
        </header>

        <!-- Welcome Section -->
        <div class="welcome-card">
            <h2>Welcome to Your Community Portal</h2>
            <p>Stay informed about road conditions, infrastructure projects, and transparency reports in your area. Access real-time information about public works and participate in community development.</p>
        </div>

        <!-- Quick Access Grid -->
        <div class="quick-access-grid">
            <a href="report_damage.php" class="access-card">
                <div class="icon" style="background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);">
                    <i class="fas fa-exclamation-triangle"></i>
                </div>
                <h3>Report Road Damage</h3>
                <p>Spotted a pothole or road issue? Report it here with photos and location details to help us fix it faster.</p>
            </a>

            <a href="public_transparency_view.php" class="access-card">
                <div class="icon">
                    <i class="fas fa-chart-line"></i>
                </div>
                <h3>Public Transparency</h3>
                <p>View detailed information about road maintenance, project costs, and infrastructure development in your area.</p>
            </a>

            <a href="gis_mapping_view.php" class="access-card">
                <div class="icon">
                    <i class="fas fa-map-marked-alt"></i>
                </div>
                <h3>GIS Mapping</h3>
                <p>Explore interactive maps showing road conditions, construction zones, and infrastructure projects.</p>
            </a>
        </div>

        <!-- Stats Overview -->
        <div class="stats-container">
            <h2>Infrastructure Overview</h2>
            <div class="stats-grid">
                <div class="stat-item">
                    <div class="number">24</div>
                    <div class="label">Active Projects</div>
                </div>
                <div class="stat-item">
                    <div class="number">156</div>
                    <div class="label">Roads Maintained</div>
                </div>
                <div class="stat-item">
                    <div class="number">89%</div>
                    <div class="label">Completion Rate</div>
                </div>
                <div class="stat-item">
                    <div class="number">12</div>
                    <div class="label">New Projects</div>
                </div>
            </div>
        </div>

        <!-- Recent Updates -->
        <div class="updates-container">
            <h2>Recent Updates</h2>
            <div class="update-item">
                <h4>Main Street Resurfacing Completed</h4>
                <p>The resurfacing project on Main Street has been successfully completed ahead of schedule.</p>
                <div class="date">Updated 2 days ago</div>
            </div>
            <div class="update-item">
                <h4>New Traffic Signals Installed</h4>
                <p>Modern traffic signals have been installed at 5 major intersections to improve traffic flow.</p>
                <div class="date">Updated 1 week ago</div>
            </div>
            <div class="update-item">
                <h4>Bridge Repair Project Started</h4>
                <p>Maintenance work on the old bridge has begun. Expected completion in 3 months.</p>
                <div class="date">Updated 2 weeks ago</div>
            </div>
        </div>
    </main>

    <!-- Include notifications system -->
    <script>
        // Remove any existing notification dropdown elements
        document.addEventListener('DOMContentLoaded', function() {
            // Remove any notification container that might exist
            const notificationContainer = document.getElementById('notification-container');
            if (notificationContainer) {
                notificationContainer.remove();
            }
            
            // Remove any notification dropdowns
            const dropdowns = document.querySelectorAll('.notification-dropdown');
            dropdowns.forEach(dropdown => dropdown.remove());
            
            // Get current user info
            const userInfoEl = document.querySelector('[data-user-info]');
            if (userInfoEl) {
                try {
                    const user = JSON.parse(userInfoEl.dataset.userInfo);
                    if (user.role === 'citizen') {
                        // Load notification count
                        loadNotificationCount(user.id);
                        
                        // Update count every 30 seconds
                        setInterval(() => loadNotificationCount(user.id), 30000);
                    }
                } catch (e) {
                    console.error('Error parsing user info:', e);
                }
            }
        });

        function loadNotificationCount(userId) {
            fetch(`../api/notifications.php?action=get_count&user_id=${userId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const badge = document.getElementById('notification-badge-top');
                        if (badge) {
                            if (data.count > 0) {
                                badge.textContent = data.count > 99 ? '99+' : data.count;
                                badge.style.display = 'flex';
                            } else {
                                badge.style.display = 'none';
                            }
                        }
                    }
                })
                .catch(error => {
                    console.error('Error loading notification count:', error);
                });
        }
    </script>
</body>
</html>
