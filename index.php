<?php
session_start();
?>
<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BridgeGuard — Monitorizare Inteligentă</title>
    <link rel="stylesheet" href="assets/style.css">
    <style>
        .hero-section {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            overflow: hidden;
            text-align: center;
        }
        .hero-section::before {
            content: '';
            position: absolute;
            inset: 0;
            background:
                radial-gradient(ellipse at 30% 40%, rgba(0, 217, 126, 0.04) 0%, transparent 50%),
                radial-gradient(ellipse at 70% 60%, rgba(16, 185, 129, 0.03) 0%, transparent 50%);
            pointer-events: none;
        }
        .hero-grid-bg {
            position: absolute;
            inset: 0;
            background-image:
                linear-gradient(rgba(0, 217, 126, 0.03) 1px, transparent 1px),
                linear-gradient(90deg, rgba(0, 217, 126, 0.03) 1px, transparent 1px);
            background-size: 60px 60px;
            mask-image: radial-gradient(ellipse at center, black 30%, transparent 70%);
            -webkit-mask-image: radial-gradient(ellipse at center, black 30%, transparent 70%);
            animation: grid-drift 20s linear infinite;
        }
        @keyframes grid-drift {
            0% { transform: translate(0, 0); }
            100% { transform: translate(60px, 60px); }
        }

        .hero-content {
            position: relative;
            z-index: 10;
            max-width: 700px;
        }
        .hero-badge {
            font-family: var(--font-mono);
            color: var(--clr-accent);
            letter-spacing: 3px;
            font-size: 11px;
            margin-bottom: 32px;
            display: inline-block;
            background: var(--clr-accent-dim);
            padding: 8px 20px;
            border-radius: 999px;
            border: 1px solid rgba(0, 217, 126, 0.12);
            text-transform: uppercase;
        }
        .hero-title {
            font-family: var(--font-display);
            font-size: clamp(44px, 6vw, 76px);
            font-weight: 800;
            line-height: 1.05;
            margin-bottom: 28px;
            color: #fff;
            letter-spacing: -1px;
        }
        .hero-title .accent {
            background: linear-gradient(135deg, var(--clr-accent), #10b981);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        .hero-subtitle {
            font-family: var(--font-body);
            font-size: 18px;
            color: var(--clr-subtle);
            margin-bottom: 48px;
            max-width: 500px;
            margin-left: auto;
            margin-right: auto;
            font-weight: 400;
            line-height: 1.7;
        }
        .hero-actions {
            display: flex;
            gap: 20px;
            align-items: center;
            justify-content: center;
        }

        .stats-section {
            padding: 100px 0;
            position: relative;
        }
        .stats-section::before {
            content: '';
            position: absolute;
            top: 0;
            left: 50%;
            transform: translateX(-50%);
            width: 40%;
            height: 1px;
            background: linear-gradient(90deg, transparent, var(--clr-accent), transparent);
            opacity: 0.2;
        }
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 40px;
            text-align: center;
        }
        .stat-item {
            padding: 32px;
        }
        .stat-number {
            font-family: var(--font-mono);
            font-size: 48px;
            font-weight: 600;
            color: var(--clr-accent);
            margin-bottom: 12px;
        }
        .stat-label {
            font-family: var(--font-mono);
            color: var(--clr-muted);
            font-size: 12px;
            letter-spacing: 2px;
            text-transform: uppercase;
        }

        .features-section {
            padding: 80px 0 120px;
        }
        .section-header {
            text-align: center;
            margin-bottom: 80px;
        }
        .section-header h2 {
            font-family: var(--font-display);
            font-size: 36px;
            margin-bottom: 16px;
            color: var(--clr-text);
        }
        .section-header p {
            color: var(--clr-subtle);
            max-width: 500px;
            margin: 0 auto;
            font-size: 16px;
        }
        .features-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 24px;
        }
        .feature-card {
            background: linear-gradient(135deg, rgba(17, 26, 46, 0.6), rgba(12, 18, 34, 0.8));
            border: 1px solid var(--clr-border);
            border-radius: 16px;
            padding: 36px 28px;
            transition: all var(--dur-base) var(--ease-spring);
            position: relative;
            overflow: hidden;
        }
        .feature-card::before {
            content: '';
            position: absolute;
            top: 0; left: 0; right: 0;
            height: 2px;
            background: linear-gradient(90deg, var(--clr-accent), var(--clr-blue));
            opacity: 0;
            transition: opacity var(--dur-base);
        }
        .feature-card:hover::before { opacity: 1; }
        .feature-card:hover {
            transform: translateY(-6px);
            box-shadow: 0 20px 50px rgba(0, 0, 0, 0.4), 0 0 30px rgba(0, 217, 126, 0.06);
            border-color: var(--clr-border-h);
        }
        .feature-icon {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            background: var(--clr-accent-dim);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 22px;
            margin-bottom: 20px;
        }
        .feature-title {
            font-family: var(--font-display);
            font-size: 18px;
            margin-bottom: 12px;
            color: #fff;
        }
        .feature-desc {
            font-family: var(--font-body);
            color: var(--clr-subtle);
            line-height: 1.7;
            font-size: 14px;
        }

        .cta-section {
            padding: 120px 0;
            text-align: center;
            position: relative;
        }
        .cta-section::before {
            content: '';
            position: absolute;
            inset: 0;
            background: radial-gradient(ellipse at 50% 50%, rgba(0, 217, 126, 0.04) 0%, transparent 60%);
            pointer-events: none;
        }

        .footer {
            background: var(--clr-bg-alt);
            padding: 60px 0 32px;
            border-top: 1px solid var(--clr-border);
        }
        .footer-inner {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .footer-logo {
            font-family: var(--font-display);
            font-size: 18px;
            font-weight: 800;
            color: var(--clr-text);
            letter-spacing: 2px;
        }
        .footer-copy {
            font-family: var(--font-mono);
            font-size: 11px;
            color: var(--clr-muted);
        }

        .reveal {
            opacity: 0;
            transform: translateY(24px);
            transition: opacity 0.8s var(--ease-smooth), transform 0.8s var(--ease-smooth);
        }
        .reveal.visible {
            opacity: 1;
            transform: translateY(0);
        }

        @media (max-width: 768px) {
            .stats-grid, .features-grid { grid-template-columns: 1fr; }
            .hero-actions { flex-direction: column; }
            .footer-inner { flex-direction: column; gap: 16px; text-align: center; }
        }
    </style>
</head>
<body>

<div class="cursor-glow" id="cursorGlow"></div>

<nav class="navbar" style="position: fixed; width: 100%; border-bottom: none; background: transparent; transition: all 0.3s ease;">
    <div class="nav-brand">
        <span class="nav-brand-title">BRIDGEGUARD</span>
    </div>
    <div class="nav-right">
        <?php if(isset($_SESSION['user'])): ?>
            <a href="dashboard.php" class="btn-primary" style="padding: 10px 24px; font-size: 14px;">Dashboard</a>
        <?php else: ?>
            <a href="login.php" class="btn-secondary" style="padding: 10px 24px; font-size: 13px; border: none; color: var(--clr-subtle);">Login</a>
            <a href="login.php" class="btn-primary" style="padding: 10px 24px; font-size: 13px;">Cont Nou</a>
        <?php endif; ?>
    </div>
</nav>

<main>
    <section class="hero-section">
        <div class="hero-grid-bg"></div>
        <div class="hero-content reveal visible">
            <div class="hero-badge">STRUCTURAL HEALTH MONITORING</div>
            <h1 class="hero-title">Sistem de monitorizare<br><span class="accent">inteligentă a podurilor.</span></h1>
            <p class="hero-subtitle">Monitorizare structurală în timp real cu senzori IoT de precizie. Prevenție prin tehnologie.</p>
            <div class="hero-actions">
                <a href="login.php" class="btn-primary" style="padding: 16px 36px; font-size: 16px;">Accesează Platforma</a>
                <a href="#features" class="btn-secondary">Află mai multe</a>
            </div>
        </div>
    </section>

    <section class="stats-section">
        <div class="container stats-grid reveal">
            <div class="stat-item">
                <div class="stat-number">24/7</div>
                <div class="stat-label">Monitorizare continuă</div>
            </div>
            <div class="stat-item">
                <div class="stat-number">&lt; 1s</div>
                <div class="stat-label">Latență alerte</div>
            </div>
            <div class="stat-item">
                <div class="stat-number">99.9%</div>
                <div class="stat-label">Uptime sistem</div>
            </div>
        </div>
    </section>

    <section id="features" class="features-section container reveal">
        <div class="section-header">
            <h2>Tehnologie avansată</h2>
            <p>Senzori de precizie industrială într-o interfață simplă și eficientă.</p>
        </div>

        <div class="features-grid">
            <div class="feature-card">
                <div class="feature-icon">📡</div>
                <h3 class="feature-title">Senzori Multipli</h3>
                <p class="feature-desc">Vibrații, deflecție, giroscop, temperatură — monitorizare completă a stării structurale.</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon">⚡</div>
                <h3 class="feature-title">Alerte Instantanee</h3>
                <p class="feature-desc">Notificări automate la depășirea limitelor de siguranță. Decizii rapide în timp real.</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon">🔒</div>
                <h3 class="feature-title">Acces Securizat</h3>
                <p class="feature-desc">Acces restricționat prin cod de administrator. Doar personalul autorizat poate gestiona platforma.</p>
            </div>
        </div>
    </section>

    <section class="cta-section reveal">
        <div class="container">
            <h2 style="font-family: var(--font-display); font-size: 36px; margin-bottom: 28px; color: #fff;">Pregătit pentru implementare?</h2>
            <a href="login.php" class="btn-primary" style="font-size: 16px; padding: 16px 40px;">Creează cont de administrator</a>
        </div>
    </section>
</main>

<footer class="footer">
    <div class="container" style="padding-bottom: 0;">
        <div class="footer-inner">
            <span class="footer-logo">BRIDGEGUARD</span>
            <span class="footer-copy">&copy; <?= date('Y') ?> BridgeGuard Systems. Toate drepturile rezervate.</span>
        </div>
    </div>
</footer>

<script>
    const glow = document.getElementById('cursorGlow');
    document.addEventListener('mousemove', e => {
        glow.style.left = e.clientX + 'px';
        glow.style.top = e.clientY + 'px';
    });

    window.addEventListener('scroll', () => {
        const nav = document.querySelector('.navbar');
        if (window.scrollY > 50) {
            nav.style.background = 'rgba(6, 10, 19, 0.92)';
            nav.style.backdropFilter = 'blur(24px)';
            nav.style.borderBottom = '1px solid rgba(60, 180, 100, 0.08)';
        } else {
            nav.style.background = 'transparent';
            nav.style.backdropFilter = 'none';
            nav.style.borderBottom = 'none';
        }
    });

    const observer = new IntersectionObserver(entries => {
        entries.forEach((entry, i) => {
            if (entry.isIntersecting) {
                setTimeout(() => entry.target.classList.add('visible'), i * 80);
            }
        });
    }, { threshold: 0.1 });
    document.querySelectorAll('.reveal').forEach(el => observer.observe(el));

    document.querySelectorAll('.btn-primary, .btn-secondary').forEach(btn => {
        btn.addEventListener('click', function(e) {
            const ripple = document.createElement('span');
            ripple.classList.add('btn-ripple');
            const rect = this.getBoundingClientRect();
            ripple.style.width = ripple.style.height = Math.max(rect.width, rect.height) + 'px';
            ripple.style.left = (e.clientX - rect.left - Math.max(rect.width, rect.height) / 2) + 'px';
            ripple.style.top = (e.clientY - rect.top - Math.max(rect.width, rect.height) / 2) + 'px';
            this.appendChild(ripple);
            setTimeout(() => ripple.remove(), 600);
        });
    });
</script>

</body>
</html>
