<?php
require_once '../../../includes/header.php';
if ($_SESSION['role'] !== 'finance_admin') die("Access denied");

// Demo data - numeric values
$assets = [
    ['id' => 'AST-001', 'description' => 'Toyota Hilux Double Cab', 'category' => 'Vehicle', 'value' => 8500000, 'purchase_date' => '2025-01-15', 'status' => 'Active', 'depreciation' => 15],
    ['id' => 'AST-002', 'description' => 'Desktop Computers (10 units)', 'category' => 'IT Equipment', 'value' => 2500000, 'purchase_date' => '2025-02-20', 'status' => 'Active', 'depreciation' => 25],
    ['id' => 'AST-003', 'description' => 'Office Furniture Set', 'category' => 'Furniture', 'value' => 1200000, 'purchase_date' => '2025-03-10', 'status' => 'Active', 'depreciation' => 10],
    ['id' => 'AST-004', 'description' => 'Laboratory Equipment', 'category' => 'Medical', 'value' => 3500000, 'purchase_date' => '2025-04-05', 'status' => 'Active', 'depreciation' => 20],
];

$total_value = array_sum(array_column($assets, 'value'));
$categories = array_unique(array_column($assets, 'category'));
?>

<style>
    .stat-card {
        transition: transform 0.3s ease, box-shadow 0.3s ease;
    }

    .stat-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15) !important;
    }

    .asset-table-wrapper {
        background: #fff;
        border-radius: 12px;
        overflow: hidden;
    }

    .badge-custom {
        padding: 6px 12px;
        font-weight: 500;
        letter-spacing: 0.3px;
    }

    .table-header-custom {
        background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);
        color: white;
        font-weight: 600;
        text-transform: uppercase;
        font-size: 0.85rem;
        letter-spacing: 0.5px;
    }

    .action-btn {
        padding: 4px 10px;
        font-size: 0.85rem;
        border-radius: 6px;
        transition: all 0.2s;
    }

    .action-btn:hover {
        transform: scale(1.05);
    }

    .currency-badge {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        padding: 8px 16px;
        border-radius: 20px;
        font-weight: 600;
        display: inline-block;
        margin-bottom: 20px;
    }

    input.form-control.form-control-lg.bg-light {
        font-size: 18px !important;
    }
</style>

<?php require_once '../../../includes/sidebar.php'; ?>

