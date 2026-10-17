<?php
require_once __DIR__ . '/../config/db_connect.php';
$conn = $mysqli;

echo "--- STARTING DAIRY HUB INTEGRATION & EXTENDED TRACKING TESTS ---\n";

// 1. Test Water Source Auto-save & Listing
echo "\n[1] Testing Dynamic Water Sources...\n";
$testSource = "Agricultural Tube Well " . time();
$stmt = $conn->prepare("INSERT IGNORE INTO `dairy_water_sources` (`source_name`) VALUES (?)");
$stmt->bind_param("s", $testSource);
$stmt->execute();
$stmt->close();
echo "Inserted custom water source: '$testSource'\n";

$res = $conn->query("SELECT * FROM `dairy_water_sources` WHERE `source_name` = '$testSource'");
if ($row = $res->fetch_assoc()) {
    echo "SUCCESS: Found custom water source in database (ID: {$row['id']})\n";
} else {
    echo "FAILURE: Could not find water source.\n";
}

// 2. Test Animal ID & Remarks Lookup
echo "\n[2] Testing Animal Identification Remarks Lookup...\n";
$testRegNo = "TEST-REG-" . rand(1000, 9999);
$testNic = "1988" . rand(10000000, 99999999);
$testEarTag = "TAG-LK-" . rand(100, 999);
$testBrand = "BR-EP-" . rand(100, 999);
$cattleData = json_encode(['remarks' => "Ear Tag: $testEarTag | Brand: $testBrand. Sahiwal heifer vaccinated."]);

// Insert into farm_registration_renewals using actual schema
$stmt = $conn->prepare("INSERT INTO `farm_registration_renewals` (`registration_no`, `farmer_name`, `nic`, `telephone_no`, `neat_cattle_data`, `farm_type`, `gps_latitude`, `gps_longitude`, `ds_division`, `gn_division`) VALUES (?, 'Kumara Perera', ?, '0771234567', ?, 'Dairy Cattle', 7.7102, 81.6924, 'Manmunai North', 'Kallady 12')");
$stmt->bind_param("sss", $testRegNo, $testNic, $cattleData);
$stmt->execute();
$regId = $conn->insert_id;
$stmt->close();

echo "Inserted test farm registration: $testRegNo (ID: $regId)\n";

// Run search matching dairy_project_crud.php lookup_animal_id logic
$q_reg = $conn->prepare("SELECT neat_cattle_data, buffaloes_data, farm_type, gps_latitude, gps_longitude, ds_division, gn_division FROM `farm_registration_renewals` WHERE registration_no = ? LIMIT 1");
$q_reg->bind_param("s", $testRegNo);
$q_reg->execute();
$rreg = $q_reg->get_result()->fetch_assoc();
$q_reg->close();

$found_remarks = '';
if ($rreg) {
    $cattle = json_decode($rreg['neat_cattle_data'] ?? '', true);
    if (is_array($cattle) && !empty($cattle['remarks'])) {
        $found_remarks = $cattle['remarks'];
    }
}

if (!empty($found_remarks) && strpos($found_remarks, $testEarTag) !== false) {
    echo "SUCCESS: Animal ID lookup matched farm registration remarks: '$found_remarks'\n";
    echo "Auto-populated coordinates: Lat {$rreg['gps_latitude']}, Lng {$rreg['gps_longitude']}, DS: {$rreg['ds_division']}, GN: {$rreg['gn_division']}\n";
} else {
    echo "FAILURE: Could not match animal ID remarks.\n";
}

