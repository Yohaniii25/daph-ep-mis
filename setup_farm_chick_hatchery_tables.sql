-- Database Migration Script for Hatchery Register & Chick Details Revamp
-- Target Database: daph

USE daph;

-- 1. Hatchery Register (Annex 3)
CREATE TABLE IF NOT EXISTS `hatchery_register` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `record_date` DATE NOT NULL,
  `cage_id` INT(11) NOT NULL,
  `no_of_eggs_loaded` INT(11) NOT NULL DEFAULT 0,
  `date_of_candling` DATE DEFAULT NULL,
  `discarded_during_candling` INT(11) NOT NULL DEFAULT 0,
  `date_of_hatching` DATE DEFAULT NULL,
  `no_of_hatched_eggs` INT(11) NOT NULL DEFAULT 0,
  `no_of_deaths` INT(11) NOT NULL DEFAULT 0,
  `no_of_good_chicks` INT(11) NOT NULL DEFAULT 0,
  `hatching_percentage` DECIMAL(5,2) NOT NULL DEFAULT 0.00,
  `loaded_to_cage_id` INT(11) NOT NULL,
  `remark` TEXT DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `cage_id` (`cage_id`),
  KEY `loaded_to_cage_id` (`loaded_to_cage_id`),
  CONSTRAINT `fk_hatchery_cage` FOREIGN KEY (`cage_id`) REFERENCES `cages` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_hatchery_target_cage` FOREIGN KEY (`loaded_to_cage_id`) REFERENCES `cages` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 2. Scenario A: Chick Growth Log (If Grown for Month-Old Chicks - Daily Log)
CREATE TABLE IF NOT EXISTS `chick_growth_log` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `record_date` DATE NOT NULL,
  `cage_id` INT(11) NOT NULL,
  `opening_chicks_count` INT(11) NOT NULL DEFAULT 0,
  `no_of_deaths` INT(11) NOT NULL DEFAULT 0,
  `feed_type` VARCHAR(255) DEFAULT NULL,
  `feed_amount_to_be_given` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `feed_amount_given` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `vaccination_treatment` TEXT DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `cage_id` (`cage_id`),
  KEY `record_date` (`record_date`),
  CONSTRAINT `fk_growth_cage` FOREIGN KEY (`cage_id`) REFERENCES `cages` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 3. Scenario B: Day-Old Chicks Distribution
CREATE TABLE IF NOT EXISTS `day_old_chicks_distribution` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `record_date` DATE NOT NULL,
  `no_of_chicks_produced` INT(11) NOT NULL DEFAULT 0,
  `sent_to_place` VARCHAR(255) NOT NULL,
  `no_of_chicks_sent` INT(11) NOT NULL DEFAULT 0,
  `price_per_chick` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `total_amount` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `record_date` (`record_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 4. Scenario C: Month-Old Chicks Distribution
CREATE TABLE IF NOT EXISTS `month_old_chicks_distribution` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `record_date` DATE NOT NULL,
  `cage_id` INT(11) DEFAULT NULL,
  `no_of_chicks_produced` INT(11) NOT NULL DEFAULT 0,
  `sent_to_place` VARCHAR(255) NOT NULL,
  `no_of_chicks_sent` INT(11) NOT NULL DEFAULT 0,
  `price_per_chick` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `total_amount` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `cage_id` (`cage_id`),
  KEY `record_date` (`record_date`),
  CONSTRAINT `fk_month_dist_cage` FOREIGN KEY (`cage_id`) REFERENCES `cages` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
