<?php
require_once 'BaseController.php';

class UserController extends BaseController {
    
    public function getAll() {
        $user = $this->requireRole('admin');
        
        $sql = "SELECT id, first_name, middle_name, last_name, email, role, status, 
                email_verified, phone, address, created_at, updated_at, last_login 
                FROM users 
                ORDER BY created_at DESC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $users = [];
        while ($row = $result->fetch_assoc()) {
            unset($row['password']); // Never return password
            $users[] = $row;
        }
        
        $this->sendResponse($users);
    }
    
    public function getById($id) {
        $user = $this->authenticate();
        
        // Users can only view their own profile, admins can view all
        if ($user['role'] !== 'admin' && $user['id'] != $id) {
            $this->sendError('Access denied', 403);
        }
        
        $sql = "SELECT id, first_name, middle_name, last_name, email, role, status, 
                email_verified, phone, address, created_at, updated_at, last_login 
                FROM users 
                WHERE id = ?";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            $this->sendError('User not found', 404);
        }
        
        $userData = $result->fetch_assoc();
        unset($userData['password']); // Never return password
        
        $this->sendResponse($userData);
    }
    
    public function create() {
        $user = $this->requireRole('admin');
        
        $data = $this->getJSONInput();
        $this->validateRequired($data, ['first_name', 'last_name', 'email', 'password', 'role']);
        
        // Check if email already exists
        $checkSql = "SELECT id FROM users WHERE email = ?";
        $checkStmt = $this->db->prepare($checkSql);
        $checkStmt->bind_param("s", $data['email']);
        $checkStmt->execute();
        $checkResult = $checkStmt->get_result();
        
        if ($checkResult->num_rows > 0) {
            $this->sendError('Email already exists');
        }
        
        // Hash password
        $hashedPassword = password_hash($data['password'], PASSWORD_DEFAULT);
        
        $sql = "INSERT INTO users (first_name, middle_name, last_name, email, password, 
                role, status, phone, address, email_verified) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("sssssssssi", 
            $data['first_name'], $data['middle_name'] ?? null, $data['last_name'],
            $data['email'], $hashedPassword, $data['role'], $data['status'] ?? 'pending',
            $data['phone'] ?? null, $data['address'] ?? null, $data['email_verified'] ?? 0
        );
        
        if ($stmt->execute()) {
            $this->sendResponse([
                'message' => 'User created successfully',
                'id' => $this->db->getLastInsertId()
            ], 201);
        } else {
            $this->sendError('Failed to create user');
        }
    }
    
    public function update($id) {
        $currentUser = $this->authenticate();
        $data = $this->getJSONInput();
        
        // Check permissions
        if ($currentUser['role'] !== 'admin' && $currentUser['id'] != $id) {
            $this->sendError('Access denied', 403);
        }
        
        // Get existing user
        $this->getById($id);
        
        $updateFields = [];
        $params = [];
        $types = '';
        
        $allowedFields = ['first_name', 'middle_name', 'last_name', 'phone', 'address'];
        
        // Only admins can change role and status
        if ($currentUser['role'] === 'admin') {
            $allowedFields[] = 'role';
            $allowedFields[] = 'status';
            $allowedFields[] = 'email_verified';
        }
        
        // Handle password update separately
        if (isset($data['password'])) {
            $hashedPassword = password_hash($data['password'], PASSWORD_DEFAULT);
            $updateFields[] = "password = ?";
            $params[] = $hashedPassword;
            $types .= 's';
        }
        
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
        
        $sql = "UPDATE users SET " . implode(', ', $updateFields) . " WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        
        $bindParams = array_merge([$types], $params);
        call_user_func_array([$stmt, 'bind_param'], $this->refValues($bindParams));
        
        if ($stmt->execute()) {
            $this->sendResponse(['message' => 'User updated successfully']);
        } else {
            $this->sendError('Failed to update user');
        }
    }
    
    public function delete($id) {
        $user = $this->requireRole('admin');
        
        // Prevent self-deletion
        if ($user['id'] == $id) {
            $this->sendError('Cannot delete your own account');
        }
        
        // Check if user exists
        $this->getById($id);
        
        $sql = "DELETE FROM users WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("i", $id);
        
        if ($stmt->execute()) {
            $this->sendResponse(['message' => 'User deleted successfully']);
        } else {
            $this->sendError('Failed to delete user');
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
