<?php
require_once '../../../includes/header.php';
if ($_SESSION['role'] !== 'finance_admin') die("Access denied");

// Demo data
$stores = [
    ['item' => 'FMD Vaccine', 'stock' => 8500, 'min_level' => 5000, 'status' => 'Normal', 'category' => 'Vaccines', 'unit' => 'Doses'],
    ['item' => 'Rabies Vaccine', 'stock' => 3200, 'min_level' => 4000, 'status' => 'Low', 'category' => 'Vaccines', 'unit' => 'Doses'],
    ['item' => 'Antibiotics (Various)', 'stock' => 12000, 'min_level' => 8000, 'status' => 'Normal', 'category' => 'Medicines', 'unit' => 'Units'],
    ['item' => 'Dewormers', 'stock' => 4500, 'min_level' => 6000, 'status' => 'Low', 'category' => 'Medicines', 'unit' => 'Units'],
    ['item' => 'Surgical Instruments', 'stock' => 180, 'min_level' => 200, 'status' => 'Low', 'category' => 'Equipment', 'unit' => 'Pieces'],
];
$low_stock_count = count(array_filter($stores, fn($s) => $s['status'] === 'Low'));
$total_value = 15750000; // Demo value in LKR
?>

<style>
    .stat-card {
        transition: transform 0.3s ease, box-shadow 0.3s ease;
        border-radius: 12px;
        overflow: hidden;
    }

    .stat-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15) !important;
    }

    .stores-table-wrapper {
        background: #fff;
        border-radius: 12px;
        overflow: hidden;
    }

    .table-header-custom {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        font-weight: 600;
        text-transform: uppercase;
        font-size: 0.85rem;
        letter-spacing: 0.5px;
    }

    .status-badge-modern {
        padding: 6px 14px;
        border-radius: 20px;
        font-size: 0.8rem;
        font-weight: 600;
        display: inline-flex;
        align-items: center;
        gap: 6px;
    }

    .stock-progress {
        height: 8px;
        border-radius: 4px;
        background-color: #e9ecef;
        overflow: hidden;
        margin-top: 8px;
    }

    .stock-progress-bar {
        height: 100%;
        border-radius: 4px;
        transition: width 0.6s ease;
    }

    .action-btn {
        padding: 5px 12px;
        font-size: 0.85rem;
        border-radius: 6px;
        transition: all 0.2s;
    }

    .action-btn:hover {
        transform: scale(1.05);
    }

    .category-icon {
        width: 40px;
        height: 40px;
        border-radius: 8px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.2rem;
    }

    .gradient-stat-card {
        background: linear-gradient(135deg, var(--gradient-start), var(--gradient-end));
        color: white;
        border: none;
    }
</style>

<?php require_once '../../../includes/sidebar.php'; ?>

