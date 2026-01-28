<?php
require_once 'BaseController.php';

class AnalyticsController extends BaseController {
    
    public function getAll() {
        $user = $this->requireRole('admin');
        
        $analytics = [
            'overview' => $this->getOverviewStats(),
            'monthly_trends' => $this->getMonthlyTrends(),
            'module_distribution' => $this->getModuleDistribution(),
            'recent_activity' => $this->getRecentActivity(),
            'status_breakdown' => $this->getStatusBreakdown()
        ];
        
        $this->sendResponse($analytics);
    }
    
    public function getById($id) {
        $user = $this->requireRole('admin');
        
        // Get specific analytics based on ID
        switch ($id) {
            case 'overview':
                $this->sendResponse($this->getOverviewStats());
                break;
            case 'trends':
                $this->sendResponse($this->getMonthlyTrends());
                break;
            case 'distribution':
                $this->sendResponse($this->getModuleDistribution());
                break;
            case 'activity':
                $this->sendResponse($this->getRecentActivity());
                break;
            case 'status':
                $this->sendResponse($this->getStatusBreakdown());
                break;
            default:
                $this->sendError('Analytics endpoint not found', 404);
                break;
        }
    }
    
    public function create() {
        $this->sendError('Method not allowed for analytics', 405);
    }
    
    public function update($id) {
        $this->sendError('Method not allowed for analytics', 405);
    }
    
    public function delete($id) {
        $this->sendError('Method not allowed for analytics', 405);
    }
    
    private function getOverviewStats() {
        $stats = [];
        
        // Damage Reports
        $sql = "SELECT COUNT(*) as total, 
                SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
                SUM(CASE WHEN status = 'in_progress' THEN 1 ELSE 0 END) as in_progress,
                SUM(CASE WHEN status = 'resolved' THEN 1 ELSE 0 END) as resolved
                FROM damage_reports";
        $result = $this->db->query($sql);
        $stats['damage_reports'] = $result->fetch_assoc();
        
        // Cost Assessments
        $sql = "SELECT COUNT(*) as total, 
                SUM(CASE WHEN status = 'draft' THEN 1 ELSE 0 END) as draft,
                SUM(CASE WHEN status = 'submitted' THEN 1 ELSE 0 END) as submitted,
                SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) as approved,
                SUM(total_cost) as total_value
                FROM cost_assessments";
        $result = $this->db->query($sql);
        $stats['cost_assessments'] = $result->fetch_assoc();
        
        // Inspections
        $sql = "SELECT COUNT(*) as total,
                SUM(CASE WHEN inspection_status = 'scheduled' THEN 1 ELSE 0 END) as scheduled,
                SUM(CASE WHEN inspection_status = 'completed' THEN 1 ELSE 0 END) as completed
                FROM inspection_reports";
        $result = $this->db->query($sql);
        $stats['inspections'] = $result->fetch_assoc();
        
        // GIS Features
        $sql = "SELECT COUNT(*) as total,
                SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active
                FROM gis_data";
        $result = $this->db->query($sql);
        $stats['gis_data'] = $result->fetch_assoc();
        
        // Maintenance Tasks
        $sql = "SELECT COUNT(*) as total,
                SUM(CASE WHEN status = 'scheduled' THEN 1 ELSE 0 END) as scheduled,
                SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed
                FROM maintenance_schedule";
        $result = $this->db->query($sql);
        $stats['maintenance'] = $result->fetch_assoc();
        
