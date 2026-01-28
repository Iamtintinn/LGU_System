<?php
// Start session and include authentication
session_start();

$basePath = '';
$loginUrl = 'login.php';

if (isset($_SERVER['SCRIPT_NAME']) && basename($_SERVER['SCRIPT_NAME']) === 'index.php') {
    $basePath = 'lgu-portal/public/';
    $loginUrl = 'index.php';
    $employeeUrl = 'lgu-portal/public/employee.php';
}

require_once '../config/auth.php';
require_once '../config/database.php';

// Require engineer role to access this page
$auth->requireRole('engineer');

// Log page access
$auth->logActivity('page_access', 'Accessed engineer dashboard');

// Get engineer statistics
try {
    $database = new Database();
    $conn = $database->getConnection();
    
    $stats = [];
    
    // Get total reports assigned
    $res = $conn->query("SELECT COUNT(*) as total FROM road_reports WHERE assigned_engineer = '" . $_SESSION['user_id'] . "'");
    $stats['assigned_reports'] = $res->fetch_assoc()['total'] ?? 0;
    
    // Get completed reports
    $res = $conn->query("SELECT COUNT(*) as total FROM road_reports WHERE assigned_engineer = '" . $_SESSION['user_id'] . "' AND status = 'completed'");
    $stats['completed_reports'] = $res->fetch_assoc()['total'] ?? 0;
    
    // Get pending inspections
    $res = $conn->query("SELECT COUNT(*) as total FROM inspections WHERE inspector_id = '" . $_SESSION['user_id'] . "' AND status = 'pending'");
    $stats['pending_inspections'] = $res->fetch_assoc()['total'] ?? 0;
    
    // Get recent activity
    $res = $conn->query("SELECT COUNT(*) as total FROM activity_logs WHERE user_id = '" . $_SESSION['user_id'] . "' AND created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)");
    $stats['recent_activity'] = $res->fetch_assoc()['total'] ?? 0;
    
} catch (Exception $e) {
    $stats = ['assigned_reports' => '!', 'completed_reports' => '!', 'pending_inspections' => '!', 'recent_activity' => '!'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Engineer Dashboard | LGU Portal</title>
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

        /* Module Header */
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

        /* Stats Grid */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: var(--glass-bg);
            backdrop-filter: blur(10px);
            border-radius: 12px;
            border: 1px solid var(--glass-border);
            padding: 24px;
            text-align: center;
            display: flex;
            flex-direction: column;
            gap: 8px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }

        .stat-number {
            font-size: 2.4rem;
            font-weight: 700;
            color: var(--primary);
        }

        .stat-label {
            font-size: 0.85rem;
            font-weight: 600;
            color: var(--text-muted);
        }

        /* Quick Actions */
        .quick-actions {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
            margin-bottom: 30px;
        }

        .action-card {
            background: var(--glass-bg);
            backdrop-filter: blur(10px);
            border-radius: 12px;
            border: 1px solid var(--glass-border);
            padding: 30px;
            text-align: center;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
            text-decoration: none;
            color: var(--text-main);
        }

        .action-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
        }

        .action-icon {
            font-size: 3rem;
            color: var(--primary);
            margin-bottom: 15px;
        }

        .action-title {
            font-size: 1.2rem;
            font-weight: 600;
            margin-bottom: 8px;
        }

        .action-description {
            font-size: 0.9rem;
            color: var(--text-muted);
        }

        /* Scrollbar Styling */
        .main-content::-webkit-scrollbar { width: 10px; }
        .main-content::-webkit-scrollbar-track { background: rgba(255, 255, 255, 0.1); }
        .main-content::-webkit-scrollbar-thumb {
            background: #888;
            border-radius: 10px;
            border: 2px solid transparent;
            background-clip: content-box;
        }
        .main-content::-webkit-scrollbar-thumb:hover { background: #555; background-clip: content-box; }
    </style>
</head>
<body>
    <?php include 'sidebar_engineer.php'; ?>

    <div class="main-content">
        <header class="module-header">
            <h1><i class="fas fa-hard-hat"></i> Engineer Dashboard</h1>
            <div style="background: rgba(255,255,255,0.1); padding: 5px 15px; border-radius: 20px; color: #fff; font-size: 0.8rem; display: inline-block; margin-top: 5px;">
                <i class="fas fa-sync"></i> Real-time System Sync
            </div>
            <p style="margin-top: 10px;">Manage road inspections, assessments, and infrastructure reports</p>
            <hr class="header-divider">
        </header>

        <!-- Stats Grid -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-number"><?php echo $stats['assigned_reports']; ?></div>
                <div class="stat-label">Assigned Reports</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $stats['completed_reports']; ?></div>
                <div class="stat-label">Completed Reports</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $stats['pending_inspections']; ?></div>
                <div class="stat-label">Pending Inspections</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $stats['recent_activity']; ?></div>
                <div class="stat-label">Recent Activities</div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="quick-actions">
            <a href="damage_assessment.php" class="action-card">
                <div class="action-icon"><i class="fas fa-search-location"></i></div>
                <div class="action-title">Damage Assessment</div>
                <div class="action-description">Assess damage reports and assign severity</div>
            </a>
            
            <a href="cost_estimation.php" class="action-card">
                <div class="action-icon"><i class="fas fa-calculator"></i></div>
                <div class="action-title">Cost Estimation</div>
                <div class="action-description">Generate budget and material estimates</div>
            </a>

            <a href="inspection_workflow.php" class="action-card">
                <div class="action-icon"><i class="fas fa-tasks"></i></div>
                <div class="action-title">Project Workflow</div>
                <div class="action-description">Monitor and update repair progress</div>
            </a>

            <a href="gis_mapping.php" class="action-card">
                <div class="action-icon"><i class="fas fa-map-marked-alt"></i></div>
                <div class="action-title">GIS Mapping</div>
                <div class="action-description">View infrastructures on the interactive map</div>
            </a>

            <a href="../lgu_officer_module/document_management.php" class="action-card">
                <div class="action-icon"><i class="fas fa-file-pdf"></i></div>
                <div class="action-title">Reports & Docs</div>
                <div class="action-description">Access and manage system documents</div>
            </a>

            <a href="inspection_management.php" class="action-card">
                <div class="action-icon"><i class="fas fa-calendar-alt"></i></div>
                <div class="action-title">Schedule</div>
                <div class="action-description">Manage your inspection schedule</div>
            </a>
        </div>
    </div>
</body>
</html>
