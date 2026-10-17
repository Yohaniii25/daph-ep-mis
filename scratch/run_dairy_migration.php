<?php
require_once __DIR__ . '/../config/db_connect.php';
require_once __DIR__ . '/../pages/modules/veterinary/processors/dairy_db_migration.php';

global $mysqli;
$ok = ensure_dairy_project_tables($mysqli);
echo "Migration result: " . ($ok ? "SUCCESS" : "FAILED") . "\n";

$res = $mysqli->query("SHOW TABLES LIKE 'dairy_water_sources'");
echo "dairy_water_sources exists: " . ($res && $res->num_rows > 0 ? "YES" : "NO") . "\n";

$res = $mysqli->query("SELECT * FROM dairy_water_sources");
while ($r = $res->fetch_assoc()) echo "  - " . $r['source_name'] . "\n";
