<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kayıt Ol - Cari & Stok Takip PRO</title>
    
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
        .register-card {
            background: #ffffff;
            border-radius: 20px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
            max-width: 480px;
            width: 100%;
            padding: 2.5rem;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
        .brand-icon {
            width: 56px;
            height: 56px;
            border-radius: 16px;
            background: linear-gradient(135deg, #059669 0%, #047857 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: #ffffff;
            font-size: 1.6rem;
            margin: 0 auto 1.25rem auto;
            box-shadow: 0 10px 15px -3px rgba(5, 150, 105, 0.3);
        }
        .form-control {
            border-radius: 12px;
            padding: 0.75rem 1rem;
            font-size: 0.95rem;
            border: 1px solid #cbd5e1;
        }
        .form-control:focus {
            border-color: #10b981;
            box-shadow: 0 0 0 4px rgba(16, 185, 129, 0.12);
        }
        .btn-register {
            background: #059669;
            border: none;
            border-radius: 12px;
            padding: 0.8rem;
            font-weight: 700;
            font-size: 1rem;
            transition: all 0.2s ease;
        }
        .btn-register:hover {
            background: #047857;
            transform: translateY(-1px);
            box-shadow: 0 10px 15px -3px rgba(5, 150, 105, 0.3);
        }
    </style>
</head>
<body>

    <div class="register-card">
        <div class="text-center mb-4">
            <div class="brand-icon">
                <i class="bi bi-person-plus-fill"></i>
            </div>
            <h3 class="fw-bold text-dark mb-1">Yeni Hesap Oluştur</h3>
            <p class="text-muted small">Cari ve stok yönetim sistemine katılın</p>
        </div>

        @if ($errors->any())
            <div class="alert alert-danger border-0 rounded-3 small py-2 mb-3">
                <ul class="mb-0 ps-3">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form action="{{ route('register') }}" method="POST">
            @csrf

            <div class="mb-3">
                <label class="form-label small fw-bold text-secondary">Ad Soyad</label>
                <div class="input-group">
                    <span class="input-group-text bg-light border-end-0 rounded-start-3 text-muted">
                        <i class="bi bi-person"></i>
                    </span>
                    <input type="text" name="name" class="form-control rounded-end-3 border-start-0 ps-0" value="{{ old('name') }}" placeholder="Ahmet Yılmaz" required autofocus>
                </div>
            </div>

            <div class="mb-3">
                <label class="form-label small fw-bold text-secondary">E-Posta Adresi</label>
                <div class="input-group">
                    <span class="input-group-text bg-light border-end-0 rounded-start-3 text-muted">
                        <i class="bi bi-envelope"></i>
                    </span>
                    <input type="email" name="email" class="form-control rounded-end-3 border-start-0 ps-0" value="{{ old('email') }}" placeholder="ahmet@firma.com" required>
                </div>
            </div>

            <div class="row g-2 mb-4">
                <div class="col-md-6">
                    <label class="form-label small fw-bold text-secondary">Şifre</label>
                    <input type="password" name="password" class="form-control" placeholder="••••••••" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label small fw-bold text-secondary">Şifre Tekrar</label>
                    <input type="password" name="password_confirmation" class="form-control" placeholder="••••••••" required>
                </div>
            </div>

            <button type="submit" class="btn btn-success btn-register text-white w-100">Kayıt Ol ve Giriş Yap</button>

            <div class="text-center mt-4 pt-2 border-top">
                <span class="text-muted small">Zaten bir hesabınız var mı?</span>
                <a href="{{ route('login') }}" class="text-decoration-none fw-bold small text-success ms-1">Giriş Yapın</a>
            </div>
        </form>
    </div>

</body>
</html>
