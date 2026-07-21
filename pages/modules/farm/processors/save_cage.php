<?php
// pages/modules/farm/processors/save_cage.php
session_start();
require_once '../../../../config/db_connect.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'farms_dd') {
    die("Access denied");
}

$action = $_POST['action'] ?? $_GET['action'] ?? 'create';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $cage_name = trim($mysqli->real_escape_string($_POST['cage_name']));

    if (empty($cage_name)) {
        header("Location: ../parent_stock_operations.php?status=error&msg=Cage name cannot be empty.");
        exit();
    }

    if ($action === 'create') {
        $stmt = $mysqli->prepare("INSERT INTO cages (cage_name) VALUES (?)");
        $stmt->bind_param("s", $cage_name);

        if ($stmt->execute()) {
            header("Location: ../parent_stock_operations.php?status=success&msg=Cage added successfully.");
        } else {
            header("Location: ../parent_stock_operations.php?status=error&msg=Failed to add cage: " . $mysqli->error);
        }
        $stmt->close();
    } elseif ($action === 'update') {
        $id = intval($_POST['id']);
        $stmt = $mysqli->prepare("UPDATE cages SET cage_name = ? WHERE id = ?");
        $stmt->bind_param("si", $cage_name, $id);

        if ($stmt->execute()) {
            header("Location: ../parent_stock_operations.php?status=success&msg=Cage updated successfully.");
        } else {
            header("Location: ../parent_stock_operations.php?status=error&msg=Failed to update cage: " . $mysqli->error);
        }
        $stmt->close();
    }
    $mysqli->close();
} elseif ($_SERVER['REQUEST_METHOD'] === 'GET' && $action === 'delete') {
    $id = intval($_GET['id']);

    $stmt = $mysqli->prepare("DELETE FROM cages WHERE id = ?");
    $stmt->bind_param("i", $id);

    if ($stmt->execute()) {
        header("Location: ../parent_stock_operations.php?status=success&msg=Cage deleted successfully.");
    } else {
        header("Location: ../parent_stock_operations.php?status=error&msg=Failed to delete cage: " . $mysqli->error);
    }
    $stmt->close();
    $mysqli->close();
} else {
    header("Location: ../parent_stock_operations.php");
}
