<?php
// API endpoint for submitting LGU inspection reports
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', 'php_errors.log');

ob_start();

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With');

session_start();
require_once '../../config/auth.php';
require_once '../../config/database.php';

// Require engineer role
$auth->requireRole('engineer');

// Initialize database connection
$db = new Database();
$conn = $db->getConnection();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

try {
    // Debug: Log all incoming data
    error_log("=== SUBMIT LGU REPORT DEBUG ===");
    error_log("POST data: " . print_r($_POST, true));
    error_log("FILES data: " . print_r($_FILES, true));
    
    // Get form data
    $location = $_POST['location'] ?? '';
    $inspection_date = $_POST['inspection_date'] ?? '';
    $severity = $_POST['severity'] ?? '';
    $description = $_POST['description'] ?? '';
    $coordinates = $_POST['coordinates'] ?? '';
    $estimated_cost = $_POST['estimated_cost'] ?? 0;
    $priority = $_POST['priority'] ?? '';
    $notes = $_POST['notes'] ?? '';
    $engineer_id = $_SESSION['user_id'];
    
    // Debug: Log received data
    error_log("Location: $location");
    error_log("Date: $inspection_date");
    error_log("Severity: $severity");
    error_log("Description: $description");
    error_log("Priority: $priority");
    error_log("Notes: $notes");
    
    // Validate required fields
    if (empty($location) || empty($inspection_date) || empty($severity) || empty($description) || empty($priority)) {
        throw new Exception('Missing required fields');
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
                error_log("Successfully uploaded photo: $file_name");
            } else {
                error_log("Failed to upload photo: $file_name");
            }
        }
    }
    
    error_log("Photos array: " . print_r($photos, true));
    
    // Generate unique inspection ID
    $inspection_id = 'LGU-INSP-' . date('Y') . '-' . str_pad(mt_rand(1, 9999), 4, '0', STR_PAD_LEFT);
    error_log("Generated inspection ID: $inspection_id");
    
    // Insert inspection report into database
    $stmt = $conn->prepare("
        INSERT INTO lgu_inspections (
            inspection_id, location, inspection_date, severity, description, 
            coordinates, estimated_cost, priority, engineer_id, photos, 
            notes, status, submitted_at
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending_approval', NOW())
    ");
    
    $photos_json = json_encode($photos);
    error_log("Photos JSON: $photos_json");
    
    $stmt->bind_param(
        'sssssdsssss',
        $inspection_id, $location, $inspection_date, $severity, $description,
        $coordinates, $estimated_cost, $priority, $engineer_id, $photos_json, $notes
    );
    
    if (!$stmt->execute()) {
        $error_msg = 'Failed to insert LGU inspection report: ' . $stmt->error;
        error_log("Database error: $error_msg");
        throw new Exception($error_msg);
    }
    
    error_log("Successfully inserted LGU inspection report");
    
    // Log activity
    $auth->logActivity('lgu_report_submitted', "Submitted LGU inspection report: $inspection_id");
    
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
            'type' => 'lgu_workflow',
            'engineer_id' => $engineer_id
        ]);
        
        $notification_stmt->bind_param('ss', $inspection_id, $notification_data);
        $notification_stmt->execute();
        error_log("LGU notification created successfully");
    } catch (Exception $notification_error) {
        // Log notification error but don't fail the main request
        error_log("LGU notification error (non-critical): " . $notification_error->getMessage());
    }
    
    // Send email notification to LGU officers (if email function exists)
    try {
        // Get LGU officer emails
        $officer_query = "SELECT email, name FROM users WHERE role = 'lgu_officer' OR role = 'admin'";
        $officer_result = $conn->query($officer_query);
        
        if ($officer_result) {
            while ($officer = $officer_result->fetch_assoc()) {
                // This would require an email sending function
                // sendEmailNotification($officer['email'], $officer['name'], $inspection_id, $location);
                error_log("Would send email notification to: " . $officer['email']);
            }
        }
    } catch (Exception $email_error) {
        error_log("Email notification error (non-critical): " . $email_error->getMessage());
    }
    
    $response = [
        'success' => true,
        'message' => 'LGU inspection report created successfully and submitted for approval',
        'inspection_id' => $inspection_id,
        'status' => 'pending_approval',
        'next_step' => 'Waiting for LGU Officer review and approval'
    ];
    
    error_log("Final response: " . json_encode($response));
    
} catch (Exception $e) {
    error_log("CRITICAL ERROR: " . $e->getMessage());
    error_log("Error trace: " . $e->getTraceAsString());
    
    // Clean any output buffer
    ob_clean();
    
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Failed to submit LGU inspection report: ' . $e->getMessage()
    ]);
}

echo json_encode($response);
?>
