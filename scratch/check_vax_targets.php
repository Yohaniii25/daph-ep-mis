<?php
require_once 'c:/xampp/htdocs/daph-ep-mis/config/db_connect.php';
global $mysqli;

echo "=== COLUMNS IN annual_vaccination_targets ===\n";
$res = $mysqli->query("SHOW COLUMNS FROM annual_vaccination_targets");
if ($res) {
    while ($r = $res->fetch_assoc()) {
        echo $r['Field'] . " (" . $r['Type'] . ")\n";
    }
} else {
    echo "Query failed: " . $mysqli->error . "\n";
}

echo "\n=== POULTRY SESSION LOGS ===\n";
$res = $mysqli->query("SELECT * FROM vaccination_session_logs WHERE category = 'Poultry' LIMIT 5");
if ($res) {
    while ($r = $res->fetch_assoc()) {
        print_r($r);
    }
}
