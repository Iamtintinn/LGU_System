<?php
// get_transparency_data.php - API for fetching public transparency data
session_start();
require_once '../../../config/auth.php';
require_once '../../../config/database.php';

header('Content-Type: application/json');

function sendResponse($success, $message, $data = []) {
    echo json_encode(['success' => $success, 'message' => $message, 'data' => $data]);
    exit;
}

// Public access - no authentication required for transparency data
try {
    $database = new Database();
    $conn = $database->getConnection();

    // Get statistics
    $stats_query = "
        SELECT 
            COUNT(*) as total_reports,
            SUM(CASE WHEN status IN ('pending', 'under_review') THEN 1 ELSE 0 END) as pending_reports,
            SUM(CASE WHEN status IN ('in_progress') THEN 1 ELSE 0 END) as under_repair,
            SUM(CASE WHEN status IN ('completed', 'closed') THEN 1 ELSE 0 END) as completed_repairs,
            SUM(CASE WHEN severity = 'urgent' THEN 1 ELSE 0 END) as urgent_cases
        FROM damage_reports
        WHERE publication_status = 'published'
    ";
    
    $stats_result = $conn->query($stats_query);
    $stats = $stats_result->fetch_assoc();

    // Get published projects/cost transparency data
    $projects_query = "
        SELECT 
            pp.publication_id as project_id,
            pp.road_name,
            pp.issue_summary as project_name,
            pp.repair_start_date,
            pp.completion_date,
            pp.repair_duration_days,
            pp.status_public as status,
            dr.severity,
            dr.damage_type,
            CONCAT(u.first_name, ' ', u.last_name) as published_by,
            pp.publication_date
        FROM public_publications pp
        LEFT JOIN damage_reports dr ON pp.damage_report_id = dr.id
        LEFT JOIN users u ON pp.published_by = u.id
        WHERE pp.archived = 0 AND pp.approval_status = 'approved'
        ORDER BY pp.publication_date DESC
        LIMIT 10
    ";
    
    $projects_result = $conn->query($projects_query);
    $projects = [];
    
    while ($row = $projects_result->fetch_assoc()) {
        // Generate mock budget data for demonstration
        $budget_ranges = [
            'urgent' => ['min' => 50000, 'max' => 200000],
            'high' => ['min' => 30000, 'max' => 150000],
            'medium' => ['min' => 20000, 'max' => 100000],
            'low' => ['min' => 10000, 'max' => 50000]
        ];
        
        $severity = $row['severity'] ?? 'medium';
        $budget_range = $budget_ranges[$severity] ?? $budget_ranges['medium'];
        $estimated_budget = rand($budget_range['min'], $budget_range['max']);
        
        $projects[] = [
            'project_id' => $row['project_id'],
            'road_name' => $row['road_name'],
            'project_name' => $row['project_name'],
            'approved_budget' => number_format($estimated_budget, 2),
            'funding_source' => 'City Infrastructure Fund',
            'status' => $row['status'],
            'published_by' => $row['published_by'],
            'publication_date' => date('M j, Y', strtotime($row['publication_date']))
        ];
    }

    // Get road issues for public display
    $road_issues_query = "
        SELECT 
            dr.location as road_name,
            dr.damage_type as issue,
            dr.severity,
            dr.status,
            dr.reported_at,
            CONCAT(u.first_name, ' ', u.last_name) as reporter_name
        FROM damage_reports dr
        LEFT JOIN users u ON dr.reporter_id = u.id
        WHERE dr.publication_status = 'published'
        ORDER BY dr.reported_at DESC
        LIMIT 20
    ";
    
    $issues_result = $conn->query($road_issues_query);
    $road_issues = [];
    
    while ($row = $issues_result->fetch_assoc()) {
        $road_issues[] = [
            'road_name' => $row['road_name'],
            'issue' => ucfirst($row['issue']),
            'severity' => ucfirst($row['severity']),
            'status' => ucfirst(str_replace('_', ' ', $row['status'])),
            'reported_date' => date('M j, Y', strtotime($row['reported_at'])),
            'reporter_name' => $row['reporter_name'] ?: 'Anonymous'
        ];
    }

    sendResponse(true, 'Transparency data retrieved successfully', [
        'statistics' => $stats,
        'projects' => $projects,
        'road_issues' => $road_issues
    ]);

} catch (Exception $e) {
    sendResponse(false, 'System error: ' . $e->getMessage());
}
?>
