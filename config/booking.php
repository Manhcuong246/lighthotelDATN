<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Thời hạn link xem chi tiết đơn (signed URL) gửi email / sau thanh toán
    |--------------------------------------------------------------------------
    */
    'signed_booking_show_ttl_days' => (int) env('BOOKING_SIGNED_SHOW_TTL_DAYS', 90),

    /*
    |--------------------------------------------------------------------------
    | Occupancy & Surcharge — App\Support\RoomOccupancyPricing
    |--------------------------------------------------------------------------
    |
    | standard_capacity : số NL + trẻ 6–11 tối đa KHÔNG phụ phí (trẻ 0–5 không chiếm chỗ).
    | max_capacity      : giới hạn cứng NL + trẻ 6–11; trẻ 0–5 không tính; vượt → từ chối.
    | max_children_05   : tối đa trẻ 0–5 mỗi phòng (policy, không cộng vào capacity).
    | default_*_rate    : % giá phòng/đêm, áp dụng khi room_type chưa set.
    |
    | Trẻ 0–5 tuổi: MIỄN PHÍ và KHÔNG tính vào sức chứa / chỗ tiêu chuẩn.
    | Khi NL + trẻ 6–11 > standard, phụ phí chỉ áp dụng cho phần vượt.
    |
    | Mức đề xuất theo hạng:
    |   Standard         → adult 30%, child 15%
    |   Superior/Deluxe  → adult 25%, child 12.5%  (default)
    |   Suite/Villa      → adult 20%, child 10%
    |
    */
    'pricing' => [
        'standard_capacity' => (int) env('BOOKING_STANDARD_CAPACITY', 2), // Phụ phí từ người thứ 3
        'max_capacity' => (int) env('BOOKING_MAX_CAPACITY', 3),             // Tối đa 3 người/phòng
        'max_children_05' => (int) env('BOOKING_MAX_CHILDREN_05', 2), // Giới hạn 2 trẻ 0-5 miễn phí
        'default_adult_surcharge_rate' => (float) env('BOOKING_DEFAULT_ADULT_SURCHARGE_RATE', 0.25),
        'default_child_surcharge_rate' => (float) env('BOOKING_DEFAULT_CHILD_SURCHARGE_RATE', 0.125),
    ],

    /*
    |--------------------------------------------------------------------------
    | Admin UI — nhãn phương thức thanh toán (bảng đơn,…)
    |--------------------------------------------------------------------------
    */
    'admin_payment_method_labels' => [
        'vnpay' => ['text' => 'VNPay', 'color' => 'dark'],
        'cash' => ['text' => 'Tiền mặt', 'color' => 'secondary'],
        'credit_card' => ['text' => 'Thẻ', 'color' => 'info'],
        'bank_transfer' => ['text' => 'Chuyển khoản', 'color' => 'primary'],
    ],

];
