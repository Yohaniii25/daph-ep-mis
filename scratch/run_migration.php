<?php
require_once __DIR__ . '/../config/db_connect.php';
require_once __DIR__ . '/../pages/modules/veterinary/processors/dairy_db_migration.php';

$res = ensure_dairy_project_tables($mysqli);
echo "ensure_dairy_project_tables result: " . ($res ? "SUCCESS" : "FAILED") . "\n";

$tables = ['citizen_charter_requests', 'dairy_hub_societies', 'dairy_hub_society_members'];
foreach ($tables as $t) {
    $q = $mysqli->query("SHOW TABLES LIKE '$t'");
    echo "Table $t exists: " . ($q->num_rows > 0 ? "YES" : "NO") . "\n";
}
