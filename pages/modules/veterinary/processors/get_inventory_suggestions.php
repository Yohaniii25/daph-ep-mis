<?php
/**
 * pages/modules/veterinary/processors/get_inventory_suggestions.php
 * Real-time Auto-suggest endpoint for Building Inventory Item Names
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json');

if (!isset($_SESSION['logged_in']) || empty($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'suggestions' => []]);
    exit();
}

require_once __DIR__ . '/../../../../config/db_connect.php';

$query = trim($_GET['q'] ?? $_POST['q'] ?? '');

$standard_catalogue = [
    'Split Air Conditioner',
    'Window Air Conditioner',
    'Ceiling Fan',
    'Pedestal Fan',
    'Desktop Computer System',
    'Laptop Computer',
    'LaserJet Printer',
    'Photocopy Machine',
    'Office Desk (Wooden)',
    'Office Desk (Steel)',
    'Executive Swivel Chair',
    'Visitor Chairs (Set)',
    'Steel Almirah / Cupboard',
    'Filing Cabinet (4-Drawer)',
    'Biological Sample Refrigerator',
    'Deep Freezer (-20C)',
    'Autoclave Sterilizer',
    'Clinical Centrifuge',
    'Compound Optical Microscope',
    'Surgical Examination Table',
    'Electronic Weighing Scale',
    'Veterinary Diagnostic Kit',
    'Liquid Nitrogen Container (Cryocan)',
    'Artificial Insemination Kit',
    'Medicine Display Rack',
    'Water Dispenser',
    'Online UPS Backup Unit',
    'Standby Diesel Generator',
    'Fire Extinguisher (Dry Powder)'
];

$suggestions = [];
$seen = [];

// 1. Query database for matching previously saved items
if (!empty($query)) {
    $search_pattern = '%' . $query . '%';
    $stmt = $mysqli->prepare("
        SELECT DISTINCT inventory_item 
        FROM building_inventories 
        WHERE is_active = 1 AND inventory_item LIKE ? 
        ORDER BY inventory_item ASC 
        LIMIT 15
    ");
    if ($stmt) {
        $stmt->bind_param("s", $search_pattern);
        $stmt->execute();
        $res = $stmt->get_result();
        while ($row = $res->fetch_assoc()) {
            $item_name = trim($row['inventory_item']);
            $lower = strtolower($item_name);
            if (!empty($item_name) && !isset($seen[$lower])) {
                $seen[$lower] = true;
                $suggestions[] = $item_name;
            }
        }
        $stmt->close();
    }

    // 2. Also match from standard catalogue to ensure robust auto-suggest
    foreach ($standard_catalogue as $cat_item) {
        if (stripos($cat_item, $query) !== false) {
            $lower = strtolower($cat_item);
            if (!isset($seen[$lower])) {
                $seen[$lower] = true;
                $suggestions[] = $cat_item;
            }
        }
        if (count($suggestions) >= 12) break;
    }
} else {
    // If empty query, return top 10 recent/common items
    $res = $mysqli->query("
        SELECT DISTINCT inventory_item 
        FROM building_inventories 
        WHERE is_active = 1 AND inventory_item IS NOT NULL AND inventory_item != ''
        ORDER BY id DESC 
        LIMIT 8
    ");
    if ($res) {
        while ($row = $res->fetch_assoc()) {
            $item_name = trim($row['inventory_item']);
            $lower = strtolower($item_name);
            if (!empty($item_name) && !isset($seen[$lower])) {
                $seen[$lower] = true;
                $suggestions[] = $item_name;
            }
        }
    }
    // Fill up to 6 from standard catalogue
    foreach ($standard_catalogue as $cat_item) {
        $lower = strtolower($cat_item);
        if (!isset($seen[$lower])) {
            $seen[$lower] = true;
            $suggestions[] = $cat_item;
        }
        if (count($suggestions) >= 8) break;
    }
}

echo json_encode([
    'success' => true,
    'query' => $query,
    'suggestions' => $suggestions
]);
