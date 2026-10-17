<?php
require_once __DIR__ . '/../config/db_connect.php';

echo "--- TESTING PROJECT ENTRY MODULE & OVERVIEW SUITE ---\n";

// 1. Verify Database Schema for Projects
echo "\n[1] Verifying Database Schema for projects_progress...\n";
$expectedCols = [
    'range_office_name', 'activity', 'funding_agent', 'target_beneficiaries',
    'dept_contribution_amount', 'dept_subsidy_percentage', 'dept_contribution_activity',
    'beneficiary_contribution_amount', 'beneficiary_contribution_activity',
    'start_date', 'end_date'
];

$res = $mysqli->query("SHOW COLUMNS FROM `projects_progress`");
$existingCols = [];
while ($row = $res->fetch_assoc()) {
    $existingCols[] = $row['Field'];
}

$missing = array_diff($expectedCols, $existingCols);
if (empty($missing)) {
    echo "SUCCESS: All required tracking columns exist in `projects_progress`!\n";
} else {
    echo "FAILURE: Missing columns: " . implode(', ', $missing) . "\n";
    exit(1);
}

// 2. Test Project Insert with Core Fields and Dual Contribution Tracking
echo "\n[2] Testing Project Entry with Dual Contribution Tracking...\n";
$testRangeName = "Batticaloa Range Office";
$testType = "PSDG";
$testName = "Renovation of Cattle Shed";
$testActivity = "Renovation of Cattle Shed";
$testFunding = "PSDG / Provincial Ministry of Agriculture";
$testBeneficiaries = 30;
$testStartDate = "2026-03-01";
$testEndDate = "2026-09-30";

$testDeptAmount = 150000.00;
$testDeptSubsidy = 75.00;
$testDeptActivity = "Floor concreting, brick partition walls, and drainage channel";

$testBenAmount = 50000.00;
$testBenActivity = "Covering remaining material needs like a roof & timber rafters";
$testSummary = "Core project entry test execution";

$stmt = $mysqli->prepare("
    INSERT INTO `projects_progress` 
    (range_id, range_office_name, project_type, project_name, activity, funding_agent,
     target_beneficiaries, start_date, end_date, 
     dept_contribution_amount, dept_subsidy_percentage, dept_contribution_activity, 
     beneficiary_contribution_amount, beneficiary_contribution_activity, 
     summary, priority, status, progress_percent) 
    VALUES (1, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Medium', 'In Progress', 0)
");
$stmt->bind_param(
    "sssssissddsdss", 
    $testRangeName, 
    $testType, 
    $testName, 
    $testActivity, 
    $testFunding, 
    $testBeneficiaries, 
    $testStartDate, 
    $testEndDate, 
    $testDeptAmount, 
    $testDeptSubsidy, 
    $testDeptActivity, 
    $testBenAmount, 
    $testBenActivity, 
    $testSummary
);
$stmt->execute();
$projectId = $mysqli->insert_id;
$stmt->close();

echo "Inserted Project ID: $projectId\n";

// 3. Verify Project Fetch and Dual Contribution Retrieval
echo "\n[3] Verifying Dual Contribution and Core Field Retrieval...\n";
$chkStmt = $mysqli->prepare("SELECT * FROM `projects_progress` WHERE id = ?");
$chkStmt->bind_param("i", $projectId);
$chkStmt->execute();
$record = $chkStmt->get_result()->fetch_assoc();
$chkStmt->close();

if ($record && 
    $record['range_office_name'] === $testRangeName &&
    $record['project_type'] === $testType &&
    $record['project_name'] === $testName &&
    intval($record['target_beneficiaries']) === 30 &&
    floatval($record['dept_contribution_amount']) == 150000.00 &&
    floatval($record['dept_subsidy_percentage']) == 75.00 &&
    $record['dept_contribution_activity'] === $testDeptActivity &&
    floatval($record['beneficiary_contribution_amount']) == 50000.00 &&
    $record['beneficiary_contribution_activity'] === $testBenActivity) {
    echo "SUCCESS: Dual contribution tracking & core fields verified completely!\n";
    echo "  - Dept: Rs. " . number_format($record['dept_contribution_amount'], 2) . " ({$record['dept_subsidy_percentage']}%) | {$record['dept_contribution_activity']}\n";
    echo "  - Beneficiary: Rs. " . number_format($record['beneficiary_contribution_amount'], 2) . " | {$record['beneficiary_contribution_activity']}\n";
    echo "  - Target: {$record['target_beneficiaries']} Beneficiaries\n";
    echo "  - Timeline: {$record['start_date']} to {$record['end_date']}\n";
} else {
    echo "FAILURE: Fetched record did not match expected values.\n";
    print_r($record);
    exit(1);
}

// 4. Verify Exclusion of Location & Assigned Officers
echo "\n[4] Verifying Form Files Exclude 'Location' and 'Assigned Officers'...\n";
$modalContent = file_get_contents(__DIR__ . '/../pages/modules/veterinary/model/add_project_modal.php');
if (strpos($modalContent, 'name="location"') === false && 
    strpos($modalContent, 'Location / GN Division') === false &&
    strpos($modalContent, 'name="officers[]"') === false &&
    strpos($modalContent, 'Assigned Officers') === false) {
    echo "SUCCESS: add_project_modal.php completely excludes 'Location' and 'Assigned Officers'!\n";
} else {
    echo "FAILURE: add_project_modal.php still contains omitted fields.\n";
    exit(1);
}

// 5. Verify Table Columns in projects_progress.php
echo "\n[5] Verifying 8 Exact Columns in Overview Table...\n";
$overviewContent = file_get_contents(__DIR__ . '/../pages/modules/veterinary/projects_progress.php');
$expectedHeaders = [
    'Serial Number',
    'Activity',
    'Funding Agent',
    'Department Contribution (Amount &amp; Activity)',
    'Beneficiary Contribution (Amount &amp; Activity)',
    'Target Numbers',
    'Start Date',
    'Expected End Date'
];

$allHeadersFound = true;
foreach ($expectedHeaders as $h) {
    if (strpos($overviewContent, $h) === false) {
        echo "Missing header in table: $h\n";
        $allHeadersFound = false;
    }
}

if ($allHeadersFound) {
    echo "SUCCESS: All 8 required overview table headers verified in projects_progress.php!\n";
} else {
    echo "FAILURE: Overview table headers mismatch.\n";
    exit(1);
}

// Clean up
$mysqli->query("DELETE FROM `projects_progress` WHERE id = $projectId");
echo "\n--- ALL PROJECT ENTRY MODULE & OVERVIEW SUITE TESTS PASSED CLEANLY! ---\n";
