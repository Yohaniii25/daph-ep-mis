<div class="modal fade" id="manualEntryModal" tabindex="-1" aria-labelledby="manualEntryModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-dark text-white">
                <h5 class="modal-title" id="manualEntryModalLabel">
                    <i class="bi bi-envelope-plus me-2"></i>Manual Email Entry
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="processors/save_inquiry.php" method="POST">
                <div class="modal-body p-4">
                    <p class="text-muted small mb-4">Copy and paste the details from the received email into the fields below to begin processing.</p>
                    
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-uppercase">Sender Name</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light"><i class="bi bi-person"></i></span>
                                <input type="text" name="sender_name" class="form-control" placeholder="e.g. John Doe" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-uppercase">Sender Email Address</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light"><i class="bi bi-at"></i></span>
                                <input type="email" name="sender_email" class="form-control" placeholder="example@mail.com" required>
                            </div>
                        </div>

                        <div class="col-12">
                            <label class="form-label small fw-bold text-uppercase">Email Subject</label>
                            <input type="text" name="subject" class="form-control" placeholder="Brief summary of the inquiry" required>
                        </div>

                        <div class="col-12">
                            <label class="form-label small fw-bold text-uppercase">Inquiry Message / Email Body</label>
                            <textarea name="message_body" class="form-control" rows="8" placeholder="Paste the full content of the email here..." required></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Discard</button>
                    <button type="submit" name="submit_inquiry" class="btn btn-dark px-4">
                        <i class="bi bi-save me-2"></i>Save & Open for Processing
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>