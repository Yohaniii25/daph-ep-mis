<?php
// pages/modules/farm/chicks_death_details.php -> Chicks Death Details Module
require_once '../../../includes/header.php';
require_once '../../../config/db_connect.php';

if ($_SESSION['role'] !== 'farms_dd') {
    die("Access denied");
}

$user_id = $_SESSION['user_id'] ?? 1;

// Selected filter month (default to current month YYYY-MM)
$selected_month = $_GET['month'] ?? date('Y-m');
$first_day_of_month = date('Y-m-01', strtotime($selected_month . '-01'));

// 1. Fetch User Batches for Dropdown Options
$batch_stmt = $mysqli->prepare("SELECT id, batch_number AS batch_name FROM vaccine_batches WHERE user_id = ? ORDER BY id DESC");
$batch_stmt->bind_param("i", $user_id);
$batch_stmt->execute();
$batch_res = $batch_stmt->get_result();
$user_batches = [];
if ($batch_res) {
    while ($b = $batch_res->fetch_assoc()) {
        $user_batches[] = $b;
    }
}
$batch_stmt->close();

// 2. Fetch Chicks Death Records for Selected Month
$death_stmt = $mysqli->prepare("SELECT * FROM chicks_death_details WHERE record_month = ? ORDER BY id DESC");
$death_stmt->bind_param("s", $first_day_of_month);
$death_stmt->execute();
$death_res = $death_stmt->get_result();
$records = [];
$total_deaths = 0;

if ($death_res) {
    while ($r = $death_res->fetch_assoc()) {
        $records[] = $r;
        $total_deaths += intval($r['deaths']);
    }
}
$death_stmt->close();

// Month Label
$month_label = date('F Y', strtotime($first_day_of_month));

require_once '../../../includes/sidebar.php';
?>

<!-- SweetAlert2 & DataTables CSS & Icons -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">

<div id="layoutSidenav_content" class="bg-light">
    <main class="container-fluid px-4 pt-4">

        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2 class="fw-bold text-dark m-0">Chicks Death Details</h2>
                <small class="text-muted">Record and track monthly chick mortality statistics per batch.</small>
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

        <!-- Rebranded Quick Options & Month Filter Card (Matching parent_stock_operations.php) -->
        <div class="card border-0 shadow-sm mb-4" style="border-radius: 12px;">
            <div class="card-header bg-white py-3 border-0">
                <h5 class="fw-bold text-dark m-0">Quick Options & Filters</h5>
            </div>
            <div class="card-body pt-0 pb-4">
                <div class="row g-3">
                    <div class="col-md-5 col-lg-4">
                        <button style="background-color: #370709; border-color: #370709;" class="btn btn-primary w-100 py-3 fw-bold shadow-sm" data-bs-toggle="modal" data-bs-target="#addDeathModal">
                            <i class="bi bi-plus-circle-fill fs-4 mb-1 d-block"></i>
                            Log Chicks Death Record
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
            <div class="col-md-4">
                <div class="card border-0 shadow-sm p-3 bg-white" style="border-radius: 12px; border-left: 5px solid #dc3545 !important;">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <small class="text-muted fw-bold uppercase d-block">Total Deaths (<?= $month_label ?>)</small>
                            <span class="fs-3 fw-bold text-danger"><?= number_format($total_deaths) ?></span>
                        </div>
                        <div class="p-3 bg-danger-subtle rounded-circle text-danger">
                            <i class="bi bi-heart-pulse-fill fs-4"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card border-0 shadow-sm p-3 bg-white" style="border-radius: 12px; border-left: 5px solid #0d6efd !important;">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <small class="text-muted fw-bold uppercase d-block">Batches Recorded</small>
                            <span class="fs-3 fw-bold text-primary"><?= count($records) ?></span>
                        </div>
                        <div class="p-3 bg-primary-subtle rounded-circle text-primary">
                            <i class="bi bi-tags-fill fs-4"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card border-0 shadow-sm p-3 bg-white" style="border-radius: 12px; border-left: 5px solid #370709 !important;">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <small class="text-muted fw-bold uppercase d-block">Active Report Period</small>
                            <span class="fs-4 fw-bold text-dark"><?= $month_label ?></span>
                        </div>
                        <div class="p-3 bg-secondary-subtle rounded-circle text-dark">
                            <i class="bi bi-calendar3 fs-4"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Data Display Table Card -->
        <div class="card border-0 shadow-sm mb-5" style="border-radius: 12px;">
            <div class="card-header bg-white py-3 border-0 d-flex justify-content-between align-items-center">
                <h5 class="fw-bold text-dark m-0">
                    <i class="bi bi-journal-text me-2 text-primary"></i>Chicks Mortality Log (<?= $month_label ?>)
                </h5>
                <span class="badge bg-danger-subtle text-danger border border-danger px-3 py-2 fw-bold fs-6">
                    Total Deaths: <?= number_format($total_deaths) ?>
                </span>
            </div>
            <div class="card-body p-4">
                <div class="table-responsive">
                    <table class="table table-striped align-middle row-border" id="chicksDeathTable" style="width:100%">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-3">Batch NO / Code</th>
                                <th class="text-center">Deaths (Count)</th>
                                <th class="text-end pe-3">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($records)): ?>
                                <tr>
                                    <td colspan="3" class="text-center text-muted py-4">No mortality records logged for <?= $month_label ?>. Click "Log Chicks Death Record" to add an entry.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($records as $r): ?>
                                    <tr>
                                        <td class="ps-3">
                                            <span class="badge bg-primary-subtle text-primary border px-3 py-2 fs-6 fw-bold">
                                                <i class="bi bi-tag-fill me-1"></i><?= htmlspecialchars($r['batch_no']) ?>
                                            </span>
                                        </td>
                                        <td class="text-center">
                                            <span class="badge bg-danger-subtle text-danger border border-danger px-3 py-2 fs-6 fw-bold">
                                                <?= number_format($r['deaths']) ?> Deaths
                                            </span>
                                        </td>
                                        <td class="text-end pe-3">
                                            <div class="btn-group btn-group-sm">
                                                <button class="btn btn-outline-secondary edit-death-btn px-3"
                                                    data-id="<?= $r['id'] ?>"
                                                    data-month="<?= date('Y-m', strtotime($r['record_month'])) ?>"
                                                    data-batch="<?= htmlspecialchars($r['batch_no']) ?>"
                                                    data-deaths="<?= $r['deaths'] ?>"
                                                    data-bs-toggle="modal" data-bs-target="#editDeathModal"
                                                    title="Edit Record">
                                                    <i class="bi bi-pencil-fill"></i>
                                                </button>
                                                <button class="btn btn-outline-danger delete-death-btn px-3"
                                                    data-href="processors/save_chicks_death.php?action=delete&id=<?= $r['id'] ?>&month=<?= urlencode($selected_month) ?>"
                                                    title="Delete Record">
                                                    <i class="bi bi-trash-fill"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                        <tfoot class="table-dark">
                            <tr>
                                <td class="ps-3 fw-bold fs-6">Total Monthly Mortality</td>
                                <td id="footer_total_deaths" class="text-center fw-bold text-warning fs-5"><?= number_format($total_deaths) ?></td>
                                <td></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>

    </main>
