<?php
session_start();
$qs = !empty($_SERVER['QUERY_STRING']) ? '?' . $_SERVER['QUERY_STRING'] . '&tab=monitoring' : '?tab=monitoring';
header("Location: projects_progress.php" . $qs);
exit();