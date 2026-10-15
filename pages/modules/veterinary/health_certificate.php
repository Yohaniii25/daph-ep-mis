<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
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

$user_id = (int)($_SESSION['user_id'] ?? 0);
$range_id = (int)($_SESSION['range_id'] ?? 0);

$range_name = 'Your Range';
$district_name = 'Your District';
$district_id = null;

if (!empty($range_id)) {
    $details_sql = "
        SELECT vr.name AS range_name, d.name AS district_name, d.id AS district_id
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
            $district_name = $data['district_name'] ?? 'Your District';
            $district_id = (int)($data['district_id'] ?? 0);
        }
        $details_query->close();
    }
}

// Active Filter Parameters
$selected_year = isset($_GET['year']) ? intval($_GET['year']) : intval(date('Y'));
$active_tab = isset($_GET['tab']) && $_GET['tab'] === 'vehicle' ? 'vehicle' : 'health';

// 1. Fetch Health Certificates (Tab 1)
$hc_records = [];
$total_hc_animals = 0;
if (!empty($range_id)) {
    $hc_sql = "
        SELECT id, report_year, report_month, health_certificate_no, farmer_nic, applicant_name_address, 
               farm_registration_no, date_of_issue, species, total_animals, selected_animals_json,
               vehicle_fitness_certificate_no, purpose 
        FROM health_certificate_issues 
        WHERE range_id = ? AND report_year = ? 
        ORDER BY date_of_issue DESC, id DESC
    ";
    $hc_stmt = $mysqli->prepare($hc_sql);
    if ($hc_stmt) {
        $hc_stmt->bind_param("ii", $range_id, $selected_year);
        $hc_stmt->execute();
        $hc_res = $hc_stmt->get_result();
        while ($row = $hc_res->fetch_assoc()) {
            $hc_records[] = $row;
            $total_hc_animals += (int)$row['total_animals'];
        }
        $hc_stmt->close();
    }
}

// 2. Fetch Vehicle Fitness Certificates (Tab 2)
$vfc_records = [];
$vfc_approved_count = 0;
$vfc_rejected_count = 0;
if (!empty($range_id)) {
    $vfc_sql = "
        SELECT id, report_year, report_month, certificate_no, health_certificate_id, health_certificate_no,
               farm_registration_no, farmer_nic, applicant_name, applicant_address, vehicle_registration_no,
               vehicle_type, driver_name, driver_license_no, animal_species, total_animals, issue_date, valid_until,
               from_location, to_location, cond_02_easy_observance, cond_03_ramp, cond_04_floor_holes,
               cond_05_adequate_covering, cond_06_ventilation, cond_07_max_animals, approval_status, remarks
        FROM vehicle_fitness_certificates
        WHERE range_id = ? AND report_year = ?
        ORDER BY issue_date DESC, id DESC
    ";
    $vfc_stmt = $mysqli->prepare($vfc_sql);
    if ($vfc_stmt) {
        $vfc_stmt->bind_param("ii", $range_id, $selected_year);
        $vfc_stmt->execute();
        $vfc_res = $vfc_stmt->get_result();
        while ($vrow = $vfc_res->fetch_assoc()) {
            $vfc_records[] = $vrow;
            if ($vrow['approval_status'] === 'Approved') {
                $vfc_approved_count++;
            } else {
                $vfc_rejected_count++;
            }
        }
        $vfc_stmt->close();
    }
}

$pageTitle = "Health & Vehicle Fitness Certificates | DAPH EP MIS";
require_once '../../../includes/header.php';
?>

<link rel="stylesheet" href="../../../assets/css/bootstrap-icons.min.css">
<link rel="stylesheet" href="../../../assets/css/dataTables.bootstrap5.min.css">
<link rel="stylesheet" href="../../../assets/css/buttons.bootstrap5.min.css">
<link rel="stylesheet" href="../../../assets/css/sweetalert2.min.css">
<link rel="stylesheet" href="../../../assets/css/veterinary.css">

<style>
.nav-pills .nav-link {
    font-weight: 600;
    color: #4b5563;
    border-radius: 8px;
    padding: 0.65rem 1.4rem;
    transition: all 0.2s ease-in-out;
}
.nav-pills .nav-link.active {
    background: linear-gradient(135deg, #370709 0%, #820100 100%) !important;
    color: #ffffff !important;
    box-shadow: 0 4px 12px rgba(130, 1, 0, 0.25);
}
.nav-pills .nav-link#tab-vehicle-nav.active {
    background: linear-gradient(135deg, #1e3a8a 0%, #2563eb 100%) !important;
    color: #ffffff !important;
    box-shadow: 0 4px 12px rgba(37, 99, 235, 0.25);
}
.kpi-card {
    transition: transform 0.2s ease, box-shadow 0.2s ease;
    border-radius: 12px;
}
.kpi-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 18px rgba(0, 0, 0, 0.08) !important;
}
.animal-pick-card {
    cursor: pointer;
    transition: all 0.15s ease-in-out;
    word-break: break-word;
    user-select: none;
}
.animal-pick-card:hover {
    border-color: #820100 !important;
    background-color: #fff1f2 !important;
}
.animal-pick-card.selected {
    border-color: #820100 !important;
    background-color: #fee2e2 !important;
}
@media (max-width: 991.98px) {
    .modal-fullscreen-lg-down {
        width: 100vw;
        max-width: none;
        height: 100%;
        margin: 0;
    }
    .modal-fullscreen-lg-down .modal-content {
        height: 100%;
        border: 0;
        border-radius: 0;
    }
}
.table-responsive::-webkit-scrollbar {
    height: 6px;
    width: 6px;
}
.table-responsive::-webkit-scrollbar-thumb {
    background: #cbd5e1;
    border-radius: 4px;
}
.table-responsive::-webkit-scrollbar-thumb:hover {
    background: #94a3b8;
}
</style>

