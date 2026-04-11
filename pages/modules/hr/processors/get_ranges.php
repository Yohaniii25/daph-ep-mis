<?php
require_once '../../../../config/db_connect.php';

header('Content-Type: text/html; charset=utf-8');

if (isset($_GET['district_id'])) {
    $district_id = intval($_GET['district_id']);
    

    $stmt = $mysqli->prepare("SELECT id, name FROM veterinary_ranges WHERE district_id = ? ORDER BY name ASC");
    
    
    $stmt->bind_param("i", $district_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        echo '<option value="">Select Range Office</option>';
        while ($row = $result->fetch_assoc()) {
            echo '<option value="' . $row['id'] . '">' . htmlspecialchars($row['name']) . '</option>';
        }
    } else {
        echo '<option value="">No ranges found for this district</option>';
    }
    
    $stmt->close();
} else {
    echo '<option value="">Please select a district</option>';
}
?>