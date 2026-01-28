<?php
require_once 'BaseController.php';

class GISController extends BaseController {
    
    public function getAll() {
        $user = $this->authenticate();
        
        $sql = "SELECT gd.*, u.first_name, u.last_name, u.email as creator_email
                FROM gis_data gd 
                JOIN users u ON gd.created_by = u.id";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $features = [];
        while ($row = $result->fetch_assoc()) {
            $features[] = $row;
        }
        
        $this->sendResponse($features);
    }
    
    public function getById($id) {
        $user = $this->authenticate();
        
        $sql = "SELECT gd.*, u.first_name, u.last_name, u.email as creator_email
                FROM gis_data gd 
                JOIN users u ON gd.created_by = u.id 
                WHERE gd.id = ?";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            $this->sendError('GIS feature not found', 404);
        }
        
        $feature = $result->fetch_assoc();
        $this->sendResponse($feature);
    }
    
    public function create() {
        $user = $this->requireRole(['engineer', 'admin']);
        
        $data = $this->getJSONInput();
        $this->validateRequired($data, ['feature_type', 'name', 'latitude', 'longitude']);
        
        $feature_id = 'GIS-' . str_pad(mt_rand(1, 999), 3, '0', STR_PAD_LEFT);
        
        $sql = "INSERT INTO gis_data (feature_id, feature_type, name, description, 
                latitude, longitude, properties, status, created_by) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $properties = isset($data['properties']) ? json_encode($data['properties']) : null;
        $description = $data['description'] ?? null;
        $status = $data['status'] ?? 'active';
        
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("sssddsssi", 
            $feature_id, 
            $data['feature_type'], 
            $data['name'], 
            $description,
            $data['latitude'], 
            $data['longitude'],
            $properties, 
            $status, 
            $user['id']
        );
        
        if ($stmt->execute()) {
            $this->sendResponse([
                'message' => 'GIS feature created successfully',
                'feature_id' => $feature_id,
                'id' => $this->db->getLastInsertId()
            ], 201);
        } else {
            $this->sendError('Failed to create GIS feature');
        }
    }
    
    public function update($id) {
        $user = $this->authenticate();
        $data = $this->getJSONInput();
        
        // Check if feature exists
        $existingFeature = $this->getByIdForUpdate($id);
        
        // Check permissions (only admin, engineer, or creator can update)
        if ($user['role'] !== 'admin' && $user['role'] !== 'engineer' && $existingFeature['created_by'] != $user['id']) {
            $this->sendError('Access denied', 403);
        }
        
        $updateFields = [];
        $params = [];
        $types = '';
        
        $allowedFields = ['feature_type', 'name', 'description', 'latitude', 'longitude', 
                        'properties', 'status'];
        
        foreach ($allowedFields as $field) {
            if (isset($data[$field])) {
                $updateFields[] = "$field = ?";
                $params[] = ($field === 'properties') ? json_encode($data[$field]) : $data[$field];
                $types .= 's';
            }
        }
        
        if (empty($updateFields)) {
            $this->sendError('No valid fields to update');
        }
        
        $params[] = $id;
        $types .= 'i';
        
        $sql = "UPDATE gis_data SET " . implode(', ', $updateFields) . " WHERE id = ?";
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
            $this->sendResponse(['message' => 'GIS feature updated successfully']);
        } else {
            $this->sendError('Failed to update GIS feature');
        }
    }
    
    public function delete($id) {
        $user = $this->authenticate();
        
        // Check if feature exists and user has permission
        $existingFeature = $this->getByIdForDelete($id);
        
        // Check permissions (only admin or feature creator can delete)
        if ($user['role'] !== 'admin' && $existingFeature['created_by'] != $user['id']) {
            $this->sendError('Access denied', 403);
        }
        
        $sql = "DELETE FROM gis_data WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("i", $id);
        
        if ($stmt->execute()) {
            $this->sendResponse(['message' => 'GIS feature deleted successfully']);
        } else {
            $this->sendError('Failed to delete GIS feature');
        }
    }
    
    public function getByType($type) {
        $user = $this->authenticate();
        
        $sql = "SELECT gd.*, u.first_name, u.last_name, u.email as creator_email
                FROM gis_data gd 
                JOIN users u ON gd.created_by = u.id 
                WHERE gd.feature_type = ?";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("s", $type);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $features = [];
        while ($row = $result->fetch_assoc()) {
            $features[] = $row;
        }
        
        $this->sendResponse($features);
    }
    
    public function getByBounds($bounds) {
        $user = $this->authenticate();
        
        // Expected bounds format: {minLat, maxLat, minLng, maxLng}
        $this->validateRequired($bounds, ['minLat', 'maxLat', 'minLng', 'maxLng']);
        
        $sql = "SELECT gd.*, u.first_name, u.last_name, u.email as creator_email
                FROM gis_data gd 
                JOIN users u ON gd.created_by = u.id 
                WHERE gd.latitude BETWEEN ? AND ? 
                AND gd.longitude BETWEEN ? AND ?";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("dddd", 
            $bounds['minLat'], $bounds['maxLat'], 
            $bounds['minLng'], $bounds['maxLng']
        );
        $stmt->execute();
        $result = $stmt->get_result();
        
        $features = [];
        while ($row = $result->fetch_assoc()) {
            $features[] = $row;
        }
        
        $this->sendResponse($features);
    }
    
    private function getByIdForUpdate($id, $user = null) {
        $sql = "SELECT gd.*, u.first_name, u.last_name, u.email as creator_email
                FROM gis_data gd 
                JOIN users u ON gd.created_by = u.id 
                WHERE gd.id = ?";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            $this->sendError('GIS feature not found', 404);
        }
        
        return $result->fetch_assoc();
    }
    
    private function getByIdForDelete($id, $user = null) {
        $sql = "SELECT gd.*, u.first_name, u.last_name, u.email as creator_email
                FROM gis_data gd 
                JOIN users u ON gd.created_by = u.id 
                WHERE gd.id = ?";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            $this->sendError('GIS feature not found', 404);
        }
        
        return $result->fetch_assoc();
    }
}
?>
