# Frequently Asked Questions (FAQ)

Welcome to the ExpensePro FAQ. This document addresses common questions regarding installation, configuration, financial features, and the gamified progression system.

---

## General

**What is ExpensePro?**
ExpensePro is an enterprise-grade, open-source Personal Finance and Small Business Management platform. It combines rigorous accounting principles with modern gamification to help users build lasting financial discipline.

**Who is this application for?**
It is designed for individuals, freelancers, and small business owners who want granular control over their financial data, advanced analytics, and a motivating system to track their financial health.

**Is it suitable for businesses?**
Yes. While optimized for personal finance, its multi-account support, category splitting, salary management, and detailed reporting make it highly effective for freelancers and small business cash-flow tracking.

**Is it open source?**
Yes, ExpensePro is fully open-source and released under the MIT License.

**Which PHP version is required?**
ExpensePro requires PHP 8.1 or higher. We recommend PHP 8.2 or 8.3 for optimal performance and security.

**Which database is supported?**
The application currently supports MySQL 8.0+ and MariaDB 10.5+.

---

## Installation

**How do I install the application?**
Please refer to our comprehensive [Installation Guide](INSTALL.md) for step-by-step instructions covering both development and production environments.

**Why won't Composer install?**
Ensure you have the required PHP extensions installed (specifically `zip`, `curl`, and `mbstring`). If you encounter memory limit errors, try running `php -d memory_limit=-1 /usr/local/bin/composer install`.

**Why can't I connect to the database?**
Verify the credentials in your `config/config.php` file. Ensure the database user has the correct privileges, the database exists, and the MySQL/MariaDB service is running.

**Why do I get a blank page?**
A blank page usually indicates a fatal PHP error. Check your web server's error logs and the `storage/logs/` directory. Ensure all required PHP extensions are enabled and file permissions are set correctly.

---

## Configuration

**How do I change the default currency?**
Navigate to your Profile or Preferences settings within the application. You can set your Base Currency there. Individual transactions can still be logged in different currencies if needed.

**How do I enable Dark Mode?**
Click on your profile icon in the top navigation bar and select "Theme Settings." You can choose between Light, Dark, or Follow System.

**How does Privacy Blur work?**
Privacy Blur applies a CSS filter to all sensitive numerical data (balances, amounts, XP). When enabled, numbers appear blurred and are only revealed when you hover over them or click the "eye" icon.

**What is Zen Mode?**
Zen Mode hides all non-essential UI elements, such as the sidebar and top navigation, providing a distraction-free environment for focused data entry and analysis.

**What is Compact Mode?**
Compact Mode reduces padding, margins, and font sizes across the application, allowing you to view more data on the screen without scrolling.

---

## Financial Features

**How are account balances calculated?**
Account balances are calculated by summing the opening balance and all posted transactions (income and expenses) linked to that account. You can also perform auditable "Balance Adjustments" which create a system transaction to correct discrepancies without altering history.

**How does Cash Flow Forecast work?**
The forecast engine analyzes your current account balances, upcoming scheduled bills, pending ledger items, and expected salaries to project your cash flow over the next 7 to 30 days.

**How does the Savings Vault work?**
Savings Vaults are dedicated sub-accounts for specific financial goals. You can set a target amount, make deposits or withdrawals, and track your progress via visual progress bars and estimated completion dates.

**How do Budgets work?**
Budgets allow you to set a monthly spending limit for specific categories. The system tracks your expenses against these limits and provides visual alerts if you are approaching or exceeding your budget.

**How are recurring bills handled?**
Recurring bills are tracked in the Bills module. You can set the amount, frequency, and next due date. The system will automatically roll over the due date when a bill is marked as paid.

**What is the Financial Timeline?**
The Financial Timeline is a chronological, color-coded log of all financial activities, system events, and administrative actions, providing a complete audit trail of your account.

**What is the Financial Calendar?**
The Financial Calendar aggregates all time-sensitive financial events—such as bill due dates, salary payments, and pending ledger items—into a single, interactive monthly view.

---

## Dashboard

**Can I customize my dashboard?**
Yes. ExpensePro features a fully customizable Dashboard Builder. You can drag and drop widgets, hide elements you don't need, and save multiple layouts.

**How do Dashboard Builder layouts work?**
When you enter "Edit Mode," widgets become draggable. The layout state (order, visibility, and size) is saved to your user preferences in the database and persists across sessions.

**Can I restore the default dashboard?**
Yes. In Edit Mode, click the "Reset" button to revert your dashboard to the default factory layout.

