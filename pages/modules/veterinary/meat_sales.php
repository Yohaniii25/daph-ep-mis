<?php
session_start();
require_once __DIR__ . '/../../../config/db_connect.php';

/** @var mysqli $mysqli */
global $mysqli;

if (!isset($_SESSION['logged_in']) || !in_array($_SESSION['role'], ['veterinary_surgeon', 'sms', 'livestock_officer', 'provincial_director', 'district_director'])) {
    header("Location: ../../../index.php");
    exit();
}

$user_id = $_SESSION['user_id'] ?? null;
$requested_range_id = isset($_GET['range_id']) && is_numeric($_GET['range_id']) ? (int)$_GET['range_id'] : null;
$range_id = $requested_range_id ?: ($_SESSION['range_id'] ?? null);

$range_name = 'All Ranges / General';
$district_name = 'Eastern Province';
$district_id = null;

if (!empty($range_id)) {
    $details_sql = "
        SELECT vr.name AS range_name, d.name AS district_name, d.id AS district_id
        FROM veterinary_ranges vr
        LEFT JOIN districts d ON vr.district_id = d.id
        WHERE vr.id = ?
    ";
    $details_query = $mysqli->prepare($details_sql);
    if ($details_query) {
        $details_query->bind_param("i", $range_id);
        $details_query->execute();
        $details_result = $details_query->get_result();
        if ($data = $details_result->fetch_assoc()) {
            $range_name = $data['range_name'];
            $district_name = $data['district_name'];
            $district_id = $data['district_id'];
        }
        $details_query->close();
    }
}

// Global Month, Year, Meat Category, and Location Filters
$selected_year = isset($_GET['year']) ? ($_GET['year'] === 'all' ? 'all' : intval($_GET['year'])) : intval(date('Y'));
$selected_month = isset($_GET['month']) ? ($_GET['month'] === 'all' ? 'all' : intval($_GET['month'])) : 'all';
$selected_category = isset($_GET['category']) ? trim($_GET['category']) : 'all';
$selected_location = isset($_GET['location']) && trim($_GET['location']) !== '' && $_GET['location'] !== 'all' ? trim($_GET['location']) : 'all';
$range_param = $requested_range_id ? "&range_id=" . $requested_range_id : ($range_id ? "&range_id=" . $range_id : "");

$months_map = [
    1 => 'January', 2 => 'February', 3 => 'March', 4 => 'April',
    5 => 'May', 6 => 'June', 7 => 'July', 8 => 'August',
    9 => 'September', 10 => 'October', 11 => 'November', 12 => 'December'
];

// Handle CRUD operations
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add') {
        $r_year = !empty($_POST['report_year']) ? intval($_POST['report_year']) : intval(date('Y'));
        $r_month = (!empty($_POST['report_month']) && $_POST['report_month'] !== 'all') ? intval($_POST['report_month']) : null;
        $record_date = sprintf('%04d-%02d-01', $r_year, $r_month ?: 1);
        $meat_type = trim($_POST['meat_type'] ?? 'Beef');
        $other_meat_name = ($meat_type === 'Other') ? trim($_POST['other_meat_name'] ?? '') : null;
        $sales_volume_kg = floatval($_POST['sales_volume_kg'] ?? 0);
        $value_per_kilo = floatval($_POST['value_per_kilo'] ?? 0);
        $total_sales_amount = floatval($_POST['total_sales_amount'] ?? 0);
        $outlet_name = trim($_POST['outlet_name_address'] ?? '');
        $contact_no = trim($_POST['contact_no'] ?? '');
        $remarks = trim($_POST['remarks'] ?? '');
        $vs_range = $range_name;

        $insert_sql = "
            INSERT INTO meat_sales_records 
            (district_id, range_id, vs_range, report_year, report_month, record_date, meat_type, other_meat_name, sales_volume_kg, value_per_kilo, total_sales_amount, outlet_name_address, contact_no, remarks, created_by)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ";
        $stmt = $mysqli->prepare($insert_sql);
        if ($stmt) {
            $stmt->bind_param(
                "iisissssdddsssi",
                $district_id,
                $range_id,
                $vs_range,
                $r_year,
                $r_month,
                $record_date,
                $meat_type,
                $other_meat_name,
                $sales_volume_kg,
                $value_per_kilo,
                $total_sales_amount,
                $outlet_name,
                $contact_no,
                $remarks,
                $user_id
            );
            if ($stmt->execute()) {
                header("Location: meat_sales.php?year=$r_year&month=" . ($r_month ?? 'all') . $range_param . "&status=success&msg=" . urlencode("Meat sales record added successfully."));
            } else {
                header("Location: meat_sales.php?status=error" . $range_param . "&msg=" . urlencode("Database insert failed: " . $stmt->error));
            }
            $stmt->close();
        } else {
            header("Location: meat_sales.php?status=error" . $range_param . "&msg=" . urlencode("Query preparation failed: " . $mysqli->error));
        }
        exit();
    } elseif ($action === 'edit') {
        $id = intval($_POST['id'] ?? 0);
        $r_year = !empty($_POST['report_year']) ? intval($_POST['report_year']) : intval(date('Y'));
        $r_month = (!empty($_POST['report_month']) && $_POST['report_month'] !== 'all') ? intval($_POST['report_month']) : null;
        $record_date = sprintf('%04d-%02d-01', $r_year, $r_month ?: 1);
        $meat_type = trim($_POST['meat_type'] ?? 'Beef');
        $other_meat_name = ($meat_type === 'Other') ? trim($_POST['other_meat_name'] ?? '') : null;
        $sales_volume_kg = floatval($_POST['sales_volume_kg'] ?? 0);
        $value_per_kilo = floatval($_POST['value_per_kilo'] ?? 0);
        $total_sales_amount = floatval($_POST['total_sales_amount'] ?? 0);
        $outlet_name = trim($_POST['outlet_name_address'] ?? '');
        $contact_no = trim($_POST['contact_no'] ?? '');
        $remarks = trim($_POST['remarks'] ?? '');

        $update_sql = "
            UPDATE meat_sales_records 
            SET report_year = ?, report_month = ?, record_date = ?, meat_type = ?, other_meat_name = ?, sales_volume_kg = ?, value_per_kilo = ?, total_sales_amount = ?, outlet_name_address = ?, contact_no = ?, remarks = ?
            WHERE id = ?
        ";
        $stmt = $mysqli->prepare($update_sql);
        if ($stmt) {
            $stmt->bind_param(
                "iissssdddsssi",
                $r_year,
                $r_month,
                $record_date,
                $meat_type,
                $other_meat_name,
                $sales_volume_kg,
                $value_per_kilo,
                $total_sales_amount,
                $outlet_name,
                $contact_no,
                $remarks,
                $id
            );
            if ($stmt->execute()) {
                header("Location: meat_sales.php?year=$r_year&month=" . ($r_month ?? 'all') . $range_param . "&status=success&msg=" . urlencode("Meat sales record updated successfully."));
            } else {
                header("Location: meat_sales.php?status=error" . $range_param . "&msg=" . urlencode("Database update failed: " . $stmt->error));
            }
            $stmt->close();
        } else {
            header("Location: meat_sales.php?status=error" . $range_param . "&msg=" . urlencode("Query preparation failed: " . $mysqli->error));
        }
        exit();
    } elseif ($action === 'delete') {
        $id = intval($_POST['id'] ?? 0);
        $stmt = $mysqli->prepare("DELETE FROM meat_sales_records WHERE id = ?");
        if ($stmt) {
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $stmt->close();
            header("Location: meat_sales.php?year=$selected_year&month=$selected_month" . $range_param . "&status=success&msg=" . urlencode("Meat sales record deleted."));
        } else {
            header("Location: meat_sales.php?status=error" . $range_param . "&msg=" . urlencode("Delete failed."));
        }
        exit();
    }
}

