<?php
session_start();
require_once '../../../../config/db_connect.php';

if (!isset($_SESSION['logged_in']) || $_SESSION['role'] !== 'veterinary_surgeon' || !isset($_SESSION['user_id'])) {
    header("Location: ../../../../index.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $category_id = intval($_POST['category_id'] ?? 0);
    $item_name   = trim($_POST['item_name'] ?? '');
    $unit        = trim($_POST['unit'] ?? '');

    if (empty($category_id) || empty($item_name) || empty($unit)) {
        $_SESSION['msg'] = "Error: All fields are required.";
        $_SESSION['msg_type'] = "danger";
        header("Location: ../section_e.php?status=db_error");
        exit();
    }

    // Check duplicate item name in the same category
    $dup_stmt = $mysqli->prepare("SELECT id FROM production_items WHERE category_id = ? AND item_name = ?");
    if ($dup_stmt) {
        $dup_stmt->bind_param("is", $category_id, $item_name);
        $dup_stmt->execute();
        if ($dup_stmt->get_result()->num_rows > 0) {
            $_SESSION['msg'] = "Error: Sub Category / Item already exists in this category.";
            $_SESSION['msg_type'] = "danger";
            $dup_stmt->close();
            header("Location: ../section_e.php?status=db_error");
            exit();
        }
        $dup_stmt->close();
    }

    $stmt = $mysqli->prepare("INSERT INTO production_items (category_id, item_name, unit) VALUES (?, ?, ?)");
    if ($stmt) {
        $stmt->bind_param("iss", $category_id, $item_name, $unit);
        if ($stmt->execute()) {
            $_SESSION['msg'] = "Production Sub Category saved successfully!";
            $_SESSION['msg_type'] = "success";
            header("Location: ../section_e.php?status=added");
        } else {
            $_SESSION['msg'] = "Database error: " . $stmt->error;
            $_SESSION['msg_type'] = "danger";
            header("Location: ../section_e.php?status=db_error");
        }
        $stmt->close();
    } else {
        $_SESSION['msg'] = "Database preparation failed.";
        $_SESSION['msg_type'] = "danger";
        header("Location: ../section_e.php?status=db_error");
    }
} else {
    header("Location: ../section_e.php");
}
exit();
?>
