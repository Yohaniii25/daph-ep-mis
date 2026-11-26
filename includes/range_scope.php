<?php
/**
 * Shared Veterinary Range Scope Helper
 *
 * Resolves the active range_id (supporting dynamic ?range_id=... switching for
 * supervisory/read-only roles) and fetches range/district metadata.
 */

if (!function_exists('resolve_range_scope')) {
    /**
     * Resolve active range_id, range/district metadata, and available ranges list.
     *
     * @return array{range_id:int, range_name:string, district_name:string, district_id:?int, ranges:array}
     */
    function resolve_range_scope(mysqli $mysqli, bool $is_supervisory = false): array
    {
        $session_range_id = !empty($_SESSION['range_id']) ? (int)$_SESSION['range_id'] : 0;
        $user_district_id = !empty($_SESSION['district_id']) ? (int)$_SESSION['district_id'] : null;
        $user_role = (string)($_SESSION['role'] ?? '');

        $ranges = [];
        if ($is_supervisory) {
            if ($user_district_id && in_array($user_role, ['district_dd', 'deputy_director_district', 'sms'], true)) {
                $stmt = $mysqli->prepare("
                    SELECT vr.id, vr.name, vr.district_id, d.name AS district_name
                    FROM veterinary_ranges vr
                    LEFT JOIN districts d ON vr.district_id = d.id
                    WHERE vr.district_id = ?
                    ORDER BY vr.name ASC
                ");
                if ($stmt) {
                    $stmt->bind_param('i', $user_district_id);
                    $stmt->execute();
                    $ranges = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
                    $stmt->close();
                }
            } else {
                $res = $mysqli->query("
                    SELECT vr.id, vr.name, vr.district_id, d.name AS district_name
                    FROM veterinary_ranges vr
                    LEFT JOIN districts d ON vr.district_id = d.id
                    ORDER BY d.name ASC, vr.name ASC
                ");
                if ($res) {
                    $ranges = $res->fetch_all(MYSQLI_ASSOC);
                }
            }
        }

        $requested_range_id = ($is_supervisory && isset($_GET['range_id']) && is_numeric($_GET['range_id']))
            ? (int)$_GET['range_id']
            : 0;

        $range_id = $requested_range_id > 0 ? $requested_range_id : $session_range_id;
        if ($range_id <= 0) {
            $range_id = !empty($ranges) ? (int)$ranges[0]['id'] : 1;
        }

        $range_name = 'Unknown Range';
        $district_name = 'Unknown District';
        $district_id = $user_district_id;

        $info_stmt = $mysqli->prepare("
            SELECT vr.name AS range_name, vr.district_id, d.name AS district_name
            FROM veterinary_ranges vr
            LEFT JOIN districts d ON vr.district_id = d.id
            WHERE vr.id = ?
            LIMIT 1
        ");
        if ($info_stmt) {
            $info_stmt->bind_param('i', $range_id);
            $info_stmt->execute();
            if ($row = $info_stmt->get_result()->fetch_assoc()) {
                $range_name = $row['range_name'] ?? $range_name;
                $district_name = $row['district_name'] ?? $district_name;
                if (!$district_id && !empty($row['district_id'])) {
                    $district_id = (int)$row['district_id'];
                }
            }
            $info_stmt->close();
        }

        return [
            'range_id' => $range_id,
            'range_name' => $range_name,
            'district_name' => $district_name,
            'district_id' => $district_id,
            'ranges' => $ranges,
        ];
    }
}
