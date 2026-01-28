<?php
// Inspection Review Page - Engineer Module
// Review and approve pending inspections
session_start();
require_once '../config/auth.php';
require_once '../config/database.php';

// Require engineer role
$auth->requireRole('engineer');

// Get inspection ID from URL
$inspectionId = isset($_GET['id']) ? $_GET['id'] : '';

// Log page access
$auth->logActivity('page_access', 'Accessed inspection review page for: ' . $inspectionId);

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $database = new Database();
    $conn = $database->getConnection();
    
    $action = $_POST['action'];
    $inspectionId = $_POST['inspection_id'];
    $notes = $_POST['notes'] ?? '';
    $priority = $_POST['priority'] ?? 'medium';
    $estimatedCost = $_POST['estimated_cost'] ?? 0;
    
    if ($action === 'approve') {
        // Update inspection status to approved
        $stmt = $conn->prepare("
            UPDATE inspections 
            SET status = 'approved', 
                reviewed_by = ?, 
                review_date = NOW(),
                review_notes = ?,
                priority = ?,
                estimated_cost = ?
            WHERE inspection_id = ?
        ");
        $stmt->bind_param("sssis", $_SESSION['user_id'], $notes, $priority, $estimatedCost, $inspectionId);
        
        if ($stmt->execute()) {
            // Create repair task
            $taskId = 'REP-' . str_pad(mt_rand(1, 9999), 4, '0', STR_PAD_LEFT);
            $taskStmt = $conn->prepare("
                INSERT INTO repair_tasks (task_id, inspection_id, assigned_to, status, priority, estimated_cost, created_date)
                VALUES (?, ?, ?, 'pending', ?, ?, NOW())
            ");
            $taskStmt->bind_param("ssssd", $taskId, $inspectionId, $_SESSION['user_id'], $priority, $estimatedCost);
            $taskStmt->execute();
            
            $_SESSION['success'] = "Inspection approved and repair task created successfully!";
        } else {
            $_SESSION['error'] = "Failed to approve inspection: " . $conn->error;
        }
    } elseif ($action === 'reject') {
        // Update inspection status to rejected
        $stmt = $conn->prepare("
            UPDATE inspections 
            SET status = 'rejected', 
                reviewed_by = ?, 
                review_date = NOW(),
                review_notes = ?
            WHERE inspection_id = ?
        ");
        $stmt->bind_param("sss", $_SESSION['user_id'], $notes, $inspectionId);
        
        if ($stmt->execute()) {
            $_SESSION['success'] = "Inspection rejected successfully!";
        } else {
            $_SESSION['error'] = "Failed to reject inspection: " . $conn->error;
        }
    }
    
    // Redirect to prevent form resubmission
    header("Location: inspection_workflow.php");
    exit;
}

// Get inspection details (mock data for now)
$inspection = [
    'inspection_id' => $inspectionId,
    'location' => 'Main Road, Brgy. 3',
    'date' => 'Dec 10, 2025',
    'inspector' => 'Inspector Santos',
    'description' => 'Large pothole approximately 2 feet in diameter causing traffic hazards. Immediate repair recommended.',
    'severity' => 'high',
    'photos' => ['pothole1.jpg', 'pothole2.jpg'],
    'estimated_damage' => 'Road surface damage requiring asphalt patching'
];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inspection Review | Engineer Portal</title>
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
        }

        .inspection-details {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .detail-item {
            background: #f8fafc;
            padding: 15px;
            border-radius: 8px;
            border-left: 4px solid var(--primary);
        }

        .detail-label {
            font-size: 0.85rem;
            color: var(--text-muted);
            margin-bottom: 5px;
        }

        .detail-value {
            font-size: 1rem;
            font-weight: 600;
            color: var(--text-main);
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

        .btn-success {
            background: var(--success);
            color: white;
        }

        .btn-danger {
            background: var(--danger);
            color: white;
        }

        .btn-secondary {
            background: #6b7280;
            color: white;
        }

        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        }

        .action-buttons {
            display: flex;
            gap: 15px;
            justify-content: flex-end;
            margin-top: 30px;
        }

        .photos-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-top: 15px;
        }

        .photo-item {
            background: #f8fafc;
            border-radius: 8px;
            padding: 10px;
            text-align: center;
            border: 1px solid #e2e8f0;
        }

        .photo-placeholder {
            width: 100%;
            height: 120px;
            background: #e2e8f0;
            border-radius: 4px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--text-muted);
            margin-bottom: 8px;
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
    </style>
</head>
<body>
    <?php include 'sidebar_engineer.php'; ?>

    <main class="main-content">
        <header class="module-header">
            <h1><i class="fas fa-clipboard-check"></i> Inspection Review</h1>
            <div style="background: rgba(255,255,255,0.1); padding: 5px 15px; border-radius: 20px; color: #fff; font-size: 0.8rem; display: inline-block; margin-top: 5px;">
                <i class="fas fa-search"></i> Review Process
            </div>
            <p style="margin-top: 10px;">Review and approve pending inspection reports</p>
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

        <div class="content-card">
            <h2 class="section-title">
                <i class="fas fa-file-alt"></i> Inspection Details
                <a href="inspection_workflow.php" class="btn btn-secondary" style="margin-left: auto; font-size: 0.8rem;">
                    <i class="fas fa-arrow-left"></i> Back to Workflow
                </a>
            </h2>

            <div class="inspection-details">
                <div class="detail-item">
                    <div class="detail-label">Inspection ID</div>
                    <div class="detail-value"><?php echo htmlspecialchars($inspection['inspection_id']); ?></div>
                </div>
                <div class="detail-item">
                    <div class="detail-label">Location</div>
                    <div class="detail-value"><?php echo htmlspecialchars($inspection['location']); ?></div>
                </div>
                <div class="detail-item">
                    <div class="detail-label">Inspection Date</div>
                    <div class="detail-value"><?php echo htmlspecialchars($inspection['date']); ?></div>
                </div>
                <div class="detail-item">
                    <div class="detail-label">Inspector</div>
                    <div class="detail-value"><?php echo htmlspecialchars($inspection['inspector']); ?></div>
                </div>
                <div class="detail-item">
                    <div class="detail-label">Severity</div>
                    <div class="detail-value severity-<?php echo $inspection['severity']; ?>">
                        <?php echo ucfirst($inspection['severity']); ?> Priority
                    </div>
                </div>
                <div class="detail-item">
                    <div class="detail-label">Estimated Damage</div>
                    <div class="detail-value"><?php echo htmlspecialchars($inspection['estimated_damage']); ?></div>
                </div>
            </div>

            <div style="margin-bottom: 30px;">
                <h3 style="margin-bottom: 15px; color: var(--text-main);">Description</h3>
                <p style="color: var(--text-muted); line-height: 1.6;">
                    <?php echo htmlspecialchars($inspection['description']); ?>
                </p>
            </div>

            <div style="margin-bottom: 30px;">
                <h3 style="margin-bottom: 15px; color: var(--text-main);">Inspection Photos</h3>
                <div class="photos-grid">
                    <?php foreach ($inspection['photos'] as $photo): ?>
                        <div class="photo-item">
                            <div class="photo-placeholder">
                                <i class="fas fa-image" style="font-size: 2rem;"></i>
                            </div>
                            <small style="color: var(--text-muted);"><?php echo htmlspecialchars($photo); ?></small>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <form method="POST">
                <input type="hidden" name="inspection_id" value="<?php echo htmlspecialchars($inspectionId); ?>">
                
                <div class="form-group">
                    <label class="form-label">Priority Assessment</label>
                    <select name="priority" class="form-control" required>
                        <option value="low">Low Priority</option>
                        <option value="medium" selected>Medium Priority</option>
                        <option value="high">High Priority</option>
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label">Estimated Repair Cost (₱)</label>
                    <input type="number" name="estimated_cost" class="form-control" placeholder="0.00" step="0.01" min="0">
                </div>

                <div class="form-group">
                    <label class="form-label">Review Notes</label>
                    <textarea name="notes" class="form-control" placeholder="Add your review notes, recommendations, or additional observations..." required></textarea>
                </div>

                <div class="action-buttons">
                    <button type="submit" name="action" value="reject" class="btn btn-danger">
                        <i class="fas fa-times"></i> Reject Inspection
                    </button>
                    <button type="submit" name="action" value="approve" class="btn btn-success">
                        <i class="fas fa-check"></i> Approve & Create Task
                    </button>
                </div>
            </form>
        </div>
    </main>
</body>
</html>
