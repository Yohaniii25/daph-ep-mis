<?php
require_once __DIR__ . '/../config/db_connect.php';
$alter = "ALTER TABLE `annual_vaccination_targets` MODIFY COLUMN `animal_type` VARCHAR(50) NOT NULL DEFAULT 'Others'";
if ($mysqli->query($alter)) {
    echo "Altered animal_type to VARCHAR(50) successfully.\n";
} else {
    echo "Error: " . $mysqli->error . "\n";
}
