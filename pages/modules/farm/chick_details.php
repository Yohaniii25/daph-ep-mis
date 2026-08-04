<?php
// pages/modules/farm/chick_details.php -> Comprehensive Chick Details Module (Replaces old Chick Death Details)
require_once '../../../includes/header.php';
require_once '../../../config/db_connect.php';

if ($_SESSION['role'] !== 'farms_dd') {
    die("Access denied");
}

$user_id = $_SESSION['user_id'] ?? 1;

// Active tab determination
$active_tab = $_GET['tab'] ?? 'day_old'; // 'day_old', 'growth', 'month_old'

// Selected filter month (default to current month YYYY-MM)
$selected_month = $_GET['month'] ?? date('Y-m');
$first_day_of_month = date('Y-m-01', strtotime($selected_month . '-01'));
$last_day_of_month = date('Y-m-t', strtotime($selected_month . '-01'));
$month_label = date('F Y', strtotime($first_day_of_month));

// Fetch available Cages for dropdowns
$cages_res = $mysqli->query("SELECT id, cage_name FROM cages ORDER BY cage_name");
$cages = [];
if ($cages_res) {
    while ($row = $cages_res->fetch_assoc()) {
        $cages[] = $row;
    }
}

// -------------------------------------------------------------
// 1. Fetch Scenario A Records (chick_growth_log)
// -------------------------------------------------------------
$sql_a = "SELECT g.*, c.cage_name 
          FROM chick_growth_log g
          LEFT JOIN cages c ON g.cage_id = c.id
          WHERE g.record_date BETWEEN ? AND ?
          ORDER BY g.record_date DESC, g.id DESC";
$stmt_a = $mysqli->prepare($sql_a);
$stmt_a->bind_param("ss", $first_day_of_month, $last_day_of_month);
$stmt_a->execute();
$res_a = $stmt_a->get_result();
$growth_records = [];
$growth_total_deaths = 0;
$growth_total_feed_given = 0;

if ($res_a) {
    while ($r = $res_a->fetch_assoc()) {
        $growth_records[] = $r;
        $growth_total_deaths += intval($r['no_of_deaths']);
        $growth_total_feed_given += floatval($r['feed_amount_given']);
    }
}
$stmt_a->close();

// -------------------------------------------------------------
// 2. Fetch Scenario B Records (day_old_chicks_distribution)
// -------------------------------------------------------------
$sql_b = "SELECT * FROM day_old_chicks_distribution 
          WHERE record_date BETWEEN ? AND ?
          ORDER BY record_date DESC, id DESC";
$stmt_b = $mysqli->prepare($sql_b);
$stmt_b->bind_param("ss", $first_day_of_month, $last_day_of_month);
$stmt_b->execute();
$res_b = $stmt_b->get_result();
$day_old_records = [];
$day_old_total_sent = 0;
$day_old_total_amount = 0;

if ($res_b) {
    while ($r = $res_b->fetch_assoc()) {
        $day_old_records[] = $r;
        $day_old_total_sent += intval($r['no_of_chicks_sent']);
        $day_old_total_amount += floatval($r['total_amount']);
    }
}
$stmt_b->close();

// -------------------------------------------------------------
// 3. Fetch Scenario C Records (month_old_chicks_distribution)
// -------------------------------------------------------------
$sql_c = "SELECT m.*, c.cage_name 
          FROM month_old_chicks_distribution m
          LEFT JOIN cages c ON m.cage_id = c.id
          WHERE m.record_date BETWEEN ? AND ?
          ORDER BY m.record_date DESC, m.id DESC";
$stmt_c = $mysqli->prepare($sql_c);
$stmt_c->bind_param("ss", $first_day_of_month, $last_day_of_month);
$stmt_c->execute();
$res_c = $stmt_c->get_result();
$month_old_records = [];
$month_old_total_sent = 0;
$month_old_total_amount = 0;

if ($res_c) {
    while ($r = $res_c->fetch_assoc()) {
        $month_old_records[] = $r;
        $month_old_total_sent += intval($r['no_of_chicks_sent']);
        $month_old_total_amount += floatval($r['total_amount']);
    }
}
$stmt_c->close();
// -------------------------------------------------------------
// 4. Fetch Chicks Issuing Details (chicks_issuing_details)
// -------------------------------------------------------------
$sql_issuing = "SELECT * FROM chicks_issuing_details 
                WHERE record_month BETWEEN ? AND ?
                ORDER BY record_month DESC, id DESC";
$stmt_issuing = $mysqli->prepare($sql_issuing);
$stmt_issuing->bind_param("ss", $first_day_of_month, $last_day_of_month);
$stmt_issuing->execute();
$res_issuing = $stmt_issuing->get_result();
$issuing_records = [];
$issuing_total_hatched = 0;
$issuing_total_live = 0;
$issuing_total_deaths_sexing = 0;
$issuing_total_issued = 0;

if ($res_issuing) {
    while ($r = $res_issuing->fetch_assoc()) {
        $issuing_records[] = $r;
        $issuing_total_hatched += intval($r['no_of_eggs_hatched']);
        $issuing_total_live += (intval($r['live_chicks_pullets']) + intval($r['live_chicks_cockerels']));
        $issuing_total_deaths_sexing += (intval($r['deaths_sexing_pullets']) + intval($r['deaths_sexing_cockerels']) + intval($r['deaths_sexing_unsexed']));
        
        $sum_9 = intval($r['do_pullets'] ?? 0) + intval($r['do_cockerels'] ?? 0) + intval($r['do_unsexed'] ?? 0)
               + intval($r['wo_pullets'] ?? 0) + intval($r['wo_cockerels'] ?? 0) + intval($r['wo_unsexed'] ?? 0)
               + intval($r['mo_pullets'] ?? 0) + intval($r['mo_cockerels'] ?? 0) + intval($r['mo_unsexed'] ?? 0);
        if ($sum_9 > 0) {
            $issuing_total_issued += $sum_9;
        } else {
            $issuing_total_issued += (intval($r['issue_cockerels_pullets']) + intval($r['issue_day_old_unsex']) + intval($r['issue_day_old_cockerel']) + intval($r['issue_month_old_unsexed']));
        }
    }
}
$stmt_issuing->close();

