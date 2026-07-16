<?php
declare(strict_types=1);
?>

<!-- Hero Section -->
<section class="landing-hero">
    <div class="hero-content">
        <div class="hero-badge">
            <i class="fas fa-rocket"></i> Enterprise-Grade Financial OS
        </div>
        <h1 class="hero-title">Master Your Financial Future.</h1>
        <p class="hero-subtitle">
            Track expenses, manage salaries, forecast cash flow, and achieve your savings goals with enterprise-grade
            precision. Designed for individuals and small businesses.
        </p>
        <div class="hero-actions">
            <a href="<?= url('/register') ?>" class="btn-hero-primary">Start for Free</a>
            <a href="#dashboard-preview" class="btn-hero-secondary">
                <i class="fas fa-play-circle"></i> View Dashboard
            </a>
        </div>
    </div>

    <!-- Interactive Dashboard Preview -->
    <div class="dashboard-preview" id="dashboard-preview">
        <div class="mock-browser">
            <div class="mock-browser-bar">
                <div class="mock-dot"></div>
                <div class="mock-dot"></div>
                <div class="mock-dot"></div>
            </div>
            <div class="mock-browser-content">
                <div class="mock-stat-card">
                    <div class="mock-stat-label">Net Cash Flow</div>
                    <div class="mock-stat-value income sensitive-data">+$12,450.00</div>
                </div>
                <div class="mock-stat-card">
                    <div class="mock-stat-label">Total Expenses</div>
                    <div class="mock-stat-value expense sensitive-data">$4,230.50</div>
                </div>
                <div class="mock-stat-card">
                    <div class="mock-stat-label">Savings Rate</div>
                    <div class="mock-stat-value sensitive-data">68%</div>
                </div>
                <div class="mock-chart">
                    <div class="mock-stat-label">Cash Flow Trend</div>
                    <div class="mock-chart-line">
                        <svg viewBox="0 0 500 100" preserveAspectRatio="none">
                            <path d="M0,80 C50,70 100,40 150,50 C200,60 250,20 300,30 C350,40 400,10 450,20 L500,10" />
                        </svg>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="scroll-indicator">
        <span>Scroll to explore</span>
        <i class="fas fa-chevron-down"></i>
    </div>
</section>

