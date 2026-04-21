<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Admin Dashboard - Light Hotel')</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        @include('partials.scrollbar-theme')
        :root {
            --sidebar-width: 260px;
            --sidebar-collapsed: 72px;
            --navbar-height: 56px;
            --primary-color: #4361ee;
            --secondary-color: #3f37c9;
            --dark-color: #1d3557;
            --light-color: #f8f9fa;
        }
        .skeleton {
    height: 20px;
    background: #eee;
    margin-bottom: 10px;
    animation: pulse 1.5s infinite;
}

        * { box-sizing: border-box; }
        .cursor-help { cursor: help; }
        html { scroll-behavior: smooth; }
        body {
            background-color: #f0f2f5;
            color: #333;
            font-family: system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
            overflow-x: hidden;
            padding-top: var(--navbar-height);
        }

        /* Navbar */
        .navbar-admin {
            background: linear-gradient(90deg, var(--dark-color), var(--secondary-color));
            height: var(--navbar-height);
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 1030;
            box-shadow: 0 2px 12px rgba(0,0,0,0.15);
        }
        .navbar-brand-admin { color: white !important; font-weight: 700; font-size: 1.15rem; }
        .navbar-brand-admin i { margin-right: 8px; }
        .toggle-btn {
            color: white;
            border: none;
            background: rgba(255,255,255,0.15);
            width: 40px;
            height: 40px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.25rem;
            transition: background 0.2s;
            cursor: pointer;
            position: relative;
            z-index: 1100;
        }
        .toggle-btn:hover { background: rgba(255,255,255,0.25); color: white; }

        /* Sidebar */
        #sidebar {
            position: fixed;
            top: var(--navbar-height);
            left: 0;
            width: var(--sidebar-width);
            height: calc(100vh - var(--navbar-height));
            background: linear-gradient(180deg, var(--dark-color), #14213d);
            color: white;
            z-index: 1025;
            transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1), width 0.3s ease;
            overflow-y: auto;
            overflow-x: hidden;
            -webkit-overflow-scrolling: touch;
        }
        #sidebar .sidebar-header {
            padding: 1rem 1.25rem;
            border-bottom: 1px solid rgba(255,255,255,0.1);
            flex-shrink: 0;
        }
        #sidebar .sidebar-header h4 { font-size: 1.1rem; margin: 0; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        #sidebar .sidebar-header p { font-size: 0.75rem; margin: 0.25rem 0 0; opacity: 0.7; }
        #sidebar .nav-link {
            color: rgba(255,255,255,0.85);
            padding: 0.75rem 1.25rem;
            margin: 0.125rem 0.75rem;
            border-radius: 10px;
            transition: all 0.2s;
            display: flex;
            align-items: center;
            white-space: nowrap;
        }
        #sidebar .nav-link i {
            width: 24px;
            flex-shrink: 0;
            font-size: 1.1rem;
            margin-right: 0.75rem;
        }
        #sidebar .nav-link:hover, #sidebar .nav-link.active {
            background: rgba(255,255,255,0.12);
            color: white;
        }
        #sidebar .nav-item { list-style: none; }
        #sidebar .nav { padding: 0.75rem 0; }

        /* Overlay */
        .sidebar-overlay {
            display: none;
            position: fixed;
            inset: 0;
            top: var(--navbar-height);
            background: rgba(0,0,0,0.5);
            z-index: 1020;
            opacity: 0;
            transition: opacity 0.3s;
        }
        .sidebar-overlay.show {
            display: block;
            opacity: 1;
        }

        /* Main content */
        #content {
            margin-left: var(--sidebar-width);
            padding: 1rem;
            min-height: calc(100vh - var(--navbar-height));
            transition: margin-left 0.3s ease;
        }

        /* Cards */
        .card-admin {
            border: none;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.06);
            margin-bottom: 1.25rem;
        }
        .card-header-admin {
            background: linear-gradient(90deg, var(--primary-color), var(--secondary-color));
            color: white;
            border-radius: 12px 12px 0 0;
            padding: 0.875rem 1.25rem;
            font-weight: 600;
            font-size: 0.95rem;
        }
        .stat-card {
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 12px rgba(0,0,0,0.06);
            transition: transform 0.2s, box-shadow 0.2s;
        }
        .stat-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(0,0,0,0.1);
        }
        .stat-icon {
            width: 48px;
            height: 48px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.4rem;
        }
        .bg-primary-light { background: rgba(67, 97, 238, 0.12); }
        .bg-success-light { background: rgba(60, 186, 159, 0.12); }
        .bg-warning-light { background: rgba(247, 183, 49, 0.12); }
        .bg-info-light { background: rgba(23, 162, 184, 0.12); }
        .bg-danger-light { background: rgba(231, 76, 60, 0.12); }
        .text-primary-dark { color: #4361ee; }
        .text-success-dark { color: #3da58a; }
        .text-warning-dark { color: #e79427; }
        .text-info-dark { color: #17a2b8; }
        .text-danger-dark { color: #c0392b; }

        /* Responsive tables */
        .table-responsive { -webkit-overflow-scrolling: touch; }
        .table th, .table td { vertical-align: middle; }

        /* Page headers */
        .page-header {
            display: flex;
            flex-wrap: wrap;
            gap: 0.75rem;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.25rem;
        }
        .page-header h1 { font-size: clamp(1.25rem, 3vw, 1.5rem); margin: 0; }
        .page-header .btn-group { flex-wrap: wrap; }

        /* Mobile: sidebar off-canvas */
        @media (max-width: 991.98px) {
            #sidebar {
                transform: translateX(-100%);
                box-shadow: 4px 0 20px rgba(0,0,0,0.2);
            }
            #sidebar.show {
                transform: translateX(0);
            }
            #content { margin-left: 0; padding: 0.75rem; }
            body { padding-top: var(--navbar-height); overflow-x: auto; }
        }

        /* Tablet */
        @media (min-width: 992px) and (max-width: 1199.98px) {
            :root { --sidebar-width: 220px; }
        }

        /* Safe area for notched devices */
        @@supports (padding: max(0px)) {
            body { padding-top: max(var(--navbar-height), env(safe-area-inset-top)); }
            #sidebar { padding-bottom: env(safe-area-inset-bottom); }
        }
        /* Pagination spacing - avoid crowded look */
        .pagination.gap-2 .page-item .page-link { margin-left: 0; border-radius: 0.375rem; }

        /* Unified icon-first buttons (admin lists & toolbars); use title="" for accessibility */
        .btn-admin-icon {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 2.25rem;
            height: 2.25rem;
            padding: 0;
            line-height: 1;
            border-radius: 0.5rem;
        }
        .btn-admin-icon i { font-size: 1.05rem; pointer-events: none; }
        .btn.btn-sm.btn-admin-icon {
            width: 2rem;
            height: 2rem;
        }
        .btn.btn-sm.btn-admin-icon i { font-size: 0.95rem; }
        .admin-action-row {
            display: inline-flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 0.35rem;
        }
        .btn-admin-icon.dropdown-toggle::after {
            display: none;
        }
        /* Full-width icon buttons (forms) */
        .btn-admin-icon.w-100 {
            width: 100% !important;
            max-width: none;
        }
    </style>
    @stack('styles')
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-admin">
        <div class="container-fluid px-3 px-md-4">
            <a class="navbar-brand navbar-brand-admin" 
   href="{{ auth()->user()->isAdmin() 
        ? route('admin.dashboard') 
        : route('staff.dashboard') }}">
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
                    <a class="dropdown-toggle d-flex align-items-center text-decoration-none text-white" href="#" role="button" data-bs-toggle="dropdown">
                        @php
                            $adminAvatarInitial = strtoupper(mb_substr(auth()->user()->full_name ?? 'U', 0, 1));
                            $adminAvatarSrc = null;
                            if (auth()->user()->avatar_url) {
                                $adminAvatarSrc = str_starts_with(auth()->user()->avatar_url, 'http')
                                    ? auth()->user()->avatar_url
                                    : '/storage/' . auth()->user()->avatar_url . '?v=' . config('room_images.cache_version', '1');
                            }
                        @endphp
                        @if($adminAvatarSrc)
                            {{-- Không dùng d-inline-flex trên span ẩn: utility !important sẽ thắng display:none và hiện 2 avatar --}}
                            <img src="{{ $adminAvatarSrc }}" alt="" class="rounded-circle me-2" style="width:36px;height:36px;object-fit:cover;border:2px solid rgba(255,255,255,0.5)"
                                 onerror="var s=this.nextElementSibling; this.remove(); if(s){ s.style.display='inline-flex'; }">
                            <span class="rounded-circle me-2 align-items-center justify-content-center text-white fw-bold" style="display:none;width:36px;height:36px;background:rgba(255,255,255,0.2);border:2px solid rgba(255,255,255,0.5);font-size:0.9rem">{{ $adminAvatarInitial }}</span>
                        @else
                            <span class="rounded-circle me-2 d-inline-flex align-items-center justify-content-center text-white fw-bold" style="width:36px;height:36px;background:rgba(255,255,255,0.2);border:2px solid rgba(255,255,255,0.5);font-size:0.9rem">{{ $adminAvatarInitial }}</span>
                        @endif
                        <span class="d-none d-md-inline small">{{ auth()->user()->isAdmin() ? 'Admin' : 'Nhân viên' }}</span>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end shadow border-0 mt-2 py-1">
                        <li><a class="dropdown-item py-2" href="{{ route('account.profile') }}"><i class="bi bi-person me-2"></i>Hồ sơ</a></li>
                        <li><a class="dropdown-item py-2" href="{{ route('account.bookings') }}"><i class="bi bi-calendar-check me-2"></i>Đơn của tôi</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item py-2 text-danger" href="#" onclick="event.preventDefault(); document.getElementById('logout-form').submit();"><i class="bi bi-box-arrow-right me-2"></i>Đăng xuất</a></li>
                    </ul>
                    <form id="logout-form" action="{{ route('admin.logout') }}" method="POST" class="d-none">@csrf</form>
                </div>
            </div>
        </div>
    </nav>

    <div class="sidebar-overlay" id="sidebarOverlay"></div>

    <aside id="sidebar">
        <div class="sidebar-header">
            <h4><i class="bi bi-building me-2"></i>Light Hotel</h4>
            <p class="small mb-0 opacity-75">Quản trị</p>
        </div>
        <ul class="nav flex-column">
            <li class="nav-item">
    <a class="nav-link 
        {{ (auth()->user()->isAdmin() && request()->routeIs('admin.dashboard')) 
        || (!auth()->user()->isAdmin() && request()->routeIs('staff.dashboard')) 
        ? 'active' : '' }}"
        
        href="{{ auth()->user()->isAdmin() 
            ? route('admin.dashboard') 
            : route('staff.dashboard') }}">
            
        <i class="bi bi-speedometer2"></i>
        Tổng quan
    </a>
