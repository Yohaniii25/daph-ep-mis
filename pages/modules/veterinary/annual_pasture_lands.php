<?php
session_start();
require_once '../../../config/db_connect.php';

// Access check: allow logged-in users
if (!isset($_SESSION['logged_in'])) {
    header("Location: ../../../index.php");
    exit();
}

$user_id = $_SESSION['user_id'] ?? null;
$user_range_name = $_SESSION['range_name'] ?? 'General Range';

// Ensure target database table exists
$table_init_sql = "
CREATE TABLE IF NOT EXISTS pasture_fodder_lands (
    id INT AUTO_INCREMENT PRIMARY KEY,
    vs_range VARCHAR(255) NOT NULL,
    report_year INT DEFAULT 2024,
    
    -- Pasture Land Fields
    pasture_families_quarter_ac INT DEFAULT 0 COMMENT '1/4 Ac',
    pasture_families_half_ac INT DEFAULT 0 COMMENT '1/2 Ac',
    pasture_families_one_ac INT DEFAULT 0 COMMENT '1 Ac',
    pasture_families_gt_one_ac INT DEFAULT 0 COMMENT '> 1Ac',
    pasture_total_acre DECIMAL(10,2) DEFAULT 0,
    pasture_total_families INT DEFAULT 0,
    
    -- Fodder Land Fields
    fodder_families_quarter_ac INT DEFAULT 0 COMMENT '1/4 Ac',
    fodder_families_half_ac INT DEFAULT 0 COMMENT '1/2 Ac',
    fodder_families_one_ac INT DEFAULT 0 COMMENT '1 Ac',
    fodder_families_gt_one_ac INT DEFAULT 0 COMMENT '> 1Ac',
    fodder_total_acre DECIMAL(10,2) DEFAULT 0,
    fodder_total_families INT DEFAULT 0,
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);";
$mysqli->query($table_init_sql);

// Auto-migrate: add report_year column if missing
$chk_col = $mysqli->query("SHOW COLUMNS FROM pasture_fodder_lands LIKE 'report_year'");
if ($chk_col && $chk_col->num_rows == 0) {
    $mysqli->query("ALTER TABLE pasture_fodder_lands ADD COLUMN report_year INT DEFAULT 2024 AFTER vs_range");
}

// Handle GET year filter (default to 'all' so all records are displayed)
$selected_year = 'all';
if (isset($_GET['year'])) {
    if ($_GET['year'] === 'all' || $_GET['year'] === '') {
        $selected_year = 'all';
    } else {
        $selected_year = intval($_GET['year']);
    }
}

