-- 1. Add base currency preference to users
ALTER TABLE `users` ADD COLUMN `base_currency_id` INT UNSIGNED NULL AFTER `role`;
ALTER TABLE `users` ADD CONSTRAINT `fk_user_base_currency` FOREIGN KEY (`base_currency_id`) REFERENCES `currencies`(`id`) ON DELETE SET NULL;

-- 2. Pending Ledger (Scheduled transactions, bills due, pending income)
CREATE TABLE `pending_ledger` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT UNSIGNED NOT NULL,
    `type` ENUM('income', 'expense') NOT NULL,
    `description` VARCHAR(255) NOT NULL,
    `amount` DECIMAL(15,2) NOT NULL,
    `currency_id` INT UNSIGNED NOT NULL,
    `due_date` DATE NOT NULL,
    `priority` ENUM('low', 'medium', 'high', 'critical') DEFAULT 'medium',
    `status` ENUM('pending', 'paid', 'cancelled') DEFAULT 'pending',
    `notes` TEXT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`currency_id`) REFERENCES `currencies`(`id`) ON DELETE RESTRICT,
    INDEX `idx_user_due` (`user_id`, `due_date`, `status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Enhance Budgets for Envelope & Carry-over
ALTER TABLE `budgets` 
    ADD COLUMN `period` ENUM('monthly', 'yearly', 'custom') DEFAULT 'monthly' AFTER `amount`,
    ADD COLUMN `start_date` DATE NULL AFTER `period`,
    ADD COLUMN `end_date` DATE NULL AFTER `start_date`,
    ADD COLUMN `carry_over` TINYINT(1) DEFAULT 0 AFTER `end_date`,
    ADD COLUMN `rolled_over_amount` DECIMAL(15,2) DEFAULT 0.00 AFTER `carry_over`;

-- 4. Daily Expenditure Log (Journal)
CREATE TABLE `daily_logs` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT UNSIGNED NOT NULL,
    `log_date` DATE NOT NULL,
    `total_spent` DECIMAL(15,2) DEFAULT 0.00,
    `mood_context` VARCHAR(50) NULL, -- e.g., 'stressful', 'productive', 'neutral'
    `notes` TEXT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY `unique_user_date` (`user_id`, `log_date`),
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    INDEX `idx_user_date` (`user_id`, `log_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;