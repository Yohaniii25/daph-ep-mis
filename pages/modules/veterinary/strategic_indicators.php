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
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">

<style>
    .gov-card {
        border: none !important;
        border-radius: 12px !important;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05) !important;
    }
</style>

<div class="d-flex w-100 align-items-stretch min-vh-100">
    <div class="flex-shrink-0" style="background-color: #370709;">
        <?php require_once '../../../includes/sidebar.php'; ?>
    </div>

    <div id="layoutSidenav_content" class="w-100">
        <main class="container-fluid px-4 pt-4">
            
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
                    <button class="btn text-white fw-bold text-nowrap" style="background-color: #820100;" data-bs-toggle="modal" data-bs-target="#addIndicatorModal">
                        <i class="bi bi-plus-circle me-1"></i> Log New Indicator
                    </button>
                </div>
            </div>

            <?php if (isset($_SESSION['msg'])): ?>
                <div class="alert alert-<?= $_SESSION['msg_type'] ?> alert-dismissible fade show shadow-sm py-2 px-3 mb-4 small" role="alert">
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
                        <table id="strategicIndicatorsTable" class="table table-striped table-hover table-bordered align-middle small bg-white text-dark m-0">
                            <thead style="background-color: #d4c7b7; color: #370709;">
                                <tr>
                                    <th>Strategic Strategy Pillar</th>
                                    <th>Sub-Activity Description</th>
                                    <th class="text-center">Target Count</th>
                                    <th class="text-center">Achieved Count</th>
                                    <th class="text-center">Target Performance Milestone</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $stmt = $mysqli->prepare("SELECT * FROM strategic_action_indicators WHERE range_id = ? AND year = ?");
                                $stmt->bind_param("ii", $range_id, $selected_year);
                                $stmt->execute();
                                $res = $stmt->get_result();
                                
                                if ($res->num_rows > 0) {
                                    while ($row = $res->fetch_assoc()) {
                                        $target   = intval($row['target_count']);
                                        $achieved = intval($row['achieved_count']);
                                        $pct      = ($target > 0) ? round(($achieved / $target) * 100, 1) : 0;
                                        
                                        // Dynamically generate color bounds context matrix classes
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

        </main>
    </div>
</div>

<div class="modal fade" id="addIndicatorModal" tabindex="-1" aria-labelledby="addIndicatorLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header text-white py-2" style="background-color: #370709;">
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
                    <button type="submit" class="btn text-white btn-sm px-4 shadow-sm" style="background-color: #820100;">Save Indicator Bounds</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>

<script>
    $(document).ready(function() {
        $('#strategicIndicatorsTable').DataTable({
            responsive: true,
            pageLength: 10,
            lengthMenu: [5, 10, 25, 50],
            ordering: true,
            language: {
                search: "_INPUT_",
                searchPlaceholder: "Filter strategic indicators..."
            }
        });
    });
</script>

<?php require_once '../../../includes/footer.php'; ?>