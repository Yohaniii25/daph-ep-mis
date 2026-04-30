<?php
session_start();
require_once '../../../../config/db_connect.php';

if (!isset($_SESSION['role'])) {
    header("Location: ../../../../index.php");
    exit();
}

$user_id = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['profile_image'])) {
    $file = $_FILES['profile_image'];
    
    // Check for upload errors
    if ($file['error'] !== UPLOAD_ERR_OK) {
        $_SESSION['error'] = "File upload failed with error code: " . $file['error'];
        header("Location: ../profile.php");
        exit();
    }
    
    $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    if (!in_array($file['type'], $allowed_types)) {
        $_SESSION['error'] = "Invalid file type. Only JPG, PNG, GIF, and WEBP are allowed.";
        header("Location: ../profile.php");
        exit();
    }
    
    $upload_dir = '../../../../assets/uploads/profile_images/';
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }
    
    // Generate unique filename
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = 'profile_' . $user_id . '_' . time() . '.' . $extension;
    $destination = $upload_dir . $filename;
    
    if (move_uploaded_file($file['tmp_name'], $destination)) {
        // Fetch old image to delete it
        $stmt = $mysqli->prepare("SELECT profile_image FROM users WHERE id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $old_image = $stmt->get_result()->fetch_assoc()['profile_image'];
        
        if (!empty($old_image) && file_exists($upload_dir . $old_image)) {
            unlink($upload_dir . $old_image);
        }
        
        // Update database
        $update_stmt = $mysqli->prepare("UPDATE users SET profile_image = ? WHERE id = ?");
        $update_stmt->bind_param("si", $filename, $user_id);
        
        if ($update_stmt->execute()) {
            $_SESSION['success'] = "Profile image updated successfully.";
        } else {
            $_SESSION['error'] = "Database update failed: " . $mysqli->error;
        }
    } else {
        $_SESSION['error'] = "Failed to move uploaded file.";
    }
}

header("Location: ../profile.php");
exit();
?>
