<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\DamageReport;
use App\Http\Requests\StoreDamageReportRequest;
use App\Models\Room;

class DamageReportController extends Controller
{
    // 📄 LIST + SEARCH + FILTER + PAGINATION
    public function index(Request $request)
    {
        $query = DamageReport::query();

        // 🔍 search theo title
        if ($request->search) {
            $query->where('title', 'like', '%' . $request->search . '%');
        }

        // 🔍 filter theo status
        if ($request->status) {
            $query->where('status', $request->status);
        }

        $reports = $query->latest()
            ->paginate(10)
            ->withQueryString(); // giữ query khi chuyển page

        return view('staff.damage_reports.index', compact('reports'));
    }

    // 📄 FORM CREATE
   public function create()
{
    $rooms = Room::all();

    return view('staff.damage_reports.create', compact('rooms'));
}

    // 💾 STORE
public function store(StoreDamageReportRequest $request)
{
    try {
        DamageReport::create([
            'room_id' => $request->room_id,
            'reported_by' => auth()->id(),
            'damage_type' => $request->title, // map từ title
            'description' => $request->description,
            'status' => $request->status,
        ]);

        return redirect()
            ->route('staff.damage-reports.index')
            ->with('success', 'Tạo báo cáo thành công');

    } catch (\Exception $e) {
        dd($e->getMessage());
    }

        
    }

    // 📄 SHOW DETAIL
    public function show($id)
    {
        $report = DamageReport::findOrFail($id);

        return view('staff.damage_reports.show', compact('report'));
    }

    // 📄 FORM EDIT
    public function edit($id)
{
    $report = DamageReport::findOrFail($id);
    $rooms = Room::all(); // 👈 THÊM DÒNG NÀY

    return view('staff.damage_reports.edit', compact('report', 'rooms'));
}

    // 🔄 UPDATE
    public function update(StoreDamageReportRequest $request, $id)
    {
        try {
            $report = DamageReport::findOrFail($id);

            $report->update($request->validated());

            return redirect()
                ->route('staff.damage-reports.index')
                ->with('success', 'Updated successfully');

        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->withInput()
                ->with('error', 'Update failed');
        }
    }

    // ❌ DELETE
    public function destroy($id)
    {
        try {
            $report = DamageReport::findOrFail($id);

            $report->delete();

            return redirect()
                ->back()
                ->with('success', 'Deleted successfully');

        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->with('error', 'Delete failed');
        }
    }
}