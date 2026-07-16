-- Add strategic indexes to optimize heavy analytical queries
ALTER TABLE `transactions` ADD INDEX `idx_type_status` (`type`, `status`);
ALTER TABLE `transactions` ADD INDEX `idx_user_type_date` (`user_id`, `type`, `transaction_date`);
ALTER TABLE `transaction_splits` ADD INDEX `idx_category` (`category_id`);
ALTER TABLE `bills` ADD INDEX `idx_status_due` (`status`, `next_due_date`);