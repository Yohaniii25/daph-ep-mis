<?php
// pages/modules/district/range_veterinary_officers.php -> Comprehensive Range Veterinary Overview & Annual Returns Statistics
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$allowed_roles = ['district_dd', 'deputy_director_district', 'administrator', 'provincial_director', 'deputy_director_hq_1', 'deputy_director_hq_2'];
if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], $allowed_roles)) {
    die("Access denied");
}

require_once '../../../config/db_connect.php';
require_once '../../../includes/header.php';
require_once '../../../includes/sidebar.php';

// Resolve District
$district_id = $_SESSION['district_id'] ?? null;
$district_name = $_SESSION['district'] ?? '';

if (empty($district_id) && !empty($district_name)) {
    if (strcasecmp($district_name, 'Amparai') === 0 || strcasecmp($district_name, 'Ampara') === 0) {
        $district_id = 1;
    } elseif (strcasecmp($district_name, 'Batticaloa') === 0) {
        $district_id = 2;
    } elseif (strcasecmp($district_name, 'Trincomalee') === 0) {
        $district_id = 3;
    }
}
if (empty($district_id)) $district_id = 1;

// Fetch official district name
$dist_stmt = $mysqli->prepare("SELECT name FROM districts WHERE id = ? LIMIT 1");
if ($dist_stmt) {
    $dist_stmt->bind_param("i", $district_id);
    $dist_stmt->execute();
    $dist_res = $dist_stmt->get_result();
    if ($row = $dist_res->fetch_assoc()) {
        $district_name = $row['name'];
    }
    $dist_stmt->close();
}

// Active Tab / View Selection
$current_view = isset($_GET['view']) ? trim($_GET['view']) : 'officers';
$selected_range_id = isset($_GET['range_id']) ? intval($_GET['range_id']) : 0;

// Fetch all ranges in this district for dropdown & metrics
$ranges_stmt = $mysqli->prepare("SELECT id, name, code, is_active FROM veterinary_ranges WHERE district_id = ? ORDER BY name ASC");
$ranges_list = [];
if ($ranges_stmt) {
    $ranges_stmt->bind_param("i", $district_id);
    $ranges_stmt->execute();
    $ranges_list = $ranges_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $ranges_stmt->close();
}

// Fetch Map for selected range or first range
$active_range_for_map = $selected_range_id > 0 ? $selected_range_id : ($ranges_list[0]['id'] ?? 0);
$map_url = '';
$active_range_name = 'All Ranges in ' . $district_name;

if ($active_range_for_map > 0) {
    $map_stmt = $mysqli->prepare("SELECT vrm.iframe_url, vr.name FROM veterinary_ranges vr LEFT JOIN veterinary_range_maps vrm ON vr.id = vrm.range_id WHERE vr.id = ?");
    if ($map_stmt) {
        $map_stmt->bind_param("i", $active_range_for_map);
        $map_stmt->execute();
        $map_res = $map_stmt->get_result();
        if ($m_row = $map_res->fetch_assoc()) {
            $map_url = $m_row['iframe_url'] ?? '';
            if ($selected_range_id > 0) {
                $active_range_name = $m_row['name'];
            }
        }
        $map_stmt->close();
    }
}

// Metric Totals
$total_ranges = count($ranges_list);

// Fetch VS List
$vs_query = "SELECT u.id AS user_id, u.username, u.full_name, u.email, u.phone, u.designation, u.is_active, u.last_login,
                    vr.id AS range_id, vr.name AS range_name,
                    (SELECT COUNT(*) FROM users staff WHERE staff.range_id = vr.id AND staff.is_active = 1) AS total_staff,
                    (SELECT COUNT(*) FROM animal_health_records ahr WHERE ahr.range_id = vr.id) AS total_treatments,
                    (SELECT COUNT(*) FROM breeding_ai_performance bai WHERE bai.range_id = vr.id) AS total_ai
             FROM users u
             LEFT JOIN veterinary_ranges vr ON u.range_id = vr.id
             WHERE u.role = 'veterinary_surgeon' AND (u.district_id = ? OR vr.district_id = ?) ";
if ($selected_range_id > 0) {
    $vs_query .= " AND vr.id = " . intval($selected_range_id);
}
$vs_query .= " ORDER BY vr.name ASC, u.full_name ASC";

$vs_list = [];
if ($vs_stmt = $mysqli->prepare($vs_query)) {
    $vs_stmt->bind_param("ii", $district_id, $district_id);
    $vs_stmt->execute();
    $vs_list = $vs_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $vs_stmt->close();
}

// Summary Metrics
$total_vs = count($vs_list);
$total_treatments_all = array_sum(array_column($vs_list, 'total_treatments'));
$total_ai_all = array_sum(array_column($vs_list, 'total_ai'));

// Fetch dataset for the active view
$report_records = [];

