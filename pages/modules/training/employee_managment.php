<?php
// pages/modules/training/employee_managment.php -> Training Centre Staff & Officers HR Registry
require_once '../../../includes/header.php';
require_once '../../../config/db_connect.php';

$allowed_roles = ['training_officer', 'administrator', 'provincial_director', 'district_dd'];
if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], $allowed_roles)) {
    die("Access denied");
}

$user_id = $_SESSION['user_id'] ?? 1;
$current_center_id = $_SESSION['training_center_id'] ?? null;
if (empty($current_center_id) && isset($_GET['center_id'])) {
    $current_center_id = intval($_GET['center_id']);
}
if (empty($current_center_id)) {
    $c_res = $mysqli->query("SELECT id FROM training_centers WHERE is_active = 1 LIMIT 1");
    if ($c_res && $row = $c_res->fetch_assoc()) {
        $current_center_id = $row['id'];
    } else {
        $current_center_id = 1;
    }
}

// Fetch Staff & Officers for current Training Centre
$stmt = $mysqli->prepare("
    SELECT u.*, tc.center_name, d.name as district_name 
    FROM users u 
    LEFT JOIN training_centers tc ON u.training_center_id = tc.id 
    LEFT JOIN districts d ON u.district_id = d.id 
    WHERE (u.training_center_id = ? OR (u.id = ? AND u.role = 'training_officer')) AND u.is_active = 1 
    ORDER BY u.id DESC
");
$stmt->bind_param("ii", $current_center_id, $user_id);
$stmt->execute();
$staff_list = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>

<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <div>
        <h3 class="fw-bold text-dark mb-1">
            <i class="bi bi-people-fill me-2" style="color: #820100;"></i>Employee &amp; Staff Management
        </h3>
        <p class="text-muted small mb-0">Training Centre staff records, instructors, designations, user roles and appointment logs</p>
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
                            <td><span class="fw-bold text-primary"><?= htmlspecialchars($emp['service_number'] ?: ($emp['emp_id'] ?: 'TC-EMP-' . $emp['id'])) ?></span></td>
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
                                    data-date_of_birth="<?= htmlspecialchars($emp['date_of_birth'] ?? '') ?>"
                                    data-appointment_date="<?= htmlspecialchars($emp['appointment_date'] ?? '') ?>"
                                    data-appointment_date_current="<?= htmlspecialchars($emp['appointment_date_current_position'] ?? '') ?>"
                                    data-bs-toggle="modal" data-bs-target="#viewEmployeeModal"
                                    title="View Profile">
                                    <i class="bi bi-eye"></i> View
                                </button>
                                <button class="btn btn-sm btn-outline-primary me-1 btn-edit-emp"
                                    data-id="<?= $emp['id'] ?>"
                                    data-service_number="<?= htmlspecialchars($emp['service_number'] ?? '') ?>"
                                    data-officer_name="<?= htmlspecialchars($emp['full_name']) ?>"
                                    data-designation="<?= htmlspecialchars($emp['designation'] ?? '') ?>"
                                    data-user_role="<?= htmlspecialchars($emp['role'] ?? 'training_officer') ?>"
                                    data-service_category="<?= htmlspecialchars($emp['service_category'] ?? '') ?>"
                                    data-email="<?= htmlspecialchars($emp['email'] ?? '') ?>"
                                    data-contact_number="<?= htmlspecialchars($emp['phone'] ?? '') ?>"
                                    data-date_of_birth="<?= htmlspecialchars($emp['date_of_birth'] ?? '') ?>"
                                    data-appointment_date="<?= htmlspecialchars($emp['appointment_date'] ?? '') ?>"
                                    data-appointment_date_current="<?= htmlspecialchars($emp['appointment_date_current_position'] ?? '') ?>"
                                    data-bs-toggle="modal" data-bs-target="#editEmployeeModal"
                                    title="Edit Officer">
                                    <i class="bi bi-pencil-square"></i> Edit
                                </button>
                                <a href="processors/office_assets_crud.php?action=delete_employee&id=<?= $emp['id'] ?>" class="btn btn-sm btn-outline-danger btn-delete" title="Deactivate Officer">
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

<!-- Include Modals -->
<?php
require_once __DIR__ . '/models/add_employee.php';
require_once __DIR__ . '/models/edit_employee.php';
require_once __DIR__ . '/models/view_employee.php';
?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    $(document).on('click', '.btn-view-emp', function() {
        const btn = $(this);
        $('#view_full_name').text(btn.data('full_name'));
        $('#view_designation').text(btn.data('designation') || 'Staff');
        $('#view_role').text(btn.data('role') || 'Employee');
        $('#view_service_number').text(btn.data('service_number') || btn.data('emp_id') || 'N/A');
        $('#view_service_category').text(btn.data('service_category') || 'N/A');
        $('#view_email').text(btn.data('email') || 'N/A');
        $('#view_phone').text(btn.data('phone') || 'N/A');
        $('#view_date_of_birth').text(btn.data('date_of_birth') || 'N/A');
        $('#view_appointment_date').text(btn.data('appointment_date') || 'N/A');
        $('#view_appointment_date_current').text(btn.data('appointment_date_current') || 'N/A');
    });

    $(document).on('click', '.btn-edit-emp', function() {
        const btn = $(this);
        $('#edit_emp_id_val').val(btn.data('id'));
        $('#edit_service_number').val(btn.data('service_number'));
        $('#edit_officer_name').val(btn.data('officer_name'));
        $('#edit_designation').val(btn.data('designation'));
        $('#edit_user_role').val(btn.data('user_role'));
        $('#edit_service_category').val(btn.data('service_category'));
        $('#edit_email').val(btn.data('email'));
        $('#edit_contact_number').val(btn.data('contact_number'));
        $('#edit_date_of_birth').val(btn.data('date_of_birth'));
        $('#edit_appointment_date').val(btn.data('appointment_date'));
        $('#edit_appointment_date_current').val(btn.data('appointment_date_current'));
    });

    if ($.fn.DataTable) {
        $('#employeeTable').DataTable({ responsive: true, pageLength: 10 });
    }
});
</script>

<?php require_once '../../../includes/footer.php'; ?>
