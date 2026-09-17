<?php
// pages/modules/veterinary/annual_pasture_lands.php
// Consolidated into pasture.php (Tab: lands)
$queryString = $_SERVER['QUERY_STRING'] ?? '';
$params = [];
if (!empty($queryString)) {
    parse_str($queryString, $params);
}
$params['tab'] = 'lands';
header("Location: pasture.php?" . http_build_query($params), true, 302);
exit();