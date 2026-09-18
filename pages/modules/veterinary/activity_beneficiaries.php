<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../../../config/db_connect.php';

/** @var mysqli $mysqli */
global $mysqli;

// 1. Session and Role Guard
$allowed_roles = [
    'veterinary_surgeon',
    'government_veterinary_surgeon',
    'additional_veterinary_surgeon',
    'deputy_director_hq_1',
    'district_dd',
    'deputy_director_district',
    'provincial_director',
    'admin',
    'super_admin'
];

if (!isset($_SESSION['logged_in']) || !in_array($_SESSION['role'] ?? '', $allowed_roles, true)) {
    header("Location: ../../../../index.php");
    exit();
}

$activity_id = isset($_GET['activity_id']) ? intval($_GET['activity_id']) : 0;
if ($activity_id <= 0) {
    header("Location: production_activities.php");
    exit();
}

// 2. Fetch Production Activity Target Details
$act_stmt = $mysqli->prepare("
    SELECT pat.*, vr.name AS range_name, d.name AS district_name
    FROM production_activity_targets pat
    LEFT JOIN veterinary_ranges vr ON pat.range_id = vr.id
    LEFT JOIN districts d ON vr.district_id = d.id
    WHERE pat.id = ?
");
$act_stmt->bind_param("i", $activity_id);
$act_stmt->execute();
$activity = $act_stmt->get_result()->fetch_assoc();
$act_stmt->close();

if (!$activity) {
    die('<div class="alert alert-danger text-center p-5 m-5">Error: Production Activity not found. <a href="production_activities.php" class="btn btn-secondary btn-sm ms-2">Back</a></div>');
}

$activity_name   = $activity['activity_name'];
$funding_source  = $activity['funding_source'] ?? 'PSDG';
$category        = ($activity['animal_category'] === 'Other') ? $activity['animal_category_other'] : ($activity['animal_category'] ?? 'General');
$target_qty      = intval($activity['target_quantity']);
$achieved_qty    = intval($activity['achieved_quantity']);
$range_name      = $activity['range_name'] ?? 'Range';
$district_name   = $activity['district_name'] ?? 'District';
$year            = $activity['year'];

// Variance calculation
$variance = $achieved_qty - $target_qty;

// 3. Fetch Beneficiaries
$b_stmt = $mysqli->prepare("SELECT * FROM activity_beneficiaries WHERE activity_id = ? ORDER BY id DESC");
$b_stmt->bind_param("i", $activity_id);
$b_stmt->execute();
$beneficiaries_res = $b_stmt->get_result();

$total_beneficiaries   = 0;
$total_project_cost    = 0.0;
$total_dept_amount     = 0.0;
$total_beneficiary_amt = 0.0;
$total_work_done_sum   = 0;
$beneficiaries_list    = [];

while ($b_row = $beneficiaries_res->fetch_assoc()) {
    $beneficiaries_list[] = $b_row;
    $total_beneficiaries++;
    $total_project_cost    += floatval($b_row['total_cost']);
    $total_dept_amount     += floatval($b_row['dept_contribution_amount']);
    $total_beneficiary_amt += floatval($b_row['beneficiary_contribution_amount']);
    $total_work_done_sum   += intval($b_row['work_done_percentage']);
}
$b_stmt->close();

$avg_work_done = ($total_beneficiaries > 0) ? round($total_work_done_sum / $total_beneficiaries, 1) : 0;

require_once '../../../includes/header.php';
?>

<link rel="stylesheet" href="../../../assets/css/dataTables.bootstrap5.min.css">
<link rel="stylesheet" href="../../../assets/css/buttons.bootstrap5.min.css">
<link rel="stylesheet" href="../../../assets/css/bootstrap-icons.min.css">
<link rel="stylesheet" href="../../../assets/css/sweetalert2.min.css">

<style>
    .gov-card {
        border: none !important;
        border-radius: 12px !important;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05) !important;
    }
    .kpi-card {
        border-radius: 12px;
        background: #ffffff;
        border-left: 4px solid #820100;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.04);
        transition: transform 0.2s ease, box-shadow 0.2s ease;
    }
    .kpi-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(0, 0, 0, 0.08);
    }
    .photo-thumb {
        width: 48px;
        height: 48px;
        object-fit: cover;
        border-radius: 8px;
        border: 2px solid #ddd;
        cursor: pointer;
        transition: transform 0.2s ease, border-color 0.2s ease;
    }
    .photo-thumb:hover {
        transform: scale(1.1);
        border-color: #820100;
    }
    .badge-funding {
        background-color: #370709;
        color: #ffffff;
        font-weight: 600;
        letter-spacing: 0.5px;
    }
</style>

