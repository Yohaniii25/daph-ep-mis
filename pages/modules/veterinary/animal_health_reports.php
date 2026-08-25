<?php
session_start();

if (!isset($_SESSION['logged_in']) || $_SESSION['role'] !== 'veterinary_surgeon') {
    header("Location: ../../../../index.php");
    exit();
}

if (!isset($_SESSION['full_name'])) {
    $_SESSION['full_name'] = $_SESSION['username'] ?? 'Veterinary Surgeon';
}

$full_name   = $_SESSION['full_name'];
$range_id    = $_SESSION['range_id'] ?? null;
$district_id = $_SESSION['district_id'] ?? null;

if (empty($range_id)) {
    die('<div class="alert alert-danger text-center p-5 m-5">Error: Your account is not assigned to any Veterinary Range.</div>');
}

require_once '../../../config/db_connect.php';

// --- FETCH NAMES ---
$district_name = 'Unknown District';
$range_name    = 'Unknown Range';

if ($district_id) {
    $stmt = $mysqli->prepare("SELECT name FROM districts WHERE id = ?");
    $stmt->bind_param("i", $district_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        $district_name = $row['name'];
    }
    $stmt->close();
}

if ($range_id) {
    $stmt = $mysqli->prepare("SELECT name FROM veterinary_ranges WHERE id = ?");
    $stmt->bind_param("i", $range_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        $range_name = $row['name'];
    }
    $stmt->close();
}

// --- DYNAMIC STATS ---
$total_stmt = $mysqli->prepare("SELECT COUNT(*) as total FROM animal_health_records WHERE range_id = ?");
$total_stmt->bind_param("i", $range_id);
$total_stmt->execute();
$total_count = $total_stmt->get_result()->fetch_assoc()['total'];

$month_stmt = $mysqli->prepare("SELECT COUNT(*) as total FROM animal_health_records WHERE range_id = ? AND MONTH(date) = MONTH(CURRENT_DATE()) AND YEAR(date) = YEAR(CURRENT_DATE())");
$month_stmt->bind_param("i", $range_id);
$month_stmt->execute();
$month_count = $month_stmt->get_result()->fetch_assoc()['total'];

$pending_stmt = $mysqli->prepare("SELECT COUNT(*) as total FROM animal_health_records WHERE range_id = ? AND report_status = 'Draft'");
$pending_stmt->bind_param("i", $range_id);
$pending_stmt->execute();
$pending_count = $pending_stmt->get_result()->fetch_assoc()['total'];

$disease_stmt = $mysqli->prepare("SELECT disease_name, COUNT(disease_name) as count FROM animal_health_records WHERE range_id = ? GROUP BY disease_name ORDER BY count DESC LIMIT 1");
$disease_stmt->bind_param("i", $range_id);
$disease_stmt->execute();
$disease_res = $disease_stmt->get_result()->fetch_assoc();
$common_disease = $disease_res['disease_name'] ?? 'None';

// --- FETCH ALL RECORDS ---
$stmt = $mysqli->prepare("SELECT * FROM animal_health_records WHERE range_id = ? ORDER BY date DESC");
$stmt->bind_param("i", $range_id);
$stmt->execute();
$treatments = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

require_once '../../../includes/header.php';
?>

<link rel="stylesheet" href="../../../assets/css/dataTables.bootstrap5.min.css">
<link rel="stylesheet" href="../../../assets/css/buttons.bootstrap5.min.css">

