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
            $center_name = trim($_POST['center_name']);
            $owner = trim($_POST['owner_proprietor']);
            $milk_sales = floatval($_POST['average_milk_sales_lit_day']);
            $yoghurt_sales = intval($_POST['yoghurt_sales_cups_day']);
            $curd_sales = intval($_POST['curd_sales_pots_day']);
            $other_details = trim($_POST['other_dairy_sales_details']);
            $sell_price = floatval($_POST['average_selling_price_lit']);

            $insert_query = "
                INSERT INTO annual_milk_sales_centers 
                (district_id, range_id, report_year, center_name, owner_proprietor, average_milk_sales_lit_day, 
                 yoghurt_sales_cups_day, curd_sales_pots_day, other_dairy_sales_details, average_selling_price_lit, created_by)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ";
            $stmt = $mysqli->prepare($insert_query);
            if ($stmt) {
                // district_id, range_id, report_year format: i i i
                // center_name, owner_proprietor format: s s
                // average_milk_sales_lit_day format: d
                // yoghurt_sales_cups_day, curd_sales_pots_day format: i i
                // other_dairy_sales_details format: s
                // average_selling_price_lit format: d
                // created_by format: i
                $stmt->bind_param("iiissdiisdi", $district_id, $range_id, $year, $center_name, $owner, 
                                  $milk_sales, $yoghurt_sales, $curd_sales, $other_details, $sell_price, $user_id);
                if ($stmt->execute()) {
                    header("Location: annual_milk_sales.php?year=$year&status=success&msg=" . urlencode("Sales outlet added successfully."));
                } else {
                    header("Location: annual_milk_sales.php?year=$selected_year&status=error&msg=" . urlencode("Failed to write to database: " . $stmt->error));
                }
                $stmt->close();
            } else {
                header("Location: annual_milk_sales.php?year=$selected_year&status=error&msg=" . urlencode("Query preparation failed."));
            }
            exit();

        } elseif ($_POST['action'] === 'edit') {
            $id = intval($_POST['id']);
            $year = intval($_POST['report_year']);
            $center_name = trim($_POST['center_name']);
            $owner = trim($_POST['owner_proprietor']);
            $milk_sales = floatval($_POST['average_milk_sales_lit_day']);
            $yoghurt_sales = intval($_POST['yoghurt_sales_cups_day']);
            $curd_sales = intval($_POST['curd_sales_pots_day']);
            $other_details = trim($_POST['other_dairy_sales_details']);
            $sell_price = floatval($_POST['average_selling_price_lit']);

            $update_query = "
                UPDATE annual_milk_sales_centers 
                SET report_year = ?, center_name = ?, owner_proprietor = ?, average_milk_sales_lit_day = ?, 
                    yoghurt_sales_cups_day = ?, curd_sales_pots_day = ?, other_dairy_sales_details = ?, 
                    average_selling_price_lit = ?
                WHERE id = ? AND range_id = ?
            ";
            $stmt = $mysqli->prepare($update_query);
            if ($stmt) {
                $stmt->bind_param("issdiisdii", $year, $center_name, $owner, $milk_sales, $yoghurt_sales, 
                                  $curd_sales, $other_details, $sell_price, $id, $range_id);
                if ($stmt->execute()) {
                    header("Location: annual_milk_sales.php?year=$year&status=success&msg=" . urlencode("Sales outlet updated successfully."));
                } else {
                    header("Location: annual_milk_sales.php?year=$selected_year&status=error&msg=" . urlencode("Failed to update database: " . $stmt->error));
                }
                $stmt->close();
            } else {
                header("Location: annual_milk_sales.php?year=$selected_year&status=error&msg=" . urlencode("Query preparation failed."));
            }
            exit();
        }
    }
}