<!-- Features Section -->
<section class="landing-section fade-in-section" id="features">
    <div class="section-header">
        <span class="section-badge">Core Capabilities</span>
        <h2 class="section-title">Everything You Need to Master Your Finances</h2>
        <p class="section-subtitle">A comprehensive suite of tools designed to give you total control over your personal
            and business financial ecosystem.</p>
    </div>

    <div class="features-grid">
        <div class="feature-card">
            <div class="feature-icon" style="background: rgba(245, 158, 11, 0.1); color: #f59e0b;"><i
                    class="fas fa-trophy"></i></div>
            <h3 class="feature-title">Achievement System</h3>
            <p class="feature-desc">Stay motivated with gamified financial milestones. Earn XP, unlock badges, and track
                your progress.</p>
        </div>
        <div class="feature-card">
            <div class="feature-icon"><i class="fas fa-arrows-alt"></i></div>
            <h3 class="feature-title">Dashboard Builder</h3>
            <p class="feature-desc">Make it yours. Drag and drop widgets, hide what you don't need, and save custom
                layouts.</p>
        </div>
        <div class="feature-card">
            <div class="feature-icon" style="background: rgba(139, 92, 246, 0.1); color: #8b5cf6;"><i
                    class="fas fa-brain"></i></div>
            <h3 class="feature-title">Advanced Analytics</h3>
            <p class="feature-desc">Enterprise BI dashboard with behavioral analysis, category intelligence, and radar
                charts.</p>
        </div>
        <div class="feature-card">
            <div class="feature-icon" style="background: rgba(245, 158, 11, 0.1); color: #f59e0b;"><i
                    class="fas fa-sliders-h"></i></div>
            <h3 class="feature-title">Balance Adjustment</h3>
            <p class="feature-desc">Professional accounting integrity. Safely adjust account balances with full audit
                trails.</p>
        </div>
        <div class="feature-card">
            <div class="feature-icon"><i class="fas fa-chart-line"></i></div>
            <h3 class="feature-title">Smart Dashboard</h3>
            <p class="feature-desc">Real-time overview of your net worth, cash flow, and financial health score with
                interactive charts.</p>
        </div>
        <div class="feature-card">
            <div class="feature-icon" style="background: rgba(139, 92, 246, 0.1); color: #8b5cf6;"><i
                    class="fas fa-history"></i></div>
            <h3 class="feature-title">Financial Timeline</h3>
            <p class="feature-desc">A centralized, searchable activity feed that tracks every financial action across
                all your modules.</p>
        </div>
        <div class="feature-card">
            <div class="feature-icon" style="background: rgba(16, 185, 129, 0.1); color: #10b981;"><i
                    class="fas fa-chart-area"></i></div>
            <h3 class="feature-title">Cash Flow Forecast</h3>
            <p class="feature-desc">Predict future balances with intelligent warnings and a What-If sandbox for
                risk-free simulation.</p>
        </div>
        <div class="feature-card">
            <div class="feature-icon" style="background: rgba(245, 158, 11, 0.1); color: #f59e0b;"><i
                    class="fas fa-calendar-alt"></i></div>
            <h3 class="feature-title">Financial Calendar</h3>
            <p class="feature-desc">Unified day, week, and month views aggregating bills, salaries, vault goals, and
                recurring expenses.</p>
        </div>
        <div class="feature-card">
            <div class="feature-icon"><i class="fas fa-briefcase"></i></div>
            <h3 class="feature-title">Salary Tracker</h3>
            <p class="feature-desc">Manage multiple employers, track payslips, bonuses, deductions, and 13th-month pay
                with ease.</p>
        </div>
        <div class="feature-card">
            <div class="feature-icon" style="background: rgba(239, 68, 68, 0.1); color: #ef4444;"><i
                    class="fas fa-receipt"></i></div>
            <h3 class="feature-title">Expense Manager</h3>
            <p class="feature-desc">Log daily spenditures with split transactions, custom categories, and receipt
                tracking capabilities.</p>
        </div>
        <div class="feature-card">
            <div class="feature-icon" style="background: rgba(16, 185, 129, 0.1); color: #10b981;"><i
                    class="fas fa-arrow-down"></i></div>
            <h3 class="feature-title">Income Tracker</h3>
            <p class="feature-desc">Monitor diverse income streams, freelance gigs, and investment returns in a single
                unified ledger.</p>
        </div>
        <div class="feature-card">
            <div class="feature-icon" style="background: rgba(20, 184, 166, 0.1); color: #14b8a6;"><i
                    class="fas fa-vault"></i></div>
            <h3 class="feature-title">Savings Vault</h3>
            <p class="feature-desc">Set specific financial goals, track deposits and withdrawals, and visualize your
                progress to 100%.</p>
        </div>
        <div class="feature-card">
            <div class="feature-icon"><i class="fas fa-piggy-bank"></i></div>
            <h3 class="feature-title">Budget Management</h3>
            <p class="feature-desc">Create custom envelope budgets with carry-over rules, custom periods, and automated
                alerts.</p>
        </div>
        <div class="feature-card">
            <div class="feature-icon" style="background: rgba(245, 158, 11, 0.1); color: #f59e0b;"><i
                    class="fas fa-file-invoice"></i></div>
            <h3 class="feature-title">Bills & Recurring</h3>
            <p class="feature-desc">Never miss a payment. Track recurring bills, calculate late penalties, and schedule
                auto-payments.</p>
        </div>
        <div class="feature-card">
            <div class="feature-icon" style="background: rgba(139, 92, 246, 0.1); color: #8b5cf6;"><i
                    class="fas fa-seedling"></i></div>
            <h3 class="feature-title">Investment Sandbox</h3>
            <p class="feature-desc">Model compound interest, inflation, and monthly contributions to visualize long-term
                wealth growth.</p>
        </div>
        <div class="feature-card">
            <div class="feature-icon" style="background: rgba(239, 68, 68, 0.1); color: #ef4444;"><i
                    class="fas fa-hand-holding-dollar"></i></div>
            <h3 class="feature-title">Loan Simulator</h3>
            <p class="feature-desc">Calculate amortization schedules, test extra payments, and see how much interest you
                can save.</p>
        </div>
        <div class="feature-card">
            <div class="feature-icon"><i class="fas fa-chart-pie"></i></div>
            <h3 class="feature-title">Advanced Analytics</h3>
            <p class="feature-desc">AI-like insights, financial health scoring, and radar detection for subscriptions
                and anomalies.</p>
        </div>
        <div class="feature-card">
            <div class="feature-icon" style="background: rgba(59, 130, 246, 0.1); color: #3b82f6;"><i
                    class="fas fa-file-invoice-dollar"></i></div>
            <h3 class="feature-title">Reports & Exports</h3>
            <p class="feature-desc">Generate beautiful PDF summaries, or export your data to XLSX, CSV, and JSON for
                external analysis.</p>
        </div>
        <div class="feature-card">
            <div class="feature-icon" style="background: rgba(16, 185, 129, 0.1); color: #10b981;"><i
                    class="fas fa-shield-alt"></i></div>
            <h3 class="feature-title">Backup & Restore</h3>
            <p class="feature-desc">Enterprise-grade data security with password-protected JSON restores and automated
                cloud backups.</p>
        </div>
        <div class="feature-card">
            <div class="feature-icon" style="background: rgba(245, 158, 11, 0.1); color: #f59e0b;"><i
                    class="fas fa-eye-slash"></i></div>
            <h3 class="feature-title">Privacy Features</h3>
            <p class="feature-desc">Instant Privacy Blur to hide sensitive balances in public, with click-to-reveal
                functionality.</p>
        </div>
        <div class="feature-card">
            <div class="feature-icon" style="background: rgba(139, 92, 246, 0.1); color: #8b5cf6;"><i
                    class="fas fa-palette"></i></div>
            <h3 class="feature-title">Theme Customization</h3>
            <p class="feature-desc">Light, Dark, and System modes. Custom accent colors, Compact Mode, and
                distraction-free Zen Mode.</p>
        </div>
        <div class="feature-card">
            <div class="feature-icon"><i class="fas fa-coins"></i></div>
            <h3 class="feature-title">Multi-Currency</h3>
            <p class="feature-desc">Support for 150+ global currencies with automatic base currency conversion and
                exchange tracking.</p>
        </div>
        <div class="feature-card">
            <div class="feature-icon" style="background: rgba(20, 184, 166, 0.1); color: #14b8a6;"><i
                    class="fas fa-mobile-alt"></i></div>
            <h3 class="feature-title">Mobile-First PWA</h3>
            <p class="feature-desc">A fully responsive Progressive Web App with offline support, ensuring you can track
                anywhere.</p>
        </div>
    </div>
</section>

