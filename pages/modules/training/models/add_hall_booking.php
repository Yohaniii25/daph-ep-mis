<div class="modal fade" id="addHallBookingModal" tabindex="-1" aria-labelledby="addHallBookingModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow">
            <form action="#" method="POST">
                <div class="modal-header bg-dark text-white py-3">
                    <h5 style="color: white;" class="modal-title fw-bold" id="addHallBookingModalLabel">
                        <i class="bi bi-door-open me-2 text-success"></i>New Lecture Hall Booking
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-close="modal" aria-label="Close"></button>
                </div>
                
                <div class="modal-body p-4">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-secondary">Select Lecture Hall / Venue <span class="text-danger">*</span></label>
                            <select class="form-select" name="hall_id" required>
                                <option value="" disabled selected>-- Choose Target Venue --</option>
                                <option value="1">Main Lecture Hall A</option>
                                <option value="2">Auditorium Complex</option>
                                <option value="3">Veterinary Range Seminar Room</option>
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-secondary">Booking Reservation Date <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" name="booking_date" required>
                        </div>

                        <div class="col-md-12">
                            <label class="form-label small fw-bold text-secondary">Align with Active Training Program <span class="text-danger">*</span></label>
                            <select class="form-select border-2" name="program_id" required>
                                <option value="" disabled selected>-- Choose Scheduled Training Allocation --</option>
                                <option value="101">Modern Milking Hygiene & Quality Parameters (TG-041)</option>
                                <option value="102">Bio-security Measures & Outbreak Control Basics (TG-098)</option>
                            </select>
                        </div>

                        <div class="col-md-12">
                            <label class="form-label small fw-bold text-secondary">Operational Logistics / Special Setup Notes</label>
                            <textarea class="form-control" name="notes" rows="3" placeholder="e.g., Multimedia projector and sound system configurations setup needed..."></textarea>
                        </div>
                    </div>
                </div>
                
                <div class="modal-footer bg-light py-3">
                    <button type="button" class="btn btn-secondary px-4" data-bs-close="modal">Cancel</button>
                    <button type="button" class="btn btn-success px-4 fw-bold shadow-sm" data-bs-close="modal">Confirm Booking (Demo)</button>
                </div>
            </form>
        </div>
    </div>
</div>