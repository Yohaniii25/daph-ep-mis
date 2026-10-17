<?php
require_once __DIR__ . '/../config/db_connect.php';

$res = $mysqli->query("DESCRIBE training_advanced_programmes");
if ($res) {
    echo "=== training_advanced_programmes ===\n";
    while ($r = $res->fetch_assoc()) echo "{$r['Field']} ({$r['Type']})\n";
}

$res = $mysqli->query("DESCRIBE animal_branding_records");
if (!$res) $res = $mysqli->query("SHOW TABLES LIKE '%brand%'");
echo "\n=== BRANDING TABLES ===\n";
while ($r = $res->fetch_array()) echo $r[0] . "\n";

$res = $mysqli->query("SHOW TABLES LIKE '%ear_tag%'");
echo "\n=== EAR TAG TABLES ===\n";
while ($r = $res->fetch_array()) echo $r[0] . "\n";
