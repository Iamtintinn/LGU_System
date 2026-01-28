<?php
// Base Controller for all API endpoints
abstract class BaseController {
    protected $db;
    protected $auth;
    protected $security;
    
    public function __construct() {
        $this->db = new Database();
        $this->auth = new Auth();
        $this->security = new Security();
    }
    
    // Helper method to send JSON response
    protected function sendResponse($data, $statusCode = 200) {
        http_response_code($statusCode);
        echo json_encode($data);
        exit;
    }
    
    // Helper method to send error response
    protected function sendError($message, $statusCode = 400) {
        http_response_code($statusCode);
        echo json_encode(['error' => $message]);
        exit;
    }
    
    // Helper method to get JSON input
    protected function getJSONInput() {
        $json = file_get_contents('php://input');
        return json_decode($json, true) ?? [];
    }
    
    // Helper method to validate required fields
    protected function validateRequired($data, $requiredFields) {
        $missing = [];
        foreach ($requiredFields as $field) {
            if (!isset($data[$field]) || empty($data[$field])) {
                $missing[] = $field;
            }
        }
        
        if (!empty($missing)) {
            $this->sendError('Missing required fields: ' . implode(', ', $missing));
        }
        
        return true;
    }
    
    // Helper method to authenticate user
    protected function authenticate() {
        $token = $this->getBearerToken();
        
        if (!$token) {
            $this->sendError('Authentication required', 401);
        }
        
        $user = $this->auth->validateToken($token);
        
        if (!$user) {
            $this->sendError('Invalid or expired token', 401);
        }
        
        return $user;
    }
    
    // Helper method to get Bearer token from headers
    private function getBearerToken() {
        $headers = getallheaders();
        if (isset($headers['Authorization'])) {
            $authHeader = $headers['Authorization'];
            if (preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
                return $matches[1];
            }
        }
        return null;
    }
    
    // Helper method to check user role
    protected function requireRole($requiredRole) {
        $user = $this->authenticate();
        
        if ($user['role'] !== $requiredRole && $user['role'] !== 'admin') {
            $this->sendError('Insufficient permissions', 403);
        }
        
        return $user;
    }
    
    // Helper method to bind parameters dynamically
    protected function bindParams($stmt, $types, $params) {
        if (empty($params)) {
            return;
        }
        
        // Convert to individual variables for bind_param
        $bindNames = [];
        for ($i = 0; $i < count($params); $i++) {
            $bindNames[] = '$param' . $i;
        }
        
        // Create dynamic bind_param call
        $bindCall = '$stmt->bind_param("' . $types . '", ' . implode(', ', $bindNames) . ');';
        
        // Execute the binding with variables in scope
        eval($bindCall);
    }
    
    // Abstract methods to be implemented by child classes
    abstract public function getAll();
    abstract public function getById($id);
    abstract public function create();
    abstract public function update($id);
    abstract public function delete($id);
}
?>
