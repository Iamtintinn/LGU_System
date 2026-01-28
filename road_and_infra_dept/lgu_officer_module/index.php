<?php
// LGU Officer Dashboard - Based on Admin UI Structure
session_start();

$basePath = '';
$loginUrl = 'login.php';

if (isset($_SERVER['SCRIPT_NAME']) && basename($_SERVER['SCRIPT_NAME']) === 'index.php') {
    $basePath = 'lgu-portal/public/';
    $loginUrl = 'index.php';
    $employeeUrl = 'lgu-portal/public/employee.php';
}

// Include authentication and database
require_once '../config/auth.php';
require_once '../config/database.php';

// Require LGU officer role (admin also has access)
$auth->requireAnyRole(['lgu_officer', 'admin']);

// Log dashboard access
$auth->logActivity('page_access', 'Accessed LGU officer dashboard');

// Handle AJAX request for activities
if (isset($_GET['get_activities']) && $_GET['get_activities'] == '1') {
    header('Content-Type: application/json');
    
    $activities = [];
    $totalPages = 0;
    $currentPage = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $itemsPerPage = 20;
    $offset = ($currentPage - 1) * $itemsPerPage;

    try {
        $database = new Database();
        $conn = $database->getConnection();
        
        // Get total count
        $countStmt = $conn->prepare("SELECT COUNT(*) as total FROM user_activity_log ual");
        $countStmt->execute();
        $totalItems = $countStmt->get_result()->fetch_assoc()['total'];
        $totalPages = ceil($totalItems / $itemsPerPage);
        $countStmt->close();
        
        // Get paginated activities
        $stmt = $conn->prepare("
            SELECT ual.*, u.first_name, u.last_name, u.email 
            FROM user_activity_log ual 
            LEFT JOIN users u ON ual.user_id = u.id 
            ORDER BY ual.created_at DESC 
            LIMIT ? OFFSET ?
        ");
        
        $stmt->bind_param("ii", $itemsPerPage, $offset);
        $stmt->execute();
        $result = $stmt->get_result();
        
        while ($activity = $result->fetch_assoc()) {
            $timeAgo = getTimeAgo($activity['created_at']);
            $userName = !empty($activity['first_name']) ? 
                $activity['first_name'] . ' ' . $activity['last_name'] : 
                'Unknown User';
                
            $activities[] = [
                'id' => $activity['id'],
                'user' => $userName,
                'email' => $activity['email'] ?? 'N/A',
                'action' => getActivityDescription($activity['activity_type'], $activity['activity_description']),
                'time' => $timeAgo,
                'type' => $activity['activity_type'],
                'created_at' => $activity['created_at'],
                'ip_address' => $activity['ip_address'] ?? 'N/A'
            ];
        }
        
        $stmt->close();
        
        echo json_encode([
            'success' => true,
            'activities' => $activities,
            'currentPage' => $currentPage,
            'totalPages' => $totalPages,
            'totalItems' => $totalItems
        ]);
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage()
        ]);
    }
    exit;
}

// Get LGU officer statistics from database
$stats = [];
try {
    $database = new Database();
    $conn = $database->getConnection();
    
    // Get pending reports count
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM damage_reports WHERE status IN ('pending', 'in_progress')");
    $stmt->execute();
    $stats['pending_reports'] = $stmt->get_result()->fetch_assoc()['count'];
    
    // Get approved reports count
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM damage_reports WHERE status IN ('resolved', 'closed')");
    $stmt->execute();
    $stats['approved_reports'] = $stmt->get_result()->fetch_assoc()['count'];
    
    // Get inspection scheduled count
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM inspection_reports WHERE inspection_status = 'scheduled'");
    $stmt->execute();
    $stats['inspection_scheduled'] = $stmt->get_result()->fetch_assoc()['count'];
    
    // Get active projects count
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM maintenance_schedule WHERE status IN ('scheduled', 'in_progress')");
    $stmt->execute();
    $stats['active_projects'] = $stmt->get_result()->fetch_assoc()['count'];
    
    // Get publication statistics
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM public_publications WHERE is_published = 1 AND archived = 0");
    $stmt->execute();
    $stats['published_reports'] = $stmt->get_result()->fetch_assoc()['count'];
    
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM damage_reports dr LEFT JOIN public_publications pp ON dr.id = pp.damage_report_id WHERE dr.status IN ('resolved', 'closed') AND pp.id IS NULL");
    $stmt->execute();
    $stats['pending_publication'] = $stmt->get_result()->fetch_assoc()['count'];
    
} catch (Exception $e) {
    // Fallback to default stats if database fails
    $stats = [
        'pending_reports' => 12,
        'approved_reports' => 45,
        'inspection_scheduled' => 8,
        'active_projects' => 23,
        'published_reports' => 15,
        'pending_publication' => 5
    ];
}

