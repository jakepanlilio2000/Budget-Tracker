-- ==============================================================================
-- Enterprise Budget & Expense Tracker: Core Architectural Hardening Migration
-- Target Schema Patch: schema_patch.sql
-- Idempotent, Safe for Execution on MySQL 8.x / MariaDB 10.x
-- ==============================================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ------------------------------------------------------------------------------
-- Helper Procedure for Idempotent Column Additions & Modifications
-- ------------------------------------------------------------------------------
DELIMITER //

DROP PROCEDURE IF EXISTS `AddColumnIfNotExists` //
CREATE PROCEDURE `AddColumnIfNotExists`(
    IN tableName VARCHAR(64),
    IN columnName VARCHAR(64),
    IN columnDefinition VARCHAR(255)
)
BEGIN
    DECLARE colCount INT;
    SELECT COUNT(*) INTO colCount
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = tableName
      AND COLUMN_NAME = columnName;

    IF colCount = 0 THEN
        SET @sql = CONCAT('ALTER TABLE `', tableName, '` ADD COLUMN `', columnName, '` ', columnDefinition);
        PREPARE stmt FROM @sql;
        EXECUTE stmt;
        DEALLOCATE PREPARE stmt;
    END IF;
END //

DROP PROCEDURE IF EXISTS `ModifyColumnIfExists` //
CREATE PROCEDURE `ModifyColumnIfExists`(
    IN tableName VARCHAR(64),
    IN columnName VARCHAR(64),
    IN columnDefinition VARCHAR(255)
)
BEGIN
    DECLARE colCount INT;
    SELECT COUNT(*) INTO colCount
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = tableName
      AND COLUMN_NAME = columnName;

    IF colCount > 0 THEN
        SET @sql = CONCAT('ALTER TABLE `', tableName, '` MODIFY COLUMN `', columnName, '` ', columnDefinition);
        PREPARE stmt FROM @sql;
        EXECUTE stmt;
        DEALLOCATE PREPARE stmt;
    END IF;
END //

DROP PROCEDURE IF EXISTS `AddIndexIfNotExists` //
CREATE PROCEDURE `AddIndexIfNotExists`(
    IN tableName VARCHAR(64),
    IN indexName VARCHAR(64),
    IN indexDefinition VARCHAR(255)
)
BEGIN
    DECLARE idxCount INT;
    SELECT COUNT(*) INTO idxCount
    FROM information_schema.STATISTICS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = tableName
      AND INDEX_NAME = indexName;

    IF idxCount = 0 THEN
        SET @sql = CONCAT('ALTER TABLE `', tableName, '` ADD INDEX `', indexName, '` ', indexDefinition);
        PREPARE stmt FROM @sql;
        EXECUTE stmt;
        DEALLOCATE PREPARE stmt;
    END IF;
END //

DROP PROCEDURE IF EXISTS `AddUniqueIndexIfNotExists` //
CREATE PROCEDURE `AddUniqueIndexIfNotExists`(
    IN tableName VARCHAR(64),
    IN indexName VARCHAR(64),
    IN indexDefinition VARCHAR(255)
)
BEGIN
    DECLARE idxCount INT;
    SELECT COUNT(*) INTO idxCount
    FROM information_schema.STATISTICS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = tableName
      AND INDEX_NAME = indexName;

    IF idxCount = 0 THEN
        SET @sql = CONCAT('ALTER TABLE `', tableName, '` ADD UNIQUE KEY `', indexName, '` ', indexDefinition);
        PREPARE stmt FROM @sql;
        EXECUTE stmt;
        DEALLOCATE PREPARE stmt;
    END IF;
END //

DROP PROCEDURE IF EXISTS `AddForeignKeyIfNotExists` //
CREATE PROCEDURE `AddForeignKeyIfNotExists`(
    IN tableName VARCHAR(64),
    IN fkName VARCHAR(64),
    IN fkDefinition VARCHAR(255)
)
BEGIN
    DECLARE fkCount INT;
    SELECT COUNT(*) INTO fkCount
    FROM information_schema.TABLE_CONSTRAINTS
    WHERE CONSTRAINT_SCHEMA = DATABASE()
      AND TABLE_NAME = tableName
      AND CONSTRAINT_NAME = fkName
      AND CONSTRAINT_TYPE = 'FOREIGN KEY';

    IF fkCount = 0 THEN
        SET @sql = CONCAT('ALTER TABLE `', tableName, '` ADD CONSTRAINT `', fkName, '` FOREIGN KEY ', fkDefinition);
        PREPARE stmt FROM @sql;
        EXECUTE stmt;
        DEALLOCATE PREPARE stmt;
    END IF;
END //

DELIMITER ;

-- ==============================================================================
-- 1. Precision & Decimal Hardening across Core Financial Entities
-- ==============================================================================

-- A. Accounts Table: Standard Scale 2, Overdraft Flag
CALL ModifyColumnIfExists('accounts', 'opening_balance', 'DECIMAL(18,2) NOT NULL DEFAULT 0.00');
CALL ModifyColumnIfExists('accounts', 'current_balance', 'DECIMAL(18,2) NOT NULL DEFAULT 0.00');
CALL AddColumnIfNotExists('accounts', 'allow_overdraft', 'TINYINT(1) NOT NULL DEFAULT 0 AFTER `current_balance`');
CALL AddIndexIfNotExists('accounts', 'idx_user_deleted', '(`user_id`, `deleted_at`)');

