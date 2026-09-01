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
            $gn_division = trim($_POST['gn_division']);
            $name_address = trim($_POST['name_address']);
            $overall_objective = trim($_POST['overall_objective']);
            $total_members = intval($_POST['total_members']);
            $reg_no = trim($_POST['reg_no']);
            $reg_department = trim($_POST['reg_department']);
            $major_activities = trim($_POST['major_activities']);
            $financial_records_availability = trim($_POST['financial_records_availability']);
            $regulated_by = trim($_POST['regulated_by']);
            $tp_no = trim($_POST['tp_no']);

            $insert_query = "
                INSERT INTO livestock_societies 
                (vs_range, gn_division, name_address, overall_objective, total_members, 
                 reg_no, reg_department, major_activities, financial_records_availability, 
                 regulated_by, tp_no)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ";
            $stmt = $mysqli->prepare($insert_query);
            if ($stmt) {
                $stmt->bind_param(
                    "ssssissssss",
                    $vs_range,
                    $gn_division,
                    $name_address,
                    $overall_objective,
                    $total_members,
                    $reg_no,
                    $reg_department,
                    $major_activities,
                    $financial_records_availability,
                    $regulated_by,
                    $tp_no
                );
                if ($stmt->execute()) {
                    header("Location: annual_livestock_societies.php?status=success&msg=" . urlencode("Livestock society added successfully."));
                } else {
                    header("Location: annual_livestock_societies.php?status=error&msg=" . urlencode("Failed to write to database: " . $stmt->error));
                }
                $stmt->close();
            } else {
                header("Location: annual_livestock_societies.php?status=error&msg=" . urlencode("Query preparation failed: " . $mysqli->error));
            }
            exit();
        } elseif ($_POST['action'] === 'edit') {
            $id = intval($_POST['id']);
            $vs_range = trim($_POST['vs_range']);
            $gn_division = trim($_POST['gn_division']);
            $name_address = trim($_POST['name_address']);
            $overall_objective = trim($_POST['overall_objective']);
            $total_members = intval($_POST['total_members']);
            $reg_no = trim($_POST['reg_no']);
            $reg_department = trim($_POST['reg_department']);
            $major_activities = trim($_POST['major_activities']);
            $financial_records_availability = trim($_POST['financial_records_availability']);
            $regulated_by = trim($_POST['regulated_by']);
            $tp_no = trim($_POST['tp_no']);

            $update_query = "
                UPDATE livestock_societies 
                SET vs_range = ?, gn_division = ?, name_address = ?, overall_objective = ?, total_members = ?, 
                    reg_no = ?, reg_department = ?, major_activities = ?, financial_records_availability = ?, 
                    regulated_by = ?, tp_no = ?
                WHERE id = ? AND vs_range = ?
            ";
            $stmt = $mysqli->prepare($update_query);
            if ($stmt) {
                $stmt->bind_param(
                    "ssssissssssis",
                    $vs_range,
                    $gn_division,
                    $name_address,
                    $overall_objective,
                    $total_members,
                    $reg_no,
                    $reg_department,
                    $major_activities,
                    $financial_records_availability,
                    $regulated_by,
                    $tp_no,
                    $id,
                    $range_name
                );
                if ($stmt->execute()) {
                    header("Location: annual_livestock_societies.php?status=success&msg=" . urlencode("Livestock society updated successfully."));
                } else {
                    header("Location: annual_livestock_societies.php?status=error&msg=" . urlencode("Failed to update database: " . $stmt->error));
                }
                $stmt->close();
            } else {
                header("Location: annual_livestock_societies.php?status=error&msg=" . urlencode("Query preparation failed: " . $mysqli->error));
            }
            exit();
        }
    }
}

if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $stmt = $mysqli->prepare("DELETE FROM livestock_societies WHERE id = ? AND vs_range = ?");
    if ($stmt) {
        $stmt->bind_param("is", $id, $range_name);
        if ($stmt->execute()) {
            header("Location: annual_livestock_societies.php?status=success&msg=" . urlencode("Record deleted successfully."));
        } else {
            header("Location: annual_livestock_societies.php?status=error&msg=" . urlencode("Failed to delete record."));
        }
        $stmt->close();
    }
    exit();
}

