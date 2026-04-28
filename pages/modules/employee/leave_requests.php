<?php
session_start();
require_once '../../../config/db_connect.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'employee') {
    header("Location: ../../../index.php");
    exit();
}

require_once '../../../includes/header.php';
require_once '../../../includes/sidebar.php';
?>

<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.css" rel="stylesheet">
<!-- DataTables CSS -->
<link href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap5.min.css" rel="stylesheet">
<link href="https://cdn.datatables.net/responsive/2.5.0/css/responsive.bootstrap5.min.css" rel="stylesheet">

<style>
    .modal-backdrop {
        z-index: 1040;
    }
    
    .priority-high {
        background-color: #dc3545;
        color: white;
        padding: 4px 12px;
        border-radius: 20px;
        font-size: 0.75rem;
        font-weight: 600;
        display: inline-block;
    }
    
    .priority-medium {
        background-color: #ffc107;
        color: #332b00;
        padding: 4px 12px;
        border-radius: 20px;
        font-size: 0.75rem;
        font-weight: 600;
        display: inline-block;
    }
    
    .priority-low {
        background-color: #198754;
        color: white;
        padding: 4px 12px;
        border-radius: 20px;
        font-size: 0.75rem;
        font-weight: 600;
        display: inline-block;
    }
    
    .status-pending {
        background-color: #ffc107;
        color: #332b00;
        padding: 4px 12px;
        border-radius: 20px;
        font-size: 0.75rem;
        font-weight: 600;
        display: inline-block;
    }
    
    .status-approved {
        background-color: #198754;
        color: white;
        padding: 4px 12px;
        border-radius: 20px;
        font-size: 0.75rem;
        font-weight: 600;
        display: inline-block;
    }
    
    .status-rejected {
        background-color: #dc3545;
        color: white;
        padding: 4px 12px;
        border-radius: 20px;
        font-size: 0.75rem;
        font-weight: 600;
        display: inline-block;
    }
    
    .action-icon {
        font-size: 1.1rem;
        margin: 0 5px;
        cursor: pointer;
        transition: all 0.2s;
        display: inline-block;
    }
    
    .action-icon:hover {
        transform: scale(1.1);
    }
    
    .icon-view {
        color: #0d6efd;
    }
    
    .icon-edit {
        color: #ffc107;
    }
    
    .icon-delete {
        color: #dc3545;
    }
    
    .balance-badge {
        font-size: 0.85rem;
        font-weight: 600;
        padding: 8px 12px;
        border-radius: 8px;
    }
    
    .leave-card {
        transition: all 0.3s ease;
        cursor: pointer;
    }
    
    .leave-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 5px 15px rgba(0,0,0,0.1);
    }
</style>

