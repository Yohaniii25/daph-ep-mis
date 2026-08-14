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
    $id                 = intval($_POST['id'] ?? 0);
    $date               = trim($_POST['started_date'] ?? '');
    $year               = intval($_POST['year'] ?? date('Y'));
    $task               = trim($_POST['programme_type'] ?? '');
    $place              = trim($_POST['location'] ?? '');
    $durations          = $_POST['duration'] ?? [];

    // Filter empty values and join using comma delimiter
    $durations = array_filter(array_map('trim', $durations));
    $time_duration = implode(', ', $durations);

    if ($id <= 0 || empty($date) || empty($task) || empty($place) || empty($time_duration)) {
        header("Location: ../advanced_programme.php?status=db_error&year=" . $year);
        exit();
    }

    // Ensure isolation: if user has a training_center_id, check it; otherwise update by id
    if ($training_center_id > 0) {
        $stmt = $mysqli->prepare("UPDATE training_advanced_programmes SET date = ?, task = ?, place = ?, time_duration = ? WHERE id = ? AND training_center_id = ?");
        if ($stmt) {
            $stmt->bind_param("ssssii", $date, $task, $place, $time_duration, $id, $training_center_id);
            if ($stmt->execute()) {
                header("Location: ../advanced_programme.php?status=updated&year=" . $year . "&center_id=" . $training_center_id);
            } else {
                header("Location: ../advanced_programme.php?status=db_error&year=" . $year);
            }
            $stmt->close();
        }
    } else {
        $stmt = $mysqli->prepare("UPDATE training_advanced_programmes SET date = ?, task = ?, place = ?, time_duration = ? WHERE id = ?");
        if ($stmt) {
            $stmt->bind_param("ssssi", $date, $task, $place, $time_duration, $id);
            if ($stmt->execute()) {
                header("Location: ../advanced_programme.php?status=updated&year=" . $year);
            } else {
                header("Location: ../advanced_programme.php?status=db_error&year=" . $year);
            }
            $stmt->close();
        }
    }
} else {
    header("Location: ../advanced_programme.php");
}
exit();
