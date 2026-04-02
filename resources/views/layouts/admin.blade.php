<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <title>@yield('title', 'Admin Dashboard - Light Hotel')</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    @vite(['resources/css/admin.css'])
    @stack('styles')
</head>
<body class="admin-body">
    <nav class="navbar navbar-expand-lg navbar-admin">
        <div class="container-fluid px-3 px-md-4">
            <a class="navbar-brand navbar-brand-admin" href="{{ route('admin.dashboard') }}">
                <i class="bi bi-speedometer2"></i>
                <span class="d-none d-sm-inline">Admin</span>
            </a>
            @if(!request()->routeIs('admin.dashboard'))
                <button
                    id="sidebarToggle"
                    class="toggle-btn me-3 d-lg-none"
                    type="button"
                    aria-label="Mở menu"
                >
                    <i class="bi bi-list"></i>
                </button>
            @endif
            <div class="d-flex align-items-center ms-auto">
                <div class="dropdown">
                    <a class="dropdown-toggle d-flex align-items-center text-decoration-none text-white" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        @if(auth()->user()->avatar_url)
                            <img src="{{ str_starts_with(auth()->user()->avatar_url, 'http') ? auth()->user()->avatar_url : asset('storage/' . auth()->user()->avatar_url) }}" alt="" class="rounded-circle me-2" style="width:36px;height:36px;object-fit:cover;border:2px solid rgba(255,255,255,0.5)">
                        @else
                            <span class="rounded-circle me-2 d-inline-flex align-items-center justify-content-center text-white fw-bold" style="width:36px;height:36px;background:rgba(255,255,255,0.2);border:2px solid rgba(255,255,255,0.5);font-size:0.9rem">{{ strtoupper(mb_substr(auth()->user()->full_name ?? 'U', 0, 1)) }}</span>
                        @endif
                        <span class="d-none d-md-inline small">{{ auth()->user()->isAdmin() ? 'Quản trị viên' : 'Nhân viên' }}</span>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end shadow border-0 mt-2 py-1">
                        <li><a class="dropdown-item py-2" href="{{ route('account.profile') }}"><i class="bi bi-person me-2"></i>Thông tin cá nhân</a></li>
                        <li><a class="dropdown-item py-2" href="{{ route('account.bookings') }}"><i class="bi bi-calendar-check me-2"></i>Lịch đặt phòng</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item py-2 text-danger" href="#" onclick="event.preventDefault(); document.getElementById('logout-form').submit();"><i class="bi bi-box-arrow-right me-2"></i>Đăng xuất</a></li>
                    </ul>
                    <form id="logout-form" action="{{ route('admin.logout') }}" method="POST" class="d-none">@csrf</form>
                </div>
            </div>
        </div>
    </nav>

    <div class="sidebar-overlay" id="sidebarOverlay"></div>

    <aside id="sidebar" aria-label="Menu quản trị">
        <div class="sidebar-header">
            <h4><i class="bi bi-building me-2"></i>Light Hotel</h4>
            <p class="small mb-0">Quản trị hệ thống</p>
        </div>
        <ul class="nav flex-column">
            <li class="nav-item">
                <a class="nav-link {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}" href="{{ route('admin.dashboard') }}">
                    <i class="bi bi-speedometer2"></i>
                    Bảng điều khiển
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link {{ request()->routeIs('admin.roomtypes*') ? 'active' : '' }}" href="{{ route('admin.roomtypes.index') }}">
                    <i class="bi bi-layers"></i>
                    Loại phòng
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link {{ request()->routeIs('admin.rooms*') ? 'active' : '' }}" href="{{ route('admin.rooms.index') }}">
                    <i class="bi bi-door-open"></i>
                    Quản lý phòng
                </a>
            </li>
            @if(auth()->user()->isAdmin())
            <li class="nav-item">
                <a class="nav-link {{ request()->routeIs('admin.bookings*') ? 'active' : '' }}" href="{{ route('admin.bookings.index') }}">
                    <i class="bi bi-calendar-check"></i>
                    Đặt phòng
                </a>
            </li>
            @endif
            @if(auth()->user()->isAdmin())
            <li class="nav-item">
                <a class="nav-link {{ request()->routeIs('admin.users*') ? 'active' : '' }}" href="{{ route('admin.users.index') }}">
                    <i class="bi bi-people"></i>
                    Người dùng
                </a>
            </li>
            @endif
            <li class="nav-item">
                <a class="nav-link {{ request()->routeIs('admin.reviews*') ? 'active' : '' }}" href="{{ route('admin.reviews.index') }}">
                    <i class="bi bi-chat-square-text"></i>
                    Đánh giá
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link {{ request()->routeIs('admin.payments*') ? 'active' : '' }}" href="{{ route('admin.payments.index') }}">
                    <i class="bi bi-credit-card"></i>
                    Thanh toán
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link {{ request()->routeIs('admin.coupons*') ? 'active' : '' }}" href="{{ route('admin.coupons.index') }}">
                    <i class="bi bi-ticket-perforated"></i>
                    Mã giảm giá
                </a>
            </li>
            @if(auth()->user()->isAdmin())
            <li class="nav-item">
                <a class="nav-link {{ request()->routeIs('admin.settings*') ? 'active' : '' }}" href="{{ route('admin.settings.index') }}">
                    <i class="bi bi-gear"></i>
                    Cài đặt
                </a>
            </li>
            @endif
            <li class="nav-item mt-2 pt-2 border-top border-secondary border-opacity-25">
                <a class="nav-link" href="{{ route('home') }}">
                    <i class="bi bi-house-door"></i>
                    Trang chủ
                </a>
            </li>
        </ul>
    </aside>

    <main id="content">
        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Đóng"></button>
            </div>
        @endif
        @if(session('error'))
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                {{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Đóng"></button>
            </div>
        @endif
        @if($errors->any())
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <ul class="mb-0 ps-3">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Đóng"></button>
            </div>
        @endif

        @yield('content')
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        (function() {
            var sidebar = document.getElementById('sidebar');
            var toggle = document.getElementById('sidebarToggle');
            var overlay = document.getElementById('sidebarOverlay');

            function toggleSidebar() {
                sidebar.classList.toggle('show');
                overlay.classList.toggle('show');
                document.body.style.overflow = sidebar.classList.contains('show') ? 'hidden' : '';
            }

            function closeSidebar() {
                sidebar.classList.remove('show');
                overlay.classList.remove('show');
                document.body.style.overflow = '';
            }

            if (toggle) {
                toggle.addEventListener('click', function (e) {
                    e.preventDefault();
                    e.stopPropagation();
                    toggleSidebar();
                });
            }
            if (overlay) overlay.addEventListener('click', closeSidebar);

            window.addEventListener('resize', function() {
                if (window.innerWidth >= 992) closeSidebar();
            });
        })();
    </script>
    @stack('scripts')
</body>
</html>
