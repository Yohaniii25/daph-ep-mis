<?php
// pages/modules/veterinary/annual_pasture_yields.php
// Consolidated into pasture.php (Tab: yields)
$queryString = $_SERVER['QUERY_STRING'] ?? '';
$params = [];
if (!empty($queryString)) {
    parse_str($queryString, $params);
}
$params['tab'] = 'yields';
header("Location: pasture.php?" . http_build_query($params), true, 302);
exit();
