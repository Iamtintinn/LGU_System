<?php
// Inspection View Page - Engineer Module
// View detailed inspection information
session_start();
require_once '../config/auth.php';
require_once '../config/database.php';

// Require engineer role
$auth->requireRole('engineer');

// Get inspection ID from URL
$inspectionId = isset($_GET['id']) ? $_GET['id'] : '';

// Log page access
$auth->logActivity('page_access', 'Viewed inspection details for: ' . $inspectionId);

// Get inspection details (mock data for now)
$inspection = [
    'inspection_id' => $inspectionId,
    'location' => 'Market Street',
    'date' => 'Dec 11, 2025',
    'inspector' => 'Inspector Reyes',
    'description' => 'Minor crack in road surface approximately 1 meter long. No immediate danger but should be monitored.',
    'severity' => 'low',
    'status' => 'approved',
    'review_date' => 'Dec 12, 2025',
    'reviewed_by' => 'Engineer Cruz',
    'review_notes' => 'Approved for routine maintenance. Schedule for next maintenance cycle.',
    'priority' => 'low',
    'estimated_cost' => 5000.00,
    'photos' => ['crack1.jpg', 'crack2.jpg'],
    'estimated_damage' => 'Surface crack requiring sealant application'
];

// Get related repair task if exists
$repairTask = [
    'task_id' => 'REP-552',
    'assigned_to' => 'Maintenance Team A',
    'status' => 'pending',
    'created_date' => 'Dec 12, 2025',
    'estimated_completion' => 'Dec 20, 2025'
];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inspection View | Engineer Portal</title>
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

        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 500;
        }

        .status-approved {
            background: rgba(34, 197, 94, 0.1);
            color: #22c55e;
        }

        .status-pending {
            background: rgba(245, 158, 11, 0.1);
            color: #f59e0b;
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

        .btn-secondary {
            background: #6b7280;
            color: white;
        }

        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
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

        .repair-task-card {
            background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
            border-radius: 12px;
            padding: 20px;
            border-left: 4px solid var(--success);
        }

        .task-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }

        .task-id {
            font-size: 1.1rem;
            font-weight: 700;
            color: var(--text-main);
        }

        .task-status {
            background: rgba(34, 197, 94, 0.1);
            color: #22c55e;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
        }

        .task-details {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
        }

        .timeline-item {
            display: flex;
            gap: 15px;
            margin-bottom: 20px;
        }

        .timeline-date {
            background: var(--primary);
            color: white;
            padding: 8px 12px;
            border-radius: 8px;
            font-weight: 600;
            min-width: 120px;
            text-align: center;
        }

        .timeline-content {
            flex: 1;
            background: #f8fafc;
            padding: 15px;
            border-radius: 8px;
            border-left: 3px solid var(--primary);
        }

        .review-section {
            background: #f8fafc;
            padding: 20px;
            border-radius: 12px;
            border-left: 4px solid var(--success);
            margin-bottom: 30px;
        }

        .review-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }

        .review-title {
            font-size: 1.1rem;
            font-weight: 700;
            color: var(--text-main);
        }

        .review-date {
            color: var(--text-muted);
            font-size: 0.9rem;
        }
    </style>
