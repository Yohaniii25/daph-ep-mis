<?php
session_start();
require_once '../../../config/db_connect.php';

// 1. Session and Role Guard
if (!isset($_SESSION['logged_in']) || $_SESSION['role'] !== 'veterinary_surgeon') {
    header("Location: ../../../../index.php");
    exit();
}

if (!isset($_SESSION['full_name'])) {
    $_SESSION['full_name'] = $_SESSION['username'] ?? 'Veterinary Surgeon';
}

$full_name   = $_SESSION['full_name'];
$range_id    = $_SESSION['range_id'] ?? null;
$district_id = $_SESSION['district_id'] ?? null;

if (empty($range_id)) {
    die('<div class="alert alert-danger text-center p-5 m-5">Error: Your account is not assigned to any Veterinary Range.</div>');
}

// 2. Fallback Definitions
$district_name = 'Unknown District';
$range_name    = 'Unknown Range';
$selected_year = isset($_GET['year']) ? intval($_GET['year']) : 2026;

// 3. Fetch Core Structural Meta Information
if ($district_id) {
    $stmt = $mysqli->prepare("SELECT name FROM districts WHERE id = ?");
    $stmt->bind_param("i", $district_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) { $district_name = $row['name']; }
    $stmt->close();
}

if ($range_id) {
    $stmt = $mysqli->prepare("SELECT name FROM veterinary_ranges WHERE id = ?");
    $stmt->bind_param("i", $range_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) { $range_name = $row['name']; }
    $stmt->close();
}

// 4. Dynamic Data Fetch: Live Lookups against existing animal_populations table
$total_population = 0;
$pop_stmt = $mysqli->prepare("SELECT SUM(quantity) as total FROM animal_populations WHERE range_id = ? AND year = ?");
$pop_stmt->bind_param("ii", $range_id, $selected_year);
$pop_stmt->execute();
$pop_result = $pop_stmt->get_result()->fetch_assoc();
if ($pop_result && $pop_result['total']) {
    $total_population = $pop_result['total'];
}
$pop_stmt->close();

// 5. Fetch Target Data from annual_vaccination_targets
$vax_targets = [
    'id' => null, 'target_fmd' => 0, 'target_bq' => 0, 'target_hs' => 0,
    'available_ldo_count' => 0, 'allocated_ldo_target' => 0,
    'casual_vaccinators_needed' => 0, 'allocated_man_days' => 0,
    'syringes_10cc_req' => 0, 'needles_14g_dozen_req' => 0, 'fuel_liters_per_month' => 0.00
];

$vax_stmt = $mysqli->prepare("SELECT * FROM annual_vaccination_targets WHERE range_id = ? AND year = ?");
$vax_stmt->bind_param("ii", $range_id, $selected_year);
$vax_stmt->execute();
$vax_res = $vax_stmt->get_result()->fetch_assoc();
if ($vax_res) {
    $vax_targets = $vax_res;
}
$vax_stmt->close();

require_once '../../../includes/header.php';
?>

<!-- DataTables CSS Dependencies inside head execution flow -->
<link href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css" rel="stylesheet">

