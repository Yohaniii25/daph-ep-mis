<?php
session_start();
require_once __DIR__ . '/../../../config/db_connect.php';

/** @var mysqli $mysqli */
global $mysqli;

// 1. Authentication & Role Guard
if (!isset($_SESSION['logged_in']) || $_SESSION['role'] !== 'veterinary_surgeon') {
    header("Location: ../../../index.php");
    exit();
}

$user_id  = $_SESSION['user_id'] ?? null;
$range_id = $_SESSION['range_id'] ?? null;

// Resolve Range ID from User Profile if missing in session
if (empty($range_id) && !empty($user_id)) {
    $user_query = $mysqli->prepare("SELECT range_id FROM users WHERE id = ?");
    if ($user_query) {
        $user_query->bind_param("i", $user_id);
        $user_query->execute();
        $user_result = $user_query->get_result();
        if ($row = $user_result->fetch_assoc()) {
            $_SESSION['range_id'] = $row['range_id'];
            $range_id = $row['range_id'];
        }
        $user_query->close();
    }
}

// 2. Fetch Officer Details & Designation
$officer_name        = $_SESSION['full_name'] ?? 'Veterinary Surgeon';
$officer_designation = 'Government Veterinary Surgeon';
$ministry_department = 'Ministry of Agriculture, Animal Production & Development / Department of Animal Production & Health (Eastern Province)';

if ($user_id) {
    $stmt = $mysqli->prepare("SELECT full_name, designation, role FROM users WHERE id = ?");
    if ($stmt) {
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $u_res = $stmt->get_result();
        if ($u = $u_res->fetch_assoc()) {
            if (!empty($u['full_name'])) {
                $officer_name = $u['full_name'];
            }
            if (!empty($u['designation'])) {
                $officer_designation = $u['designation'];
            } elseif (!empty($u['role'])) {
                $officer_designation = ucwords(str_replace('_', ' ', $u['role']));
            }
        }
        $stmt->close();
    }
}

// 3. Fetch Range & District Information
$range_name    = 'Your Assigned Range';
$district_name = 'Your District';

if (!empty($range_id)) {
    $details_sql = "
        SELECT vr.name AS range_name, d.name AS district_name
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
            $range_name    = $data['range_name'] ?? 'Your Assigned Range';
            $district_name = $data['district_name'] ?? 'Your District';
        }
        $details_query->close();
    }
}

// 4. Custom Date Range Parameters & Sanitization
$selected_year = isset($_GET['year']) ? intval($_GET['year']) : intval(date('Y'));
$from_month    = isset($_GET['from_month']) ? intval($_GET['from_month']) : 1;
$to_month      = isset($_GET['to_month']) ? intval($_GET['to_month']) : 3; // Default Q1 (Jan–Mar) or current month
$active_tab    = isset($_GET['tab']) ? preg_replace('/[^a-zA-Z0-9_\-]/', '', $_GET['tab']) : 'tab-consultations';

if ($from_month < 1 || $from_month > 12) $from_month = 1;
if ($to_month < 1 || $to_month > 12)     $to_month = 12;
if ($to_month < $from_month)             $to_month = $from_month;

$month_names = [
    1 => 'January', 2 => 'February', 3 => 'March', 4 => 'April',
    5 => 'May', 6 => 'June', 7 => 'July', 8 => 'August',
    9 => 'September', 10 => 'October', 11 => 'November', 12 => 'December'
];

$month_shorts = [
    1 => 'Jan', 2 => 'Feb', 3 => 'Mar', 4 => 'Apr',
    5 => 'May', 6 => 'Jun', 7 => 'Jul', 8 => 'Aug',
    9 => 'Sep', 10 => 'Oct', 11 => 'Nov', 12 => 'Dec'
];

$range_label = ($from_month === $to_month)
    ? $month_names[$from_month] . ' ' . $selected_year
    : $month_names[$from_month] . ' – ' . $month_names[$to_month] . ' ' . $selected_year;

// 5. Categorized Data Structure (Mapping for the 4 Required Tabs)
$tab_categories = [
    'tab-consultations' => [
        'title'       => 'Consultations & Certificates',
        'short_title' => 'Consultations',
        'icon'        => 'bi-clipboard2-pulse',
        'badge_class' => 'bg-primary text-white',
        'items'       => [
            "Consultation fee for cross breed Pet's",
            "Consultation fee for pure breed dog",
            "Health certificate"
        ]
    ],
    'tab-poultry' => [
        'title'       => 'Poultry Sales & Semen',
        'short_title' => 'Poultry & Semen',
        'icon'        => 'bi-egg-fried',
        'badge_class' => 'bg-warning text-dark',
        'items'       => [
            "Day old unsexed backyard chicks",
            "Semen straws for AI services",
            "Day Old Cockerels"
        ]
    ],
    'tab-treatments' => [
        'title'       => 'Vaccines, Surgeries & Treatments',
        'short_title' => 'Treatments & Surgeries',
        'icon'        => 'bi-heart-pulse',
        'badge_class' => 'bg-success text-white',
        'items'       => [
            "Wound ress (Wound dress)",
            "Ranikhet 1st dose & 2nd Dose",
            "OHE - Dog",
            "OHE -Cat"
        ]
    ],
    'tab-post-mortems' => [
        'title'       => 'Post Mortems',
        'short_title' => 'Post Mortems',
        'icon'        => 'bi-clipboard-x',
        'badge_class' => 'bg-danger text-white',
        'items'       => [
            "Poultry -Bird post mortems",
            "Post moturm Rabbit"
        ]
    ]
];

// Helper to normalize and map database item names to canonical tab items
function map_to_canonical_item($db_item_name, $tab_categories) {
    $clean = mb_strtolower(trim($db_item_name));
    
    // Alias mapping dictionary
    $aliases = [
        'wound ress'                   => ['tab-treatments', 'Wound ress (Wound dress)'],
        'wound dress'                  => ['tab-treatments', 'Wound ress (Wound dress)'],
        'wound ress (wound dress)'     => ['tab-treatments', 'Wound ress (Wound dress)'],
        'ranikhet 1st dose & 2nd dose' => ['tab-treatments', 'Ranikhet 1st dose & 2nd Dose'],
        'ranikhet 1st dose and 2nd dose'=>['tab-treatments', 'Ranikhet 1st dose & 2nd Dose'],
        'ohe - dog'                    => ['tab-treatments', 'OHE - Dog'],
        'ohe -dog'                     => ['tab-treatments', 'OHE - Dog'],
        'ohe dog'                      => ['tab-treatments', 'OHE - Dog'],
        'ohe -cat'                     => ['tab-treatments', 'OHE -Cat'],
        'ohe - cat'                    => ['tab-treatments', 'OHE -Cat'],
        'ohe cat'                      => ['tab-treatments', 'OHE -Cat'],
        
        "consultation fee for cross breed pet's" => ['tab-consultations', "Consultation fee for cross breed Pet's"],
        "consultation fee for cross breed pets"  => ['tab-consultations', "Consultation fee for cross breed Pet's"],
        "consultation fee for pure breed dog"    => ['tab-consultations', "Consultation fee for pure breed dog"],
        "health certificate"                     => ['tab-consultations', "Health certificate"],

        "day old unsexed backyard chicks" => ['tab-poultry', "Day old unsexed backyard chicks"],
        "semen straws for ai services"    => ['tab-poultry', "Semen straws for AI services"],
        "day old cockerels"               => ['tab-poultry', "Day Old Cockerels"],

        "poultry -bird post mortems"      => ['tab-post-mortems', "Poultry -Bird post mortems"],
        "poultry - bird post mortems"     => ['tab-post-mortems', "Poultry -Bird post mortems"],
        "post moturm rabbit"              => ['tab-post-mortems', "Post moturm Rabbit"],
        "post mortem rabbit"              => ['tab-post-mortems', "Post moturm Rabbit"]
    ];

    if (isset($aliases[$clean])) {
        return $aliases[$clean];
    }

    // Direct check against defined items
    foreach ($tab_categories as $t_key => $t_info) {
        foreach ($t_info['items'] as $item) {
            if ($clean === mb_strtolower(trim($item))) {
                return [$t_key, $item];
            }
        }
    }

    // Fallback: If not in the standard 4 tabs, categorize under 'tab-other'
    return ['tab-other', trim($db_item_name)];
}

