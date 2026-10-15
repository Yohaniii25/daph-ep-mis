<?php
session_start();
require_once __DIR__ . '/../../../config/db_connect.php';

/** @var mysqli $mysqli */
global $mysqli;

$allowed_roles = [
    'veterinary_surgeon',
    'government_veterinary_surgeon',
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

$user_id  = (int)($_SESSION['user_id'] ?? 0);
$user_role = $_SESSION['role'] ?? '';
$session_range_id = (int)($_SESSION['range_id'] ?? 0);

$is_supervisory = in_array($user_role, [
    'district_dd',
    'deputy_director_district',
    'administrator',
    'provincial_director',
    'deputy_director_hq_1',
    'deputy_director_hq_2',
    'admin'
], true);

// Requested range from GET, or session range
$requested_range_id = isset($_GET['range_id']) && is_numeric($_GET['range_id']) ? (int)$_GET['range_id'] : null;
$range_id = ($is_supervisory && $requested_range_id) ? $requested_range_id : ($session_range_id ?: 1);

$range_name = 'Ampara';
$range_code = '01';
$district_name = 'Ampara';
$district_id = 1;

// Extract Range Name, Range Code, District Name, and IDs using standard relational JOIN
if (!empty($range_id)) {
    $details_sql = "
        SELECT 
            vr.name AS range_name,
            vr.code AS range_code,
            d.name AS district_name,
            d.id AS district_id
        FROM veterinary_ranges vr
        LEFT JOIN districts d ON vr.district_id = d.id
        WHERE vr.id = ?
    ";

    $details_query = $mysqli->prepare($details_sql);
    if ($details_query) {
        $details_query->bind_param("i", $range_id);
        $details_query->execute();
        $details_result = $details_query->get_result();
        if ($data = $details_result->fetch_assoc()) {
            $range_name = $data['range_name'] ?? 'Your Assigned Range';
            $range_code = !empty($data['range_code']) ? $data['range_code'] : sprintf('%02d', $range_id);
            $district_name = $data['district_name'] ?? 'Your District';
            $district_id = (int)($data['district_id'] ?? 1);
        }
        $details_query->close();
    }
}

// Active year and active tab
$selected_year = isset($_GET['year']) ? intval($_GET['year']) : intval(date('Y'));
$active_tab = isset($_GET['tab']) && $_GET['tab'] === 'summary' ? 'summary' : 'identification';

// Strict Status Definitions for Animal Identification
$VALID_AI_STATUSES = ['Sold or transferred', 'Slaughter', 'Died'];
$selected_ai_status = isset($_GET['ai_status']) ? trim($_GET['ai_status']) : 'All';

// Fetch registered farmers for auto-complete datalist
$registered_farmers = [];
$rf_res = $mysqli->query("
    SELECT DISTINCT full_name AS farmer_name, farm_registration_no AS farm_reg_no 
    FROM farm_registration_renewals 
    WHERE full_name IS NOT NULL AND full_name != ''
    LIMIT 150
");
if ($rf_res) {
    while ($rf = $rf_res->fetch_assoc()) {
        $registered_farmers[] = $rf;
    }
}

// Query records from animal_identifications
$ai_records = [];
$ai_query = "SELECT * FROM animal_identifications WHERE 1=1";
$ai_params = [];
$ai_types = "";

if (!$is_supervisory || $range_id) {
    $ai_query .= " AND (range_id = ? OR range_id IS NULL OR range_id = 0)";
    $ai_types .= "i";
    $ai_params[] = $range_id;
}

if ($selected_year > 0) {
    $ai_query .= " AND YEAR(identification_date) = ?";
    $ai_types .= "i";
    $ai_params[] = $selected_year;
}

if ($selected_ai_status !== 'All' && in_array($selected_ai_status, $VALID_AI_STATUSES, true)) {
    $ai_query .= " AND present_status = ?";
    $ai_types .= "s";
    $ai_params[] = $selected_ai_status;
}

$ai_query .= " ORDER BY identification_date DESC, id DESC";

$ai_stmt = $mysqli->prepare($ai_query);
if ($ai_stmt) {
    if (!empty($ai_params)) {
        $ai_stmt->bind_param($ai_types, ...$ai_params);
    }
    $ai_stmt->execute();
    $ai_res = $ai_stmt->get_result();
    while ($row = $ai_res->fetch_assoc()) {
        $ai_records[] = $row;
    }
    $ai_stmt->close();
}

// Compute KPI Stats for Animal Identification
$ai_stats_query = "
    SELECT 
        COUNT(*) AS total_count,
        SUM(CASE WHEN present_status = 'Sold or transferred' THEN 1 ELSE 0 END) AS sold_count,
        SUM(CASE WHEN present_status = 'Slaughter' THEN 1 ELSE 0 END) AS slaughter_count,
        SUM(CASE WHEN present_status = 'Died' THEN 1 ELSE 0 END) AS died_count
    FROM animal_identifications
    WHERE 1=1
";
if (!$is_supervisory || $range_id) {
    $ai_stats_query .= " AND (range_id = " . intval($range_id) . " OR range_id IS NULL OR range_id = 0)";
}
$ai_stats_res = $mysqli->query($ai_stats_query);
$ai_stats = $ai_stats_res ? $ai_stats_res->fetch_assoc() : ['total_count' => 0, 'sold_count' => 0, 'slaughter_count' => 0, 'died_count' => 0];

$ai_total_count = (int)($ai_stats['total_count'] ?? 0);
$ai_sold_count = (int)($ai_stats['sold_count'] ?? 0);
$ai_slaughter_count = (int)($ai_stats['slaughter_count'] ?? 0);
$ai_died_count = (int)($ai_stats['died_count'] ?? 0);

$month_names = [
    1 => 'January', 2 => 'February', 3 => 'March', 4 => 'April',
    5 => 'May', 6 => 'June', 7 => 'July', 8 => 'August',
    9 => 'September', 10 => 'October', 11 => 'November', 12 => 'December'
];

// 1. Fetch Tagging Programs for the selected year and range
$tagging_programs = [];
$total_programs_tags_used = 0;
$total_programs_tags_spoiled = 0;

if (!empty($range_id)) {
    $prog_sql = "
        SELECT p.*, 
               (SELECT COUNT(*) FROM cattle_vouchers cv WHERE cv.tag_program_id = p.id) AS voucher_count 
        FROM ear_tag_programs p 
        WHERE p.range_id = ? AND YEAR(p.program_date) = ? 
        ORDER BY p.program_date DESC, p.id DESC
    ";
    $p_stmt = $mysqli->prepare($prog_sql);
    if ($p_stmt) {
        $p_stmt->bind_param("ii", $range_id, $selected_year);
        $p_stmt->execute();
        $p_res = $p_stmt->get_result();
        while ($row = $p_res->fetch_assoc()) {
            $tagging_programs[] = $row;
            $total_programs_tags_used += intval($row['tags_used']);
            $total_programs_tags_spoiled += intval($row['tags_spoiled']);
        }
        $p_stmt->close();
    }
}

// 2. Fetch Real-Time Aggregated Program Usage per Month
$realtime_month_usage = []; // [month => ['used' => int, 'spoiled' => int]]
for ($m = 1; $m <= 12; $m++) {
    $realtime_month_usage[$m] = ['used' => 0, 'spoiled' => 0];
}

if (!empty($range_id)) {
    $agg_sql = "
        SELECT MONTH(program_date) AS m, 
               COALESCE(SUM(tags_used), 0) AS total_used, 
               COALESCE(SUM(tags_spoiled), 0) AS total_spoiled 
        FROM ear_tag_programs 
        WHERE range_id = ? AND YEAR(program_date) = ? 
        GROUP BY MONTH(program_date)
    ";
    $a_stmt = $mysqli->prepare($agg_sql);
    if ($a_stmt) {
        $a_stmt->bind_param("ii", $range_id, $selected_year);
        $a_stmt->execute();
        $a_res = $a_stmt->get_result();
        while ($row = $a_res->fetch_assoc()) {
            $m = intval($row['m']);
            $realtime_month_usage[$m] = [
                'used' => intval($row['total_used']),
                'spoiled' => intval($row['total_spoiled'])
            ];
        }
        $a_stmt->close();
    }
}

// 3. Fetch Stock Baseline & Movements from ear_tag_usage
$raw_usage_records = [];
if (!empty($range_id)) {
    $u_sql = "
        SELECT id, report_year, report_month, opening_balance, received_qty, used_qty, spoilt_qty, transferred_qty, closing_balance 
        FROM ear_tag_usage 
        WHERE range_id = ? AND report_year = ? 
        ORDER BY report_month ASC
    ";
    $u_stmt = $mysqli->prepare($u_sql);
    if ($u_stmt) {
        $u_stmt->bind_param("ii", $range_id, $selected_year);
        $u_stmt->execute();
        $u_res = $u_stmt->get_result();
        while ($row = $u_res->fetch_assoc()) {
            $raw_usage_records[intval($row['report_month'])] = $row;
        }
        $u_stmt->close();
    }
}

// 4. Build Automated Dynamic 12-Month Real-Time Summary
$monthly_summary = [];
$running_balance = 0;
$grand_total_received = 0;
$grand_total_used = 0;
$grand_total_spoiled = 0;
$grand_total_transferred = 0;
$first_opening_balance = 0;

// Check if previous year month 12 has a closing balance to seed January opening
if (!empty($range_id)) {
    $prev_y = $selected_year - 1;
    $seed_sql = "SELECT closing_balance FROM ear_tag_usage WHERE range_id = ? AND report_year = ? AND report_month = 12 LIMIT 1";
    $s_stmt = $mysqli->prepare($seed_sql);
    if ($s_stmt) {
        $s_stmt->bind_param("ii", $range_id, $prev_y);
        $s_stmt->execute();
        $s_res = $s_stmt->get_result();
        if ($sr = $s_res->fetch_assoc()) {
            $running_balance = intval($sr['closing_balance']);
        }
        $s_stmt->close();
    }
}

for ($m = 1; $m <= 12; $m++) {
    $has_record = isset($raw_usage_records[$m]);
    $rec_id = $has_record ? $raw_usage_records[$m]['id'] : 0;
    
    // Opening balance: either recorded or roll-over from previous month's ending
    if ($has_record && $raw_usage_records[$m]['opening_balance'] > 0) {
        $opening = intval($raw_usage_records[$m]['opening_balance']);
    } else {
        $opening = $running_balance;
    }

    if ($m === 1) {
        $first_opening_balance = $opening;
    }

    $received = $has_record ? intval($raw_usage_records[$m]['received_qty']) : 0;
    $transferred = $has_record ? intval($raw_usage_records[$m]['transferred_qty']) : 0;

    // Real-time dynamic calculation from ear_tag_programs even mid-month!
    $real_used = $realtime_month_usage[$m]['used'];
    $real_spoiled = $realtime_month_usage[$m]['spoiled'];

    // If no programs logged yet for this month but a manual usage was logged previously in ear_tag_usage, respect it as fallback
    if ($real_used === 0 && $real_spoiled === 0 && $has_record) {
        $real_used = intval($raw_usage_records[$m]['used_qty']);
        $real_spoiled = intval($raw_usage_records[$m]['spoilt_qty']);
    }

    $ending = ($opening + $received) - ($real_used + $real_spoiled + $transferred);
    $running_balance = $ending;

    $grand_total_received += $received;
    $grand_total_used += $real_used;
    $grand_total_spoiled += $real_spoiled;
    $grand_total_transferred += $transferred;

    $monthly_summary[$m] = [
        'month'           => $m,
        'month_name'      => $month_names[$m],
        'id'              => $rec_id,
        'opening_balance' => $opening,
        'received_qty'    => $received,
        'used_qty'        => $real_used,
        'spoilt_qty'      => $real_spoiled,
        'transferred_qty' => $transferred,
        'ending_balance'  => $ending,
        'has_entry'       => ($has_record || $real_used > 0 || $real_spoiled > 0 || $received > 0 || $transferred > 0)
    ];
}

$latest_inventory_balance = $running_balance;

require_once '../../../includes/header.php';
?>

<link rel="stylesheet" href="../../../assets/css/bootstrap-icons.min.css">
<link rel="stylesheet" href="../../../assets/css/dataTables.bootstrap5.min.css">
<link rel="stylesheet" href="../../../assets/css/buttons.bootstrap5.min.css">
<link rel="stylesheet" href="../../../assets/css/sweetalert2.min.css">
<link rel="stylesheet" href="../../../assets/css/veterinary.css">

<style>
/* Refined styling matching letter_h_record */
.nav-pills-letter-h {
    flex-wrap: nowrap;
    gap: 8px;
}
.nav-pills-letter-h .nav-link {
    font-size: 0.88rem;
    font-weight: 600;
    color: #475569;
    padding: 0.65rem 1.25rem;
    border-radius: 8px;
    border: 1px solid #e2e8f0;
    background-color: #f8fafc;
    transition: all 0.2s ease-in-out;
    display: inline-flex;
    align-items: center;
    gap: 8px;
}
.nav-pills-letter-h .nav-link:hover {
    background-color: #f1f5f9;
    color: #820100;
    border-color: #cbd5e1;
}
.nav-pills-letter-h .nav-link.active {
    background-color: #820100 !important;
    color: #ffffff !important;
    border-color: #820100 !important;
    box-shadow: 0 3px 8px rgba(130, 1, 0, 0.25);
}
.nav-pills-letter-h .nav-link .badge-tab-count {
    font-size: 0.72rem;
    padding: 2px 7px;
    border-radius: 10px;
    background-color: rgba(0, 0, 0, 0.08);
    color: inherit;
}
.nav-pills-letter-h .nav-link.active .badge-tab-count {
    background-color: rgba(255, 255, 255, 0.25);
    color: #fff;
}
.table-period-total {
    background-color: #fef2f2 !important;
    color: #820100;
    font-weight: 700;
}

/* Animal Identification Custom Component Styles */
.ai-kpi-card {
    border: none;
    border-radius: 12px;
    transition: transform 0.2s ease, box-shadow 0.2s ease;
}
.ai-kpi-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 24px rgba(0,0,0,0.08) !important;
}
.tag-preview-badge {
    font-family: var(--bs-font-monospace);
    font-size: 0.95rem;
    letter-spacing: 0.05em;
    background-color: #f1f5f9;
    border: 1px dashed #0284c7;
    color: #0369a1;
    padding: 6px 12px;
    border-radius: 6px;
    display: inline-block;
}
.status-pill-sold {
    background-color: #e0f2fe;
    color: #0369a1;
    border: 1px solid #bae6fd;
}
.status-pill-slaughter {
    background-color: #fef3c7;
    color: #b45309;
    border: 1px solid #fde68a;
}
.status-pill-died {
    background-color: #fee2e2;
    color: #b91c1c;
    border: 1px solid #fecaca;
}
.quick-status-select {
    font-size: 0.78rem;
    padding: 3px 6px;
    border-radius: 6px;
    font-weight: 600;
    cursor: pointer;
}
.btn-maroon {
    background-color: #820100;
    color: #ffffff;
    border-color: #820100;
}
.btn-maroon:hover {
    background-color: #500707;
    color: #ffffff;
}

/* Modal Responsiveness & Viewport Scroll System */
.modal {
    z-index: 1060 !important;
}
.modal-backdrop {
    z-index: 1050 !important;
}
#modalAddAnimalIdentification .modal-dialog {
    max-width: 96vw;
    width: 1320px;
    margin: 1.25rem auto;
}
#modalAddAnimalIdentification .modal-content {
    max-height: 90vh;
    display: flex;
    flex-direction: column;
    overflow: hidden;
}
#modalAddAnimalIdentification .modal-body {
    overflow-y: auto !important;
    -webkit-overflow-scrolling: touch;
    flex: 1 1 auto;
    min-height: 0;
    max-height: calc(90vh - 130px) !important;
}
@media (max-width: 991.98px) {
    #modalAddAnimalIdentification .modal-dialog {
        max-width: 100vw;
        width: 100%;
        margin: 0;
        height: 100%;
    }
    #modalAddAnimalIdentification .modal-content {
        height: 100%;
        max-height: 100vh;
        border-radius: 0;
    }
    #modalAddAnimalIdentification .modal-body {
        max-height: calc(100vh - 120px) !important;
    }
}
</style>

