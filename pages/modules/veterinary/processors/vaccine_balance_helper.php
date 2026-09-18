<?php
/**
 * Vaccine Balance Live Deduction & Restoration Helper
 */

function deductVaccineBalance($mysqli, $range_id, $report_year, $report_month, $vaccine_name, $doses_to_deduct, $batch_no = null, $expiry_date = null, $user_id = 1) {
    if ($doses_to_deduct == 0 || empty($vaccine_name) || $range_id <= 0) {
        return;
    }

    $vax_like = $vaccine_name . '%';
    $check_stmt = $mysqli->prepare("
        SELECT id, opening_balance, received_doses, used_doses, spoilt_damaged_doses, transferred_doses, batch_no, expiry_date 
        FROM monthly_vaccine_balances 
        WHERE range_id = ? AND report_year = ? AND report_month = ? AND (vaccine_name = ? OR vaccine_name LIKE ?)
        LIMIT 1
    ");
    if ($check_stmt) {
        $check_stmt->bind_param("iiiss", $range_id, $report_year, $report_month, $vaccine_name, $vax_like);
        $check_stmt->execute();
        $row = $check_stmt->get_result()->fetch_assoc();
        $check_stmt->close();

        if ($row) {
            $bal_id = $row['id'];
            $new_used = max(0, intval($row['used_doses']) + $doses_to_deduct);
            $new_closing = max(0, intval($row['opening_balance']) + intval($row['received_doses']) - $new_used - intval($row['spoilt_damaged_doses']) - intval($row['transferred_doses']));
            
            $up_stmt = $mysqli->prepare("UPDATE monthly_vaccine_balances SET used_doses = ?, closing_balance = ? WHERE id = ?");
            if ($up_stmt) {
                $up_stmt->bind_param("iii", $new_used, $new_closing, $bal_id);
                $up_stmt->execute();
                $up_stmt->close();
            }
        } elseif ($doses_to_deduct > 0) {
            // Auto-pull prior month closing balance
            $prior_month = ($report_month == 1) ? 12 : ($report_month - 1);
            $prior_year  = ($report_month == 1) ? ($report_year - 1) : $report_year;
            $opening = 0;

            $pm_stmt = $mysqli->prepare("SELECT closing_balance, batch_no, expiry_date FROM monthly_vaccine_balances WHERE range_id = ? AND (vaccine_name = ? OR vaccine_name LIKE ?) AND report_year = ? AND report_month = ? ORDER BY id DESC LIMIT 1");
            if ($pm_stmt) {
                $pm_stmt->bind_param("issii", $range_id, $vaccine_name, $vax_like, $prior_year, $prior_month);
                $pm_stmt->execute();
                $pm_res = $pm_stmt->get_result()->fetch_assoc();
                if ($pm_res) {
                    $opening = intval($pm_res['closing_balance']);
                    if (empty($batch_no)) $batch_no = $pm_res['batch_no'];
                    if (empty($expiry_date)) $expiry_date = $pm_res['expiry_date'];
                }
                $pm_stmt->close();
            }

            // Lookup district_id
            $dist_id = 1;
            $d_stmt = $mysqli->prepare("SELECT district_id FROM veterinary_ranges WHERE id = ?");
            if ($d_stmt) {
                $d_stmt->bind_param("i", $range_id);
                $d_stmt->execute();
                if ($dr = $d_stmt->get_result()->fetch_assoc()) $dist_id = intval($dr['district_id']);
                $d_stmt->close();
            }

            // Check if user_id is valid for FK constraint
            $valid_uid = null;
            if ($user_id && $user_id > 0) {
                $u_chk = $mysqli->prepare("SELECT id FROM users WHERE id = ?");
                if ($u_chk) {
                    $u_chk->bind_param("i", $user_id);
                    $u_chk->execute();
                    if ($ur = $u_chk->get_result()->fetch_assoc()) $valid_uid = intval($ur['id']);
                    $u_chk->close();
                }
            }

            $used = $doses_to_deduct;
            $closing = max(0, $opening - $used);
            $ins_stmt = $mysqli->prepare("INSERT INTO monthly_vaccine_balances (district_id, range_id, report_year, report_month, vaccine_name, opening_balance, received_doses, used_doses, spoilt_damaged_doses, transferred_doses, closing_balance, batch_no, expiry_date, created_by) VALUES (?, ?, ?, ?, ?, ?, 0, ?, 0, 0, ?, ?, ?, ?)");
            if ($ins_stmt) {
                $ins_stmt->bind_param("iiiisiiissi", $dist_id, $range_id, $report_year, $report_month, $vaccine_name, $opening, $used, $closing, $batch_no, $expiry_date, $valid_uid);
                $ins_stmt->execute();
                $ins_stmt->close();
            }
        }
    }
}
