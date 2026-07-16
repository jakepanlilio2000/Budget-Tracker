# Architecture Documentation

## Introduction

ExpensePro is built on a custom, lightweight Vanilla PHP framework utilizing the Model-View-Controller (MVC) architectural pattern. This document provides a comprehensive overview of the system's architecture, design decisions, and internal mechanics, serving as a guide for developers looking to understand, maintain, or extend the platform.

---

## Architectural Goals

The architecture of ExpensePro is driven by the following core principles:
- **Maintainability:** Clean separation of concerns ensures that changes in one layer do not cascade unexpectedly to others.
- **Scalability:** Stateless design and optimized database queries allow the application to handle growing datasets efficiently.
- **Separation of Concerns:** Strict adherence to MVC boundaries, with complex logic encapsulated in dedicated Service classes.
- **Performance:** Minimal framework overhead, aggressive query optimization, and strategic caching.
- **Security:** Defense-in-depth approach, utilizing prepared statements, strict validation, and secure session management.
- **Extensibility:** Modular service design allows new features to be added without modifying core logic.
- **Mobile-First:** The frontend architecture prioritizes responsive, touch-friendly layouts that scale up to desktop environments.

---

## Technology Stack

| Component | Technology | Purpose |
| :--- | :--- | :--- |
| **Backend Language** | PHP 8.1+ | Core application logic, strict typing, MVC routing |
| **Web Server** | Apache 2.4+ | HTTP request handling, URL rewriting |
| **Database** | MySQL 8.0+ / MariaDB 10.5+ | Relational data storage, JSON support for flexible configs |
| **Dependency Manager** | Composer | Management of third-party PHP libraries (mPDF, PhpSpreadsheet) |
| **Frontend Scripting** | Vanilla JavaScript | Zero-dependency, high-performance DOM manipulation and AJAX |
| **Markup** | HTML5 | Semantic structure and accessibility |
| **Styling** | CSS3 | Custom properties (variables) for theming, Flexbox/Grid for layout |
| **Visualization** | Chart.js | Interactive, responsive data visualization |

---

## MVC Overview

The application strictly adheres to the Model-View-Controller pattern:

- **Models:** Represent the data layer. They encapsulate database interactions, enforce data integrity, and provide methods for retrieving and persisting records.
- **Views:** Represent the presentation layer. They are responsible for rendering HTML, utilizing output buffering to inject dynamic data passed from the controllers.
- **Controllers:** Represent the application layer. They handle HTTP requests, validate input, orchestrate business logic via Services, and pass sanitized data to Views.

---

## Request Lifecycle

The typical flow of a user request through ExpensePro is as follows:

1. **Browser:** Sends an HTTP request to the server.
2. **Apache:** Receives the request. The `.htaccess` file in the `public/` directory rewrites all non-file/directory requests to `index.php`.
3. **Front Controller (`public/index.php`):** Bootstraps the application, loads the Composer autoloader, initializes the Database and Session, and instantiates the Router.
4. **Router:** Parses the requested URI and HTTP method, matching it against defined routes to determine the target Controller and Method.
5. **Controller:** Executes the matched method. It validates CSRF tokens, sanitizes input, and calls the necessary Models or Services.
6. **Model / Service:** Executes business logic. Models interact with the Database via PDO. Services handle complex, multi-model operations (e.g., calculating FXP).
7. **Database:** Processes the SQL query and returns the result set.
8. **View:** The Controller passes the processed data to a View file. The View renders the HTML template.
9. **Response:** The rendered HTML is sent back through the Front Controller to the Browser.

---

## Folder Structure

The project is organized to separate public assets from core logic and configuration:

- `app/`: Contains the core application logic.
  - `Core/`: Framework foundation (Router, Database, Auth, CSRF, Cache).
  - `Controllers/`: HTTP request handlers.
  - `Helpers/`: Global utility functions (e.g., `e()`, `url()`).
  - `Models/`: Data access objects.
  - `Services/`: Complex business logic engines.
  - `Views/`: HTML templates and shared layouts.
- `config/`: Application configuration files (database credentials, app settings). *Must not be publicly accessible.*
- `database/`: Version-controlled SQL schema migrations and seeders.
- `public/`: The document root. Contains `index.php` (entry point), and static assets (`assets/css`, `assets/js`).
- `storage/`: Writable directories for application logs (`logs/`) and temporary files (`tmp/`).
- `vendor/`: Composer dependencies (auto-generated, not committed).
- `docs/`: Project documentation.

