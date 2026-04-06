<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Thời hạn link xem chi tiết đơn (signed URL) gửi email / sau thanh toán
    |--------------------------------------------------------------------------
    */
    'signed_booking_show_ttl_days' => (int) env('BOOKING_SIGNED_SHOW_TTL_DAYS', 90),

];
