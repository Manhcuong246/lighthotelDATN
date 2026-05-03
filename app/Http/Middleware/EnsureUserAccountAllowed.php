<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserAccountAllowed
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! Auth::check()) {
            return $next($request);
        }

        /** @var \App\Models\User|null $user */
        $user = Auth::user()->fresh();

        if (! $user) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()
                ->route('login')
                ->withErrors([
                    'email' => 'Phiên đăng nhập không còn hợp lệ.',
                ]);
        }

        if ($user->status === 'banned') {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()
                ->route('login')
                ->withErrors([
                    'email' => 'Tài khoản của bạn đã bị cấm. Vui lòng liên hệ khách sạn nếu cần hỗ trợ.',
                ]);
        }

        if (($user->status ?? 'active') !== 'active') {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()
                ->route('login')
                ->withErrors([
                    'email' => 'Tài khoản không thể đăng nhập.',
                ]);
        }

        return $next($request);
    }
}
