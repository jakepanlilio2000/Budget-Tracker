CREATE TABLE `planning_loans` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `scenario_id` INT UNSIGNED NULL, -- NULL means it's a global sandbox loan
    `user_id` INT UNSIGNED NOT NULL,
    `name` VARCHAR(100) NOT NULL,
    `principal` DECIMAL(15,2) NOT NULL,
    `annual_interest_rate` DECIMAL(5,2) NOT NULL,
    `term_months` INT UNSIGNED NOT NULL,
    `start_date` DATE NOT NULL,
    `extra_monthly_payment` DECIMAL(15,2) DEFAULT 0.00,
    `loan_type` ENUM('fixed', 'variable') DEFAULT 'fixed',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`scenario_id`) REFERENCES `planning_scenarios`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;