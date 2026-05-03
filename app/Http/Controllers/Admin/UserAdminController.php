<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Mail\AccountBannedMail;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;


class UserAdminController extends Controller
{
    public function __construct()
    {
        $this->middleware('admin');
        $this->middleware('only_admin');
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
        $user->load('roles');
        $roles = Role::orderBy('name')->get();

        return view('admin.users.show', compact('user', 'roles'));
    }

    public function edit(User $user)
    {
        return redirect()->route('admin.users.show', $user);
    }

    public function update(Request $request, User $user)
    {
        $validated = $request->validate([
            'full_name' => 'required|string|max:150',
            'email' => 'required|string|email|max:150|unique:users,email,' . $user->id,
            'phone' => 'nullable|string|max:20',
            'status' => 'required|in:active,banned',
            'avatar' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'role_ids' => 'nullable|array',
            'role_ids.*' => 'integer|exists:roles,id',
        ]);

        if ($request->hasFile('avatar')) {
            if ($user->avatar_url && ! str_starts_with($user->avatar_url, 'http')) {
                Storage::disk('public')->delete($user->avatar_url);
            }
            $validated['avatar_url'] = $request->file('avatar')->store('avatars', 'public');
        }

        unset($validated['avatar']);
        $roleIds = $validated['role_ids'] ?? [];
        unset($validated['role_ids']);

        $previousStatus = $user->status;

        $currentUser = Auth::user();
        if (
            $currentUser
            && (int) $user->id === (int) $currentUser->id
            && ($validated['status'] ?? 'active') !== 'active'
        ) {
            return redirect()
                ->route('admin.users.show', $user)
                ->with('error', 'Bạn không thể tự đặt trạng thái Bị cấm cho tài khoản đang đăng nhập.');
        }

        $user->update($validated);

        if ($request->filled('password')) {
            $user->update(['password' => bcrypt($request->password)]);
        }

        $user->roles()->sync($roleIds);

        $becameBanned = $previousStatus !== 'banned' && $validated['status'] === 'banned';
        if ($becameBanned) {
            $user->refresh();
            try {
                Mail::to($user->email)->send(new AccountBannedMail($user));
            } catch (\Throwable $e) {
                Log::error('AccountBannedMail failed', [
                    'user_id' => $user->id,
                    'email' => $user->email,
                    'exception' => $e->getMessage(),
                ]);

                return redirect()
                    ->route('admin.users.show', $user)
                    ->with('success', 'Đã lưu thông tin người dùng.')
                    ->with('warning', 'Không gửi được email thông báo cấm tài khoản. Kiểm tra cấu hình MAIL trong .env.');
            }
        }

        return redirect()->route('admin.users.show', $user)->with('success', 'Đã lưu thông tin người dùng.');
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