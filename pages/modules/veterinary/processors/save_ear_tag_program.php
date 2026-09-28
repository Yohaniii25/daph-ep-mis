<?php
session_start();
require_once __DIR__ . '/../../../../config/db_connect.php';

/** @var mysqli $mysqli */
global $mysqli;

$is_ajax = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') || 
           (isset($_POST['is_ajax']) && $_POST['is_ajax'] == '1');

if (!isset($_SESSION['logged_in']) || !in_array($_SESSION['role'], ['veterinary_surgeon', 'sms'])) {
    if ($is_ajax) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Unauthorized access.']);
        exit();
    }
    header("Location: ../../../../index.php");
    exit();
}

$user_id = $_SESSION['user_id'] ?? null;
$range_id = $_SESSION['range_id'] ?? null;

if (empty($range_id)) {
    if ($is_ajax) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Veterinary Range context missing from active session.']);
        exit();
    }
    header("Location: ../ear_tag_balance.php?status=error&msg=No+range+context");
    exit();
}

// Fetch district_id from veterinary_ranges
$district_id = null;
$dist_stmt = $mysqli->prepare("SELECT district_id FROM veterinary_ranges WHERE id = ?");
if ($dist_stmt) {
    $dist_stmt->bind_param("i", $range_id);
    $dist_stmt->execute();
    $dist_res = $dist_stmt->get_result();
    if ($d_row = $dist_res->fetch_assoc()) {
        $district_id = $d_row['district_id'];
    }
    $dist_stmt->close();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    if ($is_ajax) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
        exit();
    }
    header("Location: ../ear_tag_balance.php");
    exit();
}

$program_id     = intval($_POST['program_id'] ?? 0);
$program_date   = trim($_POST['program_date'] ?? '');
$nic_no         = strtoupper(trim($_POST['nic_no'] ?? ''));
$farmer_name    = trim($_POST['farmer_name'] ?? '');
$farm_reg_no    = trim($_POST['farm_reg_no'] ?? '');
$address        = trim($_POST['address'] ?? '');
$tags_used      = intval($_POST['tags_used'] ?? 0);
$tags_spoiled   = intval($_POST['tags_spoiled'] ?? 0);
$staff_involved = trim($_POST['staff_involved'] ?? '');
$remarks        = trim($_POST['remarks'] ?? '');

// Parse dynamic animal details & cattle voucher records
$animals_input  = $_POST['animals'] ?? [];
$animals = [];

if (is_array($animals_input)) {
    foreach ($animals_input as $anim) {
        $ear_tag_no = trim($anim['ear_tag_number'] ?? '');
        $voucher_no = trim($anim['voucher_number'] ?? '');
        $breed      = trim($anim['breed'] ?? '');
        $sex        = trim($anim['sex'] ?? '');
        $age        = trim($anim['age'] ?? '');

        // If at least tag number or voucher number is provided
        if (!empty($ear_tag_no) || !empty($voucher_no)) {
            $animals[] = [
                'ear_tag_number' => $ear_tag_no,
                'voucher_number' => $voucher_no,
                'breed'          => $breed,
                'sex'            => $sex,
                'age'            => $age
            ];
        }
    }
}

// If user entered animal rows and tags_used wasn't explicitly entered, sync count
if (count($animals) > 0 && $tags_used <= 0) {
    $tags_used = count($animals);
}

// Basic validation
if (empty($program_date) || empty($nic_no) || empty($farmer_name)) {
    $err_msg = 'Please fill in required fields: Date, Farmer Name, and IC Number.';
    if ($is_ajax) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => $err_msg]);
        exit();
    }
    header("Location: ../ear_tag_balance.php?tab=programs&status=error&msg=" . urlencode($err_msg));
    exit();
}

$mysqli->begin_transaction();

