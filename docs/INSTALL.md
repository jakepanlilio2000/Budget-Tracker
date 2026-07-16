# Installation Guide

## Introduction

Welcome to the ExpensePro installation guide. This document provides comprehensive, step-by-step instructions for deploying ExpensePro in both local development and production environments. Whether you are setting up a local sandbox or deploying to a live server, this guide will walk you through the entire process.

---

## System Requirements

Before beginning the installation, ensure your server or local environment meets the following minimum requirements:

| Component | Minimum Requirement | Recommended |
| :--- | :--- | :--- |
| **PHP Version** | 8.1 | 8.2 or 8.3 |
| **Web Server** | Apache 2.4+ (with `mod_rewrite`) | Nginx 1.20+ or Apache 2.4+ |
| **Database** | MySQL 8.0+ or MariaDB 10.5+ | MySQL 8.0+ |
| **Composer** | 2.0+ | Latest stable version |
| **Disk Space** | 500 MB | 1 GB+ (for logs and backups) |
| **RAM** | 512 MB | 1 GB+ |

### Required PHP Extensions
Ensure the following PHP extensions are installed and enabled:
- `pdo` and `pdo_mysql`
- `mbstring`
- `xml`
- `curl`
- `zip` (required by Composer and PhpSpreadsheet)
- `gd` (optional, for image processing)
- `json`
- `openssl`

---

## Before You Begin

To successfully install ExpensePro, you should have the following software installed and configured on your system:
1. A local or remote web server environment (e.g., XAMPP, MAMP, Laravel Valet, or a Linux VPS).
2. A running MySQL or MariaDB database service.
3. PHP installed via CLI (Command Line Interface).
4. Composer installed globally.
5. Git installed for version control.

---

## Clone the Repository

First, obtain the project files by cloning the repository from GitHub. Open your terminal and run:

```bash
git clone https://github.com/jakepanlilio2000/Budget-Tracker.git expensepro
cd expensepro
```

---

## Install Dependencies

ExpensePro uses Composer to manage its PHP dependencies. Run the following command in the root directory of the project to install all required packages (such as mPDF and PhpSpreadsheet):

```bash
composer install
```

**Verification:** Ensure the command completes without errors. If you encounter memory limit errors, try running `php -d memory_limit=-1 /usr/local/bin/composer install`.

---

## Database Setup

You will need an empty database for ExpensePro to store its data. 

1. Log in to your MySQL/MariaDB server:
   ```bash
   mysql -u root -p
   ```
2. Create the database and a dedicated user:
   ```sql
   CREATE DATABASE [YOUR_DATABASE_NAME] CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
   CREATE USER '[YOUR_DATABASE_USER]'@'localhost' IDENTIFIED BY '[YOUR_STRONG_PASSWORD]';
   GRANT ALL PRIVILEGES ON [YOUR_DATABASE_NAME].* TO '[YOUR_DATABASE_USER]'@'localhost';
   FLUSH PRIVILEGES;
   EXIT;
   ```

*Note: We strongly recommend using `utf8mb4` and `utf8mb4_unicode_ci` to ensure full support for emojis and international characters.*

---

## Application Configuration

ExpensePro requires a configuration file to connect to your database and define application settings.

1. Copy the example configuration file:
   ```bash
   cp config/config.example.php config/config.php
   ```
2. Open `config/config.php` in your preferred text editor and update the following values:
   - **Database Credentials:** Update `DB_HOST`, `DB_PORT`, `DB_NAME`, `DB_USER`, and `DB_PASS` with the details created in the previous step.
   - **Base URL:** Set the `APP_URL` to the domain or local URL where the application will be hosted (e.g., `http://localhost:8000` or `https://finance.yourdomain.com`).
   - **Timezone:** Set the `APP_TIMEZONE` to your local timezone (e.g., `UTC`, `America/New_York`, `Asia/Manila`).
   - **Base Currency:** Set the default currency code (e.g., `USD`, `EUR`, `PHP`).
   - **Session Configuration:** Ensure session settings are secure, especially for production.

---

## File Permissions

The application requires write permissions for specific directories to store logs, temporary files, and backups.

**For Development Environments:**
```bash
chmod -R 755 storage/
chmod -R 755 config/
```

**For Production Environments:**
It is best practice to restrict permissions as much as possible. The web server user (e.g., `www-data`) must own the writable directories.
```bash
sudo chown -R www-data:www-data storage/
sudo chmod -R 755 storage/
sudo chown -R www-data:www-data config/
sudo chmod -R 755 config/
```
*Ensure that `config/config.php` is not publicly accessible via the web server.*

---

## Database Initialization

ExpensePro uses SQL migration files to build the database schema. 

1. Navigate to the `database/migrations/` directory.
2. Import the SQL files into your database **in numerical order** (e.g., `001_...sql`, `002_...sql`, etc.). You can do this via phpMyAdmin, MySQL Workbench, or the CLI:
   ```bash
   mysql -u [YOUR_DATABASE_USER] -p [YOUR_DATABASE_NAME] < database/migrations/001_initial_schema.sql
   mysql -u [YOUR_DATABASE_USER] -p [YOUR_DATABASE_NAME] < database/migrations/002_...sql
   # Repeat for all migration files
   ```
3. **(Optional) Seed Achievements:** To populate the gamification engine with initial achievement rules, run the seeder script:
   ```bash
   php bin/seed_achievements.php
   ```

