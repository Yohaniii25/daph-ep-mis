<?php
session_start();
require_once __DIR__ . '/../../../../config/db_connect.php';

/** @var mysqli $mysqli */
global $mysqli;

$allowed_roles = [
    'veterinary_surgeon',
    'government_veterinary_surgeon',
    'additional_veterinary_surgeon',
    'sms',
    'district_dd',
    'deputy_director_district',
    'deputy_director_hq_1',
    'provincial_director',
    'admin',
    'super_admin'
];

if (!isset($_SESSION['logged_in']) || !in_array($_SESSION['role'] ?? '', $allowed_roles, true)) {
    die("Access denied: Invalid authentication clearance profile.");
}

$user_id = $_SESSION['user_id'] ?? null;
$session_range_id = $_SESSION['range_id'] ?? null;

$action = $_POST['action'] ?? $_GET['action'] ?? '';
$report_year = intval($_POST['year'] ?? $_POST['report_year'] ?? $_GET['year'] ?? date('Y'));
$range_id = intval($_POST['range_id'] ?? $_GET['range_id'] ?? $session_range_id ?? 1);

// Indicator code to clean descriptive name lookup
$indicator_name_map = [
    'b.7'  => 'Issue of she goats',
    'b.8'  => 'Issue of stud goats',
    'b.9'  => 'Issue of Heifers/Cows',
    'b.10' => 'Issue of bull calves',
    'b.11' => 'Issue of heifers(Buffalo)',
    'b.12' => 'Issue of bull calves (Buffalo)',
    'b.13' => 'Issue of D/O Chicks',
    'b.14' => 'Issue of M/O Chicks'
];

