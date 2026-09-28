<?php
session_start();
require_once __DIR__ . '/../../../config/db_connect.php';

/** @var mysqli $mysqli */
global $mysqli;

// 1. Session and Role Guard
if (!isset($_SESSION['logged_in']) || $_SESSION['role'] !== 'veterinary_surgeon') {
    header("Location: ../../../../index.php");
    exit();
}

$user_id     = $_SESSION['user_id'] ?? null;
$range_id    = $_SESSION['range_id'] ?? null;
$district_id = $_SESSION['district_id'] ?? null;

if (empty($range_id)) {
    die('<div class="alert alert-danger text-center p-5 m-5">Error: Your account is not assigned to any Veterinary Range.</div>');
}

// 2. Fetch Officer Details & Designation
$officer_name        = $_SESSION['full_name'] ?? 'Veterinary Surgeon';
$officer_designation = 'Government Veterinary Surgeon';
$ministry_department = 'Ministry of Agriculture, Animal Production & Development / Department of Animal Production & Health (Eastern Province)';

if ($user_id) {
    $stmt = $mysqli->prepare("SELECT full_name, designation, role FROM users WHERE id = ?");
    if ($stmt) {
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $u_res = $stmt->get_result();
        if ($u = $u_res->fetch_assoc()) {
            if (!empty($u['full_name'])) {
                $officer_name = $u['full_name'];
            }
            if (!empty($u['designation'])) {
                $officer_designation = $u['designation'];
            } elseif (!empty($u['role'])) {
                $officer_designation = ucwords(str_replace('_', ' ', $u['role']));
            }
        }
        $stmt->close();
    }
}

// 3. Structural Range & District Information
$district_name = 'Unknown District';
$range_name    = 'Unknown Range';

if ($district_id) {
    $stmt = $mysqli->prepare("SELECT name FROM districts WHERE id = ?");
    if ($stmt) {
        $stmt->bind_param("i", $district_id);
        $stmt->execute();
        $res = $stmt->get_result();
        if ($row = $res->fetch_assoc()) {
            $district_name = $row['name'];
        }
        $stmt->close();
    }
}

if ($range_id) {
    $stmt = $mysqli->prepare("SELECT name FROM veterinary_ranges WHERE id = ?");
    if ($stmt) {
        $stmt->bind_param("i", $range_id);
        $stmt->execute();
        $res = $stmt->get_result();
        if ($row = $res->fetch_assoc()) {
            $range_name = $row['name'];
        }
        $stmt->close();
    }
}

// 4. Report Period (Year and Month)
$selected_year  = isset($_GET['year']) ? intval($_GET['year']) : intval(date('Y'));
$selected_month = isset($_GET['month']) ? intval($_GET['month']) : intval(date('n'));

// 5. Fetch Daily Diary tasks from DB
$programmes = [];
if ($user_id) {
    if ($selected_month > 0 && $selected_month <= 12) {
        $stmt = $mysqli->prepare("SELECT id, task_date AS date, activity AS task, place, distance, time_duration FROM diary_tasks WHERE user_id = ? AND YEAR(task_date) = ? AND MONTH(task_date) = ? ORDER BY task_date ASC, id ASC");
        if ($stmt) {
            $stmt->bind_param("iii", $user_id, $selected_year, $selected_month);
            $stmt->execute();
            $result = $stmt->get_result();
            while ($row = $result->fetch_assoc()) {
                $programmes[] = $row;
            }
            $stmt->close();
        }
    } else {
        $stmt = $mysqli->prepare("SELECT id, task_date AS date, activity AS task, place, distance, time_duration FROM diary_tasks WHERE user_id = ? AND YEAR(task_date) = ? ORDER BY task_date ASC, id ASC");
        if ($stmt) {
            $stmt->bind_param("ii", $user_id, $selected_year);
            $stmt->execute();
            $result = $stmt->get_result();
            while ($row = $result->fetch_assoc()) {
                $programmes[] = $row;
            }
            $stmt->close();
        }
    }
}

