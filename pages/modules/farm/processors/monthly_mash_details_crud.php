<?php
// pages/modules/farm/processors/monthly_mash_details_crud.php
session_start();
require_once '../../../../config/db_connect.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'farms_dd') {
    die("Access denied");
}

$action = $_POST['action'] ?? $_GET['action'] ?? '';

// Handle AJAX Request: Auto-Fetch Opening Stock from Immediate Previous Date
if ($action === 'fetch_opening_stock') {
    header('Content-Type: application/json');

    $selected_date = trim($_POST['date'] ?? $_GET['date'] ?? $_POST['month'] ?? $_GET['month'] ?? date('Y-m-d'));
    
    // Ensure YYYY-MM-DD format
    if (strlen($selected_date) === 7) {
        $target_date = $selected_date . '-01';
    } else {
        $target_date = date('Y-m-d', strtotime($selected_date));
    }

    $date_label = date('d F Y', strtotime($target_date));
    $feed_types = ['Layer', 'Starter', 'Grower'];

    $updated_records       = [];
    $total_opening_stock   = 0;
    $total_received_stock  = 0;
    $total_consumption     = 0;
    $total_issued_other    = 0;
    $total_balance_stock   = 0;
    $last_prev_date_found  = '';

    foreach ($feed_types as $ft) {
        // 1. Fetch closing balance (balance_stock_kg) from immediate previous date strictly before target_date
        $stmt_prev = $mysqli->prepare("SELECT balance_stock_kg, record_month FROM monthly_mash_details WHERE record_month < ? AND feed_type = ? ORDER BY record_month DESC LIMIT 1");
        $stmt_prev->bind_param("ss", $target_date, $ft);
        $stmt_prev->execute();
        $prev_res = $stmt_prev->get_result();
        $prev_closing_stock = 0.00;
        $prev_date_found    = '';
        $found_prev         = false;

        if ($prev_res && $prev_res->num_rows > 0) {
            $row_p              = $prev_res->fetch_assoc();
            $prev_closing_stock = floatval($row_p['balance_stock_kg']);
            $prev_date_found    = $row_p['record_month'];
            $found_prev         = true;
            $last_prev_date_found = $prev_date_found;
        }
        $stmt_prev->close();

        // 2. Fetch target_date's existing record (if any)
        $stmt_chk = $mysqli->prepare("SELECT id, received_kg, issued_other_farm_kg, remarks FROM monthly_mash_details WHERE record_month = ? AND feed_type = ? LIMIT 1");
        $stmt_chk->bind_param("ss", $target_date, $ft);
        $stmt_chk->execute();
        $chk_res = $stmt_chk->get_result();

        // 3. Auto-calculate daily consumption from daily_feed_distribution log for target_date
        $stmt_sum = $mysqli->prepare("SELECT COALESCE(SUM(amount_distributed_kg), 0) AS total_consumed FROM daily_feed_distribution WHERE feed_type = ? AND distribution_date = ?");
        $stmt_sum->bind_param("ss", $ft, $target_date);
        $stmt_sum->execute();
        $auto_consumption = floatval($stmt_sum->get_result()->fetch_assoc()['total_consumed'] ?? 0);
        $stmt_sum->close();

        if ($chk_res && $chk_res->num_rows > 0) {
            $curr_row = $chk_res->fetch_assoc();
            $rec_id               = intval($curr_row['id']);
            $received_kg          = floatval($curr_row['received_kg']);
            $issued_other_farm_kg = floatval($curr_row['issued_other_farm_kg']);
            $remarks              = $curr_row['remarks'] ?? '';

            // Calculate balance stock: (opening + received) - (consumption + issued_other)
            $balance_stock_kg = ($prev_closing_stock + $received_kg) - ($auto_consumption + $issued_other_farm_kg);

            // Update database record with auto-fetched opening stock from previous date
            $stmt_upd = $mysqli->prepare("UPDATE monthly_mash_details SET opening_stock_kg = ?, consumption_kg = ?, balance_stock_kg = ? WHERE id = ?");
            $stmt_upd->bind_param("dddi", $prev_closing_stock, $auto_consumption, $balance_stock_kg, $rec_id);
            $stmt_upd->execute();
            $stmt_upd->close();
        } else {
            $rec_id               = 0;
            $received_kg          = 0.00;
            $issued_other_farm_kg = 0.00;
            $remarks              = '';
            $balance_stock_kg     = ($prev_closing_stock + $received_kg) - ($auto_consumption + $issued_other_farm_kg);

            $stmt_ins = $mysqli->prepare("INSERT INTO monthly_mash_details (record_month, feed_type, opening_stock_kg, received_kg, consumption_kg, issued_other_farm_kg, balance_stock_kg) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt_ins->bind_param("ssddddd", $target_date, $ft, $prev_closing_stock, $received_kg, $auto_consumption, $issued_other_farm_kg, $balance_stock_kg);
            $stmt_ins->execute();
            $rec_id = $stmt_ins->insert_id;
            $stmt_ins->close();
        }
        $stmt_chk->close();

        $updated_records[] = [
            'id'                   => $rec_id,
            'feed_type'            => $ft,
            'opening_stock_kg'     => $prev_closing_stock,
            'received_kg'          => $received_kg,
            'consumption_kg'       => $auto_consumption,
            'issued_other_farm_kg' => $issued_other_farm_kg,
            'balance_stock_kg'     => $balance_stock_kg,
            'remarks'              => $remarks,
            'found_previous'       => $found_prev,
            'prev_date'            => $prev_date_found
        ];

        $total_opening_stock  += $prev_closing_stock;
        $total_received_stock += $received_kg;
        $total_consumption    += $auto_consumption;
        $total_issued_other   += $issued_other_farm_kg;
        $total_balance_stock  += $balance_stock_kg;
    }

    $prev_date_label = !empty($last_prev_date_found) ? date('d F Y', strtotime($last_prev_date_found)) : 'Previous Date';

    echo json_encode([
        'status'               => 'success',
        'message'              => "Auto-fetched closing balance from $prev_date_label as opening stock for $date_label.",
        'date_label'           => $date_label,
        'prev_date_label'      => $prev_date_label,
        'selected_date'        => $target_date,
        'total_opening_stock'  => $total_opening_stock,
        'total_received_stock' => $total_received_stock,
        'total_consumption'    => $total_consumption,
        'total_issued_other'   => $total_issued_other,
        'total_balance_stock'  => $total_balance_stock,
        'records'              => $updated_records
    ]);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
    
    $opening_stock_kg     = floatval($_POST['opening_stock_kg'] ?? 0);
    $received_kg          = floatval($_POST['received_kg'] ?? 0);
    $issued_other_farm_kg = floatval($_POST['issued_other_farm_kg'] ?? 0);
    $remarks              = trim($_POST['remarks'] ?? '');

    if ($id <= 0) {
        header("Location: ../feed_management.php?tab=annex4&status=error&msg=" . urlencode("Invalid record ID."));
        exit();
    }

    // First fetch current record
    $stmt_get = $mysqli->prepare("SELECT record_month, feed_type, consumption_kg FROM monthly_mash_details WHERE id = ?");
    $stmt_get->bind_param("i", $id);
    $stmt_get->execute();
    $res = $stmt_get->get_result();
    $current = $res->fetch_assoc();
    $stmt_get->close();

    if (!$current) {
        header("Location: ../feed_management.php?tab=annex4&status=error&msg=" . urlencode("Record not found."));
        exit();
    }

    $target_date = $current['record_month'];
    $feed_type   = $current['feed_type'];

    // Auto-calculate daily consumption from daily_feed_distribution for target_date
    $stmt_sum = $mysqli->prepare("SELECT COALESCE(SUM(amount_distributed_kg), 0) AS total_consumed FROM daily_feed_distribution WHERE feed_type = ? AND distribution_date = ?");
    $stmt_sum->bind_param("ss", $feed_type, $target_date);
    $stmt_sum->execute();
    $sum_res = $stmt_sum->get_result()->fetch_assoc();
    $consumption_kg = floatval($sum_res['total_consumed'] ?? 0);
    $stmt_sum->close();

    // Auto-calculate balance formula: (opening_stock_kg + received_kg) - (consumption_kg + issued_other_farm_kg)
    $balance_stock_kg = ($opening_stock_kg + $received_kg) - ($consumption_kg + $issued_other_farm_kg);

    $sql = "UPDATE monthly_mash_details SET 
                opening_stock_kg = ?, received_kg = ?, consumption_kg = ?,
                issued_other_farm_kg = ?, balance_stock_kg = ?, remarks = ?
            WHERE id = ?";

    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param(
        "dddddsi",
        $opening_stock_kg, $received_kg, $consumption_kg,
        $issued_other_farm_kg, $balance_stock_kg, $remarks, $id
    );

    if ($stmt->execute()) {
        header("Location: ../feed_management.php?tab=annex4&date=" . urlencode($target_date) . "&status=success&msg=" . urlencode("Mash stock details updated successfully."));
    } else {
        header("Location: ../feed_management.php?tab=annex4&date=" . urlencode($target_date) . "&status=error&msg=" . urlencode("Failed to update record: " . $stmt->error));
    }
    $stmt->close();
    $mysqli->close();
} else {
    header("Location: ../feed_management.php?tab=annex4");
    exit();
}
