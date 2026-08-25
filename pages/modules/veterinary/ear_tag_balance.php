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

// Fetch Ear Tag Usage records matching year filter and range
$records = [];
if (!empty($range_id)) {
    $records_sql = "
        SELECT id, report_year, report_month, opening_balance, 
               received_qty, used_qty, spoilt_qty, transferred_qty, closing_balance 
        FROM ear_tag_usage 
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
    'total_received' => 0,
    'total_used'     => 0,
    'total_spoilt'   => 0,
    'latest_balance' => 0
];

if (!empty($records)) {
    // Latest balance is the closing balance of the latest month recorded
    $summary['latest_balance'] = $records[0]['closing_balance'];
    foreach ($records as $r) {
        $summary['total_received'] += $r['received_qty'];
        $summary['total_used']     += $r['used_qty'];
        $summary['total_spoilt']   += $r['spoilt_qty'];
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
                <h2 class="h4 fw-bold mb-1" style="color: #370709;">Ear Tag Balance - Monthly Returns</h2>
                <p class="text-muted small mb-0">Manage monthly ear tag stock usage levels for <strong class="text-dark"><?= htmlspecialchars($range_name) ?></strong> (<?= htmlspecialchars($district_name) ?> District)</p>
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
                <a href="monthly-annual-reports.php" class="btn btn-secondary shadow-sm text-nowrap">
                    <i class="bi bi-arrow-left me-2"></i>Back
                </a>
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
                        <span class="text-muted small text-uppercase fw-bold">Total Received</span>
                        <h4 class="mb-0 fw-bold math-numeric text-success mt-1"><?= number_format($summary['total_received']) ?></h4>
                    </div>
                </div>
            </div>
            <div class="col-6 col-lg-3">
                <div class="card shadow-sm border-0 border-start border-info border-4">
                    <div class="card-body py-3">
                        <span class="text-muted small text-uppercase fw-bold">Total Used (Ear Tagged)</span>
                        <h4 class="mb-0 fw-bold math-numeric text-info mt-1"><?= number_format($summary['total_used']) ?></h4>
                    </div>
                </div>
            </div>
            <div class="col-6 col-lg-3">
                <div class="card shadow-sm border-0 border-start border-danger border-4">
                    <div class="card-body py-3">
                        <span class="text-muted small text-uppercase fw-bold">Closing Balance</span>
                        <h4 class="mb-0 fw-bold math-numeric text-danger mt-1"><?= number_format($summary['latest_balance']) ?></h4>
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
                                <button class="btn w-100 py-3 text-light border-0 shadow-sm d-flex flex-column align-items-center justify-content-center" style="background-color: #820100; min-height: 105px;" data-bs-toggle="modal" data-bs-target="#addEarTagModal">
                                    <i class="bi bi-plus-circle fs-3 mb-1"></i>
                                    <span class="small fw-bold text-uppercase">Log Ear Tag Returns</span>
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
                <h5 class="card-title mb-0 fw-bold text-dark"><i class="bi bi-table me-2"></i>Ear Tag Returns Log - <?= $selected_year ?></h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0" id="earTagTable" style="min-width: 900px;">
                        <thead class="table-light text-secondary small uppercase">
                            <tr>
                                <th class="text-center" style="width: 15%">Month</th>
                                <th class="text-end" style="width: 12%">Opening Balance</th>
                                <th class="text-end" style="width: 12%">Received Qty</th>
                                <th class="text-end" style="width: 12%">Used Qty</th>
                                <th class="text-end" style="width: 12%">Spoilt/Damaged</th>
                                <th class="text-end" style="width: 12%">Transferred</th>
                                <th class="text-end" style="width: 12%">Closing Balance</th>
                                <th class="text-center" style="width: 13%">Actions</th>
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
                                        data-month="<?= htmlspecialchars($row['report_month']) ?>"
                                        data-opening="<?= htmlspecialchars($row['opening_balance']) ?>"
                                        data-received="<?= htmlspecialchars($row['received_qty']) ?>"
                                        data-used="<?= htmlspecialchars($row['used_qty']) ?>"
                                        data-spoilt="<?= htmlspecialchars($row['spoilt_qty']) ?>"
                                        data-transferred="<?= htmlspecialchars($row['transferred_qty']) ?>"
                                        data-closing="<?= htmlspecialchars($row['closing_balance']) ?>">
                                        <td class="text-center fw-bold text-dark"><?= $month_names[$row['report_month']] ?></td>
                                        <td class="text-end font-monospace"><?= number_format($row['opening_balance']) ?></td>
                                        <td class="text-end text-success font-monospace">+<?= number_format($row['received_qty']) ?></td>
                                        <td class="text-end text-info font-monospace">-<?= number_format($row['used_qty']) ?></td>
                                        <td class="text-end text-warning font-monospace">-<?= number_format($row['spoilt_qty']) ?></td>
                                        <td class="text-end text-warning font-monospace">-<?= number_format($row['transferred_qty']) ?></td>
                                        <td class="text-end fw-bold text-dark font-monospace"><?= number_format($row['closing_balance']) ?></td>
                                        <td class="text-center">
                                            <button class="btn btn-sm btn-outline-primary btn-edit-ear" title="Edit"><i class="bi bi-pencil-square"></i></button>
                                            <a href="processors/ear_tag_crud.php?action=delete&id=<?= $row['id'] ?>" class="btn btn-sm btn-outline-danger btn-delete-ear" title="Delete"><i class="bi bi-trash"></i></a>
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
<?php include 'models/add_ear_tag_modal.php'; ?>
<?php include 'models/edit_ear_tag_modal.php'; ?>

<?php
$pageScripts = '
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
$(document).ready(function() {
    // Initialize DataTable
    if ($.fn.DataTable.isDataTable("#earTagTable")) {
        $("#earTagTable").DataTable().destroy();
    }
    $("#earTagTable").DataTable({
        "order": [[0, "desc"]],
        "pageLength": 12,
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

    // Modal Calculation Engine
    $(document).on(\'input\', \'.ear-calc-trigger\', function() {
        const modal = $(this).closest(\'.modal\');
        const isEdit = modal.attr(\'id\') === \'editEarTagModal\';
        const prefix = isEdit ? \'edit_\' : \'add_\';

        const opening     = parseInt($(\'#\' + prefix + \'opening_balance\').val()) || 0;
        const received    = parseInt($(\'#\' + prefix + \'received_qty\').val()) || 0;
        const used        = parseInt($(\'#\' + prefix + \'used_qty\').val()) || 0;
        const spoilt      = parseInt($(\'#\' + prefix + \'spoilt_qty\').val()) || 0;
        const transferred = parseInt($(\'#\' + prefix + \'transferred_qty\').val()) || 0;

        const balance = (opening + received) - (used + spoilt + transferred);
        const display = $(\'#\' + prefix + \'closing_balance_display\');
        display.text(balance.toLocaleString() + \' Units\');

        const submitBtn = isEdit ? $(\'#edit_submit_btn\') : $(\'#add_submit_btn\');

        if (balance < 0) {
            display.removeClass(\'text-dark text-success\').addClass(\'text-danger fw-bold\');
            submitBtn.prop(\'disabled\', true).text(\'Error: Inventory Deficit\');
        } else {
            display.removeClass(\'text-danger\').addClass(\'text-success fw-bold\');
            submitBtn.prop(\'disabled\', false).text(isEdit ? \'Save Changes\' : \'Save Record\');
        }
    });

    // Edit button click handler
    $(document).on(\'click\', \'.btn-edit-ear\', function() {
        var $row = $(this).closest(\'tr\');
        $(\'#edit_id\').val($row.data(\'id\'));
        $(\'#edit_report_year\').val($row.data(\'year\'));
        $(\'#edit_report_month\').val($row.data(\'month\'));
        $(\'#edit_opening_balance\').val($row.data(\'opening\'));
        $(\'#edit_received_qty\').val($row.data(\'received\'));
        $(\'#edit_used_qty\').val($row.data(\'used\'));
        $(\'#edit_spoilt_qty\').val($row.data(\'spoilt\'));
        $(\'#edit_transferred_qty\').val($row.data(\'transferred\'));

        // Trigger balance rendering
        $(\'.ear-calc-trigger\').first().trigger(\'input\');

        new bootstrap.Modal(document.getElementById(\'editEarTagModal\')).show();
    });

    // Delete Alert Confirmation Click Handler
    $(document).on(\'click\', \'.btn-delete-ear\', function(e) {
        e.preventDefault();
        var deleteUrl = $(this).attr(\'href\');
        var $row = $(this).closest(\'tr\');
        var monthName = $row.find(\'td\').first().text();

        Swal.fire({
            icon: \'warning\',
            title: \'Delete Ear Tag Records?\',
            html: \'Are you sure you want to permanently delete the ear tag return records for <strong>\' + monthName + \'</strong>?<br>This action cannot be undone.\',
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
