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
$animal_type            = trim($_POST['animal_type'] ?? 'Others');
$target_fmd             = max(0, intval($_POST['target_fmd'] ?? 0));
$target_bq              = max(0, intval($_POST['target_bq'] ?? 0));
$target_hs              = max(0, intval($_POST['target_hs'] ?? 0));
$target_poultry_doses   = max(0, intval($_POST['target_poultry_doses'] ?? 0));
$available_ldo_count    = max(0, intval($_POST['available_ldo_count'] ?? 0));
$allocated_ldo_target   = max(0, intval($_POST['allocated_ldo_target'] ?? 0));
$assigned_vaccinator_id = !empty($_POST['assigned_vaccinator_id']) ? intval($_POST['assigned_vaccinator_id']) : null;
$allocated_man_days     = max(0, intval($_POST['allocated_man_days'] ?? 0));

// Removed fields safely defaulted to 0 for database integrity
$syringes_10cc_req      = max(0, intval($_POST['syringes_10cc_req'] ?? 0));
$needles_14g_dozen_req  = max(0, intval($_POST['needles_14g_dozen_req'] ?? 0));
$fuel_liters_per_month  = max(0.0, floatval($_POST['fuel_liters_per_month'] ?? 0.0));

$mysqli->begin_transaction();

try {
    // Note: Population baseline synchronization is read directly from animal_populations 
    // managed within the Range Statistics module. We do not overwrite it here.

    // Save configuration parameters mapping annual vaccination targets
    $target_check = $mysqli->prepare("SELECT id FROM annual_vaccination_targets WHERE range_id = ? AND year = ? AND animal_type = ?");
    $target_check->bind_param("iis", $range_id, $year, $animal_type);
    $target_check->execute();
    $target_exists = $target_check->get_result()->fetch_assoc();
    $target_check->close();

    if ($target_exists) {
        $vax_stmt = $mysqli->prepare("UPDATE annual_vaccination_targets 
            SET assigned_vaccinator_id = ?, target_fmd = ?, target_bq = ?, target_hs = ?, target_poultry_doses = ?, available_ldo_count = ?, allocated_ldo_target = ?, casual_vaccinators_needed = 1, allocated_man_days = ?, syringes_10cc_req = ?, needles_14g_dozen_req = ?, fuel_liters_per_month = ? 
            WHERE id = ?");
        $vax_stmt->bind_param(
            "iiiiiiiiiidi",
            $assigned_vaccinator_id,
            $target_fmd,
            $target_bq,
            $target_hs,
            $target_poultry_doses,
            $available_ldo_count,
            $allocated_ldo_target,
            $allocated_man_days,
            $syringes_10cc_req,
            $needles_14g_dozen_req,
            $fuel_liters_per_month,
            $target_exists['id']
        );
    } else {
        $vax_stmt = $mysqli->prepare("INSERT INTO annual_vaccination_targets 
            (year, range_id, animal_type, assigned_vaccinator_id, target_fmd, target_bq, target_hs, target_poultry_doses, available_ldo_count, allocated_ldo_target, casual_vaccinators_needed, allocated_man_days, syringes_10cc_req, needles_14g_dozen_req, fuel_liters_per_month) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1, ?, ?, ?, ?)");
        $vax_stmt->bind_param(
            "iisiiiiiiiiiid",
            $year,
            $range_id,
            $animal_type,
            $assigned_vaccinator_id,
            $target_fmd,
            $target_bq,
            $target_hs,
            $target_poultry_doses,
            $available_ldo_count,
            $allocated_ldo_target,
            $allocated_man_days,
            $syringes_10cc_req,
            $needles_14g_dozen_req,
            $fuel_liters_per_month
        );
    }
    $vax_stmt->execute();
    $vax_stmt->close();

    $mysqli->commit();
    $_SESSION['msg'] = "Annual vaccination target for " . htmlspecialchars($animal_type) . " configured successfully.";
    $_SESSION['msg_type'] = "success";
} catch (Exception $e) {
    $mysqli->rollback();
    $_SESSION['msg'] = "Transaction error encountered: " . htmlspecialchars($e->getMessage());
    $_SESSION['msg_type'] = "danger";
}

header("Location: ../vaccination_targets.php?year=" . $year . "&tab=targets");
$mysqli->close();
exit();
