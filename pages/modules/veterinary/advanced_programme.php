<?php
session_start();
require_once '../../../config/db_connect.php';

// 1. Session and Role Guard
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

// 2. Fallback Definitions
$district_name = 'Unknown District';
$range_name    = 'Unknown Range';
$selected_year = isset($_GET['year']) ? intval($_GET['year']) : 2026;

// 3. Fetch Core Structural Meta Information
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

// 4. Fetch Advanced Programmes from DB
$programmes = [];
$stmt = $mysqli->prepare("SELECT id, date, task, place, time_duration FROM advanced_programmes WHERE range_id = ? AND YEAR(date) = ? ORDER BY date DESC");
$stmt->bind_param("ii", $range_id, $selected_year);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $programmes[] = $row;
}
$stmt->close();

require_once '../../../includes/header.php';
?>

<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.1/css/buttons.bootstrap5.min.css">



        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h3 class="fw-bold mb-1">Advanced Programme Management</h3>
                <p class="text-muted small mb-0">Yearly planning with Mid-term (6M) and Annual (1Y) Provincial Director approval.</p>
            </div>
            <a href="monthly-annual-reports.php" class="btn btn-secondary shadow-sm text-nowrap">
                <i class="bi bi-arrow-left me-2"></i>Back
            </a>
        </div>

        <div class="card shadow-sm mb-4 border-0">
            <div class="card-header bg-white py-3 border-0">
                <h6 class="mb-0 fw-bold text-dark"><i class="bi bi-grid-3x3-gap-fill me-2"></i>Quick Actions</h6>
            </div>
            <div class="card-body pt-0">
                <div class="row row-cols-2 row-cols-md-3 row-cols-lg-5 g-3">

                    <div class="col">
                        <button type="button" class="btn w-100 h-100 py-3 text-light border-0 shadow-sm d-flex flex-column align-items-center justify-content-center" style="background-color: #820100; min-height: 105px;" data-bs-toggle="modal" data-bs-target="#addAdvancedModal">
                            <i class="bi bi-file-earmark-bar-graph-fill fs-3 mb-1"></i>
                            <span class="text-center">Add Advanced Programme</span>
                        </button>
                    </div>

                </div>
            </div>
        </div>

        <div class="card shadow-sm border-0">
            <div class="card-body p-3">
                <table class="table table-hover align-middle" id="advancedProgTable">
                    <thead class="bg-light">
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
                        <?php if (empty($programmes)): ?>
                            <tr>
                                <td colspan="6" class="text-center text-muted py-3">No advanced programmes found for year <?= htmlspecialchars($selected_year) ?>.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($programmes as $prog): ?>
                                <tr
                                    data-id="<?= $prog['id'] ?>"
                                    data-date="<?= htmlspecialchars($prog['date']) ?>"
                                    data-year="<?= htmlspecialchars($selected_year) ?>"
                                    data-type="<?= htmlspecialchars($prog['task']) ?>"
                                    data-location="<?= htmlspecialchars($prog['place']) ?>"
                                    data-duration="<?= htmlspecialchars($prog['time_duration']) ?>">
                                    <td><?= htmlspecialchars($prog['date']) ?></td>
                                    <td><?= htmlspecialchars($selected_year) ?></td>
                                    <td><?= htmlspecialchars($prog['task']) ?></td>
                                    <td><?= htmlspecialchars($prog['place']) ?></td>
                                    <td>
                                        <?php
                                        $durations = explode(', ', $prog['time_duration']);
                                        foreach ($durations as $dur) {
                                            if (trim($dur) !== '') {
                                                echo '<span class="badge bg-light text-dark border me-1">' . htmlspecialchars(trim($dur)) . '</span>';
                                            }
                                        }
                                        ?>
                                    </td>
                                    <td class="text-end">
                                        <button class="btn btn-sm btn-outline-primary btn-edit-prog" title="Edit"><i class="bi bi-pencil-square"></i></button>
                                        <button class="btn btn-sm btn-outline-danger btn-delete-prog" title="Delete"><i class="bi bi-trash"></i></button>
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
<?php include 'models/add_advanced_programme.php'; ?>
<?php include 'models/edit_advanced_programme.php'; ?>

<?php require_once '../../../includes/footer.php'; ?>

