<?php
session_start();
require_once '../../../../config/db_connect.php';

if (!isset($_SESSION['logged_in']) || $_SESSION['role'] !== 'veterinary_surgeon' || !isset($_SESSION['range_id'])) {
    header("Location: ../../../../index.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $range_id = $_SESSION['range_id'];
    $date = trim($_POST['started_date'] ?? '');
    $year = intval($_POST['year'] ?? 2026);
    $task = trim($_POST['programme_type'] ?? '');
    $place = trim($_POST['location'] ?? '');
    $durations = $_POST['duration'] ?? [];

    // Filter empty values and join using comma as delimiter
    $durations = array_filter(array_map('trim', $durations));
    $time_duration = implode(', ', $durations);

    if (empty($date) || empty($task) || empty($place) || empty($time_duration)) {
        header("Location: ../advanced_programme.php?status=db_error&year=" . $year);
        exit();
    }

    $stmt = $mysqli->prepare("INSERT INTO advanced_programmes (range_id, date, task, place, distance, time_duration) VALUES (?, ?, ?, ?, 0.00, ?)");
    if ($stmt) {
        $stmt->bind_param("issss", $range_id, $date, $task, $place, $time_duration);
        if ($stmt->execute()) {
            header("Location: ../advanced_programme.php?status=added&year=" . $year);
        } else {
            header("Location: ../advanced_programme.php?status=db_error&year=" . $year);
        }
        $stmt->close();
    } else {
        header("Location: ../advanced_programme.php?status=db_error&year=" . $year);
    }
} else {
    header("Location: ../advanced_programme.php");
}
exit();
