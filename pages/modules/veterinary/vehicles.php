<?php
session_start();
require_once '../../../config/db_connect.php';


$allowed_roles = ['veterinary_surgeon', 'government_veterinary_surgeon', 'additional_veterinary_surgeon', 'district_dd', 'provincial_director', 'administrator'];
if (!isset($_SESSION['logged_in']) || !in_array($_SESSION['role'], $allowed_roles)) {
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

// Global Month & Vehicle Filter Settings
$selected_month = filter_input(INPUT_GET, 'month', FILTER_DEFAULT) ?? date('Y-m');
if (!preg_match('/^\d{4}-\d{2}$/', $selected_month)) {
    $selected_month = date('Y-m');
}
$selected_vehicle_id = filter_input(INPUT_GET, 'vehicle_id', FILTER_VALIDATE_INT) ?: 0;
$active_tab = filter_input(INPUT_GET, 'tab', FILTER_DEFAULT) ?: 'fleet';

// 1. Fetch Registered Vehicles for this Range / District
if (!empty($range_id)) {
    $fleet_stmt = $mysqli->prepare("SELECT * FROM registered_vehicles WHERE district_id = ? AND range_id = ? AND is_active = 1 ORDER BY id DESC");
    $fleet_stmt->bind_param("ii", $district_id, $range_id);
} elseif (!empty($district_id)) {
    $fleet_stmt = $mysqli->prepare("SELECT * FROM registered_vehicles WHERE district_id = ? AND is_active = 1 ORDER BY id DESC");
    $fleet_stmt->bind_param("i", $district_id);
} else {
    $fleet_stmt = $mysqli->prepare("SELECT * FROM registered_vehicles WHERE is_active = 1 ORDER BY id DESC");
}
$fleet_stmt->execute();
$fleet_res = $fleet_stmt->get_result();
$vehicles_cache = [];
$fleet_running_count = 0;
$fleet_repair_count = 0;
while ($row = $fleet_res->fetch_assoc()) {
    $vehicles_cache[] = $row;
    if ($row['current_condition'] === 'Running') {
        $fleet_running_count++;
    } else {
        $fleet_repair_count++;
    }
}
$fleet_stmt->close();

// 2. Fetch Running Chart Trips for selected month
$rc_where = "rc.is_active = 1 AND DATE_FORMAT(rc.trip_date, '%Y-%m') = ?";
$rc_types = "s";
$rc_params = [$selected_month];

if (!empty($range_id)) {
    $rc_where .= " AND rv.range_id = ?";
    $rc_types .= "i";
    $rc_params[] = $range_id;
} elseif (!empty($district_id)) {
    $rc_where .= " AND rv.district_id = ?";
    $rc_types .= "i";
    $rc_params[] = $district_id;
}

if ($selected_vehicle_id > 0) {
    $rc_where .= " AND rc.vehicle_id = ?";
    $rc_types .= "i";
    $rc_params[] = $selected_vehicle_id;
}

$rc_sql = "
    SELECT rc.*, rv.vehicle_number, rv.vehicle_type 
    FROM vehicle_running_charts rc
    JOIN registered_vehicles rv ON rc.vehicle_id = rv.id
    WHERE {$rc_where}
    ORDER BY rc.trip_date DESC, rc.id DESC
";
$rc_stmt = $mysqli->prepare($rc_sql);
$rc_stmt->bind_param($rc_types, ...$rc_params);
$rc_stmt->execute();
$rc_res = $rc_stmt->get_result();
$running_charts_cache = [];
$total_trips_month = 0;
$total_mileage_month = 0.0;
$total_fuel_drawn_month = 0.0;
$total_fuel_consumed_month = 0.0;
$total_engine_oil_month = 0.0;
while ($rc = $rc_res->fetch_assoc()) {
    $running_charts_cache[] = $rc;
    $total_trips_month++;
    $total_mileage_month += floatval($rc['total_mileage']);
    $total_fuel_drawn_month += floatval($rc['fuel_drawn']);
    $total_fuel_consumed_month += floatval($rc['fuel_consumed']);
    $total_engine_oil_month += floatval($rc['engine_oil_drawn']);
}
$rc_stmt->close();
$avg_mpg_month = ($total_fuel_consumed_month > 0) ? ($total_mileage_month / $total_fuel_consumed_month) : 0.0;

// 3. Fetch Vehicle Repairs for selected month
$rep_where = "vr.is_active = 1 AND DATE_FORMAT(vr.repair_date, '%Y-%m') = ?";
$rep_types = "s";
$rep_params = [$selected_month];

if (!empty($range_id)) {
    $rep_where .= " AND rv.range_id = ?";
    $rep_types .= "i";
    $rep_params[] = $range_id;
} elseif (!empty($district_id)) {
    $rep_where .= " AND rv.district_id = ?";
    $rep_types .= "i";
    $rep_params[] = $district_id;
}

if ($selected_vehicle_id > 0) {
    $rep_where .= " AND vr.vehicle_id = ?";
    $rep_types .= "i";
    $rep_params[] = $selected_vehicle_id;
}

$rep_sql = "
    SELECT vr.*, rv.vehicle_number, rv.vehicle_type, u_app.full_name AS approver_name
    FROM vehicle_repairs vr
    JOIN registered_vehicles rv ON vr.vehicle_id = rv.id
    LEFT JOIN users u_app ON vr.approved_by = u_app.id
    WHERE {$rep_where}
    ORDER BY vr.repair_date DESC, vr.id DESC
";
$rep_stmt = $mysqli->prepare($rep_sql);
$rep_stmt->bind_param($rep_types, ...$rep_params);
$rep_stmt->execute();
$rep_res = $rep_stmt->get_result();
$repairs_cache = [];
$total_repairs_count = 0;
$total_repairs_cost = 0.0;
while ($rep = $rep_res->fetch_assoc()) {
    $repairs_cache[] = $rep;
    $total_repairs_count++;
    $cost_val = floatval($rep['transaction_amount'] > 0 ? $rep['transaction_amount'] : $rep['amount']);
    $total_repairs_cost += $cost_val;
}
$rep_stmt->close();

// 4. Monthly Summary Per-Vehicle Aggregation Matrix
$monthly_vehicle_matrix = [];
foreach ($vehicles_cache as $v) {
    if ($selected_vehicle_id > 0 && $v['id'] != $selected_vehicle_id) {
        continue;
    }
    $v_id = $v['id'];
    $monthly_vehicle_matrix[$v_id] = [
        'vehicle_id' => $v_id,
        'vehicle_number' => $v['vehicle_number'],
        'vehicle_type' => $v['vehicle_type'],
        'current_condition' => $v['current_condition'],
        'trips_count' => 0,
        'total_mileage' => 0.0,
        'fuel_drawn' => 0.0,
        'fuel_consumed' => 0.0,
        'latest_balance' => 0.0,
        'engine_oil_drawn' => 0.0,
        'repair_cost' => 0.0
    ];
}

foreach ($running_charts_cache as $rc) {
    $v_id = $rc['vehicle_id'];
    if (isset($monthly_vehicle_matrix[$v_id])) {
        $monthly_vehicle_matrix[$v_id]['trips_count']++;
        $monthly_vehicle_matrix[$v_id]['total_mileage'] += floatval($rc['total_mileage']);
        $monthly_vehicle_matrix[$v_id]['fuel_drawn'] += floatval($rc['fuel_drawn']);
        $monthly_vehicle_matrix[$v_id]['fuel_consumed'] += floatval($rc['fuel_consumed']);
        $monthly_vehicle_matrix[$v_id]['engine_oil_drawn'] += floatval($rc['engine_oil_drawn']);
        // Store latest recorded balance for the vehicle
        if (!isset($monthly_vehicle_matrix[$v_id]['balance_recorded'])) {
            $monthly_vehicle_matrix[$v_id]['latest_balance'] = floatval($rc['fuel_balance']);
            $monthly_vehicle_matrix[$v_id]['balance_recorded'] = true;
        }
    }
}

foreach ($repairs_cache as $rep) {
    $v_id = $rep['vehicle_id'];
    if (isset($monthly_vehicle_matrix[$v_id])) {
        $monthly_vehicle_matrix[$v_id]['repair_cost'] += floatval($rep['amount']);
    }
}

require_once '../../../includes/header.php';
?>

<link rel="stylesheet" href="../../../assets/css/dataTables.bootstrap5.min.css">
<link rel="stylesheet" href="../../../assets/css/sweetalert2.min.css">

<style>
@media print {
    body * {
        visibility: hidden;
    }
    #printableSummaryReport, #printableSummaryReport * {
        visibility: visible;
    }
    #printableSummaryReport {
        position: absolute;
        left: 0;
        top: 0;
        width: 100%;
    }
    .no-print {
        display: none !important;
    }
}
.stat-card-gold {
    background: linear-gradient(135deg, #b08723 0%, #8c6814 100%);
    color: #ffffff;
}
.stat-card-dark {
    background: linear-gradient(135deg, #212529 0%, #343a40 100%);
    color: #ffffff;
}
.suggestion-item:hover {
    background-color: #f8f9fa;
    color: #b08723;
}
</style>

<div class="container-fluid px-4 py-4">

    <!-- Top Bar: Title & Action Buttons -->
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-3 no-print">
        <div>
            <h3 class="fw-bold text-dark mb-1"><i class="bi bi-truck-front-fill text-warning me-2"></i>Fleet, Running Chart &amp; Fuel Management</h3>
            <p class="text-muted small mb-0">
                Jurisdiction Range: <strong class="text-dark"><?= htmlspecialchars($range_name) ?></strong> | 
                District: <strong class="text-dark"><?= htmlspecialchars($district_name) ?></strong> | 
                Reporting Period: <span class="badge bg-dark font-monospace text-warning"><?= date('F Y', strtotime($selected_month . '-01')) ?></span>
            </p>
        </div>
        <div class="d-flex flex-wrap gap-2">
            <button class="btn btn-primary shadow-sm" data-bs-toggle="modal" data-bs-target="#addRunningChartModal">
                <i class="bi bi-speedometer2 me-1"></i>Log Running Chart
            </button>
            <button class="btn text-white shadow-sm" style="background-color: #b08723;" data-bs-toggle="modal" data-bs-target="#addVehicleModal">
                <i class="bi bi-plus-circle-fill me-1"></i>Register Vehicle
            </button>
            <button class="btn btn-dark shadow-sm" data-bs-toggle="modal" data-bs-target="#addRepairModal">
                <i class="bi bi-wrench-adjustable me-1"></i>Log Repair Work
            </button>
            <a href="office_details.php" class="btn btn-secondary shadow-sm">
                <i class="bi bi-arrow-left me-1"></i>Back
            </a>
        </div>
    </div>

    <!-- Global Monthly & Vehicle Filter Toolbar -->
    <div class="card border-0 shadow-sm rounded-3 mb-4 bg-white no-print">
        <div class="card-body p-3">
            <form method="GET" action="vehicles.php" id="globalFilterForm" class="row g-2 align-items-center">
                <input type="hidden" name="tab" id="activeTabInput" value="<?= htmlspecialchars($active_tab) ?>">
                <div class="col-auto">
                    <span class="fw-bold small text-muted text-uppercase"><i class="bi bi-funnel-fill text-warning me-1"></i>Monthly Global Filter:</span>
                </div>
                <div class="col-auto">
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-light"><i class="bi bi-calendar-month text-muted"></i></span>
                        <input type="month" name="month" id="filterMonth" class="form-control form-control-sm font-monospace" value="<?= htmlspecialchars($selected_month) ?>">
                    </div>
                </div>
                <div class="col-auto">
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-light"><i class="bi bi-truck text-muted"></i></span>
                        <select name="vehicle_id" id="filterVehicle" class="form-select form-select-sm">
                            <option value="0">All Registered Vehicles (<?= count($vehicles_cache) ?>)</option>
                            <?php foreach ($vehicles_cache as $v): ?>
                                <option value="<?= $v['id'] ?>" <?= $selected_vehicle_id == $v['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($v['vehicle_number']) ?> — <?= htmlspecialchars($v['vehicle_type']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="col-auto">
                    <button type="submit" class="btn btn-sm text-white px-3 shadow-sm" style="background-color: #b08723;">
                        <i class="bi bi-filter me-1"></i>Apply Filter
                    </button>
                    <a href="vehicles.php?tab=<?= urlencode($active_tab) ?>" class="btn btn-sm btn-outline-secondary px-3">
                        <i class="bi bi-arrow-counterclockwise me-1"></i>Reset
                    </a>
                </div>
                <div class="col text-end">
                    <span class="badge bg-light text-dark border px-3 py-2 font-monospace">
                        Audit Window: <strong><?= date('M 01', strtotime($selected_month . '-01')) ?> &ndash; <?= date('M t, Y', strtotime($selected_month . '-01')) ?></strong>
                    </span>
                </div>
            </form>
        </div>
    </div>

    <!-- Navigation Tabs -->
    <ul class="nav nav-pills mb-4 bg-white p-2 rounded shadow-sm no-print" id="vehicleTabs" role="tablist">
        <li class="nav-item">
            <button class="nav-link <?= $active_tab === 'fleet' ? 'active' : '' ?>" id="fleet-tab" data-bs-toggle="tab" data-bs-target="#fleet-content" type="button" role="tab" style="--bs-nav-pills-link-active-bg: #b08723;">
                <i class="bi bi-truck me-2"></i>Active Vehicle Details
                <span class="badge bg-light text-dark border ms-1"><?= count($vehicles_cache) ?></span>
            </button>
        </li>
        <li class="nav-item">
            <button class="nav-link <?= $active_tab === 'running_chart' ? 'active' : '' ?>" id="running-chart-tab" data-bs-toggle="tab" data-bs-target="#running-chart-content" type="button" role="tab" style="--bs-nav-pills-link-active-bg: #b08723;">
                <i class="bi bi-speedometer2 me-2"></i>Running Chart &amp; Fuel Tracking
                <span class="badge bg-light text-dark border ms-1"><?= $total_trips_month ?> Trips</span>
            </button>
        </li>
        <li class="nav-item">
            <button class="nav-link <?= $active_tab === 'repairs' ? 'active' : '' ?>" id="repairs-tab" data-bs-toggle="tab" data-bs-target="#repairs-content" type="button" role="tab" style="--bs-nav-pills-link-active-bg: #b08723;">
                <i class="bi bi-tools me-2"></i>Maintenance &amp; Repair Logs
                <span class="badge bg-light text-dark border ms-1"><?= $total_repairs_count ?> Logs</span>
            </button>
        </li>
        <li class="nav-item">
            <button class="nav-link <?= $active_tab === 'summary' ? 'active' : '' ?>" id="summary-tab" data-bs-toggle="tab" data-bs-target="#summary-content" type="button" role="tab" style="--bs-nav-pills-link-active-bg: #b08723;">
                <i class="bi bi-pie-chart-fill me-2"></i>Unified Monthly Summary Report
                <span class="badge bg-warning text-dark ms-1">Monthly Audit</span>
            </button>
        </li>
    </ul>

    <!-- Tab Contents -->
    <div class="tab-content" id="vehicleTabsContent">
        
        <!-- Tab 1: Active Vehicle Details -->
        <div class="tab-pane fade <?= $active_tab === 'fleet' ? 'show active' : '' ?>" id="fleet-content" role="tabpanel">
            <div class="card shadow-sm border-0 rounded-3">
                <div class="card-header bg-white py-3 px-4 border-bottom d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="fw-bold text-dark mb-0">Registered Vehicle Inventory &amp; Physical Conditions</h6>
                        <small class="text-muted">Master asset fleet registered in <?= htmlspecialchars($range_name) ?></small>
                    </div>
                    <div>
                        <span class="badge bg-success me-1 px-3 py-2">Running: <?= $fleet_running_count ?></span>
                        <span class="badge bg-warning text-dark px-3 py-2">Needs Repair: <?= $fleet_repair_count ?></span>
                    </div>
                </div>
                <div class="card-body p-4">
                    <div class="table-responsive">
                        <table id="vehiclesTable" class="table table-hover align-middle w-100">
                            <thead class="table-light text-uppercase small">
                                <tr>
                                    <th>Vehicle Type</th>
                                    <th>Vehicle Number</th>
                                    <th>Chassis Number</th>
                                    <th>Issue Order No.</th>
                                    <th>Received From</th>
                                    <th>Receipt No.</th>
                                    <th class="text-center">Quantity</th>
                                    <th>Current Condition</th>
                                    <th>Specification / Remarks</th>
                                    <th class="text-center">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($vehicles_cache as $row): ?>
                                <tr id="vehicle-row-<?= $row['id'] ?>">
                                    <td class="fw-bold text-dark">
                                        <i class="bi bi-truck me-1 text-muted"></i><?= htmlspecialchars($row['vehicle_type']) ?>
                                    </td>
                                    <td><span class="badge bg-dark text-light px-2 py-1 font-monospace"><?= htmlspecialchars($row['vehicle_number']) ?></span></td>
                                    <td><span class="text-secondary small font-monospace fw-semibold"><?= htmlspecialchars($row['chassis_number']) ?></span></td>
                                    <td><span class="badge bg-light text-dark border font-monospace"><?= !empty($row['issue_order_no']) ? htmlspecialchars($row['issue_order_no']) : '-' ?></span></td>
                                    <td><small class="text-secondary"><?= !empty($row['received_from']) ? htmlspecialchars($row['received_from']) : '-' ?></small></td>
                                    <td><span class="badge bg-light text-dark border font-monospace"><?= !empty($row['receipt_no']) ? htmlspecialchars($row['receipt_no']) : '-' ?></span></td>
                                    <td class="text-center">
                                        <span class="badge bg-primary fs-6 px-2 py-1"><?= sprintf("%02d", $row['available_quantity'] ?? 1) ?></span>
                                        <br>
                                        <small class="text-muted" style="font-size:10px;" title="Baseline + Received">Base: <?= intval($row['initial_count'] ?? 1) ?> | Recv: <?= intval($row['received_quantity'] ?? 0) ?></small>
                                    </td>
                                    <td>
                                        <?php 
                                        $cond_class = ($row['current_condition'] === 'Running') ? 'bg-success' : (($row['current_condition'] === 'Needs Repair') ? 'bg-warning text-dark' : 'bg-secondary');
                                        ?>
                                        <span class="badge <?= $cond_class ?> rounded-pill px-2"><?= htmlspecialchars($row['current_condition']) ?></span>
                                    </td>
                                    <td>
                                        <?php if (!empty($row['specification'])): ?>
                                            <div class="fw-semibold text-dark small"><?= htmlspecialchars($row['specification']) ?></div>
                                        <?php endif; ?>
                                        <?php 
                                        $disp_rem = !empty($row['remarks']) ? $row['remarks'] : (!empty($row['other_details']) ? $row['other_details'] : '');
                                        if (!empty($disp_rem)): ?>
                                            <small class="text-muted"><?= htmlspecialchars($disp_rem) ?></small>
                                        <?php elseif (empty($row['specification'])): ?>
                                            <small class="text-muted">-</small>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center">
                                        <div class="btn-group">
                                            <button class="btn btn-sm btn-outline-info me-1" title="View Details" onclick='viewVehicle(<?= json_encode($row) ?>)'>
                                                <i class="bi bi-eye"></i>
                                            </button>
                                            <button class="btn btn-sm btn-outline-primary me-1" title="Edit Vehicle" onclick='editVehicle(<?= json_encode($row) ?>)'>
                                                <i class="bi bi-pencil"></i>
                                            </button>
                                            <button class="btn btn-sm btn-outline-danger" title="Delete" onclick="handleVehicleDelete(<?= $row['id'] ?>)">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tab 2: Running Chart & Fuel Tracking -->
        <div class="tab-pane fade <?= $active_tab === 'running_chart' ? 'show active' : '' ?>" id="running-chart-content" role="tabpanel">
            <div class="card shadow-sm border-0 rounded-3">
                <div class="card-header bg-white py-3 px-4 border-bottom d-flex flex-wrap justify-content-between align-items-center gap-2">
                    <div>
                        <h6 class="fw-bold text-dark mb-0">Daily Running Chart &amp; Fuel Consumption Register</h6>
                        <small class="text-muted">Recorded journeys, mileage tracking, fuel drawn &amp; miles per gallon for <?= date('F Y', strtotime($selected_month . '-01')) ?></small>
                    </div>
                    <button class="btn btn-primary btn-sm shadow-sm" data-bs-toggle="modal" data-bs-target="#addRunningChartModal">
                        <i class="bi bi-plus-circle me-1"></i>New Running Chart Entry
                    </button>
                </div>
                <div class="card-body p-4">
                    <div class="table-responsive">
                        <table id="runningChartTable" class="table table-hover align-middle w-100">
                            <thead class="table-light text-uppercase small">
                                <tr>
                                    <th>Trip Date</th>
                                    <th>Vehicle</th>
                                    <th>Driver Details</th>
                                    <th>Route &amp; Purpose</th>
                                    <th>Milometer (Out / In)</th>
                                    <th class="text-end">Total Mileage</th>
                                    <th>Fuel (Tank / Drawn / Used)</th>
                                    <th class="text-end">Balance</th>
                                    <th class="text-end">MPG</th>
                                    <th class="text-end">Engine Oil</th>
                                    <th class="text-center">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($running_charts_cache as $rc): ?>
                                <tr id="rc-row-<?= $rc['id'] ?>">
                                    <td class="fw-semibold text-secondary font-monospace small">
                                        <?= htmlspecialchars($rc['trip_date']) ?><br>
                                        <small class="text-muted"><?= htmlspecialchars(substr($rc['time_out'], 0, 5)) ?> &ndash; <?= htmlspecialchars(substr($rc['time_in'], 0, 5)) ?></small>
                                    </td>
                                    <td>
                                        <span class="badge bg-dark font-monospace"><?= htmlspecialchars($rc['vehicle_number']) ?></span><br>
                                        <small class="text-muted"><?= htmlspecialchars($rc['vehicle_type']) ?></small>
                                    </td>
                                    <td>
                                        <span class="fw-bold text-dark small"><?= htmlspecialchars($rc['driver_name']) ?></span><br>
                                        <span class="badge bg-light text-dark border font-monospace"><?= htmlspecialchars($rc['driver_initials']) ?></span>
                                    </td>
                                    <td style="max-width: 200px;">
                                        <div class="text-truncate small fw-semibold" title="<?= htmlspecialchars($rc['route_places_visited']) ?>">
                                            <i class="bi bi-geo-alt text-danger me-1"></i><?= htmlspecialchars($rc['route_places_visited']) ?>
                                        </div>
                                        <small class="text-muted text-truncate d-block" title="<?= htmlspecialchars($rc['purpose_of_trip']) ?>">
                                            <?= htmlspecialchars($rc['purpose_of_trip']) ?>
                                        </small>
                                    </td>
                                    <td class="font-monospace small">
                                        Out: <span class="text-secondary"><?= number_format($rc['milometer_out'], 1) ?></span><br>
                                        In: <span class="fw-bold text-dark"><?= number_format($rc['milometer_in'], 1) ?></span>
                                    </td>
                                    <td class="text-end font-monospace fw-bold text-primary">
                                        <?= number_format($rc['total_mileage'], 1) ?>
                                    </td>
                                    <td class="small font-monospace">
                                        Tank: <?= number_format($rc['fuel_position_in_tank'], 2) ?><br>
                                        <span class="text-success">+<?= number_format($rc['fuel_drawn'], 2) ?></span> | 
                                        <span class="text-danger">-<?= number_format($rc['fuel_consumed'], 2) ?></span>
                                    </td>
                                    <td class="text-end font-monospace fw-bold text-dark">
                                        <?= number_format($rc['fuel_balance'], 2) ?>
                                    </td>
                                    <td class="text-end font-monospace fw-bold text-success">
                                        <?= number_format($rc['miles_per_gallon'], 2) ?>
                                    </td>
                                    <td class="text-end font-monospace text-secondary">
                                        <?= number_format($rc['engine_oil_drawn'], 2) ?>
                                    </td>
                                    <td class="text-center">
                                        <div class="btn-group">
                                            <button class="btn btn-sm btn-outline-info me-1" title="View Dossier" onclick='viewRunningChart(<?= json_encode($rc) ?>)'>
                                                <i class="bi bi-eye"></i>
                                            </button>
                                            <button class="btn btn-sm btn-outline-primary me-1" title="Edit Entry" onclick='editRunningChart(<?= json_encode($rc) ?>)'>
                                                <i class="bi bi-pencil"></i>
                                            </button>
                                            <button class="btn btn-sm btn-outline-danger" title="Delete Log" onclick="handleRunningChartDelete(<?= $rc['id'] ?>)">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tab 3: Maintenance & Repair Logs -->
        <div class="tab-pane fade <?= $active_tab === 'repairs' ? 'show active' : '' ?>" id="repairs-content" role="tabpanel">
            <div class="card shadow-sm border-0 rounded-3">
                <div class="card-header bg-white py-3 px-4 border-bottom d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="fw-bold text-dark mb-0">Vehicle Maintenance &amp; Repair Operations</h6>
                        <small class="text-muted">Recorded repair costs &amp; workshop service logs for <?= date('F Y', strtotime($selected_month . '-01')) ?></small>
                    </div>
                    <div>
                        <span class="badge bg-dark text-white px-3 py-2 font-monospace">
                            Total Expenses: LKR <?= number_format($total_repairs_cost, 2) ?>
                        </span>
                    </div>
                </div>
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
                                    <th class="text-center">Approval Status</th>
                                    <th class="text-center">Receipt</th>
                                    <th class="text-center">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($repairs_cache as $row): 
                                    $cost_display = floatval($row['transaction_amount'] > 0 ? $row['transaction_amount'] : $row['amount']);
                                    $status = $row['approval_status'] ?? 'Approved';
                                ?>
                                <tr id="repair-row-<?= $row['id'] ?>">
                                    <td class="fw-semibold text-secondary font-monospace"><?= htmlspecialchars($row['repair_date']) ?></td>
                                    <td>
                                        <span class="badge bg-light text-dark border font-monospace"><?= htmlspecialchars($row['vehicle_number']) ?></span>
                                    </td>
                                    <td class="fw-bold text-dark"><?= htmlspecialchars($row['repair_done']) ?></td>
                                    <td><small class="text-muted"><?= htmlspecialchars($row['repair_description']) ?></small></td>
                                    <td><span class="small"><?= htmlspecialchars($row['place_of_repair']) ?></span></td>
                                    <td class="text-end fw-bold text-dark font-monospace"><?= number_format($cost_display, 2) ?></td>
                                    <td class="text-center">
                                        <?php if ($status === 'Approved'): ?>
                                            <span class="badge bg-success text-white px-2 py-1"><i class="bi bi-check-circle-fill me-1"></i>Approved</span>
                                        <?php elseif ($status === 'Pending District Approval'): ?>
                                            <span class="badge bg-warning text-dark px-2 py-1" title="Awaiting District DD Review"><i class="bi bi-clock-history me-1"></i>District DD</span>
                                        <?php elseif ($status === 'Pending Provincial Approval'): ?>
                                            <span class="badge bg-info text-dark px-2 py-1" title="Awaiting Provincial Director Review"><i class="bi bi-shield-exclamation me-1"></i>Prov. Director</span>
                                        <?php elseif ($status === 'Rejected'): ?>
                                            <span class="badge bg-danger text-white px-2 py-1" title="<?= !empty($row['rejection_reason']) ? 'Reason: ' . htmlspecialchars($row['rejection_reason']) : 'Rejected' ?>"><i class="bi bi-x-circle-fill me-1"></i>Rejected</span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary"><?= htmlspecialchars($status) ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center">
                                        <?php if (!empty($row['receipt_file'])): ?>
                                            <a href="/daph-ep-mis/<?= htmlspecialchars($row['receipt_file']) ?>" target="_blank" class="btn btn-sm btn-outline-secondary px-2 py-1" title="View Digital Receipt">
                                                <i class="bi bi-file-earmark-pdf-fill text-danger"></i>
                                            </a>
                                        <?php else: ?>
                                            <span class="text-muted small">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center">
                                        <div class="btn-group">
                                            <button class="btn btn-sm btn-outline-info me-1" title="View Log" onclick='viewRepair(<?= json_encode($row) ?>)'>
                                                <i class="bi bi-eye"></i>
                                            </button>
                                            <button class="btn btn-sm btn-outline-primary me-1" title="Edit Log" onclick='editRepair(<?= json_encode($row) ?>)'>
                                                <i class="bi bi-pencil"></i>
                                            </button>
                                            <?php if (in_array($_SESSION['role'], ['provincial_director', 'district_dd', 'administrator']) && strpos($status, 'Pending') !== false): ?>
                                            <button class="btn btn-sm btn-outline-success me-1" title="Quick Approve" onclick="quickApproveRepair(<?= $row['id'] ?>)">
                                                <i class="bi bi-check-lg"></i>
                                            </button>
                                            <button class="btn btn-sm btn-outline-danger me-1" title="Quick Reject" onclick="quickRejectRepair(<?= $row['id'] ?>)">
                                                <i class="bi bi-x-lg"></i>
                                            </button>
                                            <?php endif; ?>
                                            <button class="btn btn-sm btn-outline-danger" title="Delete" onclick="handleRepairDelete(<?= $row['id'] ?>)">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tab 4: Unified Monthly Summary Report -->
        <div class="tab-pane fade <?= $active_tab === 'summary' ? 'show active' : '' ?>" id="summary-content" role="tabpanel">
            <div id="printableSummaryReport">
                
                <!-- Report Header & Actions -->
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <h4 class="fw-bold text-dark mb-1">
                            <i class="bi bi-file-earmark-bar-graph-fill text-warning me-2"></i>Monthly Fleet &amp; Fuel Consumption Summary Report
                        </h4>
                        <p class="text-muted mb-0">
                            Department of Animal Production &amp; Health | Range: <strong><?= htmlspecialchars($range_name) ?></strong> | 
                            District: <strong><?= htmlspecialchars($district_name) ?></strong> | 
                            Month: <strong class="text-dark"><?= date('F Y', strtotime($selected_month . '-01')) ?></strong>
                        </p>
                    </div>
                    <div class="no-print">
                        <button class="btn btn-outline-dark btn-sm shadow-sm px-3" onclick="window.print()">
                            <i class="bi bi-printer-fill me-1"></i>Print / Export Report
                        </button>
                    </div>
                </div>

                <!-- Executive KPI Stat Cards -->
                <div class="row g-3 mb-4">
                    <div class="col-xl-3 col-md-6">
                        <div class="card border-0 shadow-sm rounded-3 h-100 stat-card-gold p-3">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <span class="text-white-50 small text-uppercase fw-semibold">Fleet Utilization</span>
                                    <h2 class="fw-bold mb-0 mt-1"><?= count($vehicles_cache) ?></h2>
                                    <small class="text-white-50">Active: <?= $fleet_running_count ?> | Repair: <?= $fleet_repair_count ?></small>
                                </div>
                                <div class="bg-white bg-opacity-25 rounded-circle p-3">
                                    <i class="bi bi-truck fs-3"></i>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-xl-3 col-md-6">
                        <div class="card border-0 shadow-sm rounded-3 h-100 stat-card-dark p-3">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <span class="text-white-50 small text-uppercase fw-semibold">Monthly Trips &amp; Mileage</span>
                                    <h2 class="fw-bold mb-0 mt-1 font-monospace"><?= number_format($total_mileage_month, 1) ?></h2>
                                    <small class="text-warning">Miles Covered in <?= $total_trips_month ?> Trips</small>
                                </div>
                                <div class="bg-white bg-opacity-25 rounded-circle p-3">
                                    <i class="bi bi-speedometer2 fs-3 text-warning"></i>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-xl-3 col-md-6">
                        <div class="card border-0 shadow-sm rounded-3 h-100 bg-white p-3 border-start border-danger border-4">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <span class="text-muted small text-uppercase fw-semibold">Fuel Consumed</span>
                                    <h2 class="fw-bold mb-0 mt-1 text-danger font-monospace"><?= number_format($total_fuel_consumed_month, 2) ?></h2>
                                    <small class="text-muted">Drawn: +<?= number_format($total_fuel_drawn_month, 2) ?> | Avg MPG: <strong class="text-success"><?= number_format($avg_mpg_month, 2) ?></strong></small>
                                </div>
                                <div class="bg-danger bg-opacity-10 text-danger rounded-circle p-3">
                                    <i class="bi bi-fuel-pump fs-3"></i>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-xl-3 col-md-6">
                        <div class="card border-0 shadow-sm rounded-3 h-100 bg-white p-3 border-start border-primary border-4">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <span class="text-muted small text-uppercase fw-semibold">Repairs &amp; Lubricants</span>
                                    <h2 class="fw-bold mb-0 mt-1 text-primary font-monospace">LKR <?= number_format($total_repairs_cost, 0) ?></h2>
                                    <small class="text-muted"><?= $total_repairs_count ?> Repairs | Oil Drawn: <strong><?= number_format($total_engine_oil_month, 2) ?> Pts</strong></small>
                                </div>
                                <div class="bg-primary bg-opacity-10 text-primary rounded-circle p-3">
                                    <i class="bi bi-tools fs-3"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Consolidated Per-Vehicle Breakdown Table -->
                <div class="card shadow-sm border-0 rounded-3 mb-4">
                    <div class="card-header bg-white py-3 px-4 border-bottom">
                        <h6 class="fw-bold text-dark mb-0">Consolidated Fleet Operations Matrix (Per-Vehicle Monthly Totals)</h6>
                        <small class="text-muted">Aggregating trip logs, mileage travelled, fuel efficiency and maintenance expenditure</small>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped align-middle mb-0">
                                <thead class="table-light text-uppercase small text-center">
                                    <tr>
                                        <th class="text-start">Vehicle Registration</th>
                                        <th>Vehicle Type</th>
                                        <th>Condition</th>
                                        <th>Trips Logged</th>
                                        <th>Total Mileage</th>
                                        <th>Fuel Drawn</th>
                                        <th>Fuel Consumed</th>
                                        <th>Ending Balance</th>
                                        <th>Efficiency (MPG)</th>
                                        <th>Engine Oil Drawn</th>
                                        <th class="text-end">Repair Cost (LKR)</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($monthly_vehicle_matrix)): ?>
                                    <tr>
                                        <td colspan="11" class="text-center py-4 text-muted">No vehicle data available for the selected filter.</td>
                                    </tr>
                                    <?php else: 
                                        $sum_trips = 0;
                                        $sum_mileage = 0.0;
                                        $sum_drawn = 0.0;
                                        $sum_consumed = 0.0;
                                        $sum_balance = 0.0;
                                        $sum_oil = 0.0;
                                        $sum_repairs = 0.0;
                                        foreach ($monthly_vehicle_matrix as $row):
                                            $sum_trips += $row['trips_count'];
                                            $sum_mileage += $row['total_mileage'];
                                            $sum_drawn += $row['fuel_drawn'];
                                            $sum_consumed += $row['fuel_consumed'];
                                            $sum_balance += $row['latest_balance'];
                                            $sum_oil += $row['engine_oil_drawn'];
                                            $sum_repairs += $row['repair_cost'];
                                            $v_mpg = ($row['fuel_consumed'] > 0) ? ($row['total_mileage'] / $row['fuel_consumed']) : 0.0;
                                    ?>
                                    <tr>
                                        <td class="text-start font-monospace fw-bold text-dark">
                                            <i class="bi bi-truck me-1 text-muted"></i><?= htmlspecialchars($row['vehicle_number']) ?>
                                        </td>
                                        <td class="text-center small"><?= htmlspecialchars($row['vehicle_type']) ?></td>
                                        <td class="text-center">
                                            <?php 
                                            $c_badge = ($row['current_condition'] === 'Running') ? 'bg-success' : 'bg-warning text-dark';
                                            ?>
                                            <span class="badge <?= $c_badge ?> rounded-pill px-2"><?= htmlspecialchars($row['current_condition']) ?></span>
                                        </td>
                                        <td class="text-center font-monospace fw-semibold"><?= $row['trips_count'] ?></td>
                                        <td class="text-center font-monospace fw-bold text-primary"><?= number_format($row['total_mileage'], 1) ?></td>
                                        <td class="text-center font-monospace text-success">+<?= number_format($row['fuel_drawn'], 2) ?></td>
                                        <td class="text-center font-monospace text-danger">-<?= number_format($row['fuel_consumed'], 2) ?></td>
                                        <td class="text-center font-monospace fw-bold"><?= number_format($row['latest_balance'], 2) ?></td>
                                        <td class="text-center font-monospace fw-bold text-success"><?= number_format($v_mpg, 2) ?></td>
                                        <td class="text-center font-monospace"><?= number_format($row['engine_oil_drawn'], 2) ?></td>
                                        <td class="text-end font-monospace fw-bold text-dark"><?= number_format($row['repair_cost'], 2) ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                                <tfoot class="table-dark font-monospace text-center">
                                    <tr>
                                        <th colspan="3" class="text-start">MONTHLY GRAND TOTALS:</th>
                                        <th><?= $sum_trips ?></th>
                                        <th class="text-primary text-white"><?= number_format($sum_mileage, 1) ?></th>
                                        <th class="text-success"><?= number_format($sum_drawn, 2) ?></th>
                                        <th class="text-warning"><?= number_format($sum_consumed, 2) ?></th>
                                        <th><?= number_format($sum_balance, 2) ?></th>
                                        <th class="text-success"><?= ($sum_consumed > 0) ? number_format($sum_mileage / $sum_consumed, 2) : '0.00' ?></th>
                                        <th><?= number_format($sum_oil, 2) ?></th>
                                        <th class="text-end text-warning"><?= number_format($sum_repairs, 2) ?></th>
                                    </tr>
                                </tfoot>
                                <?php endif; ?>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Monthly Certification Sign-off for Print -->
                <div class="row mt-5 pt-4 border-top">
                    <div class="col-4 text-center">
                        <p class="mb-5 text-muted small">Prepared By (Driver / Log Officer):</p>
                        <hr class="w-75 mx-auto mb-1">
                        <span class="small fw-bold text-dark">Signature &amp; Date</span>
                    </div>
                    <div class="col-4 text-center">
                        <p class="mb-5 text-muted small">Verified By (Technical Officer):</p>
                        <hr class="w-75 mx-auto mb-1">
                        <span class="small fw-bold text-dark">Signature &amp; Date</span>
                    </div>
                    <div class="col-4 text-center">
                        <p class="mb-5 text-muted small">Certified By (Veterinary Surgeon):</p>
                        <hr class="w-75 mx-auto mb-1">
                        <span class="small fw-bold text-dark"><?= htmlspecialchars($_SESSION['full_name'] ?? 'Veterinary Surgeon') ?></span>
                    </div>
                </div>

            </div>
        </div>

    </div>
</div>

<!-- Included Modals -->
<?php include 'models/add_vehicle.php'; ?>
<?php include 'models/edit_vehicle.php'; ?>
<?php include 'models/view_vehicle.php'; ?>
<?php include 'models/add_repair_vehicle.php'; ?>
<?php include 'models/edit_vehicle_repair.php'; ?>
<?php include 'models/view_vehicle_repair.php'; ?>
<?php include 'models/add_running_chart.php'; ?>
<?php include 'models/edit_running_chart.php'; ?>
<?php include 'models/view_running_chart.php'; ?>

<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
    var fleetTable, repairsTable, runningChartTable;

    function escapeHtml(text) {
        if (!text) return '';
        var map = { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' };
        return text.replace(/[&<>"']/g, function(m) { return map[m]; });
    }

    function escapeRegex(text) {
        return text.replace(/[-[\]{}()*+?.,\\^$|#\s]/g, '\\$&');
    }

    $(document).ready(function() {
        fleetTable = $('#vehiclesTable').DataTable({ "pageLength": 10 });
        repairsTable = $('#repairsTable').DataTable({ "pageLength": 10, "order": [[0, "desc"]] });
        runningChartTable = $('#runningChartTable').DataTable({ "pageLength": 10, "order": [[0, "desc"]] });

        // Synchronize Active Tab with Filter Form
        $('button[data-bs-toggle="tab"]').on('shown.bs.tab', function (e) {
            var target = $(e.target).attr("data-bs-target");
            var tabName = 'fleet';
            if (target === '#running-chart-content') tabName = 'running_chart';
            else if (target === '#repairs-content') tabName = 'repairs';
            else if (target === '#summary-content') tabName = 'summary';
            $('#activeTabInput').val(tabName);
        });

        // Initialize Dynamic Auto-Suggest for Vehicle Types
        setupVehicleTypeAutocomplete('#add_vehicle_type', '#add_vehicle_type_suggestions');
        setupVehicleTypeAutocomplete('#edit_vehicle_type', '#edit_vehicle_type_suggestions');

        // Dynamic Calculation Triggers for Add Running Chart
        $('.rc-calc-trigger').on('input change', function() {
            recalculateRunningChart('rc_');
        });

        // Dynamic Calculation Triggers for Edit Running Chart
        $('.edit-rc-calc-trigger').on('input change', function() {
            recalculateRunningChart('edit_rc_');
        });

        // Dynamic Fuel & Mileage Tracking: Auto-fetch previous status on Vehicle selection (Add Modal)
        $('#rc_vehicle_id').on('change', function() {
            var vId = $(this).val();
            if (!vId) return;
            $.ajax({
                url: 'processors/get_vehicle_latest_status.php',
                type: 'GET',
                data: { vehicle_id: vId },
                dataType: 'json',
                success: function(res) {
                    if (res.success) {
                        var prevFuel = parseFloat(res.latest_fuel_balance) || 0;
                        var prevMilo = parseFloat(res.latest_milometer) || 0;

                        $('#rc_prev_fuel_balance').val(prevFuel.toFixed(2));
                        $('#rc_prev_fuel_val').text(prevFuel.toFixed(2));
                        $('#rc_prev_milo_val').text(prevMilo.toFixed(1));

                        // Suggest previous ending milometer as default starting milometer if empty
                        if (!$('#rc_milometer_out').val() || parseFloat($('#rc_milometer_out').val()) === 0) {
                            $('#rc_milometer_out').val(prevMilo.toFixed(1));
                        }

                        recalculateRunningChart('rc_');
                    }
                }
            });
        });

        // Dynamic Fuel & Mileage Tracking: Auto-fetch previous status on Vehicle selection (Edit Modal)
        $('#edit_rc_vehicle_id').on('change', function() {
            var vId = $(this).val();
            var currId = $('#edit_rc_id').val();
            if (!vId) return;
            $.ajax({
                url: 'processors/get_vehicle_latest_status.php',
                type: 'GET',
                data: { vehicle_id: vId, current_id: currId },
                dataType: 'json',
                success: function(res) {
                    if (res.success) {
                        var prevFuel = parseFloat(res.latest_fuel_balance) || 0;
                        var prevMilo = parseFloat(res.latest_milometer) || 0;

                        $('#edit_rc_prev_fuel_balance').val(prevFuel.toFixed(2));
                        $('#edit_rc_prev_fuel_val').text(prevFuel.toFixed(2));
                        $('#edit_rc_prev_milo_val').text(prevMilo.toFixed(1));

                        recalculateRunningChart('edit_rc_');
                    }
                }
            });
        });

        // Live Conditional Approval Routing Badges
        function updateAddRepairRouting() {
            var amt = parseFloat($('#add_repair_amount').val()) || 0;
            $('#add_repair_transaction_amount').val(amt.toFixed(2));
            if (amt <= 0) {
                $('#add_repair_routing_badge').removeClass('alert-warning alert-primary').addClass('alert-light');
                $('#add_routing_pill').attr('class', 'badge bg-secondary rounded-pill px-3 py-2').text('Pending Cost');
                $('#add_routing_desc').html('Costs &le; LKR 50,000 route to <strong>District Deputy Director</strong>. Costs &gt; LKR 50,000 route to <strong>Provincial Director</strong>.');
            } else if (amt <= 50000.00) {
                $('#add_repair_routing_badge').removeClass('alert-light alert-primary').addClass('alert-warning');
                $('#add_routing_pill').attr('class', 'badge bg-warning text-dark rounded-pill px-3 py-2').text('Tier 1: District DD');
                $('#add_routing_desc').html('<strong class="text-dark">Conditional Routing: District Deputy Director</strong> (&le; LKR 50,000.00). An automated approval notification will be dispatched.');
            } else {
                $('#add_repair_routing_badge').removeClass('alert-light alert-warning').addClass('alert-primary');
                $('#add_routing_pill').attr('class', 'badge bg-primary text-white rounded-pill px-3 py-2').text('Tier 2: Provincial Dir');
                $('#add_routing_desc').html('<strong class="text-primary">Conditional Routing: Provincial Director</strong> (&gt; LKR 50,000.00). High-value maintenance approval notification will be dispatched.');
            }
        }
        $('#add_repair_amount').on('input change', updateAddRepairRouting);

        function updateEditRepairRouting() {
            var amt = parseFloat($('#edit_repair_amount').val()) || 0;
            $('#edit_repair_transaction_amount').val(amt.toFixed(2));
            if (amt <= 0) {
                $('#edit_repair_routing_badge').removeClass('alert-warning alert-primary').addClass('alert-light');
                $('#edit_routing_pill').attr('class', 'badge bg-secondary rounded-pill px-3 py-2').text('-');
                $('#edit_routing_desc').html('Costs &le; LKR 50,000 route to <strong>District Deputy Director</strong>. Costs &gt; LKR 50,000 route to <strong>Provincial Director</strong>.');
            } else if (amt <= 50000.00) {
                $('#edit_repair_routing_badge').removeClass('alert-light alert-primary').addClass('alert-warning');
                $('#edit_routing_pill').attr('class', 'badge bg-warning text-dark rounded-pill px-3 py-2').text('Tier 1: District DD');
                $('#edit_routing_desc').html('<strong class="text-dark">Conditional Routing: District Deputy Director</strong> (&le; LKR 50,000.00).');
            } else {
                $('#edit_repair_routing_badge').removeClass('alert-light alert-warning').addClass('alert-primary');
                $('#edit_routing_pill').attr('class', 'badge bg-primary text-white rounded-pill px-3 py-2').text('Tier 2: Provincial Dir');
                $('#edit_routing_desc').html('<strong class="text-primary">Conditional Routing: Provincial Director</strong> (&gt; LKR 50,000.00).');
            }
        }
        $('#edit_repair_amount').on('input change', updateEditRepairRouting);

        // Submit Add Vehicle Form
        $('#addVehicleForm').on('submit', function(e) {
            e.preventDefault();
            $.ajax({
                url: 'processors/save_vehicle.php',
                type: 'POST',
                data: $(this).serialize(),
                dataType: 'json',
                success: function(res) {
                    if (res.success) {
                        Swal.fire('Registered!', res.message, 'success').then(() => { location.reload(); });
                    } else { 
                        Swal.fire('Error', res.message, 'error'); 
                    }
                }
            });
        });

        // Submit Edit Vehicle Form
        $('#editVehicleForm').on('submit', function(e) {
            e.preventDefault();
            $.ajax({
                url: 'processors/update_vehicle.php',
                type: 'POST',
                data: $(this).serialize(),
                dataType: 'json',
                success: function(res) {
                    if (res.success) {
                        if (res.staged) {
                            Swal.fire({
                                icon: 'info',
                                title: 'Pending Authorization',
                                text: res.message,
                                confirmButtonColor: '#b08723'
                            }).then(() => { location.reload(); });
                        } else {
                            Swal.fire('Updated!', res.message, 'success').then(() => { location.reload(); });
                        }
                    } else { 
                        Swal.fire('Error', res.message, 'error'); 
                    }
                }
            });
        });

        // Submit Add Running Chart Form
        $('#addRunningChartForm').on('submit', function(e) {
            e.preventDefault();
            $.ajax({
                url: 'processors/save_running_chart.php',
                type: 'POST',
                data: $(this).serialize(),
                dataType: 'json',
                success: function(res) {
                    if (res.success) {
                        Swal.fire('Logged!', res.message, 'success').then(() => {
                            window.location.href = 'vehicles.php?tab=running_chart&month=' + encodeURIComponent($('#filterMonth').val());
                        });
                    } else { 
                        Swal.fire('Validation Error', res.message, 'error'); 
                    }
                }
            });
        });

        // Submit Edit Running Chart Form
        $('#editRunningChartForm').on('submit', function(e) {
            e.preventDefault();
            $.ajax({
                url: 'processors/update_running_chart.php',
                type: 'POST',
                data: $(this).serialize(),
                dataType: 'json',
                success: function(res) {
                    if (res.success) {
                        Swal.fire('Updated!', res.message, 'success').then(() => {
                            window.location.href = 'vehicles.php?tab=running_chart&month=' + encodeURIComponent($('#filterMonth').val());
                        });
                    } else { 
                        Swal.fire('Error', res.message, 'error'); 
                    }
                }
            });
        });

        // Submit Add Repair Form with Receipt Upload (FormData)
        $('#addRepairForm').on('submit', function(e) {
            e.preventDefault();
            var formData = new FormData(this);
            $.ajax({
                url: 'processors/save_vehicle_repair.php',
                type: 'POST',
                data: formData,
                contentType: false,
                processData: false,
                dataType: 'json',
                success: function(res) {
                    if (res.success) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Repair Logged!',
                            text: res.message + (res.approval_status ? ' (' + res.approval_status + ')' : ''),
                            confirmButtonColor: '#b08723'
                        }).then(() => {
                            window.location.href = 'vehicles.php?tab=repairs&month=' + encodeURIComponent($('#filterMonth').val());
                        });
                    } else { 
                        Swal.fire('Error', res.message, 'error'); 
                    }
                },
                error: function() {
                    Swal.fire('Error', 'Server connection failure during repair submission.', 'error');
                }
            });
        });

        // Submit Edit Repair Form with Receipt Upload (FormData)
        $('#editRepairForm').on('submit', function(e) {
            e.preventDefault();
            var formData = new FormData(this);
            $.ajax({
                url: 'processors/update_vehicle_repair.php',
                type: 'POST',
                data: formData,
                contentType: false,
                processData: false,
                dataType: 'json',
                success: function(res) {
                    if (res.success) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Updated!',
                            text: res.message,
                            confirmButtonColor: '#b08723'
                        }).then(() => {
                            window.location.href = 'vehicles.php?tab=repairs&month=' + encodeURIComponent($('#filterMonth').val());
                        });
                    } else { 
                        Swal.fire('Error', res.message, 'error'); 
                    }
                },
                error: function() {
                    Swal.fire('Error', 'Server connection failure during repair update.', 'error');
                }
            });
        });
    });

    // Real-time calculation helper with automated mileage and fuel tracking
    function recalculateRunningChart(prefix) {
        var mOut = parseFloat($('#' + prefix + 'milometer_out').val()) || 0;
        var mIn = parseFloat($('#' + prefix + 'milometer_in').val()) || 0;
        var totalMileage = Math.max(0, mIn - mOut);
        $('#' + prefix + 'total_mileage').val(totalMileage.toFixed(1));

        var prevFuel = parseFloat($('#' + prefix + 'prev_fuel_balance').val()) || 0;
        var drawn = parseFloat($('#' + prefix + 'fuel_drawn').val()) || 0;
        var consumed = parseFloat($('#' + prefix + 'fuel_consumed').val()) || 0;

        // Current "Fuel Position in Tank" = Previous Ending Balance + newly drawn fuel
        var tankPosition = Math.max(0, prevFuel + drawn);
        $('#' + prefix + 'fuel_position_in_tank').val(tankPosition.toFixed(2));

        // Tank Ending Balance = Position in Tank - Consumed
        var balance = Math.max(0, tankPosition - consumed);
        $('#' + prefix + 'fuel_balance').val(balance.toFixed(2));

        var mpg = consumed > 0 ? (totalMileage / consumed) : 0;
        $('#' + prefix + 'miles_per_gallon').val(mpg.toFixed(2));
    }

    // Auto-suggest implementation for Vehicle Type
    function setupVehicleTypeAutocomplete(inputSelector, dropdownSelector) {
        var timer = null;
        var activeIndex = -1;

        $(inputSelector).on('input focus', function() {
            var term = $(this).val().trim();
            var $dropdown = $(dropdownSelector);

            clearTimeout(timer);
            timer = setTimeout(function() {
                $.ajax({
                    url: 'processors/get_vehicle_type_suggestions.php',
                    type: 'GET',
                    data: { q: term },
                    dataType: 'json',
                    success: function(res) {
                        if (res.success && res.suggestions && res.suggestions.length > 0) {
                            var html = '';
                            res.suggestions.forEach(function(item) {
                                var highlighted = escapeHtml(item);
                                if (term.length > 0) {
                                    var re = new RegExp('(' + escapeRegex(term) + ')', 'gi');
                                    highlighted = highlighted.replace(re, '<strong class="text-warning">$1</strong>');
                                }
                                html += '<a class="dropdown-item py-2 px-3 d-flex align-items-center suggestion-item" href="javascript:void(0)" data-value="' + escapeHtml(item) + '">' +
                                        '<i class="bi bi-truck me-2 text-muted" style="font-size: 13px;"></i>' +
                                        '<span>' + highlighted + '</span>' +
                                        '</a>';
                            });
                            $dropdown.html(html).show();
                            activeIndex = -1;
                        } else if (term.length > 0) {
                            $dropdown.html('<div class="dropdown-header text-muted py-2 px-3 small"><i class="bi bi-pencil me-1"></i>New equipment: "' + escapeHtml(term) + '" (manual entry)</div>').show();
                            activeIndex = -1;
                        } else {
                            $dropdown.hide();
                        }
                    }
                });
            }, 200);
        });

        $(dropdownSelector).on('click', '.suggestion-item', function(e) {
            e.preventDefault();
            var val = $(this).data('value');
            $(inputSelector).val(val);
            $(dropdownSelector).hide();
        });

        $(document).on('click', function(e) {
            if (!$(e.target).closest(inputSelector + ', ' + dropdownSelector).length) {
                $(dropdownSelector).hide();
            }
        });
    }

    // Vehicle Modal Handlers
    function viewVehicle(data) {
        document.getElementById('view_vehicle_type').textContent = data.vehicle_type || '-';
        document.getElementById('view_vehicle_number').textContent = data.vehicle_number || '-';
        document.getElementById('view_chassis_number').textContent = data.chassis_number || '-';
        document.getElementById('view_current_condition').textContent = data.current_condition || '-';
        if (document.getElementById('view_vehicle_initial_count')) {
            document.getElementById('view_vehicle_initial_count').textContent = (data.initial_count !== undefined && data.initial_count !== null) ? data.initial_count : (data.available_quantity || '1');
        }
        if (document.getElementById('view_vehicle_received_quantity')) {
            document.getElementById('view_vehicle_received_quantity').textContent = (data.received_quantity !== undefined && data.received_quantity !== null) ? data.received_quantity : '0';
        }
        if (document.getElementById('view_vehicle_quantity')) {
            document.getElementById('view_vehicle_quantity').textContent = data.available_quantity || '1';
        }
        if (document.getElementById('view_vehicle_specification')) {
            document.getElementById('view_vehicle_specification').textContent = data.specification || '-';
        }
        if (document.getElementById('view_vehicle_issue_order_no')) {
            document.getElementById('view_vehicle_issue_order_no').textContent = data.issue_order_no || '-';
        }
        if (document.getElementById('view_vehicle_received_from')) {
            document.getElementById('view_vehicle_received_from').textContent = data.received_from || '-';
        }
        if (document.getElementById('view_vehicle_receipt_no')) {
            document.getElementById('view_vehicle_receipt_no').textContent = data.receipt_no || '-';
        }
        document.getElementById('view_other_details').textContent = data.remarks || data.other_details || '-';
        var modal = new bootstrap.Modal(document.getElementById('viewVehicleModal'));
        modal.show();
    }

    function editVehicle(data) {
        document.getElementById('edit_vehicle_id').value = data.id || '';
        document.getElementById('edit_vehicle_type').value = data.vehicle_type || '';
        document.getElementById('edit_vehicle_number').value = data.vehicle_number || '';
        document.getElementById('edit_chassis_number').value = data.chassis_number || '';
        document.getElementById('edit_current_condition').value = data.current_condition || 'Running';
        if (document.getElementById('edit_vehicle_initial_count')) {
            document.getElementById('edit_vehicle_initial_count').value = (data.initial_count !== undefined && data.initial_count !== null) ? data.initial_count : (data.available_quantity || 1);
        }
        if (document.getElementById('edit_vehicle_received_quantity')) {
            document.getElementById('edit_vehicle_received_quantity').value = (data.received_quantity !== undefined && data.received_quantity !== null) ? data.received_quantity : 0;
        }
        if (document.getElementById('edit_vehicle_available_quantity')) {
            document.getElementById('edit_vehicle_available_quantity').value = data.available_quantity || 1;
        }
        if (document.getElementById('edit_vehicle_specification')) {
            document.getElementById('edit_vehicle_specification').value = data.specification || '';
        }
        if (document.getElementById('edit_vehicle_issue_order_no')) {
            document.getElementById('edit_vehicle_issue_order_no').value = data.issue_order_no || '';
        }
        if (document.getElementById('edit_vehicle_received_from')) {
            document.getElementById('edit_vehicle_received_from').value = data.received_from || '';
        }
        if (document.getElementById('edit_vehicle_receipt_no')) {
            document.getElementById('edit_vehicle_receipt_no').value = data.receipt_no || '';
        }
        if (document.getElementById('edit_vehicle_remarks')) {
            document.getElementById('edit_vehicle_remarks').value = data.remarks || data.other_details || '';
        }
        document.getElementById('edit_vehicle_unit').value = data.unit || 'range_veterinary_officer';
        
        calcEditVehicleAvailability();

        var modal = new bootstrap.Modal(document.getElementById('editVehicleModal'));
        modal.show();
    }

    function calcAddVehicleAvailability() {
        var base = parseInt(document.getElementById('add_vehicle_initial_count').value) || 0;
        var recv = parseInt(document.getElementById('add_vehicle_received_quantity').value) || 0;
        document.getElementById('add_vehicle_available_quantity').value = base + recv;
    }

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
                        if (res.success) {
                            Swal.fire('Removed!', res.message, 'success');
                            fleetTable.row('#vehicle-row-' + id).remove().draw(false);
                        } else { 
                            Swal.fire('Failed', res.message, 'error'); 
                        }
                    }
                });
            }
        });
    }

    // Running Chart Handlers
    function viewRunningChart(data) {
        document.getElementById('view_rc_vehicle_number').textContent = data.vehicle_number || '-';
        document.getElementById('view_rc_vehicle_type').textContent = data.vehicle_type || '-';
        document.getElementById('view_rc_trip_date').textContent = data.trip_date || '-';
        document.getElementById('view_rc_time_out').textContent = data.time_out || '-';
        document.getElementById('view_rc_time_in').textContent = data.time_in || '-';
        document.getElementById('view_rc_driver_name').textContent = data.driver_name || '-';
        document.getElementById('view_rc_driver_initials').textContent = data.driver_initials || '-';
        document.getElementById('view_rc_route_places_visited').textContent = data.route_places_visited || '-';
        document.getElementById('view_rc_purpose_of_trip').textContent = data.purpose_of_trip || '-';
        document.getElementById('view_rc_milometer_out').textContent = parseFloat(data.milometer_out || 0).toFixed(1);
        document.getElementById('view_rc_milometer_in').textContent = parseFloat(data.milometer_in || 0).toFixed(1);
        document.getElementById('view_rc_total_mileage').textContent = parseFloat(data.total_mileage || 0).toFixed(1) + ' Miles';
        document.getElementById('view_rc_miles_per_gallon').textContent = parseFloat(data.miles_per_gallon || 0).toFixed(2) + ' MPG';
        document.getElementById('view_rc_fuel_position').textContent = parseFloat(data.fuel_position_in_tank || 0).toFixed(2);
        document.getElementById('view_rc_fuel_drawn').textContent = '+' + parseFloat(data.fuel_drawn || 0).toFixed(2);
        document.getElementById('view_rc_fuel_consumed').textContent = '-' + parseFloat(data.fuel_consumed || 0).toFixed(2);
        document.getElementById('view_rc_fuel_balance').textContent = parseFloat(data.fuel_balance || 0).toFixed(2);
        document.getElementById('view_rc_engine_oil_drawn').textContent = parseFloat(data.engine_oil_drawn || 0).toFixed(2) + ' Pts';
        document.getElementById('view_rc_remarks').textContent = data.remarks || 'None provided.';
        var modal = new bootstrap.Modal(document.getElementById('viewRunningChartModal'));
        modal.show();
    }

    function editRunningChart(data) {
        document.getElementById('edit_rc_id').value = data.id || '';
        document.getElementById('edit_rc_vehicle_id').value = data.vehicle_id || '';
        document.getElementById('edit_rc_trip_date').value = data.trip_date || '';
        document.getElementById('edit_rc_time_out').value = data.time_out || '';
        document.getElementById('edit_rc_time_in').value = data.time_in || '';
        document.getElementById('edit_rc_driver_name').value = data.driver_name || '';
        document.getElementById('edit_rc_driver_initials').value = data.driver_initials || '';
        document.getElementById('edit_rc_route_places_visited').value = data.route_places_visited || '';
        document.getElementById('edit_rc_purpose_of_trip').value = data.purpose_of_trip || '';
        document.getElementById('edit_rc_milometer_out').value = data.milometer_out || '';
        document.getElementById('edit_rc_milometer_in').value = data.milometer_in || '';
        document.getElementById('edit_rc_total_mileage').value = data.total_mileage || '';
        document.getElementById('edit_rc_miles_per_gallon').value = data.miles_per_gallon || '';
        document.getElementById('edit_rc_fuel_position_in_tank').value = data.fuel_position_in_tank || '';
        document.getElementById('edit_rc_fuel_drawn').value = data.fuel_drawn || '';
        document.getElementById('edit_rc_fuel_consumed').value = data.fuel_consumed || '';
        document.getElementById('edit_rc_fuel_balance').value = data.fuel_balance || '';
        document.getElementById('edit_rc_engine_oil_drawn').value = data.engine_oil_drawn || '';
        document.getElementById('edit_rc_remarks').value = data.remarks || '';
        
        // Fetch previous status for accurate editing
        var vId = data.vehicle_id;
        var currId = data.id;
        if (vId) {
            $.ajax({
                url: 'processors/get_vehicle_latest_status.php',
                type: 'GET',
                data: { vehicle_id: vId, current_id: currId },
                dataType: 'json',
                success: function(res) {
                    if (res.success) {
                        var prevFuel = parseFloat(res.latest_fuel_balance) || 0;
                        var prevMilo = parseFloat(res.latest_milometer) || 0;
                        $('#edit_rc_prev_fuel_balance').val(prevFuel.toFixed(2));
                        $('#edit_rc_prev_fuel_val').text(prevFuel.toFixed(2));
                        $('#edit_rc_prev_milo_val').text(prevMilo.toFixed(1));
                    }
                }
            });
        }

        var modal = new bootstrap.Modal(document.getElementById('editRunningChartModal'));
        modal.show();
    }

    function handleRunningChartDelete(id) {
        Swal.fire({
            title: 'Delete Running Chart Entry?',
            text: "This operation will drop the active journey log and fuel calculation.",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#b08723',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Yes, Delete'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: 'processors/delete_running_chart.php',
                    type: 'POST',
                    data: { id: id },
                    dataType: 'json',
                    success: function(res) {
                        if (res.success) {
                            Swal.fire('Removed!', res.message, 'success');
                            runningChartTable.row('#rc-row-' + id).remove().draw(false);
                        } else { 
                            Swal.fire('Failed', res.message, 'error'); 
                        }
                    }
                });
            }
        });
    }

    // Repair Modal Handlers
    function viewRepair(data) {
        document.getElementById('view_repair_id').value = data.id || '';
        document.getElementById('view_repair_vehicle_number').textContent = data.vehicle_number || '-';
        document.getElementById('view_repair_date').textContent = data.repair_date || '-';
        document.getElementById('view_repair_done').textContent = data.repair_done || '-';
        document.getElementById('view_place_of_repair').textContent = data.place_of_repair || '-';
        document.getElementById('view_repair_invoice_ref').textContent = data.invoice_ref || 'None';

        var cost = parseFloat(data.transaction_amount > 0 ? data.transaction_amount : data.amount) || 0;
        document.getElementById('view_repair_amount').textContent = cost.toLocaleString('en-US', {minimumFractionDigits: 2});
        document.getElementById('view_repair_description').textContent = data.repair_description || '-';

        // Approval Status Badge
        var status = data.approval_status || 'Approved';
        var badgeHtml = '';
        if (status === 'Approved') {
            badgeHtml = '<span class="badge bg-success text-white px-2 py-1"><i class="bi bi-check-circle-fill me-1"></i>Approved</span>';
        } else if (status === 'Pending District Approval') {
            badgeHtml = '<span class="badge bg-warning text-dark px-2 py-1"><i class="bi bi-clock-history me-1"></i>Pending District DD</span>';
        } else if (status === 'Pending Provincial Approval') {
            badgeHtml = '<span class="badge bg-info text-dark px-2 py-1"><i class="bi bi-shield-exclamation me-1"></i>Pending Provincial Director</span>';
        } else if (status === 'Rejected') {
            badgeHtml = '<span class="badge bg-danger text-white px-2 py-1"><i class="bi bi-x-circle-fill me-1"></i>Rejected</span>';
        } else {
            badgeHtml = '<span class="badge bg-secondary">' + escapeHtml(status) + '</span>';
        }
        document.getElementById('view_repair_status_badge').innerHTML = badgeHtml;

        // Authority & Decision info
        document.getElementById('view_repair_authority').textContent = data.approval_authority || (cost > 50000 ? 'Provincial Director' : 'District Deputy Director');
        
        if (status === 'Approved' && data.approved_at) {
            document.getElementById('view_repair_decision_info').textContent = 'Approved by ' + (data.approver_name || 'Authority') + ' on ' + data.approved_at;
        } else if (status === 'Rejected' && data.approved_at) {
            document.getElementById('view_repair_decision_info').textContent = 'Rejected on ' + data.approved_at;
        } else {
            document.getElementById('view_repair_decision_info').textContent = 'Awaiting Review';
        }

        // Rejection reason
        if (status === 'Rejected' && data.rejection_reason) {
            $('#view_repair_rejection_container').removeClass('d-none');
            document.getElementById('view_repair_rejection_reason').textContent = data.rejection_reason;
        } else {
            $('#view_repair_rejection_container').addClass('d-none');
        }

        // Receipt preview/download
        if (data.receipt_file) {
            $('#view_repair_receipt_container').html(
                '<a href="/daph-ep-mis/' + escapeHtml(data.receipt_file) + '" target="_blank" class="btn btn-sm btn-outline-primary shadow-sm">' +
                '<i class="bi bi-file-earmark-pdf-fill text-danger me-1"></i>Open Attached Receipt Document</a>'
            );
        } else {
            $('#view_repair_receipt_container').html('<span class="text-muted fst-italic small"><i class="bi bi-info-circle me-1"></i>No digital receipt file attached to this record.</span>');
        }

        // Approval action buttons in view modal
        var canApprove = <?php echo json_encode(in_array($_SESSION['role'], ['provincial_director', 'district_dd', 'administrator'])); ?>;
        if (canApprove && status.indexOf('Pending') !== -1) {
            $('#view_repair_approval_actions').removeClass('d-none');
        } else {
            $('#view_repair_approval_actions').addClass('d-none');
        }

        var modal = new bootstrap.Modal(document.getElementById('viewRepairModal'));
        modal.show();
    }

    function editRepair(data) {
        document.getElementById('edit_repair_id').value = data.id || '';
        document.getElementById('edit_repair_vehicle_id').value = data.vehicle_id || '';
        document.getElementById('edit_repair_date').value = data.repair_date || '';
        document.getElementById('edit_repair_done').value = data.repair_done || '';
        document.getElementById('edit_place_of_repair').value = data.place_of_repair || '';
        if (document.getElementById('edit_invoice_ref')) {
            document.getElementById('edit_invoice_ref').value = data.invoice_ref || '';
        }
        var cost = data.transaction_amount > 0 ? data.transaction_amount : data.amount;
        document.getElementById('edit_repair_amount').value = cost || '';
        if (document.getElementById('edit_repair_transaction_amount')) {
            document.getElementById('edit_repair_transaction_amount').value = cost || '';
        }
        document.getElementById('edit_repair_description').value = data.repair_description || '';

        // Current receipt preview indicator
        if (data.receipt_file) {
            $('#edit_receipt_preview_container').html(
                '<span class="badge bg-light text-dark border me-2"><i class="bi bi-paperclip me-1"></i>Attached:</span> ' +
                '<a href="/daph-ep-mis/' + escapeHtml(data.receipt_file) + '" target="_blank" class="text-decoration-none small text-primary fw-semibold">' +
                '<i class="bi bi-file-earmark-pdf text-danger me-1"></i>View Current Document</a>'
            );
        } else {
            $('#edit_receipt_preview_container').html('<span class="text-muted small">No receipt currently attached.</span>');
        }

        updateEditRepairRouting();
        var modal = new bootstrap.Modal(document.getElementById('editRepairModal'));
        modal.show();
    }

    // Approval Workflow Handlers
    function handleRepairAction(action) {
        var repairId = $('#view_repair_id').val();
        if (!repairId) return;

        if (action === 'approve') {
            Swal.fire({
                title: 'Approve Repair Request?',
                text: 'Are you sure you want to approve this vehicle maintenance expenditure?',
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#198754',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Yes, Approve'
            }).then((result) => {
                if (result.isConfirmed) {
                    submitApprovalDecision(repairId, 'approve', '');
                }
            });
        } else if (action === 'reject') {
            Swal.fire({
                title: 'Reject Repair Request',
                text: 'Please specify the rejection justification reason:',
                input: 'textarea',
                inputPlaceholder: 'Type reason for rejection...',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#dc3545',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Confirm Rejection',
                inputValidator: (value) => {
                    if (!value || !value.trim()) {
                        return 'You must enter a reason for rejection!';
                    }
                }
            }).then((result) => {
                if (result.isConfirmed && result.value) {
                    submitApprovalDecision(repairId, 'reject', result.value.trim());
                }
            });
        }
    }

    function quickApproveRepair(id) {
        Swal.fire({
            title: 'Approve Repair Request?',
            text: 'Confirm approval of this maintenance expenditure record.',
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#198754',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Yes, Approve'
        }).then((result) => {
            if (result.isConfirmed) {
                submitApprovalDecision(id, 'approve', '');
            }
        });
    }

    function quickRejectRepair(id) {
        Swal.fire({
            title: 'Reject Repair Request',
            text: 'Please specify reason for rejection:',
            input: 'textarea',
            inputPlaceholder: 'Enter rejection notes...',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc3545',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Confirm Rejection',
            inputValidator: (value) => {
                if (!value || !value.trim()) {
                    return 'Rejection reason is mandatory!';
                }
            }
        }).then((result) => {
            if (result.isConfirmed && result.value) {
                submitApprovalDecision(id, 'reject', result.value.trim());
            }
        });
    }

    function submitApprovalDecision(repairId, action, reason) {
        $.ajax({
            url: 'processors/approve_vehicle_repair.php',
            type: 'POST',
            data: {
                repair_id: repairId,
                action: action,
                rejection_reason: reason
            },
            dataType: 'json',
            success: function(res) {
                if (res.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Decision Recorded!',
                        text: res.message,
                        confirmButtonColor: '#b08723'
                    }).then(() => {
                        location.reload();
                    });
                } else {
                    Swal.fire('Action Failed', res.message, 'error');
                }
            },
            error: function() {
                Swal.fire('Error', 'Failed to communicate with server.', 'error');
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
                        if (res.success) {
                            Swal.fire('Cleared!', res.message, 'success');
                            repairsTable.row('#repair-row-' + id).remove().draw(false);
                        } else { 
                            Swal.fire('Failed', res.message, 'error'); 
                        }
                    }
                });
            }
        });
    }
</script>

<?php require_once '../../../includes/footer.php'; ?>