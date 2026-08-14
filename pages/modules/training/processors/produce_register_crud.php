<?php
// pages/modules/training/processors/produce_register_crud.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once '../../../../config/db_connect.php';

// Allow authorized roles
$allowed_roles = ['training_officer', 'administrator', 'provincial_director', 'district_dd'];
if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], $allowed_roles)) {
    if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Access denied. Unauthorized role.']);
        exit();
    }
    die("Access denied");
}

// Auto-create database table if not existing
$table_sql = "CREATE TABLE IF NOT EXISTS `training_produce_register` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `training_center_id` INT(11) NOT NULL,
    `commodity` VARCHAR(255) NOT NULL,
    `record_date` DATE NOT NULL,
    `plot_no_crop` VARCHAR(255) DEFAULT NULL,
    `quantity` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    `unit` VARCHAR(50) NOT NULL DEFAULT 'kg',
    `disposal_method` VARCHAR(100) DEFAULT NULL,
    `price_per_unit` DECIMAL(12,2) DEFAULT 0.00,
    `full_sum_realized` DECIMAL(14,2) DEFAULT 0.00,
    `receipt_no_credit_page` VARCHAR(255) DEFAULT NULL,
    `initials_user` VARCHAR(255) DEFAULT NULL,
    `created_by` INT(11) DEFAULT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_training_center` (`training_center_id`),
    KEY `idx_commodity` (`commodity`),
    KEY `idx_record_date` (`record_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";

$mysqli->query($table_sql);

