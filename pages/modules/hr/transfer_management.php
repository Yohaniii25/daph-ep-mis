<?php
/**
 * pages/modules/hr/transfer_management.php
 * Admin Branch Personnel Transfer Management & Authorization Interface
 * Dedicated workflow hub for Administrator, Provincial Director, and HQ Deputy Directors
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../../config/db_connect.php';

/** @var mysqli $mysqli */
global $mysqli;
require_once '../../../includes/approval_helper.php';
require_once '../../../includes/notification_helper.php';

// Role-Based Access Control: Strictly Provincial Admin Branch Personnel
$allowed_roles = ['administrator', 'provincial_director', 'deputy_director_hq_1', 'deputy_director_hq_2'];
if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], $allowed_roles)) {
    header("Location: ../../../index.php");
    exit();
}

$user_role = $_SESSION['role'];
$user_id   = intval($_SESSION['user_id'] ?? 0);
$user_name = $_SESSION['full_name'] ?? $_SESSION['username'] ?? 'Admin Branch Executive';

// Resolve Admin Branch Role Title
$role_labels = [
    'administrator'        => 'Provincial HR Administrator',
    'provincial_director'  => 'Provincial Director of Animal Production & Health',
    'deputy_director_hq_1' => 'Deputy Director H/Q (1) - Operations',
    'deputy_director_hq_2' => 'Deputy Director H/Q (2) - Planning'
];
$admin_role_title = $role_labels[$user_role] ?? ucwords(str_replace('_', ' ', $user_role));

// Fetch all transfer requests from pending_approvals
$transfers_query = "
    SELECT 
        pa.*,
        d.name AS district_name,
        vr.name AS range_name,
        u.email AS officer_email,
        u.phone AS officer_phone,
        u.designation AS officer_designation,
        u.service_number AS officer_service_number,
        u.current_station AS officer_current_station
    FROM pending_approvals pa
    LEFT JOIN districts d ON pa.district_id = d.id
    LEFT JOIN veterinary_ranges vr ON pa.range_id = vr.id
    LEFT JOIN users u ON pa.record_id = u.id
    WHERE pa.module = 'hr' AND pa.record_type = 'transfer_request'
    ORDER BY 
        CASE WHEN pa.status = 'pending' THEN 1 ELSE 2 END,
        pa.created_at DESC
";
$transfers_res = $mysqli->query($transfers_query);

$pending_transfers  = [];
$approved_transfers = [];
$rejected_transfers = [];
$all_transfers      = [];

if ($transfers_res) {
    while ($row = $transfers_res->fetch_assoc()) {
        $row['old_data_arr'] = json_decode($row['old_data'] ?? '{}', true) ?: [];
        $row['new_data_arr'] = json_decode($row['new_data'] ?? '{}', true) ?: [];
        $row['time_ago']     = format_time_ago($row['created_at']);
        $row['formatted_date'] = date('M d, Y h:i A', strtotime($row['created_at']));

        $all_transfers[] = $row;
        if ($row['status'] === 'pending') {
            $pending_transfers[] = $row;
        } elseif ($row['status'] === 'approved') {
            $approved_transfers[] = $row;
        } elseif ($row['status'] === 'rejected') {
            $rejected_transfers[] = $row;
        }
    }
}

// Fetch automated transfer notifications for the current Admin Branch user
$notifs_query = "
    SELECT id, title, message, type, link, is_read, created_at 
    FROM notifications 
    WHERE user_id = ? AND type = 'transfer_alert'
    ORDER BY created_at DESC 
    LIMIT 15
";
$transfer_notifications = [];
$stmt_n = $mysqli->prepare($notifs_query);
if ($stmt_n) {
    $stmt_n->bind_param("i", $user_id);
    $stmt_n->execute();
    $n_res = $stmt_n->get_result();
    while ($nrow = $n_res->fetch_assoc()) {
        $nrow['time_ago'] = format_time_ago($nrow['created_at']);
        $transfer_notifications[] = $nrow;
    }
    $stmt_n->close();
}

