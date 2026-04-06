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
    | standard_capacity : số người tối đa KHÔNG phụ phí (tính TẤT CẢ khách).
    | max_capacity      : giới hạn cứng (tất cả khách); vượt → từ chối.
    | max_children_05   : tối đa trẻ 0–5 mỗi phòng.
    | default_*_rate    : % giá phòng/đêm, áp dụng khi room_type chưa set.
    |
    | Trẻ 0–5 tuổi: MIỄN PHÍ nhưng TÍNH VÀO sức chứa phòng.
    | Khi tổng người > standard, phụ phí chỉ áp dụng cho NL / trẻ 6–11 dư.
    |
    | Mức đề xuất theo hạng:
    |   Standard         → adult 30%, child 15%
    |   Superior/Deluxe  → adult 25%, child 12.5%  (default)
    |   Suite/Villa      → adult 20%, child 10%
    |
    */
    'pricing' => [
        'standard_capacity' => (int) env('BOOKING_STANDARD_CAPACITY', 3),
        'max_capacity' => (int) env('BOOKING_MAX_CAPACITY', 6),
        'max_children_05' => (int) env('BOOKING_MAX_CHILDREN_05', 3),
        'default_adult_surcharge_rate' => (float) env('BOOKING_DEFAULT_ADULT_SURCHARGE_RATE', 0.25),
        'default_child_surcharge_rate' => (float) env('BOOKING_DEFAULT_CHILD_SURCHARGE_RATE', 0.125),
    ],

];
