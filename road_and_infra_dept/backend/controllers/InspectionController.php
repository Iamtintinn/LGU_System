<?php
require_once 'BaseController.php';

class InspectionController extends BaseController {
    
    public function getAll() {
        $user = $this->authenticate();
        
        $sql = "SELECT ir.*, u.first_name, u.last_name, u.email as inspector_email
                FROM inspection_reports ir 
                JOIN users u ON ir.inspector_id = u.id";
        
        if ($user['role'] === 'citizen') {
            $sql .= " WHERE ir.inspector_id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->bind_param("i", $user['id']);
        } else {
            $stmt = $this->db->prepare($sql);
        }
        
        $stmt->execute();
        $result = $stmt->get_result();
        
        $inspections = [];
        while ($row = $result->fetch_assoc()) {
            $inspections[] = $row;
        }
        
        $this->sendResponse($inspections);
    }
    
    public function getById($id) {
        $user = $this->authenticate();
        
        $sql = "SELECT ir.*, u.first_name, u.last_name, u.email as inspector_email
                FROM inspection_reports ir 
                JOIN users u ON ir.inspector_id = u.id 
                WHERE ir.id = ?";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            $this->sendError('Inspection report not found', 404);
        }
        
        $inspection = $result->fetch_assoc();
        
        // Check permissions
        if ($user['role'] === 'citizen' && $inspection['inspector_id'] != $user['id']) {
            $this->sendError('Access denied', 403);
        }
        
