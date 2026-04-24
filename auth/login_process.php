<?php
session_start();
require_once dirname(__DIR__) . '/config/constants.php';
require_once dirname(__DIR__) . '/config/db_connect.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
    $password = $_POST['password'];

    $sql = "SELECT u.*, r.role_name 
            FROM users u 
            JOIN roles r ON u.role_id = r.role_id 
            WHERE u.email = ? LIMIT 1";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($user = $result->fetch_assoc()) {

        if ($user['is_active'] == 0) {
            header("Location: ../index.php?error=account_deactivated");
            exit();
        }

        if (password_verify($password, $user['password'])) {
            
            session_regenerate_id(true);

            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['user_nic'] = $user['nic'];
            $_SESSION['full_name'] = $user['full_name'];
            $_SESSION['user_role'] = $user['role_name'];
            $_SESSION['logged_in'] = true;

            $update_sql = "UPDATE users SET last_login = NOW() WHERE user_id = ?";
            $update_stmt = $conn->prepare($update_sql);
            $update_stmt->bind_param("i", $user['user_id']);
            $update_stmt->execute();

            header("Location: ../dashboard.php");
            exit();
            
        } else {

            header("Location: ../index.php?error=invalid_credentials");
            exit();
        }
    } else {

        header("Location: ../index.php?error=invalid_credentials");
        exit();
    }
} else {

    header("Location: ../index.php");
    exit();
}