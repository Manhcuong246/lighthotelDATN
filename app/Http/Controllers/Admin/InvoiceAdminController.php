<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\HotelInfo;
use App\Models\Invoice;
use App\Models\Booking;
use App\Models\InvoiceItem;
use App\Support\BookingInvoiceViewData;
use App\Support\InvoiceExtrasSynchronizer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Exception;


class InvoiceAdminController extends Controller
{
    public function __construct()
    {
        $this->middleware('admin');
        $this->middleware('only_admin');
    }

    public function index(Request $request)
    {
        $query = Invoice::with(['booking.user', 'booking.room'])->latest();

        if ($request->filled('q')) {
            $q = $request->q;
            $query->where(function ($qry) use ($q) {
                $qry->where('invoice_number', 'like', "%{$q}%")
                    ->orWhereHas('booking', fn ($b) => $b->where('id', 'like', "%{$q}%"))
                    ->orWhereHas('booking.user', fn ($u) => $u->where('full_name', 'like', "%{$q}%"));
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $invoices = $query->paginate(15)->withQueryString();

        $counts = [
            'total' => Invoice::count(),
            'pending' => Invoice::where('status', 'pending')->count(),
            'paid' => Invoice::where('status', 'paid')->count(),
            'partially_paid' => Invoice::where('status', 'partially_paid')->count(),
        ];

        return view('admin.invoices.index', compact('invoices', 'counts'));
    }

    public function show(Invoice $invoice)
    {
        $invoice->load([
            'booking.user',
            'booking.room.roomType',
            'booking.rooms.roomType',
            'booking.bookingRooms.room.roomType',
            'booking.bookingServices.service',
            'booking.surcharges.service',
            'booking.latestPayment',
            'items',
        ]);
        $hotel = HotelInfo::first();

        return view('admin.invoices.show', compact('invoice', 'hotel'));
    }

    public function create(Booking $booking)
    {
        $booking->load([
            'user',
            'room',
            'rooms',
            'bookingServices.service',
            'bookingRooms.room.roomType',
            'surcharges.service',
        ]);

        // Kiểm tra đã có hóa đơn chưa
        if ($booking->invoice) {
            return redirect()->route('admin.invoices.show', $booking->invoice)->with('info', 'Đơn đặt phòng này đã có hóa đơn.');
        }

        if (! BookingInvoiceViewData::customerCanView($booking)) {
            return redirect()
                ->route('admin.bookings.show', $booking)
                ->with('error', 'Chỉ tạo hóa đơn khi đơn đã checkout và đã thanh toán.');
        }

        return view('admin.invoices.create', compact('booking'));
    }

    public function store(Request $request, Booking $booking)
    {
        $booking->load([
            'user',
            'room',
            'rooms',
            'bookingServices.service',
            'bookingRooms.room.roomType',
            'surcharges.service',
        ]);

        // Kiểm tra đã có hóa đơn chưa
        if ($booking->invoice) {
            return redirect()->route('admin.invoices.show', $booking->invoice)->with('info', 'Đơn đặt phòng này đã có hóa đơn.');
        }

        if (! BookingInvoiceViewData::customerCanView($booking)) {
            return redirect()
                ->route('admin.bookings.show', $booking)
                ->with('error', 'Chỉ tạo hóa đơn khi đơn đã checkout và đã thanh toán.');
        }

        $validated = $request->validate([
            'notes' => 'nullable|string|max:1000',
            'discount_amount' => 'nullable|numeric|min:0',
            'tax_amount' => 'nullable|numeric|min:0',
        ]);

        DB::beginTransaction();
        try {
            $servicesAmount = (float) $booking->bookingServices->sum(
                static fn ($s) => (float) $s->price * (int) $s->quantity
            );
            $surchargesAmount = (float) $booking->surcharges->sum(
                static fn ($s) => (float) $s->amount
            );
            $couponDiscount = (float) ($booking->discount_amount ?? 0);
            $roomsSum = (float) $booking->bookingRooms->sum('subtotal');

            $invoiceDiscount = (float) ($validated['discount_amount'] ?? 0);
            $taxAmount = (float) ($validated['tax_amount'] ?? 0);
            $bookingTotal = (float) $booking->total_price;
            $totalAmount = $bookingTotal - $invoiceDiscount + $taxAmount;

            $invoice = Invoice::create([
                'booking_id' => $booking->id,
                'invoice_number' => Invoice::generateInvoiceNumber(),
                'room_amount' => 0,
                'services_amount' => $servicesAmount,
                'surcharges_amount' => $surchargesAmount,
                'discount_amount' => $invoiceDiscount,
                'tax_amount' => $taxAmount,
                'total_amount' => $totalAmount,
                'paid_amount' => 0,
                'status' => 'pending',
                'notes' => $validated['notes'] ?? null,
                'issued_at' => now(),
            ]);

            // —— Lưu trú: từng phòng (booking_rooms) hoặc gộp 1 dòng (đơn cũ) ——
            if ($booking->bookingRooms->isNotEmpty()) {
                foreach ($booking->bookingRooms as $br) {
                    $payload = $this->buildInvoiceRoomLineFromBookingRoom($br);
                    InvoiceItem::create(array_merge([
                        'invoice_id' => $invoice->id,
                        'item_type' => 'room',
                    ], $payload));
                }
                $invoice->room_amount = $roomsSum;
            } else {
                $nights = max(1, Carbon::parse($booking->check_in)->diffInDays($booking->check_out));
                $roomPart = max(0, $bookingTotal - $servicesAmount - $surchargesAmount + $couponDiscount);
                $roomLabel = $booking->rooms->isNotEmpty()
                    ? $booking->rooms->pluck('name')->filter()->implode(', ')
                    : ($booking->room?->name ?? 'Lưu trú');
                $typeName = $booking->room?->roomType?->name;
                $payload = $this->buildInvoiceRoomLineLegacy($booking, $roomPart, $nights, $roomLabel, $typeName);

                InvoiceItem::create(array_merge([
                    'invoice_id' => $invoice->id,
                    'item_type' => 'room',
                ], $payload));
                $invoice->room_amount = $roomPart;
            }

            InvoiceExtrasSynchronizer::appendServiceItems($invoice, $booking);
            InvoiceExtrasSynchronizer::appendSurchargeItems($invoice, $booking);

            if ($couponDiscount > 0) {
                InvoiceItem::create([
                    'invoice_id' => $invoice->id,
                    'item_type' => 'coupon',
                    'description' => 'Giảm giá ưu đãi khi đặt phòng (mã / chiết khấu đơn)',
                    'quantity' => 1,
                    'unit_price' => -$couponDiscount,
                    'total_price' => -$couponDiscount,
                ]);
            }

            InvoiceExtrasSynchronizer::reconcileAdjustment($invoice, $servicesAmount, $surchargesAmount, $couponDiscount, $bookingTotal);

            if ($invoiceDiscount > 0) {
                InvoiceItem::create([
                    'invoice_id' => $invoice->id,
                    'item_type' => 'discount',
                    'description' => 'Giảm giá thêm trên hóa đơn',
                    'quantity' => 1,
                    'unit_price' => -$invoiceDiscount,
                    'total_price' => -$invoiceDiscount,
                ]);
            }

            if ($taxAmount > 0) {
                InvoiceItem::create([
                    'invoice_id' => $invoice->id,
                    'item_type' => 'fee',
                    'description' => 'Thuế và phí (theo hóa đơn)',
                    'quantity' => 1,
                    'unit_price' => $taxAmount,
                    'total_price' => $taxAmount,
                ]);
            }

            $invoice->save();

            DB::commit();

            return redirect()->route('admin.invoices.show', $invoice)->with('success', 'Tạo hóa đơn thành công!');
        } catch (Exception $e) {
            DB::rollBack();
            return back()->withErrors('Có lỗi xảy ra: ' . $e->getMessage())->withInput();
        }
    }

    public function edit(Invoice $invoice)
    {
        $invoice->load(['booking.user', 'booking.room', 'items']);
        return view('admin.invoices.edit', compact('invoice'));
    }

    public function update(Request $request, Invoice $invoice)
    {
        $validated = $request->validate([
            'notes' => 'nullable|string|max:1000',
            'discount_amount' => 'nullable|numeric|min:0',
            'tax_amount' => 'nullable|numeric|min:0',
        ]);

        DB::beginTransaction();
        try {
            $discountAmount = $validated['discount_amount'] ?? $invoice->discount_amount;
            $taxAmount = $validated['tax_amount'] ?? $invoice->tax_amount;
            $surchargesAmount = (float) ($invoice->surcharges_amount ?? 0);
            $totalAmount = (float) $invoice->room_amount + (float) $invoice->services_amount + $surchargesAmount - $discountAmount + $taxAmount;

            $invoice->update([
                'discount_amount' => $discountAmount,
                'tax_amount' => $taxAmount,
                'total_amount' => $totalAmount,
                'notes' => $validated['notes'] ?? $invoice->notes,
            ]);

            // Cập nhật lại các invoice items
            $invoice->items()->where('item_type', 'discount')->delete();
            $invoice->items()->where('item_type', 'fee')->delete();

            if ($discountAmount > 0) {
                InvoiceItem::create([
                    'invoice_id' => $invoice->id,
                    'item_type' => 'discount',
                    'description' => 'Giảm giá',
                    'quantity' => 1,
                    'unit_price' => -$discountAmount,
                    'total_price' => -$discountAmount,
                ]);
            }

            if ($taxAmount > 0) {
                InvoiceItem::create([
                    'invoice_id' => $invoice->id,
                    'item_type' => 'fee',
                    'description' => 'Thuế và phí',
                    'quantity' => 1,
                    'unit_price' => $taxAmount,
                    'total_price' => $taxAmount,
                ]);
            }

            DB::commit();

            return redirect()->route('admin.invoices.show', $invoice)->with('success', 'Cập nhật hóa đơn thành công!');
        } catch (Exception $e) {
            DB::rollBack();
            return back()->withErrors('Có lỗi xảy ra: ' . $e->getMessage())->withInput();
        }
    }

    public function destroy(Invoice $invoice)
    {
        if (!Auth::user()?->isAdmin()) {
            abort(403, 'Chỉ quản trị viên mới được xóa hóa đơn.');
        }

        DB::beginTransaction();
        try {
            $invoice->delete();

            DB::commit();
            return redirect()->route('admin.invoices.index')->with('success', 'Xóa hóa đơn thành công!');
        } catch (Exception $e) {
            DB::rollBack();
            return back()->withErrors('Có lỗi khi xóa hóa đơn. Vui lòng thử lại sau.');
        }
    }

    public function markAsPaid(Request $request, Invoice $invoice)
    {
        $validated = $request->validate([
            'amount' => 'required|numeric|min:0|max:' . $invoice->remaining_amount,
        ]);

        $newPaidAmount = $invoice->paid_amount + $validated['amount'];
        $invoice->markAsPaid($newPaidAmount);

        return back()->with('success', 'Đã cập nhật thanh toán thành công!');
    }

    /**
     * Cập nhật lại dịch vụ đặt kèm & phụ phí trên hóa đơn theo đơn hiện tại (sau khi admin thêm DV/PP sau lúc tạo HĐ).
     */
    public function syncBookingExtras(Invoice $invoice)
    {
        try {
            InvoiceExtrasSynchronizer::replaceExtrasFromBooking($invoice);

            return redirect()
                ->route('admin.invoices.show', $invoice->fresh(['items']))
                ->with('success', 'Đã đồng bộ dịch vụ & phụ phí từ đơn; tổng thanh toán khớp với tổng đơn hiện tại.');
        } catch (Exception $e) {
            return redirect()
                ->route('admin.invoices.show', $invoice)
                ->with('error', 'Không đồng bộ được: ' . $e->getMessage());
        }
    }

    public function print(Invoice $invoice)
    {
        $invoice->load([
            'booking.user',
            'booking.room.roomType',
            'booking.rooms.roomType',
            'booking.bookingRooms.room.roomType',
            'booking.bookingServices.service',
            'booking.surcharges.service',
            'booking.latestPayment',
            'items',
        ]);
        $hotel = HotelInfo::first();

        return view('admin.invoices.print', compact('invoice', 'hotel'));
    }

    /**
     * @return array{description: string, quantity: int, unit_price: float, total_price: float, guest_adults: int, guest_children_6_11: int, guest_children_0_5: int}
     */
    private function buildInvoiceRoomLineFromBookingRoom(\App\Models\BookingRoom $br): array
    {
        $n = max(1, (int) $br->nights);
        $room = $br->room;
        $typeName = $room?->roomType?->name;
        $adults = (int) $br->adults;
        $c611 = (int) $br->children_6_11;
        $c05 = (int) $br->children_0_5;

        $lines = [
            $room?->name ?? ('Phòng #' . $br->room_id),
            'Loại phòng: ' . ($typeName ?? '—'),
            'Số đêm lưu trú: ' . $n,
            '',
            'Thành phần khách (theo từng phòng trên đơn):',
            '• Người lớn: ' . $adults,
            '• Trẻ em 6–11 tuổi: ' . $c611 . ' (tính vào occupancy / giá theo chính sách loại phòng)',
            '• Trẻ em 0–5 tuổi: ' . $c05 . ' (thường miễn phí lưu trú, vẫn tính vào sức chứa tối đa phòng)',
        ];

        return [
            'description' => implode("\n", $lines),
            'quantity' => $n,
            'unit_price' => round((float) $br->subtotal / $n, 2),
            'total_price' => (float) $br->subtotal,
            'guest_adults' => $adults,
            'guest_children_6_11' => $c611,
            'guest_children_0_5' => $c05,
        ];
    }

    /**
     * Đơn cũ không có booking_rooms — mô tả tối đa theo dữ liệu bookings.adults / children / guests.
     *
     * @return array{description: string, quantity: int, unit_price: float, total_price: float, guest_adults: ?int, guest_children_6_11: ?int, guest_children_0_5: ?int}
     */
    private function buildInvoiceRoomLineLegacy(Booking $booking, float $roomPart, int $nights, string $roomLabel, ?string $typeName): array
    {
        $lines = [
            'Lưu trú (một dòng gộp — đơn không chi tiết từng phòng): ' . $roomLabel,
            'Loại phòng: ' . ($typeName ?? '—'),
            'Số đêm lưu trú: ' . $nights,
            '',
            'Thành phần khách:',
        ];

        $guestAdults = null;
        $guestC6 = null;
        $guestC0 = null;

        if ($booking->adults !== null && $booking->adults !== '') {
            $guestAdults = (int) $booking->adults;
            $lines[] = '• Người lớn: ' . $guestAdults;
        }
        if ($booking->children !== null && $booking->children !== '') {
            $lines[] = '• Trẻ em (tổng ghi trên đơn — không phân tách 0–5 và 6–11 trong dữ liệu cũ): ' . (int) $booking->children;
        }
        if ($guestAdults === null && $booking->guests !== null && $booking->guests !== '') {
            $lines[] = '• Tổng số khách ghi trên đơn: ' . (int) $booking->guests;
        }
        if (count($lines) <= 5) {
            $lines[] = '• Không có chi tiết NL / trẻ 0–5 / trẻ 6–11 trên đơn — đơn mới đa phòng sẽ hiển thị đủ từng phòng.';
        }

        return [
            'description' => implode("\n", $lines),
            'quantity' => $nights,
            'unit_price' => round($roomPart / $nights, 2),
            'total_price' => $roomPart,
            'guest_adults' => $guestAdults,
            'guest_children_6_11' => $guestC6,
            'guest_children_0_5' => $guestC0,
        ];
    }
}
