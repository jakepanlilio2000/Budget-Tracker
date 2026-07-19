<?php
declare(strict_types=1);
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ExpensePro - The Financial Operating System for Individuals & Small Businesses</title>
    <meta name="description"
        content="Track expenses, manage salaries, forecast cash flow, and achieve your savings goals with enterprise-grade precision. Features include Advanced Analytics, Dashboard Builder, and Achievement System.">

    <!-- Open Graph / Facebook -->
    <meta property="og:type" content="website">
    <meta property="og:title" content="ExpensePro - The Financial Operating System">
    <meta property="og:description"
        content="Master your financial future with enterprise-grade expense tracking, cash flow forecasting, and customizable dashboards.">

    <!-- Twitter -->
    <meta property="twitter:card" content="summary_large_image">
    <meta property="twitter:title" content="ExpensePro - The Financial Operating System">
    <meta property="twitter:description"
        content="Master your financial future with enterprise-grade expense tracking, cash flow forecasting, and customizable dashboards.">

    <link rel="stylesheet" href="<?= asset('css/app.css') ?>">
    <link rel="stylesheet" href="<?= asset('css/landing.css') ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="icon" href="<?= asset('favicon.ico') ?>" sizes="any">
</head>

<body class="landing-body">
    <!-- Floating Public Navbar -->
    <nav class="landing-nav glass">
        <div class="nav-container">
            <a href="<?= url('/') ?>" class="nav-logo">
                <i class="fas fa-wallet"></i> ExpensePro
            </a>
            <div class="nav-links" id="navLinks">
                <a href="#features">Features</a>
                <a href="#showcase">Showcase</a>
                <a href="<?= url('/login') ?>" class="btn btn-ghost">Sign In</a>
                <a href="<?= url('/register') ?>" class="btn btn-primary">Get Started</a>
            </div>
            <button class="nav-toggle" onclick="document.getElementById('navLinks').classList.toggle('open')"
                aria-label="Toggle Menu">
                <i class="fas fa-bars"></i>
            </button>
        </div>
    </nav>

    <main class="landing-main">
        <?= $content ?? '' ?>
    </main>

    <!-- Minimal JS for landing page interactions -->
    <script>

        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    e.preventDefault();
                    target.scrollIntoView({ behavior: 'smooth', block: 'start' });
                }
            });
        });


        const sections = document.querySelectorAll('.fade-in-section');
        const sectionObserver = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('is-visible');
                    sectionObserver.unobserve(entry.target);
                }
            });
        }, { threshold: 0.1 });
        sections.forEach(section => sectionObserver.observe(section));

        const counters = document.querySelectorAll('.kpi-counter');
        const counterObserver = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    const target = entry.target;
                    const endValue = parseInt(target.getAttribute('data-target'));
                    const suffix = target.getAttribute('data-suffix') || '';
                    let current = 0;
                    const increment = endValue / 50;
                    const timer = setInterval(() => {
                        current += increment;
                        if (current >= endValue) {
                            current = endValue;
                            clearInterval(timer);
                        }
                        target.textContent = Math.floor(current) + suffix;
                    }, 20);
                    counterObserver.unobserve(target);
                }
            });
        }, { threshold: 0.5 });
        counters.forEach(counter => counterObserver.observe(counter));
    </script>
</body>

</html>