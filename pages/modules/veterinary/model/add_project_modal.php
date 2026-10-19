<?php
/**
 * Project Entry Modal
 * Department of Animal Production and Health
 * Strict adherence to departmental metrics:
 * - Excluded fields: Geographic location and assigned staff members omitted
 * - Core Fields: Start Date, Target End Date, Range Office Name, Project Type, Project Name, Target Number of Beneficiaries
 * - Dual Contribution Tracking: Department Contribution (Amount & Activity, 50-100% subsidy) and Beneficiary Contribution (Amount & Activity)
 */

if (!isset($range_name) || empty($range_name)) {
    $range_name = 'Range Office';
    if (!empty($_SESSION['range_id'])) {
        $r_id = intval($_SESSION['range_id']);
        $r_q = $mysqli->query("SELECT name FROM veterinary_ranges WHERE id = $r_id");
        if ($r_q && $row = $r_q->fetch_assoc()) {
            $range_name = $row['name'];
        }
    }
}
// Fetch Dynamic PSDG Project Catalog for expansion readiness (~23 standard projects)
$psdg_catalog_items = [];
$cat_res = $mysqli->query("SELECT category_name FROM `psdg_project_catalog` WHERE is_active = 1 ORDER BY category_name ASC");
if ($cat_res) {
    while ($cr = $cat_res->fetch_assoc()) {
        $psdg_catalog_items[] = $cr['category_name'];
    }
}
?>

