<?php
session_start();
require_once '../../../../config/db_connect.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || $_SESSION['role'] !== 'veterinary_surgeon') {
    header("Location: ../vaccination_targets.php?status=error&msg=Unauthorized");
    exit();
}

$range_id = intval($_SESSION['range_id']);
$year     = intval($_POST['year']);

$target_fmd                = intval($_POST['target_fmd'] ?? 0);
$target_bq                 = intval($_POST['target_bq'] ?? 0);
$target_hs                 = intval($_POST['target_hs'] ?? 0);
$available_ldo_count       = intval($_POST['available_ldo_count'] ?? 0);
$allocated_ldo_target      = intval($_POST['allocated_ldo_target'] ?? 0);
$casual_vaccinators_needed = intval($_POST['casual_vaccinators_needed'] ?? 0);
$allocated_man_days        = intval($_POST['allocated_man_days'] ?? 0);
$syringes_10cc_req         = intval($_POST['syringes_10cc_req'] ?? 0);
$needles_14g_dozen_req     = intval($_POST['needles_14g_dozen_req'] ?? 0);
$fuel_liters_per_month     = floatval($_POST['fuel_liters_per_month'] ?? 0);

$stmt = $mysqli->prepare("
    INSERT INTO annual_vaccination_targets 
    (range_id, year, target_fmd, target_bq, target_hs, available_ldo_count, allocated_ldo_target, casual_vaccinators_needed, allocated_man_days, syringes_10cc_req, needles_14g_dozen_req, fuel_liters_per_month)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ON DUPLICATE KEY UPDATE
    target_fmd = VALUES(target_fmd),
    target_bq = VALUES(target_bq),
    target_hs = VALUES(target_hs),
    available_ldo_count = VALUES(available_ldo_count),
    allocated_ldo_target = VALUES(allocated_ldo_target),
    casual_vaccinators_needed = VALUES(casual_vaccinators_needed),
    allocated_man_days = VALUES(allocated_man_days),
    syringes_10cc_req = VALUES(syringes_10cc_req),
    needles_14g_dozen_req = VALUES(needles_14g_dozen_req),
    fuel_liters_per_month = VALUES(fuel_liters_per_month)
");

if ($stmt) {
    $stmt->bind_param("iiiiiiiiiiid", $range_id, $year, $target_fmd, $target_bq, $target_hs, $available_ldo_count, $allocated_ldo_target, $casual_vaccinators_needed, $allocated_man_days, $syringes_10cc_req, $needles_14g_dozen_req, $fuel_liters_per_month);

    if ($stmt->execute()) {
        $_SESSION['msg'] = "Vaccination target and resource allocations configured successfully.";
        $_SESSION['msg_type'] = "success";
    } else {
        $_SESSION['msg'] = "Database error: " . htmlspecialchars($stmt->error);
        $_SESSION['msg_type'] = "danger";
    }
    $stmt->close();
} else {
    $_SESSION['msg'] = "Statement prep error: " . htmlspecialchars($mysqli->error);
    $_SESSION['msg_type'] = "danger";
}

header("Location: ../vaccination_targets.php?year=" . $year);
$mysqli->close();
exit();
