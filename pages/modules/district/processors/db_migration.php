<?php
// pages/modules/district/processors/db_migration.php
// Ensures the user_quick_action_assignments and range_module_topics tables exist,
// and provides dynamic topic fetching helpers.

if (!function_exists('ensure_quick_action_assignments_table')) {
    function ensure_quick_action_assignments_table($mysqli) {
        if (!$mysqli) {
            return false;
        }

        // 1. Ensure district_id column exists in user_quick_action_assignments
        $res = $mysqli->query("SHOW COLUMNS FROM `user_quick_action_assignments` LIKE 'district_id'");
        if ($res && $res->num_rows === 0) {
            $mysqli->query("ALTER TABLE `user_quick_action_assignments` ADD COLUMN `district_id` INT NULL DEFAULT NULL AFTER `user_id`");
            $mysqli->query("ALTER TABLE `user_quick_action_assignments` ADD INDEX `idx_district_id` (`district_id`)");
        }

        // 2. Ensure range_id column exists in user_quick_action_assignments
        $res2 = $mysqli->query("SHOW COLUMNS FROM `user_quick_action_assignments` LIKE 'range_id'");
        if ($res2 && $res2->num_rows === 0) {
            $mysqli->query("ALTER TABLE `user_quick_action_assignments` ADD COLUMN `range_id` INT NULL DEFAULT NULL AFTER `district_id`");
            $mysqli->query("ALTER TABLE `user_quick_action_assignments` ADD INDEX `idx_range_id` (`range_id`)");
        }

        // 3. Allow user_id to be NULL for range-level and role-level assignments
        $mysqli->query("ALTER TABLE `user_quick_action_assignments` MODIFY COLUMN `user_id` INT NULL DEFAULT NULL");

        // 4. Ensure target_role column exists for role-wide delegation
        $resRole = $mysqli->query("SHOW COLUMNS FROM `user_quick_action_assignments` LIKE 'target_role'");
        if ($resRole && $resRole->num_rows === 0) {
            $mysqli->query("ALTER TABLE `user_quick_action_assignments` ADD COLUMN `target_role` VARCHAR(100) NULL DEFAULT NULL AFTER `user_id`");
            $mysqli->query("ALTER TABLE `user_quick_action_assignments` ADD INDEX `idx_target_role` (`target_role`)");
        }

        // 5. Drop old unique key if present and add composite unique keys
        $idx_res = $mysqli->query("SHOW INDEX FROM `user_quick_action_assignments` WHERE Key_name = 'uniq_user_action'");
        if ($idx_res && $idx_res->num_rows > 0) {
            $mysqli->query("ALTER TABLE `user_quick_action_assignments` DROP INDEX `uniq_user_action`");
        }
        $dist_idx_res = $mysqli->query("SHOW INDEX FROM `user_quick_action_assignments` WHERE Key_name = 'uniq_user_dist_action'");
        if ($dist_idx_res && $dist_idx_res->num_rows === 0) {
            $mysqli->query("ALTER TABLE `user_quick_action_assignments` ADD UNIQUE KEY `uniq_user_dist_action` (`user_id`, `district_id`, `action_id`)");
        }

        $role_idx_res = $mysqli->query("SHOW INDEX FROM `user_quick_action_assignments` WHERE Key_name = 'uniq_role_dist_action'");
        if ($role_idx_res && $role_idx_res->num_rows === 0) {
            $mysqli->query("ALTER TABLE `user_quick_action_assignments` ADD UNIQUE KEY `uniq_role_dist_action` (`target_role`, `district_id`, `action_id`)");
        }

        $range_idx_res = $mysqli->query("SHOW INDEX FROM `user_quick_action_assignments` WHERE Key_name = 'uniq_range_action'");
        if ($range_idx_res && $range_idx_res->num_rows === 0) {
            $mysqli->query("ALTER TABLE `user_quick_action_assignments` ADD UNIQUE KEY `uniq_range_action` (`range_id`, `action_id`)");
        }

        // Backfill district_id for existing rows from users
        $mysqli->query("
            UPDATE user_quick_action_assignments uqaa
            JOIN users u ON uqaa.user_id = u.id
            SET uqaa.district_id = u.district_id
            WHERE uqaa.district_id IS NULL AND u.district_id IS NOT NULL
        ");

        // 6. Ensure range_module_topics master catalog table exists
        $create_topics_sql = "
            CREATE TABLE IF NOT EXISTS `range_module_topics` (
                `id` INT AUTO_INCREMENT PRIMARY KEY,
                `module_key` VARCHAR(50) NOT NULL UNIQUE,
                `title` VARCHAR(100) NOT NULL,
                `icon` VARCHAR(50) NOT NULL,
                `color` VARCHAR(20) NOT NULL,
                `link_path` VARCHAR(255) NOT NULL,
                `category` VARCHAR(50) NOT NULL DEFAULT 'operations',
                `category_label` VARCHAR(100) NOT NULL DEFAULT 'Field Operations',
                `description` VARCHAR(255) NULL,
                `sort_order` INT NOT NULL DEFAULT 0,
                `is_active` TINYINT(1) NOT NULL DEFAULT 1,
                `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ";
        $mysqli->query($create_topics_sql);

        // 7. Seed standard module catalog into range_module_topics
        $seed_topics = [
            [
                'module_key'     => 'range_statistics',
                'title'          => 'Range Statistics',
                'icon'           => 'bi-graph-up',
                'color'          => '#820100',
                'link_path'      => 'range_statistics.php',
                'category'       => 'reports',
                'category_label' => 'Reports & Data',
                'description'    => 'Statistical returns & population summaries',
                'sort_order'     => 1
            ],
            [
                'module_key'     => 'annual_targets',
                'title'          => 'Annual Targets',
                'icon'           => 'bi-bar-chart',
                'color'          => '#370709',
                'link_path'      => 'annual_targets.php',
                'category'       => 'reports',
                'category_label' => 'Reports & Data',
                'description'    => 'Annual operational targets & performance',
                'sort_order'     => 2
            ],
            [
                'module_key'     => 'monthly_annual_reports',
                'title'          => 'Monthly/Annual Reports',
                'icon'           => 'bi-car-front-fill',
                'color'          => '#b08723',
                'link_path'      => 'monthly-annual-reports.php',
                'category'       => 'reports',
                'category_label' => 'Reports & Data',
                'description'    => 'Periodic reporting submissions',
                'sort_order'     => 3
            ],
            [
                'module_key'     => 'regulatory_functions',
                'title'          => 'Regulatory Functions',
                'icon'           => 'bi-file-earmark-plus',
                'color'          => '#a07174',
                'link_path'      => 'regulatory_functions.php',
                'category'       => 'clinical',
                'category_label' => 'Clinical & Legal',
                'description'    => 'Animals Act compounding & legal functions',
                'sort_order'     => 4
            ],
            [
                'module_key'     => 'animal_health',
                'title'          => 'Animal Health',
                'icon'           => 'bi-gear-fill',
                'color'          => '#689ccf',
                'link_path'      => 'animal_health.php',
                'category'       => 'clinical',
                'category_label' => 'Clinical Care',
                'description'    => 'Disease surveillance & clinical logs',
                'sort_order'     => 5
            ],
            [
                'module_key'     => 'clinical_services',
                'title'          => 'Clinical Services',
                'icon'           => 'bi-clipboard2-pulse',
                'color'          => '#2e7d32',
                'link_path'      => 'clinical_services.php',
                'category'       => 'clinical',
                'category_label' => 'Clinical Care',
                'description'    => 'Outpatient veterinary clinical care',
                'sort_order'     => 6
            ],
            [
                'module_key'     => 'citizen_charter',
                'title'          => 'Services Register (Citizen Charter)',
                'icon'           => 'bi-file-earmark-person-fill',
                'color'          => '#b45309',
                'link_path'      => 'clinical_services.php?tab=services_register',
                'category'       => 'clinical',
                'category_label' => 'Citizen Services',
                'description'    => 'Citizen charter service delivery logs',
                'sort_order'     => 7
            ],
            [
                'module_key'     => 'animal_breeding',
                'title'          => 'Breeding and Production',
                'icon'           => 'bi-file-earmark-text-fill',
                'color'          => '#e65100',
                'link_path'      => 'animal_breeding.php',
                'category'       => 'clinical',
                'category_label' => 'Breeding & AI',
                'description'    => 'Artificial insemination & pedigree records',
                'sort_order'     => 8
            ],
            [
                'module_key'     => 'dairy_hub',
                'title'          => 'Dairy Hub',
                'icon'           => 'bi-patch-check-fill',
                'color'          => '#1565c0',
                'link_path'      => 'dairy_hub.php',
                'category'       => 'operations',
                'category_label' => 'Dairy & Hubs',
                'description'    => 'Milk collection & dairy farmer hubs',
                'sort_order'     => 9
            ],
            [
                'module_key'     => 'projects_monitoring',
                'title'          => 'Projects and Monitoring',
                'icon'           => 'bi-kanban-fill',
                'color'          => '#00838f',
                'link_path'      => 'projects_progress.php',
                'category'       => 'operations',
                'category_label' => 'Special Projects',
                'description'    => 'Field development project tracking & site visits',
                'sort_order'     => 10
            ],
            [
                'module_key'     => 'accounts',
                'title'          => 'Accounts',
                'icon'           => 'bi-bookmark-dash-fill',
                'color'          => '#ad1457',
                'link_path'      => 'accounts.php',
                'category'       => 'reports',
                'category_label' => 'Finance & Cash',
                'description'    => 'Revenue, cash books & expenditures',
                'sort_order'     => 11
            ],
            [
                'module_key'     => 'clean_sri_lanka',
                'title'          => 'Clean Sri Lanka',
                'icon'           => 'bi-graph-up-arrow',
                'color'          => '#d84315',
                'link_path'      => 'clean_sri_lanka.php',
                'category'       => 'operations',
                'category_label' => 'National Program',
                'description'    => 'Clean Sri Lanka national initiative',
                'sort_order'     => 12
            ],
            [
                'module_key'     => 'trainings',
                'title'          => 'Trainings',
                'icon'           => 'bi-sliders',
                'color'          => '#37474f',
                'link_path'      => 'training.php',
                'category'       => 'operations',
                'category_label' => 'Capacity Building',
                'description'    => 'Farmer workshops & staff capacity sessions',
                'sort_order'     => 13
            ]
        ];

        $ins_topic = $mysqli->prepare("
            INSERT INTO `range_module_topics` (`module_key`, `title`, `icon`, `color`, `link_path`, `category`, `category_label`, `description`, `sort_order`, `is_active`)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 1)
            ON DUPLICATE KEY UPDATE 
                `title` = VALUES(`title`),
                `icon` = VALUES(`icon`),
                `color` = VALUES(`color`),
                `link_path` = VALUES(`link_path`),
                `category` = VALUES(`category`),
                `category_label` = VALUES(`category_label`),
                `description` = VALUES(`description`),
                `sort_order` = VALUES(`sort_order`)
        ");
        if ($ins_topic) {
            foreach ($seed_topics as $st) {
                $ins_topic->bind_param(
                    "ssssssssi",
                    $st['module_key'],
                    $st['title'],
                    $st['icon'],
                    $st['color'],
                    $st['link_path'],
                    $st['category'],
                    $st['category_label'],
                    $st['description'],
                    $st['sort_order']
                );
                $ins_topic->execute();
            }
            $ins_topic->close();
        }

        return true;
    }
}

/**
 * Fetch all available master module topics from database.
 * Returns associative array keyed by module_key.
 */
if (!function_exists('get_all_range_module_topics')) {
    function get_all_range_module_topics($mysqli) {
        $topics = [];
        $res = $mysqli->query("SELECT * FROM `range_module_topics` WHERE is_active = 1 ORDER BY sort_order ASC, id ASC");
        if ($res) {
            while ($row = $res->fetch_assoc()) {
                $topics[$row['module_key']] = $row;
            }
        }
        return $topics;
    }
}

/**
 * Get active module keys explicitly assigned to a range or its assigned officers.
 * Precedence hierarchy:
 * 1. Specific range_id assignment rows in user_quick_action_assignments
 * 2. If no range_id specific rows exist: District-wide role assignments for Government Veterinary Surgeons
 * 3. Union with individual officer assignments (if non-supervisory user_id provided)
 */
if (!function_exists('get_assigned_keys_for_range')) {
    function get_assigned_keys_for_range($mysqli, $range_id, $district_id = null, $user_id = null) {
        $assigned_keys = [];

        // Resolve district_id if missing
        if (empty($district_id) && !empty($range_id)) {
            $stmt_d = $mysqli->prepare("SELECT district_id FROM veterinary_ranges WHERE id = ?");
            if ($stmt_d) {
                $stmt_d->bind_param("i", $range_id);
                $stmt_d->execute();
                $r = $stmt_d->get_result()->fetch_assoc();
                if ($r && !empty($r['district_id'])) {
                    $district_id = (int)$r['district_id'];
                }
                $stmt_d->close();
            }
        }

        // 1. Check if specific range_id assignments exist
        $range_has_specific = false;
        if (!empty($range_id)) {
            $stmt_rng = $mysqli->prepare("SELECT action_id FROM user_quick_action_assignments WHERE range_id = ?");
            if ($stmt_rng) {
                $stmt_rng->bind_param("i", $range_id);
                $stmt_rng->execute();
                $res_rng = $stmt_rng->get_result();
                if ($res_rng && $res_rng->num_rows > 0) {
                    $range_has_specific = true;
                    while ($r = $res_rng->fetch_assoc()) {
                        $assigned_keys[] = $r['action_id'];
                    }
                }
                $stmt_rng->close();
            }
        }

        // 2. If no specific range assignments, fallback to district-wide assignments
        if (!$range_has_specific && !empty($district_id)) {
            $stmt_dw = $mysqli->prepare("
                SELECT DISTINCT action_id 
                FROM user_quick_action_assignments 
                WHERE range_id IS NULL 
                  AND district_id = ? 
                  AND target_role IN ('government_veterinary_surgeon', 'veterinary_surgeon')
            ");
            if ($stmt_dw) {
                $stmt_dw->bind_param("i", $district_id);
                $stmt_dw->execute();
                $res_dw = $stmt_dw->get_result();
                while ($r = $res_dw->fetch_assoc()) {
                    $assigned_keys[] = $r['action_id'];
                }
                $stmt_dw->close();
            }
        }

        // 3. If a specific user_id is provided, also check direct user-level assignments
        if (!empty($user_id)) {
            $stmt_u = $mysqli->prepare("SELECT DISTINCT action_id FROM user_quick_action_assignments WHERE user_id = ?");
            if ($stmt_u) {
                $stmt_u->bind_param("i", $user_id);
                $stmt_u->execute();
                $res_u = $stmt_u->get_result();
                while ($r = $res_u->fetch_assoc()) {
                    $assigned_keys[] = $r['action_id'];
                }
                $stmt_u->close();
            }
        }

        // Map key aliases (e.g. 'projects' / 'monitoring' to 'projects_monitoring')
        $expanded_keys = [];
        foreach (array_unique($assigned_keys) as $k) {
            $expanded_keys[] = $k;
            if ($k === 'projects' || $k === 'monitoring') {
                $expanded_keys[] = 'projects_monitoring';
            }
            if ($k === 'projects_monitoring') {
                $expanded_keys[] = 'projects';
                $expanded_keys[] = 'monitoring';
            }
        }

        return array_values(array_unique($expanded_keys));
    }
}

/**
 * Fetch and construct visible module objects for a range from the database.
 * Formats dynamic links with range_id query string parameter.
 */
if (!function_exists('get_assigned_module_topics_for_range')) {
    function get_assigned_module_topics_for_range($mysqli, $range_id, $district_id = null, $user_id = null) {
        $assigned_keys = get_assigned_keys_for_range($mysqli, $range_id, $district_id, $user_id);
        if (empty($assigned_keys)) {
            return [];
        }

        $all_topics = get_all_range_module_topics($mysqli);
        $visible_actions = [];

        foreach ($all_topics as $key => $topic) {
            // Check if topic key or alias is assigned
            if (in_array($key, $assigned_keys, true)) {
                $link = $topic['link_path'];
                if (!empty($range_id)) {
                    $separator = (strpos($link, '?') !== false) ? '&' : '?';
                    $link .= $separator . 'range_id=' . (int)$range_id;
                }

                $visible_actions[$key] = [
                    'id'          => (int)$topic['id'],
                    'module_key'  => $topic['module_key'],
                    'title'       => $topic['title'],
                    'icon'        => $topic['icon'],
                    'color'       => $topic['color'],
                    'link'        => $link,
                    'category'    => $topic['category'],
                    'category_label' => $topic['category_label'],
                    'description' => $topic['description'],
                    'sort_order'  => (int)$topic['sort_order']
                ];
            }
        }

        return $visible_actions;
    }
}
