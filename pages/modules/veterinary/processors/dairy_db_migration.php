<?php
// pages/modules/veterinary/processors/dairy_db_migration.php
// Ensures tables for Dairy Development Project Summary and Detailed Application Forms exist.

if (!function_exists('ensure_dairy_project_tables')) {
    function ensure_dairy_project_tables($mysqli) {
        if (!$mysqli) {
            return false;
        }

        // 1. Table for Dairy Project Beneficiaries Summary
        $sql_summary = "
        CREATE TABLE IF NOT EXISTS `dairy_project_beneficiaries` (
            `id` INT(11) NOT NULL AUTO_INCREMENT,
            `project_name` VARCHAR(255) NOT NULL DEFAULT 'National Dairy Production Programme',
            `project_year` INT(4) NOT NULL DEFAULT 2026,
            `range_id` INT(11) NULL DEFAULT NULL,
            `district_id` INT(11) NULL DEFAULT NULL,
            `farm_reg_no` VARCHAR(100) NOT NULL,
            `beneficiary_name` VARCHAR(255) NOT NULL,
            `address` TEXT NOT NULL,
            `nic` VARCHAR(50) NOT NULL,
            `phone` VARCHAR(50) NOT NULL,
            `daily_milk_current` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
            `daily_milk_expected` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
            `category` VARCHAR(50) NOT NULL DEFAULT '5L',
            `activities` TEXT NULL DEFAULT NULL,
            `total_cost` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
            `beneficiary_contribution` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
            `department_contribution` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
            `approval_status` ENUM('Pending', 'Recommended', 'Approved', 'Rejected') NOT NULL DEFAULT 'Pending',
            `remarks` TEXT NULL DEFAULT NULL,
            `created_by` INT(11) NULL DEFAULT NULL,
            `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            INDEX `idx_farm_reg_no` (`farm_reg_no`),
            INDEX `idx_project_year` (`project_year`),
            INDEX `idx_range_id` (`range_id`),
            INDEX `idx_district_id` (`district_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ";
        $mysqli->query($sql_summary);

        // 2. Table for Detailed Dairy Farm Development Application Form
        $sql_application = "
        CREATE TABLE IF NOT EXISTS `dairy_farm_applications` (
            `id` INT(11) NOT NULL AUTO_INCREMENT,
            `summary_id` INT(11) NULL DEFAULT NULL,
            `farm_reg_no` VARCHAR(100) NOT NULL,
            `ai_no` VARCHAR(100) NULL DEFAULT NULL,
            `survey_date` DATE NULL DEFAULT NULL,
            `main_category` VARCHAR(50) NULL DEFAULT '05 Liters',
            `producer_name` VARCHAR(255) NOT NULL,
            `nic_no` VARCHAR(50) NOT NULL,
            `address` TEXT NOT NULL,
            `daph_register_no` VARCHAR(100) NULL DEFAULT NULL,
            `contact_mobile` VARCHAR(50) NULL DEFAULT NULL,
            `telephone_no` VARCHAR(50) NULL DEFAULT NULL,
            `ds_gn_division` VARCHAR(255) NULL DEFAULT NULL,
            `gender` VARCHAR(20) NULL DEFAULT 'Male',
            `livestock_council` VARCHAR(10) NULL DEFAULT 'No',
            `transport_distance_km` DECIMAL(8,2) NULL DEFAULT 0.00,
            `previous_project_benefit` VARCHAR(10) NULL DEFAULT 'No',
            `previous_project_details` TEXT NULL DEFAULT NULL,
            `water_source` VARCHAR(100) NULL DEFAULT NULL,
            `land_extent` VARCHAR(100) NULL DEFAULT NULL,
            `pasture_extent` VARCHAR(100) NULL DEFAULT NULL,
            `shed_capacity_adults` INT(11) NULL DEFAULT 0,
            `shed_capacity_calves` INT(11) NULL DEFAULT 0,
            `common_grazing_land` VARCHAR(255) NULL DEFAULT NULL,
            `pasture_cultivation_land` VARCHAR(255) NULL DEFAULT NULL,
            `has_grass_chopper` TINYINT(1) NOT NULL DEFAULT 0,
            `has_milk_cans` TINYINT(1) NOT NULL DEFAULT 0,
            `has_milking_machine` TINYINT(1) NOT NULL DEFAULT 0,
            `has_chilling_equip` TINYINT(1) NOT NULL DEFAULT 0,
            `has_biogas_plant` TINYINT(1) NOT NULL DEFAULT 0,
            `biogas_capacity_m3` VARCHAR(50) NULL DEFAULT NULL,
            `id_ear_tag_no` VARCHAR(100) NULL DEFAULT NULL,
            `id_hot_brand_no` VARCHAR(100) NULL DEFAULT NULL,
            `geo_coordinates` VARCHAR(100) NULL DEFAULT NULL,
            `agri_env_ai_cc` VARCHAR(100) NULL DEFAULT NULL,
            `agri_env_ds_division` VARCHAR(100) NULL DEFAULT NULL,
            `agri_env_gn_division` VARCHAR(100) NULL DEFAULT NULL,
            `herd_composition_data` LONGTEXT NULL DEFAULT NULL,
            `feeding_types_data` LONGTEXT NULL DEFAULT NULL,
            `cultivating_grass_own_land` TINYINT(1) NOT NULL DEFAULT 0,
            `own_land_grass_extent` VARCHAR(100) NULL DEFAULT NULL,
            `type_of_grass` VARCHAR(255) NULL DEFAULT NULL,
            `grass_cutting_method` VARCHAR(100) NULL DEFAULT 'Hand',
            `chop_fodder_before_feeding` TINYINT(1) NOT NULL DEFAULT 0,
            `made_silage_before` TINYINT(1) NOT NULL DEFAULT 0,
            `willing_to_make_silage` TINYINT(1) NOT NULL DEFAULT 0,
            `feed_silage` TINYINT(1) NOT NULL DEFAULT 0,
            `buy_agro_byproducts` TINYINT(1) NOT NULL DEFAULT 0,
            `buy_concentrated_feed` TINYINT(1) NOT NULL DEFAULT 0,
            `mix_mineral_mixture` TINYINT(1) NOT NULL DEFAULT 0,
            `milk_morning_liters` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
            `milk_evening_liters` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
            `milk_total_liters` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
            `milk_expected_liters` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
            `milk_hygiene_measures` TEXT NULL DEFAULT NULL,
            `milk_price_per_liter` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
            `sell_to_private_collector` TINYINT(1) NOT NULL DEFAULT 0,
            `sell_to_organized_collector` TINYINT(1) NOT NULL DEFAULT 0,
            `marketing_details` VARCHAR(255) NULL DEFAULT NULL,
            `keep_daily_records` TINYINT(1) NOT NULL DEFAULT 0,
            `training_courses_data` LONGTEXT NULL DEFAULT NULL,
            `waste_pasture` TINYINT(1) NOT NULL DEFAULT 0,
            `waste_biogas` TINYINT(1) NOT NULL DEFAULT 0,
            `waste_composting` TINYINT(1) NOT NULL DEFAULT 0,
            `waste_sold_manure` TINYINT(1) NOT NULL DEFAULT 0,
            `waste_other` VARCHAR(255) NULL DEFAULT NULL,
            `training_clean_milk` TINYINT(1) NOT NULL DEFAULT 0,
            `measure_milk_yield` TINYINT(1) NOT NULL DEFAULT 0,
            `participate_health_camps` TINYINT(1) NOT NULL DEFAULT 0,
            `vaccine_hs` TINYINT(1) NOT NULL DEFAULT 0,
            `vaccine_bq` TINYINT(1) NOT NULL DEFAULT 0,
            `vaccine_fmd` TINYINT(1) NOT NULL DEFAULT 0,
            `created_by` INT(11) NULL DEFAULT NULL,
            `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            INDEX `idx_app_farm_reg_no` (`farm_reg_no`),
            INDEX `idx_app_summary_id` (`summary_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ";
        $mysqli->query($sql_application);

        return true;
    }
}
