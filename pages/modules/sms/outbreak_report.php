<?php
require_once '../../../includes/header.php';
if ($_SESSION['role'] !== 'sms') die("Access denied");

// Demo disease surveillance & epidemiological data maps
$outbreaks = [
    ['id' => 'OB-2026-001', 'disease' => 'Foot and Mouth Disease (FMD)', 'location' => 'Ampara Range - Sector 4', 'reported_date' => '2026-05-14', 'affected_animals' => 45, 'risk_level' => 'Critical', 'containment' => 'Quarantine Active'],
    ['id' => 'OB-2026-002', 'disease' => 'Brucellosis Suspect Case', 'location' => 'Trincomalee Dairy Blocks', 'reported_date' => '2026-05-22', 'affected_animals' => 12, 'risk_level' => 'High', 'containment' => 'Ring Vaccination Initiated'],
    ['id' => 'OB-2026-003', 'disease' => 'Rabies Exposure Vector', 'location' => 'Samanthurai Farming Perimeter', 'reported_date' => '2026-05-29', 'affected_animals' => 3, 'risk_level' => 'Medium', 'containment' => 'Monitoring / Isolation'],
    ['id' => 'OB-2026-004', 'disease' => 'Hemorrhagic Septicemia', 'location' => 'Kinniya Pasture Lands', 'reported_date' => '2026-05-31', 'affected_animals' => 18, 'risk_level' => 'High', 'containment' => 'Investigating Field Sample'],
];

$critical_alerts = count(array_filter($outbreaks, fn($o) => $o['risk_level'] === 'Critical' || $o['risk_level'] === 'High'));
$total_animals_tracked = array_sum(array_column($outbreaks, 'affected_animals'));
?>

<?php require_once '../../../includes/sidebar.php'; ?>

<div id="layoutSidenav_content" class="bg-light">
    <main class="container-fluid px-4 pt-4">
        <h2 class="mb-4">Epidemiological Disease Outbreak Tracking & Emergency Surveillance</h2>

        <div class="row g-4 mb-5">
            <div class="col-xl-3 col-md-6">
                <div class="card border-0 shadow-sm h-100 p-4 text-center border-start border-danger border-4">
                    <h6 class="text-muted small text-uppercase fw-bold">Active Outbreak Indices</h6>
                    <h2 class="text-danger fw-bold mb-0"><?= count($outbreaks) ?></h2>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="card border-0 shadow-sm h-100 p-4 text-center border-start border-warning border-4">
                    <h6 class="text-muted small text-uppercase fw-bold">High/Critical Risk Vectors</h6>
                    <h2 class="text-warning fw-bold mb-0"><?= $critical_alerts ?></h2>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="card border-0 shadow-sm h-100 p-4 text-center border-start border-primary border-4">
                    <h6 class="text-muted small text-uppercase fw-bold">Total Infected Head Count</h6>
                    <h2 class="text-primary fw-bold mb-0"><?= number_format($total_animals_tracked) ?> Animals</h2>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="card border-0 shadow-sm h-100 p-4 text-center border-start border-success border-4">
                    <h6 class="text-muted small text-uppercase fw-bold">Containment Protocol Integrity</h6>
                    <h2 class="text-success fw-bold mb-0">98.2%</h2>
                </div>
            </div>
        </div>

        <div class="card shadow-sm mb-4">
            <div class="card-header bg-light py-3">
                <h5 class="mb-0 fw-bold text-dark"><i class="bi bi-shield-exclamation me-2 text-danger"></i>Surveillance Response Measures</h5>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-3">
                        <button class="btn btn-danger w-100 py-3 fw-bold" data-bs-toggle="modal" data-bs-target="#addOutbreakModal">
                            <i class="bi bi-exclamation-triangle mb-2 d-inline-block fs-5"></i><br>
                            Log New Disease Outbreak Case
                        </button>
                    </div>

                </div>
            </div>
        </div>

        <div class="card shadow-sm border-0 rounded-3 mb-4">
            <div class="card-header bg-dark text-white py-3">
                <h5 class="mb-0 fw-semibold" style="color: white;"><i class="bi bi-activity me-2"></i>Active Regional Disease Containment & Outbreak Indexes</h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-striped table-hover mb-0 align-middle">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-4">Index Case Reference</th>
                                <th>Pathogen / Disease Profile</th>
                                <th>Epi-Center Location</th>
                                <th>Initial Diagnostic Date</th>
                                <th class="text-center">Active Infections Count</th>
                                <th>Risk Matrix Status</th>
                                <th class="pe-4 text-end">Containment Strategy Execution</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($outbreaks as $o): ?>
                            <tr>
                                <td class="ps-4 fw-bold text-secondary">#<?= $o['id'] ?></td>
                                <td><span class="text-danger fw-bold"><?= htmlspecialchars($o['disease']) ?></span></td>
                                <td><strong><?= htmlspecialchars($o['location']) ?></strong></td>
                                <td><span class="fw-semibold text-dark"><?= date('d M Y', strtotime($o['reported_date'])) ?></span></td>
                                <td class="text-center fw-bold text-dark"><?= number_format($o['affected_animals']) ?> Head</td>
                                <td>
                                    <span class="badge bg-<?php 
                                        echo $o['risk_level'] === 'Critical' ? 'danger' : ($o['risk_level'] === 'High' ? 'warning text-dark' : 'info'); 
                                    ?> px-2.5 py-1.5 small text-uppercase">
                                        <?= htmlspecialchars($o['risk_level']) ?>
                                    </span>
                                </td>
                                <td class="pe-4 text-end">
                                    <span class="badge bg-light text-dark border-start border-3 border-dark-subtle px-3 py-2 fw-semibold text-secondary">
                                        <i class="bi bi-shield-check text-success me-1"></i><?= htmlspecialchars($o['containment']) ?>
                                    </span>
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