<div class="container-fluid px-4 py-3">

    <!-- Top Navigation & Breadcrumb -->
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-3">
        <div>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-1 small">
                    <li class="breadcrumb-item"><a href="../../../dashboard.php" class="text-decoration-none text-muted">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="production_activities.php?year=<?= $year ?>&range_id=<?= $activity['range_id'] ?>" class="text-decoration-none text-muted">Production Activities Plan</a></li>
                    <li class="breadcrumb-item active text-dark fw-bold" aria-current="page">Beneficiary Tracking</li>
                </ol>
            </nav>
            <h4 class="fw-bold mb-0" style="color: #370709;">
                <i class="bi bi-people-fill me-2 text-danger"></i>Beneficiary Tracking Sub-Module
            </h4>
        </div>
        <div class="d-flex gap-2 mt-2 mt-md-0">
            <button class="btn text-light fw-bold text-nowrap shadow-sm" style="background-color: #820100;" data-bs-toggle="modal" data-bs-target="#addBeneficiaryModal">
                <i class="bi bi-person-plus-fill me-1"></i> Enroll Beneficiary
            </button>
            <a href="production_activities.php?year=<?= $year ?>&range_id=<?= $activity['range_id'] ?>" class="btn btn-secondary shadow-sm text-nowrap">
                <i class="bi bi-arrow-left me-1"></i> Back to Activities
            </a>
        </div>
    </div>

    <!-- Feedback Alerts -->
    <?php if (isset($_SESSION['msg'])): ?>
        <div class="alert alert-<?= $_SESSION['msg_type'] ?? 'info' ?> alert-dismissible fade show shadow-sm py-2 px-3 mb-3 small" role="alert">
            <i class="bi bi-info-circle-fill me-1"></i> <?= $_SESSION['msg'] ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <?php unset($_SESSION['msg'], $_SESSION['msg_type']); ?>
    <?php endif; ?>

    <!-- Activity Overview Hero Banner -->
    <div class="card gov-card mb-4 border-0" style="background: linear-gradient(135deg, #370709 0%, #631215 100%); color: #ffffff;">
        <div class="card-body p-4">
            <div class="row align-items-center">
                <div class="col-lg-8">
                    <div class="d-flex flex-wrap align-items-center gap-2 mb-2">
                        <span class="badge bg-light text-dark fw-bold px-3 py-1">
                            <i class="bi bi-calendar-event me-1 text-danger"></i> Year <?= htmlspecialchars($year) ?>
                        </span>
                        <span class="badge badge-funding px-3 py-1 border border-light">
                            <i class="bi bi-wallet2 me-1"></i> Funding: <?= htmlspecialchars($funding_source) ?>
                        </span>
                        <span class="badge bg-warning text-dark fw-bold px-3 py-1">
                            <i class="bi bi-tag-fill me-1"></i> Animal: <?= htmlspecialchars($category) ?>
                        </span>
                    </div>
                    <h3 class="fw-bold text-white mb-2"><?= htmlspecialchars($activity_name) ?></h3>
                    <p class="mb-0 text-light opacity-75 small">
                        <i class="bi bi-geo-alt-fill me-1 text-warning"></i> <?= htmlspecialchars($range_name) ?> Range &nbsp;|&nbsp; 
                        <i class="bi bi-building me-1 text-warning"></i> <?= htmlspecialchars($district_name) ?> District
                    </p>
                </div>
                <div class="col-lg-4 mt-3 mt-lg-0 text-lg-end">
                    <div class="bg-white bg-opacity-10 p-3 rounded-3 d-inline-block text-start w-100" style="max-width: 320px;">
                        <div class="d-flex justify-content-between text-light small mb-1">
                            <span>Target Quantity:</span>
                            <span class="fw-bold text-white"><?= number_format($target_qty) ?></span>
                        </div>
                        <div class="d-flex justify-content-between text-light small mb-1">
                            <span>Achieved Quantity:</span>
                            <span class="fw-bold text-white"><?= number_format($achieved_qty) ?></span>
                        </div>
                        <div class="d-flex justify-content-between text-light small pt-1 border-top border-light border-opacity-25">
                            <span>Target Variance:</span>
                            <?php if ($variance > 0): ?>
                                <span class="badge bg-success text-white fw-bold">+<?= number_format($variance) ?> (Surplus)</span>
                            <?php elseif ($variance < 0): ?>
                                <span class="badge bg-danger text-white fw-bold"><?= number_format($variance) ?> (Deficit)</span>
                            <?php else: ?>
                                <span class="badge bg-secondary text-white fw-bold">0 (Balanced)</span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Summary KPI Cards -->
    <div class="row g-3 mb-4">
        <div class="col-md-6 col-xl-3">
            <div class="card kpi-card h-100 p-3" style="border-left-color: #820100;">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="text-muted small fw-semibold text-uppercase">Enrolled Beneficiaries</div>
                        <div class="fs-4 fw-bold text-dark mt-1"><?= number_format($total_beneficiaries) ?></div>
                    </div>
                    <div class="rounded-circle p-3 text-white" style="background-color: #820100;">
                        <i class="bi bi-people fs-4"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-6 col-xl-3">
            <div class="card kpi-card h-100 p-3" style="border-left-color: #0d6efd;">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="text-muted small fw-semibold text-uppercase">Total Project Cost</div>
                        <div class="fs-4 fw-bold text-primary mt-1">LKR <?= number_format($total_project_cost, 2) ?></div>
                    </div>
                    <div class="rounded-circle p-3 text-white bg-primary">
                        <i class="bi bi-cash-stack fs-4"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-6 col-xl-3">
            <div class="card kpi-card h-100 p-3" style="border-left-color: #198754;">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="text-muted small fw-semibold text-uppercase">Dept. Contribution</div>
                        <div class="fs-4 fw-bold text-success mt-1">LKR <?= number_format($total_dept_amount, 2) ?></div>
                    </div>
                    <div class="rounded-circle p-3 text-white bg-success">
                        <i class="bi bi-bank fs-4"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-6 col-xl-3">
            <div class="card kpi-card h-100 p-3" style="border-left-color: #fd7e14;">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="text-muted small fw-semibold text-uppercase">Avg Work Done %</div>
                        <div class="fs-4 fw-bold text-warning mt-1"><?= $avg_work_done ?>%</div>
                    </div>
                    <div class="rounded-circle p-3 text-white bg-warning">
                        <i class="bi bi-graph-up-arrow fs-4"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Beneficiaries Table Card -->
    <div class="card gov-card mb-4">
        <div class="card-header bg-white pt-4 px-4 border-0 d-flex justify-content-between align-items-center">
            <div>
                <h5 class="fw-bold mb-1" style="color: #370709;">
                    <i class="bi bi-card-checklist me-2 text-danger"></i>Assigned Beneficiary Register
                </h5>
                <p class="text-muted small mb-0">Record of farmers/beneficiaries, GPS coordinates, financial breakdowns, and verification images.</p>
            </div>
        </div>
        <div class="card-body px-4 pb-4">
            <div class="table-responsive">
                <table id="beneficiariesTable" class="table table-striped table-hover table-bordered align-middle small bg-white text-dark m-0 w-100">
                    <thead style="background-color: #d4c7b7; color: #370709;">
                        <tr>
                            <th>Beneficiary & Farm</th>
                            <th>Contact & Address</th>
                            <th class="text-center">GPS Location</th>
                            <th>Financial Breakdown (LKR / %)</th>
                            <th class="text-center" style="min-width: 130px;">Work Done %</th>
                            <th class="text-center">Verification Photos</th>
                            <th class="text-center" style="width: 100px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($beneficiaries_list)): ?>
                            <?php foreach ($beneficiaries_list as $b): ?>
                                <?php
                                $work_pct = intval($b['work_done_percentage']);
                                $milestone_cls = ($work_pct >= 100) ? 'bg-success' : (($work_pct >= 50) ? 'bg-primary' : 'bg-warning text-dark');
                                $milestone_status = ($work_pct >= 100) ? 'Completed' : (($work_pct > 0) ? 'In Progress' : 'Not Started');
                                ?>
                                <tr>
                                    <td>
                                        <div class="fw-bold text-dark fs-6"><?= htmlspecialchars($b['name']) ?></div>
                                        <div class="text-muted small"><i class="bi bi-postcard me-1"></i>Reg: <strong><?= htmlspecialchars($b['farm_reg_no']) ?></strong></div>
                                        <div class="text-secondary small"><i class="bi bi-person-vcard me-1"></i>NIC: <?= htmlspecialchars($b['nic']) ?></div>
                                    </td>
                                    <td>
                                        <div><i class="bi bi-telephone-fill me-1 text-success"></i><?= htmlspecialchars($b['phone']) ?></div>
                                        <div class="text-muted small text-truncate" style="max-width: 200px;" title="<?= htmlspecialchars($b['address']) ?>">
                                            <i class="bi bi-geo-alt me-1"></i><?= htmlspecialchars($b['address']) ?>
                                        </div>
                                    </td>
                                    <td class="text-center">
                                        <?php if (!empty($b['gps_location'])): ?>
                                            <a href="https://www.google.com/maps/search/?api=1&query=<?= urlencode($b['gps_location']) ?>" target="_blank" class="badge bg-light text-primary border text-decoration-none" title="Open in Google Maps">
                                                <i class="bi bi-geo-alt-fill me-1 text-danger"></i><?= htmlspecialchars($b['gps_location']) ?>
                                            </a>
                                        <?php else: ?>
                                            <span class="text-muted fst-italic">Not Recorded</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="small">
                                            <span class="text-muted">Total Cost:</span> <strong>LKR <?= number_format($b['total_cost'], 2) ?></strong>
                                        </div>
                                        <div class="small text-success">
                                            <span>Dept:</span> <?= number_format($b['dept_contribution_pct'], 1) ?>% (LKR <?= number_format($b['dept_contribution_amount'], 2) ?>)
                                        </div>
                                        <div class="small text-primary">
                                            <span>Farmer:</span> <?= number_format($b['beneficiary_contribution_pct'], 1) ?>% (LKR <?= number_format($b['beneficiary_contribution_amount'], 2) ?>)
                                        </div>
                                    </td>
                                    <td>
                                        <div class="d-flex align-items-center gap-2 mb-1">
                                            <div class="progress w-100" style="height: 8px; border-radius: 4px;">
                                                <div class="progress-bar <?= $milestone_cls ?>" role="progressbar" style="width: <?= min(100, $work_pct) ?>%;"></div>
                                            </div>
                                            <span class="fw-bold font-monospace small"><?= $work_pct ?>%</span>
                                        </div>
                                        <div class="text-center">
                                            <span class="badge <?= $milestone_cls ?> rounded-pill" style="font-size: 0.72rem;"><?= $milestone_status ?></span>
                                        </div>
                                    </td>
                                    <td class="text-center">
                                        <div class="d-flex justify-content-center gap-2">
                                            <?php if (!empty($b['photo_1']) && file_exists(__DIR__ . '/../../../' . $b['photo_1'])): ?>
                                                <img src="../../../<?= htmlspecialchars($b['photo_1']) ?>" alt="Photo 1" class="photo-thumb" onclick="previewImage('../../../<?= htmlspecialchars($b['photo_1']) ?>', 'Photo 1 - <?= htmlspecialchars($b['name'], ENT_QUOTES) ?>')">
                                            <?php endif; ?>
                                            <?php if (!empty($b['photo_2']) && file_exists(__DIR__ . '/../../../' . $b['photo_2'])): ?>
                                                <img src="../../../<?= htmlspecialchars($b['photo_2']) ?>" alt="Photo 2" class="photo-thumb" onclick="previewImage('../../../<?= htmlspecialchars($b['photo_2']) ?>', 'Photo 2 - <?= htmlspecialchars($b['name'], ENT_QUOTES) ?>')">
                                            <?php endif; ?>
                                            <?php if (empty($b['photo_1']) && empty($b['photo_2'])): ?>
                                                <span class="text-muted small fst-italic">No Photos</span>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <td class="text-center text-nowrap">
                                        <button class="btn btn-sm btn-outline-primary me-1 edit-beneficiary-btn" 
                                            data-id="<?= $b['id'] ?>"
                                            data-name="<?= htmlspecialchars($b['name'], ENT_QUOTES) ?>"
                                            data-farm_reg_no="<?= htmlspecialchars($b['farm_reg_no'], ENT_QUOTES) ?>"
                                            data-nic="<?= htmlspecialchars($b['nic'], ENT_QUOTES) ?>"
                                            data-phone="<?= htmlspecialchars($b['phone'], ENT_QUOTES) ?>"
                                            data-address="<?= htmlspecialchars($b['address'], ENT_QUOTES) ?>"
                                            data-gps_location="<?= htmlspecialchars($b['gps_location'], ENT_QUOTES) ?>"
                                            data-total_cost="<?= $b['total_cost'] ?>"
                                            data-dept_pct="<?= $b['dept_contribution_pct'] ?>"
                                            data-dept_amount="<?= $b['dept_contribution_amount'] ?>"
                                            data-ben_pct="<?= $b['beneficiary_contribution_pct'] ?>"
                                            data-ben_amount="<?= $b['beneficiary_contribution_amount'] ?>"
                                            data-work_pct="<?= $b['work_done_percentage'] ?>"
                                            title="Edit Beneficiary">
                                            <i class="bi bi-pencil-square"></i>
                                        </button>
                                        <button class="btn btn-sm btn-outline-danger delete-beneficiary-btn" 
                                            data-id="<?= $b['id'] ?>"
                                            data-name="<?= htmlspecialchars($b['name'], ENT_QUOTES) ?>"
                                            title="Delete Beneficiary">
                                            <i class="bi bi-trash"></i>
                                        </button>
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

