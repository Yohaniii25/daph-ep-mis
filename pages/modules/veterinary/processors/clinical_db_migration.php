<?php
/**
 * pages/modules/veterinary/processors/clinical_db_migration.php
 * Database migration for Clinical Services OPD Register module.
 */

if (!function_exists('ensure_clinical_opd_tables')) {
    /**
     * Ensure table clinical_opd_records and associated indices exist.
     *
     * @param mysqli $mysqli
     * @return bool
     */
    function ensure_clinical_opd_tables($mysqli) {
        if (!$mysqli) {
            return false;
        }

        // 1. Create clinical_opd_records table
        $sql = "
        CREATE TABLE IF NOT EXISTS `clinical_opd_records` (
            `id` INT(11) NOT NULL AUTO_INCREMENT,
            `clinic_type` ENUM('large_animal', 'small_animal') NOT NULL DEFAULT 'large_animal',
            `entry_date` DATE NOT NULL,
            `range_id` INT(11) NULL,
            `district_id` INT(11) NULL,
            
            -- Intake Registration Identifiers
            `registration_no` VARCHAR(100) NOT NULL COMMENT 'Farm Reg No or Small Animal Reg/Case No',
            `farm_reg_no` VARCHAR(100) NULL COMMENT 'Farm Reg No if large animal',
            `owner_name` VARCHAR(255) NOT NULL,
            `owner_address` TEXT NULL,
            `owner_phone` VARCHAR(50) NULL,
            `owner_nic` VARCHAR(20) NULL,
            
            -- Clinical Data Grid physical register columns
            `herd_total_population` INT(11) DEFAULT 1 COMMENT 'Total Population of the herd',
            `species` VARCHAR(100) NOT NULL COMMENT 'Species e.g. Cattle, Buffalo, Goat, Canine, Feline',
            `breed` VARCHAR(100) NULL COMMENT 'Breed',
            `age_years` VARCHAR(50) NULL COMMENT 'Age in Years',
            `sex` ENUM('Male', 'Female', 'Unknown') DEFAULT 'Female' COMMENT 'Sex (M/F)',
            `coat_colour` VARCHAR(100) NULL COMMENT 'Coat Colour',
            `special_marks` TEXT NULL COMMENT 'Special Marks',
            `clinically_affected_count` INT(11) DEFAULT 1 COMMENT 'No of Animals Clinically affected',
            `death_count` INT(11) DEFAULT 0 COMMENT 'No of deaths if any',
            `tentative_diagnosis` TEXT NOT NULL COMMENT 'Tentative Diagnosis',
            `action_taken` TEXT NOT NULL COMMENT 'Action Taken / Treatments',
            
            -- Revenue Collection & Counter File Linkage
            `revenue_consultation` DECIMAL(12,2) DEFAULT 0.00 COMMENT 'Consultation fee (Rs.)',
            `revenue_drugs` DECIMAL(12,2) DEFAULT 0.00 COMMENT 'Drugs fee (Rs.)',
            `revenue_others` DECIMAL(12,2) DEFAULT 0.00 COMMENT 'Others fee (Rs.)',
            `revenue_total` DECIMAL(12,2) DEFAULT 0.00 COMMENT 'Total Revenue (Rs.)',
            `cash_receipt_no` VARCHAR(100) NOT NULL COMMENT 'Mandatory Cash Receipt No.',
            `leaf_issue_id` INT(11) NULL COMMENT 'Foreign key link to counterfoil_leaf_issues.id',
            
            -- General Remarks
            `remarks` TEXT NULL COMMENT 'General Remarks',
            
            `created_by` INT(11) NULL,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            INDEX `idx_clinic_type` (`clinic_type`),
            INDEX `idx_entry_date` (`entry_date`),
            INDEX `idx_range_id` (`range_id`),
            INDEX `idx_district_id` (`district_id`),
            INDEX `idx_cash_receipt_no` (`cash_receipt_no`),
            INDEX `idx_registration_no` (`registration_no`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ";

        if (!$mysqli->query($sql)) {
            error_log("Failed creating clinical_opd_records: " . $mysqli->error);
            return false;
        }

        // 2. Ensure OPD Register & Cash Receipt Book counterfoil types exist in master_counterfoil_types
        $mysqli->query("INSERT IGNORE INTO master_counterfoil_types (id, type_name, is_active) VALUES (4, 'Cash Receipt Book', 1), (20, 'OPD Register', 1)");

        return true;
    }
}
