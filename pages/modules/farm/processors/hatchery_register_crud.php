<?php
// pages/modules/farm/processors/hatchery_register_crud.php
session_start();
require_once '../../../../config/db_connect.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'farms_dd') {
    die("Access denied");
}

$action = $_POST['action'] ?? $_GET['action'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
    $record_date = trim($_POST['record_date'] ?? '');
    $cage_id = intval($_POST['cage_id'] ?? 0);
    $no_of_eggs_loaded = intval($_POST['no_of_eggs_loaded'] ?? 0);
    $date_of_candling = !empty($_POST['date_of_candling']) ? trim($_POST['date_of_candling']) : NULL;
    $discarded_during_candling = intval($_POST['discarded_during_candling'] ?? 0);
    $date_of_hatching = !empty($_POST['date_of_hatching']) ? trim($_POST['date_of_hatching']) : NULL;
    $no_of_hatched_eggs = intval($_POST['no_of_hatched_eggs'] ?? 0);
    $no_of_deaths = intval($_POST['no_of_deaths'] ?? 0);
    $no_of_good_chicks = intval($_POST['no_of_good_chicks'] ?? 0);
    $loaded_to_cage_id = intval($_POST['loaded_to_cage_id'] ?? 0);
    $remark = trim($_POST['remark'] ?? '');

    // Auto-calculate Hatching Percentage: (No. of Healthy Chicks / No. of Eggs Loaded) * 100
    $hatching_percentage = 0.00;
    if ($no_of_eggs_loaded > 0) {
        $hatching_percentage = round(($no_of_good_chicks / $no_of_eggs_loaded) * 100, 2);
    }

    if (empty($record_date) || $cage_id <= 0 || $loaded_to_cage_id <= 0) {
        header("Location: ../hatchery_register.php?status=error&msg=" . urlencode("Record date, incubator cage, and target cage are required."));
        exit();
    }

    if ($action === 'create') {
        $sql = "INSERT INTO hatchery_register (record_date, cage_id, no_of_eggs_loaded, date_of_candling, discarded_during_candling, date_of_hatching, no_of_hatched_eggs, no_of_deaths, no_of_good_chicks, hatching_percentage, loaded_to_cage_id, remark) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $mysqli->prepare($sql);
        $stmt->bind_param("siisisiiidis", $record_date, $cage_id, $no_of_eggs_loaded, $date_of_candling, $discarded_during_candling, $date_of_hatching, $no_of_hatched_eggs, $no_of_deaths, $no_of_good_chicks, $hatching_percentage, $loaded_to_cage_id, $remark);

        if ($stmt->execute()) {
            header("Location: ../hatchery_register.php?status=success&msg=" . urlencode("Hatchery record added successfully."));
        } else {
            header("Location: ../hatchery_register.php?status=error&msg=" . urlencode("Failed to add hatchery record: " . $stmt->error));
        }
        $stmt->close();
    } elseif ($action === 'update') {
        if ($id <= 0) {
            header("Location: ../hatchery_register.php?status=error&msg=" . urlencode("Invalid record ID."));
            exit();
        }

        $sql = "UPDATE hatchery_register SET record_date = ?, cage_id = ?, no_of_eggs_loaded = ?, date_of_candling = ?, discarded_during_candling = ?, date_of_hatching = ?, no_of_hatched_eggs = ?, no_of_deaths = ?, no_of_good_chicks = ?, hatching_percentage = ?, loaded_to_cage_id = ?, remark = ? WHERE id = ?";
        $stmt = $mysqli->prepare($sql);
        $stmt->bind_param("siisisiiidisi", $record_date, $cage_id, $no_of_eggs_loaded, $date_of_candling, $discarded_during_candling, $date_of_hatching, $no_of_hatched_eggs, $no_of_deaths, $no_of_good_chicks, $hatching_percentage, $loaded_to_cage_id, $remark, $id);

        if ($stmt->execute()) {
            header("Location: ../hatchery_register.php?status=success&msg=" . urlencode("Hatchery record updated successfully."));
        } else {
            header("Location: ../hatchery_register.php?status=error&msg=" . urlencode("Failed to update hatchery record: " . $stmt->error));
        }
        $stmt->close();
    }
    $mysqli->close();
} elseif ($_SERVER['REQUEST_METHOD'] === 'GET' && $action === 'delete') {
    $id = intval($_GET['id'] ?? 0);
    if ($id <= 0) {
        header("Location: ../hatchery_register.php?status=error&msg=" . urlencode("Invalid record ID."));
        exit();
    }

    $stmt = $mysqli->prepare("DELETE FROM hatchery_register WHERE id = ?");
    $stmt->bind_param("i", $id);

    if ($stmt->execute()) {
        header("Location: ../hatchery_register.php?status=success&msg=" . urlencode("Hatchery record deleted successfully."));
    } else {
        header("Location: ../hatchery_register.php?status=error&msg=" . urlencode("Failed to delete hatchery record: " . $stmt->error));
    }
    $stmt->close();
    $mysqli->close();
} else {
    header("Location: ../hatchery_register.php");
    exit();
}
