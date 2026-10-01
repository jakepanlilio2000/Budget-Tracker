-- =========================================================================
-- EXPENSEPRO v3.0 HARDENING & GAMIFICATION RE-ARCHITECTURE PATCH (REVISED)
-- Safe, Idempotent Migration Script for MySQL 8.0+ / MariaDB 10.5+
-- =========================================================================

SET FOREIGN_KEY_CHECKS = 0;

-- -------------------------------------------------------------------------
-- 1. Upgrade user_achievements table
-- -------------------------------------------------------------------------
DROP PROCEDURE IF EXISTS `UpgradeUserAchievementsV3`;
DELIMITER $$
CREATE PROCEDURE `UpgradeUserAchievementsV3`()
BEGIN
    -- A. Add achievement_key column if missing
    IF NOT EXISTS (
        SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS 
        WHERE TABLE_SCHEMA = DATABASE() 
          AND TABLE_NAME = 'user_achievements' 
          AND COLUMN_NAME = 'achievement_key'
    ) THEN
        ALTER TABLE `user_achievements` ADD COLUMN `achievement_key` VARCHAR(64) NOT NULL DEFAULT '' AFTER `achievement_id`;
    END IF;

    -- B. Add source_reference_id if missing
    IF NOT EXISTS (
        SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS 
        WHERE TABLE_SCHEMA = DATABASE() 
          AND TABLE_NAME = 'user_achievements' 
          AND COLUMN_NAME = 'source_reference_id'
    ) THEN
        ALTER TABLE `user_achievements` ADD COLUMN `source_reference_id` VARCHAR(64) NULL AFTER `unlocked_at`;
    END IF;

    -- C. Add fxp_awarded if missing
    IF NOT EXISTS (
        SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS 
        WHERE TABLE_SCHEMA = DATABASE() 
          AND TABLE_NAME = 'user_achievements' 
          AND COLUMN_NAME = 'fxp_awarded'
    ) THEN
        ALTER TABLE `user_achievements` ADD COLUMN `fxp_awarded` INT NOT NULL DEFAULT 0 AFTER `source_reference_id`;
    END IF;

    -- D. Add is_revoked if missing
    IF NOT EXISTS (
        SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS 
        WHERE TABLE_SCHEMA = DATABASE() 
          AND TABLE_NAME = 'user_achievements' 
          AND COLUMN_NAME = 'is_revoked'
    ) THEN
        ALTER TABLE `user_achievements` ADD COLUMN `is_revoked` TINYINT(1) NOT NULL DEFAULT 0 AFTER `fxp_awarded`;
    END IF;

    -- E. Add revoked_at if missing
    IF NOT EXISTS (
        SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS 
        WHERE TABLE_SCHEMA = DATABASE() 
          AND TABLE_NAME = 'user_achievements' 
          AND COLUMN_NAME = 'revoked_at'
    ) THEN
        ALTER TABLE `user_achievements` ADD COLUMN `revoked_at` TIMESTAMP NULL AFTER `is_revoked`;
    END IF;

    -- =====================================================================
    -- F. ROOT CAUSE FIX FOR ERROR #1062:
    -- Backfill achievement_key from achievement_definitions.slug for any existing
    -- rows with empty or NULL keys BEFORE creating the unique constraint.
    -- =====================================================================
    UPDATE `user_achievements` ua
    JOIN `achievement_definitions` ad ON ua.achievement_id = ad.id
    SET ua.achievement_key = ad.slug
    WHERE ua.achievement_key = '' OR ua.achievement_key IS NULL;

    -- G. For any legacy/orphaned rows where achievement_id was not in achievement_definitions,
    -- assign a distinct deterministic key based on achievement_id
    UPDATE `user_achievements`
    SET `achievement_key` = CONCAT('legacy_ach_', `achievement_id`)
    WHERE `achievement_key` = '' OR `achievement_key` IS NULL;

    -- H. Deduplicate any duplicate (user_id, achievement_key) rows before adding UNIQUE index
    DELETE t1 FROM `user_achievements` t1
    INNER JOIN `user_achievements` t2 
    WHERE t1.user_id = t2.user_id 
      AND t1.achievement_key = t2.achievement_key 
      AND (
          t1.achievement_id < t2.achievement_id 
          OR (t1.unlocked_at IS NULL AND t2.unlocked_at IS NOT NULL)
      );

    -- I. Safely create the UNIQUE INDEX on (user_id, achievement_key)
    IF NOT EXISTS (
        SELECT 1 FROM INFORMATION_SCHEMA.STATISTICS 
        WHERE TABLE_SCHEMA = DATABASE() 
          AND TABLE_NAME = 'user_achievements' 
          AND INDEX_NAME = 'uniq_user_achievement_key'
    ) THEN
        ALTER TABLE `user_achievements` ADD UNIQUE INDEX `uniq_user_achievement_key` (`user_id`, `achievement_key`);
    END IF;