</div>

<!-- ================= ADD CHICKS DEATH MODAL ================= -->
<div class="modal fade" id="addDeathModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form class="modal-content border-0 shadow" action="processors/save_chicks_death.php" method="POST">
            <input type="hidden" name="action" value="create">
            <div class="modal-header text-white" style="background-color: #370709 !important;">
                <h5 class="modal-title fw-bold">
                    <i class="bi bi-plus-circle-fill me-2"></i>Log Chicks Death Record
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body p-4">
                <div class="row g-3">
                    <div class="col-md-12">
                        <label class="form-label fw-bold">Record Month & Year</label>
                        <input type="month" name="record_month" class="form-control" value="<?= htmlspecialchars($selected_month) ?>" required>
                    </div>

                    <div class="col-md-12">
                        <label class="form-label fw-bold">Batch NO</label>
                        <select name="batch_no_select" id="add_batch_no_select" class="form-select mb-2" onchange="toggleCustomBatch('add')">
                            <option value="">-- Select Active Batch or Type Custom --</option>
                            <?php foreach ($user_batches as $ub): ?>
                                <option value="<?= htmlspecialchars($ub['batch_name']) ?>"><?= htmlspecialchars($ub['batch_name']) ?></option>
                            <?php endforeach; ?>
                            <option value="CUSTOM">+ Type Custom Batch NO...</option>
                        </select>
                        <input type="text" id="add_batch_no_custom" name="batch_no_custom" class="form-control d-none" placeholder="Enter Batch NO (e.g., Kadaknath 10, CPRS-19, 817)">
                        <input type="hidden" id="add_batch_no_final" name="batch_no" required>
                    </div>

                    <div class="col-md-12">
                        <label class="form-label fw-bold">Number of Deaths</label>
                        <input type="number" name="deaths" class="form-control" placeholder="0" min="0" value="0" required>
                    </div>
                </div>
            </div>

            <div class="modal-footer bg-light">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary fw-bold px-4" style="background-color: #370709 !important; border-color: #370709 !important;" onclick="prepareBatchNo('add')">Save Record</button>
            </div>
        </form>
    </div>
</div>

