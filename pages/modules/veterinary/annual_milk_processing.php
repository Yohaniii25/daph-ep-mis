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
            $processing_center_name = trim($_POST['processing_center_name']);
            $address = trim($_POST['address']);
            $contact_no = trim($_POST['contact_no']);
            $yoghurt_lit_per_month = floatval($_POST['yoghurt_lit_per_month']);
            $curd_lit_per_month = floatval($_POST['curd_lit_per_month']);
            $ice_cream_lit_per_month = floatval($_POST['ice_cream_lit_per_month']);
            $ghee_lit_per_month = floatval($_POST['ghee_lit_per_month']);
            $other_milk_product_lit_per_month = floatval($_POST['other_milk_product_lit_per_month']);
            $total_lit_per_month = floatval($_POST['total_lit_per_month']);
            $income_rs_per_month = floatval($_POST['income_rs_per_month']);

            $insert_query = "
                INSERT INTO milk_processing_centers 
                (vs_range, processing_center_name, address, contact_no, 
                 yoghurt_lit_per_month, curd_lit_per_month, ice_cream_lit_per_month, 
                 ghee_lit_per_month, other_milk_product_lit_per_month, total_lit_per_month, 
                 income_rs_per_month)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ";
            $stmt = $mysqli->prepare($insert_query);
            if ($stmt) {
                // vs_range (s), processing_center_name (s), address (s), contact_no (s),
                // yoghurt_lit_per_month (d), curd_lit_per_month (d), ice_cream_lit_per_month (d),
                // ghee_lit_per_month (d), other_milk_product_lit_per_month (d), total_lit_per_month (d),
                // income_rs_per_month (d) -> ssssddddddd (11 placeholders)
                $stmt->bind_param(
                    "ssssddddddd",
                    $vs_range,
                    $processing_center_name,
                    $address,
                    $contact_no,
                    $yoghurt_lit_per_month,
                    $curd_lit_per_month,
                    $ice_cream_lit_per_month,
                    $ghee_lit_per_month,
                    $other_milk_product_lit_per_month,
                    $total_lit_per_month,
                    $income_rs_per_month
                );
                if ($stmt->execute()) {
                    header("Location: annual_milk_processing.php?status=success&msg=" . urlencode("Processing center added successfully."));
                } else {
                    header("Location: annual_milk_processing.php?status=error&msg=" . urlencode("Failed to write to database: " . $stmt->error));
                }
                $stmt->close();
            } else {
                header("Location: annual_milk_processing.php?status=error&msg=" . urlencode("Query preparation failed: " . $mysqli->error));
            }
            exit();
        } elseif ($_POST['action'] === 'edit') {
            $id = intval($_POST['id']);
            $vs_range = trim($_POST['vs_range']);
            $processing_center_name = trim($_POST['processing_center_name']);
            $address = trim($_POST['address']);
            $contact_no = trim($_POST['contact_no']);
            $yoghurt_lit_per_month = floatval($_POST['yoghurt_lit_per_month']);
            $curd_lit_per_month = floatval($_POST['curd_lit_per_month']);
            $ice_cream_lit_per_month = floatval($_POST['ice_cream_lit_per_month']);
            $ghee_lit_per_month = floatval($_POST['ghee_lit_per_month']);
            $other_milk_product_lit_per_month = floatval($_POST['other_milk_product_lit_per_month']);
            $total_lit_per_month = floatval($_POST['total_lit_per_month']);
            $income_rs_per_month = floatval($_POST['income_rs_per_month']);

            $update_query = "
                UPDATE milk_processing_centers 
                SET vs_range = ?, processing_center_name = ?, address = ?, contact_no = ?, 
                    yoghurt_lit_per_month = ?, curd_lit_per_month = ?, ice_cream_lit_per_month = ?, 
                    ghee_lit_per_month = ?, other_milk_product_lit_per_month = ?, total_lit_per_month = ?, 
                    income_rs_per_month = ?
                WHERE id = ? AND vs_range = ?
            ";
            $stmt = $mysqli->prepare($update_query);
            if ($stmt) {
                // vs_range (s), processing_center_name (s), address (s), contact_no (s), 
                // yoghurt_lit_per_month (d), curd_lit_per_month (d), ice_cream_lit_per_month (d), 
                // ghee_lit_per_month (d), other_milk_product_lit_per_month (d), total_lit_per_month (d), 
                // income_rs_per_month (d), id (i), vs_range (s) -> ssssdddddddis (13 placeholders)
                $stmt->bind_param(
                    "ssssdddddddis",
                    $vs_range,
                    $processing_center_name,
                    $address,
                    $contact_no,
                    $yoghurt_lit_per_month,
                    $curd_lit_per_month,
                    $ice_cream_lit_per_month,
                    $ghee_lit_per_month,
                    $other_milk_product_lit_per_month,
                    $total_lit_per_month,
                    $income_rs_per_month,
                    $id,
                    $range_name
                );
                if ($stmt->execute()) {
                    header("Location: annual_milk_processing.php?status=success&msg=" . urlencode("Processing center updated successfully."));
                } else {
                    header("Location: annual_milk_processing.php?status=error&msg=" . urlencode("Failed to update database: " . $stmt->error));
                }
                $stmt->close();
            } else {
                header("Location: annual_milk_processing.php?status=error&msg=" . urlencode("Query preparation failed: " . $mysqli->error));
            }
            exit();
        }
    }
}

