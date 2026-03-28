<?php
session_start();
require_once '../../../../config/db_connect.php';

if (!isset($_SESSION['logged_in']) || $_SESSION['role'] !== 'veterinary_surgeon') {
    header("Location: ../../../../index.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $range_id       = $_SESSION['range_id'];
    $year           = intval($_POST['year']);
    $designation    = mysqli_real_escape_string($mysqli, $_POST['designation']);
    $target_ai      = intval($_POST['target_ai']);
    $target_pd      = intval($_POST['target_pd']);
    $target_calving = intval($_POST['target_calving']);

    // "Smart Save" - Insert new or Update existing for this Year + Designation
    $sql = "INSERT INTO breeding_target_templates 
            (range_id, year, designation, target_ai, target_pd, target_calving) 
            VALUES (?, ?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE 
            target_ai = VALUES(target_ai), 
            target_pd = VALUES(target_pd), 
            target_calving = VALUES(target_calving)";

    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param("iisiii", $range_id, $year, $designation, $target_ai, $target_pd, $target_calving);

    if ($stmt->execute()) {
        $_SESSION['success'] = "Annual targets for $designation updated successfully!";
    } else {
        $_SESSION['error'] = "Error saving targets: " . $mysqli->error;
    }

    header("Location: ../animal_breeding.php");
    exit();
}