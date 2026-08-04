<?php
// pages/modules/farm/hatchery_register.php -> Hatchery Register (Annex 3)
require_once '../../../includes/header.php';
require_once '../../../config/db_connect.php';

if ($_SESSION['role'] !== 'farms_dd') {
    die("Access denied");
}

$user_id = $_SESSION['user_id'] ?? 1;

// Selected filter month (default to current month YYYY-MM)
$selected_month = $_GET['month'] ?? date('Y-m');
$first_day_of_month = date('Y-m-01', strtotime($selected_month . '-01'));
$last_day_of_month = date('Y-m-t', strtotime($selected_month . '-01'));

// Fetch Cages for dropdowns
$cages_res = $mysqli->query("SELECT id, cage_name FROM cages ORDER BY cage_name");
$cages = [];
if ($cages_res) {
    while ($row = $cages_res->fetch_assoc()) {
        $cages[] = $row;
    }
}

// Fetch Parent Stock Batches for dropdowns
$batches_res = $mysqli->query("SELECT id, batch_number AS batch_name FROM vaccine_batches ORDER BY id DESC");
$batches = [];
if ($batches_res) {
    while ($row = $batches_res->fetch_assoc()) {
        $batches[] = $row;
    }
}

// Fetch Hatchery Register Records for selected month (with Batch and Cage Joins)
$sql = "SELECT hr.*, c1.cage_name AS incubator_cage_name, c2.cage_name AS target_cage_name, vb.batch_number AS batch_name 
        FROM hatchery_register hr
        LEFT JOIN cages c1 ON hr.cage_id = c1.id
        LEFT JOIN cages c2 ON hr.loaded_to_cage_id = c2.id
        LEFT JOIN vaccine_batches vb ON hr.batch_id = vb.id
        WHERE hr.record_date BETWEEN ? AND ?
        ORDER BY hr.record_date DESC, hr.id DESC";

$stmt = $mysqli->prepare($sql);
$stmt->bind_param("ss", $first_day_of_month, $last_day_of_month);
$stmt->execute();
$hatchery_res = $stmt->get_result();
$records = [];
$total_eggs_loaded = 0;
$total_hatched_eggs = 0;
$total_healthy_chicks = 0;
$total_net_viable_eggs = 0;

if ($hatchery_res) {
    while ($row = $hatchery_res->fetch_assoc()) {
        $records[] = $row;
        $total_eggs_loaded += intval($row['no_of_eggs_loaded']);
        $total_hatched_eggs += intval($row['no_of_hatched_eggs']);
        $total_healthy_chicks += intval($row['no_of_good_chicks']);
        
        // Candling Deduction: Net Viable Eggs = Loaded Eggs - Candling Discards
        $net_viable = max(0, intval($row['no_of_eggs_loaded']) - intval($row['discarded_during_candling']));
        $total_net_viable_eggs += $net_viable;
    }
}
$stmt->close();

$overall_hatching_pct = $total_net_viable_eggs > 0 ? round(($total_healthy_chicks / $total_net_viable_eggs) * 100, 2) : 0.00;
$month_label = date('F Y', strtotime($first_day_of_month));

require_once '../../../includes/sidebar.php';
?>

<!-- SweetAlert2 & DataTables CSS -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.2/css/buttons.bootstrap5.min.css">

