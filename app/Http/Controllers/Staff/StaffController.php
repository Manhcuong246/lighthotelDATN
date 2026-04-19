<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Booking;
use App\Models\Room;
use Carbon\Carbon;

class StaffController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'staff']);
    }

    public function dashboard()
    {
        $today = Carbon::today();

        // 📅 Booking check-in hôm nay
        $checkInToday = Booking::whereDate('check_in_date', $today)
            ->count();

        // 📅 Booking check-out hôm nay
        $checkOutToday = Booking::whereDate('check_out_date', $today)
            ->count();

        // 🏨 Khách đang ở (đã check-in nhưng chưa check-out)
        $guestsStaying = Booking::where('status', 'checked_in')
            ->count();

        // 📋 Danh sách check-in hôm nay
        $todayBookings = Booking::with('user', 'room')
            ->whereDate('check_in_date', $today)
            ->get();

        // 🏠 Thống kê phòng (nếu có bảng rooms)
        $roomsAvailable = Room::where('status', 'available')->count();
        $roomsMaintenance = Room::where('status', 'maintenance')->count();

        return view('staff.dashboard', compact(
            'todayBookings',
            'checkInToday',
            'checkOutToday',
            'guestsStaying',
            'roomsAvailable',
            'roomsMaintenance'
        ));
    }
}