<div id="layoutSidenav_content">
    <main class="container-fluid px-4 pt-4" style="background-color: #f8f9fa;">

        <!-- Page Header -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h1 class="mb-2 text-dark fw-bold">

                    </i>Assets Management
                </h1>
                <p class="text-muted mb-0">Track and manage organizational assets</p>
            </div>
            <div>
                <button class="btn btn-primary me-2" data-bs-toggle="modal" data-bs-target="#addAssetModal">
                    <i class="bi bi-plus-circle me-2"></i>Add Asset
                </button>
                <button class="btn btn-outline-secondary">
                    <i class="bi bi-download me-2"></i>Export
                </button>
            </div>
        </div>

        <!-- Stats Cards -->
        <div class="row g-3 mb-3">
            <div class="col-xl-4 col-md-4">
                <div class="card border-0 shadow-sm stat-card">
                    <div class="card-body p-4">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <p class="text-muted mb-2 text-uppercase" style="font-size: 0.75rem; font-weight: 600; letter-spacing: 1px;">Total Assets</p>
                                <h2 class="mb-0 fw-bold" style="color: #2c3e50;"><?= count($assets) ?></h2>
                                <small class="text-success"><i class="bi bi-arrow-up"></i> Active items</small>
                            </div>

                        </div>
                    </div>
                </div>
            </div>

            <div class="col-xl-4 col-md-4">
                <div class="card border-0 shadow-sm stat-card">
                    <div class="card-body p-4">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <p class="text-muted mb-2 text-uppercase" style="font-size: 0.75rem; font-weight: 600; letter-spacing: 1px;">Total Value</p>
                                <h2 class="mb-0 fw-bold" style="color: #2c3e50;">LKR <?= number_format($total_value / 1000000, 2) ?>M</h2>
                                <small class="text-muted">Sri Lankan Rupees</small>
                            </div>

                        </div>
                    </div>
                </div>
            </div>

            <div class="col-xl-4 col-md-4">
                <div class="card border-0 shadow-sm stat-card">
                    <div class="card-body p-4">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <p class="text-muted mb-2 text-uppercase" style="font-size: 0.75rem; font-weight: 600; letter-spacing: 1px;">Active Status</p>
                                <h2 class="mb-0 fw-bold" style="color: #2c3e50;">100%</h2>
                                <small class="text-success"><i class="bi bi-check-circle"></i> All operational</small>
                            </div>

                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filters -->
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-3">
                        <input type="text" class="form-control" placeholder="Search assets...">
                    </div>
                    <div class="col-md-3">
                        <select class="form-select">
                            <option>All Categories</option>
                            <option>Vehicle</option>
                            <option>IT Equipment</option>
                            <option>Furniture</option>
                            <option>Medical</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <select class="form-select">
                            <option>All Status</option>
                            <option>Active</option>
                            <option>Inactive</option>
                            <option>Under Maintenance</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <button class="btn btn-outline-primary w-100">
                            <i class="bi bi-funnel me-2"></i>Apply Filters
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Asset Table -->
        <div class="card border-0 shadow-sm asset-table-wrapper">
            <div class="card-header table-header-custom py-3">
                <div class="d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="bi bi-table me-2"></i>Asset Register</h5>
                    <span class="badge bg-light text-dark"><?= count($assets) ?> Assets</span>
                </div>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead style="background-color: #f8f9fa;">
                            <tr>
                                <th class="py-3 px-4" style="font-weight: 600; color: #495057;">Asset ID</th>
                                <th class="py-3" style="font-weight: 600; color: #495057;">Description</th>
                                <th class="py-3" style="font-weight: 600; color: #495057;">Category</th>
                                <th class="py-3 text-end" style="font-weight: 600; color: #495057;">Value (LKR)</th>
                                <th class="py-3" style="font-weight: 600; color: #495057;">Purchase Date</th>
                                <th class="py-3" style="font-weight: 600; color: #495057;">Status</th>
                                <th class="py-3 text-center" style="font-weight: 600; color: #495057;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($assets as $asset): ?>
                                <tr style="border-bottom: 1px solid #e9ecef;">
                                    <td class="px-4">
                                        <span class="badge badge-custom" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white;">
                                            <?= $asset['id'] ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="fw-semibold text-dark"><?= htmlspecialchars($asset['description']) ?></div>
                                    </td>
                                    <td>
                                        <span class="badge badge-custom bg-light text-dark border">
                                            <i class="bi bi-tag-fill me-1"></i><?= $asset['category'] ?>
                                        </span>
                                    </td>
                                    <td class="text-end">
                                        <span class="fw-bold" style="color: #2c3e50;">Rs. <?= number_format($asset['value']) ?></span>
                                    </td>
                                    <td>
                                        <span class="text-muted">
                                            <i class="bi bi-calendar3 me-1"></i><?= date('d M Y', strtotime($asset['purchase_date'])) ?>
                                        </span>
                                    </td>

                                    <td>
                                        <span class="badge badge-custom bg-success">
                                            <i class="bi bi-check-circle-fill me-1"></i><?= $asset['status'] ?>
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        <button class="btn btn-sm btn-outline-primary action-btn me-1" title="View Details">
                                            <i class="bi bi-eye"></i>
                                        </button>
                                        <button class="btn btn-sm btn-outline-secondary action-btn me-1" title="Edit">
                                            <i class="bi bi-pencil"></i>
                                        </button>
                                        <button class="btn btn-sm btn-outline-danger action-btn" title="Delete">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot style="background-color: #f8f9fa;">
                            <tr>
                                <td colspan="3" class="px-4 py-3 fw-bold text-dark">TOTAL ASSET VALUE</td>
                                <td class="text-end py-3 fw-bold" style="color: #667eea; font-size: 1.1rem;">
                                    Rs. <?= number_format($total_value) ?>
                                </td>
                                <td colspan="4"></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>

        <!-- Pagination -->
        <div class="d-flex justify-content-between align-items-center mt-4 mb-5">
            <p class="text-muted mb-0">Showing 1 to <?= count($assets) ?> of <?= count($assets) ?> entries</p>
            <nav>
                <ul class="pagination mb-0">
                    <li class="page-item disabled"><a class="page-link" href="#">Previous</a></li>
                    <li class="page-item active"><a class="page-link" href="#">1</a></li>
                    <li class="page-item"><a class="page-link" href="#">2</a></li>
                    <li class="page-item"><a class="page-link" href="#">3</a></li>
                    <li class="page-item"><a class="page-link" href="#">Next</a></li>
                </ul>
            </nav>
        </div>

    </main>
</div>

<!-- Add Asset Modal -->
<div class="modal fade" id="addAssetModal" tabindex="-1" aria-labelledby="addAssetModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-gradient text-white" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                <h5 class="modal-title" id="addAssetModalLabel" style="font-size: 17px;">
                    <i class="bi bi-plus-circle me-2"></i>Add New Asset
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">

                <form>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Asset ID</label>
                            <input type="text" class="form-control" value="Auto-generated" readonly>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Purchase Date</label>
                            <input type="date" class="form-control" value="<?= date('Y-m-d') ?>">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Description</label>
                            <input type="text" class="form-control" placeholder="e.g., Toyota Hilux Double Cab">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Category</label>
                            <select class="form-select">
                                <option selected>Select category</option>
                                <option>Vehicle</option>
                                <option>IT Equipment</option>
                                <option>Furniture</option>
                                <option>Medical</option>
                                <option>Other</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Value (LKR)</label>
                            <input type="number" class="form-control" placeholder="8500000">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Status</label>
                            <select class="form-select">
                                <option selected>Active</option>
                                <option>Inactive</option>
                                <option>Under Maintenance</option>
                            </select>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer border-0">
                <button type="button" class="btn btn-secondary px-4" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-success px-4" disabled>Save Asset</button>
            </div>
        </div>
    </div>
</div>

<?php require_once '../../../includes/footer.php'; ?>