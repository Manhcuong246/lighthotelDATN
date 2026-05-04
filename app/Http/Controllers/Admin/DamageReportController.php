<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DamageReport;
use App\Models\Room;
use App\Models\Booking;
use App\Models\BookingRoom;
use App\Models\RoomBookedDate;
use App\Services\RoomChangeService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\CarbonPeriod;

class DamageReportController extends Controller
{
    public function __construct()
    {
        $this->middleware('admin');
        $this->middleware('only_admin');
    }

    public function index()
    {
        $reports = DamageReport::with(['room', 'reporter', 'booking'])
            ->orderByRaw("FIELD(severity, 'urgent', 'high', 'medium', 'low')")
            ->orderByDesc('created_at')
            ->paginate(20);

        return view('admin.damage-reports.index', compact('reports'));
    }

    public function create()
    {
        $rooms = Room::with('roomType')
            ->orderBy('room_number')
            ->get();

        $damageTypes = DamageReport::getDamageTypes();

        return view('admin.damage-reports.create', compact('rooms', 'damageTypes'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'room_id' => 'required|exists:rooms,id',
            'damage_type' => 'required|string|max:50',
            'description' => 'required|string|min:10',
            'severity' => 'required|in:low,medium,high,urgent',
            'booking_id' => 'nullable|exists:bookings,id',
        ]);

        DB::beginTransaction();
        try {
            $report = DamageReport::create([
                'room_id' => $validated['room_id'],
                'reported_by' => Auth::id(),
                'booking_id' => $validated['booking_id'] ?? null,
                'damage_type' => $validated['damage_type'],
                'description' => $validated['description'],
                'severity' => $validated['severity'],
                'status' => 'reported',
            ]);

            // If urgent or high severity, mark room as maintenance
            if ($report->isUrgent()) {
                $room = Room::find($validated['room_id']);
                $room->update([
                    'status' => 'maintenance',
                    'maintenance_note' => $validated['description'],
                    'maintenance_since' => now(),
                    'damage_report_id' => $report->id,
                ]);

                // Check if room has current booking
                $currentBooking = $this->getCurrentBooking($room->id);
                if ($currentBooking) {
                    $report->update([
                        'booking_id' => $currentBooking->id,
                        'requires_room_change' => true,
                    ]);
                }
            }

            DB::commit();

            return redirect()->route('admin.damage-reports.index')
                ->with('success', 'Báo cáo hư hỏng đã được tạo thành công!');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors('Có lỗi xảy ra: ' . $e->getMessage())->withInput();
        }
    }

    public function show(DamageReport $damageReport)
    {
        $damageReport->load(['room.roomType', 'reporter', 'resolver', 'booking', 'roomChangeHistories']);

        // Find available alternative rooms if needed
        $alternativeRooms = [];
        if ($damageReport->requires_room_change && !$damageReport->isResolved()) {
            $alternativeRooms = $this->findAlternativeRooms($damageReport);
        }

        return view('admin.damage-reports.show', compact('damageReport', 'alternativeRooms'));
    }

    public function updateStatus(Request $request, DamageReport $damageReport)
    {
        $validated = $request->validate([
            'status' => 'required|in:in_progress,resolved,cancelled',
            'resolution_notes' => 'nullable|string',
            'repair_cost' => 'nullable|numeric|min:0',
        ]);

        DB::beginTransaction();
        try {
            if ($validated['status'] === 'resolved') {
                $damageReport->markAsResolved(
                    Auth::id(),
                    $validated['resolution_notes'] ?? null,
                    $validated['repair_cost'] ?? null
                );
            } else {
                $damageReport->update([
                    'status' => $validated['status'],
                    'resolution_notes' => $validated['resolution_notes'] ?? null,
                ]);
            }

            DB::commit();

            return back()->with('success', 'Cập nhật trạng thái thành công!');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors('Có lỗi xảy ra: ' . $e->getMessage());
        }
    }

