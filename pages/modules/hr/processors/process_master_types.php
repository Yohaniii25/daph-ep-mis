<?php
session_start();
require_once '../../../../config/db_connect.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'administrator') {
    header("Location: ../../../index.php");
    exit();
}

if (isset($_POST['add_type'])) {
    

    $programme_name = trim($mysqli->real_escape_string($_POST['programme_name']));

    if (empty($programme_name)) {
        header("Location: ../advanced_programme.php?status=empty_name");
        exit();
    }

    $check_query = "SELECT id FROM master_programme_types WHERE LOWER(programme_name) = LOWER('$programme_name')";
    $check_result = $mysqli->query($check_query);

    if ($check_result && $check_result->num_rows > 0) {
        // Type already exists
        header("Location: ../advanced_programme.php?status=duplicate");
        exit();
    } else {

        $insert_query = "INSERT INTO master_programme_types (programme_name, is_active) VALUES ('$programme_name', 1)";
        
        if ($mysqli->query($insert_query)) {

            header("Location: ../advanced_programme.php?status=type_success");
            exit();
        } else {

            header("Location: ../advanced_programme.php?status=db_error");
            exit();
        }
    }
} else {

    header("Location: ../advanced_programme.php");
    exit();
}