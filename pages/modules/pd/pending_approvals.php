<?php
/**
 * pages/modules/pd/pending_approvals.php
 * Provincial Director Approval Queue for Staged HR & Inventory Edits
 */

require_once '../../../includes/header.php';
require_once '../../../includes/approval_helper.php';

$allowed_roles = ['provincial_director', 'deputy_director_hq_1', 'deputy_director_hq_2'];
if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], $allowed_roles)) {
    echo "<script>window.location.href = '../../../dashboard.php';</script>";
    exit();
}

$pending_all       = get_pending_approvals($mysqli);
$count_all         = count($pending_all);
$count_hr          = get_pending_approvals_count($mysqli, 'hr');
$count_inventory   = get_pending_approvals_count($mysqli, 'inventory');
?>

<?php require_once '../../../includes/sidebar.php'; ?>

<style>
    .diff-old-val {
        background-color: #fee2e2;
        color: #991b1b;
        padding: 4px 8px;
        border-radius: 6px;
        font-family: monospace;
        font-size: 13px;
        display: inline-block;
        word-break: break-all;
    }
    .diff-new-val {
        background-color: #dcfce7;
        color: #166534;
        padding: 4px 8px;
        border-radius: 6px;
        font-family: monospace;
        font-size: 13px;
        font-weight: bold;
        display: inline-block;
        word-break: break-all;
    }
    .badge-hr {
        background-color: #e0e7ff;
        color: #3730a3;
    }
    .badge-inventory {
        background-color: #fef3c7;
        color: #92400e;
    }
</style>