        $this->sendResponse($inspection);
    }
    
    public function create() {
        $user = $this->requireRole(['engineer', 'admin']);
        
        $data = $this->getJSONInput();
        $this->validateRequired($data, ['location', 'inspection_type']);
        
        $inspection_id = 'IN-' . date('Y') . '-' . str_pad(mt_rand(1, 999), 3, '0', STR_PAD_LEFT);
        
        $sql = "INSERT INTO inspection_reports (inspection_id, inspector_id, location, inspection_type,
                findings, recommendations, inspection_status, scheduled_date, priority, images) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $this->db->prepare($sql);
        
        // Extract null values to variables
        $findings = $data['findings'] ?? null;
        $recommendations = $data['recommendations'] ?? null;
        $inspection_status = $data['inspection_status'] ?? 'scheduled';
        $scheduled_date = $data['scheduled_date'] ?? null;
        $priority = $data['priority'] ?? 'medium';
        $images = $data['images'] ?? null;
        
        $stmt->bind_param("sissssssss", 
            $inspection_id, 
            $user['id'], 
            $data['location'], 
            $data['inspection_type'],
            $findings, 
            $recommendations, 
            $inspection_status, 
            $scheduled_date,
            $priority, 
            $images
        );
        
        if ($stmt->execute()) {
            $this->sendResponse([
                'message' => 'Inspection report created successfully',
                'inspection_id' => $inspection_id,
                'id' => $this->db->getLastInsertId()
            ], 201);
        } else {
            $this->sendError('Failed to create inspection report');
        }
    }
    
    public function update($id) {
        $user = $this->authenticate();
        $data = $this->getJSONInput();
        
        // Check if inspection exists and user has permission
        $existingInspection = $this->getByIdForUpdate($id, $user);
        
        $updateFields = [];
        $params = [];
        $types = '';
        
        $allowedFields = ['location', 'inspection_type', 'findings', 'recommendations', 
                        'inspection_status', 'scheduled_date', 'completed_date', 'priority', 'images'];
        
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
        
        $sql = "UPDATE inspection_reports SET " . implode(', ', $updateFields) . " WHERE id = ?";
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
            $this->sendResponse(['message' => 'Inspection report updated successfully']);
        } else {
            $this->sendError('Failed to update inspection report');
        }
    }
    
    public function delete($id) {
        $user = $this->authenticate();
        
        // Check if inspection exists and user has permission
        $existingInspection = $this->getByIdForDelete($id, $user);
        
        // Check permissions (only admin or inspection creator can delete)
        if ($user['role'] !== 'admin' && $existingInspection['inspector_id'] != $user['id']) {
            $this->sendError('Access denied', 403);
        }
        
        $sql = "DELETE FROM inspection_reports WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("i", $id);
        
        if ($stmt->execute()) {
            $this->sendResponse(['message' => 'Inspection report deleted successfully']);
        } else {
            $this->sendError('Failed to delete inspection report');
        }
    }
    
    public function getByStatus($status) {
        $user = $this->authenticate();
        
        $sql = "SELECT ir.*, u.first_name, u.last_name, u.email as inspector_email
                FROM inspection_reports ir 
                JOIN users u ON ir.inspector_id = u.id 
                WHERE ir.inspection_status = ?";
        
        if ($user['role'] === 'citizen') {
            $sql .= " AND ir.inspector_id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->bind_param("si", $status, $user['id']);
        } else {
            $stmt = $this->db->prepare($sql);
            $stmt->bind_param("s", $status);
        }
        
        $stmt->execute();
        $result = $stmt->get_result();
        
        $inspections = [];
        while ($row = $result->fetch_assoc()) {
            $inspections[] = $row;
        }
        
        $this->sendResponse($inspections);
    }
    
    public function getByDateRange($startDate, $endDate) {
        $user = $this->authenticate();
        
        $sql = "SELECT ir.*, u.first_name, u.last_name, u.email as inspector_email
                FROM inspection_reports ir 
                JOIN users u ON ir.inspector_id = u.id 
                WHERE ir.scheduled_date BETWEEN ? AND ?";
        
        if ($user['role'] === 'citizen') {
            $sql .= " AND ir.inspector_id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->bind_param("ssi", $startDate, $endDate, $user['id']);
        } else {
            $stmt = $this->db->prepare($sql);
            $stmt->bind_param("ss", $startDate, $endDate);
        }
        
        $stmt->execute();
        $result = $stmt->get_result();
        
        $inspections = [];
        while ($row = $result->fetch_assoc()) {
            $inspections[] = $row;
        }
        
        $this->sendResponse($inspections);
    }
    
    public function getMyInspections() {
        $user = $this->authenticate();
        
        $sql = "SELECT ir.*, u.first_name, u.last_name, u.email as inspector_email
                FROM inspection_reports ir 
                JOIN users u ON ir.inspector_id = u.id 
                WHERE ir.inspector_id = ?";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("i", $user['id']);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $inspections = [];
        while ($row = $result->fetch_assoc()) {
            $inspections[] = $row;
        }
        
        $this->sendResponse($inspections);
    }
    
    private function getByIdForUpdate($id, $user) {
        $sql = "SELECT ir.*, u.first_name, u.last_name, u.email as inspector_email
                FROM inspection_reports ir 
                JOIN users u ON ir.inspector_id = u.id 
                WHERE ir.id = ?";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            $this->sendError('Inspection report not found', 404);
        }
        
        $inspection = $result->fetch_assoc();
        
        // Check permissions
        if ($user['role'] === 'citizen' && $inspection['inspector_id'] != $user['id']) {
            $this->sendError('Access denied', 403);
        }
        
        return $inspection;
    }
    
    private function getByIdForDelete($id, $user) {
        $sql = "SELECT ir.*, u.first_name, u.last_name, u.email as inspector_email
                FROM inspection_reports ir 
                JOIN users u ON ir.inspector_id = u.id 
                WHERE ir.id = ?";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            $this->sendError('Inspection report not found', 404);
        }
        
        return $result->fetch_assoc();
    }
    
    private function getFieldType($field) {
        $stringFields = ['location', 'inspection_type', 'findings', 'recommendations', 
                        'inspection_status', 'images'];
        $dateFields = ['scheduled_date', 'completed_date'];
        
        if (in_array($field, $stringFields)) return 's';
        if (in_array($field, $dateFields)) return 's';
        return 's';
    }
}
?>
