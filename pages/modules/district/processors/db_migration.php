<?php
// pages/modules/district/processors/db_migration.php
// Ensures the user_quick_action_assignments table and related schema exist.

if (!function_exists('ensure_quick_action_assignments_table')) {
    function ensure_quick_action_assignments_table($mysqli) {
        if (!$mysqli) {
            return false;
        }

        // Ensure district_id column exists
        $res = $mysqli->query("SHOW COLUMNS FROM `user_quick_action_assignments` LIKE 'district_id'");
        if ($res && $res->num_rows === 0) {
            $mysqli->query("ALTER TABLE `user_quick_action_assignments` ADD COLUMN `district_id` INT NULL DEFAULT NULL AFTER `user_id`");
            $mysqli->query("ALTER TABLE `user_quick_action_assignments` ADD INDEX `idx_district_id` (`district_id`)");
        }

        // Ensure range_id column exists
        $res2 = $mysqli->query("SHOW COLUMNS FROM `user_quick_action_assignments` LIKE 'range_id'");
        if ($res2 && $res2->num_rows === 0) {
            $mysqli->query("ALTER TABLE `user_quick_action_assignments` ADD COLUMN `range_id` INT NULL DEFAULT NULL AFTER `district_id`");
            $mysqli->query("ALTER TABLE `user_quick_action_assignments` ADD INDEX `idx_range_id` (`range_id`)");
        }

        // Allow user_id to be NULL for role-level district assignments
        $mysqli->query("ALTER TABLE `user_quick_action_assignments` MODIFY COLUMN `user_id` INT NULL DEFAULT NULL");

        // Ensure target_role column exists for role-wide delegation (e.g. government_veterinary_surgeon)
        $resRole = $mysqli->query("SHOW COLUMNS FROM `user_quick_action_assignments` LIKE 'target_role'");
        if ($resRole && $resRole->num_rows === 0) {
            $mysqli->query("ALTER TABLE `user_quick_action_assignments` ADD COLUMN `target_role` VARCHAR(100) NULL DEFAULT NULL AFTER `user_id`");
            $mysqli->query("ALTER TABLE `user_quick_action_assignments` ADD INDEX `idx_target_role` (`target_role`)");
        }

        // Drop old unique key if present and add composite unique key (user_id, district_id, action_id)
        $idx_res = $mysqli->query("SHOW INDEX FROM `user_quick_action_assignments` WHERE Key_name = 'uniq_user_action'");
        if ($idx_res && $idx_res->num_rows > 0) {
            $mysqli->query("ALTER TABLE `user_quick_action_assignments` DROP INDEX `uniq_user_action`");
        }
        $dist_idx_res = $mysqli->query("SHOW INDEX FROM `user_quick_action_assignments` WHERE Key_name = 'uniq_user_dist_action'");
        if ($dist_idx_res && $dist_idx_res->num_rows === 0) {
            $mysqli->query("ALTER TABLE `user_quick_action_assignments` ADD UNIQUE KEY `uniq_user_dist_action` (`user_id`, `district_id`, `action_id`)");
        }

        // Add unique key for role-based assignments if not present
        $role_idx_res = $mysqli->query("SHOW INDEX FROM `user_quick_action_assignments` WHERE Key_name = 'uniq_role_dist_action'");
        if ($role_idx_res && $role_idx_res->num_rows === 0) {
            $mysqli->query("ALTER TABLE `user_quick_action_assignments` ADD UNIQUE KEY `uniq_role_dist_action` (`target_role`, `district_id`, `action_id`)");
        }

        // Backfill district_id for existing rows from users
        $mysqli->query("
            UPDATE user_quick_action_assignments uqaa
            JOIN users u ON uqaa.user_id = u.id
            SET uqaa.district_id = u.district_id
            WHERE uqaa.district_id IS NULL AND u.district_id IS NOT NULL
        ");

        return true;
    }
}
