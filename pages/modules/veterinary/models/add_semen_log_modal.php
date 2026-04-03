<div class="modal fade" id="addSemenModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content border-0">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title">Record Monthly Semen Log</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form action="processors/save_semen_log.php" method="POST">
                <div class="modal-body p-4">
                    <div class="row g-3 mb-4">
                        <div class="col-md-4">
                            <label class="form-label small fw-bold">Month</label>
                            <select name="report_month" class="form-select" required>
                                <?php for ($i = 1; $i <= 12; $i++) echo "<option value='$i' " . (date('n') == $i ? 'selected' : '') . ">" . date('F', mktime(0, 0, 0, $i, 1)) . "</option>"; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold">Species</label>
                            <select name="species" id="speciesSelect" class="form-select" required onchange="toggleOtherInput()">
                                <option value="Cattle">Cattle</option>
                                <option value="Buffalo">Buffalo</option>
                                <option value="Goat">Goat</option>
                                <option value="Sheep">Sheep</option>
                                <option value="Poultry">Poultry</option>
                                <option value="Other">Other (Specify)</option>
                            </select>
                            <div id="otherSpeciesDiv" class="mt-2" style="display: none;">
                                <input type="text" name="other_species" id="otherSpeciesInput" class="form-control form-control-sm" placeholder="Enter animal name">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold">Opening Balance</label>
                            <input type="number" name="opening_balance" class="form-control bg-light" placeholder="0" required>
                        </div>
                    </div>

                    <div class="row g-3">
                        <div class="col-md-6 border-end">
                            <h6 class="text-success fw-bold border-bottom pb-2">ADDITIONS (Stock In)</h6>
                            <div class="mb-3">
                                <label class="form-label small">Received Semen</label>
                                <input type="number" name="received_qty" class="form-control" value="0">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <h6 class="text-danger fw-bold border-bottom pb-2">DEDUCTIONS (Stock Out)</h6>
                            <div class="mb-2">
                                <label class="form-label small">Used Semen</label>
                                <input type="number" name="used_qty" class="form-control" value="0">
                            </div>
                            <div class="mb-2">
                                <label class="form-label small">Issued Semen</label>
                                <input type="number" name="issued_qty" class="form-control" value="0">
                            </div>
                            <div class="mb-2 text-warning">
                                <label class="form-label small">Spoiled / Damaged</label>
                                <input type="number" name="spoiled_qty" class="form-control" value="0">
                            </div>
                        </div>
                    </div>

                    <div class="mt-4 p-3 bg-light rounded">
                        <label class="form-label fw-bold">Paid Amount (Rs.)</label>
                        <input type="number" step="0.01" name="paid_amount" class="form-control" placeholder="0.00">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-success px-5">Save Monthly Log</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    function toggleOtherInput() {
        const select = document.getElementById('speciesSelect');
        const otherDiv = document.getElementById('otherSpeciesDiv');
        const otherInput = document.getElementById('otherSpeciesInput');

        if (select.value === 'Other') {
            otherDiv.style.display = 'block';
            otherInput.required = true;
            otherInput.focus();
        } else {
            otherDiv.style.display = 'none';
            otherInput.required = false;
            otherInput.value = '';
        }
    }
</script>