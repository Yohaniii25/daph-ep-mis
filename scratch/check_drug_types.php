<?php
require_once __DIR__ . '/../config/db_connect.php';
global $mysqli;
$res = $mysqli->query("SELECT id, vaccine_name, brand_name, target_animal FROM drug_types LIMIT 10");
while ($row = $res->fetch_assoc()) {
    print_r($row);
}
