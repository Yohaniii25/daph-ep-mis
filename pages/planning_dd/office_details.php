<?php
// pages/planning_dd/office_details.php
// Office Details & Veterinary Range Officers Administrative Summary (Province-Wide)

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$allowed_roles = ['deputy_director_hq_1', 'administrator', 'provincial_director'];
if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], $allowed_roles)) {
    die("Access denied. Unauthorized role footprint.");
}

require_once __DIR__ . '/../../config/db_connect.php';
require_once __DIR__ . '/../../includes/header.php';

// =========================================================================
// GLOBAL PROVINCE-WIDE AGGREGATION: ALL 45 VETERINARY RANGE OFFICES
// =========================================================================
$sql = "
    SELECT 
        vr.id AS range_id,
        vr.name AS range_name,
        vr.code AS range_code,
        vr.is_active AS is_active,
        d.id AS district_id,
        d.name AS district_name,
        u.id AS vs_id,
        u.full_name AS vs_name,
        u.email AS vs_email,
        u.phone AS vs_phone,
        u.emp_id,
        u.service_number,
        u.designation,
        u.appointment_date,
        u.registered_date,
        COALESCE(staff_sub.staff_count, 0) AS staff_count,
        COALESCE(land_sub.land_count, 0) AS land_count,
        COALESCE(bldg_sub.bldg_count, 0) AS bldg_count,
        COALESCE(veh_sub.veh_count, 0) AS veh_count,
        COALESCE(mach_sub.mach_count, 0) AS mach_count,
        COALESCE(inst_sub.inst_count, 0) AS inst_count,
        COALESCE(furn_sub.furn_count, 0) AS furn_count,
        COALESCE(cnt_sub.cnt_count, 0) AS cnt_count
    FROM veterinary_ranges vr
    JOIN districts d ON vr.district_id = d.id
    LEFT JOIN users u ON vr.id = u.range_id AND u.role IN ('veterinary_surgeon', 'government_veterinary_surgeon') AND u.is_active = 1
    LEFT JOIN (
        SELECT range_id, COUNT(*) AS staff_count 
        FROM users 
        WHERE is_active = 1 AND range_id IS NOT NULL 
        GROUP BY range_id
    ) staff_sub ON vr.id = staff_sub.range_id
    LEFT JOIN (
        SELECT range_id, COUNT(*) AS land_count 
        FROM land_assets 
        GROUP BY range_id
    ) land_sub ON vr.id = land_sub.range_id
    LEFT JOIN (
        SELECT la.range_id, COUNT(bi.id) AS bldg_count 
        FROM building_inventories bi 
        JOIN land_assets la ON bi.land_asset_id = la.id 
        GROUP BY la.range_id
    ) bldg_sub ON vr.id = bldg_sub.range_id
    LEFT JOIN (
        SELECT range_id, COUNT(*) AS veh_count 
        FROM registered_vehicles 
        GROUP BY range_id
    ) veh_sub ON vr.id = veh_sub.range_id
    LEFT JOIN (
        SELECT range_id, COUNT(*) AS mach_count 
        FROM machinery_assets 
        GROUP BY range_id
    ) mach_sub ON vr.id = mach_sub.range_id
    LEFT JOIN (
        SELECT range_id, COUNT(*) AS inst_count 
        FROM instrument_assets 
        GROUP BY range_id
    ) inst_sub ON vr.id = inst_sub.range_id
    LEFT JOIN (
        SELECT range_id, COUNT(*) AS furn_count 
        FROM furniture_assets 
        GROUP BY range_id
    ) furn_sub ON vr.id = furn_sub.range_id
    LEFT JOIN (
        SELECT range_id, COUNT(*) AS cnt_count 
        FROM counterfoil_assets 
        GROUP BY range_id
    ) cnt_sub ON vr.id = cnt_sub.range_id
    ORDER BY d.name ASC, vr.name ASC
";
$offices = $mysqli->query($sql)->fetch_all(MYSQLI_ASSOC);

