-- =====================================================================
-- Tài khoản demo: khách đã hoàn tất đơn (checkout), 1 đơn gồm 3 phòng —
-- loại Standard (103), Deluxe (206), Suite (301).
--
-- Đăng nhập:
--   Email:    demo.multiroom@datn.test
--   Mật khẩu: password
--
-- Cách chạy (MySQL), chọn đúng database trước:
--   mysql -u ... -p tên_database < database/sql/seed_demo_user_multi_room_completed.sql
--
-- Lưu ý: Khoảng ngày 2024-05-01 .. 2024-05-04 cho phòng 10, 7, 3 — nếu trùng dữ liệu
-- cũ (room_booked_dates), hãy đổi ngày hoặc đổi room_id trong file này.
-- =====================================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- User (role 2 = khách trong dump mẫu)
INSERT INTO `users` (
  `id`, `full_name`, `email`, `email_verified_at`, `password`, `phone`,
  `avatar_url`, `status`, `remember_token`, `created_at`, `updated_at`
) VALUES (
  101,
  'Khách Demo Đa phòng',
  'demo.multiroom@datn.test',
  NOW(),
  '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
  '0900000999',
  NULL,
  'active',
  NULL,
  NOW(),
  NOW()
);

INSERT INTO `user_roles` (`user_id`, `role_id`) VALUES (101, 2);

-- Đơn: đã completed, đã check-in / check-out
INSERT INTO `bookings` (
  `user_id`, `room_id`, `check_in`, `check_out`,
  `actual_check_in`, `actual_check_out`,
  `guests`, `adults`, `children`, `total_price`, `coupon_code`, `discount_amount`,
  `status`, `created_at`, `updated_at`, `payment_status`, `payment_method`, `placed_via`,
  `cancellation_reason`, `cancelled_at`, `check_in_date`, `check_out_date`
) VALUES (
  101,
  NULL,
  '2024-05-01',
  '2024-05-04',
  '2024-05-01 14:00:00',
  '2024-05-04 11:00:06',
  6,
  6,
  0,
  12900000.00,
  NULL,
  0.00,
  'completed',
  '2024-04-20 10:00:00',
  NOW(),
  'paid',
  'vnpay',
  'customer_web',
  NULL,
  NULL,
  NULL,
  NULL
);

SET @bid = LAST_INSERT_ID();

-- Ba phòng, ba loại: Standard 103 (id 10), Deluxe 206 (id 7), Suite 301 (id 3); 3 đêm
INSERT INTO `booking_rooms` (
  `booking_id`, `room_id`, `adults`, `children_0_5`, `children_6_11`,
  `price_per_night`, `nights`, `subtotal`, `created_at`, `updated_at`
) VALUES
(@bid, 10, 2, 0, 0, 850000.00, 3, 2550000.00, NOW(), NOW()),
(@bid, 7, 2, 0, 0, 1350000.00, 3, 4050000.00, NOW(), NOW()),
(@bid, 3, 2, 0, 0, 2100000.00, 3, 6300000.00, NOW(), NOW());

INSERT INTO `payments` (
  `booking_id`, `amount`, `method`, `transaction_id`, `status`,
  `paid_at`, `created_at`, `updated_at`
) VALUES (
  @bid,
  12900000.00,
  'vnpay',
  'DEMO_MULTIROOM_20240420',
  'paid',
  '2024-04-20 10:05:00',
  NOW(),
  NOW()
);

-- Giữ phòng theo từng đêm (check-out không tính đêm)
INSERT INTO `room_booked_dates` (`room_id`, `booked_date`, `booking_id`, `created_at`, `updated_at`) VALUES
(10, '2024-05-01', @bid, NOW(), NOW()),
(10, '2024-05-02', @bid, NOW(), NOW()),
(10, '2024-05-03', @bid, NOW(), NOW()),
(7, '2024-05-01', @bid, NOW(), NOW()),
(7, '2024-05-02', @bid, NOW(), NOW()),
(7, '2024-05-03', @bid, NOW(), NOW()),
(3, '2024-05-01', @bid, NOW(), NOW()),
(3, '2024-05-02', @bid, NOW(), NOW()),
(3, '2024-05-03', @bid, NOW(), NOW());

SET FOREIGN_KEY_CHECKS = 1;

-- Hiển thị mã đơn vừa tạo
SELECT @bid AS booking_id_created;
