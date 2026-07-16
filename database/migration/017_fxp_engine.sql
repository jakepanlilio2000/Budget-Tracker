-- 1. Configurable FXP Actions (Admins can tweak these without code changes)
CREATE TABLE `fxp_actions` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `action_type` VARCHAR(50) NOT NULL UNIQUE,
    `description` VARCHAR(100) NOT NULL,
    `xp_value` INT NOT NULL DEFAULT 10,
    `mastery_type` VARCHAR(50) NOT NULL DEFAULT 'general', -- e.g., 'expense', 'savings', 'income', 'budget', 'consistency'
    `is_active` TINYINT(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. User Global FXP Stats
CREATE TABLE `user_fxp_stats` (
    `user_id` INT UNSIGNED PRIMARY KEY,
    `lifetime_fxp` INT UNSIGNED DEFAULT 0,
    `current_level` INT UNSIGNED DEFAULT 1,
    `prestige_stars` INT UNSIGNED DEFAULT 0,
    `xp_multiplier` DECIMAL(4,2) DEFAULT 1.00,
    `current_title` VARCHAR(100) DEFAULT 'Beginner Saver',
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. User Mastery Tracks (Independent leveling per category)
CREATE TABLE `user_mastery_stats` (
    `user_id` INT UNSIGNED NOT NULL,
    `mastery_type` VARCHAR(50) NOT NULL, -- 'expense', 'income', 'savings', 'budget', 'consistency', 'planning'
    `level` INT UNSIGNED DEFAULT 1,
    `current_xp` INT UNSIGNED DEFAULT 0,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`user_id`, `mastery_type`),
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Seed Default FXP Actions
INSERT INTO `fxp_actions` (`action_type`, `description`, `xp_value`, `mastery_type`) VALUES
('record_expense', 'Record an expense transaction', 5, 'expense'),
('record_income', 'Record an income transaction', 5, 'income'),
('receive_salary', 'Record a salary/payslip', 25, 'income'),
('pay_bill', 'Pay a bill on time', 15, 'consistency'),
('create_budget', 'Create a new budget', 10, 'budget'),
('deposit_vault', 'Deposit into a savings vault', 15, 'savings'),
('complete_vault', 'Complete a savings goal', 50, 'savings'),
('daily_login', 'Daily application login', 2, 'consistency'),
('create_backup', 'Create a system backup', 20, 'planning'),
('use_forecast', 'Run a cash flow forecast', 10, 'planning');