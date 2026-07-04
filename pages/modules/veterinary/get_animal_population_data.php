<?php
session_start();
require_once '../../../config/db_connect.php';

if (!isset($_SESSION['logged_in']) || $_SESSION['role'] !== 'veterinary_surgeon') {
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

$range_id = $_SESSION['range_id'] ?? null;
$year = isset($_GET['year']) ? intval($_GET['year']) : 2025;
$pop_type = isset($_GET['pop_type']) ? $_GET['pop_type'] : 'Total Population';

$animals = isset($_GET['animals']) ? json_decode($_GET['animals'], true) : ['Cow', 'Buffalo', 'Goat', 'Chicken', 'Pig', 'Others'];
if (!is_array($animals) || empty($animals)) {
    $animals = ['Cow', 'Buffalo', 'Goat', 'Chicken', 'Pig', 'Others'];
}

if (empty($range_id)) {
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
