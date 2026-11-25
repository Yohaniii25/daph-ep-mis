<?php
session_start();
require_once '../../../../config/db_connect.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'sms') {
    header("Location: ../../../index.php");
    exit();
}

if (isset($_POST['save_advanced'])) {

    $user_id       = intval($_SESSION['user_id'] ?? 0);
    $range_id      = intval($_SESSION['range_id'] ?? 1);
    $date          = trim($_POST['date'] ?? date('Y-m-d'));
    $place         = trim($_POST['place'] ?? '');
    $task          = trim($_POST['task'] ?? '');
    $distance      = floatval($_POST['distance'] ?? 0);
    $time_duration = trim($_POST['time_duration'] ?? '');

    if (empty($date) || empty($place) || empty($task)) {
        header("Location: ../advanced_programme.php?status=missing_fields");
        exit();
    }

    $stmt = $mysqli->prepare("INSERT INTO advanced_programmes (range_id, user_id, date, task, place, distance, time_duration) VALUES (?, ?, ?, ?, ?, ?, ?)");
    if ($stmt) {
        $stmt->bind_param("iisssds", $range_id, $user_id, $date, $task, $place, $distance, $time_duration);
        if ($stmt->execute()) {
            $stmt->close();
            header("Location: ../advanced_programme.php?status=success");
            exit();
        }
        $stmt->close();
    }

    header("Location: ../advanced_programme.php?status=db_error");
    exit();

} else {
    // Prevent direct access
    header("Location: ../advanced_programme.php");
    exit();
}