<!-- Why Choose Section -->
<section class="landing-section fade-in-section"
    style="background: rgba(0,0,0,0.02); border-top: 1px solid var(--border-color); border-bottom: 1px solid var(--border-color); max-width: 100%; padding-left: 0; padding-right: 0;">
    <div style="max-width: 1200px; margin: 0 auto; padding: 0 1.5rem;">
        <div class="section-header">
            <span class="section-badge">The Advantage</span>
            <h2 class="section-title">Why Choose ExpensePro?</h2>
            <p class="section-subtitle">Built with modern architecture and a relentless focus on user experience,
                designed to scale with your financial journey.</p>
        </div>

        <div class="advantages-grid">
            <div class="advantage-item">
                <div class="advantage-icon"><i class="fas fa-server"></i></div>
                <div class="advantage-text">
                    <h4>Enterprise Architecture</h4>
                    <p>Built on a robust, secure MVC pattern with strict validation and PDO prepared statements.</p>
                </div>
            </div>
            <div class="advantage-item">
                <div class="advantage-icon"><i class="fas fa-mobile-screen"></i></div>
                <div class="advantage-text">
                    <h4>Mobile-First Design</h4>
                    <p>Every pixel is optimized for touch interfaces, ensuring a flawless experience on any device.</p>
                </div>
            </div>
            <div class="advantage-item">
                <div class="advantage-icon"><i class="fas fa-gem"></i></div>
                <div class="advantage-text">
                    <h4>Modern Glassmorphism UI</h4>
                    <p>A stunning, translucent visual language that feels premium, clean, and highly professional.</p>
                </div>
            </div>
            <div class="advantage-item">
                <div class="advantage-icon"><i class="fas fa-bolt"></i></div>
                <div class="advantage-text">
                    <h4>Blazing Fast Performance</h4>
                    <p>Optimized queries, file-based caching, and lazy-loaded assets ensure instant page loads.</p>
                </div>
            </div>
            <div class="advantage-item">
                <div class="advantage-icon"><i class="fas fa-wifi"></i></div>
                <div class="advantage-text">
                    <h4>Offline-Friendly PWA</h4>
                    <p>Continue tracking expenses even without an internet connection. Data syncs automatically later.
                    </p>
                </div>
            </div>
            <div class="advantage-item">
                <div class="advantage-icon"><i class="fas fa-lock"></i></div>
                <div class="advantage-text">
                    <h4>Secure & Private</h4>
                    <p>Session fixation protection, CSRF tokens, and Privacy Blur keep your data safe from prying eyes.
                    </p>
                </div>
            </div>
            <div class="advantage-item">
                <div class="advantage-icon"><i class="fas fa-sliders-h"></i></div>
                <div class="advantage-text">
                    <h4>Highly Customizable</h4>
                    <p>From accent colors to Zen Mode, tailor every aspect of the interface to your personal workflow.
                    </p>
                </div>
            </div>
            <div class="advantage-item">
                <div class="advantage-icon"><i class="fas fa-building"></i></div>
                <div class="advantage-text">
                    <h4>Personal & Business</h4>
                    <p>Whether managing household budgets or small business cash flow, the platform scales to fit.</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Financial Workflow Section -->
<section class="landing-section fade-in-section" id="workflow">
    <div class="section-header">
        <span class="section-badge">The Ecosystem</span>
        <h2 class="section-title">A Complete Financial Workflow</h2>
        <p class="section-subtitle">See how every module connects to create a seamless, automated financial operating
            system.</p>
    </div>

    <div class="workflow-container">
        <div class="workflow-line"></div>

        <div class="workflow-step">
            <div class="workflow-node"><i class="fas fa-arrow-down"></i></div>
            <div class="workflow-content">
                <h4>1. Income & Salary Tracking</h4>
                <p>Log your paychecks, freelance income, and side hustles. The system automatically calculates your net
                    available cash.</p>
            </div>
        </div>

        <div class="workflow-step">
            <div class="workflow-node"><i class="fas fa-piggy-bank"></i></div>
            <div class="workflow-content">
                <h4>2. Budget Allocation</h4>
                <p>Assign your income to custom envelope budgets. Set limits for Needs, Wants, and Savings with
                    carry-over rules.</p>
            </div>
        </div>

        <div class="workflow-step">
            <div class="workflow-node"><i class="fas fa-receipt"></i></div>
            <div class="workflow-content">
                <h4>3. Expense & Bill Management</h4>
                <p>Track daily spending with split categories. Automate recurring bills and avoid late fees with smart
                    reminders.</p>
            </div>
        </div>

        <div class="workflow-step">
            <div class="workflow-node"><i class="fas fa-vault"></i></div>
            <div class="workflow-content">
                <h4>4. Savings Vault Goals</h4>
                <p>Move surplus cash into dedicated Savings Vaults. Track progress toward specific goals like a new car
                    or emergency fund.</p>
            </div>
        </div>

        <div class="workflow-step">
            <div class="workflow-node"><i class="fas fa-chart-area"></i></div>
            <div class="workflow-content">
                <h4>5. Cash Flow Forecasting</h4>
                <p>The engine projects your future balances based on scheduled bills and income, warning you of
                    potential shortages.</p>
            </div>
        </div>

        <div class="workflow-step">
            <div class="workflow-node"><i class="fas fa-history"></i></div>
            <div class="workflow-content">
                <h4>6. Financial Timeline & Calendar</h4>
                <p>Every action is recorded in a centralized timeline and mapped onto a unified financial calendar for
                    total visibility.</p>
            </div>
        </div>

        <div class="workflow-step">
            <div class="workflow-node"><i class="fas fa-calendar-check"></i></div>
            <div class="workflow-content">
                <h4>7. Monthly & Yearly Reviews</h4>
                <p>Automated AI-like insights analyze your habits, celebrate achievements, and provide actionable
                    recommendations.</p>
            </div>
        </div>
    </div>
</section>

