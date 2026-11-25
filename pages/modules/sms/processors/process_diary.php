<?php
session_start();
require_once '../../../../config/db_connect.php';

if (isset($_POST['save_task'])) {
    $user_id       = intval($_SESSION['user_id'] ?? ($_POST['user_id'] ?? 0));
    $date          = trim($_POST['task_date'] ?? '');
    $place         = trim($_POST['place'] ?? '');
    $activity      = trim($_POST['activity'] ?? '');
    $distance      = trim($_POST['distance'] ?? '0');
    $time_duration = trim($_POST['time_duration'] ?? '');

    $stmt = $mysqli->prepare("INSERT INTO diary_tasks (user_id, task_date, place, distance, activity, time_duration) VALUES (?, ?, ?, ?, ?, ?)");
    if ($stmt) {
        $stmt->bind_param("isssss", $user_id, $date, $place, $distance, $activity, $time_duration);
        if ($stmt->execute()) {
            $stmt->close();
            header("Location: ../daily_diary.php?success=1");
            exit();
        }
        $stmt->close();
    }
    header("Location: ../daily_diary.php?status=error");
    exit();
}
?>