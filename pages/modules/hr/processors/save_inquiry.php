<?php
session_start();
require_once '../../../../config/db_connect.php';

if (isset($_POST['submit_inquiry'])) {
    $name    = $mysqli->real_escape_string($_POST['sender_name']);
    $email   = $mysqli->real_escape_string($_POST['sender_email']);
    $subject = $mysqli->real_escape_string($_POST['subject']);
    $body    = $mysqli->real_escape_string($_POST['message_body']);
    
    // Default status is 'Pending' as per our DB structure
    $query = "INSERT INTO inquiries (sender_name, sender_email, subject, message_body, status) 
              VALUES ('$name', '$email', '$subject', '$body', 'Pending')";

    if ($mysqli->query($query)) {
        $_SESSION['msg'] = "Email recorded successfully. You can now minute or reply.";
        $_SESSION['msg_type'] = "success";
    } else {
        $_SESSION['msg'] = "Database Error: " . $mysqli->error;
        $_SESSION['msg_type'] = "danger";
    }

    header("Location: ../inquiry_management.php");
    exit();
}