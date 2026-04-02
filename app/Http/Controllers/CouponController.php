<?php

namespace App\Http\Controllers;

use App\Models\Coupon;
use Illuminate\Http\Request;
use Carbon\Carbon;

class CouponController extends Controller
{
    /**
     * Verify coupon code and return discount percentage.
     */
    public function verify(Request $request)
    {
        $code = $request->input('code');
        
        if (empty($code)) {
            return response()->json([
                'success' => false, 
                'message' => 'Vui lòng nhập mã giảm giá.'
            ]);
        }

        $coupon = Coupon::where('code', $code)
            ->where('is_active', true)
            ->where(function ($query) {
                $query->whereNull('expired_at')
                      ->orWhere('expired_at', '>=', Carbon::today()->toDateString());
            })
            ->first();

        if (!$coupon) {
            return response()->json([
                'success' => false, 
                'message' => 'Mã giảm giá không hợp lệ, đã hết hạn hoặc không còn hiệu lực.'
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Áp dụng mã giảm giá: -' . $coupon->discount_percent . '%',
            'discount_percent' => (float)$coupon->discount_percent,
            'code' => $coupon->code
        ]);
    }
}
