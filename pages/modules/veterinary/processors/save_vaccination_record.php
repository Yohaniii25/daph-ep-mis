<?php
session_start();
require_once '../../../../config/db_connect.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || $_SESSION['role'] !== 'veterinary_surgeon') {
    header("Location: ../vaccination_targets.php?status=error&msg=Unauthorized");
    exit();
}

$created_by = $_SESSION['user_id'] ?? null;

$range_id         = intval($_POST['range_id']);
$date             = $_POST['date'];
$farmer_reg_no    = mysqli_real_escape_string($mysqli, $_POST['farmer_reg_no']);
$animal_type      = mysqli_real_escape_string($mysqli, $_POST['animal_type'] ?? 'Other');
$disease_name     = mysqli_real_escape_string($mysqli, $_POST['disease_name'] ?? '');
$occurrence_count = intval($_POST['occurrence_count'] ?? 0);
$vaccine_name     = mysqli_real_escape_string($mysqli, $_POST['vaccine_name'] ?? '');
$doses            = intval($_POST['doses'] ?? 0);
$treatment_details = mysqli_real_escape_string($mysqli, $_POST['treatment_details'] ?? '');
$report_status    = 'Submitted';

// Capture vaccinator selection or manual input
$vaccinator_display = '';
if (!empty($_POST['vaccinator_id'])) {
    $vid = intval($_POST['vaccinator_id']);
    $vst = $mysqli->prepare("SELECT full_name, nic_no FROM casual_vaccinator_deployments WHERE id = ?");
    if ($vst) {
        $vst->bind_param("i", $vid);
        $vst->execute();
        $vres = $vst->get_result()->fetch_assoc();
        if ($vres) {
            $vaccinator_display = $vres['full_name'] . ' (NIC: ' . $vres['nic_no'] . ')';
        }
        $vst->close();
    }
}
if (empty($vaccinator_display) && !empty($_POST['vaccinator_manual'])) {
    $vaccinator_display = mysqli_real_escape_string($mysqli, $_POST['vaccinator_manual']);
}

if (!empty($vaccinator_display)) {
    if (!empty($treatment_details)) {
        $treatment_details .= ' | ' . $vaccinator_display;
    } else {
        $treatment_details = 'Vaccinator: ' . $vaccinator_display;
    }
}

// Basic validation removed to allow optional submissions

$query = "INSERT INTO animal_health_records 
          (range_id, date, farmer_reg_no, animal_type, disease_name, occurrence_count, vaccine_name, doses, treatment_details, report_status, created_by) 
          VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

$stmt = $mysqli->prepare($query);

if ($stmt) {
    $stmt->bind_param(
        "issssisisssi",
        $range_id,
        $date,
        $farmer_reg_no,
        $animal_type,
        $disease_name,
        $occurrence_count,
        $vaccine_name,
        $doses,
        $treatment_details,
        $report_status,
        $created_by
    );

    if ($stmt->execute()) {
        $_SESSION['msg'] = "Vaccination record for " . htmlspecialchars($animal_type) . " saved successfully.";
        $_SESSION['msg_type'] = "success";
        header("Location: ../vaccination_targets.php?status=success");
    } else {
        $_SESSION['msg'] = "Database error: " . htmlspecialchars($stmt->error);
        $_SESSION['msg_type'] = "danger";
        header("Location: ../vaccination_targets.php?status=error");
    }
    $stmt->close();
} else {
    $_SESSION['msg'] = "Prepare error: " . htmlspecialchars($mysqli->error);
    $_SESSION['msg_type'] = "danger";
    header("Location: ../vaccination_targets.php?status=error");
}

$mysqli->close();
exit();
