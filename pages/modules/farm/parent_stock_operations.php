<?php
// pages/modules/farm/parent_stock_operations.php -> Daily Egg Collection & Annex 01 Register Module
require_once '../../../includes/header.php';
require_once '../../../config/db_connect.php';

if ($_SESSION['role'] !== 'farms_dd') {
    die("Access denied");
}

$user_id = $_SESSION['user_id'] ?? 1;

// 1. Fetch Daily Egg Collection Records (scoped to user-created batches)
$collection_sql = "SELECT dep.*, b.batch_number AS batch_name, c.cage_name 
                   FROM daily_egg_production dep
                   JOIN vaccine_batches b ON dep.batch_id = b.id
                   JOIN cages c ON dep.cage_id = c.id
                   WHERE b.user_id = ?
                   ORDER BY dep.collection_date DESC, dep.id DESC";
$stmt = $mysqli->prepare($collection_sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$collection_res = $stmt->get_result();
$collections = [];
if ($collection_res) {
    while ($row = $collection_res->fetch_assoc()) {
        $collections[] = $row;
    }
}
$stmt->close();

// 2. Fetch Simplified Cages
$cages_res = $mysqli->query("SELECT id, cage_name FROM cages ORDER BY cage_name");
$cages = [];
if ($cages_res) {
    while ($row = $cages_res->fetch_assoc()) {
        $cages[] = $row;
    }
}

// 3. Fetch User-Scoped Batches
$batch_stmt = $mysqli->prepare("SELECT id, batch_number AS batch_name, created_at FROM vaccine_batches WHERE user_id = ? ORDER BY id DESC");
$batch_stmt->bind_param("i", $user_id);
$batch_stmt->execute();
$batch_res = $batch_stmt->get_result();
$batches = [];
if ($batch_res) {
    while ($row = $batch_res->fetch_assoc()) {
        $batches[] = $row;
    }
}
$batch_stmt->close();

?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2 class="fw-bold text-dark m-0">Parent Stock Operations & Egg Register</h2>
        <small class="text-muted">Manage daily collections, cages, batches, and view Annex 01 monthly reports.</small>
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

<!-- Main 2-Tab Navigation Bar -->
<ul class="nav nav-pills mb-4 bg-white p-2 rounded shadow-sm border" id="mainModuleTabs" role="tablist">
    <li class="nav-item me-2" role="presentation">
        <button class="nav-link active fw-bold py-2 px-4" id="daily-ops-tab" data-bs-toggle="pill" data-bs-target="#daily-ops-pane" type="button" role="tab" aria-controls="daily-ops-pane" aria-selected="true" style="--bs-nav-pills-link-active-bg: #370709;">
            <i class="bi bi-ui-checks me-2"></i>Daily Operations (Data Entry)
        </button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link fw-bold py-2 px-4" id="annex-01-tab" data-bs-toggle="pill" data-bs-target="#annex-01-pane" type="button" role="tab" aria-controls="annex-01-pane" aria-selected="false" style="--bs-nav-pills-link-active-bg: #198754;">
            <i class="bi bi-grid-3x3-gap-fill me-2 text-warning"></i>Annex 01 - Monthly Register
        </button>
    </li>
</ul>

<div class="tab-content" id="mainModuleTabsContent">
    <!-- ================= TAB 1: DAILY OPERATIONS ================= -->
    <div class="tab-pane fade show active" id="daily-ops-pane" role="tabpanel" aria-labelledby="daily-ops-tab" tabindex="0">

        <!-- Quick Options Card -->
        <div class="card border-0 shadow-sm mb-4" style="border-radius: 12px;">
            <div class="card-header bg-white py-3 border-0">
                <h5 class="fw-bold text-dark m-0">Quick Options</h5>
            </div>
            <div class="card-body pt-0 pb-4">
                <div class="row g-3">
                    <div class="col-md-4">
                        <button style="background-color: #370709; border-color: #370709;" class="btn btn-primary w-100 py-3" data-bs-toggle="modal" data-bs-target="#eggModal">
                            <i class="bi bi-egg-fill fs-5 mb-1 d-block"></i>
                            Add Daily Egg Collection
                        </button>
                    </div>
                    <div class="col-md-4">
                        <button class="btn btn-success w-100 py-3" data-bs-toggle="modal" data-bs-target="#salesReturnsModal">
                            <i class="bi bi-cart-check-fill fs-5 mb-1 d-block"></i>
                            Log Sales & Returns
                        </button>
                    </div>
                    <div class="col-md-4">
                        <button class="btn btn-secondary w-100 py-3" data-bs-toggle="modal" data-bs-target="#addCageModal">
                            <i class="bi bi-grid-3x3 fs-5 mb-1 d-block text-light"></i>
                            Add Cage
                        </button>
                    </div>
                    <div class="col-md-4">
                        <button class="btn btn-warning w-100 py-3 text-dark" data-bs-toggle="modal" data-bs-target="#addBatchModal">
                            <i class="bi bi-tags-fill fs-5 mb-1 d-block"></i>
                            Add Batch
                        </button>
                    </div>
                    <div class="col-md-4">
                        <button class="btn btn-dark w-100 py-3 text-light" data-bs-toggle="modal" data-bs-target="#exportColumnsModal">
                            <i class="bi bi-file-earmark-spreadsheet-fill fs-5 mb-1 d-block text-success"></i>
                            Export Custom Report
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Sub-Tabs Component -->
        <ul class="nav nav-tabs mb-4 px-3 border-bottom-0" id="eggModuleTabs" role="tablist">
            <li class="nav-item shadow-sm" role="presentation" style="margin-right: 4px;">
                <button class="nav-link active fw-bold text-dark border-0 py-3 px-4" id="records-tab" data-bs-toggle="tab" data-bs-target="#records-pane" type="button" role="tab" aria-controls="records-pane" aria-selected="true" style="border-radius: 8px 8px 0 0;">
                    <i class="bi bi-journal-text me-2 text-primary"></i>Daily Egg Collection Records
                </button>
            </li>
            <li class="nav-item shadow-sm" role="presentation" style="margin-right: 4px;">
                <button class="nav-link fw-bold text-dark border-0 py-3 px-4" id="cages-tab" data-bs-toggle="tab" data-bs-target="#cages-pane" type="button" role="tab" aria-controls="cages-pane" aria-selected="false" style="border-radius: 8px 8px 0 0;">
                    <i class="bi bi-grid-3x3 me-2 text-success"></i>Active Cages
                </button>
            </li>
            <li class="nav-item shadow-sm" role="presentation">
                <button class="nav-link fw-bold text-dark border-0 py-3 px-4" id="batches-tab" data-bs-toggle="tab" data-bs-target="#batches-pane" type="button" role="tab" aria-controls="batches-pane" aria-selected="false" style="border-radius: 8px 8px 0 0;">
                    <i class="bi bi-tags-fill me-2 text-warning"></i>My Batches (Scoped to You)
                </button>
            </li>
        </ul>

        <div class="tab-content bg-white p-4 shadow-sm mb-5" style="border-radius: 0 12px 12px 12px; min-height: 400px;">
            <!-- Sub-Tab 1: Collection Records -->
            <div class="tab-pane fade show active" id="records-pane" role="tabpanel" aria-labelledby="records-tab" tabindex="0">
                <div class="table-responsive">
                    <table id="eggCollectionTable" class="table table-striped align-middle row-border" style="width:100%">
                        <thead class="table-light">
                            <tr>
                                <th>Date</th>
                                <th>Batch</th>
                                <th>Cage Name</th>
                                <th>Pullets</th>
                                <th>Cockerels</th>
                                <th>Hatch Eggs (NO / Kg)</th>
                                <th>Table Eggs (NO / Kg)</th>
                                <th>Cracked Eggs (NO / Kg)</th>
                                <th>Total Eggs (NO / Kg)</th>
                                <th>Hatchery Name</th>
                                <th>Hatchability %</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($collections as $c): ?>
                                <tr>
                                    <td class="fw-bold"><?= date('d-M-Y', strtotime($c['collection_date'])) ?></td>
                                    <td><span class="badge bg-primary-subtle text-primary border px-2"><?= htmlspecialchars($c['batch_name']) ?></span></td>
                                    <td class="fw-medium text-dark"><?= htmlspecialchars($c['cage_name']) ?></td>
                                    <td><?= number_format($c['pullets']) ?></td>
                                    <td><?= number_format($c['cockerels']) ?></td>
                                    <td>
                                        <?= number_format($c['hatchable_eggs']) ?> <span class="text-muted small">NO</span>
                                        <div class="small text-muted"><?= number_format($c['hatchable_eggs_kg'] ?? 0, 2) ?> Kg</div>
                                    </td>
                                    <td>
                                        <?= number_format($c['table_eggs']) ?> <span class="text-muted small">NO</span>
                                        <div class="small text-muted"><?= number_format($c['table_eggs_kg'] ?? 0, 2) ?> Kg</div>
                                    </td>
                                    <td>
                                        <?= number_format($c['cracked_eggs']) ?> <span class="text-muted small">NO</span>
                                        <div class="small text-muted"><?= number_format($c['cracked_eggs_kg'] ?? 0, 2) ?> Kg</div>
                                    </td>
                                    <td class="fw-bold text-success">
                                        <?= number_format($c['total_eggs']) ?> NO
                                        <div class="small fw-bold text-success"><?= number_format($c['total_eggs_kg'] ?? 0, 2) ?> Kg</div>
                                    </td>
                                    <td><?= htmlspecialchars($c['hatchery_name'] ?? '-') ?></td>
                                    <td><span class="badge bg-info-subtle text-info border px-2 fw-bold"><?= number_format($c['hatchability_percentage'] ?? 0, 2) ?>%</span></td>
                                    <td class="text-end">
                                        <div class="btn-group btn-group-sm">
                                            <button class="btn btn-outline-info view-collection-btn"
                                                data-id="<?= $c['id'] ?>"
                                                data-date="<?= htmlspecialchars($c['collection_date']) ?>"
                                                data-batch-name="<?= htmlspecialchars($c['batch_name']) ?>"
                                                data-cage-name="<?= htmlspecialchars($c['cage_name']) ?>"
                                                data-pullets="<?= $c['pullets'] ?>"
                                                data-cockerels="<?= $c['cockerels'] ?>"
                                                data-total="<?= $c['total_eggs'] ?>"
                                                data-total-kg="<?= $c['total_eggs_kg'] ?? 0 ?>"
                                                data-hatchable="<?= $c['hatchable_eggs'] ?>"
                                                data-hatchable-kg="<?= $c['hatchable_eggs_kg'] ?? 0 ?>"
                                                data-table-eggs="<?= $c['table_eggs'] ?>"
                                                data-table-kg="<?= $c['table_eggs_kg'] ?? 0 ?>"
                                                data-cracked="<?= $c['cracked_eggs'] ?>"
                                                data-cracked-kg="<?= $c['cracked_eggs_kg'] ?? 0 ?>"
                                                data-loading-date="<?= htmlspecialchars($c['loading_date'] ?? '') ?>"
                                                data-hatchery-name="<?= htmlspecialchars($c['hatchery_name'] ?? '') ?>"
                                                data-eggs-loaded="<?= $c['eggs_loaded'] ?? 0 ?>"
                                                data-hatching-date="<?= htmlspecialchars($c['hatching_date'] ?? '') ?>"
                                                data-hatched-eggs="<?= $c['hatched_eggs'] ?? 0 ?>"
                                                data-hatchability="<?= number_format($c['hatchability_percentage'] ?? 0, 2) ?>"
                                                data-bs-toggle="modal" data-bs-target="#viewEggModal"
                                                title="View Details">
                                                <i class="bi bi-eye-fill"></i>
                                            </button>
                                            <button class="btn btn-outline-secondary edit-collection-btn"
                                                data-id="<?= $c['id'] ?>"
                                                data-date="<?= $c['collection_date'] ?>"
                                                data-batch="<?= $c['batch_id'] ?>"
                                                data-cage="<?= $c['cage_id'] ?>"
                                                data-pullets="<?= $c['pullets'] ?>"
                                                data-cockerels="<?= $c['cockerels'] ?>"
                                                data-total="<?= $c['total_eggs'] ?>"
                                                data-total-kg="<?= $c['total_eggs_kg'] ?? 0 ?>"
                                                data-hatchable="<?= $c['hatchable_eggs'] ?>"
                                                data-hatchable-kg="<?= $c['hatchable_eggs_kg'] ?? 0 ?>"
                                                data-table-eggs="<?= $c['table_eggs'] ?>"
                                                data-table-kg="<?= $c['table_eggs_kg'] ?? 0 ?>"
                                                data-cracked="<?= $c['cracked_eggs'] ?>"
                                                data-cracked-kg="<?= $c['cracked_eggs_kg'] ?? 0 ?>"
                                                data-loading-date="<?= htmlspecialchars($c['loading_date'] ?? '') ?>"
                                                data-hatchery-name="<?= htmlspecialchars($c['hatchery_name'] ?? '') ?>"
                                                data-eggs-loaded="<?= $c['eggs_loaded'] ?? 0 ?>"
                                                data-hatching-date="<?= htmlspecialchars($c['hatching_date'] ?? '') ?>"
                                                data-hatched-eggs="<?= $c['hatched_eggs'] ?? 0 ?>"
                                                data-hatchability="<?= number_format($c['hatchability_percentage'] ?? 0, 2) ?>"
                                                data-bs-toggle="modal" data-bs-target="#editEggModal"
                                                title="Edit Record">
                                                <i class="bi bi-pencil-fill"></i>
                                            </button>
                                            <button class="btn btn-outline-danger delete-record-btn"
                                                data-href="processors/save_daily_egg_collection.php?action=delete&id=<?= $c['id'] ?>"
                                                title="Delete Record">
                                                <i class="bi bi-trash-fill"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Sub-Tab 2: Active Cages -->
            <div class="tab-pane fade" id="cages-pane" role="tabpanel" aria-labelledby="cages-tab" tabindex="0">
                <div class="table-responsive">
                    <table class="table table-striped align-middle row-border" style="width:100%">
                        <thead class="table-light">
                            <tr>
                                <th>Cage ID</th>
                                <th>Cage Name</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($cages)): ?>
                                <tr>
                                    <td colspan="3" class="text-center text-muted py-3">No active cages.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($cages as $cg): ?>
                                    <tr>
                                        <td class="fw-bold text-muted">#<?= $cg['id'] ?></td>
                                        <td class="fw-medium text-dark"><?= htmlspecialchars($cg['cage_name']) ?></td>
                                        <td class="text-end">
                                            <div class="btn-group btn-group-sm">
                                                <button class="btn btn-outline-secondary edit-cage-btn"
                                                    data-id="<?= $cg['id'] ?>"
                                                    data-name="<?= htmlspecialchars($cg['cage_name']) ?>"
                                                    data-bs-toggle="modal" data-bs-target="#editCageModal">
                                                    <i class="bi bi-pencil-fill"></i>
                                                </button>
                                                <button class="btn btn-outline-danger delete-cage-btn"
                                                    data-href="processors/save_cage.php?action=delete&id=<?= $cg['id'] ?>"
                                                    title="Delete Cage">
                                                    <i class="bi bi-trash-fill"></i>
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

            <!-- Sub-Tab 3: My Batches -->
            <div class="tab-pane fade" id="batches-pane" role="tabpanel" aria-labelledby="batches-tab" tabindex="0">
                <div class="table-responsive">
                    <table class="table table-striped align-middle row-border" style="width:100%">
                        <thead class="table-light">
                            <tr>
                                <th>Batch ID</th>
                                <th>Batch Number / Code</th>
                                <th>Created On</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($batches)): ?>
                                <tr>
                                    <td colspan="4" class="text-center text-muted py-3">You have not created any batches yet.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($batches as $bt): ?>
                                    <tr>
                                        <td class="fw-bold text-muted">#<?= $bt['id'] ?></td>
                                        <td class="fw-bold text-primary"><?= htmlspecialchars($bt['batch_name']) ?></td>
                                        <td class="text-muted"><?= date('d-M-Y H:i', strtotime($bt['created_at'])) ?></td>
                                        <td class="text-end">
                                            <div class="btn-group btn-group-sm">
                                                <button class="btn btn-outline-secondary edit-batch-btn"
                                                    data-id="<?= $bt['id'] ?>"
                                                    data-name="<?= htmlspecialchars($bt['batch_name']) ?>"
                                                    data-bs-toggle="modal" data-bs-target="#editBatchModal">
                                                    <i class="bi bi-pencil-fill"></i>
                                                </button>
                                                <button class="btn btn-outline-danger delete-batch-btn"
                                                    data-href="processors/save_batch.php?action=delete&id=<?= $bt['id'] ?>"
                                                    title="Delete Batch">
                                                    <i class="bi bi-trash-fill"></i>
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

    <!-- ================= TAB 2: ANNEX 01 - MONTHLY REGISTER ================= -->
    <div class="tab-pane fade" id="annex-01-pane" role="tabpanel" aria-labelledby="annex-01-tab" tabindex="0">

        <!-- Filter Bar Card -->
        <div class="card border-0 shadow-sm mb-4" style="border-radius: 12px;">
            <div class="card-body py-3">
                <div class="row g-3 align-items-center">
                    <div class="col-md-4 col-lg-3">
                        <label class="form-label fw-bold text-muted mb-1 small">Select Month & Year</label>
                        <input type="month" id="annex_month_filter" class="form-control fw-bold" value="<?= date('Y-m') ?>">
                    </div>
                    <div class="col-md-4 col-lg-3 d-flex align-items-end">
                        <button type="button" class="btn btn-primary fw-bold px-4 w-100" id="btnLoadAnnexReport" style="background-color: #370709 !important; border-color: #370709 !important;">
                            <i class="bi bi-search me-1"></i>Load Report
                        </button>
                    </div>
                    <div class="col-md-4 col-lg-3 d-flex align-items-end ms-auto">
                        <button type="button" class="btn btn-outline-success fw-bold w-100" data-bs-toggle="modal" data-bs-target="#salesReturnsModal">
                            <i class="bi bi-cart-plus me-1"></i>Log Sales & Returns
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Async Report Container -->
        <div id="annexReportContainer" class="bg-white p-4 shadow-sm mb-5" style="border-radius: 12px; min-height: 450px;">
            <div class="text-center py-5">
                <div class="spinner-border text-primary" role="status"></div>
                <p class="mt-2 text-muted fw-bold">Select a month and click "Load Report" to generate Annex 01 Egg Register.</p>
            </div>
        </div>

    </div>
