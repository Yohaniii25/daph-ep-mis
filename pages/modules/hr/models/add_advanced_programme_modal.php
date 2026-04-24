<div class="modal fade" id="addAdvancedModal" tabindex="-1" aria-labelledby="addAdvancedModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content border-0 shadow">
            <form action="processors/process_advanced_programme.php" method="POST">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="addAdvancedModalLabel"><i class="bi bi-calendar-plus me-2"></i>New Advanced Programme</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label fw-bold">Programme Year</label>
                            <select name="programme_year" class="form-select" required>
                                <option value="<?php echo date('Y'); ?>"><?php echo date('Y'); ?> (Current)</option>
                                <option value="<?php echo date('Y') + 1; ?>"><?php echo date('Y') + 1; ?> (Next)</option>
                            </select>
                        </div>

                        <div class="col-md-8">
                            <label class="form-label fw-bold">Activity Type</label>
                            <select name="type_id" class="form-select" required>
                                <option value="">-- Choose Category --</option>
                                <?php
                                $master_types = $mysqli->query("SELECT id, programme_name FROM master_programme_types WHERE is_active = 1 ORDER BY programme_name ASC");
                                while($mt = $master_types->fetch_assoc()): ?>
                                    <option value="<?php echo $mt['id']; ?>">
                                        <?php echo htmlspecialchars($mt['programme_name']); ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>

                        <div class="col-md-12">
                            <label class="form-label fw-bold">Location / Place</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-geo-alt"></i></span>
                                <input type="text" name="place" class="form-control" placeholder="e.g. Mutur Range / District Office" required>
                            </div>
                        </div>

                        <div class="col-md-12">
                            <label class="form-label fw-bold">Implementation Details</label>
                            <textarea name="activity_description" class="form-control" rows="4" placeholder="Describe the specific objectives for this yearly activity..."></textarea>
                        </div>
                    </div>

                    <div class="alert alert-info mt-4 mb-0 py-2 border-0 small">
                        <i class="bi bi-info-circle me-2"></i> This programme will require a <strong>Mid-Term (6 Months)</strong> and <strong>Annual (1 Year)</strong> approval from the Provincial Director.
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="save_advanced" class="btn btn-primary px-4">Initialize Programme</button>
                </div>
            </form>
        </div>
    </div>
</div>