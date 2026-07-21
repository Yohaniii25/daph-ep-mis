<?php
session_start();
require_once '../../../../config/db_connect.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'farms_dd') {
    die("Access denied");
}

$user_id = $_SESSION['user_id'] ?? 1;
$action = $_POST['action'] ?? $_GET['action'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $sales_date   = $mysqli->real_escape_string($_POST['sales_date']);
    $egg_category = $mysqli->real_escape_string($_POST['egg_category']);
    $qty_sold     = intval($_POST['quantity_sold']);
    $actual_rate  = floatval($_POST['actual_rate']);
    $hope_rate    = floatval($_POST['hope_rate']);
    $farm_id      = $_SESSION['farm_id'] ?? null;

    if ($action === 'create') {
        // Create Transaction
        $stmt = $mysqli->prepare("INSERT INTO hatchery_sales (user_id, farm_id, sales_date, egg_category, quantity_sold, actual_rate, hope_rate) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("iissidd", $user_id, $farm_id, $sales_date, $egg_category, $qty_sold, $actual_rate, $hope_rate);

        if ($stmt->execute()) {
            header("Location: ../inputs_revenue.php?status=success&msg=Sales transaction recorded correctly.");
        } else {
            header("Location: ../inputs_revenue.php?status=error&msg=Failed to write transaction record.");
        }
        $stmt->close();
    } elseif ($action === 'update') {
        // Update Transaction
        $id = intval($_POST['id']);

        // Scope update to user's assigned farm_id to secure action
        $stmt = $mysqli->prepare("UPDATE hatchery_sales SET sales_date = ?, egg_category = ?, quantity_sold = ?, actual_rate = ?, hope_rate = ? WHERE id = ? AND farm_id = ?");
        $stmt->bind_param("ssiddii", $sales_date, $egg_category, $qty_sold, $actual_rate, $hope_rate, $id, $farm_id);

        if ($stmt->execute()) {
            header("Location: ../inputs_revenue.php?status=success&msg=Sales transaction modified successfully.");
        } else {
            header("Location: ../inputs_revenue.php?status=error&msg=Failed to update commercial data.");
        }
        $stmt->close();
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'GET' && $action === 'delete') {
    // Delete Transaction
    $id = intval($_GET['id']);
    $farm_id = $_SESSION['farm_id'] ?? null;

    // Scope delete to user's assigned farm_id to secure action
    $stmt = $mysqli->prepare("DELETE FROM hatchery_sales WHERE id = ? AND farm_id = ?");
    $stmt->bind_param("ii", $id, $farm_id);

    if ($stmt->execute()) {
        header("Location: ../inputs_revenue.php?status=success&msg=Invoice row removed completely.");
    } else {
        header("Location: ../inputs_revenue.php?status=error&msg=Failed to execute drop parameter.");
    }
    $stmt->close();
}

$mysqli->close();
