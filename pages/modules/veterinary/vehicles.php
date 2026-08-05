<?php
session_start();
require_once '../../../config/db_connect.php';

if (!isset($_SESSION['logged_in']) || $_SESSION['role'] !== 'veterinary_surgeon') {
    header("Location: ../../../../index.php");
    exit();
}

$user_id = $_SESSION['user_id'] ?? null;
$range_id = $_SESSION['range_id'] ?? null;
$range_name = $_SESSION['range_name'] ?? 'Your Range';
$district_id = $_SESSION['district_id'] ?? null;
$district_name = 'Your District';

// Fetch dynamic regional names matching current session boundaries
if (!empty($district_id)) {
    $dst_stmt = $mysqli->prepare("SELECT name FROM districts WHERE id = ?");
    $dst_stmt->bind_param("i", $district_id);
    $dst_stmt->execute();
    $dst_res = $dst_stmt->get_result();
    if ($row = $dst_res->fetch_assoc()) $district_name = $row['name'];
    $dst_stmt->close();
}
if (!empty($range_id)) {
    $rng_stmt = $mysqli->prepare("SELECT name FROM veterinary_ranges WHERE id = ?");
    $rng_stmt->bind_param("i", $range_id);
    $rng_stmt->execute();
    $rng_res = $rng_stmt->get_result();
    if ($row = $rng_res->fetch_assoc()) $range_name = $row['name'];
    $rng_stmt->close();
}

require_once '../../../includes/header.php';
?>