<div class="d-flex w-100 align-items-stretch min-vh-100">
    
    <!-- Left Static Sidebar Container Allocation -->
    <div class="flex-shrink-0" style="background-color: #370709;">
        <?php require_once '../../../includes/sidebar.php'; ?>
    </div>

    <!-- Right Content Workspace Area (Next to Sidebar) -->
    <div class="flex-grow-1 p-4 bg-light" style="overflow-x: hidden;">
        <div class="container-fluid p-0">
            
            <!-- Header Profile Banner Info Wrap -->
            <div class="mb-4 p-4 rounded shadow-sm d-flex justify-content-between align-items-center bg-white border-start border-4" style="border-color: #820100 !important;">
                <div>
                    <h4 class="fw-bold mb-1" style="color: #370709;">Annual Targets & Action Metrics</h4>
                    <span class="badge" style="background-color: #d4c7b7; color: #370709;"><i class="bi bi-geo-alt-fill me-1"></i><?= htmlspecialchars($range_name) ?> Range</span>
                    <span class="badge text-white" style="background-color: #a07174;"><i class="bi bi-building me-1"></i><?= htmlspecialchars($district_name) ?> District</span>
                </div>
                <div>
                    <div class="input-group">
                        <label class="input-group-text fw-bold text-white" style="background-color: #820100; border-color: #820100;">Fiscal Evaluation Year</label>
                        <select id="dashboardYearFilter" class="form-select border-secondary" onchange="location = '?year='+this.value;">
                            <option value="2026" <?= $selected_year == 2026 ? 'selected' : '' ?>>2026</option>
                            <option value="2025" <?= $selected_year == 2025 ? 'selected' : '' ?>>2025</option>
                            <option value="2024" <?= $selected_year == 2024 ? 'selected' : '' ?>>2024</option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- QUICK ACTIONS & STATS HIGHLIGHT CARDS -->
            <div class="row g-3 mb-4">
                <div class="col-6 col-lg-3">
                    <div class="card border-0 shadow-sm text-white" style="background-color: #820100;">
                        <div class="card-body">
                            <h6 class="small text-uppercase text-white-50">Total Livestock Population</h6>
                            <h3 class="fw-bold mb-0"><?= number_format($total_population) ?></h3>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-lg-3">
                    <div class="card border-0 shadow-sm text-white" style="background-color: #185dbd;">
                        <div class="card-body">
                            <h6 class="small text-uppercase text-white-50">FMD Campaign Target</h6>
                            <h3 class="fw-bold mb-0"><?= number_format($vax_targets['target_fmd']) ?></h3>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-lg-3">
                    <div class="card border-0 shadow-sm text-dark" style="background-color: #efbe2c;">
                        <div class="card-body">
                            <h6 class="small text-uppercase text-dark-50">BQ Campaign Target</h6>
                            <h3 class="fw-bold mb-0"><?= number_format($vax_targets['target_bq']) ?></h3>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-lg-3">
                    <div class="card border-0 shadow-sm text-white" style="background-color: #8d170e;">
                        <div class="card-body">
                            <h6 class="small text-uppercase text-white-50">HS Campaign Target</h6>
                            <h3 class="fw-bold mb-0"><?= number_format($vax_targets['target_hs']) ?></h3>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row g-4">
                <!-- LEFT CONTENT REGION -->
                <div class="col-12 col-xl-8">
                    
                    <!-- Section 1: Vaccination Matrix Setup -->
                    <div class="card border-0 shadow-sm mb-4">
                        <div class="card-header bg-white pt-3 border-0 d-flex justify-content-between align-items-center">
                            <h6 class="fw-bold mb-0" style="color: #370709;"><i class="bi bi-shield-plus me-2"></i>Vaccination Targets & Resource Ledger</h6>
                            <button class="btn btn-sm text-white fw-bold" style="background-color: #820100;" data-bs-toggle="modal" data-bs-target="#vaxTargetModal">
                                <i class="bi bi-sliders me-1"></i> Configure Matrix
                            </button>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-hover table-bordered align-middle small bg-white text-dark m-0">
                                    <thead style="background-color: #d4c7b7; color: #370709;">
                                        <tr>
                                            <th>Resource Field Component</th>
                                            <th class="text-center" style="width: 40%;">Allocation Configuration Value</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr><td>Available LDO Count</td><td class="text-center fw-bold text-secondary"><?= $vax_targets['available_ldo_count'] ?> Officers</td></tr>
                                        <tr><td>Allocated Target assigned to LDO</td><td class="text-center fw-bold" style="color: #185dbd;"><?= $vax_targets['allocated_ldo_target'] ?> Operations</td></tr>
                                        <tr><td>Casual Vaccinators Needed Personnel count</td><td class="text-center fw-bold text-danger"><?= $vax_targets['casual_vaccinators_needed'] ?> Staff</td></tr>
                                        <tr><td>Allocated Staff Field Man-Days</td><td class="text-center fw-bold"><?= $vax_targets['allocated_man_days'] ?> Days</td></tr>
                                        <tr><td>Nylon Syringes Requirement (10CC)</td><td class="text-center fw-bold"><?= $vax_targets['syringes_10cc_req'] ?> Pcs</td></tr>
                                        <tr><td>Needle 14G Requirement Tracker</td><td class="text-center fw-bold"><?= $vax_targets['needles_14g_dozen_req'] ?> Dozen</td></tr>
                                        <tr><td>Logistics Fuel Allocation Scale</td><td class="text-center fw-bold" style="color: #8d170e;"><?= number_format($vax_targets['fuel_liters_per_month'], 2) ?> L / Month</td></tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <!-- Section 2: Production Activities with JS DataTables -->
                    <div class="card border-0 shadow-sm mb-4">
                        <div class="card-header bg-white pt-3 border-0 d-flex justify-content-between align-items-center">
                            <h6 class="fw-bold mb-0" style="color: #370709;"><i class="bi bi-egg-fried me-2"></i>Production & Distribution Goals</h6>
                            <button class="btn btn-sm text-white fw-bold" style="background-color: #370709;" data-bs-toggle="modal" data-bs-target="#productionTargetModal">
                                <i class="bi bi-plus-lg me-1"></i> Add Goal Item
                            </button>
                        </div>
                        <div class="card-body">
                            <table class="table table-striped table-bordered align-middle w-100" id="productionTargetsDataTable">
                                <thead style="background-color: #370709; color: #fff;">
                                    <tr>
                                        <th>Activity Metric Name</th>
                                        <th>Target Scope Category</th>
                                        <th class="text-center">Target Goal</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $prod_stmt = $mysqli->prepare("SELECT * FROM production_activity_targets WHERE range_id = ? AND year = ?");
                                    $prod_stmt->bind_param("ii", $range_id, $selected_year);
                                    $prod_stmt->execute();
                                    $prod_res = $prod_stmt->get_result();
                                    while ($row = $prod_res->fetch_assoc()):
                                    ?>
                                        <tr>
                                            <td class="fw-bold"><?= htmlspecialchars($row['activity_name']) ?></td>
                                            <td>
                                                <?php if($row['animal_category']): ?>
                                                    <span class="badge text-dark" style="background-color: #d4c7b7;"><?= $row['animal_category'] ?></span>
                                                    <?= $row['animal_category'] === 'Other' ? '('.htmlspecialchars($row['animal_category_other']).')' : '' ?>
                                                <?php else: ?>
                                                    <span class="text-muted small">General Framework</span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="text-center fw-bold text-primary"><?= number_format($row['target_quantity']) ?></td>
                                        </tr>
                                    <?php endwhile; $prod_stmt->close(); ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                </div>

                <!-- RIGHT AREA: STAFF SIDEBAR -->
                <div class="col-12 col-xl-4">
                    <div class="card border-0 shadow-sm mb-4">
                        <div class="card-header bg-white pt-3 border-0 d-flex justify-content-between align-items-center">
                            <h6 class="fw-bold mb-0 text-dark"><i class="bi bi-people-fill me-2"></i>Casual Field Personnel</h6>
                            <button class="btn btn-xs btn-outline-dark px-2 btn-sm" data-bs-toggle="modal" data-bs-target="#deployPersonnelModal" <?= empty($vax_targets['id']) ? 'disabled' : '' ?>>
                                <i class="bi bi-person-plus-fill"></i> Assign
                            </button>
                        </div>
                        <div class="card-body p-0">
                            <div class="list-group list-group-flush small">
                                <?php
                                if (!empty($vax_targets['id'])) {
                                    $staff_stmt = $mysqli->prepare("SELECT * FROM casual_vaccinator_deployments WHERE vaccination_target_id = ?");
                                    $staff_stmt->bind_param("i", $vax_targets['id']);
                                    $staff_stmt->execute();
                                    $staff_res = $staff_stmt->get_result();
                                    if ($staff_res->num_rows > 0) {
                                        while ($st = $staff_res->fetch_assoc()) {
                                            echo '<div class="list-group-item p-3 d-flex justify-content-between align-items-center">
                                                    <div>
                                                        <h6 class="fw-bold mb-0 text-dark">'.htmlspecialchars($st['full_name']).'</h6>
                                                        <small class="text-muted">NIC: '.htmlspecialchars($st['nic_no']).'</small>
                                                    </div>
                                                    <span class="badge text-white" style="background-color: #a07174;">Active Vaccinator</span>
                                                  </div>';
                                        }
                                    } else {
                                        echo '<p class="text-muted small text-center p-4 m-0">No active deployment records attached.</p>';
                                    }
                                    $staff_stmt->close();
                                } else {
                                    echo '<p class="text-muted small text-center p-4 m-0">Initialize matrix to assign staff.</p>';
                                }
                                ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

