<?php
require_once __DIR__ . '/../config/db_connect.php';

echo "=== TABLES LIKE 'train%' ===\n";
$res = $mysqli->query("SHOW TABLES LIKE '%train%'");
while ($r = $res->fetch_array()) echo $r[0] . "\n";

echo "\n=== TABLES LIKE 'project%' ===\n";
$res = $mysqli->query("SHOW TABLES LIKE '%project%'");
while ($r = $res->fetch_array()) echo $r[0] . "\n";

echo "\n=== TABLES LIKE 'water%' ===\n";
$res = $mysqli->query("SHOW TABLES LIKE '%water%'");
while ($r = $res->fetch_array()) echo $r[0] . "\n";

echo "\n=== TABLES LIKE 'dairy%' ===\n";
$res = $mysqli->query("SHOW TABLES LIKE '%dairy%'");
while ($r = $res->fetch_array()) echo $r[0] . "\n";

echo "\n=== DESCRIBE projects_progress ===\n";
$res = $mysqli->query("DESCRIBE projects_progress");
if ($res) {
    while ($r = $res->fetch_assoc()) echo "{$r['Field']} ({$r['Type']})\n";
} else {
    echo "projects_progress not found\n";
}

echo "\n=== DESCRIBE dairy_project_beneficiaries ===\n";
$res = $mysqli->query("DESCRIBE dairy_project_beneficiaries");
if ($res) {
    while ($r = $res->fetch_assoc()) echo "{$r['Field']} ({$r['Type']})\n";
}

echo "\n=== DESCRIBE activity_beneficiaries (if exists) ===\n";
$res = $mysqli->query("DESCRIBE activity_beneficiaries");
if ($res) {
    while ($r = $res->fetch_assoc()) echo "{$r['Field']} ({$r['Type']})\n";
} else {
    echo "activity_beneficiaries not found: " . $mysqli->error . "\n";
}
