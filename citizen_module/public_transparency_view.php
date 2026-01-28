<?php
// Public Transparency View - Citizen Module
// Shows only published information approved by LGU officers
session_start();
require_once '../config/auth.php';
$auth->requireAnyRole(['citizen', 'admin']);

// Get published data from database
require_once '../config/database.php';
$database = new Database();
$conn = $database->getConnection();

// Get published reports statistics
$stats = [
    'reported_issues' => 0,
    'under_repair' => 0,
    'completed_repairs' => 0
];

// Get published reports for display
$publishedReports = [];
$stmt = $conn->prepare("
    SELECT pp.*, 
           GROUP_CONCAT(
               JSON_OBJECT(
                   'date', pp_progress.progress_date,
                   'status', pp_progress.status,
                   'description', pp_progress.description
               )
               ORDER BY pp_progress.progress_date DESC
           ) as progress_history
    FROM public_publications pp
    LEFT JOIN publication_progress pp_progress ON pp.id = pp_progress.publication_id AND pp_progress.is_public_visible = 1
    WHERE pp.is_published = 1 AND pp.archived = 0
    GROUP BY pp.id
    ORDER BY pp.publication_date DESC
");
$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    $publishedReports[] = $row;
    
    // Update statistics
    $stats['reported_issues']++;
    if ($row['status_public'] === 'under_repair') {
        $stats['under_repair']++;
    } elseif (in_array($row['status_public'], ['completed', 'fixed'])) {
        $stats['completed_repairs']++;
    }
}

