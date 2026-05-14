-- Main table for parent stock
CREATE TABLE `parent_stock_flocks` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `flock_code` VARCHAR(50) NOT NULL,
  `region` VARCHAR(100) NOT NULL,
  `assigned_cages` VARCHAR(255),
  `current_count` INT(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Audit log for every update
CREATE TABLE `stock_balance_logs` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `flock_id` INT(11) NOT NULL,
  `newly_added` INT(11) DEFAULT 0,
  `culling` INT(11) DEFAULT 0,
  `log_date` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`flock_id`) REFERENCES `parent_stock_flocks`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Daily egg production table (referenced in poultry_hatchery.php)
CREATE TABLE IF NOT EXISTS `daily_egg_production` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `flock_id` INT(11) NOT NULL,
  `collection_date` DATE NOT NULL,
  `egg_count` INT(11) NOT NULL DEFAULT 0,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`flock_id`) REFERENCES `parent_stock_flocks`(`id`),
  UNIQUE KEY `unique_flock_date` (`flock_id`, `collection_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Insert sample flocks
INSERT IGNORE INTO `parent_stock_flocks` (`flock_code`, `region`, `assigned_cages`, `current_count`) VALUES
('SAT-CB-2026-01', 'Sathurukondan', 'C-01, C-02', 4850),
('THM-CB-2026-02', 'Thampalakamam', 'B-05', 4920),
('TRK-HB-2026-01', 'Thirukkovil', 'A-10', 2870);