$report_period_label = ($selected_month > 0 ? date('F', mktime(0, 0, 0, $selected_month, 1)) . ' ' : 'All Months - ') . $selected_year;

require_once '../../../includes/header.php';
?>

<link rel="stylesheet" href="../../../assets/css/dataTables.bootstrap5.min.css">
<link rel="stylesheet" href="../../../assets/css/buttons.bootstrap5.min.css">

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h3 class="fw-bold mb-1">Daily Diary (Monthly Work Done)</h3>
        <p class="text-muted small mb-0">Monthly extraction of executed duties and official travel tracking with standard administrative header.</p>
    </div>
    <a href="monthly-annual-reports.php" class="btn btn-secondary shadow-sm text-nowrap">
        <i class="bi bi-arrow-left me-2"></i>Back to Reports
    </a>
</div>

<!-- ============================================================ -->
<!-- STANDARD MONTHLY DAILY DIARY HEADER DETAILS -->
<!-- ============================================================ -->
<div class="card shadow-sm border-0 mb-4" style="border-left: 5px solid #820100 !important;">
    <div class="card-body p-4">
        <div class="d-flex flex-wrap justify-content-between align-items-center mb-3 pb-3 border-bottom">
            <div>
                <span class="badge bg-danger-subtle text-danger px-2 py-1 mb-1 fw-semibold text-uppercase" style="font-size: 0.75rem; letter-spacing: 0.5px;">
                    <i class="bi bi-journal-check me-1"></i>Official Diary Record
                </span>
                <h5 class="fw-bold text-dark mb-0">Monthly Work Done / Daily Diary Extraction</h5>
            </div>
            <!-- Month & Year Selector -->
            <form method="GET" class="d-flex align-items-center gap-2 mt-2 mt-md-0">
                <label class="form-label small text-muted mb-0 fw-bold text-nowrap"><i class="bi bi-funnel-fill me-1"></i>Period:</label>
                <select name="month" class="form-select form-select-sm" style="min-width: 135px;" onchange="this.form.submit()">
                    <option value="0" <?= $selected_month === 0 ? 'selected' : '' ?>>All Months</option>
                    <?php for ($m = 1; $m <= 12; $m++): ?>
                        <option value="<?= $m ?>" <?= $selected_month === $m ? 'selected' : '' ?>>
                            <?= date('F', mktime(0, 0, 0, $m, 1)) ?>
                        </option>
                    <?php endfor; ?>
                </select>
                <select name="year" class="form-select form-select-sm" style="min-width: 95px;" onchange="this.form.submit()">
                    <?php for ($y = 2024; $y <= 2030; $y++): ?>
                        <option value="<?= $y ?>" <?= $selected_year === $y ? 'selected' : '' ?>><?= $y ?></option>
                    <?php endfor; ?>
                </select>
            </form>
        </div>

        <div class="row g-3">
            <div class="col-sm-6 col-lg-3">
                <div class="p-3 rounded bg-light border h-100">
                    <div class="text-muted small text-uppercase fw-semibold" style="font-size: 0.72rem;">Name of the Officer</div>
                    <div class="fw-bold text-dark mt-1 text-truncate" title="<?= htmlspecialchars($officer_name) ?>">
                        <i class="bi bi-person-badge-fill text-danger me-2"></i><?= htmlspecialchars($officer_name) ?>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-lg-3">
                <div class="p-3 rounded bg-light border h-100">
                    <div class="text-muted small text-uppercase fw-semibold" style="font-size: 0.72rem;">Designation</div>
                    <div class="fw-bold text-dark mt-1 text-truncate" title="<?= htmlspecialchars($officer_designation) ?>">
                        <i class="bi bi-briefcase-fill text-danger me-2"></i><?= htmlspecialchars($officer_designation) ?>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-lg-3">
                <div class="p-3 rounded bg-light border h-100">
                    <div class="text-muted small text-uppercase fw-semibold" style="font-size: 0.72rem;">Ministry / Department</div>
                    <div class="fw-bold text-dark mt-1" style="font-size: 0.88rem; line-height: 1.25;" title="<?= htmlspecialchars($ministry_department) ?>">
                        <i class="bi bi-building-fill text-danger me-2"></i><?= htmlspecialchars($ministry_department) ?>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-lg-3">
                <div class="p-3 rounded bg-light border h-100">
                    <div class="text-muted small text-uppercase fw-semibold" style="font-size: 0.72rem;">Year and Month of the Report</div>
                    <div class="fw-bold text-dark mt-1 text-truncate">
                        <i class="bi bi-calendar-event-fill text-danger me-2"></i><?= htmlspecialchars($report_period_label) ?>
                    </div>
                    <div class="text-muted small mt-1" style="font-size: 0.72rem;">
                        <i class="bi bi-geo-alt me-1"></i><?= htmlspecialchars($range_name) ?> (<?= htmlspecialchars($district_name) ?>)
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ============================================================ -->
<!-- QUICK ACTIONS -->
<!-- ============================================================ -->
<div class="card shadow-sm mb-4 border-0">
    <div class="card-header bg-white py-3 border-0">
        <h6 class="mb-0 fw-bold text-dark"><i class="bi bi-grid-3x3-gap-fill me-2"></i>Quick Actions</h6>
    </div>
    <div class="card-body pt-0">
        <div class="row row-cols-2 row-cols-md-3 row-cols-lg-5 g-3">
            <div class="col">
                <button type="button" class="btn w-100 h-100 py-3 text-light border-0 shadow-sm d-flex flex-column align-items-center justify-content-center" style="background-color: #820100; min-height: 105px;" data-bs-toggle="modal" data-bs-target="#addDailyDiaryModal">
                    <i class="bi bi-journal-plus fs-3 mb-1"></i>
                    <span class="text-center fw-semibold">Add Diary Entry</span>
                </button>
            </div>
        </div>
    </div>