        return $stats;
    }
    
    private function getMonthlyTrends() {
        $trends = [];
        
        // Get last 6 months of data
        for ($i = 5; $i >= 0; $i--) {
            $month = date('Y-m', strtotime("-$i months"));
            $monthName = date('M', strtotime("-$i months"));
            
            // Damage Reports
            $sql = "SELECT COUNT(*) as count FROM damage_reports 
                    WHERE DATE_FORMAT(created_at, '%Y-%m') = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->bind_param("s", $month);
            $stmt->execute();
            $result = $stmt->get_result();
            $damageCount = $result->fetch_assoc()['count'];
            
            // Cost Assessments
            $sql = "SELECT COUNT(*) as count FROM cost_assessments 
                    WHERE DATE_FORMAT(assessment_date, '%Y-%m') = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->bind_param("s", $month);
            $stmt->execute();
            $result = $stmt->get_result();
            $costCount = $result->fetch_assoc()['count'];
            
            // Inspections
            $sql = "SELECT COUNT(*) as count FROM inspection_reports 
                    WHERE DATE_FORMAT(created_at, '%Y-%m') = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->bind_param("s", $month);
            $stmt->execute();
            $result = $stmt->get_result();
            $inspectionCount = $result->fetch_assoc()['count'];
            
            // GIS Data
            $sql = "SELECT COUNT(*) as count FROM gis_data 
                    WHERE DATE_FORMAT(created_at, '%Y-%m') = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->bind_param("s", $month);
            $stmt->execute();
            $result = $stmt->get_result();
            $gisCount = $result->fetch_assoc()['count'];
            
            $trends[] = [
                'month' => $monthName,
                'damage_reports' => $damageCount,
                'cost_assessments' => $costCount,
                'inspections' => $inspectionCount,
                'gis_data' => $gisCount
            ];
        }
        
        return $trends;
    }
    
    private function getModuleDistribution() {
        $distribution = [];
        
        // Get counts for each module
        $modules = [
            'damage_reports' => 'damage_reports',
            'cost_assessments' => 'cost_assessments', 
            'inspection_reports' => 'inspection_reports',
            'gis_data' => 'gis_data',
            'maintenance_schedule' => 'maintenance_schedule',
            'documents' => 'documents',
            'public_announcements' => 'public_announcements'
        ];
        
        foreach ($modules as $key => $table) {
            $sql = "SELECT COUNT(*) as count FROM $table";
            $result = $this->db->query($sql);
            $count = $result->fetch_assoc()['count'];
            $distribution[] = [
                'module' => str_replace('_', ' ', $key),
                'count' => $count
            ];
        }
        
        return $distribution;
    }
    
    private function getRecentActivity() {
        $activities = [];
        
        // Get recent activities from different tables
        $sql = "SELECT 'Damage Report' as type, location as description, created_at, 
                CONCAT(first_name, ' ', last_name) as user
                FROM damage_reports dr
                JOIN users u ON dr.reporter_id = u.id
                ORDER BY dr.created_at DESC LIMIT 5";
        
        $result = $this->db->query($sql);
        while ($row = $result->fetch_assoc()) {
            $activities[] = [
                'type' => $row['type'],
                'description' => 'New damage report: ' . $row['description'],
                'user' => $row['user'],
                'created_at' => $row['created_at']
            ];
        }
        
        return $activities;
    }
    
    private function getStatusBreakdown() {
        $breakdown = [];
        
        // Damage Reports by Status
        $sql = "SELECT status, COUNT(*) as count 
                FROM damage_reports 
                GROUP BY status";
        $result = $this->db->query($sql);
        $breakdown['damage_reports'] = $result->fetch_all(MYSQLI_ASSOC);
        
        // Cost Assessments by Status
        $sql = "SELECT status, COUNT(*) as count 
                FROM cost_assessments 
                GROUP BY status";
        $result = $this->db->query($sql);
        $breakdown['cost_assessments'] = $result->fetch_all(MYSQLI_ASSOC);
        
        // Inspections by Status
        $sql = "SELECT inspection_status as status, COUNT(*) as count 
                FROM inspection_reports 
                GROUP BY inspection_status";
        $result = $this->db->query($sql);
        $breakdown['inspections'] = $result->fetch_all(MYSQLI_ASSOC);
        
        return $breakdown;
    }
}
?>