<!-- Analytics Showcase -->
<section class="landing-section fade-in-section">
    <div class="showcase-grid">
        <div class="showcase-text">
            <span class="section-badge">Analytics & Insights</span>
            <h3>Deep Insights, Powered by Data</h3>
            <p>Stop guessing where your money goes. Our advanced analytics engine categorizes your spending, identifies
                recurring subscriptions, and provides AI-like recommendations to optimize your financial health.</p>
            <ul style="list-style: none; padding: 0; margin: 0; display: flex; flex-direction: column; gap: 0.75rem;">
                <li style="display: flex; align-items: center; gap: 0.75rem;"><i class="fas fa-check-circle"
                        style="color: var(--success);"></i> Financial Health Score</li>
                <li style="display: flex; align-items: center; gap: 0.75rem;"><i class="fas fa-check-circle"
                        style="color: var(--success);"></i> Subscription Radar Detection</li>
                <li style="display: flex; align-items: center; gap: 0.75rem;"><i class="fas fa-check-circle"
                        style="color: var(--success);"></i> Monthly & Yearly Reviews</li>
            </ul>
        </div>
        <div class="showcase-visual">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
                <strong>Spending by Category</strong>
                <span style="font-size: 0.8rem; color: var(--text-secondary);">Last 6 Months</span>
            </div>
            <div class="mock-bar-chart" id="analyticsChart">
                <div>
                    <div style="background: linear-gradient(to top, var(--accent), rgba(59,130,246,0.3)); height: 0%;">
                    </div>
                    <small style="font-size: 0.7rem;">Food</small>
                </div>
                <div>
                    <div style="background: linear-gradient(to top, #8b5cf6, rgba(139,92,246,0.3)); height: 0%;"></div>
                    <small style="font-size: 0.7rem;">Transport</small>
                </div>
                <div>
                    <div style="background: linear-gradient(to top, #ef4444, rgba(239,68,68,0.3)); height: 0%;"></div>
                    <small style="font-size: 0.7rem;">Rent</small>
                </div>
                <div>
                    <div style="background: linear-gradient(to top, #10b981, rgba(16,185,129,0.3)); height: 0%;"></div>
                    <small style="font-size: 0.7rem;">Savings</small>
                </div>
                <div>
                    <div style="background: linear-gradient(to top, #f59e0b, rgba(245,158,11,0.3)); height: 0%;"></div>
                    <small style="font-size: 0.7rem;">Shopping</small>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Vault Showcase -->
<section class="landing-section fade-in-section"
    style="background: rgba(0,0,0,0.02); border-top: 1px solid var(--border-color); border-bottom: 1px solid var(--border-color); max-width: 100%; padding-left: 0; padding-right: 0;">
    <div style="max-width: 1200px; margin: 0 auto; padding: 0 1.5rem;">
        <div class="showcase-grid">
            <div class="showcase-visual" style="order: 1;">
                <div class="mock-vault-header">
                    <div>
                        <div class="mock-vault-title">Gaming PC Build</div>
                        <small style="color: var(--text-secondary);">Target: ₱80,000</small>
                    </div>
                    <div class="mock-vault-badge">56% Funded</div>
                </div>
                <div class="mock-vault-amounts">
                    <span style="color: var(--text-secondary);">Saved</span>
                    <span style="font-weight: 700;">₱45,000</span>
                </div>
                <div class="mock-vault-progress">
                    <div class="mock-vault-progress-bar"></div>
                </div>
                <div class="mock-vault-stats">
                    <div class="mock-vault-stat">
                        <label>Monthly Deposit</label>
                        <span>₱5,000</span>
                    </div>
                    <div class="mock-vault-stat">
                        <label>Est. Completion</label>
                        <span>Mar 2027</span>
                    </div>
                </div>
            </div>
            <div class="showcase-text" style="order: 2;">
                <span class="section-badge">Savings Vaults</span>
                <h3>Achieve Your Financial Goals</h3>
                <p>Stop mixing your savings with your spending money. Create dedicated Vaults for specific goals, track
                    your progress with beautiful visualizations, and celebrate when you hit 100%.</p>
                <ul
                    style="list-style: none; padding: 0; margin: 0; display: flex; flex-direction: column; gap: 0.75rem;">
                    <li style="display: flex; align-items: center; gap: 0.75rem;"><i class="fas fa-check-circle"
                            style="color: var(--success);"></i> Unlimited Goals & Vaults</li>
                    <li style="display: flex; align-items: center; gap: 0.75rem;"><i class="fas fa-check-circle"
                            style="color: var(--success);"></i> Automated Deposit Tracking</li>
                    <li style="display: flex; align-items: center; gap: 0.75rem;"><i class="fas fa-check-circle"
                            style="color: var(--success);"></i> Completion Milestones</li>
                </ul>
            </div>
        </div>
    </div>
</section>

<!-- Timeline Showcase -->
<section class="landing-section fade-in-section">
    <div class="showcase-grid">
        <div class="showcase-text">
            <span class="section-badge">Financial Timeline</span>
            <h3>Every Penny, Accounted For</h3>
            <p>Your complete financial history, centralized. Every transaction, bill payment, and vault deposit is
                logged in a beautiful, searchable timeline. Never lose track of where your money went.</p>
        </div>
        <div class="showcase-visual">
            <div class="mock-timeline-feed">
                <div class="mock-timeline-item" style="border-left-color: var(--success);">
                    <div class="mock-timeline-icon" style="background: rgba(16,185,129,0.1); color: var(--success);"><i
                            class="fas fa-briefcase"></i></div>
                    <div class="mock-timeline-content">
                        <strong>Salary Received</strong>
                        <small>Today, 09:00 AM • Puregold Inc.</small>
                    </div>
                    <div class="mock-timeline-amount" style="color: var(--success);">+₱25,000</div>
                </div>
                <div class="mock-timeline-item" style="border-left-color: var(--danger);">
                    <div class="mock-timeline-icon" style="background: rgba(239,68,68,0.1); color: var(--danger);"><i
                            class="fas fa-bolt"></i></div>
                    <div class="mock-timeline-content">
                        <strong>Paid Electricity Bill</strong>
                        <small>Today, 10:15 AM • Meralco</small>
                    </div>
                    <div class="mock-timeline-amount" style="color: var(--danger);">-₱3,450</div>
                </div>
                <div class="mock-timeline-item" style="border-left-color: #14b8a6;">
                    <div class="mock-timeline-icon" style="background: rgba(20,184,166,0.1); color: #14b8a6;"><i
                            class="fas fa-vault"></i></div>
                    <div class="mock-timeline-content">
                        <strong>Vault Deposit</strong>
                        <small>Today, 12:00 PM • Gaming PC</small>
                    </div>
                    <div class="mock-timeline-amount" style="color: #14b8a6;">-₱5,000</div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Forecast & Calendar Showcases -->
