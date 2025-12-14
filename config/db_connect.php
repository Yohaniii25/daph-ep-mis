<?php
require_once 'constants.php';

$mysqli = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

if ($mysqli->connect_error) {
    die("Database connection failed. Contact administrator.");
}
$mysqli->set_charset("utf8mb4"); // Important for Sinhala/Tamil
?>