if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $stmt = $mysqli->prepare("DELETE FROM annual_milk_sales_centers WHERE id = ? AND range_id = ?");
    if ($stmt) {
        $stmt->bind_param("ii", $id, $range_id);
        if ($stmt->execute()) {
            header("Location: annual_milk_sales.php?year=$selected_year&status=success&msg=" . urlencode("Record deleted successfully."));
        } else {
            header("Location: annual_milk_sales.php?year=$selected_year&status=error&msg=" . urlencode("Failed to delete record."));
        }
        $stmt->close();
    }
    exit();
}

// Fetch records matching year filter and range
$records = [];
if (!empty($range_id)) {
    $stmt = $mysqli->prepare("SELECT * FROM annual_milk_sales_centers WHERE range_id = ? AND report_year = ? ORDER BY id DESC");
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
    'outlet_count' => count($records),
    'milk_sales_sum' => 0,
    'yoghurt_sales_sum' => 0,
    'curd_sales_sum' => 0
];
foreach ($records as $r) {
    $summary['milk_sales_sum'] += $r['average_milk_sales_lit_day'];
    $summary['yoghurt_sales_sum'] += $r['yoghurt_sales_cups_day'];
    $summary['curd_sales_sum'] += $r['curd_sales_pots_day'];
}

require_once '../../../includes/header.php';
require_once '../../../includes/sidebar.php';
?>

<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.2/css/buttons.bootstrap5.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
<link rel="stylesheet" href="../../../assets/css/veterinary.css">

