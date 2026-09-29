<?php
session_start();
require_once __DIR__ . '/../../../config/db_connect.php';

/** @var mysqli $mysqli */
global $mysqli;

$allowed_roles = [
    'veterinary_surgeon',
    'sms',
    'district_dd',
    'deputy_director_district',
    'administrator',
    'provincial_director',
    'deputy_director_hq_1',
    'deputy_director_hq_2',
    'admin'
];

if (!isset($_SESSION['logged_in']) || !in_array($_SESSION['role'] ?? '', $allowed_roles, true)) {
    header("Location: ../../../index.php");
    exit();
}

$user_id = $_SESSION['user_id'] ?? null;
$user_role = $_SESSION['role'] ?? '';
$is_supervisory = in_array($user_role, [
    'district_dd',
    'deputy_director_district',
    'administrator',
    'provincial_director',
    'deputy_director_hq_1',
    'deputy_director_hq_2',
    'admin'
]);

$requested_range_id = isset($_GET['range_id']) && is_numeric($_GET['range_id']) ? (int)$_GET['range_id'] : null;
$range_id = $requested_range_id ?: ($_SESSION['range_id'] ?? null);

$range_name = 'Trincomalee';
$district_name = 'Trincomalee';
$district_id = null;
$province_name = 'Eastern Province';

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


