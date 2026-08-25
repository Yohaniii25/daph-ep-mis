<?php
session_start();
require_once '../../../config/db_connect.php';

if (!isset($_SESSION['logged_in']) || $_SESSION['role'] !== 'veterinary_surgeon') {
    header("Location: ../../../../index.php");
    exit();
}

// Establish the range_id and range_name variables before running queries
$range_id = $_SESSION['range_id'] ?? null;
$range_name = $_SESSION['range_name'] ?? 'Your Range';
$district_id = $_SESSION['district_id'] ?? null;
$district_name = 'Your District';

// Ensure we have district_id & range_id and user details
$user_full_name = 'Unknown';
$user_designation = 'Unknown';

$user_query = $mysqli->prepare("SELECT district_id, range_id, full_name, designation, role, phone, email FROM users WHERE id = ?");
if ($user_query) {
    $user_query->bind_param("i", $_SESSION['user_id']);
    $user_query->execute();
    $user_result = $user_query->get_result();
    if ($user_result->num_rows > 0) {
        $user_data = $user_result->fetch_assoc();
        $_SESSION['district_id'] = $user_data['district_id'];
        $_SESSION['range_id'] = $user_data['range_id'];
        $range_id = $user_data['range_id'];
        $district_id = $user_data['district_id'];
        $user_full_name = !empty($user_data['full_name']) ? $user_data['full_name'] : 'Unknown';

        // Use role string or designation
        $user_designation = !empty($user_data['role']) ? ucwords(str_replace('_', ' ', $user_data['role'])) : 'Unknown';
    }
    $user_query->close();
}

// Fetch district name
if (!empty($district_id)) {
    $district_query = $mysqli->prepare("SELECT name FROM districts WHERE id = ?");
    if ($district_query) {
        $district_query->bind_param("i", $district_id);
        $district_query->execute();
        $district_result = $district_query->get_result();
        if ($district_result->num_rows > 0) {
            $district_data = $district_result->fetch_assoc();
            $district_name = $district_data['name'] ?? 'Your District';
        }
        $district_query->close();
    }
}

// Fetch range name if empty
if (!empty($range_id) && $range_name === 'Your Range') {
    $range_query = $mysqli->prepare("SELECT name FROM veterinary_ranges WHERE id = ?");
    if ($range_query) {
        $range_query->bind_param("i", $range_id);
        $range_query->execute();
        $range_result = $range_query->get_result();
        if ($range_result->num_rows > 0) {
            $range_data = $range_result->fetch_assoc();
            $range_name = $range_data['name'] ?? 'Your Assigned Range';
        }
        $range_query->close();
    }
}

// Fetch employees with all relevant columns
$query = "
    SELECT 
        od.id,
        od.emp_id,
        od.service_number,
        od.full_name,
        od.email,
        od.phone AS contact_number,
        od.designation,
        od.role,
        od.service_category,
        od.appointment_date,
        od.appointment_date_current_position,
        od.registered_date,
        vr.name as range_name, 
        d.name as district_name 
    FROM users od
    LEFT JOIN veterinary_ranges vr ON od.range_id = vr.id
    LEFT JOIN districts d ON od.district_id = d.id
    WHERE od.district_id = ? AND od.range_id = ? AND od.is_active = 1
    ORDER BY od.id DESC
";

$stmt = $mysqli->prepare($query);
$stmt->bind_param("ii", $district_id, $range_id);
$stmt->execute();
$result = $stmt->get_result();

require_once '../../../includes/header.php';
?>

