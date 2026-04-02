<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\RefundRequest;
use App\Services\RefundService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class RefundAdminController extends Controller
{
    public function __construct()
    {
        $this->middleware('admin');
    }

    public function index(Request $request)
    {
        $query = RefundRequest::with(['user', 'booking'])->latest();

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $refundRequests = $query->paginate(15)->withQueryString();
        
        return view('admin.refunds.index', compact('refundRequests'));
    }

    public function show(RefundRequest $refundRequest)
    {
        $refundRequest->load(['user', 'booking.room', 'booking.payment']);
        return view('admin.refunds.show', compact('refundRequest'));
    }

    public function process(Request $request, RefundRequest $refundRequest, RefundService $refundService)
    {
        if ($refundRequest->status !== 'pending_refund') {
            return back()->with('error', 'Yêu cầu này đã được xử lý.');
        }

        $request->validate([
            'action' => 'required|in:approve,reject',
            'admin_note' => 'nullable|string|max:1000',
            'refund_proof_image' => 'nullable|image|max:2048|required_if:action,approve',
        ]);

        if ($request->action === 'approve') {
            $data = [
                'admin_note' => $request->admin_note,
            ];

            if ($request->hasFile('refund_proof_image')) {
                $data['refund_proof_image'] = $request->file('refund_proof_image')->store('refund_proofs', 'public');
            }

            $refundService->approveRefund($refundRequest, $data);
            return redirect()->route('admin.refunds.index')->with('success', 'Đã chấp nhận hoàn tiền và hủy đơn đặt phòng thành công.');
        } else {
            $refundService->rejectRefund($refundRequest, $request->admin_note ?? 'Yêu cầu bị từ chối bởi Admin.');
            return redirect()->route('admin.refunds.index')->with('success', 'Đã từ chối yêu cầu hoàn tiền.');
        }
    }
}