// Get approved cost information (only published projects)
$approvedProjects = [];
$stmt = $conn->prepare("
    SELECT pp.publication_id, pp.road_name, pp.issue_summary, 
           ca.total_cost, ca.assessment_date, ca.status as assessment_status
    FROM public_publications pp
    LEFT JOIN damage_reports dr ON pp.damage_report_id = dr.id
    LEFT JOIN cost_assessments ca ON dr.id = ca.damage_report_id
    WHERE pp.is_published = 1 AND pp.archived = 0 
    AND ca.status = 'approved'
    ORDER BY pp.publication_date DESC
");
$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    $approvedProjects[] = $row;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Public Transparency | Citizen Portal</title>
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

        /* Module Header */
        .module-header {
            color: white;
            margin-bottom: 30px;
        }

        .module-header h1 {
            font-size: 2.2rem;
            font-weight: 700;
            margin-bottom: 8px;
        }

        .module-header p {
            font-size: 1rem;
            opacity: 0.9;
            letter-spacing: 1px;
        }

        .header-divider {
            border: none;
            height: 1px;
            background: rgba(255, 255, 255, 0.3);
            margin: 15px 0;
        }

        /* Content Sections */
        .transparency-card {
            background: var(--glass-bg);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            border: 1px solid var(--glass-border);
            padding: 30px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
            margin-bottom: 30px;
        }

        .section-title {
            color: #1e40af;
            font-size: 1.4rem;
            font-weight: 700;
            margin-bottom: 10px;
        }

        .section-desc {
            color: var(--text-muted);
            font-size: 0.95rem;
            margin-bottom: 25px;
        }

        /* Overview Grid */
        .overview-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
        }

        .stat-box {
            background: #f1f5f9;
            border-radius: 12px;
            padding: 25px;
            text-align: center;
        }

        .stat-box .label {
            display: block;
            font-weight: 700;
            color: #1e293b;
            margin-bottom: 10px;
            font-size: 1.1rem;
        }

        .stat-box .value {
            display: block;
            font-size: 1.8rem;
            font-weight: 700;
            color: #0f172a;
        }

        /* Table Styling */
        .table-container {
            width: 100%;
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th {
            background: #f1f5f9;
            color: #1e293b;
            font-weight: 700;
            text-align: left;
            padding: 15px;
            font-size: 0.95rem;
        }

        td {
            padding: 15px;
            border-bottom: 1px solid rgba(0,0,0,0.05);
            font-size: 0.9rem;
            color: #334155;
        }

        tr:last-child td {
            border-bottom: none;
        }

        /* Status Badge */
        .status-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
        }

        .status-completed {
            background: #dcfce7;
            color: #166534;
        }

        .status-progress {
            background: #fef3c7;
            color: #92400e;
        }

        .status-planning {
            background: #e0e7ff;
            color: #3730a3;
        }

        /* Map Placeholder */
        .map-placeholder {
            background: #f1f5f9;
            border-radius: 12px;
            height: 350px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #64748b;
            font-size: 1rem;
            text-align: center;
            border: 2px dashed #cbd5e1;
        }

        /* Filter Section */
        .filter-section {
            background: #f8fafc;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 20px;
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
            align-items: center;
        }

        .filter-btn {
            padding: 8px 16px;
            border: 1px solid #e2e8f0;
            background: white;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s ease;
            font-size: 0.9rem;
        }

        .filter-btn:hover {
            background: #f1f5f9;
        }

        .filter-btn.active {
            background: var(--primary);
            color: white;
            border-color: var(--primary);
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
    <?php include '../sidebar/sidebar_citizen.php'; ?>

    <main class="main-content">
        <header class="module-header">
            <h1>Public Transparency</h1>
            <p>View information about road maintenance, project costs, and infrastructure development</p>
            <hr class="header-divider">
        </header>

        <!-- Road Maintenance Overview -->
        <div class="transparency-card">
            <h2 class="section-title">Road Maintenance Overview</h2>
            <p class="section-desc">Summary of verified road conditions and repair progress in your area.</p>
            <div class="overview-grid">
                <div class="stat-box">
                    <span class="label">Reported Issues</span>
                    <span class="value"><?php echo $stats['reported_issues']; ?></span>
                </div>
                <div class="stat-box">
                    <span class="label">Under Repair</span>
                    <span class="value"><?php echo $stats['under_repair']; ?></span>
                </div>
                <div class="stat-box">
                    <span class="label">Completed Repairs</span>
                    <span class="value"><?php echo $stats['completed_repairs']; ?></span>
                </div>
            </div>
        </div>

        <!-- Cost Transparency -->
        <div class="transparency-card">
            <h2 class="section-title">Cost Transparency (Approved Projects)</h2>
            <p class="section-desc">Displays approved and verified cost information for infrastructure projects.</p>
            
            <div class="filter-section">
                <button class="filter-btn active">All Projects</button>
                <button class="filter-btn">Completed</button>
                <button class="filter-btn">In Progress</button>
                <button class="filter-btn">Planning</button>
            </div>

            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Project</th>
                            <th>Road Name</th>
                            <th>Approved Budget</th>
                            <th>Assessment Date</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($approvedProjects)): ?>
                            <tr>
                                <td colspan="5" style="text-align: center; padding: 40px; color: var(--text-muted);">
                                    <i class="fas fa-info-circle" style="font-size: 1.5rem; margin-bottom: 10px; display: block;"></i>
                                    No approved cost information available at this time.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($approvedProjects as $project): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($project['publication_id']); ?></td>
                                    <td><?php echo htmlspecialchars($project['road_name']); ?></td>
                                    <td>₱<?php echo number_format($project['total_cost'], 2); ?></td>
                                    <td><?php echo date('M j, Y', strtotime($project['assessment_date'])); ?></td>
                                    <td><span class="status-badge status-completed">Approved</span></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Public Road Map -->
        <div class="transparency-card">
            <h2 class="section-title">Public Road Map</h2>
            <p class="section-desc">Visual display of verified road issues and project locations.</p>
            <div class="map-placeholder">
                <i class="fas fa-map-marked-alt" style="font-size: 3rem; margin-bottom: 15px; display: block;"></i>
                Interactive Map View (GIS Integration)<br>
                <small>Showing road conditions and project locations</small>
            </div>
        </div>

        <!-- Verified Road Issues -->
        <div class="transparency-card">
            <h2 class="section-title">Verified Road Issues & Repair Status</h2>
            <p class="section-desc">Publicly accessible information about road conditions and repair status.</p>
            
            <div class="filter-section">
                <button class="filter-btn active">All Issues</button>
                <button class="filter-btn">Potholes</button>
                <button class="filter-btn">Cracks</button>
                <button class="filter-btn">Drainage</button>
            </div>

            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Road Name</th>
                            <th>Issue Type</th>
                            <th>Severity</th>
                            <th>Status</th>
                            <th>Reported Date</th>
                            <th>Repair Duration</th>
                            <th>Completion Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($publishedReports)): ?>
                            <tr>
                                <td colspan="7" style="text-align: center; padding: 40px; color: var(--text-muted);">
                                    <i class="fas fa-info-circle" style="font-size: 1.5rem; margin-bottom: 10px; display: block;"></i>
                                    No published road issues available at this time.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($publishedReports as $report): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($report['road_name']); ?></td>
                                    <td><?php echo ucfirst($report['issue_type']); ?></td>
                                    <td><span class="status-badge status-<?php echo $report['severity_public']; ?>"><?php echo ucfirst($report['severity_public']); ?></span></td>
                                    <td><span class="status-badge status-<?php echo str_replace('_', '-', $report['status_public']); ?>"><?php echo ucfirst(str_replace('_', ' ', $report['status_public'])); ?></span></td>
                                    <td><?php echo date('M j, Y', strtotime($report['date_reported'])); ?></td>
                                    <td>
                                        <?php if ($report['repair_duration_days']): ?>
                                            <?php echo $report['repair_duration_days']; ?> days
                                        <?php else: ?>
                                            <span style="color: var(--text-muted);">N/A</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($report['completion_date']): ?>
                                            <?php echo date('M j, Y', strtotime($report['completion_date'])); ?>
                                        <?php else: ?>
                                            <span style="color: var(--text-muted);">N/A</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>
</body>
</html>
