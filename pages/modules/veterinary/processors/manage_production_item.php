<?php
session_start();
require_once __DIR__ . '/../../../../config/db_connect.php';

/** @var mysqli $mysqli */
global $mysqli;

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['logged_in']) || !in_array($_SESSION['role'] ?? '', ['veterinary_surgeon', 'admin', 'super_admin'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access.']);
    exit();
}

$action = trim($_POST['action'] ?? $_GET['action'] ?? '');
$item_id = intval($_POST['item_id'] ?? $_GET['item_id'] ?? 0);
$selected_year = intval($_POST['year'] ?? $_GET['year'] ?? date('Y'));

if (!$action) {
    echo json_encode(['success' => false, 'message' => 'Missing action parameter.']);
    exit();
}

if ($action === 'check_usage') {
    if ($item_id <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid Item ID.']);
        exit();
    }

    $stmt = $mysqli->prepare("
        SELECT 
            COUNT(*) AS total_rows,
            SUM(IF(amount > 0, 1, 0)) AS non_zero_rows,
            GROUP_CONCAT(DISTINCT IF(amount > 0, report_year, NULL) ORDER BY report_year) AS recorded_years
        FROM section_e 
        WHERE item_id = ?
    ");
    $stmt->bind_param("i", $item_id);
    $stmt->execute();
    $res = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    $non_zero_rows = intval($res['non_zero_rows'] ?? 0);
    $recorded_years = $res['recorded_years'] ?? '';
    $has_data = ($non_zero_rows > 0);

    // Fetch item details
    $it_stmt = $mysqli->prepare("SELECT item_name, unit, category_id, is_active, archived_year FROM production_items WHERE id = ?");
    $it_stmt->bind_param("i", $item_id);
    $it_stmt->execute();
    $item_info = $it_stmt->get_result()->fetch_assoc();
    $it_stmt->close();

    echo json_encode([
        'success'        => true,
        'item'           => $item_info,
        'has_data'       => $has_data,
        'non_zero_rows'  => $non_zero_rows,
        'recorded_years' => $recorded_years ?: 'None'
    ]);
    exit();
}

if ($action === 'archive') {
    if ($item_id <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid Item ID.']);
        exit();
    }

    $archive_year = intval($_POST['archived_year'] ?? $selected_year);
    if ($archive_year < 2000 || $archive_year > 2100) {
        $archive_year = intval(date('Y'));
    }

    $stmt = $mysqli->prepare("UPDATE production_items SET is_active = 0, archived_year = ? WHERE id = ?");
    $stmt->bind_param("ii", $archive_year, $item_id);
    if ($stmt->execute()) {
        echo json_encode([
            'success' => true,
            'message' => "Item archived starting from year {$archive_year}. Historical reports in prior years remain completely intact."
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $stmt->error]);
    }
    $stmt->close();
    exit();
}

if ($action === 'reactivate') {
    if ($item_id <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid Item ID.']);
        exit();
    }

    $stmt = $mysqli->prepare("UPDATE production_items SET is_active = 1, archived_year = NULL WHERE id = ?");
    $stmt->bind_param("i", $item_id);
    if ($stmt->execute()) {
        echo json_encode([
            'success' => true,
            'message' => "Subcategory has been reactivated successfully and will appear in current and future reporting years."
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $stmt->error]);
    }
    $stmt->close();
    exit();
}

if ($action === 'delete') {
    if ($item_id <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid Item ID.']);
        exit();
    }

    // Double check if any actual recorded data exists
    $stmt = $mysqli->prepare("SELECT COUNT(*) AS c FROM section_e WHERE item_id = ? AND amount > 0");
    $stmt->bind_param("i", $item_id);
    $stmt->execute();
    $cnt = intval($stmt->get_result()->fetch_assoc()['c'] ?? 0);
    $stmt->close();

    if ($cnt > 0) {
        echo json_encode([
            'success' => false,
            'message' => "Cannot permanently delete: this item has {$cnt} recorded non-zero entry(s) in past reports. Please Archive it instead to protect historical official data."
        ]);
        exit();
    }

    // Clean zero-amount rows from section_e if any
    $clean_stmt = $mysqli->prepare("DELETE FROM section_e WHERE item_id = ?");
    $clean_stmt->bind_param("i", $item_id);
    $clean_stmt->execute();
    $clean_stmt->close();

    // Delete item from production_items
    $del_stmt = $mysqli->prepare("DELETE FROM production_items WHERE id = ?");
    $del_stmt->bind_param("i", $item_id);
    if ($del_stmt->execute()) {
        echo json_encode([
            'success' => true,
            'message' => "Subcategory permanently deleted successfully."
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $del_stmt->error]);
    }
    $del_stmt->close();
    exit();
}

if ($action === 'list_archived') {
    $cat_id = intval($_GET['category_id'] ?? 0);
    $query = "
        SELECT pi.id, pi.category_id, pi.item_name, pi.unit, pi.archived_year, pc.category_name
        FROM production_items pi
        JOIN production_categories pc ON pi.category_id = pc.id
        WHERE pi.is_active = 0
    ";
    if ($cat_id > 0) {
        $query .= " AND pi.category_id = " . $cat_id;
    }
    $query .= " ORDER BY pc.sort_order ASC, pi.item_name ASC";

    $res = $mysqli->query($query);
    $archived_items = [];
    if ($res) {
        while ($r = $res->fetch_assoc()) {
            $archived_items[] = $r;
        }
    }

    echo json_encode(['success' => true, 'archived_items' => $archived_items]);
    exit();
}

echo json_encode(['success' => false, 'message' => 'Unknown action.']);
exit();