---

## Achievement System

**How does the Achievement Engine work?**
The engine uses a database-driven, rule-based evaluation system. It monitors your financial actions (e.g., recording transactions, paying bills) and dynamically updates your progress toward various milestones.

**What is Financial Experience (FXP)?**
FXP is a global experience point system. You earn FXP for almost every meaningful financial action. Different actions award different amounts of FXP, which can be configured by administrators.

**How do levels work?**
Levels are calculated using a dynamic scaling formula based on your total lifetime FXP. As you earn more FXP, the XP required for the next level increases, allowing for infinite progression.

**What is Prestige?**
Prestige is an optional "New Game+" mechanic. Once you reach Level 50, you can choose to Prestige. This resets your level to 1 but awards you a permanent XP multiplier, a Prestige Star, and exclusive titles, while keeping all your lifetime statistics and unlocked achievements.

**How are achievements unlocked?**
Achievements are unlocked automatically when your progress meets or exceeds the target defined in the achievement rule. Endless achievements dynamically generate the next target tier upon completion.

**Can achievements be reset?**
Currently, there is no built-in "reset" button for achievements to preserve the integrity of your lifetime statistics. If a reset is absolutely necessary, it must be done via direct database manipulation by an administrator.

---

## Reports & Analytics

**What reports are available?**
The application includes Monthly and Yearly Reviews, Advanced Analytics (behavioral, category, and account analysis), and comprehensive financial exports.

**Can I export reports?**
Yes. You can export your complete financial data in JSON (for backups), XLSX (multi-sheet spreadsheets), CSV (universal compatibility), and PDF (print-ready reports).

**How are analytics generated?**
Analytics are generated using optimized SQL aggregation queries that group your transaction data by date, category, and account type, which is then rendered using interactive Chart.js visualizations.

---

## Backup & Restore

**How do I create a backup?**
Navigate to Settings > Backup & Restore. Select your desired format (JSON, XLSX, CSV, or PDF) and click "Export."

**How do I restore a backup?**
In the Backup & Restore section, upload a valid `.json` backup file. You will be required to enter your account password to confirm the destructive restore operation.

**Will restoring overwrite my data?**
Yes. The restore process safely truncates all existing financial data for your user account and rebuilds it from the backup file. It is highly recommended to create a backup before restoring.

---

## Troubleshooting

**The application is slow.**
Ensure you have enabled PHP OPcache in your production environment. For heavy analytics, ensure your database tables are properly indexed. Clear the application cache if necessary.

**Charts are not loading.**
Check your browser console for JavaScript errors. Ensure that Chart.js is loading correctly and that your web server is serving static assets from the `public/` directory.

**Currency is incorrect.**
Verify your Base Currency in Preferences. If individual transactions show the wrong currency, check the currency selected during the transaction creation process.

**Dashboard widgets disappeared.**
You may have accidentally hidden them. Enter Dashboard Edit Mode and check if the widgets are unchecked in the visibility settings, or click "Reset" to restore defaults.

**Theme settings are not applying.**
Ensure your browser is not overriding CSS variables. Try clearing your browser cache. If "Follow System" is selected, ensure your OS theme is set correctly.

---

## Security

**Is my data encrypted?**
ExpensePro is designed to be self-hosted. Your data resides on your own server. Passwords are hashed using Bcrypt/Argon2. For data at rest encryption, you should enable Transparent Data Encryption (TDE) at the database level.

**How are passwords stored?**
Passwords are never stored in plain text. They are hashed using PHP's native `password_hash()` function with a strong, automatically generated salt.

**How can I report a vulnerability?**
Please follow our responsible disclosure policy outlined in the [SECURITY.md](SECURITY.md) document. Do not report security issues via public GitHub issues.

---

## Development

**How do I contribute?**
We welcome contributions! Please read our [CONTRIBUTING.md](CONTRIBUTING.md) guide to understand our workflow, coding standards, and pull request process.

**How do I report bugs?**
Open a new issue on our GitHub repository using the Bug Report template. Include steps to reproduce, your environment details, and relevant error logs.

**Where can I request new features?**
Use the Feature Request template in our GitHub Issues tracker. Clearly describe the problem, your proposed solution, and the use case.

---

**Related Documentation:**
- [README.md](../README.md)
- [INSTALL.md](INSTALL.md)
- [SECURITY.md](../SECURITY.md)
- [CONTRIBUTING.md](../CONTRIBUTING.md)