// Get recent activity from database
$recentActivity = [];
try {
    $database = new Database();
    $conn = $database->getConnection();
    
    // Check if connection is successful
    if ($conn->connect_error) {
        throw new Exception("Database connection failed: " . $conn->connect_error);
    }
    
    $stmt = $conn->prepare("
        SELECT ual.*, u.first_name, u.last_name, u.email 
        FROM user_activity_log ual 
        LEFT JOIN users u ON ual.user_id = u.id 
        ORDER BY ual.created_at DESC 
        LIMIT 5
    ");
    
    if (!$stmt) {
        throw new Exception("Failed to prepare statement: " . $conn->error);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        while ($activity = $result->fetch_assoc()) {
            $timeAgo = getTimeAgo($activity['created_at']);
            $userName = !empty($activity['first_name']) ? 
                $activity['first_name'] . ' ' . $activity['last_name'] : 
                'Unknown User';
                
            $recentActivity[] = [
                'user' => $userName,
                'action' => getActivityDescription($activity['activity_type'], $activity['activity_description']),
                'time' => $timeAgo,
                'type' => $activity['activity_type']
            ];
        }
    } else {
        // No activities found, add a default message
        $recentActivity[] = [
            'user' => 'System',
            'action' => 'No recent activities found',
            'time' => 'Just now',
            'type' => 'info'
        ];
    }
    
    $stmt->close();
} catch (Exception $e) {
    error_log("Error fetching recent activity: " . $e->getMessage());
    // Fallback to default activities if database fails
    $recentActivity = [
        ['user' => 'System', 'action' => 'Unable to load activities: ' . $e->getMessage(), 'time' => 'Just now', 'type' => 'error']
    ];
}

// Helper function to format time ago
function getTimeAgo($datetime) {
    $time = strtotime($datetime);
    $now = time();
    $diff = $now - $time;
    
    if ($diff < 60) return 'Just now';
    if ($diff < 3600) return floor($diff / 60) . ' mins ago';
    if ($diff < 86400) return floor($diff / 3600) . ' hours ago';
    return date('M j, Y', $time);
}

// Helper function to get activity description
function getActivityDescription($type, $description) {
    switch ($type) {
        case 'user_approval':
            return 'Approved user account';
        case 'user_rejection':
            return 'Rejected user account';
        case 'role_update':
            return 'Updated user role';
        case 'status_update':
            return 'Updated user status';
        case 'user_deletion':
            return 'Deleted user account';
        case 'page_access':
            return 'Accessed ' . ($description ?: 'system');
        case 'report_submission':
            return 'Submitted damage report';
        case 'report_approval':
            return 'Approved damage report';
        case 'report_rejection':
            return 'Rejected damage report';
        case 'inspection_scheduled':
            return 'Scheduled inspection';
        case 'inspection_completed':
            return 'Completed inspection';
        default:
            return $description ?: 'Performed action';
    }
}

// Helper function to get activity icon
function getActivityIcon($type) {
    switch ($type) {
        case 'user_approval':
            return 'fa-check-circle';
        case 'user_rejection':
            return 'fa-times-circle';
        case 'role_update':
            return 'fa-user-tag';
        case 'status_update':
            return 'fa-user-edit';
        case 'user_deletion':
            return 'fa-trash';
        case 'page_access':
            return 'fa-sign-in-alt';
        case 'report_submission':
            return 'fa-file-alt';
        case 'report_approval':
            return 'fa-check-double';
        case 'report_rejection':
            return 'fa-times';
        case 'inspection_scheduled':
            return 'fa-calendar-check';
        case 'inspection_completed':
            return 'fa-clipboard-check';
        case 'info':
            return 'fa-info-circle';
        case 'error':
            return 'fa-exclamation-triangle';
        default:
            return 'fa-user';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LGU Officer Dashboard | LGU Portal</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        @import url("https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap");

        :root {
            --primary: #2563eb;
            --primary-hover: #1d4ed8;
            --success: #10b981;
            --warning: #f59e0b;
            --danger: #ef4444;
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
            background: url('../user_and_access_management_module/assets/img/cityhall.jpeg') center/cover no-repeat;
            position: relative;
            overflow: hidden;
            color: var(--text-main);
        }

        body::before {
            content: "";
            position: absolute;
            inset: 0;
            backdrop-filter: blur(2px);
            background: rgba(15, 23, 42, 0.4);
            z-index: 0;
        }

        /* Main Content */
        .main-content {
            position: relative;
            margin-left: 250px; /* Account for fixed sidebar */
            height: 100vh;
            padding: 40px 60px;
            display: flex;
            flex-direction: column;
            overflow-y: auto;
            z-index: 1;
        }


        .header {
            background: var(--glass-bg);
            backdrop-filter: blur(5px);
            border-radius: 16px;
            border: 1px solid var(--glass-border);
            padding: 24px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            margin-bottom: 2rem;
        }

        .header:hover {
            transform: translateY(-2px);
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.2);
        }

        .header-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .header h1 {
            font-size: 2rem;
            font-weight: 700;
            color: var(--text-main);
        }

        .user-info {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: var(--primary);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 600;
        }

        /* Stats Grid */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: var(--glass-bg);
            backdrop-filter: blur(5px);
            border-radius: 16px;
            border: 1px solid var(--glass-border);
            padding: 24px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .stat-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.2);
        }

        .stat-icon {
            width: 50px;
            height: 50px;
            border-radius: 0.75rem;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 1rem;
            font-size: 1.25rem;
            color: white;
        }

        .stat-icon.pending { background: var(--warning); }
        .stat-icon.approved { background: var(--success); }
        .stat-icon.inspection { background: var(--primary); }
        .stat-icon.projects { background: var(--danger); }

        .stat-value {
            font-size: 2rem;
            font-weight: 700;
            color: var(--text-main);
            margin-bottom: 0.25rem;
        }

        .stat-label {
            color: var(--text-muted);
            font-size: 0.875rem;
        }

        /* Activity Section */
        .activity-section {
            background: var(--glass-bg);
            backdrop-filter: blur(5px);
            border-radius: 16px;
            border: 1px solid var(--glass-border);
            padding: 24px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .activity-section:hover {
            transform: translateY(-2px);
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.2);
        }

        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
        }

        .section-title {
            font-size: 1.25rem;
            font-weight: 600;
            color: var(--text-main);
        }

        .activity-list {
            list-style: none;
        }

        .activity-item {
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: 1rem 0;
            border-bottom: 1px solid var(--glass-border);
        }

        .activity-item:last-child {
            border-bottom: none;
        }

        .activity-icon {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: var(--primary);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 0.875rem;
        }

        .activity-content {
            flex: 1;
        }

        .activity-user {
            font-weight: 600;
            color: var(--text-main);
        }

        .activity-action {
            color: var(--text-muted);
            font-size: 0.875rem;
        }

        .activity-time {
            color: var(--text-muted);
            font-size: 0.75rem;
        }

        /* Quick Actions */
        .quick-actions {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-top: 2rem;
        }

        .action-btn {
            background: var(--glass-bg);
            backdrop-filter: blur(5px);
            border: 1px solid var(--glass-border);
            border-radius: 16px;
            padding: 1rem;
            text-decoration: none;
            color: var(--text-main);
            text-align: center;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15);
        }

        .action-btn:hover {
            transform: translateY(-2px);
            background: var(--primary);
            color: white;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.2);
        }

        .action-btn i {
            font-size: 1.5rem;
            margin-bottom: 0.5rem;
            display: block;
        }

        .page-header {
            color: white;
            margin-bottom: 20px;
        }

        .page-header h1 {
            display: flex;
            align-items: center;
            gap: 12px;
            font-size: 1.5rem;
            font-weight: 700;
        }

        .page-header h1 i {
            font-size: 1.4rem;
            opacity: 0.9;
        }

        .divider {
            border: none;
            height: 1px;
            background: rgba(255, 255, 255, 0.3);
            margin: 1rem 0;
        }

        /* Responsive */
        @media (max-width: 768px) {
            body {
                flex-direction: column;
            }
            
            .sidebar {
                width: 100%;
                padding: 1rem;
            }
            
            .main-content {
                padding: 1rem;
            }
            
            .stats-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <?php include '../sidebar/sidebar.php'; ?>

    
    <!-- Main Content -->
    <main class="main-content">
        <header class="page-header">
            <h1>
                <i class="fas fa-tachometer-alt"></i> LGU Officer Dashboard
            </h1>
            <p style="opacity: 0.8; font-size: 0.9rem">
                Welcome back, <?php echo $auth->getUserFullName(); ?>!
            </p>
            <hr class="divider" />
        </header>

        <!-- Stats Grid -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon pending">
                    <i class="fas fa-clock"></i>
                </div>
                <div class="stat-value"><?php echo $stats['pending_reports']; ?></div>
                <div class="stat-label">Pending Reports</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon approved">
                    <i class="fas fa-check-circle"></i>
                </div>
                <div class="stat-value"><?php echo $stats['approved_reports']; ?></div>
                <div class="stat-label">Approved Reports</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon inspection">
                    <i class="fas fa-calendar-check"></i>
                </div>
                <div class="stat-value"><?php echo $stats['inspection_scheduled']; ?></div>
                <div class="stat-label">Inspections Scheduled</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon projects">
                    <i class="fas fa-project-diagram"></i>
                </div>
                <div class="stat-value"><?php echo $stats['active_projects']; ?></div>
                <div class="stat-label">Active Projects</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon published" style="background: #10b981;">
                    <i class="fas fa-newspaper"></i>
                </div>
                <div class="stat-value"><?php echo $stats['published_reports']; ?></div>
                <div class="stat-label">Published Reports</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon pending" style="background: #f59e0b;">
                    <i class="fas fa-hourglass-half"></i>
                </div>
                <div class="stat-value"><?php echo $stats['pending_publication']; ?></div>
                <div class="stat-label">Pending Publication</div>
            </div>
        </div>

        <!-- Recent Activity -->
        <div class="activity-section">
            <div class="section-header">
                <h2 class="section-title">Recent Activity</h2>
                <a href="#" onclick="openActivitiesModal()" style="color: var(--primary); text-decoration: none; font-size: 0.875rem;">
                    View All <i class="fas fa-arrow-right"></i>
                </a>
            </div>
            
            <ul class="activity-list">
                <?php foreach ($recentActivity as $activity): ?>
                <li class="activity-item">
                    <div class="activity-icon">
                        <i class="fas <?php echo getActivityIcon($activity['type'] ?? 'default'); ?>"></i>
                    </div>
                    <div class="activity-content">
                        <div class="activity-user"><?php echo htmlspecialchars($activity['user']); ?></div>
                        <div class="activity-action"><?php echo htmlspecialchars($activity['action']); ?></div>
                    </div>
                    <div class="activity-time"><?php echo htmlspecialchars($activity['time']); ?></div>
                </li>
                <?php endforeach; ?>
            </ul>
        </div>

        <!-- Quick Actions -->
        <div class="quick-actions">
            <a href="publication_management.php" class="action-btn">
                <i class="fas fa-newspaper"></i>
                <span>Manage Publications</span>
            </a>
            
            <a href="road_reporting_overview.php" class="action-btn">
                <i class="fas fa-plus-circle"></i>
                <span>Review Reports</span>
            </a>

            <a href="inspection_management.php" class="action-btn">
                <i class="fas fa-calendar-plus"></i>
                <span>Schedule Inspection</span>
            </a>
            <a href="gis_overview.php" class="action-btn">
                <i class="fas fa-map"></i>
                <span>View Maps</span>
            </a>
        </div>


    </main>

    <script>
        function openActivitiesModal() {
            // Placeholder for activities modal functionality
            alert('Activities modal would open here showing all recent system activities.');
        }
    </script>
</body>
</html>
