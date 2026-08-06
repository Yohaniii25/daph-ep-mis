<?php
// pages/modules/farm/processors/produce_register_crud.php
session_start();
require_once '../../../../config/db_connect.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'farms_dd') {
    die("Access denied");
}

$user_id = $_SESSION['user_id'] ?? 1;
$action = $_POST['action'] ?? $_GET['action'] ?? '';

// Ensure tables exist & check missing columns
$mysqli->query("CREATE TABLE IF NOT EXISTS `farm_commodities` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `commodity_name` VARCHAR(255) NOT NULL,
  `unit_of_measure` VARCHAR(50) DEFAULT 'Kg',
  `description` TEXT DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

$mysqli->query("CREATE TABLE IF NOT EXISTS `farm_produce_register_annex6` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `user_id` INT(11) NOT NULL,
  `commodity_id` INT(11) NOT NULL,
  `record_date` DATE NOT NULL,
  `received_from` VARCHAR(255) DEFAULT NULL,
  `issued_to` VARCHAR(255) DEFAULT NULL,
  `plot_no` VARCHAR(100) DEFAULT NULL,
  `received_qty` DECIMAL(10,2) DEFAULT 0.00,
  `issued_qty` DECIMAL(10,2) DEFAULT 0.00,
  `opening_stock` DECIMAL(10,2) DEFAULT 0.00,
  `closing_stock` DECIMAL(10,2) DEFAULT 0.00,
  `quantity` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `disposal_method` VARCHAR(255) DEFAULT NULL,
  `unit_price` DECIMAL(10,2) DEFAULT 0.00,
  `full_sum_realized` DECIMAL(12,2) DEFAULT 0.00,
  `receipt_no_or_page` VARCHAR(255) DEFAULT NULL,
  `initials` VARCHAR(100) DEFAULT NULL,
  `remarks` TEXT DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP(),
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `commodity_id` (`commodity_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

// Dynamic column checks
$cols_res = $mysqli->query("SHOW COLUMNS FROM `farm_produce_register_annex6`");
$existing_cols = [];
if ($cols_res) {
    while ($c = $cols_res->fetch_assoc()) {
        $existing_cols[] = $c['Field'];
    }
}
if (!in_array('received_from', $existing_cols)) {
    $mysqli->query("ALTER TABLE `farm_produce_register_annex6` ADD COLUMN `received_from` VARCHAR(255) DEFAULT NULL AFTER `record_date`");
}
if (!in_array('issued_to', $existing_cols)) {
    $mysqli->query("ALTER TABLE `farm_produce_register_annex6` ADD COLUMN `issued_to` VARCHAR(255) DEFAULT NULL AFTER `received_from`");
}
if (!in_array('received_qty', $existing_cols)) {
    $mysqli->query("ALTER TABLE `farm_produce_register_annex6` ADD COLUMN `received_qty` DECIMAL(10,2) DEFAULT 0.00 AFTER `plot_no`");
}
if (!in_array('issued_qty', $existing_cols)) {
    $mysqli->query("ALTER TABLE `farm_produce_register_annex6` ADD COLUMN `issued_qty` DECIMAL(10,2) DEFAULT 0.00 AFTER `received_qty`");
}
if (!in_array('opening_stock', $existing_cols)) {
    $mysqli->query("ALTER TABLE `farm_produce_register_annex6` ADD COLUMN `opening_stock` DECIMAL(10,2) DEFAULT 0.00 AFTER `issued_qty`");
}
if (!in_array('closing_stock', $existing_cols)) {
    $mysqli->query("ALTER TABLE `farm_produce_register_annex6` ADD COLUMN `closing_stock` DECIMAL(10,2) DEFAULT 0.00 AFTER `opening_stock`");
}

// Function to recalculate opening & closing stock balances for a commodity
function recalculateProduceBalances($mysqli, $commodity_id) {
    $stmt = $mysqli->prepare("SELECT id, received_qty, issued_qty, quantity FROM farm_produce_register_annex6 WHERE commodity_id = ? ORDER BY record_date ASC, id ASC");
    $stmt->bind_param("i", $commodity_id);
    $stmt->execute();
    $res = $stmt->get_result();
    
    $running_balance = 0.00;
    while ($row = $res->fetch_assoc()) {
        $id = $row['id'];
        $rec = floatval($row['received_qty']);
        $iss = floatval($row['issued_qty']);
        
        // Backward compatibility fallback for legacy 'quantity' entries
        if ($rec == 0 && $iss == 0 && floatval($row['quantity']) > 0) {
            $rec = floatval($row['quantity']);
        }
        
        $opening = $running_balance;
        $closing = round($opening + $rec - $iss, 2);
        $running_balance = $closing;
        
        $upd = $mysqli->prepare("UPDATE farm_produce_register_annex6 SET received_qty = ?, issued_qty = ?, opening_stock = ?, closing_stock = ? WHERE id = ?");
        $upd->bind_param("ddddi", $rec, $iss, $opening, $closing, $id);
        $upd->execute();
        $upd->close();
    }
    $stmt->close();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($action === 'create_commodity') {
        $commodity_name = trim($_POST['commodity_name']);
        $unit = trim($_POST['unit_of_measure'] ?? 'Kg');
        $desc = trim($_POST['description'] ?? '');

        if (!empty($commodity_name)) {
            $stmt = $mysqli->prepare("INSERT INTO farm_commodities (commodity_name, unit_of_measure, description) VALUES (?, ?, ?)");
            $stmt->bind_param("sss", $commodity_name, $unit, $desc);
            if ($stmt->execute()) {
                $new_id = $stmt->insert_id;
                header("Location: ../production_details.php?commodity_id=" . $new_id . "&tab=manage&status=success&msg=" . urlencode("New commodity item added successfully."));
            } else {
                header("Location: ../production_details.php?tab=manage&status=error&msg=" . urlencode("Failed to add commodity item: " . $stmt->error));
            }
            $stmt->close();
        } else {
            header("Location: ../production_details.php?tab=manage&status=error&msg=" . urlencode("Commodity name is required."));
        }
        exit();
    }

    if ($action === 'update_commodity') {
        $id = intval($_POST['id']);
        $commodity_name = trim($_POST['commodity_name']);
        $unit = trim($_POST['unit_of_measure'] ?? 'Kg');
        $desc = trim($_POST['description'] ?? '');

        if (!empty($commodity_name) && $id > 0) {
            $stmt = $mysqli->prepare("UPDATE farm_commodities SET commodity_name = ?, unit_of_measure = ?, description = ? WHERE id = ?");
            $stmt->bind_param("sssi", $commodity_name, $unit, $desc, $id);
            if ($stmt->execute()) {
                header("Location: ../production_details.php?commodity_id=" . $id . "&tab=manage&status=success&msg=" . urlencode("Commodity item updated successfully."));
            } else {
                header("Location: ../production_details.php?commodity_id=" . $id . "&tab=manage&status=error&msg=" . urlencode("Failed to update commodity item: " . $stmt->error));
            }
            $stmt->close();
        } else {
            header("Location: ../production_details.php?tab=manage&status=error&msg=" . urlencode("Commodity name and ID are required."));
        }
        exit();
    }

    if ($action === 'create_receive_produce' || $action === 'create_issue_produce' || $action === 'create_produce') {
        $commodity_id = intval($_POST['commodity_id']);
        $record_date = $_POST['record_date'] ?? date('Y-m-d');
        $received_from = trim($_POST['received_from'] ?? '');
        $issued_to = trim($_POST['issued_to'] ?? '');
        $plot_no = trim($_POST['plot_no'] ?? '');
        $received_qty = floatval($_POST['received_qty'] ?? 0);
        $issued_qty = floatval($_POST['issued_qty'] ?? 0);
        $disposal_method = trim($_POST['disposal_method'] ?? 'Internal Harvest');
        $unit_price = floatval($_POST['unit_price'] ?? 0);
        $full_sum_realized = floatval($_POST['full_sum_realized'] ?? 0);
        $receipt_no_or_page = trim($_POST['receipt_no_or_page'] ?? '');
        $initials = trim($_POST['initials'] ?? ($_SESSION['username'] ?? 'User'));
        $remarks = trim($_POST['remarks'] ?? '');

        if ($action === 'create_receive_produce') {
            $issued_qty = 0.00;
            $quantity = $received_qty;
            if (empty($disposal_method)) $disposal_method = 'Harvest Intake';
        } elseif ($action === 'create_issue_produce') {
            $received_qty = 0.00;
            $quantity = $issued_qty;
            if ($full_sum_realized == 0 && $issued_qty > 0 && $unit_price > 0) {
                $full_sum_realized = round($issued_qty * $unit_price, 2);
            }
        } else {
            $quantity = max($received_qty, $issued_qty, floatval($_POST['quantity'] ?? 0));
        }

        $stmt = $mysqli->prepare("INSERT INTO farm_produce_register_annex6 (user_id, commodity_id, record_date, received_from, issued_to, plot_no, received_qty, issued_qty, quantity, disposal_method, unit_price, full_sum_realized, receipt_no_or_page, initials, remarks) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("iisssddddsddsss", $user_id, $commodity_id, $record_date, $received_from, $issued_to, $plot_no, $received_qty, $issued_qty, $quantity, $disposal_method, $unit_price, $full_sum_realized, $receipt_no_or_page, $initials, $remarks);

        if ($stmt->execute()) {
            recalculateProduceBalances($mysqli, $commodity_id);
            header("Location: ../production_details.php?commodity_id=" . $commodity_id . "&status=success&msg=" . urlencode("Produce transaction recorded successfully."));
        } else {
            header("Location: ../production_details.php?commodity_id=" . $commodity_id . "&status=error&msg=" . urlencode("Failed to record produce entry: " . $stmt->error));
        }
        $stmt->close();
        exit();
    }

    if ($action === 'update_produce') {
        $id = intval($_POST['id']);
        $commodity_id = intval($_POST['commodity_id']);
        $record_date = $_POST['record_date'];
        $received_from = trim($_POST['received_from'] ?? '');
        $issued_to = trim($_POST['issued_to'] ?? '');
        $plot_no = trim($_POST['plot_no'] ?? '');
        $received_qty = floatval($_POST['received_qty'] ?? 0);
        $issued_qty = floatval($_POST['issued_qty'] ?? 0);
        $quantity = max($received_qty, $issued_qty, floatval($_POST['quantity'] ?? 0));
        $disposal_method = trim($_POST['disposal_method'] ?? 'Harvest Intake');
        $unit_price = floatval($_POST['unit_price'] ?? 0);
        
        $full_sum_realized = floatval($_POST['full_sum_realized'] ?? 0);
        if ($full_sum_realized == 0 && $issued_qty > 0 && $unit_price > 0) {
            $full_sum_realized = round($issued_qty * $unit_price, 2);
        }

        $receipt_no_or_page = trim($_POST['receipt_no_or_page'] ?? '');
        $initials = trim($_POST['initials'] ?? '');
        $remarks = trim($_POST['remarks'] ?? '');

        $stmt = $mysqli->prepare("UPDATE farm_produce_register_annex6 SET record_date = ?, received_from = ?, issued_to = ?, plot_no = ?, received_qty = ?, issued_qty = ?, quantity = ?, disposal_method = ?, unit_price = ?, full_sum_realized = ?, receipt_no_or_page = ?, initials = ?, remarks = ? WHERE id = ? AND user_id = ?");
        $stmt->bind_param("ssssdddsddsssii", $record_date, $received_from, $issued_to, $plot_no, $received_qty, $issued_qty, $quantity, $disposal_method, $unit_price, $full_sum_realized, $receipt_no_or_page, $initials, $remarks, $id, $user_id);

        if ($stmt->execute()) {
            recalculateProduceBalances($mysqli, $commodity_id);
            header("Location: ../production_details.php?commodity_id=" . $commodity_id . "&status=success&msg=" . urlencode("Produce register entry updated successfully."));
        } else {
            header("Location: ../production_details.php?commodity_id=" . $commodity_id . "&status=error&msg=" . urlencode("Failed to update produce entry: " . $stmt->error));
        }
        $stmt->close();
        exit();
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'GET') {
    if ($action === 'delete_commodity') {
        $id = intval($_GET['id']);
        if ($id > 0) {
            $del_ledger = $mysqli->prepare("DELETE FROM farm_produce_register_annex6 WHERE commodity_id = ?");
            $del_ledger->bind_param("i", $id);
            $del_ledger->execute();
            $del_ledger->close();

            $stmt = $mysqli->prepare("DELETE FROM farm_commodities WHERE id = ?");
            $stmt->bind_param("i", $id);
            if ($stmt->execute()) {
                header("Location: ../production_details.php?tab=manage&status=success&msg=" . urlencode("Commodity item and associated produce records deleted."));
            } else {
                header("Location: ../production_details.php?tab=manage&status=error&msg=" . urlencode("Failed to delete commodity item: " . $stmt->error));
            }
            $stmt->close();
        } else {
            header("Location: ../production_details.php?tab=manage&status=error&msg=" . urlencode("Invalid commodity item ID."));
        }
        exit();
    }

    if ($action === 'delete_produce') {
        $id = intval($_GET['id']);
        $commodity_id = intval($_GET['commodity_id']);

        $stmt = $mysqli->prepare("DELETE FROM farm_produce_register_annex6 WHERE id = ? AND user_id = ?");
        $stmt->bind_param("ii", $id, $user_id);

        if ($stmt->execute()) {
            recalculateProduceBalances($mysqli, $commodity_id);
            header("Location: ../production_details.php?commodity_id=" . $commodity_id . "&status=success&msg=" . urlencode("Produce entry deleted successfully."));
        } else {
            header("Location: ../production_details.php?commodity_id=" . $commodity_id . "&status=error&msg=" . urlencode("Failed to delete entry: " . $stmt->error));
        }
        $stmt->close();
        exit();
    }
}

$mysqli->close();
?>
