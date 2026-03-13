<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\Booking;
use App\Models\RoomBookedDate;
use App\Models\Room;
use Illuminate\Http\Request;
use Carbon\Carbon;

class AdminStatisticsController extends Controller
{
    public function __construct()
    {
        $this->middleware('admin');
    }

    /**
     * Hiển thị dashboard thống kê
     */
    public function index()
    {
        $stats = $this->getStatistics();
        return view('admin.statistics.index', $stats);
    }

    /**
     * Lấy dữ liệu thống kê
     */
    private function getStatistics()
    {
        $now = Carbon::now();
        $startOfMonth = $now->clone()->startOfMonth();
        $endOfMonth = $now->clone()->endOfMonth();

        // Doanh thu trong tháng
        $monthlyRevenue = $this->getMonthlyRevenue($startOfMonth, $endOfMonth);
        
        // Doanh thu hôm nay
        $todayRevenue = $this->getTodayRevenue();
        
        // Doanh thu tổng cộng
        $totalRevenue = $this->getTotalRevenue();
        
        // Tỉ lệ lấp phòng trong tháng
        $occupancyRate = $this->getOccupancyRate($startOfMonth, $endOfMonth);
        
        // Tỉ lệ lấp phòng hôm nay
        $todayOccupancyRate = $this->getTodayOccupancyRate();
        
        // Số đặt phòng trong tháng
        $monthlyBookings = $this->getMonthlyBookingsCount($startOfMonth, $endOfMonth);
        
        // Số đặt phòng hôm nay
        $todayBookings = $this->getTodayBookingsCount();
        
        // Biểu đồ doanh thu (7 ngày gần nhất)
        $revenueChart = $this->getRevenueChartData();
        
        // Biểu đồ tỉ lệ lấp phòng (7 ngày gần nhất)
        $occupancyChart = $this->getOccupancyChartData();

        return [
            'monthlyRevenue' => $monthlyRevenue,
            'todayRevenue' => $todayRevenue,
            'totalRevenue' => $totalRevenue,
            'occupancyRate' => $occupancyRate,
            'todayOccupancyRate' => $todayOccupancyRate,
            'monthlyBookings' => $monthlyBookings,
            'todayBookings' => $todayBookings,
            'revenueChart' => $revenueChart,
            'occupancyChart' => $occupancyChart,
        ];
    }

    /**
     * Tính doanh thu trong tháng
     */
    private function getMonthlyRevenue($startDate, $endDate)
    {
        return Payment::whereBetween('paid_at', [$startDate, $endDate])
            ->where('status', 'completed')
            ->sum('amount') ?? 0;
    }

    /**
     * Tính doanh thu hôm nay
     */
    private function getTodayRevenue()
    {
        $today = Carbon::now()->startOfDay();
        $tomorrow = Carbon::now()->endOfDay();

        return Payment::whereBetween('paid_at', [$today, $tomorrow])
            ->where('status', 'completed')
            ->sum('amount') ?? 0;
    }

    /**
     * Tính doanh thu tổng cộng
     */
    private function getTotalRevenue()
    {
        return Payment::where('status', 'completed')
            ->sum('amount') ?? 0;
    }

    /**
     * Tính tỉ lệ lấp phòng (%)
     */
    private function getOccupancyRate($startDate, $endDate)
    {
        $totalRooms = Room::count();
        $daysInRange = $startDate->diffInDays($endDate) + 1;
        
        // Tổng số phòng-ngày có thể
        $totalRoomDays = $totalRooms * $daysInRange;

        // Số phòng-ngày đã đặt
        $bookedRoomDays = RoomBookedDate::whereBetween('booked_date', [$startDate, $endDate])
            ->count();

        if ($totalRoomDays == 0) {
            return 0;
        }

        return round(($bookedRoomDays / $totalRoomDays) * 100, 2);
    }

    /**
     * Tính tỉ lệ lấp phòng hôm nay
     */
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

    /**
     * Số đặt phòng trong tháng
     */
    private function getMonthlyBookingsCount($startDate, $endDate)
    {
        return Booking::whereBetween('check_in', [$startDate, $endDate])
            ->count();
    }

    /**
     * Số đặt phòng hôm nay
     */
    private function getTodayBookingsCount()
    {
        $today = Carbon::now()->toDateString();
        return Booking::whereDate('check_in', $today)
            ->count();
    }

    /**
     * Dữ liệu biểu đồ doanh thu (7 ngày gần nhất)
     */
    private function getRevenueChartData()
    {
        $data = [];
        $labels = [];

        for ($i = 6; $i >= 0; $i--) {
            $date = Carbon::now()->subDays($i);
            $dateString = $date->toDateString();
            
            $revenue = Payment::whereDate('paid_at', $dateString)
                ->where('status', 'completed')
                ->sum('amount') ?? 0;

            $labels[] = $date->format('d/m');
            $data[] = $revenue;
        }

        return [
            'labels' => $labels,
            'data' => $data,
        ];
    }

    /**
     * Dữ liệu biểu đồ tỉ lệ lấp phòng (7 ngày gần nhất)
     */
    private function getOccupancyChartData()
    {
        $data = [];
        $labels = [];
        $totalRooms = Room::count();

        for ($i = 6; $i >= 0; $i--) {
            $date = Carbon::now()->subDays($i);
            $dateString = $date->toDateString();
            
            $bookedRooms = RoomBookedDate::where('booked_date', $dateString)
                ->distinct('room_id')
                ->count('room_id');

            $occupancyRate = $totalRooms > 0 ? round(($bookedRooms / $totalRooms) * 100, 2) : 0;

            $labels[] = $date->format('d/m');
            $data[] = $occupancyRate;
        }

        return [
            'labels' => $labels,
            'data' => $data,
        ];
    }
}
