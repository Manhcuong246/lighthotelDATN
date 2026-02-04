<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use Illuminate\Http\Request;

class PaymentAdminController extends Controller
{
    public function __construct()
    {
        $this->middleware('admin');
    }

    public function index()
    {
        $payments = Payment::with(['booking.user', 'booking.room'])->latest()->paginate(15);
        return view('admin.payments.index', compact('payments'));
    }

    public function show(Payment $payment)
    {
        return view('admin.payments.show', compact('payment'));
    }

    public function edit(Payment $payment)
    {
        return view('admin.payments.edit', compact('payment'));
    }

    public function update(Request $request, Payment $payment)
    {
        $validated = $request->validate([
            'status' => 'required|in:pending,paid,failed',
            'amount' => 'sometimes|numeric|min:0',
            'method' => 'sometimes|string|max:50',
        ]);

        $payment->update($validated);

        return redirect()->route('admin.payments.index')->with('success', 'Cập nhật thanh toán thành công.');
    }

    public function destroy(Payment $payment)
    {
        if (!auth()->user()->isAdmin()) {
            abort(403, 'Chỉ quản trị viên mới được xóa thanh toán.');
        }
        $payment->delete();

        return redirect()->route('admin.payments.index')->with('success', 'Xóa thanh toán thành công.');
    }
}