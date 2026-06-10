<?php $basePath = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\'); ?>
<style>
    :root {
        --glow-primary: rgba(88, 166, 255, 0.15);
        --glow-secondary: rgba(179, 146, 240, 0.15);
    }
    
    @keyframes fadeUp {
        0% { opacity: 0; transform: translateY(40px); }
        100% { opacity: 1; transform: translateY(0); }
    }
    
    @keyframes pulseGlow {
        0% { box-shadow: 0 0 40px var(--glow-primary); }
        50% { box-shadow: 0 0 80px var(--glow-secondary); }
        100% { box-shadow: 0 0 40px var(--glow-primary); }
    }

    .hero-btn {
        transition: transform 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275), box-shadow 0.3s ease;
    }
    .hero-btn:hover {
        transform: translateY(-4px) scale(1.02);
        box-shadow: 0 12px 24px rgba(88, 166, 255, 0.3);
    }
    
    .hero-btn-ghost:hover {
        background: rgba(255, 255, 255, 0.05);
        border-color: var(--text-secondary);
    }

    .feature-tile {
        background: rgba(22, 27, 34, 0.6);
        backdrop-filter: blur(16px);
        -webkit-backdrop-filter: blur(16px);
        border: 1px solid var(--border);
        border-radius: 20px;
        padding: 40px 32px;
        transition: all 0.3s ease;
        position: relative;
        overflow: hidden;
    }
    
    .feature-tile::before {
        content: '';
        position: absolute;
        top: 0; left: 0; right: 0;
        height: 4px;
        background: linear-gradient(90deg, var(--accent-blue), transparent);
        opacity: 0;
        transition: opacity 0.3s ease;
    }

    .feature-tile:hover {
        transform: translateY(-8px);
        border-color: rgba(88, 166, 255, 0.4);
        box-shadow: 0 20px 40px rgba(0, 0, 0, 0.4);
    }
    
    .feature-tile:hover::before {
        opacity: 1;
    }

    .footer-link {
        color: var(--text-secondary);
        text-decoration: none;
        font-size: 14px;
        transition: color 0.2s ease;
        display: flex;
        align-items: center;
        gap: 8px;
    }
    .footer-link:hover {
        color: var(--accent-blue);
    }
    
    .gradient-text {
        color: transparent; 
        background-clip: text; 
        -webkit-background-clip: text; 
        background-image: linear-gradient(90deg, var(--accent-blue), #b392f0);
    }
    
    .bg-orb {
        position: absolute;
        width: 600px;
        height: 600px;
        border-radius: 50%;
        background: radial-gradient(circle, var(--glow-primary) 0%, transparent 70%);
        top: -100px;
        left: 50%;
        transform: translateX(-50%);
        z-index: -1;
        pointer-events: none;
    }
</style>

<div class="bg-orb"></div>

<header style="width: 100%; border-bottom: 1px solid rgba(255,255,255,0.08); background: rgba(13, 17, 23, 0.75); backdrop-filter: blur(20px); -webkit-backdrop-filter: blur(20px); position: sticky; top: 0; z-index: 1000;">
    <div style="max-width: 1200px; margin: 0 auto; padding: 16px 24px; display: flex; justify-content: space-between; align-items: center;">
        
        <a href="<?= $basePath ?>/" style="text-decoration: none; font-size: 24px; font-weight: 800; color: var(--text-primary); letter-spacing: -0.5px; display: flex; align-items: center; gap: 10px;">
            <i class="fa-solid fa-chart-pie" style="color: var(--accent-blue);"></i>
            Budget<span class="gradient-text">Suite</span>
        </a>

        <div style="display: flex; gap: 24px; align-items: center;">
            <a href="<?= $basePath ?>/login" style="color: var(--text-secondary); text-decoration: none; font-weight: 600; font-size: 15px; transition: color 0.2s;" onmouseover="this.style.color='var(--text-primary)'" onmouseout="this.style.color='var(--text-secondary)'">
                Sign In
            </a>
            <a href="<?= $basePath ?>/register" class="btn primary" style="padding: 10px 24px; font-size: 14px; font-weight: bold; border-radius: 8px; box-shadow: 0 4px 16px rgba(46, 160, 67, 0.2);">
                Get Started <i class="fa-solid fa-arrow-right" style="margin-left: 6px; font-size: 12px;"></i>
            </a>
        </div>
        
    </div>
</header>

<div style="width: 100%; max-width: 1200px; margin: 0 auto; padding: 80px 24px 20px; animation: fadeUp 0.8s cubic-bezier(0.16, 1, 0.3, 1); display: flex; flex-direction: column; gap: 100px;">

    <div style="text-align: center; max-width: 900px; margin: 0 auto; padding-top: 20px; position: relative;">
        
        <div style="display: inline-flex; align-items: center; gap: 8px; padding: 6px 16px; background: rgba(88, 166, 255, 0.1); border: 1px solid rgba(88, 166, 255, 0.3); border-radius: 20px; color: var(--accent-blue); font-size: 12px; font-weight: bold; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 32px;">
            <span style="display: inline-block; width: 8px; height: 8px; border-radius: 50%; background: var(--accent-blue); box-shadow: 0 0 8px var(--accent-blue);"></span>
            Next-Gen Personal Finance Engine
        </div>

        <h1 style="font-size: clamp(48px, 7vw, 84px); margin-bottom: 24px; font-weight: 800; letter-spacing: -2.5px; line-height: 1.05;">
            Stop Guessing Where Your Money Goes.<br>
            <span class="gradient-text">Start Controlling It.</span>
        </h1>

        <p style="font-size: 20px; color: var(--text-secondary); margin-bottom: 48px; line-height: 1.6; max-width: 700px; margin-left: auto; margin-right: auto;">
            Most tools only record what already happened. <strong>BudgetSuite</strong> helps you understand exactly where your cashflow is headed, forecast trajectories, and engineer absolute wealth control.
        </p>

        <div style="display: flex; gap: 20px; justify-content: center; flex-wrap: wrap;">
            <a href="<?= $basePath ?>/register" class="btn primary hero-btn" style="font-size: 18px; padding: 18px 48px; border-radius: 12px; font-weight: bold; background: var(--accent-blue); color: #fff; border: none;">
                Deploy Your Ledger
            </a>
            <a href="<?= $basePath ?>/login" class="btn ghost hero-btn-ghost" style="font-size: 18px; padding: 18px 48px; border-radius: 12px; border: 1px solid var(--border); font-weight: bold; color: var(--text-primary);">
                <i class="fa-solid fa-lock" style="margin-right: 8px; color: var(--text-muted);"></i> Access Portal
            </a>
        </div>
    </div>

    <div style="background: rgba(22, 27, 34, 0.4); border: 1px solid var(--border); border-top: 1px solid rgba(248, 81, 73, 0.4); border-radius: 24px; padding: 60px 40px; text-align: center; position: relative; overflow: hidden;">
        <div style="position: absolute; top: -50px; left: 50%; transform: translateX(-50%); width: 200px; height: 100px; background: rgba(248, 81, 73, 0.15); filter: blur(40px); z-index: 0;"></div>
        
        <h2 style="font-size: 32px; margin-bottom: 20px; position: relative; z-index: 1;">
            Your money isn’t disappearing. <span style="color: var(--accent-red);">It’s just untracked.</span>
        </h2>

        <p style="color: var(--text-secondary); max-width: 700px; margin: 0 auto; font-size: 18px; line-height: 1.6; position: relative; z-index: 1;">
            Most people don’t have a spending problem—they have a visibility problem. Expenses are scattered across digital wallets, hidden subscriptions, and unlogged daily spends. Without a unified system, financial control becomes pure guesswork.
        </p>
    </div>

    <div>
        <h2 style="font-size: 36px; text-align: center; margin-bottom: 60px; font-weight: 800; letter-spacing: -1px;">
            Built for Clarity, Control, & Performance
        </h2>

        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 24px;">

            <div class="feature-tile">
                <div style="width: 56px; height: 56px; border-radius: 12px; background: rgba(88, 166, 255, 0.1); display: flex; align-items: center; justify-content: center; margin-bottom: 24px;">
                    <i class="fa-solid fa-layer-group" style="font-size: 24px; color: var(--accent-blue);"></i>
                </div>
                <h3 style="font-size: 22px; margin-bottom: 16px; font-weight: 700;">Structured Overview</h3>
                <p style="color: var(--text-secondary); font-size: 15px; line-height: 1.6;">
                    See all your master income, dynamic expenses, and calculated balances in one modular dashboard designed for maximum data density and minimal clutter.
                </p>
            </div>

            <div class="feature-tile">
                <div style="width: 56px; height: 56px; border-radius: 12px; background: rgba(63, 185, 80, 0.1); display: flex; align-items: center; justify-content: center; margin-bottom: 24px;">
                    <i class="fa-solid fa-wand-magic-sparkles" style="font-size: 24px; color: var(--accent-green);"></i>
                </div>
                <h3 style="font-size: 22px; margin-bottom: 16px; font-weight: 700;">Trajectory Forecasting</h3>
                <p style="color: var(--text-secondary); font-size: 15px; line-height: 1.6;">
                    Utilize the built-in simulation engine to understand how today’s financial decisions and micro-spends affect your future cumulative net worth.
                </p>
            </div>

            <div class="feature-tile">
                <div style="width: 56px; height: 56px; border-radius: 12px; background: rgba(210, 153, 34, 0.1); display: flex; align-items: center; justify-content: center; margin-bottom: 24px;">
                    <i class="fa-solid fa-bolt" style="font-size: 24px; color: var(--accent-yellow);"></i>
                </div>
                <h3 style="font-size: 22px; margin-bottom: 16px; font-weight: 700;">Performant Architecture</h3>
                <p style="color: var(--text-secondary); font-size: 15px; line-height: 1.6;">
                    Engineered to be lightweight, lightning-fast, and entirely focused on logic. Your data stays private, secure, and accessible exactly when you need it.
                </p>
            </div>

        </div>
    </div>

    <div style="text-align: center; padding: 100px 24px; background: linear-gradient(180deg, rgba(22, 27, 34, 0) 0%, rgba(88, 166, 255, 0.05) 100%); border-radius: 32px; border: 1px solid rgba(88, 166, 255, 0.2); position: relative; overflow: hidden;">
        
        <h2 style="font-size: clamp(32px, 5vw, 48px); margin-bottom: 20px; font-weight: 800; letter-spacing: -1px;">
            Initialize Your Financial Future.
        </h2>

        <p style="font-size: 18px; color: var(--text-secondary); margin-bottom: 48px; max-width: 600px; margin-left: auto; margin-right: auto; line-height: 1.6;">
            The longer your finances stay unstructured, the harder they are to fix. Spin up your first profile in seconds.
        </p>

        <a href="<?= $basePath ?>/register" class="btn primary hero-btn" style="font-size: 20px; padding: 20px 60px; border-radius: 12px; font-weight: bold; background: #fff; color: #000; border: none;">
            Start Free Now <i class="fa-solid fa-arrow-right" style="margin-left: 8px;"></i>
        </a>
    </div>

    <footer style="margin-top: 40px; border-top: 1px solid var(--border); padding-top: 60px; padding-bottom: 24px;">
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 60px; margin-bottom: 60px; text-align: left;">
            
            <div>
                <h3 style="font-size: 24px; margin-bottom: 20px; color: var(--text-primary); font-weight: 800; display: flex; align-items: center; gap: 8px;">
                    <i class="fa-solid fa-chart-pie" style="color: var(--accent-blue);"></i> Budget<span style="color: var(--accent-blue);">Suite</span>
                </h3>
                <p style="color: var(--text-secondary); font-size: 15px; line-height: 1.7; max-width: 300px;">
                    Precision financial tracking and predictive analytics for absolute control over your personal and business wealth.
                </p>
            </div>

            <div>
                <h4 style="font-size: 14px; margin-bottom: 24px; color: var(--text-primary); text-transform: uppercase; letter-spacing: 1.5px;">Platform Access</h4>
                <div style="display: flex; flex-direction: column; gap: 16px;">
                    <a href="<?= $basePath ?>/login" class="footer-link"><i class="fa-solid fa-right-to-bracket" style="width: 20px;"></i> Secure Login</a>
                    <a href="<?= $basePath ?>/register" class="footer-link"><i class="fa-solid fa-user-plus" style="width: 20px;"></i> Create Account</a>
                </div>
            </div>

            <div>
                <h4 style="font-size: 14px; margin-bottom: 24px; color: var(--text-primary); text-transform: uppercase; letter-spacing: 1.5px;">System Data</h4>
                <div style="display: flex; flex-direction: column; gap: 16px;">
                    <span class="footer-link" style="cursor: default;"><i class="fa-solid fa-microchip" style="width: 20px;"></i> Offline-Ready Architecture</span>
                    <span class="footer-link" style="cursor: default;"><i class="fa-solid fa-location-dot" style="width: 20px;"></i> San Fernando, Pampanga</span>
                    <a href="mailto:support@budgetsuite.dev" class="footer-link"><i class="fa-solid fa-envelope" style="width: 20px;"></i> support@budgetsuite.dev</a>
                </div>
            </div>

        </div>

        <div style="border-top: 1px solid rgba(255,255,255,0.05); padding-top: 32px; display: flex; flex-wrap: wrap; justify-content: space-between; align-items: center; gap: 16px;">
            <p style="color: var(--text-muted); font-size: 14px; margin: 0;">
                &copy; <?= date('Y') ?> BudgetSuite Platform. All rights reserved.
            </p>
            <p style="color: var(--text-muted); font-size: 14px; margin: 0;">
                Designed & Engineered by <span style="color: var(--text-primary); font-weight: bold;">Jake Ashley C. Panlilio</span>
            </p>
        </div>
    </footer>

</div>
</body>
</html>