<?php
// Redirect to the unified database-backed Mobile Clinics & Drug Distribution module
$qs = !empty($_SERVER['QUERY_STRING']) ? '?' . $_SERVER['QUERY_STRING'] : '';
header("Location: ../veterinary/mobile_clinics.php" . $qs);
exit();
