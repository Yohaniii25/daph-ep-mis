<?php
session_start();

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'sms') {
    die("Access denied: Invalid profile context verification execution drop.");
}

require_once '../../../../config/db_connect.php';

$allowed_set_options = ['Cattle', 'Dairy Cows', 'Buffalo', 'Goats', 'Poultry', 'other'];
$action = $_POST['action'] ?? $_GET['action'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    $vaccine_name = isset($_POST['vaccine_name']) ? trim($_POST['vaccine_name']) : '';
    $description  = isset($_POST['description'])  ? trim($_POST['description'])  : '';
    $id           = isset($_POST['id'])           ? intval($_POST['id'])        : 0;
    
    $submitted_animals = $_POST['target_animal'] ?? [];
    $filtered_animals  = array_intersect($submitted_animals, $allowed_set_options);
    
    if (empty($filtered_animals) && !empty($_POST['target_animal_string'])) {
        $string_array = explode(',', $_POST['target_animal_string']);
        $filtered_animals = array_intersect(array_map('trim', $string_array), $allowed_set_options);
    }

    if (empty($vaccine_name)) {
        die("Validation Error: Drug formulation profile name field values cannot be left blank.");
    }
    if (empty($filtered_animals)) {
        die("Validation Error: You must specify at least one target livestock animal tracking property.");
    }

    $target_animal_string = implode(',', $filtered_animals);

    if ($action === 'create') {
        $insert_stmt = $mysqli->prepare("INSERT INTO `drug_types` (`vaccine_name`, `target_animal`, `description`) VALUES (?, ?, ?)");
        $insert_stmt->bind_param("sss", $vaccine_name, $target_animal_string, $description);
        
        if ($insert_stmt->execute()) {
            header("Location: ../drug_maintenance.php?status=success&msg=Drug+Type+Registered+Successfully");
            exit();
        } else {
            die("Database Write Fault: Error compiling row logging parameter updates: " . $mysqli->error);
        }
        
    } elseif ($action === 'update' && $id > 0) {
        $update_stmt = $mysqli->prepare("UPDATE `drug_types` SET `vaccine_name` = ?, `target_animal` = ?, `description` = ? WHERE `id` = ?");
        $update_stmt->bind_param("sssi", $vaccine_name, $target_animal_string, $description, $id);
        
        if ($update_stmt->execute()) {
            header("Location: ../drug_maintenance.php?status=success&msg=Drug+Configuration+Row+Modified");
            exit();
        } else {
            die("Database Update Fault: Error writing target modification configurations to row data layout: " . $mysqli->error);
        }
    }

} elseif ($_SERVER['REQUEST_METHOD'] === 'GET' && $action === 'delete') {
    
    $id = isset($_GET['id']) ? intval($_GET['id']) : 0;
    
    if ($id <= 0) {
        die("Invalid transaction lookup identifier index signature exception tracking parameter.");
    }
    
    $delete_stmt = $mysqli->prepare("DELETE FROM `drug_types` WHERE `id` = ?");
    $delete_stmt->bind_param("i", $id);
    
    if ($delete_stmt->execute()) {
        header("Location: ../drug_maintenance.php?status=success&msg=Drug+Registration+Row+Purged");
        exit();
    } else {
        die("Database Structural Execution Error: Could not clear entry configuration index target line row: " . $mysqli->error);
    }
}

header("Location: ../drug_maintenance.php");
exit();