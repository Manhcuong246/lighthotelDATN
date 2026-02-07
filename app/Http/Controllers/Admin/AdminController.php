<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AdminController extends Controller
{
    public function __construct()
    {
        $this->middleware('admin')->except(['showLoginForm', 'login']);
    }

    public function dashboard()
    {
        return view('admin.dashboard');
    }
    
    public function showLoginForm()
    {
        return view('admin.login');
    }
    
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);
        
        $remember = $request->filled('remember');

        $user = User::with('roles')->where('email', $request->email)->first();

        if (!$user) {
            return back()->withErrors(['email' => 'Thông tin đăng nhập không chính xác.']);
        }

        $canAccess = $user->roles()->whereIn('name', ['admin', 'staff'])->exists();
        if (!$canAccess) {
            return back()->withErrors(['email' => 'Bạn không có quyền truy cập khu vực quản trị. Chỉ admin và nhân viên mới đăng nhập tại đây.']);
        }

        if (Hash::check($request->password, $user->password)) {
            Auth::login($user, $remember);
            return redirect()->intended(route('admin.dashboard'));
        }

        return back()->withErrors(['email' => 'Thông tin đăng nhập không chính xác.']);
    }
    
    public function logout()
    {
        Auth::logout();
        return redirect()->route('admin.login');
    }
}