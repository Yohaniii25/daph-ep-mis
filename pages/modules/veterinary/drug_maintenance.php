<?php
session_start();
require_once '../../../config/db_connect.php';

if (!isset($_SESSION['logged_in']) || !in_array($_SESSION['role'], ['veterinary_surgeon', 'sms'])) {
    header("Location: ../../../index.php");
    exit();
}

// Extract base operational keys from the live user session wrapper
$user_id = $_SESSION['user_id'] ?? null;
$range_id = $_SESSION['range_id'] ?? null;

$range_name = 'Your Range';
$district_name = 'Your District';
$iframe_url = '';

// Step 1: Query the user's data profile if it's missing from the active session context
if (empty($range_id) && !empty($user_id)) {
    $user_query = $mysqli->prepare("SELECT range_id FROM users WHERE id = ?");
    if ($user_query) {
        $user_query->bind_param("i", $user_id);
        $user_query->execute();
        $user_result = $user_query->get_result();
        if ($row = $user_result->fetch_assoc()) {
            $_SESSION['range_id'] = $row['range_id'];
            $range_id = $row['range_id'];
        }
        $user_query->close();
    }
}

// Step 2: Extract Range Name, District Name, and Map URL using a clean, relational JOIN
if (!empty($range_id)) {
    $details_sql = "
        SELECT 
            vr.name AS range_name,
            d.name AS district_name,
            vrm.iframe_url
        FROM veterinary_ranges vr
        LEFT JOIN districts d ON vr.district_id = d.id
        LEFT JOIN veterinary_range_maps vrm ON vr.id = vrm.range_id
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
            $iframe_url = $data['iframe_url'] ?? '';
        }
        $details_query->close();
    }
}

// Compute summary metrics
$summary = [
    'total_batches' => 0,
    'total_starter' => 0,
    'total_used' => 0,
    'total_balance' => 0
];

// Active Tracked Batches
$batch_count = $mysqli->query("SELECT COUNT(*) AS total FROM vaccine_batches WHERE is_active = 1");
if ($batch_count) {
    $summary['total_batches'] = $batch_count->fetch_assoc()['total'] ?? 0;
}

// Stats sums
$stats_query = "
    SELECT 
        SUM(starter_count_month) AS total_starter,
        SUM(used_doses_count) AS total_used,
        SUM(starter_count_month + during_month_received - used_doses_count - doses_damaged) AS total_balance
    FROM drug_records