// Build Query with Filters
$where_clauses = ["1=1"];
$params = [];
$types = "";

if (!empty($range_id)) {
    $where_clauses[] = "(range_id = ? OR vs_range = ?)";
    $params[] = $range_id;
    $params[] = $range_name;
    $types .= "is";
}

if ($selected_year !== 'all') {
    $where_clauses[] = "report_year = ?";
    $params[] = $selected_year;
    $types .= "i";
}

if ($selected_month !== 'all') {
    $where_clauses[] = "report_month = ?";
    $params[] = $selected_month;
    $types .= "i";
}

if ($selected_category !== 'all') {
    $where_clauses[] = "meat_type = ?";
    $params[] = $selected_category;
    $types .= "s";
}

if ($selected_location !== 'all') {
    $where_clauses[] = "outlet_name_address = ?";
    $params[] = $selected_location;
    $types .= "s";
}

$where_sql = implode(" AND ", $where_clauses);
$query_sql = "SELECT * FROM meat_sales_records WHERE $where_sql ORDER BY report_year DESC, report_month DESC, id DESC";

$stmt = $mysqli->prepare($query_sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$records_result = $stmt->get_result();

$meat_records = [];
$total_volume = 0;
$total_turnover = 0;
$category_breakdown = ['Beef' => 0, 'Mutton' => 0, 'Chicken' => 0, 'Other' => 0];
$other_breakdown = [];

while ($row = $records_result->fetch_assoc()) {
    $meat_records[] = $row;
    $total_volume += floatval($row['sales_volume_kg']);
    $total_turnover += floatval($row['total_sales_amount']);
    if (isset($category_breakdown[$row['meat_type']])) {
        $category_breakdown[$row['meat_type']] += floatval($row['sales_volume_kg']);
    }
    if ($row['meat_type'] === 'Other') {
        $sub = !empty($row['other_meat_name']) ? trim($row['other_meat_name']) : 'Others';
        $other_breakdown[$sub] = ($other_breakdown[$sub] ?? 0) + floatval($row['sales_volume_kg']);
    }
}
$stmt->close();

$avg_value_per_kg = ($total_volume > 0) ? ($total_turnover / $total_volume) : 0;

// Automated Monthly & Yearly Summary Calculator (Targeted by Location if selected, else Range-wide)
$monthly_summary = [];
$yearly_summary = [];

$sum_where = ["(range_id = ? OR vs_range = ?)"];
$sum_params = [$range_id, $range_name];
$sum_types = "is";

if ($selected_location !== 'all') {
    $sum_where[] = "outlet_name_address = ?";
    $sum_params[] = $selected_location;
    $sum_types .= "s";
}

$sum_where_sql = implode(" AND ", $sum_where);
$summary_sql = "
    SELECT 
        report_year,
        report_month,
        meat_type,
        other_meat_name,
        SUM(sales_volume_kg) as sub_kg,
        SUM(total_sales_amount) as sub_amount,
        COUNT(*) as record_count
    FROM meat_sales_records
    WHERE $sum_where_sql
    GROUP BY report_year, report_month, meat_type, other_meat_name
    ORDER BY report_year DESC, report_month DESC
";
$summary_stmt = $mysqli->prepare($summary_sql);
if ($summary_stmt) {
    $summary_stmt->bind_param($sum_types, ...$sum_params);
    $summary_stmt->execute();
    $s_res = $summary_stmt->get_result();
    while ($sr = $s_res->fetch_assoc()) {
        $yr = intval($sr['report_year']);
        $mo = !empty($sr['report_month']) ? intval($sr['report_month']) : 0;
        $m_type = $sr['meat_type'];
        $o_name = trim($sr['other_meat_name'] ?? '');
        $label = ($m_type === 'Other' && !empty($o_name)) ? $o_name : $m_type;
        
        if (!isset($yearly_summary[$yr])) {
            $yearly_summary[$yr] = ['total_kg' => 0, 'total_amount' => 0, 'types' => []];
        }
        $yearly_summary[$yr]['total_kg'] += floatval($sr['sub_kg']);
        $yearly_summary[$yr]['total_amount'] += floatval($sr['sub_amount']);
        $yearly_summary[$yr]['types'][$label] = ($yearly_summary[$yr]['types'][$label] ?? 0) + floatval($sr['sub_kg']);
        
        if ($mo > 0) {
            $key = "$yr-" . str_pad($mo, 2, '0', STR_PAD_LEFT);
            if (!isset($monthly_summary[$key])) {
                $monthly_summary[$key] = ['year' => $yr, 'month' => $mo, 'total_kg' => 0, 'total_amount' => 0, 'types' => []];
            }
            $monthly_summary[$key]['total_kg'] += floatval($sr['sub_kg']);
            $monthly_summary[$key]['total_amount'] += floatval($sr['sub_amount']);
            $monthly_summary[$key]['types'][$label] = ($monthly_summary[$key]['types'][$label] ?? 0) + floatval($sr['sub_kg']);
        }
    }
    $summary_stmt->close();
}

// Determine distinct available years for filter
$years_query = $mysqli->query("SELECT DISTINCT report_year FROM meat_sales_records ORDER BY report_year DESC");
$available_years = [];
if ($years_query) {
    while ($y = $years_query->fetch_assoc()) {
        $available_years[] = intval($y['report_year']);
    }
}
if (!in_array(intval(date('Y')), $available_years)) {
    $available_years[] = intval(date('Y'));
    rsort($available_years);
}

// Determine distinct available locations/outlets for filter (automatically includes newly registered ones)
$loc_where = ["outlet_name_address IS NOT NULL AND TRIM(outlet_name_address) != ''"];
$loc_params = [];
$loc_types = "";
if (!empty($range_id)) {
    $loc_where[] = "(range_id = ? OR vs_range = ?)";
    $loc_params[] = $range_id;
    $loc_params[] = $range_name;
    $loc_types .= "is";
}
$loc_sql = "SELECT DISTINCT outlet_name_address FROM meat_sales_records WHERE " . implode(" AND ", $loc_where) . " ORDER BY outlet_name_address ASC";
$loc_stmt = $mysqli->prepare($loc_sql);
$available_locations = [];
if ($loc_stmt) {
    if (!empty($loc_params)) {
        $loc_stmt->bind_param($loc_types, ...$loc_params);
    }
    $loc_stmt->execute();
    $loc_res = $loc_stmt->get_result();
    while ($lr = $loc_res->fetch_assoc()) {
        $available_locations[] = $lr['outlet_name_address'];
    }
    $loc_stmt->close();
}

include '../../../includes/header.php';
?>

<div class="container-fluid px-4 py-3">
    <!-- Breadcrumb -->
    <nav aria-label="breadcrumb" class="mb-3">
        <ol class="breadcrumb mb-0 py-2 px-3 bg-white rounded shadow-sm">
            <li class="breadcrumb-item"><a href="../../../dashboard.php" class="text-decoration-none">Dashboard</a></li>
            <li class="breadcrumb-item"><a href="range_statistics.php<?= $requested_range_id ? '?range_id=' . $requested_range_id : ($range_id ? '?range_id=' . $range_id : '') ?>" class="text-decoration-none">Range Statistics</a></li>
            <li class="breadcrumb-item active fw-bold text-dark" aria-current="page">Meat Sales Details</li>
        </ol>
    </nav>

    <!-- Alerts -->
    <?php if (isset($_GET['status'])): ?>
        <div class="alert alert-<?= $_GET['status'] === 'success' ? 'success' : 'danger' ?> alert-dismissible fade show shadow-sm" role="alert">
            <i class="bi bi-<?= $_GET['status'] === 'success' ? 'check-circle-fill' : 'exclamation-triangle-fill' ?> me-2"></i>
            <?= htmlspecialchars($_GET['msg'] ?? '') ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <!-- Title & Action Bar -->
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4 bg-white p-4 rounded shadow-sm border-start border-danger border-4">
        <div>
            <h3 class="fw-bold mb-1" style="color: #370709;">
                <i class="bi bi-bag-check-fill text-danger me-2"></i>Meat Sales Details Module
            </h3>
            <p class="text-muted small mb-0">
                Track and report wholesale & retail meat sales volumes, value per kilo, and revenues for 
                <strong class="text-dark"><?= htmlspecialchars($range_name) ?></strong> (<?= htmlspecialchars($district_name) ?> District).
            </p>
        </div>
        <div class="d-flex align-items-center gap-2">
            <a href="range_statistics.php<?= $requested_range_id ? '?range_id=' . $requested_range_id : ($range_id ? '?range_id=' . $range_id : '') ?>" class="btn btn-light text-dark border fw-bold btn-sm shadow-sm d-flex align-items-center">
                <i class="bi bi-arrow-left-circle me-1"></i> Range Statistics
            </a>
            <button type="button" class="btn btn-danger text-white fw-bold shadow-sm btn-sm" data-bs-toggle="modal" data-bs-target="#addMeatModal">
                <i class="bi bi-plus-circle me-1"></i> Record Meat Sales
            </button>
        </div>
    </div>

    <!-- GLOBAL REPORTING FILTERS (Month, Year, Category, Location) -->
    <div class="card shadow-sm border-0 mb-4">
        <div class="card-body bg-light p-3 rounded">
            <form method="GET" class="row g-3 align-items-end" id="filterForm">
                <?php if ($requested_range_id): ?>
                    <input type="hidden" name="range_id" value="<?= $requested_range_id ?>">
                <?php endif; ?>
                <div class="col-md-2">
                    <label class="form-label small fw-bold text-secondary mb-1"><i class="bi bi-calendar-check me-1"></i>Year</label>
                    <select name="year" class="form-select form-select-sm" onchange="this.form.submit()">
                        <option value="all" <?= ($selected_year === 'all') ? 'selected' : '' ?>>All Years</option>
                        <?php foreach ($available_years as $yr): ?>
                            <option value="<?= $yr ?>" <?= ($selected_year !== 'all' && intval($selected_year) === $yr) ? 'selected' : '' ?>><?= $yr ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small fw-bold text-secondary mb-1"><i class="bi bi-calendar-month me-1"></i>Month</label>
                    <select name="month" class="form-select form-select-sm" onchange="this.form.submit()">
                        <option value="all" <?= ($selected_month === 'all') ? 'selected' : '' ?>>All Months</option>
                        <?php foreach ($months_map as $m_num => $m_name): ?>
                            <option value="<?= $m_num ?>" <?= ($selected_month !== 'all' && intval($selected_month) === $m_num) ? 'selected' : '' ?>><?= $m_name ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label small fw-bold text-secondary mb-1"><i class="bi bi-tags me-1"></i>Meat Category</label>
                    <select name="category" class="form-select form-select-sm" onchange="this.form.submit()">
                        <option value="all" <?= ($selected_category === 'all') ? 'selected' : '' ?>>All Meat Categories</option>
                        <option value="Beef" <?= ($selected_category === 'Beef') ? 'selected' : '' ?>>Beef</option>
                        <option value="Mutton" <?= ($selected_category === 'Mutton') ? 'selected' : '' ?>>Mutton</option>
                        <option value="Chicken" <?= ($selected_category === 'Chicken') ? 'selected' : '' ?>>Chicken</option>
                        <option value="Other" <?= ($selected_category === 'Other') ? 'selected' : '' ?>>Others (Secondary Livestock)</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label small fw-bold text-secondary mb-1"><i class="bi bi-geo-alt-fill text-danger me-1"></i>Location / Outlet</label>
                    <select name="location" class="form-select form-select-sm" onchange="this.form.submit()">
                        <option value="all" <?= ($selected_location === 'all') ? 'selected' : '' ?>>All Registered Locations (<?= count($available_locations) ?>)</option>
                        <?php foreach ($available_locations as $loc): ?>
                            <option value="<?= htmlspecialchars($loc) ?>" <?= ($selected_location === $loc) ? 'selected' : '' ?>><?= htmlspecialchars($loc) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2 d-flex gap-2">
                    <button type="submit" class="btn btn-sm btn-dark flex-grow-1"><i class="bi bi-funnel-fill me-1"></i>Filter</button>
                    <a href="meat_sales.php<?= $requested_range_id ? '?range_id=' . $requested_range_id : ($range_id ? '?range_id=' . $range_id : '') ?>" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-counterclockwise"></i> Reset</a>
                </div>
            </form>
        </div>
    </div>

    <!-- METRICS & KPI CARDS -->
    <div class="row g-3 mb-4">
        <div class="col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm rounded-3 border-start border-danger border-4 h-100">
                <div class="card-body p-3">
                    <span class="text-muted small text-uppercase fw-bold">Total Sales Turnover</span>
                    <h3 class="fw-bold text-danger mb-0 mt-1">Rs. <?= number_format($total_turnover, 2) ?></h3>
                    <span class="badge bg-danger-subtle text-danger mt-2"><i class="bi bi-cash-stack me-1"></i>Total Revenue</span>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm rounded-3 border-start border-primary border-4 h-100">
                <div class="card-body p-3">
                    <span class="text-muted small text-uppercase fw-bold">Total Sales Volume</span>
                    <h3 class="fw-bold text-primary mb-0 mt-1"><?= number_format($total_volume, 2) ?> <small class="fs-6 text-muted">kg</small></h3>
                    <span class="badge bg-primary-subtle text-primary mt-2"><i class="bi bi-speedometer2 me-1"></i>Weight Sold</span>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm rounded-3 border-start border-success border-4 h-100">
                <div class="card-body p-3">
                    <span class="text-muted small text-uppercase fw-bold">Avg. Value / Kilo</span>
                    <h3 class="fw-bold text-success mb-0 mt-1">Rs. <?= number_format($avg_value_per_kg, 2) ?></h3>
                    <span class="badge bg-success-subtle text-success mt-2"><i class="bi bi-calculator me-1"></i>Weighted Price/kg</span>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm rounded-3 border-start border-warning border-4 h-100">
                <div class="card-body p-3">
                    <span class="text-muted small text-uppercase fw-bold">Category Distribution</span>
                    <div class="d-flex justify-content-between align-items-center mt-2 small">
                        <span><i class="bi bi-circle-fill text-danger me-1"></i>Beef: <strong><?= number_format($category_breakdown['Beef']) ?></strong> kg</span>
                        <span><i class="bi bi-circle-fill text-warning me-1"></i>Chkn: <strong><?= number_format($category_breakdown['Chicken']) ?></strong> kg</span>
                    </div>
                    <div class="d-flex justify-content-between align-items-center mt-1 small">
                        <span><i class="bi bi-circle-fill text-primary me-1"></i>Mut: <strong><?= number_format($category_breakdown['Mutton']) ?></strong> kg</span>
                        <span><i class="bi bi-circle-fill text-dark me-1"></i>Oth: <strong><?= number_format($category_breakdown['Other']) ?></strong> kg</span>
                    </div>
                    <?php if (!empty($other_breakdown)): ?>
                        <div class="mt-1 pt-1 border-top text-truncate small" style="font-size: 0.75rem;">
                            <span class="text-muted fw-bold">Others:</span> 
                            <?php
                                $detail_tags = [];
                                foreach ($other_breakdown as $ok => $ov) {
                                    $detail_tags[] = htmlspecialchars($ok) . ' (' . number_format($ov) . ' kg)';
                                }
                                echo implode(', ', $detail_tags);
                            ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- AUTOMATED SUMMARY DASHBOARD (Real-Time Monthly & Yearly Calculator) -->
    <div class="card shadow-sm border-0 mb-4">
        <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center flex-wrap gap-2">
            <div>
                <h6 class="mb-0 fw-bold text-dark">
                    <i class="bi bi-calculator-fill text-danger me-2"></i>Automated Summary Dashboard (Real-Time Aggregations)
                </h6>
                <small class="text-muted">Real-time aggregated manual entries to eliminate manual end-of-year calculations.</small>
            </div>
            <div class="d-flex align-items-center gap-2">
                <?php if ($selected_location !== 'all'): ?>
                    <span class="badge bg-danger text-white px-3 py-2">
                        <i class="bi bi-geo-alt-fill me-1"></i>Location: <?= htmlspecialchars($selected_location) ?>
                    </span>
                <?php endif; ?>
                <ul class="nav nav-pills nav-fill small gap-2" id="meatSummaryTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active py-1 px-3 fw-bold" id="tab-yearly-summary" data-bs-toggle="pill" data-bs-target="#meatYearlyContent" type="button" role="tab">
                            <i class="bi bi-calendar-check me-1"></i>Yearly Roll-Up
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link py-1 px-3 fw-bold" id="tab-monthly-summary" data-bs-toggle="pill" data-bs-target="#meatMonthlyContent" type="button" role="tab">
                            <i class="bi bi-graph-up me-1"></i>Monthly Progression
                        </button>
                    </li>
                </ul>
            </div>
        </div>
                    <button class="nav-link py-1 px-3 fw-bold" id="tab-monthly-summary" data-bs-toggle="pill" data-bs-target="#meatMonthlyContent" type="button" role="tab">
                        <i class="bi bi-calendar3 me-1"></i>Monthly Progression
                    </button>
                </li>
            </ul>
        </div>
        <div class="card-body p-0">
            <div class="tab-content" id="meatSummaryTabContent">
                <!-- Yearly Aggregations -->
                <div class="tab-pane fade show active p-3" id="meatYearlyContent" role="tabpanel">
                    <div class="table-responsive">
                        <table class="table table-sm table-bordered table-hover align-middle mb-0">
                            <thead class="table-light small text-secondary">
                                <tr>
                                    <th>Report Year</th>
                                    <th class="text-end">Beef (kg)</th>
                                    <th class="text-end">Mutton (kg)</th>
                                    <th class="text-end">Chicken (kg)</th>
                                    <th class="text-end">Others / Secondary (kg)</th>
                                    <th class="text-end bg-light fw-bold">Total Volume (kg)</th>
                                    <th class="text-end bg-light text-danger fw-bold">Total Sales (Rs.)</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($yearly_summary)): ?>
                                    <tr><td colspan="7" class="text-center py-3 text-muted">No sales records compiled yet.</td></tr>
                                <?php else: ?>
                                    <?php foreach ($yearly_summary as $yr => $ydata): ?>
                                        <tr>
                                            <td class="fw-bold text-dark"><i class="bi bi-calendar-event me-1 text-danger"></i>Year <?= $yr ?></td>
                                            <td class="text-end font-monospace"><?= number_format($ydata['types']['Beef'] ?? 0, 2) ?></td>
                                            <td class="text-end font-monospace"><?= number_format($ydata['types']['Mutton'] ?? 0, 2) ?></td>
                                            <td class="text-end font-monospace"><?= number_format($ydata['types']['Chicken'] ?? 0, 2) ?></td>
                                            <td class="text-end font-monospace">
                                                <?php 
                                                    $other_sum = 0;
                                                    $other_sub_parts = [];
                                                    foreach ($ydata['types'] as $tname => $tkg) {
                                                        if (!in_array($tname, ['Beef', 'Mutton', 'Chicken'])) {
                                                            $other_sum += $tkg;
                                                            $other_sub_parts[] = htmlspecialchars($tname) . ': ' . number_format($tkg, 1) . 'kg';
                                                        }
                                                    }
                                                    echo number_format($other_sum, 2);
                                                    if (!empty($other_sub_parts)) {
                                                        echo '<br><small class="text-muted">(' . implode(', ', $other_sub_parts) . ')</small>';
                                                    }
                                                ?>
                                            </td>
                                            <td class="text-end fw-bold text-primary font-monospace bg-light"><?= number_format($ydata['total_kg'], 2) ?></td>
                                            <td class="text-end fw-bold text-danger font-monospace bg-light">Rs. <?= number_format($ydata['total_amount'], 2) ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Monthly Progression Aggregations -->
                <div class="tab-pane fade p-3" id="meatMonthlyContent" role="tabpanel">
                    <div class="table-responsive">
                        <table class="table table-sm table-bordered table-hover align-middle mb-0">
                            <thead class="table-light small text-secondary">
                                <tr>
                                    <th>Period (Month & Year)</th>
                                    <th class="text-end">Beef (kg)</th>
                                    <th class="text-end">Mutton (kg)</th>
                                    <th class="text-end">Chicken (kg)</th>
                                    <th class="text-end">Others / Secondary (kg)</th>
                                    <th class="text-end bg-light fw-bold">Monthly Volume (kg)</th>
                                    <th class="text-end bg-light text-danger fw-bold">Monthly Sales (Rs.)</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($monthly_summary)): ?>
                                    <tr><td colspan="7" class="text-center py-3 text-muted">No monthly breakdown data available.</td></tr>
                                <?php else: ?>
                                    <?php foreach ($monthly_summary as $mkey => $mdata): ?>
                                        <tr>
                                            <td class="fw-bold text-dark">
                                                <i class="bi bi-clock-history me-1 text-primary"></i>
                                                <?= ($months_map[$mdata['month']] ?? 'Month ' . $mdata['month']) . ' ' . $mdata['year'] ?>
                                            </td>
                                            <td class="text-end font-monospace"><?= number_format($mdata['types']['Beef'] ?? 0, 2) ?></td>
                                            <td class="text-end font-monospace"><?= number_format($mdata['types']['Mutton'] ?? 0, 2) ?></td>
                                            <td class="text-end font-monospace"><?= number_format($mdata['types']['Chicken'] ?? 0, 2) ?></td>
                                            <td class="text-end font-monospace">
                                                <?php 
                                                    $m_other_sum = 0;
                                                    $m_other_sub_parts = [];
                                                    foreach ($mdata['types'] as $mtname => $mtkg) {
                                                        if (!in_array($mtname, ['Beef', 'Mutton', 'Chicken'])) {
                                                            $m_other_sum += $mtkg;
                                                            $m_other_sub_parts[] = htmlspecialchars($mtname) . ': ' . number_format($mtkg, 1) . 'kg';
                                                        }
                                                    }
                                                    echo number_format($m_other_sum, 2);
                                                    if (!empty($m_other_sub_parts)) {
                                                        echo '<br><small class="text-muted">(' . implode(', ', $m_other_sub_parts) . ')</small>';
                                                    }
                                                ?>
                                            </td>
                                            <td class="text-end fw-bold text-primary font-monospace bg-light"><?= number_format($mdata['total_kg'], 2) ?></td>
                                            <td class="text-end fw-bold text-danger font-monospace bg-light">Rs. <?= number_format($mdata['total_amount'], 2) ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- MAIN DATA TABLE -->
    <div class="card shadow-sm border-0">
        <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center flex-wrap gap-2">
            <h5 class="card-title mb-0 fw-bold text-dark">
                <i class="bi bi-table me-2 text-danger"></i>Meat Sales Records
                <span class="badge bg-secondary ms-2"><?= count($meat_records) ?> entries</span>
            </h5>
            <div class="small text-muted">
                Period: <strong><?= ($selected_month !== 'all' ? $months_map[$selected_month] . ' ' : '') . ($selected_year === 'all' ? 'All Years' : $selected_year) ?></strong>
                <?php if ($selected_location !== 'all'): ?>
                    | Location: <strong class="text-danger"><?= htmlspecialchars($selected_location) ?></strong>
                <?php endif; ?>
            </div>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover table-striped align-middle mb-0" id="meatSalesTable">
                    <thead class="table-light text-secondary small text-uppercase">
                        <tr>
                            <th class="ps-3">#</th>
                            <th>Period</th>
                            <th>Category</th>
                            <th class="text-end">Sales Volume (kg)</th>
                            <th class="text-end">Value per Kilo (Rs)</th>
                            <th class="text-end">Total Sales (Rs)</th>
                            <th>Outlet / Address</th>
                            <th>Contact</th>
                            <th class="text-center pe-3">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($meat_records)): ?>
                            <tr>
                                <td colspan="9" class="text-center py-5 text-muted">
                                    <i class="bi bi-inbox fs-1 d-block mb-2 text-secondary"></i>
                                    No meat sales records found for the selected period and criteria.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($meat_records as $idx => $r): ?>
                                <?php 
                                    $cat_badge = 'bg-secondary';
                                    if ($r['meat_type'] === 'Beef') $cat_badge = 'bg-danger';
                                    elseif ($r['meat_type'] === 'Chicken') $cat_badge = 'bg-warning text-dark';
                                    elseif ($r['meat_type'] === 'Mutton') $cat_badge = 'bg-primary';
                                    elseif ($r['meat_type'] === 'Other') $cat_badge = 'bg-dark';
                                ?>
                                <tr>
                                    <td class="ps-3 fw-bold text-muted"><?= $idx + 1 ?></td>
                                    <td>
                                        <span class="fw-bold"><?= $r['report_year'] ?></span>
                                        <?php if (!empty($r['report_month']) && isset($months_map[$r['report_month']])): ?>
                                            <span class="badge bg-light text-dark border ms-1"><?= substr($months_map[$r['report_month']], 0, 3) ?></span>
                                        <?php else: ?>
                                            <span class="badge bg-light text-muted border ms-1">Annual</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($r['meat_type'] === 'Other'): ?>
                                            <span class="badge bg-dark px-2 py-1">
                                                <i class="bi bi-tag-fill me-1 text-warning"></i><?= htmlspecialchars(!empty($r['other_meat_name']) ? $r['other_meat_name'] : 'Others') ?>
                                            </span>
                                            <small class="text-muted d-block small">Secondary Livestock</small>
                                        <?php else: ?>
                                            <span class="badge <?= $cat_badge ?> px-2 py-1"><?= htmlspecialchars($r['meat_type']) ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-end fw-bold text-primary font-monospace">
                                        <?= number_format($r['sales_volume_kg'], 2) ?>
                                    </td>
                                    <td class="text-end fw-bold text-success font-monospace">
                                        Rs. <?= number_format($r['value_per_kilo'], 2) ?>
                                    </td>
                                    <td class="text-end fw-bold text-danger font-monospace">
                                        Rs. <?= number_format($r['total_sales_amount'], 2) ?>
                                    </td>
                                    <td>
                                        <span class="fw-medium text-dark"><?= htmlspecialchars($r['outlet_name_address'] ?: 'N/A') ?></span>
                                        <?php if (!empty($r['remarks'])): ?>
                                            <small class="text-muted d-block"><i class="bi bi-info-circle me-1"></i><?= htmlspecialchars($r['remarks']) ?></small>
                                        <?php endif; ?>
                                    </td>
                                    <td class="small text-muted"><?= htmlspecialchars($r['contact_no'] ?: '—') ?></td>
                                    <td class="text-center pe-3">
                                        <div class="btn-group btn-group-sm">
                                            <button type="button" class="btn btn-outline-primary btn-edit-meat" 
                                                data-id="<?= $r['id'] ?>"
                                                data-year="<?= $r['report_year'] ?>"
                                                data-month="<?= $r['report_month'] ?? '' ?>"
                                                data-type="<?= htmlspecialchars($r['meat_type']) ?>"
                                                data-othertype="<?= htmlspecialchars($r['other_meat_name'] ?? '') ?>"
                                                data-volume="<?= $r['sales_volume_kg'] ?>"
                                                data-value="<?= $r['value_per_kilo'] ?>"
                                                data-total="<?= $r['total_sales_amount'] ?>"
                                                data-outlet="<?= htmlspecialchars($r['outlet_name_address'] ?? '') ?>"
                                                data-contact="<?= htmlspecialchars($r['contact_no'] ?? '') ?>"
                                                data-remarks="<?= htmlspecialchars($r['remarks'] ?? '') ?>"
                                                title="Edit Record">
                                                <i class="bi bi-pencil-fill"></i>
                                            </button>
                                            <button type="button" class="btn btn-outline-danger btn-delete-meat" 
                                                data-id="<?= $r['id'] ?>"
                                                data-desc="<?= htmlspecialchars($r['meat_type']) ?> (<?= number_format($r['sales_volume_kg'], 2) ?> kg)"
                                                title="Delete Record">
                                                <i class="bi bi-trash-fill"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- ADD MEAT SALES MODAL -->
