-- Create LGU inspection workflow tables
-- This script creates the necessary tables for the LGU inspection approval workflow

-- Create lgu_inspections table for LGU workflow
CREATE TABLE IF NOT EXISTS lgu_inspections (
    id INT AUTO_INCREMENT PRIMARY KEY,
    inspection_id VARCHAR(50) UNIQUE NOT NULL,
    location VARCHAR(255) NOT NULL,
    inspection_date DATE NOT NULL,
    severity ENUM('low', 'medium', 'high', 'urgent') NOT NULL DEFAULT 'medium',
    description TEXT NOT NULL,
    coordinates VARCHAR(100) NULL,
    estimated_cost DECIMAL(12,2) DEFAULT 0.00,
    priority ENUM('low', 'medium', 'high') NOT NULL DEFAULT 'medium',
    engineer_id INT NOT NULL,
    photos JSON NULL,
    notes TEXT NULL,
    status ENUM('pending_approval', 'approved', 'rejected', 'in_progress', 'completed') DEFAULT 'pending_approval',
    review_date DATE NULL,
    reviewed_by INT NULL,
    review_notes TEXT NULL,
    submitted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (engineer_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (reviewed_by) REFERENCES users(id) ON DELETE SET NULL
);

-- Add task_type column to repair_tasks table if it doesn't exist
ALTER TABLE repair_tasks 
ADD COLUMN IF NOT EXISTS task_type ENUM('regular', 'lgu_inspection') DEFAULT 'regular' AFTER status;

-- Create indexes for better performance
CREATE INDEX idx_lgu_inspections_status ON lgu_inspections(status);
CREATE INDEX idx_lgu_inspections_engineer_id ON lgu_inspections(engineer_id);
CREATE INDEX idx_lgu_inspections_submitted_at ON lgu_inspections(submitted_at);
CREATE INDEX idx_lgu_inspections_severity ON lgu_inspections(severity);
CREATE INDEX idx_lgu_inspections_location ON lgu_inspections(location);

-- Create repair_tasks index for task_type
CREATE INDEX idx_repair_tasks_type ON repair_tasks(task_type);

-- Update notifications table to handle LGU inspection notifications
ALTER TABLE notifications 
MODIFY COLUMN type ENUM('inspection_report', 'lgu_inspection', 'repair_update', 'system') DEFAULT 'inspection_report';

-- Insert sample LGU inspection data for testing
INSERT INTO lgu_inspections (
    inspection_id, location, inspection_date, severity, description, 
    coordinates, estimated_cost, priority, engineer_id, photos, 
    notes, status
) VALUES 
(
    'LGU-INSP-2025-0001', 
    'National Highway, Brgy. San Jose', 
    '2025-01-15', 
    'high', 
    'Major road damage requiring immediate attention. Multiple potholes and surface deterioration affecting traffic flow.',
    '14.5995° N, 120.9842° E', 
    75000.00, 
    'high', 
    1, 
    '["lgu_damage1.jpg", "lgu_damage2.jpg"]', 
    'Critical infrastructure damage requiring immediate LGU intervention',
    'pending_approval'
),
(
    'LGU-INSP-2025-0002', 
    'Municipal Bridge Approach', 
    '2025-01-14', 
    'urgent', 
    'Structural damage to bridge approach posing safety hazards. Immediate repair required.',
    '14.6010° N, 120.9890° E', 
    125000.00, 
    'high', 
    2, 
    '["bridge_damage1.jpg", "bridge_damage2.jpg", "bridge_damage3.jpg"]', 
    'Safety critical issue - bridge approach compromised',
    'pending_approval'
),
(
    'LGU-INSP-2025-0003', 
    'Main Street Commercial Area', 
    '2025-01-13', 
    'medium', 
    'Surface cracking and minor potholes in high-traffic commercial district.',
    '14.6025° N, 120.9855° E', 
    35000.00, 
    'medium', 
    1, 
    '["commercial_damage1.jpg"]', 
    'Regular maintenance required for commercial area',
    'approved'
);

-- Create a view for unified inspection reporting
CREATE OR REPLACE VIEW unified_inspections AS
SELECT 
    'regular' as inspection_type,
    i.inspection_id,
    i.location,
    i.inspection_date,
    i.severity,
    i.description,
    i.coordinates,
    i.estimated_cost,
    i.priority,
    i.inspector_id as staff_id,
    u.name as staff_name,
    i.photos,
    i.review_notes as notes,
    i.status,
    i.review_date,
    i.reviewed_by,
    i.created_at as submission_date
FROM inspections i
LEFT JOIN users u ON i.inspector_id = u.id

UNION ALL

SELECT 
    'lgu' as inspection_type,
    li.inspection_id,
    li.location,
    li.inspection_date,
    li.severity,
    li.description,
    li.coordinates,
    li.estimated_cost,
    li.priority,
    li.engineer_id as staff_id,
    u.name as staff_name,
    li.photos,
    li.notes,
    li.status,
    li.review_date,
    li.reviewed_by,
    li.submitted_at as submission_date
FROM lgu_inspections li
LEFT JOIN users u ON li.engineer_id = u.id;

-- Create triggers for automatic notification creation
DELIMITER //

CREATE TRIGGER IF NOT EXISTS after_lgu_inspection_insert
AFTER INSERT ON lgu_inspections
FOR EACH ROW
BEGIN
    -- Create notification for LGU officers when new inspection is submitted
    INSERT INTO notifications (user_id, type, title, message, data, created_at, read_status)
    SELECT u.id, 'lgu_inspection', 'New LGU Inspection Report', 
           CONCAT('Engineer has submitted LGU inspection report: ', NEW.inspection_id), 
           JSON_OBJECT(
               'inspection_id', NEW.inspection_id,
               'location', NEW.location,
               'severity', NEW.severity,
               'type', 'lgu_workflow'
           ), 
           NEW.submitted_at, 0
    FROM users u 
    WHERE u.role = 'lgu_officer' OR u.role = 'admin';
END//

CREATE TRIGGER IF NOT EXISTS after_lgu_inspection_update
AFTER UPDATE ON lgu_inspections
FOR EACH ROW
BEGIN
    -- Create notification for engineer when LGU inspection is reviewed
    IF OLD.status != NEW.status AND NEW.status IN ('approved', 'rejected') THEN
        INSERT INTO notifications (user_id, type, title, message, data, created_at, read_status)
        SELECT NEW.engineer_id, 'lgu_inspection', 
               CONCAT('LGU Inspection ', NEW.status), 
               CONCAT('Your LGU inspection report ', NEW.inspection_id, ' has been ', NEW.status), 
               JSON_OBJECT(
                   'inspection_id', NEW.inspection_id,
                   'status', NEW.status,
                   'review_notes', NEW.review_notes,
                   'type', 'lgu_workflow'
               ), 
               NOW(), 0;
    END IF;
END//

DELIMITER ;

-- Create stored procedure for inspection statistics
DELIMITER //

CREATE PROCEDURE IF NOT EXISTS GetInspectionStatistics()
BEGIN
    SELECT 
        'Total Inspections' as metric,
        COUNT(*) as value
    FROM (
        SELECT inspection_id FROM inspections
        UNION ALL
        SELECT inspection_id FROM lgu_inspections
    ) as all_inspections
    
    UNION ALL
    
    SELECT 
        'Pending Approvals' as metric,
        COUNT(*) as value
    FROM (
        SELECT inspection_id FROM inspections WHERE status = 'pending'
        UNION ALL
        SELECT inspection_id FROM lgu_inspections WHERE status = 'pending_approval'
    ) as pending_inspections
    
    UNION ALL
    
    SELECT 
        'Repairs In Progress' as metric,
        COUNT(*) as value
    FROM repair_tasks 
    WHERE status = 'in_progress'
    
    UNION ALL
    
    SELECT 
        'Completed Repairs' as metric,
        COUNT(*) as value
    FROM repair_tasks 
    WHERE status = 'completed';
END//

DELIMITER ;

-- Grant necessary permissions (adjust as needed for your setup)
-- GRANT SELECT, INSERT, UPDATE, DELETE ON lgu_inspections TO 'your_user'@'localhost';
-- GRANT SELECT, INSERT, UPDATE, DELETE ON repair_tasks TO 'your_user'@'localhost';
-- GRANT SELECT ON unified_inspections TO 'your_user'@'localhost';
-- GRANT EXECUTE ON PROCEDURE GetInspectionStatistics TO 'your_user'@'localhost';

-- Display completion message
SELECT 'LGU Workflow tables created successfully!' as message;
