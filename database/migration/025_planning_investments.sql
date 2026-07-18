CREATE TABLE `planning_investments` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `scenario_id` INT UNSIGNED NULL,
    `user_id` INT UNSIGNED NOT NULL,
    `name` VARCHAR(100) NOT NULL,
    `asset_type` ENUM('stocks', 'etf', 'mutual_fund', 'bonds', 'crypto', 'savings', 'time_deposit', 'real_estate', 'custom') DEFAULT 'stocks',
    `initial_investment` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    `monthly_contribution` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    `annual_return_rate` DECIMAL(5,2) NOT NULL, -- e.g., 8.0 for 8%
    `annual_fee_rate` DECIMAL(5,2) DEFAULT 0.00, -- e.g., 0.5 for 0.5%
    `term_months` INT UNSIGNED NOT NULL DEFAULT 120,
    `risk_level` ENUM('low', 'medium', 'high', 'speculative') DEFAULT 'medium',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`scenario_id`) REFERENCES `planning_scenarios`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;