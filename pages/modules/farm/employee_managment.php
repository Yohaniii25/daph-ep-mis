<?php
// pages/modules/farm/employee_managment.php -> Regional Farm Staff & Officer Registry
require_once '../../../includes/header.php';
require_once '../../../config/db_connect.php';

if ($_SESSION['role'] !== 'farms_dd') {
    die("Access denied");
}

$user_id = $_SESSION['user_id'] ?? 1;
$farm_id = $_SESSION['farm_id'] ?? null;
$district_id = intval($_SESSION['district_id'] ?? 0);

// Fetch Farm Staff & Officers for current Regional Farm
$stmt = $mysqli->prepare("
    SELECT u.*, rf.farm_name as farm_name, d.name as district_name 
    FROM users u 
    LEFT JOIN regional_farms rf ON u.farm_id = rf.id 
    LEFT JOIN districts d ON u.district_id = d.id 
    WHERE (u.farm_id = ? OR (u.id = ? AND u.role = 'farms_dd')) AND u.is_active = 1 
    ORDER BY u.id DESC
");
$stmt->bind_param("ii", $farm_id, $user_id);
$stmt->execute();
$staff_list = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>

<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <div>
        <h3 class="fw-bold text-dark mb-1">
            <i class="bi bi-people-fill me-2" style="color: #820100;"></i>Employee &amp; Staff Management
        </h3>
        <p class="text-muted small mb-0">Regional Farm staff records, designations, user roles and appointment logs</p>
    </div>
    <div class="d-flex gap-2">
        <button class="btn text-light shadow-sm fw-bold" style="background-color: #820100;" data-bs-toggle="modal" data-bs-target="#addEmployeeModal">
            <i class="bi bi-person-plus-fill me-2"></i>Register New Officer
        </button>
        <a href="office_details.php" class="btn btn-secondary shadow-sm fw-bold">
            <i class="bi bi-arrow-left me-2"></i>Back to Office Details
        </a>
    </div>
</div>

<!-- Notification Status SweetAlert -->
<?php if (isset($_GET['status']) && isset($_GET['msg'])): ?>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    icon: '<?= ($_GET['status'] === 'success') ? 'success' : 'error' ?>',
                    title: '<?= ($_GET['status'] === 'success') ? 'Success!' : 'Error!' ?>',
                    text: <?= json_encode($_GET['msg'] ?? '') ?>,
                    confirmButtonColor: '#820100',
                    timer: 3500,
                    timerProgressBar: true
                });
            }
        });
    </script>
<?php endif; ?>

