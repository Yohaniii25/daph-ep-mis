<?php
require_once '../../../../config/db_connect.php';

echo "Database Connection Status: ";
if ($mysqli->connect_error) {
    echo "FAILED - " . $mysqli->connect_error;
} else {
    echo "OK";
}
echo "<br>";

// Check if table exists
$tables_result = $mysqli->query("SHOW TABLES LIKE 'veterinary_ranges'");
echo "Table 'veterinary_ranges' exists: " . ($tables_result->num_rows > 0 ? "YES" : "NO") . "<br>";

// Check data
$data_result = $mysqli->query("SELECT COUNT(*) as count FROM veterinary_ranges");
$row = $data_result->fetch_assoc();
echo "Total ranges in database: " . $row['count'] . "<br><br>";

// Check ranges by district
echo "<strong>Ranges by District:</strong><br>";
$district_result = $mysqli->query("
    SELECT d.id, d.name, COUNT(vr.id) as range_count 
    FROM districts d
    LEFT JOIN veterinary_ranges vr ON d.id = vr.district_id
    GROUP BY d.id, d.name
");

while ($district = $district_result->fetch_assoc()) {
    echo "District: " . $district['name'] . " (ID: " . $district['id'] . ") - Ranges: " . $district['range_count'] . "<br>";
    
    // Show ranges for this district
    $range_result = $mysqli->query("SELECT id, name FROM veterinary_ranges WHERE district_id = " . $district['id'] . " ORDER BY name");
    while ($vr = $range_result->fetch_assoc()) {
        echo "&nbsp;&nbsp;&nbsp;- " . $vr['name'] . "<br>";
    }
}
?>
