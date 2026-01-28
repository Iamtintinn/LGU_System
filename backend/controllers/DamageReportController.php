<?php
require_once 'BaseController.php';

class DamageReportController extends BaseController {
    
    public function getAll() {
        $user = $this->authenticate();
        
        // Build query based on user role
        $sql = "SELECT dr.*, u.first_name, u.last_name, u.email as reporter_email 
                FROM damage_reports dr 
                JOIN users u ON dr.reporter_id = u.id";
        
        // Add role-based filtering
        if ($user['role'] === 'citizen') {
            $sql .= " WHERE dr.reporter_id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->bind_param("i", $user['id']);
        } else {
            $stmt = $this->db->prepare($sql);
        }
        
        $stmt->execute();
        $result = $stmt->get_result();
        
        $reports = [];
        while ($row = $result->fetch_assoc()) {
            $reports[] = $row;
        }
        
        $this->sendResponse($reports);
    }
    
    public function getById($id) {
        $user = $this->authenticate();
        
        $sql = "SELECT dr.*, u.first_name, u.last_name, u.email as reporter_email 
                FROM damage_reports dr 
                JOIN users u ON dr.reporter_id = u.id 
                WHERE dr.id = ?";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            $this->sendError('Damage report not found', 404);
        }
        
        $report = $result->fetch_assoc();
        
        // Check permissions
        if ($user['role'] === 'citizen' && $report['reporter_id'] != $user['id']) {
            $this->sendError('Access denied', 403);
        }
        
        $this->sendResponse($report);
    }
    
    public function create() {
        $user = $this->authenticate();
        
        $data = $this->getJSONInput();
        $this->validateRequired($data, ['location', 'description', 'severity']);
        
        // Generate report ID
        $report_id = 'DR-' . date('Y') . '-' . str_pad(mt_rand(1, 999), 3, '0', STR_PAD_LEFT);
        
        $sql = "INSERT INTO damage_reports (report_id, reporter_id, location, description, severity, 
                latitude, longitude, estimated_cost, images) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $this->db->prepare($sql);
        $latitude = $data['latitude'] ?? null;
        $longitude = $data['longitude'] ?? null;
        $estimated_cost = $data['estimated_cost'] ?? null;
        $images = $data['images'] ?? null;
        
        $stmt->bind_param("sisssddss", 
            $report_id, 
            $user['id'], 
            $data['location'], 
            $data['description'], 
            $data['severity'],
            $latitude, 
            $longitude, 
            $estimated_cost, 
            $images
        );
        
        if ($stmt->execute()) {
            $this->sendResponse([
                'message' => 'Damage report created successfully',
                'report_id' => $report_id,
                'id' => $this->db->getLastInsertId()
            ], 201);
        } else {
            $this->sendError('Failed to create damage report');
        }
    }
    
    public function update($id) {
        $user = $this->authenticate();
        $data = $this->getJSONInput();
        
        // Check if report exists and user has permission
        $existingReport = $this->getByIdForUpdate($id, $user);
        
        // Build dynamic update query
        $updateFields = [];
        $params = [];
        $types = '';
        
        $allowedFields = ['location', 'description', 'severity', 'status', 'assigned_to', 
                        'latitude', 'longitude', 'estimated_cost', 'images'];
        
        foreach ($allowedFields as $field) {
            if (isset($data[$field])) {
                $updateFields[] = "$field = ?";
                $params[] = $data[$field];
                $types .= $this->getFieldType($field);
            }
        }
        
        if (empty($updateFields)) {
            $this->sendError('No valid fields to update');
        }
        
        $params[] = $id;
        $types .= 'i';
        
        $sql = "UPDATE damage_reports SET " . implode(', ', $updateFields) . " WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        
        if ($stmt === false) {
            $this->sendError('Failed to prepare statement');
        }
        
        // Create references for bind_param
        $refs = [];
        $refs[] = $types;
        foreach ($params as $key => $value) {
            $refs[] = &$params[$key];
        }
        
        // Use call_user_func_array to bind parameters dynamically
        call_user_func_array([$stmt, 'bind_param'], $refs);
        
        if ($stmt->execute()) {
            $this->sendResponse(['message' => 'Damage report updated successfully']);
        } else {
            $this->sendError('Failed to update damage report');
        }
    }
    
    public function delete($id) {
        $user = $this->authenticate();
        
        // Only admin can delete, or user can delete their own reports
        if ($user['role'] !== 'admin') {
            // Check if user owns this report
            $checkStmt = $this->db->prepare("SELECT reporter_id FROM damage_reports WHERE id = ?");
            $checkStmt->bind_param("i", $id);
            $checkStmt->execute();
            $result = $checkStmt->get_result();
            
            if ($result->num_rows === 0) {
                $this->sendError('Damage report not found', 404);
            }
            
            $report = $result->fetch_assoc();
            if ($report['reporter_id'] != $user['id']) {
                $this->sendError('Access denied', 403);
            }
        }
        
        $sql = "DELETE FROM damage_reports WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("i", $id);
        
        if ($stmt->execute()) {
            $this->sendResponse(['message' => 'Damage report deleted successfully']);
        } else {
            $this->sendError('Failed to delete damage report');
        }
    }
    
    private function getByIdForUpdate($id, $user) {
        $sql = "SELECT dr.*, u.first_name, u.last_name, u.email as reporter_email 
                FROM damage_reports dr 
                JOIN users u ON dr.reporter_id = u.id 
                WHERE dr.id = ?";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            $this->sendError('Damage report not found', 404);
        }
        
        $report = $result->fetch_assoc();
        
        // Check permissions
        if ($user['role'] === 'citizen' && $report['reporter_id'] != $user['id']) {
            $this->sendError('Access denied', 403);
        }
        
        return $report;
    }
    
    private function getFieldType($field) {
        $stringFields = ['location', 'description', 'severity', 'status', 'images'];
        $intFields = ['assigned_to'];
        $doubleFields = ['latitude', 'longitude', 'estimated_cost'];
        
        if (in_array($field, $stringFields)) return 's';
        if (in_array($field, $intFields)) return 'i';
        if (in_array($field, $doubleFields)) return 'd';
        return 's';
    }
}
?>