";
$stats_res = $mysqli->query($stats_query);
if ($stats_res && $row = $stats_res->fetch_assoc()) {
    $summary['total_starter'] = $row['total_starter'] ?? 0;
    $summary['total_used'] = $row['total_used'] ?? 0;
    $summary['total_balance'] = $row['total_balance'] ?? 0;
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
                <h2 class="h4 fw-bold mb-1" style="color: #370709;">Drug Maintenance</h2>
                <p class="text-muted small mb-0">Manage and track drug ledger balances for <strong class="text-dark"><?= htmlspecialchars($range_name) ?></strong> (<?= htmlspecialchars($district_name) ?> District)</p>
            </div>
            <a href="monthly-annual-reports.php" class="btn btn-secondary shadow-sm text-nowrap">
                <i class="bi bi-arrow-left me-2"></i>Back
            </a>
        </div>

        <div class="row g-4 mb-4">
            <div class="col-xl-3 col-md-6">
                <div class="card border-0 shadow-sm h-100 border-start border-primary border-4">
                    <div class="card-body p-4">
                        <h6 class="text-muted small text-uppercase fw-bold">Active Tracked Batches</h6>
                        <h2 class="text-primary mb-0 fw-bold"><?= number_format($summary['total_batches']) ?></h2>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="card border-0 shadow-sm h-100 border-start border-warning border-4">
                    <div class="card-body p-4">
                        <h6 class="text-muted small text-uppercase fw-bold">Doses Allocated (Starter)</h6>
                        <h2 class="text-warning mb-0 fw-bold"><?= number_format($summary['total_starter']) ?></h2>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="card border-0 shadow-sm h-100 border-start border-success border-4">
                    <div class="card-body p-4">
                        <h6 class="text-muted small text-uppercase fw-bold">Total Doses Used</h6>
                        <h2 class="text-success mb-0 fw-bold"><?= number_format($summary['total_used']) ?></h2>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="card border-0 shadow-sm h-100 border-start border-info border-4">
                    <div class="card-body p-4">
                        <h6 class="text-muted small text-uppercase fw-bold">Total Live Balance Available</h6>
                        <h2 class="text-info mb-0 fw-bold"><?= number_format($summary['total_balance']) ?></h2>
                    </div>
                </div>
            </div>
        </div>

        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white py-3 border-0">
                <h6 class="mb-0 fw-bold text-dark"><i class="bi bi-grid-3x3-gap-fill me-2"></i>Quick Actions</h6>
            </div>
            <div class="card-body pt-0">
                <div class="row g-3">
                    <div class="col-md-3">
                        <button class="btn w-100 py-3 text-light border-0 shadow-sm d-flex flex-column align-items-center justify-content-center" style="background-color: #820100; min-height: 105px;" data-bs-toggle="modal" data-bs-target="#addDrugRecordModal">
                            <i class="bi bi-plus-circle fs-3 mb-1"></i>
                            <span class="small fw-bold text-uppercase">Add New Drug Record</span>
                        </button>
                    </div>
                    <div class="col-md-3">
                        <a href="drug_types.php" class="btn w-100 py-3 text-light border-0 shadow-sm d-flex flex-column align-items-center justify-content-center" style="background-color: #370709; min-height: 105px;">
                            <i class="bi bi-search fs-3 mb-1"></i>
                            <span class="small fw-bold text-uppercase">Name of the Drugs</span>
                        </a>
                    </div>
                    <div class="col-md-3">
                        <a href="batches.php" class="btn w-100 py-3 text-light border-0 shadow-sm d-flex flex-column align-items-center justify-content-center" style="background-color: #b08723; min-height: 105px;">
                            <i class="bi bi-box-seam fs-3 mb-1"></i>
                            <span class="small fw-bold text-uppercase">Batches</span>
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <div class="card border-0 shadow-sm rounded-3">
            <div class="card-header bg-white py-3 border-0">
                <h5 class="m-0 fw-bold text-dark"><i class="bi bi-shield-plus me-2 text-success"></i>Drugs Balance Report</h5>
            </div>
            <div class="card-body">
                <table id="drugTable" class="table table-bordered table-striped align-middle row-border" style="width:100%">
                    <thead class="table-light text-center align-middle">
                        <tr>
                            <th>Log Date</th>
                            <th>Drug Name</th>
                            <th>Batch No</th>
                            <th>Date of Expiry</th>
                            <th>Opening Balance</th>
                            <th>Mid-Month Receipts</th>
                            <th>Quantity Used</th>
                            <th>Wasted / Damaged</th>
                            <th class="bg-light-success fw-bold">End Balance</th>
                            <th style="width: 100px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $ledger_query = "SELECT r.*, 
                                         COALESCE(t.vaccine_name, 'Unknown Type') AS vaccine_name, 
                                         COALESCE(b.batch_number, 'Unknown Batch') AS batch_number, 
                                         COALESCE(t.expiry_date, 'N/A') AS expiry_date,
                                         (r.starter_count_month + r.during_month_received - r.used_doses_count - r.doses_damaged) AS balance_end_month 
                                         FROM `drug_records` r
                                         LEFT JOIN `drug_types` t ON r.drug_type_id = t.id
                                         LEFT JOIN `vaccine_batches` b ON r.vaccine_batch_id = b.id
                                         ORDER BY r.id DESC";
                        $res = $mysqli->query($ledger_query);
                        if (!$res) {
                            die("Database Error: " . $mysqli->error);
                        }
                        if ($res->num_rows > 0):
                            while ($row = $res->fetch_assoc()):
                                $formatted_expiry = !empty($row['expiry_date']) ? date('Y-m-d', strtotime($row['expiry_date'])) : 'N/A';
                        ?>
                                <tr>
                                    <td class="text-center font-monospace small"><?= htmlspecialchars($row['log_date']) ?></td>
                                    <td class="fw-bold text-dark"><?= htmlspecialchars($row['vaccine_name']) ?></td>
                                    <td class="text-center"><span class="badge bg-dark font-monospace"><?= htmlspecialchars($row['batch_number']) ?></span></td>
                                    <td class="text-center small fw-semibold text-danger"><?= $formatted_expiry ?></td>
                                    <td class="text-center font-monospace"><?= number_format($row['starter_count_month']) ?></td>
                                    <td class="text-center font-monospace text-success">+<?= number_format($row['during_month_received']) ?></td>
                                    <td class="text-center font-monospace text-info">-<?= number_format($row['used_doses_count']) ?></td>
                                    <td class="text-center font-monospace text-danger">-<?= number_format($row['doses_damaged']) ?></td>
                                    <td class="text-center font-monospace fw-bold bg-light text-success"><?= number_format($row['balance_end_month']) ?></td>
                                    <td class="text-center">
                                        <div class="btn-group btn-group-sm">
                                            <button type="button" class="btn btn-outline-primary edit-drug-record-btn"
                                                data-id="<?= $row['id'] ?>"
                                                data-date="<?= $row['log_date'] ?>"
                                                data-drug="<?= htmlspecialchars($row['vaccine_name'], ENT_QUOTES) ?>"
                                                data-drug-type-id="<?= $row['drug_type_id'] ?>"
                                                data-batch="<?= $row['vaccine_batch_id'] ?>"
                                                data-expiry="<?= $formatted_expiry ?>"
                                                data-starter="<?= $row['starter_count_month'] ?>"
                                                data-received="<?= $row['during_month_received'] ?>"
                                                data-used="<?= $row['used_doses_count'] ?>"
                                                data-damaged="<?= $row['doses_damaged'] ?>">
                                                <i class="bi bi-pencil"></i>
                                            </button>
                                            <a href="processors/drug_record_crud.php?action=delete&id=<?= $row['id'] ?>" 
                                               class="btn btn-outline-danger btn-delete-drug">
                                                <i class="bi bi-trash"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                        <?php
                            endwhile;
                        endif;
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>
</div>

<?php include './models/drug_record_modal.php'; ?>

<?php
$pageScripts = '
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    $(document).ready(function() {
        // Initialize DataTable
        if ($.fn.DataTable.isDataTable("#drugTable")) {
            $("#drugTable").DataTable().destroy();
        }
        $("#drugTable").DataTable({
            "order": [
                [0, "desc"]
            ],
            "dom": \'<"d-flex justify-content-between align-items-center mb-3"Bf>rt<"d-flex justify-content-between align-items-center mt-3"ip>\',
            "language": {
                "search": "_INPUT_",
                "searchPlaceholder": "Search ledger rows..."
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
                    className: "btn btn-sm btn-danger me-2",
                    title: "Drug Stock Inventory Ledger Balances"
                },
                {
                    extend: "print",
                    text: "<i class=\\"bi bi-printer\\"></i> Print",
                    className: "btn btn-sm btn-dark"
                }
            ]
        });

        // SweetAlert2 Toast/Alert status checks
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
        } else if (status === \'error\' || status === \'db_error\') {
            Swal.fire({
                icon: \'error\',
                title: \'Operation Failed\',
                text: msg ? msg : \'Could not process database action.\',
                confirmButtonColor: \'#370709\'
            });
            window.history.replaceState({}, document.title, window.location.pathname);
        }

        // Event listener bridge script targeting your included modal configuration file
        $(document).on(\'click\', \'.edit-drug-record-btn\', function() {
            $(\'#drugAction\').val(\'update\');
            $(\'#drugId\').val($(this).data(\'id\'));
            
            // Populates your custom field structure definitions
            $(\'#logDate\').val($(this).data(\'date\'));
            var drugTypeId = $(this).data(\'drug-type-id\');
            $(\'#drugType\').val(drugTypeId);
            $(\'#vaccineBatchId\').val($(this).data(\'batch\'));
            $(\'#qtyStarter\').val($(this).data(\'starter\'));
            $(\'#qtyReceived\').val($(this).data(\'received\'));
            $(\'#qtyUsed\').val($(this).data(\'used\'));
            $(\'#qtyDamaged\').val($(this).data(\'damaged\'));
            
            // Sync dynamic display targets inside your modal
            $(\'#drugExpiryDisplay\').text($(this).data(\'expiry\'));
            
            // Trigger calculation execution
            $(\'.calc-trigger\').first().trigger(\'input\');
            
            // Modify labels and show modal safely
            $(\'#drugRecordModalTitle\').html(\'<i class="bi bi-pencil-square me-2 text-warning"></i>Modify Drug Stock Entry\');
            $(\'#immSubmitBtn\').text(\'Save Changes\');
            $(\'#addDrugRecordModal\').modal(\'show\');
        });

        // Reset elements upon close interaction
        $(\'#addDrugRecordModal\').on(\'hidden.bs.modal\', function() {
            $(\'#drugRecordForm\')[0].reset();
            $(\'#drugAction\').val(\'create\');
            $(\'#drugId\').val(\'\');
            $(\'#drugExpiryDisplay\').text(\'None selected\');
            $(\'#drugLiveBalanceDisplay\').text(\'0 Doses\').removeClass(\'text-danger text-success\').addClass(\'text-dark\');
            $(\'#drugRecordModalTitle\').html(\'<i class="bi bi-capsule-compartment me-2"></i>Drug Stock Ledger Entry\');
            $(\'#immSubmitBtn\').prop(\'disabled\', false).text(\'Commit Ledger Entry\');
        });

        // Delete Alert Confirmation Click Handler
        $(document).on(\'click\', \'.btn-delete-drug\', function(e) {
            e.preventDefault();
            var deleteUrl = $(this).attr(\'href\');
            Swal.fire({
                icon: \'warning\',
                title: \'Delete Stock Entry?\',
                html: \'Are you sure you want to permanently delete this stock record?<br>This action cannot be undone.\',
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

        // Expiry date viewer sync logic
        $(document).on(\'change\', \'#drugType\', function() {
            const selectedExpiry = $(this).find(\':selected\').data(\'expiry\');
            $(\'#drugExpiryDisplay\').text(selectedExpiry ? selectedExpiry : \'None selected\');
        });

        // Dynamic Balance Calculation Engine
        $(document).on(\'input\', \'.calc-trigger\', function() {
            const starter  = parseInt($(\'#qtyStarter\').val()) || 0;
            const received = parseInt($(\'#qtyReceived\').val()) || 0;
            const used     = parseInt($(\'#qtyUsed\').val()) || 0;
            const damaged  = parseInt($(\'#qtyDamaged\').val()) || 0;

            const balance = (starter + received) - (used + damaged);
            const display = $(\'#drugLiveBalanceDisplay\');
            display.text(balance.toLocaleString() + \' Units\');

            if (balance < 0) {
                display.removeClass(\'text-dark text-success\').addClass(\'text-danger fw-bold\');
                $(\'#immSubmitBtn\').prop(\'disabled\', true).text(\'Error: Inventory Deficit\');
            } else {
                display.removeClass(\'text-danger\').addClass(\'text-success fw-bold\');
                $(\'#immSubmitBtn\').prop(\'disabled\', false).text($(\'#drugAction\').val() === \'update\' ? \'Save Changes\' : \'Commit Ledger Entry\');
            }
        });
    });
</script>
';
require_once '../../../includes/footer.php';
?>