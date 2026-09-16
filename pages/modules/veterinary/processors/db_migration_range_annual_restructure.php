<?php
// pages/modules/veterinary/processors/db_migration_range_annual_restructure.php
require_once __DIR__ . '/../../../../config/db_connect.php';

header('Content-Type: text/plain');
echo "=== Starting Range Statistics & Annual Returns Database Migration ===\n\n";

function addColumnIfNotExists($mysqli, $table, $column, $definition) {
    $check = $mysqli->query("SHOW COLUMNS FROM `$table` LIKE '$column'");
    if ($check && $check->num_rows == 0) {
        $sql = "ALTER TABLE `$table` ADD COLUMN `$column` $definition";
        if ($mysqli->query($sql)) {
            echo "[OK] Added `$column` to `$table`\n";
        } else {
            echo "[ERROR] Failed to add `$column` to `$table`: " . $mysqli->error . "\n";
        }
    } else {
        echo "[EXISTS] Column `$column` already exists in `$table`\n";
    }
}

// 1. Annual Production Levels
echo "\n--- 1. annual_production_levels ---\n";
addColumnIfNotExists($mysqli, 'annual_production_levels', 'report_month', 'TINYINT DEFAULT NULL COMMENT "1-12 or NULL for annual total" AFTER `report_year`');

// 2. Pasture Fodder Lands
echo "\n--- 2. pasture_fodder_lands ---\n";
addColumnIfNotExists($mysqli, 'pasture_fodder_lands', 'report_month', 'TINYINT DEFAULT NULL COMMENT "1-12 or NULL for annual total" AFTER `report_year`');

// 3. Annual Pasture Yields
echo "\n--- 3. annual_pasture_yields ---\n";
addColumnIfNotExists($mysqli, 'annual_pasture_yields', 'report_month', 'TINYINT DEFAULT NULL COMMENT "1-12 or NULL for annual total" AFTER `report_year`');

// 4. Annual Feed Production
echo "\n--- 4. annual_feed_production ---\n";
addColumnIfNotExists($mysqli, 'annual_feed_production', 'report_month', 'TINYINT DEFAULT NULL COMMENT "1-12 or NULL for annual total" AFTER `report_year`');

// 5. Annual Producers Processors
echo "\n--- 5. annual_producers_processors ---\n";
addColumnIfNotExists($mysqli, 'annual_producers_processors', 'report_month', 'TINYINT DEFAULT NULL COMMENT "1-12 or NULL for annual total" AFTER `report_year`');
addColumnIfNotExists($mysqli, 'annual_producers_processors', 'income_range', 'VARCHAR(50) DEFAULT NULL COMMENT "Monthly income bracket" AFTER `organic_fert_price_rs_kg`');

// 6. Milk Collecting Centers
echo "\n--- 6. milk_collecting_centers ---\n";
addColumnIfNotExists($mysqli, 'milk_collecting_centers', 'district_id', 'INT DEFAULT NULL AFTER `vs_range`');
addColumnIfNotExists($mysqli, 'milk_collecting_centers', 'range_id', 'INT DEFAULT NULL AFTER `district_id`');
addColumnIfNotExists($mysqli, 'milk_collecting_centers', 'report_year', 'INT DEFAULT 2025 AFTER `range_id`');
addColumnIfNotExists($mysqli, 'milk_collecting_centers', 'report_month', 'TINYINT DEFAULT NULL AFTER `report_year`');
addColumnIfNotExists($mysqli, 'milk_collecting_centers', 'cow_milk_lit_month', 'DECIMAL(10,2) DEFAULT 0.00 AFTER `milk_collection_lit_per_month`');
addColumnIfNotExists($mysqli, 'milk_collecting_centers', 'buffalo_milk_lit_month', 'DECIMAL(10,2) DEFAULT 0.00 AFTER `cow_milk_lit_month`');
addColumnIfNotExists($mysqli, 'milk_collecting_centers', 'goat_milk_lit_month', 'DECIMAL(10,2) DEFAULT 0.00 AFTER `buffalo_milk_lit_month`');

