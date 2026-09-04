<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$role = $_SESSION['role'] ?? '';

$dashboard_map = [
    'provincial_director'    => 'pages/dashboard/provincial_director.php',
    'deputy_director_hq_1'   => 'pages/dashboard/planning_dd.php',
    'deputy_director_hq_2'   => 'pages/dashboard/provincial_director.php',
    'district_dd'                    => 'pages/dashboard/district.php',
    'deputy_director_district'       => 'pages/dashboard/district.php',
    'veterinary_surgeon'             => 'pages/dashboard/veterinary_office.php',
    'government_veterinary_surgeon'  => 'pages/dashboard/veterinary_office.php',
    'additional_veterinary_surgeon'  => 'pages/dashboard/veterinary_office.php',
    'training_officer'               => 'pages/dashboard/training.php',
    'sms'                            => 'pages/dashboard/sms.php',
    'farms_dd'                       => 'pages/dashboard/farms.php',
    'finance_admin'                  => 'pages/dashboard/finance.php',
    'administrator'                  => 'pages/dashboard/adminstrator.php',
    'planning_officer'               => 'pages/dashboard/planning_officer.php',
    'employee'                       => 'pages/dashboard/employee.php',
    'livestock_development_officer'  => 'pages/dashboard/employee.php',
    'development_officer'            => 'pages/dashboard/employee.php',
    'driver'                         => 'pages/dashboard/employee.php',
    'dispensary_assistant'           => 'pages/dashboard/employee.php',
    'department_laborer'             => 'pages/dashboard/employee.php',
    'night_watcher'                  => 'pages/dashboard/employee.php'
];

$target = $dashboard_map[$role] ?? 'pages/dashboard/provincial_director.php';

if (!file_exists($target)) {
    die("Dashboard not configured yet.");
}

require_once $target;