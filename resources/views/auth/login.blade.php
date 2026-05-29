<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Sign in — Scholaria</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        html, body {
            height: 100%;
            font-family: ui-sans-serif, system-ui, -apple-system, sans-serif;
            color: #111827;
        }

        /* ─────────────────────────────
           PAGE WRAPPER
        ───────────────────────────── */
        .page {
            display: flex;
            flex-direction: row;
            min-height: 100vh;
        }

        /* ─────────────────────────────
           LEFT – BLUE BRAND PANEL
        ───────────────────────────── */
        .panel-left {
            flex: 0 0 55%;
            background: linear-gradient(145deg, #0b2d6b 0%, #1648a8 45%, #2563eb 80%, #1e40af 100%);
            position: relative;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 48px 40px;
            color: #fff;
        }

        /* decorative blobs */
        .panel-left::before {
            content: '';
            position: absolute;
            width: 520px; height: 520px;
            border-radius: 50%;
            background: rgba(255,255,255,0.06);
            top: -160px; left: -140px;
            pointer-events: none;
        }
        .panel-left::after {
            content: '';
            position: absolute;
            width: 320px; height: 320px;
            border-radius: 50%;
            background: rgba(255,255,255,0.06);
            bottom: -80px; right: -60px;
            pointer-events: none;
        }
        .blob-extra {
            position: absolute;
            border-radius: 50%;
            pointer-events: none;
        }
        .blob-c { width: 180px; height: 180px; bottom: 130px; left: -40px; background: rgba(255,255,255,0.04); }
        .blob-d { width: 100px; height: 100px; top: 210px; right: 50px; background: rgba(255,255,255,0.07); }

        /* centered logo */
        .left-logo {
            position: relative; z-index: 2;
            display: flex; flex-direction: column; align-items: center; gap: 20px;
        }
        .left-logo img {
            width: 280px;
            height: 280px;
            object-fit: contain;
            filter: drop-shadow(0 0 24px rgba(255,255,255,0.6)) drop-shadow(0 0 60px rgba(255,255,255,0.25));
        }

        /* footer */
        .left-footer {
            position: absolute;
            bottom: 28px; left: 0; right: 0;
            text-align: center;
            font-size: 11px;
            color: rgba(255,255,255,0.35);
            z-index: 2;
        }

        /* ─────────────────────────────
           RIGHT – FORM PANEL
        ───────────────────────────── */
        .panel-right {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #fff;
            padding: 48px 32px;
            overflow-y: auto;
        }

        .form-box {
            width: 100%;
            max-width: 400px;
        }

        .form-title {
            font-size: 28px;
            font-weight: 700;
            color: #111827;
            line-height: 1.2;
        }
        .form-subtitle {
            font-size: 14px;
            color: #9ca3af;
            margin-top: 6px;
        }

        /* ─── Fields ─── */
        .field { margin-top: 26px; }

        .field > label {
            display: block;
            font-size: 10px;
            font-weight: 700;
            letter-spacing: 0.12em;
            text-transform: uppercase;
            color: #9ca3af;
            margin-bottom: 8px;
        }

        .field-wrap { position: relative; }

        .field-wrap input {
            width: 100%;
            border: none;
            border-bottom: 1.5px solid #e5e7eb;
            padding: 10px 52px 10px 0;
            font-size: 14px;
            color: #111827;
            background: transparent;
            outline: none;
            transition: border-color 0.2s;
        }
        .field-wrap input:focus { border-bottom-color: #0b2d6b; }
        .field-wrap input::placeholder { color: #d1d5db; }
        .field-wrap input:-webkit-autofill {
            -webkit-box-shadow: 0 0 0 1000px #fff inset;
            -webkit-text-fill-color: #111827;
        }

        .toggle-btn {
            position: absolute;
            right: 0; bottom: 10px;
            font-size: 11px; font-weight: 700;
            color: #9ca3af;
            background: none; border: none; cursor: pointer;
            letter-spacing: 0.05em;
            transition: color 0.15s;
        }
        .toggle-btn:hover { color: #0b2d6b; }

        .field-error { font-size: 12px; color: #ef4444; margin-top: 5px; }

        /* ─── Row: remember + forgot ─── */
        .row-options {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-top: 22px;
            flex-wrap: wrap;
            gap: 10px;
        }
        .remember-label {
            display: flex; align-items: center; gap: 8px;
            font-size: 13px; color: #6b7280; cursor: pointer;
        }
        .remember-label input[type="checkbox"] {
            width: 15px; height: 15px; accent-color: #0b2d6b; cursor: pointer;
        }
        .forgot-link {
            font-size: 13px; color: #0b2d6b; font-weight: 500;
            text-decoration: none;
        }
        .forgot-link:hover { text-decoration: underline; }

        /* ─── Submit ─── */
        .btn-submit {
            display: block; width: 100%;
            margin-top: 28px; padding: 13px 20px;
            border-radius: 12px;
            background: #0b2d6b;
            color: #fff;
            font-size: 14px; font-weight: 600; letter-spacing: 0.04em;
            border: none; cursor: pointer;
            transition: background 0.2s, transform 0.1s;
        }
        .btn-submit:hover { background: #0a3a8a; }
        .btn-submit:active { transform: scale(0.98); }

        /* ─── Alert ─── */
        .alert-error {
            margin-top: 18px; padding: 12px 16px;
            border-radius: 10px;
            background: #fef2f2; border: 1px solid #fecaca;
            font-size: 13px; color: #b91c1c;
        }

        /* ═══════════════════════════════
           TABLET  (max 1024px)
        ═══════════════════════════════ */
        @media (max-width: 1024px) {
            .page { flex-direction: column; }

            .panel-left {
                flex: none;
                width: 100%;
                padding: 24px 24px 28px;
                flex-direction: row;
                align-items: center;
                justify-content: center;
                gap: 16px;
                min-height: unset;
            }

            /* hide decorative blobs on small screens */
            .panel-left::before,
            .panel-left::after,
            .blob-extra { display: none; }

            /* logo becomes smaller horizontal row on tablet/mobile */
            .left-logo { flex-direction: row; gap: 14px; }
            .left-logo img { width: 56px; height: 56px; filter: drop-shadow(0 0 10px rgba(255,255,255,0.5)); }
            .left-logo-name {
                font-size: 22px; font-weight: 700; letter-spacing: 0.01em;
            }

            .left-footer { display: none; }

            .panel-right {
                flex: 1;
                align-items: flex-start;
                padding: 36px 32px;
            }
        }

        /* ═══════════════════════════════
           MOBILE  (max 600px)
        ═══════════════════════════════ */
        @media (max-width: 600px) {
            .panel-left { padding: 20px 20px 22px; }
            .left-logo img { width: 48px; height: 48px; }
            .left-logo-name { font-size: 18px; }

            .panel-right { padding: 28px 20px 40px; }

            .form-title { font-size: 24px; }
            .row-options { flex-direction: column; align-items: flex-start; }
        }
    </style>
</head>
<body>
<div class="page">

    {{-- ── LEFT PANEL ── --}}
    <div class="panel-left">
        <div class="blob-extra blob-c"></div>
        <div class="blob-extra blob-d"></div>

        {{-- single centered logo --}}
        <div class="left-logo">
            <img src="{{ asset('SCHOLORIA LOGO.png') }}" alt="Scholaria Logo" />
            <span class="left-logo-name" style="display:none;color:#fff;font-weight:700;">Scholaria</span>
        </div>

        <div class="left-footer">
            &copy; {{ date('Y') }} Scholaria &mdash; Secure access for administrators and users.
        </div>
    </div>

    {{-- ── RIGHT PANEL ── --}}
    <div class="panel-right">
        <div class="form-box">

            <h1 class="form-title">Sign In</h1>
            <p class="form-subtitle">Use your account credentials to continue.</p>

            @if (session('error'))
                <div class="alert-error">{{ session('error') }}</div>
            @endif

            <form method="POST" action="{{ route('login.store') }}" novalidate>
                @csrf

                {{-- Email / Name --}}
                <div class="field">
                    <label for="login">Email / Name</label>
                    <div class="field-wrap">
                        <input
                            id="login" name="login" type="text"
                            autocomplete="username"
                            value="{{ old('login') }}"
                            placeholder="your@email.com"
                            required
                        />
                    </div>
                    @error('login')
                        <div class="field-error">{{ $message }}</div>
                    @enderror
                </div>

                {{-- Password --}}
                <div class="field">
                    <label for="password">Password</label>
                    <div class="field-wrap">
                        <input
                            id="password" name="password" type="password"
                            autocomplete="current-password"
                            placeholder="••••••••••"
                            required
                        />
                        <button type="button" id="togglePassword" class="toggle-btn">SHOW</button>
                    </div>
                    @error('password')
                        <div class="field-error">{{ $message }}</div>
                    @enderror
                </div>

                {{-- Remember me + Forgot password --}}
                <div class="row-options">
                    <label class="remember-label">
                        <input
                            type="checkbox" name="remember" value="1"
                            {{ old('remember') ? 'checked' : '' }}
                        />
                        Remember me
                    </label>
                    @if (Route::has('password.request'))
                        <a href="{{ route('password.request') }}" class="forgot-link">Forgot password?</a>
                    @endif
                </div>

                <button type="submit" class="btn-submit">Sign in &rarr;</button>
            </form>

        </div>
    </div>

</div>
<script>
    // Show "Scholaria" text label next to logo on tablet/mobile
    (function () {
        function checkBreakpoint() {
            const name = document.querySelector('.left-logo-name');
            if (!name) return;
            name.style.display = window.innerWidth <= 1024 ? 'block' : 'none';
        }
        checkBreakpoint();
        window.addEventListener('resize', checkBreakpoint);
    })();

    // Toggle password visibility
    (function () {
        const btn = document.getElementById('togglePassword');
        const input = document.getElementById('password');
        if (!btn || !input) return;
        btn.addEventListener('click', function () {
            const isPass = input.type === 'password';
            input.type = isPass ? 'text' : 'password';
            btn.textContent = isPass ? 'HIDE' : 'SHOW';
        });
    })();
</script>
</body>
</html>
