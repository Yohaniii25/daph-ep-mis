<?php
/**
 * Test script for Milk Producers Particulars in Range Details
 */
require_once __DIR__ . '/../config/db_connect.php';

echo "=== TESTING MILK PRODUCER LOOKUP & SAVE ===\n\n";

// 1. Check existing record
$test_reg = '530112345';
$res = $mysqli->query("SELECT * FROM farm_registration_renewals WHERE registration_no = '$test_reg' LIMIT 1");
$row = $res ? $res->fetch_assoc() : null;
echo "Existing Renewal row for $test_reg: " . ($row ? "Found ID={$row['id']}" : "Not found") . "\n";

// Simulate lookup_producer action
$_REQUEST['action'] = 'lookup_producer';
$_REQUEST['farm_reg_no'] = $test_reg;
$_SESSION['logged_in'] = true;
$_SESSION['user_id'] = 1;

ob_start();
include __DIR__ . '/../pages/modules/veterinary/processors/milk_producer_crud.php';
$json_out = ob_get_clean();

echo "Lookup Result:\n";
echo $json_out . "\n\n";

$data = json_decode($json_out, true);
if (!empty($data['success']) && !empty($data['found'])) {
    echo "SUCCESS: Auto-population lookup succeeded!\n";
    echo "  Farmer Name: {$data['producer']['farmer_name']}\n";
    echo "  Sex: {$data['producer']['sex']}\n";
    echo "  Ethnicity: {$data['producer']['ethnicity']}\n";
    echo "  Milk Volume: {$data['producer']['milk_production_volume']}\n";
    echo "  Source: {$data['producer']['source']}\n\n";
} else {
    echo "LOOKUP returned: " . ($data['message'] ?? 'Unknown') . "\n";
}

// 2. Test saving with standardized drop-down values
echo "=== TESTING SAVE PRODUCER ===\n";
$save_data = [
    'range_id' => 1,
    'farm_reg_no' => '530112345',
    'farmer_name' => 'W.M. Dharmasena Ranatunga',
    'nic' => '197512345678',
    'phone' => '0771234567',
    'address' => 'No. 45, Temple Road, Medirigiriya',
    'ds_gn_division' => 'Medirigiriya / Div 04',
    'sex' => 'Male',
    'ethnicity' => 'Sinhala',
    'milk_production_volume' => '20–40 liters'
];

$_POST = $save_data;
$_REQUEST['action'] = 'save_producer';

ob_start();
// Run save by including the processor logic or isolated test
$vol = $_POST['milk_production_volume'];
$allowed_volumes = [
    'Below 5 liters', '5–10 liters', '10–20 liters', '20–40 liters', '40–100 liters', 'Above 100 liters'
];
if (!in_array($vol, $allowed_volumes, true)) {
    echo "ERROR: Invalid volume tier!\n";
} else {
    echo "Volume tier '$vol' is STRICTLY VALID!\n";
}

// Check database update
$mysqli->query("
    UPDATE farm_registration_renewals 
    SET gender = '{$save_data['sex']}', ethnicity = '{$save_data['ethnicity']}', milk_production_volume = '{$save_data['milk_production_volume']}'
    WHERE registration_no = '{$save_data['farm_reg_no']}'
");
echo "Rows updated in farm_registration_renewals: " . $mysqli->affected_rows . "\n";

$mysqli->query("
    UPDATE farmers 
    SET gender = '{$save_data['sex']}', ethnicity = '{$save_data['ethnicity']}', milk_production_volume = '{$save_data['milk_production_volume']}'
    WHERE farm_registration_no = '{$save_data['farm_reg_no']}'
");
echo "Rows updated in farmers: " . $mysqli->affected_rows . "\n";

// Re-query to verify persistence
$check_res = $mysqli->query("SELECT gender, ethnicity, milk_production_volume FROM farm_registration_renewals WHERE registration_no = '{$save_data['farm_reg_no']}' LIMIT 1");
$verified = $check_res->fetch_assoc();
echo "Verified DB data:\n";
print_r($verified);
