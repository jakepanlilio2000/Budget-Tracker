-- Radar Alerts (Subscriptions, Duplicates, Risks)
CREATE TABLE `radar_alerts` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT UNSIGNED NOT NULL,
    `type` ENUM('subscription', 'duplicate', 'spending_risk', 'budget_warning') NOT NULL,
    `severity` ENUM('low', 'medium', 'high') DEFAULT 'medium',
    `title` VARCHAR(150) NOT NULL,
    `description` TEXT NOT NULL,
    `entity_type` VARCHAR(50) NULL, -- e.g., 'transaction', 'category'
    `entity_id` INT UNSIGNED NULL,
    `is_resolved` TINYINT(1) DEFAULT 0,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `resolved_at` TIMESTAMP NULL,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    INDEX `idx_user_resolved` (`user_id`, `is_resolved`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;