<!-- ==========================================
     MODAL WINDOW 1: CONFIG VACCINATION PARAMETERS
     ========================================== -->
<div class="modal fade text-dark" id="vaxTargetModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form action="save_vax_targets.php" method="POST">
                <input type="hidden" name="year" value="<?= $selected_year ?>">
                <input type="hidden" name="range_id" value="<?= $range_id ?>">
                
                <div class="modal-header text-white" style="background-color: #370709;">
                    <h5 class="modal-title h6 fw-bold"><i class="bi bi-sliders me-2"></i>Configure Vaccination & Resource Allocation Matrix</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4 small">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label fw-bold text-secondary">Target FMD Count</label>
                            <input type="number" name="target_fmd" class="form-control form-control-sm" value="<?= $vax_targets['target_fmd'] ?>" min="0">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold text-secondary">Target BQ Count</label>
                            <input type="number" name="target_bq" class="form-control form-control-sm" value="<?= $vax_targets['target_bq'] ?>" min="0">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold text-secondary">Target HS Count</label>
                            <input type="number" name="target_hs" class="form-control form-control-sm" value="<?= $vax_targets['target_hs'] ?>" min="0">
                        </div>
                        
                        <div class="col-12"><hr class="my-1"></div>
                        
                        <div class="col-md-3">
                            <label class="form-label fw-bold text-secondary">Available LDO Count</label>
                            <input type="number" name="available_ldo_count" class="form-control form-control-sm" value="<?= $vax_targets['available_ldo_count'] ?>" min="0">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-bold text-secondary">Allocated LDO Target</label>
                            <input type="number" name="allocated_ldo_target" class="form-control form-control-sm" value="<?= $vax_targets['allocated_ldo_target'] ?>" min="0">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-bold text-secondary">Casual Vaccinators Need</label>
                            <input type="number" name="casual_vaccinators_needed" class="form-control form-control-sm" value="<?= $vax_targets['casual_vaccinators_needed'] ?>" min="0">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-bold text-secondary">Allocated Man Days</label>
                            <input type="number" name="allocated_man_days" class="form-control form-control-sm" value="<?= $vax_targets['allocated_man_days'] ?>" min="0">
                        </div>
                        
                        <div class="col-12"><hr class="my-1"></div>
                        
                        <div class="col-md-4">
                            <label class="form-label fw-bold text-secondary">Nylon Syringes Req (10CC)</label>
                            <input type="number" name="syringes_10cc_req" class="form-control form-control-sm" value="<?= $vax_targets['syringes_10cc_req'] ?>" min="0">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold text-secondary">Needle 14G (In Dozen)</label>
                            <input type="number" name="needles_14g_dozen_req" class="form-control form-control-sm" value="<?= $vax_targets['needles_14g_dozen_req'] ?>" min="0">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold text-secondary">Fuel Allocation (Liters)</label>
                            <input type="number" step="0.01" name="fuel_liters_per_month" class="form-control form-control-sm" value="<?= $vax_targets['fuel_liters_per_month'] ?>" min="0">
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light p-2">
                    <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-sm text-white" style="background-color: #370709;">Commit Target Metrics</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ==========================================
     MODAL WINDOW 2: PRODUCTION TARGET LOGS
     ========================================== -->