// Fetch records matching VS range
$records = [];
if (!empty($range_name)) {
    $stmt = $mysqli->prepare("SELECT * FROM livestock_societies WHERE vs_range = ? ORDER BY id DESC");
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
    'soc_count' => count($records),
    'total_members' => 0
];
foreach ($records as $r) {
    $summary['total_members'] += intval($r['total_members']);
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
                <h2 class="h4 fw-bold mb-1" style="color: #370709;">Details of Livestock Societies</h2>
                <p class="text-muted small mb-0">Record and monitor livestock cooperative societies for <strong class="text-dark"><?= htmlspecialchars($range_name) ?></strong> (<?= htmlspecialchars($district_name) ?> District)</p>
            </div>
        </div>

        <!-- STATS CARD GROUP -->
        <div class="row g-3 mb-4">
            <div class="col-6 col-lg-6">
                <div class="card shadow-sm border-0 border-start border-success border-4 text-center">
                    <div class="card-body py-3">
                        <span class="text-muted small text-uppercase fw-bold">Active Societies</span>
                        <h4 class="mb-0 fw-bold text-success mt-1"><?= number_format($summary['soc_count']) ?></h4>
                    </div>
                </div>
            </div>
            <div class="col-6 col-lg-6">
                <div class="card shadow-sm border-0 border-start border-info border-4 text-center">
                    <div class="card-body py-3">
                        <span class="text-muted small text-uppercase fw-bold">Total Members Registered</span>
                        <h4 class="mb-0 fw-bold text-info mt-1"><?= number_format($summary['total_members']) ?></h4>
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
                                <button class="btn btn-primary w-100 py-3 text-light border-0 shadow-sm d-flex flex-column align-items-center justify-content-center" style="background-color: #820100; min-height: 105px;" data-bs-toggle="modal" data-bs-target="#addSocModal">
                                    <i class="bi bi-plus-circle fs-3 mb-1"></i>
                                    <span class="small fw-bold text-uppercase">Add Livestock Society</span>
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
                <h5 class="card-title mb-0 fw-bold text-dark"><i class="bi bi-table me-2"></i>Societies Log Directory</h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0" id="socTable" style="min-width: 1600px;">
                        <thead class="table-light text-secondary small uppercase">
                            <tr>
                                <th>S.no</th>
                                <th>VS Range</th>
                                <th>G N Division</th>
                                <th>Name & Address</th>
                                <th>Overall Objective</th>
                                <th class="text-end">Total Members</th>
                                <th>Reg. No</th>
                                <th>Reg. Department</th>
                                <th>Major Activities</th>
                                <th>Availability of Financial Records</th>
                                <th>Regulated By</th>
                                <th>T.P.No</th>
                                <th class="text-center" style="width: 12%">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="small">
                            <?php foreach ($records as $row): ?>
                                <tr
                                    data-id="<?= $row['id'] ?>"
                                    data-vs_range="<?= htmlspecialchars($row['vs_range']) ?>"
                                    data-gn_division="<?= htmlspecialchars($row['gn_division']) ?>"
                                    data-name_address="<?= htmlspecialchars($row['name_address']) ?>"
                                    data-overall_objective="<?= htmlspecialchars($row['overall_objective']) ?>"
                                    data-total_members="<?= htmlspecialchars($row['total_members']) ?>"
                                    data-reg_no="<?= htmlspecialchars($row['reg_no']) ?>"
                                    data-reg_department="<?= htmlspecialchars($row['reg_department']) ?>"
                                    data-major_activities="<?= htmlspecialchars($row['major_activities']) ?>"
                                    data-financial_records_availability="<?= htmlspecialchars($row['financial_records_availability']) ?>"
                                    data-regulated_by="<?= htmlspecialchars($row['regulated_by']) ?>"
                                    data-tp_no="<?= htmlspecialchars($row['tp_no']) ?>">
                                    <td class="fw-bold text-center"><?= htmlspecialchars($row['id']) ?></td>
                                    <td><?= htmlspecialchars($row['vs_range']) ?></td>
                                    <td><?= htmlspecialchars($row['gn_division']) ?></td>
                                    <td><?= nl2br(htmlspecialchars($row['name_address'])) ?></td>
                                    <td><?= nl2br(htmlspecialchars($row['overall_objective'])) ?></td>
                                    <td class="text-end font-monospace"><?= number_format($row['total_members']) ?></td>
                                    <td><?= htmlspecialchars($row['reg_no']) ?></td>
                                    <td><?= htmlspecialchars($row['reg_department']) ?></td>
                                    <td><?= nl2br(htmlspecialchars($row['major_activities'])) ?></td>
                                    <td class="text-center">
                                        <span class="badge <?= $row['financial_records_availability'] === 'Yes' ? 'bg-success' : ($row['financial_records_availability'] === 'No' ? 'bg-danger' : 'bg-secondary') ?>">
                                            <?= htmlspecialchars($row['financial_records_availability']) ?>
                                        </span>
                                    </td>
                                    <td><?= htmlspecialchars($row['regulated_by']) ?></td>
                                    <td><?= htmlspecialchars($row['tp_no']) ?></td>
                                    <td class="text-center">
                                        <button class="btn btn-sm btn-outline-info btn-view" title="View"><i class="bi bi-eye"></i></button>
                                        <button class="btn btn-sm btn-outline-primary btn-edit" title="Edit"><i class="bi bi-pencil-square"></i></button>
                                        <a href="annual_livestock_societies.php?action=delete&id=<?= $row['id'] ?>" class="btn btn-sm btn-outline-danger btn-delete" title="Delete"><i class="bi bi-trash"></i></a>
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
<div class="modal fade" id="addSocModal" tabindex="-1" aria-labelledby="addSocModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <form method="POST">
            <input type="hidden" name="action" value="add">
            <div class="modal-content">
                <div class="modal-header" style="background-color: #370709; color: white;">
                    <h5 class="modal-title" id="addSocModalLabel"><i class="bi bi-plus-circle me-2"></i>Add Livestock Society</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold">VS Range</label>
                            <input type="text" name="vs_range" class="form-control" value="<?= htmlspecialchars($range_name) ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">G N Division</label>
                            <input type="text" name="gn_division" class="form-control" placeholder="e.g. GN Division name">
                        </div>
                        <div class="col-md-12">
                            <label class="form-label fw-bold">Name & Address</label>
                            <textarea name="name_address" class="form-control" rows="2" placeholder="Society Name and Registered Address" required></textarea>
                        </div>
                        <div class="col-md-12">
                            <label class="form-label fw-bold">Overall Objective</label>
                            <textarea name="overall_objective" class="form-control" rows="2" placeholder="e.g. Elevating dairy production standards"></textarea>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold">Total Members</label>
                            <input type="number" name="total_members" class="form-control" value="0" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold">Reg. No</label>
                            <input type="text" name="reg_no" class="form-control" placeholder="Registration number">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Reg. Department</label>
                            <input type="text" name="reg_department" class="form-control" placeholder="e.g. Dept of Cooperatives">
                        </div>
                        <div class="col-md-12">
                            <label class="form-label">Major Activities</label>
                            <textarea name="major_activities" class="form-control" rows="2" placeholder="Collection of Deposits, Granting Loans, member welfare, etc."></textarea>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold">Financial Records Availability</label>
                            <select name="financial_records_availability" class="form-select" required>
                                <option value="Yes">Yes</option>
                                <option value="No">No</option>
                                <option value="N/A" selected>N/A</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Regulated By</label>
                            <input type="text" name="regulated_by" class="form-control" placeholder="Regulating authority name">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">T.P. No</label>
                            <input type="text" name="tp_no" class="form-control" placeholder="Telephone Number">
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success">Save Society</button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Modal: Edit Record -->
<div class="modal fade" id="editSocModal" tabindex="-1" aria-labelledby="editSocModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <form method="POST">
            <input type="hidden" name="action" value="edit">
            <input type="hidden" name="id" id="edit_id">
            <div class="modal-content">
                <div class="modal-header" style="background-color: #370709; color: white;">
                    <h5 class="modal-title" id="editSocModalLabel"><i class="bi bi-pencil-square me-2"></i>Edit Livestock Society</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold">VS Range</label>
                            <input type="text" name="vs_range" id="edit_vs_range" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">G N Division</label>
                            <input type="text" name="gn_division" id="edit_gn_division" class="form-control">
                        </div>
                        <div class="col-md-12">
                            <label class="form-label fw-bold">Name & Address</label>
                            <textarea name="name_address" id="edit_name_address" class="form-control" rows="2" required></textarea>
                        </div>
                        <div class="col-md-12">
                            <label class="form-label fw-bold">Overall Objective</label>
                            <textarea name="overall_objective" id="edit_overall_objective" class="form-control" rows="2"></textarea>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold">Total Members</label>
                            <input type="number" name="total_members" id="edit_total_members" class="form-control" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold">Reg. No</label>
                            <input type="text" name="reg_no" id="edit_reg_no" class="form-control">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Reg. Department</label>
                            <input type="text" name="reg_department" id="edit_reg_department" class="form-control">
                        </div>
                        <div class="col-md-12">
                            <label class="form-label">Major Activities</label>
                            <textarea name="major_activities" id="edit_major_activities" class="form-control" rows="2"></textarea>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold">Financial Records Availability</label>
                            <select name="financial_records_availability" id="edit_financial_records_availability" class="form-select" required>
                                <option value="Yes">Yes</option>
                                <option value="No">No</option>
                                <option value="N/A">N/A</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Regulated By</label>
                            <input type="text" name="regulated_by" id="edit_regulated_by" class="form-control">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">T.P. No</label>
                            <input type="text" name="tp_no" id="edit_tp_no" class="form-control">
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update Society</button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Modal: View Record -->
<div class="modal fade" id="viewSocModal" tabindex="-1" aria-labelledby="viewSocModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header" style="background-color: #370709; color: white;">
                <h5 class="modal-title" id="viewSocModalLabel"><i class="bi bi-eye me-2"></i>View Livestock Society Details</h5>
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
                            <th>G N Division</th>
                            <td id="view_gn_division"></td>
                        </tr>
                        <tr>
                            <th>Name & Address</th>
                            <td id="view_name_address"></td>
                        </tr>
                        <tr>
                            <th>Overall Objective</th>
                            <td id="view_overall_objective"></td>
                        </tr>
                        <tr>
                            <th>Total Members</th>
                            <td id="view_total_members"></td>
                        </tr>
                        <tr>
                            <th>Reg. No</th>
                            <td id="view_reg_no"></td>
                        </tr>
                        <tr>
                            <th>Reg. Department</th>
                            <td id="view_reg_department"></td>
                        </tr>
                        <tr>
                            <th>Major Activities</th>
                            <td id="view_major_activities"></td>
                        </tr>
                        <tr>
                            <th>Availability of Financial Records</th>
                            <td id="view_financial_records_availability"></td>
                        </tr>
                        <tr>
                            <th>Regulated By</th>
                            <td id="view_regulated_by"></td>
                        </tr>
                        <tr>
                            <th>T.P. No</th>
                            <td id="view_tp_no"></td>
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
    $("#socTable").DataTable({
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
        $(\'#view_gn_division\').text($row.data(\'gn_division\') || \'N/A\');
        $(\'#view_name_address\').html(($row.data(\'name_address\') || \'\').replace(/\\n/g, \'<br>\'));
        $(\'#view_overall_objective\').html(($row.data(\'overall_objective\') || \'\').replace(/\\n/g, \'<br>\'));
        $(\'#view_total_members\').text($row.data(\'total_members\'));
        $(\'#view_reg_no\').text($row.data(\'reg_no\') || \'N/A\');
        $(\'#view_reg_department\').text($row.data(\'reg_department\') || \'N/A\');
        $(\'#view_major_activities\').html(($row.data(\'major_activities\') || \'\').replace(/\\n/g, \'<br>\'));
        $(\'#view_financial_records_availability\').text($row.data(\'financial_records_availability\'));
        $(\'#view_regulated_by\').text($row.data(\'regulated_by\') || \'N/A\');
        $(\'#view_tp_no\').text($row.data(\'tp_no\') || \'N/A\');

        new bootstrap.Modal(document.getElementById(\'viewSocModal\')).show();
    });

    $(document).on(\'click\', \'.btn-edit\', function() {
        var $row = $(this).closest(\'tr\');
        $(\'#edit_id\').val($row.data(\'id\'));
        $(\'#edit_vs_range\').val($row.data(\'vs_range\'));
        $(\'#edit_gn_division\').val($row.data(\'gn_division\'));
        $(\'#edit_name_address\').val($row.data(\'name_address\'));
        $(\'#edit_overall_objective\').val($row.data(\'overall_objective\'));
        $(\'#edit_total_members\').val($row.data(\'total_members\'));
        $(\'#edit_reg_no\').val($row.data(\'reg_no\'));
        $(\'#edit_reg_department\').val($row.data(\'reg_department\'));
        $(\'#edit_major_activities\').val($row.data(\'major_activities\'));
        $(\'#edit_financial_records_availability\').val($row.data(\'financial_records_availability\'));
        $(\'#edit_regulated_by\').val($row.data(\'regulated_by\'));
        $(\'#edit_tp_no\').val($row.data(\'tp_no\'));

        new bootstrap.Modal(document.getElementById(\'editSocModal\')).show();
    });

    $(document).on(\'click\', \'.btn-delete\', function(e) {
        e.preventDefault();
        var deleteUrl = $(this).attr(\'href\');
        var $row = $(this).closest(\'tr\');
        var id = $row.data(\'id\');

        Swal.fire({
            icon: \'warning\',
            title: \'Delete Society Record?\',
            html: \'Are you sure you want to permanently delete the society record <strong>#\' + id + \'</strong>?<br>This action cannot be undone.\',
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