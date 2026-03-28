<?php
session_start();
require_once '../../../config/db_connect.php';

if (!isset($_SESSION['logged_in']) || $_SESSION['role'] !== 'veterinary_surgeon') {
    header("Location: ../../../../index.php");
    exit();
}

$range_id = $_SESSION['range_id'] ?? null;
$range_name = $_SESSION['range_name'] ?? 'Your Range';

// 1. Fetch Human Resources (Staff)
$staff_stmt = $mysqli->prepare("SELECT * FROM office_details WHERE range_id = ? AND status = 'Active'");
$staff_stmt->bind_param("i", $range_id);
$staff_stmt->execute();
$staff_list = $staff_stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// 2. Fetch Immovable Assets
$immov_stmt = $mysqli->prepare("SELECT * FROM assets_immovable WHERE range_id = ?");
$immov_stmt->bind_param("i", $range_id);
$immov_stmt->execute();
$immovable_assets = $immov_stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// 3. Fetch Movable Assets
$mov_stmt = $mysqli->prepare("SELECT * FROM assets_movable WHERE range_id = ?");
$mov_stmt->bind_param("i", $range_id);
$mov_stmt->execute();
$movable_assets = $mov_stmt->get_result()->fetch_all(MYSQLI_ASSOC);

require_once '../../../includes/header.php';
require_once '../../../includes/sidebar.php';
?>

<div id="layoutSidenav_content">
    <main class="container-fluid px-4 pt-4">
        
        <div class="mb-4 d-flex justify-content-between align-items-center">
            <div>
                <h2 class="h4 fw-bold">Office Inventory & HR Registry</h2>
                <p class="text-muted small">Official records for <?= htmlspecialchars($range_name) ?> (View Only Mode)</p>
            </div>
            <?php if (isset($_SESSION['msg'])): ?>
                <div class="alert alert-<?= $_SESSION['msg_type'] ?> py-2 px-3 mb-0 small">
                    <?= $_SESSION['msg'] ?>
                </div>
                <?php unset($_SESSION['msg'], $_SESSION['msg_type']); ?>
            <?php endif; ?>
        </div>

        <div class="card shadow-sm border-0 mb-4">
            <div class="card-header bg-white py-3">
                <h5 class="mb-0 fw-bold small text-uppercase text-muted">Quick Actions</h5>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-4">
                        <button class="btn btn-primary w-100 py-3 shadow-sm border-0" data-bs-toggle="modal" data-bs-target="#addAssetModal">
                            <i class="bi bi-plus-circle fs-3"></i><br>
                            <strong>Register New Asset</strong>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <div class="card shadow-sm border-0 mb-5">
            <div class="card-header bg-white py-3 border-start border-primary border-4">
                <h5 class="mb-0 fw-bold text-primary"><i class="bi bi-people-fill me-2"></i>Human Resource Details</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="table-light small text-uppercase">
                            <tr>
                                <th>Emp ID</th>
                                <th>Officer Name</th>
                                <th>Designation</th>
                                <th>Contact Number</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($staff_list as $staff): ?>
                            <tr>
                                <td><span class="badge bg-secondary"><?= $staff['emp_id'] ?></span></td>
                                <td class="fw-bold"><?= htmlspecialchars($staff['officer_name']) ?></td>
                                <td><?= $staff['designation'] ?></td>
                                <td><?= $staff['contact_number'] ?? 'N/A' ?></td>
                                <td><span class="badge bg-success">Active</span></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="row g-4">
            <div class="col-lg-6">
                <div class="card shadow-sm border-0 h-100">
                    <div class="card-header bg-white py-3 border-start border-success border-4">
                        <h5 class="mb-0 fw-bold text-success"><i class="bi bi-house-door-fill me-2"></i>Immovable Assets</h5>
                    </div>
                    <div class="card-body">
                        <table class="table table-sm">
                            <tbody>
                                <?php foreach ($immovable_assets as $ia): ?>
                                <tr>
                                    <td><strong><?= htmlspecialchars($ia['asset_name']) ?></strong><br><small><?= $ia['location'] ?></small></td>
                                    <td class="text-end"><span class="badge bg-light text-dark"><?= $ia['extent'] ?></span></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="col-lg-6">
                <div class="card shadow-sm border-0 h-100">
                    <div class="card-header bg-white py-3 border-start border-info border-4">
                        <h5 class="mb-0 fw-bold text-info"><i class="bi bi-truck me-2"></i>Movable Assets</h5>
                    </div>
                    <div class="card-body">
                        <table class="table table-sm">
                            <tbody>
                                <?php foreach ($movable_assets as $ma): ?>
                                <tr>
                                    <td><strong><?= htmlspecialchars($ma['item_name']) ?></strong><br><small><?= $ma['serial_no'] ?></small></td>
                                    <td class="text-end"><span class="badge bg-light text-dark border"><?= $ma['condition'] ?></span></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <?php include 'models/asset_modals.php'; ?>

    </main>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const selector = document.getElementById('assetTypeSelector');
    if(selector) {
        selector.addEventListener('change', function() {
            const type = this.value;
            const immovFields = document.getElementById('immovableFields');
            const movFields = document.getElementById('movableFields');
            const header = document.getElementById('modalHeaderColor');
            const btn = document.getElementById('submitBtn');

            // Toggle Visibility
            immovFields.style.display = (type === 'immovable') ? 'block' : 'none';
            movFields.style.display = (type === 'movable') ? 'block' : 'none';
            btn.disabled = false;

            // Change Colors
            if(type === 'immovable') {
                header.className = 'modal-header bg-success text-white';
                btn.className = 'btn btn-success w-100';
            } else {
                header.className = 'modal-header bg-info text-white';
                btn.className = 'btn btn-info text-white w-100';
            }
        });
    }
});
</script>

<?php require_once '../../../includes/footer.php'; ?>