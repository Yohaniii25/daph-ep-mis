<?php
/**
 * pages/modules/veterinary/processors/clinical_services_helper.php
 * Clinical Services Shared Business Logic & Helpers:
 * 1. Conditional Drug & Vaccine Inventory Real-time Deduction (Large Animal only)
 * 2. Automated Monthly Summary Reports Generation & Multi-species Categorization
 */

require_once __DIR__ . '/vaccine_balance_helper.php';

/**
 * Automatically link to, and deduct from, master government drug balance
 * and vaccine inventories in real-time for Large Animal Clinic.
 *
 * NOTE: For Small Animal Clinic, this linkage is strictly disabled as small
 * animal drugs are purchased privately by farmers/owners and do not affect government stock.
 */
function syncLargeAnimalDrugInventory($mysqli, $range_id, $district_id, $entry_date, $drug_type_id = 0, $drug_name = '', $doses = 1, $user_id = null) {
    $report_year  = intval(date('Y', strtotime($entry_date)));
    $report_month = intval(date('n', strtotime($entry_date)));
    if ($doses <= 0) $doses = 1;

    // 1. Resolve drug_type_id & drug_name if not provided
    if ($drug_type_id <= 0) {
        $dt_q = $mysqli->query("SELECT id, vaccine_name FROM drug_types WHERE target_animal LIKE '%Cattle%' OR target_animal LIKE '%Buffalo%' OR target_animal LIKE '%Goat%' ORDER BY id ASC LIMIT 1");
        if ($dt_q && ($dt_row = $dt_q->fetch_assoc())) {
            $drug_type_id = intval($dt_row['id']);
            if (empty($drug_name)) $drug_name = $dt_row['vaccine_name'];
        } else {
            $drug_type_id = 2; // Default Xyaject Inj
            if (empty($drug_name)) $drug_name = 'Xyaject Inj';
        }
    }

    if (empty($drug_name) && $drug_type_id > 0) {
        $name_stmt = $mysqli->prepare("SELECT vaccine_name FROM drug_types WHERE id = ?");
        if ($name_stmt) {
            $name_stmt->bind_param("i", $drug_type_id);
            $name_stmt->execute();
            if ($nr = $name_stmt->get_result()->fetch_assoc()) {
                $drug_name = $nr['vaccine_name'];
            }
            $name_stmt->close();
        }
    }

    $deductions = [
        'drug_records' => false,
        'monthly_vaccine_balances' => false,
        'doses' => $doses,
        'drug_name' => $drug_name,
        'drug_type_id' => $drug_type_id,
        'new_balance' => null
    ];

    // 2. Real-time deduction from master government drug balance (drug_records)
    $dr_chk = $mysqli->prepare("
        SELECT id, starter_count_month, during_month_received, used_doses_count, doses_damaged, balance_end_month 
        FROM drug_records 
        WHERE drug_type_id = ? AND MONTH(log_date) = ? AND YEAR(log_date) = ? 
        ORDER BY id DESC LIMIT 1
    ");
    if ($dr_chk) {
        $dr_chk->bind_param("iii", $drug_type_id, $report_month, $report_year);
        $dr_chk->execute();
        $dr_res = $dr_chk->get_result();
        if ($dr_row = $dr_res->fetch_assoc()) {
            $rec_id   = intval($dr_row['id']);
            $new_used = intval($dr_row['used_doses_count']) + $doses;
            $new_bal  = intval($dr_row['starter_count_month']) + intval($dr_row['during_month_received']) - $new_used - intval($dr_row['doses_damaged']);
            $u_dr = $mysqli->prepare("UPDATE drug_records SET used_doses_count = ?, balance_end_month = ? WHERE id = ?");
            if ($u_dr) {
                $u_dr->bind_param("iii", $new_used, $new_bal, $rec_id);
                $u_dr->execute();
                $u_dr->close();
                $deductions['drug_records'] = true;
                $deductions['new_balance'] = $new_bal;
            }
        } else {
            // Find prior month closing balance
            $prev_bal = 100;
            $prev_stmt = $mysqli->prepare("SELECT balance_end_month FROM drug_records WHERE drug_type_id = ? ORDER BY log_date DESC, id DESC LIMIT 1");
            if ($prev_stmt) {
                $prev_stmt->bind_param("i", $drug_type_id);
                $prev_stmt->execute();
                if ($pr = $prev_stmt->get_result()->fetch_assoc()) {
                    $prev_bal = intval($pr['balance_end_month']);
                }
                $prev_stmt->close();
            }

            $starter = $prev_bal;
            $used    = $doses;
            $bal     = max(0, $starter - $used);
            $ins_dr = $mysqli->prepare("
                INSERT INTO drug_records 
                (log_date, drug_type_id, starter_count_month, during_month_received, used_doses_count, doses_damaged, balance_end_month) 
                VALUES (?, ?, ?, 0, ?, 0, ?)
            ");
            if ($ins_dr) {
                $ins_dr->bind_param("siiii", $entry_date, $drug_type_id, $starter, $used, $bal);
                $ins_dr->execute();
                $ins_dr->close();
                $deductions['drug_records'] = true;
                $deductions['new_balance'] = $bal;
            }
        }
        $dr_chk->close();
    }

    // 3. Real-time deduction from master government vaccine inventories (monthly_vaccine_balances)
    if (function_exists('deductVaccineBalance') && !empty($drug_name)) {
        deductVaccineBalance($mysqli, $range_id, $report_year, $report_month, $drug_name, $doses, null, null, $user_id);
        $deductions['monthly_vaccine_balances'] = true;
    }

    return $deductions;
}

/**
 * Automatically aggregate data from both Large Animal and Small Animal clinics
 * to generate Monthly Summary Reports with animal category breakdowns.
 */
function buildMonthlySummaryReportData($mysqli, $range_id, $selected_year, $selected_month = 0) {
    $where_clauses = ["(range_id = ? OR range_id IS NULL OR range_id = 0)"];
    $params = [$range_id];
    $types  = "i";

    if ($selected_year > 0) {
        $where_clauses[] = "YEAR(entry_date) = ?";
        $params[] = $selected_year;
        $types   .= "i";
    }

    if ($selected_month > 0) {
        $where_clauses[] = "MONTH(entry_date) = ?";
        $params[] = $selected_month;
        $types   .= "i";
    }

    $where_sql = implode(" AND ", $where_clauses);
    $sql = "SELECT * FROM clinical_opd_records WHERE {$where_sql} ORDER BY entry_date DESC, id DESC";

    $records = [];
    $stmt = $mysqli->prepare($sql);
    if ($stmt) {
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $res = $stmt->get_result();
        while ($row = $res->fetch_assoc()) {
            $records[] = $row;
        }
        $stmt->close();
    }

    // Canonical Animal Category Definitions for Monthly Summary Tracking
    $categories = [
        'cattle' => [
            'key' => 'cattle',
            'name' => 'Cattle (Neat Cattle)',
            'clinic' => 'Large Animal Clinic',
            'icon' => 'bi-shield-check',
            'badge' => 'bg-success text-white',
            'cases' => 0, 'large_cases' => 0, 'small_cases' => 0,
            'affected' => 0, 'deaths' => 0,
            'revenue_consultation' => 0.0, 'revenue_drugs' => 0.0, 'revenue_total' => 0.0,
            'diagnoses' => [], 'actions' => ['Field Visit' => 0, 'Prescriptions' => 0, 'Direct Medication' => 0]
        ],
        'buffaloes' => [
            'key' => 'buffaloes',
            'name' => 'Buffaloes',
            'clinic' => 'Large Animal Clinic',
            'icon' => 'bi-shield-shaded',
            'badge' => 'bg-emerald text-white',
            'cases' => 0, 'large_cases' => 0, 'small_cases' => 0,
            'affected' => 0, 'deaths' => 0,
            'revenue_consultation' => 0.0, 'revenue_drugs' => 0.0, 'revenue_total' => 0.0,
            'diagnoses' => [], 'actions' => ['Field Visit' => 0, 'Prescriptions' => 0, 'Direct Medication' => 0]
        ],
        'goats' => [
            'key' => 'goats',
            'name' => 'Goats',
            'clinic' => 'Large Animal Clinic',
            'icon' => 'bi-flower1',
            'badge' => 'bg-warning text-dark',
            'cases' => 0, 'large_cases' => 0, 'small_cases' => 0,
            'affected' => 0, 'deaths' => 0,
            'revenue_consultation' => 0.0, 'revenue_drugs' => 0.0, 'revenue_total' => 0.0,
            'diagnoses' => [], 'actions' => ['Field Visit' => 0, 'Prescriptions' => 0, 'Direct Medication' => 0]
        ],
        'sheep' => [
            'key' => 'sheep',
            'name' => 'Sheep',
            'clinic' => 'Large Animal Clinic',
            'icon' => 'bi-cloud',
            'badge' => 'bg-secondary text-white',
            'cases' => 0, 'large_cases' => 0, 'small_cases' => 0,
            'affected' => 0, 'deaths' => 0,
            'revenue_consultation' => 0.0, 'revenue_drugs' => 0.0, 'revenue_total' => 0.0,
            'diagnoses' => [], 'actions' => ['Field Visit' => 0, 'Prescriptions' => 0, 'Direct Medication' => 0]
        ],
        'swine' => [
            'key' => 'swine',
            'name' => 'Swine / Pigs',
            'clinic' => 'Large Animal Clinic',
            'icon' => 'bi-circle-half',
            'badge' => 'bg-danger text-white',
            'cases' => 0, 'large_cases' => 0, 'small_cases' => 0,
            'affected' => 0, 'deaths' => 0,
            'revenue_consultation' => 0.0, 'revenue_drugs' => 0.0, 'revenue_total' => 0.0,
            'diagnoses' => [], 'actions' => ['Field Visit' => 0, 'Prescriptions' => 0, 'Direct Medication' => 0]
        ],
        'horses' => [
            'key' => 'horses',
            'name' => 'Equine / Horses',
            'clinic' => 'Large Animal Clinic',
            'icon' => 'bi-compass',
            'badge' => 'bg-info text-dark',
            'cases' => 0, 'large_cases' => 0, 'small_cases' => 0,
            'affected' => 0, 'deaths' => 0,
            'revenue_consultation' => 0.0, 'revenue_drugs' => 0.0, 'revenue_total' => 0.0,
            'diagnoses' => [], 'actions' => ['Field Visit' => 0, 'Prescriptions' => 0, 'Direct Medication' => 0]
        ],
        'dogs' => [
            'key' => 'dogs',
            'name' => 'Dogs (Canine)',
            'clinic' => 'Small Animal Clinic',
            'icon' => 'bi-heart-pulse-fill',
            'badge' => 'bg-primary text-white',
            'cases' => 0, 'large_cases' => 0, 'small_cases' => 0,
            'affected' => 0, 'deaths' => 0,
            'revenue_consultation' => 0.0, 'revenue_drugs' => 0.0, 'revenue_total' => 0.0,
            'diagnoses' => [], 'actions' => ['Field Visit' => 0, 'Prescriptions' => 0, 'Direct Medication' => 0]
        ],
        'cats' => [
            'key' => 'cats',
            'name' => 'Cats (Feline)',
            'clinic' => 'Small Animal Clinic',
            'icon' => 'bi-star-fill',
            'badge' => 'bg-purple text-white',
            'cases' => 0, 'large_cases' => 0, 'small_cases' => 0,
            'affected' => 0, 'deaths' => 0,
            'revenue_consultation' => 0.0, 'revenue_drugs' => 0.0, 'revenue_total' => 0.0,
            'diagnoses' => [], 'actions' => ['Field Visit' => 0, 'Prescriptions' => 0, 'Direct Medication' => 0]
        ],
        'poultry' => [
            'key' => 'poultry',
            'name' => 'Poultry & Avian Birds',
            'clinic' => 'Small / Large Clinic',
            'icon' => 'bi-egg-fried',
            'badge' => 'bg-warning text-dark',
            'cases' => 0, 'large_cases' => 0, 'small_cases' => 0,
            'affected' => 0, 'deaths' => 0,
            'revenue_consultation' => 0.0, 'revenue_drugs' => 0.0, 'revenue_total' => 0.0,
            'diagnoses' => [], 'actions' => ['Field Visit' => 0, 'Prescriptions' => 0, 'Direct Medication' => 0]
        ],
        'rabbits' => [
            'key' => 'rabbits',
            'name' => 'Rabbits & Small Pets',
            'clinic' => 'Small Animal Clinic',
            'icon' => 'bi-patch-check',
            'badge' => 'bg-teal text-white',
            'cases' => 0, 'large_cases' => 0, 'small_cases' => 0,
            'affected' => 0, 'deaths' => 0,
            'revenue_consultation' => 0.0, 'revenue_drugs' => 0.0, 'revenue_total' => 0.0,
            'diagnoses' => [], 'actions' => ['Field Visit' => 0, 'Prescriptions' => 0, 'Direct Medication' => 0]
        ],
        'others' => [
            'key' => 'others',
            'name' => 'Other Animals & Wildlife',
            'clinic' => 'General Outpatient',
            'icon' => 'bi-grid-fill',
            'badge' => 'bg-dark text-white',
            'cases' => 0, 'large_cases' => 0, 'small_cases' => 0,
            'affected' => 0, 'deaths' => 0,
            'revenue_consultation' => 0.0, 'revenue_drugs' => 0.0, 'revenue_total' => 0.0,
            'diagnoses' => [], 'actions' => ['Field Visit' => 0, 'Prescriptions' => 0, 'Direct Medication' => 0]
        ]
    ];

    $totals = [
        'grand_total_cases' => 0,
        'large_animal_cases' => 0,
        'small_animal_cases' => 0,
        'grand_total_affected' => 0,
        'grand_total_deaths' => 0,
        'grand_total_consultation' => 0.0,
        'grand_total_drugs' => 0.0,
        'grand_total_revenue' => 0.0,
        'govt_drug_deductions_count' => 0,
        'private_drugs_bypassed_count' => 0
    ];

    $diagnoses_large = [];
    $diagnoses_small = [];

    foreach ($records as $r) {
        $sp = strtolower($r['species'] ?? '');
        $isLarge = ($r['clinic_type'] === 'large_animal');
        $catKey = 'others';

        if (strpos($sp, 'buffalo') !== false) {
            $catKey = 'buffaloes';
        } elseif (strpos($sp, 'cattle') !== false || strpos($sp, 'cow') !== false || strpos($sp, 'bull') !== false || strpos($sp, 'calf') !== false) {
            $catKey = 'cattle';
        } elseif (strpos($sp, 'goat') !== false) {
            $catKey = 'goats';
        } elseif (strpos($sp, 'sheep') !== false || strpos($sp, 'ram') !== false) {
            $catKey = 'sheep';
        } elseif (strpos($sp, 'swine') !== false || strpos($sp, 'pig') !== false) {
            $catKey = 'swine';
        } elseif (strpos($sp, 'horse') !== false || strpos($sp, 'equine') !== false) {
            $catKey = 'horses';
        } elseif (strpos($sp, 'dog') !== false || strpos($sp, 'canine') !== false || strpos($sp, 'pup') !== false) {
            $catKey = 'dogs';
        } elseif (strpos($sp, 'cat') !== false || strpos($sp, 'feline') !== false || strpos($sp, 'kitten') !== false) {
            $catKey = 'cats';
        } elseif (strpos($sp, 'poultry') !== false || strpos($sp, 'bird') !== false || strpos($sp, 'chicken') !== false || strpos($sp, 'avian') !== false) {
            $catKey = 'poultry';
        } elseif (strpos($sp, 'rabbit') !== false || strpos($sp, 'hamster') !== false) {
            $catKey = 'rabbits';
        }

        $aff = max(1, intval($r['clinically_affected_count'] ?? 1));
        $dth = max(0, intval($r['death_count'] ?? 0));
        $revC = floatval($r['revenue_consultation'] ?? 0);
        $revD = floatval($r['revenue_drugs'] ?? 0);
        $revT = floatval($r['revenue_total'] ?? ($revC + $revD));
        $diag = trim($r['tentative_diagnosis'] ?? 'Unspecified');
        $actType = $r['action_type'] ?? 'Direct Medication';

        // Tally category
        $categories[$catKey]['cases']++;
        if ($isLarge) {
            $categories[$catKey]['large_cases']++;
            $totals['large_animal_cases']++;
            if (!empty($diag)) {
                $diagnoses_large[$diag] = ($diagnoses_large[$diag] ?? 0) + 1;
            }
            if ($revD > 0) {
                $totals['govt_drug_deductions_count']++;
            }
        } else {
            $categories[$catKey]['small_cases']++;
            $totals['small_animal_cases']++;
            if (!empty($diag)) {
                $diagnoses_small[$diag] = ($diagnoses_small[$diag] ?? 0) + 1;
            }
            if ($revD > 0) {
                $totals['private_drugs_bypassed_count']++;
            }
        }

        $categories[$catKey]['affected'] += $aff;
        $categories[$catKey]['deaths']   += $dth;
        $categories[$catKey]['revenue_consultation'] += $revC;
        $categories[$catKey]['revenue_drugs']        += $revD;
        $categories[$catKey]['revenue_total']        += $revT;

        if (!empty($diag)) {
            $categories[$catKey]['diagnoses'][$diag] = ($categories[$catKey]['diagnoses'][$diag] ?? 0) + 1;
        }

        if (strpos($actType, 'Field') !== false) {
            $categories[$catKey]['actions']['Field Visit']++;
        } elseif (strpos($actType, 'Prescription') !== false) {
            $categories[$catKey]['actions']['Prescriptions']++;
        } else {
            $categories[$catKey]['actions']['Direct Medication']++;
        }

        // Tally grand totals
        $totals['grand_total_cases']++;
        $totals['grand_total_affected']     += $aff;
        $totals['grand_total_deaths']       += $dth;
        $totals['grand_total_consultation'] += $revC;
        $totals['grand_total_drugs']        += $revD;
        $totals['grand_total_revenue']      += $revT;
    }

    arsort($diagnoses_large);
    arsort($diagnoses_small);

    // Format top diagnoses for each category
    foreach ($categories as $k => &$cat) {
        arsort($cat['diagnoses']);
        $cat['top_diagnoses_list'] = array_slice($cat['diagnoses'], 0, 3, true);
    }
    unset($cat);

    return [
        'range_id'        => $range_id,
        'year'            => $selected_year,
        'month'           => $selected_month,
        'period_label'    => ($selected_month > 0) ? date('F', mktime(0, 0, 0, $selected_month, 1)) . " {$selected_year}" : "Full Year {$selected_year}",
        'totals'          => $totals,
        'categories'      => array_values($categories),
        'diagnoses_large' => array_slice($diagnoses_large, 0, 6, true),
        'diagnoses_small' => array_slice($diagnoses_small, 0, 6, true),
        'raw_records_count' => count($records)
    ];
}
