<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Service;

class ServiceController extends Controller
{
    /**
     * Danh sách dịch vụ
     */
    public function index()
    {
        $services = Service::orderBy('id', 'desc')->paginate(10);

        return view(
            'admin.services.index',
            compact('services')
        );
    }

    /**
     * Form thêm
     */
    public function create()
    {
        return view('admin.services.create');
    }

    /**
     * Lưu
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'price' => 'required|numeric|min:0',
            'description' => 'nullable|string'
        ]);

        Service::create([
            'name' => $request->name,
            'price' => $request->price,
            'description' => $request->description
        ]);

        return redirect()
            ->route('admin.roomtypes.services.index')
            ->with('success', 'Thêm dịch vụ thành công!');
    }

    /**
     * Form sửa
     */
    public function edit($id)
    {
        $service = Service::findOrFail($id);

        return view(
            'admin.services.edit',
            compact('service')
        );
    }

    /**
     * Update
     */
    public function update(Request $request, $id)
    {
        $service = Service::findOrFail($id);

        $request->validate([
            'name' => 'required|string|max:255',
            'price' => 'required|numeric|min:0',
            'description' => 'nullable|string'
        ]);

        $service->update([
            'name' => $request->name,
            'price' => $request->price,
            'description' => $request->description
        ]);

        return redirect()
            ->route('admin.roomtypes.services.index')
            ->with('success', 'Cập nhật dịch vụ thành công!');
    }

    /**
     * Delete
     */
    public function destroy($id)
    {
        $service = Service::findOrFail($id);

        $service->delete();

        return redirect()
            ->route('admin.roomtypes.services.index')
            ->with('success', 'Xóa dịch vụ thành công!');
    }
}