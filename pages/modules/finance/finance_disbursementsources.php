<?php
require_once '../../../includes/header.php';
if ($_SESSION['role'] !== 'finance_admin') die("Access denied");

// Numeric values
$projects = [
    ['project' => 'PSDG Dairy Development', 'allocation' => 45000000, 'disbursed' => 32000000, 'progress' => 71, 'status' => 'On Track', 'start_date' => '2024-04-01', 'end_date' => '2025-03-31'],
    ['project' => 'CBG Poultry Expansion', 'allocation' => 28000000, 'disbursed' => 28000000, 'progress' => 100, 'status' => 'Completed', 'start_date' => '2024-01-01', 'end_date' => '2024-12-31'],
    ['project' => 'Line Ministry Equipment', 'allocation' => 15000000, 'disbursed' => 8500000, 'progress' => 57, 'status' => 'In Progress', 'start_date' => '2024-07-01', 'end_date' => '2025-06-30'],
    ['project' => 'Provincial Fodder Project', 'allocation' => 20000000, 'disbursed' => 12000000, 'progress' => 60, 'status' => 'On Track', 'start_date' => '2024-05-01', 'end_date' => '2025-04-30'],
];

$total_allocation = array_sum(array_column($projects, 'allocation'));
$total_disbursed = array_sum(array_column($projects, 'disbursed'));
$overall_progress = $total_allocation > 0 ? round(($total_disbursed / $total_allocation) * 100) : 0;
$completed_projects = count(array_filter($projects, function ($p) {
    return $p['progress'] == 100;
}));
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

    .project-table-wrapper {
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

    .progress-modern {
        height: 28px;
        border-radius: 14px;
        background-color: #e9ecef;
        overflow: hidden;
        box-shadow: inset 0 2px 4px rgba(0, 0, 0, 0.1);
        position: relative;
    }

    .progress-bar-modern {
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 600;
        font-size: 0.8rem;
        transition: width 0.8s ease;
        border-radius: 14px;
        position: relative;
        overflow: hidden;
    }

    .progress-bar-modern::after {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 100%;
        background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.3), transparent);
        animation: shimmer 2s infinite;
    }

    @keyframes shimmer {
        0% {
            transform: translateX(-100%);
        }

        100% {
            transform: translateX(100%);
        }
    }

    .status-badge-custom {
        padding: 6px 14px;
        border-radius: 20px;
        font-size: 0.8rem;
        font-weight: 600;
        display: inline-flex;
        align-items: center;
        gap: 6px;
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

    .mini-chart {
        width: 60px;
        height: 30px;
    }

    .project-icon {
        width: 40px;
        height: 40px;
        border-radius: 8px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.2rem;
    }

    .table-scroll-container {
        max-height: 70vh;
        height: 100%;
        overflow-y: auto !important;
        overflow-x: auto !important;
        display: block;
    }

    .table-scroll-container table {
        margin-bottom: 0;
    }

    .table-scroll-container table thead {
        position: sticky;
        top: 0;
        z-index: 10;
    }

    .table-scroll-container table tbody {
        display: table-row-group;
    }

    .table-scroll-container::-webkit-scrollbar {
        width: 10px;
        height: 10px;
    }

    .table-scroll-container::-webkit-scrollbar-track {
        background: #f1f1f1;
        border-radius: 10px;
    }

    .table-scroll-container::-webkit-scrollbar-thumb {
        background: #667eea;
        border-radius: 10px;
    }

    .table-scroll-container::-webkit-scrollbar-thumb:hover {
        background: #764ba2;
    }

    /* Firefox scrollbar */
    .table-scroll-container {
        scrollbar-color: #667eea #f1f1f1;
        scrollbar-width: thin;
    }
</style>

<?php require_once '../../../includes/sidebar.php'; ?>

<div id="layoutSidenav_content">
    <main class="container-fluid px-4 pt-4" style="background-color: #f8f9fa;">

        <!-- Page Header -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h1 class="mb-2 text-dark fw-bold">
                    Project Finance Management
                </h1>
                <p class="text-muted mb-0">
                    Financial Year 2024/2025 Dashboard
                </p>
            </div>

        </div>
        <div class="d-flex justify-content-between align-items-center mb-4">

            <div>
                <button class="btn btn-primary me-2">
                    <i class="bi bi-plus-circle me-2"></i>New Project
                </button>
                <button class="btn btn-outline-secondary me-2">
                    <i class="bi bi-file-earmark-pdf me-2"></i>Generate Report
                </button>
                <button class="btn btn-outline-info">
                    <i class="bi bi-graph-up me-2"></i>Analytics
                </button>
            </div>
        </div>

        <!-- Statistics Cards -->
        <div class="row g-4 mb-4">
            <div class="col-xl-3 col-md-6">
                <div class="card border-0 shadow-sm stat-card">
                    <div class="card-body p-4">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <p class="text-muted mb-2 text-uppercase" style="font-size: 0.75rem; font-weight: 600; letter-spacing: 1px;">Total Allocation</p>
                                <h2 class="mb-0 fw-bold" style="color: #2c3e50;">LKR <?= number_format($total_allocation / 1000000, 1) ?>M</h2>
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
                                <p class="text-muted mb-2 text-uppercase" style="font-size: 0.75rem; font-weight: 600; letter-spacing: 1px;">Total Disbursed</p>
                                <h2 class="mb-0 fw-bold" style="color: #2c3e50;">LKR <?= number_format($total_disbursed / 1000000, 1) ?>M</h2>
                                <small class="text-success"><i class="bi bi-arrow-up"></i> <?= $overall_progress ?>% utilized</small>
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
                                <p class="text-muted mb-2 text-uppercase" style="font-size: 0.75rem; font-weight: 600; letter-spacing: 1px;">Remaining Balance</p>
                                <h2 class="mb-0 fw-bold" style="color: #2c3e50;">LKR <?= number_format(($total_allocation - $total_disbursed) / 1000000, 1) ?>M</h2>
                                <small class="text-info"><i class="bi bi-piggy-bank"></i> Available funds</small>
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
                                <p class="text-muted mb-2 text-uppercase" style="font-size: 0.75rem; font-weight: 600; letter-spacing: 1px;">Active Projects</p>
                                <h2 class="mb-0 fw-bold" style="color: #2c3e50;"><?= count($projects) ?></h2>
                                <small class="text-success"><i class="bi bi-check-circle"></i> <?= $completed_projects ?> completed</small>
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
                        <input type="text" class="form-control" placeholder="Search projects...">
                    </div>
                    <div class="col-md-2">
                        <select class="form-select">
                            <option>All Status</option>
                            <option>On Track</option>
                            <option>In Progress</option>
                            <option>Completed</option>
                            <option>Delayed</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <select class="form-select">
                            <option>All Progress</option>
                            <option>0-25%</option>
                            <option>26-50%</option>
                            <option>51-75%</option>
                            <option>76-100%</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <select class="form-select">
                            <option>Sort By</option>
                            <option>Allocation (High-Low)</option>
                            <option>Progress</option>
                            <option>Project Name</option>
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

        <!-- Project Table -->
        <div class="card border-0 shadow-sm project-table-wrapper">
            <div class="card-header table-header-custom py-3">
                <div class="d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="bi bi-clipboard-data me-2"></i>Project Disbursement Status</h5>
                    <span class="badge bg-light text-dark"><?= count($projects) ?> Projects</span>
                </div>
            </div>
            <div class="card-body p-0">
                <!-- Scrollable wrapper added -->
                <div class="table-scroll-container">
                    <table class="table table-hover align-middle mb-0">
                        <thead style="background-color: #f8f9fa;">
                            <tr>
                                <th class="py-3 px-4" style="font-weight: 600; color: #495057; width: 25%;">Project Name</th>
                                <th class="py-3 text-end" style="font-weight: 600; color: #495057;">Allocation (LKR)</th>
                                <th class="py-3 text-end" style="font-weight: 600; color: #495057;">Disbursed (LKR)</th>
                                <th class="py-3 text-end" style="font-weight: 600; color: #495057;">Balance (LKR)</th>
                                <th class="py-3" style="font-weight: 600; color: #495057;">Timeline</th>
                                <th class="py-3" style="font-weight: 600; color: #495057;">Status</th>
                                <th class="py-3 text-center" style="font-weight: 600; color: #495057;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($projects as $proj):
                                $balance = $proj['allocation'] - $proj['disbursed'];
                                $utilization = round(($proj['disbursed'] / $proj['allocation']) * 100);
                            ?>
                                <tr style="border-bottom: 1px solid #e9ecef;">
                                    <td class="px-4">
                                        <div class="d-flex align-items-center">

                                            <div>
                                                <div class="fw-semibold text-dark"><?= htmlspecialchars($proj['project']) ?></div>
                                                <small class="text-muted"><?= $utilization ?>% fund utilization</small>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="text-end">
                                        <span class="fw-bold" style="color: #2c3e50;">Rs. <?= number_format($proj['allocation']) ?></span>
                                    </td>
                                    <td class="text-end">
                                        <span class="fw-bold text-success">Rs. <?= number_format($proj['disbursed']) ?></span>
                                    </td>
                                    <td class="text-end">
                                        <span class="fw-semibold text-muted">Rs. <?= number_format($balance) ?></span>
                                    </td>
                                    <td>
                                        <small class="text-muted">
                                            <i class="bi bi-calendar-event me-1"></i>
                                            <?= date('M Y', strtotime($proj['start_date'])) ?> - <?= date('M Y', strtotime($proj['end_date'])) ?>
                                        </small>
                                    </td>

                                    <td>
                                        <?php
                                        $statusConfig = [
                                            'Completed' => ['color' => 'success', 'icon' => 'check-circle-fill'],
                                            'On Track' => ['color' => 'info', 'icon' => 'arrow-right-circle-fill'],
                                            'In Progress' => ['color' => 'warning', 'icon' => 'hourglass-split'],
                                            'Delayed' => ['color' => 'danger', 'icon' => 'exclamation-triangle-fill']
                                        ];
                                        $config = $statusConfig[$proj['status']] ?? ['color' => 'secondary', 'icon' => 'circle-fill'];
                                        ?>
                                        <span class="status-badge-custom bg-<?= $config['color'] ?> bg-opacity-10 text-<?= $config['color'] ?>">
                                            <i class="bi bi-<?= $config['icon'] ?>"></i>
                                            <?= $proj['status'] ?>
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        <button class="btn btn-sm btn-outline-primary action-btn me-1" title="View Details">
                                            <i class="bi bi-eye"></i>
                                        </button>
                                        <button class="btn btn-sm btn-outline-secondary action-btn me-1" title="Edit">
                                            <i class="bi bi-pencil"></i>
                                        </button>
                                        <button class="btn btn-sm btn-outline-info action-btn" title="Report">
                                            <i class="bi bi-file-text"></i>
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot style="background-color: #f8f9fa;">
                            <tr>
                                <td class="px-4 py-3 fw-bold text-dark">TOTAL</td>
                                <td class="text-end py-3 fw-bold" style="color: #667eea;">Rs. <?= number_format($total_allocation) ?></td>
                                <td class="text-end py-3 fw-bold text-success">Rs. <?= number_format($total_disbursed) ?></td>
                                <td class="text-end py-3 fw-bold text-muted">Rs. <?= number_format($total_allocation - $total_disbursed) ?></td>
                                <td colspan="4"></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
            <div class="card-footer bg-light py-3">
                <div class="d-flex justify-content-between align-items-center">
                    <small class="text-muted">
                        <i class="bi bi-info-circle me-1"></i>Last updated: <?= date('F j, Y - g:i A') ?> | All values in LKR
                    </small>
                    <small class="text-muted">
                        <i class="bi bi-clock me-1"></i>Financial Year: 2024/2025
                    </small>
                </div>
            </div>
        </div>

        <!-- Pagination -->
        <div class="d-flex justify-content-between align-items-center mt-4 mb-5">
            <p class="text-muted mb-0">Showing 1 to <?= count($projects) ?> of <?= count($projects) ?> entries</p>
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