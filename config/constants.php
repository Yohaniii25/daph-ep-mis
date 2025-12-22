<?php
// config/constants.php - Project constants

// Database (keep your current - change for live)
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'daph');

// Site info
define('SITE_NAME', 'DAPH Eastern Province MIS');
define('SITE_SHORT_NAME', 'DAPH - EP MIS');

// Security
define('MAX_LOGIN_ATTEMPTS', 5);
define('LOGIN_LOCKOUT_TIME', 900); // 15 minutes in seconds

// Auto-detect BASE_PATH - works in root or subfolder
$script_name = dirname($_SERVER['SCRIPT_NAME']);
if ($script_name === '/' || $script_name === '\\') {
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

// Debug mode (set to false on live server)
define('DEBUG', true);
?>