<div class="modal fade" id="addMeatModal" tabindex="-1" aria-labelledby="addMeatModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title fw-bold" id="addMeatModalLabel">
                    <i class="bi bi-plus-circle me-2"></i>Record Meat Sales Entry
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST" action="meat_sales.php<?= $requested_range_id ? '?range_id=' . $requested_range_id : ($range_id ? '?range_id=' . $range_id : '') ?>" id="addMeatForm">
                <input type="hidden" name="action" value="add">
                <div class="modal-body p-4">
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Report Year <span class="text-danger">*</span></label>
                            <input type="number" name="report_year" id="add_report_year" class="form-control" value="<?= date('Y') ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Report Month</label>
                            <select name="report_month" id="add_report_month" class="form-select">
                                <option value="">Annual Aggregate (Full Year)</option>
                                <?php foreach ($months_map as $num => $name): ?>
                                    <option value="<?= $num ?>" <?= intval(date('n')) === $num ? 'selected' : '' ?>><?= $name ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <!-- Categorization strictly by Beef, Mutton, Chicken, and Other -->
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Meat Category <span class="text-danger">*</span></label>
                        <div class="row g-2">
                            <div class="col-3">
                                <input type="radio" class="btn-check" name="meat_type" id="add_type_beef" value="Beef" checked autocomplete="off">
                                <label class="btn btn-outline-danger w-100 fw-bold py-2" for="add_type_beef">
                                    <i class="bi bi-shield-shaded d-block fs-5"></i>Beef
                                </label>
                            </div>
                            <div class="col-3">
                                <input type="radio" class="btn-check" name="meat_type" id="add_type_mutton" value="Mutton" autocomplete="off">
                                <label class="btn btn-outline-primary w-100 fw-bold py-2" for="add_type_mutton">
                                    <i class="bi bi-shield-shaded d-block fs-5"></i>Mutton
                                </label>
                            </div>
                            <div class="col-3">
                                <input type="radio" class="btn-check" name="meat_type" id="add_type_chicken" value="Chicken" autocomplete="off">
                                <label class="btn btn-outline-warning w-100 fw-bold py-2 text-dark" for="add_type_chicken">
                                    <i class="bi bi-shield-shaded d-block fs-5"></i>Chicken
                                </label>
                            </div>
                            <div class="col-3">
                                <input type="radio" class="btn-check" name="meat_type" id="add_type_other" value="Other" autocomplete="off">
                                <label class="btn btn-outline-dark w-100 fw-bold py-2" for="add_type_other">
                                    <i class="bi bi-plus-circle-dotted d-block fs-5"></i>Others
                                </label>
                            </div>
                        </div>
                    </div>

                    <!-- Custom other meat name input -->
                    <div class="mb-3 d-none" id="add_other_meat_wrapper">
                        <div class="p-3 bg-white border border-danger rounded shadow-sm">
                            <label class="form-label small fw-bold text-dark mb-1">
                                <i class="bi bi-pencil-square text-danger me-1"></i>Specify Secondary Livestock Name <span class="text-danger">*</span>
                            </label>
                            <input type="text" name="other_meat_name" id="add_other_meat_name" class="form-control" placeholder="e.g. Pig, Rabbit, Quail, Turkey">
                            <small class="text-muted">Explicitly record secondary livestock so specific sales are not lost under a generic label.</small>
                        </div>
                    </div>

                    <!-- Value per Kilo and Total Sales Amount Manual Entry Fields -->
                    <div class="card bg-light border-0 p-3 mb-3 rounded">
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label small fw-bold">Sales Volume (kg) <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <input type="number" step="0.01" name="sales_volume_kg" id="add_sales_volume" class="form-control" required min="0" placeholder="0.00">
                                    <span class="input-group-text bg-white">kg</span>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small fw-bold">Value per Kilo (Rs) <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text bg-white">Rs.</span>
                                    <input type="number" step="0.01" name="value_per_kilo" id="add_value_per_kilo" class="form-control" required min="0" placeholder="0.00">
                                </div>
                                <small class="text-muted">Manual entry price/kg</small>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small fw-bold text-danger">Total Sales Amount (Rs) <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text bg-white text-danger fw-bold">Rs.</span>
                                    <input type="number" step="0.01" name="total_sales_amount" id="add_total_sales_amount" class="form-control fw-bold border-danger" required min="0" placeholder="0.00">
                                </div>
                                <small class="text-muted">Auto-computed or manual override</small>
                            </div>
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Outlet / Market Address</label>
                            <input type="text" name="outlet_name_address" id="add_outlet_name_address" class="form-control" list="meat_outlets_list" placeholder="e.g. Town Center Meat Stall, No. 45 Main Street">
                            <datalist id="meat_outlets_list">
                                <?php foreach ($available_locations as $loc): ?>
                                    <option value="<?= htmlspecialchars($loc) ?>">
                                <?php endforeach; ?>
                            </datalist>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Contact Telephone</label>
                            <input type="text" name="contact_no" class="form-control" placeholder="e.g. 065-2224567">
                        </div>
                    </div>

                    <div class="mb-0">
                        <label class="form-label small fw-bold">Remarks / Inspection Notes</label>
                        <textarea name="remarks" class="form-control" rows="2" placeholder="Optional notes on supply batch, cold-chain compliance, or slaughter permit..."></textarea>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger text-white fw-bold"><i class="bi bi-check-circle-fill me-1"></i>Save Meat Sales</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- EDIT MEAT SALES MODAL -->
