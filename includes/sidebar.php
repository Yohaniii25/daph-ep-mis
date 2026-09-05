<?php
$role = $_SESSION['role'] ?? '';
$is_pd = in_array($role, ['provincial_director']);
$is_planning_dd = ($role === 'deputy_director_hq_1');
$is_hr_user = ($role === 'administrator');
$is_finance_admin = ($role === 'finance_admin');
$is_planning_officer = ($role === 'planning_officer');
$is_sms = ($role === 'sms');
$is_farms_dd = ($role === 'farms_dd');
$is_training_officer = ($role === 'training_officer');
$is_district_dd = in_array($role, ['district_dd', 'deputy_director_district']);
$vs_roles = ['veterinary_surgeon', 'government_veterinary_surgeon', 'additional_veterinary_surgeon'];
$emp_roles = ['employee', 'livestock_development_officer', 'development_officer', 'driver', 'dispensary_assistant', 'department_laborer', 'night_watcher'];
$is_veterinary_surgeon = in_array($role, $vs_roles);
$is_employee = in_array($role, $emp_roles);


$base_path = '/daph-ep-mis/';
$current_path = $_SERVER['REQUEST_URI'] ?? '';
$current_file = basename(parse_url($current_path, PHP_URL_PATH) ?? '');
$is_dashboard = (strpos($current_path, 'dashboard') !== false);
require_once __DIR__ . '/approval_helper.php';
?>

<style>
    #layoutSidenav_nav {
        background: white !important;
        border-right: 1px solid #eee;
    }

    .sb-sidenav-menu {
        overflow-y: auto !important;
        overflow-x: hidden !important;
        max-height: 100vh !important;
    }

    .sb-sidenav-menu::-webkit-scrollbar {
        width: 6px;
    }

    .sb-sidenav-menu::-webkit-scrollbar-track {
        background: #f1f1f1;
    }

    .sb-sidenav-menu::-webkit-scrollbar-thumb {
        background: #888;
        border-radius: 3px;
    }

    .sb-sidenav-menu::-webkit-scrollbar-thumb:hover {
        background: #555;
    }

    .sb-sidenav-menu a {
        color: #333 !important;
        transition: background 0.3s, color 0.3s;
    }

    .sb-sidenav-menu a:hover {
        background: #500707 !important;
        color: white !important;
    }

    .sb-sidenav-menu a.bg-danger,
    .sb-sidenav-menu a.active {
        background: #500707 !important;
        color: white !important;
        font-weight: bold;
    }

    .border-white {
        border-color: #ddd !important;
    }

    .text-light,
    .text-light-50 {
        color: #333 !important;
    }

    .text-light-50 {
        color: #777 !important;
    }

    .text-danger {
        color: #dc3545 !important;
    }

    .text-danger:hover {
        color: white !important;
    }

    .horizontal-line {
        height: 1px;
        background: #ddd;
    }
</style>

