-- Seed System Templates
INSERT INTO `budget_templates` (`user_id`, `name`, `description`, `allocations`, `is_system`) VALUES
(NULL, '50/30/20 Rule', 'The classic budget: 50% Needs, 30% Wants, 20% Savings.', '{"needs": 50, "wants": 30, "savings": 20}', 1),
(NULL, 'Zero-Based Budget', 'Every dollar has a job. Income minus expenses equals zero.', '{"needs": 70, "wants": 10, "savings": 20}', 1),
(NULL, 'FIRE (Financial Independence)', 'Aggressive savings for early retirement.', '{"needs": 50, "wants": 10, "savings": 40}', 1),
(NULL, 'Student Budget', 'Minimize wants, maximize savings and debt repayment.', '{"needs": 60, "wants": 10, "savings": 30}', 1),
(NULL, 'Family Budget', 'Higher needs allocation for household expenses.', '{"needs": 60, "wants": 20, "savings": 20}', 1);