<?php
require_once 'config/db_connect.php';

echo "<h3>Checking Stock Tables...</h3>";

// Check if parent_stock_flocks has data
$check_flocks = $mysqli->query("SELECT COUNT(*) as count FROM parent_stock_flocks");
$result = $check_flocks->fetch_assoc();
$flock_count = $result['count'];

echo "<p><strong>Flocks in database:</strong> " . $flock_count . "</p>";

if ($flock_count == 0) {
    echo "<p style='color:red;'><strong>⚠️ No flocks found! Adding sample data...</strong></p>";
    
    $insert = "INSERT INTO `parent_stock_flocks` (`flock_code`, `region`, `assigned_cages`, `initial_count`, `current_count`) VALUES
    ('SAT-CB-2026-01', 'Sathurukondan', 'C-01, C-02', 5000, 4850),
    ('THM-CB-2026-02', 'Thampalakamam', 'B-05', 5000, 4920),
    ('TRK-HB-2026-01', 'Thirukkovil', 'A-10', 3000, 2870)";
    
    if ($mysqli->query($insert)) {
        echo "<p style='color:green;'><strong>✅ Sample flocks added successfully!</strong></p>";
    } else {
        echo "<p style='color:red;'><strong>❌ Error adding flocks:</strong> " . $mysqli->error . "</p>";
    }
} else {
    echo "<p style='color:green;'><strong>✅ Flocks found!</strong></p>";
    
    // Display existing flocks
    $list = $mysqli->query("SELECT id, flock_code, region, current_count FROM parent_stock_flocks");
    echo "<table border='1' cellpadding='10'>";
    echo "<tr><th>ID</th><th>Code</th><th>Region</th><th>Current Count</th></tr>";
    while ($row = $list->fetch_assoc()) {
        echo "<tr><td>" . $row['id'] . "</td><td>" . $row['flock_code'] . "</td><td>" . $row['region'] . "</td><td>" . $row['current_count'] . "</td></tr>";
    }
    echo "</table>";
}

echo "<p><br><a href='pages/modules/farm/poultry_hatchery.php'>&larr; Back to Poultry Operations</a></p>";
?>
