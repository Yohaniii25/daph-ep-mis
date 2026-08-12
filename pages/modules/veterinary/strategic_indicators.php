<?php
session_start();
require_once '../../../config/db_connect.php';

// 1. Session and Role Guard
if (!isset($_SESSION['logged_in']) || $_SESSION['role'] !== 'veterinary_surgeon') {
    header("Location: ../../../../index.php");
    exit();
}

$full_name   = $_SESSION['full_name'] ?? 'Veterinary Surgeon';
$range_id    = $_SESSION['range_id'] ?? null;
$district_id = $_SESSION['district_id'] ?? null;
$selected_year = isset($_GET['year']) ? intval($_GET['year']) : 2026;

if (empty($range_id)) {
    die('<div class="alert alert-danger text-center p-5 m-5">Error: Your account is not assigned to any Veterinary Range.</div>');
}

// 2. Fetch Core Location Meta Data Information
$district_name = 'Unknown District';
$range_name    = 'Unknown Range';

if ($district_id) {
    $stmt = $mysqli->prepare("SELECT name FROM districts WHERE id = ?");
    $stmt->bind_param("i", $district_id);
    $stmt->execute();
    if ($row = $stmt->get_result()->fetch_assoc()) { $district_name = $row['name']; }
    $stmt->close();
}

if ($range_id) {
    $stmt = $mysqli->prepare("SELECT name FROM veterinary_ranges WHERE id = ?");
    $stmt->bind_param("i", $range_id);
    $stmt->execute();
    if ($row = $stmt->get_result()->fetch_assoc()) { $range_name = $row['name']; }
    $stmt->close();
}

require_once '../../../includes/header.php';
?>

<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.2/css/buttons.bootstrap5.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">

<style>
    .gov-card {
        border: none !important;
        border-radius: 12px !important;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05) !important;
    }
    .dt-buttons .btn {
        border-radius: 6px !important;
        margin-right: 6px !important;
    }
</style>

<div class="mb-4 d-flex justify-content-between align-items-center">
    <div>
        <h4 class="fw-bold mb-1" style="color: #370709;">Strategic Action Indicators</h4>
        <span class="badge" style="background-color: #d4c7b7; color: #370709;"><i class="bi bi-geo-alt-fill me-1"></i><?= htmlspecialchars($range_name) ?> Range</span>
        <span class="badge text-white" style="background-color: #a07174;"><i class="bi bi-building me-1"></i><?= htmlspecialchars($district_name) ?> District</span>
    </div>
    <div class="d-flex gap-2">
        <select class="form-select border-secondary" onchange="location = '?year='+this.value;">
            <option value="2026" <?= $selected_year == 2026 ? 'selected' : '' ?>>2026</option>
            <option value="2025" <?= $selected_year == 2025 ? 'selected' : '' ?>>2025</option>
            <option value="2024" <?= $selected_year == 2024 ? 'selected' : '' ?>>2024</option>
        </select>
        <button class="btn text-light fw-bold text-nowrap" style="background-color: #820100;" data-bs-toggle="modal" data-bs-target="#addIndicatorModal">
            <i class="bi bi-plus-circle me-1"></i> Log New Indicator
        </button>
        <a href="annual_targets.php" class="btn btn-secondary shadow-sm text-nowrap">
            <i class="bi bi-arrow-left me-2"></i>Back
        </a>
    </div>
</div>

<?php if (isset($_SESSION['msg'])): ?>
    <div class="alert alert-<?= $_SESSION['msg_type'] ?? 'info' ?> alert-dismissible fade show shadow-sm py-2 px-3 mb-4 small" role="alert">
        <?= $_SESSION['msg'] ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    <?php unset($_SESSION['msg'], $_SESSION['msg_type']); ?>
<?php endif; ?>