$count_pending  = count($pending_transfers);
$count_approved = count($approved_transfers);
$count_rejected = count($rejected_transfers);
$count_total    = count($all_transfers);

require_once '../../../includes/header.php';
require_once '../../../includes/sidebar.php';
?>

<link rel="stylesheet" href="../../../assets/css/dataTables.bootstrap5.min.css">
<style>
    :root {
        --daph-maroon: #500707;
        --daph-maroon-dark: #370709;
        --daph-gold: #c28e2b;
        --daph-gold-soft: #fcf6e8;
    }

    .transfer-metric-card {
        border-radius: 12px;
        transition: transform 0.2s ease, box-shadow 0.2s ease;
        border: 1px solid rgba(0, 0, 0, 0.08);
        background: #fff;
    }
    .transfer-metric-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 8px 20px rgba(0, 0, 0, 0.06);
    }

    .station-badge-from {
        background-color: #fee2e2;
        color: #991b1b;
        border: 1px solid #fca5a5;
        border-radius: 6px;
        font-weight: 500;
        font-size: 12px;
        padding: 4px 8px;
        display: inline-block;
    }

    .station-badge-to {
        background-color: #dcfce7;
        color: #166534;
        border: 1px solid #86efac;
        border-radius: 6px;
        font-weight: 600;
        font-size: 12px;
        padding: 4px 8px;
        display: inline-block;
    }

    .transfer-arrow-icon {
        color: #6b7280;
        margin: 0 4px;
        font-weight: bold;
    }

    .filter-tab-btn {
        border-radius: 20px;
        padding: 6px 16px;
        font-size: 0.85rem;
        font-weight: 600;
        border: 1px solid #ced4da;
        background: #fff;
        color: #495057;
        transition: all 0.2s ease;
    }
    .filter-tab-btn:hover {
        background: #f8f9fa;
        color: var(--daph-maroon);
        border-color: var(--daph-maroon);
    }
    .filter-tab-btn.active {
        background: var(--daph-maroon) !important;
        color: #fff !important;
        border-color: var(--daph-maroon) !important;
        box-shadow: 0 4px 10px rgba(80, 7, 7, 0.25);
    }

    .notification-item-card {
        border-left: 3px solid var(--daph-gold);
        transition: background 0.2s ease;
    }
    .notification-item-card.unread {
        background-color: #fffcf4;
        border-left-color: #f59e0b;
    }
    .notification-item-card:hover {
        background-color: #f9fafb;
    }
</style>