</div>

</main>
</div>

<!-- Modal views -->
<?php
include './models/add_daily_egg_collection.php';
include './models/add_cage_modal.php';
include './models/add_batch_modal.php';
include './models/edit_daily_egg_collection_modal.php';
include './models/edit_cage_modal.php';
include './models/edit_batch_modal.php';
include './models/view_daily_egg_collection_modal.php';
include './models/export_columns_modal.php';
include './models/add_sales_returns_modal.php';
?>

<script>
    $(document).ready(function() {
        if (!$.fn.DataTable.isDataTable('#eggCollectionTable')) {
            $('#eggCollectionTable').DataTable({
                responsive: true,
                dom: "<'row mb-3 align-items-center'<'col-md-8'B><'col-md-4 text-end'f>>" +
                     "<'row'<'col-sm-12'tr>>" +
                     "<'row mt-3 align-items-center'<'col-md-4'l><'col-md-8 text-end'p>>",
                buttons: [
                    {
                        extend: 'csvHtml5',
                        text: '<i class="bi bi-file-earmark-csv me-1"></i>CSV',
                        className: 'btn btn-sm btn-success me-1 shadow-sm fw-bold',
                        exportOptions: { columns: ':not(:last-child)' }
                    },
                    {
                        extend: 'pdfHtml5',
                        text: '<i class="bi bi-file-earmark-pdf me-1"></i>PDF',
                        className: 'btn btn-sm btn-danger me-1 shadow-sm fw-bold',
                        orientation: 'landscape',
                        pageSize: 'A4',
                        exportOptions: { columns: ':not(:last-child)' }
                    },
                    {
                        extend: 'print',
                        text: '<i class="bi bi-printer me-1"></i>Print',
                        className: 'btn btn-sm btn-secondary me-1 shadow-sm fw-bold',
                        exportOptions: { columns: ':not(:last-child)' }
                    },
                    {
                        text: '<i class="bi bi-file-earmark-spreadsheet-fill me-1"></i> Manage Columns Export',
                        className: 'btn btn-sm btn-dark px-3 me-1 rounded fw-bold shadow-sm',
                        action: function(e, dt, node, config) {
                            $('#exportColumnsModal').modal('show');
                        }
                    }
                ],
                language: {
                    search: "_INPUT_",
                    searchPlaceholder: "Search collections..."
                }
            });
        }

        // Auto-Calculate Total Eggs (NO) in Add Modal
        $(document).on('input', '.egg-calc', function() {
            var hatch = parseInt($('#add_hatchable_eggs').val()) || 0;
            var table = parseInt($('#add_table_eggs').val()) || 0;
            var cracked = parseInt($('#add_cracked_eggs').val()) || 0;
            $('#add_total_eggs').val(hatch + table + cracked);
        });

        // Auto-Calculate Total Weight (Kg) in Add Modal
        $(document).on('input', '.egg-kg-calc', function() {
            var hatchKg = parseFloat($('#add_hatchable_eggs_kg').val()) || 0;
            var tableKg = parseFloat($('#add_table_eggs_kg').val()) || 0;
            var crackedKg = parseFloat($('#add_cracked_eggs_kg').val()) || 0;
            $('#add_total_eggs_kg').val((hatchKg + tableKg + crackedKg).toFixed(2));
        });

        // Auto-Calculate Hatchability % in Add Modal
        $(document).on('input', '.hatch-calc', function() {
            var loaded = parseInt($('#add_eggs_loaded').val()) || 0;
            var hatched = parseInt($('#add_hatched_eggs').val()) || 0;
            var pct = (loaded > 0) ? ((hatched / loaded) * 100).toFixed(2) : '0.00';
            $('#add_hatchability_percentage').val(pct);
        });

        // Auto-Calculate Total Eggs (NO) in Edit Modal
        $(document).on('input', '.edit-egg-calc', function() {
            var hatch = parseInt($('#edit_hatchable_eggs').val()) || 0;
            var table = parseInt($('#edit_table_eggs').val()) || 0;
            var cracked = parseInt($('#edit_cracked_eggs').val()) || 0;
            $('#edit_egg_count').val(hatch + table + cracked);
        });

        // Auto-Calculate Total Weight (Kg) in Edit Modal
        $(document).on('input', '.edit-egg-kg-calc', function() {
            var hatchKg = parseFloat($('#edit_hatchable_eggs_kg').val()) || 0;
            var tableKg = parseFloat($('#edit_table_eggs_kg').val()) || 0;
            var crackedKg = parseFloat($('#edit_cracked_eggs_kg').val()) || 0;
            $('#edit_total_eggs_kg').val((hatchKg + tableKg + crackedKg).toFixed(2));
        });

        // Auto-Calculate Hatchability % in Edit Modal
        $(document).on('input', '.edit-hatch-calc', function() {
            var loaded = parseInt($('#edit_eggs_loaded').val()) || 0;
            var hatched = parseInt($('#edit_hatched_eggs').val()) || 0;
            var pct = (loaded > 0) ? ((hatched / loaded) * 100).toFixed(2) : '0.00';
            $('#edit_hatchability_percentage').val(pct);
        });

        // Populate View Modal
        $('.view-collection-btn').on('click', function() {
            $('#view_collection_date').text($(this).data('date') || '-');
            $('#view_batch_name').text($(this).data('batch-name') || '-');
            $('#view_cage_name').text($(this).data('cage-name') || '-');
            $('#view_pullets').text(Number($(this).data('pullets') || 0).toLocaleString());
            $('#view_cockerels').text(Number($(this).data('cockerels') || 0).toLocaleString());

            $('#view_hatchable_eggs').text(Number($(this).data('hatchable') || 0).toLocaleString() + ' NO');
            $('#view_hatchable_eggs_kg').text(Number($(this).data('hatchable-kg') || 0).toFixed(2) + ' Kg');

            $('#view_table_eggs').text(Number($(this).data('table-eggs') || 0).toLocaleString() + ' NO');
            $('#view_table_eggs_kg').text(Number($(this).data('table-kg') || 0).toFixed(2) + ' Kg');

            $('#view_cracked_eggs').text(Number($(this).data('cracked') || 0).toLocaleString() + ' NO');
            $('#view_cracked_eggs_kg').text(Number($(this).data('cracked-kg') || 0).toFixed(2) + ' Kg');

            $('#view_total_eggs').text(Number($(this).data('total') || 0).toLocaleString() + ' NO');
            $('#view_total_eggs_kg').text(Number($(this).data('total-kg') || 0).toFixed(2) + ' Kg');

            $('#view_loading_date').text($(this).data('loading-date') || '-');
            $('#view_hatchery_name').text($(this).data('hatchery-name') || '-');
            $('#view_eggs_loaded').text(Number($(this).data('eggs-loaded') || 0).toLocaleString());
            $('#view_hatching_date').text($(this).data('hatching-date') || '-');
            $('#view_hatched_eggs').text(Number($(this).data('hatched-eggs') || 0).toLocaleString());
            $('#view_hatchability_percentage').text(($(this).data('hatchability') || '0.00') + '%');
        });

        // Populate Edit Modal
        $('.edit-collection-btn').on('click', function() {
            var id = $(this).data('id');
            var date = $(this).data('date');
            var batch = $(this).data('batch');
            var cage = $(this).data('cage');
            var pullets = $(this).data('pullets');
            var cockerels = $(this).data('cockerels');

            var total = $(this).data('total');
            var totalKg = $(this).data('total-kg');
            var hatchable = $(this).data('hatchable');
            var hatchableKg = $(this).data('hatchable-kg');
            var tableEggs = $(this).data('table-eggs');
            var tableKg = $(this).data('table-kg');
            var cracked = $(this).data('cracked');
            var crackedKg = $(this).data('cracked-kg');

            var loadingDate = $(this).data('loading-date');
            var hatcheryName = $(this).data('hatchery-name');
            var eggsLoaded = $(this).data('eggs-loaded');
            var hatchingDate = $(this).data('hatching-date');
            var hatchedEggs = $(this).data('hatched-eggs');
            var hatchability = $(this).data('hatchability');

            $('#edit_collection_id').val(id);
            $('#edit_collection_date').val(date);
            $('#edit_batch_id').val(batch);
            $('#edit_cage_id').val(cage);
            $('#edit_pullets').val(pullets);
            $('#edit_cockerels').val(cockerels);

            $('#edit_egg_count').val(total);
            $('#edit_total_eggs_kg').val(totalKg);
            $('#edit_hatchable_eggs').val(hatchable);
            $('#edit_hatchable_eggs_kg').val(hatchableKg);
            $('#edit_table_eggs').val(tableEggs);
            $('#edit_table_eggs_kg').val(tableKg);
            $('#edit_cracked_eggs').val(cracked);
            $('#edit_cracked_eggs_kg').val(crackedKg);

            $('#edit_loading_date').val(loadingDate);
            $('#edit_hatchery_name').val(hatcheryName);
            $('#edit_eggs_loaded').val(eggsLoaded);
            $('#edit_hatching_date').val(hatchingDate);
            $('#edit_hatched_eggs').val(hatchedEggs);
            $('#edit_hatchability_percentage').val(hatchability);
        });

        // Handle editing cage
        $('.edit-cage-btn').on('click', function() {
            var id = $(this).data('id');
            var name = $(this).data('name');
            $('#edit_cage_id').val(id);
            $('#edit_cage_name').val(name);
        });

        // Handle editing batch
        $('.edit-batch-btn').on('click', function() {
            var id = $(this).data('id');
            var name = $(this).data('name');
            $('#edit_batch_num_id').val(id);
            $('#edit_batch_number').val(name);
        });

        // SweetAlert Delete Collection Record
        $(document).on('click', '.delete-record-btn', function(e) {
            e.preventDefault();
            var href = $(this).data('href');
            Swal.fire({
                title: 'Delete Collection Record?',
                text: 'Are you sure you want to permanently delete this egg collection record? This action cannot be undone.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#dc3545',
                cancelButtonColor: '#6c757d',
                confirmButtonText: '<i class="bi bi-trash-fill me-1"></i> Yes, Delete',
                cancelButtonText: 'Cancel'
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = href;
                }
            });
        });

        // SweetAlert Delete Cage
        $(document).on('click', '.delete-cage-btn', function(e) {
            e.preventDefault();
            var href = $(this).data('href');
            Swal.fire({
                title: 'Delete Cage?',
                text: 'Are you sure you want to permanently delete this cage?',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#dc3545',
                cancelButtonColor: '#6c757d',
                confirmButtonText: '<i class="bi bi-trash-fill me-1"></i> Yes, Delete',
                cancelButtonText: 'Cancel'
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = href;
                }
            });
        });

        // SweetAlert Delete Batch
        $(document).on('click', '.delete-batch-btn', function(e) {
            e.preventDefault();
            var href = $(this).data('href');
            Swal.fire({
                title: 'Delete Batch?',
                text: 'Are you sure you want to delete this batch? All associated daily egg collections will be permanently removed.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#dc3545',
                cancelButtonColor: '#6c757d',
                confirmButtonText: '<i class="bi bi-trash-fill me-1"></i> Yes, Delete',
                cancelButtonText: 'Cancel'
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = href;
                }
            });
        });

        // Export Column Selection Helpers
        $('#btnSelectAllCols').on('click', function() {
            $('.export-col-chk').prop('checked', true);
        });
        $('#btnDeselectAllCols').on('click', function() {
            $('.export-col-chk').prop('checked', false);
        });

        // ================= ANNEX 01 ASYNC AJAX REPORT LOADER =================
        function loadAnnex01Report(monthVal) {
            if (!monthVal) {
                var now = new Date();
                monthVal = now.getFullYear() + '-' + String(now.getMonth() + 1).padStart(2, '0');
            }
            $('#annexReportContainer').html('<div class="text-center py-5"><div class="spinner-border text-primary" role="status"></div><p class="mt-2 text-muted fw-bold">Loading Annex 01 Monthly Register for ' + monthVal + '...</p></div>');

            $.get('processors/get_annex_01_data.php', {
                month: monthVal
            }, function(data) {
                $('#annexReportContainer').html(data);
            }).fail(function() {
                $('#annexReportContainer').html('<div class="alert alert-danger fw-bold"><i class="bi bi-exclamation-triangle-fill me-2"></i>Failed to load Annex 01 report. Please try again.</div>');
            });
        }

        // Trigger load when switching to Annex 01 Tab
        $('#annex-01-tab').on('shown.bs.tab', function() {
            var monthVal = $('#annex_month_filter').val();
            loadAnnex01Report(monthVal);
        });

        // Trigger load on filter button click
        $('#btnLoadAnnexReport').on('click', function() {
            var monthVal = $('#annex_month_filter').val();
            loadAnnex01Report(monthVal);
        });
    });
</script>

<?php require_once '../../../includes/footer.php'; ?>