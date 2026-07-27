<?php
// pages/modules/farm/processors/month_old_distribution_crud.php
session_start();
require_once '../../../../config/db_connect.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'farms_dd') {
    if (isset($_GET['action']) && $_GET['action'] === 'get_surviving_balance') {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Access denied']);
        exit();
    }
    die("Access denied");
}

$action = $_POST['action'] ?? $_GET['action'] ?? '';

// AJAX Endpoint: Fetch net surviving balance for a cage from chick_growth_log
if ($action === 'get_surviving_balance') {
    header('Content-Type: application/json');
    $cage_id = intval($_GET['cage_id'] ?? 0);

    if ($cage_id <= 0) {
        echo json_encode(['success' => false, 'surviving_balance' => 0]);
        exit();
    }

    $stmt = $mysqli->prepare("SELECT opening_chicks_count, no_of_deaths FROM chick_growth_log WHERE cage_id = ? ORDER BY record_date DESC, id DESC LIMIT 1");
    $stmt->bind_param("i", $cage_id);
    $stmt->execute();
    $res = $stmt->get_result();

    if ($res && $row = $res->fetch_assoc()) {
        $opening = intval($row['opening_chicks_count']);
        $deaths = intval($row['no_of_deaths']);
        $surviving = max(0, $opening - $deaths);
        echo json_encode(['success' => true, 'surviving_balance' => $surviving]);
    } else {
        echo json_encode(['success' => true, 'surviving_balance' => 0]);
    }
    $stmt->close();
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
    $record_date = trim($_POST['record_date'] ?? '');
    $cage_id = !empty($_POST['cage_id']) ? intval($_POST['cage_id']) : NULL;
    $no_of_chicks_produced = intval($_POST['no_of_chicks_produced'] ?? 0);
    $sent_to_place = trim($_POST['sent_to_place'] ?? '');
    $no_of_chicks_sent = intval($_POST['no_of_chicks_sent'] ?? 0);
    $price_per_chick = floatval($_POST['price_per_chick'] ?? 0.00);

    // Auto-calculate Total Amount = no_of_chicks_sent * price_per_chick
    $total_amount = round($no_of_chicks_sent * $price_per_chick, 2);

    if (empty($record_date) || empty($sent_to_place)) {
        header("Location: ../chick_details.php?tab=month_old&status=error&msg=" . urlencode("Record date and destination place are required."));
        exit();
    }

    if ($action === 'create') {
        $sql = "INSERT INTO month_old_chicks_distribution (record_date, cage_id, no_of_chicks_produced, sent_to_place, no_of_chicks_sent, price_per_chick, total_amount) VALUES (?, ?, ?, ?, ?, ?, ?)";
        $stmt = $mysqli->prepare($sql);
        $stmt->bind_param("siisidd", $record_date, $cage_id, $no_of_chicks_produced, $sent_to_place, $no_of_chicks_sent, $price_per_chick, $total_amount);

        if ($stmt->execute()) {
            header("Location: ../chick_details.php?tab=month_old&status=success&msg=" . urlencode("Month-old chicks distribution record added successfully."));
        } else {
            header("Location: ../chick_details.php?tab=month_old&status=error&msg=" . urlencode("Failed to save distribution record: " . $stmt->error));
        }
        $stmt->close();
    } elseif ($action === 'update') {
        if ($id <= 0) {
            header("Location: ../chick_details.php?tab=month_old&status=error&msg=" . urlencode("Invalid record ID."));
            exit();
        }

        $sql = "UPDATE month_old_chicks_distribution SET record_date = ?, cage_id = ?, no_of_chicks_produced = ?, sent_to_place = ?, no_of_chicks_sent = ?, price_per_chick = ?, total_amount = ? WHERE id = ?";
        $stmt = $mysqli->prepare($sql);
        $stmt->bind_param("siisiddi", $record_date, $cage_id, $no_of_chicks_produced, $sent_to_place, $no_of_chicks_sent, $price_per_chick, $total_amount, $id);

        if ($stmt->execute()) {
            header("Location: ../chick_details.php?tab=month_old&status=success&msg=" . urlencode("Month-old chicks distribution record updated successfully."));
        } else {
            header("Location: ../chick_details.php?tab=month_old&status=error&msg=" . urlencode("Failed to update distribution record: " . $stmt->error));
        }
        $stmt->close();
    }
    $mysqli->close();
} elseif ($_SERVER['REQUEST_METHOD'] === 'GET' && $action === 'delete') {
    $id = intval($_GET['id'] ?? 0);
    if ($id <= 0) {
        header("Location: ../chick_details.php?tab=month_old&status=error&msg=" . urlencode("Invalid record ID."));
        exit();
    }

    $stmt = $mysqli->prepare("DELETE FROM month_old_chicks_distribution WHERE id = ?");
    $stmt->bind_param("i", $id);

    if ($stmt->execute()) {
        header("Location: ../chick_details.php?tab=month_old&status=success&msg=" . urlencode("Distribution record deleted successfully."));
    } else {
        header("Location: ../chick_details.php?tab=month_old&status=error&msg=" . urlencode("Failed to delete record: " . $stmt->error));
    }
    $stmt->close();
    $mysqli->close();
} else {
    header("Location: ../chick_details.php?tab=month_old");
    exit();
}
