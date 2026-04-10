-- Table to track all service changes including updates and availability toggles
CREATE TABLE IF NOT EXISTS service_history (
    id INT AUTO_INCREMENT PRIMARY KEY,
    service_id INT NOT NULL,
    service_type ENUM('photo', 'print') NOT NULL,
    action_type ENUM('created', 'edited', 'availability_changed', 'deleted') NOT NULL,
    old_values JSON,
    new_values JSON,
    changed_by VARCHAR(100),
    changed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (service_id) REFERENCES photo_services(id) ON DELETE CASCADE,
    INDEX idx_service_id (service_id),
    INDEX idx_service_type (service_type),
    INDEX idx_action_type (action_type),
    INDEX idx_changed_at (changed_at)
);

-- Note: For print services, we can use the same table with service_id referencing print_services
-- This is a practical approach since both tables have similar structure
