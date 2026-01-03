<?php
require_once '../../../includes/header.php';
if ($_SESSION['role'] !== 'provincial_director') die("Access denied");

// Demo requests
$vehicle_requests = [
    ['req_no' => 'VREQ-2025-045', 'officer' => 'District DD - Batticaloa', 'vehicle' => 'Double Cab DC-1234', 'purpose' => 'Field visit to dairy farms', 'date' => '2025-12-28', 'status' => 'Pending', 'requested_date' => '2025-12-20'],
    ['req_no' => 'VREQ-2025-044', 'officer' => 'SMS Officer', 'vehicle' => 'Van VN-5678', 'purpose' => 'Disease investigation - Trincomalee', 'date' => '2025-12-26', 'status' => 'Approved', 'requested_date' => '2025-12-18'],
    ['req_no' => 'VREQ-2025-043', 'officer' => 'Farms DD', 'vehicle' => 'Lorry LR-9012', 'purpose' => 'Fodder transport', 'date' => '2025-12-24', 'status' => 'Pending', 'requested_date' => '2025-12-19'],
];

$pending_count = count(array_filter($vehicle_requests, fn($r) => $r['status'] === 'Pending'));
$approved_count = count(array_filter($vehicle_requests, fn($r) => $r['status'] === 'Approved'));
?>

<?php require_once '../../../includes/sidebar.php'; ?>

