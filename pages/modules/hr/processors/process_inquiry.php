<?php
session_start();
require_once '../../../../config/db_connect.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $inquiry_id = $_POST['inquiry_id'];
    $action = $_POST['action_type'];
    $admin_id = $_SESSION['user_id'] ?? 1; // ID of the logged-in admin

    if ($action == 'minute') {
        $officer_id = $_POST['officer_id'];
        $note = $mysqli->real_escape_string($_POST['admin_note']);

        // 1. Update Inquiry Status
        $mysqli->query("UPDATE inquiries SET status = 'Minuted' WHERE id = $inquiry_id");

        // 2. Log the action (Audit Trail)
        $stmt = $mysqli->prepare("INSERT INTO inquiry_logs (inquiry_id, action_taken, processed_by, assigned_to, notes) VALUES (?, 'MINUTE', ?, ?, ?)");
        $stmt->bind_param("iiis", $inquiry_id, $admin_id, $officer_id, $note);
        $stmt->execute();

        // 3. (Optional) Use mail() function to send the email to the officer
        
    } elseif ($action == 'reply') {
        $content = $mysqli->real_escape_string($_POST['reply_content']);

        // 1. Update Inquiry Status
        $mysqli->query("UPDATE inquiries SET status = 'Replied' WHERE id = $inquiry_id");

        // 2. Log the reply
        $stmt = $mysqli->prepare("INSERT INTO inquiry_logs (inquiry_id, action_taken, processed_by, notes) VALUES (?, 'REPLY', ?, ?)");
        $stmt->bind_param("iis", $inquiry_id, $admin_id, $content);
        $stmt->execute();

        // 3. (Optional) Use mail() to send direct reply to applicant
    }

    header("Location: ../inquiry_management.php?success=1");
}