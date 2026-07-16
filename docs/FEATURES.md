# Feature Reference

Welcome to the official feature catalog for ExpensePro. This document provides a comprehensive overview of every major capability within the platform. ExpensePro is an enterprise-grade Personal Finance & Small Business Management platform designed to empower individuals and small businesses with professional-grade financial tracking, analytics, and gamified discipline.

---

## Feature Categories

### Dashboard

The Dashboard serves as the central command center for your financial life.

- **Dashboard Overview:** A high-level summary of your financial health, displaying key metrics, recent activity, and upcoming obligations at a glance.
- **Dashboard Builder:** A fully customizable, drag-and-drop interface that allows you to rearrange, resize, and hide widgets to suit your workflow. Layouts are saved automatically to your user preferences.
- **Widgets:** Modular components displaying specific data sets, including Financial Summary, Mastery & FXP, Savings Vaults, Recent Timeline, Cash Flow Forecast, Upcoming Events, and Achievements.
- **KPI Cards:** Visual indicators for critical metrics such as Monthly Income, Monthly Expenses, Net Cash Flow, and Total Savings.
- **Quick Actions:** Fast-access buttons for common tasks like adding a transaction, creating a budget, or depositing into a vault.
- **Dashboard Layouts:** Support for multiple saved layouts, allowing you to switch between different views (e.g., "Overview," "Analytics," "Planning").
- **Responsive Dashboard:** The layout automatically adapts to mobile, tablet, and desktop screens, ensuring a seamless experience on any device.

---

### Financial Management

Core tools for tracking and managing your money.

- **Expense Tracking:** Log daily expenses with category splitting, notes, and receipt attachments (future). Supports multiple currencies.
- **Income Tracking:** Record income sources, including salaries, freelance work, and investments.
- **Salary Management:** Detailed payslip generation with employer tracking, basic salary, bonuses, overtime, allowances, deductions, and net pay calculations.
- **Accounts:** Manage multiple financial institutions (banks, credit cards, cash). Supports various account types and real-time balance tracking.
- **Transfers:** Record transfers between accounts without double-counting income or expenses.
- **Current Balance Adjustment:** Auditable balance corrections that create system transactions to preserve history while fixing discrepancies.
- **Multi-Currency:** Set a Base Currency for your account while logging individual transactions in different currencies.
- **Exchange Rate Handling:** Manual or automatic exchange rate entry for accurate multi-currency conversion (manual in v1.0).

---

### Budget Management

Tools to plan and control your spending.

- **Budget Planning:** Create monthly budgets for specific expense categories.
- **Envelope Budgeting:** Allocate funds to specific categories, preventing overspending in other areas.
- **Budget Monitoring:** Real-time tracking of spending against budget limits with visual progress bars.
- **Budget Progress:** Visual indicators showing percentage of budget used, remaining amount, and days left in the cycle.
- **Budget Forecasting:** Predictive alerts warning you if you are on track to exceed your budget based on current spending velocity.

---

### Bills & Recurring Payments

Manage your recurring financial obligations.

- **Bills:** Track recurring bills with names, amounts, frequencies, and due dates.
- **Recurring Bills:** Automatic rollover of due dates upon payment, supporting weekly, monthly, quarterly, and yearly frequencies.
- **Partial Payments:** Record partial payments against a bill, with the system tracking the remaining balance.
- **Full Payments:** Mark bills as paid, triggering achievement checks and streak updates.
- **Overdue Tracking:** Visual alerts and notifications for bills that have passed their due date without payment.
- **Custom Penalties:** Define penalty rates (fixed or percentage) for late bill payments to track the true cost of delinquency.

---

### Savings Vault

Goal-oriented savings management.

- **Multiple Vaults:** Create unlimited savings vaults for different goals (e.g., "Emergency Fund," "Vacation," "New Car").
- **Deposit:** Add funds to a vault, updating the progress bar and estimated completion date.
- **Withdraw:** Remove funds from a vault with optional notes explaining the withdrawal.
- **Savings Goals:** Set target amounts and deadlines for each vault.
- **Goal Completion:** Celebrate milestones when a vault reaches its target, unlocking achievements.
- **Goal Cancellation:** Archive or cancel vaults that are no longer relevant, preserving the history.
- **Progress Tracking:** Visual progress bars, percentage completion, and estimated months to goal based on current savings velocity.

---

### Financial Planning

Tools for strategic financial foresight.

- **Cash Flow Forecast:** Predictive modeling of your account balances over the next 7 to 30 days, based on scheduled bills, salaries, and pending items.
- **Financial Timeline:** A chronological, color-coded feed of all financial events, system actions, and administrative changes.
- **Financial Calendar:** A visual calendar view aggregating bills, salaries, and pending ledger items by date.
- **Monthly Review:** Automated summary of the past month's income, expenses, budget performance, and savings milestones.
- **Yearly Review:** Comprehensive annual financial report, including total income, total expenses, net worth change, and goal completion rates.
- **Financial Health:** A calculated score or status indicator based on your savings ratio, debt-to-income, and budget adherence.
- **Goal Tracking:** Monitor progress toward long-term financial objectives across multiple vaults and investment accounts.

