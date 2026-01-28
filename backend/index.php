<?php
// Backend API Entry Point for LGU Road and Infrastructure Department
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    exit(0);
}

// Include required files
require_once '../config/database.php';
require_once '../config/auth.php';
require_once '../config/security.php';

// Get request method and endpoint
$method = $_SERVER['REQUEST_METHOD'];
$request = $_SERVER['REQUEST_URI'];
$path = parse_url($request, PHP_URL_PATH);
$path_parts = explode('/', trim($path, '/'));

// Remove the base directory from path
array_shift($path_parts); // Remove 'road_and_infra_dept'
array_shift($path_parts); // Remove 'backend'

$endpoint = $path_parts[0] ?? '';
$resource_id = $path_parts[1] ?? null;

try {
    // Route the request
    switch ($endpoint) {
        case 'damage-reports':
            require_once 'controllers/DamageReportController.php';
            $controller = new DamageReportController();
            handleRequest($controller, $method, $resource_id);
            break;
            
        case 'cost-assessments':
            require_once 'controllers/CostAssessmentController.php';
            $controller = new CostAssessmentController();
            handleRequest($controller, $method, $resource_id);
            break;
            
        case 'inspections':
            require_once 'controllers/InspectionController.php';
            $controller = new InspectionController();
            handleRequest($controller, $method, $resource_id);
            break;
            
        case 'gis-data':
            require_once 'controllers/GISController.php';
            $controller = new GISController();
            handleRequest($controller, $method, $resource_id);
            break;
            
        case 'documents':
            require_once 'controllers/DocumentController.php';
            $controller = new DocumentController();
            handleRequest($controller, $method, $resource_id);
            break;
            
        case 'maintenance':
            require_once 'controllers/MaintenanceController.php';
            $controller = new MaintenanceController();
            handleRequest($controller, $method, $resource_id);
            break;
            
        case 'announcements':
            require_once 'controllers/AnnouncementController.php';
            $controller = new AnnouncementController();
            handleRequest($controller, $method, $resource_id);
            break;
            
        case 'users':
            require_once 'controllers/UserController.php';
            $controller = new UserController();
            handleRequest($controller, $method, $resource_id);
            break;
            
        case 'analytics':
            require_once 'controllers/AnalyticsController.php';
            $controller = new AnalyticsController();
            handleRequest($controller, $method, $resource_id);
            break;
            
        default:
            http_response_code(404);
            echo json_encode(['error' => 'Endpoint not found']);
            break;
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Internal server error: ' . $e->getMessage()]);
}

function handleRequest($controller, $method, $resource_id) {
    switch ($method) {
        case 'GET':
            if ($resource_id) {
                $controller->getById($resource_id);
            } else {
                $controller->getAll();
            }
            break;
        case 'POST':
            $controller->create();
            break;
        case 'PUT':
            if ($resource_id) {
                $controller->update($resource_id);
            } else {
                http_response_code(400);
                echo json_encode(['error' => 'Resource ID required for PUT requests']);
            }
            break;
        case 'DELETE':
            if ($resource_id) {
                $controller->delete($resource_id);
            } else {
                http_response_code(400);
                echo json_encode(['error' => 'Resource ID required for DELETE requests']);
            }
            break;
        default:
            http_response_code(405);
            echo json_encode(['error' => 'Method not allowed']);
            break;
    }
}
?>
