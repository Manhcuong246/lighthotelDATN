<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\BookingLog;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class PaymentAdminController extends Controller
{
    public function __construct()
    {
        $this->middleware('admin');
    }

    public function index(Request $request)
    {
        $query = Payment::with([
            'booking.user',
            'booking.room',
            'booking.rooms',
        ])->latest();

        if ($request->filled('q')) {
            $q = $request->q;
            $query->where(function ($qry) use ($q) {
                $qry->where('transaction_id', 'like', "%{$q}%")
                    ->orWhereHas('booking', function ($b) use ($q) {
                        $b->where('id', 'like', "%{$q}%")
                            ->orWhereHas('user', fn ($u) => $u->where('full_name', 'like', "%{$q}%")->orWhere('email', 'like', "%{$q}%"))
                            ->orWhereHas('rooms', fn ($r) => $r->where('name', 'like', "%{$q}%"))
                            ->orWhereHas('room', fn ($r) => $r->where('name', 'like', "%{$q}%"));
                    });
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('refund_status')) {
            $query->where('refund_status', $request->refund_status);
        }

        if ($request->filled('booking_status')) {
            $query->whereHas('booking', fn ($b) => $b->where('status', $request->booking_status));
        }

        $payments = $query->paginate(10)->withQueryString();

        $pendingCancellationCount = Booking::where('status', Booking::STATUS_CANCELLATION_PENDING)->count();

        return view('admin.payments.index', compact('payments', 'pendingCancellationCount'));
    }

    public function show(Payment $payment)
    {
        $payment->load(['booking.user', 'booking.room', 'booking.rooms']);
        return view('admin.payments.show', compact('payment'));
    }

    public function uploadRefundProof(Request $request, Payment $payment)
    {
        $validated = $request->validate([
            'refund_proof' => 'required|image|mimes:jpeg,png,jpg,gif,webp|max:5120',
            'refund_admin_note' => 'nullable|string|max:2000',
        ]);

        if ($payment->refund_status !== Payment::REFUND_PENDING_ADMIN) {
            return back()->withErrors('Chỉ gửi ảnh chứng từ khi đơn đang ở bước chờ khách sạn hoàn tiền (đã có thông tin TK khách).');
        }

        $path = $request->file('refund_proof')->store('refunds/proof', 'public');
        if ($payment->refund_proof_path) {
            Storage::disk('public')->delete($payment->refund_proof_path);
        }

        DB::transaction(function () use ($payment, $validated, $path) {
            $payment->update([
                'refund_proof_path' => $path,
                'refund_admin_note' => $validated['refund_admin_note'] ?? null,
                'refund_status' => Payment::REFUND_COMPLETED,
                'refund_completed_at' => now(),
            ]);

            $booking = $payment->booking;
            if ($booking && $booking->status === 'cancelled') {
                $old = $booking->status;
                $booking->update(['status' => Booking::STATUS_REFUNDED]);
                BookingLog::create([
                    'booking_id' => $booking->id,
                    'actor_user_id' => Auth::id(),
                    'old_status' => $old,
                    'new_status' => Booking::STATUS_REFUNDED,
                    'note' => '[refund_proof_uploaded]',
                    'changed_at' => now(),
                ]);
            }
        });

        return back()->with('success', 'Đã lưu chứng từ hoàn tiền. Khách hàng sẽ xem được trên trang chi tiết đặt phòng.');
    }
}