<div class="modal fade" id="addOutbreakModal" tabindex="-1" aria-labelledby="addOutbreakModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-dark text-white py-3">
                <h5 class="modal-title" id="addOutbreakModalLabel" style="font-size: 17px; color: white;">
                    <i class="bi bi-exclamation-octagon me-2 text-danger"></i>File Urgent Pathological Outbreak Log Entry
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <form onsubmit="event.preventDefault();">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold text-secondary">Identified Disease Classification</label>
                            <input type="text" class="form-control shadow-sm" placeholder="e.g., Foot and Mouth Disease / Rabies Suspect">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold text-secondary">Reported Epi-Center Range Coordinates</label>
                            <input type="text" class="form-control shadow-sm" placeholder="e.g., Ampara Range Veterinary Block 05">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold text-secondary">Initial Diagnostic Date</label>
                            <input type="date" class="form-control shadow-sm" value="<?= date('Y-m-d') ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold text-secondary">Estimated Clinical Field Cases</label>
                            <input type="number" class="form-control shadow-sm" placeholder="e.g., 15">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold text-secondary">Epidemic Initial Risk Level Matrix</label>
                            <select class="form-select text-dark shadow-sm fw-bold">
                                <option disabled selected>-- Choose Risk Tier --</option>
                                <option class="text-danger fw-bold">Critical Outbreak Vector</option>
                                <option class="text-warning fw-bold">High System Risk</option>
                                <option class="text-primary">Medium Control Vector</option>
                                <option class="text-success">Low Containment Threat</option>
                            </select>
                        </div>
                    </div>

                    <div class="mt-4 pt-3 border-top d-flex justify-content-end gap-2">
                        <button type="button" data-bs-dismiss="modal" class="btn btn-secondary px-4">Discard Incident</button>
                        <button type="submit" class="btn btn-danger fw-bold px-4" disabled>Broadcast Outbreak Log</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once '../../../includes/footer.php'; ?>