<div class="modal fade" id="addProjectModal" tabindex="-1" aria-labelledby="addProjectModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title text-white" id="addProjectModalLabel">
                    <i class="bi bi-folder-plus me-2"></i>New Project Entry
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            
            <form action="processors/process_project.php" method="POST" id="projectForm">
                <input type="hidden" name="range_id" value="<?= htmlspecialchars($range_id ?? '') ?>">
                <div class="modal-body p-4">
                    
                    <!-- SECTION 1: CORE PROJECT DETAILS -->
                    <div class="mb-4">
                        <h6 class="fw-bold text-primary border-bottom pb-2">
                            <i class="bi bi-info-circle me-1"></i>Core Project Information
                        </h6>
                        <div class="row g-3 mt-1">
                            
                            <!-- Range Office Name -->
                            <div class="col-md-6">
                                <label class="form-label fw-bold small text-uppercase">Range Office Name <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light"><i class="bi bi-geo-alt"></i></span>
                                    <input type="text" class="form-control" name="range_office_name" id="range_office_name" value="<?= htmlspecialchars($range_name) ?>" required placeholder="e.g. Batticaloa Range Office">
                                </div>
                            </div>

                            <!-- Project Type -->
                            <div class="col-md-6">
                                <label class="form-label fw-bold small text-uppercase">Project Type <span class="text-danger">*</span></label>
                                <select class="form-select" name="project_type" id="project_type_select" required>
                                    <option value="" selected disabled>Choose Project Type...</option>
                                    <option value="PSDG">PSDG (Provincial Specific Development Grant)</option>
                                    <option value="LMP">LMP (Livestock Modernization Project)</option>
                                    <option value="CBG">CBG (Community Based Grant)</option>
                                    <option value="Special">Special Project Works</option>
                                    <option value="Other">Other Departmental Activity</option>
                                </select>
                            </div>

                            <!-- Project Name (Activity) with 23 PSDG Catalog Datalist -->
                            <div class="col-md-8">
                                <label class="form-label fw-bold small text-uppercase">
                                    Project Name / Activity <span class="text-danger">*</span>
                                    <span class="badge bg-info-subtle text-info-emphasis ms-1" id="catalog_badge">23 PSDG Categories</span>
                                </label>
                                <div class="input-group">
                                    <input type="text" class="form-control" name="project_name" id="project_name_input" list="psdg_catalog_list" required placeholder="Select or type activity (e.g. goat purchasing, goat shed upgrading)...">
                                    <button class="btn btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">Presets</button>
                                    <ul class="dropdown-menu dropdown-menu-end shadow" style="max-height: 280px; overflow-y: auto;">
                                        <li><h6 class="dropdown-header text-uppercase">PSDG Standard Project Catalog (23)</h6></li>
                                        <?php foreach ($psdg_catalog_items as $cat_item): ?>
                                            <li><a class="dropdown-item small" href="javascript:void(0);" onclick="selectPsdgCategory('<?= htmlspecialchars(addslashes($cat_item)) ?>')"><?= htmlspecialchars($cat_item) ?></a></li>
                                        <?php endforeach; ?>
                                    </ul>
                                </div>
                                <datalist id="psdg_catalog_list">
                                    <?php foreach ($psdg_catalog_items as $cat_item): ?>
                                        <option value="<?= htmlspecialchars($cat_item) ?>">
                                    <?php endforeach; ?>
                                </datalist>
                                <div class="form-text small text-muted">Scalable for all ~23 PSDG sub-projects including goat purchasing, goat shed upgrading, cattle shed renovation, etc.</div>
                            </div>

                            <!-- Target Number of Beneficiaries -->
                            <div class="col-md-4">
                                <label class="form-label fw-bold small text-uppercase">Target Beneficiaries <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light"><i class="bi bi-people"></i></span>
                                    <input type="number" class="form-control" name="target_beneficiaries" id="target_beneficiaries" min="1" step="1" required placeholder="e.g. 25">
                                </div>
                            </div>

                            <!-- Funding Agent -->
                            <div class="col-md-12">
                                <label class="form-label fw-bold small text-uppercase">Funding Agent</label>
                                <input type="text" class="form-control" name="funding_agent" id="funding_agent_input" placeholder="e.g., Provincial Ministry of Agriculture, PSDG, Central Government">
                                <div class="form-text small">Automatically suggested based on selected project type, or specify customized funding body.</div>
                            </div>

                            <!-- Start Date -->
                            <div class="col-md-6">
                                <label class="form-label fw-bold small text-uppercase">Start Date <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light"><i class="bi bi-calendar-event"></i></span>
                                    <input type="date" class="form-control" name="start_date" id="start_date" value="<?= date('Y-m-d') ?>" required>
                                </div>
                            </div>

                            <!-- Target End Date -->
                            <div class="col-md-6">
                                <label class="form-label fw-bold small text-uppercase">Target End Date <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light"><i class="bi bi-calendar-check"></i></span>
                                    <input type="date" class="form-control" name="end_date" id="end_date" value="<?= date('Y-m-d', strtotime('+6 months')) ?>" required>
                                </div>
                            </div>

                        </div>
                    </div>

                    <!-- SECTION 2: FINANCIAL & DUAL CONTRIBUTION TRACKING -->
                    <div class="mb-3">
                        <h6 class="fw-bold text-success border-bottom pb-2">
                            <i class="bi bi-cash-stack me-1"></i>Financial &amp; Contribution Tracking (Dual Tracking)
                        </h6>
                        <div class="row g-3 mt-1">
                            
                            <!-- Department Contribution Card -->
                            <div class="col-md-6">
                                <div class="card border border-success-subtle h-100 shadow-none bg-light p-3">
                                    <h6 class="fw-bold text-success mb-2">
                                        <i class="bi bi-building me-1"></i>Department Contribution
                                    </h6>
                                    
                                    <div class="mb-2">
                                        <label class="form-label small fw-bold text-uppercase">Contribution Amount (Rs.) <span class="text-danger">*</span></label>
                                        <div class="input-group">
                                            <span class="input-group-text">Rs.</span>
                                            <input type="number" class="form-control" name="dept_contribution_amount" id="dept_contribution_amount" step="0.01" min="0" required placeholder="0.00">
                                        </div>
                                    </div>

                                    <div class="mb-2">
                                        <label class="form-label small fw-bold text-uppercase">Subsidy Rate (50% - 100%) <span class="text-danger">*</span></label>
                                        <select class="form-select form-select-sm" name="dept_subsidy_percentage" id="dept_subsidy_percentage" required>
                                            <option value="100" selected>100% Subsidy (Full Grant)</option>
                                            <option value="90">90% Subsidy</option>
                                            <option value="80">80% Subsidy</option>
                                            <option value="75">75% Subsidy</option>
                                            <option value="70">70% Subsidy</option>
                                            <option value="60">60% Subsidy</option>
                                            <option value="50">50% Subsidy (Matched Funding)</option>
                                        </select>
                                    </div>

                                    <div>
                                        <label class="form-label small fw-bold text-uppercase">Specific Department Activity <span class="text-danger">*</span></label>
                                        <textarea class="form-control form-control-sm" name="dept_contribution_activity" id="dept_contribution_activity" rows="2" required placeholder="e.g. Shed foundation, brick walls, and concrete flooring subsidy"></textarea>
                                    </div>
                                </div>
                            </div>

                            <!-- Beneficiary Contribution Card -->
                            <div class="col-md-6">
                                <div class="card border border-primary-subtle h-100 shadow-none bg-light p-3">
                                    <h6 class="fw-bold text-primary mb-2">
                                        <i class="bi bi-person-check me-1"></i>Beneficiary Contribution (Farmer)
                                    </h6>
                                    
                                    <div class="mb-2">
                                        <label class="form-label small fw-bold text-uppercase">Contribution Amount (Rs.) <span class="text-danger">*</span></label>
                                        <div class="input-group">
                                            <span class="input-group-text">Rs.</span>
                                            <input type="number" class="form-control" name="beneficiary_contribution_amount" id="beneficiary_contribution_amount" step="0.01" min="0" required placeholder="0.00">
                                        </div>
                                    </div>

                                    <div class="mb-2">
                                        <label class="form-label small fw-bold text-uppercase">Remaining Share Status</label>
                                        <input type="text" class="form-control form-control-sm bg-white" id="beneficiary_share_status" readonly value="Farmer equity contribution">
                                    </div>

                                    <div>
                                        <label class="form-label small fw-bold text-uppercase">Specific Beneficiary Activity <span class="text-danger">*</span></label>
                                        <textarea class="form-control form-control-sm" name="beneficiary_contribution_activity" id="beneficiary_contribution_activity" rows="2" required placeholder="e.g. Covering remaining material needs like a roof, timber rafters &amp; manual labor"></textarea>
                                    </div>
                                </div>
                            </div>

                            <div class="col-12">
                                <label class="form-label fw-bold small text-uppercase">Additional Summary / Notes</label>
                                <textarea class="form-control form-control-sm" name="summary" rows="2" placeholder="Optional brief operational remarks..."></textarea>
                            </div>

                        </div>
                    </div>

                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary px-4 shadow-sm">
                        <i class="bi bi-save me-1"></i>Save Project
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Auto-suggest funding agent based on project type
document.getElementById('project_type_select').addEventListener('change', function() {
    const fundingInput = document.getElementById('funding_agent_input');
    const val = this.value;
    if (val === 'PSDG') {
        fundingInput.value = 'PSDG / Provincial Ministry of Agriculture';
    } else if (val === 'LMP') {
        fundingInput.value = 'LMP / Livestock Modernization Project';
    } else if (val === 'CBG') {
        fundingInput.value = 'CBG / Community Based Grant';
    } else if (val === 'Special') {
        fundingInput.value = 'Special Ministry Works';
    } else if (val === 'Other') {
        fundingInput.value = 'Department of Animal Production & Health';
    }
});

