<?php
session_start();
require_once '../../../../config/db_connect.php';

if (!isset($_SESSION['logged_in']) || $_SESSION['role'] !== 'veterinary_surgeon' || !isset($_SESSION['user_id'])) {
    header("Location: ../../../../index.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $category_name = trim($_POST['category_name'] ?? '');
    $sort_order    = intval($_POST['sort_order'] ?? 0);

    if (empty($category_name)) {
        $_SESSION['msg'] = "Error: Category name cannot be empty.";
        $_SESSION['msg_type'] = "danger";
        header("Location: ../section_e.php?status=db_error");
        exit();
    }

    // Check duplicate category name
    $dup_stmt = $mysqli->prepare("SELECT id FROM production_categories WHERE category_name = ?");
    if ($dup_stmt) {
        $dup_stmt->bind_param("s", $category_name);
        $dup_stmt->execute();
        if ($dup_stmt->get_result()->num_rows > 0) {
            $_SESSION['msg'] = "Error: Category name already exists.";
            $_SESSION['msg_type'] = "danger";
            $dup_stmt->close();
            header("Location: ../section_e.php?status=db_error");
            exit();
        }
        $dup_stmt->close();
    }

    $stmt = $mysqli->prepare("INSERT INTO production_categories (category_name, sort_order) VALUES (?, ?)");
    if ($stmt) {
        $stmt->bind_param("si", $category_name, $sort_order);
        if ($stmt->execute()) {
            $_SESSION['msg'] = "Production Category saved successfully!";
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
