<?php
require_once __DIR__ . '/../config/db_connect.php';

$tables = [
    'furniture_assets',
    'machinery_assets',
    'instrument_assets',
    'counterfoil_assets',
    'registered_vehicles'
];

foreach ($tables as $tbl) {
    $chk = $mysqli->query("SHOW COLUMNS FROM `$tbl` LIKE 'received_quantity'");
    if ($chk && $chk->num_rows === 0) {
        $sql = "ALTER TABLE `$tbl` ADD COLUMN `received_quantity` INT(11) NOT NULL DEFAULT 0 AFTER `initial_count`";
        if ($mysqli->query($sql)) {
            echo "[OK] Added received_quantity to $tbl\n";
        } else {
            echo "[ERR] Failed adding received_quantity to $tbl: " . $mysqli->error . "\n";
        }
    } else {
        echo "[EXISTS] received_quantity in $tbl\n";
    }

    // Ensure initial_count exists as well
    $chkInit = $mysqli->query("SHOW COLUMNS FROM `$tbl` LIKE 'initial_count'");
    if ($chkInit && $chkInit->num_rows === 0) {
        $sqlInit = "ALTER TABLE `$tbl` ADD COLUMN `initial_count` INT(11) NOT NULL DEFAULT 1 AFTER `available_quantity`";
        $mysqli->query($sqlInit);
        echo "[OK] Added initial_count to $tbl\n";
    }
}

// For existing rows, ensure initial_count is set to available_quantity if it is 0 or null
foreach ($tables as $tbl) {
    $mysqli->query("UPDATE `$tbl` SET `initial_count` = `available_quantity` WHERE `initial_count` IS NULL OR `initial_count` = 0");
}
echo "[DONE] Schema migration complete.\n";
