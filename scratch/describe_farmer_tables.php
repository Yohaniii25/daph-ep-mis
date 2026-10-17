<?php
require_once __DIR__ . '/../config/db_connect.php';

$tables = ['farmers', 'farm_registration_renewals', 'dairy_farm_applications'];
foreach ($tables as $t) {
    echo "=== TABLE: $t ===\n";
    $res = $mysqli->query("DESCRIBE `$t`");
    if ($res) {
        while ($r = $res->fetch_assoc()) {
            echo "{$r['Field']} ({$r['Type']})\n";
        }
    } else {
        echo "Table $t not found or error: " . $mysqli->error . "\n";
    }
    echo "\n";
}