<!-- Add Beneficiary Modal -->
<div class="modal fade" id="addBeneficiaryModal" tabindex="-1" aria-labelledby="addBeneficiaryLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header text-light py-2" style="background-color: #370709;">
                <h6 class="modal-title" id="addBeneficiaryLabel"><i class="bi bi-person-plus-fill me-2"></i> Enroll Beneficiary in Activity</h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="processors/beneficiary_crud.php" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="action" value="create">
                <input type="hidden" name="activity_id" value="<?= $activity_id ?>">

                <div class="modal-body p-4 small">
                    <h6 class="fw-bold border-bottom pb-2 mb-3 text-secondary"><i class="bi bi-person-badge me-1"></i> Personal & Farm Identification</h6>
                    <div class="row g-2 mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold mb-1">Beneficiary Full Name <span class="text-danger">*</span></label>
                            <input type="text" name="name" class="form-control form-control-sm border-secondary" placeholder="e.g. K. M. Sivakumar" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold mb-1">Farm Registration Number <span class="text-danger">*</span></label>
                            <input type="text" name="farm_reg_no" class="form-control form-control-sm border-secondary" placeholder="e.g. FRN-AMP-2026-0042" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold mb-1">National Identity Card (NIC) <span class="text-danger">*</span></label>
                            <input type="text" name="nic" class="form-control form-control-sm border-secondary" placeholder="e.g. 198512345678 or 851234567V" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold mb-1">Contact Phone Number <span class="text-danger">*</span></label>
                            <input type="text" name="phone" class="form-control form-control-sm border-secondary" placeholder="e.g. 0771234567" required>
                        </div>
                        <div class="col-md-8">
                            <label class="form-label fw-bold mb-1">Residential / Farm Physical Address <span class="text-danger">*</span></label>
                            <input type="text" name="address" class="form-control form-control-sm border-secondary" placeholder="e.g. No. 45, Main Street, Akkaraipattu" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold mb-1">GPS Location Coordinates</label>
                            <div class="input-group input-group-sm">
                                <input type="text" id="add_gps" name="gps_location" class="form-control border-secondary" placeholder="e.g. 7.2185, 81.8492">
                                <button class="btn btn-outline-secondary" type="button" onclick="getCurrentGPS('add_gps')" title="Fetch My Current Location">
                                    <i class="bi bi-crosshair"></i>
                                </button>
                            </div>
                        </div>
                    </div>

                    <h6 class="fw-bold border-bottom pb-2 mb-3 text-secondary"><i class="bi bi-cash-coin me-1"></i> Financial Contribution Breakdown</h6>
                    <div class="p-3 bg-light rounded-3 border mb-3">
                        <div class="row g-2 mb-2">
                            <div class="col-md-4">
                                <label class="form-label fw-bold mb-1">Total Project / Unit Cost (LKR)</label>
                                <input type="number" step="0.01" min="0" id="add_total_cost" name="total_cost" class="form-control form-control-sm border-secondary" value="0.00" oninput="calcContributions('add')">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-bold mb-1 text-success">Department Contribution (%)</label>
                                <input type="number" step="0.1" min="0" max="100" id="add_dept_pct" name="dept_contribution_pct" class="form-control form-control-sm border-success" value="50" oninput="calcFromDeptPct('add')">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-bold mb-1 text-success">Department Amount (LKR)</label>
                                <input type="number" step="0.01" min="0" id="add_dept_amount" name="dept_contribution_amount" class="form-control form-control-sm border-success" value="0.00" oninput="calcFromDeptAmt('add')">
                            </div>
                        </div>
                        <div class="row g-2">
                            <div class="col-md-4">
                                <small class="text-muted">Auto-computed breakdown ensures both percentage and exact LKR figures are captured.</small>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-bold mb-1 text-primary">Beneficiary Contribution (%)</label>
                                <input type="number" step="0.1" min="0" max="100" id="add_ben_pct" name="beneficiary_contribution_pct" class="form-control form-control-sm border-primary" value="50" oninput="calcFromBenPct('add')">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-bold mb-1 text-primary">Beneficiary Amount (LKR)</label>
                                <input type="number" step="0.01" min="0" id="add_ben_amount" name="beneficiary_contribution_amount" class="form-control form-control-sm border-primary" value="0.00" oninput="calcFromBenAmt('add')">
                            </div>
                        </div>
                    </div>

                    <h6 class="fw-bold border-bottom pb-2 mb-3 text-secondary"><i class="bi bi-speedometer2 me-1"></i> Milestone Progress & Physical Verification</h6>
                    <div class="row g-2 mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold mb-1">Milestone "Work Done Percentage" (0 - 100%)</label>
                            <div class="d-flex align-items-center gap-2">
                                <input type="range" class="form-range flex-grow-1" min="0" max="100" id="add_work_range" value="0" oninput="syncWorkInput('add', this.value)">
                                <input type="number" min="0" max="100" id="add_work_done" name="work_done_percentage" class="form-control form-control-sm border-secondary text-center fw-bold" style="width: 75px;" value="0" oninput="syncWorkRange('add', this.value)">
                                <span class="fw-bold">%</span>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-bold mb-1">Physical Verification Photo 1</label>
                            <input type="file" name="photo_1" class="form-control form-control-sm border-secondary" accept="image/*">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-bold mb-1">Physical Verification Photo 2</label>
                            <input type="file" name="photo_2" class="form-control form-control-sm border-secondary" accept="image/*">
                        </div>
                    </div>
                </div>

                <div class="modal-footer py-2 border-top-0">
                    <button type="button" class="btn btn-light btn-sm px-3" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn text-light btn-sm px-4 shadow-sm fw-bold" style="background-color: #820100;">Enroll Beneficiary</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Beneficiary Modal -->
