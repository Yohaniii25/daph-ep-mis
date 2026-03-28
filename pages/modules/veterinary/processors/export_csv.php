<?php
session_start();
require_once '../../../../config/db_connect.php';

$range_id = $_GET['range_id'];
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