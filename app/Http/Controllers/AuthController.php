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

        // DEBUG: Log input data
        Log::info('Register attempt', [
            'email' => $request->email,
            'full_name' => $request->full_name,
            'phone' => $request->phone,
            'has_password' => !empty($request->password),
            'password_confirmation' => $request->password_confirmation
        ]);

        // Tìm user có email này
        $existing = User::whereRaw('LOWER(email) = ?', [$request->email])->first();

        // Kiểm tra email trùng lặp
        $emailRule = Rule::unique('users', 'email');

        // Cho phép email này nếu là tài khoản guest tạm
        if ($existing && $existing->isProvisionalGuestAccount()) {
            $emailRule = Rule::unique('users', 'email')->ignore($existing->id);
            Log::info('Found existing provisional guest', ['user_id' => $existing->id]);
        }

        try {
            $validated = $request->validate([
                'full_name' => 'required|string|max:150',
                'email' => ['required', 'email', 'max:150', $emailRule],
                'password' => 'required|min:6|confirmed',
            ]);
            Log::info('Validation passed', ['validated' => $validated]);
        } catch (\Exception $e) {
            Log::error('Validation failed', ['errors' => $e->getMessage()]);
            throw $e;
        }

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

        // Tạo user mới
        try {
            Log::info('Creating new user', [
                'email' => $request->email,
                'full_name' => $request->full_name,
                'phone' => $request->phone
            ]);
            
            $user = User::create([
                'full_name' => $request->full_name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'phone' => $request->phone,
                'status' => 'active',
            ]);
            
            Log::info('User created successfully', ['user_id' => $user->id, 'email' => $user->email]);

            $customerRole = \App\Models\Role::where('name', 'customer')->first();

            if ($customerRole) {
                $user->roles()->attach($customerRole->id);
                Log::info('Customer role attached to user', ['user_id' => $user->id]);
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