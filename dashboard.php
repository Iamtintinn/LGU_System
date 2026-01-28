<?php
// Main Dashboard - Central Interface for All User Roles
session_start();

// Include authentication system
require_once __DIR__ . '/user_and_access_management_module/SimpleAuth.php';

// Initialize auth
$auth = new SimpleAuth();

// Require login to access this page
$auth->requireLogin();

// Get current user info from session
$currentUser = [
    'id' => $_SESSION['user_id'],
    'first_name' => $_SESSION['first_name'],
    'last_name' => $_SESSION['last_name'],
    'role' => $_SESSION['role']
];
$userRole = $currentUser['role'];

// Log page access
$auth->logActivity('page_access', 'Accessed main dashboard');

// Get system-wide statistics (simplified for now)
$systemStats = [
    'total_reports' => 25,
    'pending_assessments' => 8,
    'active_inspections' => 12,
    'total_users' => 45
];

// Get recent reports (simplified for now)
$recentReports = [];

// Determine dashboard content based on user role
switch ($userRole) {
    case 'admin':
        $dashboardTitle = 'Administrator Dashboard';
        $dashboardSubtitle = 'System administration and monitoring';
        $showAdminPanel = true;
        break;
    case 'lgu_officer':
        $dashboardTitle = 'LGU Officer Dashboard';
        $dashboardSubtitle = 'Reports oversight and approval management';
        $showOfficerPanel = true;
        break;
    case 'engineer':
        $dashboardTitle = 'Engineer Dashboard';
        $dashboardSubtitle = 'Technical assessments and infrastructure management';
        $showEngineerPanel = true;
        break;
    case 'citizen':
        $dashboardTitle = 'Citizen Dashboard';
        $dashboardSubtitle = 'Report submission and tracking';
        $showCitizenPanel = true;
        break;
    default:
        $dashboardTitle = 'User Dashboard';
        $dashboardSubtitle = 'Welcome to LGU Portal';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($dashboardTitle); ?> - LGU Portal</title>
    <link rel="stylesheet" href="css/styles.css">
    <style>
        body {
            margin: 0;
            padding: 0;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
        }

        .main-content {
            margin-left: 250px;
            padding: 2rem;
            min-height: 100vh;
            position: relative;
            z-index: 1;
        }
        
        .header {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            border-bottom: 1px solid rgba(255, 255, 255, 0.2);
            padding: 1rem 2rem;
            margin-left: 250px;
            position: relative;
            z-index: 1;
        }

        .header-content h1 {
            color: white;
            font-size: 2rem;
            font-weight: 600;
            margin: 0 0 0.5rem 0;
        }

        .header-content p {
            color: rgba(255, 255, 255, 0.8);
            margin: 0;
            font-size: 1rem;
        }

        .dashboard-section {
            background: rgba(255, 255, 255, 0.95);
            padding: 1.5rem;
            border-radius: 12px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
            margin-bottom: 1.5rem;
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            margin-left: 250px;
            position: relative;
            z-index: 1;
        }
        
        .dashboard-section h3 {
            margin: 0 0 1.5rem 0;
            color: #1e293b;
            font-size: 1.25rem;
            font-weight: 600;
        }

        .quicklinks-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
            margin-left: 250px;
            position: relative;
            z-index: 1;
        }

        .quicklink-card {
            background: rgba(255, 255, 255, 0.95);
            padding: 1.5rem;
            border-radius: 12px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            text-decoration: none;
            color: inherit;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .quicklink-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, #667eea, #764ba2);
            transition: height 0.3s ease;
        }

        .quicklink-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 12px 40px rgba(0, 0, 0, 0.15);
        }

        .quicklink-card:hover::before {
            height: 8px;
        }

        .quicklink-icon {
            width: 48px;
            height: 48px;
            background: linear-gradient(135deg, #667eea, #764ba2);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 1rem;
            font-size: 1.5rem;
            color: white;
        }

        .quicklink-title {
            font-size: 1.125rem;
            font-weight: 600;
            color: #1e293b;
            margin-bottom: 0.5rem;
        }

        .quicklink-description {
            font-size: 0.875rem;
            color: #64748b;
            line-height: 1.5;
            margin-bottom: 1rem;
        }

        .quicklink-stats {
            display: flex;
            gap: 1rem;
            font-size: 0.75rem;
            color: #94a3b8;
        }

        .quicklink-stat {
            display: flex;
            align-items: center;
            gap: 0.25rem;
        }

        .quicklink-arrow {
            position: absolute;
            top: 1.5rem;
            right: 1.5rem;
            color: #667eea;
            font-size: 1.25rem;
            transition: transform 0.3s ease;
        }

        .quicklink-card:hover .quicklink-arrow {
            transform: translateX(5px);
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: rgba(255, 255, 255, 0.95);
            padding: 1.5rem;
            border-radius: 12px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            text-align: center;
        }

        .stat-card .number {
            font-size: 2rem;
            font-weight: 700;
            color: #667eea;
            margin-bottom: 0.5rem;
        }

        .stat-card .label {
            font-size: 0.875rem;
            color: #64748b;
            font-weight: 500;
        }

        .activity-item {
            padding: 0.75rem 0;
            border-bottom: 1px solid rgba(0, 0, 0, 0.05);
            color: #64748b;
        }

        .activity-item:last-child {
            border-bottom: none;
        }

        .activity-time {
            font-size: 0.75rem;
            color: #94a3b8;
        }

        .welcome-message {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            padding: 1rem 1.5rem;
            border-radius: 8px;
            color: white;
            margin-bottom: 1.5rem;
            margin-left: 250px;
        }

        .alert {
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1rem;
        }

        .alert-info {
            background: rgba(59, 130, 246, 0.1);
            border: 1px solid rgba(59, 130, 246, 0.3);
            color: #3b82f6;
        }

        .alert-warning {
            background: rgba(245, 158, 11, 0.1);
            border: 1px solid rgba(245, 158, 11, 0.3);
            color: #f59e0b;
        }

        /* Module specific colors */
        .quicklink-card.damage::before { background: linear-gradient(90deg, #ef4444, #dc2626); }
        .quicklink-card.damage .quicklink-icon { background: linear-gradient(135deg, #ef4444, #dc2626); }
        .quicklink-card.damage .quicklink-arrow { color: #ef4444; }

        .quicklink-card.assessment::before { background: linear-gradient(90deg, #10b981, #059669); }
        .quicklink-card.assessment .quicklink-icon { background: linear-gradient(135deg, #10b981, #059669); }
        .quicklink-card.assessment .quicklink-arrow { color: #10b981; }

        .quicklink-card.inspection::before { background: linear-gradient(90deg, #f59e0b, #d97706); }
        .quicklink-card.inspection .quicklink-icon { background: linear-gradient(135deg, #f59e0b, #d97706); }
        .quicklink-card.inspection .quicklink-arrow { color: #f59e0b; }

        .quicklink-card.gis::before { background: linear-gradient(90deg, #8b5cf6, #7c3aed); }
        .quicklink-card.gis .quicklink-icon { background: linear-gradient(135deg, #8b5cf6, #7c3aed); }
        .quicklink-card.gis .quicklink-arrow { color: #8b5cf6; }

        .quicklink-card.documents::before { background: linear-gradient(90deg, #64748b, #475569); }
        .quicklink-card.documents .quicklink-icon { background: linear-gradient(135deg, #64748b, #475569); }
        .quicklink-card.documents .quicklink-arrow { color: #64748b; }

        .quicklink-card.reports::before { background: linear-gradient(90deg, #06b6d4, #0891b2); }
        .quicklink-card.reports .quicklink-icon { background: linear-gradient(135deg, #06b6d4, #0891b2); }
        .quicklink-card.reports .quicklink-arrow { color: #06b6d4; }
    </style>
</head>
<body>
    <!-- Include sidebar -->
    <?php require_once __DIR__ . '/sidebar/sidebar.php'; ?>

    <div class="header">
        <div class="header-content">
            <h1 class="header-title"><?php echo htmlspecialchars($dashboardTitle); ?></h1>
            <p><?php echo htmlspecialchars($dashboardSubtitle); ?></p>
        </div>
    </div>

    <!-- Welcome Message -->
    <div class="welcome-message">
        Welcome back, <?php echo htmlspecialchars($currentUser['first_name'] . ' ' . $currentUser['last_name']); ?>! 
        Your role: <strong><?php echo htmlspecialchars(ucfirst($userRole)); ?></strong>
    </div>

    <!-- System Alerts -->
    <?php if ($systemStats['pending_assessments'] > 0): ?>
        <div class="alert alert-warning">
            <strong>⚠️ Pending Actions:</strong> You have <?php echo $systemStats['pending_assessments']; ?> assessments requiring attention.
        </div>
    <?php endif; ?>

    <!-- Role-Specific Quick Links -->
    <div class="quicklinks-container">
        <?php if ($showAdminPanel): ?>
            <!-- Admin Quick Links -->
            <a href="admin_ui/index.php" class="quicklink-card">
                <div class="quicklink-icon">👥</div>
                <div class="quicklink-title">User Management</div>
                <div class="quicklink-description">Manage users, roles, and permissions</div>
                <div class="quicklink-stats">
                    <div class="quicklink-stat">👤 <?php echo $systemStats['total_users']; ?> Users</div>
                </div>
                <div class="quicklink-arrow">→</div>
            </a>

            <a href="engineer_module/damage_assessment.php" class="quicklink-card damage">
                <div class="quicklink-icon">🚧</div>
                <div class="quicklink-title">Damage Reports</div>
                <div class="quicklink-description">Review all damage reports</div>
                <div class="quicklink-stats">
                    <div class="quicklink-stat">📊 <?php echo $systemStats['total_reports']; ?> Total</div>
                </div>
                <div class="quicklink-arrow">→</div>
            </a>

            <a href="gis_mapping_and_visualization_module/mapping.php" class="quicklink-card gis">
                <div class="quicklink-icon">🗺️</div>
                <div class="quicklink-title">GIS Mapping</div>
                <div class="quicklink-description">View infrastructure on maps</div>
                <div class="quicklink-stats">
                    <div class="quicklink-stat">📍 All Sites</div>
                </div>
                <div class="quicklink-arrow">→</div>
            </a>

            <a href="lgu_officer_module/document_management.php" class="quicklink-card documents">
                <div class="quicklink-icon">📄</div>
                <div class="quicklink-title">System Reports</div>
                <div class="quicklink-description">Access all system documents</div>
                <div class="quicklink-stats">
                    <div class="quicklink-stat">📁 All Files</div>
                </div>
                <div class="quicklink-arrow">→</div>
            </a>

        <?php elseif ($showOfficerPanel): ?>
            <!-- LGU Officer Quick Links -->
            <a href="engineer_module/damage_assessment.php" class="quicklink-card damage">
                <div class="quicklink-icon">🚧</div>
                <div class="quicklink-title">Damage Reports</div>
                <div class="quicklink-description">Review and approve damage reports</div>
                <div class="quicklink-stats">
                    <div class="quicklink-stat">📊 <?php echo $systemStats['total_reports']; ?> Total</div>
                    <div class="quicklink-stat">⏱️ <?php echo $systemStats['pending_assessments']; ?> Pending</div>
                </div>
                <div class="quicklink-arrow">→</div>
            </a>

            <a href="engineer_module/damage_assessment.php#cost" class="quicklink-card assessment">
                <div class="quicklink-icon">💰</div>
                <div class="quicklink-title">Cost Assessments</div>
                <div class="quicklink-description">Review cost estimates and budgets</div>
                <div class="quicklink-stats">
                    <div class="quicklink-stat">💵 Pending Reviews</div>
                </div>
                <div class="quicklink-arrow">→</div>
            </a>

            <a href="lgu_officer_module/inspection_management.php" class="quicklink-card inspection">
                <div class="quicklink-icon">🔍</div>
                <div class="quicklink-title">Inspection Workflow</div>
                <div class="quicklink-description">Monitor inspection progress</div>
                <div class="quicklink-stats">
                    <div class="quicklink-stat">📋 <?php echo $systemStats['active_inspections']; ?> Active</div>
                </div>
                <div class="quicklink-arrow">→</div>
            </a>

            <a href="gis_mapping_and_visualization_module/mapping.php" class="quicklink-card gis">
                <div class="quicklink-icon">🗺️</div>
                <div class="quicklink-title">GIS Overview</div>
                <div class="quicklink-description">View all infrastructure locations</div>
                <div class="quicklink-stats">
                    <div class="quicklink-stat">📍 All Sites</div>
                </div>
                <div class="quicklink-arrow">→</div>
            </a>

        <?php elseif ($showEngineerPanel): ?>
            <!-- Engineer Quick Links -->
            <a href="engineer_module/damage_assessment.php" class="quicklink-card damage">
                <div class="quicklink-icon">🚧</div>
                <div class="quicklink-title">Damage Assessment</div>
                <div class="quicklink-description">Review and assess road damage</div>
                <div class="quicklink-stats">
                    <div class="quicklink-stat">📊 <?php echo $stats['pending_reports']; ?> Pending</div>
                    <div class="quicklink-stat">⏱️ 3 Urgent</div>
                </div>
                <div class="quicklink-arrow">→</div>
            </a>

            <a href="engineer_module/damage_assessment.php#cost" class="quicklink-card assessment">
                <div class="quicklink-icon">💰</div>
                <div class="quicklink-title">Cost Estimation</div>
                <div class="quicklink-description">Calculate repair costs</div>
                <div class="quicklink-stats">
                    <div class="quicklink-stat">💵 ₱<?php echo number_format($stats['assessments'] * 50000); ?> Total</div>
                    <div class="quicklink-stat">📝 <?php echo $stats['assessments']; ?> Reviews</div>
                </div>
                <div class="quicklink-arrow">→</div>
            </a>

            <a href="lgu_officer_module/inspection_management.php" class="quicklink-card inspection">
                <div class="quicklink-icon">🔍</div>
                <div class="quicklink-title">Inspection Reports</div>
                <div class="quicklink-description">Schedule and conduct inspections</div>
                <div class="quicklink-stats">
                    <div class="quicklink-stat">📋 <?php echo $stats['inspections']; ?> Reports</div>
                    <div class="quicklink-stat">📅 5 Scheduled</div>
                </div>
                <div class="quicklink-arrow">→</div>
            </a>

            <a href="gis_mapping_and_visualization_module/mapping.php" class="quicklink-card gis">
                <div class="quicklink-icon">🗺️</div>
                <div class="quicklink-title">GIS Mapping</div>
                <div class="quicklink-description">View infrastructure on maps</div>
                <div class="quicklink-stats">
                    <div class="quicklink-stat">📍 45 Sites</div>
                    <div class="quicklink-stat">🔍 12 Active</div>
                </div>
                <div class="quicklink-arrow">→</div>
            </a>

            <a href="lgu_officer_module/document_management.php" class="quicklink-card documents">
                <div class="quicklink-icon">📄</div>
                <div class="quicklink-title">Technical Documents</div>
                <div class="quicklink-description">Access engineering reports</div>
                <div class="quicklink-stats">
                    <div class="quicklink-stat">📁 89 Files</div>
                    <div class="quicklink-stat">📈 6 New</div>
                </div>
                <div class="quicklink-arrow">→</div>
            </a>

        <?php elseif ($showCitizenPanel): ?>
            <!-- Citizen Quick Links -->
            <a href="citizen_module/road_damage_reporting/dashboard.php" class="quicklink-card damage">
                <div class="quicklink-icon">🚧</div>
                <div class="quicklink-title">Report Damage</div>
                <div class="quicklink-description">Report road damage issues</div>
                <div class="quicklink-stats">
                    <div class="quicklink-stat">📝 Quick Report</div>
                </div>
                <div class="quicklink-arrow">→</div>
            </a>

            <a href="gis_mapping_and_visualization_module/mapping.php" class="quicklink-card gis">
                <div class="quicklink-icon">🗺️</div>
                <div class="quicklink-title">View Reports</div>
                <div class="quicklink-description">See reported issues on map</div>
                <div class="quicklink-stats">
                    <div class="quicklink-stat">📍 Interactive Map</div>
                </div>
                <div class="quicklink-arrow">→</div>
            </a>

            <a href="#" class="quicklink-card reports">
                <div class="quicklink-icon">📊</div>
                <div class="quicklink-title">My Reports</div>
                <div class="quicklink-description">Track your submitted reports</div>
                <div class="quicklink-stats">
                    <div class="quicklink-stat">📋 View History</div>
                </div>
                <div class="quicklink-arrow">→</div>
            </a>

            <a href="#" class="quicklink-card documents">
                <div class="quicklink-icon">📄</div>
                <div class="quicklink-title">Documents</div>
                <div class="quicklink-description">Access public documents</div>
                <div class="quicklink-stats">
                    <div class="quicklink-stat">📁 Public Files</div>
                </div>
                <div class="quicklink-arrow">→</div>
            </a>
        <?php endif; ?>
    </div>

    <!-- System Statistics -->
    <div class="dashboard-section">
        <h3>System Overview</h3>
        <div class="stats-grid">
            <div class="stat-card">
                <div class="number"><?php echo $systemStats['total_reports']; ?></div>
                <div class="label">Total Reports</div>
            </div>
            <div class="stat-card">
                <div class="number"><?php echo $systemStats['pending_assessments']; ?></div>
                <div class="label">Pending Assessments</div>
            </div>
            <div class="stat-card">
                <div class="number"><?php echo $systemStats['active_inspections']; ?></div>
                <div class="label">Active Inspections</div>
            </div>
            <div class="stat-card">
                <div class="number"><?php echo $systemStats['total_users']; ?></div>
                <div class="label">System Users</div>
            </div>
        </div>
    </div>

    <!-- Recent Activity -->
    <div class="dashboard-section">
        <h3>Recent System Activity</h3>
        <?php if (!empty($recentReports)): ?>
            <?php foreach ($recentReports as $report): ?>
                <div class="activity-item">
                    <div>
                        <strong><?php echo ucfirst($report['type']); ?>:</strong>
                        <?php echo htmlspecialchars($report['title']); ?>
                        by <?php echo htmlspecialchars($report['first_name'] . ' ' . $report['last_name']); ?>
                    </div>
                    <div class="activity-time">
                        <?php echo date('M j, Y - g:i A', strtotime($report['created_at'])); ?>
                        | Status: <?php echo htmlspecialchars(ucfirst($report['status'])); ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="activity-item">
                <div>No recent activity found</div>
            </div>
        <?php endif; ?>
    </div>

    <!-- User Activity -->
    <div class="dashboard-section">
        <h3>Your Recent Activity</h3>
        <?php if (!empty($recent_activity)): ?>
            <?php foreach ($recent_activity as $activity): ?>
                <div class="activity-item">
                    <div><?php echo htmlspecialchars($activity['activity_description']); ?></div>
                    <div class="activity-time"><?php echo date('M j, Y - g:i A', strtotime($activity['created_at'])); ?></div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="activity-item">
                <div>No recent activity found</div>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
