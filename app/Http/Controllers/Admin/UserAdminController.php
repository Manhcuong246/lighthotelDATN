<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class UserAdminController extends Controller
{
    public function __construct()
    {
        $this->middleware('admin');
        $this->middleware('admin.only');
    }

    public function index(Request $request)
    {
        $query = User::with('roles')->latest();

        if ($request->filled('q')) {
            $q = $request->q;
            $query->where(function ($qry) use ($q) {
                $qry->where('full_name', 'like', "%{$q}%")
                    ->orWhere('email', 'like', "%{$q}%")
                    ->orWhere('phone', 'like', "%{$q}%");
            });
        }

        $users = $query->paginate(15)->withQueryString();
        return view('admin.users.index', compact('users'));
    }

    public function show(User $user)
    {
        return view('admin.users.show', compact('user'));
    }

    public function edit(User $user)
    {
        $roles = Role::all();
        return view('admin.users.edit', compact('user', 'roles'));
    }

    public function update(Request $request, User $user)
    {
        $validated = $request->validate([
            'full_name' => 'required|string|max:150',
            'email' => 'required|string|email|max:150|unique:users,email,' . $user->id,
            'phone' => 'nullable|string|max:20',
            'status' => 'required|in:active,inactive,banned',
        ]);

        $user->update($validated);

        if ($request->filled('password')) {
            $user->update(['password' => $request->password]);
        }

        if ($request->filled('role_ids') && is_array($request->role_ids)) {
            $user->roles()->sync($request->role_ids);
        }

        return redirect()->route('admin.users.index')->with('success', 'Cập nhật người dùng thành công.');
    }

    public function destroy(User $user)
    {
        $currentUser = Auth::user();
        if ($currentUser && $user->id == $currentUser->id) {
            return redirect()->route('admin.users.index')->with('error', 'Bạn không thể xóa tài khoản của chính mình.');
        }

        $user->delete();

        return redirect()->route('admin.users.index')->with('success', 'Xóa người dùng thành công.');
    }
}