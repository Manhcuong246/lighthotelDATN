<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Room;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;

class StaffController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'staff']);
    }

    /**
     * Đơn còn hiển thị trên dashboard vận hành (trừ hủy / hoàn tất).
     */
    private function activeBookingQuery(): Builder
    {
        return Booking::query()->whereNotIn('status', ['cancelled', 'cancel_requested', 'completed']);
    }

    /**
     * Lọc đặt có ngày nhận phòng theo lịch = hôm nay (check_in hoặc check_in_date).
     */
    private function applyScheduledCheckInToday(Builder $query, Carbon $today): Builder
    {
        return $query->where(function ($q) use ($today) {
            $q->whereDate('check_in', $today)
                ->orWhere(function ($q2) use ($today) {
                    $q2->whereNotNull('check_in_date')
                        ->whereDate('check_in_date', $today);
                });
        });
    }

    /**
     * Lọc đặt có ngày trả phòng theo lịch = hôm nay (check_out hoặc check_out_date).
     */
    private function applyScheduledCheckOutToday(Builder $query, Carbon $today): Builder
    {
        return $query->where(function ($q) use ($today) {
            $q->whereDate('check_out', $today)
                ->orWhere(function ($q2) use ($today) {
                    $q2->whereNotNull('check_out_date')
                        ->whereDate('check_out_date', $today);
                });
        });
    }

    public function dashboard()
    {
        $today = Carbon::today();

        $checkInToday = $this->applyScheduledCheckInToday($this->activeBookingQuery(), $today)->count();

        $checkOutToday = $this->applyScheduledCheckOutToday($this->activeBookingQuery(), $today)->count();

        // Đang lưu trú: đã ghi nhận nhận phòng, chưa trả (khớp adminStayPhase "checked_in")
        $guestsStaying = Booking::whereNotNull('actual_check_in')
            ->whereNull('actual_check_out')
            ->whereNotIn('status', ['cancelled', 'cancel_requested', 'completed'])
            ->count();

        $checkInTodayQuery = fn () => $this->applyScheduledCheckInToday($this->activeBookingQuery(), $today);
        $checkInChart = [
            'labels' => ['Đã nhận phòng', 'Chờ nhận'],
            'data' => [
                $checkInTodayQuery()->whereNotNull('actual_check_in')->count(),
                $checkInTodayQuery()->whereNull('actual_check_in')->count(),
            ],
        ];

        $checkOutTodayQuery = fn () => $this->applyScheduledCheckOutToday($this->activeBookingQuery(), $today);
        $checkoutChart = [
            'labels' => ['Đã trả phòng', 'Đang ở (cần trả)', 'Chưa nhận phòng'],
            'data' => [
                $checkOutTodayQuery()->whereNotNull('actual_check_out')->count(),
                $checkOutTodayQuery()->whereNull('actual_check_out')->whereNotNull('actual_check_in')->count(),
                $checkOutTodayQuery()->whereNull('actual_check_in')->count(),
            ],
        ];

        $weekLabels = [];
        $weekCheckInCounts = [];
        $weekCheckOutCounts = [];
        for ($i = 6; $i >= 0; $i--) {
            $d = $today->copy()->subDays($i);
            $weekLabels[] = $d->format('d/m');
            $weekCheckInCounts[] = $this->applyScheduledCheckInToday($this->activeBookingQuery(), $d)->count();
            $weekCheckOutCounts[] = $this->applyScheduledCheckOutToday($this->activeBookingQuery(), $d)->count();
        }

        $roomsAvailable = Room::where('status', 'available')->count();
        $roomsMaintenance = Room::where('status', 'maintenance')->count();

        return view('staff.dashboard', compact(
            'checkInToday',
            'checkOutToday',
            'guestsStaying',
            'roomsAvailable',
            'roomsMaintenance',
            'checkInChart',
            'checkoutChart',
            'weekLabels',
            'weekCheckInCounts',
            'weekCheckOutCounts'
        ));
    }
}
