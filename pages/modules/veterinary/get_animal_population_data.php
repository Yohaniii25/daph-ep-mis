<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../../../config/db_connect.php';

/** @var mysqli $mysqli */
global $mysqli;

$allowed_roles = [
    'veterinary_surgeon',
    'district_dd',
    'deputy_director_district',
    'administrator',
    'provincial_director',
    'deputy_director_hq_1',
    'deputy_director_hq_2',
    'admin'
];

if (!isset($_SESSION['logged_in']) || !in_array($_SESSION['role'] ?? '', $allowed_roles, true)) {
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

$range_id = isset($_GET['range_id']) && intval($_GET['range_id']) > 0 
    ? intval($_GET['range_id']) 
    : intval($_SESSION['range_id'] ?? 0);
$year = isset($_GET['year']) ? intval($_GET['year']) : 2025;
$pop_type = isset($_GET['pop_type']) ? $_GET['pop_type'] : 'Total Population';

$animals = isset($_GET['animals']) ? json_decode($_GET['animals'], true) : ['Cow', 'Buffalo', 'Goat', 'Chicken', 'Pig', 'Others'];
if (!is_array($animals) || empty($animals)) {
    $animals = ['Cow', 'Buffalo', 'Goat', 'Chicken', 'Pig', 'Others'];
}

if ($range_id <= 0) {
    echo json_encode([]);
    exit();
}

$placeholders = implode(',', array_fill(0, count($animals), '?'));

if ($pop_type === 'Total Population') {
    $sql = "
        SELECT animal_type, SUM(quantity) AS total_count
        FROM animal_populations
        WHERE range_id = ? AND year = ? AND animal_type IN ($placeholders)
        GROUP BY animal_type
    ";
} else {
    $sql = "
        SELECT animal_type, SUM(quantity) AS total_count
        FROM animal_populations
        WHERE range_id = ? AND year = ? AND population_type = ? AND animal_type IN ($placeholders)
        GROUP BY animal_type
    ";
}

$stmt = $mysqli->prepare($sql);

if ($stmt) {
    $bind_types = 'ii';
    $bind_params = [$range_id, $year];

    if ($pop_type !== 'Total Population') {
        $bind_types .= 's';
        $bind_params[] = $pop_type;
    }

    foreach ($animals as $animal) {
        $bind_types .= 's';
        $bind_params[] = $animal;
    }

    $stmt->bind_param($bind_types, ...$bind_params);
    $stmt->execute();
    $result = $stmt->get_result();

    $output_rows = [];
    while ($row = $result->fetch_assoc()) {
        $output_rows[] = [
            'year' => $year,
            'animal_type' => $row['animal_type'],
            'count' => intval($row['total_count'])
        ];
    }

    $stmt->close();

    header('Content-Type: application/json');
    echo json_encode($output_rows);
} else {
    echo json_encode(['error' => 'Query generation failed']);
}
