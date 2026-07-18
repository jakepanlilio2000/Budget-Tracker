CREATE TABLE `backup_history` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT UNSIGNED NOT NULL,
    `backup_uuid` CHAR(36) NOT NULL UNIQUE,
    `filename` VARCHAR(255) NOT NULL,
    `format` VARCHAR(10) NOT NULL DEFAULT 'json',
    `file_size_bytes` INT UNSIGNED NOT NULL DEFAULT 0,
    `schema_version` VARCHAR(20) NOT NULL,
    `modules_included` JSON,
    `checksum_sha256` CHAR(64) NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `restored_at` TIMESTAMP NULL DEFAULT NULL,
    `restore_status` ENUM('success', 'failed', 'pending') DEFAULT 'pending',
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE INDEX `idx_user_backup` ON `backup_history` (`user_id`, `created_at` DESC);