</div>

<!-- ============================================================ -->
<!-- DAILY DIARY EXTRACTION TABLE -->
<!-- ============================================================ -->
<div class="card shadow-sm border-0">
    <div class="card-body p-3">
        <table class="table table-hover align-middle w-100" id="dailyDiaryTable">
            <thead class="bg-light">
                <tr>
                    <th style="width: 12%;">Date</th>
                    <th style="width: 32%;">Work Description</th>
                    <th style="width: 25%;">Work Place / Visiting Place</th>
                    <th style="width: 11%;">Distance</th>
                    <th style="width: 12%;">Duty Time</th>
                    <th style="width: 8%;" class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($programmes)): ?>
                    <?php foreach ($programmes as $prog): ?>
                        <tr
                            data-id="<?= $prog['id'] ?>"
                            data-date="<?= htmlspecialchars($prog['date']) ?>"
                            data-task="<?= htmlspecialchars($prog['task']) ?>"
                            data-place="<?= htmlspecialchars($prog['place']) ?>"
                            data-distance="<?= htmlspecialchars($prog['distance'] ?? '') ?>"
                            data-duty-time="<?= htmlspecialchars($prog['time_duration']) ?>">
                            <td class="fw-semibold text-dark">
                                <span class="d-none"><?= htmlspecialchars($prog['date']) ?></span>
                                <i class="bi bi-calendar2-check text-danger me-1"></i><?= date('d D', strtotime($prog['date'])) ?>
                            </td>
                            <td><?= htmlspecialchars($prog['task']) ?></td>
                            <td><?= htmlspecialchars($prog['place']) ?></td>
                            <td>
                                <span class="badge bg-secondary-subtle text-secondary border px-2 py-1">
                                    <i class="bi bi-signpost-2 me-1"></i><?= htmlspecialchars($prog['distance'] ?: '-') ?>
                                </span>
                            </td>
                            <td>
                                <span class="badge bg-light text-dark border px-2 py-1">
                                    <i class="bi bi-clock me-1 text-muted"></i><?= htmlspecialchars($prog['time_duration']) ?>
                                </span>
                            </td>
                            <td class="text-end">
                                <button class="btn btn-sm btn-outline-primary btn-edit-prog" title="Edit Diary Entry"><i class="bi bi-pencil-square"></i></button>
                                <button class="btn btn-sm btn-outline-danger btn-delete-prog" title="Delete Diary Entry"><i class="bi bi-trash"></i></button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