require_once '../../../includes/sidebar.php';
?>

<!-- SweetAlert2 & DataTables CSS -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.2/css/buttons.bootstrap5.min.css">

<div id="layoutSidenav_content" class="bg-light">
    <main class="container-fluid px-4 pt-4">

        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2 class="fw-bold text-dark m-0">Chick Details Management</h2>
                <small class="text-muted">Comprehensive tracking for daily chick growth, feeding, and distribution sales (Day-Old & Month-Old).</small>
            </div>
            <span class="badge bg-secondary p-2 fs-6">Logged in: <b><?= htmlspecialchars($_SESSION['username']) ?></b></span>
        </div>

        <!-- Notification Status SweetAlert -->
        <?php if (isset($_GET['status'])): ?>
            <script>
                document.addEventListener('DOMContentLoaded', function() {
                    if (typeof Swal !== 'undefined') {
                        Swal.fire({
                            icon: '<?= ($_GET['status'] === 'success') ? 'success' : 'error' ?>',
                            title: '<?= ($_GET['status'] === 'success') ? 'Success!' : 'Error!' ?>',
                            text: <?= json_encode($_GET['msg'] ?? '') ?>,
                            confirmButtonColor: '#370709',
                            timer: 3500,
                            timerProgressBar: true
                        });
                    }
                });
            </script>
        <?php endif; ?>

        <!-- Month Filter Card -->
        <div class="card border-0 shadow-sm mb-4" style="border-radius: 12px;">
            <div class="card-body py-3">
                <div class="row align-items-center">
                    <div class="col-md-6 col-lg-7">
                        <h6 class="fw-bold text-dark m-0"><i class="bi bi-calendar-check-fill text-primary me-2"></i>Active Reporting Month: <span class="text-primary"><?= $month_label ?></span></h6>
                    </div>
                    <div class="col-md-6 col-lg-5">
                        <div class="d-flex align-items-center">
                            <input type="month" id="month_filter" class="form-control fw-bold me-2" value="<?= htmlspecialchars($selected_month) ?>">
                            <button type="button" class="btn btn-dark fw-bold text-nowrap" id="btnFilter">
                                <i class="bi bi-funnel-fill me-1"></i>Filter
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Main 3-Tab Scenario Navigation Bar -->
        <ul class="nav nav-pills mb-4 bg-white p-2 rounded shadow-sm border" id="chickModuleTabs" role="tablist">
            <li class="nav-item me-2" role="presentation">
                <button class="nav-link <?= ($active_tab === 'day_old') ? 'active' : '' ?> fw-bold py-3 px-4" id="day-old-tab" data-bs-toggle="pill" data-bs-target="#day-old-pane" type="button" role="tab" aria-controls="day-old-pane" aria-selected="<?= ($active_tab === 'day_old') ? 'true' : 'false' ?>" style="--bs-nav-pills-link-active-bg: #0d6efd;">
                    <i class="bi bi-box-arrow-up-right me-2"></i>Scenario A: Day-Old Chicks Distribution
                </button>
            </li>
            <li class="nav-item me-2" role="presentation">
                <button class="nav-link <?= ($active_tab === 'growth') ? 'active' : '' ?> fw-bold py-3 px-4" id="growth-tab" data-bs-toggle="pill" data-bs-target="#growth-pane" type="button" role="tab" aria-controls="growth-pane" aria-selected="<?= ($active_tab === 'growth') ? 'true' : 'false' ?>" style="--bs-nav-pills-link-active-bg: #370709;">
                    <i class="bi bi-activity me-2"></i>Scenario B: Month-Old Chicks Growth Log
                </button>
            </li>
            <li class="nav-item me-2" role="presentation">
                <button class="nav-link <?= ($active_tab === 'month_old') ? 'active' : '' ?> fw-bold py-3 px-4" id="month-old-tab" data-bs-toggle="pill" data-bs-target="#month-old-pane" type="button" role="tab" aria-controls="month-old-pane" aria-selected="<?= ($active_tab === 'month_old') ? 'true' : 'false' ?>" style="--bs-nav-pills-link-active-bg: #198754;">
                    <i class="bi bi-truck me-2"></i>Scenario C: Month-Old Chicks Distribution
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link <?= ($active_tab === 'issuing') ? 'active' : '' ?> fw-bold py-3 px-4" id="issuing-tab" data-bs-toggle="pill" data-bs-target="#issuing-pane" type="button" role="tab" aria-controls="issuing-pane" aria-selected="<?= ($active_tab === 'issuing') ? 'true' : 'false' ?>" style="--bs-nav-pills-link-active-bg: #8d170e;">
                    <i class="bi bi-file-earmark-spreadsheet me-2"></i>Chicks Issuing Summary Report
                </button>
            </li>
        </ul>

        <div class="tab-content" id="chickModuleTabContent">

            <!-- ========================================================= -->
            <!-- TAB 1: SCENARIO B - DAY-OLD CHICKS DISTRIBUTION -->
            <!-- ========================================================= -->
            <div class="tab-pane fade <?= ($active_tab === 'day_old') ? 'show active' : '' ?>" id="day-old-pane" role="tabpanel" aria-labelledby="day-old-tab" tabindex="0">

                <!-- KPI Summary Cards -->
                <div class="row g-3 mb-4">
                    <div class="col-md-6">
                        <div class="card border-0 shadow-sm p-3 bg-white" style="border-radius: 12px; border-left: 5px solid #0d6efd !important;">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <small class="text-muted fw-bold uppercase d-block">Total Day-Old Chicks Sent</small>
                                    <span class="fs-3 fw-bold text-primary"><?= number_format($day_old_total_sent) ?></span>
                                </div>
                                <div class="p-3 bg-primary-subtle rounded-circle text-primary">
                                    <i class="bi bi-box-arrow-up-right fs-4"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card border-0 shadow-sm p-3 bg-white" style="border-radius: 12px; border-left: 5px solid #198754 !important;">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <small class="text-muted fw-bold uppercase d-block">Total Revenue (Rs.)</small>
                                    <span class="fs-3 fw-bold text-success">Rs. <?= number_format($day_old_total_amount, 2) ?></span>
                                </div>
                                <div class="p-3 bg-success-subtle rounded-circle text-success">
                                    <i class="bi bg-currency-dollar fs-4"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card border-0 shadow-sm mb-4" style="border-radius: 12px;">
                    <div class="card-header bg-white py-3 border-0 d-flex justify-content-between align-items-center">
                        <h5 class="fw-bold text-dark m-0"><i class="bi bi-box-arrow-up-right me-2 text-primary"></i>Day-Old Chicks Distribution & Sales Log</h5>
                        <button class="btn btn-primary fw-bold px-4" data-bs-toggle="modal" data-bs-target="#addDayOldModal">
                            <i class="bi bi-plus-circle me-1"></i>Log Day-Old Distribution
                        </button>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle border" id="dayOldTable">
                                <thead class="table-dark" style="background-color: #0d6efd;">
                                    <tr>
                                        <th>Date</th>
                                        <th>Chicks Produced</th>
                                        <th>Destination / Place</th>
                                        <th>Chicks Sent</th>
                                        <th>Price Per Chick (Rs.)</th>
                                        <th>Total Amount (Rs.)</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($day_old_records as $r): ?>
                                        <tr>
                                            <td><?= date('d M Y', strtotime($r['record_date'])) ?></td>
                                            <td class="fw-bold text-secondary"><?= number_format($r['no_of_chicks_produced']) ?></td>
                                            <td><span class="badge bg-light text-dark border fs-6"><i class="bi bi-geo-alt-fill text-danger me-1"></i><?= htmlspecialchars($r['sent_to_place']) ?></span></td>
                                            <td class="fw-bold text-primary"><?= number_format($r['no_of_chicks_sent']) ?></td>
                                            <td>Rs. <?= number_format($r['price_per_chick'], 2) ?></td>
                                            <td class="fw-bold text-success">Rs. <?= number_format($r['total_amount'], 2) ?></td>
                                            <td>
                                                <button class="btn btn-sm btn-outline-primary me-1 btn-edit-day-old"
                                                    data-id="<?= $r['id'] ?>"
                                                    data-record_date="<?= $r['record_date'] ?>"
                                                    data-no_of_chicks_produced="<?= $r['no_of_chicks_produced'] ?>"
                                                    data-sent_to_place="<?= htmlspecialchars($r['sent_to_place']) ?>"
                                                    data-no_of_chicks_sent="<?= $r['no_of_chicks_sent'] ?>"
                                                    data-price_per_chick="<?= $r['price_per_chick'] ?>"
                                                    data-total_amount="<?= $r['total_amount'] ?>"
                                                    data-bs-toggle="modal" data-bs-target="#editDayOldModal">
                                                    <i class="bi bi-pencil-square"></i>
                                                </button>
                                                <a href="processors/day_old_distribution_crud.php?action=delete&id=<?= $r['id'] ?>" class="btn btn-sm btn-outline-danger btn-delete">
                                                    <i class="bi bi-trash"></i>
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ========================================================= -->
            <!-- TAB 2: SCENARIO A - MONTH-OLD CHICKS GROWTH LOG -->
            <!-- ========================================================= -->
            <div class="tab-pane fade <?= ($active_tab === 'growth') ? 'show active' : '' ?>" id="growth-pane" role="tabpanel" aria-labelledby="growth-tab" tabindex="0">

                <!-- KPI Summary Cards -->
                <div class="row g-3 mb-4">
                    <div class="col-md-4">
                        <div class="card border-0 shadow-sm p-3 bg-white" style="border-radius: 12px; border-left: 5px solid #dc3545 !important;">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <small class="text-muted fw-bold uppercase d-block">Total Deaths (<?= $month_label ?>)</small>
                                    <span class="fs-3 fw-bold text-danger"><?= number_format($growth_total_deaths) ?></span>
                                </div>
                                <div class="p-3 bg-danger-subtle rounded-circle text-danger">
                                    <i class="bi bi-heart-pulse-fill fs-4"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card border-0 shadow-sm p-3 bg-white" style="border-radius: 12px; border-left: 5px solid #ffc107 !important;">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <small class="text-muted fw-bold uppercase d-block">Total Feed Given (<?= $month_label ?>)</small>
                                    <span class="fs-3 fw-bold text-warning"><?= number_format($growth_total_feed_given, 2) ?> kg</span>
                                </div>
                                <div class="p-3 bg-warning-subtle rounded-circle text-warning">
                                    <i class="bi bi-basket-fill fs-4"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card border-0 shadow-sm p-3 bg-white" style="border-radius: 12px; border-left: 5px solid #370709 !important;">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <small class="text-muted fw-bold uppercase d-block">Total Logs Recorded</small>
                                    <span class="fs-3 fw-bold text-dark"><?= count($growth_records) ?></span>
                                </div>
                                <div class="p-3 bg-light rounded-circle text-dark border">
                                    <i class="bi bi-journal-check fs-4"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card border-0 shadow-sm mb-4" style="border-radius: 12px;">
                    <div class="card-header bg-white py-3 border-0 d-flex justify-content-between align-items-center">
                        <h5 class="fw-bold text-dark m-0"><i class="bi bi-activity me-2 text-danger"></i>Daily Growth, Mortality & Feeding Register</h5>
                        <button style="background-color: #370709; border-color: #370709;" class="btn btn-primary fw-bold px-4" data-bs-toggle="modal" data-bs-target="#addGrowthModal">
                            <i class="bi bi-plus-circle me-1"></i>Log Daily Growth Record
                        </button>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle border" id="growthTable">
                                <thead class="table-dark" style="background-color: #370709;">
                                    <tr>
                                        <th>Date</th>
                                        <th>Cage Name</th>
                                        <th>Opening Chicks</th>
                                        <th>Deaths</th>
                                        <th>Net Surviving</th>
                                        <th>Feed Type</th>
                                        <th>Feed To Be Given (kg)</th>
                                        <th>Feed Given (kg)</th>
                                        <th>Vaccination / Treatment</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($growth_records as $r):
                                        $surviving = max(0, intval($r['opening_chicks_count']) - intval($r['no_of_deaths']));
                                    ?>
                                        <tr>
                                            <td><?= date('d M Y', strtotime($r['record_date'])) ?></td>
                                            <td><span class="badge bg-secondary"><?= htmlspecialchars($r['cage_name'] ?? 'N/A') ?></span></td>
                                            <td class="fw-bold text-primary"><?= number_format($r['opening_chicks_count']) ?></td>
                                            <td class="fw-bold text-danger"><?= number_format($r['no_of_deaths']) ?></td>
                                            <td class="fw-bold text-success"><?= number_format($surviving) ?></td>
                                            <td><span class="badge bg-light text-dark border"><?= htmlspecialchars($r['feed_type'] ?? '-') ?></span></td>
                                            <td><?= number_format($r['feed_amount_to_be_given'], 2) ?></td>
                                            <td class="fw-bold"><?= number_format($r['feed_amount_given'], 2) ?></td>
                                            <td><span class="small"><?= htmlspecialchars($r['vaccination_treatment'] ?? '-') ?></span></td>
                                            <td>
                                                <button class="btn btn-sm btn-outline-primary me-1 btn-edit-growth"
                                                    data-id="<?= $r['id'] ?>"
                                                    data-record_date="<?= $r['record_date'] ?>"
                                                    data-cage_id="<?= $r['cage_id'] ?>"
                                                    data-opening_chicks_count="<?= $r['opening_chicks_count'] ?>"
                                                    data-no_of_deaths="<?= $r['no_of_deaths'] ?>"
                                                    data-feed_type="<?= htmlspecialchars($r['feed_type'] ?? '') ?>"
                                                    data-feed_amount_to_be_given="<?= $r['feed_amount_to_be_given'] ?>"
                                                    data-feed_amount_given="<?= $r['feed_amount_given'] ?>"
                                                    data-vaccination_treatment="<?= htmlspecialchars($r['vaccination_treatment'] ?? '') ?>"
                                                    data-bs-toggle="modal" data-bs-target="#editGrowthModal">
                                                    <i class="bi bi-pencil-square"></i>
                                                </button>
                                                <a href="processors/chick_growth_log_crud.php?action=delete&id=<?= $r['id'] ?>" class="btn btn-sm btn-outline-danger btn-delete">
                                                    <i class="bi bi-trash"></i>
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ========================================================= -->
            <!-- TAB 3: SCENARIO C - MONTH-OLD CHICKS DISTRIBUTION -->
            <!-- ========================================================= -->
            <div class="tab-pane fade <?= ($active_tab === 'month_old') ? 'show active' : '' ?>" id="month-old-pane" role="tabpanel" aria-labelledby="month-old-tab" tabindex="0">

                <!-- KPI Summary Cards -->
                <div class="row g-3 mb-4">
                    <div class="col-md-6">
                        <div class="card border-0 shadow-sm p-3 bg-white" style="border-radius: 12px; border-left: 5px solid #198754 !important;">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <small class="text-muted fw-bold uppercase d-block">Total Month-Old Chicks Sent</small>
                                    <span class="fs-3 fw-bold text-success"><?= number_format($month_old_total_sent) ?></span>
                                </div>
                                <div class="p-3 bg-success-subtle rounded-circle text-success">
                                    <i class="bi bi-truck fs-4"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card border-0 shadow-sm p-3 bg-white" style="border-radius: 12px; border-left: 5px solid #370709 !important;">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <small class="text-muted fw-bold uppercase d-block">Total Revenue (Rs.)</small>
                                    <span class="fs-3 fw-bold text-dark">Rs. <?= number_format($month_old_total_amount, 2) ?></span>
                                </div>
                                <div class="p-3 bg-light rounded-circle text-dark border">
                                    <i class="bi bi-cash-stack fs-4"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card border-0 shadow-sm mb-4" style="border-radius: 12px;">
                    <div class="card-header bg-white py-3 border-0 d-flex justify-content-between align-items-center">
                        <h5 class="fw-bold text-dark m-0"><i class="bi bi-truck me-2 text-success"></i>Month-Old Chicks Distribution & Sales Log</h5>
                        <button class="btn btn-success fw-bold px-4" data-bs-toggle="modal" data-bs-target="#addMonthOldModal">
                            <i class="bi bi-plus-circle me-1"></i>Log Month-Old Distribution
                        </button>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle border" id="monthOldTable">
                                <thead class="table-dark" style="background-color: #198754;">
                                    <tr>
                                        <th>Date</th>
                                        <th>Source Cage</th>
                                        <th>Chicks Produced</th>
                                        <th>Destination / Place</th>
                                        <th>Chicks Sent</th>
                                        <th>Price Per Chick (Rs.)</th>
                                        <th>Total Amount (Rs.)</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($month_old_records as $r): ?>
                                        <tr>
                                            <td><?= date('d M Y', strtotime($r['record_date'])) ?></td>
                                            <td><span class="badge bg-secondary"><?= htmlspecialchars($r['cage_name'] ?? 'N/A') ?></span></td>
                                            <td class="fw-bold text-secondary"><?= number_format($r['no_of_chicks_produced']) ?></td>
                                            <td><span class="badge bg-light text-dark border fs-6"><i class="bi bi-geo-alt-fill text-danger me-1"></i><?= htmlspecialchars($r['sent_to_place']) ?></span></td>
                                            <td class="fw-bold text-success"><?= number_format($r['no_of_chicks_sent']) ?></td>
                                            <td>Rs. <?= number_format($r['price_per_chick'], 2) ?></td>
                                            <td class="fw-bold text-success">Rs. <?= number_format($r['total_amount'], 2) ?></td>
                                            <td>
                                                <button class="btn btn-sm btn-outline-primary me-1 btn-edit-month-old"
                                                    data-id="<?= $r['id'] ?>"
                                                    data-record_date="<?= $r['record_date'] ?>"
                                                    data-cage_id="<?= $r['cage_id'] ?>"
                                                    data-no_of_chicks_produced="<?= $r['no_of_chicks_produced'] ?>"
                                                    data-sent_to_place="<?= htmlspecialchars($r['sent_to_place']) ?>"
                                                    data-no_of_chicks_sent="<?= $r['no_of_chicks_sent'] ?>"
                                                    data-price_per_chick="<?= $r['price_per_chick'] ?>"
                                                    data-total_amount="<?= $r['total_amount'] ?>"
                                                    data-bs-toggle="modal" data-bs-target="#editMonthOldModal">
                                                    <i class="bi bi-pencil-square"></i>
                                                </button>
                                                <a href="processors/month_old_distribution_crud.php?action=delete&id=<?= $r['id'] ?>" class="btn btn-sm btn-outline-danger btn-delete">
                                                    <i class="bi bi-trash"></i>
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ========================================================= -->
            <!-- TAB 4: CHICKS ISSUING DETAILS SUMMARY REPORT -->
            <!-- ========================================================= -->
            <div class="tab-pane fade <?= ($active_tab === 'issuing') ? 'show active' : '' ?>" id="issuing-pane" role="tabpanel" aria-labelledby="issuing-tab" tabindex="0">
                
                <!-- KPI Summary Cards -->
                <div class="row g-3 mb-4">
                    <div class="col-md-3">
                        <div class="card border-0 shadow-sm p-3 bg-white" style="border-radius: 12px; border-left: 5px solid #8d170e !important;">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <small class="text-muted fw-bold uppercase d-block">Total Eggs Hatched</small>
                                    <span class="fs-3 fw-bold" style="color: #8d170e;"><?= number_format($issuing_total_hatched) ?></span>
                                </div>
                                <div class="p-3 rounded-circle" style="background-color: #f3ebf9; color: #8d170e;">
                                    <i class="bi bi-egg-fill fs-4"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card border-0 shadow-sm p-3 bg-white" style="border-radius: 12px; border-left: 5px solid #0d6efd !important;">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <small class="text-muted fw-bold uppercase d-block">Live Chicks Count</small>
                                    <span class="fs-3 fw-bold text-primary"><?= number_format($issuing_total_live) ?></span>
                                </div>
                                <div class="p-3 bg-primary-subtle rounded-circle text-primary">
                                    <i class="bi bi-check-circle-fill fs-4"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card border-0 shadow-sm p-3 bg-white" style="border-radius: 12px; border-left: 5px solid #dc3545 !important;">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <small class="text-muted fw-bold uppercase d-block">Post-Sexing Deaths</small>
                                    <span class="fs-3 fw-bold text-danger"><?= number_format($issuing_total_deaths_sexing) ?></span>
                                </div>
                                <div class="p-3 bg-danger-subtle rounded-circle text-danger">
                                    <i class="bi bi-x-circle-fill fs-4"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card border-0 shadow-sm p-3 bg-white" style="border-radius: 12px; border-left: 5px solid #198754 !important;">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <small class="text-muted fw-bold uppercase d-block">Total Chicks Issued</small>
                                    <span class="fs-3 fw-bold text-success"><?= number_format($issuing_total_issued) ?></span>
                                </div>
                                <div class="p-3 bg-success-subtle rounded-circle text-success">
                                    <i class="bi bi-box-arrow-up-right fs-4"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card border-0 shadow-sm mb-4" style="border-radius: 12px;">
                    <div class="card-header bg-white py-3 border-0 d-flex justify-content-between align-items-center">
                        <h5 class="fw-bold text-light m-0"><i class="bi bi-file-earmark-spreadsheet me-2" style="color: #8d170e;"></i>Chicks Issuing Monthly Summary Register</h5>
                        <button class="btn fw-bold px-4 text-light" style="background-color: #8d170e;" data-bs-toggle="modal" data-bs-target="#addIssuingModal">
                            <i class="bi bi-plus-circle me-1"></i>Log Chicks Issuing Record
                        </button>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-bordered table-hover align-middle text-center" id="issuingTable" style="font-size: 0.85rem;">
                                <thead class="table-light align-middle" style="background-color: #6f42c1; color: white;">
                                    <tr>
                                        <th rowspan="3" class="align-middle">Month</th>
                                        <th rowspan="3" class="align-middle">Issue Date</th>
                                        <th rowspan="3" class="align-middle">Range Name</th>
                                        <th rowspan="3" class="align-middle">Batch No</th>
                                        <th colspan="4" class="bg-secondary text-white">Hatchery & Monthly Stock</th>
                                        <th colspan="2" class="bg-primary text-white">No. of Live Chicks</th>
                                        <th colspan="3" class="bg-danger text-white">Total Deaths (Sexing To Issue)</th>
                                        <th colspan="9" class="bg-success text-white">Live Chicks Issued Categories</th>
                                        <th rowspan="3" class="align-middle">Rate (Rs.)</th>
                                        <th rowspan="3" class="align-middle">Total Amount (Rs.)</th>
                                        <th rowspan="3" class="align-middle">Remarks</th>
                                        <th rowspan="3" class="align-middle">Actions</th>
                                    </tr>
                                    <tr>
                                        <th rowspan="2" class="small">Eggs Hatched</th>
                                        <th rowspan="2" class="small">Starting Bal.</th>
                                        <th rowspan="2" class="small">Deaths (Pre-Sex)</th>
                                        <th rowspan="2" class="small">Received</th>
                                        <th rowspan="2" class="small">Pullets</th>
                                        <th rowspan="2" class="small">Cockerels</th>
                                        <th rowspan="2" class="small">Pullets</th>
                                        <th rowspan="2" class="small">Cockerels</th>
                                        <th rowspan="2" class="small">Unsexed</th>
                                        <th colspan="3" class="small bg-warning text-dark">Day Old (D/O)</th>
                                        <th colspan="3" class="small bg-info text-dark">Week Old</th>
                                        <th colspan="3" class="small bg-dark text-white">Month Old</th>
                                    </tr>
                                    <tr>
                                        <th class="small bg-warning text-dark">Pullets</th>
                                        <th class="small bg-warning text-dark">Cockerels</th>
                                        <th class="small bg-warning text-dark">Unsexed</th>
                                        <th class="small bg-info text-dark">Pullets</th>
                                        <th class="small bg-info text-dark">Cockerels</th>
                                        <th class="small bg-info text-dark">Unsexed</th>
                                        <th class="small bg-dark text-white">Pullets</th>
                                        <th class="small bg-dark text-white">Cockerels</th>
                                        <th class="small bg-dark text-white">Unsexed</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($issuing_records as $r): ?>
                                        <tr>
                                            <td class="fw-bold text-nowrap"><?= date('M Y', strtotime($r['record_month'])) ?></td>
                                            <td class="text-nowrap"><?= !empty($r['issue_date']) ? date('Y-m-d', strtotime($r['issue_date'])) : '-' ?></td>
                                            <td><?= htmlspecialchars($r['name_of_range'] ?? '-') ?></td>
                                            <td><span class="badge text-white" style="background-color: #6f42c1;"><?= htmlspecialchars($r['batch_no']) ?></span></td>
                                            <td><?= number_format($r['no_of_eggs_hatched']) ?></td>
                                            <td><?= number_format($r['starting_balance_of_month']) ?></td>
                                            <td class="text-danger"><?= number_format($r['deaths_before_sexing']) ?></td>
                                            <td><?= number_format($r['received']) ?></td>
                                            <td class="fw-bold text-primary"><?= number_format($r['live_chicks_pullets']) ?></td>
                                            <td class="fw-bold text-primary"><?= number_format($r['live_chicks_cockerels']) ?></td>
                                            <td class="text-danger"><?= number_format($r['deaths_sexing_pullets']) ?></td>
                                            <td class="text-danger"><?= number_format($r['deaths_sexing_cockerels']) ?></td>
                                            <td class="text-danger"><?= number_format($r['deaths_sexing_unsexed']) ?></td>

                                            <!-- 9 Categories -->
                                            <td class="fw-bold text-success"><?= number_format($r['do_pullets'] ?? 0) ?></td>
                                            <td class="fw-bold text-success"><?= number_format($r['do_cockerels'] ?? 0) ?></td>
                                            <td class="fw-bold text-success"><?= number_format($r['do_unsexed'] ?? 0) ?></td>
                                            <td class="fw-bold text-success"><?= number_format($r['wo_pullets'] ?? 0) ?></td>
                                            <td class="fw-bold text-success"><?= number_format($r['wo_cockerels'] ?? 0) ?></td>
                                            <td class="fw-bold text-success"><?= number_format($r['wo_unsexed'] ?? 0) ?></td>
                                            <td class="fw-bold text-success"><?= number_format($r['mo_pullets'] ?? 0) ?></td>
                                            <td class="fw-bold text-success"><?= number_format($r['mo_cockerels'] ?? 0) ?></td>
                                            <td class="fw-bold text-success"><?= number_format($r['mo_unsexed'] ?? 0) ?></td>

                                            <td class="fw-bold">Rs. <?= number_format($r['rate'] ?? 0, 2) ?></td>
                                            <td class="fw-bold text-primary">Rs. <?= number_format($r['total_amount'] ?? 0, 2) ?></td>
                                            <td class="small"><?= htmlspecialchars($r['remarks'] ?? '-') ?></td>
                                            <td class="text-nowrap">
                                                <button class="btn btn-sm btn-outline-primary me-1 btn-edit-issuing" 
                                                        data-id="<?= $r['id'] ?>"
                                                        data-record_month="<?= date('Y-m', strtotime($r['record_month'])) ?>"
                                                        data-issue_date="<?= htmlspecialchars($r['issue_date'] ?? '') ?>"
                                                        data-name_of_range="<?= htmlspecialchars($r['name_of_range'] ?? '') ?>"
                                                        data-batch_no="<?= htmlspecialchars($r['batch_no']) ?>"
                                                        data-no_of_eggs_hatched="<?= $r['no_of_eggs_hatched'] ?>"
                                                        data-starting_balance_of_month="<?= $r['starting_balance_of_month'] ?>"
                                                        data-deaths_before_sexing="<?= $r['deaths_before_sexing'] ?>"
                                                        data-received="<?= $r['received'] ?>"
                                                        data-live_chicks_pullets="<?= $r['live_chicks_pullets'] ?>"
                                                        data-live_chicks_cockerels="<?= $r['live_chicks_cockerels'] ?>"
                                                        data-deaths_sexing_pullets="<?= $r['deaths_sexing_pullets'] ?>"
                                                        data-deaths_sexing_cockerels="<?= $r['deaths_sexing_cockerels'] ?>"
                                                        data-deaths_sexing_unsexed="<?= $r['deaths_sexing_unsexed'] ?>"
                                                        data-do_pullets="<?= $r['do_pullets'] ?? 0 ?>"
                                                        data-do_cockerels="<?= $r['do_cockerels'] ?? 0 ?>"
                                                        data-do_unsexed="<?= $r['do_unsexed'] ?? 0 ?>"
                                                        data-wo_pullets="<?= $r['wo_pullets'] ?? 0 ?>"
                                                        data-wo_cockerels="<?= $r['wo_cockerels'] ?? 0 ?>"
                                                        data-wo_unsexed="<?= $r['wo_unsexed'] ?? 0 ?>"
                                                        data-mo_pullets="<?= $r['mo_pullets'] ?? 0 ?>"
                                                        data-mo_cockerels="<?= $r['mo_cockerels'] ?? 0 ?>"
                                                        data-mo_unsexed="<?= $r['mo_unsexed'] ?? 0 ?>"
                                                        data-rate="<?= $r['rate'] ?? 0 ?>"
                                                        data-total_amount="<?= $r['total_amount'] ?? 0 ?>"
                                                        data-remarks="<?= htmlspecialchars($r['remarks'] ?? '') ?>"
                                                        data-bs-toggle="modal" data-bs-target="#editIssuingModal">
                                                    <i class="bi bi-pencil-square"></i>
                                                </button>
                                                <a href="processors/chicks_issuing_crud.php?action=delete&id=<?= $r['id'] ?>" class="btn btn-sm btn-outline-danger btn-delete">
                                                    <i class="bi bi-trash"></i>
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

        </div>

    </main>
