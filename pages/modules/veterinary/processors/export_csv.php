<?php
session_start();
require_once __DIR__ . '/../../../../config/db_connect.php';

/** @var mysqli $mysqli */
global $mysqli;

if (!isset($_SESSION['logged_in']) || empty($_SESSION['user_id'])) {
    header("Location: ../../../../index.php");
    exit();
}

$user_role = $_SESSION['role'] ?? '';
$is_supervisory = in_array($user_role, [
    'district_dd',
    'deputy_director_district',
    'administrator',
    'provincial_director',
    'deputy_director_hq_1',
    'deputy_director_hq_2'
]);

// Non-supervisory veterinary users can ONLY access/export their own assigned range
if (!$is_supervisory && !empty($_SESSION['range_id'])) {
    $range_id = (int)$_SESSION['range_id'];
} else {
    $range_id = isset($_GET['range_id']) ? (int)$_GET['range_id'] : (int)($_SESSION['range_id'] ?? 0);
}

if (empty($range_id)) {
    die("Unauthorized or missing range identification.");
}
$filename = "Animal_Health_Report_" . date('Y-m-d') . ".csv";

header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="' . $filename . '";');

$output = fopen('php://output', 'w');
fputcsv($output, ['Date', 'Farmer Reg No', 'Disease', 'Vaccine', 'Count', 'Doses', 'Remarks']);

$query = "SELECT date, farmer_reg_no, disease_name, vaccine_name, occurrence_count, doses, treatment_details 
          FROM animal_health_records WHERE range_id = ?";
$stmt = $mysqli->prepare($query);
$stmt->bind_param("i", $range_id);
$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    fputcsv($output, $row);
}
fclose($output);
exit();