// Handle POST submissions (INSERT and UPDATE)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        if ($_POST['action'] === 'add') {
            $vs_range = trim($_POST['vs_range'] ?? '');
            $report_year = intval($_POST['report_year'] ?? date('Y'));

            // Pasture fields
            $pasture_families_quarter_ac = intval($_POST['pasture_families_quarter_ac'] ?? 0);
            $pasture_families_half_ac = intval($_POST['pasture_families_half_ac'] ?? 0);
            $pasture_families_one_ac = intval($_POST['pasture_families_one_ac'] ?? 0);
            $pasture_families_gt_one_ac = intval($_POST['pasture_families_gt_one_ac'] ?? 0);
            $pasture_total_acre = floatval($_POST['pasture_total_acre'] ?? 0);
            $pasture_total_families = intval($_POST['pasture_total_families'] ?? 0);

            // Fodder fields
            $fodder_families_quarter_ac = intval($_POST['fodder_families_quarter_ac'] ?? 0);
            $fodder_families_half_ac = intval($_POST['fodder_families_half_ac'] ?? 0);
            $fodder_families_one_ac = intval($_POST['fodder_families_one_ac'] ?? 0);
            $fodder_families_gt_one_ac = intval($_POST['fodder_families_gt_one_ac'] ?? 0);
            $fodder_total_acre = floatval($_POST['fodder_total_acre'] ?? 0);
            $fodder_total_families = intval($_POST['fodder_total_families'] ?? 0);

            $insert_query = "
                INSERT INTO pasture_fodder_lands 
                (vs_range, report_year, 
                 pasture_families_quarter_ac, pasture_families_half_ac, pasture_families_one_ac, pasture_families_gt_one_ac, pasture_total_acre, pasture_total_families,
                 fodder_families_quarter_ac, fodder_families_half_ac, fodder_families_one_ac, fodder_families_gt_one_ac, fodder_total_acre, fodder_total_families)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ";
            $stmt = $mysqli->prepare($insert_query);
            if ($stmt) {
                $stmt->bind_param(
                    "siiiiidiiiiidi",
                    $vs_range,
                    $report_year,
                    $pasture_families_quarter_ac,
                    $pasture_families_half_ac,
                    $pasture_families_one_ac,
                    $pasture_families_gt_one_ac,
                    $pasture_total_acre,
                    $pasture_total_families,
                    $fodder_families_quarter_ac,
                    $fodder_families_half_ac,
                    $fodder_families_one_ac,
                    $fodder_families_gt_one_ac,
                    $fodder_total_acre,
                    $fodder_total_families
                );

                if ($stmt->execute()) {
                    header("Location: annual_pasture_lands.php?year=all&status=success&msg=" . urlencode("Pasture & Fodder land record added successfully."));
                } else {
                    header("Location: annual_pasture_lands.php?year=" . urlencode($selected_year) . "&status=error&msg=" . urlencode("Database insert failed: " . $stmt->error));
                }
                $stmt->close();
            } else {
                header("Location: annual_pasture_lands.php?year=" . urlencode($selected_year) . "&status=error&msg=" . urlencode("Query preparation failed: " . $mysqli->error));
            }
            exit();
        } elseif ($_POST['action'] === 'edit') {
            $id = intval($_POST['id'] ?? 0);
            $vs_range = trim($_POST['vs_range'] ?? '');
            $report_year = intval($_POST['report_year'] ?? date('Y'));

            // Pasture fields
            $pasture_families_quarter_ac = intval($_POST['pasture_families_quarter_ac'] ?? 0);
            $pasture_families_half_ac = intval($_POST['pasture_families_half_ac'] ?? 0);
            $pasture_families_one_ac = intval($_POST['pasture_families_one_ac'] ?? 0);
            $pasture_families_gt_one_ac = intval($_POST['pasture_families_gt_one_ac'] ?? 0);
            $pasture_total_acre = floatval($_POST['pasture_total_acre'] ?? 0);
            $pasture_total_families = intval($_POST['pasture_total_families'] ?? 0);

            // Fodder fields
            $fodder_families_quarter_ac = intval($_POST['fodder_families_quarter_ac'] ?? 0);
            $fodder_families_half_ac = intval($_POST['fodder_families_half_ac'] ?? 0);
            $fodder_families_one_ac = intval($_POST['fodder_families_one_ac'] ?? 0);
            $fodder_families_gt_one_ac = intval($_POST['fodder_families_gt_one_ac'] ?? 0);
            $fodder_total_acre = floatval($_POST['fodder_total_acre'] ?? 0);
            $fodder_total_families = intval($_POST['fodder_total_families'] ?? 0);

            $update_query = "
                UPDATE pasture_fodder_lands SET 
                    vs_range = ?,
                    report_year = ?,
                    pasture_families_quarter_ac = ?, pasture_families_half_ac = ?, pasture_families_one_ac = ?, pasture_families_gt_one_ac = ?, pasture_total_acre = ?, pasture_total_families = ?,
                    fodder_families_quarter_ac = ?, fodder_families_half_ac = ?, fodder_families_one_ac = ?, fodder_families_gt_one_ac = ?, fodder_total_acre = ?, fodder_total_families = ?
                WHERE id = ?
            ";
            $stmt = $mysqli->prepare($update_query);
            if ($stmt) {
                $stmt->bind_param(
                    "siiiiidiiiiidii",
                    $vs_range,
                    $report_year,
                    $pasture_families_quarter_ac,
                    $pasture_families_half_ac,
                    $pasture_families_one_ac,
                    $pasture_families_gt_one_ac,
                    $pasture_total_acre,
                    $pasture_total_families,
                    $fodder_families_quarter_ac,
                    $fodder_families_half_ac,
                    $fodder_families_one_ac,
                    $fodder_families_gt_one_ac,
                    $fodder_total_acre,
                    $fodder_total_families,
                    $id
                );
                if ($stmt->execute()) {
                    header("Location: annual_pasture_lands.php?year=all&status=success&msg=" . urlencode("Pasture & Fodder land record updated successfully."));
                } else {
                    header("Location: annual_pasture_lands.php?year=" . urlencode($selected_year) . "&status=error&msg=" . urlencode("Database update failed: " . $stmt->error));
                }
                $stmt->close();
            } else {
                header("Location: annual_pasture_lands.php?year=" . urlencode($selected_year) . "&status=error&msg=" . urlencode("Query preparation failed: " . $mysqli->error));
            }
            exit();
        }
    }
}

// Handle GET Delete Action
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $stmt = $mysqli->prepare("DELETE FROM pasture_fodder_lands WHERE id = ?");
    if ($stmt) {
        $stmt->bind_param("i", $id);
        if ($stmt->execute()) {
            header("Location: annual_pasture_lands.php?year=" . urlencode($selected_year) . "&status=success&msg=" . urlencode("Record deleted successfully."));
        } else {
            header("Location: annual_pasture_lands.php?year=" . urlencode($selected_year) . "&status=error&msg=" . urlencode("Failed to delete record: " . $stmt->error));
        }
        $stmt->close();
    }
    exit();
}

// Fetch records from database with year filter support
$records = [];
if ($selected_year === 'all') {
    $result = $mysqli->query("SELECT * FROM pasture_fodder_lands ORDER BY report_year DESC, id DESC");
} else {
    $stmt = $mysqli->prepare("SELECT * FROM pasture_fodder_lands WHERE report_year = ? ORDER BY id DESC");
    if ($stmt) {
        $stmt->bind_param("i", $selected_year);
        $stmt->execute();
        $result = $stmt->get_result();
    }
}
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $records[] = $row;
    }
}

// Calculate summary totals
$summary = [
    'total_records' => count($records),
    'pasture_acres_sum' => 0,
    'pasture_families_sum' => 0,
    'fodder_acres_sum' => 0,
    'fodder_families_sum' => 0
];
foreach ($records as $r) {
    $summary['pasture_acres_sum'] += floatval($r['pasture_total_acre']);
    $summary['pasture_families_sum'] += intval($r['pasture_total_families']);
    $summary['fodder_acres_sum'] += floatval($r['fodder_total_acre']);
    $summary['fodder_families_sum'] += intval($r['fodder_total_families']);
}

// Fetch list of VS Ranges for quick selection autocomplete/dropdown
$vs_ranges_list = [];
$range_res = $mysqli->query("SELECT name FROM veterinary_ranges ORDER BY name ASC");
if ($range_res) {
    while ($r_row = $range_res->fetch_assoc()) {
        $vs_ranges_list[] = $r_row['name'];
    }
}

require_once '../../../includes/header.php';
?>

<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.2/css/buttons.bootstrap5.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
<link rel="stylesheet" href="../../../assets/css/veterinary.css">