// 1. Animal Population
if ($current_view === 'statistics' || $current_view === 'animal_pop') {
    $stat_sql = "SELECT ap.id, ap.year, ap.animal_type, ap.quantity, ap.updated_at, vr.name AS range_name 
                 FROM animal_populations ap 
                 JOIN veterinary_ranges vr ON ap.range_id = vr.id 
                 WHERE vr.district_id = ?";
    if ($selected_range_id > 0) $stat_sql .= " AND vr.id = " . intval($selected_range_id);
    $stat_sql .= " ORDER BY ap.year DESC, vr.name ASC";
    if ($st = $mysqli->prepare($stat_sql)) {
        $st->bind_param("i", $district_id);
        $st->execute();
        $report_records = $st->get_result()->fetch_all(MYSQLI_ASSOC);
        $st->close();
    }
}
// 2. Human Population
elseif ($current_view === 'human_pop') {
    $hp_sql = "SELECT hp.id, hp.year, hp.ethnicity, hp.population_type, hp.population_count, hp.created_at, vr.name AS range_name 
               FROM human_populations hp 
               JOIN veterinary_ranges vr ON hp.range_id = vr.id 
               WHERE vr.district_id = ?";
    if ($selected_range_id > 0) $hp_sql .= " AND vr.id = " . intval($selected_range_id);
    $hp_sql .= " ORDER BY hp.year DESC, vr.name ASC";
    if ($st = $mysqli->prepare($hp_sql)) {
        $st->bind_param("i", $district_id);
        $st->execute();
        $report_records = $st->get_result()->fetch_all(MYSQLI_ASSOC);
        $st->close();
    }
}
// 3. Production Levels
elseif ($current_view === 'prod_levels') {
    $pl_sql = "SELECT apl.*, vr.name AS range_name, u.full_name AS recorded_by 
               FROM annual_production_levels apl 
               JOIN veterinary_ranges vr ON apl.range_id = vr.id 
               LEFT JOIN users u ON apl.created_by = u.id 
               WHERE (apl.district_id = ? OR vr.district_id = ?)";
    if ($selected_range_id > 0) $pl_sql .= " AND vr.id = " . intval($selected_range_id);
    $pl_sql .= " ORDER BY apl.report_year DESC, vr.name ASC";
    if ($st = $mysqli->prepare($pl_sql)) {
        $st->bind_param("ii", $district_id, $district_id);
        $st->execute();
        $report_records = $st->get_result()->fetch_all(MYSQLI_ASSOC);
        $st->close();
    }
}
// 4. Pasture & Fodder Lands
elseif ($current_view === 'pasture_lands') {
    $pfl_sql = "SELECT apf.*, vr.name AS range_name, u.full_name AS recorded_by 
                FROM annual_pasture_fodder_lands apf 
                JOIN veterinary_ranges vr ON apf.range_id = vr.id 
                LEFT JOIN users u ON apf.created_by = u.id 
                WHERE (apf.district_id = ? OR vr.district_id = ?)";
    if ($selected_range_id > 0) $pfl_sql .= " AND vr.id = " . intval($selected_range_id);
    $pfl_sql .= " ORDER BY apf.report_year DESC, vr.name ASC";
    if ($st = $mysqli->prepare($pfl_sql)) {
        $st->bind_param("ii", $district_id, $district_id);
        $st->execute();
        $report_records = $st->get_result()->fetch_all(MYSQLI_ASSOC);
        $st->close();
    }
}
// 5. Pasture Yields
elseif ($current_view === 'pasture_yields') {
    $py_sql = "SELECT apy.*, vr.name AS range_name, u.full_name AS recorded_by 
               FROM annual_pasture_yields apy 
               JOIN veterinary_ranges vr ON apy.range_id = vr.id 
               LEFT JOIN users u ON apy.created_by = u.id 
               WHERE (apy.district_id = ? OR vr.district_id = ?)";
    if ($selected_range_id > 0) $py_sql .= " AND vr.id = " . intval($selected_range_id);
    $py_sql .= " ORDER BY apy.report_year DESC, vr.name ASC";
    if ($st = $mysqli->prepare($py_sql)) {
        $st->bind_param("ii", $district_id, $district_id);
        $st->execute();
        $report_records = $st->get_result()->fetch_all(MYSQLI_ASSOC);
        $st->close();
    }
}
// 6. Producers & Processors
elseif ($current_view === 'producers') {
    $pp_sql = "SELECT app.*, vr.name AS range_name, u.full_name AS recorded_by 
               FROM annual_producers_processors app 
               JOIN veterinary_ranges vr ON app.range_id = vr.id 
               LEFT JOIN users u ON app.created_by = u.id 
               WHERE (app.district_id = ? OR vr.district_id = ?)";
    if ($selected_range_id > 0) $pp_sql .= " AND vr.id = " . intval($selected_range_id);
    $pp_sql .= " ORDER BY app.report_year DESC, vr.name ASC";
    if ($st = $mysqli->prepare($pp_sql)) {
        $st->bind_param("ii", $district_id, $district_id);
        $st->execute();
        $report_records = $st->get_result()->fetch_all(MYSQLI_ASSOC);
        $st->close();
    }
}
// 7. Feed Production
elseif ($current_view === 'feed_prod') {
    $fp_sql = "SELECT afp.*, vr.name AS range_name, u.full_name AS recorded_by 
               FROM annual_feed_production afp 
               JOIN veterinary_ranges vr ON afp.range_id = vr.id 
               LEFT JOIN users u ON afp.created_by = u.id 
               WHERE (afp.district_id = ? OR vr.district_id = ?)";
    if ($selected_range_id > 0) $fp_sql .= " AND vr.id = " . intval($selected_range_id);
    $fp_sql .= " ORDER BY afp.report_year DESC, vr.name ASC";
    if ($st = $mysqli->prepare($fp_sql)) {
        $st->bind_param("ii", $district_id, $district_id);
        $st->execute();
        $report_records = $st->get_result()->fetch_all(MYSQLI_ASSOC);
        $st->close();
    }
}
// 8. Livestock Societies
elseif ($current_view === 'societies') {
    $ls_sql = "SELECT als.*, vr.name AS range_name, u.full_name AS recorded_by 
               FROM annual_livestock_societies als 
               JOIN veterinary_ranges vr ON als.range_id = vr.id 
               LEFT JOIN users u ON als.created_by = u.id 
               WHERE (als.district_id = ? OR vr.district_id = ?)";
    if ($selected_range_id > 0) $ls_sql .= " AND vr.id = " . intval($selected_range_id);
    $ls_sql .= " ORDER BY als.report_year DESC, vr.name ASC";
    if ($st = $mysqli->prepare($ls_sql)) {
        $st->bind_param("ii", $district_id, $district_id);
        $st->execute();
        $report_records = $st->get_result()->fetch_all(MYSQLI_ASSOC);
        $st->close();
    }
}
// 9. Milk Collecting Centers
elseif ($current_view === 'milk_collecting') {
    $mcc_sql = "SELECT amc.*, vr.name AS range_name, u.full_name AS recorded_by 
                FROM annual_milk_collecting_centers amc 
                JOIN veterinary_ranges vr ON amc.range_id = vr.id 
                LEFT JOIN users u ON amc.created_by = u.id 
                WHERE (amc.district_id = ? OR vr.district_id = ?)";
    if ($selected_range_id > 0) $mcc_sql .= " AND vr.id = " . intval($selected_range_id);
    $mcc_sql .= " ORDER BY amc.report_year DESC, vr.name ASC";
    if ($st = $mysqli->prepare($mcc_sql)) {
        $st->bind_param("ii", $district_id, $district_id);
        $st->execute();
        $report_records = $st->get_result()->fetch_all(MYSQLI_ASSOC);
        $st->close();
    }
}
// 10. Milk Processing Centers
elseif ($current_view === 'milk_processing') {
    $mpc_sql = "SELECT amp.*, vr.name AS range_name, u.full_name AS recorded_by 
                FROM annual_milk_processing_centers amp 
                JOIN veterinary_ranges vr ON amp.range_id = vr.id 
                LEFT JOIN users u ON amp.created_by = u.id 
                WHERE (amp.district_id = ? OR vr.district_id = ?)";
    if ($selected_range_id > 0) $mpc_sql .= " AND vr.id = " . intval($selected_range_id);
    $mpc_sql .= " ORDER BY amp.report_year DESC, vr.name ASC";
    if ($st = $mysqli->prepare($mpc_sql)) {
        $st->bind_param("ii", $district_id, $district_id);
        $st->execute();
        $report_records = $st->get_result()->fetch_all(MYSQLI_ASSOC);
        $st->close();
    }
}
// 11. Milk Sales Centers
elseif ($current_view === 'milk_sales') {
    $msc_sql = "SELECT ams.*, vr.name AS range_name, u.full_name AS recorded_by 
                FROM annual_milk_sales_centers ams 
                JOIN veterinary_ranges vr ON ams.range_id = vr.id 
                LEFT JOIN users u ON ams.created_by = u.id 
                WHERE (ams.district_id = ? OR vr.district_id = ?)";
    if ($selected_range_id > 0) $msc_sql .= " AND vr.id = " . intval($selected_range_id);
    $msc_sql .= " ORDER BY ams.report_year DESC, vr.name ASC";
    if ($st = $mysqli->prepare($msc_sql)) {
        $st->bind_param("ii", $district_id, $district_id);
        $st->execute();
        $report_records = $st->get_result()->fetch_all(MYSQLI_ASSOC);
        $st->close();
    }
}
// 12. Annual Vaccination Targets
elseif ($current_view === 'targets') {
    $tgt_sql = "SELECT avt.id, avt.year, avt.animal_type, avt.target_fmd, avt.target_bq, avt.target_hs, avt.available_ldo_count, avt.allocated_ldo_target, vr.name AS range_name, u.full_name AS vaccinator_name
                FROM annual_vaccination_targets avt
                JOIN veterinary_ranges vr ON avt.range_id = vr.id
                LEFT JOIN users u ON avt.assigned_vaccinator_id = u.id
                WHERE vr.district_id = ?";
    if ($selected_range_id > 0) $tgt_sql .= " AND vr.id = " . intval($selected_range_id);
    $tgt_sql .= " ORDER BY avt.year DESC, vr.name ASC";
    if ($st = $mysqli->prepare($tgt_sql)) {
        $st->bind_param("i", $district_id);
        $st->execute();
        $report_records = $st->get_result()->fetch_all(MYSQLI_ASSOC);
        $st->close();
    }
}
// 13. Animal Health
elseif ($current_view === 'health') {
    $hlth_sql = "SELECT ahr.id, ahr.date, ahr.farmer_reg_no, ahr.animal_type, ahr.disease_name, ahr.occurrence_count, ahr.vaccine_name, ahr.doses, ahr.treatment_details, ahr.report_status, vr.name AS range_name, u.full_name AS recorded_by
                 FROM animal_health_records ahr
                 JOIN veterinary_ranges vr ON ahr.range_id = vr.id
                 LEFT JOIN users u ON ahr.created_by = u.id
                 WHERE vr.district_id = ?";
    if ($selected_range_id > 0) $hlth_sql .= " AND vr.id = " . intval($selected_range_id);
    $hlth_sql .= " ORDER BY ahr.date DESC";
    if ($st = $mysqli->prepare($hlth_sql)) {
        $st->bind_param("i", $district_id);
        $st->execute();
        $report_records = $st->get_result()->fetch_all(MYSQLI_ASSOC);
        $st->close();
    }
}
// 14. Breeding AI
elseif ($current_view === 'breeding') {
    $brd_sql = "SELECT bai.id, bai.report_year, bai.report_month, bai.ai_date, bai.cow_id, bai.semen_code, bai.ai_type, bai.technician_code, vr.name AS range_name, u.full_name AS recorded_by
                FROM breeding_ai_performance bai
                JOIN veterinary_ranges vr ON bai.range_id = vr.id
                LEFT JOIN users u ON bai.created_by = u.id
                WHERE vr.district_id = ?";
    if ($selected_range_id > 0) $brd_sql .= " AND vr.id = " . intval($selected_range_id);
    $brd_sql .= " ORDER BY bai.ai_date DESC";
    if ($st = $mysqli->prepare($brd_sql)) {
        $st->bind_param("i", $district_id);
        $st->execute();
        $report_records = $st->get_result()->fetch_all(MYSQLI_ASSOC);
        $st->close();
    }
}
// 15. Dairy Hub
elseif ($current_view === 'dairy') {
    $dry_sql = "SELECT dh.id, dh.range_id, dh.center_name, dh.center_type, dh.location, dh.contact_person, dh.contact_number, dh.daily_collection_liters, dh.status, vr.name AS range_name 
                FROM dairy_hub_records dh
                JOIN veterinary_ranges vr ON dh.range_id = vr.id
                WHERE vr.district_id = ?";
    if ($selected_range_id > 0) $dry_sql .= " AND vr.id = " . intval($selected_range_id);
    $dry_sql .= " ORDER BY vr.name ASC";
    if ($st = $mysqli->prepare($dry_sql)) {
        $st->bind_param("i", $district_id);
        $st->execute();
        $report_records = $st->get_result()->fetch_all(MYSQLI_ASSOC);
        $st->close();
    }
}
// 16. Revenue
elseif ($current_view === 'revenue') {
    $rev_sql = "SELECT cbs.id, cbs.report_year, cbs.report_month, cbs.item_name, cbs.quantity_sold, cbs.unit_price, cbs.total_amount, cbs.amount_deposited, vr.name AS range_name, u.full_name AS created_by_name
                FROM cash_book_summaries cbs
                JOIN veterinary_ranges vr ON cbs.range_id = vr.id
                LEFT JOIN users u ON cbs.created_by = u.id
                WHERE (cbs.district_id = ? OR vr.district_id = ?)";
    if ($selected_range_id > 0) $rev_sql .= " AND vr.id = " . intval($selected_range_id);
    $rev_sql .= " ORDER BY cbs.report_year DESC, cbs.report_month DESC";
    if ($st = $mysqli->prepare($rev_sql)) {
        $st->bind_param("ii", $district_id, $district_id);
        $st->execute();
        $report_records = $st->get_result()->fetch_all(MYSQLI_ASSOC);
        $st->close();
    }
}
// 17. Diaries
elseif ($current_view === 'diaries') {
    $dir_sql = "SELECT dt.id, dt.task_date, dt.activity_title, dt.activity_description, dt.status, dt.created_at, vr.name AS range_name, u.full_name AS officer_name, u.designation
                FROM diary_tasks dt
                JOIN users u ON dt.user_id = u.id
                LEFT JOIN veterinary_ranges vr ON u.range_id = vr.id
                WHERE (u.district_id = ? OR vr.district_id = ?)";
    if ($selected_range_id > 0) $dir_sql .= " AND vr.id = " . intval($selected_range_id);
    $dir_sql .= " ORDER BY dt.task_date DESC LIMIT 100";
    if ($st = $mysqli->prepare($dir_sql)) {
        $st->bind_param("ii", $district_id, $district_id);
        $st->execute();
        $report_records = $st->get_result()->fetch_all(MYSQLI_ASSOC);
        $st->close();
    }
}