<!-- MAIN CONTENT CONTAINER -->
<div class="mb-4 d-flex justify-content-between align-items-center">
    <div>
        <h2 class="h4 fw-bold mb-1" style="color: #370709;">Ear Tag Balance & Program Management</h2>
        <p class="text-muted small mb-0">
            Log tagging programs, register animal identification &amp; vouchers, and track dynamic monthly returns for 
            <strong class="text-dark"><?= htmlspecialchars($range_name) ?></strong> (<?= htmlspecialchars($district_name) ?> District)
        </p>
    </div>
    
    <div class="d-flex align-items-center gap-2">
        <form method="GET" class="d-flex align-items-center gap-2">
            <input type="hidden" name="tab" value="<?= htmlspecialchars($active_tab) ?>">
            <label class="small fw-bold text-muted mb-0">Year:</label>
            <select name="year" class="form-select form-select-sm" onchange="this.form.submit()" style="width: 105px;">
                <?php
                $curr_year = intval(date('Y'));
                for ($y = $curr_year - 4; $y <= $curr_year + 4; $y++) {
                    $sel = ($y === $selected_year) ? 'selected' : '';
                    echo "<option value=\"$y\" $sel>$y</option>";
                }
                ?>
            </select>
        </form>
        <button type="button" class="btn btn-warning text-dark shadow-sm text-nowrap" id="btnTopRegisterAI" data-bs-toggle="modal" data-bs-target="#modalAddAnimalIdentification" data-mode="single">
            <i class="bi bi-tag-fill me-1"></i> Register Animal Identification
        </button>
        <a href="monthly-annual-reports.php" class="btn btn-secondary shadow-sm text-nowrap">
            <i class="bi bi-arrow-left me-2"></i>Back
        </a>
    </div>
