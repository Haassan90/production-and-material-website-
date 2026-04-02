<?php
// ========== ERROR LOGGING ==========
ini_set('log_errors', 1);
ini_set('error_log', 'C:/xampp/php/logs/php_error_log');
error_log("=== API CALL STARTED ===");

error_reporting(0);
ini_set('display_errors', 0);
ob_clean();

require_once 'config.php';
header('Content-Type: application/json');

$action = $_GET['action'] ?? '';

// ========== BACKGROUND PRODUCTION UPDATE - DISABLED ==========
// ✅ SIRF CRON UPDATE KAREGA - BROWSER UPDATES DISABLED
if ($action === 'background_production_update') {
    // Allow from any origin
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
    
    // OPTIONS request handle
    if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
        http_response_code(200);
        exit();
    }
    
    // ✅ BROWSER UPDATES DISABLED - SIRF UI REFRESH KE LIYE
    echo json_encode([
        'success' => true,
        'message' => 'Browser updates disabled - using CRON only',
        'updated' => 0,
        'completed' => 0,
        'skipped' => 0,
        'version_mismatches' => 0,
        'results' => [],
        'timestamp' => date('Y-m-d H:i:s'),
        'mode' => 'cron_only'
    ]);
    exit;
}

// ========== LOGIN ==========
if ($action === 'login') {
    $data = json_decode(file_get_contents('php://input'), true);

    $stmt = $pdo->prepare("
        SELECT u.*, l.name as location_name 
        FROM users u
        LEFT JOIN locations l ON u.location_id = l.id
        WHERE u.username = ? AND u.role = ?
    ");
    $stmt->execute([$data['username'], $data['role']]);
    $user = $stmt->fetch();

    if ($user) {
        // Simple plain text password comparison only
        // ❌ AUTO-UPGRADE REMOVED - No bcrypt conversion
        // ❌ SESSION REGENERATION REMOVED
        // ❌ SESSION TIMEOUT REMOVED
        $passOk = ($data['password'] === $user['password']);

        if ($passOk) {
            // Simple session without regeneration
            $_SESSION['user_id']    = $user['id'];
            $_SESSION['username']   = $user['username'];
            $_SESSION['role']       = $user['role'];
            $_SESSION['location']   = $user['location_name'] ?? 'all';
            echo json_encode(['success' => true, 'user' => $user]);
        } else {
            echo json_encode(['success' => false]);
        }
    } else {
        echo json_encode(['success' => false]);
    }
    exit;
}

// ========== LOGOUT ==========
if ($action === 'logout') {
    session_destroy();
    echo json_encode(['success' => true]);
    exit;
}

// ========== CHECK LOGIN ==========
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit;
}

// ❌ SESSION TIMEOUT REMOVED - No automatic logout
// if (isset($_SESSION['login_time']) && (time() - $_SESSION['login_time']) > 28800) {
//     session_destroy();
//     echo json_encode(['success' => false, 'message' => 'Session expired']);
//     exit;
// }
// Refresh session timer on activity
// $_SESSION['login_time'] = time();

