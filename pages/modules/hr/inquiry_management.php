<?php
session_start();
require_once '../../../config/db_connect.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'administrator') {
    header("Location: ../../../index.php");
    exit();
}

$query = "SELECT * FROM inquiries ORDER BY received_at DESC";
$result = $mysqli->query($query);

require_once '../../../includes/header.php';
require_once '../../../includes/sidebar.php';
?>

<style>
    /* Your existing styles - unchanged */
    .inquiry-toggle[aria-expanded="true"] .transition-icon {
        transform: rotate(90deg);
        color: #0d6efd !important;
    }

    .transition-icon {
        transition: transform 0.2s ease-in-out;
    }

    .shadow-inner {
        box-shadow: inset 0 2px 4px rgba(0, 0, 0, 0.05);
    }
</style>

<div id="layoutSidenav_content">
    <main class="container-fluid px-4 pt-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h3 class="fw-bold">Inquiry Management</h3>
                <p class="text-muted small">Click on a subject to read the full message body.</p>
            </div>
            <button class="btn btn-dark shadow-sm" data-bs-toggle="modal" data-bs-target="#manualEntryModal">
                <i class="bi bi-envelope-plus me-2"></i>Manual Email Entry
            </button>
        </div>

        <div class="card shadow-sm border-0">
            <div class="card-header bg-white py-3">
                <h6 class="mb-0 fw-bold"><i class="bi bi-inbox me-2 text-primary"></i>Incoming Inquiries Log</h6>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table align-middle mb-0" id="inquiryTable">
                        <thead class="bg-light small text-uppercase">
                            <tr>
                                <th class="ps-4">Date/Time</th>
                                <th>Sender Info</th>
                                <th>Subject</th>
                                <th>Status</th>
                                <th class="text-end pe-4 no-export">Actions</th>
                            </tr>
                        </thead>
                        <tbody id="inquiryAccordion">
                            <?php while ($row = $result->fetch_assoc()): ?>
                                <tr class="table-hover inquiry-row" data-message="<?php echo htmlspecialchars($row['message_body']); ?>">
                                    <td class="ps-4" style="width: 180px;">
                                        <?php echo (new DateTime($row['received_at']))->format('Y-m-d H:i'); ?>
                                    </td>
                                    <td>
                                        <div class="fw-bold"><?php echo htmlspecialchars($row['sender_name']); ?></div>
                                        <div class="text-muted small"><?php echo htmlspecialchars($row['sender_email']); ?></div>
                                    </td>
                                    <td>
                                        <a href="javascript:void(0)" class="text-decoration-none fw-bold d-flex align-items-center inquiry-toggle">
                                            <i class="bi bi-chevron-right small me-2 text-muted transition-icon"></i>
                                            <?php echo htmlspecialchars($row['subject']); ?>
                                        </a>
                                    </td>
                                    <td>
                                        <?php
                                        $status = $row['status'] ?? 'Pending';
                                        $badge_class = ($status == 'Pending') ? 'warning' : (($status == 'Minuted') ? 'info' : 'success');
                                        ?>
                                        <span class="badge bg-<?php echo $badge_class; ?>"><?php echo $status; ?></span>
                                    </td>
                                    <td class="text-end pe-4">
                                        <button class="btn btn-outline-info btn-sm" onclick="openMinuteModal(<?php echo $row['id']; ?>)">
                                            <i class="bi bi-person-up me-1"></i> Minute
                                        </button>
                                        <button class="btn btn-outline-success btn-sm" onclick="openReplyModal(<?php echo $row['id']; ?>, '<?php echo htmlspecialchars($row['sender_email'] ?? ''); ?>')">
                                            <i class="bi bi-reply me-1"></i> Reply
                                        </button>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>
</div>

<!-- Modals and rest of your code remain exactly the same -->
<div class="modal fade" id="minuteModal" tabindex="-1"> ... </div>
<div class="modal fade" id="replyModal" tabindex="-1"> ... </div>

<?php include 'models/manual_entry_modal.php'; ?>

<script src="https://code.jquery.com/jquery-3.7.0.js"></script>
<script src="https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.8/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/dataTables.buttons.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.bootstrap5.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.0/pdfmake.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.0/vfs_fonts.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.html5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.print.min.js"></script>

<script>
    $(document).ready(function() {
        // 1. Helper function to format the hidden row
        function formatChildRow(message) {
            return `
            <div class="p-4 bg-light border-bottom shadow-inner">
                <label class="text-muted small fw-bold mb-2">FULL MESSAGE CONTENT:</label>
                <div class="bg-white p-3 rounded border">
                    ${message.replace(/\n/g, '<br>')}
                </div>
            </div>`;
        }

        var table = $('#inquiryTable').DataTable({
            "order": [
                [0, "desc"]
            ],
            "pageLength": 10,
            "dom": '<"d-flex justify-content-between align-items-center mb-3"Bf>rt<"d-flex justify-content-between align-items-center mt-3"ip>',
            "buttons": [{
                    extend: 'excelHtml5',
                    className: 'btn btn-sm btn-success me-2',
                    exportOptions: {
                        columns: ':not(.no-export)'
                    }
                },
                {
                    extend: 'pdfHtml5',
                    className: 'btn btn-sm btn-danger me-2',
                    exportOptions: {
                        columns: ':not(.no-export)'
                    }
                },
                {
                    extend: 'print',
                    className: 'btn btn-sm btn-dark',
                    exportOptions: {
                        columns: ':not(.no-export)'
                    }
                }
            ],
            "columnDefs": [{
                "orderable": false,
                "targets": [2, 4]
            }]
        });

        // 2. Handle the click event for showing/hiding the child row
        $('#inquiryTable tbody').on('click', '.inquiry-toggle', function() {
            var tr = $(this).closest('tr');
            var row = table.row(tr);
            var icon = $(this).find('.transition-icon');

            if (row.child.isShown()) {
                // This row is already open - close it
                row.child.hide();
                tr.removeClass('shown');
                icon.css('transform', 'rotate(0deg)').css('color', '');
            } else {
                // Open this row
                var message = tr.data('message');
                row.child(formatChildRow(message), 'p-0').show(); // 'p-0' class removes padding from the new td
                tr.addClass('shown');
                icon.css('transform', 'rotate(90deg)').css('color', '#0d6efd');
            }
        });
    });

    // Modal functions remain the same
    function openMinuteModal(id) {
        $('#min_inquiry_id').val(id);
        $('#minuteModal').modal('show');
    }

    function openReplyModal(id, email) {
        $('#rep_inquiry_id').val(id);
        $('#rep_email').val(email);
        $('#replyModal').modal('show');
    }
</script>

<?php require_once '../../../includes/footer.php'; ?>