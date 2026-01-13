<?php
require_once '../../../includes/header.php';
if ($_SESSION['role'] !== 'veterinary_surgeon') die("Access denied");

// Demo campaigns
$campaigns = [
    ['date' => '2026-01-15', 'vaccine' => 'FMD', 'target_animals' => 500, 'location' => 'Amparai', 'status' => 'Scheduled'],
    ['date' => '2026-01-10', 'vaccine' => 'Rabies', 'target_animals' => 200, 'location' => 'Karaitivu', 'status' => 'Completed'],
    ['date' => '2026-01-05', 'vaccine' => 'Brucellosis', 'target_animals' => 300, 'location' => 'Sainthamaruthu', 'status' => 'Ongoing'],
];
?>

<?php require_once '../../../includes/sidebar.php'; ?>

<div id="layoutSidenav_content">
    <main class="container-fluid px-4 pt-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="mb-0">Immunization Campaigns</h2>
            <button style="font-size: 16px;" class="btn btn-success btn-lg" data-bs-toggle="modal" data-bs-target="#addCampaignModal">
                <i class="bi bi-plus-circle me-2"></i>Schedule New Campaign
            </button>
        </div>

        <div class="card shadow-sm">
            <div class="card-header bg-dark text-white">
                <h5 style="color: white;" class="mb-0">Scheduled Campaigns</h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Date</th>
                                <th>Vaccine</th>
                                <th>Target Animals</th>
                                <th>Location</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($campaigns as $c): ?>
                            <tr>
                                <td><?= date('d M Y', strtotime($c['date'])) ?></td>
                                <td><strong><?= $c['vaccine'] ?></strong></td>
                                <td><?= number_format($c['target_animals']) ?></td>
                                <td><?= htmlspecialchars($c['location']) ?></td>
                                <td>
                                    <span class="badge bg-<?= $c['status'] === 'Completed' ? 'success' : ($c['status'] === 'Ongoing' ? 'warning' : 'info') ?>">
                                        <?= $c['status'] ?>
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

<!-- Add Campaign Modal -->
<div class="modal fade" id="addCampaignModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title"><i class="bi bi-plus-circle me-2"></i>Schedule New Campaign</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">

                <form>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Campaign Date</label>
                            <input type="date" class="form-control">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Vaccine</label>
                            <select class="form-select">
                                <option>FMD</option>
                                <option>Rabies</option>
                                <option>Brucellosis</option>
                                <option>Other</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Target Animals</label>
                            <input type="number" class="form-control" placeholder="e.g., 500">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Location</label>
                            <input type="text" class="form-control" placeholder="e.g., Amparai Division">
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-success" disabled>Save Campaign</button>
            </div>
        </div>
    </div>
</div>

<?php require_once '../../../includes/footer.php'; ?>