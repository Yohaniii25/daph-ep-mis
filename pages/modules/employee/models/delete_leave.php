<!-- Delete Leave Modal -->
<div class="modal fade" id="deleteLeaveModal" tabindex="-1" aria-labelledby="deleteLeaveModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title fw-bold" id="deleteLeaveModalLabel">
                    <i class="bi bi-trash me-2"></i>Delete Leave Request
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="deleteLeaveForm" action="../../../controllers/employee/delete_leave.php" method="POST">
                <div class="modal-body">
                    <input type="hidden" name="leave_id" id="delete_leave_id">
                    <div class="text-center py-3">
                        <i class="bi bi-exclamation-triangle fs-1 text-warning mb-3 d-block"></i>
                        <p class="mb-0">Are you sure you want to delete this leave request?</p>
                        <small class="text-muted">This action cannot be undone.</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger">
                        <i class="bi bi-trash me-2"></i>Delete
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    // Show delete confirmation modal
    function confirmDelete(leaveId) {
        $('#delete_leave_id').val(leaveId);
        $('#deleteLeaveModal').modal('show');
    }
</script>