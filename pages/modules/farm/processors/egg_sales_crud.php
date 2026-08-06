<?php
// pages/modules/farm/processors/egg_sales_crud.php
session_start();
require_once '../../../../config/db_connect.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'farms_dd') {
    die("Access denied");
}

$user_id = $_SESSION['user_id'] ?? 1;
$action = $_POST['action'] ?? $_GET['action'] ?? '';

// Auto-create table if not exists
$table_check_sql = "CREATE TABLE IF NOT EXISTS `daily_egg_sales` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `user_id` INT(11) NOT NULL,
  `cage_id` INT(11) NOT NULL,
  `batch_id` INT(11) NOT NULL,
  `sale_date` DATE NOT NULL,
  `table_eggs_no` INT(11) DEFAULT 0,
  `table_eggs_kg` DECIMAL(10,2) DEFAULT 0.00,
  `table_eggs_unit_price` DECIMAL(10,2) DEFAULT 0.00,
  `table_eggs_total_sales` DECIMAL(12,2) DEFAULT 0.00,
  `cracked_eggs_no` INT(11) DEFAULT 0,
  `cracked_eggs_kg` DECIMAL(10,2) DEFAULT 0.00,
  `cracked_eggs_unit_price` DECIMAL(10,2) DEFAULT 0.00,
  `cracked_eggs_total_sales` DECIMAL(12,2) DEFAULT 0.00,
  `grand_total_sales` DECIMAL(12,2) DEFAULT 0.00,
  `remarks` TEXT DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP(),
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `cage_id` (`cage_id`),
  KEY `batch_id` (`batch_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
$mysqli->query($table_check_sql);

if ($action === 'get_collection') {
    header('Content-Type: application/json');
    if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'farms_dd') {
        echo json_encode(['status' => 'error', 'message' => 'Access denied']);
        exit;
    }

    $sale_date = $_POST['sale_date'] ?? '';
    $cage_id   = intval($_POST['cage_id'] ?? 0);
    $batch_id  = intval($_POST['batch_id'] ?? 0);

    if (empty($sale_date) || $cage_id <= 0 || $batch_id <= 0) {
        echo json_encode(['status' => 'empty', 'message' => 'Missing date, cage, or batch']);
        exit;
    }

    // 1. Primary Lookup: Search daily_egg_production (Parent Stock Register)
    $stmt_find = $mysqli->prepare("SELECT 
                                    SUM(dep.table_eggs) AS table_eggs, 
                                    SUM(dep.table_eggs_kg) AS table_eggs_kg, 
                                    SUM(dep.cracked_eggs) AS cracked_eggs, 
                                    SUM(dep.cracked_eggs_kg) AS cracked_eggs_kg,
                                    COUNT(dep.id) AS total_records
                                   FROM daily_egg_production dep
                                   LEFT JOIN vaccine_batches b ON dep.batch_id = b.id
                                   WHERE DATE(dep.collection_date) = ? AND dep.cage_id = ? AND dep.batch_id = ? 
                                     AND (b.user_id = ? OR b.user_id IS NULL OR b.user_id = 0)");
    $stmt_find->bind_param("siii", $sale_date, $cage_id, $batch_id, $user_id);
    $stmt_find->execute();
    $res = $stmt_find->get_result();

    if ($res && ($row = $res->fetch_assoc()) && intval($row['total_records']) > 0) {
        echo json_encode([
            'status'          => 'success',
            'source'          => 'production',
            'table_eggs'      => intval($row['table_eggs'] ?? 0),
            'table_eggs_kg'   => floatval($row['table_eggs_kg'] ?? 0),
            'cracked_eggs'    => intval($row['cracked_eggs'] ?? 0),
            'cracked_eggs_kg' => floatval($row['cracked_eggs_kg'] ?? 0)
        ]);
        $stmt_find->close();
        exit;
    }
    $stmt_find->close();

    // 2. Secondary Lookup: Search daily_egg_sales (Existing Sales Entry for this Date, Cage & Batch)
    $stmt_sales = $mysqli->prepare("SELECT 
                                     SUM(table_eggs_no) AS table_eggs, 
                                     SUM(table_eggs_kg) AS table_eggs_kg, 
                                     AVG(table_eggs_unit_price) AS table_eggs_unit_price,
                                     SUM(cracked_eggs_no) AS cracked_eggs, 
                                     SUM(cracked_eggs_kg) AS cracked_eggs_kg,
                                     AVG(cracked_eggs_unit_price) AS cracked_eggs_unit_price,
                                     COUNT(id) AS total_records
                                    FROM daily_egg_sales 
                                    WHERE DATE(sale_date) = ? AND cage_id = ? AND batch_id = ? AND user_id = ?");
    $stmt_sales->bind_param("siii", $sale_date, $cage_id, $batch_id, $user_id);
    $stmt_sales->execute();
    $res_sales = $stmt_sales->get_result();

    if ($res_sales && ($row_s = $res_sales->fetch_assoc()) && intval($row_s['total_records']) > 0) {
        echo json_encode([
            'status'                  => 'success',
            'source'                  => 'sales',
            'table_eggs'              => intval($row_s['table_eggs'] ?? 0),
            'table_eggs_kg'           => floatval($row_s['table_eggs_kg'] ?? 0),
            'table_eggs_unit_price'   => floatval($row_s['table_eggs_unit_price'] ?? 0),
            'cracked_eggs'            => intval($row_s['cracked_eggs'] ?? 0),
            'cracked_eggs_kg'         => floatval($row_s['cracked_eggs_kg'] ?? 0),
            'cracked_eggs_unit_price' => floatval($row_s['cracked_eggs_unit_price'] ?? 0)
        ]);
        $stmt_sales->close();
        exit;
    }
    $stmt_sales->close();

    echo json_encode(['status' => 'not_found', 'message' => 'No collection or sales record found for selected Date, Cage & Batch']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $sale_date = $mysqli->real_escape_string($_POST['sale_date']);
    $cage_id = intval($_POST['cage_id']);
    $batch_id = intval($_POST['batch_id']);

    // Table Eggs Section
    $table_eggs_no = intval($_POST['table_eggs_no'] ?? 0);
    $table_eggs_kg = floatval($_POST['table_eggs_kg'] ?? 0);
    $table_eggs_unit_price = floatval($_POST['table_eggs_unit_price'] ?? 0);
    $table_eggs_total_sales = floatval($_POST['table_eggs_total_sales'] ?? 0);
    if ($table_eggs_total_sales == 0 && $table_eggs_unit_price > 0) {
        $multiplier = ($table_eggs_no > 0) ? $table_eggs_no : $table_eggs_kg;
        $table_eggs_total_sales = round($multiplier * $table_eggs_unit_price, 2);
    }

    // Cracked Eggs Section
    $cracked_eggs_no = intval($_POST['cracked_eggs_no'] ?? 0);
    $cracked_eggs_kg = floatval($_POST['cracked_eggs_kg'] ?? 0);
    $cracked_eggs_unit_price = floatval($_POST['cracked_eggs_unit_price'] ?? 0);
    $cracked_eggs_total_sales = floatval($_POST['cracked_eggs_total_sales'] ?? 0);
    if ($cracked_eggs_total_sales == 0 && $cracked_eggs_unit_price > 0) {
        $multiplier = ($cracked_eggs_no > 0) ? $cracked_eggs_no : $cracked_eggs_kg;
        $cracked_eggs_total_sales = round($multiplier * $cracked_eggs_unit_price, 2);
    }

    $grand_total_sales = round($table_eggs_total_sales + $cracked_eggs_total_sales, 2);
    $remarks = isset($_POST['remarks']) ? $mysqli->real_escape_string($_POST['remarks']) : null;

    if ($action === 'create') {
        $stmt = $mysqli->prepare("INSERT INTO daily_egg_sales (user_id, cage_id, batch_id, sale_date, table_eggs_no, table_eggs_kg, table_eggs_unit_price, table_eggs_total_sales, cracked_eggs_no, cracked_eggs_kg, cracked_eggs_unit_price, cracked_eggs_total_sales, grand_total_sales, remarks) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("iiisidddidddds", 
            $user_id, 
            $cage_id, 
            $batch_id, 
            $sale_date, 
            $table_eggs_no, 
            $table_eggs_kg, 
            $table_eggs_unit_price, 
            $table_eggs_total_sales, 
            $cracked_eggs_no, 
            $cracked_eggs_kg, 
            $cracked_eggs_unit_price, 
            $cracked_eggs_total_sales, 
            $grand_total_sales, 
            $remarks
        );

        if ($stmt->execute()) {
            header("Location: ../sales_of_eggs.php?status=success&msg=" . urlencode("Daily egg sales transaction recorded successfully."));
        } else {
            header("Location: ../sales_of_eggs.php?status=error&msg=" . urlencode("Failed to record egg sales: " . $stmt->error));
        }
        $stmt->close();
    } elseif ($action === 'update') {
        $id = intval($_POST['id']);
        $stmt = $mysqli->prepare("UPDATE daily_egg_sales SET cage_id = ?, batch_id = ?, sale_date = ?, table_eggs_no = ?, table_eggs_kg = ?, table_eggs_unit_price = ?, table_eggs_total_sales = ?, cracked_eggs_no = ?, cracked_eggs_kg = ?, cracked_eggs_unit_price = ?, cracked_eggs_total_sales = ?, grand_total_sales = ?, remarks = ? WHERE id = ? AND user_id = ?");
        $stmt->bind_param("iisidddiddddsii", 
            $cage_id, 
            $batch_id, 
            $sale_date, 
            $table_eggs_no, 
            $table_eggs_kg, 
            $table_eggs_unit_price, 
            $table_eggs_total_sales, 
            $cracked_eggs_no, 
            $cracked_eggs_kg, 
            $cracked_eggs_unit_price, 
            $cracked_eggs_total_sales, 
            $grand_total_sales, 
            $remarks, 
            $id, 
            $user_id
        );

        if ($stmt->execute()) {
            header("Location: ../sales_of_eggs.php?status=success&msg=" . urlencode("Egg sales transaction updated successfully."));
        } else {
            header("Location: ../sales_of_eggs.php?status=error&msg=" . urlencode("Failed to update egg sales: " . $stmt->error));
        }
        $stmt->close();
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'GET' && $action === 'delete') {
    $id = intval($_GET['id']);
    $stmt = $mysqli->prepare("DELETE FROM daily_egg_sales WHERE id = ? AND user_id = ?");
    $stmt->bind_param("ii", $id, $user_id);

    if ($stmt->execute()) {
        header("Location: ../sales_of_eggs.php?status=success&msg=" . urlencode("Egg sales record deleted successfully."));
    } else {
        header("Location: ../sales_of_eggs.php?status=error&msg=" . urlencode("Failed to delete record: " . $stmt->error));
    }
    $stmt->close();
}

$mysqli->close();
?>
