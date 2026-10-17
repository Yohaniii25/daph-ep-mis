<?php
require_once __DIR__ . '/../config/db_connect.php';

echo "Running migration for demographic & milk production volume fields...\n";

// Add to farm_registration_renewals
$cols_renewals = [
    'gender' => "VARCHAR(20) NULL AFTER nic",
    'ethnicity' => "VARCHAR(50) NULL AFTER gender",
    'milk_production_volume' => "VARCHAR(50) NULL AFTER daily_milk_production"
];
foreach ($cols_renewals as $col => $def) {
    $chk = $mysqli->query("SHOW COLUMNS FROM `farm_registration_renewals` LIKE '$col'");
    if ($chk && $chk->num_rows === 0) {
        $mysqli->query("ALTER TABLE `farm_registration_renewals` ADD COLUMN `$col` $def");
        echo "Added $col to farm_registration_renewals\n";
    }
}

// Add to dairy_farm_applications
$cols_apps = [
    'ethnicity' => "VARCHAR(50) NULL AFTER gender",
    'milk_production_volume' => "VARCHAR(50) NULL AFTER milk_total_liters"
];
foreach ($cols_apps as $col => $def) {
    $chk = $mysqli->query("SHOW COLUMNS FROM `dairy_farm_applications` LIKE '$col'");
    if ($chk && $chk->num_rows === 0) {
        $mysqli->query("ALTER TABLE `dairy_farm_applications` ADD COLUMN `$col` $def");
        echo "Added $col to dairy_farm_applications\n";
    }
}

// Add to farmers
$cols_farmers = [
    'gender' => "VARCHAR(20) NULL AFTER contact_no",
    'ethnicity' => "VARCHAR(50) NULL AFTER gender",
    'milk_production_volume' => "VARCHAR(50) NULL AFTER ethnicity"
];
foreach ($cols_farmers as $col => $def) {
    $chk = $mysqli->query("SHOW COLUMNS FROM `farmers` LIKE '$col'");
    if ($chk && $chk->num_rows === 0) {
        $mysqli->query("ALTER TABLE `farmers` ADD COLUMN `$col` $def");
        echo "Added $col to farmers\n";
    }
}

echo "Demographic fields migration completed.\n";
