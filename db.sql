-- ==============================================
-- PRODUCTION DASHBOARD - COMPLETE DATABASE
-- ==============================================

-- Create database
CREATE DATABASE IF NOT EXISTS production_dashboard;
USE production_dashboard;

-- ==============================================
-- LOCATIONS TABLE
-- ==============================================
CREATE TABLE IF NOT EXISTS locations (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(50) UNIQUE NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ==============================================
-- MACHINES TABLE (COMPLETE WITH ALL FIELDS)
-- ==============================================
CREATE TABLE IF NOT EXISTS machines (
    id INT PRIMARY KEY AUTO_INCREMENT,
    machine_id VARCHAR(20) UNIQUE NOT NULL,
    work_order VARCHAR(100) NULL,
    name VARCHAR(100) NOT NULL,
    location_id INT,
    status ENUM('idle', 'running', 'stopped', 'completed') DEFAULT 'idle',
    current_product VARCHAR(100),
    current_description TEXT NULL,
    current_brand VARCHAR(100) NULL,
    current_customer VARCHAR(200) NULL,
    current_color VARCHAR(50),
    current_size VARCHAR(20),
    target_qty INT DEFAULT 0,
    produced_qty INT DEFAULT 0,
    current_speed DECIMAL(5,2) DEFAULT 2.5,
    auto_speed BOOLEAN DEFAULT TRUE,
    stop_reason TEXT,
    completed_at DATETIME NULL,
    last_updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    last_updated_by VARCHAR(20) DEFAULT 'browser',
    version INT DEFAULT 1, 
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (location_id) REFERENCES locations(id) ON DELETE SET NULL,
    INDEX idx_status (status),
    INDEX idx_machine_id (machine_id),
    INDEX idx_location (location_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ==============================================
-- COMPLETED ORDERS TABLE
-- ==============================================
CREATE TABLE IF NOT EXISTS completed_orders (
    id INT PRIMARY KEY AUTO_INCREMENT,
    location_name VARCHAR(50),
    machine_name VARCHAR(100),
    machine_id VARCHAR(20),
    work_order VARCHAR(100) NULL,
    product VARCHAR(100),
    description TEXT NULL,
    brand VARCHAR(100) NULL,
    customer_name VARCHAR(200) NULL,
    color VARCHAR(50),
    size VARCHAR(20),
    target_qty INT DEFAULT 0,
    produced_qty INT DEFAULT 0,
    completed_at DATETIME,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_completed_at (completed_at),
    INDEX idx_machine_id (machine_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ==============================================
-- MACHINE DOWNTIME TABLE
-- ==============================================
CREATE TABLE IF NOT EXISTS machine_downtime (
    id INT PRIMARY KEY AUTO_INCREMENT,
    machine_id VARCHAR(20),
    machine_name VARCHAR(100),
    location_name VARCHAR(50),
    stop_reason VARCHAR(100),
    stopped_at DATETIME,
    resumed_at DATETIME,
    downtime_minutes INT,
    status VARCHAR(20) DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_status (status),
    INDEX idx_stopped_at (stopped_at),
    INDEX idx_machine_id (machine_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ==============================================
-- USERS TABLE
-- ==============================================
CREATE TABLE IF NOT EXISTS users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(50) NOT NULL,
    role ENUM('operator', 'manager') DEFAULT 'operator',
    location_id INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_login TIMESTAMP NULL,
    FOREIGN KEY (location_id) REFERENCES locations(id) ON DELETE SET NULL,
    INDEX idx_role (role),
    INDEX idx_username (username)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ==============================================
-- STOP REASONS TABLE
-- ==============================================
CREATE TABLE IF NOT EXISTS stop_reasons (
    id INT PRIMARY KEY AUTO_INCREMENT,
    reason VARCHAR(100) UNIQUE NOT NULL,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ==============================================
-- WASTE ITEMS TABLE
-- ==============================================
CREATE TABLE IF NOT EXISTS waste_items (
    id INT PRIMARY KEY AUTO_INCREMENT,
    machine_id VARCHAR(20) NOT NULL,
    machine_name VARCHAR(100),
    location_name VARCHAR(50),
    product_name VARCHAR(100) NOT NULL,
    color VARCHAR(50),
    size VARCHAR(20),
    waste_quantity INT NOT NULL,
    waste_reason VARCHAR(50),
    notes TEXT,
    reported_by VARCHAR(50),
    reported_at DATETIME NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_reported_at (reported_at),
    INDEX idx_machine_id (machine_id),
    INDEX idx_waste_reason (waste_reason)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ==============================================
-- SETTINGS TABLE
-- ==============================================
CREATE TABLE IF NOT EXISTS settings (
    id INT PRIMARY KEY AUTO_INCREMENT,
    setting_key VARCHAR(100) UNIQUE NOT NULL,
    setting_value TEXT,
    description VARCHAR(255),
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ==============================================
-- SPLIT ORDERS TABLE
-- ==============================================
CREATE TABLE IF NOT EXISTS split_orders (
    id INT PRIMARY KEY AUTO_INCREMENT,
    parent_order_id VARCHAR(100) NOT NULL,
    machine_id VARCHAR(20) NOT NULL,
    location_name VARCHAR(50),
    customer_name VARCHAR(200),
    product_name VARCHAR(100) NOT NULL,
    brand VARCHAR(100),
    description TEXT,
    color VARCHAR(50),
    size VARCHAR(20),
    allocated_qty INT NOT NULL,
    produced_qty INT DEFAULT 0,
    status ENUM('pending', 'running', 'completed') DEFAULT 'pending',
    started_at DATETIME,
    completed_at DATETIME NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (machine_id) REFERENCES machines(machine_id) ON DELETE CASCADE,
    INDEX idx_parent_order (parent_order_id),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ==============================================
-- ORDER TRACKING TABLE
-- ==============================================
CREATE TABLE IF NOT EXISTS order_tracking (
    id INT PRIMARY KEY AUTO_INCREMENT,
    order_id VARCHAR(100) UNIQUE NOT NULL,
    customer_name VARCHAR(200),
    total_qty INT NOT NULL,
    allocated_qty INT DEFAULT 0,
    produced_qty INT DEFAULT 0,
    remaining_qty INT DEFAULT 0,
    status ENUM('pending', 'in_progress', 'completed') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ==============================================
-- ========== STOCK MANAGEMENT TABLES ==========
-- ==============================================

-- 1. RAW MATERIALS TABLE
CREATE TABLE IF NOT EXISTS raw_materials (
    id INT PRIMARY KEY AUTO_INCREMENT,
    material_name VARCHAR(100) NOT NULL UNIQUE,
    unit VARCHAR(20) DEFAULT 'bags',
    min_stock_level DECIMAL(10,2) DEFAULT 5,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_material (material_name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 2. STOCK LEVELS BY LOCATION
CREATE TABLE IF NOT EXISTS stock_levels (
    id INT PRIMARY KEY AUTO_INCREMENT,
    material_id INT NOT NULL,
    location_name VARCHAR(50) NOT NULL,
    quantity DECIMAL(10,2) DEFAULT 0,
    last_updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (material_id) REFERENCES raw_materials(id) ON DELETE CASCADE,
    UNIQUE KEY unique_material_location (material_id, location_name),
    INDEX idx_location (location_name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 3. STOCK USAGE RECORDS
CREATE TABLE IF NOT EXISTS stock_usage (
    id INT PRIMARY KEY AUTO_INCREMENT,
    location_name VARCHAR(50) NOT NULL,
    shift ENUM('day', 'night') NOT NULL,
    material_id INT NOT NULL,
    material_name VARCHAR(100) NOT NULL,
    bags_used DECIMAL(10,2) NOT NULL,
    reported_by VARCHAR(50) NOT NULL,
    reported_at DATETIME NOT NULL,
    report_date DATE NOT NULL,
    notes TEXT,
    FOREIGN KEY (material_id) REFERENCES raw_materials(id) ON DELETE CASCADE,
    INDEX idx_location (location_name),
    INDEX idx_date (report_date),
    INDEX idx_shift (shift)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 4. STOCK HISTORY (AUDIT TRAIL)
CREATE TABLE IF NOT EXISTS stock_history (
    id INT PRIMARY KEY AUTO_INCREMENT,
    material_id INT NOT NULL,
    material_name VARCHAR(100) NOT NULL,
    location_name VARCHAR(50),
    shift ENUM('day', 'night'),
    old_stock DECIMAL(10,2),
    new_stock DECIMAL(10,2),
    change_type ENUM('usage', 'restock', 'adjustment', 'initial') NOT NULL,
    changed_by VARCHAR(50) NOT NULL,
    changed_at DATETIME NOT NULL,
    notes TEXT,
    FOREIGN KEY (material_id) REFERENCES raw_materials(id) ON DELETE CASCADE,
    INDEX idx_material (material_id),
    INDEX idx_changed_at (changed_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ==============================================
-- INSERT LOCATIONS
-- ==============================================
INSERT IGNORE INTO locations (name) VALUES 
('Modan'), 
('Baldeya'), 
('Al-Khraj');

-- ==============================================
-- INSERT USERS
-- ==============================================
INSERT IGNORE INTO users (username, password, role, location_id) VALUES
('1111', '1111', 'operator', 1),
('2222', '2222', 'operator', 2),
('3333', '3333', 'operator', 3),
('Admin', '12345', 'manager', NULL),
('admin', 'admin', 'manager', NULL);  -- Extra admin for testing

-- ==============================================
-- INSERT STOP REASONS
-- ==============================================
INSERT IGNORE INTO stop_reasons (reason) VALUES
('Mechanical Breakdown'),
('Material Shortage'),
('Power Failure'),
('Die Change'),
('Operator Issue'),
('Quality Check'),
('Tool Change'),
('Cleaning'),
('Maintenance'),
('No Operator'),
('Power Cut'),
('Raw Material Issue'),
('Planning Hold'),
('Break Time'),
('Setup Time'),
('Waiting for Material');

-- ==============================================
-- INSERT DEFAULT SETTINGS
-- ==============================================
INSERT IGNORE INTO settings (setting_key, setting_value, description) VALUES
('auto_refresh_interval', '30', 'Auto refresh interval in seconds'),
('default_speed', '2.5', 'Default machine speed'),
('cron_enabled', '1', 'Enable/disable cron job'),
('waste_reasons', 'electricity,mechanical,material,operator,other', 'Available waste reasons'),
('last_cron_run', '0', 'Timestamp of last CRON run');

-- ==============================================
-- INSERT RAW MATERIALS (STOCK)
-- ==============================================
INSERT IGNORE INTO raw_materials (material_name, unit, min_stock_level) VALUES
('HDPE 952', 'bags', 10),
('HDPE - F9584', 'bags', 5),
('Blue PE 9034', 'bags', 5),
('Grey PE-7009', 'bags', 5),
('Green PE-60033-8', 'bags', 5),
('Red PE-1010', 'bags', 5),
('Orange PE-531', 'bags', 5),
('White PE-6070', 'bags', 5),
('Yellow 256-Q', 'bags', 3),
('Browni Pa-491', 'bags', 5),
('Of Grade (HDPE)', 'bags', 5),
('PP OF GRADE', 'bags', 5),
('PP polypropylene', 'bags', 10),
('COPPER WIRE 0.80MM', 'rolls', 3),
('LLLDPE', 'bags', 5),
('FIRE RETARDENT', 'bags', 5);

-- ==============================================
-- INITIALIZE STOCK LEVELS (ZERO FOR ALL LOCATIONS)
-- ==============================================
-- First, get all material IDs and locations
INSERT IGNORE INTO stock_levels (material_id, location_name, quantity)
SELECT rm.id, 'Modan', 0
FROM raw_materials rm;

INSERT IGNORE INTO stock_levels (material_id, location_name, quantity)
SELECT rm.id, 'Baldeya', 0
FROM raw_materials rm;

INSERT IGNORE INTO stock_levels (material_id, location_name, quantity)
SELECT rm.id, 'Al-Khraj', 0
FROM raw_materials rm;

-- ==============================================
-- INSERT 45 MACHINES (15 per location)
-- ==============================================
-- Modan machines (Location ID: 1)
INSERT IGNORE INTO machines (machine_id, work_order, name, location_id, current_speed, auto_speed, current_description, current_brand, current_customer, last_updated_by) VALUES
('M1', NULL, 'Machine 1', 1, 2.5, TRUE, NULL, NULL, NULL, 'browser'),
('M2', NULL, 'Machine 2', 1, 2.5, TRUE, NULL, NULL, NULL, 'browser'),
('M3', NULL, 'Machine 3', 1, 2.5, TRUE, NULL, NULL, NULL, 'browser'),
('M4', NULL, 'Machine 4', 1, 2.5, TRUE, NULL, NULL, NULL, 'browser'),
('M5', NULL, 'Machine 5', 1, 2.5, TRUE, NULL, NULL, NULL, 'browser'),
('M6', NULL, 'Machine 6', 1, 2.5, TRUE, NULL, NULL, NULL, 'browser'),
('M7', NULL, 'Machine 7', 1, 2.5, TRUE, NULL, NULL, NULL, 'browser'),
('M8', NULL, 'Machine 8', 1, 2.5, TRUE, NULL, NULL, NULL, 'browser'),
('M9', NULL, 'Machine 9', 1, 2.5, TRUE, NULL, NULL, NULL, 'browser'),
('M10', NULL, 'Machine 10', 1, 2.5, TRUE, NULL, NULL, NULL, 'browser'),
('M11', NULL, 'Machine 11', 1, 2.5, TRUE, NULL, NULL, NULL, 'browser'),
('M12', NULL, 'Machine 12', 1, 2.5, TRUE, NULL, NULL, NULL, 'browser'),
('M13', NULL, 'Machine 13', 1, 2.5, TRUE, NULL, NULL, NULL, 'browser'),
('M14', NULL, 'Machine 14', 1, 2.5, TRUE, NULL, NULL, NULL, 'browser'),
('M15', NULL, 'Machine 15', 1, 2.5, TRUE, NULL, NULL, NULL, 'browser');

-- Baldeya machines (Location ID: 2)
INSERT IGNORE INTO machines (machine_id, work_order, name, location_id, current_speed, auto_speed, current_description, current_brand, current_customer, last_updated_by) VALUES
('B1', NULL, 'Machine 1', 2, 2.5, TRUE, NULL, NULL, NULL, 'browser'),
('B2', NULL, 'Machine 2', 2, 2.5, TRUE, NULL, NULL, NULL, 'browser'),
('B3', NULL, 'Machine 3', 2, 2.5, TRUE, NULL, NULL, NULL, 'browser'),
('B4', NULL, 'Machine 4', 2, 2.5, TRUE, NULL, NULL, NULL, 'browser'),
('B5', NULL, 'Machine 5', 2, 2.5, TRUE, NULL, NULL, NULL, 'browser'),
('B6', NULL, 'Machine 6', 2, 2.5, TRUE, NULL, NULL, NULL, 'browser'),
('B7', NULL, 'Machine 7', 2, 2.5, TRUE, NULL, NULL, NULL, 'browser'),
('B8', NULL, 'Machine 8', 2, 2.5, TRUE, NULL, NULL, NULL, 'browser'),
('B9', NULL, 'Machine 9', 2, 2.5, TRUE, NULL, NULL, NULL, 'browser'),
('B10', NULL, 'Machine 10', 2, 2.5, TRUE, NULL, NULL, NULL, 'browser'),
('B11', NULL, 'Machine 11', 2, 2.5, TRUE, NULL, NULL, NULL, 'browser'),
('B12', NULL, 'Machine 12', 2, 2.5, TRUE, NULL, NULL, NULL, 'browser'),
('B13', NULL, 'Machine 13', 2, 2.5, TRUE, NULL, NULL, NULL, 'browser'),
('B14', NULL, 'Machine 14', 2, 2.5, TRUE, NULL, NULL, NULL, 'browser'),
('B15', NULL, 'Machine 15', 2, 2.5, TRUE, NULL, NULL, NULL, 'browser');

-- Al-Khraj machines (Location ID: 3)
INSERT IGNORE INTO machines (machine_id, work_order, name, location_id, current_speed, auto_speed, current_description, current_brand, current_customer, last_updated_by) VALUES
('A1', NULL, 'Machine 1', 3, 2.5, TRUE, NULL, NULL, NULL, 'browser'),
('A2', NULL, 'Machine 2', 3, 2.5, TRUE, NULL, NULL, NULL, 'browser'),
('A3', NULL, 'Machine 3', 3, 2.5, TRUE, NULL, NULL, NULL, 'browser'),
('A4', NULL, 'Machine 4', 3, 2.5, TRUE, NULL, NULL, NULL, 'browser'),
('A5', NULL, 'Machine 5', 3, 2.5, TRUE, NULL, NULL, NULL, 'browser'),
('A6', NULL, 'Machine 6', 3, 2.5, TRUE, NULL, NULL, NULL, 'browser'),
('A7', NULL, 'Machine 7', 3, 2.5, TRUE, NULL, NULL, NULL, 'browser'),
('A8', NULL, 'Machine 8', 3, 2.5, TRUE, NULL, NULL, NULL, 'browser'),
('A9', NULL, 'Machine 9', 3, 2.5, TRUE, NULL, NULL, NULL, 'browser'),
('A10', NULL, 'Machine 10', 3, 2.5, TRUE, NULL, NULL, NULL, 'browser'),
('A11', NULL, 'Machine 11', 3, 2.5, TRUE, NULL, NULL, NULL, 'browser'),
('A12', NULL, 'Machine 12', 3, 2.5, TRUE, NULL, NULL, NULL, 'browser'),
('A13', NULL, 'Machine 13', 3, 2.5, TRUE, NULL, NULL, NULL, 'browser'),
('A14', NULL, 'Machine 14', 3, 2.5, TRUE, NULL, NULL, NULL, 'browser'),
('A15', NULL, 'Machine 15', 3, 2.5, TRUE, NULL, NULL, NULL, 'browser');

-- ==============================================
-- UPDATE MACHINE SPEEDS BASED ON SIZE
-- ==============================================
UPDATE machines SET current_speed = 4.8 WHERE current_size = '20mm' AND current_speed = 2.5;
UPDATE machines SET current_speed = 4.2 WHERE current_size = '25mm' AND current_speed = 2.5;
UPDATE machines SET current_speed = 3.8 WHERE current_size = '32mm' AND current_speed = 2.5;
UPDATE machines SET current_speed = 3.2 WHERE current_size = '40mm' AND current_speed = 2.5;
UPDATE machines SET current_speed = 2.8 WHERE current_size = '50mm' AND current_speed = 2.5;
UPDATE machines SET current_speed = 2.2 WHERE current_size = '63mm' AND current_speed = 2.5;
UPDATE machines SET current_speed = 1.8 WHERE current_size = '75mm' AND current_speed = 2.5;
UPDATE machines SET current_speed = 1.2 WHERE current_size = '90mm' AND current_speed = 2.5;
UPDATE machines SET current_speed = 0.73 WHERE current_size = '110mm' AND current_speed = 2.5;

-- ==============================================
-- VERIFICATION QUERIES
-- ==============================================

-- Check all tables
SHOW TABLES;

-- Check counts
SELECT 'locations' AS table_name, COUNT(*) AS count FROM locations
UNION ALL
SELECT 'users', COUNT(*) FROM users
UNION ALL
SELECT 'machines', COUNT(*) FROM machines
UNION ALL
SELECT 'stop_reasons', COUNT(*) FROM stop_reasons
UNION ALL
SELECT 'completed_orders', COUNT(*) FROM completed_orders
UNION ALL
SELECT 'machine_downtime', COUNT(*) FROM machine_downtime
UNION ALL
SELECT 'waste_items', COUNT(*) FROM waste_items
UNION ALL
SELECT 'settings', COUNT(*) FROM settings
UNION ALL
SELECT 'split_orders', COUNT(*) FROM split_orders
UNION ALL
SELECT 'order_tracking', COUNT(*) FROM order_tracking
UNION ALL
SELECT 'raw_materials', COUNT(*) FROM raw_materials
UNION ALL
SELECT 'stock_levels', COUNT(*) FROM stock_levels
UNION ALL
SELECT 'stock_usage', COUNT(*) FROM stock_usage
UNION ALL
SELECT 'stock_history', COUNT(*) FROM stock_history;

-- Show machine table structure
DESCRIBE machines;

-- Show stock tables structure
DESCRIBE raw_materials;
DESCRIBE stock_levels;
DESCRIBE stock_usage;
DESCRIBE stock_history;

-- Show initial stock data
SELECT 
    rm.material_name,
    SUM(CASE WHEN sl.location_name = 'Modan' THEN sl.quantity ELSE 0 END) as Modan,
    SUM(CASE WHEN sl.location_name = 'Baldeya' THEN sl.quantity ELSE 0 END) as Baldeya,
    SUM(CASE WHEN sl.location_name = 'Al-Khraj' THEN sl.quantity ELSE 0 END) as Al_Khraj,
    SUM(sl.quantity) as Total
FROM raw_materials rm
LEFT JOIN stock_levels sl ON rm.id = sl.material_id
GROUP BY rm.id
ORDER BY rm.material_name;

-- ==============================================
-- FINAL VERIFICATION
-- ==============================================
SELECT 
    '✅ DATABASE CREATION COMPLETE' AS status,
    NOW() AS completed_at,
    (SELECT COUNT(*) FROM machines) AS total_machines,
    (SELECT COUNT(*) FROM locations) AS total_locations,
    (SELECT COUNT(*) FROM users) AS total_users,
    (SELECT COUNT(*) FROM raw_materials) AS total_materials;