// 6. Fetch and Aggregate Cash Book Records in Selected Date Range
$raw_records = [];
if (!empty($range_id)) {
    $sql = "
        SELECT id, district_id, range_id, report_year, report_month, item_name, 
               quantity_sold, unit_price, total_amount, amount_deposited, created_at
        FROM cash_book_summaries
        WHERE range_id = ?
          AND report_year = ?
          AND report_month >= ? AND report_month <= ?
        ORDER BY report_month ASC, id ASC
    ";
    $stmt = $mysqli->prepare($sql);
    if ($stmt) {
        $stmt->bind_param("iiii", $range_id, $selected_year, $from_month, $to_month);
        $stmt->execute();
        $res = $stmt->get_result();
        while ($r = $res->fetch_assoc()) {
            $raw_records[] = $r;
        }
        $stmt->close();
    }
}

// Initialize aggregation data structure
$aggregated_data = []; // [tab_key][canonical_item] => ['quantity_sold' => X, 'unit_price' => Y, 'total_amount' => Z, 'amount_deposited' => W, 'entries' => []]
$other_items = [];     // Any custom items outside standard 4 tabs

foreach ($tab_categories as $t_key => $t_info) {
    $aggregated_data[$t_key] = [];
    foreach ($t_info['items'] as $item) {
        $aggregated_data[$t_key][$item] = [
            'item_name'        => $item,
            'quantity_sold'    => 0,
            'unit_price'       => 0.00,
            'total_amount'     => 0.00,
            'amount_deposited' => 0.00,
            'entries'          => []
        ];
    }
}

// Aggregate records
foreach ($raw_records as $rec) {
    list($tab_key, $canonical_name) = map_to_canonical_item($rec['item_name'], $tab_categories);

    if ($tab_key === 'tab-other') {
        if (!isset($other_items[$canonical_name])) {
            $other_items[$canonical_name] = [
                'item_name'        => $canonical_name,
                'quantity_sold'    => 0,
                'unit_price'       => floatval($rec['unit_price']),
                'total_amount'     => 0.00,
                'amount_deposited' => 0.00,
                'entries'          => []
            ];
        }
        $other_items[$canonical_name]['quantity_sold']    += intval($rec['quantity_sold']);
        $other_items[$canonical_name]['total_amount']     += floatval($rec['total_amount']);
        $other_items[$canonical_name]['amount_deposited'] += floatval($rec['amount_deposited']);
        $other_items[$canonical_name]['unit_price']        = floatval($rec['unit_price']);
        $other_items[$canonical_name]['entries'][]         = $rec;
    } else {
        if (!isset($aggregated_data[$tab_key][$canonical_name])) {
            $aggregated_data[$tab_key][$canonical_name] = [
                'item_name'        => $canonical_name,
                'quantity_sold'    => 0,
                'unit_price'       => floatval($rec['unit_price']),
                'total_amount'     => 0.00,
                'amount_deposited' => 0.00,
                'entries'          => []
            ];
        }
        $aggregated_data[$tab_key][$canonical_name]['quantity_sold']    += intval($rec['quantity_sold']);
        $aggregated_data[$tab_key][$canonical_name]['total_amount']     += floatval($rec['total_amount']);
        $aggregated_data[$tab_key][$canonical_name]['amount_deposited'] += floatval($rec['amount_deposited']);
        // Keep latest unit price for display
        $aggregated_data[$tab_key][$canonical_name]['unit_price']        = floatval($rec['unit_price']);
        $aggregated_data[$tab_key][$canonical_name]['entries'][]         = $rec;
    }
}

// Compute Tab Subtotals & Master Grand Totals
$tab_subtotals = [];
$master_grand_total = [
    'quantity_sold'    => 0,
    'total_amount'     => 0.00,
    'amount_deposited' => 0.00
];

foreach ($tab_categories as $t_key => $t_info) {
    $tab_subtotals[$t_key] = [
        'quantity_sold'    => 0,
        'total_amount'     => 0.00,
        'amount_deposited' => 0.00,
        'item_count'       => count($t_info['items']),
        'recorded_count'   => 0
    ];

    foreach ($aggregated_data[$t_key] as $i_name => $data) {
        $tab_subtotals[$t_key]['quantity_sold']    += $data['quantity_sold'];
        $tab_subtotals[$t_key]['total_amount']     += $data['total_amount'];
        $tab_subtotals[$t_key]['amount_deposited'] += $data['amount_deposited'];
        if (!empty($data['entries'])) {
            $tab_subtotals[$t_key]['recorded_count']++;
        }
    }

    $master_grand_total['quantity_sold']    += $tab_subtotals[$t_key]['quantity_sold'];
    $master_grand_total['total_amount']     += $tab_subtotals[$t_key]['total_amount'];
    $master_grand_total['amount_deposited'] += $tab_subtotals[$t_key]['amount_deposited'];
}

// Add other items into subtotals & grand totals if present
$other_subtotal = [
    'quantity_sold'    => 0,
    'total_amount'     => 0.00,
    'amount_deposited' => 0.00,
    'item_count'       => count($other_items)
];
foreach ($other_items as $o_name => $o_data) {
    $other_subtotal['quantity_sold']    += $o_data['quantity_sold'];
    $other_subtotal['total_amount']     += $o_data['total_amount'];
    $other_subtotal['amount_deposited'] += $o_data['amount_deposited'];

    $master_grand_total['quantity_sold']    += $o_data['quantity_sold'];
    $master_grand_total['total_amount']     += $o_data['total_amount'];
    $master_grand_total['amount_deposited'] += $o_data['amount_deposited'];
}

$grand_variance = $master_grand_total['total_amount'] - $master_grand_total['amount_deposited'];

require_once '../../../includes/header.php';
?>

<link rel="stylesheet" href="../../../assets/css/bootstrap-icons.min.css">
<link rel="stylesheet" href="../../../assets/css/dataTables.bootstrap5.min.css">
<link rel="stylesheet" href="../../../assets/css/buttons.bootstrap5.min.css">
<link rel="stylesheet" href="../../../assets/css/sweetalert2.min.css">
<link rel="stylesheet" href="../../../assets/css/veterinary.css">