</div>

<!-- TOP STATS CARDS (DYNAMIC REAL-TIME METRICS) -->
<div class="row g-3 mb-4">
    <div class="col-6 col-lg-3">
        <div class="card shadow-sm border-0 border-start border-primary border-4">
            <div class="card-body py-3">
                <span class="text-muted small text-uppercase fw-bold">Active Year</span>
                <h4 class="mb-0 fw-bold math-numeric text-primary mt-1"><?= $selected_year ?></h4>
                <small class="text-muted"><?= number_format($ai_total_count) ?> Animals Identified</small>
            </div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="card shadow-sm border-0 border-start border-success border-4">
            <div class="card-body py-3">
                <span class="text-muted small text-uppercase fw-bold">Total Received</span>
                <h4 class="mb-0 fw-bold math-numeric text-success mt-1"><?= number_format($grand_total_received) ?></h4>
                <small class="text-muted">Stock received in <?= $selected_year ?></small>
            </div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="card shadow-sm border-0 border-start border-info border-4">
            <div class="card-body py-3">
                <span class="text-muted small text-uppercase fw-bold">Total Used (Tagged)</span>
                <h4 class="mb-0 fw-bold math-numeric text-info mt-1"><?= number_format($grand_total_used) ?></h4>
                <small class="text-muted">Real-time aggregate total</small>
            </div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="card shadow-sm border-0 border-start border-danger border-4">
            <div class="card-body py-3">
                <span class="text-muted small text-uppercase fw-bold">Current Ending Balance</span>
                <h4 class="mb-0 fw-bold math-numeric <?= $latest_inventory_balance < 0 ? 'text-danger' : 'text-dark' ?> mt-1">
                    <?= number_format($latest_inventory_balance) ?>
                </h4>
                <small class="text-muted">Available stock in range</small>
            </div>
        </div>
    </div>
</div>

