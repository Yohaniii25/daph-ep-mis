<?php
require_once '../../../includes/header.php';
if ($_SESSION['role'] !== 'sms') die("Access denied");
require_once '../../../config/db_connect.php';

// Dynamic metrics extraction using the centralized stock ledger
$metric_sql = "SELECT 
    COUNT(DISTINCT b.id) as active_batches_count,
    SUM(CASE WHEN l.transaction_type = 'INITIAL' THEN l.quantity ELSE 0 END) as global_starter_doses,
    ABS(SUM(CASE WHEN l.transaction_type = 'DISPENSE' THEN l.quantity ELSE 0 END)) as global_doses_used,
    SUM(l.quantity) as real_time_balance
FROM vaccine_batches b
LEFT JOIN vaccine_stock_ledger l ON b.id = l.vaccine_batch_id
WHERE b.is_active = 1";

$metric_res = $mysqli->query($metric_sql);
$metrics = $metric_res ? $metric_res->fetch_assoc() : [];

$total_batches  = $metrics['active_batches_count'] ?? 0;
$starter_doses  = $metrics['global_starter_doses'] ?? 0;
$doses_used     = $metrics['global_doses_used'] ?? 0;
$balance_doses  = $metrics['real_time_balance'] ?? 0;

?>

<?php require_once '../../../includes/sidebar.php'; ?>

