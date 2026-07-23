<?php
// pages/modules/farm/processors/update_annex_cell.php
session_start();
require_once '../../../../config/db_connect.php';

header('Content-Type: application/json');

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'farms_dd') {
    echo json_encode(['success' => false, 'message' => 'Access denied']);
    exit();
}

$user_id = $_SESSION['user_id'] ?? 1;

$target_type = $_POST['target_type'] ?? '';
$date_str = $mysqli->real_escape_string($_POST['date'] ?? '');

if (empty($date_str)) {
    echo json_encode(['success' => false, 'message' => 'Missing date']);
    exit();
}

if ($target_type === 'cage_entry') {
    $cage_id = intval($_POST['cage_id'] ?? 0);
    $row_type = $_POST['row_type'] ?? ''; // Hat, T/E, C/E
    $field_type = $_POST['field_type'] ?? ''; // no, kg
    $val = floatval($_POST['val'] ?? 0);

    if ($cage_id <= 0 || !in_array($row_type, ['Hat', 'T/E', 'C/E']) || !in_array($field_type, ['no', 'kg'])) {
        echo json_encode(['success' => false, 'message' => 'Invalid parameters']);
        exit();
    }

    // Map to table column name
    $col_name = '';
    if ($row_type === 'Hat') {
        $col_name = ($field_type === 'no') ? 'hatchable_eggs' : 'hatchable_eggs_kg';
    } elseif ($row_type === 'T/E') {
        $col_name = ($field_type === 'no') ? 'table_eggs' : 'table_eggs_kg';
    } elseif ($row_type === 'C/E') {
        $col_name = ($field_type === 'no') ? 'cracked_eggs' : 'cracked_eggs_kg';
    }

    // Check if record exists for this date and cage_id
    $chk_sql = "SELECT dep.id, dep.batch_id, dep.hatchable_eggs, dep.hatchable_eggs_kg, dep.table_eggs, dep.table_eggs_kg, dep.cracked_eggs, dep.cracked_eggs_kg 
               FROM daily_egg_production dep
               JOIN vaccine_batches b ON dep.batch_id = b.id
               WHERE dep.collection_date = ? AND dep.cage_id = ? AND b.user_id = ? LIMIT 1";
    $stmt = $mysqli->prepare($chk_sql);
    $stmt->bind_param("sii", $date_str, $cage_id, $user_id);
    $stmt->execute();
    $res = $stmt->get_result();
    
    if ($row = $res->fetch_assoc()) {
        $rec_id = $row['id'];
        $stmt->close();

        // Update single column
        $upd_sql = "UPDATE daily_egg_production SET `$col_name` = ? WHERE id = ?";
        $upd_stmt = $mysqli->prepare($upd_sql);
        if ($field_type === 'no') {
            $val_int = intval($val);
            $upd_stmt->bind_param("ii", $val_int, $rec_id);
        } else {
            $upd_stmt->bind_param("di", $val, $rec_id);
        }
        $upd_stmt->execute();
        $upd_stmt->close();

        // Recalculate totals for this record
        $recalc_sql = "UPDATE daily_egg_production 
                       SET total_eggs = hatchable_eggs + table_eggs + cracked_eggs,
                           total_eggs_kg = round(hatchable_eggs_kg + table_eggs_kg + cracked_eggs_kg, 2)
                       WHERE id = ?";
        $recalc_stmt = $mysqli->prepare($recalc_sql);
        $recalc_stmt->bind_param("i", $rec_id);
        $recalc_stmt->execute();
        $recalc_stmt->close();

        echo json_encode(['success' => true, 'message' => 'Cell updated']);
        exit();
    } else {
        $stmt->close();
        // Record does not exist: find user's latest batch or create record
        $batch_stmt = $mysqli->prepare("SELECT id FROM vaccine_batches WHERE user_id = ? ORDER BY id DESC LIMIT 1");
        $batch_stmt->bind_param("i", $user_id);
        $batch_stmt->execute();
        $batch_res = $batch_stmt->get_result();
        $batch_row = $batch_res->fetch_assoc();
        $batch_stmt->close();

        if (!$batch_row) {
            echo json_encode(['success' => false, 'message' => 'No active batch found for user. Please add a batch first.']);
            exit();
        }

        $batch_id = $batch_row['id'];

        $ins_sql = "INSERT INTO daily_egg_production (batch_id, cage_id, collection_date, `$col_name`) VALUES (?, ?, ?, ?)";
        $ins_stmt = $mysqli->prepare($ins_sql);
        if ($field_type === 'no') {
            $val_int = intval($val);
            $ins_stmt->bind_param("iisi", $batch_id, $cage_id, $date_str, $val_int);
        } else {
            $ins_stmt->bind_param("iisd", $batch_id, $cage_id, $date_str, $val);
        }
        
        if ($ins_stmt->execute()) {
            $new_id = $ins_stmt->insert_id;
            $ins_stmt->close();

            // Recalculate totals
            $recalc_sql = "UPDATE daily_egg_production 
                           SET total_eggs = hatchable_eggs + table_eggs + cracked_eggs,
                               total_eggs_kg = round(hatchable_eggs_kg + table_eggs_kg + cracked_eggs_kg, 2)
                           WHERE id = ?";
            $recalc_stmt = $mysqli->prepare($recalc_sql);
            $recalc_stmt->bind_param("i", $new_id);
            $recalc_stmt->execute();
            $recalc_stmt->close();

            echo json_encode(['success' => true, 'message' => 'Record created & cell updated']);
            exit();
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to create record: ' . $ins_stmt->error]);
            exit();
        }
    }

} elseif ($target_type === 'sales_returns') {
    $field_name = $_POST['field_name'] ?? '';
    $val = floatval($_POST['val'] ?? 0);

    $allowed_fields = ['hatchery_return_no', 'hatchery_return_kg', 'total_sales_no', 'total_sales_kg'];
    if (!in_array($field_name, $allowed_fields)) {
        echo json_encode(['success' => false, 'message' => 'Invalid field']);
        exit();
    }

    $is_int = strpos($field_name, '_no') !== false;

    // Upsert
    $sql = "INSERT INTO daily_egg_sales_returns (record_date, `$field_name`) VALUES (?, ?)
            ON DUPLICATE KEY UPDATE `$field_name` = VALUES(`$field_name`)";
    $stmt = $mysqli->prepare($sql);
    if ($is_int) {
        $val_int = intval($val);
        $stmt->bind_param("si", $date_str, $val_int);
    } else {
        $stmt->bind_param("sd", $date_str, $val);
    }

    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Sales/Returns updated']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to update sales/returns']);
    }
    $stmt->close();
    exit();
}

echo json_encode(['success' => false, 'message' => 'Unknown action']);