<div id="layoutSidenav_content" class="bg-light">
    <main class="container-fluid px-4 pt-4">

        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2 class="fw-bold text-dark m-0">Hatchery Register</h2>
                <small class="text-muted">Track egg incubation, candling, hatching efficiency, and healthy chick allocations.</small>
            </div>
            <span class="badge bg-secondary p-2 fs-6">Logged in: <b><?= htmlspecialchars($_SESSION['username']) ?></b></span>
        </div>

        <!-- Notification Status SweetAlert -->
        <?php if (isset($_GET['status'])): ?>
            <script>
                document.addEventListener('DOMContentLoaded', function() {
                    if (typeof Swal !== 'undefined') {
                        Swal.fire({
                            icon: '<?= ($_GET['status'] === 'success') ? 'success' : 'error' ?>',
                            title: '<?= ($_GET['status'] === 'success') ? 'Success!' : 'Error!' ?>',
                            text: <?= json_encode($_GET['msg'] ?? '') ?>,
                            confirmButtonColor: '#370709',
                            timer: 3500,
                            timerProgressBar: true
                        });
                    }
                });
            </script>
        <?php endif; ?>

        <!-- Quick Options & Month Filter Card -->
        <div class="card border-0 shadow-sm mb-4" style="border-radius: 12px;">
            <div class="card-header bg-white py-3 border-0">
                <h5 class="fw-bold text-dark m-0">Quick Options & Filters</h5>
            </div>
            <div class="card-body pt-0 pb-4">
                <div class="row g-3">
                    <div class="col-md-5 col-lg-4 text">
                        <button style="background-color: #370709; border-color: #370709;" class="btn btn-primary w-100 py-3 fw-bold shadow-sm" data-bs-toggle="modal" data-bs-target="#addHatcheryModal">
                            <i class="bi bi-plus-circle-fill fs-4 mb-1 d-block text-light"></i>
                            Log Hatchery Register Entry
                        </button>
                    </div>
                    <div class="col-md-7 col-lg-8">
                        <div class="p-3 bg-light rounded border border-light-subtle d-flex align-items-center justify-content-between h-100 shadow-sm">
                            <div class="me-3 flex-grow-1">
                                <label class="form-label fw-bold text-dark mb-1 small">
                                    <i class="bi bi-calendar-month-fill me-1 text-primary"></i>Filter Report Month & Year
                                </label>
                                <input type="month" id="month_filter" class="form-control fw-bold border" value="<?= htmlspecialchars($selected_month) ?>">
                            </div>
                            <div class="d-flex align-items-end" style="height: 100%;">
                                <button type="button" class="btn btn-dark fw-bold px-4 py-2 mt-4" id="btnFilter">
                                    <i class="bi bi-funnel-fill me-1"></i>Filter Report
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Summary Statistics KPI Cards -->
        <div class="row g-3 mb-4">
            <div class="col-md-3">
                <div class="card border-0 shadow-sm p-3 bg-white" style="border-radius: 12px; border-left: 5px solid #0d6efd !important;">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <small class="text-muted fw-bold uppercase d-block">Total Eggs Loaded</small>
                            <span class="fs-3 fw-bold text-primary"><?= number_format($total_eggs_loaded) ?></span>
                        </div>
                        <div class="p-3 bg-primary-subtle rounded-circle text-primary">
                            <i class="bi bi-egg-fill fs-4"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card border-0 shadow-sm p-3 bg-white" style="border-radius: 12px; border-left: 5px solid #ffc107 !important;">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <small class="text-muted fw-bold uppercase d-block">Total Hatched Eggs</small>
                            <span class="fs-3 fw-bold text-warning"><?= number_format($total_hatched_eggs) ?></span>
                        </div>
                        <div class="p-3 bg-warning-subtle rounded-circle text-warning">
                            <i class="bi bi-egg-fried fs-4"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card border-0 shadow-sm p-3 bg-white" style="border-radius: 12px; border-left: 5px solid #198754 !important;">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <small class="text-muted fw-bold uppercase d-block">Healthy Chicks</small>
                            <span class="fs-3 fw-bold text-success"><?= number_format($total_healthy_chicks) ?></span>
                        </div>
                        <div class="p-3 bg-success-subtle rounded-circle text-success">
                            <i class="bi bi-check-circle-fill fs-4"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card border-0 shadow-sm p-3 bg-white" style="border-radius: 12px; border-left: 5px solid #370709 !important;">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <small class="text-muted fw-bold uppercase d-block">Hatching % (Avg)</small>
                            <span class="fs-3 fw-bold text-dark"><?= number_format($overall_hatching_pct, 2) ?>%</span>
                        </div>
                        <div class="p-3 bg-light rounded-circle text-dark border">
                            <i class="bi bi-percent fs-4"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Data Table Card -->
        <div class="card border-0 shadow-sm mb-4" style="border-radius: 12px;">
            <div class="card-header bg-white py-3 border-0 d-flex justify-content-between align-items-center">
                <h5 class="fw-bold text-dark m-0">Hatchery Incubation & Hatching Log - <?= $month_label ?></h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover align-middle border" id="hatcheryTable">
                        <thead class="table-dark" style="background-color: #370709;">
                            <tr>
                                <th>Date</th>
                                <th>Batch No</th>
                                <th>Incubator Cage</th>
                                <th>Eggs Loaded</th>
                                <th>Candling Date</th>
                                <th>Discarded (Candling)</th>
                                <th>Net Viable Eggs</th>
                                <th>Hatching Date</th>
                                <th>Hatched Eggs</th>
                                <th>Deaths</th>
                                <th>Healthy Chicks</th>
                                <th>Hatching %</th>
                                <th>Loaded to Cage</th>
                                <th>Remark</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($records as $r): ?>
                                <?php $net_viable_row = max(0, intval($r['no_of_eggs_loaded']) - intval($r['discarded_during_candling'])); ?>
                                <tr>
                                    <td><?= date('d M Y', strtotime($r['record_date'])) ?></td>
                                    <td><span class="badge bg-purple" style="background-color: #6f42c1;"><?= htmlspecialchars($r['batch_name'] ?? 'N/A') ?></span></td>
                                    <td><span class="badge bg-secondary"><?= htmlspecialchars($r['incubator_cage_name'] ?? 'N/A') ?></span></td>
                                    <td class="fw-bold text-primary"><?= number_format($r['no_of_eggs_loaded']) ?></td>
                                    <td><?= !empty($r['date_of_candling']) ? date('d M Y', strtotime($r['date_of_candling'])) : '-' ?></td>
                                    <td class="text-danger"><?= number_format($r['discarded_during_candling']) ?></td>
                                    <td class="fw-bold text-info"><?= number_format($net_viable_row) ?></td>
                                    <td><?= !empty($r['date_of_hatching']) ? date('d M Y', strtotime($r['date_of_hatching'])) : '-' ?></td>
                                    <td class="fw-bold text-warning"><?= number_format($r['no_of_hatched_eggs']) ?></td>
                                    <td class="text-danger"><?= number_format($r['no_of_deaths']) ?></td>
                                    <td class="fw-bold text-success"><?= number_format($r['no_of_good_chicks']) ?></td>
                                    <td><span class="badge bg-success fs-6"><?= number_format($r['hatching_percentage'], 2) ?>%</span></td>
                                    <td><span class="badge bg-info text-dark fw-bold"><?= htmlspecialchars($r['target_cage_name'] ?? 'N/A') ?></span></td>
                                    <td class="small"><?= htmlspecialchars($r['remark'] ?? '-') ?></td>
                                    <td>
                                        <button class="btn btn-sm btn-outline-primary me-1 btn-edit" 
                                                data-id="<?= $r['id'] ?>"
                                                data-record_date="<?= $r['record_date'] ?>"
                                                data-batch_id="<?= $r['batch_id'] ?? '' ?>"
                                                data-cage_id="<?= $r['cage_id'] ?>"
                                                data-no_of_eggs_loaded="<?= $r['no_of_eggs_loaded'] ?>"
                                                data-date_of_candling="<?= $r['date_of_candling'] ?>"
                                                data-discarded_during_candling="<?= $r['discarded_during_candling'] ?>"
                                                data-date_of_hatching="<?= $r['date_of_hatching'] ?>"
                                                data-no_of_hatched_eggs="<?= $r['no_of_hatched_eggs'] ?>"
                                                data-no_of_deaths="<?= $r['no_of_deaths'] ?>"
                                                data-no_of_good_chicks="<?= $r['no_of_good_chicks'] ?>"
                                                data-hatching_percentage="<?= $r['hatching_percentage'] ?>"
                                                data-loaded_to_cage_id="<?= $r['loaded_to_cage_id'] ?>"
                                                data-remark="<?= htmlspecialchars($r['remark'] ?? '') ?>"
                                                data-bs-toggle="modal" data-bs-target="#editHatcheryModal">
                                            <i class="bi bi-pencil-square"></i>
                                        </button>
                                        <a href="processors/hatchery_register_crud.php?action=delete&id=<?= $r['id'] ?>" class="btn btn-sm btn-outline-danger btn-delete">
                                            <i class="bi bi-trash"></i>
                                        </a>
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

