-- ================================================
-- MMS SAFE MIGRATION - HOSTINGER FIXED
-- Select u594753397_db first, then run this
-- ================================================

-- ================================================
-- PART 1: USERS TABLE - password column size fix
-- ================================================
ALTER TABLE users MODIFY COLUMN password VARCHAR(255) NOT NULL;

-- ================================================
-- PART 2: MMS TABLES - Create if not exist
-- ================================================

CREATE TABLE IF NOT EXISTS raw_materials (
    id              INT PRIMARY KEY AUTO_INCREMENT,
    material_name   VARCHAR(100) NOT NULL UNIQUE,
    unit            VARCHAR(20) DEFAULT 'bags',
    min_stock_level DECIMAL(10,2) DEFAULT 5,
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_material (material_name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

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

-- ================================================
-- PART 3: Add invoice_no if not exists
-- ================================================
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

-- ================================================
-- PART 4: Fix shift column
-- ================================================
ALTER TABLE stock_usage   MODIFY COLUMN shift VARCHAR(20) NOT NULL DEFAULT 'Morning';
ALTER TABLE stock_history MODIFY COLUMN shift VARCHAR(20) DEFAULT NULL;

-- ================================================
-- PART 5: Insert default materials
-- ================================================
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

-- ================================================
-- PART 6: Initialize stock levels
-- ================================================
INSERT IGNORE INTO stock_levels (material_id, location_name, quantity)
SELECT id, 'Modan',    0 FROM raw_materials;

INSERT IGNORE INTO stock_levels (material_id, location_name, quantity)
SELECT id, 'Baldeya',  0 FROM raw_materials;

INSERT IGNORE INTO stock_levels (material_id, location_name, quantity)
SELECT id, 'Al-Khraj', 0 FROM raw_materials;

-- ================================================
-- DONE
-- ================================================
SELECT
    (SELECT COUNT(*) FROM raw_materials) AS materials,
    (SELECT COUNT(*) FROM stock_levels)  AS stock_rows,
    (SELECT COUNT(*) FROM stock_usage)   AS usage_records,
    (SELECT COUNT(*) FROM stock_history) AS history_records,
    '✅ MMS Migration Done!' AS status,
    NOW() AS run_at;
