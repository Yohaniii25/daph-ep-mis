<?php
session_start();
require_once '../../../config/db_connect.php';

if (!isset($_SESSION['logged_in']) || !in_array($_SESSION['role'], ['veterinary_surgeon', 'sms'])) {
    header("Location: ../../../index.php");
    exit();
}

$user_id = $_SESSION['user_id'] ?? null;
$range_id = $_SESSION['range_id'] ?? null;

$range_name = 'Your Range';
$district_name = 'Your District';
$district_id = null;

if (!empty($range_id)) {
    $details_sql = "
        SELECT vr.name AS range_name, d.name AS district_name, d.id AS district_id
        FROM veterinary_ranges vr
        LEFT JOIN districts d ON vr.district_id = d.id
        WHERE vr.id = ?
    ";
    $details_query = $mysqli->prepare($details_sql);
    if ($details_query) {
        $details_query->bind_param("i", $range_id);
        $details_query->execute();
        $details_result = $details_query->get_result();
        if ($data = $details_result->fetch_assoc()) {
            $range_name = $data['range_name'];
            $district_name = $data['district_name'];
            $district_id = $data['district_id'];
        }
        $details_query->close();
    }
}

// Handle GET year filter (default to 'all' so all records for the range are displayed)
$selected_year = 'all';
if (isset($_GET['year'])) {
    if ($_GET['year'] === 'all' || $_GET['year'] === '') {
        $selected_year = 'all';
    } else {
        $selected_year = intval($_GET['year']);
    }
}

// Inline CRUD actions:
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        if ($_POST['action'] === 'add') {
            $year = intval($_POST['report_year']);
            $co3 = floatval($_POST['co3_kg_year']);
            $co4 = floatval($_POST['co4_kg_year']);
            $co5 = floatval($_POST['co5_kg_year']);
            $aus_red = floatval($_POST['australian_red_nepier_kg_year']);
            $sup_nep = floatval($_POST['super_nepier_kg_year']);
            $sampoorna = floatval($_POST['sampoorna_kg_year']);
            $other = floatval($_POST['other_varieties_kg_year']);

            // Validate duplicate year
            $check_stmt = $mysqli->prepare("SELECT id FROM annual_pasture_yields WHERE range_id = ? AND report_year = ?");
            $check_stmt->bind_param("ii", $range_id, $year);
            $check_stmt->execute();
            if ($check_stmt->get_result()->num_rows > 0) {
                header("Location: annual_pasture_yields.php?year=" . urlencode($selected_year) . "&status=error&msg=" . urlencode("A record for the year $year already exists."));
                exit();
            }
            $check_stmt->close();

            $insert_query = "
                INSERT INTO annual_pasture_yields 
                (district_id, range_id, report_year, co3_kg_year, co4_kg_year, co5_kg_year, 
                 australian_red_nepier_kg_year, super_nepier_kg_year, sampoorna_kg_year, other_varieties_kg_year, created_by)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ";
            $stmt = $mysqli->prepare($insert_query);
            if ($stmt) {
                $stmt->bind_param("iiidddddddi", $district_id, $range_id, $year, $co3, $co4, $co5, 
                                  $aus_red, $sup_nep, $sampoorna, $other, $user_id);
                if ($stmt->execute()) {
                    header("Location: annual_pasture_yields.php?year=all&status=success&msg=" . urlencode("Pasture yields added successfully."));
                } else {
                    header("Location: annual_pasture_yields.php?year=" . urlencode($selected_year) . "&status=error&msg=" . urlencode("Failed to write to database: " . $stmt->error));
                }
                $stmt->close();
            } else {
                header("Location: annual_pasture_yields.php?year=" . urlencode($selected_year) . "&status=error&msg=" . urlencode("Query preparation failed."));
            }
            exit();

        } elseif ($_POST['action'] === 'edit') {
            $id = intval($_POST['id']);
            $year = intval($_POST['report_year']);
            $co3 = floatval($_POST['co3_kg_year']);
            $co4 = floatval($_POST['co4_kg_year']);
            $co5 = floatval($_POST['co5_kg_year']);
            $aus_red = floatval($_POST['australian_red_nepier_kg_year']);
            $sup_nep = floatval($_POST['super_nepier_kg_year']);
            $sampoorna = floatval($_POST['sampoorna_kg_year']);
            $other = floatval($_POST['other_varieties_kg_year']);

            $update_query = "
                UPDATE annual_pasture_yields 
                SET report_year = ?, co3_kg_year = ?, co4_kg_year = ?, co5_kg_year = ?, 
                    australian_red_nepier_kg_year = ?, super_nepier_kg_year = ?, 
                    sampoorna_kg_year = ?, other_varieties_kg_year = ?
                WHERE id = ? AND range_id = ?
            ";
            $stmt = $mysqli->prepare($update_query);
            if ($stmt) {
                $stmt->bind_param("idddddddii", $year, $co3, $co4, $co5, $aus_red, $sup_nep, 
                                  $sampoorna, $other, $id, $range_id);
                if ($stmt->execute()) {
                    header("Location: annual_pasture_yields.php?year=all&status=success&msg=" . urlencode("Pasture yields updated successfully."));
                } else {
                    header("Location: annual_pasture_yields.php?year=" . urlencode($selected_year) . "&status=error&msg=" . urlencode("Failed to update database: " . $stmt->error));
                }
                $stmt->close();
            } else {
                header("Location: annual_pasture_yields.php?year=" . urlencode($selected_year) . "&status=error&msg=" . urlencode("Query preparation failed."));
            }
            exit();
        }
    }
}

