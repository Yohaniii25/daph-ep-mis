<?php
// pages/modules/farm/parent_stock_operations.php -> Daily Egg Collection Module
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

require_once '../../../includes/sidebar.php';
?>

<!-- SweetAlert2 & DataTables CSS & Icons -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.2/css/buttons.bootstrap5.min.css">

<div id="layoutSidenav_content" class="bg-light">
    <main class="container-fluid px-4 pt-4">

        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="fw-bold text-dark m-0">Daily Egg Collection Module</h2>
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

        <!-- Rebranded Action Buttons Card -->
        <div class="card border-0 shadow-sm mb-4" style="border-radius: 12px;">
            <div class="card-header bg-white py-3 border-0">
                <h5 class="fw-bold text-dark m-0">Quick Options</h5>
            </div>
            <div class="card-body pt-0 pb-4">
                <div class="row g-3">
                    <div class="col-md-3">
                        <button style="background-color: #370709; border-color: #370709;" class="btn btn-primary w-100 py-3" data-bs-toggle="modal" data-bs-target="#eggModal">
                            <i class="bi bi-egg-fill fs-5 mb-1 d-block"></i>
                            Add Daily Egg Collection
                        </button>
                    </div>
                    <div class="col-md-3">
                        <button class="btn btn-success w-100 py-3" data-bs-toggle="modal" data-bs-target="#addCageModal">
                            <i class="bi bi-grid-3x3 fs-5 mb-1 d-block"></i>
                            Add Cage
                        </button>
                    </div>
                    <div class="col-md-3">
                        <button class="btn btn-warning w-100 py-3 fw-bold text-dark" data-bs-toggle="modal" data-bs-target="#addBatchModal">
                            <i class="bi bi-tags-fill fs-5 mb-1 d-block"></i>
                            Add Batch
                        </button>
                    </div>
                    <div class="col-md-3">
                        <button class="btn btn-dark w-100 py-3 fw-bold text-light" data-bs-toggle="modal" data-bs-target="#exportColumnsModal">
                            <i class="bi bi-file-earmark-spreadsheet-fill fs-5 mb-1 d-block text-success"></i>
                            Export Custom Report
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tabs Components -->
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
            <!-- Tab 1: Collection Records -->
            <div class="tab-pane fade show active" id="records-pane" role="tabpanel" aria-labelledby="records-tab" tabindex="0">
                <table id="eggCollectionTable" class="table table-striped align-middle row-border" style="width:100%">
                    <thead class="table-light">
                        <tr>
                            <th>Date</th>
                            <th>Batch</th>
                            <th>Cage Name</th>
                            <th>Pullets</th>
                            <th>Cockerels</th>
                            <th>Hatch Eggs</th>
                            <th>Table Eggs</th>
                            <th>Cracked Eggs</th>
                            <th>Total Eggs</th>
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
                                <td><?= number_format($c['hatchable_eggs']) ?></td>
                                <td><?= number_format($c['table_eggs']) ?></td>
                                <td><?= number_format($c['cracked_eggs']) ?></td>
                                <td class="fw-bold text-success"><?= number_format($c['total_eggs']) ?></td>
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
                                            data-hatchable="<?= $c['hatchable_eggs'] ?>"
                                            data-table-eggs="<?= $c['table_eggs'] ?>"
                                            data-cracked="<?= $c['cracked_eggs'] ?>"
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
                                            data-hatchable="<?= $c['hatchable_eggs'] ?>"
                                            data-table-eggs="<?= $c['table_eggs'] ?>"
                                            data-cracked="<?= $c['cracked_eggs'] ?>"
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

            <!-- Tab 2: Active Cages -->
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

            <!-- Tab 3: My Batches -->
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
?>

<!-- Scripts -->
<script src="https://code.jquery.com/jquery-3.7.0.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
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
        $('#eggCollectionTable').DataTable({
            dom: '<"d-flex justify-content-between align-items-center mb-3"Bf>rt<"d-flex justify-content-between align-items-center mt-3"ip>',
            buttons: [
                {
                    text: '<i class="bi bi-file-earmark-spreadsheet-fill me-1"></i> Manage Columns Export',
                    className: 'btn btn-sm btn-success px-3 me-1 rounded fw-bold',
                    action: function ( e, dt, node, config ) {
                        $('#exportColumnsModal').modal('show');
                    }
                },
                {
                    extend: 'pdf',
                    className: 'btn btn-sm btn-warning px-3 me-1 rounded text-dark'
                },
                {
                    extend: 'print',
                    className: 'btn btn-sm btn-danger px-3 rounded'
                }
            ],
            language: {
                search: "_INPUT_",
                searchPlaceholder: "Search collections..."
            }
        });

        // Auto-Calculate Total Eggs in Add Modal
        $(document).on('input', '.egg-calc', function() {
            var hatch = parseInt($('#add_hatchable_eggs').val()) || 0;
            var table = parseInt($('#add_table_eggs').val()) || 0;
            var cracked = parseInt($('#add_cracked_eggs').val()) || 0;
            $('#add_total_eggs').val(hatch + table + cracked);
        });

        // Auto-Calculate Hatchability % in Add Modal
        $(document).on('input', '.hatch-calc', function() {
            var loaded = parseInt($('#add_eggs_loaded').val()) || 0;
            var hatched = parseInt($('#add_hatched_eggs').val()) || 0;
            var pct = (loaded > 0) ? ((hatched / loaded) * 100).toFixed(2) : '0.00';
            $('#add_hatchability_percentage').val(pct);
        });

        // Auto-Calculate Total Eggs in Edit Modal
        $(document).on('input', '.edit-egg-calc', function() {
            var hatch = parseInt($('#edit_hatchable_eggs').val()) || 0;
            var table = parseInt($('#edit_table_eggs').val()) || 0;
            var cracked = parseInt($('#edit_cracked_eggs').val()) || 0;
            $('#edit_egg_count').val(hatch + table + cracked);
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
            $('#view_hatchable_eggs').text(Number($(this).data('hatchable') || 0).toLocaleString());
            $('#view_table_eggs').text(Number($(this).data('table-eggs') || 0).toLocaleString());
            $('#view_cracked_eggs').text(Number($(this).data('cracked') || 0).toLocaleString());
            $('#view_total_eggs').text(Number($(this).data('total') || 0).toLocaleString());
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
            var hatchable = $(this).data('hatchable');
            var tableEggs = $(this).data('table-eggs');
            var cracked = $(this).data('cracked');
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
            $('#edit_hatchable_eggs').val(hatchable);
            $('#edit_table_eggs').val(tableEggs);
            $('#edit_cracked_eggs').val(cracked);
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
    });
</script>