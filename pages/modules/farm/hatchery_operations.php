<?php
require_once '../../../includes/header.php';

require_once '../../../config/db_connect.php';

if ($_SESSION['role'] !== 'farms_dd') {
    die("Access denied");
}

$user_id = $_SESSION['user_id'] ?? 1; // Fallback to 1 if session key differs

// 1. Fetch KPI Summary Metrics (Overall Totals)
$where = "";
if ($_SESSION['role'] === 'farms_dd' && !empty($_SESSION['farm_id'])) {
    $where = " WHERE farm_id = " . (int)$_SESSION['farm_id'];
}

$kpi_query = "SELECT 
    IFNULL(SUM(total_collected), 0) AS total_eggs,
    IFNULL(SUM(hatchable_count), 0) AS total_hatchable,
    IFNULL(SUM(chicks_hatched), 0) AS total_chicks,
    IFNULL(SUM(table_count) + SUM(cracked_count), 0) AS total_commercial_waste,
    IFNULL(SUM(CASE WHEN chicks_hatched IS NOT NULL THEN hatchable_count ELSE 0 END), 0) AS completed_hatchable
FROM hatchery_batches" . $where;

$kpi_res = $mysqli->query($kpi_query)->fetch_assoc();

$today_eggs = $kpi_res['total_eggs'];
$today_hatchable = $kpi_res['total_hatchable'];
$total_commercial_waste = $kpi_res['total_commercial_waste'];
$hatch_rate = ($kpi_res['completed_hatchable'] > 0)
    ? round(($kpi_res['total_chicks'] / $kpi_res['completed_hatchable']) * 100, 1)
    : 0.0;


require_once '../../../includes/sidebar.php';

?>

<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.2/css/buttons.bootstrap5.min.css">

