ALTER TABLE `bills` 
ADD COLUMN `category_id` INT UNSIGNED NULL AFTER `user_id`,
ADD COLUMN `recurring_count` INT UNSIGNED NULL AFTER `frequency`,
ADD FOREIGN KEY (`category_id`) REFERENCES `categories`(`id`) ON DELETE SET NULL;
ALTER TABLE `transactions` MODIFY `category_id` INT UNSIGNED NULL;
ALTER TABLE `user_preferences` ADD COLUMN `base_currency_id` INT UNSIGNED NULL AFTER `default_landing_page`;
UPDATE `user_preferences` up
JOIN `users` u ON up.user_id = u.id
SET up.base_currency_id = u.base_currency_id
WHERE u.base_currency_id IS NOT NULL;
ALTER TABLE `users` DROP COLUMN `base_currency_id`;