<?php
require 'config/db_connect.php';

$tables = ['meat_sales_records', 'milk_collecting_centers', 'milk_processing_centers', 'milk_product_sales_centers'];
foreach ($tables as $t) {
    echo "=== Table: $t ===\n";
    $res = $mysqli->query("DESCRIBE $t");
    if ($res) {
        while ($row = $res->fetch_assoc()) {
            echo "  - " . $row['Field'] . " (" . $row['Type'] . ")\n";
        }
    } else {
        echo "  Error: " . $mysqli->error . "\n";
    }
}
