CREATE DATABASE IF NOT EXISTS `jeevalink` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `jeevalink`;

-- --------------------------------------------------------
-- Table structure for table `users`
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `users` (
  `id` BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  `full_name` VARCHAR(255) NOT NULL,
  `email` VARCHAR(255) NOT NULL UNIQUE,
  `mobile` VARCHAR(20) NOT NULL UNIQUE,
  `password_hash` VARCHAR(255) NOT NULL,
  `role` ENUM('donor', 'volunteer', 'hospital', 'admin') NOT NULL DEFAULT 'donor',
  `blood_group` VARCHAR(5) NOT NULL DEFAULT 'N/A',
  `city` VARCHAR(100) NOT NULL,
  `district` VARCHAR(100) NOT NULL,
  `address` TEXT DEFAULT NULL,
  `weight` INT DEFAULT NULL,
  `date_of_birth` DATE DEFAULT NULL,
  `last_donated_date` DATE DEFAULT NULL,
  `profile_picture` TEXT DEFAULT NULL,
  `available_for_donation` BOOLEAN NOT NULL DEFAULT TRUE,
  `reward_points` INT NOT NULL DEFAULT 100,
  `lives_saved` INT NOT NULL DEFAULT 0,
  `total_donations` INT NOT NULL DEFAULT 0,
  `status` ENUM('Active', 'Pending Approval', 'Suspended', 'Rejected') NOT NULL DEFAULT 'Active',
  `expo_push_token` TEXT DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX `idx_users_blood_group` (`blood_group`),
  INDEX `idx_users_district` (`district`),
  INDEX `idx_users_city` (`city`),
  INDEX `idx_users_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for table `blood_requests`
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `blood_requests` (
  `id` BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  `requested_by` BIGINT UNSIGNED NOT NULL,
  `patient_name` VARCHAR(255) NOT NULL,
  `blood_group` VARCHAR(5) NOT NULL,
  `units_required` INT NOT NULL DEFAULT 1,
  `hospital_name` VARCHAR(255) NOT NULL,
  `hospital_address` TEXT DEFAULT NULL,
  `city` VARCHAR(100) NOT NULL,
  `district` VARCHAR(100) NOT NULL,
  `location` TEXT DEFAULT NULL,
  `contact_number` VARCHAR(20) NOT NULL,
  `contact_person_name` VARCHAR(255) DEFAULT NULL,
  `required_by_date` DATETIME NOT NULL,
  `urgency_level` ENUM('Normal', 'Urgent', 'Emergency SOS') NOT NULL DEFAULT 'Normal',
  `additional_notes` TEXT DEFAULT NULL,
  `status` ENUM('Pending', 'Fulfilled') NOT NULL DEFAULT 'Pending',
  `verified` BOOLEAN NOT NULL DEFAULT FALSE,
  `fulfilled_by` BIGINT UNSIGNED DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`requested_by`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`fulfilled_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  INDEX `idx_requests_blood_group` (`blood_group`),
  INDEX `idx_requests_district` (`district`),
  INDEX `idx_requests_city` (`city`),
  INDEX `idx_requests_urgency` (`urgency_level`),
  INDEX `idx_requests_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for table `notifications`
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `notifications` (
  `id` BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  `recipient_id` BIGINT UNSIGNED NOT NULL,
  `title` VARCHAR(255) NOT NULL,
  `message` TEXT NOT NULL,
  `type` ENUM('SOS', 'Reward', 'Match', 'Fulfilled', 'Warning') NOT NULL,
  `is_read` BOOLEAN NOT NULL DEFAULT FALSE,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`recipient_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  INDEX `idx_notifications_recipient` (`recipient_id`),
  INDEX `idx_notifications_read` (`is_read`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for table `complaints`
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `complaints` (
  `id` BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  `reporter_id` BIGINT UNSIGNED NOT NULL,
  `target_id` BIGINT UNSIGNED NOT NULL,
  `reason` TEXT NOT NULL,
  `status` ENUM('Pending', 'Resolved') NOT NULL DEFAULT 'Pending',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`reporter_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`target_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  INDEX `idx_complaints_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
