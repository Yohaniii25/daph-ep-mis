<?php
require_once __DIR__ . '/../../../../config/db_connect.php';

$tables = [
    'meat_sales_records',
    'milk_collecting_centers',
    'milk_processing_centers',
    'milk_product_sales_centers'
];

foreach ($tables as $tbl) {
    echo "Processing table: $tbl...\n";
    $col_check = $mysqli->query("SHOW COLUMNS FROM `$tbl` LIKE 'record_date'");
    if ($col_check && $col_check->num_rows === 0) {
        $alter = "ALTER TABLE `$tbl` ADD COLUMN `record_date` DATE NULL AFTER `report_month`";
        if ($mysqli->query($alter)) {
            echo "  Added column record_date.\n";
            // Populate existing rows
            $update = "UPDATE `$tbl` SET `record_date` = IF(created_at IS NOT NULL, DATE(created_at), CONCAT(report_year, '-', LPAD(IFNULL(report_month, 1), 2, '0'), '-01')) WHERE `record_date` IS NULL";
            $mysqli->query($update);
            echo "  Populated existing rows with default record_date.\n";
        } else {
            echo "  Error adding column: " . $mysqli->error . "\n";
        }
    } else {
        echo "  Column record_date already exists.\n";
    }
}

echo "Migration finished successfully.\n";
