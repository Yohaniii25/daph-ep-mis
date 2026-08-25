<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once '../../../includes/header.php';

$allowed_roles = ['training_officer', 'administrator', 'provincial_director', 'district_dd'];
if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], $allowed_roles)) {
    die("Access denied");
}

require_once '../../../config/db_connect.php';

// Resolve Training Centre Data Isolation
$all_centers = [];
$centers_res = $mysqli->query("SELECT id, center_name, location FROM training_centers WHERE is_active = 1 ORDER BY id ASC");
if ($centers_res) {
    while ($row = $centers_res->fetch_assoc()) {
        $all_centers[] = $row;
    }
}

$current_center_id = $_SESSION['training_center_id'] ?? null;
if (empty($current_center_id) && isset($_GET['center_id'])) {
    $current_center_id = intval($_GET['center_id']);
}
if (empty($current_center_id) && !empty($all_centers)) {
    $current_center_id = $all_centers[0]['id'];
}

$current_training_center = null;
foreach ($all_centers as $c) {
    if ($c['id'] == $current_center_id) {
        $current_training_center = $c;
        break;
    }
}

// Selected Year
$selected_year = isset($_GET['year']) ? intval($_GET['year']) : intval(date('Y'));
if ($selected_year < 2020 || $selected_year > 2035) {
    $selected_year = intval(date('Y'));
}

// Auto-seed sample entries if table is empty for current center
$check_stmt = $mysqli->prepare("SELECT COUNT(*) AS total FROM training_advanced_programmes WHERE training_center_id = ?");
if ($check_stmt) {
    $check_stmt->bind_param("i", $current_center_id);
    $check_stmt->execute();
    $cnt = $check_stmt->get_result()->fetch_assoc()['total'];
    if ($cnt == 0 && $current_center_id > 0) {
        $seed_samples = [
            ['2026-03-15', 'Modern Milking & Dairy Processing Workshop', 'Main Lecture Hall A', '09:00 AM - 12:30 PM, 02:00 PM - 04:30 PM'],
            ['2026-05-20', 'Poultry Disease Control & Vaccination Seminar', 'Auditorium Block B', '09:30 AM - 01:00 PM'],
            ['2026-07-10', 'Pasture Cultivation & Forage Management Field Demo', 'Demonstration Plot 03', '08:30 AM - 11:30 AM']
        ];
        $seed_insert = $mysqli->prepare("INSERT INTO training_advanced_programmes (training_center_id, date, task, place, distance, time_duration) VALUES (?, ?, ?, ?, 0.00, ?)");
        if ($seed_insert) {
            foreach ($seed_samples as $s) {
                $seed_insert->bind_param("issss", $current_center_id, $s[0], $s[1], $s[2], $s[3]);
                $seed_insert->execute();
            }
            $seed_insert->close();
        }
    }
    $check_stmt->close();
}

// Fetch Advanced Programmes for current training center and year
$programmes = [];
$stmt = $mysqli->prepare("SELECT id, date, task, place, time_duration FROM training_advanced_programmes WHERE training_center_id = ? AND YEAR(date) = ? ORDER BY date DESC");
if ($stmt) {
    $stmt->bind_param("ii", $current_center_id, $selected_year);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $programmes[] = $row;
    }
    $stmt->close();
}
?>

<?php require_once '../../../includes/sidebar.php'; ?>

<link rel="stylesheet" href="../../../assets/css/dataTables.bootstrap5.min.css">
<link rel="stylesheet" href="../../../assets/css/buttons.bootstrap5.min.css">
<link rel="stylesheet" href="../../../assets/css/sweetalert2.min.css">

