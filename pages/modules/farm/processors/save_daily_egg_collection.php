<?php
// pages/modules/farm/processors/save_daily_egg_collection.php
session_start();
require_once '../../../../config/db_connect.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'farms_dd') {
    die("Access denied");
}

$user_id = $_SESSION['user_id'] ?? 1;
$action = $_POST['action'] ?? $_GET['action'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $batch_id = intval($_POST['batch_id']);
    $cage_id = intval($_POST['cage_id']);
    $collection_date = $mysqli->real_escape_string($_POST['collection_date']);
    $pullets = intval($_POST['pullets']);
    $cockerels = intval($_POST['cockerels']);
    $total_eggs = intval($_POST['total_eggs']);
    $hatchable = intval($_POST['hatchable_eggs']);
    $table_eggs = intval($_POST['table_eggs']);
    $cracked = intval($_POST['cracked_eggs']);

    // Check ownership of the selected batch in vaccine_batches
    $chk = $mysqli->prepare("SELECT id FROM vaccine_batches WHERE id = ? AND user_id = ?");
    $chk->bind_param("ii", $batch_id, $user_id);
    $chk->execute();
    $chk->store_result();
    if ($chk->num_rows === 0) {
        $chk->close();
        header("Location: ../parent_stock_operations.php?status=error&msg=Access denied to the specified batch.");
        exit();
    }
    $chk->close();

    // Check eggs count validation
    if ($total_eggs !== ($hatchable + $table_eggs + $cracked)) {
        header("Location: ../parent_stock_operations.php?status=error&msg=Total eggs must equal sum of hatchable, table, and cracked eggs.");
        exit();
    }

    if ($action === 'create') {
        $stmt = $mysqli->prepare("INSERT INTO daily_egg_production (batch_id, cage_id, collection_date, pullets, cockerels, total_eggs, hatchable_eggs, table_eggs, cracked_eggs) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("iissiiiii", $batch_id, $cage_id, $collection_date, $pullets, $cockerels, $total_eggs, $hatchable, $table_eggs, $cracked);

        if ($stmt->execute()) {
            header("Location: ../parent_stock_operations.php?status=success&msg=Daily egg collection recorded.");
        } else {
            header("Location: ../parent_stock_operations.php?status=error&msg=Failed to save collection detail.");
        }
        $stmt->close();
    } elseif ($action === 'update') {
        $id = intval($_POST['id']);

        // Verify this daily egg production record is associated with a batch owned by this user
        $chk_record = $mysqli->prepare("SELECT dep.id FROM daily_egg_production dep JOIN vaccine_batches b ON dep.batch_id = b.id WHERE dep.id = ? AND b.user_id = ?");
        $chk_record->bind_param("ii", $id, $user_id);
        $chk_record->execute();
        $chk_record->store_result();
        if ($chk_record->num_rows === 0) {
            $chk_record->close();
            header("Location: ../parent_stock_operations.php?status=error&msg=Access denied to edit this record.");
            exit();
        }
        $chk_record->close();

        $stmt = $mysqli->prepare("UPDATE daily_egg_production SET batch_id = ?, cage_id = ?, collection_date = ?, pullets = ?, cockerels = ?, total_eggs = ?, hatchable_eggs = ?, table_eggs = ?, cracked_eggs = ? WHERE id = ?");
        $stmt->bind_param("iissiiiiii", $batch_id, $cage_id, $collection_date, $pullets, $cockerels, $total_eggs, $hatchable, $table_eggs, $cracked, $id);

        if ($stmt->execute()) {
            header("Location: ../parent_stock_operations.php?status=success&msg=Daily egg collection updated.");
        } else {
            header("Location: ../parent_stock_operations.php?status=error&msg=Failed to update collection.");
        }
        $stmt->close();
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'GET' && $action === 'delete') {
    $id = intval($_GET['id']);

    // Verify this daily egg production record is associated with a batch owned by this user
    $chk_record = $mysqli->prepare("SELECT dep.id FROM daily_egg_production dep JOIN vaccine_batches b ON dep.batch_id = b.id WHERE dep.id = ? AND b.user_id = ?");
    $chk_record->bind_param("ii", $id, $user_id);
    $chk_record->execute();
    $chk_record->store_result();
    if ($chk_record->num_rows === 0) {
        $chk_record->close();
        header("Location: ../parent_stock_operations.php?status=error&msg=Access denied to delete this record.");
        exit();
    }
    $chk_record->close();

    $stmt = $mysqli->prepare("DELETE FROM daily_egg_production WHERE id = ?");
    $stmt->bind_param("i", $id);

    if ($stmt->execute()) {
        header("Location: ../parent_stock_operations.php?status=success&msg=Daily egg collection deleted.");
    } else {
        header("Location: ../parent_stock_operations.php?status=error&msg=Failed to delete collection record.");
    }
    $stmt->close();
}

$mysqli->close();