---

### Analytics

Business-intelligence-grade insights into your financial behavior.

- **Advanced Analytics:** A dedicated dashboard featuring interactive charts and deep-dive metrics.
- **Interactive Charts:** Powered by Chart.js, supporting zoom, hover tooltips, and dynamic filtering.
- **Reports:** Generate and export financial reports in PDF, XLSX, CSV, and JSON formats.
- **Financial Insights:** Automated textual analysis highlighting spending anomalies, savings opportunities, and budget risks.
- **Trend Analysis:** Line and area charts showing income and expense trends over time (6 months, 1 year, custom range).
- **Category Analysis:** Donut and pie charts visualizing spending distribution by category.
- **Cash Flow Analysis:** Bar charts comparing monthly income vs. expenses and net cash flow.
- **Forecast Analysis:** Visual representation of the cash flow forecast with confidence intervals.

---

### Achievement Engine

A gamified progression system designed to motivate financial discipline.

- **Dynamic Achievements:** Database-driven rules that evaluate user behavior in real-time.
- **Endless Achievement Chains:** Milestones that automatically scale (e.g., 10 → 25 → 62 transactions) using configurable multipliers, ensuring infinite progression.
- **Financial Experience (FXP):** Global experience points awarded for every meaningful action (logging expenses, paying bills, saving money).
- **Endless Leveling:** Infinite level progression using a dynamic scaling formula (`Level = floor((FXP / 100) ^ (2/3)) + 1`).
- **Mastery Categories:** Independent leveling tracks for Expense, Income, Savings, Budget, and Consistency behaviors.
- **Titles:** Dynamic monikers (e.g., "Beginner Saver" → "Financial Architect") that evolve as you level up and prestige.
- **Streak System:** Tracks consecutive days of logins, transactions, and bill payments, with current and best streak records.
- **Prestige:** An optional "New Game+" reset at Level 50 that awards a permanent XP multiplier, a Prestige Star, and exclusive titles while preserving lifetime stats.
- **Achievement Dashboard:** A dedicated page displaying your level, FXP progress, mastery tracks, streaks, and unlocked achievements.
- **Statistics:** Comprehensive lifetime stats including total transactions, income, savings, goals completed, and longest streak.
- **Rewards:** Visual celebrations, notification toasts, and profile badges for major milestones.

---

### Financial Simulators

Safe environments for modeling financial decisions.

- **Investment Sandbox:** Simulate compound growth, stock market returns, and portfolio diversification without risking real money.
- **Loan Sandbox:** Model loan amortization, interest costs, and payoff strategies for mortgages, car loans, or student debt.
- **Budget Sandbox:** Test different budget allocations and scenarios to see their potential impact on your cash flow before applying them to your live budget.

*Note: These simulators do not modify your live financial data unless you explicitly choose to "Apply" the results.*

---

### Personalization

Tailor the application to your preferences and workflow.

- **Theme System:** Seamlessly switch between Light and Dark modes, or follow your OS system preference.
- **Light Theme:** A clean, high-contrast interface optimized for daylight viewing.
- **Dark Theme:** A low-light interface reducing eye strain and saving battery on OLED screens.
- **Follow System:** Automatically matches your operating system's theme setting.
- **Glassmorphism:** Modern UI design with translucent, blurred backgrounds for a premium feel.
- **Privacy Blur:** Instantly obfuscates all sensitive numerical data with a CSS blur filter, revealable on hover or click.
- **Compact Mode:** Reduces padding and font sizes for high-density data viewing on large screens.
- **Zen Mode:** Hides all non-essential UI elements (sidebars, headers) for distraction-free data entry.
- **Accent Colors:** Customize the primary color theme of the application (Blue, Green, Purple, etc.).
- **Preferences:** A central settings page for managing currency, theme, privacy, and notification settings.

---

### Backup & Restore

Enterprise-grade data portability and disaster recovery.

- **ZIP Backups:** Comprehensive export of all financial data, configurations, and achievement states in a single compressed file.
- **Restore:** Secure, password-protected import of backup files, safely truncating existing data and rebuilding the schema.
- **Backup History:** (Future) Maintain a log of previous backup dates and file sizes.
- **Scheduled Backups:** (Future) Automate daily or weekly backups to local storage or cloud providers.

---

### Search & Navigation

Find what you need, when you need it.

- **Global Search:** Instant, cross-module search across transactions, bills, vaults, accounts, and timeline events.
- **Sidebar Navigation:** A collapsible, responsive menu providing access to all major modules.
- **Dashboard Navigation:** Breadcrumb trails and quick links for moving between dashboard widgets and detailed views.
- **Timeline Search:** Filter the financial timeline by date range, module, or keyword.
- **Calendar Search:** Jump to specific dates or filter calendar events by type (bills, salaries).

---

### Security

Your financial data is protected by industry-standard practices.

