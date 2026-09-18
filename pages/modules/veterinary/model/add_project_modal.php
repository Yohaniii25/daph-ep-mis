<div class="modal fade" id="addProjectModal" tabindex="-1" aria-labelledby="addProjectModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-primary text-white">
                <h5 style="color: white;" class="modal-title" id="addProjectModalLabel">
                    <i class="bi bi-folder-plus me-2"></i>New Project Registration
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            
            <form action="processors/process_project.php" method="POST" id="projectForm">
                <div class="modal-body p-4">
                    <div class="row g-3">
                        
                        <div class="col-md-6">
                            <label class="form-label fw-bold small text-uppercase">Project Type <span class="text-danger">*</span></label>
                            <select class="form-select" name="project_type" id="project_type_select" required>
                                <option value="" selected disabled>Choose Type...</option>
                                <option value="PSDG">PSDG</option>
                                <option value="LMP">LMP</option>
                                <option value="CBG">CBG</option>
                                <option value="Special">Special Project</option>
                                <option value="Other">Other Activity</option>
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-bold small text-uppercase">Project Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="project_name" id="project_name_display" required>
                        </div>

                        <div class="col-md-8">
                            <label class="form-label fw-bold small text-uppercase">Location / GN Division</label>
                            <input type="text" class="form-control" name="location" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold small text-uppercase">Priority</label>
                            <select class="form-select" name="priority">
                                <option value="Low">Low</option>
                                <option value="Medium" selected>Medium</option>
                                <option value="High">High</option>
                                <option value="Urgent">Urgent</option>
                            </select>
                        </div>

                        <hr class="my-2">

                        <div class="col-md-6">
                            <label class="form-label fw-bold small text-uppercase">Start Date</label>
                            <input type="date" class="form-control" name="start_date" value="<?= date('Y-m-d') ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold small text-uppercase">Target End Date</label>
                            <input type="date" class="form-control" name="end_date" required>
                        </div>

                        <div class="col-12">
                            <label class="form-label fw-bold small text-uppercase">Assigned Officers (Search & Add)</label>
                            <div class="input-group mb-2">
                                <span class="input-group-text bg-white"><i class="bi bi-search"></i></span>
                                <input type="text" id="officerSearch" class="form-control" placeholder="Type officer name..." list="officerList">
                                <datalist id="officerList">
                                    <?php
                                    $off_stmt = $mysqli->prepare("SELECT id, officer_name, designation FROM office_details WHERE range_id = ? AND status = 'Active'");
                                    $off_stmt->bind_param("i", $range_id);
                                    $off_stmt->execute();
                                    $officers = $off_stmt->get_result();
                                    while($off = $officers->fetch_assoc()):
                                    ?>
                                        <option value="<?= htmlspecialchars($off['officer_name']) ?>" data-id="<?= $off['id'] ?>" data-desig="<?= $off['designation'] ?>">
                                    <?php endwhile; ?>
                                </datalist>
                                <button type="button" class="btn btn-outline-primary" id="addOfficerBtn">Add</button>
                            </div>

                            <div id="selectedOfficers" class="d-flex flex-wrap gap-2 p-2 border rounded bg-light min-vh-10">
                                <span class="text-muted small py-1" id="noOfficerMsg">No officers assigned yet.</span>
                            </div>
                        </div>

                        <div class="col-12">
                            <label class="form-label fw-bold small text-uppercase">Brief Summary</label>
                            <textarea class="form-control" name="summary" rows="2"></textarea>
                        </div>

                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-link text-decoration-none text-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary px-5 shadow-sm">Save Project</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// 1. Logic for Project Name auto-fill
document.getElementById('project_type_select').addEventListener('change', function() {
    const nameInput = document.getElementById('project_name_display');
    if (this.value === 'Special' || this.value === 'Other') {
        nameInput.value = ""; 
        nameInput.placeholder = "Enter Specific Project Name...";
        nameInput.focus();
    } else {
        nameInput.value = this.value;
    }
});

// 2. Search & Suggest Logic for Officers
const officerSearch = document.getElementById('officerSearch');
const addOfficerBtn = document.getElementById('addOfficerBtn');
const selectedOfficersDiv = document.getElementById('selectedOfficers');
const noOfficerMsg = document.getElementById('noOfficerMsg');
const officerList = document.getElementById('officerList');

addOfficerBtn.addEventListener('click', function() {
    const val = officerSearch.value;
    const options = officerList.childNodes;
    
    let officerId = null;
    let designation = "";

    // Find the ID and Designation from the datalist
    for (let i = 0; i < options.length; i++) {
        if (options[i].value === val) {
            officerId = options[i].getAttribute('data-id');
            designation = options[i].getAttribute('data-desig');
            break;
        }
    }

    if (officerId) {
        // Prevent adding the same officer twice
        if (document.getElementById('assigned_off_' + officerId)) {
            alert("Officer already added.");
            officerSearch.value = "";
            return;
        }

        // Remove "No officers assigned" message
        if (noOfficerMsg) noOfficerMsg.remove();

        // Create the tag/badge
        const tag = document.createElement('div');
        tag.className = "badge bg-white text-dark border p-2 d-flex align-items-center shadow-sm";
        tag.id = 'assigned_off_' + officerId;
        tag.innerHTML = `
            <div class="text-start me-2">
                <div class="fw-bold">${val}</div>
                <div class="x-small text-muted" style="font-size: 10px;">${designation}</div>
            </div>
            <input type="hidden" name="officers[]" value="${officerId}">
            <button type="button" class="btn-close ms-2" style="font-size: 10px;" onclick="this.parentElement.remove()"></button>
        `;
        
        selectedOfficersDiv.appendChild(tag);
        officerSearch.value = ""; // Clear search
    } else {
        alert("Please select a valid officer from the list.");
    }
});
</script>