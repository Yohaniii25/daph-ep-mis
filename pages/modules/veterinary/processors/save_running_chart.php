<?php
/**
 * pages/modules/veterinary/processors/save_running_chart.php
 * Persists vehicle daily running chart logs, mileage calculations and fuel metrics
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../../../config/db_connect.php';
if (!headers_sent()) {
    header('Content-Type: application/json');
}

$allowed_roles = ['veterinary_surgeon', 'government_veterinary_surgeon', 'additional_veterinary_surgeon', 'provincial_director', 'district_dd', 'deputy_director_district'];
if (!isset($_SESSION['logged_in']) || !in_array($_SESSION['role'], $allowed_roles)) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized entry context execution terminated.']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = $_SESSION['user_id'] ?? null;
    $vehicle_id = isset($_POST['vehicle_id']) ? filter_var($_POST['vehicle_id'], FILTER_VALIDATE_INT) : 0;
    $trip_date = isset($_POST['trip_date']) ? trim($_POST['trip_date']) : '';
    $driver_name = isset($_POST['driver_name']) ? trim(htmlspecialchars($_POST['driver_name'])) : '';
    $driver_initials = isset($_POST['driver_initials']) ? strtoupper(trim(htmlspecialchars($_POST['driver_initials']))) : '';
    $time_out = isset($_POST['time_out']) ? trim($_POST['time_out']) : '';
    $time_in = isset($_POST['time_in']) ? trim($_POST['time_in']) : '';
    $route_places_visited = isset($_POST['route_places_visited']) ? trim(htmlspecialchars($_POST['route_places_visited'])) : '';
    $purpose_of_trip = isset($_POST['purpose_of_trip']) ? trim(htmlspecialchars($_POST['purpose_of_trip'])) : '';

    $milometer_out = isset($_POST['milometer_out']) ? filter_var($_POST['milometer_out'], FILTER_VALIDATE_FLOAT) : false;
    $milometer_in = isset($_POST['milometer_in']) ? filter_var($_POST['milometer_in'], FILTER_VALIDATE_FLOAT) : false;

    $fuel_position_in_tank = isset($_POST['fuel_position_in_tank']) ? floatval($_POST['fuel_position_in_tank']) : 0.0;
    $fuel_drawn = isset($_POST['fuel_drawn']) ? floatval($_POST['fuel_drawn']) : 0.0;
    $fuel_consumed = isset($_POST['fuel_consumed']) ? floatval($_POST['fuel_consumed']) : 0.0;
    $engine_oil_drawn = isset($_POST['engine_oil_drawn']) ? floatval($_POST['engine_oil_drawn']) : 0.0;
    $remarks = isset($_POST['remarks']) ? trim(htmlspecialchars($_POST['remarks'])) : '';

    // Basic required validations
    if (!$user_id || !$vehicle_id || empty($trip_date) || empty($driver_name) || empty($driver_initials) || 
        empty($time_out) || empty($time_in) || empty($route_places_visited) || empty($purpose_of_trip) ||
        $milometer_out === false || $milometer_in === false) {
        echo json_encode(['success' => false, 'message' => 'Please provide all mandatory fields accurately.']);
        exit();
    }

    if ($milometer_in < $milometer_out) {
        echo json_encode(['success' => false, 'message' => 'Milometer Reading-In must be greater than or equal to Milometer Reading-Out.']);
        exit();
    }

    // Mileage & Fuel math calculations
    $total_mileage = round($milometer_in - $milometer_out, 2);

    // Fetch previous ending fuel balance for this vehicle
    $prev_fuel_balance = 0.0;
    $prev_stmt = $mysqli->prepare("SELECT fuel_balance FROM vehicle_running_charts WHERE vehicle_id = ? AND is_active = 1 ORDER BY trip_date DESC, id DESC LIMIT 1");
    if ($prev_stmt) {
        $prev_stmt->bind_param("i", $vehicle_id);
        $prev_stmt->execute();
        $prev_res = $prev_stmt->get_result()->fetch_assoc();
        if ($prev_res && isset($prev_res['fuel_balance'])) {
            $prev_fuel_balance = floatval($prev_res['fuel_balance']);
        }
        $prev_stmt->close();
    } elseif (isset($_POST['prev_fuel_balance'])) {
        $prev_fuel_balance = floatval($_POST['prev_fuel_balance']);
    }

    // Dynamic Fuel Tracking: Automate tank balance calculation
    // Current "Fuel Position in Tank" = Previous Balance + newly purchased/drawn fuel
    if ($fuel_position_in_tank <= 0 || ($prev_fuel_balance > 0 || $fuel_drawn > 0)) {
        $fuel_position_in_tank = round($prev_fuel_balance + $fuel_drawn, 2);
    }
    $fuel_balance = round($fuel_position_in_tank - $fuel_consumed, 2);
    if ($fuel_balance < 0) {
        echo json_encode(['success' => false, 'message' => 'Fuel consumed cannot exceed total fuel available in tank (' . $fuel_position_in_tank . ' L).']);
        exit();
    }

    $miles_per_gallon = ($fuel_consumed > 0) ? round($total_mileage / $fuel_consumed, 2) : 0.00;

    // Resolve vehicle district and range
    $district_id = $_SESSION['district_id'] ?? 0;
    $range_id = $_SESSION['range_id'] ?? 0;
    $v_stmt = $mysqli->prepare("SELECT district_id, range_id FROM registered_vehicles WHERE id = ?");
    if ($v_stmt) {
        $v_stmt->bind_param("i", $vehicle_id);
        $v_stmt->execute();
        $v_res = $v_stmt->get_result()->fetch_assoc();
        if ($v_res) {
            if (!empty($v_res['district_id'])) $district_id = intval($v_res['district_id']);
            if (!empty($v_res['range_id'])) $range_id = intval($v_res['range_id']);
        }
        $v_stmt->close();
    }

    // Insert record
    $ins_query = "
        INSERT INTO vehicle_running_charts (
            vehicle_id, user_id, district_id, range_id, trip_date,
            driver_name, driver_initials, time_out, time_in,
            route_places_visited, purpose_of_trip,
            milometer_out, milometer_in, total_mileage,
            fuel_position_in_tank, fuel_drawn, fuel_consumed, fuel_balance,
            miles_per_gallon, engine_oil_drawn, remarks, is_active
        ) VALUES (
            ?, ?, ?, ?, ?,
            ?, ?, ?, ?,
            ?, ?,
            ?, ?, ?,
            ?, ?, ?, ?,
            ?, ?, ?, 1
        )
    ";

    $stmt = $mysqli->prepare($ins_query);
    if ($stmt) {
        $stmt->bind_param(
            "iiiisssssssddddddddds",
            $vehicle_id, $user_id, $district_id, $range_id, $trip_date,
            $driver_name, $driver_initials, $time_out, $time_in,
            $route_places_visited, $purpose_of_trip,
            $milometer_out, $milometer_in, $total_mileage,
            $fuel_position_in_tank, $fuel_drawn, $fuel_consumed, $fuel_balance,
            $miles_per_gallon, $engine_oil_drawn, $remarks
        );

        if ($stmt->execute()) {
            echo json_encode([
                'success' => true,
                'message' => 'Running chart log recorded successfully.',
                'total_mileage' => $total_mileage,
                'fuel_balance' => $fuel_balance,
                'miles_per_gallon' => $miles_per_gallon
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Database error: ' . $stmt->error]);
        }
        $stmt->close();
    } else {
        echo json_encode(['success' => false, 'message' => 'Statement preparation failed: ' . $mysqli->error]);
    }
}
