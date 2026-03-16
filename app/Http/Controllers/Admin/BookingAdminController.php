<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\RoomBookedDate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\CarbonPeriod;

class BookingAdminController extends Controller
{
    public function __construct()
    {
        $this->middleware('admin');
    }

    public function index()
    {
        $bookings = Booking::with(['user', 'room'])->latest()->paginate(15);
        return view('admin.bookings.index', compact('bookings'));
    }

    public function show(Booking $booking)
    {
        return view('admin.bookings.show', compact('booking'));
    }

    public function edit(Booking $booking)
    {
        return view('admin.bookings.edit', compact('booking'));
    }

    public function update(Request $request, Booking $booking)
    {
        $validated = $request->validate([
            'check_in' => 'required|date',
            'check_out' => 'required|date|after:check_in',
            'guests' => 'required|integer|min:1|max:' . $booking->room->max_guests,
            'total_price' => 'required|numeric|min:0',
            'status' => 'required|in:pending,confirmed,cancelled,completed',
        ]);

        $old_status = $booking->status;

        DB::beginTransaction();
        try {
            // If check-in or check-out dates changed, update the RoomBookedDate records
            $newCheckIn = new \Carbon\Carbon($validated['check_in']);
            $newCheckOut = new \Carbon\Carbon($validated['check_out']);

            if ($booking->check_in != $newCheckIn->format('Y-m-d') || $booking->check_out != $newCheckOut->format('Y-m-d')) {
                // Delete old booked dates
                RoomBookedDate::where('booking_id', $booking->id)->delete();

                // Create new booked dates
                $period = CarbonPeriod::create($newCheckIn, $newCheckOut->copy()->subDay());
                foreach ($period as $date) {
                    RoomBookedDate::create([
                        'room_id' => $booking->room_id,
                        'booked_date' => $date->toDateString(),
                        'booking_id' => $booking->id,
                    ]);
                }
            }

            $booking->update($validated);

            // Log status change if status was updated
            if ($old_status !== $booking->status) {
                \App\Models\BookingLog::create([
                    'booking_id' => $booking->id,
                    'old_status' => $old_status,
                    'new_status' => $booking->status,
                    'changed_at' => now(),
                ]);
            }

            DB::commit();

            return redirect()->route('admin.bookings.show', $booking)->with('success', 'Cập nhật đơn đặt phòng thành công.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors('Có lỗi xảy ra, vui lòng thử lại sau.')->withInput();
        }
    }

    public function destroy(Booking $booking)
    {
        $user = Auth::user();
        if (!$user || !$user->isAdmin()) {
            abort(403, 'Chỉ quản trị viên mới được xóa đơn đặt phòng.');
        }
        DB::beginTransaction();
        try {
            // Remove related booked date records first to satisfy FK constraints
            RoomBookedDate::where('booking_id', $booking->id)->delete();

            $booking->delete();

            DB::commit();
            return redirect()->route('admin.bookings.index')->with('success', 'Xóa đơn đặt phòng thành công.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors('Có lỗi khi xóa đơn đặt phòng. Vui lòng thử lại sau.');
        }
    }

    public function updateStatus(Request $request, Booking $booking)
    {
        $request->validate([
            'status' => 'required|in:pending,confirmed,cancelled,completed,awaiting_payment',
        ]);

        $old = $booking->status;
        $booking->status = $request->status;
        $booking->save();

        \App\Models\BookingLog::create([
            'booking_id' => $booking->id,
            'old_status' => $old,
            'new_status' => $booking->status,
            'changed_at' => now(),
        ]);

        return back()->with('success', 'Cập nhật trạng thái thành công.');
    }

    /**
     * Admin yêu cầu thanh toán deposit (30%)
     */
    public function requestPayment(Request $request, Booking $booking)
    {
        // Chỉ cho phép yêu cầu thanh toán khi status = pending
        if ($booking->status !== Booking::STATUS_PENDING) {
            return back()->withErrors('Đơn này chưa ở trạng thái chờ xác nhận.');
        }

        DB::beginTransaction();
        try {
            $oldStatus = $booking->status;
            
            $booking->update([
                'payment_request_sent_at' => now(),
                'status' => Booking::STATUS_AWAITING_PAYMENT,
            ]);

            // Tạo booking log với error handling
            try {
                \App\Models\BookingLog::create([
                    'booking_id' => $booking->id,
                    'old_status' => $oldStatus,
                    'new_status' => Booking::STATUS_AWAITING_PAYMENT,
                    'changed_at' => now(),
                ]);
            } catch (\Exception $logException) {
                // Log error but don't fail the whole operation
                \Log::error('Failed to create booking log', [
                    'booking_id' => $booking->id,
                    'error' => $logException->getMessage()
                ]);
            }

            DB::commit();

            return back()->with('success', 'Đã gửi yêu cầu thanh toán deposit cho khách hàng.');
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Request payment failed', [
                'booking_id' => $booking->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return back()->withErrors('Có lỗi xảy ra: ' . $e->getMessage())->withInput();
        }
    }

    /**
     * Admin xác nhận đã nhận được thanh toán deposit
     */
    public function confirmPayment(Booking $booking)
    {
        // Kiểm tra booking đã được thanh toán chưa
        if ($booking->isDepositPaid()) {
            return back()->withErrors('Đơn này đã được thanh toán rồi.');
        }

        if ($booking->status !== Booking::STATUS_AWAITING_PAYMENT) {
            return back()->withErrors('Đơn này chưa ở trạng thái yêu cầu thanh toán.');
        }

        DB::beginTransaction();
        try {
            $booking->update([
                'deposit_paid_at' => now(),
                'status' => Booking::STATUS_CONFIRMED,
            ]);

            \App\Models\BookingLog::create([
                'booking_id' => $booking->id,
                'old_status' => Booking::STATUS_AWAITING_PAYMENT,
                'new_status' => Booking::STATUS_CONFIRMED,
                'changed_at' => now(),
            ]);

            DB::commit();

            return back()->with('success', 'Đã xác nhận thanh toán deposit. Đơn đặt phòng đã được xác nhận thành công.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors('Có lỗi xảy ra, vui lòng thử lại sau.');
        }
    }

    public function checkIn(Booking $booking)
    {
        if ($booking->status !== 'confirmed' || $booking->actual_check_in) {
            return back()->with('error', 'Không thể thực hiện check-in cho đơn này.');
        }

        $old = $booking->status;
        $booking->actual_check_in = now();
        $booking->save();

        \App\Models\BookingLog::create([
            'booking_id' => $booking->id,
            'old_status' => $old,
            'new_status' => 'checked_in',
            'changed_at' => now(),
        ]);

        return back()->with('success', 'Khách đã được check-in.');
    }

    public function checkOut(Booking $booking)
    {
        if (!$booking->actual_check_in || $booking->actual_check_out) {
            return back()->with('error', 'Không thể thực hiện check-out cho đơn này.');
        }

        $old = $booking->status;
        $booking->actual_check_out = now();
        // mark completed on checkout
        $booking->status = 'completed';
        $booking->save();

        \App\Models\BookingLog::create([
            'booking_id' => $booking->id,
            'old_status' => $old,
            'new_status' => 'completed',
            'changed_at' => now(),
        ]);

        return back()->with('success', 'Khách đã check-out.');
    }
}
