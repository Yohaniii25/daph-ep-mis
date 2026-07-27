<?php
// pages/modules/farm/processors/day_old_distribution_crud.php
session_start();
require_once '../../../../config/db_connect.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'farms_dd') {
    die("Access denied");
}

$action = $_POST['action'] ?? $_GET['action'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
    $record_date = trim($_POST['record_date'] ?? '');
    $no_of_chicks_produced = intval($_POST['no_of_chicks_produced'] ?? 0);
    $sent_to_place = trim($_POST['sent_to_place'] ?? '');
    $no_of_chicks_sent = intval($_POST['no_of_chicks_sent'] ?? 0);
    $price_per_chick = floatval($_POST['price_per_chick'] ?? 0.00);

    // Auto-calculate Total Amount = no_of_chicks_sent * price_per_chick
    $total_amount = round($no_of_chicks_sent * $price_per_chick, 2);

    if (empty($record_date) || empty($sent_to_place)) {
        header("Location: ../chick_details.php?tab=day_old&status=error&msg=" . urlencode("Record date and destination place are required."));
        exit();
    }

    if ($action === 'create') {
        $sql = "INSERT INTO day_old_chicks_distribution (record_date, no_of_chicks_produced, sent_to_place, no_of_chicks_sent, price_per_chick, total_amount) VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = $mysqli->prepare($sql);
        $stmt->bind_param("sisidd", $record_date, $no_of_chicks_produced, $sent_to_place, $no_of_chicks_sent, $price_per_chick, $total_amount);

        if ($stmt->execute()) {
            header("Location: ../chick_details.php?tab=day_old&status=success&msg=" . urlencode("Day-old chicks distribution record added successfully."));
        } else {
            header("Location: ../chick_details.php?tab=day_old&status=error&msg=" . urlencode("Failed to save distribution record: " . $stmt->error));
        }
        $stmt->close();
    } elseif ($action === 'update') {
        if ($id <= 0) {
            header("Location: ../chick_details.php?tab=day_old&status=error&msg=" . urlencode("Invalid record ID."));
            exit();
        }

        $sql = "UPDATE day_old_chicks_distribution SET record_date = ?, no_of_chicks_produced = ?, sent_to_place = ?, no_of_chicks_sent = ?, price_per_chick = ?, total_amount = ? WHERE id = ?";
        $stmt = $mysqli->prepare($sql);
        $stmt->bind_param("sisi ddi", $record_date, $no_of_chicks_produced, $sent_to_place, $no_of_chicks_sent, $price_per_chick, $total_amount, $id);
        // Note: s i s i d d i -> string, int, string, int, double, double, int (no spaces in string format below)
        $stmt = $mysqli->prepare("UPDATE day_old_chicks_distribution SET record_date = ?, no_of_chicks_produced = ?, sent_to_place = ?, no_of_chicks_sent = ?, price_per_chick = ?, total_amount = ? WHERE id = ?");
        $stmt->bind_param("sisiddi", $record_date, $no_of_chicks_produced, $sent_to_place, $no_of_chicks_sent, $price_per_chick, $total_amount, $id);

        if ($stmt->execute()) {
            header("Location: ../chick_details.php?tab=day_old&status=success&msg=" . urlencode("Day-old chicks distribution record updated successfully."));
        } else {
            header("Location: ../chick_details.php?tab=day_old&status=error&msg=" . urlencode("Failed to update distribution record: " . $stmt->error));
        }
        $stmt->close();
    }
    $mysqli->close();
} elseif ($_SERVER['REQUEST_METHOD'] === 'GET' && $action === 'delete') {
    $id = intval($_GET['id'] ?? 0);
    if ($id <= 0) {
        header("Location: ../chick_details.php?tab=day_old&status=error&msg=" . urlencode("Invalid record ID."));
        exit();
    }

    $stmt = $mysqli->prepare("DELETE FROM day_old_chicks_distribution WHERE id = ?");
    $stmt->bind_param("i", $id);

    if ($stmt->execute()) {
        header("Location: ../chick_details.php?tab=day_old&status=success&msg=" . urlencode("Distribution record deleted successfully."));
    } else {
        header("Location: ../chick_details.php?tab=day_old&status=error&msg=" . urlencode("Failed to delete record: " . $stmt->error));
    }
    $stmt->close();
    $mysqli->close();
} else {
    header("Location: ../chick_details.php?tab=day_old");
    exit();
}
