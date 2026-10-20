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
if ($range_id <= 0) {
    $range_id = 1; // Default fallback to Range 1 (Ampara)
}

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
$active_tab = isset($_GET['tab']) && $_GET['tab'] === 'drugs' ? 'drugs' : 'clinics';

// -------------------------------------------------------------------------
// 1. Fetch Tab 1 Records: Mobile Clinics & Activities
// -------------------------------------------------------------------------
$clinic_records = [];
$kpi_total_clinics = 0;
$kpi_farmers_benefited = 0;
$kpi_animals_treated = 0;
$kpi_ai_done = 0;

$c_sql = "
    SELECT mc.*, vr.name AS range_name, d.name AS district_name
    FROM mobile_clinics mc
    LEFT JOIN veterinary_ranges vr ON vr.id = mc.range_id
    LEFT JOIN districts d ON d.id = mc.district_id
    WHERE mc.range_id = ? AND mc.report_year = ?
    ORDER BY mc.clinic_date DESC, mc.id DESC
";
$c_stmt = $mysqli->prepare($c_sql);
if ($c_stmt) {
    $c_stmt->bind_param("ii", $range_id, $selected_year);
    $c_stmt->execute();
    $c_res = $c_stmt->get_result();
    while ($row = $c_res->fetch_assoc()) {
        $clinic_records[] = $row;
        $kpi_total_clinics++;
        $kpi_farmers_benefited += (int)$row['farmers_benefited'];
        $kpi_animals_treated += (int)$row['animals_treated'];
        $kpi_ai_done += (int)$row['ai_done'];
    }
    $c_stmt->close();
}

// -------------------------------------------------------------------------
// 2. Fetch Tab 2 Records: Drug & Consumable Distribution Sessions
// -------------------------------------------------------------------------
$drug_records = [];
$kpi_total_doses_issued = 0.00;
$kpi_active_drug_types = 0;

$d_sql = "
    SELECT dd.*, vr.name AS range_name,
           (SELECT COUNT(*) FROM mobile_clinic_treatments t WHERE t.distribution_id = dd.id) AS treatment_count,
           (SELECT IFNULL(SUM(animals_treated), 0) FROM mobile_clinic_treatments t WHERE t.distribution_id = dd.id) AS total_patients
    FROM mobile_clinic_drug_distributions dd
    LEFT JOIN veterinary_ranges vr ON vr.id = dd.range_id
    WHERE dd.range_id = ? AND dd.report_year = ?
    ORDER BY dd.distribution_date DESC, dd.id DESC
";
$d_stmt = $mysqli->prepare($d_sql);
if ($d_stmt) {
    $d_stmt->bind_param("ii", $range_id, $selected_year);
    $d_stmt->execute();
    $d_res = $d_stmt->get_result();
    while ($d_row = $d_res->fetch_assoc()) {
        $drug_records[] = $d_row;
        $kpi_total_doses_issued += (float)$d_row['total_issued'];
    }
    $d_stmt->close();
}
$kpi_active_drug_types = count($drug_records);

require_once '../../../includes/header.php';
?>

<link rel="stylesheet" href="../../../assets/css/bootstrap-icons.min.css">
<link rel="stylesheet" href="../../../assets/css/dataTables.bootstrap5.min.css">

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
.nav-pills .nav-link#tab-drugs-nav.active {
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
@media print {
    body * {
        visibility: hidden;
    }
    #printableClinicArea, #printableClinicArea *,
    #printableDrugArea, #printableDrugArea * {
        visibility: visible;
    }
    #printableClinicArea, #printableDrugArea {
        position: absolute;
        left: 0;
        top: 0;
        width: 100%;
        margin: 0;
        padding: 15px;
    }
}
</style>

