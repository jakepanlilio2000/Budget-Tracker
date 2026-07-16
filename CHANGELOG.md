# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Added
- *(Placeholder for new features)*

### Changed
- *(Placeholder for changes in existing functionality)*

### Fixed
- *(Placeholder for bug fixes)*

### Removed
- *(Placeholder for removed features)*

### Deprecated
- *(Placeholder for soon-to-be removed features)*

### Security
- *(Placeholder for security fixes)*

## [1.0.0] - Initial Public Release

The first stable, production-ready release of ExpensePro, featuring a comprehensive suite of personal finance and small business management tools, powered by a gamified progression system.

### Added
- **Core Modules:** User Authentication, Role-Based Access Control (RBAC), and a customizable Installer.
- **Financial Tracking:** Comprehensive Expense and Income tracking with multi-currency support and intelligent category splitting.
- **Account Management:** Multi-institution account tracking with auditable balance adjustments and soft deletes.
- **Budgeting & Bills:** Monthly category-based budgets with overspend alerts, and recurring bill management with penalty tracking.
- **Savings & Goals:** Dedicated Savings Vaults for goal-oriented tracking, deposits, and withdrawals.
- **Payroll:** Detailed Salary and Payslip management, including employer tracking, allowances, and deductions.
- **Planning & Forecasting:** Financial Calendar, Cash Flow Forecast (7-30 days), and Pending Ledger for scheduled items.
- **Analytics & Reporting:** Advanced Analytics dashboard with behavioral and category intelligence, plus Monthly and Yearly Reviews.
- **Enterprise Reports:** Comprehensive export capabilities in JSON, XLSX, CSV, and PDF formats.
- **Gamification:** Full Achievement Engine, Endless FXP System, Infinite Leveling, Mastery Tracks, Prestige System, and Streaks.
- **Customization:** Fully customizable Dashboard Builder (drag-and-drop), Theme System (Light/Dark), Privacy Blur, Compact Mode, and Zen Mode.
- **Simulators:** Investment Sandbox and Loan Sandbox for financial modeling.
- **System:** Global Search, Financial Timeline, Activity Logs, and comprehensive Backup & Restore functionality.

### Security
- Implemented strict server-side input validation and output escaping (`e()`) to prevent XSS.
- Enforced 100% usage of PDO prepared statements to prevent SQL injection.
- Integrated CSRF token protection on all state-changing forms and AJAX requests.
- Utilized PHP's native `password_hash()` for secure credential storage.
- Implemented secure session handling with `HttpOnly`, `Secure`, and `SameSite` cookie attributes.
- Added immutable Audit Logs for administrative and destructive actions.
- Enforced strict file permissions and secure configuration management.