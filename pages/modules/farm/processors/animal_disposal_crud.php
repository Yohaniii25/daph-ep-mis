<?php
// pages/modules/farm/processors/animal_disposal_crud.php
session_start();
require_once '../../../config/db_connect.php';

if (!isset($_SESSION['role'])) {
    header("Location: ../../../index.php");
    exit();
}

$action = $_REQUEST['action'] ?? '';
$user_id = $_SESSION['user_id'] ?? 1;

// Default redirect page fallback
$redirect_page = $_REQUEST['redirect_page'] ?? '../cattle_register.php';
// Sanitize redirect_page to ensure safety
if (strpos($redirect_page, '..') === false) {
    $redirect_page = '../' . basename($redirect_page);
}

if ($action === 'add') {
    $species = trim($_POST['species'] ?? 'Cattle');
    $disposal_date = $_POST['disposal_date'] ?? date('Y-m-d');
    $voucher_no = trim($_POST['voucher_no'] ?? '');
    $how_disposed_of = trim($_POST['how_disposed_of'] ?? 'Sold');
    if ($how_disposed_of === 'Other' && !empty($_POST['how_disposed_other'])) {
        $how_disposed_of = trim($_POST['how_disposed_other']);
    }
    
    $amount_realized = floatval($_POST['amount_realized'] ?? 0);
    $cash_receipt_info = trim($_POST['cash_receipt_info'] ?? '');
    
    $stud_bulls = max(0, intval($_POST['stud_bulls'] ?? 0));
    $draught_bulls = max(0, intval($_POST['draught_bulls'] ?? 0));
    $cows = max(0, intval($_POST['cows'] ?? 0));
    $heifer_calves = max(0, intval($_POST['heifer_calves'] ?? 0));
    $bull_calves = max(0, intval($_POST['bull_calves'] ?? 0));
    $remarks = trim($_POST['remarks'] ?? '');

    $total_animals = $stud_bulls + $draught_bulls + $cows + $heifer_calves + $bull_calves;

    $stmt = $mysqli->prepare("INSERT INTO animal_disposal_register 
        (user_id, species, disposal_date, voucher_no, how_disposed_of, amount_realized, cash_receipt_info, stud_bulls, draught_bulls, cows, heifer_calves, bull_calves, total_animals, remarks) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    
    if ($stmt) {
        $stmt->bind_param("issssdsiiiiiis", 
            $user_id, $species, $disposal_date, $voucher_no, $how_disposed_of, 
            $amount_realized, $cash_receipt_info, $stud_bulls, $draught_bulls, 
            $cows, $heifer_calves, $bull_calves, $total_animals, $remarks
        );
        
        if ($stmt->execute()) {
            $msg = urlencode("Record successfully logged for " . $species . " (Voucher No: " . $voucher_no . ")");
            header("Location: " . $redirect_page . "?status=success&msg=" . $msg);
            exit();
        } else {
            $msg = urlencode("Database insert error: " . $stmt->error);
            header("Location: " . $redirect_page . "?status=error&msg=" . $msg);
            exit();
        }
        $stmt->close();
    } else {
        $msg = urlencode("Failed to prepare database query.");
        header("Location: " . $redirect_page . "?status=error&msg=" . $msg);
        exit();
    }

} elseif ($action === 'edit') {
    $id = intval($_POST['id'] ?? 0);
    $species = trim($_POST['species'] ?? 'Cattle');
    $disposal_date = $_POST['disposal_date'] ?? date('Y-m-d');
    $voucher_no = trim($_POST['voucher_no'] ?? '');
    $how_disposed_of = trim($_POST['how_disposed_of'] ?? 'Sold');
    if ($how_disposed_of === 'Other' && !empty($_POST['how_disposed_other'])) {
        $how_disposed_of = trim($_POST['how_disposed_other']);
    }
    
    $amount_realized = floatval($_POST['amount_realized'] ?? 0);
    $cash_receipt_info = trim($_POST['cash_receipt_info'] ?? '');
    
    $stud_bulls = max(0, intval($_POST['stud_bulls'] ?? 0));
    $draught_bulls = max(0, intval($_POST['draught_bulls'] ?? 0));
    $cows = max(0, intval($_POST['cows'] ?? 0));
    $heifer_calves = max(0, intval($_POST['heifer_calves'] ?? 0));
    $bull_calves = max(0, intval($_POST['bull_calves'] ?? 0));
    $remarks = trim($_POST['remarks'] ?? '');

    $total_animals = $stud_bulls + $draught_bulls + $cows + $heifer_calves + $bull_calves;

    $stmt = $mysqli->prepare("UPDATE animal_disposal_register SET 
        disposal_date = ?, voucher_no = ?, how_disposed_of = ?, amount_realized = ?, 
        cash_receipt_info = ?, stud_bulls = ?, draught_bulls = ?, cows = ?, 
        heifer_calves = ?, bull_calves = ?, total_animals = ?, remarks = ? 
        WHERE id = ? AND user_id = ?");
    
    if ($stmt) {
        $stmt->bind_param("sssdsiiiiissii", 
            $disposal_date, $voucher_no, $how_disposed_of, $amount_realized, 
            $cash_receipt_info, $stud_bulls, $draught_bulls, $cows, 
            $heifer_calves, $bull_calves, $total_animals, $remarks, $id, $user_id
        );
        
        if ($stmt->execute()) {
            $msg = urlencode("Record successfully updated.");
            header("Location: " . $redirect_page . "?status=success&msg=" . $msg);
            exit();
        } else {
            $msg = urlencode("Database update error: " . $stmt->error);
            header("Location: " . $redirect_page . "?status=error&msg=" . $msg);
            exit();
        }
        $stmt->close();
    } else {
        $msg = urlencode("Failed to prepare update query.");
        header("Location: " . $redirect_page . "?status=error&msg=" . $msg);
        exit();
    }

} elseif ($action === 'delete') {
    $id = intval($_GET['id'] ?? 0);
    
    if ($id > 0) {
        $stmt = $mysqli->prepare("DELETE FROM animal_disposal_register WHERE id = ? AND user_id = ?");
        if ($stmt) {
            $stmt->bind_param("ii", $id, $user_id);
            if ($stmt->execute()) {
                $msg = urlencode("Record deleted successfully.");
                header("Location: " . $redirect_page . "?status=success&msg=" . $msg);
                exit();
            } else {
                $msg = urlencode("Error deleting record.");
                header("Location: " . $redirect_page . "?status=error&msg=" . $msg);
                exit();
            }
            $stmt->close();
        }
    }
    header("Location: " . $redirect_page . "?status=error&msg=" . urlencode("Invalid record ID."));
    exit();

} else {
    header("Location: " . $redirect_page);
    exit();
}
