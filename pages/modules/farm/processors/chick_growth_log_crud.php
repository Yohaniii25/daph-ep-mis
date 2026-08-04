<?php
// pages/modules/farm/processors/chick_growth_log_crud.php
session_start();
require_once '../../../../config/db_connect.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'farms_dd') {
    if (isset($_GET['action']) && $_GET['action'] === 'get_opening_balance') {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Access denied']);
        exit();
    }
    die("Access denied");
}

$action = $_POST['action'] ?? $_GET['action'] ?? '';

// AJAX Endpoint: Auto-Calculate Opening Balance for Scenario A
if ($action === 'get_opening_balance') {
    header('Content-Type: application/json');
    $cage_id = intval($_GET['cage_id'] ?? 0);
    $record_date = trim($_GET['record_date'] ?? date('Y-m-d'));

    if ($cage_id <= 0) {
        echo json_encode(['success' => false, 'opening_chicks_count' => 0]);
        exit();
    }

    // 1. Check previous day record in chick_growth_log
    $stmt = $mysqli->prepare("SELECT opening_chicks_count, no_of_deaths FROM chick_growth_log WHERE cage_id = ? AND record_date < ? ORDER BY record_date DESC, id DESC LIMIT 1");
    $stmt->bind_param("is", $cage_id, $record_date);
    $stmt->execute();
    $res = $stmt->get_result();

    if ($res && $row = $res->fetch_assoc()) {
        $prev_opening = intval($row['opening_chicks_count']);
        $prev_deaths = intval($row['no_of_deaths']);
        $calc_opening = max(0, $prev_opening - $prev_deaths);
        echo json_encode([
            'success' => true, 
            'opening_chicks_count' => $calc_opening, 
            'no_of_deaths' => $prev_deaths,
            'source' => 'previous_log'
        ]);
        $stmt->close();
        exit();
    }
    $stmt->close();

    // 2. If no previous growth log entry exists for this cage, check Hatchery Register healthy chicks loaded to this cage
    $stmt2 = $mysqli->prepare("SELECT IFNULL(SUM(no_of_good_chicks), 0) AS total_healthy FROM hatchery_register WHERE loaded_to_cage_id = ? AND record_date <= ?");
    $stmt2->bind_param("is", $cage_id, $record_date);
    $stmt2->execute();
    $res2 = $stmt2->get_result();

    $hatchery_chicks = 0;
    if ($res2 && $row2 = $res2->fetch_assoc()) {
        $hatchery_chicks = intval($row2['total_healthy']);
    }
    $stmt2->close();

    echo json_encode([
        'success' => true, 
        'opening_chicks_count' => $hatchery_chicks, 
        'no_of_deaths' => 0,
        'source' => 'hatchery_register'
    ]);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
    $record_date = trim($_POST['record_date'] ?? '');
    $cage_id = intval($_POST['cage_id'] ?? 0);
    $opening_chicks_count = intval($_POST['opening_chicks_count'] ?? 0);
    $no_of_deaths = intval($_POST['no_of_deaths'] ?? 0);
    $feed_type = trim($_POST['feed_type'] ?? '');
    $feed_amount_to_be_given = floatval($_POST['feed_amount_to_be_given'] ?? 0.00);
    $feed_amount_given = floatval($_POST['feed_amount_given'] ?? 0.00);
    $vaccination_treatment = trim($_POST['vaccination_treatment'] ?? '');

    if (empty($record_date) || $cage_id <= 0) {
        header("Location: ../chick_details.php?tab=growth&status=error&msg=" . urlencode("Record date and cage selection are required."));
        exit();
    }

    if ($action === 'create') {
        $sql = "INSERT INTO chick_growth_log (record_date, cage_id, opening_chicks_count, no_of_deaths, feed_type, feed_amount_to_be_given, feed_amount_given, vaccination_treatment) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $mysqli->prepare($sql);
        $stmt->bind_param("siiisdds", $record_date, $cage_id, $opening_chicks_count, $no_of_deaths, $feed_type, $feed_amount_to_be_given, $feed_amount_given, $vaccination_treatment);

        if ($stmt->execute()) {
            header("Location: ../chick_details.php?tab=growth&status=success&msg=" . urlencode("Daily growth log saved successfully."));
        } else {
            header("Location: ../chick_details.php?tab=growth&status=error&msg=" . urlencode("Failed to save growth log: " . $stmt->error));
        }
        $stmt->close();
    } elseif ($action === 'update') {
        if ($id <= 0) {
            header("Location: ../chick_details.php?tab=growth&status=error&msg=" . urlencode("Invalid record ID."));
            exit();
        }

        $sql = "UPDATE chick_growth_log SET record_date = ?, cage_id = ?, opening_chicks_count = ?, no_of_deaths = ?, feed_type = ?, feed_amount_to_be_given = ?, feed_amount_given = ?, vaccination_treatment = ? WHERE id = ?";
        $stmt = $mysqli->prepare($sql);
        $stmt->bind_param("siiisddsi", $record_date, $cage_id, $opening_chicks_count, $no_of_deaths, $feed_type, $feed_amount_to_be_given, $feed_amount_given, $vaccination_treatment, $id);

        if ($stmt->execute()) {
            header("Location: ../chick_details.php?tab=growth&status=success&msg=" . urlencode("Daily growth log updated successfully."));
        } else {
            header("Location: ../chick_details.php?tab=growth&status=error&msg=" . urlencode("Failed to update growth log: " . $stmt->error));
        }
        $stmt->close();
    }
    $mysqli->close();
} elseif ($_SERVER['REQUEST_METHOD'] === 'GET' && $action === 'delete') {
    $id = intval($_GET['id'] ?? 0);
    if ($id <= 0) {
        header("Location: ../chick_details.php?tab=growth&status=error&msg=" . urlencode("Invalid record ID."));
        exit();
    }

    $stmt = $mysqli->prepare("DELETE FROM chick_growth_log WHERE id = ?");
    $stmt->bind_param("i", $id);

    if ($stmt->execute()) {
        header("Location: ../chick_details.php?tab=growth&status=success&msg=" . urlencode("Growth log deleted successfully."));
    } else {
        header("Location: ../chick_details.php?tab=growth&status=error&msg=" . urlencode("Failed to delete growth log: " . $stmt->error));
    }
    $stmt->close();
    $mysqli->close();
} else {
    header("Location: ../chick_details.php");
    exit();
}
