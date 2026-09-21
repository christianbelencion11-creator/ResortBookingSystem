<?php
// Prevent multiple inclusions
if (defined('CONFIG_DATABASE_LOADED')) {
    return;
}
define('CONFIG_DATABASE_LOADED', true);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Set timezone for Philippines (standard for resort systems)
date_default_timezone_set('Asia/Manila');

// Database configuration
define('DB_HOST', 'localhost');
define('DB_PORT', '3306');
define('DB_NAME', 'ResortBookingDB');
define('DB_USER', 'root');

// Path helpers
define('ROOT_DIR', dirname(__DIR__));

// Dynamic Base URL detection (works both in root domain and subfolder /ResortBookingSystem/)
$docRoot = isset($_SERVER['DOCUMENT_ROOT']) ? str_replace('\\', '/', realpath($_SERVER['DOCUMENT_ROOT'])) : '';
$projRoot = str_replace('\\', '/', realpath(ROOT_DIR));
$baseWebPath = '';
if ($docRoot && strpos($projRoot, $docRoot) === 0) {
    $baseWebPath = substr($projRoot, strlen($docRoot));
}
$baseWebPath = rtrim(str_replace('\\', '/', $baseWebPath), '/');
define('BASE_URL', $baseWebPath);

function url($path = '') {
    $path = '/' . ltrim($path, '/');
    return BASE_URL . $path;
}

function asset($path = '') {
    return url($path);
}

// Connect to database with auto-fallback passwords (blank for default XAMPP, or 'admin123')
$pdo = null;
$passwordsToTry = ['', 'admin123', 'root'];
$lastException = null;

foreach ($passwordsToTry as $pass) {
    try {
        $dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=utf8mb4";
        $pdo = new PDO($dsn, DB_USER, $pass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
        break; // Connected successfully!
    } catch (PDOException $e) {
        $lastException = $e;
    }
}

if (!$pdo) {
    // If database does not exist yet, try connecting without dbname and create it or display friendly setup prompt
    foreach ($passwordsToTry as $pass) {
        try {
            $tempPdo = new PDO("mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";charset=utf8mb4", DB_USER, $pass);
            $tempPdo->exec("CREATE DATABASE IF NOT EXISTS `" . DB_NAME . "` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;");
            
            // Re-attempt connecting to newly created DB
            $dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=utf8mb4";
            $pdo = new PDO($dsn, DB_USER, $pass, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);
            
            // Auto import sql dump if available
            $sqlFile = ROOT_DIR . '/database/resort_db.sql';
            if (file_exists($sqlFile)) {
                $sql = file_get_contents($sqlFile);
                if (!empty($sql)) {
                    $tempPdo->exec("USE `" . DB_NAME . "`;");
                    $pdo->exec($sql);
                }
            }
            break;
        } catch (Exception $ex) {
            // Keep trying next password
        }
    }
}

if (!$pdo) {
    die("<div style='font-family:Arial,sans-serif; padding:40px; text-align:center;'>
        <h2 style='color:#e11d48;'>Database Connection Error</h2>
        <p style='color:#475569;'>Could not connect to MySQL server at <strong>" . DB_HOST . ":" . DB_PORT . "</strong>.</p>
        <p style='color:#64748b; font-size:14px;'>Make sure MySQL is running in XAMPP and that <code>ResortBookingDB</code> is imported from <code>database/resort_db.sql</code>.</p>
        <p style='color:#94a3b8; font-size:12px;'>Error: " . htmlspecialchars($lastException ? $lastException->getMessage() : 'Unknown connection error') . "</p>
    </div>");
}
