<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DamageReport;
use App\Models\Room;
use App\Models\Booking;
use App\Models\RoomChangeHistory;
use App\Models\RoomBookedDate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\CarbonPeriod;

class DamageReportController extends Controller
{
    public function __construct()
    {
        $this->middleware('admin');
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
     * Change room for guest
     */
    public function changeRoom(Request $request, DamageReport $damageReport)
    {
        $validated = $request->validate([
            'new_room_id' => 'required|exists:rooms,id',
        ]);

        if (!$damageReport->booking_id) {
            return back()->withErrors('Không có booking nào cần chuyển phòng');
        }

        DB::beginTransaction();
        try {
            $booking = Booking::findOrFail($damageReport->booking_id);
            $oldRoomId = $booking->room_id;
            $newRoomId = $validated['new_room_id'];
            $newRoom = Room::findOrFail($newRoomId);

            // Check new room availability
            if (!$this->isRoomAvailable($newRoomId, $booking->check_in, $booking->check_out)) {
                return back()->withErrors('Phòng mới không còn trống trong khoảng thời gian này');
            }

            // Delete old booked dates
            RoomBookedDate::where('booking_id', $booking->id)->delete();

            // Create new booked dates
            $period = CarbonPeriod::create($booking->check_in, $booking->check_out->copy()->subDay());
            foreach ($period as $date) {
                RoomBookedDate::create([
                    'room_id' => $newRoomId,
                    'booked_date' => $date->toDateString(),
                    'booking_id' => $booking->id,
                ]);
            }

            // Update booking
            $booking->update([
                'room_id' => $newRoomId,
            ]);

            // Create history record
            RoomChangeHistory::create([
                'booking_id' => $booking->id,
                'from_room_id' => $oldRoomId,
                'to_room_id' => $newRoomId,
                'damage_report_id' => $damageReport->id,
                'reason' => 'Phòng bị hư hỏng: ' . $damageReport->damage_type,
                'changed_by' => Auth::id(),
                'changed_at' => now(),
            ]);

            // Update damage report
            $damageReport->update([
                'requires_room_change' => false,
            ]);

            DB::commit();

            return back()->with('success', 'Đã chuyển khách sang phòng ' . $newRoom->room_number . ' thành công!');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors('Có lỗi xảy ra: ' . $e->getMessage());
        }
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
        return Booking::where('room_id', $roomId)
            ->where('check_in', '<=', now())
            ->where('check_out', '>=', now())
            ->whereIn('status', ['confirmed', 'checked_in'])
            ->first();
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
