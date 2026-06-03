-- Create database and use it
CREATE DATABASE IF NOT EXISTS budget_tracker CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE budget_tracker;

-- profiles: each profile = isolated budget
CREATE TABLE profiles (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    currency VARCHAR(10) DEFAULT '₱',
    color VARCHAR(7) DEFAULT '#4F7BF7',
    pay_schedule ENUM('semi_monthly','weekly','bi_weekly','monthly','quarterly','semi_annual','annual') DEFAULT 'semi_monthly',
    pay_day_1 TINYINT UNSIGNED DEFAULT 15,
    pay_day_2 TINYINT UNSIGNED DEFAULT 30,
    weekly_day TINYINT UNSIGNED DEFAULT 5,
    base_income DECIMAL(12,2) DEFAULT 0.00,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- categories: user-defined groupings
CREATE TABLE categories (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    profile_id INT UNSIGNED NOT NULL,
    name VARCHAR(100) NOT NULL,
    type ENUM('inflow','outflow','savings') NOT NULL DEFAULT 'outflow',
    color VARCHAR(7) DEFAULT '#888888',
    icon VARCHAR(50) DEFAULT 'tag',
    sort_order SMALLINT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (profile_id) REFERENCES profiles(id) ON DELETE CASCADE
);

-- entries: each line item in the budget
CREATE TABLE entries (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    profile_id INT UNSIGNED NOT NULL,
    category_id INT UNSIGNED NOT NULL,
    name VARCHAR(150) NOT NULL,
    amount DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    type ENUM('inflow','outflow') NOT NULL,
    is_active BOOLEAN DEFAULT TRUE,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (profile_id) REFERENCES profiles(id) ON DELETE CASCADE,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE RESTRICT
);

-- entry_frequencies: when each entry is due
CREATE TABLE entry_frequencies (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    entry_id INT UNSIGNED NOT NULL,
    frequency_type ENUM('weekly','bi_weekly','semi_monthly','monthly','quarterly','semi_annual','annual','custom_months','one_time') NOT NULL,
    specific_day TINYINT UNSIGNED,
    specific_date DATE,
    is_first_half BOOLEAN DEFAULT TRUE,
    start_date DATE,
    end_date DATE,
    total_months TINYINT UNSIGNED,
    months_paid TINYINT UNSIGNED DEFAULT 0,
    repeat_on_months JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (entry_id) REFERENCES entries(id) ON DELETE CASCADE
);

-- transactions: actual recorded inflows and outflows per period
CREATE TABLE transactions (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    profile_id INT UNSIGNED NOT NULL,
    entry_id INT UNSIGNED,
    category_id INT UNSIGNED NOT NULL,
    name VARCHAR(150) NOT NULL,
    amount DECIMAL(12,2) NOT NULL,
    type ENUM('inflow','outflow') NOT NULL,
    period_date DATE NOT NULL,
    is_checked BOOLEAN DEFAULT FALSE,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (profile_id) REFERENCES profiles(id) ON DELETE CASCADE,
    FOREIGN KEY (entry_id) REFERENCES entries(id) ON DELETE SET NULL,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE RESTRICT
);

CREATE TABLE shopping_log (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    profile_id INT UNSIGNED NOT NULL,
    item_name VARCHAR(255) NOT NULL,
    store_name VARCHAR(100) DEFAULT NULL,
    amount DECIMAL(10,2) NOT NULL,
    payment_method VARCHAR(50) DEFAULT 'Cash',
    is_need BOOLEAN DEFAULT 1,
    purchase_date DATE NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_shopping_profile FOREIGN KEY (profile_id) REFERENCES profiles(id) ON DELETE CASCADE
);

-- calculator_sessions: saved calculator states
CREATE TABLE calculator_sessions (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    profile_id INT UNSIGNED NOT NULL,
    name VARCHAR(100),
    items JSON NOT NULL,
    result DECIMAL(12,2),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (profile_id) REFERENCES profiles(id) ON DELETE CASCADE
);

CREATE TABLE savings_goals (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    profile_id INT UNSIGNED NOT NULL,
    name VARCHAR(100) NOT NULL,
    target_amount DECIMAL(12,2) NOT NULL,
    current_amount DECIMAL(12,2) DEFAULT 0.00,
    color VARCHAR(7) DEFAULT '#3fb950',
    icon VARCHAR(50) DEFAULT '🎯',
    target_date DATE NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (profile_id) REFERENCES profiles(id) ON DELETE CASCADE
);

-- 1. Create the Users Table
CREATE TABLE users (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 2. Create a default admin user to claim your existing data
-- Password is 'password123' (you can change this later)
INSERT INTO users (name, email, password) 
VALUES ('Admin', 'admin@budgetsuite.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi');

-- 3. Link existing Profiles to this new user
ALTER TABLE profiles ADD COLUMN user_id INT UNSIGNED NOT NULL DEFAULT 1 AFTER id;
ALTER TABLE profiles ADD CONSTRAINT fk_user_profile FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE;

-- SEED DATA for Jash
INSERT INTO profiles (id, name, currency, pay_schedule, base_income) VALUES (1, 'Jash', '₱', 'semi_monthly', 8000.00);

INSERT INTO categories (id, profile_id, name, type, sort_order) VALUES 
(1, 1, 'INCOME & INFLOWS', 'inflow', 1),
(2, 1, 'FIXED EXPENSES & LOANS', 'outflow', 2),
(3, 1, 'TEMPORARY EXPENSES', 'outflow', 3),
(4, 1, 'VARIABLE EXPENSES', 'outflow', 4);

-- Entries (Income)
INSERT INTO entries (id, profile_id, category_id, name, amount, type) VALUES 
(1, 1, 1, 'Base Pay', 8000.00, 'inflow'),
(2, 1, 1, 'Internet Shares', 1900.00, 'inflow');
INSERT INTO entry_frequencies (entry_id, frequency_type, is_first_half) VALUES (1, 'semi_monthly', 1), (1, 'semi_monthly', 0);
INSERT INTO entry_frequencies (entry_id, frequency_type, is_first_half) VALUES (2, 'semi_monthly', 0);

-- Entries (Fixed/Loans)
INSERT INTO entries (id, profile_id, category_id, name, amount, type) VALUES 
(3, 1, 2, 'Contribution', 1500.00, 'outflow'),
(4, 1, 2, 'Motor', 4080.00, 'outflow'),
(5, 1, 2, 'Dental', 1000.00, 'outflow'),
(6, 1, 2, 'Credit Card', 2000.00, 'outflow'),
(7, 1, 2, 'Internet', 1798.00, 'outflow'),
(8, 1, 2, 'InvestED Loan', 1028.80, 'outflow');
INSERT INTO entry_frequencies (entry_id, frequency_type, is_first_half) VALUES 
(3, 'semi_monthly', 0),
(4, 'semi_monthly', 1),
(5, 'semi_monthly', 1),
(6, 'semi_monthly', 0),
(7, 'semi_monthly', 0),
(8, 'semi_monthly', 0);

-- Entries (Temporary/Installments)
INSERT INTO entries (id, profile_id, category_id, name, amount, type) VALUES 
(9, 1, 3, 'Mobo', 1238.00, 'outflow'),
(10, 1, 3, 'UPS', 1561.96, 'outflow'),
(11, 1, 3, 'Mikrotik', 1468.95, 'outflow'),
(12, 1, 3, 'Screw/Omni/Ext', 310.66, 'outflow');
INSERT INTO entry_frequencies (entry_id, frequency_type, specific_day, total_months, months_paid) VALUES 
(9, 'custom_months', 15, 6, 0),
(10, 'custom_months', 30, 3, 0),
(11, 'custom_months', 30, 3, 0),
(12, 'custom_months', 30, 3, 0);

-- Entries (Variable)
INSERT INTO entries (id, profile_id, category_id, name, amount, type) VALUES 
(13, 1, 4, 'Fuel', 350.00, 'outflow'),
(14, 1, 4, 'Food', 975.00, 'outflow');
INSERT INTO entry_frequencies (entry_id, frequency_type, is_first_half) VALUES 
(13, 'semi_monthly', 1), (13, 'semi_monthly', 0),
(14, 'semi_monthly', 1), (14, 'semi_monthly', 0);