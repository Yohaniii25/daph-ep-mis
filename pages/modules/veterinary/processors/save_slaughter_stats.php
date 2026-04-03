<?php
session_start();

if (!isset($_SESSION['logged_in']) || $_SESSION['role'] !== 'veterinary_surgeon') {
    header("Content-Type: application/json");
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized access.']);
    exit();
}

require_once '../../../../config/db_connect.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $range_id       = $_SESSION['range_id'] ?? null;
    $created_by     = $_SESSION['user_id'] ?? 0;
    
    $report_month   = filter_input(INPUT_POST, 'report_month', FILTER_VALIDATE_INT);
    $report_year    = filter_input(INPUT_POST, 'report_year', FILTER_VALIDATE_INT);
    $species        = $_POST['species'] ?? '';
    $location_type  = $_POST['location_type'] ?? '';
    $animal_count   = filter_input(INPUT_POST, 'animal_count', FILTER_VALIDATE_INT);
    $total_weight   = filter_input(INPUT_POST, 'total_weight_kg', FILTER_VALIDATE_FLOAT);

    if (!$range_id || !$report_month || !$species || !$location_type || $animal_count <= 0) {
        $_SESSION['error'] = "Invalid data provided. Please check all fields.";
        header("Location: ../slaughter_stats.php");
        exit();
    }

    $sql = "INSERT INTO slaughter_statistics 
            (range_id, report_month, report_year, species, location_type, animal_count, total_weight_kg, created_by) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)";

    $stmt = $mysqli->prepare($sql);
    
    $stmt->bind_param("iiisssdi", 
        $range_id, 
        $report_month, 
        $report_year, 
        $species, 
        $location_type, 
        $animal_count, 
        $total_weight, 
        $created_by
    );

    if ($stmt->execute()) {
        $_SESSION['success'] = "Slaughter record for $species saved successfully!";
    } else {
        $_SESSION['error'] = "Database Error: " . $stmt->error;
    }

    $stmt->close();
    $mysqli->close();

    header("Location: ../slaughter_stats.php");
    exit();
}