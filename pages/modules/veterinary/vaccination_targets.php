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
    if ($row = $result->fetch_assoc()) {
        $district_name = $row['name'];
    }
    $stmt->close();
}

if ($range_id) {
    $stmt = $mysqli->prepare("SELECT name FROM veterinary_ranges WHERE id = ?");
    $stmt->bind_param("i", $range_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        $range_name = $row['name'];
    }
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

// Fetch distinct enum options for animal species from Database
$species_options = [];
$enum_query = $mysqli->query("SHOW COLUMNS FROM animal_populations LIKE 'animal_type'");
if ($enum_query) {
    $enum_row = $enum_query->fetch_assoc();
    if ($enum_row) {
        preg_match_all("/'([^']+)'/", $enum_row['Type'], $matches);
        $species_options = $matches[1] ?? [];
    }
}


// 5. Fetch Target Data from annual_vaccination_targets
$vax_targets = [
    'id' => null,
    'target_fmd' => 0,
    'target_bq' => 0,
    'target_hs' => 0,
    'available_ldo_count' => 0,
    'allocated_ldo_target' => 0,
    'casual_vaccinators_needed' => 0,
    'allocated_man_days' => 0,
    'syringes_10cc_req' => 0,
    'needles_14g_dozen_req' => 0,
    'fuel_liters_per_month' => 0.00
];

$vax_stmt = $mysqli->prepare("SELECT * FROM annual_vaccination_targets WHERE range_id = ? AND year = ?");
$vax_stmt->bind_param("ii", $range_id, $selected_year);
$vax_stmt->execute();
$vax_res = $vax_stmt->get_result()->fetch_assoc();
if ($vax_res) {
    $vax_targets = $vax_res;
} else {
    // Auto-create default bounds index row
    $ins_stmt = $mysqli->prepare("INSERT INTO annual_vaccination_targets (range_id, year) VALUES (?, ?)");
    if ($ins_stmt) {
        $ins_stmt->bind_param("ii", $range_id, $selected_year);
        $ins_stmt->execute();
        $new_id = $ins_stmt->insert_id;
        $ins_stmt->close();

        $vax_stmt2 = $mysqli->prepare("SELECT * FROM annual_vaccination_targets WHERE id = ?");
        if ($vax_stmt2) {
            $vax_stmt2->bind_param("i", $new_id);
            $vax_stmt2->execute();
            $vax_targets = $vax_stmt2->get_result()->fetch_assoc();
            $vax_stmt2->close();
        }
    }
}
$vax_stmt->close();

// Fetch Animal Populations Breakdown
$animal_pop_data = [];
$anim_stmt = $mysqli->prepare("SELECT animal_type, quantity FROM animal_populations WHERE range_id = ? AND year = ?");
if ($anim_stmt) {
    $anim_stmt->bind_param("ii", $range_id, $selected_year);
    $anim_stmt->execute();
    $anim_res = $anim_stmt->get_result();
    while ($row = $anim_res->fetch_assoc()) {
        $animal_pop_data[$row['animal_type']] = $row['quantity'];
    }
    $anim_stmt->close();
}

$deployed_staff = [];
if (!empty($vax_targets['id'])) {
    $staff_stmt = $mysqli->prepare("SELECT * FROM casual_vaccinator_deployments WHERE vaccination_target_id = ?");
    if ($staff_stmt) {
        $staff_stmt->bind_param("i", $vax_targets['id']);
        $staff_stmt->execute();
        $staff_res = $staff_stmt->get_result();
        while ($st = $staff_res->fetch_assoc()) {
            $deployed_staff[] = htmlspecialchars($st['full_name']) . " (NIC: " . htmlspecialchars($st['nic_no']) . ")";
        }
        $staff_stmt->close();
    }
}
$deployed_staff_str = !empty($deployed_staff) ? implode("<br>", $deployed_staff) : '<span class="text-muted small">None Deployed</span>';

require_once '../../../includes/header.php';
?>

<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">


<div class="d-flex w-100 align-items-stretch min-vh-100">

    <div class="flex-shrink-0" style="background-color: #370709;">
        <?php require_once '../../../includes/sidebar.php'; ?>
    </div>

    <div id="layoutSidenav_content">
        <main class="container-fluid px-4 pt-4">

            <div class="mb-4 d-flex justify-content-between align-items-center">
                <div>
                    <h4 class="fw-bold mb-1" style="color: #370709;">Annual Targets & Field Metrics</h4>
                    <span class="badge" style="background-color: #d4c7b7; color: #370709;"><i class="bi bi-geo-alt-fill me-1"></i><?= htmlspecialchars($range_name) ?> Range</span>
                    <span class="badge text-light" style="background-color: #a07174;"><i class="bi bi-building me-1"></i><?= htmlspecialchars($district_name) ?> District</span>
                </div>
                <div>
                    <div class="input-group">
                        <label class="input-group-text fw-bold text-light" style="background-color: #820100; border-color: #820100;">Evaluation Year</label>
                        <select id="dashboardYearFilter" class="form-select border-secondary" onchange="location = '?year='+this.value;">
                            <option value="2026" <?= $selected_year == 2026 ? 'selected' : '' ?>>2026</option>
                            <option value="2025" <?= $selected_year == 2025 ? 'selected' : '' ?>>2025</option>
                            <option value="2024" <?= $selected_year == 2024 ? 'selected' : '' ?>>2024</option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Animal Species Population Table -->
            <div class="card gov-card mb-4 shadow-sm border-0">
                <div class="card-header bg-white pt-3 px-4 border-0 d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="fw-bold mb-0" style="color: #370709;"><i class="bi bi-archive-fill me-2 text-danger"></i>Species Populations</h6>
                        <p class="text-muted small mb-0">Review and edit live population counts for species in this range/year.</p>
                    </div>
                    <div>
                        <button class="btn btn-sm btn-dark fw-bold" data-bs-toggle="modal" data-bs-target="#addPopulationModal"><i class="bi bi-plus-circle me-1"></i> Add / Update Population</button>
                    </div>
                </div>
                <div class="card-body px-4 pb-4">
                    <div class="table-responsive">
                        <table id="animalPopTable" class="table table-sm table-striped table-bordered align-middle small bg-white m-0">
                            <thead style="background-color: #d4c7b7; color: #370709;">
                                <tr>
                                    <th>Species</th>
                                    <th class="text-center">Live Count</th>
                                    <th class="text-center">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                // Build a union of species from populations and the enum options
                                $all_species = array_unique(array_merge(array_keys($animal_pop_data), $species_options));
                                foreach ($all_species as $sp) {
                                    $qty = isset($animal_pop_data[$sp]) ? intval($animal_pop_data[$sp]) : 0;
                                    $safe_sp = htmlspecialchars($sp);
                                    echo '<tr>';
                                    echo '<td>' . $safe_sp . '</td>';
                                    echo '<td class="text-center fw-bold">' . number_format($qty) . '</td>';
                                    echo '<td class="text-center"><button class="btn btn-xs btn-outline-primary edit-pop-btn" data-species="' . $safe_sp . '" data-qty="' . $qty . '"><i class="bi bi-pencil-square me-1"></i>Edit</button></td>';
                                    echo '</tr>';
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <?php if (isset($_SESSION['msg'])): ?>
                <div class="alert alert-<?= $_SESSION['msg_type'] ?> alert-dismissible fade show shadow-sm py-2 px-3 mb-4 small" role="alert">
                    <?= $_SESSION['msg'] ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
                <?php unset($_SESSION['msg'], $_SESSION['msg_type']); ?>
            <?php endif; ?>


            <!-- Combined Species population, targets & staff deployment matrix card -->
            <div class="card gov-card mb-4 shadow-sm border-0">
                <div class="card-header bg-white pt-4 px-4 border-0 d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="fw-bold mb-1" style="color: #370709;"><i class="bi bi-grid-3x3-gap-fill me-2 text-danger"></i>Combined Populations, Targets & Staff Deployment Matrix</h5>
                        <p class="text-muted small mb-0">Unified dashboard aligning live animal populations, configured vaccination targets, and assigned casual vaccinators.</p>
                    </div>
                    <div>
                        <button class="btn btn-sm text-light fw-bold" style="background-color: #185dbd;" data-bs-toggle="modal" data-bs-target="#recordVaccinationModal">
                            <i class="bi bi-shield-plus me-1"></i> Record Vaccination
                        </button>
                    </div>
                </div>
                <div class="card-body px-4 pb-4">
                    <div class="table-responsive">
                        <table id="combinedMatrixTable" class="table table-striped table-hover table-bordered align-middle small bg-white text-dark m-0">
                            <thead style="background-color: #d4c7b7; color: #370709;">
                                <tr>
                                    <th>Animal Species</th>
                                    <th class="text-center">Live Population Count</th>
                                    <th class="text-center">FMD Vaccination Target</th>
                                    <th class="text-center">BQ Vaccination Target</th>
                                    <th class="text-center">HS Vaccination Target</th>
                                    <th class="text-center">Available LDO Count</th>
                                    <th class="text-center">Allocated Target for LDO</th>
                                    <th class="text-center">Casual Vaccinators Needed</th>
                                    <th class="text-center">Assigned Deployed Personnel</th>
                                    <th class="text-center">Allocated Staff Man-Days</th>
                                    <th class="text-center">Nylon Syringes (10CC)</th>
                                    <th class="text-center">Needle 14G</th>
                                    <th class="text-center">Fuel Allocation (L)</th>
                                </tr>
                            </thead>
                            <tbody>

                            </tbody>
                        </table>
                    </div>

                    <!-- Deployed Casual Staff Details -->
                    <div class="mt-3">
                        <h6 class="fw-bold small mb-2" style="color: #370709;"><i class="bi bi-people-fill me-1 text-danger"></i>Deployed Casual Staff</h6>
                        <div class="list-group">
                            <?php
                            if (!empty($vax_targets['id'])) {
                                $ds = $mysqli->prepare("SELECT id, full_name, nic_no FROM casual_vaccinator_deployments WHERE vaccination_target_id = ? ORDER BY id ASC");
                                if ($ds) {
                                    $ds->bind_param("i", $vax_targets['id']);
                                    $ds->execute();
                                    $dres = $ds->get_result();
                                    if ($dres->num_rows > 0) {
                                        while ($row = $dres->fetch_assoc()) {
                                            $sid = intval($row['id']);
                                            $sname = htmlspecialchars($row['full_name']);
                                            $snic = htmlspecialchars($row['nic_no']);
                                            echo '<div class="list-group-item d-flex justify-content-between align-items-center">';
                                            echo '<div><span class="fw-bold text-dark d-block">' . $sname . '</span><small class="text-muted">NIC: ' . $snic . '</small></div>';
                                            echo '<div class="btn-group btn-group-sm" role="group">';
                                            echo '<button class="btn btn-outline-primary edit-staff-btn" data-id="' . $sid . '" data-name="' . $sname . '" data-nic="' . $snic . '" title="Edit"><i class="bi bi-pencil-square"></i></button>';
                                            echo '<form method="POST" action="processors/delete_vaccinator_deployment.php" onsubmit="return confirm(\'Delete this vaccinator?\');" style="display:inline-block; margin:0;">';
                                            echo '<input type="hidden" name="id" value="' . $sid . '">';
                                            echo '<input type="hidden" name="year" value="' . htmlspecialchars($selected_year) . '">';
                                            echo '<button type="submit" class="btn btn-outline-danger" title="Delete"><i class="bi bi-trash"></i></button>';
                                            echo '</form>';
                                            echo '</div>';
                                            echo '</div>';
                                        }
                                    } else {
                                        echo '<div class="list-group-item text-muted small">No deployed staff assigned.</div>';
                                    }
                                    $ds->close();
                                }
                            } else {
                                echo '<div class="list-group-item text-muted small">Setup target matrix to assign staff.</div>';
                            }
                            ?>
                        </div>
                    </div>


                    </div>
                </div>
            </div>



    </div>
    </main>
</div>

<!-- Record Vaccination Modal -->
<?php include 'models/vaccination_model.php'; ?>

<!-- Deploy Casual Staff Modal -->
<?php include 'models/vaccination_staff.php'; ?>

<!-- Add/Update Species Population Modal -->
<?php include 'models/add_animal_population.php'; ?>


<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>

<script>
    $(document).ready(function() {
        $('#combinedMatrixTable').DataTable({
            responsive: true,
            pageLength: 10,
            lengthMenu: [5, 10, 25]
        });

        $('#vaxResourcesTable').DataTable({
            responsive: true,
            pageLength: 10,
            lengthMenu: [5, 10, 25],
            ordering: false
        });
        $('#animalPopTable').DataTable({
            responsive: true,
            pageLength: 10,
            lengthMenu: [5, 10, 25]
        });
        $('#recentVaxTable').DataTable({
            responsive: true,
            pageLength: 10,
            lengthMenu: [5, 10, 25]
        });
    });
</script>

<script>
    // Edit population button behavior: populate and show the Add/Update Population modal
    $(document).on('click', '.edit-pop-btn', function(e) {
        e.preventDefault();
        var species = $(this).data('species');
        var qty = $(this).data('qty');

        var $modal = $('#addPopulationModal');
        var $select = $modal.find('select[name="animal_type"]');
        var $qty = $modal.find('input[name="quantity"]');

        // If the species option does not exist in the select, append it
        if ($select.find('option[value="' + species + '"]').length === 0) {
            $select.append('<option value="' + $('<div/>').text(species).html() + '">' + $('<div/>').text(species).html() + '</option>');
        }

        $select.val(species);
        $qty.val(qty);

        var bsModal = new bootstrap.Modal(document.getElementById('addPopulationModal'));
        bsModal.show();
    });

    // Edit staff button: populate deploy staff modal and show
    $(document).on('click', '.edit-staff-btn', function(e) {
        e.preventDefault();
        var id = $(this).data('id');
        var name = $(this).data('name');
        var nic = $(this).data('nic');

        var $modal = $('#deployPersonnelModal');
        $modal.find('input[name="full_name"]').val(name);
        $modal.find('input[name="nic_no"]').val(nic);
        $modal.find('input#deploy_staff_id').val(id);

        var bs = new bootstrap.Modal(document.getElementById('deployPersonnelModal'));
        bs.show();
    });
</script>

<?php
require_once '../../../includes/footer.php';
?>