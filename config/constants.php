<?php
// config/constants.php - Project constants

// Helper to safely load standard .env files
if (!function_exists('load_daph_env')) {
    function load_daph_env($env_file) {
        if (!file_exists($env_file) || !is_readable($env_file)) return;
        $lines = file($env_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if ($lines === false) return;
        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '' || str_starts_with($line, '#') || str_starts_with($line, ';')) {
                continue;
            }
            if (strpos($line, '=') !== false) {
                list($key, $val) = explode('=', $line, 2);
                $key = trim($key);
                $val = trim($val);
                $val = trim($val, "\"'");
                $_ENV[$key] = $val;
                putenv("$key=$val");
            }
        }
    }
}

// Load .env configuration
load_daph_env(dirname(__DIR__) . '/.env');

// Session security configuration
require_once __DIR__ . '/session.php';

// Database fallback constants (keep for legacy scripts)
define('DB_HOST', $_ENV['DB_HOST'] ?? 'localhost');
define('DB_USER', $_ENV['DB_USER'] ?? 'root');
define('DB_PASS', $_ENV['DB_PASS'] ?? '');
define('DB_NAME', $_ENV['DB_NAME'] ?? 'daph');

// Site info
define('SITE_NAME', 'DAPH Eastern Province MIS');
define('SITE_SHORT_NAME', 'DAPH - EP MIS');

// Security
define('MAX_LOGIN_ATTEMPTS', 5);
define('LOGIN_LOCKOUT_TIME', 900); // 15 minutes in seconds

// Auto-detect BASE_PATH - works in root or subfolder
$script_name = dirname($_SERVER['SCRIPT_NAME'] ?? '');
if ($script_name === '/' || $script_name === '\\' || empty($script_name)) {
    define('BASE_PATH', '/');
} else {
    define('BASE_PATH', rtrim($script_name, '/') . '/');
}

// Languages (for future trilingual support)
define('LANGUAGES', ['en' => 'English', 'si' => 'Sinhala', 'ta' => 'Tamil']);
define('DEFAULT_LANG', 'en');

// Date formats
define('DATE_FORMAT', 'd M Y');
define('DATETIME_FORMAT', 'd M Y h:i A');

// App version
define('APP_VERSION', '1.0.0 - December 2025');

// Debug mode configuration
$is_debug = isset($_ENV['APP_DEBUG']) ? filter_var($_ENV['APP_DEBUG'], FILTER_VALIDATE_BOOLEAN) : false;
define('DEBUG', $is_debug);

if (!DEBUG) {
    // Production Mode: Suppress display, write to private error log
    ini_set('display_errors', '0');
    ini_set('display_startup_errors', '0');
    ini_set('log_errors', '1');
    @ini_set('error_log', dirname(__DIR__) . '/storage/logs/php_errors.log');
    error_reporting(E_ALL & ~E_DEPRECATED & ~E_STRICT);
} else {
    // Local / Debug Mode
    ini_set('display_errors', '1');
    ini_set('display_startup_errors', '1');
    error_reporting(E_ALL);
}
?>