<div id="layoutSidenav_content">
    <main class="container-fluid px-4 pt-4">

        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2 class="fw-bold text-dark mb-1">Employee Workspace</h2>
                <p class="text-muted mb-0">Welcome back, <?= htmlspecialchars($_SESSION['username']) ?>! Here's a quick overview of your daily activities and tasks.</p>
            </div>
            <div class="text-end d-none d-md-block">
                <div class="p-2 bg-white shadow-sm rounded border">
                    <span class="small fw-bold text-uppercase text-muted d-block" style="font-size: 0.7rem;">Current Date</span>
                    <span class="text-primary fw-bold"><i class="bi bi-calendar3 me-2"></i><?= date('l, d M Y') ?></span>
                </div>
            </div>
        </div>

        <!-- Statistics Cards -->
        <div class="row g-4 mb-4">
            <div class="col-xl-3 col-md-6">
                <div class="card border-0 shadow-sm h-100 border-start border-primary border-4">
                    <div class="card-body p-4">
                        <h6 class="text-muted small text-uppercase fw-bold">My Daily Tasks</h6>
                        <h2 class="text-primary mb-0">6</h2>
                        <small class="text-muted">Activities recorded today</small>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="card border-0 shadow-sm h-100 border-start border-success border-4">
                    <div class="card-body p-4">
                        <h6 class="text-muted small text-uppercase fw-bold">Completed Activities</h6>
                        <h2 class="text-success mb-0">14</h2>
                        <small class="text-muted">Activities completed today</small>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="card border-0 shadow-sm h-100 border-start border-warning border-4">
                    <div class="card-body p-4">
                        <h6 class="text-muted small text-uppercase fw-bold">Pending Activities</h6>
                        <h2 class="text-warning mb-0">6</h2>
                        <small class="text-muted">Activities awaiting approval</small>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="card border-0 shadow-sm h-100 border-start border-info border-4">
                    <div class="card-body p-4">
                        <h6 class="text-muted small text-uppercase fw-bold">Account Status</h6>
                        <h2 class="text-info mb-0">Active</h2>
                        <small class="text-muted">System Verified</small>
                    </div>
                </div>
            </div>
        </div>

        <!-- Leave Balance Cards -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white border-0 py-3">
                        <h5 class="fw-bold mb-0"><i class="bi bi-calendar-check me-2 text-primary"></i>My Leave Balance</h5>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-2">
                                <div class="alert alert-info text-center mb-0 leave-card">
                                    <i class="bi bi-umbrella fs-2 d-block"></i>
                                    <h5 class="fw-bold mt-2 mb-0">Casual</h5>
                                    <span class="badge bg-primary fs-6">24 days</span>
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="alert alert-success text-center mb-0 leave-card">
                                    <i class="bi bi-heart-pulse fs-2 d-block"></i>
                                    <h5 class="fw-bold mt-2 mb-0">Sick</h5>
                                    <span class="badge bg-success fs-6">24 days</span>
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="alert alert-warning text-center mb-0 leave-card">
                                    <i class="bi bi-airplane fs-2 d-block"></i>
                                    <h5 class="fw-bold mt-2 mb-0">Foreign</h5>
                                    <span class="badge bg-warning fs-6">Unlimited</span>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="alert alert-danger text-center mb-0 leave-card">
                                    <i class="bi bi-briefcase fs-2 d-block"></i>
                                    <h5 class="fw-bold mt-2 mb-0">Duty</h5>
                                    <span class="badge bg-danger fs-6">Unlimited</span>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="alert alert-secondary text-center mb-0 leave-card">
                                    <i class="bi bi-gender-female fs-2 d-block"></i>
                                    <h5 class="fw-bold mt-2 mb-0">Maternity</h5>
                                    <span class="badge bg-secondary fs-6">Unlimited</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Quick Actions Section -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white border-0 py-3">
                        <h5 class="fw-bold mb-0"><i class="bi bi-lightning-charge me-2 text-warning"></i>Quick Actions</h5>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">

                            <div class="col-md-3">
                                <button class="btn w-100 py-3 shadow-sm" style="background-color: #0d6efd; color: white;" data-bs-toggle="modal" data-bs-target="#leaveRequestModal">
                                    <i class="bi bi-calendar-plus fs-4 me-2"></i>
                                    <span class="fw-bold">Request Leave</span>
                                </button>
                            </div>

                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Data Table Section -->
        <div class="row">
            <div class="col-12">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white border-0 py-3">
                        <h5 class="fw-bold mb-0"><i class="bi bi-table me-2 text-primary"></i>My Leave Requests</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table id="leaveTable" class="table table-hover" style="width:100%">
                                <thead class="table-light">
                                    <tr>
                                        <th>From Date</th>
                                        <th>To Date</th>
                                        <th>Leave Type</th>
                                        <th>No of Days</th>
                                        <th>Reason</th>
                                        <th>Status</th>
                                        <th class="text-center">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td>2026-05-10</td>
                                        <td>2026-05-12</td>
                                        <td>Casual</td>
                                        <td>3</td>
                                        <td>Personal work</td>
                                        <td><span class="status-pending">Pending</span></td>
                                        <td class="text-center">
                                            <i class="bi bi-eye action-icon icon-view" onclick="viewLeave(this)" title="View"></i>
                                            <i class="bi bi-pencil action-icon icon-edit" onclick="editLeave(this)" title="Edit"></i>
                                            <i class="bi bi-trash action-icon icon-delete" onclick="deleteLeave(this)" title="Delete"></i>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>2026-04-20</td>
                                        <td>2026-04-22</td>
                                        <td>Sick</td>
                                        <td>3</td>
                                        <td>Fever</td>
                                        <td><span class="status-approved">Approved</span></td>
                                        <td class="text-center">
                                            <i class="bi bi-eye action-icon icon-view" onclick="viewLeave(this)" title="View"></i>
                                            <i class="bi bi-pencil action-icon icon-edit" onclick="editLeave(this)" title="Edit"></i>
                                            <i class="bi bi-trash action-icon icon-delete" onclick="deleteLeave(this)" title="Delete"></i>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>2026-06-01</td>
                                        <td>2026-06-05</td>
                                        <td>Duty</td>
                                        <td>5</td>
                                        <td>Official training</td>
                                        <td><span class="status-pending">Pending</span></td>
                                        <td class="text-center">
                                            <i class="bi bi-eye action-icon icon-view" onclick="viewLeave(this)" title="View"></i>
                                            <i class="bi bi-pencil action-icon icon-edit" onclick="editLeave(this)" title="Edit"></i>
                                            <i class="bi bi-trash action-icon icon-delete" onclick="deleteLeave(this)" title="Delete"></i>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>2026-03-15</td>
                                        <td>2026-03-20</td>
                                        <td>Maternity</td>
                                        <td>6</td>
                                        <td>Medical leave</td>
                                        <td><span class="status-approved">Approved</span></td>
                                        <td class="text-center">
                                            <i class="bi bi-eye action-icon icon-view" onclick="viewLeave(this)" title="View"></i>
                                            <i class="bi bi-pencil action-icon icon-edit" onclick="editLeave(this)" title="Edit"></i>
                                            <i class="bi bi-trash action-icon icon-delete" onclick="deleteLeave(this)" title="Delete"></i>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>2026-07-10</td>
                                        <td>2026-07-15</td>
                                        <td>Foreign</td>
                                        <td>6</td>
                                        <td>Family vacation</td>
                                        <td><span class="status-pending">Pending</span></td>
                                        <td class="text-center">
                                            <i class="bi bi-eye action-icon icon-view" onclick="viewLeave(this)" title="View"></i>
                                            <i class="bi bi-pencil action-icon icon-edit" onclick="editLeave(this)" title="Edit"></i>
                                            <i class="bi bi-trash action-icon icon-delete" onclick="deleteLeave(this)" title="Delete"></i>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>2026-04-28</td>
                                        <td>2026-04-29</td>
                                        <td>Casual</td>
                                        <td>2</td>
                                        <td>Family function</td>
                                        <td><span class="status-rejected">Rejected</span></td>
                                        <td class="text-center">
                                            <i class="bi bi-eye action-icon icon-view" onclick="viewLeave(this)" title="View"></i>
                                            <i class="bi bi-pencil action-icon icon-edit" onclick="editLeave(this)" title="Edit"></i>
                                            <i class="bi bi-trash action-icon icon-delete" onclick="deleteLeave(this)" title="Delete"></i>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>
