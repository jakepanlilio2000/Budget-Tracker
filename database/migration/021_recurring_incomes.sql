CREATE TABLE `recurring_incomes` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT UNSIGNED NOT NULL,
    `name` VARCHAR(100) NOT NULL,
    `amount` DECIMAL(15,2) NOT NULL,
    `currency_id` INT UNSIGNED NOT NULL,
    `account_id` INT UNSIGNED NOT NULL,
    `category_id` INT UNSIGNED DEFAULT NULL,
    `frequency` ENUM('daily', 'weekly', 'bi-weekly', 'monthly', 'quarterly', 'yearly', 'custom') NOT NULL DEFAULT 'monthly',
    `custom_interval_days` INT UNSIGNED DEFAULT NULL, -- Used when frequency = 'custom'
    `start_date` DATE NOT NULL,
    `end_date` DATE DEFAULT NULL, -- NULL means 'Never Ends'
    `next_post_date` DATE NOT NULL,
    `last_posted_date` DATE DEFAULT NULL,
    `status` ENUM('active', 'paused', 'completed') NOT NULL DEFAULT 'active',
    `total_posted_count` INT UNSIGNED DEFAULT 0,
    `notes` TEXT,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`currency_id`) REFERENCES `currencies`(`id`),
    FOREIGN KEY (`account_id`) REFERENCES `accounts`(`id`),
    FOREIGN KEY (`category_id`) REFERENCES `categories`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;