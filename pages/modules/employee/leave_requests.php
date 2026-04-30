<?php
session_start();
require_once '../../../config/db_connect.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'employee') {
    header("Location: ../../../index.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Fetch current user designation
$user_query = "SELECT full_name, designation FROM users WHERE id = '$user_id'";
$user_result = $mysqli->query($user_query);
$user_info = $user_result->fetch_assoc();
$user_designation = $user_info['designation'] ?? 'Other';
$user_name = $user_info['full_name'] ?? $_SESSION['username'];

// Fetch user's leave requests
$leave_requests_query = "SELECT * FROM leave_requests WHERE user_id = '$user_id' ORDER BY created_at DESC";
$leave_requests_result = $mysqli->query($leave_requests_query);

// Leave statistics from DB
$stats_query = "SELECT 
    COUNT(*) as total,
    SUM(CASE WHEN status = 'Pending' THEN 1 ELSE 0 END) as pending,
    SUM(CASE WHEN status = 'Approved' THEN 1 ELSE 0 END) as approved,
    SUM(CASE WHEN status = 'Rejected' THEN 1 ELSE 0 END) as rejected,
    SUM(CASE WHEN status = 'Approved' THEN no_of_days ELSE 0 END) as total_approved_days
    FROM leave_requests WHERE user_id = '$user_id'";
$stats_result = $mysqli->query($stats_query);
$stats = $stats_result->fetch_assoc();
$total_leaves   = $stats['total'] ?? 0;
$pending_leaves = $stats['pending'] ?? 0;
$approved_leaves= $stats['approved'] ?? 0;
$rejected_leaves= $stats['rejected'] ?? 0;

// Fetch potential acting officers
$acting_query = "SELECT id, full_name, designation FROM users WHERE id != '$user_id' AND is_active = 1 ORDER BY full_name ASC";
$acting_result = $mysqli->query($acting_query);

// Determine Working Hours Group
$work_group = 'B'; // Default
$group_a = ['Veterinary surgeon', 'Veterinary surgeoon', 'LDO', 'DO', 'GVS'];
if (in_array($user_designation, $group_a)) {
    $work_group = 'A';
}

$work_hours = [
    'A' => [
        'mon_fri' => '8.30 AM - 3.30 PM',
        'sat' => '8.30 AM - 12.15 PM'
    ],
    'B' => [
        'mon_fri' => '8.00 AM - 4.00 PM',
        'sat' => '8.00 AM - 12.30 PM'
    ]
];

require_once '../../../includes/header.php';
require_once '../../../includes/sidebar.php';
?>

<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.css" rel="stylesheet">
<!-- DataTables CSS -->
<link href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap5.min.css" rel="stylesheet">
<link href="https://cdn.datatables.net/responsive/2.5.0/css/responsive.bootstrap5.min.css" rel="stylesheet">
<link href="https://cdn.datatables.net/buttons/2.3.6/css/buttons.bootstrap5.min.css" rel="stylesheet">

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
                <p class="text-muted mb-0">Welcome back, <?= htmlspecialchars($user_name) ?>! Here's a quick overview of your leave status and requests.</p>
            </div>
            <div class="text-end d-none d-md-block">
                <div class="p-2 bg-white shadow-sm rounded border">
                    <span class="small fw-bold text-uppercase text-muted d-block" style="font-size: 0.7rem;">Current Date</span>
                    <span class="text-primary fw-bold"><i class="bi bi-calendar3 me-2"></i><?= date('l, d M Y') ?></span>
                </div>
            </div>
        </div>

        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success alert-dismissible fade show border-0 shadow-sm" role="alert">
                <i class="bi bi-check-circle me-2"></i>
                <?= $_SESSION['success']; unset($_SESSION['success']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="show" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger alert-dismissible fade show border-0 shadow-sm" role="alert">
                <i class="bi bi-exclamation-triangle me-2"></i>
                <?= $_SESSION['error']; unset($_SESSION['error']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <!-- Statistics Cards -->
        <div class="row g-4 mb-4">
            <div class="col-xl-3 col-md-6">
                <div class="card border-0 shadow-sm h-100 border-start border-primary border-4">
                    <div class="card-body p-4">
                        <h6 class="text-muted small text-uppercase fw-bold">Total Requests</h6>
                        <h2 class="text-primary mb-0"><?= $total_leaves ?></h2>
                        <small class="text-muted">All leave requests</small>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="card border-0 shadow-sm h-100 border-start border-success border-4">
                    <div class="card-body p-4">
                        <h6 class="text-muted small text-uppercase fw-bold">Approved</h6>
                        <h2 class="text-success mb-0"><?= $approved_leaves ?></h2>
                        <small class="text-muted">Requests approved</small>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="card border-0 shadow-sm h-100 border-start border-warning border-4">
                    <div class="card-body p-4">
                        <h6 class="text-muted small text-uppercase fw-bold">Pending</h6>
                        <h2 class="text-warning mb-0"><?= $pending_leaves ?></h2>
                        <small class="text-muted">Awaiting approval</small>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="card border-0 shadow-sm h-100 border-start border-danger border-4">
                    <div class="card-body p-4">
                        <h6 class="text-muted small text-uppercase fw-bold">Rejected</h6>
                        <h2 class="text-danger mb-0"><?= $rejected_leaves ?></h2>
                        <small class="text-muted">Requests rejected</small>
                    </div>
                </div>
            </div>
        </div>

        <!-- Leave Balance Summary Table -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white border-0 py-3 d-flex justify-content-between align-items-center">
                        <h5 class="fw-bold mb-0"><i class="bi bi-calendar-check me-2 text-primary"></i>My Leave Balance</h5>
                        <small class="text-muted">Casual &amp; Sick leave entitlement: 24 days/year each</small>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Leave Type</th>
                                        <th class="text-center">Entitlement</th>
                                        <th class="text-center">Approved Days</th>
                                        <th class="text-center">Pending Days</th>
                                        <th class="text-center">Remaining</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $leave_types = [
                                        'Casual'   => ['entitlement' => 24,  'unlimited' => false, 'icon' => 'bi-umbrella',     'color' => 'primary'],
                                        'Sick'     => ['entitlement' => 24,  'unlimited' => false, 'icon' => 'bi-heart-pulse',  'color' => 'success'],
                                        'Foreign'  => ['entitlement' => null,'unlimited' => true,  'icon' => 'bi-airplane',     'color' => 'warning'],
                                        'Duty'     => ['entitlement' => null,'unlimited' => true,  'icon' => 'bi-briefcase',    'color' => 'danger'],
                                        'Maternity'=> ['entitlement' => null,'unlimited' => true,  'icon' => 'bi-gender-female','color' => 'secondary'],
                                    ];
                                    foreach ($leave_types as $type => $info):
                                        $type_query = "SELECT 
                                            SUM(CASE WHEN status='Approved' THEN no_of_days ELSE 0 END) as approved_days,
                                            SUM(CASE WHEN status='Pending' THEN no_of_days ELSE 0 END) as pending_days
                                            FROM leave_requests WHERE user_id='$user_id' AND leave_type='$type'";
                                        $type_result = $mysqli->query($type_query);
                                        $type_data = $type_result->fetch_assoc();
                                        $approved_days = $type_data['approved_days'] ?? 0;
                                        $pending_days  = $type_data['pending_days'] ?? 0;
                                        if (!$info['unlimited']) {
                                            $remaining = max(0, $info['entitlement'] - $approved_days);
                                            $remaining_text = $remaining . ' days';
                                            $remaining_class = $remaining < 5 ? 'text-danger fw-bold' : 'text-success fw-bold';
                                        }
                                    ?>
                                    <tr>
                                        <td>
                                            <i class="bi <?= $info['icon'] ?> me-2 text-<?= $info['color'] ?>"></i>
                                            <strong><?= $type ?></strong>
                                        </td>
                                        <td class="text-center"><?= $info['unlimited'] ? '<span class="badge bg-secondary">Unlimited</span>' : $info['entitlement'].' days' ?></td>
                                        <td class="text-center"><span class="badge bg-success"><?= number_format($approved_days, 1) ?></span></td>
                                        <td class="text-center"><span class="badge bg-warning text-dark"><?= number_format($pending_days, 1) ?></span></td>
                                        <td class="text-center">
                                            <?php if ($info['unlimited']): ?>
                                                <span class="text-muted">—</span>
                                            <?php else: ?>
                                                <span class="<?= $remaining_class ?>"><?= $remaining_text ?></span>
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
                                <button class="btn w-100 py-5 shadow-sm" style="background-color: #370709; color: white;" data-bs-toggle="modal" data-bs-target="#leaveRequestModal">
                                    <i class="bi bi-calendar-plus fs-4 me-2"></i>
                                    <span style="font-size: 1.2rem;">Request Leave</span>
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
                                    <?php if ($leave_requests_result && $leave_requests_result->num_rows > 0): ?>
                                        <?php while ($row = $leave_requests_result->fetch_assoc()): ?>
                                            <tr>
                                                <td><?= htmlspecialchars($row['start_date']) ?></td>
                                                <td><?= htmlspecialchars($row['resume_date']) ?></td>
                                                <td>
                                                    <?= htmlspecialchars($row['leave_type']) ?>
                                                    <?php if ($row['is_half_day']): ?>
                                                        <span class="badge bg-info ms-1" style="font-size: 0.65rem;">Half Day</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td><?= htmlspecialchars($row['no_of_days']) ?></td>
                                                <td><?= htmlspecialchars($row['reason']) ?></td>
                                                <td>
                                                    <span class="status-<?= strtolower($row['status']) ?>">
                                                        <?= htmlspecialchars($row['status']) ?>
                                                    </span>
                                                </td>
                                                <td class="text-center">
                                                    <i class="bi bi-eye action-icon icon-view" onclick="viewLeaveDetails(<?= htmlspecialchars(json_encode($row)) ?>)" title="View"></i>
                                                    <?php if ($row['status'] == 'Pending'): ?>
                                                        <i class="bi bi-pencil action-icon icon-edit" onclick="openEditModal(<?= htmlspecialchars(json_encode($row)) ?>)" title="Edit"></i>
                                                        <i class="bi bi-trash action-icon icon-delete" onclick="confirmDelete(<?= $row['id'] ?>)" title="Delete"></i>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                        <?php endwhile; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="7" class="text-center text-muted py-4">No leave requests found.</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>
</div>


<?php require_once 'models/leave_request.php'; ?>

<?php require_once 'models/view_leave.php'; ?>

<?php require_once 'models/edit_leave.php'; ?>

<?php require_once 'models/delete_leave.php'; ?>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.datatables.net/responsive/2.5.0/js/dataTables.responsive.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.3.6/js/dataTables.buttons.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.3.6/js/buttons.bootstrap5.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.3.6/js/buttons.html5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.3.6/js/buttons.print.min.js"></script>

<script>
    let leaveDataTable;

    $(document).ready(function() {
        // Initialize Leave DataTable
        leaveDataTable = $('#leaveTable').DataTable({
            responsive: true,
            dom: "<'row mb-2'<'col-sm-12 col-md-4'l><'col-sm-12 col-md-8 d-flex justify-content-md-end align-items-center flex-wrap gap-2'Bf>>" +
                 "<'row'<'col-sm-12'tr>>" +
                 "<'row'<'col-sm-12 col-md-5 mt-3'i><'col-sm-12 col-md-7 mt-3'p>>",
            buttons: [
                { extend: 'csv', className: 'btn btn-sm btn-info text-white', exportOptions: { columns: [0, 1, 2, 3, 4, 5] } },
                { extend: 'excel', className: 'btn btn-sm btn-success', exportOptions: { columns: [0, 1, 2, 3, 4, 5] } },
                { extend: 'print', className: 'btn btn-sm btn-danger', exportOptions: { columns: [0, 1, 2, 3, 4, 5] } }
            ],
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
        const fromDateInput = document.getElementById('from_date');
        const toDateInput = document.getElementById('to_date');
        const halfDayCheckbox = document.getElementById('is_half_day');
        
        const fromDateVal = fromDateInput.value;
        const toDateVal = toDateInput.value;
        
        if (fromDateVal && toDateVal) {
            const start = new Date(fromDateVal);
            const end = new Date(toDateVal);
            
            if (halfDayCheckbox.checked) {
                // If half day is checked, we only allow same day leave
                if (fromDateVal !== toDateVal) {
                    alert("Half-day leave can only be applied for a single day.");
                    toDateInput.value = fromDateVal;
                    document.getElementById('no_of_days').value = '0.5';
                    return;
                }
                
                // Check if it's Saturday
                if (start.getDay() === 6) { // 6 is Saturday
                    alert("Saturday half-days are not allowed as per working hours.");
                    halfDayCheckbox.checked = false;
                    document.getElementById('no_of_days').value = '1';
                } else {
                    document.getElementById('no_of_days').value = '0.5';
                }
            } else {
                const diffTime = Math.abs(end - start);
                const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24)) + 1;
                document.getElementById('no_of_days').value = diffDays;
            }
        }
    }

    function toggleHalfDay() {
        const fromDateInput = document.getElementById('from_date');
        const toDateInput = document.getElementById('to_date');
        const halfDayCheckbox = document.getElementById('is_half_day');

        if (halfDayCheckbox.checked) {
            if (fromDateInput.value) {
                toDateInput.value = fromDateInput.value;
            }
        }
        calculateDays();
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
    function viewLeaveDetails(data) {
        const content = `
            <table class="table table-borderless mb-0">
                <tr><th class="text-muted small" style="width:40%">From Date</th><td>${data.start_date}</td></tr>
                <tr><th class="text-muted small">To Date</th><td>${data.resume_date}</td></tr>
                <tr><th class="text-muted small">Leave Type</th><td>${data.leave_type} ${data.is_half_day == 1 ? '<span class="badge bg-info">Half Day</span>' : ''}</td></tr>
                <tr><th class="text-muted small">No of Days</th><td>${data.no_of_days}</td></tr>
                <tr><th class="text-muted small">Reason</th><td>${data.reason}</td></tr>
                <tr><th class="text-muted small">Status</th><td><span class="status-${data.status.toLowerCase()}">${data.status}</span></td></tr>
                <tr><th class="text-muted small">Requested On</th><td>${data.request_date}</td></tr>
            </table>
        `;
        $('#viewLeaveContent').html(content);
        $('#viewLeaveModal').modal('show');
    }

    // Open Edit Modal and pre-fill data
    function openEditModal(data) {
        $('#edit_leave_id').val(data.id);
        $('#edit_from_date').val(data.start_date);
        $('#edit_to_date').val(data.resume_date);
        $('#edit_leave_type').val(data.leave_type);
        $('#edit_reason').val(data.reason);
        $('#edit_no_of_days').val(data.no_of_days);
        if (data.is_half_day == 1) {
            $('#edit_is_half_day').prop('checked', true);
        } else {
            $('#edit_is_half_day').prop('checked', false);
        }
        $('#editLeaveModal').modal('show');
    }

    // Calculate days for edit form
    function calculateEditDays() {
        const from = document.getElementById('edit_from_date').value;
        const to   = document.getElementById('edit_to_date').value;
        const half = document.getElementById('edit_is_half_day').checked;
        if (from && to) {
            if (half) {
                const d = new Date(from);
                if (d.getDay() === 6) {
                    alert('Saturday half-days are not allowed.');
                    document.getElementById('edit_is_half_day').checked = false;
                    return;
                }
                document.getElementById('edit_no_of_days').value = '0.5';
            } else {
                const start = new Date(from), end = new Date(to);
                const days = Math.ceil(Math.abs(end - start) / (1000*60*60*24)) + 1;
                document.getElementById('edit_no_of_days').value = days;
            }
        }
    }

    function toggleEditHalfDay() {
        const from = document.getElementById('edit_from_date').value;
        if (document.getElementById('edit_is_half_day').checked && from) {
            document.getElementById('edit_to_date').value = from;
        }
        calculateEditDays();
    }

</script>

<?php require_once '../../../includes/footer.php'; ?>