// -------------------------------------------------------------------------
// 2. Handle CRUD Form Submissions (Create / Update / Delete)
// -------------------------------------------------------------------------
$alert_status = null;
$alert_message = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];

    // SAVE / UPDATE RECORD
    if ($action === 'save_renewal') {
        $edit_id = !empty($_POST['editing_record_id']) ? intval($_POST['editing_record_id']) : null;
        $range_context = !empty($_POST['range_id']) ? intval($_POST['range_id']) : ($range_id ?: 1);
        $district_context = $district_id ?: 1;

        // 1. General Information
        $reg_date = trim($_POST['general']['date_of_registration_renewal'] ?? date('Y-m-d'));
        $prov = trim($_POST['general']['province'] ?? $province_name);
        $dist = trim($_POST['general']['district'] ?? $district_name);
        $ds_div = trim($_POST['general']['ds_division'] ?? '');
        $vs_div = trim($_POST['general']['vs_division'] ?? $range_name);
        $gn_div = trim($_POST['general']['gn_division'] ?? '');
        $farmer_name = trim($_POST['general']['farmer_name'] ?? '');
        $farmer_addr = trim($_POST['general']['farmer_address'] ?? '');
        $reg_no = trim($_POST['general']['registration_no'] ?? '');
        $phone = trim($_POST['general']['telephone_no'] ?? '');
        $nic = trim($_POST['general']['nic'] ?? '');
        $farm_type = trim($_POST['general']['farm_type'] ?? '');
        $mixed_type = trim($_POST['general']['mixed_farm_type'] ?? '');

        // 2. Neat Cattle Data
        $cattle_input = $_POST['cattle'] ?? [];
        $neat_cattle_json = json_encode($cattle_input);
        $total_neat_cattle = 0;
        foreach (['cows_milch', 'unproductive_cows', 'heifers', 'female_under_1', 'bulls', 'male_under_1'] as $rKey) {
            foreach (['european', 'indian', 'local'] as $cKey) {
                $total_neat_cattle += intval($cattle_input[$rKey][$cKey] ?? 0);
            }
        }

        // 3. Buffaloes Data
        $buf_input = $_POST['buffalo'] ?? [];
        $buffalo_json = json_encode($buf_input);
        $total_buffaloes = 0;
        foreach (['cows_milch', 'unproductive_cows', 'heifers', 'female_under_1', 'bulls', 'male_under_1'] as $rKey) {
            foreach (['niliravi', 'murah', 'cross_breed', 'indian', 'local'] as $cKey) {
                if (isset($buf_input[$rKey][$cKey])) {
                    $total_buffaloes += intval($buf_input[$rKey][$cKey]);
                }
            }
        }

        // 4. Milk Production and Sale
        $milk_input = $_POST['milk'] ?? [];
        $milk_json = json_encode($milk_input);
        $cow_prod = floatval($milk_input['total_production']['cow'] ?? 0);
        $buf_prod = floatval($milk_input['total_production']['buffalo'] ?? 0);
        $daily_milk_prod = $cow_prod + $buf_prod;

        // 5. Fodder / Pasture (Dynamic 1-to-Many Rows)
        $fodder_items_input = $_POST['fodder_items'] ?? [];
        $parsed_fodder_items = [];
        $fod_napier = 0.0;
        $fod_sorghum = 0.0;
        $fod_maize = 0.0;
        $fod_other = 0.0;
        $fod_other_specify_arr = [];
        $fod_total = 0.0;

        if (is_array($fodder_items_input)) {
            foreach ($fodder_items_input as $fRow) {
                $item = trim($fRow['item'] ?? '');
                $amt = floatval($fRow['amount'] ?? 0);
                $spec = trim($fRow['other_specify'] ?? '');
                if ($item === '') continue;

                $parsed_fodder_items[] = [
                    'item' => $item,
                    'amount' => $amt,
                    'other_specify' => ($item === 'Other') ? $spec : ''
                ];

                $fod_total += $amt;

                if ($item === 'Hybrid Napier') {
                    $fod_napier += $amt;
                } elseif ($item === 'Sorghum') {
                    $fod_sorghum += $amt;
                } elseif ($item === 'Maize(fodder)') {
                    $fod_maize += $amt;
                } elseif ($item === 'Other') {
                    $fod_other += $amt;
                    if (!empty($spec)) {
                        $fod_other_specify_arr[] = $spec;
                    }
                }
            }
        }
        $fod_other_specify = implode(', ', $fod_other_specify_arr);
        $fodder_json = json_encode($parsed_fodder_items);

        // 6. Swine
        $swine_f = intval($_POST['swine']['breeding_female'] ?? 0);
        $swine_m = intval($_POST['swine']['breeding_male'] ?? 0);
        $swine_w = intval($_POST['swine']['weaners_fattening'] ?? 0);
        $swine_p = intval($_POST['swine']['pre_weaners'] ?? 0);
        $swine_tot = intval($_POST['swine']['total_no'] ?? ($swine_f + $swine_m + $swine_w + $swine_p));
        $swine_freq_meat = trim($_POST['swine']['freq_sales_meat'] ?? 'Not applicable');
        $swine_freq_breed = trim($_POST['swine']['freq_sales_breeding'] ?? 'Not applicable');

        // 7. Goat
        $goat_f = intval($_POST['goat']['breeding_female'] ?? 0);
        $goat_m = intval($_POST['goat']['breeding_male'] ?? 0);
        $goat_w = intval($_POST['goat']['weaners_fattening'] ?? 0);
        $goat_p = intval($_POST['goat']['pre_weaners'] ?? 0);
        $goat_tot = intval($_POST['goat']['total_no'] ?? ($goat_f + $goat_m + $goat_w + $goat_p));
        $goat_freq_meat = trim($_POST['goat']['freq_sales_meat'] ?? 'Not applicable');
        $goat_freq_breed = trim($_POST['goat']['freq_sales_breeding'] ?? 'Not applicable');
        $goat_milk = floatval($_POST['goat']['milk_production_per_day'] ?? 0);

        // 8. Sheep
        $sheep_f = intval($_POST['sheep']['breeding_female'] ?? 0);
        $sheep_m = intval($_POST['sheep']['breeding_male'] ?? 0);
        $sheep_meat = intval($_POST['sheep']['for_meat'] ?? 0);
        $sheep_tot = $sheep_f + $sheep_m + $sheep_meat;

        if ($edit_id) {
            // UPDATE existing database record
            $upd_stmt = $mysqli->prepare("
                UPDATE `farm_registration_renewals` SET
                    `date_of_registration_renewal` = ?, `province` = ?, `district` = ?, `ds_division` = ?,
                    `vs_division` = ?, `gn_division` = ?, `farmer_name` = ?, `farmer_address` = ?,
                    `registration_no` = ?, `telephone_no` = ?, `nic` = ?, `farm_type` = ?,
                    `mixed_farm_type` = ?, `neat_cattle_data` = ?, `total_neat_cattle` = ?,
                    `buffaloes_data` = ?, `total_buffaloes` = ?, `milk_data` = ?,
                    `daily_milk_production` = ?, `fodder_hybrid_napier` = ?, `fodder_sorghum` = ?,
                    `fodder_maize` = ?, `fodder_other` = ?, `fodder_other_specify` = ?, `fodder_total_land_area` = ?,
                    `fodder_data` = ?,
                    `swine_total_no` = ?, `swine_breeding_female` = ?, `swine_breeding_male` = ?,
                    `swine_weaners_fattening` = ?, `swine_pre_weaners` = ?, `swine_freq_meat` = ?,
                    `swine_freq_breeding` = ?, `goat_total_no` = ?, `goat_breeding_female` = ?,
                    `goat_breeding_male` = ?, `goat_weaners_fattening` = ?, `goat_pre_weaners` = ?,
                    `goat_freq_meat` = ?, `goat_freq_breeding` = ?, `goat_milk_per_day` = ?,
                    `sheep_breeding_female` = ?, `sheep_breeding_male` = ?, `sheep_for_meat` = ?,
                    `sheep_total_no` = ?
                WHERE `id` = ?
            ");
            if ($upd_stmt) {
                $upd_stmt->bind_param(
                    "ssssssssssssssisisdddddsdsiiiiissiiiiissdiiiii",
                    $reg_date, $prov, $dist, $ds_div,
                    $vs_div, $gn_div, $farmer_name, $farmer_addr,
                    $reg_no, $phone, $nic, $farm_type,
                    $mixed_type, $neat_cattle_json, $total_neat_cattle,
                    $buffalo_json, $total_buffaloes, $milk_json,
                    $daily_milk_prod, $fod_napier, $fod_sorghum,
                    $fod_maize, $fod_other, $fod_other_specify, $fod_total,
                    $fodder_json,
                    $swine_tot, $swine_f, $swine_m,
                    $swine_w, $swine_p, $swine_freq_meat,
                    $swine_freq_breed, $goat_tot, $goat_f,
                    $goat_m, $goat_w, $goat_p,
                    $goat_freq_meat, $goat_freq_breed, $goat_milk,
                    $sheep_f, $sheep_m, $sheep_meat,
                    $sheep_tot, $edit_id
                );
                $upd_stmt->execute();
                $upd_stmt->close();

                // Sync 1-to-many child table
                $del_fod = $mysqli->prepare("DELETE FROM `farm_registration_fodder_items` WHERE `farm_registration_id` = ?");
                if ($del_fod) {
                    $del_fod->bind_param("i", $edit_id);
                    $del_fod->execute();
                    $del_fod->close();
                }
                if (!empty($parsed_fodder_items)) {
                    $ins_fod = $mysqli->prepare("INSERT INTO `farm_registration_fodder_items` (`farm_registration_id`, `crop_item`, `other_specify`, `amount_perches`) VALUES (?, ?, ?, ?)");
                    if ($ins_fod) {
                        foreach ($parsed_fodder_items as $pItem) {
                            $ins_fod->bind_param("issd", $edit_id, $pItem['item'], $pItem['other_specify'], $pItem['amount']);
                            $ins_fod->execute();
                        }
                        $ins_fod->close();
                    }
                }

                $alert_status = 'success';
                $alert_message = "Registration record #$reg_no updated successfully in the database.";
            } else {
                $alert_status = 'danger';
                $alert_message = "Database error: " . $mysqli->error;
            }
        } else {
            // INSERT new database record
            $ins_stmt = $mysqli->prepare("
                INSERT INTO `farm_registration_renewals` (
                    `range_id`, `district_id`, `date_of_registration_renewal`, `province`, `district`,
                    `ds_division`, `vs_division`, `gn_division`, `farmer_name`, `farmer_address`,
                    `registration_no`, `telephone_no`, `nic`, `farm_type`, `mixed_farm_type`,
                    `neat_cattle_data`, `total_neat_cattle`, `buffaloes_data`, `total_buffaloes`,
                    `milk_data`, `daily_milk_production`, `fodder_hybrid_napier`, `fodder_sorghum`,
                    `fodder_maize`, `fodder_other`, `fodder_other_specify`, `fodder_total_land_area`, `fodder_data`,
                    `swine_total_no`, `swine_breeding_female`, `swine_breeding_male`, `swine_weaners_fattening`,
                    `swine_pre_weaners`, `swine_freq_meat`, `swine_freq_breeding`, `goat_total_no`,
                    `goat_breeding_female`, `goat_breeding_male`, `goat_weaners_fattening`, `goat_pre_weaners`,
                    `goat_freq_meat`, `goat_freq_breeding`, `goat_milk_per_day`, `sheep_breeding_female`,
                    `sheep_breeding_male`, `sheep_for_meat`, `sheep_total_no`, `created_by`
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            if ($ins_stmt) {
                $ins_stmt->bind_param(
                    "iissssssssssssssisisdddddsdsiiiiissiiiiissdiiiii",
                    $range_context, $district_context, $reg_date, $prov, $dist,
                    $ds_div, $vs_div, $gn_div, $farmer_name, $farmer_addr,
                    $reg_no, $phone, $nic, $farm_type, $mixed_type,
                    $neat_cattle_json, $total_neat_cattle, $buffalo_json, $total_buffaloes,
                    $milk_json, $daily_milk_prod, $fod_napier, $fod_sorghum,
                    $fod_maize, $fod_other, $fod_other_specify, $fod_total, $fodder_json,
                    $swine_tot, $swine_f, $swine_m, $swine_w, $swine_p,
                    $swine_freq_meat, $swine_freq_breed, $goat_tot, $goat_f,
                    $goat_m, $goat_w, $goat_p, $goat_freq_meat,
                    $goat_freq_breed, $goat_milk, $sheep_f, $sheep_m,
                    $sheep_meat, $sheep_tot, $user_id
                );
                $ins_stmt->execute();
                $new_reg_id = $ins_stmt->insert_id;
                $ins_stmt->close();

                // Sync 1-to-many child table
                if ($new_reg_id && !empty($parsed_fodder_items)) {
                    $ins_fod = $mysqli->prepare("INSERT INTO `farm_registration_fodder_items` (`farm_registration_id`, `crop_item`, `other_specify`, `amount_perches`) VALUES (?, ?, ?, ?)");
                    if ($ins_fod) {
                        foreach ($parsed_fodder_items as $pItem) {
                            $ins_fod->bind_param("issd", $new_reg_id, $pItem['item'], $pItem['other_specify'], $pItem['amount']);
                            $ins_fod->execute();
                        }
                        $ins_fod->close();
                    }
                }

                $alert_status = 'success';
                $alert_message = "Registration record #$reg_no saved successfully to the database.";
            } else {
                $alert_status = 'danger';
                $alert_message = "Database error: " . $mysqli->error;
            }
        }

        // Synchronize with central farmers table if NIC provided
        if (!empty($nic)) {
            $chk_f = $mysqli->prepare("SELECT id FROM `farmers` WHERE nic_no = ? LIMIT 1");
            if ($chk_f) {
                $chk_f->bind_param("s", $nic);
                $chk_f->execute();
                $f_res = $chk_f->get_result();
                $tot_livestock = $total_neat_cattle + $total_buffaloes + $goat_tot + $swine_tot + $sheep_tot;
                if ($f_row = $f_res->fetch_assoc()) {
                    $upd_f = $mysqli->prepare("
                        UPDATE `farmers` SET
                            full_name = ?, location_address = ?, contact_no = ?,
                            farm_registration_no = ?, cattle_count = ?, buffalo_count = ?,
                            goat_count = ?, swine_count = ?, total_animal_count = ?
                        WHERE id = ?
                    ");
                    if ($upd_f) {
                        $upd_f->bind_param("ssssiiiiii", $farmer_name, $farmer_addr, $phone, $reg_no, $total_neat_cattle, $total_buffaloes, $goat_tot, $swine_tot, $tot_livestock, $f_row['id']);
                        $upd_f->execute();
                        $upd_f->close();
                    }
                } else {
                    $ins_f = $mysqli->prepare("
                        INSERT INTO `farmers` (
                            nic_no, full_name, farm_registration_no, location_address,
                            district_id, range_id, contact_no, cattle_count,
                            buffalo_count, goat_count, swine_count, total_animal_count, is_active
                        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1)
                    ");
                    if ($ins_f) {
                        $ins_f->bind_param("ssssiisiiiii", $nic, $farmer_name, $reg_no, $farmer_addr, $district_context, $range_context, $phone, $total_neat_cattle, $total_buffaloes, $goat_tot, $swine_tot, $tot_livestock);
                        $ins_f->execute();
                        $ins_f->close();
                    }
                }
                $chk_f->close();
            }
        }

        // Return JSON for AJAX requests
        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
            header('Content-Type: application/json');
            echo json_encode(['status' => $alert_status, 'message' => $alert_message]);
            exit();
        }
    }

    // DELETE RECORD
    if ($action === 'delete_renewal' && !empty($_POST['id'])) {
        $del_id = intval($_POST['id']);
        $del_stmt = $mysqli->prepare("DELETE FROM `farm_registration_renewals` WHERE `id` = ?");
        if ($del_stmt) {
            $del_stmt->bind_param("i", $del_id);
            $del_stmt->execute();
            $del_stmt->close();
            $alert_status = 'success';
            $alert_message = "Registration record deleted successfully.";
        }
        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
            header('Content-Type: application/json');
            echo json_encode(['status' => 'success', 'message' => 'Record deleted successfully']);
            exit();
        }
    }
}

// -------------------------------------------------------------------------
// 3. Dynamic Database Fetch: Retrieve Live Data for View Details & KPIs
// -------------------------------------------------------------------------
$db_records = [];
$total_kpi_cattle = 0;
$total_kpi_buffalo = 0;
$total_kpi_ruminants = 0;

$records_query = "
    SELECT * FROM `farm_registration_renewals`
    ORDER BY `date_of_registration_renewal` DESC, `id` DESC
";
$res = $mysqli->query($records_query);
if ($res) {
    while ($row = $res->fetch_assoc()) {
        $db_records[] = $row;
        $total_kpi_cattle += (int)$row['total_neat_cattle'];
        $total_kpi_buffalo += (int)$row['total_buffaloes'];
        $total_kpi_ruminants += ((int)$row['goat_total_no'] + (int)$row['sheep_total_no']);
    }
}

// Prepare records array for client-side JavaScript binding & dynamic modal rendering
$records_for_js = [];
foreach ($db_records as $r) {
    $f_data = !empty($r['fodder_data']) ? (json_decode($r['fodder_data'], true) ?: []) : [];
    if (empty($f_data)) {
        if ((float)$r['fodder_hybrid_napier'] > 0) $f_data[] = ['item' => 'Hybrid Napier', 'amount' => (float)$r['fodder_hybrid_napier'], 'other_specify' => ''];
        if ((float)$r['fodder_sorghum'] > 0) $f_data[] = ['item' => 'Sorghum', 'amount' => (float)$r['fodder_sorghum'], 'other_specify' => ''];
        if ((float)$r['fodder_maize'] > 0) $f_data[] = ['item' => 'Maize(fodder)', 'amount' => (float)$r['fodder_maize'], 'other_specify' => ''];
        if ((float)$r['fodder_other'] > 0) $f_data[] = ['item' => 'Other', 'amount' => (float)$r['fodder_other'], 'other_specify' => $r['fodder_other_specify'] ?? ''];
    }
    $records_for_js[] = [
        'id' => (int)$r['id'],
        'registration_no' => $r['registration_no'],
        'date_of_registration_renewal' => $r['date_of_registration_renewal'],
        'province' => $r['province'],
        'district' => $r['district'],
        'ds_division' => $r['ds_division'],
        'vs_division' => $r['vs_division'],
        'gn_division' => $r['gn_division'],
        'farmer_name' => $r['farmer_name'],
        'farmer_address' => $r['farmer_address'],
        'telephone_no' => $r['telephone_no'],
        'nic' => $r['nic'],
        'farm_type' => $r['farm_type'],
        'mixed_farm_type' => $r['mixed_farm_type'],
        'total_neat_cattle' => (int)$r['total_neat_cattle'],
        'total_buffaloes' => (int)$r['total_buffaloes'],
        'daily_milk_production' => (float)$r['daily_milk_production'],
        'neat_cattle_data' => !empty($r['neat_cattle_data']) ? (json_decode($r['neat_cattle_data'], true) ?: []) : [],
        'buffaloes_data' => !empty($r['buffaloes_data']) ? (json_decode($r['buffaloes_data'], true) ?: []) : [],
        'milk_data' => !empty($r['milk_data']) ? (json_decode($r['milk_data'], true) ?: []) : [],
        'fodder_hybrid_napier' => (float)$r['fodder_hybrid_napier'],
        'fodder_sorghum' => (float)$r['fodder_sorghum'],
        'fodder_maize' => (float)$r['fodder_maize'],
        'fodder_other' => (float)$r['fodder_other'],
        'fodder_other_specify' => $r['fodder_other_specify'] ?? '',
        'fodder_total_land_area' => (float)$r['fodder_total_land_area'],
        'fodder_data' => $f_data,
        'swine_total_no' => (int)$r['swine_total_no'],
        'swine_breeding_female' => (int)$r['swine_breeding_female'],
        'swine_breeding_male' => (int)$r['swine_breeding_male'],
        'swine_weaners_fattening' => (int)$r['swine_weaners_fattening'],
        'swine_pre_weaners' => (int)$r['swine_pre_weaners'],
        'swine_freq_meat' => $r['swine_freq_meat'] ?: 'Not applicable',
        'swine_freq_breeding' => $r['swine_freq_breeding'] ?: 'Not applicable',
        'goat_total_no' => (int)$r['goat_total_no'],
        'goat_breeding_female' => (int)$r['goat_breeding_female'],
        'goat_breeding_male' => (int)$r['goat_breeding_male'],
        'goat_weaners_fattening' => (int)$r['goat_weaners_fattening'],
        'goat_pre_weaners' => (int)$r['goat_pre_weaners'],
        'goat_freq_meat' => $r['goat_freq_meat'] ?: 'Not applicable',
        'goat_freq_breeding' => $r['goat_freq_breeding'] ?: 'Not applicable',
        'goat_milk_per_day' => (float)$r['goat_milk_per_day'],
        'sheep_breeding_female' => (int)$r['sheep_breeding_female'],
        'sheep_breeding_male' => (int)$r['sheep_breeding_male'],
        'sheep_for_meat' => (int)$r['sheep_for_meat'],
        'sheep_total_no' => (int)$r['sheep_total_no']
    ];
}

// -------------------------------------------------------------------------
// 4. Server-Side Prepopulation if edit_id is passed via GET
// -------------------------------------------------------------------------
$edit_rec = null;
if (isset($_GET['edit_id']) && is_numeric($_GET['edit_id'])) {
    $target_edit_id = (int)$_GET['edit_id'];
    $stmt_e = $mysqli->prepare("SELECT * FROM `farm_registration_renewals` WHERE `id` = ?");
    if ($stmt_e) {
        $stmt_e->bind_param("i", $target_edit_id);
        $stmt_e->execute();
        $edit_rec = $stmt_e->get_result()->fetch_assoc();
        $stmt_e->close();
    }
}

require_once '../../../includes/header.php';
?>

<div class="container-fluid px-2 px-md-3 py-3">

    <!-- Top Breadcrumb -->
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0 small">
                <li class="breadcrumb-item"><a href="../../../dashboard.php" class="text-decoration-none text-muted"><i class="bi bi-house-door me-1"></i>Dashboard</a></li>
                <li class="breadcrumb-item"><a href="regulatory_functions.php" class="text-decoration-none text-muted">Veterinary Module</a></li>
                <li class="breadcrumb-item active text-dark fw-semibold" aria-current="page">Livestock Farm Registration & Renewal</li>
            </ol>
        </nav>
        <div class="d-flex align-items-center gap-2">
            <span class="badge bg-light text-dark border px-3 py-2">
                <i class="bi bi-geo-alt-fill text-danger me-1"></i>Range: <strong><?= htmlspecialchars($range_name) ?></strong>
            </span>
            <span class="badge bg-light text-dark border px-3 py-2">
                <i class="bi bi-calendar3 text-primary me-1"></i>Year: <strong><?= date('Y') ?></strong>
            </span>
        </div>
    </div>

    <!-- Alert Message Banner -->
    <?php if ($alert_message): ?>
    <div class="alert alert-<?= $alert_status ?> alert-dismissible fade show shadow-xs rounded-3 mb-3" role="alert">
        <i class="bi bi-check-circle-fill me-2"></i><?= htmlspecialchars($alert_message) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    <?php endif; ?>

    <!-- Header Hero Banner with Dynamic Live KPIs -->
    <div class="branding-hero-card p-4 mb-4">
        <div class="row align-items-center g-3">
            <div class="col-lg-7">
                <div class="d-flex align-items-center gap-2 mb-2">
                    <span class="badge bg-warning text-dark fw-bold px-2 py-1"><i class="bi bi-shield-check me-1"></i>DAPH Database Bound</span>
                    <span class="badge bg-white bg-opacity-25 text-white">Livestock Farm Registration Renewal</span>
                </div>
                <h1 class="h3 fw-bold mb-2">Livestock Farm Registration Renewal & Animal Branding</h1>
                <p class="mb-0 text-white-50 small" style="max-width: 620px;">
                    Department of Animal Production and Health official registry for farm renewals, livestock census demographics, daily milk production, and fodder land area in <strong><?= htmlspecialchars($range_name) ?></strong>.
                </p>
            </div>
            <div class="col-lg-5">
                <div class="d-flex flex-wrap justify-content-lg-end gap-2">
                    <div class="stat-pill text-center">
                        <span class="d-block small text-white-50">Registered Farms</span>
                        <strong id="kpiTotalFarms"><?= count($db_records) ?></strong>
                    </div>
                    <div class="stat-pill text-center">
                        <span class="d-block small text-white-50">Neat Cattle</span>
                        <strong id="kpiTotalCattle"><?= $total_kpi_cattle ?></strong>
                    </div>
                    <div class="stat-pill text-center">
                        <span class="d-block small text-white-50">Buffaloes</span>
                        <strong id="kpiTotalBuffalo"><?= $total_kpi_buffalo ?></strong>
                    </div>
                    <div class="stat-pill text-center">
                        <span class="d-block small text-white-50">Goat / Sheep</span>
                        <strong id="kpiTotalRuminants"><?= $total_kpi_ruminants ?></strong>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Navigation View Switcher (Form vs View Added Details) -->
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
        <ul class="nav nav-pills main-view-tabs gap-2" id="mainLivestockViewTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link <?= empty($_GET['view_records']) ? 'active' : '' ?>" id="view-form-tab" data-bs-toggle="pill" data-bs-target="#viewFormPanel" type="button" role="tab" aria-selected="<?= empty($_GET['view_records']) ? 'true' : 'false' ?>">
                    <i class="bi bi-pencil-square me-2"></i><span id="formTabTitle">Livestock Registration & Renewal Form</span>
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link <?= !empty($_GET['view_records']) ? 'active' : '' ?>" id="view-registry-tab" data-bs-toggle="pill" data-bs-target="#viewRegistryPanel" type="button" role="tab" aria-selected="<?= !empty($_GET['view_records']) ? 'true' : 'false' ?>">
                    <i class="bi bi-table me-2"></i>View Added Details
                    <span class="badge bg-secondary ms-2" id="recordsCountBadge"><?= count($db_records) ?></span>
                </button>
            </li>
        </ul>

        <div class="d-flex align-items-center gap-2">
            <div class="btn-group btn-group-sm" role="group" aria-label="Layout Mode">
                <button type="button" class="btn btn-outline-secondary active" id="btnLayoutTabs" title="Vertical Tabbed View">
                    <i class="bi bi-layout-sidebar-inset me-1"></i>Vertical Tabs
                </button>
                <button type="button" class="btn btn-outline-secondary" id="btnLayoutAccordion" title="Collapsible Accordion View">
                    <i class="bi bi-view-stacked me-1"></i>Accordion
                </button>
            </div>
            <button class="btn btn-sm btn-outline-primary" onclick="window.print();">
                <i class="bi bi-printer me-1"></i>Print Form
            </button>
        </div>
    </div>

    <!-- Tab Contents Container -->
    <div class="tab-content" id="mainLivestockTabContent">

        <!-- ============================================================== -->
        <!-- VIEW 1: REGISTRATION & RENEWAL FORM (VERTICAL TABS / ACCORDION)-->
        <!-- ============================================================== -->
        <div class="tab-pane fade <?= empty($_GET['view_records']) ? 'show active' : '' ?>" id="viewFormPanel" role="tabpanel" aria-labelledby="view-form-tab">

            <!-- Progress Tracker Bar (8 Active Sections) -->
            <div class="category-progress-tracker" id="categoryProgressTracker">
                <div class="category-progress-segment active" data-index="0" title="1. General Information"></div>
                <div class="category-progress-segment" data-index="1" title="2. Neat Cattle"></div>
                <div class="category-progress-segment" data-index="2" title="3. Buffaloes"></div>
                <div class="category-progress-segment" data-index="3" title="4. Milk Production and Sale"></div>
                <div class="category-progress-segment" data-index="4" title="5. Fodder / pasture cultivations (Land area) in Perch"></div>
                <div class="category-progress-segment" data-index="5" title="6. Swine"></div>
                <div class="category-progress-segment" data-index="6" title="7. Goat"></div>
                <div class="category-progress-segment" data-index="7" title="8. Sheep"></div>
            </div>

            <!-- Master Form Bound to Database -->
            <form id="farmRenewalMasterForm" method="POST" action="animal_branding.php" onsubmit="handleFormSubmitAjax(event);">
                <input type="hidden" name="action" value="save_renewal">
                <input type="hidden" name="range_id" value="<?= htmlspecialchars($range_id ?? '') ?>">
                <input type="hidden" name="editing_record_id" id="editingRecordId" value="<?= htmlspecialchars($edit_rec['id'] ?? '') ?>">

                <!-- Status Banner when Editing Existing Database Record -->
                <div id="editingStatusBanner" class="alert alert-warning d-flex justify-content-between align-items-center mb-3 <?= empty($edit_rec) ? 'd-none' : '' ?>">
                    <div>
                        <i class="bi bi-pencil-square me-2"></i>
                        Currently Editing Database Record: <strong id="editingRecordNoDisplay"><?= htmlspecialchars($edit_rec['registration_no'] ?? '') ?></strong>
                        (Farmer: <span id="editingFarmerDisplay"><?= htmlspecialchars($edit_rec['farmer_name'] ?? '') ?></span>)
                    </div>
                    <button type="button" class="btn btn-sm btn-outline-dark" onclick="resetMasterForm();">
                        <i class="bi bi-x-circle me-1"></i>Cancel Edit / New Form
                    </button>
                </div>

                <!-- VERTICAL TABBED INTERFACE LAYOUT -->
                <div id="verticalTabsLayoutContainer" class="row g-4">

                    <!-- Left Column: Category Navigation Tabs (Col-lg-3 Col-md-4) -->
                    <div class="col-lg-3 col-md-4">
                        <div class="v-tabs-nav">
                            <div class="v-tabs-header d-flex justify-content-between align-items-center">
                                <h6>Form Categories</h6>
                                <span class="badge bg-secondary-subtle text-secondary small">8 Sections</span>
                            </div>

                            <div class="nav flex-column" id="categoryVerticalTabs" role="tablist" aria-orientation="vertical">
                                <!-- 1. General Information -->
                                <button type="button" class="v-tab-btn active" data-target-pane="pane-general" data-category-index="0" role="tab" aria-selected="true">
                                    <span class="tab-num">1</span>
                                    <i class="bi bi-info-circle-fill tab-icon"></i>
                                    <span class="tab-title">1. General Information</span>
                                    <i class="bi bi-chevron-right tab-chevron"></i>
                                </button>

                                <!-- 2. Neat Cattle -->
                                <button type="button" class="v-tab-btn" data-target-pane="pane-cattle" data-category-index="1" role="tab" aria-selected="false">
                                    <span class="tab-num">2</span>
                                    <i class="bi bi-shield-check tab-icon"></i>
                                    <span class="tab-title">2. Neat Cattle</span>
                                    <i class="bi bi-chevron-right tab-chevron"></i>
                                </button>

                                <!-- 3. Buffaloes -->
                                <button type="button" class="v-tab-btn" data-target-pane="pane-buffaloes" data-category-index="2" role="tab" aria-selected="false">
                                    <span class="tab-num">3</span>
                                    <i class="bi bi-record-circle-fill tab-icon"></i>
                                    <span class="tab-title">3. Buffaloes</span>
                                    <i class="bi bi-chevron-right tab-chevron"></i>
                                </button>

                                <!-- 4. Milk Production and Sale -->
                                <button type="button" class="v-tab-btn" data-target-pane="pane-milk" data-category-index="3" role="tab" aria-selected="false">
                                    <span class="tab-num">4</span>
                                    <i class="bi bi-cup-hot-fill tab-icon"></i>
                                    <span class="tab-title">4. Milk Production & Sale</span>
                                    <i class="bi bi-chevron-right tab-chevron"></i>
                                </button>

                                <!-- 5. Fodder / pasture cultivations (Land area) in Perch -->
                                <button type="button" class="v-tab-btn" data-target-pane="pane-fodder" data-category-index="4" role="tab" aria-selected="false">
                                    <span class="tab-num">5</span>
                                    <i class="bi bi-tree-fill tab-icon"></i>
                                    <span class="tab-title">5. Fodder / Pasture in Perch</span>
                                    <i class="bi bi-chevron-right tab-chevron"></i>
                                </button>

                                <!-- 6. Swine -->
                                <button type="button" class="v-tab-btn" data-target-pane="pane-swine" data-category-index="5" role="tab" aria-selected="false">
                                    <span class="tab-num">6</span>
                                    <i class="bi bi-bookmark-star-fill tab-icon"></i>
                                    <span class="tab-title">6. Swine</span>
                                    <i class="bi bi-chevron-right tab-chevron"></i>
                                </button>

                                <!-- 7. Goat -->
                                <button type="button" class="v-tab-btn" data-target-pane="pane-goat" data-category-index="6" role="tab" aria-selected="false">
                                    <span class="tab-num">7</span>
                                    <i class="bi bi-patch-check-fill tab-icon"></i>
                                    <span class="tab-title">7. Goat</span>
                                    <i class="bi bi-chevron-right tab-chevron"></i>
                                </button>

                                <!-- 8. Sheep -->
                                <button type="button" class="v-tab-btn" data-target-pane="pane-sheep" data-category-index="7" role="tab" aria-selected="false">
                                    <span class="tab-num">8</span>
                                    <i class="bi bi-circle-square tab-icon"></i>
                                    <span class="tab-title">8. Sheep</span>
                                    <i class="bi bi-chevron-right tab-chevron"></i>
                                </button>
                            </div>

                            <!-- Actions Box -->
                            <div class="p-3 mt-3 bg-light rounded-3 border text-center">
                                <span class="small text-muted d-block mb-2">Form Actions</span>
                                <div class="d-grid gap-2">
                                    <button type="button" class="btn btn-sm btn-outline-secondary" onclick="resetMasterForm();">
                                        <i class="bi bi-arrow-counterclockwise me-1"></i>Reset / New Form
                                    </button>
                                    <button type="submit" id="btnSubmitForm" class="btn btn-sm btn-danger fw-bold" style="background-color: var(--daph-maroon); border-color: var(--daph-maroon);">
                                        <i class="bi bi-check-circle me-1"></i><span id="btnSubmitText"><?= empty($edit_rec) ? 'Save to Database' : 'Update Record in Database' ?></span>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Right Column: Active Category Content Panels (Col-lg-9 Col-md-8) -->
                    <div class="col-lg-9 col-md-8">
                        <div class="category-content-card">

                            <!-- ============================================================== -->
                            <!-- 1. GENERAL INFORMATION                                         -->
                            <!-- ============================================================== -->
                            <div class="category-pane" id="pane-general" style="display: block;">
                                <div class="category-panel-header d-flex flex-wrap justify-content-between align-items-center gap-2">
                                    <div>
                                        <span class="category-badge-step mb-1">Section 1 of 8</span>
                                        <h4 class="h5 fw-bold text-dark mb-1">
                                            <i class="bi bi-info-circle-fill text-danger me-2"></i>1. General Information
                                        </h4>
                                        <p class="text-muted small mb-0">Record registration renewal date, geographical jurisdiction, and farmer credentials.</p>
                                    </div>
                                    <span class="badge bg-danger-subtle text-danger border border-danger-subtle px-3 py-2">
                                        Mandatory General Fields
                                    </span>
                                </div>

                                <div class="p-4">
                                    <div class="row g-4">
                                        <!-- Left Column -->
                                        <div class="col-md-6 border-end-md">
                                            <h6 class="small fw-bold text-uppercase text-secondary mb-3 pb-2 border-bottom">
                                                <i class="bi bi-geo-alt-fill text-danger me-1"></i>Location & Division Details
                                            </h6>
                                            <div class="mb-3">
                                                <label class="form-label">Date of Registration Renewal <span class="text-danger">*</span></label>
                                                <div class="input-group input-group-sm">
                                                    <span class="input-group-text bg-white"><i class="bi bi-calendar-event"></i></span>
                                                    <input type="date" name="general[date_of_registration_renewal]" id="gen_date_renewal" class="form-control" value="<?= htmlspecialchars($edit_rec['date_of_registration_renewal'] ?? date('Y-m-d')) ?>" required>
                                                </div>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label">Province <span class="text-danger">*</span></label>
                                                <input type="text" name="general[province]" id="gen_province" class="form-control form-control-sm bg-light" value="<?= htmlspecialchars($edit_rec['province'] ?? $province_name) ?>" required>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label">District <span class="text-danger">*</span></label>
                                                <input type="text" name="general[district]" id="gen_district" class="form-control form-control-sm bg-light" value="<?= htmlspecialchars($edit_rec['district'] ?? $district_name) ?>" required>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label">DS Division <span class="text-danger">*</span></label>
                                                <input type="text" name="general[ds_division]" id="gen_ds_division" class="form-control form-control-sm" placeholder="e.g. Town and Gravets" value="<?= htmlspecialchars($edit_rec['ds_division'] ?? '') ?>" required>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label">VS Division <span class="text-danger">*</span></label>
                                                <input type="text" name="general[vs_division]" id="gen_vs_division" class="form-control form-control-sm" value="<?= htmlspecialchars($edit_rec['vs_division'] ?? $range_name) ?>" required>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label">GN Division <span class="text-danger">*</span></label>
                                                <input type="text" name="general[gn_division]" id="gen_gn_division" class="form-control form-control-sm" placeholder="e.g. 241B Orr's Hill" value="<?= htmlspecialchars($edit_rec['gn_division'] ?? '') ?>" required>
                                            </div>
                                        </div>

                                        <!-- Right Column -->
                                        <div class="col-md-6">
                                            <h6 class="small fw-bold text-uppercase text-secondary mb-3 pb-2 border-bottom">
                                                <i class="bi bi-person-badge-fill text-primary me-1"></i>Farmer & Farm Profile
                                            </h6>
                                            <div class="mb-3">
                                                <label class="form-label">Name of the Farmer <span class="text-danger">*</span></label>
                                                <div class="input-group input-group-sm">
                                                    <span class="input-group-text bg-white"><i class="bi bi-person"></i></span>
                                                    <input type="text" name="general[farmer_name]" id="gen_farmer_name" class="form-control" placeholder="Full Name of Farmer" value="<?= htmlspecialchars($edit_rec['farmer_name'] ?? '') ?>" required>
                                                </div>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label">Address of the Farmer <span class="text-danger">*</span></label>
                                                <textarea name="general[farmer_address]" id="gen_farmer_address" class="form-control form-control-sm" rows="2" placeholder="Permanent Residential / Holding Address" required><?= htmlspecialchars($edit_rec['farmer_address'] ?? '') ?></textarea>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label">Registration No (9 Digit no.) <span class="text-danger">*</span></label>
                                                <div class="input-group input-group-sm">
                                                    <span class="input-group-text font-monospace bg-white">#</span>
                                                    <input type="text" name="general[registration_no]" id="gen_registration_no" class="form-control font-monospace fw-bold" placeholder="e.g. 104829375" pattern="\d{9}" maxlength="9" value="<?= htmlspecialchars($edit_rec['registration_no'] ?? '') ?>" required>
                                                </div>
                                                <small class="text-muted" style="font-size: 0.75rem;">Must be exactly 9 numeric digits.</small>
                                            </div>
                                            <div class="row g-2 mb-3">
                                                <div class="col-6">
                                                    <label class="form-label">Telephone No <span class="text-danger">*</span></label>
                                                    <input type="tel" name="general[telephone_no]" id="gen_telephone_no" class="form-control form-control-sm" placeholder="e.g. 0771234567" value="<?= htmlspecialchars($edit_rec['telephone_no'] ?? '') ?>" required>
                                                </div>
                                                <div class="col-6">
                                                    <label class="form-label">NIC <span class="text-danger">*</span></label>
                                                    <input type="text" name="general[nic]" id="gen_nic" class="form-control form-control-sm" placeholder="National Identity Card" value="<?= htmlspecialchars($edit_rec['nic'] ?? '') ?>" required>
                                                </div>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label">Type of farm <span class="text-danger">*</span></label>
                                                <?php $curr_ft = $edit_rec['farm_type'] ?? ''; ?>
                                                <select name="general[farm_type]" id="gen_farm_type" class="form-select form-select-sm" required>
                                                    <option value="" disabled <?= empty($curr_ft) ? 'selected' : '' ?>>-- Select Type of Farm --</option>
                                                    <option value="Dairy Cattle Farm" <?= $curr_ft === 'Dairy Cattle Farm' ? 'selected' : '' ?>>Dairy Cattle Farm</option>
                                                    <option value="Buffalo Dairy Farm" <?= $curr_ft === 'Buffalo Dairy Farm' ? 'selected' : '' ?>>Buffalo Dairy Farm</option>
                                                    <option value="Dual (Cattle & Buffalo)" <?= $curr_ft === 'Dual (Cattle & Buffalo)' ? 'selected' : '' ?>>Dual (Cattle & Buffalo)</option>
                                                    <option value="Goat / Sheep Ruminant Farm" <?= $curr_ft === 'Goat / Sheep Ruminant Farm' ? 'selected' : '' ?>>Goat / Sheep Ruminant Farm</option>
                                                    <option value="Swine / Piggery Operation" <?= $curr_ft === 'Swine / Piggery Operation' ? 'selected' : '' ?>>Swine / Piggery Operation</option>
                                                    <option value="Poultry & Mixed Livestock" <?= $curr_ft === 'Poultry & Mixed Livestock' ? 'selected' : '' ?>>Poultry & Mixed Livestock</option>
                                                    <option value="Commercial Breeding Farm" <?= $curr_ft === 'Commercial Breeding Farm' ? 'selected' : '' ?>>Commercial Breeding Farm</option>
                                                    <option value="Smallholder Backyard Farm" <?= $curr_ft === 'Smallholder Backyard Farm' ? 'selected' : '' ?>>Smallholder Backyard Farm</option>
                                                </select>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label">Mixed Type of farm <span class="text-danger">*</span></label>
                                                <?php $curr_mft = $edit_rec['mixed_farm_type'] ?? ''; ?>
                                                <select name="general[mixed_farm_type]" id="gen_mixed_farm_type" class="form-select form-select-sm" required>
                                                    <option value="" disabled <?= empty($curr_mft) ? 'selected' : '' ?>>-- Select Mixed Type of Farm --</option>
                                                    <option value="Cattle + Buffaloes" <?= $curr_mft === 'Cattle + Buffaloes' ? 'selected' : '' ?>>Cattle + Buffaloes</option>
                                                    <option value="Cattle + Goat" <?= $curr_mft === 'Cattle + Goat' ? 'selected' : '' ?>>Cattle + Goat</option>
                                                    <option value="Cattle + Crop / Paddy Cultivation" <?= $curr_mft === 'Cattle + Crop / Paddy Cultivation' ? 'selected' : '' ?>>Cattle + Crop / Paddy Cultivation</option>
                                                    <option value="Cattle + Pasture / Fodder Production" <?= $curr_mft === 'Cattle + Pasture / Fodder Production' ? 'selected' : '' ?>>Cattle + Pasture / Fodder Production</option>
                                                    <option value="Dairy + Poultry" <?= $curr_mft === 'Dairy + Poultry' ? 'selected' : '' ?>>Dairy + Poultry</option>
                                                    <option value="Buffalo + Wetland Paddy" <?= $curr_mft === 'Buffalo + Wetland Paddy' ? 'selected' : '' ?>>Buffalo + Wetland Paddy</option>
                                                    <option value="Swine + Crop Agriculture" <?= $curr_mft === 'Swine + Crop Agriculture' ? 'selected' : '' ?>>Swine + Crop Agriculture</option>
                                                    <option value="Monoculture / Single Species Only" <?= $curr_mft === 'Monoculture / Single Species Only' ? 'selected' : '' ?>>Monoculture / Single Species Only</option>
                                                    <option value="Other Mixed Farming" <?= $curr_mft === 'Other Mixed Farming' ? 'selected' : '' ?>>Other Mixed Farming</option>
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="panel-nav-footer">
                                    <div></div>
                                    <button type="button" class="btn btn-dark btn-sm px-4 fw-semibold btn-next-category" data-next-pane="pane-cattle" data-next-index="1">
                                        Next: 2. Neat Cattle <i class="bi bi-arrow-right ms-1"></i>
                                    </button>
                                </div>
                            </div>

                            <!-- ============================================================== -->
                            <!-- 2. NEAT CATTLE (GRID: European, Indian, Local)                 -->
                            <!-- ============================================================== -->
                            <div class="category-pane" id="pane-cattle" style="display: none;">
                                <div class="category-panel-header d-flex flex-wrap justify-content-between align-items-center gap-2">
                                    <div>
                                        <span class="category-badge-step mb-1">Section 2 of 8</span>
                                        <h4 class="h5 fw-bold text-dark mb-1">
                                            <i class="bi bi-shield-check text-primary me-2"></i>2. Neat Cattle
                                        </h4>
                                        <p class="text-muted small mb-0">Demographics grid by breed origin (European, Indian, Local) and livestock age/milking class.</p>
                                    </div>
                                    <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-3 py-2">
                                        Grid: European | Indian | Local
                                    </span>
                                </div>

                                <div class="p-4">
                                    <div class="table-responsive">
                                        <table class="table daph-entry-grid align-middle" id="neatCattleGridTable">
                                            <thead>
                                                <tr>
                                                    <th style="width: 34%;">Livestock Category / Class</th>
                                                    <th class="text-center" style="width: 22%;">European</th>
                                                    <th class="text-center" style="width: 22%;">Indian</th>
                                                    <th class="text-center" style="width: 22%;">Local</th>
                                                    <th class="text-center bg-light" style="width: 15%;">Row Total</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php
                                                $neat_rows = [
                                                    'cows_milch' => 'Cows (Milch)',
                                                    'unproductive_cows' => 'Unproductive Cows',
                                                    'heifers' => 'Heifers',
                                                    'female_under_1' => 'Female Calf',
                                                    'bulls' => 'Bulls',
                                                    'male_under_1' => 'Male Calf'
                                                ];
                                                $edit_cattle = !empty($edit_rec['neat_cattle_data']) ? json_decode($edit_rec['neat_cattle_data'], true) : [];
                                                foreach ($neat_rows as $rKey => $rLabel):
                                                ?>
                                                <tr>
                                                    <td class="fw-semibold"><?= $rLabel ?></td>
                                                    <td><input type="number" min="0" value="<?= intval($edit_cattle[$rKey]['european'] ?? 0) ?>" name="cattle[<?= $rKey ?>][european]" class="daph-grid-input cattle-calc-input" data-col="euro" data-row="<?= $rKey ?>"></td>
                                                    <td><input type="number" min="0" value="<?= intval($edit_cattle[$rKey]['indian'] ?? 0) ?>" name="cattle[<?= $rKey ?>][indian]" class="daph-grid-input cattle-calc-input" data-col="indian" data-row="<?= $rKey ?>"></td>
                                                    <td><input type="number" min="0" value="<?= intval($edit_cattle[$rKey]['local'] ?? 0) ?>" name="cattle[<?= $rKey ?>][local]" class="daph-grid-input cattle-calc-input" data-col="local" data-row="<?= $rKey ?>"></td>
                                                    <td class="daph-grid-total-cell" id="row_tot_cattle_<?= $rKey ?>">0</td>
                                                </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                            <tfoot>
                                                <tr>
                                                    <td class="fw-bold text-dark">Total Neat Cattle</td>
                                                    <td class="text-center font-monospace" id="col_tot_cattle_euro">0</td>
                                                    <td class="text-center font-monospace" id="col_tot_cattle_indian">0</td>
                                                    <td class="text-center font-monospace" id="col_tot_cattle_local">0</td>
                                                    <td class="text-center font-monospace text-primary fs-6" id="grand_tot_neat_cattle"><?= intval($edit_rec['total_neat_cattle'] ?? 0) ?></td>
                                                </tr>
                                            </tfoot>
                                        </table>
                                    </div>
                                </div>

                                <div class="panel-nav-footer">
                                    <button type="button" class="btn btn-outline-secondary btn-sm btn-prev-category" data-prev-pane="pane-general" data-prev-index="0">
                                        <i class="bi bi-arrow-left me-1"></i>Previous: 1. General Information
                                    </button>
                                    <button type="button" class="btn btn-dark btn-sm px-4 fw-semibold btn-next-category" data-next-pane="pane-buffaloes" data-next-index="2">
                                        Next: 3. Buffaloes <i class="bi bi-arrow-right ms-1"></i>
                                    </button>
                                </div>
                            </div>

                            <!-- ============================================================== -->
                            <!-- 3. BUFFALOES                                                  -->
                            <!-- ============================================================== -->
                            <div class="category-pane" id="pane-buffaloes" style="display: none;">
                                <div class="category-panel-header d-flex flex-wrap justify-content-between align-items-center gap-2">
                                    <div>
                                        <span class="category-badge-step mb-1">Section 3 of 8</span>
                                        <h4 class="h5 fw-bold text-dark mb-1">
                                            <i class="bi bi-record-circle-fill text-warning me-2"></i>3. Buffaloes
                                        </h4>
                                        <p class="text-muted small mb-0">Demographics grid for water buffaloes across Niliravi, Murah, and Cross breed.</p>
                                    </div>
                                    <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle px-3 py-2">
                                        Grid: Niliravi | Murah | Cross breed
                                    </span>
                                </div>

                                <div class="p-4">
                                    <div class="table-responsive">
                                        <table class="table daph-entry-grid align-middle" id="buffaloGridTable">
                                            <thead>
                                                <tr>
                                                    <th style="width: 34%;">Livestock Category / Class</th>
                                                    <th class="text-center" style="width: 18%;">Niliravi</th>
                                                    <th class="text-center" style="width: 18%;">Murah</th>
                                                    <th class="text-center" style="width: 18%;">Cross breed</th>
                                                    <th class="text-center bg-light" style="width: 12%;">Row Total</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php
                                                $buf_rows = [
                                                    'cows_milch' => 'Cows (Milch)',
                                                    'unproductive_cows' => 'Unproductive Cows',
                                                    'heifers' => 'Heifers',
                                                    'female_under_1' => 'less than 1 year Female',
                                                    'bulls' => 'Bulls',
                                                    'male_under_1' => 'less than 1 year Male'
                                                ];
                                                $edit_buf = !empty($edit_rec['buffaloes_data']) ? json_decode($edit_rec['buffaloes_data'], true) : [];
                                                foreach ($buf_rows as $rKey => $rLabel):
                                                ?>
                                                <tr>
                                                    <td class="fw-semibold"><?= $rLabel ?></td>
                                                    <td><input type="number" min="0" value="<?= intval($edit_buf[$rKey]['niliravi'] ?? ($edit_buf[$rKey]['indian'] ?? 0)) ?>" name="buffalo[<?= $rKey ?>][niliravi]" class="daph-grid-input buffalo-calc-input" data-col="niliravi" data-row="<?= $rKey ?>"></td>
                                                    <td><input type="number" min="0" value="<?= intval($edit_buf[$rKey]['murah'] ?? 0) ?>" name="buffalo[<?= $rKey ?>][murah]" class="daph-grid-input buffalo-calc-input" data-col="murah" data-row="<?= $rKey ?>"></td>
                                                    <td><input type="number" min="0" value="<?= intval($edit_buf[$rKey]['cross_breed'] ?? ($edit_buf[$rKey]['local'] ?? 0)) ?>" name="buffalo[<?= $rKey ?>][cross_breed]" class="daph-grid-input buffalo-calc-input" data-col="cross_breed" data-row="<?= $rKey ?>"></td>
                                                    <td class="daph-grid-total-cell" id="row_tot_buf_<?= $rKey ?>">0</td>
                                                </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                            <tfoot>
                                                <tr>
                                                    <td class="fw-bold text-dark">Total Buffaloes</td>
                                                    <td class="text-center font-monospace" id="col_tot_buf_niliravi">0</td>
                                                    <td class="text-center font-monospace" id="col_tot_buf_murah">0</td>
                                                    <td class="text-center font-monospace" id="col_tot_buf_cross_breed">0</td>
                                                    <td class="text-center font-monospace text-warning-emphasis fs-6" id="grand_tot_buffaloes"><?= intval($edit_rec['total_buffaloes'] ?? 0) ?></td>
                                                </tr>
                                            </tfoot>
                                        </table>
                                    </div>
                                </div>

                                <div class="panel-nav-footer">
                                    <button type="button" class="btn btn-outline-secondary btn-sm btn-prev-category" data-prev-pane="pane-cattle" data-prev-index="1">
                                        <i class="bi bi-arrow-left me-1"></i>Previous: 2. Neat Cattle
                                    </button>
                                    <button type="button" class="btn btn-dark btn-sm px-4 fw-semibold btn-next-category" data-next-pane="pane-milk" data-next-index="3">
                                        Next: 4. Milk Production & Sale <i class="bi bi-arrow-right ms-1"></i>
                                    </button>
                                </div>
                            </div>

                            <!-- ============================================================== -->
                            <!-- 4. MILK PRODUCTION AND SALE (GRID: Cow, Buffalo)               -->
                            <!-- ============================================================== -->
                            <div class="category-pane" id="pane-milk" style="display: none;">
                                <div class="category-panel-header d-flex flex-wrap justify-content-between align-items-center gap-2">
                                    <div>
                                        <span class="category-badge-step mb-1">Section 4 of 8</span>
                                        <h4 class="h5 fw-bold text-dark mb-1">
                                            <i class="bi bi-cup-hot-fill text-info me-2"></i>4. Milk Production and Sale
                                        </h4>
                                        <p class="text-muted small mb-0">Record daily milk production, home consumption, farm processing, and commercial sales.</p>
                                    </div>
                                    <span class="badge bg-info-subtle text-info-emphasis border border-info-subtle px-3 py-2">
                                        Grid: Cow | Buffalo (L/day)
                                    </span>
                                </div>

                                <div class="p-4">
                                    <div class="table-responsive">
                                        <?php
                                        $edit_milk = !empty($edit_rec['milk_data']) ? json_decode($edit_rec['milk_data'], true) : [];
                                        ?>
                                        <table class="table daph-entry-grid align-middle" id="milkGridTable">
                                            <thead>
                                                <tr>
                                                    <th style="width: 40%;">Milk Production & Utilization Category</th>
                                                    <th class="text-center" style="width: 25%;">Cow (L)/day</th>
                                                    <th class="text-center" style="width: 25%;">Buffalo (L)/day</th>
                                                    <th class="text-center bg-light" style="width: 15%;">Total (L)/day</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <tr>
                                                    <td class="fw-semibold">Total Production (L)/day</td>
                                                    <td><input type="number" step="0.1" min="0" value="<?= floatval($edit_milk['total_production']['cow'] ?? 0.0) ?>" name="milk[total_production][cow]" class="daph-grid-input milk-calc-input" data-col="cow" data-row="prod"></td>
                                                    <td><input type="number" step="0.1" min="0" value="<?= floatval($edit_milk['total_production']['buffalo'] ?? 0.0) ?>" name="milk[total_production][buffalo]" class="daph-grid-input milk-calc-input" data-col="buf" data-row="prod"></td>
                                                    <td class="daph-grid-total-cell font-monospace text-primary fw-bold" id="row_tot_milk_prod">0.0</td>
                                                </tr>
                                                <tr>
                                                    <td class="fw-semibold">Household Consumption (L)/day</td>
                                                    <td><input type="number" step="0.1" min="0" value="<?= floatval($edit_milk['household_consumption']['cow'] ?? 0.0) ?>" name="milk[household_consumption][cow]" class="daph-grid-input milk-calc-input" data-col="cow" data-row="home"></td>
                                                    <td><input type="number" step="0.1" min="0" value="<?= floatval($edit_milk['household_consumption']['buffalo'] ?? 0.0) ?>" name="milk[household_consumption][buffalo]" class="daph-grid-input milk-calc-input" data-col="buf" data-row="home"></td>
                                                    <td class="daph-grid-total-cell font-monospace" id="row_tot_milk_home">0.0</td>
                                                </tr>
                                                <tr>
                                                    <td class="fw-semibold">Used for Processing (L)/day</td>
                                                    <td><input type="number" step="0.1" min="0" value="<?= floatval($edit_milk['used_for_processing']['cow'] ?? 0.0) ?>" name="milk[used_for_processing][cow]" class="daph-grid-input milk-calc-input" data-col="cow" data-row="proc"></td>
                                                    <td><input type="number" step="0.1" min="0" value="<?= floatval($edit_milk['used_for_processing']['buffalo'] ?? 0.0) ?>" name="milk[used_for_processing][buffalo]" class="daph-grid-input milk-calc-input" data-col="buf" data-row="proc"></td>
                                                    <td class="daph-grid-total-cell font-monospace" id="row_tot_milk_proc">0.0</td>
                                                </tr>
                                                <tr>
                                                    <td class="fw-semibold">Sales (L)/day</td>
                                                    <td><input type="number" step="0.1" min="0" value="<?= floatval($edit_milk['sales']['cow'] ?? 0.0) ?>" name="milk[sales][cow]" class="daph-grid-input milk-calc-input" data-col="cow" data-row="sales"></td>
                                                    <td><input type="number" step="0.1" min="0" value="<?= floatval($edit_milk['sales']['buffalo'] ?? 0.0) ?>" name="milk[sales][buffalo]" class="daph-grid-input milk-calc-input" data-col="buf" data-row="sales"></td>
                                                    <td class="daph-grid-total-cell font-monospace text-success fw-bold" id="row_tot_milk_sales">0.0</td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>

                                <div class="panel-nav-footer">
                                    <button type="button" class="btn btn-outline-secondary btn-sm btn-prev-category" data-prev-pane="pane-buffaloes" data-prev-index="2">
                                        <i class="bi bi-arrow-left me-1"></i>Previous: 3. Buffaloes
                                    </button>
                                    <button type="button" class="btn btn-dark btn-sm px-4 fw-semibold btn-next-category" data-next-pane="pane-fodder" data-next-index="4">
                                        Next: 5. Fodder / Pasture <i class="bi bi-arrow-right ms-1"></i>
                                    </button>
                                </div>
                            </div>

                            <!-- ============================================================== -->
                            <!-- ============================================================== -->
                            <!-- 5. FODDER / PASTURE CULTIVATIONS (DYNAMIC 1-TO-MANY ROWS)       -->
                            <!-- ============================================================== -->
                            <div class="category-pane" id="pane-fodder" style="display: none;">
                                <div class="category-panel-header d-flex flex-wrap justify-content-between align-items-center gap-2">
                                    <div>
                                        <span class="category-badge-step mb-1">Section 5 of 8</span>
                                        <h4 class="h5 fw-bold text-dark mb-1">
                                            <i class="bi bi-tree-fill text-success me-2"></i>5. Fodder / pasture cultivations (Land area) in Perch
                                        </h4>
                                        <p class="text-muted small mb-0">Record land extent in Perches (160 Perch = 1 Acre). Add multiple crops with custom specifications.</p>
                                    </div>
                                    <button type="button" class="btn btn-sm btn-success fw-semibold shadow-sm" onclick="addFodderRow();">
                                        <i class="bi bi-plus-circle me-1"></i>Add Crop Row
                                    </button>
                                </div>

                                <div class="p-4">
                                    <?php
                                    $initial_fodder_items = [];
                                    if (!empty($edit_rec['fodder_data'])) {
                                        $initial_fodder_items = json_decode($edit_rec['fodder_data'], true) ?: [];
                                    }
                                    if (empty($initial_fodder_items) && !empty($edit_rec)) {
                                        if ((float)($edit_rec['fodder_hybrid_napier'] ?? 0) > 0) $initial_fodder_items[] = ['item' => 'Hybrid Napier', 'amount' => (float)$edit_rec['fodder_hybrid_napier'], 'other_specify' => ''];
                                        if ((float)($edit_rec['fodder_sorghum'] ?? 0) > 0) $initial_fodder_items[] = ['item' => 'Sorghum', 'amount' => (float)$edit_rec['fodder_sorghum'], 'other_specify' => ''];
                                        if ((float)($edit_rec['fodder_maize'] ?? 0) > 0) $initial_fodder_items[] = ['item' => 'Maize(fodder)', 'amount' => (float)$edit_rec['fodder_maize'], 'other_specify' => ''];
                                        if ((float)($edit_rec['fodder_other'] ?? 0) > 0) $initial_fodder_items[] = ['item' => 'Other', 'amount' => (float)$edit_rec['fodder_other'], 'other_specify' => $edit_rec['fodder_other_specify'] ?? ''];
                                    }
                                    if (empty($initial_fodder_items)) {
                                        $initial_fodder_items[] = ['item' => '', 'amount' => '', 'other_specify' => ''];
                                    }
                                    ?>
                                    <div class="table-responsive mb-3">
                                        <table class="table table-bordered align-middle mb-0" id="fodderItemsTable">
                                            <thead class="table-light small text-uppercase">
                                                <tr>
                                                    <th style="width: 32%;">Item / Crop Type <span class="text-danger">*</span></th>
                                                    <th style="width: 36%;">Specify Other Type <small class="text-muted fw-normal">(If "Other")</small></th>
                                                    <th style="width: 22%;">Amount (in Perch) <span class="text-danger">*</span></th>
                                                    <th style="width: 10%;" class="text-center">Action</th>
                                                </tr>
                                            </thead>
                                            <tbody id="fodderItemsTableBody">
                                                <?php foreach ($initial_fodder_items as $fIdx => $fRow): 
                                                    $isOther = ($fRow['item'] ?? '') === 'Other';
                                                ?>
                                                <tr class="fodder-row" id="fodder_row_<?= $fIdx ?>">
                                                    <td>
                                                        <select name="fodder_items[<?= $fIdx ?>][item]" class="form-select form-select-sm fodder-item-select" required onchange="handleFodderItemChange(<?= $fIdx ?>); calcFodderTotal();">
                                                            <option value="" disabled <?= empty($fRow['item']) ? 'selected' : '' ?>>-- Select Crop Item --</option>
                                                            <option value="Hybrid Napier" <?= ($fRow['item'] ?? '') === 'Hybrid Napier' ? 'selected' : '' ?>>Hybrid Napier</option>
                                                            <option value="Sorghum" <?= ($fRow['item'] ?? '') === 'Sorghum' ? 'selected' : '' ?>>Sorghum</option>
                                                            <option value="Maize(fodder)" <?= ($fRow['item'] ?? '') === 'Maize(fodder)' ? 'selected' : '' ?>>Maize(fodder)</option>
                                                            <option value="Other" <?= $isOther ? 'selected' : '' ?>>Other</option>
                                                        </select>
                                                    </td>
                                                    <td>
                                                        <div id="fodder_specify_wrap_<?= $fIdx ?>" style="<?= $isOther ? 'display: block;' : 'display: none;' ?>">
                                                            <div class="input-group input-group-sm">
                                                                <span class="input-group-text"><i class="bi bi-tag-fill text-success"></i></span>
                                                                <input type="text" maxlength="255" name="fodder_items[<?= $fIdx ?>][other_specify]" id="fodder_other_specify_<?= $fIdx ?>" class="form-control form-control-sm fodder-specify-input" placeholder="e.g. Guinea Grass, CO-3, etc." value="<?= htmlspecialchars($fRow['other_specify'] ?? '') ?>" <?= $isOther ? 'required' : '' ?>>
                                                            </div>
                                                        </div>
                                                        <div id="fodder_specify_placeholder_<?= $fIdx ?>" class="text-muted small px-2" style="<?= $isOther ? 'display: none;' : 'display: block;' ?>">
                                                            <span class="fst-italic">- N/A -</span>
                                                        </div>
                                                    </td>
                                                    <td>
                                                        <div class="input-group input-group-sm">
                                                            <input type="number" step="0.1" min="0" name="fodder_items[<?= $fIdx ?>][amount]" class="form-control form-control-sm fodder-amount-input" placeholder="0.0" value="<?= isset($fRow['amount']) && $fRow['amount'] !== '' ? floatval($fRow['amount']) : '' ?>" required oninput="calcFodderTotal();">
                                                            <span class="input-group-text">P</span>
                                                        </div>
                                                    </td>
                                                    <td class="text-center">
                                                        <button type="button" class="btn btn-outline-danger btn-sm px-2 py-1" onclick="removeFodderRow(<?= $fIdx ?>);" title="Remove Row">
                                                            <i class="bi bi-trash"></i>
                                                        </button>
                                                    </td>
                                                </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                            <tfoot class="table-light fw-bold">
                                                <tr>
                                                    <td colspan="2" class="text-end text-dark">Total Land Area (in Perch):</td>
                                                    <td>
                                                        <div class="input-group input-group-sm">
                                                            <input type="number" step="0.1" min="0" readonly name="fodder[total_land_area]" id="fodder_total_land_area" class="form-control fw-bold text-success font-monospace" placeholder="0.0" value="<?= floatval($edit_rec['fodder_total_land_area'] ?? 0.0) ?>">
                                                            <span class="input-group-text fw-bold">Perches</span>
                                                        </div>
                                                    </td>
                                                    <td class="text-center">
                                                        <button type="button" class="btn btn-sm btn-success px-2 py-1" onclick="addFodderRow();" title="Add Another Crop Row">
                                                            <i class="bi bi-plus-lg"></i>
                                                        </button>
                                                    </td>
                                                </tr>
                                            </tfoot>
                                        </table>
                                    </div>
                                    <div class="d-flex justify-content-between align-items-center">
                                        <small class="text-muted"><i class="bi bi-info-circle me-1"></i>Click <strong>"Add Crop Row"</strong> or the <i class="bi bi-plus-lg text-success"></i> button to record multiple crops.</small>
                                        <button type="button" class="btn btn-outline-success btn-sm fw-semibold" onclick="addFodderRow();">
                                            <i class="bi bi-plus-circle me-1"></i>Add Row
                                        </button>
                                    </div>
                                </div>

                                <div class="panel-nav-footer">
                                    <button type="button" class="btn btn-outline-secondary btn-sm btn-prev-category" data-prev-pane="pane-milk" data-prev-index="3">
                                        <i class="bi bi-arrow-left me-1"></i>Previous: 4. Milk Production & Sale
                                    </button>
                                    <button type="button" class="btn btn-dark btn-sm px-4 fw-semibold btn-next-category" data-next-pane="pane-swine" data-next-index="5">
                                        Next: 6. Swine <i class="bi bi-arrow-right ms-1"></i>
                                    </button>
                                </div>
                            </div>

                            <!-- ============================================================== -->
                            <!-- 6. SWINE                                                       -->
                            <!-- ============================================================== -->
                            <div class="category-pane" id="pane-swine" style="display: none;">
                                <div class="category-panel-header d-flex flex-wrap justify-content-between align-items-center gap-2">
                                    <div>
                                        <span class="category-badge-step mb-1">Section 6 of 8</span>
                                        <h4 class="h5 fw-bold text-dark mb-1">
                                            <i class="bi bi-bookmark-star-fill text-danger me-2"></i>6. Swine
                                        </h4>
                                        <p class="text-muted small mb-0">Record swine herd numbers, breeding stock, fatteners, piglings, and sales frequencies.</p>
                                    </div>
                                    <span class="badge bg-danger-subtle text-danger border border-danger-subtle px-3 py-2">
                                        Piggery Operations
                                    </span>
                                </div>

                                <div class="p-4">
                                    <div class="row g-3">
                                        <div class="col-md-6">
                                            <label class="form-label">Breeding Female</label>
                                            <input type="number" min="0" value="<?= intval($edit_rec['swine_breeding_female'] ?? 0) ?>" name="swine[breeding_female]" id="swine_breeding_female" class="form-control form-control-sm swine-sub-calc" placeholder="0">
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">Breeding Male</label>
                                            <input type="number" min="0" value="<?= intval($edit_rec['swine_breeding_male'] ?? 0) ?>" name="swine[breeding_male]" id="swine_breeding_male" class="form-control form-control-sm swine-sub-calc" placeholder="0">
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">Weaners fattening</label>
                                            <input type="number" min="0" value="<?= intval($edit_rec['swine_weaners_fattening'] ?? 0) ?>" name="swine[weaners_fattening]" id="swine_weaners" class="form-control form-control-sm swine-sub-calc" placeholder="0">
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">Pre weaners (Piglings)</label>
                                            <input type="number" min="0" value="<?= intval($edit_rec['swine_pre_weaners'] ?? 0) ?>" name="swine[pre_weaners]" id="swine_pre_weaners" class="form-control form-control-sm swine-sub-calc" placeholder="0">
                                        </div>
                                        <div class="col-12">
                                            <div class="p-3 bg-light rounded-3 border d-flex justify-content-between align-items-center">
                                                <div>
                                                    <label class="form-label fw-bold text-dark mb-0">Total Swine Herd (Total No)</label>
                                                    <small class="text-muted d-block">Automatic summation of Breeding Female + Breeding Male + Weaners + Pre weaners.</small>
                                                </div>
                                                <div class="text-end">
                                                    <div class="input-group input-group-sm" style="max-width: 200px;">
                                                        <input type="number" min="0" readonly value="<?= intval($edit_rec['swine_total_no'] ?? 0) ?>" name="swine[total_no]" id="swine_total_no" class="form-control form-control-sm font-monospace fw-bold text-danger fs-6 text-center" placeholder="0">
                                                        <span class="input-group-text fw-semibold">Heads</span>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">Frequency of animal sales for meat</label>
                                            <?php $cur_sfm = $edit_rec['swine_freq_meat'] ?? 'Not applicable'; ?>
                                            <select name="swine[freq_sales_meat]" id="swine_freq_meat" class="form-select form-select-sm">
                                                <option value="Not applicable" <?= $cur_sfm === 'Not applicable' ? 'selected' : '' ?>>Not applicable / None</option>
                                                <option value="Weekly" <?= $cur_sfm === 'Weekly' ? 'selected' : '' ?>>Weekly</option>
                                                <option value="Monthly" <?= $cur_sfm === 'Monthly' ? 'selected' : '' ?>>Monthly</option>
                                                <option value="Bi-monthly" <?= $cur_sfm === 'Bi-monthly' ? 'selected' : '' ?>>Bi-monthly</option>
                                                <option value="Quarterly" <?= $cur_sfm === 'Quarterly' ? 'selected' : '' ?>>Quarterly</option>
                                                <option value="Biannually" <?= $cur_sfm === 'Biannually' ? 'selected' : '' ?>>Biannually</option>
                                                <option value="Annually" <?= $cur_sfm === 'Annually' ? 'selected' : '' ?>>Annually</option>
                                                <option value="On demand" <?= $cur_sfm === 'On demand' ? 'selected' : '' ?>>On demand / As needed</option>
                                            </select>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">Frequency of animal sales for breeding</label>
                                            <?php $cur_sfb = $edit_rec['swine_freq_breeding'] ?? 'Not applicable'; ?>
                                            <select name="swine[freq_sales_breeding]" id="swine_freq_breeding" class="form-select form-select-sm">
                                                <option value="Not applicable" <?= $cur_sfb === 'Not applicable' ? 'selected' : '' ?>>Not applicable / None</option>
                                                <option value="Monthly" <?= $cur_sfb === 'Monthly' ? 'selected' : '' ?>>Monthly</option>
                                                <option value="Quarterly" <?= $cur_sfb === 'Quarterly' ? 'selected' : '' ?>>Quarterly</option>
                                                <option value="Biannually" <?= $cur_sfb === 'Biannually' ? 'selected' : '' ?>>Biannually</option>
                                                <option value="Annually" <?= $cur_sfb === 'Annually' ? 'selected' : '' ?>>Annually</option>
                                                <option value="Seasonal" <?= $cur_sfb === 'Seasonal' ? 'selected' : '' ?>>Seasonal</option>
                                                <option value="Rarely" <?= $cur_sfb === 'Rarely' ? 'selected' : '' ?>>Rarely / As requested</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>

                                <div class="panel-nav-footer">
                                    <button type="button" class="btn btn-outline-secondary btn-sm btn-prev-category" data-prev-pane="pane-fodder" data-prev-index="4">
                                        <i class="bi bi-arrow-left me-1"></i>Previous: 5. Fodder / Pasture
                                    </button>
                                    <button type="button" class="btn btn-dark btn-sm px-4 fw-semibold btn-next-category" data-next-pane="pane-goat" data-next-index="6">
                                        Next: 7. Goat <i class="bi bi-arrow-right ms-1"></i>
                                    </button>
                                </div>
                            </div>

                            <!-- ============================================================== -->
                            <!-- 7. GOAT                                                        -->
                            <!-- ============================================================== -->
                            <div class="category-pane" id="pane-goat" style="display: none;">
                                <div class="category-panel-header d-flex flex-wrap justify-content-between align-items-center gap-2">
                                    <div>
                                        <span class="category-badge-step mb-1">Section 7 of 8</span>
                                        <h4 class="h5 fw-bold text-dark mb-1">
                                            <i class="bi bi-patch-check-fill text-warning me-2"></i>7. Goat
                                        </h4>
                                        <p class="text-muted small mb-0">Record goat flock counts, kids, fatteners, breeding frequency, and daily milk yield.</p>
                                    </div>
                                    <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle px-3 py-2">
                                        Caprine Herd
                                    </span>
                                </div>

                                <div class="p-4">
                                    <div class="row g-3">
                                        <div class="col-md-6">
                                            <label class="form-label">Breeding Female</label>
                                            <input type="number" min="0" value="<?= intval($edit_rec['goat_breeding_female'] ?? 0) ?>" name="goat[breeding_female]" id="goat_breeding_female" class="form-control form-control-sm goat-sub-calc" placeholder="0">
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">Breeding Male</label>
                                            <input type="number" min="0" value="<?= intval($edit_rec['goat_breeding_male'] ?? 0) ?>" name="goat[breeding_male]" id="goat_breeding_male" class="form-control form-control-sm goat-sub-calc" placeholder="0">
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">Weaners fattening</label>
                                            <input type="number" min="0" value="<?= intval($edit_rec['goat_weaners_fattening'] ?? 0) ?>" name="goat[weaners_fattening]" id="goat_weaners" class="form-control form-control-sm goat-sub-calc" placeholder="0">
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">Pre weaners (Kids)</label>
                                            <input type="number" min="0" value="<?= intval($edit_rec['goat_pre_weaners'] ?? 0) ?>" name="goat[pre_weaners]" id="goat_pre_weaners" class="form-control form-control-sm goat-sub-calc" placeholder="0">
                                        </div>
                                        <div class="col-12">
                                            <div class="p-3 bg-light rounded-3 border d-flex justify-content-between align-items-center">
                                                <div>
                                                    <label class="form-label fw-bold text-dark mb-0">Total Goat Flock (Total No)</label>
                                                    <small class="text-muted d-block">Automatic summation of Breeding Female + Breeding Male + Weaners + Pre weaners (Kids).</small>
                                                </div>
                                                <div class="text-end">
                                                    <div class="input-group input-group-sm" style="max-width: 200px;">
                                                        <input type="number" min="0" readonly value="<?= intval($edit_rec['goat_total_no'] ?? 0) ?>" name="goat[total_no]" id="goat_total_no" class="form-control form-control-sm font-monospace fw-bold text-warning-emphasis fs-6 text-center" placeholder="0">
                                                        <span class="input-group-text fw-semibold">Heads</span>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label">Frequency of animal sales for meat</label>
                                            <?php $cur_gfm = $edit_rec['goat_freq_meat'] ?? 'Not applicable'; ?>
                                            <select name="goat[freq_sales_meat]" id="goat_freq_meat" class="form-select form-select-sm">
                                                <option value="Not applicable" <?= $cur_gfm === 'Not applicable' ? 'selected' : '' ?>>Not applicable / None</option>
                                                <option value="Weekly" <?= $cur_gfm === 'Weekly' ? 'selected' : '' ?>>Weekly</option>
                                                <option value="Monthly" <?= $cur_gfm === 'Monthly' ? 'selected' : '' ?>>Monthly</option>
                                                <option value="Bi-monthly" <?= $cur_gfm === 'Bi-monthly' ? 'selected' : '' ?>>Bi-monthly</option>
                                                <option value="Quarterly" <?= $cur_gfm === 'Quarterly' ? 'selected' : '' ?>>Quarterly</option>
                                                <option value="Festive / Seasonal" <?= $cur_gfm === 'Festive / Seasonal' ? 'selected' : '' ?>>Festive / Seasonal (Eid/Festivals)</option>
                                                <option value="On demand" <?= $cur_gfm === 'On demand' ? 'selected' : '' ?>>On demand</option>
                                            </select>
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label">Frequency of animal sales for Breeding</label>
                                            <?php $cur_gfb = $edit_rec['goat_freq_breeding'] ?? 'Not applicable'; ?>
                                            <select name="goat[freq_sales_breeding]" id="goat_freq_breeding" class="form-select form-select-sm">
                                                <option value="Not applicable" <?= $cur_gfb === 'Not applicable' ? 'selected' : '' ?>>Not applicable / None</option>
                                                <option value="Monthly" <?= $cur_gfb === 'Monthly' ? 'selected' : '' ?>>Monthly</option>
                                                <option value="Quarterly" <?= $cur_gfb === 'Quarterly' ? 'selected' : '' ?>>Quarterly</option>
                                                <option value="Biannually" <?= $cur_gfb === 'Biannually' ? 'selected' : '' ?>>Biannually</option>
                                                <option value="Annually" <?= $cur_gfb === 'Annually' ? 'selected' : '' ?>>Annually</option>
                                                <option value="Seasonal" <?= $cur_gfb === 'Seasonal' ? 'selected' : '' ?>>Seasonal</option>
                                                <option value="Rarely" <?= $cur_gfb === 'Rarely' ? 'selected' : '' ?>>Rarely</option>
                                            </select>
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label">Milk Production per day</label>
                                            <div class="input-group input-group-sm">
                                                <input type="number" step="0.1" min="0" value="<?= floatval($edit_rec['goat_milk_per_day'] ?? 0.0) ?>" name="goat[milk_production_per_day]" id="goat_milk_per_day" class="form-control" placeholder="0.0">
                                                <span class="input-group-text">L/day</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="panel-nav-footer">
                                    <button type="button" class="btn btn-outline-secondary btn-sm btn-prev-category" data-prev-pane="pane-swine" data-prev-index="5">
                                        <i class="bi bi-arrow-left me-1"></i>Previous: 6. Swine
                                    </button>
                                    <button type="button" class="btn btn-dark btn-sm px-4 fw-semibold btn-next-category" data-next-pane="pane-sheep" data-next-index="7">
                                        Next: 8. Sheep <i class="bi bi-arrow-right ms-1"></i>
                                    </button>
                                </div>
                            </div>

                            <!-- ============================================================== -->
                            <!-- 8. SHEEP                                                       -->
                            <!-- ============================================================== -->
                            <div class="category-pane" id="pane-sheep" style="display: none;">
                                <div class="category-panel-header d-flex flex-wrap justify-content-between align-items-center gap-2">
                                    <div>
                                        <span class="category-badge-step mb-1">Section 8 of 8</span>
                                        <h4 class="h5 fw-bold text-dark mb-1">
                                            <i class="bi bi-circle-square text-secondary me-2"></i>8. Sheep
                                        </h4>
                                        <p class="text-muted small mb-0">Record sheep breeding females, breeding males, and sheep maintained for meat.</p>
                                    </div>
                                    <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle px-3 py-2">
                                        Ovine Flock
                                    </span>
                                </div>

                                <div class="p-4">
                                    <div class="row g-3">
                                        <div class="col-md-4">
                                            <label class="form-label">Breeding Female</label>
                                            <input type="number" min="0" value="<?= intval($edit_rec['sheep_breeding_female'] ?? 0) ?>" name="sheep[breeding_female]" id="sheep_breeding_female" class="form-control form-control-sm sheep-calc-input">
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label">Breeding Male</label>
                                            <input type="number" min="0" value="<?= intval($edit_rec['sheep_breeding_male'] ?? 0) ?>" name="sheep[breeding_male]" id="sheep_breeding_male" class="form-control form-control-sm sheep-calc-input">
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label">For meat</label>
                                            <input type="number" min="0" value="<?= intval($edit_rec['sheep_for_meat'] ?? 0) ?>" name="sheep[for_meat]" id="sheep_for_meat" class="form-control form-control-sm sheep-calc-input">
                                        </div>
                                        <div class="col-12 mt-3">
                                            <div class="p-3 bg-light rounded-3 border d-flex justify-content-between align-items-center">
                                                <div>
                                                    <span class="small fw-bold text-muted text-uppercase">Total Sheep in Flock:</span>
                                                    <small class="text-muted d-block">Automatic summation of Breeding Female + Breeding Male + For Meat.</small>
                                                </div>
                                                <h5 id="totalSheepDisplay" class="mb-0 text-secondary fw-bold"><?= intval($edit_rec['sheep_total_no'] ?? 0) ?> Heads</h5>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="panel-nav-footer">
                                    <button type="button" class="btn btn-outline-secondary btn-sm btn-prev-category" data-prev-pane="pane-goat" data-prev-index="6">
                                        <i class="bi bi-arrow-left me-1"></i>Previous: 7. Goat
                                    </button>
                                    <div class="d-flex gap-2">
                                        <button type="button" class="btn btn-outline-secondary btn-sm" onclick="resetMasterForm();">
                                            <i class="bi bi-arrow-counterclockwise me-1"></i>Reset
                                        </button>
                                        <button type="submit" class="btn btn-danger btn-sm px-4 fw-bold shadow-sm" style="background-color: var(--daph-maroon); border-color: var(--daph-maroon);">
                                            <i class="bi bi-check2-circle me-1"></i><span id="btnSubmitBottomText"><?= empty($edit_rec) ? 'Save to Database' : 'Update Record in Database' ?></span>
                                        </button>
                                    </div>
                                </div>
                            </div>

                        </div>
                    </div>
                </div>

                <!-- COLLAPSIBLE ACCORDION LAYOUT SHELL (POPULATED DYNAMICALLY) -->
                <div id="accordionLayoutContainer" class="accordion accordion-branding d-none"></div>

            </form>
        </div>

        <!-- ============================================================== -->
        <!-- VIEW 2: VIEW ADDED DETAILS (DATA GRID & SUMMARY TABLE)         -->
        <!-- ============================================================== -->
        <div class="tab-pane fade <?= !empty($_GET['view_records']) ? 'show active' : '' ?>" id="viewRegistryPanel" role="tabpanel" aria-labelledby="view-registry-tab">
            <div class="card shadow-sm border-0 rounded-4">
                <div class="card-header bg-white py-3 d-flex flex-wrap justify-content-between align-items-center gap-2">
                    <div>
                        <h5 class="mb-0 fw-bold text-dark"><i class="bi bi-table text-danger me-2"></i>View Added Details - Registered Livestock Farms</h5>
                        <small class="text-muted">Master live database records for <?= htmlspecialchars($range_name) ?> range.</small>
                    </div>
                    <div class="d-flex gap-2">
                        <button type="button" class="btn btn-sm btn-primary" onclick="switchToNewForm();">
                            <i class="bi bi-plus-circle me-1"></i>Add New Registration
                        </button>
                    </div>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0 datatable" id="addedDetailsDataTable">
                            <thead class="table-light small text-uppercase">
                                <tr>
                                    <th class="ps-4">Reg No (9 Digits)</th>
                                    <th>Renewal Date</th>
                                    <th>Farmer Name</th>
                                    <th>NIC / Phone</th>
                                    <th>DS / GN Division</th>
                                    <th>Farm Type</th>
                                    <th class="text-center">Cattle</th>
                                    <th class="text-center">Buffalo</th>
                                    <th class="text-center">Milk (L/d)</th>
                                    <th class="text-center">Pasture (P)</th>
                                    <th class="text-end pe-4">Actions</th>
                                </tr>
                            </thead>
                            <tbody id="addedDetailsTableBody">
                                <?php if (empty($db_records)): ?>
                                <tr>
                                    <td colspan="11" class="text-center py-5 text-muted">
                                        <i class="bi bi-inbox fs-1 d-block mb-2 text-secondary"></i>
                                        <span class="fw-semibold">No livestock farm registration records found in the database.</span><br>
                                        <small class="text-muted">Click the button below to register the first farm.</small><br>
                                        <button type="button" class="btn btn-sm btn-primary mt-3" onclick="switchToNewForm();">
                                            <i class="bi bi-plus-circle me-1"></i>Register New Farm
                                        </button>
                                    </td>
                                </tr>
                                <?php else: ?>
                                <?php foreach($db_records as $rec): ?>
                                <tr id="row_record_<?= $rec['id'] ?>">
                                    <td class="ps-4">
                                        <span class="badge bg-light text-dark border font-monospace fw-bold fs-7"><?= htmlspecialchars($rec['registration_no']) ?></span>
                                    </td>
                                    <td><small class="text-muted"><?= htmlspecialchars($rec['date_of_registration_renewal']) ?></small></td>
                                    <td>
                                        <strong class="d-block text-dark"><?= htmlspecialchars($rec['farmer_name']) ?></strong>
                                        <small class="text-muted text-truncate d-inline-block" style="max-width: 180px;"><?= htmlspecialchars($rec['farmer_address']) ?></small>
                                    </td>
                                    <td>
                                        <span class="small d-block fw-semibold text-dark"><?= htmlspecialchars($rec['nic']) ?></span>
                                        <small class="text-muted"><?= htmlspecialchars($rec['telephone_no']) ?></small>
                                    </td>
                                    <td>
                                        <span class="small d-block"><?= htmlspecialchars($rec['ds_division']) ?></span>
                                        <small class="text-muted"><?= htmlspecialchars($rec['gn_division']) ?></small>
                                    </td>
                                    <td>
                                        <span class="badge bg-primary-subtle text-primary border border-primary-subtle"><?= htmlspecialchars($rec['farm_type']) ?></span>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge bg-dark rounded-pill"><?= (int)$rec['total_neat_cattle'] ?></span>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge bg-warning text-dark rounded-pill"><?= (int)$rec['total_buffaloes'] ?></span>
                                    </td>
                                    <td class="text-center font-monospace">
                                        <?= number_format((float)$rec['daily_milk_production'], 1) ?>
                                    </td>
                                    <td class="text-center font-monospace">
                                        <?= number_format((float)$rec['fodder_total_land_area'], 1) ?>
                                        <?php
                                        $f_list = !empty($rec['fodder_data']) ? (json_decode($rec['fodder_data'], true) ?: []) : [];
                                        if (empty($f_list)) {
                                            if ((float)$rec['fodder_hybrid_napier'] > 0) $f_list[] = ['item' => 'Hybrid Napier', 'amount' => (float)$rec['fodder_hybrid_napier'], 'other_specify' => ''];
                                            if ((float)$rec['fodder_sorghum'] > 0) $f_list[] = ['item' => 'Sorghum', 'amount' => (float)$rec['fodder_sorghum'], 'other_specify' => ''];
                                            if ((float)$rec['fodder_maize'] > 0) $f_list[] = ['item' => 'Maize(fodder)', 'amount' => (float)$rec['fodder_maize'], 'other_specify' => ''];
                                            if ((float)$rec['fodder_other'] > 0) $f_list[] = ['item' => 'Other', 'amount' => (float)$rec['fodder_other'], 'other_specify' => $rec['fodder_other_specify'] ?? ''];
                                        }
                                        if (!empty($f_list)):
                                        ?>
                                        <div class="text-start mt-1" style="font-size: 0.72rem; line-height: 1.25;">
                                            <?php foreach ($f_list as $fItem): ?>
                                                <div class="text-nowrap text-secondary">
                                                    • <?= htmlspecialchars($fItem['item'] === 'Other' && !empty($fItem['other_specify']) ? 'Other (' . $fItem['other_specify'] . ')' : $fItem['item']) ?>: <strong><?= number_format((float)$fItem['amount'], 1) ?>P</strong>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-end pe-4">
                                        <div class="btn-group btn-group-sm">
                                            <button type="button" class="btn btn-outline-primary" onclick="showRecordDetailModal(<?= $rec['id'] ?>);" title="View Full Details">
                                                <i class="bi bi-eye-fill"></i> View
                                            </button>
                                            <button type="button" class="btn btn-outline-secondary" onclick="loadRecordIntoForm(<?= $rec['id'] ?>);" title="Edit / Load into Form">
                                                <i class="bi bi-pencil-fill"></i>
                                            </button>
                                            <button type="button" class="btn btn-outline-danger" onclick="deleteRecordPrompt(<?= $rec['id'] ?>, '<?= htmlspecialchars($rec['registration_no'], ENT_QUOTES) ?>');" title="Delete Record">
                                                <i class="bi bi-trash"></i>
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

    </div>
</div>

<!-- Modal: Comprehensive Record Detail View (Bound to Database) -->
<div class="modal fade" id="recordDetailModal" tabindex="-1" aria-labelledby="recordDetailModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-header text-white" style="background: linear-gradient(135deg, #370709 0%, #5a1215 100%);">
                <div class="d-flex align-items-center gap-2">
                    <i class="bi bi-card-checklist fs-5 text-warning"></i>
                    <h6 class="modal-title fw-bold" id="recordDetailModalLabel">Livestock Farm Registration Details</h6>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4" id="modalDetailContent">
                <!-- Dynamically populated via JS -->
            </div>
            <div class="modal-footer bg-light">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-dark btn-sm" onclick="window.print();">
                    <i class="bi bi-printer me-1"></i>Print Record Summary
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Client-side Data Binding & Veterinary JS Module -->
<script>
// Live database records array passed from PHP to global scope
window.liveDatabaseRecords = <?= json_encode($records_for_js, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?: '[]' ?>;
let liveDatabaseRecords = window.liveDatabaseRecords;
</script>
<script src="../../../assets/js/veterinary.js"></script>

<?php
require_once '../../../includes/footer.php';
?>