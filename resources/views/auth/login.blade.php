<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Login - SIP Tirta Perwira</title>
    <style>
        :root {
            --bg: #f4f7fb;
            --card: #ffffff;
            --text: #0f172a;
            --muted: #64748b;
            --accent: #1d4ed8;
            --accent-2: #1e40af;
            --line: #e2e8f0;
            --danger: #b91c1c;
        }

        * { box-sizing: border-box; }

        body {
            margin: 0;
            min-height: 100vh;
            font-family: "Segoe UI", "Noto Sans", sans-serif;
            background: var(--bg);
            color: var(--text);
        }

        .layout {
            display: grid;
            min-height: 100vh;
            grid-template-columns: 1fr;
        }

        .branding {
            display: none;
            position: relative;
            padding: 48px;
            color: #fff;
            background: radial-gradient(circle at 18% 20%, #cddcff 0%, #2f63da 55%, #2148b4 100%);
        }

        .branding-inner {
            max-width: 560px;
            margin: auto;
            text-align: center;
        }

        .branding h1 {
            margin: 0;
            font-size: 52px;
            line-height: 1.05;
            font-weight: 900;
            letter-spacing: -0.02em;
        }

        .branding p {
            margin-top: 18px;
            font-size: 21px;
            line-height: 1.45;
            opacity: 0.95;
        }

        .form-wrap {
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 32px 20px;
        }

        .card {
            width: 100%;
            max-width: 520px;
            background: var(--card);
            border: 1px solid var(--line);
            border-radius: 20px;
            padding: 32px 28px;
            box-shadow: 0 20px 40px rgba(15, 23, 42, 0.08);
        }

        .title {
            margin: 0;
            font-size: 34px;
            line-height: 1.1;
            font-weight: 850;
        }

        .subtitle {
            margin: 10px 0 0;
            color: var(--muted);
            font-size: 15px;
        }

        .field {
            margin-top: 16px;
        }

        .field label {
            display: block;
            margin-bottom: 8px;
            font-size: 14px;
            color: #334155;
            font-weight: 600;
        }

        .field input[type="text"],
        .field input[type="password"] {
            width: 100%;
            border: 1px solid #cbd5e1;
            border-radius: 12px;
            padding: 12px 14px;
            font-size: 15px;
            outline: none;
            transition: border-color 120ms ease, box-shadow 120ms ease;
        }

        .field input:focus {
            border-color: var(--accent);
            box-shadow: 0 0 0 3px rgba(29, 78, 216, 0.12);
        }

        .remember {
            margin-top: 14px;
            display: flex;
            align-items: center;
            gap: 8px;
            color: #475569;
            font-size: 14px;
        }

        .error-box {
            margin-top: 14px;
            padding: 11px 12px;
            border-radius: 10px;
            border: 1px solid #fecaca;
            background: #fef2f2;
            color: var(--danger);
            font-size: 14px;
        }

        .btn {
            margin-top: 22px;
            width: 100%;
            border: 0;
            border-radius: 12px;
            background: linear-gradient(90deg, var(--accent), var(--accent-2));
            color: #fff;
            font-size: 15px;
            font-weight: 700;
            letter-spacing: 0.03em;
            padding: 13px 14px;
            cursor: pointer;
        }

        .footer {
            margin-top: 22px;
            text-align: center;
            color: #94a3b8;
            font-size: 12px;
            letter-spacing: 0.12em;
            text-transform: uppercase;
        }

        @media (min-width: 1024px) {
            .layout {
                grid-template-columns: 1fr 1fr;
            }

            .branding {
                display: flex;
            }

            .form-wrap {
                padding: 40px;
            }

            .card {
                border: 0;
                box-shadow: none;
                background: transparent;
                max-width: 480px;
                padding: 0;
            }
        }
    </style>
</head>
<body>
<div class="layout">
    <section class="branding" aria-hidden="true">
        <div class="branding-inner">
            <h1>SIP TIRTA PERWIRA</h1>
            <p>Sistem Akuntansi SAKEP untuk transaksi, jurnal, dan pelaporan keuangan terintegrasi.</p>
        </div>
    </section>

    <section class="form-wrap">
        <div class="card">
            <h1 class="title">Selamat Datang</h1>
            <p class="subtitle">Silakan login untuk mengakses portal akuntansi Tirta Perwira.</p>

            <form method="POST" action="{{ route('auth.custom.login') }}" novalidate>
                @csrf
                <input type="hidden" name="panel" value="{{ $panel }}">

                <div class="field">
                    <label for="login">Username atau Email</label>
                    <input id="login" type="text" name="login" value="{{ old('login') }}" required autofocus>
                </div>

                <div class="field">
                    <label for="password">Password</label>
                    <input id="password" type="password" name="password" required>
                </div>

                <label class="remember" for="remember">
                    <input id="remember" type="checkbox" name="remember" value="1" {{ old('remember') ? 'checked' : '' }}>
                    Ingat saya
                </label>

                @if ($errors->any())
                    <div class="error-box">
                        {{ $errors->first('login') ?? $errors->first() }}
                    </div>
                @endif

                <button class="btn" type="submit">MASUK KE PORTAL</button>
            </form>

            <div class="footer">Powered by Tirta Perwira Accounting</div>
        </div>
    </section>
</div>
</body>
</html>