<div id="layoutSidenav_content">
    <main class="container-fluid px-4 py-4">
        <!-- Page Title Header -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2 class="h3 fw-bold mb-1" style="color: #370709;">
                    <i class="bi bi-shield-check me-2"></i>Pending Approvals Queue
                </h2>
                <p class="text-muted small mb-0">
                    Review and authorize staged Human Resources & Inventory modifications before they take effect in live records.
                </p>
            </div>
            <div>
                <button class="btn btn-outline-secondary btn-sm" onclick="location.reload();">
                    <i class="bi bi-arrow-clockwise me-1"></i> Refresh Queue
                </button>
            </div>
        </div>

        <!-- Metrics Overview -->
        <div class="row g-3 mb-4">
            <div class="col-md-4">
                <div class="card border-0 shadow-sm" style="border-left: 4px solid #500707 !important;">
                    <div class="card-body p-3">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <small class="text-muted text-uppercase fw-semibold" style="font-size: 11px;">Total Pending Requests</small>
                                <h3 class="fw-bold text-dark mb-0" id="statTotal"><?= $count_all ?></h3>
                            </div>
                            <div class="rounded-circle p-3 text-light" style="background-color: #500707;">
                                <i class="bi bi-clock-history fs-4"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card border-0 shadow-sm" style="border-left: 4px solid #3730a3 !important;">
                    <div class="card-body p-3">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <small class="text-muted text-uppercase fw-semibold" style="font-size: 11px;">HR Modifications</small>
                                <h3 class="fw-bold mb-0 text-primary" id="statHr"><?= $count_hr ?></h3>
                            </div>
                            <div class="rounded-circle p-3 text-light" style="background-color: #3730a3;">
                                <i class="bi bi-person-gear fs-4"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card border-0 shadow-sm" style="border-left: 4px solid #b45309 !important;">
                    <div class="card-body p-3">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <small class="text-muted text-uppercase fw-semibold" style="font-size: 11px;">Inventory Modifications</small>
                                <h3 class="fw-bold mb-0 text-warning" id="statInventory"><?= $count_inventory ?></h3>
                            </div>
                            <div class="rounded-circle p-3 text-light" style="background-color: #b45309;">
                                <i class="bi bi-boxes fs-4"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filter Tabs & Main Table -->
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white py-3">
                <ul class="nav nav-pills card-header-pills" id="approvalTabs" role="tablist">
                    <li class="nav-link-item">
                        <button class="nav-link active filter-btn" data-filter="all">
                            All Requests <span class="badge bg-secondary ms-1"><?= $count_all ?></span>
                        </button>
                    </li>
                    <li class="nav-link-item ms-2">
                        <button class="nav-link filter-btn" data-filter="hr">
                            <i class="bi bi-people me-1"></i>Human Resources <span class="badge bg-primary ms-1"><?= $count_hr ?></span>
                        </button>
                    </li>
                    <li class="nav-link-item ms-2">
                        <button class="nav-link filter-btn" data-filter="inventory">
                            <i class="bi bi-box-seam me-1"></i>Inventory <span class="badge bg-warning text-dark ms-1"><?= $count_inventory ?></span>
                        </button>
                    </li>
                </ul>
            </div>

            <div class="card-body p-0">
                <?php if (empty($pending_all)): ?>
                    <div class="text-center py-5" id="emptyStateContainer">
                        <i class="bi bi-check2-circle fs-1 text-success opacity-75 d-block mb-3"></i>
                        <h5 class="fw-bold text-dark">Queue is All Clear!</h5>
                        <p class="text-muted small">No pending modifications requiring authorization at this time.</p>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0" id="approvalsTable">
                            <thead class="bg-light">
                                <tr>
                                    <th class="ps-4">Request Details</th>
                                    <th>Target Entity</th>
                                    <th>Requested By</th>
                                    <th>Jurisdiction</th>
                                    <th>Changes</th>
                                    <th class="text-end pe-4">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($pending_all as $item): 
                                    $diff_count = count($item['diff'] ?? []);
                                    $diff_json = htmlspecialchars(json_encode($item['diff'] ?? []), ENT_QUOTES, 'UTF-8');
                                ?>
                                    <tr id="row-<?= $item['id'] ?>" class="approval-row" data-module="<?= htmlspecialchars($item['module']) ?>">
                                        <td class="ps-4">
                                            <div class="d-flex align-items-center">
                                                <span class="badge <?= $item['module'] === 'hr' ? 'badge-hr' : 'badge-inventory' ?> me-2 px-2 py-1">
                                                    <?= strtoupper($item['module']) ?>
                                                </span>
                                                <div>
                                                    <small class="text-muted d-block" style="font-size: 11px;">#REQ-<?= str_pad($item['id'], 4, '0', STR_PAD_LEFT) ?></small>
                                                    <span class="fw-semibold text-dark small"><?= ucwords(str_replace('_', ' ', $item['record_type'])) ?></span>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <strong class="text-dark d-block"><?= htmlspecialchars($item['target_name']) ?></strong>
                                            <small class="text-muted" style="font-size: 11px;">Record ID: #<?= $item['record_id'] ?></small>
                                        </td>
                                        <td>
                                            <div class="fw-bold text-dark small"><?= htmlspecialchars($item['requester_name']) ?></div>
                                            <span class="badge bg-light text-secondary border" style="font-size: 10px;">
                                                <?= ucwords(str_replace('_', ' ', $item['requester_role'])) ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div class="small text-dark">
                                                <?= !empty($item['range_name']) ? htmlspecialchars($item['range_name']) . ' Range' : 'General Office' ?>
                                            </div>
                                            <small class="text-muted"><?= htmlspecialchars($item['district_name'] ?? 'Provincial HQ') ?></small>
                                        </td>
                                        <td>
                                            <button type="button" class="btn btn-sm btn-outline-info view-diff-btn py-1 px-2"
                                                    data-id="<?= $item['id'] ?>"
                                                    data-target="<?= htmlspecialchars($item['target_name']) ?>"
                                                    data-requester="<?= htmlspecialchars($item['requester_name']) ?> (<?= ucwords(str_replace('_', ' ', $item['requester_role'])) ?>)"
                                                    data-diff='<?= $diff_json ?>'>
                                                <i class="bi bi-eye me-1"></i><?= $diff_count ?> field<?= $diff_count === 1 ? '' : 's' ?> modified
                                            </button>
                                        </td>
                                        <td class="text-end pe-4">
                                            <button type="button" class="btn btn-sm btn-success px-3 me-1 approve-btn" data-id="<?= $item['id'] ?>" data-name="<?= htmlspecialchars($item['target_name']) ?>">
                                                <i class="bi bi-check-lg me-1"></i>Approve
                                            </button>
                                            <button type="button" class="btn btn-sm btn-outline-danger px-3 reject-btn" data-id="<?= $item['id'] ?>" data-name="<?= htmlspecialchars($item['target_name']) ?>">
                                                <i class="bi bi-x-lg me-1"></i>Reject
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </main>
</div>

