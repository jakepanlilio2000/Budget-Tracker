<?php $basePath = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\'); ?>

<div style="width: 100%; max-width: 1200px; margin: 0 auto; padding: 60px 20px 20px; animation: fadeUp 0.8s ease-out; display: flex; flex-direction: column; gap: 80px;">

    <!-- 1. HERO -->
    <div style="text-align: center; max-width: 900px; margin: 0 auto; padding-top: 40px;">
        
        <div style="display: inline-block; padding: 6px 16px; background: rgba(88, 166, 255, 0.1); border: 1px solid var(--accent-blue); border-radius: 20px; color: var(--accent-blue); font-size: 12px; font-weight: bold; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 24px;">
            Next-Gen Personal Finance System
        </div>

        <h1 style="font-size: clamp(48px, 6vw, 72px); margin-bottom: 24px; font-weight: 800; letter-spacing: -2px; line-height: 1.1;">
            Stop Guessing Where Your Money Goes.<br>
            <span style="color: transparent; background-clip: text; -webkit-background-clip: text; background-image: linear-gradient(90deg, var(--accent-blue), #b392f0);">
                Start Controlling It With Precision.
            </span>
        </h1>

        <p style="font-size: 20px; color: var(--text-secondary); margin-bottom: 48px; line-height: 1.6; max-width: 750px; margin-left: auto; margin-right: auto;">
            Most budgeting tools only record what already happened. <strong>BudgetSuite</strong> helps you understand where your money is going, predict future outcomes, and make better financial decisions before problems happen.
        </p>

        <div style="display: flex; gap: 20px; justify-content: center; flex-wrap: wrap;">
            <a href="<?= $basePath ?>/register" class="btn primary hero-btn" style="font-size: 18px; padding: 18px 48px; border-radius: 8px; font-weight: bold; background: var(--accent-blue); color: #fff;">
                Get Started Free
            </a>
            <a href="<?= $basePath ?>/login" class="btn ghost hero-btn-ghost" style="font-size: 18px; padding: 18px 48px; border-radius: 8px; border: 1px solid var(--border); font-weight: bold; color: var(--text-primary);">
                Login
            </a>
        </div>
    </div>

    <!-- 2. PAIN -->
    <div style="background: var(--bg-elevated); border: 1px solid var(--border); border-radius: 24px; padding: 60px 40px; text-align: center;">
        
        <h2 style="font-size: 32px; margin-bottom: 16px;">
            Your money isn’t disappearing. It’s just untracked.
        </h2>

        <p style="color: var(--text-secondary); max-width: 650px; margin: 0 auto; font-size: 16px; line-height: 1.6;">
            Most people don’t have a spending problem—they have a visibility problem. Expenses are scattered across apps, subscriptions, cash spending, and forgotten transactions. Without a clear system, financial control becomes guesswork.
        </p>
    </div>

    <!-- 3. FEATURES -->
    <div>
        <h2 style="font-size: 36px; text-align: center; margin-bottom: 48px; font-weight: bold;">
            Built for Clarity, Control, and Confidence
        </h2>

        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 24px;">

            <div class="feature-tile" style="background: var(--bg-card); border: 1px solid var(--border); border-radius: 16px; padding: 32px;">
                <div style="font-size: 24px; margin-bottom: 16px;">📊</div>
                <h3 style="font-size: 20px; margin-bottom: 12px;">Complete Financial Overview</h3>
                <p style="color: var(--text-secondary); font-size: 14px; line-height: 1.6;">
                    See all your income, expenses, and balances in one structured dashboard designed for clarity, not clutter.
                </p>
            </div>

            <div class="feature-tile" style="background: var(--bg-card); border: 1px solid var(--border); border-radius: 16px; padding: 32px;">
                <div style="font-size: 24px; margin-bottom: 16px;">📈</div>
                <h3 style="font-size: 20px; margin-bottom: 12px;">Smart Forecasting</h3>
                <p style="color: var(--text-secondary); font-size: 14px; line-height: 1.6;">
                    Understand how today’s decisions affect your future balance with simple, realistic projections.
                </p>
            </div>

            <div class="feature-tile" style="background: var(--bg-card); border: 1px solid var(--border); border-radius: 16px; padding: 32px;">
                <div style="font-size: 24px; margin-bottom: 16px;">🔒</div>
                <h3 style="font-size: 20px; margin-bottom: 12px;">Private by Design</h3>
                <p style="color: var(--text-secondary); font-size: 14px; line-height: 1.6;">
                    Your financial data stays yours. No unnecessary tracking, no hidden data sharing.
                </p>
            </div>

        </div>
    </div>

    <!-- 4. CTA -->
    <div style="text-align: center; padding: 80px 20px; background: linear-gradient(180deg, transparent, rgba(88, 166, 255, 0.05)); border-radius: 24px; border: 1px solid var(--border);">

        <h2 style="font-size: 40px; margin-bottom: 16px; font-weight: 800;">
            Take control of your finances today.
        </h2>

        <p style="font-size: 18px; color: var(--text-secondary); margin-bottom: 40px;">
            The longer your finances stay unstructured, the harder they are to fix.
        </p>

        <a href="<?= $basePath ?>/register" class="btn primary hero-btn" style="font-size: 20px; padding: 20px 60px; border-radius: 8px; font-weight: bold;">
            Start Free Now
        </a>
    </div>

    <!-- 5. FOOTER -->
    <footer style="margin-top: 40px; border-top: 1px solid var(--border); padding-top: 60px;">
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 40px; margin-bottom: 60px; text-align: left;">
            
            <!-- Brand Column -->
            <div>
                <h3 style="font-size: 20px; margin-bottom: 16px; color: var(--text-primary);">Budget<span style="color: var(--accent-blue);">Suite</span></h3>
                <p style="color: var(--text-secondary); font-size: 14px; line-height: 1.6;">
                    Precision financial tracking and predictive analytics for absolute control over your wealth.
                </p>
            </div>

            <!-- Platform Links -->
            <div>
                <h4 style="font-size: 14px; margin-bottom: 16px; color: var(--text-primary); text-transform: uppercase; letter-spacing: 1px;">Platform</h4>
                <div style="display: flex; flex-direction: column; gap: 12px;">
                    <a href="<?= $basePath ?>/login" class="footer-link">Secure Login</a>
                    <a href="<?= $basePath ?>/register" class="footer-link">Create Account</a>
                </div>
            </div>

            <!-- Contact & Social -->
            <div>
                <h4 style="font-size: 14px; margin-bottom: 16px; color: var(--text-primary); text-transform: uppercase; letter-spacing: 1px;">Connect</h4>
                <div style="display: flex; flex-direction: column; gap: 12px;">
                    <a href="#" class="footer-link">Facebook</a>
                    <a href="#" class="footer-link">LinkedIn</a>
                    <a href="mailto:support@example.com" class="footer-link">Email Support</a>
                    <span style="color: var(--text-secondary); font-size: 14px; margin-top: 8px;">📍 San Fernando, Pampanga</span>
                </div>
            </div>

        </div>

        <!-- Copyright & Acknowledgement -->
        <div style="border-top: 1px solid var(--border); padding-top: 24px; padding-bottom: 24px; display: flex; flex-wrap: wrap; justify-content: space-between; align-items: center; gap: 16px;">
            <p style="color: var(--text-muted); font-size: 13px; margin: 0;">
                &copy; <?= date('Y') ?> BudgetSuite. All rights reserved.
            </p>
            <p style="color: var(--text-muted); font-size: 13px; margin: 0;">
                Designed & Engineered by <span style="color: var(--text-primary); font-weight: bold;">Jake Ashley C. Panlilio</span>
            </p>
        </div>
    </footer>

</div>

<style>
@keyframes fadeUp {
    0% { opacity: 0; transform: translateY(40px); }
    100% { opacity: 1; transform: translateY(0); }
}
.hero-btn:hover {
    transform: translateY(-2px) scale(1.02);
}
.feature-tile:hover {
    transform: translateY(-8px);
    border-color: var(--accent-blue);
}
.footer-link {
    color: var(--text-secondary);
    text-decoration: none;
    font-size: 14px;
    transition: color 0.2s ease;
}
.footer-link:hover {
    color: var(--accent-blue);
}
</style>