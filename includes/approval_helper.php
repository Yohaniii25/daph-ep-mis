<?php
/**
 * includes/approval_helper.php
 * Staged Approval Workflow Engine for HR and Inventory Modules
 */

if (!function_exists('stage_or_apply_edit')) {
    /**
     * Determine if an edit should be staged or applied directly.
     * Provincial Director edits are pre-authorized.
     * All other users (District DD, VS, SMS, Farm DD, etc.) are staged.
     *
     * @param mysqli $mysqli Database connection
     * @param string $module 'hr' or 'inventory'
     * @param string $record_type Table name, e.g. 'users', 'furniture_assets', etc.
     * @param int $record_id Primary key ID in the target table
     * @param string $target_name Readable title, e.g. officer name or item name
     * @param array $old_data Associative array of existing database values
     * @param array $new_data Associative array of proposed new values
     * @param int|null $district_id
     * @param int|null $range_id
     * @return array ['is_staged' => bool, 'approval_id' => int|null, 'message' => string]
     */
    function stage_or_apply_edit($mysqli, $module, $record_type, $record_id, $target_name, $old_data, $new_data, $district_id = null, $range_id = null) {
        $user_role = $_SESSION['role'] ?? '';
        $user_id   = intval($_SESSION['user_id'] ?? 0);
        $user_name = $_SESSION['full_name'] ?? $_SESSION['username'] ?? 'User #' . $user_id;

        // 1. Provincial Director edits bypass staging (self-authorized executive)
        if ($user_role === 'provincial_director') {
            return [
                'is_staged' => false,
                'approval_id' => null,
                'message' => 'Authorized as Provincial Director.'
            ];
        }

        // 2. Stage the edit into pending_approvals table
        $old_json = json_encode($old_data, JSON_UNESCAPED_UNICODE);
        $new_json = json_encode($new_data, JSON_UNESCAPED_UNICODE);

        $stmt = $mysqli->prepare("
            INSERT INTO pending_approvals 
            (module, record_type, record_id, target_name, requested_by, requester_name, requester_role, district_id, range_id, old_data, new_data, status, created_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', NOW())
        ");

        if (!$stmt) {
            return [
                'is_staged' => false,
                'error' => 'Failed to prepare staging statement: ' . $mysqli->error
            ];
        }

        $stmt->bind_param(
            "ssisisiiiss",
            $module,
            $record_type,
            $record_id,
            $target_name,
            $user_id,
            $user_name,
            $user_role,
            $district_id,
            $range_id,
            $old_json,
            $new_json
        );

        $executed = $stmt->execute();
        $approval_id = $mysqli->insert_id;
        $stmt->close();

        if ($executed && $approval_id) {
            // 3. Notify the Provincial Director with an in-app notification & bell badge
            $module_label = ($module === 'hr') ? 'Human Resources' : 'Inventory';
            $notif_title = 'Pending Authorization Required';
            $notif_msg = "User {$user_name} (" . ucwords(str_replace('_', ' ', $user_role)) . ") submitted edits for {$module_label} record '{$target_name}'. Approval required.";
            $notif_link = 'pages/modules/pd/pending_approvals.php';

            $pd_res = $mysqli->query("SELECT id FROM users WHERE role = 'provincial_director' AND is_active = 1");
            if ($pd_res) {
                $ins_notif = $mysqli->prepare("
                    INSERT INTO notifications (user_id, title, message, type, link, is_read, created_at) 
                    VALUES (?, ?, ?, 'approval_required', ?, 0, NOW())
                ");
                if ($ins_notif) {
                    while ($pd_user = $pd_res->fetch_assoc()) {
                        $p_uid = intval($pd_user['id']);
                        $ins_notif->bind_param("isss", $p_uid, $notif_title, $notif_msg, $notif_link);
                        $ins_notif->execute();
                    }
                    $ins_notif->close();
                }
            }

            return [
                'is_staged' => true,
                'approval_id' => $approval_id,
                'message' => 'Edit submitted successfully. Changes are pending authorization by the Provincial Director.'
            ];
        }

        return [
            'is_staged' => false,
            'error' => 'Database execution failed while staging edit.'
        ];
    }
}

if (!function_exists('get_pending_approvals_count')) {
    /**
     * Get count of pending approvals
     */
    function get_pending_approvals_count($mysqli, $module = null) {
        if (!$mysqli) return 0;
        if (!empty($module)) {
            $stmt = $mysqli->prepare("SELECT COUNT(*) AS cnt FROM pending_approvals WHERE status = 'pending' AND module = ?");
            $stmt->bind_param("s", $module);
            $stmt->execute();
            $row = $stmt->get_result()->fetch_assoc();
            $stmt->close();
            return intval($row['cnt'] ?? 0);
        } else {
            $res = $mysqli->query("SELECT COUNT(*) AS cnt FROM pending_approvals WHERE status = 'pending'");
            if ($res && $row = $res->fetch_assoc()) {
                return intval($row['cnt'] ?? 0);
            }
        }
        return 0;
    }
}

if (!function_exists('get_pending_approvals')) {
    /**
     * Retrieve all pending approval records
     */
    function get_pending_approvals($mysqli, $module = null, $limit = 100) {
        $records = [];
        if (!$mysqli) return $records;

        $sql = "
            SELECT 
                pa.*,
                d.name AS district_name,
                vr.name AS range_name
            FROM pending_approvals pa
            LEFT JOIN districts d ON pa.district_id = d.id
            LEFT JOIN veterinary_ranges vr ON pa.range_id = vr.id
            WHERE pa.status = 'pending'
        ";
        if (!empty($module)) {
            $sql .= " AND pa.module = '" . $mysqli->real_escape_string($module) . "'";
        }
        $sql .= " ORDER BY pa.created_at DESC LIMIT " . intval($limit);

        $res = $mysqli->query($sql);
        if ($res) {
            while ($row = $res->fetch_assoc()) {
                $row['old_data_arr'] = json_decode($row['old_data'] ?? '{}', true) ?: [];
                $row['new_data_arr'] = json_decode($row['new_data'] ?? '{}', true) ?: [];
                $row['diff'] = compute_record_diff($row['old_data_arr'], $row['new_data_arr']);
                $records[] = $row;
            }
        }
        return $records;
    }
}

if (!function_exists('compute_record_diff')) {
    /**
     * Compute field-by-field diff between old and new data
     */
    function compute_record_diff($old_arr, $new_arr) {
        $diff = [];
        $ignored_keys = ['id', 'updated_at', 'created_at', 'password'];

        foreach ($new_arr as $key => $new_val) {
            if (in_array($key, $ignored_keys)) continue;
            $old_val = $old_arr[$key] ?? null;

            // Loose comparison to prevent int vs string false diffs (e.g. "5" == 5)
            if ((string)$old_val !== (string)$new_val) {
                $field_label = ucwords(str_replace('_', ' ', $key));
                $diff[$key] = [
                    'label' => $field_label,
                    'old' => $old_val !== null && $old_val !== '' ? $old_val : '(empty)',
                    'new' => $new_val !== null && $new_val !== '' ? $new_val : '(empty)'
                ];
            }
        }
        return $diff;
    }
}

if (!function_exists('approve_pending_edit')) {
    /**
     * Approve a pending edit: update the live table and mark staging record as approved
     */
    function approve_pending_edit($mysqli, $approval_id, $reviewer_id) {
        if (!$mysqli || empty($approval_id)) {
            return ['success' => false, 'message' => 'Invalid approval identifier.'];
        }

        $stmt = $mysqli->prepare("SELECT * FROM pending_approvals WHERE id = ? AND status = 'pending'");
        $stmt->bind_param("i", $approval_id);
        $stmt->execute();
        $approval = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$approval) {
            return ['success' => false, 'message' => 'Pending approval record not found or already processed.'];
        }

        $record_type = $approval['record_type'];
        $record_id   = intval($approval['record_id']);
        $new_data    = json_decode($approval['new_data'] ?? '{}', true) ?: [];

        if (empty($new_data) || $record_id <= 0) {
            return ['success' => false, 'message' => 'Proposed changes data is empty or invalid.'];
        }

        // Apply changes to the live table
        $update_ok = apply_changes_to_live_table($mysqli, $record_type, $record_id, $new_data);

        if (!$update_ok) {
            return ['success' => false, 'message' => 'Failed to apply staged updates to live table: ' . $mysqli->error];
        }

        // Update pending_approvals row
        $upd_stmt = $mysqli->prepare("
            UPDATE pending_approvals 
            SET status = 'approved', reviewed_by = ?, reviewed_at = NOW() 
            WHERE id = ?
        ");
        $upd_stmt->bind_param("ii", $reviewer_id, $approval_id);
        $upd_stmt->execute();
        $upd_stmt->close();

        // Send in-app notification to requester
        $req_id = intval($approval['requested_by']);
        if ($req_id > 0) {
            $notif_title = 'Modifications Authorized';
            $notif_msg = "Your proposed modifications for '" . htmlspecialchars($approval['target_name']) . "' have been approved by the Provincial Director and updated in live records.";
            $ins_n = $mysqli->prepare("INSERT INTO notifications (user_id, title, message, type, is_read, created_at) VALUES (?, ?, ?, 'approval_result', 0, NOW())");
            if ($ins_n) {
                $ins_n->bind_param("iss", $req_id, $notif_title, $notif_msg);
                $ins_n->execute();
                $ins_n->close();
            }
        }

        return [
            'success' => true,
            'message' => "Modifications for '" . htmlspecialchars($approval['target_name']) . "' successfully approved and applied."
        ];
    }
}

if (!function_exists('reject_pending_edit')) {
    /**
     * Reject a pending edit: leave live table untouched and log rejection reason
     */
    function reject_pending_edit($mysqli, $approval_id, $reviewer_id, $reason = '') {
        if (!$mysqli || empty($approval_id)) {
            return ['success' => false, 'message' => 'Invalid approval identifier.'];
        }

        $stmt = $mysqli->prepare("SELECT * FROM pending_approvals WHERE id = ? AND status = 'pending'");
        $stmt->bind_param("i", $approval_id);
        $stmt->execute();
        $approval = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$approval) {
            return ['success' => false, 'message' => 'Pending approval record not found or already processed.'];
        }

        $reason_clean = trim($reason);

        // Update pending_approvals row
        $upd_stmt = $mysqli->prepare("
            UPDATE pending_approvals 
            SET status = 'rejected', rejection_reason = ?, reviewed_by = ?, reviewed_at = NOW() 
            WHERE id = ?
        ");
        $upd_stmt->bind_param("sii", $reason_clean, $reviewer_id, $approval_id);
        $upd_stmt->execute();
        $upd_stmt->close();

        // Send in-app notification to requester
        $req_id = intval($approval['requested_by']);
        if ($req_id > 0) {
            $notif_title = 'Modifications Rejected';
            $notif_msg = "Your proposed modifications for '" . htmlspecialchars($approval['target_name']) . "' were rejected by the Provincial Director." . (!empty($reason_clean) ? " Reason: {$reason_clean}" : "");
            $ins_n = $mysqli->prepare("INSERT INTO notifications (user_id, title, message, type, is_read, created_at) VALUES (?, ?, ?, 'approval_result', 0, NOW())");
            if ($ins_n) {
                $ins_n->bind_param("iss", $req_id, $notif_title, $notif_msg);
                $ins_n->execute();
                $ins_n->close();
            }
        }

        return [
            'success' => true,
            'message' => "Modifications for '" . htmlspecialchars($approval['target_name']) . "' have been rejected."
        ];
    }
}

if (!function_exists('apply_changes_to_live_table')) {
    /**
     * Apply decoded changes to the appropriate target table
     */
    function apply_changes_to_live_table($mysqli, $table_name, $record_id, $data) {
        $allowed_tables = [
            'users',
            'office_details',
            'building_inventories',
            'furniture_assets',
            'machinery_assets',
            'instrument_assets',
            'registered_vehicles',
            'land_assets',
            'counterfoil_assets'
        ];

        if (!in_array($table_name, $allowed_tables)) {
            return false;
        }

        // Get table columns
        $col_res = $mysqli->query("SHOW COLUMNS FROM `{$table_name}`");
        $valid_cols = [];
        if ($col_res) {
            while ($c = $col_res->fetch_assoc()) {
                $valid_cols[$c['Field']] = true;
            }
        }

        $set_clauses = [];
        $types = '';
        $values = [];

        foreach ($data as $col => $val) {
            if ($col === 'id' || !isset($valid_cols[$col])) continue;

            $set_clauses[] = "`{$col}` = ?";
            if (is_int($val)) {
                $types .= 'i';
            } elseif (is_float($val)) {
                $types .= 'd';
            } else {
                $types .= 's';
            }
            $values[] = $val;
        }

        if (empty($set_clauses)) {
            return true; // No columns to update
        }

        $sql = "UPDATE `{$table_name}` SET " . implode(', ', $set_clauses) . " WHERE `id` = ?";
        $types .= 'i';
        $values[] = $record_id;

        $stmt = $mysqli->prepare($sql);
        if (!$stmt) return false;

        $stmt->bind_param($types, ...$values);
        $ok = $stmt->execute();
        $stmt->close();
        return $ok;
    }
}