<section class="landing-section fade-in-section"
    style="background: rgba(0,0,0,0.02); border-top: 1px solid var(--border-color); border-bottom: 1px solid var(--border-color); max-width: 100%; padding-left: 0; padding-right: 0;">
    <div style="max-width: 1200px; margin: 0 auto; padding: 0 1.5rem;">
        <div class="showcase-grid">
            <div class="showcase-visual">
                <div class="mock-forecast-cards">
                    <div class="mock-forecast-card">
                        <label>Current Balance</label>
                        <span>₱124,500</span>
                    </div>
                    <div class="mock-forecast-card" style="border-color: var(--accent);">
                        <label>Projected (30 Days)</label>
                        <span style="color: var(--accent);">₱142,300</span>
                    </div>
                </div>
                <div class="mock-forecast-alert">
                    <i class="fas fa-exclamation-triangle"></i>
                    <span>Warning: Cash flow drops below ₱10k on Day 18 due to upcoming bills.</span>
                </div>
            </div>
            <div class="showcase-text">
                <span class="section-badge">Cash Flow Forecast</span>
                <h3>See the Future of Your Finances</h3>
                <p>Don't wait for the end of the month to realize you're short. Our predictive engine analyzes your
                    scheduled income, bills, and vault goals to project your daily balances and warn you of potential
                    shortages.</p>
            </div>
        </div>
    </div>
</section>
<!-- Achievement System Showcase -->
<section class="landing-section fade-in-section">
    <div class="showcase-grid">
        <div class="showcase-text">
            <span class="section-badge">Gamification</span>
            <h3>Achievement System</h3>
            <p>Stay motivated with our comprehensive achievement system. Earn XP, unlock badges, and track your
                financial milestones as you build healthy money habits.</p>
            <ul style="list-style: none; padding: 0; margin: 0; display: flex; flex-direction: column; gap: 0.75rem;">
                <li style="display: flex; align-items: center; gap: 0.75rem;"><i class="fas fa-trophy"
                        style="color: #f59e0b;"></i> Unlock Badges for Financial Discipline</li>
                <li style="display: flex; align-items: center; gap: 0.75rem;"><i class="fas fa-star"
                        style="color: #f59e0b;"></i> Earn XP and Track Progress</li>
                <li style="display: flex; align-items: center; gap: 0.75rem;"><i class="fas fa-chart-line"
                        style="color: #f59e0b;"></i> Visual Milestone Tracking</li>
            </ul>
        </div>
        <div class="showcase-visual">
            <div
                style="background: var(--bg-glass); border: 1px solid var(--border-color); border-radius: 16px; padding: 1.5rem; border-left: 4px solid #10b981;">
                <div style="display: flex; align-items: center; gap: 1rem; margin-bottom: 1rem;">
                    <div
                        style="width: 56px; height: 56px; border-radius: 12px; background: rgba(16,185,129,0.15); color: #10b981; display: flex; align-items: center; justify-content: center; font-size: 1.5rem;">
                        <i class="fas fa-gem"></i>
                    </div>
                    <div>
                        <h4 style="margin: 0; font-size: 1.1rem;">Savings Master</h4>
                        <small style="color: var(--text-secondary);">Saved a total of 50,000 in your vaults.</small>
                    </div>
                </div>
                <div
                    style="background: var(--border-color); border-radius: 99px; height: 8px; overflow: hidden; margin-bottom: 0.5rem;">
                    <div style="width: 75%; height: 100%; background: #10b981; transition: width 1s ease;"></div>
                </div>
                <div class="flex-between" style="font-size: 0.85rem;">
                    <span style="color: var(--text-secondary);">Progress: 75%</span>
                    <span style="color: #f59e0b; font-weight: 600;"><i class="fas fa-star"></i> 100 XP</span>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Dashboard Builder Showcase -->
<section class="landing-section fade-in-section"
    style="background: rgba(0,0,0,0.02); border-top: 1px solid var(--border-color); border-bottom: 1px solid var(--border-color); max-width: 100%; padding-left: 0; padding-right: 0;">
    <div style="max-width: 1200px; margin: 0 auto; padding: 0 1.5rem;">
        <div class="showcase-grid">
            <div class="showcase-visual" style="order: 1;">
                <div
                    style="background: var(--bg-glass); border: 2px dashed var(--accent); border-radius: 16px; padding: 1.5rem; position: relative;">
                    <div
                        style="position: absolute; top: -12px; right: 20px; background: var(--accent); color: white; padding: 0.25rem 0.75rem; border-radius: 99px; font-size: 0.75rem; font-weight: 600;">
                        <i class="fas fa-edit"></i> Edit Mode
                    </div>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                        <div
                            style="background: rgba(0,0,0,0.03); border: 1px solid var(--border-color); border-radius: 8px; padding: 1rem; position: relative;">
                            <div
                                style="position: absolute; top: -8px; left: -8px; width: 24px; height: 24px; background: var(--accent); border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; font-size: 0.6rem; cursor: grab;">
                                <i class="fas fa-grip-vertical"></i>
                            </div>
                            <small style="color: var(--text-secondary);">Net Cash Flow</small>
                            <h4 style="margin: 0.25rem 0 0; color: var(--success);">+$12,450</h4>
                        </div>
                        <div
                            style="background: rgba(0,0,0,0.03); border: 1px solid var(--border-color); border-radius: 8px; padding: 1rem; position: relative; transform: translateY(10px); box-shadow: 0 10px 20px rgba(0,0,0,0.1);">
                            <div
                                style="position: absolute; top: -8px; left: -8px; width: 24px; height: 24px; background: var(--accent); border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; font-size: 0.6rem; cursor: grabbing;">
                                <i class="fas fa-grip-vertical"></i>
                            </div>
                            <small style="color: var(--text-secondary);">Savings Vault</small>
                            <div
                                style="background: var(--border-color); height: 6px; border-radius: 99px; margin-top: 0.5rem;">
                                <div style="width: 60%; height: 100%; background: var(--accent); border-radius: 99px;">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="showcase-text" style="order: 2;">
                <span class="section-badge">Customization</span>
                <h3>Dashboard Builder</h3>
                <p>Make the platform truly yours. Drag and drop widgets, resize panels, and hide what you don't need.
                    Save multiple layouts for different workflows.</p>
                <ul
                    style="list-style: none; padding: 0; margin: 0; display: flex; flex-direction: column; gap: 0.75rem;">
                    <li style="display: flex; align-items: center; gap: 0.75rem;"><i class="fas fa-arrows-alt"
                            style="color: var(--accent);"></i> Drag & Drop Widget Reordering</li>
                    <li style="display: flex; align-items: center; gap: 0.75rem;"><i class="fas fa-eye-slash"
                            style="color: var(--accent);"></i> Hide/Show Specific Modules</li>
                    <li style="display: flex; align-items: center; gap: 0.75rem;"><i class="fas fa-save"
                            style="color: var(--accent);"></i> Auto-Save Layout Preferences</li>
                </ul>
            </div>
        </div>
    </div>
