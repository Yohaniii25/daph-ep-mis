<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../../../config/db_connect.php';

/** @var mysqli $mysqli */
global $mysqli;

$allowed_roles = [
    'veterinary_surgeon',
    'government_veterinary_surgeon',
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
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

$range_id = isset($_GET['range_id']) && intval($_GET['range_id']) > 0 
    ? intval($_GET['range_id']) 
    : intval($_SESSION['range_id'] ?? 0);
$year = isset($_GET['year']) ? intval($_GET['year']) : intval(date('Y'));

// Standardized livestock categories matching user specifications
$standard_species = ['Cattle', 'Buffaloes', 'Goats', 'Sheep', 'Swine', 'Poultry'];

$requested_animals = isset($_GET['animals']) ? json_decode($_GET['animals'], true) : $standard_species;
if (!is_array($requested_animals) || empty($requested_animals)) {
    $requested_animals = $standard_species;
}

if ($range_id <= 0) {
    header('Content-Type: application/json');
    echo json_encode([]);
    exit();
}

$range_name = '';
$vr_stmt = $mysqli->prepare("SELECT name FROM veterinary_ranges WHERE id = ?");
if ($vr_stmt) {
    $vr_stmt->bind_param("i", $range_id);
    $vr_stmt->execute();
    $vr_res = $vr_stmt->get_result();
    if ($vr_row = $vr_res->fetch_assoc()) {
        $range_name = $vr_row['name'];
    }
    $vr_stmt->close();
}

$counts = [
    'Cattle' => 0,
    'Buffaloes' => 0,
    'Goats' => 0,
    'Sheep' => 0,
    'Swine' => 0,
    'Poultry' => 0
];

// Helper to normalize species names to standardized categories
function normalizeSpeciesCategory($name) {
    $n = strtolower(trim($name));
    if (in_array($n, ['cattle', 'cow', 'cows', 'neat cattle', 'neat_cattle'])) return 'Cattle';
    if (in_array($n, ['buffaloes', 'buffalo', 'buffalos'])) return 'Buffaloes';
    if (in_array($n, ['goats', 'goat'])) return 'Goats';
    if (in_array($n, ['sheep'])) return 'Sheep';
    if (in_array($n, ['swine', 'pig', 'pigs'])) return 'Swine';
    if (in_array($n, ['poultry', 'chicken', 'chickens', 'birds'])) return 'Poultry';
    return ucfirst(trim($name));
}

// 1. Dynamic livestock aggregation directly from farm_registration_renewals (Master Registry)
$has_live_data = false;
$query_renewals = "
    SELECT 
        id,
        total_neat_cattle,
        neat_cattle_data,
        total_buffaloes,
        buffaloes_data,
        goat_total_no,
        sheep_total_no,
        swine_total_no,
        poultry_data,
        farm_type
    FROM farm_registration_renewals
    WHERE (
        range_id = ? 
        OR (vs_division IS NOT NULL AND vs_division != '' AND vs_division = ?)
        OR (vs_division IS NOT NULL AND vs_division != '' AND vs_division = (SELECT name FROM veterinary_ranges WHERE id = ? LIMIT 1))
    )
    AND (
        YEAR(date_of_registration_renewal) = ?
        OR (date_of_registration_renewal IS NULL AND YEAR(created_at) = ?)
        OR (? = 0)
    )
";

$live_stmt = $mysqli->prepare($query_renewals);
if ($live_stmt) {
    $live_stmt->bind_param("isiiii", $range_id, $range_name, $range_id, $year, $year, $year);
    $live_stmt->execute();
    $res = $live_stmt->get_result();
    
    $live_rows_count = 0;
    while ($row = $res->fetch_assoc()) {
        $live_rows_count++;
        
        // Neat cattle count
        $c_cnt = intval($row['total_neat_cattle']);
        if ($c_cnt <= 0 && !empty($row['neat_cattle_data'])) {
            $c_data = is_string($row['neat_cattle_data']) ? json_decode($row['neat_cattle_data'], true) : $row['neat_cattle_data'];
            if (is_array($c_data)) {
                foreach ($c_data as $sub) {
                    if (is_array($sub)) {
                        foreach ($sub as $val) {
                            $c_cnt += intval($val);
                        }
                    }
                }
            }
        }
        $counts['Cattle'] += $c_cnt;
        
        // Buffalo count
        $b_cnt = intval($row['total_buffaloes']);
        if ($b_cnt <= 0 && !empty($row['buffaloes_data'])) {
            $b_data = is_string($row['buffaloes_data']) ? json_decode($row['buffaloes_data'], true) : $row['buffaloes_data'];
            if (is_array($b_data)) {
                foreach ($b_data as $sub) {
                    if (is_array($sub)) {
                        foreach ($sub as $val) {
                            $b_cnt += intval($val);
                        }
                    }
                }
            }
        }
        $counts['Buffaloes'] += $b_cnt;
        
        // Small ruminants & Swine
        $counts['Goats'] += intval($row['goat_total_no']);
        $counts['Sheep'] += intval($row['sheep_total_no']);
        $counts['Swine'] += intval($row['swine_total_no']);
        
        // Poultry count
        if (!empty($row['poultry_data'])) {
            $pdata = is_string($row['poultry_data']) ? json_decode($row['poultry_data'], true) : $row['poultry_data'];
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
                    $counts['Poultry'] += $flock_sum;
                } elseif (!empty($pdata['housing']) && is_array($pdata['housing'])) {
                    $housing_sum = 0;
                    foreach (['deep_litter_pens', 'battery_cages', 'free_range'] as $hKey) {
                        if (!empty($pdata['housing'][$hKey]) && is_numeric($pdata['housing'][$hKey])) {
                            $housing_sum += intval($pdata['housing'][$hKey]);
                        }
                    }
                    $counts['Poultry'] += $housing_sum;
                }
            }
        }
    }
    $live_stmt->close();
    
    $total_live_animals = $counts['Cattle'] + $counts['Buffaloes'] + $counts['Goats'] + $counts['Sheep'] + $counts['Swine'] + $counts['Poultry'];
    if ($total_live_animals > 0 || $live_rows_count > 0) {
        $has_live_data = true;
    }
}

// 2. Fallback: If no live registrations exist for this year, read from historical animal_populations table
if (!$has_live_data) {
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
            $std = normalizeSpeciesCategory($fb_row['animal_type']);
            $cnt = intval($fb_row['total_count']);
            if (isset($counts[$std])) {
                $counts[$std] += $cnt;
            } else {
                $counts[$std] = $cnt;
            }
        }
        $fb_stmt->close();
    }
}

// Build filtered output rows preserving standardized category order
$output_rows = [];
$seen_categories = [];
foreach ($requested_animals as $req_animal) {
    $cat = normalizeSpeciesCategory($req_animal);
    if (isset($seen_categories[$cat])) continue;
    $seen_categories[$cat] = true;
    
    $c = $counts[$cat] ?? 0;
    
    $output_rows[] = [
        'year' => $year,
        'animal_type' => $cat,
        'count' => $c
    ];
}

header('Content-Type: application/json');
echo json_encode($output_rows);
exit();