<div class="container-fluid py-4 px-3 px-xl-5">

    <!-- PAGE HEADER -->
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
        <div>
            <div class="d-flex align-items-center gap-2 mb-1">
                <span class="badge bg-danger-subtle text-danger border border-danger-subtle px-2 py-1 font-monospace">VETERINARY FIELD OPERATIONS</span>
                <span class="badge bg-secondary-subtle text-secondary border px-2 py-1">
                    <i class="bi bi-geo-alt-fill me-1"></i><?= htmlspecialchars($range_name) ?> (<?= htmlspecialchars($district_name) ?>)
                </span>
            </div>
            <h2 class="h3 fw-bold mb-0 text-dark">Mobile Clinics &amp; Drug Distribution</h2>
            <p class="text-muted small mb-0">Record field clinic reports, medical team deployments, breeding &amp; health activities, and drug inventory dispensing.</p>
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

            <a href="monthly-annual-reports.php" class="btn btn-sm btn-secondary shadow-sm text-nowrap">
                <i class="bi bi-arrow-left me-1"></i>Back to Reports
            </a>
        </div>
    </div>

    <!-- STATISTICAL KPI CARDS -->
    <div class="row g-3 mb-4">
        <!-- Clinics Conducted -->
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm kpi-card bg-white h-100">
                <div class="card-body p-3 d-flex align-items-center gap-3">
                    <div class="rounded-3 bg-danger-subtle text-danger p-3 d-flex align-items-center justify-content-center">
                        <i class="bi bi-truck fs-3"></i>
                    </div>
                    <div>
                        <span class="text-muted small text-uppercase fw-semibold d-block">Clinics Held</span>
                        <h3 class="fw-bold mb-0 text-dark font-monospace"><?= number_format($kpi_total_clinics) ?></h3>
                        <small class="text-muted"><?= $selected_year ?> Sessions</small>
                    </div>
                </div>
            </div>
        </div>

        <!-- Farmers Benefited -->
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm kpi-card bg-white h-100">
                <div class="card-body p-3 d-flex align-items-center gap-3">
                    <div class="rounded-3 bg-primary-subtle text-primary p-3 d-flex align-items-center justify-content-center">
                        <i class="bi bi-people fs-3"></i>
                    </div>
                    <div>
                        <span class="text-muted small text-uppercase fw-semibold d-block">Farmers Benefited</span>
                        <h3 class="fw-bold mb-0 text-dark font-monospace"><?= number_format($kpi_farmers_benefited) ?></h3>
                        <small class="text-muted">Direct Livestock Owners</small>
                    </div>
                </div>
            </div>
        </div>

        <!-- Animals Treated -->
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm kpi-card bg-white h-100">
                <div class="card-body p-3 d-flex align-items-center gap-3">
                    <div class="rounded-3 bg-success-subtle text-success p-3 d-flex align-items-center justify-content-center">
                        <i class="bi bi-heart-pulse fs-3"></i>
                    </div>
                    <div>
                        <span class="text-muted small text-uppercase fw-semibold d-block">Animals Treated</span>
                        <h3 class="fw-bold mb-0 text-dark font-monospace"><?= number_format($kpi_animals_treated) ?></h3>
                        <small class="text-muted">Clinical Cases Attended</small>
                    </div>
                </div>
            </div>
        </div>

        <!-- Total Doses Issued -->
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm kpi-card bg-white h-100">
                <div class="card-body p-3 d-flex align-items-center gap-3">
                    <div class="rounded-3 bg-info-subtle text-info p-3 d-flex align-items-center justify-content-center">
                        <i class="bi bi-capsule fs-3"></i>
                    </div>
                    <div>
                        <span class="text-muted small text-uppercase fw-semibold d-block">Total Doses Issued</span>
                        <h3 class="fw-bold mb-0 text-dark font-monospace"><?= number_format($kpi_total_doses_issued, 1) ?></h3>
                        <small class="text-muted">Stock Distributed</small>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- MAIN TWO-TAB INTERFACE -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white py-3 border-bottom d-flex align-items-center justify-content-between flex-wrap gap-3">
            <!-- TAB PILLS -->
            <ul class="nav nav-pills gap-2" id="mobileClinicTab" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link <?= ($active_tab === 'clinics') ? 'active' : '' ?>" id="tab-clinics-nav" data-bs-toggle="pill" data-bs-target="#tab-clinics-pane" type="button" role="tab" aria-selected="<?= ($active_tab === 'clinics') ? 'true' : 'false' ?>">
                        <i class="bi bi-truck-front-fill me-2"></i>Tab 1: Conduction of Mobile Clinics &amp; Activities
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link <?= ($active_tab === 'drugs') ? 'active' : '' ?>" id="tab-drugs-nav" data-bs-toggle="pill" data-bs-target="#tab-drugs-pane" type="button" role="tab" aria-selected="<?= ($active_tab === 'drugs') ? 'true' : 'false' ?>">
                        <i class="bi bi-capsule me-2"></i>Tab 2: Drugs and Consumable Distribution Details
                    </button>
                </li>
            </ul>

            <!-- ACTION BUTTONS -->
            <div class="d-flex align-items-center gap-2">
                <button type="button" class="btn btn-sm btn-danger shadow-sm fw-bold px-3" onclick="openRecordClinicModal()">
                    <i class="bi bi-plus-circle-fill me-1"></i>Record Mobile Clinic
                </button>
                <button type="button" class="btn btn-sm btn-primary shadow-sm fw-bold px-3" onclick="openRecordDrugModal()">
                    <i class="bi bi-capsule-pill me-1"></i>Record Drug Distribution
                </button>
            </div>
        </div>

        <div class="card-body p-3 p-md-4">
            <div class="tab-content" id="mobileClinicTabContent">

                <!-- ================================================================= -->
                <!-- TAB 1: CONDUCTION OF MOBILE CLINICS & ACTIVITIES                  -->
                <!-- ================================================================= -->
                <div class="tab-pane fade <?= ($active_tab === 'clinics') ? 'show active' : '' ?>" id="tab-clinics-pane" role="tabpanel" tabindex="0">
                    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
                        <div>
                            <h5 class="fw-bold mb-0 text-dark">Mobile Clinics &amp; Activities Register</h5>
                            <p class="text-muted small mb-0">Field clinics conducted in <?= $selected_year ?>, team members, breeding, health, and extension services.</p>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-hover table-bordered align-middle w-100" id="tableMobileClinics">
                            <thead class="table-light small text-uppercase">
                                <tr>
                                    <th style="width: 50px;" class="text-center">#</th>
                                    <th style="width: 105px;">Clinic Date</th>
                                    <th>Places Visited</th>
                                    <th>Mobile Team</th>
                                    <th class="text-center" style="width: 90px;">Farmers</th>
                                    <th class="text-center" style="width: 85px;">Cases</th>
                                    <th style="min-width: 170px;">Activities Summary</th>
                                    <th style="width: 120px;" class="text-center">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($clinic_records)): ?>
                                    <?php foreach ($clinic_records as $idx => $r): 
                                        $team = json_decode($r['team_members_json'] ?? '[]', true) ?: [];
                                    ?>
                                        <tr>
                                            <td class="text-center text-muted fw-bold"><?= $idx + 1 ?></td>
                                            <td class="font-monospace fw-bold text-dark">
                                                <?= htmlspecialchars(date('Y-m-d', strtotime($r['clinic_date']))) ?>
                                            </td>
                                            <td>
                                                <strong class="text-dark"><?= htmlspecialchars($r['places_visited']) ?></strong>
                                                <div class="small text-muted"><i class="bi bi-geo-alt me-1"></i><?= htmlspecialchars($r['range_name'] ?? $range_name) ?></div>
                                            </td>
                                            <td>
                                                <?php if (!empty($team)): ?>
                                                    <div class="d-flex flex-wrap gap-1">
                                                        <?php foreach (array_slice($team, 0, 3) as $m): ?>
                                                            <span class="badge bg-light text-dark border">
                                                                <i class="bi bi-person me-1"></i><?= htmlspecialchars($m['name'] ?? '') ?>
                                                                <small class="text-muted">(<?= htmlspecialchars($m['designation'] ?? 'Staff') ?>)</small>
                                                            </span>
                                                        <?php endforeach; ?>
                                                        <?php if (count($team) > 3): ?>
                                                            <span class="badge bg-secondary-subtle text-secondary">+<?= count($team) - 3 ?> more</span>
                                                        <?php endif; ?>
                                                    </div>
                                                <?php else: ?>
                                                    <span class="text-muted small fst-italic">No team registered</span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="text-center font-monospace fw-bold text-primary">
                                                <?= number_format($r['farmers_benefited']) ?>
                                            </td>
                                            <td class="text-center font-monospace">
                                                <span class="badge bg-success-subtle text-success border border-success-subtle" title="Cases Attended">
                                                    <?= number_format($r['cases_attended']) ?>
                                                </span>
                                                <small class="text-muted d-block font-monospace">/ <?= number_format($r['cases_reported']) ?> rep</small>
                                            </td>
                                            <td>
                                                <div class="small">
                                                    <span class="badge bg-danger-subtle text-danger me-1">A.I: <?= $r['ai_done'] ?></span>
                                                    <span class="badge bg-info-subtle text-info me-1">P.D: <?= $r['pd_diagnosed'] ?></span>
                                                    <span class="badge bg-success-subtle text-success me-1">Vac: <?= $r['animals_vaccinated'] ?></span>
                                                    <span class="badge bg-warning-subtle text-warning text-dark">Treated: <?= $r['animals_treated'] ?></span>
                                                </div>
                                                <?php if (!empty($r['training_conducted'])): ?>
                                                    <div class="small text-muted mt-1">
                                                        <i class="bi bi-mortarboard me-1"></i><?= $r['training_conducted'] ?> Training(s) (<?= $r['training_participants'] ?> pax)
                                                    </div>
                                                <?php endif; ?>
                                            </td>
                                            <td class="text-center">
                                                <div class="btn-group btn-group-sm">
                                                    <button type="button" class="btn btn-outline-secondary" onclick="viewMobileClinic(<?= (int)$r['id'] ?>)" title="View & Print">
                                                        <i class="bi bi-printer"></i>
                                                    </button>
                                                    <button type="button" class="btn btn-outline-primary" onclick="editMobileClinic(<?= (int)$r['id'] ?>)" title="Edit">
                                                        <i class="bi bi-pencil"></i>
                                                    </button>
                                                    <button type="button" class="btn btn-outline-danger" onclick="deleteMobileClinic(<?= (int)$r['id'] ?>)" title="Delete">
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

                <!-- ================================================================= -->
                <!-- TAB 2: DRUGS AND CONSUMABLE DISTRIBUTION DETAILS                  -->
                <!-- ================================================================= -->
                <div class="tab-pane fade <?= ($active_tab === 'drugs') ? 'show active' : '' ?>" id="tab-drugs-pane" role="tabpanel" tabindex="0">
                    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
                        <div>
                            <h5 class="fw-bold mb-0 text-dark">Drugs &amp; Consumable Distribution Register</h5>
                            <p class="text-muted small mb-0">Dispensing records linked with treatment grid, auto-calculated usage, and balance in hand.</p>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-hover table-bordered align-middle w-100" id="tableDrugDistributions">
                            <thead class="table-light small text-uppercase">
                                <tr>
                                    <th style="width: 50px;" class="text-center">#</th>
                                    <th style="width: 105px;">Date</th>
                                    <th>Drug / Consumable Name</th>
                                    <th class="text-end" style="width: 110px;">Initial Stock</th>
                                    <th class="text-end" style="width: 110px;">Total Issued</th>
                                    <th class="text-end" style="width: 120px;">Balance in Hand</th>
                                    <th class="text-center" style="width: 100px;">Patients</th>
                                    <th style="width: 120px;" class="text-center">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($drug_records)): ?>
                                    <?php foreach ($drug_records as $idx => $dr): ?>
                                        <tr>
                                            <td class="text-center text-muted fw-bold"><?= $idx + 1 ?></td>
                                            <td class="font-monospace fw-bold text-dark">
                                                <?= htmlspecialchars(date('Y-m-d', strtotime($dr['distribution_date']))) ?>
                                            </td>
                                            <td>
                                                <strong class="text-dark fs-6"><?= htmlspecialchars($dr['drug_name']) ?></strong>
                                                <div class="small text-muted font-monospace">Unit: <?= htmlspecialchars($dr['unit_of_measure'] ?? 'Doses') ?></div>
                                            </td>
                                            <td class="text-end font-monospace fw-bold text-secondary">
                                                <?= number_format($dr['initial_stock'], 2) ?>
                                            </td>
                                            <td class="text-end font-monospace fw-bold text-danger">
                                                <?= number_format($dr['total_issued'], 2) ?>
                                            </td>
                                            <td class="text-end font-monospace fw-bold">
                                                <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1 fs-6">
                                                    <?= number_format($dr['balance_in_hand'], 2) ?>
                                                </span>
                                            </td>
                                            <td class="text-center font-monospace">
                                                <span class="badge bg-primary-subtle text-primary border border-primary-subtle">
                                                    <?= (int)$dr['treatment_count'] ?> Treatments
                                                </span>
                                                <small class="text-muted d-block"><?= (int)$dr['total_patients'] ?> Animals</small>
                                            </td>
                                            <td class="text-center">
                                                <div class="btn-group btn-group-sm">
                                                    <button type="button" class="btn btn-outline-secondary" onclick="viewDrugDistribution(<?= (int)$dr['id'] ?>)" title="View & Print">
                                                        <i class="bi bi-printer"></i>
                                                    </button>
                                                    <button type="button" class="btn btn-outline-primary" onclick="editDrugDistribution(<?= (int)$dr['id'] ?>)" title="Edit">
                                                        <i class="bi bi-pencil"></i>
                                                    </button>
                                                    <button type="button" class="btn btn-outline-danger" onclick="deleteDrugDistribution(<?= (int)$dr['id'] ?>)" title="Delete">
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