<div class="modal fade" id="editMeatModal" tabindex="-1" aria-labelledby="editMeatModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title fw-bold" id="editMeatModalLabel">
                    <i class="bi bi-pencil-square me-2"></i>Edit Meat Sales Entry
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST" action="meat_sales.php<?= $requested_range_id ? '?range_id=' . $requested_range_id : ($range_id ? '?range_id=' . $range_id : '') ?>" id="editMeatForm">
                <input type="hidden" name="action" value="edit">
                <input type="hidden" name="id" id="edit_meat_id">
                <div class="modal-body p-4">
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Report Year <span class="text-danger">*</span></label>
                            <input type="number" name="report_year" id="edit_report_year" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Report Month</label>
                            <select name="report_month" id="edit_report_month" class="form-select">
                                <option value="">Annual Aggregate (Full Year)</option>
                                <?php foreach ($months_map as $num => $name): ?>
                                    <option value="<?= $num ?>"><?= $name ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <!-- Meat Category -->
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Meat Category <span class="text-danger">*</span></label>
                        <div class="row g-2">
                            <div class="col-3">
                                <input type="radio" class="btn-check" name="meat_type" id="edit_type_beef" value="Beef" autocomplete="off">
                                <label class="btn btn-outline-danger w-100 fw-bold py-2" for="edit_type_beef">Beef</label>
                            </div>
                            <div class="col-3">
                                <input type="radio" class="btn-check" name="meat_type" id="edit_type_mutton" value="Mutton" autocomplete="off">
                                <label class="btn btn-outline-primary w-100 fw-bold py-2" for="edit_type_mutton">Mutton</label>
                            </div>
                            <div class="col-3">
                                <input type="radio" class="btn-check" name="meat_type" id="edit_type_chicken" value="Chicken" autocomplete="off">
                                <label class="btn btn-outline-warning w-100 fw-bold py-2 text-dark" for="edit_type_chicken">Chicken</label>
                            </div>
                            <div class="col-3">
                                <input type="radio" class="btn-check" name="meat_type" id="edit_type_other" value="Other" autocomplete="off">
                                <label class="btn btn-outline-dark w-100 fw-bold py-2" for="edit_type_other">Others</label>
                            </div>
                        </div>
                    </div>

                    <!-- Custom other meat name input -->
                    <div class="mb-3 d-none" id="edit_other_meat_wrapper">
                        <div class="p-3 bg-white border border-primary rounded shadow-sm">
                            <label class="form-label small fw-bold text-dark mb-1">
                                <i class="bi bi-pencil-square text-primary me-1"></i>Specify Secondary Livestock Name <span class="text-danger">*</span>
                            </label>
                            <input type="text" name="other_meat_name" id="edit_other_meat_name" class="form-control" placeholder="e.g. Pig, Rabbit, Quail, Turkey">
                            <small class="text-muted">Explicit livestock category name.</small>
                        </div>
                    </div>

                    <!-- Value per Kilo and Total Sales Amount -->
                    <div class="card bg-light border-0 p-3 mb-3 rounded">
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label small fw-bold">Sales Volume (kg) <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <input type="number" step="0.01" name="sales_volume_kg" id="edit_sales_volume" class="form-control" required min="0">
                                    <span class="input-group-text bg-white">kg</span>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small fw-bold">Value per Kilo (Rs) <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text bg-white">Rs.</span>
                                    <input type="number" step="0.01" name="value_per_kilo" id="edit_value_per_kilo" class="form-control" required min="0">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small fw-bold text-primary">Total Sales Amount (Rs) <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text bg-white text-primary fw-bold">Rs.</span>
                                    <input type="number" step="0.01" name="total_sales_amount" id="edit_total_sales_amount" class="form-control fw-bold border-primary" required min="0">
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Outlet / Market Address</label>
                            <input type="text" name="outlet_name_address" id="edit_outlet_name_address" class="form-control" list="meat_outlets_list">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Contact Telephone</label>
                            <input type="text" name="contact_no" id="edit_contact_no" class="form-control">
                        </div>
                    </div>

                    <div class="mb-0">
                        <label class="form-label small fw-bold">Remarks</label>
                        <textarea name="remarks" id="edit_remarks" class="form-control" rows="2"></textarea>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary fw-bold"><i class="bi bi-check-circle-fill me-1"></i>Update Record</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- DELETE MODAL -->
