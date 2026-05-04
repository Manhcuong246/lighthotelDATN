<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Giới hạn giờ đổi phòng
    |--------------------------------------------------------------------------
    |
    | Không cho phép đổi phòng sau giờ này (24h format), trừ trường hợp khẩn cấp.
    | Mặc định: 22:00 (10 giờ tối)
    |
    */
    'time_restriction_hour' => (int) env('ROOM_CHANGE_TIME_RESTRICTION_HOUR', 22),

    /*
    |--------------------------------------------------------------------------
    | Bật giới hạn giờ đổi phòng
    |--------------------------------------------------------------------------
    |
    | false (mặc định): Lễ tân/Admin đổi phòng mọi lúc (giống luồng cũ trên web).
    | true: Sau time_restriction_hour chỉ đổi khi báo khẩn cấp.
    |
    */
    'enforce_time_restriction' => filter_var(
        env('ROOM_CHANGE_ENFORCE_TIME', false),
        FILTER_VALIDATE_BOOLEAN
    ),

    /*
    |--------------------------------------------------------------------------
    | Chính sách hạ hạng (Downgrade)
    |--------------------------------------------------------------------------
    |
    | Khi đổi sang phòng rẻ hơn:
    |   'refund'  → Hoàn tiền ngay cho khách
    |   'credit'  → Ghi credit vào Folio cho lần sau
    |   'none'    → Không hoàn tiền (chỉ cập nhật giá)
    |
    */
    'downgrade_policy' => env('ROOM_CHANGE_DOWNGRADE_POLICY', 'credit'),

    /*
    |--------------------------------------------------------------------------
    | Chính sách nâng hạng (Upgrade)
    |--------------------------------------------------------------------------
    |
    | Khi đổi sang phòng đắt hơn:
    |   'pay_now'      → Yêu cầu thanh toán ngay
    |   'add_to_folio' → Ghi nợ vào hóa đơn tổng (Folio)
    |   'auto_confirm' → Tự động xác nhận (dùng cho admin)
    |
    */
    'upgrade_policy' => env('ROOM_CHANGE_UPGRADE_POLICY', 'add_to_folio'),

    /*
    |--------------------------------------------------------------------------
    | Lý do đổi phòng mặc định
    |--------------------------------------------------------------------------
    */
    'reasons' => [
        'guest_request' => 'Khách yêu cầu đổi phòng',
        'room_issue'    => 'Phòng bị lỗi thiết bị',
        'upgrade'       => 'Khách muốn nâng hạng',
        'noise'         => 'Tiếng ồn / không gian ồn ào',
        'view_request'  => 'Khách muốn đổi view',
        'maintenance'   => 'Bảo trì phòng',
        'emergency'     => 'Khẩn cấp kỹ thuật',
        'other'         => 'Lý do khác',
    ],

    /*
    |--------------------------------------------------------------------------
    | Trạng thái phòng cho phép đổi sang
    |--------------------------------------------------------------------------
    |
    | Chỉ cho phép đổi sang phòng có trạng thái này (Ready/Clean)
    |
    */
    'allowed_target_statuses' => ['available'],
];
