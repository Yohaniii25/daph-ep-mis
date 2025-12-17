<?php

require_once 'includes/header.php';  

$role = $_SESSION['role'] ?? '';

$dashboard_map = [
    'provincial_director' => 'pages/dashboard/provincial_director.php',
    'district_dd'         => 'pages/dashboard/district.php',
    'veterinary_surgeon'  => 'pages/dashboard/veterinary_office.php',
    'training_officer'    => 'pages/dashboard/training.php',
    'sms'                 => 'pages/dashboard/sms.php',
    'farms_dd'            => 'pages/dashboard/farms.php',
    'finance_admin'       => 'pages/dashboard/finance.php',
    'administrator'       => 'pages/dashboard/adminstrator.php',
    'planning_officer'    => 'pages/dashboard/planning_officer.php'
];

$target = $dashboard_map[$role] ?? 'pages/dashboard/provincial_director.php';

if (!file_exists($target)) {
    die("Dashboard not configured yet.");
}


require_once $target;

require_once 'includes/footer.php';