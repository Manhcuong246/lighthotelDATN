<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Admin Dashboard - Light Hotel')</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        :root {
            --sidebar-width: 250px;
            --navbar-height: 60px;
            --primary-color: #4361ee;
            --secondary-color: #3f37c9;
            --dark-color: #1d3557;
            --light-color: #f8f9fa;
        }
        
        body {
            background-color: #f0f2f5;
            color: #333;
            font-family: system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
            overflow-x: hidden;
        }
        
        /* Sidebar styles */
        #sidebar {
            min-height: calc(100vh - var(--navbar-height));
            background: linear-gradient(to bottom, var(--dark-color), #14213d);
            color: white;
            width: var(--sidebar-width);
            transition: all 0.3s;
            position: fixed;
            top: var(--navbar-height);
            left: 0;
            z-index: 100;
            box-shadow: 3px 0 10px rgba(0,0,0,0.1);
        }
        
        #sidebar .nav-link {
            color: rgba(255, 255, 255, 0.8);
            padding: 12px 20px;
            margin: 5px 15px;
            border-radius: 8px;
            transition: all 0.3s;
        }
        
        #sidebar .nav-link:hover, 
        #sidebar .nav-link.active {
            background: rgba(255, 255, 255, 0.1);
            color: white;
        }
        
        #sidebar .nav-link i {
            margin-right: 10px;
            width: 20px;
            text-align: center;
        }
        
        #sidebar .sidebar-header {
            padding: 20px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        #content {
            margin-left: var(--sidebar-width);
            margin-top: var(--navbar-height);
            padding: 20px;
            transition: all 0.3s;
            min-height: calc(100vh - var(--navbar-height));
        }
        
        /* Top navbar styles */
        .navbar-admin {
            background: linear-gradient(90deg, var(--dark-color), var(--secondary-color));
            height: var(--navbar-height);
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 105;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
        
        .navbar-brand-admin {
            color: white !important;
            font-weight: 700;
            font-size: 1.5rem;
        }
        
        .navbar-brand-admin i {
            margin-right: 10px;
        }
        
        .user-info {
            color: white !important;
        }
        
        .toggle-btn {
            color: white;
            border: none;
            background: none;
            font-size: 1.2rem;
        }
        
        /* Card styles */
        .card-admin {
            border: none;
            border-radius: 12px;
            box-shadow: 0 6px 15px rgba(0,0,0,0.08);
            margin-bottom: 20px;
            transition: transform 0.3s ease;
        }
        
        .card-admin:hover {
            transform: translateY(-5px);
        }
        
        .card-header-admin {
            background: linear-gradient(90deg, var(--primary-color), var(--secondary-color));
            color: white;
            border-radius: 12px 12px 0 0 !important;
            padding: 15px 20px;
            font-weight: 600;
            border: none;
        }
        
        /* Stats cards */
        .stat-card {
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 6px 15px rgba(0,0,0,0.08);
            transition: all 0.3s ease;
        }
        
        .stat-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.12);
        }
        
        .stat-icon {
            width: 60px;
            height: 60px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.8rem;
        }
        
        .bg-primary-light { background-color: rgba(67, 97, 238, 0.15); }
        .bg-success-light { background-color: rgba(60, 186, 159, 0.15); }
        .bg-warning-light { background-color: rgba(247, 183, 49, 0.15); }
        .bg-danger-light { background-color: rgba(231, 76, 60, 0.15); }
        
        .text-primary-dark { color: #4361ee; }
        .text-success-dark { color: #3da58a; }
        .text-warning-dark { color: #e79427; }
        .text-danger-dark { color: #c0392b; }
        
        /* Responsive adjustments */
        @media (max-width: 768px) {
            #sidebar {
                margin-left: -var(--sidebar-width);
            }
            
            #sidebar.active {
                margin-left: 0;
            }
            
            #content {
                margin-left: 0;
            }
            
            .overlay {
                display: none;
                position: fixed;
                top: 0;
                left: 0;
                right: 0;
                bottom: 0;
                background: rgba(0,0,0,0.5);
                z-index: 99;
            }
            
            .overlay.active {
                display: block;
            }
        }
    </style>
</head>
<body>
    <!-- Top Navigation -->
    <nav class="navbar navbar-expand-lg navbar-admin">
        <div class="container-fluid px-4">
            <button class="toggle-btn me-3" id="sidebarToggle">
                <i class="bi bi-list"></i>
            </button>
            <a class="navbar-brand navbar-brand-admin" href="{{ route('admin.dashboard') }}">
                <i class="bi bi-speedometer2"></i>
                Admin Dashboard
            </a>
            <div class="d-flex align-items-center ms-auto">
                <div class="dropdown">
                    <a class="user-info dropdown-toggle d-flex align-items-center text-decoration-none" href="#" role="button" data-bs-toggle="dropdown">
                        <i class="bi bi-person-circle me-2" style="font-size: 1.5rem;"></i>
                        <span class="d-none d-md-inline">Admin User</span>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li><a class="dropdown-item" href="#"><i class="bi bi-person me-2"></i> Hồ sơ</a></li>
                        <li><a class="dropdown-item" href="#"><i class="bi bi-gear me-2"></i> Cài đặt</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="#" onclick="event.preventDefault(); document.getElementById('logout-form').submit();"><i class="bi bi-box-arrow-right me-2"></i> Đăng xuất</a></li>
                                            <form id="logout-form" action="{{ route('admin.logout') }}" method="POST" class="d-none">
                                                @csrf
                                            </form>
                    </ul>
                </div>
            </div>
        </div>
    </nav>

    <!-- Sidebar -->
    <div class="d-flex">
        <div class="sidebar" id="sidebar">
            <div class="sidebar-header">
                <h4><i class="bi bi-building me-2"></i>Light Hotel</h4>
                <p class="small mb-0 text-muted">Quản trị hệ thống</p>
            </div>
            <ul class="nav flex-column mt-4">
                <li class="nav-item">
                    <a class="nav-link active" href="{{ route('admin.dashboard') }}">
                        <i class="bi bi-speedometer2"></i>
                        Bảng điều khiển
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="{{ route('admin.rooms.index') }}">
                        <i class="bi bi-door-open"></i>
                        Quản lý phòng
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="{{ route('admin.bookings.index') }}">
                        <i class="bi bi-calendar-check"></i>
                        Đặt phòng
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="{{ route('admin.users.index') }}">
                        <i class="bi bi-people"></i>
                        Người dùng
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="{{ route('admin.reviews.index') }}">
                        <i class="bi bi-chat-square-text"></i>
                        Đánh giá
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="{{ route('admin.payments.index') }}">
                        <i class="bi bi-credit-card"></i>
                        Thanh toán
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="{{ route('admin.settings.index') }}">
                        <i class="bi bi-gear"></i>
                        Cài đặt
                    </a>
                </li>
            </ul>
        </div>

        <!-- Main Content -->
        <div id="content">
            @if(session('success'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    {{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            @if($errors->any())
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <ul class="mb-0">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            @yield('content')
        </div>
    </div>

    <!-- Overlay for mobile -->
    <div class="overlay" id="overlay"></div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const sidebar = document.getElementById('sidebar');
            const toggleBtn = document.getElementById('sidebarToggle');
            const overlay = document.getElementById('overlay');
            
            toggleBtn.addEventListener('click', function() {
                sidebar.classList.toggle('active');
                overlay.classList.toggle('active');
            });
            
            overlay.addEventListener('click', function() {
                sidebar.classList.remove('active');
                overlay.classList.remove('active');
            });
        });
    </script>
</body>
</html>