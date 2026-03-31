<div class="modal fade" id="recordTreatmentModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title"><i class="bi bi-plus-circle me-2"></i> Record Animal Health & Vaccination</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form action="processors/save_health_record.php" method="POST">
                <div class="modal-body">
                    <input type="hidden" name="range_id" value="<?= $range_id ?>">

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Farmer Reg Number</label>
                            <input type="text" name="farmer_reg_no" class="form 00-control" placeholder="e.g. EP/TRIN/VET/1024" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Date of Treatment/Vaccination</label>
                            <input type="date" name="date" class="form-control" value="<?= date('Y-m-d') ?>" required>
                        </div>

                        <hr class="my-2">

                        <div class="mb-3">
                            <label class="form-label small fw-bold">Animal Type / Species</label>
                            <select name="animal_type" class="form-select form-select-sm border-dark" required>
                                <option value="" selected disabled>-- Select Species --</option>
                                <option value="Cattle">Cattle</option>
                                <option value="Buffalo">Buffalo</option>
                                <option value="Goat">Goat</option>
                                <option value="Sheep">Sheep</option>
                                <option value="Swine">Swine</option>
                                <option value="Poultry">Poultry</option>
                                <option value="Ornamental Birds">Ornamental Birds</option>
                                <option value="Other">Other</option>
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-bold">Disease Name</label>
                            <input type="text" name="disease_name" class="form-control" placeholder="e.g. Foot & Mouth Disease" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Affected Animals (Count)</label>
                            <input type="number" name="occurrence_count" class="form-control" min="1" required>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-bold">Vaccine Given</label>
                            <input type="text" name="vaccine_name" class="form-control" placeholder="Name of vaccine (if any)">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Number of Doses</label>
                            <input type="number" name="doses" class="form-control" min="0" value="0">
                        </div>

                        <div class="col-12">
                            <label class="form-label fw-bold">Treatment & Remarks</label>
                            <textarea name="treatment_details" class="form-control" rows="3" placeholder="Medicine details, dosage, next follow-up..."></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success">Save Record</button>
                </div>
            </form>
        </div>
    </div>
</div>