try {
    // 1. Check or link farmer in farmers table
    $farmer_id = null;
    $chk_farmer = $mysqli->prepare("SELECT id, farm_registration_no, location_address FROM farmers WHERE nic_no = ? LIMIT 1");
    if ($chk_farmer) {
        $chk_farmer->bind_param("s", $nic_no);
        $chk_farmer->execute();
        $f_res = $chk_farmer->get_result();
        if ($f_row = $f_res->fetch_assoc()) {
            $farmer_id = $f_row['id'];
            // If address or reg_no was blank in db but provided now, update it
            if (empty($f_row['farm_registration_no']) && !empty($farm_reg_no)) {
                $upd_f = $mysqli->prepare("UPDATE farmers SET farm_registration_no = ? WHERE id = ?");
                if ($upd_f) {
                    $upd_f->bind_param("si", $farm_reg_no, $farmer_id);
                    $upd_f->execute();
                    $upd_f->close();
                }
            }
        }
        $chk_farmer->close();
    }

    // If farmer does not exist, insert into farmers table to link registry
    if (!$farmer_id) {
        $ins_farmer = $mysqli->prepare("INSERT INTO farmers (nic_no, full_name, farm_registration_no, location_address, district_id, range_id, is_active) VALUES (?, ?, ?, ?, ?, ?, 1)");
        if ($ins_farmer) {
            $ins_farmer->bind_param("ssssii", $nic_no, $farmer_name, $farm_reg_no, $address, $district_id, $range_id);
            if ($ins_farmer->execute()) {
                $farmer_id = $ins_farmer->insert_id;
            }
            $ins_farmer->close();
        }
    }

    $old_program_date = null;

    if ($program_id > 0) {
        // Fetch old program date for usage recalculation if month changed
        $q_old = $mysqli->prepare("SELECT program_date FROM ear_tag_programs WHERE id = ? AND range_id = ?");
        if ($q_old) {
            $q_old->bind_param("ii", $program_id, $range_id);
            $q_old->execute();
            $old_res = $q_old->get_result();
            if ($old_row = $old_res->fetch_assoc()) {
                $old_program_date = $old_row['program_date'];
            }
            $q_old->close();
        }

        // Update existing program (Strictly NO Program Title!)
        $upd_prog = $mysqli->prepare("
            UPDATE ear_tag_programs 
            SET program_date = ?, farmer_id = ?, farmer_name = ?, nic_no = ?, farm_reg_no = ?, address = ?, 
                tags_used = ?, tags_spoiled = ?, staff_involved = ?, remarks = ?, updated_at = NOW() 
            WHERE id = ? AND range_id = ?
        ");
        if (!$upd_prog) {
            throw new Exception("Error preparing program update: " . $mysqli->error);
        }
        $upd_prog->bind_param("sissssissiii", 
            $program_date, $farmer_id, $farmer_name, $nic_no, $farm_reg_no, $address,
            $tags_used, $tags_spoiled, $staff_involved, $remarks, $program_id, $range_id
        );
        if (!$upd_prog->execute()) {
            throw new Exception("Error executing program update: " . $upd_prog->error);
        }
        $upd_prog->close();

        // Delete previous vouchers to rewrite fresh
        $del_v = $mysqli->prepare("DELETE FROM cattle_vouchers WHERE tag_program_id = ?");
        if ($del_v) {
            $del_v->bind_param("i", $program_id);
            $del_v->execute();
            $del_v->close();
        }
    } else {
        // Insert new tagging program (Strictly NO Program Title!)
        $ins_prog = $mysqli->prepare("
            INSERT INTO ear_tag_programs 
            (range_id, district_id, program_date, farmer_id, farmer_name, nic_no, farm_reg_no, address, tags_used, tags_spoiled, staff_involved, remarks, created_by) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        if (!$ins_prog) {
            throw new Exception("Error preparing program insert: " . $mysqli->error);
        }
        $ins_prog->bind_param("iisissssissii", 
            $range_id, $district_id, $program_date, $farmer_id, $farmer_name, $nic_no, $farm_reg_no, $address,
            $tags_used, $tags_spoiled, $staff_involved, $remarks, $user_id
        );
        if (!$ins_prog->execute()) {
            throw new Exception("Error executing program insert: " . $ins_prog->error);
        }
        $program_id = $ins_prog->insert_id;
        $ins_prog->close();
    }

    // 2. Interconnect each ear tag with cattle vouchers (the animal's identity card)
    if (!empty($animals) && $program_id > 0) {
        $ins_v = $mysqli->prepare("
            INSERT INTO cattle_vouchers 
            (tag_program_id, farmer_id, farmer_nic, farm_reg_no, range_id, ear_tag_number, voucher_number, breed, sex, age, issue_date, status, created_by) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Active', ?)
        ");
        if (!$ins_v) {
            throw new Exception("Error preparing cattle voucher insert: " . $mysqli->error);
        }

        foreach ($animals as $an) {
            $ins_v->bind_param("iisssssssssi", 
                $program_id, $farmer_id, $nic_no, $farm_reg_no, $range_id,
                $an['ear_tag_number'], $an['voucher_number'], $an['breed'], $an['sex'], $an['age'],
                $program_date, $user_id
            );
            if (!$ins_v->execute()) {
                throw new Exception("Error saving cattle voucher [{$an['ear_tag_number']}]: " . $ins_v->error);
            }
        }
        $ins_v->close();
    }

    // 3. Dynamic Real-Time Synchronization with Monthly Summary (ear_tag_usage)
    // Synchronize current program's month/year
    syncMonthUsage($mysqli, $range_id, $district_id, $program_date, $user_id);

    // If month/year was changed on edit, also resync old month
    if (!empty($old_program_date)) {
        $old_time = strtotime($old_program_date);
        $new_time = strtotime($program_date);
        if (date('Y-m', $old_time) !== date('Y-m', $new_time)) {
            syncMonthUsage($mysqli, $range_id, $district_id, $old_program_date, $user_id);
        }
    }

    $mysqli->commit();

    if ($is_ajax) {
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'message' => 'Ear tagging program and cattle vouchers saved successfully.',
            'program_id' => $program_id
        ]);
        exit();
    }

    header("Location: ../ear_tag_balance.php?tab=programs&status=success&msg=Tagging+Program+Saved+Successfully");
    exit();

} catch (Exception $e) {
    $mysqli->rollback();
    $err = $e->getMessage();
    if ($is_ajax) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => $err]);
        exit();
    }
    header("Location: ../ear_tag_balance.php?tab=programs&status=error&msg=" . urlencode($err));
    exit();
}

