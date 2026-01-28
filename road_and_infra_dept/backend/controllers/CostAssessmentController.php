<?php
require_once 'BaseController.php';

class CostAssessmentController extends BaseController {
    
    public function getAll() {
        $user = $this->authenticate();
        
        $sql = "SELECT ca.*, dr.location as damage_location, dr.description as damage_description,
                u.first_name, u.last_name, u.email as assessor_email
                FROM cost_assessments ca 
                JOIN damage_reports dr ON ca.damage_report_id = dr.id
                JOIN users u ON ca.assessor_id = u.id";
        
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
        
        $assessments = [];
        while ($row = $result->fetch_assoc()) {
            $assessments[] = $row;
        }
        
        $this->sendResponse($assessments);
    }
    
    public function getById($id) {
        $user = $this->authenticate();
        
        $sql = "SELECT ca.*, dr.location as damage_location, dr.description as damage_description,
                u.first_name, u.last_name, u.email as assessor_email
                FROM cost_assessments ca 
                JOIN damage_reports dr ON ca.damage_report_id = dr.id
                JOIN users u ON ca.assessor_id = u.id 
                WHERE ca.id = ?";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            $this->sendError('Cost assessment not found', 404);
        }
        
        $assessment = $result->fetch_assoc();
        
        // Check permissions
        if ($user['role'] === 'citizen' && $assessment['damage_report_id'] != $user['id']) {
            $this->sendError('Access denied', 403);
        }
        
        $this->sendResponse($assessment);
    }
    
    public function create() {
        $user = $this->requireRole('engineer');
        
        $data = $this->getJSONInput();
        $this->validateRequired($data, ['damage_report_id', 'total_cost']);
        
        // Generate assessment ID
        $assessment_id = 'CA-' . date('Y') . '-' . str_pad(mt_rand(1, 999), 3, '0', STR_PAD_LEFT);
        
        $sql = "INSERT INTO cost_assessments (assessment_id, damage_report_id, assessor_id, 
                labor_cost, material_cost, equipment_cost, total_cost, assessment_notes, status) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $this->db->prepare($sql);
        
        // Prepare variables for binding
        $damage_report_id = $data['damage_report_id'];
        $labor_cost = $data['labor_cost'] ?? 0;
        $material_cost = $data['material_cost'] ?? 0;
        $equipment_cost = $data['equipment_cost'] ?? 0;
        $total_cost = $data['total_cost'];
        $assessment_notes = $data['assessment_notes'] ?? null;
        $status = $data['status'] ?? 'draft';
        
        // Bind all parameters at once
        $stmt->bind_param("siidddsss", 
            $assessment_id, $damage_report_id, $user['id'], 
            $labor_cost, $material_cost, $equipment_cost, $total_cost, 
            $assessment_notes, $status
        );
        
        if ($stmt->execute()) {
            $this->sendResponse([
                'message' => 'Cost assessment created successfully',
                'assessment_id' => $assessment_id,
                'id' => $this->db->getLastInsertId()
            ], 201);
        } else {
            $this->sendError('Failed to create cost assessment');
        }
    }
    
    public function update($id) {
        $user = $this->authenticate();
        $data = $this->getJSONInput();
        
        // Check if assessment exists and user has permission
        $this->getById($id);
        
        $updateFields = [];
        $params = [];
        $types = '';
        
        $allowedFields = ['labor_cost', 'material_cost', 'equipment_cost', 'total_cost', 
                        'assessment_notes', 'status', 'approved_by', 'approved_at'];
        
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
        
        $sql = "UPDATE cost_assessments SET " . implode(', ', $updateFields) . " WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        
        // Prepare variables for binding
        $bindIndex = 0;
        $boundVars = [];
        
        foreach ($allowedFields as $field) {
            if (isset($data[$field])) {
                $boundVars[] = $data[$field];
                $bindIndex++;
            }
        }
        
        // Build bind parameters dynamically
        $types = '';
        $values = [];
        for ($i = 0; $i < count($boundVars); $i++) {
            $types .= $this->getFieldType($allowedFields[$i]);
            $values[] = $boundVars[$i];
        }
        
        // Add ID parameter
        $types .= 'i';
        $values[] = $id;
        
        // Use individual bind_param calls to avoid reference issues
        for ($i = 0; $i < count($values); $i++) {
            $stmt->bind_param($types[$i], $values[$i]);
        }
        
        if ($stmt->execute()) {
            $this->sendResponse(['message' => 'Cost assessment updated successfully']);
        } else {
            $this->sendError('Failed to update cost assessment');
        }
    }
    
    public function delete($id) {
        $user = $this->requireRole('admin');
        
        // Check if assessment exists
        $this->getById($id);
        
        $sql = "DELETE FROM cost_assessments WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("i", $id);
        
        if ($stmt->execute()) {
            $this->sendResponse(['message' => 'Cost assessment deleted successfully']);
        } else {
            $this->sendError('Failed to delete cost assessment');
        }
    }
    
    private function getFieldType($field) {
        $stringFields = ['assessment_notes', 'status'];
        $intFields = ['damage_report_id', 'assessor_id', 'approved_by'];
        $doubleFields = ['labor_cost', 'material_cost', 'equipment_cost', 'total_cost'];
        
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
