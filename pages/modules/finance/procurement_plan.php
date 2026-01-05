<?php
require_once '../../../includes/header.php';
if ($_SESSION['role'] !== 'finance_admin') die("Access denied");

// Demo data - numeric
$procurements = [
    ['item' => 'Veterinary Vaccines', 'quantity' => '10,000 doses', 'estimated_cost' => 5000000, 'status' => 'Planned', 'supplier' => 'MedVet Suppliers', 'priority' => 'High'],
    ['item' => 'Poultry Feed', 'quantity' => '200 tons', 'estimated_cost' => 12000000, 'status' => 'In Progress',  'supplier' => 'Feed Solutions Ltd', 'priority' => 'High'],
    ['item' => 'Medical Equipment', 'quantity' => 'Various', 'estimated_cost' => 8000000, 'status' => 'Approved',  'supplier' => 'Healthcare Supplies Co', 'priority' => 'Medium'],
    ['item' => 'Office Renovation', 'quantity' => '1 project', 'estimated_cost' => 15000000, 'status' => 'Planned', 'supplier' => 'BuildPro Contractors', 'priority' => 'Low'],
];

$total_cost = array_sum(array_column($procurements, 'estimated_cost'));
$status_counts = array_count_values(array_column($procurements, 'status'));
$approved_count = $status_counts['Approved'] ?? 0;
$in_progress_count = $status_counts['In Progress'] ?? 0;
?>