<div class="card shadow-sm border-0" style="border-radius: 12px;">
    <div class="card-body p-4">
        <div class="table-responsive">
            <table id="employeeTable" class="table table-hover align-middle w-100">
                <thead class="table-dark" style="background-color: #370709;">
                    <tr>
                        <th>Service #</th>
                        <th>Officer Name</th>
                        <th>Designation</th>
                        <th>Role</th>
                        <th>Service Category</th>
                        <th>Contact Number</th>
                        <th>Appointment Date</th>
                        <th class="text-center">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($staff_list as $emp): ?>
                        <tr>
                            <td><span class="fw-bold text-primary"><?= htmlspecialchars($emp['service_number'] ?: ($emp['emp_id'] ?: 'EMP-' . $emp['id'])) ?></span></td>
                            <td>
                                <div class="fw-bold text-dark"><?= htmlspecialchars($emp['full_name']) ?></div>
                                <small class="text-muted"><?= htmlspecialchars($emp['email'] ?: 'No Email') ?></small>
                            </td>
                            <td><span class="badge bg-light text-dark border"><?= htmlspecialchars($emp['designation'] ?: 'N/A') ?></span></td>
                            <td><span class="badge bg-info-subtle text-info border border-info px-2"><?= ucwords(str_replace('_', ' ', $emp['role'] ?? 'employee')) ?></span></td>
                            <td class="small"><?= htmlspecialchars($emp['service_category'] ?: '-') ?></td>
                            <td class="small"><?= htmlspecialchars($emp['phone'] ?: '-') ?></td>
                            <td class="small text-nowrap"><?= !empty($emp['appointment_date']) ? date('Y-m-d', strtotime($emp['appointment_date'])) : '-' ?></td>
                            <td class="text-center text-nowrap">
                                <button class="btn btn-sm btn-outline-info me-1 btn-view-emp"
                                    data-full_name="<?= htmlspecialchars($emp['full_name']) ?>"
                                    data-service_number="<?= htmlspecialchars($emp['service_number'] ?? '') ?>"
                                    data-emp_id="<?= htmlspecialchars($emp['emp_id'] ?? '') ?>"
                                    data-designation="<?= htmlspecialchars($emp['designation'] ?? '') ?>"
                                    data-role="<?= htmlspecialchars(ucwords(str_replace('_', ' ', $emp['role'] ?? ''))) ?>"
                                    data-service_category="<?= htmlspecialchars($emp['service_category'] ?? '') ?>"
                                    data-email="<?= htmlspecialchars($emp['email'] ?? '') ?>"
                                    data-phone="<?= htmlspecialchars($emp['phone'] ?? '') ?>"
                                    data-appointment_date="<?= htmlspecialchars($emp['appointment_date'] ?? '') ?>"
                                    data-appointment_current="<?= htmlspecialchars($emp['appointment_date_current_position'] ?? '') ?>"
                                    data-registered_date="<?= htmlspecialchars($emp['registered_date'] ?? '') ?>"
                                    data-bs-toggle="modal" data-bs-target="#viewEmployeeModal"
                                    title="View Officer Details">
                                    <i class="bi bi-eye"></i> View
                                </button>
                                <button class="btn btn-sm btn-outline-primary me-1 btn-edit-emp"
                                    data-id="<?= $emp['id'] ?>"
                                    data-service_number="<?= htmlspecialchars($emp['service_number'] ?? '') ?>"
                                    data-officer_name="<?= htmlspecialchars($emp['full_name']) ?>"
                                    data-designation="<?= htmlspecialchars($emp['designation'] ?? '') ?>"
                                    data-user_role="<?= htmlspecialchars($emp['role'] ?? '') ?>"
                                    data-service_category="<?= htmlspecialchars($emp['service_category'] ?? '') ?>"
                                    data-email="<?= htmlspecialchars($emp['email'] ?? '') ?>"
                                    data-contact_number="<?= htmlspecialchars($emp['phone'] ?? '') ?>"
                                    data-appointment_date="<?= htmlspecialchars($emp['appointment_date'] ?? '') ?>"
                                    data-appointment_date_current_position="<?= htmlspecialchars($emp['appointment_date_current_position'] ?? '') ?>"
                                    data-bs-toggle="modal" data-bs-target="#editEmployeeModal"
                                    title="Edit Officer">
                                    <i class="bi bi-pencil-square"></i> Edit
                                </button>
                                <a href="processors/office_assets_crud.php?action=delete_employee&id=<?= $emp['id'] ?>" class="btn btn-sm btn-outline-danger btn-delete" title="Delete Officer">
                                    <i class="bi bi-trash"></i> Delete
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php 
include 'models/add_employee.php';
include 'models/edit_employee.php';
include 'models/view_employee.php';
?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    $(document).on('click', '.btn-view-emp', function() {
        const btn = $(this);
        $('#view_full_name').text(btn.data('full_name') || '-');
        $('#view_service_number').text(btn.data('service_number') || '-');
        $('#view_emp_id').text(btn.data('emp_id') || '-');
        $('#view_designation').text(btn.data('designation') || '-');
        $('#view_role').text(btn.data('role') || '-');
        $('#view_service_category').text(btn.data('service_category') || '-');
        $('#view_email').text(btn.data('email') || '-');
        $('#view_contact').text(btn.data('phone') || '-');
        $('#view_appointment_date').text(btn.data('appointment_date') || '-');
        $('#view_appointment_current').text(btn.data('appointment_current') || '-');
        $('#view_registered_date').text(btn.data('registered_date') || '-');
    });

    $(document).on('click', '.btn-edit-emp', function() {
        const btn = $(this);
        $('#edit_emp_id_val').val(btn.data('id'));
        $('#edit_emp_service_number').val(btn.data('service_number'));
        $('#edit_emp_officer_name').val(btn.data('officer_name'));
        $('#edit_emp_designation').val(btn.data('designation'));
        $('#edit_emp_user_role').val(btn.data('user_role'));
        $('#edit_emp_service_category').val(btn.data('service_category'));
        $('#edit_emp_email').val(btn.data('email'));
        $('#edit_emp_contact_number').val(btn.data('contact_number'));
        $('#edit_emp_appointment_date').val(btn.data('appointment_date'));
        $('#edit_emp_appointment_date_current_position').val(btn.data('appointment_date_current_position'));
    });
});
</script>

<?php require_once '../../../includes/footer.php'; ?>