<link rel="stylesheet" href="../../../assets/css/dataTables.bootstrap5.min.css">


        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h3 class="fw-bold mb-1">Employee Management</h3>
                <p class="text-muted small mb-0">Manage staff details, designations, and office assignments</p>
            </div>
            <div class="d-flex gap-2">
                <button class="btn btn-success shadow-sm" data-bs-toggle="modal" data-bs-target="#addEmployeeModal">
                    <i class="bi bi-person-plus-fill me-2"></i>Add New Officer
                </button>
                <a href="office_details.php" class="btn btn-secondary shadow-sm">
                    <i class="bi bi-arrow-left me-2"></i>Back
                </a>
            </div>
        </div>

        <?php if (isset($_SESSION['msg'])): ?>
            <div class="alert alert-<?= $_SESSION['msg_type'] ?? 'info' ?> alert-dismissible fade show shadow-sm py-2 px-3 mb-4 small" role="alert">
                <?= htmlspecialchars($_SESSION['msg']) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            <?php unset($_SESSION['msg'], $_SESSION['msg_type']); ?>
        <?php endif; ?>

        <div class="card shadow-sm border-0">
            <div class="card-body">
                <div class="table-responsive">
                    <table id="employeeTable" class="table table-hover align-middle w-100">
                        <thead class="bg-light">
                            <tr class="small text-uppercase">
                                <th>Service #</th>
                                <th>Officer Name</th>
                                <th>Designation</th>
                                <th>Role</th>
                                <th>District</th>
                                <th>Range</th>
                                <th>Contact</th>
                                <th>Leave Details</th>
                                <th class="text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($row = $result->fetch_assoc()): ?>
                                <tr id="row-<?= $row['id'] ?>">
                                    <td><span class="fw-bold text-primary"><?= htmlspecialchars($row['service_number'] ?? $row['emp_id'] ?? '-') ?></span></td>
                                    <td>
                                        <div class="fw-bold"><?= htmlspecialchars($row['full_name']) ?></div>
                                        <small class="text-muted"><?= htmlspecialchars($row['email'] ?? 'No Email') ?></small>
                                    </td>
                                    <td><span class="badge bg-light text-dark border"><?= htmlspecialchars($row['designation'] ?? 'N/A') ?></span></td>
                                    <td><span class="badge bg-info-soft text-info"><?= ucwords(str_replace('_', ' ', $row['role'] ?? 'N/A')) ?></span></td>
                                    <td><?= $row['district_name'] ?? '<span class="text-muted small">N/A</span>' ?></td>
                                    <td><?= $row['range_name'] ?? '<span class="text-muted small">N/A</span>' ?></td>
                                    <td class="small"><?= htmlspecialchars($row['contact_number'] ?? 'N/A') ?></td>
                                    <td>
                                        <a href="#" class="btn btn-sm btn-outline-primary" title="View Leave Details">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                    </td>
                                    <td class="text-center">
                                        <div class="btn-group">
                                            <button class="btn btn-sm btn-outline-info me-1" title="View Details" onclick='viewEmployee(<?= json_encode($row) ?>)'>
                                                <i class="bi bi-eye"></i>
                                            </button>
                                            <button class="btn btn-sm btn-outline-primary me-1" title="Edit" onclick='editEmployee(<?= json_encode($row) ?>)'>
                                                <i class="bi bi-pencil"></i>
                                            </button>
                                            <button class="btn btn-sm btn-outline-danger" title="Delete" onclick="confirmDelete(<?= $row['id'] ?>)">
                                                <i class="bi bi-trash"></i>
                                            </button>
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

<?php include 'models/add_employee.php'; ?>
<?php include 'models/edit_employee.php'; ?>

