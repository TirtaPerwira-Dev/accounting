<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Terjadi Kendala</title>
    <style>
        body {
            margin: 0;
            font-family: "Segoe UI", Tahoma, Arial, sans-serif;
            background: linear-gradient(135deg, #f4f7ff 0%, #e4ebff 100%);
            color: #1f2a44;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px;
        }

        .card {
            max-width: 560px;
            width: 100%;
            background: #ffffff;
            border-radius: 18px;
            box-shadow: 0 12px 38px rgba(47, 99, 218, 0.16);
            padding: 36px;
            text-align: center;
            border: 1px solid #d7e1ff;
        }

        .badge {
            display: inline-block;
            background: #2f63da;
            color: #fff;
            border-radius: 999px;
            font-weight: 700;
            font-size: 13px;
            padding: 8px 14px;
            letter-spacing: 0.05em;
            text-transform: uppercase;
            margin-bottom: 16px;
        }

        h1 {
            margin: 0;
            font-size: 30px;
            line-height: 1.2;
        }

        p {
            margin-top: 14px;
            font-size: 16px;
            line-height: 1.6;
            color: #4b5879;
        }

        .actions {
            margin-top: 28px;
        }

        a {
            display: inline-block;
            text-decoration: none;
            background: #2f63da;
            color: #fff;
            font-weight: 700;
            padding: 12px 20px;
            border-radius: 12px;
        }
    </style>
</head>
<body>
<div class="card">
    <span class="badge">Error {{ $status ?? 500 }}</span>
    <h1>Mohon Maaf, Terjadi Kendala</h1>
    <p>{{ $message ?? 'Sistem sedang tidak dapat memproses permintaan Anda.' }}</p>
    <div class="actions">
        <a href="{{ url()->previous() }}">Kembali</a>
    </div>
</div>
</body>
</html>