<!-- ============================================================ -->
<!-- THREE-SECTION INTERFACE SPLIT (TABS) -->
<!-- ============================================================ -->
<div class="card shadow-sm border-0 mb-4">
    <div class="card-header bg-white p-3 border-bottom">
        <ul class="nav nav-pills nav-pills-letter-h" id="earTagTabs" role="tablist">
            <!-- TAB 1: ANIMAL IDENTIFICATION & VOUCHERS -->
            <li class="nav-item" role="presentation">
                <button class="nav-link <?= $active_tab === 'identification' ? 'active' : '' ?>" 
                        id="tab-identification-btn" 
                        data-bs-toggle="pill" 
                        data-bs-target="#tab-identification-content" 
                        type="button" 
                        role="tab" 
                        aria-controls="tab-identification-content" 
                        aria-selected="<?= $active_tab === 'identification' ? 'true' : 'false' ?>">
                    <i class="bi bi-tag-fill me-1"></i>
                    Animal Identification &amp; Vouchers
                    <span class="badge-tab-count"><?= count($ai_records) ?></span>
                </button>
            </li>

            <!-- TAB 2: MONTHLY SUMMARY -->
            <li class="nav-item" role="presentation">
                <button class="nav-link <?= $active_tab === 'summary' ? 'active' : '' ?>" 
                        id="tab-summary-btn" 
                        data-bs-toggle="pill" 
                        data-bs-target="#tab-summary-content" 
                        type="button" 
                        role="tab" 
                        aria-controls="tab-summary-content" 
                        aria-selected="<?= $active_tab === 'summary' ? 'true' : 'false' ?>">
                    <i class="bi bi-calendar3 me-1"></i>
                    Monthly Summary
                    <span class="badge-tab-count">12 Months</span>
                </button>
            </li>
        </ul>
    </div>

    <div class="tab-content" id="earTagTabsContent">




        <!-- ============================================================ -->
        <!-- TAB 1 CONTENT: ANIMAL IDENTIFICATION & CATTLE VOUCHERS       -->
        <!-- ============================================================ -->
        <div class="tab-pane fade <?= $active_tab === 'identification' ? 'show active' : '' ?>" 
             id="tab-identification-content" 
             role="tabpanel" 
             aria-labelledby="tab-identification-btn">
            
            <div class="p-3 bg-light border-bottom d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div>
                    <h6 class="mb-0 fw-bold text-dark"><i class="bi bi-tag-fill me-2 text-danger"></i>Animal Identification Program &amp; Cattle Vouchers - <?= $selected_year ?></h6>
                    <small class="text-muted">Auto-concatenates VS Range, Farm Registration, and Animal Sequences for ear tag generation, and manages individual cattle vouchers</small>
                </div>
                <div class="d-flex align-items-center gap-2 flex-wrap">
                    <button type="button" class="btn btn-sm btn-maroon fw-semibold shadow-sm" id="btnOpenAddModal" data-bs-toggle="modal" data-bs-target="#modalAddAnimalIdentification" data-mode="single">
                        <i class="bi bi-plus-circle-fill me-1"></i>Register Animal Identification
                    </button>
                    <button type="button" class="btn btn-sm btn-success fw-semibold shadow-sm" id="btnOpenBatchModal" data-bs-toggle="modal" data-bs-target="#modalAddAnimalIdentification" data-mode="batch">
                        <i class="bi bi-layers-fill me-1"></i>Batch Tagging Grid
                    </button>
                </div>
            </div>

            <!-- KPI STATS CARDS FOR ANIMAL IDENTIFICATION -->
            <div class="p-3 bg-white border-bottom">
                <div class="row g-3">
                    <div class="col-6 col-md-3">
                        <div class="card ai-kpi-card shadow-sm p-3 h-100" style="background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%); border-left: 4px solid #370709 !important;">
                            <div class="d-flex justify-content-between align-items-center">
                                <span class="text-uppercase fw-bold small text-muted">Total Identified</span>
                                <i class="bi bi-tags-fill fs-4" style="color: #370709; opacity: 0.8;"></i>
                            </div>
                            <h4 class="fw-bold my-1 text-dark font-monospace"><?= number_format($ai_total_count) ?></h4>
                            <small class="text-muted">Cattle vouchers &amp; tags issued</small>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="card ai-kpi-card shadow-sm p-3 h-100" style="background: linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 100%); border-left: 4px solid #0284c7 !important;">
                            <div class="d-flex justify-content-between align-items-center">
                                <span class="text-uppercase fw-bold small text-primary">Sold or Transferred</span>
                                <i class="bi bi-arrow-left-right fs-4 text-primary opacity-75"></i>
                            </div>
                            <h4 class="fw-bold my-1 text-dark font-monospace"><?= number_format($ai_sold_count) ?></h4>
                            <small class="text-muted"><?= $ai_total_count > 0 ? round(($ai_sold_count / $ai_total_count) * 100, 1) : 0 ?>% of tagged inventory</small>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="card ai-kpi-card shadow-sm p-3 h-100" style="background: linear-gradient(135deg, #fffbeb 0%, #fef3c7 100%); border-left: 4px solid #d97706 !important;">
                            <div class="d-flex justify-content-between align-items-center">
                                <span class="text-uppercase fw-bold small text-warning">Slaughter</span>
                                <i class="bi bi-scissors fs-4 text-warning opacity-75"></i>
                            </div>
                            <h4 class="fw-bold my-1 text-dark font-monospace"><?= number_format($ai_slaughter_count) ?></h4>
                            <small class="text-muted"><?= $ai_total_count > 0 ? round(($ai_slaughter_count / $ai_total_count) * 100, 1) : 0 ?>% meat production / disposal</small>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="card ai-kpi-card shadow-sm p-3 h-100" style="background: linear-gradient(135deg, #fef2f2 0%, #fee2e2 100%); border-left: 4px solid #dc2626 !important;">
                            <div class="d-flex justify-content-between align-items-center">
                                <span class="text-uppercase fw-bold small text-danger">Died</span>
                                <i class="bi bi-x-circle-fill fs-4 text-danger opacity-75"></i>
                            </div>
                            <h4 class="fw-bold my-1 text-danger font-monospace"><?= number_format($ai_died_count) ?></h4>
                            <small class="text-muted"><?= $ai_total_count > 0 ? round(($ai_died_count / $ai_total_count) * 100, 1) : 0 ?>% natural / disease mortality</small>
                        </div>
                    </div>
                </div>
            </div>

            <!-- CUSTOM ACTION TOOLBAR -->
            <div class="p-3 bg-white border-bottom d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div>
                    <span class="fw-bold text-dark"><i class="bi bi-table me-1 text-primary"></i>Animal Identification Details Grid</span>
                </div>
                <div class="custom-table-toolbar d-flex gap-2 flex-wrap align-items-center">
                    <button type="button" class="btn btn-sm btn-table-export-csv" onclick="exportAnimalIdentificationCSV();" title="Export Animal Identification Records to CSV">
                        <i class="bi bi-file-earmark-spreadsheet"></i> <span>Export to CSV</span>
                    </button>
                    <button type="button" class="btn btn-sm btn-table-export-pdf" onclick="exportAnimalIdentificationPDF();" title="Export Animal Identification Records to PDF">
                        <i class="bi bi-file-earmark-pdf"></i> <span>Export to PDF</span>
                    </button>
                    <div class="dropdown">
                        <button class="btn btn-sm btn-table-sort dropdown-toggle" type="button" id="aiSortMenuBtn" data-bs-toggle="dropdown" aria-expanded="false" title="Quick Sort Records">
                            <i class="bi bi-sort-down me-1"></i>Sort
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end shadow-sm" aria-labelledby="aiSortMenuBtn">
                            <li><h6 class="dropdown-header">Identification Date</h6></li>
                            <li><a class="dropdown-item" href="javascript:void(0)" onclick="sortAnimalIdentification(7, 'desc');"><i class="bi bi-sort-numeric-down-alt me-2 text-danger"></i>Date (Newest first)</a></li>
                            <li><a class="dropdown-item" href="javascript:void(0)" onclick="sortAnimalIdentification(7, 'asc');"><i class="bi bi-sort-numeric-down me-2 text-secondary"></i>Date (Oldest first)</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><h6 class="dropdown-header">Ear Tag Number</h6></li>
                            <li><a class="dropdown-item" href="javascript:void(0)" onclick="sortAnimalIdentification(5, 'asc');"><i class="bi bi-sort-numeric-down me-2 text-primary"></i>Ear Tag (Ascending)</a></li>
                            <li><a class="dropdown-item" href="javascript:void(0)" onclick="sortAnimalIdentification(5, 'desc');"><i class="bi bi-sort-numeric-down-alt me-2 text-primary"></i>Ear Tag (Descending)</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><h6 class="dropdown-header">Farmer Name</h6></li>
                            <li><a class="dropdown-item" href="javascript:void(0)" onclick="sortAnimalIdentification(3, 'asc');"><i class="bi bi-sort-alpha-down me-2 text-success"></i>Farmer Name (A to Z)</a></li>
                            <li><a class="dropdown-item" href="javascript:void(0)" onclick="sortAnimalIdentification(3, 'desc');"><i class="bi bi-sort-alpha-down-alt me-2 text-success"></i>Farmer Name (Z to A)</a></li>
                        </ul>
                    </div>
                    <span class="badge table-record-count-badge" id="animalIdentificationRecordCountBadge" data-entity="Identified Animals">
                        <i class="bi bi-check2-circle me-1"></i><span id="animalIdentificationRecordCountText"><?= count($ai_records) ?> Identified Animals</span>
                    </span>
                </div>
            </div>

            <!-- 14-COLUMN DATA GRID TABLE -->
            <div class="p-3">
                <div class="table-responsive">
                    <table id="animalIdentificationTable" class="table table-hover table-striped align-middle mb-0 w-100" style="min-width: 1400px;">
                        <thead class="table-light text-secondary small">
                            <tr>
                                <th class="text-center" style="width: 40px;">#</th>
                                <th>District</th>
                                <th>VS Range</th>
                                <th>Farmer's Name</th>
                                <th>Farm Registration No.</th>
                                <th>Ear Tag No. (Auto-generated)</th>
                                <th>Cattle Voucher S.No. / Tag Serial</th>
                                <th>Date of Identification</th>
                                <th>Gender</th>
                                <th>Colour</th>
                                <th>Species</th>
                                <th>Breed</th>
                                <th>Age</th>
                                <th>Purpose (Animal ownership)</th>
                                <th>IO number</th>
                                <th class="text-center" style="min-width: 170px;">Present Status</th>
                                <th class="text-center" style="width: 90px;">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="small">
                            <?php if (empty($ai_records)): ?>
                                <tr>
                                    <td colspan="17" class="text-center py-5 text-muted">
                                        <i class="bi bi-tag fs-2 d-block mb-2 text-secondary opacity-50"></i>
                                        No animal identification records found for the selected criteria.
                                        <div class="mt-2">
                                            <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#modalAddAnimalIdentification" data-mode="single">
                                                <i class="bi bi-plus-circle me-1"></i>Register First Animal Identification
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($ai_records as $idx => $r): ?>
                                    <tr data-id="<?= $r['id'] ?>" data-tag="<?= htmlspecialchars($r['ear_tag_no']) ?>" data-farmer="<?= htmlspecialchars($r['farmer_name']) ?>">
                                        <td class="text-center text-muted fw-bold"><?= $idx + 1 ?></td>
                                        <td><span class="badge bg-light text-dark border"><?= htmlspecialchars($r['district'] ?: $district_name) ?></span></td>
                                        <td><span class="fw-semibold text-dark"><?= htmlspecialchars($r['vs_range'] ?: $range_name) ?></span></td>
                                        <td><strong class="text-dark"><?= htmlspecialchars($r['farmer_name']) ?></strong></td>
                                        <td><span class="font-monospace text-secondary small bg-light px-2 py-1 rounded border"><?= htmlspecialchars($r['farm_reg_no']) ?></span></td>
                                        <td><strong class="font-monospace text-primary bg-primary-subtle px-2 py-1 rounded border border-primary-subtle d-inline-block"><?= htmlspecialchars($r['ear_tag_no']) ?></strong></td>
                                        <td><span class="font-monospace fw-bold text-dark"><?= htmlspecialchars($r['voucher_serial_no']) ?></span></td>
                                        <td class="text-nowrap text-secondary"><i class="bi bi-calendar-event me-1"></i><?= date('d M Y', strtotime($r['identification_date'])) ?></td>
                                        <td>
                                            <?php if ($r['gender'] === 'Male'): ?>
                                                <span class="badge bg-primary-subtle text-primary border border-primary-subtle"><i class="bi bi-gender-male me-1"></i>Male</span>
                                            <?php else: ?>
                                                <span class="badge bg-danger-subtle text-danger border border-danger-subtle"><i class="bi bi-gender-female me-1"></i>Female</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?= htmlspecialchars($r['colour'] ?: '—') ?></td>
                                        <td><span class="badge bg-secondary-subtle text-secondary"><?= htmlspecialchars($r['species'] ?: 'Cattle') ?></span></td>
                                        <td><?= htmlspecialchars($r['breed'] ?: '—') ?></td>
                                        <td class="font-monospace"><?= htmlspecialchars($r['age'] ?: '—') ?></td>
                                        <td><?= htmlspecialchars($r['purpose'] ?: '—') ?></td>
                                        <td><span class="badge bg-light text-dark border font-monospace"><?= htmlspecialchars($r['io_number'] ?: '—') ?></span></td>
                                        <td class="text-center">
                                            <!-- Strict Present Status Dropdown (Mandatory Requirement) -->
                                            <div class="input-group input-group-sm justify-content-center">
                                                <select class="form-select quick-status-select <?= $r['present_status'] === 'Sold or transferred' ? 'status-pill-sold' : ($r['present_status'] === 'Slaughter' ? 'status-pill-slaughter' : 'status-pill-died') ?>"
                                                        data-record-id="<?= $r['id'] ?>" 
                                                        onchange="updateAnimalStatus(<?= $r['id'] ?>, this.value, this)">
                                                    <option value="Sold or transferred" <?= $r['present_status'] === 'Sold or transferred' ? 'selected' : '' ?>>Sold or transferred</option>
                                                    <option value="Slaughter" <?= $r['present_status'] === 'Slaughter' ? 'selected' : '' ?>>Slaughter</option>
                                                    <option value="Died" <?= $r['present_status'] === 'Died' ? 'selected' : '' ?>>Died</option>
                                                </select>
                                            </div>
                                        </td>
                                        <td class="text-center text-nowrap">
                                            <div class="btn-group btn-group-sm">
                                                <button type="button" class="btn btn-outline-primary btn-edit-record" data-id="<?= $r['id'] ?>" title="Edit Record">
                                                    <i class="bi bi-pencil-square"></i>
                                                </button>
                                                <button type="button" class="btn btn-outline-success btn-print-card" data-id="<?= $r['id'] ?>" title="Print Voucher Card">
                                                    <i class="bi bi-printer"></i>
                                                </button>
                                                <button type="button" class="btn btn-outline-danger btn-delete-record" data-id="<?= $r['id'] ?>" data-tag="<?= htmlspecialchars($r['ear_tag_no']) ?>" title="Delete">
                                                    <i class="bi bi-trash"></i>
                                                </button>
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

        <!-- ============================================================ -->
        <!-- TAB 2 CONTENT: AUTOMATED REAL-TIME MONTHLY SUMMARY           -->
        <!-- ============================================================ -->
        <div class="tab-pane fade <?= $active_tab === 'summary' ? 'show active' : '' ?>" 
             id="tab-summary-content" 
             role="tabpanel" 
             aria-labelledby="tab-summary-btn">
            
            <div class="p-3 bg-light border-bottom d-flex justify-content-between align-items-center">
                <div>
                    <h6 class="mb-0 fw-bold text-dark"><i class="bi bi-calendar-check me-2 text-success"></i>Monthly Ear Tag Stock Returns &amp; Real-Time Balances - <?= $selected_year ?></h6>
                    <small class="text-muted">Dynamically aggregates stock, usage, and spoilage balances</small>
                </div>
                <div>
                    <button class="btn btn-sm btn-outline-dark fw-semibold" data-bs-toggle="modal" data-bs-target="#modalStockAdjustment">
                        <i class="bi bi-box-arrow-in-down me-1"></i>Log Stock Movement / Baseline
                    </button>
                </div>
            </div>

            <div class="p-3">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0" id="monthlySummaryTable" style="width: 100%;">
                        <thead class="table-light text-secondary small uppercase">
                            <tr>
                                <th style="width: 14%;" class="text-center">Month</th>
                                <th style="width: 12%;" class="text-end">Opening Balance</th>
                                <th style="width: 12%;" class="text-end">Received Quantity</th>
                                <th style="width: 12%;" class="text-end">Used Quantity</th>
                                <th style="width: 12%;" class="text-end">Spoiled Quantity</th>
                                <th style="width: 12%;" class="text-end">Transferred Amounts</th>
                                <th style="width: 14%;" class="text-end">Ending Balance</th>
                                <th style="width: 12%;" class="text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="small">
                            <?php foreach ($monthly_summary as $m_num => $m_data): ?>
                                <tr data-month="<?= $m_num ?>" 
                                    data-year="<?= $selected_year ?>"
                                    data-id="<?= $m_data['id'] ?>"
                                    data-opening="<?= $m_data['opening_balance'] ?>"
                                    data-received="<?= $m_data['received_qty'] ?>"
                                    data-transferred="<?= $m_data['transferred_qty'] ?>"
                                    class="<?= $m_data['has_entry'] ? '' : 'text-muted' ?>">
                                    
                                    <td class="text-center fw-bold text-dark">
                                        <?= $m_data['month_name'] ?>
                                    </td>
                                    <td class="text-end font-monospace">
                                        <?= number_format($m_data['opening_balance']) ?>
                                    </td>
                                    <td class="text-end font-monospace text-success">
                                        <?= $m_data['received_qty'] > 0 ? '+' . number_format($m_data['received_qty']) : '0' ?>
                                    </td>
                                    <td class="text-end font-monospace text-info fw-bold">
                                        <?= $m_data['used_qty'] > 0 ? '-' . number_format($m_data['used_qty']) : '0' ?>
                                    </td>
                                    <td class="text-end font-monospace text-warning fw-bold">
                                        <?= $m_data['spoilt_qty'] > 0 ? '-' . number_format($m_data['spoilt_qty']) : '0' ?>
                                    </td>
                                    <td class="text-end font-monospace text-secondary">
                                        <?= $m_data['transferred_qty'] > 0 ? '-' . number_format($m_data['transferred_qty']) : '0' ?>
                                    </td>
                                    <td class="text-end font-monospace fw-bold <?= $m_data['ending_balance'] < 0 ? 'text-danger' : 'text-dark' ?>">
                                        <?= number_format($m_data['ending_balance']) ?>
                                    </td>
                                    <td class="text-center">
                                        <button type="button" class="btn btn-sm btn-outline-secondary btn-edit-stock py-0 px-2" 
                                                data-month="<?= $m_num ?>" 
                                                data-monthname="<?= $m_data['month_name'] ?>"
                                                data-id="<?= $m_data['id'] ?>"
                                                data-opening="<?= $m_data['opening_balance'] ?>"
                                                data-received="<?= $m_data['received_qty'] ?>"
                                                data-transferred="<?= $m_data['transferred_qty'] ?>"
                                                title="Log / Edit Stock Details for <?= $m_data['month_name'] ?>">
                                            <i class="bi bi-sliders me-1"></i>Stock
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot class="table-light border-top border-2">
                            <tr class="fw-bold table-period-total">
                                <td class="text-center text-uppercase">Grand Total / Balance</td>
                                <td class="text-end font-monospace"><?= number_format($first_opening_balance) ?></td>
                                <td class="text-end font-monospace text-success">+<?= number_format($grand_total_received) ?></td>
                                <td class="text-end font-monospace text-info">-<?= number_format($grand_total_used) ?></td>
                                <td class="text-end font-monospace text-warning">-<?= number_format($grand_total_spoiled) ?></td>
                                <td class="text-end font-monospace text-secondary">-<?= number_format($grand_total_transferred) ?></td>
                                <td class="text-end font-monospace <?= $latest_inventory_balance < 0 ? 'text-danger' : 'text-dark' ?>">
                                    <?= number_format($latest_inventory_balance) ?>
                                </td>
                                <td class="text-center small text-muted">—</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ============================================================ -->
