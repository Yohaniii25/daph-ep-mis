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

// Inline CRUD actions:
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        if ($_POST['action'] === 'add') {
            $vs_range = trim($_POST['vs_range']);
            $collecting_center_name = trim($_POST['collecting_center_name']);
            $address = trim($_POST['address']);
            $contact_no = trim($_POST['contact_no']);
            $milk_collection_lit_per_month = floatval($_POST['milk_collection_lit_per_month']);
            $milk_chilling_capacity = floatval($_POST['milk_chilling_capacity']);
            $milk_supply_to = trim($_POST['milk_supply_to']);

            $insert_query = "
                INSERT INTO milk_collecting_centers 
                (vs_range, collecting_center_name, address, contact_no, 
                 milk_collection_lit_per_month, milk_chilling_capacity, milk_supply_to)
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ";
            $stmt = $mysqli->prepare($insert_query);
            if ($stmt) {
                $stmt->bind_param(
                    "ssssdds",
                    $vs_range,
                    $collecting_center_name,
                    $address,
                    $contact_no,
                    $milk_collection_lit_per_month,
                    $milk_chilling_capacity,
                    $milk_supply_to
                );
                if ($stmt->execute()) {
                    header("Location: annual_milk_collecting.php?status=success&msg=" . urlencode("Collecting center added successfully."));
                } else {
                    header("Location: annual_milk_collecting.php?status=error&msg=" . urlencode("Failed to write to database: " . $stmt->error));
                }
                $stmt->close();
            } else {
                header("Location: annual_milk_collecting.php?status=error&msg=" . urlencode("Query preparation failed: " . $mysqli->error));
            }
            exit();
        } elseif ($_POST['action'] === 'edit') {
            $id = intval($_POST['id']);
            $vs_range = trim($_POST['vs_range']);
            $collecting_center_name = trim($_POST['collecting_center_name']);
            $address = trim($_POST['address']);
            $contact_no = trim($_POST['contact_no']);
            $milk_collection_lit_per_month = floatval($_POST['milk_collection_lit_per_month']);
            $milk_chilling_capacity = floatval($_POST['milk_chilling_capacity']);
            $milk_supply_to = trim($_POST['milk_supply_to']);

            $update_query = "
                UPDATE milk_collecting_centers 
                SET vs_range = ?, collecting_center_name = ?, address = ?, contact_no = ?, 
                    milk_collection_lit_per_month = ?, milk_chilling_capacity = ?, milk_supply_to = ?
                WHERE id = ? AND vs_range = ?
            ";
            $stmt = $mysqli->prepare($update_query);
            if ($stmt) {
                // vs_range (s), collecting_center_name (s), address (s), contact_no (s), 
                // milk_collection_lit_per_month (d), milk_chilling_capacity (d), milk_supply_to (s), 
                // id (i), vs_range (s) -> ssssddsis (9 placeholders)
                $stmt->bind_param(
                    "ssssddsis",
                    $vs_range,
                    $collecting_center_name,
                    $address,
                    $contact_no,
                    $milk_collection_lit_per_month,
                    $milk_chilling_capacity,
                    $milk_supply_to,
                    $id,
                    $range_name
                );
                if ($stmt->execute()) {
                    header("Location: annual_milk_collecting.php?status=success&msg=" . urlencode("Collecting center updated successfully."));
                } else {
                    header("Location: annual_milk_collecting.php?status=error&msg=" . urlencode("Failed to update database: " . $stmt->error));
                }
                $stmt->close();
            } else {
                header("Location: annual_milk_collecting.php?status=error&msg=" . urlencode("Query preparation failed: " . $mysqli->error));
            }
            exit();
        }
    }
}

if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $stmt = $mysqli->prepare("DELETE FROM milk_collecting_centers WHERE id = ? AND vs_range = ?");
    if ($stmt) {
        $stmt->bind_param("is", $id, $range_name);
        if ($stmt->execute()) {
            header("Location: annual_milk_collecting.php?status=success&msg=" . urlencode("Record deleted successfully."));
        } else {
            header("Location: annual_milk_collecting.php?status=error&msg=" . urlencode("Failed to delete record."));
        }
        $stmt->close();
    }
    exit();
}

