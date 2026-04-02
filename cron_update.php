<?php
/**
 * CRON JOB for Production Auto-Update
 * Run this every minute via server cron:
 * * * * * * php /path/to/your/project/cron_update.php
 * 
 * This ensures production continues even when browsers are closed.
 * FINAL VERSION 3.0 – With version locking and 45s window
 */

require_once 'config.php';

// Disable time limit for long runs
set_time_limit(300);

// Lock file to prevent multiple cron instances
$lockFile = __DIR__ . '/cron_running.lock';

// Check if already running (prevent double cron)
if (file_exists($lockFile)) {
    $lockTime = file_get_contents($lockFile);
    if (time() - $lockTime < 55) {
        error_log("⏰ CRON: Already running, skipping this instance...");
        exit;
    }
}

// Create lock file
file_put_contents($lockFile, time());

// Log start
$startTime = microtime(true);
error_log("🧠 CRON: Starting at " . date('Y-m-d H:i:s'));

try {
    // Ensure columns exist
    try {
        $pdo->exec("ALTER TABLE machines ADD COLUMN IF NOT EXISTS last_updated_by VARCHAR(20) DEFAULT 'browser'");
        $pdo->exec("ALTER TABLE machines ADD COLUMN IF NOT EXISTS current_customer VARCHAR(200) NULL");
        $pdo->exec("ALTER TABLE machines ADD COLUMN IF NOT EXISTS version INT DEFAULT 1");
    } catch (Exception $e) {
        // Ignore
    }
    
    // ✅ SELECT with version column
    $stmt = $pdo->query("
        SELECT m.*, l.name as location_name, m.version
        FROM machines m
        LEFT JOIN locations l ON m.location_id = l.id
        WHERE m.status = 'running'
    ");
    $machines = $stmt->fetchAll();

    $runningCount = count($machines);
    if ($runningCount === 0) {
        error_log("🧠 CRON: No running machines.");
        unlink($lockFile);
        exit;
    }

    error_log("🧠 CRON: Found $runningCount running machines.");

    $updatedCount = 0;
    $completedCount = 0;
    $skippedCount = 0;
    $versionMismatches = 0;
    $errorCount = 0;

    foreach ($machines as $machine) {
        $machineId = $machine['machine_id'];
        
        try {
            // ✅ 45 seconds check
            $lastUpdated = $machine['last_updated'] ?? $machine['created_at'];
            $lastTime = strtotime($lastUpdated);
            $now = time();
            
            if ($now - $lastTime < 45) {
                error_log("⏭️ CRON: Machine $machineId recently updated, skipping");
                $skippedCount++;
                continue;
            }

            // ✅ Cron self-check
            if (isset($machine['last_updated_by']) && $machine['last_updated_by'] === 'cron') {
                if ($now - $lastTime < 30) {
                    error_log("⏭️ CRON: Machine $machineId updated by cron recently, skipping");
                    $skippedCount++;
                    continue;
                }
            }
            
            // Get values
            $currentQty = floatval($machine['produced_qty'] ?? 0);
            $targetQty = floatval($machine['target_qty'] ?? 0);
            $speed = floatval($machine['current_speed'] ?? 2.5);
            $currentVersion = intval($machine['version'] ?? 1);
            
            if ($speed <= 0) $speed = 2.5;
            
            if ($currentQty >= $targetQty && $targetQty > 0) {
                $skippedCount++;
                continue;
            }

            // Calculate new quantity
            $increment = $speed;
            $newQty = $currentQty + $increment;
            
            if ($targetQty > 0 && $newQty > $targetQty) $newQty = $targetQty;
            if ($newQty < 0) $newQty = 0;
            
            if (abs($newQty - $currentQty) < 0.01) {
                $skippedCount++;
                continue;
            }

            // Begin transaction
            $pdo->beginTransaction();

            // ✅ VERSION CHECK UPDATE
            $updateStmt = $pdo->prepare("
                UPDATE machines SET 
                    produced_qty = ?,
                    last_updated = NOW(),
                    last_updated_by = 'cron',
                    version = version + 1
                WHERE machine_id = ? 
                AND status = 'running'
                AND version = ?
            ");
            $updateStmt->execute([$newQty, $machineId, $currentVersion]);
            
            if ($updateStmt->rowCount() === 0) {
                $pdo->rollBack();
                error_log("⏭️ CRON: Version mismatch for $machineId (v$currentVersion)");
                $versionMismatches++;
                $skippedCount++;
                continue;
            }

            // Check completion
            $completed = false;
            if ($targetQty > 0 && $newQty >= $targetQty) {
                $completeStmt = $pdo->prepare("UPDATE machines SET status = 'completed', completed_at = NOW() WHERE machine_id = ?");
                $completeStmt->execute([$machineId]);

                // Ensure columns exist
                try {
                    $pdo->exec("ALTER TABLE completed_orders ADD COLUMN IF NOT EXISTS brand VARCHAR(100)");
                    $pdo->exec("ALTER TABLE completed_orders ADD COLUMN IF NOT EXISTS customer_name VARCHAR(200)");
                    $pdo->exec("ALTER TABLE completed_orders ADD COLUMN IF NOT EXISTS description TEXT");
                } catch (Exception $e) {}

                // Insert into completed_orders
                $insertStmt = $pdo->prepare("
                    INSERT INTO completed_orders 
                    (location_name, machine_name, machine_id, work_order, product, brand, customer_name, description, color, size, target_qty, produced_qty, completed_at)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
                ");
                
                $insertStmt->execute([
                    $machine['location_name'] ?? '',
                    $machine['name'] ?? '',
                    $machine['machine_id'],
                    $machine['work_order'] ?? '',
                    $machine['current_product'] ?? '',
                    $machine['current_brand'] ?? '',
                    $machine['current_customer'] ?? '',
                    $machine['current_description'] ?? '',
                    $machine['current_color'] ?? '',
                    $machine['current_size'] ?? '',
                    $targetQty,
                    $newQty
                ]);

                $completed = true;
                $completedCount++;
                error_log("✅ CRON: Machine $machineId COMPLETED at $newQty/$targetQty");
            }

            $pdo->commit();
            $updatedCount++;
            
            if ($completed) {
                error_log("✅ CRON: Completed $machineId: $currentQty → $newQty [v{$currentVersion}→v" . ($currentVersion+1) . "]");
            } else {
                error_log("🔄 CRON: Updated $machineId: $currentQty → " . round($newQty, 2) . "m [v{$currentVersion}→v" . ($currentVersion+1) . "]");
            }

        } catch (Exception $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            $errorCount++;
            error_log("❌ CRON: Error $machineId: " . $e->getMessage());
        }
    }

    $executionTime = round(microtime(true) - $startTime, 2);
    error_log("📊 CRON SUMMARY: Updated: $updatedCount, Completed: $completedCount, Skipped: $skippedCount, Version Mismatches: $versionMismatches, Errors: $errorCount");
    error_log("⏱️ CRON: Finished in {$executionTime}s");

} catch (Exception $e) {
    error_log("💥 CRON FATAL ERROR: " . $e->getMessage());
}

// Remove lock file
if (file_exists($lockFile)) unlink($lockFile);
?>