$action = $_POST['action'] ?? $_GET['action'] ?? '';
$is_ajax = (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') || (isset($_POST['is_ajax']) && $_POST['is_ajax'] == '1');
$redirect_url = '../produce_register.php';

// Helper to respond
function sendResponse($success, $message, $is_ajax, $redirect_url) {
    if ($is_ajax) {
        header('Content-Type: application/json');
        echo json_encode(['success' => $success, 'message' => $message]);
        exit();
    } else {
        if ($success) {
            $_SESSION['success_message'] = $message;
        } else {
            $_SESSION['error_message'] = $message;
        }
        header("Location: $redirect_url");
        exit();
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($action === 'create') {
        $training_center_id     = intval($_POST['training_center_id'] ?? 0);
        $commodity              = trim($_POST['commodity'] ?? '');
        $record_date            = trim($_POST['record_date'] ?? date('Y-m-d'));
        $plot_no_crop           = trim($_POST['plot_no_crop'] ?? '');
        $quantity               = floatval($_POST['quantity'] ?? 0);
        $unit                   = trim($_POST['unit'] ?? 'kg');
        $disposal_method        = trim($_POST['disposal_method'] ?? '');
        $price_per_unit         = floatval($_POST['price_per_unit'] ?? 0);
        $full_sum_realized      = isset($_POST['full_sum_realized']) && $_POST['full_sum_realized'] !== '' 
                                  ? floatval($_POST['full_sum_realized']) 
                                  : round($quantity * $price_per_unit, 2);
        $receipt_no_credit_page = trim($_POST['receipt_no_credit_page'] ?? '');
        $initials_user          = trim($_POST['initials_user'] ?? ($_SESSION['full_name'] ?? $_SESSION['username'] ?? ''));
        $created_by             = intval($_SESSION['user_id'] ?? 0);

        if ($training_center_id <= 0) {
            sendResponse(false, "Invalid training center facility.", $is_ajax, $redirect_url);
        }
        if (empty($commodity)) {
            sendResponse(false, "Commodity is required.", $is_ajax, $redirect_url);
        }
        if (empty($record_date)) {
            sendResponse(false, "Record date is required.", $is_ajax, $redirect_url);
        }
        if ($quantity <= 0) {
            sendResponse(false, "Quantity must be greater than 0.", $is_ajax, $redirect_url);
        }

        $stmt = $mysqli->prepare("INSERT INTO training_produce_register 
            (training_center_id, commodity, record_date, plot_no_crop, quantity, unit, disposal_method, price_per_unit, full_sum_realized, receipt_no_credit_page, initials_user, created_by) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

        if ($stmt) {
            $stmt->bind_param("isssdssddssi", 
                $training_center_id, 
                $commodity, 
                $record_date, 
                $plot_no_crop, 
                $quantity, 
                $unit, 
                $disposal_method, 
                $price_per_unit, 
                $full_sum_realized, 
                $receipt_no_credit_page, 
                $initials_user, 
                $created_by
            );
            if ($stmt->execute()) {
                $inserted_id = $stmt->insert_id;
                $stmt->close();
                sendResponse(true, "Produce register entry recorded successfully.", $is_ajax, $redirect_url . "?commodity=" . urlencode($commodity));
            } else {
                $err = $stmt->error;
                $stmt->close();
                sendResponse(false, "Database insert failed: " . $err, $is_ajax, $redirect_url);
            }
        } else {
            sendResponse(false, "Database prepare error: " . $mysqli->error, $is_ajax, $redirect_url);
        }
    } elseif ($action === 'update') {
        $id                     = intval($_POST['id'] ?? 0);
        $training_center_id     = intval($_POST['training_center_id'] ?? 0);
        $commodity              = trim($_POST['commodity'] ?? '');
        $record_date            = trim($_POST['record_date'] ?? date('Y-m-d'));
        $plot_no_crop           = trim($_POST['plot_no_crop'] ?? '');
        $quantity               = floatval($_POST['quantity'] ?? 0);
        $unit                   = trim($_POST['unit'] ?? 'kg');
        $disposal_method        = trim($_POST['disposal_method'] ?? '');
        $price_per_unit         = floatval($_POST['price_per_unit'] ?? 0);
        $full_sum_realized      = isset($_POST['full_sum_realized']) && $_POST['full_sum_realized'] !== '' 
                                  ? floatval($_POST['full_sum_realized']) 
                                  : round($quantity * $price_per_unit, 2);
        $receipt_no_credit_page = trim($_POST['receipt_no_credit_page'] ?? '');
        $initials_user          = trim($_POST['initials_user'] ?? '');

        if ($id <= 0) {
            sendResponse(false, "Invalid record ID.", $is_ajax, $redirect_url);
        }
        if (empty($commodity)) {
            sendResponse(false, "Commodity is required.", $is_ajax, $redirect_url);
        }
        if (empty($record_date)) {
            sendResponse(false, "Record date is required.", $is_ajax, $redirect_url);
        }
        if ($quantity <= 0) {
            sendResponse(false, "Quantity must be greater than 0.", $is_ajax, $redirect_url);
        }

        // Verify training center isolation if non-admin
        if (!in_array($_SESSION['role'], ['administrator', 'provincial_director', 'district_dd']) && $training_center_id > 0) {
            $stmt = $mysqli->prepare("UPDATE training_produce_register SET 
                commodity = ?, record_date = ?, plot_no_crop = ?, quantity = ?, unit = ?, disposal_method = ?, price_per_unit = ?, full_sum_realized = ?, receipt_no_credit_page = ?, initials_user = ? 
                WHERE id = ? AND training_center_id = ?");
            if ($stmt) {
                $stmt->bind_param("sssdssddssii", 
                    $commodity, 
                    $record_date, 
                    $plot_no_crop, 
                    $quantity, 
                    $unit, 
                    $disposal_method, 
                    $price_per_unit, 
                    $full_sum_realized, 
                    $receipt_no_credit_page, 
                    $initials_user, 
                    $id,
                    $training_center_id
                );
                if ($stmt->execute()) {
                    $stmt->close();
                    sendResponse(true, "Produce register entry updated successfully.", $is_ajax, $redirect_url);
                } else {
                    $err = $stmt->error;
                    $stmt->close();
                    sendResponse(false, "Database update failed: " . $err, $is_ajax, $redirect_url);
                }
            } else {
                sendResponse(false, "Database prepare error: " . $mysqli->error, $is_ajax, $redirect_url);
            }
        } else {
            $stmt = $mysqli->prepare("UPDATE training_produce_register SET 
                commodity = ?, record_date = ?, plot_no_crop = ?, quantity = ?, unit = ?, disposal_method = ?, price_per_unit = ?, full_sum_realized = ?, receipt_no_credit_page = ?, initials_user = ? 
                WHERE id = ?");
            if ($stmt) {
                $stmt->bind_param("sssdssddssi", 
                    $commodity, 
                    $record_date, 
                    $plot_no_crop, 
                    $quantity, 
                    $unit, 
                    $disposal_method, 
                    $price_per_unit, 
                    $full_sum_realized, 
                    $receipt_no_credit_page, 
                    $initials_user, 
                    $id
                );
                if ($stmt->execute()) {
                    $stmt->close();
                    sendResponse(true, "Produce register entry updated successfully.", $is_ajax, $redirect_url);
                } else {
                    $err = $stmt->error;
                    $stmt->close();
                    sendResponse(false, "Database update failed: " . $err, $is_ajax, $redirect_url);
                }
            } else {
                sendResponse(false, "Database prepare error: " . $mysqli->error, $is_ajax, $redirect_url);
            }
        }
    }
} elseif ($action === 'delete') {
    $id = intval($_POST['id'] ?? $_GET['id'] ?? 0);
    $training_center_id = intval($_POST['training_center_id'] ?? $_GET['training_center_id'] ?? 0);

    if ($id <= 0) {
        sendResponse(false, "Invalid record ID.", $is_ajax, $redirect_url);
    }

    if (!in_array($_SESSION['role'], ['administrator', 'provincial_director', 'district_dd']) && $training_center_id > 0) {
        $stmt = $mysqli->prepare("DELETE FROM training_produce_register WHERE id = ? AND training_center_id = ?");
        if ($stmt) {
            $stmt->bind_param("ii", $id, $training_center_id);
            if ($stmt->execute()) {
                $stmt->close();
                sendResponse(true, "Produce record deleted successfully.", $is_ajax, $redirect_url);
            } else {
                $err = $stmt->error;
                $stmt->close();
                sendResponse(false, "Failed to delete record: " . $err, $is_ajax, $redirect_url);
            }
        }
    } else {
        $stmt = $mysqli->prepare("DELETE FROM training_produce_register WHERE id = ?");
        if ($stmt) {
            $stmt->bind_param("i", $id);
            if ($stmt->execute()) {
                $stmt->close();
                sendResponse(true, "Produce record deleted successfully.", $is_ajax, $redirect_url);
            } else {
                $err = $stmt->error;
                $stmt->close();
                sendResponse(false, "Failed to delete record: " . $err, $is_ajax, $redirect_url);
            }
        }
    }
}

sendResponse(false, "Invalid request parameters.", $is_ajax, $redirect_url);