if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $stmt = $mysqli->prepare("DELETE FROM annual_pasture_yields WHERE id = ? AND range_id = ?");
    if ($stmt) {
        $stmt->bind_param("ii", $id, $range_id);
        if ($stmt->execute()) {
            header("Location: annual_pasture_yields.php?year=" . urlencode($selected_year) . "&status=success&msg=" . urlencode("Record deleted successfully."));
        } else {
            header("Location: annual_pasture_yields.php?year=" . urlencode($selected_year) . "&status=error&msg=" . urlencode("Failed to delete record."));
        }
        $stmt->close();
    }
    exit();
}

// Fetch records matching year filter and range
$records = [];
if (!empty($range_id)) {
    if ($selected_year === 'all') {
        $stmt = $mysqli->prepare("SELECT * FROM annual_pasture_yields WHERE range_id = ? ORDER BY report_year DESC, id DESC");
        if ($stmt) {
            $stmt->bind_param("i", $range_id);
            $stmt->execute();
            $res = $stmt->get_result();
            while ($row = $res->fetch_assoc()) {
                $records[] = $row;
            }
            $stmt->close();
        }
    } else {
        $stmt = $mysqli->prepare("SELECT * FROM annual_pasture_yields WHERE range_id = ? AND report_year = ? ORDER BY report_year DESC, id DESC");
        if ($stmt) {
            $stmt->bind_param("ii", $range_id, $selected_year);
            $stmt->execute();
            $res = $stmt->get_result();
            while ($row = $res->fetch_assoc()) {
                $records[] = $row;
            }
            $stmt->close();
        }
    }
}

// Summary stats
$summary = [
    'total_yield_kg' => 0,
    'highest_variety' => 'N/A',
    'highest_qty' => 0
];
foreach ($records as $r) {
    $sum = $r['co3_kg_year'] + $r['co4_kg_year'] + $r['co5_kg_year'] + $r['australian_red_nepier_kg_year'] + $r['super_nepier_kg_year'] + $r['sampoorna_kg_year'] + $r['other_varieties_kg_year'];
    $summary['total_yield_kg'] += $sum;

    $candidates = [
        'CO3' => $r['co3_kg_year'],
        'CO4' => $r['co4_kg_year'],
        'CO5' => $r['co5_kg_year'],
        'Aus Red' => $r['australian_red_nepier_kg_year'],
        'Super Nepier' => $r['super_nepier_kg_year'],
        'Sampoorna' => $r['sampoorna_kg_year'],
        'Other' => $r['other_varieties_kg_year']
    ];
    foreach ($candidates as $name => $val) {
        if ($val > $summary['highest_qty']) {
            $summary['highest_qty'] = $val;
            $summary['highest_variety'] = $name;
        }
    }
}

