<?php
require_once __DIR__ . '/../config/db_connect.php';
global $mysqli;

$patterns = ['%drug%', '%vaccin%', '%inventor%', '%stock%', '%medicine%'];
foreach ($patterns as $p) {
    echo "--- Pattern: $p ---\n";
    $res = $mysqli->query("SHOW TABLES LIKE '$p'");
    while ($row = $res->fetch_array()) {
        echo "  " . $row[0] . "\n";
    }
}