<style>
.tabs-scroller {
    overflow-x: auto;
    white-space: nowrap;
}
.tabs-scroller::-webkit-scrollbar {
    height: 4px;
}
.tabs-scroller::-webkit-scrollbar-thumb {
    background-color: #cbd5e1;
    border-radius: 4px;
}
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
.cat-badge-circle {
    width: 24px;
    height: 24px;
    border-radius: 50%;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    background-color: #820100;
    color: #ffffff;
    font-size: 0.78rem;
    font-weight: 700;
}
.nav-pills-letter-h .nav-link.active .cat-badge-circle {
    background-color: #ffffff;
    color: #820100;
}
.period-badge-pill {
    background: linear-gradient(135deg, #820100 0%, #370709 100%);
    color: #ffffff;
    border-radius: 20px;
    padding: 0.35rem 0.9rem;
    font-size: 0.85rem;
    font-weight: 600;
    letter-spacing: 0.5px;
}
.stat-pill {
    background: #ffffff;
    border-radius: 12px;
    padding: 1.1rem 1.25rem;
    border: 1px solid #e9ecef;
    box-shadow: 0 2px 8px rgba(0,0,0,0.03);
    transition: all 0.2s ease;
}
.stat-pill:hover {
    box-shadow: 0 4px 14px rgba(0,0,0,0.07);
    transform: translateY(-1px);
}
.table-custom thead th {
    background-color: #f8fafc;
    color: #334155;
    font-weight: 700;
    text-transform: uppercase;
    font-size: 0.72rem;
    letter-spacing: 0.5px;
    padding: 0.75rem 1rem;
    border-bottom: 2px solid #e2e8f0;
}
.table-custom tbody td {
    padding: 0.85rem 1rem;
    vertical-align: middle;
}
.tr-subtotal td {
    background-color: #f1f5f9 !important;
    font-weight: 700 !important;
    color: #1e293b !important;
    border-top: 2px solid #cbd5e1 !important;
}
.tr-master-total td {
    background-color: #370709 !important;
    background: linear-gradient(90deg, #370709 0%, #820100 100%) !important;
    color: #ffffff !important;
    font-weight: 700 !important;
    font-size: 0.95rem;
    padding: 0.95rem 1rem !important;
    border-top: 2px solid #250406 !important;
}
.tr-master-total td .text-warning {
    color: #ffc107 !important;
}
.tr-master-total td .text-white {
    color: #ffffff !important;
}
.tr-master-total td span.badge {
    background-color: #ffc107 !important;
    color: #212529 !important;
}
.btn-period-preset.active {
    background-color: #820100 !important;
    color: #ffffff !important;
    border-color: #820100 !important;
}
</style>

<!-- PAGE TITLE & BREADCRUMB -->
<div class="mb-4 d-flex flex-wrap justify-content-between align-items-center gap-3">
    <div>
        <div class="d-flex align-items-center gap-2 mb-1">
            <span class="period-badge-pill"><i class="bi bi-journal-bookmark-fill me-1"></i> CASH BOOK SUMMARY</span>
            <span class="badge bg-light text-dark border px-2 py-1"><i class="bi bi-geo-alt-fill text-danger me-1"></i><?= htmlspecialchars($range_name) ?> Range</span>
        </div>
        <h2 class="h4 fw-bold mb-0" style="color: #370709;">Cash Book Summary & Revenue Aggregation</h2>
        <p class="text-muted small mb-0">Tabbed category revenue registry, deposit compliance, and custom date range aggregation for Government Veterinary Offices.</p>
    </div>
    
    <div class="d-flex align-items-center gap-2">
        <a href="production_balance.php" class="btn btn-secondary shadow-sm text-nowrap">
            <i class="bi bi-arrow-left me-2"></i>Back to Production Balance
        </a>
        <button type="button" class="btn btn-sm text-light fw-bold shadow-sm d-flex align-items-center gap-1" style="background-color: #820100;" data-bs-toggle="modal" data-bs-target="#addCashBookSummaryModal">
            <i class="bi bi-plus-circle fs-6"></i>
            <span>Record Cash Book Entry</span>
        </button>
    </div>
</div>

<!-- ============================================================ -->
<!-- 1. CUSTOM DATE RANGE SUMMARY FILTER BAR & PRESETS            -->
<!-- ============================================================ -->
<div class="card shadow-sm border-0 mb-4" style="border-top: 4px solid #820100 !important;">
    <div class="card-body p-3 p-md-4 bg-light-subtle">
        <form method="GET" action="cash_book_summary.php" id="formDateRangeFilter">
            <input type="hidden" name="tab" id="filter_active_tab" value="<?= htmlspecialchars($active_tab) ?>">

            <div class="row g-3 align-items-end mb-3">
                <div class="col-lg-3 col-md-4 col-sm-6">
                    <label class="form-label small fw-bold text-dark mb-1">
                        <i class="bi bi-calendar-event me-1 text-primary"></i>From: Month / Year
                    </label>
                    <select name="from_month" id="rangeFromMonth" class="form-select" style="border-radius: 8px;">
                        <?php for ($m = 1; $m <= 12; $m++): ?>
                            <option value="<?= $m ?>" <?= ($m === $from_month) ? 'selected' : '' ?>>
                                <?= str_pad($m, 2, '0', STR_PAD_LEFT) ?> - <?= $month_names[$m] ?>
                            </option>
                        <?php endfor; ?>
                    </select>
                </div>

                <div class="col-lg-3 col-md-4 col-sm-6">
                    <label class="form-label small fw-bold text-dark mb-1">
                        <i class="bi bi-calendar-check me-1 text-success"></i>To: Month / Year
                    </label>
                    <select name="to_month" id="rangeToMonth" class="form-select" style="border-radius: 8px;">
                        <?php for ($m = 1; $m <= 12; $m++): ?>
                            <option value="<?= $m ?>" <?= ($m === $to_month) ? 'selected' : '' ?>>
                                <?= str_pad($m, 2, '0', STR_PAD_LEFT) ?> - <?= $month_names[$m] ?>
                            </option>
                        <?php endfor; ?>
                    </select>
                </div>

                <div class="col-lg-2 col-md-4 col-sm-6">
                    <label class="form-label small fw-bold text-dark mb-1">Reporting Year</label>
                    <select name="year" id="rangeYear" class="form-select" style="border-radius: 8px;">
                        <?php
                        $curr_yr = intval(date('Y'));
                        for ($y = $curr_yr - 3; $y <= $curr_yr + 3; $y++): ?>
                            <option value="<?= $y ?>" <?= ($y === $selected_year) ? 'selected' : '' ?>><?= $y ?></option>
                        <?php endfor; ?>
                    </select>
                </div>

                <div class="col-lg-2 col-md-6 col-sm-6">
                    <button type="submit" class="btn btn-primary w-100 shadow-sm fw-bold" style="background-color: #820100; border-color: #820100; border-radius: 8px;">
                        <i class="bi bi-funnel-fill me-1"></i> Apply Filter
                    </button>
                </div>

                <div class="col-lg-2 col-md-6 col-sm-12 text-lg-end">
                    <button type="button" class="btn btn-outline-dark w-100 shadow-sm" onclick="window.print();" style="border-radius: 8px;">
                        <i class="bi bi-printer me-1"></i> Print View
                    </button>
                </div>
            </div>

            <!-- Quick Period Interval Presets -->
            <div class="d-flex flex-wrap align-items-center gap-2 pt-2 border-top">
                <span class="small text-muted fw-bold me-1"><i class="bi bi-clock-history me-1"></i>Quick Range Presets:</span>
                <button type="button" class="btn btn-sm btn-outline-secondary btn-period-preset <?= ($from_month === 1 && $to_month === 3) ? 'active fw-bold' : '' ?>" data-from="1" data-to="3">Q1 (Jan – Mar)</button>
                <button type="button" class="btn btn-sm btn-outline-secondary btn-period-preset <?= ($from_month === 4 && $to_month === 6) ? 'active fw-bold' : '' ?>" data-from="4" data-to="6">Q2 (Apr – Jun)</button>
                <button type="button" class="btn btn-sm btn-outline-secondary btn-period-preset <?= ($from_month === 1 && $to_month === 6) ? 'active fw-bold' : '' ?>" data-from="1" data-to="6">Mid-Year (Jan – Jun)</button>
                <button type="button" class="btn btn-sm btn-outline-secondary btn-period-preset <?= ($from_month === 7 && $to_month === 9) ? 'active fw-bold' : '' ?>" data-from="7" data-to="9">Q3 (Jul – Sep)</button>
                <button type="button" class="btn btn-sm btn-outline-secondary btn-period-preset <?= ($from_month === 10 && $to_month === 12) ? 'active fw-bold' : '' ?>" data-from="10" data-to="12">Q4 (Oct – Dec)</button>
                <button type="button" class="btn btn-sm btn-outline-secondary btn-period-preset <?= ($from_month === 1 && $to_month === 12) ? 'active fw-bold' : '' ?>" data-from="1" data-to="12">Full Year (Jan – Dec)</button>
            </div>
        </form>
    </div>
</div>

<!-- ============================================================ -->
<!-- 2. EXECUTIVE KPI CARDS & CONSOLIDATED SUMMARY METRICS        -->
<!-- ============================================================ -->
<div class="row g-3 mb-4">
    <div class="col-xl-3 col-md-6">
        <div class="stat-pill d-flex align-items-center justify-content-between h-100">
            <div>
                <div class="text-muted small fw-semibold text-uppercase">Consolidated Period</div>
                <div class="fw-bold text-dark fs-6 mt-1"><?= htmlspecialchars($range_label) ?></div>
                <div class="text-muted small" style="font-size: 0.75rem;">Multi-month aggregation</div>
            </div>
            <div class="rounded-circle p-3 bg-light text-primary">
                <i class="bi bi-calendar3 fs-4"></i>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6">
        <div class="stat-pill d-flex align-items-center justify-content-between h-100">
            <div>
                <div class="text-muted small fw-semibold text-uppercase">Total Quantity Sold</div>
                <div class="fw-bold text-dark fs-4 mt-1"><?= number_format($master_grand_total['quantity_sold']) ?></div>
                <div class="text-muted small" style="font-size: 0.75rem;">Units across all streams</div>
            </div>
            <div class="rounded-circle p-3 bg-light text-info">
                <i class="bi bi-box-seam fs-4"></i>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6">
        <div class="stat-pill d-flex align-items-center justify-content-between h-100 border-start border-4 border-danger">
            <div>
                <div class="text-muted small fw-semibold text-uppercase">Total Revenue Amount</div>
                <div class="fw-bold fs-4 mt-1 text-danger">Rs. <?= number_format($master_grand_total['total_amount'], 2) ?></div>
                <div class="text-muted small" style="font-size: 0.75rem;">Consolidated gross billing</div>
            </div>
            <div class="rounded-circle p-3 bg-danger-subtle text-danger">
                <i class="bi bi-cash-stack fs-4"></i>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6">
        <div class="stat-pill d-flex align-items-center justify-content-between h-100 border-start border-4 border-success">
            <div>
                <div class="text-muted small fw-semibold text-uppercase">Amount Deposited in Bank</div>
                <div class="fw-bold fs-4 mt-1 text-success">Rs. <?= number_format($master_grand_total['amount_deposited'], 2) ?></div>
                <div class="small mt-1">
                    <?php if (abs($grand_variance) < 0.01): ?>
                        <span class="badge bg-success-subtle text-success border border-success-subtle">
                            <i class="bi bi-check-circle me-1"></i>100% Deposited (Balanced)
                        </span>
                    <?php elseif ($grand_variance > 0): ?>
                        <span class="badge bg-warning-subtle text-dark border border-warning-subtle">
                            <i class="bi bi-exclamation-triangle me-1"></i>Rs. <?= number_format($grand_variance, 2) ?> Pending Deposit
                        </span>
                    <?php else: ?>
                        <span class="badge bg-info-subtle text-info border border-info-subtle">
                            <i class="bi bi-info-circle me-1"></i>Rs. <?= number_format(abs($grand_variance), 2) ?> Surplus
                        </span>
                    <?php endif; ?>
                </div>
            </div>
            <div class="rounded-circle p-3 bg-success-subtle text-success">
                <i class="bi bi-bank fs-4"></i>
            </div>
        </div>
    </div>
</div>

<!-- ============================================================ -->
<!-- 3. TABBED UI STRUCTURE & CATEGORIZED DATA GRIDS             -->
<!-- ============================================================ -->
<div class="card shadow-sm border-0 mb-4">
    <div class="card-header bg-white p-3 border-bottom tabs-scroller">
        <ul class="nav nav-pills nav-pills-letter-h" id="cashBookTabs" role="tablist">
            <!-- Tab 1: Consultations & Certificates -->
            <li class="nav-item" role="presentation">
                <button class="nav-link <?= ($active_tab === 'tab-consultations') ? 'active' : '' ?>" id="tab-consultations-btn" data-bs-toggle="pill" data-bs-target="#tab-consultations" type="button" role="tab">
                    <span class="cat-badge-circle">1</span>
                    Tab 1: Consultations & Certificates
                    <span class="badge-tab-count"><?= count($tab_categories['tab-consultations']['items']) ?></span>
                </button>
            </li>

            <!-- Tab 2: Poultry Sales & Semen -->
            <li class="nav-item" role="presentation">
                <button class="nav-link <?= ($active_tab === 'tab-poultry') ? 'active' : '' ?>" id="tab-poultry-btn" data-bs-toggle="pill" data-bs-target="#tab-poultry" type="button" role="tab">
                    <span class="cat-badge-circle">2</span>
                    Tab 2: Poultry Sales & Semen
                    <span class="badge-tab-count"><?= count($tab_categories['tab-poultry']['items']) ?></span>
                </button>
            </li>

            <!-- Tab 3: Vaccines, Surgeries & Treatments -->
            <li class="nav-item" role="presentation">
                <button class="nav-link <?= ($active_tab === 'tab-treatments') ? 'active' : '' ?>" id="tab-treatments-btn" data-bs-toggle="pill" data-bs-target="#tab-treatments" type="button" role="tab">
                    <span class="cat-badge-circle">3</span>
                    Tab 3: Vaccines, Surgeries & Treatments
                    <span class="badge-tab-count"><?= count($tab_categories['tab-treatments']['items']) ?></span>
                </button>
            </li>

            <!-- Tab 4: Post Mortems -->
            <li class="nav-item" role="presentation">
                <button class="nav-link <?= ($active_tab === 'tab-post-mortems') ? 'active' : '' ?>" id="tab-post-mortems-btn" data-bs-toggle="pill" data-bs-target="#tab-post-mortems" type="button" role="tab">
                    <span class="cat-badge-circle">4</span>
                    Tab 4: Post Mortems
                    <span class="badge-tab-count"><?= count($tab_categories['tab-post-mortems']['items']) ?></span>
                </button>
            </li>

            <?php if (!empty($other_items)): ?>
            <!-- Optional Tab for Custom / Uncategorized Streams -->
            <li class="nav-item" role="presentation">
                <button class="nav-link <?= ($active_tab === 'tab-other') ? 'active' : '' ?>" id="tab-other-btn" data-bs-toggle="pill" data-bs-target="#tab-other" type="button" role="tab">
                    <span class="cat-badge-circle"><i class="bi bi-tag-fill" style="font-size:0.7rem;"></i></span>
                    Other Streams
                    <span class="badge-tab-count"><?= count($other_items) ?></span>
                </button>
            </li>
            <?php endif; ?>

            <!-- Consolidated Summary -->
            <li class="nav-item ms-auto" role="presentation">
                <button class="nav-link text-danger border-danger bg-white <?= ($active_tab === 'tab-consolidated') ? 'active' : '' ?>" id="tab-consolidated-btn" data-bs-toggle="pill" data-bs-target="#tab-consolidated" type="button" role="tab">
                    <i class="bi bi-table me-1"></i> Consolidated Range Summary
                </button>
            </li>
        </ul>
    </div>

    <div class="card-body p-3 p-md-4">
        <div class="tab-content" id="cashBookTabsContent">

            <?php foreach ($tab_categories as $t_key => $t_info): ?>
                <div class="tab-pane fade <?= ($active_tab === $t_key) ? 'show active' : '' ?>" id="<?= $t_key ?>" role="tabpanel">
                    
                    <div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
                        <div>
                            <h5 class="fw-bold mb-1" style="color: #370709;">
                                <i class="<?= $t_info['icon'] ?> text-danger me-2"></i><?= htmlspecialchars($t_info['title']) ?>
                            </h5>
                            <span class="text-muted small">
                                Consolidated figures aggregated from <strong><?= $month_names[$from_month] ?></strong> to <strong><?= $month_names[$to_month] ?> <?= $selected_year ?></strong>.
                            </span>
                        </div>

                        <div class="d-flex align-items-center gap-2">
                            <div class="btn-group btn-group-sm">
                                <button type="button" class="btn btn-sm btn-success border btn-export-csv" data-target="table_<?= $t_key ?>" title="Export this tab to CSV">
                                    <i class="bi bi-file-earmark-spreadsheet me-1"></i> CSV
                                </button>
                                <button type="button" class="btn btn-sm btn-danger border" onclick="window.print();" title="Export PDF">
                                    <i class="bi bi-file-pdf me-1"></i> PDF
                                </button>
                                <button type="button" class="btn btn-sm btn-dark border" onclick="window.print();" title="Print">
                                    <i class="bi bi-printer me-1"></i> Print
                                </button>
                            </div>
                            <button type="button" class="btn btn-sm text-white fw-bold btn-quick-record-tab shadow-sm" style="background-color: #820100;" data-tab="<?= $t_key ?>" data-bs-toggle="modal" data-bs-target="#addCashBookSummaryModal">
                                <i class="bi bi-plus-lg me-1"></i>Add Entry
                            </button>
                        </div>
                    </div>

                    <!-- DATA GRID FOR THIS TAB -->
                    <div class="table-responsive rounded-3 border">
                        <table class="table table-hover table-custom align-middle mb-0" id="table_<?= $t_key ?>">
                            <thead>
                                <tr>
                                    <th style="min-width: 260px;">Name of the item</th>
                                    <th class="text-end" style="width: 140px;">Quantity sold</th>
                                    <th class="text-end" style="width: 160px;">Unit price (Rs. / Cts.)</th>
                                    <th class="text-end" style="width: 180px;">Total amount (Rs. / Cts.)</th>
                                    <th class="text-end" style="width: 190px;">Amount deposited (Rs. / Cts.)</th>
                                    <th class="text-center no-export" style="width: 130px;">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($t_info['items'] as $item_name):
                                    $item_data = $aggregated_data[$t_key][$item_name] ?? [
                                        'quantity_sold'    => 0,
                                        'unit_price'       => 0.00,
                                        'total_amount'     => 0.00,
                                        'amount_deposited' => 0.00,
                                        'entries'          => []
                                    ];
                                    $has_entries = !empty($item_data['entries']);
                                    $entries_json = htmlspecialchars(json_encode($item_data['entries']), ENT_QUOTES, 'UTF-8');
                                ?>
                                    <tr>
                                        <td>
                                            <div class="d-flex align-items-center gap-2">
                                                <i class="bi bi-dot text-danger fs-4"></i>
                                                <div>
                                                    <span class="fw-bold text-dark"><?= htmlspecialchars($item_name) ?></span>
                                                    <?php if ($has_entries): ?>
                                                        <span class="badge bg-light text-muted border ms-1" style="font-size: 0.7rem;">
                                                            <?= count($item_data['entries']) ?> <?= count($item_data['entries']) === 1 ? 'record' : 'records' ?>
                                                        </span>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="text-end fw-semibold">
                                            <?= number_format($item_data['quantity_sold']) ?>
                                        </td>
                                        <td class="text-end font-monospace text-muted">
                                            <?= number_format($item_data['unit_price'], 2) ?>
                                        </td>
                                        <td class="text-end font-monospace fw-bold text-dark">
                                            Rs. <?= number_format($item_data['total_amount'], 2) ?>
                                        </td>
                                        <td class="text-end font-monospace fw-bold text-success">
                                            Rs. <?= number_format($item_data['amount_deposited'], 2) ?>
                                        </td>
                                        <td class="text-center no-export">
                                            <div class="btn-group btn-group-sm">
                                                <button type="button" class="btn btn-outline-primary btn-record-item" title="Record transaction for this item" data-item="<?= htmlspecialchars($item_name, ENT_QUOTES) ?>" data-tab="<?= $t_key ?>">
                                                    <i class="bi bi-plus"></i>
                                                </button>
                                                <?php if ($has_entries): ?>
                                                    <button type="button" class="btn btn-outline-secondary btn-view-entries" title="View individual monthly vouchers" data-item="<?= htmlspecialchars($item_name, ENT_QUOTES) ?>" data-entries='<?= $entries_json ?>'>
                                                        <i class="bi bi-list-ul"></i>
                                                    </button>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>

                            <tfoot>
                                <!-- TAB SUBTOTAL CALCULATION ROW -->
                                <tr class="tr-subtotal">
                                    <td class="text-uppercase fw-bold">
                                        <i class="bi bi-calculator me-2"></i>Subtotal: <?= htmlspecialchars($t_info['title']) ?>
                                    </td>
                                    <td class="text-end font-monospace">
                                        <?= number_format($tab_subtotals[$t_key]['quantity_sold']) ?>
                                    </td>
                                    <td class="text-end text-muted font-monospace">—</td>
                                    <td class="text-end font-monospace text-dark">
                                        Rs. <?= number_format($tab_subtotals[$t_key]['total_amount'], 2) ?>
                                    </td>
                                    <td class="text-end font-monospace text-success">
                                        Rs. <?= number_format($tab_subtotals[$t_key]['amount_deposited'], 2) ?>
                                    </td>
                                    <td class="text-center no-export">
                                        <span class="badge bg-secondary-subtle text-secondary small">Subtotal</span>
                                    </td>
                                </tr>

                                <!-- MASTER GRAND TOTAL CALCULATION ROW AT BOTTOM -->
                                <tr class="tr-master-total">
                                    <td class="text-uppercase fw-bold">
                                        <i class="bi bi-shield-lock-fill me-2 text-warning"></i>MASTER TOTAL AMOUNT (All Revenue Streams)
                                    </td>
                                    <td class="text-end font-monospace fw-bold">
                                        <?= number_format($master_grand_total['quantity_sold']) ?>
                                    </td>
                                    <td class="text-end text-light font-monospace opacity-75">—</td>
                                    <td class="text-end font-monospace fw-bold text-white fs-6">
                                        Rs. <?= number_format($master_grand_total['total_amount'], 2) ?>
                                    </td>
                                    <td class="text-end font-monospace fw-bold text-warning fs-6">
                                        Rs. <?= number_format($master_grand_total['amount_deposited'], 2) ?>
                                    </td>
                                    <td class="text-center no-export">
                                        <span class="badge bg-warning text-dark fw-bold small">Master Total</span>
                                    </td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>

                </div>
            <?php endforeach; ?>

            <!-- TAB 5: CONSOLIDATED MASTER SUMMARY TAB -->
            <div class="tab-pane fade <?= ($active_tab === 'tab-consolidated') ? 'show active' : '' ?>" id="tab-consolidated" role="tabpanel">
                <div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
                    <div>
                        <h5 class="fw-bold mb-1" style="color: #370709;">
                            <i class="bi bi-pie-chart-fill text-danger me-2"></i>Consolidated Cash Book Summary (All 4 Tabs)
                        </h5>
                        <span class="text-muted small">
                            Full breakdown across all revenue streams for <strong><?= htmlspecialchars($range_label) ?></strong>.
                        </span>
                    </div>

                    <div class="d-flex align-items-center gap-2">
                        <span class="badge bg-light text-dark border px-3 py-2">
                            Settlement Status: <strong><?= abs($grand_variance) < 0.01 ? '100% Balanced' : 'Rs. ' . number_format($grand_variance, 2) . ' Pending' ?></strong>
                        </span>
                        <button type="button" class="btn btn-sm btn-outline-success border" id="btnExportConsolidatedCSV">
                            <i class="bi bi-file-earmark-spreadsheet me-1"></i> CSV
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-dark border" onclick="window.print();">
                            <i class="bi bi-printer me-1"></i> Print
                        </button>
                    </div>
                </div>

                <div class="table-responsive rounded-3 border">
                    <table class="table table-hover table-custom align-middle mb-0" id="table_consolidated">
                        <thead>
                            <tr>
                                <th style="min-width: 260px;">Name of the item</th>
                                <th class="text-end" style="width: 140px;">Quantity sold</th>
                                <th class="text-end" style="width: 160px;">Unit price (Rs. / Cts.)</th>
                                <th class="text-end" style="width: 180px;">Total amount (Rs. / Cts.)</th>
                                <th class="text-end" style="width: 190px;">Amount deposited (Rs. / Cts.)</th>
                                <th class="text-center no-export" style="width: 130px;">Category</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($tab_categories as $t_key => $t_info): ?>
                                <!-- Category Section Header -->
                                <tr class="table-light">
                                    <td colspan="6" class="fw-bold text-uppercase py-2" style="background-color: #f1f5f9; color: #334155; font-size: 0.78rem;">
                                        <i class="<?= $t_info['icon'] ?> text-danger me-1"></i> <?= htmlspecialchars($t_info['title']) ?>
                                    </td>
                                </tr>

                                <?php foreach ($t_info['items'] as $item_name):
                                    $item_data = $aggregated_data[$t_key][$item_name] ?? [
                                        'quantity_sold'    => 0,
                                        'unit_price'       => 0.00,
                                        'total_amount'     => 0.00,
                                        'amount_deposited' => 0.00,
                                        'entries'          => []
                                    ];
                                    $has_entries = !empty($item_data['entries']);
                                    $entries_json = htmlspecialchars(json_encode($item_data['entries']), ENT_QUOTES, 'UTF-8');
                                ?>
                                    <tr>
                                        <td>
                                            <span class="ps-3 fw-bold text-dark"><?= htmlspecialchars($item_name) ?></span>
                                            <?php if ($has_entries): ?>
                                                <button type="button" class="btn btn-link btn-sm p-0 ms-1 text-decoration-none btn-view-entries" data-item="<?= htmlspecialchars($item_name, ENT_QUOTES) ?>" data-entries='<?= $entries_json ?>' title="Click to view vouchers">
                                                    (<?= count($item_data['entries']) ?> entries)
                                                </button>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-end fw-semibold">
                                            <?= number_format($item_data['quantity_sold']) ?>
                                        </td>
                                        <td class="text-end font-monospace text-muted">
                                            <?= number_format($item_data['unit_price'], 2) ?>
                                        </td>
                                        <td class="text-end font-monospace fw-bold text-dark">
                                            Rs. <?= number_format($item_data['total_amount'], 2) ?>
                                        </td>
                                        <td class="text-end font-monospace fw-bold text-success">
                                            Rs. <?= number_format($item_data['amount_deposited'], 2) ?>
                                        </td>
                                        <td class="text-center no-export">
                                            <span class="badge <?= $t_info['badge_class'] ?> rounded-pill" style="font-size: 0.68rem;">
                                                <?= htmlspecialchars($t_info['short_title']) ?>
                                            </span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>

                                <!-- Category Subtotal -->
                                <tr class="tr-subtotal" style="background-color: #f8fafc !important;">
                                    <td class="fw-bold ps-3 text-muted small text-uppercase">
                                        Subtotal: <?= htmlspecialchars($t_info['short_title']) ?>
                                    </td>
                                    <td class="text-end font-monospace small">
                                        <?= number_format($tab_subtotals[$t_key]['quantity_sold']) ?>
                                    </td>
                                    <td class="text-end text-muted font-monospace small">—</td>
                                    <td class="text-end font-monospace fw-bold text-dark small">
                                        Rs. <?= number_format($tab_subtotals[$t_key]['total_amount'], 2) ?>
                                    </td>
                                    <td class="text-end font-monospace fw-bold text-success small">
                                        Rs. <?= number_format($tab_subtotals[$t_key]['amount_deposited'], 2) ?>
                                    </td>
                                    <td class="text-center no-export">—</td>
                                </tr>
                            <?php endforeach; ?>

                            <?php if (!empty($other_items)): ?>
                                <!-- Other Items Section -->
                                <tr class="table-light">
                                    <td colspan="6" class="fw-bold text-uppercase py-2" style="background-color: #f1f5f9; color: #334155; font-size: 0.78rem;">
                                        <i class="bi bi-tag-fill text-secondary me-1"></i> Other Revenue Streams
                                    </td>
                                </tr>
                                <?php foreach ($other_items as $o_name => $o_data):
                                    $entries_json = htmlspecialchars(json_encode($o_data['entries']), ENT_QUOTES, 'UTF-8');
                                ?>
                                    <tr>
                                        <td>
                                            <span class="ps-3 fw-bold text-dark"><?= htmlspecialchars($o_name) ?></span>
                                            <button type="button" class="btn btn-link btn-sm p-0 ms-1 text-decoration-none btn-view-entries" data-item="<?= htmlspecialchars($o_name, ENT_QUOTES) ?>" data-entries='<?= $entries_json ?>'>
                                                (<?= count($o_data['entries']) ?> entries)
                                            </button>
                                        </td>
                                        <td class="text-end fw-semibold"><?= number_format($o_data['quantity_sold']) ?></td>
                                        <td class="text-end font-monospace text-muted"><?= number_format($o_data['unit_price'], 2) ?></td>
                                        <td class="text-end font-monospace fw-bold text-dark">Rs. <?= number_format($o_data['total_amount'], 2) ?></td>
                                        <td class="text-end font-monospace fw-bold text-success">Rs. <?= number_format($o_data['amount_deposited'], 2) ?></td>
                                        <td class="text-center no-export">
                                            <span class="badge bg-secondary rounded-pill" style="font-size: 0.68rem;">Custom</span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>

                        <tfoot>
                            <!-- MASTER GRAND TOTAL ROW -->
                            <tr class="tr-master-total">
                                <td class="text-uppercase fw-bold">
                                    <i class="bi bi-shield-lock-fill me-2 text-warning"></i>MASTER TOTAL AMOUNT (All Revenue Streams)
                                </td>
                                <td class="text-end font-monospace fw-bold">
                                    <?= number_format($master_grand_total['quantity_sold']) ?>
                                </td>
                                <td class="text-end text-light font-monospace opacity-75">—</td>
                                <td class="text-end font-monospace fw-bold text-white fs-6">
                                    Rs. <?= number_format($master_grand_total['total_amount'], 2) ?>
                                </td>
                                <td class="text-end font-monospace fw-bold text-warning fs-6">
                                    Rs. <?= number_format($master_grand_total['amount_deposited'], 2) ?>
                                </td>
                                <td class="text-center no-export">
                                    <span class="badge bg-warning text-dark fw-bold small">Master Total</span>
                                </td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>

            <?php if (!empty($other_items)): ?>
            <!-- TAB 6 (OPTIONAL): OTHER STREAMS -->
            <div class="tab-pane fade <?= ($active_tab === 'tab-other') ? 'show active' : '' ?>" id="tab-other" role="tabpanel">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="fw-bold mb-0 text-dark">Additional Custom Revenue Streams</h5>
                    <button type="button" class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#addCashBookSummaryModal">
                        <i class="bi bi-plus-lg me-1"></i>Add Custom Item
                    </button>
                </div>

                <div class="table-responsive rounded-3 border">
                    <table class="table table-hover table-custom align-middle mb-0" id="table_other">
                        <thead>
                            <tr>
                                <th style="min-width: 260px;">Name of the item</th>
                                <th class="text-end" style="width: 140px;">Quantity sold</th>
                                <th class="text-end" style="width: 160px;">Unit price (Rs. / Cts.)</th>
                                <th class="text-end" style="width: 180px;">Total amount (Rs. / Cts.)</th>
                                <th class="text-end" style="width: 190px;">Amount deposited (Rs. / Cts.)</th>
                                <th class="text-center no-export" style="width: 130px;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($other_items as $o_name => $o_data):
                                $entries_json = htmlspecialchars(json_encode($o_data['entries']), ENT_QUOTES, 'UTF-8');
                            ?>
                                <tr>
                                    <td>
                                        <span class="fw-bold text-dark"><?= htmlspecialchars($o_name) ?></span>
                                    </td>
                                    <td class="text-end fw-semibold"><?= number_format($o_data['quantity_sold']) ?></td>
                                    <td class="text-end font-monospace text-muted"><?= number_format($o_data['unit_price'], 2) ?></td>
                                    <td class="text-end font-monospace fw-bold text-dark">Rs. <?= number_format($o_data['total_amount'], 2) ?></td>
                                    <td class="text-end font-monospace fw-bold text-success">Rs. <?= number_format($o_data['amount_deposited'], 2) ?></td>
                                    <td class="text-center no-export">
                                        <div class="btn-group btn-group-sm">
                                            <button type="button" class="btn btn-outline-primary btn-record-item" data-item="<?= htmlspecialchars($o_name, ENT_QUOTES) ?>">
                                                <i class="bi bi-plus"></i>
                                            </button>
                                            <button type="button" class="btn btn-outline-secondary btn-view-entries" data-item="<?= htmlspecialchars($o_name, ENT_QUOTES) ?>" data-entries='<?= $entries_json ?>'>
                                                <i class="bi bi-list-ul"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot>
                            <tr class="tr-subtotal">
                                <td class="text-uppercase fw-bold">Subtotal: Other Items</td>
                                <td class="text-end font-monospace"><?= number_format($other_subtotal['quantity_sold']) ?></td>
                                <td class="text-end text-muted font-monospace">—</td>
                                <td class="text-end font-monospace text-dark">Rs. <?= number_format($other_subtotal['total_amount'], 2) ?></td>
                                <td class="text-end font-monospace text-success">Rs. <?= number_format($other_subtotal['amount_deposited'], 2) ?></td>
                                <td class="text-center no-export">—</td>
                            </tr>
                            <tr class="tr-master-total">
                                <td class="text-uppercase fw-bold">
                                    <i class="bi bi-shield-lock-fill me-2 text-warning"></i>MASTER TOTAL AMOUNT (All Revenue Streams)
                                </td>
                                <td class="text-end font-monospace fw-bold">
                                    <?= number_format($master_grand_total['quantity_sold']) ?>
                                </td>
                                <td class="text-end text-light font-monospace opacity-75">—</td>
                                <td class="text-end font-monospace fw-bold text-white fs-6">
                                    Rs. <?= number_format($master_grand_total['total_amount'], 2) ?>
                                </td>
                                <td class="text-end font-monospace fw-bold text-warning fs-6">
                                    Rs. <?= number_format($master_grand_total['amount_deposited'], 2) ?>
                                </td>
                                <td class="text-center no-export">
                                    <span class="badge bg-warning text-dark fw-bold small">Master Total</span>
                                </td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
            <?php endif; ?>

        </div>

        <!-- Sticky Bottom Action Bar with Master Grand Total Display -->
        <div class="mt-4 p-3 rounded-3 bg-light border d-flex flex-wrap justify-content-between align-items-center gap-3">
            <div class="d-flex align-items-center gap-3">
                <span class="badge" style="background-color: #820100; font-size: 0.85rem; padding: 0.5rem 0.85rem;">
                    <i class="bi bi-shield-lock-fill me-1 text-warning"></i> MASTER GRAND TOTAL
                </span>
                <div>
                    <span class="text-muted small">Total Revenue across all categories (<?= htmlspecialchars($range_label) ?>):</span>
                    <span class="fw-bold fs-5 text-danger font-monospace ms-2">
                        Rs. <?= number_format($master_grand_total['total_amount'], 2) ?>
                    </span>
                </div>
            </div>
            <div class="d-flex align-items-center gap-3">
                <div>
                    <span class="text-muted small">Total Bank Deposited:</span>
                    <span class="fw-bold fs-5 text-success font-monospace ms-2">
                        Rs. <?= number_format($master_grand_total['amount_deposited'], 2) ?>
                    </span>
                </div>
                <span class="badge <?= abs($grand_variance) < 0.01 ? 'bg-success' : 'bg-warning text-dark' ?> px-3 py-2 fw-bold">
                    <?= abs($grand_variance) < 0.01 ? '100% Balanced' : 'Rs. ' . number_format($grand_variance, 2) . ' Pending' ?>
                </span>
            </div>
        </div>
    </div>
</div>

<!-- ============================================================ -->
<!-- 4. OFFICIAL GOVERNMENT SIGN-OFF SEAL & FOOTER                -->
<!-- ============================================================ -->
<div class="card shadow-sm border-0 mt-4">
    <div class="card-body p-4">
        <div class="row text-center gy-4 align-items-center">
            <div class="col-md-4">
                <div class="small text-muted mb-1">Prepared & Verified By:</div>
                <div class="fw-bold text-dark fs-6"><?= htmlspecialchars($officer_name) ?></div>
                <div class="small text-muted"><?= htmlspecialchars($officer_designation) ?></div>
                <div class="small text-muted mt-2 border-top pt-2 d-inline-block px-4">Signature of Reporting Officer</div>
            </div>
            <div class="col-md-4">
                <div class="small text-muted mb-1">Reporting Period Verification:</div>
                <div class="badge bg-light text-dark border px-3 py-2 fs-6"><?= htmlspecialchars($range_label) ?></div>
                <div class="small text-muted mt-1">Generated: <?= date('Y-m-d H:i') ?></div>
            </div>
            <div class="col-md-4">
                <div class="small text-muted mb-1">Official Rubber Stamp / Seal:</div>
                <div class="border rounded-3 p-3 bg-light d-inline-block" style="min-width: 200px; min-height: 80px;">
                    <div class="small fw-bold text-uppercase text-secondary">Government Veterinary Office</div>
                    <div class="small text-muted"><?= htmlspecialchars($range_name) ?> Range</div>
                    <div class="small text-muted"><?= htmlspecialchars($district_name) ?> District</div>
                </div>
            </div>
        </div>
    </div>
</div>

</main>
</div>

<!-- Modals -->
<?php include 'model/add_cash_book_summary_modal.php'; ?>
<?php include 'model/edit_cash_book_summary_modal.php'; ?>
<?php include 'model/view_cash_book_item_entries_modal.php'; ?>

<script src="https://code.jquery.com/jquery-3.7.0.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/dataTables.buttons.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.bootstrap5.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/pdfmake.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/vfs_fonts.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.html5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.print.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
$(document).ready(function() {
    const monthNames = {
        1: 'January', 2: 'February', 3: 'March', 4: 'April',
        5: 'May', 6: 'June', 7: 'July', 8: 'August',
        9: 'September', 10: 'October', 11: 'November', 12: 'December'
    };

    // Keep active tab state synchronized across filter and modals
    $('button[data-bs-toggle="tab"], button[data-bs-toggle="pill"]').on('shown.bs.tab', function(e) {
        const targetId = $(e.target).attr('data-bs-target').replace('#', '');
        $('#filter_active_tab').val(targetId);
        $('#add_modal_active_tab').val(targetId);
        $('#edit_modal_active_tab').val(targetId);
        
        // Update URL hash without jump
        if (history.replaceState) {
            const url = new URL(window.location);
            url.searchParams.set('tab', targetId);
            history.replaceState(null, '', url);
        }
    });

    // Quick Period Presets Click Handler
    $('.btn-period-preset').on('click', function() {
        const fromM = $(this).data('from');
        const toM = $(this).data('to');
        $('#rangeFromMonth').val(fromM);
        $('#rangeToMonth').val(toM);
        $('#formDateRangeFilter').submit();
    });

    // Universal CSV export for any cash book table (tabs & consolidated)
    $(document).on('click', '.btn-export-csv', function() {
        const targetTableId = $(this).data('target');
        const $table = $('#' + targetTableId);
        let csv = [];
        $table.find('tr').each(function() {
            let row = [];
            $(this).find('th, td').each(function() {
                if (!$(this).hasClass('no-export')) {
                    let text = $(this).text().replace(/\s+/g, ' ').trim();
                    row.push('"' + text.replace(/"/g, '""') + '"');
                }
            });
            if (row.length > 0) csv.push(row.join(','));
        });
        const csvFile = new Blob([csv.join('\n')], { type: 'text/csv;charset=utf-8;' });
        const downloadLink = document.createElement('a');
        downloadLink.download = (targetTableId || 'cash_book_summary') + '_<?= $selected_year ?>.csv';
        downloadLink.href = window.URL.createObjectURL(csvFile);
        downloadLink.style.display = 'none';
        document.body.appendChild(downloadLink);
        downloadLink.click();
        document.body.removeChild(downloadLink);
    });

    // CSV export for consolidated report table
    $('#btnExportConsolidatedCSV').on('click', function() {
        let csv = [];
        $('#table_consolidated tr').each(function() {
            let row = [];
            $(this).find('th, td').each(function() {
                if (!$(this).hasClass('no-export')) {
                    let text = $(this).text().replace(/\s+/g, ' ').trim();
                    row.push('"' + text.replace(/"/g, '""') + '"');
                }
            });
            if (row.length > 0) csv.push(row.join(','));
        });
        let csvFile = new Blob([csv.join('\n')], { type: 'text/csv;charset=utf-8;' });
        let downloadLink = document.createElement('a');
        downloadLink.download = 'Cash_Book_Consolidated_Summary_<?= $selected_year ?>.csv';
        downloadLink.href = window.URL.createObjectURL(csvFile);
        downloadLink.style.display = 'none';
        document.body.appendChild(downloadLink);
        downloadLink.click();
        document.body.removeChild(downloadLink);
    });

    // Handle SweetAlert status messages
    const urlParams = new URLSearchParams(window.location.search);
    const status = urlParams.get('status');
    if (status === 'success') {
        Swal.fire({
            icon: 'success',
            title: 'Cash Book Updated!',
            text: 'Revenue record processed and aggregated successfully.',
            timer: 2000,
            confirmButtonColor: '#820100'
        });
        const cleanUrl = new URL(window.location);
        cleanUrl.searchParams.delete('status');
        window.history.replaceState({}, document.title, cleanUrl);
    } else if (status === 'db_error') {
        Swal.fire({
            icon: 'error',
            title: 'Action Failed',
            text: 'Could not process database action. Please check parameters and try again.',
            confirmButtonColor: '#820100'
        });
        const cleanUrl = new URL(window.location);
        cleanUrl.searchParams.delete('status');
        window.history.replaceState({}, document.title, cleanUrl);
    }

    // Trigger "+ Record Entry" for a specific item
    $(document).on('click', '.btn-record-item', function() {
        const itemName = $(this).data('item');
        const tabKey = $(this).data('tab') || $('#filter_active_tab').val();

        // Pre-select in Add Modal
        const $selector = $('#add_item_selector');
        let matched = false;

        $selector.find('option').each(function() {
            if ($(this).val() === itemName) {
                $selector.val(itemName).trigger('change');
                matched = true;
                return false;
            }
        });

        if (!matched) {
            $selector.val('__custom__').trigger('change');
            $('#add_custom_item_name').val(itemName);
            $('#add_final_item_name').val(itemName);
        }

        $('#add_modal_active_tab').val(tabKey);
        new bootstrap.Modal(document.getElementById('addCashBookSummaryModal')).show();
    });

    // Quick record from Tab header
    $('.btn-quick-record-tab').on('click', function() {
        const tabKey = $(this).data('tab');
        $('#add_modal_active_tab').val(tabKey);
    });

    // View Item Entries Breakdown Modal
    $(document).on('click', '.btn-view-entries', function() {
        const itemName = $(this).data('item');
        const entries = $(this).data('entries') || [];

        $('#viewItemNameHeading').text(itemName);
        $('#viewItemEntriesPeriod').text('Period: <?= htmlspecialchars($range_label) ?>');
        $('.btn-quick-add-for-item').attr('data-item', itemName);

        const $tbody = $('#itemEntriesTbody');
        const $tfoot = $('#itemEntriesTfoot');
        $tbody.empty();
        $tfoot.empty();

        let sumQty = 0;
        let sumTotal = 0;
        let sumDep = 0;

        if (entries.length === 0) {
            $tbody.append('<tr><td colspan="6" class="text-center text-muted py-3">No individual entries recorded in this period.</td></tr>');
        } else {
            entries.forEach(function(rec) {
                const qty = parseInt(rec.quantity_sold) || 0;
                const price = parseFloat(rec.unit_price) || 0;
                const total = parseFloat(rec.total_amount) || 0;
                const dep = parseFloat(rec.amount_deposited) || 0;
                const mName = monthNames[rec.report_month] || 'Month ' + rec.report_month;

                sumQty += qty;
                sumTotal += total;
                sumDep += dep;

                const tr = `
                    <tr id="entry_row_${rec.id}">
                        <td><strong>${mName}</strong> ${rec.report_year}</td>
                        <td class="text-end">${qty.toLocaleString()}</td>
                        <td class="text-end font-monospace">${price.toFixed(2)}</td>
                        <td class="text-end font-monospace fw-bold text-dark">Rs. ${total.toFixed(2)}</td>
                        <td class="text-end font-monospace fw-bold text-success">Rs. ${dep.toFixed(2)}</td>
                        <td class="text-end">
                            <button type="button" class="btn btn-xs btn-outline-primary btn-edit-entry me-1" 
                                data-id="${rec.id}"
                                data-year="${rec.report_year}"
                                data-month="${rec.report_month}"
                                data-item="${rec.item_name}"
                                data-qty="${rec.quantity_sold}"
                                data-price="${rec.unit_price}"
                                data-total="${rec.total_amount}"
                                data-deposited="${rec.amount_deposited}"
                                title="Edit this record">
                                <i class="bi bi-pencil-square"></i>
                            </button>
                            <button type="button" class="btn btn-xs btn-outline-danger btn-delete-entry" 
                                data-id="${rec.id}"
                                data-name="${rec.item_name} (${mName})"
                                title="Delete this record">
                                <i class="bi bi-trash"></i>
                            </button>
                        </td>
                    </tr>
                `;
                $tbody.append(tr);
            });

            const footHtml = `
                <tr class="table-light">
                    <td>Total for Item</td>
                    <td class="text-end">${sumQty.toLocaleString()}</td>
                    <td class="text-end text-muted font-monospace">—</td>
                    <td class="text-end font-monospace text-dark">Rs. ${sumTotal.toFixed(2)}</td>
                    <td class="text-end font-monospace text-success">Rs. ${sumDep.toFixed(2)}</td>
                    <td></td>
                </tr>
            `;
            $tfoot.append(footHtml);
        }

        new bootstrap.Modal(document.getElementById('viewItemEntriesModal')).show();
    });

    // Quick add from within View Item Entries modal
    $('.btn-quick-add-for-item').on('click', function() {
        const itemName = $(this).attr('data-item');
        const viewModalEl = document.getElementById('viewItemEntriesModal');
        const viewModalInstance = bootstrap.Modal.getInstance(viewModalEl);
        if (viewModalInstance) viewModalInstance.hide();

        const $selector = $('#add_item_selector');
        let matched = false;
        $selector.find('option').each(function() {
            if ($(this).val() === itemName) {
                $selector.val(itemName).trigger('change');
                matched = true;
                return false;
            }
        });

        if (!matched) {
            $selector.val('__custom__').trigger('change');
            $('#add_custom_item_name').val(itemName);
            $('#add_final_item_name').val(itemName);
        }

        new bootstrap.Modal(document.getElementById('addCashBookSummaryModal')).show();
    });

    // Trigger Edit Modal from an entry row
    $(document).on('click', '.btn-edit-entry', function() {
        const id = $(this).data('id');
        const yr = $(this).data('year');
        const m = $(this).data('month');
        const item = $(this).data('item');
        const qty = $(this).data('qty');
        const price = $(this).data('price');
        const total = $(this).data('total');
        const dep = $(this).data('deposited');

        // Hide view entries modal if open
        const viewModalEl = document.getElementById('viewItemEntriesModal');
        if (viewModalEl) {
            const instance = bootstrap.Modal.getInstance(viewModalEl);
            if (instance) instance.hide();
        }

        $('#edit_id').val(id);
        $('#edit_report_year').val(yr);
        $('#edit_report_month').val(m);
        $('#edit_qty_sold').val(qty);
        $('#edit_unit_price').val(price);
        $('#edit_total_amount').val(total);
        $('#edit_amount_deposited').val(dep);

        const $editSelector = $('#edit_item_selector');
        let matched = false;
        $editSelector.find('option').each(function() {
            if ($(this).val() === item) {
                $editSelector.val(item).trigger('change');
                matched = true;
                return false;
            }
        });

        if (!matched) {
            $editSelector.val('__custom__').trigger('change');
            $('#edit_custom_item_name').val(item);
            $('#edit_final_item_name').val(item);
        }

        new bootstrap.Modal(document.getElementById('editCashBookSummaryModal')).show();
    });

    // AJAX Delete Confirmation Click Handler
    $(document).on('click', '.btn-delete-entry', function() {
        const recordId = $(this).data('id');
        const entryName = $(this).data('name') || 'this voucher';

        Swal.fire({
            icon: 'warning',
            title: 'Delete Revenue Record?',
            html: `You are about to delete entry "<strong>${entryName}</strong>".<br>This will recalculate all aggregated totals.`,
            showCancelButton: true,
            confirmButtonColor: '#820100',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Yes, Delete',
            cancelButtonText: 'Cancel'
        }).then(function(result) {
            if (result.isConfirmed) {
                $.ajax({
                    url: 'processors/delete_cash_book_summary.php',
                    type: 'POST',
                    data: { id: recordId },
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            Swal.fire({
                                icon: 'success',
                                title: 'Deleted!',
                                text: 'The voucher record has been removed.',
                                timer: 1200,
                                showConfirmButton: false
                            });
                            // Reload page to refresh all aggregations
                            setTimeout(function() {
                                window.location.reload();
                            }, 1200);
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: 'Delete Failed',
                                text: response.message || 'Error occurred during deletion.'
                            });
                        }
                    },
                    error: function() {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: 'AJAX request execution failed.'
                        });
                    }
                });
            }
        });
    });
});
</script>

<?php require_once '../../../includes/footer.php'; ?>