</section>

<!-- Advanced Analytics Showcase -->
<section class="landing-section fade-in-section">
    <div class="showcase-grid">
        <div class="showcase-text">
            <span class="section-badge">Business Intelligence</span>
            <h3>Advanced Analytics</h3>
            <p>Go beyond basic tracking. Our BI dashboard provides deep insights into your spending behavior, category
                trends, and financial health with enterprise-grade charts.</p>
            <ul style="list-style: none; padding: 0; margin: 0; display: flex; flex-direction: column; gap: 0.75rem;">
                <li style="display: flex; align-items: center; gap: 0.75rem;"><i class="fas fa-brain"
                        style="color: #8b5cf6;"></i> Behavioral Spending Analysis</li>
                <li style="display: flex; align-items: center; gap: 0.75rem;"><i class="fas fa-chart-pie"
                        style="color: #8b5cf6;"></i> Category Intelligence & Trends</li>
                <li style="display: flex; align-items: center; gap: 0.75rem;"><i class="fas fa-radar"
                        style="color: #8b5cf6;"></i> Radar & Heatmap Visualizations</li>
            </ul>
        </div>
        <div class="showcase-visual">
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                <div
                    style="background: var(--bg-glass); border: 1px solid var(--border-color); border-radius: 12px; padding: 1rem; text-align: center;">
                    <small style="color: var(--text-secondary);">Spending by Day</small>
                    <div style="width: 100px; height: 100px; margin: 1rem auto; position: relative;">
                        <div
                            style="position: absolute; inset: 0; border: 2px solid rgba(139,92,246,0.3); border-radius: 50%;">
                        </div>
                        <div
                            style="position: absolute; inset: 15px; border: 2px solid rgba(139,92,246,0.3); border-radius: 50%;">
                        </div>
                        <div
                            style="position: absolute; inset: 30px; border: 2px solid rgba(139,92,246,0.3); border-radius: 50%;">
                        </div>
                        <svg viewBox="0 0 100 100" style="position: absolute; inset: 0; width: 100%; height: 100%;">
                            <polygon points="50,10 85,35 75,80 25,80 15,35" fill="rgba(139,92,246,0.2)" stroke="#8b5cf6"
                                stroke-width="2" />
                        </svg>
                    </div>
                </div>
                <div
                    style="background: var(--bg-glass); border: 1px solid var(--border-color); border-radius: 12px; padding: 1rem;">
                    <small style="color: var(--text-secondary);">Category Trends</small>
                    <div
                        style="display: flex; align-items: flex-end; gap: 0.5rem; height: 100px; margin-top: 1rem; padding-bottom: 0.5rem; border-bottom: 1px solid var(--border-color);">
                        <div style="flex: 1; display: flex; flex-direction: column; justify-content: flex-end;">
                            <div style="height: 30px; background: #ef4444;"></div>
                            <div style="height: 20px; background: #3b82f6;"></div>
                        </div>
                        <div style="flex: 1; display: flex; flex-direction: column; justify-content: flex-end;">
                            <div style="height: 40px; background: #ef4444;"></div>
                            <div style="height: 30px; background: #3b82f6;"></div>
                        </div>
                        <div style="flex: 1; display: flex; flex-direction: column; justify-content: flex-end;">
                            <div style="height: 20px; background: #ef4444;"></div>
                            <div style="height: 40px; background: #3b82f6;"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
<section class="landing-section fade-in-section">
    <div class="showcase-grid">
        <div class="showcase-text">
            <span class="section-badge">Financial Calendar</span>
            <h3>Your Financial Life, Visualized</h3>
            <p>See your entire financial landscape at a glance. The unified calendar aggregates salary dates, bill due
                dates, vault milestones, and budget resets into a single, beautiful view.</p>
        </div>
        <div class="showcase-visual">
            <div class="mock-calendar-header">
                <strong>October 2026</strong>
                <div style="display: flex; gap: 0.5rem;">
                    <i class="fas fa-chevron-left" style="color: var(--text-secondary); cursor: pointer;"></i>
                    <i class="fas fa-chevron-right" style="color: var(--text-secondary); cursor: pointer;"></i>
                </div>
            </div>
            <div class="mock-calendar-grid" id="interactiveCalendar">
                <div class="mock-calendar-day">S</div>
                <div class="mock-calendar-day">M</div>
                <div class="mock-calendar-day">T</div>
                <div class="mock-calendar-day">W</div>
                <div class="mock-calendar-day">T</div>
                <div class="mock-calendar-day">F</div>
                <div class="mock-calendar-day">S</div>

                <div class="mock-calendar-day"></div>
                <div class="mock-calendar-day"></div>
                <div class="mock-calendar-day active">1</div>
                <div class="mock-calendar-day active">2</div>
                <div class="mock-calendar-day active has-event green" data-event="salary" data-amount="+₱25,000"
                    data-title="Salary Received">3</div>
                <div class="mock-calendar-day active">4</div>
                <div class="mock-calendar-day active">5</div>
                <div class="mock-calendar-day active has-event red" data-event="bill" data-amount="-₱3,450"
                    data-title="Electricity Bill">6</div>
                <div class="mock-calendar-day active">7</div>
                <div class="mock-calendar-day active">8</div>
                <div class="mock-calendar-day active has-event yellow" data-event="vault" data-amount="-₱5,000"
                    data-title="Vault Deposit">9</div>
                <div class="mock-calendar-day active">10</div>
                <div class="mock-calendar-day active">11</div>
                <div class="mock-calendar-day active">12</div>
                <div class="mock-calendar-day active">13</div>
                <div class="mock-calendar-day active">14</div>
                <div class="mock-calendar-day active has-event green" data-event="salary" data-amount="+₱25,000"
                    data-title="Salary Received">15</div>
                <div class="mock-calendar-day active">16</div>
                <div class="mock-calendar-day active">17</div>
                <div class="mock-calendar-day active has-event red" data-event="bill" data-amount="-₱1,899"
                    data-title="Internet Bill">18</div>
                <div class="mock-calendar-day active">19</div>
            </div>
            <div
                style="display: flex; gap: 1rem; margin-top: 1.5rem; font-size: 0.75rem; color: var(--text-secondary); flex-wrap: wrap;">
                <span style="display: flex; align-items: center; gap: 0.25rem;"><span
                        style="width: 8px; height: 8px; border-radius: 50%; background: var(--success);"></span>
                    Salary</span>
                <span style="display: flex; align-items: center; gap: 0.25rem;"><span
                        style="width: 8px; height: 8px; border-radius: 50%; background: var(--danger);"></span>
                    Bills</span>
                <span style="display: flex; align-items: center; gap: 0.25rem;"><span
                        style="width: 8px; height: 8px; border-radius: 50%; background: #f59e0b;"></span> Vault</span>
            </div>
        </div>
    </div>
