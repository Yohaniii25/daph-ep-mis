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
            $cow_milk = floatval($_POST['cow_milk_lit_day']);
            $buffalo_milk = floatval($_POST['buffalo_milk_lit_day']);
            $goat_milk = floatval($_POST['goat_milk_lit_day']);
            $chicks = intval($_POST['chicks_production_no_day']);
            $eggs = intval($_POST['eggs_production_no_day']);
            $beef = floatval($_POST['beef_kg_day']);
            $mutton = floatval($_POST['mutton_kg_day']);
            $chicken = floatval($_POST['chicken_kg_day']);
            $curd = floatval($_POST['curd_lit_day']);
            $ghee = floatval($_POST['ghee_lit_day']);
            $yoghurt = floatval($_POST['yoghurt_lit_day']);

            // Check if year already exists for this range
            $check_stmt = $mysqli->prepare("SELECT id FROM annual_production_levels WHERE range_id = ? AND report_year = ?");
            $check_stmt->bind_param("ii", $range_id, $year);
            $check_stmt->execute();
            $check_result = $check_stmt->get_result();
            if ($check_result->num_rows > 0) {
                header("Location: annual_production_levels.php?year=" . urlencode($selected_year) . "&status=error&msg=" . urlencode("A record for the year $year already exists."));
                exit();
            }
            $check_stmt->close();

            $insert_query = "
                INSERT INTO annual_production_levels 
                (district_id, range_id, report_year, cow_milk_lit_day, buffalo_milk_lit_day, goat_milk_lit_day, 
                 chicks_production_no_day, eggs_production_no_day, beef_kg_day, mutton_kg_day, chicken_kg_day, 
                 curd_lit_day, ghee_lit_day, yoghurt_lit_day, created_by)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ";
            $stmt = $mysqli->prepare($insert_query);
            if ($stmt) {
                // district_id, range_id, report_year are 'iii'
                // cow_milk, buffalo_milk, goat_milk are 'ddd'
                // chicks, eggs are 'ii'
                // beef, mutton, chicken, curd, ghee, yoghurt are 'dddddd'
                // created_by is 'i'
                $stmt->bind_param("iiidddiiddddddi", $district_id, $range_id, $year, $cow_milk, $buffalo_milk, $goat_milk, 
                                  $chicks, $eggs, $beef, $mutton, $chicken, $curd, $ghee, $yoghurt, $user_id);
                if ($stmt->execute()) {
                    header("Location: annual_production_levels.php?year=all&status=success&msg=" . urlencode("Production levels added successfully."));
                } else {
                    header("Location: annual_production_levels.php?year=" . urlencode($selected_year) . "&status=error&msg=" . urlencode("Failed to write to database: " . $stmt->error));
                }
                $stmt->close();
            } else {
                header("Location: annual_production_levels.php?year=" . urlencode($selected_year) . "&status=error&msg=" . urlencode("Query preparation failed."));
            }
            exit();

        } elseif ($_POST['action'] === 'edit') {
            $id = intval($_POST['id']);
            $year = intval($_POST['report_year']);
            $cow_milk = floatval($_POST['cow_milk_lit_day']);
            $buffalo_milk = floatval($_POST['buffalo_milk_lit_day']);
            $goat_milk = floatval($_POST['goat_milk_lit_day']);
            $chicks = intval($_POST['chicks_production_no_day']);
            $eggs = intval($_POST['eggs_production_no_day']);
            $beef = floatval($_POST['beef_kg_day']);
            $mutton = floatval($_POST['mutton_kg_day']);
            $chicken = floatval($_POST['chicken_kg_day']);
            $curd = floatval($_POST['curd_lit_day']);
            $ghee = floatval($_POST['ghee_lit_day']);
            $yoghurt = floatval($_POST['yoghurt_lit_day']);

            $update_query = "
                UPDATE annual_production_levels 
                SET report_year = ?, cow_milk_lit_day = ?, buffalo_milk_lit_day = ?, goat_milk_lit_day = ?, 
                    chicks_production_no_day = ?, eggs_production_no_day = ?, beef_kg_day = ?, 
                    mutton_kg_day = ?, chicken_kg_day = ?, curd_lit_day = ?, ghee_lit_day = ?, 
                    yoghurt_lit_day = ?
                WHERE id = ? AND range_id = ?
            ";
            $stmt = $mysqli->prepare($update_query);
            if ($stmt) {
                $stmt->bind_param("idddiiddddddii", $year, $cow_milk, $buffalo_milk, $goat_milk, 
                                  $chicks, $eggs, $beef, $mutton, $chicken, $curd, $ghee, $yoghurt, $id, $range_id);
                if ($stmt->execute()) {
                    header("Location: annual_production_levels.php?year=all&status=success&msg=" . urlencode("Production levels updated successfully."));
                } else {
                    header("Location: annual_production_levels.php?year=" . urlencode($selected_year) . "&status=error&msg=" . urlencode("Failed to update database: " . $stmt->error));
                }
                $stmt->close();
            } else {
                header("Location: annual_production_levels.php?year=" . urlencode($selected_year) . "&status=error&msg=" . urlencode("Query preparation failed."));
            }
            exit();
        }
    }
}

