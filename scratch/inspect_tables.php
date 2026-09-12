<?php
require_once __DIR__ . '/../config/db_connect.php';

foreach (['inventory_issue_orders', 'inventory_receipt_orders', 'inventory_transfers'] as $table) {
    echo "\n=== COLUMNS: $table ===\n";
    $res = $mysqli->query("SHOW COLUMNS FROM $table");
    if ($res) {
        while ($c = $res->fetch_assoc()) {
            echo "{$c['Field']} ({$c['Type']})\n";
        }
    }
}
