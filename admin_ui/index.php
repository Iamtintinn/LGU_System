<?php
// Admin UI Main Dashboard
session_start();

$basePath = '';
$loginUrl = 'login.php';

$isRoot = (strpos($_SERVER['PHP_SELF'], 'road_and_infra_dept') === false);
if ($isRoot) {
    $loginUrl = 'index.php';
}

// Include authentication and database
require_once '../config/auth.php';
require_once '../config/database.php';

// Require admin role
$auth->requireRole('admin');

// Log dashboard access
$auth->logActivity('page_access', 'Accessed admin dashboard');

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

// Get real-time statistics from database
$stats = [
    'total_users' => 0,
    'active_users' => 0,
    'pending_reports' => 0,
    'completion_rate' => '0%'
];

try {
    $database = new Database();
    $conn = $database->getConnection();
    
    // Total Users
    $res = $conn->query("SELECT COUNT(*) as count FROM users");
    $stats['total_users'] = $res->fetch_assoc()['count'];
    
    // Active/Verified Users (Users who have logged in or are verified)
    $res = $conn->query("SELECT COUNT(*) as count FROM users WHERE status = 'active'");
    $stats['active_users'] = $res->fetch_assoc()['count'];
    
    // Pending Damage Reports
    $res = $conn->query("SELECT COUNT(*) as count FROM damage_reports WHERE status = 'pending'");
    $stats['pending_reports'] = $res->fetch_assoc()['count'] ?? 0;
    
    // System Health / Completion Rate (Completed assessments/inspections)
    $res = $conn->query("SELECT 
        (SELECT COUNT(*) FROM cost_assessments WHERE status = 'completed') as completed,
        (SELECT COUNT(*) FROM cost_assessments) as total");
    $healthData = $res->fetch_assoc();
    if ($healthData['total'] > 0) {
        $stats['completion_rate'] = round(($healthData['completed'] / $healthData['total']) * 100) . '%';
    } else {
        $stats['completion_rate'] = '100%'; // Default if no data
    }

    // Role Distribution for Chart
    $roleDist = [];
    $res = $conn->query("SELECT role, COUNT(*) as count FROM users GROUP BY role");
    while($row = $res->fetch_assoc()) {
        $roleDist[$row['role']] = $row['count'];
    }

    // Registration Trends for Chart (Last 7 days)
    $regTrends = [];
    $res = $conn->query("
        SELECT DATE(created_at) as reg_date, COUNT(*) as count 
        FROM users 
        WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 6 DAY) 
        GROUP BY reg_date 
        ORDER BY reg_date ASC
    ");
    while($row = $res->fetch_assoc()) {
        $regTrends[$row['reg_date']] = $row['count'];
    }
} catch (Exception $e) {
    error_log("Stats Error: " . $e->getMessage());
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
    <title>Admin Dashboard | LGU Portal</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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

        .stat-icon.users { background: var(--primary); }
        .stat-icon.sessions { background: var(--success); }
        .stat-icon.reports { background: var(--warning); }
        .stat-icon.health { background: var(--danger); }

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

        /* Analytics Section */
        .analytics-section {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .chart-card {
            background: var(--glass-bg);
            backdrop-filter: blur(5px);
            border-radius: 16px;
            border: 1px solid var(--glass-border);
            padding: 24px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15);
            height: 350px;
            display: flex;
            flex-direction: column;
        }

        .chart-container {
            flex: 1;
            position: relative;
            min-height: 0;
        }

        .chart-title {
            font-size: 1.1rem;
            font-weight: 600;
            color: var(--text-main);
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 8px;
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

        /* Modal Styles */
        .modal {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.6);
            backdrop-filter: blur(4px);
            z-index: 9999;
            display: flex;
            justify-content: center;
            align-items: center;
            animation: fadeIn 0.3s ease;
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        .modal-content {
            background: var(--glass-bg);
            backdrop-filter: blur(10px);
            border-radius: 16px;
            border: 1px solid var(--glass-border);
            box-shadow: 0 25px 50px rgba(0, 0, 0, 0.25);
            max-width: 90vw;
            max-height: 90vh;
            width: 900px;
            display: flex;
            flex-direction: column;
            animation: slideUp 0.3s ease;
        }

        @keyframes slideUp {
            from { 
                opacity: 0;
                transform: translateY(30px);
            }
            to { 
                opacity: 1;
                transform: translateY(0);
            }
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 24px;
            border-bottom: 1px solid var(--glass-border);
        }

        .modal-header h2 {
            font-size: 1.25rem;
            font-weight: 700;
            color: var(--text-main);
            display: flex;
            align-items: center;
            gap: 10px;
            margin: 0;
        }

        .modal-header h2 i {
            color: var(--primary);
            font-size: 1.1rem;
        }

        .modal-close {
            background: transparent;
            border: none;
            color: var(--text-muted);
            font-size: 1.25rem;
            cursor: pointer;
            padding: 8px;
            border-radius: 8px;
            transition: all 0.2s;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .modal-close:hover {
            background: rgba(0, 0, 0, 0.1);
            color: var(--text-main);
        }

        .modal-body {
            flex: 1;
            padding: 24px;
            overflow: hidden;
            display: flex;
            flex-direction: column;
        }

        .activities-filters {
            display: flex;
            gap: 16px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }

        .activities-filters .filter-group {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .activities-filters label {
            font-size: 0.875rem;
            color: var(--text-muted);
            font-weight: 500;
            white-space: nowrap;
        }

        .activities-filters select {
            padding: 8px 12px;
            border-radius: 8px;
            border: 1px solid rgba(0, 0, 0, 0.1);
            background: rgba(255, 255, 255, 0.6);
            color: var(--text-main);
            cursor: pointer;
            font-size: 0.85rem;
            transition: all 0.2s;
            min-width: 150px;
        }

        .activities-filters select:focus {
            outline: none;
            border-color: var(--primary);
            background: white;
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
        }

        .btn-refresh {
            padding: 8px 16px;
            border-radius: 8px;
            border: 1px solid var(--primary);
            background: var(--primary);
            color: white;
            cursor: pointer;
            font-size: 0.85rem;
            font-weight: 500;
            transition: all 0.2s;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .btn-refresh:hover {
            background: var(--primary-hover);
            transform: translateY(-1px);
        }

        .activities-table-wrapper {
            flex: 1;
            overflow-y: auto;
            margin-bottom: 20px;
            border: 1px solid var(--glass-border);
            border-radius: 12px;
            background: rgba(255, 255, 255, 0.3);
        }

        .activities-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
        }

        .activities-table th {
            position: sticky;
            top: 0;
            background: rgba(248, 250, 252, 0.95);
            backdrop-filter: blur(3px);
            padding: 14px 16px;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
            color: var(--text-muted);
            border-bottom: 2px solid #e2e8f0;
            text-align: left;
            z-index: 10;
            letter-spacing: 0.5px;
        }

        .activities-table th i {
            margin-right: 6px;
            color: var(--primary);
            font-size: 0.7rem;
        }

        .activities-table td {
            padding: 16px;
            font-size: 0.9rem;
            border-bottom: 1px solid #f1f5f9;
        }

        .activities-table tr:hover td {
            background: rgba(248, 250, 252, 0.6);
        }

        .activity-icon {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            background: rgba(37, 99, 235, 0.1);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.875rem;
        }

        .activity-details {
            flex: 1;
        }

        .activity-user {
            font-weight: 600;
            color: var(--text-main);
            margin-bottom: 4px;
        }

        .activity-email {
            font-size: 0.8rem;
            color: var(--text-muted);
        }

        .activity-action {
            color: var(--text-main);
            margin-bottom: 4px;
        }

        .activity-meta {
            display: flex;
            align-items: center;
            gap: 16px;
            font-size: 0.8rem;
            color: var(--text-muted);
        }

        .activity-time {
            font-weight: 500;
        }

        .activity-ip {
            font-family: monospace;
        }

        .modal-pagination {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 16px 0;
            border-top: 1px solid var(--glass-border);
        }

        .pagination-info {
            color: var(--text-muted);
            font-size: 0.875rem;
        }

        .pagination-controls {
            display: flex;
            gap: 8px;
        }

        .pagination-btn {
            padding: 8px 12px;
            border-radius: 6px;
            border: 1px solid rgba(0, 0, 0, 0.1);
            background: rgba(255, 255, 255, 0.6);
            color: var(--text-main);
            text-decoration: none;
            font-size: 0.875rem;
            font-weight: 500;
            transition: all 0.2s;
            cursor: pointer;
        }

        .pagination-btn:hover:not(.disabled) {
            background: var(--primary);
            color: white;
            border-color: var(--primary);
        }

        .pagination-btn.disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }

        .pagination-btn.active {
            background: var(--primary);
            color: white;
            border-color: var(--primary);
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
    <?php include '../sidebar/admin_sidebar.php'; ?>


    <!-- Main Content -->
    <main class="main-content">
    <header class="page-header">
      <h1>
        <i class="fas fa-tachometer-alt"></i> Admin Dashboard
      </h1>
      <p style="opacity: 0.8; font-size: 0.9rem">
        Welcome back, <?php echo $auth->getUserFullName(); ?>!
      </p>
      <hr class="divider" />
    </header>

        <!-- Stats Grid -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon users">
                    <i class="fas fa-users"></i>
                </div>
                <div class="stat-value"><?php echo number_format($stats['total_users']); ?></div>
                <div class="stat-label">Total Users</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon sessions" style="background: #10b981;">
                    <i class="fas fa-user-check"></i>
                </div>
                <div class="stat-value"><?php echo number_format($stats['active_users']); ?></div>
                <div class="stat-label">Active Users</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon reports" style="background: #f59e0b;">
                    <i class="fas fa-exclamation-triangle"></i>
                </div>
                <div class="stat-value"><?php echo number_format($stats['pending_reports']); ?></div>
                <div class="stat-label">Pending Reports</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon health" style="background: #ef4444;">
                    <i class="fas fa-chart-line"></i>
                </div>
                <div class="stat-value"><?php echo $stats['completion_rate']; ?></div>
                <div class="stat-label">Completion Rate</div>
            </div>
        </div>

        <!-- Analytics Section -->
        <div class="analytics-section">
            <div class="chart-card">
                <h3 class="chart-title"><i class="fas fa-pie-chart" style="color: var(--primary);"></i> User Role Distribution</h3>
                <div class="chart-container">
                    <canvas id="roleChart"></canvas>
                </div>
            </div>
            <div class="chart-card">
                <h3 class="chart-title"><i class="fas fa-chart-area" style="color: #10b981;"></i> Registration Trends</h3>
                <div class="chart-container">
                    <canvas id="trendChart"></canvas>
                </div>
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
            <a href="users.php" class="action-btn">
                <i class="fas fa-user-plus"></i>
                <span>Add User</span>
            </a>
            <a href="permissions.php" class="action-btn">
                <i class="fas fa-user-shield"></i>
                <span>Manage Roles</span>
            </a>
            <a href="settings.php" class="action-btn">
                <i class="fas fa-tools"></i>
                <span>System Tools</span>
            </a>
        </div>
    </main>

    <!-- Activities Modal -->
    <div id="activitiesModal" class="modal" style="display: none;">
        <div class="modal-content">
            <div class="modal-header">
                <h2><i class="fas fa-history"></i> All Activities</h2>
                <button class="modal-close" onclick="closeActivitiesModal()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-body">
                <div class="activities-filters">
                    <div class="filter-group">
                        <label for="modal-filter-type">Type:</label>
                        <select id="modal-filter-type" onchange="filterModalActivities()">
                            <option value="">All Types</option>
                            <option value="user_approval">User Approvals</option>
                            <option value="user_rejection">User Rejections</option>
                            <option value="role_update">Role Updates</option>
                            <option value="page_access">Page Access</option>
                            <option value="login">Logins</option>
                            <option value="logout">Logouts</option>
                        </select>
                    </div>
                    <div class="filter-group">
                        <label for="modal-filter-user">User:</label>
                        <select id="modal-filter-user" onchange="filterModalActivities()">
                            <option value="">All Users</option>
                        </select>
                    </div>
                    <div class="filter-group">
                        <button class="btn-refresh" onclick="loadAllActivities()">
                            <i class="fas fa-sync-alt"></i> Refresh
                        </button>
                    </div>
                </div>
                <div class="activities-table-wrapper">
                    <table class="activities-table">
                        <thead>
                            <tr>
                                <th><i class="fas fa-cog"></i> Action</th>
                                <th><i class="fas fa-user"></i> User</th>
                                <th><i class="fas fa-clock"></i> Time</th>
                                <th><i class="fas fa-network-wired"></i> IP Address</th>
                            </tr>
                        </thead>
                        <tbody id="modal-activities-tbody">
                            <tr>
                                <td colspan="4" style="text-align: center; padding: 40px;">
                                    <i class="fas fa-spinner fa-spin" style="font-size: 2rem; margin-bottom: 8px; display: block;"></i>
                                    Loading activities...
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div class="modal-pagination" id="modal-pagination">
                    <!-- Pagination will be inserted here -->
                </div>
            </div>
        </div>
    </div>

    <script>
        let currentPage = 1;
        let totalPages = 1;
        let allActivities = [];

        // Open activities modal
        function openActivitiesModal() {
            document.getElementById('activitiesModal').style.display = 'flex';
            loadAllActivities();
        }

        // Close activities modal
        function closeActivitiesModal() {
            document.getElementById('activitiesModal').style.display = 'none';
        }

        // Load all activities from server
        async function loadAllActivities(page = 1) {
            const tbody = document.getElementById('modal-activities-tbody');
            const pagination = document.getElementById('modal-pagination');
            
            // Show loading state
            tbody.innerHTML = `
                <tr>
                    <td colspan="4" style="text-align: center; padding: 40px;">
                        <i class="fas fa-spinner fa-spin" style="font-size: 2rem; margin-bottom: 8px; display: block;"></i>
                        Loading activities...
                    </td>
                </tr>
            `;

            try {
                const response = await fetch(`index.php?get_activities=1&page=${page}`);
                const data = await response.json();
                
                if (data.success) {
                    allActivities = data.activities;
                    currentPage = data.currentPage;
                    totalPages = data.totalPages;
                    
                    renderActivities(data.activities);
                    renderPagination(data.currentPage, data.totalPages, data.totalItems);
                    populateUserFilter();
                } else {
                    throw new Error(data.message || 'Failed to load activities');
                }
            } catch (error) {
                console.error('Error loading activities:', error);
                tbody.innerHTML = `
                    <tr>
                        <td colspan="4" style="text-align: center; padding: 40px; color: var(--text-muted);">
                            <i class="fas fa-exclamation-triangle" style="font-size: 2rem; margin-bottom: 8px; display: block;"></i>
                            Failed to load activities
                        </td>
                    </tr>
                `;
            }
        }

        // Render activities in the table
        function renderActivities(activities) {
            const tbody = document.getElementById('modal-activities-tbody');
            
            if (activities.length === 0) {
                tbody.innerHTML = `
                    <tr>
                        <td colspan="4" style="text-align: center; padding: 40px; color: var(--text-muted);">
                            <i class="fas fa-inbox" style="font-size: 2rem; margin-bottom: 8px; display: block;"></i>
                            No activities found
                        </td>
                    </tr>
                `;
                return;
            }

            tbody.innerHTML = activities.map(activity => `
                <tr data-type="${activity.type}" data-user="${activity.user}">
                    <td>
                        <div style="display: flex; align-items: center; gap: 12px;">
                            <div class="activity-icon">
                                <i class="fas ${getActivityIcon(activity.type)}"></i>
                            </div>
                            <div class="activity-details">
                                <div class="activity-action">${activity.action}</div>
                                <div class="activity-meta">
                                    <span class="activity-time">${activity.time}</span>
                                    <span>ID: ${activity.id}</span>
                                </div>
                            </div>
                        </div>
                    </td>
                    <td>
                        <div class="activity-user">${activity.user}</div>
                        <div class="activity-email">${activity.email}</div>
                    </td>
                    <td>
                        <div class="activity-time">${activity.time}</div>
                        <div style="font-size: 0.75rem; color: var(--text-muted);">
                            ${new Date(activity.created_at).toLocaleString()}
                        </div>
                    </td>
                    <td>
                        <span class="activity-ip">${activity.ip_address}</span>
                    </td>
                </tr>
            `).join('');
        }

        // Render pagination controls
        function renderPagination(currentPage, totalPages, totalItems) {
            const pagination = document.getElementById('modal-pagination');
            
            if (totalPages <= 1) {
                pagination.innerHTML = '';
                return;
            }

            const startItem = (currentPage - 1) * 20 + 1;
            const endItem = Math.min(currentPage * 20, totalItems);

            let paginationHTML = `
                <div class="pagination-info">
                    Showing ${startItem} to ${endItem} of ${totalItems} activities
                </div>
                <div class="pagination-controls">
            `;

            // Previous button
            if (currentPage > 1) {
                paginationHTML += `
                    <button class="pagination-btn" onclick="loadAllActivities(${currentPage - 1})">
                        <i class="fas fa-chevron-left"></i> Previous
                    </button>
                `;
            }

            // Page numbers
            const startPage = Math.max(1, currentPage - 2);
            const endPage = Math.min(totalPages, currentPage + 2);

            for (let page = startPage; page <= endPage; page++) {
                const activeClass = page === currentPage ? 'active' : '';
                paginationHTML += `
                    <button class="pagination-btn ${activeClass}" onclick="loadAllActivities(${page})">
                        ${page}
                    </button>
                `;
            }

            // Next button
            if (currentPage < totalPages) {
                paginationHTML += `
                    <button class="pagination-btn" onclick="loadAllActivities(${currentPage + 1})">
                        Next <i class="fas fa-chevron-right"></i>
                    </button>
                `;
            }

            paginationHTML += '</div>';
            pagination.innerHTML = paginationHTML;
        }

        // Populate user filter dropdown
        function populateUserFilter() {
            const users = new Set();
            allActivities.forEach(activity => {
                if (activity.user && activity.user !== 'Unknown User') {
                    users.add(activity.user);
                }
            });

            const select = document.getElementById('modal-filter-user');
            const currentValue = select.value;
            
            // Clear existing options except the first one
            select.innerHTML = '<option value="">All Users</option>';
            
            // Add user options
            Array.from(users).sort().forEach(user => {
                const option = document.createElement('option');
                option.value = user;
                option.textContent = user;
                if (user === currentValue) {
                    option.selected = true;
                }
                select.appendChild(option);
            });
        }

        // Filter activities
        function filterModalActivities() {
            const typeFilter = document.getElementById('modal-filter-type').value;
            const userFilter = document.getElementById('modal-filter-user').value;
            const rows = document.querySelectorAll('#modal-activities-tbody tr[data-user]');
            
            rows.forEach(row => {
                const matchesType = !typeFilter || row.dataset.type === typeFilter;
                const matchesUser = !userFilter || row.dataset.user === userFilter;
                
                row.style.display = matchesType && matchesUser ? '' : 'none';
            });
        }

        // Get activity icon (same as PHP function)
        function getActivityIcon(type) {
            switch (type) {
                case 'user_approval':
                    return 'fa-check-circle text-success';
                case 'user_rejection':
                    return 'fa-times-circle text-danger';
                case 'role_update':
                    return 'fa-user-tag text-primary';
                case 'status_update':
                    return 'fa-user-edit text-info';
                case 'user_deletion':
                    return 'fa-trash text-danger';
                case 'page_access':
                    return 'fa-sign-in-alt text-secondary';
                case 'unauthorized_access':
                    return 'fa-shield-alt text-warning';
                case 'login':
                    return 'fa-sign-in-alt text-success';
                case 'logout':
                    return 'fa-sign-out-alt text-secondary';
                default:
                    return 'fa-user text-muted';
            }
        }

        // Close modal when clicking outside
        window.onclick = function(event) {
            const modal = document.getElementById('activitiesModal');
            if (event.target === modal) {
                closeActivitiesModal();
            }
        }
        // Chart.js Implementations
        document.addEventListener('DOMContentLoaded', function() {
            // Role Distribution Chart
            const roleCtx = document.getElementById('roleChart').getContext('2d');
            const roleData = <?php echo json_encode($roleDist); ?>;
            
            const roleLabels = Object.keys(roleData).map(role => role.replace('_', ' ').toUpperCase());
            const roleValues = Object.values(roleData);
            
            new Chart(roleCtx, {
                type: 'doughnut',
                data: {
                    labels: roleLabels,
                    datasets: [{
                        data: roleValues,
                        backgroundColor: [
                            '#2563eb', // primary (admin)
                            '#10b981', // success (engineer)
                            '#f59e0b', // warning (lgu_officer)
                            '#64748b'  // muted (citizen)
                        ],
                        borderWidth: 0,
                        hoverOffset: 10
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: {
                                padding: 20,
                                usePointStyle: true,
                                font: {
                                    size: 12,
                                    family: "'Inter', sans-serif"
                                }
                            }
                        },
                        tooltip: {
                            backgroundColor: 'rgba(255, 255, 255, 0.9)',
                            titleColor: '#1e293b',
                            bodyColor: '#1e293b',
                            borderColor: '#e2e8f0',
                            borderWidth: 1,
                            padding: 12,
                            displayColors: true,
                            callbacks: {
                                label: function(context) {
                                    let label = context.label || '';
                                    if (label) label += ': ';
                                    label += context.raw;
                                    return label;
                                }
                            }
                        }
                    },
                    cutout: '70%'
                }
            });

            // Registration Trends Chart
            const trendCtx = document.getElementById('trendChart').getContext('2d');
            const trendData = <?php echo json_encode($regTrends); ?>;
            
            // Fill in missing dates for the last 7 days if any
            const labels = [];
            const values = [];
            for (let i = 6; i >= 0; i--) {
                const d = new Date();
                d.setDate(d.getDate() - i);
                const dateStr = d.toISOString().split('T')[0];
                const displayDate = d.toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
                
                labels.push(displayDate);
                values.push(trendData[dateStr] || 0);
            }

            new Chart(trendCtx, {
                type: 'line',
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'New Users',
                        data: values,
                        borderColor: '#10b981',
                        backgroundColor: 'rgba(16, 185, 129, 0.1)',
                        fill: true,
                        tension: 0.4,
                        pointRadius: 4,
                        pointBackgroundColor: '#10b981',
                        borderWidth: 3
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                precision: 0,
                                font: { size: 10 }
                            },
                            grid: {
                                color: 'rgba(0, 0, 0, 0.05)'
                            }
                        },
                        x: {
                            ticks: {
                                font: { size: 10 }
                            },
                            grid: { display: false }
                        }
                    }
                }
            });
        });
    </script>
</body>
</html>
