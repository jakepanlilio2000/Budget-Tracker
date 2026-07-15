CREATE TABLE `user_preferences` (
    `user_id` INT UNSIGNED PRIMARY KEY,
    `theme` ENUM('light', 'dark', 'auto') DEFAULT 'auto',
    `accent_color` VARCHAR(7) DEFAULT '#3b82f6',
    `privacy_blur` TINYINT(1) DEFAULT 0,
    `zen_mode` TINYINT(1) DEFAULT 0,
    `compact_mode` TINYINT(1) DEFAULT 0,
    `default_landing_page` VARCHAR(50) DEFAULT '/dashboard',
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;