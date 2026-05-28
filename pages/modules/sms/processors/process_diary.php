<?php
session_start();
require_once '../../../../config/db_connect.php';

if (isset($_POST['save_task'])) {
    $user_id = $_POST['user_id'];
    $date = $_POST['task_date'];
    $place = $mysqli->real_escape_string($_POST['place']);
    $activity = $mysqli->real_escape_string($_POST['activity']);
    $status = $_POST['status'];

    $query = "INSERT INTO diary_tasks (user_id, task_date, place, activity, status) 
              VALUES ('$user_id', '$date', '$place', '$activity', '$status')";

    if ($mysqli->query($query)) {
        header("Location: ../my_diary.php?success=1");
    } else {
        header("Location: ../my_diary.php?status=error");
    }
}
?>