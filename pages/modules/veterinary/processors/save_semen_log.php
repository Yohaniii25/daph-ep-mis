<?php
session_start();

if (!isset($_SESSION['logged_in']) || $_SESSION['role'] !== 'veterinary_surgeon') {
    header("Location: ../../../../index.php");
    exit();
}

require_once '../../../../config/db_connect.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $range_id        = $_SESSION['range_id'] ?? null;
    $report_month    = filter_input(INPUT_POST, 'report_month', FILTER_VALIDATE_INT);
    $report_year     = date('Y');
    // Check if "Other" species was specified
    $species = $_POST['species'] ?? '';
    if ($species === 'Other' && !empty($_POST['other_species'])) {
        $species = mysqli_real_escape_string($mysqli, $_POST['other_species']);
    } else {
        $species = mysqli_real_escape_string($mysqli, $species);
    }

    // Inventory Quantities (Default to 0 if empty)
    $opening_balance = filter_input(INPUT_POST, 'opening_balance', FILTER_VALIDATE_INT) ?: 0;
    $received_qty    = filter_input(INPUT_POST, 'received_qty', FILTER_VALIDATE_INT) ?: 0;
    $used_qty        = filter_input(INPUT_POST, 'used_qty', FILTER_VALIDATE_INT) ?: 0;
    $issued_qty      = filter_input(INPUT_POST, 'issued_qty', FILTER_VALIDATE_INT) ?: 0;
    $spoiled_qty     = filter_input(INPUT_POST, 'spoiled_qty', FILTER_VALIDATE_INT) ?: 0;

    $paid_amount     = filter_input(INPUT_POST, 'paid_amount', FILTER_VALIDATE_FLOAT) ?: 0.00;

    if (!$range_id || !$report_month || empty($species)) {
        $_SESSION['error'] = "Required fields are missing. Please try again.";
        header("Location: ../breeding_logs.php");
        exit();
    }

    $sql = "INSERT INTO semen_logs 
            (range_id, report_month, report_year, species, opening_balance, 
             received_qty, used_qty, issued_qty, spoiled_qty, paid_amount) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

    $stmt = $mysqli->prepare($sql);

    if ($stmt) {
        $stmt->bind_param(
            "iiisiiiiid",
            $range_id,
            $report_month,
            $report_year,
            $species,
            $opening_balance,
            $received_qty,
            $used_qty,
            $issued_qty,
            $spoiled_qty,
            $paid_amount
        );

        if ($stmt->execute()) {
            $_SESSION['success'] = "Semen log for <strong>$species</strong> recorded successfully!";
        } else {
            $_SESSION['error'] = "Database Error: " . $stmt->error;
        }
        $stmt->close();
    } else {
        $_SESSION['error'] = "Statement Preparation Failed: " . $mysqli->error;
    }

    $mysqli->close();

    header("Location: ../breeding_logs.php");
    exit();
} else {
    header("Location: ../breeding_logs.php");
    exit();
}
