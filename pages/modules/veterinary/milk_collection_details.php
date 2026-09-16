<?php
session_start();
require_once '../../../config/db_connect.php';

if (!isset($_SESSION['logged_in']) || !in_array($_SESSION['role'], ['veterinary_surgeon', 'sms', 'livestock_officer', 'provincial_director', 'district_director'])) {
    header("Location: ../../../index.php");
    exit();
}

$user_id = $_SESSION['user_id'] ?? null;
$range_id = $_SESSION['range_id'] ?? null;

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

// Active Tab
$active_tab = $_GET['tab'] ?? 'collecting';
if (!in_array($active_tab, ['collecting', 'processing', 'sales'])) {
    $active_tab = 'collecting';
}

// Global Month, Year, and Date Filters
$selected_year = isset($_GET['year']) ? ($_GET['year'] === 'all' ? 'all' : intval($_GET['year'])) : intval(date('Y'));
$selected_month = isset($_GET['month']) ? ($_GET['month'] === 'all' ? 'all' : intval($_GET['month'])) : 'all';
$selected_date = isset($_GET['date']) && !empty($_GET['date']) ? trim($_GET['date']) : '';

$months_map = [
    1 => 'January', 2 => 'February', 3 => 'March', 4 => 'April',
    5 => 'May', 6 => 'June', 7 => 'July', 8 => 'August',
    9 => 'September', 10 => 'October', 11 => 'November', 12 => 'December'
];

$income_ranges = [
    '< Rs. 25,000',
    'Rs. 25,000 - 50,000',
    'Rs. 50,000 - 100,000',
    'Rs. 100,000 - 250,000',
    'Rs. 250,000 - 500,000',
    'Rs. 500,000 - 1,000,000',
    '> Rs. 1,000,000'
];

