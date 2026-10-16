<?php
// pages/modules/veterinary/dairy_project_summary.php
// Redirect to integrated dairy_hub.php module

$qs = !empty($_SERVER['QUERY_STRING']) ? '?' . $_SERVER['QUERY_STRING'] : '';
header("Location: dairy_hub.php" . $qs);
exit();
