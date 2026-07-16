-- 1. User Streaks Tracking
CREATE TABLE `user_streaks` (
    `user_id` INT UNSIGNED NOT NULL,
    `streak_type` VARCHAR(50) NOT NULL, -- e.g., 'daily_login', 'daily_transaction', 'consecutive_savings'
    `current_streak` INT UNSIGNED DEFAULT 0,
    `best_streak` INT UNSIGNED DEFAULT 0,
    `last_action_date` DATE DEFAULT NULL,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`user_id`, `streak_type`),
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Prestige History Log
CREATE TABLE `user_prestige_history` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT UNSIGNED NOT NULL,
    `prestige_number` INT UNSIGNED NOT NULL, -- 1st prestige, 2nd prestige, etc.
    `level_at_prestige` INT UNSIGNED NOT NULL,
    `fxp_at_prestige` INT UNSIGNED NOT NULL,
    `prestige_date` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Ensure user_fxp_stats has the required prestige columns (if not already added in Phase 1)
ALTER TABLE `user_fxp_stats` 
ADD COLUMN IF NOT EXISTS `prestige_stars` INT UNSIGNED DEFAULT 0,
ADD COLUMN IF NOT EXISTS `xp_multiplier` DECIMAL(4,2) DEFAULT 1.00,
ADD COLUMN IF NOT EXISTS `current_title` VARCHAR(100) DEFAULT 'Beginner Saver';

INSERT IGNORE INTO `fxp_actions` (`action_type`, `description`, `xp_value`, `mastery_type`) VALUES
('create_account', 'Create a new financial account', 15, 'planning'),
('create_pending', 'Add an item to the pending ledger', 5, 'planning'),
('pending_paid', 'Clear a pending ledger item', 10, 'consistency'),
('create_bill', 'Create a new recurring bill', 10, 'planning'),
('pay_bill', 'Pay a bill on time', 15, 'consistency'),
('create_daily_log', 'Log daily spending', 10, 'consistency'),
('receive_salary', 'Record a salary/payslip', 25, 'income'),
('create_vault', 'Create a new savings vault', 15, 'savings');