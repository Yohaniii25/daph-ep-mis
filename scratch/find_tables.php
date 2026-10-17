<?php
require_once __DIR__ . '/../config/db_connect.php';

$res = $mysqli->query("SHOW TABLES");
while ($r = $res->fetch_array()) {
    $t = $r[0];
    if (stripos($t, 'milk') !== false || stripos($t, 'farm') !== false || stripos($t, 'producer') !== false || stripos($t, 'range') !== false) {
        echo "TABLE: $t\n";
    }
}