// Auto-calculate suggested beneficiary amount when subsidy rate changes
document.getElementById('dept_subsidy_percentage').addEventListener('change', function() {
    const subsidyPct = parseFloat(this.value) || 100;
    const deptAmt = parseFloat(document.getElementById('dept_contribution_amount').value) || 0;
    const shareStatus = document.getElementById('beneficiary_share_status');
    
    if (subsidyPct >= 100) {
        shareStatus.value = "Full Department Subsidy (0% Farmer Cash Share)";
    } else {
        const farmerPct = 100 - subsidyPct;
        shareStatus.value = farmerPct + "% Farmer Contribution";
        if (deptAmt > 0) {
            // total cost = deptAmt / (subsidyPct / 100)
            const totalEst = deptAmt / (subsidyPct / 100);
            const benEst = totalEst - deptAmt;
            const benInput = document.getElementById('beneficiary_contribution_amount');
            if (!benInput.value || parseFloat(benInput.value) === 0) {
                benInput.value = benEst.toFixed(2);
            }
        }
    }
});

function selectPsdgCategory(categoryName) {
    document.getElementById('project_name_input').value = categoryName;
    document.getElementById('project_type_select').value = 'PSDG';
    document.getElementById('funding_agent_input').value = 'PSDG / Provincial Ministry of Agriculture';
    
    const deptAct = document.getElementById('dept_contribution_activity');
    const benAct = document.getElementById('beneficiary_contribution_activity');
    
    if (categoryName.toLowerCase().includes('goat purchasing')) {
        deptAct.value = 'Subsidy for purchasing improved breeding goats (75% grant)';
        benAct.value = 'Animal transportation, supplemental feeding, and housing';
    } else if (categoryName.toLowerCase().includes('shed upgrading') || categoryName.toLowerCase().includes('cattle shed')) {
        deptAct.value = 'Concrete floor, brick foundation, and partition wall materials';
        benAct.value = 'Roofing sheets, timber rafters, and manual masonry labor';
    } else if (categoryName.toLowerCase().includes('silage') || categoryName.toLowerCase().includes('pasture')) {
        deptAct.value = 'Fodder cuttings, fertilizer, and silage bunker construction subsidy';
        benAct.value = 'Land preparation, weeding, labor, and crop maintenance';
    } else if (categoryName.toLowerCase().includes('poultry')) {
        deptAct.value = 'Supply of vaccinated pullets/chicks and basic drinker/feeder equipment';
        benAct.value = 'Night shelter construction, commercial feed, and bio-fencing';
    }
}
</script>