<div class="modal fade text-dark" id="productionTargetModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="save_production_target.php" method="POST">
                <input type="hidden" name="year" value="<?= $selected_year ?>">
                <input type="hidden" name="range_id" value="<?= $range_id ?>">
                
                <div class="modal-header text-white" style="background-color: #370709;">
                    <h5 class="modal-title h6 fw-bold"><i class="bi bi-box-seam me-2"></i>Append Production Target Configuration Item</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-3 small">
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label fw-bold text-secondary">Activity Target Track Name</label>
                            <input type="text" name="activity_name" class="form-control form-control-sm" placeholder="e.g., Cattle Shed construction, Stud goats issue" required>
                        </div>
                        
                        <div class="col-12">
                            <div class="form-check form-switch bg-light p-2 rounded ps-5">
                                <input class="form-check-input" type="checkbox" id="isAnimalSpecificToggle" checked onchange="toggleAnimalSelectionLayout(this.checked)">
                                <label class="form-check-label fw-bold text-dark small" for="isAnimalSpecificToggle">Is this activity animal-specific?</label>
                            </div>
                        </div>

                        <div id="animalSelectionWrapperBlock" class="row g-2 m-0 p-0 w-100">
                            <div class="col-6">
                                <label class="form-label fw-bold text-secondary">Target Animal Scope</label>
                                <select id="animalCategoryField" name="animal_category" class="form-select form-select-sm" onchange="toggleCustomAnimalSpecificationInput(this.value)">
                                    <option value="Cow">Cow</option>
                                    <option value="Buffalo">Buffalo</option>
                                    <option value="Goat">Goat</option>
                                    <option value="Chicken">Chicken</option>
                                    <option value="Pig">Pig</option>
                                    <option value="Other">Other (Specify Below)</option>
                                </select>
                            </div>
                            <div class="col-6">
                                <label class="form-label fw-bold text-secondary">Target Goal (Quantity)</label>
                                <input type="number" name="target_quantity" class="form-control form-control-sm" min="1" value="1" required>
                            </div>
                            <div class="col-12 mt-2" id="customAnimalSpecificationInputBlock" style="display:none;">
                                <label class="form-label fw-bold text-danger">Specify Custom Animal Classification</label>
                                <input type="text" id="customAnimalInput" name="animal_category_other" class="form-control form-control-sm" placeholder="e.g., Swine, Duck">
                            </div>
                        </div>

                    </div>
                </div>
                <div class="modal-footer bg-light p-2">
                    <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-sm text-white" style="background-color: #370709;">Save Production Entry</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- JS CDN dependencies and DataTables initialization engine scripts -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>