if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $stmt = $mysqli->prepare("DELETE FROM milk_processing_centers WHERE id = ? AND vs_range = ?");
    if ($stmt) {
        $stmt->bind_param("is", $id, $range_name);
        if ($stmt->execute()) {
            header("Location: annual_milk_processing.php?status=success&msg=" . urlencode("Record deleted successfully."));
        } else {
            header("Location: annual_milk_processing.php?status=error&msg=" . urlencode("Failed to delete record."));
        }
        $stmt->close();
    }
    exit();
}

// Fetch records matching VS range
$records = [];
if (!empty($range_name)) {
    $stmt = $mysqli->prepare("SELECT * FROM milk_processing_centers WHERE vs_range = ? ORDER BY id DESC");
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
    'total_processed' => 0,
    'total_income' => 0
];
foreach ($records as $r) {
    $summary['total_processed'] += floatval($r['total_lit_per_month']);
    $summary['total_income'] += floatval($r['income_rs_per_month']);
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
                <h2 class="h4 fw-bold mb-1" style="color: #370709;">Details of Milk Processing Centers</h2>
                <p class="text-muted small mb-0">Record and track processing volumes and products for <strong class="text-dark"><?= htmlspecialchars($range_name) ?></strong> (<?= htmlspecialchars($district_name) ?> District)</p>
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
                        <span class="text-muted small text-uppercase fw-bold">Total Processed (Litre/Month)</span>
                        <h4 class="mb-0 fw-bold text-primary mt-1"><?= number_format($summary['total_processed'], 2) ?> Litres</h4>
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
                                    <span class="small fw-bold text-uppercase">Add Processing Centre</span>
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
                <h5 class="card-title mb-0 fw-bold text-dark"><i class="bi bi-table me-2"></i>Milk Processing Centers log</h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0 table-bordered" id="centerTable" style="min-width: 1600px;">
                        <thead class="table-light text-secondary small uppercase">
                            <tr>
                                <th rowspan="2" class="align-middle text-center">S.No</th>
                                <th rowspan="2" class="align-middle">VS Range</th>
                                <th rowspan="2" class="align-middle">Processing Center Name</th>
                                <th rowspan="2" class="align-middle">Address</th>
                                <th rowspan="2" class="align-middle">Contact No</th>
                                <th colspan="6" class="text-center">Milk process lit / month</th>
                                <th rowspan="2" class="align-middle text-end">Income Rs / month</th>
                                <th rowspan="2" class="align-middle text-center" style="width: 12%">Actions</th>
                            </tr>
                            <tr class="table-light text-secondary small">
                                <th class="text-end">Yoghurt lit / month</th>
                                <th class="text-end">Curd lit / month</th>
                                <th class="text-end">Ice cream lit / month</th>
                                <th class="text-end">Ghee lit / month</th>
                                <th class="text-end">Other milk product lit / month</th>
                                <th class="text-end fw-bold bg-light">Total lit / month</th>
                            </tr>
                        </thead>
                        <tbody class="small">
                            <?php foreach ($records as $row): ?>
                                <tr
                                    data-id="<?= $row['id'] ?>"
                                    data-vs_range="<?= htmlspecialchars($row['vs_range']) ?>"
                                    data-center_name="<?= htmlspecialchars($row['processing_center_name']) ?>"
                                    data-address="<?= htmlspecialchars($row['address']) ?>"
                                    data-contact_no="<?= htmlspecialchars($row['contact_no']) ?>"
                                    data-yoghurt="<?= htmlspecialchars($row['yoghurt_lit_per_month']) ?>"
                                    data-curd="<?= htmlspecialchars($row['curd_lit_per_month']) ?>"
                                    data-ice_cream="<?= htmlspecialchars($row['ice_cream_lit_per_month']) ?>"
                                    data-ghee="<?= htmlspecialchars($row['ghee_lit_per_month']) ?>"
                                    data-other_product="<?= htmlspecialchars($row['other_milk_product_lit_per_month']) ?>"
                                    data-total="<?= htmlspecialchars($row['total_lit_per_month']) ?>"
                                    data-income="<?= htmlspecialchars($row['income_rs_per_month']) ?>">
                                    <td class="fw-bold text-center"><?= htmlspecialchars($row['id']) ?></td>
                                    <td><?= htmlspecialchars($row['vs_range']) ?></td>
                                    <td class="fw-bold"><?= htmlspecialchars($row['processing_center_name']) ?></td>
                                    <td><?= nl2br(htmlspecialchars($row['address'])) ?></td>
                                    <td><?= htmlspecialchars($row['contact_no']) ?></td>
                                    <td class="text-end font-monospace"><?= number_format($row['yoghurt_lit_per_month'], 2) ?></td>
                                    <td class="text-end font-monospace"><?= number_format($row['curd_lit_per_month'], 2) ?></td>
                                    <td class="text-end font-monospace"><?= number_format($row['ice_cream_lit_per_month'], 2) ?></td>
                                    <td class="text-end font-monospace"><?= number_format($row['ghee_lit_per_month'], 2) ?></td>
                                    <td class="text-end font-monospace"><?= number_format($row['other_milk_product_lit_per_month'], 2) ?></td>
                                    <td class="text-end font-monospace fw-bold bg-light"><?= number_format($row['total_lit_per_month'], 2) ?></td>
                                    <td class="text-end font-monospace fw-bold text-success">Rs. <?= number_format($row['income_rs_per_month'], 2) ?></td>
                                    <td class="text-center">
                                        <button class="btn btn-sm btn-outline-info btn-view" title="View"><i class="bi bi-eye-fill"></i></button>
                                        <button class="btn btn-sm btn-outline-primary btn-edit" title="Edit"><i class="bi bi-pencil-fill"></i></button>
                                        <a href="annual_milk_processing.php?action=delete&id=<?= $row['id'] ?>" class="btn btn-sm btn-outline-danger btn-delete" title="Delete"><i class="bi bi-trash-fill"></i></a>
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
        <form method="POST" id="addCenterForm">
            <input type="hidden" name="action" value="add">
            <div class="modal-content">
                <div class="modal-header" style="background-color: #370709; color: white;">
                    <h5 class="modal-title" id="addCenterModalLabel"><i class="bi bi-plus-circle me-2"></i>Add Milk Processing Centre</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold">VS Range</label>
                            <input type="text" name="vs_range" class="form-control" value="<?= htmlspecialchars($range_name) ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Processing Center Name</label>
                            <input type="text" name="processing_center_name" class="form-control" placeholder="e.g. Balapitiya Milk Coop." required>
                        </div>
                        <div class="col-md-12">
                            <label class="form-label fw-bold">Address</label>
                            <textarea name="address" class="form-control" rows="2" placeholder="Full Address of Centre" required></textarea>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Contact No</label>
                            <input type="text" name="contact_no" class="form-control" placeholder="Telephone/Mobile">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold text-success">Income Rs / month</label>
                            <input type="number" step="0.01" name="income_rs_per_month" class="form-control" value="0.00" required>
                        </div>

                        <div class="col-12 mt-4">
                            <h6 class="fw-bold border-bottom pb-2 text-secondary">Milk Processing Volume (lit / month)</h6>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Yoghurt lit / month</label>
                            <input type="number" step="0.01" name="yoghurt_lit_per_month" id="add_yoghurt" class="form-control volume-input" value="0.00">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Curd lit / month</label>
                            <input type="number" step="0.01" name="curd_lit_per_month" id="add_curd" class="form-control volume-input" value="0.00">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Ice cream lit / month</label>
                            <input type="number" step="0.01" name="ice_cream_lit_per_month" id="add_ice_cream" class="form-control volume-input" value="0.00">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Ghee lit / month</label>
                            <input type="number" step="0.01" name="ghee_lit_per_month" id="add_ghee" class="form-control volume-input" value="0.00">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Other milk product lit / month</label>
                            <input type="number" step="0.01" name="other_milk_product_lit_per_month" id="add_other" class="form-control volume-input" value="0.00">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold">Total lit / month</label>
                            <input type="number" step="0.01" name="total_lit_per_month" id="add_total" class="form-control bg-light" value="0.00" required>
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
        <form method="POST" id="editCenterForm">
            <input type="hidden" name="action" value="edit">
            <input type="hidden" name="id" id="edit_id">
            <div class="modal-content">
                <div class="modal-header" style="background-color: #370709; color: white;">
                    <h5 class="modal-title" id="editCenterModalLabel"><i class="bi bi-pencil-fill me-2"></i>Edit Milk Processing Centre</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold">VS Range</label>
                            <input type="text" name="vs_range" id="edit_vs_range" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Processing Center Name</label>
                            <input type="text" name="processing_center_name" id="edit_center_name" class="form-control" required>
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
                            <label class="form-label fw-bold text-success">Income Rs / month</label>
                            <input type="number" step="0.01" name="income_rs_per_month" id="edit_income" class="form-control" required>
                        </div>

                        <div class="col-12 mt-4">
                            <h6 class="fw-bold border-bottom pb-2 text-secondary">Milk Processing Volume (lit / month)</h6>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Yoghurt lit / month</label>
                            <input type="number" step="0.01" name="yoghurt_lit_per_month" id="edit_yoghurt" class="form-control edit-volume-input">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Curd lit / month</label>
                            <input type="number" step="0.01" name="curd_lit_per_month" id="edit_curd" class="form-control edit-volume-input">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Ice cream lit / month</label>
                            <input type="number" step="0.01" name="ice_cream_lit_per_month" id="edit_ice_cream" class="form-control edit-volume-input">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Ghee lit / month</label>
                            <input type="number" step="0.01" name="ghee_lit_per_month" id="edit_ghee" class="form-control edit-volume-input">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Other milk product lit / month</label>
                            <input type="number" step="0.01" name="other_milk_product_lit_per_month" id="edit_other" class="form-control edit-volume-input">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold">Total lit / month</label>
                            <input type="number" step="0.01" name="total_lit_per_month" id="edit_total" class="form-control bg-light" required>
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
                <h5 class="modal-title" id="viewCenterModalLabel"><i class="bi bi-eye-fill me-2"></i>Milk Processing Center Details</h5>
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
                            <th>Processing Center Name</th>
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
                            <th>Yoghurt lit / month</th>
                            <td id="view_yoghurt" class="font-monospace"></td>
                        </tr>
                        <tr>
                            <th>Curd lit / month</th>
                            <td id="view_curd" class="font-monospace"></td>
                        </tr>
                        <tr>
                            <th>Ice cream lit / month</th>
                            <td id="view_ice_cream" class="font-monospace"></td>
                        </tr>
                        <tr>
                            <th>Ghee lit / month</th>
                            <td id="view_ghee" class="font-monospace"></td>
                        </tr>
                        <tr>
                            <th>Other milk product lit / month</th>
                            <td id="view_other" class="font-monospace"></td>
                        </tr>
                        <tr>
                            <th>Total lit / month</th>
                            <td id="view_total" class="font-monospace fw-bold bg-light"></td>
                        </tr>
                        <tr>
                            <th>Income Rs / month</th>
                            <td id="view_income" class="font-monospace fw-bold text-success"></td>
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

    // Helper functions to auto calculate sum
    function calculateAddTotal() {
        var yoghurt = parseFloat($("#add_yoghurt").val()) || 0;
        var curd = parseFloat($("#add_curd").val()) || 0;
        var ice_cream = parseFloat($("#add_ice_cream").val()) || 0;
        var ghee = parseFloat($("#add_ghee").val()) || 0;
        var other = parseFloat($("#add_other").val()) || 0;
        var total = yoghurt + curd + ice_cream + ghee + other;
        $("#add_total").val(total.toFixed(2));
    }
    
    function calculateEditTotal() {
        var yoghurt = parseFloat($("#edit_yoghurt").val()) || 0;
        var curd = parseFloat($("#edit_curd").val()) || 0;
        var ice_cream = parseFloat($("#edit_ice_cream").val()) || 0;
        var ghee = parseFloat($("#edit_ghee").val()) || 0;
        var other = parseFloat($("#edit_other").val()) || 0;
        var total = yoghurt + curd + ice_cream + ghee + other;
        $("#edit_total").val(total.toFixed(2));
    }

    $(".volume-input").on("input", calculateAddTotal);
    $(".edit-volume-input").on("input", calculateEditTotal);

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
        
        var yoghurt = parseFloat($row.data(\'yoghurt\')) || 0;
        var curd = parseFloat($row.data(\'curd\')) || 0;
        var ice_cream = parseFloat($row.data(\'ice_cream\')) || 0;
        var ghee = parseFloat($row.data(\'ghee\')) || 0;
        var other = parseFloat($row.data(\'other_product\')) || 0;
        var total = parseFloat($row.data(\'total\')) || 0;
        var income = parseFloat($row.data(\'income\')) || 0;
        
        $(\'#view_yoghurt\').text(yoghurt.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2}));
        $(\'#view_curd\').text(curd.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2}));
        $(\'#view_ice_cream\').text(ice_cream.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2}));
        $(\'#view_ghee\').text(ghee.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2}));
        $(\'#view_other\').text(other.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2}));
        $(\'#view_total\').text(total.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2}));
        $(\'#view_income\').text("Rs. " + income.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2}));

        new bootstrap.Modal(document.getElementById(\'viewCenterModal\')).show();
    });

    $(document).on(\'click\', \'.btn-edit\', function() {
        var $row = $(this).closest(\'tr\');
        $(\'#edit_id\').val($row.data(\'id\'));
        $(\'#edit_vs_range\').val($row.data(\'vs_range\'));
        $(\'#edit_center_name\').val($row.data(\'center_name\'));
        $(\'#edit_address\').val($row.data(\'address\'));
        $(\'#edit_contact_no\').val($row.data(\'contact_no\'));
        $(\'#edit_yoghurt\').val($row.data(\'yoghurt\'));
        $(\'#edit_curd\').val($row.data(\'curd\'));
        $(\'#edit_ice_cream\').val($row.data(\'ice_cream\'));
        $(\'#edit_ghee\').val($row.data(\'ghee\'));
        $(\'#edit_other\').val($row.data(\'other_product\'));
        $(\'#edit_total\').val($row.data(\'total\'));
        $(\'#edit_income\').val($row.data(\'income\'));

        new bootstrap.Modal(document.getElementById(\'editCenterModal\')).show();
    });

    $(document).on(\'click\', \'.btn-delete\', function(e) {
        e.preventDefault();
        var deleteUrl = $(this).attr(\'href\');
        var $row = $(this).closest(\'tr\');
        var id = $row.data(\'id\');

        Swal.fire({
            icon: \'warning\',
            title: \'Delete Processing Center?\',
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