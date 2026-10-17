<?php
require_once __DIR__ . '/../config/db_connect.php';
global $mysqli;

$tables = ['drug_records', 'drug_types', 'monthly_vaccine_balances', 'vaccine_batches', 'stock_balance_logs'];
foreach ($tables as $t) {
    echo "=== Table: $t ===\n";
    $r = $mysqli->query("SHOW COLUMNS FROM $t");
    if ($r) {
        while ($col = $r->fetch_assoc()) {
            echo "  " . $col['Field'] . " (" . $col['Type'] . ")\n";
        }
    } else {
        echo "  [Not found or error: " . $mysqli->error . "]\n";
    }
}
