-- ================================================================
-- TASSNE ALLADAEN - COMPLETE DATABASE
-- Production Dashboard + Material Management System (MMS)
-- ================================================================
-- HOW TO USE:
-- Fresh install  → Run full file in phpMyAdmin > SQL tab
-- Existing live  → Run only the MIGRATION section at the bottom
-- ================================================================

CREATE DATABASE IF NOT EXISTS production_dashboard;
USE production_dashboard;

-- ================================================================
-- TABLE 1: LOCATIONS
-- ================================================================
CREATE TABLE IF NOT EXISTS locations (
    id   INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(50) UNIQUE NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ================================================================
-- TABLE 2: MACHINES
-- ================================================================
CREATE TABLE IF NOT EXISTS machines (
    id                  INT PRIMARY KEY AUTO_INCREMENT,
    machine_id          VARCHAR(20) UNIQUE NOT NULL,
    work_order          VARCHAR(100) NULL,
    name                VARCHAR(100) NOT NULL,
    location_id         INT,
    status              ENUM('idle','running','stopped','completed') DEFAULT 'idle',
    current_product     VARCHAR(100),
    current_description TEXT NULL,
    current_brand       VARCHAR(100) NULL,
    current_customer    VARCHAR(200) NULL,
    current_color       VARCHAR(50),
    current_size        VARCHAR(20),
    target_qty          INT DEFAULT 0,
    produced_qty        INT DEFAULT 0,
    current_speed       DECIMAL(5,2) DEFAULT 2.5,
    auto_speed          BOOLEAN DEFAULT TRUE,
    stop_reason         TEXT,
    completed_at        DATETIME NULL,
    last_updated        TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    last_updated_by     VARCHAR(20) DEFAULT 'browser',
    version             INT DEFAULT 1,
    created_at          TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (location_id) REFERENCES locations(id) ON DELETE SET NULL,
    INDEX idx_status     (status),
    INDEX idx_machine_id (machine_id),
    INDEX idx_location   (location_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ================================================================
-- TABLE 3: COMPLETED ORDERS
-- ================================================================
CREATE TABLE IF NOT EXISTS completed_orders (
    id            INT PRIMARY KEY AUTO_INCREMENT,
    location_name VARCHAR(50),
    machine_name  VARCHAR(100),
    machine_id    VARCHAR(20),
    work_order    VARCHAR(100) NULL,
    product       VARCHAR(100),
    description   TEXT NULL,
    brand         VARCHAR(100) NULL,
    customer_name VARCHAR(200) NULL,
    color         VARCHAR(50),
    size          VARCHAR(20),
    target_qty    INT DEFAULT 0,
    produced_qty  INT DEFAULT 0,
    completed_at  DATETIME,
    created_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_completed_at (completed_at),
    INDEX idx_machine_id   (machine_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ================================================================
-- TABLE 4: MACHINE DOWNTIME
-- ================================================================
CREATE TABLE IF NOT EXISTS machine_downtime (
    id               INT PRIMARY KEY AUTO_INCREMENT,
    machine_id       VARCHAR(20),
    machine_name     VARCHAR(100),
    location_name    VARCHAR(50),
    stop_reason      VARCHAR(100),
    stopped_at       DATETIME,
    resumed_at       DATETIME,
    downtime_minutes INT,
    status           VARCHAR(20) DEFAULT 'active',
    created_at       TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_status     (status),
    INDEX idx_stopped_at (stopped_at),
    INDEX idx_machine_id (machine_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ================================================================
-- TABLE 5: USERS (VARCHAR 255 for future password security)
-- ================================================================
CREATE TABLE IF NOT EXISTS users (
    id          INT PRIMARY KEY AUTO_INCREMENT,
    username    VARCHAR(50) UNIQUE NOT NULL,
    password    VARCHAR(255) NOT NULL,
    role        ENUM('operator','manager') DEFAULT 'operator',
    location_id INT,
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_login  TIMESTAMP NULL,
    FOREIGN KEY (location_id) REFERENCES locations(id) ON DELETE SET NULL,
    INDEX idx_role     (role),
    INDEX idx_username (username)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ================================================================
-- TABLE 6: STOP REASONS
-- ================================================================
CREATE TABLE IF NOT EXISTS stop_reasons (
    id         INT PRIMARY KEY AUTO_INCREMENT,
    reason     VARCHAR(100) UNIQUE NOT NULL,
    is_active  BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ================================================================
-- TABLE 7: WASTE ITEMS
-- ================================================================
CREATE TABLE IF NOT EXISTS waste_items (
    id             INT PRIMARY KEY AUTO_INCREMENT,
    machine_id     VARCHAR(20) NOT NULL,
    machine_name   VARCHAR(100),
    location_name  VARCHAR(50),
    product_name   VARCHAR(100) NOT NULL,
    color          VARCHAR(50),
    size           VARCHAR(20),
    waste_quantity INT NOT NULL,
    waste_reason   VARCHAR(50),
    notes          TEXT,
    reported_by    VARCHAR(50),
    reported_at    DATETIME NOT NULL,
    created_at     TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_reported_at  (reported_at),
    INDEX idx_machine_id   (machine_id),
    INDEX idx_waste_reason (waste_reason)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ================================================================
-- TABLE 8: SETTINGS
-- ================================================================
CREATE TABLE IF NOT EXISTS settings (
    id            INT PRIMARY KEY AUTO_INCREMENT,
    setting_key   VARCHAR(100) UNIQUE NOT NULL,
    setting_value TEXT,
    description   VARCHAR(255),
    updated_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    created_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ================================================================
-- TABLE 9: SPLIT ORDERS
-- ================================================================
CREATE TABLE IF NOT EXISTS split_orders (
    id              INT PRIMARY KEY AUTO_INCREMENT,
    parent_order_id VARCHAR(100) NOT NULL,
    machine_id      VARCHAR(20) NOT NULL,
    location_name   VARCHAR(50),
    customer_name   VARCHAR(200),
    product_name    VARCHAR(100) NOT NULL,
    brand           VARCHAR(100),
    description     TEXT,
    color           VARCHAR(50),
    size            VARCHAR(20),
    allocated_qty   INT NOT NULL,
    produced_qty    INT DEFAULT 0,
    status          ENUM('pending','running','completed') DEFAULT 'pending',
    started_at      DATETIME,
    completed_at    DATETIME NULL,
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (machine_id) REFERENCES machines(machine_id) ON DELETE CASCADE,
    INDEX idx_parent_order (parent_order_id),
    INDEX idx_status       (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ================================================================
-- TABLE 10: ORDER TRACKING
-- ================================================================
CREATE TABLE IF NOT EXISTS order_tracking (
    id            INT PRIMARY KEY AUTO_INCREMENT,
    order_id      VARCHAR(100) UNIQUE NOT NULL,
    customer_name VARCHAR(200),
    total_qty     INT NOT NULL,
    allocated_qty INT DEFAULT 0,
    produced_qty  INT DEFAULT 0,
    remaining_qty INT DEFAULT 0,
    status        ENUM('pending','in_progress','completed') DEFAULT 'pending',
    created_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ================================================================
-- TABLE 11: MMS - RAW MATERIALS
-- ================================================================
CREATE TABLE IF NOT EXISTS raw_materials (
    id              INT PRIMARY KEY AUTO_INCREMENT,
    material_name   VARCHAR(100) NOT NULL UNIQUE,
    unit            VARCHAR(20) DEFAULT 'bags',
    min_stock_level DECIMAL(10,2) DEFAULT 5,
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_material (material_name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ================================================================
-- TABLE 12: MMS - STOCK LEVELS BY LOCATION
-- ================================================================
CREATE TABLE IF NOT EXISTS stock_levels (
    id            INT PRIMARY KEY AUTO_INCREMENT,
    material_id   INT NOT NULL,
    location_name VARCHAR(50) NOT NULL,
    quantity      DECIMAL(10,2) DEFAULT 0,
    last_updated  TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (material_id) REFERENCES raw_materials(id) ON DELETE CASCADE,
    UNIQUE KEY unique_material_location (material_id, location_name),
    INDEX idx_location (location_name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ================================================================
-- TABLE 13: MMS - STOCK USAGE RECORDS
-- ================================================================
CREATE TABLE IF NOT EXISTS stock_usage (
    id            INT PRIMARY KEY AUTO_INCREMENT,
    location_name VARCHAR(50) NOT NULL,
    shift         VARCHAR(20) NOT NULL DEFAULT 'Morning',
    material_id   INT NOT NULL,
    material_name VARCHAR(100) NOT NULL,
    bags_used     DECIMAL(10,2) NOT NULL,
    reported_by   VARCHAR(50) NOT NULL,
    reported_at   DATETIME NOT NULL,
    report_date   DATE NOT NULL,
    notes         TEXT,
    FOREIGN KEY (material_id) REFERENCES raw_materials(id) ON DELETE CASCADE,
    INDEX idx_location (location_name),
    INDEX idx_date     (report_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ================================================================
-- TABLE 14: MMS - STOCK HISTORY (AUDIT TRAIL)
-- ================================================================
CREATE TABLE IF NOT EXISTS stock_history (
    id            INT PRIMARY KEY AUTO_INCREMENT,
    material_id   INT NOT NULL,
    material_name VARCHAR(100) NOT NULL,
    location_name VARCHAR(50),
    shift         VARCHAR(20),
    old_stock     DECIMAL(10,2),
    new_stock     DECIMAL(10,2),
    change_type   VARCHAR(20) NOT NULL DEFAULT 'restock',
    changed_by    VARCHAR(50) NOT NULL,
    changed_at    DATETIME NOT NULL,
    invoice_no    VARCHAR(100) DEFAULT NULL,
    notes         TEXT,
    FOREIGN KEY (material_id) REFERENCES raw_materials(id) ON DELETE CASCADE,
    INDEX idx_material   (material_id),
    INDEX idx_changed_at (changed_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ================================================================
-- DATA: LOCATIONS
-- ================================================================
INSERT IGNORE INTO locations (name) VALUES
('Modan'),
('Baldeya'),
('Al-Khraj');

-- ================================================================
-- DATA: USERS
-- ================================================================
INSERT IGNORE INTO users (username, password, role, location_id) VALUES
('1111',  '1111',  'operator', 1),
('2222',  '2222',  'operator', 2),
('3333',  '3333',  'operator', 3),
('Admin', '12345', 'manager',  NULL),
('admin', 'admin', 'manager',  NULL);

-- ================================================================
-- DATA: STOP REASONS
-- ================================================================
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

-- ================================================================
-- DATA: SETTINGS
-- ================================================================
INSERT IGNORE INTO settings (setting_key, setting_value, description) VALUES
('auto_refresh_interval', '30',   'Auto refresh interval in seconds'),
('default_speed',         '2.5',  'Default machine speed'),
('cron_enabled',          '1',    'Enable/disable cron job'),
('waste_reasons', 'electricity,mechanical,material,operator,other', 'Waste reasons'),
('last_cron_run',         '0',    'Timestamp of last CRON run');

-- ================================================================
-- DATA: RAW MATERIALS (MMS)
-- ================================================================
INSERT IGNORE INTO raw_materials (material_name, unit, min_stock_level) VALUES
('HDPE 952',           'bags',  10),
('HDPE - F9584',       'bags',   5),
('Blue PE 9034',       'bags',   5),
('Grey PE-7009',       'bags',   5),
('Green PE-60033-8',   'bags',   5),
('Red PE-1010',        'bags',   5),
('Orange PE-531',      'bags',   5),
('White PE-6070',      'bags',   5),
('Yellow 256-Q',       'bags',   3),
('Browni Pa-491',      'bags',   5),
('Of Grade (HDPE)',    'bags',   5),
('PP OF GRADE',        'bags',   5),
('PP polypropylene',   'bags',  10),
('COPPER WIRE 0.80MM', 'rolls',  3),
('LLLDPE',             'bags',   5),
('FIRE RETARDENT',     'bags',   5);

-- ================================================================
-- DATA: STOCK LEVELS - Initialize 0 for all locations
-- ================================================================
INSERT IGNORE INTO stock_levels (material_id, location_name, quantity)
SELECT id, 'Modan',    0 FROM raw_materials;

INSERT IGNORE INTO stock_levels (material_id, location_name, quantity)
SELECT id, 'Baldeya',  0 FROM raw_materials;

INSERT IGNORE INTO stock_levels (material_id, location_name, quantity)
SELECT id, 'Al-Khraj', 0 FROM raw_materials;

-- ================================================================
-- DATA: 45 MACHINES (15 per location)
-- ================================================================
INSERT IGNORE INTO machines (machine_id, name, location_id, current_speed, auto_speed, last_updated_by) VALUES
('M1','Machine 1',1,2.5,TRUE,'browser'),('M2','Machine 2',1,2.5,TRUE,'browser'),
('M3','Machine 3',1,2.5,TRUE,'browser'),('M4','Machine 4',1,2.5,TRUE,'browser'),
('M5','Machine 5',1,2.5,TRUE,'browser'),('M6','Machine 6',1,2.5,TRUE,'browser'),
('M7','Machine 7',1,2.5,TRUE,'browser'),('M8','Machine 8',1,2.5,TRUE,'browser'),
('M9','Machine 9',1,2.5,TRUE,'browser'),('M10','Machine 10',1,2.5,TRUE,'browser'),
('M11','Machine 11',1,2.5,TRUE,'browser'),('M12','Machine 12',1,2.5,TRUE,'browser'),
('M13','Machine 13',1,2.5,TRUE,'browser'),('M14','Machine 14',1,2.5,TRUE,'browser'),
('M15','Machine 15',1,2.5,TRUE,'browser');

INSERT IGNORE INTO machines (machine_id, name, location_id, current_speed, auto_speed, last_updated_by) VALUES
('B1','Machine 1',2,2.5,TRUE,'browser'),('B2','Machine 2',2,2.5,TRUE,'browser'),
('B3','Machine 3',2,2.5,TRUE,'browser'),('B4','Machine 4',2,2.5,TRUE,'browser'),
('B5','Machine 5',2,2.5,TRUE,'browser'),('B6','Machine 6',2,2.5,TRUE,'browser'),
('B7','Machine 7',2,2.5,TRUE,'browser'),('B8','Machine 8',2,2.5,TRUE,'browser'),
('B9','Machine 9',2,2.5,TRUE,'browser'),('B10','Machine 10',2,2.5,TRUE,'browser'),
('B11','Machine 11',2,2.5,TRUE,'browser'),('B12','Machine 12',2,2.5,TRUE,'browser'),
('B13','Machine 13',2,2.5,TRUE,'browser'),('B14','Machine 14',2,2.5,TRUE,'browser'),
('B15','Machine 15',2,2.5,TRUE,'browser');

INSERT IGNORE INTO machines (machine_id, name, location_id, current_speed, auto_speed, last_updated_by) VALUES
('A1','Machine 1',3,2.5,TRUE,'browser'),('A2','Machine 2',3,2.5,TRUE,'browser'),
('A3','Machine 3',3,2.5,TRUE,'browser'),('A4','Machine 4',3,2.5,TRUE,'browser'),
('A5','Machine 5',3,2.5,TRUE,'browser'),('A6','Machine 6',3,2.5,TRUE,'browser'),
('A7','Machine 7',3,2.5,TRUE,'browser'),('A8','Machine 8',3,2.5,TRUE,'browser'),
('A9','Machine 9',3,2.5,TRUE,'browser'),('A10','Machine 10',3,2.5,TRUE,'browser'),
('A11','Machine 11',3,2.5,TRUE,'browser'),('A12','Machine 12',3,2.5,TRUE,'browser'),
('A13','Machine 13',3,2.5,TRUE,'browser'),('A14','Machine 14',3,2.5,TRUE,'browser'),
('A15','Machine 15',3,2.5,TRUE,'browser');

-- ================================================================
-- ⚠️  MIGRATION SECTION - FOR EXISTING LIVE DATABASE ONLY
-- Run this part ONLY if database already exists on Hostinger
-- It will NOT delete any existing data
-- ================================================================

-- Fix users password column size
ALTER TABLE users MODIFY COLUMN password VARCHAR(255) NOT NULL;

-- Fix stock_usage shift column
ALTER TABLE stock_usage MODIFY COLUMN shift VARCHAR(20) NOT NULL DEFAULT 'Morning';

-- Fix stock_history shift column
ALTER TABLE stock_history MODIFY COLUMN shift VARCHAR(20) DEFAULT NULL;

-- Add invoice_no if missing
SET @col_exists = (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'stock_history'
    AND COLUMN_NAME = 'invoice_no'
);
SET @sql = IF(@col_exists = 0,
    'ALTER TABLE stock_history ADD COLUMN invoice_no VARCHAR(100) DEFAULT NULL',
    'SELECT 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- ================================================================
-- VERIFICATION
-- ================================================================
SELECT
    (SELECT COUNT(*) FROM locations)     AS locations,
    (SELECT COUNT(*) FROM users)         AS users,
    (SELECT COUNT(*) FROM machines)      AS machines,
    (SELECT COUNT(*) FROM stop_reasons)  AS stop_reasons,
    (SELECT COUNT(*) FROM raw_materials) AS materials,
    (SELECT COUNT(*) FROM stock_levels)  AS stock_rows,
    '✅ Database Complete!' AS status,
    NOW() AS run_at;