</div>

<!-- Modal views -->
<?php
include './models/day_old_distribution_modals.php';
include './models/chick_growth_modals.php';
include './models/month_old_distribution_modals.php';
include './models/chicks_issuing_modals.php';
?>

<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/dataTables.buttons.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.bootstrap5.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/pdfmake.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/vfs_fonts.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.html5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.print.min.js"></script>

<script>
    $(document).ready(function() {
        // Helper function for DataTables export button configuration
        function createButtonsConfig(orientation = 'portrait') {
            return [{
                    extend: 'csv',
                    text: '<i class="bi bi-filetype-csv me-1"></i> CSV',
                    className: 'btn btn-sm btn-success me-1 rounded font-weight-bold',
                    exportOptions: {
                        columns: ':not(:last-child)'
                    }
                },
                {
                    extend: 'pdf',
                    text: '<i class="bi bi-file-earmark-pdf me-1"></i> PDF',
                    className: 'btn btn-sm btn-danger me-1 rounded font-weight-bold',
                    orientation: orientation,
                    pageSize: 'A4',
                    exportOptions: {
                        columns: ':not(:last-child)'
                    }
                },
                {
                    extend: 'print',
                    text: '<i class="bi bi-printer me-1"></i> Print',
                    className: 'btn btn-sm btn-dark rounded font-weight-bold',
                    exportOptions: {
                        columns: ':not(:last-child)'
                    }
                }
            ];
        }

        const commonDom = '<"d-flex justify-content-between align-items-center mb-3"Bf>rt<"d-flex justify-content-between align-items-center mt-3"ip>';

        // Initialize DataTables with CSV, PDF, and Print export support
        $('#growthTable').DataTable({
            order: [
                [0, 'desc']
            ],
            pageLength: 25,
            dom: commonDom,
            buttons: createButtonsConfig('landscape')
        });

        $('#dayOldTable').DataTable({
            order: [
                [0, 'desc']
            ],
            pageLength: 25,
            dom: commonDom,
            buttons: createButtonsConfig('portrait')
        });

        $('#monthOldTable').DataTable({
            order: [
                [0, 'desc']
            ],
            pageLength: 25,
            dom: commonDom,
            buttons: createButtonsConfig('portrait')
        });

        $('#issuingTable').DataTable({
            order: [
                [0, 'desc']
            ],
            pageLength: 25,
            dom: commonDom,
            buttons: createButtonsConfig('landscape')
        });

        // Month filter action
        $('#btnFilter').on('click', function() {
            const mVal = $('#month_filter').val();
            const activeTabPane = $('.nav-link.active').attr('id').replace('-tab', '');
            if (mVal) {
                window.location.href = 'chick_details.php?month=' + encodeURIComponent(mVal) + '&tab=' + encodeURIComponent(activeTabPane);
            }
        });

        // -------------------------------------------------------------
        // Scenario A: Auto-fetch Opening Balance for Growth Log
        // -------------------------------------------------------------
        function fetchOpeningBalance() {
            const cageId = $('#add_growth_cage_id').val();
            const recordDate = $('#add_growth_date').val();
            if (cageId) {
                $.getJSON('processors/chick_growth_log_crud.php', {
                    action: 'get_opening_balance',
                    cage_id: cageId,
                    record_date: recordDate
                }, function(res) {
                    if (res.success) {
                        $('#add_growth_opening').val(res.opening_chicks_count);
                        if (typeof res.no_of_deaths !== 'undefined') {
                            $('#add_growth_deaths').val(res.no_of_deaths);
                        }
                    }
                });
            }
        }

        $('#add_growth_cage_id, #add_growth_date').on('change', fetchOpeningBalance);
        $('#btn_auto_calc_opening').on('click', fetchOpeningBalance);

        // Populate Edit Growth Modal
        $(document).on('click', '.btn-edit-growth', function() {
            const btn = $(this);
            $('#edit_growth_id').val(btn.data('id'));
            $('#edit_growth_record_date').val(btn.data('record_date'));
            $('#edit_growth_cage_id').val(btn.data('cage_id'));
            $('#edit_growth_opening').val(btn.data('opening_chicks_count'));
            $('#edit_growth_deaths').val(btn.data('no_of_deaths'));
            $('#edit_growth_feed_type').val(btn.data('feed_type'));
            $('#edit_growth_feed_to_be_given').val(btn.data('feed_amount_to_be_given'));
            $('#edit_growth_feed_given').val(btn.data('feed_amount_given'));
            $('#edit_growth_vaccination').val(btn.data('vaccination_treatment'));
        });

        // -------------------------------------------------------------
        // Scenario B: Live Total Calculation (Day-Old Distribution)
        // -------------------------------------------------------------
        function calcDayOldAddTotal() {
            const sent = parseFloat($('#add_day_old_sent').val()) || 0;
            const price = parseFloat($('#add_day_old_price').val()) || 0;
            $('#add_day_old_total').val((sent * price).toFixed(2));
        }

        function calcDayOldEditTotal() {
            const sent = parseFloat($('#edit_day_old_sent').val()) || 0;
            const price = parseFloat($('#edit_day_old_price').val()) || 0;
            $('#edit_day_old_total').val((sent * price).toFixed(2));
        }
        $('.calc-day-old').on('input change', calcDayOldAddTotal);
        $('.edit-calc-day-old').on('input change', calcDayOldEditTotal);

        // Populate Edit Day-Old Modal
        $(document).on('click', '.btn-edit-day-old', function() {
            const btn = $(this);
            $('#edit_day_old_id').val(btn.data('id'));
            $('#edit_day_old_date').val(btn.data('record_date'));
            $('#edit_day_old_produced').val(btn.data('no_of_chicks_produced'));
            $('#edit_day_old_place').val(btn.data('sent_to_place'));
            $('#edit_day_old_sent').val(btn.data('no_of_chicks_sent'));
            $('#edit_day_old_price').val(btn.data('price_per_chick'));
            calcDayOldEditTotal();
        });

        // -------------------------------------------------------------
        // Scenario C: Data Linking & Live Total Calculation (Month-Old Distribution)
        // -------------------------------------------------------------
        $('#add_month_old_cage_id').on('change', function() {
            const cageId = $(this).val();
            if (cageId) {
                $.getJSON('processors/month_old_distribution_crud.php', {
                    action: 'get_surviving_balance',
                    cage_id: cageId
                }, function(res) {
                    if (res.success) {
                        $('#add_month_old_produced').val(res.surviving_balance);
                    }
                });
            }
        });

        function calcMonthOldAddTotal() {
            const sent = parseFloat($('#add_month_old_sent').val()) || 0;
            const price = parseFloat($('#add_month_old_price').val()) || 0;
            $('#add_month_old_total').val((sent * price).toFixed(2));
        }

        function calcMonthOldEditTotal() {
            const sent = parseFloat($('#edit_month_old_sent').val()) || 0;
            const price = parseFloat($('#edit_month_old_price').val()) || 0;
            $('#edit_month_old_total').val((sent * price).toFixed(2));
        }
        $('.calc-month-old').on('input change', calcMonthOldAddTotal);
        $('.edit-calc-month-old').on('input change', calcMonthOldEditTotal);

        // Populate Edit Month-Old Modal
        $(document).on('click', '.btn-edit-month-old', function() {
            const btn = $(this);
            $('#edit_month_old_id').val(btn.data('id'));
            $('#edit_month_old_date').val(btn.data('record_date'));
            $('#edit_month_old_cage_id').val(btn.data('cage_id'));
            $('#edit_month_old_produced').val(btn.data('no_of_chicks_produced'));
            $('#edit_month_old_place').val(btn.data('sent_to_place'));
            $('#edit_month_old_sent').val(btn.data('no_of_chicks_sent'));
            $('#edit_month_old_price').val(btn.data('price_per_chick'));
            calcMonthOldEditTotal();
        });

        // -------------------------------------------------------------
        // Populate Edit Chicks Issuing Summary Modal
        // -------------------------------------------------------------
        $(document).on('click', '.btn-edit-issuing', function() {
            const btn = $(this);
            $('#edit_issuing_id').val(btn.data('id'));
            $('#edit_issuing_record_month').val(btn.data('record_month'));
            $('#edit_issuing_issue_date').val(btn.data('issue_date'));
            $('#edit_issuing_name_of_range').val(btn.data('name_of_range'));
            $('#edit_issuing_batch_no').val(btn.data('batch_no'));
            $('#edit_issuing_eggs_hatched').val(btn.data('no_of_eggs_hatched'));
            $('#edit_issuing_starting_bal').val(btn.data('starting_balance_of_month'));
            $('#edit_issuing_deaths_before_sex').val(btn.data('deaths_before_sexing'));
            $('#edit_issuing_received').val(btn.data('received'));
            $('#edit_issuing_live_pullets').val(btn.data('live_chicks_pullets'));
            $('#edit_issuing_live_cockerels').val(btn.data('live_chicks_cockerels'));
            $('#edit_issuing_deaths_pullets').val(btn.data('deaths_sexing_pullets'));
            $('#edit_issuing_deaths_cockerels').val(btn.data('deaths_sexing_cockerels'));
            $('#edit_issuing_deaths_unsexed').val(btn.data('deaths_sexing_unsexed'));

            // 9 categories
            $('#edit_do_pullets').val(btn.data('do_pullets'));
            $('#edit_do_cockerels').val(btn.data('do_cockerels'));
            $('#edit_do_unsexed').val(btn.data('do_unsexed'));

            $('#edit_wo_pullets').val(btn.data('wo_pullets'));
            $('#edit_wo_cockerels').val(btn.data('wo_cockerels'));
            $('#edit_wo_unsexed').val(btn.data('wo_unsexed'));

            $('#edit_mo_pullets').val(btn.data('mo_pullets'));
            $('#edit_mo_cockerels').val(btn.data('mo_cockerels'));
            $('#edit_mo_unsexed').val(btn.data('mo_unsexed'));

            $('#edit_rate').val(btn.data('rate'));
            $('#edit_total_amount').val(btn.data('total_amount'));

            $('#edit_issuing_remarks').val(btn.data('remarks'));

            // Trigger input event to re-run JS calculation if needed
            $('#edit_rate').trigger('input');
        });

        // Delete confirmation SweetAlert
        $(document).on('click', '.btn-delete', function(e) {
            e.preventDefault();
            const deleteUrl = $(this).attr('href');
            Swal.fire({
                title: 'Are you sure?',
                text: "This record will be permanently deleted!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Yes, delete it!'
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = deleteUrl;
                }
            });
        });
    });
</script>

<?php require_once '../../../includes/footer.php'; ?>