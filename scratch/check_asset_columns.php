<?php
require_once __DIR__ . '/../config/db_connect.php';

$tables = ['furniture_assets', 'machinery_assets', 'instrument_assets', 'counterfoil_assets', 'registered_vehicles', 'building_inventories'];
foreach ($tables as $t) {
    echo "=== $t ===\n";
    $res = $mysqli->query("SHOW COLUMNS FROM `$t`");
    while ($r = $res->fetch_assoc()) {
        if (in_array($r['Field'], ['initial_count', 'received_quantity', 'available_quantity'])) {
            echo "  {$r['Field']} ({$r['Type']})\n";
        }
    }
}