<?php  ?>



        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2 class="h4 mb-0 fw-bold">Animal Health Reports</h2>
                <small class="text-muted"><?= htmlspecialchars($range_name) ?> | DAPH Eastern Province</small>
            </div>
            <div class="d-flex gap-2">
                <button class="btn btn-success shadow-sm" data-bs-toggle="modal" data-bs-target="#recordTreatmentModal">
                    <i class="bi bi-plus-lg me-1"></i> New Health Record
                </button>
                <a href="monthly-annual-reports.php" class="btn btn-secondary shadow-sm text-nowrap">
                    <i class="bi bi-arrow-left me-2"></i>Back
                </a>
            </div>
        </div>

        <div class="row g-4 mb-4">
            <div class="col-xl-3 col-md-6">
                <div class="card border-0 shadow-sm h-100 text-center p-3 border-start border-primary border-4">
                    <h6 class="text-muted small fw-bold text-uppercase">Total Records</h6>
                    <h3 class="mb-0"><?= $total_count ?></h3>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="card border-0 shadow-sm h-100 text-center p-3 border-start border-warning border-4">
                    <h6 class="text-muted small fw-bold text-uppercase">This Month</h6>
                    <h3 class="mb-0"><?= $month_count ?></h3>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="card border-0 shadow-sm h-100 text-center p-3 border-start border-danger border-4">
                    <h6 class="text-muted small fw-bold text-uppercase">Pending Drafts</h6>
                    <h3 class="mb-0 text-danger"><?= $pending_count ?></h3>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="card border-0 shadow-sm h-100 text-center p-3 border-start border-info border-4">
                    <h6 class="text-muted small fw-bold text-uppercase">Most Common</h6>
                    <h5 class="mb-0 text-truncate text-info"><?= htmlspecialchars($common_disease) ?></h5>
                </div>
            </div>
        </div>

        <div class="card shadow-sm border-0 mb-4 bg-light">
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-3">
                        <label class="small fw-bold">From Date</label>
                        <input type="date" id="minDate" class="form-control form-control-sm">
                    </div>
                    <div class="col-md-3">
                        <label class="small fw-bold">To Date</label>
                        <input type="date" id="maxDate" class="form-control form-control-sm">
                    </div>
                    <div class="col-md-4">
                        <label class="small fw-bold">Live Search (Disease, Farmer, Vaccine...)</label>
                        <input type="text" id="customSearch" class="form-control form-control-sm" placeholder="Type to filter instantly...">
                    </div>
                    <div class="col-md-2 d-flex align-items-end">
                        <button id="resetFilter" class="btn btn-sm btn-secondary w-100">Reset All</button>
                    </div>
                </div>
            </div>
        </div>

        <div class="card shadow-sm border-0">
            <div class="card-body">
                <table id="healthTable" class="table table-hover align-middle w-100">
                    <thead class="table-light small text-uppercase">
                        <tr>
                            <th>Date</th>
                            <th>Farmer Reg</th>
                            <th>Disease / Vaccine</th>
                            <th class="text-center">Affected</th>
                            <th>Remarks</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($treatments as $t): ?>
                            <tr>
                                <td data-sort="<?= strtotime($t['date']) ?>"><?= date('d M Y', strtotime($t['date'])) ?></td>
                                <td><span class="badge bg-light text-dark border"><?= htmlspecialchars($t['farmer_reg_no']) ?></span></td>
                                <td>
                                    <strong><?= htmlspecialchars($t['disease_name']) ?></strong><br>
                                    <small class="text-muted"><?= htmlspecialchars($t['vaccine_name'] ?: 'None') ?></small>
                                </td>
                                <td class="text-center"><?= $t['occurrence_count'] ?></td>
                                <td><small class="text-muted"><?= htmlspecialchars(substr($t['treatment_details'], 0, 40)) ?>...</small></td>
                                <td><span class="badge bg-<?= $t['report_status'] === 'Draft' ? 'warning' : 'success' ?>"><?= $t['report_status'] ?></span></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <?php include 'models/add_health_record.php'; ?>
    </main>
</div>

<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/dataTables.buttons.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.bootstrap5.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/pdfmake.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/vfs_fonts.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.html5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.print.min.js"></script>

<script>
    $(document).ready(function() {
        // Initialize DataTable
        var table = $('#healthTable').DataTable({
            "order": [
                [0, "desc"]
            ],
            "dom": '<"d-flex justify-content-between align-items-center mb-3"B>rt<"d-flex justify-content-between align-items-center mt-3"ip>',
            "buttons": [{
                    extend: 'csv',
                    text: '<i class="bi bi-filetype-csv"></i> CSV',
                    className: 'btn btn-sm btn-success shadow-sm ',
                    titleAttr: 'Export Filtered CSV'
                },
                {
                    extend: 'pdf',
                    text: '<i class="bi bi-file-earmark-pdf"></i> PDF',
                    className: 'btn btn-sm btn-danger  shadow-sm', // White/Light style
                    title: 'Animal Health Records - <?= $range_name ?>'
                },
                {
                    extend: 'print',
                    text: '<i class="bi bi-printer"></i> Print',
                    className: 'btn btn-sm btn-warning shadow-sm' // White/Light style
                }
            ]
        });

        // Date Range Filter Logic
        $.fn.dataTable.ext.search.push(function(settings, data, dataIndex) {
            var min = $('#minDate').val();
            var max = $('#maxDate').val();
            var rowTimestamp = $(table.row(dataIndex).node()).find('td:first').data('sort');
            var rowDate = new Date(rowTimestamp * 1000);

            var filterMin = min ? new Date(min) : null;
            var filterMax = max ? new Date(max) : null;
            if (filterMin) filterMin.setHours(0, 0, 0, 0);
            if (filterMax) filterMax.setHours(23, 59, 59, 999);

            if ((!filterMin && !filterMax) || (!filterMin && rowDate <= filterMax) ||
                (filterMin <= rowDate && !filterMax) || (filterMin <= rowDate && rowDate <= filterMax)) {
                return true;
            }
            return false;
        });

        // Event Listeners
        $('#minDate, #maxDate').on('change', function() {
            table.draw();
        });
        $('#customSearch').on('keyup', function() {
            table.search(this.value).draw();
        });
        $('#resetFilter').on('click', function() {
            $('#minDate, #maxDate, #customSearch').val('');
            table.search('').draw();
        });
    });
</script>

<?php require_once '../../../includes/footer.php'; ?>