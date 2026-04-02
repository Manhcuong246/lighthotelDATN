<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Role;
use App\Models\Room;
use App\Models\Booking;
use App\Models\Payment;
use App\Models\RoomBookedDate;
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
        // Thống kê tổng quan
        $totalRooms = Room::count();
        $totalBookings = Booking::count();
        $totalCustomers = User::whereHas('roles', function($q) {
            $q->where('name', 'customer');
        })->count();
        
// Doanh thu (Payment status = 'paid' khi thanh toán thành công)
        $totalRevenue = Payment::where('status', 'paid')->sum('amount') ?? 0;
        $monthlyRevenue = Payment::where('status', 'paid')
            ->whereMonth('paid_at', Carbon::now()->month)
            ->whereYear('paid_at', Carbon::now()->year)
            ->sum('amount') ?? 0;
        $todayRevenue = Payment::where('status', 'paid')
            ->whereDate('paid_at', Carbon::today())
            ->sum('amount') ?? 0;
        
        // Tỉ lệ lấp phòng
        $today = Carbon::now()->toDateString();
        $bookedRoomsToday = RoomBookedDate::where('booked_date', $today)
            ->distinct('room_id')
            ->count('room_id');
        $occupancyRate = $totalRooms > 0 ? round(($bookedRoomsToday / $totalRooms) * 100, 1) : 0;
        
        // Tỉ lệ lấp phòng tháng
        $startOfMonth = Carbon::now()->startOfMonth();
        $endOfMonth = Carbon::now()->endOfMonth();
        $daysInMonth = $startOfMonth->diffInDays($endOfMonth) + 1;
        $totalRoomDays = $totalRooms * $daysInMonth;
        $bookedRoomDays = RoomBookedDate::whereBetween('booked_date', [$startOfMonth, $endOfMonth])
            ->count();
        $monthlyOccupancyRate = $totalRoomDays > 0 ? round(($bookedRoomDays / $totalRoomDays) * 100, 1) : 0;
        
        // Dữ liệu biểu đồ doanh thu 7 ngày gần nhất
        $revenueChart = $this->getRevenueChartData();
        
        // Dữ liệu biểu đồ tỉ lệ lấp phòng 7 ngày gần nhất
        $occupancyChart = $this->getOccupancyChartData();
        
        // Top 5 phòng có doanh thu cao nhất (cho biểu đồ tròn)
        $topRoomsByRevenue = Payment::where('payments.status', 'paid')
            ->join('bookings', 'payments.booking_id', '=', 'bookings.id')
            ->join('rooms', 'bookings.room_id', '=', 'rooms.id')
            ->selectRaw('rooms.id, rooms.name, SUM(payments.amount) as total_revenue')
            ->groupBy('rooms.id', 'rooms.name')
            ->orderByDesc('total_revenue')
            ->limit(5)
            ->get();

        // Tình trạng phòng (available, booked, maintenance)
        $roomsAvailable = Room::where('status', 'available')->count();
        $roomsBooked = Room::where('status', 'booked')->count();
        $roomsMaintenance = Room::where('status', 'maintenance')->count();
        return view('admin.dashboard', compact(
            'totalRooms',
            'totalBookings',
            'totalCustomers',
            'totalRevenue',
            'monthlyRevenue',
            'todayRevenue',
            'occupancyRate',
            'monthlyOccupancyRate',
            'revenueChart',
            'occupancyChart',
            'topRoomsByRevenue',
            'roomsAvailable',
            'roomsBooked',
            'roomsMaintenance'
        ));
    }
    
    private function getRevenueChartData()
    {
        $labels = [];
        $data = [];
        
        for ($i = 6; $i >= 0; $i--) {
            $date = Carbon::now()->subDays($i);
            $labels[] = $date->format('d/m');
            $revenue = Payment::where('status', 'paid')
                ->whereDate('paid_at', $date)
                ->sum('amount') ?? 0;
            $data[] = $revenue;
        }
        
        $max = count($data) > 0 ? max($data) : 0;
        $suggestedMax = $max > 0 ? (int) ceil($max * 1.2 / 100000) * 100000 : 1000000;
        
        return ['labels' => $labels, 'data' => $data, 'suggestedMax' => $suggestedMax];
    }
    
    private function getOccupancyChartData()
    {
        $labels = [];
        $data = [];
        $totalRooms = Room::count();
        
        for ($i = 6; $i >= 0; $i--) {
            $date = Carbon::now()->subDays($i);
            $labels[] = $date->format('d/m');
            $bookedRooms = RoomBookedDate::where('booked_date', $date->toDateString())
                ->distinct('room_id')
                ->count('room_id');
            $rate = $totalRooms > 0 ? round(($bookedRooms / $totalRooms) * 100, 1) : 0;
            $data[] = $rate;
        }
        
        return ['labels' => $labels, 'data' => $data];
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
    
    public function exportStatistics()
    {
        $today = Carbon::now()->format('Y-m-d');
        $filename = "bao-cao-thong-ke-{$today}.csv";
        
        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename={$filename}",
        ];
        
        $callback = function() {
            $output = fopen('php://output', 'w');
            fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
            
            // Header
            fputcsv($output, ['Thống kê khách sạn - ' . Carbon::now()->format('d/m/Y')]);
            fputcsv($output, []);
            
            // Doanh thu
            $totalRevenue = Payment::where('status', 'paid')->sum('amount') ?? 0;
            $monthlyRevenue = Payment::where('status', 'paid')
                ->whereMonth('paid_at', Carbon::now()->month)
                ->sum('amount') ?? 0;
            $todayRevenue = Payment::where('status', 'paid')
                ->whereDate('paid_at', Carbon::today())
                ->sum('amount') ?? 0;
            
            fputcsv($output, ['DOANH THU']);
            fputcsv($output, ['Tổng doanh thu', number_format($totalRevenue, 0, ',', '.') . ' ₫']);
            fputcsv($output, ['Doanh thu tháng này', number_format($monthlyRevenue, 0, ',', '.') . ' ₫']);
            fputcsv($output, ['Doanh thu hôm nay', number_format($todayRevenue, 0, ',', '.') . ' ₫']);
            fputcsv($output, []);
            
            // Tỉ lệ lấp phòng
            $totalRooms = Room::count();
            $bookedRoomsToday = RoomBookedDate::where('booked_date', Carbon::today())
                ->distinct('room_id')
                ->count('room_id');
            $occupancyRate = $totalRooms > 0 ? round(($bookedRoomsToday / $totalRooms) * 100, 1) : 0;
            
            fputcsv($output, ['TỈ LỆ LẤP PHÒNG']);
            fputcsv($output, ['Tổng số phòng', $totalRooms]);
            fputcsv($output, ['Phòng đã đặt hôm nay', $bookedRoomsToday]);
            fputcsv($output, ['Tỉ lệ lấp phòng', $occupancyRate . '%']);
            
            fclose($output);
        };
        
        return response()->stream($callback, 200, $headers);
    }
}
