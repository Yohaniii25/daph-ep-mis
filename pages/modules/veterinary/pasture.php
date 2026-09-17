<?php
session_start();
require_once __DIR__ . '/../../../config/db_connect.php';

/** @var mysqli $mysqli */
global $mysqli;

// Access check: allow logged-in authorized veterinary and district roles
$allowed_roles = [
    'veterinary_surgeon',
    'sms',
    'district_dd',
    'deputy_director_district',
    'administrator',
    'provincial_director',
    'deputy_director_hq_1',
    'deputy_director_hq_2',
    'admin'
];

if (!isset($_SESSION['logged_in']) || !in_array($_SESSION['role'] ?? '', $allowed_roles, true)) {
    header("Location: ../../../index.php");
    exit();
}

$user_id = $_SESSION['user_id'] ?? null;
$requested_range_id = isset($_GET['range_id']) && is_numeric($_GET['range_id']) ? (int)$_GET['range_id'] : null;
$range_id = $requested_range_id ?: ($_SESSION['range_id'] ?? null);
$district_id = $_SESSION['district_id'] ?? null;

$range_name = 'General Range';
$district_name = 'Eastern Province';

// Fetch District and Range Names
if ($range_id) {
    $stmt = $mysqli->prepare("
        SELECT vr.name AS range_name, vr.district_id, d.name AS district_name 
        FROM veterinary_ranges vr 
        LEFT JOIN districts d ON vr.district_id = d.id 
        WHERE vr.id = ?
    ");
    if ($stmt) {
        $stmt->bind_param("i", $range_id);
        $stmt->execute();
        $res = $stmt->get_result();
        if ($r = $res->fetch_assoc()) {
            $range_name = $r['range_name'];
            if (!empty($r['district_name'])) {
                $district_name = $r['district_name'];
            }
            if (!empty($r['district_id'])) {
                $district_id = (int)$r['district_id'];
            }
        }
        $stmt->close();
    }
}


// Auto-migrate: add report_year / report_month columns if missing
$chk_col = $mysqli->query("SHOW COLUMNS FROM pasture_fodder_lands LIKE 'report_year'");
if ($chk_col && $chk_col->num_rows == 0) {
    $mysqli->query("ALTER TABLE pasture_fodder_lands ADD COLUMN report_year INT DEFAULT 2024 AFTER vs_range");
}
$chk_col_m = $mysqli->query("SHOW COLUMNS FROM pasture_fodder_lands LIKE 'report_month'");
if ($chk_col_m && $chk_col_m->num_rows == 0) {
    $mysqli->query("ALTER TABLE pasture_fodder_lands ADD COLUMN report_month TINYINT(4) NULL AFTER report_year");
}

// Active Tab & Filters
$current_tab = isset($_GET['tab']) && $_GET['tab'] === 'yields' ? 'yields' : 'lands';
$selected_year = isset($_GET['year']) ? ($_GET['year'] === 'all' || $_GET['year'] === '' ? 'all' : intval($_GET['year'])) : 'all';
$selected_month = isset($_GET['month']) ? ($_GET['month'] === 'all' || $_GET['month'] === '' ? 'all' : intval($_GET['month'])) : 'all';

$months_map = [
    1 => 'January', 2 => 'February', 3 => 'March', 4 => 'April',
    5 => 'May', 6 => 'June', 7 => 'July', 8 => 'August',
    9 => 'September', 10 => 'October', 11 => 'November', 12 => 'December'
];

// Helper to preserve URL params for redirects
function get_pasture_redirect_url($tab, $selected_year, $selected_month, $status, $msg, $range_id = null) {
    $params = [
        'tab' => $tab,
        'year' => $selected_year,
        'month' => $selected_month,
        'status' => $status,
        'msg' => $msg
    ];
    if ($range_id) {
        $params['range_id'] = $range_id;
    }
    return 'pasture.php?' . http_build_query($params);
}

// ==============================================================================
// POST CRUD HANDLERS
// ==============================================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];

    // --------------------------------------------------------------------------
    // 1. ADD PASTURE & FODDER LAND RECORD
    // --------------------------------------------------------------------------
    if ($action === 'add_land') {
        $vs_range = trim($_POST['vs_range'] ?? $range_name);
        $report_year = intval($_POST['report_year'] ?? date('Y'));
        $report_month = (!empty($_POST['report_month']) && $_POST['report_month'] !== 'all') ? intval($_POST['report_month']) : null;

        $pasture_families_quarter_ac = intval($_POST['pasture_families_quarter_ac'] ?? 0);
        $pasture_families_half_ac = intval($_POST['pasture_families_half_ac'] ?? 0);
        $pasture_families_one_ac = intval($_POST['pasture_families_one_ac'] ?? 0);
        $pasture_families_gt_one_ac = intval($_POST['pasture_families_gt_one_ac'] ?? 0);
        $pasture_total_acre = floatval($_POST['pasture_total_acre'] ?? 0);
        $pasture_total_families = intval($_POST['pasture_total_families'] ?? 0);

        $fodder_families_quarter_ac = intval($_POST['fodder_families_quarter_ac'] ?? 0);
        $fodder_families_half_ac = intval($_POST['fodder_families_half_ac'] ?? 0);
        $fodder_families_one_ac = intval($_POST['fodder_families_one_ac'] ?? 0);
        $fodder_families_gt_one_ac = intval($_POST['fodder_families_gt_one_ac'] ?? 0);
        $fodder_total_acre = floatval($_POST['fodder_total_acre'] ?? 0);
        $fodder_total_families = intval($_POST['fodder_total_families'] ?? 0);

        $insert_query = "
            INSERT INTO pasture_fodder_lands 
            (vs_range, report_year, report_month,
             pasture_families_quarter_ac, pasture_families_half_ac, pasture_families_one_ac, pasture_families_gt_one_ac, pasture_total_acre, pasture_total_families,
             fodder_families_quarter_ac, fodder_families_half_ac, fodder_families_one_ac, fodder_families_gt_one_ac, fodder_total_acre, fodder_total_families)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ";
        $stmt = $mysqli->prepare($insert_query);
        if ($stmt) {
            $stmt->bind_param(
                "siiiiidiiiiidi",
                $vs_range, $report_year, $report_month,
                $pasture_families_quarter_ac, $pasture_families_half_ac, $pasture_families_one_ac, $pasture_families_gt_one_ac, $pasture_total_acre, $pasture_total_families,
                $fodder_families_quarter_ac, $fodder_families_half_ac, $fodder_families_one_ac, $fodder_families_gt_one_ac, $fodder_total_acre, $fodder_total_families
            );
            if ($stmt->execute()) {
                header("Location: " . get_pasture_redirect_url('lands', 'all', 'all', 'success', 'Pasture & Fodder land record added successfully.', $range_id));
            } else {
                header("Location: " . get_pasture_redirect_url('lands', $selected_year, $selected_month, 'error', 'Database insert failed: ' . $stmt->error, $range_id));
            }
            $stmt->close();
        } else {
            header("Location: " . get_pasture_redirect_url('lands', $selected_year, $selected_month, 'error', 'Query preparation failed: ' . $mysqli->error, $range_id));
        }
        exit();
    }

    // --------------------------------------------------------------------------
    // 2. EDIT PASTURE & FODDER LAND RECORD
    // --------------------------------------------------------------------------
    elseif ($action === 'edit_land') {
        $id = intval($_POST['id'] ?? 0);
        $vs_range = trim($_POST['vs_range'] ?? $range_name);
        $report_year = intval($_POST['report_year'] ?? date('Y'));
        $report_month = (!empty($_POST['report_month']) && $_POST['report_month'] !== 'all') ? intval($_POST['report_month']) : null;

        $pasture_families_quarter_ac = intval($_POST['pasture_families_quarter_ac'] ?? 0);
        $pasture_families_half_ac = intval($_POST['pasture_families_half_ac'] ?? 0);
        $pasture_families_one_ac = intval($_POST['pasture_families_one_ac'] ?? 0);
        $pasture_families_gt_one_ac = intval($_POST['pasture_families_gt_one_ac'] ?? 0);
        $pasture_total_acre = floatval($_POST['pasture_total_acre'] ?? 0);
        $pasture_total_families = intval($_POST['pasture_total_families'] ?? 0);

        $fodder_families_quarter_ac = intval($_POST['fodder_families_quarter_ac'] ?? 0);
        $fodder_families_half_ac = intval($_POST['fodder_families_half_ac'] ?? 0);
        $fodder_families_one_ac = intval($_POST['fodder_families_one_ac'] ?? 0);
        $fodder_families_gt_one_ac = intval($_POST['fodder_families_gt_one_ac'] ?? 0);
        $fodder_total_acre = floatval($_POST['fodder_total_acre'] ?? 0);
        $fodder_total_families = intval($_POST['fodder_total_families'] ?? 0);

        $update_query = "
            UPDATE pasture_fodder_lands SET 
                vs_range = ?,
                report_year = ?,
                report_month = ?,
                pasture_families_quarter_ac = ?, pasture_families_half_ac = ?, pasture_families_one_ac = ?, pasture_families_gt_one_ac = ?, pasture_total_acre = ?, pasture_total_families = ?,
                fodder_families_quarter_ac = ?, fodder_families_half_ac = ?, fodder_families_one_ac = ?, fodder_families_gt_one_ac = ?, fodder_total_acre = ?, fodder_total_families = ?
            WHERE id = ?
        ";
        $stmt = $mysqli->prepare($update_query);
        if ($stmt) {
            $stmt->bind_param(
                "siiiiidiiiiidii",
                $vs_range, $report_year, $report_month,
                $pasture_families_quarter_ac, $pasture_families_half_ac, $pasture_families_one_ac, $pasture_families_gt_one_ac, $pasture_total_acre, $pasture_total_families,
                $fodder_families_quarter_ac, $fodder_families_half_ac, $fodder_families_one_ac, $fodder_families_gt_one_ac, $fodder_total_acre, $fodder_total_families,
                $id
            );
            if ($stmt->execute()) {
                header("Location: " . get_pasture_redirect_url('lands', 'all', 'all', 'success', 'Pasture & Fodder land record updated successfully.', $range_id));
            } else {
                header("Location: " . get_pasture_redirect_url('lands', $selected_year, $selected_month, 'error', 'Database update failed: ' . $stmt->error, $range_id));
            }
            $stmt->close();
        } else {
            header("Location: " . get_pasture_redirect_url('lands', $selected_year, $selected_month, 'error', 'Query preparation failed: ' . $mysqli->error, $range_id));
        }
        exit();
    }

    // --------------------------------------------------------------------------
    // 3. ADD PASTURE YIELD RECORD
    // --------------------------------------------------------------------------
    elseif ($action === 'add_yield') {
        $year = intval($_POST['report_year'] ?? date('Y'));
        $month = (!empty($_POST['report_month']) && $_POST['report_month'] !== 'all') ? intval($_POST['report_month']) : null;
        $co3 = floatval($_POST['co3_kg_year'] ?? 0);
        $co4 = floatval($_POST['co4_kg_year'] ?? 0);
        $co5 = floatval($_POST['co5_kg_year'] ?? 0);
        $aus_red = floatval($_POST['australian_red_nepier_kg_year'] ?? 0);
        $sup_nep = floatval($_POST['super_nepier_kg_year'] ?? 0);
        $sampoorna = floatval($_POST['sampoorna_kg_year'] ?? 0);
        $other = floatval($_POST['other_varieties_kg_year'] ?? 0);

        // Validate duplicate year and month for this range
        $check_stmt = $mysqli->prepare("
            SELECT id FROM annual_pasture_yields 
            WHERE range_id = ? AND report_year = ? AND ((report_month = ?) OR (report_month IS NULL AND ? IS NULL))
        ");
        $check_stmt->bind_param("iiii", $range_id, $year, $month, $month);
        $check_stmt->execute();
        if ($check_stmt->get_result()->num_rows > 0) {
            header("Location: " . get_pasture_redirect_url('yields', $selected_year, $selected_month, 'error', 'A yield record for this year and month already exists.', $range_id));
            exit();
        }
        $check_stmt->close();

        $insert_query = "
            INSERT INTO annual_pasture_yields 
            (district_id, range_id, report_year, report_month, co3_kg_year, co4_kg_year, co5_kg_year, 
             australian_red_nepier_kg_year, super_nepier_kg_year, sampoorna_kg_year, other_varieties_kg_year, created_by)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ";
        $stmt = $mysqli->prepare($insert_query);
        if ($stmt) {
            $stmt->bind_param("iiiidddddddi", $district_id, $range_id, $year, $month, $co3, $co4, $co5, 
                              $aus_red, $sup_nep, $sampoorna, $other, $user_id);
            if ($stmt->execute()) {
                header("Location: " . get_pasture_redirect_url('yields', 'all', 'all', 'success', 'Pasture yields added successfully.', $range_id));
            } else {
                header("Location: " . get_pasture_redirect_url('yields', $selected_year, $selected_month, 'error', 'Failed to write yield record: ' . $stmt->error, $range_id));
            }
            $stmt->close();
        } else {
            header("Location: " . get_pasture_redirect_url('yields', $selected_year, $selected_month, 'error', 'Query preparation failed: ' . $mysqli->error, $range_id));
        }
        exit();
    }

    // --------------------------------------------------------------------------
    // 4. EDIT PASTURE YIELD RECORD
    // --------------------------------------------------------------------------
    elseif ($action === 'edit_yield') {
        $id = intval($_POST['id'] ?? 0);
        $year = intval($_POST['report_year'] ?? date('Y'));
        $month = (!empty($_POST['report_month']) && $_POST['report_month'] !== 'all') ? intval($_POST['report_month']) : null;
        $co3 = floatval($_POST['co3_kg_year'] ?? 0);
        $co4 = floatval($_POST['co4_kg_year'] ?? 0);
        $co5 = floatval($_POST['co5_kg_year'] ?? 0);
        $aus_red = floatval($_POST['australian_red_nepier_kg_year'] ?? 0);
        $sup_nep = floatval($_POST['super_nepier_kg_year'] ?? 0);
        $sampoorna = floatval($_POST['sampoorna_kg_year'] ?? 0);
        $other = floatval($_POST['other_varieties_kg_year'] ?? 0);

        // Prevent duplicate year/month collision with another record
        $check_stmt = $mysqli->prepare("
            SELECT id FROM annual_pasture_yields 
            WHERE range_id = ? AND report_year = ? AND ((report_month = ?) OR (report_month IS NULL AND ? IS NULL)) AND id != ?
        ");
        $check_stmt->bind_param("iiiii", $range_id, $year, $month, $month, $id);
        $check_stmt->execute();
        if ($check_stmt->get_result()->num_rows > 0) {
            header("Location: " . get_pasture_redirect_url('yields', $selected_year, $selected_month, 'error', 'A record for this year and month already exists.', $range_id));
            exit();
        }
        $check_stmt->close();

        $update_query = "
            UPDATE annual_pasture_yields 
            SET report_year = ?, report_month = ?, co3_kg_year = ?, co4_kg_year = ?, co5_kg_year = ?,
                australian_red_nepier_kg_year = ?, super_nepier_kg_year = ?, sampoorna_kg_year = ?, other_varieties_kg_year = ?
            WHERE id = ? AND range_id = ?
        ";
        $stmt = $mysqli->prepare($update_query);
        if ($stmt) {
            $stmt->bind_param("iidddddddii", $year, $month, $co3, $co4, $co5, $aus_red, $sup_nep, $sampoorna, $other, $id, $range_id);
            if ($stmt->execute()) {
                header("Location: " . get_pasture_redirect_url('yields', 'all', 'all', 'success', 'Pasture yields updated successfully.', $range_id));
            } else {
                header("Location: " . get_pasture_redirect_url('yields', $selected_year, $selected_month, 'error', 'Failed to update record: ' . $stmt->error, $range_id));
            }
            $stmt->close();
        } else {
            header("Location: " . get_pasture_redirect_url('yields', $selected_year, $selected_month, 'error', 'Query preparation failed: ' . $mysqli->error, $range_id));
        }
        exit();
    }
}

// ==============================================================================
// GET DELETE ACTIONS
// ==============================================================================
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $del_id = intval($_GET['id']);
    $del_type = $_GET['type'] ?? ($current_tab === 'yields' ? 'yield' : 'land');

    if ($del_type === 'land') {
        $stmt = $mysqli->prepare("DELETE FROM pasture_fodder_lands WHERE id = ?");
        if ($stmt) {
            $stmt->bind_param("i", $del_id);
            if ($stmt->execute()) {
                header("Location: " . get_pasture_redirect_url('lands', $selected_year, $selected_month, 'success', 'Pasture & Fodder land record deleted successfully.', $range_id));
            } else {
                header("Location: " . get_pasture_redirect_url('lands', $selected_year, $selected_month, 'error', 'Failed to delete land record: ' . $stmt->error, $range_id));
            }
            $stmt->close();
        }
        exit();
    } elseif ($del_type === 'yield') {
        $stmt = $mysqli->prepare("DELETE FROM annual_pasture_yields WHERE id = ? AND range_id = ?");
        if ($stmt) {
            $stmt->bind_param("ii", $del_id, $range_id);
            if ($stmt->execute()) {
                header("Location: " . get_pasture_redirect_url('yields', $selected_year, $selected_month, 'success', 'Pasture yield record deleted successfully.', $range_id));
            } else {
                header("Location: " . get_pasture_redirect_url('yields', $selected_year, $selected_month, 'error', 'Failed to delete yield record: ' . $stmt->error, $range_id));
            }
            $stmt->close();
        }
        exit();
    }
}

// ==============================================================================
// FETCH TAB 1: PASTURE & FODDER LANDS RECORDS
// ==============================================================================
$land_records = [];
$where_land = ["1=1"];
$params_land = [];
$types_land = "";

if ($selected_year !== 'all') {
    $where_land[] = "report_year = ?";
    $params_land[] = $selected_year;
    $types_land .= "i";
}
if ($selected_month !== 'all') {
    $where_land[] = "report_month = ?";
    $params_land[] = $selected_month;
    $types_land .= "i";
}

$where_land_sql = implode(" AND ", $where_land);
$stmt_land = $mysqli->prepare("SELECT * FROM pasture_fodder_lands WHERE $where_land_sql ORDER BY report_year DESC, report_month DESC, id DESC");
if (!empty($params_land)) {
    $stmt_land->bind_param($types_land, ...$params_land);
}
$stmt_land->execute();
$res_land = $stmt_land->get_result();
if ($res_land) {
    while ($r = $res_land->fetch_assoc()) {
        $land_records[] = $r;
    }
}
$stmt_land->close();

// Land Summary Totals
$land_summary = [
    'total_records' => count($land_records),
    'pasture_acres_sum' => 0,
    'pasture_families_sum' => 0,
    'fodder_acres_sum' => 0,
    'fodder_families_sum' => 0
];
foreach ($land_records as $lr) {
    $land_summary['pasture_acres_sum'] += floatval($lr['pasture_total_acre']);
    $land_summary['pasture_families_sum'] += intval($lr['pasture_total_families']);
    $land_summary['fodder_acres_sum'] += floatval($lr['fodder_total_acre']);
    $land_summary['fodder_families_sum'] += intval($lr['fodder_total_families']);
}

// Fetch list of VS Ranges for quick dropdown selection
$vs_ranges_list = [];
$range_res = $mysqli->query("SELECT name FROM veterinary_ranges ORDER BY name ASC");
if ($range_res) {
    while ($r_row = $range_res->fetch_assoc()) {
        $vs_ranges_list[] = $r_row['name'];
    }
}

// ==============================================================================
// FETCH TAB 2: PASTURE YIELDS RECORDS
// ==============================================================================
$yield_records = [];
$where_yield = ["range_id = ?"];
$params_yield = [$range_id];
$types_yield = "i";

if ($selected_year !== 'all') {
    $where_yield[] = "report_year = ?";
    $params_yield[] = $selected_year;
    $types_yield .= "i";
}
if ($selected_month !== 'all') {
    $where_yield[] = "report_month = ?";
    $params_yield[] = $selected_month;
    $types_yield .= "i";
}

$where_yield_sql = implode(" AND ", $where_yield);
$stmt_yield = $mysqli->prepare("SELECT * FROM annual_pasture_yields WHERE $where_yield_sql ORDER BY report_year DESC, report_month DESC, id DESC");
if (!empty($params_yield)) {
    $stmt_yield->bind_param($types_yield, ...$params_yield);
}
$stmt_yield->execute();
$res_yield = $stmt_yield->get_result();
if ($res_yield) {
    while ($yr = $res_yield->fetch_assoc()) {
        $yield_records[] = $yr;
    }
}
$stmt_yield->close();

// Yield Summary Totals
$yield_summary = [
    'total_yield_kg' => 0,
    'highest_variety' => 'None',
    'highest_qty' => 0
];
$variety_totals = [
    'CO3' => 0, 'CO4' => 0, 'CO5' => 0,
    'Australian Red Napier' => 0,
    'Super Napier' => 0,
    'Sampoorna' => 0,
    'Other Varieties' => 0
];

foreach ($yield_records as $yr) {
    $variety_totals['CO3'] += floatval($yr['co3_kg_year']);
    $variety_totals['CO4'] += floatval($yr['co4_kg_year']);
    $variety_totals['CO5'] += floatval($yr['co5_kg_year']);
    $variety_totals['Australian Red Napier'] += floatval($yr['australian_red_nepier_kg_year']);
    $variety_totals['Super Napier'] += floatval($yr['super_nepier_kg_year']);
    $variety_totals['Sampoorna'] += floatval($yr['sampoorna_kg_year']);
    $variety_totals['Other Varieties'] += floatval($yr['other_varieties_kg_year']);
}

$yield_summary['total_yield_kg'] = array_sum($variety_totals);
if ($yield_summary['total_yield_kg'] > 0) {
    arsort($variety_totals);
    $yield_summary['highest_variety'] = key($variety_totals);
    $yield_summary['highest_qty'] = current($variety_totals);
}

require_once __DIR__ . '/../../../includes/header.php';
?>

<link rel="stylesheet" href="../../../assets/css/bootstrap-icons.min.css">
<link rel="stylesheet" href="../../../assets/css/dataTables.bootstrap5.min.css">
<link rel="stylesheet" href="../../../assets/css/buttons.bootstrap5.min.css">
<link rel="stylesheet" href="../../../assets/css/sweetalert2.min.css">
<link rel="stylesheet" href="../../../assets/css/veterinary.css">

<style>
    .card-header-gradient {
        background: linear-gradient(135deg, #370709 0%, #680d12 100%);
        color: #ffffff;
    }
    .pasture-tab-nav .nav-link {
        font-weight: 600;
        color: #495057;
        padding: 0.75rem 1.25rem;
        border-radius: 8px 8px 0 0;
        transition: all 0.2s ease-in-out;
    }
    .pasture-tab-nav .nav-link.active {
        color: #370709 !important;
        background-color: #ffffff;
        border-bottom: 3px solid #370709 !important;
    }
    .pasture-tab-nav .nav-link:hover:not(.active) {
        background-color: #f1f5f9;
        color: #0f172a;
    }
    .badge-auto-calc {
        font-size: 0.7rem;
        padding: 0.2em 0.5em;
        border-radius: 4px;
        background-color: #e0e7ff;
        color: #3730a3;
    }
    .border-pasture {
        border-left: 4px solid #3b82f6 !important;
    }
    .border-fodder {
        border-left: 4px solid #10b981 !important;
    }
    .table-nested-header th {
        vertical-align: middle !important;
        text-align: center !important;
        font-size: 0.82rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.3px;
        background-color: #ffffff !important;
        color: #000000 !important;
    }
</style>

        <!-- PAGE BANNER / TITLE CARD -->
        <div class="card shadow-sm border-0 mb-4 overflow-hidden">
            <div class="card-body p-4 card-header-gradient d-flex justify-content-between align-items-center flex-wrap gap-3">
                <div>
                    <span class="badge bg-warning text-dark fw-bold mb-2">Annexure : 07 & Annual Returns</span>
                    <h3 class="h4 fw-bold mb-1 text-white">Pasture & Fodder Management</h3>
                    <p class="mb-0 text-white-50 small">
                        Consolidated tracking for Pasture & Fodder lands, cultivation, and variety yields in <strong class="text-white"><?= htmlspecialchars($range_name) ?></strong> (<?= htmlspecialchars($district_name) ?> District).
                    </p>
                </div>
                <div class="d-flex align-items-center gap-3">
                    <form method="GET" class="d-flex align-items-center gap-2 flex-wrap" id="filterForm">
                        <input type="hidden" name="tab" value="<?= htmlspecialchars($current_tab) ?>">
                        <?php if ($requested_range_id): ?>
                            <input type="hidden" name="range_id" value="<?= $requested_range_id ?>">
                        <?php endif; ?>
                        
                        <label class="small fw-bold text-white mb-0">Year:</label>
                        <select name="year" class="form-select form-select-sm" onchange="this.form.submit()" style="width: 105px;">
                            <option value="all" <?= ($selected_year === 'all') ? 'selected' : '' ?>>All Years</option>
                            <?php
                            $curr_year = intval(date('Y'));
                            for ($y = $curr_year - 5; $y <= $curr_year + 5; $y++) {
                                $sel = ($selected_year !== 'all' && $y === intval($selected_year)) ? 'selected' : '';
                                echo "<option value=\"$y\" $sel>$y</option>";
                            }
                            ?>
                        </select>
                        <label class="small fw-bold text-white mb-0 ms-1">Month:</label>
                        <select name="month" class="form-select form-select-sm" onchange="this.form.submit()" style="width: 120px;">
                            <option value="all" <?= ($selected_month === 'all') ? 'selected' : '' ?>>All Months</option>
                            <?php foreach ($months_map as $m_num => $m_name): ?>
                                <option value="<?= $m_num ?>" <?= ($selected_month !== 'all' && intval($selected_month) === $m_num) ? 'selected' : '' ?>><?= $m_name ?></option>
                            <?php endforeach; ?>
                        </select>
                    </form>
                    <a href="range_statistics.php<?= $requested_range_id ? '?range_id=' . $requested_range_id : '' ?>" class="btn btn-light text-dark fw-bold btn-sm shadow-sm">
                        <i class="bi bi-arrow-left-circle me-1"></i> Range Statistics
                    </a>
                </div>
            </div>
        </div>

        <!-- NAVIGATION TABS -->
        <ul class="nav nav-tabs pasture-tab-nav mb-4 border-bottom" id="pastureTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link <?= $current_tab === 'lands' ? 'active' : '' ?>" id="lands-tab" data-bs-toggle="tab" data-bs-target="#lands-tab-pane" type="button" role="tab" aria-controls="lands-tab-pane" aria-selected="<?= $current_tab === 'lands' ? 'true' : 'false' ?>">
                    <i class="bi bi-tree-fill me-2 text-success"></i>Pasture & Fodder Lands
                    <span class="badge bg-secondary ms-1"><?= count($land_records) ?></span>
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link <?= $current_tab === 'yields' ? 'active' : '' ?>" id="yields-tab" data-bs-toggle="tab" data-bs-target="#yields-tab-pane" type="button" role="tab" aria-controls="yields-tab-pane" aria-selected="<?= $current_tab === 'yields' ? 'true' : 'false' ?>">
                    <i class="bi bi-water me-2 text-info"></i>Pasture Yields
                    <span class="badge bg-secondary ms-1"><?= count($yield_records) ?></span>
                </button>
            </li>
        </ul>

        <div class="tab-content" id="pastureTabsContent">

            <!-- ================================================================= -->
            <!-- TAB 1: PASTURE & FODDER LANDS (ANNEXURE 07) -->
            <!-- ================================================================= -->
            <div class="tab-pane fade <?= $current_tab === 'lands' ? 'show active' : '' ?>" id="lands-tab-pane" role="tabpanel" aria-labelledby="lands-tab-pane" tabindex="0">
                
                <!-- KPI OVERVIEW CARDS: LANDS -->
                <div class="row g-3 mb-4">
                    <div class="col-12 col-sm-6 col-xl-3">
                        <div class="card shadow-sm border-0 border-start border-primary border-4 h-100">
                            <div class="card-body py-3">
                                <span class="text-muted small text-uppercase fw-bold">Active Filter</span>
                                <h4 class="mb-0 fw-bold text-primary mt-1">
                                    <?= ($selected_month !== 'all' && isset($months_map[$selected_month]) ? substr($months_map[$selected_month], 0, 3) . ' ' : '') . (($selected_year === 'all') ? 'All Years' : htmlspecialchars($selected_year)) ?>
                                </h4>
                                <small class="text-muted"><?= $land_summary['total_records'] ?> Land Records Recorded</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-12 col-sm-6 col-xl-3">
                        <div class="card shadow-sm border-0 border-start border-primary border-4 h-100">
                            <div class="card-body py-3">
                                <span class="text-muted small text-uppercase fw-bold">Pasture Land</span>
                                <h4 class="mb-0 fw-bold text-primary mt-1"><?= number_format($land_summary['pasture_acres_sum'], 2) ?> Ac</h4>
                                <small class="text-muted"><?= number_format($land_summary['pasture_families_sum']) ?> Beneficiary Families</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-12 col-sm-6 col-xl-3">
                        <div class="card shadow-sm border-0 border-start border-success border-4 h-100">
                            <div class="card-body py-3">
                                <span class="text-muted small text-uppercase fw-bold">Fodder Land</span>
                                <h4 class="mb-0 fw-bold text-success mt-1"><?= number_format($land_summary['fodder_acres_sum'], 2) ?> Ac</h4>
                                <small class="text-muted"><?= number_format($land_summary['fodder_families_sum']) ?> Beneficiary Families</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-12 col-sm-6 col-xl-3">
                        <div class="card shadow-sm border-0 border-start border-info border-4 h-100">
                            <div class="card-body py-3">
                                <span class="text-muted small text-uppercase fw-bold">Total Cultivation</span>
                                <h4 class="mb-0 fw-bold text-dark mt-1"><?= number_format($land_summary['pasture_acres_sum'] + $land_summary['fodder_acres_sum'], 2) ?> Ac</h4>
                                <small class="text-muted"><?= number_format($land_summary['pasture_families_sum'] + $land_summary['fodder_families_sum']) ?> Total Farm Families</small>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ADD LAND ACCORDION FORM -->
                <div class="card shadow-sm border-0 mb-4">
                    <div class="card-header bg-white py-3 border-0 d-flex justify-content-between align-items-center">
                        <h6 class="mb-0 fw-bold text-dark"><i class="bi bi-plus-circle me-2 text-success"></i>Record New Pasture & Fodder Land Data</h6>
                        <button class="btn btn-sm btn-outline-success" type="button" data-bs-toggle="collapse" data-bs-target="#collapseAddLand" aria-expanded="false" aria-controls="collapseAddLand">
                            <i class="bi bi-chevron-expand me-1"></i> Toggle Form
                        </button>
                    </div>
                    <div class="collapse show" id="collapseAddLand">
                        <div class="card-body pt-0 px-4 pb-4">
                            <form method="POST" action="pasture.php?tab=lands<?= $requested_range_id ? '&range_id=' . $requested_range_id : '' ?>" id="addLandForm">
                                <input type="hidden" name="action" value="add_land">

                                <div class="row g-3 mb-3">
                                    <div class="col-12 col-md-4">
                                        <label for="vs_range" class="form-label small fw-bold">Veterinary Range <span class="text-danger">*</span></label>
                                        <input type="text" name="vs_range" id="vs_range" class="form-control form-control-sm" list="ranges_datalist" value="<?= htmlspecialchars($range_name) ?>" required>
                                        <datalist id="ranges_datalist">
                                            <?php foreach ($vs_ranges_list as $vr_name): ?>
                                                <option value="<?= htmlspecialchars($vr_name) ?>">
                                            <?php endforeach; ?>
                                        </datalist>
                                    </div>
                                    <div class="col-12 col-md-4">
                                        <label for="report_year" class="form-label small fw-bold">Report Year <span class="text-danger">*</span></label>
                                        <input type="number" name="report_year" id="report_year" class="form-control form-control-sm" value="<?= date('Y') ?>" required>
                                    </div>
                                    <div class="col-12 col-md-4">
                                        <label for="report_month" class="form-label small fw-bold">Report Month</label>
                                        <select name="report_month" id="report_month" class="form-select form-select-sm">
                                            <option value="">Annual Aggregate (Whole Year)</option>
                                            <?php foreach ($months_map as $m_num => $m_name): ?>
                                                <option value="<?= $m_num ?>" <?= ($selected_month !== 'all' && intval($selected_month) === $m_num) ? 'selected' : '' ?>><?= $m_name ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>

                                <div class="row g-4 mb-3">
                                    <!-- Pasture Land Details -->
                                    <div class="col-12 col-lg-6">
                                        <div class="card border-0 shadow-sm border-pasture">
                                            <div class="card-header bg-light py-2">
                                                <h6 class="mb-0 fw-bold text-primary small"><i class="bi bi-tree-fill me-1"></i>Pasture Land Families</h6>
                                            </div>
                                            <div class="card-body p-3">
                                                <div class="row g-2 mb-3">
                                                    <div class="col-6 col-sm-3">
                                                        <label class="form-label small fw-bold">1/4 Ac</label>
                                                        <input type="number" name="pasture_families_quarter_ac" id="p_quarter" class="form-control form-control-sm pasture-calc-input" min="0" value="0">
                                                    </div>
                                                    <div class="col-6 col-sm-3">
                                                        <label class="form-label small fw-bold">1/2 Ac</label>
                                                        <input type="number" name="pasture_families_half_ac" id="p_half" class="form-control form-control-sm pasture-calc-input" min="0" value="0">
                                                    </div>
                                                    <div class="col-6 col-sm-3">
                                                        <label class="form-label small fw-bold">1 Ac</label>
                                                        <input type="number" name="pasture_families_one_ac" id="p_one" class="form-control form-control-sm pasture-calc-input" min="0" value="0">
                                                    </div>
                                                    <div class="col-6 col-sm-3">
                                                        <label class="form-label small fw-bold">&gt; 1Ac</label>
                                                        <input type="number" name="pasture_families_gt_one_ac" id="p_gt_one" class="form-control form-control-sm pasture-calc-input" min="0" value="0">
                                                    </div>
                                                </div>
                                                <hr class="my-2">
                                                <div class="row g-2">
                                                    <div class="col-6">
                                                        <label class="form-label small fw-bold text-primary">Total Acre</label>
                                                        <div class="input-group input-group-sm">
                                                            <input type="number" step="0.01" name="pasture_total_acre" id="p_total_acre" class="form-control" min="0" value="0.00">
                                                            <span class="input-group-text">Ac</span>
                                                        </div>
                                                    </div>
                                                    <div class="col-6">
                                                        <label class="form-label small fw-bold text-primary d-flex justify-content-between align-items-center">
                                                            <span>Total Families</span>
                                                            <span class="badge-auto-calc" id="p_auto_badge">Auto</span>
                                                        </label>
                                                        <input type="number" name="pasture_total_families" id="p_total_families" class="form-control form-control-sm border-primary" min="0" value="0">
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Fodder Land Details -->
                                    <div class="col-12 col-lg-6">
                                        <div class="card border-0 shadow-sm border-fodder">
                                            <div class="card-header bg-light py-2">
                                                <h6 class="mb-0 fw-bold text-success small"><i class="bi bi-flower2 me-1"></i>Fodder Land Families</h6>
                                            </div>
                                            <div class="card-body p-3">
                                                <div class="row g-2 mb-3">
                                                    <div class="col-6 col-sm-3">
                                                        <label class="form-label small fw-bold">1/4 Ac</label>
                                                        <input type="number" name="fodder_families_quarter_ac" id="f_quarter" class="form-control form-control-sm fodder-calc-input" min="0" value="0">
                                                    </div>
                                                    <div class="col-6 col-sm-3">
                                                        <label class="form-label small fw-bold">1/2 Ac</label>
                                                        <input type="number" name="fodder_families_half_ac" id="f_half" class="form-control form-control-sm fodder-calc-input" min="0" value="0">
                                                    </div>
                                                    <div class="col-6 col-sm-3">
                                                        <label class="form-label small fw-bold">1 Ac</label>
                                                        <input type="number" name="fodder_families_one_ac" id="f_one" class="form-control form-control-sm fodder-calc-input" min="0" value="0">
                                                    </div>
                                                    <div class="col-6 col-sm-3">
                                                        <label class="form-label small fw-bold">&gt; 1Ac</label>
                                                        <input type="number" name="fodder_families_gt_one_ac" id="f_gt_one" class="form-control form-control-sm fodder-calc-input" min="0" value="0">
                                                    </div>
                                                </div>
                                                <hr class="my-2">
                                                <div class="row g-2">
                                                    <div class="col-6">
                                                        <label class="form-label small fw-bold text-success">Total Acre</label>
                                                        <div class="input-group input-group-sm">
                                                            <input type="number" step="0.01" name="fodder_total_acre" id="f_total_acre" class="form-control" min="0" value="0.00">
                                                            <span class="input-group-text">Ac</span>
                                                        </div>
                                                    </div>
                                                    <div class="col-6">
                                                        <label class="form-label small fw-bold text-success d-flex justify-content-between align-items-center">
                                                            <span>Total Families</span>
                                                            <span class="badge-auto-calc" id="f_auto_badge">Auto</span>
                                                        </label>
                                                        <input type="number" name="fodder_total_families" id="f_total_families" class="form-control form-control-sm border-success" min="0" value="0">
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="d-flex justify-content-end gap-2">
                                    <button type="reset" class="btn btn-light border px-4" id="btnResetLandForm">
                                        <i class="bi bi-x-circle me-1"></i> Reset
                                    </button>
                                    <button type="submit" class="btn btn-primary px-4 fw-bold" style="background-color: #370709; border-color: #370709;">
                                        <i class="bi bi-plus-circle me-1"></i> Save Land Data
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- TABLE: PASTURE & FODDER LANDS -->
                <div class="card shadow-sm border-0 mb-4">
                    <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center border-bottom flex-wrap gap-2">
                        <h5 class="card-title mb-0 fw-bold text-dark">
                            <i class="bi bi-table me-2 text-success"></i>Pasture &amp; Fodder Lands Records Table
                        </h5>
                        <span class="badge bg-secondary"><?= count($land_records) ?> Entries</span>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-bordered table-hover align-middle mb-0 text-center bg-white text-dark" id="pastureFodderTable" style="width: 100%; min-width: 1200px;">
                                <thead class="table-light table-nested-header bg-white text-dark">
                                    <tr>
                                        <th rowspan="3" class="align-middle bg-white text-dark" style="width: 50px;">S.No</th>
                                        <th rowspan="3" class="align-middle bg-white text-dark" style="min-width: 140px;">VS Range</th>
                                        <th rowspan="3" class="align-middle bg-white text-dark" style="width: 90px;">Period</th>
                                        <th colspan="6" class="bg-white text-primary py-2">Pasture Land</th>
                                        <th colspan="6" class="bg-white text-success py-2">Fodder Land</th>
                                        <th rowspan="3" class="align-middle bg-white text-dark" style="width: 110px;">Actions</th>
                                    </tr>
                                    <tr>
                                        <th colspan="4" class="bg-white text-dark py-1">No. of Families Having Pasture Land</th>
                                        <th rowspan="2" class="align-middle bg-white text-dark py-1" style="width: 80px;">Total Acre</th>
                                        <th rowspan="2" class="align-middle bg-white text-dark py-1" style="width: 80px;">Total Families</th>
                                        <th colspan="4" class="bg-white text-dark py-1">No. of Families Having Fodder Land</th>
                                        <th rowspan="2" class="align-middle bg-white text-dark py-1" style="width: 80px;">Total Acre</th>
                                        <th rowspan="2" class="align-middle bg-white text-dark py-1" style="width: 80px;">Total Families</th>
                                    </tr>
                                    <tr>
                                        <th class="bg-white text-dark py-1" style="width: 60px;">1/4 Ac</th>
                                        <th class="bg-white text-dark py-1" style="width: 60px;">1/2 Ac</th>
                                        <th class="bg-white text-dark py-1" style="width: 60px;">1 Ac</th>
                                        <th class="bg-white text-dark py-1" style="width: 60px;">&gt; 1Ac</th>
                                        <th class="bg-white text-dark py-1" style="width: 60px;">1/4 Ac</th>
                                        <th class="bg-white text-dark py-1" style="width: 60px;">1/2 Ac</th>
                                        <th class="bg-white text-dark py-1" style="width: 60px;">1 Ac</th>
                                        <th class="bg-white text-dark py-1" style="width: 60px;">&gt; 1Ac</th>
                                    </tr>
                                </thead>
                                <tbody class="small bg-white text-dark">
                                    <?php if (empty($land_records)): ?>
                                        <tr>
                                            <td colspan="16" class="text-center py-5 text-muted bg-white">
                                                <i class="bi bi-inbox fs-2 d-block mb-2 text-secondary"></i>
                                                No pasture &amp; fodder land records found for this period.
                                            </td>
                                        </tr>
                                    <?php else: ?>
                                        <?php $sno = 1; foreach ($land_records as $row): ?>
                                            <tr data-row='<?= json_encode($row, JSON_HEX_APOS | JSON_HEX_QUOT) ?>' class="bg-white text-dark">
                                                <td class="fw-bold text-dark bg-white"><?= $sno++ ?></td>
                                                <td class="fw-bold text-start text-dark bg-white"><?= htmlspecialchars($row['vs_range']) ?></td>
                                                <td class="bg-white">
                                                    <span class="badge bg-secondary"><?= htmlspecialchars($row['report_year'] ?? '2024') ?></span>
                                                    <?php if (!empty($row['report_month']) && isset($months_map[$row['report_month']])): ?>
                                                        <span class="badge bg-light text-dark border ms-1"><?= substr($months_map[$row['report_month']], 0, 3) ?></span>
                                                    <?php else: ?>
                                                        <span class="badge bg-light text-muted border ms-1">Annual</span>
                                                    <?php endif; ?>
                                                </td>

                                                <!-- Pasture Land Fields -->
                                                <td class="font-monospace text-dark bg-white"><?= number_format($row['pasture_families_quarter_ac']) ?></td>
                                                <td class="font-monospace text-dark bg-white"><?= number_format($row['pasture_families_half_ac']) ?></td>
                                                <td class="font-monospace text-dark bg-white"><?= number_format($row['pasture_families_one_ac']) ?></td>
                                                <td class="font-monospace text-dark bg-white"><?= number_format($row['pasture_families_gt_one_ac']) ?></td>
                                                <td class="font-monospace fw-bold text-dark bg-white"><?= number_format($row['pasture_total_acre'], 2) ?></td>
                                                <td class="font-monospace fw-bold text-dark bg-white"><?= number_format($row['pasture_total_families']) ?></td>

                                                <!-- Fodder Land Fields -->
                                                <td class="font-monospace text-dark bg-white"><?= number_format($row['fodder_families_quarter_ac']) ?></td>
                                                <td class="font-monospace text-dark bg-white"><?= number_format($row['fodder_families_half_ac']) ?></td>
                                                <td class="font-monospace text-dark bg-white"><?= number_format($row['fodder_families_one_ac']) ?></td>
                                                <td class="font-monospace text-dark bg-white"><?= number_format($row['fodder_families_gt_one_ac']) ?></td>
                                                <td class="font-monospace fw-bold text-dark bg-white"><?= number_format($row['fodder_total_acre'], 2) ?></td>
                                                <td class="font-monospace fw-bold text-dark bg-white"><?= number_format($row['fodder_total_families']) ?></td>

                                                <!-- Actions -->
                                                <td class="text-center bg-white">
                                                    <div class="btn-group btn-group-sm" role="group">
                                                        <button type="button" class="btn btn-outline-info text-dark btn-view-land" title="View Details">
                                                            <i class="bi bi-eye-fill text-info"></i>
                                                        </button>
                                                        <button type="button" class="btn btn-outline-primary btn-edit-land" title="Edit Record">
                                                            <i class="bi bi-pencil-fill text-primary"></i>
                                                        </button>
                                                        <a href="pasture.php?tab=lands&action=delete&type=land&id=<?= $row['id'] ?><?= $requested_range_id ? '&range_id=' . $requested_range_id : '' ?>" class="btn btn-outline-danger btn-delete-land" title="Delete Record">
                                                            <i class="bi bi-trash-fill text-danger"></i>
                                                        </a>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

            </div>

            <!-- ================================================================= -->
            <!-- TAB 2: PASTURE YIELDS -->
            <!-- ================================================================= -->
            <div class="tab-pane fade <?= $current_tab === 'yields' ? 'show active' : '' ?>" id="yields-tab-pane" role="tabpanel" aria-labelledby="yields-tab-pane" tabindex="0">
                
                <!-- KPI OVERVIEW CARDS: YIELDS -->
                <div class="row g-3 mb-4">
                    <div class="col-6 col-lg-3">
                        <div class="card shadow-sm border-0 border-start border-primary border-4 text-center h-100">
                            <div class="card-body py-3">
                                <span class="text-muted small text-uppercase fw-bold">Active Filter</span>
                                <h4 class="mb-0 fw-bold text-primary mt-1">
                                    <?= ($selected_month !== 'all' && isset($months_map[$selected_month]) ? substr($months_map[$selected_month], 0, 3) . ' ' : '') . (($selected_year === 'all') ? 'All Years' : htmlspecialchars($selected_year)) ?>
                                </h4>
                                <small class="text-muted"><?= count($yield_records) ?> Yield Logs</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-6 col-lg-3">
                        <div class="card shadow-sm border-0 border-start border-success border-4 text-center h-100">
                            <div class="card-body py-3">
                                <span class="text-muted small text-uppercase fw-bold">Total Yield</span>
                                <h4 class="mb-0 fw-bold text-success mt-1"><?= number_format($yield_summary['total_yield_kg'], 2) ?> Kg</h4>
                                <small class="text-muted">Combined Across Varieties</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-6 col-lg-3">
                        <div class="card shadow-sm border-0 border-start border-info border-4 text-center h-100">
                            <div class="card-body py-3">
                                <span class="text-muted small text-uppercase fw-bold">Top Variety</span>
                                <h4 class="mb-0 fw-bold text-info mt-1"><?= htmlspecialchars($yield_summary['highest_variety']) ?></h4>
                                <small class="text-muted">Highest Yielding</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-6 col-lg-3">
                        <div class="card shadow-sm border-0 border-start border-warning border-4 text-center h-100">
                            <div class="card-body py-3">
                                <span class="text-muted small text-uppercase fw-bold">Max Variety Qty</span>
                                <h4 class="mb-0 fw-bold text-warning mt-1"><?= number_format($yield_summary['highest_qty'], 2) ?> Kg</h4>
                                <small class="text-muted">Top Single Variety Output</small>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- QUICK ACTIONS FOR YIELDS -->
                <div class="row g-4 mb-4">
                    <div class="col-12">
                        <div class="card shadow-sm border-0">
                            <div class="card-header bg-white py-3 border-0 d-flex justify-content-between align-items-center">
                                <h6 class="mb-0 fw-bold text-dark"><i class="bi bi-grid-3x3-gap-fill me-2 text-info"></i>Yield Actions</h6>
                                <button class="btn btn-sm btn-primary fw-bold px-3 shadow-sm" style="background-color: #370709; border-color: #370709;" data-bs-toggle="modal" data-bs-target="#addYieldModal">
                                    <i class="bi bi-plus-circle me-1"></i> Add Pasture Yield Record
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- TABLE: PASTURE YIELDS -->
                <div class="card shadow-sm border-0 mb-4">
                    <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center border-bottom flex-wrap gap-2">
                        <h5 class="card-title mb-0 fw-bold text-dark">
                            <i class="bi bi-table me-2 text-info"></i>Pasture Yields Log - <?= htmlspecialchars($range_name) ?>
                        </h5>
                        <span class="badge bg-secondary"><?= count($yield_records) ?> Entries</span>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0" id="yieldTable" style="width: 100%; min-width: 1200px;">
                                <thead class="table-light text-secondary small text-uppercase">
                                    <tr>
                                        <th class="text-center">Period</th>
                                        <th class="text-end">CO3 (Kg/year)</th>
                                        <th class="text-end">CO4 (Kg/year)</th>
                                        <th class="text-end">CO5 (Kg/year)</th>
                                        <th class="text-end">Australian Red Napier</th>
                                        <th class="text-end">Super Napier</th>
                                        <th class="text-end">Sampoorna</th>
                                        <th class="text-end">Other Varieties</th>
                                        <th class="text-center" style="width: 10%">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="small">
                                    <?php if (empty($yield_records)): ?>
                                        <tr>
                                            <td colspan="9" class="text-center py-4 text-muted">
                                                <i class="bi bi-inbox fs-3 d-block mb-2 text-secondary"></i>
                                                No pasture yield records located for this range and period.
                                            </td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($yield_records as $row): ?>
                                            <tr 
                                                data-id="<?= $row['id'] ?>"
                                                data-year="<?= htmlspecialchars($row['report_year']) ?>"
                                                data-month="<?= htmlspecialchars($row['report_month'] ?? '') ?>"
                                                data-co3="<?= htmlspecialchars($row['co3_kg_year']) ?>"
                                                data-co4="<?= htmlspecialchars($row['co4_kg_year']) ?>"
                                                data-co5="<?= htmlspecialchars($row['co5_kg_year']) ?>"
                                                data-aus_red="<?= htmlspecialchars($row['australian_red_nepier_kg_year']) ?>"
                                                data-sup_nep="<?= htmlspecialchars($row['super_nepier_kg_year']) ?>"
                                                data-sampoorna="<?= htmlspecialchars($row['sampoorna_kg_year']) ?>"
                                                data-other="<?= htmlspecialchars($row['other_varieties_kg_year']) ?>">
                                                <td class="text-center fw-bold">
                                                    <?= htmlspecialchars($row['report_year']) ?>
                                                    <?php if (!empty($row['report_month']) && isset($months_map[$row['report_month']])): ?>
                                                        <span class="badge bg-light text-dark border ms-1"><?= substr($months_map[$row['report_month']], 0, 3) ?></span>
                                                    <?php else: ?>
                                                        <span class="badge bg-light text-muted border ms-1">Annual</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td class="text-end font-monospace"><?= number_format($row['co3_kg_year'], 2) ?></td>
                                                <td class="text-end font-monospace"><?= number_format($row['co4_kg_year'], 2) ?></td>
                                                <td class="text-end font-monospace"><?= number_format($row['co5_kg_year'], 2) ?></td>
                                                <td class="text-end font-monospace"><?= number_format($row['australian_red_nepier_kg_year'], 2) ?></td>
                                                <td class="text-end font-monospace"><?= number_format($row['super_nepier_kg_year'], 2) ?></td>
                                                <td class="text-end font-monospace"><?= number_format($row['sampoorna_kg_year'], 2) ?></td>
                                                <td class="text-end font-monospace"><?= number_format($row['other_varieties_kg_year'], 2) ?></td>
                                                <td class="text-center">
                                                    <div class="btn-group btn-group-sm">
                                                        <button type="button" class="btn btn-outline-primary btn-edit-yield" title="Edit Yield">
                                                            <i class="bi bi-pencil-square"></i>
                                                        </button>
                                                        <a href="pasture.php?tab=yields&action=delete&type=yield&id=<?= $row['id'] ?><?= $requested_range_id ? '&range_id=' . $requested_range_id : '' ?>" class="btn btn-outline-danger btn-delete-yield" title="Delete Yield">
                                                            <i class="bi bi-trash"></i>
                                                        </a>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

            </div>
        </div>

    </main>
</div>

<!-- =========================================================================== -->
<!-- MODALS -->
<!-- =========================================================================== -->

<!-- MODAL 1: VIEW LAND DETAILS -->
<div class="modal fade" id="viewLandModal" tabindex="-1" aria-labelledby="viewLandModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header card-header-gradient">
                <h5 class="modal-title fw-bold text-white" id="viewLandModalLabel">
                    <i class="bi bi-eye-fill me-2"></i>Land Data Details - <span id="v_vs_range"></span> (<span id="v_report_year"></span>)
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <div class="row g-4">
                    <!-- Pasture Column -->
                    <div class="col-md-6 border-end">
                        <div class="p-3 bg-light rounded border border-pasture">
                            <h6 class="fw-bold text-primary mb-3"><i class="bi bi-tree-fill me-2"></i>Pasture Land Details</h6>
                            <ul class="list-group list-group-flush small">
                                <li class="list-group-item bg-transparent d-flex justify-content-between">
                                    <span>1/4 Acre Families:</span>
                                    <strong id="v_p_quarter">0</strong>
                                </li>
                                <li class="list-group-item bg-transparent d-flex justify-content-between">
                                    <span>1/2 Acre Families:</span>
                                    <strong id="v_p_half">0</strong>
                                </li>
                                <li class="list-group-item bg-transparent d-flex justify-content-between">
                                    <span>1 Acre Families:</span>
                                    <strong id="v_p_one">0</strong>
                                </li>
                                <li class="list-group-item bg-transparent d-flex justify-content-between">
                                    <span>&gt; 1 Acre Families:</span>
                                    <strong id="v_p_gt_one">0</strong>
                                </li>
                                <li class="list-group-item bg-transparent d-flex justify-content-between text-primary">
                                    <span>Total Extent (Acres):</span>
                                    <strong id="v_p_total_acre">0.00 Ac</strong>
                                </li>
                                <li class="list-group-item bg-transparent d-flex justify-content-between text-primary">
                                    <span>Total Farm Families:</span>
                                    <strong id="v_p_total_families">0</strong>
                                </li>
                            </ul>
                        </div>
                    </div>
                    <!-- Fodder Column -->
                    <div class="col-md-6">
                        <div class="p-3 bg-light rounded border border-fodder">
                            <h6 class="fw-bold text-success mb-3"><i class="bi bi-flower2 me-2"></i>Fodder Land Details</h6>
                            <ul class="list-group list-group-flush small">
                                <li class="list-group-item bg-transparent d-flex justify-content-between">
                                    <span>1/4 Acre Families:</span>
                                    <strong id="v_f_quarter">0</strong>
                                </li>
                                <li class="list-group-item bg-transparent d-flex justify-content-between">
                                    <span>1/2 Acre Families:</span>
                                    <strong id="v_f_half">0</strong>
                                </li>
                                <li class="list-group-item bg-transparent d-flex justify-content-between">
                                    <span>1 Acre Families:</span>
                                    <strong id="v_f_one">0</strong>
                                </li>
                                <li class="list-group-item bg-transparent d-flex justify-content-between">
                                    <span>&gt; 1 Acre Families:</span>
                                    <strong id="v_f_gt_one">0</strong>
                                </li>
                                <li class="list-group-item bg-transparent d-flex justify-content-between text-success">
                                    <span>Total Extent (Acres):</span>
                                    <strong id="v_f_total_acre">0.00 Ac</strong>
                                </li>
                                <li class="list-group-item bg-transparent d-flex justify-content-between text-success">
                                    <span>Total Farm Families:</span>
                                    <strong id="v_f_total_families">0</strong>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer bg-light">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- MODAL 2: EDIT LAND RECORD -->
<div class="modal fade" id="editLandModal" tabindex="-1" aria-labelledby="editLandModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <form method="POST" action="pasture.php?tab=lands<?= $requested_range_id ? '&range_id=' . $requested_range_id : '' ?>" id="editLandForm">
                <input type="hidden" name="action" value="edit_land">
                <input type="hidden" name="id" id="e_id">

                <div class="modal-header card-header-gradient">
                    <h5 class="modal-title fw-bold text-white" id="editLandModalLabel">
                        <i class="bi bi-pencil-fill me-2"></i>Edit Pasture &amp; Fodder Land Record
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body p-4">
                    <div class="row g-3 mb-3">
                        <div class="col-md-4">
                            <label for="e_vs_range" class="form-label small fw-bold">VS Range <span class="text-danger">*</span></label>
                            <input type="text" name="vs_range" id="e_vs_range" class="form-control form-control-sm" required>
                        </div>
                        <div class="col-md-4">
                            <label for="e_report_year" class="form-label small fw-bold">Report Year <span class="text-danger">*</span></label>
                            <input type="number" name="report_year" id="e_report_year" class="form-control form-control-sm" required>
                        </div>
                        <div class="col-md-4">
                            <label for="e_report_month" class="form-label small fw-bold">Report Month</label>
                            <select name="report_month" id="e_report_month" class="form-select form-select-sm">
                                <option value="">Annual Aggregate</option>
                                <?php foreach ($months_map as $m_num => $m_name): ?>
                                    <option value="<?= $m_num ?>"><?= $m_name ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="row g-4 mb-3">
                        <!-- Pasture Land -->
                        <div class="col-md-6">
                            <div class="card border-0 shadow-sm border-pasture">
                                <div class="card-header bg-light py-2">
                                    <h6 class="mb-0 fw-bold small text-primary"><i class="bi bi-tree-fill me-1"></i>Pasture Land</h6>
                                </div>
                                <div class="card-body p-3">
                                    <div class="row g-2 mb-2">
                                        <div class="col-6">
                                            <label class="form-label small fw-bold">1/4 Ac</label>
                                            <input type="number" name="pasture_families_quarter_ac" id="e_p_quarter" class="form-control form-control-sm edit-pasture-calc" min="0">
                                        </div>
                                        <div class="col-6">
                                            <label class="form-label small fw-bold">1/2 Ac</label>
                                            <input type="number" name="pasture_families_half_ac" id="e_p_half" class="form-control form-control-sm edit-pasture-calc" min="0">
                                        </div>
                                        <div class="col-6">
                                            <label class="form-label small fw-bold">1 Ac</label>
                                            <input type="number" name="pasture_families_one_ac" id="e_p_one" class="form-control form-control-sm edit-pasture-calc" min="0">
                                        </div>
                                        <div class="col-6">
                                            <label class="form-label small fw-bold">&gt; 1Ac</label>
                                            <input type="number" name="pasture_families_gt_one_ac" id="e_p_gt_one" class="form-control form-control-sm edit-pasture-calc" min="0">
                                        </div>
                                    </div>
                                    <hr class="my-2">
                                    <div class="row g-2">
                                        <div class="col-6">
                                            <label class="form-label small fw-bold text-primary">Total Acre</label>
                                            <input type="number" step="0.01" name="pasture_total_acre" id="e_p_total_acre" class="form-control form-control-sm" min="0">
                                        </div>
                                        <div class="col-6">
                                            <label class="form-label small fw-bold text-primary">Total Families</label>
                                            <input type="number" name="pasture_total_families" id="e_p_total_families" class="form-control form-control-sm" min="0">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Fodder Land -->
                        <div class="col-md-6">
                            <div class="card border-0 shadow-sm border-fodder">
                                <div class="card-header bg-light py-2">
                                    <h6 class="mb-0 fw-bold small text-success"><i class="bi bi-flower2 me-1"></i>Fodder Land</h6>
                                </div>
                                <div class="card-body p-3">
                                    <div class="row g-2 mb-2">
                                        <div class="col-6">
                                            <label class="form-label small fw-bold">1/4 Ac</label>
                                            <input type="number" name="fodder_families_quarter_ac" id="e_f_quarter" class="form-control form-control-sm edit-fodder-calc" min="0">
                                        </div>
                                        <div class="col-6">
                                            <label class="form-label small fw-bold">1/2 Ac</label>
                                            <input type="number" name="fodder_families_half_ac" id="e_f_half" class="form-control form-control-sm edit-fodder-calc" min="0">
                                        </div>
                                        <div class="col-6">
                                            <label class="form-label small fw-bold">1 Ac</label>
                                            <input type="number" name="fodder_families_one_ac" id="e_f_one" class="form-control form-control-sm edit-fodder-calc" min="0">
                                        </div>
                                        <div class="col-6">
                                            <label class="form-label small fw-bold">&gt; 1Ac</label>
                                            <input type="number" name="fodder_families_gt_one_ac" id="e_f_gt_one" class="form-control form-control-sm edit-fodder-calc" min="0">
                                        </div>
                                    </div>
                                    <hr class="my-2">
                                    <div class="row g-2">
                                        <div class="col-6">
                                            <label class="form-label small fw-bold text-success">Total Acre</label>
                                            <input type="number" step="0.01" name="fodder_total_acre" id="e_f_total_acre" class="form-control form-control-sm" min="0">
                                        </div>
                                        <div class="col-6">
                                            <label class="form-label small fw-bold text-success">Total Families</label>
                                            <input type="number" name="fodder_total_families" id="e_f_total_families" class="form-control form-control-sm" min="0">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary fw-bold" style="background-color: #370709; border-color: #370709;">Update Record</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- MODAL 3: ADD YIELD RECORD -->
<div class="modal fade" id="addYieldModal" tabindex="-1" aria-labelledby="addYieldModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <form method="POST" action="pasture.php?tab=yields<?= $requested_range_id ? '&range_id=' . $requested_range_id : '' ?>" id="addYieldForm">
            <input type="hidden" name="action" value="add_yield">
            <div class="modal-content border-0 shadow">
                <div class="modal-header card-header-gradient py-3">
                    <h5 class="modal-title fw-bold text-white fs-6" id="addYieldModalLabel">
                        <i class="bi bi-plus-circle me-2"></i>Add Pasture Yield Record
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <!-- Period Row -->
                    <div class="row g-3 mb-3 p-3 bg-light rounded border">
                        <div class="col-12 col-md-6">
                            <label class="form-label fw-bold small text-secondary">Report Year <span class="text-danger">*</span></label>
                            <input type="number" name="report_year" class="form-control form-control-sm" value="<?= date('Y') ?>" required>
                        </div>
                        <div class="col-12 col-md-6">
                            <label class="form-label fw-bold small text-secondary">Report Month</label>
                            <select name="report_month" class="form-select form-select-sm">
                                <option value="">-- Annual Aggregate (Whole Year) --</option>
                                <?php foreach ($months_map as $m_num => $m_name): ?>
                                    <option value="<?= $m_num ?>" <?= ($selected_month !== 'all' && intval($selected_month) === $m_num) ? 'selected' : '' ?>><?= $m_name ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <!-- Variety Yields Grid -->
                    <div class="border rounded p-3 bg-white">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <span class="small fw-bold text-uppercase text-secondary">
                                <i class="bi bi-water me-1 text-info"></i>Variety Yield Output Metrics (Kg / Year)
                            </span>
                            <span class="badge bg-light text-dark border px-2 py-1 small">
                                Total: <strong id="addYieldTotalPreview" class="text-success">0.00</strong> Kg
                            </span>
                        </div>
                        <div class="row g-3">
                            <div class="col-12 col-sm-6 col-md-4">
                                <label class="form-label small fw-semibold text-dark">CO3</label>
                                <div class="input-group input-group-sm">
                                    <input type="number" step="0.01" name="co3_kg_year" id="add_co3" class="form-control yield-calc-input" min="0" value="0.00">
                                    <span class="input-group-text">Kg</span>
                                </div>
                            </div>
                            <div class="col-12 col-sm-6 col-md-4">
                                <label class="form-label small fw-semibold text-dark">CO4</label>
                                <div class="input-group input-group-sm">
                                    <input type="number" step="0.01" name="co4_kg_year" id="add_co4" class="form-control yield-calc-input" min="0" value="0.00">
                                    <span class="input-group-text">Kg</span>
                                </div>
                            </div>
                            <div class="col-12 col-sm-6 col-md-4">
                                <label class="form-label small fw-semibold text-dark">CO5</label>
                                <div class="input-group input-group-sm">
                                    <input type="number" step="0.01" name="co5_kg_year" id="add_co5" class="form-control yield-calc-input" min="0" value="0.00">
                                    <span class="input-group-text">Kg</span>
                                </div>
                            </div>
                            <div class="col-12 col-sm-6 col-md-4">
                                <label class="form-label small fw-semibold text-dark">Australian Red Napier</label>
                                <div class="input-group input-group-sm">
                                    <input type="number" step="0.01" name="australian_red_nepier_kg_year" id="add_aus_red" class="form-control yield-calc-input" min="0" value="0.00">
                                    <span class="input-group-text">Kg</span>
                                </div>
                            </div>
                            <div class="col-12 col-sm-6 col-md-4">
                                <label class="form-label small fw-semibold text-dark">Super Napier</label>
                                <div class="input-group input-group-sm">
                                    <input type="number" step="0.01" name="super_nepier_kg_year" id="add_sup_nep" class="form-control yield-calc-input" min="0" value="0.00">
                                    <span class="input-group-text">Kg</span>
                                </div>
                            </div>
                            <div class="col-12 col-sm-6 col-md-4">
                                <label class="form-label small fw-semibold text-dark">Sampoorna</label>
                                <div class="input-group input-group-sm">
                                    <input type="number" step="0.01" name="sampoorna_kg_year" id="add_sampoorna" class="form-control yield-calc-input" min="0" value="0.00">
                                    <span class="input-group-text">Kg</span>
                                </div>
                            </div>
                            <div class="col-12 col-sm-6 col-md-4">
                                <label class="form-label small fw-semibold text-dark">Other Varieties</label>
                                <div class="input-group input-group-sm">
                                    <input type="number" step="0.01" name="other_varieties_kg_year" id="add_other" class="form-control yield-calc-input" min="0" value="0.00">
                                    <span class="input-group-text">Kg</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light py-2">
                    <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-sm btn-primary fw-bold" style="background-color: #370709; border-color: #370709;">
                        <i class="bi bi-check-circle me-1"></i> Save Yield Record
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- MODAL 4: EDIT YIELD RECORD -->
<div class="modal fade" id="editYieldModal" tabindex="-1" aria-labelledby="editYieldModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <form method="POST" action="pasture.php?tab=yields<?= $requested_range_id ? '&range_id=' . $requested_range_id : '' ?>" id="editYieldForm">
            <input type="hidden" name="action" value="edit_yield">
            <input type="hidden" name="id" id="edit_yield_id">
            <div class="modal-content border-0 shadow">
                <div class="modal-header card-header-gradient py-3">
                    <h5 class="modal-title fw-bold text-white fs-6" id="editYieldModalLabel">
                        <i class="bi bi-pencil-square me-2"></i>Edit Pasture Yield Record
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <!-- Period Row -->
                    <div class="row g-3 mb-3 p-3 bg-light rounded border">
                        <div class="col-12 col-md-6">
                            <label class="form-label fw-bold small text-secondary">Report Year <span class="text-danger">*</span></label>
                            <input type="number" name="report_year" id="edit_yield_year" class="form-control form-control-sm" required>
                        </div>
                        <div class="col-12 col-md-6">
                            <label class="form-label fw-bold small text-secondary">Report Month</label>
                            <select name="report_month" id="edit_yield_month" class="form-select form-select-sm">
                                <option value="">-- Annual Aggregate (Whole Year) --</option>
                                <?php foreach ($months_map as $m_num => $m_name): ?>
                                    <option value="<?= $m_num ?>"><?= $m_name ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <!-- Variety Yields Grid -->
                    <div class="border rounded p-3 bg-white">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <span class="small fw-bold text-uppercase text-secondary">
                                <i class="bi bi-water me-1 text-info"></i>Variety Yield Output Metrics (Kg / Year)
                            </span>
                            <span class="badge bg-light text-dark border px-2 py-1 small">
                                Total: <strong id="editYieldTotalPreview" class="text-success">0.00</strong> Kg
                            </span>
                        </div>
                        <div class="row g-3">
                            <div class="col-12 col-sm-6 col-md-4">
                                <label class="form-label small fw-semibold text-dark">CO3</label>
                                <div class="input-group input-group-sm">
                                    <input type="number" step="0.01" name="co3_kg_year" id="edit_co3" class="form-control edit-yield-calc-input" min="0">
                                    <span class="input-group-text">Kg</span>
                                </div>
                            </div>
                            <div class="col-12 col-sm-6 col-md-4">
                                <label class="form-label small fw-semibold text-dark">CO4</label>
                                <div class="input-group input-group-sm">
                                    <input type="number" step="0.01" name="co4_kg_year" id="edit_co4" class="form-control edit-yield-calc-input" min="0">
                                    <span class="input-group-text">Kg</span>
                                </div>
                            </div>
                            <div class="col-12 col-sm-6 col-md-4">
                                <label class="form-label small fw-semibold text-dark">CO5</label>
                                <div class="input-group input-group-sm">
                                    <input type="number" step="0.01" name="co5_kg_year" id="edit_co5" class="form-control edit-yield-calc-input" min="0">
                                    <span class="input-group-text">Kg</span>
                                </div>
                            </div>
                            <div class="col-12 col-sm-6 col-md-4">
                                <label class="form-label small fw-semibold text-dark">Australian Red Napier</label>
                                <div class="input-group input-group-sm">
                                    <input type="number" step="0.01" name="australian_red_nepier_kg_year" id="edit_aus_red" class="form-control edit-yield-calc-input" min="0">
                                    <span class="input-group-text">Kg</span>
                                </div>
                            </div>
                            <div class="col-12 col-sm-6 col-md-4">
                                <label class="form-label small fw-semibold text-dark">Super Napier</label>
                                <div class="input-group input-group-sm">
                                    <input type="number" step="0.01" name="super_nepier_kg_year" id="edit_sup_nep" class="form-control edit-yield-calc-input" min="0">
                                    <span class="input-group-text">Kg</span>
                                </div>
                            </div>
                            <div class="col-12 col-sm-6 col-md-4">
                                <label class="form-label small fw-semibold text-dark">Sampoorna</label>
                                <div class="input-group input-group-sm">
                                    <input type="number" step="0.01" name="sampoorna_kg_year" id="edit_sampoorna" class="form-control edit-yield-calc-input" min="0">
                                    <span class="input-group-text">Kg</span>
                                </div>
                            </div>
                            <div class="col-12 col-sm-6 col-md-4">
                                <label class="form-label small fw-semibold text-dark">Other Varieties</label>
                                <div class="input-group input-group-sm">
                                    <input type="number" step="0.01" name="other_varieties_kg_year" id="edit_other" class="form-control edit-yield-calc-input" min="0">
                                    <span class="input-group-text">Kg</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light py-2">
                    <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-sm btn-primary fw-bold" style="background-color: #370709; border-color: #370709;">
                        <i class="bi bi-check-circle me-1"></i> Update Yield Record
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<?php
$pageScripts = '
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
$(document).ready(function() {

    // 1. Initialize Lands DataTable with multi-row headers
    if ($("#pastureFodderTable").length) {
        $("#pastureFodderTable").DataTable({
            "orderCellsTop": true,
            "autoWidth": false,
            "order": [[0, "asc"]],
            "pageLength": 10,
            "dom": "Bfrtip",
            "columnDefs": [
                { "orderable": false, "targets": -1 }
            ],
            "buttons": [
                {
                    extend: "csv",
                    text: "<i class=\\"bi bi-file-earmark-spreadsheet\\"></i> CSV",
                    className: "btn btn-sm btn-success me-2"
                },
                {
                    extend: "pdf",
                    text: "<i class=\\"bi bi-file-pdf\\"></i> PDF",
                    className: "btn btn-sm btn-danger me-2"
                },
                {
                    extend: "print",
                    text: "<i class=\\"bi bi-printer\\"></i> Print",
                    className: "btn btn-sm btn-dark"
                }
            ]
        });
    }

    // 2. Initialize Yields DataTable
    if ($("#yieldTable").length) {
        $("#yieldTable").DataTable({
            "order": [[0, "desc"]],
            "pageLength": 10,
            "dom": "Bfrtip",
            "columnDefs": [
                { "orderable": false, "targets": -1 }
            ],
            "buttons": [
                {
                    extend: "csv",
                    text: "<i class=\\"bi bi-file-earmark-spreadsheet\\"></i> CSV",
                    className: "btn btn-sm btn-success me-2"
                },
                {
                    extend: "pdf",
                    text: "<i class=\\"bi bi-file-pdf\\"></i> PDF",
                    className: "btn btn-sm btn-danger me-2"
                },
                {
                    extend: "print",
                    text: "<i class=\\"bi bi-printer\\"></i> Print",
                    className: "btn btn-sm btn-dark"
                }
            ]
        });
    }

    // Tab URL sync: reflect tab change in filterForm and browser history
    $("button[data-bs-toggle=\'tab\']").on("shown.bs.tab", function (e) {
        var targetId = $(e.target).attr("id");
        var tabVal = (targetId === "yields-tab") ? "yields" : "lands";
        $("input[name=\'tab\']").val(tabVal);

        var currentUrl = new URL(window.location.href);
        currentUrl.searchParams.set("tab", tabVal);
        window.history.replaceState({}, "", currentUrl.toString());
    });

    // Auto-calculate Pasture Total Farm Families (Add Form)
    var pastureUserOverridden = false;
    $("#p_total_families").on("input change", function() {
        pastureUserOverridden = true;
        $("#p_auto_badge").text("Manual").removeClass("badge-auto-calc").addClass("badge bg-warning text-dark");
    });
    $(".pasture-calc-input").on("input change", function() {
        if (!pastureUserOverridden) {
            var q = parseInt($("#p_quarter").val()) || 0;
            var h = parseInt($("#p_half").val()) || 0;
            var o = parseInt($("#p_one").val()) || 0;
            var gt = parseInt($("#p_gt_one").val()) || 0;
            $("#p_total_families").val(q + h + o + gt);
        }
    });

    // Auto-calculate Fodder Total Farm Families (Add Form)
    var fodderUserOverridden = false;
    $("#f_total_families").on("input change", function() {
        fodderUserOverridden = true;
        $("#f_auto_badge").text("Manual").removeClass("badge-auto-calc").addClass("badge bg-warning text-dark");
    });
    $(".fodder-calc-input").on("input change", function() {
        if (!fodderUserOverridden) {
            var q = parseInt($("#f_quarter").val()) || 0;
            var h = parseInt($("#f_half").val()) || 0;
            var o = parseInt($("#f_one").val()) || 0;
            var gt = parseInt($("#f_gt_one").val()) || 0;
            $("#f_total_families").val(q + h + o + gt);
        }
    });

    // Reset Form Listener
    $("#btnResetLandForm").on("click", function() {
        pastureUserOverridden = false;
        fodderUserOverridden = false;
        $("#p_auto_badge").text("Auto").removeClass("badge bg-warning text-dark").addClass("badge-auto-calc");
        $("#f_auto_badge").text("Auto").removeClass("badge bg-warning text-dark").addClass("badge-auto-calc");
    });

    // Auto-calculate for Land Edit Modal
    $(".edit-pasture-calc").on("input change", function() {
        var q = parseInt($("#e_p_quarter").val()) || 0;
        var h = parseInt($("#e_p_half").val()) || 0;
        var o = parseInt($("#e_p_one").val()) || 0;
        var gt = parseInt($("#e_p_gt_one").val()) || 0;
        $("#e_p_total_families").val(q + h + o + gt);
    });
    $(".edit-fodder-calc").on("input change", function() {
        var q = parseInt($("#e_f_quarter").val()) || 0;
        var h = parseInt($("#e_f_half").val()) || 0;
        var o = parseInt($("#e_f_one").val()) || 0;
        var gt = parseInt($("#e_f_gt_one").val()) || 0;
        $("#e_f_total_families").val(q + h + o + gt);
    });

    // View Land Details
    $(document).on("click", ".btn-view-land", function() {
        var rowData = $(this).closest("tr").data("row");
        if (rowData) {
            $("#v_vs_range").text(rowData.vs_range);
            $("#v_report_year").text(rowData.report_year || 2024);
            $("#v_p_quarter").text(rowData.pasture_families_quarter_ac);
            $("#v_p_half").text(rowData.pasture_families_half_ac);
            $("#v_p_one").text(rowData.pasture_families_one_ac);
            $("#v_p_gt_one").text(rowData.pasture_families_gt_one_ac);
            $("#v_p_total_acre").text(parseFloat(rowData.pasture_total_acre).toFixed(2) + " Ac");
            $("#v_p_total_families").text(rowData.pasture_total_families);
            $("#v_f_quarter").text(rowData.fodder_families_quarter_ac);
            $("#v_f_half").text(rowData.fodder_families_half_ac);
            $("#v_f_one").text(rowData.fodder_families_one_ac);
            $("#v_f_gt_one").text(rowData.fodder_families_gt_one_ac);
            $("#v_f_total_acre").text(parseFloat(rowData.fodder_total_acre).toFixed(2) + " Ac");
            $("#v_f_total_families").text(rowData.fodder_total_families);

            new bootstrap.Modal(document.getElementById("viewLandModal")).show();
        }
    });

    // Edit Land Record
    $(document).on("click", ".btn-edit-land", function() {
        var rowData = $(this).closest("tr").data("row");
        if (rowData) {
            $("#e_id").val(rowData.id);
            $("#e_vs_range").val(rowData.vs_range);
            $("#e_report_year").val(rowData.report_year || 2024);
            $("#e_report_month").val(rowData.report_month || "");
            $("#e_p_quarter").val(rowData.pasture_families_quarter_ac);
            $("#e_p_half").val(rowData.pasture_families_half_ac);
            $("#e_p_one").val(rowData.pasture_families_one_ac);
            $("#e_p_gt_one").val(rowData.pasture_families_gt_one_ac);
            $("#e_p_total_acre").val(rowData.pasture_total_acre);
            $("#e_p_total_families").val(rowData.pasture_total_families);
            $("#e_f_quarter").val(rowData.fodder_families_quarter_ac);
            $("#e_f_half").val(rowData.fodder_families_half_ac);
            $("#e_f_one").val(rowData.fodder_families_one_ac);
            $("#e_f_gt_one").val(rowData.fodder_families_gt_one_ac);
            $("#e_f_total_acre").val(rowData.fodder_total_acre);
            $("#e_f_total_families").val(rowData.fodder_total_families);

            new bootstrap.Modal(document.getElementById("editLandModal")).show();
        }
    });

    // Delete Land Record Confirmation
    $(document).on("click", ".btn-delete-land", function(e) {
        e.preventDefault();
        var deleteUrl = $(this).attr("href");
        var rowData = $(this).closest("tr").data("row");
        var rangeName = rowData ? rowData.vs_range : "this record";

        Swal.fire({
            icon: "warning",
            title: "Delete Land Record?",
            html: "Are you sure you want to delete the pasture & fodder land record for <strong>" + rangeName + "</strong>?<br>This action cannot be undone.",
            showCancelButton: true,
            confirmButtonColor: "#dc3545",
            cancelButtonColor: "#6c757d",
            confirmButtonText: "Yes, Delete",
            cancelButtonText: "Cancel"
        }).then(function(result) {
            if (result.isConfirmed) {
                window.location.href = deleteUrl;
            }
        });
    });

    // Yield live total calculation
    function updateAddYieldTotal() {
        var total = 0;
        $(".yield-calc-input").each(function() {
            total += parseFloat($(this).val()) || 0;
        });
        $("#addYieldTotalPreview").text(total.toFixed(2));
    }
    $(document).on("input change", ".yield-calc-input", updateAddYieldTotal);

    function updateEditYieldTotal() {
        var total = 0;
        $(".edit-yield-calc-input").each(function() {
            total += parseFloat($(this).val()) || 0;
        });
        $("#editYieldTotalPreview").text(total.toFixed(2));
    }
    $(document).on("input change", ".edit-yield-calc-input", updateEditYieldTotal);

    // Edit Yield Record
    $(document).on("click", ".btn-edit-yield", function() {
        var $row = $(this).closest("tr");
        $("#edit_yield_id").val($row.data("id"));
        $("#edit_yield_year").val($row.data("year"));
        $("#edit_yield_month").val($row.data("month") || "");
        $("#edit_co3").val($row.data("co3"));
        $("#edit_co4").val($row.data("co4"));
        $("#edit_co5").val($row.data("co5"));
        $("#edit_aus_red").val($row.data("aus_red"));
        $("#edit_sup_nep").val($row.data("sup_nep"));
        $("#edit_sampoorna").val($row.data("sampoorna"));
        $("#edit_other").val($row.data("other"));

        updateEditYieldTotal();
        new bootstrap.Modal(document.getElementById("editYieldModal")).show();
    });

    // Delete Yield Record Confirmation
    $(document).on("click", ".btn-delete-yield", function(e) {
        e.preventDefault();
        var deleteUrl = $(this).attr("href");
        var $row = $(this).closest("tr");
        var year = $row.data("year");

        Swal.fire({
            icon: "warning",
            title: "Delete Yield Record?",
            html: "Are you sure you want to permanently delete the pasture yield record for year <strong>" + year + "</strong>?<br>This action cannot be undone.",
            showCancelButton: true,
            confirmButtonColor: "#dc3545",
            cancelButtonColor: "#6c757d",
            confirmButtonText: "Yes, Delete",
            cancelButtonText: "Cancel"
        }).then(function(result) {
            if (result.isConfirmed) {
                window.location.href = deleteUrl;
            }
        });
    });

    // Flash Toast / Alert Handler
    var urlParams = new URLSearchParams(window.location.search);
    var status = urlParams.get("status");
    var msg = urlParams.get("msg") || "";

    if (status === "success") {
        Swal.fire({
            icon: "success",
            title: "Success!",
            text: msg ? msg : "Operation completed successfully.",
            confirmButtonColor: "#370709"
        });
        urlParams.delete("status");
        urlParams.delete("msg");
        var newSearch = urlParams.toString() ? "?" + urlParams.toString() : "";
        window.history.replaceState({}, document.title, window.location.pathname + newSearch);
    } else if (status === "error") {
        Swal.fire({
            icon: "error",
            title: "Error!",
            text: msg ? msg : "An error occurred during operation.",
            confirmButtonColor: "#370709"
        });
        urlParams.delete("status");
        urlParams.delete("msg");
        var newSearch = urlParams.toString() ? "?" + urlParams.toString() : "";
        window.history.replaceState({}, document.title, window.location.pathname + newSearch);
    }

});
</script>
';
require_once __DIR__ . '/../../../includes/footer.php';
?>
