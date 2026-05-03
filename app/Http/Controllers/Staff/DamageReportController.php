<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreDamageReportRequest;
use App\Models\Booking;
use App\Models\DamageReport;
use App\Models\Room;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DamageReportController extends Controller
{
    public function index(Request $request)
    {
        $query = DamageReport::query()->with('room');

        if ($request->filled('search')) {
            $term = '%' . $request->search . '%';
            $query->where(function ($q) use ($term) {
                $q->where('damage_type', 'like', $term)
                    ->orWhere('description', 'like', $term);
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $reports = $query->latest()
            ->paginate(10)
            ->withQueryString();

        return view('staff.damage_reports.index', compact('reports'));
    }

    public function create()
    {
        $rooms = Room::query()->with('roomType')->orderBy('room_number')->orderBy('name')->get();
        $damageTypes = DamageReport::getDamageTypes();

        return view('staff.damage_reports.create', compact('rooms', 'damageTypes'));
    }

    public function store(StoreDamageReportRequest $request)
    {
        $validated = $request->validated();

        try {
            DB::beginTransaction();

            $report = DamageReport::create([
                'room_id' => $validated['room_id'],
                'reported_by' => auth()->id(),
                'damage_type' => $validated['damage_type'],
                'description' => $validated['description'],
                'severity' => $validated['severity'],
                'status' => 'reported',
            ]);

            if ($report->isUrgent()) {
                $room = Room::query()->find($validated['room_id']);
                if ($room) {
                    $room->update([
                        'status' => 'maintenance',
                        'maintenance_note' => $validated['description'],
                        'maintenance_since' => now(),
                        'damage_report_id' => $report->id,
                    ]);

                    $currentBooking = $this->getCurrentBooking($room->id);
                    if ($currentBooking) {
                        $report->update([
                            'booking_id' => $currentBooking->id,
                            'requires_room_change' => true,
                        ]);
                    }
                }
            }

            DB::commit();

            return redirect()
                ->route('staff.damage-reports.index')
                ->with('success', 'Tạo báo cáo thành công.');
        } catch (\Throwable $e) {
            DB::rollBack();

            return redirect()
                ->back()
                ->withInput()
                ->with('error', 'Không thể tạo báo cáo: ' . $e->getMessage());
        }
    }

    public function show($id)
    {
        $report = DamageReport::query()
            ->with(['room.roomType', 'reporter'])
            ->findOrFail($id);

        $damageTypes = DamageReport::getDamageTypes();
        $severityLabels = DamageReport::getSeverityLabels();

        return view('staff.damage_reports.show', compact('report', 'damageTypes', 'severityLabels'));
    }

    public function edit($id)
    {
        $report = DamageReport::findOrFail($id);
        $rooms = Room::query()->with('roomType')->orderBy('room_number')->orderBy('name')->get();
        $damageTypes = DamageReport::getDamageTypes();

        return view('staff.damage_reports.edit', compact('report', 'rooms', 'damageTypes'));
    }

    public function update(StoreDamageReportRequest $request, $id)
    {
        $report = DamageReport::findOrFail($id);
        $data = $request->validated();
        $becomingResolved = $data['status'] === 'resolved' && $report->status !== 'resolved';

        try {
            $payload = [
                'room_id' => $data['room_id'],
                'damage_type' => $data['damage_type'],
                'description' => $data['description'],
                'severity' => $data['severity'],
            ];
            if (! $becomingResolved) {
                $payload['status'] = $data['status'];
            }

            $report->update($payload);

            if ($becomingResolved) {
                $report->markAsResolved((int) auth()->id(), null, null);
            }

            return redirect()
                ->route('staff.damage-reports.index')
                ->with('success', 'Cập nhật báo cáo thành công.');
        } catch (\Throwable $e) {
            return redirect()
                ->back()
                ->withInput()
                ->with('error', 'Cập nhật thất bại: ' . $e->getMessage());
        }
    }

    public function destroy($id)
    {
        try {
            $report = DamageReport::findOrFail($id);
            $report->delete();

            return redirect()
                ->back()
                ->with('success', 'Đã xóa báo cáo.');
        } catch (\Throwable $e) {
            return redirect()
                ->back()
                ->with('error', 'Xóa thất bại.');
        }
    }

    private function getCurrentBooking(int $roomId): ?Booking
    {
        return Booking::findActiveOccupancyForRoomToday($roomId);
    }
}
