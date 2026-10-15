<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../../../../config/db_connect.php';

/** @var mysqli $mysqli */
global $mysqli;

$allowed_roles = [
    'veterinary_surgeon',
    'government_veterinary_surgeon',
    'sms',
    'district_dd',
    'deputy_director_district',
    'administrator',
    'provincial_director',
    'deputy_director_hq_1',
    'deputy_director_hq_2',
    'admin'
];

if (!isset($_SESSION['logged_in']) || !in_array($_SESSION['role'] ?? '', $allowed_roles, true)) {
    if (isset($_REQUEST['ajax'])) {
        header('Content-Type: application/json; charset=UTF-8');
        echo json_encode(['success' => false, 'message' => 'Unauthorized access clearance profile.']);
        exit();
    }
    die("Access denied: Invalid authentication clearance profile.");
}

$user_id = (int)($_SESSION['user_id'] ?? 0);
$session_range_id = (int)($_SESSION['range_id'] ?? 0);
$action = trim($_POST['action'] ?? $_GET['action'] ?? '');

$range_id = (int)($_REQUEST['range_id'] ?? $session_range_id ?: 1);

// Query to get district_id from veterinary_ranges
$district_id = 1;
if (!empty($range_id)) {
    $r_query = $mysqli->prepare("SELECT district_id FROM veterinary_ranges WHERE id = ?");
    if ($r_query) {
        $r_query->bind_param("i", $range_id);
        $r_query->execute();
        $r_result = $r_query->get_result();
        if ($r_row = $r_result->fetch_assoc()) {
            $district_id = (int)$r_row['district_id'];
        }
        $r_query->close();
    }
}

/**
 * Helper to compute automated monthly metrics from live registrations
 */