// 7. Milk Processing Centers
echo "\n--- 7. milk_processing_centers ---\n";
addColumnIfNotExists($mysqli, 'milk_processing_centers', 'district_id', 'INT DEFAULT NULL AFTER `vs_range`');
addColumnIfNotExists($mysqli, 'milk_processing_centers', 'range_id', 'INT DEFAULT NULL AFTER `district_id`');
addColumnIfNotExists($mysqli, 'milk_processing_centers', 'report_year', 'INT DEFAULT 2025 AFTER `range_id`');
addColumnIfNotExists($mysqli, 'milk_processing_centers', 'report_month', 'TINYINT DEFAULT NULL AFTER `report_year`');
addColumnIfNotExists($mysqli, 'milk_processing_centers', 'cow_milk_lit_month', 'DECIMAL(10,2) DEFAULT 0.00 AFTER `processing_center_name`');
addColumnIfNotExists($mysqli, 'milk_processing_centers', 'buffalo_milk_lit_month', 'DECIMAL(10,2) DEFAULT 0.00 AFTER `cow_milk_lit_month`');
addColumnIfNotExists($mysqli, 'milk_processing_centers', 'goat_milk_lit_month', 'DECIMAL(10,2) DEFAULT 0.00 AFTER `buffalo_milk_lit_month`');
addColumnIfNotExists($mysqli, 'milk_processing_centers', 'income_range', 'VARCHAR(50) DEFAULT NULL AFTER `income_rs_per_month`');

// 8. Milk Product Sales Centers
echo "\n--- 8. milk_product_sales_centers ---\n";
addColumnIfNotExists($mysqli, 'milk_product_sales_centers', 'district_id', 'INT DEFAULT NULL AFTER `vs_range`');
addColumnIfNotExists($mysqli, 'milk_product_sales_centers', 'range_id', 'INT DEFAULT NULL AFTER `district_id`');
addColumnIfNotExists($mysqli, 'milk_product_sales_centers', 'report_year', 'INT DEFAULT 2025 AFTER `range_id`');
addColumnIfNotExists($mysqli, 'milk_product_sales_centers', 'report_month', 'TINYINT DEFAULT NULL AFTER `report_year`');
addColumnIfNotExists($mysqli, 'milk_product_sales_centers', 'cow_milk_lit_month', 'DECIMAL(10,2) DEFAULT 0.00 AFTER `sales_center_name`');
addColumnIfNotExists($mysqli, 'milk_product_sales_centers', 'buffalo_milk_lit_month', 'DECIMAL(10,2) DEFAULT 0.00 AFTER `cow_milk_lit_month`');
addColumnIfNotExists($mysqli, 'milk_product_sales_centers', 'goat_milk_lit_month', 'DECIMAL(10,2) DEFAULT 0.00 AFTER `buffalo_milk_lit_month`');
addColumnIfNotExists($mysqli, 'milk_product_sales_centers', 'income_range', 'VARCHAR(50) DEFAULT NULL AFTER `income_rs_per_month`');

// 9. Livestock Societies: Code of Conduct PDF
echo "\n--- 9. livestock_societies ---\n";
addColumnIfNotExists($mysqli, 'livestock_societies', 'district_id', 'INT DEFAULT NULL AFTER `vs_range`');
addColumnIfNotExists($mysqli, 'livestock_societies', 'range_id', 'INT DEFAULT NULL AFTER `district_id`');
addColumnIfNotExists($mysqli, 'livestock_societies', 'code_of_conduct_pdf', 'VARCHAR(255) DEFAULT NULL COMMENT "Path to uploaded PDF document" AFTER `tp_no`');