<!-- Comparison / Diff Modal -->
<div class="modal fade" id="diffModal" tabindex="-1" aria-labelledby="diffModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header text-light" style="background: linear-gradient(135deg, #500707 0%, #750d0d 100%);">
                <div>
                    <h5 class="modal-title fw-bold" id="diffModalLabel"><i class="bi bi-sliders me-2"></i>Proposed Modifications Diff</h5>
                    <small class="text-light-50" id="diffModalSubtitle">Review Old Values vs New Values</small>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <div class="alert alert-light border mb-3 py-2 px-3">
                    <div class="row text-muted small">
                        <div class="col-sm-6">
                            <strong>Target Record:</strong> <span id="modalTargetName" class="text-dark"></span>
                        </div>
                        <div class="col-sm-6 text-sm-end">
                            <strong>Requested By:</strong> <span id="modalRequester" class="text-dark"></span>
                        </div>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table table-bordered align-middle">
                        <thead class="bg-light">
                            <tr>
                                <th style="width: 25%;">Field Name</th>
                                <th style="width: 37.5%;" class="text-danger"><i class="bi bi-dash-circle me-1"></i>Old Value (Current)</th>
                                <th style="width: 37.5%;" class="text-success"><i class="bi bi-plus-circle me-1"></i>New Value (Proposed)</th>
                            </tr>
                        </thead>
                        <tbody id="diffModalBody">
                            <!-- Injected via JavaScript -->
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer bg-light justify-content-between">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Close</button>
                <div>
                    <button type="button" class="btn btn-outline-danger me-2" id="modalRejectBtn"><i class="bi bi-x-lg me-1"></i>Reject</button>
                    <button type="button" class="btn btn-success px-4" id="modalApproveBtn"><i class="bi bi-check-lg me-1"></i>Approve & Apply</button>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once '../../../includes/footer.php'; ?>