- **Authentication:** Secure login with password hashing (Bcrypt/Argon2).
- **Authorization:** Role-Based Access Control (RBAC) restricting administrative features.
- **Sessions:** Secure session handling with `HttpOnly`, `Secure`, and `SameSite` cookies.
- **CSRF Protection:** Cryptographically secure tokens on all state-changing forms.
- **XSS Prevention:** Strict output escaping on all user-generated content.
- **SQL Injection Prevention:** 100% usage of PDO prepared statements.
- **Audit Logging:** Immutable records of administrative and destructive actions.

---

### Mobile Experience

Designed with a mobile-first philosophy.

- **Responsive Layouts:** Fluid grids and flexbox layouts that adapt from 320px mobile screens to 4K desktops.
- **Touch-Friendly Targets:** Minimum 44x44px touch targets for all interactive elements.
- **Optimized Navigation:** Collapsible menus and bottom navigation bars for easy thumb access.
- **Mobile Dashboards:** Simplified widget views optimized for small screens.
- **PWA Ready:** (Future) Progressive Web App support for offline access and home screen installation.

---

### Performance

Built for speed and efficiency.

- **Query Optimization:** Indexed database columns and optimized SQL aggregations.
- **Lazy Loading:** Heavy services (like the Achievement Engine) are triggered asynchronously or post-transaction.
- **Asset Optimization:** Vanilla JavaScript and CSS ensure zero framework overhead.
- **Caching:** Strategic caching of dashboard statistics and lifetime metrics to prevent redundant database hits.
- **Efficient Rendering:** Virtual scrolling (future) for long lists like the Financial Timeline.

---

## Feature Comparison Table

| Feature Category | Feature | Availability | Notes |
| :--- | :--- | :--- | :--- |
| **Dashboard** | Dashboard Builder | ✅ Implemented | Drag-and-drop widget layout |
| **Financial** | Expense/Income Tracking | ✅ Implemented | Multi-currency support |
| **Financial** | Salary Management | ✅ Implemented | Payslip generation |
| **Financial** | Accounts & Transfers | ✅ Implemented | Auditable adjustments |
| **Budget** | Budget Planning | ✅ Implemented | Category-based limits |
| **Bills** | Recurring Bills | ✅ Implemented | Penalty tracking |
| **Savings** | Savings Vault | ✅ Implemented | Goal tracking |
| **Planning** | Cash Flow Forecast | ✅ Implemented | 7-30 day projection |
| **Planning** | Financial Calendar | ✅ Implemented | Aggregated events |
| **Analytics** | Advanced Analytics | ✅ Implemented | Interactive charts |
| **Gamification** | Achievement Engine | ✅ Implemented | Endless chains, FXP, Prestige |
| **Simulators** | Investment/Loan Sandbox | ✅ Implemented | Non-destructive modeling |
| **Personalization** | Theme System | ✅ Implemented | Light/Dark/Privacy modes |
| **System** | Backup & Restore | ✅ Implemented | JSON/XLSX/CSV/PDF |
| **System** | Global Search | ✅ Implemented | Cross-module search |
| **Mobile** | Responsive UI | ✅ Implemented | Mobile-first design |
| **Future** | PWA Offline Support | 🔮 Planned | Service workers |
| **Future** | Bank Sync API |  Planned | Plaid/Teller integration |
| **Future** | Multi-User Households | 🔮 Planned | Shared vaults/budgets |

---

## Feature Highlights

- **Endless Achievement Engine:** The only personal finance app with an infinite, dynamically scaling progression system that never runs out of goals.
- **Enterprise-Grade Security:** 100% SQL injection prevention, CSRF protection, and audit logging out of the box.
- **Dashboard Builder:** Fully customizable, drag-and-drop dashboard that adapts to your workflow, not the other way around.
- **Financial Intelligence:** Advanced analytics and cash flow forecasting that provide actionable insights, not just raw data.
- **Data Sovereignty:** Self-hosted architecture ensures your financial data never leaves your server.

---

## Planned Future Enhancements

*The following features are under consideration for future releases and do not represent guaranteed functionality:*

- **PWA Offline Support:** Service workers for offline transaction logging with background sync.
- **Open Banking API:** Plaid or Teller integration for automatic transaction importing.
- **Multi-User Households:** Shared vaults and budgets with granular permission roles.
- **Recurring Transactions:** Native auto-generation of scheduled income/expenses.
- **Webhooks:** Integration with external notification services (Discord, Slack, Email).
- **REST API:** Public API for third-party app development.
- **Plugin System:** Modular architecture for community-developed extensions.

---

## Related Documentation

- [README.md](../README.md) - Project overview and quick start.
- [INSTALL.md](INSTALL.md) - Detailed installation and configuration guide.
- [ARCHITECTURE.md](ARCHITECTURE.md) - Technical architecture and design patterns.
- [ROADMAP.md](../ROADMAP.md) - Future development plans.
- [FAQ.md](FAQ.md) - Frequently asked questions and troubleshooting.