<!-- ========================================================================= -->
<!-- MODAL INCLUDES                                                            -->
<!-- ========================================================================= -->
<?php
require_once __DIR__ . '/models/modal_record_mobile_clinic.php';
require_once __DIR__ . '/models/modal_record_drug_distribution.php';
require_once __DIR__ . '/models/modal_view_mobile_clinic.php';
require_once __DIR__ . '/models/modal_view_drug_distribution.php';
?>

<!-- JAVASCRIPTS -->
<script src="../../../assets/js/jquery.min.js"></script>
<script src="../../../assets/js/jquery.dataTables.min.js"></script>
<script src="../../../assets/js/dataTables.bootstrap5.min.js"></script>
<script src="../../../assets/js/sweetalert2.all.min.js"></script>

<script>
const CRUD_URL = 'processors/mobile_clinics_crud.php';
let drugCatalog = [];

$(document).ready(function() {
    // Initialize DataTables
    $('#tableMobileClinics').DataTable({
        pageLength: 10,
        responsive: true,
        order: [[1, 'desc']],
        language: { search: "_INPUT_", searchPlaceholder: "Search clinics..." }
    });

    $('#tableDrugDistributions').DataTable({
        pageLength: 10,
        responsive: true,
        order: [[1, 'desc']],
        language: { search: "_INPUT_", searchPlaceholder: "Search drug distributions..." }
    });

    // Preload Drugs Catalog
    loadDrugCatalog();
});

