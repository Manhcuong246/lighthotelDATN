<?php
namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Booking;

class StaffController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'staff']);
    }

    public function dashboard()
    {
        $todayBookings = Booking::with('user')
            ->whereDate('check_in_date', today())
            ->get();

        return view('staff.dashboard', compact('todayBookings'));
    }
}