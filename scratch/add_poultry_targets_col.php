<?php
require_once 'c:/xampp/htdocs/daph-ep-mis/config/db_connect.php';
global $mysqli;

$check = $mysqli->query("SHOW COLUMNS FROM annual_vaccination_targets LIKE 'poultry_targets_json'");
if ($check && $check->num_rows > 0) {
    echo "Column poultry_targets_json already exists in annual_vaccination_targets.\n";
} else {
    $alt = $mysqli->query("ALTER TABLE annual_vaccination_targets ADD COLUMN poultry_targets_json TEXT NULL AFTER target_poultry_doses");
    if ($alt) {
        echo "Successfully added poultry_targets_json column.\n";
    } else {
        echo "Error adding column: " . $mysqli->error . "\n";
    }
}