// -------------------------------------------------------------------------
// TAB 1: CONDUCTION OF MOBILE CLINICS JAVASCRIPT
// -------------------------------------------------------------------------

function openRecordClinicModal() {
    $('#formRecordMobileClinic')[0].reset();
    $('#clinic_edit_id').val('');
    $('#modalRecordMobileClinicLabel').text('Conduction of Mobile Clinic & Activities');
    $('#btnSubmitMobileClinic').html('<i class="bi bi-check-circle-fill me-1"></i>Save Clinic Record');
    $('#clinicTeamMemberList').empty();
    
    // Default team members: VS and LDI
    addClinicTeamMemberRow('Dr. M. S. Veterinary Surgeon', 'Veterinary Surgeon');
    addClinicTeamMemberRow('', 'Livestock Development Instructor');

    const modal = new bootstrap.Modal(document.getElementById('modalRecordMobileClinic'));
    modal.show();
}

function addClinicTeamMemberRow(defaultName = '', defaultDesig = '', defaultContact = '') {
    const tbody = document.getElementById('clinicTeamMemberList');
    const rowCount = tbody.querySelectorAll('tr').length;
    const rowId = rowCount + 1;

    const tr = document.createElement('tr');
    tr.className = 'team-member-row';
    tr.innerHTML = `
        <td class="text-center text-muted fw-bold">${rowId}</td>
        <td>
            <input type="text" class="form-control form-control-sm" name="team_members[${rowCount}][name]" value="${escapeHtml(defaultName)}" placeholder="Staff Name" required>
        </td>
        <td>
            <select class="form-select form-select-sm" name="team_members[${rowCount}][designation]" required>
                <option value="Veterinary Surgeon" ${defaultDesig === 'Veterinary Surgeon' ? 'selected' : ''}>Veterinary Surgeon (VS)</option>
                <option value="Livestock Development Instructor" ${defaultDesig === 'Livestock Development Instructor' ? 'selected' : ''}>Livestock Development Instructor (LDI)</option>
                <option value="Livestock Officer" ${defaultDesig === 'Livestock Officer' ? 'selected' : ''}>Livestock Officer (LO)</option>
                <option value="Development Officer" ${defaultDesig === 'Development Officer' ? 'selected' : ''}>Development Officer (DO)</option>
                <option value="Veterinary Attendant" ${defaultDesig === 'Veterinary Attendant' ? 'selected' : ''}>Veterinary Attendant</option>
                <option value="Driver" ${defaultDesig === 'Driver' ? 'selected' : ''}>Driver</option>
                <option value="Other Support Staff" ${defaultDesig === 'Other Support Staff' ? 'selected' : ''}>Other Support Staff</option>
            </select>
        </td>
        <td>
            <input type="text" class="form-control form-control-sm" name="team_members[${rowCount}][contact]" value="${escapeHtml(defaultContact)}" placeholder="07X-XXXXXXX">
        </td>
        <td class="text-center">
            <button type="button" class="btn btn-sm btn-outline-danger" onclick="removeClinicTeamMemberRow(this)">
                <i class="bi bi-trash"></i>
            </button>
        </td>
    `;
    tbody.appendChild(tr);
}

function removeClinicTeamMemberRow(btn) {
    const tbody = document.getElementById('clinicTeamMemberList');
    if (tbody.querySelectorAll('tr').length <= 1) {
        Swal.fire({
            icon: 'warning',
            title: 'At least one team member required',
            text: 'A mobile clinic session must have at least one assigned team officer.'
        });
        return;
    }
    btn.closest('tr').remove();
    // Re-index rows
    tbody.querySelectorAll('tr').forEach((tr, index) => {
        tr.querySelector('td:first-child').textContent = index + 1;
    });
}

function handleSaveMobileClinic(e) {
    e.preventDefault();
    const form = document.getElementById('formRecordMobileClinic');
    const formData = new FormData(form);

    $('#btnSubmitMobileClinic').prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span>Saving...');

    fetch(CRUD_URL, {
        method: 'POST',
        body: formData
    })
    .then(r => r.json())
    .then(data => {
        $('#btnSubmitMobileClinic').prop('disabled', false).html('<i class="bi bi-check-circle-fill me-1"></i>Save Clinic Record');
        if (data.success) {
            Swal.fire({
                icon: 'success',
                title: 'Recorded Successfully',
                text: data.message || 'Mobile Clinic details saved.',
                timer: 1600,
                showConfirmButton: false
            }).then(() => {
                location.reload();
            });
        } else {
            Swal.fire({
                icon: 'error',
                title: 'Save Failed',
                text: data.message || 'Could not save mobile clinic record.'
            });
        }
    })
    .catch(err => {
        $('#btnSubmitMobileClinic').prop('disabled', false).html('<i class="bi bi-check-circle-fill me-1"></i>Save Clinic Record');
        Swal.fire({
            icon: 'error',
            title: 'Network Error',
            text: err.message
        });
    });
}