<div id="layoutSidenav_content">
    <main class="container-fluid px-4 pt-4">

        <div class="mb-4 d-flex justify-content-between align-items-center">
            <div>
                <h2 class="h4 fw-bold mb-1" style="color: #370709;">Milk Sales Centers</h2>
                <p class="text-muted small mb-0">Record daily milk products sales volumes for outlets in <strong class="text-dark"><?= htmlspecialchars($range_name) ?></strong> (<?= htmlspecialchars($district_name) ?> District)</p>
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
                        <span class="text-muted small text-uppercase fw-bold">Active Outlets</span>
                        <h4 class="mb-0 fw-bold text-success mt-1"><?= number_format($summary['outlet_count']) ?> Outlets</h4>
                    </div>
                </div>
            </div>
            <div class="col-6 col-lg-3">
                <div class="card shadow-sm border-0 border-start border-info border-4 text-center">
                    <div class="card-body py-3">
                        <span class="text-muted small text-uppercase fw-bold">Milk Sales / Day</span>
                        <h4 class="mb-0 fw-bold text-info mt-1"><?= number_format($summary['milk_sales_sum'], 2) ?> Liters</h4>
                    </div>
                </div>
            </div>
            <div class="col-6 col-lg-3">
                <div class="card shadow-sm border-0 border-start border-warning border-4 text-center">
                    <div class="card-body py-3">
                        <span class="text-muted small text-uppercase fw-bold">Yoghurt Sales</span>
                        <h4 class="mb-0 fw-bold text-warning mt-1"><?= number_format($summary['yoghurt_sales_sum']) ?> Cups</h4>
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
                                <button class="btn btn-primary w-100 py-3 text-light border-0 shadow-sm d-flex flex-column align-items-center justify-content-center" style="background-color: #820100; min-height: 105px;" data-bs-toggle="modal" data-bs-target="#addOutletModal">
                                    <i class="bi bi-plus-circle fs-3 mb-1"></i>
                                    <span class="small fw-bold text-uppercase">Add Sales Center</span>
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
                <h5 class="card-title mb-0 fw-bold text-dark"><i class="bi bi-table me-2"></i>Milk Sales Center Directory - <?= $selected_year ?></h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0" id="salesTable" style="min-width: 1300px;">
                        <thead class="table-light text-secondary small uppercase">
                            <tr>
                                <th>Outlet Name</th>
                                <th>Owner / Proprietor</th>
                                <th class="text-end">Milk Sales (L/day)</th>
                                <th class="text-end">Yoghurt (Cups/day)</th>
                                <th class="text-end">Curd (Pots/day)</th>
                                <th>Other Dairy Sales Details</th>
                                <th class="text-end">Selling Price (LKR)</th>
                                <th class="text-center" style="width: 10%">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="small">
                            <?php if (empty($records)): ?>
                                <tr>
                                    <td colspan="8" class="text-center py-4 text-muted">
                                        <i class="bi bi-inbox fs-3 d-block mb-2"></i>
                                        No records located for the selected year <?= $selected_year ?>.
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($records as $row): ?>
                                    <tr 
                                        data-id="<?= $row['id'] ?>"
                                        data-year="<?= htmlspecialchars($row['report_year']) ?>"
                                        data-center_name="<?= htmlspecialchars($row['center_name']) ?>"
                                        data-owner_proprietor="<?= htmlspecialchars($row['owner_proprietor']) ?>"
                                        data-average_milk_sales_lit_day="<?= htmlspecialchars($row['average_milk_sales_lit_day']) ?>"
                                        data-yoghurt_sales_cups_day="<?= htmlspecialchars($row['yoghurt_sales_cups_day']) ?>"
                                        data-curd_sales_pots_day="<?= htmlspecialchars($row['curd_sales_pots_day']) ?>"
                                        data-other_dairy_sales_details="<?= htmlspecialchars($row['other_dairy_sales_details']) ?>"
                                        data-average_selling_price_lit="<?= htmlspecialchars($row['average_selling_price_lit']) ?>">
                                        <td class="fw-bold"><?= htmlspecialchars($row['center_name']) ?></td>
                                        <td><?= htmlspecialchars($row['owner_proprietor']) ?></td>
                                        <td class="text-end font-monospace text-primary fw-bold"><?= number_format($row['average_milk_sales_lit_day'], 2) ?></td>
                                        <td class="text-end font-monospace"><?= number_format($row['yoghurt_sales_cups_day']) ?></td>
                                        <td class="text-end font-monospace"><?= number_format($row['curd_sales_pots_day']) ?></td>
                                        <td><?= htmlspecialchars($row['other_dairy_sales_details']) ?></td>
                                        <td class="text-end font-monospace text-success fw-bold">LKR <?= number_format($row['average_selling_price_lit'], 2) ?></td>
                                        <td class="text-center">
                                            <button class="btn btn-sm btn-outline-primary btn-edit" title="Edit"><i class="bi bi-pencil-square"></i></button>
                                            <a href="annual_milk_sales.php?year=<?= $selected_year ?>&action=delete&id=<?= $row['id'] ?>" class="btn btn-sm btn-outline-danger btn-delete" title="Delete"><i class="bi bi-trash"></i></a>
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
<div class="modal fade" id="addOutletModal" tabindex="-1" aria-labelledby="addOutletModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <form method="POST">
            <input type="hidden" name="action" value="add">
            <div class="modal-content">
                <div class="modal-header" style="background-color: #370709; color: white;">
                    <h5 class="modal-title" id="addOutletModalLabel"><i class="bi bi-plus-circle me-2"></i>Add Sales Center</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-12">
                            <label class="form-label fw-bold">Report Year</label>
                            <input type="number" name="report_year" class="form-control" value="<?= date('Y') ?>" required>
                        </div>
                        <div class="col-md-12">
                            <label class="form-label fw-bold">Sales Center / Outlet Name</label>
                            <input type="text" name="center_name" class="form-control" placeholder="e.g. MILCO Sales Centre Balapitiya" required>
                        </div>
                        <div class="col-md-12">
                            <label class="form-label">Owner / Proprietor</label>
                            <input type="text" name="owner_proprietor" class="form-control" placeholder="e.g. Coop Council, MILCO, Retailer Agent">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Average Milk Sales (L/Day)</label>
                            <input type="number" step="0.01" name="average_milk_sales_lit_day" class="form-control" value="0.00">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Yoghurt Sales (Cups/Day)</label>
                            <input type="number" name="yoghurt_sales_cups_day" class="form-control" value="0">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Curd Sales (Pots/Day)</label>
                            <input type="number" name="curd_sales_pots_day" class="form-control" value="0">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Average Retail Selling Price (LKR / Liter)</label>
                            <input type="number" step="0.01" name="average_selling_price_lit" class="form-control" value="0.00">
                        </div>
                        <div class="col-md-12">
                            <label class="form-label">Other Dairy Sales Details</label>
                            <textarea name="other_dairy_sales_details" class="form-control" rows="2" placeholder="e.g. Butter, Cheese, flavored milk bottles"></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success">Save Outlet</button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Modal: Edit Record -->
