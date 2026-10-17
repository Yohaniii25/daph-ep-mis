<?php
require_once __DIR__ . '/../config/db_connect.php';
global $mysqli;

$res = $mysqli->query("SELECT * FROM drug_types");
while ($r = $res->fetch_assoc()) {
    echo "ID {$r['id']}: {$r['vaccine_name']} | Brand: {$r['brand_name']} | Target: {$r['target_animal']} | Unit: {$r['unit_of_measure']}\n";
}
