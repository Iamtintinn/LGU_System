<?php
require_once 'BaseController.php';

class DocumentController extends BaseController {
    
    public function getAll() {
        $user = $this->authenticate();
        
        $sql = "SELECT d.*, u.first_name, u.last_name, u.email as uploader_email
                FROM documents d 
                JOIN users u ON d.uploaded_by = u.id";
        
        if ($user['role'] === 'citizen') {
            $sql .= " WHERE d.uploaded_by = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->bind_param("i", $user['id']);
        } else {
            $stmt = $this->db->prepare($sql);
        }
        
        $stmt->execute();
        $result = $stmt->get_result();
        
        $documents = [];
        while ($row = $result->fetch_assoc()) {
            $documents[] = $row;
        }
        
        $this->sendResponse($documents);
    }
    
    public function getById($id) {
        $user = $this->authenticate();
        
        $sql = "SELECT d.*, u.first_name, u.last_name, u.email as uploader_email
                FROM documents d 
                JOIN users u ON d.uploaded_by = u.id 
                WHERE d.id = ?";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            $this->sendError('Document not found', 404);
        }
        
        $document = $result->fetch_assoc();
        
        // Check permissions
        if ($user['role'] === 'citizen' && $document['uploaded_by'] != $user['id']) {
            $this->sendError('Access denied', 403);
        }
        
        $this->sendResponse($document);
    }
    
    public function create() {
        $user = $this->authenticate();
        
        $data = $this->getJSONInput();
        $this->validateRequired($data, ['title', 'document_type', 'category', 'file_path']);
        
        $document_id = 'DOC-' . date('Y') . '-' . str_pad(mt_rand(1, 999), 3, '0', STR_PAD_LEFT);
        
        $sql = "INSERT INTO documents (document_id, title, description, document_type, category,
                file_path, file_size, mime_type, uploaded_by, related_id, related_type, is_public) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $this->db->prepare($sql);
        
        // Extract null values to variables
        $description = $data['description'] ?? null;
        $file_size = $data['file_size'] ?? null;
        $mime_type = $data['mime_type'] ?? null;
        $related_id = $data['related_id'] ?? null;
        $related_type = $data['related_type'] ?? null;
        $is_public = $data['is_public'] ?? 0;
        
        $stmt->bind_param("ssssssssiiii", 
            $document_id, 
            $data['title'], 
            $description,
            $data['document_type'], 
            $data['category'], 
            $data['file_path'],
            $file_size, 
            $mime_type,
            $user['id'], 
            $related_id, 
            $related_type,
            $is_public
        );
        
        if ($stmt->execute()) {
            $this->sendResponse([
                'message' => 'Document uploaded successfully',
                'document_id' => $document_id,
                'id' => $this->db->getLastInsertId()
            ], 201);
        } else {
            $this->sendError('Failed to upload document');
        }
    }
    
    public function update($id) {
        $user = $this->authenticate();
        $data = $this->getJSONInput();
        
        // Check if document exists and user has permission
        $existingDocument = $this->getByIdForUpdate($id, $user);
        
        $updateFields = [];
        $params = [];
        $types = '';
        
        $allowedFields = ['title', 'description', 'category', 'related_id', 'related_type', 'is_public'];
        
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
        
        $sql = "UPDATE documents SET " . implode(', ', $updateFields) . " WHERE id = ?";
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
            $this->sendResponse(['message' => 'Document updated successfully']);
        } else {
            $this->sendError('Failed to update document');
        }
    }
    
    public function delete($id) {
        $user = $this->authenticate();
        
        // Check if document exists and user has permission
        $document = $this->getByIdForDelete($id, $user);
        
        $sql = "DELETE FROM documents WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("i", $id);
        
        if ($stmt->execute()) {
            $this->sendResponse(['message' => 'Document deleted successfully']);
        } else {
            $this->sendError('Failed to delete document');
        }
    }
    
    private function getByIdForUpdate($id, $user) {
        $sql = "SELECT d.*, u.first_name, u.last_name, u.email as uploader_email
                FROM documents d 
                JOIN users u ON d.uploaded_by = u.id 
                WHERE d.id = ?";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            $this->sendError('Document not found', 404);
        }
        
        $document = $result->fetch_assoc();
        
        // Check permissions
        if ($user['role'] === 'citizen' && $document['uploaded_by'] != $user['id']) {
            $this->sendError('Access denied', 403);
        }
        
        return $document;
    }
    
    private function getByIdForDelete($id, $user) {
        $sql = "SELECT d.*, u.first_name, u.last_name, u.email as uploader_email
                FROM documents d 
                JOIN users u ON d.uploaded_by = u.id 
                WHERE d.id = ?";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            $this->sendError('Document not found', 404);
        }
        
        $document = $result->fetch_assoc();
        
        // Check if user can delete (admin or document owner)
        if ($user['role'] !== 'admin' && $document['uploaded_by'] != $user['id']) {
            $this->sendError('Access denied', 403);
        }
        
        return $document;
    }
    
    private function getFieldType($field) {
        $stringFields = ['title', 'description', 'document_type', 'category', 'related_type'];
        $intFields = ['uploaded_by', 'related_id', 'is_public'];
        
        if (in_array($field, $stringFields)) return 's';
        if (in_array($field, $intFields)) return 'i';
        return 's';
    }
}
?>
