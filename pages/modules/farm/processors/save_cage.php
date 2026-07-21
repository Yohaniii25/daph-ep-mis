<?php
// pages/modules/farm/processors/save_cage.php
session_start();
require_once '../../../../config/db_connect.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'farms_dd') {
    die("Access denied");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $cage_name = trim($mysqli->real_escape_string($_POST['cage_name']));

    if (empty($cage_name)) {
        header("Location: ../parent_stock_operations.php?status=error&msg=Cage name cannot be empty.");
        exit();
    }

    $stmt = $mysqli->prepare("INSERT INTO cages (cage_name) VALUES (?)");
    $stmt->bind_param("s", $cage_name);

    if ($stmt->execute()) {
        header("Location: ../parent_stock_operations.php?status=success&msg=Cage added successfully.");
    } else {
        header("Location: ../parent_stock_operations.php?status=error&msg=Failed to add cage: " . $mysqli->error);
    }
    $stmt->close();
    $mysqli->close();
} else {
    header("Location: ../parent_stock_operations.php");
}
