<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once '../../../../config/db_connect.php';

$allowed_roles = ['training_officer', 'administrator', 'provincial_director', 'district_dd'];
if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], $allowed_roles)) {
    header("Location: ../../../../index.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $training_center_id = intval($_POST['training_center_id'] ?? $_SESSION['training_center_id'] ?? 0);
    $date               = trim($_POST['started_date'] ?? '');
    $year               = intval($_POST['year'] ?? date('Y'));
    $task               = trim($_POST['programme_type'] ?? '');
    $place              = trim($_POST['location'] ?? '');
    $durations          = $_POST['duration'] ?? [];

    // Filter empty values and join using comma delimiter
    $durations = array_filter(array_map('trim', $durations));
    $time_duration = implode(', ', $durations);

    if ($training_center_id <= 0 || empty($date) || empty($task) || empty($place) || empty($time_duration)) {
        header("Location: ../advanced_programme.php?status=db_error&year=" . $year);
        exit();
    }


    $stmt = $mysqli->prepare("INSERT INTO training_advanced_programmes (training_center_id, date, task, place, distance, time_duration) VALUES (?, ?, ?, ?, 0.00, ?)");
    if ($stmt) {
        $stmt->bind_param("issss", $training_center_id, $date, $task, $place, $time_duration);
        if ($stmt->execute()) {
            header("Location: ../advanced_programme.php?status=added&year=" . $year . "&center_id=" . $training_center_id);
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
