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
use Illuminate\Support\Facades\Cache;
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
        if ($request->filled('room_type_id') || $request->filled('focus_room_type_id')) {
            return redirect()->route('admin.dashboard');
        }

        return $this->renderDashboardAllTypes($request);
    }

    /**
     * @return \Illuminate\Contracts\View\View
     */
    private function renderDashboardAllTypes(Request $request)
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
        $bookedRoomsToday = $this->countOccupiedRoomSlotsOnDate($today, null);
        $occupancyRate = round(($bookedRoomsToday / $sellableRooms) * 100, 1);

        // Tỉ lệ lấp phòng tháng (cache ngắn — tránh lặp query theo từng ngày)
        $monthlyOccupancyRate = $this->computeMonthlyOccupancyRateCached($sellableRooms, null);

        // Dữ liệu biểu đồ doanh thu 7 ngày gần nhất
        $revenueChart = $this->getRevenueChartData(null);

        // Dữ liệu biểu đồ tỉ lệ lấp phòng 7 ngày gần nhất
        $occupancyChart = $this->getOccupancyChartData($sellableRooms, null);

        $topRoomTypesByRevenue = collect();

        // Tình trạng phòng theo lịch đêm nay (không chỉ cột status trên bảng rooms)
        $roomsBooked = $bookedRoomsToday;
        $roomsAvailable = max(0, $totalRooms - $roomsMaintenance - $roomsBooked);

        $dashKpis = $this->buildDashboardOperationalKpis(null, $monthlyRevenue, $sellableRooms);
        $inventoryStack = $this->roomInventoryStack($totalRooms, $roomsMaintenance, $roomsBooked, $roomsAvailable);

        $roomTypesForDetail = RoomType::query()->orderBy('name')->get(['id', 'name']);

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
            'dashKpis',
            'inventoryStack',
            'roomTypesForDetail'
        ));
    }

    /**
     * Chi tiết một loại phòng: thông số danh mục + KPI + biểu đồ doanh thu paid (theo ngày ghi nhận TT).
     */
    public function roomTypeDetail(Request $request)
    {
        if (! auth()->user()->isAdmin()) {
            abort(403, 'Chỉ quản trị viên mới xem dữ liệu này.');
        }

        $validated = $request->validate([
            'room_type_id' => 'required|integer|exists:room_types,id',
            'start' => 'nullable|date',
            'end' => 'nullable|date',
        ]);

        $roomTypeId = (int) $validated['room_type_id'];
        $roomType = RoomType::query()->find($roomTypeId);
        if (! $roomType) {
            return response()->json(['error' => 'Không tìm thấy loại phòng.'], 404);
        }

        [$start, $end] = $this->parseDashboardChartDateRange(
            $request,
            Carbon::now()->subDays(6)->startOfDay(),
            Carbon::now()->endOfDay()
        );

        $roomsBase = Room::query()->where('room_type_id', $roomTypeId);
        $roomsTotal = (int) $roomsBase->clone()->count();
        $roomsMaintenance = (int) $roomsBase->clone()->where('status', 'maintenance')->count();
        $sellable = max(1, $roomsTotal - $roomsMaintenance);

        $today = Carbon::today()->toDateString();
        $occupancyToday = round(($this->countOccupiedRoomSlotsOnDate($today, $roomTypeId) / $sellable) * 100, 1);
        $occupancyMonth = $this->computeMonthlyOccupancyRateCached($sellable, $roomTypeId);

        $revToday = $this->sumRevenueForRoomTypeOnDate($roomTypeId, $today);
        $revMtd = 0.0;
        for (
            $d = Carbon::now()->copy()->startOfMonth();
            $d->lte(Carbon::now());
            $d->addDay()
        ) {
            $revMtd += $this->sumRevenueForRoomTypeOnDate($roomTypeId, $d->toDateString());
        }

        $nightsMtd = $this->sumPaidRoomNightsInCurrentMonth($roomTypeId);
        $adrMtd = $nightsMtd > 0 ? (int) round($revMtd / $nightsMtd) : null;

        $chart = $this->getRevenueChartDataForRange($start, $end, $roomTypeId);

        return response()->json([
            'room_type' => [
                'id' => $roomType->id,
                'name' => $roomType->name,
                'price' => (float) ($roomType->price ?? 0),
                'capacity' => (int) ($roomType->capacity ?? 0),
                'standard_capacity' => $roomType->standard_capacity !== null ? (int) $roomType->standard_capacity : null,
                'adult_capacity' => (int) ($roomType->adult_capacity ?? 0),
                'child_capacity' => (int) ($roomType->child_capacity ?? 0),
                'beds' => $roomType->beds,
                'baths' => $roomType->baths,
                'rooms_total' => $roomsTotal,
                'rooms_maintenance' => $roomsMaintenance,
                'rooms_sellable' => $sellable,
                'edit_url' => route('admin.roomtypes.edit', $roomType->id),
            ],
            'kpis' => [
                'occupancy_today_pct' => $occupancyToday,
                'occupancy_month_pct' => $occupancyMonth,
                'revenue_today' => $revToday,
                'revenue_mtd' => $revMtd,
                'adr_mtd' => $adrMtd,
                'room_nights_paid_mtd' => round($nightsMtd, 1),
            ],
            'range' => [
                'start' => $start->toDateString(),
                'end' => $end->toDateString(),
            ],
            'revenue_chart' => $chart,
        ]);
    }

    /**
     * Tỉ lệ lấp phòng cả tháng — cache 3 phút (trước đây lặp ~30 query/ngày trong tháng).
     */
    private function computeMonthlyOccupancyRateCached(int $sellableRooms, ?int $roomTypeId): float
    {
        $monthKey = Carbon::now()->format('Y-m');

        return (float) Cache::remember(
            'admin_dash_mocc_'.$monthKey.'_'.($roomTypeId ?? 'all').'_sr'.$sellableRooms,
            180,
            function () use ($sellableRooms, $roomTypeId): float {
                $startOfMonth = Carbon::now()->startOfMonth();
                $endOfMonth = Carbon::now()->endOfMonth();
                $daysInMonth = $startOfMonth->diffInDays($endOfMonth) + 1;
                $totalRoomDays = max(1, $sellableRooms * $daysInMonth);
                $bookedRoomDays = 0;
                for ($d = $startOfMonth->copy(); $d->lte($endOfMonth); $d->addDay()) {
                    $bookedRoomDays += $this->countOccupiedRoomSlotsOnDate($d->toDateString(), $roomTypeId);
                }

                return round(($bookedRoomDays / $totalRoomDays) * 100, 1);
            }
        );
    }

    /**
     * @return array{
     *   revpar_mtd: int,
     *   adr_month: int|null,
     *   room_nights_sold_month: float,
     *   arrivals_today: int,
     *   stays_in_house: int
     * }
     */
    private function buildDashboardOperationalKpis(
        ?int $roomTypeId,
        float $monthlyRevenue,
        int $sellableRooms
    ): array {
        $nights = $this->sumPaidRoomNightsInCurrentMonth($roomTypeId);
        $adr = $nights > 0 ? (int) round($monthlyRevenue / $nights) : null;

        $dayOfMonth = max(1, (int) Carbon::now()->day);
        $availNightsMtd = max(1, $sellableRooms * $dayOfMonth);
        $revpar = (int) round($monthlyRevenue / $availNightsMtd);

        return [
            'revpar_mtd' => $revpar,
            'adr_month' => $adr,
            'room_nights_sold_month' => $nights,
            'arrivals_today' => $this->countArrivalsToday($roomTypeId),
            'stays_in_house' => $this->countStaysInHouse($roomTypeId),
        ];
    }

    /**
     * @return array{
     *   total: int,
     *   pct_available: float,
     *   pct_booked: float,
     *   pct_maintenance: float,
     *   rooms_available: int,
     *   rooms_booked: int,
     *   rooms_maintenance: int
     * }
     */
    private function roomInventoryStack(
        int $totalRooms,
        int $roomsMaintenance,
        int $roomsBooked,
        int $roomsAvailable
    ): array {
        if ($totalRooms < 1) {
            return [
                'total' => 0,
                'pct_available' => 0.0,
                'pct_booked' => 0.0,
                'pct_maintenance' => 0.0,
                'rooms_available' => 0,
                'rooms_booked' => 0,
                'rooms_maintenance' => 0,
            ];
        }

        return [
            'total' => $totalRooms,
            'pct_available' => round($roomsAvailable / $totalRooms * 100, 1),
            'pct_booked' => round($roomsBooked / $totalRooms * 100, 1),
            'pct_maintenance' => round($roomsMaintenance / $totalRooms * 100, 1),
            'rooms_available' => $roomsAvailable,
            'rooms_booked' => $roomsBooked,
            'rooms_maintenance' => $roomsMaintenance,
        ];
    }

    /** Đêm phòng (booking_rooms.nights + đơn legacy) có ghi nhận thanh toán trong tháng hiện tại. */
    private function sumPaidRoomNightsInCurrentMonth(?int $roomTypeId): float
    {
        $y = (int) Carbon::now()->year;
        $mo = (int) Carbon::now()->month;
        $exclude = ['cancelled', 'cancel_requested'];

        $q = BookingRoom::query()->whereHas('booking', function ($b) use ($y, $mo, $exclude) {
            $b->whereNotIn('status', $exclude)
                ->whereHas('payments', function ($p) use ($y, $mo) {
                    $p->where('status', 'paid')
                        ->whereRaw('YEAR(COALESCE(payments.paid_at, payments.created_at)) = ?', [$y])
                        ->whereRaw('MONTH(COALESCE(payments.paid_at, payments.created_at)) = ?', [$mo]);
                });
        });

        if ($roomTypeId !== null) {
            $roomIds = Room::where('room_type_id', $roomTypeId)->pluck('id');
            $q->where(function ($q2) use ($roomTypeId, $roomIds) {
                $q2->where('booking_rooms.room_type_id', $roomTypeId)
                    ->orWhereIn('booking_rooms.room_id', $roomIds);
            });
        }

        $fromLines = (float) $q->clone()->sum('nights');

        $legacyQ = Booking::query()
            ->whereDoesntHave('bookingRooms')
            ->whereNotIn('status', $exclude)
            ->whereNotNull('room_id')
            ->whereHas('payments', function ($p) use ($y, $mo) {
                $p->where('status', 'paid')
                    ->whereRaw('YEAR(COALESCE(payments.paid_at, payments.created_at)) = ?', [$y])
                    ->whereRaw('MONTH(COALESCE(payments.paid_at, payments.created_at)) = ?', [$mo]);
            });

        if ($roomTypeId !== null) {
            $legacyQ->whereHas('room', fn ($r) => $r->where('room_type_id', $roomTypeId));
        }

        $legacyNights = (float) $legacyQ->get()->sum(function (Booking $b) {
            return (float) max(0, Carbon::parse($b->check_in)->diffInDays(Carbon::parse($b->check_out)));
        });

        return $fromLines + $legacyNights;
    }

    private function countArrivalsToday(?int $roomTypeId): int
    {
        if ($roomTypeId === null) {
            return (int) Booking::query()
                ->whereDate('check_in', Carbon::today()->toDateString())
                ->whereNotIn('status', ['cancelled', 'cancel_requested'])
                ->count();
        }

        $roomIds = Room::where('room_type_id', $roomTypeId)->pluck('id');

        return (int) Booking::query()
            ->whereDate('check_in', Carbon::today()->toDateString())
            ->whereNotIn('status', ['cancelled', 'cancel_requested'])
            ->where(function ($q) use ($roomTypeId, $roomIds) {
                $q->whereHas('bookingRooms', fn ($br) => $br->where('room_type_id', $roomTypeId))
                    ->orWhereHas('bookingRooms', fn ($br) => $br->whereIn('room_id', $roomIds))
                    ->orWhere(fn ($q2) => $q2->whereDoesntHave('bookingRooms')->whereIn('room_id', $roomIds));
            })
            ->count();
    }

    private function countStaysInHouse(?int $roomTypeId): int
    {
        $today = Carbon::today()->toDateString();
        $active = ['pending', 'confirmed', 'checked_in'];

        if ($roomTypeId === null) {
            return (int) Booking::query()
                ->whereIn('status', $active)
                ->whereDate('check_in', '<=', $today)
                ->whereDate('check_out', '>', $today)
                ->count();
        }

        $roomIds = Room::where('room_type_id', $roomTypeId)->pluck('id');

        return (int) Booking::query()
            ->whereIn('status', $active)
            ->whereDate('check_in', '<=', $today)
            ->whereDate('check_out', '>', $today)
            ->where(function ($q) use ($roomTypeId, $roomIds) {
                $q->whereHas('bookingRooms', fn ($br) => $br->where('room_type_id', $roomTypeId))
                    ->orWhereHas('bookingRooms', fn ($br) => $br->whereIn('room_id', $roomIds))
                    ->orWhere(fn ($q2) => $q2->whereDoesntHave('bookingRooms')->whereIn('room_id', $roomIds));
            })
            ->count();
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

        [$start, $end] = $this->parseDashboardChartDateRange(
            $request,
            Carbon::now()->subDays(6)->startOfDay(),
            Carbon::now()->endOfDay()
        );

        return response()->json($this->getRevenueChartDataForRange($start, $end, null));
    }

    /**
     * Top 5 loại phòng theo doanh thu (paid). Không gửi tham số start → tính từ đầu dữ liệu đến ngày end.
     */
    public function roomRevenueRanking(Request $request)
    {
        if (! auth()->user()->isAdmin()) {
            abort(403, 'Chỉ quản trị viên mới xem dữ liệu xếp hạng doanh thu.');
        }

        [$start, $end, $allTime] = $this->parseRoomTypeRankingPaymentRange($request);

        $rows = $this->getTopRoomTypesByRevenueInRange($start, $end, 5);
        $labels = $rows->pluck('name')->all();
        $data = $rows->pluck('total_revenue')->map(static fn ($v): float => round((float) $v, 0))->all();

        return response()->json([
            'all_time' => $allTime,
            'start' => $allTime ? null : $start->toDateString(),
            'end' => $end->toDateString(),
            'labels' => $labels,
            'data' => $data,
            'rows' => $rows->values(),
        ]);
    }

    /**
     * @return array{0: \Carbon\Carbon, 1: \Carbon\Carbon, 2: bool} start, end, allTime (không giới hạn ngày bắt đầu)
     */
    private function parseRoomTypeRankingPaymentRange(Request $request): array
    {
        $end = $request->filled('end')
            ? Carbon::parse($request->input('end'))->endOfDay()
            : Carbon::now()->endOfDay();

        $allTime = ! $request->filled('start');
        $start = $allTime
            ? Carbon::parse('1970-01-01')->startOfDay()
            : Carbon::parse($request->input('start'))->startOfDay();

        if ($start->greaterThan($end)) {
            [$start, $end] = [$end->copy()->startOfDay(), $start->copy()->endOfDay()];
            $allTime = false;
        }

        return [$start, $end, $allTime];
    }

    /** @return array{0: \Carbon\Carbon, 1: \Carbon\Carbon} */
    private function parseDashboardChartDateRange(Request $request, ?Carbon $defaultStart = null, ?Carbon $defaultEnd = null): array
    {
        $end = $request->filled('end')
            ? Carbon::parse($request->input('end'))
            : ($defaultEnd ?? Carbon::now())->copy();
        $start = $request->filled('start')
            ? Carbon::parse($request->input('start'))
            : ($defaultStart ?? Carbon::now()->subDays(29))->copy();

        $start = $start->copy()->startOfDay();
        $end = $end->copy()->endOfDay();

        if ($start->greaterThan($end)) {
            [$start, $end] = [$end->copy()->startOfDay(), $start->copy()->endOfDay()];
        }

        if ($start->diffInDays($end) > 366) {
            $start = $end->copy()->subDays(366)->startOfDay();
        }

        return [$start, $end];
    }

    /**
     * @return Collection<int, object{id: int, name: string, total_revenue: float}>
     */
    private function getTopRoomTypesByRevenueInRange(Carbon $start, Carbon $end, int $limit): Collection
    {
        $exclude = ['cancelled', 'cancel_requested'];
        $startDate = $start->toDateString();
        $endDate = $end->toDateString();
        $coalesce = 'COALESCE(booking_rooms.room_type_id, rooms.room_type_id)';

        $fromBookingRooms = BookingRoom::query()
            ->selectRaw($coalesce.' as room_type_id, SUM(booking_rooms.subtotal) as total_revenue')
            ->join('bookings', 'bookings.id', '=', 'booking_rooms.booking_id')
            ->leftJoin('rooms', 'rooms.id', '=', 'booking_rooms.room_id')
            ->whereHas('booking', static function ($q) use ($exclude, $startDate, $endDate): void {
                $q->whereNotIn('status', $exclude)
                    ->whereHas('payments', static function ($p) use ($startDate, $endDate): void {
                        $p->where('status', 'paid')
                            ->whereRaw('DATE(COALESCE(payments.paid_at, payments.created_at)) >= ?', [$startDate])
                            ->whereRaw('DATE(COALESCE(payments.paid_at, payments.created_at)) <= ?', [$endDate]);
                    });
            })
            ->whereRaw($coalesce.' IS NOT NULL')
            ->groupByRaw($coalesce)
            ->get();

        $fromLegacy = Booking::query()
            ->selectRaw('rooms.room_type_id as room_type_id, SUM(bookings.total_price) as total_revenue')
            ->join('rooms', 'rooms.id', '=', 'bookings.room_id')
            ->whereNotNull('bookings.room_id')
            ->whereNotIn('bookings.status', $exclude)
            ->whereDoesntHave('bookingRooms')
            ->whereHas('payments', static function ($p) use ($startDate, $endDate): void {
                $p->where('status', 'paid')
                    ->whereRaw('DATE(COALESCE(payments.paid_at, payments.created_at)) >= ?', [$startDate])
                    ->whereRaw('DATE(COALESCE(payments.paid_at, payments.created_at)) <= ?', [$endDate]);
            })
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
            $bookedRooms = $this->countOccupiedRoomSlotsOnDate($date->toDateString(), $roomTypeId);
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
     * Tổng slot phòng có lịch trong đêm $date: khóa lịch theo phòng + dòng đặt chưa gán phòng + đơn legacy (chỉ bookings.room_id).
     * (Trước đây chỉ đếm room_booked_dates — thường chỉ có sau khi đã gán phòng cụ thể → thiếu đơn chưa gán phòng.)
     */
    private function countOccupiedRoomSlotsOnDate(string $date, ?int $roomTypeId = null): int
    {
        return $this->countDistinctRoomsBookedOnDate($date, $roomTypeId)
            + $this->countUnassignedBookingRoomLinesOverlappingDate($date, $roomTypeId)
            + $this->countLegacySingleRoomBookingsOverlappingDate($date, $roomTypeId);
    }

    private function countUnassignedBookingRoomLinesOverlappingDate(string $date, ?int $roomTypeId = null): int
    {
        return (int) BookingRoom::query()
            ->whereNull('room_id')
            ->when($roomTypeId !== null, static fn ($q) => $q->where('booking_rooms.room_type_id', $roomTypeId))
            ->whereHas('booking', static function ($q) use ($date): void {
                $q->whereNotIn('status', ['cancelled', 'cancel_requested'])
                    ->whereDate('check_in', '<=', $date)
                    ->whereDate('check_out', '>', $date);
            })
            ->count();
    }

    private function countLegacySingleRoomBookingsOverlappingDate(string $date, ?int $roomTypeId = null): int
    {
        return (int) Booking::query()
            ->whereDoesntHave('bookingRooms')
            ->whereNotNull('room_id')
            ->whereNotIn('status', ['cancelled', 'cancel_requested'])
            ->whereDate('check_in', '<=', $date)
            ->whereDate('check_out', '>', $date)
            ->whereHas('room', static function ($q): void {
                $q->where('status', '!=', 'maintenance');
            })
            ->when($roomTypeId !== null, static fn ($q) => $q->whereHas('room', static fn ($r) => $r->where('room_type_id', $roomTypeId)))
            ->count();
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

    /**
     * Thêm % đóng góp so với tổng các dòng trong danh sách (để so sánh tương đối trên biểu đồ).
     *
     * @param  Collection<int, object{id: int, name: string, total_revenue: float}>  $rows
     * @return Collection<int, object{id: int, name: string, total_revenue: float, pct_of_total: float}>
     */
    private function enrichTopRoomTypesWithPercentShare(Collection $rows): Collection
    {
        $sum = (float) $rows->sum('total_revenue');
        if ($sum <= 0) {
            return $rows->map(static function ($r) {
                $a = (array) $r;
                $a['pct_of_total'] = 0.0;

                return (object) $a;
            });
        }

        return $rows->map(static function ($r) use ($sum) {
            $a = (array) $r;
            $a['pct_of_total'] = round(((float) $r->total_revenue / $sum) * 100, 1);

            return (object) $a;
        });
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

        $today = Carbon::now()->format('Y-m-d');
        $filename = 'bao-cao-thong-ke-'.$today.'.csv';

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename='.$filename,
        ];

        $callback = function (): void {
            $output = fopen('php://output', 'w');
            fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

            fputcsv($output, ['Thống kê - Toàn bộ khách sạn - '.Carbon::now()->format('d/m/Y')]);
            fputcsv($output, []);

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
            $bookedRoomsToday = $this->countOccupiedRoomSlotsOnDate(Carbon::today()->toDateString(), null);

            fputcsv($output, ['DOANH THU']);
            fputcsv($output, ['Tổng doanh thu (đã thu)', number_format($totalRevenue, 0, ',', '.').' ₫']);
            fputcsv($output, ['Doanh thu tháng này', number_format($monthlyRevenue, 0, ',', '.').' ₫']);
            fputcsv($output, ['Doanh thu hôm nay', number_format($todayRevenue, 0, ',', '.').' ₫']);
            fputcsv($output, []);

            $occupancyRate = round(($bookedRoomsToday / $sellableRooms) * 100, 1);
            $roomsAvailable = max(0, $totalRooms - $roomsMaintenance - $bookedRoomsToday);

            fputcsv($output, ['TÌNH TRẠNG PHÒNG (HÔM NAY — toàn khách sạn)']);
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
