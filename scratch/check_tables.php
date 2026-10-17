<?php
require_once __DIR__ . '/../config/db_connect.php';

$tables = ['dairy_farm_applications', 'dairy_hub_records', 'dairy_project_beneficiaries', 'livestock_society_committee', 'livestock_society_meetings'];

foreach ($tables as $t) {
    echo "\n=== TABLE: $t ===\n";
    $res = $mysqli->query("DESCRIBE $t");
    if ($res) {
        while ($r = $res->fetch_assoc()) {
            echo "{$r['Field']} | {$r['Type']} | Null:{$r['Null']} | Key:{$r['Key']} | Default:{$r['Default']}\n";
        }
    } else {
        echo "Error: " . $mysqli->error . "\n";
    }
}