<div id="layoutSidenav_content">
    <main class="container-fluid px-4 pt-4">
        <h2 class="mb-4">Immunization</h2>

        <!-- Quick Stats -->
        <div class="row g-4 mb-4">
            <div class="col-xl-3 col-md-6">
                <div class="card border-0 shadow-sm h-100 border-start border-primary border-4">
                    <div class="card-body p-4">
                        <h6 class="text-muted small text-uppercase fw-bold">Active Tracked Batches</h6>
                        <h2 class="text-primary mb-0 fw-bold"><?= number_format($total_batches) ?></h2>

                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="card border-0 shadow-sm h-100 border-start border-warning border-4">
                    <div class="card-body p-4">
                        <h6 class="text-muted small text-uppercase fw-bold">Doses Allocated (Starter)</h6>
                        <h2 class="text-warning mb-0 fw-bold"><?= number_format($starter_doses) ?></h2>

                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="card border-0 shadow-sm h-100 border-start border-success border-4">
                    <div class="card-body p-4">
                        <h6 class="text-muted small text-uppercase fw-bold">Total Doses Used</h6>
                        <h2 class="text-success mb-0 fw-bold"><?= number_format($doses_used) ?></h2>

                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="card border-0 shadow-sm h-100 border-start border-info border-4">
                    <div class="card-body p-4">
                        <h6 class="text-muted small text-uppercase fw-bold">Total Live Balance Available</h6>
                        <h2 class="text-info mb-0 fw-bold"><?= number_format($balance_doses) ?></h2>

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

                    <div class="col-md-3">
                        <a href="vaccine_types.php" class="btn btn-primary w-100 py-3">
                            <i class="bi bi-search"></i><br>
                            Vaccine Types
                        </a>
                    </div>
                    <div class="col-md-3">
                        <a href="batches.php" class="btn btn-warning w-100 py-3">
                            <i class="bi bi-people"></i><br>
                            Batches
                        </a>
                    </div>

                </div>
            </div>
        </div>

        <!-- Epidemiology Table -->
        <div class="card border-0 shadow-sm rounded-3">
            <div class="card-header bg-white py-3 border-0 d-flex justify-content-between align-items-center">
                <h5 class="m-0 fw-bold text-dark"><i class="bi bi-shield-plus me-2 text-success"></i>Monthly Immunization & Vaccine Balance Ledger</h5>
            </div>
            <div class="card-body">
                <table id="immunizationTable" class="table table-bordered table-striped align-middle" style="width:100%">
                    <thead class="table-light text-center align-middle">
                        <tr>
                            <th rowspan="2">ID</th>
                            <th rowspan="2">Date</th>
                            <th rowspan="2">Vaccination Type</th>
                            <th rowspan="2">Starter Count (Month)</th>
                            <th rowspan="2">Received (During Month)</th>
                            <th colspan="2" class="bg-primary-subtle text-primary">Doses Used</th>
                            <th rowspan="2" class="text-danger">Doses Damaged</th>
                            <th colspan="2" class="bg-success-subtle text-success">Balance Doses</th>
                            <th rowspan="2" class="text-end">Actions</th>
                        </tr>
                        <tr>
                            <th class="bg-primary-subtle text-primary-emphasis">Batch Number</th>
                            <th class="bg-primary-subtle text-primary-emphasis">No. of Doses</th>
                            <th class="bg-success-subtle text-success-emphasis">Batch Number</th>
                            <th class="bg-success-subtle text-success-emphasis">No. of Doses</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $sql = "SELECT 
            b.id,
            b.created_at as log_date,
            b.batch_number,
            t.vaccine_name,
            -- Baseline allocation opening inventory metric
            SUM(CASE WHEN l.transaction_type = 'INITIAL' THEN l.quantity ELSE 0 END) as starter_count_month,
            -- Mid-month incremental entries pool 
            SUM(CASE WHEN l.transaction_type = 'MID_MONTH_RECEIVE' THEN l.quantity ELSE 0 END) as during_month_received,
            -- Active distributed application totals
            ABS(SUM(CASE WHEN l.transaction_type = 'DISPENSE' THEN l.quantity ELSE 0 END)) as used_doses_count,
            -- Cold chain breakages and waste tracking metric
            ABS(SUM(CASE WHEN l.transaction_type = 'DAMAGE' THEN l.quantity ELSE 0 END)) as doses_damaged,
            -- Moving total math formula profile
            SUM(l.quantity) as balance_doses_qty
        FROM vaccine_batches b
        JOIN vaccine_types t ON b.vaccine_type_id = t.id
        LEFT JOIN `vaccine_stock_ledger` l ON b.id = l.vaccine_batch_id
        GROUP BY b.id
        ORDER BY b.id DESC";

                        $res = $mysqli->query($sql);
                        while ($row = $res->fetch_assoc()):
                        ?>
                            <tr>
                                <td class="fw-bold text-center">#<?= $row['id'] ?></td>
                                <td><?= date('Y-m-d', strtotime($row['log_date'])) ?></td>
                                <td class="fw-bold text-dark"><?= htmlspecialchars($row['vaccine_name']) ?></td>
                                <td class="text-center text-secondary"><?= number_format($row['starter_count_month']) ?></td>
                                <td class="text-center text-info"><?= number_format($row['during_month_received']) ?></td>

                                <!-- Doses Used Data Blocks Section -->
                                <td class="text-center text-primary bg-light small fw-semibold"><?= htmlspecialchars($row['batch_number']) ?></td>
                                <td class="text-center fw-bold text-primary bg-light"><?= number_format($row['used_doses_count']) ?></td>

                                <!-- Damaged Metrics Section -->
                                <td class="text-center fw-bold text-danger"><?= number_format($row['doses_damaged']) ?></td>

                                <!-- Running Stock Balance Blocks Section -->
                                <td class="text-center text-success bg-light small fw-semibold"><?= htmlspecialchars($row['batch_number']) ?></td>
                                <td class="text-center fw-bold text-success bg-light"><?= number_format($row['balance_doses_qty']) ?></td>

                                <td class="text-end">
                                    <div class="btn-group btn-group-sm">
                                        <!-- Bound contextual parameters down to your Modal framework triggers -->
                                        <button class="btn btn-outline-secondary edit-imm-btn"
                                            data-id="<?= $row['id'] ?>"
                                            data-date="<?= date('Y-m-d', strtotime($row['log_date'])) ?>"
                                            data-type="<?= htmlspecialchars($row['vaccine_name']) ?>"
                                            data-starter="<?= $row['starter_count_month'] ?>"
                                            data-received="<?= $row['during_month_received'] ?>"
                                            data-batch="<?= htmlspecialchars($row['batch_number']) ?>"
                                            data-used="<?= $row['used_doses_count'] ?>"
                                            data-damaged="<?= $row['doses_damaged'] ?>"
                                            data-bs-toggle="modal" data-bs-target="#addVaccineBatchModal">
                                            <i class="bi bi-pencil"></i>
                                        </button>
                                        <a href="processors/vaccine_batch_crud.php?action=delete&id=<?= $row['id'] ?>"
                                            class="btn btn-outline-danger"
                                            onclick="return confirm('Are you sure you want to completely remove this entire batch tracking entry sequence from your warehouse indices?');">
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
$pageScripts = <<<SCRIPT
<script>
    $(document).ready(function() {
        $('#immunizationTable').DataTable({
            "order": [[0, "desc"]],
            dom: '<"d-flex justify-content-between align-items-center mb-3"Bf>rt<"d-flex justify-content-between align-items-center mt-3"ip>',
            buttons: [
                { extend: 'csv', className: 'btn btn-sm btn-success px-3 me-1 rounded' },
                { extend: 'pdf', className: 'btn btn-sm btn-warning px-3 me-1 rounded text-dark' },
                { extend: 'print', className: 'btn btn-sm btn-danger px-3 rounded' }
            ]
        });

        // Intercept Edit Button clicks and route straight into your adjustment modal lifecycle
        $('.edit-imm-btn').on('click', function() {
            // Locate adjustment components safely inside the window context
            $('#modalAction').val('update');
            $('#batchId').val($(this).data('id'));
            $('#batchNumber').val($(this).data('batch'));
            $('#expiryDate').val($(this).data('date'));
            
            // Hide registration layouts, swap control components visibility variables
            $('#vaccineTypeContainer').addClass('d-none');
            $('#initialAllocationWrapper').addClass('d-none');
            $('#initialAllocatedDoses').prop('required', false);
            $('#ledgerAdjustmentsWrapper').removeClass('d-none');
            
            // Clear past input variables to let users set fresh adjustment differences
            $('#midMonthArrival').val(0);
            $('#newDamaged').val(0);

            $('#modalTitle').html('<i class="bi bi-pencil-square me-2 text-warning"></i>Inject Live Batch Adjustments');
            $('#submitBtn').removeClass('btn-success').addClass('btn-warning').text('Inject Ledger Updates');
        });
    });
</script>
SCRIPT;
require_once '../../../includes/footer.php';
?>