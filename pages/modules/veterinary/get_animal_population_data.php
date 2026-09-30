<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../../../config/db_connect.php';

/** @var mysqli $mysqli */
global $mysqli;

$allowed_roles = [
    'veterinary_surgeon',
    'district_dd',
    'deputy_director_district',
    'administrator',
    'provincial_director',
    'deputy_director_hq_1',
    'deputy_director_hq_2',
    'admin'
];

if (!isset($_SESSION['logged_in']) || !in_array($_SESSION['role'] ?? '', $allowed_roles, true)) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

$range_id = isset($_GET['range_id']) && intval($_GET['range_id']) > 0 
    ? intval($_GET['range_id']) 
    : intval($_SESSION['range_id'] ?? 0);
$year = isset($_GET['year']) ? intval($_GET['year']) : intval(date('Y'));

$requested_animals = isset($_GET['animals']) ? json_decode($_GET['animals'], true) : ['Cow', 'Buffalo', 'Goat', 'Sheep', 'Chicken', 'Pig', 'Others'];
if (!is_array($requested_animals) || empty($requested_animals)) {
    $requested_animals = ['Cow', 'Buffalo', 'Goat', 'Sheep', 'Chicken', 'Pig', 'Others'];
}

if ($range_id <= 0) {
    header('Content-Type: application/json');
    echo json_encode([]);
    exit();
}

$counts = [
    'Cow' => 0,
    'Buffalo' => 0,
    'Goat' => 0,
    'Sheep' => 0,
    'Chicken' => 0,
    'Poultry' => 0,
    'Pig' => 0,
    'Others' => 0
];

// Check if live registrations exist in farm_registration_renewals for this range and year
$has_live_data = false;
$chk_stmt = $mysqli->prepare("
    SELECT COUNT(*) AS total_regs
    FROM farm_registration_renewals
    WHERE range_id = ? AND YEAR(date_of_registration_renewal) = ?
");
if ($chk_stmt) {
    $chk_stmt->bind_param("ii", $range_id, $year);
    $chk_stmt->execute();
    $chk_res = $chk_stmt->get_result();
    if ($chk_row = $chk_res->fetch_assoc()) {
        $has_live_data = (intval($chk_row['total_regs']) > 0);
    }
    $chk_stmt->close();
}

if ($has_live_data) {
    // 1. Dynamic livestock aggregation directly from farm_registration_renewals (Master Registry)
    $stmt = $mysqli->prepare("
        SELECT 
            COALESCE(SUM(total_neat_cattle), 0) AS cow_total,
            COALESCE(SUM(total_buffaloes), 0) AS buffalo_total,
            COALESCE(SUM(goat_total_no), 0) AS goat_total,
            COALESCE(SUM(sheep_total_no), 0) AS sheep_total,
            COALESCE(SUM(swine_total_no), 0) AS pig_total
        FROM farm_registration_renewals
        WHERE range_id = ? AND YEAR(date_of_registration_renewal) = ?
    ");
    if ($stmt) {
        $stmt->bind_param("ii", $range_id, $year);
        $stmt->execute();
        $res = $stmt->get_result();
        if ($row = $res->fetch_assoc()) {
            $counts['Cow'] = intval($row['cow_total']);
            $counts['Buffalo'] = intval($row['buffalo_total']);
            $counts['Goat'] = intval($row['goat_total']);
            $counts['Sheep'] = intval($row['sheep_total']);
            $counts['Pig'] = intval($row['pig_total']);
        }
        $stmt->close();
    }

    // 2. Sum poultry counts from poultry registrations
    $p_stmt = $mysqli->prepare("
        SELECT poultry_data
        FROM farm_registration_renewals
        WHERE range_id = ? AND YEAR(date_of_registration_renewal) = ?
          AND (
              farm_type = 'Poultry' 
              OR (poultry_data IS NOT NULL AND poultry_data != '' AND poultry_data != '[]' AND poultry_data != 'null')
          )
    ");
    if ($p_stmt) {
        $p_stmt->bind_param("ii", $range_id, $year);
        $p_stmt->execute();
        $p_res = $p_stmt->get_result();
        $p_total = 0;
        while ($p_row = $p_res->fetch_assoc()) {
            $pdata_raw = $p_row['poultry_data'];
            if (!empty($pdata_raw)) {
                $pdata = is_string($pdata_raw) ? json_decode($pdata_raw, true) : $pdata_raw;
                if (is_array($pdata)) {
                    $flock_sum = 0;
                    if (!empty($pdata['flock_age_groups']) && is_array($pdata['flock_age_groups'])) {
                        foreach ($pdata['flock_age_groups'] as $fg) {
                            if (isset($fg['quantity']) && is_numeric($fg['quantity'])) {
                                $flock_sum += intval($fg['quantity']);
                            }
                        }
                    }
                    if ($flock_sum > 0) {
                        $p_total += $flock_sum;
                    } elseif (!empty($pdata['housing']) && is_array($pdata['housing'])) {
                        foreach (['deep_litter_pens', 'battery_cages', 'free_range'] as $hKey) {
                            if (!empty($pdata['housing'][$hKey]) && is_numeric($pdata['housing'][$hKey])) {
                                $p_total += intval($pdata['housing'][$hKey]);
                            }
                        }
                    }
                }
            }
        }
        $counts['Chicken'] = $p_total;
        $counts['Poultry'] = $p_total;
        $p_stmt->close();
    }
} else {
    // Fallback: If no live registrations exist for this year, read from historical animal_populations table
    $fb_stmt = $mysqli->prepare("
        SELECT animal_type, SUM(quantity) AS total_count
        FROM animal_populations
        WHERE range_id = ? AND year = ?
        GROUP BY animal_type
    ");
    if ($fb_stmt) {
        $fb_stmt->bind_param("ii", $range_id, $year);
        $fb_stmt->execute();
        $fb_res = $fb_stmt->get_result();
        while ($fb_row = $fb_res->fetch_assoc()) {
            $atype = $fb_row['animal_type'];
            $cnt = intval($fb_row['total_count']);
            $counts[$atype] = $cnt;
            if ($atype === 'Chicken') {
                $counts['Poultry'] = $cnt;
            } elseif ($atype === 'Poultry') {
                $counts['Chicken'] = $cnt;
            }
        }
        $fb_stmt->close();
    }
}

// Build filtered output rows preserving requested order
$output_rows = [];
foreach ($requested_animals as $req_animal) {
    $c = 0;
    if (isset($counts[$req_animal])) {
        $c = $counts[$req_animal];
    } elseif ($req_animal === 'Poultry' && isset($counts['Chicken'])) {
        $c = $counts['Chicken'];
    } elseif ($req_animal === 'Chicken' && isset($counts['Poultry'])) {
        $c = $counts['Poultry'];
    }
    
    $output_rows[] = [
        'year' => $year,
        'animal_type' => ($req_animal === 'Chicken' ? 'Chicken' : $req_animal),
        'count' => $c
    ];
}

header('Content-Type: application/json');
echo json_encode($output_rows);
exit();