// Province-Wide Global Totals
$total_offices   = count($offices);
$active_offices  = count(array_filter($offices, fn($o) => $o['is_active'] == 1));
$appointed_vs    = count(array_filter($offices, fn($o) => !empty($o['vs_name'])));
$total_staff     = array_sum(array_column($offices, 'staff_count'));
$total_vehicles  = array_sum(array_column($offices, 'veh_count'));
$total_lands     = array_sum(array_column($offices, 'land_count'));
$total_buildings = array_sum(array_column($offices, 'bldg_count'));
$total_mach_inst = array_sum(array_column($offices, 'mach_count')) + array_sum(array_column($offices, 'inst_count'));
$total_furniture = array_sum(array_column($offices, 'furn_count'));
?>

<div class="container-fluid px-4 py-3">

    <!-- Header & Breadcrumb -->
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 pb-2 border-bottom">
        <div>
            <div class="d-flex align-items-center gap-2">
                <h2 class="fw-bold mb-0 text-dark">Veterinary Range Officers Office Details</h2>
                <span class="badge bg-primary px-3 py-2 rounded-pill fw-semibold">HQ-1 Office Summary</span>
                <span class="badge bg-dark px-3 py-2 rounded-pill fw-normal">All 45 Range Offices</span>
            </div>
            <p class="text-muted small mb-0 mt-1">
                Global administrative profiles, Veterinary Range Officer HR deployments, and assigned office inventory across the Eastern Province.
            </p>
        </div>
        <div class="d-flex align-items-center gap-2 mt-3 mt-md-0">
            <a href="range_details.php" class="btn btn-outline-secondary btn-sm shadow-sm">
                <i class="bi bi-grid-3x3-gap-fill me-1"></i> Range Details Hub
            </a>
            <button type="button" class="btn btn-dark btn-sm shadow-sm" onclick="window.print()">
                <i class="bi bi-printer me-1"></i> Print Directory
            </button>
        </div>
    </div>

    <!-- 1. GLOBAL OFFICE INFRASTRUCTURE KPI CARDS -->
    <div class="row g-3 mb-4">
        <!-- Range Offices -->
        <div class="col-xl-3 col-md-6">
            <div class="card border-0 shadow-sm rounded-4 h-100 p-3 border-start border-primary border-4 bg-white">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <small class="text-muted text-uppercase fw-bold">Total Range Offices</small>
                        <h3 class="fw-bold text-dark mb-0 mt-1"><?= $total_offices ?></h3>
                        <small class="text-success"><i class="bi bi-check-circle-fill"></i> <?= $active_offices ?> Active Across 3 Districts</small>
                    </div>
                    <div class="bg-primary bg-opacity-10 text-primary p-3 rounded-circle">
                        <i class="bi bi-building fs-3"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Range Officers Appointed -->
        <div class="col-xl-3 col-md-6">
            <div class="card border-0 shadow-sm rounded-4 h-100 p-3 border-start border-success border-4 bg-white">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <small class="text-muted text-uppercase fw-bold">Range Veterinary Officers</small>
                        <h3 class="fw-bold text-success mb-0 mt-1"><?= $appointed_vs ?> <small class="fs-6 text-muted">Surgeons</small></h3>
                        <small class="text-muted"><?= round(($appointed_vs / max($total_offices, 1)) * 100) ?>% Range Officer Staffing</small>
                    </div>
                    <div class="bg-success bg-opacity-10 text-success p-3 rounded-circle">
                        <i class="bi bi-person-badge-fill fs-3"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Range Fleet Vehicles -->
        <div class="col-xl-3 col-md-6">
            <div class="card border-0 shadow-sm rounded-4 h-100 p-3 border-start border-warning border-4 bg-white">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <small class="text-muted text-uppercase fw-bold">Assigned Fleet Vehicles</small>
                        <h3 class="fw-bold text-dark mb-0 mt-1"><?= number_format($total_vehicles) ?> <small class="fs-6 text-muted">Vehicles</small></h3>
                        <small class="text-muted">Field motorbikes, double cabs & vans</small>
                    </div>
                    <div class="bg-warning bg-opacity-10 text-warning p-3 rounded-circle">
                        <i class="bi bi-truck fs-3"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Immovable Infrastructure -->
        <div class="col-xl-3 col-md-6">
            <div class="card border-0 shadow-sm rounded-4 h-100 p-3 border-start border-info border-4 bg-white">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <small class="text-muted text-uppercase fw-bold">Lands & Buildings</small>
                        <h3 class="fw-bold text-info mb-0 mt-1"><?= $total_lands ?> <small class="fs-6 text-muted">Lands</small> / <?= $total_buildings ?> <small class="fs-6 text-muted">Bldgs</small></h3>
                        <small class="text-muted">Government immovable assets</small>
                    </div>
                    <div class="bg-info bg-opacity-10 text-info p-3 rounded-circle">
                        <i class="bi bi-houses fs-3"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- 2. MASTER DIRECTORY: RANGE OFFICERS & OFFICE RESOURCES TABLE -->
    <div class="card border-0 shadow-sm rounded-4 mb-4">
        <div class="card-header bg-white border-0 py-3 px-4 d-flex flex-wrap justify-content-between align-items-center gap-3">
            <div>
                <h5 class="fw-bold mb-0 text-dark">
                    <i class="bi bi-table me-2 text-primary"></i>Veterinary Range Officers & Office Resources Directory
                </h5>
                <small class="text-muted">Official directory of Range Officers, contact channels, and allocated office resources across all 45 Ranges</small>
            </div>

            <!-- Quick District Filter Buttons -->
            <div class="btn-group btn-group-sm" role="group" id="districtFilterGroup">
                <button type="button" class="btn btn-outline-dark active filter-btn" data-filter="">All Districts (<?= count($offices) ?>)</button>
                <button type="button" class="btn btn-outline-dark filter-btn" data-filter="Ampara">Ampara (20)</button>
                <button type="button" class="btn btn-outline-dark filter-btn" data-filter="Batticaloa">Batticaloa (14)</button>
                <button type="button" class="btn btn-outline-dark filter-btn" data-filter="Trincomalee">Trincomalee (11)</button>
            </div>
        </div>

        <div class="card-body px-4 pb-4">
            <div class="table-responsive">
                <table id="officeDetailsTable" class="table table-hover align-middle w-100" style="font-size: 0.92rem;">
                    <thead class="table-light">
                        <tr>
                            <th class="text-center" style="width: 70px;">Code</th>
                            <th>Veterinary Range</th>
                            <th>District</th>
                            <th>Range Veterinary Officer</th>
                            <th>Contact Phone</th>
                            <th>Contact Email</th>
                            <th class="text-center">Staff</th>
                            <th class="text-center">Lands / Bldgs</th>
                            <th class="text-center">Vehicles</th>
                            <th class="text-center">Machinery / Inst</th>
                            <th class="text-center">Furniture</th>
                            <th class="text-center" style="width: 80px;">Status</th>
                            <th class="text-center" style="width: 70px;">Profile</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($offices as $o): 
                            $dist_badge = ($o['district_name'] === 'Ampara') ? 'bg-primary' : (($o['district_name'] === 'Batticaloa') ? 'bg-success' : 'bg-warning text-dark');
                        ?>
                            <tr>
                                <td class="text-center font-monospace fw-bold text-secondary"><?= htmlspecialchars($o['range_code'] ?: 'R' . str_pad($o['range_id'], 3, '0', STR_PAD_LEFT)) ?></td>
                                <td><strong class="text-dark"><?= htmlspecialchars($o['range_name']) ?></strong></td>
                                <td><span class="badge <?= $dist_badge ?> bg-opacity-75 rounded-pill px-2 py-1"><?= htmlspecialchars($o['district_name']) ?></span></td>
                                <td>
                                    <?php if (!empty($o['vs_name'])): ?>
                                        <div class="d-flex align-items-center">
                                            <div class="avatar-sm bg-primary bg-opacity-10 text-primary rounded-circle d-flex align-items-center justify-content-center me-2" style="width: 28px; height: 28px;">
                                                <i class="bi bi-person-fill"></i>
                                            </div>
                                            <div>
                                                <div class="fw-semibold text-dark"><?= htmlspecialchars($o['vs_name']) ?></div>
                                                <small class="text-muted"><?= htmlspecialchars($o['designation'] ?: 'Veterinary Surgeon') ?></small>
                                            </div>
                                        </div>
                                    <?php else: ?>
                                        <span class="badge bg-secondary bg-opacity-25 text-secondary rounded-pill px-2 py-1"><i class="bi bi-person-x me-1"></i>Unassigned</span>
                                    <?php endif; ?>
                                </td>
                                <td class="small text-dark font-monospace"><?= htmlspecialchars($o['vs_phone'] ?: 'N/A') ?></td>
                                <td class="small text-muted"><?= htmlspecialchars($o['vs_email'] ?: 'N/A') ?></td>
                                <td class="text-center font-monospace fw-bold text-dark"><?= number_format($o['staff_count']) ?></td>
                                <td class="text-center font-monospace text-muted"><?= $o['land_count'] ?> / <?= $o['bldg_count'] ?></td>
                                <td class="text-center font-monospace fw-bold <?= $o['veh_count'] > 0 ? 'text-primary' : 'text-muted' ?>"><?= number_format($o['veh_count']) ?></td>
                                <td class="text-center font-monospace text-muted"><?= $o['mach_count'] + $o['inst_count'] ?></td>
                                <td class="text-center font-monospace text-muted"><?= number_format($o['furn_count']) ?></td>
                                <td class="text-center">
                                    <?php if ($o['is_active']): ?>
                                        <span class="badge bg-success bg-opacity-10 text-success rounded-pill px-2 py-1">Active</span>
                                    <?php else: ?>
                                        <span class="badge bg-danger bg-opacity-10 text-danger rounded-pill px-2 py-1">Inactive</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center">
                                    <button type="button" 
                                            class="btn btn-sm btn-outline-primary rounded-circle" 
                                            title="View Office Profile"
                                            onclick="showOfficeModal(<?= htmlspecialchars(json_encode($o)) ?>)">
                                        <i class="bi bi-eye"></i>
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</div>