require_once '../../../includes/header.php';
?>

<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.2/css/buttons.bootstrap5.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
<link rel="stylesheet" href="../../../assets/css/veterinary.css">



        <div class="mb-4 d-flex justify-content-between align-items-center">
            <div>
                <h2 class="h4 fw-bold mb-1" style="color: #370709;">Annual Pasture Yields</h2>
                <p class="text-muted small mb-0">Record and track annual pasture yield statistics for <strong class="text-dark"><?= htmlspecialchars($range_name) ?></strong> (<?= htmlspecialchars($district_name) ?> District)</p>
            </div>
            
            <div class="d-flex align-items-center gap-2">
                <form method="GET" class="d-flex align-items-center gap-2">
                    <label class="small fw-bold text-muted mb-0">Year:</label>
                    <select name="year" class="form-select form-select-sm" onchange="this.form.submit()" style="width: 110px;">
                        <option value="all" <?= ($selected_year === 'all') ? 'selected' : '' ?>>All Years</option>
                        <?php
                        $curr_year = intval(date('Y'));
                        for ($y = $curr_year - 5; $y <= $curr_year + 5; $y++) {
                            $sel = ($selected_year !== 'all' && $y === intval($selected_year)) ? 'selected' : '';
                            echo "<option value=\"$y\" $sel>$y</option>";
                        }
                        ?>
                    </select>
                </form>
            </div>
        </div>

        <!-- STATS CARD GROUP -->
        <div class="row g-3 mb-4">
            <div class="col-6 col-lg-3">
                <div class="card shadow-sm border-0 border-start border-primary border-4 text-center">
                    <div class="card-body py-3">
                        <span class="text-muted small text-uppercase fw-bold">Active Year</span>
                        <h4 class="mb-0 fw-bold text-primary mt-1"><?= ($selected_year === 'all') ? 'All Years' : htmlspecialchars($selected_year) ?></h4>
                    </div>
                </div>
            </div>
            <div class="col-6 col-lg-3">
                <div class="card shadow-sm border-0 border-start border-success border-4 text-center">
                    <div class="card-body py-3">
                        <span class="text-muted small text-uppercase fw-bold">Total Yield</span>
                        <h4 class="mb-0 fw-bold text-success mt-1"><?= number_format($summary['total_yield_kg'], 2) ?> Kg</h4>
                    </div>
                </div>
            </div>
            <div class="col-6 col-lg-3">
                <div class="card shadow-sm border-0 border-start border-info border-4 text-center">
                    <div class="card-body py-3">
                        <span class="text-muted small text-uppercase fw-bold">Highest Yielding Variety</span>
                        <h4 class="mb-0 fw-bold text-info mt-1"><?= htmlspecialchars($summary['highest_variety']) ?></h4>
                    </div>
                </div>
            </div>
            <div class="col-6 col-lg-3">
                <div class="card shadow-sm border-0 border-start border-warning border-4 text-center">
                    <div class="card-body py-3">
                        <span class="text-muted small text-uppercase fw-bold">Max Variety Qty</span>
                        <h4 class="mb-0 fw-bold text-warning mt-1"><?= number_format($summary['highest_qty'], 2) ?> Kg</h4>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-4 mb-4">
            <div class="col-12">
                <div class="card shadow-sm border-0">
                    <div class="card-header bg-white py-3 border-0">
                        <h6 class="mb-0 fw-bold text-dark"><i class="bi bi-grid-3x3-gap-fill me-2"></i>Quick Actions</h6>
                    </div>
                    <div class="card-body pt-0">
                        <div class="row g-3">
                            <div class="col-md-3">
                                <button class="btn btn-primary w-100 py-3 text-light border-0 shadow-sm d-flex flex-column align-items-center justify-content-center" style="background-color: #820100; min-height: 105px;" data-bs-toggle="modal" data-bs-target="#addYieldModal">
                                    <i class="bi bi-plus-circle fs-3 mb-1"></i>
                                    <span class="small fw-bold text-uppercase">Add Yield Record</span>
                                </button>
                            </div>
                            <div class="col-md-3">
                                <a href="range_statistics.php" class="btn btn-outline-secondary w-100 py-3 d-flex flex-column align-items-center justify-content-center" style="min-height: 105px;">
                                    <i class="bi bi-arrow-left-circle fs-3 mb-1"></i>
                                    <span class="small fw-bold text-uppercase">Back to Statistics</span>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- RECORDS LIST TABLE -->
        <div class="card shadow-sm border-0">
            <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center border-bottom">
                <h5 class="card-title mb-0 fw-bold text-dark"><i class="bi bi-table me-2"></i>Pasture Yields Log - <?= ($selected_year === 'all') ? 'All Years' : htmlspecialchars($selected_year) ?></h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0" id="yieldTable" style="min-width: 1200px;">
                        <thead class="table-light text-secondary small uppercase">
                            <tr>
                                <th class="text-center">Year</th>
                                <th class="text-end">CO3 (Kg/year)</th>
                                <th class="text-end">CO4 (Kg/year)</th>
                                <th class="text-end">CO5 (Kg/year)</th>
                                <th class="text-end">Australian Red Nepier (Kg/year)</th>
                                <th class="text-end">Super Nepier (Kg/year)</th>
                                <th class="text-end">Sampoorna (Kg/year)</th>
                                <th class="text-end">Other Varieties (Kg/year)</th>
                                <th class="text-center" style="width: 10%">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="small">
                            <?php if (empty($records)): ?>
                                <tr>
                                    <td colspan="9" class="text-center py-4 text-muted">
                                        <i class="bi bi-inbox fs-3 d-block mb-2"></i>
                                        No records located <?= ($selected_year === 'all') ? 'in the database.' : 'for the selected year ' . htmlspecialchars($selected_year) . '.' ?>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($records as $row): ?>
                                    <tr 
                                        data-id="<?= $row['id'] ?>"
                                        data-year="<?= htmlspecialchars($row['report_year']) ?>"
                                        data-co3="<?= htmlspecialchars($row['co3_kg_year']) ?>"
                                        data-co4="<?= htmlspecialchars($row['co4_kg_year']) ?>"
                                        data-co5="<?= htmlspecialchars($row['co5_kg_year']) ?>"
                                        data-aus_red="<?= htmlspecialchars($row['australian_red_nepier_kg_year']) ?>"
                                        data-sup_nep="<?= htmlspecialchars($row['super_nepier_kg_year']) ?>"
                                        data-sampoorna="<?= htmlspecialchars($row['sampoorna_kg_year']) ?>"
                                        data-other="<?= htmlspecialchars($row['other_varieties_kg_year']) ?>">
                                        <td class="text-center fw-bold"><?= htmlspecialchars($row['report_year']) ?></td>
                                        <td class="text-end font-monospace"><?= number_format($row['co3_kg_year'], 2) ?></td>
                                        <td class="text-end font-monospace"><?= number_format($row['co4_kg_year'], 2) ?></td>
                                        <td class="text-end font-monospace"><?= number_format($row['co5_kg_year'], 2) ?></td>
                                        <td class="text-end font-monospace"><?= number_format($row['australian_red_nepier_kg_year'], 2) ?></td>
                                        <td class="text-end font-monospace"><?= number_format($row['super_nepier_kg_year'], 2) ?></td>
                                        <td class="text-end font-monospace"><?= number_format($row['sampoorna_kg_year'], 2) ?></td>
                                        <td class="text-end font-monospace"><?= number_format($row['other_varieties_kg_year'], 2) ?></td>
                                        <td class="text-center">
                                            <button class="btn btn-sm btn-outline-primary btn-edit" title="Edit"><i class="bi bi-pencil-square"></i></button>
                                            <a href="annual_pasture_yields.php?year=<?= urlencode($selected_year) ?>&action=delete&id=<?= $row['id'] ?>" class="btn btn-sm btn-outline-danger btn-delete" title="Delete"><i class="bi bi-trash"></i></a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    </main>