<div class="modal fade" id="editBeneficiaryModal" tabindex="-1" aria-labelledby="editBeneficiaryLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header text-light py-2" style="background-color: #370709;">
                <h6 class="modal-title" id="editBeneficiaryLabel"><i class="bi bi-pencil-square me-2"></i> Update Beneficiary Record</h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="processors/beneficiary_crud.php" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="action" value="update">
                <input type="hidden" name="activity_id" value="<?= $activity_id ?>">
                <input type="hidden" name="id" id="edit_id">

                <div class="modal-body p-4 small">
                    <h6 class="fw-bold border-bottom pb-2 mb-3 text-secondary"><i class="bi bi-person-badge me-1"></i> Personal & Farm Identification</h6>
                    <div class="row g-2 mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold mb-1">Beneficiary Full Name <span class="text-danger">*</span></label>
                            <input type="text" name="name" id="edit_name" class="form-control form-control-sm border-secondary" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold mb-1">Farm Registration Number <span class="text-danger">*</span></label>
                            <input type="text" name="farm_reg_no" id="edit_farm_reg_no" class="form-control form-control-sm border-secondary" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold mb-1">National Identity Card (NIC) <span class="text-danger">*</span></label>
                            <input type="text" name="nic" id="edit_nic" class="form-control form-control-sm border-secondary" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold mb-1">Contact Phone Number <span class="text-danger">*</span></label>
                            <input type="text" name="phone" id="edit_phone" class="form-control form-control-sm border-secondary" required>
                        </div>
                        <div class="col-md-8">
                            <label class="form-label fw-bold mb-1">Residential / Farm Physical Address <span class="text-danger">*</span></label>
                            <input type="text" name="address" id="edit_address" class="form-control form-control-sm border-secondary" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold mb-1">GPS Location Coordinates</label>
                            <div class="input-group input-group-sm">
                                <input type="text" id="edit_gps" name="gps_location" class="form-control border-secondary">
                                <button class="btn btn-outline-secondary" type="button" onclick="getCurrentGPS('edit_gps')" title="Fetch My Current Location">
                                    <i class="bi bi-crosshair"></i>
                                </button>
                            </div>
                        </div>
                    </div>

                    <h6 class="fw-bold border-bottom pb-2 mb-3 text-secondary"><i class="bi bi-cash-coin me-1"></i> Financial Contribution Breakdown</h6>
                    <div class="p-3 bg-light rounded-3 border mb-3">
                        <div class="row g-2 mb-2">
                            <div class="col-md-4">
                                <label class="form-label fw-bold mb-1">Total Project / Unit Cost (LKR)</label>
                                <input type="number" step="0.01" min="0" id="edit_total_cost" name="total_cost" class="form-control form-control-sm border-secondary" oninput="calcContributions('edit')">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-bold mb-1 text-success">Department Contribution (%)</label>
                                <input type="number" step="0.1" min="0" max="100" id="edit_dept_pct" name="dept_contribution_pct" class="form-control form-control-sm border-success" oninput="calcFromDeptPct('edit')">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-bold mb-1 text-success">Department Amount (LKR)</label>
                                <input type="number" step="0.01" min="0" id="edit_dept_amount" name="dept_contribution_amount" class="form-control form-control-sm border-success" oninput="calcFromDeptAmt('edit')">
                            </div>
                        </div>
                        <div class="row g-2">
                            <div class="col-md-4">
                                <small class="text-muted">Dynamic tracking updates percentages and exact amounts synchronously.</small>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-bold mb-1 text-primary">Beneficiary Contribution (%)</label>
                                <input type="number" step="0.1" min="0" max="100" id="edit_ben_pct" name="beneficiary_contribution_pct" class="form-control form-control-sm border-primary" oninput="calcFromBenPct('edit')">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-bold mb-1 text-primary">Beneficiary Amount (LKR)</label>
                                <input type="number" step="0.01" min="0" id="edit_ben_amount" name="beneficiary_contribution_amount" class="form-control form-control-sm border-primary" oninput="calcFromBenAmt('edit')">
                            </div>
                        </div>
                    </div>

                    <h6 class="fw-bold border-bottom pb-2 mb-3 text-secondary"><i class="bi bi-speedometer2 me-1"></i> Milestone Progress & Physical Verification</h6>
                    <div class="row g-2 mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold mb-1">Milestone "Work Done Percentage" (0 - 100%)</label>
                            <div class="d-flex align-items-center gap-2">
                                <input type="range" class="form-range flex-grow-1" min="0" max="100" id="edit_work_range" oninput="syncWorkInput('edit', this.value)">
                                <input type="number" min="0" max="100" id="edit_work_done" name="work_done_percentage" class="form-control form-control-sm border-secondary text-center fw-bold" style="width: 75px;" oninput="syncWorkRange('edit', this.value)">
                                <span class="fw-bold">%</span>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-bold mb-1">Replace Photo 1 (Optional)</label>
                            <input type="file" name="photo_1" class="form-control form-control-sm border-secondary" accept="image/*">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-bold mb-1">Replace Photo 2 (Optional)</label>
                            <input type="file" name="photo_2" class="form-control form-control-sm border-secondary" accept="image/*">
                        </div>
                    </div>
                </div>

                <div class="modal-footer py-2 border-top-0">
                    <button type="button" class="btn btn-light btn-sm px-3" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn text-light btn-sm px-4 shadow-sm fw-bold" style="background-color: #820100;">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Image Lightbox Modal -->
