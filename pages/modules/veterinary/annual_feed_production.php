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

// Handle GET year filter
$selected_year = isset($_GET['year']) ? intval($_GET['year']) : intval(date('Y'));

// Inline CRUD actions:
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        if ($_POST['action'] === 'add') {
            $year = intval($_POST['report_year']);
            $mill_name = trim($_POST['feed_mill_name']);
            $prop_details = trim($_POST['proprietor_details']);
            $category = trim($_POST['category_type']);
            $qty = floatval($_POST['produced_qty_mt_month']);
            $raw_source = trim($_POST['raw_materials_source']);
            $outlets = trim($_POST['market_outlets']);

            $insert_query = "
                INSERT INTO annual_feed_production 
                (district_id, range_id, report_year, feed_mill_name, proprietor_details, category_type, 
                 produced_qty_mt_month, raw_materials_source, market_outlets, created_by)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ";
            $stmt = $mysqli->prepare($insert_query);
            if ($stmt) {
                // district_id, range_id, report_year format: i i i
                // feed_mill_name, proprietor_details, category_type format: s s s
                // produced_qty_mt_month format: d
                // raw_materials_source, market_outlets format: s s
                // created_by format: i
                $stmt->bind_param(
                    "iiisssdssi",
                    $district_id,
                    $range_id,
                    $year,
                    $mill_name,
                    $prop_details,
                    $category,
                    $qty,
                    $raw_source,
                    $outlets,
                    $user_id
                );
                if ($stmt->execute()) {
                    header("Location: annual_feed_production.php?year=$year&status=success&msg=" . urlencode("Feed mill added successfully."));
                } else {
                    header("Location: annual_feed_production.php?year=$selected_year&status=error&msg=" . urlencode("Failed to write to database: " . $stmt->error));
                }
                $stmt->close();
            } else {
                header("Location: annual_feed_production.php?year=$selected_year&status=error&msg=" . urlencode("Query preparation failed."));
            }
            exit();
        } elseif ($_POST['action'] === 'edit') {
            $id = intval($_POST['id']);
            $year = intval($_POST['report_year']);
            $mill_name = trim($_POST['feed_mill_name']);
            $prop_details = trim($_POST['proprietor_details']);
            $category = trim($_POST['category_type']);
            $qty = floatval($_POST['produced_qty_mt_month']);
            $raw_source = trim($_POST['raw_materials_source']);
            $outlets = trim($_POST['market_outlets']);

            $update_query = "
                UPDATE annual_feed_production 
                SET report_year = ?, feed_mill_name = ?, proprietor_details = ?, category_type = ?, 
                    produced_qty_mt_month = ?, raw_materials_source = ?, market_outlets = ?
                WHERE id = ? AND range_id = ?
            ";
            $stmt = $mysqli->prepare($update_query);
            if ($stmt) {
                $stmt->bind_param(
                    "isssdssii",
                    $year,
                    $mill_name,
                    $prop_details,
                    $category,
                    $qty,
                    $raw_source,
                    $outlets,
                    $id,
                    $range_id
                );
                if ($stmt->execute()) {
                    header("Location: annual_feed_production.php?year=$year&status=success&msg=" . urlencode("Feed mill updated successfully."));
                } else {
                    header("Location: annual_feed_production.php?year=$selected_year&status=error&msg=" . urlencode("Failed to update database: " . $stmt->error));
                }
                $stmt->close();
            } else {
                header("Location: annual_feed_production.php?year=$selected_year&status=error&msg=" . urlencode("Query preparation failed."));
            }
            exit();
        }
    }
}

if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $stmt = $mysqli->prepare("DELETE FROM annual_feed_production WHERE id = ? AND range_id = ?");
    if ($stmt) {
        $stmt->bind_param("ii", $id, $range_id);
        if ($stmt->execute()) {
            header("Location: annual_feed_production.php?year=$selected_year&status=success&msg=" . urlencode("Record deleted successfully."));
        } else {
            header("Location: annual_feed_production.php?year=$selected_year&status=error&msg=" . urlencode("Failed to delete record."));
        }
        $stmt->close();
    }
    exit();
}