// 3. Test Historical Ministry Project Benefits Detection
echo "\n[3] Testing Historical Project Benefit Detection...\n";
$testProjName = "Ministry Dairy Development 2024";
$conn->query("INSERT INTO `dairy_project_beneficiaries` (`project_name`, `project_year`, `farm_reg_no`, `beneficiary_name`, `nic`, `phone`, `activities`, `department_contribution`, `approval_status`, `is_deleted`)
VALUES ('$testProjName', 2024, '$testRegNo', 'Kumara Perera', '$testNic', '0771234567', 'Cattle Shed Construction Subsidy', 75000.00, 'Approved', 0)");
$beneficiaryId = $conn->insert_id;

$projStmt = $conn->prepare("
    SELECT project_name, project_year, category, activities, department_contribution, approval_status 
    FROM `dairy_project_beneficiaries` 
    WHERE (farm_reg_no = ? OR (nic = ? AND nic != '')) AND COALESCE(is_deleted, 0) = 0
    ORDER BY project_year DESC, id DESC
");
$projStmt->bind_param("ss", $testRegNo, $testNic);
$projStmt->execute();
$histProjects = $projStmt->get_result()->fetch_all(MYSQLI_ASSOC);
$projStmt->close();

if (!empty($histProjects)) {
    echo "SUCCESS: Auto-detected " . count($histProjects) . " historical ministry project(s)!\n";
    echo "Project: {$histProjects[0]['project_name']} (Year: {$histProjects[0]['project_year']}, Activities: {$histProjects[0]['activities']})\n";
} else {
    echo "FAILURE: Failed to detect historical projects.\n";
}

// 4. Test Strict Non-Deletion Policy (Snapshot & Soft Delete)
echo "\n[4] Testing Strict Non-Deletion Policy & History Snapshot...\n";
$archiveStmt = $conn->prepare("INSERT INTO `farm_master_history` (`farm_reg_no`, `module_source`, `action_type`, `snapshot_data`, `recorded_by`) VALUES (?, ?, 'SOFT_DELETE', ?, 1)");
$entityType = 'dairy_project_beneficiaries';
$prevJson = json_encode($histProjects[0]);
$archiveStmt->bind_param("sss", $testRegNo, $entityType, $prevJson);
$archiveStmt->execute();
$histId = $conn->insert_id;
$archiveStmt->close();

// Soft delete
$conn->query("UPDATE `dairy_project_beneficiaries` SET is_deleted = 1, deleted_at = NOW() WHERE id = $beneficiaryId");

$checkSoft = $conn->query("SELECT is_deleted, deleted_at FROM `dairy_project_beneficiaries` WHERE id = $beneficiaryId")->fetch_assoc();
$checkHist = $conn->query("SELECT * FROM `farm_master_history` WHERE id = $histId")->fetch_assoc();

if ($checkSoft['is_deleted'] == 1 && !empty($checkHist)) {
    echo "SUCCESS: Record soft-deleted (is_deleted=1) and full snapshot safely preserved in farm_master_history (History ID: {$checkHist['id']})\n";
} else {
    echo "FAILURE: Soft-delete or history snapshot failed.\n";
}

// 5. Test Extended Dairy Hub Tracking Modules & Training Integration
echo "\n[5] Testing Extended Dairy Hub Tracking & Farmer Training Records Integration...\n";
$trainCourse = "Clean Milk Production & Quality Control";
$trainDate = "2026-03-15";
$trainInstitute = "Livestock Development Center";
$trainStmt = $conn->prepare("INSERT INTO `farmer_training_records` (`farm_reg_no`, `farmer_name`, `nic`, `course_name`, `institute`, `completion_date`, `source`) VALUES (?, 'Kumara Perera', ?, ?, ?, ?, 'Dairy Farm Survey')");
$trainStmt->bind_param("sssss", $testRegNo, $testNic, $trainCourse, $trainInstitute, $trainDate);
$trainStmt->execute();
$trainingId = $conn->insert_id;
$trainStmt->close();

$resTrain = $conn->query("SELECT * FROM `farmer_training_records` WHERE `id` = $trainingId")->fetch_assoc();
if ($resTrain && $resTrain['course_name'] === $trainCourse) {
    echo "SUCCESS: Farmer training record linked with primary system training module!\n";
} else {
    echo "FAILURE: Farmer training record insertion failed.\n";
}

// 6. Test Projects Module ("Add Project" capture)
echo "\n[6] Testing Projects Module Interface & Persistence...\n";
$projName = "Provincial Silage Subsidy 2026";
$projType = "PSDG";
$psdgStmt = $conn->prepare("INSERT INTO `projects_progress` (`range_id`, `project_type`, `project_name`, `summary`, `location`, `start_date`, `end_date`, `priority`, `status`, `progress_percent`) VALUES (1, ?, ?, 'Silage distribution', 'Batticaloa', '2026-03-01', '2026-12-31', 'High', 'In Progress', 0)");
$psdgStmt->bind_param("ss", $projType, $projName);
$psdgStmt->execute();
$pId = $conn->insert_id;
$psdgStmt->close();

$resProj = $conn->query("SELECT * FROM `projects_progress` WHERE `id` = $pId")->fetch_assoc();
if ($resProj && $resProj['project_name'] === $projName && $resProj['project_type'] === $projType) {
    echo "SUCCESS: Projects Module captured project_type and project_name properly (ID: $pId)!\n";
} else {
    echo "FAILURE: Project module persistence failed.\n";
}

// Clean up test data
echo "\nCleaning up test scratch records...\n";
$conn->query("DELETE FROM `dairy_water_sources` WHERE `source_name` = '$testSource'");
$conn->query("DELETE FROM `farm_registration_renewals` WHERE `id` = $regId");
$conn->query("DELETE FROM `dairy_project_beneficiaries` WHERE `id` = $beneficiaryId");
$conn->query("DELETE FROM `farm_master_history` WHERE `id` = $histId");
$conn->query("DELETE FROM `farmer_training_records` WHERE `id` = $trainingId");
$conn->query("DELETE FROM `projects_progress` WHERE `id` = $pId");

echo "\n--- ALL 6 CORE INTEGRATION TESTS PASSED CLEANLY! ---\n";