---

## Web Server Configuration

### Apache Configuration

ExpensePro requires URL rewriting to route all requests through the `public/index.php` entry point. 

1. **Document Root:** Ensure your web server's document root is pointed directly to the `public/` directory, **not** the root of the project.
2. **Enable mod_rewrite:** Ensure the `rewrite` module is enabled in your Apache configuration.
3. **Virtual Host Example:**
   ```apache
   <VirtualHost *:80>
       ServerName finance.yourdomain.com
       DocumentRoot /path/to/expensepro/public

       <Directory /path/to/expensepro/public>
           AllowOverride All
           Require all granted
       </Directory>

       ErrorLog ${APACHE_LOG_DIR}/expensepro_error.log
       CustomLog ${APACHE_LOG_DIR}/expensepro_access.log combined
   </VirtualHost>
   ```
4. Ensure an `.htaccess` file exists in the `public/` directory (provided by default in the repository) to handle fallback routing.

---

## First Launch

Once the server is configured and running, navigate to your Base URL in a web browser. 

- If the application includes an interactive installer, follow the on-screen prompts to create your first administrator account.
- If there is no installer, navigate directly to the `/login` or `/register` route to create your initial user account.

---

## Default Setup Checklist

After your first login, complete the following setup steps to tailor the application to your needs:

- [ ] **Database Connected:** Verify no database errors appear on the dashboard.
- [ ] **Application Loads:** Ensure all CSS and JavaScript assets load correctly.
- [ ] **Authentication Works:** Test logging out and logging back in.
- [ ] **Dashboard Loads:** Verify the Dashboard Builder widgets render correctly.
- [ ] **Currency Configured:** Go to Preferences and confirm your Base Currency is set.
- [ ] **Theme Configured:** Toggle between Light and Dark mode to ensure theme persistence.
- [ ] **Backup Configured:** Navigate to Settings and test a small JSON backup export.

---

## Updating the Application

When a new version of ExpensePro is released, follow this workflow to update safely:

1. **Backup:** Always create a full backup of your database and `config/` directory before updating.
2. **Pull Changes:** Fetch the latest code from the repository.
   ```bash
   git pull origin main
   ```
3. **Update Dependencies:** Install any new or updated Composer packages.
   ```bash
   composer install --no-dev --optimize-autoloader
   ```
4. **Apply Database Changes:** Check the `database/migrations/` folder for any new SQL files and import them into your database in order.
5. **Clear Cache:** If applicable, clear any application caches.
6. **Verify:** Test the application to ensure all features are functioning as expected.

---

## Troubleshooting

If you encounter issues during installation, consult this guide:

- **Database Connection Issues:** Double-check the credentials in `config/config.php`. Ensure the database user has the correct privileges and the database exists.
- **Composer Issues:** If packages fail to install, ensure you have the required PHP extensions (especially `zip` and `curl`) and sufficient memory limits.
- **Permission Problems:** If you see "Permission denied" or "Failed to open stream" errors, verify that the `storage/` and `config/` directories are writable by the web server user.
- **Missing PHP Extensions:** A blank page or fatal error often indicates a missing extension. Check your `phpinfo()` output against the "System Requirements" table.
- **Apache Rewrite Issues (404 Not Found):** If sub-routes return 404 errors, ensure `AllowOverride All` is set in your Apache config and `mod_rewrite` is enabled.
- **500 Internal Server Error:** Check the `storage/logs/` directory or your web server's error log for specific PHP fatal errors.
- **Blank Page:** Enable error reporting temporarily in your PHP configuration to reveal hidden syntax or fatal errors.

---

## Production Deployment Recommendations

For a secure and performant production environment, implement the following:

- **HTTPS:** Always use SSL/TLS certificates (e.g., via Let's Encrypt) to encrypt data in transit.
- **Secure Permissions:** Restrict file permissions. The `config/` directory should not be readable by the public.
- **Regular Backups:** Automate database dumps and file backups to an off-site location.
- **PHP OPcache:** Enable OPcache in your `php.ini` to significantly improve PHP execution performance.
- **Error Logging:** Disable `display_errors` in production and ensure `log_errors` is enabled, writing to a secure log file.
- **Monitoring:** Implement server monitoring (e.g., UptimeRobot, New Relic) to track performance and downtime.
- **Database Backups:** Schedule automated daily backups of your MySQL/MariaDB database.

---

## Verification

To confirm a successful installation, run through this final checklist:

- [ ] The application loads without PHP errors or warnings.
- [ ] User registration and login function correctly.
- [ ] A test transaction can be created and saved to the database.
- [ ] The Dashboard displays data and charts correctly.
- [ ] Backup and Restore features execute without timing out or failing.
- [ ] The application is accessible via HTTPS (if in production).

---

## Additional Resources

For more information on using, contributing to, and securing ExpensePro, please refer to the following documentation:

- [README.md](../README.md) - Project overview, features, and architecture.
- [CONTRIBUTING.md](../CONTRIBUTING.md) - Guidelines for contributing code and documentation.
- [SECURITY.md](../SECURITY.md) - Security policy and vulnerability reporting.
- [ROADMAP.md](../ROADMAP.md) - Future development plans and feature pipeline.
- [CHANGELOG.md](../CHANGELOG.md) - Version history and release notes.
- [CODE_OF_CONDUCT.md](../CODE_OF_CONDUCT.md) - Community standards and expectations.