END $$
DELIMITER ;

CALL `UpgradeUserAchievementsV3`();
DROP PROCEDURE `UpgradeUserAchievementsV3`;

-- -------------------------------------------------------------------------
-- 2. Upgrade user_financial_stats table
-- -------------------------------------------------------------------------
DROP PROCEDURE IF EXISTS `UpgradeUserFinancialStatsV3`;
DELIMITER $$
CREATE PROCEDURE `UpgradeUserFinancialStatsV3`()
BEGIN
    IF NOT EXISTS (
        SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS 
        WHERE TABLE_SCHEMA = DATABASE() 
          AND TABLE_NAME = 'user_financial_stats' 
          AND COLUMN_NAME = 'current_streak'
    ) THEN
        ALTER TABLE `user_financial_stats` ADD COLUMN `current_streak` INT NOT NULL DEFAULT 0 AFTER `level`;
    END IF;

    IF NOT EXISTS (
        SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS 
        WHERE TABLE_SCHEMA = DATABASE() 
          AND TABLE_NAME = 'user_financial_stats' 
          AND COLUMN_NAME = 'last_active_date'
    ) THEN
        ALTER TABLE `user_financial_stats` ADD COLUMN `last_active_date` DATE NULL AFTER `current_streak`;
    END IF;
END $$
DELIMITER ;

CALL `UpgradeUserFinancialStatsV3`();
DROP PROCEDURE `UpgradeUserFinancialStatsV3`;

