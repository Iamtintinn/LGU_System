<?php
require_once 'BaseController.php';

class MaintenanceController extends BaseController {
    
    public function getAll() {
        $user = $this->authenticate();
        
        $sql = "SELECT ms.*, u.first_name, u.last_name, u.email as creator_email,
                au.first_name as assigned_first_name, au.last_name as assigned_last_name
                FROM maintenance_schedule ms 
                JOIN users u ON ms.created_by = u.id 
                LEFT JOIN users au ON ms.assigned_to = au.id";
        
        if ($user['role'] === 'citizen') {
            $sql .= " WHERE ms.created_by = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->bind_param("i", $user['id']);
        } else {
            $stmt = $this->db->prepare($sql);
        }
        
        $stmt->execute();
        $result = $stmt->get_result();
        
        $tasks = [];
        while ($row = $result->fetch_assoc()) {
            $tasks[] = $row;
        }
        
        $this->sendResponse($tasks);
    }
    
    public function getById($id) {
        $user = $this->authenticate();
        
        $sql = "SELECT ms.*, u.first_name, u.last_name, u.email as creator_email,
                au.first_name as assigned_first_name, au.last_name as assigned_last_name
                FROM maintenance_schedule ms 
                JOIN users u ON ms.created_by = u.id 
                LEFT JOIN users au ON ms.assigned_to = au.id 
                WHERE ms.id = ?";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            $this->sendError('Maintenance task not found', 404);
        }
        
        $task = $result->fetch_assoc();
        
        if ($user['role'] === 'citizen' && $task['created_by'] != $user['id']) {
            $this->sendError('Access denied', 403);
        }
        
        $this->sendResponse($task);
    }
    
    public function create() {
        $user = $this->authenticate();
        
        $data = $this->getJSONInput();
        $this->validateRequired($data, ['task_name', 'location', 'task_type', 'scheduled_date']);
        
        $task_id = 'MT-' . date('Y') . '-' . str_pad(mt_rand(1, 999), 3, '0', STR_PAD_LEFT);
        
        $sql = "INSERT INTO maintenance_schedule (task_id, task_name, description, location, task_type,
                priority, status, scheduled_date, estimated_duration, assigned_to, created_by) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("ssssssssiii", 
            $task_id, $data['task_name'], $data['description'] ?? null,
            $data['location'], $data['task_type'], $data['priority'] ?? 'medium',
            $data['status'] ?? 'scheduled', $data['scheduled_date'],
            $data['estimated_duration'] ?? null, $data['assigned_to'] ?? null, $user['id']
        );
        
        if ($stmt->execute()) {
            $this->sendResponse([
                'message' => 'Maintenance task created successfully',
                'task_id' => $task_id,
                'id' => $this->db->getLastInsertId()
            ], 201);
        } else {
            $this->sendError('Failed to create maintenance task');
        }
    }
    
    public function update($id) {
        $user = $this->authenticate();
        $data = $this->getJSONInput();
        
        $this->getById($id);
        
        $updateFields = [];
        $params = [];
        $types = '';
        
        $allowedFields = ['task_name', 'description', 'location', 'task_type', 'priority', 
                        'status', 'scheduled_date', 'estimated_duration', 'assigned_to', 
                        'completed_date', 'actual_duration', 'cost', 'materials_used', 'notes'];
        
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
        
        $sql = "UPDATE maintenance_schedule SET " . implode(', ', $updateFields) . " WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        
        $bindParams = array_merge([$types], $params);
        call_user_func_array([$stmt, 'bind_param'], $this->refValues($bindParams));
        
        if ($stmt->execute()) {
            $this->sendResponse(['message' => 'Maintenance task updated successfully']);
        } else {
            $this->sendError('Failed to update maintenance task');
        }
    }
    
    public function delete($id) {
        $user = $this->requireRole('admin');
        
        $this->getById($id);
        
        $sql = "DELETE FROM maintenance_schedule WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("i", $id);
        
        if ($stmt->execute()) {
            $this->sendResponse(['message' => 'Maintenance task deleted successfully']);
        } else {
            $this->sendError('Failed to delete maintenance task');
        }
    }
    
    private function getFieldType($field) {
        $stringFields = ['task_name', 'description', 'location', 'task_type', 'priority', 
                        'status', 'materials_used', 'notes'];
        $intFields = ['estimated_duration', 'assigned_to', 'created_by', 'actual_duration'];
        $doubleFields = ['cost'];
        
        if (in_array($field, $stringFields)) return 's';
        if (in_array($field, $intFields)) return 'i';
        if (in_array($field, $doubleFields)) return 'd';
        return 's';
    }
    
    private function refValues($arr) {
        $refs = [];
        foreach ($arr as $key => $value) {
            $refs[$key] = &$arr[$key];
        }
        return $refs;
    }
}
?>
