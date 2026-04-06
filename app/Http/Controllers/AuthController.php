<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use App\Models\User;

class AuthController extends Controller
{
    public function showLoginForm()
    {
        return view('auth.login');
    }

    public function showRegisterForm()
    {
        return view('auth.register');
    }

    public function login(Request $request)
    {
        $request->merge(['email' => Str::lower(trim((string) $request->email))]);

        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        $user = User::query()
            ->whereRaw('LOWER(email) = ?', [$request->email])
            ->first();

        if ($user && $user->isProvisionalGuestAccount()) {
            return back()->withErrors([
                'email' => 'Email này đã dùng khi đặt phòng nhưng tài khoản chưa đặt mật khẩu. Vui lòng dùng trang Đăng ký với cùng email để tạo mật khẩu — các đơn đặt phòng trước đó sẽ tự gắn với tài khoản.',
            ]);
        }

        if ($user && Hash::check($request->password, $user->password)) {
            // Ngăn admin/staff đăng nhập qua trang user
            if ($user->isAdmin() || $user->isStaff()) {
                return back()->withErrors([
                    'email' => 'Tài khoản quản trị vui lòng đăng nhập tại trang Admin.',
                ]);
            }
            
            Auth::login($user, $request->filled('remember'));
            return redirect()->intended('/');
        }

        return back()->withErrors([
            'email' => 'Thông tin đăng nhập không chính xác.',
        ]);
    }

    public function register(Request $request)
    {
        $request->merge(['email' => Str::lower(trim((string) $request->email))]);

        $existing = User::query()
            ->whereRaw('LOWER(email) = ?', [$request->email])
            ->first();

        $emailUnique = Rule::unique('users', 'email');
        if ($existing && $existing->isProvisionalGuestAccount()) {
            $emailUnique = Rule::unique('users', 'email')->ignore($existing->id);
        }

        $request->validate([
            'full_name' => 'required|string|max:150',
            'email' => ['required', 'string', 'email', 'max:150', $emailUnique],
            'password' => 'required|string|min:6|confirmed',
        ]);

        if ($existing && $existing->isProvisionalGuestAccount()) {
            $existing->forceFill([
                'full_name' => $request->full_name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'phone' => $request->phone ?? $existing->phone,
                'status' => 'active',
            ])->save();

            $guestRole = \App\Models\Role::where('name', 'guest')->first();
            if ($guestRole && ! $existing->hasRole('guest')) {
                $existing->roles()->attach($guestRole->id);
            }

            Auth::login($existing);

            return redirect()->intended('/')->with(
                'success',
                'Tài khoản đã được kích hoạt. Các đơn đặt phòng trước đó (nếu có) nằm trong mục Đặt phòng của tôi.'
            );
        }

        $user = User::create([
            'full_name' => $request->full_name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'phone' => $request->phone ?? null,
            'status' => 'active',
        ]);

        $guestRole = \App\Models\Role::where('name', 'guest')->first();
        if ($guestRole) {
            $user->roles()->attach($guestRole->id);
        }

        Auth::login($user);

        return redirect()->intended('/')->with('success', 'Đăng ký thành công! Chào mừng bạn đến với Light Hotel.');
    }

    public function logout(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }
}