<div class="container-fluid py-4 px-3 px-xl-5">

    <!-- PAGE HEADER -->
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
        <div>
            <div class="d-flex align-items-center gap-2 mb-1">
                <span class="badge bg-danger-subtle text-danger border border-danger-subtle px-2 py-1 font-monospace">SCHEDULE 03 &amp; 04</span>
                <span class="badge bg-secondary-subtle text-secondary border px-2 py-1"><i class="bi bi-geo-alt-fill me-1"></i><?= htmlspecialchars($range_name) ?> (<?= htmlspecialchars($district_name) ?>)</span>
            </div>
            <h2 class="h3 fw-bold mb-0 text-dark">Animal Health &amp; Vehicle Fitness Certificates</h2>
            <p class="text-muted small mb-0">Statutory transportation certificates, animal identification inspection (Schedule-03), and vehicle transit fitness approval (Schedule-04).</p>
        </div>

        <div class="d-flex align-items-center gap-2 flex-wrap">
            <form method="GET" class="d-flex align-items-center gap-2">
                <input type="hidden" name="tab" value="<?= htmlspecialchars($active_tab) ?>">
                <label class="small fw-bold text-muted mb-0">Year:</label>
                <select name="year" class="form-select form-select-sm fw-bold font-monospace" onchange="this.form.submit()" style="width: 105px;">
                    <?php
                    $curr_year = intval(date('Y'));
                    for ($y = $curr_year - 4; $y <= $curr_year + 3; $y++) {
                        $sel = ($y === $selected_year) ? 'selected' : '';
                        echo "<option value=\"$y\" $sel>$y</option>";
                    }
                    ?>
                </select>
            </form>

            <a href="ear_tag_balance.php" class="btn btn-sm btn-outline-secondary shadow-sm text-nowrap">
                <i class="bi bi-tags-fill me-1"></i>Ear Tag Register
            </a>
            <a href="monthly-annual-reports.php" class="btn btn-sm btn-secondary shadow-sm text-nowrap">
                <i class="bi bi-arrow-left me-1"></i>Back to Reports
            </a>
        </div>
    </div>

    <!-- MAIN TWO-TAB INTERFACE -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white border-bottom p-3">
            <ul class="nav nav-pills gap-2" id="certificateTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link <?= $active_tab === 'health' ? 'active' : '' ?>" id="tab-health-nav" data-bs-toggle="pill" data-bs-target="#pane-health" type="button" role="tab" aria-controls="pane-health" aria-selected="<?= $active_tab === 'health' ? 'true' : 'false' ?>">
                        <i class="bi bi-file-earmark-medical me-2"></i>Tab 1: Issue Health Certificate (Schedule - 03)
                        <span class="badge bg-light text-dark ms-2"><?= count($hc_records) ?></span>
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link <?= $active_tab === 'vehicle' ? 'active' : '' ?>" id="tab-vehicle-nav" data-bs-toggle="pill" data-bs-target="#pane-vehicle" type="button" role="tab" aria-controls="pane-vehicle" aria-selected="<?= $active_tab === 'vehicle' ? 'true' : 'false' ?>">
                        <i class="bi bi-truck-front me-2"></i>Tab 2: Vehicle Fitness Certificates (Schedule - 04)
                        <span class="badge bg-light text-dark ms-2"><?= count($vfc_records) ?></span>
                    </button>
                </li>
            </ul>
        </div>

        <div class="card-body p-4 bg-light">
            <div class="tab-content" id="certificateTabsContent">

                <!-- ======================================================= -->
                <!-- TAB 1: ISSUE HEALTH CERTIFICATE (SCHEDULE - 03)        -->
                <!-- ======================================================= -->
                <div class="tab-pane fade <?= $active_tab === 'health' ? 'show active' : '' ?>" id="pane-health" role="tabpanel" aria-labelledby="tab-health-nav">
                    
                    <!-- KPI STATS CARDS (TAB 1) -->
                    <div class="row g-3 mb-4">
                        <div class="col-6 col-lg-3">
                            <div class="card kpi-card shadow-sm p-3 border-0 border-start border-4 border-danger h-100 bg-white">
                                <span class="text-muted small text-uppercase fw-bold">Health Certs Issued</span>
                                <h3 class="fw-bold my-1 text-danger font-monospace"><?= number_format(count($hc_records)) ?></h3>
                                <small class="text-muted"><i class="bi bi-calendar3 me-1"></i>Year <?= $selected_year ?></small>
                            </div>
                        </div>
                        <div class="col-6 col-lg-3">
                            <div class="card kpi-card shadow-sm p-3 border-0 border-start border-4 border-primary h-100 bg-white">
                                <span class="text-muted small text-uppercase fw-bold">Total Animals Certified</span>
                                <h3 class="fw-bold my-1 text-primary font-monospace"><?= number_format($total_hc_animals) ?></h3>
                                <small class="text-muted">Certified under Schedule-03</small>
                            </div>
                        </div>
                        <div class="col-6 col-lg-3">
                            <div class="card kpi-card shadow-sm p-3 border-0 border-start border-4 border-success h-100 bg-white">
                                <span class="text-muted small text-uppercase fw-bold">Counterfoil Integration</span>
                                <h3 class="fw-bold my-1 text-success font-monospace"><?= number_format(count($hc_records)) ?></h3>
                                <small class="text-success fw-semibold"><i class="bi bi-check-circle me-1"></i>Synced with Leaf Records</small>
                            </div>
                        </div>
                        <div class="col-6 col-lg-3">
                            <div class="card kpi-card shadow-sm p-3 border-0 border-start border-4 border-dark h-100 bg-white">
                                <span class="text-muted small text-uppercase fw-bold">Active Range</span>
                                <h5 class="fw-bold my-1 text-dark text-truncate"><?= htmlspecialchars($range_name) ?></h5>
                                <small class="text-muted"><?= htmlspecialchars($district_name) ?> District</small>
                            </div>
                        </div>
                    </div>

                    <!-- ACTION BAR & TABLE WRAPPER (TAB 1) -->
                    <div class="card border-0 shadow-sm">
                        <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center flex-wrap gap-2 border-bottom">
                            <div>
                                <h5 class="fw-bold text-dark mb-0"><i class="bi bi-file-earmark-medical me-2 text-danger"></i>Health Certificates Registry (Schedule - 03)</h5>
                                <small class="text-muted">Issued animal transportation health certificates with full animal identification logs</small>
                            </div>
                            <div class="d-flex gap-2">
                                <button type="button" class="btn text-white btn-sm shadow-sm" style="background-color: #820100;" data-bs-toggle="modal" data-bs-target="#modalIssueHealthCert" onclick="prepareHealthCertModal(false);">
                                    <i class="bi bi-plus-circle me-1"></i>Issue New Health Certificate
                                </button>
                            </div>
                        </div>

                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-hover align-middle mb-0" id="healthCertTable" style="min-width: 1250px;">
                                    <thead class="table-light text-secondary small">
                                        <tr>
                                            <th class="text-center" style="width: 45px;">#</th>
                                            <th>Health Cert No.</th>
                                            <th>Issue Date</th>
                                            <th>Farmer NIC</th>
                                            <th>Applicant Name &amp; Address</th>
                                            <th>Farm Reg No.</th>
                                            <th>Species</th>
                                            <th class="text-center">Total Animals</th>
                                            <th>Vehicle Cert No.</th>
                                            <th>Purpose</th>
                                            <th class="text-center" style="width: 120px;">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody class="small">
                                        <?php if (empty($hc_records)): ?>
                                            <tr>
                                                <td colspan="11" class="text-center py-5 text-muted">
                                                    <i class="bi bi-inbox fs-2 d-block mb-2 text-secondary opacity-50"></i>
                                                    No Health Certificates found for year <?= $selected_year ?>.
                                                    <div class="mt-2">
                                                        <button type="button" class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#modalIssueHealthCert" onclick="prepareHealthCertModal(false);">
                                                            <i class="bi bi-plus-circle me-1"></i>Issue First Health Certificate
                                                        </button>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php else: ?>
                                            <?php foreach ($hc_records as $idx => $r): ?>
                                                <tr>
                                                    <td class="text-center text-muted fw-bold"><?= $idx + 1 ?></td>
                                                    <td>
                                                        <span class="font-monospace fw-bold text-danger bg-danger-subtle px-2 py-1 rounded border border-danger-subtle d-inline-block">
                                                            <?= htmlspecialchars($r['health_certificate_no']) ?>
                                                        </span>
                                                    </td>
                                                    <td class="text-nowrap text-secondary font-monospace"><i class="bi bi-calendar-event me-1"></i><?= htmlspecialchars($r['date_of_issue']) ?></td>
                                                    <td><span class="font-monospace fw-bold text-dark"><?= htmlspecialchars($r['farmer_nic'] ?: '—') ?></span></td>
                                                    <td>
                                                        <strong class="text-dark d-block"><?= htmlspecialchars(strtok($r['applicant_name_address'], "\n")) ?></strong>
                                                        <small class="text-muted"><?= htmlspecialchars(substr($r['applicant_name_address'], strlen(strtok($r['applicant_name_address'], "\n")) + 1) ?: '—') ?></small>
                                                    </td>
                                                    <td>
                                                        <?php if (!empty($r['farm_registration_no'])): ?>
                                                            <span class="badge bg-light text-primary border font-monospace"><?= htmlspecialchars($r['farm_registration_no']) ?></span>
                                                        <?php else: ?>
                                                            <span class="text-muted">—</span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td><span class="badge bg-secondary-subtle text-secondary"><?= htmlspecialchars($r['species'] ?: 'Cattle') ?></span></td>
                                                    <td class="text-center">
                                                        <span class="badge bg-danger fs-6 px-2 py-1 font-monospace" title="Schedule-03 Total Selected Animals">
                                                            <?= number_format($r['total_animals']) ?>
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <?php if (!empty($r['vehicle_fitness_certificate_no'])): ?>
                                                            <span class="font-monospace fw-bold text-primary"><?= htmlspecialchars($r['vehicle_fitness_certificate_no']) ?></span>
                                                        <?php else: ?>
                                                            <span class="text-muted small">Not Linked</span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td><?= htmlspecialchars($r['purpose'] ?: 'Transport') ?></td>
                                                    <td class="text-center text-nowrap">
                                                        <div class="btn-group btn-group-sm">
                                                            <button type="button" class="btn btn-outline-warning text-dark btn-print-hc" data-id="<?= $r['id'] ?>" title="View &amp; Print Official Schedule - 03 Form">
                                                                <i class="bi bi-printer"></i>
                                                            </button>
                                                            <button type="button" class="btn btn-outline-primary btn-edit-hc" data-id="<?= $r['id'] ?>" title="Edit Health Certificate">
                                                                <i class="bi bi-pencil-square"></i>
                                                            </button>
                                                            <button type="button" class="btn btn-outline-danger btn-delete-hc" data-id="<?= $r['id'] ?>" data-no="<?= htmlspecialchars($r['health_certificate_no']) ?>" title="Delete Record">
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

                </div>

                <!-- ======================================================= -->
                <!-- TAB 2: VEHICLE FITNESS CERTIFICATES (SCHEDULE - 04)    -->
                <!-- ======================================================= -->
                <div class="tab-pane fade <?= $active_tab === 'vehicle' ? 'show active' : '' ?>" id="pane-vehicle" role="tabpanel" aria-labelledby="tab-vehicle-nav">
                    
                    <!-- KPI STATS CARDS (TAB 2) -->
                    <div class="row g-3 mb-4">
                        <div class="col-6 col-lg-3">
                            <div class="card kpi-card shadow-sm p-3 border-0 border-start border-4 border-primary h-100 bg-white">
                                <span class="text-muted small text-uppercase fw-bold">Vehicle Inspections</span>
                                <h3 class="fw-bold my-1 text-primary font-monospace"><?= number_format(count($vfc_records)) ?></h3>
                                <small class="text-muted"><i class="bi bi-calendar3 me-1"></i>Year <?= $selected_year ?></small>
                            </div>
                        </div>
                        <div class="col-6 col-lg-3">
                            <div class="card kpi-card shadow-sm p-3 border-0 border-start border-4 border-success h-100 bg-white">
                                <span class="text-muted small text-uppercase fw-bold">Transports Approved</span>
                                <h3 class="fw-bold my-1 text-success font-monospace"><?= number_format($vfc_approved_count) ?></h3>
                                <small class="text-success fw-semibold"><i class="bi bi-check2-circle me-1"></i>Passed Conditions 02-07</small>
                            </div>
                        </div>
                        <div class="col-6 col-lg-3">
                            <div class="card kpi-card shadow-sm p-3 border-0 border-start border-4 border-danger h-100 bg-white">
                                <span class="text-muted small text-uppercase fw-bold">Rejected / Conditional</span>
                                <h3 class="fw-bold my-1 text-danger font-monospace"><?= number_format($vfc_rejected_count) ?></h3>
                                <small class="text-danger fw-semibold"><i class="bi bi-x-circle me-1"></i>Failed Welfare Standards</small>
                            </div>
                        </div>
                        <div class="col-6 col-lg-3">
                            <div class="card kpi-card shadow-sm p-3 border-0 border-start border-4 border-info h-100 bg-white">
                                <span class="text-muted small text-uppercase fw-bold">Counterfoil Integration</span>
                                <h3 class="fw-bold my-1 text-info font-monospace"><?= number_format(count($vfc_records)) ?></h3>
                                <small class="text-muted">Type: Animal Transport</small>
                            </div>
                        </div>
                    </div>

                    <!-- ACTION BAR & TABLE WRAPPER (TAB 2) -->
                    <div class="card border-0 shadow-sm">
                        <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center flex-wrap gap-2 border-bottom">
                            <div>
                                <h5 class="fw-bold text-dark mb-0"><i class="bi bi-truck-front me-2 text-primary"></i>Vehicle Fitness Certificates Registry (Schedule - 04)</h5>
                                <small class="text-muted">Livestock transit vehicle inspection and compliance verification checklist (Conditions 02 to 07)</small>
                            </div>
                            <div class="d-flex gap-2">
                                <button type="button" class="btn btn-primary btn-sm shadow-sm" data-bs-toggle="modal" data-bs-target="#modalIssueVehicleFitness" onclick="prepareVehicleFitnessModal(false);">
                                    <i class="bi bi-plus-circle me-1"></i>Issue Vehicle Fitness Certificate
                                </button>
                            </div>
                        </div>

                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-hover align-middle mb-0" id="vehicleFitnessTable" style="min-width: 1300px;">
                                    <thead class="table-light text-secondary small">
                                        <tr>
                                            <th class="text-center" style="width: 45px;">#</th>
                                            <th>Vehicle Cert No.</th>
                                            <th>Date of Issue</th>
                                            <th>Linked Health Cert</th>
                                            <th>Applicant / Owner</th>
                                            <th>Vehicle Reg No.</th>
                                            <th>Vehicle Type</th>
                                            <th>Species &amp; Quantity</th>
                                            <th>Route (From &rarr; To)</th>
                                            <th class="text-center">Conditions 02-07</th>
                                            <th class="text-center">Approval</th>
                                            <th class="text-center" style="width: 120px;">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody class="small">
                                        <?php if (empty($vfc_records)): ?>
                                            <tr>
                                                <td colspan="12" class="text-center py-5 text-muted">
                                                    <i class="bi bi-truck fs-2 d-block mb-2 text-secondary opacity-50"></i>
                                                    No Vehicle Fitness Certificates found for year <?= $selected_year ?>.
                                                    <div class="mt-2">
                                                        <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#modalIssueVehicleFitness" onclick="prepareVehicleFitnessModal(false);">
                                                            <i class="bi bi-plus-circle me-1"></i>Issue First Vehicle Fitness Certificate
                                                        </button>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php else: ?>
                                            <?php foreach ($vfc_records as $vidx => $vr): ?>
                                                <?php 
                                                $all_pass = ($vr['cond_02_easy_observance'] === 'Yes' && $vr['cond_03_ramp'] === 'Yes' && $vr['cond_04_floor_holes'] === 'Yes' && $vr['cond_05_adequate_covering'] === 'Yes' && $vr['cond_06_ventilation'] === 'Yes' && $vr['cond_07_max_animals'] === 'Yes');
                                                ?>
                                                <tr>
                                                    <td class="text-center text-muted fw-bold"><?= $vidx + 1 ?></td>
                                                    <td>
                                                        <span class="font-monospace fw-bold text-primary bg-primary-subtle px-2 py-1 rounded border border-primary-subtle d-inline-block">
                                                            <?= htmlspecialchars($vr['certificate_no']) ?>
                                                        </span>
                                                    </td>
                                                    <td class="text-nowrap text-secondary font-monospace"><i class="bi bi-calendar-event me-1"></i><?= htmlspecialchars($vr['issue_date']) ?></td>
                                                    <td>
                                                        <?php if (!empty($vr['health_certificate_no'])): ?>
                                                            <span class="font-monospace fw-semibold text-danger"><?= htmlspecialchars($vr['health_certificate_no']) ?></span>
                                                        <?php else: ?>
                                                            <span class="text-muted">—</span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td>
                                                        <strong class="text-dark d-block"><?= htmlspecialchars($vr['applicant_name']) ?></strong>
                                                        <small class="text-muted font-monospace"><?= htmlspecialchars($vr['farmer_nic'] ?: $vr['farm_registration_no'] ?: '') ?></small>
                                                    </td>
                                                    <td>
                                                        <strong class="font-monospace text-uppercase text-dark"><?= htmlspecialchars($vr['vehicle_registration_no']) ?></strong>
                                                    </td>
                                                    <td><?= htmlspecialchars($vr['vehicle_type'] ?: 'Lorry / Truck') ?></td>
                                                    <td>
                                                        <span class="badge bg-light text-dark border font-monospace">
                                                            <?= htmlspecialchars($vr['total_animals']) ?> <?= htmlspecialchars($vr['animal_species']) ?>
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <small class="d-block text-truncate" style="max-width: 160px;" title="<?= htmlspecialchars($vr['from_location']) ?> &rarr; <?= htmlspecialchars($vr['to_location']) ?>">
                                                            <?= htmlspecialchars($vr['from_location'] ?: 'Origin') ?> &rarr; <?= htmlspecialchars($vr['to_location'] ?: 'Dest') ?>
                                                        </small>
                                                    </td>
                                                    <td class="text-center">
                                                        <?php if ($all_pass): ?>
                                                            <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1" title="All conditions 02-07 satisfied">
                                                                <i class="bi bi-check-all me-1"></i>6/6 Pass
                                                            </span>
                                                        <?php else: ?>
                                                            <span class="badge bg-danger-subtle text-danger border border-danger-subtle px-2 py-1" title="One or more conditions failed">
                                                                <i class="bi bi-exclamation-triangle-fill me-1"></i>Failed Checklist
                                                            </span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td class="text-center">
                                                        <?php if ($vr['approval_status'] === 'Approved'): ?>
                                                            <span class="badge bg-success px-2 py-1"><i class="bi bi-check-circle-fill me-1"></i>Approved</span>
                                                        <?php else: ?>
                                                            <span class="badge bg-danger px-2 py-1"><i class="bi bi-x-circle-fill me-1"></i>Rejected</span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td class="text-center text-nowrap">
                                                        <div class="btn-group btn-group-sm">
                                                            <button type="button" class="btn btn-outline-warning text-dark btn-print-vfc" data-id="<?= $vr['id'] ?>" title="View &amp; Print Official Schedule - 04 Form">
                                                                <i class="bi bi-printer"></i>
                                                            </button>
                                                            <button type="button" class="btn btn-outline-primary btn-edit-vfc" data-id="<?= $vr['id'] ?>" title="Edit Vehicle Fitness Certificate">
                                                                <i class="bi bi-pencil-square"></i>
                                                            </button>
                                                            <button type="button" class="btn btn-outline-danger btn-delete-vfc" data-id="<?= $vr['id'] ?>" data-no="<?= htmlspecialchars($vr['certificate_no']) ?>" title="Delete Record">
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

                </div>

            </div>
        </div>
    </div>

