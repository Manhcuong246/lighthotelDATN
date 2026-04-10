<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Room;
use App\Models\Booking;
use App\Models\BookingRoom;
use App\Models\Payment;
use App\Models\RoomBookedDate;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
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
        [$rangeStart, $rangeEnd] = $this->resolveDashboardRange(request());

        // Thống kê tổng quan
        $totalRooms = Room::count();
        $totalBookings = Booking::count();
        $totalCustomers = User::whereHas('roles', function($q) {
            $q->where('name', 'customer');
        })->count();
        
        // Doanh thu (Payment status = 'paid'; ngày ghi nhận: paid_at hoặc created_at)
        $totalRevenue = (float) (Payment::where('status', 'paid')->sum('amount') ?? 0);
        $monthlyRevenue = (float) Payment::where('status', 'paid')
            ->whereRaw('YEAR(COALESCE(paid_at, created_at)) = ?', [Carbon::now()->year])
            ->whereRaw('MONTH(COALESCE(paid_at, created_at)) = ?', [Carbon::now()->month])
            ->sum('amount');
        $todayRevenue = (float) Payment::where('status', 'paid')
            ->whereRaw('DATE(COALESCE(paid_at, created_at)) = ?', [Carbon::today()->toDateString()])
            ->sum('amount');

        $roomsMaintenance = Room::where('status', 'maintenance')->count();
        $sellableRooms = max(1, $totalRooms - $roomsMaintenance);

        // Tỉ lệ lấp phòng (theo lịch giữ phòng hôm nay; mẫu số: phòng không bảo trì)
        $today = Carbon::now()->toDateString();
        $bookedRoomsToday = $this->countDistinctRoomsBookedOnDate($today);
        $occupancyRate = round(($bookedRoomsToday / $sellableRooms) * 100, 1);

        // Tỉ lệ lấp phòng tháng (đêm phòng đã bán / capacity tháng, chỉ đơn không hủy)
        $startOfMonth = Carbon::now()->startOfMonth();
        $endOfMonth = Carbon::now()->endOfMonth();
        $daysInMonth = $startOfMonth->diffInDays($endOfMonth) + 1;
        $totalRoomDays = $sellableRooms * $daysInMonth;
        $bookedRoomDays = RoomBookedDate::query()
            ->whereBetween('booked_date', [$startOfMonth->toDateString(), $endOfMonth->toDateString()])
            ->whereHas('booking', static function ($q): void {
                $q->whereNotIn('status', ['cancelled', 'cancel_requested']);
            })
            ->whereHas('room', static function ($q): void {
                $q->where('status', '!=', 'maintenance');
            })
            ->count();
        $monthlyOccupancyRate = $totalRoomDays > 0 ? round(($bookedRoomDays / $totalRoomDays) * 100, 1) : 0;

        // Dữ liệu biểu đồ doanh thu theo khoảng thời gian
        $revenueChart = $this->getRevenueChartDataForRange($rangeStart, $rangeEnd);

        // Dữ liệu biểu đồ tỉ lệ lấp phòng theo khoảng thời gian
        $occupancyChart = $this->getOccupancyChartDataForRange($rangeStart, $rangeEnd, $sellableRooms);

        // Top phòng theo doanh thu trong khoảng thời gian
        $topRoomsByRevenue = $this->getTopRoomsByRevenue(5, $rangeStart, $rangeEnd);

        // Tình trạng phòng theo lịch đêm nay (không chỉ cột status trên bảng rooms)
        $roomsBooked = $bookedRoomsToday;
        $roomsAvailable = max(0, $totalRooms - $roomsMaintenance - $roomsBooked);

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
            ,
            'rangeStart',
            'rangeEnd'
        ));
    }

    /**
     * API: dữ liệu dashboard theo khoảng thời gian (dùng cho UI lọc thời gian).
     */
    public function dashboardData(Request $request)
    {
        [$rangeStart, $rangeEnd] = $this->resolveDashboardRange($request);

        $totalRooms = Room::count();
        $roomsMaintenance = Room::where('status', 'maintenance')->count();
        $sellableRooms = max(1, $totalRooms - $roomsMaintenance);

        $revenueSum = (float) Payment::where('status', 'paid')
            ->whereBetween('paid_at', [$rangeStart->copy()->startOfDay(), $rangeEnd->copy()->endOfDay()])
            ->sum('amount');

        // fallback nếu paid_at null → dùng created_at
        $revenueSumCreatedAt = (float) Payment::where('status', 'paid')
            ->whereNull('paid_at')
            ->whereBetween('created_at', [$rangeStart->copy()->startOfDay(), $rangeEnd->copy()->endOfDay()])
            ->sum('amount');

        $revenueInRange = $revenueSum + $revenueSumCreatedAt;

        $bookingsInRange = (int) Booking::query()
            ->whereBetween('created_at', [$rangeStart->copy()->startOfDay(), $rangeEnd->copy()->endOfDay()])
            ->count();

        $revenueChart = $this->getRevenueChartDataForRange($rangeStart, $rangeEnd);
        $occupancyChart = $this->getOccupancyChartDataForRange($rangeStart, $rangeEnd, $sellableRooms);
        $topRoomsByRevenue = $this->getTopRoomsByRevenue(5, $rangeStart, $rangeEnd);

        return response()->json([
            'range' => [
                'start' => $rangeStart->toDateString(),
                'end' => $rangeEnd->toDateString(),
            ],
            'kpis' => [
                'revenue' => $revenueInRange,
                'bookings' => $bookingsInRange,
                'sellable_rooms' => $sellableRooms,
            ],
            'charts' => [
                'revenue' => $revenueChart,
                'occupancy' => $occupancyChart,
            ],
            'top_rooms' => $topRoomsByRevenue->values(),
        ]);
    }
    
    private function resolveDashboardRange(Request $request): array
    {
        $end = $request->filled('end') ? Carbon::parse($request->input('end')) : Carbon::now();
        $start = $request->filled('start') ? Carbon::parse($request->input('start')) : Carbon::now()->subDays(6);

        $start = $start->startOfDay();
        $end = $end->endOfDay();

        if ($start->greaterThan($end)) {
            [$start, $end] = [$end->copy()->startOfDay(), $start->copy()->endOfDay()];
        }

        // Giới hạn tối đa 366 ngày để tránh query quá nặng
        if ($start->diffInDays($end) > 366) {
            $start = $end->copy()->subDays(366)->startOfDay();
        }

        return [$start, $end];
    }

    private function getRevenueChartDataForRange(Carbon $start, Carbon $end): array
    {
        $labels = [];
        $data = [];

        $cursor = $start->copy()->startOfDay();
        $endDay = $end->copy()->startOfDay();

        while ($cursor->lessThanOrEqualTo($endDay)) {
            $labels[] = $cursor->format('d/m');

            $revenuePaidAt = (float) Payment::where('status', 'paid')
                ->whereBetween('paid_at', [$cursor->copy()->startOfDay(), $cursor->copy()->endOfDay()])
                ->sum('amount');

            $revenueCreatedAt = (float) Payment::where('status', 'paid')
                ->whereNull('paid_at')
                ->whereBetween('created_at', [$cursor->copy()->startOfDay(), $cursor->copy()->endOfDay()])
                ->sum('amount');

            $data[] = $revenuePaidAt + $revenueCreatedAt;

            $cursor->addDay();
        }

        $max = count($data) > 0 ? max($data) : 0;
        $suggestedMax = $max > 0 ? (int) ceil($max * 1.2 / 100000) * 100000 : 1000000;

        return ['labels' => $labels, 'data' => $data, 'suggestedMax' => $suggestedMax];
    }

    private function getOccupancyChartDataForRange(Carbon $start, Carbon $end, int $sellableRooms): array
    {
        $labels = [];
        $data = [];
        $denom = max(1, $sellableRooms);

        $cursor = $start->copy()->startOfDay();
        $endDay = $end->copy()->startOfDay();

        while ($cursor->lessThanOrEqualTo($endDay)) {
            $labels[] = $cursor->format('d/m');
            $bookedRooms = $this->countDistinctRoomsBookedOnDate($cursor->toDateString());
            $data[] = round(($bookedRooms / $denom) * 100, 1);

            $cursor->addDay();
        }

        return ['labels' => $labels, 'data' => $data];
    }

    /**
     * Số phòng (không bảo trì) có ít nhất một đêm được giữ trong ngày $date.
     */
    private function countDistinctRoomsBookedOnDate(string $date): int
    {
        return (int) RoomBookedDate::query()
            ->whereDate('booked_date', $date)
            ->whereHas('booking', static function ($q): void {
                $q->whereNotIn('status', ['cancelled', 'cancel_requested']);
            })
            ->whereHas('room', static function ($q): void {
                $q->where('status', '!=', 'maintenance');
            })
            ->selectRaw('COUNT(DISTINCT room_id) as aggregate')
            ->value('aggregate');
    }

    /**
     * Doanh thu đã thanh toán gán theo phòng: booking_rooms.subtotal + đơn cũ chỉ có bookings.room_id.
     *
     * @return Collection<int, object{id: int, name: string, total_revenue: float}>
     */
    private function getTopRoomsByRevenue(int $limit, ?Carbon $rangeStart = null, ?Carbon $rangeEnd = null): Collection
    {
        $exclude = ['cancelled', 'cancel_requested'];
        $rangeStart = $rangeStart?->copy()->startOfDay();
        $rangeEnd = $rangeEnd?->copy()->endOfDay();

        $fromBookingRooms = BookingRoom::query()
            ->selectRaw('booking_rooms.room_id as id, rooms.name, SUM(booking_rooms.subtotal) as total_revenue')
            ->join('bookings', 'bookings.id', '=', 'booking_rooms.booking_id')
            ->join('rooms', 'rooms.id', '=', 'booking_rooms.room_id')
            ->whereHas('booking', static function ($q) use ($exclude, $rangeStart, $rangeEnd): void {
                $q->whereNotIn('status', $exclude)
                    ->whereHas('payments', static fn ($p) => $p->where('status', 'paid'));

                if ($rangeStart && $rangeEnd) {
                    $q->whereBetween('created_at', [$rangeStart, $rangeEnd]);
                }
            })
            ->groupBy('booking_rooms.room_id', 'rooms.name')
            ->get();

        $fromLegacyRoomId = Booking::query()
            ->selectRaw(
                'bookings.room_id as id, rooms.name, SUM((SELECT COALESCE(SUM(p.amount), 0) FROM payments p WHERE p.booking_id = bookings.id AND p.status = ?)) as total_revenue',
                ['paid']
            )
            ->join('rooms', 'rooms.id', '=', 'bookings.room_id')
            ->whereNotNull('bookings.room_id')
            ->whereNotIn('bookings.status', $exclude)
            ->whereDoesntHave('bookingRooms')
            ->whereHas('payments', static fn ($p) => $p->where('status', 'paid'))
            ->when($rangeStart && $rangeEnd, static function ($q) use ($rangeStart, $rangeEnd): void {
                $q->whereBetween('bookings.created_at', [$rangeStart, $rangeEnd]);
            })
            ->groupBy('bookings.room_id', 'rooms.name')
            ->get();

        return $fromBookingRooms
            ->concat($fromLegacyRoomId)
            ->groupBy('id')
            ->map(static function (Collection $rows): object {
                $first = $rows->first();

                return (object) [
                    'id' => (int) $first->id,
                    'name' => (string) $first->name,
                    'total_revenue' => (float) $rows->sum('total_revenue'),
                ];
            })
            ->sortByDesc(static fn (object $r): float => $r->total_revenue)
            ->values()
            ->take($limit);
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
        
        $callback = function () {
            $output = fopen('php://output', 'w');
            fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

            // Header
            fputcsv($output, ['Thống kê khách sạn - ' . Carbon::now()->format('d/m/Y')]);
            fputcsv($output, []);

            // Doanh thu (cùng logic trang tổng quan)
            $totalRevenue = Payment::where('status', 'paid')->sum('amount') ?? 0;
            $monthlyRevenue = Payment::where('status', 'paid')
                ->whereRaw('YEAR(COALESCE(paid_at, created_at)) = ?', [Carbon::now()->year])
                ->whereRaw('MONTH(COALESCE(paid_at, created_at)) = ?', [Carbon::now()->month])
                ->sum('amount') ?? 0;
            $todayRevenue = Payment::where('status', 'paid')
                ->whereRaw('DATE(COALESCE(paid_at, created_at)) = ?', [Carbon::today()->toDateString()])
                ->sum('amount') ?? 0;

            fputcsv($output, ['DOANH THU']);
            fputcsv($output, ['Tổng doanh thu', number_format($totalRevenue, 0, ',', '.') . ' ₫']);
            fputcsv($output, ['Doanh thu tháng này', number_format($monthlyRevenue, 0, ',', '.') . ' ₫']);
            fputcsv($output, ['Doanh thu hôm nay', number_format($todayRevenue, 0, ',', '.') . ' ₫']);
            fputcsv($output, []);

            $totalRooms = Room::count();
            $roomsMaintenance = Room::where('status', 'maintenance')->count();
            $sellableRooms = max(1, $totalRooms - $roomsMaintenance);
            $bookedRoomsToday = (int) RoomBookedDate::query()
                ->whereDate('booked_date', Carbon::today()->toDateString())
                ->whereHas('booking', static function ($q): void {
                    $q->whereNotIn('status', ['cancelled', 'cancel_requested']);
                })
                ->whereHas('room', static function ($q): void {
                    $q->where('status', '!=', 'maintenance');
                })
                ->selectRaw('COUNT(DISTINCT room_id) as aggregate')
                ->value('aggregate');
            $occupancyRate = round(($bookedRoomsToday / $sellableRooms) * 100, 1);
            $roomsAvailable = max(0, $totalRooms - $roomsMaintenance - $bookedRoomsToday);

            fputcsv($output, ['TÌNH TRẠNG PHÒNG (HÔM NAY)']);
            fputcsv($output, ['Tổng số phòng', $totalRooms]);
            fputcsv($output, ['Bảo trì', $roomsMaintenance]);
            fputcsv($output, ['Có lịch / đã đặt (đêm nay)', $bookedRoomsToday]);
            fputcsv($output, ['Trống (không bảo trì, không lịch đêm nay)', $roomsAvailable]);
            fputcsv($output, []);

            fputcsv($output, ['TỈ LỆ LẤP PHÒNG (HÔM NAY)']);
            fputcsv($output, ['Phòng kinh doanh (không bảo trì)', $sellableRooms]);
            fputcsv($output, ['Phòng có khách / đặt đêm nay', $bookedRoomsToday]);
            fputcsv($output, ['Tỉ lệ lấp phòng', $occupancyRate . '%']);
            
            fclose($output);
        };
        
        return response()->stream($callback, 200, $headers);
    }
}