/**
 * Helper function to synchronize real-time aggregated used & spoiled counts into ear_tag_usage
 */
function syncMonthUsage($mysqli, $range_id, $district_id, $target_date, $user_id) {
    $ts = strtotime($target_date);
    $report_year  = intval(date('Y', $ts));
    $report_month = intval(date('n', $ts));

    // Calculate aggregated totals from ear_tag_programs for this month
    $sum_stmt = $mysqli->prepare("
        SELECT COALESCE(SUM(tags_used), 0) AS total_used, 
               COALESCE(SUM(tags_spoiled), 0) AS total_spoiled 
        FROM ear_tag_programs 
        WHERE range_id = ? AND YEAR(program_date) = ? AND MONTH(program_date) = ?
    ");
    $total_used = 0;
    $total_spoiled = 0;
    if ($sum_stmt) {
        $sum_stmt->bind_param("iii", $range_id, $report_year, $report_month);
        $sum_stmt->execute();
        $sum_res = $sum_stmt->get_result();
        if ($s_row = $sum_res->fetch_assoc()) {
            $total_used = intval($s_row['total_used']);
            $total_spoiled = intval($s_row['total_spoiled']);
        }
        $sum_stmt->close();
    }

    // Check if record exists in ear_tag_usage
    $chk_u = $mysqli->prepare("SELECT id, opening_balance, received_qty, transferred_qty FROM ear_tag_usage WHERE range_id = ? AND report_year = ? AND report_month = ?");
    if ($chk_u) {
        $chk_u->bind_param("iii", $range_id, $report_year, $report_month);
        $chk_u->execute();
        $u_res = $chk_u->get_result();

        if ($u_row = $u_res->fetch_assoc()) {
            $u_id = $u_row['id'];
            $opening     = intval($u_row['opening_balance']);
            $received    = intval($u_row['received_qty']);
            $transferred = intval($u_row['transferred_qty']);
            $closing     = ($opening + $received) - ($total_used + $total_spoiled + $transferred);

            $upd = $mysqli->prepare("UPDATE ear_tag_usage SET used_qty = ?, spoilt_qty = ?, closing_balance = ?, updated_at = NOW() WHERE id = ?");
            if ($upd) {
                $upd->bind_param("iiii", $total_used, $total_spoiled, $closing, $u_id);
                $upd->execute();
                $upd->close();
            }
        } else {
            // Check if previous month has closing balance to carry over as opening balance
            $prev_closing = 0;
            $prev_month = $report_month - 1;
            $prev_year = $report_year;
            if ($prev_month === 0) {
                $prev_month = 12;
                $prev_year = $report_year - 1;
            }
            $p_stmt = $mysqli->prepare("SELECT closing_balance FROM ear_tag_usage WHERE range_id = ? AND report_year = ? AND report_month = ? LIMIT 1");
            if ($p_stmt) {
                $p_stmt->bind_param("iii", $range_id, $prev_year, $prev_month);
                $p_stmt->execute();
                $p_res = $p_stmt->get_result();
                if ($pr = $p_res->fetch_assoc()) {
                    $prev_closing = intval($pr['closing_balance']);
                }
                $p_stmt->close();
            }

            $opening = $prev_closing;
            $received = 0;
            $transferred = 0;
            $closing = ($opening + $received) - ($total_used + $total_spoiled + $transferred);

            $ins = $mysqli->prepare("
                INSERT INTO ear_tag_usage 
                (district_id, range_id, report_year, report_month, opening_balance, received_qty, used_qty, spoilt_qty, transferred_qty, closing_balance, created_by) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            if ($ins) {
                $ins->bind_param("iiiiiiiiiii", $district_id, $range_id, $report_year, $report_month, $opening, $received, $total_used, $total_spoiled, $transferred, $closing, $user_id);
                $ins->execute();
                $ins->close();
            }
        }
        $chk_u->close();
    }
}
