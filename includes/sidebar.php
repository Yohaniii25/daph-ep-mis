<?php
$role = $_SESSION['role'] ?? '';
$is_pd = ($role === 'provincial_director');
$is_hr_user = ($role === 'administrator');
$is_finance_admin = ($role === 'finance_admin');
$is_planning_officer = ($role === 'planning_officer');
$is_sms = ($role === 'sms');
$is_farms_dd = ($role === 'farms_dd');
$is_training_officer = ($role === 'training_officer');
$is_district_dd = ($role === 'district_dd');
$is_veterinary_surgeon = ($role === 'veterinary_surgeon');
$is_employee = ($role === 'employee');

$base_path = '/daph-ep-mis/';
$is_dashboard = (strpos($_SERVER['REQUEST_URI'], 'dashboard') !== false);
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

    .text-white,
    .text-white-50 {
        color: #333 !important;
    }

    .text-white-50 {
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
                    <a class="nav-link d-flex align-items-center px-4 py-3 <?= $is_dashboard ? 'bg-danger' : '' ?>" href="<?= $base_path ?>dashboard.php">
                        Dashboard
                    </a>
                    <!-- Provincial Director Menu -->
                    <?php if ($is_pd): ?>
                        <a class="nav-link d-flex align-items-center px-4 py-3" href="<?= $base_path ?>pages/modules/pd/my_diary.php">
                            Diary Management
                        </a>

                        <a class="nav-link d-flex align-items-center px-4 py-3" href="<?= $base_path ?>pages/modules/pd/approval_diaries.php">
                            Approval of Diaries & Programmes
                        </a>

                        <a class="nav-link d-flex align-items-center px-4 py-3" href="<?= $base_path ?>pages/modules/pd/vehicle_approval.php">
                            Vehicle Management
                        </a>

                        <a class="nav-link d-flex align-items-center px-4 py-3" href="<?= $base_path ?>pages/modules/pd/provincial_reports.php">
                            Reports
                        </a>
                    <?php endif; ?>

                    <?php if ($is_hr_user): ?>
                        <!-- HR Management Menu -->
                        <a class="nav-link d-flex align-items-center px-4 py-3" href="<?= $base_path ?>pages/modules/hr/employee_managment.php">
                            HR Management
                        </a>

                        <a class="nav-link d-flex align-items-center px-4 py-3" href="<?= $base_path ?>pages/modules/hr/leave_management.php">
                            Leave Management
                        </a>
                        <a class="nav-link d-flex align-items-center px-4 py-3" href="<?= $base_path ?>pages/modules/hr/inquiry_management.php">
                            Documents
                        </a>

                        <a class="nav-link d-flex align-items-center px-4 py-3" href="<?= $base_path ?>pages/modules/hr/todo_tasks.php">
                            To-Do Tasks
                        </a>
                        <a class="nav-link d-flex align-items-center px-4 py-3" href="<?= $base_path ?>pages/modules/hr/rti_management.php">
                            RTI Management
                        </a>
                    <?php endif; ?>

                    <?php if ($is_finance_admin): ?>
                        <a class="nav-link d-flex align-items-center px-4 py-3" href="<?= $base_path ?>pages/modules/finance/assets_management.php">
                            Assets Management
                        </a>
                        <a class="nav-link d-flex align-items-center px-4 py-3" href="<?= $base_path ?>pages/modules/finance/procurement_plan.php">
                            Procurement Plan
                        </a>
                        <a class="nav-link d-flex align-items-center px-4 py-3" href="<?= $base_path ?>pages/modules/finance/finance_disbursementsources.php">
                            Finance Disbursement
                        </a>
                        <a class="nav-link d-flex align-items-center px-4 py-3" href="<?= $base_path ?>pages/modules/finance/veterinary_stores.php">
                            Veterinary Stores
                        </a>
                    <?php endif; ?>

                    <?php if ($is_planning_officer): ?>
                        <!-- planning Officer Menu-->
                        <a class="nav-link d-flex align-items-center px-4 py-3" href="<?= $base_path ?>pages/modules/project/psdg_projects.php">
                            Development Projects (PSDG/CBG/NGO)
                        </a>
                        <a class="nav-link d-flex align-items-center px-4 py-3" href="<?= $base_path ?>pages/modules/project/progress_physical_financial.php">
                            Progress Reports (Physical & Financial)
                        </a>
                    <?php endif; ?>
                    <?php if ($is_sms): ?>
                        <!-- Subject Matter Specialist Menu-->
                        <a class="nav-link d-flex align-items-center px-4 py-3" href="<?= $base_path ?>pages/modules/sms/my_diary.php">
                            My Diary
                        </a>
                        <a class="nav-link d-flex align-items-center px-4 py-3" href="<?= $base_path ?>pages/modules/sms/provincial_epidemiology.php">
                            Provincial Epidemiology
                        </a>
                        <a class="nav-link d-flex align-items-center px-4 py-3" href="<?= $base_path ?>pages/modules/sms/veterinary_supply_chain.php">
                            Veterinary Supply Chain
                        </a>
                    <?php endif; ?>

                    <?php if ($is_farms_dd): ?>
                        <!-- Farms Operations Menu -->
                        <a class="nav-link d-flex align-items-center px-4 py-3" href="<?= $base_path ?>pages/modules/farm/parent_stock_operations.php">
                            Parent Stock Operations
                        </a>
                        <a class="nav-link d-flex align-items-center px-4 py-3" href="<?= $base_path ?>pages/modules/farm/hatchery_operations.php">
                            Log Grading & Collection
                        </a>
                        <a class="nav-link d-flex align-items-center px-4 py-3" href="<?= $base_path ?>pages/modules/farm/inputs_revenue.php">
                            Sales & Revenue
                        </a>

                    <?php endif; ?>
                    <?php if ($is_training_officer): ?>
                        <!-- Training Officer Menu -->
                        <a class="nav-link d-flex align-items-center px-4 py-3" href="<?= $base_path ?>pages/modules/training/training_activities.php">
                            Training Activities
                        </a>
                        <a class="nav-link d-flex align-items-center px-4 py-3" href="<?= $base_path ?>pages/modules/training/farmer_participation.php">
                            Farmer Participation
                        </a>
                        <a class="nav-link d-flex align-items-center px-4 py-3" href="<?= $base_path ?>pages/modules/training/revenue_management.php">
                            Revenue Management
                        </a>
                    <?php endif; ?>
                    <?php if ($is_district_dd): ?>
                        <a class="nav-link d-flex align-items-center px-4 py-3" href="<?= $base_path ?>pages/modules/district/diary_management.php">
                            Diary Management
                        </a>
                        <!-- <a class="nav-link d-flex align-items-center px-4 py-3" href="<?= $base_path ?>pages/modules/district/approval_diaries.php">
                            Approval of Diaries & Programmes
                        </a> -->
                        <a class="nav-link d-flex align-items-center px-4 py-3" href="<?= $base_path ?>pages/modules/district/revenue_management.php">
                            Revenue Management
                        </a>
                        <a class="nav-link d-flex align-items-center px-4 py-3" href="<?= $base_path ?>pages/modules/district/district_revenue_summary.php">
                            District Revenue Summary
                        </a>
                    <?php endif; ?>
                    <?php if ($is_veterinary_surgeon): ?>

                        <a class="nav-link d-flex align-items-center px-4 py-3" href="<?= $base_path ?>pages/modules/veterinary/animal_health.php">
                            Animal Health
                        </a>
                        <a class="nav-link d-flex align-items-center px-4 py-3" href="<?= $base_path ?>pages/modules/veterinary/animal_breeding.php">
                            Animal Breeding
                        </a>
                        <a class="nav-link d-flex align-items-center px-4 py-3" href="<?= $base_path ?>pages/modules/veterinary/regulatory_functions.php">
                            Regulatory Functions
                        </a>
                        <a class="nav-link d-flex align-items-center px-4 py-3" href="<?= $base_path ?>pages/modules/veterinary/office_details.php">
                            Office details
                        </a>
                        <a class="nav-link d-flex align-items-center px-4 py-3" href="<?= $base_path ?>pages/modules/veterinary/range_statistics.php">
                            Range statistics
                        </a>
                        <a class="nav-link d-flex align-items-center px-4 py-3" href="<?= $base_path ?>pages/modules/veterinary/dairy_hub.php">
                            Dairy Hub
                        </a>
                        <a class="nav-link d-flex align-items-center px-4 py-3" href="<?= $base_path ?>pages/modules/veterinary/projects_progress.php">
                            Projects & Progress
                        </a>
                        <a class="nav-link d-flex align-items-center px-4 py-3" href="<?= $base_path ?>pages/modules/veterinary/monitoring.php">
                            Monitoring
                        </a>
                        <a class="nav-link d-flex align-items-center px-4 py-3" href="<?= $base_path ?>pages/modules/veterinary/accounts.php">
                            Accounts
                        </a>
                        <a class="nav-link d-flex align-items-center px-4 py-3" href="<?= $base_path ?>pages/modules/veterinary/clean_sri_lanka.php">
                            Clean Sri Lanka
                        </a>
                        <a class="nav-link d-flex align-items-center px-4 py-3" href="<?= $base_path ?>pages/modules/veterinary/animals_act_forensic.php">
                            Animals Act & Forensic Reporting
                        </a>
                        <a class="nav-link d-flex align-items-center px-4 py-3" href="<?= $base_path ?>pages/modules/veterinary/training.php">
                            Trainings
                        </a>
                    <?php endif; ?>
                    <!-- employee sidebar -->
                    <?php if ($is_employee): ?>
                        <a class="nav-link d-flex align-items-center px-4 py-3" href="<?= $base_path ?>pages/modules/employee/my_diary.php">
                            My Diary
                        </a>
                        <a class="nav-link d-flex align-items-center px-4 py-3" href="<?= $base_path ?>pages/modules/employee/leave_requests.php">
                            Leave Requests
                        </a>
                        <!-- profile -->
                        <a class="nav-link d-flex align-items-center px-4 py-3" href="<?= $base_path ?>pages/modules/employee/profile.php">
                            Profile
                        </a>

                    <?php endif; ?>

                </div>
                <div class="horizontal-line"></div>
            </div>

            <div class="px pb" style="flex-shrink: 0;">

                <a class="nav-link d-flex align-items-center d-block py-3 px-3" href="<?= $base_path ?>pages/settings.php">
                    Settings
                </a>
                <a class="nav-link d-flex align-items-center d-block py-3 px-3" href="<?= $base_path ?>logout.php">
                    Logout
                </a>

                <div class="user-info mt-4 px-4 pb-4">
                    <div style="color:#555;">Logged in as:</div>
                    <strong style="color:#000;"><?= htmlspecialchars($_SESSION['full_name'] ?? $_SESSION['username'] ?? 'User') ?></strong><br>
                    <small style="color:#777;"><?= ucwords(str_replace('_', ' ', $role)) ?></small>
                </div>
            </div>
        </div>
    </nav>
</div>