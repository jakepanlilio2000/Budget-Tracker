-- Centralized cache for financial summaries to ensure lightning-fast dashboard/report loads
CREATE TABLE `financial_summary_cache` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT UNSIGNED NOT NULL,
    `metric_key` VARCHAR(100) NOT NULL, -- e.g., 'assets_total', 'income_monthly', 'net_worth'
    `period_start` DATE DEFAULT NULL,   -- NULL for lifetime/all-time metrics (like Net Worth)
    `period_end` DATE DEFAULT NULL,
    `value` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    `currency_id` INT UNSIGNED DEFAULT NULL,
    `calculated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY `unique_user_metric_period` (`user_id`, `metric_key`, `period_start`, `period_end`),
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Registry for future extensible modules (e.g., Investments, Loans)
CREATE TABLE `summary_module_registry` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `module_name` VARCHAR(50) NOT NULL UNIQUE,
    `provider_class` VARCHAR(100) NOT NULL,
    `is_active` TINYINT(1) DEFAULT 1,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Seed core modules
INSERT INTO `summary_module_registry` (`module_name`, `provider_class`, `is_active`) VALUES
('transactions', 'App\\Services\\Providers\\TransactionSummaryProvider', 1),
('vaults', 'App\\Services\\Providers\\VaultSummaryProvider', 1),
('budgets', 'App\\Services\\Providers\\BudgetSummaryProvider', 1),
('bills', 'App\\Services\\Providers\\BillSummaryProvider', 1);