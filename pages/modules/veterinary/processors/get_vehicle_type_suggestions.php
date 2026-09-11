<?php
/**
 * pages/modules/veterinary/processors/get_vehicle_type_suggestions.php
 * Real-time Auto-suggest endpoint for Vehicle Types (including non-standard equipment)
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!headers_sent()) {
    header('Content-Type: application/json');
}

if (!isset($_SESSION['logged_in']) || empty($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'suggestions' => []]);
    exit();
}

require_once __DIR__ . '/../../../../config/db_connect.php';

$query = trim($_GET['q'] ?? $_POST['q'] ?? '');

$standard_catalogue = [
    'Single Cab (4x4)',
    'Double Cab',
    'Motorbike',
    'Truck Logistics',
    'Van / Emergency Utility',
    'Tractor (Agricultural / Hauling)',
    'Trailer',
    'Water Bowser',
    'Mobile Veterinary Clinic',
    'Lorry (Heavy Duty)',
    'Animal Ambulance',
    'Three Wheeler / Tri-Shaw',
    'Mini Truck',
    'Jeep / SUV'
];

$suggestions = [];
$seen = [];

// 1. Query database for matching previously saved vehicle types
if (!empty($query)) {
    $search_pattern = '%' . $query . '%';
    $stmt = $mysqli->prepare("
        SELECT DISTINCT vehicle_type 
        FROM registered_vehicles 
        WHERE is_active = 1 AND vehicle_type LIKE ? 
        ORDER BY vehicle_type ASC 
        LIMIT 15
    ");
    if ($stmt) {
        $stmt->bind_param("s", $search_pattern);
        $stmt->execute();
        $res = $stmt->get_result();
        while ($row = $res->fetch_assoc()) {
            $type_name = trim($row['vehicle_type']);
            $lower = strtolower($type_name);
            if (!empty($type_name) && !isset($seen[$lower])) {
                $seen[$lower] = true;
                $suggestions[] = $type_name;
            }
        }
        $stmt->close();
    }

    // 2. Also match from standard catalogue to ensure comprehensive suggestions for non-standard equipment
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
    // If empty query, return distinct recently registered vehicle types
    $res = $mysqli->query("
        SELECT DISTINCT vehicle_type 
        FROM registered_vehicles 
        WHERE is_active = 1 AND vehicle_type IS NOT NULL AND vehicle_type != ''
        ORDER BY id DESC 
        LIMIT 8
    ");
    if ($res) {
        while ($row = $res->fetch_assoc()) {
            $type_name = trim($row['vehicle_type']);
            $lower = strtolower($type_name);
            if (!empty($type_name) && !isset($seen[$lower])) {
                $seen[$lower] = true;
                $suggestions[] = $type_name;
            }
        }
    }
    // Fill up from standard catalogue
    foreach ($standard_catalogue as $cat_item) {
        $lower = strtolower($cat_item);
        if (!isset($seen[$lower])) {
            $seen[$lower] = true;
            $suggestions[] = $cat_item;
        }
        if (count($suggestions) >= 10) break;
    }
}

echo json_encode([
    'success' => true,
    'query' => $query,
    'suggestions' => $suggestions
]);
