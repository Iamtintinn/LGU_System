<?php
// view_citizen_report.php - Read-only view of citizen reports for LGU officers
session_start();
require_once '../config/auth.php';
require_once '../config/database.php';

$auth->requireAnyRole(['lgu_officer', 'admin']);

// Get report ID from URL
$report_id = $_GET['id'] ?? '';

if (empty($report_id)) {
    header('Location: citizen_reports_view.php');
    exit;
}

try {
    $database = new Database();
    $conn = $database->getConnection();

    // Fetch report details
    $stmt = $conn->prepare("
        SELECT dr.*, 
               u.first_name as reporter_first_name, 
               u.last_name as reporter_last_name,
               u.email as reporter_email,
               u.phone as reporter_phone,
               CONCAT(ao.first_name, ' ', ao.last_name) as assigned_officer_name
        FROM damage_reports dr
        LEFT JOIN users u ON dr.reporter_id = u.id
        LEFT JOIN users ao ON dr.assigned_to = ao.id
        WHERE dr.report_id = ?
    ");
    $stmt->bind_param('s', $report_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        header('Location: citizen_reports_view.php?error=notfound');
        exit;
    }
    
    $report = $result->fetch_assoc();
    
    // Parse images
    $report['images'] = $report['images'] ? json_decode($report['images'], true) : [];
    
} catch (Exception $e) {
    header('Location: citizen_reports_view.php?error=database');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Report #<?php echo $report_id; ?> | LGU Officer</title>
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
            overflow-y: auto;
            z-index: 1;
        }

        .header {
            color: white;
            margin-bottom: 30px;
        }

        .header h1 {
            font-size: 2.2rem;
            font-weight: 700;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .header p {
            font-size: 1.1rem;
            opacity: 0.9;
        }

        .divider {
            border: none;
            height: 1px;
            background: rgba(255, 255, 255, 0.3);
            margin: 20px 0;
        }

        .report-container {
            background: var(--glass-bg);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            border: 1px solid var(--glass-border);
            padding: 30px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
            max-width: 900px;
        }

        .report-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 1px solid #e2e8f0;
        }

        .report-id {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--primary);
        }

        .status-badge {
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
            text-transform: uppercase;
        }

        .status-pending { background: #fef3c7; color: #d97706; }
        .status-under_review { background: #dbeafe; color: #2563eb; }
        .status-approved { background: #dcfce7; color: #16a34a; }
        .status-in_progress { background: #e9d5ff; color: #9333ea; }
        .status-completed { background: #d1fae5; color: #059669; }
        .status-rejected { background: #fee2e2; color: #dc2626; }

        .severity-badge {
            padding: 6px 12px;
            border-radius: 15px;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
            margin-left: 10px;
        }

        .severity-urgent { background: #fee2e2; color: var(--danger); }
        .severity-high { background: #fed7aa; color: #ea580c; }
        .severity-medium { background: #fef3c7; color: #d97706; }
        .severity-low { background: #dbeafe; color: #2563eb; }

        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 25px;
            margin-bottom: 30px;
        }

        .info-section {
            background: #f8fafc;
            border-radius: 12px;
            padding: 20px;
            border: 1px solid #e2e8f0;
        }

        .info-title {
            font-size: 1.1rem;
            font-weight: 600;
            color: var(--text-main);
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .info-title i {
            color: var(--primary);
        }

        .info-item {
            margin-bottom: 12px;
            display: flex;
            align-items: flex-start;
            gap: 10px;
        }

        .info-label {
            font-weight: 600;
            color: var(--text-muted);
            min-width: 120px;
            font-size: 0.9rem;
        }

        .info-value {
            color: var(--text-main);
            flex: 1;
        }

        .description-box {
            background: #f8fafc;
            border-radius: 12px;
            padding: 20px;
            border: 1px solid #e2e8f0;
            margin-bottom: 25px;
        }

        .description-text {
            line-height: 1.6;
            color: var(--text-main);
        }

        .images-section {
            margin-bottom: 25px;
        }

        .images-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
            gap: 15px;
            margin-top: 15px;
        }

        .image-card {
            border-radius: 10px;
            overflow: hidden;
            border: 1px solid #e2e8f0;
            cursor: pointer;
            transition: transform 0.2s;
        }

        .image-card:hover {
            transform: scale(1.05);
        }

        .image-card img {
            width: 100%;
            height: 150px;
            object-fit: cover;
        }

        .lgu-notes {
            background: #f0f9ff;
            border-radius: 12px;
            padding: 20px;
            border: 1px solid #bae6fd;
            margin-bottom: 25px;
        }

        .lgu-notes-title {
            font-weight: 600;
            color: #0369a1;
            margin-bottom: 10px;
        }

        .lgu-notes-text {
            color: #0c4a6e;
            line-height: 1.6;
        }

        .no-notes {
            color: var(--text-muted);
            font-style: italic;
        }

        .back-button {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            padding: 12px 24px;
            background: var(--primary);
            color: white;
            text-decoration: none;
            border-radius: 10px;
            font-weight: 600;
            transition: all 0.2s;
            margin-top: 20px;
        }

        .back-button:hover {
            background: var(--primary-hover);
            transform: translateY(-2px);
        }

        .no-images {
            color: var(--text-muted);
            font-style: italic;
            padding: 20px;
            text-align: center;
            background: #f8fafc;
            border-radius: 10px;
            border: 1px solid #e2e8f0;
        }

        .reporter-info {
            background: #fef3c7;
            border-radius: 12px;
            padding: 20px;
            border: 1px solid #fbbf24;
        }

        .anonymous-badge {
            background: #e5e7eb;
            color: #6b7280;
            padding: 4px 10px;
            border-radius: 15px;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
        }
    </style>
</head>
<body>
    <?php include '../sidebar/sidebar.php'; ?>

    <main class="main-content">
        <header class="header">
            <h1><i class="fas fa-file-alt"></i> Report Details</h1>
            <p>View detailed information about the citizen report</p>
            <hr class="divider">
        </header>

        <div class="report-container">
            <div class="report-header">
                <div>
                    <div class="report-id">Report #<?php echo htmlspecialchars($report['report_id']); ?></div>
                    <span class="severity-badge severity-<?php echo $report['severity']; ?>">
                        <?php echo htmlspecialchars($report['severity']); ?>
                    </span>
                </div>
                <div class="status-badge status-<?php echo $report['status']; ?>">
                    <?php echo str_replace('_', ' ', htmlspecialchars($report['status'])); ?>
                </div>
            </div>

            <div class="info-grid">
                <div class="info-section">
                    <div class="info-title">
                        <i class="fas fa-map-marker-alt"></i> Location Information
                    </div>
                    <div class="info-item">
                        <span class="info-label">Location:</span>
                        <span class="info-value"><?php echo htmlspecialchars($report['location']); ?></span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Barangay:</span>
                        <span class="info-value"><?php echo htmlspecialchars($report['barangay']); ?></span>
                    </div>
                    <?php if (!empty($report['estimated_size'])): ?>
                    <div class="info-item">
                        <span class="info-label">Estimated Size:</span>
                        <span class="info-value"><?php echo htmlspecialchars($report['estimated_size']); ?></span>
                    </div>
                    <?php endif; ?>
                    <div class="info-item">
                        <span class="info-label">Traffic Impact:</span>
                        <span class="info-value"><?php echo htmlspecialchars($report['traffic_impact']); ?></span>
                    </div>
                </div>

                <div class="info-section">
                    <div class="info-title">
                        <i class="fas fa-tools"></i> Damage Details
                    </div>
                    <div class="info-item">
                        <span class="info-label">Damage Type:</span>
                        <span class="info-value"><?php echo htmlspecialchars($report['damage_type']); ?></span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Severity:</span>
                        <span class="info-value"><?php echo htmlspecialchars($report['severity']); ?></span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Reported:</span>
                        <span class="info-value"><?php echo date('M j, Y g:i A', strtotime($report['reported_at'])); ?></span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Last Updated:</span>
                        <span class="info-value"><?php echo date('M j, Y g:i A', strtotime($report['updated_at'])); ?></span>
                    </div>
                </div>
            </div>

            <div class="description-box">
                <div class="info-title">
                    <i class="fas fa-align-left"></i> Description
                </div>
                <div class="description-text">
                    <?php echo nl2br(htmlspecialchars($report['description'])); ?>
                </div>
            </div>

            <?php if (!empty($report['images'])): ?>
            <div class="images-section">
                <div class="info-title">
                    <i class="fas fa-camera"></i> Evidence Photos
                </div>
                <div class="images-grid">
                    <?php foreach ($report['images'] as $image): ?>
                    <div class="image-card" onclick="window.open('../../uploads/reports/<?php echo htmlspecialchars($image); ?>', '_blank')">
                        <img src="../../uploads/reports/<?php echo htmlspecialchars($image); ?>" alt="Evidence Photo">
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php else: ?>
            <div class="images-section">
                <div class="info-title">
                    <i class="fas fa-camera"></i> Evidence Photos
                </div>
                <div class="no-images">
                    <i class="fas fa-image" style="font-size: 2rem; margin-bottom: 10px; opacity: 0.5;"></i>
                    <p>No photos uploaded with this report</p>
                </div>
            </div>
            <?php endif; ?>

            <?php if ($report['anonymous_report']): ?>
            <div class="reporter-info">
                <div class="info-title">
                    <i class="fas fa-user-secret"></i> Reporter Information
                </div>
                <div class="anonymous-badge">
                    <i class="fas fa-user-secret"></i> Anonymous Report
                </div>
                <p style="margin-top: 10px; color: #6b7280;">This report was submitted anonymously to protect the reporter's privacy.</p>
            </div>
            <?php else: ?>
            <div class="info-section">
                <div class="info-title">
                    <i class="fas fa-user"></i> Reporter Information
                </div>
                <div class="info-item">
                    <span class="info-label">Name:</span>
                    <span class="info-value">
                        <?php echo htmlspecialchars($report['reporter_first_name'] . ' ' . $report['reporter_last_name']); ?>
                    </span>
                </div>
                <div class="info-item">
                    <span class="info-label">Email:</span>
                    <span class="info-value"><?php echo htmlspecialchars($report['reporter_email']); ?></span>
                </div>
                <?php if (!empty($report['contact_number'])): ?>
                <div class="info-item">
                    <span class="info-label">Contact:</span>
                    <span class="info-value"><?php echo htmlspecialchars($report['contact_number']); ?></span>
                </div>
                <?php endif; ?>
                <?php if (!empty($report['reporter_phone'])): ?>
                <div class="info-item">
                    <span class="info-label">Phone:</span>
                    <span class="info-value"><?php echo htmlspecialchars($report['reporter_phone']); ?></span>
                </div>
                <?php endif; ?>
            </div>
            <?php endif; ?>

            <?php if (!empty($report['lgu_notes'])): ?>
            <div class="lgu-notes">
                <div class="lgu-notes-title">
                    <i class="fas fa-clipboard"></i> LGU Officer Notes
                </div>
                <div class="lgu-notes-text">
                    <?php echo nl2br(htmlspecialchars($report['lgu_notes'])); ?>
                </div>
            </div>
            <?php else: ?>
            <div class="lgu-notes">
                <div class="lgu-notes-title">
                    <i class="fas fa-clipboard"></i> LGU Officer Notes
                </div>
                <div class="no-notes">No notes added yet.</div>
            </div>
            <?php endif; ?>

            <?php if (!empty($report['assigned_officer_name'])): ?>
            <div class="info-section">
                <div class="info-title">
                    <i class="fas fa-user-tie"></i> Assigned Officer
                </div>
                <div class="info-item">
                    <span class="info-label">Officer:</span>
                    <span class="info-value"><?php echo htmlspecialchars($report['assigned_officer_name']); ?></span>
                </div>
            </div>
            <?php endif; ?>

            <a href="citizen_reports_view.php" class="back-button">
                <i class="fas fa-arrow-left"></i> Back to Reports
            </a>
        </div>
    </main>
</body>
</html>
