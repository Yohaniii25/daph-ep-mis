<?php
// Redirect to the unified Mobile Clinics & Drug Distribution module
header("Location: mobile_clinics.php" . (!empty($_SERVER['QUERY_STRING']) ? '?' . $_SERVER['QUERY_STRING'] : ''));
exit();