<!-- DataTables CSS -->
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.1/css/buttons.bootstrap5.min.css">

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

<script>
    $(document).ready(function() {
        var table = $('#advancedProgTable').DataTable({
            "order": [
                [0, "desc"]
            ],
            "responsive": true,
            "pageLength": 15,
            "dom": '<"d-flex justify-content-between align-items-center mb-3"Bf>rt<"d-flex justify-content-between align-items-center mt-3"ip>',
            "buttons": [{
                    extend: 'csv',
                    className: 'btn btn-sm btn-success me-2',
                    text: '<i class="bi bi-file-earmark-spreadsheet"></i> CSV'
                },
                {
                    extend: 'pdf',
                    className: 'btn btn-sm btn-danger me-2',
                    text: '<i class="bi bi-file-earmark-pdf"></i> PDF',
                    customize: function(doc) {
                        doc.content.push({
                            margin: [0, 40, 0, 30],
                            columns: [{
                                    text: '_______________________\nSignature',
                                    alignment: 'center'
                                },
                                {
                                    text: '_______________________\nOfficial Stamp',
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
                                            margin: [0, 15, 0, 15]
                                        },
                                        {
                                            text: 'Subject Officer\n\n\n____________________',
                                            alignment: 'center',
                                            margin: [0, 15, 0, 15]
                                        },
                                        {
                                            text: '',
                                            margin: [0, 15, 0, 15]
                                        }
                                    ],
                                    [{
                                            text: 'Approved by\n\n\n____________________',
                                            alignment: 'center',
                                            margin: [0, 15, 0, 15]
                                        },
                                        {
                                            text: 'Chief Secretary, Eastern Province\n\n\n____________________',
                                            alignment: 'center',
                                            margin: [0, 15, 0, 15]
                                        },
                                        {
                                            text: '',
                                            margin: [0, 15, 0, 15]
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

        // Check for URL status parameters
        const urlParams = new URLSearchParams(window.location.search);
        const status = urlParams.get('status');
        if (status === 'added') {
            Swal.fire({
                icon: 'success',
                title: 'Programme Added!',
                text: 'The advanced programme has been saved successfully.',
                confirmButtonColor: '#370709'
            });
            window.history.replaceState({}, document.title, window.location.pathname);
        } else if (status === 'updated') {
            Swal.fire({
                icon: 'success',
                title: 'Programme Updated!',
                text: 'Changes have been saved successfully.',
                confirmButtonColor: '#370709'
            });
            window.history.replaceState({}, document.title, window.location.pathname);
        } else if (status === 'db_error') {
            Swal.fire({
                icon: 'error',
                title: 'Database Error',
                text: 'Could not process database request. Please check fields.',
                confirmButtonColor: '#370709'
            });
            window.history.replaceState({}, document.title, window.location.pathname);
        }

        // -----------------------------------------------
        // DYNAMIC DURATION FIELDS (ADD / EDIT)
        // -----------------------------------------------
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

        // -----------------------------------------------
        // EDIT – open modal and pre-fill fields
        // -----------------------------------------------
        $(document).on('click', '.btn-edit-prog', function() {
            var $row = $(this).closest('tr');
            $('#edit_id').val($row.data('id'));
            $('#edit_date').val($row.data('date'));
            $('#edit_year').val($row.data('year'));
            $('#edit_type').val($row.data('type'));
            $('#edit_location').val($row.data('location'));

            // Populate durations list
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

        // -----------------------------------------------
        // DELETE – SweetAlert confirmation via AJAX
        // -----------------------------------------------
        $(document).on('click', '.btn-delete-prog', function() {
            var $row = $(this).closest('tr');
            var progType = $row.data('type') || 'this record';
            var recordId = $row.data('id');
            Swal.fire({
                icon: 'warning',
                title: 'Delete Programme?',
                html: 'You are about to delete <strong>' + progType + '</strong>.<br>This action cannot be undone.',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Yes, Delete',
                cancelButtonText: 'Cancel'
            }).then(function(result) {
                if (result.isConfirmed) {
                    $.ajax({
                        url: 'processors/delete_advanced_programme.php',
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
                                    text: 'The programme record has been removed.',
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

<!-- SweetAlert2 -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>