<!-- MODALS INCLUSION -->
<!-- ============================================================ -->
<?php include __DIR__ . '/model/modal_ear_tag_stock.php'; ?>
<?php include __DIR__ . '/model/modal_animal_identification.php'; ?>

<?php
$pageScripts = '
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
$(document).ready(function() {

    // Preserve and switch tab parameter in URL without page refresh
    $(\'#earTagTabs button[data-bs-toggle="pill"]\').on(\'shown.bs.tab\', function (e) {
        var target = $(e.target).attr("data-bs-target");
        var tabParam = "identification";
        if (target.indexOf("summary") !== -1) {
            tabParam = "summary";
        }
        var newUrl = new URL(window.location.href);
        newUrl.searchParams.set("tab", tabParam);
        window.history.replaceState(null, "", newUrl.toString());
        
        // Adjust column layouts for DataTables
        $.fn.dataTable.tables({ visible: true, api: true }).columns.adjust();
    });

    // Monthly Summary DataTable
    if ($.fn.DataTable.isDataTable("#monthlySummaryTable")) {
        $("#monthlySummaryTable").DataTable().destroy();
    }
    var sumTable = $("#monthlySummaryTable").DataTable({
        "ordering": false,
        "paging": false,
        "info": false,
        "searching": false,
        "dom": \'<"d-flex justify-content-end mb-2"B>t\',
        "buttons": [
            {
                extend: "csv",
                text: "<i class=\\"bi bi-file-earmark-spreadsheet\\"></i> CSV Summary",
                className: "btn btn-sm btn-success me-2"
            },
            {
                extend: "pdf",
                text: "<i class=\\"bi bi-file-pdf\\"></i> PDF Summary",
                className: "btn btn-sm btn-danger me-2"
            },
            {
                extend: "print",
                text: "<i class=\\"bi bi-printer\\"></i> Print Summary",
                className: "btn btn-sm btn-dark"
            }
        ]
    });

    // Alert query checks from redirect
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
        var cleanUrl = window.location.pathname + (urlParams.toString() ? "?" + urlParams.toString() : "");
        window.history.replaceState({}, document.title, cleanUrl);
    } else if (status === "error") {
        Swal.fire({
            icon: "error",
            title: "Operation Failed",
            text: msg ? msg : "Could not process database action.",
            confirmButtonColor: "#370709"
        });
        urlParams.delete("status");
        urlParams.delete("msg");
        var cleanUrl = window.location.pathname + (urlParams.toString() ? "?" + urlParams.toString() : "");
        window.history.replaceState({}, document.title, cleanUrl);
    }

    // MONTHLY SUMMARY: LOG / EDIT STOCK BUTTON
    $(document).on("click", ".btn-edit-stock", function() {
        var mId = $(this).data("id") || 0;
        var monthNum = $(this).data("month");
        var opening = $(this).data("opening") || 0;
        var received = $(this).data("received") || 0;
        var transferred = $(this).data("transferred") || 0;

        $("#stock_id").val(mId);
        $("#stock_year").val("' . $selected_year . '");
        $("#stock_month").val(monthNum);
        $("#stock_opening_balance").val(opening);
        $("#stock_received_qty").val(received);
        $("#stock_transferred_qty").val(transferred);

        var mEl = document.getElementById("modalStockAdjustment");
        if (mEl) {
            bootstrap.Modal.getOrCreateInstance(mEl).show();
        }
    });

});
</script>
';