// 10. Meat Sales Table
echo "\n--- 10. meat_sales_records table ---\n";
$create_meat_sql = "
CREATE TABLE IF NOT EXISTS `meat_sales_records` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `district_id` INT DEFAULT NULL,
    `range_id` INT DEFAULT NULL,
    `vs_range` VARCHAR(255) NOT NULL,
    `report_year` INT NOT NULL DEFAULT 2025,
    `report_month` TINYINT DEFAULT NULL COMMENT '1-12 or NULL for annual total',
    `meat_type` ENUM('Beef', 'Mutton', 'Chicken', 'Other') NOT NULL,
    `other_meat_name` VARCHAR(100) DEFAULT NULL,
    `sales_volume_kg` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    `value_per_kilo` DECIMAL(10,2) NOT NULL DEFAULT 0.00 COMMENT 'Value/Price per Kilo Rs',
    `total_sales_amount` DECIMAL(14,2) NOT NULL DEFAULT 0.00 COMMENT 'Total Sales Rs',
    `outlet_name_address` VARCHAR(255) DEFAULT NULL,
    `contact_no` VARCHAR(50) DEFAULT NULL,
    `remarks` TEXT DEFAULT NULL,
    `created_by` INT DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
";

if ($mysqli->query($create_meat_sql)) {
    echo "[OK] `meat_sales_records` table ready\n";
} else {
    echo "[ERROR] Failed to create `meat_sales_records`: " . $mysqli->error . "\n";
}

// 11. Create uploads folder for Code of Conduct PDF
$upload_dir = __DIR__ . '/../../../../assets/uploads/code_of_conduct';
if (!is_dir($upload_dir)) {
    if (mkdir($upload_dir, 0777, true)) {
        echo "[OK] Created upload directory: $upload_dir\n";
        file_put_contents($upload_dir . '/index.html', '<!DOCTYPE html><html><head><title>403 Forbidden</title></head><body>Directory access is forbidden.</body></html>');
    } else {
        echo "[ERROR] Failed to create upload directory: $upload_dir\n";
    }
} else {
    echo "[EXISTS] Upload directory already exists: $upload_dir\n";
}

// Populate sample meat sales records if empty so the module has immediate visualization data
$count_res = $mysqli->query("SELECT COUNT(*) AS c FROM meat_sales_records");
$row = $count_res ? $count_res->fetch_assoc() : ['c' => 0];
if ($row['c'] == 0) {
    echo "Inserting realistic initial meat sales records for testing...\n";
    $sample_data = [
        ['Beef', NULL, 1450.00, 2200.00, 3190000.00, 'Eastern Prime Meats, Main Street', '065-2223456', 2025, NULL, 'Ampara', 1, 1],
        ['Chicken', NULL, 3800.00, 1150.00, 4370000.00, 'Green Valley Broilers, Town Center', '063-2221190', 2025, NULL, 'Ampara', 1, 1],
        ['Mutton', NULL, 620.00, 3400.00, 2108000.00, 'City Meat Stall, Hospital Road', '065-3338877', 2025, 3, 'Ampara', 1, 1],
        ['Other', 'Pork', 450.00, 1800.00, 810000.00, 'Highland Farm Cuts, Junction Road', '067-2244112', 2025, 3, 'Ampara', 1, 1],
        ['Chicken', NULL, 4200.00, 1180.00, 4956000.00, 'Lanka Poultry Depot, Market Square', '065-5551234', 2025, 2, 'Ampara', 1, 1],
        ['Beef', NULL, 1200.00, 2150.00, 2580000.00, 'Central Butchery, Coastal Way', '065-7778899', 2025, 2, 'Ampara', 1, 1],
    ];

    $ins_stmt = $mysqli->prepare("
        INSERT INTO meat_sales_records 
        (meat_type, other_meat_name, sales_volume_kg, value_per_kilo, total_sales_amount, outlet_name_address, contact_no, report_year, report_month, vs_range, district_id, range_id) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    if ($ins_stmt) {
        foreach ($sample_data as $s) {
            $ins_stmt->bind_param("ssddsssissii", $s[0], $s[1], $s[2], $s[3], $s[4], $s[5], $s[6], $s[7], $s[8], $s[9], $s[10], $s[11]);
            $ins_stmt->execute();
        }
        $ins_stmt->close();
        echo "[OK] Inserted sample meat sales records.\n";
    }
}

echo "\n=== Migration Completed Successfully! ===\n";
