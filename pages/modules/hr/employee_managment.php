<?php
session_start();
require_once '../../../config/db_connect.php';

// Check if admin
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'administrator') {
    header("Location: ../../../index.php");
    exit();
}

// Fetch Employees with District and Range Names
$query = "
    SELECT 
        od.*, 
        u.unit_name,
        vr.name as range_name, 
        d.name as district_name 
    FROM office_details od
    LEFT JOIN master_units u ON od.unit_id = u.id
    LEFT JOIN veterinary_ranges vr ON od.range_id = vr.id
    LEFT JOIN districts d ON vr.district_id = d.id
    ORDER BY od.id DESC
";
$result = $mysqli->query($query);

require_once '../../../includes/header.php';
require_once '../../../includes/sidebar.php';
?>

<link rel="stylesheet" href="../../../assets/css/dataTables.bootstrap5.min.css">

<div id="layoutSidenav_content">
    <main class="container-fluid px-4 pt-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h3 class="fw-bold">Employee Management</h3>
                <p class="text-muted small">Manage staff details, designations, and office assignments</p>
            </div>
            <button class="btn btn-success shadow-sm" data-bs-toggle="modal" data-bs-target="#addEmployeeModal">
                <i class="bi bi-person-plus-fill me-2"></i>Add New Officer
            </button>
        </div>

        <div class="card shadow-sm border-0">
            <div class="card-body">
                <div class="table-responsive">
                    <table id="employeeTable" class="table table-hover align-middle w-100">
                        <thead class="bg-light">
    <tr class="small text-uppercase">
        <th>Emp ID</th>
        <th>Officer Name</th>
        <th>Unit/Section</th> <th>Designation</th>
        <th>District</th>
        <th>Range</th>
        <th>Contact</th>
        <th>Status</th>
        <th class="text-center">Actions</th>
    </tr>
</thead>
                        <tbody>
    <?php while ($row = $result->fetch_assoc()): ?>
        <tr id="row-<?= $row['id'] ?>">
            <td><span class="fw-bold text-primary">#<?= $row['emp_id'] ?></span></td>
            <td>
                <div class="fw-bold"><?= htmlspecialchars($row['officer_name']) ?></div>
                <small class="text-muted"><?= htmlspecialchars($row['email'] ?? 'No Email') ?></small>
            </td>
            <td>
                <span class="badge bg-info-soft text-info">
                    <?= htmlspecialchars($row['unit_name'] ?? 'Unassigned') ?>
                </span>
            </td>
            <td><span class="badge bg-light text-dark border"><?= $row['designation'] ?></span></td>
            <td><?= $row['district_name'] ?? '<span class="text-muted small">N/A</span>' ?></td>
            <td><?= $row['range_name'] ?? '<span class="text-muted small">N/A</span>' ?></td>
            <td class="small"><?= $row['contact_number'] ?? 'N/A' ?></td>
                                    <td>
                                        <?php if ($row['status'] == 'Active'): ?>
                                            <span class="badge bg-success-soft text-success"><i class="bi bi-check-circle me-1"></i>Active</span>
                                        <?php else: ?>
                                            <span class="badge bg-danger-soft text-danger"><i class="bi bi-x-circle me-1"></i>Inactive</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center">
                                        <div class="btn-group">
                                            <button class="btn btn-sm btn-outline-info me-1" title="View">
                                                <i class="bi bi-eye"></i>
                                            </button>
                                            <button class="btn btn-sm btn-outline-primary me-1" title="Edit">
                                                <i class="bi bi-pencil-square"></i>
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

<?php
include 'models/add_employee.php';
?>

<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>

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

    function confirmDelete(id) {
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                title: 'Deactivate Officer?',
                text: 'Are you sure you want to remove/deactivate this officer? Jurisdiction directors will be automatically notified.',
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
                        data: { id: id },
                        dataType: 'json',
                        success: function(response) {
                            if (response.success) {
                                Swal.fire({
                                    icon: 'success',
                                    title: 'Officer Removed',
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
                                    title: 'Operation Failed',
                                    text: response.message
                                });
                            }
                        },
                        error: function() {
                            Swal.fire('Error', 'Server communication failure.', 'error');
                        }
                    });
                }
            });
        } else {
            if (confirm("Are you sure you want to deactivate this officer?")) {
                $.post('processors/delete_employee.php', { id: id }, function(res) {
                    if (res.success) {
                        $('#row-' + id).remove();
                    } else {
                        alert(res.message);
                    }
                }, 'json');
            }
        }
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