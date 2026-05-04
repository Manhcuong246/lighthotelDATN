<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
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

        if (! $user || ! Hash::check($request->password, $user->password)) {
            return back()->withErrors([
                'email' => 'Email hoặc mật khẩu không chính xác.',
            ]);
        }

        if ($user->status === 'banned') {
            return back()->withErrors([
                'email' => 'Tài khoản của bạn đã bị cấm. Vui lòng liên hệ khách sạn nếu cần hỗ trợ.',
            ]);
        }

        if (($user->status ?? 'active') !== 'active') {
            return back()->withErrors([
                'email' => 'Tài khoản không thể đăng nhập.',
            ]);
        }

        if ($user->isProvisionalGuestAccount()) {
            return back()->withErrors([
                'email' => 'Tài khoản chưa kích hoạt, vui lòng đăng ký lại để đặt mật khẩu.',
            ]);
        }

        Auth::login($user, $request->filled('remember'));

        if ($user->isAdmin()) {
            return redirect('/admin/dashboard');
        }

        if ($user->isStaff()) {
            return redirect('/staff/dashboard');
        }

        return redirect('/');
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

        // Nếu là guest tạm -> nâng cấp account
        if ($existing && $existing->isProvisionalGuestAccount()) {

            $existing->update([
                'full_name' => $request->full_name,
                'password' => Hash::make($request->password),
                'phone' => $request->phone ?? $existing->phone,
                'status' => 'active',
            ]);

            $customerRole = \App\Models\Role::where('name', 'customer')->first();

            if ($customerRole && !$existing->hasRole('customer')) {
                $existing->roles()->attach($customerRole->id);
            }

            return redirect('/login')->with('success', 'Kích hoạt tài khoản thành công! Vui lòng đăng nhập.');
        }

        try {
            $user = User::create([
                'full_name' => $request->full_name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'phone' => $request->phone,
                'status' => 'active',
            ]);

            $customerRole = \App\Models\Role::where('name', 'customer')->first();

            if ($customerRole) {
                $user->roles()->attach($customerRole->id);
            } else {
                Log::error('Customer role not found in database');
            }

            return redirect('/login')->with('success', 'Đăng ký thành công! Vui lòng đăng nhập.');
        } catch (\Exception $e) {
            Log::error('Failed to create user', [
                'error' => $e->getMessage(),
                'email' => $request->email,
                'trace' => $e->getTraceAsString()
            ]);
            return back()->withErrors(['email' => 'Đăng ký thất bại: ' . $e->getMessage()])->withInput();
        }
    }

    public function logout(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }
}