function computeMonthlyMetrics($mysqli, $range_id, $report_year, $report_month) {
    // 1. Opening Balance
    $opening_balance = 0;
    if ($report_month === 1) {
        $prev_y = $report_year - 1;
        $seed_q = $mysqli->prepare("SELECT closing_balance FROM cattle_voucher_usage WHERE range_id = ? AND report_year = ? AND report_month = 12 LIMIT 1");
        if ($seed_q) {
            $seed_q->bind_param("ii", $range_id, $prev_y);
            $seed_q->execute();
            $s_res = $seed_q->get_result();
            if ($sr = $s_res->fetch_assoc()) {
                $opening_balance = (int)$sr['closing_balance'];
            }
            $seed_q->close();
        }
    } else {
        $prev_m = $report_month - 1;
        $prev_q = $mysqli->prepare("SELECT closing_balance FROM cattle_voucher_usage WHERE range_id = ? AND report_year = ? AND report_month = ? LIMIT 1");
        if ($prev_q) {
            $prev_q->bind_param("iii", $range_id, $report_year, $prev_m);
            $prev_q->execute();
            $p_res = $prev_q->get_result();
            if ($pr = $p_res->fetch_assoc()) {
                $opening_balance = (int)$pr['closing_balance'];
            }
            $prev_q->close();
        }
    }

    // 2. Used Vouchers Count (Live from animal_identifications)
    $used_qty = 0;
    $u_stmt = $mysqli->prepare("
        SELECT COUNT(*) AS total_used 
        FROM animal_identifications 
        WHERE (range_id = ? OR range_id IS NULL OR range_id = 0)
          AND YEAR(identification_date) = ? AND MONTH(identification_date) = ? 
          AND voucher_serial_no IS NOT NULL AND voucher_serial_no != ''
    ");
    if ($u_stmt) {
        $u_stmt->bind_param("iii", $range_id, $report_year, $report_month);
        $u_stmt->execute();
        $used_qty = (int)($u_stmt->get_result()->fetch_assoc()['total_used'] ?? 0);
        $u_stmt->close();
    }

    // 3. Damaged Tags Count (Live from animal_identifications)
    $damaged_qty = 0;
    $d_stmt = $mysqli->prepare("
        SELECT COUNT(*) AS total_damaged 
        FROM animal_identifications 
        WHERE (range_id = ? OR range_id IS NULL OR range_id = 0)
          AND YEAR(identification_date) = ? AND MONTH(identification_date) = ? 
          AND present_status = 'Tag Damaged / Removed'
    ");
    if ($d_stmt) {
        $d_stmt->bind_param("iii", $range_id, $report_year, $report_month);
        $d_stmt->execute();
        $damaged_qty = (int)($d_stmt->get_result()->fetch_assoc()['total_damaged'] ?? 0);
        $d_stmt->close();
    }

    // 4. Distributed serial blocks allocated in this month
    $distributed_blocks = [];
    $dist_stmt = $mysqli->prepare("
        SELECT farmer_name, farm_reg_no, prefix, start_serial, end_serial, total_allocated, distribution_date 
        FROM cattle_voucher_distributions 
        WHERE range_id = ? AND YEAR(distribution_date) = ? AND MONTH(distribution_date) = ?
        ORDER BY id ASC
    ");
    if ($dist_stmt) {
        $dist_stmt->bind_param("iii", $range_id, $report_year, $report_month);
        $dist_stmt->execute();
        $dist_res = $dist_stmt->get_result();
        while ($drow = $dist_res->fetch_assoc()) {
            $prefix = $drow['prefix'] ?: 'A-';
            $p_start = $drow['start_serial'] >= 100000 ? $drow['start_serial'] : str_pad((string)$drow['start_serial'], 6, '0', STR_PAD_LEFT);
            $p_end = $drow['end_serial'] >= 100000 ? $drow['end_serial'] : str_pad((string)$drow['end_serial'], 6, '0', STR_PAD_LEFT);
            $distributed_blocks[] = [
                'farmer_name' => $drow['farmer_name'],
                'farm_reg_no' => $drow['farm_reg_no'],
                'range_label' => "{$prefix}{$p_start} to {$prefix}{$p_end}",
                'total' => (int)$drow['total_allocated'],
                'date' => $drow['distribution_date']
            ];
        }
        $dist_stmt->close();
    }

    return [
        'opening_balance' => $opening_balance,
        'used_qty' => $used_qty,
        'damaged_qty' => $damaged_qty,
        'distributed_blocks' => $distributed_blocks
    ];
}

// -------------------------------------------------------------------------
// ACTION: GET MONTH AUTO METRICS (AJAX)
// -------------------------------------------------------------------------
if ($action === 'get_month_auto_metrics') {
    header('Content-Type: application/json; charset=UTF-8');
    $report_year  = (int)($_GET['year'] ?? date('Y'));
    $report_month = (int)($_GET['month'] ?? date('n'));

    // Check if record already exists to get current received_qty
    $existing_received = 0;
    $existing_id = 0;
    $ex_q = $mysqli->prepare("SELECT id, received_qty, opening_balance FROM cattle_voucher_usage WHERE range_id = ? AND report_year = ? AND report_month = ? LIMIT 1");
    if ($ex_q) {
        $ex_q->bind_param("iii", $range_id, $report_year, $report_month);
        $ex_q->execute();
        $ex_res = $ex_q->get_result();
        if ($ex_row = $ex_res->fetch_assoc()) {
            $existing_id = (int)$ex_row['id'];
            $existing_received = (int)$ex_row['received_qty'];
        }
        $ex_q->close();
    }

    $auto = computeMonthlyMetrics($mysqli, $range_id, $report_year, $report_month);
    $ending = ($auto['opening_balance'] + $existing_received) - ($auto['used_qty'] + $auto['damaged_qty']);

    echo json_encode([
        'success' => true,
        'id' => $existing_id,
        'opening_balance' => $auto['opening_balance'],
        'received_qty' => $existing_received,
        'used_qty' => $auto['used_qty'],
        'damaged_qty' => $auto['damaged_qty'],
        'spoilt_qty' => 0,
        'transferred_qty' => 0,
        'ending_balance' => $ending,
        'distributed_blocks' => $auto['distributed_blocks']
    ]);
    exit();
}

$redirect_to = trim($_POST['redirect_to'] ?? $_GET['redirect_to'] ?? '../cattle_voucher.php');
if (empty($redirect_to)) {
    $redirect_to = '../cattle_voucher.php';
}

// -------------------------------------------------------------------------
// POST: SAVE / UPDATE CATTLE VOUCHER MONTHLY RETURN
// -------------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id              = (int)($_POST['id'] ?? 0);
    $report_year     = (int)($_POST['report_year'] ?? date('Y'));
    $report_month    = (int)($_POST['report_month'] ?? date('n'));
    
    // Strict requirement: User data entry is RESTRICTED to received_qty!
    $received_qty    = (int)($_POST['received_qty'] ?? 0);
    if ($received_qty < 0) {
        $received_qty = 0;
    }

    // Auto-compute all other metrics from live database records
    $auto = computeMonthlyMetrics($mysqli, $range_id, $report_year, $report_month);
    $opening_balance = $auto['opening_balance'];
    $used_qty        = $auto['used_qty'];
    $damaged_qty     = $auto['damaged_qty'];
    $spoilt_qty      = 0;
    $transferred_qty = 0;

    // Projected Ending Balance
    $closing_balance = ($opening_balance + $received_qty) - ($used_qty + $spoilt_qty + $damaged_qty + $transferred_qty);

    $is_ajax = isset($_POST['ajax']) || (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest');
    $separator = (strpos($redirect_to, '?') !== false) ? '&' : '?';

    if (empty($report_year) || empty($report_month)) {
        if ($is_ajax) {
            header('Content-Type: application/json; charset=UTF-8');
            echo json_encode(['success' => false, 'message' => 'Missing report year or month.']);
            exit();
        }
        header("Location: {$redirect_to}{$separator}status=error&msg=Missing+required+fields");
        exit();
    }

    // Check if a record already exists for this range/year/month
    $check_stmt = $mysqli->prepare("SELECT id FROM `cattle_voucher_usage` WHERE range_id = ? AND report_year = ? AND report_month = ? LIMIT 1");
    if ($check_stmt) {
        $check_stmt->bind_param("iii", $range_id, $report_year, $report_month);
        $check_stmt->execute();
        $c_res = $check_stmt->get_result();
        if ($c_row = $c_res->fetch_assoc()) {
            $id = (int)$c_row['id'];
            $action = 'update';
        }
        $check_stmt->close();
    }

    if ($action === 'create' || $id === 0) {
        $stmt = $mysqli->prepare("INSERT INTO `cattle_voucher_usage` 
            (district_id, range_id, report_year, report_month, opening_balance, received_qty, used_qty, spoilt_qty, damaged_qty, transferred_qty, closing_balance, created_by) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        if (!$stmt) {
            $err = $mysqli->error;
            if ($is_ajax) {
                header('Content-Type: application/json; charset=UTF-8');
                echo json_encode(['success' => false, 'message' => $err]);
                exit();
            }
            header("Location: {$redirect_to}{$separator}status=error&msg=" . urlencode($err));
            exit();
        }
        $stmt->bind_param("iiiiiiiiiiii", 
            $district_id, $range_id, $report_year, $report_month, 
            $opening_balance, $received_qty, $used_qty, $spoilt_qty, $damaged_qty, $transferred_qty, 
            $closing_balance, $user_id
        );
        
        if ($stmt->execute()) {
            $stmt->close();
            if ($is_ajax) {
                header('Content-Type: application/json; charset=UTF-8');
                echo json_encode(['success' => true, 'message' => 'Cattle Voucher Monthly Return successfully logged.']);
                exit();
            }
            header("Location: {$redirect_to}{$separator}status=success&msg=Voucher+Stock+Record+Created+Successfully");
            exit();
        } else {
            $err = $stmt->error;
            $stmt->close();
            if ($is_ajax) {
                header('Content-Type: application/json; charset=UTF-8');
                echo json_encode(['success' => false, 'message' => $err]);
                exit();
            }
            header("Location: {$redirect_to}{$separator}status=error&msg=" . urlencode($err));
            exit();
        }

    } else {
        // UPDATE
        $stmt = $mysqli->prepare("UPDATE `cattle_voucher_usage` SET 
            opening_balance = ?, 
            received_qty = ?, 
            used_qty = ?, 
            spoilt_qty = ?, 
            damaged_qty = ?, 
            transferred_qty = ?, 
            closing_balance = ?,
            updated_at = NOW() 
            WHERE id = ? AND range_id = ?");
        if (!$stmt) {
            $err = $mysqli->error;
            if ($is_ajax) {
                header('Content-Type: application/json; charset=UTF-8');
                echo json_encode(['success' => false, 'message' => $err]);
                exit();
            }
            header("Location: {$redirect_to}{$separator}status=error&msg=" . urlencode($err));
            exit();
        }
        $stmt->bind_param("iiiiiiiii", 
            $opening_balance, $received_qty, $used_qty, $spoilt_qty, $damaged_qty, $transferred_qty, 
            $closing_balance, $id, $range_id
        );
        
        if ($stmt->execute()) {
            $stmt->close();
            if ($is_ajax) {
                header('Content-Type: application/json; charset=UTF-8');
                echo json_encode(['success' => true, 'message' => 'Cattle Voucher Monthly Return updated successfully.']);
                exit();
            }
            header("Location: {$redirect_to}{$separator}status=success&msg=Voucher+Stock+Record+Updated+Successfully");
            exit();
        } else {
            $err = $stmt->error;
            $stmt->close();
            if ($is_ajax) {
                header('Content-Type: application/json; charset=UTF-8');
                echo json_encode(['success' => false, 'message' => $err]);
                exit();
            }
            header("Location: {$redirect_to}{$separator}status=error&msg=" . urlencode($err));
            exit();
        }
    }

} elseif ($_SERVER['REQUEST_METHOD'] === 'GET' && $action === 'delete') {
    $id = (int)($_GET['id'] ?? 0);
    $separator = (strpos($redirect_to, '?') !== false) ? '&' : '?';

    if ($id <= 0) {
        header("Location: {$redirect_to}{$separator}status=error&msg=Invalid+Record+ID");
        exit();
    }

    $stmt = $mysqli->prepare("DELETE FROM `cattle_voucher_usage` WHERE id = ? AND range_id = ?");
    if (!$stmt) {
        header("Location: {$redirect_to}{$separator}status=error&msg=" . urlencode($mysqli->error));
        exit();
    }
    $stmt->bind_param("ii", $id, $range_id);
    
    if ($stmt->execute()) {
        header("Location: {$redirect_to}{$separator}status=success&msg=Voucher+Stock+Record+Deleted");
        exit();
    } else {
        header("Location: {$redirect_to}{$separator}status=error&msg=" . urlencode($stmt->error));
        exit();
    }
}

header("Location: " . $redirect_to);
exit();
