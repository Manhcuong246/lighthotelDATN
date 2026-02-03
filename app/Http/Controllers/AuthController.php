<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
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
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);
        
        $credentials = $request->only('email', 'password');
        $remember = $request->filled('remember');
        
        if (Auth::attempt($credentials, $remember)) {
            return redirect()->intended('/');
        }
        
        return back()->withErrors([
            'email' => 'Thông tin đăng nhập không chính xác.',
        ]);
    }
    
    public function register(Request $request)
    {
        $request->validate([
            'full_name' => 'required|string|max:150',
            'email' => 'required|string|email|max:150|unique:users',
            'password' => 'required|string|min:6|confirmed',
        ]);
        
        $user = User::create([
            'full_name' => $request->full_name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'phone' => $request->phone ?? null,
            'status' => 'active',
        ]);
        
        // Assign 'guest' role by default
        $guestRole = \App\Models\Role::where('name', 'guest')->first();
        if ($guestRole) {
            $user->roles()->attach($guestRole->id);
        }
        
        Auth::login($user);
        
        return redirect()->intended('/');
    }
    
    public function logout(Request $request)
    {
        Auth::logout();
        
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        
        return redirect('/');
    }
}


