<?php

return [
    'tmn_code' => env('VNPAY_TMN_CODE', 'RAN82HXP'),
    'hash_secret' => env('VNPAY_HASH_SECRET', '4EBRZ3S70W7RVBK5RZKZBSN8T8YZYGKZ'),
    'url' => env('VNPAY_URL', 'https://sandbox.vnpayment.vn/paymentv2/vpcpay.html'),
    /** IP gửi kèm khi tạo link thanh toán từ server (không có IP khách). */
    'server_ip' => env('VNPAY_SERVER_IP', '127.0.0.1'),
    /** Số ngày hiệu lực của link “vào thanh toán” (/payment/vnpay/pay/{booking}) gửi qua email (chữ ký URL). */
    'pay_entry_signed_ttl_days' => (int) env('VNPAY_PAY_ENTRY_TTL_DAYS', 14),
    /** Phút VNPay cho mỗi lần tạo giao dịch (sau khi khách bấm link và hệ thống redirect sang VNPay). */
    'transaction_expire_minutes' => (int) env('VNPAY_TXN_EXPIRE_MINUTES', 15),
];
