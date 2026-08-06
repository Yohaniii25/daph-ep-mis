<?php
// pages/modules/farm/processors/drug_register_crud.php
session_start();
require_once '../../../../config/db_connect.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'farms_dd') {
    die("Access denied");
}

$user_id = $_SESSION['user_id'] ?? 1;
$action = $_POST['action'] ?? $_GET['action'] ?? '';

$cols_ledger = $mysqli->query("SHOW COLUMNS FROM `farm_drug_register_annex5`");
$existing_cols = [];
if ($cols_ledger) {
    while ($c = $cols_ledger->fetch_assoc()) {
        $existing_cols[] = $c['Field'];
    }
}
if (!in_array('order_no', $existing_cols)) {
    $mysqli->query("ALTER TABLE `farm_drug_register_annex5` ADD COLUMN `order_no` VARCHAR(100) DEFAULT NULL AFTER `item_id`");
}
if (!in_array('received_from', $existing_cols)) {
    $mysqli->query("ALTER TABLE `farm_drug_register_annex5` ADD COLUMN `received_from` VARCHAR(255) DEFAULT NULL AFTER `record_date`");
}
if (!in_array('issued_to', $existing_cols)) {
    $mysqli->query("ALTER TABLE `farm_drug_register_annex5` ADD COLUMN `issued_to` VARCHAR(255) DEFAULT NULL AFTER `received_from`");
}
if (!in_array('exp_date', $existing_cols)) {
    $mysqli->query("ALTER TABLE `farm_drug_register_annex5` ADD COLUMN `exp_date` DATE DEFAULT NULL AFTER `ref_doc_no`");
}

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
        $exp_date = !empty($_POST['exp_date']) ? $_POST['exp_date'] : null;

        if (!empty($item_name)) {
            $stmt = $mysqli->prepare("INSERT INTO farm_drug_items (item_name, unit_of_measure, description, exp_date) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("ssss", $item_name, $unit, $desc, $exp_date);
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

    if ($action === 'create_receive_order' || $action === 'create_issue_order' || $action === 'create_ledger') {
        $item_id = intval($_POST['item_id']);
        $record_date = $_POST['record_date'] ?? date('Y-m-d');
        $order_no = trim($_POST['order_no'] ?? '');
        $received_from = trim($_POST['received_from'] ?? '');
        $issued_to = trim($_POST['issued_to'] ?? '');
        $party_name = trim($_POST['party_name'] ?? '');
        $ref_doc_no = trim($_POST['ref_doc_no'] ?? '');
        $exp_date = !empty($_POST['exp_date']) ? $_POST['exp_date'] : null;
        $received_qty = floatval($_POST['received_qty'] ?? 0);
        $issued_qty = floatval($_POST['issued_qty'] ?? 0);
        $remarks = trim($_POST['remarks'] ?? '');

        // Standardize action type specific fields
        if ($action === 'create_receive_order') {
            $issued_qty = 0.00;
            if (empty($order_no)) {
                $order_no = 'RO-' . date('Ymd', strtotime($record_date)) . '-' . rand(1000, 9999);
            }
            if (empty($party_name)) {
                $party_name = !empty($received_from) ? $received_from : 'Supplier';
            }
        } elseif ($action === 'create_issue_order') {
            $received_qty = 0.00;
            if (empty($order_no)) {
                $order_no = 'IO-' . date('Ymd', strtotime($record_date)) . '-' . rand(1000, 9999);
            }
            if (empty($party_name)) {
                $party_name = !empty($issued_to) ? $issued_to : 'Farm Unit';
            }
        } else {
            if (empty($party_name)) {
                $party_name = !empty($received_from) ? $received_from : (!empty($issued_to) ? $issued_to : '-');
            }
        }

        $stmt = $mysqli->prepare("INSERT INTO farm_drug_register_annex5 (user_id, item_id, order_no, record_date, received_from, issued_to, party_name, ref_doc_no, exp_date, received_qty, issued_qty, remarks) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("iisssssssdds", $user_id, $item_id, $order_no, $record_date, $received_from, $issued_to, $party_name, $ref_doc_no, $exp_date, $received_qty, $issued_qty, $remarks);
        
        if ($stmt->execute()) {
            recalculateDrugBalances($mysqli, $item_id);
            header("Location: ../drug_details.php?item_id=" . $item_id . "&status=success&msg=" . urlencode("Stock transaction (" . htmlspecialchars($order_no) . ") recorded successfully."));
        } else {
            header("Location: ../drug_details.php?item_id=" . $item_id . "&status=error&msg=" . urlencode("Failed to record stock transaction: " . $stmt->error));
        }
        $stmt->close();
        exit();
    }

    if ($action === 'update_ledger') {
        $id = intval($_POST['id']);
        $item_id = intval($_POST['item_id']);
        $order_no = trim($_POST['order_no'] ?? '');
        $record_date = $_POST['record_date'];
        $received_from = trim($_POST['received_from'] ?? '');
        $issued_to = trim($_POST['issued_to'] ?? '');
        $party_name = trim($_POST['party_name'] ?? '');
        $ref_doc_no = trim($_POST['ref_doc_no'] ?? '');
        $exp_date = !empty($_POST['exp_date']) ? $_POST['exp_date'] : null;
        $received_qty = floatval($_POST['received_qty'] ?? 0);
        $issued_qty = floatval($_POST['issued_qty'] ?? 0);
        $remarks = trim($_POST['remarks'] ?? '');

        if (empty($party_name)) {
            $party_name = !empty($received_from) ? $received_from : (!empty($issued_to) ? $issued_to : '-');
        }

        $stmt = $mysqli->prepare("UPDATE farm_drug_register_annex5 SET order_no = ?, record_date = ?, received_from = ?, issued_to = ?, party_name = ?, ref_doc_no = ?, exp_date = ?, received_qty = ?, issued_qty = ?, remarks = ? WHERE id = ? AND user_id = ?");
        $stmt->bind_param("sssssssddsii", $order_no, $record_date, $received_from, $issued_to, $party_name, $ref_doc_no, $exp_date, $received_qty, $issued_qty, $remarks, $id, $user_id);
        
        if ($stmt->execute()) {
            recalculateDrugBalances($mysqli, $item_id);
            header("Location: ../drug_details.php?item_id=" . $item_id . "&status=success&msg=" . urlencode("Stock transaction updated successfully."));
        } else {
            header("Location: ../drug_details.php?item_id=" . $item_id . "&status=error&msg=" . urlencode("Failed to update stock transaction: " . $stmt->error));
        }
        $stmt->close();
        exit();
    }

    if ($action === 'update_item') {
        $id = intval($_POST['id']);
        $item_name = trim($_POST['item_name']);
        $unit = trim($_POST['unit_of_measure'] ?? 'units');
        $desc = trim($_POST['description'] ?? '');
        $exp_date = !empty($_POST['exp_date']) ? $_POST['exp_date'] : null;

        if (!empty($item_name) && $id > 0) {
            $stmt = $mysqli->prepare("UPDATE farm_drug_items SET item_name = ?, unit_of_measure = ?, description = ?, exp_date = ? WHERE id = ?");
            $stmt->bind_param("ssssi", $item_name, $unit, $desc, $exp_date, $id);
            if ($stmt->execute()) {
                header("Location: ../drug_details.php?item_id=" . $id . "&tab=manage&status=success&msg=" . urlencode("Drug item updated successfully."));
            } else {
                header("Location: ../drug_details.php?item_id=" . $id . "&tab=manage&status=error&msg=" . urlencode("Failed to update drug item: " . $stmt->error));
            }
            $stmt->close();
        } else {
            header("Location: ../drug_details.php?tab=manage&status=error&msg=" . urlencode("Item name and ID are required."));
        }
        exit();
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'GET' && $action === 'delete_item') {
    $id = intval($_GET['id']);
    if ($id > 0) {
        $del_ledger = $mysqli->prepare("DELETE FROM farm_drug_register_annex5 WHERE item_id = ?");
        $del_ledger->bind_param("i", $id);
        $del_ledger->execute();
        $del_ledger->close();

        $stmt = $mysqli->prepare("DELETE FROM farm_drug_items WHERE id = ?");
        $stmt->bind_param("i", $id);
        if ($stmt->execute()) {
            header("Location: ../drug_details.php?tab=manage&status=success&msg=" . urlencode("Drug item and related ledger entries deleted."));
        } else {
            header("Location: ../drug_details.php?tab=manage&status=error&msg=" . urlencode("Failed to delete drug item: " . $stmt->error));
        }
        $stmt->close();
    } else {
        header("Location: ../drug_details.php?tab=manage&status=error&msg=" . urlencode("Invalid drug item ID."));
    }
    exit();
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
    exit;
}

$mysqli->close();
?>
