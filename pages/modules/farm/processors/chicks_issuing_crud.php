<?php
// pages/modules/farm/processors/chicks_issuing_crud.php
session_start();
require_once '../../../../config/db_connect.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'farms_dd') {
    die("Access denied");
}

$action = $_POST['action'] ?? $_GET['action'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
    
    // Convert record_month (YYYY-MM) to YYYY-MM-01 format
    $month_input = trim($_POST['record_month'] ?? date('Y-m'));
    $record_month = date('Y-m-01', strtotime($month_input . '-01'));

    $batch_no = trim($_POST['batch_no'] ?? '');
    $no_of_eggs_hatched = intval($_POST['no_of_eggs_hatched'] ?? 0);
    $starting_balance_of_month = intval($_POST['starting_balance_of_month'] ?? 0);
    $deaths_before_sexing = intval($_POST['deaths_before_sexing'] ?? 0);
    $received = intval($_POST['received'] ?? 0);
    
    // Live chicks
    $live_chicks_pullets = intval($_POST['live_chicks_pullets'] ?? 0);
    $live_chicks_cockerels = intval($_POST['live_chicks_cockerels'] ?? 0);
    
    // Deaths sexing to
    $deaths_sexing_pullets = intval($_POST['deaths_sexing_pullets'] ?? 0);
    $deaths_sexing_cockerels = intval($_POST['deaths_sexing_cockerels'] ?? 0);
    $deaths_sexing_unsexed = intval($_POST['deaths_sexing_unsexed'] ?? 0);
    
    // Issue breakdown
    $issue_cockerels_pullets = intval($_POST['issue_cockerels_pullets'] ?? 0);
    $issue_day_old_unsex = intval($_POST['issue_day_old_unsex'] ?? 0);
    $issue_day_old_cockerel = intval($_POST['issue_day_old_cockerel'] ?? 0);
    $issue_month_old_unsexed = intval($_POST['issue_month_old_unsexed'] ?? 0);
    
    $remarks = trim($_POST['remarks'] ?? '');

    if (empty($batch_no)) {
        header("Location: ../chick_details.php?tab=issuing&status=error&msg=" . urlencode("Batch number is required."));
        exit();
    }

    if ($action === 'create') {
        $sql = "INSERT INTO chicks_issuing_details (
                    record_month, batch_no, no_of_eggs_hatched, starting_balance_of_month,
                    deaths_before_sexing, received, live_chicks_pullets, live_chicks_cockerels,
                    deaths_sexing_pullets, deaths_sexing_cockerels, deaths_sexing_unsexed,
                    issue_cockerels_pullets, issue_day_old_unsex, issue_day_old_cockerel,
                    issue_month_old_unsexed, remarks
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $mysqli->prepare($sql);
        $stmt->bind_param(
            "ssiiiiiiiiiiiiis",
            $record_month, $batch_no, $no_of_eggs_hatched, $starting_balance_of_month,
            $deaths_before_sexing, $received, $live_chicks_pullets, $live_chicks_cockerels,
            $deaths_sexing_pullets, $deaths_sexing_cockerels, $deaths_sexing_unsexed,
            $issue_cockerels_pullets, $issue_day_old_unsex, $issue_day_old_cockerel,
            $issue_month_old_unsexed, $remarks
        );

        if ($stmt->execute()) {
            header("Location: ../chick_details.php?tab=issuing&status=success&msg=" . urlencode("Chicks issuing summary record added successfully."));
        } else {
            header("Location: ../chick_details.php?tab=issuing&status=error&msg=" . urlencode("Failed to save record: " . $stmt->error));
        }
        $stmt->close();
    } elseif ($action === 'update') {
        if ($id <= 0) {
            header("Location: ../chick_details.php?tab=issuing&status=error&msg=" . urlencode("Invalid record ID."));
            exit();
        }

        $sql = "UPDATE chicks_issuing_details SET 
                    record_month = ?, batch_no = ?, no_of_eggs_hatched = ?, starting_balance_of_month = ?,
                    deaths_before_sexing = ?, received = ?, live_chicks_pullets = ?, live_chicks_cockerels = ?,
                    deaths_sexing_pullets = ?, deaths_sexing_cockerels = ?, deaths_sexing_unsexed = ?,
                    issue_cockerels_pullets = ?, issue_day_old_unsex = ?, issue_day_old_cockerel = ?,
                    issue_month_old_unsexed = ?, remarks = ?
                WHERE id = ?";

        $stmt = $mysqli->prepare($sql);
        $stmt->bind_param(
            "ssiiiiiiiiiiiiisi",
            $record_month, $batch_no, $no_of_eggs_hatched, $starting_balance_of_month,
            $deaths_before_sexing, $received, $live_chicks_pullets, $live_chicks_cockerels,
            $deaths_sexing_pullets, $deaths_sexing_cockerels, $deaths_sexing_unsexed,
            $issue_cockerels_pullets, $issue_day_old_unsex, $issue_day_old_cockerel,
            $issue_month_old_unsexed, $remarks, $id
        );

        if ($stmt->execute()) {
            header("Location: ../chick_details.php?tab=issuing&status=success&msg=" . urlencode("Chicks issuing summary record updated successfully."));
        } else {
            header("Location: ../chick_details.php?tab=issuing&status=error&msg=" . urlencode("Failed to update record: " . $stmt->error));
        }
        $stmt->close();
    }
    $mysqli->close();
} elseif ($_SERVER['REQUEST_METHOD'] === 'GET' && $action === 'delete') {
    $id = intval($_GET['id'] ?? 0);
    if ($id <= 0) {
        header("Location: ../chick_details.php?tab=issuing&status=error&msg=" . urlencode("Invalid record ID."));
        exit();
    }

    $stmt = $mysqli->prepare("DELETE FROM chicks_issuing_details WHERE id = ?");
    $stmt->bind_param("i", $id);

    if ($stmt->execute()) {
        header("Location: ../chick_details.php?tab=issuing&status=success&msg=" . urlencode("Record deleted successfully."));
    } else {
        header("Location: ../chick_details.php?tab=issuing&status=error&msg=" . urlencode("Failed to delete record: " . $stmt->error));
    }
    $stmt->close();
    $mysqli->close();
} else {
    header("Location: ../chick_details.php?tab=issuing");
    exit();
}
