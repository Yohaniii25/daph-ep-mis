<div class="modal fade" id="addTaskModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="processors/process_diary.php" method="POST">
                <div class="modal-header">
                    <h5 class="modal-title">Add New Diary Task</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="user_id" value="<?php echo $_SESSION['user_id']; ?>">
                    
                    <div class="mb-3">
                        <label class="form-label">Date</label>
                        <input type="date" name="task_date" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Place/Location</label>
                        <input type="text" name="place" class="form-control" placeholder="e.g. Mutur Range Office" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Activity Description</label>
                        <textarea name="activity" class="form-control" rows="3" required></textarea>
                    </div>
                    <div class="row g-2">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Distance (km)</label>
                            <input type="text" name="distance" class="form-control" placeholder="e.g. 12.5" value="0">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Time Duration</label>
                            <input type="text" name="time_duration" class="form-control" placeholder="e.g. 09:00 AM - 01:00 PM">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" name="save_task" class="btn btn-primary">Save Task</button>
                </div>
            </form>
        </div>
    </div>
</div>
