<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../../../../config/db_connect.php';

$allowed_roles = [
    'veterinary_surgeon',
    'district_dd',
    'deputy_director_district',
    'administrator',
    'provincial_director',
    'admin'
];

$is_ajax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';

function sendResponse($success, $message, $redirect_url = null, $data = []) {
    global $is_ajax;
    if ($is_ajax) {
        header('Content-Type: application/json');
        echo json_encode(array_merge(['success' => $success, 'message' => $message], $data));
        exit();
    }
    $_SESSION['msg'] = $message;
    $_SESSION['msg_type'] = $success ? 'success' : 'danger';
    header("Location: " . ($redirect_url ?: '../vaccination_targets.php'));
    exit();
}

if (!isset($_SESSION['logged_in']) || !in_array($_SESSION['role'] ?? '', $allowed_roles, true)) {
    sendResponse(false, 'Unauthorized access.');
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendResponse(false, 'Invalid request method.');
}

$action = trim($_POST['action'] ?? 'add');
$user_id = $_SESSION['user_id'] ?? null;
$session_range_id = intval($_SESSION['range_id'] ?? 0);
$post_range_id = intval($_POST['range_id'] ?? 0);
$range_id = ($post_range_id > 0) ? $post_range_id : $session_range_id;

if ($range_id <= 0) {
    sendResponse(false, 'Veterinary Range ID is missing or invalid.');
}

// -------------------------------------------------------------
// DELETE ACTION
// -------------------------------------------------------------
if ($action === 'delete') {
    $session_id = intval($_POST['id'] ?? 0);
    $year = intval($_POST['year'] ?? date('Y'));

    if ($session_id <= 0) {
        sendResponse(false, 'Invalid session record ID.', "../vaccination_targets.php?year={$year}");
    }

    $stmt = $mysqli->prepare("DELETE FROM vaccination_session_logs WHERE id = ? AND range_id = ?");
    if ($stmt) {
        $stmt->bind_param("ii", $session_id, $range_id);
        if ($stmt->execute()) {
            $stmt->close();
            sendResponse(true, 'Vaccination session log deleted successfully.', "../vaccination_targets.php?year={$year}");
        } else {
            $err = $stmt->error;
            $stmt->close();
            sendResponse(false, 'Error deleting session: ' . $err, "../vaccination_targets.php?year={$year}");
        }
    } else {
        sendResponse(false, 'Database prepare error: ' . $mysqli->error, "../vaccination_targets.php?year={$year}");
    }
}