-- -------------------------------------------------------------------------
-- 3. Ensure achievement_definitions table and seed structured catalog
-- -------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `achievement_definitions` (
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
    `is_chain` TINYINT(1) DEFAULT 0,
    `chain_multiplier` DECIMAL(5,2) DEFAULT 1.00,
    `base_target` DECIMAL(15,2) DEFAULT 0.00,
    `is_active` TINYINT(1) DEFAULT 1,
    INDEX `idx_rule_type` (`rule_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Upsert the 4 Structured Financial Tracks
INSERT INTO `achievement_definitions` 
(`slug`, `name`, `description`, `icon`, `color`, `category`, `rarity`, `xp_value`, `rule_type`, `rule_config`, `is_active`)
VALUES
-- Track 1: Budget Disciplinarian
('budget_disciplinarian_1m', 'Budget Discipline (1 Month)', 'Completed 1 full monthly billing cycle without exceeding any configured category budgets.', 'fa-shield-halved', '#10b981', 'budgets', 'common', 150, 'budget_streak', '{"months": 1}', 1),
('budget_disciplinarian_3m', 'Budget Vanguard (3 Months)', 'Sustained perfect budget discipline for 3 consecutive monthly cycles without overrun.', 'fa-chess-rook', '#3b82f6', 'budgets', 'rare', 500, 'budget_streak', '{"months": 3}', 1),
('budget_disciplinarian_6m', 'Master Disciplinarian (6 Months)', 'Demonstrated elite fiscal governance with 6 consecutive months within budget limits.', 'fa-crown', '#8b5cf6', 'budgets', 'epic', 1200, 'budget_streak', '{"months": 6}', 1),

-- Track 2: Savings Velocity
('savings_velocity_20', 'Capital Builder (20% Rate)', 'Achieved a net-positive monthly savings rate >= 20% of net income.', 'fa-arrow-trend-up', '#10b981', 'savings', 'common', 200, 'savings_rate', '{"min_rate": 20.0}', 1),
('savings_velocity_40', 'Velocity Accumulator (40% Rate)', 'Maintained an aggressive net savings rate >= 40% across a monthly cycle.', 'fa-bolt', '#f59e0b', 'savings', 'rare', 600, 'savings_rate', '{"min_rate": 40.0}', 1),
('savings_velocity_60', 'Sovereign Wealth (60% Rate)', 'Elite frugality and earnings retention: saved >= 60% of total monthly cash flow.', 'fa-gem', '#ec4899', 'savings', 'legendary', 1500, 'savings_rate', '{"min_rate": 60.0}', 1),

-- Track 3: Reconciliation Master
('reconciliation_master_7d', 'Reconciliation Novice (7 Days)', 'Logged or audited financial transactions across 7 distinct calendar days.', 'fa-calendar-check', '#3b82f6', 'streaks', 'common', 100, 'logging_streak', '{"days": 7}', 1),
('reconciliation_master_30d', 'Ledger Guardian (30 Days)', 'Maintained uninterrupted daily financial reconciliation for 30 consecutive calendar days.', 'fa-book-bookmark', '#8b5cf6', 'streaks', 'rare', 450, 'logging_streak', '{"days": 30}', 1),
('reconciliation_master_90d', 'Auditing Virtuoso (90 Days)', 'Logged transactions consistently across 90 distinct calendar days. Immune to time spoofing.', 'fa-certificate', '#f59e0b', 'streaks', 'epic', 1500, 'logging_streak', '{"days": 90}', 1),

-- Track 4: Debt & Liquidity Resilience
('liquidity_resilience_3m', 'Liquidity Fortress (3 Months)', 'Accumulated emergency vault reserves covering >= 3 months of baseline categorical expenses.', 'fa-vault', '#10b981', 'liquidity', 'rare', 800, 'emergency_fund', '{"months_coverage": 3.0}', 1),
('liquidity_resilience_6m', 'Ironclad Solvency (6 Months)', 'Created an unshakeable liquidity buffer covering >= 6 months of living expenses.', 'fa-monument', '#6366f1', 'liquidity', 'legendary', 2000, 'emergency_fund', '{"months_coverage": 6.0}', 1)
ON DUPLICATE KEY UPDATE 
    `name` = VALUES(`name`),
    `description` = VALUES(`description`),
    `xp_value` = VALUES(`xp_value`),
    `rule_config` = VALUES(`rule_config`),
    `is_active` = 1;

-- -------------------------------------------------------------------------
-- 4. Ensure fxp_ledger exists
-- -------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `fxp_ledger` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT UNSIGNED NOT NULL,
    `event_type` VARCHAR(64) NOT NULL,
    `reference_id` VARCHAR(64) NOT NULL,
    `fxp_amount` INT NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY `uniq_user_event_ref` (`user_id`, `event_type`, `reference_id`),
    INDEX `idx_user_fxp` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------------------------------------------------------
-- 5. Ensure idempotency_keys exists (matching schema definitions)
-- -------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `idempotency_keys` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT UNSIGNED NOT NULL,
    `key_hash` VARCHAR(64) NOT NULL,
    `endpoint` VARCHAR(128) NOT NULL,
    `request_hash` VARCHAR(64) NOT NULL,
    `response_code` INT NOT NULL DEFAULT 200,
    `response_body` MEDIUMTEXT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `expires_at` TIMESTAMP NOT NULL,
    UNIQUE KEY `uq_user_key` (`user_id`, `key_hash`),
    INDEX `idx_expires` (`expires_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;