</div>


<!-- Leave Request Modal -->
<div class="modal fade" id="leaveRequestModal" tabindex="-1" aria-labelledby="leaveRequestModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title fw-bold" id="leaveRequestModalLabel">
                    <i class="bi bi-calendar-plus me-2"></i>Request New Leave
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="leaveRequestForm" onsubmit="saveLeaveRequest(event)">
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold">From Date <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" name="from_date" id="from_date" required onchange="calculateDays()">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">To Date <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" name="to_date" id="to_date" required onchange="calculateDays()">
                        </div>
                        <div class="col-md-12">
                            <label class="form-label fw-bold">Leave Type <span class="text-danger">*</span></label>
                            <select class="form-select" name="leave_type" id="leave_type" required>
                                <option value="">Select Leave Type</option>
                                <option value="Casual">Casual Leave (24 days available)</option>
                                <option value="Sick">Sick Leave (24 days available)</option>
                                <option value="Foreign">Foreign Leave (Unlimited)</option>
                                <option value="Duty">Duty Leave (Unlimited)</option>
                                <option value="Maternity">Maternity Leave (Unlimited)</option>
                            </select>
                        </div>
                        <div class="col-md-12">
                            <label class="form-label fw-bold">No of Days</label>
                            <input type="text" class="form-control" name="no_of_days" id="no_of_days" readonly>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-bold">Reason <span class="text-danger">*</span></label>
                            <textarea class="form-control" name="reason" rows="3" placeholder="Enter reason for leave" required></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success">
                        <i class="bi bi-send me-2"></i>Submit Request
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- View Leave Modal -->
<div class="modal fade" id="viewLeaveModal" tabindex="-1" aria-labelledby="viewLeaveModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-info text-white">
                <h5 class="modal-title fw-bold" id="viewLeaveModalLabel">
                    <i class="bi bi-eye me-2"></i>Leave Request Details
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="viewLeaveContent">
                <!-- Dynamic content -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.datatables.net/responsive/2.5.0/js/dataTables.responsive.min.js"></script>

