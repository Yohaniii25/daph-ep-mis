<?php
require_once '../../../includes/header.php';
if ($_SESSION['role'] !== 'sms') die("Access denied");

// Demo disease control programs and containment execution data maps
$control_programs = [
    ['id' => 'CP-0981', 'program_name' => 'FMD Emergency Ring Vaccination', 'target_range' => 'Ampara Range - Sector 4', 'start_date' => '2026-05-16', 'progress_pct' => 85, 'status' => 'In Progress', 'officer_in_charge' => 'Dr. K. Silva'],
    ['id' => 'CP-0982', 'program_name' => 'Bovine Brucellosis Serological Testing', 'target_range' => 'Trincomalee Coastal Zone', 'start_date' => '2026-05-24', 'progress_pct' => 40, 'status' => 'In Progress', 'officer_in_charge' => 'Dr. A. Perera'],
    ['id' => 'CP-0983', 'program_name' => 'Anthrax Preventive Barrier Immunization', 'target_range' => 'Samanthurai Perimeter', 'start_date' => '2026-05-28', 'progress_pct' => 100, 'status' => 'Completed', 'officer_in_charge' => 'Dr. M. Rahuman'],
    ['id' => 'CP-0984', 'program_name' => 'Tick-Borne Disease Vector Suppression', 'target_range' => 'Kinniya Pasture Blocks', 'start_date' => '2026-06-02', 'progress_pct' => 0, 'status' => 'Scheduled', 'officer_in_charge' => 'Dr. T. Jayasinghe'],
];

$active_programs = count(array_filter($control_programs, fn($p) => $p['status'] === 'In Progress'));
$completed_programs = count(array_filter($control_programs, fn($p) => $p['status'] === 'Completed'));
?>

<?php require_once '../../../includes/sidebar.php'; ?>

<div id="layoutSidenav_content" class="bg-light">
    <main class="container-fluid px-4 pt-4">
        <h2 class="mb-4">Disease Control Campaigns & Biosecurity Protocols</h2>

        <div class="row g-4 mb-5">
            <div class="col-xl-3 col-md-6">
                <div class="card border-0 shadow-sm h-100 p-4 text-center border-start border-primary border-4">
                    <h6 class="text-muted small text-uppercase fw-bold">Total Enforced Campaigns</h6>
                    <h2 class="text-primary fw-bold mb-0"><?= count($control_programs) ?></h2>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="card border-0 shadow-sm h-100 p-4 text-center border-start border-warning border-4">
                    <h6 class="text-muted small text-uppercase fw-bold">Active Shielding Measures</h6>
                    <h2 class="text-warning fw-bold mb-0"><?= $active_programs ?></h2>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="card border-0 shadow-sm h-100 p-4 text-center border-start border-success border-4">
                    <h6 class="text-muted small text-uppercase fw-bold">Successfully Contained</h6>
                    <h2 class="text-success fw-bold mb-0"><?= $completed_programs ?> Programs</h2>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="card border-0 shadow-sm h-100 p-4 text-center border-start border-info border-4">
                    <h6 class="text-muted small text-uppercase fw-bold">Biosecurity Alert Status</h6>
                    <h2 class="text-info fw-bold mb-0">Normal</h2>
                </div>
            </div>
        </div>

        <div class="card shadow-sm mb-4">
            <div class="card-header bg-light py-3">
                <h5 class="mb-0 fw-bold text-dark"><i class="bi bi-shield-check me-2 text-success"></i>Campaign Management Center</h5>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-3">
                        <button class="btn btn-success w-100 py-3 fw-bold" data-bs-toggle="modal" data-bs-target="#addProgramModal">
                            <i class="bi bi-shield-plus mb-2 d-inline-block fs-5"></i><br>
                            Launch Control Intervention
                        </button>
                    </div>

                </div>
            </div>
        </div>

        <div class="card shadow-sm border-0 rounded-3 mb-4">
            <div class="card-header bg-dark text-white py-3">
                <h5 class="mb-0 fw-semibold" style="color: white;"><i class="bi bi-shield-shaded me-2"></i>Active Regional Disease Suppression & Prophylaxis Registries</h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-striped table-hover mb-0 align-middle">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-4">Program Reference</th>
                                <th>Intervention / Campaign Name</th>
                                <th>Target Range Catchment</th>
                                <th>Activation Date</th>
                                <th style="width: 20%;">Execution Progress</th>
                                <th>Campaign Status</th>
                                <th class="pe-4 text-end">Authorized Officer</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($control_programs as $p): ?>
                            <tr>
                                <td class="ps-4 fw-bold text-secondary">#<?= $p['id'] ?></td>
                                <td><span class="text-primary fw-bold"><?= htmlspecialchars($p['program_name']) ?></span></td>
                                <td><strong><?= htmlspecialchars($p['target_range']) ?></strong></td>
                                <td><span class="fw-semibold text-dark"><?= date('d M Y', strtotime($p['start_date'])) ?></span></td>
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="progress flex-grow-1" style="height: 6px;">
                                            <div class="progress-bar bg-<?php echo $p['progress_pct'] === 100 ? 'success' : 'primary'; ?>" 
                                                 role="progressbar" 
                                                 style="width: <?= $p['progress_pct'] ?>%" 
                                                 aria-valuenow="<?= $p['progress_pct'] ?>" 
                                                 aria-valuemin="0" 
                                                 aria-valuemax="100"></div>
                                        </div>
                                        <span class="small fw-bold text-dark"><?= $p['progress_pct'] ?>%</span>
                                    </div>
                                </td>
                                <td>
                                    <span class="badge bg-<?php 
                                        echo $p['status'] === 'Completed' ? 'success' : ($p['status'] === 'In Progress' ? 'warning text-dark' : 'secondary'); 
                                    ?> px-2.5 py-1.5 small text-uppercase">
                                        <?= htmlspecialchars($p['status']) ?>
                                    </span>
                                </td>
                                <td class="pe-4 text-end fw-semibold text-secondary">
                                    <i class="bi bi-person-badge text-dark-subtle me-1"></i><?= htmlspecialchars($p['officer_in_charge']) ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>