</main>
</div>

<!-- ============================================================ -->
<!-- INCLUDED MODALS -->
<!-- ============================================================ -->
<?php include 'model/add_daily_diary.php'; ?>
<?php include 'model/edit_daily_diary.php'; ?>

<?php require_once '../../../includes/footer.php'; ?>

<!-- DataTables & Export Scripts -->
<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.1/js/dataTables.buttons.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.bootstrap5.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/pdfmake.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/vfs_fonts.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.html5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.print.min.js"></script>
<!-- SweetAlert2 -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
    $(document).ready(function() {
        var officerName         = <?= json_encode($officer_name) ?>;
        var officerDesignation  = <?= json_encode($officer_designation) ?>;
        var ministryDepartment  = <?= json_encode($ministry_department) ?>;
        var reportPeriod        = <?= json_encode($report_period_label) ?>;
        var rangeName           = <?= json_encode($range_name) ?>;
        var districtName        = <?= json_encode($district_name) ?>;

        var table = $('#dailyDiaryTable').DataTable({
            "order": [
                [0, "asc"]
            ],
            "responsive": true,
            "pageLength": 31,
            "dom": '<"d-flex justify-content-between align-items-center mb-3"Bf>rt<"d-flex justify-content-between align-items-center mt-3"ip>',
            "buttons": [{
                    extend: 'csv',
                    className: 'btn btn-sm btn-success me-2',
                    text: '<i class="bi bi-file-earmark-spreadsheet"></i> CSV',
                    exportOptions: {
                        columns: [0, 1, 2, 3, 4]
                    }
                },
                {
                    extend: 'pdf',
                    className: 'btn btn-sm btn-danger me-2',
                    text: '<i class="bi bi-file-earmark-pdf"></i> PDF',
                    exportOptions: {
                        columns: [0, 1, 2, 3, 4]
                    },
                    customize: function(doc) {
                        // Official Report Header in PDF
                        doc.content.splice(0, 0, {
                            margin: [0, 0, 0, 15],
                            table: {
                                widths: ['*'],
                                body: [
                                    [{
                                        text: [
                                            { text: ministryDepartment + '\n', bold: true, fontSize: 11, alignment: 'center' },
                                            { text: 'MONTHLY WORK DONE / DAILY DIARY EXTRACTION\n\n', bold: true, fontSize: 13, alignment: 'center' },
                                            { text: 'Name of the Officer: ', bold: true, fontSize: 10 },
                                            { text: officerName + '          ', fontSize: 10 },
                                            { text: 'Year & Month of Report: ', bold: true, fontSize: 10 },
                                            { text: reportPeriod + '\n', fontSize: 10 },
                                            { text: 'Designation: ', bold: true, fontSize: 10 },
                                            { text: officerDesignation + '          ', fontSize: 10 },
                                            { text: 'Range / Station: ', bold: true, fontSize: 10 },
                                            { text: rangeName + ' (' + districtName + ')\n', fontSize: 10 }
                                        ],
                                        fillColor: '#f8f9fa',
                                        margin: [10, 8, 10, 8]
                                    }]
                                ]
                            },
                            layout: 'noBorders'
                        });

                        doc.content.push({
                            margin: [0, 35, 0, 25],
                            columns: [{
                                    text: '_______________________\nSignature of Officer',
                                    alignment: 'center'
                                },
                                {
                                    text: '_______________________\nOfficial Stamp & Date',
                                    alignment: 'center'
                                }
                            ]
                        });
                        doc.content.push({
                            text: 'For Approving Office Use Only',
                            style: 'header',
                            alignment: 'center',
                            margin: [0, 0, 0, 10],
                            bold: true
                        });
                        doc.content.push({
                            table: {
                                widths: ['*', '*', '*'],
                                body: [
                                    [{
                                            text: 'Checked by\n\n\n____________________',
                                            alignment: 'center',
                                            margin: [0, 12, 0, 12]
                                        },
                                        {
                                            text: 'Subject Officer\n\n\n____________________',
                                            alignment: 'center',
                                            margin: [0, 12, 0, 12]
                                        },
                                        {
                                            text: '',
                                            margin: [0, 12, 0, 12]
                                        }
                                    ],
                                    [{
                                            text: 'Recommended by\n\n\n____________________',
                                            alignment: 'center',
                                            margin: [0, 12, 0, 12]
                                        },
                                        {
                                            text: 'Approved by (PD / DD)\n\n\n____________________',
                                            alignment: 'center',
                                            margin: [0, 12, 0, 12]
                                        },
                                        {
                                            text: '',
                                            margin: [0, 12, 0, 12]
                                        }
                                    ]
                                ]
                            }
                        });
                    }
                },
                {
                    extend: 'print',
                    className: 'btn btn-sm btn-dark',
                    text: '<i class="bi bi-printer"></i> Print',
                    exportOptions: {
                        columns: [0, 1, 2, 3, 4]
                    },
                    customize: function(win) {
                        var headerHtml = `
                        <div style="border-bottom: 2px solid #222; padding-bottom: 12px; margin-bottom: 20px;">
                            <div style="text-align: center; font-size: 13px; font-weight: bold; text-transform: uppercase;">${ministryDepartment}</div>
                            <h3 style="text-align: center; font-weight: bold; margin: 6px 0 14px 0; letter-spacing: 0.5px;">MONTHLY WORK DONE / DAILY DIARY EXTRACTION</h3>
                            <table style="width: 100%; border: none; font-size: 13px; line-height: 1.8;">
                                <tr>
                                    <td style="width: 25%;"><strong>Name of the Officer:</strong></td>
                                    <td style="width: 35%;">${officerName}</td>
                                    <td style="width: 22%;"><strong>Year & Month of Report:</strong></td>
                                    <td style="width: 18%;"><strong>${reportPeriod}</strong></td>
                                </tr>
                                <tr>
                                    <td><strong>Designation:</strong></td>
                                    <td>${officerDesignation}</td>
                                    <td><strong>Range / Station:</strong></td>
                                    <td>${rangeName} (${districtName})</td>
                                </tr>
                                <tr>
                                    <td><strong>Ministry / Department:</strong></td>
                                    <td colspan="3">${ministryDepartment}</td>
                                </tr>
                            </table>
                        </div>`;
                        $(win.document.body).prepend(headerHtml);

                        var footerHtml = `
                        <div style="margin-top: 50px;">
                            <div style="display: flex; justify-content: space-around; margin-bottom: 35px;">
                                <div style="text-align: center;">_______________________<br>Signature of Officer</div>
                                <div style="text-align: center;">_______________________<br>Official Stamp & Date</div>
                            </div>
                            <h4 style="text-align:center; font-weight: bold; margin-bottom: 15px;">For Approving Office Use Only</h4>
                            <table style="width: 100%; border-collapse: collapse; border: 1px solid black;" border="1">
                                <tr>
                                    <td style="padding: 20px 10px; text-align: center; border: 1px solid black; width: 33%;">Checked by<br><br><br><br>____________________</td>
                                    <td style="padding: 20px 10px; text-align: center; border: 1px solid black; width: 33%;">Subject Officer<br><br><br><br>____________________</td>
                                    <td style="padding: 20px 10px; text-align: center; border: 1px solid black; width: 33%;"></td>
                                </tr>
                                <tr>
                                    <td style="padding: 20px 10px; text-align: center; border: 1px solid black; width: 33%;">Recommended by<br><br><br><br>____________________</td>
                                    <td style="padding: 20px 10px; text-align: center; border: 1px solid black; width: 33%;">Approved by (PD / DD)<br><br><br><br>____________________</td>
                                    <td style="padding: 20px 10px; text-align: center; border: 1px solid black; width: 33%;"></td>
                                </tr>
                            </table>
                        </div>`;
                        $(win.document.body).append(footerHtml);
                    }
                }
            ],
            "language": {
                "emptyTable": "No daily diary (work done) records found for " + reportPeriod + ".",
                "search": "Search diary records:",
                "lengthMenu": "Show _MENU_ records"
            }
        });

        // Check for URL status parameters
        const urlParams = new URLSearchParams(window.location.search);
        const status = urlParams.get('status');
        if (status === 'added') {
            Swal.fire({
                icon: 'success',
                title: 'Diary Entry Added!',
                text: 'The daily diary record has been saved successfully.',
                confirmButtonColor: '#820100'
            });
            window.history.replaceState({}, document.title, window.location.pathname + window.location.search.replace(/[\?&]status=[^&]+/, ''));
        } else if (status === 'updated') {
            Swal.fire({
                icon: 'success',
                title: 'Diary Entry Updated!',
                text: 'Changes to the daily diary record have been saved successfully.',
                confirmButtonColor: '#820100'
            });
            window.history.replaceState({}, document.title, window.location.pathname + window.location.search.replace(/[\?&]status=[^&]+/, ''));
        } else if (status === 'db_error') {
            Swal.fire({
                icon: 'error',
                title: 'Database Error',
                text: 'Could not process database request. Please check required fields.',
                confirmButtonColor: '#820100'
            });
            window.history.replaceState({}, document.title, window.location.pathname + window.location.search.replace(/[\?&]status=[^&]+/, ''));
        }

        // -----------------------------------------------
        // EDIT – open modal and pre-fill fields
        // -----------------------------------------------
        $(document).on('click', '.btn-edit-prog', function() {
            var $row = $(this).closest('tr');
            $('#edit_id').val($row.data('id'));
            $('#edit_date').val($row.data('date'));
            $('#edit_task').val($row.data('task'));
            $('#edit_place').val($row.data('place'));
            $('#edit_distance').val($row.data('distance'));
            $('#edit_duty_time').val($row.data('duty-time'));
            new bootstrap.Modal(document.getElementById('editDailyDiaryModal')).show();
        });

        // -----------------------------------------------
        // DELETE – SweetAlert confirmation via AJAX
        // -----------------------------------------------
        $(document).on('click', '.btn-delete-prog', function() {
            var $row = $(this).closest('tr');
            var progTask = $row.data('task') || 'this diary entry';
            var recordId = $row.data('id');
            Swal.fire({
                icon: 'warning',
                title: 'Delete Diary Entry?',
                html: 'You are about to delete <strong>' + progTask + '</strong>.<br>This action cannot be undone.',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Yes, Delete',
                cancelButtonText: 'Cancel'
            }).then(function(result) {
                if (result.isConfirmed) {
                    $.ajax({
                        url: 'processors/delete_daily_diary.php',
                        type: 'POST',
                        data: {
                            id: recordId
                        },
                        dataType: 'json',
                        success: function(response) {
                            if (response.success) {
                                $row.fadeOut(400, function() {
                                    $row.remove();
                                    table.row($row).remove().draw(false);
                                });
                                Swal.fire({
                                    icon: 'success',
                                    title: 'Deleted!',
                                    text: 'The diary record has been removed.',
                                    confirmButtonColor: '#820100',
                                    timer: 2000,
                                    showConfirmButton: false
                                });
                            } else {
                                Swal.fire({
                                    icon: 'error',
                                    title: 'Error',
                                    text: response.message || 'Could not delete the record.',
                                    confirmButtonColor: '#820100'
                                });
                            }
                        },
                        error: function() {
                            Swal.fire({
                                icon: 'error',
                                title: 'Error',
                                text: 'Failed to communicate with DB processor.',
                                confirmButtonColor: '#820100'
                            });
                        }
                    });
                }
            });
        });

    });
</script>