</div>

<!-- Modal: Add Record -->
<div class="modal fade" id="addYieldModal" tabindex="-1" aria-labelledby="addYieldModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <form method="POST">
            <input type="hidden" name="action" value="add">
            <div class="modal-content">
                <div class="modal-header" style="background-color: #370709; color: white;">
                    <h5 class="modal-title" id="addYieldModalLabel"><i class="bi bi-plus-circle me-2"></i>Add Pasture Yield</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-12">
                            <label class="form-label fw-bold">Report Year</label>
                            <input type="number" name="report_year" class="form-control" value="<?= date('Y') ?>" required>
                        </div>
                        <div class="col-md-12">
                            <label class="form-label">CO3 (Kg/year)</label>
                            <input type="number" step="0.01" name="co3_kg_year" class="form-control" value="0.00">
                        </div>
                        <div class="col-md-12">
                            <label class="form-label">CO4 (Kg/year)</label>
                            <input type="number" step="0.01" name="co4_kg_year" class="form-control" value="0.00">
                        </div>
                        <div class="col-md-12">
                            <label class="form-label">CO5 (Kg/year)</label>
                            <input type="number" step="0.01" name="co5_kg_year" class="form-control" value="0.00">
                        </div>
                        <div class="col-md-12">
                            <label class="form-label">Australian Red Nepier (Kg/year)</label>
                            <input type="number" step="0.01" name="australian_red_nepier_kg_year" class="form-control" value="0.00">
                        </div>
                        <div class="col-md-12">
                            <label class="form-label">Super Nepier (Kg/year)</label>
                            <input type="number" step="0.01" name="super_nepier_kg_year" class="form-control" value="0.00">
                        </div>
                        <div class="col-md-12">
                            <label class="form-label">Sampoorna (Kg/year)</label>
                            <input type="number" step="0.01" name="sampoorna_kg_year" class="form-control" value="0.00">
                        </div>
                        <div class="col-md-12">
                            <label class="form-label">Other Varieties (Kg/year)</label>
                            <input type="number" step="0.01" name="other_varieties_kg_year" class="form-control" value="0.00">
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success">Save Record</button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Modal: Edit Record -->
<div class="modal fade" id="editYieldModal" tabindex="-1" aria-labelledby="editYieldModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <form method="POST">
            <input type="hidden" name="action" value="edit">
            <input type="hidden" name="id" id="edit_id">
            <div class="modal-content">
                <div class="modal-header" style="background-color: #370709; color: white;">
                    <h5 class="modal-title" id="editYieldModalLabel"><i class="bi bi-pencil-square me-2"></i>Edit Pasture Yield</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-12">
                            <label class="form-label fw-bold">Report Year</label>
                            <input type="number" name="report_year" id="edit_report_year" class="form-control" required>
                        </div>
                        <div class="col-md-12">
                            <label class="form-label">CO3 (Kg/year)</label>
                            <input type="number" step="0.01" name="co3_kg_year" id="edit_co3" class="form-control">
                        </div>
                        <div class="col-md-12">
                            <label class="form-label">CO4 (Kg/year)</label>
                            <input type="number" step="0.01" name="co4_kg_year" id="edit_co4" class="form-control">
                        </div>
                        <div class="col-md-12">
                            <label class="form-label">CO5 (Kg/year)</label>
                            <input type="number" step="0.01" name="co5_kg_year" id="edit_co5" class="form-control">
                        </div>
                        <div class="col-md-12">
                            <label class="form-label">Australian Red Nepier (Kg/year)</label>
                            <input type="number" step="0.01" name="australian_red_nepier_kg_year" id="edit_aus_red" class="form-control">
                        </div>
                        <div class="col-md-12">
                            <label class="form-label">Super Nepier (Kg/year)</label>
                            <input type="number" step="0.01" name="super_nepier_kg_year" id="edit_sup_nep" class="form-control">
                        </div>
                        <div class="col-md-12">
                            <label class="form-label">Sampoorna (Kg/year)</label>
                            <input type="number" step="0.01" name="sampoorna_kg_year" id="edit_sampoorna" class="form-control">
                        </div>
                        <div class="col-md-12">
                            <label class="form-label">Other Varieties (Kg/year)</label>
                            <input type="number" step="0.01" name="other_varieties_kg_year" id="edit_other" class="form-control">
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update Record</button>
                </div>
            </div>
        </form>
    </div>
