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

// Extract Range Name and District Name
if (!empty($range_id)) {
    $details_sql = "
        SELECT 
            vr.name AS range_name,
            d.name AS district_name
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
            $range_name = $data['range_name'] ?? 'Your Assigned Range';
            $district_name = $data['district_name'] ?? 'Your District';
        }
        $details_query->close();
    }
}

// Handle year filtering
$selected_year = isset($_GET['year']) ? intval($_GET['year']) : intval(date('Y'));

// Fetch PD performance records matching year filter and range
$records = [];
if (!empty($range_id)) {
    $records_sql = "
        SELECT id, report_year, report_month, vs_tech_code, ai_date, cow_id, pd_date, result 
        FROM breeding_pd_performance 
        WHERE range_id = ? AND report_year = ? 
        ORDER BY report_month DESC, pd_date DESC, id DESC
    ";
    $stmt = $mysqli->prepare($records_sql);
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

// Compute statistics
$summary = [
    'total_completed' => count($records),
    'total_pregnant'  => 0,
    'total_non_preg'  => 0
];

foreach ($records as $r) {
    if ($r['result'] === 'P') {
        $summary['total_pregnant']++;
    } else {
        $summary['total_non_preg']++;
    }
}

$month_names = [
    1 => 'January', 2 => 'February', 3 => 'March', 4 => 'April',
    5 => 'May', 6 => 'June', 7 => 'July', 8 => 'August',
    9 => 'September', 10 => 'October', 11 => 'November', 12 => 'December'
];

require_once '../../../includes/header.php';
?>

<link rel="stylesheet" href="../../../assets/css/bootstrap-icons.min.css">
<link rel="stylesheet" href="../../../assets/css/dataTables.bootstrap5.min.css">
<link rel="stylesheet" href="../../../assets/css/buttons.bootstrap5.min.css">
<link rel="stylesheet" href="../../../assets/css/sweetalert2.min.css">
<link rel="stylesheet" href="../../../assets/css/veterinary.css">



        <div class="mb-4 d-flex justify-content-between align-items-center">
            <div>
                <div class="d-flex align-items-center gap-2 mb-1">
                    <a href="animal_breeding.php?year=<?= $selected_year ?>" class="btn btn-sm btn-outline-secondary py-0"><i class="bi bi-arrow-left"></i> Back</a>
                    <h2 class="h4 fw-bold mb-0" style="color: #370709;">Pregnancy Diagnosis (PD) Performance Log</h2>
                </div>
                <p class="text-muted small mb-0">Record and track pregnancy diagnosis outcomes for <strong class="text-dark"><?= htmlspecialchars($range_name) ?></strong> (<?= htmlspecialchars($district_name) ?> District)</p>
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
                <div class="card shadow-sm border-0 border-start border-primary border-4">
                    <div class="card-body py-3">
                        <span class="text-muted small text-uppercase fw-bold">Active Year</span>
                        <h4 class="mb-0 fw-bold math-numeric text-primary mt-1"><?= $selected_year ?></h4>
                    </div>
                </div>
            </div>
            <div class="col-6 col-lg-3">
                <div class="card shadow-sm border-0 border-start border-success border-4">
                    <div class="card-body py-3">
                        <span class="text-muted small text-uppercase fw-bold">Total PD Completed</span>
                        <h4 class="mb-0 fw-bold math-numeric text-success mt-1"><?= number_format($summary['total_completed']) ?></h4>
                    </div>
                </div>
            </div>
            <div class="col-6 col-lg-3">
                <div class="card shadow-sm border-0 border-start border-info border-4">
                    <div class="card-body py-3">
                        <span class="text-muted small text-uppercase fw-bold">Pregnant Result</span>
                        <h4 class="mb-0 fw-bold math-numeric text-info mt-1"><?= number_format($summary['total_pregnant']) ?></h4>
                    </div>
                </div>
            </div>
            <div class="col-6 col-lg-3">
                <div class="card shadow-sm border-0 border-start border-danger border-4">
                    <div class="card-body py-3">
                        <span class="text-muted small text-uppercase fw-bold">Not Pregnant Result</span>
                        <h4 class="mb-0 fw-bold math-numeric text-danger mt-1"><?= number_format($summary['total_non_preg']) ?></h4>
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
                                <button class="btn w-100 py-3 text-light border-0 shadow-sm d-flex flex-column align-items-center justify-content-center" style="min-height: 105px; background-color: #b08723;" data-bs-toggle="modal" data-bs-target="#addPDPerfModal">
                                    <i class="bi bi-plus-circle fs-3 mb-1"></i>
                                    <span class="small fw-bold text-uppercase">Add PD Record</span>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- RECORDS LIST TABLE -->
        <div class="card shadow-sm border-0">
            <div class="card-header bg-white py-3 border-bottom">
                <h5 class="card-title mb-0 fw-bold text-dark"><i class="bi bi-table me-2"></i>PD Performance Registry - <?= $selected_year ?></h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0" id="pdPerfTable" style="min-width: 1000px;">
                        <thead class="table-light text-secondary small uppercase">
                            <tr>
                                <th class="text-center" style="width: 10%">Month</th>
                                <th class="text-center" style="width: 15%">VS / Tech Code</th>
                                <th class="text-center" style="width: 15%">Cow ID</th>
                                <th class="text-center" style="width: 15%">AI Date</th>
                                <th class="text-center" style="width: 15%">PD Date</th>
                                <th class="text-center" style="width: 18%">Pregnancy Result</th>
                                <th class="text-center" style="width: 12%">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="small">
                            <?php if (empty($records)): ?>
                                <tr>
                                    <td colspan="7" class="text-center py-4 text-muted">
                                        <i class="bi bi-inbox fs-3 d-block mb-2"></i>
                                        No records located for the selected year <?= $selected_year ?>.
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($records as $row): ?>
                                    <tr 
                                        data-id="<?= $row['id'] ?>"
                                        data-year="<?= htmlspecialchars($row['report_year']) ?>"
                                        data-month="<?= htmlspecialchars($row['report_month']) ?>"
                                        data-vs_tech_code="<?= htmlspecialchars($row['vs_tech_code']) ?>"
                                        data-cow_id="<?= htmlspecialchars($row['cow_id']) ?>"
                                        data-ai_date="<?= htmlspecialchars($row['ai_date'] ?: '') ?>"
                                        data-pd_date="<?= htmlspecialchars($row['pd_date']) ?>"
                                        data-result="<?= htmlspecialchars($row['result']) ?>">
                                        <td class="text-center fw-bold text-dark"><?= $month_names[$row['report_month']] ?></td>
                                        <td class="text-center font-monospace fw-semibold"><?= htmlspecialchars($row['vs_tech_code']) ?></td>
                                        <td class="text-center font-monospace text-primary"><?= htmlspecialchars($row['cow_id']) ?></td>
                                        <td class="text-center"><?= htmlspecialchars($row['ai_date'] ?: '-') ?></td>
                                        <td class="text-center"><?= htmlspecialchars($row['pd_date']) ?></td>
                                        <td class="text-center">
                                            <?php if ($row['result'] === 'P'): ?>
                                                <span class="badge bg-success py-1 px-3">P (Pregnant)</span>
                                            <?php else: ?>
                                                <span class="badge bg-danger py-1 px-3">NP (Not Pregnant)</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-center">
                                            <button class="btn btn-sm btn-outline-primary btn-edit-pd" title="Edit"><i class="bi bi-pencil-square"></i></button>
                                            <a href="processors/pd_performance_crud.php?action=delete&id=<?= $row['id'] ?>" class="btn btn-sm btn-outline-danger btn-delete-pd" title="Delete"><i class="bi bi-trash"></i></a>
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

<!-- Modals -->
<?php include 'models/add_pd_performance_modal.php'; ?>
<?php include 'models/edit_pd_performance_modal.php'; ?>

<?php
$pageScripts = '
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
$(document).ready(function() {
    // Initialize DataTable
    if ($.fn.DataTable.isDataTable("#pdPerfTable")) {
        $("#pdPerfTable").DataTable().destroy();
    }
    $("#pdPerfTable").DataTable({
        "order": [[0, "desc"]],
        "pageLength": 10,
        "dom": \'<"d-flex justify-content-between align-items-center mb-3"Bf>rt<"d-flex justify-content-between align-items-center mt-3"ip>\',
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

    // Alert query checks
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

    // Edit button click handler
    $(document).on(\'click\', \'.btn-edit-pd\', function() {
        var $row = $(this).closest(\'tr\');
        $(\'#edit_id\').val($row.data(\'id\'));
        $(\'#edit_report_year\').val($row.data(\'year\'));
        $(\'#edit_report_month\').val($row.data(\'month\'));
        $(\'#edit_vs_tech_code\').val($row.data(\'vs_tech_code\'));
        $(\'#edit_cow_id\').val($row.data(\'cow_id\'));
        $(\'#edit_ai_date\').val($row.data(\'ai_date\'));
        $(\'#edit_pd_date\').val($row.data(\'pd_date\'));
        $(\'#edit_result\').val($row.data(\'result\'));

        new bootstrap.Modal(document.getElementById(\'editPDPerfModal\')).show();
    });

    // Delete Alert Confirmation Click Handler
    $(document).on(\'click\', \'.btn-delete-pd\', function(e) {
        e.preventDefault();
        var deleteUrl = $(this).attr(\'href\');
        var $row = $(this).closest(\'tr\');
        var cowId = $row.data(\'cow_id\');

        Swal.fire({
            icon: \'warning\',
            title: \'Delete PD Record?\',
            html: \'Are you sure you want to permanently delete the PD test record for Cow ID <strong>\' + cowId + \'</strong>?<br>This action cannot be undone.\',
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
