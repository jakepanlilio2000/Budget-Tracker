CREATE TABLE `planning_scenarios` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT UNSIGNED NOT NULL,
    `name` VARCHAR(100) NOT NULL DEFAULT 'Untitled Scenario',
    `description` TEXT,
    `is_favorite` TINYINT(1) DEFAULT 0,
    `is_archived` TINYINT(1) DEFAULT 0,
    `workspace_data` JSON, -- Stores the complete simulation state (income, tax, buckets, events)
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE INDEX `idx_user_archived` ON `planning_scenarios` (`user_id`, `is_archived`);