// Handle POST Submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $sub_module = $_POST['sub_module'] ?? '';
    $action = $_POST['action'] ?? '';
    $record_date = !empty($_POST['record_date']) ? trim($_POST['record_date']) : date('Y-m-d');
    $r_year = !empty($_POST['report_year']) ? intval($_POST['report_year']) : intval(date('Y', strtotime($record_date)));
    $r_month = (!empty($_POST['report_month']) && $_POST['report_month'] !== 'all') ? intval($_POST['report_month']) : intval(date('n', strtotime($record_date)));

    if ($sub_module === 'collecting') {
        if ($action === 'add') {
            $vs_range = trim($_POST['vs_range'] ?: $range_name);
            $name = trim($_POST['collecting_center_name'] ?? '');
            $address = trim($_POST['address'] ?? '');
            $contact = trim($_POST['contact_no'] ?? '');
            $cow_milk = floatval($_POST['cow_milk_lit_month'] ?? 0);
            $buffalo_milk = floatval($_POST['buffalo_milk_lit_month'] ?? 0);
            $goat_milk = floatval($_POST['goat_milk_lit_month'] ?? 0);
            $total_milk = $cow_milk + $buffalo_milk + $goat_milk;
            $chilling_cap = floatval($_POST['milk_chilling_capacity'] ?? 0);
            $supply_to = trim($_POST['milk_supply_to'] ?? '');

            $stmt = $mysqli->prepare("
                INSERT INTO milk_collecting_centers 
                (vs_range, district_id, range_id, report_year, report_month, record_date, collecting_center_name, address, contact_no, 
                 cow_milk_lit_month, buffalo_milk_lit_month, goat_milk_lit_month, milk_collection_lit_per_month, milk_chilling_capacity, milk_supply_to)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            if ($stmt) {
                $stmt->bind_param("siiiissssddddds", $vs_range, $district_id, $range_id, $r_year, $r_month, $record_date, $name, $address, $contact, $cow_milk, $buffalo_milk, $goat_milk, $total_milk, $chilling_cap, $supply_to);
                $stmt->execute();
                $stmt->close();
                header("Location: milk_collection_details.php?tab=collecting&year=$r_year&month=" . ($r_month ?? 'all') . "&status=success&msg=" . urlencode("Collecting center added successfully."));
            } else {
                header("Location: milk_collection_details.php?tab=collecting&status=error&msg=" . urlencode("Insert failed: " . $mysqli->error));
            }
            exit();
        } elseif ($action === 'edit') {
            $id = intval($_POST['id'] ?? 0);
            $record_date = !empty($_POST['record_date']) ? trim($_POST['record_date']) : null;
            $r_year = !empty($_POST['report_year']) ? intval($_POST['report_year']) : ($record_date ? intval(date('Y', strtotime($record_date))) : intval(date('Y')));
            $r_month = (!empty($_POST['report_month']) && $_POST['report_month'] !== 'all') ? intval($_POST['report_month']) : ($record_date ? intval(date('n', strtotime($record_date))) : null);
            $vs_range = trim($_POST['vs_range'] ?: $range_name);
            $name = trim($_POST['collecting_center_name'] ?? '');
            $address = trim($_POST['address'] ?? '');
            $contact = trim($_POST['contact_no'] ?? '');
            $cow_milk = floatval($_POST['cow_milk_lit_month'] ?? 0);
            $buffalo_milk = floatval($_POST['buffalo_milk_lit_month'] ?? 0);
            $goat_milk = floatval($_POST['goat_milk_lit_month'] ?? 0);
            $total_milk = $cow_milk + $buffalo_milk + $goat_milk;
            $chilling_cap = floatval($_POST['milk_chilling_capacity'] ?? 0);
            $supply_to = trim($_POST['milk_supply_to'] ?? '');

            $stmt = $mysqli->prepare("
                UPDATE milk_collecting_centers 
                SET vs_range = ?, report_year = ?, report_month = ?, record_date = ?, collecting_center_name = ?, address = ?, contact_no = ?, 
                    cow_milk_lit_month = ?, buffalo_milk_lit_month = ?, goat_milk_lit_month = ?, milk_collection_lit_per_month = ?, 
                    milk_chilling_capacity = ?, milk_supply_to = ?
                WHERE id = ?
            ");
            if ($stmt) {
                $stmt->bind_param("siissssdddddsi", $vs_range, $r_year, $r_month, $record_date, $name, $address, $contact, $cow_milk, $buffalo_milk, $goat_milk, $total_milk, $chilling_cap, $supply_to, $id);
                $stmt->execute();
                $stmt->close();
                header("Location: milk_collection_details.php?tab=collecting&year=$r_year&month=" . ($r_month ?? 'all') . "&status=success&msg=" . urlencode("Collecting center updated successfully."));
            } else {
                header("Location: milk_collection_details.php?tab=collecting&status=error&msg=" . urlencode("Update failed."));
            }
            exit();
        } elseif ($action === 'delete') {
            $id = intval($_POST['id'] ?? 0);
            $stmt = $mysqli->prepare("DELETE FROM milk_collecting_centers WHERE id = ?");
            if ($stmt) {
                $stmt->bind_param("i", $id);
                $stmt->execute();
                $stmt->close();
                header("Location: milk_collection_details.php?tab=collecting&status=success&msg=" . urlencode("Collecting center deleted."));
            }
            exit();
        }
    } elseif ($sub_module === 'processing') {
        if ($action === 'add') {
            $vs_range = trim($_POST['vs_range'] ?: $range_name);
            $name = trim($_POST['processing_center_name'] ?? '');
            $address = trim($_POST['address'] ?? '');
            $contact = trim($_POST['contact_no'] ?? '');
            $cow_milk = floatval($_POST['cow_milk_lit_month'] ?? 0);
            $buffalo_milk = floatval($_POST['buffalo_milk_lit_month'] ?? 0);
            $goat_milk = floatval($_POST['goat_milk_lit_month'] ?? 0);
            $yoghurt = floatval($_POST['yoghurt_lit_per_month'] ?? 0);
            $curd = floatval($_POST['curd_lit_per_month'] ?? 0);
            $ice_cream = floatval($_POST['ice_cream_lit_per_month'] ?? 0);
            $ghee = floatval($_POST['ghee_lit_per_month'] ?? 0);
            $other_product = floatval($_POST['other_milk_product_lit_per_month'] ?? 0);
            $total_lit = $cow_milk + $buffalo_milk + $goat_milk;
            if ($total_lit == 0) {
                $total_lit = $yoghurt + $curd + $ice_cream + $ghee + $other_product;
            }
            $income_range = trim($_POST['income_range'] ?? '');

            $stmt = $mysqli->prepare("
                INSERT INTO milk_processing_centers 
                (vs_range, district_id, range_id, report_year, report_month, record_date, processing_center_name, cow_milk_lit_month, buffalo_milk_lit_month, goat_milk_lit_month, address, contact_no, 
                 yoghurt_lit_per_month, curd_lit_per_month, ice_cream_lit_per_month, ghee_lit_per_month, other_milk_product_lit_per_month, total_lit_per_month, income_range)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            if ($stmt) {
                $stmt->bind_param("siiiissdddssdddddds", $vs_range, $district_id, $range_id, $r_year, $r_month, $record_date, $name, $cow_milk, $buffalo_milk, $goat_milk, $address, $contact, 
                                  $yoghurt, $curd, $ice_cream, $ghee, $other_product, $total_lit, $income_range);
                $stmt->execute();
                $stmt->close();
                header("Location: milk_collection_details.php?tab=processing&year=$r_year&month=" . ($r_month ?? 'all') . "&status=success&msg=" . urlencode("Processing center added successfully."));
            } else {
                header("Location: milk_collection_details.php?tab=processing&status=error&msg=" . urlencode("Insert failed: " . $mysqli->error));
            }
            exit();
        } elseif ($action === 'edit') {
            $id = intval($_POST['id'] ?? 0);
            $record_date = !empty($_POST['record_date']) ? trim($_POST['record_date']) : null;
            $r_year = !empty($_POST['report_year']) ? intval($_POST['report_year']) : ($record_date ? intval(date('Y', strtotime($record_date))) : intval(date('Y')));
            $r_month = (!empty($_POST['report_month']) && $_POST['report_month'] !== 'all') ? intval($_POST['report_month']) : ($record_date ? intval(date('n', strtotime($record_date))) : null);
            $vs_range = trim($_POST['vs_range'] ?: $range_name);
            $name = trim($_POST['processing_center_name'] ?? '');
            $address = trim($_POST['address'] ?? '');
            $contact = trim($_POST['contact_no'] ?? '');
            $cow_milk = floatval($_POST['cow_milk_lit_month'] ?? 0);
            $buffalo_milk = floatval($_POST['buffalo_milk_lit_month'] ?? 0);
            $goat_milk = floatval($_POST['goat_milk_lit_month'] ?? 0);
            $yoghurt = floatval($_POST['yoghurt_lit_per_month'] ?? 0);
            $curd = floatval($_POST['curd_lit_per_month'] ?? 0);
            $ice_cream = floatval($_POST['ice_cream_lit_per_month'] ?? 0);
            $ghee = floatval($_POST['ghee_lit_per_month'] ?? 0);
            $other_product = floatval($_POST['other_milk_product_lit_per_month'] ?? 0);
            $total_lit = $cow_milk + $buffalo_milk + $goat_milk;
            if ($total_lit == 0) {
                $total_lit = $yoghurt + $curd + $ice_cream + $ghee + $other_product;
            }
            $income_range = trim($_POST['income_range'] ?? '');

            $stmt = $mysqli->prepare("
                UPDATE milk_processing_centers 
                SET vs_range = ?, report_year = ?, report_month = ?, record_date = ?, processing_center_name = ?, cow_milk_lit_month = ?, buffalo_milk_lit_month = ?, goat_milk_lit_month = ?, address = ?, contact_no = ?, 
                    yoghurt_lit_per_month = ?, curd_lit_per_month = ?, ice_cream_lit_per_month = ?, ghee_lit_per_month = ?, other_milk_product_lit_per_month = ?, total_lit_per_month = ?, income_range = ?
                WHERE id = ?
            ");
            if ($stmt) {
                $stmt->bind_param("siisssdddssddddddsi", $vs_range, $r_year, $r_month, $record_date, $name, $cow_milk, $buffalo_milk, $goat_milk, $address, $contact, 
                                  $yoghurt, $curd, $ice_cream, $ghee, $other_product, $total_lit, $income_range, $id);
                $stmt->execute();
                $stmt->close();
                header("Location: milk_collection_details.php?tab=processing&year=$r_year&month=" . ($r_month ?? 'all') . "&status=success&msg=" . urlencode("Processing center updated successfully."));
            } else {
                header("Location: milk_collection_details.php?tab=processing&status=error&msg=" . urlencode("Update failed."));
            }
            exit();
        } elseif ($action === 'delete') {
            $id = intval($_POST['id'] ?? 0);
            $stmt = $mysqli->prepare("DELETE FROM milk_processing_centers WHERE id = ?");
            if ($stmt) {
                $stmt->bind_param("i", $id);
                $stmt->execute();
                $stmt->close();
                header("Location: milk_collection_details.php?tab=processing&status=success&msg=" . urlencode("Processing center deleted."));
            }
            exit();
        }
    } elseif ($sub_module === 'sales') {
        if ($action === 'add') {
            $vs_range = trim($_POST['vs_range'] ?: $range_name);
            $name = trim($_POST['sales_center_name'] ?? '');
            $address = trim($_POST['address'] ?? '');
            $contact = trim($_POST['contact_no'] ?? '');
            $cow_milk = floatval($_POST['cow_milk_lit_month'] ?? 0);
            $buffalo_milk = floatval($_POST['buffalo_milk_lit_month'] ?? 0);
            $goat_milk = floatval($_POST['goat_milk_lit_month'] ?? 0);
            $fresh_milk = floatval($_POST['fresh_milk_lit_per_month'] ?? 0);
            $yoghurt = floatval($_POST['yoghurt_lit_per_month'] ?? 0);
            $curd = floatval($_POST['curd_lit_per_month'] ?? 0);
            $ice_cream = floatval($_POST['ice_cream_lit_per_month'] ?? 0);
            $ghee = floatval($_POST['ghee_lit_per_month'] ?? 0);
            $other_product = floatval($_POST['other_milk_product_lit_per_month'] ?? 0);
            $total_lit = $cow_milk + $buffalo_milk + $goat_milk;
            if ($total_lit == 0) {
                $total_lit = $fresh_milk + $yoghurt + $curd + $ice_cream + $ghee + $other_product;
            }
            $income_range = trim($_POST['income_range'] ?? '');

            $stmt = $mysqli->prepare("
                INSERT INTO milk_product_sales_centers 
                (vs_range, district_id, range_id, report_year, report_month, record_date, sales_center_name, cow_milk_lit_month, buffalo_milk_lit_month, goat_milk_lit_month, address, contact_no, 
                 fresh_milk_lit_per_month, yoghurt_lit_per_month, curd_lit_per_month, ice_cream_lit_per_month, ghee_lit_per_month, other_milk_product_lit_per_month, total_lit_per_month, income_range)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            if ($stmt) {
                $stmt->bind_param("siiiissdddssddddddds", $vs_range, $district_id, $range_id, $r_year, $r_month, $record_date, $name, $cow_milk, $buffalo_milk, $goat_milk, $address, $contact, 
                                  $fresh_milk, $yoghurt, $curd, $ice_cream, $ghee, $other_product, $total_lit, $income_range);
                $stmt->execute();
                $stmt->close();
                header("Location: milk_collection_details.php?tab=sales&year=$r_year&month=" . ($r_month ?? 'all') . "&status=success&msg=" . urlencode("Sales center added successfully."));
            } else {
                header("Location: milk_collection_details.php?tab=sales&status=error&msg=" . urlencode("Insert failed: " . $mysqli->error));
            }
            exit();
        } elseif ($action === 'edit') {
            $id = intval($_POST['id'] ?? 0);
            $record_date = !empty($_POST['record_date']) ? trim($_POST['record_date']) : null;
            $r_year = !empty($_POST['report_year']) ? intval($_POST['report_year']) : ($record_date ? intval(date('Y', strtotime($record_date))) : intval(date('Y')));
            $r_month = (!empty($_POST['report_month']) && $_POST['report_month'] !== 'all') ? intval($_POST['report_month']) : ($record_date ? intval(date('n', strtotime($record_date))) : null);
            $vs_range = trim($_POST['vs_range'] ?: $range_name);
            $name = trim($_POST['sales_center_name'] ?? '');
            $address = trim($_POST['address'] ?? '');
            $contact = trim($_POST['contact_no'] ?? '');
            $cow_milk = floatval($_POST['cow_milk_lit_month'] ?? 0);
            $buffalo_milk = floatval($_POST['buffalo_milk_lit_month'] ?? 0);
            $goat_milk = floatval($_POST['goat_milk_lit_month'] ?? 0);
            $fresh_milk = floatval($_POST['fresh_milk_lit_per_month'] ?? 0);
            $yoghurt = floatval($_POST['yoghurt_lit_per_month'] ?? 0);
            $curd = floatval($_POST['curd_lit_per_month'] ?? 0);
            $ice_cream = floatval($_POST['ice_cream_lit_per_month'] ?? 0);
            $ghee = floatval($_POST['ghee_lit_per_month'] ?? 0);
            $other_product = floatval($_POST['other_milk_product_lit_per_month'] ?? 0);
            $total_lit = $cow_milk + $buffalo_milk + $goat_milk;
            if ($total_lit == 0) {
                $total_lit = $fresh_milk + $yoghurt + $curd + $ice_cream + $ghee + $other_product;
            }
            $income_range = trim($_POST['income_range'] ?? '');

            $stmt = $mysqli->prepare("
                UPDATE milk_product_sales_centers 
                SET vs_range = ?, report_year = ?, report_month = ?, record_date = ?, sales_center_name = ?, cow_milk_lit_month = ?, buffalo_milk_lit_month = ?, goat_milk_lit_month = ?, address = ?, contact_no = ?, 
                    fresh_milk_lit_per_month = ?, yoghurt_lit_per_month = ?, curd_lit_per_month = ?, ice_cream_lit_per_month = ?, ghee_lit_per_month = ?, other_milk_product_lit_per_month = ?, total_lit_per_month = ?, income_range = ?
                WHERE id = ?
            ");
            if ($stmt) {
                $stmt->bind_param("siisssdddssdddddddsi", $vs_range, $r_year, $r_month, $record_date, $name, $cow_milk, $buffalo_milk, $goat_milk, $address, $contact, 
                                  $fresh_milk, $yoghurt, $curd, $ice_cream, $ghee, $other_product, $total_lit, $income_range, $id);
                $stmt->execute();
                $stmt->close();
                header("Location: milk_collection_details.php?tab=sales&year=$r_year&month=" . ($r_month ?? 'all') . "&status=success&msg=" . urlencode("Sales center updated successfully."));
            } else {
                header("Location: milk_collection_details.php?tab=sales&status=error&msg=" . urlencode("Update failed."));
            }
            exit();
        } elseif ($action === 'delete') {
            $id = intval($_POST['id'] ?? 0);
            $stmt = $mysqli->prepare("DELETE FROM milk_product_sales_centers WHERE id = ?");
            if ($stmt) {
                $stmt->bind_param("i", $id);
                $stmt->execute();
                $stmt->close();
                header("Location: milk_collection_details.php?tab=sales&status=success&msg=" . urlencode("Sales center deleted."));
            }
            exit();
        }
    }
}

// Build Filter SQL helper
function buildFilterQuery($base_sql, $range_id, $range_name, $selected_year, $selected_month, $selected_date = '') {
    $where = ["1=1"];
    $params = [];
    $types = "";

    if (!empty($range_id)) {
        $where[] = "(range_id = ? OR vs_range = ?)";
        $params[] = $range_id;
        $params[] = $range_name;
        $types .= "is";
    }
    if ($selected_year !== 'all') {
        $where[] = "report_year = ?";
        $params[] = $selected_year;
        $types .= "i";
    }
    if ($selected_month !== 'all') {
        $where[] = "report_month = ?";
        $params[] = $selected_month;
        $types .= "i";
    }
    if (!empty($selected_date)) {
        $where[] = "record_date = ?";
        $params[] = $selected_date;
        $types .= "s";
    }
    $where_sql = implode(" AND ", $where);
    return ["sql" => "$base_sql WHERE $where_sql ORDER BY record_date DESC, report_year DESC, id DESC", "params" => $params, "types" => $types];
}

// 1. Fetch Collecting Centers
$q1 = buildFilterQuery("SELECT * FROM milk_collecting_centers", $range_id, $range_name, $selected_year, $selected_month, $selected_date);
$stmt1 = $mysqli->prepare($q1['sql']);
if (!empty($q1['params'])) $stmt1->bind_param($q1['types'], ...$q1['params']);
$stmt1->execute();
$collecting_records = $stmt1->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt1->close();

// 2. Fetch Processing Centers
$q2 = buildFilterQuery("SELECT * FROM milk_processing_centers", $range_id, $range_name, $selected_year, $selected_month, $selected_date);
$stmt2 = $mysqli->prepare($q2['sql']);
if (!empty($q2['params'])) $stmt2->bind_param($q2['types'], ...$q2['params']);
$stmt2->execute();
$processing_records = $stmt2->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt2->close();

// 3. Fetch Sales Centers
$q3 = buildFilterQuery("SELECT * FROM milk_product_sales_centers", $range_id, $range_name, $selected_year, $selected_month, $selected_date);
$stmt3 = $mysqli->prepare($q3['sql']);
if (!empty($q3['params'])) $stmt3->bind_param($q3['types'], ...$q3['params']);
$stmt3->execute();
$sales_records = $stmt3->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt3->close();

// Metrics calculation for Collecting tab
$collecting_totals = ['cow' => 0, 'buffalo' => 0, 'goat' => 0, 'total' => 0];
foreach ($collecting_records as $r) {
    $collecting_totals['cow'] += floatval($r['cow_milk_lit_month']);
    $collecting_totals['buffalo'] += floatval($r['buffalo_milk_lit_month']);
    $collecting_totals['goat'] += floatval($r['goat_milk_lit_month']);
    $collecting_totals['total'] += floatval($r['milk_collection_lit_per_month'] ?: ($r['cow_milk_lit_month'] + $r['buffalo_milk_lit_month'] + $r['goat_milk_lit_month']));
}

$processing_totals = ['cow' => 0, 'buffalo' => 0, 'goat' => 0, 'total' => 0];
foreach ($processing_records as $r) {
    $processing_totals['cow'] += floatval($r['cow_milk_lit_month']);
    $processing_totals['buffalo'] += floatval($r['buffalo_milk_lit_month']);
    $processing_totals['goat'] += floatval($r['goat_milk_lit_month']);
    $processing_totals['total'] += floatval($r['total_lit_per_month']);
}

$sales_totals = ['cow' => 0, 'buffalo' => 0, 'goat' => 0, 'total' => 0];
foreach ($sales_records as $r) {
    $sales_totals['cow'] += floatval($r['cow_milk_lit_month']);
    $sales_totals['buffalo'] += floatval($r['buffalo_milk_lit_month']);
    $sales_totals['goat'] += floatval($r['goat_milk_lit_month']);
    $sales_totals['total'] += floatval($r['total_lit_per_month']);
}

include '../../../includes/header.php';
?>

<div class="container-fluid px-4 py-3">
    <!-- Breadcrumb -->
    <nav aria-label="breadcrumb" class="mb-3">
        <ol class="breadcrumb mb-0 py-2 px-3 bg-white rounded shadow-sm">
            <li class="breadcrumb-item"><a href="../../../dashboard.php" class="text-decoration-none">Dashboard</a></li>
            <li class="breadcrumb-item"><a href="range_statistics.php?range_id=<?= $range_id ?? 1 ?>" class="text-decoration-none">Range Statistics</a></li>
            <li class="breadcrumb-item active fw-bold text-dark" aria-current="page">Milk Collection Details</li>
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

    <!-- Title Bar -->
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4 bg-white p-4 rounded shadow-sm border-start border-primary border-4">
        <div>
            <h3 class="fw-bold mb-1" style="color: #370709;">
                <i class="bi bi-bucket-fill text-primary me-2"></i>Milk Collection Details
                <span class="badge bg-primary-subtle text-primary fs-6 ms-2">Unified Management Hub</span>
            </h3>
            <p class="text-muted small mb-0">
                Consolidated registry of Milk Collecting Centers, Processing Facilities, and Sales Outlets subdivided strictly by <strong>Cow</strong>, <strong>Buffalo</strong>, and <strong>Goat</strong> milk.
            </p>
        </div>
        <div class="d-flex gap-2">
            <a href="range_statistics.php?range_id=<?= $range_id ?? 1 ?>" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-1"></i> Back to Hub
            </a>
            <?php if ($active_tab === 'collecting'): ?>
                <button type="button" class="btn btn-primary fw-bold shadow-sm" data-bs-toggle="modal" data-bs-target="#addCollectingModal">
                    <i class="bi bi-plus-circle me-1"></i> Add Collecting Center
                </button>
            <?php elseif ($active_tab === 'processing'): ?>
                <button type="button" class="btn btn-primary fw-bold shadow-sm" data-bs-toggle="modal" data-bs-target="#addProcessingModal">
                    <i class="bi bi-plus-circle me-1"></i> Add Processing Center
                </button>
            <?php else: ?>
                <button type="button" class="btn btn-primary fw-bold shadow-sm" data-bs-toggle="modal" data-bs-target="#addSalesModal">
                    <i class="bi bi-plus-circle me-1"></i> Add Sales Center
                </button>
            <?php endif; ?>
        </div>
    </div>

    <!-- GLOBAL REPORTING FILTERS (Month, Year, & Date) -->
    <div class="card shadow-sm border-0 mb-4">
        <div class="card-body bg-light p-3 rounded">
            <form method="GET" class="row g-3 align-items-end" id="filterForm">
                <input type="hidden" name="tab" value="<?= htmlspecialchars($active_tab) ?>">
                <div class="col-md-3">
                    <label class="form-label small fw-bold text-secondary mb-1"><i class="bi bi-calendar-check me-1"></i>Report Year</label>
                    <select name="year" class="form-select form-select-sm" onchange="this.form.submit()">
                        <option value="all" <?= ($selected_year === 'all') ? 'selected' : '' ?>>All Years</option>
                        <?php for ($yr = date('Y') + 1; $yr >= 2020; $yr--): ?>
                            <option value="<?= $yr ?>" <?= ($selected_year !== 'all' && intval($selected_year) === $yr) ? 'selected' : '' ?>><?= $yr ?></option>
                        <?php endfor; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label small fw-bold text-secondary mb-1"><i class="bi bi-calendar-month me-1"></i>Report Month</label>
                    <select name="month" class="form-select form-select-sm" onchange="this.form.submit()">
                        <option value="all" <?= ($selected_month === 'all') ? 'selected' : '' ?>>All Months (Annual Aggregate)</option>
                        <?php foreach ($months_map as $m_num => $m_name): ?>
                            <option value="<?= $m_num ?>" <?= ($selected_month !== 'all' && intval($selected_month) === $m_num) ? 'selected' : '' ?>><?= $m_name ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label small fw-bold text-secondary mb-1"><i class="bi bi-calendar-date me-1"></i>Specific Date</label>
                    <input type="date" name="date" class="form-control form-control-sm" value="<?= htmlspecialchars($selected_date) ?>" onchange="this.form.submit()">
                </div>
                <div class="col-md-3 d-flex gap-2">
                    <button type="submit" class="btn btn-sm btn-dark flex-grow-1"><i class="bi bi-funnel-fill me-1"></i>Apply Filters</button>
                    <a href="milk_collection_details.php?tab=<?= $active_tab ?>" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-counterclockwise"></i> Reset</a>
                </div>
            </form>
        </div>
    </div>

    <!-- NAVIGATION TABS -->
    <ul class="nav nav-pills mb-4 gap-2 bg-white p-2 rounded shadow-sm">
        <li class="nav-item">
            <a class="nav-link fw-bold px-4 py-2 <?= $active_tab === 'collecting' ? 'active shadow-sm' : 'text-dark' ?>" 
               href="milk_collection_details.php?tab=collecting&year=<?= $selected_year ?>&month=<?= $selected_month ?>&date=<?= urlencode($selected_date) ?>">
                <i class="bi bi-bucket-fill me-2"></i>Milk Collecting Centers
                <span class="badge <?= $active_tab === 'collecting' ? 'bg-white text-primary' : 'bg-primary text-white' ?> ms-2"><?= count($collecting_records) ?></span>
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link fw-bold px-4 py-2 <?= $active_tab === 'processing' ? 'active shadow-sm' : 'text-dark' ?>" 
               href="milk_collection_details.php?tab=processing&year=<?= $selected_year ?>&month=<?= $selected_month ?>&date=<?= urlencode($selected_date) ?>">
                <i class="bi bi-gear-wide-connected me-2"></i>Milk Processing Centers
                <span class="badge <?= $active_tab === 'processing' ? 'bg-white text-primary' : 'bg-primary text-white' ?> ms-2"><?= count($processing_records) ?></span>
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link fw-bold px-4 py-2 <?= $active_tab === 'sales' ? 'active shadow-sm' : 'text-dark' ?>" 
               href="milk_collection_details.php?tab=sales&year=<?= $selected_year ?>&month=<?= $selected_month ?>&date=<?= urlencode($selected_date) ?>">
                <i class="bi bi-shop me-2"></i>Milk Sales Centers
                <span class="badge <?= $active_tab === 'sales' ? 'bg-white text-primary' : 'bg-primary text-white' ?> ms-2"><?= count($sales_records) ?></span>
            </a>
        </li>
    </ul>

    <!-- TAB 1: MILK COLLECTING CENTERS -->
    <?php if ($active_tab === 'collecting'): ?>
        <!-- KPI Cards: Subdivided by Cow, Buffalo, Goat -->
        <div class="row g-3 mb-4">
            <div class="col-sm-6 col-xl-3">
                <div class="card border-0 shadow-sm rounded-3 border-start border-primary border-4 h-100">
                    <div class="card-body p-3">
                        <span class="text-muted small text-uppercase fw-bold">Cow Milk Collected</span>
                        <h3 class="fw-bold text-primary mb-0 mt-1"><?= number_format($collecting_totals['cow'], 2) ?> <small class="fs-6 text-muted">L/mo</small></h3>
                        <span class="badge bg-primary-subtle text-primary mt-2"><i class="bi bi-shield-shaded me-1"></i>Bovine / Cow</span>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-xl-3">
                <div class="card border-0 shadow-sm rounded-3 border-start border-info border-4 h-100">
                    <div class="card-body p-3">
                        <span class="text-muted small text-uppercase fw-bold">Buffalo Milk Collected</span>
                        <h3 class="fw-bold text-info mb-0 mt-1"><?= number_format($collecting_totals['buffalo'], 2) ?> <small class="fs-6 text-muted">L/mo</small></h3>
                        <span class="badge bg-info-subtle text-info mt-2"><i class="bi bi-shield-shaded me-1"></i>Bubaline / Buffalo</span>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-xl-3">
                <div class="card border-0 shadow-sm rounded-3 border-start border-warning border-4 h-100">
                    <div class="card-body p-3">
                        <span class="text-muted small text-uppercase fw-bold">Goat Milk Collected</span>
                        <h3 class="fw-bold text-warning mb-0 mt-1"><?= number_format($collecting_totals['goat'], 2) ?> <small class="fs-6 text-muted">L/mo</small></h3>
                        <span class="badge bg-warning-subtle text-dark mt-2"><i class="bi bi-shield-shaded me-1"></i>Caprine / Goat</span>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-xl-3">
                <div class="card border-0 shadow-sm rounded-3 border-start border-success border-4 h-100">
                    <div class="card-body p-3">
                        <span class="text-muted small text-uppercase fw-bold">Total Milk Volume</span>
                        <h3 class="fw-bold text-success mb-0 mt-1"><?= number_format($collecting_totals['total'], 2) ?> <small class="fs-6 text-muted">L/mo</small></h3>
                        <span class="badge bg-success-subtle text-success mt-2"><i class="bi bi-check2-all me-1"></i>All Species Combined</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Table: Collecting Centers -->
        <div class="card shadow-sm border-0">
            <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0 fw-bold text-dark">
                    <i class="bi bi-bucket-fill me-2 text-primary"></i>Milk Collecting Centers List
                </h5>
                <div class="small text-muted">
                    Period: <strong><?= (!empty($selected_date) ? 'Date: ' . date('d M Y', strtotime($selected_date)) . ' | ' : '') . ($selected_month !== 'all' ? $months_map[$selected_month] . ' ' : '') . ($selected_year === 'all' ? 'All Years' : $selected_year) ?></strong>
                </div>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover table-striped align-middle mb-0">
                        <thead class="table-light text-secondary small text-uppercase">
                            <tr>
                                <th class="ps-3">#</th>
                                <th>Date</th>
                                <th>Period</th>
                                <th>Center Name & Address</th>
                                <th>Contact</th>
                                <th class="text-end text-primary">Cow Milk (L/m)</th>
                                <th class="text-end text-info">Buffalo Milk (L/m)</th>
                                <th class="text-end text-warning">Goat Milk (L/m)</th>
                                <th class="text-end fw-bold">Total Collection (L/m)</th>
                                <th class="text-end">Chilling Cap. (L)</th>
                                <th>Supply To</th>
                                <th class="text-center pe-3">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($collecting_records)): ?>
                                <tr>
                                    <td colspan="12" class="text-center py-5 text-muted">No milk collecting centers found.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($collecting_records as $idx => $r): ?>
                                    <tr>
                                        <td class="ps-3 fw-bold text-muted"><?= $idx + 1 ?></td>
                                        <td>
                                            <?php if (!empty($r['record_date'])): ?>
                                                <span class="fw-semibold text-dark"><i class="bi bi-calendar3 me-1 text-secondary"></i><?= date('d M Y', strtotime($r['record_date'])) ?></span>
                                            <?php else: ?>
                                                <span class="text-muted">—</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <span class="fw-bold"><?= $r['report_year'] ?? 2025 ?></span>
                                            <span class="badge bg-light text-dark border ms-1"><?= !empty($r['report_month']) && isset($months_map[$r['report_month']]) ? substr($months_map[$r['report_month']], 0, 3) : 'Annual' ?></span>
                                        </td>
                                        <td>
                                            <div class="fw-bold text-dark"><?= htmlspecialchars($r['collecting_center_name']) ?></div>
                                            <small class="text-muted"><?= htmlspecialchars($r['address']) ?></small>
                                        </td>
                                        <td class="small text-muted"><?= htmlspecialchars($r['contact_no'] ?: '—') ?></td>
                                        <td class="text-end font-monospace text-primary fw-bold"><?= number_format($r['cow_milk_lit_month'], 2) ?></td>
                                        <td class="text-end font-monospace text-info fw-bold"><?= number_format($r['buffalo_milk_lit_month'], 2) ?></td>
                                        <td class="text-end font-monospace text-warning fw-bold"><?= number_format($r['goat_milk_lit_month'], 2) ?></td>
                                        <td class="text-end font-monospace fw-bold text-success"><?= number_format($r['milk_collection_lit_per_month'] ?: ($r['cow_milk_lit_month'] + $r['buffalo_milk_lit_month'] + $r['goat_milk_lit_month']), 2) ?></td>
                                        <td class="text-end font-monospace"><?= number_format($r['milk_chilling_capacity'], 2) ?></td>
                                        <td><span class="badge bg-secondary"><?= htmlspecialchars($r['milk_supply_to'] ?: 'Direct Distribution') ?></span></td>
                                        <td class="text-center pe-3">
                                            <div class="btn-group btn-group-sm">
                                                <button type="button" class="btn btn-outline-primary btn-edit-collecting"
                                                    data-id="<?= $r['id'] ?>"
                                                    data-date="<?= htmlspecialchars($r['record_date'] ?? '') ?>"
                                                    data-range="<?= htmlspecialchars($r['vs_range']) ?>"
                                                    data-year="<?= $r['report_year'] ?? 2025 ?>"
                                                    data-month="<?= $r['report_month'] ?? '' ?>"
                                                    data-name="<?= htmlspecialchars($r['collecting_center_name']) ?>"
                                                    data-address="<?= htmlspecialchars($r['address']) ?>"
                                                    data-contact="<?= htmlspecialchars($r['contact_no']) ?>"
                                                    data-cow="<?= $r['cow_milk_lit_month'] ?>"
                                                    data-buffalo="<?= $r['buffalo_milk_lit_month'] ?>"
                                                    data-goat="<?= $r['goat_milk_lit_month'] ?>"
                                                    data-chilling="<?= $r['milk_chilling_capacity'] ?>"
                                                    data-supply="<?= htmlspecialchars($r['milk_supply_to']) ?>">
                                                    <i class="bi bi-pencil-fill"></i>
                                                </button>
                                                <button type="button" class="btn btn-outline-danger btn-delete-entry"
                                                    data-module="collecting"
                                                    data-id="<?= $r['id'] ?>"
                                                    data-title="<?= htmlspecialchars($r['collecting_center_name']) ?>">
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
    <?php endif; ?>

    <!-- TAB 2: MILK PROCESSING CENTERS -->
    <?php if ($active_tab === 'processing'): ?>
        <!-- KPI Cards: Subdivided by Cow, Buffalo, Goat -->
        <div class="row g-3 mb-4">
            <div class="col-sm-6 col-xl-3">
                <div class="card border-0 shadow-sm rounded-3 border-start border-primary border-4 h-100">
                    <div class="card-body p-3">
                        <span class="text-muted small text-uppercase fw-bold">Cow Milk Intake</span>
                        <h3 class="fw-bold text-primary mb-0 mt-1"><?= number_format($processing_totals['cow'], 2) ?> <small class="fs-6 text-muted">L/mo</small></h3>
                        <span class="badge bg-primary-subtle text-primary mt-2">Cow Milk Processed</span>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-xl-3">
                <div class="card border-0 shadow-sm rounded-3 border-start border-info border-4 h-100">
                    <div class="card-body p-3">
                        <span class="text-muted small text-uppercase fw-bold">Buffalo Milk Intake</span>
                        <h3 class="fw-bold text-info mb-0 mt-1"><?= number_format($processing_totals['buffalo'], 2) ?> <small class="fs-6 text-muted">L/mo</small></h3>
                        <span class="badge bg-info-subtle text-info mt-2">Buffalo Milk Processed</span>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-xl-3">
                <div class="card border-0 shadow-sm rounded-3 border-start border-warning border-4 h-100">
                    <div class="card-body p-3">
                        <span class="text-muted small text-uppercase fw-bold">Goat Milk Intake</span>
                        <h3 class="fw-bold text-warning mb-0 mt-1"><?= number_format($processing_totals['goat'], 2) ?> <small class="fs-6 text-muted">L/mo</small></h3>
                        <span class="badge bg-warning-subtle text-dark mt-2">Goat Milk Processed</span>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-xl-3">
                <div class="card border-0 shadow-sm rounded-3 border-start border-success border-4 h-100">
                    <div class="card-body p-3">
                        <span class="text-muted small text-uppercase fw-bold">Total Products / Intake</span>
                        <h3 class="fw-bold text-success mb-0 mt-1"><?= number_format($processing_totals['total'], 2) ?> <small class="fs-6 text-muted">L/mo</small></h3>
                        <span class="badge bg-success-subtle text-success mt-2">Total Processing Volume</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Table: Processing Centers -->
        <div class="card shadow-sm border-0">
            <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0 fw-bold text-dark">
                    <i class="bi bi-gear-wide-connected me-2 text-primary"></i>Milk Processing Centers List
                </h5>
                <div class="small text-muted">
                    Period: <strong><?= (!empty($selected_date) ? 'Date: ' . date('d M Y', strtotime($selected_date)) . ' | ' : '') . ($selected_month !== 'all' ? $months_map[$selected_month] . ' ' : '') . ($selected_year === 'all' ? 'All Years' : $selected_year) ?></strong>
                </div>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover table-striped align-middle mb-0">
                        <thead class="table-light text-secondary small text-uppercase">
                            <tr>
                                <th class="ps-3">#</th>
                                <th>Date</th>
                                <th>Period</th>
                                <th>Center Name & Address</th>
                                <th class="text-end text-primary">Cow (L/m)</th>
                                <th class="text-end text-info">Buffalo (L/m)</th>
                                <th class="text-end text-warning">Goat (L/m)</th>
                                <th class="text-end">Products (Yoghurt/Curd/IceCream/Ghee)</th>
                                <th class="text-end fw-bold">Total Vol (L/m)</th>
                                <th>Monthly Income Range</th>
                                <th class="text-center pe-3">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($processing_records)): ?>
                                <tr>
                                    <td colspan="11" class="text-center py-5 text-muted">No milk processing centers found.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($processing_records as $idx => $r): ?>
                                    <tr>
                                        <td class="ps-3 fw-bold text-muted"><?= $idx + 1 ?></td>
                                        <td>
                                            <?php if (!empty($r['record_date'])): ?>
                                                <span class="fw-semibold text-dark"><i class="bi bi-calendar3 me-1 text-secondary"></i><?= date('d M Y', strtotime($r['record_date'])) ?></span>
                                            <?php else: ?>
                                                <span class="text-muted">—</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <span class="fw-bold"><?= $r['report_year'] ?? 2025 ?></span>
                                            <span class="badge bg-light text-dark border ms-1"><?= !empty($r['report_month']) && isset($months_map[$r['report_month']]) ? substr($months_map[$r['report_month']], 0, 3) : 'Annual' ?></span>
                                        </td>
                                        <td>
                                            <div class="fw-bold text-dark"><?= htmlspecialchars($r['processing_center_name']) ?></div>
                                            <small class="text-muted"><?= htmlspecialchars($r['address']) ?></small>
                                        </td>
                                        <td class="text-end font-monospace text-primary fw-bold"><?= number_format($r['cow_milk_lit_month'], 2) ?></td>
                                        <td class="text-end font-monospace text-info fw-bold"><?= number_format($r['buffalo_milk_lit_month'], 2) ?></td>
                                        <td class="text-end font-monospace text-warning fw-bold"><?= number_format($r['goat_milk_lit_month'], 2) ?></td>
                                        <td class="text-end small">
                                            Yog: <?= number_format($r['yoghurt_lit_per_month']) ?> | Curd: <?= number_format($r['curd_lit_per_month']) ?> | Ghee: <?= number_format($r['ghee_lit_per_month']) ?>
                                        </td>
                                        <td class="text-end font-monospace fw-bold text-success"><?= number_format($r['total_lit_per_month'], 2) ?></td>
                                        <td>
                                            <?php if (!empty($r['income_range'])): ?>
                                                <span class="badge bg-light text-dark border"><i class="bi bi-shield-lock-fill text-secondary me-1"></i><?= htmlspecialchars($r['income_range']) ?></span>
                                            <?php else: ?>
                                                <span class="badge bg-light text-muted border">Undisclosed</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-center pe-3">
                                            <div class="btn-group btn-group-sm">
                                                <button type="button" class="btn btn-outline-primary btn-edit-processing"
                                                    data-id="<?= $r['id'] ?>"
                                                    data-date="<?= htmlspecialchars($r['record_date'] ?? '') ?>"
                                                    data-range="<?= htmlspecialchars($r['vs_range']) ?>"
                                                    data-year="<?= $r['report_year'] ?? 2025 ?>"
                                                    data-month="<?= $r['report_month'] ?? '' ?>"
                                                    data-name="<?= htmlspecialchars($r['processing_center_name']) ?>"
                                                    data-address="<?= htmlspecialchars($r['address']) ?>"
                                                    data-contact="<?= htmlspecialchars($r['contact_no']) ?>"
                                                    data-cow="<?= $r['cow_milk_lit_month'] ?>"
                                                    data-buffalo="<?= $r['buffalo_milk_lit_month'] ?>"
                                                    data-goat="<?= $r['goat_milk_lit_month'] ?>"
                                                    data-yoghurt="<?= $r['yoghurt_lit_per_month'] ?>"
                                                    data-curd="<?= $r['curd_lit_per_month'] ?>"
                                                    data-icecream="<?= $r['ice_cream_lit_per_month'] ?>"
                                                    data-ghee="<?= $r['ghee_lit_per_month'] ?>"
                                                    data-other="<?= $r['other_milk_product_lit_per_month'] ?>"
                                                    data-incomerange="<?= htmlspecialchars($r['income_range'] ?? '') ?>">
                                                    <i class="bi bi-pencil-fill"></i>
                                                </button>
                                                <button type="button" class="btn btn-outline-danger btn-delete-entry"
                                                    data-module="processing"
                                                    data-id="<?= $r['id'] ?>"
                                                    data-title="<?= htmlspecialchars($r['processing_center_name']) ?>">
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
    <?php endif; ?>

    <!-- TAB 3: MILK PRODUCT SALES CENTERS -->
    <?php if ($active_tab === 'sales'): ?>
        <!-- KPI Cards: Subdivided by Cow, Buffalo, Goat -->
        <div class="row g-3 mb-4">
            <div class="col-sm-6 col-xl-3">
                <div class="card border-0 shadow-sm rounded-3 border-start border-primary border-4 h-100">
                    <div class="card-body p-3">
                        <span class="text-muted small text-uppercase fw-bold">Cow Milk Sales</span>
                        <h3 class="fw-bold text-primary mb-0 mt-1"><?= number_format($sales_totals['cow'], 2) ?> <small class="fs-6 text-muted">L/mo</small></h3>
                        <span class="badge bg-primary-subtle text-primary mt-2">Cow Milk Dispatched</span>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-xl-3">
                <div class="card border-0 shadow-sm rounded-3 border-start border-info border-4 h-100">
                    <div class="card-body p-3">
                        <span class="text-muted small text-uppercase fw-bold">Buffalo Milk Sales</span>
                        <h3 class="fw-bold text-info mb-0 mt-1"><?= number_format($sales_totals['buffalo'], 2) ?> <small class="fs-6 text-muted">L/mo</small></h3>
                        <span class="badge bg-info-subtle text-info mt-2">Buffalo Milk Dispatched</span>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-xl-3">
                <div class="card border-0 shadow-sm rounded-3 border-start border-warning border-4 h-100">
                    <div class="card-body p-3">
                        <span class="text-muted small text-uppercase fw-bold">Goat Milk Sales</span>
                        <h3 class="fw-bold text-warning mb-0 mt-1"><?= number_format($sales_totals['goat'], 2) ?> <small class="fs-6 text-muted">L/mo</small></h3>
                        <span class="badge bg-warning-subtle text-dark mt-2">Goat Milk Dispatched</span>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-xl-3">
                <div class="card border-0 shadow-sm rounded-3 border-start border-success border-4 h-100">
                    <div class="card-body p-3">
                        <span class="text-muted small text-uppercase fw-bold">Total Sales Volume</span>
                        <h3 class="fw-bold text-success mb-0 mt-1"><?= number_format($sales_totals['total'], 2) ?> <small class="fs-6 text-muted">L/mo</small></h3>
                        <span class="badge bg-success-subtle text-success mt-2">Total Dispatches</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Table: Sales Centers -->
        <div class="card shadow-sm border-0">
            <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0 fw-bold text-dark">
                    <i class="bi bi-shop me-2 text-primary"></i>Milk Product Sales Centers List
                </h5>
                <div class="small text-muted">
                    Period: <strong><?= (!empty($selected_date) ? 'Date: ' . date('d M Y', strtotime($selected_date)) . ' | ' : '') . ($selected_month !== 'all' ? $months_map[$selected_month] . ' ' : '') . ($selected_year === 'all' ? 'All Years' : $selected_year) ?></strong>
                </div>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover table-striped align-middle mb-0">
                        <thead class="table-light text-secondary small text-uppercase">
                            <tr>
                                <th class="ps-3">#</th>
                                <th>Date</th>
                                <th>Period</th>
                                <th>Sales Center & Address</th>
                                <th class="text-end text-primary">Cow (L/m)</th>
                                <th class="text-end text-info">Buffalo (L/m)</th>
                                <th class="text-end text-warning">Goat (L/m)</th>
                                <th class="text-end">Fresh Milk / By-products</th>
                                <th class="text-end fw-bold">Total Volume (L/m)</th>
                                <th>Monthly Income Range</th>
                                <th class="text-center pe-3">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($sales_records)): ?>
                                <tr>
                                    <td colspan="11" class="text-center py-5 text-muted">No milk product sales centers found.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($sales_records as $idx => $r): ?>
                                    <tr>
                                        <td class="ps-3 fw-bold text-muted"><?= $idx + 1 ?></td>
                                        <td>
                                            <?php if (!empty($r['record_date'])): ?>
                                                <span class="fw-semibold text-dark"><i class="bi bi-calendar3 me-1 text-secondary"></i><?= date('d M Y', strtotime($r['record_date'])) ?></span>
                                            <?php else: ?>
                                                <span class="text-muted">—</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <span class="fw-bold"><?= $r['report_year'] ?? 2025 ?></span>
                                            <span class="badge bg-light text-dark border ms-1"><?= !empty($r['report_month']) && isset($months_map[$r['report_month']]) ? substr($months_map[$r['report_month']], 0, 3) : 'Annual' ?></span>
                                        </td>
                                        <td>
                                            <div class="fw-bold text-dark"><?= htmlspecialchars($r['sales_center_name']) ?></div>
                                            <small class="text-muted"><?= htmlspecialchars($r['address']) ?></small>
                                        </td>
                                        <td class="text-end font-monospace text-primary fw-bold"><?= number_format($r['cow_milk_lit_month'], 2) ?></td>
                                        <td class="text-end font-monospace text-info fw-bold"><?= number_format($r['buffalo_milk_lit_month'], 2) ?></td>
                                        <td class="text-end font-monospace text-warning fw-bold"><?= number_format($r['goat_milk_lit_month'], 2) ?></td>
                                        <td class="text-end small">
                                            FM: <?= number_format($r['fresh_milk_lit_per_month']) ?> | Yog: <?= number_format($r['yoghurt_lit_per_month']) ?> | Curd: <?= number_format($r['curd_lit_per_month']) ?>
                                        </td>
                                        <td class="text-end font-monospace fw-bold text-success"><?= number_format($r['total_lit_per_month'], 2) ?></td>
                                        <td>
                                            <?php if (!empty($r['income_range'])): ?>
                                                <span class="badge bg-light text-dark border"><i class="bi bi-shield-lock-fill text-secondary me-1"></i><?= htmlspecialchars($r['income_range']) ?></span>
                                            <?php else: ?>
                                                <span class="badge bg-light text-muted border">Undisclosed</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-center pe-3">
                                            <div class="btn-group btn-group-sm">
                                                <button type="button" class="btn btn-outline-primary btn-edit-sales"
                                                    data-id="<?= $r['id'] ?>"
                                                    data-date="<?= htmlspecialchars($r['record_date'] ?? '') ?>"
                                                    data-range="<?= htmlspecialchars($r['vs_range']) ?>"
                                                    data-year="<?= $r['report_year'] ?? 2025 ?>"
                                                    data-month="<?= $r['report_month'] ?? '' ?>"
                                                    data-name="<?= htmlspecialchars($r['sales_center_name']) ?>"
                                                    data-address="<?= htmlspecialchars($r['address']) ?>"
                                                    data-contact="<?= htmlspecialchars($r['contact_no']) ?>"
                                                    data-cow="<?= $r['cow_milk_lit_month'] ?>"
                                                    data-buffalo="<?= $r['buffalo_milk_lit_month'] ?>"
                                                    data-goat="<?= $r['goat_milk_lit_month'] ?>"
                                                    data-fresh="<?= $r['fresh_milk_lit_per_month'] ?>"
                                                    data-yoghurt="<?= $r['yoghurt_lit_per_month'] ?>"
                                                    data-curd="<?= $r['curd_lit_per_month'] ?>"
                                                    data-icecream="<?= $r['ice_cream_lit_per_month'] ?>"
                                                    data-ghee="<?= $r['ghee_lit_per_month'] ?>"
                                                    data-other="<?= $r['other_milk_product_lit_per_month'] ?>"
                                                    data-incomerange="<?= htmlspecialchars($r['income_range'] ?? '') ?>">
                                                    <i class="bi bi-pencil-fill"></i>
                                                </button>
                                                <button type="button" class="btn btn-outline-danger btn-delete-entry"
                                                    data-module="sales"
                                                    data-id="<?= $r['id'] ?>"
                                                    data-title="<?= htmlspecialchars($r['sales_center_name']) ?>">
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
    <?php endif; ?>

</div>

<!-- MODAL: ADD COLLECTING CENTER -->
<div class="modal fade" id="addCollectingModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title fw-bold"><i class="bi bi-plus-circle me-2"></i>Add Milk Collecting Center</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="milk_collection_details.php?tab=collecting">
                <input type="hidden" name="sub_module" value="collecting">
                <input type="hidden" name="action" value="add">
                <div class="modal-body p-4">
                    <div class="row g-3 mb-3">
                        <div class="col-md-3">
                            <label class="form-label small fw-bold">VS Range</label>
                            <input type="text" name="vs_range" class="form-control" value="<?= htmlspecialchars($range_name) ?>" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small fw-bold">Date of Record <span class="text-danger">*</span></label>
                            <input type="date" name="record_date" id="add_col_date" class="form-control" value="<?= date('Y-m-d') ?>" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small fw-bold">Report Year</label>
                            <input type="number" name="report_year" id="add_col_year" class="form-control" value="<?= date('Y') ?>" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small fw-bold">Report Month</label>
                            <select name="report_month" id="add_col_month" class="form-select">
                                <option value="">Annual Aggregate</option>
                                <?php foreach ($months_map as $num => $name): ?>
                                    <option value="<?= $num ?>" <?= intval(date('n')) === $num ? 'selected' : '' ?>><?= $name ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Collecting Center Name <span class="text-danger">*</span></label>
                            <input type="text" name="collecting_center_name" class="form-control" required placeholder="e.g. Mahaoya Milk Chilling & Collection Point">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Contact Number</label>
                            <input type="text" name="contact_no" class="form-control" placeholder="e.g. 063-2223456">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Address</label>
                        <input type="text" name="address" class="form-control" placeholder="Center Street Address / Location">
                    </div>

                    <!-- Strictly Subdivided by Milk Type: Cow, Buffalo, Goat -->
                    <div class="card bg-light border-0 p-3 mb-3 rounded">
                        <label class="form-label small fw-bold text-primary mb-2">
                            <i class="bi bi-segmented-nav me-1"></i>Monthly Milk Collection Breakdown by Species (Litres / Month)
                        </label>
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label small fw-bold">Cow Milk (L/m)</label>
                                <input type="number" step="0.01" name="cow_milk_lit_month" id="add_c_cow" class="form-control calc-collecting" value="0.00">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small fw-bold">Buffalo Milk (L/m)</label>
                                <input type="number" step="0.01" name="buffalo_milk_lit_month" id="add_c_buf" class="form-control calc-collecting" value="0.00">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small fw-bold">Goat Milk (L/m)</label>
                                <input type="number" step="0.01" name="goat_milk_lit_month" id="add_c_goat" class="form-control calc-collecting" value="0.00">
                            </div>
                        </div>
                        <div class="mt-2 text-end">
                            <span class="small text-muted">Total Collection Preview: </span>
                            <strong class="text-success font-monospace" id="add_c_total_preview">0.00 L</strong>
                        </div>
                    </div>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Milk Chilling Capacity (Litres)</label>
                            <input type="number" step="0.01" name="milk_chilling_capacity" class="form-control" value="0.00">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Supply Distributed To</label>
                            <input type="text" name="milk_supply_to" class="form-control" placeholder="e.g. Milco, Pelwatte, Local Vendors">
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary fw-bold"><i class="bi bi-check-circle-fill me-1"></i>Save Collecting Center</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- MODAL: ADD PROCESSING CENTER -->
<div class="modal fade" id="addProcessingModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title fw-bold"><i class="bi bi-plus-circle me-2"></i>Add Milk Processing Center</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="milk_collection_details.php?tab=processing">
                <input type="hidden" name="sub_module" value="processing">
                <input type="hidden" name="action" value="add">
                <div class="modal-body p-4">
                    <div class="row g-3 mb-3">
                        <div class="col-md-3">
                            <label class="form-label small fw-bold">VS Range</label>
                            <input type="text" name="vs_range" class="form-control" value="<?= htmlspecialchars($range_name) ?>" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small fw-bold">Date of Record <span class="text-danger">*</span></label>
                            <input type="date" name="record_date" id="add_proc_date" class="form-control" value="<?= date('Y-m-d') ?>" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small fw-bold">Report Year</label>
                            <input type="number" name="report_year" id="add_proc_year" class="form-control" value="<?= date('Y') ?>" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small fw-bold">Report Month</label>
                            <select name="report_month" id="add_proc_month" class="form-select">
                                <option value="">Annual Aggregate</option>
                                <?php foreach ($months_map as $num => $name): ?>
                                    <option value="<?= $num ?>" <?= intval(date('n')) === $num ? 'selected' : '' ?>><?= $name ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Processing Center Name <span class="text-danger">*</span></label>
                            <input type="text" name="processing_center_name" class="form-control" required placeholder="e.g. Eastern Dairy Processors Ltd">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Contact Number</label>
                            <input type="text" name="contact_no" class="form-control" placeholder="e.g. 065-2227788">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Address</label>
                        <input type="text" name="address" class="form-control" placeholder="Factory / Processing Address">
                    </div>

                    <!-- Strictly Subdivided by Milk Type: Cow, Buffalo, Goat -->
                    <div class="card bg-light border-0 p-3 mb-3 rounded">
                        <label class="form-label small fw-bold text-primary mb-2">
                            <i class="bi bi-segmented-nav me-1"></i>Raw Milk Processed by Species (Litres / Month)
                        </label>
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label small fw-bold">Cow Milk (L/m)</label>
                                <input type="number" step="0.01" name="cow_milk_lit_month" class="form-control" value="0.00">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small fw-bold">Buffalo Milk (L/m)</label>
                                <input type="number" step="0.01" name="buffalo_milk_lit_month" class="form-control" value="0.00">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small fw-bold">Goat Milk (L/m)</label>
                                <input type="number" step="0.01" name="goat_milk_lit_month" class="form-control" value="0.00">
                            </div>
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-3">
                            <label class="form-label small fw-bold">Yoghurt (L/m)</label>
                            <input type="number" step="0.01" name="yoghurt_lit_per_month" class="form-control" value="0.00">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small fw-bold">Curd (L/m)</label>
                            <input type="number" step="0.01" name="curd_lit_per_month" class="form-control" value="0.00">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small fw-bold">Ice Cream (L/m)</label>
                            <input type="number" step="0.01" name="ice_cream_lit_per_month" class="form-control" value="0.00">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small fw-bold">Ghee (L/m)</label>
                            <input type="number" step="0.01" name="ghee_lit_per_month" class="form-control" value="0.00">
                        </div>
                    </div>

                    <!-- Privacy Protected Income Range Field -->
                    <div class="card bg-white border p-3 mb-0 rounded">
                        <label class="form-label small fw-bold text-dark">
                            <i class="bi bi-shield-lock-fill text-primary me-1"></i>Monthly Income Range (Privacy-Protected)
                        </label>
                        <select name="income_range" class="form-select">
                            <option value="">-- Select Approximate Monthly Income Bracket --</option>
                            <?php foreach ($income_ranges as $ir): ?>
                                <option value="<?= htmlspecialchars($ir) ?>"><?= htmlspecialchars($ir) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <small class="text-muted mt-1">Exact figures replaced with standard ranges for producer privacy.</small>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary fw-bold"><i class="bi bi-check-circle-fill me-1"></i>Save Processing Center</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- MODAL: ADD SALES CENTER -->
<div class="modal fade" id="addSalesModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title fw-bold"><i class="bi bi-plus-circle me-2"></i>Add Milk Sales Center</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="milk_collection_details.php?tab=sales">
                <input type="hidden" name="sub_module" value="sales">
                <input type="hidden" name="action" value="add">
                <div class="modal-body p-4">
                    <div class="row g-3 mb-3">
                        <div class="col-md-3">
                            <label class="form-label small fw-bold">VS Range</label>
                            <input type="text" name="vs_range" class="form-control" value="<?= htmlspecialchars($range_name) ?>" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small fw-bold">Date of Record <span class="text-danger">*</span></label>
                            <input type="date" name="record_date" id="add_sales_date" class="form-control" value="<?= date('Y-m-d') ?>" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small fw-bold">Report Year</label>
                            <input type="number" name="report_year" id="add_sales_year" class="form-control" value="<?= date('Y') ?>" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small fw-bold">Report Month</label>
                            <select name="report_month" id="add_sales_month" class="form-select">
                                <option value="">Annual Aggregate</option>
                                <?php foreach ($months_map as $num => $name): ?>
                                    <option value="<?= $num ?>" <?= intval(date('n')) === $num ? 'selected' : '' ?>><?= $name ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Sales Center / Milk Bar Name <span class="text-danger">*</span></label>
                            <input type="text" name="sales_center_name" class="form-control" required placeholder="e.g. Fresh Milk Bar - Clock Tower">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Contact Number</label>
                            <input type="text" name="contact_no" class="form-control" placeholder="e.g. 065-3331122">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Address</label>
                        <input type="text" name="address" class="form-control" placeholder="Outlet Address / Location">
                    </div>

                    <!-- Strictly Subdivided by Milk Type: Cow, Buffalo, Goat -->
                    <div class="card bg-light border-0 p-3 mb-3 rounded">
                        <label class="form-label small fw-bold text-primary mb-2">
                            <i class="bi bi-segmented-nav me-1"></i>Liquid Milk Sales by Species (Litres / Month)
                        </label>
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label small fw-bold">Cow Milk (L/m)</label>
                                <input type="number" step="0.01" name="cow_milk_lit_month" class="form-control" value="0.00">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small fw-bold">Buffalo Milk (L/m)</label>
                                <input type="number" step="0.01" name="buffalo_milk_lit_month" class="form-control" value="0.00">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small fw-bold">Goat Milk (L/m)</label>
                                <input type="number" step="0.01" name="goat_milk_lit_month" class="form-control" value="0.00">
                            </div>
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-4">
                            <label class="form-label small fw-bold">Fresh Milk (L/m)</label>
                            <input type="number" step="0.01" name="fresh_milk_lit_per_month" class="form-control" value="0.00">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold">Yoghurt (L/m)</label>
                            <input type="number" step="0.01" name="yoghurt_lit_per_month" class="form-control" value="0.00">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold">Curd (L/m)</label>
                            <input type="number" step="0.01" name="curd_lit_per_month" class="form-control" value="0.00">
                        </div>
                    </div>

                    <!-- Privacy Protected Income Range Field -->
                    <div class="card bg-white border p-3 mb-0 rounded">
                        <label class="form-label small fw-bold text-dark">
                            <i class="bi bi-shield-lock-fill text-primary me-1"></i>Monthly Income Range (Privacy-Protected)
                        </label>
                        <select name="income_range" class="form-select">
                            <option value="">-- Select Approximate Monthly Income Bracket --</option>
                            <?php foreach ($income_ranges as $ir): ?>
                                <option value="<?= htmlspecialchars($ir) ?>"><?= htmlspecialchars($ir) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary fw-bold"><i class="bi bi-check-circle-fill me-1"></i>Save Sales Center</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- EDIT COLLECTING MODAL -->
<div class="modal fade" id="editCollectingModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title fw-bold"><i class="bi bi-pencil-square me-2"></i>Edit Milk Collecting Center</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="milk_collection_details.php?tab=collecting">
                <input type="hidden" name="sub_module" value="collecting">
                <input type="hidden" name="action" value="edit">
                <input type="hidden" name="id" id="edit_col_id">
                <div class="modal-body p-4">
                    <div class="row g-3 mb-3">
                        <div class="col-md-3">
                            <label class="form-label small fw-bold">VS Range</label>
                            <input type="text" name="vs_range" id="edit_col_range" class="form-control" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small fw-bold">Date of Record <span class="text-danger">*</span></label>
                            <input type="date" name="record_date" id="edit_col_date" class="form-control" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small fw-bold">Report Year</label>
                            <input type="number" name="report_year" id="edit_col_year" class="form-control" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small fw-bold">Report Month</label>
                            <select name="report_month" id="edit_col_month" class="form-select">
                                <option value="">Annual Aggregate</option>
                                <?php foreach ($months_map as $num => $name): ?>
                                    <option value="<?= $num ?>"><?= $name ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Collecting Center Name</label>
                            <input type="text" name="collecting_center_name" id="edit_col_name" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Contact Number</label>
                            <input type="text" name="contact_no" id="edit_col_contact" class="form-control">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Address</label>
                        <input type="text" name="address" id="edit_col_address" class="form-control">
                    </div>
                    <div class="card bg-light border-0 p-3 mb-3 rounded">
                        <label class="form-label small fw-bold text-primary mb-2">Breakdown by Species (Litres / Month)</label>
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label small fw-bold">Cow Milk</label>
                                <input type="number" step="0.01" name="cow_milk_lit_month" id="edit_col_cow" class="form-control">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small fw-bold">Buffalo Milk</label>
                                <input type="number" step="0.01" name="buffalo_milk_lit_month" id="edit_col_buffalo" class="form-control">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small fw-bold">Goat Milk</label>
                                <input type="number" step="0.01" name="goat_milk_lit_month" id="edit_col_goat" class="form-control">
                            </div>
                        </div>
                    </div>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Chilling Capacity (L)</label>
                            <input type="number" step="0.01" name="milk_chilling_capacity" id="edit_col_chilling" class="form-control">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Supply Distributed To</label>
                            <input type="text" name="milk_supply_to" id="edit_col_supply" class="form-control">
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary fw-bold">Update Collecting Center</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- EDIT PROCESSING MODAL -->
<div class="modal fade" id="editProcessingModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title fw-bold"><i class="bi bi-pencil-square me-2"></i>Edit Milk Processing Center</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="milk_collection_details.php?tab=processing">
                <input type="hidden" name="sub_module" value="processing">
                <input type="hidden" name="action" value="edit">
                <input type="hidden" name="id" id="edit_proc_id">
                <div class="modal-body p-4">
                    <div class="row g-3 mb-3">
                        <div class="col-md-3">
                            <label class="form-label small fw-bold">VS Range</label>
                            <input type="text" name="vs_range" id="edit_proc_range" class="form-control" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small fw-bold">Date of Record <span class="text-danger">*</span></label>
                            <input type="date" name="record_date" id="edit_proc_date" class="form-control" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small fw-bold">Report Year</label>
                            <input type="number" name="report_year" id="edit_proc_year" class="form-control" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small fw-bold">Report Month</label>
                            <select name="report_month" id="edit_proc_month" class="form-select">
                                <option value="">Annual Aggregate</option>
                                <?php foreach ($months_map as $num => $name): ?>
                                    <option value="<?= $num ?>"><?= $name ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Processing Center Name</label>
                            <input type="text" name="processing_center_name" id="edit_proc_name" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Contact Number</label>
                            <input type="text" name="contact_no" id="edit_proc_contact" class="form-control">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Address</label>
                        <input type="text" name="address" id="edit_proc_address" class="form-control">
                    </div>
                    <div class="card bg-light border-0 p-3 mb-3 rounded">
                        <label class="form-label small fw-bold text-primary mb-2">Raw Milk Processed by Species (Litres / Month)</label>
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label small fw-bold">Cow Milk (L/m)</label>
                                <input type="number" step="0.01" name="cow_milk_lit_month" id="edit_proc_cow" class="form-control">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small fw-bold">Buffalo Milk (L/m)</label>
                                <input type="number" step="0.01" name="buffalo_milk_lit_month" id="edit_proc_buffalo" class="form-control">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small fw-bold">Goat Milk (L/m)</label>
                                <input type="number" step="0.01" name="goat_milk_lit_month" id="edit_proc_goat" class="form-control">
                            </div>
                        </div>
                    </div>
                    <div class="row g-3 mb-3">
                        <div class="col-md-3">
                            <label class="form-label small fw-bold">Yoghurt (L/m)</label>
                            <input type="number" step="0.01" name="yoghurt_lit_per_month" id="edit_proc_yoghurt" class="form-control">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small fw-bold">Curd (L/m)</label>
                            <input type="number" step="0.01" name="curd_lit_per_month" id="edit_proc_curd" class="form-control">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small fw-bold">Ice Cream (L/m)</label>
                            <input type="number" step="0.01" name="ice_cream_lit_per_month" id="edit_proc_icecream" class="form-control">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small fw-bold">Ghee (L/m)</label>
                            <input type="number" step="0.01" name="ghee_lit_per_month" id="edit_proc_ghee" class="form-control">
                        </div>
                    </div>
                    <div class="card bg-white border p-3 mb-0 rounded">
                        <label class="form-label small fw-bold text-dark">Monthly Income Range</label>
                        <select name="income_range" id="edit_proc_incomerange" class="form-select">
                            <option value="">-- Select Income Range --</option>
                            <?php foreach ($income_ranges as $ir): ?>
                                <option value="<?= htmlspecialchars($ir) ?>"><?= htmlspecialchars($ir) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary fw-bold">Update Processing Center</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- EDIT SALES MODAL -->
<div class="modal fade" id="editSalesModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title fw-bold"><i class="bi bi-pencil-square me-2"></i>Edit Milk Sales Center</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="milk_collection_details.php?tab=sales">
                <input type="hidden" name="sub_module" value="sales">
                <input type="hidden" name="action" value="edit">
                <input type="hidden" name="id" id="edit_sales_id">
                <div class="modal-body p-4">
                    <div class="row g-3 mb-3">
                        <div class="col-md-3">
                            <label class="form-label small fw-bold">VS Range</label>
                            <input type="text" name="vs_range" id="edit_sales_range" class="form-control" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small fw-bold">Date of Record <span class="text-danger">*</span></label>
                            <input type="date" name="record_date" id="edit_sales_date" class="form-control" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small fw-bold">Report Year</label>
                            <input type="number" name="report_year" id="edit_sales_year" class="form-control" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small fw-bold">Report Month</label>
                            <select name="report_month" id="edit_sales_month" class="form-select">
                                <option value="">Annual Aggregate</option>
                                <?php foreach ($months_map as $num => $name): ?>
                                    <option value="<?= $num ?>"><?= $name ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Sales Center Name</label>
                            <input type="text" name="sales_center_name" id="edit_sales_name" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Contact Number</label>
                            <input type="text" name="contact_no" id="edit_sales_contact" class="form-control">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Address</label>
                        <input type="text" name="address" id="edit_sales_address" class="form-control">
                    </div>
                    <div class="card bg-light border-0 p-3 mb-3 rounded">
                        <label class="form-label small fw-bold text-primary mb-2">Liquid Milk Sales by Species (Litres / Month)</label>
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label small fw-bold">Cow Milk</label>
                                <input type="number" step="0.01" name="cow_milk_lit_month" id="edit_sales_cow" class="form-control">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small fw-bold">Buffalo Milk</label>
                                <input type="number" step="0.01" name="buffalo_milk_lit_month" id="edit_sales_buffalo" class="form-control">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small fw-bold">Goat Milk</label>
                                <input type="number" step="0.01" name="goat_milk_lit_month" id="edit_sales_goat" class="form-control">
                            </div>
                        </div>
                    </div>
                    <div class="row g-3 mb-3">
                        <div class="col-md-4">
                            <label class="form-label small fw-bold">Fresh Milk (L/m)</label>
                            <input type="number" step="0.01" name="fresh_milk_lit_per_month" id="edit_sales_fresh" class="form-control">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold">Yoghurt (L/m)</label>
                            <input type="number" step="0.01" name="yoghurt_lit_per_month" id="edit_sales_yoghurt" class="form-control">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold">Curd (L/m)</label>
                            <input type="number" step="0.01" name="curd_lit_per_month" id="edit_sales_curd" class="form-control">
                        </div>
                    </div>
                    <div class="card bg-white border p-3 mb-0 rounded">
                        <label class="form-label small fw-bold text-dark">Monthly Income Range</label>
                        <select name="income_range" id="edit_sales_incomerange" class="form-select">
                            <option value="">-- Select Income Range --</option>
                            <?php foreach ($income_ranges as $ir): ?>
                                <option value="<?= htmlspecialchars($ir) ?>"><?= htmlspecialchars($ir) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary fw-bold">Update Sales Center</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- GLOBAL DELETE CONFIRM MODAL -->
<div class="modal fade" id="deleteEntryModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content border-0 shadow">
            <div class="modal-body text-center p-4">
                <i class="bi bi-exclamation-triangle-fill text-danger fs-1 mb-2 d-block"></i>
                <h5 class="fw-bold mb-2">Delete Record?</h5>
                <p class="text-muted small mb-3" id="deleteEntryTitle"></p>
                <form method="POST" id="deleteEntryForm">
                    <input type="hidden" name="sub_module" id="deleteEntryModule">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="id" id="deleteEntryId">
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
    // Synchronize Date changes to Year and Month selects
    function syncDateToYearMonth(dateSelector, yearSelector, monthSelector) {
        $(dateSelector).on("change", function() {
            const val = $(this).val();
            if (val) {
                const parts = val.split('-');
                if (parts.length === 3) {
                    $(yearSelector).val(parts[0]);
                    $(monthSelector).val(parseInt(parts[1], 10));
                }
            }
        });
    }
    syncDateToYearMonth("#add_col_date", "#add_col_year", "#add_col_month");
    syncDateToYearMonth("#edit_col_date", "#edit_col_year", "#edit_col_month");
    syncDateToYearMonth("#add_proc_date", "#add_proc_year", "#add_proc_month");
    syncDateToYearMonth("#edit_proc_date", "#edit_proc_year", "#edit_proc_month");
    syncDateToYearMonth("#add_sales_date", "#add_sales_year", "#add_sales_month");
    syncDateToYearMonth("#edit_sales_date", "#edit_sales_year", "#edit_sales_month");

    // Collecting add calculation preview
    $(".calc-collecting").on("input", function() {
        const c = parseFloat($("#add_c_cow").val()) || 0;
        const b = parseFloat($("#add_c_buf").val()) || 0;
        const g = parseFloat($("#add_c_goat").val()) || 0;
        $("#add_c_total_preview").text((c + b + g).toFixed(2) + " L");
    });

    // Populate Edit Collecting
    $(".btn-edit-collecting").on("click", function() {
        const btn = $(this);
        $("#edit_col_id").val(btn.data("id"));
        $("#edit_col_date").val(btn.data("date") || "");
        $("#edit_col_range").val(btn.data("range"));
        $("#edit_col_year").val(btn.data("year"));
        $("#edit_col_month").val(btn.data("month"));
        $("#edit_col_name").val(btn.data("name"));
        $("#edit_col_address").val(btn.data("address"));
        $("#edit_col_contact").val(btn.data("contact"));
        $("#edit_col_cow").val(btn.data("cow"));
        $("#edit_col_buffalo").val(btn.data("buffalo"));
        $("#edit_col_goat").val(btn.data("goat"));
        $("#edit_col_chilling").val(btn.data("chilling"));
        $("#edit_col_supply").val(btn.data("supply"));
        $("#editCollectingModal").modal("show");
    });

    // Populate Edit Processing
    $(".btn-edit-processing").on("click", function() {
        const btn = $(this);
        $("#edit_proc_id").val(btn.data("id"));
        $("#edit_proc_date").val(btn.data("date") || "");
        $("#edit_proc_range").val(btn.data("range"));
        $("#edit_proc_year").val(btn.data("year"));
        $("#edit_proc_month").val(btn.data("month"));
        $("#edit_proc_name").val(btn.data("name"));
        $("#edit_proc_address").val(btn.data("address"));
        $("#edit_proc_contact").val(btn.data("contact"));
        $("#edit_proc_cow").val(btn.data("cow"));
        $("#edit_proc_buffalo").val(btn.data("buffalo"));
        $("#edit_proc_goat").val(btn.data("goat"));
        $("#edit_proc_yoghurt").val(btn.data("yoghurt"));
        $("#edit_proc_curd").val(btn.data("curd"));
        $("#edit_proc_icecream").val(btn.data("icecream"));
        $("#edit_proc_ghee").val(btn.data("ghee"));
        $("#edit_proc_incomerange").val(btn.data("incomerange"));
        $("#editProcessingModal").modal("show");
    });

    // Populate Edit Sales
    $(".btn-edit-sales").on("click", function() {
        const btn = $(this);
        $("#edit_sales_id").val(btn.data("id"));
        $("#edit_sales_date").val(btn.data("date") || "");
        $("#edit_sales_range").val(btn.data("range"));
        $("#edit_sales_year").val(btn.data("year"));
        $("#edit_sales_month").val(btn.data("month"));
        $("#edit_sales_name").val(btn.data("name"));
        $("#edit_sales_address").val(btn.data("address"));
        $("#edit_sales_contact").val(btn.data("contact"));
        $("#edit_sales_cow").val(btn.data("cow"));
        $("#edit_sales_buffalo").val(btn.data("buffalo"));
        $("#edit_sales_goat").val(btn.data("goat"));
        $("#edit_sales_fresh").val(btn.data("fresh"));
        $("#edit_sales_yoghurt").val(btn.data("yoghurt"));
        $("#edit_sales_curd").val(btn.data("curd"));
        $("#edit_sales_incomerange").val(btn.data("incomerange"));
        $("#editSalesModal").modal("show");
    });

    // Global Delete Modal trigger
    $(".btn-delete-entry").on("click", function() {
        const btn = $(this);
        const module = btn.data("module");
        $("#deleteEntryModule").val(module);
        $("#deleteEntryId").val(btn.data("id"));
        $("#deleteEntryTitle").text(btn.data("title"));
        $("#deleteEntryForm").attr("action", `milk_collection_details.php?tab=${module}`);
        $("#deleteEntryModal").modal("show");
    });
});
</script>
<?php
$extra_scripts = ob_get_clean();
include '../../../includes/footer.php';
?>