<div id="layoutSidenav_content">
    <main class="container-fluid px-4 pt-4" style="background-color: #f8f9fa;">

        <!-- Page Header -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h1 class="mb-2 text-dark fw-bold">
                    Veterinary Stores Management
                </h1>
                <p class="text-muted mb-0">
                    Real-time Stock Monitoring & Inventory Control
                </p>
            </div>
        </div>

        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <button class="btn btn-primary me-2">
                    <i class="bi bi-plus-circle me-2"></i>Add New Item
                </button>
                <button class="btn btn-outline-secondary me-2">
                    <i class="bi bi-file-earmark-pdf me-2"></i>Generate Report
                </button>
                <button class="btn btn-outline-warning">
                    <i class="bi bi-exclamation-triangle me-2"></i>View Alerts
                </button>
            </div>
        </div>

        <!-- Statistics Cards -->
        <div class="row g-4 mb-4">
            <div class="col-xl-3 col-md-6">
                <div class="card border-0 shadow-sm stat-card gradient-stat-card">
                    <div class="card-body p-4">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <p class="mb-2 text-uppercase opacity-75" style="font-size: 0.75rem; font-weight: 600; letter-spacing: 1px; color: black;">Total Items</p>
                                <h2 class="mb-0 fw-bold text-black"><?= count($stores) ?></h2>
                                <small class="opacity-75 text-black">Active inventory items</small>
                            </div>

                        </div>
                    </div>
                </div>
            </div>

            <div class="col-xl-3 col-md-6">
                <div class="card border-0 shadow-sm stat-card gradient-stat-card" >
                    <div class="card-body p-4">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <p class="mb-2 text-uppercase opacity-75" style="font-size: 0.75rem; font-weight: 600; letter-spacing: 1px; color: black;">Normal Stock</p>
                                <h2 class="mb-0 fw-bold text-black"><?= count($stores) - $low_stock_count ?></h2>
                                <small class="opacity-75 text-black">Adequate levels</small>
                            </div>

                        </div>
                    </div>
                </div>
            </div>

            <div class="col-xl-3 col-md-6">
                <div class="card border-0 shadow-sm stat-card gradient-stat-card">
                    <div class="card-body p-4">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <p class="mb-2 text-uppercase opacity-75" style="font-size: 0.75rem; font-weight: 600; letter-spacing: 1px; color: black;">Low Stock Alert</p>
                                <h2 class="mb-0 fw-bold text-black"><?= $low_stock_count ?></h2>
                                <small class="opacity-75 text-black">Requires attention</small>
                            </div>

                        </div>
                    </div>
                </div>
            </div>

            <div class="col-xl-3 col-md-6">
                <div class="card border-0 shadow-sm stat-card">
                    <div class="card-body p-4">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <p class="text-muted mb-2 text-uppercase" style="font-size: 0.75rem; font-weight: 600; letter-spacing: 1px;">Total Value</p>
                                <h2 class="mb-0 fw-bold" style="color: #2c3e50;">LKR <?= number_format($total_value / 1000000, 1) ?>M</h2>
                                <small class="text-muted">Inventory worth</small>
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
                    <div class="col-md-4">
                        <input type="text" class="form-control" placeholder="Search items...">
                    </div>
                    <div class="col-md-2">
                        <select class="form-select">
                            <option>All Categories</option>
                            <option>Vaccines</option>
                            <option>Medicines</option>
                            <option>Equipment</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <select class="form-select">
                            <option>All Status</option>
                            <option>Normal</option>
                            <option>Low</option>
                            <option>Critical</option>
                        </select>
                    </div>

                    <div class="col-md-2">
                        <button class="btn btn-outline-primary w-100">
                            <i class="bi bi-funnel me-2"></i>Apply
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Stock Table -->
        <div class="card border-0 shadow-sm stores-table-wrapper">
            <div class="card-header table-header-custom py-3">
                <div class="d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="bi bi-archive me-2"></i>Current Stock Levels</h5>
                    <span class="badge bg-light text-dark"><?= count($stores) ?> Items</span>
                </div>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead style="background-color: #f8f9fa;">
                            <tr>
                                <th class="py-3 px-4" style="font-weight: 600; color: #495057;">Item Details</th>
                                <th class="py-3" style="font-weight: 600; color: #495057;">Category</th>
                                <th class="py-3 text-end" style="font-weight: 600; color: #495057;">Current Stock</th>
                                <th class="py-3 text-end" style="font-weight: 600; color: #495057;">Minimum Level</th>

                                <th class="py-3" style="font-weight: 600; color: #495057;">Status</th>
                                <th class="py-3 text-center" style="font-weight: 600; color: #495057;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($stores as $item):
                                $stock_percentage = ($item['stock'] / $item['min_level']) * 100;
                                $bar_color = $stock_percentage >= 100 ? 'success' : ($stock_percentage >= 80 ? 'warning' : 'danger');
                            ?>
                                <tr style="border-bottom: 1px solid #e9ecef;">
                                    <td class="px-4">
                                        <div class="d-flex align-items-center">
                  
                                            <div>
                                                <div class="fw-semibold text-dark"><?= htmlspecialchars($item['item']) ?></div>
                                                <small class="text-muted">Unit: <?= $item['unit'] ?></small>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="badge bg-light text-dark"><?= $item['category'] ?></span>
                                    </td>
                                    <td class="text-end">
                                        <span class="fw-bold" style="color: #2c3e50;"><?= number_format($item['stock']) ?></span>
                                    </td>
                                    <td class="text-end">
                                        <span class="text-muted"><?= number_format($item['min_level']) ?></span>
                                    </td>

                                    <td>
                                        <?php
                                        $statusConfig = [
                                            'Normal' => ['color' => 'success', 'icon' => 'check-circle-fill'],
                                            'Low' => ['color' => 'warning', 'icon' => 'exclamation-triangle-fill'],
                                            'Critical' => ['color' => 'danger', 'icon' => 'x-circle-fill']
                                        ];
                                        $config = $statusConfig[$item['status']] ?? ['color' => 'secondary', 'icon' => 'circle-fill'];
                                        ?>
                                        <span class="status-badge-modern bg-<?= $config['color'] ?> bg-opacity-10 text-<?= $config['color'] ?>">
                                            <i class="bi bi-<?= $config['icon'] ?>"></i>
                                            <?= $item['status'] ?>
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        <button class="btn btn-sm btn-outline-primary action-btn me-1" title="View Details">
                                            <i class="bi bi-eye"></i>
                                        </button>
                                        <button class="btn btn-sm btn-outline-secondary action-btn me-1" title="Edit">
                                            <i class="bi bi-pencil"></i>
                                        </button>
                                        <button class="btn btn-sm btn-outline-success action-btn" title="Restock">
                                            <i class="bi bi-box-arrow-in-down"></i>
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="card-footer bg-light py-3">
                <div class="d-flex justify-content-between align-items-center">
                    <small class="text-muted">
                        <i class="bi bi-info-circle me-1"></i>Last updated: <?= date('F j, Y - g:i A') ?>
                    </small>
                    <small class="text-muted">
                        <i class="bi bi-clock me-1"></i>System Status: Active
                    </small>
                </div>
            </div>
        </div>

        <!-- Pagination -->
        <div class="d-flex justify-content-between align-items-center mt-4 mb-5">
            <p class="text-muted mb-0">Showing 1 to <?= count($stores) ?> of <?= count($stores) ?> entries</p>
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

<?php require_once '../../../includes/footer.php'; ?>