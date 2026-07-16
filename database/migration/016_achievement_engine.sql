-- Drop old tables if they exist from previous phases to start fresh
DROP TABLE IF EXISTS `user_achievements`;
DROP TABLE IF EXISTS `achievements`;

-- 1. Achievement Definitions (The Rules)
CREATE TABLE `achievement_definitions` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `slug` VARCHAR(100) NOT NULL UNIQUE,
    `name` VARCHAR(150) NOT NULL,
    `description` TEXT,
    `icon` VARCHAR(50) DEFAULT 'fa-trophy',
    `color` VARCHAR(7) DEFAULT '#3b82f6',
    `category` VARCHAR(50) NOT NULL,
    `rarity` ENUM('common', 'rare', 'epic', 'legendary', 'hidden') DEFAULT 'common',
    `xp_value` INT DEFAULT 10,
    `rule_type` VARCHAR(50) NOT NULL,
    `rule_config` JSON NOT NULL,
    `is_active` TINYINT(1) DEFAULT 1,
    INDEX `idx_rule_type` (`rule_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. User Progress
CREATE TABLE `user_achievements` (
    `user_id` INT UNSIGNED NOT NULL,
    `achievement_id` INT UNSIGNED NOT NULL,
    `progress` DECIMAL(15,2) DEFAULT 0.00,
    `target` DECIMAL(15,2) DEFAULT 100.00,
    `unlocked_at` TIMESTAMP NULL,
    PRIMARY KEY (`user_id`, `achievement_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Cached User Stats (For lightning-fast rule evaluation)
CREATE TABLE `user_financial_stats` (
    `user_id` INT UNSIGNED PRIMARY KEY,
    `net_worth` DECIMAL(15,2) DEFAULT 0.00,
    `total_savings` DECIMAL(15,2) DEFAULT 0.00,
    `transaction_count` INT DEFAULT 0,
    `account_count` INT DEFAULT 0,
    `vault_count` INT DEFAULT 0,
    `completed_vaults` INT DEFAULT 0,
    `total_xp` INT DEFAULT 0,
    `level` INT DEFAULT 1,
    `wealth_tier` VARCHAR(50) DEFAULT 'Broke',
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;