</section>

<!-- Statistics -->
<section class="landing-section fade-in-section" style="text-align: center;">
    <div class="section-header">
        <span class="section-badge">Built for Scale</span>
        <h2 class="section-title">Enterprise-Grade Capabilities</h2>
    </div>
    <div class="stats-grid">
        <div class="stat-item">
            <h3 class="kpi-counter" data-target="150" data-suffix="+">0</h3>
            <p>Global Currencies Supported</p>
        </div>
        <div class="stat-item">
            <h3>∞</h3>
            <p>Unlimited Accounts & Vaults</p>
        </div>
        <div class="stat-item">
            <h3 class="kpi-counter" data-target="100" data-suffix="%">0</h3>
            <p>Data Ownership & Privacy</p>
        </div>
        <div class="stat-item">
            <h3 class="kpi-counter" data-target="24" data-suffix="/7">0</h3>
            <p>Offline PWA Availability</p>
        </div>
    </div>
</section>

<!-- Theme Showcase -->
<section class="landing-section fade-in-section"
    style="background: rgba(0,0,0,0.02); border-top: 1px solid var(--border-color); border-bottom: 1px solid var(--border-color); max-width: 100%; padding-left: 0; padding-right: 0;">
    <div style="max-width: 1200px; margin: 0 auto; padding: 0 1.5rem;">
        <div class="section-header">
            <span class="section-badge">Your Interface</span>
            <h2 class="section-title">Designed for Your Focus</h2>
            <p class="section-subtitle">Whether you prefer a clean light theme, a sleek dark mode, or need to hide
                sensitive data in public, ExpensePro adapts to you.</p>
        </div>
        <div class="theme-showcase-grid">
            <div class="theme-mockup light">
                <div class="theme-mockup-bar">
                    <div class="theme-mockup-dot"></div>
                    <div class="theme-mockup-dot"></div>
                    <div class="theme-mockup-dot"></div>
                </div>
                <div class="theme-mockup-content">
                    <h4 style="margin-top: 0;">Light Mode</h4>
                    <p style="font-size: 0.85rem; color: #64748b;">Clean, bright, and optimized for daylight viewing.
                        Perfect for daytime productivity.</p>
                    <div style="height: 8px; background: #e2e8f0; border-radius: 4px; margin-top: 1rem;"></div>
                    <div style="height: 8px; background: #e2e8f0; border-radius: 4px; margin-top: 0.5rem; width: 70%;">
                    </div>
                </div>
            </div>
            <div class="theme-mockup dark">
                <div class="theme-mockup-bar">
                    <div class="theme-mockup-dot"></div>
                    <div class="theme-mockup-dot"></div>
                    <div class="theme-mockup-dot"></div>
                </div>
                <div class="theme-mockup-content">
                    <h4 style="margin-top: 0;">Dark Mode</h4>
                    <p style="font-size: 0.85rem; color: #94a3b8;">Reduce eye strain and save battery. A sleek, modern
                        aesthetic for night owls.</p>
                    <div style="height: 8px; background: #334155; border-radius: 4px; margin-top: 1rem;"></div>
                    <div style="height: 8px; background: #334155; border-radius: 4px; margin-top: 0.5rem; width: 70%;">
                    </div>
                </div>
            </div>
            <div class="theme-mockup blur">
                <div class="theme-mockup-bar">
                    <div class="theme-mockup-dot"></div>
                    <div class="theme-mockup-dot"></div>
                    <div class="theme-mockup-dot"></div>
                </div>
                <div class="theme-mockup-content">
                    <h4 style="margin-top: 0;">Privacy Blur Mode</h4>
                    <div class="sensitive-text" style="display: flex; gap: 1rem; margin: 1rem 0;">
                        <div style="flex: 1; padding: 0.75rem; background: #f1f5f9; border-radius: 8px;">
                            <div style="font-size: 0.7rem; color: #64748b;">Balance</div>
                            <div style="font-size: 1.2rem; font-weight: 700; color: #10b981;">₱124,500</div>
                        </div>
                        <div style="flex: 1; padding: 0.75rem; background: #f1f5f9; border-radius: 8px;">
                            <div style="font-size: 0.7rem; color: #64748b;">Expenses</div>
                            <div style="font-size: 1.2rem; font-weight: 700; color: #ef4444;">₱45,230</div>
                        </div>
                    </div>
                    <div class="sensitive-text"
                        style="height: 40px; background: linear-gradient(90deg, #3b82f6 0%, #8b5cf6 100%); border-radius: 6px; margin-bottom: 0.5rem;">
                    </div>
                    <div class="sensitive-text"
                        style="height: 8px; background: #e2e8f0; border-radius: 4px; margin-bottom: 0.5rem;"></div>
                    <div class="sensitive-text"
                        style="height: 8px; background: #e2e8f0; border-radius: 4px; width: 70%;"></div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Call to Action -->
