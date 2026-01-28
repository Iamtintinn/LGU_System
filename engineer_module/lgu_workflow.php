<?php
// LGU Workflow - Engineer Module
// Create and submit inspection reports for LGU Officer approval
session_start();
require_once '../config/auth.php';
require_once '../config/database.php';

// Require engineer role
$auth->requireRole('engineer');

// Initialize database connection
$database = new Database();
$conn = $database->getConnection();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'submit_report') {
        // Create new inspection report for LGU approval
        $location = $_POST['location'] ?? '';
        $inspection_date = $_POST['inspection_date'] ?? '';
        $severity = $_POST['severity'] ?? '';
        $description = $_POST['description'] ?? '';
        $coordinates = $_POST['coordinates'] ?? '';
        $estimated_cost = $_POST['estimated_cost'] ?? 0;
        $priority = $_POST['priority'] ?? '';
        $notes = $_POST['notes'] ?? '';
        
        // Validate required fields
        if (empty($location) || empty($inspection_date) || empty($severity) || empty($description) || empty($priority)) {
            $_SESSION['error'] = "Please fill in all required fields.";
            header("Location: lgu_workflow.php");
            exit;
        }
        
        // Handle photo uploads
        $photos = [];
        $upload_dir = '../../../uploads/reports/';
        
        // Create upload directory if it doesn't exist
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        
        // Process uploaded photos
        for ($i = 1; $i <= 3; $i++) {
            $photo_key = "uploaded_photo_{$i}";
            if (isset($_FILES[$photo_key]) && $_FILES[$photo_key]['error'] === UPLOAD_ERR_OK) {
                $file = $_FILES[$photo_key];
                $file_name = 'LGU-' . date('Y') . '-' . uniqid() . '_' . $i . '.jpg';
                $file_path = $upload_dir . $file_name;
                
                if (move_uploaded_file($file['tmp_name'], $file_path)) {
                    $photos[] = $file_name;
                }
            }
        }
        
        // Generate unique inspection ID
        $inspection_id = 'LGU-INSP-' . date('Y') . '-' . str_pad(mt_rand(1, 9999), 4, '0', STR_PAD_LEFT);
        
        // Insert inspection report into database
        $stmt = $conn->prepare("
            INSERT INTO lgu_inspections (
                inspection_id, location, inspection_date, severity, description, 
                coordinates, estimated_cost, priority, engineer_id, photos, 
                notes, status, submitted_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending_approval', NOW())
        ");
        
        $photos_json = json_encode($photos);
        $stmt->bind_param(
            'sssssdsssss',
            $inspection_id, $location, $inspection_date, $severity, $description,
            $coordinates, $estimated_cost, $priority, $_SESSION['user_id'], $photos_json, $notes
        );
        
        if ($stmt->execute()) {
            // Create notification for LGU officers
            try {
                $notification_stmt = $conn->prepare("
                    INSERT INTO notifications (user_id, type, title, message, data, created_at, read_status)
                    SELECT u.id, 'lgu_inspection', 'New LGU Inspection Report', 
                           CONCAT('Engineer has submitted a new inspection report for LGU approval: ', ?), 
                           ?, NOW(), 0
                    FROM users u 
                    WHERE u.role = 'lgu_officer' OR u.role = 'admin'
                ");
                
                $notification_data = json_encode([
                    'inspection_id' => $inspection_id,
                    'location' => $location,
                    'severity' => $severity,
                    'type' => 'lgu_workflow'
                ]);
                
                $notification_stmt->bind_param('ss', $inspection_id, $notification_data);
                $notification_stmt->execute();
            } catch (Exception $e) {
                // Log notification error but don't fail the main request
                error_log("Notification error: " . $e->getMessage());
            }
            
            $_SESSION['success'] = "Inspection report submitted successfully for LGU approval!";
            $auth->logActivity('lgu_report_submitted', "Submitted LGU inspection report: $inspection_id");
        } else {
            $_SESSION['error'] = "Failed to submit inspection report: " . $conn->error;
        }
        
        header("Location: lgu_workflow.php");
        exit;
    }
}

// Fetch existing LGU inspection reports
$reports = [];
try {
    $query = "
        SELECT li.*, u.name as engineer_name 
        FROM lgu_inspections li 
        LEFT JOIN users u ON li.engineer_id = u.id 
        WHERE li.engineer_id = ?
        ORDER BY li.submitted_at DESC
    ";
    $stmt = $conn->prepare($query);
    $stmt->bind_param('i', $_SESSION['user_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($row = $result->fetch_assoc()) {
        $photos = json_decode($row['photos'] ?? '[]', true) ?: [];
        $reports[] = [
            'id' => $row['inspection_id'],
            'location' => $row['location'],
            'date' => date('M d, Y', strtotime($row['inspection_date'])),
            'submitted_date' => date('M d, Y H:i', strtotime($row['submitted_at'])),
            'status' => $row['status'],
            'severity' => ucfirst($row['severity']),
            'cost' => $row['estimated_cost'] ? '₱' . number_format($row['estimated_cost'], 2) : '₱0.00',
            'priority' => ucfirst($row['priority']),
            'engineer' => $row['engineer_name'] ?? 'Unknown',
            'description' => $row['description'],
            'images' => $photos,
            'review_notes' => $row['review_notes'] ?? '',
            'review_date' => $row['review_date'] ? date('M d, Y', strtotime($row['review_date'])) : ''
        ];
    }
} catch (Exception $e) {
    error_log("Error fetching reports: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LGU Inspection Workflow | Engineer Portal</title>
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
            color: var(--text-main);
            font-size: 1.4rem;
            font-weight: 700;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 12px;
        }

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

        textarea.form-control {
            resize: vertical;
            min-height: 100px;
        }

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
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        }

        .btn-secondary {
            background: #6b7280;
            color: white;
        }

        .custom-table {
            width: 100%;
            border-collapse: collapse;
        }

        .custom-table th {
            text-align: left;
            padding: 12px 15px;
            font-size: 0.75rem;
            font-weight: 700;
            color: #64748b;
            text-transform: uppercase;
            border-bottom: 1px solid #f1f5f9;
        }

        .custom-table td {
            padding: 18px 15px;
            font-size: 0.95rem;
            color: #334155;
            border-bottom: 1px solid #f1f5f9;
        }

        .badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 14px;
            border-radius: 8px;
            font-size: 0.85rem;
            font-weight: 600;
        }

        .badge-pending {
            background: #fef3c7;
            color: #92400e;
        }

        .badge-approved {
            background: #dcfce7;
            color: #166534;
        }

        .badge-rejected {
            background: #fee2e2;
            color: #dc2626;
        }

        .severity-high {
            color: var(--danger);
        }

        .severity-medium {
            color: var(--warning);
        }

        .severity-low {
            color: var(--success);
        }

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

        .photo-upload {
            border: 2px dashed #e2e8f0;
            border-radius: 8px;
            padding: 20px;
            text-align: center;
            cursor: pointer;
            transition: border-color 0.3s ease;
        }

        .photo-upload:hover {
            border-color: var(--primary);
        }

        .photo-upload input[type="file"] {
            display: none;
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }

        .btn-action {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 8px 18px;
            border-radius: 10px;
            font-size: 0.9rem;
            font-weight: 600;
            color: white;
            background: #2563eb;
            border: none;
            cursor: pointer;
            text-decoration: none;
            transition: 0.2s;
        }

        .btn-action:hover {
            background: #1d4ed8;
            transform: translateY(-1px);
        }
    </style>
</head>
<body>
    <?php include 'sidebar_engineer.php'; ?>

    <main class="main-content">
        <header class="module-header">
            <h1><i class="fas fa-building"></i> LGU Inspection Workflow</h1>
            <div style="background: rgba(255,255,255,0.1); padding: 5px 15px; border-radius: 20px; color: #fff; font-size: 0.8rem; display: inline-block; margin-top: 5px;">
                <i class="fas fa-clipboard-check"></i> LGU Approval Process
            </div>
            <p style="margin-top: 10px;">Create inspection reports that require LGU Officer approval</p>
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

        <!-- New Report Form -->
        <div class="content-card">
            <h2 class="section-title">
                <i class="fas fa-plus-circle"></i> Create New LGU Inspection Report
            </h2>

            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="action" value="submit_report">
                
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Location *</label>
                        <input type="text" name="location" class="form-control" placeholder="Enter inspection location" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Inspection Date *</label>
                        <input type="date" name="inspection_date" class="form-control" required>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Severity Level *</label>
                        <select name="severity" class="form-control" required>
                            <option value="">Select Severity</option>
                            <option value="low">Low</option>
                            <option value="medium">Medium</option>
                            <option value="high">High</option>
                            <option value="urgent">Urgent</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Priority *</label>
                        <select name="priority" class="form-control" required>
                            <option value="">Select Priority</option>
                            <option value="low">Low Priority</option>
                            <option value="medium">Medium Priority</option>
                            <option value="high">High Priority</option>
                        </select>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Coordinates</label>
                        <input type="text" name="coordinates" class="form-control" placeholder="e.g., 14.5995° N, 120.9842° E">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Estimated Cost (₱)</label>
                        <input type="number" name="estimated_cost" class="form-control" placeholder="0.00" step="0.01" min="0">
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">Description *</label>
                    <textarea name="description" class="form-control" placeholder="Provide detailed description of the inspection findings..." required></textarea>
                </div>

                <div class="form-group">
                    <label class="form-label">Additional Notes</label>
                    <textarea name="notes" class="form-control" placeholder="Any additional observations or recommendations..."></textarea>
                </div>

                <div class="form-group">
                    <label class="form-label">Inspection Photos</label>
                    <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 15px;">
                        <div class="photo-upload">
                            <input type="file" name="uploaded_photo_1" accept="image/*" id="photo1">
                            <label for="photo1" style="cursor: pointer;">
                                <i class="fas fa-camera" style="font-size: 2rem; color: var(--text-muted); margin-bottom: 8px;"></i>
                                <div style="color: var(--text-muted);">Photo 1</div>
                            </label>
                        </div>
                        <div class="photo-upload">
                            <input type="file" name="uploaded_photo_2" accept="image/*" id="photo2">
                            <label for="photo2" style="cursor: pointer;">
                                <i class="fas fa-camera" style="font-size: 2rem; color: var(--text-muted); margin-bottom: 8px;"></i>
                                <div style="color: var(--text-muted);">Photo 2</div>
                            </label>
                        </div>
                        <div class="photo-upload">
                            <input type="file" name="uploaded_photo_3" accept="image/*" id="photo3">
                            <label for="photo3" style="cursor: pointer;">
                                <i class="fas fa-camera" style="font-size: 2rem; color: var(--text-muted); margin-bottom: 8px;"></i>
                                <div style="color: var(--text-muted);">Photo 3</div>
                            </label>
                        </div>
                    </div>
                </div>

                <div style="margin-top: 30px;">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-paper-plane"></i> Submit for LGU Approval
                    </button>
                </div>
            </form>
        </div>

        <!-- Previous Reports -->
        <div class="content-card">
            <h2 class="section-title">
                <i class="fas fa-history"></i> Your LGU Inspection Reports
            </h2>

            <?php if (empty($reports)): ?>
                <div style="text-align: center; padding: 40px; color: var(--text-muted);">
                    <i class="fas fa-inbox" style="font-size: 3rem; margin-bottom: 15px;"></i>
                    <p>No LGU inspection reports submitted yet.</p>
                </div>
            <?php else: ?>
                <table class="custom-table">
                    <thead>
                        <tr>
                            <th width="120">Report ID</th>
                            <th>Location</th>
                            <th width="150">Date</th>
                            <th width="150">Submitted</th>
                            <th width="120">Severity</th>
                            <th width="120">Status</th>
                            <th width="100">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($reports as $report): ?>
                            <tr>
                                <td style="font-weight: 700;"><?php echo $report['id']; ?></td>
                                <td><?php echo $report['location']; ?></td>
                                <td><?php echo $report['date']; ?></td>
                                <td><?php echo $report['submitted_date']; ?></td>
                                <td>
                                    <span class="severity-<?php echo strtolower($report['severity']); ?>">
                                        <?php echo $report['severity']; ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="badge badge-<?php echo str_replace('_', '', strtolower($report['status'])); ?>">
                                        <?php echo ucwords(str_replace('_', ' ', $report['status'])); ?>
                                    </span>
                                </td>
                                <td>
                                    <button class="btn-action" onclick='viewReport(<?php echo json_encode($report); ?>)'>
                                        <i class="fas fa-eye"></i> View
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </main>

    <script>
        function viewReport(report) {
            // Create a simple modal to display report details
            const modal = document.createElement('div');
            modal.style.cssText = `
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background: rgba(0,0,0,0.5);
                display: flex;
                align-items: center;
                justify-content: center;
                z-index: 9999;
            `;
            
            modal.innerHTML = `
                <div style="background: white; padding: 30px; border-radius: 15px; max-width: 600px; max-height: 80vh; overflow-y: auto;">
                    <h2 style="margin-bottom: 20px; color: #1e293b;">
                        <i class="fas fa-file-alt"></i> LGU Inspection Report
                    </h2>
                    <div style="margin-bottom: 15px;"><strong>Report ID:</strong> ${report.id}</div>
                    <div style="margin-bottom: 15px;"><strong>Location:</strong> ${report.location}</div>
                    <div style="margin-bottom: 15px;"><strong>Date:</strong> ${report.date}</div>
                    <div style="margin-bottom: 15px;"><strong>Severity:</strong> ${report.severity}</div>
                    <div style="margin-bottom: 15px;"><strong>Priority:</strong> ${report.priority}</div>
                    <div style="margin-bottom: 15px;"><strong>Estimated Cost:</strong> ${report.cost}</div>
                    <div style="margin-bottom: 15px;"><strong>Status:</strong> ${report.status}</div>
                    <div style="margin-bottom: 15px;"><strong>Submitted:</strong> ${report.submitted_date}</div>
                    ${report.review_date ? `<div style="margin-bottom: 15px;"><strong>Reviewed:</strong> ${report.review_date}</div>` : ''}
                    <div style="margin-bottom: 15px;"><strong>Description:</strong></div>
                    <div style="background: #f8fafc; padding: 15px; border-radius: 8px; margin-bottom: 15px;">${report.description}</div>
                    ${report.review_notes ? `
                        <div style="margin-bottom: 15px;"><strong>Review Notes:</strong></div>
                        <div style="background: #fef3c7; padding: 15px; border-radius: 8px; margin-bottom: 15px;">${report.review_notes}</div>
                    ` : ''}
                    <button onclick="this.parentElement.parentElement.remove()" style="background: #2563eb; color: white; border: none; padding: 10px 20px; border-radius: 8px; cursor: pointer;">
                        <i class="fas fa-times"></i> Close
                    </button>
                </div>
            `;
            
            document.body.appendChild(modal);
            modal.addEventListener('click', (e) => {
                if (e.target === modal) {
                    modal.remove();
                }
            });
        }
    </script>
</body>
</html>
