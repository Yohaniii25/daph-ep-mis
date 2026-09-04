<?php
/**
 * includes/notification_helper.php
 * Automated Notification Engine with Dynamic Role Jurisdiction Routing
 */

if (!function_exists('create_officer_notification')) {
    /**
     * Dispatch an automated notification for officer add / remove / transfer events
     *
     * @param mysqli $mysqli Database connection
     * @param string $action 'New Officer Added', 'Officer Removed', 'Officer Transferred', etc.
     * @param string $officer_name Name of the officer
     * @param string $service_number Officer service number or employee ID
     * @param int|null $range_id Veterinary Range ID
     * @param string|null $link Optional navigation link
     * @return array Summary of notifications sent ['success' => bool, 'sent_count' => int, 'recipients' => array]
     */
    function create_officer_notification($mysqli, $action, $officer_name, $service_number = '', $range_id = null, $link = null) {
        if (!$mysqli) {
            return ['success' => false, 'sent_count' => 0, 'error' => 'No database connection'];
        }

        $range_name = 'Unassigned';
        $district_id = null;
        $district_name = 'General';

        // 1. Join range_id with veterinary_ranges and districts to determine jurisdiction
        if (!empty($range_id)) {
            $stmt_range = $mysqli->prepare("
                SELECT 
                    vr.id AS range_id, 
                    vr.name AS range_name, 
                    vr.district_id, 
                    d.name AS district_name 
                FROM veterinary_ranges vr
                LEFT JOIN districts d ON vr.district_id = d.id
                WHERE vr.id = ?
                LIMIT 1
            ");
            if ($stmt_range) {
                $stmt_range->bind_param("i", $range_id);
                $stmt_range->execute();
                $res = $stmt_range->get_result();
                if ($row = $res->fetch_assoc()) {
                    $range_name = $row['range_name'] ?? 'Range #' . $range_id;
                    $district_id = !empty($row['district_id']) ? intval($row['district_id']) : null;
                    $district_name = $row['district_name'] ?? 'District #' . $district_id;
                }
                $stmt_range->close();
            }
        }

        // 2. Build user-friendly notification title and message
        $officer_disp = trim($officer_name);
        $id_disp = !empty($service_number) ? " (Service/Emp No: " . trim($service_number) . ")" : "";
        $range_text = !empty($range_name) && $range_name !== 'Unassigned' 
            ? "{$range_name} Range" . (!empty($district_name) && $district_name !== 'General' ? " ({$district_name} District)" : "")
            : "the departmental registry";

        $action_lower = strtolower($action);
        if (strpos($action_lower, 'add') !== false || strpos($action_lower, 'new') !== false || strpos($action_lower, 'creat') !== false) {
            $title = 'New Officer Added';
            $message = "Officer {$officer_disp}{$id_disp} has been added and assigned to {$range_text}.";
        } elseif (strpos($action_lower, 'remov') !== false || strpos($action_lower, 'delet') !== false || strpos($action_lower, 'deactivat') !== false) {
            $title = 'Officer Removed';
            $message = "Officer {$officer_disp}{$id_disp} has been removed/deactivated from {$range_text}.";
        } elseif (strpos($action_lower, 'transfer') !== false) {
            $title = 'Officer Transferred';
            $message = "Officer {$officer_disp}{$id_disp} has been transferred to {$range_text}.";
        } else {
            $title = $action;
            $message = "Officer record updated: {$officer_disp}{$id_disp} - {$range_text}.";
        }

        if (empty($link)) {
            $link = 'pages/modules/hr/employee_managment.php';
        }

        // 3. Identify recipient users based on jurisdiction
        $recipient_user_ids = [];

        // Global oversight: Provincial Director & Deputy Director Headquarters
        $global_query = "
            SELECT id, role, full_name, district 
            FROM users 
            WHERE role IN ('provincial_director', 'deputy_director_hq_1', 'deputy_director_hq_2') 
              AND is_active = 1
        ";
        $global_res = $mysqli->query($global_query);
        if ($global_res) {
            while ($u = $global_res->fetch_assoc()) {
                $recipient_user_ids[intval($u['id'])] = $u['role'];
            }
        }

        // Restricted oversight: District Deputy Director for the officer's district
        if (!empty($district_id) || (!empty($district_name) && $district_name !== 'General')) {
            $dist_match_name = $district_name;
            // Handle district name spelling variants (Ampara vs Amparai)
            $dist_alt_name = (strcasecmp($district_name, 'Ampara') === 0) ? 'Amparai' : ((strcasecmp($district_name, 'Amparai') === 0) ? 'Ampara' : $district_name);

            $stmt_ddd = $mysqli->prepare("
                SELECT id, role, full_name, district, district_id 
                FROM users 
                WHERE role = 'district_dd' 
                  AND is_active = 1
                  AND (district_id = ? OR district = ? OR district = ?)
            ");
            if ($stmt_ddd) {
                $stmt_ddd->bind_param("iss", $district_id, $dist_match_name, $dist_alt_name);
                $stmt_ddd->execute();
                $ddd_res = $stmt_ddd->get_result();
                while ($u = $ddd_res->fetch_assoc()) {
                    $recipient_user_ids[intval($u['id'])] = $u['role'];
                }
                $stmt_ddd->close();
            }
        }

        // 4. Insert notification for each recipient
        $sent_count = 0;
        $insert_stmt = $mysqli->prepare("
            INSERT INTO notifications (user_id, title, message, type, link, is_read, created_at) 
            VALUES (?, ?, ?, 'officer_change', ?, 0, NOW())
        ");

        if ($insert_stmt) {
            foreach (array_keys($recipient_user_ids) as $uid) {
                $insert_stmt->bind_param("isss", $uid, $title, $message, $link);
                if ($insert_stmt->execute()) {
                    $sent_count++;
                }
            }
            $insert_stmt->close();
        }

        return [
            'success' => true,
            'title' => $title,
            'message' => $message,
            'sent_count' => $sent_count,
            'recipients' => $recipient_user_ids
        ];
    }
}

if (!function_exists('get_user_notifications')) {
    /**
     * Retrieve recent notifications for a user
     */
    function get_user_notifications($mysqli, $user_id, $limit = 8) {
        $notifications = [];
        if (!$mysqli || empty($user_id)) {
            return $notifications;
        }

        $stmt = $mysqli->prepare("
            SELECT id, user_id, title, message, type, link, is_read, created_at 
            FROM notifications 
            WHERE user_id = ? 
            ORDER BY created_at DESC, id DESC 
            LIMIT ?
        ");
        if ($stmt) {
            $stmt->bind_param("ii", $user_id, $limit);
            $stmt->execute();
            $res = $stmt->get_result();
            while ($row = $res->fetch_assoc()) {
                $row['time_ago'] = format_time_ago($row['created_at']);
                $notifications[] = $row;
            }
            $stmt->close();
        }
        return $notifications;
    }
}

if (!function_exists('get_unread_notification_count')) {
    /**
     * Get unread notification count for a user
     */
    function get_unread_notification_count($mysqli, $user_id) {
        if (!$mysqli || empty($user_id)) {
            return 0;
        }
        $stmt = $mysqli->prepare("
            SELECT COUNT(*) AS unread_cnt 
            FROM notifications 
            WHERE user_id = ? AND is_read = 0
        ");
        if ($stmt) {
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $res = $stmt->get_result();
            $row = $res->fetch_assoc();
            $stmt->close();
            return intval($row['unread_cnt'] ?? 0);
        }
        return 0;
    }
}

if (!function_exists('mark_notification_as_read')) {
    /**
     * Mark a single notification or all notifications as read for a user
     */
    function mark_notification_as_read($mysqli, $user_id, $notification_id = null) {
        if (!$mysqli || empty($user_id)) {
            return false;
        }

        if (!empty($notification_id)) {
            $stmt = $mysqli->prepare("
                UPDATE notifications 
                SET is_read = 1 
                WHERE id = ? AND user_id = ?
            ");
            if ($stmt) {
                $stmt->bind_param("ii", $notification_id, $user_id);
                $ok = $stmt->execute();
                $stmt->close();
                return $ok;
            }
        } else {
            $stmt = $mysqli->prepare("
                UPDATE notifications 
                SET is_read = 1 
                WHERE user_id = ?
            ");
            if ($stmt) {
                $stmt->bind_param("i", $user_id);
                $ok = $stmt->execute();
                $stmt->close();
                return $ok;
            }
        }
        return false;
    }
}

if (!function_exists('format_time_ago')) {
    /**
     * Relative time string format helper
     */
    function format_time_ago($datetime) {
        $time = strtotime($datetime);
        if (!$time) return 'Recently';

        $diff = time() - $time;
        if ($diff < 60) {
            return 'Just now';
        } elseif ($diff < 3600) {
            $mins = max(1, floor($diff / 60));
            return $mins . ' min' . ($mins > 1 ? 's' : '') . ' ago';
        } elseif ($diff < 86400) {
            $hrs = floor($diff / 3600);
            return $hrs . ' hour' . ($hrs > 1 ? 's' : '') . ' ago';
        } elseif ($diff < 604800) {
            $days = floor($diff / 86400);
            return $days . ' day' . ($days > 1 ? 's' : '') . ' ago';
        } else {
            return date('M j, Y', $time);
        }
    }
}

if (!function_exists('notify_assigned_officer')) {
    /**
     * Dispatch an automated direct assignment notification to an officer
     * Dynamic message format: "You are assigned as the [Insert Position/Role Name]"
     * Triggers via in-app notification bell and email if SMTP/mail is configured.
     *
     * @param mysqli $mysqli Database connection
     * @param int $user_id Recipient officer user ID
     * @param string $position_or_role Position or role assigned
     * @param string|null $link Optional portal link (defaults to dashboard.php)
     * @return array Result status ['success' => bool, 'notification_id' => int, 'message' => string, 'email_sent' => bool]
     */
    function notify_assigned_officer($mysqli, $user_id, $position_or_role, $link = 'dashboard.php') {
        if (!$mysqli || empty($user_id)) {
            return ['success' => false, 'error' => 'Invalid database connection or user ID'];
        }

        $clean_title = trim($position_or_role);
        // Map common raw enum role keys to human-friendly titles if raw role passed
        $role_map = [
            'sms' => 'Subject Matter Specialist',
            'deputy_director_hq_1' => 'Deputy Director H/Q (1)',
            'deputy_director_hq_2' => 'Deputy Director H/Q (2)',
            'district_dd' => 'District Deputy Director',
            'veterinary_surgeon' => 'Veterinary Surgeon',
            'government_veterinary_surgeon' => 'Government Veterinary Surgeon',
            'additional_veterinary_surgeon' => 'Additional Veterinary Surgeon',
            'provincial_director' => 'Provincial Director',
            'livestock_development_officer' => 'Livestock Development Officer',
            'development_officer' => 'Development Officer',
            'driver' => 'Driver',
            'dispensary_assistant' => 'Dispensary Assistant',
            'department_laborer' => 'Department Laborer',
            'night_watcher' => 'Night Watcher',
            'farms_dd' => 'Deputy Director (Farms)',
            'training_officer' => 'Training Officer',
            'planning_officer' => 'Planning Officer',
            'finance_admin' => 'Finance Administrator',
            'administrator' => 'Administrator'
        ];

        if (isset($role_map[$clean_title])) {
            $clean_title = $role_map[$clean_title];
        }

        // Exact message format requested by user:
        // "You are assigned as the [Insert Position/Role Name]"
        $notification_message = "You are assigned as the " . $clean_title;
        $title = "Role Assignment";
        $type = "role_assignment";

        // 1. In-app notification bell record
        $stmt = $mysqli->prepare("
            INSERT INTO notifications (user_id, title, message, type, link, is_read, created_at) 
            VALUES (?, ?, ?, ?, ?, 0, NOW())
        ");

        $inserted_id = 0;
        if ($stmt) {
            $stmt->bind_param("issss", $user_id, $title, $notification_message, $type, $link);
            $stmt->execute();
            $inserted_id = $stmt->insert_id;
            $stmt->close();
        }

        // 2. Email dispatch if email exists and mail/SMTP is configured
        $email_sent = false;
        $stmt_user = $mysqli->prepare("SELECT email, full_name, username FROM users WHERE id = ? LIMIT 1");
        if ($stmt_user) {
            $stmt_user->bind_param("i", $user_id);
            $stmt_user->execute();
            $user_data = $stmt_user->get_result()->fetch_assoc();
            $stmt_user->close();

            if (!empty($user_data['email'])) {
                $recipient_email = $user_data['email'];
                $recipient_name = !empty($user_data['full_name']) ? $user_data['full_name'] : $user_data['username'];
                $email_subject = "Official Notification: " . $notification_message . " - DAPH Eastern Province";
                
                $email_body = "Dear " . $recipient_name . ",\r\n\r\n";
                $email_body .= $notification_message . ".\r\n\r\n";
                $email_body .= "Please log in to the Department of Animal Production and Health (Eastern Province) portal to view your updated dashboard and assigned tasks.\r\n\r\n";
                $email_body .= "Department of Animal Production & Health\r\nEastern Province, Sri Lanka\r\n";

                $headers = "From: no-reply@daph.ep.gov.lk\r\n" .
                           "Reply-To: info@daph.ep.gov.lk\r\n" .
                           "X-Mailer: PHP/" . phpversion();

                // Safe mail dispatch (wrapped with @ so it won't throw warning if local mail server is not active)
                $email_sent = @mail($recipient_email, $email_subject, $email_body, $headers);
            }
        }

        return [
            'success' => true,
            'notification_id' => $inserted_id,
            'message' => $notification_message,
            'email_sent' => $email_sent
        ];
    }
}

