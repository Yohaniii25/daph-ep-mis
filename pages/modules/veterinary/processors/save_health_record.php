<?php
session_start();
require_once '../../../../config/db_connect.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || $_SESSION['role'] !== 'veterinary_surgeon') {
    header("Location: ../animal_health.php?status=error&msg=Unauthorized");
    exit();
}

$created_by = $_SESSION['user_id'] ?? null; 

$range_id         = intval($_POST['range_id']);
$date             = $_POST['date'];
$farmer_reg_no    = mysqli_real_escape_string($mysqli, $_POST['farmer_reg_no']);
$animal_type      = mysqli_real_escape_string($mysqli, $_POST['animal_type'] ?? 'Other');
$disease_name     = mysqli_real_escape_string($mysqli, $_POST['disease_name']);
$occurrence_count = intval($_POST['occurrence_count']);
$vaccine_name     = mysqli_real_escape_string($mysqli, $_POST['vaccine_name'] ?? '');
$doses            = intval($_POST['doses'] ?? 0);
$treatment_details= mysqli_real_escape_string($mysqli, $_POST['treatment_details']);
$report_status    = mysqli_real_escape_string($mysqli, $_POST['report_status'] ?? 'Submitted');

$query = "INSERT INTO animal_health_records 
          (range_id, date, farmer_reg_no, animal_type, disease_name, occurrence_count, vaccine_name, doses, treatment_details, report_status, created_by) 
          VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

$stmt = $mysqli->prepare($query);

if ($stmt) {

    $stmt->bind_param("issssisissi", 
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
        $_SESSION['success_msg'] = "Health record for " . htmlspecialchars($animal_type) . " saved successfully.";
        header("Location: ../animal_health.php?status=success");
    } else {
        header("Location: ../animal_health.php?status=error&msg=" . urlencode($stmt->error));
    }
    $stmt->close();
} else {
    header("Location: ../animal_health.php?status=error&msg=" . urlencode($mysqli->error));
}

$mysqli->close();
exit();