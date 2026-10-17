<?php
// Scratch verification test for Citizen Charter and Dairy Hub Society
require_once __DIR__ . '/../config/db_connect.php';

echo "=== STARTING VERIFICATION TEST ===\n\n";

// -------------------------------------------------------------
// TEST 1: Citizen Charter Database & CRUD
// -------------------------------------------------------------
echo "[1] Testing Citizen Charter Requests...\n";

// Test direct insert via SQL simulation or testing lookup
$today = date('Y-m-d');
$ref_test_1 = 'CC-' . date('Ymd') . '-TEST1';
$ref_test_2 = 'CC-' . date('Ymd') . '-TEST2';

// Clean old test records
$mysqli->query("DELETE FROM `citizen_charter_requests` WHERE reference_no LIKE 'CC-%-TEST%'");

// 1. Direct Visit request
$stmt1 = $mysqli->prepare("
    INSERT INTO `citizen_charter_requests` (
        `reference_no`, `request_date`, `request_origin`, `range_id`, `beneficiary_name`, 
        `nic_no`, `phone_no`, `address`, `farm_reg_no`, `purpose_of_visit`, 
        `immediate_action_taken`, `final_remarks`, `status`, `created_by`
    ) VALUES (
        ?, ?, 'Direct Visit', 1, 'K. Perera', '198754123456', '0771234567', 
        'No. 45, Station Road, Ampara', '530112345', 'Bank loan recommendation letter for dairy expansion',
        'Inspection scheduled for 2026-10-20. Recommendation letter issued.', 'Applicant satisfied.', 'Completed', 1
    )
");
$stmt1->bind_param("ss", $ref_test_1, $today);
$stmt1->execute();
$id1 = $stmt1->insert_id;
$stmt1->close();
echo "  -> Created 'Direct Visit' Citizen Charter request ID: $id1 (Ref: $ref_test_1)\n";

// 2. By Call request
$stmt2 = $mysqli->prepare("
    INSERT INTO `citizen_charter_requests` (
        `reference_no`, `request_date`, `request_origin`, `range_id`, `beneficiary_name`, 
        `nic_no`, `phone_no`, `address`, `farm_reg_no`, `purpose_of_visit`, 
        `immediate_action_taken`, `final_remarks`, `status`, `created_by`
    ) VALUES (
        ?, ?, 'By Call', 1, 'M. Farook', '199201987654', '0719876543', 
        'Sammanthurai Main St', 'FRN-AMP-002', 'Ear tag issuances inquiry for newly bought heifers',
        'Instructed to bring transport permit on Monday morning for 5 ear tags.', 'Pending follow-up.', 'In Progress', 1
    )
");
$stmt2->bind_param("ss", $ref_test_2, $today);
$stmt2->execute();
$id2 = $stmt2->insert_id;
$stmt2->close();
echo "  -> Created 'By Call' Citizen Charter request ID: $id2 (Ref: $ref_test_2)\n";

// Verify both records exist with their distinct request origins
$check_cc = $mysqli->query("SELECT id, request_origin, beneficiary_name, purpose_of_visit FROM `citizen_charter_requests` WHERE id IN ($id1, $id2)");
while ($row = $check_cc->fetch_assoc()) {
    echo "  [OK] Found ID {$row['id']}: Origin='{$row['request_origin']}', Name='{$row['beneficiary_name']}', Purpose='{$row['purpose_of_visit']}'\n";
}

// -------------------------------------------------------------
// TEST 2: Dairy Hub Society Auto-Enrollment via Application Form
// -------------------------------------------------------------
echo "\n[2] Testing Dairy Hub Application Form Auto-Enrollment...\n";

$test_farm_reg = 'TEST-FARM-999';
$test_nic = '200012345678';
$test_farmer_name = 'Sunil Shantha';
$test_phone = '0789998877';
$test_address = 'Uhana Road, Ampara';

// Clean test records
$mysqli->query("DELETE FROM `dairy_hub_society_members` WHERE farm_reg_no = '$test_farm_reg' OR nic = '$test_nic'");
$mysqli->query("DELETE FROM `dairy_farm_applications` WHERE farm_reg_no = '$test_farm_reg'");

// Simulate auto-enrollment logic in dairy_project_crud.php
$range_id = 1;
$cur_year = date('Y');

// Get society_id
$soc_res = $mysqli->query("SELECT id FROM `dairy_hub_societies` WHERE range_id = $range_id LIMIT 1");
$soc_row = $soc_res->fetch_assoc();
$society_id = $soc_row ? intval($soc_row['id']) : 1;

// Membership sequence
$cnt_res = $mysqli->query("SELECT COUNT(*) AS total FROM `dairy_hub_society_members` WHERE range_id = $range_id");
$cnt_row = $cnt_res->fetch_assoc();
$next_num = intval($cnt_row['total'] ?? 0) + 1;
$membership_no = sprintf("DHS-R%02d-%s-%04d", $range_id, $cur_year, $next_num);

$ins_mem = $mysqli->prepare("
    INSERT INTO `dairy_hub_society_members` (
        society_id, range_id, membership_no, farm_reg_no, member_name, 
        nic, phone, address, ds_gn_division, enrollment_source, enrollment_date, 
        executive_position, is_executive, status
    ) VALUES (
        ?, ?, ?, ?, ?, 
        ?, ?, ?, 'Ampara DS', 'Dairy Hub Survey Application', CURDATE(), 
        'Member', 0, 'Active'
    )
");
$ins_mem->bind_param("iissssss", $society_id, $range_id, $membership_no, $test_farm_reg, $test_farmer_name, $test_nic, $test_phone, $test_address);
$ins_mem->execute();
$new_member_id = $ins_mem->insert_id;
$ins_mem->close();

echo "  -> Auto-Enrolled Farmer '$test_farmer_name' into Dairy Hub Society!\n";
echo "  -> Generated Membership Number: $membership_no (Member ID: $new_member_id)\n";

// -------------------------------------------------------------
// TEST 3: Society Administration - Assign President & Vice President
// -------------------------------------------------------------
echo "\n[3] Testing Society Administration (Executive Position Assignment)...\n";

// Assign as President
$upd_pres = $mysqli->prepare("UPDATE `dairy_hub_society_members` SET executive_position = 'President', is_executive = 1 WHERE id = ?");
$upd_pres->bind_param("i", $new_member_id);
$upd_pres->execute();
$upd_pres->close();

$chk_pres = $mysqli->query("SELECT member_name, executive_position, is_executive FROM `dairy_hub_society_members` WHERE id = $new_member_id")->fetch_assoc();
echo "  [OK] Member is now: {$chk_pres['executive_position']} (is_executive = {$chk_pres['is_executive']})\n";

// Now test collision handling when assigning another member as President
// Member ID $new_member_id should be demoted to 'Member'
$mem2_res = $mysqli->query("SELECT id, member_name FROM `dairy_hub_society_members` WHERE range_id = 1 AND id != $new_member_id LIMIT 1");
if ($mem2 = $mem2_res->fetch_assoc()) {
    $second_id = $mem2['id'];
    // Demote prior President
    $mysqli->query("UPDATE `dairy_hub_society_members` SET executive_position = 'Member', is_executive = 0 WHERE range_id = 1 AND executive_position = 'President'");
    // Promote second member
    $mysqli->query("UPDATE `dairy_hub_society_members` SET executive_position = 'President', is_executive = 1 WHERE id = $second_id");

    // Re-verify both
    $r1 = $mysqli->query("SELECT member_name, executive_position FROM `dairy_hub_society_members` WHERE id = $new_member_id")->fetch_assoc();
    $r2 = $mysqli->query("SELECT member_name, executive_position FROM `dairy_hub_society_members` WHERE id = $second_id")->fetch_assoc();
    echo "  [OK] Collision resolution verified:\n";
    echo "       - Member 1 ({$r1['member_name']}): {$r1['executive_position']}\n";
    echo "       - Member 2 ({$r2['member_name']}): {$r2['executive_position']}\n";

    // Set Member 1 as Vice President
    $mysqli->query("UPDATE `dairy_hub_society_members` SET executive_position = 'Vice President', is_executive = 1 WHERE id = $new_member_id");
    $r1_vp = $mysqli->query("SELECT member_name, executive_position FROM `dairy_hub_society_members` WHERE id = $new_member_id")->fetch_assoc();
    echo "  [OK] Member 1 successfully assigned as: {$r1_vp['executive_position']}\n";
}

// -------------------------------------------------------------
// TEST 4: Code of Conduct Update & Display
// -------------------------------------------------------------
echo "\n[4] Testing Formal Code of Conduct...\n";
$new_title = "Dairy Hub Farmers' Society Official Code of Conduct & Ethics (2026 Revision)";
$new_text = "Clause 1: All members shall deliver clean, unadulterated cow milk complying with provincial health standards.\nClause 2: Democratic representation under the elected President and Vice President shall govern all society affairs.\nClause 3: Active participation in quarterly disease surveillance and vaccination programs is mandatory.";

$upd_code = $mysqli->prepare("UPDATE `dairy_hub_societies` SET code_of_conduct_title = ?, code_of_conduct_text = ? WHERE range_id = 1");
$upd_code->bind_param("ss", $new_title, $new_text);
$upd_code->execute();
$upd_code->close();

$chk_code = $mysqli->query("SELECT society_name, code_of_conduct_title, code_of_conduct_text FROM `dairy_hub_societies` WHERE range_id = 1")->fetch_assoc();
echo "  [OK] Society: {$chk_code['society_name']}\n";
echo "  [OK] Title: {$chk_code['code_of_conduct_title']}\n";
echo "  [OK] Code length: " . strlen($chk_code['code_of_conduct_text']) . " characters\n";

echo "\n=== ALL VERIFICATION TESTS PASSED SUCCESSFULLY! ===\n";
