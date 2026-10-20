<!-- Reusable Modal: Log / Edit Operational Training Session -->
<div class="modal fade" id="modalLogTraining" tabindex="-1" aria-labelledby="modalLogTrainingLabel" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header text-white" style="background: linear-gradient(135deg, #820100 0%, #a30100 100%);">
                <div class="d-flex align-items-center">
                    <div class="rounded-circle bg-white text-danger p-2 me-3 d-flex align-items-center justify-content-center" style="width: 42px; height: 42px;">
                        <i class="bi bi-mortarboard-fill fs-5"></i>
                    </div>
                    <div>
                        <h5 class="modal-title fw-bold mb-0" id="modalLogTrainingLabel">Log Training &amp; Extension Session</h5>
                        <small class="text-white-50" id="modalTrainingSubtitle">Operational capacity development and farmer education</small>
                    </div>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            
            <form id="trainingSessionForm" enctype="multipart/form-data" novalidate>
                <input type="hidden" name="action" value="save_training">
                <input type="hidden" name="training_id" id="tr_training_id" value="0">
                <input type="hidden" name="range_id" id="tr_range_id" value="<?= htmlspecialchars((string)($range_id ?? 1)) ?>">
                <input type="hidden" name="module_context" id="tr_module_context" value="projects">

                <div class="modal-body p-4 bg-light">
                    <!-- Context Notification Banner -->
                    <div class="alert alert-info py-2 px-3 mb-3 d-flex align-items-center small">
                        <i class="bi bi-info-circle-fill me-2 fs-6"></i>
                        <span>All fields marked with <strong class="text-danger">*</strong> are mandatory departmental records.</span>
                    </div>

                    <div class="card border-0 shadow-sm p-3 mb-3 bg-white rounded-3">
                        <div class="row g-3">
                            <!-- Field 1: Training Date -->
                            <div class="col-md-6">
                                <label for="tr_training_date" class="form-label fw-bold text-dark small text-uppercase">
                                    Training Date <span class="text-danger">*</span>
                                </label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light text-muted"><i class="bi bi-calendar-event"></i></span>
                                    <input type="date" class="form-control" id="tr_training_date" name="training_date" required value="<?= date('Y-m-d') ?>">
                                </div>
                                <div class="invalid-feedback">Please select the training date.</div>
                            </div>

                            <!-- Field 5: Attendance (Numeric Count) -->
                            <div class="col-md-6">
                                <label for="tr_attendance" class="form-label fw-bold text-dark small text-uppercase">
                                    Attendance (Numeric Count) <span class="text-danger">*</span>
                                </label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light text-muted"><i class="bi bi-people-fill"></i></span>
                                    <input type="number" class="form-control" id="tr_attendance" name="attendance" min="1" step="1" required placeholder="e.g. 35">
                                </div>
                                <div class="invalid-feedback">Please enter a valid numeric attendee count.</div>
                            </div>

                            <!-- Field 2: Topic -->
                            <div class="col-md-12">
                                <label for="tr_topic" class="form-label fw-bold text-dark small text-uppercase">
                                    Training Topic / Subject <span class="text-danger">*</span>
                                </label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light text-muted"><i class="bi bi-journal-text"></i></span>
                                    <input type="text" class="form-control" id="tr_topic" name="topic" list="topicSuggestions" required placeholder="e.g. Clean Milk Production, Mastitis Control & CMT Testing">
                                </div>
                                <datalist id="topicSuggestions">
                                    <option value="Clean Milk Production, Mastitis Control & CMT Testing">
                                    <option value="Heifer Calf Rearing & Artificial Insemination Timelines">
                                    <option value="PSDG Commercial Poultry Bio-Security & Housing">
                                    <option value="LMP Fodder Cultivation & Silage Preparation Methods">
                                    <option value="Cattle Nutrition, Ration Balancing & Mineral Supplementation">
                                    <option value="Tick-borne Diseases & Internal Parasite Deworming Protocol">
                                    <option value="Animal Welfare, Biosecurity and Farm Waste Management">
                                    <option value="Dairy Farmer Cooperative Governance & Milk Pricing Standards">
                                </datalist>
                                <div class="invalid-feedback">Please provide the training topic.</div>
                            </div>

                            <!-- Field 3: Target Group -->
                            <div class="col-md-6">
                                <label for="tr_target_group" class="form-label fw-bold text-dark small text-uppercase">
                                    Target Group <span class="text-danger">*</span>
                                </label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light text-muted"><i class="bi bi-diagram-3"></i></span>
                                    <input type="text" class="form-control" id="tr_target_group" name="target_group" list="targetGroupSuggestions" required placeholder="e.g. Dairy Farmers &amp; Hub Members">
                                </div>
                                <datalist id="targetGroupSuggestions">
                                    <option value="Dairy Farmers & Hub Members">
                                    <option value="Poultry Farmers & Beneficiaries">
                                    <option value="Smallholder Livestock Farmers">
                                    <option value="Dairy Farm Holders & Women Beneficiaries">
                                    <option value="Commercial Dairy Enterprise Operators">
                                    <option value="Veterinary Range Staff & LDOs">
                                    <option value="Community Milk Collectors & Transporters">
                                </datalist>
                                <div class="invalid-feedback">Please specify the target group.</div>
                            </div>

                            <!-- Field 4: Location -->
                            <div class="col-md-6">
                                <label for="tr_location" class="form-label fw-bold text-dark small text-uppercase">
                                    Location / Venue <span class="text-danger">*</span>
                                </label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light text-muted"><i class="bi bi-geo-alt-fill"></i></span>
                                    <input type="text" class="form-control" id="tr_location" name="location" required placeholder="e.g. Range Office Auditorium / Milk Collection Center">
                                </div>
                                <div class="invalid-feedback">Please provide the training location.</div>
                            </div>

                            <!-- Field 7: Trainer / Facilitator -->
                            <div class="col-md-6">
                                <label for="tr_trainer_name" class="form-label fw-bold text-dark small text-uppercase">
                                    Trainer / Resource Person
                                </label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light text-muted"><i class="bi bi-person-badge"></i></span>
                                    <input type="text" class="form-control" id="tr_trainer_name" name="trainer_name" placeholder="e.g. Government Veterinary Surgeon / SMS">
                                </div>
                            </div>

                            <!-- Field 6: Photos (File upload functionality) -->
                            <div class="col-md-6">
                                <label for="tr_photo" class="form-label fw-bold text-dark small text-uppercase">
                                    Session Photo <span class="text-primary"><i class="bi bi-camera-fill me-1"></i>(Upload Photo)</span>
                                </label>
                                <input type="file" class="form-control" id="tr_photo" name="training_photo" accept="image/jpeg,image/png,image/webp,image/gif">
                                <div class="form-text small">Accepted: JPG, PNG, WEBP (Max 10MB). Captures field evidence.</div>
                            </div>

                            <!-- Image Preview Area -->
                            <div class="col-md-12 d-none" id="tr_photo_preview_container">
                                <div class="border rounded p-2 bg-light d-flex align-items-center justify-content-between">
                                    <div class="d-flex align-items-center gap-3">
                                        <img id="tr_photo_preview_img" src="" alt="Preview" class="rounded border shadow-sm" style="width: 80px; height: 60px; object-fit: cover;">
                                        <div>
                                            <div class="fw-bold small" id="tr_photo_label">Session Photo Selected</div>
                                            <small class="text-muted" id="tr_photo_sub">Ready for upload</small>
                                        </div>
                                    </div>
                                    <button type="button" class="btn btn-sm btn-outline-danger" id="tr_btn_clear_photo">
                                        <i class="bi bi-x-circle me-1"></i>Remove
                                    </button>
                                </div>
                            </div>

                            <!-- Field 8: Remarks / Key Highlights -->
                            <div class="col-md-12">
                                <label for="tr_remarks" class="form-label fw-bold text-dark small text-uppercase">
                                    Remarks / Practical Demonstrations &amp; Materials
                                </label>
                                <textarea class="form-control" id="tr_remarks" name="remarks" rows="2" placeholder="Record key topics taught, practical demonstrations conducted, handouts/inputs distributed, etc."></textarea>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="modal-footer bg-white border-top">
                    <button type="button" class="btn btn-secondary px-4" data-bs-dismiss="modal">
                        <i class="bi bi-x me-1"></i>Close
                    </button>
                    <button type="submit" class="btn btn-success px-4" id="tr_btn_submit" style="background-color: #820100; border-color: #820100;">
                        <i class="bi bi-check2-circle me-1"></i><span id="tr_submit_text">Save Training Record</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal: View Full Size Training Photo -->
<div class="modal fade" id="modalViewTrainingPhoto" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-dark text-white py-2">
                <h6 class="modal-title" id="viewPhotoTitle"><i class="bi bi-image me-2"></i>Training Session Photo</h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body text-center p-2 bg-black">
                <img id="viewPhotoImg" src="" alt="Training Photo" class="img-fluid rounded" style="max-height: 80vh;">
                <div class="text-white-50 small mt-2" id="viewPhotoCaption"></div>
            </div>
        </div>
    </div>
</div>
