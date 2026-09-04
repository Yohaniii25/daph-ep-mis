<?php
/**
 * pages/modules/pd/employee_managment.php
 * Global HR Directory & Role Management Module for Provincial Director
 * Strictly organized inside pages/modules/pd/
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once '../../../config/db_connect.php';
require_once '../../../includes/notification_helper.php';

// Authorization: Provincial Director, Administrator, Deputy Director H/Q 1 & 2
$allowed_roles = ['administrator', 'provincial_director', 'deputy_director_hq_1', 'deputy_director_hq_2'];
if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], $allowed_roles)) {
    header("Location: ../../../index.php");
    exit();
}

$user_role = $_SESSION['role'];

// Fetch all employees from live users table joined with district, range, farm, and training center
$query = "
    SELECT 
        u.*, 
        d.name AS district_name, 
        vr.name AS range_name, 
        rf.farm_name,
        tc.center_name AS training_center_name
    FROM users u
    LEFT JOIN districts d ON (u.district_id = d.id OR u.district = d.name)
    LEFT JOIN veterinary_ranges vr ON u.range_id = vr.id
    LEFT JOIN regional_farms rf ON u.farm_id = rf.id
    LEFT JOIN training_centers tc ON u.training_center_id = tc.id
    ORDER BY 
        CASE 
            WHEN u.role = 'provincial_director' THEN 1
            WHEN u.role = 'deputy_director_hq_1' THEN 2
            WHEN u.role = 'deputy_director_hq_2' THEN 3
            WHEN u.role = 'sms' THEN 4
            WHEN u.role = 'district_dd' THEN 5
            WHEN u.role IN ('veterinary_surgeon', 'government_veterinary_surgeon', 'additional_veterinary_surgeon') THEN 6
            ELSE 7
        END,
        u.id ASC
";
$result = $mysqli->query($query);
$all_officers = [];
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $all_officers[] = $row;
    }
}

// 1. Subject Matter Specialist (SMS)
$sms_officers = array_filter($all_officers, function ($o) {
    return ($o['role'] === 'sms') 
        || (stripos($o['designation'] ?? '', 'Subject Matter Specialist') !== false)
        || (stripos($o['designation'] ?? '', 'SMS') !== false);
});

// 2. Deputy Director H/Q (1 & 2)
$dd_hq1_officers = array_filter($all_officers, function ($o) {
    return ($o['role'] === 'deputy_director_hq_1') || (stripos($o['designation'] ?? '', 'H/Q-1') !== false) || (stripos($o['designation'] ?? '', 'HQ 1') !== false);
});

$dd_hq2_officers = array_filter($all_officers, function ($o) {
    return ($o['role'] === 'deputy_director_hq_2') || (stripos($o['designation'] ?? '', 'H/Q-2') !== false) || (stripos($o['designation'] ?? '', 'HQ 2') !== false);
});

// 3. Veterinary Surgeons (Field & Clinical)
$vs_officers = array_filter($all_officers, function ($o) {
    return in_array($o['role'], ['veterinary_surgeon', 'government_veterinary_surgeon', 'additional_veterinary_surgeon'])
        || (stripos($o['designation'] ?? '', 'Veterinary Surgeon') !== false)
        || (stripos($o['designation'] ?? '', 'GVS') !== false)
        || (stripos($o['designation'] ?? '', 'AVS') !== false);
});

// District DDs
$district_dd_officers = array_filter($all_officers, function ($o) {
    return ($o['role'] === 'district_dd');
});

// Fetch master options for Modals
$districts = $mysqli->query("SELECT id, name FROM districts ORDER BY id ASC")->fetch_all(MYSQLI_ASSOC);
$ranges = $mysqli->query("SELECT id, name, district_id FROM veterinary_ranges WHERE is_active = 1 ORDER BY name ASC")->fetch_all(MYSQLI_ASSOC);
$farms = $mysqli->query("SELECT id, farm_name FROM regional_farms WHERE is_active = 1 ORDER BY farm_name ASC")->fetch_all(MYSQLI_ASSOC);
$training_centers = $mysqli->query("SELECT id, center_name FROM training_centers WHERE is_active = 1 ORDER BY center_name ASC")->fetch_all(MYSQLI_ASSOC);

require_once '../../../includes/header.php';
require_once '../../../includes/sidebar.php';
?>

<link rel="stylesheet" href="../../../assets/css/dataTables.bootstrap5.min.css">
<style>
    :root {
        --daph-maroon: #820100;
        --daph-maroon-dark: #370709;
        --daph-gold: #c28e2b;
        --daph-gold-soft: #fcf6e8;
    }

    .key-role-card {
        border-radius: 12px;
        transition: transform 0.2s ease, box-shadow 0.2s ease;
        border: 1px solid rgba(0, 0, 0, 0.08);
        background: #fff;
    }

    .key-role-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 10px 24px rgba(0, 0, 0, 0.08) !important;
    }

    .badge-soft-maroon {
        background-color: #faebeb;
        color: #721c24;
        border: 1px solid #f5c6cb;
    }

    .badge-soft-primary {
        background-color: #e7f1ff;
        color: #0d6efd;
    }

    .badge-soft-success {
        background-color: #e8fadf;
        color: #198754;
    }

    .badge-soft-warning {
        background-color: #fff8e6;
        color: #b07802;
    }

    .badge-soft-purple {
        background-color: #f3e8ff;
        color: #6f42c1;
    }

    .filter-pill-btn {
        border-radius: 20px;
        padding: 6px 14px;
        font-size: 0.85rem;
        font-weight: 500;
        border: 1px solid #ced4da;
        background: #fff;
        color: #495057;
        transition: all 0.2s ease;
    }

    .filter-pill-btn:hover {
        background: #f8f9fa;
        color: var(--daph-maroon);
        border-color: var(--daph-maroon);
    }

    .filter-pill-btn.active {
        background: var(--daph-maroon) !important;
        color: #fff !important;
        border-color: var(--daph-maroon) !important;
        box-shadow: 0 4px 10px rgba(130, 1, 0, 0.25);
    }

    .table thead th {
        font-size: 0.78rem;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        background-color: #f8f9fa;
        color: #555;
        border-bottom: 2px solid #dee2e6;
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
                        <li class="breadcrumb-item"><a href="pending_approvals.php" class="text-decoration-none text-muted">Provincial Oversight</a></li>
                        <li class="breadcrumb-item active text-danger fw-bold" aria-current="page">Global HR Directory</li>
                    </ol>
                </nav>
                <h3 class="fw-bold text-dark mb-0 d-flex align-items-center gap-2">
                    <i class="bi bi-people-fill" style="color: #820100;"></i>
                    Global Human Resources Directory
                </h3>
                <p class="text-muted small mb-0">Province-wide personnel registry, executive appointments, and instant role oversight</p>
            </div>
            <div class="d-flex gap-2 mt-2 mt-md-0">
                <button type="button" class="btn text-light shadow-sm btn-sm px-3 d-flex align-items-center gap-2" style="background-color: #820100;" data-bs-toggle="modal" data-bs-target="#addEmployeeModal">
                    <i class="bi bi-person-plus-fill"></i>
                    <span>Register New Officer</span>
                </button>
                <button class="btn btn-outline-secondary shadow-sm btn-sm px-3 d-flex align-items-center gap-2" onclick="refreshDirectory()">
                    <i class="bi bi-arrow-clockwise"></i>
                    <span>Refresh</span>
                </button>
            </div>
        </div>

        <?php if (isset($_SESSION['msg'])): ?>
            <div class="alert alert-<?= htmlspecialchars($_SESSION['msg_type'] ?? 'info') ?> alert-dismissible fade show shadow-sm" role="alert">
                <i class="bi bi-check-circle-fill me-2"></i>
                <?= htmlspecialchars($_SESSION['msg']) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            <?php unset($_SESSION['msg'], $_SESSION['msg_type']); ?>
        <?php endif; ?>

        <!-- SECTION 1: KEY ROLE IDENTIFICATION CARDS -->
        <div class="row g-3 mb-4">

            <!-- 1. Subject Matter Specialist (SMS) -->
            <div class="col-xl-4 col-md-6">
                <div class="card shadow-sm border-0 key-role-card h-100" style="border-left: 4px solid #6f42c1 !important;">
                    <div class="card-body p-3">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <div class="d-flex align-items-center gap-2">
                                <div class="rounded-circle p-2 text-white d-flex align-items-center justify-content-center" style="width: 38px; height: 38px; background: #6f42c1;">
                                    <i class="bi bi-star-fill"></i>
                                </div>
                                <div>
                                    <h6 class="fw-bold mb-0 text-dark">Subject Matter Specialist</h6>
                                    <small class="text-muted">Top Provincial Technical Advisory</small>
                                </div>
                            </div>
                            <span class="badge badge-soft-purple px-2 py-1"><?= count($sms_officers) ?> Assigned</span>
                        </div>
                        <div class="mt-2 pt-2 border-top">
                            <?php if (!empty($sms_officers)): ?>
                                <?php foreach ($sms_officers as $sms): ?>
                                    <div class="d-flex justify-content-between align-items-center py-1">
                                        <div>
                                            <div class="fw-bold text-dark small"><?= htmlspecialchars($sms['full_name'] ?: $sms['username']) ?></div>
                                            <small class="text-muted"><i class="bi bi-envelope me-1"></i><?= htmlspecialchars($sms['email']) ?></small>
                                        </div>
                                        <button class="btn btn-sm btn-outline-purple py-0 px-2" style="font-size: 11px; border: 1px solid #6f42c1; color: #6f42c1;" onclick="applyRoleFilter('Subject Matter Specialist')">
                                            View
                                        </button>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <p class="text-muted small mb-0 fst-italic">No officer assigned as SMS.</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 2. Deputy Director H/Q (1 & 2) -->
            <div class="col-xl-4 col-md-6">
                <div class="card shadow-sm border-0 key-role-card h-100" style="border-left: 4px solid var(--daph-maroon) !important;">
                    <div class="card-body p-3">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <div class="d-flex align-items-center gap-2">
                                <div class="rounded-circle p-2 text-white d-flex align-items-center justify-content-center" style="width: 38px; height: 38px; background: var(--daph-maroon);">
                                    <i class="bi bi-building"></i>
                                </div>
                                <div>
                                    <h6 class="fw-bold mb-0 text-dark">Deputy Director H/Q (1 & 2)</h6>
                                    <small class="text-muted">Headquarters Executive Oversight</small>
                                </div>
                            </div>
                            <span class="badge badge-soft-maroon px-2 py-1"><?= count($dd_hq1_officers) + count($dd_hq2_officers) ?> Assigned</span>
                        </div>
                        <div class="mt-2 pt-2 border-top small">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <div>
                                    <span class="badge bg-secondary me-1" style="font-size: 9px;">H/Q-1</span>
                                    <strong class="text-dark">
                                        <?= !empty($dd_hq1_officers) ? htmlspecialchars(reset($dd_hq1_officers)['full_name'] ?: reset($dd_hq1_officers)['username']) : 'Vacant' ?>
                                    </strong>
                                </div>
                                <small class="text-muted"><?= !empty($dd_hq1_officers) ? htmlspecialchars(reset($dd_hq1_officers)['email']) : '' ?></small>
                            </div>
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <span class="badge bg-secondary me-1" style="font-size: 9px;">H/Q-2</span>
                                    <strong class="text-dark">
                                        <?= !empty($dd_hq2_officers) ? htmlspecialchars(reset($dd_hq2_officers)['full_name'] ?: reset($dd_hq2_officers)['username']) : 'Vacant' ?>
                                    </strong>
                                </div>
                                <small class="text-muted"><?= !empty($dd_hq2_officers) ? htmlspecialchars(reset($dd_hq2_officers)['email']) : '' ?></small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 3. Veterinary Surgeons -->
            <div class="col-xl-4 col-md-12">
                <div class="card shadow-sm border-0 key-role-card h-100" style="border-left: 4px solid #0d6efd !important;">
                    <div class="card-body p-3">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <div class="d-flex align-items-center gap-2">
                                <div class="rounded-circle p-2 text-white d-flex align-items-center justify-content-center" style="width: 38px; height: 38px; background: #0d6efd;">
                                    <i class="bi bi-shield-plus"></i>
                                </div>
                                <div>
                                    <h6 class="fw-bold mb-0 text-dark">Veterinary Surgeons</h6>
                                    <small class="text-muted">Field Clinical & Range Executives</small>
                                </div>
                            </div>
                            <span class="badge badge-soft-primary px-2 py-1"><?= count($vs_officers) ?> Officers</span>
                        </div>
                        <div class="mt-2 pt-2 border-top d-flex justify-content-between align-items-center">
                            <div class="small text-muted">
                                Active across ranges in <strong class="text-dark">Ampara, Batticaloa & Trincomalee</strong>
                            </div>
                            <button class="btn btn-sm btn-outline-primary py-0 px-2" style="font-size: 11px;" onclick="applyRoleFilter('Veterinary Surgeon')">
                                Filter All VS
                            </button>
                        </div>
                    </div>
                </div>
            </div>

        </div>

        <!-- SECTION 2: INTERACTIVE QUICK FILTER BAR -->
        <div class="card shadow-sm border-0 mb-3">
            <div class="card-body p-3">
                <div class="d-flex flex-wrap align-items-center justify-content-between gap-2">
                    <div class="d-flex flex-wrap align-items-center gap-2" id="quickFilterButtonGroup">
                        <span class="small fw-bold text-muted text-uppercase me-1"><i class="bi bi-funnel-fill me-1"></i>Quick Role Filter:</span>
                        <button type="button" class="filter-pill-btn active" data-filter="">
                            All Personnel <span class="badge bg-secondary rounded-pill ms-1"><?= count($all_officers) ?></span>
                        </button>
                        <button type="button" class="filter-pill-btn" data-filter="Subject Matter Specialist">
                            <i class="bi bi-star-fill text-warning me-1"></i>Subject Matter Specialist <span class="badge bg-secondary rounded-pill ms-1"><?= count($sms_officers) ?></span>
                        </button>
                        <button type="button" class="filter-pill-btn" data-filter="Deputy Director H/Q">
                            <i class="bi bi-building me-1"></i>Deputy Director H/Q <span class="badge bg-secondary rounded-pill ms-1"><?= count($dd_hq1_officers) + count($dd_hq2_officers) ?></span>
                        </button>
                        <button type="button" class="filter-pill-btn" data-filter="Veterinary Surgeon">
                            <i class="bi bi-shield-plus text-primary me-1"></i>Veterinary Surgeons <span class="badge bg-secondary rounded-pill ms-1"><?= count($vs_officers) ?></span>
                        </button>
                        <button type="button" class="filter-pill-btn" data-filter="District Deputy Director">
                            <i class="bi bi-geo-alt me-1"></i>District DDs <span class="badge bg-secondary rounded-pill ms-1"><?= count($district_dd_officers) ?></span>
                        </button>
                        <button type="button" class="filter-pill-btn" data-filter="Training Officer">
                            <i class="bi bi-mortarboard me-1"></i>Training Officers
                        </button>
                        <button type="button" class="filter-pill-btn" data-filter="Deputy Director (Farms)">
                            <i class="bi bi-flower1 me-1"></i>Farms DD
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- SECTION 3: GLOBAL HR DIRECTORY TABLE -->
        <div class="card shadow-sm border-0">
            <div class="card-body p-3">
                <div class="table-responsive">
                    <table id="globalHrTable" class="table table-hover align-middle w-100">
                        <thead>
                            <tr>
                                <th>Officer Name / Contact</th>
                                <th>Assigned Role</th>
                                <th>Official Designation</th>
                                <th>District / Scope</th>
                                <th>Facility / Range</th>
                                <th>Status</th>
                                <th class="text-center" style="width: 140px;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($all_officers as $officer):
                                $officer_role = $officer['role'];
                                $officer_name = !empty($officer['full_name']) ? $officer['full_name'] : $officer['username'];

                                // Clean role badge title
                                $role_display = ucwords(str_replace('_', ' ', $officer_role));
                                if ($officer_role === 'sms') $role_display = 'Subject Matter Specialist';
                                elseif ($officer_role === 'deputy_director_hq_1') $role_display = 'Deputy Director H/Q (1)';
                                elseif ($officer_role === 'deputy_director_hq_2') $role_display = 'Deputy Director H/Q (2)';
                                elseif ($officer_role === 'district_dd') $role_display = 'District Deputy Director';
                                elseif ($officer_role === 'veterinary_surgeon') $role_display = 'Veterinary Surgeon';
                                elseif ($officer_role === 'government_veterinary_surgeon') $role_display = 'Government Veterinary Surgeon';
                                elseif ($officer_role === 'additional_veterinary_surgeon') $role_display = 'Additional Veterinary Surgeon';
                                elseif ($officer_role === 'farms_dd') $role_display = 'Deputy Director (Farms)';

                                // Badge color mapping
                                $badge_class = 'badge-soft-maroon';
                                if ($officer_role === 'sms') $badge_class = 'badge-soft-purple';
                                elseif (strpos($officer_role, 'deputy_director_hq') !== false) $badge_class = 'badge-soft-maroon';
                                elseif (strpos($officer_role, 'veterinary') !== false) $badge_class = 'badge-soft-primary';
                                elseif ($officer_role === 'district_dd') $badge_class = 'badge-soft-warning';
                                elseif ($officer_role === 'training_officer') $badge_class = 'badge-soft-success';

                                // Location summary
                                $workstation = 'Headquarters / Provincial';
                                if (!empty($officer['range_name'])) {
                                    $workstation = $officer['range_name'] . ' Range';
                                } elseif (!empty($officer['farm_name'])) {
                                    $workstation = $officer['farm_name'] . ' (Regional Farm)';
                                } elseif (!empty($officer['training_center_name'])) {
                                    $workstation = $officer['training_center_name'] . ' (Training Center)';
                                }
                            ?>
                                <tr id="officer-row-<?= $officer['id'] ?>" data-role="<?= htmlspecialchars($role_display) ?>">
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="avatar rounded-circle bg-light border text-dark d-flex align-items-center justify-content-center me-2 fw-bold" style="width: 38px; height: 38px; font-size: 14px;">
                                                <?= strtoupper(substr($officer_name, 0, 1)) ?>
                                            </div>
                                            <div>
                                                <div class="fw-bold text-dark"><?= htmlspecialchars($officer_name) ?></div>
                                                <small class="text-muted d-block" style="font-size: 11px;">
                                                    <i class="bi bi-envelope me-1"></i><?= htmlspecialchars($officer['email'] ?: 'No email') ?>
                                                    <?php if (!empty($officer['phone'])): ?>
                                                        • <i class="bi bi-telephone ms-1 me-1"></i><?= htmlspecialchars($officer['phone']) ?>
                                                    <?php endif; ?>
                                                </small>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="badge <?= $badge_class ?> px-2 py-1" style="font-size: 11px;">
                                            <?= htmlspecialchars($role_display) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="fw-medium text-secondary small">
                                            <?= htmlspecialchars($officer['designation'] ?: '—') ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge bg-light text-dark border">
                                            <i class="bi bi-geo-alt me-1 text-danger"></i>
                                            <?= htmlspecialchars($officer['district_name'] ?: ($officer['district'] ?: 'Provincial')) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="small text-muted">
                                            <?= htmlspecialchars($workstation) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if (!empty($officer['is_active'])): ?>
                                            <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill px-2">
                                                <i class="bi bi-check-circle-fill me-1"></i>Active
                                            </span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary-subtle text-secondary rounded-pill px-2">Inactive</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center">
                                        <button type="button" class="btn btn-sm btn-outline-danger" title="Assign New Role / Designation"
                                            onclick="openAssignRoleModal(<?= htmlspecialchars(json_encode($officer), ENT_QUOTES, 'UTF-8') ?>)">
                                            <i class="bi bi-pencil-square me-1"></i>Assign Role
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    </main>
</div>

<!-- DEDICATED ADD EMPLOYEE MODAL (PD SPECIFIC) -->
<?php require_once __DIR__ . '/models/add_employee.php'; ?>

<!-- DEDICATED ASSIGN ROLE MODAL (PD SPECIFIC) -->
<?php require_once __DIR__ . '/models/assign_role_modal.php'; ?>

<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script src="../../../assets/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
<script src="../../../assets/js/sweetalert2.all.min.js"></script>

<script>
    var dataTable;

    $(document).ready(function() {
        dataTable = $('#globalHrTable').DataTable({
            "pageLength": 15,
            "order": [],
            "language": {
                "search": "",
                "searchPlaceholder": "Search officer, role, designation, district..."
            }
        });

        // Quick Role Filter Buttons
        $('#quickFilterButtonGroup .filter-pill-btn').on('click', function() {
            $('#quickFilterButtonGroup .filter-pill-btn').removeClass('active');
            $(this).addClass('active');

            var filterValue = $(this).data('filter');
            dataTable.column(1).search(filterValue).draw();
        });

        // Live preview of the assignment notification message in Role Modal
        function updateNotificationPreview() {
            var roleSelect = $('#modal_role option:selected').text();
            var designation = $('#modal_designation').val().trim();
            var title = designation || roleSelect || '[Position/Role Name]';
            $('#preview_notification_text').text('You are assigned as the ' + title);
        }

        $('#modal_role, #modal_designation').on('input change', updateNotificationPreview);

        // Submit role reassignment via AJAX
        $('#pdAssignRoleForm').on('submit', function(e) {
            e.preventDefault();
            var $btn = $('#saveAssignmentBtn');
            $btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span>Saving & Notifying...');

            $.ajax({
                url: 'processors/update_user_role.php',
                type: 'POST',
                data: $(this).serialize(),
                dataType: 'json',
                success: function(response) {
                    $btn.prop('disabled', false).html('<i class="bi bi-check2-circle me-1"></i>Confirm & Dispatch Notification');
                    if (response.success) {
                        $('#assignRoleModal').modal('hide');
                        Swal.fire({
                            icon: 'success',
                            title: 'Role Assigned & Notified',
                            html: '<p class="mb-2">' + response.message + '</p><div class="p-2 bg-light border rounded small font-monospace">Notification Dispatched: <strong>' + response.notification_msg + '</strong></div>',
                            confirmButtonColor: '#820100'
                        }).then(() => {
                            location.reload();
                        });
                    } else {
                        Swal.fire('Error', response.message || 'Operation failed.', 'error');
                    }
                },
                error: function(xhr) {
                    $btn.prop('disabled', false).html('<i class="bi bi-check2-circle me-1"></i>Confirm & Dispatch Notification');
                    Swal.fire('Server Error', 'Failed to communicate with server. Please try again.', 'error');
                }
            });
        });
    });

    function applyRoleFilter(roleString) {
        $('#quickFilterButtonGroup .filter-pill-btn').removeClass('active');
        var matchingBtn = $('#quickFilterButtonGroup .filter-pill-btn').filter(function() {
            return $(this).data('filter') === roleString;
        });
        if (matchingBtn.length) {
            matchingBtn.addClass('active');
        }
        dataTable.column(1).search(roleString).draw();
        $('html, body').animate({
            scrollTop: $("#globalHrTable").offset().top - 120
        }, 300);
    }

    function refreshDirectory() {
        location.reload();
    }

    function openAssignRoleModal(officer) {
        $('#modal_user_id').val(officer.id);
        $('#modal_full_name').val(officer.full_name || officer.username);
        $('#modal_officer_name_display').text(officer.full_name || officer.username);
        $('#modal_officer_email_display').text(officer.email || 'No email registered');
        $('#modal_current_role_badge').text(officer.role.replace(/_/g, ' ').toUpperCase());

        $('#modal_role').val(officer.role);
        $('#modal_designation').val(officer.designation || '');
        $('#modal_district_id').val(officer.district_id || '');
        $('#modal_range_id').val(officer.range_id || '');
        $('#modal_farm_id').val(officer.farm_id || '');
        $('#modal_training_center_id').val(officer.training_center_id || '');
        $('#modal_service_number').val(officer.service_number || officer.emp_id || '');

        var title = officer.designation || $('#modal_role option:selected').text();
        $('#preview_notification_text').text('You are assigned as the ' + (title || '...'));

        $('#assignRoleModal').modal('show');
    }
</script>

<?php require_once '../../../includes/footer.php'; ?>