<!-- Range Office Profile Modal -->
<div class="modal fade" id="officeDetailsModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content rounded-4 border-0 shadow">
            <div class="modal-header bg-dark text-white rounded-top-4 py-3">
                <h5 class="modal-title fw-bold" id="modalOfficeTitle">Range Office Profile</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4" id="modalOfficeBody">
                <!-- Injected via JS -->
            </div>
            <div class="modal-footer bg-light rounded-bottom-4 py-2">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>

<script>
$(document).ready(function() {
    var table = $('#officeDetailsTable').DataTable({
        pageLength: 15,
        lengthMenu: [15, 25, 45, 100],
        order: [[2, 'asc'], [1, 'asc']],
        responsive: true,
        dom: '<"d-flex flex-wrap justify-content-between align-items-center mb-3"Bf>rt<"d-flex flex-wrap justify-content-between align-items-center mt-3"ip>',
        buttons: [
            {
                extend: 'csv',
                text: '<i class="bi bi-filetype-csv me-1"></i> Export CSV',
                className: 'btn btn-sm btn-success rounded-pill me-2',
                exportOptions: { columns: [0, 1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11] }
            },
            {
                extend: 'print',
                text: '<i class="bi bi-printer me-1"></i> Print Directory',
                className: 'btn btn-sm btn-dark rounded-pill',
                exportOptions: { columns: [0, 1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11] }
            }
        ],
        language: {
            search: "_INPUT_",
            searchPlaceholder: "Search range, officer, or district..."
        }
    });

    // Custom District Quick Filter Buttons
    $('#districtFilterGroup .filter-btn').on('click', function() {
        $('#districtFilterGroup .filter-btn').removeClass('active');
        $(this).addClass('active');
        var filterVal = $(this).data('filter');
        table.column(2).search(filterVal ? '^' + filterVal + '$' : '', true, false).draw();
    });
});

