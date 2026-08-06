<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/db_connect.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit();
}

$user_id = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['profile_image'])) {
    $file = $_FILES['profile_image'];
    
    if ($file['error'] !== UPLOAD_ERR_OK) {
        $_SESSION['error'] = "File upload failed. Error code: " . $file['error'];
        header("Location: ../pages/profile.php");
        exit();
    }
    
    $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    if (!in_array($file['type'], $allowed_types)) {
        $_SESSION['error'] = "Invalid file format. Allowed formats: JPG, PNG, GIF, WEBP.";
        header("Location: ../pages/profile.php");
        exit();
    }
    
    // Maximum 5MB file size
    if ($file['size'] > 5 * 1024 * 1024) {
        $_SESSION['error'] = "File is too large. Maximum allowed size is 5MB.";
        header("Location: ../pages/profile.php");
        exit();
    }
    
    $upload_dir = __DIR__ . '/../assets/uploads/profile_images/';
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }
    
    $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $filename = 'profile_' . $user_id . '_' . time() . '.' . $extension;
    $destination = $upload_dir . $filename;
    
    if (move_uploaded_file($file['tmp_name'], $destination)) {
        // Retrieve existing image to delete if stored
        $stmt = $mysqli->prepare("SELECT profile_image FROM users WHERE id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $res = $stmt->get_result();
        if ($res && $res->num_rows > 0) {
            $old_img = $res->fetch_assoc()['profile_image'];
            if (!empty($old_img) && file_exists($upload_dir . $old_img)) {
                unlink($upload_dir . $old_img);
            }
        }
        
        // Update user record
        $update_stmt = $mysqli->prepare("UPDATE users SET profile_image = ? WHERE id = ?");
        $update_stmt->bind_param("si", $filename, $user_id);
        if ($update_stmt->execute()) {
            $_SESSION['success'] = "Profile picture updated successfully!";
        } else {
            $_SESSION['error'] = "Database update error: " . $mysqli->error;
        }
    } else {
        $_SESSION['error'] = "Failed to save uploaded picture.";
    }
}

header("Location: ../pages/profile.php");
exit();