<style>
    .card-header-gradient {
        color: black;
    }

    .section-pasture-header {
        color: black;
    }

    .section-fodder-header {
        color: black;
    }

    .border-pasture {
        border-left: 4px solid #3b82f6 !important;
    }

    .border-fodder {
        border-left: 4px solid #10b981 !important;
    }

    .table-nested-header th {
        vertical-align: middle !important;
        text-align: center !important;
        font-size: 0.85rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.3px;
        background-color: #ffffff !important;
        color: #000000 !important;
    }

    .badge-auto-calc {
        font-size: 0.7rem;
        padding: 0.2em 0.5em;
        border-radius: 4px;
        background-color: #e0e7ff;
        color: #3730a3;
    }

    #pastureFodderTable,
    #pastureFodderTable th,
    #pastureFodderTable td {
        background-color: #ffffff !important;
        color: #000000 !important;
    }
</style>



        <!-- PAGE TITLE CARD -->
        <div class="card shadow-sm border-0 mb-4 overflow-hidden">
            <div class="card-body p-4 card-header-gradient d-flex justify-content-between align-items-center flex-wrap gap-3">
                <div>
                    <span class="badge bg-warning text-dark fw-bold mb-2">Annexure : 07</span>
                    <h3 class="h4 fw-bold mb-1">Data of Pasture and Fodder land & farm families - <?= ($selected_year === 'all') ? 'All Years' : htmlspecialchars($selected_year) ?></h3>
                    <p class="mb-0 text-white-50 small">Manage and track pasture land allocations, fodder cultivation, and beneficiary farm families by VS Range.</p>
                </div>
                <div class="d-flex align-items-center gap-3">
                    <form method="GET" class="d-flex align-items-center gap-2">
                        <label class="small fw-bold text-dark mb-0">Year:</label>
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
                    <a href="range_statistics.php" class="btn btn-light text-dark fw-bold btn-sm shadow-sm">
                        <i class="bi bi-arrow-left-circle me-1"></i> Range Statistics
                    </a>
                </div>
            </div>
        </div>

        <!-- STATS OVERVIEW CARDS -->
        <div class="row g-3 mb-4">
            <div class="col-6 col-lg-3">
                <div class="card shadow-sm border-0 border-start border-primary border-4 text-center h-100">
                    <div class="card-body py-3">
                        <span class="text-muted small text-uppercase fw-bold">Pasture Total Extent</span>
                        <h4 class="mb-0 fw-bold text-primary mt-1"><?= number_format($summary['pasture_acres_sum'], 2) ?> <small class="fs-6 text-muted">Ac</small></h4>
                        <span class="small text-muted"><?= number_format($summary['pasture_families_sum']) ?> total families</span>
                    </div>
                </div>
            </div>
            <div class="col-6 col-lg-3">
                <div class="card shadow-sm border-0 border-start border-success border-4 text-center h-100">
                    <div class="card-body py-3">
                        <span class="text-muted small text-uppercase fw-bold">Fodder Total Extent</span>
                        <h4 class="mb-0 fw-bold text-success mt-1"><?= number_format($summary['fodder_acres_sum'], 2) ?> <small class="fs-6 text-muted">Ac</small></h4>
                        <span class="small text-muted"><?= number_format($summary['fodder_families_sum']) ?> total families</span>
                    </div>
                </div>
            </div>
            <div class="col-6 col-lg-3">
                <div class="card shadow-sm border-0 border-start border-info border-4 text-center h-100">
                    <div class="card-body py-3">
                        <span class="text-muted small text-uppercase fw-bold">Combined Extent</span>
                        <h4 class="mb-0 fw-bold text-info mt-1"><?= number_format($summary['pasture_acres_sum'] + $summary['fodder_acres_sum'], 2) ?> <small class="fs-6 text-muted">Ac</small></h4>
                        <span class="small text-muted">Pasture + Fodder</span>
                    </div>
                </div>
            </div>
            <div class="col-6 col-lg-3">
                <div class="card shadow-sm border-0 border-start border-warning border-4 text-center h-100">
                    <div class="card-body py-3">
                        <span class="text-muted small text-uppercase fw-bold">Total VS Ranges</span>
                        <h4 class="mb-0 fw-bold text-warning mt-1"><?= $summary['total_records'] ?></h4>
                        <span class="small text-muted">Recorded Entries</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- DATA ENTRY FORM CARD -->
        <div class="card shadow-sm border-0 mb-4">
            <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0 fw-bold text-dark"><i class="bi bi-pencil-square me-2 text-danger"></i>Data Entry Form</h5>
                <span class="text-muted small"><i class="bi bi-info-circle me-1"></i>Enter details for a VS Range</span>
            </div>
            <div class="card-body p-4">
                <form method="POST" action="annual_pasture_lands.php" id="dataEntryForm">
                    <input type="hidden" name="action" value="add">

                    <!-- VS RANGE & REPORT YEAR INPUT -->
                    <div class="row mb-4">
                        <div class="col-md-6 col-lg-4">
                            <label for="vs_range" class="form-label fw-bold text-dark">VS Range <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text bg-light"><i class="bi bi-geo-alt-fill text-danger"></i></span>
                                <input type="text" name="vs_range" id="vs_range" class="form-control" list="rangeOptions" placeholder="e.g. Ampara, Batticaloa, Trincomalee..." required>
                                <datalist id="rangeOptions">
                                    <?php foreach ($vs_ranges_list as $range_item): ?>
                                        <option value="<?= htmlspecialchars($range_item) ?>">
                                    <?php endforeach; ?>
                                </datalist>
                            </div>
                        </div>
                        <div class="col-md-6 col-lg-4">
                            <label for="report_year" class="form-label fw-bold text-dark">Report Year <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text bg-light"><i class="bi bi-calendar-event text-primary"></i></span>
                                <input type="number" name="report_year" id="report_year" class="form-control" value="<?= date('Y') ?>" required>
                            </div>
                        </div>
                    </div>

                    <!-- TWO VISUALLY DISTINCT SECTIONS FOR PASTURE LAND & FODDER LAND -->
                    <div class="row g-4 mb-4">

                        <!-- SECTION 1: PASTURE LAND -->
                        <div class="col-lg-6">
                            <div class="card border-0 shadow-sm h-100 border-pasture">
                                <div class="card-header section-pasture-header py-3">
                                    <h6 class="mb-0 fw-bold"><i class="bi bi-tree-fill me-2"></i>Pasture Land</h6>
                                </div>
                                <div class="card-body p-3">
                                    <div class="text-muted small fw-bold text-uppercase mb-2 border-bottom pb-1">No. of Families Having Pasture Land</div>
                                    <div class="row g-3">
                                        <div class="col-6 col-md-3">
                                            <label class="form-label small fw-bold">1/4 Ac</label>
                                            <input type="number" name="pasture_families_quarter_ac" id="p_quarter" class="form-control form-control-sm pasture-calc-input" min="0" value="0">
                                        </div>
                                        <div class="col-6 col-md-3">
                                            <label class="form-label small fw-bold">1/2 Ac</label>
                                            <input type="number" name="pasture_families_half_ac" id="p_half" class="form-control form-control-sm pasture-calc-input" min="0" value="0">
                                        </div>
                                        <div class="col-6 col-md-3">
                                            <label class="form-label small fw-bold">1 Ac</label>
                                            <input type="number" name="pasture_families_one_ac" id="p_one" class="form-control form-control-sm pasture-calc-input" min="0" value="0">
                                        </div>
                                        <div class="col-6 col-md-3">
                                            <label class="form-label small fw-bold">&gt; 1Ac</label>
                                            <input type="number" name="pasture_families_gt_one_ac" id="p_gt_one" class="form-control form-control-sm pasture-calc-input" min="0" value="0">
                                        </div>
                                    </div>
                                    <hr class="my-3">
                                    <div class="row g-3">
                                        <div class="col-6">
                                            <label class="form-label small fw-bold text-primary">Total Acre</label>
                                            <div class="input-group input-group-sm">
                                                <input type="number" step="0.01" name="pasture_total_acre" id="p_total_acre" class="form-control" min="0" value="0.00">
                                                <span class="input-group-text">Ac</span>
                                            </div>
                                        </div>
                                        <div class="col-6">
                                            <label class="form-label small fw-bold text-primary d-flex justify-content-between align-items-center">
                                                <span>Total Farm Families</span>
                                                <span class="badge-auto-calc" id="p_auto_badge" title="Auto calculated from 4 categories above">Auto</span>
                                            </label>
                                            <input type="number" name="pasture_total_families" id="p_total_families" class="form-control form-control-sm border-primary" min="0" value="0">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- SECTION 2: FODDER LAND -->
                        <div class="col-lg-6">
                            <div class="card border-0 shadow-sm h-100 border-fodder">
                                <div class="card-header section-fodder-header py-3">
                                    <h6 class="mb-0 fw-bold"><i class="bi bi-flower2 me-2"></i>Fodder Land</h6>
                                </div>
                                <div class="card-body p-3">
                                    <div class="text-muted small fw-bold text-uppercase mb-2 border-bottom pb-1">No. of Families Having Fodder Land</div>
                                    <div class="row g-3">
                                        <div class="col-6 col-md-3">
                                            <label class="form-label small fw-bold">1/4 Ac</label>
                                            <input type="number" name="fodder_families_quarter_ac" id="f_quarter" class="form-control form-control-sm fodder-calc-input" min="0" value="0">
                                        </div>
                                        <div class="col-6 col-md-3">
                                            <label class="form-label small fw-bold">1/2 Ac</label>
                                            <input type="number" name="fodder_families_half_ac" id="f_half" class="form-control form-control-sm fodder-calc-input" min="0" value="0">
                                        </div>
                                        <div class="col-6 col-md-3">
                                            <label class="form-label small fw-bold">1 Ac</label>
                                            <input type="number" name="fodder_families_one_ac" id="f_one" class="form-control form-control-sm fodder-calc-input" min="0" value="0">
                                        </div>
                                        <div class="col-6 col-md-3">
                                            <label class="form-label small fw-bold">&gt; 1Ac</label>
                                            <input type="number" name="fodder_families_gt_one_ac" id="f_gt_one" class="form-control form-control-sm fodder-calc-input" min="0" value="0">
                                        </div>
                                    </div>
                                    <hr class="my-3">
                                    <div class="row g-3">
                                        <div class="col-6">
                                            <label class="form-label small fw-bold text-success">Total Acre</label>
                                            <div class="input-group input-group-sm">
                                                <input type="number" step="0.01" name="fodder_total_acre" id="f_total_acre" class="form-control" min="0" value="0.00">
                                                <span class="input-group-text">Ac</span>
                                            </div>
                                        </div>
                                        <div class="col-6">
                                            <label class="form-label small fw-bold text-success d-flex justify-content-between align-items-center">
                                                <span>Total Farm Families</span>
                                                <span class="badge-auto-calc" id="f_auto_badge" title="Auto calculated from 4 categories above">Auto</span>
                                            </label>
                                            <input type="number" name="fodder_total_families" id="f_total_families" class="form-control form-control-sm border-success" min="0" value="0">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                    </div>

                    <div class="d-flex justify-content-end gap-2">
                        <button type="reset" class="btn btn-light border px-4" id="btnResetForm">
                            <i class="bi bi-x-circle me-1"></i> Reset
                        </button>
                        <button type="submit" class="btn btn-primary px-4 fw-bold" style="background-color: #370709; border-color: #370709;">
                            <i class="bi bi-plus-circle me-1"></i> Save Land Data
                        </button>
                    </div>

                </form>
            </div>
        </div>

        <!-- DATA DISPLAY TABLE CARD -->
        <div class="card shadow-sm border-0 mb-4">
            <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center border-bottom">
                <h5 class="card-title mb-0 fw-bold text-dark">
                    <i class="bi bi-table me-2 text-primary"></i>Pasture & Fodder Lands Records Table - <?= ($selected_year === 'all') ? 'All Years' : htmlspecialchars($selected_year) ?>
                </h5>
                <span class="badge bg-secondary"><?= count($records) ?> Entries</span>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-bordered table-hover align-middle mb-0 text-center bg-white text-dark" id="pastureFodderTable" style="width: 100%; min-width: 1200px;">

                        <!-- 3-ROW NESTED COMPLEX HEADER matching document specifications -->
                        <thead class="table-light table-nested-header bg-white text-dark">
                            <!-- ROW 1 -->
                            <tr>
                                <th rowspan="3" class="align-middle bg-white text-dark" style="width: 50px;">S.No</th>
                                <th rowspan="3" class="align-middle bg-white text-dark" style="min-width: 150px;">VS Range</th>
                                <th rowspan="3" class="align-middle bg-white text-dark" style="width: 80px;">Year</th>
                                <th colspan="6" class="bg-white text-dark py-2">Pasture Land</th>
                                <th colspan="6" class="bg-white text-dark py-2">Fodder Land</th>
                                <th rowspan="3" class="align-middle bg-white text-dark" style="width: 110px;">Actions</th>
                            </tr>
                            <!-- ROW 2 -->
                            <tr>
                                <th colspan="4" class="bg-white text-dark py-2">No.of Families Having Pasture Land</th>
                                <th rowspan="2" class="align-middle bg-white text-dark py-2" style="width: 90px;">Total Acre</th>
                                <th rowspan="2" class="align-middle bg-white text-dark py-2" style="width: 90px;">Total Farm families</th>
                                <th colspan="4" class="bg-white text-dark py-2">No.of Families Having Fodder Land</th>
                                <th rowspan="2" class="align-middle bg-white text-dark py-2" style="width: 90px;">Total Acre</th>
                                <th rowspan="2" class="align-middle bg-white text-dark py-2" style="width: 90px;">Total Farm families</th>
                            </tr>
                            <!-- ROW 3 -->
                            <tr>
                                <th class="bg-white text-dark py-1" style="width: 65px;">1/4 Ac</th>
                                <th class="bg-white text-dark py-1" style="width: 65px;">1/2 Ac</th>
                                <th class="bg-white text-dark py-1" style="width: 65px;">1 Ac</th>
                                <th class="bg-white text-dark py-1" style="width: 65px;">&gt; 1Ac</th>

                                <th class="bg-white text-dark py-1" style="width: 65px;">1/4 Ac</th>
                                <th class="bg-white text-dark py-1" style="width: 65px;">1/2 Ac</th>
                                <th class="bg-white text-dark py-1" style="width: 65px;">1 Ac</th>
                                <th class="bg-white text-dark py-1" style="width: 65px;">&gt; 1Ac</th>
                            </tr>
                        </thead>

                        <tbody class="small bg-white text-dark">
                            <?php if (empty($records)): ?>
                                <tr>
                                    <td colspan="16" class="text-center py-5 text-dark bg-white">
                                        <i class="bi bi-inbox fs-1 d-block mb-2 text-secondary"></i>
                                        No pasture & fodder land records found in the database.
                                        <br><span class="small">Use the form above to add a new record.</span>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php $sno = 1;
                                foreach ($records as $row): ?>
                                    <tr data-row='<?= json_encode($row, JSON_HEX_APOS | JSON_HEX_QUOT) ?>' class="bg-white text-dark">
                                        <td class="fw-bold text-dark bg-white"><?= $sno++ ?></td>
                                        <td class="fw-bold text-start text-dark bg-white"><?= htmlspecialchars($row['vs_range']) ?></td>
                                        <td class="bg-white"><span class="badge bg-secondary"><?= htmlspecialchars($row['report_year'] ?? '2024') ?></span></td>

                                        <!-- Pasture Land Fields -->
                                        <td class="font-monospace text-dark bg-white"><?= number_format($row['pasture_families_quarter_ac']) ?></td>
                                        <td class="font-monospace text-dark bg-white"><?= number_format($row['pasture_families_half_ac']) ?></td>
                                        <td class="font-monospace text-dark bg-white"><?= number_format($row['pasture_families_one_ac']) ?></td>
                                        <td class="font-monospace text-dark bg-white"><?= number_format($row['pasture_families_gt_one_ac']) ?></td>
                                        <td class="font-monospace fw-bold text-dark bg-white"><?= number_format($row['pasture_total_acre'], 2) ?></td>
                                        <td class="font-monospace fw-bold text-dark bg-white"><?= number_format($row['pasture_total_families']) ?></td>

                                        <!-- Fodder Land Fields -->
                                        <td class="font-monospace text-dark bg-white"><?= number_format($row['fodder_families_quarter_ac']) ?></td>
                                        <td class="font-monospace text-dark bg-white"><?= number_format($row['fodder_families_half_ac']) ?></td>
                                        <td class="font-monospace text-dark bg-white"><?= number_format($row['fodder_families_one_ac']) ?></td>
                                        <td class="font-monospace text-dark bg-white"><?= number_format($row['fodder_families_gt_one_ac']) ?></td>
                                        <td class="font-monospace fw-bold text-dark bg-white"><?= number_format($row['fodder_total_acre'], 2) ?></td>
                                        <td class="font-monospace fw-bold text-dark bg-white"><?= number_format($row['fodder_total_families']) ?></td>

                                        <!-- Action Buttons: bi-eye-fill, bi-pencil-fill, bi-trash-fill -->
                                        <td class="text-center bg-white">
                                            <div class="btn-group btn-group-sm" role="group">
                                                <button type="button" class="btn btn-outline-info text-dark btn-view" title="View Details">
                                                    <i class="bi bi-eye-fill text-info"></i>
                                                </button>
                                                <button type="button" class="btn btn-outline-primary btn-edit" title="Edit Record">
                                                    <i class="bi bi-pencil-fill text-primary"></i>
                                                </button>
                                                <a href="annual_pasture_lands.php?year=<?= urlencode($selected_year) ?>&action=delete&id=<?= $row['id'] ?>" class="btn btn-outline-danger btn-delete" title="Delete Record">
                                                    <i class="bi bi-trash-fill text-danger"></i>
                                                </a>
                                            </div>
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

