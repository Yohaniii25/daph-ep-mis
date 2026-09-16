<?php
require_once __DIR__ . '/../config/db_connect.php';
$r = $mysqli->query("SHOW INDEX FROM animal_populations");
while($row = $r->fetch_assoc()) {
    echo $row['Key_name'] . " - " . $row['Column_name'] . "\n";
}
