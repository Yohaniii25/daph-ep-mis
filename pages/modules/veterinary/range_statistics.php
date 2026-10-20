<?php
session_start();

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
    header("Location: ../../../index.php");
    exit();
}

require_once __DIR__ . '/../../../config/db_connect.php';

/** @var mysqli $mysqli */
global $mysqli;

if (!isset($_SESSION['full_name'])) {
    $_SESSION['full_name'] = $_SESSION['username'] ?? 'Veterinary Surgeon';
}

$full_name   = $_SESSION['full_name'];
$requested_range_id = isset($_GET['range_id']) && is_numeric($_GET['range_id']) ? (int)$_GET['range_id'] : null;
$range_id    = $requested_range_id ?: ($_SESSION['range_id'] ?? null);
$district_id = $_SESSION['district_id'] ?? null;

if (empty($range_id)) {
    $first_r = $mysqli->query("SELECT id FROM veterinary_ranges ORDER BY id ASC LIMIT 1")->fetch_assoc();
    $range_id = $first_r['id'] ?? 1;
}

$district_name = 'Unknown District';
$range_name    = 'Unknown Range';

// Fetch District and Range Names
if ($range_id) {
    $stmt = $mysqli->prepare("SELECT vr.name AS range_name, vr.district_id, d.name AS district_name 
                             FROM veterinary_ranges vr 
                             LEFT JOIN districts d ON vr.district_id = d.id 
                             WHERE vr.id = ?");
    if ($stmt) {
        $stmt->bind_param("i", $range_id);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($row = $result->fetch_assoc()) {
            $range_name = $row['range_name'];
            if (!empty($row['district_name'])) {
                $district_name = $row['district_name'];
            }
        }
        $stmt->close();
    }
}

// Fetch distinct recorded years for human population
$pop_years = [2026, 2025, 2024, 2023];
if ($range_id) {
    $stmt_yr = $mysqli->prepare("SELECT DISTINCT year FROM human_populations WHERE range_id = ? ORDER BY year DESC");
    if ($stmt_yr) {
        $stmt_yr->bind_param("i", $range_id);
        $stmt_yr->execute();
        $yr_res = $stmt_yr->get_result();
        while ($yrow = $yr_res->fetch_assoc()) {
            $pop_years[] = intval($yrow['year']);
        }
        $stmt_yr->close();
    }
}
$pop_years = array_unique($pop_years);
rsort($pop_years);

// Fetch all veterinary ranges for the range switcher
$all_ranges = [];
$ranges_res = $mysqli->query("SELECT vr.id, vr.name, d.name AS district_name FROM veterinary_ranges vr LEFT JOIN districts d ON vr.district_id = d.id ORDER BY d.name ASC, vr.name ASC");
if ($ranges_res) {
    while ($r_row = $ranges_res->fetch_assoc()) {
        $all_ranges[] = $r_row;
    }
}

// Fetch distinct recorded years for animal population (Master Registry farm_registration_renewals prioritized)
$animal_pop_years = [intval(date('Y'))];
if ($range_id) {
    // 1. Live Master Registry years from farm_registration_renewals
    $stmt_myr = $mysqli->prepare("
        SELECT DISTINCT YEAR(date_of_registration_renewal) AS yr 
        FROM farm_registration_renewals 
        WHERE (
            range_id = ? 
            OR (vs_division IS NOT NULL AND vs_division != '' AND vs_division = ?)
            OR (vs_division IS NOT NULL AND vs_division != '' AND vs_division = (SELECT name FROM veterinary_ranges WHERE id = ? LIMIT 1))
        )
        AND date_of_registration_renewal IS NOT NULL 
        ORDER BY yr DESC
    ");
    if ($stmt_myr) {
        $stmt_myr->bind_param("isi", $range_id, $range_name, $range_id);
        $stmt_myr->execute();
        $myr_res = $stmt_myr->get_result();
        while ($myrow = $myr_res->fetch_assoc()) {
            if (!empty($myrow['yr'])) {
                $animal_pop_years[] = intval($myrow['yr']);
            }
        }
        $stmt_myr->close();
    }
    // 2. Union historical recorded years from animal_populations
    $stmt_ayr = $mysqli->prepare("SELECT DISTINCT year FROM animal_populations WHERE range_id = ? ORDER BY year DESC");
    if ($stmt_ayr) {
        $stmt_ayr->bind_param("i", $range_id);
        $stmt_ayr->execute();
        $ayr_res = $stmt_ayr->get_result();
        while ($ayrow = $ayr_res->fetch_assoc()) {
            if (!empty($ayrow['year'])) {
                $animal_pop_years[] = intval($ayrow['year']);
            }
        }
        $stmt_ayr->close();
    }
}
$animal_pop_years = array_values(array_unique($animal_pop_years));
rsort($animal_pop_years);

// ==========================================
// FARM STATISTICS & VISUALIZATIONS AGGREGATOR
// ==========================================
$farm_stats_query = "
    SELECT 
        id,
        farm_type,
        total_neat_cattle,
        neat_cattle_data,
        total_buffaloes,
        buffaloes_data,
        poultry_data,
        daily_milk_production,
        milk_data,
        fodder_total_land_area,
        fodder_data
    FROM farm_registration_renewals
    WHERE (
        range_id = ? 
        OR (vs_division IS NOT NULL AND vs_division != '' AND vs_division = ?)
        OR (vs_division IS NOT NULL AND vs_division != '' AND vs_division = (SELECT name FROM veterinary_ranges WHERE id = ? LIMIT 1))
    )
";

$stmt_fs = $mysqli->prepare($farm_stats_query);
$farm_renewals = [];
if ($stmt_fs) {
    $stmt_fs->bind_param("isi", $range_id, $range_name, $range_id);
    $stmt_fs->execute();
    $fs_res = $stmt_fs->get_result();
    while ($fs_row = $fs_res->fetch_assoc()) {
        $farm_renewals[] = $fs_row;
    }
    $stmt_fs->close();
}

// Chart 1: Farmers by Animal Type (strictly Cattle, Buffalo, Poultry)
$cattle_farmers_count = 0;
$buffalo_farmers_count = 0;
$poultry_farmers_count = 0;

// Specialty Poultry Farmers Aggregator
$poultry_specialty_farmers = [
    'Broiler Farmers' => [],
    'Layer Farmers' => [],
    'Free-Ranging / Local Farmers' => [],
    'Farmers involved in Feed Manufacturing' => []
];
$total_poultry_birds = 0;
$flock_age_totals = [
    'Chicks (<8 weeks)' => 0,
    'Growers (8-17 weeks old)' => 0,
    'Hens (>18 Weeks)' => 0,
    'Other / Unclassified' => 0
];
$total_poultry_monthly_feed_kg = 0.0;
$daph_registered_feed_mfr_count = 0;

// Chart 2: Milk Production Volume Buckets (<5L, 5-10L, 10-20L, 20-40L, 40-100L, >100L)
$milk_buckets = [
    '<5L' => 0,
    '5-10L' => 0,
    '10-20L' => 0,
    '20-40L' => 0,
    '40-100L' => 0,
    '>100L' => 0
];
$total_dairy_farmers = 0;
$renewal_ids = [];

foreach ($farm_renewals as $fr) {
    $f_id = intval($fr['id']);
    $renewal_ids[] = $f_id;

    // Check Cattle
    $c_count = intval($fr['total_neat_cattle']);
    if ($c_count <= 0 && !empty($fr['neat_cattle_data'])) {
        $c_json = is_string($fr['neat_cattle_data']) ? json_decode($fr['neat_cattle_data'], true) : $fr['neat_cattle_data'];
        if (is_array($c_json)) {
            foreach ($c_json as $sub) {
                if (is_array($sub)) {
                    foreach ($sub as $val) $c_count += intval($val);
                }
            }
        }
    }
    if ($c_count > 0 || stripos($fr['farm_type'] ?? '', 'cattle') !== false) {
        $cattle_farmers_count++;
    }

    // Check Buffalo
    $b_count = intval($fr['total_buffaloes']);
    if ($b_count <= 0 && !empty($fr['buffaloes_data'])) {
        $b_json = is_string($fr['buffaloes_data']) ? json_decode($fr['buffaloes_data'], true) : $fr['buffaloes_data'];
        if (is_array($b_json)) {
            foreach ($b_json as $sub) {
                if (is_array($sub)) {
                    foreach ($sub as $val) $b_count += intval($val);
                }
            }
        }
    }
    if ($b_count > 0 || stripos($fr['farm_type'] ?? '', 'buffalo') !== false) {
        $buffalo_farmers_count++;
    }

    // Check Poultry & Specialty Sub-categories
    $is_poultry = (stripos($fr['farm_type'] ?? '', 'poultry') !== false);
    $p_json = [];
    if (!empty($fr['poultry_data']) && $fr['poultry_data'] !== '[]' && $fr['poultry_data'] !== 'null') {
        $p_json = is_string($fr['poultry_data']) ? json_decode($fr['poultry_data'], true) : $fr['poultry_data'];
        if (is_array($p_json) && !empty($p_json)) {
            $is_poultry = true;
        } else {
            $p_json = [];
        }
    }

    if ($is_poultry) {
        $poultry_farmers_count++;

        $p_ft = trim($p_json['poultry_farm_type'] ?? ($fr['farm_type'] ?? ''));

        // 1. Broiler Farmers
        $is_broiler = false;
        if (stripos($p_ft, 'broiler') !== false) {
            $is_broiler = true;
        } elseif (!empty($p_json['purchase_animals']['broiler']) && is_array($p_json['purchase_animals']['broiler'])) {
            foreach ($p_json['purchase_animals']['broiler'] as $pv) {
                if (!empty($pv)) { $is_broiler = true; break; }
            }
        }
        if (!$is_broiler && !empty($p_json['prod']['avg_weight_broiler'])) {
            $is_broiler = true;
        }
        if ($is_broiler) {
            $poultry_specialty_farmers['Broiler Farmers'][$f_id] = true;
        }

        // 2. Layer Farmers
        $is_layer = false;
        if (stripos($p_ft, 'layer') !== false) {
            $is_layer = true;
        } elseif (!empty($p_json['purchase_animals']['layer']) && is_array($p_json['purchase_animals']['layer'])) {
            foreach ($p_json['purchase_animals']['layer'] as $pv) {
                if (!empty($pv)) { $is_layer = true; break; }
            }
        }
        if (!$is_layer && (!empty($p_json['prod']['avg_egg_hen_year']) || !empty($p_json['marketing']['table_eggs']['sold_qty']))) {
            $is_layer = true;
        }
        if ($is_layer) {
            $poultry_specialty_farmers['Layer Farmers'][$f_id] = true;
        }

        // 3. Free-Ranging / Local Farmers
        $is_local = false;
        if (stripos($p_ft, 'local') !== false || stripos($p_ft, 'free') !== false) {
            $is_local = true;
        } elseif (!empty($p_json['purchase_animals']['local_free']) && is_array($p_json['purchase_animals']['local_free'])) {
            foreach ($p_json['purchase_animals']['local_free'] as $pv) {
                if (!empty($pv)) { $is_local = true; break; }
            }
        }
        if ($is_local) {
            $poultry_specialty_farmers['Free-Ranging / Local Farmers'][$f_id] = true;
        }

        // Default fallback if poultry registered but no specialty selected: assign Broiler or Free-Ranging
        if (!$is_broiler && !$is_layer && !$is_local) {
            if (stripos($p_ft, 'other') !== false) {
                $poultry_specialty_farmers['Free-Ranging / Local Farmers'][$f_id] = true;
            } else {
                $poultry_specialty_farmers['Broiler Farmers'][$f_id] = true;
            }
        }

        // 4. Farmers involved in Feed Manufacturing
        $is_feed_mfg = false;
        if (($p_json['daph_feed_mfr_registered'] ?? '') === 'Yes' || !empty($p_json['daph_feed_mfr_reg_no'])) {
            $is_feed_mfg = true;
            $daph_registered_feed_mfr_count++;
        }
        if (!empty($p_json['feed_mfg']) && is_array($p_json['feed_mfg'])) {
            foreach ($p_json['feed_mfg'] as $mfg_entry) {
                $prod_qty = floatval($mfg_entry['monthly_prod'] ?? 0);
                if ($prod_qty > 0) {
                    $is_feed_mfg = true;
                    $unit = strtolower($mfg_entry['unit'] ?? 'kg');
                    $total_poultry_monthly_feed_kg += ($unit === 'mt' ? $prod_qty * 1000 : $prod_qty);
                }
            }
        }
        if (!$is_feed_mfg && !empty($p_json['feed_method']) && is_array($p_json['feed_method'])) {
            foreach ($p_json['feed_method'] as $fm_row) {
                if (!empty($fm_row['self_prep'])) {
                    $is_feed_mfg = true;
                    break;
                }
            }
        }
        if ($is_feed_mfg) {
            $poultry_specialty_farmers['Farmers involved in Feed Manufacturing'][$f_id] = true;
        }

        // Flock Age Groups & Headcounts
        if (!empty($p_json['flock_age_groups']) && is_array($p_json['flock_age_groups'])) {
            foreach ($p_json['flock_age_groups'] as $fg_item) {
                $qty = intval($fg_item['quantity'] ?? 0);
                if ($qty > 0) {
                    $total_poultry_birds += $qty;
                    $grp_name = trim($fg_item['age_group'] ?? '');
                    if (stripos($grp_name, '<8') !== false || stripos($grp_name, 'chick') !== false) {
                        $flock_age_totals['Chicks (<8 weeks)'] += $qty;
                    } elseif (stripos($grp_name, '8-17') !== false || stripos($grp_name, 'grower') !== false) {
                        $flock_age_totals['Growers (8-17 weeks old)'] += $qty;
                    } elseif (stripos($grp_name, '>18') !== false || stripos($grp_name, 'hen') !== false) {
                        $flock_age_totals['Hens (>18 Weeks)'] += $qty;
                    } else {
                        $flock_age_totals['Other / Unclassified'] += $qty;
                    }
                }
            }
        }
    }

    // Milk volume buckets (<5L, 5-10L, 10-20L, 20-40L, 40-100L, >100L)
    $m_vol = floatval($fr['daily_milk_production']);
    if ($m_vol <= 0 && !empty($fr['milk_data'])) {
        $m_json = is_string($fr['milk_data']) ? json_decode($fr['milk_data'], true) : $fr['milk_data'];
        if (is_array($m_json) && isset($m_json['total_production'])) {
            $m_vol = floatval($m_json['total_production']['cow'] ?? 0) + floatval($m_json['total_production']['buffalo'] ?? 0);
        }
    }
    if ($m_vol > 0) {
        $total_dairy_farmers++;
        if ($m_vol < 5.0) {
            $milk_buckets['<5L']++;
        } elseif ($m_vol <= 10.0) {
            $milk_buckets['5-10L']++;
        } elseif ($m_vol <= 20.0) {
            $milk_buckets['10-20L']++;
        } elseif ($m_vol <= 40.0) {
            $milk_buckets['20-40L']++;
        } elseif ($m_vol <= 100.0) {
            $milk_buckets['40-100L']++;
        } else {
            $milk_buckets['>100L']++;
        }
    }
}

$poultry_specialty_counts = [
    'Broiler Farmers' => count($poultry_specialty_farmers['Broiler Farmers']),
    'Layer Farmers' => count($poultry_specialty_farmers['Layer Farmers']),
    'Free-Ranging / Local Farmers' => count($poultry_specialty_farmers['Free-Ranging / Local Farmers']),
    'Farmers involved in Feed Manufacturing' => count($poultry_specialty_farmers['Farmers involved in Feed Manufacturing'])
];


// Fodder Cultivation Summary
$fodder_crops_summary = [];
$fodder_cultivating_farmers_set = [];
$total_fodder_land_area = 0.0;
$total_fodder_yield_amount = 0.0;

if (!empty($renewal_ids)) {
    $id_list_str = implode(',', $renewal_ids);
    // Query child table farm_registration_fodder_items
    $fod_query = "
        SELECT farm_registration_id, crop_item, other_specify, amount_perches, yield_amount_kg 
        FROM farm_registration_fodder_items 
        WHERE farm_registration_id IN ($id_list_str)
        ORDER BY crop_item ASC
    ";
    $fod_res = $mysqli->query($fod_query);
    $has_child_fodder = false;
    if ($fod_res && $fod_res->num_rows > 0) {
        $has_child_fodder = true;
        while ($frow = $fod_res->fetch_assoc()) {
            $crop = trim($frow['crop_item']);
            if (empty($crop)) continue;
            if ($crop === 'Other' && !empty($frow['other_specify'])) {
                $crop = 'Other (' . trim($frow['other_specify']) . ')';
            }
            $perches = floatval($frow['amount_perches']);
            $yield_kg = floatval($frow['yield_amount_kg']);
            $f_id = intval($frow['farm_registration_id']);

            // If yield is 0 in child table, check if parent fodder_data JSON has yield_amount
            if ($yield_kg <= 0) {
                foreach ($farm_renewals as $pRen) {
                    if (intval($pRen['id']) === $f_id && !empty($pRen['fodder_data'])) {
                        $p_fod = is_string($pRen['fodder_data']) ? json_decode($pRen['fodder_data'], true) : $pRen['fodder_data'];
                        if (is_array($p_fod)) {
                            foreach ($p_fod as $pItem) {
                                if (($pItem['item'] ?? '') === $frow['crop_item'] && !empty($pItem['yield_amount'])) {
                                    $yield_kg = floatval($pItem['yield_amount']);
                                    break;
                                }
                            }
                        }
                    }
                }
            }

            $fodder_cultivating_farmers_set[$f_id] = true;
            $total_fodder_land_area += $perches;
            $total_fodder_yield_amount += $yield_kg;

            if (!isset($fodder_crops_summary[$crop])) {
                $fodder_crops_summary[$crop] = [
                    'crop_name' => $crop,
                    'farmers' => [],
                    'land_area' => 0.0,
                    'yield_amount' => 0.0
                ];
            }
            $fodder_crops_summary[$crop]['farmers'][$f_id] = true;
            $fodder_crops_summary[$crop]['land_area'] += $perches;
            $fodder_crops_summary[$crop]['yield_amount'] += $yield_kg;
        }
    }

    // Fallback to JSON fodder_data if child table empty
    if (!$has_child_fodder) {
        foreach ($farm_renewals as $pRen) {
            $f_id = intval($pRen['id']);
            if (!empty($pRen['fodder_data'])) {
                $p_fod = is_string($pRen['fodder_data']) ? json_decode($pRen['fodder_data'], true) : $pRen['fodder_data'];
                if (is_array($p_fod)) {
                    foreach ($p_fod as $pItem) {
                        $crop = trim($pItem['item'] ?? '');
                        if (empty($crop)) continue;
                        if ($crop === 'Other' && !empty($pItem['other_specify'])) {
                            $crop = 'Other (' . trim($pItem['other_specify']) . ')';
                        }
                        $perches = floatval($pItem['amount'] ?? ($pItem['land_area'] ?? 0));
                        $yield_kg = floatval($pItem['yield_amount'] ?? 0);

                        $fodder_cultivating_farmers_set[$f_id] = true;
                        $total_fodder_land_area += $perches;
                        $total_fodder_yield_amount += $yield_kg;

                        if (!isset($fodder_crops_summary[$crop])) {
                            $fodder_crops_summary[$crop] = [
                                'crop_name' => $crop,
                                'farmers' => [],
                                'land_area' => 0.0,
                                'yield_amount' => 0.0
                            ];
                        }
                        $fodder_crops_summary[$crop]['farmers'][$f_id] = true;
                        $fodder_crops_summary[$crop]['land_area'] += $perches;
                        $fodder_crops_summary[$crop]['yield_amount'] += $yield_kg;
                    }
                }
            }
        }
    }
}
$total_cultivating_farmers = count($fodder_cultivating_farmers_set);

// ==========================================
// AUTOMATED SUMMARY CALCULATOR & AGGREGATOR
// ==========================================
$prod_summary_year = isset($_GET['prod_year']) ? ($_GET['prod_year'] === 'all' ? 'all' : intval($_GET['prod_year'])) : intval(date('Y'));

// 1. Milk Collection Aggregator
$milk_summary = ['centers' => 0, 'cow_milk' => 0, 'buffalo_milk' => 0, 'goat_milk' => 0, 'total_milk' => 0, 'chilling_cap' => 0];
$milk_query = "
    SELECT 
        COUNT(*) as centers,
        COALESCE(SUM(cow_milk_lit_month), 0) as cow_milk,
        COALESCE(SUM(buffalo_milk_lit_month), 0) as buffalo_milk,
        COALESCE(SUM(goat_milk_lit_month), 0) as goat_milk,
        COALESCE(SUM(milk_collection_lit_per_month), 0) as total_milk,
        COALESCE(SUM(milk_chilling_capacity), 0) as chilling_cap
    FROM milk_collecting_centers
    WHERE (range_id = ? OR vs_range = ?) " . ($prod_summary_year !== 'all' ? "AND report_year = ?" : "");
$stmt_milk = $mysqli->prepare($milk_query);
if ($stmt_milk) {
    if ($prod_summary_year !== 'all') {
        $stmt_milk->bind_param("isi", $range_id, $range_name, $prod_summary_year);
    } else {
        $stmt_milk->bind_param("is", $range_id, $range_name);
    }
    $stmt_milk->execute();
    $res_milk = $stmt_milk->get_result();
    if ($row_milk = $res_milk->fetch_assoc()) {
        $milk_summary = $row_milk;
    }
    $stmt_milk->close();
}

// 2. Feed Production Mills Aggregator
$feed_summary = ['mills' => 0, 'total_mt' => 0, 'categories' => []];
$feed_query = "
    SELECT 
        COUNT(DISTINCT feed_mill_name) as mills,
        COALESCE(SUM(produced_qty_mt_month), 0) as total_mt
    FROM annual_feed_production
    WHERE (range_id = ?) " . ($prod_summary_year !== 'all' ? "AND report_year = ?" : "");
$stmt_feed = $mysqli->prepare($feed_query);
if ($stmt_feed) {
    if ($prod_summary_year !== 'all') {
        $stmt_feed->bind_param("ii", $range_id, $prod_summary_year);
    } else {
        $stmt_feed->bind_param("i", $range_id);
    }
    $stmt_feed->execute();
    $res_feed = $stmt_feed->get_result();
    if ($row_feed = $res_feed->fetch_assoc()) {
        $feed_summary['mills'] = $row_feed['mills'];
        $feed_summary['total_mt'] = $row_feed['total_mt'];
    }
    $stmt_feed->close();
}

// Feed Categories
$feed_cat_query = "
    SELECT category_type, COALESCE(SUM(produced_qty_mt_month), 0) as cat_mt
    FROM annual_feed_production
    WHERE (range_id = ?) " . ($prod_summary_year !== 'all' ? "AND report_year = ?" : "") . "
    GROUP BY category_type";
$stmt_fcat = $mysqli->prepare($feed_cat_query);
if ($stmt_fcat) {
    if ($prod_summary_year !== 'all') {
        $stmt_fcat->bind_param("ii", $range_id, $prod_summary_year);
    } else {
        $stmt_fcat->bind_param("i", $range_id);
    }
    $stmt_fcat->execute();
    $res_fcat = $stmt_fcat->get_result();
    while ($rf = $res_fcat->fetch_assoc()) {
        $feed_summary['categories'][$rf['category_type']] = floatval($rf['cat_mt']);
    }
    $stmt_fcat->close();
}

// 3. Production Outlets (Producers & Processors) Aggregator
$outlets_summary = ['chicks' => 0, 'live_chicken_kg' => 0, 'dressed_chicken_kg' => 0, 'organic_fert_mt' => 0, 'organic_families' => 0];
$outlets_query = "
    SELECT 
        COALESCE(SUM(chicks_produced_month), 0) as chicks,
        COALESCE(SUM(chicken_sale_live_kg_month), 0) as live_chicken_kg,
        COALESCE(SUM(chicken_sale_dressed_kg_month), 0) as dressed_chicken_kg,
        COALESCE(SUM(organic_fert_prod_mt_year), 0) as organic_fert_mt,
        COALESCE(SUM(organic_fert_farm_families), 0) as organic_families
    FROM annual_producers_processors
    WHERE (range_id = ?) " . ($prod_summary_year !== 'all' ? "AND report_year = ?" : "");
$stmt_out = $mysqli->prepare($outlets_query);
if ($stmt_out) {
    if ($prod_summary_year !== 'all') {
        $stmt_out->bind_param("ii", $range_id, $prod_summary_year);
    } else {
        $stmt_out->bind_param("i", $range_id);
    }
    $stmt_out->execute();
    $res_out = $stmt_out->get_result();
    if ($row_out = $res_out->fetch_assoc()) {
        $outlets_summary = $row_out;
    }
    $stmt_out->close();
}

// 4. Meat Sales Aggregator
$meat_summary = ['total_kg' => 0, 'total_revenue' => 0, 'categories' => [], 'others_detail' => []];
$meat_query = "
    SELECT 
        meat_type,
        other_meat_name,
        COALESCE(SUM(sales_volume_kg), 0) as total_kg,
        COALESCE(SUM(total_sales_amount), 0) as total_revenue
    FROM meat_sales_records
    WHERE (range_id = ? OR vs_range = ?) " . ($prod_summary_year !== 'all' ? "AND report_year = ?" : "") . "
    GROUP BY meat_type, other_meat_name";
$stmt_meat = $mysqli->prepare($meat_query);
if ($stmt_meat) {
    if ($prod_summary_year !== 'all') {
        $stmt_meat->bind_param("isi", $range_id, $range_name, $prod_summary_year);
    } else {
        $stmt_meat->bind_param("is", $range_id, $range_name);
    }
    $stmt_meat->execute();
    $res_meat = $stmt_meat->get_result();
    while ($rm = $res_meat->fetch_assoc()) {
        $m_type = $rm['meat_type'];
        $m_display_type = ($m_type === 'Chicken') ? 'Poultry' : $m_type;
        $m_kg = floatval($rm['total_kg']);
        $m_rev = floatval($rm['total_revenue']);
        $meat_summary['total_kg'] += $m_kg;
        $meat_summary['total_revenue'] += $m_rev;
        $meat_summary['categories'][$m_display_type] = ($meat_summary['categories'][$m_display_type] ?? 0) + $m_kg;
        if ($m_type === 'Other' && !empty($rm['other_meat_name'])) {
            $oname = trim($rm['other_meat_name']);
            $meat_summary['others_detail'][$oname] = ($meat_summary['others_detail'][$oname] ?? 0) + $m_kg;
        }
    }
    $stmt_meat->close();
}

// Available production summary years
$available_prod_years = array_unique(array_merge($pop_years, [intval(date('Y')), intval(date('Y')) - 1, 2025, 2024]));
rsort($available_prod_years);

require_once '../../../includes/header.php';
?>

<link rel="stylesheet" href="../../../assets/css/bootstrap-icons.min.css">
<link rel="stylesheet" href="../../../assets/css/veterinary.css">

        <!-- Header with Range Selector Dropdown -->
        <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-3">
            <div>
                <h2 class="h4 mb-0 fw-bold" style="color: #370709;">Range Statistics & Overview</h2>
                <small class="text-muted"><i class="bi bi-geo-alt-fill text-danger me-1"></i><?= htmlspecialchars($range_name) ?> (<?= htmlspecialchars($district_name) ?>) | DAPH Eastern Province</small>
            </div>
            <div class="d-flex align-items-center gap-2 flex-wrap">
                <form method="GET" class="d-flex align-items-center gap-2 m-0 bg-white p-2 rounded-3 border shadow-xs">
                    <label class="form-label small fw-bold text-secondary mb-0 text-nowrap"><i class="bi bi-buildings text-primary me-1"></i>Veterinary Range:</label>
                    <select name="range_id" class="form-select form-select-sm fw-semibold" onchange="this.form.submit()" style="min-width: 220px;">
                        <option value="<?= $range_id ?>"><?= htmlspecialchars($range_name) ?></option>
                    </select>
                </form>
                <a href="range_details.php<?= $requested_range_id ? '?range_id=' . $requested_range_id : ($range_id ? '?range_id=' . $range_id : '') ?>" class="btn btn-secondary shadow-sm text-nowrap">
                    <i class="bi bi-arrow-left me-2"></i>Back
                </a>
            </div>
        </div>

        <?php if (isset($_SESSION['msg'])): ?>
            <div class="alert alert-<?= htmlspecialchars($_SESSION['msg_type'] ?? 'info') ?> alert-dismissible fade show mb-4 shadow-sm" role="alert">
                <?= $_SESSION['msg'] ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            <?php unset($_SESSION['msg'], $_SESSION['msg_type']); ?>
        <?php endif; ?>

        <!-- Human Population Dynamics section -->
        <div class="row g-4 mb-5 mt-2">
            <div class="col-12">
                <div class="card gov-card">
                    <div class="card-header bg-white pt-4 px-4 border-0 d-flex justify-content-between align-items-center flex-wrap gap-2">
                        <div>
                            <h5 class="fw-bold mb-1" style="color: #370709;"><i class="bi bi-people-fill me-2"></i>Human Population</h5>
                            <p class="text-muted small mb-0">Demographic composition tracking and sector breakdown analytics from database.</p>
                        </div>
                        <div>
                            <button type="button" class="btn btn-sm btn-dark fw-bold shadow-sm" data-bs-toggle="modal" data-bs-target="#manageHumanPopulationModal">
                                <i class="bi bi-gear-fill me-1"></i> Manage Population
                            </button>
                        </div>
                    </div>
                    <div class="card-body px-4 pb-4">

                        <div class="row g-3 mb-4 p-3 rounded text-dark" style="background-color: #f8fafc; border: 1px solid #e2e8f0;">
                            <div class="col-12 col-md-4">
                                <label class="form-label small fw-bold text-secondary">Year Selection</label>
                                <select id="filterYear" class="form-select form-select-sm filter-control">
                                    <?php foreach ($pop_years as $py): ?>
                                        <option value="<?= $py ?>" <?= $py === 2025 ? 'selected' : '' ?>><?= $py ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="col-12 col-md-4">
                                <label class="form-label small fw-bold text-secondary">Ethnicity Focus</label>
                                <div class="position-relative" id="ethnicityDropdownWrapper">
                                    <button type="button"
                                        class="form-select form-select-sm text-start filter-control"
                                        id="ethnicityDropdownBtn"
                                        aria-expanded="false">
                                        All Ethnicities
                                    </button>
                                    <div class="ethnicity-dropdown-menu" id="ethnicityDropdownMenu">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" value="All" id="ethAll" checked>
                                            <label class="form-check-label small fw-bold" for="ethAll">All</label>
                                        </div>
                                        <hr class="dropdown-divider my-1">
                                        <div class="form-check">
                                            <input class="form-check-input ethnicity-option" type="checkbox" value="Sinhala" id="ethSinhala" checked>
                                            <label class="form-check-label small" for="ethSinhala">Sinhala</label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input ethnicity-option" type="checkbox" value="Tamil" id="ethTamil" checked>
                                            <label class="form-check-label small" for="ethTamil">Tamil</label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input ethnicity-option" type="checkbox" value="Muslim" id="ethMuslim" checked>
                                            <label class="form-check-label small" for="ethMuslim">Muslim</label>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="col-12 col-md-4">
                                <label class="form-label small fw-bold text-secondary">Population Type Metric</label>
                                <select id="filterPopType" class="form-select form-select-sm filter-control">
                                    <option value="Total Population" selected>Total Population (Male + Female)</option>
                                    <option value="Male">Male Only</option>
                                    <option value="Female">Female Only</option>
                                    <option value="Households">Households Count</option>
                                </select>
                            </div>
                        </div>

                        <div class="row g-4 mb-4">
                            <div class="col-12 col-lg-5 d-flex justify-content-center align-items-center position-relative">
                                <div style="position: relative; width: 100%; max-width: 320px; height: 320px;">
                                    <canvas id="humanPopulationPieChart"></canvas>
                                </div>
                            </div>
                            <div class="col-12 col-lg-7">
                                <div class="table-responsive">
                                    <table id="humanPopulationTable" class="table table-striped table-hover table-bordered align-middle w-100 m-0">
                                        <thead class="table-light text-secondary small">
                                            <tr>
                                                <th>Year</th>
                                                <th>Ethnicity</th>
                                                <th>Population Split</th>
                                                <th>Total Population Group</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>
            </div>
        </div>

        <!-- Animal Population Dynamics section -->
        <div class="row g-4 mb-5 mt-2">
            <div class="col-12">
                <div class="card gov-card">
                    <div class="card-header bg-white pt-4 px-4 border-0 d-flex justify-content-between align-items-center flex-wrap gap-2">
                        <div>
                            <h5 class="fw-bold mb-1" style="color: #370709;"><i class="bi bi-shield-shaded me-2"></i>Animal Population</h5>
                            <p class="text-muted small mb-0">Live aggregated livestock demographics dynamically synchronized with the Master Registry (Farm Registrations).</p>
                        </div>
                        <div class="d-flex align-items-center gap-2">
                            <span class="badge bg-success-subtle text-success border border-success-subtle px-3 py-2 fw-semibold">
                                <i class="bi bi-broadcast me-1"></i>Automated Live Registry Sync
                            </span>
                        </div>
                    </div>
                    <div class="card-body px-4 pb-4">

                        <div class="row g-3 mb-4 p-3 rounded text-dark" style="background-color: #f8fafc; border: 1px solid #e2e8f0;">
                            <div class="col-12 col-md-4">
                                <label class="form-label small fw-bold text-secondary">Year Selection</label>
                                <select id="filterYearAnimal" class="form-select form-select-sm filter-control-animal">
                                    <?php 
                                    $default_ay = $animal_pop_years[0] ?? intval(date('Y'));
                                    foreach ($animal_pop_years as $apy): 
                                    ?>
                                        <option value="<?= $apy ?>" <?= $apy === $default_ay ? 'selected' : '' ?>><?= $apy ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="col-12 col-md-4">
                                <label class="form-label small fw-bold text-secondary">Livestock Category Focus</label>
                                <div class="position-relative" id="animalDropdownWrapper">
                                    <button type="button"
                                        class="form-select form-select-sm text-start bg-white"
                                        id="animalDropdownBtn"
                                        aria-expanded="false">
                                        All Animals Selected (6)
                                    </button>
                                    <div class="animal-dropdown-menu p-3 border shadow-sm bg-white rounded position-absolute" id="animalDropdownMenu">
                                        <div class="form-check mb-1">
                                            <input class="form-check-input" type="checkbox" value="All" id="animAll" checked>
                                            <label class="form-check-label small fw-bold" for="animAll">All</label>
                                        </div>
                                        <hr class="dropdown-divider my-1">
                                        <div class="form-check mb-1">
                                            <input class="form-check-input animal-option" type="checkbox" value="Cattle" id="animCattle" checked>
                                            <label class="form-check-label small" for="animCattle">Cattle</label>
                                        </div>
                                        <div class="form-check mb-1">
                                            <input class="form-check-input animal-option" type="checkbox" value="Buffaloes" id="animBuffaloes" checked>
                                            <label class="form-check-label small" for="animBuffaloes">Buffaloes</label>
                                        </div>
                                        <div class="form-check mb-1">
                                            <input class="form-check-input animal-option" type="checkbox" value="Goats" id="animGoats" checked>
                                            <label class="form-check-label small" for="animGoats">Goats</label>
                                        </div>
                                        <div class="form-check mb-1">
                                            <input class="form-check-input animal-option" type="checkbox" value="Sheep" id="animSheep" checked>
                                            <label class="form-check-label small" for="animSheep">Sheep</label>
                                        </div>
                                        <div class="form-check mb-1">
                                            <input class="form-check-input animal-option" type="checkbox" value="Swine" id="animSwine" checked>
                                            <label class="form-check-label small" for="animSwine">Swine</label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input animal-option" type="checkbox" value="Poultry" id="animPoultry" checked>
                                            <label class="form-check-label small" for="animPoultry">Poultry</label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="row g-4 mb-4">
                            <div class="col-12 col-lg-5 d-flex justify-content-center align-items-center position-relative">
                                <div style="position: relative; width: 100%; max-width: 320px; height: 320px;">
                                    <canvas id="animalPopulationPieChart"></canvas>
                                </div>
                            </div>
                            <div class="col-12 col-lg-7">
                                <div class="table-responsive">
                                    <table id="animalPopulationTable" class="table table-striped table-hover table-bordered align-middle w-100 m-0">
                                        <thead class="table-light text-secondary small">
                                            <tr>
                                                <th>Year</th>
                                                <th>Animal Type</th>
                                                <th>Count Split</th>
                                                <th>Total Selected Group</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>
            </div>
        </div>

        <!-- ============================================================== -->
        <!-- NEW FARM STATISTICS VISUALIZATIONS & FODDER CULTIVATION        -->
        <!-- ============================================================== -->
        <div class="row g-4 mb-4">
            <!-- New Chart 1: Farmers by Animal Type (Cattle, Buffalo, Poultry) -->
            <div class="col-12 col-lg-6">
                <div class="card gov-card h-100 shadow-sm border-0">
                    <div class="card-header bg-white pt-4 px-4 border-0 d-flex justify-content-between align-items-center flex-wrap gap-2">
                        <div>
                            <h5 class="fw-bold mb-1" style="color: #370709;">
                                <i class="bi bi-people-fill text-danger me-2"></i>Farmers by Animal Type
                            </h5>
                            <p class="text-muted small mb-0">Total farmers rearing specific livestock (Cattle, Buffalo, Poultry).</p>
                        </div>
                        <span class="badge bg-danger-subtle text-danger border border-danger-subtle px-3 py-1 fw-semibold">
                            Live Master Registry
                        </span>
                    </div>
                    <div class="card-body px-4 pb-4 d-flex flex-column">
                        <div class="position-relative flex-grow-1" style="min-height: 280px; max-height: 320px; height: 280px; width: 100%;">
                            <canvas id="farmersByAnimalChart" style="display: block; width: 100% !important; height: 100% !important;"></canvas>
                        </div>
                        <div class="row g-2 mt-3 pt-3 border-top text-center">
                            <div class="col-4">
                                <div class="p-2 rounded bg-light border">
                                    <small class="text-muted d-block text-uppercase fw-semibold" style="font-size: 0.72rem;">Cattle</small>
                                    <span class="fw-bold fs-6 text-dark"><?= number_format($cattle_farmers_count) ?></span>
                                    <small class="text-secondary d-block" style="font-size: 0.7rem;">Farmers</small>
                                </div>
                            </div>
                            <div class="col-4">
                                <div class="p-2 rounded bg-light border">
                                    <small class="text-muted d-block text-uppercase fw-semibold" style="font-size: 0.72rem;">Buffalo</small>
                                    <span class="fw-bold fs-6 text-dark"><?= number_format($buffalo_farmers_count) ?></span>
                                    <small class="text-secondary d-block" style="font-size: 0.7rem;">Farmers</small>
                                </div>
                            </div>
                            <div class="col-4">
                                <div class="p-2 rounded bg-light border">
                                    <small class="text-muted d-block text-uppercase fw-semibold" style="font-size: 0.72rem;">Poultry</small>
                                    <span class="fw-bold fs-6 text-dark"><?= number_format($poultry_farmers_count) ?></span>
                                    <small class="text-secondary d-block" style="font-size: 0.7rem;">Farmers</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- New Chart 2: Milk Production Volume Distribution -->
            <div class="col-12 col-lg-6">
                <div class="card gov-card h-100 shadow-sm border-0">
                    <div class="card-header bg-white pt-4 px-4 border-0 d-flex justify-content-between align-items-center flex-wrap gap-2">
                        <div>
                            <h5 class="fw-bold mb-1" style="color: #370709;">
                                <i class="bi bi-droplet-half text-primary me-2"></i>Milk Production Volume
                            </h5>
                            <p class="text-muted small mb-0">Daily milk production volume buckets across registered dairy farmers.</p>
                        </div>
                        <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-3 py-1 fw-semibold">
                            <?= number_format($total_dairy_farmers) ?> Active Producers
                        </span>
                    </div>
                    <div class="card-body px-4 pb-4 d-flex flex-column">
                        <div class="position-relative flex-grow-1" style="min-height: 280px; max-height: 320px; height: 280px; width: 100%;">
                            <canvas id="milkVolumeBucketsChart" style="display: block; width: 100% !important; height: 100% !important;"></canvas>
                        </div>
                        <div class="row g-2 mt-3 pt-3 border-top text-center" style="font-size: 0.72rem;">
                            <?php foreach ($milk_buckets as $bucketLabel => $bucketCount): ?>
                                <div class="col-4 col-md-2">
                                    <div class="p-1 rounded bg-light border">
                                        <span class="text-muted d-block text-truncate" title="<?= htmlspecialchars($bucketLabel) ?>"><?= htmlspecialchars($bucketLabel) ?></span>
                                        <span class="fw-bold text-dark"><?= number_format($bucketCount) ?></span>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Fodder Cultivation Summary Block & Table -->
        <div class="row g-4 mb-5">
            <div class="col-12">
                <div class="card gov-card shadow-sm border-0">
                    <div class="card-header bg-white pt-4 px-4 border-0 d-flex justify-content-between align-items-center flex-wrap gap-2">
                        <div>
                            <h5 class="fw-bold mb-1" style="color: #370709;">
                                <i class="bi bi-flower1 text-success me-2"></i>Fodder / Pasture Cultivation Summary
                            </h5>
                            <p class="text-muted small mb-0">Aggregated land area and yield figures pulled directly from farm pasture cultivation records.</p>
                        </div>
                        <span class="badge bg-success-subtle text-success border border-success-subtle px-3 py-2 fw-semibold">
                            <i class="bi bi-check2-circle me-1"></i>Cultivator Registry Summary
                        </span>
                    </div>
                    <div class="card-body px-4 pb-4">
                        <!-- KPI Metric Highlights -->
                        <div class="row g-3 mb-4">
                            <div class="col-12 col-md-4">
                                <div class="card border-0 shadow-sm rounded-3 p-3 h-100" style="background: linear-gradient(135deg, #f0fdf4 0%, #dcfce7 100%); border-left: 4px solid #16a34a !important;">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <span class="text-uppercase fw-bold small text-success">Cultivating Farmers</span>
                                        <i class="bi bi-people fs-4 text-success opacity-75"></i>
                                    </div>
                                    <h3 class="fw-bold my-2 text-dark">
                                        <?= number_format($total_cultivating_farmers) ?> <small class="fs-6 text-muted">Farmers</small>
                                    </h3>
                                    <small class="text-muted">Distinct farmers registered with fodder cultivation</small>
                                </div>
                            </div>
                            <div class="col-12 col-md-4">
                                <div class="card border-0 shadow-sm rounded-3 p-3 h-100" style="background: linear-gradient(135deg, #ecfeff 0%, #cffafe 100%); border-left: 4px solid #0891b2 !important;">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <span class="text-uppercase fw-bold small text-info" style="color: #0891b2 !important;">Total Cultivated Land Area</span>
                                        <i class="bi bi-aspect-ratio fs-4 text-info opacity-75"></i>
                                    </div>
                                    <h3 class="fw-bold my-2 text-dark">
                                        <?= number_format($total_fodder_land_area, 1) ?> <small class="fs-6 text-muted">Perches</small>
                                    </h3>
                                    <small class="text-muted">Equivalent to <strong><?= number_format($total_fodder_land_area / 160, 2) ?></strong> Acres (160 Perches = 1 Acre)</small>
                                </div>
                            </div>
                            <div class="col-12 col-md-4">
                                <div class="card border-0 shadow-sm rounded-3 p-3 h-100" style="background: linear-gradient(135deg, #fefce8 0%, #fef9c3 100%); border-left: 4px solid #ca8a04 !important;">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <span class="text-uppercase fw-bold small text-warning" style="color: #ca8a04 !important;">Total Estimated Yield</span>
                                        <i class="bi bi-basket fs-4 text-warning opacity-75"></i>
                                    </div>
                                    <h3 class="fw-bold my-2 text-dark">
                                        <?= number_format($total_fodder_yield_amount, 1) ?> <small class="fs-6 text-muted">kg</small>
                                    </h3>
                                    <small class="text-muted">Total harvest biomass output (<strong><?= number_format($total_fodder_yield_amount / 1000, 2) ?></strong> MT)</small>
                                </div>
                            </div>
                        </div>

                        <!-- Breakdown Table by Crop / Pasture Variety -->
                        <div class="table-responsive">
                            <table class="table table-hover table-bordered align-middle w-100 m-0" style="border-color: #e2e8f0;">
                                <thead class="table-light text-secondary small">
                                    <tr style="background-color: #f8fafc;">
                                        <th class="py-3 px-3">Crop / Pasture Variety</th>
                                        <th class="py-3 px-3 text-center">Cultivating Farmers</th>
                                        <th class="py-3 px-3 text-end">Land Area (Perches)</th>
                                        <th class="py-3 px-3 text-end">Land Area (Acres)</th>
                                        <th class="py-3 px-3 text-end">Total Yield (kg)</th>
                                        <th class="py-3 px-3 text-end">Avg Yield / Perch (kg/P)</th>
                                        <th class="py-3 px-3 text-center">Share of Land Area</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (!empty($fodder_crops_summary)): ?>
                                        <?php foreach ($fodder_crops_summary as $fc): 
                                            $f_count = count($fc['farmers']);
                                            $c_area = floatval($fc['land_area']);
                                            $c_yield = floatval($fc['yield_amount']);
                                            $c_acres = $c_area / 160;
                                            $avg_y_perch = ($c_area > 0) ? ($c_yield / $c_area) : 0;
                                            $share_pct = ($total_fodder_land_area > 0) ? (($c_area / $total_fodder_land_area) * 100) : 0;
                                        ?>
                                            <tr>
                                                <td class="fw-bold text-dark px-3">
                                                    <i class="bi bi-patch-check-fill text-success me-2"></i><?= htmlspecialchars($fc['crop_name']) ?>
                                                </td>
                                                <td class="text-center">
                                                    <span class="badge bg-secondary-subtle text-secondary px-3 py-1 fw-semibold"><?= number_format($f_count) ?></span>
                                                </td>
                                                <td class="text-end fw-semibold text-dark px-3"><?= number_format($c_area, 1) ?></td>
                                                <td class="text-end text-muted px-3"><?= number_format($c_acres, 2) ?></td>
                                                <td class="text-end fw-semibold text-success px-3"><?= number_format($c_yield, 1) ?></td>
                                                <td class="text-end text-muted px-3"><?= number_format($avg_y_perch, 2) ?></td>
                                                <td class="text-center px-3">
                                                    <div class="d-flex align-items-center justify-content-center gap-2">
                                                        <div class="progress flex-grow-1" style="height: 6px; max-width: 80px;">
                                                            <div class="progress-bar bg-success" role="progressbar" style="width: <?= min(100, $share_pct) ?>%" aria-valuenow="<?= round($share_pct) ?>" aria-valuemin="0" aria-valuemax="100"></div>
                                                        </div>
                                                        <small class="text-muted" style="min-width: 40px;"><?= number_format($share_pct, 1) ?>%</small>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                        <tr class="table-light fw-bold border-top-2">
                                            <td class="px-3 text-uppercase">Total Aggregated</td>
                                            <td class="text-center"><?= number_format($total_cultivating_farmers) ?></td>
                                            <td class="text-end px-3"><?= number_format($total_fodder_land_area, 1) ?></td>
                                            <td class="text-end px-3"><?= number_format($total_fodder_land_area / 160, 2) ?></td>
                                            <td class="text-end px-3 text-success"><?= number_format($total_fodder_yield_amount, 1) ?></td>
                                            <td class="text-end px-3"><?= $total_fodder_land_area > 0 ? number_format($total_fodder_yield_amount / $total_fodder_land_area, 2) : '0.00' ?></td>
                                            <td class="text-center px-3">100.0%</td>
                                        </tr>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="7" class="text-center py-4 text-muted">
                                                <i class="bi bi-info-circle me-1"></i>No fodder / pasture cultivation records registered for this veterinary range yet.
                                            </td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Dedicated Poultry Statistics & Specialty Farmer Profiles Section -->
        <div class="row g-4 mb-5">
            <div class="col-12">
                <div class="card gov-card shadow-sm border-0">
                    <div class="card-header bg-white pt-4 px-4 border-0 d-flex justify-content-between align-items-center flex-wrap gap-2">
                        <div>
                            <h5 class="fw-bold mb-1" style="color: #370709;">
                                <i class="bi bi-egg-fill text-warning me-2"></i>Poultry Statistics &amp; Specialty Farmer Profiles
                            </h5>
                            <p class="text-muted small mb-0">
                                Dynamic analytics tracking specialty poultry farmers across Broiler, Layer, Free-Ranging/Local rearing, and Feed Manufacturing in <strong><?= htmlspecialchars($range_name) ?></strong> range.
                            </p>
                        </div>
                        <div class="d-flex align-items-center gap-2">
                            <span class="badge bg-warning-subtle text-dark border border-warning px-3 py-2 fw-semibold">
                                <i class="bi bi-patch-check-fill text-warning me-1"></i>Master Branding Registry
                            </span>
                            <span class="badge bg-light text-secondary border px-3 py-2 fw-semibold">
                                Total Poultry Farmers: <?= number_format($poultry_farmers_count) ?>
                            </span>
                        </div>
                    </div>

                    <div class="card-body px-4 pb-4">
                        <!-- 4 Top Specialty Metric Cards -->
                        <div class="row g-3 mb-4">
                            <!-- Broiler Farmers -->
                            <div class="col-12 col-sm-6 col-xl-3">
                                <div class="card border-0 shadow-sm rounded-3 p-3 h-100" style="background: linear-gradient(135deg, #fffbeb 0%, #fef3c7 100%); border-left: 4px solid #f59e0b !important;">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <span class="text-uppercase fw-bold small text-dark">Broiler Farmers</span>
                                        <i class="bi bi-fire fs-4 text-warning opacity-75"></i>
                                    </div>
                                    <h3 class="fw-bold my-2 text-dark">
                                        <?= number_format($poultry_specialty_counts['Broiler Farmers']) ?> <small class="fs-6 text-muted">Holdings</small>
                                    </h3>
                                    <div class="d-flex justify-content-between align-items-center small text-muted">
                                        <span>Commercial Meat Production</span>
                                        <span class="badge bg-warning text-dark fw-semibold">
                                            <?= $poultry_farmers_count > 0 ? round(($poultry_specialty_counts['Broiler Farmers'] / $poultry_farmers_count) * 100, 1) : 0 ?>%
                                        </span>
                                    </div>
                                </div>
                            </div>

                            <!-- Layer Farmers -->
                            <div class="col-12 col-sm-6 col-xl-3">
                                <div class="card border-0 shadow-sm rounded-3 p-3 h-100" style="background: linear-gradient(135deg, #faf5ff 0%, #f3e8ff 100%); border-left: 4px solid #8b5cf6 !important;">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <span class="text-uppercase fw-bold small" style="color: #7c3aed;">Layer Farmers</span>
                                        <i class="bi bi-egg-fried fs-4 text-purple opacity-75" style="color: #8b5cf6;"></i>
                                    </div>
                                    <h3 class="fw-bold my-2 text-dark">
                                        <?= number_format($poultry_specialty_counts['Layer Farmers']) ?> <small class="fs-6 text-muted">Holdings</small>
                                    </h3>
                                    <div class="d-flex justify-content-between align-items-center small text-muted">
                                        <span>Table &amp; Pullet Egg Sales</span>
                                        <span class="badge text-white fw-semibold" style="background-color: #8b5cf6;">
                                            <?= $poultry_farmers_count > 0 ? round(($poultry_specialty_counts['Layer Farmers'] / $poultry_farmers_count) * 100, 1) : 0 ?>%
                                        </span>
                                    </div>
                                </div>
                            </div>

                            <!-- Free-Ranging / Local Farmers -->
                            <div class="col-12 col-sm-6 col-xl-3">
                                <div class="card border-0 shadow-sm rounded-3 p-3 h-100" style="background: linear-gradient(135deg, #f0fdf4 0%, #dcfce7 100%); border-left: 4px solid #10b981 !important;">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <span class="text-uppercase fw-bold small text-success">Free-Range / Local</span>
                                        <i class="bi bi-tree fs-4 text-success opacity-75"></i>
                                    </div>
                                    <h3 class="fw-bold my-2 text-dark">
                                        <?= number_format($poultry_specialty_counts['Free-Ranging / Local Farmers']) ?> <small class="fs-6 text-muted">Holdings</small>
                                    </h3>
                                    <div class="d-flex justify-content-between align-items-center small text-muted">
                                        <span>Indigenous &amp; Backyard Flocks</span>
                                        <span class="badge bg-success text-white fw-semibold">
                                            <?= $poultry_farmers_count > 0 ? round(($poultry_specialty_counts['Free-Ranging / Local Farmers'] / $poultry_farmers_count) * 100, 1) : 0 ?>%
                                        </span>
                                    </div>
                                </div>
                            </div>

                            <!-- Farmers in Feed Manufacturing -->
                            <div class="col-12 col-sm-6 col-xl-3">
                                <div class="card border-0 shadow-sm rounded-3 p-3 h-100" style="background: linear-gradient(135deg, #eff6ff 0%, #dbeafe 100%); border-left: 4px solid #3b82f6 !important;">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <span class="text-uppercase fw-bold small text-primary">Feed Manufacturing</span>
                                        <i class="bi bi-gear-wide-connected fs-4 text-primary opacity-75"></i>
                                    </div>
                                    <h3 class="fw-bold my-2 text-dark">
                                        <?= number_format($poultry_specialty_counts['Farmers involved in Feed Manufacturing']) ?> <small class="fs-6 text-muted">Producers</small>
                                    </h3>
                                    <div class="d-flex justify-content-between align-items-center small text-muted">
                                        <span>DAPH Registered &amp; Self-Mix</span>
                                        <span class="badge bg-primary text-white fw-semibold">
                                            <?= $poultry_farmers_count > 0 ? round(($poultry_specialty_counts['Farmers involved in Feed Manufacturing'] / $poultry_farmers_count) * 100, 1) : 0 ?>%
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Visual Breakdown: Chart & Detailed Table -->
                        <div class="row g-4 align-items-stretch">
                            <!-- Doughnut Distribution Chart -->
                            <div class="col-12 col-lg-5">
                                <div class="p-3 border rounded-3 bg-light h-100 d-flex flex-column">
                                    <div class="d-flex justify-content-between align-items-center mb-3">
                                        <h6 class="fw-bold text-dark mb-0">
                                            <i class="bi bi-pie-chart-fill text-warning me-1"></i>Specialty Distribution
                                        </h6>
                                        <span class="badge bg-white text-secondary border small">By Specialty Category</span>
                                    </div>
                                    <div class="position-relative flex-grow-1" style="min-height: 260px; max-height: 290px; height: 260px; width: 100%;">
                                        <canvas id="poultrySpecialtyChart" style="display: block; width: 100% !important; height: 100% !important;"></canvas>
                                    </div>
                                    <div class="row g-2 mt-3 pt-3 border-top text-center" style="font-size: 0.75rem;">
                                        <div class="col-6 col-md-4">
                                            <div class="p-2 bg-white rounded border">
                                                <div class="text-muted small">Total Flock Headcount</div>
                                                <div class="fw-bold text-dark fs-6"><?= number_format($total_poultry_birds) ?></div>
                                            </div>
                                        </div>
                                        <div class="col-6 col-md-4">
                                            <div class="p-2 bg-white rounded border">
                                                <div class="text-muted small">Monthly Feed Mfg</div>
                                                <div class="fw-bold text-primary fs-6"><?= number_format($total_poultry_monthly_feed_kg, 0) ?> <small>kg</small></div>
                                            </div>
                                        </div>
                                        <div class="col-12 col-md-4">
                                            <div class="p-2 bg-white rounded border">
                                                <div class="text-muted small">DAPH Reg. Feed Mills</div>
                                                <div class="fw-bold text-success fs-6"><?= number_format($daph_registered_feed_mfr_count) ?></div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Specialty Breakdown Table -->
                            <div class="col-12 col-lg-7">
                                <div class="table-responsive border rounded-3 h-100">
                                    <table class="table table-hover align-middle mb-0 w-100" style="border-color: #f1f5f9;">
                                        <thead class="table-light text-secondary small">
                                            <tr>
                                                <th class="py-3 px-3">Specialty Category</th>
                                                <th class="py-3 px-3 text-center">Farmers</th>
                                                <th class="py-3 px-3">Production Focus / Key Details</th>
                                                <th class="py-3 px-3 text-center">Share</th>
                                                <th class="py-3 px-3 text-end">Compliance Status</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php
                                            $specialty_meta = [
                                                'Broiler Farmers' => [
                                                    'icon' => 'bi-fire',
                                                    'color' => '#f59e0b',
                                                    'badge' => 'bg-warning-subtle text-dark border border-warning',
                                                    'focus' => 'Commercial broiler meat, daily gain, feed conversion & contract buyback',
                                                    'status' => 'Commercial / Intensive'
                                                ],
                                                'Layer Farmers' => [
                                                    'icon' => 'bi-egg-fried',
                                                    'color' => '#8b5cf6',
                                                    'badge' => 'bg-purple-subtle text-purple border',
                                                    'focus' => 'Commercial table egg & pullet egg production, laying hen flocks',
                                                    'status' => 'Egg Marketing Active'
                                                ],
                                                'Free-Ranging / Local Farmers' => [
                                                    'icon' => 'bi-tree',
                                                    'color' => '#10b981',
                                                    'badge' => 'bg-success-subtle text-success border border-success-subtle',
                                                    'focus' => 'Village scavenging chickens, dual-purpose indigenous breeds & backyard pens',
                                                    'status' => 'Semi-intensive / Free'
                                                ],
                                                'Farmers involved in Feed Manufacturing' => [
                                                    'icon' => 'bi-gear-wide-connected',
                                                    'color' => '#3b82f6',
                                                    'badge' => 'bg-primary-subtle text-primary border border-primary-subtle',
                                                    'focus' => 'On-farm feed preparation, feed formulation & commercial mill distribution',
                                                    'status' => $daph_registered_feed_mfr_count > 0 ? ($daph_registered_feed_mfr_count . ' DAPH Certified') : 'On-Farm Mixing'
                                                ]
                                            ];

                                            foreach ($poultry_specialty_counts as $specName => $specCount):
                                                $meta = $specialty_meta[$specName] ?? [];
                                                $pct = $poultry_farmers_count > 0 ? (($specCount / $poultry_farmers_count) * 100) : 0;
                                            ?>
                                                <tr>
                                                    <td class="py-3 px-3 fw-bold text-dark">
                                                        <i class="bi <?= $meta['icon'] ?> me-2" style="color: <?= $meta['color'] ?>;"></i>
                                                        <?= htmlspecialchars($specName) ?>
                                                    </td>
                                                    <td class="py-3 px-3 text-center">
                                                        <span class="badge px-3 py-1 fw-bold fs-6" style="background-color: <?= $meta['color'] ?>20; color: <?= $meta['color'] ?>; border: 1px solid <?= $meta['color'] ?>40;">
                                                            <?= number_format($specCount) ?>
                                                        </span>
                                                    </td>
                                                    <td class="py-3 px-3 small text-secondary">
                                                        <?= htmlspecialchars($meta['focus']) ?>
                                                    </td>
                                                    <td class="py-3 px-3 text-center">
                                                        <div class="d-flex align-items-center justify-content-center gap-2">
                                                            <div class="progress flex-grow-1" style="height: 6px; max-width: 60px;">
                                                                <div class="progress-bar" role="progressbar" style="width: <?= min(100, $pct) ?>%; background-color: <?= $meta['color'] ?>;" aria-valuenow="<?= round($pct) ?>" aria-valuemin="0" aria-valuemax="100"></div>
                                                            </div>
                                                            <small class="fw-semibold text-dark"><?= number_format($pct, 1) ?>%</small>
                                                        </div>
                                                    </td>
                                                    <td class="py-3 px-3 text-end">
                                                        <span class="badge bg-light text-dark border px-2 py-1 small">
                                                            <?= htmlspecialchars($meta['status']) ?>
                                                        </span>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>

                        <!-- Flock Age Groups Headcount Breakdown (If Recorded) -->
                        <?php if ($total_poultry_birds > 0): ?>
                        <div class="mt-4 pt-3 border-top">
                            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-2">
                                <span class="small fw-bold text-dark text-uppercase">
                                    <i class="bi bi-diagram-3 me-1 text-primary"></i>Flock Age Group Distribution Across Registered Holdings:
                                </span>
                                <span class="text-muted small">Total: <strong><?= number_format($total_poultry_birds) ?></strong> birds</span>
                            </div>
                            <div class="row g-2">
                                <?php foreach ($flock_age_totals as $ageName => $ageQty): 
                                    $agePct = $total_poultry_birds > 0 ? round(($ageQty / $total_poultry_birds) * 100, 1) : 0;
                                ?>
                                    <div class="col-6 col-md-3">
                                        <div class="p-2 border rounded bg-white d-flex justify-content-between align-items-center">
                                            <span class="small text-truncate" title="<?= htmlspecialchars($ageName) ?>"><?= htmlspecialchars($ageName) ?></span>
                                            <div class="text-end">
                                                <span class="fw-bold text-dark"><?= number_format($ageQty) ?></span>
                                                <small class="text-muted ms-1">(<?= $agePct ?>%)</small>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <?php endif; ?>

                    </div>
                </div>
            </div>
        </div>

        <!-- Automated Production & Sales Summary Dashboard -->
        <div class="card gov-card mb-5">
            <div class="card-header bg-white pt-4 px-4 border-0 d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div>
                    <h5 class="fw-bold mb-1" style="color: #370709;">
                        <i class="bi bi-speedometer2 text-danger me-2"></i>Automated Production & Sales Summary Dashboards
                    </h5>
                    <p class="text-muted small mb-0">
                        Real-time compilation of manual entries across milk centers, feed mills, production outlets, and meat sales (Year: <strong class="text-dark"><?= $prod_summary_year === 'all' ? 'All Years Combined' : $prod_summary_year ?></strong>).
                    </p>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <form method="GET" class="d-flex align-items-center gap-2 mb-0">
                        <?php if ($requested_range_id): ?>
                            <input type="hidden" name="range_id" value="<?= $requested_range_id ?>">
                        <?php endif; ?>
                        <label class="small fw-bold text-muted text-nowrap mb-0">Summary Year:</label>
                        <select name="prod_year" class="form-select form-select-sm" onchange="this.form.submit()" style="width: 120px;">
                            <option value="all" <?= ($prod_summary_year === 'all') ? 'selected' : '' ?>>All Years</option>
                            <?php foreach ($available_prod_years as $py): ?>
                                <option value="<?= $py ?>" <?= ($prod_summary_year !== 'all' && intval($prod_summary_year) === $py) ? 'selected' : '' ?>><?= $py ?></option>
                            <?php endforeach; ?>
                        </select>
                    </form>
                </div>
            </div>
            <div class="card-body px-4 pb-4">
                <!-- 4 Top Executive Summary Cards -->
                <div class="row g-3 mb-4">
                    <!-- Milk Collection Summary Card -->
                    <div class="col-12 col-md-6 col-xl-3">
                        <div class="card border-0 shadow-sm rounded-3 h-100 p-3" style="background: linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 100%); border-left: 4px solid #0284c7 !important;">
                            <div class="d-flex justify-content-between align-items-center">
                                <span class="text-uppercase fw-bold small text-primary">Milk Collection</span>
                                <span class="badge bg-primary text-white rounded-pill px-2 py-1"><?= number_format($milk_summary['centers']) ?> Centers</span>
                            </div>
                            <h3 class="fw-bold my-2 text-dark">
                                <?= number_format($milk_summary['total_milk']) ?> <small class="fs-6 text-muted">Liters</small>
                            </h3>
                            <div class="small text-muted mt-auto pt-2 border-top">
                                <div class="d-flex justify-content-between">
                                    <span>Cow: <strong><?= number_format($milk_summary['cow_milk']) ?></strong> L</span>
                                    <span>Buff: <strong><?= number_format($milk_summary['buffalo_milk']) ?></strong> L</span>
                                </div>
                                <div class="d-flex justify-content-between mt-1">
                                    <span>Goat: <strong><?= number_format($milk_summary['goat_milk']) ?></strong> L</span>
                                    <span>Chilling: <strong><?= number_format($milk_summary['chilling_cap']) ?></strong> L</span>
                                </div>
                            </div>
                            <a href="milk_collection_details.php<?= $requested_range_id ? '?range_id=' . $requested_range_id : '' ?>" class="btn btn-sm btn-outline-primary mt-3 w-100 fw-bold">
                                View Milk Log <i class="bi bi-arrow-right ms-1"></i>
                            </a>
                        </div>
                    </div>

                    <!-- Feed Production Summary Card -->
                    <div class="col-12 col-md-6 col-xl-3">
                        <div class="card border-0 shadow-sm rounded-3 h-100 p-3" style="background: linear-gradient(135deg, #fefce8 0%, #fef9c3 100%); border-left: 4px solid #ca8a04 !important;">
                            <div class="d-flex justify-content-between align-items-center">
                                <span class="text-uppercase fw-bold small text-dark">Feed Production</span>
                                <span class="badge bg-warning text-dark rounded-pill px-2 py-1"><?= number_format($feed_summary['mills']) ?> Active Mills</span>
                            </div>
                            <h3 class="fw-bold my-2 text-dark">
                                <?= number_format($feed_summary['total_mt'], 2) ?> <small class="fs-6 text-muted">MT Total</small>
                            </h3>
                            <div class="small text-muted mt-auto pt-2 border-top">
                                <?php if (empty($feed_summary['categories'])): ?>
                                    <span>No mill entries logged for this period.</span>
                                <?php else: ?>
                                    <?php foreach (array_slice($feed_summary['categories'], 0, 2, true) as $fcat => $fmt): ?>
                                        <div class="d-flex justify-content-between">
                                            <span><?= ucfirst(htmlspecialchars($fcat)) ?>:</span>
                                            <strong><?= number_format($fmt, 1) ?> MT</strong>
                                        </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                            <a href="annual_feed_production.php<?= $requested_range_id ? '?range_id=' . $requested_range_id : '' ?>" class="btn btn-sm btn-outline-warning text-dark mt-3 w-100 fw-bold">
                                View Feed Mills <i class="bi bi-arrow-right ms-1"></i>
                            </a>
                        </div>
                    </div>

                    <!-- Production Outlets Summary Card -->
                    <div class="col-12 col-md-6 col-xl-3">
                        <div class="card border-0 shadow-sm rounded-3 h-100 p-3" style="background: linear-gradient(135deg, #f0fdf4 0%, #dcfce7 100%); border-left: 4px solid #16a34a !important;">
                            <div class="d-flex justify-content-between align-items-center">
                                <span class="text-uppercase fw-bold small text-success">Production Outlets</span>
                                <span class="badge bg-success text-white rounded-pill px-2 py-1"><?= number_format($outlets_summary['chicks']) ?> Chicks</span>
                            </div>
                            <h3 class="fw-bold my-2 text-dark">
                                <?= number_format($outlets_summary['live_chicken_kg'] + $outlets_summary['dressed_chicken_kg']) ?> <small class="fs-6 text-muted">kg Poultry</small>
                            </h3>
                            <div class="small text-muted mt-auto pt-2 border-top">
                                <div class="d-flex justify-content-between">
                                    <span>Live: <strong><?= number_format($outlets_summary['live_chicken_kg']) ?></strong> kg</span>
                                    <span>Dressed: <strong><?= number_format($outlets_summary['dressed_chicken_kg']) ?></strong> kg</span>
                                </div>
                                <div class="d-flex justify-content-between mt-1">
                                    <span>Organic Fert: <strong><?= number_format($outlets_summary['organic_fert_mt'], 1) ?></strong> MT</span>
                                </div>
                            </div>
                            <a href="annual_producers_processors.php<?= $requested_range_id ? '?range_id=' . $requested_range_id : '' ?>" class="btn btn-sm btn-outline-success mt-3 w-100 fw-bold">
                                View Outlets <i class="bi bi-arrow-right ms-1"></i>
                            </a>
                        </div>
                    </div>

                    <!-- Meat Sales Summary Card -->
                    <div class="col-12 col-md-6 col-xl-3">
                        <div class="card border-0 shadow-sm rounded-3 h-100 p-3" style="background: linear-gradient(135deg, #fff1f2 0%, #ffe4e6 100%); border-left: 4px solid #e11d48 !important;">
                            <div class="d-flex justify-content-between align-items-center">
                                <span class="text-uppercase fw-bold small text-danger">Meat Sales Turnover</span>
                                <span class="badge bg-danger text-white rounded-pill px-2 py-1"><?= number_format($meat_summary['total_kg'], 1) ?> kg</span>
                            </div>
                            <h3 class="fw-bold my-2 text-danger">
                                Rs. <?= number_format($meat_summary['total_revenue'], 2) ?>
                            </h3>
                            <div class="small text-muted mt-auto pt-2 border-top">
                                <div class="d-flex justify-content-between">
                                    <span>Beef: <strong><?= number_format($meat_summary['categories']['Beef'] ?? 0) ?></strong> kg</span>
                                    <span>Mutton: <strong><?= number_format($meat_summary['categories']['Mutton'] ?? 0) ?></strong> kg</span>
                                </div>
                                <div class="d-flex justify-content-between mt-1">
                                    <span>Poultry: <strong><?= number_format($meat_summary['categories']['Poultry'] ?? 0) ?></strong> kg</span>
                                    <span>Others: <strong><?= number_format($meat_summary['categories']['Other'] ?? 0) ?></strong> kg</span>
                                </div>
                            </div>
                            <a href="meat_sales.php<?= $requested_range_id ? '?range_id=' . $requested_range_id : '' ?>" class="btn btn-sm btn-outline-danger mt-3 w-100 fw-bold">
                                View Meat Sales <i class="bi bi-arrow-right ms-1"></i>
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Aggregated Sector Overview Table -->
                <div class="table-responsive rounded border">
                    <table class="table table-hover table-striped align-middle mb-0">
                        <thead class="table-light small text-secondary text-uppercase">
                            <tr>
                                <th>Operational Sector</th>
                                <th>Active Units / Outlets</th>
                                <th>Primary Volume Aggregation</th>
                                <th>Secondary Metrics & Categorization</th>
                                <th class="text-center">Action</th>
                            </tr>
                        </thead>
                        <tbody class="small">
                            <tr>
                                <td class="fw-bold text-dark">
                                    <i class="bi bi-droplet-half text-primary me-2 fs-6"></i>Milk Collection Centers
                                </td>
                                <td><span class="badge bg-primary-subtle text-primary fw-bold"><?= number_format($milk_summary['centers']) ?> Centers</span></td>
                                <td><strong class="text-primary fs-6"><?= number_format($milk_summary['total_milk']) ?></strong> Liters Total</td>
                                <td>
                                    Cow: <?= number_format($milk_summary['cow_milk']) ?> L | Buffalo: <?= number_format($milk_summary['buffalo_milk']) ?> L | Goat: <?= number_format($milk_summary['goat_milk']) ?> L
                                </td>
                                <td class="text-center">
                                    <a href="milk_collection_details.php<?= $requested_range_id ? '?range_id=' . $requested_range_id : '' ?>" class="btn btn-xs btn-outline-primary py-1 px-2 fw-bold">
                                        Open Module
                                    </a>
                                </td>
                            </tr>
                            <tr>
                                <td class="fw-bold text-dark">
                                    <i class="bi bi-prescription2 text-warning me-2 fs-6"></i>Feed Production Mills
                                </td>
                                <td><span class="badge bg-warning-subtle text-dark fw-bold"><?= number_format($feed_summary['mills']) ?> Registered Mills</span></td>
                                <td><strong class="text-dark fs-6"><?= number_format($feed_summary['total_mt'], 2) ?></strong> Metric Tons (MT)</td>
                                <td>
                                    <?php 
                                        if (!empty($feed_summary['categories'])) {
                                            $cat_strs = [];
                                            foreach ($feed_summary['categories'] as $ck => $cv) {
                                                $cat_strs[] = ucfirst(htmlspecialchars($ck)) . ': ' . number_format($cv, 1) . ' MT';
                                            }
                                            echo implode(' | ', $cat_strs);
                                        } else {
                                            echo '<span class="text-muted">No feed production logs</span>';
                                        }
                                    ?>
                                </td>
                                <td class="text-center">
                                    <a href="annual_feed_production.php<?= $requested_range_id ? '?range_id=' . $requested_range_id : '' ?>" class="btn btn-xs btn-outline-warning text-dark py-1 px-2 fw-bold">
                                        Open Module
                                    </a>
                                </td>
                            </tr>
                            <tr>
                                <td class="fw-bold text-dark">
                                    <i class="bi bi-buildings text-success me-2 fs-6"></i>Production Outlets (Producers & Processors)
                                </td>
                                <td><span class="badge bg-success-subtle text-success fw-bold">Active Outlets</span></td>
                                <td><strong class="text-success fs-6"><?= number_format($outlets_summary['chicks']) ?></strong> Day-Old Chicks</td>
                                <td>
                                    Live Poultry: <?= number_format($outlets_summary['live_chicken_kg']) ?> kg | Dressed: <?= number_format($outlets_summary['dressed_chicken_kg']) ?> kg | Organic Fertilizer: <?= number_format($outlets_summary['organic_fert_mt'], 1) ?> MT
                                </td>
                                <td class="text-center">
                                    <a href="annual_producers_processors.php<?= $requested_range_id ? '?range_id=' . $requested_range_id : '' ?>" class="btn btn-xs btn-outline-success py-1 px-2 fw-bold">
                                        Open Module
                                    </a>
                                </td>
                            </tr>
                            <tr>
                                <td class="fw-bold text-dark">
                                    <i class="bi bi-basket3-fill text-danger me-2 fs-6"></i>Meat Sales Details
                                </td>
                                <td><span class="badge bg-danger-subtle text-danger fw-bold">Wholesale & Retail</span></td>
                                <td><strong class="text-danger fs-6"><?= number_format($meat_summary['total_kg'], 1) ?></strong> kg (Rs. <?= number_format($meat_summary['total_revenue'], 2) ?>)</td>
                                <td>
                                    Beef: <?= number_format($meat_summary['categories']['Beef'] ?? 0) ?> kg | Mutton: <?= number_format($meat_summary['categories']['Mutton'] ?? 0) ?> kg | Poultry: <?= number_format($meat_summary['categories']['Poultry'] ?? 0) ?> kg
                                    <?php if (!empty($meat_summary['others_detail'])): ?>
                                        | <span class="fw-bold">Others:</span> <?= implode(', ', array_map(fn($k, $v) => htmlspecialchars($k) . ' (' . number_format($v) . ' kg)', array_keys($meat_summary['others_detail']), $meat_summary['others_detail'])) ?>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center">
                                    <a href="meat_sales.php<?= $requested_range_id ? '?range_id=' . $requested_range_id : '' ?>" class="btn btn-xs btn-outline-danger py-1 px-2 fw-bold">
                                        Open Module
                                    </a>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Annual Returns & Inventories Quick Actions -->
        <div class="card gov-card mb-5">
            <div class="card-header bg-white pt-4 px-4 border-0">
                <h5 class="fw-bold mb-1" style="color: #370709;"><i class="bi bi-grid-3x3-gap-fill me-2"></i>Annual Returns & Inventory Management</h5>
                <p class="text-muted small mb-0">Quick access links to manage annual data logs, production levels, pasture details, and livestock societies.</p>
            </div>
            <div class="card-body px-4 pb-4">
                <div class="row g-3">
                    <div class="col-12 col-sm-6 col-md-4 col-lg-3">
                        <a href="annual_production_levels.php<?= $requested_range_id ? '?range_id=' . $requested_range_id : '' ?>" class="btn btn-primary w-100 py-3 d-flex flex-column align-items-center justify-content-center h-100 border-2">
                            <i class="bi bi-graph-up-arrow fs-3 mb-2"></i>
                            <span class="text-center fw-bold">Production Levels</span>
                            <small class="text-white-50 small">Daily Range Rates</small>
                        </a>
                    </div>
                    <div class="col-12 col-sm-6 col-md-4 col-lg-3">
                        <a href="pasture.php<?= $requested_range_id ? '?range_id=' . $requested_range_id : '' ?>" class="btn btn-success w-100 py-3 d-flex flex-column align-items-center justify-content-center h-100 border-2">
                            <i class="bi bi-tree-fill fs-3 mb-2"></i>
                            <span class="text-center fw-bold">Pasture & Fodder</span>
                            <small class="text-white-50 small">Lands, Cultivation & Yields</small>
                        </a>
                    </div>
                    <div class="col-12 col-sm-6 col-md-4 col-lg-3">
                        <a href="meat_sales.php<?= $requested_range_id ? '?range_id=' . $requested_range_id : '' ?>" class="btn btn-danger w-100 py-3 d-flex flex-column align-items-center justify-content-center h-100 border-2">
                            <i class="bi bi-basket3-fill fs-3 mb-2"></i>
                            <span class="text-center fw-bold">Meat Sales Details</span>
                            <small class="text-white-50 small">Beef, Mutton, Poultry, Other</small>
                        </a>
                    </div>
                    <div class="col-12 col-sm-6 col-md-4 col-lg-3">
                        <a href="milk_collection_details.php<?= $requested_range_id ? '?range_id=' . $requested_range_id : '' ?>" class="btn btn-dark w-100 py-3 d-flex flex-column align-items-center justify-content-center h-100 border-2">
                            <i class="bi bi-droplet-half fs-3 mb-2 text-info"></i>
                            <span class="text-center fw-bold">Milk Collection Details</span>
                            <small class="text-white-50 small">Cow, Buffalo & Goat Breakdown</small>
                        </a>
                    </div>
                    <div class="col-12 col-sm-6 col-md-4 col-lg-3">
                        <a href="annual_producers_processors.php<?= $requested_range_id ? '?range_id=' . $requested_range_id : '' ?>" class="btn btn-warning w-100 py-3 d-flex flex-column align-items-center justify-content-center h-100 border-2 text-dark">
                            <i class="bi bi-buildings fs-3 mb-2"></i>
                            <span class="text-center fw-bold">Producers & Processors</span>
                            <small class="text-muted small">Private Sector & Income</small>
                        </a>
                    </div>
                    <div class="col-12 col-sm-6 col-md-4 col-lg-3">
                        <a href="annual_feed_production.php<?= $requested_range_id ? '?range_id=' . $requested_range_id : '' ?>" class="btn btn-secondary w-100 py-3 d-flex flex-column align-items-center justify-content-center h-100 border-2">
                            <i class="bi bi-prescription2 fs-3 mb-2"></i>
                            <span class="text-center fw-bold">Feed Production Mills</span>
                            <small class="text-white-50 small">Output & Outlets</small>
                        </a>
                    </div>
                    <div class="col-12 col-sm-6 col-md-4 col-lg-3">
                        <a href="annual_livestock_societies.php<?= $requested_range_id ? '?range_id=' . $requested_range_id : '' ?>" class="btn btn-outline-primary w-100 py-3 d-flex flex-column align-items-center justify-content-center h-100 border-2">
                            <i class="bi bi-people-fill fs-3 mb-2"></i>
                            <span class="text-center fw-bold">Livestock Societies</span>
                            <small class="text-muted small">Societies & Code of Conduct</small>
                        </a>
                    </div>
                </div>
            </div>
        </div>

<?php
include 'models/add_health_record.php';
include 'models/manage_human_population_modal.php';

ob_start();
?>
<script>
const CURRENT_RANGE_ID = <?= json_encode($range_id) ?>;
$(document).ready(function() {
    // Dynamic total calculation in manage population modal
    function updateManagePopTotal() {
        const male = parseInt($("#managePopMale").val()) || 0;
        const female = parseInt($("#managePopFemale").val()) || 0;
        $("#managePopTotalPreview").text((male + female).toLocaleString());
    }
    $(document).on("input", "#managePopMale, #managePopFemale", updateManagePopTotal);

    // Reset button in manage population modal
    $("#btnResetPopForm").on("click", function() {
        $("#manageHumanPopForm")[0].reset();
        const currentYear = $("#filterYear").val() || new Date().getFullYear();
        $("#managePopYear").val(currentYear);
        $("#managePopTotalPreview").text("0");
        $("#formTabLabel").text("Add / Update Record");
        $("#btnSavePopForm").html('<i class="bi bi-check-circle-fill me-1 text-success"></i> Save Demographics');
        $("#managePopAlertBox").empty();
    });

    // Fetch and populate recorded demographics list in modal
    function loadRecordedDemographics() {
        const rangeParam = typeof CURRENT_RANGE_ID !== 'undefined' && CURRENT_RANGE_ID ? `&range_id=${CURRENT_RANGE_ID}` : '';
        $.ajax({
            url: `processors/save_human_population.php?action=get_list${rangeParam}`,
            type: "GET",
            dataType: "json",
            success: function(res) {
                if (res.success) {
                    const tbody = $("#recordedDemographicsTable tbody");
                    tbody.empty();
                    $("#recordsCountBadge").text(res.data.length);
                    if (res.data.length === 0) {
                        tbody.append('<tr><td colspan="7" class="text-center py-3 text-muted">No population records found for this range.</td></tr>');
                        return;
                    }
                    res.data.forEach(function(item) {
                        const safeEth = $("<div>").text(item.ethnicity).html();
                        const row = `
                            <tr data-year="${item.year}" data-ethnicity="${safeEth}" data-male="${item.male}" data-female="${item.female}" data-households="${item.households}">
                                <td class="fw-bold">${item.year}</td>
                                <td><span class="badge bg-secondary">${safeEth}</span></td>
                                <td class="text-end text-primary font-monospace">${Number(item.male).toLocaleString()}</td>
                                <td class="text-end text-danger font-monospace">${Number(item.female).toLocaleString()}</td>
                                <td class="text-end fw-bold font-monospace" style="color: #370709;">${Number(item.total).toLocaleString()}</td>
                                <td class="text-end text-success font-monospace">${Number(item.households).toLocaleString()}</td>
                                <td class="text-center">
                                    <div class="btn-group btn-group-sm">
                                        <button type="button" class="btn btn-outline-primary btn-xs py-0 px-2 btn-edit-human-pop" title="Edit">
                                            <i class="bi bi-pencil-square"></i>
                                        </button>
                                        <button type="button" class="btn btn-outline-danger btn-xs py-0 px-2 btn-delete-human-pop" title="Delete">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        `;
                        tbody.append(row);
                    });
                }
            },
            error: function() {
                $("#recordedDemographicsTable tbody").html('<tr><td colspan="7" class="text-center py-3 text-danger">Failed to load records.</td></tr>');
            }
        });
    }

    // Modal open event: pre-fill current active year & refresh demographics list
    $("#manageHumanPopulationModal").on("show.bs.modal", function() {
        const currentYear = $("#filterYear").val() || new Date().getFullYear();
        if ($("#formTabLabel").text() === "Add / Update Record") {
            $("#managePopYear").val(currentYear);
        }
        loadRecordedDemographics();
    });

    $("#btnRefreshPopList").on("click", function() {
        loadRecordedDemographics();
    });

    // Form submission via AJAX
    $("#manageHumanPopForm").on("submit", function(e) {
        e.preventDefault();
        const btn = $("#btnSavePopForm");
        const originalBtnHtml = btn.html();
        btn.prop("disabled", true).html('<span class="spinner-border spinner-border-sm me-1" role="status"></span> Saving...');

        $.ajax({
            url: "processors/save_human_population.php",
            type: "POST",
            data: $(this).serialize(),
            dataType: "json",
            success: function(res) {
                btn.prop("disabled", false).html(originalBtnHtml);
                if (res.success) {
                    Swal.fire({
                        icon: "success",
                        title: "Saved Successfully",
                        text: res.message,
                        timer: 2000,
                        showConfirmButton: false
                    });

                    // Ensure saved year exists in filter dropdown and is selected
                    const savedYear = res.year;
                    if (savedYear) {
                        if ($(`#filterYear option[value="${savedYear}"]`).length === 0) {
                            $("#filterYear").prepend(new Option(savedYear, savedYear, true, true));
                        }
                        $("#filterYear").val(savedYear);
                    }

                    // Trigger direct reload of pie chart and datatable
                    if (typeof window.fetchFilteredPopulationData === 'function') {
                        window.fetchFilteredPopulationData();
                    }

                    // Refresh modal records list
                    loadRecordedDemographics();

                    // Close the modal so the updated pie chart and table are immediately visible
                    const modalEl = document.getElementById("manageHumanPopulationModal");
                    if (modalEl && typeof bootstrap !== 'undefined' && bootstrap.Modal) {
                        const modalInstance = bootstrap.Modal.getOrCreateInstance(modalEl);
                        if (modalInstance) {
                            modalInstance.hide();
                        }
                    }
                    $("#manageHumanPopulationModal").modal("hide");
                } else {
                    Swal.fire({
                        icon: "error",
                        title: "Save Failed",
                        text: res.message || "An error occurred while saving demographics."
                    });
                }
            },
            error: function() {
                btn.prop("disabled", false).html(originalBtnHtml);
                Swal.fire({
                    icon: "error",
                    title: "Request Error",
                    text: "Server communication failed. Check connection and try again."
                });
            }
        });
    });

    // Edit button click in modal records table
    $(document).on("click", ".btn-edit-human-pop", function() {
        const row = $(this).closest("tr");
        const year = row.data("year");
        const ethnicity = row.data("ethnicity");
        const male = row.data("male");
        const female = row.data("female");
        const households = row.data("households");

        $("#managePopYear").val(year);
        $("#managePopEthnicity").val(ethnicity);
        $("#managePopMale").val(male);
        $("#managePopFemale").val(female);
        $("#managePopHouseholds").val(households);
        updateManagePopTotal();

        $("#formTabLabel").text(`Edit Record: ${ethnicity} (${year})`);
        $("#btnSavePopForm").html('<i class="bi bi-pencil-square me-1"></i> Update Demographics');

        const addTabTrigger = new bootstrap.Tab(document.getElementById("add-pop-tab"));
        addTabTrigger.show();
    });

    // Delete button click in modal records table
    $(document).on("click", ".btn-delete-human-pop", function() {
        const row = $(this).closest("tr");
        const year = row.data("year");
        const ethnicity = row.data("ethnicity");

        Swal.fire({
            icon: "warning",
            title: "Delete Demographic Record?",
            html: `Are you sure you want to delete population data for <strong>${ethnicity}</strong> in year <strong>${year}</strong>?<br><small class="text-danger">This will remove Male, Female, and Household counts for this group.</small>`,
            showCancelButton: true,
            confirmButtonColor: "#370709",
            cancelButtonColor: "#6c757d",
            confirmButtonText: '<i class="bi bi-trash-fill me-1"></i> Yes, Delete',
            cancelButtonText: "Cancel"
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: "processors/save_human_population.php",
                    type: "POST",
                    data: {
                        action: "delete",
                        year: year,
                        ethnicity: ethnicity,
                        range_id: typeof CURRENT_RANGE_ID !== 'undefined' && CURRENT_RANGE_ID ? CURRENT_RANGE_ID : ''
                    },
                    dataType: "json",
                    success: function(res) {
                        if (res.success) {
                            Swal.fire({
                                icon: "success",
                                title: "Deleted",
                                text: res.message,
                                timer: 1800,
                                showConfirmButton: false
                            });
                            row.fadeOut(300, function() {
                                $(this).remove();
                                const currentCount = parseInt($("#recordsCountBadge").text()) || 1;
                                $("#recordsCountBadge").text(Math.max(0, currentCount - 1));
                                if ($("#recordedDemographicsTable tbody tr").length === 0) {
                                    $("#recordedDemographicsTable tbody").append('<tr><td colspan="7" class="text-center py-3 text-muted">No population records found for this range.</td></tr>');
                                }
                            });

                            // Refresh main page chart & table
                            if (typeof window.fetchFilteredPopulationData === 'function') {
                                window.fetchFilteredPopulationData();
                            }
                            const filterYearEl = document.getElementById("filterYear");
                            if (filterYearEl) {
                                filterYearEl.dispatchEvent(new Event("change"));
                            }
                        } else {
                            Swal.fire({
                                icon: "error",
                                title: "Deletion Failed",
                                text: res.message
                            });
                        }
                    },
                    error: function() {
                        Swal.fire({
                            icon: "error",
                            title: "Error",
                            text: "Server communication error while attempting to delete."
                        });
                    }
                });
            }
        });
    });

    // End of $(document).ready modal & population scripts
});