</div>

<div class="modal fade" id="addProgramModal" tabindex="-1" aria-labelledby="addProgramModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-dark text-white py-3">
                <h5 class="modal-title" id="addProgramModalLabel" style="font-size: 17px; color: white;">
                    <i class="bi bi-shield-plus me-2 text-success"></i>Deploy Prophylactic Protection Directive
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <form onsubmit="event.preventDefault();">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold text-secondary">Campaign / Intervention Program Nomenclature</label>
                            <input type="text" class="form-control shadow-sm" placeholder="e.g., Mandatory Ring Immunization Protocol">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold text-secondary">Target Range Operational Catchment</label>
                            <input type="text" class="form-control shadow-sm" placeholder="e.g., Ampara Range Veterinary Block 04">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold text-secondary">Campaign Activation Date</label>
                            <input type="date" class="form-control shadow-sm" value="<?= date('Y-m-d') ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold text-secondary">Assigned Veterinary Surgeon</label>
                            <input type="text" class="form-control shadow-sm" placeholder="e.g., Dr. Name">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold text-secondary">Deployment Strategy Scope</label>
                            <select class="form-select text-dark shadow-sm fw-bold">
                                <option disabled selected>-- Select Protocol Target --</option>
                                <option>Emergency Ring Vaccination</option>
                                <option>Active Surveillance Herd Sampling</option>
                                <option>Quarantine Security Lockdown</option>
                                <option>Vector Eradication Sprays</option>
                            </select>
                        </div>
                    </div>

                    <div class="mt-4 pt-3 border-top d-flex justify-content-end gap-2">
                        <button type="button" data-bs-dismiss="modal" class="btn btn-secondary px-4">Discard Program</button>
                        <button type="submit" class="btn btn-success fw-bold px-4" disabled>Execute Protection Directive</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once '../../../includes/footer.php'; ?>