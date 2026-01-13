<?php
require_once '../../../includes/header.php';
if ($_SESSION['role'] !== 'veterinary_surgeon') die("Access denied");

// Demo stock
$stock = [
    ['vaccine' => 'FMD', 'quantity' => 1200, 'expiry' => '2026-06-30', 'status' => 'Sufficient'],
    ['vaccine' => 'Rabies', 'quantity' => 450, 'expiry' => '2026-04-15', 'status' => 'Low'],
    ['vaccine' => 'Brucellosis', 'quantity' => 800, 'expiry' => '2026-07-20', 'status' => 'Sufficient'],
];
?>

<?php require_once '../../../includes/sidebar.php'; ?>

<div id="layoutSidenav_content">
    <main class="container-fluid px-4 pt-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="mb-0">Vaccine Stock Management</h2>
            <button style="font-size: 16px;" class="btn btn-success btn-lg" data-bs-toggle="modal" data-bs-target="#orderVaccineModal">
                <i class="bi bi-truck me-2"></i>Order Vaccine
            </button>
        </div>

        <div class="card shadow-sm">
            <div class="card-header bg-dark text-white">
                <h5 style="color: white;" class="mb-0">Current Vaccine Stock</h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Vaccine</th>
                                <th>Quantity Available</th>
                                <th>Expiry Date</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($stock as $s): ?>
                            <tr>
                                <td><strong><?= htmlspecialchars($s['vaccine']) ?></strong></td>
                                <td><?= number_format($s['quantity']) ?></td>
                                <td><?= date('d M Y', strtotime($s['expiry'])) ?></td>
                                <td>
                                    <span class="badge bg-<?= $s['status'] === 'Sufficient' ? 'success' : 'warning' ?>">
                                        <?= $s['status'] ?>
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

<!-- Order Vaccine Modal -->
<div class="modal fade" id="orderVaccineModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title"><i class="bi bi-truck me-2"></i>Order Vaccine</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-info text-center mb-4">
                    <i class="bi bi-info-circle me-2"></i>Demo Mode - Vaccine order in Phase 2
                </div>
                <form>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Vaccine Type</label>
                            <select class="form-select">
                                <option>FMD</option>
                                <option>Rabies</option>
                                <option>Brucellosis</option>
                                <option>Other</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Quantity Needed</label>
                            <input type="number" class="form-control" placeholder="e.g., 1000">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Reason / Notes</label>
                            <textarea class="form-control" rows="3" placeholder="e.g., Upcoming campaign"></textarea>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-success" disabled>Submit Order</button>
            </div>
        </div>
    </div>
</div>

<?php require_once '../../../includes/footer.php'; ?>