<script>
$(document).ready(function() {
    let currentModalId = null;
    let currentModalTargetName = '';

    // Filter Tabs
    $('.filter-btn').on('click', function() {
        $('.filter-btn').removeClass('active');
        $(this).addClass('active');

        const filter = $(this).data('filter');
        if (filter === 'all') {
            $('.approval-row').show();
        } else {
            $('.approval-row').hide();
            $('.approval-row[data-module="' + filter + '"]').show();
        }
    });

    // View Diff Modal
    $(document).on('click', '.view-diff-btn', function() {
        const btn = $(this);
        currentModalId = btn.data('id');
        currentModalTargetName = btn.data('target');
        const requester = btn.data('requester');
        const diffData = btn.data('diff');

        $('#modalTargetName').text(currentModalTargetName);
        $('#modalRequester').text(requester);

        let rowsHtml = '';
        if (typeof diffData === 'object' && Object.keys(diffData).length > 0) {
            for (const [key, item] of Object.entries(diffData)) {
                rowsHtml += `
                    <tr>
                        <td class="fw-bold text-secondary small">${item.label}</td>
                        <td><span class="diff-old-val">${escapeHtml(item.old)}</span></td>
                        <td><span class="diff-new-val">${escapeHtml(item.new)}</span></td>
                    </tr>
                `;
            }
        } else {
            rowsHtml = `<tr><td colspan="3" class="text-center text-muted py-3">No field differences detected.</td></tr>`;
        }

        $('#diffModalBody').html(rowsHtml);
        $('#diffModal').modal('show');
    });

    // Approve from modal
    $('#modalApproveBtn').on('click', function() {
        if (currentModalId) {
            $('#diffModal').modal('hide');
            executeApprove(currentModalId, currentModalTargetName);
        }
    });

    // Reject from modal
    $('#modalRejectBtn').on('click', function() {
        if (currentModalId) {
            $('#diffModal').modal('hide');
            executeReject(currentModalId, currentModalTargetName);
        }
    });

    // Approve from table row
    $(document).on('click', '.approve-btn', function() {
        const id = $(this).data('id');
        const name = $(this).data('name');
        executeApprove(id, name);
    });

    // Reject from table row
    $(document).on('click', '.reject-btn', function() {
        const id = $(this).data('id');
        const name = $(this).data('name');
        executeReject(id, name);
    });

    function executeApprove(id, name) {
        Swal.fire({
            title: 'Approve Modifications?',
            text: `Are you sure you want to authorize and officially apply changes for '${name}'?`,
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#198754',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Yes, Authorize Changes',
            cancelButtonText: 'Cancel'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: 'processors/process_approval.php',
                    type: 'POST',
                    data: { action: 'approve', id: id },
                    dataType: 'json',
                    success: function(resp) {
                        if (resp && resp.success) {
                            Swal.fire({
                                icon: 'success',
                                title: 'Authorized!',
                                text: resp.message,
                                timer: 2000,
                                showConfirmButton: false
                            });
                            removeRowAndUpdateCounts(id, resp.pending_count);
                        } else {
                            Swal.fire('Approval Error', resp ? resp.message : 'Action failed', 'error');
                        }
                    },
                    error: function() {
                        Swal.fire('Server Error', 'Communication with server failed.', 'error');
                    }
                });
            }
        });
    }

    function executeReject(id, name) {
        Swal.fire({
            title: 'Reject Modifications?',
            text: `Please provide a reason for rejecting the proposed changes for '${name}':`,
            input: 'text',
            inputPlaceholder: 'e.g. Invalid quantity specified / Incorrect designation...',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc3545',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Reject Modifications',
            cancelButtonText: 'Cancel',
            inputValidator: (value) => {
                if (!value) {
                    return 'Please enter a reason for rejection!';
                }
            }
        }).then((result) => {
            if (result.isConfirmed) {
                const reason = result.value;
                $.ajax({
                    url: 'processors/process_approval.php',
                    type: 'POST',
                    data: { action: 'reject', id: id, reason: reason },
                    dataType: 'json',
                    success: function(resp) {
                        if (resp && resp.success) {
                            Swal.fire({
                                icon: 'info',
                                title: 'Changes Discarded',
                                text: resp.message,
                                timer: 2000,
                                showConfirmButton: false
                            });
                            removeRowAndUpdateCounts(id, resp.pending_count);
                        } else {
                            Swal.fire('Rejection Error', resp ? resp.message : 'Action failed', 'error');
                        }
                    },
                    error: function() {
                        Swal.fire('Server Error', 'Communication with server failed.', 'error');
                    }
                });
            }
        });
    }

    function removeRowAndUpdateCounts(id, remainingCount) {
        $('#row-' + id).fadeOut(300, function() {
            $(this).remove();
            if ($('.approval-row').length === 0) {
                location.reload();
            }
        });
        if (typeof remainingCount !== 'undefined') {
            $('#statTotal').text(remainingCount);
        }
    }

    function escapeHtml(text) {
        if (text === null || text === undefined) return '';
        return String(text)
            .replace(/&/g, "&amp;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;")
            .replace(/"/g, "&quot;")
            .replace(/'/g, "&#039;");
    }
});
</script>
