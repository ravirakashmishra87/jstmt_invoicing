<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Sign in · JST Machine Tools</title>
    <link href="https://fonts.googleapis.com/css2?family=Barlow:wght@600;700&family=Source+Sans+3:wght@400;600&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { font-family: "Source Sans 3", sans-serif; background: #e7eef5; min-height: 100vh; }
        .split { min-height: 100vh; display: grid; grid-template-columns: 1.1fr .9fr; }
        .panel {
            background: linear-gradient(160deg, #0f2744 0%, #1e4e79 70%, #2f6fa3 100%);
            color: #fff;
            padding: 3rem;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }
        .panel h1 { font-family: Barlow, sans-serif; font-size: 2.6rem; }
        .mark {
            width: 56px; height: 56px; border-radius: 12px; background: rgba(255,255,255,.14);
            display: grid; place-items: center; font-family: Barlow, sans-serif; font-weight: 700; font-size: 1.2rem;
        }
        .form-wrap { display: grid; place-items: center; padding: 2rem; }
        .card { border: 0; border-radius: 16px; box-shadow: 0 10px 30px rgba(15, 39, 68, .08); max-width: 440px; width: 100%; }
        .btn-primary { background: #1e4e79; border-color: #1e4e79; }
        @media (max-width: 900px) { .split { grid-template-columns: 1fr; } .panel { min-height: 220px; padding: 1.5rem; } }
    </style>
</head>
<body>
<div class="split">
    <section class="panel">
        <div class="mark">JST</div>
        <div>
            <h1>Machine tools.<br>Clean invoices.</h1>
            <p class="mb-0 mt-3" style="max-width: 28rem; color: #d6e6f5;">Quotations, GST tax invoices, serial-tracked stock and receipts for JST Machine Tools.</p>
        </div>
        <div style="color:#b7cbe0;">Gujarat · GST invoicing</div>
    </section>
    <section class="form-wrap">
        <div class="card p-4 p-md-5">
            <h2 class="h4 mb-1" style="font-family: Barlow, sans-serif;">Sign in</h2>
            <p class="text-muted mb-4">Use your username or email.</p>
            <form method="POST" action="{{ route('login.attempt') }}">
                @csrf
                <div class="mb-3">
                    <label class="form-label" for="login">Username or email</label>
                    <input id="login" name="login" value="{{ old('login') }}" class="form-control @error('login') is-invalid @enderror" required autofocus>
                    @error('login')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="mb-3">
                    <label class="form-label" for="password">Password</label>
                    <input id="password" type="password" name="password" class="form-control @error('password') is-invalid @enderror" required>
                    @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="form-check mb-4">
                    <input class="form-check-input" type="checkbox" name="remember" id="remember" value="1">
                    <label class="form-check-label" for="remember">Remember this browser</label>
                </div>
                <button class="btn btn-primary w-100" type="submit">Sign in</button>
            </form>
        </div>
    </section>
</div>
</body>
</html>
