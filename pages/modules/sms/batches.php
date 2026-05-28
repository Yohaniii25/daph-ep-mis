<?php
require_once '../../../includes/header.php';
if ($_SESSION['role'] !== 'sms') die("Access denied");
require_once '../../../config/db_connect.php';

?>

<?php require_once '../../../includes/sidebar.php'; ?>

<div id="layoutSidenav_content">
    <main class="container-fluid px-4 pt-4">
        <h2 class="mb-4">Batch details</h2>

        <!-- Quick Stats -->
        <div class="row g-4 mb-5">
            <div class="col-xl-3 col-md-6">
                <div class="card border-0 shadow-sm h-100 p-4 text-center">
                    <h6 class="text-muted">Total Batches</h6>
                    <h2 class="text-danger">7</h2>
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
                        <button class="btn btn-success w-100 py-3" data-bs-toggle="modal" data-bs-target="#reportCaseModal">
                            <i class="bi bi-journal-text"></i><br>
                            Add New Batch
                        </button>
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
                        $sql = "SELECT * FROM sms_immunization ORDER BY log_date DESC, id DESC";
                        $res = $mysqli->query($sql);
                        while ($row = $res->fetch_assoc()):
                        ?>
                            <tr>
                                <td class="fw-bold text-center">#<?= $row['id'] ?></td>
                                <td><?= $row['log_date'] ?></td>
                                <td class="fw-bold"><?= htmlspecialchars($row['vaccination_type']) ?></td>
                                <td class="text-center"><?= number_format($row['starter_count_month']) ?></td>
                                <td class="text-center"><?= number_format($row['during_month_received']) ?></td>
                                <td class="text-center text-primary bg-light"><?= htmlspecialchars($row['used_batch_number']) ?></td>
                                <td class="text-center fw-bold text-primary bg-light"><?= number_format($row['used_doses_count']) ?></td>
                                <td class="text-center text-danger"><?= number_format($row['doses_damaged']) ?></td>
                                <td class="text-center text-success bg-light"><?= htmlspecialchars($row['balance_batch_number']) ?></td>
                                <td class="text-center fw-bold text-success bg-light"><?= number_format($row['balance_doses_qty']) ?></td>
                                <td class="text-end">
                                    <div class="btn-group btn-group-sm">
                                        <button class="btn btn-outline-secondary edit-imm-btn"
                                            data-id="<?= $row['id'] ?>"
                                            data-date="<?= $row['log_date'] ?>"
                                            data-type="<?= htmlspecialchars($row['vaccination_type']) ?>"
                                            data-starter="<?= $row['starter_count_month'] ?>"
                                            data-received="<?= $row['during_month_received'] ?>"
                                            data-ubatch="<?= htmlspecialchars($row['used_batch_number']) ?>"
                                            data-uqty="<?= $row['used_doses_count'] ?>"
                                            data-damaged="<?= $row['doses_damaged'] ?>"
                                            data-bbatch="<?= htmlspecialchars($row['balance_batch_number']) ?>"
                                            data-bs-toggle="modal" data-bs-target="#immunizationModal">
                                            <i class="bi bi-pencil"></i>
                                        </button>
                                        <a href="processors/immunization_crud.php?action=delete&id=<?= $row['id'] ?>"
                                            class="btn btn-outline-danger"
                                            onclick="return confirm('Delete this record?');">
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
            ]
        });

        // Auto-calculate remaining balances
        $('.calc-trigger').on('input', function() {
            let starter = parseInt($('#qtyStarter').val()) || 0;
            let received = parseInt($('#qtyReceived').val()) || 0;
            let used = parseInt($('#qtyUsed').val()) || 0;
            let damaged = parseInt($('#qtyDamaged').val()) || 0;

            let balance = (starter + received) - (used + damaged);
            $('#balancePreview').val(balance.toLocaleString() + " Doses");
        });

        // Handle updates mapping
        $('.edit-imm-btn').on('click', function() {
            $('#immAction').val('update');
            $('#immId').val($(this).data('id'));
            $('#logDate').val($(this).data('date'));
            $('#vaccineType').val($(this).data('type'));
            $('#qtyStarter').val($(this).data('starter'));
            $('#qtyReceived').val($(this).data('received'));
            $('#batchUsed').val($(this).data('ubatch'));
            $('#qtyUsed').val($(this).data('uqty'));
            $('#qtyDamaged').val($(this).data('damaged'));
            $('#batchBalance').val($(this).data('bbatch'));

            $('.calc-trigger').first().trigger('input'); // Force refresh math calculations
            $('#immModalTitle').html('<i class="bi bi-pencil-square me-2"></i>Edit Immunization Row');
        });

        $('#immunizationModal').on('hidden.bs.modal', function() {
            $('#immAction').val('create');
            $('#immId').val('');
            $('#immForm')[0].reset();
            $('#balancePreview').val('0');
            $('#immModalTitle').html('<i class="bi bi-shield-plus me-2"></i>Log Immunization Records');
        });
    });
</script>
SCRIPT;
require_once '../../../includes/footer.php';
?>