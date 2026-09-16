<?php
require_once __DIR__ . '/../config/db_connect.php';

$tables = [
    'annual_production_levels', 
    'pasture_fodder_lands', 
    'annual_pasture_yields', 
    'annual_feed_production', 
    'annual_producers_processors', 
    'milk_collecting_centers', 
    'milk_processing_centers', 
    'milk_product_sales_centers', 
    'livestock_societies'
];

foreach ($tables as $t) {
    echo "=== Table: $t ===\n";
    $res = $mysqli->query("SHOW TABLES LIKE '$t'");
    if ($res && $res->num_rows > 0) {
        $cols = $mysqli->query("SHOW COLUMNS FROM $t");
        while ($c = $cols->fetch_assoc()) {
            echo "  {$c['Field']} ({$c['Type']})\n";
        }
    } else {
        echo "  [TABLE DOES NOT EXIST]\n";
    }
}