// ─────────────────────────────────────────────────────────────────────────────
// 1. SAVE / UPDATE ANNUAL PRODUCTION ISSUE TARGETS
// ─────────────────────────────────────────────────────────────────────────────
if ($action === 'save_targets') {
    $t_she_goats       = intval($_POST['target_issue_she_goats'] ?? 0);
    $t_stud_goats      = intval($_POST['target_issue_stud_goats'] ?? 0);
    $t_heifers_cows    = intval($_POST['target_issue_heifers_cows'] ?? 0);
    $t_bull_calves     = intval($_POST['target_issue_bull_calves'] ?? 0);
    $t_buf_heifers     = intval($_POST['target_issue_buffalo_heifers'] ?? 0);
    $t_buf_bull_calves = intval($_POST['target_issue_buffalo_bull_calves'] ?? 0);
    $t_do_chicks       = intval($_POST['target_issue_do_chicks'] ?? 0);
    $t_mo_chicks       = intval($_POST['target_issue_mo_chicks'] ?? 0);

    // Check if target record exists for this range and year
    $check_stmt = $mysqli->prepare("SELECT id FROM annual_breeding_targets WHERE range_id = ? AND year = ?");
    $check_stmt->bind_param("ii", $range_id, $report_year);
    $check_stmt->execute();
    $existing = $check_stmt->get_result()->fetch_assoc();
    $check_stmt->close();

    if ($existing) {
        $upd_stmt = $mysqli->prepare("
            UPDATE annual_breeding_targets SET
                target_issue_she_goats = ?,
                target_issue_stud_goats = ?,
                target_issue_heifers_cows = ?,
                target_issue_bull_calves = ?,
                target_issue_buffalo_heifers = ?,
                target_issue_buffalo_bull_calves = ?,
                target_issue_do_chicks = ?,
                target_issue_mo_chicks = ?
            WHERE range_id = ? AND year = ?
        ");
        $upd_stmt->bind_param(
            "iiiiiiiiii",
            $t_she_goats,
            $t_stud_goats,
            $t_heifers_cows,
            $t_bull_calves,
            $t_buf_heifers,
            $t_buf_bull_calves,
            $t_do_chicks,
            $t_mo_chicks,
            $range_id,
            $report_year
        );
        $upd_stmt->execute();
        $upd_stmt->close();
    } else {
        $ins_stmt = $mysqli->prepare("
            INSERT INTO annual_breeding_targets (
                range_id, year,
                target_issue_she_goats,
                target_issue_stud_goats,
                target_issue_heifers_cows,
                target_issue_bull_calves,
                target_issue_buffalo_heifers,
                target_issue_buffalo_bull_calves,
                target_issue_do_chicks,
                target_issue_mo_chicks
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $ins_stmt->bind_param(
            "iiiiiiiiii",
            $range_id,
            $report_year,
            $t_she_goats,
            $t_stud_goats,
            $t_heifers_cows,
            $t_bull_calves,
            $t_buf_heifers,
            $t_buf_bull_calves,
            $t_do_chicks,
            $t_mo_chicks
        );
        $ins_stmt->execute();
        $ins_stmt->close();
    }

    header("Location: ../animal_breeding.php?tab=production&year={$report_year}&range_id={$range_id}&status=success&msg=" . urlencode("Production issue targets successfully configured for Year {$report_year}."));
    exit();
}

// ─────────────────────────────────────────────────────────────────────────────
// 2. LOG NEW PRODUCTION ISSUE RECORD
// ─────────────────────────────────────────────────────────────────────────────
if ($action === 'create') {
    $issue_date          = trim($_POST['issue_date'] ?? date('Y-m-d'));
    $report_month        = intval($_POST['report_month'] ?? date('n', strtotime($issue_date)));
    $indicator_code      = trim($_POST['indicator_code'] ?? '');
    $indicator_name      = $indicator_name_map[$indicator_code] ?? trim($_POST['indicator_name'] ?? $indicator_code);
    $quantity            = intval($_POST['quantity'] ?? 0);
    $beneficiary_name    = trim($_POST['beneficiary_name'] ?? '');
    $beneficiary_nic     = trim($_POST['beneficiary_nic'] ?? '');
    $beneficiary_address = trim($_POST['beneficiary_address'] ?? '');
    $remarks             = trim($_POST['remarks'] ?? '');

    if (empty($indicator_code) || $quantity <= 0) {
        header("Location: ../animal_breeding.php?tab=production&year={$report_year}&range_id={$range_id}&status=error&msg=" . urlencode("Please specify a valid indicator and quantity issued."));
        exit();
    }

    $ins_stmt = $mysqli->prepare("
        INSERT INTO production_issue_records (
            range_id, report_year, report_month, issue_date,
            indicator_code, indicator_name, quantity,
            beneficiary_name, beneficiary_nic, beneficiary_address,
            remarks, created_by
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $ins_stmt->bind_param(
        "iiisssissssi",
        $range_id,
        $report_year,
        $report_month,
        $issue_date,
        $indicator_code,
        $indicator_name,
        $quantity,
        $beneficiary_name,
        $beneficiary_nic,
        $beneficiary_address,
        $remarks,
        $user_id
    );

    if ($ins_stmt->execute()) {
        header("Location: ../animal_breeding.php?tab=production&year={$report_year}&range_id={$range_id}&status=success&msg=" . urlencode("Issue record logged successfully for {$indicator_name}."));
    } else {
        header("Location: ../animal_breeding.php?tab=production&year={$report_year}&range_id={$range_id}&status=error&msg=" . urlencode("Failed to log record: " . $mysqli->error));
    }
    $ins_stmt->close();
    exit();
}

// ─────────────────────────────────────────────────────────────────────────────
// 3. UPDATE AN EXISTING PRODUCTION ISSUE RECORD
// ─────────────────────────────────────────────────────────────────────────────
if ($action === 'update') {
    $id                  = intval($_POST['id'] ?? 0);
    $issue_date          = trim($_POST['issue_date'] ?? date('Y-m-d'));
    $report_month        = intval($_POST['report_month'] ?? date('n', strtotime($issue_date)));
    $indicator_code      = trim($_POST['indicator_code'] ?? '');
    $indicator_name      = $indicator_name_map[$indicator_code] ?? trim($_POST['indicator_name'] ?? $indicator_code);
    $quantity            = intval($_POST['quantity'] ?? 0);
    $beneficiary_name    = trim($_POST['beneficiary_name'] ?? '');
    $beneficiary_nic     = trim($_POST['beneficiary_nic'] ?? '');
    $beneficiary_address = trim($_POST['beneficiary_address'] ?? '');
    $remarks             = trim($_POST['remarks'] ?? '');

    if ($id <= 0 || empty($indicator_code) || $quantity <= 0) {
        header("Location: ../animal_breeding.php?tab=production&year={$report_year}&range_id={$range_id}&status=error&msg=" . urlencode("Invalid update parameters."));
        exit();
    }

    $upd_stmt = $mysqli->prepare("
        UPDATE production_issue_records SET
            report_month = ?,
            issue_date = ?,
            indicator_code = ?,
            indicator_name = ?,
            quantity = ?,
            beneficiary_name = ?,
            beneficiary_nic = ?,
            beneficiary_address = ?,
            remarks = ?
        WHERE id = ? AND range_id = ?
    ");
    $upd_stmt->bind_param(
        "isssissssii",
        $report_month,
        $issue_date,
        $indicator_code,
        $indicator_name,
        $quantity,
        $beneficiary_name,
        $beneficiary_nic,
        $beneficiary_address,
        $remarks,
        $id,
        $range_id
    );

    if ($upd_stmt->execute()) {
        header("Location: ../animal_breeding.php?tab=production&year={$report_year}&range_id={$range_id}&status=success&msg=" . urlencode("Issue record #{$id} updated successfully."));
    } else {
        header("Location: ../animal_breeding.php?tab=production&year={$report_year}&range_id={$range_id}&status=error&msg=" . urlencode("Failed to update record: " . $mysqli->error));
    }
    $upd_stmt->close();
    exit();
}

// ─────────────────────────────────────────────────────────────────────────────
// 4. DELETE A PRODUCTION ISSUE RECORD
// ─────────────────────────────────────────────────────────────────────────────
if ($action === 'delete') {
    $id = intval($_POST['id'] ?? $_GET['id'] ?? 0);
    if ($id > 0) {
        $del_stmt = $mysqli->prepare("DELETE FROM production_issue_records WHERE id = ? AND range_id = ?");
        $del_stmt->bind_param("ii", $id, $range_id);
        if ($del_stmt->execute()) {
            header("Location: ../animal_breeding.php?tab=production&year={$report_year}&range_id={$range_id}&status=success&msg=" . urlencode("Record deleted successfully."));
        } else {
            header("Location: ../animal_breeding.php?tab=production&year={$report_year}&range_id={$range_id}&status=error&msg=" . urlencode("Failed to delete record: " . $mysqli->error));
        }
        $del_stmt->close();
    } else {
        header("Location: ../animal_breeding.php?tab=production&year={$report_year}&range_id={$range_id}&status=error&msg=" . urlencode("Invalid record identifier."));
    }
    exit();
}

header("Location: ../animal_breeding.php?tab=production&year={$report_year}&range_id={$range_id}");
exit();