// Fetch records matching year filter and range
$records = [];
if (!empty($range_id)) {
    $stmt = $mysqli->prepare("SELECT * FROM annual_feed_production WHERE range_id = ? AND report_year = ? ORDER BY id DESC");
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

// Summary stats
$summary = [
    'mill_count' => count($records),
    'total_qty_mt' => 0,
    'categories' => []
];
foreach ($records as $r) {
    $summary['total_qty_mt'] += $r['produced_qty_mt_month'];
    $cat = $r['category_type'];
    if (!isset($summary['categories'][$cat])) {
        $summary['categories'][$cat] = 0;
    }
    $summary['categories'][$cat]++;
}

require_once '../../../includes/header.php';
?>

<link rel="stylesheet" href="../../../assets/css/bootstrap-icons.min.css">
<link rel="stylesheet" href="../../../assets/css/dataTables.bootstrap5.min.css">
<link rel="stylesheet" href="../../../assets/css/buttons.bootstrap5.min.css">
<link rel="stylesheet" href="../../../assets/css/sweetalert2.min.css">
<link rel="stylesheet" href="../../../assets/css/veterinary.css">



        <div class="mb-4 d-flex justify-content-between align-items-center">
            <div>
                <h2 class="h4 fw-bold mb-1" style="color: #370709;">Annual Feed Production</h2>
                <p class="text-muted small mb-0">Record and track feed mills and production metrics for <strong class="text-dark"><?= htmlspecialchars($range_name) ?></strong> (<?= htmlspecialchars($district_name) ?> District)</p>
            </div>

            <div class="d-flex align-items-center gap-2">
                <form method="GET" class="d-flex align-items-center gap-2">
                    <label class="small fw-bold text-muted mb-0">Year:</label>
                    <select name="year" class="form-select form-select-sm" onchange="this.form.submit()" style="width: 100px;">
                        <?php
                        $curr_year = intval(date('Y'));
                        for ($y = $curr_year - 5; $y <= $curr_year + 5; $y++) {
                            $sel = ($y === $selected_year) ? 'selected' : '';
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
                        <h4 class="mb-0 fw-bold text-primary mt-1"><?= $selected_year ?></h4>
                    </div>
                </div>
            </div>
            <div class="col-6 col-lg-3">
                <div class="card shadow-sm border-0 border-start border-success border-4 text-center">
                    <div class="card-body py-3">
                        <span class="text-muted small text-uppercase fw-bold">Active Mills</span>
                        <h4 class="mb-0 fw-bold text-success mt-1"><?= number_format($summary['mill_count']) ?> Mills</h4>
                    </div>
                </div>
            </div>
            <div class="col-6 col-lg-3">
                <div class="card shadow-sm border-0 border-start border-info border-4 text-center">
                    <div class="card-body py-3">
                        <span class="text-muted small text-uppercase fw-bold">Total Monthly Output</span>
                        <h4 class="mb-0 fw-bold text-info mt-1"><?= number_format($summary['total_qty_mt'], 2) ?> MT</h4>
                    </div>
                </div>
            </div>
            <div class="col-6 col-lg-3">
                <div class="card shadow-sm border-0 border-start border-warning border-4 text-center">
                    <div class="card-body py-3">
                        <span class="text-muted small text-uppercase fw-bold">Poultry Feed Mills</span>
                        <h4 class="mb-0 fw-bold text-warning mt-1"><?= number_format($summary['categories']['poultry'] ?? 0) ?> Mills</h4>
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
                                <button class="btn btn-primary w-100 py-3 text-light border-0 shadow-sm d-flex flex-column align-items-center justify-content-center" style="background-color: #820100; min-height: 105px;" data-bs-toggle="modal" data-bs-target="#addMillModal">
                                    <i class="bi bi-plus-circle fs-3 mb-1"></i>
                                    <span class="small fw-bold text-uppercase">Add Feed Mill</span>
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
                <h5 class="card-title mb-0 fw-bold text-dark"><i class="bi bi-table me-2"></i>Feed Mills Directories - <?= $selected_year ?></h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0" id="millTable" style="min-width: 1200px;">
                        <thead class="table-light text-secondary small uppercase">
                            <tr>
                                <th>Mill Name</th>
                                <th>Proprietor Details</th>
                                <th class="text-center">Feed Category</th>
                                <th class="text-end">Produced (MT/Month)</th>
                                <th>Raw Material Source</th>
                                <th>Market Outlets</th>
                                <th class="text-center" style="width: 10%">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="small">
                            <?php foreach ($records as $row): ?>
                                <tr
                                    data-id="<?= $row['id'] ?>"
                                    data-year="<?= htmlspecialchars($row['report_year']) ?>"
                                    data-mill_name="<?= htmlspecialchars($row['feed_mill_name']) ?>"
                                    data-proprietor_details="<?= htmlspecialchars($row['proprietor_details']) ?>"
                                    data-category_type="<?= htmlspecialchars($row['category_type']) ?>"
                                    data-qty="<?= htmlspecialchars($row['produced_qty_mt_month']) ?>"
                                    data-raw_source="<?= htmlspecialchars($row['raw_materials_source']) ?>"
                                    data-outlets="<?= htmlspecialchars($row['market_outlets']) ?>">
                                    <td class="fw-bold"><?= htmlspecialchars($row['feed_mill_name']) ?></td>
                                    <td><?= nl2br(htmlspecialchars($row['proprietor_details'])) ?></td>
                                    <td class="text-center">
                                        <span class="badge bg-secondary text-capitalize"><?= htmlspecialchars($row['category_type']) ?></span>
                                    </td>
                                    <td class="text-end font-monospace"><?= number_format($row['produced_qty_mt_month'], 2) ?></td>
                                    <td><?= htmlspecialchars($row['raw_materials_source']) ?></td>
                                    <td><?= htmlspecialchars($row['market_outlets']) ?></td>
                                    <td class="text-center">
                                        <button class="btn btn-sm btn-outline-primary btn-edit" title="Edit"><i class="bi bi-pencil-square"></i></button>
                                        <a href="annual_feed_production.php?year=<?= $selected_year ?>&action=delete&id=<?= $row['id'] ?>" class="btn btn-sm btn-outline-danger btn-delete" title="Delete"><i class="bi bi-trash"></i></a>
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

<!-- Modal: Add Record -->
<div class="modal fade" id="addMillModal" tabindex="-1" aria-labelledby="addMillModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <form method="POST">
            <input type="hidden" name="action" value="add">
            <div class="modal-content">
                <div class="modal-header" style="background-color: #370709; color: white;">
                    <h5 class="modal-title" id="addMillModalLabel"><i class="bi bi-plus-circle me-2"></i>Add Feed Mill Record</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-12">
                            <label class="form-label fw-bold">Report Year</label>
                            <input type="number" name="report_year" class="form-control" value="<?= date('Y') ?>" required>
                        </div>
                        <div class="col-md-12">
                            <label class="form-label fw-bold">Feed Mill Name</label>
                            <input type="text" name="feed_mill_name" class="form-control" placeholder="e.g. Balapitiya Feeds Ltd" required>
                        </div>
                        <div class="col-md-12">
                            <label class="form-label">Proprietor Details (Name, Address, Tel)</label>
                            <textarea name="proprietor_details" class="form-control" rows="3" placeholder="Name, Address & Contact Details"></textarea>
                        </div>
                        <div class="col-md-12">
                            <label class="form-label fw-bold">Feed Category</label>
                            <select name="category_type" class="form-select" required>
                                <option value="poultry">Poultry Feed</option>
                                <option value="cattle">Cattle Feed</option>
                                <option value="pig">Pig Feed</option>
                                <option value="other">Other Feed</option>
                            </select>
                        </div>
                        <div class="col-md-12">
                            <label class="form-label">Quantity Produced per Month (MT)</label>
                            <input type="number" step="0.01" name="produced_qty_mt_month" class="form-control" value="0.00">
                        </div>
                        <div class="col-md-12">
                            <label class="form-label">Source of Raw Materials</label>
                            <input type="text" name="raw_materials_source" class="form-control" placeholder="Local / Imported / Specific Distributors">
                        </div>
                        <div class="col-md-12">
                            <label class="form-label">Major Distribution Channels / Outlets</label>
                            <textarea name="market_outlets" class="form-control" rows="2" placeholder="Outlets and markets sold to"></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success">Save mill</button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Modal: Edit Record -->
<div class="modal fade" id="editMillModal" tabindex="-1" aria-labelledby="editMillModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <form method="POST">
            <input type="hidden" name="action" value="edit">
            <input type="hidden" name="id" id="edit_id">
            <div class="modal-content">
                <div class="modal-header" style="background-color: #370709; color: white;">
                    <h5 class="modal-title" id="editMillModalLabel"><i class="bi bi-pencil-square me-2"></i>Edit Feed Mill Record</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-12">
                            <label class="form-label fw-bold">Report Year</label>
                            <input type="number" name="report_year" id="edit_report_year" class="form-control" required>
                        </div>
                        <div class="col-md-12">
                            <label class="form-label fw-bold">Feed Mill Name</label>
                            <input type="text" name="feed_mill_name" id="edit_mill_name" class="form-control" required>
                        </div>
                        <div class="col-md-12">
                            <label class="form-label">Proprietor Details (Name, Address, Tel)</label>
                            <textarea name="proprietor_details" id="edit_proprietor_details" class="form-control" rows="3"></textarea>
                        </div>
                        <div class="col-md-12">
                            <label class="form-label fw-bold">Feed Category</label>
                            <select name="category_type" id="edit_category_type" class="form-select" required>
                                <option value="poultry">Poultry Feed</option>
                                <option value="cattle">Cattle Feed</option>
                                <option value="pig">Pig Feed</option>
                                <option value="other">Other Feed</option>
                            </select>
                        </div>
                        <div class="col-md-12">
                            <label class="form-label">Quantity Produced per Month (MT)</label>
                            <input type="number" step="0.01" name="produced_qty_mt_month" id="edit_qty" class="form-control">
                        </div>
                        <div class="col-md-12">
                            <label class="form-label">Source of Raw Materials</label>
                            <input type="text" name="raw_materials_source" id="edit_raw_source" class="form-control">
                        </div>
                        <div class="col-md-12">
                            <label class="form-label">Major Distribution Channels / Outlets</label>
                            <textarea name="market_outlets" id="edit_outlets" class="form-control" rows="2"></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update Mill</button>
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
    $("#millTable").DataTable({
        "order": [[0, "asc"]],
        "pageLength": 10,
        "dom": "Bfrtip",
        "language": {
            "emptyTable": "No records located for the selected year."
        },
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
        $(\'#edit_mill_name\').val($row.data(\'mill_name\'));
        $(\'#edit_proprietor_details\').val($row.data(\'proprietor_details\'));
        $(\'#edit_category_type\').val($row.data(\'category_type\'));
        $(\'#edit_qty\').val($row.data(\'qty\'));
        $(\'#edit_raw_source\').val($row.data(\'raw_source\'));
        $(\'#edit_outlets\').val($row.data(\'outlets\'));

        new bootstrap.Modal(document.getElementById(\'editMillModal\')).show();
    });

    $(document).on(\'click\', \'.btn-delete\', function(e) {
        e.preventDefault();
        var deleteUrl = $(this).attr(\'href\');
        var $row = $(this).closest(\'tr\');
        var millName = $row.data(\'mill_name\');

        Swal.fire({
            icon: \'warning\',
            title: \'Delete Feed Mill Record?\',
            html: \'Are you sure you want to permanently delete the mill <strong>\' + millName + \'</strong>?<br>This action cannot be undone.\',
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