---

## Routing

Routing is handled by a centralized, regex-based Router defined in `public/index.php`. Routes map HTTP verbs and URI patterns to specific Controller methods. This ensures a single entry point for all requests, facilitating centralized authentication checks and CSRF validation.

---

## Controllers

Controllers act as the traffic cops of the application. They do not contain complex business logic. Instead, they:
1. Verify user authentication and authorization.
2. Validate CSRF tokens for POST/PUT/DELETE requests.
3. Sanitize and cast incoming `$_POST` and `$_GET` data.
4. Delegate processing to Models or Services.
5. Set flash messages (Session) for user feedback.
6. Redirect or render a View.

---

## Models

Models provide a clean API for interacting with the database. They utilize PDO (PHP Data Objects) with prepared statements for all queries, preventing SQL injection. Models handle CRUD operations and often include static methods for complex queries specific to that entity.

---

## Views

Views are written in raw PHP/HTML. They use `ob_start()` and `ob_get_clean()` to capture rendered content, which is then passed to a master layout (`layouts/app.php`). This ensures consistent headers, footers, and navigation across the application. All dynamic output is escaped using the `e()` helper to prevent XSS.

---

## Database Layer

The database is designed with strict relational integrity:
- **Normalization:** Data is normalized to reduce redundancy (e.g., separate tables for Accounts, Categories, and Transactions).
- **Foreign Keys:** Enforced at the database level to maintain referential integrity.
- **Indexing:** Critical columns (e.g., `user_id`, `transaction_date`, `status`) are indexed to optimize aggregation and filtering queries.
- **Soft Deletes:** Financial records use a `deleted_at` timestamp instead of hard deletion, preserving audit trails and allowing for data recovery.
- **JSON Columns:** Used sparingly for highly variable, non-relational data (e.g., `dashboard_config`, `allowances` in salaries) to maintain schema flexibility without sacrificing relational integrity.

---

## Services

Services encapsulate complex business logic that spans multiple models. Examples include:
- `AchievementEngine`: Evaluates user actions against dynamic rules.
- `FxpEngine`: Calculates and awards Financial Experience points.
- `CashFlowService`: Projects future balances based on scheduled events.
- `TimelineService`: Aggregates events from various modules into a unified chronological feed.

By isolating this logic in Services, Controllers remain thin, and the business logic can be reused or tested independently.

---

## Dashboard Architecture

The Dashboard is built on a modular widget system. 
- **State Management:** The layout configuration (widget order, visibility, size) is stored as a JSON object in the `user_preferences` table.
- **Rendering:** The `DashboardController` fetches this configuration and passes it to the view. The view conditionally renders widgets based on the configuration.
- **Interactivity:** Vanilla JavaScript handles the HTML5 Drag-and-Drop API. When a user saves a layout, an AJAX POST request updates the JSON configuration in the database.

---

## Achievement Engine

The Achievement Engine is a highly scalable, rule-based progression system:
- **Dynamic Achievements:** Rules are stored in the `achievement_definitions` table. The engine evaluates these rules in batch using optimized SQL queries.
- **Endless Chains:** Instead of creating thousands of static rows, chain achievements use a `chain_multiplier` to dynamically calculate the next target tier (e.g., Target * 2.5) upon completion.
- **FXP & Leveling:** Actions trigger the `FxpEngine`, which awards XP based on configurable weights. Levels are calculated dynamically using a scaling formula (`Level = floor((FXP / 100) ^ (2/3)) + 1`), allowing for infinite progression.
- **Mastery & Prestige:** Independent mastery tracks are updated alongside global FXP. The Prestige system resets levels but applies a permanent multiplier to future FXP gains, stored in the `user_fxp_stats` table.

---

## Analytics Architecture

Analytics are generated on-the-fly using aggregated SQL queries. 
- **Data Aggregation:** Queries use `GROUP BY` and `SUM/COUNT` functions to process raw transaction data into meaningful metrics (e.g., spending by category, monthly trends).
- **Visualization:** The aggregated JSON data is passed to the frontend, where Chart.js renders interactive, responsive charts.
- **Caching:** Heavy analytical queries are cached using the `Cache` service to prevent database overload on subsequent page loads.

---

## Timeline Architecture

The Financial Timeline utilizes an event-sourcing-like approach. Whenever a significant action occurs (e.g., a transaction is created, a bill is paid), the relevant Controller calls `TimelineService::logEvent()`. This inserts a standardized record into the `timeline_events` table, which includes the module, action type, description, and associated financial amount. This provides a unified, chronological feed of all user activity.

