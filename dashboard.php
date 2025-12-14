<?php
// dashboard.php → Smart redirector (THIS FILE IS PERFECT — DO NOT CHANGE)
require_once 'includes/header.php';  // Only this one starts the page

$role = $_SESSION['role'] ?? '';

$dashboard_map = [
    'provincial_director' => 'pages/dashboard/provincial_director.php',
    'district_dd'         => 'pages/dashboard/district.php',
    'veterinary_surgeon'  => 'pages/dashboard/veterinary_office.php',
    'ldo'                 => 'pages/dashboard/veterinary_office.php',
    'sms'                 => 'pages/dashboard/sms.php',
    'farms_dd'            => 'pages/dashboard/farms.php',
    'admin'               => 'pages/dashboard/admin_finance.php',
    'planning_officer'    => 'pages/dashboard/admin_finance.php',
    'accountant'          => 'pages/dashboard/admin_finance.php',
];

$target = $dashboard_map[$role] ?? 'pages/dashboard/provincial_director.php';

if (!file_exists($target)) {
    die("Dashboard not configured yet.");
}

// This file ONLY contains content — no header/sidebar/footer
require_once $target;

require_once 'includes/footer.php';  // Only here we close the page
?>