<!-- View Employee Details Modal -->
<div class="modal fade" id="viewEmployeeModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header text-light" style="background-color: #370709;">
                <h5 class="modal-title"><i class="bi bi-person-badge me-2"></i>Officer Details</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <div class="row g-3">
                    <div class="col-md-6">
                        <small class="text-muted d-block text-uppercase fw-semibold" style="font-size:11px;">Full Name</small>
                        <span class="fw-bold text-dark" id="view_full_name">-</span>
                    </div>
                    <div class="col-md-6">
                        <small class="text-muted d-block text-uppercase fw-semibold" style="font-size:11px;">Service Number</small>
                        <span class="fw-semibold text-dark" id="view_service_number">-</span>
                    </div>
                    <div class="col-md-6">
                        <small class="text-muted d-block text-uppercase fw-semibold" style="font-size:11px;">Employee ID</small>
                        <span class="fw-semibold text-dark" id="view_emp_id">-</span>
                    </div>
                    <div class="col-md-6">
                        <small class="text-muted d-block text-uppercase fw-semibold" style="font-size:11px;">Designation</small>
                        <span class="fw-semibold text-dark" id="view_designation">-</span>
                    </div>
                    <div class="col-md-6">
                        <small class="text-muted d-block text-uppercase fw-semibold" style="font-size:11px;">Role</small>
                        <span class="fw-semibold text-dark" id="view_role">-</span>
                    </div>
                    <div class="col-md-6">
                        <small class="text-muted d-block text-uppercase fw-semibold" style="font-size:11px;">Service Category</small>
                        <span class="fw-semibold text-dark" id="view_service_category">-</span>
                    </div>
                    <div class="col-md-6">
                        <small class="text-muted d-block text-uppercase fw-semibold" style="font-size:11px;">Email</small>
                        <span class="fw-semibold text-dark" id="view_email">-</span>
                    </div>
                    <div class="col-md-6">
                        <small class="text-muted d-block text-uppercase fw-semibold" style="font-size:11px;">Contact Number</small>
                        <span class="fw-semibold text-dark" id="view_contact">-</span>
                    </div>
                    <div class="col-md-6">
                        <small class="text-muted d-block text-uppercase fw-semibold" style="font-size:11px;">District</small>
                        <span class="fw-semibold text-dark" id="view_district">-</span>
                    </div>
                    <div class="col-md-6">
                        <small class="text-muted d-block text-uppercase fw-semibold" style="font-size:11px;">Range</small>
                        <span class="fw-semibold text-dark" id="view_range">-</span>
                    </div>
                    <div class="col-md-6">
                        <small class="text-muted d-block text-uppercase fw-semibold" style="font-size:11px;">Appointment Date</small>
                        <span class="fw-semibold text-dark" id="view_appointment_date">-</span>
                    </div>
                    <div class="col-md-6">
                        <small class="text-muted d-block text-uppercase fw-semibold" style="font-size:11px;">Appointment to Current Position</small>
                        <span class="fw-semibold text-dark" id="view_appointment_current">-</span>
                    </div>
                    <div class="col-md-6">
                        <small class="text-muted d-block text-uppercase fw-semibold" style="font-size:11px;">Registered Date</small>
                        <span class="fw-semibold text-dark" id="view_registered_date">-</span>
                    </div>
                </div>
            </div>
            <div class="modal-footer bg-light">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
    $(document).ready(function() {
        // Handle district change to load ranges
        $('#modal_district').on('change', function() {
            var districtId = $(this).val();
            console.log("District selected:", districtId);

            if (districtId) {
                $.ajax({
                    url: 'processors/get_ranges.php',
                    type: 'GET',
                    data: {
                        district_id: districtId
                    },
                    dataType: 'html',
                    success: function(response) {
                        console.log("Response received:", response);
                        $('#modal_range').html(response);
                    },
                    error: function(xhr, status, error) {
                        console.error("AJAX Error:", status, error);
                        console.error("Response:", xhr.responseText);
                        alert("Failed to load ranges. Check console (F12) for details.");
                    }
                });
            } else {
                $('#modal_range').html('<option value="">Select Range Office</option>');
            }
        });

        // Handle main DataTable initialization
        $('#employeeTable').DataTable({
            "pageLength": 10,
            "order": [
                [0, "desc"]
            ],
            "language": {
                "searchPlaceholder": "Search by name or ID...",
                "search": ""
            }
        });
    });

    function viewEmployee(data) {
        document.getElementById('view_full_name').textContent = data.full_name || '-';
        document.getElementById('view_service_number').textContent = data.service_number || '-';
        document.getElementById('view_emp_id').textContent = data.emp_id || '-';
        document.getElementById('view_designation').textContent = data.designation || '-';
        document.getElementById('view_role').textContent = data.role ? data.role.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase()) : '-';
        document.getElementById('view_service_category').textContent = data.service_category || '-';
        document.getElementById('view_email').textContent = data.email || '-';
        document.getElementById('view_contact').textContent = data.contact_number || '-';
        document.getElementById('view_district').textContent = data.district_name || '-';
        document.getElementById('view_range').textContent = data.range_name || '-';
        document.getElementById('view_appointment_date').textContent = data.appointment_date || '-';
        document.getElementById('view_appointment_current').textContent = data.appointment_date_current_position || '-';
        document.getElementById('view_registered_date').textContent = data.registered_date || '-';
        var modal = new bootstrap.Modal(document.getElementById('viewEmployeeModal'));
        modal.show();
    }

    function editEmployee(data) {
        document.getElementById('edit_id').value = data.id || '';
        document.getElementById('edit_service_number').value = data.service_number || data.emp_id || '';
        document.getElementById('edit_officer_name').value = data.full_name || '';
        document.getElementById('edit_designation').value = data.designation || '';
        document.getElementById('edit_user_role').value = data.role || 'employee';
        document.getElementById('edit_service_category').value = data.service_category || '';
        document.getElementById('edit_email').value = data.email || '';
        document.getElementById('edit_contact_number').value = data.contact_number || data.phone || '';
        document.getElementById('edit_appointment_date').value = data.appointment_date || '';
        document.getElementById('edit_appointment_date_current_position').value = data.appointment_date_current_position || '';

        var editModal = new bootstrap.Modal(document.getElementById('editEmployeeModal'));
        editModal.show();
    }

    function confirmDelete(id) {
        Swal.fire({
            title: 'Deactivate Officer?',
            text: 'Are you sure you want to deactivate this officer? This action cannot be undone.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc3545',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Yes, Deactivate',
            cancelButtonText: 'Cancel'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: 'processors/delete_employee.php',
                    type: 'POST',
                    data: {
                        id: id
                    },
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            Swal.fire({
                                icon: 'success',
                                title: 'Deactivated!',
                                text: response.message,
                                timer: 2000,
                                showConfirmButton: false
                            });
                            $('#row-' + id).fadeOut(400, function() {
                                $(this).remove();
                            });
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: 'Error',
                                text: response.message
                            });
                        }
                    },
                    error: function() {
                        Swal.fire({
                            icon: 'error',
                            title: 'Server Error',
                            text: 'Failed to connect to the server.'
                        });
                    }
                });
            }
        });
    }
</script>

<style>
    /* Styling for a modern soft-badge look */
    .bg-success-soft {
        background-color: #e8fadf;
        color: #198754;
    }

    .bg-danger-soft {
        background-color: #fbe9eb;
        color: #dc3545;
    }

    .dataTables_filter input {
        border-radius: 20px;
        padding-left: 15px;
        border: 1px solid #ddd;
    }
</style>

<?php require_once '../../../includes/footer.php'; ?>