</li>
@if(!auth()->user()->isAdmin())
<li class="nav-item">

    <a class="nav-link 
        {{ request()->routeIs('staff.activity_logs.*') ? 'active' : '' }}"

        href="{{ route('staff.activity_logs.index') }}">

        <i class="bi bi-clock-history"></i>
        Nhật ký hoạt động

    </a>

</li>
@endif
@if(!auth()->user()->isAdmin())
<li class="nav-item">
    <a class="nav-link 
        {{ request()->routeIs('staff.damage-reports.*') ? 'active' : '' }}"
        href="{{ route('staff.damage-reports.index') }}">
        
        <i class="bi bi-exclamation-triangle"></i>
        Báo cáo hư hỏng

    </a>
</li>
@endif
            @if(auth()->user()->isAdmin())
<li class="nav-item">
    <a class="nav-link {{ request()->routeIs('admin.roomtypes.*') ? 'active' : '' }}" 
       href="{{ route('admin.roomtypes.index') }}">
        <i class="bi bi-layers"></i>
        Loại phòng
    </a>
</li>
@endif
            <li class="nav-item">
                <a class="nav-link {{ request()->routeIs('admin.site-contents.*') ? 'active' : '' }}" href="{{ route('admin.site-contents.index') }}">
                    <i class="bi bi-file-earmark-text"></i>
                    Nội dung website
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link {{ request()->routeIs('admin.services.*') ? 'active' : '' }}"
                   href="{{ route('admin.services.index') }}">
                    <i class="bi bi-cone-striped"></i>
                    Dịch vụ
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link {{ request()->routeIs('admin.rooms*') ? 'active' : '' }}" href="{{ route('admin.rooms.index') }}">
                    <i class="bi bi-door-open"></i>
                    Phòng
                </a>
            </li>
            @if(auth()->user()->isAdmin())
            <li class="nav-item">
                <a class="nav-link {{ request()->routeIs('admin.bookings.index') || request()->routeIs('admin.payments.show') || request()->routeIs('admin.payments.edit') ? 'active' : '' }}" href="{{ route('admin.bookings.index') }}">
                    <i class="bi bi-calendar-check"></i>
                    Đặt phòng
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link {{ request()->routeIs('admin.refunds*') ? 'active' : '' }}" href="{{ route('admin.refunds.index') }}">
                    <i class="bi bi-wallet2"></i>
                    Hoàn tiền
                </a>
            </li>
            @endif
            @if(!auth()->user()->isAdmin())
            <li class="nav-item">
                <a class="nav-link {{ request()->routeIs('admin.bookings.index') || request()->routeIs('admin.payments.show') || request()->routeIs('admin.payments.edit') ? 'active' : '' }}" href="{{ route('admin.bookings.index') }}">
                    <i class="bi bi-credit-card"></i>
                    Đặt phòng
                </a>
            </li>
            @endif
            @if(auth()->user()->isAdmin())
            <li class="nav-item">
                <a class="nav-link {{ request()->routeIs('admin.users*') ? 'active' : '' }}" href="{{ route('admin.users.index') }}">
                    <i class="bi bi-people"></i>
                    Tài khoản
                </a>
            </li>
            @endif
            <li class="nav-item">
                <a class="nav-link {{ request()->routeIs('admin.reviews*') ? 'active' : '' }}" href="{{ route('admin.reviews.index') }}">
                    <i class="bi bi-chat-square-text"></i>
                    Đánh giá
                </a>
            </li>
           @if(auth()->user()->isAdmin())
<li class="nav-item">
    <a class="nav-link {{ request()->routeIs('admin.coupons*') ? 'active' : '' }}" 
       href="{{ route('admin.coupons.index') }}">
        <i class="bi bi-ticket-perforated"></i>
        Mã giảm
    </a>
</li>
@endif
            @if(auth()->user()->isAdmin())
            <li class="nav-item">
                <a class="nav-link {{ request()->routeIs('admin.settings*') ? 'active' : '' }}" href="{{ route('admin.settings.index') }}">
                    <i class="bi bi-gear"></i>
                    Cài đặt
                </a>
            </li>
            @endif
            <li class="nav-item mt-2 pt-2 border-top border-secondary">
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
    <script src="{{ asset('js/guest-edit.js') }}"></script>
    <script>
        document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(function (el) {
            new bootstrap.Tooltip(el);
        });
    </script>
</body>
</html>
