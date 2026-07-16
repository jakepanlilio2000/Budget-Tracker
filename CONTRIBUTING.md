# Contributing to ExpensePro

## Welcome
Thank you for your interest in contributing to ExpensePro! We are thrilled to have you here. Whether you are fixing a typo, squashing a bug, or proposing a major new feature, your contributions help make this platform more robust, secure, and valuable for users worldwide. This guide will help you navigate the contribution process smoothly.

## Code of Conduct
By participating in this project, you agree to abide by our [Code of Conduct](CODE_OF_CONDUCT.md). We are committed to providing a welcoming, respectful, and harassment-free environment for everyone, regardless of background or experience level.

## Ways to Contribute
There are many ways to add value to ExpensePro:
- **Code Contributions:** Fix bugs, improve performance, or build new features.
- **Documentation:** Improve the README, write tutorials, or clarify inline code comments.
- **Testing:** Report bugs, verify fixes, or write automated test cases.
- **Design & UX:** Suggest UI/UX improvements or provide accessibility enhancements.
- **Community Support:** Answer questions in discussions or help triage issues.

## Reporting Bugs
If you encounter a bug, please help us by submitting a detailed issue report:
1. Search the [issue tracker](https://github.com/yourusername/expensepro/issues) to ensure it hasn't already been reported.
2. Use the provided Bug Report template.
3. Include your PHP version, MySQL version, OS, and browser details.
4. Provide clear, step-by-step instructions to reproduce the issue.
5. Attach relevant screenshots, error logs, or stack traces.

## Suggesting Features
We love hearing new ideas! To suggest a feature:
1. Check existing issues and the [Roadmap](README.md#roadmap) to avoid duplicates.
2. Open a new issue using the Feature Request template.
3. Clearly describe the problem the feature solves and the proposed solution.
4. Explain how this aligns with ExpensePro's core mission of enterprise-grade personal finance management.

## Before Opening an Issue
Before creating a new issue, please ensure:
- You are running the latest version of the `main` branch.
- You have reviewed the [Documentation](README.md) and [FAQ](README.md#faq).
- The issue is specific to ExpensePro and not a third-party dependency (e.g., Chart.js, mPDF).

## Development Workflow
1. **Fork** the repository to your GitHub account.
2. **Clone** your fork locally: `git clone https://github.com/your-username/expensepro.git`
3. **Create a new branch** for your work (see Branch Naming Convention).
4. **Make your changes** and ensure they adhere to our Coding Standards.
5. **Test** your changes locally.
6. **Commit** your changes using the Commit Message Convention.
7. **Push** your branch to your fork.
8. **Open a Pull Request** against the `main` branch of the upstream repository.

## Branch Naming Convention
Use descriptive, lowercase, hyphen-separated branch names prefixed with a category:
- `feature/add-investment-sandbox`
- `bugfix/resolve-csv-export-encoding`
- `docs/update-installation-steps`
- `refactor/optimize-analytics-queries`

## Commit Message Convention
We follow a simplified Conventional Commits specification to maintain a clean history:
- `feat:` A new feature
- `fix:` A bug fix
- `docs:` Documentation only changes
- `style:` Changes that do not affect the meaning of the code (whitespace, formatting)
- `refactor:` A code change that neither fixes a bug nor adds a feature
- `perf:` A code change that improves performance
- `test:` Adding missing tests or correcting existing tests

*Example:* `feat: add dynamic chain multiplier to achievement engine`

## Pull Request Guidelines
- Ensure your PR addresses a specific issue or feature request.
- Keep PRs focused and atomic. Avoid bundling unrelated changes.
- Update the `README.md` or relevant documentation if your PR changes user-facing behavior.
- Ensure all automated checks (if applicable) pass before requesting a review.
- Be responsive to maintainer feedback and requested changes.

## Coding Standards
- **PHP:** Follow [PSR-12](https://www.php-fig.org/psr/psr-12/) coding standards. Use strict typing (`declare(strict_types=1);`) at the top of every PHP file.
- **JavaScript:** Use Vanilla JS. Avoid external frameworks unless absolutely necessary. Use `const` and `let` appropriately, and prefer arrow functions for callbacks.
- **CSS:** Maintain the existing CSS variable system (`var(--accent)`, etc.) to ensure seamless Light/Dark mode compatibility.
- **Security:** Never commit hardcoded credentials. Always use prepared statements for database queries and escape all output using the `e()` helper.

## Project Structure Overview
Familiarize yourself with the MVC architecture before contributing:
- `app/Core/`: Framework foundation (Router, Database, Auth, CSRF).
- `app/Controllers/`: HTTP request handlers.
- `app/Models/`: Data access objects and database interactions.
- `app/Services/`: Complex business logic (e.g., `AchievementEngine`, `FxpEngine`).
- `app/Views/`: HTML templates and layouts.
- `database/migrations/`: Version-controlled SQL schema files.

## Documentation Guidelines
- Keep documentation clear, concise, and free of jargon.
- Use Markdown best practices (proper heading hierarchy, code blocks, lists).
- Update inline PHPDoc comments for all new public methods and classes.

## Testing Expectations
While the project currently relies on manual testing, contributors are encouraged to:
- Manually verify their changes across different browsers and screen sizes (mobile-first).
- Test edge cases (e.g., empty states, invalid input, large datasets).
- Add PHPUnit tests for new Services or Core utilities as the test suite expands.

## Security Disclosure Reminder
If you discover a security vulnerability, **do not** open a public issue. 
Please email us directly at [security@expensepro.example.com](mailto:security@expensepro.example.com) with a detailed description of the vulnerability. We will acknowledge your report promptly and work with you to resolve it.

## Community Expectations
- Be kind, constructive, and patient.
- Assume good intentions from fellow contributors and maintainers.
- Focus on the problem, not the person.
- Respect the maintainers' time; they review contributions voluntarily.

## Recognition for Contributors
All contributors who have a merged Pull Request will be acknowledged. Significant contributors may be invited to join the core maintainer team. We believe in giving credit where it is due, and your name will be preserved in the project's commit history and contributor lists.

## Thank You
Building an enterprise-grade financial platform is a significant undertaking, and we could not do it without the open-source community. Thank you for your time, effort, and dedication to making ExpensePro better for everyone!