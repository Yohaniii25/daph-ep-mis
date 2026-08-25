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

// 2. Fetch Range & District Names
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

<link rel="stylesheet" href="../../../assets/css/dataTables.bootstrap5.min.css">
<link rel="stylesheet" href="../../../assets/css/bootstrap-icons.min.css">

<style>
    .gov-card {
        border: none !important;
        border-radius: 12px !important;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05) !important;
    }
</style>

<div class="mb-4 d-flex justify-content-between align-items-center">
                <div>
                    <h4 class="fw-bold mb-1" style="color: #370709;">Production Activity Logs</h4>
                    <span class="badge" style="background-color: #d4c7b7; color: #370709;"><i class="bi bi-geo-alt-fill me-1"></i><?= htmlspecialchars($range_name) ?> Range</span>
                    <span class="badge text-light" style="background-color: #a07174;"><i class="bi bi-building me-1"></i><?= htmlspecialchars($district_name) ?> District</span>
                </div>
                <div class="d-flex gap-2">
                    <select class="form-select border-secondary" onchange="location = '?year='+this.value;">
                        <option value="2026" <?= $selected_year == 2026 ? 'selected' : '' ?>>2026</option>
                        <option value="2025" <?= $selected_year == 2025 ? 'selected' : '' ?>>2025</option>
                        <option value="2024" <?= $selected_year == 2024 ? 'selected' : '' ?>>2024</option>
                    </select>
                    <button class="btn text-light fw-bold text-nowrap" style="background-color: #820100;" data-bs-toggle="modal" data-bs-target="#addActivityModal">
                        <i class="bi bi-plus-circle me-1"></i> Log Activity Target
                    </button>
                    <a href="annual_targets.php" class="btn btn-secondary shadow-sm text-nowrap">
                        <i class="bi bi-arrow-left me-2"></i>Back
                    </a>
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
                    <h5 class="fw-bold mb-1" style="color: #370709;"><i class="bi bi-journal-check me-2 text-danger"></i>Configured Target Matrix Metrics</h5>
                    <p class="text-muted small mb-0">Unified summary sheet tracking macro-level targets against direct live implementations.</p>
                </div>
                <div class="card-body px-4 pb-4">
                    <div class="table-responsive">
                        <table id="productionActivitiesTable" class="table table-striped table-hover table-bordered align-middle small bg-white text-dark m-0">
                            <thead style="background-color: #d4c7b7; color: #370709;">
                                <tr>
                                    <th>Activity / Indicator Name</th>
                                    <th>Animal Category</th>
                                    <th class="text-center">Target Quantity</th>
                                    <th class="text-center">Achieved Quantity</th>
                                    <th class="text-center">Completion Rate</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $stmt = $mysqli->prepare("SELECT * FROM production_activity_targets WHERE range_id = ? AND year = ?");
                                $stmt->bind_param("ii", $range_id, $selected_year);
                                $stmt->execute();
                                $res = $stmt->get_result();
                                
                                if ($res->num_rows > 0) {
                                    while ($row = $res->fetch_assoc()) {
                                        $category = ($row['animal_category'] === 'Other') ? $row['animal_category_other'] : $row['animal_category'];
                                        $pct = ($row['target_quantity'] > 0) ? round(($row['achieved_quantity'] / $row['target_quantity']) * 100, 1) : 0;
                                        
                                        // Visual Indicator Color Switch
                                        $badge_cls = ($pct >= 100) ? 'bg-success' : (($pct >= 50) ? 'bg-primary' : 'bg-warning text-dark');
                                        
                                        echo '<tr>';
                                        echo '<td class="fw-bold text-dark">' . htmlspecialchars($row['activity_name']) . '</td>';
                                        echo '<td><span class="badge bg-light text-secondary border">' . htmlspecialchars($category ?? 'N/A') . '</span></td>';
                                        echo '<td class="text-center fw-bold text-secondary">' . number_format($row['target_quantity']) . '</td>';
                                        echo '<td class="text-center fw-bold text-danger">' . number_format($row['achieved_quantity']) . '</td>';
                                        echo '<td class="text-center"><span class="badge ' . $badge_cls . '">' . $pct . '%</span></td>';
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

<div class="modal fade" id="addActivityModal" tabindex="-1" aria-labelledby="addActivityLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header text-light py-2" style="background-color: #370709;">
                <h6 class="modal-title" id="addActivityLabel"><i class="bi bi-plus-circle me-2"></i> Log New Production Activity Target</h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="processors/save_production_activity.php" method="POST">
                <div class="modal-body p-3 small">
                    <input type="hidden" name="range_id" value="<?= htmlspecialchars($range_id) ?>">
                    <input type="hidden" name="year" value="<?= htmlspecialchars($selected_year) ?>">

                    <div class="mb-2">
                        <label class="form-label fw-bold mb-1">Activity Name / Metric Title</label>
                        <input type="text" name="activity_name" class="form-control form-control-sm border-secondary" placeholder="e.g. Pasture Development, Silage Production" required>
                    </div>

                    <div class="row g-2 mb-2">
                        <div class="col-md-6">
                            <label class="form-label fw-bold mb-1">Animal Category</label>
                            <select id="animalCategorySelect" name="animal_category" class="form-select form-select-sm border-secondary" required onchange="toggleOtherCategoryInput(this.value)">
                                <option value="" selected disabled>-- Select Profile Option --</option>
                                <option value="Cow">Cow</option>
                                <option value="Buffalo">Buffalo</option>
                                <option value="Goat">Goat</option>
                                <option value="Chicken">Chicken</option>
                                <option value="Pig">Pig</option>
                                <option value="Other">Other Species</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold mb-1">If "Other" (Specify Specie)</label>
                            <input type="text" id="otherCategoryInput" name="animal_category_other" class="form-control form-control-sm border-secondary" placeholder="e.g. Rabbit, Sheep" disabled>
                        </div>
                    </div>

                    <div class="row g-2">
                        <div class="col-md-6">
                            <label class="form-label fw-bold mb-1">Target Quantity Limit</label>
                            <input type="number" name="target_quantity" class="form-control form-control-sm border-secondary" min="0" value="0" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold mb-1">Achieved Quantity (To Date)</label>
                            <input type="number" name="achieved_quantity" class="form-control form-control-sm border-secondary" min="0" value="0" required>
                        </div>
                    </div>

                </div>
                <div class="modal-footer py-2 border-top-0">
                    <button type="button" class="btn btn-light btn-sm px-3" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn text-light btn-sm px-4 shadow-sm" style="background-color: #820100;">Save Activity Profile</button>
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
        $('#productionActivitiesTable').DataTable({
            responsive: true,
            pageLength: 10,
            lengthMenu: [5, 10, 25, 50],
            ordering: true,
            language: {
                search: "_INPUT_",
                searchPlaceholder: "Filter production metrics..."
            }
        });
    });

    function toggleOtherCategoryInput(value) {
        const otherInput = document.getElementById('otherCategoryInput');
        if (value === 'Other') {
            otherInput.disabled = false;
            otherInput.required = true;
            otherInput.focus();
        } else {
            otherInput.disabled = true;
            otherInput.required = false;
            otherInput.value = '';
        }
    }
</script>

<?php require_once '../../../includes/footer.php'; ?>