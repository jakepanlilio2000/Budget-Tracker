-- Bills & Recurring Payments
CREATE TABLE `bills` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT UNSIGNED NOT NULL,
    `name` VARCHAR(100) NOT NULL,
    `total_amount` DECIMAL(15,2) NOT NULL,
    `frequency` ENUM('weekly', 'monthly', 'quarterly', 'yearly', 'custom') DEFAULT 'monthly',
    `next_due_date` DATE NOT NULL,
    `penalty_rate` DECIMAL(10,2) DEFAULT 0.00,
    `penalty_type` ENUM('fixed', 'percentage') DEFAULT 'fixed',
    `status` ENUM('active', 'paused', 'completed') DEFAULT 'active',
    `notes` TEXT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    INDEX `idx_user_due` (`user_id`, `next_due_date`, `status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `bill_payments` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `bill_id` INT UNSIGNED NOT NULL,
    `user_id` INT UNSIGNED NOT NULL,
    `amount_paid` DECIMAL(15,2) NOT NULL,
    `penalty_applied` DECIMAL(15,2) DEFAULT 0.00,
    `payment_date` DATE NOT NULL,
    `account_id` INT UNSIGNED NULL, -- Linked to actual account if paid from system
    `notes` VARCHAR(255) NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`bill_id`) REFERENCES `bills`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`account_id`) REFERENCES `accounts`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Employers (For Salary Tracking)
CREATE TABLE `employers` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT UNSIGNED NOT NULL,
    `company_name` VARCHAR(150) NOT NULL,
    `contact_email` VARCHAR(100) NULL,
    `contact_phone` VARCHAR(50) NULL,
    `address` TEXT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Salaries / Payslips
CREATE TABLE `salaries` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT UNSIGNED NOT NULL,
    `employer_id` INT UNSIGNED NOT NULL,
    `pay_period_start` DATE NOT NULL,
    `pay_period_end` DATE NOT NULL,
    `basic_salary` DECIMAL(15,2) NOT NULL,
    `bonus` DECIMAL(15,2) DEFAULT 0.00,
    `overtime_pay` DECIMAL(15,2) DEFAULT 0.00,
    `allowances` JSON NULL, -- e.g., [{"name": "Transport", "amount": 100}]
    `deductions` JSON NULL, -- e.g., [{"name": "Tax", "amount": 50}]
    `thirteenth_month` DECIMAL(15,2) DEFAULT 0.00,
    `net_pay` DECIMAL(15,2) NOT NULL,
    `payment_date` DATE NOT NULL,
    `status` ENUM('draft', 'paid') DEFAULT 'paid',
    `notes` TEXT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`employer_id`) REFERENCES `employers`(`id`) ON DELETE CASCADE,
    INDEX `idx_user_period` (`user_id`, `pay_period_start`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;