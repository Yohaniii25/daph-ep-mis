<?php
// pages/modules/farm/chicks_death_details.php -> Redirect to revamped Chick Details Module
$query = !empty($_SERVER['QUERY_STRING']) ? '?' . $_SERVER['QUERY_STRING'] : '';
header("Location: chick_details.php" . $query);
exit();