// -------------------------------------------------------------
// ADD / EDIT ACTION
// -------------------------------------------------------------
if ($action === 'add' || $action === 'edit') {
    $session_date = trim($_POST['session_date'] ?? '');
    if (empty($session_date) || !strtotime($session_date)) {
        sendResponse(false, 'A valid session date is required.');
    }

    $timestamp = strtotime($session_date);
    $report_year = intval(date('Y', $timestamp));
    $report_month = intval(date('n', $timestamp));

    $category = trim($_POST['category'] ?? 'Livestock');
    if (!in_array($category, ['Livestock', 'Poultry'], true)) {
        $category = 'Livestock';
    }

    $animal_type = trim($_POST['animal_type'] ?? '');
    if (empty($animal_type)) {
        sendResponse(false, 'Animal species / category is required.');
    }

    $vaccine_name = trim($_POST['vaccine_name'] ?? '');
    if (empty($vaccine_name) || $vaccine_name === 'Other') {
        $custom_vaccine = trim($_POST['custom_vaccine_name'] ?? '');
        if (!empty($custom_vaccine)) {
            $vaccine_name = $custom_vaccine;
        }
    }
    if (empty($vaccine_name)) {
        sendResponse(false, 'Vaccine name is required.');
    }

    $vaccinator_id = !empty($_POST['vaccinator_id']) ? intval($_POST['vaccinator_id']) : null;
    $vaccinator_name = trim($_POST['vaccinator_name'] ?? '');

    // If vaccinator_id is selected, resolve their name from casual_vaccinator_deployments
    if ($vaccinator_id && $vaccinator_id > 0) {
        $vstmt = $mysqli->prepare("SELECT full_name, nic_no FROM casual_vaccinator_deployments WHERE id = ?");
        if ($vstmt) {
            $vstmt->bind_param("i", $vaccinator_id);
            $vstmt->execute();
            $vres = $vstmt->get_result()->fetch_assoc();
            if ($vres) {
                $vaccinator_name = $vres['full_name'] . ' (NIC: ' . $vres['nic_no'] . ')';
            }
            $vstmt->close();
        }
    }

    if (empty($vaccinator_name)) {
        $vaccinator_name = 'Unspecified Officer / Vaccinator';
    }

    $vaccinated_count = max(0, intval($_POST['vaccinated_count'] ?? 0));
    $doses_administered = max(0, intval($_POST['doses_administered'] ?? $vaccinated_count));
    $batch_no = trim($_POST['batch_no'] ?? null);
    $location_name = trim($_POST['location_name'] ?? null);
    $remarks = trim($_POST['remarks'] ?? null);

    if ($action === 'add') {
        $sql = "INSERT INTO vaccination_session_logs 
                (range_id, session_date, report_year, report_month, category, animal_type, vaccine_name, vaccinator_id, vaccinator_name, vaccinated_count, doses_administered, batch_no, location_name, remarks, created_by) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $mysqli->prepare($sql);
        if ($stmt) {
            $stmt->bind_param(
                "isiisssisissssi",
                $range_id,
                $session_date,
                $report_year,
                $report_month,
                $category,
                $animal_type,
                $vaccine_name,
                $vaccinator_id,
                $vaccinator_name,
                $vaccinated_count,
                $doses_administered,
                $batch_no,
                $location_name,
                $remarks,
                $user_id
            );
            if ($stmt->execute()) {
                $new_id = $stmt->insert_id;
                $stmt->close();
                sendResponse(true, "Vaccination session for {$animal_type} logged successfully ({$vaccinated_count} animals vaccinated).", "../vaccination_targets.php?year={$report_year}", ['id' => $new_id]);
            } else {
                $err = $stmt->error;
                $stmt->close();
                sendResponse(false, 'Error recording session: ' . $err, "../vaccination_targets.php?year={$report_year}");
            }
        } else {
            sendResponse(false, 'Database prepare error: ' . $mysqli->error, "../vaccination_targets.php?year={$report_year}");
        }
    } else { // EDIT
        $session_id = intval($_POST['id'] ?? 0);
        if ($session_id <= 0) {
            sendResponse(false, 'Invalid session record ID for edit.');
        }

        $sql = "UPDATE vaccination_session_logs 
                SET session_date = ?, report_year = ?, report_month = ?, category = ?, animal_type = ?, vaccine_name = ?, vaccinator_id = ?, vaccinator_name = ?, vaccinated_count = ?, doses_administered = ?, batch_no = ?, location_name = ?, remarks = ? 
                WHERE id = ? AND range_id = ?";
        $stmt = $mysqli->prepare($sql);
        if ($stmt) {
            $stmt->bind_param(
                "siisssisissssii",
                $session_date,
                $report_year,
                $report_month,
                $category,
                $animal_type,
                $vaccine_name,
                $vaccinator_id,
                $vaccinator_name,
                $vaccinated_count,
                $doses_administered,
                $batch_no,
                $location_name,
                $remarks,
                $session_id,
                $range_id
            );
            if ($stmt->execute()) {
                $stmt->close();
                sendResponse(true, "Vaccination session record updated successfully.", "../vaccination_targets.php?year={$report_year}");
            } else {
                $err = $stmt->error;
                $stmt->close();
                sendResponse(false, 'Error updating session: ' . $err, "../vaccination_targets.php?year={$report_year}");
            }
        } else {
            sendResponse(false, 'Database prepare error: ' . $mysqli->error, "../vaccination_targets.php?year={$report_year}");
        }
    }
}

sendResponse(false, 'Unhandled action.');
