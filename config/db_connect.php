<?php
// config/db_connect.php - Centralized Database Connection

require_once __DIR__ . '/constants.php';

// If .env hasn't been loaded yet, load it now
if (function_exists('load_daph_env')) {
    load_daph_env(dirname(__DIR__) . '/.env');
}

$db_host = $_ENV['DB_HOST'] ?? (defined('DB_HOST') ? DB_HOST : 'localhost');
$db_user = $_ENV['DB_USER'] ?? (defined('DB_USER') ? DB_USER : 'root');
$db_pass = $_ENV['DB_PASS'] ?? (defined('DB_PASS') ? DB_PASS : '');
$db_name = $_ENV['DB_NAME'] ?? (defined('DB_NAME') ? DB_NAME : 'daph');
$db_port = isset($_ENV['DB_PORT']) ? intval($_ENV['DB_PORT']) : 3306;

// Suppress raw mysqli errors from leaking credentials or paths to browser
mysqli_report(MYSQLI_REPORT_OFF);

$mysqli = @new mysqli($db_host, $db_user, $db_pass, $db_name, $db_port);

if ($mysqli->connect_error) {
    error_log("DAPH MIS Database Connection Error: " . $mysqli->connect_error);
    die("Database connection failed. Contact administrator.");
}

$mysqli->set_charset("utf8mb4"); // Important for Sinhala/Tamil support
?>