<div id="layoutSidenav_content">
    <main class="container-fluid px-4 pt-4">
        <h2 class="mb-4">Hatchery & Egg Grading Operations</h2>

        <?php if (isset($_GET['status'])): ?>
            <div class="alert alert-<?= ($_GET['status'] === 'success') ? 'success' : 'danger' ?> alert-dismissible fade show shadow-sm" role="alert">
                <strong><?= ($_GET['status'] === 'success') ? 'Success!' : 'Error!' ?></strong> <?= htmlspecialchars($_GET['msg'] ?? '') ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- Quick Stats -->
        <div class="row g-4 mb-4">
            <div class="col-xl-3 col-md-6">
                <div class="card border-0 shadow-sm h-100 border-start border-primary border-4">
                    <div class="card-body p-4">
                        <h6 class="text-muted small text-uppercase fw-bold">Total Eggs Collected</h6>
                        <h2 class="text-primary mb-0 fw-bold"><?= number_format($today_eggs) ?></h2>
                        <small class="text-muted">Total Intake Overall</small>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="card border-0 shadow-sm h-100 border-start border-warning border-4">
                    <div class="card-body p-4">
                        <h6 class="text-muted small text-uppercase fw-bold">Hatchable Inventory</h6>
                        <h2 class="text-warning mb-0 fw-bold"><?= number_format($today_hatchable) ?></h2>
                        <small class="text-muted">Set for Incubation Overall</small>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="card border-0 shadow-sm h-100 border-start border-success border-4">
                    <div class="card-body p-4">
                        <h6 class="text-muted small text-uppercase fw-bold">Avg Hatching Rate</h6>
                        <h2 class="text-success mb-0 fw-bold"><?= $hatch_rate ?><span class="fs-6 fw-normal text-muted"> %</span></h2>
                        <small class="text-muted">Hatch Success Benchmark</small>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="card border-0 shadow-sm h-100 border-start border-info border-4">
                    <div class="card-body p-4">
                        <h6 class="text-muted small text-uppercase fw-bold">Commercial / Waste</h6>
                        <h2 class="text-info mb-0 fw-bold"><?= number_format($total_commercial_waste) ?></h2>
                        <small class="text-muted">Table & Cracked Disposals</small>
                    </div>
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-light">
                <h5>Quick Actions</h5>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-4">
                        <button style="background-color: #370709; border-color: #370709;" class="btn btn-primary w-100 py-3" data-bs-toggle="modal" data-bs-target="#gradingModal">
                            <i class="bi bi-file-earmark-plus"></i><br>
                            Log Grading & Collection
                        </button>
                    </div>


                </div>
            </div>
        </div>

        <!-- Registered Farms Table -->
        <div class="card border-0 shadow-sm rounded-3 mb-4">
            <div class="card-header bg-white py-3 border-0 d-flex justify-content-between align-items-center">
                <h5 class="m-0 fw-bold text-dark"><i class="bi bi-journal-text me-2"></i>Hatchery Batches</h5>
            </div>
            <div class="card-body">
                <table id="hatcheryTable" class="table table-striped align-middle row-border" style="width:100%">
                    <thead class="table-light">
                        <tr>
                            <th>Batch ID</th>
                            <th>Date</th>
                            <th>Hatchable</th>
                            <th>Cracked</th>
                            <th>Table</th>
                            <th>Total Count</th>
                            <th>Chicks Born</th>
                            <th>Success %</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $ledger_sql = "SELECT * FROM hatchery_batches" . $where . " ORDER BY batch_date DESC, id DESC";
                        $res = $mysqli->query($ledger_sql);
                        while ($row = $res->fetch_assoc()):
                            $rate = 0;
                            if (!is_null($row['chicks_hatched']) && $row['hatchable_count'] > 0) {
                                $rate = round(($row['chicks_hatched'] / $row['hatchable_count']) * 100, 1);
                            }
                        ?>
                            <tr>
                                <td class="fw-bold">#<?= $row['id'] ?></td>
                                <td><?= $row['batch_date'] ?></td>
                                <td class="text-success fw-bold"><?= number_format($row['hatchable_count']) ?></td>
                                <td class="text-danger"><?= number_format($row['cracked_count']) ?></td>
                                <td class="text-primary"><?= number_format($row['table_count']) ?></td>
                                <td class="fw-bold"><?= number_format($row['total_collected']) ?></td>
                                <td class="fw-bold text-dark"><?= is_null($row['chicks_hatched']) ? '<span class="badge bg-warning text-dark">Incubating</span>' : number_format($row['chicks_hatched']) ?></td>
                                <td>
                                    <?php if (is_null($row['chicks_hatched'])): ?>
                                        <span class="text-muted">-</span>
                                    <?php else: ?>
                                        <span class="badge <?= ($rate >= 85) ? 'bg-success' : 'bg-secondary' ?>"><?= $rate ?>%</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end">
                                    <div class="btn-group btn-group-sm">
                                        <button class="btn btn-outline-secondary edit-btn"
                                            data-id="<?= $row['id'] ?>"
                                            data-date="<?= $row['batch_date'] ?>"
                                            data-hatchable="<?= $row['hatchable_count'] ?>"
                                            data-cracked="<?= $row['cracked_count'] ?>"
                                            data-table="<?= $row['table_count'] ?>"
                                            data-chicks="<?= $row['chicks_hatched'] ?? '' ?>"
                                            data-bs-toggle="modal" data-bs-target="#gradingModal">
                                            <i class="bi bi-pencil"></i>
                                        </button>
                                        <a href="processors/hatchery_crud.php?action=delete&id=<?= $row['id'] ?>"
                                            class="btn btn-outline-danger"
                                            onclick="return confirm('Are you sure you want to permanently delete this batch row?');">
                                            <i class="bi bi-trash"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>
</div>

<?php
// Include the consolidated model modal view template 
include './models/grading_modal.php';
require_once '../../../includes/footer.php';
?>

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
        $('#hatcheryTable').DataTable({
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
                searchPlaceholder: "Search batch records..."
            }
        });

        // Populate data inside modal for editing
        $('.edit-btn').on('click', function() {
            $('#modalAction').val('update');
            $('#batchId').val($(this).data('id'));
            $('#batchDate').val($(this).data('date'));
            $('#qtyHatchable').val($(this).data('hatchable'));
            $('#qtyCracked').val($(this).data('cracked'));
            $('#qtyTable').val($(this).data('table'));
            $('#qtyChicks').val($(this).data('chicks'));
            $('#modalTitle').html('<i class="bi bi-pencil-square me-2"></i>Edit Hatchery Entry');
        });

        // Reset fields on modal close to keep "Add" layout fresh
        $('#gradingModal').on('hidden.bs.modal', function() {
            $('#modalAction').val('create');
            $('#batchId').val('');
            $('#gradingForm')[0].reset();
            $('#modalTitle').html('<i class="bi bi-egg-fried me-2"></i>Log Grading & Collection');
        });
    });
</script>