<!-- MODAL: VIEW DETAILS -->
<div class="modal fade" id="viewModal" tabindex="-1" aria-labelledby="viewModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header card-header-gradient">
                <h5 class="modal-title fw-bold" id="viewModalLabel">
                    <i class="bi bi-eye-fill me-2"></i>View Land Data - <span id="v_vs_range"></span> (<span id="v_report_year"></span>)
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <div class="row g-4">
                    <!-- Pasture Column -->
                    <div class="col-md-6 border-end">
                        <div class="p-3 bg-light rounded border border-pasture">
                            <h6 class="fw-bold text-primary mb-3"><i class="bi bi-tree-fill me-2"></i>Pasture Land Details</h6>
                            <ul class="list-group list-group-flush small">
                                <li class="list-group-item bg-transparent d-flex justify-content-between">
                                    <span>1/4 Acre Families:</span>
                                    <strong id="v_p_quarter">0</strong>
                                </li>
                                <li class="list-group-item bg-transparent d-flex justify-content-between">
                                    <span>1/2 Acre Families:</span>
                                    <strong id="v_p_half">0</strong>
                                </li>
                                <li class="list-group-item bg-transparent d-flex justify-content-between">
                                    <span>1 Acre Families:</span>
                                    <strong id="v_p_one">0</strong>
                                </li>
                                <li class="list-group-item bg-transparent d-flex justify-content-between">
                                    <span>&gt; 1 Acre Families:</span>
                                    <strong id="v_p_gt_one">0</strong>
                                </li>
                                <li class="list-group-item bg-transparent d-flex justify-content-between text-primary">
                                    <span>Total Extent (Acres):</span>
                                    <strong id="v_p_total_acre">0.00 Ac</strong>
                                </li>
                                <li class="list-group-item bg-transparent d-flex justify-content-between text-primary">
                                    <span>Total Farm Families:</span>
                                    <strong id="v_p_total_families">0</strong>
                                </li>
                            </ul>
                        </div>
                    </div>
                    <!-- Fodder Column -->
                    <div class="col-md-6">
                        <div class="p-3 bg-light rounded border border-fodder">
                            <h6 class="fw-bold text-success mb-3"><i class="bi bi-flower2 me-2"></i>Fodder Land Details</h6>
                            <ul class="list-group list-group-flush small">
                                <li class="list-group-item bg-transparent d-flex justify-content-between">
                                    <span>1/4 Acre Families:</span>
                                    <strong id="v_f_quarter">0</strong>
                                </li>
                                <li class="list-group-item bg-transparent d-flex justify-content-between">
                                    <span>1/2 Acre Families:</span>
                                    <strong id="v_f_half">0</strong>
                                </li>
                                <li class="list-group-item bg-transparent d-flex justify-content-between">
                                    <span>1 Acre Families:</span>
                                    <strong id="v_f_one">0</strong>
                                </li>
                                <li class="list-group-item bg-transparent d-flex justify-content-between">
                                    <span>&gt; 1 Acre Families:</span>
                                    <strong id="v_f_gt_one">0</strong>
                                </li>
                                <li class="list-group-item bg-transparent d-flex justify-content-between text-success">
                                    <span>Total Extent (Acres):</span>
                                    <strong id="v_f_total_acre">0.00 Ac</strong>
                                </li>
                                <li class="list-group-item bg-transparent d-flex justify-content-between text-success">
                                    <span>Total Farm Families:</span>
                                    <strong id="v_f_total_families">0</strong>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer bg-light">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- MODAL: EDIT RECORD -->
