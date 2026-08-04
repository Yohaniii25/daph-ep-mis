<?php
// pages/modules/farm/processors/produce_register_crud.php
session_start();
require_once '../../../../config/db_connect.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'farms_dd') {
    die("Access denied");
}

$user_id = $_SESSION['user_id'] ?? 1;
$action = $_POST['action'] ?? $_GET['action'] ?? '';

// Ensure tables exist
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
  `plot_no` VARCHAR(100) DEFAULT NULL,
  `quantity` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `disposal_method` VARCHAR(255) NOT NULL,
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
                header("Location: ../production_details.php?commodity_id=" . $new_id . "&status=success&msg=" . urlencode("New commodity item added successfully."));
            } else {
                header("Location: ../production_details.php?status=error&msg=" . urlencode("Failed to add commodity item: " . $stmt->error));
            }
            $stmt->close();
        } else {
            header("Location: ../production_details.php?status=error&msg=" . urlencode("Commodity name is required."));
        }
        exit();
    }

    if ($action === 'create_produce') {
        $commodity_id = intval($_POST['commodity_id']);
        $record_date = $_POST['record_date'];
        $plot_no = trim($_POST['plot_no'] ?? '');
        $quantity = floatval($_POST['quantity'] ?? 0);
        $disposal_method = trim($_POST['disposal_method']);
        $unit_price = floatval($_POST['unit_price'] ?? 0);
        
        // Backend validation & calculation for Full Sum Realized
        $full_sum_realized = floatval($_POST['full_sum_realized'] ?? 0);
        if ($full_sum_realized == 0 && $quantity > 0 && $unit_price > 0) {
            $full_sum_realized = round($quantity * $unit_price, 2);
        }

        $receipt_no_or_page = trim($_POST['receipt_no_or_page'] ?? '');
        $initials = trim($_POST['initials'] ?? ($_SESSION['username'] ?? 'User'));
        $remarks = trim($_POST['remarks'] ?? '');

        $stmt = $mysqli->prepare("INSERT INTO farm_produce_register_annex6 (user_id, commodity_id, record_date, plot_no, quantity, disposal_method, unit_price, full_sum_realized, receipt_no_or_page, initials, remarks) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("iissdsddsss", $user_id, $commodity_id, $record_date, $plot_no, $quantity, $disposal_method, $unit_price, $full_sum_realized, $receipt_no_or_page, $initials, $remarks);

        if ($stmt->execute()) {
            header("Location: ../production_details.php?commodity_id=" . $commodity_id . "&status=success&msg=" . urlencode("Produce register entry logged successfully."));
        } else {
            header("Location: ../production_details.php?commodity_id=" . $commodity_id . "&status=error&msg=" . urlencode("Failed to log produce entry: " . $stmt->error));
        }
        $stmt->close();
        exit();
    }

    if ($action === 'update_produce') {
        $id = intval($_POST['id']);
        $commodity_id = intval($_POST['commodity_id']);
        $record_date = $_POST['record_date'];
        $plot_no = trim($_POST['plot_no'] ?? '');
        $quantity = floatval($_POST['quantity'] ?? 0);
        $disposal_method = trim($_POST['disposal_method']);
        $unit_price = floatval($_POST['unit_price'] ?? 0);
        
        $full_sum_realized = floatval($_POST['full_sum_realized'] ?? 0);
        if ($full_sum_realized == 0 && $quantity > 0 && $unit_price > 0) {
            $full_sum_realized = round($quantity * $unit_price, 2);
        }

        $receipt_no_or_page = trim($_POST['receipt_no_or_page'] ?? '');
        $initials = trim($_POST['initials'] ?? '');
        $remarks = trim($_POST['remarks'] ?? '');

        $stmt = $mysqli->prepare("UPDATE farm_produce_register_annex6 SET record_date = ?, plot_no = ?, quantity = ?, disposal_method = ?, unit_price = ?, full_sum_realized = ?, receipt_no_or_page = ?, initials = ?, remarks = ? WHERE id = ? AND user_id = ?");
        $stmt->bind_param("ssdsddsssii", $record_date, $plot_no, $quantity, $disposal_method, $unit_price, $full_sum_realized, $receipt_no_or_page, $initials, $remarks, $id, $user_id);

        if ($stmt->execute()) {
            header("Location: ../production_details.php?commodity_id=" . $commodity_id . "&status=success&msg=" . urlencode("Produce register entry updated successfully."));
        } else {
            header("Location: ../production_details.php?commodity_id=" . $commodity_id . "&status=error&msg=" . urlencode("Failed to update produce entry: " . $stmt->error));
        }
        $stmt->close();
        exit();
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'GET' && $action === 'delete_produce') {
    $id = intval($_GET['id']);
    $commodity_id = intval($_GET['commodity_id']);

    $stmt = $mysqli->prepare("DELETE FROM farm_produce_register_annex6 WHERE id = ? AND user_id = ?");
    $stmt->bind_param("ii", $id, $user_id);

    if ($stmt->execute()) {
        header("Location: ../production_details.php?commodity_id=" . $commodity_id . "&status=success&msg=" . urlencode("Produce entry deleted successfully."));
    } else {
        header("Location: ../production_details.php?commodity_id=" . $commodity_id . "&status=error&msg=" . urlencode("Failed to delete entry: " . $stmt->error));
    }
    $stmt->close();
    exit();
}

$mysqli->close();
?>
