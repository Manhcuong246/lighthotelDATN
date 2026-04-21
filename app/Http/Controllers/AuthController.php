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
        $request->merge([
            'email' => Str::lower(trim((string) $request->email))
        ]);

        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        $user = User::whereRaw('LOWER(email) = ?', [$request->email])->first();

        // Nếu là tài khoản guest tạm
        if ($user && $user->isProvisionalGuestAccount()) {
            return back()->withErrors([
                'email' => 'Tài khoản chưa kích hoạt, vui lòng đăng ký lại để đặt mật khẩu.',
            ]);
        }

        // Nếu user tồn tại và đúng password
        if ($user && Hash::check($request->password, $user->password)) {

            Auth::login($user, $request->filled('remember'));

            // 🔴 ADMIN LOGIN
            if ($user->isAdmin()) {
                return redirect('/admin/dashboard');
            }

            // 🔵 STAFF LOGIN
            if ($user->isStaff()) {
                return redirect('/staff/dashboard');
            }

            // 🟢 CUSTOMER LOGIN
            return redirect('/');
        }

        return back()->withErrors([
            'email' => 'Email hoặc mật khẩu không chính xác.',
        ]);
    }

    public function register(Request $request)
    {
        $request->merge([
            'email' => Str::lower(trim((string) $request->email))
        ]);

        $existing = User::whereRaw('LOWER(email) = ?', [$request->email])->first();

        $emailRule = Rule::unique('users', 'email');

        if ($existing && $existing->isProvisionalGuestAccount()) {
            $emailRule = Rule::unique('users', 'email')->ignore($existing->id);
        }

        $request->validate([
            'full_name' => 'required|string|max:150',
            'email' => ['required', 'email', 'max:150', $emailRule],
            'password' => 'required|min:6|confirmed',
        ]);

        // Nếu là guest tạm → nâng cấp account
        if ($existing && $existing->isProvisionalGuestAccount()) {

            $existing->update([
                'full_name' => $request->full_name,
                'password' => Hash::make($request->password),
                'phone' => $request->phone ?? $existing->phone,
                'status' => 'active',
            ]);

            $guestRole = \App\Models\Role::where('name', 'guest')->first();

            if ($guestRole && !$existing->hasRole('guest')) {
                $existing->roles()->attach($guestRole->id);
            }

            Auth::login($existing);

            return redirect('/')->with('success', 'Kích hoạt tài khoản thành công!');
        }

        // Tạo user mới
        $user = User::create([
            'full_name' => $request->full_name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'phone' => $request->phone,
            'status' => 'active',
        ]);

        $guestRole = \App\Models\Role::where('name', 'guest')->first();

        if ($guestRole) {
            $user->roles()->attach($guestRole->id);
        }

        Auth::login($user);

        return redirect('/')->with('success', 'Đăng ký thành công!');
    }

    public function logout(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }
}