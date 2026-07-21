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

<!-- DataTables CSS & Icons -->
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.2/css/buttons.bootstrap5.min.css">

<div id="layoutSidenav_content" class="bg-light">
    <main class="container-fluid px-4 pt-4">

        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="fw-bold text-dark m-0">Daily Egg Collection Module</h2>
            <span class="badge bg-secondary p-2 fs-6">Logged in: <b><?= htmlspecialchars($_SESSION['username']) ?></b></span>
        </div>

        <!-- Notification Status Alerts -->
        <?php if (isset($_GET['status'])): ?>
            <div class="alert alert-<?= ($_GET['status'] === 'success') ? 'success' : 'danger' ?> alert-dismissible fade show shadow-sm" role="alert">
                <strong><?= ($_GET['status'] === 'success') ? 'Success!' : 'Error!' ?></strong>
                <?= htmlspecialchars($_GET['msg'] ?? '') ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- Rebranded Action Buttons Card -->
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
                        <button class="btn btn-success w-100 py-3" data-bs-toggle="modal" data-bs-target="#addCageModal">
                            <i class="bi bi-grid-3x3 fs-5 mb-1 d-block"></i>
                            Add Cage
                        </button>
                    </div>
                    <div class="col-md-4">
                        <button class="btn btn-warning w-100 py-3 fw-bold text-dark" data-bs-toggle="modal" data-bs-target="#addBatchModal">
                            <i class="bi bi-tags-fill fs-5 mb-1 d-block"></i>
                            Add Batch
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
                            <th>Total Eggs</th>
                            <th>Hatchable</th>
                            <th>Table</th>
                            <th>Cracked</th>
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
                                <td class="fw-bold text-success"><?= number_format($c['total_eggs']) ?></td>
                                <td><?= number_format($c['hatchable_eggs']) ?></td>
                                <td><?= number_format($c['table_eggs']) ?></td>
                                <td><?= number_format($c['cracked_eggs']) ?></td>
                                <td class="text-end">
                                    <div class="btn-group btn-group-sm">
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
                                            data-bs-toggle="modal" data-bs-target="#editEggModal">
                                            <i class="bi bi-pencil"></i>
                                        </button>
                                        <a href="processors/save_daily_egg_collection.php?action=delete&id=<?= $c['id'] ?>"
                                            class="btn btn-outline-danger"
                                            onclick="return confirm('Are you sure you want to permanently delete this egg collection record?');">
                                            <i class="bi bi-trash"></i>
                                        </a>
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
                                                    <i class="bi bi-pencil"></i>
                                                </button>
                                                <a href="processors/save_cage.php?action=delete&id=<?= $cg['id'] ?>"
                                                    class="btn btn-outline-danger"
                                                    onclick="return confirm('Are you sure you want to permanently delete this cage?');">
                                                    <i class="bi bi-trash"></i>
                                                </a>
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
                                                    <i class="bi bi-pencil"></i>
                                                </button>
                                                <a href="processors/save_batch.php?action=delete&id=<?= $bt['id'] ?>"
                                                    class="btn btn-outline-danger"
                                                    onclick="return confirm('Are you sure you want to delete this batch? All associated daily collections will be removed.');">
                                                    <i class="bi bi-trash"></i>
                                                </a>
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
?>

<!-- Scripts -->
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

<script>
    $(document).ready(function() {
        $('#eggCollectionTable').DataTable({
            dom: '<"d-flex justify-content-between align-items-center mb-3"Bf>rt<"d-flex justify-content-between align-items-center mt-3"ip>',
            buttons: [{
                    extend: 'csv',
                    className: 'btn btn-sm btn-success px-3 me-1 rounded'
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

        // Handle editing population in the modal form
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
    });
</script>