// ==========================================
// INITIALIZE SPECIALTY & FARM CHARTS (CHART.JS)
// ==========================================
function initFarmDistributionCharts(retryCount = 0) {
    if (typeof Chart === 'undefined') {
        if (retryCount < 30) {
            setTimeout(function() {
                initFarmDistributionCharts(retryCount + 1);
            }, 100);
        } else {
            console.warn("[FarmCharts] Chart.js failed to load within timeout period.");
        }
        return;
    }

    // Chart 1: Farmers by Animal Type (Cattle, Buffalo, Poultry)
    try {
        const farmersCanvasEl = document.getElementById('farmersByAnimalChart');
        if (farmersCanvasEl) {
            const existingInstance = Chart.getChart(farmersCanvasEl);
            if (existingInstance) {
                existingInstance.destroy();
            }

            new Chart(farmersCanvasEl.getContext('2d'), {
                type: 'bar',
                data: {
                    labels: ['Cattle', 'Buffalo', 'Poultry'],
                    datasets: [{
                        label: 'Registered Farmers',
                        data: [<?= (int)$cattle_farmers_count ?>, <?= (int)$buffalo_farmers_count ?>, <?= (int)$poultry_farmers_count ?>],
                        backgroundColor: ['#820100', '#0284c7', '#f59e0b'],
                        borderRadius: 8,
                        borderSkipped: false,
                        barThickness: 46
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            backgroundColor: '#1e293b',
                            titleFont: { size: 13, weight: 'bold' },
                            bodyFont: { size: 12 },
                            padding: 10,
                            cornerRadius: 8,
                            callbacks: {
                                label: function(ctx) {
                                    return ` ${ctx.parsed.y} Farmers`;
                                }
                            }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            border: { display: false },
                            ticks: {
                                precision: 0,
                                font: { size: 11, family: 'Inter, system-ui, sans-serif' },
                                color: '#64748b'
                            },
                            grid: {
                                color: '#f1f5f9'
                            }
                        },
                        x: {
                            border: { display: false },
                            grid: { display: false },
                            ticks: {
                                font: { weight: '600', size: 12, family: 'Inter, system-ui, sans-serif' },
                                color: '#334155'
                            }
                        }
                    }
                }
            });
        }
    } catch (e1) {
        console.error("[FarmCharts] Error rendering farmersByAnimalChart:", e1);
    }

    // Chart 2: Milk Production Volume Distribution Buckets
    try {
        const milkCanvasEl = document.getElementById('milkVolumeBucketsChart');
        if (milkCanvasEl) {
            const existingInstance = Chart.getChart(milkCanvasEl);
            if (existingInstance) {
                existingInstance.destroy();
            }

            new Chart(milkCanvasEl.getContext('2d'), {
                type: 'bar',
                data: {
                    labels: <?= json_encode(array_keys($milk_buckets)) ?>,
                    datasets: [{
                        label: 'Dairy Farmers',
                        data: <?= json_encode(array_values($milk_buckets)) ?>,
                        backgroundColor: [
                            '#38bdf8', // Below 5 liters (<5L)
                            '#0284c7', // 5 to 10 liters (5-10L)
                            '#0369a1', // 10 to 20 liters (10-20L)
                            '#059669', // 20 to 40 liters (20-40L)
                            '#10b981', // 40 to 100 liters (40-100L)
                            '#820100'  // More than 100 liters (>100L)
                        ],
                        borderRadius: 6,
                        borderSkipped: false,
                        barThickness: 34
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            backgroundColor: '#1e293b',
                            titleFont: { size: 13, weight: 'bold' },
                            bodyFont: { size: 12 },
                            padding: 10,
                            cornerRadius: 8,
                            callbacks: {
                                label: function(ctx) {
                                    return ` ${ctx.parsed.y} Farmers`;
                                }
                            }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            border: { display: false },
                            ticks: {
                                precision: 0,
                                font: { size: 11, family: 'Inter, system-ui, sans-serif' },
                                color: '#64748b'
                            },
                            grid: {
                                color: '#f1f5f9'
                            }
                        },
                        x: {
                            border: { display: false },
                            grid: { display: false },
                            ticks: {
                                font: { weight: '600', size: 11, family: 'Inter, system-ui, sans-serif' },
                                color: '#334155'
                            }
                        }
                    }
                }
            });
        }
    } catch (e2) {
        console.error("[FarmCharts] Error rendering milkVolumeBucketsChart:", e2);
    }

    // Chart 3: Dedicated Poultry Specialty Distribution Chart
    try {
        const poultrySpecialtyCanvasEl = document.getElementById('poultrySpecialtyChart');
        if (poultrySpecialtyCanvasEl) {
            const existingInstance = Chart.getChart(poultrySpecialtyCanvasEl);
            if (existingInstance) {
                existingInstance.destroy();
            }

            new Chart(poultrySpecialtyCanvasEl.getContext('2d'), {
                type: 'doughnut',
                data: {
                    labels: <?= json_encode(array_keys($poultry_specialty_counts)) ?>,
                    datasets: [{
                        data: <?= json_encode(array_values($poultry_specialty_counts)) ?>,
                        backgroundColor: [
                            '#f59e0b', // Broiler (Amber)
                            '#8b5cf6', // Layer (Purple)
                            '#10b981', // Free-Range / Local (Emerald)
                            '#3b82f6'  // Feed Manufacturing (Blue)
                        ],
                        borderWidth: 2,
                        borderColor: '#ffffff',
                        hoverOffset: 6
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    cutout: '62%',
                    plugins: {
                        legend: {
                            display: true,
                            position: 'bottom',
                            labels: {
                                boxWidth: 12,
                                padding: 12,
                                font: { size: 11, family: 'Inter, system-ui, sans-serif', weight: '600' },
                                color: '#334155'
                            }
                        },
                        tooltip: {
                            backgroundColor: '#1e293b',
                            titleFont: { size: 13, weight: 'bold' },
                            bodyFont: { size: 12 },
                            padding: 10,
                            cornerRadius: 8,
                            callbacks: {
                                label: function(ctx) {
                                    const total = ctx.dataset.data.reduce((a, b) => a + b, 0);
                                    const val = ctx.parsed;
                                    const pct = total > 0 ? Math.round((val / total) * 100) : 0;
                                    return ` ${ctx.label}: ${val} Farmers (${pct}%)`;
                                }
                            }
                        }
                    }
                }
            });
        }
    } catch (e3) {
        console.error("[FarmCharts] Error rendering poultrySpecialtyChart:", e3);
    }
}

// Ensure execution across all browser readiness phases
initFarmDistributionCharts();
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', function() {
        initFarmDistributionCharts();
    });
}
window.addEventListener('load', function() {
    initFarmDistributionCharts();
});
if (typeof $ !== 'undefined') {
    $(document).ready(function() {
        initFarmDistributionCharts();
    });
}
</script>
<?php
$pageScripts = ob_get_clean();
require_once '../../../includes/footer.php';
?>