</head>
<body>
    <?php include 'sidebar_engineer.php'; ?>

    <main class="main-content">
        <header class="module-header">
            <h1><i class="fas fa-eye"></i> Inspection View</h1>
            <div style="background: rgba(255,255,255,0.1); padding: 5px 15px; border-radius: 20px; color: #fff; font-size: 0.8rem; display: inline-block; margin-top: 5px;">
                <i class="fas fa-info-circle"></i> Detailed View
            </div>
            <p style="margin-top: 10px;">View comprehensive inspection information and status</p>
            <hr class="header-divider">
        </header>

        <div class="content-card">
            <h2 class="section-title">
                <i class="fas fa-file-alt"></i> Inspection Details
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
                    <div class="detail-label">Status</div>
                    <div class="detail-value">
                        <span class="status-badge status-<?php echo $inspection['status']; ?>">
                            <i class="fas fa-check-circle"></i>
                            <?php echo ucfirst($inspection['status']); ?>
                        </span>
                    </div>
                </div>
                <div class="detail-item">
                    <div class="detail-label">Priority</div>
                    <div class="detail-value severity-<?php echo $inspection['priority']; ?>">
                        <?php echo ucfirst($inspection['priority']); ?> Priority
                    </div>
                </div>
                <div class="detail-item">
                    <div class="detail-label">Estimated Cost</div>
                    <div class="detail-value">₱<?php echo number_format($inspection['estimated_cost'], 2); ?></div>
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

            <?php if ($inspection['status'] === 'approved'): ?>
                <div class="review-section">
                    <div class="review-header">
                        <div class="review-title">
                            <i class="fas fa-clipboard-check" style="color: var(--success); margin-right: 8px;"></i>
                            Review Information
                        </div>
                        <div class="review-date">Reviewed on <?php echo htmlspecialchars($inspection['review_date']); ?></div>
                    </div>
                    <div style="margin-bottom: 15px;">
                        <strong>Reviewed by:</strong> <?php echo htmlspecialchars($inspection['reviewed_by']); ?>
                    </div>
                    <div>
                        <strong>Review Notes:</strong>
                        <p style="margin-top: 8px; color: var(--text-muted); line-height: 1.6;">
                            <?php echo htmlspecialchars($inspection['review_notes']); ?>
                        </p>
                    </div>
                </div>
            <?php endif; ?>

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

            <?php if ($repairTask): ?>
                <div style="margin-bottom: 30px;">
                    <h3 style="margin-bottom: 15px; color: var(--text-main);">
                        <i class="fas fa-tools" style="color: var(--success); margin-right: 8px;"></i>
                        Related Repair Task
                    </h3>
                    <div class="repair-task-card">
                        <div class="task-header">
                            <div class="task-id"><?php echo htmlspecialchars($repairTask['task_id']); ?></div>
                            <div class="task-status">
                                <i class="fas fa-clock"></i>
                                <?php echo ucfirst($repairTask['status']); ?>
                            </div>
                        </div>
                        <div class="task-details">
                            <div>
                                <div style="font-size: 0.85rem; color: var(--text-muted); margin-bottom: 5px;">Assigned To</div>
                                <div style="font-weight: 600;"><?php echo htmlspecialchars($repairTask['assigned_to']); ?></div>
                            </div>
                            <div>
                                <div style="font-size: 0.85rem; color: var(--text-muted); margin-bottom: 5px;">Created Date</div>
                                <div style="font-weight: 600;"><?php echo htmlspecialchars($repairTask['created_date']); ?></div>
                            </div>
                            <div>
                                <div style="font-size: 0.85rem; color: var(--text-muted); margin-bottom: 5px;">Est. Completion</div>
                                <div style="font-weight: 600;"><?php echo htmlspecialchars($repairTask['estimated_completion']); ?></div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <div>
                <h3 style="margin-bottom: 15px; color: var(--text-main);">
                    <i class="fas fa-history" style="color: var(--primary); margin-right: 8px;"></i>
                    Inspection Timeline
                </h3>
                <div class="timeline-item">
                    <div class="timeline-date">
                        <?php echo htmlspecialchars($inspection['date']); ?>
                    </div>
                    <div class="timeline-content">
                        <strong>Inspection Conducted</strong>
                        <p style="margin-top: 5px; color: var(--text-muted);">
                            Inspection completed by <?php echo htmlspecialchars($inspection['inspector']); ?> at <?php echo htmlspecialchars($inspection['location']); ?>
                        </p>
                    </div>
                </div>
                <?php if ($inspection['status'] === 'approved'): ?>
                    <div class="timeline-item">
                        <div class="timeline-date">
                            <?php echo htmlspecialchars($inspection['review_date']); ?>
                        </div>
                        <div class="timeline-content">
                            <strong>Inspection Approved</strong>
                            <p style="margin-top: 5px; color: var(--text-muted);">
                                Approved by <?php echo htmlspecialchars($inspection['reviewed_by']); ?> with priority level: <?php echo ucfirst($inspection['priority']); ?>
                            </p>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Action Buttons -->
            <div style="display: flex; gap: 15px; margin-top: 30px; justify-content: center; align-items: center;">
                <a href="inspection_workflow.php" class="btn btn-primary" style="min-width: 180px !important; max-width: 180px !important; padding: 12px 20px !important; text-align: center !important; display: inline-flex !important; align-items: center !important; justify-content: center !important;">
                    <i class="fas fa-arrow-left"></i> Back to Workflow
                </a>
                <a href="edit_inspection.php?id=<?php echo htmlspecialchars($inspection['inspection_id']); ?>" class="btn btn-secondary" style="min-width: 180px !important; max-width: 180px !important; padding: 12px 20px !important; text-align: center !important; display: inline-flex !important; align-items: center !important; justify-content: center !important;">
                    <i class="fas fa-edit"></i> Edit Inspection
                </a>
            </div>
        </div>
    </main>
</body>
</html>