<div class="card gov-card mb-4 shadow-sm border-0">
    <div class="card-header bg-white pt-4 px-4 border-0">
        <h5 class="fw-bold mb-1" style="color: #370709;"><i class="bi bi-bar-chart-steps me-2 text-danger"></i>Performance Management Pillars</h5>
        <p class="text-muted small mb-0">Tracks performance progress on major sub-activities mapped to institutional strategy pillars.</p>
    </div>
    <div class="card-body px-4 pb-4">
        <div class="table-responsive">
            <table id="strategicIndicatorsTable" class="table table-striped table-hover table-bordered align-middle small bg-white text-dark m-0 w-100">
                <thead style="background-color: #d4c7b7; color: #370709;">
                    <tr>
                        <th>Strategic Strategy Pillar</th>
                        <th>Sub-Activity Description</th>
                        <th class="text-center">Target Count</th>
                        <th class="text-center">Achieved Count</th>
                        <th class="text-center">Target Performance Milestone</th>
                        <th class="text-center" style="width: 110px;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $stmt = $mysqli->prepare("SELECT * FROM strategic_action_indicators WHERE range_id = ? AND year = ? ORDER BY id DESC");
                    $stmt->bind_param("ii", $range_id, $selected_year);
                    $stmt->execute();
                    $res = $stmt->get_result();
                    
                    if ($res->num_rows > 0) {
                        while ($row = $res->fetch_assoc()) {
                            $target   = intval($row['target_count']);
                            $achieved = intval($row['achieved_count']);
                            $pct      = ($target > 0) ? round(($achieved / $target) * 100, 1) : 0;
                            
                            $bar_color = ($pct >= 100) ? 'bg-success' : (($pct >= 50) ? 'bg-primary' : 'bg-warning');
                            
                            echo '<tr>';
                            echo '<td class="fw-bold text-dark" style="max-width: 220px;">' . htmlspecialchars($row['strategy_pillar']) . '</td>';
                            echo '<td class="text-muted">' . htmlspecialchars($row['sub_activity']) . '</td>';
                            echo '<td class="text-center fw-bold text-secondary">' . number_format($target) . '</td>';
                            echo '<td class="text-center fw-bold text-danger">' . number_format($achieved) . '</td>';
                            echo '<td>';
                            echo '  <div class="d-flex align-items-center gap-2">';
                            echo '      <div class="progress w-100" style="height: 8px; border-radius: 4px; overflow: hidden; background-color: #e9ecef;">';
                            echo '          <div class="progress-bar ' . $bar_color . '" role="progressbar" style="width: ' . min($pct, 100) . '%"></div>';
                            echo '      </div>';
                            echo '      <span class="fw-bold font-monospace text-dark small">' . $pct . '%</span>';
                            echo '  </div>';
                            echo '</td>';
                            echo '<td class="text-center text-nowrap">';
                            echo '  <button class="btn btn-sm btn-outline-primary me-1 edit-indicator-btn" data-id="' . $row['id'] . '" data-pillar="' . htmlspecialchars($row['strategy_pillar'], ENT_QUOTES) . '" data-sub="' . htmlspecialchars($row['sub_activity'], ENT_QUOTES) . '" data-target="' . $target . '" data-achieved="' . $achieved . '" title="Edit"><i class="bi bi-pencil"></i></button>';
                            echo '  <button class="btn btn-sm btn-outline-danger delete-indicator-btn" data-id="' . $row['id'] . '" title="Delete"><i class="bi bi-trash"></i></button>';
                            echo '</td>';
                            echo '</tr>';
                        }
                    }
                    $stmt->close();
                    ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Add Indicator Modal -->
<div class="modal fade" id="addIndicatorModal" tabindex="-1" aria-labelledby="addIndicatorLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header text-light py-2" style="background-color: #370709;">
                <h6 class="modal-title" id="addIndicatorLabel"><i class="bi bi-patch-plus me-2"></i> Log New Strategic Pillar Marker</h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="processors/save_strategic_indicator.php" method="POST">
                <div class="modal-body p-3 small">
                    <input type="hidden" name="range_id" value="<?= htmlspecialchars($range_id) ?>">
                    <input type="hidden" name="year" value="<?= htmlspecialchars($selected_year) ?>">

                    <div class="mb-2">
                        <label class="form-label fw-bold mb-1">Strategic Pillar Category Group</label>
                        <select name="strategy_pillar" class="form-select form-select-sm border-secondary" required>
                            <option value="" selected disabled>-- Select Strategic Pillar Blueprint --</option>
                            <option value="Disease Prevention and Prophylaxis control">Disease Prevention & Control</option>
                            <option value="Livestock Breeding Infrastructure Maximization">Livestock Breeding Maximization</option>
                            <option value="Dairy Production Extension Schemes">Dairy Production Extension</option>
                            <option value="Institutional Capacity and Staff Deployment">Institutional Capacity</option>
                            <option value="Community Welfare Food Security Distributions">Community Food Safety</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold mb-1">Sub-Activity Target Action Objective</label>
                        <textarea name="sub_activity" class="form-control form-control-sm border-secondary" rows="3" placeholder="Describe specific targeted monitoring tasks, distribution bounds, or clinical evaluations..." required></textarea>
                    </div>

                    <div class="row g-2">
                        <div class="col-md-6">
                            <label class="form-label fw-bold mb-1">Target Count Blueprint</label>
                            <input type="number" name="target_count" class="form-control form-control-sm border-secondary" min="0" value="0" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold mb-1">Achieved Count Outcome</label>
                            <input type="number" name="achieved_count" class="form-control form-control-sm border-secondary" min="0" value="0" required>
                        </div>
                    </div>
                </div>
                <div class="modal-footer py-2 border-top-0">
                    <button type="button" class="btn btn-light btn-sm px-3" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn text-light btn-sm px-4 shadow-sm" style="background-color: #820100;">Save Indicator Bounds</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Indicator Modal -->
