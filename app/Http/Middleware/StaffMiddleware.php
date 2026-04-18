<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class StaffMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        // chưa login
        if (!auth()->check()) {
            return redirect()->route('login');
        }

        // không phải staff
        if (!auth()->user()->isStaff()) {
            abort(403, 'Bạn không có quyền truy cập Staff');
        }

        return $next($request);
    }
}