$is_statistics_category = in_array($current_view, [
    'statistics', 'animal_pop', 'human_pop', 'prod_levels', 'pasture_lands', 
    'pasture_yields', 'producers', 'feed_prod', 'societies', 
    'milk_collecting', 'milk_processing', 'milk_sales'
]);

// Determine record count for badge
$active_record_count = ($current_view === 'officers') ? count($vs_list) : count($report_records);
?>

<!-- DataTables + Buttons CSS -->
<link rel="stylesheet" href="../../../assets/css/dataTables.bootstrap5.min.css">
<link rel="stylesheet" href="../../../assets/css/buttons.bootstrap5.min.css">

<style>
    /* Modern UI Design System for Range Veterinary Officers */
    :root {
        --daph-maroon: #820100;
        --daph-dark-maroon: #4a0000;
        --daph-gold: #b08723;
        --daph-gold-light: #fdf6e7;
    }

    .kpi-stat-card {
        border: none;
        border-radius: 14px;
        transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
        background: #ffffff;
        box-shadow: 0 2px 12px rgba(0, 0, 0, 0.04);
        position: relative;
        overflow: hidden;
    }
    .kpi-stat-card::after {
        content: '';
        position: absolute;
        bottom: 0;
        left: 0;
        right: 0;
        height: 3px;
        background: transparent;
        transition: all 0.2s ease;
    }
    .kpi-stat-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 8px 24px rgba(0, 0, 0, 0.09);
    }
    .kpi-stat-card.kpi-maroon::after { background: linear-gradient(90deg, #820100, #b71c1c); }
    .kpi-stat-card.kpi-success::after { background: linear-gradient(90deg, #2e7d32, #43a047); }
    .kpi-stat-card.kpi-info::after { background: linear-gradient(90deg, #0288d1, #00acc1); }
    .kpi-stat-card.kpi-warning::after { background: linear-gradient(90deg, #f57c00, #ffa000); }

    .kpi-icon-wrapper {
        width: 48px;
        height: 48px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.35rem;
    }

    /* Primary Module Buttons */
    .btn-range-action {
        min-height: 100px;
        transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
        border: none;
        border-radius: 14px;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        color: #ffffff !important;
        text-decoration: none;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
        position: relative;
        padding: 14px 10px;
    }
    .btn-range-action:hover {
        transform: translateY(-4px);
        box-shadow: 0 10px 22px rgba(0, 0, 0, 0.2);
        filter: brightness(1.1);
        color: #ffffff !important;
    }
    .btn-range-action.active {
        box-shadow: 0 0 0 3px #ffffff, 0 0 0 6px var(--daph-maroon), 0 12px 24px rgba(130, 1, 0, 0.35);
        filter: brightness(1.05);
        font-weight: 700;
    }
    .btn-range-action.active::before {
        content: '';
        position: absolute;
        top: -6px;
        width: 14px;
        height: 6px;
        border-radius: 4px;
        background: #ffffff;
    }
    .btn-range-action i {
        font-size: 1.6rem;
        margin-bottom: 6px;
    }

    /* Sub-category pill buttons */
    .sub-stat-pill {
        border-radius: 50px;
        font-size: 0.82rem;
        padding: 7px 16px;
        font-weight: 600;
        transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
        display: inline-flex;
        align-items: center;
        border-width: 1.5px;
    }
    .sub-stat-pill:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(0,0,0,0.1);
    }
    .sub-stat-pill.active-pill {
        box-shadow: 0 4px 14px rgba(0, 0, 0, 0.18);
        font-weight: 700;
        transform: translateY(-1px);
    }

    /* Export Buttons Styling */
    .dt-buttons .btn {
        margin-right: 6px;
        margin-bottom: 4px;
        border-radius: 8px;
        font-weight: 600;
        font-size: 0.82rem;
        padding: 5px 12px;
        box-shadow: 0 2px 6px rgba(0,0,0,0.06);
        transition: all 0.15s ease;
    }
    .dt-buttons .btn:hover {
        transform: translateY(-1px);
        box-shadow: 0 4px 10px rgba(0,0,0,0.12);
    }

    /* Table Enhancements */
    #summaryDataTable thead th {
        background-color: #f8fafc;
        color: #334155;
        font-weight: 700;
        font-size: 0.82rem;
        text-transform: uppercase;
        letter-spacing: 0.4px;
        border-bottom: 2px solid #e2e8f0;
        padding: 12px 14px;
    }
    #summaryDataTable tbody td {
        padding: 11px 14px;
        font-size: 0.875rem;
    }
    #summaryDataTable tbody tr:hover {
        background-color: rgba(130, 1, 0, 0.03) !important;
    }

    .card-modern {
        border: none;
        border-radius: 14px;
        box-shadow: 0 2px 14px rgba(0,0,0,0.04);
        background: #ffffff;
    }
</style>

<div id="layoutSidenav_content" class="bg-light">
    <main class="container-fluid px-4 pt-4 pb-5">
        
        <!-- Header & Breadcrumbs -->
        <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 pb-2 border-bottom gap-3">
            <div>
                <div class="d-flex align-items-center gap-2 mb-1">
                    <span class="badge bg-danger-subtle text-danger border border-danger-subtle px-2 py-1 rounded-pill small fw-bold">
                        <i class="bi bi-shield-check me-1"></i> District Administration
                    </span>
                    <span class="text-muted small">/</span>
                    <span class="text-muted small fw-medium"><?= htmlspecialchars($district_name) ?> District</span>
                </div>
                <h2 class="text-dark fw-bold mb-0">Range Veterinary Details &amp; Records Summary</h2>
                <p class="text-muted small mb-0 mt-1">Operational indicators, clinical records &amp; exportable datasets for <strong><?= htmlspecialchars($district_name) ?> District</strong>.</p>
            </div>
            
            <div class="d-flex align-items-center flex-wrap gap-2">
                <!-- Range Selector Filter -->
                <form method="GET" action="" class="d-flex align-items-center gap-2 m-0">
                    <input type="hidden" name="view" value="<?= htmlspecialchars($current_view) ?>">
                    <div class="input-group input-group-sm shadow-sm">
                        <span class="input-group-text bg-white border-end-0 text-muted">
                            <i class="bi bi-funnel-fill text-danger"></i>
                        </span>
                        <select name="range_id" class="form-select form-select-sm border-start-0 ps-0 fw-semibold" onchange="this.form.submit()" style="min-width: 210px;">
                            <option value="0">All Ranges (<?= count($ranges_list) ?> Offices)</option>
                            <?php foreach ($ranges_list as $rng): ?>
                                <option value="<?= $rng['id'] ?>" <?= $selected_range_id == $rng['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($rng['name']) ?> Range
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </form>

                <?php if ($selected_range_id > 0): ?>
                    <a href="?view=<?= urlencode($current_view) ?>&range_id=0" class="btn btn-outline-secondary btn-sm shadow-sm" title="Show all ranges">
                        <i class="bi bi-x-circle me-1"></i> Clear Filter
                    </a>
                <?php endif; ?>

                <a href="../../../dashboard.php" class="btn btn-secondary btn-sm shadow-sm">
                    <i class="bi bi-arrow-left me-1"></i> Dashboard
                </a>
            </div>
        </div>

        <!-- Metric KPI Cards Strip -->
        <div class="row g-3 mb-4">
            <!-- 1. Total Ranges -->
            <div class="col-12 col-sm-6 col-xl-3">
                <div class="card card-modern kpi-stat-card kpi-maroon h-100 p-3">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <span class="text-muted text-uppercase fw-bold" style="font-size: 0.75rem; letter-spacing: 0.5px;">VS Range Offices</span>
                            <h3 class="fw-bold text-dark mt-1 mb-0"><?= number_format($total_ranges) ?></h3>
                            <small class="text-muted">In <?= htmlspecialchars($district_name) ?></small>
                        </div>
                        <div class="kpi-icon-wrapper bg-danger-subtle text-danger">
                            <i class="bi bi-building"></i>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 2. Assigned Surgeons -->
            <div class="col-12 col-sm-6 col-xl-3">
                <div class="card card-modern kpi-stat-card kpi-success h-100 p-3">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <span class="text-muted text-uppercase fw-bold" style="font-size: 0.75rem; letter-spacing: 0.5px;">Veterinary Surgeons</span>
                            <h3 class="fw-bold text-dark mt-1 mb-0"><?= number_format($total_vs) ?></h3>
                            <small class="text-success fw-medium"><i class="bi bi-check2-circle me-1"></i>Active Officers</small>
                        </div>
                        <div class="kpi-icon-wrapper bg-success-subtle text-success">
                            <i class="bi bi-person-badge-fill"></i>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 3. Total Treatments -->
            <div class="col-12 col-sm-6 col-xl-3">
                <div class="card card-modern kpi-stat-card kpi-info h-100 p-3">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <span class="text-muted text-uppercase fw-bold" style="font-size: 0.75rem; letter-spacing: 0.5px;">Clinical Cases</span>
                            <h3 class="fw-bold text-dark mt-1 mb-0"><?= number_format($total_treatments_all) ?></h3>
                            <small class="text-info fw-medium">Animal Health Logged</small>
                        </div>
                        <div class="kpi-icon-wrapper bg-info-subtle text-info">
                            <i class="bi bi-heart-pulse-fill"></i>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 4. Total Breeding AI -->
            <div class="col-12 col-sm-6 col-xl-3">
                <div class="card card-modern kpi-stat-card kpi-warning h-100 p-3">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <span class="text-muted text-uppercase fw-bold" style="font-size: 0.75rem; letter-spacing: 0.5px;">Breeding AI Records</span>
                            <h3 class="fw-bold text-dark mt-1 mb-0"><?= number_format($total_ai_all) ?></h3>
                            <small class="text-warning fw-medium">Inseminations</small>
                        </div>
                        <div class="kpi-icon-wrapper bg-warning-subtle text-warning">
                            <i class="bi bi-activity"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Range Profile & Map Row -->
        <div class="row g-3 mb-4">
            <div class="col-12 col-lg-4">
                <div class="card card-modern h-100">
                    <div class="card-header bg-white pt-3 px-4 border-0">
                        <h6 class="fw-bold mb-1 text-dark"><i class="bi bi-geo-fill text-danger me-2"></i>District Jurisdiction Profile</h6>
                        <p class="text-muted small mb-0">Assigned administrative footprint and scope.</p>
                    </div>
                    <div class="card-body px-4 pt-1">
                        <div class="table-responsive">
                            <table class="table table-bordered align-middle m-0 small">
                                <tbody>
                                    <tr>
                                        <th class="bg-light text-muted" style="width: 45%;">District</th>
                                        <td class="fw-bold text-danger"><?= htmlspecialchars($district_name) ?></td>
                                    </tr>
                                    <tr>
                                        <th class="bg-light text-muted">Total VS Ranges</th>
                                        <td class="fw-bold text-dark"><?= number_format($total_ranges) ?> Range Offices</td>
                                    </tr>
                                    <tr>
                                        <th class="bg-light text-muted">In-Charge Surgeons</th>
                                        <td class="fw-bold text-success"><?= number_format($total_vs) ?> Officers</td>
                                    </tr>
                                    <tr>
                                        <th class="bg-light text-muted">Active Scope Filter</th>
                                        <td>
                                            <span class="badge bg-primary-subtle text-primary border border-primary-subtle fw-semibold">
                                                <i class="bi bi-geo-alt me-1"></i><?= htmlspecialchars($active_range_name) ?>
                                            </span>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-12 col-lg-8">
                <div class="card card-modern h-100">
                    <div class="card-header bg-white pt-3 px-4 border-0 d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="fw-bold mb-1 text-dark"><i class="bi bi-map-fill text-primary me-2"></i>Range Map View (<?= htmlspecialchars($active_range_name) ?>)</h6>
                            <p class="text-muted small mb-0">Geographical boundary tracking and spatial coordinates.</p>
                        </div>
                        <?php if ($selected_range_id > 0): ?>
                            <a href="?view=<?= urlencode($current_view) ?>&range_id=0" class="btn btn-sm btn-outline-secondary">Reset to All Ranges</a>
                        <?php endif; ?>
                    </div>
                    <div class="card-body px-4 pb-4 pt-1">
                        <div class="rounded-3 overflow-hidden shadow-sm border bg-light" style="min-height: 180px;">
                            <?php if (!empty($map_url) && filter_var($map_url, FILTER_VALIDATE_URL)): ?>
                                <iframe src="<?= htmlspecialchars($map_url, ENT_QUOTES, 'UTF-8') ?>" width="100%" height="180" style="border:0;" allowfullscreen="" loading="lazy"></iframe>
                            <?php else: ?>
                                <div class="w-100 d-flex flex-column align-items-center justify-content-center py-4 text-muted" style="min-height: 180px;">
                                    <i class="bi bi-geo-alt-fill fs-2 text-warning mb-1"></i>
                                    <span class="small fw-semibold">Interactive map viewport active for <?= htmlspecialchars($active_range_name) ?>.</span>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Primary Quick Action Module Buttons -->
        <div class="card card-modern mb-4">
            <div class="card-header bg-white py-3 px-4 border-0 d-flex justify-content-between align-items-center">
                <h6 class="mb-0 fw-bold text-dark"><i class="bi bi-grid-3x3-gap-fill text-danger me-2"></i>Range Module Records &amp; Data Summaries</h6>
                <small class="text-muted d-none d-md-inline">Select a module to filter operational datasets</small>
            </div>
            <div class="card-body px-4 pt-0">
                <div class="row row-cols-2 row-cols-md-4 row-cols-lg-8 g-2">
                    
                    <!-- 1. Officers -->
                    <div class="col">
                        <a href="?view=officers&range_id=<?= $selected_range_id ?>" class="btn-range-action <?= $current_view === 'officers' ? 'active' : '' ?>" style="background: linear-gradient(145deg, #820100, #a30100);">
                            <i class="bi bi-people-fill"></i>
                            <span class="text-center">VS Officers</span>
                        </a>
                    </div>

                    <!-- 2. Statistics -->
                    <div class="col">
                        <a href="?view=statistics&range_id=<?= $selected_range_id ?>" class="btn-range-action <?= $is_statistics_category ? 'active' : '' ?>" style="background: linear-gradient(145deg, #4a0000, #6d0000);">
                            <i class="bi bi-bar-chart-line-fill"></i>
                            <span class="text-center">Range Statistics</span>
                        </a>
                    </div>

                    <!-- 3. Annual Targets -->
                    <div class="col">
                        <a href="?view=targets&range_id=<?= $selected_range_id ?>" class="btn-range-action <?= $current_view === 'targets' ? 'active' : '' ?>" style="background: linear-gradient(145deg, #b08723, #c99c2e);">
                            <i class="bi bi-bullseye"></i>
                            <span class="text-center">Annual Targets</span>
                        </a>
                    </div>

                    <!-- 4. Animal Health -->
                    <div class="col">
                        <a href="?view=health&range_id=<?= $selected_range_id ?>" class="btn-range-action <?= $current_view === 'health' ? 'active' : '' ?>" style="background: linear-gradient(145deg, #0288d1, #039be5);">
                            <i class="bi bi-heart-pulse-fill"></i>
                            <span class="text-center">Animal Health</span>
                        </a>
                    </div>

                    <!-- 5. Animal Breeding -->
                    <div class="col">
                        <a href="?view=breeding&range_id=<?= $selected_range_id ?>" class="btn-range-action <?= $current_view === 'breeding' ? 'active' : '' ?>" style="background: linear-gradient(145deg, #e65100, #f57c00);">
                            <i class="bi bi-activity"></i>
                            <span class="text-center">Animal Breeding</span>
                        </a>
                    </div>

                    <!-- 6. Dairy Hub -->
                    <div class="col">
                        <a href="?view=dairy&range_id=<?= $selected_range_id ?>" class="btn-range-action <?= $current_view === 'dairy' ? 'active' : '' ?>" style="background: linear-gradient(145deg, #1565c0, #1976d2);">
                            <i class="bi bi-patch-check-fill"></i>
                            <span class="text-center">Dairy Hub</span>
                        </a>
                    </div>

                    <!-- 7. Revenue / Cash Book -->
                    <div class="col">
                        <a href="?view=revenue&range_id=<?= $selected_range_id ?>" class="btn-range-action <?= $current_view === 'revenue' ? 'active' : '' ?>" style="background: linear-gradient(145deg, #2e7d32, #388e3c);">
                            <i class="bi bi-cash-stack"></i>
                            <span class="text-center">Cash Book</span>
                        </a>
                    </div>

                    <!-- 8. Supervision & Diaries -->
                    <div class="col">
                        <a href="?view=diaries&range_id=<?= $selected_range_id ?>" class="btn-range-action <?= $current_view === 'diaries' ? 'active' : '' ?>" style="background: linear-gradient(145deg, #283593, #3949ab);">
                            <i class="bi bi-journal-check"></i>
                            <span class="text-center">Diaries Log</span>
                        </a>
                    </div>

                </div>
            </div>
        </div>

        <?php if ($is_statistics_category): ?>
        <!-- Annual Returns & Inventory Management Section - ONLY VISIBLE WHEN RANGE STATISTICS IS CLICKED -->
        <div class="card card-modern mb-4 border-start border-4 border-danger shadow-sm">
            <div class="card-header bg-white py-3 px-4 border-0 d-flex flex-wrap justify-content-between align-items-center gap-2">
                <div>
                    <h6 class="mb-0 fw-bold text-dark"><i class="bi bi-clipboard-data-fill text-danger me-2"></i>Annual Returns &amp; Inventory Management Records</h6>
                    <small class="text-muted">Consolidated annual logs, production levels, pasture details, and livestock societies across range offices.</small>
                </div>
                <span class="badge bg-danger-subtle text-danger border border-danger-subtle px-3 py-2 rounded-pill small">
                    <i class="bi bi-bar-chart-fill me-1"></i> Statistical Indicators
                </span>
            </div>
            <div class="card-body px-4 pt-1 pb-3">
                <div class="d-flex flex-wrap gap-2">
                    <a href="?view=animal_pop&range_id=<?= $selected_range_id ?>" class="btn btn-sm sub-stat-pill <?= in_array($current_view, ['statistics', 'animal_pop']) ? 'btn-danger active-pill text-white' : 'btn-outline-danger' ?>">
                        <i class="bi bi-bug-fill me-1"></i> Animal Population
                    </a>
                    <a href="?view=human_pop&range_id=<?= $selected_range_id ?>" class="btn btn-sm sub-stat-pill <?= $current_view === 'human_pop' ? 'btn-primary active-pill text-white' : 'btn-outline-primary' ?>">
                        <i class="bi bi-people-fill me-1"></i> Human Population
                    </a>
                    <a href="?view=prod_levels&range_id=<?= $selected_range_id ?>" class="btn btn-sm sub-stat-pill <?= $current_view === 'prod_levels' ? 'btn-primary active-pill text-white' : 'btn-outline-primary' ?>">
                        <i class="bi bi-graph-up-arrow me-1"></i> Production Levels
                    </a>
                    <a href="?view=pasture_lands&range_id=<?= $selected_range_id ?>" class="btn btn-sm sub-stat-pill <?= $current_view === 'pasture_lands' ? 'btn-success active-pill text-white' : 'btn-outline-success' ?>">
                        <i class="bi bi-tree-fill me-1"></i> Pasture &amp; Fodder Lands
                    </a>
                    <a href="?view=pasture_yields&range_id=<?= $selected_range_id ?>" class="btn btn-sm sub-stat-pill <?= $current_view === 'pasture_yields' ? 'btn-info active-pill text-white' : 'btn-outline-info' ?>">
                        <i class="bi bi-water me-1"></i> Pasture Yields
                    </a>
                    <a href="?view=producers&range_id=<?= $selected_range_id ?>" class="btn btn-sm sub-stat-pill <?= $current_view === 'producers' ? 'btn-warning active-pill text-dark' : 'btn-outline-warning text-dark' ?>">
                        <i class="bi bi-buildings me-1"></i> Producers &amp; Processors
                    </a>
                    <a href="?view=feed_prod&range_id=<?= $selected_range_id ?>" class="btn btn-sm sub-stat-pill <?= $current_view === 'feed_prod' ? 'btn-danger active-pill text-white' : 'btn-outline-danger' ?>">
                        <i class="bi bi-prescription2 me-1"></i> Feed Production
                    </a>
                    <a href="?view=societies&range_id=<?= $selected_range_id ?>" class="btn btn-sm sub-stat-pill <?= $current_view === 'societies' ? 'btn-secondary active-pill text-white' : 'btn-outline-secondary' ?>">
                        <i class="bi bi-heart-fill me-1"></i> Livestock Societies
                    </a>
                    <a href="?view=milk_collecting&range_id=<?= $selected_range_id ?>" class="btn btn-sm sub-stat-pill <?= $current_view === 'milk_collecting' ? 'btn-dark active-pill text-white' : 'btn-outline-dark' ?>">
                        <i class="bi bi-bucket-fill me-1"></i> Milk Collecting Centers
                    </a>
                    <a href="?view=milk_processing&range_id=<?= $selected_range_id ?>" class="btn btn-sm sub-stat-pill <?= $current_view === 'milk_processing' ? 'btn-primary active-pill text-white' : 'btn-outline-primary' ?>">
                        <i class="bi bi-gear-wide-connected me-1"></i> Milk Processing Centers
                    </a>
                    <a href="?view=milk_sales&range_id=<?= $selected_range_id ?>" class="btn btn-sm sub-stat-pill <?= $current_view === 'milk_sales' ? 'btn-success active-pill text-white' : 'btn-outline-success' ?>">
                        <i class="bi bi-shop me-1"></i> Milk Sales Centers
                    </a>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Dynamic Data Section with CSV, PDF, Print, Excel Options -->
        <div class="card card-modern mb-4">
            <div class="card-header bg-white py-3 px-4 d-flex flex-wrap justify-content-between align-items-center gap-2 border-bottom">
                <div class="d-flex align-items-center gap-2">
                    <h5 class="m-0 fw-bold text-dark">
                        <?php if ($current_view === 'officers'): ?>
                            <i class="bi bi-people-fill me-2 text-danger"></i>Range Veterinary Surgeons &amp; Staff
                        <?php elseif (in_array($current_view, ['statistics', 'animal_pop'])): ?>
                            <i class="bi bi-bug-fill me-2 text-dark"></i>Animal Population Statistics
                        <?php elseif ($current_view === 'human_pop'): ?>
                            <i class="bi bi-people-fill me-2 text-primary"></i>Human Population Demographics
                        <?php elseif ($current_view === 'prod_levels'): ?>
                            <i class="bi bi-graph-up-arrow me-2 text-primary"></i>Annual Production Levels
                        <?php elseif ($current_view === 'pasture_lands'): ?>
                            <i class="bi bi-tree-fill me-2 text-success"></i>Pasture &amp; Fodder Lands
                        <?php elseif ($current_view === 'pasture_yields'): ?>
                            <i class="bi bi-water me-2 text-info"></i>Pasture Yields
                        <?php elseif ($current_view === 'producers'): ?>
                            <i class="bi bi-buildings me-2 text-warning"></i>Producers &amp; Processors
                        <?php elseif ($current_view === 'feed_prod'): ?>
                            <i class="bi bi-prescription2 me-2 text-danger"></i>Annual Feed Production Mills
                        <?php elseif ($current_view === 'societies'): ?>
                            <i class="bi bi-heart-fill me-2 text-secondary"></i>Livestock Societies Directory
                        <?php elseif ($current_view === 'milk_collecting'): ?>
                            <i class="bi bi-bucket-fill me-2 text-dark"></i>Milk Collecting Centers
                        <?php elseif ($current_view === 'milk_processing'): ?>
                            <i class="bi bi-gear-wide-connected me-2 text-primary"></i>Milk Processing Centers
                        <?php elseif ($current_view === 'milk_sales'): ?>
                            <i class="bi bi-shop me-2 text-success"></i>Milk Product Sales Centers
                        <?php elseif ($current_view === 'targets'): ?>
                            <i class="bi bi-bullseye me-2 text-warning"></i>Annual Vaccination &amp; Action Targets
                        <?php elseif ($current_view === 'health'): ?>
                            <i class="bi bi-heart-pulse-fill me-2 text-info"></i>Animal Health &amp; Disease Treatment Logs
                        <?php elseif ($current_view === 'breeding'): ?>
                            <i class="bi bi-activity me-2 text-danger"></i>Artificial Insemination &amp; Breeding Performance
                        <?php elseif ($current_view === 'dairy'): ?>
                            <i class="bi bi-patch-check-fill me-2 text-primary"></i>Dairy Hub Centers &amp; Societies
                        <?php elseif ($current_view === 'revenue'): ?>
                            <i class="bi bi-cash-stack me-2 text-success"></i>Revenue &amp; Cash Book Transactions
                        <?php elseif ($current_view === 'diaries'): ?>
                            <i class="bi bi-journal-check me-2 text-primary"></i>Field Supervision Diaries Log
                        <?php endif; ?>
                    </h5>
                    <span class="badge bg-light text-dark border ms-1"><?= htmlspecialchars($active_range_name) ?></span>
                    <span class="badge bg-secondary-subtle text-secondary border"><?= $active_record_count ?> <?= $active_record_count === 1 ? 'Record' : 'Records' ?></span>
                </div>
                <div class="small text-muted">
                    <span class="badge bg-light text-muted border"><i class="bi bi-download me-1"></i>Data Export Toolbar</span>
                </div>
            </div>
            
            <div class="card-body px-4 py-3">
                <div class="table-responsive">
                    
                    <!-- 1. Officers Table -->
                    <?php if ($current_view === 'officers'): ?>
                        <table id="summaryDataTable" class="table table-hover table-striped table-bordered align-middle w-100 small">
                            <thead class="table-light">
                                <tr>
                                    <th>Veterinary Surgeon</th>
                                    <th>Range Office</th>
                                    <th>Email</th>
                                    <th>Phone</th>
                                    <th class="text-center">Staff Count</th>
                                    <th class="text-center">Treatments</th>
                                    <th class="text-center">AI Inseminations</th>
                                    <th class="text-center">Status</th>
                                    <th>Last Login</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($vs_list as $vs): ?>
                                    <tr>
                                        <td>
                                            <div class="fw-bold text-dark"><?= htmlspecialchars($vs['full_name']) ?></div>
                                            <small class="text-muted font-monospace"><?= htmlspecialchars($vs['username']) ?></small>
                                        </td>
                                        <td><span class="fw-semibold text-primary"><i class="bi bi-building me-1"></i><?= htmlspecialchars($vs['range_name'] ?? 'Unassigned') ?></span></td>
                                        <td><?= htmlspecialchars($vs['email'] ?? 'N/A') ?></td>
                                        <td><?= htmlspecialchars($vs['phone'] ?? 'N/A') ?></td>
                                        <td class="text-center font-monospace fw-bold"><?= (int)$vs['total_staff'] ?></td>
                                        <td class="text-center font-monospace text-primary fw-bold"><?= (int)$vs['total_treatments'] ?></td>
                                        <td class="text-center font-monospace text-success fw-bold"><?= (int)$vs['total_ai'] ?></td>
                                        <td class="text-center"><span class="badge bg-<?= !empty($vs['is_active']) ? 'success' : 'danger' ?>"><?= !empty($vs['is_active']) ? 'Active' : 'Inactive' ?></span></td>
                                        <td><?= !empty($vs['last_login']) ? date('d M Y h:i A', strtotime($vs['last_login'])) : 'Never' ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>

                    <!-- 2. Animal Population Table -->
                    <?php elseif (in_array($current_view, ['statistics', 'animal_pop'])): ?>
                        <table id="summaryDataTable" class="table table-hover table-striped table-bordered align-middle w-100 small">
                            <thead class="table-light">
                                <tr>
                                    <th>Year</th>
                                    <th>Range Office</th>
                                    <th>Animal Type</th>
                                    <th class="text-end">Population Count</th>
                                    <th>Last Updated</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($report_records as $rec): ?>
                                    <tr>
                                        <td class="fw-bold"><?= htmlspecialchars($rec['year']) ?></td>
                                        <td><i class="bi bi-building me-1 text-primary"></i><?= htmlspecialchars($rec['range_name']) ?></td>
                                        <td><span class="badge bg-info text-dark"><?= htmlspecialchars($rec['animal_type']) ?></span></td>
                                        <td class="text-end font-monospace fw-bold text-primary"><?= number_format($rec['quantity']) ?></td>
                                        <td><?= date('d M Y', strtotime($rec['updated_at'])) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>

                    <!-- 3. Human Population Table -->
                    <?php elseif ($current_view === 'human_pop'): ?>
                        <table id="summaryDataTable" class="table table-hover table-striped table-bordered align-middle w-100 small">
                            <thead class="table-light">
                                <tr>
                                    <th>Year</th>
                                    <th>Range Office</th>
                                    <th>Ethnicity</th>
                                    <th>Population Type</th>
                                    <th class="text-end">Count</th>
                                    <th>Created At</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($report_records as $rec): ?>
                                    <tr>
                                        <td class="fw-bold"><?= htmlspecialchars($rec['year']) ?></td>
                                        <td><i class="bi bi-building me-1 text-primary"></i><?= htmlspecialchars($rec['range_name']) ?></td>
                                        <td><span class="badge bg-secondary"><?= htmlspecialchars($rec['ethnicity']) ?></span></td>
                                        <td><?= htmlspecialchars($rec['population_type']) ?></td>
                                        <td class="text-end font-monospace fw-bold text-primary"><?= number_format($rec['population_count']) ?></td>
                                        <td><?= date('d M Y', strtotime($rec['created_at'])) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>

                    <!-- 4. Production Levels Table -->
                    <?php elseif ($current_view === 'prod_levels'): ?>
                        <table id="summaryDataTable" class="table table-hover table-striped table-bordered align-middle w-100 small">
                            <thead class="table-light">
                                <tr>
                                    <th>Year</th>
                                    <th>Range Office</th>
                                    <th class="text-end">Cow Milk (L/Day)</th>
                                    <th class="text-end">Buffalo Milk (L/Day)</th>
                                    <th class="text-end">Eggs (No/Day)</th>
                                    <th class="text-end">Beef (Kg/Day)</th>
                                    <th class="text-end">Mutton (Kg/Day)</th>
                                    <th class="text-end">Chicken (Kg/Day)</th>
                                    <th class="text-end">Curd (L/Day)</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($report_records as $rec): ?>
                                    <tr>
                                        <td class="fw-bold"><?= htmlspecialchars($rec['report_year']) ?></td>
                                        <td><i class="bi bi-building me-1 text-primary"></i><?= htmlspecialchars($rec['range_name']) ?></td>
                                        <td class="text-end font-monospace fw-bold text-primary"><?= number_format($rec['cow_milk_lit_day'], 2) ?></td>
                                        <td class="text-end font-monospace"><?= number_format($rec['buffalo_milk_lit_day'], 2) ?></td>
                                        <td class="text-end font-monospace text-success fw-bold"><?= number_format($rec['eggs_production_no_day']) ?></td>
                                        <td class="text-end font-monospace"><?= number_format($rec['beef_kg_day'], 2) ?></td>
                                        <td class="text-end font-monospace"><?= number_format($rec['mutton_kg_day'], 2) ?></td>
                                        <td class="text-end font-monospace"><?= number_format($rec['chicken_kg_day'], 2) ?></td>
                                        <td class="text-end font-monospace"><?= number_format($rec['curd_lit_day'], 2) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>

                    <!-- 5. Pasture & Fodder Lands Table -->
                    <?php elseif ($current_view === 'pasture_lands'): ?>
                        <table id="summaryDataTable" class="table table-hover table-striped table-bordered align-middle w-100 small">
                            <thead class="table-light">
                                <tr>
                                    <th>Year</th>
                                    <th>Range Office</th>
                                    <th class="text-end">Pasture Total (Acres)</th>
                                    <th class="text-center">Pasture &lt;0.25 Ac</th>
                                    <th class="text-center">Pasture 0.5 Ac</th>
                                    <th class="text-center">Pasture 1.0 Ac</th>
                                    <th class="text-end">Fodder Total (Acres)</th>
                                    <th class="text-center">Fodder &lt;0.25 Ac</th>
                                    <th class="text-center">Fodder &gt;1.0 Ac</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($report_records as $rec): ?>
                                    <tr>
                                        <td class="fw-bold"><?= htmlspecialchars($rec['report_year']) ?></td>
                                        <td><i class="bi bi-building me-1 text-primary"></i><?= htmlspecialchars($rec['range_name']) ?></td>
                                        <td class="text-end font-monospace text-success fw-bold"><?= number_format($rec['pasture_total_acres'], 2) ?></td>
                                        <td class="text-center font-monospace"><?= (int)$rec['pasture_fam_quarter_ac'] ?></td>
                                        <td class="text-center font-monospace"><?= (int)$rec['pasture_fam_half_ac'] ?></td>
                                        <td class="text-center font-monospace"><?= (int)$rec['pasture_fam_one_ac'] ?></td>
                                        <td class="text-end font-monospace text-info fw-bold"><?= number_format($rec['fodder_total_acres'], 2) ?></td>
                                        <td class="text-center font-monospace"><?= (int)$rec['fodder_fam_quarter_ac'] ?></td>
                                        <td class="text-center font-monospace"><?= (int)$rec['fodder_fam_gt_one_ac'] ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>

                    <!-- 6. Pasture Yields Table -->
                    <?php elseif ($current_view === 'pasture_yields'): ?>
                        <table id="summaryDataTable" class="table table-hover table-striped table-bordered align-middle w-100 small">
                            <thead class="table-light">
                                <tr>
                                    <th>Year</th>
                                    <th>Range Office</th>
                                    <th class="text-end">CO-3 (Kg/Year)</th>
                                    <th class="text-end">CO-4 (Kg/Year)</th>
                                    <th class="text-end">CO-5 (Kg/Year)</th>
                                    <th class="text-end">Super Nepier (Kg/Yr)</th>
                                    <th class="text-end">Aus Red Nepier (Kg/Yr)</th>
                                    <th class="text-end">Sampoorna (Kg/Yr)</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($report_records as $rec): ?>
                                    <tr>
                                        <td class="fw-bold"><?= htmlspecialchars($rec['report_year']) ?></td>
                                        <td><i class="bi bi-building me-1 text-primary"></i><?= htmlspecialchars($rec['range_name']) ?></td>
                                        <td class="text-end font-monospace"><?= number_format($rec['co3_kg_year'], 2) ?></td>
                                        <td class="text-end font-monospace"><?= number_format($rec['co4_kg_year'], 2) ?></td>
                                        <td class="text-end font-monospace text-success fw-bold"><?= number_format($rec['co5_kg_year'], 2) ?></td>
                                        <td class="text-end font-monospace text-primary fw-bold"><?= number_format($rec['super_nepier_kg_year'], 2) ?></td>
                                        <td class="text-end font-monospace"><?= number_format($rec['australian_red_nepier_kg_year'], 2) ?></td>
                                        <td class="text-end font-monospace"><?= number_format($rec['sampoorna_kg_year'], 2) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>

                    <!-- 7. Producers & Processors Table -->
                    <?php elseif ($current_view === 'producers'): ?>
                        <table id="summaryDataTable" class="table table-hover table-striped table-bordered align-middle w-100 small">
                            <thead class="table-light">
                                <tr>
                                    <th>Year</th>
                                    <th>Range Office</th>
                                    <th class="text-center">Chick Producers</th>
                                    <th class="text-end">Chicks (No/Mo)</th>
                                    <th class="text-center">Feed Producers</th>
                                    <th class="text-end">Feed (MT/Mo)</th>
                                    <th class="text-center">Poultry Processors</th>
                                    <th class="text-end">Organic Fert (MT/Yr)</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($report_records as $rec): ?>
                                    <tr>
                                        <td class="fw-bold"><?= htmlspecialchars($rec['report_year']) ?></td>
                                        <td><i class="bi bi-building me-1 text-primary"></i><?= htmlspecialchars($rec['range_name']) ?></td>
                                        <td class="text-center font-monospace"><?= (int)$rec['chick_producers_count'] ?></td>
                                        <td class="text-end font-monospace text-primary fw-bold"><?= number_format($rec['chicks_produced_month']) ?></td>
                                        <td class="text-center font-monospace"><?= (int)$rec['feed_producers_count'] ?></td>
                                        <td class="text-end font-monospace"><?= number_format($rec['feed_production_mt_month'], 2) ?></td>
                                        <td class="text-center font-monospace"><?= (int)$rec['poultry_processors_count'] ?></td>
                                        <td class="text-end font-monospace text-success fw-bold"><?= number_format($rec['organic_fert_prod_mt_year'], 2) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>

                    <!-- 8. Feed Production Table -->
                    <?php elseif ($current_view === 'feed_prod'): ?>
                        <table id="summaryDataTable" class="table table-hover table-striped table-bordered align-middle w-100 small">
                            <thead class="table-light">
                                <tr>
                                    <th>Year</th>
                                    <th>Range Office</th>
                                    <th>Feed Mill Name</th>
                                    <th>Proprietor Details</th>
                                    <th>Category</th>
                                    <th class="text-end">Monthly Qty (MT)</th>
                                    <th>Raw Materials Source</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($report_records as $rec): ?>
                                    <tr>
                                        <td class="fw-bold"><?= htmlspecialchars($rec['report_year']) ?></td>
                                        <td><i class="bi bi-building me-1 text-primary"></i><?= htmlspecialchars($rec['range_name']) ?></td>
                                        <td class="fw-bold text-dark"><?= htmlspecialchars($rec['feed_mill_name']) ?></td>
                                        <td><small><?= htmlspecialchars($rec['proprietor_details'] ?? '-') ?></small></td>
                                        <td><span class="badge bg-secondary"><?= htmlspecialchars($rec['category_type'] ?? '-') ?></span></td>
                                        <td class="text-end font-monospace text-danger fw-bold"><?= number_format($rec['produced_qty_mt_month'], 2) ?> MT</td>
                                        <td><?= htmlspecialchars($rec['raw_materials_source'] ?? '-') ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>

                    <!-- 9. Livestock Societies Table -->
                    <?php elseif ($current_view === 'societies'): ?>
                        <table id="summaryDataTable" class="table table-hover table-striped table-bordered align-middle w-100 small">
                            <thead class="table-light">
                                <tr>
                                    <th>Year</th>
                                    <th>Range Office</th>
                                    <th>GN Division</th>
                                    <th>Society Name &amp; Address</th>
                                    <th class="text-center">Total Members</th>
                                    <th>Reg No</th>
                                    <th>Financial Records</th>
                                    <th>Contact No</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($report_records as $rec): ?>
                                    <tr>
                                        <td class="fw-bold"><?= htmlspecialchars($rec['report_year']) ?></td>
                                        <td><i class="bi bi-building me-1 text-primary"></i><?= htmlspecialchars($rec['range_name']) ?></td>
                                        <td><?= htmlspecialchars($rec['gn_division']) ?></td>
                                        <td class="fw-bold text-dark"><?= htmlspecialchars($rec['name_and_address']) ?></td>
                                        <td class="text-center font-monospace fw-bold text-primary"><?= (int)$rec['total_members'] ?></td>
                                        <td class="font-monospace"><small><?= htmlspecialchars($rec['registration_no'] ?? '-') ?></small></td>
                                        <td><span class="badge bg-<?= $rec['has_financial_records'] === 'Yes' ? 'success' : 'secondary' ?>"><?= htmlspecialchars($rec['has_financial_records']) ?></span></td>
                                        <td><?= htmlspecialchars($rec['contact_no'] ?? '-') ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>

                    <!-- 10. Milk Collecting Centers Table -->
                    <?php elseif ($current_view === 'milk_collecting'): ?>
                        <table id="summaryDataTable" class="table table-hover table-striped table-bordered align-middle w-100 small">
                            <thead class="table-light">
                                <tr>
                                    <th>Year</th>
                                    <th>Range Office</th>
                                    <th>Center Name</th>
                                    <th>Address</th>
                                    <th>Contact No</th>
                                    <th class="text-end">Collection (Lit/Mo)</th>
                                    <th class="text-end">Chilling Cap (Lit)</th>
                                    <th>Supplied To</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($report_records as $rec): ?>
                                    <tr>
                                        <td class="fw-bold"><?= htmlspecialchars($rec['report_year']) ?></td>
                                        <td><i class="bi bi-building me-1 text-primary"></i><?= htmlspecialchars($rec['range_name']) ?></td>
                                        <td class="fw-bold text-dark"><?= htmlspecialchars($rec['center_name']) ?></td>
                                        <td><small><?= htmlspecialchars($rec['address'] ?? '-') ?></small></td>
                                        <td><?= htmlspecialchars($rec['contact_no'] ?? '-') ?></td>
                                        <td class="text-end font-monospace text-primary fw-bold"><?= number_format($rec['collection_lit_month'], 2) ?></td>
                                        <td class="text-end font-monospace text-success fw-bold"><?= number_format($rec['chilling_capacity_lit'], 2) ?></td>
                                        <td><?= htmlspecialchars($rec['milk_supply_to'] ?? '-') ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>

                    <!-- 11. Milk Processing Centers Table -->
                    <?php elseif ($current_view === 'milk_processing'): ?>
                        <table id="summaryDataTable" class="table table-hover table-striped table-bordered align-middle w-100 small">
                            <thead class="table-light">
                                <tr>
                                    <th>Year</th>
                                    <th>Range Office</th>
                                    <th>Processing Center</th>
                                    <th>Contact No</th>
                                    <th class="text-end">Yoghurt (L/Mo)</th>
                                    <th class="text-end">Curd (L/Mo)</th>
                                    <th class="text-end">Ice Cream (L/Mo)</th>
                                    <th class="text-end">Ghee (L/Mo)</th>
                                    <th class="text-end">Monthly Income (Rs)</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($report_records as $rec): ?>
                                    <tr>
                                        <td class="fw-bold"><?= htmlspecialchars($rec['report_year']) ?></td>
                                        <td><i class="bi bi-building me-1 text-primary"></i><?= htmlspecialchars($rec['range_name']) ?></td>
                                        <td class="fw-bold text-dark"><?= htmlspecialchars($rec['center_name']) ?></td>
                                        <td><?= htmlspecialchars($rec['contact_no'] ?? '-') ?></td>
                                        <td class="text-end font-monospace"><?= number_format($rec['yoghurt_lit_month'], 2) ?></td>
                                        <td class="text-end font-monospace"><?= number_format($rec['curd_lit_month'], 2) ?></td>
                                        <td class="text-end font-monospace"><?= number_format($rec['ice_cream_lit_month'], 2) ?></td>
                                        <td class="text-end font-monospace"><?= number_format($rec['ghee_lit_month'], 2) ?></td>
                                        <td class="text-end font-monospace text-success fw-bold">LKR <?= number_format($rec['income_rs_month'], 2) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>

                    <!-- 12. Milk Sales Centers Table -->
                    <?php elseif ($current_view === 'milk_sales'): ?>
                        <table id="summaryDataTable" class="table table-hover table-striped table-bordered align-middle w-100 small">
                            <thead class="table-light">
                                <tr>
                                    <th>Year</th>
                                    <th>Range Office</th>
                                    <th>Sales Center Name</th>
                                    <th>Address</th>
                                    <th>Contact No</th>
                                    <th class="text-end">Fresh Milk (L/Mo)</th>
                                    <th class="text-end">Yoghurt (L/Mo)</th>
                                    <th class="text-end">Curd (L/Mo)</th>
                                    <th class="text-end">Monthly Income (Rs)</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($report_records as $rec): ?>
                                    <tr>
                                        <td class="fw-bold"><?= htmlspecialchars($rec['report_year']) ?></td>
                                        <td><i class="bi bi-building me-1 text-primary"></i><?= htmlspecialchars($rec['range_name']) ?></td>
                                        <td class="fw-bold text-dark"><?= htmlspecialchars($rec['sales_center_name']) ?></td>
                                        <td><small><?= htmlspecialchars($rec['address'] ?? '-') ?></small></td>
                                        <td><?= htmlspecialchars($rec['contact_no'] ?? '-') ?></td>
                                        <td class="text-end font-monospace text-primary fw-bold"><?= number_format($rec['fresh_milk_lit_month'], 2) ?></td>
                                        <td class="text-end font-monospace"><?= number_format($rec['yoghurt_lit_month'], 2) ?></td>
                                        <td class="text-end font-monospace"><?= number_format($rec['curd_lit_month'], 2) ?></td>
                                        <td class="text-end font-monospace text-success fw-bold">LKR <?= number_format($rec['income_rs_month'], 2) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>

                    <!-- 13. Targets Table -->
                    <?php elseif ($current_view === 'targets'): ?>
                        <table id="summaryDataTable" class="table table-hover table-striped table-bordered align-middle w-100 small">
                            <thead class="table-light">
                                <tr>
                                    <th>Year</th>
                                    <th>Range Office</th>
                                    <th>Animal Type</th>
                                    <th class="text-center">FMD Target</th>
                                    <th class="text-center">BQ Target</th>
                                    <th class="text-center">HS Target</th>
                                    <th class="text-center">LDO Target</th>
                                    <th>Assigned Vaccinator</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($report_records as $rec): ?>
                                    <tr>
                                        <td class="fw-bold"><?= htmlspecialchars($rec['year']) ?></td>
                                        <td><i class="bi bi-building me-1 text-primary"></i><?= htmlspecialchars($rec['range_name']) ?></td>
                                        <td><?= htmlspecialchars($rec['animal_type']) ?></td>
                                        <td class="text-center font-monospace text-danger fw-bold"><?= number_format($rec['target_fmd']) ?></td>
                                        <td class="text-center font-monospace text-primary fw-bold"><?= number_format($rec['target_bq']) ?></td>
                                        <td class="text-center font-monospace text-success fw-bold"><?= number_format($rec['target_hs']) ?></td>
                                        <td class="text-center font-monospace"><?= number_format($rec['allocated_ldo_target']) ?></td>
                                        <td><?= htmlspecialchars($rec['vaccinator_name'] ?? 'General Range Officer') ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>

                    <!-- 14. Animal Health Table -->
                    <?php elseif ($current_view === 'health'): ?>
                        <table id="summaryDataTable" class="table table-hover table-striped table-bordered align-middle w-100 small">
                            <thead class="table-light">
                                <tr>
                                    <th>Date</th>
                                    <th>Range</th>
                                    <th>Farmer Reg No</th>
                                    <th>Animal Type</th>
                                    <th>Disease Name</th>
                                    <th class="text-center">Cases</th>
                                    <th>Vaccine Administered</th>
                                    <th class="text-center">Doses</th>
                                    <th>Treatment Notes</th>
                                    <th class="text-center">Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($report_records as $rec): ?>
                                    <tr>
                                        <td><?= date('d M Y', strtotime($rec['date'])) ?></td>
                                        <td><?= htmlspecialchars($rec['range_name']) ?></td>
                                        <td class="font-monospace fw-semibold"><?= htmlspecialchars($rec['farmer_reg_no']) ?></td>
                                        <td><?= htmlspecialchars($rec['animal_type']) ?></td>
                                        <td class="fw-bold text-danger"><?= htmlspecialchars($rec['disease_name']) ?></td>
                                        <td class="text-center font-monospace fw-bold"><?= (int)$rec['occurrence_count'] ?></td>
                                        <td><?= htmlspecialchars($rec['vaccine_name'] ?? '-') ?></td>
                                        <td class="text-center font-monospace text-primary fw-bold"><?= (int)$rec['doses'] ?></td>
                                        <td><small class="text-muted"><?= htmlspecialchars($rec['treatment_details'] ?? '-') ?></small></td>
                                        <td class="text-center"><span class="badge bg-success"><?= htmlspecialchars($rec['report_status'] ?? 'Submitted') ?></span></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>

                    <!-- 15. Breeding AI Table -->
                    <?php elseif ($current_view === 'breeding'): ?>
                        <table id="summaryDataTable" class="table table-hover table-striped table-bordered align-middle w-100 small">
                            <thead class="table-light">
                                <tr>
                                    <th>AI Date</th>
                                    <th>Range Office</th>
                                    <th>Cow ID / Tag</th>
                                    <th>Semen Code</th>
                                    <th>AI Type / Service</th>
                                    <th>Technician Code</th>
                                    <th>Logged By</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($report_records as $rec): ?>
                                    <tr>
                                        <td><?= date('d M Y', strtotime($rec['ai_date'])) ?></td>
                                        <td><?= htmlspecialchars($rec['range_name']) ?></td>
                                        <td class="font-monospace fw-bold text-primary"><?= htmlspecialchars($rec['cow_id']) ?></td>
                                        <td class="font-monospace text-danger"><?= htmlspecialchars($rec['semen_code']) ?></td>
                                        <td><span class="badge bg-secondary"><?= htmlspecialchars($rec['ai_type'] ?? 'First Service') ?></span></td>
                                        <td class="font-monospace"><?= htmlspecialchars($rec['technician_code'] ?? '-') ?></td>
                                        <td><?= htmlspecialchars($rec['recorded_by'] ?? 'In-Charge Officer') ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>

                    <!-- 16. Dairy Hub Table -->
                    <?php elseif ($current_view === 'dairy'): ?>
                        <table id="summaryDataTable" class="table table-hover table-striped table-bordered align-middle w-100 small">
                            <thead class="table-light">
                                <tr>
                                    <th>Center Name</th>
                                    <th>Range Office</th>
                                    <th>Center Type</th>
                                    <th>Location</th>
                                    <th>Contact Person</th>
                                    <th>Contact No</th>
                                    <th class="text-end">Daily Liters</th>
                                    <th class="text-center">Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($report_records as $rec): ?>
                                    <tr>
                                        <td class="fw-bold text-dark"><?= htmlspecialchars($rec['center_name']) ?></td>
                                        <td><?= htmlspecialchars($rec['range_name']) ?></td>
                                        <td><?= htmlspecialchars($rec['center_type'] ?? 'Collection Center') ?></td>
                                        <td><?= htmlspecialchars($rec['location'] ?? '-') ?></td>
                                        <td><?= htmlspecialchars($rec['contact_person'] ?? '-') ?></td>
                                        <td><?= htmlspecialchars($rec['contact_number'] ?? '-') ?></td>
                                        <td class="text-end font-monospace fw-bold text-primary"><?= number_format($rec['daily_collection_liters'] ?? 0, 2) ?> L</td>
                                        <td class="text-center"><span class="badge bg-success"><?= htmlspecialchars($rec['status'] ?? 'Active') ?></span></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>

                    <!-- 17. Revenue Table -->
                    <?php elseif ($current_view === 'revenue'): ?>
                        <table id="summaryDataTable" class="table table-hover table-striped table-bordered align-middle w-100 small">
                            <thead class="table-light">
                                <tr>
                                    <th>Period</th>
                                    <th>Range Office</th>
                                    <th>Receipt Item</th>
                                    <th class="text-center">Quantity</th>
                                    <th class="text-end">Unit Price</th>
                                    <th class="text-end">Total Amount</th>
                                    <th class="text-end">Deposited</th>
                                    <th>Recorded By</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($report_records as $rec): ?>
                                    <tr>
                                        <td class="fw-bold"><?= htmlspecialchars($rec['report_year']) ?> - M<?= (int)$rec['report_month'] ?></td>
                                        <td><?= htmlspecialchars($rec['range_name']) ?></td>
                                        <td class="fw-semibold"><?= htmlspecialchars($rec['item_name']) ?></td>
                                        <td class="text-center font-monospace"><?= (int)$rec['quantity_sold'] ?></td>
                                        <td class="text-end font-monospace">LKR <?= number_format($rec['unit_price'], 2) ?></td>
                                        <td class="text-end font-monospace text-primary fw-bold">LKR <?= number_format($rec['total_amount'], 2) ?></td>
                                        <td class="text-end font-monospace text-success fw-bold">LKR <?= number_format($rec['amount_deposited'], 2) ?></td>
                                        <td><?= htmlspecialchars($rec['created_by_name'] ?? 'Range Cashier') ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>

                    <!-- 18. Diaries Log Table -->
                    <?php elseif ($current_view === 'diaries'): ?>
                        <table id="summaryDataTable" class="table table-hover table-striped table-bordered align-middle w-100 small">
                            <thead class="table-light">
                                <tr>
                                    <th>Date</th>
                                    <th>Officer Name</th>
                                    <th>Designation</th>
                                    <th>Range Office</th>
                                    <th>Activity Title</th>
                                    <th>Description</th>
                                    <th class="text-center">Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($report_records as $rec): ?>
                                    <tr>
                                        <td><?= date('d M Y', strtotime($rec['task_date'])) ?></td>
                                        <td class="fw-bold text-dark"><?= htmlspecialchars($rec['officer_name']) ?></td>
                                        <td><?= htmlspecialchars($rec['designation'] ?? 'Field Officer') ?></td>
                                        <td><?= htmlspecialchars($rec['range_name'] ?? 'District Pool') ?></td>
                                        <td class="fw-semibold text-primary"><?= htmlspecialchars($rec['activity_title']) ?></td>
                                        <td><small class="text-muted"><?= htmlspecialchars($rec['activity_description'] ?? '-') ?></small></td>
                                        <td class="text-center">
                                            <span class="badge bg-<?= $rec['status'] === 'Approved' ? 'success' : ($rec['status'] === 'Pending' ? 'warning text-dark' : 'secondary') ?>">
                                                <?= htmlspecialchars($rec['status']) ?>
                                            </span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>

                </div>
            </div>
        </div>

    </main>
</div>

<?php require_once '../../../includes/footer.php'; ?>

<!-- DataTables + Buttons JS -->
<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.1/js/dataTables.buttons.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.bootstrap5.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/pdfmake.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/vfs_fonts.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.html5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.print.min.js"></script>

<script>
$(document).ready(function() {
    $('#summaryDataTable').DataTable({
        dom: '<"d-flex flex-wrap justify-content-between align-items-center mb-3"Bf>rt<"d-flex justify-content-between align-items-center mt-3"lip>',
        buttons: [
            {
                extend: 'copyHtml5',
                text: '<i class="bi bi-clipboard me-1"></i> Copy',
                className: 'btn btn-secondary btn-sm'
            },
            {
                extend: 'excelHtml5',
                text: '<i class="bi bi-file-earmark-excel me-1"></i> Excel',
                className: 'btn btn-success btn-sm',
                title: 'DAPH_<?= ucfirst($current_view) ?>_<?= preg_replace('/[^A-Za-z0-9]/', '_', $district_name) ?>'
            },
            {
                extend: 'pdfHtml5',
                text: '<i class="bi bi-file-earmark-pdf me-1"></i> PDF',
                className: 'btn btn-danger btn-sm',
                orientation: 'landscape',
                pageSize: 'A4',
                title: 'DAPH - <?= strtoupper(str_replace('_', ' ', $current_view)) ?> Summary - <?= $district_name ?> District'
            },
            {
                extend: 'print',
                text: '<i class="bi bi-printer me-1"></i> Print',
                className: 'btn btn-dark btn-sm'
            }
        ],
        pageLength: 15,
        order: [],
        language: {
            search: "_INPUT_",
            searchPlaceholder: "Search records...",
            lengthMenu: "Show _MENU_ records",
            emptyTable: "No records found for the selected range or filter criteria.",
            zeroRecords: "No matching records found."
        }
    });
});
</script>
