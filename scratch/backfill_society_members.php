<?php
require_once __DIR__ . '/../config/db_connect.php';
require_once __DIR__ . '/../pages/modules/veterinary/processors/dairy_db_migration.php';

ensure_dairy_project_tables($mysqli);

// 1. Fetch applications
$apps = $mysqli->query("SELECT * FROM `dairy_farm_applications`");
$enrolled = 0;

while ($app = $apps->fetch_assoc()) {
    $farm_reg_no = $app['farm_reg_no'];
    $name = $app['producer_name'];
    $nic = $app['nic_no'];
    $phone = !empty($app['contact_mobile']) ? $app['contact_mobile'] : $app['telephone_no'];
    $address = $app['address'];
    $gn = $app['ds_gn_division'];
    $date = !empty($app['survey_date']) ? $app['survey_date'] : date('Y-m-d');
    $range_id = 1;

    // Check summary for range_id
    if (!empty($app['summary_id'])) {
        $sq = $mysqli->query("SELECT range_id, district_id FROM `dairy_project_beneficiaries` WHERE id = " . intval($app['summary_id']));
        if ($sq && $srow = $sq->fetch_assoc()) {
            if (!empty($srow['range_id'])) $range_id = intval($srow['range_id']);
        }
    }

    // Check if society exists
    $soc_q = $mysqli->query("SELECT id FROM `dairy_hub_societies` WHERE range_id = {$range_id} LIMIT 1");
    $soc_id = ($soc_q && $s = $soc_q->fetch_assoc()) ? intval($s['id']) : 0;
    if ($soc_id === 0) {
        $mysqli->query("INSERT INTO `dairy_hub_societies` (range_id, district_id, society_name, status) VALUES ({$range_id}, 1, 'Dairy Hub Cooperative Society - Range #{$range_id}', 'Active')");
        $soc_id = $mysqli->insert_id;
    }

    // Check if member already in table
    $chk = $mysqli->query("SELECT id FROM `dairy_hub_society_members` WHERE range_id = {$range_id} AND nic = '{$mysqli->real_escape_string($nic)}'");
    if ($chk && $chk->num_rows === 0) {
        $cnt_q = $mysqli->query("SELECT COUNT(*) AS total FROM `dairy_hub_society_members` WHERE range_id = {$range_id}");
        $total = ($cnt_q && $c = $cnt_q->fetch_assoc()) ? intval($c['total']) : 0;
        $mem_no = sprintf("DHS-%02d-%s-%04d", $range_id, date('Y', strtotime($date)), $total + 1);

        $stmt = $mysqli->prepare("
            INSERT INTO `dairy_hub_society_members` 
            (society_id, range_id, district_id, membership_no, farm_reg_no, member_name, nic, phone, address, ds_gn_division, enrollment_date, enrollment_source, executive_position, is_executive, status, code_of_conduct_agreed, created_by)
            VALUES (?, ?, 1, ?, ?, ?, ?, ?, ?, ?, ?, 'Dairy Hub Application', 'Member', 0, 'Active', 1, 1)
        ");
        $stmt->bind_param("iisssssssss", $soc_id, $range_id, $mem_no, $farm_reg_no, $name, $nic, $phone, $address, $gn, $date);
        $stmt->execute();
        $stmt->close();
        $enrolled++;
    }
}

// 2. Fetch beneficiaries who might not have filled application yet
$bens = $mysqli->query("SELECT * FROM `dairy_project_beneficiaries`");
while ($b = $bens->fetch_assoc()) {
    $range_id = !empty($b['range_id']) ? intval($b['range_id']) : 1;
    $nic = $b['nic'];
    $farm_reg_no = $b['farm_reg_no'];
    $name = $b['beneficiary_name'];
    $phone = $b['phone'];
    $address = $b['address'];
    $date = date('Y-m-d');

    // Check if society exists
    $soc_q = $mysqli->query("SELECT id FROM `dairy_hub_societies` WHERE range_id = {$range_id} LIMIT 1");
    $soc_id = ($soc_q && $s = $soc_q->fetch_assoc()) ? intval($s['id']) : 0;
    if ($soc_id === 0) {
        $mysqli->query("INSERT INTO `dairy_hub_societies` (range_id, district_id, society_name, status) VALUES ({$range_id}, 1, 'Dairy Hub Cooperative Society - Range #{$range_id}', 'Active')");
        $soc_id = $mysqli->insert_id;
    }

    $chk = $mysqli->query("SELECT id FROM `dairy_hub_society_members` WHERE range_id = {$range_id} AND nic = '{$mysqli->real_escape_string($nic)}'");
    if ($chk && $chk->num_rows === 0) {
        $cnt_q = $mysqli->query("SELECT COUNT(*) AS total FROM `dairy_hub_society_members` WHERE range_id = {$range_id}");
        $total = ($cnt_q && $c = $cnt_q->fetch_assoc()) ? intval($c['total']) : 0;
        $mem_no = sprintf("DHS-%02d-%s-%04d", $range_id, date('Y', strtotime($date)), $total + 1);

        $stmt = $mysqli->prepare("
            INSERT INTO `dairy_hub_society_members` 
            (society_id, range_id, district_id, membership_no, farm_reg_no, member_name, nic, phone, address, ds_gn_division, enrollment_date, enrollment_source, executive_position, is_executive, status, code_of_conduct_agreed, created_by)
            VALUES (?, ?, 1, ?, ?, ?, ?, ?, ?, '', ?, 'Dairy Project Beneficiary', 'Member', 0, 'Active', 1, 1)
        ");
        $stmt->bind_param("iisssssss", $soc_id, $range_id, $mem_no, $farm_reg_no, $name, $nic, $phone, $address, $date);
        $stmt->execute();
        $stmt->close();
        $enrolled++;
    }
}

echo "Backfill finished. Newly enrolled members: {$enrolled}\n";

$all_mem = $mysqli->query("SELECT id, membership_no, member_name, executive_position FROM `dairy_hub_society_members`");
echo "Total Enrolled Society Members in DB: " . $all_mem->num_rows . "\n";
while ($m = $all_mem->fetch_assoc()) {
    echo " - [{$m['membership_no']}] {$m['member_name']} ({$m['executive_position']})\n";
}