if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $stmt = $mysqli->prepare("DELETE FROM annual_production_levels WHERE id = ? AND range_id = ?");
    if ($stmt) {
        $stmt->bind_param("ii", $id, $range_id);
        if ($stmt->execute()) {
            header("Location: annual_production_levels.php?year=" . urlencode($selected_year) . "&status=success&msg=" . urlencode("Record deleted successfully."));
        } else {
            header("Location: annual_production_levels.php?year=" . urlencode($selected_year) . "&status=error&msg=" . urlencode("Failed to delete record."));
        }
        $stmt->close();
    }
    exit();
}

// Fetch records matching year filter and range
$records = [];
if (!empty($range_id)) {
    if ($selected_year === 'all') {
        $stmt = $mysqli->prepare("SELECT * FROM annual_production_levels WHERE range_id = ? ORDER BY report_year DESC, id DESC");
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
        $stmt = $mysqli->prepare("SELECT * FROM annual_production_levels WHERE range_id = ? AND report_year = ? ORDER BY report_year DESC, id DESC");
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
    'avg_cow_milk' => 0,
    'total_eggs' => 0,
    'total_beef' => 0
];
if (count($records) > 0) {
    $cow_milk_sum = 0;
    foreach ($records as $r) {
        $cow_milk_sum += $r['cow_milk_lit_day'];
        $summary['total_eggs'] += $r['eggs_production_no_day'];
        $summary['total_beef'] += $r['beef_kg_day'];
    }
    $summary['avg_cow_milk'] = $cow_milk_sum / count($records);
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
                <h2 class="h4 fw-bold mb-1" style="color: #370709;">Annual Production Levels</h2>
                <p class="text-muted small mb-0">Record and track daily production rates at range level for <strong class="text-dark"><?= htmlspecialchars($range_name) ?></strong> (<?= htmlspecialchars($district_name) ?> District)</p>
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
                        <span class="text-muted small text-uppercase fw-bold">Cow Milk (Daily Avg)</span>
                        <h4 class="mb-0 fw-bold text-success mt-1"><?= number_format($summary['avg_cow_milk'], 2) ?> L</h4>
                    </div>
                </div>
            </div>
            <div class="col-6 col-lg-3">
                <div class="card shadow-sm border-0 border-start border-info border-4 text-center">
                    <div class="card-body py-3">
                        <span class="text-muted small text-uppercase fw-bold">Eggs (Daily Avg)</span>
                        <h4 class="mb-0 fw-bold text-info mt-1"><?= number_format($summary['total_eggs']) ?> Nos</h4>
                    </div>
                </div>
            </div>
            <div class="col-6 col-lg-3">
                <div class="card shadow-sm border-0 border-start border-danger border-4 text-center">
                    <div class="card-body py-3">
                        <span class="text-muted small text-uppercase fw-bold">Beef Production</span>
                        <h4 class="mb-0 fw-bold text-danger mt-1"><?= number_format($summary['total_beef'], 2) ?> Kg</h4>
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
                                <button class="btn btn-primary w-100 py-3 text-light border-0 shadow-sm d-flex flex-column align-items-center justify-content-center" style="background-color: #820100; min-height: 105px;" data-bs-toggle="modal" data-bs-target="#addProdLvlModal">
                                    <i class="bi bi-plus-circle fs-3 mb-1"></i>
                                    <span class="small fw-bold text-uppercase">Add Production Levels</span>
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
                <h5 class="card-title mb-0 fw-bold text-dark"><i class="bi bi-table me-2"></i>Production Levels Log - <?= ($selected_year === 'all') ? 'All Years' : htmlspecialchars($selected_year) ?></h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0" id="prodLvlTable" style="min-width: 1500px;">
                        <thead class="table-light text-secondary small uppercase">
                            <tr>
                                <th class="text-center">Year</th>
                                <th class="text-end">Cow Milk (L/day)</th>
                                <th class="text-end">Buffalo Milk (L/day)</th>
                                <th class="text-end">Goat Milk (L/day)</th>
                                <th class="text-end">Chicks Prod (Nos/day)</th>
                                <th class="text-end">Eggs Prod (Nos/day)</th>
                                <th class="text-end">Beef (Kg/day)</th>
                                <th class="text-end">Mutton (Kg/day)</th>
                                <th class="text-end">Chicken (Kg/day)</th>
                                <th class="text-end">Curd (L/day)</th>
                                <th class="text-end">Ghee (L/day)</th>
                                <th class="text-end">Yoghurt (L/day)</th>
                                <th class="text-center" style="width: 10%">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="small">
                            <?php if (empty($records)): ?>
                                <tr>
                                    <td colspan="13" class="text-center py-4 text-muted">
                                        <i class="bi bi-inbox fs-3 d-block mb-2"></i>
                                        No records located <?= ($selected_year === 'all') ? 'in the database.' : 'for the selected year ' . htmlspecialchars($selected_year) . '.' ?>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($records as $row): ?>
                                    <tr 
                                        data-id="<?= $row['id'] ?>"
                                        data-year="<?= htmlspecialchars($row['report_year']) ?>"
                                        data-cow_milk="<?= htmlspecialchars($row['cow_milk_lit_day']) ?>"
                                        data-buffalo_milk="<?= htmlspecialchars($row['buffalo_milk_lit_day']) ?>"
                                        data-goat_milk="<?= htmlspecialchars($row['goat_milk_lit_day']) ?>"
                                        data-chicks="<?= htmlspecialchars($row['chicks_production_no_day']) ?>"
                                        data-eggs="<?= htmlspecialchars($row['eggs_production_no_day']) ?>"
                                        data-beef="<?= htmlspecialchars($row['beef_kg_day']) ?>"
                                        data-mutton="<?= htmlspecialchars($row['mutton_kg_day']) ?>"
                                        data-chicken="<?= htmlspecialchars($row['chicken_kg_day']) ?>"
                                        data-curd="<?= htmlspecialchars($row['curd_lit_day']) ?>"
                                        data-ghee="<?= htmlspecialchars($row['ghee_lit_day']) ?>"
                                        data-yoghurt="<?= htmlspecialchars($row['yoghurt_lit_day']) ?>">
                                        <td class="text-center fw-bold"><?= htmlspecialchars($row['report_year']) ?></td>
                                        <td class="text-end font-monospace"><?= number_format($row['cow_milk_lit_day'], 2) ?></td>
                                        <td class="text-end font-monospace"><?= number_format($row['buffalo_milk_lit_day'], 2) ?></td>
                                        <td class="text-end font-monospace"><?= number_format($row['goat_milk_lit_day'], 2) ?></td>
                                        <td class="text-end font-monospace"><?= number_format($row['chicks_production_no_day']) ?></td>
                                        <td class="text-end font-monospace"><?= number_format($row['eggs_production_no_day']) ?></td>
                                        <td class="text-end font-monospace text-danger"><?= number_format($row['beef_kg_day'], 2) ?></td>
                                        <td class="text-end font-monospace text-danger"><?= number_format($row['mutton_kg_day'], 2) ?></td>
                                        <td class="text-end font-monospace text-danger"><?= number_format($row['chicken_kg_day'], 2) ?></td>
                                        <td class="text-end font-monospace text-success"><?= number_format($row['curd_lit_day'], 2) ?></td>
                                        <td class="text-end font-monospace text-success"><?= number_format($row['ghee_lit_day'], 2) ?></td>
                                        <td class="text-end font-monospace text-success"><?= number_format($row['yoghurt_lit_day'], 2) ?></td>
                                        <td class="text-center">
                                            <button class="btn btn-sm btn-outline-primary btn-edit" title="Edit"><i class="bi bi-pencil-square"></i></button>
                                            <a href="annual_production_levels.php?year=<?= urlencode($selected_year) ?>&action=delete&id=<?= $row['id'] ?>" class="btn btn-sm btn-outline-danger btn-delete" title="Delete"><i class="bi bi-trash"></i></a>
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
<div class="modal fade" id="addProdLvlModal" tabindex="-1" aria-labelledby="addProdLvlModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <form method="POST">
            <input type="hidden" name="action" value="add">
            <div class="modal-content">
                <div class="modal-header" style="background-color: #370709; color: white;">
                    <h5 class="modal-title" id="addProdLvlModalLabel"><i class="bi bi-plus-circle me-2"></i>Add Annual Production Levels</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Report Year</label>
                            <input type="number" name="report_year" class="form-control" value="<?= date('Y') ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Cow Milk (Lit/Day)</label>
                            <input type="number" step="0.01" name="cow_milk_lit_day" class="form-control" value="0.00">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Buffalo Milk (Lit/Day)</label>
                            <input type="number" step="0.01" name="buffalo_milk_lit_day" class="form-control" value="0.00">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Goat Milk (Lit/Day)</label>
                            <input type="number" step="0.01" name="goat_milk_lit_day" class="form-control" value="0.00">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Chicks Production (Nos/Day)</label>
                            <input type="number" name="chicks_production_no_day" class="form-control" value="0">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Eggs Production (Nos/Day)</label>
                            <input type="number" name="eggs_production_no_day" class="form-control" value="0">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Beef (Kg/Day)</label>
                            <input type="number" step="0.01" name="beef_kg_day" class="form-control" value="0.00">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Mutton (Kg/Day)</label>
                            <input type="number" step="0.01" name="mutton_kg_day" class="form-control" value="0.00">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Chicken (Kg/Day)</label>
                            <input type="number" step="0.01" name="chicken_kg_day" class="form-control" value="0.00">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Curd Production (Lit/Day)</label>
                            <input type="number" step="0.01" name="curd_lit_day" class="form-control" value="0.00">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Ghee Production (Lit/Day)</label>
                            <input type="number" step="0.01" name="ghee_lit_day" class="form-control" value="0.00">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Yoghurt Production (Lit/Day)</label>
                            <input type="number" step="0.01" name="yoghurt_lit_day" class="form-control" value="0.00">
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
<div class="modal fade" id="editProdLvlModal" tabindex="-1" aria-labelledby="editProdLvlModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <form method="POST">
            <input type="hidden" name="action" value="edit">
            <input type="hidden" name="id" id="edit_id">
            <div class="modal-content">
                <div class="modal-header" style="background-color: #370709; color: white;">
                    <h5 class="modal-title" id="editProdLvlModalLabel"><i class="bi bi-pencil-square me-2"></i>Edit Annual Production Levels</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Report Year</label>
                            <input type="number" name="report_year" id="edit_report_year" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Cow Milk (Lit/Day)</label>
                            <input type="number" step="0.01" name="cow_milk_lit_day" id="edit_cow_milk" class="form-control">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Buffalo Milk (Lit/Day)</label>
                            <input type="number" step="0.01" name="buffalo_milk_lit_day" id="edit_buffalo_milk" class="form-control">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Goat Milk (Lit/Day)</label>
                            <input type="number" step="0.01" name="goat_milk_lit_day" id="edit_goat_milk" class="form-control">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Chicks Production (Nos/Day)</label>
                            <input type="number" name="chicks_production_no_day" id="edit_chicks" class="form-control">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Eggs Production (Nos/Day)</label>
                            <input type="number" name="eggs_production_no_day" id="edit_eggs" class="form-control">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Beef (Kg/Day)</label>
                            <input type="number" step="0.01" name="beef_kg_day" id="edit_beef" class="form-control">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Mutton (Kg/Day)</label>
                            <input type="number" step="0.01" name="mutton_kg_day" id="edit_mutton" class="form-control">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Chicken (Kg/Day)</label>
                            <input type="number" step="0.01" name="chicken_kg_day" id="edit_chicken" class="form-control">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Curd Production (Lit/Day)</label>
                            <input type="number" step="0.01" name="curd_lit_day" id="edit_curd" class="form-control">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Ghee Production (Lit/Day)</label>
                            <input type="number" step="0.01" name="ghee_lit_day" id="edit_ghee" class="form-control">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Yoghurt Production (Lit/Day)</label>
                            <input type="number" step="0.01" name="yoghurt_lit_day" id="edit_yoghurt" class="form-control">
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
    $("#prodLvlTable").DataTable({
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
        $(\'#edit_cow_milk\').val($row.data(\'cow_milk\'));
        $(\'#edit_buffalo_milk\').val($row.data(\'buffalo_milk\'));
        $(\'#edit_goat_milk\').val($row.data(\'goat_milk\'));
        $(\'#edit_chicks\').val($row.data(\'chicks\'));
        $(\'#edit_eggs\').val($row.data(\'eggs\'));
        $(\'#edit_beef\').val($row.data(\'beef\'));
        $(\'#edit_mutton\').val($row.data(\'mutton\'));
        $(\'#edit_chicken\').val($row.data(\'chicken\'));
        $(\'#edit_curd\').val($row.data(\'curd\'));
        $(\'#edit_ghee\').val($row.data(\'ghee\'));
        $(\'#edit_yoghurt\').val($row.data(\'yoghurt\'));

        new bootstrap.Modal(document.getElementById(\'editProdLvlModal\')).show();
    });

    $(document).on(\'click\', \'.btn-delete\', function(e) {
        e.preventDefault();
        var deleteUrl = $(this).attr(\'href\');
        var $row = $(this).closest(\'tr\');
        var year = $row.data(\'year\');

        Swal.fire({
            icon: \'warning\',
            title: \'Delete Production Levels Record?\',
            html: \'Are you sure you want to permanently delete the production level record for year <strong>\' + year + \'</strong>?<br>This action cannot be undone.\',
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