</div>

<?php
$pageScripts = '
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
$(document).ready(function() {
    $("#yieldTable").DataTable({
        "order": [[0, "desc"]],
        "pageLength": 10,
        "dom": "Bfrtip",
        "buttons": [
            {
                extend: "csv",
                text: "<i class=\\"bi bi-file-earmark-spreadsheet\\"></i> CSV",
                className: "btn btn-sm btn-success me-2"
            },
            {
                extend: "pdf",
                text: "<i class=\\"bi bi-file-pdf\\"></i> PDF",
                className: "btn btn-sm btn-danger me-2"
            },
            {
                extend: "print",
                text: "<i class=\\"bi bi-printer\\"></i> Print",
                className: "btn btn-sm btn-dark"
            }
        ]
    });

    var urlParams = new URLSearchParams(window.location.search);
    var status = urlParams.get(\'status\');
    var msg = urlParams.get(\'msg\') || \'\';

    if (status === \'success\') {
        Swal.fire({
            icon: \'success\',
            title: \'Success!\',
            text: msg ? msg : \'Operation completed successfully.\',
            confirmButtonColor: \'#370709\'
        });
        window.history.replaceState({}, document.title, window.location.pathname);
    } else if (status === \'error\') {
        Swal.fire({
            icon: \'error\',
            title: \'Operation Failed\',
            text: msg ? msg : \'Could not process database action.\',
            confirmButtonColor: \'#370709\'
        });
        window.history.replaceState({}, document.title, window.location.pathname);
    }

    $(document).on(\'click\', \'.btn-edit\', function() {
        var $row = $(this).closest(\'tr\');
        $(\'#edit_id\').val($row.data(\'id\'));
        $(\'#edit_report_year\').val($row.data(\'year\'));
        $(\'#edit_co3\').val($row.data(\'co3\'));
        $(\'#edit_co4\').val($row.data(\'co4\'));
        $(\'#edit_co5\').val($row.data(\'co5\'));
        $(\'#edit_aus_red\').val($row.data(\'aus_red\'));
        $(\'#edit_sup_nep\').val($row.data(\'sup_nep\'));
        $(\'#edit_sampoorna\').val($row.data(\'sampoorna\'));
        $(\'#edit_other\').val($row.data(\'other\'));

        new bootstrap.Modal(document.getElementById(\'editYieldModal\')).show();
    });

    $(document).on(\'click\', \'.btn-delete\', function(e) {
        e.preventDefault();
        var deleteUrl = $(this).attr(\'href\');
        var $row = $(this).closest(\'tr\');
        var year = $row.data(\'year\');

        Swal.fire({
            icon: \'warning\',
            title: \'Delete Yield Record?\',
            html: \'Are you sure you want to permanently delete the yield record for year <strong>\' + year + \'</strong>?<br>This action cannot be undone.\',
            showCancelButton: true,
            confirmButtonColor: \'#d33\',
            cancelButtonColor: \'#6c757d\',
            confirmButtonText: \'Yes, Delete\',
            cancelButtonText: \'Cancel\'
        }).then(function(result) {
            if (result.isConfirmed) {
                window.location.href = deleteUrl;
            }
        });
    });
});
</script>
';
require_once '../../../includes/footer.php';
?>