</div>

<!-- ============================================================ -->
<!-- MODAL INCLUSIONS                                             -->
<!-- ============================================================ -->
<?php include __DIR__ . '/model/modal_issue_health_certificate.php'; ?>
<?php include __DIR__ . '/model/modal_print_schedule03.php'; ?>
<?php include __DIR__ . '/model/modal_issue_vehicle_fitness.php'; ?>
<?php include __DIR__ . '/model/modal_print_schedule04.php'; ?>

<?php
ob_start();
?>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
// Global Scope State
let loadedFarmerAnimals = [];
let selectedAnimalsSet = new Set();
let hcCustomRowIdx = 0;

$(document).ready(function() {

    // Tab URL parameter synchronization without page reload
    $('#certificateTabs button[data-bs-toggle="pill"]').on('shown.bs.tab', function(e) {
        const target = $(e.target).attr("data-bs-target");
        const tabParam = (target.indexOf("vehicle") !== -1) ? "vehicle" : "health";
        const newUrl = new URL(window.location.href);
        newUrl.searchParams.set("tab", tabParam);
        window.history.replaceState(null, "", newUrl.toString());

        // Adjust column layouts for DataTables
        $.fn.dataTable.tables({ visible: true, api: true }).columns.adjust();
    });

    // 1. DataTables: Health Certificates Table
    if ($("#healthCertTable").length && $("#healthCertTable tbody tr td[colspan]").length === 0) {
        if ($.fn.DataTable.isDataTable("#healthCertTable")) {
            $("#healthCertTable").DataTable().destroy();
        }
        $("#healthCertTable").DataTable({
            pageLength: 15,
            lengthMenu: [10, 15, 25, 50],
            order: [[2, "desc"]],
            columnDefs: [{ orderable: false, targets: [0, 10] }],
            dom: '<"d-flex justify-content-between align-items-center mb-3"Bf>rt<"d-flex justify-content-between align-items-center mt-3"ip>',
            buttons: [
                { extend: "csv", text: '<i class="bi bi-file-earmark-spreadsheet me-1"></i>CSV', className: "btn btn-sm btn-success me-2" },
                { extend: "pdf", text: '<i class="bi bi-file-pdf me-1"></i>PDF', className: "btn btn-sm btn-danger me-2" },
                { extend: "print", text: '<i class="bi bi-printer me-1"></i>Print List', className: "btn btn-sm btn-dark" }
            ]
        });
    }

    // 2. DataTables: Vehicle Fitness Table
    if ($("#vehicleFitnessTable").length && $("#vehicleFitnessTable tbody tr td[colspan]").length === 0) {
        if ($.fn.DataTable.isDataTable("#vehicleFitnessTable")) {
            $("#vehicleFitnessTable").DataTable().destroy();
        }
        $("#vehicleFitnessTable").DataTable({
            pageLength: 15,
            lengthMenu: [10, 15, 25, 50],
            order: [[2, "desc"]],
            columnDefs: [{ orderable: false, targets: [0, 9, 10, 11] }],
            dom: '<"d-flex justify-content-between align-items-center mb-3"Bf>rt<"d-flex justify-content-between align-items-center mt-3"ip>',
            buttons: [
                { extend: "csv", text: '<i class="bi bi-file-earmark-spreadsheet me-1"></i>CSV', className: "btn btn-sm btn-success me-2" },
                { extend: "pdf", text: '<i class="bi bi-file-pdf me-1"></i>PDF', className: "btn btn-sm btn-danger me-2" },
                { extend: "print", text: '<i class="bi bi-printer me-1"></i>Print List', className: "btn btn-sm btn-dark" }
            ]
        });
    }

    // =========================================================================
    // TAB 1: HEALTH CERTIFICATE JAVASCRIPT LOGIC
    // =========================================================================

    // Lookup Farmer by NIC or Farm Reg No
    function performHcFarmerLookup(queryVal) {
        queryVal = (queryVal || "").trim();
        if (!queryVal) return;

        const btn = $("#btnHcLookup");
        btn.prop("disabled", true).html('<span class="spinner-border spinner-border-sm me-1"></span><span class="d-none d-sm-inline">Lookup</span>');

        $("#hcLookupBadge").attr("class", "badge bg-info-subtle text-info border border-info-subtle px-2 py-1 text-wrap")
                           .html('<span class="spinner-border spinner-border-sm me-1"></span>Verifying...');
        $("#hc_lookup_status").html('<span class="text-primary"><span class="spinner-border spinner-border-sm me-1"></span>Locating farmer and registered animals...</span>');

        $.ajax({
            url: "processors/health_certificate_crud.php?action=lookup_farmer",
            type: "GET",
            data: { query: queryVal },
            dataType: "json",
            success: function(resp) {
                btn.prop("disabled", false).html('<i class="bi bi-search me-1"></i>Lookup');
                if (resp.success && resp.farmer) {
                    const f = resp.farmer;
                    $("#hc_applicant_name").val(f.farmer_name || "");
                    $("#hc_farmer_nic").val(f.farmer_nic || "");
                    $("#hc_farm_reg_no").val(f.farm_reg_no || "");
                    $("#hc_applicant_address").val(f.location_address || "");
                    $("#hcLookupBadge").attr("class", "badge bg-success-subtle text-success border border-success-subtle px-2 py-1 text-wrap")
                                       .html(`<i class="bi bi-check-circle-fill me-1"></i>Verified: ${f.farmer_name}`);
                    $("#hc_lookup_status").html(`<span class="text-success"><i class="bi bi-check-circle-fill me-1"></i>Farmer profile loaded (${f.vs_range || 'Assigned Range'}).</span>`);

                    // Load animals for group selection
                    loadedFarmerAnimals = resp.animals || [];
                    renderHcAnimalPicker(loadedFarmerAnimals);
                } else {
                    $("#hcLookupBadge").attr("class", "badge bg-warning-subtle text-dark border border-warning-subtle px-2 py-1 text-wrap")
                                       .html('<i class="bi bi-exclamation-triangle me-1"></i>Manual Entry Mode');
                    $("#hc_lookup_status").html('<span class="text-warning-emphasis"><i class="bi bi-info-circle me-1"></i>Farmer not found in master records. Enter manual details below.</span>');
                    loadedFarmerAnimals = [];
                    renderHcAnimalPicker([]);
                }
            },
            error: function() {
                btn.prop("disabled", false).html('<i class="bi bi-search me-1"></i>Lookup');
                $("#hcLookupBadge").attr("class", "badge bg-danger text-white px-2 py-1 text-wrap").text("Lookup Error");
                $("#hc_lookup_status").html('<span class="text-danger"><i class="bi bi-x-circle me-1"></i>Communication error connecting to server. Please try again or enter details manually.</span>');
            }
        });
    }

    let hcLookupTimer = null;
    $(document).on("input", "#hc_lookup_input", function() {
        const val = $(this).val().trim();
        clearTimeout(hcLookupTimer);
        if (val.length >= 3) {
            hcLookupTimer = setTimeout(function() {
                performHcFarmerLookup(val);
            }, 350);
        }
    });

    $(document).on("keydown", "#hc_lookup_input", function(e) {
        if (e.key === "Enter" || e.keyCode === 13) {
            e.preventDefault();
            clearTimeout(hcLookupTimer);
            performHcFarmerLookup($(this).val().trim());
        }
    });

    $(document).on("click", "#btnHcLookup", function() {
        performHcFarmerLookup($("#hc_lookup_input").val().trim());
    });

    // Render Farmer's Registered Animals Picker
    function renderHcAnimalPicker(animals) {
        const container = $("#hc_animal_picker_grid");
        const placeholder = $("#hc_animal_picker_placeholder");

        if (!animals || animals.length === 0) {
            container.addClass("d-none").empty();
            placeholder.removeClass("d-none").html('<i class="bi bi-info-circle me-1"></i>No identified ear tags found for this farmer. Use "Add Custom Row" below to manually add animals.');
            return;
        }

        placeholder.addClass("d-none");
        container.removeClass("d-none").empty();

        animals.forEach((an, idx) => {
            const isChecked = selectedAnimalsSet.has(String(an.ear_tag_no));
            const card = `
                <div class="col-12 col-sm-6 col-md-4 col-lg-3">
                    <div class="p-2 rounded border animal-pick-card ${isChecked ? 'selected' : ''}" data-tag="${an.ear_tag_no}" data-idx="${idx}">
                        <div class="form-check mb-0">
                            <input class="form-check-input hc-animal-checkbox" type="checkbox" value="${an.ear_tag_no}" id="hc_chk_${idx}" ${isChecked ? 'checked' : ''}>
                            <label class="form-check-label w-100 cursor-pointer" for="hc_chk_${idx}">
                                <strong class="font-monospace text-primary d-block">${an.ear_tag_no}</strong>
                                <small class="text-muted d-block text-truncate">${an.species} | ${an.breed || 'Standard'}</small>
                                <small class="text-secondary d-block" style="font-size: 11px;">Age: ${an.age || '—'} | ${an.colour || '—'}</small>
                            </label>
                        </div>
                    </div>
                </div>
            `;
            container.append(card);
        });
    }

    // Toggle Animal Selection via Checkbox or Card Click
    $(document).on("change", ".hc-animal-checkbox", function() {
        const tag = $(this).val();
        const card = $(this).closest(".animal-pick-card");
        if ($(this).is(":checked")) {
            card.addClass("selected");
            selectedAnimalsSet.add(tag);
        } else {
            card.removeClass("selected");
            selectedAnimalsSet.delete(tag);
        }
        rebuildSchedule03Grid();
    });

    // Select All / Unselect All
    $(document).on("click", "#btnHcSelectAll", function() {
        $(".hc-animal-checkbox").prop("checked", true);
        $(".animal-pick-card").addClass("selected");
        loadedFarmerAnimals.forEach(an => selectedAnimalsSet.add(String(an.ear_tag_no)));
        rebuildSchedule03Grid();
    });

    $(document).on("click", "#btnHcUnselectAll", function() {
        $(".hc-animal-checkbox").prop("checked", false);
        $(".animal-pick-card").removeClass("selected");
        selectedAnimalsSet.clear();
        rebuildSchedule03Grid();
    });

    // Add Manual / Custom Row to Schedule - 03 Form Grid
    $(document).on("click", "#btnHcAddManualRow", function() {
        hcCustomRowIdx++;
        $("#hcNoAnimalsRow").remove();
        const curCount = $("#hcSchedule03Body tr.s03-animal-row").length;

        const tr = `
            <tr class="s03-animal-row" data-source="manual" data-id="custom_${hcCustomRowIdx}">
                <td class="text-center text-muted fw-bold s03-row-idx">${curCount + 1}</td>
                <td>
                    <input type="text" name="selected_animals[${curCount}][identification_no]" class="form-control form-control-sm font-monospace fw-bold text-uppercase" placeholder="Tag No / Brand" required>
                </td>
                <td>
                    <select name="selected_animals[${curCount}][species]" class="form-select form-select-sm">
                        <option value="Cattle" selected>Cattle</option>
                        <option value="Buffalo">Buffalo</option>
                        <option value="Goat">Goat</option>
                        <option value="Sheep">Sheep</option>
                        <option value="Swine">Swine</option>
                    </select>
                </td>
                <td>
                    <input type="text" name="selected_animals[${curCount}][breed]" class="form-control form-control-sm" placeholder="Breed">
                </td>
                <td>
                    <input type="text" name="selected_animals[${curCount}][age]" class="form-control form-control-sm" placeholder="Age">
                </td>
                <td>
                    <input type="text" name="selected_animals[${curCount}][colour]" class="form-control form-control-sm" placeholder="Colour">
                </td>
                <td>
                    <input type="text" name="selected_animals[${curCount}][specific_marks]" class="form-control form-control-sm" placeholder="Other specific marks">
                </td>
                <td class="text-center">
                    <button type="button" class="btn btn-sm btn-outline-danger py-0 px-1 btn-del-s03-row"><i class="bi bi-x"></i></button>
                </td>
            </tr>
        `;
        $("#hcSchedule03Body").append(tr);
        updateSchedule03SummaryCount();
    });

    // Remove row from Schedule - 03
    $(document).on("click", ".btn-del-s03-row", function() {
        const tr = $(this).closest("tr");
        const tag = tr.data("tag");
        if (tag) {
            selectedAnimalsSet.delete(String(tag));
            $(`.hc-animal-checkbox[value="${tag}"]`).prop("checked", false);
            $(`.animal-pick-card[data-tag="${tag}"]`).removeClass("selected");
        }
        tr.remove();
        if ($("#hcSchedule03Body tr.s03-animal-row").length === 0) {
            $("#hcSchedule03Body").html(`
                <tr id="hcNoAnimalsRow">
                    <td colspan="8" class="text-center py-4 text-muted">
                        <i class="bi bi-inbox fs-4 d-block mb-1 opacity-50"></i>
                        No animals selected yet. Select animals above or click "Add Custom Row".
                    </td>
                </tr>
            `);
        }
        updateSchedule03SummaryCount();
    });

    // Rebuild Schedule - 03 Grid from selectedAnimalsSet
    function rebuildSchedule03Grid() {
        const body = $("#hcSchedule03Body");
        // Retain manual custom rows
        const manualRows = body.find("tr.s03-animal-row[data-source='manual']").detach();
        body.empty();

        let rowCount = 0;
        selectedAnimalsSet.forEach(tag => {
            const an = loadedFarmerAnimals.find(item => String(item.ear_tag_no) === String(tag));
            if (an) {
                rowCount++;
                const tr = `
                    <tr class="s03-animal-row" data-source="picker" data-tag="${an.ear_tag_no}">
                        <td class="text-center text-muted fw-bold s03-row-idx">${rowCount}</td>
                        <td>
                            <input type="hidden" name="selected_animals[${rowCount - 1}][animal_id]" value="${an.id}">
                            <input type="text" name="selected_animals[${rowCount - 1}][identification_no]" class="form-control form-control-sm font-monospace fw-bold text-uppercase bg-light" value="${an.ear_tag_no}" readonly required>
                        </td>
                        <td>
                            <input type="text" name="selected_animals[${rowCount - 1}][species]" class="form-control form-control-sm bg-light" value="${an.species || 'Cattle'}" readonly>
                        </td>
                        <td>
                            <input type="text" name="selected_animals[${rowCount - 1}][breed]" class="form-control form-control-sm" value="${an.breed || ''}">
                        </td>
                        <td>
                            <input type="text" name="selected_animals[${rowCount - 1}][age]" class="form-control form-control-sm" value="${an.age || ''}">
                        </td>
                        <td>
                            <input type="text" name="selected_animals[${rowCount - 1}][colour]" class="form-control form-control-sm" value="${an.colour || ''}">
                        </td>
                        <td>
                            <input type="text" name="selected_animals[${rowCount - 1}][specific_marks]" class="form-control form-control-sm" value="${an.purpose ? 'Purpose: ' + an.purpose : ''}">
                        </td>
                        <td class="text-center">
                            <button type="button" class="btn btn-sm btn-outline-danger py-0 px-1 btn-del-s03-row"><i class="bi bi-x"></i></button>
                        </td>
                    </tr>
                `;
                body.append(tr);
            }
        });

        // Re-append custom manual rows
        if (manualRows.length) {
            body.append(manualRows);
        }

        if (body.find("tr.s03-animal-row").length === 0) {
            body.html(`
                <tr id="hcNoAnimalsRow">
                    <td colspan="8" class="text-center py-4 text-muted">
                        <i class="bi bi-inbox fs-4 d-block mb-1 opacity-50"></i>
                        No animals selected yet. Select animals above or click "Add Custom Row".
                    </td>
                </tr>
            `);
        }

        updateSchedule03SummaryCount();
    }

    function updateSchedule03SummaryCount() {
        const rows = $("#hcSchedule03Body tr.s03-animal-row");
        rows.each(function(i) {
            $(this).find(".s03-row-idx").text(i + 1);
            // Re-index inputs
            $(this).find("input, select").each(function() {
                const name = $(this).attr("name");
                if (name && name.startsWith("selected_animals[")) {
                    $(this).attr("name", name.replace(/selected_animals\[\d+\]/, `selected_animals[${i}]`));
                }
            });
        });
        const total = rows.length;
        $("#hc_selected_count_text").text(total);
        if (total > 0) {
            $("#hc_total_selected_badge").attr("class", "badge bg-danger fs-6 px-3 py-2");
        } else {
            $("#hc_total_selected_badge").attr("class", "badge bg-secondary fs-6 px-3 py-2");
        }
    }

    // Prepare Health Certificate Modal (New Record Mode)
    window.prepareHealthCertModal = function(isEdit) {
        if (!isEdit) {
            $("#formIssueHealthCert")[0].reset();
            $("#hc_edit_id").val("0");
            $("#modalIssueHealthCertLabel").text("Issue Animal Health Certificate (Schedule - 03)");
            $("#hc_date_of_issue").val(new Date().toISOString().split("T")[0]);
            $("#hcLookupBadge").attr("class", "badge bg-secondary-subtle text-secondary border px-2 py-1")
                               .html('<i class="bi bi-search me-1"></i>Enter NIC or Farm Reg No.');
            $("#hc_lookup_status").html('<i class="bi bi-info-circle me-1"></i>Type NIC or Farm Reg to auto-fill details and load registered animals.');
            loadedFarmerAnimals = [];
            selectedAnimalsSet.clear();
            renderHcAnimalPicker([]);
            $("#hcSchedule03Body").html(`
                <tr id="hcNoAnimalsRow">
                    <td colspan="8" class="text-center py-4 text-muted">
                        <i class="bi bi-inbox fs-4 d-block mb-1 opacity-50"></i>
                        No animals selected yet. Select animals above or click "Add Custom Row".
                    </td>
                </tr>
            `);
            updateSchedule03SummaryCount();

            // Auto-suggest next Health Certificate Number starting with HC-
            $("#hc_health_certificate_no").val("HC-" + Math.floor(100000 + Math.random() * 900000));
        }
    };

    // Submit Health Certificate Form
    window.submitHealthCertForm = function(e) {
        e.preventDefault();
        const totalAnimals = $("#hcSchedule03Body tr.s03-animal-row").length;
        if (totalAnimals === 0) {
            Swal.fire({
                icon: "warning",
                title: "No Animals Selected",
                text: "Please select or add at least one animal to the Schedule - 03 details grid.",
                confirmButtonColor: "#820100"
            });
            return;
        }

        Swal.fire({ title: "Saving Health Certificate...", allowOutsideClick: false, didOpen: () => Swal.showLoading() });

        const formData = $("#formIssueHealthCert").serialize();
        $.ajax({
            url: "processors/health_certificate_crud.php",
            type: "POST",
            data: formData,
            dataType: "json",
            success: function(resp) {
                Swal.close();
                if (resp.success) {
                    Swal.fire({
                        icon: "success",
                        title: "Certificate Issued!",
                        text: `Health Certificate ${resp.certificate_no} saved with ${resp.total_animals} animals and connected to counterfoils.`,
                        confirmButtonColor: "#820100"
                    }).then(() => {
                        window.location.href = "health_certificate.php?tab=health";
                    });
                } else {
                    Swal.fire("Error", resp.message || "Failed to save certificate.", "error");
                }
            },
            error: function(xhr) {
                Swal.close();
                const msg = (xhr.responseJSON && xhr.responseJSON.message) ? xhr.responseJSON.message : "Server communication error. Please try again.";
                Swal.fire("Error", msg, "error");
            }
        });
    };

    // Edit Health Certificate Handler
    $(document).on("click", ".btn-edit-hc", function() {
        const id = $(this).data("id");
        if (!id) return;

        Swal.fire({ title: "Loading...", allowOutsideClick: false, didOpen: () => Swal.showLoading() });

        $.ajax({
            url: "processors/health_certificate_crud.php?action=get_health_certificate",
            type: "GET",
            data: { id: id },
            dataType: "json",
            success: function(resp) {
                Swal.close();
                if (!resp.success || !resp.certificate) {
                    Swal.fire("Error", "Could not load certificate.", "error");
                    return;
                }
                const c = resp.certificate;
                $("#hc_edit_id").val(c.id);
                $("#modalIssueHealthCertLabel").text("Edit Health Certificate: " + c.health_certificate_no);
                $("#hc_date_of_issue").val(c.date_of_issue);
                $("#hc_health_certificate_no").val(c.health_certificate_no);
                $("#hc_vehicle_fitness_certificate_no").val(c.vehicle_fitness_certificate_no || "");
                $("#hc_species").val(c.species || "Cattle");
                $("#hc_purpose").val(c.purpose || "Transport");
                $("#hc_farmer_nic").val(c.farmer_nic || "");
                $("#hc_farm_reg_no").val(c.farm_registration_no || "");

                // Split applicant name and address
                const lines = (c.applicant_name_address || "").split("\n");
                $("#hc_applicant_name").val(lines[0] || "");
                $("#hc_applicant_address").val(lines.slice(1).join(" ") || "");

                // Load existing animals into Schedule - 03 Grid
                selectedAnimalsSet.clear();
                const body = $("#hcSchedule03Body").empty();
                const animals = c.animals || [];
                animals.forEach((an, idx) => {
                    const tr = `
                        <tr class="s03-animal-row" data-source="manual">
                            <td class="text-center text-muted fw-bold s03-row-idx">${idx + 1}</td>
                            <td>
                                <input type="text" name="selected_animals[${idx}][identification_no]" class="form-control form-control-sm font-monospace fw-bold text-uppercase" value="${an.identification_no}" required>
                            </td>
                            <td>
                                <input type="text" name="selected_animals[${idx}][species]" class="form-control form-control-sm" value="${an.species || c.species}">
                            </td>
                            <td>
                                <input type="text" name="selected_animals[${idx}][breed]" class="form-control form-control-sm" value="${an.breed || ''}">
                            </td>
                            <td>
                                <input type="text" name="selected_animals[${idx}][age]" class="form-control form-control-sm" value="${an.age || ''}">
                            </td>
                            <td>
                                <input type="text" name="selected_animals[${idx}][colour]" class="form-control form-control-sm" value="${an.colour || ''}">
                            </td>
                            <td>
                                <input type="text" name="selected_animals[${idx}][specific_marks]" class="form-control form-control-sm" value="${an.specific_marks || ''}">
                            </td>
                            <td class="text-center">
                                <button type="button" class="btn btn-sm btn-outline-danger py-0 px-1 btn-del-s03-row"><i class="bi bi-x"></i></button>
                            </td>
                        </tr>
                    `;
                    body.append(tr);
                });
                updateSchedule03SummaryCount();

                const mEl = document.getElementById("modalIssueHealthCert");
                bootstrap.Modal.getOrCreateInstance(mEl).show();
            },
            error: function() {
                Swal.close();
                Swal.fire("Error", "Server communication error.", "error");
            }
        });
    });

    // Delete Health Certificate Handler
    $(document).on("click", ".btn-delete-hc", function() {
        const id = $(this).data("id");
        const certNo = $(this).data("no");
        if (!id) return;

        Swal.fire({
            title: `Delete Certificate ${certNo}?`,
            text: "This will permanently remove the Health Certificate and delete its synchronized counterfoil leaf record.",
            icon: "warning",
            showCancelButton: true,
            confirmButtonColor: "#d33",
            confirmButtonText: "Yes, Delete"
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: "processors/health_certificate_crud.php?action=delete_health_certificate",
                    type: "POST",
                    data: { id: id },
                    dataType: "json",
                    success: function(resp) {
                        if (resp.success) {
                            Swal.fire({ icon: "success", title: "Deleted", text: resp.message, timer: 1500, showConfirmButton: false })
                                .then(() => window.location.href = "health_certificate.php?tab=health");
                        } else {
                            Swal.fire("Failed", resp.message || "Could not delete.", "error");
                        }
                    }
                });
            }
        });
    });

    // Printable Schedule - 03 Certificate Preview
    $(document).on("click", ".btn-print-hc", function() {
        const id = $(this).data("id");
        if (!id) return;

        $.ajax({
            url: "processors/health_certificate_crud.php?action=get_health_certificate",
            type: "GET",
            data: { id: id },
            dataType: "json",
            success: function(resp) {
                if (resp.success && resp.certificate) {
                    const c = resp.certificate;
                    $("#s03_cert_no").text(c.health_certificate_no);
                    $("#s03_date").text(c.date_of_issue);
                    $("#s03_range").text(c.range_name || "<?= htmlspecialchars($range_name) ?>");
                    $("#s03_district").text(c.district_name || "<?= htmlspecialchars($district_name) ?>");
                    $("#s03_applicant_name").text(c.applicant_name_address);
                    $("#s03_farmer_nic").text(c.farmer_nic || "—");
                    $("#s03_farm_reg_no").text(c.farm_registration_no || "—");
                    $("#s03_vehicle_cert").text(c.vehicle_fitness_certificate_no || "—");
                    $("#s03_purpose").text(c.purpose || "Transport");
                    $("#s03_surgeon_range").text(`${c.range_name || 'Ampara'} Veterinary Range`);

                    const tbody = $("#s03_animals_tbody").empty();
                    const animals = c.animals || [];
                    $("#s03_total_count").text(`${animals.length} Animals`);

                    if (animals.length === 0) {
                        tbody.html('<tr><td colspan="7" class="text-center py-3 text-muted">No animal identification rows attached.</td></tr>');
                    } else {
                        animals.forEach(an => {
                            tbody.append(`
                                <tr>
                                    <td class="fw-bold">${an.serial_no}</td>
                                    <td><strong class="font-monospace">${an.identification_no}</strong></td>
                                    <td>${an.species || 'Cattle'}</td>
                                    <td>${an.breed || '—'}</td>
                                    <td>${an.age || '—'}</td>
                                    <td>${an.colour || '—'}</td>
                                    <td>${an.specific_marks || '—'}</td>
                                </tr>
                            `);
                        });
                    }

                    const modalEl = document.getElementById("modalPrintSchedule03");
                    bootstrap.Modal.getOrCreateInstance(modalEl).show();
                }
            }
        });
    });

    window.printSchedule03 = function() {
        window.print();
    };

    // =========================================================================
    // TAB 2: VEHICLE FITNESS CERTIFICATE JAVASCRIPT LOGIC
    // =========================================================================

    // Populate Linked Health Certificate Dropdown for Tab 2
    function loadLinkedHcOptions() {
        $.ajax({
            url: "processors/health_certificate_crud.php?action=get_health_cert_options",
            type: "GET",
            dataType: "json",
            success: function(resp) {
                if (resp.success && resp.options) {
                    const sel = $("#vfc_linked_hc_select").empty();
                    sel.append('<option value="">-- Choose Issued Health Certificate --</option>');
                    resp.options.forEach(opt => {
                        sel.append(`
                            <option value="${opt.id}" 
                                    data-cert="${opt.cert_no}" 
                                    data-farmer="${opt.farmer_name}" 
                                    data-nic="${opt.farmer_nic}" 
                                    data-reg="${opt.farm_reg_no}" 
                                    data-species="${opt.species}" 
                                    data-count="${opt.total_animals}"
                                    data-vehicle="${opt.vehicle_cert_no}">
                                ${opt.cert_no} - ${opt.farmer_name} (${opt.total_animals} ${opt.species})
                            </option>
                        `);
                    });
                }
            }
        });
    }

    // Auto-populate upon selecting linked Health Certificate
    $(document).on("change", "#vfc_linked_hc_select", function() {
        const opt = $(this).find("option:selected");
        if (opt.val()) {
            $("#vfc_health_cert_id").val(opt.val());
            $("#vfc_health_cert_no").val(opt.data("cert") || "");
            $("#vfc_applicant_name").val(opt.data("farmer") || "");
            $("#vfc_farmer_nic").val(opt.data("nic") || "");
            $("#vfc_farm_reg_no").val(opt.data("reg") || "");
            $("#vfc_animal_species").val(opt.data("species") || "Cattle");
            $("#vfc_total_animals").val(opt.data("count") || 1);
            if (opt.data("vehicle")) {
                $("#vfc_certificate_no").val(opt.data("vehicle"));
            }
        }
    });

    // Lookup Farmer / Farm Reg for Vehicle Fitness
    $(document).on("click", "#btnVfcLookup", function() {
        const val = $("#vfc_lookup_input").val().trim();
        if (!val) return;
        $("#vfc_lookup_status").html('<span class="text-primary"><span class="spinner-border spinner-border-sm me-1"></span>Fetching...</span>');
        $.ajax({
            url: "processors/health_certificate_crud.php?action=lookup_farmer",
            type: "GET",
            data: { query: val },
            dataType: "json",
            success: function(resp) {
                if (resp.success && resp.farmer) {
                    const f = resp.farmer;
                    $("#vfc_applicant_name").val(f.farmer_name);
                    $("#vfc_farmer_nic").val(f.farmer_nic);
                    $("#vfc_farm_reg_no").val(f.farm_reg_no);
                    $("#vfc_applicant_address").val(f.location_address);
                    $("#vfc_lookup_status").html(`<span class="text-success"><i class="bi bi-check-circle-fill me-1"></i>Found: ${f.farmer_name}</span>`);
                } else {
                    $("#vfc_lookup_status").html('<span class="text-warning-emphasis"><i class="bi bi-info-circle me-1"></i>Farmer not found. Enter manually.</span>');
                }
            }
        });
    });

    // Condition Checklist (Conditions 02 to 07) Live Status & Approval Evaluation
    function evaluateVfcChecklist() {
        const conditions = ['02', '03', '04', '05', '06', '07'];
        let allPass = true;
        conditions.forEach(cond => {
            const val = $(`input[name="cond_${cond}_easy_observance"], input[name="cond_${cond}_ramp"], input[name="cond_${cond}_floor_holes"], input[name="cond_${cond}_adequate_covering"], input[name="cond_${cond}_ventilation"], input[name="cond_${cond}_max_animals"]`).filter(":checked").val();
            const pill = $(`#status_pill_${cond}`);
            if (val === 'Yes') {
                pill.html('<span class="badge bg-success-subtle text-success border border-success-subtle">Pass</span>');
            } else {
                pill.html('<span class="badge bg-danger-subtle text-danger border border-danger-subtle">Fail</span>');
                allPass = false;
            }
        });

        const liveBadge = $("#vfc_approval_live_badge");
        if (allPass) {
            liveBadge.html('<span class="badge bg-success fs-6 px-3 py-2"><i class="bi bi-check-circle-fill me-1"></i>Transport Approval Granted</span>');
        } else {
            liveBadge.html('<span class="badge bg-danger fs-6 px-3 py-2"><i class="bi bi-x-circle-fill me-1"></i>Approval Rejected / Ineligible</span>');
        }
    }

    $(document).on("change", ".vfc-cond-radio", evaluateVfcChecklist);

    // Prepare Vehicle Fitness Modal
    window.prepareVehicleFitnessModal = function(isEdit) {
        if (!isEdit) {
            $("#formIssueVehicleFitness")[0].reset();
            $("#vfc_edit_id").val("0");
            $("#modalIssueVehicleFitnessLabel").text("Issue Vehicle Fitness Certificate (Schedule - 04)");
            $("#vfc_issue_date").val(new Date().toISOString().split("T")[0]);
            const d = new Date();
            d.setDate(d.getDate() + 3);
            $("#vfc_valid_until").val(d.toISOString().split("T")[0]);
            $("#vfc_certificate_no").val("VFC-" + new Date().getFullYear() + "-" + Math.floor(1000 + Math.random() * 9000));
            loadLinkedHcOptions();
            evaluateVfcChecklist();
        }
    };

    // Submit Vehicle Fitness Form
    window.submitVehicleFitnessForm = function(e) {
        e.preventDefault();
        Swal.fire({ title: "Saving Vehicle Fitness Certificate...", allowOutsideClick: false, didOpen: () => Swal.showLoading() });

        const formData = $("#formIssueVehicleFitness").serialize();
        $.ajax({
            url: "processors/health_certificate_crud.php",
            type: "POST",
            data: formData,
            dataType: "json",
            success: function(resp) {
                Swal.close();
                if (resp.success) {
                    Swal.fire({
                        icon: resp.all_passed ? "success" : "warning",
                        title: resp.all_passed ? "Transport Approved!" : "Conditional / Rejected Record",
                        text: resp.message,
                        confirmButtonColor: resp.all_passed ? "#2563eb" : "#d97706"
                    }).then(() => {
                        window.location.href = "health_certificate.php?tab=vehicle";
                    });
                } else {
                    Swal.fire("Error", resp.message || "Failed to save certificate.", "error");
                }
            },
            error: function(xhr) {
                Swal.close();
                const msg = (xhr.responseJSON && xhr.responseJSON.message) ? xhr.responseJSON.message : "Server communication error. Please try again.";
                Swal.fire("Error", msg, "error");
            }
        });
    };

    // Edit Vehicle Fitness Certificate
    $(document).on("click", ".btn-edit-vfc", function() {
        const id = $(this).data("id");
        if (!id) return;

        Swal.fire({ title: "Loading...", allowOutsideClick: false, didOpen: () => Swal.showLoading() });

        $.ajax({
            url: "processors/health_certificate_crud.php?action=get_vehicle_fitness",
            type: "GET",
            data: { id: id },
            dataType: "json",
            success: function(resp) {
                Swal.close();
                if (!resp.success || !resp.certificate) {
                    Swal.fire("Error", "Could not load certificate.", "error");
                    return;
                }
                const v = resp.certificate;
                $("#vfc_edit_id").val(v.id);
                $("#modalIssueVehicleFitnessLabel").text("Edit Vehicle Fitness Certificate: " + v.certificate_no);
                $("#vfc_certificate_no").val(v.certificate_no);
                $("#vfc_health_cert_no").val(v.health_certificate_no || "");
                $("#vfc_health_cert_id").val(v.health_certificate_id || "");
                $("#vfc_farm_reg_no").val(v.farm_registration_no || "");
                $("#vfc_farmer_nic").val(v.farmer_nic || "");
                $("#vfc_applicant_name").val(v.applicant_name);
                $("#vfc_applicant_address").val(v.applicant_address || "");
                $("#vfc_vehicle_reg_no").val(v.vehicle_registration_no);
                $("#vfc_vehicle_type").val(v.vehicle_type || "Lorry / Truck");
                $("#vfc_driver_name").val(v.driver_name || "");
                $("#vfc_driver_license_no").val(v.driver_license_no || "");
                $("#vfc_animal_species").val(v.animal_species || "Cattle");
                $("#vfc_total_animals").val(v.total_animals || 1);
                $("#vfc_issue_date").val(v.issue_date);
                $("#vfc_valid_until").val(v.valid_until || "");
                $("#vfc_from_location").val(v.from_location || "");
                $("#vfc_to_location").val(v.to_location || "");
                $("#vfc_remarks").val(v.remarks || "");

                // Set radios for conditions 02-07
                $(`input[name="cond_02_easy_observance"][value="${v.cond_02_easy_observance}"]`).prop("checked", true);
                $(`input[name="cond_03_ramp"][value="${v.cond_03_ramp}"]`).prop("checked", true);
                $(`input[name="cond_04_floor_holes"][value="${v.cond_04_floor_holes}"]`).prop("checked", true);
                $(`input[name="cond_05_adequate_covering"][value="${v.cond_05_adequate_covering}"]`).prop("checked", true);
                $(`input[name="cond_06_ventilation"][value="${v.cond_06_ventilation}"]`).prop("checked", true);
                $(`input[name="cond_07_max_animals"][value="${v.cond_07_max_animals}"]`).prop("checked", true);
                evaluateVfcChecklist();

                const mEl = document.getElementById("modalIssueVehicleFitness");
                bootstrap.Modal.getOrCreateInstance(mEl).show();
            },
            error: function() {
                Swal.close();
                Swal.fire("Error", "Server communication error.", "error");
            }
        });
    });

    // Delete Vehicle Fitness Certificate
    $(document).on("click", ".btn-delete-vfc", function() {
        const id = $(this).data("id");
        const certNo = $(this).data("no");
        if (!id) return;

        Swal.fire({
            title: `Delete Vehicle Cert ${certNo}?`,
            text: "This will remove the Vehicle Fitness Certificate and unlink its counterfoil record.",
            icon: "warning",
            showCancelButton: true,
            confirmButtonColor: "#d33",
            confirmButtonText: "Yes, Delete"
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: "processors/health_certificate_crud.php?action=delete_vehicle_fitness",
                    type: "POST",
                    data: { id: id },
                    dataType: "json",
                    success: function(resp) {
                        if (resp.success) {
                            Swal.fire({ icon: "success", title: "Deleted", text: resp.message, timer: 1500, showConfirmButton: false })
                                .then(() => window.location.href = "health_certificate.php?tab=vehicle");
                        } else {
                            Swal.fire("Failed", resp.message || "Could not delete.", "error");
                        }
                    }
                });
            }
        });
    });

    // Printable Schedule - 04 Certificate Preview
    $(document).on("click", ".btn-print-vfc", function() {
        const id = $(this).data("id");
        if (!id) return;

        $.ajax({
            url: "processors/health_certificate_crud.php?action=get_vehicle_fitness",
            type: "GET",
            data: { id: id },
            dataType: "json",
            success: function(resp) {
                if (resp.success && resp.certificate) {
                    const v = resp.certificate;
                    $("#s04_cert_no").text(v.certificate_no);
                    $("#s04_date").text(v.issue_date);
                    $("#s04_range").text(v.range_name || "<?= htmlspecialchars($range_name) ?>");
                    $("#s04_district").text(v.district_name || "<?= htmlspecialchars($district_name) ?>");
                    $("#s04_health_cert").text(v.health_certificate_no || "—");
                    $("#s04_applicant_name").text(v.applicant_name);
                    $("#s04_farm_reg_no").text(v.farm_registration_no || "—");
                    $("#s04_vehicle_no").text(v.vehicle_registration_no);
                    $("#s04_vehicle_type").text(v.vehicle_type || "Lorry / Truck");
                    $("#s04_driver_info").text((v.driver_name || "—") + (v.driver_license_no ? ` (Lic: ${v.driver_license_no})` : ""));
                    $("#s04_load_spec").text(`${v.total_animals} ${v.animal_species}`);
                    $("#s04_from_loc").text(v.from_location || "Origin");
                    $("#s04_to_loc").text(v.to_location || "Destination");
                    $("#s04_surgeon_range").text(`${v.range_name || 'Ampara'} Veterinary Range`);

                    // Checklist results
                    $("#s04_cond_02").text(v.cond_02_easy_observance === 'Yes' ? 'PASS (Yes)' : 'FAIL (No)').attr("class", v.cond_02_easy_observance === 'Yes' ? 'fw-bold text-success' : 'fw-bold text-danger');
                    $("#s04_cond_03").text(v.cond_03_ramp === 'Yes' ? 'PASS (Yes)' : 'FAIL (No)').attr("class", v.cond_03_ramp === 'Yes' ? 'fw-bold text-success' : 'fw-bold text-danger');
                    $("#s04_cond_04").text(v.cond_04_floor_holes === 'Yes' ? 'PASS (Yes)' : 'FAIL (No)').attr("class", v.cond_04_floor_holes === 'Yes' ? 'fw-bold text-success' : 'fw-bold text-danger');
                    $("#s04_cond_05").text(v.cond_05_adequate_covering === 'Yes' ? 'PASS (Yes)' : 'FAIL (No)').attr("class", v.cond_05_adequate_covering === 'Yes' ? 'fw-bold text-success' : 'fw-bold text-danger');
                    $("#s04_cond_06").text(v.cond_06_ventilation === 'Yes' ? 'PASS (Yes)' : 'FAIL (No)').attr("class", v.cond_06_ventilation === 'Yes' ? 'fw-bold text-success' : 'fw-bold text-danger');
                    $("#s04_cond_07").text(v.cond_07_max_animals === 'Yes' ? 'PASS (Yes)' : 'FAIL (No)').attr("class", v.cond_07_max_animals === 'Yes' ? 'fw-bold text-success' : 'fw-bold text-danger');

                    const allPass = (v.cond_02_easy_observance === 'Yes' && v.cond_03_ramp === 'Yes' && v.cond_04_floor_holes === 'Yes' && v.cond_05_adequate_covering === 'Yes' && v.cond_06_ventilation === 'Yes' && v.cond_07_max_animals === 'Yes');
                    if (allPass) {
                        $("#s04_approval_text").text("TRANSPORTATION APPROVAL GRANTED").attr("class", "fw-bold mb-1 text-uppercase text-success");
                        $("#s04_endorsement_sub").text("The vehicle described above complies with all statutory animal welfare standards under Schedule - 04 regulations.");
                        $("#s04_endorsement_card").attr("class", "p-3 mb-4 rounded border border-success bg-success-subtle text-center");
                    } else {
                        $("#s04_approval_text").text("TRANSPORTATION APPROVAL REJECTED / CONDITIONAL").attr("class", "fw-bold mb-1 text-uppercase text-danger");
                        $("#s04_endorsement_sub").text("The vehicle failed one or more critical welfare conditions and is not certified for livestock transit.");
                        $("#s04_endorsement_card").attr("class", "p-3 mb-4 rounded border border-danger bg-danger-subtle text-center");
                    }

                    const modalEl = document.getElementById("modalPrintSchedule04");
                    bootstrap.Modal.getOrCreateInstance(modalEl).show();
                }
            }
        });
    });

    window.printSchedule04 = function() {
        window.print();
    };

});
</script>
<?php
$pageScripts = ob_get_clean();
require_once '../../../includes/footer.php';
?>