<section class="cta-section">
    <div class="cta-content">
        <h2>Ready to Take Control of Your Finances?</h2>
        <p>Join thousands of users who have transformed their financial health with ExpensePro. Start your journey
            today.</p>
        <div class="hero-actions">
            <a href="<?= url('/register') ?>" class="btn-hero-primary">Create Free Account</a>
            <a href="<?= url('/login') ?>" class="btn-hero-secondary">Sign In to Dashboard</a>
        </div>
    </div>
</section>

<!-- Footer -->
<footer class="landing-footer">
    <div class="footer-grid">
        <div class="footer-brand">
            <h3><i class="fas fa-wallet"></i> ExpensePro</h3>
            <p>The all-in-one Personal Finance & Small Business Management Platform. Track, plan, and achieve your
                financial goals with enterprise-grade precision.</p>
        </div>
        <div class="footer-col">
            <h4>Product</h4>
            <ul>
                <li><a href="#features">Features</a></li>
                <li><a href="#workflow">Workflow</a></li>
                <li><a href="<?= url('/login') ?>">Sign In</a></li>
                <li><a href="<?= url('/register') ?>">Register</a></li>
            </ul>
        </div>
        <div class="footer-col">
            <h4>Resources</h4>
            <ul>
                <li><a href="#">Documentation</a></li>
                <li><a href="#">Changelog</a></li>
                <li><a href="#">API Status</a></li>
                <li><a href="#">Support</a></li>
            </ul>
        </div>
        <div class="footer-col">
            <h4>Company</h4>
            <ul>
                <li><a href="#">About Us</a></li>
                <li><a href="#">Privacy Policy</a></li>
                <li><a href="#">Terms of Service</a></li>
                <li><a href="#">Contact</a></li>
            </ul>
        </div>
    </div>
    <div class="footer-bottom">
        <span>&copy;
            <?= date('Y') ?> ExpensePro. All rights reserved.
        </span>
        <span>Version 1.0.0 </span>
    </div>
</footer>

<script>
    // Smooth scroll for anchor links
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function (e) {
            const target = document.querySelector(this.getAttribute('href'));
            if (target) {
                e.preventDefault();
                target.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }
        });
    });

    document.addEventListener('DOMContentLoaded', function () {
        // 1. Animate Analytics Bars with Random Heights
        const chart = document.getElementById('analyticsChart');
        if (chart) {
            const bars = chart.querySelectorAll('div > div');
            setTimeout(() => {
                bars.forEach(bar => {
                    const randomHeight = Math.floor(Math.random() * 60) + 30;
                    bar.style.height = randomHeight + '%';
                });
            }, 300);
        }

        // 2. Interactive Calendar with Tooltips
        const calendarDays = document.querySelectorAll('.has-event');
        const tooltip = document.createElement('div');
        tooltip.style.cssText = `
                position: fixed;
                background: var(--bg-glass-solid);
                border: 1px solid var(--border-color);
                padding: 0.75rem;
                border-radius: 8px;
                font-size: 0.8rem;
                z-index: 9999;
                pointer-events: none;
                opacity: 0;
                transition: opacity 0.2s;
                box-shadow: 0 10px 30px rgba(0,0,0,0.15);
                backdrop-filter: blur(12px);
                -webkit-backdrop-filter: blur(12px);
                max-width: 200px;
            `;
        document.body.appendChild(tooltip);

        calendarDays.forEach(day => {
            const eventType = day.getAttribute('data-event');
            const amount = day.getAttribute('data-amount');
            const title = day.getAttribute('data-title');

            if (eventType && amount && title) {
                day.style.cursor = 'pointer';

                day.addEventListener('mouseenter', function (e) {
                    const rect = this.getBoundingClientRect();
                    const iconClass = eventType === 'salary' ? 'fa-briefcase' :
                        eventType === 'vault' ? 'fa-vault' : 'fa-file-invoice';
                    const color = eventType === 'salary' ? 'var(--success)' :
                        eventType === 'vault' ? '#f59e0b' : 'var(--danger)';

                    tooltip.innerHTML = `
                            <div style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.5rem;">
                                <i class="fas ${iconClass}" style="color: ${color};"></i>
                                <strong style="color: var(--text-primary);">${title}</strong>
                            </div>
                            <div style="color: ${color}; font-weight: 700; font-size: 1rem;">
                                ${amount}
                            </div>
                        `;

                    tooltip.style.left = (rect.left + rect.width / 2 - 100) + 'px';
                    tooltip.style.top = (rect.top - 100) + 'px';
                    tooltip.style.opacity = '1';
                });

                day.addEventListener('mouseleave', function () {
                    tooltip.style.opacity = '0';
                });
            }
        });

        // 3. Live Theme Switching
        const themeMockups = document.querySelectorAll('.theme-mockup');
        themeMockups.forEach(mockup => {
            mockup.style.cursor = 'pointer';
            mockup.addEventListener('click', function () {
                let theme = 'light';
                if (this.classList.contains('dark')) {
                    theme = 'dark';
                }

                document.documentElement.setAttribute('data-theme', theme);

                // Visual feedback
                themeMockups.forEach(m => {
                    m.style.transform = 'none';
                    m.style.borderColor = 'var(--border-color)';
                });

                this.style.transform = 'scale(1.05)';
                this.style.borderColor = 'var(--accent)';

                setTimeout(() => {
                    this.style.transform = 'none';
                }, 200);
            });
        });
    });
</script>