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

        // 3. Table for Citizen Charter Non-Clinical Requests Register
        $sql_charter = "
        CREATE TABLE IF NOT EXISTS `citizen_charter_requests` (
            `id` INT(11) NOT NULL AUTO_INCREMENT,
            `range_id` INT(11) NULL DEFAULT NULL,
            `district_id` INT(11) NULL DEFAULT NULL,
            `request_date` DATE NOT NULL,
            `reference_no` VARCHAR(100) NOT NULL,
            `request_origin` ENUM('Direct Visit', 'By Call') NOT NULL DEFAULT 'Direct Visit',
            `beneficiary_name` VARCHAR(255) NOT NULL,
            `nic_no` VARCHAR(50) NULL DEFAULT NULL,
            `phone_no` VARCHAR(50) NULL DEFAULT NULL,
            `address` TEXT NULL DEFAULT NULL,
            `farm_reg_no` VARCHAR(100) NULL DEFAULT NULL,
            `purpose_of_visit` TEXT NOT NULL,
            `immediate_action_taken` TEXT NOT NULL,
            `final_remarks` TEXT NULL DEFAULT NULL,
            `status` ENUM('Completed', 'Pending', 'In Progress', 'Referred') NOT NULL DEFAULT 'Completed',
            `created_by` INT(11) NULL DEFAULT NULL,
            `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            INDEX `idx_range_date` (`range_id`, `request_date`),
            INDEX `idx_request_origin` (`request_origin`),
            INDEX `idx_nic` (`nic_no`),
            INDEX `idx_farm_reg_no` (`farm_reg_no`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ";
        $mysqli->query($sql_charter);

        // 4. Table for Dairy Hub Societies & Governance Code of Conduct
        $sql_societies = "
        CREATE TABLE IF NOT EXISTS `dairy_hub_societies` (
            `id` INT(11) NOT NULL AUTO_INCREMENT,
            `range_id` INT(11) NOT NULL,
            `district_id` INT(11) NULL DEFAULT NULL,
            `society_name` VARCHAR(255) NOT NULL,
            `reg_no` VARCHAR(100) NULL DEFAULT NULL,
            `established_date` DATE NULL DEFAULT NULL,
            `code_of_conduct_title` VARCHAR(255) NOT NULL DEFAULT 'Dairy Hub Society Official Code of Conduct & Operational Ethics',
            `code_of_conduct_text` LONGTEXT NULL DEFAULT NULL,
            `code_of_conduct_pdf` VARCHAR(255) NULL DEFAULT NULL,
            `status` ENUM('Active', 'Pending Registration', 'Dormant') NOT NULL DEFAULT 'Active',
            `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            UNIQUE KEY `idx_range_society` (`range_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ";
        $mysqli->query($sql_societies);

        // 5. Table for Enrolled Dairy Hub Society Members & Executive Positions
        $sql_members = "
        CREATE TABLE IF NOT EXISTS `dairy_hub_society_members` (
            `id` INT(11) NOT NULL AUTO_INCREMENT,
            `society_id` INT(11) NULL DEFAULT NULL,
            `range_id` INT(11) NOT NULL,
            `district_id` INT(11) NULL DEFAULT NULL,
            `membership_no` VARCHAR(100) NOT NULL,
            `farm_reg_no` VARCHAR(100) NULL DEFAULT NULL,
            `member_name` VARCHAR(255) NOT NULL,
            `nic` VARCHAR(50) NOT NULL,
            `phone` VARCHAR(50) NULL DEFAULT NULL,
            `address` TEXT NULL DEFAULT NULL,
            `ds_gn_division` VARCHAR(255) NULL DEFAULT NULL,
            `enrollment_date` DATE NOT NULL,
            `enrollment_source` VARCHAR(100) NOT NULL DEFAULT 'Dairy Hub Application',
            `executive_position` VARCHAR(100) NOT NULL DEFAULT 'Member',
            `is_executive` TINYINT(1) NOT NULL DEFAULT 0,
            `status` ENUM('Active', 'Inactive', 'Suspended') NOT NULL DEFAULT 'Active',
            `code_of_conduct_agreed` TINYINT(1) NOT NULL DEFAULT 1,
            `created_by` INT(11) NULL DEFAULT NULL,
            `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            UNIQUE KEY `idx_unique_range_nic` (`range_id`, `nic`),
            INDEX `idx_membership_no` (`membership_no`),
            INDEX `idx_farm_reg_no` (`farm_reg_no`),
            INDEX `idx_position` (`executive_position`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ";
        $mysqli->query($sql_members);

        // 6. Ensure default Code of Conduct text is established for active societies
        $default_code_of_conduct = "### DEPARTMENT OF ANIMAL PRODUCTION AND HEALTH - EASTERN PROVINCE
#### DAIRY HUB SOCIETY: OFFICIAL CODE OF CONDUCT & GOVERNANCE CHARTER

**1. PURPOSE & ORGANIZATIONAL ETHICS**
The Dairy Hub Society operates as a premier collaborative producer organization uniting local dairy farmers, veterinary extension officers, and livestock development authorities. The primary objectives are to optimize clean milk yield, ensure guaranteed farm-gate pricing, facilitate collective feed procurement, and uphold superior livestock health standards.

**2. MEMBERSHIP ELIGIBILITY & ENROLLMENT**
- Membership is automatically accorded upon the formal submission and departmental verification of a Dairy Farm Development Survey Application.
- Every registered member agrees to abide by democratic cooperative principles, maintain accurate production ledgers, and uphold collective community welfare.

**3. RAW MILK QUALITY & CHILLING STANDARDS**
- All delivered milk must adhere to standard compositional requirements: minimum 3.8% Milk Fat and 8.5% Solids-Not-Fat (SNF).
- Zero tolerance for adulteration (added water, starch, neutralizing agents, or antibiotics). Milk from animals undergoing antibiotic withdrawal periods must be strictly excluded.
- Milk must be delivered in sanitized stainless steel or food-grade aluminium cans to the collection center within 2 hours of milking.

**4. GOVERNANCE & EXECUTIVE RESPONSIBILITIES**
- The Executive Committee comprises the **President**, **Vice President**, **Secretary**, **Treasurer**, and appointed Committee Members.
- Executive Officers are elected or assigned through institutional consensus to manage daily operations, maintain transparency in price settlement, and liaise with the Government Veterinary Surgeon.
- Executive meetings shall convene monthly to review milk collections, cold-chain operations, and financial audits.

**5. ANIMAL HEALTH, WELFARE & BIOSECURITY**
- All members must participate in compulsory prophylactic disease vaccination campaigns (Foot and Mouth Disease - FMD, Haemorrhagic Septicaemia - HS, and Black Quarter - BQ).
- Mandatory ear-tagging and identification of all registered cattle and buffaloes under the National Livestock Identification Framework.
- Prompt notification to the Government Veterinary Office in the event of suspected contagious disease outbreaks or sudden livestock mortality.

**6. DISCIPLINARY MEASURES & DISPUTE RESOLUTION**
Any member found violating raw milk safety standards, tampering with collection weighing equipment, or acting against collective society welfare shall be subject to Executive Committee review and temporary membership suspension pending departmental investigation.";

        // 7. Dynamic Water Sources Table
        $mysqli->query("
        CREATE TABLE IF NOT EXISTS `dairy_water_sources` (
            `id` INT(11) NOT NULL AUTO_INCREMENT,
            `source_name` VARCHAR(100) NOT NULL,
            `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            UNIQUE KEY `idx_water_source_name` (`source_name`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ");

        // Seed default permanent water sources
        $default_sources = ['Dug Well', 'Pipe-Borne Water', 'Tube Well', 'River / Stream'];
        foreach ($default_sources as $ds) {
            $mysqli->query("INSERT IGNORE INTO `dairy_water_sources` (`source_name`) VALUES ('" . $mysqli->real_escape_string($ds) . "')");
        }

        // 8. Farmer Completed Training Integration Table (Primary Training Module Integration)
        $mysqli->query("
        CREATE TABLE IF NOT EXISTS `farmer_training_records` (
            `id` INT(11) NOT NULL AUTO_INCREMENT,
            `range_id` INT(11) NULL DEFAULT NULL,
            `farm_reg_no` VARCHAR(100) NOT NULL,
            `farmer_name` VARCHAR(255) NOT NULL,
            `nic` VARCHAR(50) NULL DEFAULT NULL,
            `course_name` VARCHAR(255) NOT NULL,
            `institute` VARCHAR(255) NOT NULL,
            `completion_date` DATE NULL DEFAULT NULL,
            `source` VARCHAR(100) NOT NULL DEFAULT 'Dairy Farm Survey',
            `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            INDEX `idx_ft_farm_reg` (`farm_reg_no`),
            INDEX `idx_ft_nic` (`nic`),
            INDEX `idx_ft_range` (`range_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ");

        // 9. Central Master Audit History Table (Strict Non-Deletion & Data Preservation)
        $mysqli->query("
        CREATE TABLE IF NOT EXISTS `farm_master_history` (
            `id` INT(11) NOT NULL AUTO_INCREMENT,
            `farm_reg_no` VARCHAR(100) NOT NULL,
            `module_source` VARCHAR(100) NOT NULL,
            `action_type` VARCHAR(50) NOT NULL,
            `snapshot_data` LONGTEXT NOT NULL,
            `recorded_by` INT(11) NULL DEFAULT NULL,
            `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            INDEX `idx_fmh_reg` (`farm_reg_no`),
            INDEX `idx_fmh_module` (`module_source`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ");

        // 10. Ensure Extended Tracking Columns in dairy_farm_applications
        $dfa_cols = [
            "`animal_id_method` VARCHAR(50) NOT NULL DEFAULT 'Ear Tag'",
            "`branding_remarks` TEXT NULL DEFAULT NULL",
            "`private_milk_qty_liters` DECIMAL(10,2) NOT NULL DEFAULT 0.00",
            "`private_milk_price` DECIMAL(10,2) NOT NULL DEFAULT 0.00",
            "`organized_milk_qty_liters` DECIMAL(10,2) NOT NULL DEFAULT 0.00",
            "`organized_collector_name` VARCHAR(255) NULL DEFAULT NULL",
            "`bookkeeping_records_notes` TEXT NULL DEFAULT NULL",
            "`vaccine_hs_date` DATE NULL DEFAULT NULL",
            "`vaccine_bq_date` DATE NULL DEFAULT NULL",
            "`vaccine_fmd_date` DATE NULL DEFAULT NULL"
        ];
        foreach ($dfa_cols as $c_def) {
            preg_match('/`([^`]+)`/', $c_def, $m);
            if (!empty($m[1])) {
                $col_name = $m[1];
                $chk_col = $mysqli->query("SHOW COLUMNS FROM `dairy_farm_applications` LIKE '$col_name'");
                if ($chk_col && $chk_col->num_rows === 0) {
                    $mysqli->query("ALTER TABLE `dairy_farm_applications` ADD COLUMN $c_def");
                }
            }
        }

        // 11. Soft delete columns for dairy_project_beneficiaries (Non-Deletion Policy)
        $dpb_cols = [
            "`is_deleted` TINYINT(1) NOT NULL DEFAULT 0",
            "`deleted_at` DATETIME NULL DEFAULT NULL"
        ];
        foreach ($dpb_cols as $c_def) {
            preg_match('/`([^`]+)`/', $c_def, $m);
            if (!empty($m[1])) {
                $col_name = $m[1];
                $chk_col = $mysqli->query("SHOW COLUMNS FROM `dairy_project_beneficiaries` LIKE '$col_name'");
                if ($chk_col && $chk_col->num_rows === 0) {
                    $mysqli->query("ALTER TABLE `dairy_project_beneficiaries` ADD COLUMN $c_def");
                }
            }
        }

        // 12. Core Project Entry & Dual Contribution Tracking Columns for projects_progress
        $pp_cols = [
            "`range_office_name` VARCHAR(255) NULL DEFAULT NULL",
            "`activity` VARCHAR(255) NULL DEFAULT NULL",
            "`funding_agent` VARCHAR(255) NULL DEFAULT NULL",
            "`target_beneficiaries` INT(11) NOT NULL DEFAULT 0",
            "`dept_contribution_amount` DECIMAL(12,2) NOT NULL DEFAULT 0.00",
            "`dept_subsidy_percentage` DECIMAL(5,2) NOT NULL DEFAULT 100.00",
            "`dept_contribution_activity` TEXT NULL DEFAULT NULL",
            "`beneficiary_contribution_amount` DECIMAL(12,2) NOT NULL DEFAULT 0.00",
            "`beneficiary_contribution_activity` TEXT NULL DEFAULT NULL"
        ];
        foreach ($pp_cols as $c_def) {
            preg_match('/`([^`]+)`/', $c_def, $m);
            if (!empty($m[1])) {
                $col_name = $m[1];
                $chk_col = $mysqli->query("SHOW COLUMNS FROM `projects_progress` LIKE '$col_name'");
                if ($chk_col && $chk_col->num_rows === 0) {
                    $mysqli->query("ALTER TABLE `projects_progress` ADD COLUMN $c_def");
                }
            }
        }

        // 13. Beneficiary-Specific Sub-Pages & Individual Profiles Table
        $mysqli->query("
        CREATE TABLE IF NOT EXISTS `project_individual_beneficiaries` (
            `id` INT(11) NOT NULL AUTO_INCREMENT,
            `project_id` INT(11) NOT NULL,
            `range_id` INT(11) NULL DEFAULT NULL,
            `beneficiary_name` VARCHAR(255) NOT NULL,
            `nic_no` VARCHAR(50) NOT NULL,
            `address` TEXT NULL DEFAULT NULL,
            `district` VARCHAR(100) NULL DEFAULT NULL,
            `range_name` VARCHAR(100) NULL DEFAULT NULL,
            `gn_division` VARCHAR(100) NULL DEFAULT NULL,
            `phone_no` VARCHAR(50) NULL DEFAULT NULL,
            `gender` ENUM('Male', 'Female', 'Other') NOT NULL DEFAULT 'Male',
            `dept_contribution_amount` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
            `dept_contribution_activity` TEXT NULL DEFAULT NULL,
            `beneficiary_contribution_amount` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
            `beneficiary_contribution_activity` TEXT NULL DEFAULT NULL,
            `start_date` DATE NULL DEFAULT NULL,
            `agreement_date` DATE NULL DEFAULT NULL,
            `completion_date` DATE NULL DEFAULT NULL,
            `doc_submission_date` DATE NULL DEFAULT NULL,
            `final_amount` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
            `check_received_date` DATE NULL DEFAULT NULL,
            `check_no` VARCHAR(100) NULL DEFAULT NULL,
            `status` VARCHAR(50) NOT NULL DEFAULT 'In Progress',
            `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            INDEX `idx_pib_proj` (`project_id`),
            INDEX `idx_pib_nic` (`nic_no`),
            INDEX `idx_pib_range` (`range_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ");

        // 14. Dynamic PSDG Project Catalog (Scalable ~23 PSDG Projects)
        $mysqli->query("
        CREATE TABLE IF NOT EXISTS `psdg_project_catalog` (
            `id` INT(11) NOT NULL AUTO_INCREMENT,
            `category_name` VARCHAR(255) NOT NULL,
            `sub_sector` VARCHAR(100) NOT NULL DEFAULT 'Livestock Development',
            `default_subsidy_pct` DECIMAL(5,2) NOT NULL DEFAULT 75.00,
            `is_active` TINYINT(1) NOT NULL DEFAULT 1,
            `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            UNIQUE KEY `idx_ppc_cat` (`category_name`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ");

        // Pre-seed 23 Standard PSDG Projects
        $psdg_23_projects = [
            'Goat Purchasing & Breed Improvement',
            'Goat Shed Upgrading & Slatted Floor Construction',
            'Cattle Shed Renovation & Milking Parlour',
            'Cattle Shed Construction (New Commercial)',
            'Heifer Calf Rearing Scheme (HCRS)',
            'Dairy Cattle Purchasing Subsidy',
            'Pasture Establishment (CO-3 / CO-4 / Hybrid Napier)',
            'Silage Production & Bunker Construction',
            'Clean Milk Hygiene Equipment & Chilling Units',
            'Biogas Digester Unit Construction',
            'Poultry Unit Assistance (Free-Range Layers)',
            'Commercial Broiler & Layer Housing',
            'Duck Farming Scheme',
            'Swine / Pig Pen Construction & Breeding Support',
            'Milking Machine Distribution Subsidy',
            'Chaff Cutter Machine Distribution',
            'Night Paddock & Bio-Fencing Construction',
            'Cattle Housing Biosecurity & Disinfection Unit',
            'Artificial Insemination (AI) Sub-Center Upgrading',
            'Mineral Block & Feed Supplement Distribution',
            'Farmer Milk Collection Center (MCC) Equipment',
            'Veterinary Health Camp & Mass Deworming Support',
            'Livestock Manure Composting & Vermiculture Unit'
        ];
        foreach ($psdg_23_projects as $pname) {
            $mysqli->query("INSERT IGNORE INTO `psdg_project_catalog` (`category_name`) VALUES ('" . $mysqli->real_escape_string($pname) . "')");
        }

        // 15. Unified Scheduled Monitoring Logs Table (Project & Dairy Hub Site Visits)
        $mysqli->query("
        CREATE TABLE IF NOT EXISTS `project_monitoring_visits` (
            `id` INT(11) NOT NULL AUTO_INCREMENT,
            `source_module` VARCHAR(50) NOT NULL DEFAULT 'Project',
            `project_id` INT(11) NULL DEFAULT NULL,
            `beneficiary_id` INT(11) NULL DEFAULT NULL,
            `dairy_hub_id` INT(11) NULL DEFAULT NULL,
            `farm_reg_no` VARCHAR(100) NULL DEFAULT NULL,
            `farmer_name` VARCHAR(255) NULL DEFAULT NULL,
            `range_id` INT(11) NULL DEFAULT NULL,
            `range_name` VARCHAR(100) NULL DEFAULT NULL,
            `veterinary_surgeon_name` VARCHAR(255) NULL DEFAULT NULL,
            `visit_date` DATE NOT NULL,
            `visit_interval` ENUM('1 Month', '3 Months', '6 Months', 'Bi-Annual (12 Mo)', 'Bi-Annual (18 Mo)', 'Bi-Annual (24 Mo)', 'Routine / Ad-Hoc') NOT NULL,
            `farm_conditions` TEXT NOT NULL,
            `animal_illnesses` TEXT NULL DEFAULT NULL,
            `deaths_count` INT(11) NOT NULL DEFAULT 0,
            `deaths_details` TEXT NULL DEFAULT NULL,
            `natural_disasters` TEXT NULL DEFAULT NULL,
            `recommendations` TEXT NULL DEFAULT NULL,
            `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            INDEX `idx_pmv_proj` (`project_id`),
            INDEX `idx_pmv_ben` (`beneficiary_id`),
            INDEX `idx_pmv_reg` (`farm_reg_no`),
            INDEX `idx_pmv_source` (`source_module`),
            INDEX `idx_pmv_range` (`range_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ");

        return true;
    }
}
