<?php
require_once '../../../includes/header.php';
if ($_SESSION['role'] !== 'veterinary_surgeon') die("Access denied");

// Demo registered farmers
$farmers = [
    ['id' => 'F001', 'name' => 'Mr. Silva', 'nic' => '198512345678', 'contact' => '071-2345678', 'farm_type' => 'Dairy', 'animals' => 25, 'registered' => '2024-05-10'],
    ['id' => 'F002', 'name' => 'Ms. Perera', 'nic' => '199012345678', 'contact' => '077-3456789', 'farm_type' => 'Poultry', 'animals' => 500, 'registered' => '2023-11-15'],
    ['id' => 'F003', 'name' => 'Mr. Fernando', 'nic' => '197812345678', 'contact' => '076-4567890', 'farm_type' => 'Mixed', 'animals' => 40, 'registered' => '2025-01-05'],
];
?>

<?php require_once '../../../includes/sidebar.php'; ?>

<div id="layoutSidenav_content">
    <main class="container-fluid px-4 pt-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="mb-0">Farmer Registration</h2>
            <button  style="font-size: 16px;" class="btn btn-success btn-lg" data-bs-toggle="modal" data-bs-target="#registerFarmerModal">
                <i class="bi bi-person-plus me-2"></i>Register New Farmer
            </button>
        </div>

        <!-- Quick Stats -->
        <div class="row g-4 mb-5">
            <div class="col-xl-3 col-md-6">
                <div class="card border-0 shadow-sm h-100 p-4 text-center">
                    <h6 class="text-muted">Total Registered Farmers</h6>
                    <h2 class="text-primary"><?= count($farmers) ?></h2>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="card border-0 shadow-sm h-100 p-4 text-center">
                    <h6 class="text-muted">Dairy Farmers</h6>
                    <h2 class="text-info">1</h2>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="card border-0 shadow-sm h-100 p-4 text-center">
                    <h6 class="text-muted">Poultry Farmers</h6>
                    <h2 class="text-success">1</h2>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="card border-0 shadow-sm h-100 p-4 text-center">
                    <h6 class="text-muted">New This Month</h6>
                    <h2 class="text-warning">1</h2>
                </div>
            </div>
        </div>

        <!-- Farmer List Table -->
        <div class="card shadow-sm">
            <div class="card-header bg-dark text-white">
                <h5 style="color: white;" class="mb-0">Registered Farmers</h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Farmer ID</th>
                                <th>Name</th>
                                <th>NIC</th>
                                <th>Contact</th>
                                <th>Farm Type</th>
                                <th>Animals</th>
                                <th>Registered Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($farmers as $f): ?>
                            <tr>
                                <td><strong><?= $f['id'] ?></strong></td>
                                <td><?= htmlspecialchars($f['name']) ?></td>
                                <td><?= $f['nic'] ?></td>
                                <td><?= $f['contact'] ?></td>
                                <td><?= $f['farm_type'] ?></td>
                                <td><?= $f['animals'] ?></td>
                                <td><?= date('d M Y', strtotime($f['registered'])) ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>
</div>

<!-- Register New Farmer Modal -->
<div class="modal fade" id="registerFarmerModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title"><i class="bi bi-person-plus me-2"></i>Register New Farmer</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Full Name</label>
                            <input type="text" class="form-control" placeholder="e.g., Mr. Silva">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">NIC Number</label>
                            <input type="text" class="form-control" placeholder="e.g., 198512345678">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Contact Number</label>
                            <input type="text" class="form-control" placeholder="e.g., 071-2345678">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Farm Type</label>
                            <select class="form-select">
                                <option>Dairy</option>
                                <option>Poultry</option>
                                <option>Mixed</option>
                                <option>Other</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Number of Animals</label>
                            <input type="number" class="form-control" placeholder="e.g., 25">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Registration Date</label>
                            <input type="date" class="form-control" value="<?= date('Y-m-d') ?>">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Address</label>
                            <textarea class="form-control" rows="3" placeholder="Full farm address"></textarea>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-success" disabled>Register Farmer</button>
            </div>
        </div>
    </div>
</div>

<?php require_once '../../../includes/footer.php'; ?>