<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">


        
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h3 class="fw-bold text-dark">Fleet &amp; Vehicle Asset Registry</h3>
                <p class="text-muted small">
                    Jurisdiction Range: <strong class="text-dark"><?= htmlspecialchars($range_name) ?></strong> | 
                    District: <strong class="text-dark"><?= htmlspecialchars($district_name) ?></strong>
                </p>
            </div>
            <div>
                <button class="btn text-white shadow-sm me-2" style="background-color: #b08723;" data-bs-toggle="modal" data-bs-target="#addVehicleModal">
                    <i class="bi bi-plus-circle-fill me-2"></i>Register New Vehicle
                </button>
                <button class="btn btn-dark shadow-sm" data-bs-toggle="modal" data-bs-target="#addRepairModal">
                    <i class="bi bi-wrench-adjustable me-2"></i>Log Repair Work
                </button>
            </div>
        </div>

        <ul class="nav nav-pills mb-4 bg-white p-2 rounded shadow-sm" id="vehicleTabs" role="tablist">
            <li class="nav-item">
                <button class="nav-link active" id="fleet-tab" data-bs-toggle="tab" data-bs-target="#fleet-content" type="button" role="tab" style="--bs-nav-pills-link-active-bg: #b08723;">
                    <i class="bi bi-truck me-2"></i>Active Vehicle Details
                </button>
            </li>
            <li class="nav-item">
                <button class="nav-link" id="repairs-tab" data-bs-toggle="tab" data-bs-target="#repairs-content" type="button" role="tab" style="--bs-nav-pills-link-active-bg: #b08723;">
                    <i class="bi bi-tools me-2"></i>Maintenance &amp; Repair Logs
                </button>
            </li>
        </ul>

        <div class="tab-content" id="vehicleTabsContent">
            
            <div class="tab-pane fade show active" id="fleet-content" role="tabpanel">
                <div class="card shadow-sm border-0">
                    <div class="card-body p-4">
                        <div class="table-responsive">
                            <table id="vehiclesTable" class="table table-hover align-middle w-100">
                                <thead class="table-light text-uppercase small">
                                    <tr>
                                        <th>Vehicle Type</th>
                                        <th>Vehicle Number</th>
                                        <th>Chassis Number</th>
                                        <th>Current Condition</th>
                                        <th>Other Relevant Details</th>
                                        <th class="text-center">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $fleet_stmt = $mysqli->prepare("SELECT * FROM registered_vehicles WHERE district_id = ? AND range_id = ? AND is_active = 1 ORDER BY id DESC");
                                    $fleet_stmt->bind_param("ii", $district_id, $range_id);
                                    $fleet_stmt->execute();
                                    $fleet_res = $fleet_stmt->get_result();
                                    
                                    $vehicles_cache = [];
                                    while ($row = $fleet_res->fetch_assoc()):
                                        $vehicles_cache[] = $row;
                                    ?>
                                    <tr id="vehicle-row-<?= $row['id'] ?>">
                                        <td class="fw-bold text-dark"><?= htmlspecialchars($row['vehicle_type']) ?></td>
                                        <td><span class="badge bg-dark text-light px-2 py-1 font-monospace"><?= htmlspecialchars($row['vehicle_number']) ?></span></td>
                                        <td><span class="text-secondary small font-monospace fw-semibold"><?= htmlspecialchars($row['chassis_number']) ?></span></td>
                                        <td>
                                            <?php 
                                            $cond_class = ($row['current_condition'] === 'Running') ? 'bg-success' : (($row['current_condition'] === 'Needs Repair') ? 'bg-warning text-dark' : 'bg-secondary');
                                            ?>
                                            <span class="badge <?= $cond_class ?> rounded-pill px-2"><?= htmlspecialchars($row['current_condition']) ?></span>
                                        </td>
                                        <td><small class="text-muted"><?= htmlspecialchars($row['other_details']) ?></small></td>
                                        <td class="text-center">
                                            <button class="btn btn-sm btn-outline-danger" onclick="handleVehicleDelete(<?= $row['id'] ?>)">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </td>
                                    </tr>
                                    <?php endwhile; $fleet_stmt->close(); ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <div class="tab-pane fade" id="repairs-content" role="tabpanel">
                <div class="card shadow-sm border-0">
                    <div class="card-body p-4">
                        <div class="table-responsive">
                            <table id="repairsTable" class="table table-hover align-middle w-100">
                                <thead class="table-light text-uppercase small">
                                    <tr>
                                        <th>Repair Date</th>
                                        <th>Vehicle Number</th>
                                        <th>Repair Done</th>
                                        <th>Description of Repair</th>
                                        <th>Place of Repair</th>
                                        <th class="text-end">Amount (LKR)</th>
                                        <th class="text-center">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $rep_stmt = $mysqli->prepare("
                                        SELECT vr.*, rv.vehicle_number 
                                        FROM vehicle_repairs vr
                                        JOIN registered_vehicles rv ON vr.vehicle_id = rv.id
                                        WHERE rv.district_id = ? AND rv.range_id = ? AND vr.is_active = 1
                                        ORDER BY vr.repair_date DESC
                                    ");
                                    $rep_stmt->bind_param("ii", $district_id, $range_id);
                                    $rep_stmt->execute();
                                    $rep_res = $rep_stmt->get_result();

                                    while ($row = $rep_res->fetch_assoc()):
                                    ?>
                                    <tr id="repair-row-<?= $row['id'] ?>">
                                        <td class="fw-semibold text-secondary"><?= htmlspecialchars($row['repair_date']) ?></td>
                                        <td><span class="badge bg-light text-dark border font-monospace"><?= htmlspecialchars($row['vehicle_number']) ?></span></td>
                                        <td class="fw-bold text-dark"><?= htmlspecialchars($row['repair_done']) ?></td>
                                        <td><small class="text-muted"><?= htmlspecialchars($row['repair_description']) ?></small></td>
                                        <td><span class="small"><?= htmlspecialchars($row['place_of_repair']) ?></span></td>
                                        <td class="text-end fw-bold text-dark"><?= number_format($row['amount'], 2) ?></td>
                                        <td class="text-center">
                                            <button class="btn btn-sm btn-outline-danger" onclick="handleRepairDelete(<?= $row['id'] ?>)">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </td>
                                    </tr>
                                    <?php endwhile; $rep_stmt->close(); ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </main>
</div>

<!-- include models -->
<?php include 'models/add_vehicle.php'; ?>
<?php include 'models/add_repair_vehicle.php'; ?>



<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
    var fleetTable, repairsTable;
    $(document).ready(function() {
        fleetTable = $('#vehiclesTable').DataTable({ "pageLength": 10 });
        repairsTable = $('#repairsTable').DataTable({ "pageLength": 10, "order": [[0, "desc"]] });

        // Fleet Entry Processing
        $('#addVehicleForm').on('submit', function(e) {
            e.preventDefault();
            $.ajax({
                url: 'processors/save_vehicle.php',
                type: 'POST',
                data: $(this).serialize(),
                dataType: 'json',
                success: function(res) {
                    if(res.success) {
                        Swal.fire('Registered!', res.message, 'success').then(() => { location.reload(); });
                    } else { Swal.fire('Error', res.message, 'error'); }
                }
            });
        });

        // Maintenance Operation Logging Processing
        $('#addRepairForm').on('submit', function(e) {
            e.preventDefault();
            $.ajax({
                url: 'processors/save_vehicle_repair.php',
                type: 'POST',
                data: $(this).serialize(),
                dataType: 'json',
                success: function(res) {
                    if(res.success) {
                        Swal.fire('Logged!', res.message, 'success').then(() => { location.reload(); });
                    } else { Swal.fire('Error', res.message, 'error'); }
                }
            });
        });
    });

    function handleVehicleDelete(id) {
        Swal.fire({
            title: 'Delete Fleet Asset?',
            text: "This operation will drop the active record line data.",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#b08723',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Yes, Delete'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: 'processors/delete_vehicle.php',
                    type: 'POST',
                    data: { id: id },
                    dataType: 'json',
                    success: function(res) {
                        if(res.success) {
                            Swal.fire('Removed!', res.message, 'success');
                            fleetTable.row('#vehicle-row-' + id).remove().draw(false);
                        } else { Swal.fire('Failed', res.message, 'error'); }
                    }
                });
            }
        });
    }

    function handleRepairDelete(id) {
        Swal.fire({
            title: 'Scrub Repair Entry?',
            text: "Permanently drop this log row configuration statement item?",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#212529',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Yes, Purge'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: 'processors/delete_vehicle_repair.php',
                    type: 'POST',
                    data: { id: id },
                    dataType: 'json',
                    success: function(res) {
                        if(res.success) {
                            Swal.fire('Cleared!', res.message, 'success');
                            repairsTable.row('#repair-row-' + id).remove().draw(false);
                        } else { Swal.fire('Failed', res.message, 'error'); }
                    }
                });
            }
        });
    }
</script>

<?php require_once '../../../includes/footer.php'; ?>