ob_start();
?>
<script>
let gridRowIndex = 0;
let currentRangeCode = <?= json_encode($range_code ?? '01') ?>;

// Function: Concatenate Ear Tag Number
function computeEarTagNumber(rangeCode, farmRegNo, seqNo) {
    const cleanRange = (rangeCode || "01").trim();
    const cleanReg = (farmRegNo || "FARM").trim().replace(/[^A-Za-z0-9_-]/g, "");
    const cleanSeq = String(seqNo || "001").trim();
    return `${cleanRange}-${cleanReg}-${cleanSeq}`;
}

// Function: Update all rows in modal grid
function refreshGridConcatenations() {
    const rangeCode = $("#ai_range_code").val().trim() || "01";
    const farmReg = $("#ai_farm_reg_no").val().trim() || "FARM";

    $("#batchGridBody tr.grid-animal-row").each(function(idx) {
        const seqVal = $(this).find(".input-seq").val().trim() || String(idx + 1).padStart(3, "0");
        const computedTag = computeEarTagNumber(rangeCode, farmReg, seqVal);
        $(this).find(".input-tag").val(computedTag);
    });

    const firstTag = $("#batchGridBody tr.grid-animal-row:first .input-tag").val();
    if (firstTag) {
        $("#previewTagText").text(firstTag);
    }
}

// Function: Recalculate row numbering & badges
function updateGridRowMetadata() {
    const rows = $("#batchGridBody tr.grid-animal-row");
    rows.each(function(idx) {
        $(this).find(".row-idx").text(idx + 1);
        if (rows.length > 1) {
            $(this).find(".btn-del-row").prop("disabled", false);
        } else {
            $(this).find(".btn-del-row").prop("disabled", true);
        }
    });
    $("#animalsCountBadge").text(`${rows.length} Animal Entry${rows.length > 1 ? "ies" : ""}`);
}

// Function: Fetch next sequence number for selected farm
function fetchNextSequenceForFarm(farmRegNo) {
    if (!farmRegNo) return;
    const rangeCode = $("#ai_range_code").val();
    const rangeId = $("#ai_range_id").val();

    $.ajax({
        url: "processors/animal_identification_crud.php?action=get_next_seq",
        type: "GET",
        data: { farm_reg_no: farmRegNo, range_code: rangeCode, range_id: rangeId },
        dataType: "json",
        success: function(res) {
            if (res.success) {
                let startSeq = parseInt(res.next_seq_num) || 1;
                $("#batchGridBody tr.grid-animal-row").each(function(i) {
                    const nextVal = String(startSeq + i).padStart(3, "0");
                    $(this).find(".input-seq").val(nextVal);
                });
                refreshGridConcatenations();
            }
        }
    });
}

// Quick status updater directly from table
function updateAnimalStatus(recordId, newStatus, selectEl) {
    if (!recordId || !newStatus) return;

    Swal.fire({
        title: "Update Present Status?",
        text: `Change animal status to "${newStatus}"?`,
        icon: "question",
        showCancelButton: true,
        confirmButtonColor: "#820100",
        confirmButtonText: "Yes, Update",
        cancelButtonText: "Cancel"
    }).then((result) => {
        if (result.isConfirmed) {
            $.ajax({
                url: "processors/animal_identification_crud.php?action=update_status",
                type: "POST",
                data: { id: recordId, status: newStatus },
                dataType: "json",
                success: function(resp) {
                    if (resp.success) {
                        Swal.fire({
                            icon: "success",
                            title: "Status Updated",
                            text: resp.message,
                            timer: 1500,
                            showConfirmButton: false
                        });
                        $(selectEl).removeClass("status-pill-sold status-pill-slaughter status-pill-died");
                        if (newStatus === "Sold or transferred") $(selectEl).addClass("status-pill-sold");
                        else if (newStatus === "Slaughter") $(selectEl).addClass("status-pill-slaughter");
                        else if (newStatus === "Died") $(selectEl).addClass("status-pill-died");
                    } else {
                        Swal.fire("Update Failed", resp.message || "Could not update status.", "error");
                    }
                },
                error: function() {
                    Swal.fire("Error", "Server communication error.", "error");
                }
            });
        } else {
            location.reload();
        }
    });
}

