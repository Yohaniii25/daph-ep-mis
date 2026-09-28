<?php
session_start();
require_once __DIR__ . '/../../../../config/db_connect.php';

/** @var mysqli $mysqli */
global $mysqli;

if (!isset($_SESSION['logged_in']) || $_SESSION['role'] !== 'veterinary_surgeon' || !isset($_SESSION['range_id'])) {
    header("Location: ../../../../index.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $range_id = $_SESSION['range_id'];
    $user_id  = $_SESSION['user_id'] ?? null;
    $date     = trim($_POST['date'] ?? $_POST['started_date'] ?? '');
    $year     = intval($_POST['year'] ?? 0);
    $month    = intval($_POST['month'] ?? 0);

    if ($date && (!$year || !$month)) {
        $timestamp = strtotime($date);
        if (!$year) $year = intval(date('Y', $timestamp));
        if (!$month) $month = intval(date('n', $timestamp));
    }
    if (!$year) $year = 2026;

    $task     = trim($_POST['task'] ?? $_POST['programme_type'] ?? '');
    $place    = trim($_POST['place'] ?? $_POST['location'] ?? '');
    
    // Duty time or legacy duration array
    $duty_time = trim($_POST['duty_time'] ?? '');
    if (empty($duty_time) && !empty($_POST['duration'])) {
        $durations = array_filter(array_map('trim', (array)$_POST['duration']));
        $duty_time = implode(', ', $durations);
    }

    if (empty($date) || empty($task) || empty($place) || empty($duty_time)) {
        header("Location: ../advanced_programme.php?status=db_error&year=" . $year . "&month=" . $month);
        exit();
    }

    $stmt = $mysqli->prepare("INSERT INTO advanced_programmes (range_id, user_id, date, task, place, distance, time_duration) VALUES (?, ?, ?, ?, ?, 0.00, ?)");
    if ($stmt) {
        $stmt->bind_param("iissss", $range_id, $user_id, $date, $task, $place, $duty_time);
        if ($stmt->execute()) {
            header("Location: ../advanced_programme.php?status=added&year=" . $year . "&month=" . $month);
        } else {
            header("Location: ../advanced_programme.php?status=db_error&year=" . $year . "&month=" . $month);
        }
        $stmt->close();
    } else {
        header("Location: ../advanced_programme.php?status=db_error&year=" . $year . "&month=" . $month);
    }
} else {
    header("Location: ../advanced_programme.php");
}
exit();
