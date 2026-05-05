<?php

return [
    /** Để trống trong .env = không cấu hình VNPay (chỉ local / chỉ tiền mặt). */
    'tmn_code' => (string) env('VNPAY_TMN_CODE', ''),
    'hash_secret' => (string) env('VNPAY_HASH_SECRET', ''),
    'url' => env('VNPAY_URL', 'https://sandbox.vnpayment.vn/paymentv2/vpcpay.html'),
    /** IP gửi kèm khi tạo link thanh toán từ server (không có IP khách). */
    'server_ip' => env('VNPAY_SERVER_IP', '127.0.0.1'),
    /** Số ngày hiệu lực của link “vào thanh toán” (/payment/vnpay/pay/{booking}) gửi qua email (chữ ký URL). */
    'pay_entry_signed_ttl_days' => (int) env('VNPAY_PAY_ENTRY_TTL_DAYS', 14),
    /**
     * Phút chờ thanh toán trên cổng VNPay — tính từ lúc tạo URL (khách bấm link / đặt phòng),
     * không phải từ lúc gửi email. Tăng nếu khách cần thời gian (tối đa 1440 trong code).
     */
    'transaction_expire_minutes' => (int) env('VNPAY_TXN_EXPIRE_MINUTES', 30),
];
