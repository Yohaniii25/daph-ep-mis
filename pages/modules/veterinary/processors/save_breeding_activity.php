<?php
session_start();
require_once '../../../../config/db_connect.php';

if (!isset($_SESSION['logged_in']) || $_SESSION['role'] !== 'veterinary_surgeon') {
    header("Location: ../../../../index.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $range_id       = $_SESSION['range_id'];
    $officer_id     = intval($_POST['officer_id']);
    $month_number = intval($_POST['month_number']);
    $year           = intval($_POST['year']);
    $ai_count       = intval($_POST['ai_count']);
    $pd_count       = intval($_POST['pd_count']);
    $calving_count  = intval($_POST['calving_count']);

    // Basic Validation
    if ($officer_id <= 0) {
        $_SESSION['error'] = "Invalid officer selected.";
        header("Location: ../animal_breeding.php");
        exit();
    }

    $stmt = $mysqli->prepare("
        INSERT INTO breeding_progress 
        (range_id, officer_id, year, month_number, ai_count, pd_count, calving_count) 
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ");

    $stmt->bind_param("iiiiiii", $range_id, $officer_id, $year, $month_number, $ai_count, $pd_count, $calving_count);

    if ($stmt->execute()) {
        $_SESSION['success'] = "Quarter $month_number activity recorded!";
    } else {
        $_SESSION['error'] = "Failed to save record: " . $mysqli->error;
    }

    header("Location: ../animal_breeding.php");
    exit();
}