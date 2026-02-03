<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class AdminController extends Controller
{
    public function __construct()
    {
        // Apply admin middleware to all methods in this controller
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
        
        $credentials = $request->only('email', 'password');
        $remember = $request->filled('remember');
        
        // Check if the user exists first and has admin role
        $user = User::with('roles')->where('email', $credentials['email'])->first();
        
        if (!$user) {
            return back()->withErrors(['email' => 'Thông tin đăng nhập không chính xác.']);
        }
        
        // Check if user has admin role before attempting login
        $adminRole = $user->roles()->where('name', 'admin')->first();
        if (!$adminRole) {
            return back()->withErrors(['email' => 'Bạn không có quyền truy cập khu vực quản trị.']);
        }
        
        if (Auth::attempt($credentials, $remember)) {
            $authenticatedUser = Auth::user();
            
            // Double-check admin role after authentication by querying the pivot table
            $hasAdminRole = DB::table('user_roles')
                ->join('roles', 'user_roles.role_id', '=', 'roles.id')
                ->where('user_roles.user_id', $authenticatedUser->id)
                ->where('roles.name', 'admin')
                ->exists();
            
            if ($hasAdminRole) {
                return redirect()->intended(route('admin.dashboard'));
            } else {
                Auth::logout();
                return back()->withErrors(['email' => 'Bạn không có quyền truy cập khu vực quản trị.']);
            }
        }
        
        return back()->withErrors(['email' => 'Thông tin đăng nhập không chính xác.']);
    }
    
    public function logout()
    {
        Auth::logout();
        return redirect()->route('admin.login');
    }
}