<?php
session_start();
require_once '../../../config/db_connect.php';

$vs_roles = ['veterinary_surgeon', 'government_veterinary_surgeon', 'additional_veterinary_surgeon'];
if (!isset($_SESSION['logged_in']) || !in_array($_SESSION['role'], $vs_roles)) {
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
        od.employment_type,
        od.service_category,
        od.date_of_birth,
        od.appointment_date,
        od.appointment_date_current_position,
        od.position_to_current_location,
        od.registered_date,
        od.unit,
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

// Fetch available units & offices to populate the Transfer Request modal dropdown
$all_ranges_list = [];
$res_ranges = $mysqli->query("SELECT vr.id, vr.name, d.name AS district_name FROM veterinary_ranges vr LEFT JOIN districts d ON vr.district_id = d.id ORDER BY d.name ASC, vr.name ASC");
if ($res_ranges) {
    while ($r = $res_ranges->fetch_assoc()) {
        $dist_suffix = !empty($r['district_name']) ? " ({$r['district_name']})" : "";
        $all_ranges_list[] = [
            'id' => $r['id'],
            'name' => "Range Office - {$r['name']}{$dist_suffix}"
        ];
    }
}

$all_districts_list = [];
$res_districts = $mysqli->query("SELECT id, name FROM districts ORDER BY name ASC");
if ($res_districts) {
    while ($r = $res_districts->fetch_assoc()) {
        $all_districts_list[] = $r;
    }
}

$all_farms_list = [];
$res_farms = $mysqli->query("SELECT id, farm_name FROM regional_farms ORDER BY farm_name ASC");
if ($res_farms) {
    while ($r = $res_farms->fetch_assoc()) {
        $all_farms_list[] = [
            'id' => $r['id'],
            'name' => "Regional Farm - {$r['farm_name']}"
        ];
    }
}

$all_training_list = [];
$res_training = $mysqli->query("SELECT id, center_name, location FROM training_centers ORDER BY center_name ASC");
if ($res_training) {
    while ($r = $res_training->fetch_assoc()) {
        $loc_suffix = !empty($r['location']) ? " ({$r['location']})" : "";
        $all_training_list[] = [
            'id' => $r['id'],
            'name' => "Training Center - {$r['center_name']}{$loc_suffix}"
        ];
    }
}

$all_master_units_list = [];
$res_units = $mysqli->query("SELECT id, unit_name FROM master_units ORDER BY id ASC");
if ($res_units) {
    while ($r = $res_units->fetch_assoc()) {
        $all_master_units_list[] = [
            'id' => $r['id'],
            'name' => "Unit - {$r['unit_name']}"
        ];
    }
}

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
                                <th>Type</th>
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
                                    <td>
                                        <?php if (($row['employment_type'] ?? 'permanent') === 'temporary'): ?>
                                            <span class="badge bg-warning-soft text-warning border border-warning">Temporary</span>
                                        <?php else: ?>
                                            <span class="badge bg-success-soft text-success border border-success">Permanent</span>
                                        <?php endif; ?>
                                    </td>
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
                                            <button class="btn btn-sm btn-outline-warning text-dark me-1" title="Request Transfer" onclick='openTransferModal(<?= json_encode($row) ?>)'>
                                                <i class="bi bi-arrow-left-right"></i>
                                            </button>
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
<?php include 'models/transfer_modal.php'; ?>

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
                        <small class="text-muted d-block text-uppercase fw-semibold" style="font-size:11px;">Employment Type</small>
                        <span class="fw-semibold text-dark" id="view_employment_type">-</span>
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
                        <small class="text-muted d-block text-uppercase fw-semibold" style="font-size:11px;">Date of Birth</small>
                        <span class="fw-semibold text-dark" id="view_date_of_birth">-</span>
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
                        <small class="text-muted d-block text-uppercase fw-semibold" style="font-size:11px;">Position to Current Location</small>
                        <span class="fw-semibold text-dark" id="view_position_to_current_location">-</span>
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
        document.getElementById('view_employment_type').textContent = data.employment_type ? (data.employment_type.charAt(0).toUpperCase() + data.employment_type.slice(1)) : 'Permanent';
        document.getElementById('view_service_category').textContent = data.service_category || '-';
        document.getElementById('view_email').textContent = data.email || '-';
        document.getElementById('view_contact').textContent = data.contact_number || '-';
        document.getElementById('view_district').textContent = data.district_name || '-';
        document.getElementById('view_range').textContent = data.range_name || '-';
        document.getElementById('view_date_of_birth').textContent = data.date_of_birth || '-';
        document.getElementById('view_appointment_date').textContent = data.appointment_date || '-';
        document.getElementById('view_appointment_current').textContent = data.appointment_date_current_position || '-';
        if (document.getElementById('view_position_to_current_location')) {
            document.getElementById('view_position_to_current_location').textContent = data.position_to_current_location || '-';
        }
        document.getElementById('view_registered_date').textContent = data.registered_date || '-';
        var modal = new bootstrap.Modal(document.getElementById('viewEmployeeModal'));
        modal.show();
    }

    const roleDesignationMapping = {
        'government_veterinary_surgeon': 'Government Veterinary Surgeon (GVS)',
        'additional_veterinary_surgeon': 'Additional Veterinary Surgeon (AVS)',
        'livestock_development_officer': 'Livestock Development Officer (or Instructor)',
        'development_officer': 'Development Officer (DO)',
        'driver': 'Driver',
        'dispensary_assistant': 'Dispensary Assistant',
        'department_laborer': 'Department Laborer',
        'night_watcher': 'Night Watcher'
    };

    function syncRoleToDesignation(roleSelectElem, targetDesignationId) {
        const desigElem = document.getElementById(targetDesignationId);
        if (!desigElem) return;
        const mapped = roleDesignationMapping[roleSelectElem.value];
        if (mapped) {
            for (let i = 0; i < desigElem.options.length; i++) {
                if (desigElem.options[i].value === mapped) {
                    desigElem.selectedIndex = i;
                    return;
                }
            }
        }
    }

    function editEmployee(data) {
        document.getElementById('edit_id').value = data.id || '';
        if (document.getElementById('edit_employee_unit')) {
            document.getElementById('edit_employee_unit').value = data.unit || '';
        }
        document.getElementById('edit_service_number').value = data.service_number || data.emp_id || '';
        document.getElementById('edit_officer_name').value = data.full_name || '';

        var desigElem = document.getElementById('edit_designation');
        if (desigElem) {
            desigElem.value = data.designation || '';
            if (!desigElem.value && data.designation) {
                for (let i = 0; i < desigElem.options.length; i++) {
                    if (desigElem.options[i].value.toLowerCase().includes(data.designation.toLowerCase()) || 
                        desigElem.options[i].text.toLowerCase().includes(data.designation.toLowerCase())) {
                        desigElem.selectedIndex = i;
                        break;
                    }
                }
            }
        }

        var roleElem = document.getElementById('edit_user_role');
        if (roleElem) {
            roleElem.value = data.role || 'employee';
            if (!roleElem.value && data.role) {
                roleElem.value = 'employee';
            }
        }

        var empTypeElem = document.getElementById('edit_employment_type');
        if (empTypeElem) {
            empTypeElem.value = data.employment_type || 'permanent';
        }

        document.getElementById('edit_service_category').value = data.service_category || '';
        document.getElementById('edit_email').value = data.email || '';
        document.getElementById('edit_contact_number').value = data.contact_number || data.phone || '';
        document.getElementById('edit_date_of_birth').value = data.date_of_birth || '';
        document.getElementById('edit_appointment_date').value = data.appointment_date || '';
        document.getElementById('edit_appointment_date_current_position').value = data.appointment_date_current_position || '';
        if (document.getElementById('edit_position_to_current_location')) {
            document.getElementById('edit_position_to_current_location').value = data.position_to_current_location || '';
        }

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

    function openTransferModal(data) {
        if (!data) return;
        document.getElementById('transfer_employee_id').value = data.id || '';
        document.getElementById('transfer_employee_name').innerText = data.full_name || 'Officer';
        
        var svc = data.service_number || data.emp_id || '-';
        var desig = data.designation || data.role || 'Staff';
        var range = data.range_name ? (data.range_name + ' Range') : '';
        document.getElementById('transfer_employee_meta').innerText = 'Service #: ' + svc + ' | Designation: ' + desig + (range ? (' | ' + range) : '');
        
        document.getElementById('transfer_target_unit').value = '';
        document.getElementById('transfer_reason').value = '';

        var transferModal = new bootstrap.Modal(document.getElementById('transferRequestModal'));
        transferModal.show();
    }

    function submitTransferRequest(e) {
        e.preventDefault();
        var form = document.getElementById('transferRequestForm');
        var empId = document.getElementById('transfer_employee_id').value;
        var targetUnit = document.getElementById('transfer_target_unit').value;
        var reason = document.getElementById('transfer_reason').value.trim();
        var submitBtn = document.getElementById('submitTransferBtn');

        if (!empId || !targetUnit || !reason) {
            Swal.fire({
                icon: 'warning',
                title: 'Missing Required Fields',
                text: 'Please select a target unit and provide a reason for the transfer request.'
            });
            return;
        }

        var originalBtnHtml = submitBtn.innerHTML;
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span> Routing Request...';

        $.ajax({
            url: 'processors/request_transfer.php',
            type: 'POST',
            data: {
                employee_id: empId,
                target_unit: targetUnit,
                reason: reason
            },
            dataType: 'json',
            success: function(response) {
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalBtnHtml;

                if (response.success) {
                    var modalElem = document.getElementById('transferRequestModal');
                    var modalInstance = bootstrap.Modal.getInstance(modalElem);
                    if (modalInstance) {
                        modalInstance.hide();
                    }

                    Swal.fire({
                        icon: 'success',
                        title: 'Transfer Request Dispatched',
                        text: response.message,
                        confirmButtonColor: '#820100'
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Request Failed',
                        text: response.message || 'An error occurred while routing the transfer request.'
                    });
                }
            },
            error: function() {
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalBtnHtml;

                Swal.fire({
                    icon: 'error',
                    title: 'Server Error',
                    text: 'Unable to connect to the server. Please try again.'
                });
            }
        });
    }

    <?php if (isset($_SESSION['staged_msg'])): ?>
    Swal.fire({
        icon: 'info',
        title: 'Authorization Pending',
        text: <?= json_encode($_SESSION['staged_msg']) ?>,
        confirmButtonColor: '#500707'
    });
    <?php unset($_SESSION['staged_msg']); ?>
    <?php endif; ?>
</script>

<style>
    /* Styling for a modern soft-badge look */
    .bg-success-soft {
        background-color: #e8fadf;
        color: #198754;
    }

    .bg-warning-soft {
        background-color: #fff9e6;
        color: #b78103;
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