<div class="modal fade" id="imagePreviewModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content bg-dark text-white border-0">
            <div class="modal-header border-0 pb-0">
                <h6 class="modal-title text-truncate" id="previewCaption">Physical Verification Photo</h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body text-center p-3">
                <img id="previewImageSrc" src="" alt="Preview" class="img-fluid rounded shadow" style="max-height: 75vh; object-fit: contain;">
            </div>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/dataTables.buttons.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.bootstrap5.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.html5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.print.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
$(document).ready(function() {
    $('#beneficiariesTable').DataTable({
        responsive: true,
        pageLength: 10,
        dom: '<"d-flex justify-content-between align-items-center mb-3"Bf>rt<"d-flex justify-content-between align-items-center mt-3"ip>',
        buttons: [
            {
                extend: 'csv',
                text: '<i class="bi bi-file-earmark-spreadsheet me-1"></i> Export CSV',
                className: 'btn btn-sm btn-success fw-bold me-1',
                exportOptions: { columns: [0, 1, 2, 3, 4] }
            },
            {
                extend: 'excel',
                text: '<i class="bi bi-file-earmark-excel me-1"></i> Export Excel',
                className: 'btn btn-sm btn-primary fw-bold me-1',
                exportOptions: { columns: [0, 1, 2, 3, 4] }
            },
            {
                extend: 'print',
                text: '<i class="bi bi-printer me-1"></i> Print',
                className: 'btn btn-sm btn-secondary fw-bold me-1',
                exportOptions: { columns: [0, 1, 2, 3, 4] }
            }
        ],
        language: {
            search: "_INPUT_",
            searchPlaceholder: "Search beneficiaries, NIC, phone..."
        }
    });

    // Populate Edit Beneficiary Modal
    $('.edit-beneficiary-btn').on('click', function() {
        const btn = $(this);
        $('#edit_id').val(btn.data('id'));
        $('#edit_name').val(btn.data('name'));
        $('#edit_farm_reg_no').val(btn.data('farm_reg_no'));
        $('#edit_nic').val(btn.data('nic'));
        $('#edit_phone').val(btn.data('phone'));
        $('#edit_address').val(btn.data('address'));
        $('#edit_gps').val(btn.data('gps_location'));
        $('#edit_total_cost').val(btn.data('total_cost'));
        $('#edit_dept_pct').val(btn.data('dept_pct'));
        $('#edit_dept_amount').val(btn.data('dept_amount'));
        $('#edit_ben_pct').val(btn.data('ben_pct'));
        $('#edit_ben_amount').val(btn.data('ben_amount'));

        const workPct = btn.data('work_pct');
        $('#edit_work_done').val(workPct);
        $('#edit_work_range').val(workPct);

        $('#editBeneficiaryModal').modal('show');
    });

    // SweetAlert2 Delete Confirmation
    $('.delete-beneficiary-btn').on('click', function() {
        const id = $(this).data('id');
        const name = $(this).data('name');
        Swal.fire({
            title: 'Delete Beneficiary?',
            text: `Are you sure you want to delete ${name}? This action cannot be undone.`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#820100',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Yes, Delete Record'
        }).then((result) => {
            if (result.isConfirmed) {
                window.location = `processors/beneficiary_crud.php?action=delete&id=${id}&activity_id=<?= $activity_id ?>`;
            }
        });
    });
});

