<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../../../../config/db_connect.php';

$allowed_roles = [
    'veterinary_surgeon',
    'district_dd',
    'deputy_director_district',
    'sms',
    'administrator',
    'provincial_director',
    'admin',
    'super_admin'
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

require_once __DIR__ . '/vaccine_balance_helper.php';

if (!isset($_SESSION['logged_in']) || !in_array($_SESSION['role'] ?? '', $allowed_roles, true)) {
    sendResponse(false, 'Unauthorized access.');
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendResponse(false, 'Invalid request method.');
}

$action = trim($_POST['action'] ?? 'add');
$user_id = $_SESSION['user_id'] ?? 1;
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
        sendResponse(false, 'Invalid program record ID.', "../vaccination_targets.php?year={$year}");
    }

    // Lookup old session to reverse balance deduction
    $old_stmt = $mysqli->prepare("SELECT range_id, report_year, report_month, vaccine_name, doses_administered FROM vaccination_session_logs WHERE id = ?");
    if ($old_stmt) {
        $old_stmt->bind_param("i", $session_id);
        $old_stmt->execute();
        $old_row = $old_stmt->get_result()->fetch_assoc();
        $old_stmt->close();

        if ($old_row) {
            // Reverse deduction: pass negative doses
            deductVaccineBalance(
                $mysqli, 
                intval($old_row['range_id']), 
                intval($old_row['report_year']), 
                intval($old_row['report_month']), 
                $old_row['vaccine_name'], 
                -intval($old_row['doses_administered']), 
                null, 
                null, 
                $user_id
            );
        }
    }

    $stmt = $mysqli->prepare("DELETE FROM vaccination_session_logs WHERE id = ? AND range_id = ?");
    if ($stmt) {
        $stmt->bind_param("ii", $session_id, $range_id);
        if ($stmt->execute()) {
            $stmt->close();
            sendResponse(true, 'Vaccination program log deleted and vaccine balance restored successfully.', "../vaccination_targets.php?year={$year}");
        } else {
            $err = $stmt->error;
            $stmt->close();
            sendResponse(false, 'Error deleting program record: ' . $err, "../vaccination_targets.php?year={$year}");
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
        sendResponse(false, 'A valid Vaccination Program Date is required.');
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

    // Process Multi-Vaccinators
    $vaccinator_ids = $_POST['vaccinator_ids'] ?? [];
    if (!is_array($vaccinator_ids) && !empty($vaccinator_ids)) {
        $vaccinator_ids = explode(',', $vaccinator_ids);
    }
    $clean_vaccinator_ids = array_filter(array_map('intval', (array)$vaccinator_ids));
    $assigned_vaccinator_ids_str = !empty($clean_vaccinator_ids) ? implode(',', $clean_vaccinator_ids) : null;
    $primary_vaccinator_id = !empty($clean_vaccinator_ids) ? $clean_vaccinator_ids[0] : (!empty($_POST['vaccinator_id']) ? intval($_POST['vaccinator_id']) : null);

    $manual_vaccinator_name = trim($_POST['vaccinator_name'] ?? '');
    $resolved_names = [];

    if (!empty($clean_vaccinator_ids)) {
        $in_clause = implode(',', $clean_vaccinator_ids);
        $v_res = $mysqli->query("SELECT id, full_name, nic_no FROM casual_vaccinator_deployments WHERE id IN ({$in_clause})");
        if ($v_res) {
            while ($vr = $v_res->fetch_assoc()) {
                $resolved_names[] = $vr['full_name'];
            }
        }
    }

    if (!empty($resolved_names)) {
        $vaccinator_name = implode(', ', $resolved_names);
        if (!empty($manual_vaccinator_name) && !in_array($manual_vaccinator_name, $resolved_names, true)) {
            // Append manual additions if distinct
            $vaccinator_name .= ', ' . $manual_vaccinator_name;
        }
    } else {
        $vaccinator_name = !empty($manual_vaccinator_name) ? $manual_vaccinator_name : 'Unspecified Officer / Vaccinator';
    }

    $vaccinated_count = max(0, intval($_POST['vaccinated_count'] ?? 0));
    $doses_administered = max(0, intval($_POST['doses_administered'] ?? $vaccinated_count));
    $batch_no = trim($_POST['batch_no'] ?? null);
    $location_name = trim($_POST['location_name'] ?? null);
    $remarks = trim($_POST['remarks'] ?? null);
    $expiry_date = !empty($_POST['expiry_date']) ? trim($_POST['expiry_date']) : null;

    if ($action === 'add') {
        $sql = "INSERT INTO vaccination_session_logs 
                (range_id, session_date, report_year, report_month, category, animal_type, vaccine_name, vaccinator_id, vaccinator_name, assigned_vaccinator_ids, vaccinated_count, doses_administered, batch_no, location_name, remarks, created_by) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $mysqli->prepare($sql);
        if ($stmt) {
            $stmt->bind_param(
                "isiisssisssisssi",
                $range_id,
                $session_date,
                $report_year,
                $report_month,
                $category,
                $animal_type,
                $vaccine_name,
                $primary_vaccinator_id,
                $vaccinator_name,
                $assigned_vaccinator_ids_str,
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

                // Deduct from Live Vaccine Balance
                deductVaccineBalance($mysqli, $range_id, $report_year, $report_month, $vaccine_name, $doses_administered, $batch_no, $expiry_date, $user_id);

                sendResponse(true, "Vaccination program for {$animal_type} logged successfully ({$vaccinated_count} animals vaccinated, {$doses_administered} doses deducted from inventory).", "../vaccination_targets.php?year={$report_year}", ['id' => $new_id]);
            } else {
                $err = $stmt->error;
                $stmt->close();
                sendResponse(false, 'Error recording vaccination program: ' . $err, "../vaccination_targets.php?year={$report_year}");
            }
        } else {
            sendResponse(false, 'Database prepare error: ' . $mysqli->error, "../vaccination_targets.php?year={$report_year}");
        }
    } else { // EDIT
        $session_id = intval($_POST['id'] ?? 0);
        if ($session_id <= 0) {
            sendResponse(false, 'Invalid vaccination program record ID for edit.');
        }

        // Fetch old record to calculate balance difference
        $old_doses = 0;
        $old_vax = $vaccine_name;
        $old_stmt = $mysqli->prepare("SELECT doses_administered, vaccine_name, report_year, report_month FROM vaccination_session_logs WHERE id = ?");
        if ($old_stmt) {
            $old_stmt->bind_param("i", $session_id);
            $old_stmt->execute();
            if ($or = $old_stmt->get_result()->fetch_assoc()) {
                $old_doses = intval($or['doses_administered']);
                $old_vax = $or['vaccine_name'];
            }
            $old_stmt->close();
        }

        $sql = "UPDATE vaccination_session_logs 
                SET session_date = ?, report_year = ?, report_month = ?, category = ?, animal_type = ?, vaccine_name = ?, vaccinator_id = ?, vaccinator_name = ?, assigned_vaccinator_ids = ?, vaccinated_count = ?, doses_administered = ?, batch_no = ?, location_name = ?, remarks = ? 
                WHERE id = ? AND range_id = ?";
        $stmt = $mysqli->prepare($sql);
        if ($stmt) {
            $stmt->bind_param(
                "siisssissisisssii",
                $session_date,
                $report_year,
                $report_month,
                $category,
                $animal_type,
                $vaccine_name,
                $primary_vaccinator_id,
                $vaccinator_name,
                $assigned_vaccinator_ids_str,
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

                // Live balance adjustment:
                if ($old_vax === $vaccine_name) {
                    $doses_diff = $doses_administered - $old_doses;
                    deductVaccineBalance($mysqli, $range_id, $report_year, $report_month, $vaccine_name, $doses_diff, $batch_no, $expiry_date, $user_id);
                } else {
                    // Revert old vaccine doses and deduct new vaccine doses
                    deductVaccineBalance($mysqli, $range_id, $report_year, $report_month, $old_vax, -$old_doses, null, null, $user_id);
                    deductVaccineBalance($mysqli, $range_id, $report_year, $report_month, $vaccine_name, $doses_administered, $batch_no, $expiry_date, $user_id);
                }

                sendResponse(true, "Vaccination program record updated and inventory balances synced successfully.", "../vaccination_targets.php?year={$report_year}");
            } else {
                $err = $stmt->error;
                $stmt->close();
                sendResponse(false, 'Error updating program record: ' . $err, "../vaccination_targets.php?year={$report_year}");
            }
        } else {
            sendResponse(false, 'Database prepare error: ' . $mysqli->error, "../vaccination_targets.php?year={$report_year}");
        }
    }
}

sendResponse(false, 'Unhandled action.');