function editMobileClinic(id) {
    fetch(`${CRUD_URL}?action=get_mobile_clinic&id=${id}`)
    .then(r => r.json())
    .then(data => {
        if (!data.success) {
            Swal.fire('Error', data.message, 'error');
            return;
        }
        const c = data.clinic;
        $('#clinic_edit_id').val(c.id);
        $('#modalRecordMobileClinicLabel').text('Edit Conduction of Mobile Clinic (ID #' + c.id + ')');
        $('#btnSubmitMobileClinic').html('<i class="bi bi-check-circle-fill me-1"></i>Update Clinic Record');

        $('#clinic_date').val(c.clinic_date);
        $('#clinic_places_visited').val(c.places_visited);
        $('#clinic_farmers_benefited').val(c.farmers_benefited);
        $('#clinic_cases_reported').val(c.cases_reported);
        $('#clinic_cases_attended').val(c.cases_attended);

        // Breeding
        $('#clinic_ai_done').val(c.ai_done);
        $('#clinic_pd_diagnosed').val(c.pd_diagnosed);

        // Health
        $('#clinic_animals_vaccinated').val(c.animals_vaccinated);
        $('#clinic_animals_treated').val(c.animals_treated);
        $('#clinic_poultry_gumboro').val(c.poultry_gumboro);
        $('#clinic_poultry_ranikhet_1st').val(c.poultry_ranikhet_1st);
        $('#clinic_poultry_ranikhet_2nd').val(c.poultry_ranikhet_2nd);
        $('#clinic_poultry_fowlpox').val(c.poultry_fowlpox);
        $('#clinic_cattle_goat_fmd_bq').val(c.cattle_goat_fmd_bq);
        $('#clinic_cattle_goat_arv').val(c.cattle_goat_arv);

        // Extension
        $('#clinic_training_conducted').val(c.training_conducted);
        $('#clinic_training_type').val(c.training_type);
        $('#clinic_training_participants').val(c.training_participants);
        $('#clinic_animals_ear_tagged').val(c.animals_ear_tagged);

        $('#clinic_remarks').val(c.remarks);

        // Team members
        $('#clinicTeamMemberList').empty();
        if (c.team_members && c.team_members.length > 0) {
            c.team_members.forEach(m => {
                addClinicTeamMemberRow(m.name, m.designation, m.contact || '');
            });
        } else {
            addClinicTeamMemberRow();
        }

        const modal = new bootstrap.Modal(document.getElementById('modalRecordMobileClinic'));
        modal.show();
    })
    .catch(err => Swal.fire('Error', err.message, 'error'));
}

function viewMobileClinic(id) {
    fetch(`${CRUD_URL}?action=get_mobile_clinic&id=${id}`)
    .then(r => r.json())
    .then(data => {
        if (!data.success) {
            Swal.fire('Error', data.message, 'error');
            return;
        }
        const c = data.clinic;
        $('#view_clinic_district').text(c.district_name || 'District');
        $('#view_clinic_range').text(c.range_name || 'Range');
        $('#view_clinic_date').text(c.clinic_date);
        $('#view_clinic_places').text(c.places_visited);

        $('#view_clinic_farmers').text(c.farmers_benefited);
        $('#view_clinic_cases_rep').text(c.cases_reported);
        $('#view_clinic_cases_att').text(c.cases_attended);

        $('#view_clinic_ai').text(c.ai_done);
        $('#view_clinic_pd').text(c.pd_diagnosed);
        $('#view_clinic_vaccinated').text(c.animals_vaccinated);
        $('#view_clinic_treated').text(c.animals_treated);

        $('#view_p_gumboro').text(c.poultry_gumboro);
        $('#view_p_ranikhet1').text(c.poultry_ranikhet_1st);
        $('#view_p_ranikhet2').text(c.poultry_ranikhet_2nd);
        $('#view_p_fowlpox').text(c.poultry_fowlpox);
        $('#view_cg_fmdbq').text(c.cattle_goat_fmd_bq);
        $('#view_cg_arv').text(c.cattle_goat_arv);

        $('#view_clinic_training_cnt').text(c.training_conducted);
        $('#view_clinic_training_type').text(c.training_type || 'N/A');
        $('#view_clinic_participants').text(c.training_participants);
        $('#view_clinic_eartagged').text(c.animals_ear_tagged);

        $('#view_clinic_remarks').text(c.remarks || 'None provided.');

        // Team tbody
        const tb = $('#view_clinic_team_tbody');
        tb.empty();
        if (c.team_members && c.team_members.length > 0) {
            c.team_members.forEach((m, idx) => {
                tb.append(`
                    <tr>
                        <td class="text-center">${idx + 1}</td>
                        <td class="fw-bold">${escapeHtml(m.name)}</td>
                        <td>${escapeHtml(m.designation)}</td>
                        <td>${escapeHtml(m.contact || '-')}</td>
                    </tr>
                `);
            });
        } else {
            tb.append('<tr><td colspan="4" class="text-center text-muted">No team members specified.</td></tr>');
        }

        const modal = new bootstrap.Modal(document.getElementById('modalViewMobileClinic'));
        modal.show();
    })
    .catch(err => Swal.fire('Error', err.message, 'error'));
}

