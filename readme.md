# ExpensePro

**Enterprise-Grade Personal Finance & Small Business Management Platform**

> *Master your financial future with precision, intelligence, and gamified discipline.*

**Status:** Production-Ready &bull; **Version:** 2.0.0 &bull; **License:** MIT

[![PHP Version](https://img.shields.io/badge/PHP-8.1+-777BB4?style=for-the-badge&logo=php)](https://www.php.net/)
[![MySQL](https://img.shields.io/badge/MySQL-8.0+-4479A1?style=for-the-badge&logo=mysql)](https://www.mysql.com/)
[![Architecture](https://img.shields.io/badge/Architecture-MVC-007EC6?style=for-the-badge)](#)
[![Mobile First](https://img.shields.io/badge/Design-Mobile_First-000000?style=for-the-badge)](#)
[![License](https://img.shields.io/badge/License-MIT-green?style=for-the-badge)](LICENSE)

---

## Table of Contents

- [About](#about)
- [Feature Highlights](#feature-highlights)
- [Screenshots](#screenshots)
- [Technology Stack](#technology-stack)
- [Architecture](#architecture)
- [Folder Structure](#folder-structure)
- [Installation](#installation)
- [Configuration](#configuration)
- [Usage Guide](#usage-guide)
- [Security](#security)
- [Performance](#performance)
- [Mobile Experience](#mobile-experience)
- [Dashboard](#dashboard)
- [Analytics](#analytics)
- [Achievement Engine](#achievement-engine)
- [Customization](#customization)
- [Backup & Restore](#backup--restore)
- [Roadmap](#roadmap)
- [FAQ](#faq)
- [Contributing](#contributing)
- [Bug Reports](#bug-reports)
- [License](#license)
- [Credits](#credits)
- [Acknowledgements](#acknowledgements)

---

## About

**ExpensePro** is a comprehensive, enterprise-grade Personal Finance and Small Business Management platform built from the ground up in Vanilla PHP. Designed for individuals, freelancers, and small business owners, it transforms raw financial data into actionable intelligence. 

By combining rigorous accounting principles with modern gamification (FXP, endless achievements, and prestige systems), ExpensePro motivates users to build lasting financial discipline while providing the deep analytical tools required for strategic wealth building.

---

## Feature Highlights

### Core Financial Management
- **Expense & Income Tracking:** Multi-currency transaction logging with intelligent category splitting.
- **Salary Management:** Detailed payslip generation, employer tracking, and allowance/deduction breakdowns.
- **Accounts:** Support for multiple financial institutions, account types, and real-time balance tracking with auditable adjustments.
- **Budgets:** Monthly category-based budgeting with overspend alerts.
- **Bills:** Recurring bill management with penalty tracking, specific due dates, and payment history.
- **Savings Vault:** Goal-oriented savings accounts with progress tracking, deposits, and withdrawals.

### Advanced Intelligence
- **Financial Timeline:** Chronological, color-coded log of all financial activities and system events.
- **Financial Calendar:** Visual mapping of upcoming bills, salaries, and pending ledger items.
- **Cash Flow Forecast:** 7-to-30 day predictive modeling of account balances based on scheduled events.
- **Monthly & Yearly Reviews:** Automated financial health reports, budget success rates, and savings milestones.
- **Advanced Analytics:** Interactive BI dashboard featuring behavioral analysis, category intelligence, and multi-dimensional charting.
- **Investment & Loan Sandboxes:** Simulation tools for projecting compound growth and amortization.

### Gamification & Progression
- **Achievement Engine:** Database-driven, dynamic milestone tracking.
- **FXP System:** Global Financial Experience points awarded for every meaningful action.
- **Endless Leveling:** Infinite, dynamically scaling progression without hard caps.
- **Mastery Tracks:** Independent leveling for Expense, Income, Savings, Budget, and Consistency.
- **Prestige System:** Optional "New Game+" reset that awards permanent XP multipliers and exclusive titles.
- **Streaks:** Consistency tracking for daily logins, transactions, and bill payments.

### System & UX
- **Dashboard Builder:** Fully customizable, drag-and-drop widget layout system.
- **Theme System:** Seamless Light/Dark mode with dynamic accent colors.
- **Privacy Modes:** Privacy Blur, Compact Mode, and Zen Mode for focused, secure viewing.
- **Global Search:** Instant, cross-module search across transactions, bills, vaults, and logs.
- **Backup & Restore:** Comprehensive data export/import in JSON, XLSX, CSV, and PDF formats.
- **Multi-Currency:** Base currency configuration with per-transaction currency support.
- **Activity Logs:** Immutable audit trail for administrative and destructive actions.

---

## Screenshots


| Dashboard | Advanced Analytics | Achievement Engine |
| :---: | :---: | :---: |
| `![Dashboard](public/assets/screenshots/dashboard.png)` | `![Analytics](public/assets/screenshots/analytics.png)` | `![Achievements](public/assets/screenshots/achievements.png)` |
| **Financial Calendar** | **Savings Vaults** | **Dark Mode UI** |
| `![Calendar](public/assets/screenshots/calendar.png)` | `![Vaults](public/assets/screenshots/vaults.png)` | `![Dark Mode](public/assets/screenshots/dark-mode.png)` |

---

## Technology Stack

| Component | Technology | Purpose |
| :--- | :--- | :--- |
| **Backend** | PHP 8.1+ | Core application logic, MVC routing |
| **Database** | MySQL 8.0+ | Relational data storage, JSON support |
| **Frontend** | Vanilla JS, CSS3 | Zero-dependency, high-performance UI |
| **Charts** | Chart.js | Interactive data visualization |
| **PDF Generation** | mPDF | Enterprise-grade report exporting |
| **Spreadsheet** | PhpSpreadsheet | Multi-sheet XLSX export/import |
| **Icons** | Font Awesome 6 | Consistent, scalable vector iconography |

---

## Architecture

ExpensePro adheres to strict **Model-View-Controller (MVC)** principles, ensuring a clean separation of concerns:

- **Routing:** A centralized, regex-based router in `public/index.php` maps URIs to controller actions securely.
- **Controllers:** Handle HTTP requests, validate input, orchestrate services, and pass sanitized data to views.
- **Models:** Encapsulate database interactions using PDO prepared statements, preventing SQL injection.
- **Services:** Contain complex business logic (e.g., `AchievementEngine`, `FxpEngine`, `CashFlowService`), keeping controllers lean.
- **Views:** Render HTML using output buffering (`ob_start`), utilizing a shared `layouts.app` wrapper for consistency.
- **Scalability:** Stateless design, query optimization, and a lightweight caching layer ensure the application scales efficiently from personal use to small business deployments.

---

## Folder Structure

```text
expensepro/
├── app/
│   ├── Core/           # Framework foundation (Router, Database, Auth, CSRF)
│   ├── Controllers/    # HTTP request handlers
│   ├── Helpers/        # Global utility functions (e.g., e(), url())
│   ├── Models/         # Data access objects
│   ├── Services/       # Business logic engines (FXP, Analytics, Timeline)
│   └── Views/          # HTML templates and layouts
├── config/
│   └── config.php      # Database and application settings
├── database/
│   └── migrations/     # Version-controlled SQL schema files
├── public/
│   ├── assets/         # CSS, JS, and images
│   └── index.php       # Application entry point
├── storage/
│   ├── logs/           # Application and error logs
│   └── tmp/            # Temporary files (e.g., PDF generation)
├── vendor/             # Composer dependencies
├── composer.json       # Dependency manifest
└── README.md           # Project documentation
```

---

## Installation

1. **Clone the repository:**
   ```bash
   git clone https://github.com/yourusername/expensepro.git
   cd expensepro
   ```
2. **Install dependencies:**
   ```bash
   composer install
   ```
3. **Configure the database:**
   - Create a new MySQL database.
   - Copy `config/config.example.php` to `config/config.php` and update the credentials.
4. **Run migrations:**
   - Import the SQL files located in `database/migrations/` in numerical order, or run the provided setup script.
5. **Set permissions:**
   ```bash
   chmod -R 755 storage/
   ```
6. **Seed initial data (Optional):**
   ```bash
   php bin/seed_achievements.php
   ```

---

## Configuration

The `config/config.php` file manages core application settings:
- **Database:** Host, port, database name, username, and password.
- **App Settings:** Base URL, default timezone, and session configurations.
- **Security:** Session cookie parameters and CSRF token expiration.

*Ensure the `config/` directory is not publicly accessible via the web server.*

---

## Usage Guide

1. **Onboarding:** Register an account and set your **Base Currency** in Preferences.
2. **Setup:** Navigate to **Accounts** to add your financial institutions, then populate **Categories** for income and expenses.
3. **Daily Use:** Use the **Global Search** or quick-add buttons to log transactions. Check the **Pending Ledger** for upcoming obligations.
4. **Review:** Visit the **Dashboard** for a high-level overview, or dive into **Advanced Analytics** for behavioral spending insights.
5. **Gamify:** Monitor your **Achievement Engine** to track FXP, level up your Mastery tracks, and maintain your daily streaks.

---

## Security

ExpensePro is built with a security-first mindset:
- **SQL Injection:** 100% of database queries use PDO prepared statements.
- **XSS Protection:** All user-generated output is escaped via the `e()` helper function.
- **CSRF Protection:** Every state-changing POST request requires a valid, cryptographically secure CSRF token.
- **Authentication:** Passwords are hashed using PHP's `password_hash()` (Bcrypt/Argon2).
- **Authorization:** Role-based access control (RBAC) restricts administrative features (e.g., Backup/Restore) to authorized users.
- **Audit Trails:** Destructive actions are logged with timestamps and user IDs in the Activity Logs.

---

## Performance

- **Optimized Queries:** Complex aggregations (e.g., Monthly Reviews) use targeted `GROUP BY` clauses and indexed columns.
- **Caching:** Dashboard statistics and lifetime metrics utilize a lightweight caching layer to prevent redundant database hits.
- **Asset Optimization:** Vanilla JavaScript and CSS ensure zero framework overhead, resulting in sub-100ms Time-to-Interactive (TTI).
- **Lazy Evaluation:** Heavy services (like the Achievement Engine) are triggered asynchronously or post-transaction to keep response times minimal.

---

## Mobile Experience

Designed with a **Mobile-First** philosophy:
- Responsive CSS Grid and Flexbox layouts adapt seamlessly from 320px mobile screens to 4K desktops.
- Touch-friendly targets (minimum 44x44px) for all interactive elements.
- Collapsible navigation and optimized modal dialogs for small viewports.
- Native-like feel with smooth CSS transitions and hardware-accelerated animations.

---

## Dashboard

The Dashboard is fully customizable via the **Dashboard Builder**. Available widgets include:
- **Financial Summary:** Real-time income, expense, and net cash flow.
- **Mastery & FXP:** Current level, XP progress, and mastery track breakdowns.
- **Savings Vaults:** Visual progress bars for active financial goals.
- **Recent Timeline:** Chronological feed of recent financial activity.
- **Cash Flow Forecast:** 7-day projected balance with shortage warnings.
- **Upcoming Events:** Consolidated view of pending bills and salaries.
- **Achievements:** Quick view of recently unlocked milestones.

---

## Analytics

The Advanced Analytics module provides business-intelligence-grade insights:
- **Financial Performance:** Area and bar charts for Income vs. Expenses and Net Cash Flow.
- **Behavioral Analysis:** Radar charts for spending by day of the week, and bar charts for spending by hour.
- **Category Intelligence:** Donut charts for top spending categories and stacked bar charts for monthly category trends.
- **Account Analysis:** Visual breakdown of asset allocation across different account types.
- **Interactive Controls:** Dynamic date-range filtering with instant chart re-rendering.

---

## Achievement Engine

The crown jewel of ExpensePro, designed to make financial discipline addictive:
- **Dynamic Achievements:** Database-driven rules evaluate user behavior in real-time.
- **Endless Chains:** Milestones automatically scale (e.g., 10 → 25 → 62 transactions) using configurable multipliers, ensuring the system never runs out of goals.
- **FXP (Financial Experience):** Every action (logging expenses, paying bills, saving) awards XP, scaled by a prestige multiplier.
- **Infinite Leveling:** A dynamic scaling formula (`Level = floor((FXP / 100) ^ (2/3)) + 1`) ensures smooth, endless progression.
- **Mastery Tracks:** Independent leveling for specific behaviors (Expense, Income, Savings, Budget, Consistency).
- **Prestige System:** Upon reaching Level 50, users can Prestige, resetting their level to 1 but gaining a permanent +10% XP multiplier, a Prestige Star, and exclusive titles.
- **Titles:** Dynamic monikers (e.g., "Beginner Saver" → "Financial Architect") that evolve with your progress.
- **Streaks:** Tracks consecutive days of logins, transactions, and bill payments, with current and best streak records.

---

## Customization

Tailor the application to your workflow:
- **Dashboard Builder:** Drag, drop, resize, and hide widgets. Layouts are saved automatically to user preferences.
- **Themes:** Toggle between Light and Dark modes, with customizable accent colors.
- **Base Currency:** Set a default currency for global calculations, while retaining per-transaction currency flexibility.
- **Privacy Blur:** Instantly obfuscates all sensitive numerical data with a CSS blur filter, revealable on hover.
- **Compact Mode:** Reduces padding and font sizes for high-density data viewing.
- **Zen Mode:** Hides all non-essential UI elements (sidebars, headers) for distraction-free data entry.

---

## Backup & Restore

Enterprise-grade data portability is built-in:
- **Export Formats:** JSON (full fidelity), XLSX (multi-sheet, styled), CSV (universal compatibility), and PDF (print-ready reports).
- **Comprehensive Scope:** Exports transactions, splits, accounts, categories, budgets, bills, salaries, vaults, daily logs, pending ledger, and the entire Achievement/FXP state.
- **Secure Restore:** Requires administrative password verification. Safely truncates existing data and rebuilds the schema with foreign key integrity mapping.
- **Nuclear Option:** A secure, password-protected "Delete All Data" feature that truncates all financial tables while preserving user accounts and preferences.

---

## Roadmap

### ✅ Completed
- Core MVC architecture and authentication.
- Transaction, Account, Budget, Bill, and Vault management.
- Advanced Analytics and Financial Calendar.
- Endless Achievement Engine, FXP, Mastery, and Prestige.
- Dashboard Builder and Customization modes (Privacy, Zen, Compact).
- Comprehensive Backup/Restore (JSON, XLSX, CSV, PDF).

### 🔮 Future Ideas
- **PWA Offline Support:** Service workers for offline transaction logging with background sync.
- **Multi-User Households:** Shared vaults and budgets with granular permission roles.
- **Open Banking API:** Plaid or Teller integration for automatic transaction importing.
- **Recurring Transactions:** Native auto-generation of scheduled income/expenses.

### 🛠 Planned Improvements
- Automated database migration CLI tool.
- Enhanced unit testing suite (PHPUnit) for Core Services.
- Webhook support for external notification integrations.

---

## FAQ

**Q: Is ExpensePro suitable for small business accounting?**  
A: Yes. While designed for personal finance, its multi-account support, category splitting, salary management, and detailed reporting make it highly effective for freelancers and small business cash-flow tracking.

**Q: Is my financial data secure?**  
A: Absolutely. ExpensePro runs on your own server. Data is never sent to third-party APIs. All inputs are sanitized, queries are parameterized, and sessions are securely managed.

**Q: Can I customize the achievement rules?**  
A: Yes. The `fxp_actions` and `achievement_definitions` tables are fully configurable. You can adjust XP values, chain multipliers, and targets directly via the database without altering the PHP code.

**Q: Does the Prestige system delete my data?**  
A: No. Prestiging only resets your FXP Level to 1 and resets Mastery levels. Your lifetime statistics, unlocked achievements, and all financial data remain completely intact.

---

## Contributing

Contributions are welcome! Please follow these steps:
1. Fork the repository.
2. Create a feature branch (`git checkout -b feature/AmazingFeature`).
3. Commit your changes (`git commit -m 'Add some AmazingFeature'`).
4. Push to the branch (`git push origin feature/AmazingFeature`).
5. Open a Pull Request.

Please ensure your code adheres to the existing PSR-12 coding standards and includes appropriate comments.

---

## Bug Reports

Found a bug? Please open an issue on GitHub with the following information:
- A clear, descriptive title.
- Steps to reproduce the behavior.
- Expected vs. actual behavior.
- Your PHP/MySQL version and browser details.
- Relevant error logs from `storage/logs/`.

---

## License

This project is licensed under the **MIT License**. See the [LICENSE](LICENSE) file for details.

---

## Credits

- **[Chart.js](https://www.chartjs.org/)** for beautiful, responsive data visualization.
- **[mPDF](https://mpdf.github.io/)** for robust, HTML-to-PDF conversion.
- **[PhpSpreadsheet](https://phpspreadsheet.readthedocs.io/)** for enterprise Excel file generation.
- **[Font Awesome](https://fontawesome.com/)** for comprehensive vector iconography.

---

## Acknowledgements

Special thanks to the open-source PHP community for the robust tooling that makes lightweight, dependency-minimal frameworks possible. This project was built with a commitment to clean code, user empowerment, and financial literacy.

---

<div align="center">
  <sub>Built with ❤️ and strict financial discipline.</sub><br>
  <sub>© 2026 ExpensePro. All rights reserved.</sub>
</div>