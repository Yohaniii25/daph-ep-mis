<?php
// Standalone Training module is embedded into Projects and Dairy Hub workflows.
// Redirect any legacy links to Projects and Monitoring training tab.
session_start();
$range_param = isset($_GET['range_id']) ? '&range_id=' . intval($_GET['range_id']) : '';
header("Location: projects_progress.php?tab=trainings" . $range_param);
exit();