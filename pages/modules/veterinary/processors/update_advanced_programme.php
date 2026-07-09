<?php
session_start();
require_once '../../../../config/db_connect.php';

if (!isset($_SESSION['logged_in']) || $_SESSION['role'] !== 'veterinary_surgeon' || !isset($_SESSION['range_id'])) {
    header("Location: ../../../../index.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $range_id = $_SESSION['range_id'];
    $id = intval($_POST['id'] ?? 0);
    $date = trim($_POST['started_date'] ?? '');
    $year = intval($_POST['year'] ?? 2026);
    $task = trim($_POST['programme_type'] ?? '');
    $place = trim($_POST['location'] ?? '');
    $durations = $_POST['duration'] ?? [];

    // Filter empty values and join using comma as delimiter
    $durations = array_filter(array_map('trim', $durations));
    $time_duration = implode(', ', $durations);

    if (empty($id) || empty($date) || empty($task) || empty($place) || empty($time_duration)) {
        header("Location: ../advanced_programme.php?status=db_error&year=" . $year);
        exit();
    }

    $stmt = $mysqli->prepare("UPDATE advanced_programmes SET date = ?, task = ?, place = ?, time_duration = ? WHERE id = ? AND range_id = ?");
    if ($stmt) {
        $stmt->bind_param("ssssii", $date, $task, $place, $time_duration, $id, $range_id);
        if ($stmt->execute()) {
            header("Location: ../advanced_programme.php?status=updated&year=" . $year);
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
