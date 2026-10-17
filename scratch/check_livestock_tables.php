<?php
require_once __DIR__ . '/../config/db_connect.php';

$res = $mysqli->query("SHOW TABLES LIKE '%livestock%'");
echo "Livestock tables:\n";
while ($r = $res->fetch_array()) {
    echo " - " . $r[0] . "\n";
}

$q = $mysqli->query('SELECT * FROM livestock_societies LIMIT 5');
echo "\nRows in livestock_societies: " . $q->num_rows . "\n";
while ($r = $q->fetch_assoc()) {
    print_r($r);
}
