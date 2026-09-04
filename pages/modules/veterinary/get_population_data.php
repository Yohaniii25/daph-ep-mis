<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../../../config/db_connect.php';

// Access validation check
if (!isset($_SESSION['logged_in']) || $_SESSION['role'] !== 'veterinary_surgeon') {
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

$range_id = $_SESSION['range_id'] ?? null;
$year = isset($_GET['year']) ? intval($_GET['year']) : 2025;
$pop_type = isset($_GET['pop_type']) ? $_GET['pop_type'] : 'Total Population';

// Decode selected multi-select ethnicities array (with sanitation fallbacks)
$ethnicities = isset($_GET['ethnicities']) ? json_decode($_GET['ethnicities'], true) : ['Sinhala', 'Tamil', 'Muslim'];
if (!is_array($ethnicities) || empty($ethnicities)) {
    $ethnicities = ['Sinhala', 'Tamil', 'Muslim'];
}

if (empty($range_id)) {
    echo json_encode([]);
    exit();
}

// Prepare dynamic placeholder mapping arrays for SQL IN clause injection
$clause_placeholders = implode(',', array_fill(0, count($ethnicities), '?'));

// Handle 'Total Population' aggregation metric queries dynamically
if ($pop_type === 'Total Population') {
    $sql = "
        SELECT ethnicity, SUM(population_count) AS total_count 
        FROM human_populations 
        WHERE range_id = ? AND year = ? AND population_type IN ('Male', 'Female') AND ethnicity IN ($clause_placeholders)
        GROUP BY ethnicity
    ";
} else {
    $sql = "
        SELECT ethnicity, SUM(population_count) AS total_count 
        FROM human_populations 
        WHERE range_id = ? AND year = ? AND population_type = ? AND ethnicity IN ($clause_placeholders)
        GROUP BY ethnicity
    ";
}

$stmt = $mysqli->prepare($sql);

if ($stmt) {
    // Dynamic binding handling based on target type selections
    $bind_types = "ii"; 
    $bind_params = [$range_id, $year];
    
    if ($pop_type !== 'Total Population') {
        $bind_types .= "s";
        $bind_params[] = $pop_type;
    }
    
    foreach ($ethnicities as $eth) {
        $bind_types .= "s";
        $bind_params[] = $eth;
    }
    
    $stmt->bind_param($bind_types, ...$bind_params);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $output_rows = [];
    while ($row = $result->fetch_assoc()) {
        $output_rows[] = [
            'year' => $year,
            'ethnicity' => $row['ethnicity'],
            'count' => intval($row['total_count'])
        ];
    }
    
    $stmt->close();
    
    header('Content-Type: application/json');
    echo json_encode($output_rows);
} else {
    echo json_encode(['error' => 'Query generation failed']);
}