<style>
.stats-card {
    border-left: 4px solid;
    transition: transform 0.2s, box-shadow 0.2s;
}
.stats-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0,0,0,0.1);
}
.stats-card.pending { border-left-color: #ffc107; }
.stats-card.approved { border-left-color: #28a745; }
.stats-card.total { border-left-color: #820100; }

.action-buttons .btn {
    min-width: 90px;
}

.table thead th {
    background-color: #f8f9fa;
    font-weight: 600;
    border-bottom: 2px solid #dee2e6;
    color: #495057;
}

.request-details {
    font-size: 0.9rem;
}

.filter-section {
    background-color: #f8f9fa;
    padding: 1rem;
    border-radius: 0.25rem;
    margin-bottom: 1.5rem;
}

.view-btn {
    padding: 0.25rem 0.5rem;
    font-size: 0.875rem;
}

@media print {
    .no-print { display: none; }
}
</style>

<div id="layoutSidenav_content">
    <main class="container-fluid px-4 pt-4">
        <!-- Page Header -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2 class="mb-1">Vehicle Movement Approvals</h2>
                <p class="text-muted mb-0">Review and approve inter-district vehicle movement requests</p>
            </div>

        </div>

        <!-- Statistics Cards -->
        <div class="row mb-4">
            <div class="col-md-4 mb-3">
                <div class="card stats-card pending">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <p class="text-muted mb-1">Pending Requests</p>
                                <h3 class="mb-0"><?= $pending_count ?></h3>
                            </div>
                            <div class="text-warning" style="font-size: 2rem;">
                                <i class="fas fa-clock"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4 mb-3">
                <div class="card stats-card approved">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <p class="text-muted mb-1">Approved</p>
                                <h3 class="mb-0"><?= $approved_count ?></h3>
                            </div>
                            <div class="text-success" style="font-size: 2rem;">
                                <i class="fas fa-check-circle"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4 mb-3">
                <div class="card stats-card total">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <p class="text-muted mb-1">Total Requests</p>
                                <h3 class="mb-0"><?= count($vehicle_requests) ?></h3>
                            </div>
                            <div style="color: #820100; font-size: 2rem;">
                                <i class="fas fa-list"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filter Section -->
        <div class="filter-section no-print">
            <div class="row g-3">
                <div class="col-md-3">
                    <label class="form-label small">Status</label>
                    <select class="form-select form-select-sm">
                        <option value="">All Status</option>
                        <option value="pending">Pending</option>
                        <option value="approved">Approved</option>
                        <option value="rejected">Rejected</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label small">Vehicle Type</label>
                    <select class="form-select form-select-sm">
                        <option value="">All Types</option>
                        <option value="cab">Double Cab</option>
                        <option value="van">Van</option>
                        <option value="lorry">Lorry</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label small">Date From</label>
                    <input type="date" class="form-control form-control-sm">
                </div>
                <div class="col-md-3">
                    <label class="form-label small">Date To</label>
                    <input type="date" class="form-control form-control-sm">
                </div>
            </div>
        </div>

        <!-- Requests Table -->
        <div class="card shadow-sm">
            <div class="card-header text-white d-flex justify-content-between align-items-center" style="background-color:#820100;">
                <h5 class="mb-0 text-white" style="color: white !important;">Vehicle Movement Requests</h5>
                <span class="badge bg-light text-dark"><?= count($vehicle_requests) ?> Records</span>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th style="width: 10%;">Request No.</th>
                                <th style="width: 15%;">Requesting Officer</th>
                                <th style="width: 12%;">Vehicle</th>
                                <th style="width: 25%;">Purpose</th>
                                <th style="width: 10%;">Travel Date</th>
                                <th style="width: 10%;">Requested On</th>
                                <th style="width: 8%;">Status</th>
                                <th style="width: 10%;" class="no-print">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($vehicle_requests as $req): ?>
                            <tr>
                                <td>
                                    <strong class="text-primary"><?= $req['req_no'] ?></strong>
                                </td>
                                <td>
                                    <div class="request-details">
                                        <i class="fas fa-user me-1"></i><?= htmlspecialchars($req['officer']) ?>
                                    </div>
                                </td>
                                <td>
                                    <div class="request-details">
                                        <i class="fas fa-car me-1"></i><?= $req['vehicle'] ?>
                                    </div>
                                </td>
                                <td>
                                    <div class="request-details text-muted">
                                        <?= htmlspecialchars($req['purpose']) ?>
                                    </div>
                                </td>
                                <td>
                                    <div class="request-details">
                                        <i class="fas fa-calendar me-1"></i><?= date('d M Y', strtotime($req['date'])) ?>
                                    </div>
                                </td>
                                <td>
                                    <small class="text-muted"><?= date('d M Y', strtotime($req['requested_date'])) ?></small>
                                </td>
                                <td>
                                    <span class="badge bg-<?= $req['status'] === 'Approved' ? 'success' : ($req['status'] === 'Rejected' ? 'danger' : 'warning') ?>">
                                        <?= $req['status'] ?>
                                    </span>
                                </td>
                                <td class="no-print">
                                    <div class="action-buttons">
                                        <?php if ($req['status'] === 'Pending'): ?>
                                        <div class="btn-group-vertical w-100" role="group">
                                            <button class="btn btn-sm btn-success mb-1" 
                                                    onclick="handleApproval('<?= $req['req_no'] ?>', 'approve')"
                                                    title="Approve Request">
                                                <i class="fas fa-check"></i> Approve
                                            </button>
                                            <button class="btn btn-sm btn-danger" 
                                                    onclick="handleApproval('<?= $req['req_no'] ?>', 'reject')"
                                                    title="Reject Request">
                                                <i class="fas fa-times"></i> Reject
                                            </button>
                                        </div>
                                        <?php else: ?>
                                        <button class="btn btn-sm btn-outline-secondary view-btn" 
                                                onclick="viewDetails('<?= $req['req_no'] ?>')">
                                            <i class="fas fa-eye"></i> View
                                        </button>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Approval Modal -->
        <div class="modal fade" id="approvalModal" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="modalTitle">Confirm Action</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <p id="modalMessage"></p>
                        <div class="mb-3">
                            <label for="remarks" class="form-label">Remarks (Optional)</label>
                            <textarea class="form-control" id="remarks" rows="3" 
                                      placeholder="Enter any additional comments..."></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="button" class="btn" id="confirmBtn" onclick="confirmAction()">Confirm</button>
                    </div>
                </div>
            </div>
        </div>

    </main>
</div>

<script>
let currentRequest = null;
let currentAction = null;

function handleApproval(requestNo, action) {
    currentRequest = requestNo;
    currentAction = action;
    
    const modal = new bootstrap.Modal(document.getElementById('approvalModal'));
    const modalTitle = document.getElementById('modalTitle');
    const modalMessage = document.getElementById('modalMessage');
    const confirmBtn = document.getElementById('confirmBtn');
    
    if (action === 'approve') {
        modalTitle.textContent = 'Approve Vehicle Movement';
        modalMessage.textContent = `Are you sure you want to approve request ${requestNo}?`;
        confirmBtn.className = 'btn btn-success';
        confirmBtn.innerHTML = '<i class="fas fa-check"></i> Approve';
    } else {
        modalTitle.textContent = 'Reject Vehicle Movement';
        modalMessage.textContent = `Are you sure you want to reject request ${requestNo}?`;
        confirmBtn.className = 'btn btn-danger';
        confirmBtn.innerHTML = '<i class="fas fa-times"></i> Reject';
    }
    
    modal.show();
}

function confirmAction() {
    const remarks = document.getElementById('remarks').value;
    
    // Here you would send the approval/rejection to your backend
    console.log(`Action: ${currentAction}, Request: ${currentRequest}, Remarks: ${remarks}`);
    
    // Show success message
    alert(`Request ${currentRequest} has been ${currentAction}d successfully!`);
    
    // Close modal and reload page
    bootstrap.Modal.getInstance(document.getElementById('approvalModal')).hide();
    location.reload();
}

function viewDetails(requestNo) {
    alert(`View details for ${requestNo}`);
    // Implement view details functionality
}
</script>

<?php require_once '../../../includes/footer.php'; ?>