-- B. Currencies Table: Rate Scale 6
CALL ModifyColumnIfExists('currencies', 'exchange_rate', 'DECIMAL(18,6) NOT NULL DEFAULT 1.000000');

-- C. Transactions Table: Rate Snapshotting, Settled Amounts, Idempotency
CALL ModifyColumnIfExists('transactions', 'total_amount', 'DECIMAL(18,2) NOT NULL DEFAULT 0.00');
CALL AddColumnIfNotExists('transactions', 'client_mutation_id', 'VARCHAR(64) NULL AFTER `notes`');
CALL AddColumnIfNotExists('transactions', 'rate_applied', 'DECIMAL(18,6) NOT NULL DEFAULT 1.000000 AFTER `currency_id`');
CALL AddColumnIfNotExists('transactions', 'original_currency_id', 'INT UNSIGNED NULL AFTER `rate_applied`');
CALL AddColumnIfNotExists('transactions', 'base_currency_id', 'INT UNSIGNED NULL AFTER `original_currency_id`');
CALL AddColumnIfNotExists('transactions', 'settled_amount', 'DECIMAL(18,2) NOT NULL DEFAULT 0.00 AFTER `base_currency_id`');

-- Backfill transactions rate snapshots where currently null/zero
UPDATE `transactions` 
SET `settled_amount` = `total_amount` 
WHERE `settled_amount` IS NULL OR `settled_amount` = 0.00;

UPDATE `transactions` 
SET `original_currency_id` = `currency_id` 
WHERE `original_currency_id` IS NULL;

UPDATE `transactions` 
SET `rate_applied` = 1.000000 
WHERE `rate_applied` IS NULL OR `rate_applied` = 0.000000;

-- Optimal Compound Indexes on Transactions
CALL AddIndexIfNotExists('transactions', 'idx_user_account_date', '(`user_id`, `account_id`, `transaction_date`)');
CALL AddIndexIfNotExists('transactions', 'idx_user_status_date', '(`user_id`, `status`, `transaction_date`)');
CALL AddUniqueIndexIfNotExists('transactions', 'uq_user_mutation', '(`user_id`, `client_mutation_id`)');

-- D. Transaction Splits Table
CALL ModifyColumnIfExists('transaction_splits', 'amount', 'DECIMAL(18,2) NOT NULL DEFAULT 0.00');
CALL AddIndexIfNotExists('transaction_splits', 'idx_split_cat', '(`category_id`)');
CALL AddIndexIfNotExists('transaction_splits', 'idx_split_txn', '(`transaction_id`)');

-- E. Budgets Table: Multi-Currency & Precision
CALL ModifyColumnIfExists('budgets', 'amount', 'DECIMAL(18,2) NOT NULL DEFAULT 0.00');
CALL AddColumnIfNotExists('budgets', 'rolled_over_amount', 'DECIMAL(18,2) NOT NULL DEFAULT 0.00 AFTER `amount`');
CALL AddColumnIfNotExists('budgets', 'currency_id', 'INT UNSIGNED NOT NULL DEFAULT 1 AFTER `rolled_over_amount`');
CALL AddIndexIfNotExists('budgets', 'idx_user_month_cat', '(`user_id`, `month`, `category_id`)');
CALL AddForeignKeyIfNotExists('budgets', 'fk_budget_currency', '(`currency_id`) REFERENCES `currencies`(`id`) ON DELETE RESTRICT');

-- F. Bills & Bill Payments Table
CALL ModifyColumnIfExists('bills', 'total_amount', 'DECIMAL(18,2) NOT NULL DEFAULT 0.00');
CALL ModifyColumnIfExists('bills', 'penalty_rate', 'DECIMAL(10,4) NOT NULL DEFAULT 0.0000');
CALL AddIndexIfNotExists('bills', 'idx_user_status_due', '(`user_id`, `status`, `next_due_date`)');

CALL ModifyColumnIfExists('bill_payments', 'amount_paid', 'DECIMAL(18,2) NOT NULL DEFAULT 0.00');
CALL ModifyColumnIfExists('bill_payments', 'penalty_applied', 'DECIMAL(18,2) NOT NULL DEFAULT 0.00');
CALL AddIndexIfNotExists('bill_payments', 'idx_user_bill', '(`user_id`, `bill_id`)');

-- G. Salaries Table
CALL ModifyColumnIfExists('salaries', 'basic_salary', 'DECIMAL(18,2) NOT NULL DEFAULT 0.00');
CALL ModifyColumnIfExists('salaries', 'bonus', 'DECIMAL(18,2) NOT NULL DEFAULT 0.00');
CALL ModifyColumnIfExists('salaries', 'overtime_pay', 'DECIMAL(18,2) NOT NULL DEFAULT 0.00');
CALL ModifyColumnIfExists('salaries', 'thirteenth_month', 'DECIMAL(18,2) NOT NULL DEFAULT 0.00');
CALL ModifyColumnIfExists('salaries', 'net_pay', 'DECIMAL(18,2) NOT NULL DEFAULT 0.00');
CALL AddColumnIfNotExists('salaries', 'currency_id', 'INT UNSIGNED NOT NULL DEFAULT 1 AFTER `employer_id`');
CALL AddForeignKeyIfNotExists('salaries', 'fk_salary_currency', '(`currency_id`) REFERENCES `currencies`(`id`) ON DELETE RESTRICT');

