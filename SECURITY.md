# Security Policy

## Supported Versions

We take the security of ExpensePro seriously. The following versions of the project are currently being supported with security updates:

| Version | Supported          | Security Updates |
| :------ | :----------------- | :--------------- |
| 2.x.x   | :white_check_mark: | Active           |
| 1.x.x   | :x:                | End of Life      |

*We strongly recommend always running the latest stable release to ensure you have the most recent security patches.*

## Reporting a Vulnerability

If you discover a security vulnerability within ExpensePro, please send an email to [security@expensepro.example.com](mailto:security@expensepro.example.com). **Do not** open a public GitHub issue for security vulnerabilities.

When reporting, please include the following information to help us triage and resolve the issue quickly:
- A clear description of the vulnerability.
- Step-by-step instructions to reproduce the issue.
- The potential impact of the vulnerability.
- Your PHP, MySQL, and OS environment details.
- Any suggested fixes or proof-of-concept code (optional but appreciated).

### Expected Timeline & Responsible Disclosure
- **Acknowledgment:** We will acknowledge receipt of your report within **48 hours**.
- **Investigation:** We will investigate and validate the issue within **7 days**.
- **Resolution:** We aim to release a patch for confirmed vulnerabilities within **14 days**, depending on complexity.
- **Disclosure:** We kindly request that you do not publicly disclose the vulnerability until we have released a fix and notified our user base. We will credit you in the release notes (unless you prefer to remain anonymous).

## Security Best Practices

To ensure the highest level of security when deploying ExpensePro, administrators should adhere to the following best practices:
1. **Keep PHP Updated:** Always run a supported, actively maintained version of PHP (8.1 or higher).
2. **Update Dependencies:** Regularly run `composer update` to ensure all third-party libraries (e.g., mPDF, PhpSpreadsheet) are patched against known CVEs.
3. **Use HTTPS:** Always serve the application over HTTPS in production to encrypt data in transit.
4. **Protect Credentials:** Never commit `config/config.php` or `.env` files to version control. Restrict file permissions (e.g., `chmod 600`) to the web server user.
5. **Strong Passwords:** Enforce strong, unique passwords for all user and database accounts.
6. **Regular Backups:** Utilize the built-in Backup & Restore feature or database-level snapshots to maintain secure, off-site backups.
7. **Secure Server Permissions:** Ensure the `storage/` directory is writable only by the web server, and disable directory listing on the web server.

## Security Features

ExpensePro is built with a defense-in-depth approach. The following security measures are implemented natively:
- **Password Hashing:** All user passwords are hashed using PHP's native `password_hash()` (Bcrypt/Argon2).
- **CSRF Protection:** State-changing requests require valid, cryptographically secure Cross-Site Request Forgery tokens.
- **XSS Protection:** All user-generated output is strictly escaped using the `e()` helper function to prevent Cross-Site Scripting.
- **SQL Injection Prevention:** 100% of database queries utilize PDO prepared statements with parameterized inputs.
- **Input Validation:** Strict server-side validation and type casting (`declare(strict_types=1)`) are enforced on all incoming data.
- **Audit Logs:** Destructive or sensitive administrative actions are immutably logged with timestamps and user IDs.
- **Soft Deletes:** Financial records utilize soft deletes (`deleted_at`) to prevent accidental data loss and maintain audit integrity.
- **Secure Session Handling:** Sessions are configured with `HttpOnly`, `Secure`, and `SameSite` cookie attributes to mitigate session hijacking.

## Security Updates

Security patches will be released as patch versions (e.g., `2.0.1`). We recommend enabling GitHub notifications for releases or using a dependency scanner to stay informed about updates. Critical vulnerabilities may result in an expedited out-of-band release.

## Acknowledgements

We extend our deepest gratitude to the security researchers and ethical hackers who take the time to responsibly disclose vulnerabilities. Your dedication helps keep ExpensePro and its users safe. 

*If you have reported a vulnerability that was accepted and patched, please let us know how you would like to be credited in our Security Acknowledgements hall of fame.*