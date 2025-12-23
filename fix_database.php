<?php
require_once 'config/db_connect.php';

// Fix the AUTO_INCREMENT for staff table if it's missing
$sql = "ALTER TABLE staff MODIFY id int(11) NOT NULL AUTO_INCREMENT";

if ($mysqli->query($sql)) {
    echo "✓ Database fixed successfully!<br>";
    echo "The 'id' column in the 'staff' table is now set to AUTO_INCREMENT.";
} else {
    echo "Error: " . $mysqli->error;
}

$mysqli->close();
?>
