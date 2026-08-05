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

// Extract Range Name, District Name, and IDs using standard relational JOIN
if (!empty($range_id)) {
    $details_sql = "
        SELECT 
            vr.name AS range_name,
            d.name AS district_name,
            d.id AS district_id
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
            $district_id = $data['district_id'] ?? null;
        }
        $details_query->close();
    }
}

// Handle year filtering
$selected_year = isset($_GET['year']) ? intval($_GET['year']) : intval(date('Y'));

// Fetch Health Certificate issues matching year filter and range
$records = [];
if (!empty($range_id)) {
    $records_sql = "
        SELECT id, report_year, report_month, health_certificate_no, applicant_name_address, 
               farm_registration_no, date_of_issue, species, animal_details_male, 
               animal_details_female, vehicle_fitness_certificate_no, purpose 
        FROM health_certificate_issues 
        WHERE range_id = ? AND report_year = ? 
        ORDER BY report_month DESC, id DESC
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

// Compute summary stats for the selected year
$summary = [
    'total_issued' => count($records),
    'total_male'   => 0,
    'total_female' => 0
];

foreach ($records as $r) {
    $summary['total_male']   += $r['animal_details_male'];
    $summary['total_female'] += $r['animal_details_female'];
}

$month_names = [
    1 => 'January', 2 => 'February', 3 => 'March', 4 => 'April',
    5 => 'May', 6 => 'June', 7 => 'July', 8 => 'August',
    9 => 'September', 10 => 'October', 11 => 'November', 12 => 'December'
];

require_once '../../../includes/header.php';
?>

<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.2/css/buttons.bootstrap5.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
<link rel="stylesheet" href="../../../assets/css/veterinary.css">



        <div class="mb-4 d-flex justify-content-between align-items-center">
            <div>
                <h2 class="h4 fw-bold mb-1" style="color: #370709;">Health Certificate Issues Log</h2>
                <p class="text-muted small mb-0">Record and monitor animal transportation health certificates for <strong class="text-dark"><?= htmlspecialchars($range_name) ?></strong> (<?= htmlspecialchars($district_name) ?> District)</p>
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
                        <span class="text-muted small text-uppercase fw-bold">Total Issued</span>
                        <h4 class="mb-0 fw-bold math-numeric text-success mt-1"><?= number_format($summary['total_issued']) ?></h4>
                    </div>
                </div>
            </div>
            <div class="col-6 col-lg-3">
                <div class="card shadow-sm border-0 border-start border-info border-4">
                    <div class="card-body py-3">
                        <span class="text-muted small text-uppercase fw-bold">Male Animals</span>
                        <h4 class="mb-0 fw-bold math-numeric text-info mt-1"><?= number_format($summary['total_male']) ?></h4>
                    </div>
                </div>
            </div>
            <div class="col-6 col-lg-3">
                <div class="card shadow-sm border-0 border-start border-danger border-4">
                    <div class="card-body py-3">
                        <span class="text-muted small text-uppercase fw-bold">Female Animals</span>
                        <h4 class="mb-0 fw-bold math-numeric text-danger mt-1"><?= number_format($summary['total_female']) ?></h4>
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
                                <button class="btn w-100 py-3 text-light border-0 shadow-sm d-flex flex-column align-items-center justify-content-center" style="background-color: #820100; min-height: 105px;" data-bs-toggle="modal" data-bs-target="#addHealthCertModal">
                                    <i class="bi bi-plus-circle fs-3 mb-1"></i>
                                    <span class="small fw-bold text-uppercase">Issue Health Certificate</span>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- RECORDS LIST TABLE -->
        <div class="card shadow-sm border-0">
            <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center border-bottom">
                <h5 class="card-title mb-0 fw-bold text-dark"><i class="bi bi-table me-2"></i>Issued Health Certificates Log - <?= $selected_year ?></h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0" id="healthCertTable" style="min-width: 1200px;">
                        <thead class="table-light text-secondary small uppercase">
                            <tr>
                                <th class="text-center" style="width: 8%">Month</th>
                                <th class="text-center" style="width: 10%">Certificate No</th>
                                <th style="width: 18%">Applicant Name & Address</th>
                                <th class="text-center" style="width: 10%">Farm Reg No</th>
                                <th class="text-center" style="width: 8%">Issue Date</th>
                                <th class="text-center" style="width: 8%">Species</th>
                                <th class="text-end" style="width: 6%">Male Qty</th>
                                <th class="text-end" style="width: 6%">Female Qty</th>
                                <th class="text-center" style="width: 10%">Vehicle Cert</th>
                                <th style="width: 10%">Purpose</th>
                                <th class="text-center" style="width: 8%">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="small">
                            <?php if (empty($records)): ?>
                                <tr>
                                    <td colspan="11" class="text-center py-4 text-muted">
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
                                        data-cert_no="<?= htmlspecialchars($row['health_certificate_no']) ?>"
                                        data-applicant="<?= htmlspecialchars($row['applicant_name_address']) ?>"
                                        data-farm_reg="<?= htmlspecialchars($row['farm_registration_no']) ?>"
                                        data-issue_date="<?= htmlspecialchars($row['date_of_issue']) ?>"
                                        data-species="<?= htmlspecialchars($row['species']) ?>"
                                        data-male="<?= htmlspecialchars($row['animal_details_male']) ?>"
                                        data-female="<?= htmlspecialchars($row['animal_details_female']) ?>"
                                        data-vehicle="<?= htmlspecialchars($row['vehicle_fitness_certificate_no']) ?>"
                                        data-purpose="<?= htmlspecialchars($row['purpose']) ?>">
                                        <td class="text-center fw-bold text-dark"><?= $month_names[$row['report_month']] ?></td>
                                        <td class="text-center font-monospace fw-semibold"><?= htmlspecialchars($row['health_certificate_no']) ?></td>
                                        <td><?= nl2br(htmlspecialchars($row['applicant_name_address'])) ?></td>
                                        <td class="text-center font-monospace"><?= htmlspecialchars($row['farm_registration_no'] ?: '-') ?></td>
                                        <td class="text-center"><?= htmlspecialchars($row['date_of_issue']) ?></td>
                                        <td class="text-center bg-light fw-bold text-secondary"><?= htmlspecialchars($row['species'] ?: '-') ?></td>
                                        <td class="text-end text-primary font-monospace"><?= number_format($row['animal_details_male']) ?></td>
                                        <td class="text-end text-danger font-monospace"><?= number_format($row['animal_details_female']) ?></td>
                                        <td class="text-center font-monospace"><?= htmlspecialchars($row['vehicle_fitness_certificate_no'] ?: '-') ?></td>
                                        <td><?= htmlspecialchars($row['purpose'] ?: '-') ?></td>
                                        <td class="text-center">
                                            <button class="btn btn-sm btn-outline-primary btn-edit-health" title="Edit"><i class="bi bi-pencil-square"></i></button>
                                            <a href="processors/health_certificate_crud.php?action=delete&id=<?= $row['id'] ?>" class="btn btn-sm btn-outline-danger btn-delete-health" title="Delete"><i class="bi bi-trash"></i></a>
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
<?php include 'models/add_health_certificate_modal.php'; ?>
<?php include 'models/edit_health_certificate_modal.php'; ?>

<?php
$pageScripts = '
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
$(document).ready(function() {
    // Initialize DataTable
    if ($.fn.DataTable.isDataTable("#healthCertTable")) {
        $("#healthCertTable").DataTable().destroy();
    }
    $("#healthCertTable").DataTable({
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
    $(document).on(\'click\', \'.btn-edit-health\', function() {
        var $row = $(this).closest(\'tr\');
        $(\'#edit_id\').val($row.data(\'id\'));
        $(\'#edit_report_year\').val($row.data(\'year\'));
        $(\'#edit_report_month\').val($row.data(\'month\'));
        $(\'#edit_health_certificate_no\').val($row.data(\'cert_no\'));
        $(\'#edit_applicant_name_address\').val($row.data(\'applicant\'));
        $(\'#edit_farm_registration_no\').val($row.data(\'farm_reg\'));
        $(\'#edit_date_of_issue\').val($row.data(\'issue_date\'));
        $(\'#edit_species\').val($row.data(\'species\'));
        $(\'#edit_animal_details_male\').val($row.data(\'male\'));
        $(\'#edit_animal_details_female\').val($row.data(\'female\'));
        $(\'#edit_vehicle_fitness_certificate_no\').val($row.data(\'vehicle\'));
        $(\'#edit_purpose\').val($row.data(\'purpose\'));

        new bootstrap.Modal(document.getElementById(\'editHealthCertModal\')).show();
    });

    // Delete Alert Confirmation Click Handler
    $(document).on(\'click\', \'.btn-delete-health\', function(e) {
        e.preventDefault();
        var deleteUrl = $(this).attr(\'href\');
        var $row = $(this).closest(\'tr\');
        var certNo = $row.data(\'cert_no\');

        Swal.fire({
            icon: \'warning\',
            title: \'Delete Health Certificate Record?\',
            html: \'Are you sure you want to permanently delete the health certificate <strong>\' + certNo + \'</strong>?<br>This action cannot be undone.\',
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
