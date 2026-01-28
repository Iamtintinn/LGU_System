<?php
// Publications View - Engineer Module
// View publications created by LGU officers
session_start();
require_once '../config/auth.php';
require_once '../config/database.php';

// Require engineer role
$auth->requireRole('engineer');

// Get publication ID from URL if specified
$publicationId = isset($_GET['id']) ? (int)$_GET['id'] : null;

// Handle AJAX request for publication details
if (isset($_GET['ajax_get_details'])) {
    $database = new Database();
    $conn = $database->getConnection();
    $id = (int)$_GET['ajax_get_details'];
    
    $stmt = $conn->prepare("
        SELECT pp.*, u.first_name, u.last_name
        FROM public_publications pp
        LEFT JOIN users u ON pp.published_by = u.id
        WHERE pp.id = ?
    ");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $data = $result->fetch_assoc();
    
    if ($data) {
        // Format dates
        $data['formatted_date'] = date('M j, Y', strtotime($data['publication_date']));
        $data['formatted_reported'] = date('M j, Y', strtotime($data['date_reported']));
        $data['formatted_start'] = $data['repair_start_date'] ? date('M j, Y', strtotime($data['repair_start_date'])) : 'N/A';
        $data['formatted_completion'] = $data['completion_date'] ? date('M j, Y', strtotime($data['completion_date'])) : 'N/A';
        
        header('Content-Type: application/json');
        echo json_encode($data);
    } else {
        http_response_code(404);
        echo json_encode(['error' => 'Publication not found']);
    }
    exit;
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $database = new Database();
    $conn = $database->getConnection();
    
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'create_new_publication':
                // Create a new publication without linking to damage report
                $roadName = $_POST['road_name'];
                $publicationTitle = $_POST['publication_title'];
                $issueSummary = $_POST['issue_summary'];
                $issueType = $_POST['issue_type'];
                $severityPublic = $_POST['severity_public'];
                $statusPublic = $_POST['status_public'];
                $dateReported = $_POST['date_reported'];
                $repairStartDate = $_POST['repair_start_date'] ?: null;
                $completionDate = $_POST['completion_date'] ?: null;
                $additionalNotes = $_POST['additional_notes'] ?: null;
                
                // Generate publication ID
                $publicationId_new = 'PUB-' . date('Y') . '-' . str_pad(mt_rand(1, 999), 3, '0', STR_PAD_LEFT);
                
                // Calculate repair duration
                $repairDuration = null;
                if ($repairStartDate && $completionDate) {
                    $start = new DateTime($repairStartDate);
                    $end = new DateTime($completionDate);
                    $repairDuration = $start->diff($end)->days;
                }
                
                // Insert publication
                $stmt = $conn->prepare("
                    INSERT INTO public_publications (
                        publication_id, publication_title, damage_report_id, road_name, issue_summary, issue_type,
                        severity_public, status_public, date_reported, repair_start_date,
                        completion_date, repair_duration_days, is_published, approval_status, publication_date, published_by
                    ) VALUES (?, ?, NULL, ?, ?, ?, ?, ?, ?, ?, ?, ?, 0, 'pending', NOW(), ?)
                ");
                
                if (!$stmt) {
                    $_SESSION['error'] = "Database error: " . $conn->error;
                    break;
                }
                
                $stmt->bind_param("ssssssssssii", 
                    $publicationId_new, $publicationTitle, $roadName, $issueSummary, $issueType,
                    $severityPublic, $statusPublic, $dateReported, $repairStartDate,
                    $completionDate, $repairDuration, $_SESSION['user_id']
                );
                
                if ($stmt->execute()) {
                    // Log activity
                    if (method_exists($auth, 'logActivity')) {
                        $auth->logActivity('publication_proposal', "Proposed new publication: $publicationId_new");
                    }
                    
                    // Add initial progress entry
                    $dbPublicationId = $conn->insert_id;
                    $currentDate = date('Y-m-d');
                    $description = "Publication proposal created: " . $publicationTitle;
                    
                    $progressStmt = $conn->prepare("
                        INSERT INTO publication_progress (publication_id, progress_date, status, description, created_by)
                        VALUES (?, ?, 'reported', ?, ?)
                    ");
                    $progressStmt->bind_param("issi", $dbPublicationId, $currentDate, $description, $_SESSION['user_id']);
                    $progressStmt->execute();
                    
                    // Create notification for LGU Officers
                    $notificationStmt = $conn->prepare("
                        INSERT INTO notifications (user_id, type, title, message, related_id, created_at)
                        SELECT u.id, 'warning', 'New Publication Proposal', ?, ?, NOW()
                        FROM users u
                        WHERE u.role = 'lgu_officer' OR u.role = 'admin'
                    ");
                    $notificationMessage = "Engineer Proposal: {$publicationTitle} for {$roadName} requires approval.";
                    $notificationStmt->bind_param("si", $notificationMessage, $dbPublicationId);
                    $notificationStmt->execute();
                    
                    $_SESSION['success'] = "New publication proposed successfully! Waiting for LGU Officer approval.";
                } else {
                    $_SESSION['error'] = "Failed to create publication: " . $conn->error;
                }
                break;
        }
        
        // Redirect to prevent form resubmission
        header("Location: " . $_SERVER['PHP_SELF']);
        exit;
    }
}

// Get publications data
$database = new Database();
$conn = $database->getConnection();

// Get all publications
$publications = [];
$stmt = $conn->prepare("
    SELECT pp.*, u.first_name, u.last_name
    FROM public_publications pp
    LEFT JOIN users u ON pp.published_by = u.id
    WHERE (pp.is_published = 1 AND pp.archived = 0)
    OR (pp.published_by = ? AND pp.archived = 0)
    ORDER BY pp.publication_date DESC
");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $publications[] = $row;
}


// Get statistics
$stats = [
    'total' => count($publications),
    'completed' => 0,
    'under_repair' => 0,
    'reported' => 0,
    'high_severity' => 0
];

foreach ($publications as $pub) {
    if ($pub['status_public'] === 'completed' || $pub['status_public'] === 'fixed') {
        $stats['completed']++;
    } elseif ($pub['status_public'] === 'under_repair') {
        $stats['under_repair']++;
    } elseif ($pub['status_public'] === 'reported') {
        $stats['reported']++;
    }
    
    if ($pub['severity_public'] === 'high') {
        $stats['high_severity']++;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Publications View | Engineer Module</title>
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

        /* Stats Grid */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: var(--glass-bg);
            backdrop-filter: blur(10px);
            border-radius: 16px;
            border: 1px solid var(--glass-border);
            padding: 24px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15);
            transition: all 0.3s ease;
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

        .stat-icon.total { background: var(--primary); }
        .stat-icon.completed { background: var(--success); }
        .stat-icon.repair { background: var(--warning); }
        .stat-icon.high { background: var(--danger); }

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

        /* Content Sections */
        .content-card {
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
            margin-bottom: 20px;
        }

        /* Publication Detail */
        .publication-detail {
            background: #f8fafc;
            padding: 25px;
            border-radius: 12px;
            margin-bottom: 20px;
            border-left: 4px solid var(--primary);
        }

        .publication-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 20px;
        }

        .publication-title {
            font-size: 1.3rem;
            font-weight: 700;
            color: var(--text-main);
            margin-bottom: 8px;
        }

        .publication-meta {
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
            margin-bottom: 15px;
        }

        .meta-item {
            display: flex;
            align-items: center;
            gap: 5px;
            font-size: 0.9rem;
            color: var(--text-muted);
        }

        /* Table Styling */
        .table-container {
            width: 100%;
            overflow-x: auto;
            margin-bottom: 20px;
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
            font-size: 0.9rem;
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

        .status-reported { background: #fef3c7; color: #92400e; }
        .status-under-repair { background: #fef3c7; color: #92400e; }
        .status-completed { background: #dcfce7; color: #166534; }
        .status-fixed { background: #dcfce7; color: #166534; }

        .approval-pending { background: #fef3c7; color: #92400e; border: 1px solid #fde68a; }
        .approval-approved { background: #dcfce7; color: #166534; border: 1px solid #bbf7d0; }
        .approval-rejected { background: #fee2e2; color: #991b1b; border: 1px solid #fecaca; }
        .approval-needs-revision { background: #fef3c7; color: #92400e; border: 1px solid #fde68a; border-left: 4px solid var(--warning); }

        .severity-low { background: #dbeafe; color: #1e40af; }
        .severity-medium { background: #fef3c7; color: #92400e; }
        .severity-high { background: #fecaca; color: #dc2626; }

        /* Buttons */
        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 8px;
            font-size: 0.9rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .btn-primary {
            background: var(--primary);
            color: white;
        }

        .btn-primary:hover {
            background: var(--primary-hover);
        }

        .btn-sm {
            padding: 6px 12px;
            font-size: 0.8rem;
        }

        /* Alert Messages */
        .alert {
            padding: 15px 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-weight: 500;
        }

        .alert-success {
            background: #dcfce7;
            color: #166534;
            border: 1px solid #bbf7d0;
        }

        .alert-error {
            background: #fef2f2;
            color: #dc2626;
            border: 1px solid #fecaca;
        }

        /* Forms */
        .form-group {
            margin-bottom: 20px;
        }

        .form-label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: var(--text-main);
        }

        .form-control {
            width: 100%;
            padding: 10px 15px;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            font-size: 0.9rem;
            transition: border-color 0.3s ease;
        }

        .form-control:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
        }

        select.form-control {
            cursor: pointer;
        }

        textarea.form-control {
            resize: vertical;
            min-height: 100px;
        }

        /* Modal */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            backdrop-filter: blur(5px);
            animation: fadeIn 0.3s ease;
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        .modal-content {
            background: white;
            margin: 5vh auto;
            padding: 0;
            border-radius: 20px;
            width: 90%;
            max-width: 800px;
            max-height: 90vh;
            overflow-y: auto;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
            animation: slideUp 0.3s ease;
        }

        @keyframes slideUp {
            from { transform: translateY(20px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }

        .modal-header {
            padding: 25px 30px;
            border-bottom: 1px solid #f1f5f9;
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: #fff;
            position: sticky;
            top: 0;
            z-index: 10;
        }

        .modal-body {
            padding: 30px;
        }

        .modal-footer {
            padding: 20px 30px;
            border-top: 1px solid #f1f5f9;
            display: flex;
            justify-content: flex-end;
            background: #f8fafc;
        }

        .modal-title {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--text-main);
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .close {
            font-size: 1.5rem;
            cursor: pointer;
            color: var(--text-muted);
            background: #f1f5f9;
            border: none;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.2s;
        }

        .close:hover {
            background: #e2e8f0;
            color: var(--text-main);
            transform: rotate(90deg);
        }

        .detail-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 25px;
            margin-bottom: 25px;
        }

        .detail-item label {
            display: block;
            font-size: 0.85rem;
            font-weight: 600;
            color: var(--text-muted);
            margin-bottom: 5px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .detail-item div {
            font-size: 1rem;
            font-weight: 600;
            color: var(--text-main);
        }

        .summary-box {
            background: #f8fafc;
            padding: 20px;
            border-radius: 12px;
            border-left: 4px solid var(--primary);
            margin-bottom: 25px;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .main-content {
                padding: 20px;
                margin-left: 0;
            }
            
            .stats-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <?php include 'sidebar_engineer.php'; ?>

    <main class="main-content">
        <header class="module-header">
            <h1><i class="fas fa-newspaper"></i> Publications View</h1>
            <div style="background: rgba(255,255,255,0.1); padding: 5px 15px; border-radius: 20px; color: #fff; font-size: 0.8rem; display: inline-block; margin-top: 5px;">
                <i class="fas fa-sync"></i> Live Updates
            </div>
            <p style="margin-top: 10px;">View and monitor public infrastructure publications from LGU officers</p>
            <hr class="header-divider">
        </header>

        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i> <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
            </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-circle"></i> <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
            </div>
        <?php endif; ?>

        <!-- Statistics Overview -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon total">
                    <i class="fas fa-newspaper"></i>
                </div>
                <div class="stat-value"><?php echo $stats['total']; ?></div>
                <div class="stat-label">Total Publications</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon completed">
                    <i class="fas fa-check-circle"></i>
                </div>
                <div class="stat-value"><?php echo $stats['completed']; ?></div>
                <div class="stat-label">Completed Works</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon repair">
                    <i class="fas fa-tools"></i>
                </div>
                <div class="stat-value"><?php echo $stats['under_repair']; ?></div>
                <div class="stat-label">Under Repair</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon high">
                    <i class="fas fa-exclamation-triangle"></i>
                </div>
                <div class="stat-value"><?php echo $stats['high_severity']; ?></div>
                <div class="stat-label">High Priority</div>
            </div>
        </div>


        <!-- Create New Publication -->
        <div class="content-card">
            <h2 class="section-title"><i class="fas fa-plus-circle"></i> Create New Publication</h2>
            <p style="color: var(--text-muted); margin-bottom: 20px;">Create a new public announcement or road infrastructure update for citizens.</p>
            
            <form method="POST" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px;">
                <input type="hidden" name="action" value="create_new_publication">
                
                <div class="form-group">
                    <label class="form-label">Road Name *</label>
                    <input type="text" name="road_name" class="form-control" placeholder="e.g., Main Street, Highway 1" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Publication Title *</label>
                    <input type="text" name="publication_title" class="form-control" placeholder="e.g., Road Repair Notice" required>
                </div>
                
                <div class="form-group" style="grid-column: 1 / -1;">
                    <label class="form-label">Issue Summary/Description *</label>
                    <textarea name="issue_summary" class="form-control" placeholder="Provide detailed information about the road issue, maintenance work, or infrastructure update..." required></textarea>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Issue Type *</label>
                    <select name="issue_type" class="form-control" required>
                        <option value="">Select Issue Type</option>
                        <option value="pothole">Pothole Repair</option>
                        <option value="crack">Crack Repair</option>
                        <option value="drainage">Drainage Maintenance</option>
                        <option value="surface_damage">Surface Resurfacing</option>
                        <option value="construction">New Construction</option>
                        <option value="maintenance">Routine Maintenance</option>
                        <option value="closure">Road Closure</option>
                        <option value="announcement">Public Announcement</option>
                        <option value="other">Other</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Public Severity Level *</label>
                    <select name="severity_public" class="form-control" required>
                        <option value="">Select Severity</option>
                        <option value="low">Low - Minor Issue</option>
                        <option value="medium">Medium - Moderate Impact</option>
                        <option value="high">High - Major Impact</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Public Status *</label>
                    <select name="status_public" class="form-control" required>
                        <option value="">Select Status</option>
                        <option value="reported">Reported</option>
                        <option value="under_repair">Under Repair</option>
                        <option value="completed">Completed</option>
                        <option value="fixed">Fixed</option>
                        <option value="scheduled">Scheduled</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Date Reported *</label>
                    <input type="date" name="date_reported" class="form-control" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Expected Start Date</label>
                    <input type="date" name="repair_start_date" class="form-control">
                </div>
                
                <div class="form-group">
                    <label class="form-label">Expected Completion Date</label>
                    <input type="date" name="completion_date" class="form-control">
                </div>
                
                <div class="form-group" style="grid-column: 1 / -1;">
                    <label class="form-label">Additional Notes</label>
                    <textarea name="additional_notes" class="form-control" placeholder="Any additional information for the public (detour routes, contact information, etc.)..." rows="3"></textarea>
                </div>
                
                <div style="grid-column: 1 / -1; display: flex; gap: 10px; justify-content: flex-end; margin-top: 10px;">
                    <button type="button" class="btn" style="background: #e5e7eb; color: #374151;" onclick="this.form.reset()">
                        <i class="fas fa-times"></i> Clear Form
                    </button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-paper-plane"></i> Send for Approval
                    </button>
                </div>
            </form>
        </div>

        <div class="content-card">
            <h2 class="section-title">
                <i class="fas fa-list"></i> All Publications
            </h2>
            
            <?php if (empty($publications)): ?>
                <div style="text-align: center; padding: 60px 20px;">
                    <i class="fas fa-newspaper" style="font-size: 3rem; color: var(--text-muted); margin-bottom: 15px; display: block;"></i>
                    <h3 style="color: var(--text-main); margin-bottom: 10px;">No Publications Available</h3>
                    <p style="color: var(--text-muted);">No publications have been created by LGU officers yet.</p>
                </div>
            <?php else: ?>
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>Publication ID</th>
                                <th>Road Name</th>
                                <th>Issue Summary</th>
                                <th>Type</th>
                                <th>Severity</th>
                                <th>Status</th>
                                <th>Approval</th>
                                <th>Publication Date</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($publications as $publication): ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($publication['publication_id']); ?></strong></td>
                                    <td><?php echo htmlspecialchars($publication['road_name']); ?></td>
                                    <td><?php echo htmlspecialchars(substr($publication['issue_summary'], 0, 50)) . '...'; ?></td>
                                    <td><?php echo ucfirst(str_replace('_', ' ', $publication['issue_type'])); ?></td>
                                    <td><span class="status-badge severity-<?php echo $publication['severity_public']; ?>"><?php echo ucfirst($publication['severity_public']); ?></span></td>
                                    <td><span class="status-badge status-<?php echo str_replace('_', '-', $publication['status_public']); ?>"><?php echo ucfirst(str_replace('_', ' ', $publication['status_public'])); ?></span></td>
                                    <td>
                                        <span class="status-badge approval-<?php echo $publication['approval_status']; ?>">
                                            <i class="fas <?php 
                                                echo $publication['approval_status'] === 'approved' ? 'fa-check-circle' : 
                                                    ($publication['approval_status'] === 'pending' ? 'fa-clock' : 
                                                    ($publication['approval_status'] === 'needs_revision' ? 'fa-edit' : 'fa-times-circle')); 
                                            ?>"></i>
                                            <?php 
                                                if ($publication['approval_status'] === 'needs_revision') {
                                                    echo "Needs Revision";
                                                } else {
                                                    echo ucfirst($publication['approval_status']);
                                                }
                                            ?>
                                        </span>
                                    </td>
                                    <td><?php echo date('M j, Y', strtotime($publication['publication_date'])); ?></td>
                                    <td>
                                        <button onclick="viewPublication(<?php echo $publication['id']; ?>)" class="btn btn-primary btn-sm">
                                            <i class="fas fa-eye"></i> View
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <!-- Publication Detail Modal -->
    <div id="viewModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title" id="modalTitle"><i class="fas fa-file-alt"></i> Publication Details</h3>
                <button type="button" class="close" onclick="closeModal()">&times;</button>
            </div>
            <div class="modal-body" id="modalContent">
                <div style="text-align: center; padding: 40px;">
                    <i class="fas fa-circle-notch fa-spin" style="font-size: 2rem; color: var(--primary);"></i>
                    <p style="margin-top: 15px; color: var(--text-muted);">Loading details...</p>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn" style="background: #e2e8f0;" onclick="closeModal()">Close</button>
            </div>
        </div>
    </div>

    <script>
        function viewPublication(id) {
            const modal = document.getElementById('viewModal');
            const content = document.getElementById('modalContent');
            modal.style.display = 'block';
            
            // Initial loader
            content.innerHTML = `
                <div style="text-align: center; padding: 40px;">
                    <i class="fas fa-circle-notch fa-spin" style="font-size: 2rem; color: var(--primary);"></i>
                    <p style="margin-top: 15px; color: var(--text-muted);">Loading details...</p>
                </div>
            `;

            fetch(`publications_view.php?ajax_get_details=${id}`)
                .then(response => response.json())
                .then(data => {
                    if (data.error) {
                        content.innerHTML = `<p class="alert alert-error">${data.error}</p>`;
                        return;
                    }

                    const statusClass = data.status_public.replace('_', '-');
                    const approvalClass = data.approval_status;

                    content.innerHTML = `
                        <div class="publication-header" style="margin-bottom: 30px; border-bottom: 2px solid #f1f5f9; padding-bottom: 20px;">
                            <div>
                                <h2 style="font-size: 1.8rem; color: var(--text-main); margin-bottom: 10px;">${data.publication_title || data.road_name}</h2>
                                <div class="publication-meta" style="flex-wrap: wrap;">
                                    <span class="meta-item"><i class="fas fa-map-marker-alt"></i> ${data.road_name}</span>
                                    <span class="meta-item"><i class="fas fa-hashtag"></i> ${data.publication_id}</span>
                                    <span class="meta-item"><i class="fas fa-calendar-alt"></i> Published: ${data.formatted_date}</span>
                                    <span class="meta-item"><i class="fas fa-user-edit"></i> ${data.first_name} ${data.last_name}</span>
                                </div>
                            </div>
                            <div style="display: flex; flex-direction: column; align-items: flex-end; gap: 10px;">
                                <span class="status-badge status-${statusClass}">${data.status_public.replace('_', ' ').toUpperCase()}</span>
                                <span class="status-badge approval-${approvalClass}">${data.approval_status.toUpperCase()}</span>
                            </div>
                        </div>

                        <div class="summary-box">
                            <label class="form-label" style="font-size: 0.8rem; color: var(--primary);"><i class="fas fa-info-circle"></i> PROJECT SUMMARY</label>
                            <p style="font-size: 1.1rem; line-height: 1.6; color: var(--text-main); font-weight: 500;">${data.issue_summary}</p>
                        </div>

                        <div class="detail-row">
                            <div class="detail-item">
                                <label>Issue Type</label>
                                <div>${data.issue_type.replace('_', ' ').toUpperCase()}</div>
                            </div>
                            <div class="detail-item">
                                <label>Severity Level</label>
                                <div><span class="status-badge severity-${data.severity_public}">${data.severity_public.toUpperCase()}</span></div>
                            </div>
                            <div class="detail-item">
                                <label>Date Reported</label>
                                <div>${data.formatted_reported}</div>
                            </div>
                        </div>

                        <div class="detail-row" style="background: #f8fafc; padding: 20px; border-radius: 12px;">
                            <div class="detail-item">
                                <label>Expected Start</label>
                                <div>${data.formatted_start}</div>
                            </div>
                            <div class="detail-item">
                                <label>Expected Completion</label>
                                <div>${data.formatted_completion}</div>
                            </div>
                            <div class="detail-item">
                                <label>Duration</label>
                                <div>${data.repair_duration_days ? data.repair_duration_days + ' Days' : 'N/A'}</div>
                            </div>
                        </div>
                    `;
                })
                .catch(err => {
                    content.innerHTML = `<p class="alert alert-error">Error loading details. Please try again.</p>`;
                });
        }

        function closeModal() {
            document.getElementById('viewModal').style.display = 'none';
        }

        window.onclick = function(event) {
            const modal = document.getElementById('viewModal');
            if (event.target == modal) {
                closeModal();
            }
        }

        // Auto-open modal if ID is in URL
        document.addEventListener('DOMContentLoaded', () => {
            const urlParams = new URLSearchParams(window.location.search);
            const pubId = urlParams.get('id');
            if (pubId) {
                viewPublication(pubId);
            }
        });
    </script>
</body>
</html>
