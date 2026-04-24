<?php
session_start();
require_once '../../../../config/db_connect.php';

if (isset($_POST['update_programme'])) {
    $id = intval($_POST['programme_id']);
    $place = $mysqli->real_escape_string($_POST['place']);
    $description = $mysqli->real_escape_string($_POST['description']);
    $user_id = $_SESSION['user_id'];


    $update_sql = "UPDATE advanced_programmes 
                   SET place = '$place', activity_description = '$description' 
                   WHERE id = $id 
                   AND user_id = '$user_id' 
                   AND mid_term_status IN ('Pending', 'Rejected')";

    if ($mysqli->query($update_sql)) {
        header("Location: ../advanced_programme.php?status=updated");
    } else {
        header("Location: ../advanced_programme.php?status=error");
    }
}