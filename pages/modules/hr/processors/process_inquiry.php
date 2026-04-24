<?php
session_start();
require_once '../../../../config/db_connect.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $inquiry_id = $_POST['inquiry_id'];
    $action = $_POST['action_type'];
    $admin_id = $_SESSION['user_id'] ?? 1; 

    if ($action == 'minute') {
        $officer_id = $_POST['officer_id'];
        $note = $mysqli->real_escape_string($_POST['admin_note']);

        $mysqli->query("UPDATE inquiries SET status = 'Minuted' WHERE id = $inquiry_id");

        $stmt = $mysqli->prepare("INSERT INTO inquiry_logs (inquiry_id, action_taken, processed_by, assigned_to, notes) VALUES (?, 'MINUTE', ?, ?, ?)");
        $stmt->bind_param("iiis", $inquiry_id, $admin_id, $officer_id, $note);
        $stmt->execute();

        
    } elseif ($action == 'reply') {
        $content = $mysqli->real_escape_string($_POST['reply_content']);

        $mysqli->query("UPDATE inquiries SET status = 'Replied' WHERE id = $inquiry_id");


        $stmt = $mysqli->prepare("INSERT INTO inquiry_logs (inquiry_id, action_taken, processed_by, notes) VALUES (?, 'REPLY', ?, ?)");
        $stmt->bind_param("iis", $inquiry_id, $admin_id, $content);
        $stmt->execute();

    }

    header("Location: ../inquiry_management.php?success=1");
}