---

## Cash Flow Forecast

The Cash Flow Forecast engine integrates data from multiple modules:
1. It fetches current account balances.
2. It queries upcoming `bills`, `pending_ledger` items, and expected `salaries` within the forecast window.
3. It calculates the net impact of these scheduled events on a daily basis, projecting the final balance and flagging potential cash shortages.

---

## Calendar Integration

The Financial Calendar acts as an aggregator. The `CalendarService` queries distinct modules (Bills, Salaries, Pending Ledger) for time-bound events, normalizes them into a standard event structure (title, date, color, icon), and merges them into a single chronological array for the frontend calendar library to render.

---

## Theme System

The theme system is built entirely on CSS Custom Properties (Variables):
- **Light/Dark Mode:** Toggling the theme updates a `data-theme` attribute on the `<html>` tag. CSS variables automatically swap color palettes based on this attribute.
- **Privacy Blur:** A `.sensitive-data` class applies a CSS `filter: blur()`. JavaScript toggles a `.revealed` class on hover/click to remove the blur.
- **Compact/Zen Modes:** These modes apply utility classes to the `<body>` tag, which override default padding, font sizes, or hide specific layout containers via CSS.

---

## Security Architecture

ExpensePro employs a defense-in-depth security strategy:
- **Authentication & Authorization:** Session-based authentication with Role-Based Access Control (RBAC) to restrict administrative routes.
- **CSRF Protection:** Every state-changing form includes a cryptographically secure token, validated by the Controller before processing.
- **XSS Prevention:** All dynamic output in Views is passed through the `e()` (htmlspecialchars) helper function.
- **SQL Injection Prevention:** 100% of database interactions use PDO prepared statements.
- **Input Validation:** Strict server-side validation and type casting (`declare(strict_types=1)`) are enforced on all incoming data.
- **Audit Logging:** Destructive actions (e.g., data deletion, balance adjustments) are immutably logged in the `audit_logs` or `timeline_events` tables.

---

## Performance

- **Query Optimization:** Complex queries are optimized using proper indexing and avoiding `SELECT *` where possible.
- **Caching:** The `Cache` service stores the results of expensive computations (e.g., dashboard stats, lifetime analytics) in memory/files for a configurable duration.
- **Asset Optimization:** The frontend uses Vanilla JS and CSS without heavy framework overhead, ensuring rapid Time-to-Interactive (TTI).
- **Lazy Evaluation:** Heavy services (like the Achievement Engine) are triggered post-transaction to ensure the primary user action remains fast.

---

## Extensibility

The modular Service architecture allows for easy extensibility. To add a new feature (e.g., a new financial module):
1. Create a new Model for database interactions.
2. Create a new Service for business logic.
3. Create a Controller to handle HTTP requests.
4. Hook into existing Services (e.g., call `TimelineService::logEvent()` or `FxpEngine::award()`) to integrate with the Timeline and Achievement systems without modifying their core code.

---

## Design Principles

- **SOLID Principles:** Classes and services are designed to have single responsibilities and depend on abstractions where appropriate.
- **DRY (Don't Repeat Yourself):** Shared logic is abstracted into Helpers and Services.
- **Convention over Configuration:** Standardized naming conventions for controllers, models, and views reduce cognitive load.

---

## Future Architecture Considerations

*The following are conceptual ideas for future major versions and are not currently implemented:*

- **Plugin Architecture:** A modular system allowing third-party developers to inject routes, views, and services without modifying the core codebase.
- **REST API:** A dedicated API layer using token-based authentication (OAuth2/JWT) for mobile app integration and third-party webhooks.
- **Cloud Synchronization:** An optional, encrypted sync layer for multi-device state management.
- **Horizontal Scalability:** Refactoring the caching and session layers to support Redis/Memcached and database read replicas for high-traffic deployments.

---

## References

For more detailed information on specific aspects of the project, please refer to the following documentation:

- [README.md](../README.md) - Project overview and feature highlights.
- [INSTALL.md](INSTALL.md) - Deployment and setup instructions.
- [CONTRIBUTING.md](../CONTRIBUTING.md) - Guidelines for code contributions.
- [ROADMAP.md](../ROADMAP.md) - Future development plans.
- [SECURITY.md](../SECURITY.md) - Security policies and vulnerability reporting.