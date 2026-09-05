<?php
// pages/modules/district/processors/db_migration.php
// Ensures the user_quick_action_assignments table and related schema exist.

if (!function_exists('ensure_quick_action_assignments_table')) {
    function ensure_quick_action_assignments_table($mysqli) {
        if (!$mysqli) {
            return false;
        }

        $table_sql = "
            CREATE TABLE IF NOT EXISTS `user_quick_action_assignments` (
                `id` INT AUTO_INCREMENT PRIMARY KEY,
                `user_id` INT NOT NULL,
                `action_id` VARCHAR(100) NOT NULL,
                `assigned_by` INT NULL,
                `assigned_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
                UNIQUE KEY `uniq_user_action` (`user_id`, `action_id`),
                KEY `idx_user_id` (`user_id`),
                KEY `idx_action_id` (`action_id`),
                KEY `idx_assigned_by` (`assigned_by`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ";

        return $mysqli->query($table_sql);
    }
}
