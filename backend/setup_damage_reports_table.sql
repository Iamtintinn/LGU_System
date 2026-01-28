-- Create damage_reports table for citizen road damage reporting
CREATE TABLE IF NOT EXISTS damage_reports (
    id INT AUTO_INCREMENT PRIMARY KEY,
    report_id VARCHAR(20) UNIQUE NOT NULL,
    reporter_id INT NULL,
    location VARCHAR(255) NOT NULL,
    barangay VARCHAR(100) NOT NULL,
    damage_type VARCHAR(50) NOT NULL,
    severity ENUM('low', 'medium', 'high', 'urgent') NOT NULL DEFAULT 'medium',
    description TEXT NOT NULL,
    estimated_size VARCHAR(100) NULL,
    traffic_impact ENUM('none', 'minor', 'moderate', 'severe', 'blocked') DEFAULT 'moderate',
    contact_number VARCHAR(20) NULL,
    anonymous_report TINYINT(1) DEFAULT 0,
    images JSON,
    status ENUM('pending', 'under_review', 'approved', 'in_progress', 'completed', 'rejected') DEFAULT 'pending',
    assigned_to INT NULL,
    lgu_notes TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (reporter_id) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (assigned_to) REFERENCES users(id) ON DELETE SET NULL
);

-- Create indexes for better performance
CREATE INDEX idx_damage_reports_status ON damage_reports(status);
CREATE INDEX idx_damage_reports_created_at ON damage_reports(created_at);
CREATE INDEX idx_damage_reports_severity ON damage_reports(severity);
CREATE INDEX idx_damage_reports_location ON damage_reports(location);

-- Insert sample data for testing
INSERT INTO damage_reports (report_id, reporter_id, location, barangay, damage_type, severity, description, status) VALUES
('DR-2025-001', 1, 'Main Street', 'Poblacion', 'pothole', 'high', 'Large pothole causing traffic congestion', 'pending'),
('DR-2025-002', 2, 'Riverside Boulevard', 'Barangay 1', 'crack', 'medium', 'Longitudinal crack along the road shoulder', 'under_review'),
('DR-2025-003', 3, 'Quezon Avenue corner EDSA', 'San Miguel', 'flooding', 'urgent', 'Severe flooding during heavy rains', 'approved');
