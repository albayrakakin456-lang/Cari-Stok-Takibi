<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Cari, Kasa & Stok Takip Sistemi')</title>
    
    <!-- Erken Tema Başlatıcı (FOUC / Beyaz parlama önleyici) -->
    <script>
        (function () {
            const savedTheme = localStorage.getItem('app-theme') || 'light';
            document.documentElement.setAttribute('data-bs-theme', savedTheme);
        })();
    </script>

    <!-- Google Fonts: Plus Jakarta Sans (Modern Fintech Tipografisi) -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    
    <!-- Bootstrap 5 & Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    
    <style>
        :root, [data-bs-theme="light"] {
            --bs-body-font-family: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, sans-serif;
            --bs-body-bg: #f8fafc;
            --primary-accent: #3b82f6;
            --dark-navbar: #0f172a;
            --card-bg: #ffffff;
            --card-border: #e2e8f0;
            --text-heading: #0f172a;
        }

        [data-bs-theme="dark"] {
            --bs-body-font-family: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, sans-serif;
            --bs-body-bg: #0b0f19;
            --primary-accent: #3b82f6;
            --dark-navbar: #030712;
            --card-bg: #111827;
            --card-border: #1f2937;
            --text-heading: #f9fafb;
        }

        body {
            font-family: var(--bs-body-font-family);
            background-color: var(--bs-body-bg);
            color: var(--bs-body-color);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            transition: background-color 0.2s ease, color 0.2s ease;
        }

        /* Navbar Tasarımı */
        .navbar-custom {
            background-color: var(--dark-navbar) !important;
            border-bottom: 1px solid rgba(255, 255, 255, 0.08);
        }
        .navbar-brand {
            font-weight: 800;
            font-size: 1.15rem;
            letter-spacing: -0.5px;
            white-space: nowrap;
        }
        .nav-link {
            font-weight: 600;
            font-size: 0.84rem;
            color: #94a3b8 !important;
            padding: 0.42rem 0.65rem !important;
            border-radius: 8px;
            transition: all 0.2s ease;
            white-space: nowrap;
            display: inline-flex;
            align-items: center;
            gap: 0.45rem;
        }
        .nav-link i {
            font-size: 0.95rem;
            line-height: 1;
        }
        .nav-link:hover {
            color: #ffffff !important;
            background-color: rgba(255, 255, 255, 0.06);
        }
        .nav-link.active {
            color: #ffffff !important;
            background-color: rgba(59, 130, 246, 0.2);
            font-weight: 600;
        }

        /* Stok Kodu ve Rozet Stilleri */
        .stock-code-badge {
            font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
            font-size: 0.825rem;
            font-weight: 700;
            padding: 0.25rem 0.6rem;
            border-radius: 6px;
            background-color: #f1f5f9;
            color: #1e293b;
            border: 1px solid #cbd5e1;
            display: inline-block;
            letter-spacing: 0.4px;
        }
        [data-bs-theme="dark"] .stock-code-badge {
            background-color: #1e293b !important;
            color: #38bdf8 !important;
            border-color: #334155 !important;
        }

        /* Tüm bg-light ve btn-light elemanlarının karanlık mod uyumu */
        [data-bs-theme="dark"] .badge.bg-light,
        [data-bs-theme="dark"] .badge.bg-light.text-dark,
        [data-bs-theme="dark"] .bg-light {
            background-color: #1e293b !important;
            color: #e2e8f0 !important;
            border-color: #334155 !important;
        }
        [data-bs-theme="dark"] .btn-light {
            background-color: #1e293b !important;
            color: #f1f5f9 !important;
            border-color: #334155 !important;
        }
        [data-bs-theme="dark"] .btn-light:hover {
            background-color: #334155 !important;
            color: #ffffff !important;
            border-color: #475569 !important;
        }

        /* Kart ve Tablo Stilleri */
        .card {
            border: 1px solid var(--card-border);
            border-radius: 14px;
            box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.04), 0 1px 2px -1px rgba(0, 0, 0, 0.04);
            background-color: var(--card-bg) !important;
        }
        .card-header {
            border-top-left-radius: 14px !important;
            border-top-right-radius: 14px !important;
            font-weight: 700;
            border-bottom-color: var(--card-border);
        }

        /* Renk ve Arka Plan Dark Uyumu */
        .bg-white {
            background-color: var(--card-bg) !important;
        }
        .text-dark {
            color: var(--text-heading) !important;
        }
        [data-bs-theme="dark"] .border {
            border-color: var(--card-border) !important;
        }
        [data-bs-theme="dark"] .border-top {
            border-top-color: var(--card-border) !important;
        }
        [data-bs-theme="dark"] .border-bottom {
            border-bottom-color: var(--card-border) !important;
        }

        .table thead th {
            font-size: 0.8rem;
            text-transform: uppercase;
            letter-spacing: 0.6px;
            font-weight: 700;
            color: #64748b;
            border-bottom: 2px solid var(--card-border);
        }
        [data-bs-theme="dark"] .table thead th {
            color: #94a3b8;
            border-bottom-color: #374151;
        }
        [data-bs-theme="dark"] .table td {
            color: #e2e8f0;
            border-bottom-color: #1f2937;
        }
        [data-bs-theme="dark"] .table-light {
            background-color: #1f2937 !important;
            color: #94a3b8 !important;
        }
        [data-bs-theme="dark"] .table-hover > tbody > tr:hover > * {
            background-color: rgba(255, 255, 255, 0.04) !important;
        }

        /* Form kontrolleri dark mod uyumu */
        [data-bs-theme="dark"] .form-control,
        [data-bs-theme="dark"] .form-select {
            background-color: #1f2937;
            border-color: #374151;
            color: #f9fafb;
        }
        [data-bs-theme="dark"] .form-control:focus,
        [data-bs-theme="dark"] .form-select:focus {
            background-color: #1f2937;
            border-color: #3b82f6;
            color: #f9fafb;
        }
        [data-bs-theme="dark"] .input-group-text {
            background-color: #374151;
            border-color: #4b5563;
            color: #e5e7eb;
        }

        /* Stat / KPI Kartları */
        .kpi-card {
            transition: transform 0.2s ease, box-shadow 0.2s ease;
            border-radius: 16px;
            overflow: hidden;
            border: 1px solid var(--card-border);
            background-color: var(--card-bg) !important;
        }
        .kpi-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.12), 0 8px 10px -6px rgba(0, 0, 0, 0.08);
        }
        .kpi-icon-badge {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.35rem;
        }

        /* Butonlar */
        .btn {
            font-weight: 600;
            border-radius: 10px;
            padding: 0.5rem 1rem;
            transition: all 0.2s ease;
        }
        .btn-primary {
            background-color: #2563eb;
            border-color: #2563eb;
        }
        .btn-primary:hover {
            background-color: #1d4ed8;
            border-color: #1d4ed8;
        }

        /* Footer */
        footer {
            background-color: var(--card-bg) !important;
            border-color: var(--card-border) !important;
        }
    </style>
    @yield('styles')