function deleteMobileClinic(id) {
    Swal.fire({
        title: 'Delete Clinic Record?',
        text: 'Are you sure you want to delete this Mobile Clinic report? This action cannot be reversed.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Yes, Delete'
    }).then((result) => {
        if (result.isConfirmed) {
            $.post(CRUD_URL, { action: 'delete_mobile_clinic', id: id }, function(res) {
                if (res.success) {
                    Swal.fire('Deleted', res.message, 'success').then(() => location.reload());
                } else {
                    Swal.fire('Failed', res.message, 'error');
                }
            }, 'json').fail(err => Swal.fire('Error', err.statusText, 'error'));
        }
    });
}

function printClinicReport() {
    window.print();
}

// -------------------------------------------------------------------------
// TAB 2: DRUGS AND CONSUMABLE DISTRIBUTION JAVASCRIPT
// -------------------------------------------------------------------------

function loadDrugCatalog() {
    fetch(`${CRUD_URL}?action=get_drug_options`)
    .then(r => r.json())
    .then(data => {
        if (data.success && data.drugs) {
            drugCatalog = data.drugs;
            const select = $('#dist_drug_select');
            select.empty();
            select.append('<option value="">-- Choose Master Inventory Drug --</option>');
            drugCatalog.forEach(d => {
                select.append(`<option value="${d.id}" data-name="${escapeHtml(d.drug_name)}" data-stock="${d.stock}">${escapeHtml(d.drug_name)} ${d.brand_name ? '(' + escapeHtml(d.brand_name) + ')' : ''} [Stock: ${d.stock}]</option>`);
            });
        }
    })
    .catch(err => console.error("Error loading drug catalog:", err));
}

function openRecordDrugModal() {
    $('#formRecordDrugDistribution')[0].reset();
    $('#dist_edit_id').val('');
    $('#modalRecordDrugDistributionLabel').text('Drug & Consumable Distribution Entry');
    $('#btnSubmitDrugDistribution').html('<i class="bi bi-check2-circle me-1"></i>Save Distribution & Sync Inventory');
    $('#treatmentGridRows').empty();
    $('#customDrugWrapper').addClass('d-none');
    $('#dist_custom_drug_name').val('');
    $('#dist_drug_name').val('');
    $('#dist_drug_type_id').val('');
    $('#dist_initial_stock').val('0.00');

    // Add first clean row
    addTreatmentRow();
    recalcInventoryBalances();

    const modal = new bootstrap.Modal(document.getElementById('modalRecordDrugDistribution'));
    modal.show();
}

function handleDrugSelectChange(selectElem) {
    const opt = selectElem.options[selectElem.selectedIndex];
    const drugId = selectElem.value;
    if (drugId) {
        const drugName = opt.getAttribute('data-name');
        const stock = parseFloat(opt.getAttribute('data-stock') || 0);

        $('#dist_drug_name').val(drugName);
        $('#dist_drug_type_id').val(drugId);
        $('#dist_initial_stock').val(stock.toFixed(2));
        $('#customDrugWrapper').addClass('d-none');
        $('#dist_custom_drug_name').val('');

        // Fetch exact latest balance
        fetch(`${CRUD_URL}?action=get_drug_stock&drug_id=${drugId}&drug_name=${encodeURIComponent(drugName)}`)
        .then(r => r.json())
        .then(res => {
            if (res.success && res.stock !== undefined) {
                $('#dist_initial_stock').val(parseFloat(res.stock).toFixed(2));
                recalcInventoryBalances();
            }
        });
    } else {
        $('#dist_drug_name').val('');
        $('#dist_drug_type_id').val('');
        $('#dist_initial_stock').val('0.00');
    }
    recalcInventoryBalances();
}

function toggleCustomDrugInput() {
    const wrap = $('#customDrugWrapper');
    if (wrap.hasClass('d-none')) {
        wrap.removeClass('d-none');
        $('#dist_drug_select').val('');
        $('#dist_drug_type_id').val('');
        $('#dist_custom_drug_name').focus();
    } else {
        wrap.addClass('d-none');
    }
    syncDrugName();
}

function syncDrugName() {
    const customVal = $('#dist_custom_drug_name').val().trim();
    if (customVal) {
        $('#dist_drug_name').val(customVal);
        $('#dist_drug_type_id').val('');
    }
}

function updateUnitLabels(unit) {
    $('.unit-label-span').text(`(${unit})`);
    recalcInventoryBalances();
}

function addTreatmentRow(data = {}) {
    $('#emptyTreatmentState').addClass('d-none');
    const tbody = document.getElementById('treatmentGridRows');
    const rowCount = tbody.querySelectorAll('tr').length;
    const rowId = rowCount + 1;

    const tr = document.createElement('tr');
    tr.className = 'treatment-row';
    tr.innerHTML = `
        <td class="text-center text-muted fw-bold">${rowId}</td>
        <td>
            <div class="input-group input-group-sm">
                <input type="text" class="form-control font-monospace row-nic" name="treatments[${rowCount}][farmer_nic]" value="${escapeHtml(data.farmer_nic || '')}" placeholder="NIC No" onchange="autoFetchFarmer(this)">
                <button class="btn btn-outline-secondary" type="button" title="Lookup Farmer" onclick="autoFetchFarmer(this)">
                    <i class="bi bi-search"></i>
                </button>
            </div>
        </td>
        <td>
            <input type="text" class="form-control form-control-sm fw-bold row-name" name="treatments[${rowCount}][farmer_name]" value="${escapeHtml(data.farmer_name || '')}" placeholder="Farmer Name" required>
        </td>
        <td>
            <input type="text" class="form-control form-control-sm row-addr" name="treatments[${rowCount}][farmer_address]" value="${escapeHtml(data.farmer_address || '')}" placeholder="Address / Village">
        </td>
        <td>
            <select class="form-select form-select-sm" name="treatments[${rowCount}][species_affected]">
                <option value="Cattle" ${data.species_affected === 'Cattle' ? 'selected' : ''}>Cattle</option>
                <option value="Buffalo" ${data.species_affected === 'Buffalo' ? 'selected' : ''}>Buffalo</option>
                <option value="Goat" ${data.species_affected === 'Goat' ? 'selected' : ''}>Goat</option>
                <option value="Sheep" ${data.species_affected === 'Sheep' ? 'selected' : ''}>Sheep</option>
                <option value="Poultry" ${data.species_affected === 'Poultry' ? 'selected' : ''}>Poultry</option>
                <option value="Swine" ${data.species_affected === 'Swine' ? 'selected' : ''}>Swine</option>
                <option value="Canine" ${data.species_affected === 'Canine' ? 'selected' : ''}>Canine</option>
                <option value="Other" ${data.species_affected === 'Other' ? 'selected' : ''}>Other</option>
            </select>
        </td>
        <td>
            <input type="text" class="form-control form-control-sm" name="treatments[${rowCount}][disease_diagnosed]" value="${escapeHtml(data.disease_diagnosed || 'Clinical Condition')}" placeholder="e.g. Mastitis, Worm Infestation">
        </td>
        <td>
            <input type="number" min="1" class="form-control form-control-sm text-center font-monospace row-animals" name="treatments[${rowCount}][animals_treated]" value="${data.animals_treated || 1}" oninput="recalcInventoryBalances()">
        </td>
        <td>
            <input type="number" step="any" min="0" class="form-control form-control-sm text-end font-monospace fw-bold text-danger row-dose" name="treatments[${rowCount}][total_dose_issued]" value="${data.total_dose_issued || 0}" oninput="recalcInventoryBalances()" required>
        </td>
        <td class="text-center">
            <button type="button" class="btn btn-sm btn-outline-danger" onclick="removeTreatmentRow(this)">
                <i class="bi bi-trash"></i>
            </button>
        </td>
    `;
    tbody.appendChild(tr);
    recalcInventoryBalances();
}