    /**
     * Change room for guest (cùng luồng pricing / lịch / HĐ với đổi phòng thông thường).
     */
    public function changeRoom(Request $request, DamageReport $damageReport, RoomChangeService $roomChangeService)
    {
        $validated = $request->validate([
            'new_room_id' => 'required|exists:rooms,id',
        ]);

        if (! $damageReport->booking_id) {
            return back()->withErrors('Không có booking nào cần chuyển phòng');
        }

        $booking = Booking::findOrFail($damageReport->booking_id);
        $newRoomId = (int) $validated['new_room_id'];

        $oldRoomId = (int) ($booking->room_id ?: 0);
        if ($damageReport->room_id) {
            $matchBr = BookingRoom::where('booking_id', $booking->id)
                ->where('room_id', $damageReport->room_id)
                ->exists();
            if ($matchBr) {
                $oldRoomId = (int) $damageReport->room_id;
            }
        }
        if ($oldRoomId <= 0) {
            $fallbackBr = BookingRoom::where('booking_id', $booking->id)
                ->whereNotNull('room_id')
                ->orderByDesc('id')
                ->first();
            $oldRoomId = $fallbackBr ? (int) $fallbackBr->room_id : 0;
        }

        if ($oldRoomId <= 0) {
            return back()->withErrors('Không xác định được phòng hiện tại của khách để đổi.');
        }

        try {
            $roomChangeService->changeRoom(
                $booking,
                $oldRoomId,
                $newRoomId,
                'Phòng bị hư hỏng: '.$damageReport->damage_type,
                Auth::id(),
                $damageReport->id,
                true
            );
        } catch (\Throwable $e) {
            return back()->withErrors('Có lỗi xảy ra: '.$e->getMessage());
        }

        $damageReport->update([
            'requires_room_change' => false,
        ]);

        $newRoom = Room::findOrFail($newRoomId);

        return back()->with(
            'success',
            'Đã chuyển khách sang phòng '.($newRoom->room_number ?: $newRoom->name).' thành công!'
        );
    }

    /**
     * Process refund for damage
     */
    public function processRefund(Request $request, DamageReport $damageReport)
    {
        if (!$damageReport->booking_id) {
            return back()->withErrors('Không có booking nào để hoàn tiền');
        }

        $validated = $request->validate([
            'refund_percentage' => 'required|numeric|min:0|max:100',
            'refund_reason' => 'nullable|string',
        ]);

        DB::beginTransaction();
        try {
            $booking = Booking::findOrFail($damageReport->booking_id);
            $refundAmount = $booking->total_price * ($validated['refund_percentage'] / 100);

            // Update damage report
            $damageReport->update([
                'requires_refund' => true,
                'refund_amount' => $refundAmount,
                'resolution_notes' => ($damageReport->resolution_notes ?? '') . "\nHoàn tiền {$validated['refund_percentage']}%: " . ($validated['refund_reason'] ?? ''),
            ]);

            // Cancel booking if 100% refund
            if ($validated['refund_percentage'] >= 100) {
                $booking->update(['status' => 'cancelled']);
                RoomBookedDate::where('booking_id', $booking->id)->delete();
            }

            DB::commit();

            return back()->with('success', 'Đã xử lý hoàn tiền ' . number_format($refundAmount) . 'đ (' . $validated['refund_percentage'] . '%)');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors('Có lỗi xảy ra: ' . $e->getMessage());
        }
    }

    /**
     * Find current booking for a room
     */
    private function getCurrentBooking(int $roomId): ?Booking
    {
        return Booking::findActiveOccupancyForRoomToday($roomId);
    }

    /**
     * Find alternative rooms for room change
     */
    private function findAlternativeRooms(DamageReport $damageReport): array
    {
        if (!$damageReport->booking_id) {
            return [];
        }

        $booking = Booking::find($damageReport->booking_id);
        if (!$booking) {
            return [];
        }

        $originalRoom = $damageReport->room;

        // Get booked room IDs in the date range
        $period = CarbonPeriod::create($booking->check_in, $booking->check_out->copy()->subDay());
        $dates = collect($period)->map(fn($d) => $d->toDateString())->toArray();

        $bookedRoomIds = RoomBookedDate::whereIn('booked_date', $dates)
            ->pluck('room_id')
            ->unique()
            ->toArray();

        // Find available rooms with same or better type
        return Room::with('roomType')
            ->where('status', 'available')
            ->whereNotIn('id', array_merge($bookedRoomIds, [$originalRoom->id]))
            ->whereHas('roomType', function ($q) use ($originalRoom) {
                $q->where('base_price', '>=', $originalRoom->roomType->base_price ?? 0);
            })
            ->get()
            ->toArray();
    }

    /**
     * Check if room is available
     */
    private function isRoomAvailable(int $roomId, $checkIn, $checkOut): bool
    {
        $period = CarbonPeriod::create($checkIn, $checkOut->copy()->subDay());
        $dates = collect($period)->map(fn($d) => $d->toDateString())->toArray();

        $conflict = RoomBookedDate::where('room_id', $roomId)
            ->whereIn('booked_date', $dates)
            ->exists();

        return !$conflict;
    }
}
