<?php
// get_citizen_reports.php - API for LGU officers to fetch citizen reports
session_start();
require_once '../../config/auth.php';
require_once '../../config/database.php';

header('Content-Type: application/json');

function sendResponse($success, $message, $data = []) {
    echo json_encode(['success' => $success, 'message' => $message, 'data' => $data]);
    exit;
}

if (!$auth->isLoggedIn()) {
    sendResponse(false, 'Session expired. Please login again.');
}

$auth->requireAnyRole(['lgu_officer', 'admin']);

try {
    $database = new Database();
    $conn = $database->getConnection();

    // Get query parameters
    $status = $_GET['status'] ?? 'all';
    $severity = $_GET['severity'] ?? 'all';
    $search = $_GET['search'] ?? '';
    $sort = $_GET['sort'] ?? 'latest';

    // Build base query using correct column names
    $query = "
        SELECT dr.*, 
               CONCAT(u.first_name, ' ', u.last_name) as reporter_name, 
               u.email as reporter_email,
               CONCAT(ao.first_name, ' ', ao.last_name) as assigned_officer_name
        FROM damage_reports dr
        LEFT JOIN users u ON dr.reporter_id = u.id
        LEFT JOIN users ao ON dr.assigned_to = ao.id
        WHERE 1=1
    ";
    
    $params = [];
    $types = '';

    // Add filters
    if ($status !== 'all') {
        $query .= " AND dr.status = ?";
        $params[] = $status;
        $types .= 's';
    }

    if ($severity !== 'all') {
        $query .= " AND dr.severity = ?";
        $params[] = $severity;
        $types .= 's';
    }

    if (!empty($search)) {
        $query .= " AND (dr.location LIKE ? OR dr.description LIKE ? OR dr.report_id LIKE ?)";
        $searchParam = "%$search%";
        $params[] = $searchParam;
        $params[] = $searchParam;
        $params[] = $searchParam;
        $types .= 'sss';
    }

    // Add sorting using reported_at column
    if ($sort === 'latest') {
        $query .= " ORDER BY dr.reported_at DESC";
    } elseif ($sort === 'oldest') {
        $query .= " ORDER BY dr.reported_at ASC";
    } elseif ($sort === 'severity_high') {
        $query .= " ORDER BY FIELD(dr.severity, 'urgent', 'high', 'medium', 'low'), dr.reported_at DESC";
    } elseif ($sort === 'severity_low') {
        $query .= " ORDER BY FIELD(dr.severity, 'low', 'medium', 'high', 'urgent'), dr.reported_at DESC";
    }

    $stmt = $conn->prepare($query);
    
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }

    $stmt->execute();
    $result = $stmt->get_result();

    $reports = [];
    while ($row = $result->fetch_assoc()) {
        // Parse images JSON if exists
        $row['images'] = $row['images'] ? json_decode($row['images'], true) : [];
        
        // Format date using reported_at column
        $row['created_at_formatted'] = date('M j, Y g:i A', strtotime($row['reported_at']));
        $row['updated_at_formatted'] = date('M j, Y g:i A', strtotime($row['updated_at']));
        
        $reports[] = $row;
    }

    // Get statistics
    $stats_query = "
        SELECT 
            COUNT(*) as total,
            SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
            SUM(CASE WHEN status = 'under_review' THEN 1 ELSE 0 END) as under_review,
            SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) as approved,
            SUM(CASE WHEN status = 'in_progress' THEN 1 ELSE 0 END) as in_progress,
            SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed,
            SUM(CASE WHEN severity = 'urgent' THEN 1 ELSE 0 END) as urgent,
            SUM(CASE WHEN severity = 'high' THEN 1 ELSE 0 END) as high
        FROM damage_reports
    ";
    
    $stats_result = $conn->query($stats_query);
    $stats = $stats_result->fetch_assoc();

    sendResponse(true, 'Reports retrieved successfully', [
        'reports' => $reports,
        'stats' => $stats
    ]);

} catch (Exception $e) {
    sendResponse(false, 'System error: ' . $e->getMessage());
}
?>
