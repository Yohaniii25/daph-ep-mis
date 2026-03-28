<?php
session_start();
require_once '../../../../config/db_connect.php';

// Security Check
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_SESSION['range_id'])) {
    header("Location: ../office_details.php");
    exit();
}

$range_id = $_SESSION['range_id'];
$type     = $_POST['asset_type'] ?? '';
$name     = $_POST['display_name'] ?? '';
$desc     = $_POST['description'] ?? '';

try {
    if ($type === 'immovable') {
        // Handle Land/Buildings
        $location = $_POST['location'] ?? 'N/A';
        $extent   = $_POST['extent'] ?? 'N/A';

        $stmt = $mysqli->prepare("INSERT INTO assets_immovable (range_id, asset_name, description, location, extent) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("issss", $range_id, $name, $desc, $location, $extent);

    } elseif ($type === 'movable') {
        // Handle Vehicles/Equipment
        $cat    = $_POST['category'] ?? 'Equipment';
        $cond   = $_POST['condition'] ?? 'Good';
        $serial = $_POST['serial_no'] ?? 'N/A';

        $stmt = $mysqli->prepare("INSERT INTO assets_movable (range_id, asset_category, item_name, serial_no, `condition`) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("issss", $range_id, $cat, $name, $serial, $cond);
    } else {
        throw new Exception("Invalid Asset Type selected.");
    }

    if ($stmt->execute()) {
        $_SESSION['msg'] = "Asset successfully registered.";
        $_SESSION['msg_type'] = "success";
    } else {
        throw new Exception("Database error: " . $stmt->error);
    }
    $stmt->close();

} catch (Exception $e) {
    $_SESSION['msg'] = "Error: " . $e->getMessage();
    $_SESSION['msg_type'] = "danger";
}

header("Location: ../office_details.php");
exit();