<!-- Modal: Add Hatchery Record -->
<div class="modal fade" id="addHatcheryModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form action="processors/hatchery_register_crud.php" method="POST">
                <input type="hidden" name="action" value="create">
                <div class="modal-header text-white" style="background-color: #370709;">
                    <h5 class="modal-header-title fw-bold m-0"><i class="bi bi-egg-fill me-2"></i>Log Hatchery Register Entry</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Record Date <span class="text-danger">*</span></label>
                            <input type="date" name="record_date" id="add_record_date" class="form-control" value="<?= date('Y-m-d') ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Parent Stock Batch <span class="badge bg-info text-dark">Auto-Link</span></label>
                            <select name="batch_id" id="add_batch_id" class="form-select">
                                <option value="">-- Select Parent Stock Batch --</option>
                                <?php foreach ($batches as $b): ?>
                                    <option value="<?= $b['id'] ?>"><?= htmlspecialchars($b['batch_name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Incubator Cage <span class="text-danger">*</span></label>
                            <select name="cage_id" class="form-select" required>
                                <option value="">-- Select Cage --</option>
                                <?php foreach ($cages as $c): ?>
                                    <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['cage_name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">No. of Eggs Loaded <span class="badge bg-secondary">Auto-pulled</span> <span class="text-danger">*</span></label>
                            <input type="number" name="no_of_eggs_loaded" id="add_no_of_eggs_loaded" class="form-control bg-light fw-bold calc-trigger" min="0" value="0" readonly required>
                            <small class="text-muted">Pulled automatically from Parent Stock Operations</small>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Date of Candling</label>
                            <input type="date" name="date_of_candling" class="form-control">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold text-danger">Discarded During Candling (Minus)</label>
                            <input type="number" name="discarded_during_candling" id="add_discarded_during_candling" class="form-control border-danger calc-trigger" min="0" value="0">
                            <small class="text-muted d-block mt-1">Net Viable Eggs after Candling: <strong id="add_net_viable_display" class="text-primary">0</strong></small>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Date of Hatching</label>
                            <input type="date" name="date_of_hatching" class="form-control">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">No. of Hatched Eggs <span class="badge bg-secondary">Auto-pulled</span></label>
                            <input type="number" name="no_of_hatched_eggs" id="add_no_of_hatched_eggs" class="form-control bg-light fw-bold" min="0" value="0" readonly>
                            <small class="text-muted">Pulled automatically from Parent Stock Operations</small>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold">No. of Deaths</label>
                            <input type="number" name="no_of_deaths" class="form-control" min="0" value="0">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold text-success">Healthy Chicks <span class="text-danger">*</span></label>
                            <input type="number" name="no_of_good_chicks" id="add_no_of_good_chicks" class="form-control border-success calc-trigger fw-bold" min="0" value="0" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold">Hatching Percentage (%)</label>
                            <div class="input-group">
                                <input type="text" id="add_hatching_percentage" class="form-control bg-light fw-bold" readonly value="0.00%">
                                <span class="input-group-text bg-secondary text-white">%</span>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold text-primary">Loaded to Cage ID (Target Cage) <span class="text-danger">*</span></label>
                            <select name="loaded_to_cage_id" class="form-select border-primary" required>
                                <option value="">-- Select Target Cage --</option>
                                <?php foreach ($cages as $c): ?>
                                    <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['cage_name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-12">
                            <label class="form-label fw-bold">Remark</label>
                            <textarea name="remark" class="form-control" rows="2" placeholder="Optional notes..."></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary fw-bold" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary fw-bold" style="background-color: #370709; border-color: #370709;">Save Hatchery Record</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal: Edit Hatchery Record -->
<div class="modal fade" id="editHatcheryModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form action="processors/hatchery_register_crud.php" method="POST">
                <input type="hidden" name="action" value="update">
                <input type="hidden" name="id" id="edit_id">
                <div class="modal-header text-white" style="background-color: #370709;">
                    <h5 class="modal-header-title fw-bold m-0"><i class="bi bi-pencil-square me-2"></i>Edit Hatchery Record</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Record Date <span class="text-danger">*</span></label>
                            <input type="date" name="record_date" id="edit_record_date" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Parent Stock Batch <span class="badge bg-info text-dark">Auto-Link</span></label>
                            <select name="batch_id" id="edit_batch_id" class="form-select">
                                <option value="">-- Select Parent Stock Batch --</option>
                                <?php foreach ($batches as $b): ?>
                                    <option value="<?= $b['id'] ?>"><?= htmlspecialchars($b['batch_name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Incubator Cage <span class="text-danger">*</span></label>
                            <select name="cage_id" id="edit_cage_id" class="form-select" required>
                                <option value="">-- Select Cage --</option>
                                <?php foreach ($cages as $c): ?>
                                    <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['cage_name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">No. of Eggs Loaded <span class="badge bg-secondary">Auto-pulled</span> <span class="text-danger">*</span></label>
                            <input type="number" name="no_of_eggs_loaded" id="edit_no_of_eggs_loaded" class="form-control bg-light fw-bold edit-calc-trigger" min="0" readonly required>
                            <small class="text-muted">Pulled automatically from Parent Stock Operations</small>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Date of Candling</label>
                            <input type="date" name="date_of_candling" id="edit_date_of_candling" class="form-control">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold text-danger">Discarded During Candling (Minus)</label>
                            <input type="number" name="discarded_during_candling" id="edit_discarded_during_candling" class="form-control border-danger edit-calc-trigger" min="0">
                            <small class="text-muted d-block mt-1">Net Viable Eggs after Candling: <strong id="edit_net_viable_display" class="text-primary">0</strong></small>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Date of Hatching</label>
                            <input type="date" name="date_of_hatching" id="edit_date_of_hatching" class="form-control">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">No. of Hatched Eggs <span class="badge bg-secondary">Auto-pulled</span></label>
                            <input type="number" name="no_of_hatched_eggs" id="edit_no_of_hatched_eggs" class="form-control bg-light fw-bold" min="0" readonly>
                            <small class="text-muted">Pulled automatically from Parent Stock Operations</small>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold">No. of Deaths</label>
                            <input type="number" name="no_of_deaths" id="edit_no_of_deaths" class="form-control" min="0">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold text-success">Healthy Chicks <span class="text-danger">*</span></label>
                            <input type="number" name="no_of_good_chicks" id="edit_no_of_good_chicks" class="form-control border-success edit-calc-trigger fw-bold" min="0" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold">Hatching Percentage (%)</label>
                            <div class="input-group">
                                <input type="text" id="edit_hatching_percentage" class="form-control bg-light fw-bold" readonly value="0.00%">
                                <span class="input-group-text bg-secondary text-white">%</span>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold text-primary">Loaded to Cage ID (Target Cage) <span class="text-danger">*</span></label>
                            <select name="loaded_to_cage_id" id="edit_loaded_to_cage_id" class="form-select border-primary" required>
                                <option value="">-- Select Target Cage --</option>
                                <?php foreach ($cages as $c): ?>
                                    <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['cage_name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-12">
                            <label class="form-label fw-bold">Remark</label>
                            <textarea name="remark" id="edit_remark" class="form-control" rows="2"></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary fw-bold" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary fw-bold" style="background-color: #370709; border-color: #370709;">Update Hatchery Record</button>
                </div>
            </form>
        </div>
    </div>
</div>
                    <button type="button" class="btn btn-secondary fw-bold" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary fw-bold" style="background-color: #370709; border-color: #370709;">Update Hatchery Record</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/dataTables.buttons.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.bootstrap5.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/pdfmake.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/vfs_fonts.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.html5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.print.min.js"></script>

<script>
$(document).ready(function() {
    $('#hatcheryTable').DataTable({
        order: [[0, 'desc']],
        pageLength: 25,
        dom: '<"d-flex justify-content-between align-items-center mb-3"Bf>rt<"d-flex justify-content-between align-items-center mt-3"ip>',
        buttons: [
            { 
                extend: 'csv', 
                text: '<i class="bi bi-filetype-csv me-1"></i> CSV', 
                className: 'btn btn-sm btn-success me-1 rounded font-weight-bold',
                exportOptions: { columns: ':not(:last-child)' }
            },
            { 
                extend: 'pdf', 
                text: '<i class="bi bi-file-earmark-pdf me-1"></i> PDF', 
                className: 'btn btn-sm btn-danger me-1 rounded font-weight-bold',
                orientation: 'landscape',
                pageSize: 'A4',
                exportOptions: { columns: ':not(:last-child)' }
            },
            { 
                extend: 'print', 
                text: '<i class="bi bi-printer me-1"></i> Print', 
                className: 'btn btn-sm btn-dark rounded font-weight-bold',
                exportOptions: { columns: ':not(:last-child)' }
            }
        ]
    });

    // Month filter action
    $('#btnFilter').on('click', function() {
        const mVal = $('#month_filter').val();
        if (mVal) {
            window.location.href = 'hatchery_register.php?month=' + encodeURIComponent(mVal);
        }
    });

    // Auto-fetch Eggs Loaded & Hatched Eggs from parent_stock_operations.php
    function fetchParentStockData(batchId, recordDate, isEdit = false) {
        if (!batchId) return;
        $.getJSON('processors/get_parent_stock_hatchery_data.php', {
            batch_id: batchId,
            record_date: recordDate
        }, function(res) {
            if (res && res.success) {
                const loadedInput = isEdit ? '#edit_no_of_eggs_loaded' : '#add_no_of_eggs_loaded';
                const hatchedInput = isEdit ? '#edit_no_of_hatched_eggs' : '#add_no_of_hatched_eggs';
                $(loadedInput).val(res.eggs_loaded);
                $(hatchedInput).val(res.hatched_eggs);
                if (isEdit) {
                    updateEditPercentage();
                } else {
                    updateAddPercentage();
                }
            }
        });
    }

    $('#add_batch_id, #add_record_date').on('change', function() {
        const bId = $('#add_batch_id').val();
        const rDate = $('#add_record_date').val();
        fetchParentStockData(bId, rDate, false);
    });

    $('#edit_batch_id, #edit_record_date').on('change', function() {
        const bId = $('#edit_batch_id').val();
        const rDate = $('#edit_record_date').val();
        fetchParentStockData(bId, rDate, true);
    });

    // Calculate Hatching Percentage with Candling Deduction: (Healthy Chicks / (Loaded Eggs - Candling Discards)) * 100
    function updateAddPercentage() {
        const eggs = parseFloat($('#add_no_of_eggs_loaded').val()) || 0;
        const candling = parseFloat($('#add_discarded_during_candling').val()) || 0;
        const healthy = parseFloat($('#add_no_of_good_chicks').val()) || 0;
        
        const netViable = Math.max(0, eggs - candling);
        $('#add_net_viable_display').text(netViable);

        if (netViable > 0) {
            const pct = ((healthy / netViable) * 100).toFixed(2);
            $('#add_hatching_percentage').val(pct + '%');
        } else {
            $('#add_hatching_percentage').val('0.00%');
        }
    }

    function updateEditPercentage() {
        const eggs = parseFloat($('#edit_no_of_eggs_loaded').val()) || 0;
        const candling = parseFloat($('#edit_discarded_during_candling').val()) || 0;
        const healthy = parseFloat($('#edit_no_of_good_chicks').val()) || 0;
        
        const netViable = Math.max(0, eggs - candling);
        $('#edit_net_viable_display').text(netViable);

        if (netViable > 0) {
            const pct = ((healthy / netViable) * 100).toFixed(2);
            $('#edit_hatching_percentage').val(pct + '%');
        } else {
            $('#edit_hatching_percentage').val('0.00%');
        }
    }

    $('.calc-trigger').on('input change', updateAddPercentage);
    $('.edit-calc-trigger').on('input change', updateEditPercentage);

    // Populate Edit Modal
    $(document).on('click', '.btn-edit', function() {
        const btn = $(this);
        $('#edit_id').val(btn.data('id'));
        $('#edit_record_date').val(btn.data('record_date'));
        $('#edit_batch_id').val(btn.data('batch_id'));
        $('#edit_cage_id').val(btn.data('cage_id'));
        $('#edit_no_of_eggs_loaded').val(btn.data('no_of_eggs_loaded'));
        $('#edit_date_of_candling').val(btn.data('date_of_candling'));
        $('#edit_discarded_during_candling').val(btn.data('discarded_during_candling'));
        $('#edit_date_of_hatching').val(btn.data('date_of_hatching'));
        $('#edit_no_of_hatched_eggs').val(btn.data('no_of_hatched_eggs'));
        $('#edit_no_of_deaths').val(btn.data('no_of_deaths'));
        $('#edit_no_of_good_chicks').val(btn.data('no_of_good_chicks'));
        $('#edit_loaded_to_cage_id').val(btn.data('loaded_to_cage_id'));
        $('#edit_remark').val(btn.data('remark'));
        updateEditPercentage();
    });

    // Delete confirmation SweetAlert
    $(document).on('click', '.btn-delete', function(e) {
        e.preventDefault();
        const deleteUrl = $(this).attr('href');
        Swal.fire({
            title: 'Are you sure?',
            text: "This hatchery record will be permanently deleted!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Yes, delete it!'
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = deleteUrl;
            }
        });
    });
});
</script>

<?php require_once '../../../includes/footer.php'; ?>
