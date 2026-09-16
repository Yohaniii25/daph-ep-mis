<?php
/**
 * pages/modules/veterinary/processors/update_vehicle_repair.php
 * Updates vehicle repair record with receipt management and approval recalculation
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../../../config/db_connect.php';

if (!headers_sent()) {
    header('Content-Type: application/json');
}

$allowed_roles = ['veterinary_surgeon', 'government_veterinary_surgeon', 'additional_veterinary_surgeon', 'district_dd', 'provincial_director', 'administrator'];
if (!isset($_SESSION['logged_in']) || !in_array($_SESSION['role'], $allowed_roles)) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access.']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
    $vehicle_id = filter_input(INPUT_POST, 'vehicle_id', FILTER_VALIDATE_INT);
    $repair_date = trim(filter_input(INPUT_POST, 'repair_date', FILTER_SANITIZE_SPECIAL_CHARS));
    $repair_done = trim(filter_input(INPUT_POST, 'repair_done', FILTER_SANITIZE_SPECIAL_CHARS));
    $repair_description = trim(filter_input(INPUT_POST, 'repair_description', FILTER_SANITIZE_SPECIAL_CHARS));
    $place_of_repair = trim(filter_input(INPUT_POST, 'place_of_repair', FILTER_SANITIZE_SPECIAL_CHARS));
    $invoice_ref = trim(filter_input(INPUT_POST, 'invoice_ref', FILTER_SANITIZE_SPECIAL_CHARS));

    $amount_raw = $_POST['transaction_amount'] ?? $_POST['amount'] ?? null;
    $amount = filter_var($amount_raw, FILTER_VALIDATE_FLOAT);
    $transaction_amount = $amount;

    if (!$id || !$vehicle_id || empty($repair_done) || empty($repair_date) || $amount === false || $amount < 0) {
        echo json_encode(['success' => false, 'message' => 'Validation error: Please provide valid repair details.']);
        exit();
    }

    // Fetch existing record
    $curr_stmt = $mysqli->prepare("SELECT * FROM vehicle_repairs WHERE id = ? AND is_active = 1");
    if (!$curr_stmt) {
        echo json_encode(['success' => false, 'message' => 'Database error.']);
        exit();
    }
    $curr_stmt->bind_param("i", $id);
    $curr_stmt->execute();
    $existing = $curr_stmt->get_result()->fetch_assoc();
    $curr_stmt->close();

    if (!$existing) {
        echo json_encode(['success' => false, 'message' => 'Repair record not found.']);
        exit();
    }

    // Handle Receipt Upload if provided
    $receipt_file_path = $existing['receipt_file'];
    if (isset($_FILES['receipt_file']) && $_FILES['receipt_file']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['receipt_file'];
        $allowed_exts = ['pdf', 'jpg', 'jpeg', 'png'];
        $file_ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

        if (!in_array($file_ext, $allowed_exts)) {
            echo json_encode(['success' => false, 'message' => 'Invalid file format. Only PDF, JPG, PNG accepted.']);
            exit();
        }

        if ($file['size'] > 5 * 1024 * 1024) {
            echo json_encode(['success' => false, 'message' => 'Receipt file exceeds 5MB.']);
            exit();
        }

        $upload_dir = __DIR__ . '/../../../../assets/uploads/receipts/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }

        $new_filename = 'receipt_' . $vehicle_id . '_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $file_ext;
        $target_destination = $upload_dir . $new_filename;

        if (move_uploaded_file($file['tmp_name'], $target_destination)) {
            $receipt_file_path = 'assets/uploads/receipts/' . $new_filename;
        }
    }

    // Re-evaluate approval routing if not already approved
    $approval_status = $existing['approval_status'];
    $approval_authority = $existing['approval_authority'];
    $user_role = $_SESSION['role'] ?? '';

    if ($approval_status !== 'Approved' || ($user_role !== 'provincial_director' && $user_role !== 'administrator')) {
        if ($amount <= 50000.00) {
            $approval_status = 'Pending District Approval';
            $approval_authority = 'District Deputy Director';
        } else {
            $approval_status = 'Pending Provincial Approval';
            $approval_authority = 'Provincial Director';
        }
    }

    $stmt = $mysqli->prepare("
        UPDATE vehicle_repairs 
        SET vehicle_id = ?, repair_date = ?, repair_done = ?, repair_description = ?, 
            place_of_repair = ?, invoice_ref = ?, amount = ?, transaction_amount = ?, 
            approval_status = ?, approval_authority = ?, receipt_file = ?
        WHERE id = ?
    ");

    if ($stmt) {
        $stmt->bind_param(
            "isssssddsssi", 
            $vehicle_id, $repair_date, $repair_done, $repair_description, 
            $place_of_repair, $invoice_ref, $amount, $transaction_amount, 
            $approval_status, $approval_authority, $receipt_file_path, $id
        );

        if ($stmt->execute()) {
            echo json_encode([
                'success' => true, 
                'message' => 'Vehicle repair record updated successfully.',
                'approval_status' => $approval_status,
                'approval_authority' => $approval_authority
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Database update failed: ' . $stmt->error]);
        }
        $stmt->close();
    } else {
        echo json_encode(['success' => false, 'message' => 'Statement preparation failed: ' . $mysqli->error]);
    }
}
