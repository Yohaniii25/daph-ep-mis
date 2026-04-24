<?php
session_start();
require_once '../../../../config/db_connect.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'administrator') {
    header("Location: ../../../index.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $user_id = $_SESSION['user_id'];
    $original_id = mysqli_real_escape_string($mysqli, $_POST['original_id']);
    $programme_year = mysqli_real_escape_string($mysqli, $_POST['programme_year']);
    $place = mysqli_real_escape_string($mysqli, $_POST['place']);
    $activity_description = mysqli_real_escape_string($mysqli, $_POST['activity_description']);
    $amendment_reason = mysqli_real_escape_string($mysqli, $_POST['amendment_reason']);

    $getTypeSql = "SELECT type_id FROM advanced_programmes WHERE id = '$original_id' LIMIT 1";
    $typeResult = $mysqli->query($getTypeSql);
    
    if ($typeResult && $typeResult->num_rows > 0) {
        $typeRow = $typeResult->fetch_assoc();
        $type_id = $typeRow['type_id'];

        $insertSql = "INSERT INTO amended_programmes 
                     (user_id, original_id, programme_year, type_id, place, activity_description, amendment_reason) 
                     VALUES 
                     ('$user_id', '$original_id', '$programme_year', '$type_id', '$place', '$activity_description', '$amendment_reason')";

        if ($mysqli->query($insertSql)) {

            $_SESSION['success_msg'] = "Programme amended successfully.";
            header("Location: ../amend_programme.php?status=success");
            exit();
        } else {

            $_SESSION['error_msg'] = "Database error: " . $mysqli->error;
            header("Location: ../amend_programme.php?status=error");
            exit();
        }
    } else {
        // Original Programme Not Found
        $_SESSION['error_msg'] = "Could not find the original programme data.";
        header("Location: amend_programme.php?status=not_found");
        exit();
    }
} else {
    // Direct Access Forbidden
    header("Location: ../amend_programme.php");
    exit();
}