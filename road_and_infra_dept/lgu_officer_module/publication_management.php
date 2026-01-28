<?php
// Publication Management - LGU Officer Module
// Manages publishing of verified completed road issues for public viewing
session_start();
require_once '../config/auth.php';
require_once '../config/database.php';

// Require LGU officer role
$auth->requireAnyRole(['lgu_officer', 'admin']);

// Handle AJAX request for publication details (Preview)
if (isset($_GET['ajax_get_details'])) {
    $database = new Database();
    $conn = $database->getConnection();
    $id = (int)$_GET['ajax_get_details'];
    $type = isset($_GET['type']) ? $_GET['type'] : 'publication';
    
    if ($type === 'raw_report') {
        // Fetch from damage_reports
        $stmt = $conn->prepare("
            SELECT dr.*, u.first_name, u.last_name, dr.location as road_name, dr.description as issue_summary, 
                   'Damage Report' as issue_type, dr.severity as severity_public, dr.status as status_public,
                   dr.report_id as publication_id, dr.reported_at as date_reported
            FROM damage_reports dr
            LEFT JOIN users u ON dr.reporter_id = u.id
            WHERE dr.id = ?
        ");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        $data = $result->fetch_assoc();
        if ($data) {
            $data['approval_status'] = 'READY';
            $data['publication_date'] = $data['updated_at'];
            $data['repair_start_date'] = null;
            $data['completion_date'] = null;
            $data['item_type'] = 'raw_report';
        }
    } else {
        // Fetch from public_publications
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
            $data['item_type'] = 'publication';
        }
    }
    
    if ($data) {
        $data['formatted_date'] = date('M j, Y', strtotime($data['publication_date']));
        $data['formatted_reported'] = date('M j, Y', strtotime($data['date_reported']));
        $data['formatted_start'] = ($data['repair_start_date'] && $data['repair_start_date'] != '0000-00-00') ? date('M j, Y', strtotime($data['repair_start_date'])) : 'TBD';
        $data['formatted_completion'] = ($data['completion_date'] && $data['completion_date'] != '0000-00-00') ? date('M j, Y', strtotime($data['completion_date'])) : 'TBD';
        
        header('Content-Type: application/json');
        echo json_encode($data);
    } else {
        http_response_code(404);
        echo json_encode(['error' => 'Item not found']);
    }
    exit;
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $database = new Database();
    $conn = $database->getConnection();
    
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'publish_report':
                // Publish a damage report for public viewing
                $damageReportId = (int)$_POST['damage_report_id'];
                $roadName = $_POST['road_name'];
                $issueSummary = $_POST['issue_summary'];
                $issueType = $_POST['issue_type'];
                $severityPublic = $_POST['severity_public'];
                $statusPublic = $_POST['status_public'];
                $dateReported = $_POST['date_reported'];
                $repairStartDate = $_POST['repair_start_date'] ?: null;
                $completionDate = $_POST['completion_date'] ?: null;
                
                // Generate publication ID
                $publicationId = 'PUB-' . date('Y') . '-' . str_pad(mt_rand(1, 999), 3, '0', STR_PAD_LEFT);
                
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
                        publication_id, damage_report_id, road_name, issue_summary, issue_type,
                        severity_public, status_public, date_reported, repair_start_date,
                        completion_date, repair_duration_days, is_published, publication_date, published_by
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1, NOW(), ?)
                ");
                
                $stmt->bind_param("sissssssssii", 
                    $publicationId, $damageReportId, $roadName, $issueSummary, $issueType,
                    $severityPublic, $statusPublic, $dateReported, $repairStartDate,
                    $completionDate, $repairDuration, $_SESSION['user_id']
                );
                
                if ($stmt->execute()) {
                    // Update damage report publication status
                    $updateDR = $conn->prepare("UPDATE damage_reports SET publication_status = 'published' WHERE id = ?");
                    $updateDR->bind_param("i", $damageReportId);
                    $updateDR->execute();

                    // Log activity
                    $auth->logActivity('report_publication', "Published road issue: $publicationId");
                    
                    // Add initial progress entry
                    $newPublicationId = $conn->insert_id;
                    $currentDate = date('Y-m-d');
                    $progressStmt = $conn->prepare("
                        INSERT INTO publication_progress (publication_id, progress_date, status, description, created_by)
                        VALUES (?, ?, 'published', 'Published for public viewing', ?)
                    ");
                    $progressStmt->bind_param("isi", $newPublicationId, $currentDate, $_SESSION['user_id']);
                    $progressStmt->execute();
                    
                    // Create notification for engineers
                    $notificationStmt = $conn->prepare("
                        INSERT INTO notifications (user_id, type, title, message, related_id, created_at)
                        SELECT u.id, 'publication', 'Report Published', ?, ?, NOW()
                        FROM users u
                        WHERE u.role = 'engineer'
                    ");
                    $notificationMessage = "Report published: {$issueSummary} for {$roadName}";
                    $notificationStmt->bind_param("si", $notificationMessage, $newPublicationId);
                    $notificationStmt->execute();
                    
                    $_SESSION['success'] = "Report published successfully!";
                } else {
                    $_SESSION['error'] = "Failed to publish report: " . $conn->error;
                }
                break;
                
            case 'publish_all_pending':
                // Publish all pending damage reports
                $stmt = $conn->prepare("
                    SELECT dr.* 
                    FROM damage_reports dr 
                    LEFT JOIN public_publications pp ON dr.id = pp.damage_report_id 
                    WHERE pp.id IS NULL AND dr.status = 'confirmed' AND dr.publication_status = 'pending'
                ");
                $stmt->execute();
                $pendingToPublish = $stmt->get_result();
                
                $publishedCount = 0;
                while($report = $pendingToPublish->fetch_assoc()) {
                    $pubId = 'PUB-' . date('Y') . '-' . str_pad(mt_rand(1, 999), 3, '0', STR_PAD_LEFT);
                    
                    $pubStmt = $conn->prepare("
                        INSERT INTO public_publications (
                            publication_id, damage_report_id, road_name, issue_summary, issue_type,
                            severity_public, status_public, date_reported, is_published, publication_date, published_by
                        ) VALUES (?, ?, ?, ?, ?, ?, 'reported', ?, 1, NOW(), ?)
                    ");
                    
                    // Simple defaults for bulk publishing
                    $severityMap = ['low'=>'low', 'medium'=>'medium', 'high'=>'high', 'critical'=>'high'];
                    $sev = (isset($report['severity']) && isset($severityMap[$report['severity']])) ? $severityMap[$report['severity']] : 'medium';
                    $cat = isset($report['category']) ? $report['category'] : 'other';
                    
                    $pubStmt->bind_param("sissssii", 
                        $pubId, $report['id'], $report['location'], $report['description'], 
                        $cat, $sev, $report['date_reported'], $_SESSION['user_id']
                    );
                    
                    if ($pubStmt->execute()) {
                        // Update damage report status
                        $updateDR = $conn->prepare("UPDATE damage_reports SET publication_status = 'published' WHERE id = ?");
                        $updateDR->bind_param("i", $report['id']);
                        $updateDR->execute();
                        $publishedCount++;
                    }
                }
                
                if ($publishedCount > 0) {
                    $auth->logActivity('bulk_publication', "Published $publishedCount reports in bulk");
                    $_SESSION['success'] = "Successfully published $publishedCount reports!";
                } else {
                    $_SESSION['error'] = "No reports were published.";
                }
                break;
                
                
            case 'update_publication':
                // Update existing publication
                $publicationId = (int)$_POST['publication_id'];
                $statusPublic = $_POST['status_public'];
                $completionDate = $_POST['completion_date'] ?: null;
                
                // Update publication
                $stmt = $conn->prepare("
                    UPDATE public_publications 
                    SET status_public = ?, completion_date = ?, last_updated = NOW()
                    WHERE id = ?
                ");
                
                $stmt->bind_param("ssi", $statusPublic, $completionDate, $publicationId);
                
                if ($stmt->execute()) {
                    // Add progress entry
                    $progressStmt = $conn->prepare("
                        INSERT INTO publication_progress (publication_id, progress_date, status, description, created_by)
                        VALUES (?, ?, ?, ?, ?)
                    ");
                    $description = "Status updated to: " . ucfirst(str_replace('_', ' ', $statusPublic));
                    $currentDate = date('Y-m-d');
                    $progressStmt->bind_param("isssi", $publicationId, $currentDate, $statusPublic, $description, $_SESSION['user_id']);
                    $progressStmt->execute();
                    
                    $auth->logActivity('publication_update', "Updated publication: $publicationId");
                    $_SESSION['success'] = "Publication updated successfully!";
                } else {
                    $_SESSION['error'] = "Failed to update publication: " . $conn->error;
                }
                break;
                
            case 'archive_publication':
                // Archive publication (remove from public view)
                $publicationId = (int)$_POST['publication_id'];
                $archiveReason = $_POST['archive_reason'];
                
                $stmt = $conn->prepare("
                    UPDATE public_publications 
                    SET archived = 1, archive_reason = ?, is_published = 0, last_updated = NOW()
                    WHERE id = ?
                ");
                
                $stmt->bind_param("si", $archiveReason, $publicationId);
                
                if ($stmt->execute()) {
                    $auth->logActivity('publication_archive', "Archived publication: $publicationId");
                    $_SESSION['success'] = "Publication archived successfully!";
                } else {
                    $_SESSION['error'] = "Failed to archive publication: " . $conn->error;
                }
                break;
                
            case 'approve_publication':
                // Approve publication proposal from engineer
                $publication_id = (int)$_POST['publication_id'];
                
                $stmt = $conn->prepare("
                    UPDATE public_publications 
                    SET approval_status = 'approved', is_published = 1, last_updated = NOW() 
                    WHERE id = ?
                ");
                $stmt->bind_param("i", $publication_id);
                
                if ($stmt->execute()) {
                    // Log activity
                    $auth->logActivity('publication_approval', "Approved publication: $publication_id");
                    
                    // Add progress entry
                    $progressStmt = $conn->prepare("
                        INSERT INTO publication_progress (publication_id, progress_date, status, description, created_by)
                        VALUES (?, ?, 'published', 'Proposal approved and published by LGU Officer', ?)
                    ");
                    $currentDate = date('Y-m-d');
                    $progressStmt->bind_param("isi", $publication_id, $currentDate, $_SESSION['user_id']);
                    $progressStmt->execute();
                    
                    // Notify the engineer who proposed it
                    $notifyStmt = $conn->prepare("
                        INSERT INTO notifications (user_id, type, title, message, related_id, created_at)
                        SELECT published_by, 'success', 'Proposal Approved', 'Your publication proposal has been approved and is now public.', ?, NOW()
                        FROM public_publications WHERE id = ?
                    ");
                    $notifyStmt->bind_param("ii", $publication_id, $publication_id);
                    $notifyStmt->execute();
                    
                    $_SESSION['success'] = "Publication approved and published successfully!";
                } else {
                    $_SESSION['error'] = "Failed to approve publication: " . $conn->error;
                }
                break;
                
            case 'reject_publication':
                // Reject publication proposal from engineer
                $publication_id = (int)$_POST['publication_id'];
                $reject_reason = $_POST['reject_reason'] ?: 'No reason provided';
                
                $stmt = $conn->prepare("
                    UPDATE public_publications 
                    SET approval_status = 'rejected', is_published = 0, last_updated = NOW() 
                    WHERE id = ?
                ");
                $stmt->bind_param("i", $publication_id);
                
                if ($stmt->execute()) {
                    // Log activity
                    $auth->logActivity('publication_rejection', "Rejected publication: $publication_id");
                    
                    // Add progress entry
                    $progressStmt = $conn->prepare("
                        INSERT INTO publication_progress (publication_id, progress_date, status, description, created_by)
                        VALUES (?, ?, 'reported', ?, ?)
                    ");
                    $currentDate = date('Y-m-d');
                    $desc = "Proposal rejected: " . $reject_reason;
                    $progressStmt->bind_param("issi", $publication_id, $currentDate, $desc, $_SESSION['user_id']);
                    $progressStmt->execute();
                    
                    // Notify the engineer
                    $notifyStmt = $conn->prepare("
                        INSERT INTO notifications (user_id, type, title, message, related_id, created_at)
                        SELECT published_by, 'error', 'Proposal Rejected', ?, ?, NOW()
                        FROM public_publications WHERE id = ?
                    ");
                    $message = "Your publication proposal was rejected. Reason: " . $reject_reason;
                    $notifyStmt->bind_param("sii", $message, $publication_id, $publication_id);
                    $notifyStmt->execute();
                    
                    $_SESSION['success'] = "Publication proposal rejected.";
                } else {
                    $_SESSION['error'] = "Failed to reject publication: " . $conn->error;
                }
                break;
                
            case 'request_revision':
                // Request revision for publication proposal
                $publication_id = (int)$_POST['publication_id'];
                $revision_notes = $_POST['revision_notes'] ?: 'No details provided';
                
                $stmt = $conn->prepare("
                    UPDATE public_publications 
                    SET approval_status = 'needs_revision', is_published = 0, last_updated = NOW() 
                    WHERE id = ?
                ");
                $stmt->bind_param("i", $publication_id);
                
                if ($stmt->execute()) {
                    // Log activity
                    $auth->logActivity('publication_revision_request', "Requested revision: $publication_id");
                    
                    // Add progress entry
                    $progressStmt = $conn->prepare("
                        INSERT INTO publication_progress (publication_id, progress_date, status, description, created_by)
                        VALUES (?, ?, 'reported', ?, ?)
                    ");
                    $currentDate = date('Y-m-d');
                    $desc = "Revision requested: " . $revision_notes;
                    $progressStmt->bind_param("issi", $publication_id, $currentDate, $desc, $_SESSION['user_id']);
                    $progressStmt->execute();
                    
                    // Notify the engineer
                    $notifyStmt = $conn->prepare("
                        INSERT INTO notifications (user_id, type, title, message, related_id, created_at)
                        SELECT published_by, 'warning', 'Revision Requested', ?, ?, NOW()
                        FROM public_publications WHERE id = ?
                    ");
                    $message = "Your publication proposal needs revision. Feedback: " . $revision_notes;
                    $notifyStmt->bind_param("sii", $message, $publication_id, $publication_id);
                    $notifyStmt->execute();
                    
                    $_SESSION['success'] = "Revision request sent to engineer.";
                } else {
                    $_SESSION['error'] = "Failed to request revision: " . $conn->error;
                }
                break;
                
            case 'decline_pending_report':
                // Stop a damage report from being published
                $reportId = (int)$_POST['report_id'];
                
                $stmt = $conn->prepare("UPDATE damage_reports SET publication_status = 'declined' WHERE id = ?");
                $stmt->bind_param("i", $reportId);
                
                if ($stmt->execute()) {
                    $auth->logActivity('decline_publication_report', "Declined publishing damage report ID: $reportId");
                    $_SESSION['success'] = "Report removed from publication queue.";
                } else {
                    $_SESSION['error'] = "Failed to decline report: " . $conn->error;
                }
                break;
        }
        
        // Redirect to prevent form resubmission
        header("Location: " . $_SERVER['PHP_SELF']);
        exit;
    }
}

// Get data for display
$database = new Database();
$conn = $database->getConnection();

// Get pending damage reports that can be published
$pendingReports = [];
$stmt = $conn->prepare("
    SELECT dr.*, u.first_name, u.last_name 
    FROM damage_reports dr
    LEFT JOIN users u ON dr.reporter_id = u.id
    WHERE dr.status IN ('resolved', 'closed') 
    AND dr.id NOT IN (SELECT damage_report_id FROM public_publications WHERE archived = 0)
    AND dr.publication_status = 'pending'
    ORDER BY dr.updated_at DESC
");
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $pendingReports[] = $row;
}

// 1. Get unarchived publications and proposals
$stmt = $conn->prepare("
    SELECT pp.*, dr.report_id as internal_report_id, dr.severity as internal_severity, u.first_name, u.last_name,
           (SELECT CONCAT(u2.first_name, ' ', u2.last_name) FROM users u2 WHERE u2.id = pp.published_by) as engineer_name,
           'publication' as item_origin
    FROM public_publications pp
    LEFT JOIN damage_reports dr ON pp.damage_report_id = dr.id
    LEFT JOIN users u ON pp.published_by = u.id
    WHERE pp.archived = 0
");
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $allPublications[] = $row;
}

// 2. Get pending damage reports that are "ready" but not yet in public_publications
$stmt = $conn->prepare("
    SELECT dr.id, dr.report_id as publication_id, dr.location as road_name, dr.description as issue_summary, 
           'Damage Report' as issue_type, dr.severity as severity_public, dr.status as status_public, 
           dr.reported_at as date_reported, dr.updated_at as publication_date, 'READY' as approval_status,
           u.first_name, u.last_name, 'raw_report' as item_origin
    FROM damage_reports dr
    LEFT JOIN users u ON dr.reporter_id = u.id
    WHERE dr.status IN ('resolved', 'closed') 
    AND dr.id NOT IN (SELECT damage_report_id FROM public_publications WHERE damage_report_id IS NOT NULL AND archived = 0)
    AND dr.publication_status = 'pending'
");
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $allPublications[] = $row;
}

// Sort all by date descending
usort($allPublications, function($a, $b) {
    return strtotime($b['publication_date']) - strtotime($a['publication_date']);
});

// Get Statistics for Proposals (needed for summary cards)
$stmt = $conn->prepare("
    SELECT 
        SUM(CASE WHEN approval_status = 'pending' THEN 1 ELSE 0 END) as pending,
        SUM(CASE WHEN approval_status = 'approved' AND damage_report_id IS NULL THEN 1 ELSE 0 END) as approved_proposals,
        SUM(CASE WHEN approval_status = 'rejected' THEN 1 ELSE 0 END) as rejected,
        SUM(CASE WHEN approval_status = 'needs_revision' THEN 1 ELSE 0 END) as revision
    FROM public_publications 
    WHERE archived = 0
");
$stmt->execute();
$proposalStats = $stmt->get_result()->fetch_assoc();

// Get statistics
$stats = [
    'active_announcements' => 0,
    'engineer_proposals' => 0,
    'ready_to_publish' => 0,
    'completed_public' => 0,
    'under_repair_public' => 0
];

foreach ($allPublications as $report) {
    if ($report['approval_status'] === 'approved') {
        $stats['active_announcements']++;
        if ($report['status_public'] === 'completed' || $report['status_public'] === 'fixed') {
            $stats['completed_public']++;
        } elseif ($report['status_public'] === 'under_repair') {
            $stats['under_repair_public']++;
        }
    } elseif ($report['approval_status'] === 'READY') {
        $stats['ready_to_publish']++;
    } elseif ($report['approval_status'] === 'pending' || $report['approval_status'] === 'needs_revision') {
        $stats['engineer_proposals']++;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Publication Management | LGU Officer</title>
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
            padding: 40px 60px;
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
        }

        .module-header p {
            font-size: 1rem;
            opacity: 0.9;
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

        .stat-icon.pending { background: var(--warning); }
        .stat-icon.published { background: var(--primary); }
        .stat-icon.completed { background: var(--success); }
        .stat-icon.repair { background: var(--danger); }

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

        .btn-success {
            background: var(--success);
            color: white;
        }

        .btn-warning {
            background: var(--warning);
            color: white;
        }

        .btn-danger {
            background: var(--danger);
            color: white;
        }

        .btn-sm {
            padding: 6px 12px;
            font-size: 0.8rem;
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
        }

        .modal-content {
            background: white;
            margin: 5% auto;
            padding: 30px;
            border-radius: 16px;
            width: 90%;
            max-width: 600px;
            max-height: 80vh;
            overflow-y: auto;
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .modal-title {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--text-main);
        }

        .close {
            font-size: 1.5rem;
            cursor: pointer;
            color: var(--text-muted);
            background: none;
            border: none;
        }

        .close:hover {
            color: var(--text-main);
        }

        /* Status Badge */
        .status-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
        }

        .status-pending { background: #fef3c7; color: #92400e; }
        .status-published { background: #dbeafe; color: #1e40af; }
        .status-completed { background: #dcfce7; color: #166534; }
        .status-under-repair { background: #fef3c7; color: #92400e; }
        .status-fixed { background: #dcfce7; color: #166534; }
        .status-ready { background: #fee2e2; color: #991b1b; border: 1px solid #fecaca; }

        .proposal-item:hover {
            box-shadow: 0 4px 12px rgba(0,0,0,0.05);
            border-color: var(--primary);
        }

        .proposal-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 15px;
        }

        .proposal-info h4 {
            margin-bottom: 5px;
            color: var(--text-main);
        }

        .proposal-meta {
            font-size: 0.85rem;
            color: var(--text-muted);
            display: flex;
            gap: 15px;
        }

        .proposal-actions {
            display: flex;
            gap: 10px;
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


        /* Proposal Stats Mini Grid */
        .proposal-stats-mini {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
            gap: 15px;
            margin-bottom: 25px;
        }

        .stat-mini-card {
            background: #f8fafc;
            padding: 15px;
            border-radius: 12px;
            border: 1px solid #e2e8f0;
            text-align: center;
        }

        .stat-mini-val {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--text-main);
            display: block;
        }

        .stat-mini-label {
            font-size: 0.75rem;
            color: var(--text-muted);
            text-transform: uppercase;
            font-weight: 600;
        }

        /* Publication Detail Modal (Reuse from Eng) */
        .modal-body-content {
            padding: 10px;
        }

        .preview-box {
            border: 2px solid #3b82f6;
            border-radius: 12px;
            padding: 2px;
            position: relative;
        }

        .preview-label {
            position: absolute;
            top: -12px;
            left: 20px;
            background: #3b82f6;
            color: white;
            padding: 2px 12px;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 700;
            z-index: 5;
        }

        .status-needs-revision { background: #fee2e2; color: #991b1b; }
        .approval-needs-revision { background: #fef3c7; color: #92400e; }

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
    <?php include '../sidebar/sidebar.php'; ?>

    <main class="main-content">
        <header class="module-header">
            <h1><i class="fas fa-newspaper"></i> Publication Management</h1>
            <p>Publish verified completed road issues for public transparency</p>
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
        <div class="stats-grid" style="display: grid; grid-template-columns: repeat(5, 1fr); gap: 20px; margin-bottom: 25px;">
            <div class="stat-card">
                <div class="stat-icon" style="background: #fff7ed; color: #f97316;">
                    <i class="fas fa-hourglass-half"></i>
                </div>
                <div class="stat-value"><?php echo $stats['ready_to_publish']; ?></div>
                <div class="stat-label">Ready to Publish</div>
            </div>

            <div class="stat-card">
                <div class="stat-icon" style="background: #f0fdf4; color: #22c55e;">
                    <i class="fas fa-file-invoice"></i>
                </div>
                <div class="stat-value"><?php echo $stats['engineer_proposals']; ?></div>
                <div class="stat-label">Engineer Proposals</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon" style="background: #eff6ff; color: #3b82f6;">
                    <i class="fas fa-bullhorn"></i>
                </div>
                <div class="stat-value"><?php echo $stats['active_announcements']; ?></div>
                <div class="stat-label">Live Publications</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon" style="background: #fdf2f8; color: #db2777;">
                    <i class="fas fa-check-double"></i>
                </div>
                <div class="stat-value"><?php echo $stats['completed_public']; ?></div>
                <div class="stat-label">Completed Works</div>
            </div>

            <div class="stat-card">
                <div class="stat-icon repair">
                    <i class="fas fa-tools"></i>
                </div>
                <div class="stat-value"><?php echo $stats['under_repair_public']; ?></div>
                <div class="stat-label">Under Repair</div>
            </div>
        </div>




        <!-- All Publications & Proposals -->
        <div class="content-card">
            <h2 class="section-title"><i class="fas fa-newspaper"></i> All Publications & Proposals</h2>
            
            <?php if (empty($allPublications)): ?>
                <p style="color: var(--text-muted); text-align: center; padding: 40px;">
                    <i class="fas fa-file-alt" style="font-size: 2rem; margin-bottom: 10px; display: block;"></i>
                    No publications or proposals found.
                </p>
            <?php else: ?>
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>Publication ID</th>
                                <th>Road Name</th>
                                <th>Issue Summary</th>
                                <th>Status</th>
                                <th>Appr. Status</th>
                                <th style="width: 160px;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($allPublications as $report): ?>
                                <?php 
                                    $statusClass = str_replace('_', '-', $report['status_public']);
                                    $approvalClass = strtolower(str_replace(' ', '-', $report['approval_status']));
                                ?>
                                <tr>
                                    <td>
                                        <strong><?php echo $report['publication_id']; ?></strong>
                                        <?php if (isset($report['internal_report_id'])): ?>
                                            <br><small style="color: #94a3b8; font-size: 0.7rem;">Ref: <?php echo $report['internal_report_id']; ?></small>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($report['road_name']); ?></td>
                                    <td>
                                        <div style="max-width: 300px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;" title="<?php echo htmlspecialchars($report['issue_summary']); ?>">
                                            <?php echo htmlspecialchars($report['issue_summary']); ?>
                                        </div>
                                        <small style="color: #64748b; font-size: 0.75rem;">
                                            <?php if ($report['item_origin'] === 'raw_report'): ?>
                                                <i class="fas fa-user-tag"></i> Reported by Citizen
                                            <?php else: ?>
                                                <i class="fas fa-hard-hat"></i> Proposed by <?php echo htmlspecialchars($report['engineer_name'] ?: ($report['first_name'] . ' ' . $report['last_name'])); ?>
                                            <?php endif; ?>
                                        </small>
                                    </td>
                                    <td><span class="status-badge status-<?php echo $statusClass; ?>"><?php echo strtoupper(str_replace('_', ' ', $report['status_public'])); ?></span></td>
                                    <td><span class="status-badge status-<?php echo $approvalClass; ?>"><?php echo strtoupper(str_replace('_', ' ', $report['approval_status'])); ?></span></td>
                                    <td>
                                        <div style="display: flex; gap: 8px;">
                                            <button class="btn btn-sm" style="background: #f1f5f9; color: #475569;" onclick="previewPublication(<?php echo $report['id']; ?>, '<?php echo $report['item_origin']; ?>')" title="Preview">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                            
                                            <?php if ($report['item_origin'] === 'raw_report'): ?>
                                                <button class="btn btn-primary btn-sm" onclick="openPublishModal(<?php echo $report['id']; ?>, '<?php echo addslashes($report['road_name']); ?>', '<?php echo addslashes($report['issue_summary']); ?>', '<?php echo $report['severity_public']; ?>')" title="Approve & Publish">
                                                    <i class="fas fa-check-circle"></i>
                                                </button>
                                                <button class="btn btn-sm" style="background: #fee2e2; color: #991b1b;" onclick="submitAction('decline_pending_report', {report_id: <?php echo $report['id']; ?>}, 'Stop this report from being published?')" title="Decline">
                                                    <i class="fas fa-ban"></i>
                                                </button>
                                            <?php elseif ($report['approval_status'] === 'pending' || $report['approval_status'] === 'needs_revision'): ?>
                                                <button class="btn btn-success btn-sm" title="Approve" onclick="submitAction('approve_publication', {publication_id: <?php echo $report['id']; ?>}, 'Approve and publish this proposal?')">
                                                    <i class="fas fa-check"></i>
                                                </button>
                                                <button class="btn btn-danger btn-sm" onclick="showRejectModal(<?php echo $report['id']; ?>)" title="Decline">
                                                    <i class="fas fa-times"></i>
                                                </button>
                                            <?php elseif ($report['approval_status'] === 'approved'): ?>
                                                <button class="btn btn-warning btn-sm" onclick="openUpdateModal(<?php echo $report['id']; ?>, '<?php echo $report['status_public']; ?>', '<?php echo $report['completion_date']; ?>')" title="Update Progress">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <button class="btn btn-danger btn-sm" onclick="openArchiveModal(<?php echo $report['id']; ?>)" title="Archive">
                                                    <i class="fas fa-archive"></i>
                                                </button>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <!-- Reject Modal -->
    <div id="rejectModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title">Decline Publication Proposal</h3>
                <button type="button" class="close" onclick="closeModal('rejectModal')">&times;</button>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="reject_publication">
                <input type="hidden" name="publication_id" id="reject_pub_id">
                
                <div class="form-group">
                    <label class="form-label">Reason for Rejection *</label>
                    <textarea name="reject_reason" class="form-control" placeholder="Provide feedback to the engineer..." required></textarea>
                </div>
                
                <div style="display: flex; gap: 10px; justify-content: flex-end;">
                    <button type="button" class="btn" style="background: #e5e7eb;" onclick="closeModal('rejectModal')">Cancel</button>
                    <button type="submit" class="btn btn-danger">Confirm Reject</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Revision Modal -->
    <div id="revisionModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title">Request Revision</h3>
                <button type="button" class="close" onclick="closeModal('revisionModal')">&times;</button>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="request_revision">
                <input type="hidden" name="publication_id" id="revision_pub_id">
                
                <div class="form-group">
                    <label class="form-label">Feedback / Required Changes *</label>
                    <textarea name="revision_notes" class="form-control" placeholder="What needs to be corrected?..." required></textarea>
                </div>
                
                <div style="display: flex; gap: 10px; justify-content: flex-end;">
                    <button type="button" class="btn" style="background: #e5e7eb;" onclick="closeModal('revisionModal')">Cancel</button>
                    <button type="submit" class="btn btn-warning">Send Request</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Preview Modal -->
    <div id="previewModal" class="modal">
        <div class="modal-content" style="max-width: 800px;">
            <div class="modal-header">
                <h3 class="modal-title"><i class="fas fa-search-plus"></i> Publication Preview</h3>
                <button type="button" class="close" onclick="closeModal('previewModal')">&times;</button>
            </div>
            <div class="modal-body">
                <div class="preview-box">
                    <div class="preview-label">PUBLIC VIEW PREVIEW</div>
                    <div id="previewContent" style="padding: 20px;">
                        <div style="text-align: center; padding: 40px;">
                            <i class="fas fa-circle-notch fa-spin" style="font-size: 2rem; color: var(--primary);"></i>
                            <p style="margin-top: 15px; color: var(--text-muted);">Loading preview...</p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer" id="previewModalFooter" style="padding: 20px 30px; border-top: 1px solid #f1f5f9; display: flex; justify-content: flex-end; gap: 10px; background: #f8fafc; border-radius: 0 0 16px 16px;">
                <button type="button" class="btn" style="background: #e2e8f0; color: #475569;" onclick="closeModal('previewModal')">Close</button>
                <div id="previewActions" style="display: flex; gap: 10px;">
                    <!-- Action buttons will be injected here -->
                </div>
            </div>
        </div>
    </div>

    <script>

        function previewPublication(id, type = 'publication') {
            const modal = document.getElementById('previewModal');
            const content = document.getElementById('previewContent');
            const actions = document.getElementById('previewActions');
            modal.style.display = 'block';
            actions.innerHTML = ''; // Clear previous actions
            
            content.innerHTML = `
                <div style="text-align: center; padding: 40px;">
                    <i class="fas fa-circle-notch fa-spin" style="font-size: 2rem; color: var(--primary);"></i>
                    <p style="margin-top: 15px; color: var(--text-muted);">Loading preview...</p>
                </div>
            `;

            fetch(`publication_management.php?ajax_get_details=${id}&type=${type}`)
                .then(response => response.json())
                .then(data => {
                    if (data.error) {
                        content.innerHTML = `<p class="alert alert-error">${data.error}</p>`;
                        return;
                    }

                    const statusClass = data.status_public.replace('_', '-');

                    content.innerHTML = `
                        <div style="margin-bottom: 25px; border-bottom: 2px solid #f1f5f9; padding-bottom: 15px;">
                            <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 15px;">
                                <div>
                                    <h2 style="font-size: 1.6rem; color: #0f172a; margin-bottom: 5px;">${data.publication_title || data.road_name}</h2>
                                    <div style="display: flex; gap: 15px; font-size: 0.8rem; color: #64748b;">
                                        <span><i class="fas fa-map-marker-alt"></i> ${data.road_name}</span>
                                        <span><i class="fas fa-hashtag"></i> ${data.publication_id}</span>
                                        <span><i class="fas fa-calendar-alt"></i> ${data.formatted_date}</span>
                                    </div>
                                </div>
                                <div style="display: flex; flex-direction: column; align-items: flex-end; gap: 8px;">
                                    <span class="status-badge status-${statusClass}">${data.status_public.replace('_', ' ').toUpperCase()}</span>
                                    <span class="status-badge" style="background: #f1f5f9; color: #475569; font-size: 0.7rem; border: 1px solid #e2e8f0;">${data.approval_status.toUpperCase()}</span>
                                </div>
                            </div>
                        </div>

                        <div style="background: #f8fafc; padding: 20px; border-radius: 12px; border-left: 4px solid var(--primary); margin-bottom: 25px;">
                            <label style="font-size: 0.75rem; color: var(--primary); font-weight: 700; letter-spacing: 1px; display: block; margin-bottom: 10px;">PROJECT SUMMARY</label>
                            <p style="font-size: 1.1rem; line-height: 1.6; color: #1e293b; font-weight: 500;">${data.issue_summary}</p>
                        </div>

                        <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; margin-bottom: 25px;">
                            <div>
                                <label style="display: block; font-size: 0.8rem; color: #64748b; margin-bottom: 5px;">Issue Type</label>
                                <div style="font-weight: 600;">${data.issue_type.replace('_', ' ').toUpperCase()}</div>
                            </div>
                            <div>
                                <label style="display: block; font-size: 0.8rem; color: #64748b; margin-bottom: 5px;">Reported Date</label>
                                <div style="font-weight: 600;">${data.formatted_reported}</div>
                            </div>
                            <div>
                                <label style="display: block; font-size: 0.8rem; color: #64748b; margin-bottom: 5px;">Severity</label>
                                <div><span class="status-badge status-${data.severity_public}">${data.severity_public.toUpperCase()}</span></div>
                            </div>
                        </div>

                        <div style="background: #eff6ff; padding: 20px; border-radius: 12px; display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px;">
                            <div>
                                <label style="display: block; font-size: 0.8rem; color: #3b82f6; margin-bottom: 5px; font-weight: 600;">Work Start</label>
                                <div style="font-weight: 700; color: #1e40af;">${data.formatted_start}</div>
                            </div>
                            <div>
                                <label style="display: block; font-size: 0.8rem; color: #3b82f6; margin-bottom: 5px; font-weight: 600;">Target Completion</label>
                                <div style="font-weight: 700; color: #1e40af;">${data.formatted_completion}</div>
                            </div>
                            <div>
                                <label style="display: block; font-size: 0.8rem; color: #3b82f6; margin-bottom: 5px; font-weight: 600;">Duration</label>
                                <div style="font-weight: 700; color: #1e40af;">${data.repair_duration_days ? data.repair_duration_days + ' Days' : 'N/A'}</div>
                            </div>
                        </div>
                    `;

                    // Add action buttons based on type and status
                    if (data.item_type === 'raw_report') {
                        actions.innerHTML = `
                            <button type="button" class="btn btn-sm" style="background: #fee2e2; color: #991b1b;" onclick="closeModal('previewModal'); submitAction('decline_pending_report', {report_id: ${data.id}}, 'Stop this report from being published?')">
                                <i class="fas fa-ban"></i> Decline
                            </button>
                            <button type="button" class="btn btn-primary" onclick="closeModal('previewModal'); openPublishModal(${data.id}, '${data.road_name.replace(/'/g, "\\'")}', '${data.issue_summary.replace(/'/g, "\\'")}', '${data.severity_public}')">
                                <i class="fas fa-check-circle"></i> Approve & Publish
                            </button>
                        `;
                    } else if (data.approval_status === 'pending' || data.approval_status === 'needs_revision') {
                        actions.innerHTML = `
                            <button type="button" class="btn btn-danger" onclick="closeModal('previewModal'); showRejectModal(${data.id})">
                                <i class="fas fa-times"></i> Decline
                            </button>
                            <button type="button" class="btn btn-success" onclick="submitAction('approve_publication', {publication_id: ${data.id}}, 'Approve and publish this proposal?')">
                                <i class="fas fa-check"></i> Approve
                            </button>
                        `;
                    } else {
                        actions.innerHTML = '';
                    }
                })
                .catch(err => {
                    content.innerHTML = `<p class="alert alert-error">Error loading details.</p>`;
                });
        }
    </script>

    <!-- Publish Modal -->
    <div id="publishModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title">Publish Report for Public Viewing</h3>
                <button class="close" onclick="closeModal('publishModal')">&times;</button>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="publish_report">
                <input type="hidden" id="publish_damage_report_id" name="damage_report_id">
                
                <div class="form-group">
                    <label class="form-label">Road Name *</label>
                    <input type="text" id="publish_road_name" name="road_name" class="form-control" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Issue Summary *</label>
                    <textarea id="publish_issue_summary" name="issue_summary" class="form-control" required></textarea>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Issue Type *</label>
                    <select id="publish_issue_type" name="issue_type" class="form-control" required>
                        <option value="">Select Issue Type</option>
                        <option value="pothole">Pothole</option>
                        <option value="crack">Crack</option>
                        <option value="drainage">Drainage Issue</option>
                        <option value="surface_damage">Surface Damage</option>
                        <option value="other">Other</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Public Severity Level *</label>
                    <select id="publish_severity_public" name="severity_public" class="form-control" required>
                        <option value="">Select Severity</option>
                        <option value="low">Low</option>
                        <option value="medium">Medium</option>
                        <option value="high">High</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Public Status *</label>
                    <select id="publish_status_public" name="status_public" class="form-control" required>
                        <option value="">Select Status</option>
                        <option value="reported">Reported</option>
                        <option value="under_repair">Under Repair</option>
                        <option value="completed">Completed</option>
                        <option value="fixed">Fixed</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Date Reported *</label>
                    <input type="date" id="publish_date_reported" name="date_reported" class="form-control" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Repair Start Date</label>
                    <input type="date" id="publish_repair_start_date" name="repair_start_date" class="form-control">
                </div>
                
                <div class="form-group">
                    <label class="form-label">Completion Date</label>
                    <input type="date" id="publish_completion_date" name="completion_date" class="form-control">
                </div>
                
                <div style="display: flex; gap: 10px; justify-content: flex-end;">
                    <button type="button" class="btn" style="background: #e5e7eb; color: #374151;" onclick="closeModal('publishModal')">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-publish"></i> Publish Report
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Update Modal -->
    <div id="updateModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title">Update Publication Status</h3>
                <button class="close" onclick="closeModal('updateModal')">&times;</button>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="update_publication">
                <input type="hidden" id="update_publication_id" name="publication_id">
                
                <div class="form-group">
                    <label class="form-label">Public Status *</label>
                    <select id="update_status_public" name="status_public" class="form-control" required>
                        <option value="reported">Reported</option>
                        <option value="under_repair">Under Repair</option>
                        <option value="completed">Completed</option>
                        <option value="fixed">Fixed</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Completion Date</label>
                    <input type="date" id="update_completion_date" name="completion_date" class="form-control">
                </div>
                
                <div style="display: flex; gap: 10px; justify-content: flex-end;">
                    <button type="button" class="btn" style="background: #e5e7eb; color: #374151;" onclick="closeModal('updateModal')">Cancel</button>
                    <button type="submit" class="btn btn-warning">
                        <i class="fas fa-edit"></i> Update Publication
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Archive Modal -->
    <div id="archiveModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title">Archive Publication</h3>
                <button class="close" onclick="closeModal('archiveModal')">&times;</button>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="archive_publication">
                <input type="hidden" id="archive_publication_id" name="publication_id">
                
                <div class="form-group">
                    <label class="form-label">Archive Reason *</label>
                    <select id="archive_reason" name="archive_reason" class="form-control" required>
                        <option value="">Select Reason</option>
                        <option value="Report declined">Report Declined</option>
                        <option value="Information outdated">Information Outdated</option>
                        <option value="Data correction needed">Data Correction Needed</option>
                        <option value="Administrative removal">Administrative Removal</option>
                        <option value="Other">Other</option>
                    </select>
                </div>
                
                <div style="display: flex; gap: 10px; justify-content: flex-end;">
                    <button type="button" class="btn" style="background: #e5e7eb; color: #374151;" onclick="closeModal('archiveModal')">Cancel</button>
                    <button type="submit" class="btn btn-danger">
                        <i class="fas fa-archive"></i> Archive Publication
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        /**
         * Centralized function to submit POST actions without inline forms
         */
        function submitAction(action, data, confirmMessage = null) {
            if (confirmMessage && !confirm(confirmMessage)) return;
            
            const form = document.createElement('form');
            form.method = 'POST';
            
            const actionInput = document.createElement('input');
            actionInput.type = 'hidden';
            actionInput.name = 'action';
            actionInput.value = action;
            form.appendChild(actionInput);
            
            for (const [key, value] of Object.entries(data)) {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = key;
                input.value = value;
                form.appendChild(input);
            }
            
            document.body.appendChild(form);
            form.submit();
        }

        function openPublishModal(reportId, location, description, severity) {
            document.getElementById('publish_damage_report_id').value = reportId;
            document.getElementById('publish_road_name').value = location;
            document.getElementById('publish_issue_summary').value = description;
            document.getElementById('publish_date_reported').value = new Date().toISOString().split('T')[0];
            
            // Set severity based on internal severity
            if (severity === 'critical') {
                document.getElementById('publish_severity_public').value = 'high';
            } else {
                document.getElementById('publish_severity_public').value = severity;
            }
            
            document.getElementById('publishModal').style.display = 'block';
        }

        function openUpdateModal(publicationId, currentStatus, completionDate) {
            document.getElementById('update_publication_id').value = publicationId;
            document.getElementById('update_status_public').value = currentStatus;
            if (completionDate) {
                document.getElementById('update_completion_date').value = completionDate;
            }
            document.getElementById('updateModal').style.display = 'block';
        }

        function openArchiveModal(publicationId) {
            document.getElementById('archive_publication_id').value = publicationId;
            document.getElementById('archiveModal').style.display = 'block';
        }

        function closeModal(modalId) {
            document.getElementById(modalId).style.display = 'none';
        }

        // Enhanced functions for pending reports
        function refreshPendingReports() {
            location.reload();
        }

        function filterPendingReports() {
            const severityFilter = document.getElementById('severityFilter').value;
            const statusFilter = document.getElementById('statusFilter').value;
            const table = document.getElementById('pendingReportsTable');
            const rows = table.getElementsByTagName('tbody')[0].getElementsByTagName('tr');
            let visibleCount = 0;

            for (let row of rows) {
                const severity = row.getAttribute('data-severity');
                const status = row.getAttribute('data-status');
                
                const severityMatch = !severityFilter || severity === severityFilter;
                const statusMatch = !statusFilter || status === statusFilter;
                
                if (severityMatch && statusMatch) {
                    row.style.display = '';
                    visibleCount++;
                } else {
                    row.style.display = 'none';
                }
            }

            updateFilterInfo(visibleCount);
        }

        function sortPendingReports() {
            const sortBy = document.getElementById('sortBy').value;
            const table = document.getElementById('pendingReportsTable');
            const tbody = table.getElementsByTagName('tbody')[0];
            const rows = Array.from(tbody.getElementsByTagName('tr'));

            rows.sort((a, b) => {
                switch (sortBy) {
                    case 'newest':
                        return new Date(b.getAttribute('data-date')) - new Date(a.getAttribute('data-date'));
                    case 'oldest':
                        return new Date(a.getAttribute('data-date')) - new Date(b.getAttribute('data-date'));
                    case 'severity':
                        const severityOrder = { high: 3, medium: 2, low: 1, critical: 4 };
                        return severityOrder[b.getAttribute('data-severity')] - severityOrder[a.getAttribute('data-severity')];
                    case 'location':
                        return a.getAttribute('data-location').localeCompare(b.getAttribute('data-location'));
                    default:
                        return 0;
                }
            });

            // Re-append sorted rows
            rows.forEach(row => tbody.appendChild(row));
        }

        function updateFilterInfo(visibleCount) {
            const filterInfo = document.getElementById('filterInfo');
            const totalCount = document.getElementById('pendingReportsTable').getElementsByTagName('tbody')[0].getElementsByTagName('tr').length;
            
            if (visibleCount === totalCount) {
                filterInfo.textContent = `Showing all ${totalCount} reports`;
            } else {
                filterInfo.textContent = `Showing ${visibleCount} of ${totalCount} reports`;
            }
        }

        function publishAllReports() {
            if (confirm('Are you sure you want to publish all pending reports? This will make them visible to the public.')) {
                // Create a form to submit all reports
                const form = document.createElement('form');
                form.method = 'POST';
                
                // Add action input
                const actionInput = document.createElement('input');
                actionInput.type = 'hidden';
                actionInput.name = 'action';
                actionInput.value = 'publish_all_pending';
                form.appendChild(actionInput);
                
                // Submit the form
                document.body.appendChild(form);
                form.submit();
            }
        }

        function exportPendingReports() {
            const table = document.getElementById('pendingReportsTable');
            const rows = table.getElementsByTagName('tbody')[0].getElementsByTagName('tr');
            let csv = 'Report ID,Location,Description,Severity,Status,Date Resolved,Reported By\n';
            
            for (let row of rows) {
                if (row.style.display !== 'none') {
                    const cells = row.getElementsByTagName('td');
                    const reportId = cells[0].textContent.trim();
                    const location = cells[1].textContent.trim();
                    const description = cells[2].textContent.trim();
                    const severity = cells[3].textContent.trim();
                    const status = cells[4].textContent.trim();
                    const dateResolved = cells[5].textContent.trim();
                    const reportedBy = cells[6].textContent.trim();
                    
                    csv += `"${reportId}","${location}","${description}","${severity}","${status}","${dateResolved}","${reportedBy}"\n`;
                }
            }
            
            // Create download link
            const blob = new Blob([csv], { type: 'text/csv' });
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = `pending_reports_${new Date().toISOString().split('T')[0]}.csv`;
            a.click();
            window.URL.revokeObjectURL(url);
        }

        function viewReportDetails(reportId) {
            // This could open a modal with full report details
            // For now, we'll show a simple alert
            alert(`Viewing full details for report ID: ${reportId}\n\nThis feature could show complete report information including photos, full description, and timeline.`);
        }

        function showRejectModal(id) {
            document.getElementById('reject_pub_id').value = id;
            document.getElementById('rejectModal').style.display = 'block';
        }

        function showRevisionModal(id) {
            document.getElementById('revision_pub_id').value = id;
            document.getElementById('revisionModal').style.display = 'block';
        }

        // Close modal when clicking outside
        window.onclick = function(event) {
            if (event.target.classList.contains('modal')) {
                event.target.style.display = 'none';
            }
        }
    </script>
</body>
</html>