<div id="layoutSidenav_content">
    <main class="container-fluid px-4 pt-3 pb-5">

        <!-- Breadcrumb & Top Bar -->
        <div class="d-flex flex-wrap justify-content-between align-items-center mb-3 pb-2 border-bottom">
            <div>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb mb-1 small">
                        <li class="breadcrumb-item"><a href="../../../dashboard.php" class="text-decoration-none text-muted">Dashboard</a></li>
                        <li class="breadcrumb-item"><a href="employee_managment.php" class="text-decoration-none text-muted">HR Directory</a></li>
                        <li class="breadcrumb-item active text-danger fw-bold" aria-current="page">Admin Transfer Workflow</li>
                    </ol>
                </nav>
                <h3 class="fw-bold text-dark mb-0 d-flex align-items-center gap-2">
                    <i class="bi bi-arrow-left-right text-danger"></i>
                    Admin Branch Transfer Management
                </h3>
                <p class="text-muted small mb-0">Role-based transfer authorization hub • Automated request routing, workstation updates, and notification processing</p>
            </div>
            <div class="d-flex align-items-center gap-2 mt-2 mt-md-0">
                <!-- Role-Based Authorization Badge -->
                <span class="badge bg-danger-subtle text-danger border border-danger-subtle px-3 py-2 rounded-pill d-flex align-items-center gap-1 shadow-sm">
                    <i class="bi bi-shield-lock-fill"></i>
                    <span class="fw-semibold">Role: <?= htmlspecialchars($admin_role_title) ?></span>
                </span>
                <button class="btn btn-danger btn-sm shadow-sm px-3 d-flex align-items-center gap-1" onclick="location.reload();" style="background-color: #500707; border-color: #500707;">
                    <i class="bi bi-arrow-clockwise"></i> Refresh
                </button>
            </div>
        </div>

        <!-- Metric KPI Cards -->
        <div class="row g-3 mb-4">
            <div class="col-xl-3 col-md-6">
                <div class="card border-0 shadow-sm h-100 transfer-metric-card" style="border-left: 4px solid #f59e0b !important;">
                    <div class="card-body p-3">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <small class="text-muted text-uppercase fw-bold" style="font-size: 11px;">Pending Transfer Requests</small>
                                <h3 class="fw-bold mb-0 text-warning" id="kpiPendingCount"><?= $count_pending ?></h3>
                                <small class="text-muted" style="font-size: 11px;">Awaiting executive action</small>
                            </div>
                            <div class="rounded-circle p-3 text-white bg-warning shadow-sm">
                                <i class="bi bi-hourglass-split fs-4"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-xl-3 col-md-6">
                <div class="card border-0 shadow-sm h-100 transfer-metric-card" style="border-left: 4px solid #198754 !important;">
                    <div class="card-body p-3">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <small class="text-muted text-uppercase fw-bold" style="font-size: 11px;">Authorized Transfers</small>
                                <h3 class="fw-bold mb-0 text-success" id="kpiApprovedCount"><?= $count_approved ?></h3>
                                <small class="text-muted" style="font-size: 11px;">Station updated in live registry</small>
                            </div>
                            <div class="rounded-circle p-3 text-white bg-success shadow-sm">
                                <i class="bi bi-check-circle-fill fs-4"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-xl-3 col-md-6">
                <div class="card border-0 shadow-sm h-100 transfer-metric-card" style="border-left: 4px solid #dc3545 !important;">
                    <div class="card-body p-3">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <small class="text-muted text-uppercase fw-bold" style="font-size: 11px;">Rejected Requests</small>
                                <h3 class="fw-bold mb-0 text-danger" id="kpiRejectedCount"><?= $count_rejected ?></h3>
                                <small class="text-muted" style="font-size: 11px;">Station assignments retained</small>
                            </div>
                            <div class="rounded-circle p-3 text-white bg-danger shadow-sm">
                                <i class="bi bi-x-circle-fill fs-4"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-xl-3 col-md-6">
                <div class="card border-0 shadow-sm h-100 transfer-metric-card" style="border-left: 4px solid var(--daph-maroon) !important;">
                    <div class="card-body p-3">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <small class="text-muted text-uppercase fw-bold" style="font-size: 11px;">Total Transfer Footprint</small>
                                <h3 class="fw-bold mb-0 text-dark" id="kpiTotalCount"><?= $count_total ?></h3>
                                <small class="text-muted" style="font-size: 11px;">Rotational transfer audit trail</small>
                            </div>
                            <div class="rounded-circle p-3 text-white shadow-sm" style="background: var(--daph-maroon);">
                                <i class="bi bi-journal-text fs-4"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-4">
            <!-- Left / Main Column: Transfer Requests Queue & History Table -->
            <div class="col-xl-8 col-lg-7">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white py-3 border-bottom d-flex flex-wrap align-items-center justify-content-between gap-2">
                        <div class="d-flex flex-wrap align-items-center gap-2" id="transferTabButtonGroup">
                            <button type="button" class="filter-tab-btn active" data-filter="pending">
                                <i class="bi bi-hourglass-split me-1 text-warning"></i>Pending Requests
                                <span class="badge bg-warning text-dark rounded-pill ms-1" id="tabPendingBadge"><?= $count_pending ?></span>
                            </button>
                            <button type="button" class="filter-tab-btn" data-filter="approved">
                                <i class="bi bi-check-circle me-1 text-success"></i>Approved History
                                <span class="badge bg-secondary rounded-pill ms-1"><?= $count_approved ?></span>
                            </button>
                            <button type="button" class="filter-tab-btn" data-filter="rejected">
                                <i class="bi bi-x-circle me-1 text-danger"></i>Rejected History
                                <span class="badge bg-secondary rounded-pill ms-1"><?= $count_rejected ?></span>
                            </button>
                            <button type="button" class="filter-tab-btn" data-filter="all">
                                <i class="bi bi-collection me-1"></i>All Transfers
                                <span class="badge bg-secondary rounded-pill ms-1"><?= $count_total ?></span>
                            </button>
                        </div>
                    </div>

                    <div class="card-body p-3">
                        <div class="table-responsive">
                            <table id="transferManagementTable" class="table table-hover align-middle w-100">
                                <thead>
                                    <tr>
                                        <th>Officer &amp; Service No</th>
                                        <th>Current &amp; Target Station</th>
                                        <th>Transfer Justification</th>
                                        <th>Submitted By</th>
                                        <th>Status</th>
                                        <th class="text-center" style="width: 170px;">Admin Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($all_transfers as $req):
                                        $old_d = $req['old_data_arr'];
                                        $new_d = $req['new_data_arr'];

                                        $from_station = $old_d['current_station'] ?? ($old_d['unit'] ?? ($old_d['current_location'] ?? ($req['range_name'] ? ($req['range_name'] . ' Range') : 'Central HQ')));
                                        $target_unit  = $new_d['target_unit'] ?? 'Unassigned Target';
                                        $reason_text  = $new_d['reason'] ?? 'No justification provided.';
                                        $emp_id       = intval($req['record_id']);
                                        $approval_id  = intval($req['id']);
                                        $req_status   = $req['status'];
                                    ?>
                                        <tr id="transfer-row-<?= $approval_id ?>" data-status="<?= $req_status ?>">
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <div class="avatar rounded-circle bg-light border text-dark d-flex align-items-center justify-content-center me-2 fw-bold" style="width: 38px; height: 38px; font-size: 13px;">
                                                        <?= strtoupper(substr($req['target_name'], 0, 1)) ?>
                                                    </div>
                                                    <div>
                                                        <div class="fw-bold text-dark"><?= htmlspecialchars($req['target_name']) ?></div>
                                                        <small class="text-muted d-block" style="font-size: 11px;">
                                                            <i class="bi bi-card-text me-1"></i><?= htmlspecialchars($old_d['service_number'] ?? ($old_d['emp_id'] ?? 'N/A')) ?>
                                                            <?php if (!empty($old_d['designation'])): ?>
                                                                • <?= htmlspecialchars($old_d['designation']) ?>
                                                            <?php endif; ?>
                                                        </small>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <div class="small">
                                                    <div class="mb-1">
                                                        <span class="text-muted" style="font-size: 10px;">FROM:</span>
                                                        <span class="station-badge-from" title="Current Station">
                                                            <i class="bi bi-geo-alt me-1"></i><?= htmlspecialchars($from_station) ?>
                                                        </span>
                                                    </div>
                                                    <div>
                                                        <span class="text-muted" style="font-size: 10px;">TO:</span>
                                                        <span class="station-badge-to" title="Requested Target Station">
                                                            <i class="bi bi-geo-alt-fill me-1"></i><?= htmlspecialchars($target_unit) ?>
                                                        </span>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <div class="small text-muted" style="max-width: 200px; font-size: 12px;" title="<?= htmlspecialchars($reason_text) ?>">
                                                    <i class="bi bi-chat-left-quote me-1 text-primary"></i>
                                                    "<?= htmlspecialchars(mb_strimwidth($reason_text, 0, 75, '...')) ?>"
                                                </div>
                                                <small class="text-muted" style="font-size: 10px;">
                                                    <i class="bi bi-clock me-1"></i><?= $req['time_ago'] ?>
                                                </small>
                                            </td>
                                            <td>
                                                <div class="small">
                                                    <div class="fw-semibold text-dark"><?= htmlspecialchars($req['requester_name']) ?></div>
                                                    <small class="text-muted d-block" style="font-size: 11px;">
                                                        <?= ucwords(str_replace('_', ' ', $req['requester_role'])) ?>
                                                        <?php if (!empty($req['range_name'])): ?>
                                                            • <?= htmlspecialchars($req['range_name']) ?>
                                                        <?php endif; ?>
                                                    </small>
                                                </div>
                                            </td>
                                            <td>
                                                <?php if ($req_status === 'pending'): ?>
                                                    <span class="badge bg-warning text-dark border border-warning-subtle rounded-pill px-2 py-1">
                                                        <i class="bi bi-hourglass-split me-1"></i>Pending Review
                                                    </span>
                                                <?php elseif ($req_status === 'approved'): ?>
                                                    <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill px-2 py-1">
                                                        <i class="bi bi-check-circle-fill me-1"></i>Approved &amp; Executed
                                                    </span>
                                                <?php elseif ($req_status === 'rejected'): ?>
                                                    <span class="badge bg-danger-subtle text-danger border border-danger-subtle rounded-pill px-2 py-1">
                                                        <i class="bi bi-x-circle-fill me-1"></i>Rejected
                                                    </span>
                                                    <?php if (!empty($req['rejection_reason'])): ?>
                                                        <small class="text-muted d-block mt-1" style="font-size: 10px;">Reason: <?= htmlspecialchars($req['rejection_reason']) ?></small>
                                                    <?php endif; ?>
                                                <?php endif; ?>
                                            </td>
                                            <td class="text-center" id="action-cell-<?= $approval_id ?>">
                                                <?php if ($req_status === 'pending'): ?>
                                                    <div class="d-flex flex-column gap-1">
                                                        <button type="button" class="btn btn-success btn-sm py-1 px-2 d-flex align-items-center justify-content-center gap-1 shadow-sm fw-semibold"
                                                                title="Approve station change and update live records"
                                                                onclick="authorizeTransfer(<?= $approval_id ?>, '<?= htmlspecialchars(addslashes($req['target_name'])) ?>', '<?= htmlspecialchars(addslashes($target_unit)) ?>')">
                                                            <i class="bi bi-check-circle-fill"></i> Approve Station Change
                                                        </button>
                                                        <button type="button" class="btn btn-outline-danger btn-sm py-1 px-2 d-flex align-items-center justify-content-center gap-1"
                                                                title="Reject station change with justification"
                                                                onclick="rejectTransfer(<?= $approval_id ?>, '<?= htmlspecialchars(addslashes($req['target_name'])) ?>', '<?= htmlspecialchars(addslashes($target_unit)) ?>')">
                                                            <i class="bi bi-x-circle-fill"></i> Reject Station Change
                                                        </button>
                                                    </div>
                                                <?php else: ?>
                                                    <span class="text-muted small fst-italic">
                                                        Processed on <?= date('M d, Y', strtotime($req['reviewed_at'] ?? $req['created_at'])) ?>
                                                    </span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right Column: Live Automated Notifications Feed -->
            <div class="col-xl-4 col-lg-5">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-header bg-white py-3 border-bottom d-flex align-items-center justify-content-between">
                        <h6 class="fw-bold mb-0 text-dark d-flex align-items-center gap-2">
                            <i class="bi bi-bell-fill text-danger"></i>
                            Automated Transfer Alerts Feed
                        </h6>
                        <span class="badge bg-danger rounded-pill"><?= count($transfer_notifications) ?> Alerts</span>
                    </div>
                    <div class="card-body p-3" style="max-height: 650px; overflow-y: auto;">
                        <p class="text-muted small mb-3">
                            Direct in-app dispatch notifications generated when Veterinary Surgeons or Branch Heads submit employee station change requests.
                        </p>

                        <?php if (!empty($transfer_notifications)): ?>
                            <div class="d-flex flex-column gap-2">
                                <?php foreach ($transfer_notifications as $notif): 
                                    $is_unread = ($notif['is_read'] == 0);
                                ?>
                                    <div class="card border shadow-none p-3 notification-item-card <?= $is_unread ? 'unread' : '' ?>">
                                        <div class="d-flex justify-content-between align-items-start mb-1">
                                            <span class="badge <?= $is_unread ? 'bg-danger' : 'bg-secondary' ?>" style="font-size: 10px;">
                                                <?= $is_unread ? 'New Transfer Request' : 'Processed Alert' ?>
                                            </span>
                                            <small class="text-muted" style="font-size: 11px;">
                                                <i class="bi bi-clock me-1"></i><?= $notif['time_ago'] ?>
                                            </small>
                                        </div>
                                        <h6 class="fw-bold text-dark small mb-1"><?= htmlspecialchars($notif['title']) ?></h6>
                                        <p class="text-muted small mb-2" style="font-size: 11.5px; line-height: 1.4;">
                                            <?= htmlspecialchars($notif['message']) ?>
                                        </p>
                                        <div class="d-flex justify-content-between align-items-center pt-1 border-top" style="font-size: 11px;">
                                            <span class="text-muted"><?= date('M d, Y h:i A', strtotime($notif['created_at'])) ?></span>
                                            <?php if ($is_unread): ?>
                                                <button type="button" class="btn btn-link btn-sm p-0 text-decoration-none text-danger" onclick="markRead(<?= $notif['id'] ?>, this)">
                                                    Mark as read
                                                </button>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <div class="text-center py-5 text-muted">
                                <i class="bi bi-bell-slash fs-1 d-block mb-2 text-muted opacity-50"></i>
                                <p class="small mb-0">No active transfer notifications received.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

    </main>
