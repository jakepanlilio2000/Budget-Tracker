-- Categories Table (Enhanced)
CREATE TABLE IF NOT EXISTS `categories` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT UNSIGNED NOT NULL,
    `parent_id` INT UNSIGNED NULL,
    `name` VARCHAR(100) NOT NULL,
    `type` ENUM('income', 'expense', 'transfer') NOT NULL,
    `color` VARCHAR(7) DEFAULT '#3b82f6',
    `icon` VARCHAR(50) DEFAULT 'fa-tag',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `deleted_at` TIMESTAMP NULL,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`parent_id`) REFERENCES `categories`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Transactions Table (Parent)
CREATE TABLE IF NOT EXISTS `transactions` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT UNSIGNED NOT NULL,
    `account_id` INT UNSIGNED NOT NULL,
    `type` ENUM('income', 'expense', 'transfer') NOT NULL,
    `total_amount` DECIMAL(15,2) NOT NULL,
    `currency_id` INT UNSIGNED NOT NULL,
    `transaction_date` DATE NOT NULL,
    `status` ENUM('draft', 'posted', 'archived') DEFAULT 'posted',
    `description` VARCHAR(255) NULL,
    `notes` TEXT NULL,
    `is_favorite` TINYINT(1) DEFAULT 0,
    `is_recurring` TINYINT(1) DEFAULT 0,
    `recurring_rule` VARCHAR(50) NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `deleted_at` TIMESTAMP NULL,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`account_id`) REFERENCES `accounts`(`id`) ON DELETE RESTRICT,
    FOREIGN KEY (`currency_id`) REFERENCES `currencies`(`id`) ON DELETE RESTRICT,
    INDEX `idx_user_date` (`user_id`, `transaction_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Transaction Splits Table (Child)
CREATE TABLE IF NOT EXISTS `transaction_splits` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `transaction_id` INT UNSIGNED NOT NULL,
    `category_id` INT UNSIGNED NOT NULL,
    `amount` DECIMAL(15,2) NOT NULL,
    `notes` VARCHAR(255) NULL,
    FOREIGN KEY (`transaction_id`) REFERENCES `transactions`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`category_id`) REFERENCES `categories`(`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tags Table
CREATE TABLE IF NOT EXISTS `tags` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT UNSIGNED NOT NULL,
    `name` VARCHAR(50) NOT NULL,
    `color` VARCHAR(7) DEFAULT '#64748b',
    UNIQUE KEY `unique_user_tag` (`user_id`, `name`),
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Transaction Tags Pivot
CREATE TABLE IF NOT EXISTS `transaction_tags` (
    `transaction_id` INT UNSIGNED NOT NULL,
    `tag_id` INT UNSIGNED NOT NULL,
    PRIMARY KEY (`transaction_id`, `tag_id`),
    FOREIGN KEY (`transaction_id`) REFERENCES `transactions`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`tag_id`) REFERENCES `tags`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;