<div class="modal fade" id="deleteMeatModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content border-0 shadow">
            <div class="modal-body text-center p-4">
                <i class="bi bi-exclamation-triangle-fill text-danger fs-1 mb-2 d-block"></i>
                <h5 class="fw-bold mb-2">Delete Meat Record?</h5>
                <p class="text-muted small mb-3" id="deleteMeatDesc"></p>
                <form method="POST" action="meat_sales.php<?= $requested_range_id ? '?range_id=' . $requested_range_id : ($range_id ? '?range_id=' . $range_id : '') ?>">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="id" id="deleteMeatId">
                    <div class="d-flex gap-2 justify-content-center">
                        <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-sm btn-danger fw-bold">Yes, Delete</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php
ob_start();
?>
<script>
$(document).ready(function() {
    // Dynamic calculation helper for Add modal
    function calculateAddTotal() {
        const vol = parseFloat($("#add_sales_volume").val()) || 0;
        const val = parseFloat($("#add_value_per_kilo").val()) || 0;
        if (vol > 0 && val > 0) {
            $("#add_total_sales_amount").val((vol * val).toFixed(2));
        }
    }
    $("#add_sales_volume, #add_value_per_kilo").on("input", calculateAddTotal);

    // Toggle Others input in Add modal
    $('#addMeatForm input[name="meat_type"]').on("change", function() {
        if ($("#add_type_other").is(":checked")) {
            $("#add_other_meat_wrapper").removeClass("d-none").hide().slideDown(200);
            $("#add_other_meat_name").prop("required", true).focus();
        } else {
            $("#add_other_meat_wrapper").slideUp(150, function() {
                $(this).addClass("d-none");
            });
            $("#add_other_meat_name").prop("required", false).val("");
        }
    });

    // Toggle Others input in Edit modal
    $('#editMeatForm input[name="meat_type"]').on("change", function() {
        if ($("#edit_type_other").is(":checked")) {
            $("#edit_other_meat_wrapper").removeClass("d-none").hide().slideDown(200);
            $("#edit_other_meat_name").prop("required", true).focus();
        } else {
            $("#edit_other_meat_wrapper").slideUp(150, function() {
                $(this).addClass("d-none");
            });
            $("#edit_other_meat_name").prop("required", false);
        }
    });

    // Dynamic calculation helper for Edit modal
    function calculateEditTotal() {
        const vol = parseFloat($("#edit_sales_volume").val()) || 0;
        const val = parseFloat($("#edit_value_per_kilo").val()) || 0;
        if (vol > 0 && val > 0) {
            $("#edit_total_sales_amount").val((vol * val).toFixed(2));
        }
    }
    $("#edit_sales_volume, #edit_value_per_kilo").on("input", calculateEditTotal);

    // Populate Edit Modal
    $(".btn-edit-meat").on("click", function() {
        const btn = $(this);
        $("#edit_meat_id").val(btn.data("id"));
        $("#edit_report_year").val(btn.data("year"));
        $("#edit_report_month").val(btn.data("month"));
        
        const type = btn.data("type");
        $(`#editMeatForm input[name="meat_type"][value="${type}"]`).prop("checked", true);
        
        if (type === "Other") {
            $("#edit_other_meat_wrapper").removeClass("d-none").show();
            $("#edit_other_meat_name").val(btn.data("othertype")).prop("required", true);
        } else {
            $("#edit_other_meat_wrapper").addClass("d-none").hide();
            $("#edit_other_meat_name").val("").prop("required", false);
        }

        $("#edit_sales_volume").val(btn.data("volume"));
        $("#edit_value_per_kilo").val(btn.data("value"));
        $("#edit_total_sales_amount").val(btn.data("total"));
        $("#edit_outlet_name_address").val(btn.data("outlet"));
        $("#edit_contact_no").val(btn.data("contact"));
        $("#edit_remarks").val(btn.data("remarks"));

        $("#editMeatModal").modal("show");
    });

    // Delete trigger
    $(".btn-delete-meat").on("click", function() {
        const btn = $(this);
        $("#deleteMeatId").val(btn.data("id"));
        $("#deleteMeatDesc").text("Are you sure you want to delete record: " + btn.data("desc") + "?");
        $("#deleteMeatModal").modal("show");
    });
});
</script>
<?php
$extra_scripts = ob_get_clean();
include '../../../includes/footer.php';
?>
