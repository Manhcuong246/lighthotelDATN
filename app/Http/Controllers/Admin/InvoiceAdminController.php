<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\Booking;
use App\Models\InvoiceItem;
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
        $invoice->load(['booking.user', 'booking.room', 'booking.bookingServices.service', 'items']);
        return view('admin.invoices.show', compact('invoice'));
    }

    public function create(Booking $booking)
    {
        $booking->load(['user', 'room', 'bookingServices.service']);

        // Kiểm tra đã có hóa đơn chưa
        if ($booking->invoice) {
            return redirect()->route('admin.invoices.show', $booking->invoice)->with('info', 'Đơn đặt phòng này đã có hóa đơn.');
        }

        return view('admin.invoices.create', compact('booking'));
    }

    public function store(Request $request, Booking $booking)
    {
        $booking->load(['user', 'room', 'bookingServices.service']);

        // Kiểm tra đã có hóa đơn chưa
        if ($booking->invoice) {
            return redirect()->route('admin.invoices.show', $booking->invoice)->with('info', 'Đơn đặt phòng này đã có hóa đơn.');
        }

        $validated = $request->validate([
            'notes' => 'nullable|string|max:1000',
            'discount_amount' => 'nullable|numeric|min:0',
            'tax_amount' => 'nullable|numeric|min:0',
        ]);

        DB::beginTransaction();
        try {
            // Tính toán các khoản tiền
            $roomAmount = $booking->total_price;
            $servicesAmount = $booking->bookingServices->sum('price');
            $discountAmount = $validated['discount_amount'] ?? 0;
            $taxAmount = $validated['tax_amount'] ?? 0;
            $totalAmount = $roomAmount + $servicesAmount - $discountAmount + $taxAmount;

            // Tạo hóa đơn
            $invoice = Invoice::create([
                'booking_id' => $booking->id,
                'invoice_number' => Invoice::generateInvoiceNumber(),
                'room_amount' => $roomAmount,
                'services_amount' => $servicesAmount,
                'discount_amount' => $discountAmount,
                'tax_amount' => $taxAmount,
                'total_amount' => $totalAmount,
                'paid_amount' => 0,
                'status' => 'pending',
                'notes' => $validated['notes'] ?? null,
                'issued_at' => now(),
            ]);

            // Tạo các item của hóa đơn
            // Phòng
            $nights = Carbon::parse($booking->check_in)->diffInDays($booking->check_out);
            InvoiceItem::create([
                'invoice_id' => $invoice->id,
                'item_type' => 'room',
                'description' => 'Phòng: ' . $booking->room->name . ' (' . $nights . ' đêm)',
                'quantity' => $nights,
                'unit_price' => $roomAmount / max($nights, 1),
                'total_price' => $roomAmount,
            ]);

            // Dịch vụ
            foreach ($booking->bookingServices as $service) {
                InvoiceItem::create([
                    'invoice_id' => $invoice->id,
                    'item_type' => 'service',
                    'description' => $service->service->name,
                    'quantity' => $service->quantity,
                    'unit_price' => $service->price,
                    'total_price' => $service->price * $service->quantity,
                ]);
            }

            // Giảm giá
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

            // Thuế
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
            $totalAmount = $invoice->room_amount + $invoice->services_amount - $discountAmount + $taxAmount;

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
            $invoice->items()->delete();
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

    public function print(Invoice $invoice)
    {
        $invoice->load(['booking.user', 'booking.room', 'booking.bookingServices.service', 'items']);
        return view('admin.invoices.print', compact('invoice'));
    }
}
