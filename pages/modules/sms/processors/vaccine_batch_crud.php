<?php
session_start();
require_once '../../../../config/db_connect.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'sms') {
    die("Access denied");
}

$action = $_POST['action'] ?? $_GET['action'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    $batch_number = trim($mysqli->real_escape_string($_POST['batch_number']));
    $manufacturer = trim($mysqli->real_escape_string($_POST['manufacturer']));
    $expiry_date  = $mysqli->real_escape_string($_POST['expiry_date']);

    // -------------------------------------------------------------
    // ACTION: INITIAL CREATE REGISTRATION
    // -------------------------------------------------------------
    if ($action === 'create') {
        $vaccine_type_id = intval($_POST['vaccine_type_id']);
        $initial_doses   = intval($_POST['initial_allocated_doses']);

        // Check for duplicate batch codes to prevent database constraint errors
        $check = $mysqli->prepare("SELECT id FROM vaccine_batches WHERE batch_number = ?");
        $check->bind_param("s", $batch_number);
        $check->execute();
        $check->store_result();
        if ($check->num_rows > 0) {
            $check->close();
            header("Location: ../batches.php?status=error&msg=" . urlencode("Batch reference code already exists inside database indices."));
            exit;
        }
        $check->close();

        // 1. Insert Core Batch Identity Record Row
        $stmt = $mysqli->prepare("INSERT INTO vaccine_batches (vaccine_type_id, batch_number, manufacturer, initial_allocated_doses, expiry_date) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("issis", $vaccine_type_id, $batch_number, $manufacturer, $initial_doses, $expiry_date);
        
        if ($stmt->execute()) {
            $new_batch_id = $mysqli->insert_id;
            $stmt->close();

            // 2. Insert Matching Seed Entry Transaction Row inside the Ledger
            $ledger_stmt = $mysqli->prepare("INSERT INTO vaccine_stock_ledger (vaccine_batch_id, transaction_type, quantity, notes) VALUES (?, 'INITIAL', ?, 'Initial system deployment allocation balance')");
            $ledger_stmt->bind_param("ii", $new_batch_id, $initial_doses);
            $ledger_stmt->execute();
            $ledger_stmt->close();

            header("Location: ../batches.php?status=success");
            exit;
        } else {
            header("Location: ../batches.php?status=error");
            exit;
        }
    } 
    
    // -------------------------------------------------------------
    // ACTION: INLINE RECORD UPDATE & TRANSACTION INJECTION
    // -------------------------------------------------------------
    elseif ($action === 'update') {
        $id = intval($_POST['id']);
        $mid_month_arrival = intval($_POST['mid_month_arrival'] ?? 0);
        $new_damaged       = intval($_POST['new_damaged'] ?? 0);

        // 1. Update changeable baseline master identities
        $stmt = $mysqli->prepare("UPDATE vaccine_batches SET batch_number = ?, manufacturer = ?, expiry_date = ? WHERE id = ?");
        $stmt->bind_param("sssi", $batch_number, $manufacturer, $expiry_date, $id);
        $stmt->execute();
        $stmt->close();

        // 2. Process Mid-Month Stock Receipts if any arrived
        if ($mid_month_arrival > 0) {
            $arr_stmt = $mysqli->prepare("INSERT INTO vaccine_stock_ledger (vaccine_batch_id, transaction_type, quantity, notes) VALUES (?, 'MID_MONTH_RECEIVE', ?, 'Mid-month supply allocation received')");
            $arr_stmt->bind_param("ii", $id, $mid_month_arrival);
            $arr_stmt->execute();
            $arr_stmt->close();
        }

        // 3. Process Damage Logs if any occurred
        if ($new_damaged > 0) {
            // Negative integer inversion to represent an inventory drop transaction entry
            $negative_dmg = $new_damaged * -1;
            
            $dmg_stmt = $mysqli->prepare("INSERT INTO vaccine_stock_ledger (vaccine_batch_id, transaction_type, quantity, notes) VALUES (?, 'DAMAGE', ?, 'Inventory stock breakage/cold-chain failure report')");
            $dmg_stmt->bind_param("ii", $id, $negative_dmg);
            $dmg_stmt->execute();
            $dmg_stmt->close();
        }

        header("Location: ../batches.php?status=success");
        exit;
    }
} 

// -----------------------------------------------------------------
// ACTION: REMOVE RECORD SECURELY VIA GET HANDSHAKE
// -----------------------------------------------------------------
elseif ($_SERVER['REQUEST_METHOD'] === 'GET' && $action === 'delete') {
    $id = intval($_GET['id']);
    
    // Cascading execution handles cascading ledger drops automatically based on constraints
    $stmt = $mysqli->prepare("DELETE FROM vaccine_batches WHERE id = ?");
    $stmt->bind_param("i", $id);
    
    $stmt->execute() ? header("Location: ../vaccination_batches.php?status=success") : header("Location: ../vaccination_batches.php?status=error");
    $stmt->close();
}

$mysqli->close();
?>