// Show Range Office Details Modal
function showOfficeModal(office) {
    $('#modalOfficeTitle').text(office.range_name + ' (' + (office.range_code || 'R' + office.range_id) + ') Office Details');
    
    var officerHtml = office.vs_name ? `
        <div class="card bg-light border-0 p-3 rounded-3 mb-3">
            <h6 class="fw-bold text-dark mb-2"><i class="bi bi-person-badge-fill me-2 text-primary"></i>Range Veterinary Officer Profile</h6>
            <div class="row g-2">
                <div class="col-md-6">
                    <small class="text-muted d-block">Officer Name</small>
                    <strong class="text-dark">${office.vs_name}</strong>
                </div>
                <div class="col-md-6">
                    <small class="text-muted d-block">Designation</small>
                    <span>${office.designation || 'Veterinary Surgeon'}</span>
                </div>
                <div class="col-md-6">
                    <small class="text-muted d-block">Employee / Service ID</small>
                    <span class="font-monospace">${office.emp_id || office.service_number || 'N/A'}</span>
                </div>
                <div class="col-md-6">
                    <small class="text-muted d-block">Contact Phone</small>
                    <span class="font-monospace">${office.vs_phone || 'N/A'}</span>
                </div>
                <div class="col-md-6">
                    <small class="text-muted d-block">Email Address</small>
                    <span>${office.vs_email || 'N/A'}</span>
                </div>
                <div class="col-md-6">
                    <small class="text-muted d-block">Appointment Date</small>
                    <span>${office.appointment_date || office.registered_date || 'N/A'}</span>
                </div>
            </div>
        </div>
    ` : `
        <div class="alert alert-warning py-2 mb-3 small">
            <i class="bi bi-exclamation-circle me-1"></i> No permanent Range Veterinary Officer currently assigned to this range.
        </div>
    `;

    var content = `
        <div class="row g-3">
            <div class="col-md-6">
                <small class="text-muted d-block">Administrative District</small>
                <h5 class="fw-bold text-dark mb-0">${office.district_name} District</h5>
            </div>
            <div class="col-md-6 text-md-end">
                <small class="text-muted d-block">Operational Status</small>
                <span class="badge ${office.is_active == 1 ? 'bg-success' : 'bg-danger'} px-3 py-2 rounded-pill">
                    ${office.is_active == 1 ? 'Active Operational' : 'Inactive'}
                </span>
            </div>

            <div class="col-12">
                ${officerHtml}
            </div>

            <div class="col-12">
                <h6 class="fw-bold text-dark mb-2"><i class="bi bi-archive-fill me-2 text-primary"></i>Assigned Office Inventory & Assets</h6>
                <div class="row g-2 text-center">
                    <div class="col-3 bg-light p-2 rounded-3 border">
                        <small class="text-muted d-block">Total Staff</small>
                        <strong class="text-dark fs-5">${office.staff_count}</strong>
                    </div>
                    <div class="col-3 bg-light p-2 rounded-3 border">
                        <small class="text-muted d-block">Vehicles</small>
                        <strong class="text-primary fs-5">${office.veh_count}</strong>
                    </div>
                    <div class="col-3 bg-light p-2 rounded-3 border">
                        <small class="text-muted d-block">Lands / Bldgs</small>
                        <strong class="text-dark fs-5">${office.land_count} / ${office.bldg_count}</strong>
                    </div>
                    <div class="col-3 bg-light p-2 rounded-3 border">
                        <small class="text-muted d-block">Equipment</small>
                        <strong class="text-success fs-5">${parseInt(office.mach_count) + parseInt(office.inst_count)}</strong>
                    </div>
                </div>
            </div>
        </div>
    `;

    $('#modalOfficeBody').html(content);
    var modal = new bootstrap.Modal(document.getElementById('officeDetailsModal'));
    modal.show();
}
</script>