// Fetch records matching VS range
$records = [];
if (!empty($range_name)) {
    $stmt = $mysqli->prepare("SELECT * FROM milk_collecting_centers WHERE vs_range = ? ORDER BY id DESC");
    if ($stmt) {
        $stmt->bind_param("s", $range_name);
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
    'center_count' => count($records),
    'total_collection' => 0
];
foreach ($records as $r) {
    $summary['total_collection'] += floatval($r['milk_collection_lit_per_month']);
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
                <h2 class="h4 fw-bold mb-1" style="color: #370709;">Details of Milk Collecting Centers</h2>
                <p class="text-muted small mb-0">Record and track milk collection volumes and capacities for <strong class="text-dark"><?= htmlspecialchars($range_name) ?></strong> (<?= htmlspecialchars($district_name) ?> District)</p>
            </div>
        </div>

        <!-- STATS CARD GROUP -->
        <div class="row g-3 mb-4">
            <div class="col-6 col-lg-6">
                <div class="card shadow-sm border-0 border-start border-success border-4 text-center">
                    <div class="card-body py-3">
                        <span class="text-muted small text-uppercase fw-bold">Total Centers</span>
                        <h4 class="mb-0 fw-bold text-success mt-1"><?= number_format($summary['center_count']) ?> Centers</h4>
                    </div>
                </div>
            </div>
            <div class="col-6 col-lg-6">
                <div class="card shadow-sm border-0 border-start border-primary border-4 text-center">
                    <div class="card-body py-3">
                        <span class="text-muted small text-uppercase fw-bold">Total Collection (Litre/Month)</span>
                        <h4 class="mb-0 fw-bold text-primary mt-1"><?= number_format($summary['total_collection'], 2) ?> Litres</h4>
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
                                <button class="btn btn-primary w-100 py-3 text-light border-0 shadow-sm d-flex flex-column align-items-center justify-content-center" style="background-color: #820100; min-height: 105px;" data-bs-toggle="modal" data-bs-target="#addCenterModal">
                                    <i class="bi bi-plus-circle fs-3 mb-1"></i>
                                    <span class="small fw-bold text-uppercase">Add Collecting Center</span>
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
                <h5 class="card-title mb-0 fw-bold text-dark"><i class="bi bi-table me-2"></i>Milk Collecting Centers Directory</h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0" id="centerTable" style="min-width: 1400px;">
                        <thead class="table-light text-secondary small uppercase">
                            <tr>
                                <th>S.no</th>
                                <th>VS Range</th>
                                <th>Collcting Center Name</th>
                                <th>Address</th>
                                <th>Contact No</th>
                                <th class="text-end">Milk Collection lit / Month</th>
                                <th class="text-end">Milk Chilling Capacity</th>
                                <th>Milk Supply to</th>
                                <th class="text-center" style="width: 12%">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="small">
                            <?php foreach ($records as $row): ?>
                                <tr
                                    data-id="<?= $row['id'] ?>"
                                    data-vs_range="<?= htmlspecialchars($row['vs_range']) ?>"
                                    data-center_name="<?= htmlspecialchars($row['collecting_center_name']) ?>"
                                    data-address="<?= htmlspecialchars($row['address']) ?>"
                                    data-contact_no="<?= htmlspecialchars($row['contact_no']) ?>"
                                    data-collection="<?= htmlspecialchars($row['milk_collection_lit_per_month']) ?>"
                                    data-capacity="<?= htmlspecialchars($row['milk_chilling_capacity']) ?>"
                                    data-supply_to="<?= htmlspecialchars($row['milk_supply_to']) ?>">
                                    <td class="fw-bold text-center"><?= htmlspecialchars($row['id']) ?></td>
                                    <td><?= htmlspecialchars($row['vs_range']) ?></td>
                                    <td class="fw-bold"><?= htmlspecialchars($row['collecting_center_name']) ?></td>
                                    <td><?= nl2br(htmlspecialchars($row['address'])) ?></td>
                                    <td><?= htmlspecialchars($row['contact_no']) ?></td>
                                    <td class="text-end font-monospace text-primary fw-bold"><?= number_format($row['milk_collection_lit_per_month'], 2) ?></td>
                                    <td class="text-end font-monospace"><?= number_format($row['milk_chilling_capacity'], 2) ?></td>
                                    <td><?= htmlspecialchars($row['milk_supply_to']) ?></td>
                                    <td class="text-center">
                                        <button class="btn btn-sm btn-outline-info btn-view" title="View"><i class="bi bi-eye-fill"></i></button>
                                        <button class="btn btn-sm btn-outline-primary btn-edit" title="Edit"><i class="bi bi-pencil-fill"></i></button>
                                        <a href="annual_milk_collecting.php?action=delete&id=<?= $row['id'] ?>" class="btn btn-sm btn-outline-danger btn-delete" title="Delete"><i class="bi bi-trash-fill"></i></a>
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
<div class="modal fade" id="addCenterModal" tabindex="-1" aria-labelledby="addCenterModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <form method="POST">
            <input type="hidden" name="action" value="add">
            <div class="modal-content">
                <div class="modal-header" style="background-color: #370709; color: white;">
                    <h5 class="modal-title" id="addCenterModalLabel"><i class="bi bi-plus-circle me-2"></i>Add Milk Collecting Center</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold">VS Range</label>
                            <input type="text" name="vs_range" class="form-control" value="<?= htmlspecialchars($range_name) ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Collcting Center Name</label>
                            <input type="text" name="collecting_center_name" class="form-control" placeholder="e.g. MILCO Hub Balapitiya" required>
                        </div>
                        <div class="col-md-12">
                            <label class="form-label fw-bold">Address</label>
                            <textarea name="address" class="form-control" rows="2" placeholder="Detail Address of Center" required></textarea>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Contact No</label>
                            <input type="text" name="contact_no" class="form-control" placeholder="Telephone/Mobile">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Milk Supply to</label>
                            <input type="text" name="milk_supply_to" class="form-control" placeholder="e.g. MILCO, Nestlé, Cargills">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Milk Collection lit / Month</label>
                            <input type="number" step="0.01" name="milk_collection_lit_per_month" class="form-control" value="0.00" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Milk Chilling Capacity</label>
                            <input type="number" step="0.01" name="milk_chilling_capacity" class="form-control" value="0.00" required>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success">Save Center</button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Modal: Edit Record -->
<div class="modal fade" id="editCenterModal" tabindex="-1" aria-labelledby="editCenterModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <form method="POST">
            <input type="hidden" name="action" value="edit">
            <input type="hidden" name="id" id="edit_id">
            <div class="modal-content">
                <div class="modal-header" style="background-color: #370709; color: white;">
                    <h5 class="modal-title" id="editCenterModalLabel"><i class="bi bi-pencil-fill me-2"></i>Edit Milk Collecting Center</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold">VS Range</label>
                            <input type="text" name="vs_range" id="edit_vs_range" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Collcting Center Name</label>
                            <input type="text" name="collecting_center_name" id="edit_center_name" class="form-control" required>
                        </div>
                        <div class="col-md-12">
                            <label class="form-label fw-bold">Address</label>
                            <textarea name="address" id="edit_address" class="form-control" rows="2" required></textarea>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Contact No</label>
                            <input type="text" name="contact_no" id="edit_contact_no" class="form-control">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Milk Supply to</label>
                            <input type="text" name="milk_supply_to" id="edit_supply_to" class="form-control">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Milk Collection lit / Month</label>
                            <input type="number" step="0.01" name="milk_collection_lit_per_month" id="edit_collection" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Milk Chilling Capacity</label>
                            <input type="number" step="0.01" name="milk_chilling_capacity" id="edit_capacity" class="form-control" required>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update Center</button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Modal: View Record -->
<div class="modal fade" id="viewCenterModal" tabindex="-1" aria-labelledby="viewCenterModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header" style="background-color: #370709; color: white;">
                <h5 class="modal-title" id="viewCenterModalLabel"><i class="bi bi-eye-fill me-2"></i>Milk Collecting Center Details</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <table class="table table-bordered table-striped">
                    <tbody>
                        <tr>
                            <th style="width: 35%;">VS Range</th>
                            <td id="view_vs_range"></td>
                        </tr>
                        <tr>
                            <th>Collcting Center Name</th>
                            <td id="view_center_name"></td>
                        </tr>
                        <tr>
                            <th>Address</th>
                            <td id="view_address"></td>
                        </tr>
                        <tr>
                            <th>Contact No</th>
                            <td id="view_contact_no"></td>
                        </tr>
                        <tr>
                            <th>Milk Collection lit / Month</th>
                            <td id="view_collection" class="font-monospace fw-bold text-primary"></td>
                        </tr>
                        <tr>
                            <th>Milk Chilling Capacity</th>
                            <td id="view_capacity" class="font-monospace"></td>
                        </tr>
                        <tr>
                            <th>Milk Supply to</th>
                            <td id="view_supply_to"></td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <div class="modal-footer bg-light">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<?php
$pageScripts = '
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
$(document).ready(function() {
    $("#centerTable").DataTable({
        "order": [[0, "asc"]],
        "pageLength": 10,
        "dom": "Bfrtip",
        "language": {
            "emptyTable": "No records located for this range."
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

    $(document).on(\'click\', \'.btn-view\', function() {
        var $row = $(this).closest(\'tr\');
        $(\'#view_vs_range\').text($row.data(\'vs_range\'));
        $(\'#view_center_name\').text($row.data(\'center_name\'));
        $(\'#view_address\').html(($row.data(\'address\') || \'\').replace(/\\n/g, \'<br>\'));
        $(\'#view_contact_no\').text($row.data(\'contact_no\') || \'N/A\');
        
        var collection = parseFloat($row.data(\'collection\')) || 0;
        var capacity = parseFloat($row.data(\'capacity\')) || 0;
        
        $(\'#view_collection\').text(collection.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2}) + " Litres");
        $(\'#view_capacity\').text(capacity.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2}));
        $(\'#view_supply_to\').text($row.data(\'supply_to\') || \'N/A\');

        new bootstrap.Modal(document.getElementById(\'viewCenterModal\')).show();
    });

    $(document).on(\'click\', \'.btn-edit\', function() {
        var $row = $(this).closest(\'tr\');
        $(\'#edit_id\').val($row.data(\'id\'));
        $(\'#edit_vs_range\').val($row.data(\'vs_range\'));
        $(\'#edit_center_name\').val($row.data(\'center_name\'));
        $(\'#edit_address\').val($row.data(\'address\'));
        $(\'#edit_contact_no\').val($row.data(\'contact_no\'));
        $(\'#edit_collection\').val($row.data(\'collection\'));
        $(\'#edit_capacity\').val($row.data(\'capacity\'));
        $(\'#edit_supply_to\').val($row.data(\'supply_to\'));

        new bootstrap.Modal(document.getElementById(\'editCenterModal\')).show();
    });

    $(document).on(\'click\', \'.btn-delete\', function(e) {
        e.preventDefault();
        var deleteUrl = $(this).attr(\'href\');
        var $row = $(this).closest(\'tr\');
        var id = $row.data(\'id\');

        Swal.fire({
            icon: \'warning\',
            title: \'Delete Collecting Center?\',
            html: \'Are you sure you want to permanently delete the center record <strong>#\' + id + \'</strong>?<br>This action cannot be undone.\',
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