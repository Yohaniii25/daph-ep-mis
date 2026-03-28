<?php
session_start();
require_once '../../../../config/db_connect.php';

// 1. Security Access Check
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || $_SESSION['role'] !== 'veterinary_surgeon') {
    header("Location: ../animal_health.php?status=error&msg=Unauthorized");
    exit();
}

// GET THE USER ID FROM SESSION
// Ensure 'user_id' is the correct key you use during login
$created_by = $_SESSION['user_id'] ?? null; 

// 2. Collect and Sanitize Data
$range_id         = intval($_POST['range_id']);
$date             = $_POST['date'];
$farmer_reg_no    = mysqli_real_escape_string($mysqli, $_POST['farmer_reg_no']);
$disease_name     = mysqli_real_escape_string($mysqli, $_POST['disease_name']);
$occurrence_count = intval($_POST['occurrence_count']);
$vaccine_name     = mysqli_real_escape_string($mysqli, $_POST['vaccine_name'] ?? '');
$doses            = intval($_POST['doses'] ?? 0);
$treatment_details= mysqli_real_escape_string($mysqli, $_POST['treatment_details']);
$report_status    = mysqli_real_escape_string($mysqli, $_POST['report_status'] ?? 'Submitted');

// 3. Updated SQL Statement (Added created_by)
$query = "INSERT INTO animal_health_records 
          (range_id, date, farmer_reg_no, disease_name, occurrence_count, vaccine_name, doses, treatment_details, report_status, created_by) 
          VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

$stmt = $mysqli->prepare($query);

if ($stmt) {
    // UPDATED BIND STRING: Added "i" at the end for created_by (integer)
    // New string: "isssisissi"
    $stmt->bind_param("isssisissi", 
        $range_id, 
        $date, 
        $farmer_reg_no, 
        $disease_name, 
        $occurrence_count, 
        $vaccine_name, 
        $doses, 
        $treatment_details, 
        $report_status,
        $created_by
    );

    if ($stmt->execute()) {
        $_SESSION['success_msg'] = "Record saved successfully.";
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