function removeTreatmentRow(btn) {
    btn.closest('tr').remove();
    const tbody = document.getElementById('treatmentGridRows');
    const rows = tbody.querySelectorAll('tr');
    if (rows.length === 0) {
        $('#emptyTreatmentState').removeClass('d-none');
    } else {
        rows.forEach((tr, index) => {
            tr.querySelector('td:first-child').textContent = index + 1;
        });
    }
    recalcInventoryBalances();
}

function addSampleTreatmentRow() {
    addTreatmentRow({
        farmer_nic: '852103456V',
        farmer_name: 'K. Sivalingam',
        farmer_address: 'Malwatta Road, Ampara',
        species_affected: 'Cattle',
        disease_diagnosed: 'Helminthiasis (Worm Infestation)',
        animals_treated: 2,
        total_dose_issued: 2.0
    });
}

function autoFetchFarmer(elem) {
    const row = elem.closest('tr');
    const nicInput = row.querySelector('.row-nic');
    const query = nicInput.value.trim();
    if (!query) return;

    fetch(`${CRUD_URL}?action=lookup_farmer&query=${encodeURIComponent(query)}`)
    .then(r => r.json())
    .then(res => {
        if (res.success && res.farmer) {
            row.querySelector('.row-name').value = res.farmer.farmer_name || '';
            row.querySelector('.row-addr').value = res.farmer.farmer_address || '';
            nicInput.classList.add('is-valid');
            setTimeout(() => nicInput.classList.remove('is-valid'), 2000);
        } else {
            Swal.fire({
                icon: 'info',
                title: 'No Farmer Match',
                text: 'No registered livestock farmer was found for this NIC. You can manually enter the farmer name and address.',
                timer: 2500,
                showConfirmButton: false
            });
        }
    })
    .catch(err => console.error(err));
}

function recalcInventoryBalances() {
    const initialStock = parseFloat($('#dist_initial_stock').val()) || 0;
    const unit = $('#dist_unit_of_measure').val() || 'Doses';

    let totalIssued = 0.00;
    let totalAnimals = 0;
    let totalFarmers = 0;

    $('.treatment-row').each(function() {
        const dose = parseFloat($(this).find('.row-dose').val()) || 0;
        const animals = parseInt($(this).find('.row-animals').val()) || 0;
        const fname = $(this).find('.row-name').val().trim();

        totalIssued += dose;
        totalAnimals += animals;
        if (fname) totalFarmers++;
    });

    const balanceInHand = Math.max(0, initialStock - totalIssued);

    $('#dist_total_issued_display').val(totalIssued.toFixed(2));
    $('#grid_sum_doses').text(`${totalIssued.toFixed(2)} ${unit}`);
    $('#grid_total_farmers').text(totalFarmers);
    $('#grid_total_animals').text(totalAnimals);

    const badge = $('#badge_balance_in_hand');
    badge.text(`${balanceInHand.toFixed(2)} ${unit}`);

    if (totalIssued > initialStock) {
        badge.removeClass('bg-success').addClass('bg-danger');
        badge.text(`${balanceInHand.toFixed(2)} ${unit} (DEFICIT)`);
    } else {
        badge.removeClass('bg-danger').addClass('bg-success');
    }
}

function handleSaveDrugDistribution(e) {
    e.preventDefault();
    const form = document.getElementById('formRecordDrugDistribution');
    const drugName = $('#dist_drug_name').val().trim();

    if (!drugName) {
        Swal.fire('Drug Required', 'Please select a drug from inventory or enter a custom drug name.', 'warning');
        return;
    }

    const rows = document.querySelectorAll('#treatmentGridRows tr.treatment-row');
    if (rows.length === 0) {
        Swal.fire('Treatments Required', 'Please add at least one treatment row to the distribution grid.', 'warning');
        return;
    }

    const formData = new FormData(form);
    $('#btnSubmitDrugDistribution').prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span>Saving...');

    fetch(CRUD_URL, {
        method: 'POST',
        body: formData
    })
    .then(r => r.json())
    .then(data => {
        $('#btnSubmitDrugDistribution').prop('disabled', false).html('<i class="bi bi-check2-circle me-1"></i>Save Distribution & Sync Inventory');
        if (data.success) {
            Swal.fire({
                icon: 'success',
                title: 'Recorded & Synchronized!',
                text: data.message,
                timer: 2000,
                showConfirmButton: false
            }).then(() => {
                location.href = 'mobile_clinics.php?tab=drugs&year=<?= $selected_year ?>';
            });
        } else {
            Swal.fire('Save Failed', data.message, 'error');
        }
    })
    .catch(err => {
        $('#btnSubmitDrugDistribution').prop('disabled', false).html('<i class="bi bi-check2-circle me-1"></i>Save Distribution & Sync Inventory');
        Swal.fire('Error', err.message, 'error');
    });
}

