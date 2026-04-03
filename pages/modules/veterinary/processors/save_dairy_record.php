<?php
session_start();
require_once '../../../../config/db_connect.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $range_id = $_SESSION['range_id'];
    $user_id  = $_SESSION['user_id'];
    
    $date     = $_POST['collection_date'];
    $farmer   = mysqli_real_escape_string($mysqli, $_POST['farmer_reg_no']);
    $qty      = $_POST['milk_quantity'];
    $price    = $_POST['price_per_liter'];
    $fat      = $_POST['fat_per'] ?: 0;
    $snf      = $_POST['snf_per'] ?: 0;

    $sql = "INSERT INTO dairy_hub_records 
            (range_id, collection_date, farmer_reg_no, milk_quantity_liters, fat_percentage, snf_percentage, price_per_liter, created_by) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
    
    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param("issddddi", $range_id, $date, $farmer, $qty, $fat, $snf, $price, $user_id);

    if ($stmt->execute()) {
        $_SESSION['success'] = "Milk collection for $farmer recorded!";
    } else {
        $_SESSION['error'] = "Error saving record.";
    }

    header("Location: ../dairy_hub.php");
    exit();
}