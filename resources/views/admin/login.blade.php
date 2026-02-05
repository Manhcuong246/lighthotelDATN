<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Đăng nhập Admin - Light Hotel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            font-family: system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
        }
        .login-container {
            background: white;
            border-radius: 15px;
            box-shadow: 0 15px 35px rgba(0,0,0,0.1);
            overflow: hidden;
            max-width: 450px;
        }
        .login-header {
            background: linear-gradient(90deg, #4361ee, #3a0ca3);
            color: white;
            padding: 30px;
            text-align: center;
        }
        .login-body {
            padding: 40px;
        }
        .form-control {
            border-radius: 8px;
            padding: 12px 15px;
            border: 2px solid #e9ecef;
        }
        .form-control:focus {
            border-color: #4361ee;
            box-shadow: 0 0 0 0.25rem rgba(67, 97, 238, 0.25);
        }
        .btn-login {
            background: linear-gradient(90deg, #4361ee, #3a0ca3);
            border: none;
            border-radius: 8px;
            padding: 12px;
            font-weight: 600;
        }
        .btn-login:hover {
            background: linear-gradient(90deg, #3a0ca3, #4361ee);
            transform: translateY(-2px);
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="row justify-content-center">
            <div class="login-container">
                <div class="login-header">
                    <h2><i class="bi bi-shield-lock me-2"></i> Quản trị</h2>
                    <p class="mb-0 opacity-75">Đăng nhập dành cho Quản trị viên & Nhân viên</p>
                </div>
                <div class="login-body">
                    @if($errors->any())
                        <div class="alert alert-danger py-2 small">
                            @foreach($errors->all() as $err) {{ $err }} @endforeach
                        </div>
                    @endif
                    <form method="POST" action="{{ route('admin.login.submit') }}">
                        @csrf
                        <div class="mb-3">
                            <label for="email" class="form-label">Email</label>
                            <input type="text" class="form-control" id="email" name="email" value="{{ old('email') }}" placeholder="Nhập email" required>
                        </div>
                        <div class="mb-3">
                            <label for="password" class="form-label">Mật khẩu</label>
                            <input type="password" class="form-control" id="password" name="password" placeholder="Nhập mật khẩu" required>
                        </div>
                        <div class="mb-3 form-check">
                            <input type="checkbox" class="form-check-input" id="remember" name="remember">
                            <label class="form-check-label" for="remember">Ghi nhớ đăng nhập</label>
                        </div>
                        <button type="submit" class="btn btn-primary btn-login w-100">Đăng nhập Admin</button>
                    </form>
                    
                    <div class="text-center mt-3">
                        <a href="{{ route('login') }}" class="text-decoration-none">
                            <i class="bi bi-arrow-left me-1"></i> Quay lại đăng nhập người dùng
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>