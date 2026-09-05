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
$pd_pending_count = isset($mysqli) ? get_pending_approvals_count($mysqli) : 0;
$current_cat_param = $_GET['cat'] ?? '';
$current_view_param = $_GET['view'] ?? '';
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

    /* Standardized link styles matching all sidebar items */
    .sb-sidenav-menu a {
        color: #333 !important;
        transition: background 0.2s ease, color 0.2s ease;
        text-decoration: none;
    }

    .sb-sidenav-menu .nav-link i {
        font-size: 1.1rem;
        width: 24px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
        transition: color 0.2s ease;
    }

    /* Universal active & hover matching existing system */
    .sb-sidenav-menu a:hover {
        background: #500707 !important;
        color: #ffffff !important;
    }

    .sb-sidenav-menu a:hover i,
    .sb-sidenav-menu a:hover .rotate-caret {
        color: #ffffff !important;
    }

    .sb-sidenav-menu a.bg-danger,
    .sb-sidenav-menu a.active {
        background: #500707 !important;
        color: #ffffff !important;
        font-weight: bold;
    }

    .sb-sidenav-menu a.active i,
    .sb-sidenav-menu a.active .rotate-caret {
        color: #ffffff !important;
    }

    .border-white {
        border-color: #ddd !important;
    }

    /* Category Accordion Parent Toggle */
    .sb-sidenav-menu .category-toggle {
        font-size: 0.95rem;
        font-weight: 500;
        color: #333 !important;
        cursor: pointer;
    }

    .sb-sidenav-menu .rotate-caret {
        transition: transform 0.25s ease;
        font-size: 0.85rem;
        color: #777;
    }

    .sb-sidenav-menu .category-toggle.collapsed .rotate-caret {
        transform: rotate(-90deg);
    }

    /* Submenu Links - identical padding height (py-3) and typography with clean indent */
    .sb-sidenav-menu .submenu-link {
        padding: 0.75rem 1.5rem 0.75rem 2.85rem !important;
        font-size: 0.92rem;
        color: #444 !important;
        background: transparent !important;
        border: none !important;
        display: flex;
        align-items: center;
        transition: background 0.2s ease, color 0.2s ease;
        text-decoration: none;
    }

    .sb-sidenav-menu .submenu-link i {
        font-size: 1.05rem;
        width: 22px;
        color: #555;
        transition: color 0.2s ease;
    }

    .sb-sidenav-menu .submenu-link:hover {
        background: #500707 !important;
        color: #ffffff !important;
    }

    .sb-sidenav-menu .submenu-link:hover i {
        color: #ffffff !important;
    }

    .sb-sidenav-menu .submenu-link.active {
        background: #500707 !important;
        color: #ffffff !important;
        font-weight: bold;
    }

    .sb-sidenav-menu .submenu-link.active i {
        color: #ffffff !important;
    }

    .sidebar-heading {
        font-size: 0.72rem;
        letter-spacing: 0.06em;
        text-transform: uppercase;
        font-weight: 700;
        color: #888;
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
                        <i class="bi bi-speedometer2 me-2"></i> Dashboard
                    </a>

                    <!-- Notifications Center Hub -->
                    <a class="nav-link d-flex align-items-center px-4 py-3 <?= (strpos($current_path, 'notifications.php') !== false) ? 'active' : '' ?>"
                        href="<?= $base_path ?>pages/notifications.php">
                        <i class="bi bi-bell-fill me-2"></i> Notifications
                        <?php if (!empty($header_unread_count) && $header_unread_count > 0): ?>
                            <span class="badge rounded-pill bg-danger ms-auto"><?= $header_unread_count ?></span>
                        <?php endif; ?>
                    </a>

                    <!-- Core User Categories Navigation -->
                    <div class="sidebar-heading px-4 pt-3 pb-1">Core Categories</div>

                    <?php
                    $current_cat_param = $_GET['cat'] ?? '';
                    ?>

                    <!-- 1. Provincial Director -->
                    <!-- 2. Subject Matter Specialist -->
                    <?php $is_cat_sms = ($current_cat_param === 'subject_matter_specialist' || $is_sms); ?>
                    <a class="nav-link d-flex align-items-center justify-content-between px-4 py-3 category-toggle <?= $is_cat_sms ? '' : 'collapsed' ?>"
                       data-bs-toggle="collapse" href="#catSubmenu_sms" role="button" aria-expanded="<?= $is_cat_sms ? 'true' : 'false' ?>">
                        <span class="d-flex align-items-center">
                            <i class="bi bi-journal-medical me-2"></i> Specialist (SMS)
                        </span>
                        <i class="bi bi-chevron-down rotate-caret"></i>
                    </a>
                    <div class="collapse <?= $is_cat_sms ? 'show' : '' ?>" id="catSubmenu_sms">
                        <a class="nav-link submenu-link <?= ($current_cat_param === 'subject_matter_specialist' && strpos($current_path, 'categories/view.php') !== false) ? 'active' : '' ?>" href="<?= $base_path ?>pages/categories/view.php?cat=subject_matter_specialist">
                            <i class="bi bi-grid-fill me-2"></i> Action Hub (Overview)
                        </a>
                        <a class="nav-link submenu-link <?= ($is_dashboard && ($current_view_param === 'sms' || (empty($current_view_param) && $is_sms))) ? 'active' : '' ?>" href="<?= $base_path ?>dashboard.php?view=sms">
                            <i class="bi bi-graph-up me-2"></i> Statistical Dashboard
                        </a>
                        <a class="nav-link submenu-link <?= (strpos($current_path, 'sms/outbreak_report.php') !== false) ? 'active' : '' ?>" href="<?= $base_path ?>pages/modules/sms/outbreak_report.php">
                            <i class="bi bi-exclamation-triangle me-2"></i> Outbreak Reports
                        </a>
                        <a class="nav-link submenu-link <?= (strpos($current_path, 'sms/immunization.php') !== false) ? 'active' : '' ?>" href="<?= $base_path ?>pages/modules/sms/immunization.php">
                            <i class="bi bi-shield-plus me-2"></i> Immunization
                        </a>
                        <a class="nav-link submenu-link <?= (strpos($current_path, 'sms/mobile_clinics.php') !== false) ? 'active' : '' ?>" href="<?= $base_path ?>pages/modules/sms/mobile_clinics.php">
                            <i class="bi bi-truck me-2"></i> Mobile Clinics
                        </a>
                    </div>

                    <!-- 3. Deputy Director - H/Q-1 -->
                    <?php $is_cat_hq1 = ($current_cat_param === 'deputy_director_hq_1' || $is_planning_dd); ?>
                    <a class="nav-link d-flex align-items-center justify-content-between px-4 py-3 category-toggle <?= $is_cat_hq1 ? '' : 'collapsed' ?>"
                       data-bs-toggle="collapse" href="#catSubmenu_hq1" role="button" aria-expanded="<?= $is_cat_hq1 ? 'true' : 'false' ?>">
                        <span class="d-flex align-items-center">
                            <i class="bi bi-kanban me-2"></i> DD - H/Q-1 (Planning)
                        </span>
                        <i class="bi bi-chevron-down rotate-caret"></i>
                    </a>
                    <div class="collapse <?= $is_cat_hq1 ? 'show' : '' ?>" id="catSubmenu_hq1">
                        <a class="nav-link submenu-link <?= ($current_cat_param === 'deputy_director_hq_1' && strpos($current_path, 'categories/view.php') !== false) ? 'active' : '' ?>" href="<?= $base_path ?>pages/categories/view.php?cat=deputy_director_hq_1">
                            <i class="bi bi-grid-fill me-2"></i> Action Hub (Overview)
                        </a>
                        <a class="nav-link submenu-link <?= ($is_dashboard && ($current_view_param === 'planning_dd' || (empty($current_view_param) && $is_planning_dd))) ? 'active' : '' ?>" href="<?= $base_path ?>dashboard.php?view=planning_dd">
                            <i class="bi bi-graph-up me-2"></i> Planning Dashboard
                        </a>
                        <a class="nav-link submenu-link <?= (strpos($current_path, 'planning_dd/range_details.php') !== false) ? 'active' : '' ?>" href="<?= $base_path ?>pages/planning_dd/range_details.php">
                            <i class="bi bi-geo-alt me-2"></i> Range Details
                        </a>
                        <a class="nav-link submenu-link <?= (strpos($current_path, 'veterinary/annual_targets.php') !== false) ? 'active' : '' ?>" href="<?= $base_path ?>pages/modules/veterinary/annual_targets.php">
                            <i class="bi bi-bullseye me-2"></i> Annual Targets
                        </a>
                        <a class="nav-link submenu-link <?= (strpos($current_path, 'project/psdg_projects.php') !== false) ? 'active' : '' ?>" href="<?= $base_path ?>pages/modules/project/psdg_projects.php">
                            <i class="bi bi-graph-up-arrow me-2"></i> PSDG Projects
                        </a>
                    </div>

                    <!-- 4. Deputy Director - H/Q-2 -->
                    <?php $is_cat_hq2 = ($current_cat_param === 'deputy_director_hq_2'); ?>
                    <a class="nav-link d-flex align-items-center justify-content-between px-4 py-3 category-toggle <?= $is_cat_hq2 ? '' : 'collapsed' ?>"
                       data-bs-toggle="collapse" href="#catSubmenu_hq2" role="button" aria-expanded="<?= $is_cat_hq2 ? 'true' : 'false' ?>">
                        <span class="d-flex align-items-center">
                            <i class="bi bi-shield-shaded me-2"></i> DD - H/Q-2 (Regulatory)
                        </span>
                        <i class="bi bi-chevron-down rotate-caret"></i>
                    </a>
                    <div class="collapse <?= $is_cat_hq2 ? 'show' : '' ?>" id="catSubmenu_hq2">
                        <a class="nav-link submenu-link <?= ($current_cat_param === 'deputy_director_hq_2' && strpos($current_path, 'categories/view.php') !== false) ? 'active' : '' ?>" href="<?= $base_path ?>pages/categories/view.php?cat=deputy_director_hq_2">
                            <i class="bi bi-grid-fill me-2"></i> Action Hub (Overview)
                        </a>
                        <a class="nav-link submenu-link <?= ($is_dashboard && $current_view_param === 'planning_dd') ? 'active' : '' ?>" href="<?= $base_path ?>dashboard.php?view=planning_dd">
                            <i class="bi bi-graph-up me-2"></i> Regulatory Dashboard
                        </a>
                        <a class="nav-link submenu-link <?= (strpos($current_path, 'veterinary/regulatory_functions.php') !== false) ? 'active' : '' ?>" href="<?= $base_path ?>pages/modules/veterinary/regulatory_functions.php">
                            <i class="bi bi-patch-check me-2"></i> Regulatory Functions
                        </a>
                        <a class="nav-link submenu-link <?= (strpos($current_path, 'veterinary/animal_breeding.php') !== false) ? 'active' : '' ?>" href="<?= $base_path ?>pages/modules/veterinary/animal_breeding.php">
                            <i class="bi bi-diagram-3 me-2"></i> Breeding Monitoring
                        </a>
                    </div>

                    <!-- 5. Deputy Director - District -->
                    <?php $is_cat_district = ($current_cat_param === 'deputy_director_district' || $is_district_dd); ?>
                    <a class="nav-link d-flex align-items-center justify-content-between px-4 py-3 category-toggle <?= $is_cat_district ? '' : 'collapsed' ?>"
                       data-bs-toggle="collapse" href="#catSubmenu_district" role="button" aria-expanded="<?= $is_cat_district ? 'true' : 'false' ?>">
                        <span class="d-flex align-items-center">
                            <i class="bi bi-geo-alt me-2"></i> DD - District
                        </span>
                        <i class="bi bi-chevron-down rotate-caret"></i>
                    </a>
                    <div class="collapse <?= $is_cat_district ? 'show' : '' ?>" id="catSubmenu_district">
                        <a class="nav-link submenu-link <?= ($current_cat_param === 'deputy_director_district' && strpos($current_path, 'categories/view.php') !== false) ? 'active' : '' ?>" href="<?= $base_path ?>pages/categories/view.php?cat=deputy_director_district">
                            <i class="bi bi-grid-fill me-2"></i> Action Hub (Overview)
                        </a>
                        <a class="nav-link submenu-link <?= ($is_dashboard && ($current_view_param === 'district' || (empty($current_view_param) && $is_district_dd))) ? 'active' : '' ?>" href="<?= $base_path ?>dashboard.php?view=district">
                            <i class="bi bi-graph-up me-2"></i> District Dashboard
                        </a>
                        <a class="nav-link submenu-link <?= (strpos($current_path, 'district/office_details.php') !== false) ? 'active' : '' ?>" href="<?= $base_path ?>pages/modules/district/office_details.php">
                            <i class="bi bi-building me-2"></i> Office Details
                        </a>
                        <a class="nav-link submenu-link <?= (strpos($current_path, 'district/task_assignments.php') !== false) ? 'active' : '' ?>" href="<?= $base_path ?>pages/modules/district/task_assignments.php">
                            <i class="bi bi-person-check me-2"></i> Task Delegation
                        </a>
                        <a class="nav-link submenu-link <?= (strpos($current_path, 'district/range_veterinary_officers.php') !== false) ? 'active' : '' ?>" href="<?= $base_path ?>pages/modules/district/range_veterinary_officers.php">
                            <i class="bi bi-person-badge me-2"></i> Range Officers
                        </a>
                        <a class="nav-link submenu-link <?= (strpos($current_path, 'district/district_revenue_summary.php') !== false) ? 'active' : '' ?>" href="<?= $base_path ?>pages/modules/district/district_revenue_summary.php">
                            <i class="bi bi-currency-exchange me-2"></i> Revenue Summary
                        </a>
                    </div>

                    <!-- 6. Range Veterinary Officer -->
                    <?php $is_cat_rvo = ($current_cat_param === 'range_veterinary_officer' || $is_veterinary_surgeon); ?>
                    <a class="nav-link d-flex align-items-center justify-content-between px-4 py-3 category-toggle <?= $is_cat_rvo ? '' : 'collapsed' ?>"
                       data-bs-toggle="collapse" href="#catSubmenu_rvo" role="button" aria-expanded="<?= $is_cat_rvo ? 'true' : 'false' ?>">
                        <span class="d-flex align-items-center">
                            <i class="bi bi-hospital me-2"></i> Range Vet Officer
                        </span>
                        <i class="bi bi-chevron-down rotate-caret"></i>
                    </a>
                    <div class="collapse <?= $is_cat_rvo ? 'show' : '' ?>" id="catSubmenu_rvo">
                        <a class="nav-link submenu-link <?= ($current_cat_param === 'range_veterinary_officer' && strpos($current_path, 'categories/view.php') !== false) ? 'active' : '' ?>" href="<?= $base_path ?>pages/categories/view.php?cat=range_veterinary_officer">
                            <i class="bi bi-grid-fill me-2"></i> Action Hub (Overview)
                        </a>
                        <a class="nav-link submenu-link <?= ($is_dashboard && ($current_view_param === 'veterinary_office' || (empty($current_view_param) && $is_veterinary_surgeon))) ? 'active' : '' ?>" href="<?= $base_path ?>dashboard.php?view=veterinary_office">
                            <i class="bi bi-graph-up me-2"></i> Vet Office Dashboard
                        </a>
                        <a class="nav-link submenu-link <?= (strpos($current_path, 'veterinary/range_details.php') !== false) ? 'active' : '' ?>" href="<?= $base_path ?>pages/modules/veterinary/range_details.php">
                            <i class="bi bi-geo-alt me-2"></i> Range Details
                        </a>
                        <a class="nav-link submenu-link <?= (strpos($current_path, 'veterinary/office_details.php') !== false) ? 'active' : '' ?>" href="<?= $base_path ?>pages/modules/veterinary/office_details.php">
                            <i class="bi bi-building me-2"></i> Office Details
                        </a>
                    </div>

                    <!-- 7. Training Centers -->
                    <?php $is_cat_tc = ($current_cat_param === 'training_centers' || $is_training_officer); ?>
                    <a class="nav-link d-flex align-items-center justify-content-between px-4 py-3 category-toggle <?= $is_cat_tc ? '' : 'collapsed' ?>"
                       data-bs-toggle="collapse" href="#catSubmenu_tc" role="button" aria-expanded="<?= $is_cat_tc ? 'true' : 'false' ?>">
                        <span class="d-flex align-items-center">
                            <i class="bi bi-mortarboard me-2"></i> Training Centers
                        </span>
                        <i class="bi bi-chevron-down rotate-caret"></i>
                    </a>
                    <div class="collapse <?= $is_cat_tc ? 'show' : '' ?>" id="catSubmenu_tc">
                        <a class="nav-link submenu-link <?= ($current_cat_param === 'training_centers' && strpos($current_path, 'categories/view.php') !== false) ? 'active' : '' ?>" href="<?= $base_path ?>pages/categories/view.php?cat=training_centers">
                            <i class="bi bi-grid-fill me-2"></i> Action Hub (Overview)
                        </a>
                        <a class="nav-link submenu-link <?= ($is_dashboard && ($current_view_param === 'training' || (empty($current_view_param) && $is_training_officer))) ? 'active' : '' ?>" href="<?= $base_path ?>dashboard.php?view=training">
                            <i class="bi bi-graph-up me-2"></i> Training Dashboard
                        </a>
                        <a class="nav-link submenu-link <?= (strpos($current_path, 'training/advanced_programme.php') !== false) ? 'active' : '' ?>" href="<?= $base_path ?>pages/modules/training/advanced_programme.php">
                            <i class="bi bi-calendar2-week me-2"></i> Advance Programme
                        </a>
                        <a class="nav-link submenu-link <?= (strpos($current_path, 'training/monthly_income_summary.php') !== false) ? 'active' : '' ?>" href="<?= $base_path ?>pages/modules/training/monthly_income_summary.php">
                            <i class="bi bi-cash-stack me-2"></i> Income Summary
                        </a>
                        <a class="nav-link submenu-link <?= (strpos($current_path, 'training/produce_register.php') !== false) ? 'active' : '' ?>" href="<?= $base_path ?>pages/modules/training/produce_register.php">
                            <i class="bi bi-journal-text me-2"></i> Produce Register
                        </a>
                    </div>

                    <!-- 8. Regional Farms -->
                    <?php $is_cat_farms = ($current_cat_param === 'regional_farms' || $is_farms_dd); ?>
                    <a class="nav-link d-flex align-items-center justify-content-between px-4 py-3 category-toggle <?= $is_cat_farms ? '' : 'collapsed' ?>"
                       data-bs-toggle="collapse" href="#catSubmenu_farms" role="button" aria-expanded="<?= $is_cat_farms ? 'true' : 'false' ?>">
                        <span class="d-flex align-items-center">
                            <i class="bi bi-tree me-2"></i> Regional Farms
                        </span>
                        <i class="bi bi-chevron-down rotate-caret"></i>
                    </a>
                    <div class="collapse <?= $is_cat_farms ? 'show' : '' ?>" id="catSubmenu_farms">
                        <a class="nav-link submenu-link <?= ($current_cat_param === 'regional_farms' && strpos($current_path, 'categories/view.php') !== false) ? 'active' : '' ?>" href="<?= $base_path ?>pages/categories/view.php?cat=regional_farms">
                            <i class="bi bi-grid-fill me-2"></i> Action Hub (Overview)
                        </a>
                        <a class="nav-link submenu-link <?= ($is_dashboard && ($current_view_param === 'farms' || (empty($current_view_param) && $is_farms_dd))) ? 'active' : '' ?>" href="<?= $base_path ?>dashboard.php?view=farms">
                            <i class="bi bi-graph-up me-2"></i> Farms Dashboard
                        </a>
                        <a class="nav-link submenu-link <?= (strpos($current_path, 'farm/parent_stock_operations.php') !== false) ? 'active' : '' ?>" href="<?= $base_path ?>pages/modules/farm/parent_stock_operations.php">
                            <i class="bi bi-collection me-2"></i> Parent Stock
                        </a>
                        <a class="nav-link submenu-link <?= (strpos($current_path, 'farm/hatchery_register.php') !== false) ? 'active' : '' ?>" href="<?= $base_path ?>pages/modules/farm/hatchery_register.php">
                            <i class="bi bi-egg-fried me-2"></i> Hatchery Register
                        </a>
                        <a class="nav-link submenu-link <?= (strpos($current_path, 'farm/cattle_register.php') !== false) ? 'active' : '' ?>" href="<?= $base_path ?>pages/modules/farm/cattle_register.php">
                            <i class="bi bi-record-circle me-2"></i> Cattle Register
                        </a>
                    </div>

                    <div class="horizontal-line my-2"></div>
                    <div class="sidebar-heading px-4 pt-2 pb-1">Role Workspace</div>


                    <!-- Planning Deputy Director (H/Q-1) Menu -->
                    <?php if ($is_planning_dd): ?>
                        <a class="nav-link d-flex align-items-center px-4 py-3 <?= (strpos($current_path, 'planning_dd/range_details') !== false || in_array($current_file, ['range_statistics.php', 'annual_targets.php', 'monthly-annual-reports.php', 'regulatory_functions.php', 'animal_health.php', 'clinical_services.php', 'animal_breeding.php', 'livestock_production.php', 'dairy_hub.php', 'projects.php', 'monitoring.php', 'accounts.php', 'clean_sri_lanka.php', 'trainings.php'])) ? 'active' : '' ?>"
                            href="<?= $base_path ?>pages/planning_dd/range_details.php">
                            <i class="bi bi-geo-alt me-2"></i> Range Details
                        </a>
                        <a class="nav-link d-flex align-items-center px-4 py-3 <?= (strpos($current_path, 'planning_dd/office_details') !== false) ? 'active' : '' ?>"
                            href="<?= $base_path ?>pages/planning_dd/office_details.php">
                            <i class="bi bi-building me-2"></i> Office Details
                        </a>
                    <?php endif; ?>

                    <!-- Provincial Director Menu -->
                    <?php if ($is_pd): 
                        $is_pd_role_active = (strpos($current_path, 'role_hub.php') !== false || strpos($current_path, 'summary_') !== false);
                    ?>
                        <a class="nav-link d-flex align-items-center px-4 py-3 <?= (strpos($current_path, 'pd/pending_approvals.php') !== false) ? 'active' : '' ?>"
                            href="<?= $base_path ?>pages/modules/pd/pending_approvals.php">
                            <i class="bi bi-shield-check me-2"></i> Pending Approvals
                            <?php if (!empty($pd_pending_count) && $pd_pending_count > 0): ?>
                                <span class="badge rounded-pill bg-danger ms-auto"><?= $pd_pending_count ?></span>
                            <?php endif; ?>
                        </a>
                        <a class="nav-link d-flex align-items-center px-4 py-3 <?= (strpos($current_path, 'pd/employee_manag') !== false) ? 'active' : '' ?>"
                            href="<?= $base_path ?>pages/modules/pd/employee_managment.php">
                            <i class="bi bi-people me-2"></i> Global HR Directory
                        </a>
                        <a class="nav-link d-flex align-items-center px-4 py-3 <?= (strpos($current_path, 'pd/animal_health_reports.php') !== false) ? 'active' : '' ?>"
                            href="<?= $base_path ?>pages/modules/pd/animal_health_reports.php">
                            <i class="bi bi-heart-pulse me-2"></i> Animal Health Log
                        </a>

                        <!-- User Role Summaries Collapsible Submenu -->
                        <a class="nav-link d-flex align-items-center justify-content-between px-4 py-3 category-toggle <?= $is_pd_role_active ? '' : 'collapsed' ?>"
                           data-bs-toggle="collapse" href="#pdUserRolesSubmenu" role="button" aria-expanded="<?= $is_pd_role_active ? 'true' : 'false' ?>">
                            <span class="d-flex align-items-center">
                                <i class="bi bi-person-lines-fill me-2"></i> User Role Analytics
                            </span>
                            <i class="bi bi-chevron-down rotate-caret"></i>
                        </a>
                        <div class="collapse <?= $is_pd_role_active ? 'show' : '' ?>" id="pdUserRolesSubmenu">
                            <a class="nav-link submenu-link <?= (strpos($current_path, 'role=vet_surgeon') !== false || strpos($current_path, 'summary_vet_surgeon.php') !== false) ? 'active' : '' ?>" 
                               href="<?= $base_path ?>pages/modules/pd/role_hub.php?role=vet_surgeon">
                                <i class="bi bi-hospital me-2"></i> Veterinary Surgeons
                            </a>
                            <a class="nav-link submenu-link <?= (strpos($current_path, 'role=ldo') !== false || strpos($current_path, 'summary_ldo.php') !== false) ? 'active' : '' ?>" 
                               href="<?= $base_path ?>pages/modules/pd/role_hub.php?role=ldo">
                                <i class="bi bi-person-badge me-2"></i> Livestock Dev Officers
                            </a>
                            <a class="nav-link submenu-link <?= (strpos($current_path, 'role=sms') !== false || strpos($current_path, 'summary_sms.php') !== false) ? 'active' : '' ?>" 
                               href="<?= $base_path ?>pages/modules/pd/role_hub.php?role=sms">
                                <i class="bi bi-journal-medical me-2"></i> Specialist (SMS)
                            </a>
                            <a class="nav-link submenu-link <?= (strpos($current_path, 'role=district_dd') !== false || strpos($current_path, 'summary_district_dd.php') !== false) ? 'active' : '' ?>" 
                               href="<?= $base_path ?>pages/modules/pd/role_hub.php?role=district_dd">
                                <i class="bi bi-geo-alt me-2"></i> District Deputy Directors
                            </a>
                            <a class="nav-link submenu-link <?= (strpos($current_path, 'role=training_officer') !== false || strpos($current_path, 'summary_training_officer.php') !== false) ? 'active' : '' ?>" 
                               href="<?= $base_path ?>pages/modules/pd/role_hub.php?role=training_officer">
                                <i class="bi bi-mortarboard me-2"></i> Training Officers
                            </a>
                            <a class="nav-link submenu-link <?= (strpos($current_path, 'role=farms') !== false || strpos($current_path, 'summary_farms.php') !== false) ? 'active' : '' ?>" 
                               href="<?= $base_path ?>pages/modules/pd/role_hub.php?role=farms">
                                <i class="bi bi-flower1 me-2"></i> Farm Officers
                            </a>
                        </div>
                    <?php endif; ?>

                    <?php if ($is_hr_user): ?>
                        <!-- HR Management Menu -->
                        <a class="nav-link d-flex align-items-center px-4 py-3"
                            href="<?= $base_path ?>pages/modules/hr/employee_managment.php">
                            <i class="bi bi-people me-2"></i> HR Management
                        </a>

                        <a class="nav-link d-flex align-items-center px-4 py-3"
                            href="<?= $base_path ?>pages/modules/hr/leave_management.php">
                            <i class="bi bi-calendar-check me-2"></i> Leave Management
                        </a>
                        <a class="nav-link d-flex align-items-center px-4 py-3"
                            href="<?= $base_path ?>pages/modules/hr/inquiry_management.php">
                            <i class="bi bi-file-earmark-text me-2"></i> Documents
                        </a>

                        <a class="nav-link d-flex align-items-center px-4 py-3"
                            href="<?= $base_path ?>pages/modules/hr/todo_tasks.php">
                            <i class="bi bi-check2-square me-2"></i> To-Do Tasks
                        </a>
                        <a class="nav-link d-flex align-items-center px-4 py-3"
                            href="<?= $base_path ?>pages/modules/hr/rti_management.php">
                            <i class="bi bi-shield-shaded me-2"></i> RTI Management
                        </a>
                    <?php endif; ?>

                    <?php if ($is_finance_admin): ?>
                        <a class="nav-link d-flex align-items-center px-4 py-3"
                            href="<?= $base_path ?>pages/modules/finance/assets_management.php">
                            <i class="bi bi-cash-coin me-2"></i> Assets Management
                        </a>
                        <a class="nav-link d-flex align-items-center px-4 py-3"
                            href="<?= $base_path ?>pages/modules/finance/procurement_plan.php">
                            <i class="bi bi-cart-check me-2"></i> Procurement Plan
                        </a>
                        <a class="nav-link d-flex align-items-center px-4 py-3"
                            href="<?= $base_path ?>pages/modules/finance/finance_disbursementsources.php">
                            <i class="bi bi-wallet2 me-2"></i> Finance Disbursement
                        </a>
                        <a class="nav-link d-flex align-items-center px-4 py-3"
                            href="<?= $base_path ?>pages/modules/finance/veterinary_stores.php">
                            <i class="bi bi-shop me-2"></i> Veterinary Stores
                        </a>
                    <?php endif; ?>

                    <?php if ($is_planning_officer): ?>
                        <!-- planning Officer Menu-->
                        <a class="nav-link d-flex align-items-center px-4 py-3"
                            href="<?= $base_path ?>pages/modules/project/psdg_projects.php">
                            <i class="bi bi-kanban me-2"></i> Development Projects (PSDG/CBG/NGO)
                        </a>
                        <a class="nav-link d-flex align-items-center px-4 py-3"
                            href="<?= $base_path ?>pages/modules/project/progress_physical_financial.php">
                            <i class="bi bi-graph-up-arrow me-2"></i> Progress Reports (Physical & Financial)
                        </a>
                    <?php endif; ?>
                    <?php if ($is_sms): ?>
                        <!-- Subject Matter Specialist Menu-->
                        <a class="nav-link d-flex align-items-center px-4 py-3 <?= in_array(basename(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH)), ['office_details.php', 'lands_buildings.php', 'vehicles.php', 'furniture.php', 'machineries.php', 'instruments.php', 'counter_foilage.php', 'employee_managment.php']) && strpos($_SERVER['REQUEST_URI'], '/sms/') !== false ? 'active' : '' ?>"
                            href="<?= $base_path ?>pages/modules/sms/office_details.php">
                            <i class="bi bi-building me-2"></i> Office Details
                        </a>
                        <a class="nav-link d-flex align-items-center px-4 py-3 <?= (basename(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH)) === 'my_diary.php' || basename(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH)) === 'advanced_programme.php' || basename(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH)) === 'daily_diary.php') ? 'active' : '' ?>"
                            href="<?= $base_path ?>pages/modules/sms/my_diary.php">
                            <i class="bi bi-journal-bookmark me-2"></i> Diary and Advanced Programme
                        </a>
                        <a class="nav-link d-flex align-items-center px-4 py-3 <?= in_array(basename(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH)), ['immunization.php', 'vaccine_types.php']) ? 'active' : '' ?>"
                            href="<?= $base_path ?>pages/modules/sms/immunization.php">
                            <i class="bi bi-shield-plus me-2"></i> Immunization
                        </a>
                        <a class="nav-link d-flex align-items-center px-4 py-3 <?= (basename(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH)) === 'mobile_clinics.php') ? 'active' : '' ?>"
                            href="<?= $base_path ?>pages/modules/sms/mobile_clinics.php">
                            <i class="bi bi-truck me-2"></i> Mobile Clinics
                        </a>
                        <a class="nav-link d-flex align-items-center px-4 py-3 <?= (basename(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH)) === 'drug_maintenance.php') ? 'active' : '' ?>"
                            href="<?= $base_path ?>pages/modules/sms/drug_maintenance.php">
                            <i class="bi bi-prescription2 me-2"></i> Drug Maintenance
                        </a>
                        <a class="nav-link d-flex align-items-center px-4 py-3 <?= (basename(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH)) === 'outbreak_report.php') ? 'active' : '' ?>"
                            href="<?= $base_path ?>pages/modules/sms/outbreak_report.php">
                            <i class="bi bi-exclamation-triangle me-2"></i> Outbreak Report
                        </a>
                        <a class="nav-link d-flex align-items-center px-4 py-3 <?= (basename(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH)) === 'disease_control.php') ? 'active' : '' ?>"
                            href="<?= $base_path ?>pages/modules/sms/disease_control.php">
                            <i class="bi bi-virus me-2"></i> Disease Control
                        </a>
                    <?php endif; ?>

                    <?php if ($is_farms_dd): ?>
                        <!-- Farms Operations Menu -->
                        <a class="nav-link d-flex align-items-center px-4 py-3 <?= (basename(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH)) === 'office_details.php') ? 'active' : '' ?>"
                            href="<?= $base_path ?>pages/modules/farm/office_details.php">
                            <i class="bi bi-building me-2"></i> Office Details
                        </a>
                        <a class="nav-link d-flex align-items-center px-4 py-3 <?= (basename(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH)) === 'parent_stock_operations.php') ? 'active' : '' ?>"
                            href="<?= $base_path ?>pages/modules/farm/parent_stock_operations.php">
                            <i class="bi bi-collection me-2"></i> Parent Stock Operations
                        </a>
                        <a class="nav-link d-flex align-items-center px-4 py-3 <?= (basename(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH)) === 'hatchery_register.php') ? 'active' : '' ?>"
                            href="<?= $base_path ?>pages/modules/farm/hatchery_register.php">
                            <i class="bi bi-egg-fried me-2"></i> Hatchery Register
                        </a>
                        <a class="nav-link d-flex align-items-center px-4 py-3 <?= (basename(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH)) === 'chick_details.php') ? 'active' : '' ?>"
                            href="<?= $base_path ?>pages/modules/farm/chick_details.php">
                            <i class="bi bi-twitter me-2"></i> Chick Details
                        </a>
                        <a class="nav-link d-flex align-items-center px-4 py-3 <?= (basename(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH)) === 'feed_management.php') ? 'active' : '' ?>"
                            href="<?= $base_path ?>pages/modules/farm/feed_management.php">
                            <i class="bi bi-basket me-2"></i> Feed Management
                        </a>
                        <a class="nav-link d-flex align-items-center px-4 py-3 <?= (basename(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH)) === 'sales_of_eggs.php') ? 'active' : '' ?>"
                            href="<?= $base_path ?>pages/modules/farm/sales_of_eggs.php">
                            <i class="bi bi-cart me-2"></i> Sales of Eggs
                        </a>
                        <a class="nav-link d-flex align-items-center px-4 py-3 <?= (basename(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH)) === 'drug_details.php') ? 'active' : '' ?>"
                            href="<?= $base_path ?>pages/modules/farm/drug_details.php">
                            <i class="bi bi-capsule-pill me-2"></i> Drugs Details
                        </a>
                        <a class="nav-link d-flex align-items-center px-4 py-3 <?= (basename(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH)) === 'production_details.php') ? 'active' : '' ?>"
                            href="<?= $base_path ?>pages/modules/farm/production_details.php">
                            <i class="bi bi-gear me-2"></i> Production Details
                        </a>
                        <a class="nav-link d-flex align-items-center px-4 py-3 <?= (basename(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH)) === 'fuel_register.php') ? 'active' : '' ?>"
                            href="<?= $base_path ?>pages/modules/farm/fuel_register.php">
                            <i class="bi bi-fuel-pump me-2"></i> Fuel Register
                        </a>
                        <a class="nav-link d-flex align-items-center px-4 py-3 <?= (basename(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH)) === 'cattle_register.php') ? 'active' : '' ?>"
                            href="<?= $base_path ?>pages/modules/farm/cattle_register.php">
                            <i class="bi bi-record-circle me-2"></i> Cattle
                        </a>
                        <a class="nav-link d-flex align-items-center px-4 py-3 <?= (basename(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH)) === 'white_cattle_register.php') ? 'active' : '' ?>"
                            href="<?= $base_path ?>pages/modules/farm/white_cattle_register.php">
                            <i class="bi bi-circle me-2"></i> White Cattle
                        </a>
                        <a class="nav-link d-flex align-items-center px-4 py-3 <?= (basename(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH)) === 'buffalo_register.php') ? 'active' : '' ?>"
                            href="<?= $base_path ?>pages/modules/farm/buffalo_register.php">
                            <i class="bi bi-diamond me-2"></i> Buffalo
                        </a>
                        <a class="nav-link d-flex align-items-center px-4 py-3 <?= (basename(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH)) === 'goat_register.php') ? 'active' : '' ?>"
                            href="<?= $base_path ?>pages/modules/farm/goat_register.php">
                            <i class="bi bi-star me-2"></i> Goat
                        </a>
                        <a class="nav-link d-flex align-items-center px-4 py-3 <?= (basename(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH)) === 'accounts_register.php') ? 'active' : '' ?>"
                            href="<?= $base_path ?>pages/modules/farm/accounts_register.php">
                            <i class="bi bi-calculator me-2"></i> Accounts
                        </a>
                    <?php endif; ?>
                    <?php if ($is_training_officer): ?>
                        <a class="nav-link d-flex align-items-center px-4 py-3 <?= in_array(basename(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH)), ['office_details.php', 'lands_buildings.php', 'vehicles.php', 'furniture.php', 'machineries.php', 'instruments.php', 'counter_foilage.php', 'employee_managment.php']) && strpos($_SERVER['REQUEST_URI'], '/training/') !== false ? 'active' : '' ?>"
                            href="<?= $base_path ?>pages/modules/training/office_details.php">
                            <i class="bi bi-building me-2"></i> Office Details
                        </a>
                        <a class="nav-link d-flex align-items-center px-4 py-3 <?= (basename(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH)) === 'monthly_income_summary.php') ? 'active' : '' ?>"
                            href="<?= $base_path ?>pages/modules/training/monthly_income_summary.php">
                            <i class="bi bi-cash-stack me-2"></i> Monthly Income Summary
                        </a>
                        <a class="nav-link d-flex align-items-center px-4 py-3 <?= (basename(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH)) === 'advanced_programme.php') ? 'active' : '' ?>"
                            href="<?= $base_path ?>pages/modules/training/advanced_programme.php">
                            <i class="bi bi-calendar2-week me-2"></i> Advance Programme
                        </a>
                        <a class="nav-link d-flex align-items-center px-4 py-3 <?= ($current_file === 'produce_register.php') ? 'active' : '' ?>"
                            href="<?= $base_path ?>pages/modules/training/produce_register.php">
                            <i class="bi bi-journal-text me-2"></i> Produce Register (Perishables)
                        </a>
                    <?php endif; ?>
                    <?php if ($is_district_dd): ?>
                        <a class="nav-link d-flex align-items-center px-4 py-3 <?= ($current_file === 'office_details.php') ? 'active' : '' ?>"
                            href="<?= $base_path ?>pages/modules/district/office_details.php">
                            <i class="bi bi-building me-2"></i> Office Details
                        </a>
                        <a class="nav-link d-flex align-items-center px-4 py-3 <?= ($current_file === 'task_assignments.php') ? 'active' : '' ?>"
                            href="<?= $base_path ?>pages/modules/district/task_assignments.php">
                            <i class="bi bi-person-check me-2"></i> Task Delegation
                        </a>
                        <a class="nav-link d-flex align-items-center px-4 py-3 <?= ($current_file === 'range_veterinary_officers.php') ? 'active' : '' ?>"
                            href="<?= $base_path ?>pages/modules/district/range_veterinary_officers.php">
                            <i class="bi bi-person-badge me-2"></i> Range Veterinary Officer
                        </a>
                        <a class="nav-link d-flex align-items-center px-4 py-3 <?= ($current_file === 'regional_farms.php') ? 'active' : '' ?>"
                            href="<?= $base_path ?>pages/modules/district/regional_farms.php">
                            <i class="bi bi-flower1 me-2"></i> Regional Farms
                        </a>
                        <a class="nav-link d-flex align-items-center px-4 py-3 <?= ($current_file === 'training_centers.php') ? 'active' : '' ?>"
                            href="<?= $base_path ?>pages/modules/district/training_centers.php">
                            <i class="bi bi-mortarboard me-2"></i> Training Center
                        </a>
                        <a class="nav-link d-flex align-items-center px-4 py-3 <?= ($current_file === 'subject_matter_specialists.php') ? 'active' : '' ?>"
                            href="<?= $base_path ?>pages/modules/district/subject_matter_specialists.php">
                            <i class="bi bi-award me-2"></i> Subject Matter Specialist
                        </a>
                        <a class="nav-link d-flex align-items-center px-4 py-3 <?= ($current_file === 'users_summary.php') ? 'active' : '' ?>"
                            href="<?= $base_path ?>pages/modules/district/users_summary.php">
                            <i class="bi bi-people me-2"></i> Users Summary
                        </a>
                        <a class="nav-link d-flex align-items-center px-4 py-3 <?= ($current_file === 'diary_management.php') ? 'active' : '' ?>"
                            href="<?= $base_path ?>pages/modules/district/diary_management.php">
                            <i class="bi bi-journal-text me-2"></i> Diary Management
                        </a>
                        <a class="nav-link d-flex align-items-center px-4 py-3 <?= ($current_file === 'revenue_management.php') ? 'active' : '' ?>"
                            href="<?= $base_path ?>pages/modules/district/revenue_management.php">
                            <i class="bi bi-currency-exchange me-2"></i> Revenue Management
                        </a>
                        <a class="nav-link d-flex align-items-center px-4 py-3 <?= ($current_file === 'district_revenue_summary.php') ? 'active' : '' ?>"
                            href="<?= $base_path ?>pages/modules/district/district_revenue_summary.php">
                            <i class="bi bi-bar-chart-line me-2"></i> District Revenue Summary
                        </a>
                    <?php endif; ?>
                    <?php if ($is_veterinary_surgeon): ?>
                        <a class="nav-link d-flex align-items-center px-4 py-3"
                            href="<?= $base_path ?>pages/modules/veterinary/office_details.php">
                            <i class="bi bi-building me-2"></i> Office Details
                        </a>
                        <a class="nav-link d-flex align-items-center px-4 py-3 <?= (basename(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH)) === 'range_details.php') ? 'active' : '' ?>"
                            href="<?= $base_path ?>pages/modules/veterinary/range_details.php">
                            <i class="bi bi-geo-alt me-2"></i> Range Details
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
                            <i class="bi bi-journal-text me-2"></i> My Diary
                        </a>
                        <a class="nav-link d-flex align-items-center px-4 py-3"
                            href="<?= $base_path ?>pages/modules/employee/leave_requests.php">
                            <i class="bi bi-calendar-minus me-2"></i> Leave Requests
                        </a>
                        <!-- profile -->
                        <a class="nav-link d-flex align-items-center px-4 py-3 <?= (basename(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH)) === 'profile.php') ? 'active' : '' ?>"
                            href="<?= $base_path ?>pages/profile.php">
                            <i class="bi bi-person-circle me-2"></i> Profile
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