<div id="layoutSidenav_nav">
    <nav class="sb-sidenav accordion" style="background:#fff; height:100%;">
        <div class="sb-sidenav-menu h-100 d-flex flex-column justify-content-between">
            <div style="flex: 1; overflow-y: auto; overflow-x: hidden;">

                <div class="text-center py-4 border-bottom">
                    <img src="<?= $base_path ?>assets/img/animal_health_logo.png" height="30" class="mb-2">
                </div>

                <!-- Main Menu Items -->
                <div class="sidebar-menu">
                    <a class="nav-link d-flex align-items-center px-4 py-3 <?= $is_dashboard ? 'bg-danger text-light' : '' ?>"
                        href="<?= $base_path ?>dashboard.php">
                        Dashboard
                    </a>

                    <!-- Planning Deputy Director (H/Q-1) Menu -->
                    <?php if ($is_planning_dd): ?>
                        <a class="nav-link d-flex align-items-center px-4 py-3 <?= (strpos($current_path, 'planning_dd/range_details') !== false || in_array($current_file, ['range_statistics.php', 'annual_targets.php', 'monthly-annual-reports.php', 'regulatory_functions.php', 'animal_health.php', 'clinical_services.php', 'animal_breeding.php', 'livestock_production.php', 'dairy_hub.php', 'projects.php', 'monitoring.php', 'accounts.php', 'clean_sri_lanka.php', 'trainings.php'])) ? 'active' : '' ?>"
                            href="<?= $base_path ?>pages/planning_dd/range_details.php">
                            Range Details
                        </a>
                        <a class="nav-link d-flex align-items-center px-4 py-3 <?= (strpos($current_path, 'planning_dd/office_details') !== false) ? 'active' : '' ?>"
                            href="<?= $base_path ?>pages/planning_dd/office_details.php">
                            Office Details
                        </a>
                    <?php endif; ?>

                    <!-- Provincial Director Menu -->
                    <?php if ($is_pd): 
                        $pd_pending_count = isset($mysqli) ? get_pending_approvals_count($mysqli) : 0;
                    ?>
                        <a class="nav-link d-flex align-items-center px-4 py-3 <?= (strpos($current_path, 'pd/pending_approvals.php') !== false) ? 'active' : '' ?>"
                            href="<?= $base_path ?>pages/modules/pd/pending_approvals.php">
                            <i class="bi bi-shield-check me-2"></i> Pending Approvals
                            <?php if ($pd_pending_count > 0): ?>
                                <span class="badge rounded-pill bg-danger ms-auto"><?= $pd_pending_count ?></span>
                            <?php endif; ?>
                        </a>

                        <a class="nav-link d-flex align-items-center px-4 py-3 <?= (strpos($current_path, 'pd/employee_manag') !== false) ? 'active' : '' ?>"
                            href="<?= $base_path ?>pages/modules/pd/employee_managment.php">
                            <i class="bi bi-people-fill me-2"></i> Global HR Directory
                        </a>

                        <a class="nav-link d-flex align-items-center px-4 py-3"
                            href="<?= $base_path ?>pages/modules/pd/animal_health_reports.php">
                            Animal Health Log
                        </a>

                        <a class="nav-link d-flex align-items-center px-4 py-3" href="#">
                            Breeding Metrics
                        </a>

                        <a class="nav-link d-flex align-items-center px-4 py-3" href="#">
                            Hatchability
                        </a>

                        <a class="nav-link d-flex align-items-center px-4 py-3" href="#">
                            Vaccine Balances
                        </a>
                        <a class="nav-link d-flex align-items-center px-4 py-3" href="#">
                            Advanced Programs
                        </a>
                        <a class="nav-link d-flex align-items-center px-4 py-3" href="#">
                            Leave Reports
                        </a>
                        <a class="nav-link d-flex align-items-center px-4 py-3" href="#">
                            Asset Inventory
                        </a>
                        <a class="nav-link d-flex align-items-center px-4 py-3" href="#">
                            Dairy Hub Data
                        </a>
                    <?php endif; ?>

                    <?php if ($is_hr_user): ?>
                        <!-- HR Management Menu -->
                        <a class="nav-link d-flex align-items-center px-4 py-3"
                            href="<?= $base_path ?>pages/modules/hr/employee_managment.php">
                            HR Management
                        </a>

                        <a class="nav-link d-flex align-items-center px-4 py-3"
                            href="<?= $base_path ?>pages/modules/hr/leave_management.php">
                            Leave Management
                        </a>
                        <a class="nav-link d-flex align-items-center px-4 py-3"
                            href="<?= $base_path ?>pages/modules/hr/inquiry_management.php">
                            Documents
                        </a>

                        <a class="nav-link d-flex align-items-center px-4 py-3"
                            href="<?= $base_path ?>pages/modules/hr/todo_tasks.php">
                            To-Do Tasks
                        </a>
                        <a class="nav-link d-flex align-items-center px-4 py-3"
                            href="<?= $base_path ?>pages/modules/hr/rti_management.php">
                            RTI Management
                        </a>
                    <?php endif; ?>

                    <?php if ($is_finance_admin): ?>
                        <a class="nav-link d-flex align-items-center px-4 py-3"
                            href="<?= $base_path ?>pages/modules/finance/assets_management.php">
                            Assets Management
                        </a>
                        <a class="nav-link d-flex align-items-center px-4 py-3"
                            href="<?= $base_path ?>pages/modules/finance/procurement_plan.php">
                            Procurement Plan
                        </a>
                        <a class="nav-link d-flex align-items-center px-4 py-3"
                            href="<?= $base_path ?>pages/modules/finance/finance_disbursementsources.php">
                            Finance Disbursement
                        </a>
                        <a class="nav-link d-flex align-items-center px-4 py-3"
                            href="<?= $base_path ?>pages/modules/finance/veterinary_stores.php">
                            Veterinary Stores
                        </a>
                    <?php endif; ?>

                    <?php if ($is_planning_officer): ?>
                        <!-- planning Officer Menu-->
                        <a class="nav-link d-flex align-items-center px-4 py-3"
                            href="<?= $base_path ?>pages/modules/project/psdg_projects.php">
                            Development Projects (PSDG/CBG/NGO)
                        </a>
                        <a class="nav-link d-flex align-items-center px-4 py-3"
                            href="<?= $base_path ?>pages/modules/project/progress_physical_financial.php">
                            Progress Reports (Physical & Financial)
                        </a>
                    <?php endif; ?>
                    <?php if ($is_sms): ?>
                        <!-- Subject Matter Specialist Menu-->
                        <a class="nav-link d-flex align-items-center px-4 py-3 <?= in_array(basename(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH)), ['office_details.php', 'lands_buildings.php', 'vehicles.php', 'furniture.php', 'machineries.php', 'instruments.php', 'counter_foilage.php', 'employee_managment.php']) && strpos($_SERVER['REQUEST_URI'], '/sms/') !== false ? 'active' : '' ?>"
                            href="<?= $base_path ?>pages/modules/sms/office_details.php">
                            Office Details
                        </a>
                        <a class="nav-link d-flex align-items-center px-4 py-3 <?= (basename(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH)) === 'my_diary.php' || basename(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH)) === 'advanced_programme.php' || basename(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH)) === 'daily_diary.php') ? 'active' : '' ?>"
                            href="<?= $base_path ?>pages/modules/sms/my_diary.php">
                            Diary and Advanced Programme
                        </a>
                        <a class="nav-link d-flex align-items-center px-4 py-3 <?= in_array(basename(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH)), ['immunization.php', 'vaccine_types.php']) ? 'active' : '' ?>"
                            href="<?= $base_path ?>pages/modules/sms/immunization.php">
                            Immunization
                        </a>
                        <a class="nav-link d-flex align-items-center px-4 py-3 <?= (basename(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH)) === 'mobile_clinics.php') ? 'active' : '' ?>"
                            href="<?= $base_path ?>pages/modules/sms/mobile_clinics.php">
                            Mobile Clinics
                        </a>
                        <a class="nav-link d-flex align-items-center px-4 py-3 <?= (basename(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH)) === 'drug_maintenance.php') ? 'active' : '' ?>"
                            href="<?= $base_path ?>pages/modules/sms/drug_maintenance.php">
                            Drug Maintenance
                        </a>
                        <a class="nav-link d-flex align-items-center px-4 py-3 <?= (basename(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH)) === 'outbreak_report.php') ? 'active' : '' ?>"
                            href="<?= $base_path ?>pages/modules/sms/outbreak_report.php">
                            Outbreak Report
                        </a>
                        <a class="nav-link d-flex align-items-center px-4 py-3 <?= (basename(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH)) === 'disease_control.php') ? 'active' : '' ?>"
                            href="<?= $base_path ?>pages/modules/sms/disease_control.php">
                            Disease Control
                        </a>
                    <?php endif; ?>

                    <?php if ($is_farms_dd): ?>
                        <!-- Farms Operations Menu -->
                        <a class="nav-link d-flex align-items-center px-4 py-3 <?= (basename(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH)) === 'office_details.php') ? 'active' : '' ?>"
                            href="<?= $base_path ?>pages/modules/farm/office_details.php">
                            Office Details
                        </a>
                        <a class="nav-link d-flex align-items-center px-4 py-3 <?= (basename(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH)) === 'parent_stock_operations.php') ? 'active' : '' ?>"
                            href="<?= $base_path ?>pages/modules/farm/parent_stock_operations.php">
                            Parent Stock Operations
                        </a>
                        <a class="nav-link d-flex align-items-center px-4 py-3 <?= (basename(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH)) === 'hatchery_register.php') ? 'active' : '' ?>"
                            href="<?= $base_path ?>pages/modules/farm/hatchery_register.php">
                            Hatchery Register
                        </a>
                        <a class="nav-link d-flex align-items-center px-4 py-3 <?= (basename(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH)) === 'chick_details.php') ? 'active' : '' ?>"
                            href="<?= $base_path ?>pages/modules/farm/chick_details.php">
                            Chick Details
                        </a>
                        <a class="nav-link d-flex align-items-center px-4 py-3 <?= (basename(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH)) === 'feed_management.php') ? 'active' : '' ?>"
                            href="<?= $base_path ?>pages/modules/farm/feed_management.php">
                            Feed Management
                        </a>
                        <a class="nav-link d-flex align-items-center px-4 py-3 <?= (basename(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH)) === 'sales_of_eggs.php') ? 'active' : '' ?>"
                            href="<?= $base_path ?>pages/modules/farm/sales_of_eggs.php">
                            Sales of Eggs
                        </a>
                        <a class="nav-link d-flex align-items-center px-4 py-3 <?= (basename(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH)) === 'drug_details.php') ? 'active' : '' ?>"
                            href="<?= $base_path ?>pages/modules/farm/drug_details.php">
                            Drugs Details
                        </a>
                        <a class="nav-link d-flex align-items-center px-4 py-3 <?= (basename(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH)) === 'production_details.php') ? 'active' : '' ?>"
                            href="<?= $base_path ?>pages/modules/farm/production_details.php">
                            Production Details
                        </a>
                        <a class="nav-link d-flex align-items-center px-4 py-3 <?= (basename(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH)) === 'fuel_register.php') ? 'active' : '' ?>"
                            href="<?= $base_path ?>pages/modules/farm/fuel_register.php">
                            Fuel Register
                        </a>
                        <a class="nav-link d-flex align-items-center px-4 py-3 <?= (basename(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH)) === 'cattle_register.php') ? 'active' : '' ?>"
                            href="<?= $base_path ?>pages/modules/farm/cattle_register.php">
                            Cattle
                        </a>
                        <a class="nav-link d-flex align-items-center px-4 py-3 <?= (basename(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH)) === 'white_cattle_register.php') ? 'active' : '' ?>"
                            href="<?= $base_path ?>pages/modules/farm/white_cattle_register.php">
                            White Cattle
                        </a>
                        <a class="nav-link d-flex align-items-center px-4 py-3 <?= (basename(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH)) === 'buffalo_register.php') ? 'active' : '' ?>"
                            href="<?= $base_path ?>pages/modules/farm/buffalo_register.php">
                            Buffalo
                        </a>
                        <a class="nav-link d-flex align-items-center px-4 py-3 <?= (basename(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH)) === 'goat_register.php') ? 'active' : '' ?>"
                            href="<?= $base_path ?>pages/modules/farm/goat_register.php">
                            Goat
                        </a>
                        <a class="nav-link d-flex align-items-center px-4 py-3 <?= (basename(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH)) === 'accounts_register.php') ? 'active' : '' ?>"
                            href="<?= $base_path ?>pages/modules/farm/accounts_register.php">
                            Accounts
                        </a>
                    <?php endif; ?>
                    <?php if ($is_training_officer): ?>
                        <a class="nav-link d-flex align-items-center px-4 py-3 <?= in_array(basename(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH)), ['office_details.php', 'lands_buildings.php', 'vehicles.php', 'furniture.php', 'machineries.php', 'instruments.php', 'counter_foilage.php', 'employee_managment.php']) && strpos($_SERVER['REQUEST_URI'], '/training/') !== false ? 'active' : '' ?>"
                            href="<?= $base_path ?>pages/modules/training/office_details.php">
                            Office Details
                        </a>
                        <a class="nav-link d-flex align-items-center px-4 py-3 <?= (basename(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH)) === 'monthly_income_summary.php') ? 'active' : '' ?>"
                            href="<?= $base_path ?>pages/modules/training/monthly_income_summary.php">
                            Monthly Income Summary
                        </a>
                        <a class="nav-link d-flex align-items-center px-4 py-3 <?= (basename(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH)) === 'advanced_programme.php') ? 'active' : '' ?>"
                            href="<?= $base_path ?>pages/modules/training/advanced_programme.php">
                            Advance Programme
                        </a>
                        <a class="nav-link d-flex align-items-center px-4 py-3 <?= (basename(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH)) === 'produce_register.php') ? 'active' : '' ?>"
                            href="<?= $base_path ?>pages/modules/training/produce_register.php">
                            Produce Register (Perishables)
                        </a>
                    <?php endif; ?>
                    <?php if ($is_district_dd): ?>
                        <a class="nav-link d-flex align-items-center px-4 py-3 <?= (basename(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH)) === 'office_details.php') ? 'active' : '' ?>"
                            href="<?= $base_path ?>pages/modules/district/office_details.php">
                            Office Details
                        </a>
                        <a class="nav-link d-flex align-items-center px-4 py-3 <?= (basename(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH)) === 'task_assignments.php') ? 'active' : '' ?>"
                            href="<?= $base_path ?>pages/modules/district/task_assignments.php">
                            <i class="bi bi-person-check me-2"></i> Task Delegation
                        </a>
                        <a class="nav-link d-flex align-items-center px-4 py-3 <?= (basename(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH)) === 'range_veterinary_officers.php') ? 'active' : '' ?>"
                            href="<?= $base_path ?>pages/modules/district/range_veterinary_officers.php">
                            Range Veterinary Officer
                        </a>
                        <a class="nav-link d-flex align-items-center px-4 py-3 <?= (basename(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH)) === 'regional_farms.php') ? 'active' : '' ?>"
                            href="<?= $base_path ?>pages/modules/district/regional_farms.php">
                            Regional Farms
                        </a>
                        <a class="nav-link d-flex align-items-center px-4 py-3 <?= (basename(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH)) === 'training_centers.php') ? 'active' : '' ?>"
                            href="<?= $base_path ?>pages/modules/district/training_centers.php">
                            Training Center
                        </a>
                        <a class="nav-link d-flex align-items-center px-4 py-3 <?= (basename(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH)) === 'subject_matter_specialists.php') ? 'active' : '' ?>"
                            href="<?= $base_path ?>pages/modules/district/subject_matter_specialists.php">
                            Subject Matter Specialist
                        </a>
                        <a class="nav-link d-flex align-items-center px-4 py-3 <?= (basename(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH)) === 'users_summary.php') ? 'active' : '' ?>"
                            href="<?= $base_path ?>pages/modules/district/users_summary.php">
                            Users Summary
                        </a>
                        <a class="nav-link d-flex align-items-center px-4 py-3 <?= (basename(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH)) === 'diary_management.php') ? 'active' : '' ?>"
                            href="<?= $base_path ?>pages/modules/district/diary_management.php">
                            Diary Management
                        </a>
                        <a class="nav-link d-flex align-items-center px-4 py-3 <?= (basename(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH)) === 'revenue_management.php') ? 'active' : '' ?>"
                            href="<?= $base_path ?>pages/modules/district/revenue_management.php">
                            Revenue Management
                        </a>
                        <a class="nav-link d-flex align-items-center px-4 py-3 <?= (basename(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH)) === 'district_revenue_summary.php') ? 'active' : '' ?>"
                            href="<?= $base_path ?>pages/modules/district/district_revenue_summary.php">
                            District Revenue Summary
                        </a>
                    <?php endif; ?>
                    <?php if ($is_veterinary_surgeon): ?>
                        <a class="nav-link d-flex align-items-center px-4 py-3"
                            href="<?= $base_path ?>pages/modules/veterinary/office_details.php">
                            Office Details
                        </a>
                        <a class="nav-link d-flex align-items-center px-4 py-3 <?= (basename(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH)) === 'range_details.php') ? 'active' : '' ?>"
                            href="<?= $base_path ?>pages/modules/veterinary/range_details.php">
                            Range Details
                        </a>

                    <?php endif; ?>
                    <!-- employee sidebar -->
                    <?php if ($is_employee): ?>
                        <a class="nav-link d-flex align-items-center px-4 py-3 <?= (basename(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH)) === 'range_details.php') ? 'active' : '' ?>"
                            href="<?= $base_path ?>pages/modules/veterinary/range_details.php">
                            <i class="bi bi-grid-3x3-gap me-2"></i> Range Details
                        </a>
                        <a class="nav-link d-flex align-items-center px-4 py-3"
                            href="<?= $base_path ?>pages/modules/employee/my_diary.php">
                            My Diary
                        </a>
                        <a class="nav-link d-flex align-items-center px-4 py-3"
                            href="<?= $base_path ?>pages/modules/employee/leave_requests.php">
                            Leave Requests
                        </a>
                        <!-- profile -->
                        <a class="nav-link d-flex align-items-center px-4 py-3 <?= (basename(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH)) === 'profile.php') ? 'active' : '' ?>"
                            href="<?= $base_path ?>pages/profile.php">
                            Profile
                        </a>

                    <?php endif; ?>

                </div>
                <div class="horizontal-line"></div>
            </div>

            <div class="px pb" style="flex-shrink: 0;">

                <a class="nav-link d-flex align-items-center d-block py-3 px-3 <?= (basename(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH)) === 'profile.php' || basename(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH)) === 'settings.php') ? 'active' : '' ?>"
                    href="<?= $base_path ?>pages/profile.php">
                    <i class="bi bi-person-circle me-2"></i> Profile
                </a>
                <a class="nav-link d-flex align-items-center d-block py-3 px-3" href="<?= $base_path ?>logout.php" id="logout-link">
                    <i class="bi bi-box-arrow-right me-2"></i> Logout
                </a>

                <div class="user-info mt-4 px-4 pb-4">
                    <div style="color:#555;">Logged in as:</div>
                    <strong
                        style="color:#000;"><?= htmlspecialchars($_SESSION['full_name'] ?? $_SESSION['username'] ?? 'User') ?></strong><br>
                    <small style="color:#777;"><?= ucwords(str_replace('_', ' ', $role)) ?></small>
                </div>
            </div>
        </div>
    </nav>
</div>