function editDrugDistribution(id) {
    fetch(`${CRUD_URL}?action=get_drug_distribution&id=${id}`)
    .then(r => r.json())
    .then(data => {
        if (!data.success) {
            Swal.fire('Error', data.message, 'error');
            return;
        }
        const d = data.distribution;
        $('#dist_edit_id').val(d.id);
        $('#modalRecordDrugDistributionLabel').text('Edit Drug Distribution (Session #' + d.id + ')');
        $('#btnSubmitDrugDistribution').html('<i class="bi bi-check2-circle me-1"></i>Update & Sync Inventory');

        $('#dist_distribution_date').val(d.distribution_date);
        $('#dist_drug_name').val(d.drug_name);
        $('#dist_drug_type_id').val(d.drug_type_id || '');
        $('#dist_initial_stock').val(parseFloat(d.initial_stock).toFixed(2));
        $('#dist_unit_of_measure').val(d.unit_of_measure || 'Doses');
        $('#dist_remarks').val(d.remarks);

        // Select or custom
        if (d.drug_type_id) {
            $('#dist_drug_select').val(d.drug_type_id);
            $('#customDrugWrapper').addClass('d-none');
        } else {
            $('#dist_drug_select').val('');
            $('#customDrugWrapper').removeClass('d-none');
            $('#dist_custom_drug_name').val(d.drug_name);
        }

        // Treatments Grid
        $('#treatmentGridRows').empty();
        if (d.treatments && d.treatments.length > 0) {
            d.treatments.forEach(t => {
                addTreatmentRow(t);
            });
        } else {
            addTreatmentRow();
        }

        recalcInventoryBalances();
        const modal = new bootstrap.Modal(document.getElementById('modalRecordDrugDistribution'));
        modal.show();
    })
    .catch(err => Swal.fire('Error', err.message, 'error'));
}

function viewDrugDistribution(id) {
    fetch(`${CRUD_URL}?action=get_drug_distribution&id=${id}`)
    .then(r => r.json())
    .then(data => {
        if (!data.success) {
            Swal.fire('Error', data.message, 'error');
            return;
        }
        const d = data.distribution;
        $('#view_drug_name').text(d.drug_name);
        $('#view_dist_date').text(d.distribution_date);
        $('#view_dist_range').text(d.range_name || 'Range');
        $('#view_dist_district').text(d.district_name || 'District');
        $('#view_initial_stock').text(`${parseFloat(d.initial_stock).toFixed(2)} ${d.unit_of_measure}`);
        $('#view_total_issued').text(`${parseFloat(d.total_issued).toFixed(2)} ${d.unit_of_measure}`);
        $('#view_balance_hand').text(`${parseFloat(d.balance_in_hand).toFixed(2)} ${d.unit_of_measure}`);
        $('#view_dist_remarks').text(d.remarks || 'None provided.');

        const tb = $('#view_treatments_tbody');
        tb.empty();

        let sumAnimals = 0;
        let sumDoses = 0.00;

        if (d.treatments && d.treatments.length > 0) {
            d.treatments.forEach((t, idx) => {
                const animals = parseInt(t.animals_treated) || 0;
                const dose = parseFloat(t.total_dose_issued) || 0;
                sumAnimals += animals;
                sumDoses += dose;

                tb.append(`
                    <tr>
                        <td class="text-center">${idx + 1}</td>
                        <td class="fw-bold">${escapeHtml(t.farmer_name)}</td>
                        <td>${escapeHtml(t.farmer_address || '-')}</td>
                        <td class="font-monospace">${escapeHtml(t.farmer_nic || '-')}</td>
                        <td><span class="badge bg-light text-dark border">${escapeHtml(t.species_affected)}</span></td>
                        <td>${escapeHtml(t.disease_diagnosed)}</td>
                        <td class="text-center font-monospace">${animals}</td>
                        <td class="text-end font-monospace fw-bold text-danger">${dose.toFixed(2)}</td>
                    </tr>
                `);
            });
        } else {
            tb.append('<tr><td colspan="8" class="text-center text-muted">No treatments recorded.</td></tr>');
        }

        $('#view_sum_animals').text(sumAnimals);
        $('#view_sum_doses').text(`${sumDoses.toFixed(2)} ${d.unit_of_measure}`);

        const modal = new bootstrap.Modal(document.getElementById('modalViewDrugDistribution'));
        modal.show();
    })
    .catch(err => Swal.fire('Error', err.message, 'error'));
}

function deleteDrugDistribution(id) {
    Swal.fire({
        title: 'Delete Drug Distribution?',
        text: 'This will delete the distribution log and its linked patient treatments. Are you sure?',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Yes, Delete'
    }).then((result) => {
        if (result.isConfirmed) {
            $.post(CRUD_URL, { action: 'delete_drug_distribution', id: id }, function(res) {
                if (res.success) {
                    Swal.fire('Deleted', res.message, 'success').then(() => location.reload());
                } else {
                    Swal.fire('Failed', res.message, 'error');
                }
            }, 'json').fail(err => Swal.fire('Error', err.statusText, 'error'));
        }
    });
}

function printDrugReport() {
    window.print();
}

function escapeHtml(text) {
    if (!text) return '';
    return text.toString()
        .replace(/&/g, "&amp;")
        .replace(/</g, "&lt;")
        .replace(/>/g, "&gt;")
        .replace(/"/g, "&quot;")
        .replace(/'/g, "&#039;");
}
</script>

<?php require_once '../../../includes/footer.php'; ?>
