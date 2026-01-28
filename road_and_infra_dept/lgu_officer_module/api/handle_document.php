<?php
/**
 * handle_document.php - Robust API for Document Management
 * 
 * This version is designed to catch EVERY possible error and log it,
 * while ensuring valid JSON is ALWAYS returned to the browser.
 */

// 1. Error Reporting Configuration
error_reporting(E_ALL);
ini_set('display_errors', 0); // Don't let errors break the JSON output

// 2. Global Logging Function
$logFile = __DIR__ . '/debug_upload.log';
function logError($message) {
    global $logFile;
    $timestamp = date('Y-m-d H:i:s');
    file_put_contents($logFile, "[$timestamp] $message\n", FILE_APPEND);
}

// 3. Output Control - Buffer everything to prevent accidental output
ob_start();

// 4. Response Helper
function sendJSON($data) {
    // Clear buffer and send JSON
    ob_end_clean();
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

function sendError($message, $debug = null) {
    logError("API ERROR: $message" . ($debug ? " | Debug: $debug" : ""));
    sendJSON([
        'success' => false, 
        'message' => $message,
        'debug' => $debug
    ]);
}

// 5. Catch PHP Errors/Exceptions
set_error_handler(function($errno, $errstr, $errfile, $errline) {
    sendError("PHP Runtime Error", "$errstr in $errfile on line $errline");
});

register_shutdown_function(function() {
    $error = error_get_last();
    if ($error !== NULL && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
        sendError("Fatal PHP Error", $error['message'] . " in " . $error['file'] . " on line " . $error['line']);
    }
});

try {
    // 6. Load Dependencies
    $config_dir = dirname(dirname(__DIR__)) . '/config';
    $auth_file = $config_dir . '/auth.php';
    $db_file = $config_dir . '/database.php';

    if (!file_exists($auth_file)) sendError("Authentication system file missing.");
    if (!file_exists($db_file)) sendError("Database configuration file missing.");

    require_once $auth_file;
    require_once $db_file;

    // 7. Security Check
    if (!$auth->isLoggedIn()) {
        sendError("Your session has expired. Please log in again.");
    }

    $database = new Database();
    $conn = $database->getConnection();
    if (!$conn) sendError("Unable to connect to the database.");

    $action = $_GET['action'] ?? '';

    switch ($action) {
        case 'upload':
            // 8. Validate Input
            if (!isset($_FILES['document'])) sendError("No file received by the server.");
            if (!isset($_POST['report_id'])) sendError("Report ID is missing.");

            $report_id = $_POST['report_id'];
            $file = $_FILES['document'];
            $title = $_POST['title'] ?? 'Generated Report';
            $category = 'general'; // Default from Enum
            $user_id = $_SESSION['user_id'];

            // 9. Handle Upload Errors
            if ($file['error'] !== UPLOAD_ERR_OK) {
                $errorMessages = [
                    UPLOAD_ERR_INI_SIZE => "File exceeds upload_max_filesize in php.ini",
                    UPLOAD_ERR_FORM_SIZE => "File exceeds MAX_FILE_SIZE in form",
                    UPLOAD_ERR_PARTIAL => "File was only partially uploaded",
                    UPLOAD_ERR_NO_FILE => "No file was uploaded",
                    UPLOAD_ERR_NO_TMP_DIR => "Missing a temporary folder",
                    UPLOAD_ERR_CANT_WRITE => "Failed to write file to disk",
                    UPLOAD_ERR_EXTENSION => "A PHP extension stopped the file upload"
                ];
                sendError("File upload failed.", $errorMessages[$file['error']] ?? "Unknown PHP upload error");
            }

            // 10. Directory Setup
            $upload_dir = dirname(dirname(__DIR__)) . '/uploads/documents/';
            if (!is_dir($upload_dir)) {
                if (!mkdir($upload_dir, 0777, true)) {
                    sendError("Failed to create storage directory.", "Path: $upload_dir");
                }
            }

            $file_ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            $filename = $report_id . '_' . time() . '.' . $file_ext;
            $target_path = $upload_dir . $filename;

            // 11. Move File and Save Record
            if (move_uploaded_file($file['tmp_name'], $target_path)) {
                // Table: documents (document_id, title, category, document_type, file_path, file_size, mime_type, uploaded_by)
                $sql = "INSERT INTO documents (document_id, title, category, document_type, file_path, file_size, mime_type, uploaded_by) 
                        VALUES (?, ?, ?, 'report', ?, ?, ?, ?) 
                        ON DUPLICATE KEY UPDATE 
                        file_path = VALUES(file_path), 
                        file_size = VALUES(file_size), 
                        mime_type = VALUES(mime_type), 
                        updated_at = NOW()";

                $stmt = $conn->prepare($sql);
                if (!$stmt) sendError("Database prepare failed.", $conn->error);

                // ? corresponding to: document_id (s), title (s), category (s), file_path (s), file_size (i), mime_type (s), uploaded_by (i)
                $stmt->bind_param("ssssisi", $report_id, $title, $category, $filename, $file['size'], $file['type'], $user_id);
                
                if ($stmt->execute()) {
                    sendJSON(['success' => true, 'message' => 'Document successfully uploaded and stored.']);
                } else {
                    sendError("Could not save document info to database.", $stmt->error);
                }
            } else {
                sendError("Failed to save the uploaded file to the server.", "Check folder permissions: $upload_dir");
            }
            break;

        case 'download':
            $report_id = $_GET['report_id'] ?? '';
            $stmt = $conn->prepare("SELECT file_path FROM documents WHERE document_id = ?");
            $stmt->bind_param("s", $report_id);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($row = $result->fetch_assoc()) {
                $url = '../uploads/documents/' . $row['file_path'];
                sendJSON(['success' => true, 'url' => $url]);
            } else {
                sendError("No file has been uploaded for this report yet.");
            }
            break;

        case 'send':
            $report_id = $_POST['report_id'] ?? '';
            $stmt = $conn->prepare("UPDATE documents SET is_public = 1 WHERE document_id = ?");
            $stmt->bind_param("s", $report_id);
            if ($stmt->execute()) {
                $auth->logActivity('document_sent', "Sent report $report_id to systems.");
                sendJSON(['success' => true, 'message' => 'Report successfully sent to Publication Management module.']);
            } else {
                sendError("Failed to update status in database.", $conn->error);
            }
            break;

        default:
            sendError("Invalid action requested.");
            break;
    }
} catch (Throwable $e) {
    sendError("An unexpected system exception occurred.", $e->getMessage());
}

// Clear any accidental output if we reach here
ob_end_clean();
sendError("The system reached an unexpected end of script logic.");
