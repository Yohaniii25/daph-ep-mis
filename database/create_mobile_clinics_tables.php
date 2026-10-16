<?php
require_once __DIR__ . '/../config/db_connect.php';

/** @var mysqli $mysqli */
global $mysqli;

// 1. Table: mobile_clinics
$sql1 = "
CREATE TABLE IF NOT EXISTS `mobile_clinics` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `district_id` int(11) DEFAULT 1,
  `range_id` int(11) DEFAULT 1,
  `report_year` year(4) NOT NULL,
  `report_month` tinyint(4) NOT NULL,
  `clinic_date` date NOT NULL,
  `places_visited` text DEFAULT NULL,
  `team_members_json` longtext DEFAULT NULL,
  `farmers_benefited` int(11) NOT NULL DEFAULT 0,
  `cases_reported` int(11) NOT NULL DEFAULT 0,
  `cases_attended` int(11) NOT NULL DEFAULT 0,
  `ai_done` int(11) NOT NULL DEFAULT 0,
  `pd_diagnosed` int(11) NOT NULL DEFAULT 0,
  `animals_vaccinated` int(11) NOT NULL DEFAULT 0,
  `animals_treated` int(11) NOT NULL DEFAULT 0,
  `poultry_gumboro` int(11) NOT NULL DEFAULT 0,
  `poultry_ranikhet_1st` int(11) NOT NULL DEFAULT 0,
  `poultry_ranikhet_2nd` int(11) NOT NULL DEFAULT 0,
  `poultry_fowlpox` int(11) NOT NULL DEFAULT 0,
  `cattle_goat_fmd_bq` int(11) NOT NULL DEFAULT 0,
  `cattle_goat_arv` int(11) NOT NULL DEFAULT 0,
  `training_conducted` int(11) NOT NULL DEFAULT 0,
  `training_type` varchar(255) DEFAULT '',
  `training_participants` int(11) NOT NULL DEFAULT 0,
  `animals_ear_tagged` int(11) NOT NULL DEFAULT 0,
  `remarks` text DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_mc_range_date` (`range_id`, `clinic_date`),
  KEY `idx_mc_year_month` (`report_year`, `report_month`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
";
if ($mysqli->query($sql1)) {
    echo "Table 'mobile_clinics' created or verified successfully.\n";
} else {
    echo "Error creating 'mobile_clinics': " . $mysqli->error . "\n";
}

// 2. Table: mobile_clinic_drug_distributions
$sql2 = "
CREATE TABLE IF NOT EXISTS `mobile_clinic_drug_distributions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `district_id` int(11) DEFAULT 1,
  `range_id` int(11) DEFAULT 1,
  `report_year` year(4) NOT NULL,
  `report_month` tinyint(4) NOT NULL,
  `distribution_date` date NOT NULL,
  `drug_type_id` int(11) DEFAULT NULL,
  `drug_name` varchar(255) NOT NULL,
  `batch_no` varchar(100) DEFAULT NULL,
  `initial_stock` decimal(12,2) NOT NULL DEFAULT 0.00,
  `total_issued` decimal(12,2) NOT NULL DEFAULT 0.00,
  `balance_in_hand` decimal(12,2) NOT NULL DEFAULT 0.00,
  `unit_of_measure` varchar(50) DEFAULT 'Doses',
  `remarks` text DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_mcdd_range_date` (`range_id`, `distribution_date`),
  KEY `idx_mcdd_drug_name` (`drug_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
";
if ($mysqli->query($sql2)) {
    echo "Table 'mobile_clinic_drug_distributions' created or verified successfully.\n";
} else {
    echo "Error creating 'mobile_clinic_drug_distributions': " . $mysqli->error . "\n";
}

// 3. Table: mobile_clinic_treatments
$sql3 = "
CREATE TABLE IF NOT EXISTS `mobile_clinic_treatments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `distribution_id` int(11) NOT NULL,
  `serial_no` int(11) NOT NULL DEFAULT 1,
  `farmer_name` varchar(255) NOT NULL,
  `farmer_address` text DEFAULT NULL,
  `farmer_nic` varchar(50) DEFAULT NULL,
  `species_affected` varchar(100) DEFAULT 'Cattle',
  `disease_diagnosed` varchar(255) DEFAULT NULL,
  `animals_treated` int(11) NOT NULL DEFAULT 1,
  `total_dose_issued` decimal(10,2) NOT NULL DEFAULT 0.00,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_mct_distribution` (`distribution_id`),
  KEY `idx_mct_farmer_nic` (`farmer_nic`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
";
if ($mysqli->query($sql3)) {
    echo "Table 'mobile_clinic_treatments' created or verified successfully.\n";
} else {
    echo "Error creating 'mobile_clinic_treatments': " . $mysqli->error . "\n";
}
