-- =========================================================================
-- EXPENSE TRACKER ENTERPRISE v4.0 FULL HARDENING & BACKUP PATCH
-- Comprehensive, 100% Idempotent Migration Script for MariaDB 10.5+ / MySQL 8.0+
-- Standardizes Arbitrary-Precision DECIMALs, Idempotency Guards, Reconciliation Logs,
-- Export Auditing, Anti-Exploit Gamification Keys, and High-Frequency Query Indexes.
-- =========================================================================

SET FOREIGN_KEY_CHECKS = 0;

-- -------------------------------------------------------------------------
-- 1. Idempotency Keys Table (Replay & Double-Click Protection)
-- -------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `idempotency_keys` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT UNSIGNED NOT NULL,
    `key_hash` CHAR(64) NOT NULL,
    `endpoint` VARCHAR(128) NOT NULL,
    `request_hash` CHAR(64) NOT NULL,
    `response_code` SMALLINT UNSIGNED NOT NULL,
    `response_body` MEDIUMTEXT NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `expires_at` TIMESTAMP NOT NULL,
    UNIQUE KEY `uq_user_key` (`user_id`, `key_hash`),
    INDEX `idx_expires` (`expires_at`),
    CONSTRAINT `fk_idempotency_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------------------------------------------------------
-- 2. Double-Entry Reconciliation Audit Logs Table
-- -------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `reconciliation_audit_logs` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT UNSIGNED NOT NULL,
    `account_id` INT UNSIGNED NOT NULL,
    `stored_balance` DECIMAL(18,2) NOT NULL,
    `ledger_balance` DECIMAL(18,2) NOT NULL,
    `discrepancy` DECIMAL(18,2) NOT NULL,
    `reconciled` TINYINT(1) NOT NULL DEFAULT 0,
    `notes` TEXT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_recon_user_acc` (`user_id`, `account_id`, `created_at` DESC),
    CONSTRAINT `fk_recon_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_recon_acc` FOREIGN KEY (`account_id`) REFERENCES `accounts` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------------------------------------------------------
-- 3. Export Audit Trail Logs Table
-- -------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `export_audit_logs` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT UNSIGNED NOT NULL,
    `export_type` VARCHAR(32) NOT NULL,
    `format` VARCHAR(16) NOT NULL,
    `record_count` INT UNSIGNED NOT NULL DEFAULT 0,
    `filters_applied` JSON NULL,
    `ip_address` VARCHAR(45) NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_export_user` (`user_id`, `created_at` DESC),
    CONSTRAINT `fk_export_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------------------------------------------------------