-- H. Daily Logs
CALL ModifyColumnIfExists('daily_logs', 'total_spent', 'DECIMAL(18,2) NOT NULL DEFAULT 0.00');

-- I. Savings Vaults & Transactions
CALL ModifyColumnIfExists('savings_vaults', 'target_amount', 'DECIMAL(18,2) NOT NULL DEFAULT 0.00');
CALL ModifyColumnIfExists('savings_vaults', 'current_amount', 'DECIMAL(18,2) NOT NULL DEFAULT 0.00');
CALL AddColumnIfNotExists('savings_vaults', 'currency_id', 'INT UNSIGNED NOT NULL DEFAULT 1 AFTER `target_amount`');
CALL AddIndexIfNotExists('savings_vaults', 'idx_user_status', '(`user_id`, `status`)');
CALL AddForeignKeyIfNotExists('savings_vaults', 'fk_vault_currency', '(`currency_id`) REFERENCES `currencies`(`id`) ON DELETE RESTRICT');

CALL ModifyColumnIfExists('vault_transactions', 'amount', 'DECIMAL(18,2) NOT NULL DEFAULT 0.00');
CALL AddIndexIfNotExists('vault_transactions', 'idx_vault_user_date', '(`vault_id`, `user_id`, `created_at`)');

-- J. Planning Loans & Investments
CALL ModifyColumnIfExists('planning_loans', 'principal', 'DECIMAL(18,2) NOT NULL DEFAULT 0.00');
CALL ModifyColumnIfExists('planning_loans', 'annual_interest_rate', 'DECIMAL(8,4) NOT NULL DEFAULT 0.0000');
CALL ModifyColumnIfExists('planning_loans', 'extra_monthly_payment', 'DECIMAL(18,2) NOT NULL DEFAULT 0.00');

CALL ModifyColumnIfExists('planning_investments', 'initial_investment', 'DECIMAL(18,2) NOT NULL DEFAULT 0.00');
CALL ModifyColumnIfExists('planning_investments', 'monthly_contribution', 'DECIMAL(18,2) NOT NULL DEFAULT 0.00');
CALL ModifyColumnIfExists('planning_investments', 'annual_return_rate', 'DECIMAL(8,4) NOT NULL DEFAULT 0.0000');
CALL ModifyColumnIfExists('planning_investments', 'annual_fee_rate', 'DECIMAL(8,4) NOT NULL DEFAULT 0.0000');

-- ==============================================================================
-- 2. Infrastructure Tables: Idempotency, Gamification Audit, Balance Reconciliation
-- ==============================================================================

-- A. Idempotency Mutation Store
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
    INDEX `idx_expires` (`expires_at`),
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- B. Event-Driven Gamification Audit Ledger
CREATE TABLE IF NOT EXISTS `gamification_events` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT UNSIGNED NOT NULL,
    `event_type` VARCHAR(50) NOT NULL,
    `reference_id` VARCHAR(64) NOT NULL,
    `xp_awarded` INT NOT NULL DEFAULT 0,
    `is_reversed` TINYINT(1) NOT NULL DEFAULT 0,
    `reversed_at` TIMESTAMP NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY `uq_user_event_ref` (`user_id`, `event_type`, `reference_id`),
    INDEX `idx_user_reversed` (`user_id`, `is_reversed`),
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- C. Balance Reconciliation Audit Trail
CREATE TABLE IF NOT EXISTS `reconciliation_audit_logs` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT UNSIGNED NOT NULL,
    `account_id` INT UNSIGNED NOT NULL,
    `stored_balance` DECIMAL(18,2) NOT NULL,
    `ledger_balance` DECIMAL(18,2) NOT NULL,
    `discrepancy` DECIMAL(18,2) NOT NULL,
    `reconciled` TINYINT(1) NOT NULL DEFAULT 0,
    `notes` VARCHAR(255) NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_acc_reconcile` (`account_id`, `created_at`),
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`account_id`) REFERENCES `accounts`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------------------------
-- Clean up helper stored procedures
-- ------------------------------------------------------------------------------
DROP PROCEDURE IF EXISTS `AddColumnIfNotExists`;
DROP PROCEDURE IF EXISTS `ModifyColumnIfExists`;
DROP PROCEDURE IF EXISTS `AddIndexIfNotExists`;
DROP PROCEDURE IF EXISTS `AddUniqueIndexIfNotExists`;
DROP PROCEDURE IF EXISTS `AddForeignKeyIfNotExists`;

SET FOREIGN_KEY_CHECKS = 1;

-- ==============================================================================
-- End of schema_patch.sql
-- ==============================================================================