// Dynamic Financial Calculation Handlers
function calcContributions(prefix) {
    const total = parseFloat($(`#${prefix}_total_cost`).val()) || 0;
    const deptPct = parseFloat($(`#${prefix}_dept_pct`).val()) || 0;
    const benPct = Math.max(0, 100 - deptPct);
    $(`#${prefix}_ben_pct`).val(benPct.toFixed(1));

    $(`#${prefix}_dept_amount`).val(((deptPct / 100) * total).toFixed(2));
    $(`#${prefix}_ben_amount`).val(((benPct / 100) * total).toFixed(2));
}

function calcFromDeptPct(prefix) {
    const total = parseFloat($(`#${prefix}_total_cost`).val()) || 0;
    let deptPct = parseFloat($(`#${prefix}_dept_pct`).val()) || 0;
    if (deptPct > 100) deptPct = 100;
    if (deptPct < 0) deptPct = 0;
    $(`#${prefix}_dept_pct`).val(deptPct);

    const benPct = Math.max(0, 100 - deptPct);
    $(`#${prefix}_ben_pct`).val(benPct.toFixed(1));

    if (total > 0) {
        $(`#${prefix}_dept_amount`).val(((deptPct / 100) * total).toFixed(2));
        $(`#${prefix}_ben_amount`).val(((benPct / 100) * total).toFixed(2));
    }
}