</div>

<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script src="../../../assets/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
<script src="../../../assets/js/sweetalert2.all.min.js"></script>

<script>
    var transferTable;

    $(document).ready(function() {
        // Initialize DataTable
        transferTable = $('#transferManagementTable').DataTable({
            "pageLength": 10,
            "order": [],
            "language": {
                "search": "",
                "searchPlaceholder": "Search by officer, station, submitter, reason..."
            }
        });

        // Default to filtering Pending requests
        applyStatusFilter('pending');

        // Tab Filter Buttons
        $('#transferTabButtonGroup .filter-tab-btn').on('click', function() {
            $('#transferTabButtonGroup .filter-tab-btn').removeClass('active');
            $(this).addClass('active');

            var filter = $(this).data('filter');
            applyStatusFilter(filter);
        });
    });

    function applyStatusFilter(status) {
        if (status === 'all') {
            transferTable.column(4).search('').draw();
        } else if (status === 'pending') {
            transferTable.column(4).search('Pending').draw();
        } else if (status === 'approved') {
            transferTable.column(4).search('Approved').draw();
        } else if (status === 'rejected') {
            transferTable.column(4).search('Rejected').draw();
        }
    }

    // Direct Action: Authorize Transfer
    function authorizeTransfer(approvalId, officerName, targetStation) {
        Swal.fire({
            title: 'Authorize Station Change?',
            html: '<p class="mb-2">Are you sure you want to approve the workstation transfer for <strong>' + officerName + '</strong>?</p>' +
                  '<div class="p-2 bg-light border rounded text-start small">' +
                  '• <strong>Target Station:</strong> ' + targetStation + '<br>' +
                  '• <strong>Effect:</strong> Officer active station and unit records will be updated immediately in the Department MIS.' +
                  '</div>',
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#198754',
            cancelButtonColor: '#6c757d',
            confirmButtonText: '<i class="bi bi-check-circle-fill me-1"></i> Yes, Authorize Station Change',
            cancelButtonText: 'Cancel'
        }).then((result) => {
            if (result.isConfirmed) {
                Swal.fire({
                    title: 'Processing Authorization...',
                    text: 'Updating live records and dispatching alerts...',
                    allowOutsideClick: false,
                    didOpen: () => {
                        Swal.showLoading();
                    }
                });

                $.ajax({
                    url: 'processors/process_transfer.php',
                    type: 'POST',
                    data: {
                        action: 'approve',
                        id: approvalId
                    },
                    dataType: 'json',
                    success: function(resp) {
                        if (resp && resp.success) {
                            Swal.fire({
                                icon: 'success',
                                title: 'Transfer Authorized!',
                                text: resp.message || 'The officer station records have been updated successfully.',
                                confirmButtonColor: '#500707'
                            }).then(() => {
                                location.reload();
                            });
                        } else {
                            Swal.fire('Authorization Error', resp ? resp.message : 'Action failed. Please try again.', 'error');
                        }
                    },
                    error: function(xhr) {
                        Swal.fire('Server Error', 'Failed to communicate with authorization service.', 'error');
                    }
                });
            }
        });
    }

    // Direct Action: Reject Transfer
    function rejectTransfer(approvalId, officerName, targetStation) {
        Swal.fire({
            title: 'Reject Transfer Request?',
            html: '<p class="mb-2">Please provide the official administrative reason for rejecting the transfer for <strong>' + officerName + '</strong>:</p>',
            input: 'textarea',
            inputPlaceholder: 'e.g. Inadequate clinical coverage in current range / Transfer delayed until annual review...',
            inputAttributes: {
                'rows': 3
            },
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc3545',
            cancelButtonColor: '#6c757d',
            confirmButtonText: '<i class="bi bi-x-circle-fill me-1"></i> Confirm Rejection',
            cancelButtonText: 'Cancel',
            inputValidator: (value) => {
                if (!value || !value.trim()) {
                    return 'An official rejection justification is required.';
                }
            }
        }).then((result) => {
            if (result.isConfirmed) {
                var reason = result.value.trim();

                Swal.fire({
                    title: 'Processing Rejection...',
                    text: 'Recording administrative decision and notifying requester...',
                    allowOutsideClick: false,
                    didOpen: () => {
                        Swal.showLoading();
                    }
                });

                $.ajax({
                    url: 'processors/process_transfer.php',
                    type: 'POST',
                    data: {
                        action: 'reject',
                        id: approvalId,
                        reason: reason
                    },
                    dataType: 'json',
                    success: function(resp) {
                        if (resp && resp.success) {
                            Swal.fire({
                                icon: 'info',
                                title: 'Transfer Request Rejected',
                                text: resp.message || 'Rejection reason logged and notification sent.',
                                confirmButtonColor: '#500707'
                            }).then(() => {
                                location.reload();
                            });
                        } else {
                            Swal.fire('Error', resp ? resp.message : 'Action failed.', 'error');
                        }
                    },
                    error: function() {
                        Swal.fire('Server Error', 'Failed to connect to transfer service.', 'error');
                    }
                });
            }
        });
    }

    // Quick Mark Notification Read
    function markRead(notifId, btnElem) {
        $.ajax({
            url: '../../../includes/notification_action.php',
            type: 'POST',
            data: { action: 'mark_read', id: notifId },
            success: function() {
                var card = $(btnElem).closest('.notification-item-card');
                card.removeClass('unread').find('.badge').removeClass('bg-danger').addClass('bg-secondary').text('Processed Alert');
                $(btnElem).remove();
            }
        });
    }
</script>

<?php require_once '../../../includes/footer.php'; ?>
