<?php
/**
 * pages/modules/veterinary/processors/save_vehicle_repair.php
 * Persists vehicle repair records with tiered approval workflows, receipt file upload, and notifications.
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
    $user_id = $_SESSION['user_id'] ?? null;
    $user_role = $_SESSION['role'] ?? '';

    $vehicle_id         = filter_input(INPUT_POST, 'vehicle_id', FILTER_VALIDATE_INT);
    $repair_date        = trim(filter_input(INPUT_POST, 'repair_date', FILTER_SANITIZE_SPECIAL_CHARS));
    $repair_done        = trim(filter_input(INPUT_POST, 'repair_done', FILTER_SANITIZE_SPECIAL_CHARS));
    $place_of_repair    = trim(filter_input(INPUT_POST, 'place_of_repair', FILTER_SANITIZE_SPECIAL_CHARS));
    $invoice_ref        = trim(filter_input(INPUT_POST, 'invoice_ref', FILTER_SANITIZE_SPECIAL_CHARS));
    $repair_description = trim(filter_input(INPUT_POST, 'repair_description', FILTER_SANITIZE_SPECIAL_CHARS));

    // Support both amount and transaction_amount
    $amount_raw = $_POST['transaction_amount'] ?? $_POST['amount'] ?? null;
    $amount = filter_var($amount_raw, FILTER_VALIDATE_FLOAT);
    $transaction_amount = $amount;

    if (!$user_id || !$vehicle_id || empty($repair_done) || empty($repair_date) || $amount === false || $amount < 0) {
        echo json_encode(['success' => false, 'message' => 'Please provide all required fields with a valid repair amount.']);
        exit();
    }

    // Handle Receipt File Upload
    $receipt_file_path = null;
    if (isset($_FILES['receipt_file']) && $_FILES['receipt_file']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['receipt_file'];
        $allowed_exts = ['pdf', 'jpg', 'jpeg', 'png'];
        $file_ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

        if (!in_array($file_ext, $allowed_exts)) {
            echo json_encode(['success' => false, 'message' => 'Invalid receipt file type. Supported formats: PDF, JPG, PNG.']);
            exit();
        }

        if ($file['size'] > 5 * 1024 * 1024) { // 5MB limit
            echo json_encode(['success' => false, 'message' => 'Receipt file exceeds the 5MB size limit.']);
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
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to upload receipt document.']);
            exit();
        }
    }

    // Determine Vehicle details and district
    $veh_reg = "Vehicle #{$vehicle_id}";
    $district_id = $_SESSION['district_id'] ?? null;
    $v_stmt = $mysqli->prepare("SELECT registration_no, vehicle_name, district_id FROM registered_vehicles WHERE id = ?");
    if ($v_stmt) {
        $v_stmt->bind_param("i", $vehicle_id);
        $v_stmt->execute();
        $v_row = $v_stmt->get_result()->fetch_assoc();
        if ($v_row) {
            $veh_reg = !empty($v_row['registration_no']) ? $v_row['registration_no'] : ($v_row['vehicle_name'] ?? $veh_reg);
            if (!empty($v_row['district_id'])) {
                $district_id = intval($v_row['district_id']);
            }
        }
        $v_stmt->close();
    }

    // Tiered Maintenance Approval Logic
    // Limit: <= 50,000 -> District Deputy Director; > 50,000 -> Provincial Director
    $approved_by = null;
    $approved_at = null;

    if ($user_role === 'provincial_director' || $user_role === 'administrator') {
        $approval_status = 'Approved';
        $approval_authority = ($user_role === 'provincial_director') ? 'Provincial Director' : 'Administrator';
        $approved_by = $user_id;
        $approved_at = date('Y-m-d H:i:s');
    } else {
        if ($amount <= 50000.00) {
            $approval_status = 'Pending District Approval';
            $approval_authority = 'District Deputy Director';
        } else {
            $approval_status = 'Pending Provincial Approval';
            $approval_authority = 'Provincial Director';
        }
    }

    $ins = $mysqli->prepare("
        INSERT INTO vehicle_repairs (
            vehicle_id, user_id, repair_date, repair_done, repair_description, 
            place_of_repair, invoice_ref, amount, transaction_amount, 
            approval_status, approval_authority, approved_by, approved_at, receipt_file, is_active
        ) VALUES (
            ?, ?, ?, ?, ?, 
            ?, ?, ?, ?, 
            ?, ?, ?, ?, ?, 1
        )
    ");

    if ($ins) {
        $ins->bind_param(
            "iisssssddssiss",
            $vehicle_id, $user_id, $repair_date, $repair_done, $repair_description,
            $place_of_repair, $invoice_ref, $amount, $transaction_amount,
            $approval_status, $approval_authority, $approved_by, $approved_at, $receipt_file_path
        );

        if ($ins->execute()) {
            $repair_id = $ins->insert_id;
            $ins->close();

            // Send automated notifications for pending approvals
            if (strpos($approval_status, 'Pending') !== false) {
                $notif_title = ($amount <= 50000.00)
                    ? "Repair Approval Required (District): {$veh_reg}"
                    : "High-Value Repair Approval Required (Provincial): {$veh_reg}";

                $notif_msg = "New maintenance repair for {$veh_reg} ({$repair_done}) amounting to LKR " . number_format($amount, 2) . " has been submitted and is awaiting your review.";
                $link = "pages/modules/veterinary/vehicles.php?tab=repairs";

                if ($amount <= 50000.00) {
                    // Notify District DD of this district
                    $dst_users = [];
                    if (!empty($district_id)) {
                        $d_stmt = $mysqli->prepare("SELECT id FROM users WHERE role = 'district_dd' AND (district_id = ? OR district_id IS NULL) AND is_active = 1");
                        if ($d_stmt) {
                            $d_stmt->bind_param("i", $district_id);
                            $d_stmt->execute();
                            $d_res = $d_stmt->get_result();
                            while ($dr = $d_res->fetch_assoc()) {
                                $dst_users[] = intval($dr['id']);
                            }
                            $d_stmt->close();
                        }
                    }
                    if (empty($dst_users)) {
                        $d_stmt = $mysqli->query("SELECT id FROM users WHERE role = 'district_dd' AND is_active = 1");
                        if ($d_stmt) {
                            while ($dr = $d_stmt->fetch_assoc()) {
                                $dst_users[] = intval($dr['id']);
                            }
                        }
                    }
                    foreach ($dst_users as $target_uid) {
                        $n_stmt = $mysqli->prepare("INSERT INTO notifications (user_id, title, message, type, link, is_read, created_at) VALUES (?, ?, ?, 'repair_approval', ?, 0, NOW())");
                        if ($n_stmt) {
                            $n_stmt->bind_param("isss", $target_uid, $notif_title, $notif_msg, $link);
                            $n_stmt->execute();
                            $n_stmt->close();
                        }
                    }
                } else {
                    // Notify Provincial Directors
                    $pd_stmt = $mysqli->query("SELECT id FROM users WHERE role = 'provincial_director' AND is_active = 1");
                    if ($pd_stmt) {
                        while ($pdr = $pd_stmt->fetch_assoc()) {
                            $target_uid = intval($pdr['id']);
                            $n_stmt = $mysqli->prepare("INSERT INTO notifications (user_id, title, message, type, link, is_read, created_at) VALUES (?, ?, ?, 'repair_approval', ?, 0, NOW())");
                            if ($n_stmt) {
                                $n_stmt->bind_param("isss", $target_uid, $notif_title, $notif_msg, $link);
                                $n_stmt->execute();
                                $n_stmt->close();
                            }
                        }
                    }
                }
            }

            echo json_encode([
                'success' => true,
                'message' => 'Vehicle repair entry and financial transaction logged successfully.',
                'approval_status' => $approval_status,
                'approval_authority' => $approval_authority
            ]);
            exit();
        } else {
            echo json_encode(['success' => false, 'message' => 'Database error: ' . $ins->error]);
            exit();
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to prepare statement: ' . $mysqli->error]);
        exit();
    }
}