-- 4. Backup History Table (if missing)
-- -------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `backup_history` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT UNSIGNED NOT NULL,
    `backup_uuid` CHAR(36) NOT NULL UNIQUE,
    `filename` VARCHAR(255) NOT NULL,
    `format` VARCHAR(16) NOT NULL DEFAULT 'json',
    `file_size_bytes` BIGINT UNSIGNED NOT NULL DEFAULT 0,
    `schema_version` VARCHAR(20) NOT NULL,
    `modules_included` JSON NULL,
    `checksum_sha256` CHAR(64) NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `restored_at` TIMESTAMP NULL DEFAULT NULL,
    `restore_status` ENUM('success', 'failed', 'pending') DEFAULT 'pending',
    INDEX `idx_user_backup` (`user_id`, `created_at` DESC),
    CONSTRAINT `fk_backup_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------------------------------------------------------
-- 5. Stored Procedure for Idempotent Schema Upgrades
-- -------------------------------------------------------------------------
DROP PROCEDURE IF EXISTS `ApplyV4HardeningPatch`;
DELIMITER $$
CREATE PROCEDURE `ApplyV4HardeningPatch`()
BEGIN
    -- =========================================================================
    -- A. STANDARD ARBITRARY-PRECISION DECIMAL CONVERSIONS
    -- Pre-clean NULL values before any ALTER TABLE to prevent #1265 truncation errors
    -- =========================================================================
    
    -- accounts
    UPDATE `accounts` SET `opening_balance` = 0.00 WHERE `opening_balance` IS NULL;
    UPDATE `accounts` SET `current_balance` = 0.00 WHERE `current_balance` IS NULL;

    ALTER TABLE `accounts` 
        MODIFY COLUMN `opening_balance` DECIMAL(18,2) NOT NULL DEFAULT 0.00,
        MODIFY COLUMN `current_balance` DECIMAL(18,2) NOT NULL DEFAULT 0.00;

    -- transactions
    UPDATE `transactions` SET `total_amount` = 0.00 WHERE `total_amount` IS NULL;

    -- Expand total_amount and converted_amount precision
    -- NOTE: converted_amount retains NULL DEFAULT NULL so existing NULL records never trigger #1265
    ALTER TABLE `transactions` 
        MODIFY COLUMN `total_amount` DECIMAL(18,2) NOT NULL DEFAULT 0.00,
        MODIFY COLUMN `converted_amount` DECIMAL(18,2) NULL DEFAULT NULL;

    -- Backfill null converted_amount to total_amount
    UPDATE `transactions` 
    SET `converted_amount` = `total_amount` 
    WHERE `converted_amount` IS NULL;

    -- rate_applied column
    IF NOT EXISTS (
        SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'transactions' AND COLUMN_NAME = 'rate_applied'
    ) THEN
        ALTER TABLE `transactions` ADD COLUMN `rate_applied` DECIMAL(18,6) NOT NULL DEFAULT 1.000000 AFTER `converted_amount`;
    ELSE
        UPDATE `transactions` SET `rate_applied` = 1.000000 WHERE `rate_applied` IS NULL;
        ALTER TABLE `transactions` MODIFY COLUMN `rate_applied` DECIMAL(18,6) NOT NULL DEFAULT 1.000000;
    END IF;

    -- settled_amount column
    IF NOT EXISTS (
        SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'transactions' AND COLUMN_NAME = 'settled_amount'
    ) THEN
        ALTER TABLE `transactions` ADD COLUMN `settled_amount` DECIMAL(18,2) NOT NULL DEFAULT 0.00 AFTER `rate_applied`;
    ELSE
        UPDATE `transactions` SET `settled_amount` = 0.00 WHERE `settled_amount` IS NULL;
        ALTER TABLE `transactions` MODIFY COLUMN `settled_amount` DECIMAL(18,2) NOT NULL DEFAULT 0.00;
    END IF;

    -- Backfill settled_amount
    UPDATE `transactions` 
    SET `settled_amount` = COALESCE(`converted_amount`, `total_amount`, 0.00) 
    WHERE `settled_amount` = 0.00 OR `settled_amount` IS NULL;

    -- client_mutation_id column
    IF NOT EXISTS (
        SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'transactions' AND COLUMN_NAME = 'client_mutation_id'
    ) THEN
        ALTER TABLE `transactions` ADD COLUMN `client_mutation_id` VARCHAR(64) NULL AFTER `notes`;
    END IF;

    -- transaction_splits
    UPDATE `transaction_splits` SET `amount` = 0.00 WHERE `amount` IS NULL;
    ALTER TABLE `transaction_splits` MODIFY COLUMN `amount` DECIMAL(18,2) NOT NULL DEFAULT 0.00;

    -- budgets
    UPDATE `budgets` SET `amount` = 0.00 WHERE `amount` IS NULL;
    ALTER TABLE `budgets` MODIFY COLUMN `amount` DECIMAL(18,2) NOT NULL DEFAULT 0.00;

    -- bills & bill_payments
    UPDATE `bills` SET `total_amount` = 0.00 WHERE `total_amount` IS NULL;
    ALTER TABLE `bills` MODIFY COLUMN `total_amount` DECIMAL(18,2) NOT NULL DEFAULT 0.00;

    UPDATE `bill_payments` SET `amount_paid` = 0.00 WHERE `amount_paid` IS NULL;
    ALTER TABLE `bill_payments` MODIFY COLUMN `amount_paid` DECIMAL(18,2) NOT NULL DEFAULT 0.00;

    UPDATE `bill_payments` SET `penalty_applied` = 0.00 WHERE `penalty_applied` IS NULL;
    ALTER TABLE `bill_payments` MODIFY COLUMN `penalty_applied` DECIMAL(18,2) NOT NULL DEFAULT 0.00;

    -- salaries
    UPDATE `salaries` SET `basic_salary` = 0.00 WHERE `basic_salary` IS NULL;
    UPDATE `salaries` SET `bonus` = 0.00 WHERE `bonus` IS NULL;
    UPDATE `salaries` SET `overtime_pay` = 0.00 WHERE `overtime_pay` IS NULL;
    UPDATE `salaries` SET `thirteenth_month` = 0.00 WHERE `thirteenth_month` IS NULL;
    UPDATE `salaries` SET `net_pay` = 0.00 WHERE `net_pay` IS NULL;

    ALTER TABLE `salaries` 
        MODIFY COLUMN `basic_salary` DECIMAL(18,2) NOT NULL DEFAULT 0.00,
        MODIFY COLUMN `bonus` DECIMAL(18,2) NOT NULL DEFAULT 0.00,
        MODIFY COLUMN `overtime_pay` DECIMAL(18,2) NOT NULL DEFAULT 0.00,
        MODIFY COLUMN `thirteenth_month` DECIMAL(18,2) NOT NULL DEFAULT 0.00,
        MODIFY COLUMN `net_pay` DECIMAL(18,2) NOT NULL DEFAULT 0.00;

    -- savings_vaults & vault_transactions
    UPDATE `savings_vaults` SET `target_amount` = 0.00 WHERE `target_amount` IS NULL;
    UPDATE `savings_vaults` SET `current_amount` = 0.00 WHERE `current_amount` IS NULL;

    ALTER TABLE `savings_vaults` 
        MODIFY COLUMN `target_amount` DECIMAL(18,2) NOT NULL DEFAULT 0.00,
        MODIFY COLUMN `current_amount` DECIMAL(18,2) NOT NULL DEFAULT 0.00;

    UPDATE `vault_transactions` SET `amount` = 0.00 WHERE `amount` IS NULL;
    ALTER TABLE `vault_transactions` MODIFY COLUMN `amount` DECIMAL(18,2) NOT NULL DEFAULT 0.00;

    -- recurring_incomes
    IF EXISTS (SELECT 1 FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'recurring_incomes') THEN
        UPDATE `recurring_incomes` SET `amount` = 0.00 WHERE `amount` IS NULL;
        ALTER TABLE `recurring_incomes` MODIFY COLUMN `amount` DECIMAL(18,2) NOT NULL DEFAULT 0.00;
    END IF;

    -- pending_ledger
    IF EXISTS (SELECT 1 FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'pending_ledger') THEN
        UPDATE `pending_ledger` SET `amount` = 0.00 WHERE `amount` IS NULL;
        ALTER TABLE `pending_ledger` MODIFY COLUMN `amount` DECIMAL(18,2) NOT NULL DEFAULT 0.00;
    END IF;

    -- =========================================================================
    -- B. BACKUP_HISTORY METADATA COLUMNS
    -- =========================================================================
    IF NOT EXISTS (
        SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'backup_history' AND COLUMN_NAME = 'is_encrypted'
    ) THEN
        ALTER TABLE `backup_history` ADD COLUMN `is_encrypted` TINYINT(1) NOT NULL DEFAULT 0 AFTER `file_size_bytes`;
    END IF;

    IF NOT EXISTS (
        SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'backup_history' AND COLUMN_NAME = 'encryption_algorithm'
    ) THEN
        ALTER TABLE `backup_history` ADD COLUMN `encryption_algorithm` VARCHAR(32) NULL AFTER `is_encrypted`;
    END IF;

    IF NOT EXISTS (
        SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'backup_history' AND COLUMN_NAME = 'record_count'
    ) THEN
        ALTER TABLE `backup_history` ADD COLUMN `record_count` INT UNSIGNED NOT NULL DEFAULT 0 AFTER `file_size_bytes`;
    END IF;

    -- =========================================================================
    -- C. USER_ACHIEVEMENTS SCHEMA & COMPOSITE KEY PROTECTION
    -- =========================================================================
    IF NOT EXISTS (
        SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'user_achievements' AND COLUMN_NAME = 'achievement_key'
    ) THEN
        ALTER TABLE `user_achievements` ADD COLUMN `achievement_key` VARCHAR(64) NOT NULL DEFAULT '' AFTER `achievement_id`;
    END IF;

    IF NOT EXISTS (
        SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'user_achievements' AND COLUMN_NAME = 'source_reference_id'
    ) THEN
        ALTER TABLE `user_achievements` ADD COLUMN `source_reference_id` VARCHAR(64) NULL AFTER `unlocked_at`;
    END IF;

    IF NOT EXISTS (
        SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'user_achievements' AND COLUMN_NAME = 'fxp_awarded'
    ) THEN
        ALTER TABLE `user_achievements` ADD COLUMN `fxp_awarded` INT NOT NULL DEFAULT 0 AFTER `source_reference_id`;
    END IF;

    IF NOT EXISTS (
        SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'user_achievements' AND COLUMN_NAME = 'is_revoked'
    ) THEN
        ALTER TABLE `user_achievements` ADD COLUMN `is_revoked` TINYINT(1) NOT NULL DEFAULT 0 AFTER `fxp_awarded`;
    END IF;

    IF NOT EXISTS (
        SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'user_achievements' AND COLUMN_NAME = 'revoked_at'
    ) THEN
        ALTER TABLE `user_achievements` ADD COLUMN `revoked_at` TIMESTAMP NULL AFTER `is_revoked`;
    END IF;

    -- Backfill achievement_key from achievement_definitions.slug
    UPDATE `user_achievements` ua
    JOIN `achievement_definitions` ad ON ua.achievement_id = ad.id
    SET ua.achievement_key = ad.slug
    WHERE ua.achievement_key = '' OR ua.achievement_key IS NULL;

    -- Fallback for any records whose achievement_id does not exist in achievement_definitions
    UPDATE `user_achievements`
    SET `achievement_key` = CONCAT('legacy_ach_', `achievement_id`)
    WHERE `achievement_key` = '' OR `achievement_key` IS NULL;

    -- Strict, deterministic deduplication before creating unique index
    DELETE t1 FROM `user_achievements` t1
    INNER JOIN `user_achievements` t2 
      ON t1.user_id = t2.user_id 
      AND t1.achievement_key = t2.achievement_key
    WHERE (
        (t1.unlocked_at IS NULL AND t2.unlocked_at IS NOT NULL)
        OR (
            ((t1.unlocked_at IS NOT NULL AND t2.unlocked_at IS NOT NULL) OR (t1.unlocked_at IS NULL AND t2.unlocked_at IS NULL))
            AND t1.achievement_id < t2.achievement_id
        )
    );

    -- Unique composite index
    IF NOT EXISTS (
        SELECT 1 FROM INFORMATION_SCHEMA.STATISTICS
        WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'user_achievements' AND INDEX_NAME = 'uniq_user_achievement_key'
    ) THEN
        ALTER TABLE `user_achievements` ADD UNIQUE INDEX `uniq_user_achievement_key` (`user_id`, `achievement_key`);
    END IF;

    -- =========================================================================
    -- D. QUERY PERFORMANCE INDEXES
    -- =========================================================================
    IF NOT EXISTS (
        SELECT 1 FROM INFORMATION_SCHEMA.STATISTICS
        WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'transactions' AND INDEX_NAME = 'idx_tx_user_date'
    ) THEN
        ALTER TABLE `transactions` ADD INDEX `idx_tx_user_date` (`user_id`, `transaction_date` DESC);
    END IF;

    IF NOT EXISTS (
        SELECT 1 FROM INFORMATION_SCHEMA.STATISTICS
        WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'transactions' AND INDEX_NAME = 'idx_tx_user_account'
    ) THEN
        ALTER TABLE `transactions` ADD INDEX `idx_tx_user_account` (`user_id`, `account_id`, `status`);
    END IF;

    IF NOT EXISTS (
        SELECT 1 FROM INFORMATION_SCHEMA.STATISTICS
        WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'transactions' AND INDEX_NAME = 'idx_tx_user_category'
    ) THEN
        ALTER TABLE `transactions` ADD INDEX `idx_tx_user_category` (`user_id`, `category_id`);
    END IF;

    IF NOT EXISTS (
        SELECT 1 FROM INFORMATION_SCHEMA.STATISTICS
        WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'accounts' AND INDEX_NAME = 'idx_acc_user_status'
    ) THEN
        ALTER TABLE `accounts` ADD INDEX `idx_acc_user_status` (`user_id`, `status`, `deleted_at`);
    END IF;

    IF NOT EXISTS (
        SELECT 1 FROM INFORMATION_SCHEMA.STATISTICS
        WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'budgets' AND INDEX_NAME = 'idx_budget_user_month'
    ) THEN
        ALTER TABLE `budgets` ADD INDEX `idx_budget_user_month` (`user_id`, `month`, `category_id`);
    END IF;

END $$
DELIMITER ;

CALL `ApplyV4HardeningPatch`();
DROP PROCEDURE IF EXISTS `ApplyV4HardeningPatch`;

SET FOREIGN_KEY_CHECKS = 1;
