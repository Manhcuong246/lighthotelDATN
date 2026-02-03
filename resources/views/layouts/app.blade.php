<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Light Hotel')</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        html, body {
            height: 100%;
        }
        body {
            background: #f5f7fb;
            font-family: system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
            display: flex;
            flex-direction: column;
        }
        .navbar-brand span {
            font-weight: 700;
            letter-spacing: 1px;
        }
        .navbar {
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
            background: linear-gradient(90deg, #111827, #1f2937);
        }
        .hero-section {
            position: relative;
            overflow: hidden;
            border-radius: 24px;
            padding: 60px 40px;
            background: linear-gradient(135deg, #0f172a, #1d4ed8);
            color: #fff;
            box-shadow: 0 24px 60px rgba(15,23,42,0.55);
        }
        .hero-bg {
            position: absolute;
            inset: 0;
            background-image: url('https://images.pexels.com/photos/258154/pexels-photo-258154.jpeg?auto=compress&cs=tinysrgb&w=1600');
            background-size: cover;
            background-position: center;
            opacity: 0.20;
        }
        .hero-overlay {
            position: absolute;
            inset: 0;
            background: radial-gradient(circle at top left, rgba(56,189,248,0.6), transparent 55%),
                        radial-gradient(circle at bottom right, rgba(248,250,252,0.2), transparent 55%);
            mix-blend-mode: screen;
        }
        .hero-content {
            position: relative;
            z-index: 2;
        }
        .hero-badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 4px 12px;
            border-radius: 999px;
            background: rgba(15,23,42,0.6);
            border: 1px solid rgba(148,163,184,0.5);
            backdrop-filter: blur(12px);
            font-size: 0.8rem;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            color: #e5e7eb;
        }
        .hero-title {
            font-size: clamp(2.4rem, 3vw + 1.5rem, 3.4rem);
            font-weight: 800;
            line-height: 1.1;
            margin-bottom: 0.75rem;
        }
        .hero-subtitle {
            font-size: 1.05rem;
            max-width: 520px;
            color: #e5e7eb;
        }
        .hero-tags span {
            background: rgba(15,23,42,0.6);
            border-radius: 999px;
            padding: 4px 10px;
            font-size: 0.8rem;
            color: #e5e7eb;
        }
        .card-room {
            border: none;
            border-radius: 18px;
            overflow: hidden;
            box-shadow: 0 18px 40px rgba(15,23,42,0.12);
            transition: transform .2s ease, box-shadow .2s ease, translate .2s ease;
            background: #ffffff;
        }
        .card-room:hover {
            transform: translateY(-6px);
            box-shadow: 0 32px 60px rgba(15,23,42,0.25);
        }
        .card-room-img {
            height: 220px;
            object-fit: cover;
        }
        .badge-soft {
            background: rgba(15,23,42,0.06);
            color: #4b5563;
        }
        .section-title {
            font-weight: 700;
            letter-spacing: .04em;
            text-transform: uppercase;
            color: #6b7280;
            font-size: .78rem;
        }
        main {
            flex: 1 0 auto;
        }
        footer {
            flex-shrink: 0;
        }
    </style>
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-dark mb-4">
    <div class="container">
        <a class="navbar-brand d-flex align-items-center gap-2" href="{{ route('home') }}">
            <span class="rounded-circle bg-light d-inline-flex align-items-center justify-content-center" style="width:32px;height:32px;">
                <span class="text-dark fw-bold">L</span>
            </span>
            <span>Light Hotel</span>
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto align-items-lg-center gap-lg-2">
                <li class="nav-item">
                    <a class="nav-link fw-medium" href="{{ route('home') }}">Trang chủ</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link fw-medium" href="#rooms-section">Phòng & giá</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link fw-medium" href="{{ route('admin.dashboard') }}">Quản trị</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link fw-medium" href="{{ route('admin.rooms.index') }}">Quản lý phòng</a>
                </li>
            </ul>
            <div class="d-flex ms-lg-4 mt-3 mt-lg-0 gap-2">
                @auth
                <a href="#" class="btn btn-outline-light btn-sm px-3" onclick="event.preventDefault(); document.getElementById('main-logout-form').submit();">
                    Đăng xuất
                </a>
                <form id="main-logout-form" action="{{ route('admin.logout') }}" method="POST" class="d-none">
                    @csrf
                </form>
                @else
                <a href="{{ route('login') }}" class="btn btn-outline-light btn-sm px-3">
                    Đăng nhập
                </a>
                <a href="{{ route('register') }}" class="btn btn-light btn-sm px-3 text-dark fw-semibold">
                    Đăng ký
                </a>
                @endauth
            </div>
        </div>
    </div>
</nav>

<main class="container mb-5">
    @if(session('success'))
        <div class="alert alert-success">
            {{ session('success') }}
        </div>
    @endif

    @if($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @yield('content')
</main>

<footer class="bg-white py-4 border-top mt-auto">
    <div class="container d-flex flex-column flex-md-row justify-content-between align-items-center gap-2 text-muted small">
        <div>
            &copy; {{ date('Y') }} Light Hotel. All rights reserved.
        </div>
        <div class="d-flex gap-3">
            <span>Chính sách bảo mật</span>
            <span>Điều khoản sử dụng</span>
        </div>
    </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>


