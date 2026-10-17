<?php
session_start();
require_once __DIR__ . '/../../../../config/db_connect.php';

/** @var mysqli $mysqli */
global $mysqli;

if (!isset($_SESSION['logged_in']) || $_SESSION['role'] !== 'veterinary_surgeon' || !isset($_SESSION['range_id'])) {
    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
        echo json_encode(['success' => false, 'message' => 'Unauthorized']);
        exit();
    }
    header('Location: ../../../../index.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $range_id     = $_SESSION['range_id'];
    $district_id  = $_SESSION['district_id'] ?? null;
    $report_year  = intval($_POST['report_year'] ?? date('Y'));
    $report_month = intval($_POST['report_month'] ?? date('n'));
    $items        = $_POST['items'] ?? [];

    if (empty($range_id) || empty($report_year) || empty($report_month)) {
        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
            echo json_encode(['success' => false, 'message' => 'Invalid parameters.']);
            exit();
        }
        header("Location: ../section_e.php?year=$report_year&month=$report_month&status=db_error");
        exit();
    }

    // Lookup district_id if missing
    if (empty($district_id)) {
        $stmt_d = $mysqli->prepare("SELECT district_id FROM veterinary_ranges WHERE id = ?");
        if ($stmt_d) {
            $stmt_d->bind_param("i", $range_id);
            $stmt_d->execute();
            $stmt_d->bind_result($district_id);
            $stmt_d->fetch();
            $stmt_d->close();
        }
    }

    $stmt = $mysqli->prepare("
        INSERT INTO section_e (
            district_id, range_id, report_year, report_month, category_id, item_id, amount
        ) VALUES (?, ?, ?, ?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE 
            category_id = VALUES(category_id),
            amount      = VALUES(amount),
            updated_at  = NOW()
    ");

    $saved_count = 0;
    foreach ($items as $item) {
        $item_id     = intval($item['item_id'] ?? 0);
        $category_id = intval($item['category_id'] ?? 0);
        $amount      = floatval($item['amount'] ?? 0);

        if ($item_id <= 0 || $category_id <= 0) continue;

        if ($stmt) {
            $stmt->bind_param(
                "iiiiidd",
                $district_id, $range_id, $report_year, $report_month, $category_id, $item_id, $amount
            );
            if ($stmt->execute()) {
                $saved_count++;
            }
        }
    }
    // -----------------------------------------------------------------
    // AUTOMATED DATA PULLS: Directly populate AI data & Cash Receipts revenue
    // -----------------------------------------------------------------
    // 1. Pull AI Data directly from breeding_ai_performance
    $ai_stmt = $mysqli->prepare("
        SELECT 
            COUNT(*) AS total_ai,
            SUM(CASE WHEN LOWER(ai_type) LIKE '%goat%' OR LOWER(cow_id) LIKE '%goat%' OR LOWER(semen_code) LIKE '%goat%' THEN 1 ELSE 0 END) AS goat_ai,
            SUM(CASE WHEN LOWER(ai_type) LIKE '%buff%' OR LOWER(cow_id) LIKE '%buff%' OR LOWER(semen_code) LIKE '%buff%' THEN 1 ELSE 0 END) AS buffalo_ai,
            SUM(CASE WHEN LOWER(ai_type) NOT LIKE '%goat%' AND LOWER(cow_id) NOT LIKE '%goat%' AND LOWER(semen_code) NOT LIKE '%goat%'
                      AND LOWER(ai_type) NOT LIKE '%buff%' AND LOWER(cow_id) NOT LIKE '%buff%' AND LOWER(semen_code) NOT LIKE '%buff%' THEN 1 ELSE 0 END) AS cattle_ai
        FROM breeding_ai_performance
        WHERE range_id = ? AND report_year = ? AND report_month = ?
    ");
    if ($ai_stmt) {
        $ai_stmt->bind_param("iii", $range_id, $report_year, $report_month);
        $ai_stmt->execute();
        $ai_data = $ai_stmt->get_result()->fetch_assoc();
        $ai_stmt->close();

        if ($ai_data) {
            $cattle_ai  = intval($ai_data['cattle_ai']);
            $goat_ai    = intval($ai_data['goat_ai']);
            $buffalo_ai = intval($ai_data['buffalo_ai']);
            $total_ai   = intval($ai_data['total_ai']);

            // Upsert into Section E: Item 94 (Neat Cattle), 95 (Goat), 96 (Buffaloa), 98 (Used semen)
            $auto_ai_items = [
                94 => ['cat' => 10, 'val' => $cattle_ai],
                95 => ['cat' => 10, 'val' => $goat_ai],
                96 => ['cat' => 10, 'val' => $buffalo_ai],
                98 => ['cat' => 11, 'val' => $total_ai]
            ];

            foreach ($auto_ai_items as $a_item_id => $a_info) {
                if ($stmt) {
                    $amt_val = floatval($a_info['val']);
                    $stmt->bind_param(
                        "iiiiidd",
                        $district_id, $range_id, $report_year, $report_month, $a_info['cat'], $a_item_id, $amt_val
                    );
                    $stmt->execute();
                }
            }
        }
    }

    // 2. Pull Revenue sums directly from cash_book_summaries
    $cb_stmt = $mysqli->prepare("
        SELECT COALESCE(SUM(total_amount), 0) AS total_revenue
        FROM cash_book_summaries
        WHERE range_id = ? AND report_year = ? AND report_month = ?
    ");
    if ($cb_stmt) {
        $cb_stmt->bind_param("iii", $range_id, $report_year, $report_month);
        $cb_stmt->execute();
        $cb_row = $cb_stmt->get_result()->fetch_assoc();
        $cb_stmt->close();

        $rev_val = floatval($cb_row['total_revenue'] ?? 0.0);
        // Upsert into Section E: Item 102 (Paid amount in Semen Details / Revenue)
        if ($stmt) {
            $cat_11 = 11;
            $item_102 = 102;
            $stmt->bind_param(
                "iiiiidd",
                $district_id, $range_id, $report_year, $report_month, $cat_11, $item_102, $rev_val
            );
            $stmt->execute();
        }
    }

    // 3. Auto-calculate Semen ending balance for Item 103 (Balance available end of month)
    $semen_calc = $mysqli->prepare("
        SELECT item_id, amount FROM section_e 
        WHERE range_id = ? AND report_year = ? AND report_month = ? AND item_id IN (97, 98, 99, 100, 101)
    ");
    if ($semen_calc) {
        $semen_calc->bind_param("iii", $range_id, $report_year, $report_month);
        $semen_calc->execute();
        $s_res = $semen_calc->get_result();
        $s_vals = [];
        while ($sr = $s_res->fetch_assoc()) {
            $s_vals[intval($sr['item_id'])] = floatval($sr['amount']);
        }
        $semen_calc->close();

        $beg_s   = $s_vals[97] ?? 0.0;
        $used_s  = $s_vals[98] ?? 0.0;
        $spoil_s = $s_vals[99] ?? 0.0;
        $rec_s   = $s_vals[100] ?? 0.0;
        $iss_s   = $s_vals[101] ?? 0.0;

        $end_balance = max(0.0, ($beg_s + $rec_s) - ($used_s + $iss_s + $spoil_s));
        if ($stmt) {
            $cat_11 = 11;
            $item_103 = 103;
            $stmt->bind_param(
                "iiiiidd",
                $district_id, $range_id, $report_year, $report_month, $cat_11, $item_103, $end_balance
            );
            $stmt->execute();
        }
    }

    if ($stmt) $stmt->close();

    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
        echo json_encode(['success' => true, 'saved' => $saved_count]);
        exit();
    }

    header("Location: ../section_e.php?year=$report_year&month=$report_month&status=saved");
    exit();
}

header("Location: ../section_e.php");
exit();
