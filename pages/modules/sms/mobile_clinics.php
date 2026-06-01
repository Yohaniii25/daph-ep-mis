<?php
require_once '../../../includes/header.php';
if ($_SESSION['role'] !== 'sms') die("Access denied");

// Demo static mobile health clinic metrics data arrays
$clinics = [
    ['id' => 'MC-0412', 'region' => 'Ampara Veterinary Range', 'session_date' => '2026-06-05', 'livestock_type' => 'Cattle / Dairy', 'target_doses' => 250, 'status' => 'Scheduled'],
    ['id' => 'MC-0413', 'region' => 'Trincomalee Coastal Zone', 'session_date' => '2026-06-12', 'livestock_type' => 'Goats / Poultry', 'target_doses' => 180, 'status' => 'Pending Confirmation'],
    ['id' => 'MC-0414', 'region' => 'Samanthurai Farming Blocks', 'session_date' => '2026-05-28', 'livestock_type' => 'Buffalo / Cattle', 'target_doses' => 300, 'status' => 'Completed'],
    ['id' => 'MC-0415', 'region' => 'Kinniya Livestock Village', 'session_date' => '2026-06-18', 'livestock_type' => 'Dairy Livestock', 'target_doses' => 200, 'status' => 'Scheduled'],
];

$scheduled_count = count(array_filter($clinics, fn($c) => $c['status'] === 'Scheduled'));
$total_allocated_targets = array_sum(array_column($clinics, 'target_doses'));
?>

<?php require_once '../../../includes/sidebar.php'; ?>

<div id="layoutSidenav_content" class="bg-light">
    <main class="container-fluid px-4 pt-4">
        <h2 class="mb-4">Mobile Veterinary Clinical Emergency & Extension Ledger</h2>

        <div class="row g-4 mb-5">
            <div class="col-xl-3 col-md-6">
                <div class="card border-0 shadow-sm h-100 p-4 text-center border-start border-primary border-4">
                    <h6 class="text-muted small text-uppercase fw-bold">Total Operations Tracked</h6>
                    <h2 class="text-primary fw-bold mb-0"><?= count($clinics) ?></h2>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="card border-0 shadow-sm h-100 p-4 text-center border-start border-warning border-4">
                    <h6 class="text-muted small text-uppercase fw-bold">Active Scheduled Units</h6>
                    <h2 class="text-warning fw-bold mb-0"><?= $scheduled_count ?></h2>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="card border-0 shadow-sm h-100 p-4 text-center border-start border-success border-4">
                    <h6 class="text-muted small text-uppercase fw-bold">Target Dynamic Allocation</h6>
                    <h2 class="text-success fw-bold mb-0"><?= number_format($total_allocated_targets) ?> Head</h2>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="card border-0 shadow-sm h-100 p-4 text-center border-start border-info border-4">
                    <h6 class="text-muted small text-uppercase fw-bold">Surveillance Sync Integrity</h6>
                    <h2 class="text-info fw-bold mb-0">Active</h2>
                </div>
            </div>
        </div>

        <div class="card shadow-sm mb-4">
            <div class="card-header bg-light py-3">
                <h5 class="mb-0 fw-bold text-dark"><i class="bi bi-lightning-charge me-2"></i>Operational Control Center</h5>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-3">
                        <button class="btn btn-success w-100 py-3 fw-bold" data-bs-toggle="modal" data-bs-target="#addClinicModal">
                            <i class="bi bi-calendar-plus mb-2 d-inline-block fs-5"></i><br>
                            Schedule New Mobile Clinic
                        </button>
                    </div>

                </div>
            </div>
        </div>

        <div class="card shadow-sm border-0 rounded-3 mb-4">
            <div class="card-header bg-dark text-white py-3">
                <h5 class="mb-0 fw-semibold" style="color: white;"><i class="bi bi-truck me-2"></i>Active Regional Extension Clinical Log</h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-striped table-hover mb-0 align-middle">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-4">Operation ID</th>
                                <th>Target Regional Catchment</th>
                                <th>Scheduled Session Date</th>
                                <th>Dominant Livestock Classification</th>
                                <th class="text-center">Target Allocation Size</th>
                                <th class="pe-4 text-end">Clinical Deployment Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($clinics as $c): ?>
                            <tr>
                                <td class="ps-4 fw-bold text-secondary">#<?= $c['id'] ?></td>
                                <td><strong><?= htmlspecialchars($c['region']) ?></strong></td>
                                <td><span class="fw-semibold text-dark"><?= date('d M Y', strtotime($c['session_date'])) ?></span></td>
                                <td><span class="badge bg-light text-dark border"><?= htmlspecialchars($c['livestock_type']) ?></span></td>
                                <td class="text-center fw-bold text-primary"><?= number_format($c['target_doses']) ?> Doses</td>
                                <td class="pe-4 text-end">
                                    <span class="badge rounded-pill bg-<?php 
                                        echo $c['status'] === 'Completed' ? 'success' : ($c['status'] === 'Scheduled' ? 'info' : 'warning text-dark'); 
                                    ?> px-3 py-2">
                                        <?= htmlspecialchars($c['status']) ?>
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

<div class="modal fade" id="addClinicModal" tabindex="-1" aria-labelledby="addClinicModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-dark text-white py-3">
                <h5 class="modal-title" id="addClinicModalLabel" style="font-size: 17px; color: white;">
                    <i class="bi bi-calendar-plus me-2 text-success"></i>Initialize Field Deployment Sequence
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <form onsubmit="event.preventDefault();">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold text-secondary">Target Range Location / Village</label>
                            <input type="text" class="form-control" placeholder="e.g., Eastern Range Sector Block B">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold text-secondary">Target Deployment Date</label>
                            <input type="date" class="form-control" value="<?= date('Y-m-d') ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold text-secondary">Livestock Focus Profiles</label>
                            <select class="form-select text-darkfw-bold">
                                <option disabled selected>-- Choose Category --</option>
                                <option>Cattle / Dairy Farms</option>
                                <option>Goat Stock Breeding Lots</option>
                                <option>Poultry Commercial Ranges</option>
                                <option>Mixed General Herds</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold text-secondary">Projected Allocation Cap (Doses)</label>
                            <input type="number" class="form-control" placeholder="e.g., 250">
                        </div>
                    </div>

                    <div class="mt-4 pt-3 border-top d-flex justify-content-end gap-2">
                        <button type="button" data-bs-dismiss="modal" class="btn btn-secondary px-4">Discard</button>
                        <button type="submit" class="btn btn-success fw-bold px-4" disabled>Schedule Clinic</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once '../../../includes/footer.php'; ?>

```