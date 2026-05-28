<?php
session_start();
require_once '../../../../config/db_connect.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'sms') {
    header("Location: ../../../index.php");
    exit();
}

if (isset($_POST['save_advanced'])) {

    $user_id = $_SESSION['user_id'];
    $type_id = intval($_POST['type_id']);
    $year = intval($_POST['programme_year']);
    $place = $mysqli->real_escape_string($_POST['place']);
    $description = $mysqli->real_escape_string($_POST['activity_description']);

    if (empty($type_id) || empty($year) || empty($place)) {
        header("Location: ../advanced_programme.php?status=missing_fields");
        exit();
    }

    $dup_check = $mysqli->query("SELECT id FROM advanced_programmes 
                                 WHERE user_id = '$user_id' 
                                 AND type_id = '$type_id' 
                                 AND programme_year = '$year'");

    if ($dup_check && $dup_check->num_rows > 0) {
        header("Location: ../advanced_programme.php?status=record_exists");
        exit();
    }

    $insert_sql = "INSERT INTO advanced_programmes 
                   (user_id, type_id, programme_year, place, activity_description) 
                   VALUES 
                   ('$user_id', '$type_id', '$year', '$place', '$description')";

    if ($mysqli->query($insert_sql)) {

        header("Location: ../advanced_programme.php?status=success");
        exit();
    } else {

        header("Location: ../advanced_programme.php?status=db_error");
        exit();
    }

} else {
    // Prevent direct access
    header("Location: ../advanced_programme.php");
    exit();
}