<div class="modal fade" id="editOutletModal" tabindex="-1" aria-labelledby="editOutletModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <form method="POST">
            <input type="hidden" name="action" value="edit">
            <input type="hidden" name="id" id="edit_id">
            <div class="modal-content">
                <div class="modal-header" style="background-color: #370709; color: white;">
                    <h5 class="modal-title" id="editOutletModalLabel"><i class="bi bi-pencil-square me-2"></i>Edit Sales Center</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-12">
                            <label class="form-label fw-bold">Report Year</label>
                            <input type="number" name="report_year" id="edit_report_year" class="form-control" required>
                        </div>
                        <div class="col-md-12">
                            <label class="form-label fw-bold">Sales Center / Outlet Name</label>
                            <input type="text" name="center_name" id="edit_center_name" class="form-control" required>
                        </div>
                        <div class="col-md-12">
                            <label class="form-label">Owner / Proprietor</label>
                            <input type="text" name="owner_proprietor" id="edit_owner_proprietor" class="form-control">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Average Milk Sales (L/Day)</label>
                            <input type="number" step="0.01" name="average_milk_sales_lit_day" id="edit_average_milk_sales_lit_day" class="form-control">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Yoghurt Sales (Cups/Day)</label>
                            <input type="number" name="yoghurt_sales_cups_day" id="edit_yoghurt_sales_cups_day" class="form-control">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Curd Sales (Pots/Day)</label>
                            <input type="number" name="curd_sales_pots_day" id="edit_curd_sales_pots_day" class="form-control">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Average Retail Selling Price (LKR / Liter)</label>
                            <input type="number" step="0.01" name="average_selling_price_lit" id="edit_average_selling_price_lit" class="form-control">
                        </div>
                        <div class="col-md-12">
                            <label class="form-label">Other Dairy Sales Details</label>
                            <textarea name="other_dairy_sales_details" id="edit_other_dairy_sales_details" class="form-control" rows="2"></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update Outlet</button>
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
    $("#salesTable").DataTable({
        "order": [[0, "asc"]],
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
        $(\'#edit_center_name\').val($row.data(\'center_name\'));
        $(\'#edit_owner_proprietor\').val($row.data(\'owner_proprietor\'));
        $(\'#edit_average_milk_sales_lit_day\').val($row.data(\'average_milk_sales_lit_day\'));
        $(\'#edit_yoghurt_sales_cups_day\').val($row.data(\'yoghurt_sales_cups_day\'));
        $(\'#edit_curd_sales_pots_day\').val($row.data(\'curd_sales_pots_day\'));
        $(\'#edit_other_dairy_sales_details\').val($row.data(\'other_dairy_sales_details\'));
        $(\'#edit_average_selling_price_lit\').val($row.data(\'average_selling_price_lit\'));

        new bootstrap.Modal(document.getElementById(\'editOutletModal\')).show();
    });

    $(document).on(\'click\', \'.btn-delete\', function(e) {
        e.preventDefault();
        var deleteUrl = $(this).attr(\'href\');
        var $row = $(this).closest(\'tr\');
        var name = $row.data(\'center_name\');

        Swal.fire({
            icon: \'warning\',
            title: \'Delete Sales Center?\',
            html: \'Are you sure you want to permanently delete the outlet <strong>\' + name + \'</strong>?<br>This action cannot be undone.\',
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