$(document).ready(function() {

    // Initialize Animal Identification DataTable
    if ($.fn.DataTable.isDataTable("#animalIdentificationTable")) {
        $("#animalIdentificationTable").DataTable().destroy();
    }
    const aiTable = $("#animalIdentificationTable").DataTable({
        pageLength: 15,
        lengthMenu: [10, 15, 25, 50, 100],
        order: [[7, "desc"]],
        responsive: false,
        buttons: [
            {
                extend: "csvHtml5",
                className: "d-none",
                filename: function() {
                    return "Animal_Identification_Records_" + new Date().toISOString().slice(0, 10);
                },
                title: "Animal Identification Register - Cattle Vouchers & Ear Tagging",
                exportOptions: {
                    columns: ":visible:not(:first-child):not(:last-child)"
                }
            },
            {
                extend: "pdfHtml5",
                className: "d-none",
                orientation: "landscape",
                pageSize: "A4",
                filename: function() {
                    return "Animal_Identification_Records_" + new Date().toISOString().slice(0, 10);
                },
                title: "Animal Identification Register - Cattle Vouchers & Ear Tagging",
                exportOptions: {
                    columns: ":visible:not(:first-child):not(:last-child)"
                },
                customize: function (doc) {
                    doc.defaultStyle.fontSize = 8;
                    if (doc.styles && doc.styles.tableHeader) {
                        doc.styles.tableHeader.fontSize = 9;
                        doc.styles.tableHeader.fillColor = "#370709";
                        doc.styles.tableHeader.color = "#ffffff";
                    }
                }
            }
        ],
        language: {
            search: "",
            searchPlaceholder: "Search identified animals...",
            emptyTable: "No records found in this view."
        }
    });
    window.aiTableInstance = aiTable;

    // Dynamic Record Count sync
    aiTable.on("draw.dt init.dt", function() {
        const info = aiTable.page.info();
        const count = info.recordsDisplay;
        const total = info.recordsTotal;
        const badgeText = $("#animalIdentificationRecordCountText");
        if (badgeText.length) {
            badgeText.text(count === total ? `${count} Identified Animals` : `${count} of ${total} Identified Animals`);
        }
    });

    window.exportAnimalIdentificationCSV = function() {
        if (window.aiTableInstance && typeof window.aiTableInstance.button === "function") {
            window.aiTableInstance.button(".buttons-csv").trigger();
        }
    };

    window.exportAnimalIdentificationPDF = function() {
        if (window.aiTableInstance && typeof window.aiTableInstance.button === "function") {
            window.aiTableInstance.button(".buttons-pdf").trigger();
        }
    };

    window.sortAnimalIdentification = function(colIndex, dir) {
        if (window.aiTableInstance) {
            window.aiTableInstance.order([colIndex, dir]).draw();
        }
    };

    // Reactive Concatenation Triggers
    $(document).on("input change", "#ai_range_code, #ai_farm_reg_no, .input-seq", function() {
        refreshGridConcatenations();
    });

    // Auto-prefix voucher serial number with A-
    $(document).on("blur", "input[name*='[voucher_serial_no]'], .input-voucher", function() {
        let val = $(this).val().trim();
        if (!val) return;
        if (/^cv[-_]?/i.test(val)) {
            val = val.replace(/^cv[-_]?/i, "A-");
        } else if (!/^A[-_]/i.test(val)) {
            val = "A-" + val.replace(/^A/i, "").replace(/^[-_]+/, "");
        } else {
            val = "A-" + val.substring(2);
        }
        $(this).val(val.toUpperCase());
    });

    // Add row to grid
    function addGridRow() {
        gridRowIndex++;
        const currentCount = $("#batchGridBody tr.grid-animal-row").length;
        const lastSeq = parseInt($("#batchGridBody tr.grid-animal-row:last .input-seq").val()) || currentCount;
        const nextSeq = String(lastSeq + 1).padStart(3, "0");
        const suggestedVoucher = "A-" + Math.floor(100000 + Math.random() * 900000);

        const tr = `
            <tr class="grid-animal-row" data-row="${gridRowIndex}">
                <td class="text-center text-muted fw-bold row-idx">${currentCount + 1}</td>
                <td>
                    <input type="text" name="animals[${gridRowIndex}][animal_seq_no]" class="form-control form-control-sm font-monospace text-center input-seq" value="${nextSeq}" style="max-width: 70px;" required>
                </td>
                <td>
                    <input type="text" name="animals[${gridRowIndex}][ear_tag_no]" class="form-control form-control-sm font-monospace text-uppercase fw-bold text-primary input-tag bg-light" readonly required>
                </td>
                <td>
                    <input type="text" name="animals[${gridRowIndex}][voucher_serial_no]" class="form-control form-control-sm font-monospace text-uppercase input-voucher" value="${suggestedVoucher}" placeholder="e.g. A-918059" required>
                </td>
                <td>
                    <select name="animals[${gridRowIndex}][gender]" class="form-select form-select-sm">
                        <option value="Female" selected>Female</option>
                        <option value="Male">Male</option>
                    </select>
                </td>
                <td>
                    <input type="text" name="animals[${gridRowIndex}][colour]" class="form-control form-control-sm" placeholder="e.g. Brown">
                </td>
                <td>
                    <select name="animals[${gridRowIndex}][species]" class="form-select form-select-sm">
                        <option value="Cattle" selected>Cattle</option>
                        <option value="Buffalo">Buffalo</option>
                        <option value="Goat">Goat</option>
                        <option value="Sheep">Sheep</option>
                    </select>
                </td>
                <td>
                    <input type="text" name="animals[${gridRowIndex}][breed]" class="form-control form-control-sm" placeholder="e.g. Jersey" list="breedPresets">
                </td>
                <td>
                    <input type="text" name="animals[${gridRowIndex}][age]" class="form-control form-control-sm" placeholder="e.g. 2 Yrs">
                </td>
                <td>
                    <input type="text" name="animals[${gridRowIndex}][purpose]" class="form-control form-control-sm" value="Dairy Production">
                </td>
                <td class="table-warning">
                    <select name="animals[${gridRowIndex}][present_status]" class="form-select form-select-sm fw-bold">
                        <option value="Sold or transferred" selected>Sold or transferred</option>
                        <option value="Slaughter">Slaughter</option>
                        <option value="Died">Died</option>
                    </select>
                </td>
                <td class="text-center">
                    <button type="button" class="btn btn-sm btn-outline-danger py-0 px-1 btn-del-row" title="Remove">
                        <i class="bi bi-trash"></i>
                    </button>
                </td>
            </tr>
        `;
        $("#batchGridBody").append(tr);
        updateGridRowMetadata();
        refreshGridConcatenations();
    }

    $("#btnAddGridRow, #btnAddGridRowBottom").on("click", function() {
        addGridRow();
    });

    // Delete row
    $(document).on("click", ".btn-del-row", function() {
        if ($("#batchGridBody tr.grid-animal-row").length > 1) {
            $(this).closest("tr").remove();
            updateGridRowMetadata();
            refreshGridConcatenations();
        }
    });

    // Auto-complete search registered farmers
    let farmerTimer = null;
    $("#ai_farmer_name").on("input", function() {
        const val = $(this).val().trim();
        if (val.length < 2) return;

        clearTimeout(farmerTimer);
        farmerTimer = setTimeout(function() {
            $.ajax({
                url: "processors/animal_identification_crud.php?action=search_farmers",
                type: "GET",
                data: { term: val, range_id: $("#ai_range_id").val() },
                dataType: "json",
                success: function(resp) {
                    if (resp.success && resp.farmers) {
                        const dl = $("#registeredFarmersList").empty();
                        resp.farmers.forEach(function(f) {
                            dl.append(`<option value="${f.farmer_name}" data-reg="${f.farm_reg_no}" data-dist="${f.district}" data-range="${f.vs_range}">Reg: ${f.farm_reg_no} | ${f.vs_range}</option>`);
                        });
                    }
                }
            });
        }, 300);
    });

    // When farmer is picked, auto-populate farm reg no and fetch next sequence
    $("#ai_farmer_name").on("change", function() {
        const selectedVal = $(this).val();
        const opt = $(`#registeredFarmersList option[value="${selectedVal}"]`);
        if (opt.length) {
            const regNo = opt.data("reg");
            if (regNo) {
                $("#ai_farm_reg_no").val(regNo);
                fetchNextSequenceForFarm(regNo);
            }
        } else if ($("#ai_farm_reg_no").val()) {
            fetchNextSequenceForFarm($("#ai_farm_reg_no").val());
        }
    });

    $("#ai_farm_reg_no").on("blur", function() {
        fetchNextSequenceForFarm($(this).val());
    });

    // Prepare Modal Form & State (Single vs Batch)
    window.prepareAnimalIdentificationModal = function(isBatch) {
        $("#ai_edit_id").val("0");
        $("#modalAddAnimalIdentificationLabel").text(
            isBatch 
                ? "Batch Animal Identification & Cattle Voucher Entry" 
                : "Animal Identification Entry (Ear Tagging & Cattle Voucher)"
        );
        const form = document.getElementById("formAddAnimalIdentification");
        if (form) form.reset();
        $("#ai_edit_id").val("0");
        $("#ai_range_code").val(currentRangeCode || "01");
        $("#ai_date").val(new Date().toISOString().split("T")[0]);
        $("#batchGridBody tr.grid-animal-row:gt(0)").remove();

        // Auto-generate suggested voucher serial starting with A- if empty
        const firstVoucher = $("#batchGridBody tr:first input[name*='[voucher_serial_no]']");
        if (firstVoucher.length) {
            firstVoucher.val("A-" + Math.floor(100000 + Math.random() * 900000));
        }

        if (isBatch && $("#batchGridBody tr.grid-animal-row").length === 1) {
            addGridRow();
            addGridRow();
        }

        updateGridRowMetadata();
        refreshGridConcatenations();
    };

    // Bootstrap show.bs.modal event listener (Reliable native trigger)
    const modalAiEl = document.getElementById("modalAddAnimalIdentification");
    if (modalAiEl) {
        modalAiEl.addEventListener("show.bs.modal", function(event) {
            const trigger = event.relatedTarget;
            // Only prepare fresh form if opening in new record mode (not edit mode)
            if ($("#ai_edit_id").val() === "0" || !$("#ai_edit_id").val()) {
                const isBatch = trigger ? (trigger.getAttribute("data-mode") === "batch" || trigger.id === "btnOpenBatchModal") : false;
                prepareAnimalIdentificationModal(isBatch);
            }
        });
    }

    // Programmatic Open Function (Backwards compatible fallback)
    window.openAnimalIdentificationModal = function(isBatch) {
        prepareAnimalIdentificationModal(isBatch);
        const modalEl = document.getElementById("modalAddAnimalIdentification");
        if (modalEl) {
            bootstrap.Modal.getOrCreateInstance(modalEl).show();
        }
    };

    // Submit form (Save / Batch Save)
    window.submitAnimalIdentificationForm = function(e) {
        if (e && typeof e.preventDefault === "function") {
            e.preventDefault();
        }
        const form = $("#formAddAnimalIdentification");
        if (!form.length) return;

        // Normalize all voucher serials to have A- prefix
        $("#batchGridBody tr.grid-animal-row").each(function() {
            const vInput = $(this).find("input[name*='[voucher_serial_no]']");
            let vVal = vInput.val().trim();
            if (vVal) {
                if (/^cv[-_]?/i.test(vVal)) {
                    vVal = vVal.replace(/^cv[-_]?/i, "A-");
                } else if (!/^A[-_]/i.test(vVal)) {
                    vVal = "A-" + vVal.replace(/^A/i, "").replace(/^[-_]+/, "");
                } else {
                    vVal = "A-" + vVal.substring(2);
                }
                vInput.val(vVal.toUpperCase());
            }
        });

        refreshGridConcatenations();

        const btn = $("#btnSubmitIdentification");
        const origHtml = btn.html();
        btn.prop("disabled", true).html(`<span class="spinner-border spinner-border-sm me-1"></span> Saving...`);

        $.ajax({
            url: "processors/animal_identification_crud.php",
            type: "POST",
            data: form.serialize(),
            dataType: "json",
            success: function(resp) {
                btn.prop("disabled", false).html(origHtml);
                if (resp.success) {
                    Swal.fire({
                        icon: "success",
                        title: "Records Saved",
                        text: resp.message,
                        timer: 2000,
                        showConfirmButton: false
                    }).then(() => {
                        window.location.href = "ear_tag_balance.php?tab=identification";
                    });
                } else {
                    Swal.fire("Save Error", resp.message || "Failed to record entries.", "error");
                }
            },
            error: function(xhr, status, error) {
                btn.prop("disabled", false).html(origHtml);
                Swal.fire("Server Error", "Could not communicate with database: " + error, "error");
            }
        });
    };

    $(document).on("submit", "#formAddAnimalIdentification", function(e) {
        submitAnimalIdentificationForm(e);
    });

    // Edit Record Handler
    $(document).on("click", ".btn-edit-record", function() {
        const id = $(this).data("id");
        if (!id) return;

        Swal.fire({ title: "Loading...", allowOutsideClick: false, didOpen: () => Swal.showLoading() });

        $.ajax({
            url: "processors/animal_identification_crud.php?action=get_record",
            type: "GET",
            data: { id: id },
            dataType: "json",
            success: function(resp) {
                Swal.close();
                if (!resp.success || !resp.record) {
                    Swal.fire("Error", "Record could not be loaded.", "error");
                    return;
                }
                const r = resp.record;
                $("#ai_edit_id").val(r.id);
                $("#modalAddAnimalIdentificationLabel").text("Edit Animal Identification Record: " + r.ear_tag_no);
                $("#ai_district").val(r.district);
                $("#ai_vs_range").val(r.vs_range);
                $("#ai_range_code").val(r.range_code || currentRangeCode);
                $("#ai_farmer_name").val(r.farmer_name);
                $("#ai_farm_reg_no").val(r.farm_reg_no);
                $("#ai_date").val(r.identification_date);
                $("#ai_io_number").val(r.io_number || "");

                // Reset grid to single row populated with record data
                $("#batchGridBody tr.grid-animal-row:gt(0)").remove();
                const row = $("#batchGridBody tr.grid-animal-row:first");
                row.find(".input-seq").val(r.animal_seq_no || "001");
                row.find(".input-tag").val(r.ear_tag_no);
                row.find("input[name*=\"[voucher_serial_no]\"]").val(r.voucher_serial_no);
                row.find("select[name*=\"[gender]\"]").val(r.gender);
                row.find("input[name*=\"[colour]\"]").val(r.colour);
                row.find("select[name*=\"[species]\"]").val(r.species);
                row.find("input[name*=\"[breed]\"]").val(r.breed);
                row.find("input[name*=\"[age]\"]").val(r.age);
                row.find("input[name*=\"[purpose]\"]").val(r.purpose);
                row.find("select[name*=\"[present_status]\"]").val(r.present_status);

                updateGridRowMetadata();
                refreshGridConcatenations();

                const modalEl = document.getElementById("modalAddAnimalIdentification");
                bootstrap.Modal.getOrCreateInstance(modalEl).show();
            },
            error: function() {
                Swal.close();
                Swal.fire("Error", "Server error while fetching record.", "error");
            }
        });
    });

    // Delete Record Handler
    $(document).on("click", ".btn-delete-record", function() {
        const id = $(this).data("id");
        const tag = $(this).data("tag");

        Swal.fire({
            title: "Delete Record?",
            text: `Permanently delete Ear Tag ${tag} from the registry?`,
            icon: "warning",
            showCancelButton: true,
            confirmButtonColor: "#d33",
            confirmButtonText: "Yes, Delete"
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: "processors/animal_identification_crud.php?action=delete",
                    type: "POST",
                    data: { id: id },
                    dataType: "json",
                    success: function(resp) {
                        if (resp.success) {
                            Swal.fire({ icon: "success", title: "Deleted", text: resp.message, timer: 1500, showConfirmButton: false })
                                .then(() => {
                                    window.location.href = "ear_tag_balance.php?tab=identification";
                                });
                        } else {
                            Swal.fire("Failed", resp.message || "Could not delete.", "error");
                        }
                    }
                });
            }
        });
    });

    // Printable Card Preview
    $(document).on("click", ".btn-print-card", function() {
        const id = $(this).data("id");
        $.ajax({
            url: "processors/animal_identification_crud.php?action=get_record",
            type: "GET",
            data: { id: id },
            dataType: "json",
            success: function(resp) {
                if (resp.success && resp.record) {
                    const r = resp.record;
                    $("#cardEarTag").text(r.ear_tag_no);
                    $("#cardVoucherNo").text(r.voucher_serial_no);
                    $("#cardDate").text(r.identification_date);
                    $("#cardFarmer").text(r.farmer_name);
                    $("#cardFarmReg").text(r.farm_reg_no);
                    $("#cardRange").text(`${r.vs_range} / ${r.district}`);
                    $("#cardBreed").text(`${r.species} (${r.breed || "Standard"})`);
                    $("#cardGender").text(r.gender);
                    $("#cardColour").text(r.colour || "—");
                    $("#cardAge").text(r.age || "—");
                    $("#cardPurpose").text(r.purpose || "—");
                    $("#cardIO").text(r.io_number || "—");
                    $("#cardStatus").text(r.present_status);

                    const modalEl = document.getElementById("modalPrintCard");
                    bootstrap.Modal.getOrCreateInstance(modalEl).show();
                }
            }
        });
    });

    // Initialize initial preview
    refreshGridConcatenations();
});
</script>
<?php
$pageScripts .= ob_get_clean();

require_once '../../../includes/footer.php';
?>
