<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Giriş Yap - Cari & Stok Takip PRO</title>
    
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    
    <!-- Bootstrap 5 & Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    
    <style>
        body {
            font-family: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, sans-serif;
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1.5rem;
        }
        .login-card {
            background: #ffffff;
            border-radius: 20px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
            max-width: 440px;
            width: 100%;
            padding: 2.5rem;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
        .brand-icon {
            width: 56px;
            height: 56px;
            border-radius: 16px;
            background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: #ffffff;
            font-size: 1.6rem;
            margin: 0 auto 1.25rem auto;
            box-shadow: 0 10px 15px -3px rgba(37, 99, 235, 0.3);
        }
        .form-control {
            border-radius: 12px;
            padding: 0.75rem 1rem;
            font-size: 0.95rem;
            border: 1px solid #cbd5e1;
        }
        .form-control:focus {
            border-color: #3b82f6;
            box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.12);
        }
        .btn-login {
            background: #2563eb;
            border: none;
            border-radius: 12px;
            padding: 0.8rem;
            font-weight: 700;
            font-size: 1rem;
            transition: all 0.2s ease;
        }
        .btn-login:hover {
            background: #1d4ed8;
            transform: translateY(-1px);
            box-shadow: 0 10px 15px -3px rgba(37, 99, 235, 0.3);
        }
    </style>
</head>
<body>

    <div class="login-card">
        <div class="text-center mb-4">
            <div class="brand-icon">
                <i class="bi bi-wallet2"></i>
            </div>
            <h3 class="fw-bold text-dark mb-1">Cari & Stok Takip</h3>
            <p class="text-muted small">İşletme paneline erişmek için giriş yapın</p>
        </div>

        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show border-0 rounded-3 small py-2" role="alert">
                <i class="bi bi-check-circle me-1"></i> {{ session('success') }}
            </div>
        @endif

        @if ($errors->any())
            <div class="alert alert-danger border-0 rounded-3 small py-2 mb-3">
                <ul class="mb-0 ps-3">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form action="{{ route('login') }}" method="POST">
            @csrf

            <div class="mb-3">
                <label class="form-label small fw-bold text-secondary">E-Posta Adresi</label>
                <div class="input-group">
                    <span class="input-group-text bg-light border-end-0 rounded-start-3 text-muted">
                        <i class="bi bi-envelope"></i>
                    </span>
                    <input type="email" name="email" class="form-control rounded-end-3 border-start-0 ps-0" value="{{ old('email') }}" placeholder="ornek@firma.com" required autofocus>
                </div>
            </div>

            <div class="mb-3">
                <label class="form-label small fw-bold text-secondary">Şifre</label>
                <div class="input-group">
                    <span class="input-group-text bg-light border-end-0 rounded-start-3 text-muted">
                        <i class="bi bi-lock"></i>
                    </span>
                    <input type="password" name="password" class="form-control rounded-end-3 border-start-0 ps-0" placeholder="••••••••" required>
                </div>
            </div>

            <div class="mb-4 form-check">
                <input type="checkbox" name="remember" class="form-check-input" id="rememberMe">
                <label class="form-check-label text-muted small" for="rememberMe">Beni Hatırla</label>
            </div>

            <button type="submit" class="btn btn-primary btn-login w-100">Giriş Yap</button>

            <div class="text-center mt-4 pt-2 border-top">
                <span class="text-muted small">Henüz hesabınız yok mu?</span>
                <a href="{{ route('register') }}" class="text-decoration-none fw-bold small text-primary ms-1">Hemen Kayıt Olun</a>
            </div>
        </form>
    </div>

</body>
</html>
