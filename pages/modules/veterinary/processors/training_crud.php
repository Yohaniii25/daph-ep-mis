<?php
session_start();
header('Content-Type: application/json; charset=UTF-8');

require_once __DIR__ . '/../../../../config/db_connect.php';

/** @var mysqli $mysqli */
global $mysqli;

$allowed_roles = [
    'veterinary_surgeon',
    'government_veterinary_surgeon',
    'additional_veterinary_surgeon',
    'deputy_director_hq_1',
    'deputy_director_hq_2',
    'district_dd',
    'deputy_director_district',
    'provincial_director',
    'admin',
    'super_admin',
    'administrator',
    'sms',
    'planning_officer',
    'employee',
    'livestock_development_officer'
];

if (!isset($_SESSION['logged_in']) || !in_array($_SESSION['role'] ?? '', $allowed_roles, true)) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access.']);
    exit();
}

$action = $_REQUEST['action'] ?? '';

function sendJson($data) {
    echo json_encode($data);
    exit();
}

$upload_base_dir = __DIR__ . '/../../../../assets/uploads/trainings/';
if (!is_dir($upload_base_dir)) {
    mkdir($upload_base_dir, 0777, true);
}

// -------------------------------------------------------------------------
// 1. ACTION: SAVE TRAINING RECORD (CREATE OR UPDATE)
// -------------------------------------------------------------------------
if ($action === 'save_training') {
    $id             = intval($_POST['training_id'] ?? 0);
    $range_id       = !empty($_POST['range_id']) && is_numeric($_POST['range_id']) ? intval($_POST['range_id']) : intval($_SESSION['range_id'] ?? 1);
    $module_context = trim($_POST['module_context'] ?? 'projects');
    if (!in_array($module_context, ['projects', 'dairy_hub'])) {
        $module_context = 'projects';
    }

    $training_date  = trim($_POST['training_date'] ?? '');
    $topic          = trim($_POST['topic'] ?? '');
    $target_group   = trim($_POST['target_group'] ?? '');
    $location       = trim($_POST['location'] ?? '');
    $attendance_raw = trim($_POST['attendance'] ?? '');
    $trainer_name   = trim($_POST['trainer_name'] ?? '');
    $remarks        = trim($_POST['remarks'] ?? '');
    $user_id        = intval($_SESSION['user_id'] ?? 0);

    // Validate Mandatory Fields
    if (empty($training_date)) {
        sendJson(['success' => false, 'message' => 'Please provide the Training Date.']);
    }
    if (empty($topic)) {
        sendJson(['success' => false, 'message' => 'Please provide the Training Topic.']);
    }
    if (empty($target_group)) {
        sendJson(['success' => false, 'message' => 'Please specify the Target Group.']);
    }
    if (empty($location)) {
        sendJson(['success' => false, 'message' => 'Please provide the Training Location.']);
    }
    if ($attendance_raw === '' || !is_numeric($attendance_raw) || intval($attendance_raw) < 0) {
        sendJson(['success' => false, 'message' => 'Please enter a valid numeric Attendance count.']);
    }
    $attendance = intval($attendance_raw);

    // Existing photo path if updating
    $existing_photo = null;
    if ($id > 0) {
        $st_old = $mysqli->prepare("SELECT photo_path FROM operational_trainings WHERE id = ?");
        $st_old->bind_param("i", $id);
        $st_old->execute();
        $old_row = $st_old->get_result()->fetch_assoc();
        $existing_photo = $old_row['photo_path'] ?? null;
        $st_old->close();
    }

    $photo_path = $existing_photo;

    // Handle Photo Upload
    if (isset($_FILES['training_photo']) && $_FILES['training_photo']['error'] !== UPLOAD_ERR_NO_FILE) {
        $file_err = $_FILES['training_photo']['error'];
        if ($file_err !== UPLOAD_ERR_OK) {
            sendJson(['success' => false, 'message' => 'Photo upload failed with error code: ' . $file_err]);
        }

        $tmp_name = $_FILES['training_photo']['tmp_name'];
        $orig_name = $_FILES['training_photo']['name'];
        $file_size = $_FILES['training_photo']['size'];

        // Max 10MB
        if ($file_size > 10 * 1024 * 1024) {
            sendJson(['success' => false, 'message' => 'Uploaded photo exceeds maximum limit of 10MB.']);
        }

        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = finfo_file($finfo, $tmp_name);
        finfo_close($finfo);

        $allowed_mimes = [
            'image/jpeg' => 'jpg',
            'image/png'  => 'png',
            'image/webp' => 'webp',
            'image/gif'  => 'gif'
        ];

        if (!isset($allowed_mimes[$mime])) {
            sendJson(['success' => false, 'message' => 'Invalid image format. Allowed formats: JPG, PNG, WEBP, GIF.']);
        }

        $ext = $allowed_mimes[$mime];
        $new_filename = 'training_' . $module_context . '_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
        $target_destination = $upload_base_dir . $new_filename;

        $moved = is_uploaded_file($tmp_name) 
            ? move_uploaded_file($tmp_name, $target_destination) 
            : copy($tmp_name, $target_destination);

        if (!$moved) {
            sendJson(['success' => false, 'message' => 'Failed to save the uploaded image file.']);
        }

        // Delete previous photo if it exists and a new one is uploaded
        if (!empty($existing_photo)) {
            $prev_full_path = __DIR__ . '/../../../../' . ltrim($existing_photo, '/');
            if (file_exists($prev_full_path) && is_file($prev_full_path)) {
                @unlink($prev_full_path);
            }
        }

        $photo_path = 'assets/uploads/trainings/' . $new_filename;
    }

    // Insert or Update
    if ($id > 0) {
        $stmt = $mysqli->prepare("
            UPDATE `operational_trainings`
            SET `training_date` = ?,
                `topic` = ?,
                `target_group` = ?,
                `location` = ?,
                `attendance` = ?,
                `photo_path` = ?,
                `trainer_name` = ?,
                `remarks` = ?
            WHERE `id` = ? AND `range_id` = ?
        ");
        $stmt->bind_param(
            "ssssisssii",
            $training_date,
            $topic,
            $target_group,
            $location,
            $attendance,
            $photo_path,
            $trainer_name,
            $remarks,
            $id,
            $range_id
        );

        if ($stmt->execute()) {
            $stmt->close();
            sendJson(['success' => true, 'message' => 'Training record updated successfully!']);
        } else {
            $err = $stmt->error;
            $stmt->close();
            sendJson(['success' => false, 'message' => 'Database error: ' . $err]);
        }
    } else {
        $stmt = $mysqli->prepare("
            INSERT INTO `operational_trainings`
            (`range_id`, `module_context`, `training_date`, `topic`, `target_group`, `location`, `attendance`, `photo_path`, `trainer_name`, `remarks`, `created_by`)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->bind_param(
            "isssssisssi",
            $range_id,
            $module_context,
            $training_date,
            $topic,
            $target_group,
            $location,
            $attendance,
            $photo_path,
            $trainer_name,
            $remarks,
            $user_id
        );

        if ($stmt->execute()) {
            $new_id = $stmt->insert_id;
            $stmt->close();
            sendJson(['success' => true, 'message' => 'Training session logged successfully!', 'id' => $new_id]);
        } else {
            $err = $stmt->error;
            $stmt->close();
            sendJson(['success' => false, 'message' => 'Database error: ' . $err]);
        }
    }
}

// -------------------------------------------------------------------------
// 2. ACTION: GET TRAINING RECORD BY ID
// -------------------------------------------------------------------------
if ($action === 'get_training') {
    $id = intval($_GET['id'] ?? 0);
    if ($id <= 0) {
        sendJson(['success' => false, 'message' => 'Invalid training record ID.']);
    }

    $stmt = $mysqli->prepare("SELECT * FROM operational_trainings WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($row) {
        sendJson(['success' => true, 'data' => $row]);
    } else {
        sendJson(['success' => false, 'message' => 'Training session record not found.']);
    }
}

// -------------------------------------------------------------------------
// 3. ACTION: DELETE TRAINING RECORD
// -------------------------------------------------------------------------
if ($action === 'delete_training') {
    $id = intval($_POST['id'] ?? 0);
    if ($id <= 0) {
        sendJson(['success' => false, 'message' => 'Invalid training record ID.']);
    }

    $stmt_sel = $mysqli->prepare("SELECT photo_path FROM operational_trainings WHERE id = ?");
    $stmt_sel->bind_param("i", $id);
    $stmt_sel->execute();
    $row = $stmt_sel->get_result()->fetch_assoc();
    $stmt_sel->close();

    if (!empty($row['photo_path'])) {
        $full_p = __DIR__ . '/../../../../' . ltrim($row['photo_path'], '/');
        if (file_exists($full_p) && is_file($full_p)) {
            @unlink($full_p);
        }
    }

    $stmt = $mysqli->prepare("DELETE FROM operational_trainings WHERE id = ?");
    $stmt->bind_param("i", $id);
    if ($stmt->execute()) {
        $stmt->close();
        sendJson(['success' => true, 'message' => 'Training session record deleted successfully.']);
    } else {
        $err = $stmt->error;
        $stmt->close();
        sendJson(['success' => false, 'message' => 'Failed to delete record: ' . $err]);
    }
}

sendJson(['success' => false, 'message' => 'Unknown action.']);