<!-- ================= EDIT CHICKS DEATH MODAL ================= -->
<div class="modal fade" id="editDeathModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form class="modal-content border-0 shadow" action="processors/save_chicks_death.php" method="POST">
            <input type="hidden" name="action" value="update">
            <input type="hidden" id="edit_death_id" name="id">
            
            <div class="modal-header bg-dark text-white">
                <h5 class="modal-title fw-bold">
                    <i class="bi bi-pencil-fill me-2 text-warning"></i>Edit Chicks Death Record
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body p-4">
                <div class="row g-3">
                    <div class="col-md-12">
                        <label class="form-label fw-bold">Record Month & Year</label>
                        <input type="month" id="edit_record_month" name="record_month" class="form-control" required>
                    </div>

                    <div class="col-md-12">
                        <label class="form-label fw-bold">Batch NO</label>
                        <select name="batch_no_select" id="edit_batch_no_select" class="form-select mb-2" onchange="toggleCustomBatch('edit')">
                            <option value="">-- Select Active Batch or Type Custom --</option>
                            <?php foreach ($user_batches as $ub): ?>
                                <option value="<?= htmlspecialchars($ub['batch_name']) ?>"><?= htmlspecialchars($ub['batch_name']) ?></option>
                            <?php endforeach; ?>
                            <option value="CUSTOM">+ Type Custom Batch NO...</option>
                        </select>
                        <input type="text" id="edit_batch_no_custom" name="batch_no_custom" class="form-control d-none" placeholder="Enter Batch NO (e.g., Kadaknath 10, CPRS-19, 817)">
                        <input type="hidden" id="edit_batch_no_final" name="batch_no" required>
                    </div>

                    <div class="col-md-12">
                        <label class="form-label fw-bold">Number of Deaths</label>
                        <input type="number" id="edit_deaths" name="deaths" class="form-control" min="0" required>
                    </div>
                </div>
            </div>

            <div class="modal-footer bg-light">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-warning fw-bold px-4 text-dark" onclick="prepareBatchNo('edit')">Update Record</button>
            </div>
        </form>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.7.0.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
<script>
    function toggleCustomBatch(prefix) {
        var selectVal = $('#' + prefix + '_batch_no_select').val();
        if (selectVal === 'CUSTOM') {
            $('#' + prefix + '_batch_no_custom').removeClass('d-none').prop('required', true);
        } else {
            $('#' + prefix + '_batch_no_custom').addClass('d-none').prop('required', false);
        }
    }

    function prepareBatchNo(prefix) {
        var selectVal = $('#' + prefix + '_batch_no_select').val();
        if (selectVal === 'CUSTOM') {
            var customVal = $('#' + prefix + '_batch_no_custom').val();
            $('#' + prefix + '_batch_no_final').val(customVal);
        } else {
            $('#' + prefix + '_batch_no_final').val(selectVal);
        }
    }

    $(document).ready(function() {
        $('#chicksDeathTable').DataTable({
            language: {
                search: "_INPUT_",
                searchPlaceholder: "Search mortality records..."
            }
        });

        // Handle Month Filter Redirect
        $('#btnFilter').on('click', function() {
            var mVal = $('#month_filter').val();
            if (mVal) {
                window.location.href = 'chicks_death_details.php?month=' + encodeURIComponent(mVal);
            }
        });

        // Populate Edit Modal
        $('.edit-death-btn').on('click', function() {
            var id = $(this).data('id');
            var month = $(this).data('month');
            var batch = $(this).data('batch');
            var deaths = $(this).data('deaths');

            $('#edit_death_id').val(id);
            $('#edit_record_month').val(month);
            $('#edit_deaths').val(deaths);

            // Match batch in select or custom
            var exists = false;
            $('#edit_batch_no_select option').each(function() {
                if ($(this).val() === batch) {
                    exists = true;
                }
            });

            if (exists) {
                $('#edit_batch_no_select').val(batch);
                $('#edit_batch_no_custom').addClass('d-none');
            } else {
                $('#edit_batch_no_select').val('CUSTOM');
                $('#edit_batch_no_custom').removeClass('d-none').val(batch);
            }
            $('#edit_batch_no_final').val(batch);
        });

        // Delete confirmation
        $(document).on('click', '.delete-death-btn', function(e) {
            e.preventDefault();
            var href = $(this).data('href');
            Swal.fire({
                title: 'Delete Mortality Record?',
                text: 'Are you sure you want to permanently delete this chicks death record? This action cannot be undone.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#dc3545',
                cancelButtonColor: '#6c757d',
                confirmButtonText: '<i class="bi bi-trash-fill me-1"></i> Yes, Delete',
                cancelButtonText: 'Cancel'
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = href;
                }
            });
        });
    });
</script>
