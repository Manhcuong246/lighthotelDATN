<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Room;
use App\Models\Booking;
use App\Models\BookingRoom;
use App\Models\Payment;
use App\Models\RoomBookedDate;
use App\Models\RoomType;
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

    public function dashboard(Request $request)
    {
        $roomTypes = RoomType::orderBy('name')->get(['id', 'name']);
        $roomTypeFilterId = $this->normalizeRoomTypeFilterId($request);

        if ($roomTypeFilterId === null) {
            return $this->renderDashboardAllTypes($roomTypes, null);
        }

        return $this->renderDashboardFilteredByRoomType($request, $roomTypes, $roomTypeFilterId);
    }

    /**
     * @return \Illuminate\Contracts\View\View
     */
    private function renderDashboardAllTypes(Collection $roomTypes, ?int $roomTypeFilterId)
    {
        // Thống kê tổng quan
        $totalRooms = Room::count();
        $totalBookings = Booking::count();
        $totalCustomers = User::whereHas('roles', function ($q) {
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

        // Dữ liệu biểu đồ doanh thu 7 ngày gần nhất
        $revenueChart = $this->getRevenueChartData(null);

        // Dữ liệu biểu đồ tỉ lệ lấp phòng 7 ngày gần nhất
        $occupancyChart = $this->getOccupancyChartData($sellableRooms, null);

        // Top 5 loại phòng (theo tổng thành tiền dòng đặt đã thanh toán + đơn cũ)
        $topRoomTypesByRevenue = $this->getTopRoomTypesByRevenue(5, null);

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
            'topRoomTypesByRevenue',
            'roomsAvailable',
            'roomsBooked',
            'roomsMaintenance',
            'roomTypes',
            'roomTypeFilterId'
        ));
    }

    /**
     * @return \Illuminate\Contracts\View\View
     */
    private function renderDashboardFilteredByRoomType(Request $request, Collection $roomTypes, int $roomTypeFilterId)
    {
        $roomIdsOfType = Room::where('room_type_id', $roomTypeFilterId)->pluck('id');

        $totalRooms = Room::where('room_type_id', $roomTypeFilterId)->count();
        $totalBookings = Booking::query()
            ->whereNotIn('status', ['cancelled', 'cancel_requested'])
            ->where(function ($q) use ($roomTypeFilterId, $roomIdsOfType) {
                $q->whereHas('bookingRooms', function ($br) use ($roomTypeFilterId, $roomIdsOfType) {
                    $br->where('room_type_id', $roomTypeFilterId)
                        ->orWhereIn('room_id', $roomIdsOfType);
                })->orWhere(function ($q2) use ($roomIdsOfType) {
                    $q2->whereDoesntHave('bookingRooms')->whereIn('room_id', $roomIdsOfType);
                });
            })
            ->count();

        $totalCustomers = User::whereHas('roles', function ($q) {
            $q->where('name', 'customer');
        })->count();

        $baseBr = BookingRoom::query()
            ->where(function ($q) use ($roomTypeFilterId, $roomIdsOfType) {
                $q->where('room_type_id', $roomTypeFilterId)
                    ->orWhereIn('room_id', $roomIdsOfType);
            })
            ->whereHas('booking', function ($b) {
                $b->whereNotIn('status', ['cancelled', 'cancel_requested'])
                    ->whereHas('payments', fn ($p) => $p->where('status', 'paid'));
            });

        $totalRevenue = (float) $baseBr->clone()->sum('subtotal')
            + $this->legacyPaidBookingRevenueForRoomType($roomTypeFilterId);

        $monthlyRevenue = (float) BookingRoom::query()
            ->where(function ($q) use ($roomTypeFilterId, $roomIdsOfType) {
                $q->where('room_type_id', $roomTypeFilterId)
                    ->orWhereIn('room_id', $roomIdsOfType);
            })
            ->whereHas('booking', function ($b) {
                $b->whereNotIn('status', ['cancelled', 'cancel_requested'])
                    ->whereHas('payments', function ($p) {
                        $p->where('status', 'paid')
                            ->whereRaw('YEAR(COALESCE(payments.paid_at, payments.created_at)) = ?', [Carbon::now()->year])
                            ->whereRaw('MONTH(COALESCE(payments.paid_at, payments.created_at)) = ?', [Carbon::now()->month]);
                    });
            })
            ->sum('subtotal');

        $todayRevenue = (float) BookingRoom::query()
            ->where(function ($q) use ($roomTypeFilterId, $roomIdsOfType) {
                $q->where('room_type_id', $roomTypeFilterId)
                    ->orWhereIn('room_id', $roomIdsOfType);
            })
            ->whereHas('booking', function ($b) {
                $b->whereNotIn('status', ['cancelled', 'cancel_requested'])
                    ->whereHas('payments', function ($p) {
                        $p->where('status', 'paid')
                            ->whereRaw('DATE(COALESCE(payments.paid_at, payments.created_at)) = ?', [Carbon::today()->toDateString()]);
                    });
            })
            ->sum('subtotal');

        $roomsMaintenance = Room::where('room_type_id', $roomTypeFilterId)->where('status', 'maintenance')->count();
        $sellableRooms = max(1, $totalRooms - $roomsMaintenance);

        $today = Carbon::now()->toDateString();
        $bookedRoomsToday = $this->countDistinctRoomsBookedOnDate($today, $roomTypeFilterId);
        $occupancyRate = round(($bookedRoomsToday / $sellableRooms) * 100, 1);

        $startOfMonth = Carbon::now()->startOfMonth();
        $endOfMonth = Carbon::now()->endOfMonth();
        $daysInMonth = $startOfMonth->diffInDays($endOfMonth) + 1;
        $totalRoomDays = $sellableRooms * $daysInMonth;
        $bookedRoomDays = RoomBookedDate::query()
            ->whereBetween('booked_date', [$startOfMonth->toDateString(), $endOfMonth->toDateString()])
            ->whereHas('booking', static function ($q): void {
                $q->whereNotIn('status', ['cancelled', 'cancel_requested']);
            })
            ->whereHas('room', function ($q) use ($roomTypeFilterId): void {
                $q->where('room_type_id', $roomTypeFilterId)
                    ->where('status', '!=', 'maintenance');
            })
            ->count();
        $monthlyOccupancyRate = $totalRoomDays > 0 ? round(($bookedRoomDays / $totalRoomDays) * 100, 1) : 0;

        $revenueChart = $this->getRevenueChartData($roomTypeFilterId);
        $occupancyChart = $this->getOccupancyChartData($sellableRooms, $roomTypeFilterId);
        $topRoomTypesByRevenue = $this->getTopRoomTypesByRevenue(5, $roomTypeFilterId);

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
            'topRoomTypesByRevenue',
            'roomsAvailable',
            'roomsBooked',
            'roomsMaintenance',
            'roomTypes',
            'roomTypeFilterId'
        ));
    }

    private function normalizeRoomTypeFilterId(Request $request): ?int
    {
        if (! $request->filled('room_type_id')) {
            return null;
        }

        $id = (int) $request->input('room_type_id');
        if ($id < 1 || ! RoomType::whereKey($id)->exists()) {
            return null;
        }

        return $id;
    }

    private function legacyPaidBookingRevenueForRoomType(int $roomTypeId): float
    {
        $roomIds = Room::where('room_type_id', $roomTypeId)->pluck('id');

        return (float) Booking::whereDoesntHave('bookingRooms')
            ->whereNotIn('status', ['cancelled', 'cancel_requested'])
            ->whereIn('room_id', $roomIds)
            ->whereHas('payments', fn ($p) => $p->where('status', 'paid'))
            ->sum('total_price');
    }

    /**
     * API: chỉ phục vụ component "Biểu đồ doanh thu" trên dashboard.
     * Cho phép chọn khoảng ngày mà KHÔNG ảnh hưởng các KPI / widget khác.
     */
    public function revenueChartData(Request $request)
    {
        if (! auth()->user()->isAdmin()) {
            abort(403, 'Chỉ quản trị viên mới xem dữ liệu biểu đồ doanh thu.');
        }

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

        return response()->json($this->getRevenueChartDataForRange($start, $end, $this->normalizeRoomTypeFilterId($request)));
    }
    
    private function getRevenueChartData(?int $roomTypeId = null): array
    {
        $labels = [];
        $data = [];

        for ($i = 6; $i >= 0; $i--) {
            $date = Carbon::now()->subDays($i);
            $labels[] = $date->format('d/m');
            $data[] = $this->sumRevenueForRoomTypeOnDate($roomTypeId, $date->toDateString());
        }

        $max = count($data) > 0 ? max($data) : 0;
        $suggestedMax = $max > 0 ? (int) ceil($max * 1.2 / 100000) * 100000 : 1000000;

        return ['labels' => $labels, 'data' => $data, 'suggestedMax' => $suggestedMax];
    }

    private function getRevenueChartDataForRange(Carbon $start, Carbon $end, ?int $roomTypeId = null): array
    {
        $labels = [];
        $data = [];

        $cursor = $start->copy()->startOfDay();
        $endDay = $end->copy()->startOfDay();

        while ($cursor->lessThanOrEqualTo($endDay)) {
            $labels[] = $cursor->format('d/m');
            $data[] = $this->sumRevenueForRoomTypeOnDate($roomTypeId, $cursor->toDateString());
            $cursor->addDay();
        }

        $max = count($data) > 0 ? max($data) : 0;
        $suggestedMax = $max > 0 ? (int) ceil($max * 1.2 / 100000) * 100000 : 1000000;

        return ['labels' => $labels, 'data' => $data, 'suggestedMax' => $suggestedMax];
    }

    /**
     * Doanh thu trong ngày: toàn hệ thống (Payment paid) hoặc theo loại phòng (tổng booking_rooms.subtotal có thanh toán ghi nhận trong ngày).
     */
    private function sumRevenueForRoomTypeOnDate(?int $roomTypeId, string $dateYmd): float
    {
        if ($roomTypeId === null) {
            return (float) Payment::where('status', 'paid')
                ->whereRaw('DATE(COALESCE(paid_at, created_at)) = ?', [$dateYmd])
                ->sum('amount');
        }

        $roomIdsOfType = Room::where('room_type_id', $roomTypeId)->pluck('id');

        $fromLines = (float) BookingRoom::query()
            ->where(function ($q) use ($roomTypeId, $roomIdsOfType) {
                $q->where('room_type_id', $roomTypeId)
                    ->orWhereIn('room_id', $roomIdsOfType);
            })
            ->whereHas('booking', function ($b) use ($dateYmd) {
                $b->whereNotIn('status', ['cancelled', 'cancel_requested'])
                    ->whereHas('payments', function ($p) use ($dateYmd) {
                        $p->where('status', 'paid')
                            ->whereRaw('DATE(COALESCE(payments.paid_at, payments.created_at)) = ?', [$dateYmd]);
                    });
            })
            ->sum('subtotal');

        $legacy = (float) Booking::query()
            ->whereDoesntHave('bookingRooms')
            ->whereNotIn('status', ['cancelled', 'cancel_requested'])
            ->whereIn('room_id', $roomIdsOfType)
            ->whereHas('payments', function ($p) use ($dateYmd) {
                $p->where('status', 'paid')
                    ->whereRaw('DATE(COALESCE(payments.paid_at, payments.created_at)) = ?', [$dateYmd]);
            })
            ->sum('total_price');

        return $fromLines + $legacy;
    }

    private function getOccupancyChartData(int $sellableRooms, ?int $roomTypeId = null): array
    {
        $labels = [];
        $data = [];
        $denom = max(1, $sellableRooms);

        for ($i = 6; $i >= 0; $i--) {
            $date = Carbon::now()->subDays($i);
            $labels[] = $date->format('d/m');
            $bookedRooms = $this->countDistinctRoomsBookedOnDate($date->toDateString(), $roomTypeId);
            $data[] = round(($bookedRooms / $denom) * 100, 1);
        }

        return ['labels' => $labels, 'data' => $data];
    }

    /**
     * Số phòng (không bảo trì) có ít nhất một đêm được giữ trong ngày $date.
     */
    private function countDistinctRoomsBookedOnDate(string $date, ?int $roomTypeId = null): int
    {
        $q = RoomBookedDate::query()
            ->whereDate('booked_date', $date)
            ->whereHas('booking', static function ($q): void {
                $q->whereNotIn('status', ['cancelled', 'cancel_requested']);
            })
            ->whereHas('room', function ($q) use ($roomTypeId): void {
                $q->where('status', '!=', 'maintenance');
                if ($roomTypeId !== null) {
                    $q->where('room_type_id', $roomTypeId);
                }
            });

        return (int) $q->selectRaw('COUNT(DISTINCT room_id) as aggregate')
            ->value('aggregate');
    }

    /**
     * Doanh thu đã thanh toán gán theo loại phòng: booking_rooms (COALESCE dòng loại, loại từ phòng vật lý)) + đơn cũ chỉ có bookings.room_id.
     *
     * @return Collection<int, object{id: int, name: string, total_revenue: float}>
     */
    private function getTopRoomTypesByRevenue(int $limit, ?int $roomTypeId = null): Collection
    {
        $exclude = ['cancelled', 'cancel_requested'];
        $coalesce = 'COALESCE(booking_rooms.room_type_id, rooms.room_type_id)';

        $fromBookingRooms = BookingRoom::query()
            ->selectRaw($coalesce.' as room_type_id, SUM(booking_rooms.subtotal) as total_revenue')
            ->join('bookings', 'bookings.id', '=', 'booking_rooms.booking_id')
            ->leftJoin('rooms', 'rooms.id', '=', 'booking_rooms.room_id')
            ->whereHas('booking', static function ($q) use ($exclude): void {
                $q->whereNotIn('status', $exclude)
                    ->whereHas('payments', static fn ($p) => $p->where('status', 'paid'));
            })
            ->whereRaw($coalesce.' IS NOT NULL')
            ->when($roomTypeId !== null, static function ($q) use ($roomTypeId, $coalesce) {
                $q->whereRaw($coalesce.' = ?', [$roomTypeId]);
            })
            ->groupByRaw($coalesce)
            ->get();

        $fromLegacy = Booking::query()
            ->selectRaw(
                'rooms.room_type_id as room_type_id, SUM((SELECT COALESCE(SUM(p.amount), 0) FROM payments p WHERE p.booking_id = bookings.id AND p.status = ?)) as total_revenue',
                ['paid']
            )
            ->join('rooms', 'rooms.id', '=', 'bookings.room_id')
            ->whereNotNull('bookings.room_id')
            ->whereNotIn('bookings.status', $exclude)
            ->whereDoesntHave('bookingRooms')
            ->whereHas('payments', static fn ($p) => $p->where('status', 'paid'))
            ->when($roomTypeId !== null, static fn ($q) => $q->where('rooms.room_type_id', $roomTypeId))
            ->groupBy('rooms.room_type_id')
            ->get();

        $totals = [];
        foreach ($fromBookingRooms as $row) {
            $rid = (int) $row->room_type_id;
            if ($rid < 1) {
                continue;
            }
            $totals[$rid] = ($totals[$rid] ?? 0) + (float) $row->total_revenue;
        }
        foreach ($fromLegacy as $row) {
            $rid = (int) $row->room_type_id;
            if ($rid < 1) {
                continue;
            }
            $totals[$rid] = ($totals[$rid] ?? 0) + (float) $row->total_revenue;
        }

        if ($totals === []) {
            return collect();
        }

        $names = RoomType::query()->whereIn('id', array_keys($totals))->pluck('name', 'id');

        return collect($totals)
            ->map(static function (float $rev, int|string $id) use ($names): object {
                $id = (int) $id;

                return (object) [
                    'id' => $id,
                    'name' => (string) ($names[$id] ?? ('Loại #'.$id)),
                    'total_revenue' => $rev,
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

    $user = User::with('roles')
        ->where('email', $request->email)
        ->first();

    if (!$user) {
        return back()->withErrors([
            'email' => 'Thông tin đăng nhập không chính xác.'
        ]);
    }

    // Kiểm tra có role admin hoặc staff
    $canAccess = $user->roles()
        ->whereIn('name', ['admin', 'staff'])
        ->exists();

    if (!$canAccess) {
        return back()->withErrors([
            'email' => 'Bạn không có quyền truy cập khu vực quản trị.'
        ]);
    }

    // Kiểm tra password
    if (Hash::check($request->password, $user->password)) {

        Auth::login($user, $remember);

        // 🎯 PHÂN LUỒNG THEO ROLE
        if ($user->roles()->where('name', 'staff')->exists()) {
            return redirect()->route('staff.dashboard');
        }

        if ($user->roles()->where('name', 'admin')->exists()) {
            return redirect()->route('admin.dashboard');
        }
    }

    return back()->withErrors([
        'email' => 'Thông tin đăng nhập không chính xác.'
    ]);
}
    public function logout()
    {
        Auth::logout();
        return redirect()->route('admin.login');
    }
    
    public function exportStatistics(Request $request)
    {
        if (! auth()->user()->isAdmin()) {
            abort(403, 'Chỉ quản trị viên mới xuất báo cáo thống kê.');
        }

        $roomTypeFilterId = $this->normalizeRoomTypeFilterId($request);
        $scopeTitle = 'Toàn bộ khách sạn';
        if ($roomTypeFilterId !== null) {
            $rn = RoomType::whereKey($roomTypeFilterId)->value('name');
            $scopeTitle = $rn ? 'Loại phòng: '.$rn : 'Loại phòng #'.$roomTypeFilterId;
        }

        $today = Carbon::now()->format('Y-m-d');
        $filename = $roomTypeFilterId
            ? 'bao-cao-loai-phong-'.$roomTypeFilterId.'-'.$today.'.csv'
            : 'bao-cao-thong-ke-'.$today.'.csv';

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename='.$filename,
        ];

        $exportFilterId = $roomTypeFilterId;
        $exportScopeTitle = $scopeTitle;
        $legacyHelper = fn (int $id): float => $this->legacyPaidBookingRevenueForRoomType($id);

        $callback = function () use ($exportFilterId, $exportScopeTitle, $legacyHelper) {
            $output = fopen('php://output', 'w');
            fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

            fputcsv($output, ['Thống kê - '.$exportScopeTitle.' - '.Carbon::now()->format('d/m/Y')]);
            fputcsv($output, []);

            if ($exportFilterId === null) {
                $totalRevenue = Payment::where('status', 'paid')->sum('amount') ?? 0;
                $monthlyRevenue = Payment::where('status', 'paid')
                    ->whereRaw('YEAR(COALESCE(paid_at, created_at)) = ?', [Carbon::now()->year])
                    ->whereRaw('MONTH(COALESCE(paid_at, created_at)) = ?', [Carbon::now()->month])
                    ->sum('amount') ?? 0;
                $todayRevenue = Payment::where('status', 'paid')
                    ->whereRaw('DATE(COALESCE(paid_at, created_at)) = ?', [Carbon::today()->toDateString()])
                    ->sum('amount') ?? 0;

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
            } else {
                $rid = $exportFilterId;
                $roomIdsOfType = Room::where('room_type_id', $rid)->pluck('id');

                $baseBr = BookingRoom::query()
                    ->where(function ($q) use ($rid, $roomIdsOfType) {
                        $q->where('room_type_id', $rid)->orWhereIn('room_id', $roomIdsOfType);
                    })
                    ->whereHas('booking', function ($b) {
                        $b->whereNotIn('status', ['cancelled', 'cancel_requested'])
                            ->whereHas('payments', fn ($p) => $p->where('status', 'paid'));
                    });

                $totalRevenue = (float) $baseBr->clone()->sum('subtotal') + $legacyHelper($rid);

                $monthlyRevenue = (float) BookingRoom::query()
                    ->where(function ($q) use ($rid, $roomIdsOfType) {
                        $q->where('room_type_id', $rid)->orWhereIn('room_id', $roomIdsOfType);
                    })
                    ->whereHas('booking', function ($b) {
                        $b->whereNotIn('status', ['cancelled', 'cancel_requested'])
                            ->whereHas('payments', function ($p) {
                                $p->where('status', 'paid')
                                    ->whereRaw('YEAR(COALESCE(payments.paid_at, payments.created_at)) = ?', [Carbon::now()->year])
                                    ->whereRaw('MONTH(COALESCE(payments.paid_at, payments.created_at)) = ?', [Carbon::now()->month]);
                            });
                    })
                    ->sum('subtotal');

                $todayRevenue = (float) BookingRoom::query()
                    ->where(function ($q) use ($rid, $roomIdsOfType) {
                        $q->where('room_type_id', $rid)->orWhereIn('room_id', $roomIdsOfType);
                    })
                    ->whereHas('booking', function ($b) {
                        $b->whereNotIn('status', ['cancelled', 'cancel_requested'])
                            ->whereHas('payments', function ($p) {
                                $p->where('status', 'paid')
                                    ->whereRaw('DATE(COALESCE(payments.paid_at, payments.created_at)) = ?', [Carbon::today()->toDateString()]);
                            });
                    })
                    ->sum('subtotal');

                $totalRooms = Room::where('room_type_id', $rid)->count();
                $roomsMaintenance = Room::where('room_type_id', $rid)->where('status', 'maintenance')->count();
                $sellableRooms = max(1, $totalRooms - $roomsMaintenance);
                $bookedRoomsToday = (int) RoomBookedDate::query()
                    ->whereDate('booked_date', Carbon::today()->toDateString())
                    ->whereHas('booking', static function ($q): void {
                        $q->whereNotIn('status', ['cancelled', 'cancel_requested']);
                    })
                    ->whereHas('room', function ($q) use ($rid): void {
                        $q->where('room_type_id', $rid)->where('status', '!=', 'maintenance');
                    })
                    ->selectRaw('COUNT(DISTINCT room_id) as aggregate')
                    ->value('aggregate');
            }

            if ($exportFilterId === null) {
                fputcsv($output, ['DOANH THU']);
                fputcsv($output, ['Tổng doanh thu (đã thu)', number_format($totalRevenue, 0, ',', '.').' ₫']);
            } else {
                fputcsv($output, ['DOANH THU (theo dòng đặt loại phòng, đơn đã thanh toán)']);
                fputcsv($output, ['Tổng thành tiền phòng (booking_rooms + đơn cũ)', number_format($totalRevenue, 0, ',', '.').' ₫']);
            }
            fputcsv($output, ['Doanh thu tháng này', number_format($monthlyRevenue, 0, ',', '.').' ₫']);
            fputcsv($output, ['Doanh thu hôm nay', number_format($todayRevenue, 0, ',', '.').' ₫']);
            fputcsv($output, []);

            $occupancyRate = round(($bookedRoomsToday / $sellableRooms) * 100, 1);
            $roomsAvailable = max(0, $totalRooms - $roomsMaintenance - $bookedRoomsToday);

            fputcsv($output, ['TÌNH TRẠNG PHÒNG (HÔM NAY — cùng phạm vi)']);
            fputcsv($output, ['Tổng số phòng', $totalRooms]);
            fputcsv($output, ['Bảo trì', $roomsMaintenance]);
            fputcsv($output, ['Có lịch / đã đặt (đêm nay)', $bookedRoomsToday]);
            fputcsv($output, ['Trống (không bảo trì, không lịch đêm nay)', $roomsAvailable]);
            fputcsv($output, []);

            fputcsv($output, ['TỈ LỆ LẤP PHÒNG (HÔM NAY)']);
            fputcsv($output, ['Phòng kinh doanh (không bảo trì)', $sellableRooms]);
            fputcsv($output, ['Phòng có khách / đặt đêm nay', $bookedRoomsToday]);
            fputcsv($output, ['Tỉ lệ lấp phòng', $occupancyRate.'%']);

            fclose($output);
        };

        return response()->stream($callback, 200, $headers);
    }
}
