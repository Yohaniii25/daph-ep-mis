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

if (!function_exists('get_pending_transfers_count')) {
    /**
     * Get count of pending transfer requests
     */
    function get_pending_transfers_count($mysqli) {
        if (!$mysqli) return 0;
        $res = $mysqli->query("SELECT COUNT(*) AS cnt FROM pending_approvals WHERE status = 'pending' AND module = 'hr' AND record_type = 'transfer_request'");
        if ($res && $row = $res->fetch_assoc()) {
            return intval($row['cnt'] ?? 0);
        }
        return 0;
    }
}

if (!function_exists('parse_transfer_target_unit')) {
    /**
     * Resolve target unit string into structured DB foreign key columns
     */
    function parse_transfer_target_unit($mysqli, $target_unit_str) {
        $result = [
            'target_unit' => $target_unit_str,
            'target_range_id' => null,
            'target_district_id' => null,
            'target_farm_id' => null,
            'target_training_center_id' => null,
            'target_district' => 'Provincial'
        ];

        $trimmed = trim($target_unit_str);

        // 1. Check Range Office
        if (stripos($trimmed, 'Range Office - ') === 0) {
            $raw_name = substr($trimmed, strlen('Range Office - '));
            $clean_name = trim(preg_replace('/\s*\(.*?\)\s*/', '', $raw_name));
            $stmt = $mysqli->prepare("SELECT id, district_id FROM veterinary_ranges WHERE name LIKE ? OR name = ? LIMIT 1");
            if ($stmt) {
                $like_param = "%{$clean_name}%";
                $stmt->bind_param("ss", $like_param, $clean_name);
                $stmt->execute();
                $r = $stmt->get_result()->fetch_assoc();
                if ($r) {
                    $result['target_range_id'] = intval($r['id']);
                    $result['target_district_id'] = intval($r['district_id']);
                    $d_res = $mysqli->query("SELECT name FROM districts WHERE id = " . intval($r['district_id']));
                    if ($d_res && $d_row = $d_res->fetch_assoc()) {
                        $result['target_district'] = $d_row['name'];
                    }
                }
                $stmt->close();
            }
        }
        // 2. Check District Office
        elseif (stripos($trimmed, 'District Office - ') === 0) {
            $dist_name = trim(substr($trimmed, strlen('District Office - ')));
            $stmt = $mysqli->prepare("SELECT id, name FROM districts WHERE name LIKE ? OR name = ? LIMIT 1");
            if ($stmt) {
                $like_param = "%{$dist_name}%";
                $stmt->bind_param("ss", $like_param, $dist_name);
                $stmt->execute();
                $r = $stmt->get_result()->fetch_assoc();
                if ($r) {
                    $result['target_district_id'] = intval($r['id']);
                    $result['target_district'] = $r['name'];
                }
                $stmt->close();
            }
        }
        // 3. Check Regional Farm
        elseif (stripos($trimmed, 'Regional Farm - ') === 0) {
            $farm_name = trim(substr($trimmed, strlen('Regional Farm - ')));
            $stmt = $mysqli->prepare("SELECT id FROM regional_farms WHERE farm_name LIKE ? OR farm_name = ? LIMIT 1");
            if ($stmt) {
                $like_param = "%{$farm_name}%";
                $stmt->bind_param("ss", $like_param, $farm_name);
                $stmt->execute();
                $r = $stmt->get_result()->fetch_assoc();
                if ($r) {
                    $result['target_farm_id'] = intval($r['id']);
                }
                $stmt->close();
            }
        }
        // 4. Check Training Center
        elseif (stripos($trimmed, 'Training Center - ') === 0) {
            $raw_tc = trim(substr($trimmed, strlen('Training Center - ')));
            $clean_tc = trim(preg_replace('/\s*\(.*?\)\s*/', '', $raw_tc));
            $stmt = $mysqli->prepare("SELECT id FROM training_centers WHERE center_name LIKE ? OR center_name = ? LIMIT 1");
            if ($stmt) {
                $like_param = "%{$clean_tc}%";
                $stmt->bind_param("ss", $like_param, $clean_tc);
                $stmt->execute();
                $r = $stmt->get_result()->fetch_assoc();
                if ($r) {
                    $result['target_training_center_id'] = intval($r['id']);
                }
                $stmt->close();
            }
        }

        if (strcasecmp($result['target_district'], 'Ampara') === 0) {
            $result['target_district'] = 'Amparai';
        }

        return $result;
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

        // Special handling for employee transfer requests
        if (isset($new_arr['target_unit'])) {
            $diff['target_unit'] = [
                'label' => 'Target Unit / Office',
                'old'   => $old_arr['current_location'] ?? ($old_arr['unit'] ?? '(Current Workstation)'),
                'new'   => $new_arr['target_unit']
            ];
            if (!empty($new_arr['reason'])) {
                $diff['reason'] = [
                    'label' => 'Reason for Transfer',
                    'old'   => '—',
                    'new'   => $new_arr['reason']
                ];
            }
            return $diff;
        }

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
        if ($record_type === 'transfer_request') {
            $target_unit = $new_data['target_unit'] ?? '';
            $t_range_id  = isset($new_data['target_range_id']) && $new_data['target_range_id'] !== '' ? intval($new_data['target_range_id']) : null;
            $t_dist_id   = isset($new_data['target_district_id']) && $new_data['target_district_id'] !== '' ? intval($new_data['target_district_id']) : null;
            $t_farm_id   = isset($new_data['target_farm_id']) && $new_data['target_farm_id'] !== '' ? intval($new_data['target_farm_id']) : null;
            $t_tc_id     = isset($new_data['target_training_center_id']) && $new_data['target_training_center_id'] !== '' ? intval($new_data['target_training_center_id']) : null;
            $t_district  = $new_data['target_district'] ?? 'Provincial';
            if (strcasecmp($t_district, 'Ampara') === 0) {
                $t_district = 'Amparai';
            }

            $upd_emp = $mysqli->prepare("
                UPDATE users 
                SET unit = ?, range_id = ?, district_id = ?, farm_id = ?, training_center_id = ?, district = ?
                WHERE id = ?
            ");
            if ($upd_emp) {
                $upd_emp->bind_param("siiiisi", $target_unit, $t_range_id, $t_dist_id, $t_farm_id, $t_tc_id, $t_district, $record_id);
                $update_ok = $upd_emp->execute();
                $upd_emp->close();
            } else {
                $update_ok = false;
            }

            // Also keep office_details in sync if a matching employee record exists
            $old_data_arr = json_decode($approval['old_data'] ?? '{}', true) ?: [];
            $emp_identifier = $old_data_arr['emp_id'] ?? ($old_data_arr['service_number'] ?? '');
            if (!empty($emp_identifier)) {
                $upd_od = $mysqli->prepare("UPDATE office_details SET range_id = ?, status = 'Active' WHERE emp_id = ?");
                if ($upd_od) {
                    $upd_od->bind_param("is", $t_range_id, $emp_identifier);
                    $upd_od->execute();
                    $upd_od->close();
                }
            }
        } else {
            $update_ok = apply_changes_to_live_table($mysqli, $record_type, $record_id, $new_data);
        }

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
        if ($record_type === 'transfer_request') {
            $notif_title = 'Employee Transfer Approved';
            $notif_msg = "Transfer request for " . htmlspecialchars($approval['target_name']) . " to [" . htmlspecialchars($new_data['target_unit'] ?? '') . "] was officially approved and updated in live records.";
            $notif_link = 'pages/modules/hr/employee_managment.php';
            if ($req_id > 0) {
                $ins_n = $mysqli->prepare("INSERT INTO notifications (user_id, title, message, type, link, is_read, created_at) VALUES (?, ?, ?, 'transfer_alert', ?, 0, NOW())");
                if ($ins_n) {
                    $ins_n->bind_param("isss", $req_id, $notif_title, $notif_msg, $notif_link);
                    $ins_n->execute();
                    $ins_n->close();
                }
            }

            // Also notify the employee themselves
            if ($record_id > 0 && $record_id !== $req_id) {
                $emp_notif_title = 'Workstation Transfer Approved';
                $emp_notif_msg = "Your official transfer to [" . htmlspecialchars($new_data['target_unit'] ?? '') . "] has been approved by the Provincial Administration. Your station records have been updated.";
                $ins_emp_n = $mysqli->prepare("INSERT INTO notifications (user_id, title, message, type, link, is_read, created_at) VALUES (?, ?, ?, 'transfer_alert', ?, 0, NOW())");
                if ($ins_emp_n) {
                    $ins_emp_n->bind_param("isss", $record_id, $emp_notif_title, $emp_notif_msg, $notif_link);
                    $ins_emp_n->execute();
                    $ins_emp_n->close();
                }
            }

            return [
                'success' => true,
                'message' => "Transfer for '" . htmlspecialchars($approval['target_name']) . "' to [" . htmlspecialchars($new_data['target_unit'] ?? '') . "] successfully approved and executed."
            ];
        } else {
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
            if ($approval['record_type'] === 'transfer_request') {
                $new_data = json_decode($approval['new_data'] ?? '{}', true) ?: [];
                $notif_title = 'Transfer Request Rejected';
                $notif_msg = "Your transfer request for '" . htmlspecialchars($approval['target_name']) . "' to [" . htmlspecialchars($new_data['target_unit'] ?? '') . "] was rejected by the Provincial Administration." . (!empty($reason_clean) ? " Reason: {$reason_clean}" : "");
                $notif_link = 'pages/modules/veterinary/employee_managment.php';
                $ins_n = $mysqli->prepare("INSERT INTO notifications (user_id, title, message, type, link, is_read, created_at) VALUES (?, ?, ?, 'transfer_alert', ?, 0, NOW())");
                if ($ins_n) {
                    $ins_n->bind_param("isss", $req_id, $notif_title, $notif_msg, $notif_link);
                    $ins_n->execute();
                    $ins_n->close();
                }
            } else {
                $notif_title = 'Modifications Rejected';
                $notif_msg = "Your proposed modifications for '" . htmlspecialchars($approval['target_name']) . "' were rejected by the Provincial Director." . (!empty($reason_clean) ? " Reason: {$reason_clean}" : "");
                $ins_n = $mysqli->prepare("INSERT INTO notifications (user_id, title, message, type, is_read, created_at) VALUES (?, ?, ?, 'approval_result', 0, NOW())");
                if ($ins_n) {
                    $ins_n->bind_param("iss", $req_id, $notif_title, $notif_msg);
                    $ins_n->execute();
                    $ins_n->close();
                }
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

if (!function_exists('get_unit_label')) {
    /**
     * Map unit category keys to formal labels (matches index.php)
     */
    function get_unit_label($key) {
        $units = [
            'provincial_director'            => 'Provincial Director',
            'additional_provincial_director' => 'Additional Provincial Director',
            'subject_matter_specialist'      => 'Subject Matter Specialist',
            'deputy_director_hq_1'           => 'Deputy Director - H/Q-1',
            'deputy_director_hq_2'           => 'Deputy Director - H/Q-2',
            'deputy_director_district'       => 'Deputy Director - District',
            'range_veterinary_officer'       => 'Range Veterinary Officer',
            'training_centers'               => 'Training Centers',
            'regional_farms'                 => 'Regional Farms',
        ];
        $k = trim((string)$key);
        if ($k === '') return 'Unassigned';
        return $units[$k] ?? ucwords(str_replace('_', ' ', $k));
    }
}

if (!function_exists('check_and_notify_unit_transfer')) {
    /**
     * Check if a record's unit has changed and notify Provincial Director
     * 
     * @param mysqli $mysqli Database connection
     * @param string $record_name Employee or Asset Name
     * @param string|null $old_unit Previous Unit key
     * @param string|null $new_unit Newly submitted Unit key
     * @param string $link Direct link for the notification
     * @return bool True if transfer detected and notification sent
     */
    function check_and_notify_unit_transfer($mysqli, $record_name, $old_unit, $new_unit, $link = '') {
        if (!$mysqli) return false;

        $old = trim((string)$old_unit);
        $new = trim((string)$new_unit);

        // Detect mismatch
        if ($old !== $new && $new !== '') {
            $old_label = get_unit_label($old);
            $new_label = get_unit_label($new);

            $notif_title = 'Transfer Alert';
            $notif_msg = "Transfer Alert: {$record_name} was transferred from {$old_label} to {$new_label}";
            $notif_type = 'transfer_alert';
            $notif_link = !empty($link) ? $link : 'pages/modules/pd/pending_approvals.php';

            // Query active Provincial Directors
            $pd_res = $mysqli->query("SELECT id FROM users WHERE role = 'provincial_director' AND is_active = 1");
            if ($pd_res) {
                $ins_notif = $mysqli->prepare("
                    INSERT INTO notifications (user_id, title, message, type, link, is_read, created_at) 
                    VALUES (?, ?, ?, ?, ?, 0, NOW())
                ");
                if ($ins_notif) {
                    while ($pd_user = $pd_res->fetch_assoc()) {
                        $p_uid = intval($pd_user['id']);
                        $ins_notif->bind_param("issss", $p_uid, $notif_title, $notif_msg, $notif_type, $notif_link);
                        $ins_notif->execute();
                    }
                    $ins_notif->close();
                }
            }
            return true;
        }

        return false;
    }
}