function calcFromBenPct(prefix) {
    const total = parseFloat($(`#${prefix}_total_cost`).val()) || 0;
    let benPct = parseFloat($(`#${prefix}_ben_pct`).val()) || 0;
    if (benPct > 100) benPct = 100;
    if (benPct < 0) benPct = 0;
    $(`#${prefix}_ben_pct`).val(benPct);

    const deptPct = Math.max(0, 100 - benPct);
    $(`#${prefix}_dept_pct`).val(deptPct.toFixed(1));

    if (total > 0) {
        $(`#${prefix}_dept_amount`).val(((deptPct / 100) * total).toFixed(2));
        $(`#${prefix}_ben_amount`).val(((benPct / 100) * total).toFixed(2));
    }
}

function calcFromDeptAmt(prefix) {
    const total = parseFloat($(`#${prefix}_total_cost`).val()) || 0;
    const deptAmt = parseFloat($(`#${prefix}_dept_amount`).val()) || 0;
    if (total > 0) {
        const deptPct = Math.min(100, Math.max(0, (deptAmt / total) * 100));
        const benPct = Math.max(0, 100 - deptPct);
        const benAmt = Math.max(0, total - deptAmt);

        $(`#${prefix}_dept_pct`).val(deptPct.toFixed(1));
        $(`#${prefix}_ben_pct`).val(benPct.toFixed(1));
        $(`#${prefix}_ben_amount`).val(benAmt.toFixed(2));
    }
}

