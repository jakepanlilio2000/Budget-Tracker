CREATE TABLE `budget_templates` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT UNSIGNED NULL, -- NULL for system defaults
    `name` VARCHAR(100) NOT NULL,
    `description` TEXT,
    `allocations` JSON NOT NULL, -- e.g., {"needs": 50, "wants": 30, "savings": 20}
    `is_system` TINYINT(1) DEFAULT 1,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

