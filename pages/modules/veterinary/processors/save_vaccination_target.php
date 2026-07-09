<?php
session_start();
require_once '../../../../config/db_connect.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_SESSION['role']) || $_SESSION['role'] !== 'veterinary_surgeon') {
    header("Location: ../vaccination_targets.php?status=error&msg=Unauthorized");
    exit();
}

// 1. Collect inputs cleanly
$range_id               = intval($_POST['range_id']);
$year                   = intval($_POST['year']);
$animal_type            = $_POST['animal_type'];
$quantity               = intval($_POST['quantity']);
$target_fmd             = intval($_POST['target_fmd']);
$target_bq              = intval($_POST['target_bq']);
$target_hs              = intval($_POST['target_hs']);
$available_ldo_count    = intval($_POST['available_ldo_count']);
$allocated_ldo_target   = intval($_POST['allocated_ldo_target']);
$assigned_vaccinator_id = intval($_POST['assigned_vaccinator_id']);
$allocated_man_days     = intval($_POST['allocated_man_days']);
$syringes_10cc_req      = intval($_POST['syringes_10cc_req']);
$needles_14g_dozen_req  = intval($_POST['needles_14g_dozen_req']);
$fuel_liters_per_month  = floatval($_POST['fuel_liters_per_month']);

$mysqli->begin_transaction();

try {
    // A. Keep live population manual entry fields synced up nicely
    $pop_check = $mysqli->prepare("SELECT id FROM animal_populations WHERE range_id = ? AND year = ? AND animal_type = ?");
    $pop_check->bind_param("iis", $range_id, $year, $animal_type);
    $pop_check->execute();
    $pop_exists = $pop_check->get_result()->fetch_assoc();
    $pop_check->close();

    if ($pop_exists) {
        $pop_stmt = $mysqli->prepare("UPDATE animal_populations SET quantity = ? WHERE id = ?");
        $pop_stmt->bind_param("ii", $quantity, $pop_exists['id']);
    } else {
        $pop_stmt = $mysqli->prepare("INSERT INTO animal_populations (range_id, year, animal_type, quantity) VALUES (?, ?, ?, ?)");
        $pop_stmt->bind_param("iisi", $range_id, $year, $animal_type, $quantity);
    }
    $pop_stmt->execute();
    $pop_stmt->close();

    // B. Save configuration parameters directly mapping assigned_vaccinator_id reference column
    $target_check = $mysqli->prepare("SELECT id FROM annual_vaccination_targets WHERE range_id = ? AND year = ?");
    $target_check->bind_param("ii", $range_id, $year);
    $target_check->execute();
    $target_exists = $target_check->get_result()->fetch_assoc();
    $target_check->close();

    if ($target_exists) {
        $vax_stmt = $mysqli->prepare("UPDATE annual_vaccination_targets SET assigned_vaccinator_id = ?, target_fmd = ?, target_bq = ?, target_hs = ?, available_ldo_count = ?, allocated_ldo_target = ?, casual_vaccinators_needed = 1, allocated_man_days = ?, syringes_10cc_req = ?, needles_14g_dozen_req = ?, fuel_liters_per_month = ? WHERE id = ?");
        $vax_stmt->bind_param("iiiiiiiiidi", $assigned_vaccinator_id, $target_fmd, $target_bq, $target_hs, $available_ldo_count, $allocated_ldo_target, $allocated_man_days, $syringes_10cc_req, $needles_14g_dozen_req, $fuel_liters_per_month, $target_exists['id']);
    } else {
        $vax_stmt = $mysqli->prepare("INSERT INTO annual_vaccination_targets (year, range_id, assigned_vaccinator_id, target_fmd, target_bq, target_hs, available_ldo_count, allocated_ldo_target, casual_vaccinators_needed, allocated_man_days, syringes_10cc_req, needles_14g_dozen_req, fuel_liters_per_month) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 1, ?, ?, ?, ?)");
        $vax_stmt->bind_param("iiiiiiiiiiid", $year, $range_id, $assigned_vaccinator_id, $target_fmd, $target_bq, $target_hs, $available_ldo_count, $allocated_ldo_target, $allocated_man_days, $syringes_10cc_req, $needles_14g_dozen_req, $fuel_liters_per_month);
    }
    $vax_stmt->execute();
    $vax_stmt->close();

    $mysqli->commit();
    $_SESSION['msg'] = "Annual Matrix metrics and Casual Allocations saved properly.";
    $_SESSION['msg_type'] = "success";

} catch (Exception $e) {
    $mysqli->rollback();
    $_SESSION['msg'] = "Transaction error encountered: " . htmlspecialchars($e->getMessage());
    $_SESSION['msg_type'] = "danger";
}

header("Location: ../vaccination_targets.php?year=" . $year);
$mysqli->close();
exit();