// ========== GET MACHINES ==========
if ($action === 'get_machines') {
    $location = $_GET['location'] ?? '';
    
    try {
        if ($_SESSION['role'] === 'operator') {
            $stmt = $pdo->prepare("
                SELECT m.*, l.name as location_name 
                FROM machines m
                JOIN locations l ON m.location_id = l.id
                WHERE l.name = ?
                ORDER BY m.id
            ");
            $stmt->execute([$_SESSION['location']]);
        } else {
            if ($location && $location !== 'all') {
                $stmt = $pdo->prepare("
                    SELECT m.*, l.name as location_name 
                    FROM machines m
                    JOIN locations l ON m.location_id = l.id
                    WHERE l.name = ?
                    ORDER BY m.id
                ");
                $stmt->execute([$location]);
            } else {
                $stmt = $pdo->query("
                    SELECT m.*, l.name as location_name 
                    FROM machines m
                    JOIN locations l ON m.location_id = l.id
                    ORDER BY l.id, m.id
                ");
            }
        }
        
        $machines = $stmt->fetchAll();
        echo json_encode(['success' => true, 'data' => $machines]);
        
    } catch (Exception $e) {
        error_log("GET MACHINES ERROR: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}

// ========== START MACHINE ==========
if ($action === 'start_machine') {
    $data = json_decode(file_get_contents('php://input'), true);
    
    try {
        $pdo->beginTransaction();
        
        // Ensure columns exist
        $pdo->exec("ALTER TABLE machines ADD COLUMN IF NOT EXISTS current_description TEXT");
        $pdo->exec("ALTER TABLE machines ADD COLUMN IF NOT EXISTS current_brand VARCHAR(100)");
        $pdo->exec("ALTER TABLE machines ADD COLUMN IF NOT EXISTS current_customer VARCHAR(200)");
        $pdo->exec("ALTER TABLE machines ADD COLUMN IF NOT EXISTS last_updated_by VARCHAR(20) DEFAULT 'browser'");
        $pdo->exec("ALTER TABLE machines ADD COLUMN IF NOT EXISTS version INT DEFAULT 1");
        
        $stmt = $pdo->prepare("
            UPDATE machines SET 
                status = 'running',
                current_product = ?,
                current_description = ?,
                current_brand = ?,
                current_customer = ?,
                current_color = ?,
                current_size = ?,
                target_qty = ?,
                produced_qty = 0,
                current_speed = ?,
                work_order = ?,
                stop_reason = NULL,
                completed_at = NULL,
                last_updated = NOW(),
                last_updated_by = 'operator',
                version = 1
            WHERE machine_id = ?
        ");
        
        $stmt->execute([
            $data['product'],
            $data['description'] ?? null,
            $data['brand'] ?? null,
            $data['customer'] ?? null,
            $data['color'],
            $data['size'],
            $data['qty'],
            $data['speed'] ?? 2.5,
            $data['work_order'] ?? '',
            $data['machine_id']
        ]);
        
        $pdo->commit();
        
        echo json_encode(['success' => true, 'message' => 'Machine started']);
        
    } catch (Exception $e) {
        $pdo->rollBack();
        error_log("START MACHINE ERROR: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}

// ========== STOP MACHINE ==========
if ($action === 'stop_machine') {
    $data = json_decode(file_get_contents('php://input'), true);
    
    try {
        $pdo->beginTransaction();
        
        $stmt = $pdo->prepare("
            SELECT m.*, l.name as location_name 
            FROM machines m
            JOIN locations l ON m.location_id = l.id
            WHERE m.machine_id = ?
        ");
        $stmt->execute([$data['machine_id']]);
        $machine = $stmt->fetch();
        
        if (!$machine) {
            throw new Exception('Machine not found');
        }
        
        $stmt = $pdo->prepare("
            UPDATE machines SET 
                status = 'stopped',
                stop_reason = ?,
                completed_at = NULL,
                last_updated = NOW(),
                last_updated_by = 'operator',
                version = version + 1
            WHERE machine_id = ?
        ");
        $stmt->execute([$data['reason'], $data['machine_id']]);
        
        $stmt = $pdo->prepare("
            INSERT INTO machine_downtime 
            (machine_id, machine_name, location_name, stop_reason, stopped_at, status)
            VALUES (?, ?, ?, ?, NOW(), 'active')
        ");
        $stmt->execute([
            $machine['machine_id'],
            $machine['name'],
            $machine['location_name'],
            $data['reason']
        ]);
        
        $pdo->commit();
        echo json_encode(['success' => true, 'message' => 'Machine stopped']);
        
    } catch (Exception $e) {
        $pdo->rollBack();
        error_log("STOP MACHINE ERROR: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}

// ========== RESUME MACHINE ==========
if ($action === 'resume_machine') {
    $data = json_decode(file_get_contents('php://input'), true);
    
    try {
        $pdo->beginTransaction();
        
        $stmt = $pdo->prepare("
            UPDATE machines SET 
                status = 'running',
                stop_reason = NULL,
                completed_at = NULL,
                last_updated = NOW(),
                last_updated_by = 'operator',
                version = version + 1
            WHERE machine_id = ?
        ");
        $stmt->execute([$data['machine_id']]);
        
        $stmt = $pdo->prepare("
            UPDATE machine_downtime 
            SET resumed_at = NOW(),
                downtime_minutes = TIMESTAMPDIFF(MINUTE, stopped_at, NOW()),
                status = 'resolved'
            WHERE machine_id = ? AND status = 'active'
            ORDER BY id DESC LIMIT 1
        ");
        $stmt->execute([$data['machine_id']]);
        
        $pdo->commit();
        echo json_encode(['success' => true, 'message' => 'Machine resumed']);
        
    } catch (Exception $e) {
        $pdo->rollBack();
        error_log("RESUME MACHINE ERROR: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}

// ========== UPDATE QUANTITY ==========
if ($action === 'update_quantity') {
    $data = json_decode(file_get_contents('php://input'), true);
    
    try {
        $pdo->beginTransaction();
        
        $stmt = $pdo->prepare("SELECT * FROM machines WHERE machine_id = ?");
        $stmt->execute([$data['machine_id']]);
        $machine = $stmt->fetch();
        
        if (!$machine) {
            throw new Exception('Machine not found');
        }
        
        $currentVersion = $machine['version'] ?? 1;
        $newQty = min($data['produced_qty'], $machine['target_qty']);
        
        // Update with version check
        $stmt = $pdo->prepare("
            UPDATE machines SET 
                produced_qty = ?,
                last_updated = NOW(),
                last_updated_by = 'operator',
                version = version + 1
            WHERE machine_id = ? AND version = ?
        ");
        $stmt->execute([$newQty, $data['machine_id'], $currentVersion]);
        
        if ($stmt->rowCount() === 0) {
            throw new Exception('Version mismatch - please refresh and try again');
        }
        
        $pdo->commit();
        echo json_encode(['success' => true, 'message' => "Quantity updated to $newQty"]);
        
    } catch (Exception $e) {
        $pdo->rollBack();
        error_log("UPDATE QUANTITY ERROR: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}

// ========== UPDATE TARGET ==========
if ($action === 'update_target') {
    $data = json_decode(file_get_contents('php://input'), true);
    
    try {
        $pdo->beginTransaction();
        
        $stmt = $pdo->prepare("SELECT produced_qty, version FROM machines WHERE machine_id = ?");
        $stmt->execute([$data['machine_id']]);
        $machine = $stmt->fetch();
        
        if (!$machine) {
            throw new Exception('Machine not found');
        }
        
        $currentVersion = $machine['version'] ?? 1;
        $newTarget = max($data['target_qty'], $machine['produced_qty'] ?? 0);
        
        $stmt = $pdo->prepare("
            UPDATE machines SET 
                target_qty = ?,
                last_updated = NOW(),
                last_updated_by = 'operator',
                version = version + 1
            WHERE machine_id = ? AND version = ?
        ");
        $stmt->execute([$newTarget, $data['machine_id'], $currentVersion]);
        
        if ($stmt->rowCount() === 0) {
            throw new Exception('Version mismatch - please refresh and try again');
        }
        
        $pdo->commit();
        echo json_encode(['success' => true, 'message' => "Target updated to $newTarget"]);
        
    } catch (Exception $e) {
        $pdo->rollBack();
        error_log("UPDATE TARGET ERROR: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}

// ========== COMPLETE MACHINE ==========
if ($action === 'complete_machine') {
    $data = json_decode(file_get_contents('php://input'), true);
    
    try {
        $pdo->beginTransaction();
        
        $stmt = $pdo->prepare("
            SELECT m.*, l.name as location_name 
            FROM machines m
            JOIN locations l ON m.location_id = l.id
            WHERE m.machine_id = ?
        ");
        $stmt->execute([$data['machine_id']]);
        $machine = $stmt->fetch();
        
        if (!$machine) {
            throw new Exception('Machine not found');
        }
        
        // Ensure columns exist
        try {
            $pdo->exec("ALTER TABLE completed_orders ADD COLUMN IF NOT EXISTS description TEXT");
            $pdo->exec("ALTER TABLE completed_orders ADD COLUMN IF NOT EXISTS brand VARCHAR(100)");
            $pdo->exec("ALTER TABLE completed_orders ADD COLUMN IF NOT EXISTS customer_name VARCHAR(200)");
        } catch (Exception $e) {
            // Columns might already exist, ignore
        }
        
        $insertStmt = $pdo->prepare("
            INSERT INTO completed_orders 
            (location_name, machine_name, machine_id, work_order, product, brand, customer_name, description, color, size, target_qty, produced_qty, completed_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
        ");
        
        $insertStmt->execute([
            $machine['location_name'],
            $machine['name'],
            $machine['machine_id'],
            $machine['work_order'] ?? $data['work_order'] ?? '',
            $machine['current_product'],
            $machine['current_brand'] ?? '',
            $machine['current_customer'] ?? '',
            $machine['current_description'] ?? null,
            $machine['current_color'],
            $machine['current_size'],
            $machine['target_qty'],
            $machine['produced_qty']
        ]);
        
        $updateStmt = $pdo->prepare("
            UPDATE machines SET 
                status = 'completed',
                completed_at = NOW(),
                last_updated = NOW(),
                last_updated_by = 'operator',
                version = version + 1
            WHERE machine_id = ?
        ");
        $updateStmt->execute([$data['machine_id']]);
        
        $pdo->commit();
        
        echo json_encode([
            'success' => true, 
            'message' => 'Order completed'
        ]);
        
    } catch (Exception $e) {
        $pdo->rollBack();
        error_log("COMPLETE MACHINE ERROR: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}

// ========== UPDATE MACHINE ==========
if ($action === 'update_machine') {
    $data = json_decode(file_get_contents('php://input'), true);
    
    try {
        $pdo->beginTransaction();
        
        $stmt = $pdo->prepare("SELECT version FROM machines WHERE machine_id = ? AND status = 'running'");
        $stmt->execute([$data['machine_id']]);
        $machine = $stmt->fetch();
        
        if (!$machine) {
            throw new Exception('Machine not found or not running');
        }
        
        $currentVersion = $machine['version'] ?? 1;
        
        $stmt = $pdo->prepare("
            UPDATE machines SET 
                current_product = ?,
                current_color = ?,
                current_size = ?,
                current_speed = ?,
                last_updated = NOW(),
                last_updated_by = 'operator',
                version = version + 1
            WHERE machine_id = ? AND status = 'running' AND version = ?
        ");
        
        $stmt->execute([
            $data['product'],
            $data['color'],
            $data['size'],
            $data['speed'],
            $data['machine_id'],
            $currentVersion
        ]);
        
        if ($stmt->rowCount() === 0) {
            throw new Exception('Version mismatch - please refresh and try again');
        }
        
        $pdo->commit();
        
        echo json_encode(['success' => true, 'message' => 'Machine updated']);
        
    } catch (Exception $e) {
        $pdo->rollBack();
        error_log("UPDATE MACHINE ERROR: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}

// ========== UPDATE SPEED ==========
if ($action === 'update_speed') {
    $data = json_decode(file_get_contents('php://input'), true);
    
    try {
        $pdo->beginTransaction();
        
        $stmt = $pdo->prepare("SELECT version FROM machines WHERE machine_id = ?");
        $stmt->execute([$data['machine_id']]);
        $machine = $stmt->fetch();
        
        if (!$machine) {
            throw new Exception('Machine not found');
        }
        
        $currentVersion = $machine['version'] ?? 1;
        
        $stmt = $pdo->prepare("
            UPDATE machines SET 
                current_speed = ?,
                last_updated = NOW(),
                last_updated_by = 'operator',
                version = version + 1
            WHERE machine_id = ? AND version = ?
        ");
        $stmt->execute([$data['speed'], $data['machine_id'], $currentVersion]);
        
        if ($stmt->rowCount() === 0) {
            throw new Exception('Version mismatch - please refresh and try again');
        }
        
        $pdo->commit();
        
        echo json_encode(['success' => true, 'message' => 'Speed updated']);
        
    } catch (Exception $e) {
        $pdo->rollBack();
        error_log("UPDATE SPEED ERROR: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}

// ========== RENAME MACHINE ==========
// ✅ NEW - Sirf manager rename kar sakta hai
if ($action === 'rename_machine') {
    // Sirf manager rename kar sakta hai
    if ($_SESSION['role'] !== 'manager') {
        echo json_encode(['success' => false, 'message' => 'Unauthorized - Only managers can rename machines']);
        exit;
    }
    
    $data = json_decode(file_get_contents('php://input'), true);
    $machineId = $data['machine_id'] ?? '';
    $newName = trim($data['new_name'] ?? '');
    
    if (empty($machineId) || empty($newName)) {
        echo json_encode(['success' => false, 'message' => 'Machine ID and new name required']);
        exit;
    }
    
    if (strlen($newName) > 100) {
        echo json_encode(['success' => false, 'message' => 'Machine name too long (max 100 characters)']);
        exit;
    }
    
    try {
        $pdo->beginTransaction();
        
        // Check if machine exists
        $checkStmt = $pdo->prepare("SELECT * FROM machines WHERE machine_id = ?");
        $checkStmt->execute([$machineId]);
        $machine = $checkStmt->fetch();
        
        if (!$machine) {
            throw new Exception('Machine not found');
        }
        
        // Update machine name
        $stmt = $pdo->prepare("
            UPDATE machines SET 
                name = ?,
                last_updated = NOW(),
                last_updated_by = 'operator',
                version = version + 1
            WHERE machine_id = ?
        ");
        $stmt->execute([$newName, $machineId]);
        
        $pdo->commit();
        
        echo json_encode([
            'success' => true,
            'message' => 'Machine renamed successfully',
            'new_name' => $newName
        ]);
        
    } catch (Exception $e) {
        $pdo->rollBack();
        error_log("RENAME MACHINE ERROR: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}

// ========== RESET MACHINE ==========
if ($action === 'reset_machine') {
    $data = json_decode(file_get_contents('php://input'), true);
    
    try {
        $pdo->beginTransaction();
        
        $stmt = $pdo->prepare("
            UPDATE machines SET 
                status = 'idle',
                current_product = NULL,
                current_description = NULL,
                current_brand = NULL,
                current_customer = NULL,
                current_color = NULL,
                current_size = NULL,
                target_qty = 0,
                produced_qty = 0,
                current_speed = 2.5,
                stop_reason = NULL,
                completed_at = NULL,
                work_order = NULL,
                last_updated = NOW(),
                last_updated_by = 'operator',
                version = version + 1
            WHERE machine_id = ?
        ");
        $stmt->execute([$data['machine_id']]);
        
        $pdo->commit();
        echo json_encode(['success' => true, 'message' => 'Machine reset']);
        
    } catch (Exception $e) {
        $pdo->rollBack();
        error_log("RESET MACHINE ERROR: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}

// ========== GET COMPLETED ORDERS ==========
if ($action === 'get_completed_orders') {
    try {
        $orders = $pdo->query("
            SELECT * FROM completed_orders 
            ORDER BY completed_at DESC 
            LIMIT 500
        ")->fetchAll();
        
        echo json_encode(['success' => true, 'data' => $orders]);
        
    } catch (Exception $e) {
        error_log("GET COMPLETED ORDERS ERROR: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}

// ========== DELETE ORDER ==========
if ($action === 'delete_order') {
    if ($_SESSION['role'] !== 'manager') {
        echo json_encode(['success' => false, 'message' => 'Unauthorized']);
        exit;
    }
    
    $data = json_decode(file_get_contents('php://input'), true);
    
    try {
        $stmt = $pdo->prepare("DELETE FROM completed_orders WHERE id = ?");
        $stmt->execute([$data['order_id']]);
        echo json_encode(['success' => true, 'message' => 'Order deleted']);
    } catch (Exception $e) {
        error_log("DELETE ORDER ERROR: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}

// ========== CLEAR COMPLETED ORDERS ==========
if ($action === 'clear_completed_orders') {
    if ($_SESSION['role'] !== 'manager') {
        echo json_encode(['success' => false, 'message' => 'Unauthorized']);
        exit;
    }
    
    try {
        $pdo->exec("DELETE FROM completed_orders");
        echo json_encode(['success' => true, 'message' => 'All cleared']);
    } catch (Exception $e) {
        error_log("CLEAR COMPLETED ORDERS ERROR: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}

// ========== GET DOWNTIME REPORT ==========
if ($action === 'get_downtime_report') {
    $filter = $_GET['filter'] ?? 'today';
    
    try {
        if ($filter === 'today') {
            $stmt = $pdo->query("SELECT * FROM machine_downtime WHERE DATE(stopped_at) = CURDATE() ORDER BY stopped_at DESC");
        } elseif ($filter === 'week') {
            $stmt = $pdo->query("SELECT * FROM machine_downtime WHERE stopped_at >= DATE_SUB(NOW(), INTERVAL 7 DAY) ORDER BY stopped_at DESC");
        } elseif ($filter === 'month') {
            $stmt = $pdo->query("SELECT * FROM machine_downtime WHERE stopped_at >= DATE_SUB(NOW(), INTERVAL 30 DAY) ORDER BY stopped_at DESC");
        } else {
            $stmt = $pdo->query("SELECT * FROM machine_downtime ORDER BY stopped_at DESC");
        }
        
        $downtimes = $stmt->fetchAll();
        echo json_encode(['success' => true, 'data' => $downtimes]);
        
    } catch (Exception $e) {
        error_log("GET DOWNTIME REPORT ERROR: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}

// ========== DELETE DOWNTIME ==========
if ($action === 'delete_downtime') {
    if ($_SESSION['role'] !== 'manager') {
        echo json_encode(['success' => false, 'message' => 'Unauthorized']);
        exit;
    }
    
    $data = json_decode(file_get_contents('php://input'), true);
    
    try {
        $stmt = $pdo->prepare("DELETE FROM machine_downtime WHERE id = ?");
        $stmt->execute([$data['id']]);
        echo json_encode(['success' => true, 'message' => 'Deleted']);
    } catch (Exception $e) {
        error_log("DELETE DOWNTIME ERROR: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}

// ========== CLEAR DOWNTIME ==========
if ($action === 'clear_downtime') {
    if ($_SESSION['role'] !== 'manager') {
        echo json_encode(['success' => false, 'message' => 'Unauthorized']);
        exit;
    }
    
    try {
        $pdo->exec("DELETE FROM machine_downtime");
        echo json_encode(['success' => true, 'message' => 'All cleared']);
    } catch (Exception $e) {
        error_log("CLEAR DOWNTIME ERROR: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}

// ========== WASTE REPORT ==========
if ($action === 'report_waste') {
    if (!isset($_SESSION['user_id'])) {
        echo json_encode(['success' => false, 'message' => 'Not logged in']);
        exit;
    }
    
    $data = json_decode(file_get_contents('php://input'), true);
    
    try {
        $pdo->beginTransaction();
        
        $stmt = $pdo->prepare("
            INSERT INTO waste_items 
            (machine_id, machine_name, location_name, product_name, color, size, waste_quantity, waste_reason, notes, reported_by, reported_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
        ");
        
        $stmt->execute([
            $data['machine_id'],
            $data['machine_name'] ?? '',
            $data['location_name'] ?? '',
            $data['product'],
            $data['color'] ?? '',
            $data['size'] ?? '',
            intval($data['quantity']),
            $data['reason'] ?? 'other',
            $data['notes'] ?? null,
            $_SESSION['username'] ?? 'operator'
        ]);
        
        $pdo->commit();
        
        echo json_encode([
            'success' => true,
            'message' => 'Waste reported successfully'
        ]);
        
    } catch (Exception $e) {
        $pdo->rollBack();
        error_log("WASTE REPORT ERROR: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}

// ========== GET WASTE REPORT ==========
if ($action === 'get_waste_report') {
    try {
        $stmt = $pdo->query("SELECT * FROM waste_items ORDER BY reported_at DESC LIMIT 500");
        $waste = $stmt->fetchAll();
        
        echo json_encode(['success' => true, 'data' => $waste]);
        
    } catch (Exception $e) {
        error_log("GET WASTE REPORT ERROR: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}

// ========== DELETE WASTE ==========
if ($action === 'delete_waste') {
    if ($_SESSION['role'] !== 'manager') {
        echo json_encode(['success' => false, 'message' => 'Unauthorized']);
        exit;
    }
    
    $data = json_decode(file_get_contents('php://input'), true);
    
    try {
        $stmt = $pdo->prepare("DELETE FROM waste_items WHERE id = ?");
        $stmt->execute([$data['id']]);
        
        echo json_encode(['success' => true, 'message' => 'Deleted']);
        
    } catch (Exception $e) {
        error_log("DELETE WASTE ERROR: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}

// ========== FETCH SALES ORDER FROM ERPNEXT ==========
if ($action === 'fetch_sales_order') {
    $data = json_decode(file_get_contents('php://input'), true);
    $searchTerm = $data['search_term'] ?? '';
    $location = $data['location'] ?? '';
    
    try {
        $base_url = rtrim(trim(ERPNEXT_URL), '/');
        
        // First, get list of orders with customer info
        $url = $base_url . "/api/resource/Sales%20Order?fields=[\"name\",\"customer\",\"customer_name\"]&limit=20";
        
        if (!empty($location)) {
            $location_lower = strtolower(trim($location));
            
            if ($location_lower === 'modan') {
                $url = $base_url . "/api/resource/Sales%20Order?filters=[[\"custom_assign_production_to_warehouse\",\"=\",\"MODON\"]]&fields=[\"name\",\"customer\",\"customer_name\"]&limit=20";
            } elseif ($location_lower === 'baldeya') {
                $url = $base_url . "/api/resource/Sales%20Order?filters=[[\"custom_assign_production_to_warehouse\",\"=\",\"BALADIYA\"]]&fields=[\"name\",\"customer\",\"customer_name\"]&limit=20";
            } elseif ($location_lower === 'al-khraj') {
                $url = $base_url . "/api/resource/Sales%20Order?filters=[[\"custom_assign_production_to_warehouse\",\"=\",\"ALKHARAJ\"]]&fields=[\"name\",\"customer\",\"customer_name\"]&limit=20";
            }
        }
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: token ' . ERPNEXT_API_KEY . ':' . ERPNEXT_API_SECRET
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode === 200) {
            $result = json_decode($response, true);
            $orders = $result['data'] ?? [];
            
            // Now fetch details for each order to get items with BRAND
            $detailedOrders = [];
            foreach ($orders as $order) {
                $detailUrl = $base_url . "/api/resource/Sales%20Order/" . urlencode($order['name']);
                
                $ch2 = curl_init();
                curl_setopt($ch2, CURLOPT_URL, $detailUrl);
                curl_setopt($ch2, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch2, CURLOPT_HTTPHEADER, [
                    'Authorization: token ' . ERPNEXT_API_KEY . ':' . ERPNEXT_API_SECRET
                ]);
                
                $detailResponse = curl_exec($ch2);
                $httpCode2 = curl_getinfo($ch2, CURLINFO_HTTP_CODE);
                curl_close($ch2);
                
                if ($httpCode2 === 200) {
                    $detail = json_decode($detailResponse, true);
                    $orderDetail = $detail['data'] ?? [];
                    
                    // Get customer name
                    $customerName = $orderDetail['customer_name'] ?? $orderDetail['customer'] ?? '';
                    
                    // Get items with BRAND
                    $items = [];
                    if (isset($orderDetail['items']) && is_array($orderDetail['items'])) {
                        foreach ($orderDetail['items'] as $item) {
                            $items[] = [
                                'item_code' => $item['item_code'] ?? '',
                                'item_name' => $item['item_name'] ?? '',
                                'description' => $item['description'] ?? '',
                                'qty' => $item['qty'] ?? 0,
                                'brand' => $item['custom_brand_type'] ?? $item['brand'] ?? ''
                            ];
                        }
                    }
                    
                    $order['items'] = $items;
                    $order['customer_name'] = $customerName;
                } else {
                    $order['items'] = [];
                    $order['customer_name'] = '';
                }
                
                $detailedOrders[] = $order;
            }
            
            // Filter by search term if provided
            if (!empty($searchTerm)) {
                $filtered = [];
                foreach ($detailedOrders as $order) {
                    $match = false;
                    if (stripos($order['name'], $searchTerm) !== false) {
                        $match = true;
                    } else if (stripos($order['customer_name'] ?? '', $searchTerm) !== false) {
                        $match = true;
                    } else {
                        foreach ($order['items'] as $item) {
                            if (stripos($item['item_name'] ?? '', $searchTerm) !== false ||
                                stripos($item['item_code'] ?? '', $searchTerm) !== false) {
                                $match = true;
                                break;
                            }
                        }
                    }
                    if ($match) {
                        $filtered[] = $order;
                    }
                }
                $detailedOrders = $filtered;
            }
            
            echo json_encode([
                'success' => true,
                'data' => $detailedOrders
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'ERPNext returned HTTP ' . $httpCode
            ]);
        }
        
    } catch (Exception $e) {
        error_log("FETCH SALES ORDER ERROR: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}

// ========== GET SINGLE SALES ORDER DETAILS ==========
if ($action === 'get_sales_order_details') {
    $data = json_decode(file_get_contents('php://input'), true);
    $orderId = $data['order_id'] ?? '';
    
    if (empty($orderId)) {
        echo json_encode(['success' => false, 'message' => 'Order ID required']);
        exit;
    }
    
    try {
        $base_url = rtrim(trim(ERPNEXT_URL), '/');
        $url = $base_url . "/api/resource/Sales%20Order/" . urlencode($orderId);
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: token ' . ERPNEXT_API_KEY . ':' . ERPNEXT_API_SECRET
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode === 200) {
            $result = json_decode($response, true);
            echo json_encode([
                'success' => true,
                'data' => $result['data'] ?? null
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'Order not found'
            ]);
        }
        
    } catch (Exception $e) {
        error_log("GET SALES ORDER DETAILS ERROR: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}

// ========================================
// ========== MATERIAL MANAGEMENT ==========
// ========================================

$sessionRole = $_SESSION['role'] ?? '';
$mmsIsAdmin = ($sessionRole === 'manager' || $sessionRole === 'admin');
$mmsLocMap  = ['AlKhraj'=>'Al-Khraj','Al-Khraj'=>'Al-Khraj','Modan'=>'Modan','Baldeya'=>'Baldeya','all'=>'all'];
$mmsUserLoc = $mmsLocMap[$_SESSION['location'] ?? ''] ?? ($_SESSION['location'] ?? 'all');

// ========== MMS: GET MATERIALS ==========
if ($action === 'mms_get_materials') {
    $rows = $pdo->query("SELECT id, material_name, unit, min_stock_level FROM raw_materials ORDER BY material_name")->fetchAll();
    echo json_encode(['success' => true, 'data' => $rows]);
    exit;
}

// ========== MMS: ADD MATERIAL (operator + admin) ==========
if ($action === 'mms_add_material') {
    $data  = json_decode(file_get_contents('php://input'), true) ?? [];
    $name  = trim($data['name'] ?? '');
    $unit  = in_array($data['unit'] ?? '', ['bags','rolls']) ? $data['unit'] : 'bags';
    $minSt = floatval($data['min_stock'] ?? 5);
    if (!$name) { echo json_encode(['success'=>false,'message'=>'Name required']); exit; }
    try {
        $pdo->prepare("INSERT INTO raw_materials (material_name, unit, min_stock_level) VALUES (?,?,?)")->execute([$name,$unit,$minSt]);
        $newId = $pdo->lastInsertId();
        $s = $pdo->prepare("INSERT IGNORE INTO stock_levels (material_id, location_name, quantity) VALUES (?,?,0)");
        foreach (['Modan','Baldeya','Al-Khraj'] as $l) $s->execute([$newId,$l]);
        // Log who added material
        $pdo->prepare("INSERT INTO stock_history (material_id,material_name,location_name,old_stock,new_stock,change_type,changed_by,changed_at,notes) VALUES (?,?,'all',0,0,'initial',?,NOW(),?)")->execute([$newId,$name,$_SESSION['username'],'New material added']);
        echo json_encode(['success'=>true,'id'=>$newId]);
    } catch(PDOException $e) { echo json_encode(['success'=>false,'message'=>'Already exists']); }
    exit;
}

// ========== MMS: OPERATOR EDIT STOCK ==========
if ($action === 'mms_operator_edit_stock') {
    if ($mmsIsAdmin) { echo json_encode(['success'=>false,'message'=>'Use admin edit']); exit; }
    $data  = json_decode(file_get_contents('php://input'), true) ?? [];
    $mid   = intval($data['material_id'] ?? 0);
    $newQ  = floatval($data['new_qty'] ?? 0);
    $reason= trim($data['reason'] ?? '');
    $loc   = $mmsUserLoc;
    if (!$mid || $newQ < 0) { echo json_encode(['success'=>false,'message'=>'Invalid input']); exit; }
    if (!$reason) { echo json_encode(['success'=>false,'message'=>'Reason required']); exit; }
    try {
        $mat=$pdo->prepare("SELECT material_name FROM raw_materials WHERE id=?"); $mat->execute([$mid]); $m=$mat->fetch();
        if (!$m) { echo json_encode(['success'=>false,'message'=>'Material not found']); exit; }
        $st=$pdo->prepare("SELECT quantity FROM stock_levels WHERE material_id=? AND location_name=?"); $st->execute([$mid,$loc]); $stk=$st->fetch(); $cur=$stk?floatval($stk['quantity']):0;
        if ($stk) $pdo->prepare("UPDATE stock_levels SET quantity=? WHERE material_id=? AND location_name=?")->execute([$newQ,$mid,$loc]);
        else $pdo->prepare("INSERT INTO stock_levels (material_id,location_name,quantity) VALUES (?,?,?)")->execute([$mid,$loc,$newQ]);
        $pdo->prepare("INSERT INTO stock_history (material_id,material_name,location_name,old_stock,new_stock,change_type,changed_by,changed_at,notes) VALUES (?,?,?,?,?,'adjustment',?,NOW(),?)")->execute([$mid,$m['material_name'],$loc,$cur,$newQ,$_SESSION['username'],$reason]);
        echo json_encode(['success'=>true]);
    } catch(PDOException $e) { echo json_encode(['success'=>false,'message'=>'DB error']); }
    exit;
}

// ========== MMS: DELETE STOCK (set to 0 with reason) ==========
if ($action === 'mms_delete_stock') {
    $data  = json_decode(file_get_contents('php://input'), true) ?? [];
    $mid   = intval($data['material_id'] ?? 0);
    $reason= trim($data['reason'] ?? '');
    $loc   = $mmsIsAdmin ? ($mmsLocMap[$data['location']??''] ?? $mmsUserLoc) : $mmsUserLoc;
    if (!$mid) { echo json_encode(['success'=>false,'message'=>'Invalid input']); exit; }
    if (!$reason) { echo json_encode(['success'=>false,'message'=>'Reason required']); exit; }
    try {
        $mat=$pdo->prepare("SELECT material_name FROM raw_materials WHERE id=?"); $mat->execute([$mid]); $m=$mat->fetch();
        if (!$m) { echo json_encode(['success'=>false,'message'=>'Material not found']); exit; }
        $st=$pdo->prepare("SELECT quantity FROM stock_levels WHERE material_id=? AND location_name=?"); $st->execute([$mid,$loc]); $stk=$st->fetch(); $cur=$stk?floatval($stk['quantity']):0;
        $pdo->prepare("UPDATE stock_levels SET quantity=0 WHERE material_id=? AND location_name=?")->execute([$mid,$loc]);
        $pdo->prepare("INSERT INTO stock_history (material_id,material_name,location_name,old_stock,new_stock,change_type,changed_by,changed_at,notes) VALUES (?,?,?,?,0,'adjustment',?,NOW(),?)")->execute([$mid,$m['material_name'],$loc,$cur,$_SESSION['username'],'DELETED: '.$reason]);
        echo json_encode(['success'=>true]);
    } catch(PDOException $e) { echo json_encode(['success'=>false,'message'=>'DB error']); }
    exit;
}

// ========== MMS: TRANSFER STOCK ==========
if ($action === 'mms_transfer_stock') {
    if ($mmsIsAdmin) { echo json_encode(['success'=>false,'message'=>'Operators only']); exit; }
    $data   = json_decode(file_get_contents('php://input'), true) ?? [];
    $mid    = intval($data['material_id'] ?? 0);
    $qty    = floatval($data['qty'] ?? 0);
    $toLoc  = $mmsLocMap[$data['to_location'] ?? ''] ?? '';
    $notes  = trim($data['notes'] ?? '');
    $fromLoc= $mmsUserLoc;
    if (!$mid || $qty <= 0 || !$toLoc) { echo json_encode(['success'=>false,'message'=>'Invalid input']); exit; }
    if ($toLoc === $fromLoc) { echo json_encode(['success'=>false,'message'=>'Cannot transfer to same location']); exit; }
    try {
        $pdo->beginTransaction();
        $mat=$pdo->prepare("SELECT material_name,unit FROM raw_materials WHERE id=?"); $mat->execute([$mid]); $m=$mat->fetch();
        if (!$m) { $pdo->rollBack(); echo json_encode(['success'=>false,'message'=>'Material not found']); exit; }
        // Check source stock
        $stFrom=$pdo->prepare("SELECT quantity FROM stock_levels WHERE material_id=? AND location_name=?"); $stFrom->execute([$mid,$fromLoc]); $from=$stFrom->fetch(); $curFrom=$from?floatval($from['quantity']):0;
        if ($curFrom < $qty) { $pdo->rollBack(); echo json_encode(['success'=>false,'message'=>"Not enough stock (have $curFrom, need $qty)"]); exit; }
        // Deduct from source
        $pdo->prepare("UPDATE stock_levels SET quantity=? WHERE material_id=? AND location_name=?")->execute([$curFrom-$qty,$mid,$fromLoc]);
        // Add to destination
        $stTo=$pdo->prepare("SELECT quantity FROM stock_levels WHERE material_id=? AND location_name=?"); $stTo->execute([$mid,$toLoc]); $to=$stTo->fetch(); $curTo=$to?floatval($to['quantity']):0;
        if ($to) $pdo->prepare("UPDATE stock_levels SET quantity=? WHERE material_id=? AND location_name=?")->execute([$curTo+$qty,$mid,$toLoc]);
        else $pdo->prepare("INSERT INTO stock_levels (material_id,location_name,quantity) VALUES (?,?,?)")->execute([$mid,$toLoc,$curTo+$qty]);
        // History logs
        $transferNote = "Transfer to $toLoc" . ($notes ? ": $notes" : '');
        $pdo->prepare("INSERT INTO stock_history (material_id,material_name,location_name,old_stock,new_stock,change_type,changed_by,changed_at,notes) VALUES (?,?,?,?,?,'usage',?,NOW(),?)")->execute([$mid,$m['material_name'],$fromLoc,$curFrom,$curFrom-$qty,$_SESSION['username'],$transferNote]);
        $pdo->prepare("INSERT INTO stock_history (material_id,material_name,location_name,old_stock,new_stock,change_type,changed_by,changed_at,notes) VALUES (?,?,?,?,?,'restock',?,NOW(),?)")->execute([$mid,$m['material_name'],$toLoc,$curTo,$curTo+$qty,$_SESSION['username'],"Transfer from $fromLoc" . ($notes ? ": $notes" : '')]);
        $pdo->commit();
        echo json_encode(['success'=>true]);
    } catch(PDOException $e) { $pdo->rollBack(); echo json_encode(['success'=>false,'message'=>'DB error']); }
    exit;
}

// ========== MMS: CHANGE PASSWORD ==========
if ($action === 'mms_change_password') {
    $data    = json_decode(file_get_contents('php://input'), true) ?? [];
    $oldPass = trim($data['old_password'] ?? '');
    $newPass = trim($data['new_password'] ?? '');
    if (!$oldPass || !$newPass) { echo json_encode(['success'=>false,'message'=>'Both fields required']); exit; }
    if (strlen($newPass) < 4) { echo json_encode(['success'=>false,'message'=>'Min 4 characters']); exit; }
    try {
        $stmt=$pdo->prepare("SELECT password FROM users WHERE id=?"); $stmt->execute([$_SESSION['user_id']]); $u=$stmt->fetch();
        // Simple plain text comparison only
        $ok = ($oldPass === $u['password']);
        if (!$ok) { echo json_encode(['success'=>false,'message'=>'Current password wrong']); exit; }
        // Keep as plain text - no hashing
        $pdo->prepare("UPDATE users SET password=? WHERE id=?")->execute([$newPass, $_SESSION['user_id']]);
        echo json_encode(['success'=>true]);
    } catch(PDOException $e) { echo json_encode(['success'=>false,'message'=>'DB error']); }
    exit;
}

// ========== MMS: GET STOCK ==========
if ($action === 'mms_get_stock') {
    $loc    = $mmsIsAdmin ? ($_GET['location'] ?? 'all') : $mmsUserLoc;
    $params = []; $where = '';
    if ($loc !== 'all') { $where = 'WHERE sl.location_name = ?'; $params[] = $loc; }
    $stmt = $pdo->prepare("SELECT sl.material_id, sl.location_name, sl.quantity, rm.material_name, rm.unit, rm.min_stock_level FROM stock_levels sl JOIN raw_materials rm ON rm.id=sl.material_id $where ORDER BY sl.location_name, rm.material_name");
    $stmt->execute($params);
    echo json_encode(['success'=>true,'data'=>$stmt->fetchAll()]);
    exit;
}

// ========== MMS: GET STATS ==========
if ($action === 'mms_get_stats') {
    $loc = $mmsIsAdmin ? ($_GET['location'] ?? 'all') : $mmsUserLoc;
    $params=[]; $where='';
    if ($loc !== 'all') { $where='AND sl.location_name=?'; $params[]=$loc; }
    $stmt = $pdo->prepare("SELECT SUM(CASE WHEN rm.unit='bags' THEN sl.quantity ELSE 0 END) AS tb, SUM(CASE WHEN rm.unit='bags' THEN sl.quantity*25 ELSE 0 END) AS tkg, SUM(CASE WHEN rm.unit='rolls' THEN sl.quantity ELSE 0 END) AS tc, SUM(CASE WHEN sl.quantity < rm.min_stock_level THEN 1 ELSE 0 END) AS ls FROM stock_levels sl JOIN raw_materials rm ON rm.id=sl.material_id WHERE 1=1 $where");
    $stmt->execute($params);
    $r = $stmt->fetch();
    echo json_encode(['success'=>true,'data'=>['total_bags'=>intval($r['tb']),'total_kg'=>intval($r['tkg']),'total_copper'=>intval($r['tc']),'low_stock'=>intval($r['ls'])]]);
    exit;
}

// ========== MMS: SUBMIT USAGE ==========
if ($action === 'mms_submit_usage') {
    if ($mmsIsAdmin) { echo json_encode(['success'=>false,'message'=>'Admins cannot report usage']); exit; }
    $data     = json_decode(file_get_contents('php://input'), true) ?? [];
    $shift    = in_array($data['shift']??'',['Morning','Evening']) ? $data['shift'] : 'Morning';
    $date     = $data['date'] ?? date('Y-m-d');
    $notes    = trim($data['notes'] ?? '');
    $location = $mmsUserLoc;
    $items    = $data['items'] ?? [];
    if (empty($items)) { echo json_encode(['success'=>false,'message'=>'No items']); exit; }
    try {
        $pdo->beginTransaction();
        foreach ($items as $item) {
            $mid = intval($item['material_id']); $qty = floatval($item['qty']);
            if ($qty <= 0) continue;
            $mat = $pdo->prepare("SELECT material_name, unit FROM raw_materials WHERE id=?"); $mat->execute([$mid]); $m = $mat->fetch();
            if (!$m) continue;
            $st = $pdo->prepare("SELECT quantity FROM stock_levels WHERE material_id=? AND location_name=?"); $st->execute([$mid,$location]); $stk=$st->fetch();
            $cur = $stk ? floatval($stk['quantity']) : 0;
            if ($cur < $qty) { $pdo->rollBack(); echo json_encode(['success'=>false,'message'=>"Low stock: {$m['material_name']} (have $cur, need $qty)"]); exit; }
            $pdo->prepare("UPDATE stock_levels SET quantity=? WHERE material_id=? AND location_name=?")->execute([$cur-$qty,$mid,$location]);
            $pdo->prepare("INSERT INTO stock_usage (location_name,shift,material_id,material_name,bags_used,reported_by,reported_at,report_date,notes) VALUES (?,?,?,?,?,?,NOW(),?,?)")->execute([$location,$shift,$mid,$m['material_name'],$qty,$_SESSION['username'],$date,$notes]);
            $pdo->prepare("INSERT INTO stock_history (material_id,material_name,location_name,shift,old_stock,new_stock,change_type,changed_by,changed_at,notes) VALUES (?,?,?,?,?,?,'usage',?,NOW(),?)")->execute([$mid,$m['material_name'],$location,$shift,$cur,$cur-$qty,$_SESSION['username'],$notes]);
        }
        $pdo->commit();
        echo json_encode(['success'=>true]);
    } catch(PDOException $e) { $pdo->rollBack(); echo json_encode(['success'=>false,'message'=>'DB error']); }
    exit;
}

// ========== MMS: ADD STOCK ==========
if ($action === 'mms_add_stock') {
    if ($mmsIsAdmin) { echo json_encode(['success'=>false,'message'=>'Admins cannot add stock']); exit; }
    $data=$json_data=json_decode(file_get_contents('php://input'),true)??[];
    $mid=intval($data['material_id']??0); $qty=floatval($data['qty']??0); $loc=$mmsUserLoc;
    $notes=trim($data['notes']??''); $invoice=trim($data['invoice']??'');
    if (!$mid||$qty<=0) { echo json_encode(['success'=>false,'message'=>'Invalid input']); exit; }
    try {
        $mat=$pdo->prepare("SELECT material_name,unit FROM raw_materials WHERE id=?"); $mat->execute([$mid]); $m=$mat->fetch();
        if (!$m) { echo json_encode(['success'=>false,'message'=>'Material not found']); exit; }
        $st=$pdo->prepare("SELECT quantity FROM stock_levels WHERE material_id=? AND location_name=?"); $st->execute([$mid,$loc]); $stk=$st->fetch();
        $cur=$stk?floatval($stk['quantity']):0; $new=$cur+$qty;
        if ($stk) $pdo->prepare("UPDATE stock_levels SET quantity=? WHERE material_id=? AND location_name=?")->execute([$new,$mid,$loc]);
        else $pdo->prepare("INSERT INTO stock_levels (material_id,location_name,quantity) VALUES (?,?,?)")->execute([$mid,$loc,$new]);
        $pdo->prepare("INSERT INTO stock_history (material_id,material_name,location_name,old_stock,new_stock,change_type,changed_by,changed_at,invoice_no,notes) VALUES (?,?,?,?,?,'restock',?,NOW(),?,?)")->execute([$mid,$m['material_name'],$loc,$cur,$new,$_SESSION['username'],$invoice,$notes]);
        echo json_encode(['success'=>true]);
    } catch(PDOException $e) { echo json_encode(['success'=>false,'message'=>'DB error']); }
    exit;
}

// ========== MMS: EDIT STOCK ==========
if ($action === 'mms_edit_stock') {
    if (!$mmsIsAdmin) { echo json_encode(['success'=>false,'message'=>'Admins only']); exit; }
    $data=json_decode(file_get_contents('php://input'),true)??[];
    $mid=intval($data['material_id']??0); $loc=$mmsLocMap[$data['location']??'']??($data['location']??''); $newQ=floatval($data['new_qty']??0); $reason=trim($data['reason']??'');
    try {
        $mat=$pdo->prepare("SELECT material_name FROM raw_materials WHERE id=?"); $mat->execute([$mid]); $m=$mat->fetch();
        $st=$pdo->prepare("SELECT quantity FROM stock_levels WHERE material_id=? AND location_name=?"); $st->execute([$mid,$loc]); $stk=$st->fetch(); $cur=$stk?floatval($stk['quantity']):0;
        if ($stk) $pdo->prepare("UPDATE stock_levels SET quantity=? WHERE material_id=? AND location_name=?")->execute([$newQ,$mid,$loc]);
        else $pdo->prepare("INSERT INTO stock_levels (material_id,location_name,quantity) VALUES (?,?,?)")->execute([$mid,$loc,$newQ]);
        $pdo->prepare("INSERT INTO stock_history (material_id,material_name,location_name,old_stock,new_stock,change_type,changed_by,changed_at,notes) VALUES (?,?,?,?,?,'adjustment',?,NOW(),?)")->execute([$mid,$m['material_name'],$loc,$cur,$newQ,$_SESSION['username'],$reason]);
        echo json_encode(['success'=>true]);
    } catch(PDOException $e) { echo json_encode(['success'=>false,'message'=>'DB error']); }
    exit;
}

// ========== MMS: GET HISTORY ==========
if ($action === 'mms_get_history') {
    $loc=$mmsIsAdmin?($_GET['location']??'all'):$mmsUserLoc;
    $start=$_GET['start']??''; $end=$_GET['end']??'';
    $params=[]; $where=[];
    if ($loc!=='all') { $where[]='su.location_name=?'; $params[]=$loc; }
    if ($start) { $where[]='su.report_date>=?'; $params[]=$start; }
    if ($end)   { $where[]='su.report_date<=?'; $params[]=$end; }
    $wc=$where?'WHERE '.implode(' AND ',$where):'';
    $stmt=$pdo->prepare("SELECT su.id,su.report_date,su.location_name,su.shift,su.material_name,su.bags_used,rm.unit,su.notes FROM stock_usage su JOIN raw_materials rm ON rm.id=su.material_id $wc ORDER BY su.reported_at DESC LIMIT 500");
    $stmt->execute($params);
    echo json_encode(['success'=>true,'data'=>$stmt->fetchAll()]);
    exit;
}

// ========== MMS: DELETE HISTORY (admin only) ==========
if ($action === 'mms_delete_history') {
    if (!$mmsIsAdmin) { echo json_encode(['success'=>false,'message'=>'Admins only']); exit; }
    $data = json_decode(file_get_contents('php://input'), true) ?? [];
    $id   = intval($data['id'] ?? 0);
    if (!$id) { echo json_encode(['success'=>false,'message'=>'Invalid ID']); exit; }
    try {
        // Get record info to restore stock
        $rec = $pdo->prepare("SELECT material_id, location_name, bags_used FROM stock_usage WHERE id=?");
        $rec->execute([$id]);
        $r = $rec->fetch();
        if ($r) {
            // Restore stock
            $pdo->prepare("UPDATE stock_levels SET quantity = quantity + ? WHERE material_id=? AND location_name=?")
                ->execute([$r['bags_used'], $r['material_id'], $r['location_name']]);
        }
        $pdo->prepare("DELETE FROM stock_usage WHERE id=?")->execute([$id]);
        echo json_encode(['success'=>true]);
    } catch(PDOException $e) { echo json_encode(['success'=>false,'message'=>'DB error']); }
    exit;
}

// ========== MMS: GET REPORT ==========
if ($action === 'mms_get_report') {
    $loc=$mmsIsAdmin?($_GET['location']??'all'):$mmsUserLoc; $period=$_GET['period']??'all';
    $params=[]; $where=[];
    if ($loc!=='all') { $where[]='su.location_name=?'; $params[]=$loc; }
    if ($period==='today')     { $where[]='su.report_date=?'; $params[]=date('Y-m-d'); }
    elseif ($period==='week')  { $where[]='su.report_date>=?'; $params[]=date('Y-m-d',strtotime('-7 days')); }
    elseif ($period==='month') { $where[]='su.report_date>=?'; $params[]=date('Y-m-d',strtotime('-30 days')); }
    $wc=$where?'WHERE '.implode(' AND ',$where):'';
    $stmt=$pdo->prepare("SELECT su.material_name,rm.unit,SUM(su.bags_used) AS tq,SUM(CASE WHEN rm.unit='bags' THEN su.bags_used*25 ELSE 0 END) AS tkg FROM stock_usage su JOIN raw_materials rm ON rm.id=su.material_id $wc GROUP BY su.material_id,su.material_name,rm.unit ORDER BY tq DESC LIMIT 10");
    $stmt->execute($params);
    $rows=$stmt->fetchAll(); $tq=0;$tkg=0;$labels=[];$cd=[];$top='-';
    foreach ($rows as $i=>$r) { $tq+=floatval($r['tq']); $tkg+=floatval($r['tkg']); $labels[]=$r['material_name']; $cd[]=floatval($r['tq']); if($i===0)$top=$r['material_name'].' ('.intval($r['tq']).')'; }
    echo json_encode(['success'=>true,'data'=>['total_qty'=>intval($tq),'total_kg'=>intval($tkg),'top_material'=>$top,'chart_labels'=>$labels,'chart_data'=>$cd]]);
    exit;
}

// ========== DEFAULT RESPONSE ==========
echo json_encode(['success' => false, 'message' => 'Invalid action']);
?>