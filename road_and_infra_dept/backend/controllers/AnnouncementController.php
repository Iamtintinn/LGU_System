<?php
require_once 'BaseController.php';

class AnnouncementController extends BaseController {
    
    public function getAll() {
        $user = $this->authenticate();
        
        $sql = "SELECT pa.*, u.first_name, u.last_name, u.email as creator_email
                FROM public_announcements pa 
                JOIN users u ON pa.created_by = u.id 
                WHERE pa.is_active = 1 
                ORDER BY pa.created_at DESC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $announcements = [];
        while ($row = $result->fetch_assoc()) {
            $announcements[] = $row;
        }
        
        $this->sendResponse($announcements);
    }
    
    public function getById($id) {
        $user = $this->authenticate();
        
        $sql = "SELECT pa.*, u.first_name, u.last_name, u.email as creator_email
                FROM public_announcements pa 
                JOIN users u ON pa.created_by = u.id 
                WHERE pa.id = ? AND pa.is_active = 1";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            $this->sendError('Announcement not found', 404);
        }
        
        $announcement = $result->fetch_assoc();
        $this->sendResponse($announcement);
    }
    
    public function create() {
        $user = $this->requireRole('lgu_officer');
        
        $data = $this->getJSONInput();
        $this->validateRequired($data, ['title', 'content']);
        
        $announcement_id = 'ANN-' . date('Y') . '-' . str_pad(mt_rand(1, 999), 3, '0', STR_PAD_LEFT);
        
        $sql = "INSERT INTO public_announcements (announcement_id, title, content, announcement_type,
                priority, is_active, start_date, end_date, target_audience, created_by) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $this->db->prepare($sql);
        
        // Prepare variables for binding
        $title = $data['title'];
        $content = $data['content'];
        $announcement_type = $data['announcement_type'] ?? 'general';
        $priority = $data['priority'] ?? 'medium';
        $is_active = $data['is_active'] ?? 1;
        $start_date = $data['start_date'] ?? date('Y-m-d H:i:s');
        $end_date = $data['end_date'] ?? null;
        $target_audience = $data['target_audience'] ?? 'all';
        $created_by = $user['id'];
        
        // Bind all parameters at once
        $stmt->bind_param("sssiisssi", 
            $announcement_id, $title, $content, $announcement_type, 
            $priority, $is_active, $start_date, $end_date, 
            $target_audience, $created_by
        );
        
        if ($stmt->execute()) {
            $this->sendResponse([
                'message' => 'Announcement created successfully',
                'announcement_id' => $announcement_id,
                'id' => $this->db->getLastInsertId()
            ], 201);
        } else {
            $this->sendError('Failed to create announcement');
        }
    }
    
    public function update($id) {
        $user = $this->requireRole('lgu_officer');
        $data = $this->getJSONInput();
        
        $this->getById($id);
        
        $updateFields = [];
        $params = [];
        $types = '';
        
        $allowedFields = ['title', 'content', 'announcement_type', 'priority', 
                        'is_active', 'start_date', 'end_date', 'target_audience'];
        
        foreach ($allowedFields as $field) {
            if (isset($data[$field])) {
                $updateFields[] = "$field = ?";
                $params[] = $data[$field];
                $types .= 's';
            }
        }
        
        if (empty($updateFields)) {
            $this->sendError('No valid fields to update');
        }
        
        $params[] = $id;
        $types .= 'i';
        
        $sql = "UPDATE public_announcements SET " . implode(', ', $updateFields) . " WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        
        $bindParams = array_merge([$types], $params);
        call_user_func_array([$stmt, 'bind_param'], $this->refValues($bindParams));
        
        if ($stmt->execute()) {
            $this->sendResponse(['message' => 'Announcement updated successfully']);
        } else {
            $this->sendError('Failed to update announcement');
        }
    }
    
    public function delete($id) {
        $user = $this->requireRole('admin');
        
        $this->getById($id);
        
        $sql = "DELETE FROM public_announcements WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("i", $id);
        
        if ($stmt->execute()) {
            $this->sendResponse(['message' => 'Announcement deleted successfully']);
        } else {
            $this->sendError('Failed to delete announcement');
        }
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
