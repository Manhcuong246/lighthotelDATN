<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class AdminOnlyMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        if (!Auth::check()) {
            abort(403, 'Vui lòng đăng nhập để tiếp tục.');
        }

        $user = Auth::user();

        // Cho phép cả admin và staff truy cập
        if (!$user->isAdmin() && !$user->isStaff()) {
            abort(403, 'Chỉ quản trị viên và nhân viên mới được thực hiện thao tác này.');
        }

        return $next($request);
    }
}