function calcFromBenAmt(prefix) {
    const total = parseFloat($(`#${prefix}_total_cost`).val()) || 0;
    const benAmt = parseFloat($(`#${prefix}_ben_amount`).val()) || 0;
    if (total > 0) {
        const benPct = Math.min(100, Math.max(0, (benAmt / total) * 100));
        const deptPct = Math.max(0, 100 - benPct);
        const deptAmt = Math.max(0, total - benAmt);

        $(`#${prefix}_ben_pct`).val(benPct.toFixed(1));
        $(`#${prefix}_dept_pct`).val(deptPct.toFixed(1));
        $(`#${prefix}_dept_amount`).val(deptAmt.toFixed(2));
    }
}

// Milestone work done sync
function syncWorkInput(prefix, val) {
    $(`#${prefix}_work_done`).val(val);
}
function syncWorkRange(prefix, val) {
    $(`#${prefix}_work_range`).val(val);
}

// GPS Fetch Helper
function getCurrentGPS(targetInputId) {
    if ("geolocation" in navigator) {
        navigator.geolocation.getCurrentPosition(
            function(position) {
                const coords = `${position.coords.latitude.toFixed(6)}, ${position.coords.longitude.toFixed(6)}`;
                document.getElementById(targetInputId).value = coords;
            },
            function(error) {
                Swal.fire({
                    icon: 'error',
                    title: 'GPS Error',
                    text: 'Unable to retrieve location: ' + error.message,
                    confirmButtonColor: '#820100'
                });
            },
            { enableHighAccuracy: true, timeout: 10000 }
        );
    } else {
        Swal.fire({
            icon: 'info',
            title: 'Not Supported',
            text: 'Geolocation is not supported by your browser.',
            confirmButtonColor: '#820100'
        });
    }
}

// Image Lightbox Preview
function previewImage(src, caption) {
    $('#previewImageSrc').attr('src', src);
    $('#previewCaption').text(caption);
    $('#imagePreviewModal').modal('show');
}
</script>

<?php require_once '../../../includes/footer.php'; ?>
