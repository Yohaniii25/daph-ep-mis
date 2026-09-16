<?php
require_once __DIR__ . '/../config/db_connect.php';
$r = $mysqli->query("SHOW COLUMNS FROM animal_populations LIKE 'animal_type'");
print_r($r->fetch_assoc());
