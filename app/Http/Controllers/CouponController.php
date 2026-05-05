<?php

namespace App\Http\Controllers;

use App\Http\Requests\VerifyCouponCodeRequest;
use App\Models\Coupon;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;

class CouponController extends Controller
{
    /**
     * Verify coupon code and return discount percentage.
     */
    public function verify(VerifyCouponCodeRequest $request): JsonResponse
    {
        $code = $request->validated('code');

        $coupon = Coupon::where('code', $code)
            ->where('is_active', true)
            ->where(function ($query) {
                $query->whereNull('expired_at')
                      ->orWhere('expired_at', '>=', Carbon::today()->toDateString());
            })
            ->first();

        if (! $coupon) {
            return response()->json([
                'success' => false,
                'message' => 'Mã giảm giá không hợp lệ, đã hết hạn hoặc không còn hiệu lực.',
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Áp dụng mã giảm giá: -'.$coupon->discount_percent.'%',
            'discount_percent' => (float) $coupon->discount_percent,
            'code' => $coupon->code,
        ]);
    }
}