<div class="modal fade" id="editIndicatorModal" tabindex="-1" aria-labelledby="editIndicatorLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header text-light py-2" style="background-color: #370709;">
                <h6 class="modal-title" id="editIndicatorLabel"><i class="bi bi-pencil-square me-2"></i> Edit Strategic Action Indicator</h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="processors/update_strategic_indicator.php" method="POST">
                <div class="modal-body p-3 small">
                    <input type="hidden" name="id" id="edit_indicator_id">
                    <input type="hidden" name="year" value="<?= htmlspecialchars($selected_year) ?>">

                    <div class="mb-2">
                        <label class="form-label fw-bold mb-1">Strategic Pillar Category Group</label>
                        <select name="strategy_pillar" id="edit_strategy_pillar" class="form-select form-select-sm border-secondary" required>
                            <option value="" disabled>-- Select Strategic Pillar --</option>
                            <option value="Disease Prevention and Prophylaxis control">Disease Prevention & Control</option>
                            <option value="Livestock Breeding Infrastructure Maximization">Livestock Breeding Maximization</option>
                            <option value="Dairy Production Extension Schemes">Dairy Production Extension</option>
                            <option value="Institutional Capacity and Staff Deployment">Institutional Capacity</option>
                            <option value="Community Welfare Food Security Distributions">Community Food Safety</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold mb-1">Sub-Activity Target Action Objective</label>
                        <textarea name="sub_activity" id="edit_sub_activity" class="form-control form-control-sm border-secondary" rows="3" required></textarea>
                    </div>

                    <div class="row g-2">
                        <div class="col-md-6">
                            <label class="form-label fw-bold mb-1">Target Count</label>
                            <input type="number" name="target_count" id="edit_target_count" class="form-control form-control-sm border-secondary" min="0" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold mb-1">Achieved Count</label>
                            <input type="number" name="achieved_count" id="edit_achieved_count" class="form-control form-control-sm border-secondary" min="0" required>
                        </div>
                    </div>
                </div>
                <div class="modal-footer py-2 border-top-0">
                    <button type="button" class="btn btn-light btn-sm px-3" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn text-light btn-sm px-4 shadow-sm" style="background-color: #820100;">Update Indicator</button>
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
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.0/pdfmake.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.0/vfs_fonts.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.html5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.print.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
    $(document).ready(function() {
        $('#strategicIndicatorsTable').DataTable({
            responsive: true,
            pageLength: 10,
            dom: '<"d-flex justify-content-between align-items-center mb-3"Bf>rt<"d-flex justify-content-between align-items-center mt-3"ip>',
            buttons: [
                {
                    extend: 'csv',
                    text: '<i class="bi bi-file-earmark-spreadsheet me-1"></i> Export CSV',
                    className: 'btn btn-sm btn-success fw-bold me-1',
                    exportOptions: { columns: ':not(:last-child)' }
                },
                {
                    extend: 'excel',
                    text: '<i class="bi bi-file-earmark-excel me-1"></i> Export Excel',
                    className: 'btn btn-sm btn-primary fw-bold me-1',
                    exportOptions: { columns: ':not(:last-child)' }
                },
                {
                    extend: 'pdf',
                    text: '<i class="bi bi-file-earmark-pdf me-1"></i> Export PDF',
                    className: 'btn btn-sm btn-danger fw-bold me-1',
                    orientation: 'landscape',
                    pageSize: 'A4',
                    exportOptions: { columns: ':not(:last-child)' }
                },
                {
                    extend: 'print',
                    text: '<i class="bi bi-printer me-1"></i> Print Table',
                    className: 'btn btn-sm btn-secondary fw-bold me-1',
                    exportOptions: { columns: ':not(:last-child)' }
                }
            ],
            language: {
                search: "",
                searchPlaceholder: "Search indicators..."
            }
        });

        // Edit button handler
        $(document).on('click', '.edit-indicator-btn', function() {
            var id = $(this).data('id');
            var pillar = $(this).data('pillar');
            var sub = $(this).data('sub');
            var target = $(this).data('target');
            var achieved = $(this).data('achieved');

            $('#edit_indicator_id').val(id);
            $('#edit_strategy_pillar').val(pillar);
            $('#edit_sub_activity').val(sub);
            $('#edit_target_count').val(target);
            $('#edit_achieved_count').val(achieved);

            var modal = new bootstrap.Modal(document.getElementById('editIndicatorModal'));
            modal.show();
        });

        // Delete button handler with SweetAlert2
        $(document).on('click', '.delete-indicator-btn', function() {
            var id = $(this).data('id');
            Swal.fire({
                title: 'Delete Indicator Record?',
                text: 'Are you sure you want to delete this strategic action indicator? This action cannot be undone.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#dc3545',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Yes, Delete',
                cancelButtonText: 'Cancel'
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: 'processors/delete_strategic_indicator.php',
                        type: 'POST',
                        data: { id: id },
                        dataType: 'json',
                        success: function(response) {
                            if (response.success) {
                                Swal.fire({
                                    icon: 'success',
                                    title: 'Deleted!',
                                    text: response.message,
                                    timer: 2000,
                                    showConfirmButton: false
                                }).then(function() {
                                    location.reload();
                                });
                            } else {
                                Swal.fire({
                                    icon: 'error',
                                    title: 'Error',
                                    text: response.message
                                });
                            }
                        },
                        error: function() {
                            Swal.fire({
                                icon: 'error',
                                title: 'Server Error',
                                text: 'Failed to connect to the server.'
                            });
                        }
                    });
                }
            });
        });
    });
</script>

<?php require_once '../../../includes/footer.php'; ?>