<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../../../../config/db_connect.php';

/** @var mysqli $mysqli */
global $mysqli;

// 1. Authentication & Role Validation
$allowed_roles = [
    'veterinary_surgeon',
    'government_veterinary_surgeon',
    'additional_veterinary_surgeon',
    'deputy_director_hq_1',
    'district_dd',
    'deputy_director_district',
    'provincial_director',
    'admin',
    'super_admin'
];

if (!isset($_SESSION['logged_in']) || !in_array($_SESSION['role'] ?? '', $allowed_roles, true)) {
    die(json_encode(['success' => false, 'message' => 'Unauthorized access denied.']));
}

$action = $_POST['action'] ?? $_GET['action'] ?? '';
$upload_base_dir = __DIR__ . '/../../../../assets/uploads/beneficiaries/';
if (!is_dir($upload_base_dir)) {
    mkdir($upload_base_dir, 0777, true);
}

// Helper function to securely handle photo uploads
function handle_photo_upload($file_input_name, $upload_dir) {
    if (!isset($_FILES[$file_input_name]) || $_FILES[$file_input_name]['error'] !== UPLOAD_ERR_OK) {
        return null;
    }
    
    $file = $_FILES[$file_input_name];
    $allowed_extensions = ['jpg', 'jpeg', 'png', 'webp'];
    $file_info = pathinfo($file['name']);
    $extension = strtolower($file_info['extension'] ?? '');
    
    if (!in_array($extension, $allowed_extensions, true)) {
        return false;
    }
    
    // Check mime type
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime_type = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);
    
    $allowed_mimes = ['image/jpeg', 'image/png', 'image/webp'];
    if (!in_array($mime_type, $allowed_mimes, true)) {
        return false;
    }
    
    $new_filename = 'beneficiary_' . time() . '_' . bin2hex(random_bytes(6)) . '.' . $extension;
    $destination = $upload_dir . $new_filename;
    
    if (move_uploaded_file($file['tmp_name'], $destination)) {
        return 'assets/uploads/beneficiaries/' . $new_filename;
    }
    
    return false;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $activity_id                   = intval($_POST['activity_id'] ?? 0);
    $name                          = trim($_POST['name'] ?? '');
    $farm_reg_no                   = trim($_POST['farm_reg_no'] ?? '');
    $nic                           = trim($_POST['nic'] ?? '');
    $phone                         = trim($_POST['phone'] ?? '');
    $address                       = trim($_POST['address'] ?? '');
    $gps_location                  = trim($_POST['gps_location'] ?? '');
    $total_cost                    = floatval($_POST['total_cost'] ?? 0);
    $dept_contribution_pct         = floatval($_POST['dept_contribution_pct'] ?? 0);
    $dept_contribution_amount      = floatval($_POST['dept_contribution_amount'] ?? 0);
    $beneficiary_contribution_pct  = floatval($_POST['beneficiary_contribution_pct'] ?? 0);
    $beneficiary_contribution_amount = floatval($_POST['beneficiary_contribution_amount'] ?? 0);
    $work_done_percentage          = max(0, min(100, intval($_POST['work_done_percentage'] ?? 0)));
    $id                            = intval($_POST['id'] ?? 0);

    // Validation
    if ($activity_id <= 0) {
        $_SESSION['msg'] = "Validation Error: Missing valid activity assignment.";
        $_SESSION['msg_type'] = "danger";
        header("Location: ../production_activities.php");
        exit();
    }

    if (empty($name) || empty($farm_reg_no) || empty($nic) || empty($phone) || empty($address)) {
        $_SESSION['msg'] = "Validation Error: Name, Farm Reg No, NIC, Phone, and Address are required.";
        $_SESSION['msg_type'] = "danger";
        header("Location: ../activity_beneficiaries.php?activity_id=" . $activity_id);
        exit();
    }

    // Auto-calculate exact amounts if percentages provided, or vice-versa
    if ($total_cost > 0) {
        if ($dept_contribution_amount <= 0 && $dept_contribution_pct > 0) {
            $dept_contribution_amount = round(($dept_contribution_pct / 100) * $total_cost, 2);
        }
        if ($beneficiary_contribution_amount <= 0 && $beneficiary_contribution_pct > 0) {
            $beneficiary_contribution_amount = round(($beneficiary_contribution_pct / 100) * $total_cost, 2);
        }
        if ($dept_contribution_pct <= 0 && $dept_contribution_amount > 0) {
            $dept_contribution_pct = round(($dept_contribution_amount / $total_cost) * 100, 2);
        }
        if ($beneficiary_contribution_pct <= 0 && $beneficiary_contribution_amount > 0) {
            $beneficiary_contribution_pct = round(($beneficiary_contribution_amount / $total_cost) * 100, 2);
        }
    }

    if ($action === 'create') {
        $photo_1 = handle_photo_upload('photo_1', $upload_base_dir);
        $photo_2 = handle_photo_upload('photo_2', $upload_base_dir);

        if ($photo_1 === false || $photo_2 === false) {
            $_SESSION['msg'] = "Upload Error: Verification photos must be valid images (JPG, PNG, WEBP).";
            $_SESSION['msg_type'] = "warning";
        }

        $photo_1_path = ($photo_1 !== false && $photo_1 !== null) ? $photo_1 : null;
        $photo_2_path = ($photo_2 !== false && $photo_2 !== null) ? $photo_2 : null;

        $stmt = $mysqli->prepare("
            INSERT INTO activity_beneficiaries 
            (activity_id, name, farm_reg_no, nic, phone, address, gps_location, total_cost, dept_contribution_pct, dept_contribution_amount, beneficiary_contribution_pct, beneficiary_contribution_amount, work_done_percentage, photo_1, photo_2)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");

        if ($stmt) {
            $stmt->bind_param(
                "issssssdddddiss",
                $activity_id,
                $name,
                $farm_reg_no,
                $nic,
                $phone,
                $address,
                $gps_location,
                $total_cost,
                $dept_contribution_pct,
                $dept_contribution_amount,
                $beneficiary_contribution_pct,
                $beneficiary_contribution_amount,
                $work_done_percentage,
                $photo_1_path,
                $photo_2_path
            );

            if ($stmt->execute()) {
                $_SESSION['msg'] = "Beneficiary enrolled successfully with milestone progress.";
                $_SESSION['msg_type'] = "success";
            } else {
                $_SESSION['msg'] = "Database Error: " . htmlspecialchars($stmt->error);
                $_SESSION['msg_type'] = "danger";
            }
            $stmt->close();
        } else {
            $_SESSION['msg'] = "Database Statement Error: " . htmlspecialchars($mysqli->error);
            $_SESSION['msg_type'] = "danger";
        }

        header("Location: ../activity_beneficiaries.php?activity_id=" . $activity_id);
        exit();

    } elseif ($action === 'update' && $id > 0) {

        // Fetch existing photos first
        $cur_stmt = $mysqli->prepare("SELECT photo_1, photo_2 FROM activity_beneficiaries WHERE id = ? AND activity_id = ?");
        $cur_stmt->bind_param("ii", $id, $activity_id);
        $cur_stmt->execute();
        $cur_res = $cur_stmt->get_result()->fetch_assoc();
        $cur_stmt->close();

        $photo_1_path = $cur_res['photo_1'] ?? null;
        $photo_2_path = $cur_res['photo_2'] ?? null;

        $new_p1 = handle_photo_upload('photo_1', $upload_base_dir);
        if ($new_p1 !== null && $new_p1 !== false) {
            // Delete old file if exists
            if (!empty($photo_1_path) && file_exists(__DIR__ . '/../../../../' . $photo_1_path)) {
                @unlink(__DIR__ . '/../../../../' . $photo_1_path);
            }
            $photo_1_path = $new_p1;
        }

        $new_p2 = handle_photo_upload('photo_2', $upload_base_dir);
        if ($new_p2 !== null && $new_p2 !== false) {
            if (!empty($photo_2_path) && file_exists(__DIR__ . '/../../../../' . $photo_2_path)) {
                @unlink(__DIR__ . '/../../../../' . $photo_2_path);
            }
            $photo_2_path = $new_p2;
        }

        $stmt = $mysqli->prepare("
            UPDATE activity_beneficiaries SET 
                name = ?, 
                farm_reg_no = ?, 
                nic = ?, 
                phone = ?, 
                address = ?, 
                gps_location = ?, 
                total_cost = ?, 
                dept_contribution_pct = ?, 
                dept_contribution_amount = ?, 
                beneficiary_contribution_pct = ?, 
                beneficiary_contribution_amount = ?, 
                work_done_percentage = ?, 
                photo_1 = ?, 
                photo_2 = ?
            WHERE id = ? AND activity_id = ?
        ");

        if ($stmt) {
            $stmt->bind_param(
                "ssssssdddddissii",
                $name,
                $farm_reg_no,
                $nic,
                $phone,
                $address,
                $gps_location,
                $total_cost,
                $dept_contribution_pct,
                $dept_contribution_amount,
                $beneficiary_contribution_pct,
                $beneficiary_contribution_amount,
                $work_done_percentage,
                $photo_1_path,
                $photo_2_path,
                $id,
                $activity_id
            );

            if ($stmt->execute()) {
                $_SESSION['msg'] = "Beneficiary record and milestone tracking updated successfully.";
                $_SESSION['msg_type'] = "success";
            } else {
                $_SESSION['msg'] = "Database Update Error: " . htmlspecialchars($stmt->error);
                $_SESSION['msg_type'] = "danger";
            }
            $stmt->close();
        } else {
            $_SESSION['msg'] = "Database Statement Error: " . htmlspecialchars($mysqli->error);
            $_SESSION['msg_type'] = "danger";
        }

        header("Location: ../activity_beneficiaries.php?activity_id=" . $activity_id);
        exit();
    }

} elseif ($_SERVER['REQUEST_METHOD'] === 'GET' && $action === 'delete') {

    $id          = intval($_GET['id'] ?? 0);
    $activity_id = intval($_GET['activity_id'] ?? 0);

    if ($id > 0 && $activity_id > 0) {
        // Fetch photos to remove files from disk
        $cur_stmt = $mysqli->prepare("SELECT photo_1, photo_2 FROM activity_beneficiaries WHERE id = ? AND activity_id = ?");
        $cur_stmt->bind_param("ii", $id, $activity_id);
        $cur_stmt->execute();
        if ($row = $cur_stmt->get_result()->fetch_assoc()) {
            if (!empty($row['photo_1']) && file_exists(__DIR__ . '/../../../../' . $row['photo_1'])) {
                @unlink(__DIR__ . '/../../../../' . $row['photo_1']);
            }
            if (!empty($row['photo_2']) && file_exists(__DIR__ . '/../../../../' . $row['photo_2'])) {
                @unlink(__DIR__ . '/../../../../' . $row['photo_2']);
            }
        }
        $cur_stmt->close();

        $del_stmt = $mysqli->prepare("DELETE FROM activity_beneficiaries WHERE id = ? AND activity_id = ?");
        $del_stmt->bind_param("ii", $id, $activity_id);
        if ($del_stmt->execute()) {
            $_SESSION['msg'] = "Beneficiary record deleted successfully.";
            $_SESSION['msg_type'] = "success";
        } else {
            $_SESSION['msg'] = "Failed to delete beneficiary record: " . htmlspecialchars($del_stmt->error);
            $_SESSION['msg_type'] = "danger";
        }
        $del_stmt->close();
    }

    header("Location: ../activity_beneficiaries.php?activity_id=" . $activity_id);
    exit();
}

header("Location: ../production_activities.php");
exit();
