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
            <li class="nav-item" role="presentation">
                <button class="nav-link <?= ($active_tab === 'month_old') ? 'active' : '' ?> fw-bold py-3 px-4" id="month-old-tab" data-bs-toggle="pill" data-bs-target="#month-old-pane" type="button" role="tab" aria-controls="month-old-pane" aria-selected="<?= ($active_tab === 'month_old') ? 'true' : 'false' ?>" style="--bs-nav-pills-link-active-bg: #198754;">
                    <i class="bi bi-truck me-2"></i>Scenario C: Month-Old Chicks Distribution
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

        </div>

    </main>
</div>

<!-- ========================================================================= -->
<!-- MODALS FOR SCENARIO A: GROWTH LOG -->
<!-- ========================================================================= -->

<!-- Add Growth Modal -->
<div class="modal fade" id="addGrowthModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form action="processors/chick_growth_log_crud.php" method="POST">
                <input type="hidden" name="action" value="create">
                <div class="modal-header text-light" style="background-color: #370709;">
                    <h5 class="modal-header-title fw-bold m-0"><i class="bi bi-activity me-2"></i>Log Daily Growth & Mortality</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Record Date <span class="text-danger">*</span></label>
                            <input type="date" name="record_date" id="add_growth_date" class="form-control" value="<?= date('Y-m-d') ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Select Cage <span class="text-danger">*</span></label>
                            <select name="cage_id" id="add_growth_cage_id" class="form-select" required>
                                <option value="">-- Select Cage --</option>
                                <?php foreach ($cages as $c): ?>
                                    <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['cage_name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold text-primary">Opening Chicks Count <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <input type="number" name="opening_chicks_count" id="add_growth_opening" class="form-control fw-bold border-primary" min="0" value="0" required>
                                <button type="button" class="btn btn-outline-primary" id="btn_auto_calc_opening" title="Auto-fetch balance from previous log/hatchery">
                                    <i class="bi bi-arrow-repeat"></i> Auto-Fetch
                                </button>
                            </div>
                            <small class="text-muted">Calculated as Previous Day Opening - Deaths (or Hatchery Register if 1st entry).</small>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold text-danger">No. of Deaths <span class="text-danger">*</span></label>
                            <input type="number" name="no_of_deaths" class="form-control border-danger fw-bold" min="0" value="0" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Feed Type</label>
                            <input type="text" name="feed_type" class="form-control" placeholder="e.g. Starter Mesh, Grower Feed">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-bold">Feed To Be Given (kg)</label>
                            <input type="number" step="0.01" name="feed_amount_to_be_given" class="form-control" min="0" value="0.00">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-bold">Feed Given (kg)</label>
                            <input type="number" step="0.01" name="feed_amount_given" class="form-control" min="0" value="0.00">
                        </div>
                        <div class="col-md-12">
                            <label class="form-label fw-bold">Vaccination / Treatment Details</label>
                            <textarea name="vaccination_treatment" class="form-control" rows="2" placeholder="e.g. ND Vaccine, Vitamins..."></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary fw-bold" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary fw-bold" style="background-color: #370709; border-color: #370709;">Save Growth Log</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Growth Modal -->
<div class="modal fade" id="editGrowthModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form action="processors/chick_growth_log_crud.php" method="POST">
                <input type="hidden" name="action" value="update">
                <input type="hidden" name="id" id="edit_growth_id">
                <div class="modal-header text-white" style="background-color: #370709;">
                    <h5 class="modal-header-title fw-bold m-0"><i class="bi bi-pencil-square me-2"></i>Edit Growth Log Entry</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Record Date <span class="text-danger">*</span></label>
                            <input type="date" name="record_date" id="edit_growth_record_date" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Select Cage <span class="text-danger">*</span></label>
                            <select name="cage_id" id="edit_growth_cage_id" class="form-select" required>
                                <option value="">-- Select Cage --</option>
                                <?php foreach ($cages as $c): ?>
                                    <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['cage_name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold text-primary">Opening Chicks Count <span class="text-danger">*</span></label>
                            <input type="number" name="opening_chicks_count" id="edit_growth_opening" class="form-control fw-bold border-primary" min="0" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold text-danger">No. of Deaths <span class="text-danger">*</span></label>
                            <input type="number" name="no_of_deaths" id="edit_growth_deaths" class="form-control border-danger fw-bold" min="0" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Feed Type</label>
                            <input type="text" name="feed_type" id="edit_growth_feed_type" class="form-control">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-bold">Feed To Be Given (kg)</label>
                            <input type="number" step="0.01" name="feed_amount_to_be_given" id="edit_growth_feed_to_be_given" class="form-control" min="0">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-bold">Feed Given (kg)</label>
                            <input type="number" step="0.01" name="feed_amount_given" id="edit_growth_feed_given" class="form-control" min="0">
                        </div>
                        <div class="col-md-12">
                            <label class="form-label fw-bold">Vaccination / Treatment Details</label>
                            <textarea name="vaccination_treatment" id="edit_growth_vaccination" class="form-control" rows="2"></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary fw-bold" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary fw-bold" style="background-color: #370709; border-color: #370709;">Update Growth Log</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ========================================================================= -->
<!-- MODALS FOR SCENARIO B: DAY-OLD DISTRIBUTION -->
<!-- ========================================================================= -->

<!-- Add Day-Old Distribution Modal -->
<div class="modal fade" id="addDayOldModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form action="processors/day_old_distribution_crud.php" method="POST">
                <input type="hidden" name="action" value="create">
                <div class="modal-header text-white" style="background-color: #0d6efd;">
                    <h5 class="modal-header-title fw-bold m-0"><i class="bi bi-box-arrow-up-right me-2"></i>Log Day-Old Chicks Distribution</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Record Date <span class="text-danger">*</span></label>
                            <input type="date" name="record_date" class="form-control" value="<?= date('Y-m-d') ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">No. of Chicks Produced</label>
                            <input type="number" name="no_of_chicks_produced" class="form-control" min="0" value="0">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Sent to Place / Destination <span class="text-danger">*</span></label>
                            <input type="text" name="sent_to_place" class="form-control" placeholder="e.g. Trincomalee, Colombo, Farm X" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold text-primary">No. of Chicks Sent <span class="text-danger">*</span></label>
                            <input type="number" name="no_of_chicks_sent" id="add_day_old_sent" class="form-control calc-day-old fw-bold border-primary" min="0" value="0" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Price Per Chick (Rs.) <span class="text-danger">*</span></label>
                            <input type="number" step="0.01" name="price_per_chick" id="add_day_old_price" class="form-control calc-day-old fw-bold" min="0" value="0.00" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold text-success">Total Amount (Rs.)</label>
                            <div class="input-group">
                                <span class="input-group-text bg-success text-white">Rs.</span>
                                <input type="text" id="add_day_old_total" class="form-control bg-light fw-bold text-success" readonly value="0.00">
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary fw-bold" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary fw-bold">Save Day-Old Distribution</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Day-Old Distribution Modal -->
<div class="modal fade" id="editDayOldModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form action="processors/day_old_distribution_crud.php" method="POST">
                <input type="hidden" name="action" value="update">
                <input type="hidden" name="id" id="edit_day_old_id">
                <div class="modal-header text-white" style="background-color: #0d6efd;">
                    <h5 class="modal-header-title fw-bold m-0"><i class="bi bi-pencil-square me-2"></i>Edit Day-Old Chicks Distribution</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Record Date <span class="text-danger">*</span></label>
                            <input type="date" name="record_date" id="edit_day_old_date" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">No. of Chicks Produced</label>
                            <input type="number" name="no_of_chicks_produced" id="edit_day_old_produced" class="form-control" min="0">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Sent to Place / Destination <span class="text-danger">*</span></label>
                            <input type="text" name="sent_to_place" id="edit_day_old_place" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold text-primary">No. of Chicks Sent <span class="text-danger">*</span></label>
                            <input type="number" name="no_of_chicks_sent" id="edit_day_old_sent" class="form-control edit-calc-day-old fw-bold border-primary" min="0" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Price Per Chick (Rs.) <span class="text-danger">*</span></label>
                            <input type="number" step="0.01" name="price_per_chick" id="edit_day_old_price" class="form-control edit-calc-day-old fw-bold" min="0" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold text-success">Total Amount (Rs.)</label>
                            <div class="input-group">
                                <span class="input-group-text bg-success text-white">Rs.</span>
                                <input type="text" id="edit_day_old_total" class="form-control bg-light fw-bold text-success" readonly value="0.00">
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary fw-bold" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary fw-bold">Update Day-Old Distribution</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ========================================================================= -->
<!-- MODALS FOR SCENARIO C: MONTH-OLD DISTRIBUTION -->
<!-- ========================================================================= -->

<!-- Add Month-Old Distribution Modal -->
<div class="modal fade" id="addMonthOldModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form action="processors/month_old_distribution_crud.php" method="POST">
                <input type="hidden" name="action" value="create">
                <div class="modal-header text-white" style="background-color: #198754;">
                    <h5 class="modal-header-title fw-bold m-0"><i class="bi bi-truck me-2"></i>Log Month-Old Chicks Distribution</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Record Date <span class="text-danger">*</span></label>
                            <input type="date" name="record_date" class="form-control" value="<?= date('Y-m-d') ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Source Cage (Data Link)</label>
                            <select name="cage_id" id="add_month_old_cage_id" class="form-select">
                                <option value="">-- Select Source Cage --</option>
                                <?php foreach ($cages as $c): ?>
                                    <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['cage_name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold text-secondary">No. of Chicks Produced (Surviving Balance)</label>
                            <input type="number" name="no_of_chicks_produced" id="add_month_old_produced" class="form-control" min="0" value="0">
                            <small class="text-muted">Auto-populated from growth log surviving balance when cage is selected.</small>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Sent to Place / Destination <span class="text-danger">*</span></label>
                            <input type="text" name="sent_to_place" class="form-control" placeholder="e.g. Trincomalee, Ampara, Batticaloa" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold text-success">No. of Chicks Sent <span class="text-danger">*</span></label>
                            <input type="number" name="no_of_chicks_sent" id="add_month_old_sent" class="form-control calc-month-old fw-bold border-success" min="0" value="0" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Price Per Chick (Rs.) <span class="text-danger">*</span></label>
                            <input type="number" step="0.01" name="price_per_chick" id="add_month_old_price" class="form-control calc-month-old fw-bold" min="0" value="0.00" required>
                        </div>
                        <div class="col-md-12">
                            <label class="form-label fw-bold text-success">Total Amount (Rs.)</label>
                            <div class="input-group">
                                <span class="input-group-text bg-success text-white">Rs.</span>
                                <input type="text" id="add_month_old_total" class="form-control bg-light fw-bold text-success fs-5" readonly value="0.00">
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary fw-bold" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success fw-bold">Save Month-Old Distribution</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Month-Old Distribution Modal -->
<div class="modal fade" id="editMonthOldModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form action="processors/month_old_distribution_crud.php" method="POST">
                <input type="hidden" name="action" value="update">
                <input type="hidden" name="id" id="edit_month_old_id">
                <div class="modal-header text-white" style="background-color: #198754;">
                    <h5 class="modal-header-title fw-bold m-0"><i class="bi bi-pencil-square me-2"></i>Edit Month-Old Chicks Distribution</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Record Date <span class="text-danger">*</span></label>
                            <input type="date" name="record_date" id="edit_month_old_date" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Source Cage</label>
                            <select name="cage_id" id="edit_month_old_cage_id" class="form-select">
                                <option value="">-- Select Source Cage --</option>
                                <?php foreach ($cages as $c): ?>
                                    <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['cage_name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">No. of Chicks Produced</label>
                            <input type="number" name="no_of_chicks_produced" id="edit_month_old_produced" class="form-control" min="0">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Sent to Place / Destination <span class="text-danger">*</span></label>
                            <input type="text" name="sent_to_place" id="edit_month_old_place" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold text-success">No. of Chicks Sent <span class="text-danger">*</span></label>
                            <input type="number" name="no_of_chicks_sent" id="edit_month_old_sent" class="form-control edit-calc-month-old fw-bold border-success" min="0" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Price Per Chick (Rs.) <span class="text-danger">*</span></label>
                            <input type="number" step="0.01" name="price_per_chick" id="edit_month_old_price" class="form-control edit-calc-month-old fw-bold" min="0" required>
                        </div>
                        <div class="col-md-12">
                            <label class="form-label fw-bold text-success">Total Amount (Rs.)</label>
                            <div class="input-group">
                                <span class="input-group-text bg-success text-white">Rs.</span>
                                <input type="text" id="edit_month_old_total" class="form-control bg-light fw-bold text-success fs-5" readonly value="0.00">
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary fw-bold" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success fw-bold">Update Month-Old Distribution</button>
                </div>
            </form>
        </div>
    </div>
</div>

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