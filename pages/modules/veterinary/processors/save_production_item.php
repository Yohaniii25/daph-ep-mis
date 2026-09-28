<?php
session_start();
require_once __DIR__ . '/../../../../config/db_connect.php';

/** @var mysqli $mysqli */
global $mysqli;

if (!isset($_SESSION['logged_in']) || !in_array($_SESSION['role'] ?? '', ['veterinary_surgeon', 'admin', 'super_admin']) || !isset($_SESSION['user_id'])) {
    header("Location: ../../../../index.php");
    exit();
}

$year  = intval($_POST['year'] ?? date('Y'));
$month = intval($_POST['month'] ?? date('n'));
$active_tab = trim($_POST['active_tab'] ?? '');

$redirect_base = "../section_e.php?year={$year}&month={$month}";
if ($active_tab) {
    $redirect_base .= "&tab=" . urlencode($active_tab);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $category_id = intval($_POST['category_id'] ?? 0);
    $item_name   = trim($_POST['item_name'] ?? '');
    $unit        = trim($_POST['unit'] ?? '');

    if (empty($category_id) || empty($item_name) || empty($unit)) {
        $_SESSION['msg'] = "Error: All fields are required.";
        $_SESSION['msg_type'] = "danger";
        header("Location: {$redirect_base}&status=error");
        exit();
    }

    // Check duplicate item name in the same category
    $dup_stmt = $mysqli->prepare("SELECT id, is_active FROM production_items WHERE category_id = ? AND item_name = ?");
    if ($dup_stmt) {
        $dup_stmt->bind_param("is", $category_id, $item_name);
        $dup_stmt->execute();
        $dup_res = $dup_stmt->get_result();
        if ($dup_row = $dup_res->fetch_assoc()) {
            if (intval($dup_row['is_active']) === 0) {
                // Reactivate archived item
                $react_id = intval($dup_row['id']);
                $mysqli->query("UPDATE production_items SET is_active = 1, archived_year = NULL, unit = '" . $mysqli->real_escape_string($unit) . "' WHERE id = {$react_id}");
                $_SESSION['msg'] = "Subcategory '{$item_name}' was previously archived and has now been reactivated!";
                $_SESSION['msg_type'] = "success";
                $dup_stmt->close();
                header("Location: {$redirect_base}&status=reactivated");
                exit();
            } else {
                $_SESSION['msg'] = "Error: Sub Category '{$item_name}' is already active in this category.";
                $_SESSION['msg_type'] = "danger";
                $dup_stmt->close();
                header("Location: {$redirect_base}&status=error");
                exit();
            }
        }
        $dup_stmt->close();
    }

    $stmt = $mysqli->prepare("INSERT INTO production_items (category_id, item_name, unit, is_active) VALUES (?, ?, ?, 1)");
    if ($stmt) {
        $stmt->bind_param("iss", $category_id, $item_name, $unit);
        if ($stmt->execute()) {
            $_SESSION['msg'] = "Production Sub Category '{$item_name}' added successfully!";
            $_SESSION['msg_type'] = "success";
            header("Location: {$redirect_base}&status=added");
        } else {
            $_SESSION['msg'] = "Database error: " . $stmt->error;
            $_SESSION['msg_type'] = "danger";
            header("Location: {$redirect_base}&status=db_error");
        }
        $stmt->close();
    } else {
        $_SESSION['msg'] = "Database preparation failed.";
        $_SESSION['msg_type'] = "danger";
        header("Location: {$redirect_base}&status=db_error");
    }
} else {
    header("Location: {$redirect_base}");
}
exit();