<script>
    let leaveDataTable;

    $(document).ready(function() {
        // Initialize Leave DataTable
        leaveDataTable = $('#leaveTable').DataTable({
            responsive: true,
            language: {
                search: "Search:",
                lengthMenu: "Show _MENU_ entries",
                info: "Showing _START_ to _END_ of _TOTAL_ entries",
                paginate: {
                    first: "First",
                    last: "Last",
                    next: "→",
                    previous: "←"
                }
            },
            order: [[0, 'desc']],
            columnDefs: [
                { orderable: false, targets: 6 }
            ],
            pageLength: 10
        });
    });

    // Calculate number of days between two dates
    function calculateDays() {
        const fromDate = document.getElementById('from_date').value;
        const toDate = document.getElementById('to_date').value;
        
        if (fromDate && toDate) {
            const start = new Date(fromDate);
            const end = new Date(toDate);
            const diffTime = Math.abs(end - start);
            const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24)) + 1;
            document.getElementById('no_of_days').value = diffDays + ' days';
        }
    }

    // Save Leave Request
    function saveLeaveRequest(event) {
        event.preventDefault();
        const form = document.getElementById('leaveRequestForm');
        const formData = new FormData(form);
        
        const leaveData = {
            from_date: formData.get('from_date'),
            to_date: formData.get('to_date'),
            leave_type: formData.get('leave_type'),
            no_of_days: document.getElementById('no_of_days').value,
            reason: formData.get('reason')
        };
        
        // Add new row to DataTable
        leaveDataTable.row.add([
            leaveData.from_date,
            leaveData.to_date,
            leaveData.leave_type,
            leaveData.no_of_days,
            leaveData.reason,
            '<span class="status-pending">Pending</span>',
            `<div class="text-center">
                <i class="bi bi-eye action-icon icon-view" onclick="viewLeave(this)" title="View"></i>
                <i class="bi bi-pencil action-icon icon-edit" onclick="editLeave(this)" title="Edit"></i>
                <i class="bi bi-trash action-icon icon-delete" onclick="deleteLeave(this)" title="Delete"></i>
            </div>`
        ]).draw();
        
        // Close modal and reset form
        $('#leaveRequestModal').modal('hide');
        form.reset();
        
        alert('Leave request submitted successfully!');
    }
    
    // View Leave Details
    function viewLeave(element) {
        const row = $(element).closest('tr');
        const cells = row.find('td');
        
        const content = `
            <div class="mb-3">
                <label class="fw-bold text-muted small">From Date</label>
                <p class="mb-0">${cells.eq(0).text()}</p>
            </div>
            <div class="mb-3">
                <label class="fw-bold text-muted small">To Date</label>
                <p class="mb-0">${cells.eq(1).text()}</p>
            </div>
            <div class="mb-3">
                <label class="fw-bold text-muted small">Leave Type</label>
                <p class="mb-0">${cells.eq(2).text()}</p>
            </div>
            <div class="mb-3">
                <label class="fw-bold text-muted small">No of Days</label>
                <p class="mb-0">${cells.eq(3).text()}</p>
            </div>
            <div class="mb-3">
                <label class="fw-bold text-muted small">Reason</label>
                <p class="mb-0">${cells.eq(4).text()}</p>
            </div>
            <div class="mb-3">
                <label class="fw-bold text-muted small">Status</label>
                <p class="mb-0">${cells.eq(5).text()}</p>
            </div>
        `;
        
        $('#viewLeaveContent').html(content);
        $('#viewLeaveModal').modal('show');
    }
    
    // Edit Leave Request
    function editLeave(element) {
        const row = $(element).closest('tr');
        const cells = row.find('td');
        
        const newFrom = prompt('Edit From Date (YYYY-MM-DD):', cells.eq(0).text());
        const newTo = prompt('Edit To Date (YYYY-MM-DD):', cells.eq(1).text());
        const newType = prompt('Edit Leave Type (Casual/Sick/Foreign/Duty/Maternity):', cells.eq(2).text());
        const newReason = prompt('Edit Reason:', cells.eq(4).text());
        
        if (newFrom && newTo && newType && newReason) {
            // Calculate days
            const start = new Date(newFrom);
            const end = new Date(newTo);
            const diffTime = Math.abs(end - start);
            const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24)) + 1;
            
            cells.eq(0).text(newFrom);
            cells.eq(1).text(newTo);
            cells.eq(2).text(newType);
            cells.eq(3).text(diffDays + ' days');
            cells.eq(4).text(newReason);
            
            alert('Leave request updated successfully!');
        }
    }
    
    // Delete Leave Request
    function deleteLeave(element) {
        if (confirm('Are you sure you want to delete this leave request?')) {
            const row = $(element).closest('tr');
            leaveDataTable.row(row).remove().draw();
            alert('Leave request deleted successfully!');
        }
    }
    
    // Save Daily Task
    function saveTask(event) {
        event.preventDefault();
        alert('Daily task added successfully!');
        $('#addTaskModal').modal('hide');
        document.getElementById('addTaskForm').reset();
    }
</script>

<?php require_once '../../../includes/footer.php'; ?>