<div class="modal fade" id="editModal" tabindex="-1" aria-labelledby="editModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <form method="POST" action="annual_pasture_lands.php" id="editDataForm">
                <input type="hidden" name="action" value="edit">
                <input type="hidden" name="id" id="e_id">

                <div class="modal-header card-header-gradient">
                    <h5 class="modal-title fw-bold" id="editModalLabel">
                        <i class="bi bi-pencil-fill me-2"></i>Edit Land Record
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body p-4">
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label for="e_vs_range" class="form-label fw-bold">VS Range <span class="text-danger">*</span></label>
                            <input type="text" name="vs_range" id="e_vs_range" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label for="e_report_year" class="form-label fw-bold">Report Year <span class="text-danger">*</span></label>
                            <input type="number" name="report_year" id="e_report_year" class="form-control" required>
                        </div>
                    </div>

                    <div class="row g-4 mb-3">
                        <!-- Pasture Land -->
                        <div class="col-md-6">
                            <div class="card border-0 shadow-sm border-pasture">
                                <div class="card-header section-pasture-header py-2">
                                    <h6 class="mb-0 fw-bold small"><i class="bi bi-tree-fill me-1"></i>Pasture Land</h6>
                                </div>
                                <div class="card-body p-3">
                                    <div class="row g-2 mb-2">
                                        <div class="col-6">
                                            <label class="form-label small fw-bold">1/4 Ac</label>
                                            <input type="number" name="pasture_families_quarter_ac" id="e_p_quarter" class="form-control form-control-sm edit-pasture-calc" min="0">
                                        </div>
                                        <div class="col-6">
                                            <label class="form-label small fw-bold">1/2 Ac</label>
                                            <input type="number" name="pasture_families_half_ac" id="e_p_half" class="form-control form-control-sm edit-pasture-calc" min="0">
                                        </div>
                                        <div class="col-6">
                                            <label class="form-label small fw-bold">1 Ac</label>
                                            <input type="number" name="pasture_families_one_ac" id="e_p_one" class="form-control form-control-sm edit-pasture-calc" min="0">
                                        </div>
                                        <div class="col-6">
                                            <label class="form-label small fw-bold">&gt; 1Ac</label>
                                            <input type="number" name="pasture_families_gt_one_ac" id="e_p_gt_one" class="form-control form-control-sm edit-pasture-calc" min="0">
                                        </div>
                                    </div>
                                    <hr class="my-2">
                                    <div class="row g-2">
                                        <div class="col-6">
                                            <label class="form-label small fw-bold text-primary">Total Acre</label>
                                            <input type="number" step="0.01" name="pasture_total_acre" id="e_p_total_acre" class="form-control form-control-sm" min="0">
                                        </div>
                                        <div class="col-6">
                                            <label class="form-label small fw-bold text-primary">Total Families</label>
                                            <input type="number" name="pasture_total_families" id="e_p_total_families" class="form-control form-control-sm" min="0">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Fodder Land -->
                        <div class="col-md-6">
                            <div class="card border-0 shadow-sm border-fodder">
                                <div class="card-header section-fodder-header py-2">
                                    <h6 class="mb-0 fw-bold small"><i class="bi bi-flower2 me-1"></i>Fodder Land</h6>
                                </div>
                                <div class="card-body p-3">
                                    <div class="row g-2 mb-2">
                                        <div class="col-6">
                                            <label class="form-label small fw-bold">1/4 Ac</label>
                                            <input type="number" name="fodder_families_quarter_ac" id="e_f_quarter" class="form-control form-control-sm edit-fodder-calc" min="0">
                                        </div>
                                        <div class="col-6">
                                            <label class="form-label small fw-bold">1/2 Ac</label>
                                            <input type="number" name="fodder_families_half_ac" id="e_f_half" class="form-control form-control-sm edit-fodder-calc" min="0">
                                        </div>
                                        <div class="col-6">
                                            <label class="form-label small fw-bold">1 Ac</label>
                                            <input type="number" name="fodder_families_one_ac" id="e_f_one" class="form-control form-control-sm edit-fodder-calc" min="0">
                                        </div>
                                        <div class="col-6">
                                            <label class="form-label small fw-bold">&gt; 1Ac</label>
                                            <input type="number" name="fodder_families_gt_one_ac" id="e_f_gt_one" class="form-control form-control-sm edit-fodder-calc" min="0">
                                        </div>
                                    </div>
                                    <hr class="my-2">
                                    <div class="row g-2">
                                        <div class="col-6">
                                            <label class="form-label small fw-bold text-success">Total Acre</label>
                                            <input type="number" step="0.01" name="fodder_total_acre" id="e_f_total_acre" class="form-control form-control-sm" min="0">
                                        </div>
                                        <div class="col-6">
                                            <label class="form-label small fw-bold text-success">Total Families</label>
                                            <input type="number" name="fodder_total_families" id="e_f_total_families" class="form-control form-control-sm" min="0">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary fw-bold">Update Record</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php
