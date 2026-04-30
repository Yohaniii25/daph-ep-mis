<div class="modal fade" id="addTaskModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="processors/save_diary.php" method="POST">
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
                    <div class="mb-3">
                        <label class="form-label">Current Status</label>
                        <select name="status" class="form-select">
                            <option value="Not Started">Not Started</option>
                            <option value="Ongoing">Ongoing</option>
                            <option value="Completed">Completed</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" name="save_task" class="btn btn-primary">Save Task</button>
                </div>
            </form>
        </div>
    </div>
</div>