<script>
$(document).ready(function() {
    $('#productionTargetsDataTable').DataTable({
        responsive: true,
        pageLength: 10,
        lengthMenu: [5, 10, 25, 50],
        language: {
            search: "_INPUT_",
            searchPlaceholder: "Search goals..."
        }
    });
});

function toggleAnimalSelectionLayout(isAnimalSpecific) {
    const block = document.getElementById('animalSelectionWrapperBlock');
    const catField = document.getElementById('animalCategoryField');
    const customInput = document.getElementById('customAnimalInput');
    
    if (!isAnimalSpecific) {
        block.style.opacity = '0.5';
        catField.disabled = true;
        customInput.disabled = true;
        catField.value = '';
    } else {
        block.style.opacity = '1';
        catField.disabled = false;
        customInput.disabled = false;
        catField.value = 'Cow';
    }
}

function toggleCustomAnimalSpecificationInput(selectedValue) {
    const customBlock = document.getElementById('customAnimalSpecificationInputBlock');
    const customInput = document.getElementById('customAnimalInput');
    if (selectedValue === 'Other') {
        customBlock.style.display = 'block';
        customInput.setAttribute('required', 'required');
    } else {
        customBlock.style.display = 'none';
        customInput.removeAttribute('required');
    }
}
</script>

<?php 
require_once '../../../includes/footer.php'; 
?>
