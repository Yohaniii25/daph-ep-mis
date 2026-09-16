<?php
/**
 * pages/modules/veterinary/processors/get_vehicle_latest_status.php
 * Fetches the vehicle's most recent active trip metrics (ending fuel balance, ending milometer reading)
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../../../config/db_connect.php';

header('Content-Type: application/json');

if (!isset($_SESSION['logged_in'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access.']);
    exit();
}

$vehicle_id = filter_input(INPUT_GET, 'vehicle_id', FILTER_VALIDATE_INT);
if (!$vehicle_id) {
    echo json_encode(['success' => false, 'message' => 'Invalid vehicle identifier.']);
    exit();
}

// Query the latest active running chart trip for this vehicle
$stmt = $mysqli->prepare("
    SELECT id, trip_date, milometer_in, fuel_balance, fuel_position_in_tank, driver_name, driver_initials
    FROM vehicle_running_charts
    WHERE vehicle_id = ? AND is_active = 1
    ORDER BY trip_date DESC, id DESC
    LIMIT 1
");

if (!$stmt) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $mysqli->error]);
    exit();
}

$stmt->bind_param("i", $vehicle_id);
$stmt->execute();
$res = $stmt->get_result();

if ($row = $res->fetch_assoc()) {
    echo json_encode([
        'success'         => true,
        'has_record'      => true,
        'last_trip_id'    => intval($row['id']),
        'last_trip_date'  => $row['trip_date'],
        'milometer_out'   => floatval($row['milometer_in']),
        'fuel_balance'    => floatval($row['fuel_balance']),
        'driver_name'     => $row['driver_name'],
        'driver_initials' => $row['driver_initials']
    ]);
} else {
    // No previous record exists for this vehicle
    echo json_encode([
        'success'         => true,
        'has_record'      => false,
        'last_trip_id'    => null,
        'last_trip_date'  => null,
        'milometer_out'   => 0.0,
        'fuel_balance'    => 0.0,
        'driver_name'     => '',
        'driver_initials' => ''
    ]);
}

$stmt->close();
exit();