<style>
.stat-card {
    transition: transform 0.3s ease, box-shadow 0.3s ease;
}
.stat-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 10px 30px rgba(0,0,0,0.15) !important;
}
.procurement-table-wrapper {
    background: #fff;
    border-radius: 12px;
    overflow: hidden;
}
.badge-custom {
    padding: 6px 14px;
    font-weight: 500;
    letter-spacing: 0.3px;
    border-radius: 6px;
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
    padding: 5px 12px;
    font-size: 0.85rem;
    border-radius: 6px;
    transition: all 0.2s;
}
.action-btn:hover {
    transform: scale(1.05);
}
.priority-badge-high {
    background: linear-gradient(135deg, #ff6b6b 0%, #ee5a6f 100%);
    color: white;
}
.priority-badge-medium {
    background: linear-gradient(135deg, #ffa502 0%, #ff7f50 100%);
    color: white;
}
.priority-badge-low {
    background: linear-gradient(135deg, #95a5a6 0%, #7f8c8d 100%);
    color: white;
}
.timeline-badge {
    position: relative;
    padding-left: 25px;
}
.timeline-badge::before {
    content: '';
    position: absolute;
    left: 8px;
    top: 50%;
    transform: translateY(-50%);
    width: 8px;
    height: 8px;
    border-radius: 50%;
    background: currentColor;
}
</style>

<?php require_once '../../../includes/sidebar.php'; ?>

<div id="layoutSidenav_content">
    <main class="container-fluid px-4 pt-4" style="background-color: #f8f9fa;">
        
        <!-- Page Header -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2 class="mb-2 text-dark fw-bold">
                    </i>Procurement Planning & Management
                </h1>
                <p class="text-muted mb-0">Strategic procurement planning for 2026</p>
            </div>

        </div>
        <div class="d-flex justify-content-between align-items-center mb-4">

            <div>
                <button class="btn btn-primary me-2">
                    <i class="bi bi-plus-circle me-2"></i>New Procurement
                </button>
                <button class="btn btn-outline-secondary me-2">
                    <i class="bi bi-file-earmark-excel me-2"></i>Export
                </button>
                <button class="btn btn-outline-info">
                    <i class="bi bi-calendar-check me-2"></i>View Calendar
                </button>
            </div>
        </div>


        <!-- Stats Cards -->
        <div class="row g-4 mb-4">
            <div class="col-xl-3 col-md-6">
                <div class="card border-0 shadow-sm stat-card">
                    <div class="card-body p-4">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <p class="text-muted mb-2 text-uppercase" style="font-size: 0.75rem; font-weight: 600; letter-spacing: 1px;">Total Items</p>
                                <h2 class="mb-0 fw-bold" style="color: #2c3e50;"><?= count($procurements) ?></h2>
                                <small class="text-success"><i class="bi bi-graph-up"></i> Planned for 2026</small>
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
                                <p class="text-muted mb-2 text-uppercase" style="font-size: 0.75rem; font-weight: 600; letter-spacing: 1px;">Total Budget</p>
                                <h2 class="mb-0 fw-bold" style="color: #2c3e50;">LKR <?= number_format($total_cost / 1000000, 1) ?>M</h2>
                                <small class="text-muted">Sri Lankan Rupees</small>
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
                                <p class="text-muted mb-2 text-uppercase" style="font-size: 0.75rem; font-weight: 600; letter-spacing: 1px;">In Progress</p>
                                <h2 class="mb-0 fw-bold" style="color: #2c3e50;"><?= $in_progress_count ?></h2>
                                <small class="text-warning"><i class="bi bi-hourglass-split"></i> Active procurements</small>
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
                                <p class="text-muted mb-2 text-uppercase" style="font-size: 0.75rem; font-weight: 600; letter-spacing: 1px;">Approved</p>
                                <h2 class="mb-0 fw-bold" style="color: #2c3e50;"><?= $approved_count ?></h2>
                                <small class="text-success"><i class="bi bi-check-circle"></i> Ready to proceed</small>
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
                        <input type="text" class="form-control" placeholder="Search procurement items...">
                    </div>
                    <div class="col-md-2">
                        <select class="form-select">
                            <option>All Status</option>
                            <option>Planned</option>
                            <option>Approved</option>
                            <option>In Progress</option>
                            <option>Completed</option>
                        </select>
                    </div>

                    <div class="col-md-2">
                        <select class="form-select">
                            <option>All Priorities</option>
                            <option>High</option>
                            <option>Medium</option>
                            <option>Low</option>
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

        <!-- Procurement Table -->
        <div class="card border-0 shadow-sm procurement-table-wrapper">
            <div class="card-header table-header-custom py-3">
                <div class="d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="bi bi-list-check me-2"></i>Procurement Items - 2026 Plan</h5>
                    <span class="badge bg-light text-dark"><?= count($procurements) ?> Items</span>
                </div>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead style="background-color: #f8f9fa;">
                            <tr>
                                <th class="py-3 px-4" style="font-weight: 600; color: #495057;">Item Description</th>
                                <th class="py-3 text-end" style="font-weight: 600; color: #495057;">Estimated Cost (LKR)</th>
                                <th class="py-3" style="font-weight: 600; color: #495057;">Supplier</th>
                                <th class="py-3" style="font-weight: 600; color: #495057;">Priority</th>
                                <th class="py-3" style="font-weight: 600; color: #495057;">Status</th>
                                <th class="py-3 text-center" style="font-weight: 600; color: #495057;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($procurements as $item): ?>
                            <tr style="border-bottom: 1px solid #e9ecef;">
                                <td class="px-4">
                                    <div class="fw-semibold text-dark">
                                        <?= htmlspecialchars($item['item']) ?>
                                    </div>
                                </td>

                                <td class="text-end">
                                    <span class="fw-bold" style="color: #2c3e50;">Rs. <?= number_format($item['estimated_cost']) ?></span>
                                </td>
                                <td>
                                    <span class="text-muted small">
                                        <i class="bi bi-building me-1"></i><?= $item['supplier'] ?>
                                    </span>
                                </td>

                                <td>
                                    <span class="badge badge-custom priority-badge-<?= strtolower($item['priority']) ?>">
                                        <?= $item['priority'] ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="badge badge-custom <?= $item['status'] === 'Approved' ? 'bg-success' : ($item['status'] === 'In Progress' ? 'bg-warning text-dark' : 'bg-secondary') ?>">
                                        <i class="bi bi-<?= $item['status'] === 'Approved' ? 'check-circle' : ($item['status'] === 'In Progress' ? 'hourglass-split' : 'clock') ?>-fill me-1"></i>
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
                                    <button class="btn btn-sm btn-outline-success action-btn" title="Approve">
                                        <i class="bi bi-check-lg"></i>
                                    </button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot style="background-color: #f8f9fa;">
                            <tr>
                                <td colspan="2" class="px-4 py-3 fw-bold text-dark">TOTAL ESTIMATED BUDGET</td>
                                <td class="text-end py-3 fw-bold" style="color: #ff512f; font-size: 1.1rem;">
                                    Rs. <?= number_format($total_cost) ?>
                                </td>
                                <td colspan="5"></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>

        <!-- Pagination -->
        <div class="d-flex justify-content-between align-items-center mt-4 mb-5">
            <p class="text-muted mb-0">Showing 1 to <?= count($procurements) ?> of <?= count($procurements) ?> entries</p>
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