<?php
/**
 * includes/get_counterfoil_types_helper.php
 * Real-time Auto-suggest endpoint logic for Counterfoil Book Types
 */

if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
    session_start();
}

if (!function_exists('getCounterfoilTypeSuggestions')) {
    function getCounterfoilTypeSuggestions($mysqli, $query = '') {
        $baseline_categories = [
            'AI Register',
            'Animal Transport',
            'AI Certificate Book',
            'Cash Receipt Book',
            'Certificate for Slaughter of Buffalo',
            'Fuel Order',
            'Holiday Warrant',
            'Health Certificate',
            'Issue Order',
            'Ownership Voucher',
            'Receive Order (Receipt Order)',
            'Railway Warrant Goods',
            'Register of Cattle Branded',
            'GVS 01',
            'AI Performance',
            'Calf Register',
            'PD Register',
            'Cattle Voucher',
            'Disease Outbreak Register',
            'OPD Register',
            'PIV (Schedule 08)',
            'Register for Animal Identification',
            'ARV Register',
            'Animal Birth Control Register',
            'Produce Register'
        ];

        $suggestions = [];
        $seen = [];

        // 1. Query database for matching counterfoil types from master table and previously entered assets
        if (isset($mysqli) && !$mysqli->connect_error) {
            if (!empty($query)) {
                $search_pattern = '%' . $query . '%';
                $sql = "
                    SELECT DISTINCT type_name FROM (
                        SELECT type_name FROM master_counterfoil_types WHERE is_active = 1 AND type_name LIKE ?
                        UNION
                        SELECT counterfoil_type AS type_name FROM counterfoil_assets WHERE is_active = 1 AND counterfoil_type IS NOT NULL AND counterfoil_type != '' AND counterfoil_type LIKE ?
                    ) AS combined_types
                    ORDER BY type_name ASC
                    LIMIT 25
                ";
                if ($stmt = $mysqli->prepare($sql)) {
                    $stmt->bind_param("ss", $search_pattern, $search_pattern);
                    $stmt->execute();
                    $res = $stmt->get_result();
                    while ($row = $res->fetch_assoc()) {
                        $type_name = trim($row['type_name']);
                        $lower = strtolower($type_name);
                        if (!empty($type_name) && !isset($seen[$lower])) {
                            $seen[$lower] = true;
                            $suggestions[] = $type_name;
                        }
                    }
                    $stmt->close();
                }
            } else {
                // Return active master types + distinct saved assets
                $sql = "
                    SELECT DISTINCT type_name FROM (
                        SELECT type_name FROM master_counterfoil_types WHERE is_active = 1
                        UNION
                        SELECT counterfoil_type AS type_name FROM counterfoil_assets WHERE is_active = 1 AND counterfoil_type IS NOT NULL AND counterfoil_type != ''
                    ) AS combined_types
                    ORDER BY type_name ASC
                    LIMIT 35
                ";
                if ($res = $mysqli->query($sql)) {
                    while ($row = $res->fetch_assoc()) {
                        $type_name = trim($row['type_name']);
                        $lower = strtolower($type_name);
                        if (!empty($type_name) && !isset($seen[$lower])) {
                            $seen[$lower] = true;
                            $suggestions[] = $type_name;
                        }
                    }
                }
            }
        }

        // 2. Merge baseline categories as fallback / comprehensive completion
        foreach ($baseline_categories as $cat) {
            if (empty($query) || stripos($cat, $query) !== false) {
                $lower = strtolower($cat);
                if (!isset($seen[$lower])) {
                    $seen[$lower] = true;
                    $suggestions[] = $cat;
                }
            }
        }

        return $suggestions;
    }
}

// Only output JSON if invoked directly as an HTTP endpoint or script (not just required as a library)
if (!defined('COUNTERFOIL_TYPES_HELPER_NO_OUTPUT')) {
    if (!headers_sent()) {
        header('Content-Type: application/json');
    }

    // Ensure user is authenticated (unless running in CLI)
    if (php_sapi_name() !== 'cli' && (!isset($_SESSION['logged_in']) || empty($_SESSION['user_id']))) {
        echo json_encode(['success' => false, 'suggestions' => []]);
        exit();
    }

    require_once __DIR__ . '/../config/db_connect.php';

    $query = trim($_GET['q'] ?? $_POST['q'] ?? '');
    $suggestions = getCounterfoilTypeSuggestions($mysqli, $query);

    echo json_encode([
        'success' => true,
        'query' => $query,
        'suggestions' => $suggestions
    ]);
}