$pageScripts = '
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
$(document).ready(function() {

    // Initialize DataTable with orderCellsTop: true to resolve multi-row header column count warnings
    $("#pastureFodderTable").DataTable({
        "orderCellsTop": true,
        "autoWidth": false,
        "order": [[0, "asc"]],
        "pageLength": 10,
        "dom": "Bfrtip",
        "columnDefs": [
            { "orderable": false, "targets": -1 }
        ],
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

    // Auto-calculate Pasture Total Farm Families
    var pastureUserOverridden = false;
    $("#p_total_families").on("input change", function() {
        pastureUserOverridden = true;
        $("#p_auto_badge").text("Manual").removeClass("badge-auto-calc").addClass("badge bg-warning text-dark");
    });

    $(".pasture-calc-input").on("input change", function() {
        if (!pastureUserOverridden) {
            var q = parseInt($("#p_quarter").val()) || 0;
            var h = parseInt($("#p_half").val()) || 0;
            var o = parseInt($("#p_one").val()) || 0;
            var gt = parseInt($("#p_gt_one").val()) || 0;
            var total = q + h + o + gt;
            $("#p_total_families").val(total);
        }
    });

    // Auto-calculate Fodder Total Farm Families
    var fodderUserOverridden = false;
    $("#f_total_families").on("input change", function() {
        fodderUserOverridden = true;
        $("#f_auto_badge").text("Manual").removeClass("badge-auto-calc").addClass("badge bg-warning text-dark");
    });

    $(".fodder-calc-input").on("input change", function() {
        if (!fodderUserOverridden) {
            var q = parseInt($("#f_quarter").val()) || 0;
            var h = parseInt($("#f_half").val()) || 0;
            var o = parseInt($("#f_one").val()) || 0;
            var gt = parseInt($("#f_gt_one").val()) || 0;
            var total = q + h + o + gt;
            $("#f_total_families").val(total);
        }
    });

    // Reset Form Listener
    $("#btnResetForm").on("click", function() {
        pastureUserOverridden = false;
        fodderUserOverridden = false;
        $("#p_auto_badge").text("Auto").removeClass("badge bg-warning text-dark").addClass("badge-auto-calc");
        $("#f_auto_badge").text("Auto").removeClass("badge bg-warning text-dark").addClass("badge-auto-calc");
    });

    // Auto-calculate for Edit Modal
    $(".edit-pasture-calc").on("input change", function() {
        var q = parseInt($("#e_p_quarter").val()) || 0;
        var h = parseInt($("#e_p_half").val()) || 0;
        var o = parseInt($("#e_p_one").val()) || 0;
        var gt = parseInt($("#e_p_gt_one").val()) || 0;
        $("#e_p_total_families").val(q + h + o + gt);
    });

    $(".edit-fodder-calc").on("input change", function() {
        var q = parseInt($("#e_f_quarter").val()) || 0;
        var h = parseInt($("#e_f_half").val()) || 0;
        var o = parseInt($("#e_f_one").val()) || 0;
        var gt = parseInt($("#e_f_gt_one").val()) || 0;
        $("#e_f_total_families").val(q + h + o + gt);
    });

    // View Button Click Handler
    $(document).on("click", ".btn-view", function() {
        var rowData = $(this).closest("tr").data("row");
        if (rowData) {
            $("#v_vs_range").text(rowData.vs_range);
            $("#v_report_year").text(rowData.report_year || 2024);
            
            $("#v_p_quarter").text(rowData.pasture_families_quarter_ac);
            $("#v_p_half").text(rowData.pasture_families_half_ac);
            $("#v_p_one").text(rowData.pasture_families_one_ac);
            $("#v_p_gt_one").text(rowData.pasture_families_gt_one_ac);
            $("#v_p_total_acre").text(parseFloat(rowData.pasture_total_acre).toFixed(2) + " Ac");
            $("#v_p_total_families").text(rowData.pasture_total_families);
            
            $("#v_f_quarter").text(rowData.fodder_families_quarter_ac);
            $("#v_f_half").text(rowData.fodder_families_half_ac);
            $("#v_f_one").text(rowData.fodder_families_one_ac);
            $("#v_f_gt_one").text(rowData.fodder_families_gt_one_ac);
            $("#v_f_total_acre").text(parseFloat(rowData.fodder_total_acre).toFixed(2) + " Ac");
            $("#v_f_total_families").text(rowData.fodder_total_families);

            new bootstrap.Modal(document.getElementById("viewModal")).show();
        }
    });

    // Edit Button Click Handler
    $(document).on("click", ".btn-edit", function() {
        var rowData = $(this).closest("tr").data("row");
        if (rowData) {
            $("#e_id").val(rowData.id);
            $("#e_vs_range").val(rowData.vs_range);
            $("#e_report_year").val(rowData.report_year || 2024);
            
            $("#e_p_quarter").val(rowData.pasture_families_quarter_ac);
            $("#e_p_half").val(rowData.pasture_families_half_ac);
            $("#e_p_one").val(rowData.pasture_families_one_ac);
            $("#e_p_gt_one").val(rowData.pasture_families_gt_one_ac);
            $("#e_p_total_acre").val(rowData.pasture_total_acre);
            $("#e_p_total_families").val(rowData.pasture_total_families);
            
            $("#e_f_quarter").val(rowData.fodder_families_quarter_ac);
            $("#e_f_half").val(rowData.fodder_families_half_ac);
            $("#e_f_one").val(rowData.fodder_families_one_ac);
            $("#e_f_gt_one").val(rowData.fodder_families_gt_one_ac);
            $("#e_f_total_acre").val(rowData.fodder_total_acre);
            $("#e_f_total_families").val(rowData.fodder_total_families);

            new bootstrap.Modal(document.getElementById("editModal")).show();
        }
    });

    // Delete Confirmation with SweetAlert2
    $(document).on("click", ".btn-delete", function(e) {
        e.preventDefault();
        var deleteUrl = $(this).attr("href");
        var rowData = $(this).closest("tr").data("row");
        var rangeName = rowData ? rowData.vs_range : "this record";

        Swal.fire({
            icon: "warning",
            title: "Delete Record?",
            html: "Are you sure you want to delete the pasture & fodder land record for <strong>" + rangeName + "</strong>?<br>This action cannot be undone.",
            showCancelButton: true,
            confirmButtonColor: "#dc3545",
            cancelButtonColor: "#6c757d",
            confirmButtonText: "Yes, Delete",
            cancelButtonText: "Cancel"
        }).then(function(result) {
            if (result.isConfirmed) {
                window.location.href = deleteUrl;
            }
        });
    });

    // Alert Handling for URL Params
    var urlParams = new URLSearchParams(window.location.search);
    var status = urlParams.get("status");
    var msg = urlParams.get("msg") || "";

    if (status === "success") {
        Swal.fire({
            icon: "success",
            title: "Success!",
            text: msg ? msg : "Operation completed successfully.",
            confirmButtonColor: "#370709"
        });
        window.history.replaceState({}, document.title, window.location.pathname);
    } else if (status === "error") {
        Swal.fire({
            icon: "error",
            title: "Error!",
            text: msg ? msg : "An error occurred during operation.",
            confirmButtonColor: "#370709"
        });
        window.history.replaceState({}, document.title, window.location.pathname);
    }

});
</script>
';
require_once '../../../includes/footer.php';
?>