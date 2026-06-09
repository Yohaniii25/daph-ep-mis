<div class="modal fade" id="addAccommodationModal" tabindex="-1" aria-labelledby="addAccommodationModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow">
            <form action="#" method="POST">
                <div class="modal-header text-white py-3" style="background-color: #370709;">
                    <h5 style="color: white;" class="modal-title fw-bold" id="addAccommodationModalLabel">
                        <i class="bi bi-house-add me-2"></i>Add Hostel Accommodation Booking
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-close="modal" aria-label="Close"></button>
                </div>
                
                <div class="modal-body p-4">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-secondary">Hostel Block Location Location <span class="text-danger">*</span></label>
                            <select class="form-select" name="hostel_block" required>
                                <option value="" disabled selected>-- Choose Building Block --</option>
                                <option value="A">Block A (Main Officer Wing)</option>
                                <option value="B">Block B (Farmer Training Wing)</option>
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-secondary">Room No / Shared Space Index <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="room_number" placeholder="e.g., Room 04" required>
                        </div>

                        <div class="col-md-12">
                            <label class="form-label small fw-bold text-secondary">Target Guest Profile / Farmer Range Entity Alignment <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="guest_alignment" placeholder="e.g., Ampara Range Field Officers / Batch 03" required>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label small fw-bold text-secondary">Check-In Date <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" name="check_in" required>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label small fw-bold text-secondary">Check-Out Date <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" name="check_out" required>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label small fw-bold text-secondary">Total Beds to Reserve <span class="text-danger">*</span></label>
                            <input type="number" min="1" max="10" class="form-control" name="beds_count" value="1" required>
                        </div>
                    </div>
                </div>
                
                <div class="modal-footer bg-light py-3">
                    <button type="button" class="btn btn-secondary px-4" data-bs-close="modal">Discard</button>
                    <button type="button" class="btn text-white px-4 fw-bold shadow-sm" style="background-color: #370709;" data-bs-close="modal">Check-In Guest (Demo)</button>
                </div>
            </form>
        </div>
    </div>
</div>