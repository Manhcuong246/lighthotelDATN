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
        $this->middleware('only_admin');
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
            'admin_note' => 'nullable|string|max:1000|required_if:action,reject',
            'refund_proof_image' => 'nullable|image|max:2048|required_if:action,approve',
        ], [
            'admin_note.required_if' => 'Vui lòng nhập lý do khi từ chối yêu cầu hoàn tiền.',
            'refund_proof_image.required_if' => 'Vui lòng tải minh chứng chuyển khoản khi duyệt hoàn tiền.',
        ]);

        if ($request->action === 'approve') {
            $data = [
                'admin_note' => $request->admin_note,
            ];

            if ($request->hasFile('refund_proof_image')) {
                $data['refund_proof_image'] = $request->file('refund_proof_image')->store('refund_proofs', 'public');
            }

            if (! $refundService->approveRefund($refundRequest, $data)) {
                return back()->with('error', 'Không thể duyệt hoàn tiền. Yêu cầu có thể đã được xử lý trước đó.');
            }
            return redirect()->route('admin.refunds.index')->with('success', 'Đã chấp nhận hoàn tiền và hủy đơn đặt phòng thành công.');
        } else {
            if (! $refundService->rejectRefund($refundRequest, (string) $request->admin_note)) {
                return back()->with('error', 'Không thể từ chối yêu cầu. Yêu cầu có thể đã được xử lý trước đó.');
            }
            return redirect()->route('admin.refunds.index')->with('success', 'Đã từ chối yêu cầu hoàn tiền.');
        }
    }
}
