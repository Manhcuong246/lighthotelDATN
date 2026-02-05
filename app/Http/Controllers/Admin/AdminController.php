<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Role;
use App\Models\Payment;
use App\Models\Booking;
use App\Models\RoomBookedDate;
use App\Models\Room;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

class AdminController extends Controller
{
    public function __construct()
    {
        $this->middleware('admin')->except(['showLoginForm', 'login']);
    }

    public function dashboard()
    {
        // Lấy dữ liệu thống kê
        $now = Carbon::now();
        $startOfMonth = $now->clone()->startOfMonth();
        $endOfMonth = $now->clone()->endOfMonth();

        $stats = [
            'todayRevenue' => Payment::whereDate('paid_at', $now->toDateString())
                ->where('status', 'completed')
                ->sum('amount') ?? 0,
            'monthlyRevenue' => Payment::whereBetween('paid_at', [$startOfMonth, $endOfMonth])
                ->where('status', 'completed')
                ->sum('amount') ?? 0,
            'totalRevenue' => Payment::where('status', 'completed')->sum('amount') ?? 0,
            'todayOccupancyRate' => $this->getTodayOccupancyRate(),
            'monthlyOccupancyRate' => $this->getMonthlyOccupancyRate($startOfMonth, $endOfMonth),
            'todayBookings' => Booking::whereDate('check_in', $now->toDateString())->count(),
            'monthlyBookings' => Booking::whereBetween('check_in', [$startOfMonth, $endOfMonth])->count(),
        ];

        return view('admin.dashboard', $stats);
    }

    private function getTodayOccupancyRate()
    {
        $today = Carbon::now()->toDateString();
        $totalRooms = Room::count();
        
        $bookedRoomsToday = RoomBookedDate::where('booked_date', $today)
            ->distinct('room_id')
            ->count('room_id');

        if ($totalRooms == 0) {
            return 0;
        }

        return round(($bookedRoomsToday / $totalRooms) * 100, 2);
    }

    private function getMonthlyOccupancyRate($startDate, $endDate)
    {
        $totalRooms = Room::count();
        $daysInRange = $startDate->diffInDays($endDate) + 1;
        
        $totalRoomDays = $totalRooms * $daysInRange;

        $bookedRoomDays = RoomBookedDate::whereBetween('booked_date', [$startDate, $endDate])
            ->count();

        if ($totalRoomDays == 0) {
            return 0;
        }

        return round(($bookedRoomDays / $totalRoomDays) * 100, 2);
    }
    
    public function showLoginForm()
    {
        return view('admin.login');
    }
    
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|string',
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