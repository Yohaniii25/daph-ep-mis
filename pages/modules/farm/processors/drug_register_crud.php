<?php
// pages/modules/farm/processors/drug_register_crud.php
session_start();
require_once '../../../../config/db_connect.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'farms_dd') {
    die("Access denied");
}

$user_id = $_SESSION['user_id'] ?? 1;
$action = $_POST['action'] ?? $_GET['action'] ?? '';

// Ensure tables exist
$mysqli->query("CREATE TABLE IF NOT EXISTS `farm_drug_items` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `item_name` VARCHAR(255) NOT NULL,
  `unit_of_measure` VARCHAR(50) DEFAULT 'units',
  `description` TEXT DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

$mysqli->query("CREATE TABLE IF NOT EXISTS `farm_drug_register_annex5` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `user_id` INT(11) NOT NULL,
  `item_id` INT(11) NOT NULL,
  `record_date` DATE NOT NULL,
  `party_name` VARCHAR(255) NOT NULL,
  `ref_doc_no` VARCHAR(255) DEFAULT NULL,
  `received_qty` DECIMAL(10,2) DEFAULT 0.00,
  `issued_qty` DECIMAL(10,2) DEFAULT 0.00,
  `balance_qty` DECIMAL(10,2) DEFAULT 0.00,
  `remarks` TEXT DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP(),
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `item_id` (`item_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

// Function to recalculate running balances for an item
function recalculateDrugBalances($mysqli, $item_id) {
    $stmt = $mysqli->prepare("SELECT id, received_qty, issued_qty FROM farm_drug_register_annex5 WHERE item_id = ? ORDER BY record_date ASC, id ASC");
    $stmt->bind_param("i", $item_id);
    $stmt->execute();
    $res = $stmt->get_result();
    
    $running_balance = 0.00;
    while ($row = $res->fetch_assoc()) {
        $id = $row['id'];
        $rec = floatval($row['received_qty']);
        $iss = floatval($row['issued_qty']);
        $running_balance = round($running_balance + $rec - $iss, 2);
        
        $upd = $mysqli->prepare("UPDATE farm_drug_register_annex5 SET balance_qty = ? WHERE id = ?");
        $upd->bind_param("di", $running_balance, $id);
        $upd->execute();
        $upd->close();
    }
    $stmt->close();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($action === 'create_item') {
        $item_name = trim($_POST['item_name']);
        $unit = trim($_POST['unit_of_measure'] ?? 'units');
        $desc = trim($_POST['description'] ?? '');

        if (!empty($item_name)) {
            $stmt = $mysqli->prepare("INSERT INTO farm_drug_items (item_name, unit_of_measure, description) VALUES (?, ?, ?)");
            $stmt->bind_param("sss", $item_name, $unit, $desc);
            if ($stmt->execute()) {
                $new_id = $stmt->insert_id;
                header("Location: ../drug_details.php?item_id=" . $new_id . "&status=success&msg=" . urlencode("New drug item added successfully."));
            } else {
                header("Location: ../drug_details.php?status=error&msg=" . urlencode("Failed to add drug item: " . $stmt->error));
            }
            $stmt->close();
        } else {
            header("Location: ../drug_details.php?status=error&msg=" . urlencode("Drug item name is required."));
        }
        exit();
    }

    if ($action === 'create_ledger') {
        $item_id = intval($_POST['item_id']);
        $record_date = $_POST['record_date'];
        $party_name = trim($_POST['party_name']);
        $ref_doc_no = trim($_POST['ref_doc_no'] ?? '');
        $received_qty = floatval($_POST['received_qty'] ?? 0);
        $issued_qty = floatval($_POST['issued_qty'] ?? 0);
        $remarks = trim($_POST['remarks'] ?? '');

        $stmt = $mysqli->prepare("INSERT INTO farm_drug_register_annex5 (user_id, item_id, record_date, party_name, ref_doc_no, received_qty, issued_qty, remarks) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("iissddds", $user_id, $item_id, $record_date, $party_name, $ref_doc_no, $received_qty, $issued_qty, $remarks);
        
        if ($stmt->execute()) {
            recalculateDrugBalances($mysqli, $item_id);
            header("Location: ../drug_details.php?item_id=" . $item_id . "&status=success&msg=" . urlencode("Drug stock transaction recorded successfully."));
        } else {
            header("Location: ../drug_details.php?item_id=" . $item_id . "&status=error&msg=" . urlencode("Failed to record stock transaction: " . $stmt->error));
        }
        $stmt->close();
        exit();
    }

    if ($action === 'update_ledger') {
        $id = intval($_POST['id']);
        $item_id = intval($_POST['item_id']);
        $record_date = $_POST['record_date'];
        $party_name = trim($_POST['party_name']);
        $ref_doc_no = trim($_POST['ref_doc_no'] ?? '');
        $received_qty = floatval($_POST['received_qty'] ?? 0);
        $issued_qty = floatval($_POST['issued_qty'] ?? 0);
        $remarks = trim($_POST['remarks'] ?? '');

        $stmt = $mysqli->prepare("UPDATE farm_drug_register_annex5 SET record_date = ?, party_name = ?, ref_doc_no = ?, received_qty = ?, issued_qty = ?, remarks = ? WHERE id = ? AND user_id = ?");
        $stmt->bind_param("sssddsii", $record_date, $party_name, $ref_doc_no, $received_qty, $issued_qty, $remarks, $id, $user_id);
        
        if ($stmt->execute()) {
            recalculateDrugBalances($mysqli, $item_id);
            header("Location: ../drug_details.php?item_id=" . $item_id . "&status=success&msg=" . urlencode("Stock transaction updated successfully."));
        } else {
            header("Location: ../drug_details.php?item_id=" . $item_id . "&status=error&msg=" . urlencode("Failed to update stock transaction: " . $stmt->error));
        }
        $stmt->close();
        exit();
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'GET' && $action === 'delete_ledger') {
    $id = intval($_GET['id']);
    $item_id = intval($_GET['item_id']);
    
    $stmt = $mysqli->prepare("DELETE FROM farm_drug_register_annex5 WHERE id = ? AND user_id = ?");
    $stmt->bind_param("ii", $id, $user_id);

    if ($stmt->execute()) {
        recalculateDrugBalances($mysqli, $item_id);
        header("Location: ../drug_details.php?item_id=" . $item_id . "&status=success&msg=" . urlencode("Stock transaction deleted successfully."));
    } else {
        header("Location: ../drug_details.php?item_id=" . $item_id . "&status=error&msg=" . urlencode("Failed to delete record: " . $stmt->error));
    }
    $stmt->close();
    exit();
}

$mysqli->close();
?>
