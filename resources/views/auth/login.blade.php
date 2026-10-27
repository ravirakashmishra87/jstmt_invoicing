<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Sign in · JST Machine Tools</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Barlow:wght@500;600;700&family=Source+Sans+3:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        :root {
            --ink: #0f2744;
            --ink-2: #16365c;
            --steel: #1e4e79;
            --steel-2: #2f6fa3;
            --slate: #4a627a;
            --line: #d5dee8;
            --paper: #eef2f5;
            --card: #ffffff;
        }
        * { box-sizing: border-box; }
        html, body { min-height: 100%; }
        body {
            margin: 0;
            font-family: "Source Sans 3", sans-serif;
            color: #1e293b;
            background: var(--paper);
            overflow-x: hidden;
        }
        h1, h2 {
            font-family: Barlow, sans-serif;
            letter-spacing: .01em;
        }

        .login-shell {
            min-height: 100vh;
            min-height: 100dvh;
            display: grid;
            grid-template-columns: minmax(0, 1.15fr) minmax(320px, .85fr);
        }

        /* Light industrial hero — matches logo slate/silver palette */
        .login-hero {
            position: relative;
            isolation: isolate;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            padding: clamp(1.75rem, 4vw, 3.25rem);
            color: var(--ink);
            background:
                radial-gradient(900px 480px at 12% 8%, rgba(74, 98, 122, .12), transparent 55%),
                radial-gradient(700px 420px at 92% 88%, rgba(30, 78, 121, .1), transparent 50%),
                linear-gradient(165deg, #f7f9fb 0%, #eef2f5 42%, #e4ebf1 100%);
            overflow: hidden;
            border-right: 1px solid #d0dae4;
        }
        .login-hero::before {
            content: "";
            position: absolute;
            inset: 0;
            z-index: -1;
            background-image:
                linear-gradient(rgba(74, 98, 122, .07) 1px, transparent 1px),
                linear-gradient(90deg, rgba(74, 98, 122, .07) 1px, transparent 1px);
            background-size: 44px 44px;
            mask-image: radial-gradient(ellipse at 30% 20%, #000 0%, transparent 72%);
            animation: gridDrift 32s linear infinite;
        }
        .login-hero::after {
            content: "";
            position: absolute;
            width: 460px;
            height: 460px;
            right: -150px;
            bottom: -160px;
            border-radius: 50%;
            border: 1px solid rgba(74, 98, 122, .16);
            box-shadow:
                inset 0 0 0 48px rgba(74, 98, 122, .04),
                inset 0 0 0 96px rgba(74, 98, 122, .03);
            animation: ringPulse 9s ease-in-out infinite;
            pointer-events: none;
        }

        .hero-top {
            animation: riseIn .7s ease both;
        }
        .brand-logo {
            display: block;
            width: min(320px, 78%);
            height: auto;
            max-height: 150px;
            object-fit: contain;
            object-position: left center;
            filter: drop-shadow(0 10px 24px rgba(15, 39, 68, .1));
        }

        .hero-mid {
            max-width: 34rem;
            animation: riseIn .85s ease .08s both;
        }
        .hero-mid .eyebrow {
            margin: 0 0 .75rem;
            font-size: .74rem;
            letter-spacing: .16em;
            text-transform: uppercase;
            font-weight: 700;
            color: var(--slate);
        }
        .hero-mid h1 {
            margin: 0;
            font-size: clamp(2.1rem, 4.2vw, 3.15rem);
            font-weight: 700;
            line-height: 1.08;
            color: var(--ink);
        }
        .hero-mid h1 span {
            display: block;
            color: var(--slate);
            font-weight: 600;
        }
        .hero-mid p {
            margin: 1.1rem 0 0;
            font-size: 1.05rem;
            line-height: 1.55;
            color: #52657a;
            max-width: 28rem;
        }
        .capability-row {
            display: flex;
            flex-wrap: wrap;
            gap: .55rem;
            margin-top: 1.6rem;
        }
        .capability {
            padding: .4rem .75rem;
            border-radius: 999px;
            border: 1px solid rgba(74, 98, 122, .28);
            background: rgba(255, 255, 255, .72);
            color: var(--ink-2);
            font-size: .78rem;
            font-weight: 600;
            letter-spacing: .04em;
        }

        .hero-foot {
            color: #64788c;
            font-size: .9rem;
            animation: riseIn .9s ease .14s both;
        }

        .login-panel {
            position: relative;
            display: flex;
            flex-direction: column;
            justify-content: center;
            padding: clamp(1.5rem, 4vw, 3rem);
            background:
                radial-gradient(560px 260px at 80% 0%, rgba(74, 98, 122, .08), transparent 60%),
                linear-gradient(180deg, #e8eef3 0%, #dde5ec 100%);
        }
        .login-card {
            width: 100%;
            max-width: 420px;
            margin: 0 auto;
            background: var(--card);
            border: 1px solid var(--line);
            border-radius: 18px;
            box-shadow: 0 18px 48px rgba(15, 39, 68, .08);
            padding: clamp(1.5rem, 3vw, 2.25rem);
            animation: riseIn .75s ease .12s both;
        }
        .login-card .kicker {
            font-size: .72rem;
            letter-spacing: .14em;
            text-transform: uppercase;
            font-weight: 700;
            color: var(--slate);
            margin-bottom: .45rem;
        }
        .login-card h2 {
            margin: 0;
            font-size: 1.55rem;
            font-weight: 700;
            color: var(--ink);
        }
        .login-card .lede {
            margin: .4rem 0 1.4rem;
            color: #64748b;
            font-size: .95rem;
        }
        .login-card .form-label {
            font-size: .72rem;
            letter-spacing: .06em;
            text-transform: uppercase;
            font-weight: 700;
            color: #64748b;
        }
        .login-card .form-control {
            min-height: 46px;
            border-radius: 10px;
            border-color: #c5d4e4;
            font-size: .95rem;
        }
        .login-card .form-control:focus {
            border-color: var(--slate);
            box-shadow: 0 0 0 .2rem rgba(74, 98, 122, .18);
        }
        .login-card .form-check-label {
            color: #475569;
            font-size: .92rem;
        }
        .btn-login {
            min-height: 48px;
            border-radius: 10px;
            background: #4a627a;
            border-color: #4a627a;
            font-family: Barlow, sans-serif;
            font-weight: 700;
            letter-spacing: .03em;
            font-size: 1rem;
        }
        .btn-login:hover,
        .btn-login:focus {
            background: var(--ink);
            border-color: var(--ink);
        }

        .login-credit {
            margin: 1.75rem auto 0;
            max-width: 420px;
            text-align: center;
            color: #64748b;
            font-size: .82rem;
            line-height: 1.5;
            animation: riseIn .85s ease .2s both;
        }
        .login-credit a {
            color: var(--steel);
            font-weight: 700;
            text-decoration: none;
            border-bottom: 1px solid rgba(30, 78, 121, .35);
        }
        .login-credit a:hover {
            color: var(--ink);
            border-bottom-color: var(--ink);
        }

        @keyframes riseIn {
            from { opacity: 0; transform: translateY(14px); }
            to { opacity: 1; transform: translateY(0); }
        }
        @keyframes gridDrift {
            from { background-position: 0 0, 0 0; }
            to { background-position: 44px 44px, 44px 44px; }
        }
        @keyframes ringPulse {
            0%, 100% { transform: scale(1); opacity: .65; }
            50% { transform: scale(1.05); opacity: 1; }
        }

        @media (max-width: 960px) {
            .login-shell { grid-template-columns: 1fr; }
            .login-hero {
                min-height: auto;
                padding: 1.5rem 1.35rem 1.75rem;
                gap: 1.35rem;
                border-right: 0;
                border-bottom: 1px solid #d0dae4;
            }
            .brand-logo {
                width: min(240px, 70%);
                max-height: 110px;
            }
            .hero-mid h1 { font-size: clamp(1.75rem, 7vw, 2.25rem); }
            .login-hero::after { width: 240px; height: 240px; right: -80px; bottom: -100px; }
            .login-panel { padding: 1.35rem 1.15rem 1.75rem; }
        }

        @media (prefers-reduced-motion: reduce) {
            .login-hero::before,
            .login-hero::after,
            .hero-top,
            .hero-mid,
            .hero-foot,
            .login-card,
            .login-credit {
                animation: none !important;
            }
        }
    </style>
</head>
<body>
<div class="login-shell">
    <section class="login-hero" aria-label="JST Machine Tools">
        <div class="hero-top">
            <img
                class="brand-logo"
                src="{{ asset('images/jst-logo.png') }}"
                alt="JST Machine Tools"
                width="640"
                height="280"
            >
        </div>

        <div class="hero-mid">
            <p class="eyebrow">Engineering ERP</p>
            <h1>
                Precision operations.
                <span>Clear commercial control.</span>
            </h1>
            <p>Quotations, GST tax invoices, serial-tracked stock and receipts — built for machine-tool manufacturing.</p>
            <div class="capability-row" aria-hidden="true">
                <span class="capability">Quotations</span>
                <span class="capability">GST invoicing</span>
                <span class="capability">Stock &amp; serials</span>
                <span class="capability">Receipts</span>
            </div>
        </div>

        <div class="hero-foot">
            Production-ready commercial suite for machine shops
        </div>
    </section>

    <section class="login-panel">
        <div class="login-card">
            <div class="kicker">Secure access</div>
            <h2>Sign in</h2>
            <p class="lede">Enter your username or email to continue to the ERP workspace.</p>

            <form method="POST" action="{{ route('login.attempt') }}">
                @csrf
                <div class="mb-3">
                    <label class="form-label" for="login">Username or email</label>
                    <input id="login" name="login" value="{{ old('login') }}" class="form-control @error('login') is-invalid @enderror" required autofocus autocomplete="username">
                    @error('login')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="mb-3">
                    <label class="form-label" for="password">Password</label>
                    <input id="password" type="password" name="password" class="form-control @error('password') is-invalid @enderror" required autocomplete="current-password">
                    @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="form-check mb-4">
                    <input class="form-check-input" type="checkbox" name="remember" id="remember" value="1">
                    <label class="form-check-label" for="remember">Remember this browser</label>
                </div>
                <button class="btn btn-primary btn-login w-100" type="submit">Sign in to ERP</button>
            </form>
        </div>

        <p class="login-credit">
            Designed and managed by
            <a href="https://techchowk.com" target="_blank" rel="noopener noreferrer">TechChow Pvt. Ltd.</a>
        </p>
    </section>
</div>
</body>
</html>
