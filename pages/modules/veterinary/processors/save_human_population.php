<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../../../../config/db_connect.php';

header('Content-Type: application/json');

// Check authentication and role
if (!isset($_SESSION['logged_in']) || $_SESSION['role'] !== 'veterinary_surgeon') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access.']);
    exit();
}

$range_id = intval($_SESSION['range_id'] ?? 0);
if ($range_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Your account is not assigned to any Veterinary Range.']);
    exit();
}

$action = $_POST['action'] ?? $_GET['action'] ?? 'save';

if ($action === 'get_list') {
    // Return all human population records for this range grouped by year and ethnicity
    $stmt = $mysqli->prepare("
        SELECT year, ethnicity, population_type, population_count 
        FROM human_populations 
        WHERE range_id = ? 
        ORDER BY year DESC, ethnicity ASC
    ");
    if (!$stmt) {
        echo json_encode(['success' => false, 'message' => 'Failed to prepare query: ' . $mysqli->error]);
        exit();
    }
    $stmt->bind_param("i", $range_id);
    $stmt->execute();
    $result = $stmt->get_result();

    $grouped = [];
    while ($row = $result->fetch_assoc()) {
        $key = $row['year'] . '___' . $row['ethnicity'];
        if (!isset($grouped[$key])) {
            $grouped[$key] = [
                'year' => intval($row['year']),
                'ethnicity' => $row['ethnicity'],
                'male' => 0,
                'female' => 0,
                'households' => 0
            ];
        }
        if ($row['population_type'] === 'Male') {
            $grouped[$key]['male'] = intval($row['population_count']);
        } elseif ($row['population_type'] === 'Female') {
            $grouped[$key]['female'] = intval($row['population_count']);
        } elseif ($row['population_type'] === 'Households') {
            $grouped[$key]['households'] = intval($row['population_count']);
        }
    }
    $stmt->close();

    $data = array_values($grouped);
    foreach ($data as &$item) {
        $item['total'] = $item['male'] + $item['female'];
    }

    echo json_encode(['success' => true, 'data' => $data]);
    exit();
}

if ($action === 'delete') {
    $year = intval($_POST['year'] ?? 0);
    $ethnicity = trim($_POST['ethnicity'] ?? '');

    if ($year <= 0 || empty($ethnicity)) {
        echo json_encode(['success' => false, 'message' => 'Invalid parameters for deletion.']);
        exit();
    }

    $stmt = $mysqli->prepare("DELETE FROM human_populations WHERE range_id = ? AND year = ? AND ethnicity = ?");
    if (!$stmt) {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $mysqli->error]);
        exit();
    }
    $stmt->bind_param("iis", $range_id, $year, $ethnicity);
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => "Population records for {$ethnicity} ({$year}) deleted successfully."]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Delete failed: ' . $stmt->error]);
    }
    $stmt->close();
    exit();
}

if ($action === 'save') {
    $year = intval($_POST['year'] ?? 0);
    $ethnicity = trim($_POST['ethnicity'] ?? '');

    $allowed_ethnicities = ['Sinhala', 'Tamil', 'Muslim'];
    if (!in_array($ethnicity, $allowed_ethnicities, true)) {
        echo json_encode(['success' => false, 'message' => 'Please select a valid ethnicity (Sinhala, Tamil, or Muslim).']);
        exit();
    }

    $male_count = max(0, intval($_POST['male_count'] ?? 0));
    $female_count = max(0, intval($_POST['female_count'] ?? 0));
    $households_count = max(0, intval($_POST['households_count'] ?? 0));

    if ($year < 2000 || $year > 2100) {
        echo json_encode(['success' => false, 'message' => 'Please provide a valid year between 2000 and 2100.']);
        exit();
    }

    // Array of population types and values to upsert
    $types = [
        'Male' => $male_count,
        'Female' => $female_count,
        'Households' => $households_count
    ];

    $check_stmt = $mysqli->prepare("
        SELECT id FROM human_populations 
        WHERE range_id = ? AND year = ? AND ethnicity = ? AND population_type = ?
    ");

    $insert_stmt = $mysqli->prepare("
        INSERT INTO human_populations (range_id, year, ethnicity, population_type, population_count) 
        VALUES (?, ?, ?, ?, ?)
    ");

    $update_stmt = $mysqli->prepare("
        UPDATE human_populations 
        SET population_count = ? 
        WHERE id = ?
    ");

    if (!$check_stmt || !$insert_stmt || !$update_stmt) {
        echo json_encode(['success' => false, 'message' => 'Failed to prepare database queries: ' . $mysqli->error]);
        exit();
    }

    $mysqli->begin_transaction();
    try {
        foreach ($types as $pop_type => $count) {
            $check_stmt->bind_param("iiss", $range_id, $year, $ethnicity, $pop_type);
            $check_stmt->execute();
            $check_res = $check_stmt->get_result();

            if ($row = $check_res->fetch_assoc()) {
                $record_id = intval($row['id']);
                $update_stmt->bind_param("ii", $count, $record_id);
                $update_stmt->execute();
            } else {
                $insert_stmt->bind_param("iissi", $range_id, $year, $ethnicity, $pop_type, $count);
                $insert_stmt->execute();
            }
        }
        $mysqli->commit();
        echo json_encode([
            'success' => true,
            'message' => "Demographic data for {$ethnicity} ({$year}) saved successfully.",
            'year' => $year
        ]);
    } catch (Exception $e) {
        $mysqli->rollback();
        echo json_encode(['success' => false, 'message' => 'Transaction error: ' . $e->getMessage()]);
    }

    $check_stmt->close();
    $insert_stmt->close();
    $update_stmt->close();
    exit();
}

echo json_encode(['success' => false, 'message' => 'Unknown action requested.']);
exit();
