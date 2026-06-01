<div class="modal fade" id="addDrugRecordModal" tabindex="-1" aria-labelledby="drugRecordModalTitle" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-light text-white py-3">
                <h5 class="modal-title" id="drugRecordModalTitle">
                    <i class="bi bi-capsule-compartment me-2"></i>Drug Stock Ledger Entry
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <form id="drugRecordForm" action="processors/drug_record_crud.php" method="POST">
                <input type="hidden" id="drugAction" name="action" value="create">
                <input type="hidden" id="drugId" name="id" value="">

                <div class="modal-body p-4">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label for="logDate" class="form-label fw-semibold text-secondary">Reporting / Log Date</label>
                            <input type="date" class="form-control" id="logDate" name="log_date" value="<?= date('Y-m-d') ?>" required>
                        </div>

                        <div class="col-md-4">
                            <label for="drugType" class="form-label fw-semibold text-secondary">Drug Type</label>
                            <select class="form-select text-dark fw-bold" id="drugType" name="drug_type_id" required>
                                <option value="" selected disabled>-- Choose Type --</option>
                                <?php
                                // Fetch all registered drug type records for selection
                                $type_opts = $mysqli->query("SELECT id, vaccine_name FROM drug_types ORDER BY vaccine_name ASC");
                                while($t_opt = $type_opts->fetch_assoc()):
                                ?>
                                    <option value="<?= (int) $t_opt['id'] ?>">
                                        <?= htmlspecialchars($t_opt['vaccine_name'], ENT_QUOTES) ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>

                        <div class="col-md-4">
                            <label for="vaccineBatchId" class="form-label fw-semibold text-secondary">Target Vaccine Batch</label>
                            <select class="form-select text-dark fw-bold" id="vaccineBatchId" name="vaccine_batch_id" required>
                                <option value="" selected disabled>-- Choose Batch --</option>
                                <?php
                                $batch_opts = $mysqli->query("SELECT id, batch_number FROM vaccine_batches WHERE is_active = 1 ORDER BY id DESC");
                                while($opt = $batch_opts->fetch_assoc()):
                                ?>
                                    <option value="<?= $opt['id'] ?>">
                                        <?= htmlspecialchars($opt['batch_number']) ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>

                        <div class="col-12"><hr class="my-2 text-muted"></div>

                        <div class="col-md-6">
                            <label for="qtyStarter" class="form-label fw-semibold text-dark">Opening Balance Doses</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light text-muted"><i class="bi bi-box-arrow-in-right"></i></span>
                                <input type="number" min="0" class="form-control calc-trigger fw-bold text-primary" id="qtyStarter" name="starter_count_month" value="0" required>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <label for="qtyReceived" class="form-label fw-semibold text-dark">Mid-Month Additions</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light text-muted"><i class="bi bi-plus-circle"></i></span>
                                <input type="number" min="0" class="form-control calc-trigger fw-bold text-success" id="qtyReceived" name="during_month_received" value="0" required>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <label for="qtyUsed" class="form-label fw-semibold text-dark">Quantity Used During the Month</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light text-muted"><i class="bi bi-shield-check"></i></span>
                                <input type="number" min="0" class="form-control calc-trigger fw-bold text-info" id="qtyUsed" name="used_doses_count" value="0" required>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <label for="qtyDamaged" class="form-label fw-semibold text-dark">Doses Wasted / Damaged</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light text-muted"><i class="bi bi-exclamation-triangle-fill text-danger"></i></span>
                                <input type="number" min="0" class="form-control calc-trigger fw-bold text-danger" id="qtyDamaged" name="doses_damaged" value="0" required>
                            </div>
                        </div>

                    </div>
                </div>

                <div class="modal-footer bg-light border-0 py-3">
                    <button type="button" class="btn btn-secondary px-3" data-bs-dismiss="modal">Discard</button>
                    <button type="submit" id="immSubmitBtn" class="btn btn-success fw-bold px-4">Commit Ledger Entry</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    // Expiry date viewer sync logic
    $('#vaccineBatchId').on('change', function() {
        const selectedExpiry = $(this).find(':selected').data('expiry');
        $('#drugExpiryDisplay').text(selectedExpiry ? selectedExpiry : 'None selected');
    });

    // Dynamic Balance Calculation Engine
    $(document).on('input', '.drug-calc-trigger', function() {
        const starter  = parseInt($('#qtyStarter').val()) || 0;
        const received = parseInt($('#qtyReceived').val()) || 0;
        const used     = parseInt($('#qtyUsed').val()) || 0;
        const damaged  = parseInt($('#qtyDamaged').val()) || 0;

        const balance = (starter + received) - (used + damaged);
        const display = $('#drugLiveBalanceDisplay');
        display.text(balance.toLocaleString() + ' Units');

        if (balance < 0) {
            display.removeClass('text-dark text-success').addClass('text-danger fw-bold');
            $('#drugSubmitBtn').prop('disabled', true).text('Error: Inventory Deficit');
        } else {
            display.removeClass('text-danger').addClass('text-success fw-bold');
            $('#drugSubmitBtn').prop('disabled', false).text($('#drugAction').val() === 'update' ? 'Save Changes' : 'Commit Ledger Entry');
        }
    });
});
</script>
