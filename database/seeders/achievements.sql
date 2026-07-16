-- Seed Achievements
INSERT INTO `achievements` (`slug`, `name`, `description`, `icon`, `color`, `category`, `difficulty`, `xp_points`, `sort_order`) VALUES
('first_expense', 'First Expense', 'Logged your very first expense transaction.', 'fa-receipt', '#ef4444', 'Discipline', 'easy', 10, 1),
('first_income', 'First Income', 'Logged your very first income transaction.', 'fa-arrow-down', '#10b981', 'Discipline', 'easy', 10, 2),
('first_salary', 'First Salary', 'Recorded your first payslip.', 'fa-briefcase', '#3b82f6', 'Discipline', 'easy', 15, 3),
('first_budget', 'First Budget', 'Created your first monthly budget.', 'fa-piggy-bank', '#8b5cf6', 'Discipline', 'easy', 15, 4),
('first_vault', 'First Vault', 'Created your first savings vault.', 'fa-vault', '#14b8a6', 'Discipline', 'easy', 15, 5),
('first_backup', 'Data Hoarder', 'Created your first system backup.', 'fa-shield-alt', '#f59e0b', 'Discipline', 'easy', 20, 6),

('saved_1k', 'Savings Starter', 'Saved a total of 1,000 in your vaults.', 'fa-coins', '#10b981', 'Savings', 'easy', 20, 10),
('saved_10k', 'Savings Pro', 'Saved a total of 10,000 in your vaults.', 'fa-money-bill-wave', '#10b981', 'Savings', 'medium', 50, 11),
('saved_50k', 'Savings Master', 'Saved a total of 50,000 in your vaults.', 'fa-gem', '#10b981', 'Savings', 'hard', 100, 12),
('saved_100k', 'Centurion', 'Saved a total of 100,000 in your vaults.', 'fa-crown', '#f59e0b', 'Savings', 'legendary', 250, 13),
('first_goal_completed', 'Goal Crusher', 'Completed your first savings goal.', 'fa-flag-checkered', '#3b82f6', 'Savings', 'medium', 50, 14),
('multiple_goals_completed', 'Overachiever', 'Completed 5 or more savings goals.', 'fa-trophy', '#f59e0b', 'Savings', 'hard', 150, 15),

('txn_100', 'Century Club', 'Recorded 100 transactions.', 'fa-receipt', '#3b82f6', 'Milestones', 'medium', 50, 50),
('txn_500', 'High Volume', 'Recorded 500 transactions.', 'fa-layer-group', '#8b5cf6', 'Milestones', 'hard', 100, 51),
('txn_1000', 'Data Entry Pro', 'Recorded 1,000 transactions.', 'fa-database', '#f59e0b', 'Milestones', 'legendary', 250, 52),
('multi_accounts', 'Diversified', 'Created 3 or more accounts.', 'fa-university', '#14b8a6', 'Milestones', 'easy', 30, 53),
('multi_vaults', 'Vault Collector', 'Created 3 or more savings vaults.', 'fa-vault', '#14b8a6', 'Milestones', 'medium', 50, 54);