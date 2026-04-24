<?php
require_once '../../../config/db_connect.php';

$searchTerm = $_GET['term'] ?? '';

// Search by programme name from the master table
$sql = "SELECT ap.id, ap.programme_year, ap.place, ap.activity_description, mpt.programme_name 
        FROM advanced_programmes ap
        JOIN master_programme_types mpt ON ap.type_id = mpt.id
        WHERE mpt.programme_name LIKE '%$searchTerm%' 
        LIMIT 10";

$result = $mysqli->query($sql);
$data = [];

while ($row = $result->fetch_assoc()) {
    $data[] = [
        'id'    => $row['id'],
        'label' => $row['programme_name'] . " (" . $row['programme_year'] . ")", // What user sees
        'value' => $row['programme_name'], // What stays in the input
        'year'  => $row['programme_year'],
        'place' => $row['place'],
        'desc'  => $row['activity_description']
    ];
}

echo json_encode($data);