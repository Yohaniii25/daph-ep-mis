<?php
require 'config/constants.php';
$mysqli = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
$mysqli->query("ALTER TABLE users ADD COLUMN profile_image VARCHAR(255) DEFAULT NULL");
echo $mysqli->error;
?>
