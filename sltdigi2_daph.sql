
CREATE TABLE `advanced_programmes` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `type_id` int(11) NOT NULL,
  `programme_year` year(4) NOT NULL,
  `place` varchar(255) NOT NULL,
  `activity_description` text,
  `mid_term_status` enum('Pending','Submitted','Approved','Rejected') DEFAULT 'Pending',
  `mid_term_remarks` text,
  `mid_term_approved_at` datetime DEFAULT NULL,
  `final_status` enum('Pending','Submitted','Approved','Rejected') DEFAULT 'Pending',
  `final_remarks` text,
  `final_approved_at` datetime DEFAULT NULL,
  `current_stage` enum('Admin_Draft','PD_MidTerm_Review','Admin_Implementation','PD_Final_Review','Completed') DEFAULT 'Admin_Draft',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;



CREATE TABLE `amended_programmes` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `original_id` int(11) DEFAULT NULL,
  `programme_year` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL,
  `type_id` int(11) NOT NULL,
  `place` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `activity_description` mediumtext COLLATE utf8mb4_unicode_ci,
  `amendment_reason` mediumtext COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;



CREATE TABLE `animal_health_records` (
  `id` int(11) NOT NULL,
  `range_id` int(11) NOT NULL,
  `date` date NOT NULL,
  `farmer_reg_no` varchar(50) NOT NULL,
  `animal_type` enum('Cattle','Buffalo','Goat','Sheep','Swine','Poultry','Ornamental Birds','Other') NOT NULL,
  `disease_name` varchar(100) NOT NULL,
  `occurrence_count` int(11) DEFAULT '0',
  `vaccine_name` varchar(100) DEFAULT NULL,
  `doses` int(11) DEFAULT '0',
  `treatment_details` text,
  `report_status` enum('Draft','Submitted','Approved') DEFAULT 'Submitted',
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;



CREATE TABLE `assets_immovable` (
  `id` int(11) NOT NULL,
  `range_id` int(11) NOT NULL,
  `asset_name` varchar(255) NOT NULL,
  `description` text,
  `location` varchar(255) DEFAULT NULL,
  `extent` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;



CREATE TABLE `assets_movable` (
  `id` int(11) NOT NULL,
  `range_id` int(11) NOT NULL,
  `asset_category` enum('Vehicle','Equipment','Furniture','Other') DEFAULT 'Equipment',
  `item_name` varchar(255) NOT NULL,
  `serial_no` varchar(100) DEFAULT NULL,
  `condition` enum('Good','Fair','Needs Repair','Discarded') DEFAULT 'Good'
) ENGINE=InnoDB DEFAULT CHARSET=latin1;



CREATE TABLE `audit_logs` (
  `id` int(11) NOT NULL,
  `log_timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `user_id` int(11) NOT NULL,
  `username` varchar(50) DEFAULT NULL,
  `role` varchar(30) DEFAULT NULL,
  `action_type` varchar(20) NOT NULL,
  `module_name` varchar(100) DEFAULT NULL,
  `table_name` varchar(100) DEFAULT NULL,
  `record_id` int(11) DEFAULT NULL,
  `old_values` text,
  `new_values` text,
  `ip_address` varchar(45) DEFAULT NULL,
  `device_info` text,
  `remarks` text
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


CREATE TABLE `breeding_progress` (
  `id` int(11) NOT NULL,
  `range_id` int(11) NOT NULL,
  `officer_id` int(11) NOT NULL,
  `year` year(4) NOT NULL,
  `month_number` tinyint(4) NOT NULL,
  `ai_count` int(11) DEFAULT '0',
  `pd_count` int(11) DEFAULT '0',
  `calving_count` int(11) DEFAULT '0',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=latin1;



CREATE TABLE `breeding_target_templates` (
  `id` int(11) NOT NULL,
  `range_id` int(11) NOT NULL,
  `year` year(4) NOT NULL,
  `designation` varchar(100) NOT NULL,
  `target_ai` int(11) DEFAULT '0',
  `target_pd` int(11) DEFAULT '0',
  `target_calving` int(11) DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;



CREATE TABLE `daily_egg_production` (
  `id` int(11) NOT NULL,
  `flock_id` int(11) NOT NULL,
  `collection_date` date NOT NULL,
  `egg_count` int(11) NOT NULL DEFAULT '0',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;



CREATE TABLE `dairy_hub_records` (
  `id` int(11) NOT NULL,
  `range_id` int(11) NOT NULL,
  `collection_date` date NOT NULL,
  `farmer_reg_no` varchar(255) NOT NULL,
  `milk_quantity_liters` decimal(10,2) NOT NULL,
  `fat_percentage` decimal(4,2) DEFAULT '0.00',
  `snf_percentage` decimal(4,2) DEFAULT '0.00',
  `price_per_liter` decimal(10,2) DEFAULT '0.00',
  `total_amount` decimal(15,2) GENERATED ALWAYS AS ((`milk_quantity_liters` * `price_per_liter`)) STORED,
  `created_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;



CREATE TABLE `diary_tasks` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `task_date` date NOT NULL,
  `place` varchar(255) NOT NULL,
  `activity` text NOT NULL,
  `status` enum('Not Started','Ongoing','Completed') DEFAULT 'Not Started',
  `task_type` enum('Daily','Advanced','Amendment','Annual') DEFAULT 'Daily',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;



CREATE TABLE `districts` (
  `id` int(11) NOT NULL,
  `name` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;


-- --------------------------------------------------------

--
-- Table structure for table `drug_records`
--

CREATE TABLE `drug_records` (
  `id` int(11) NOT NULL,
  `log_date` date NOT NULL,
  `drug_type_id` int(11) NOT NULL COMMENT 'References drug_types.id',
  `vaccine_batch_id` int(11) NOT NULL COMMENT 'References vaccine_batches.id',
  `starter_count_month` int(11) NOT NULL DEFAULT '0',
  `during_month_received` int(11) NOT NULL DEFAULT '0',
  `used_doses_count` int(11) NOT NULL DEFAULT '0',
  `doses_damaged` int(11) NOT NULL DEFAULT '0',
  `balance_end_month` int(11) GENERATED ALWAYS AS (((`starter_count_month` + `during_month_received`) - (`used_doses_count` + `doses_damaged`))) STORED,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;



CREATE TABLE `drug_types` (
  `id` int(11) NOT NULL,
  `vaccine_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `target_animal` set('Cattle','Dairy Cows','Buffalo','Goats','Poultry','other') COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `date` date DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


--

CREATE TABLE `hatchery_batches` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `batch_date` date NOT NULL,
  `hatchable_count` int(11) NOT NULL DEFAULT '0',
  `cracked_count` int(11) NOT NULL DEFAULT '0',
  `table_count` int(11) NOT NULL DEFAULT '0',
  `total_collected` int(11) GENERATED ALWAYS AS (((`hatchable_count` + `cracked_count`) + `table_count`)) STORED,
  `chicks_hatched` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


--

CREATE TABLE `hatchery_sales` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `sales_date` date NOT NULL,
  `egg_category` enum('Table','Cracked') NOT NULL,
  `quantity_sold` int(11) NOT NULL DEFAULT '0',
  `actual_rate` decimal(10,2) NOT NULL DEFAULT '0.00',
  `hope_rate` decimal(10,2) NOT NULL DEFAULT '0.00',
  `total_revenue` decimal(15,2) GENERATED ALWAYS AS ((`quantity_sold` * `actual_rate`)) STORED,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;



--
-- Table structure for table `inquiries`
--

CREATE TABLE `inquiries` (
  `id` int(11) NOT NULL,
  `sender_name` varchar(255) NOT NULL,
  `sender_email` varchar(150) NOT NULL,
  `subject` varchar(255) DEFAULT NULL,
  `message_body` text NOT NULL,
  `received_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `status` enum('Pending','Minuted','Replied','Closed') DEFAULT 'Pending'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;



--
-- Table structure for table `inquiry_logs`
--

CREATE TABLE `inquiry_logs` (
  `id` int(11) NOT NULL,
  `inquiry_id` int(11) NOT NULL,
  `action_type` enum('MINUTE','REPLY') NOT NULL,
  `processed_by` int(11) NOT NULL,
  `assigned_to` int(11) DEFAULT NULL,
  `content` text NOT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `leave_requests`
--

CREATE TABLE `leave_requests` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `leave_type` varchar(50) NOT NULL,
  `request_date` date NOT NULL,
  `start_date` date NOT NULL,
  `resume_date` date NOT NULL,
  `no_of_days` decimal(4,2) NOT NULL,
  `is_half_day` tinyint(1) DEFAULT '0',
  `reason` text NOT NULL,
  `acting_user_id` int(11) DEFAULT NULL,
  `status` enum('Pending','Approved','Rejected') DEFAULT 'Pending',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


--

CREATE TABLE `livestock_targets` (
  `id` int(11) NOT NULL,
  `range_id` int(11) NOT NULL,
  `item_id` int(11) NOT NULL,
  `target_year` int(11) NOT NULL,
  `annual_target_value` decimal(15,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;



CREATE TABLE `master_programme_types` (
  `id` int(11) NOT NULL,
  `programme_name` varchar(255) NOT NULL,
  `is_active` tinyint(1) DEFAULT '1',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;



CREATE TABLE `master_units` (
  `id` int(11) NOT NULL,
  `unit_name` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


--

CREATE TABLE `monthly_production_records` (
  `id` int(11) NOT NULL,
  `range_id` int(11) NOT NULL,
  `item_id` int(11) NOT NULL,
  `amount` decimal(15,2) NOT NULL,
  `report_date` date NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=latin1;



--
-- Table structure for table `office_details`
--

CREATE TABLE `office_details` (
  `id` int(11) NOT NULL,
  `range_id` int(11) DEFAULT NULL,
  `unit_id` int(11) DEFAULT NULL,
  `officer_name` varchar(255) NOT NULL,
  `designation` varchar(100) NOT NULL,
  `emp_id` varchar(50) DEFAULT NULL,
  `contact_number` varchar(20) DEFAULT NULL,
  `registered_date` date DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `status` enum('Active','Inactive') DEFAULT 'Active'
) ENGINE=InnoDB DEFAULT CHARSET=latin1;



--
-- Table structure for table `parent_stock_flocks`
--

CREATE TABLE `parent_stock_flocks` (
  `id` int(11) NOT NULL,
  `flock_code` varchar(50) NOT NULL,
  `region` varchar(100) NOT NULL,
  `current_count` int(11) NOT NULL DEFAULT '0',
  `assigned_cages` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;



--
-- Table structure for table `production_categories`
--

CREATE TABLE `production_categories` (
  `id` int(11) NOT NULL,
  `category_name` varchar(100) NOT NULL,
  `sort_order` int(11) DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=latin1;



--
-- Table structure for table `production_items`
--

CREATE TABLE `production_items` (
  `id` int(11) NOT NULL,
  `category_id` int(11) NOT NULL,
  `item_name` varchar(100) NOT NULL,
  `unit` varchar(20) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;



--
-- Table structure for table `projects_progress`
--

CREATE TABLE `projects_progress` (
  `id` int(11) NOT NULL,
  `range_id` int(11) NOT NULL,
  `project_type` enum('PSDG','LMP','CBG','Special','Other') NOT NULL,
  `project_name` varchar(255) NOT NULL,
  `summary` text,
  `location` varchar(255) DEFAULT NULL,
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `priority` enum('Low','Medium','High','Urgent') DEFAULT 'Medium',
  `progress_percent` int(3) DEFAULT '0',
  `status` enum('Planned','In Progress','On Hold','Completed') DEFAULT 'Planned',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=latin1;


--

CREATE TABLE `project_assignments` (
  `id` int(11) NOT NULL,
  `project_id` int(11) NOT NULL,
  `officer_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;



CREATE TABLE `regulatory_records` (
  `id` int(11) NOT NULL,
  `range_id` int(11) NOT NULL,
  `date` date NOT NULL,
  `activity_type` varchar(50) DEFAULT NULL,
  `certificate_number` varchar(50) DEFAULT NULL,
  `details` text,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `semen_logs`
--

CREATE TABLE `semen_logs` (
  `id` int(11) NOT NULL,
  `range_id` int(11) NOT NULL,
  `report_month` tinyint(4) NOT NULL,
  `report_year` int(11) NOT NULL,
  `species` varchar(50) NOT NULL,
  `opening_balance` int(11) DEFAULT '0',
  `received_qty` int(11) DEFAULT '0',
  `used_qty` int(11) DEFAULT '0',
  `issued_qty` int(11) DEFAULT '0',
  `spoiled_qty` int(11) DEFAULT '0',
  `paid_amount` decimal(15,2) DEFAULT '0.00',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=latin1;



--
-- Table structure for table `slaughter_statistics`
--

CREATE TABLE `slaughter_statistics` (
  `id` int(11) NOT NULL,
  `range_id` int(11) NOT NULL,
  `report_month` tinyint(4) NOT NULL,
  `report_year` int(11) NOT NULL,
  `species` enum('Cattle','Goat','Poultry','Pig','Other') NOT NULL,
  `location_type` enum('Slaughter House','In-Farm') NOT NULL,
  `animal_count` int(11) NOT NULL DEFAULT '0',
  `total_weight_kg` decimal(12,2) NOT NULL DEFAULT '0.00',
  `created_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=latin1;


CREATE TABLE `sms_immunization` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `log_date` date NOT NULL,
  `vaccination_type` varchar(150) NOT NULL,
  `starter_count_month` int(11) NOT NULL DEFAULT '0',
  `during_month_received` int(11) NOT NULL DEFAULT '0',
  `used_batch_number` varchar(100) NOT NULL,
  `used_doses_count` int(11) NOT NULL DEFAULT '0',
  `doses_damaged` int(11) NOT NULL DEFAULT '0',
  `balance_batch_number` varchar(100) NOT NULL,
  `balance_doses_qty` int(11) NOT NULL DEFAULT '0',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `sms_immunization`
--


-- --------------------------------------------------------

--
-- Table structure for table `stock_balance_logs`
--

CREATE TABLE `stock_balance_logs` (
  `id` int(11) NOT NULL,
  `flock_id` int(11) NOT NULL,
  `newly_added` int(11) DEFAULT '0',
  `culling` int(11) DEFAULT '0',
  `log_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `stock_balance_logs`
--


-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `phone` varchar(15) DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `emp_id` varchar(50) DEFAULT NULL,
  `designation` varchar(100) DEFAULT NULL,
  `role` enum('provincial_director','district_dd','veterinary_surgeon','training_officer','sms','farms_dd','finance_admin','planning_officer','administrator','data_entry','employee') NOT NULL,
  `district_id` int(11) DEFAULT NULL,
  `range_id` int(11) DEFAULT NULL,
  `unit_id` int(11) DEFAULT NULL,
  `registered_date` date DEFAULT NULL,
  `office_id` int(11) DEFAULT NULL COMMENT 'Links to veterinary_ranges.id (for Veterinary Surgeon only)',
  `district` enum('Amparai','Batticaloa','Trincomalee','Provincial') NOT NULL,
  `is_active` tinyint(1) DEFAULT '1',
  `last_login` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `profile_image` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Dumping data for table `users`
--


--
-- Table structure for table `vaccine_batches`
--

CREATE TABLE `vaccine_batches` (
  `id` int(11) NOT NULL,
  `batch_number` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `remarks` text COLLATE utf8mb4_unicode_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `vaccine_batches`
--


--
-- Table structure for table `vaccine_types`
--

CREATE TABLE `vaccine_types` (
  `id` int(11) NOT NULL,
  `vaccine_name` varchar(100) NOT NULL,
  `target_animal` varchar(255) DEFAULT NULL,
  `description` text,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `vaccine_types`
--


--
-- Table structure for table `veterinary_ranges`
--

CREATE TABLE `veterinary_ranges` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `district_id` int(11) DEFAULT NULL,
  `code` varchar(20) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT '1'
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

