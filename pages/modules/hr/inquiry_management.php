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
    /* Rotates the icon when the parent 'a' tag does NOT have the class 'collapsed' */
    .inquiry-toggle[aria-expanded="true"] .transition-icon {
        transform: rotate(90deg);
        color: #0d6efd !important;
        /* Changes arrow to primary blue when open */
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
                    <table class="table align-middle mb-0">
                        <thead class="bg-light small text-uppercase">
                            <tr>
                                <th class="ps-4">Date/Time</th>
                                <th>Sender Info</th>
                                <th>Subject (Click to Expand)</th>
                                <th>Status</th>
                                <th class="text-end pe-4">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($row = $result->fetch_assoc()): ?>
                                <tr class="table-hover">
                                    <td class="ps-4" style="width: 180px;">
                                        <?php
                                        $dt = new DateTime($row['received_at']);
                                        echo $dt->format('Y-m-d H:i');
                                        ?>
                                    </td>
                                    <td>
                                        <div class="fw-bold"><?php echo htmlspecialchars($row['sender_name']); ?></div>
                                        <div class="text-muted small"><?php echo htmlspecialchars($row['sender_email']); ?></div>
                                    </td>
                                    <td>
                                        <!-- FIXED COLLAPSE LINK -->
                                        <a href="#msg<?php echo $row['id']; ?>"
                                            class="text-decoration-none fw-bold d-flex align-items-center inquiry-toggle"
                                            data-bs-toggle="collapse"
                                            role="button"
                                            aria-expanded="false"
                                            aria-controls="msg<?php echo $row['id']; ?>">
                                            <i class="bi bi-chevron-right small me-2 text-muted transition-icon"></i>
                                            <?php echo htmlspecialchars($row['subject']); ?>
                                        </a>
                                    </td>
                                    <td>
                                        <?php
                                        $status = $row['status'] ?? 'Pending';
                                        $badge_class = 'secondary';
                                        if ($status == 'Pending') $badge_class = 'warning';
                                        elseif ($status == 'Minuted') $badge_class = 'info';
                                        elseif ($status == 'Replied') $badge_class = 'success';
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

                                <!-- COLLAPSE CONTENT - Now properly placed inside the table -->
                                <tr>
                                    <td colspan="5" class="p-0 border-0">
                                        <div class="collapse" id="msg<?php echo $row['id']; ?>">
                                            <div class="p-4 bg-light border-bottom shadow-inner">
                                                <label class="text-muted small fw-bold mb-2">FULL MESSAGE CONTENT:</label>
                                                <div class="bg-white p-3 rounded border">
                                                    <?php echo nl2br(htmlspecialchars($row['message_body'])); ?>
                                                </div>
                                            </div>
                                        </div>
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

<div class="modal fade" id="minuteModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-info text-white">
                <h5 class="modal-title"><i class="bi bi-person-up me-2"></i>Minute to Relevant Officer</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form action="processors/process_inquiry.php" method="POST">
                <div class="modal-body">
                    <input type="hidden" name="inquiry_id" id="min_inquiry_id">
                    <input type="hidden" name="action_type" value="minute">
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Select Officer</label>
                        <select name="officer_id" class="form-select" required>
                            <option value="">Search/Select Officer...</option>
                            <?php
                            $officers = $mysqli->query("SELECT id, officer_name, designation FROM office_details");
                            while ($off = $officers->fetch_assoc()) {
                                echo "<option value='{$off['id']}'>{$off['officer_name']} ({$off['designation']})</option>";
                            }
                            ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Instructions</label>
                        <textarea name="admin_note" class="form-control" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-info text-white w-100">Send to Officer</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="replyModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title"><i class="bi bi-chat-left-text me-2"></i>Send Direct Reply</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form action="processors/process_inquiry.php" method="POST">
                <div class="modal-body">
                    <input type="hidden" name="inquiry_id" id="rep_inquiry_id">
                    <input type="hidden" name="action_type" value="reply">
                    <div class="mb-3">
                        <label class="form-label small fw-bold">To Applicant</label>
                        <input type="text" id="rep_email" class="form-control-plaintext border-bottom" readonly>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Reply Content</label>
                        <textarea name="reply_content" class="form-control" rows="6" required></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-success w-100">Send Email</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include 'models/manual_entry_modal.php'; ?>

<script>
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