</head>
<body>

    @auth
    <!-- Üst Menü Navigasyonu -->
    <nav class="navbar navbar-expand-lg navbar-dark navbar-custom py-2 px-3 shadow-sm sticky-top">
        <div class="container-fluid px-3 px-xl-4" style="max-width: 1400px;">
            <a class="navbar-brand text-white d-flex align-items-center gap-2 me-3" href="{{ route('dashboard') }}">
                <span class="d-inline-flex p-2 bg-primary rounded-3 text-white">
                    <i class="bi bi-wallet2 fs-5"></i>
                </span>
                <span>Cari & Stok <span class="badge bg-primary-subtle text-primary border border-primary-subtle fs-6 fw-bold ms-1">PRO</span></span>
            </a>

            <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#mainNavbar">
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse" id="mainNavbar">
                <ul class="navbar-nav me-auto ms-lg-2 gap-1 flex-row flex-wrap flex-lg-nowrap align-items-center">
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}" href="{{ route('dashboard') }}">
                            <i class="bi bi-speedometer2"></i>
                            <span>{{ __('Dashboard') }}</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('contacts.*') ? 'active' : '' }}" href="{{ route('contacts.index') }}">
                            <i class="bi bi-people"></i>
                            <span>{{ __('Contacts') }}</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('products.*') ? 'active' : '' }}" href="{{ route('products.index') }}">
                            <i class="bi bi-box-seam"></i>
                            <span>{{ __('Products & Stock') }}</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('sales.*') ? 'active' : '' }}" href="{{ route('sales.index') }}">
                            <i class="bi bi-receipt"></i>
                            <span>{{ __('Sales Invoices') }}</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('purchases.*') ? 'active' : '' }}" href="{{ route('purchases.index') }}">
                            <i class="bi bi-truck"></i>
                            <span>{{ __('Purchase Invoices') }}</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('cash.*') ? 'active' : '' }}" href="{{ route('cash.index') }}">
                            <i class="bi bi-cash-stack"></i>
                            <span>{{ __('Cash Ledger') }}</span>
                        </a>
                    </li>
                </ul>

                <div class="d-flex align-items-center gap-2 pt-2 pt-lg-0 border-top border-secondary border-opacity-25 border-top-lg-0 flex-nowrap">
                    <!-- Dil Değiştirici Buton (TR / EN) -->
                    @if(app()->getLocale() === 'en')
                        <a href="{{ route('lang.switch', 'tr') }}" class="btn btn-sm btn-outline-secondary text-white d-flex align-items-center gap-1 rounded-pill px-2 py-1 flex-shrink-0" title="Türkçe'ye Geç" style="font-size: 0.78rem; font-weight: 700; border-color: rgba(255,255,255,0.25);">
                            <span>🇹🇷 TR</span>
                        </a>
                    @else
                        <a href="{{ route('lang.switch', 'en') }}" class="btn btn-sm btn-outline-secondary text-white d-flex align-items-center gap-1 rounded-pill px-2 py-1 flex-shrink-0" title="Switch to English" style="font-size: 0.78rem; font-weight: 700; border-color: rgba(255,255,255,0.25);">
                            <span>🇬🇧 EN</span>
                        </a>
                    @endif

                    <!-- Tema Değiştirici Buton (Karanlık / Aydınlık Mod) -->
                    <button type="button" class="btn btn-sm btn-outline-secondary text-white d-flex align-items-center justify-content-center rounded-circle p-0 flex-shrink-0" id="themeToggleBtn" title="Temayı Değiştir" style="width: 32px; height: 32px; border-color: rgba(255,255,255,0.25);">
                        <i class="bi bi-moon-stars-fill fs-6" id="themeIcon"></i>
                    </button>

                    <div class="d-flex align-items-center gap-2 text-white flex-nowrap ms-1">
                        <div class="rounded-circle bg-primary bg-opacity-25 text-primary border border-primary border-opacity-50 d-flex align-items-center justify-content-center flex-shrink-0" style="width: 32px; height: 32px; font-weight: 700; font-size: 0.85rem;">
                            {{ mb_strtoupper(mb_substr(Auth::user()->name, 0, 1, 'UTF-8'), 'UTF-8') }}
                        </div>
                        <span class="small fw-semibold text-nowrap" style="font-size: 0.85rem;">{{ Auth::user()->name }}</span>
                    </div>

                    <form action="{{ route('logout') }}" method="POST" class="d-inline mb-0 ms-1 flex-shrink-0">
                        @csrf
                        <button type="submit" class="btn btn-outline-danger btn-sm d-flex align-items-center gap-1 py-1 px-2 text-nowrap" style="font-size: 0.825rem;" title="Çıkış Yap">
                            <i class="bi bi-box-arrow-right"></i>
                            <span>{{ __('Logout') }}</span>
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </nav>
    @endauth

    <!-- Ana İçerik Konteyneri -->
    <main class="container my-4 flex-grow-1" style="max-width: 1300px;">
        <!-- Bildirim Mesajları (Flash Alerts) -->
        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show border-0 shadow-sm d-flex align-items-center gap-2 rounded-3 mb-4" role="alert">
                <i class="bi bi-check-circle-fill fs-5 text-success"></i>
                <div class="fw-semibold">{{ session('success') }}</div>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        @if(session('error'))
            <div class="alert alert-danger alert-dismissible fade show border-0 shadow-sm d-flex align-items-center gap-2 rounded-3 mb-4" role="alert">
                <i class="bi bi-exclamation-triangle-fill fs-5 text-danger"></i>
                <div class="fw-semibold">{{ session('error') }}</div>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        @yield('content')
    </main>

    <!-- Alt Bilgi (Footer) -->
    <footer class="py-3 text-center text-muted small mt-auto border-top bg-white">
        <div class="container">
            <span>&copy; {{ date('Y') }} <strong>Cari & Stok Takip</strong> — akinsoft altyapısı ile hazırlanmıştır.</span>
        </div>
    </footer>

    <!-- Bootstrap 5 JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

    <!-- Karanlık / Aydınlık Mod Yönetimi (LocalStorage) -->
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const toggleBtn = document.getElementById('themeToggleBtn');
            const themeIcon = document.getElementById('themeIcon');
            const htmlTag = document.documentElement;

            function updateThemeUI(theme) {
                if (!themeIcon || !toggleBtn) return;
                if (theme === 'dark') {
                    themeIcon.className = 'bi bi-sun-fill text-warning fs-6';
                    toggleBtn.setAttribute('title', 'Aydınlık Moda Geç');
                } else {
                    themeIcon.className = 'bi bi-moon-stars-fill text-light fs-6';
                    toggleBtn.setAttribute('title', 'Karanlık Moda Geç');
                }
            }

            // Sayfa yüklendiğinde mevcut aktif temayı ikona yansıt
            const currentTheme = htmlTag.getAttribute('data-bs-theme') || 'light';
            updateThemeUI(currentTheme);

            if (toggleBtn) {
                toggleBtn.addEventListener('click', function () {
                    const activeTheme = htmlTag.getAttribute('data-bs-theme') || 'light';
                    const newTheme = activeTheme === 'dark' ? 'light' : 'dark';

                    htmlTag.setAttribute('data-bs-theme', newTheme);
                    localStorage.setItem('app-theme', newTheme);
                    updateThemeUI(newTheme);
                });
            }
        });
    </script>
    @yield('scripts')
</body>
</html>

