<?php
require_once __DIR__ . '/../config/db_connect.php';

function showCols($mysqli, $table) {
    echo "=== Table: $table ===\n";
    $res = $mysqli->query("SHOW COLUMNS FROM `$table`");
    if ($res) {
        while ($row = $res->fetch_assoc()) {
            echo "{$row['Field']} ({$row['Type']})\n";
        }
    } else {
        echo "Error or table does not exist: " . $mysqli->error . "\n";
    }
    echo "\n";
}

showCols($mysqli, 'farmer_training_records');
showCols($mysqli, 'farm_master_history');
showCols($mysqli, 'dairy_project_beneficiaries');
showCols($mysqli, 'training_programs');
showCols($mysqli, 'projects_progress');
