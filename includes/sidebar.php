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

$base_path = '/daph-ep-mis/';
$is_dashboard = (strpos($_SERVER['REQUEST_URI'], 'dashboard') !== false);
?>

<style>
    #layoutSidenav_nav {
        background: white !important;
        border-right: 1px solid #eee;
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
    <nav class="sb-sidenav accordion" style="background:#fff; height:100vh; width:260px; position:fixed; top:0; left:0; z-index:1030;">
        <div class="sb-sidenav-menu h-100 d-flex flex-column justify-content-between">
            <div>

                <div class="text-center py-4 border-bottom">
                    <img src="<?= $base_path ?>assets/img/logo.png" height="60" class="mb-2">

                </div>

                <!-- Main Menu Items -->
                <div class="sidebar-menu">
                    <a class="nav-link d-flex align-items-center px-4 py-3 <?= $is_dashboard ? 'bg-danger' : '' ?>" href="<?= $base_path ?>dashboard.php">
                        Dashboard
                    </a>
                    <!-- Provincial Director Menu -->
                    <?php if ($is_pd): ?>
                        <a class="nav-link d-flex align-items-center px-4 py-3" href="<?= $base_path ?>pages/diary_management.php">
                            Diary Management
                        </a>

                        <a class="nav-link d-flex align-items-center px-4 py-3" href="<?= $base_path ?>pages/vehicle_management.php">
                            Vehicle Management
                        </a>

                        <a class="nav-link d-flex align-items-center px-4 py-3" href="<?= $base_path ?>pages/reports.php">
                            Reports
                        </a>
                    <?php endif; ?>

                    <?php if ($is_hr_user): ?>
                        <!-- HR Management Menu -->
                        <a class="nav-link d-flex align-items-center px-4 py-3" href="<?= $base_path ?>pages/diary_management.php">
                            HR Management
                        </a>

                        <a class="nav-link d-flex align-items-center px-4 py-3" href="<?= $base_path ?>pages/vehicle_management.php">
                            Leave Managementt
                        </a>

                        <a class="nav-link d-flex align-items-center px-4 py-3" href="<?= $base_path ?>pages/reports.php">
                            To-Do Tasks
                        </a>
                        <a class="nav-link d-flex align-items-center px-4 py-3" href="<?= $base_path ?>pages/reports.php">
                            RTI Management
                        </a>
                    <?php endif; ?>

                    <?php if ($is_finance_admin): ?>
                        <!-- finance admin menu -->
                        <a class="nav-link d-flex align-items-center px-4 py-3" ref="<?= $base_path ?>pages/modules/finance/assets_management.php">
                            Finance Management

                        </a>
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
                        <!-- planning Ofiicer Menu-->
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
                        <a class="nav-link d-flex align-items-center px-4 py-3" href="<?= $base_path ?>pages/modules/farm/poultry_hatchery.php">
                            Poultry Operations
                        </a>
                        <a class="nav-link d-flex align-items-center px-4 py-3" href="<?= $base_path ?>pages/modules/farm/livestock_operations.php">
                            Livestock Operations
                        </a>
                        <a class="nav-link d-flex align-items-center px-4 py-3" href="<?= $base_path ?>pages/modules/farm/fodder_distribution.php">
                            Fodder Management
                        </a>
                        <a class="nav-link d-flex align-items-center px-4 py-3" href="<?= $base_path ?>pages/modules/farm/inputs_revenue.php">
                            Inputs & Revenue
                        </a>
                    <?php endif; ?>
                    <?php if ($is_training_officer): ?>
                        <!-- Farms Operations Menu -->
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
                        <!-- District Deputy Director Menu -->
                        <a class="nav-link d-flex align-items-center px-4 py-3" href="<?= $base_path ?>pages/modules/district/diary_management.php">
                            Diary Management
                        </a>

                        <a class="nav-link d-flex align-items-center px-4 py-3" href="<?= $base_path ?>pages/modules/district/revenue_management.php">
                            Revenue Management
                        </a>
                    <?php endif; ?>
                </div>
                <div class="horizontal-line"></div>
            </div>

            <div class="px pb">

                <a class="nav-link d-flex align-items-center d-block py-3 px-3" href="<?= $base_path ?>pages/settings.php">
                    Settings
                </a>
                <a class="nav-link d-flex align-items-center d-block py-3 px-3" href="<?= $base_path ?>logout.php">
                    Logout
                </a>

                <div class="user-info mt-4 px-4 pb-4">
                    <div style="color:#555;">Logged in as:</div>
                    <strong style="color:#000;"><?= htmlspecialchars($_SESSION['full_name']) ?></strong><br>
                    <small style="color:#777;"><?= ucwords(str_replace('_', ' ', $role)) ?></small>
                </div>
            </div>
        </div>
    </nav>
</div>