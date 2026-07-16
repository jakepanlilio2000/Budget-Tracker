-- 1. Add chain configuration to achievement definitions
ALTER TABLE `achievement_definitions` 
ADD COLUMN `is_chain` TINYINT(1) DEFAULT 0 AFTER `rule_config`,
ADD COLUMN `chain_multiplier` DECIMAL(5,2) DEFAULT 1.00 AFTER `is_chain`,
ADD COLUMN `base_target` DECIMAL(15,2) DEFAULT 0 AFTER `chain_multiplier`;

-- 2. Add chain level tracking to user progress
ALTER TABLE `user_achievements` 
ADD COLUMN `chain_level` INT UNSIGNED DEFAULT 1 AFTER `achievement_id`;