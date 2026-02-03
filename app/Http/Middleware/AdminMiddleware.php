<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class AdminMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Temporarily bypass authentication for development
        // In production, uncomment the code below for security
        /*
        if (!Auth::check()) {
            return redirect()->route('admin.login');
        }
        
        $user = Auth::user();
        
        // Check if user has admin role
        if (!$user->roles()->where('name', 'admin')->exists()) {
            abort(403, 'Bạn không có quyền truy cập khu vực quản trị.');
        }
        */
        
        return $next($request);
    }
}