<div id="layoutSidenav_content" class="bg-light">
    <main class="container-fluid px-4 pt-4 pb-5">

        <!-- Top Header & Location Info -->
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
            <div>
                <h3 class="fw-bold mb-1 text-dark">Advance Programme Management</h3>
                <p class="text-muted small mb-0">
                    Annual training schedules &amp; advance activity plans for 
                    <strong class="text-dark"><?= htmlspecialchars($current_training_center['center_name'] ?? 'Training Centre') ?></strong>
                    (Location: <?= htmlspecialchars($current_training_center['location'] ?? 'N/A') ?>)
                </p>
            </div>

            <div class="d-flex flex-wrap align-items-center gap-2">
                <!-- Training Centre Selector (for Admins / Multi-center users) -->
                <?php if (in_array($_SESSION['role'], ['administrator', 'provincial_director', 'district_dd']) && count($all_centers) > 1): ?>
                    <form method="GET" action="" class="d-inline-block">
                        <input type="hidden" name="year" value="<?= $selected_year ?>">
                        <select name="center_id" class="form-select form-select-sm shadow-sm border-secondary fw-semibold" onchange="this.form.submit()">
                            <?php foreach ($all_centers as $tc): ?>
                                <option value="<?= $tc['id'] ?>" <?= $tc['id'] == $current_center_id ? 'selected' : '' ?>>
                                    🏢 <?= htmlspecialchars($tc['center_name']) ?> (<?= htmlspecialchars($tc['location']) ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </form>
                <?php endif; ?>

                <!-- Year Filter Dropdown -->
                <form method="GET" action="" class="d-inline-block">
                    <?php if (!empty($current_center_id)): ?>
                        <input type="hidden" name="center_id" value="<?= $current_center_id ?>">
                    <?php endif; ?>
                    <div class="input-group input-group-sm shadow-sm">
                        <span class="input-group-text bg-white fw-bold text-secondary"><i class="bi bi-calendar-event me-1"></i> Year:</span>
                        <select name="year" class="form-select fw-bold text-dark" onchange="this.form.submit()">
                            <?php for ($y = 2024; $y <= 2030; $y++): ?>
                                <option value="<?= $y ?>" <?= $y == $selected_year ? 'selected' : '' ?>><?= $y ?></option>
                            <?php endfor; ?>
                        </select>
                    </div>
                </form>

                <button type="button" class="btn btn-sm text-light shadow-sm fw-semibold rounded-3 px-3 py-1.5" style="background-color: #370709;" data-bs-toggle="modal" data-bs-target="#addAdvancedModal">
                    <i class="bi bi-calendar-plus me-1"></i> Add Advance Programme
                </button>
            </div>
        </div>

        <!-- Quick Action Card -->
        <div class="card shadow-sm mb-4 border-0 rounded-4">
            <div class="card-header bg-white py-3 border-0">
                <h6 class="mb-0 fw-bold text-dark"><i class="bi bi-grid-3x3-gap-fill me-2 text-warning"></i>Quick Actions</h6>
            </div>
            <div class="card-body pt-0">
                <div class="row row-cols-2 row-cols-md-4 g-3">
                    <div class="col">
                        <button type="button" class="btn w-100 py-3 text-light border-0 shadow-sm d-flex flex-column align-items-center justify-content-center rounded-3" style="background-color: #370709;" data-bs-toggle="modal" data-bs-target="#addAdvancedModal">
                            <i class="bi bi-calendar-plus fs-3 mb-1"></i>
                            <span class="text-center fw-semibold">Add Advance Programme</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Main Data Table Card -->
        <div class="card shadow-sm border-0 rounded-4">
            <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
                <h6 class="m-0 fw-bold text-dark">
                    <i class="bi bi-calendar-week me-2 text-primary"></i>
                    Advance Programme List - Year <?= $selected_year ?>
                </h6>
                <span class="badge bg-secondary bg-opacity-10 text-dark border px-3 py-1.5 font-monospace">
                    <?= htmlspecialchars($current_training_center['center_name'] ?? 'Training Centre') ?>
                </span>
            </div>

            <div class="card-body p-4">
                <div class="table-responsive">
                    <table class="table table-hover align-middle border w-100 small" id="advancedProgTable">
                        <thead class="table-light text-secondary">
                            <tr>
                                <th>Started Date</th>
                                <th>Year</th>
                                <th>Programme Type</th>
                                <th>Location</th>
                                <th>Duration</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($programmes as $prog): ?>
                                <tr data-id="<?= $prog['id'] ?>"
                                    data-date="<?= htmlspecialchars($prog['date']) ?>"
                                    data-year="<?= htmlspecialchars($selected_year) ?>"
                                    data-type="<?= htmlspecialchars($prog['task']) ?>"
                                    data-location="<?= htmlspecialchars($prog['place']) ?>"
                                    data-duration="<?= htmlspecialchars($prog['time_duration']) ?>">
                                    <td class="font-monospace text-nowrap"><?= htmlspecialchars($prog['date']) ?></td>
                                    <td class="font-monospace"><?= htmlspecialchars($selected_year) ?></td>
                                    <td class="fw-semibold text-dark"><?= htmlspecialchars($prog['task']) ?></td>
                                    <td><?= htmlspecialchars($prog['place']) ?></td>
                                    <td>
                                        <?php
                                        $durations = explode(', ', $prog['time_duration']);
                                        foreach ($durations as $dur) {
                                            if (trim($dur) !== '') {
                                                echo '<span class="badge bg-light text-dark border me-1 my-0.5">' . htmlspecialchars(trim($dur)) . '</span>';
                                            }
                                        }
                                        ?>
                                    </td>
                                    <td class="text-end">
                                        <button class="btn btn-sm btn-outline-primary btn-edit-prog me-1" title="Edit"><i class="bi bi-pencil-square"></i></button>
                                        <button class="btn btn-sm btn-outline-danger btn-delete-prog" title="Delete"><i class="bi bi-trash"></i></button>
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

<!-- Included Modals -->
<?php include 'models/add_advanced_programme.php'; ?>
<?php include 'models/edit_advanced_programme.php'; ?>

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
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
    $(document).ready(function() {
        var table = $('#advancedProgTable').DataTable({
            "order": [[0, "desc"]],
            "responsive": true,
            "pageLength": 15,
            "dom": '<"d-flex justify-content-between align-items-center mb-3"Bf>rt<"d-flex justify-content-between align-items-center mt-3"ip>',
            "buttons": [
                {
                    extend: 'csv',
                    className: 'btn btn-sm btn-success me-2 rounded shadow-sm',
                    text: '<i class="bi bi-file-earmark-spreadsheet me-1"></i> CSV'
                },
                {
                    extend: 'pdf',
                    className: 'btn btn-sm btn-danger me-2 rounded shadow-sm',
                    text: '<i class="bi bi-file-earmark-pdf me-1"></i> PDF',
                    customize: function(doc) {
                        doc.content.push({
                            margin: [0, 40, 0, 30],
                            columns: [
                                { text: '_______________________\nSignature', alignment: 'center' },
                                { text: '_______________________\nOfficial Stamp', alignment: 'center' }
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
                                    [
                                        { text: 'Checked by\n\n\n____________________', alignment: 'center', margin: [0, 15, 0, 15] },
                                        { text: 'Subject Officer\n\n\n____________________', alignment: 'center', margin: [0, 15, 0, 15] },
                                        { text: '', margin: [0, 15, 0, 15] }
                                    ],
                                    [
                                        { text: 'Approved by\n\n\n____________________', alignment: 'center', margin: [0, 15, 0, 15] },
                                        { text: 'Chief Secretary, Eastern Province\n\n\n____________________', alignment: 'center', margin: [0, 15, 0, 15] },
                                        { text: '', margin: [0, 15, 0, 15] }
                                    ]
                                ]
                            }
                        });
                    }
                },
                {
                    extend: 'print',
                    className: 'btn btn-sm btn-dark rounded shadow-sm',
                    text: '<i class="bi bi-printer me-1"></i> Print',
                    customize: function(win) {
                        var footerHtml = `
                        <div style="margin-top: 60px;">
                            <div style="display: flex; justify-content: space-around; margin-bottom: 40px;">
                                <div style="text-align: center;">_______________________<br>Signature</div>
                                <div style="text-align: center;">_______________________<br>Official Stamp</div>
                            </div>
                            <h4 style="text-align:center; font-weight: bold; margin-bottom: 20px;">For Approving Office Use Only</h4>
                            <table style="width: 100%; border-collapse: collapse; border: 1px solid black;" border="1">
                                <tr>
                                    <td style="padding: 25px 10px; text-align: center; border: 1px solid black; width: 33%;">Checked by<br><br><br><br>____________________</td>
                                    <td style="padding: 25px 10px; text-align: center; border: 1px solid black; width: 33%;">Subject Officer<br><br><br><br>____________________</td>
                                    <td style="padding: 25px 10px; text-align: center; border: 1px solid black; width: 33%;"></td>
                                </tr>
                                <tr>
                                    <td style="padding: 25px 10px; text-align: center; border: 1px solid black; width: 33%;">Approved by<br><br><br><br>____________________</td>
                                    <td style="padding: 25px 10px; text-align: center; border: 1px solid black; width: 33%;">Chief Secretary, Eastern Province<br><br><br><br>____________________</td>
                                    <td style="padding: 25px 10px; text-align: center; border: 1px solid black; width: 33%;"></td>
                                </tr>
                            </table>
                        </div>`;
                        $(win.document.body).append(footerHtml);
                    }
                }
            ],
            "language": {
                "search": "Search programmes:",
                "lengthMenu": "Show _MENU_ records"
            }
        });

        // Check for URL status parameters (SweetAlert2 Feedback)
        const urlParams = new URLSearchParams(window.location.search);
        const status = urlParams.get('status');
        if (status === 'added') {
            Swal.fire({
                icon: 'success',
                title: 'Programme Saved!',
                text: 'The training advance programme has been saved successfully.',
                confirmButtonColor: '#370709',
                timer: 3000,
                timerProgressBar: true
            });
            window.history.replaceState({}, document.title, window.location.pathname);
        } else if (status === 'updated') {
            Swal.fire({
                icon: 'success',
                title: 'Programme Updated!',
                text: 'Changes have been updated successfully.',
                confirmButtonColor: '#370709',
                timer: 3000,
                timerProgressBar: true
            });
            window.history.replaceState({}, document.title, window.location.pathname);
        } else if (status === 'db_error') {
            Swal.fire({
                icon: 'error',
                title: 'Database Error',
                text: 'Could not process database request. Please check required fields.',
                confirmButtonColor: '#370709'
            });
            window.history.replaceState({}, document.title, window.location.pathname);
        }

        // Dynamic duration fields
        $(document).on('click', '.add-duration-btn', function() {
            var container = $(this).closest('#duration-container-add, #duration-container-edit');
            var inputHtml = `
                <div class="input-group mb-1 duration-entry">
                    <input type="text" name="duration[]" class="form-control form-control-sm" placeholder="e.g. 2 Hours or 09:00 AM - 11:00 AM" required>
                    <button type="button" class="btn btn-sm btn-outline-danger remove-duration-btn"><i class="bi bi-dash"></i></button>
                </div>
            `;
            container.append(inputHtml);
        });

        $(document).on('click', '.remove-duration-btn', function() {
            $(this).closest('.duration-entry').remove();
        });

        // Edit button pre-fill handler
        $(document).on('click', '.btn-edit-prog', function() {
            var $row = $(this).closest('tr');
            $('#edit_id').val($row.data('id'));
            $('#edit_date').val($row.data('date'));
            $('#edit_year').val($row.data('year'));
            $('#edit_type').val($row.data('type'));
            $('#edit_location').val($row.data('location'));

            var durationStr = $row.data('duration') || '';
            var durations = String(durationStr).split(', ');
            var container = $('#duration-container-edit');
            container.empty();

            if (durations.length === 0 || (durations.length === 1 && durations[0] === '')) {
                container.append(`
                    <div class="input-group mb-1 duration-entry">
                        <input type="text" name="duration[]" class="form-control form-control-sm" placeholder="e.g. 2 Hours or 09:00 AM - 11:00 AM" required>
                        <button type="button" class="btn btn-sm btn-outline-success add-duration-btn"><i class="bi bi-plus"></i></button>
                    </div>
                `);
            } else {
                durations.forEach(function(dur, index) {
                    var btnHtml = index === 0 ?
                        '<button type="button" class="btn btn-sm btn-outline-success add-duration-btn"><i class="bi bi-plus"></i></button>' :
                        '<button type="button" class="btn btn-sm btn-outline-danger remove-duration-btn"><i class="bi bi-dash"></i></button>';
                    container.append(`
                        <div class="input-group mb-1 duration-entry">
                            <input type="text" name="duration[]" class="form-control form-control-sm" value="${dur}" placeholder="e.g. 2 Hours or 09:00 AM - 11:00 AM" required>
                            ${btnHtml}
                        </div>
                    `);
                });
            }
            new bootstrap.Modal(document.getElementById('editAdvancedModal')).show();
        });

        // Delete button SweetAlert2 confirmation handler
        $(document).on('click', '.btn-delete-prog', function() {
            var $row = $(this).closest('tr');
            var progType = $row.data('type') || 'this record';
            var recordId = $row.data('id');
            var currentCenterId = <?= json_encode($current_center_id) ?>;

            Swal.fire({
                icon: 'warning',
                title: 'Delete Programme?',
                html: 'You are about to delete <strong>' + progType + '</strong>.<br>This action cannot be undone.',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#6c757d',
                confirmButtonText: '<i class="bi bi-trash me-1"></i> Yes, Delete',
                cancelButtonText: 'Cancel'
            }).then(function(result) {
                if (result.isConfirmed) {
                    $.ajax({
                        url: 'processors/delete_advanced_programme.php',
                        type: 'POST',
                        data: {
                            id: recordId,
                            training_center_id: currentCenterId
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
                                    text: 'The advance programme record has been removed.',
                                    confirmButtonColor: '#370709',
                                    timer: 2000,
                                    showConfirmButton: false
                                });
                            } else {
                                Swal.fire({
                                    icon: 'error',
                                    title: 'Error',
                                    text: response.message || 'Could not delete the record.',
                                    confirmButtonColor: '#370709'
                                });
                            }
                        },
                        error: function() {
                            Swal.fire({
                                icon: 'error',
                                title: 'Error',
                                text: 'Failed to communicate with DB processor.',
                                confirmButtonColor: '#370709'
                            });
                        }
                    });
                }
            });
        });
    });
</script>

<?php require_once '../../../includes/footer.php'; ?>
