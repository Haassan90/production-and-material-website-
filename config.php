<?php
session_start();

// Database configuration

$host     = 'localhost';   // VERY IMPORTANT
$dbname   = 'u594753397_db';
$username = 'u594753397_root';
$password = 'Taco2989**';

// ========== ERPNext CONFIGURATION ==========
define('ERPNEXT_URL', 'https://erp.tacogroup.net/');
define('ERPNEXT_API_KEY', '7f413b034030e5e');
define('ERPNEXT_API_SECRET', '97e3e612754d064');


// Set timezone (important for timestamp fields)
date_default_timezone_set('Asia/Riyadh'); // یا آپ کا ٹائم زون

try {
    // Better PDO options
    $pdo = new PDO(
        "mysql:host=$host;dbname=$dbname;charset=utf8", // utf8mb4 is better
        $username,
        $password,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        ]
    );
    
    // Test connection (optional)
    error_log("Database connected successfully");
    
} catch(PDOException $e) {
    // Log error for debugging
    error_log("Database connection failed: " . $e->getMessage());
    
    // Return JSON error for API calls
    if (strpos($_SERVER['REQUEST_URI'], 'api.php') !== false) {
        header('Content-Type: application/json');
        die(json_encode(['success' => false, 'message' => 'Database connection failed']));
    } else {
        die("Connection failed: " . $e->getMessage());
    }
}

// Session timeout (30 minutes)
if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > 1800)) {
    session_unset();
    session_destroy();
    session_start();
}
$_SESSION['last_activity'] = time();
?>