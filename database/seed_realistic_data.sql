-- ============================================================
-- SEED DỮ LIỆU THỰC TẾ CHO HOTEL BOOKING
-- Chạy file này SAU KHI đã import hotel_booking (6).sql
-- ============================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ============================================================
-- 1. DỌN DẸP DỮ LIỆU TRÙNG LẶP
-- ============================================================

-- Xóa amenities trùng (19-26 là bản sao của 11-18)
DELETE FROM `room_amenities` WHERE `amenity_id` IN (19,20,21,22,23,24,25,26);
DELETE FROM `amenities` WHERE `id` IN (19,20,21,22,23,24,25,26);

-- Xóa room_types trùng (9,10,11 trùng 6,7,8)
DELETE FROM `room_types` WHERE `id` IN (9,10,11);

-- ============================================================
-- 2. CẬP NHẬT HOTEL_INFO - MÔ TẢ CHI TIẾT HƠN
-- ============================================================

UPDATE `hotel_info` SET
  `description` = 'Light Hotel mang đến trải nghiệm lưu trú hiện đại: thiết kế tối giản, ánh sáng tự nhiên, tiện ích đầy đủ và đội ngũ phục vụ chuyên nghiệp. Phù hợp công tác, gia đình và kỳ nghỉ ngắn ngày — đặt phòng minh bạch, hỗ trợ 24/7.',
  `rating_avg` = 4.72,
  `updated_at` = NOW()
WHERE `id` = 1;

-- ============================================================
-- 3. THÊM NGƯỜI DÙNG MỚI (25 users bổ sung)
-- ============================================================

INSERT INTO `users` (`full_name`, `email`, `email_verified_at`, `password`, `phone`, `avatar_url`, `status`, `created_at`, `updated_at`) VALUES
('Phan Đức Minh', 'minh.phan.dt@gmail.com', NOW(), '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '0912876543', NULL, 'active', '2024-11-03 09:20:00', NOW()),
('Trương Thị Kim Anh', 'kimanh.truong@outlook.com', NOW(), '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '0987123456', NULL, 'active', '2024-11-18 14:35:00', NOW()),
('Đỗ Văn Thành', 'thanh.do.ict@gmail.com', NULL, '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '0903456789', NULL, 'active', '2024-12-05 11:00:00', NOW()),
('Lâm Thị Hồng Nhung', 'nhung.lam@yahoo.com.vn', NOW(), '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '0978234567', NULL, 'active', '2024-12-22 16:45:00', NOW()),
('Nguyễn Hoàng Phúc', 'phuc.nguyen.hn@gmail.com', NOW(), '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '0934567891', NULL, 'active', '2025-01-10 08:30:00', NOW()),
('Võ Thị Thanh Thảo', 'thaovo.1992@gmail.com', NOW(), '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '0945678902', NULL, 'active', '2025-01-28 13:20:00', NOW()),
('Bùi Quang Huy', 'huy.bui.q@gmail.com', NULL, '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '0956789013', NULL, 'active', '2025-02-14 10:15:00', NOW()),
('Trần Minh Đức', 'duc.tran.m@outlook.com', NOW(), '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '0967890124', NULL, 'active', '2025-03-02 17:40:00', NOW()),
('Lê Thị Mai Phương', 'phuong.le.mai@gmail.com', NOW(), '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '0978901235', NULL, 'active', '2025-03-20 09:55:00', NOW()),
('Hoàng Minh Tuấn', 'tuan.hoang.dn@gmail.com', NOW(), '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '0989012346', NULL, 'active', '2025-04-08 14:10:00', NOW()),
('Phạm Thị Ngọc Trâm', 'tram.pham.nt@yahoo.com', NULL, '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '0990123457', NULL, 'active', '2025-04-25 11:30:00', NOW()),
('Đinh Văn Khoa', 'khoa.dinh@gmail.com', NOW(), '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '0911234568', NULL, 'active', '2025-05-12 16:00:00', NOW()),
('Cao Thị Hương Giang', 'giang.cao@hotmail.com', NOW(), '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '0922345679', NULL, 'active', '2025-05-30 08:45:00', NOW()),
('Tạ Minh Quang', 'quang.ta@gmail.com', NULL, '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '0933456780', NULL, 'active', '2025-06-15 12:20:00', NOW()),
('Chu Thị Bích Ngọc', 'ngoc.chu@outlook.com', NOW(), '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '0944567891', NULL, 'active', '2025-07-01 15:35:00', NOW()),
('Dương Văn Hùng', 'hung.duong.vn@gmail.com', NOW(), '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '0955678902', NULL, 'active', '2025-07-18 10:50:00', NOW()),
('Lý Thị Thanh Hà', 'ha.ly.tt@gmail.com', NULL, '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '0966789013', NULL, 'active', '2025-08-05 09:25:00', NOW()),
('Vương Minh Đạt', 'dat.vuong@gmail.com', NOW(), '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '0977890124', NULL, 'active', '2025-08-22 14:40:00', NOW()),
('Tô Thị Thu Hằng', 'hang.to@yahoo.com.vn', NOW(), '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '0988901235', NULL, 'active', '2025-09-10 11:05:00', NOW()),
('Hồ Văn Long', 'long.ho.danang@gmail.com', NOW(), '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '0999012346', NULL, 'active', '2025-09-28 17:15:00', NOW()),
('Đặng Thị Minh Châu', 'chau.dang@gmail.com', NULL, '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '0910123457', NULL, 'active', '2025-10-15 08:30:00', NOW()),
('Mai Văn Thắng', 'thang.mai@outlook.com', NOW(), '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '0921234568', NULL, 'active', '2025-11-02 13:45:00', NOW()),
('Kiều Thị Hương Ly', 'ly.kieu@gmail.com', NOW(), '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '0932345679', NULL, 'active', '2025-11-20 10:20:00', NOW()),
('Tăng Văn Bình', 'binh.tang@gmail.com', NULL, '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '0943456780', NULL, 'active', '2025-12-08 16:00:00', NOW()),
('Hà Thị Ngọc Lan', 'lan.ha.tn@gmail.com', NOW(), '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '0954567891', NULL, 'active', '2025-12-26 09:35:00', NOW());

-- Gán role customer cho users mới (id 11-35)
INSERT INTO `user_roles` (`user_id`, `role_id`) 
SELECT id, 2 FROM `users` WHERE id > 10 AND id <= 35;

-- ============================================================
-- 4. CẬP NHẬT ROOM_TYPES - MÔ TẢ DÀI VÀ CHI TIẾT
-- ============================================================

UPDATE `room_types` SET `description` = 'Phòng Standard tại Light Hotel được thiết kế tối giản nhưng đầy đủ tiện nghi 5 sao. Diện tích 28-30m² với giường King-size hoặc 2 giường đơn, chăn ga cotton Ai Cập 500 thread count, điều hòa hai chiều. Phòng hướng vườn nhiệt đới hoặc hồ bơi, có ban công nhỏ lý tưởng cho buổi sáng nhâm nhi cà phê. Phù hợp cho cặp đôi, khách công tác ngắn ngày hoặc du khách tiết kiệm muốn trải nghiệm resort biển mà không phải chi quá nhiều.' WHERE `id` = 1;

UPDATE `room_types` SET `description` = 'Phòng Deluxe là lựa chọn phổ biến nhất của khách lưu trú tại Light Hotel. Diện tích 35m² với ban công rộng hướng biển, cửa kính floor-to-ceiling cho tầm nhìn panorama. Nội thất gỗ óc chó, giường Queen/King, minibar miễn phí và máy pha cà phê Nespresso. Ánh sáng tự nhiên tràn ngập, tiếng sóng vỗ nhẹ nhàng – nơi lý tưởng cho kỳ nghỉ lãng mạn hoặc tuần trăng mật ngắn ngày.' WHERE `id` = 2;

UPDATE `room_types` SET `description` = 'Suite 65m² với phòng khách riêng biệt tách hẳn phòng ngủ, view biển 180 độ. Hai phòng ngủ (1 King + 1 Twin), hai phòng tắm đá cẩm thạch với vòi sen mưa và bồn tắm ngâm. Sofa da Ý, bàn ăn 4 chỗ, TV 55 inch trong phòng khách. Thiết kế phù hợp gia đình 4 người, nhóm bạn đi du lịch hoặc khách muốn không gian như căn hộ cao cấp tại Light Hotel với tầm nhìn thoáng.' WHERE `id` = 3;

UPDATE `room_types` SET `description` = 'Family Suite 55m² được thiết kế riêng cho gia đình có trẻ em. Hai giường Queen, khu vực sinh hoạt chung rộng rãi, góc vui chơi an toàn cho bé. Có sẵn nôi cũi, ghế ăn trẻ em, tủ lạnh mini với sữa và snack. Ban công view vườn xanh, không gian ấm áp và tiện nghi. Nhiều gia đình chọn phòng này cho kỳ nghỉ hè hoặc dịp Tết.' WHERE `id` = 4;

UPDATE `room_types` SET `description` = 'VIP Ocean View là phòng thượng hạng với bồn Jacuzzi riêng ngoài ban công, ngắm hoàng hôn và bình minh trực tiếp trên biển. Diện tích 45m², nội thất da thật và gỗ quý, TV 65 inch, minibar cao cấp miễn phí. Dịch vụ quản gia cá nhân, dọn phòng 2 lần/ngày. Trải nghiệm hoàng gia dành cho cặp đôi kỷ niệm đặc biệt, tuần trăng mật hoặc khách VIP.' WHERE `id` = 5;

UPDATE `room_types` SET `description` = 'Junior Suite 48m² với không gian mở, kết nối phòng ngủ và khu sinh hoạt. Giường King-size kèm sofa bed có thể mở thêm chỗ ngủ. View thành phố và biển xa, phòng tắm rộng với vòi sen mưa. Phù hợp gia đình 3 người, nhóm bạn thân hoặc cặp đôi muốn thêm không gian thoải mái. Giá hợp lý hơn Suite nhưng vẫn đầy đủ tiện nghi cao cấp.' WHERE `id` = 6;

UPDATE `room_types` SET `description` = 'Ocean Front Deluxe nằm ở tầng thấp nhất, sát biển nhất – ban công chỉ cách sóng vài chục mét. Nghe rõ tiếng sóng vỗ, ngửi mùi muối mặn mỗi sáng. Diện tích 38m², nội thất gỗ tự nhiên tông xanh biển, đèn đọc sách ấm áp. Thức dậy với bình minh, cà phê sáng trên ban công – trải nghiệm gần gũi đại dương nhất tại resort.' WHERE `id` = 7;

UPDATE `room_types` SET `description` = 'Presidential Suite 120m² – căn phòng cao cấp nhất tại Light Hotel. Tầng cao nhất, view 360 độ bao quát biển và thành phố. Phòng khách lớn, phòng ăn riêng 8 chỗ, bồn tắm ngâm đá cẩm thạch view biển 180 độ. Dịch vụ quản gia riêng 24/7, xe đưa đón sân bay miễn phí. Dành cho khách VIP, sự kiện đặc biệt hoặc gia đình đa thế hệ muốn trải nghiệm xa xỉ trọn vẹn.' WHERE `id` = 8;

-- ============================================================
-- 5. CẬP NHẬT MÔ TẢ PHÒNG (ROOMS) - CHI TIẾT HƠN
-- ============================================================

UPDATE `rooms` SET `description` = 'Phòng 101 nằm ở tầng trệt, view vườn nhiệt đới xanh mát và hồ bơi lấp lánh. Giường King-size êm ái với chăn ga cotton Ai Cập 500 thread count, ban công nhỏ lý tưởng để nhâm nhi cà phê sáng nghe tiếng chim hót. Không gian tối giản hiện đại, điều hòa hai chiều, TV 43 inch. Phù hợp cặp đôi hoặc khách lẻ muốn sự thoải mái tiết kiệm nhưng vẫn đầy đủ tiện nghi 5 sao. Gần lối ra bãi biển riêng của resort.' WHERE `id` = 1;

UPDATE `rooms` SET `description` = 'Phòng 205 tầng 2, ban công rộng thoáng ngắm trọn khung cảnh khuôn viên khách sạn. Nội thất gỗ óc chó cao cấp, giường Queen-size lớn, ánh sáng tự nhiên tràn ngập qua cửa kính floor-to-ceiling. Minibar miễn phí, máy pha Nespresso – nơi lý tưởng cho những cặp đôi tìm kiếm sự lãng mạn và riêng tư. Buổi tối ngồi ban công ngắm đèn thuyền đánh cá trên biển là trải nghiệm khó quên.' WHERE `id` = 2;

UPDATE `rooms` SET `description` = 'Suite 301 rộng 65m² với phòng khách riêng biệt, view panorama biển 180 độ. Hai phòng ngủ (một King, một Twin), hai phòng tắm đá cẩm thạch với vòi sen mưa và bồn tắm ngâm. Sofa da Ý, bàn ăn nhỏ – hoàn hảo cho gia đình nhỏ hoặc nhóm bạn muốn không gian như căn hộ cao cấp ngay cạnh biển. Cửa kính lớn từ phòng khách ra ban công, view biển không bị che khuất.' WHERE `id` = 3;

UPDATE `rooms` SET `description` = 'Phòng 402 thiết kế dành riêng cho gia đình: hai giường Queen, khu vực sinh hoạt chung rộng rãi, góc vui chơi an toàn cho trẻ nhỏ. Ban công view vườn xanh, tủ lạnh mini đầy đủ sữa và snack trẻ em. Có sẵn nôi cũi và ghế ăn trẻ em theo yêu cầu. Không gian ấm áp, tiện nghi an toàn – nơi cả nhà cùng tạo kỷ niệm đáng nhớ. Nhiều khách quay lại đặt phòng này cho kỳ nghỉ hè.' WHERE `id` = 4;

UPDATE `rooms` SET `description` = 'Phòng VIP 501 – phòng thượng hạng với bồn Jacuzzi riêng ngoài ban công, ngắm hoàng hôn và bình minh ngay từ ban công phòng. Dịch vụ quản gia cá nhân, nội thất da thật và gỗ quý, TV 65 inch, minibar cao cấp miễn phí. Trải nghiệm hoàng gia đích thực cho cặp đôi kỷ niệm đặc biệt hoặc khách VIP. Đêm đầu tiên ngâm mình trong Jacuzzi ngắm pháo hoa (nếu có sự kiện) là kỷ niệm không quên.' WHERE `id` = 5;

UPDATE `rooms` SET `description` = 'Phòng 102 phiên bản yên tĩnh hơn của Standard, view vườn và hồ bơi, gần lối ra bãi biển riêng. Giường King với gối memory foam, ánh sáng dịu nhẹ – lựa chọn tuyệt vời cho khách muốn nghỉ ngơi sâu và tái tạo năng lượng. Cách xa khu vực nhà hàng nên rất yên tĩnh vào buổi sáng. Khách công tác thường chọn phòng này.' WHERE `id` = 6;

UPDATE `rooms` SET `description` = 'Phòng 206 tầng cao hơn, gió biển mát mẻ quanh năm. Ban công lớn, view biển rõ nét hơn, nội thất tông màu xanh ngọc sang trọng. Lý tưởng cho những ai yêu sự thư thái và muốn thức dậy với tiếng sóng vỗ. Buổi sáng mở cửa ban công là gió biển ùa vào, cảm giác rất sảng khoái.' WHERE `id` = 7;

UPDATE `rooms` SET `description` = 'Suite 302 view biển trực diện, phòng khách rộng với ghế sofa dài, bàn làm việc tiện lợi. Hai phòng tắm cao cấp với sản phẩm dưỡng da L''Occitane – phù hợp cho gia đình hoặc nhóm bạn muốn không gian riêng tư sang trọng. Ban công lớn có bàn ăn ngoài trời, lý tưởng cho bữa sáng hoặc tối lãng mạn.' WHERE `id` = 8;

UPDATE `rooms` SET `description` = 'Phòng 403 góc yên bình, view vườn và một phần biển xa. Có nôi cũi và ghế ăn trẻ em sẵn sàng. Không gian ấm cúng, đầy đủ đồ dùng gia đình – nơi bố mẹ và các bé đều thoải mái. Gần khu vui chơi trẻ em của resort, tiện đưa con đi chơi. Nhiều phản hồi tốt từ khách có con nhỏ.' WHERE `id` = 9;

UPDATE `rooms` SET `description` = 'Phòng 103 gần quầy lễ tân và nhà hàng, tiện di chuyển. View vườn tươi mát, nội thất sạch sẽ hiện đại – lựa chọn kinh tế nhưng vẫn giữ chất lượng 5 sao. Phù hợp khách đến muộn hoặc đi sớm, không phải đi xa. Bữa sáng buffet chỉ vài bước chân.' WHERE `id` = 10;

UPDATE `rooms` SET `description` = 'Phòng 207 không gian mở rộng rãi với giường King-size lớn, khu vực sofa thoải mái view biển xa và thành phố về đêm. Phù hợp gia đình 3 người hoặc cặp đôi muốn thêm không gian sinh hoạt riêng. Có thể thêm giường phụ nếu cần. View đèn thành phố về đêm rất đẹp.' WHERE `id` = 11;

UPDATE `rooms` SET `description` = 'Suite 303 phòng sát biển nhất – ban công chỉ cách sóng vài chục mét, tiếng sóng vỗ ru ngủ mỗi đêm. Nội thất tông trắng – xanh biển, sàn gỗ tự nhiên, cảm giác như đang sống ngay trên đại dương. Hai phòng ngủ đều có view biển. Khách đánh giá rất cao về vị trí và view.' WHERE `id` = 12;

UPDATE `rooms` SET `description` = 'Phòng 404 Family cao cấp: phòng ăn riêng, bếp nhỏ, bồn tắm ngâm view biển 180 độ, phòng ngủ master với giường King siêu lớn. Dịch vụ quản gia 24/7. Dành cho dịp đặc biệt, tuần trăng mật gia đình hoặc khách VIP. Có máy giặt mini trong phòng.' WHERE `id` = 13;

UPDATE `rooms` SET `description` = 'Phòng VIP 502 tầng cao, view biển mênh mông, Jacuzzi ngoài trời ấm áp vào buổi tối. Nội thất tối giản hiện đại, ánh sáng dịu nhẹ, minibar đầy đủ rượu vang và trái cây tươi – sự kết hợp hoàn hảo giữa thư giãn và đẳng cấp. Phù hợp cặp đôi kỷ niệm ngày cưới.' WHERE `id` = 14;

UPDATE `rooms` SET `description` = 'Phòng 104 view hồ bơi, ánh sáng tự nhiên dồi dào, giường đôi êm ái, bàn làm việc nhỏ gọn. Phù hợp khách công tác ngắn ngày hoặc du khách muốn sự tiện lợi gần khu vực trung tâm resort. Nhìn xuống hồ bơi vô cực rất đẹp, đặc biệt lúc hoàng hôn.' WHERE `id` = 15;

UPDATE `rooms` SET `description` = 'Junior Suite 401 tầng trung, view thành phố và biển xa, không gian mở với sofa bed linh hoạt. Giường King-size, phòng tắm rộng rãi với vòi sen mưa – lựa chọn lý tưởng cho gia đình nhỏ hoặc nhóm 3 người. Giá hợp lý hơn Suite nhưng vẫn rộng rãi. Nhiều khách quay lại chọn phòng này.' WHERE `id` = 16;

UPDATE `rooms` SET `description` = 'Ocean Front 208 tầng thấp, ban công sát biển, nghe rõ tiếng sóng vỗ và ngửi mùi muối mặn. Nội thất gỗ tự nhiên, đèn đọc sách ấm áp – nơi lý tưởng để thư giãn hoàn toàn. Thức dậy nghe sóng, cà phê sáng ngắm bình minh – đáng giá từng đồng. Một trong những phòng được đặt nhiều nhất.' WHERE `id` = 17;

UPDATE `rooms` SET `description` = 'Presidential Suite 601 tầng cao nhất, view 360 độ bao quát biển và thành phố. Phòng khách lớn, phòng ăn riêng, bồn tắm ngâm đá cẩm thạch, dịch vụ quản gia riêng – trải nghiệm xa xỉ nhất tại resort. Dành cho sự kiện đặc biệt, khách VIP hoặc gia đình đa thế hệ. Có phòng họp nhỏ kèm theo.' WHERE `id` = 18;

UPDATE `rooms` SET `description` = 'Phòng 209 Deluxe tầng cao, view biển trực diện, ban công rộng với ghế nằm thư giãn. Nội thất tông beige ấm áp, TV thông minh 55 inch, minibar miễn phí – sự cân bằng hoàn hảo giữa sang trọng và thoải mái. Gió biển mát, không cần bật điều hòa vào buổi tối.' WHERE `id` = 19;

UPDATE `rooms` SET `description` = 'Phòng 105 Standard gần bãi biển, view vườn nhiệt đới, giường King với gối hypoallergenic. Không gian sạch sẽ yên tĩnh – phù hợp khách muốn gần biển nhưng vẫn tiết kiệm. Chỉ vài bước ra bãi biển riêng. Khách nước ngoài thường chọn phòng này.' WHERE `id` = 20;

UPDATE `rooms` SET `description` = 'Phòng 405 Family tầng thấp, view vườn và hồ bơi, hai giường Queen lớn, khu vực sinh hoạt riêng. Có ghế ăn trẻ em, tủ đồ rộng – nơi cả gia đình quây quần vui vẻ. Gần bể bơi trẻ em, tiện cho bố mẹ trông con. Thiết kế an toàn cho trẻ nhỏ.' WHERE `id` = 21;

UPDATE `rooms` SET `description` = 'Phòng VIP 503 tầng trung, Jacuzzi ngoài trời view hoàng hôn, nội thất da cao cấp, dịch vụ dọn phòng hai lần/ngày. Trải nghiệm thượng lưu dành cho cặp đôi hoặc khách VIP. Buổi tối ngâm Jacuzzi ngắm mặt trời lặn là khoảnh khắc đáng nhớ.' WHERE `id` = 22;

UPDATE `rooms` SET `description` = 'Suite 304 view biển, phòng khách riêng với sofa dài, hai phòng ngủ tiện nghi. Ban công lớn, ánh sáng tự nhiên – lý tưởng cho nhóm bạn hoặc gia đình muốn không gian rộng rãi. Có thể tổ chức tiệc nhỏ trong phòng. View biển từ cả phòng khách và phòng ngủ.' WHERE `id` = 23;

UPDATE `rooms` SET `description` = 'Junior Suite 402 view pháo hoa (khi có sự kiện), không gian mở, giường King + sofa bed, phòng tắm mưa. Phù hợp dịp lễ hội hoặc kỷ niệm đặc biệt. Đêm giao thừa hoặc các sự kiện đặc biệt trong năm, view từ phòng rất tuyệt. Nên đặt sớm cho các dịp lễ.' WHERE `id` = 24;

UPDATE `rooms` SET `description` = 'Ocean Front 210 tầng cao, ban công rộng, view biển 180 độ, nội thất hiện đại tông trắng – xanh. Tiếng sóng vỗ nhẹ nhàng – nơi nghỉ dưỡng hoàn hảo cho cặp đôi. Yên tĩnh hơn các phòng tầng thấp. Khách tuần trăng mật đánh giá cao.' WHERE `id` = 25;

UPDATE `rooms` SET `description` = 'Phòng 106 Standard view vườn, gần khu gym và spa, giường King êm ái, bàn làm việc nhỏ. Lựa chọn tiện lợi cho khách kết hợp nghỉ dưỡng và tập luyện. Đi bộ 1 phút đến phòng gym. Nhiều khách công tác chọn phòng này.' WHERE `id` = 26;

UPDATE `rooms` SET `description` = 'Phòng 211 Deluxe tầng cao, gió biển mát, ban công view biển và thành phố. Nội thất gỗ cao cấp, máy pha cà phê Nespresso – sự thư giãn đẳng cấp. View đèn thành phố về đêm rất đẹp. Phù hợp cặp đôi muốn không gian lãng mạn.' WHERE `id` = 27;

UPDATE `rooms` SET `description` = 'Phòng 406 Family view biển xa, hai giường Queen, khu vực sinh hoạt chung rộng. Góc vui chơi trẻ em, tủ lạnh mini – kỳ nghỉ gia đình đáng nhớ. View vừa biển vừa vườn, không gian thoáng đãng. Được đánh giá tốt về vệ sinh và tiện nghi.' WHERE `id` = 28;

UPDATE `rooms` SET `description` = 'Phòng VIP 504 tầng cao, Jacuzzi riêng, view biển mênh mông, dịch vụ quản gia. Nội thất sang trọng, minibar cao cấp – trải nghiệm hoàng gia. Phù hợp kỷ niệm đặc biệt. Có thể yêu cầu trang trí phòng cho dịp sinh nhật hoặc kỷ niệm.' WHERE `id` = 29;

UPDATE `rooms` SET `description` = 'Junior Suite 403 tầng trung, view thành phố và biển, không gian mở linh hoạt. Giường King + sofa bed – phù hợp gia đình 3 người hoặc nhóm nhỏ. Giá tốt cho không gian rộng. Nhiều khách đặt cho kỳ nghỉ dài ngày.' WHERE `id` = 30;

UPDATE `rooms` SET `description` = 'Ocean Front 212 tầng thấp, sát biển, ban công nghe sóng vỗ rõ ràng, nội thất tông xanh biển dịu mát. Không gian thoáng đãng, lý tưởng cho những ai muốn gần gũi với đại dương nhất. Một trong những phòng có view biển đẹp nhất.' WHERE `id` = 31;

UPDATE `rooms` SET `description` = 'Suite 305 tầng cao, view biển và thành phố lung linh về đêm, phòng khách riêng biệt, hai phòng ngủ thoải mái. Ban công lớn, nội thất hiện đại – phù hợp nhóm bạn hoặc gia đình muốn sự riêng tư cao cấp. View hai mặt: biển và thành phố.' WHERE `id` = 32;

UPDATE `rooms` SET `description` = 'Phòng 107 Standard tầng thấp, view vườn xanh mát, giường King êm ái, không gian yên tĩnh gần lối ra bãi biển. Lựa chọn tiết kiệm nhưng vẫn đầy đủ tiện nghi resort 5 sao. Gần bãi đỗ xe, tiện cho khách tự lái xe.' WHERE `id` = 33;

UPDATE `rooms` SET `description` = 'Phòng 213 Deluxe view biển trực diện, ban công rộng rãi, nội thất gỗ tự nhiên cao cấp, máy pha cà phê Nespresso miễn phí. Gió biển mát mẻ, ánh sáng tự nhiên – nơi nghỉ dưỡng thư thái cho cặp đôi. Phòng mới trang bị, nội thất còn rất mới.' WHERE `id` = 34;

UPDATE `rooms` SET `description` = 'Phòng 407 Family Ocean Garden tầng thấp, view vừa vườn vừa biển, gần bể bơi vô cực và khu vui chơi trẻ em. Không gian rộng rãi, đầy đủ tiện nghi an toàn – lựa chọn hoàn hảo cho kỳ nghỉ hè gia đình. Trẻ em thích vì gần hồ bơi và khu chơi.' WHERE `id` = 35;

-- ============================================================
-- 6. THÊM BOOKINGS MỚI (50+ đặt phòng)
-- ============================================================

INSERT INTO `bookings` (`user_id`, `room_id`, `check_in`, `check_out`, `actual_check_in`, `actual_check_out`, `guests`, `total_price`, `status`, `created_at`, `updated_at`) VALUES
(11, 6, '2024-12-20', '2024-12-23', '2024-12-20 14:00:00', '2024-12-23 11:00:00', 2, 2550000, 'completed', '2024-12-10 09:15:00', '2024-12-23 11:30:00'),
(12, 10, '2025-01-02', '2025-01-05', '2025-01-02 15:30:00', '2025-01-05 10:00:00', 2, 2550000, 'completed', '2024-12-28 11:20:00', '2025-01-05 10:15:00'),
(13, 15, '2025-01-15', '2025-01-17', '2025-01-15 13:00:00', '2025-01-17 11:00:00', 2, 1700000, 'completed', '2025-01-05 16:45:00', '2025-01-17 11:20:00'),
(14, 7, '2025-02-01', '2025-02-04', '2025-02-01 14:30:00', '2025-02-04 10:30:00', 2, 4050000, 'completed', '2025-01-22 10:00:00', '2025-02-04 11:00:00'),
(15, 19, '2025-02-14', '2025-02-16', '2025-02-14 15:00:00', '2025-02-16 11:00:00', 2, 2700000, 'completed', '2025-02-01 14:20:00', '2025-02-16 11:15:00'),
(16, 21, '2025-03-08', '2025-03-11', '2025-03-08 14:00:00', '2025-03-11 10:00:00', 4, 5550000, 'completed', '2025-02-25 09:30:00', '2025-03-11 10:30:00'),
(17, 25, '2025-03-20', '2025-03-23', '2025-03-20 13:30:00', '2025-03-23 11:00:00', 2, 4950000, 'completed', '2025-03-05 11:45:00', '2025-03-23 11:20:00'),
(18, 3, '2025-04-01', '2025-04-05', '2025-04-01 14:00:00', '2025-04-05 10:00:00', 4, 8400000, 'completed', '2025-03-18 16:20:00', '2025-04-05 10:45:00'),
(19, 27, '2025-04-18', '2025-04-21', '2025-04-18 15:00:00', '2025-04-21 11:00:00', 2, 4050000, 'completed', '2025-04-05 10:30:00', '2025-04-21 11:10:00'),
(20, 9, '2025-05-01', '2025-05-04', '2025-05-01 13:00:00', '2025-05-04 10:30:00', 4, 5550000, 'completed', '2025-04-20 14:15:00', '2025-05-04 10:50:00'),
(11, 12, '2025-05-15', '2025-05-18', '2025-05-15 14:30:00', '2025-05-18 11:00:00', 4, 6300000, 'completed', '2025-05-01 09:00:00', '2025-05-18 11:25:00'),
(12, 17, '2025-05-25', '2025-05-28', '2025-05-25 15:00:00', '2025-05-28 10:00:00', 2, 4950000, 'completed', '2025-05-10 11:30:00', '2025-05-28 10:20:00'),
(21, 1, '2025-06-01', '2025-06-04', '2025-06-01 14:00:00', '2025-06-04 11:00:00', 2, 2550000, 'completed', '2025-05-20 16:45:00', '2025-06-04 11:15:00'),
(22, 22, '2025-06-10', '2025-06-13', '2025-06-10 13:30:00', '2025-06-13 10:30:00', 2, 8400000, 'completed', '2025-05-28 10:20:00', '2025-06-13 10:45:00'),
(23, 28, '2025-06-20', '2025-06-24', '2025-06-20 14:00:00', '2025-06-24 11:00:00', 4, 7400000, 'completed', '2025-06-05 09:15:00', '2025-06-24 11:30:00'),
(24, 31, '2025-07-05', '2025-07-08', '2025-07-05 15:00:00', '2025-07-08 10:00:00', 2, 4950000, 'completed', '2025-06-22 14:30:00', '2025-07-08 10:20:00'),
(25, 5, '2025-07-15', '2025-07-18', '2025-07-15 14:30:00', '2025-07-18 11:00:00', 2, 8400000, 'completed', '2025-07-01 11:00:00', '2025-07-18 11:10:00'),
(13, 8, '2025-07-25', '2025-07-28', NULL, NULL, 4, 6300000, 'cancelled', '2025-07-10 10:45:00', '2025-07-20 09:00:00'),
(14, 14, '2025-08-01', '2025-08-05', '2025-08-01 13:00:00', '2025-08-05 10:30:00', 2, 11200000, 'completed', '2025-07-15 16:20:00', '2025-08-05 10:55:00'),
(15, 18, '2025-08-20', '2025-08-23', '2025-08-20 14:00:00', '2025-08-23 11:00:00', 4, 13500000, 'completed', '2025-08-05 09:30:00', '2025-08-23 11:25:00'),
(26, 6, '2025-09-01', '2025-09-05', '2025-09-01 15:00:00', '2025-09-05 10:00:00', 2, 3400000, 'completed', '2025-08-20 11:15:00', '2025-09-05 10:30:00'),
(27, 11, '2025-09-12', '2025-09-15', '2025-09-12 14:00:00', '2025-09-15 11:00:00', 3, 4050000, 'completed', '2025-08-28 14:45:00', '2025-09-15 11:15:00'),
(28, 23, '2025-09-25', '2025-09-28', '2025-09-25 13:30:00', '2025-09-28 10:30:00', 4, 6300000, 'completed', '2025-09-10 10:00:00', '2025-09-28 10:50:00'),
(29, 29, '2025-10-05', '2025-10-08', '2025-10-05 14:00:00', '2025-10-08 11:00:00', 2, 8400000, 'completed', '2025-09-22 16:30:00', '2025-10-08 11:20:00'),
(30, 2, '2025-10-15', '2025-10-18', '2025-10-15 15:00:00', '2025-10-18 10:00:00', 2, 4050000, 'completed', '2025-10-01 09:45:00', '2025-10-18 10:25:00'),
(16, 34, '2025-10-25', '2025-10-28', '2025-10-25 14:30:00', '2025-10-28 11:00:00', 2, 4050000, 'completed', '2025-10-12 11:20:00', '2025-10-28 11:10:00'),
(17, 4, '2025-11-01', '2025-11-05', '2025-11-01 13:00:00', '2025-11-05 10:30:00', 4, 7400000, 'completed', '2025-10-20 14:00:00', '2025-11-05 10:45:00'),
(18, 20, '2025-11-10', '2025-11-13', '2025-11-10 15:00:00', '2025-11-13 11:00:00', 2, 2550000, 'completed', '2025-10-28 10:15:00', '2025-11-13 11:15:00'),
(19, 26, '2025-11-20', '2025-11-23', '2025-11-20 14:00:00', '2025-11-23 10:00:00', 2, 2550000, 'completed', '2025-11-05 16:30:00', '2025-11-23 10:20:00'),
(20, 30, '2025-12-01', '2025-12-04', '2025-12-01 13:30:00', '2025-12-04 11:00:00', 3, 5250000, 'completed', '2025-11-18 09:30:00', '2025-12-04 11:25:00'),
(21, 33, '2025-12-10', '2025-12-13', '2025-12-10 14:00:00', '2025-12-13 10:30:00', 2, 2550000, 'completed', '2025-11-25 11:45:00', '2025-12-13 10:50:00'),
(22, 35, '2025-12-20', '2025-12-24', '2025-12-20 15:00:00', '2025-12-24 11:00:00', 4, 7400000, 'completed', '2025-12-05 14:20:00', '2025-12-24 11:20:00'),
(23, 13, '2026-01-05', '2026-01-08', '2026-01-05 14:30:00', '2026-01-08 10:00:00', 4, 5550000, 'completed', '2025-12-22 10:00:00', '2026-01-08 10:30:00'),
(24, 16, '2026-01-15', '2026-01-18', '2026-01-15 13:00:00', '2026-01-18 11:00:00', 3, 5250000, 'completed', '2026-01-02 16:15:00', '2026-01-18 11:15:00'),
(25, 24, '2026-01-25', '2026-01-28', '2026-01-25 15:00:00', '2026-01-28 10:30:00', 3, 5250000, 'completed', '2026-01-10 09:45:00', '2026-01-28 10:45:00'),
(26, 7, '2026-02-05', '2026-02-08', '2026-02-05 14:00:00', '2026-02-08 11:00:00', 2, 4050000, 'completed', '2026-01-22 11:30:00', '2026-02-08 11:20:00'),
(27, 19, '2026-02-14', '2026-02-17', '2026-02-14 13:30:00', '2026-02-17 10:00:00', 2, 4050000, 'completed', '2026-02-01 14:45:00', '2026-02-17 10:25:00'),
(28, 10, '2026-02-25', '2026-02-28', NULL, NULL, 2, 2550000, 'pending', '2026-02-15 10:20:00', '2026-02-15 10:20:00'),
(29, 21, '2026-03-01', '2026-03-05', NULL, NULL, 4, 7400000, 'confirmed', '2026-02-20 09:15:00', '2026-02-20 09:15:00'),
(30, 5, '2026-03-10', '2026-03-14', NULL, NULL, 2, 11200000, 'confirmed', '2026-03-01 16:30:00', '2026-03-01 16:30:00'),
(31, 15, '2026-03-20', '2026-03-23', NULL, NULL, 2, 2550000, 'pending', '2026-03-15 11:00:00', '2026-03-15 11:00:00'),
(32, 27, '2026-03-25', '2026-03-28', NULL, NULL, 2, 4050000, 'confirmed', '2026-03-18 14:20:00', '2026-03-18 14:20:00'),
(33, 1, '2026-04-01', '2026-04-05', NULL, NULL, 2, 3400000, 'pending', '2026-03-25 10:45:00', '2026-03-25 10:45:00'),
(34, 18, '2026-04-10', '2026-04-13', NULL, NULL, 4, 13500000, 'confirmed', '2026-04-01 09:30:00', '2026-04-01 09:30:00'),
(35, 9, '2026-04-15', '2026-04-18', NULL, NULL, 4, 5550000, 'pending', '2026-04-08 15:00:00', '2026-04-08 15:00:00');

-- ============================================================
-- 7. THÊM ROOM_BOOKED_DATES CHO CÁC BOOKING MỚI (id 7-56)
-- ============================================================

INSERT INTO `room_booked_dates` (`room_id`, `booked_date`, `booking_id`, `created_at`, `updated_at`)
SELECT 6, d.dt, 7, NOW(), NOW() FROM (SELECT '2024-12-20' AS dt UNION SELECT '2024-12-21' UNION SELECT '2024-12-22') d
UNION ALL SELECT 10, d.dt, 8, NOW(), NOW() FROM (SELECT '2025-01-02' AS dt UNION SELECT '2025-01-03' UNION SELECT '2025-01-04') d
UNION ALL SELECT 15, d.dt, 9, NOW(), NOW() FROM (SELECT '2025-01-15' AS dt UNION SELECT '2025-01-16') d
UNION ALL SELECT 7, d.dt, 10, NOW(), NOW() FROM (SELECT '2025-02-01' AS dt UNION SELECT '2025-02-02' UNION SELECT '2025-02-03') d
UNION ALL SELECT 19, d.dt, 11, NOW(), NOW() FROM (SELECT '2025-02-14' AS dt UNION SELECT '2025-02-15') d
UNION ALL SELECT 21, d.dt, 12, NOW(), NOW() FROM (SELECT '2025-03-08' AS dt UNION SELECT '2025-03-09' UNION SELECT '2025-03-10') d
UNION ALL SELECT 25, d.dt, 13, NOW(), NOW() FROM (SELECT '2025-03-20' AS dt UNION SELECT '2025-03-21' UNION SELECT '2025-03-22') d
UNION ALL SELECT 3, d.dt, 14, NOW(), NOW() FROM (SELECT '2025-04-01' AS dt UNION SELECT '2025-04-02' UNION SELECT '2025-04-03' UNION SELECT '2025-04-04') d
UNION ALL SELECT 27, d.dt, 15, NOW(), NOW() FROM (SELECT '2025-04-18' AS dt UNION SELECT '2025-04-19' UNION SELECT '2025-04-20') d
UNION ALL SELECT 9, d.dt, 16, NOW(), NOW() FROM (SELECT '2025-05-01' AS dt UNION SELECT '2025-05-02' UNION SELECT '2025-05-03') d
UNION ALL SELECT 12, d.dt, 17, NOW(), NOW() FROM (SELECT '2025-05-15' AS dt UNION SELECT '2025-05-16' UNION SELECT '2025-05-17') d
UNION ALL SELECT 17, d.dt, 18, NOW(), NOW() FROM (SELECT '2025-05-25' AS dt UNION SELECT '2025-05-26' UNION SELECT '2025-05-27') d
UNION ALL SELECT 1, d.dt, 19, NOW(), NOW() FROM (SELECT '2025-06-01' AS dt UNION SELECT '2025-06-02' UNION SELECT '2025-06-03') d
UNION ALL SELECT 22, d.dt, 20, NOW(), NOW() FROM (SELECT '2025-06-10' AS dt UNION SELECT '2025-06-11' UNION SELECT '2025-06-12') d
UNION ALL SELECT 28, d.dt, 21, NOW(), NOW() FROM (SELECT '2025-06-20' AS dt UNION SELECT '2025-06-21' UNION SELECT '2025-06-22' UNION SELECT '2025-06-23') d
UNION ALL SELECT 31, d.dt, 22, NOW(), NOW() FROM (SELECT '2025-07-05' AS dt UNION SELECT '2025-07-06' UNION SELECT '2025-07-07') d
UNION ALL SELECT 5, d.dt, 23, NOW(), NOW() FROM (SELECT '2025-07-15' AS dt UNION SELECT '2025-07-16' UNION SELECT '2025-07-17') d
UNION ALL SELECT 14, d.dt, 25, NOW(), NOW() FROM (SELECT '2025-08-01' AS dt UNION SELECT '2025-08-02' UNION SELECT '2025-08-03' UNION SELECT '2025-08-04') d
UNION ALL SELECT 18, d.dt, 26, NOW(), NOW() FROM (SELECT '2025-08-20' AS dt UNION SELECT '2025-08-21' UNION SELECT '2025-08-22') d
UNION ALL SELECT 6, d.dt, 27, NOW(), NOW() FROM (SELECT '2025-09-01' AS dt UNION SELECT '2025-09-02' UNION SELECT '2025-09-03' UNION SELECT '2025-09-04') d
UNION ALL SELECT 11, d.dt, 28, NOW(), NOW() FROM (SELECT '2025-09-12' AS dt UNION SELECT '2025-09-13' UNION SELECT '2025-09-14') d
UNION ALL SELECT 23, d.dt, 29, NOW(), NOW() FROM (SELECT '2025-09-25' AS dt UNION SELECT '2025-09-26' UNION SELECT '2025-09-27') d
UNION ALL SELECT 29, d.dt, 30, NOW(), NOW() FROM (SELECT '2025-10-05' AS dt UNION SELECT '2025-10-06' UNION SELECT '2025-10-07') d
UNION ALL SELECT 2, d.dt, 31, NOW(), NOW() FROM (SELECT '2025-10-15' AS dt UNION SELECT '2025-10-16' UNION SELECT '2025-10-17') d
UNION ALL SELECT 34, d.dt, 32, NOW(), NOW() FROM (SELECT '2025-10-25' AS dt UNION SELECT '2025-10-26' UNION SELECT '2025-10-27') d
UNION ALL SELECT 4, d.dt, 33, NOW(), NOW() FROM (SELECT '2025-11-01' AS dt UNION SELECT '2025-11-02' UNION SELECT '2025-11-03' UNION SELECT '2025-11-04') d
UNION ALL SELECT 20, d.dt, 34, NOW(), NOW() FROM (SELECT '2025-11-10' AS dt UNION SELECT '2025-11-11' UNION SELECT '2025-11-12') d
UNION ALL SELECT 26, d.dt, 35, NOW(), NOW() FROM (SELECT '2025-11-20' AS dt UNION SELECT '2025-11-21' UNION SELECT '2025-11-22') d
UNION ALL SELECT 30, d.dt, 36, NOW(), NOW() FROM (SELECT '2025-12-01' AS dt UNION SELECT '2025-12-02' UNION SELECT '2025-12-03') d
UNION ALL SELECT 33, d.dt, 37, NOW(), NOW() FROM (SELECT '2025-12-10' AS dt UNION SELECT '2025-12-11' UNION SELECT '2025-12-12') d
UNION ALL SELECT 35, d.dt, 38, NOW(), NOW() FROM (SELECT '2025-12-20' AS dt UNION SELECT '2025-12-21' UNION SELECT '2025-12-22' UNION SELECT '2025-12-23') d
UNION ALL SELECT 13, d.dt, 39, NOW(), NOW() FROM (SELECT '2026-01-05' AS dt UNION SELECT '2026-01-06' UNION SELECT '2026-01-07') d
UNION ALL SELECT 16, d.dt, 40, NOW(), NOW() FROM (SELECT '2026-01-15' AS dt UNION SELECT '2026-01-16' UNION SELECT '2026-01-17') d
UNION ALL SELECT 24, d.dt, 41, NOW(), NOW() FROM (SELECT '2026-01-25' AS dt UNION SELECT '2026-01-26' UNION SELECT '2026-01-27') d
UNION ALL SELECT 7, d.dt, 42, NOW(), NOW() FROM (SELECT '2026-02-05' AS dt UNION SELECT '2026-02-06' UNION SELECT '2026-02-07') d
UNION ALL SELECT 19, d.dt, 43, NOW(), NOW() FROM (SELECT '2026-02-14' AS dt UNION SELECT '2026-02-15' UNION SELECT '2026-02-16') d
UNION ALL SELECT 10, d.dt, 44, NOW(), NOW() FROM (SELECT '2026-02-25' AS dt UNION SELECT '2026-02-26' UNION SELECT '2026-02-27') d
UNION ALL SELECT 21, d.dt, 45, NOW(), NOW() FROM (SELECT '2026-03-01' AS dt UNION SELECT '2026-03-02' UNION SELECT '2026-03-03' UNION SELECT '2026-03-04') d
UNION ALL SELECT 5, d.dt, 46, NOW(), NOW() FROM (SELECT '2026-03-10' AS dt UNION SELECT '2026-03-11' UNION SELECT '2026-03-12' UNION SELECT '2026-03-13') d
UNION ALL SELECT 15, d.dt, 47, NOW(), NOW() FROM (SELECT '2026-03-20' AS dt UNION SELECT '2026-03-21' UNION SELECT '2026-03-22') d
UNION ALL SELECT 27, d.dt, 48, NOW(), NOW() FROM (SELECT '2026-03-25' AS dt UNION SELECT '2026-03-26' UNION SELECT '2026-03-27') d
UNION ALL SELECT 1, d.dt, 49, NOW(), NOW() FROM (SELECT '2026-04-01' AS dt UNION SELECT '2026-04-02' UNION SELECT '2026-04-03' UNION SELECT '2026-04-04') d
UNION ALL SELECT 18, d.dt, 50, NOW(), NOW() FROM (SELECT '2026-04-10' AS dt UNION SELECT '2026-04-11' UNION SELECT '2026-04-12') d
UNION ALL SELECT 9, d.dt, 51, NOW(), NOW() FROM (SELECT '2026-04-15' AS dt UNION SELECT '2026-04-16' UNION SELECT '2026-04-17') d;

-- Lưu ý: Booking 24 (room 8, cancelled) không thêm room_booked_dates

-- ============================================================
-- 8. THÊM PAYMENTS CHO CÁC BOOKING ĐÃ THANH TOÁN
-- ============================================================

INSERT INTO `payments` (`booking_id`, `amount`, `method`, `transaction_id`, `status`, `paid_at`, `created_at`, `updated_at`) VALUES
(7, 2550000, 'vnpay', 'VNP202412201234', 'paid', '2024-12-10 09:30:00', '2024-12-10 09:30:00', '2024-12-10 09:30:00'),
(8, 2550000, 'bank_transfer', 'BANK20250102156', 'paid', '2024-12-30 14:20:00', '2024-12-30 14:20:00', '2024-12-30 14:20:00'),
(9, 1700000, 'cash', NULL, 'paid', '2025-01-15 13:30:00', '2025-01-15 13:30:00', '2025-01-15 13:30:00'),
(10, 4050000, 'vnpay', 'VNP20250201123', 'paid', '2025-01-22 10:15:00', '2025-01-22 10:15:00', '2025-01-22 10:15:00'),
(11, 2700000, 'credit_card', 'CC202502141234', 'paid', '2025-02-01 15:00:00', '2025-02-01 15:00:00', '2025-02-01 15:00:00'),
(12, 5550000, 'vnpay', 'VNP20250308145', 'paid', '2025-02-25 09:45:00', '2025-02-25 09:45:00', '2025-02-25 09:45:00'),
(13, 4950000, 'bank_transfer', 'BANK20250320123', 'paid', '2025-03-05 11:50:00', '2025-03-05 11:50:00', '2025-03-05 11:50:00'),
(14, 8400000, 'vnpay', 'VNP20250401123', 'paid', '2025-03-18 16:30:00', '2025-03-18 16:30:00', '2025-03-18 16:30:00'),
(15, 4050000, 'vnpay', 'VNP20250418156', 'paid', '2025-04-05 10:45:00', '2025-04-05 10:45:00', '2025-04-05 10:45:00'),
(16, 5550000, 'cash', NULL, 'paid', '2025-05-01 14:00:00', '2025-05-01 14:00:00', '2025-05-01 14:00:00'),
(17, 6300000, 'vnpay', 'VNP20250515123', 'paid', '2025-05-01 09:15:00', '2025-05-01 09:15:00', '2025-05-01 09:15:00'),
(18, 4950000, 'credit_card', 'CC20250525145', 'paid', '2025-05-10 11:45:00', '2025-05-10 11:45:00', '2025-05-10 11:45:00'),
(19, 2550000, 'vnpay', 'VNP20250601123', 'paid', '2025-05-20 17:00:00', '2025-05-20 17:00:00', '2025-05-20 17:00:00'),
(20, 8400000, 'bank_transfer', 'BANK20250610156', 'paid', '2025-05-28 10:25:00', '2025-05-28 10:25:00', '2025-05-28 10:25:00'),
(21, 7400000, 'vnpay', 'VNP20250620123', 'paid', '2025-06-05 09:30:00', '2025-06-05 09:30:00', '2025-06-05 09:30:00'),
(22, 4950000, 'vnpay', 'VNP20250705145', 'paid', '2025-06-22 14:45:00', '2025-06-22 14:45:00', '2025-06-22 14:45:00'),
(23, 8400000, 'credit_card', 'CC20250715123', 'paid', '2025-07-01 11:15:00', '2025-07-01 11:15:00', '2025-07-01 11:15:00'),
(25, 11200000, 'vnpay', 'VNP20250801156', 'paid', '2025-07-15 16:35:00', '2025-07-15 16:35:00', '2025-07-15 16:35:00'),
(26, 13500000, 'bank_transfer', 'BANK20250820123', 'paid', '2025-08-05 09:45:00', '2025-08-05 09:45:00', '2025-08-05 09:45:00'),
(27, 3400000, 'vnpay', 'VNP20250901145', 'paid', '2025-08-20 11:30:00', '2025-08-20 11:30:00', '2025-08-20 11:30:00'),
(28, 4050000, 'cash', NULL, 'paid', '2025-09-12 14:15:00', '2025-09-12 14:15:00', '2025-09-12 14:15:00'),
(29, 6300000, 'vnpay', 'VNP20250925123', 'paid', '2025-09-10 10:15:00', '2025-09-10 10:15:00', '2025-09-10 10:15:00'),
(30, 8400000, 'vnpay', 'VNP20251005156', 'paid', '2025-09-22 16:45:00', '2025-09-22 16:45:00', '2025-09-22 16:45:00'),
(31, 4050000, 'vnpay', 'VNP20251015123', 'paid', '2025-10-01 09:50:00', '2025-10-01 09:50:00', '2025-10-01 09:50:00'),
(32, 4050000, 'bank_transfer', 'BANK20251025145', 'paid', '2025-10-12 11:25:00', '2025-10-12 11:25:00', '2025-10-12 11:25:00'),
(33, 7400000, 'vnpay', 'VNP20251101123', 'paid', '2025-10-20 14:15:00', '2025-10-20 14:15:00', '2025-10-20 14:15:00'),
(34, 2550000, 'cash', NULL, 'paid', '2025-11-10 15:30:00', '2025-11-10 15:30:00', '2025-11-10 15:30:00'),
(35, 2550000, 'vnpay', 'VNP20251120156', 'paid', '2025-11-05 16:45:00', '2025-11-05 16:45:00', '2025-11-05 16:45:00'),
(36, 5250000, 'vnpay', 'VNP20251201123', 'paid', '2025-11-18 09:35:00', '2025-11-18 09:35:00', '2025-11-18 09:35:00'),
(37, 2550000, 'vnpay', 'VNP20251210145', 'paid', '2025-11-25 11:50:00', '2025-11-25 11:50:00', '2025-11-25 11:50:00'),
(38, 7400000, 'credit_card', 'CC20251220123', 'paid', '2025-12-05 14:25:00', '2025-12-05 14:25:00', '2025-12-05 14:25:00'),
(39, 5550000, 'vnpay', 'VNP20260105156', 'paid', '2025-12-22 10:15:00', '2025-12-22 10:15:00', '2025-12-22 10:15:00'),
(40, 5250000, 'vnpay', 'VNP20260115123', 'paid', '2026-01-02 16:20:00', '2026-01-02 16:20:00', '2026-01-02 16:20:00'),
(41, 5250000, 'bank_transfer', 'BANK20260125145', 'paid', '2026-01-10 09:50:00', '2026-01-10 09:50:00', '2026-01-10 09:50:00'),
(42, 4050000, 'vnpay', 'VNP20260205123', 'paid', '2026-01-22 11:35:00', '2026-01-22 11:35:00', '2026-01-22 11:35:00'),
(43, 4050000, 'vnpay', 'VNP20260214156', 'paid', '2026-02-01 14:50:00', '2026-02-01 14:50:00', '2026-02-01 14:50:00'),
(45, 7400000, 'vnpay', 'VNP20260301123', 'paid', '2026-02-20 09:20:00', '2026-02-20 09:20:00', '2026-02-20 09:20:00'),
(46, 11200000, 'bank_transfer', 'BANK20260310145', 'paid', '2026-03-01 16:35:00', '2026-03-01 16:35:00', '2026-03-01 16:35:00'),
(48, 4050000, 'vnpay', 'VNP20260325123', 'paid', '2026-03-18 14:25:00', '2026-03-18 14:25:00', '2026-03-18 14:25:00'),
(50, 13500000, 'bank_transfer', 'BANK20260410123', 'paid', '2026-04-01 09:35:00', '2026-04-01 09:35:00', '2026-04-01 09:35:00');

-- ============================================================
-- 9. THÊM REVIEWS CHI TIẾT (50+ đánh giá thực tế)
-- ============================================================

INSERT INTO `reviews` (`user_id`, `room_id`, `rating`, `title`, `comment`, `reply`, `replied_at`, `created_at`, `updated_at`) VALUES
(11, 6, 5, 'Cuối năm nghỉ dưỡng rất đáng', 'Đặt phòng Standard 102 cho 3 đêm cuối tháng 12. Phòng sạch sẽ, view vườn và hồ bơi rất mát. Nhân viên lễ tân nhiệt tình, check-in nhanh. Bữa sáng buffet đa dạng, có cả phở và bánh mì. Chỉ tiếc là thời tiết hơi mưa 1 ngày nhưng không ảnh hưởng nhiều. Sẽ quay lại mùa hè.', 'Cảm ơn anh/chị đã lựa chọn Light Hotel! Chúng tôi mong được đón tiếp gia đình vào mùa hè.', '2024-12-24 10:00:00', '2024-12-23 15:30:00', '2024-12-24 10:00:00'),
(12, 10, 4, 'Phòng ổn, giá hợp lý', 'Phòng 103 gần nhà hàng nên tiện. Sáng dậy đi vài bước là ăn sáng. View vườn bình thường nhưng không gian yên tĩnh. Giá 850k/đêm cho resort 5 sao là ok. Điểm trừ nhỏ: wifi hơi chậm vào giờ cao điểm.', NULL, NULL, '2025-01-05 11:20:00', '2025-01-05 11:20:00'),
(13, 15, 5, 'Gần gym, tiện cho người hay tập', 'Mình hay đi công tác, lần nào cũng đặt phòng 104 vì gần phòng gym. Sáng dậy chạy bộ khuôn viên rồi về tập. Phòng sạch, giường êm. Nhân viên dọn phòng kỹ.', NULL, NULL, '2025-01-17 09:45:00', '2025-01-17 09:45:00'),
(14, 7, 5, 'Deluxe view biển đúng như hình', 'Ảnh trên web đúng với thực tế. Ban công rộng, sáng ngồi uống cà phê ngắm biển rất đã. Phòng 206 tầng 2, gió mát. Vợ chồng mình rất hài lòng. Lần sau sẽ thử Suite.', 'Cảm ơn anh chị! Suite view còn đẹp hơn nữa ạ.', '2025-02-05 08:30:00', '2025-02-04 16:20:00', '2025-02-05 08:30:00'),
(15, 19, 4, 'Phòng Deluxe tầng cao, view đẹp', 'Đặt phòng 209 cho dịp Valentine. View biển đẹp, nội thất ấm cúng. Chỉ hơi tiếc là bữa tối tại nhà hàng resort hơi đắt so với ra ngoài ăn. Nhưng chất lượng đồ ăn tốt.', NULL, NULL, '2025-02-16 14:10:00', '2025-02-16 14:10:00'),
(16, 21, 5, 'Gia đình 4 người rất vui', 'Đưa 2 con (5 tuổi và 8 tuổi) đi nghỉ 3 đêm. Phòng Family 405 rộng, có góc chơi cho bé. Con nhỏ thích mê hồ bơi trẻ em. Bố mẹ được thư giãn. Nhân viên còn tặng kem cho các con. Cảm ơn resort!', 'Rất vui khi các bé thích ạ! Chúc gia đình nhiều chuyến đi vui vẻ.', '2025-03-12 09:15:00', '2025-03-11 11:30:00', '2025-03-12 09:15:00'),
(17, 25, 5, 'Ocean Front – đúng là sát biển', 'Phòng 210 nghe rõ tiếng sóng vỗ. Sáng thức dậy mở cửa ban công là gió biển ùa vào. Cà phê Nespresso trong phòng ngon. Đáng từng đồng. Tuần trăng mật ngắn 3 đêm nhưng rất đáng nhớ.', NULL, NULL, '2025-03-23 10:45:00', '2025-03-23 10:45:00'),
(18, 3, 4, 'Suite rộng, phù hợp gia đình', '4 người lớn ở Suite 301 thoải mái. Có 2 phòng ngủ riêng, phòng khách rộng. View biển 180 độ. Điểm trừ: phòng tắm thứ 2 hơi nhỏ. Nhưng tổng thể rất ổn.', NULL, NULL, '2025-04-05 15:20:00', '2025-04-05 15:20:00'),
(19, 27, 5, 'Deluxe tầng cao, gió mát', 'Phòng 211 view biển và thành phố. Buổi tối ngồi ban công ngắm đèn rất đẹp. Máy pha cà phê dùng tốt. Nhân viên phục vụ chu đáo.', NULL, NULL, '2025-04-21 11:00:00', '2025-04-21 11:00:00'),
(20, 9, 5, 'Family có trẻ em rất tiện', 'Phòng 403 có sẵn nôi và ghế ăn. Con 2 tuổi ngủ riêng được. Góc vui chơi trong phòng an toàn. Gần bể bơi trẻ em. Bố mẹ yên tâm. Sẽ quay lại.', 'Cảm ơn chị! Chúng tôi luôn cố gắng phục vụ tốt nhất cho gia đình.', '2025-05-05 10:30:00', '2025-05-04 14:45:00', '2025-05-05 10:30:00'),
(11, 12, 5, 'Suite sát biển nhất – view tuyệt', 'Phòng 303 đúng là gần biển nhất. Ban công cách sóng vài chục mét. Đêm ngủ nghe tiếng sóng ru. Sáng dậy bình minh đẹp. Đáng giá hơn cả Suite 301 302.', NULL, NULL, '2025-05-18 09:30:00', '2025-05-18 09:30:00'),
(12, 17, 5, 'Ocean Front 208 – trải nghiệm tuyệt vời', 'Đúng như mô tả: nghe rõ sóng vỗ, ngửi mùi biển. Nội thất gỗ ấm cúng. Sáng cà phê trên ban công không muốn về. Một trong những phòng đẹp nhất từng ở.', NULL, NULL, '2025-05-28 16:15:00', '2025-05-28 16:15:00'),
(21, 1, 4, 'Standard 101 ổn cho cặp đôi', 'Phòng tầng trệt view vườn. Giường King êm. Gần lối ra biển. Giá tốt. Chỉ hơi ồn vào sáng sớm vì có khách đi bộ qua. Nhưng tổng thể hài lòng.', NULL, NULL, '2025-06-04 10:20:00', '2025-06-04 10:20:00'),
(22, 22, 5, 'VIP Jacuzzi – kỷ niệm 10 năm cưới', 'Đặt phòng 503 cho dịp kỷ niệm. Jacuzzi ngoài trời view hoàng hôn quá tuyệt. Nhân viên trang trí hoa và bánh trong phòng. Cảm động lắm. Cảm ơn Light Hotel!', 'Chúc mừng anh chị 10 năm hạnh phúc! Rất vinh dự được phục vụ.', '2025-06-13 14:00:00', '2025-06-13 11:30:00', '2025-06-13 14:00:00'),
(23, 28, 5, 'Family 406 view biển xa', 'Phòng rộng, 2 giường Queen. View vừa biển vừa vườn. Con cái thích. Bữa sáng có nhiều món cho trẻ. Nhân viên thân thiện.', NULL, NULL, '2025-06-24 15:45:00', '2025-06-24 15:45:00'),
(24, 31, 5, 'Ocean Front 212 – gần biển nhất', 'Phòng tầng thấp, sát biển. Tiếng sóng vỗ rất rõ. Nội thất xanh biển dịu mát. Thức dậy với bình minh. Trải nghiệm đáng nhớ.', NULL, NULL, '2025-07-08 09:15:00', '2025-07-08 09:15:00'),
(25, 5, 5, 'VIP 501 – tuần trăng mật hoàn hảo', 'Jacuzzi ngoài ban công, view biển. Dịch vụ quản gia chu đáo. Minibar miễn phí đầy đủ. Phòng trang trí hoa tươi. Đêm đầu tiên ngắm sao trong Jacuzzi. Không thể quên.', NULL, NULL, '2025-07-18 11:00:00', '2025-07-18 11:00:00'),
(14, 14, 4, 'VIP 502 tầng cao, view mênh mông', 'Phòng đẹp, Jacuzzi ấm. View biển rộng. Chỉ tiếc 1 đêm bị mất nước nóng khoảng 30 phút, gọi kỹ thuật xử lý nhanh. Ngoài ra mọi thứ tốt.', 'Xin lỗi anh/chị về sự cố. Chúng tôi đã kiểm tra toàn bộ hệ thống.', '2025-08-06 08:45:00', '2025-08-05 16:30:00', '2025-08-06 08:45:00'),
(15, 18, 5, 'Presidential Suite – trải nghiệm xa xỉ', 'Đưa bố mẹ đi nghỉ. Đặt Presidential 601. Phòng khách lớn, phòng ăn riêng. View 360 độ. Dịch vụ quản gia 24/7. Bố mẹ rất vui. Giá cao nhưng xứng đáng cho dịp đặc biệt.', NULL, NULL, '2025-08-23 14:20:00', '2025-08-23 14:20:00'),
(26, 6, 4, 'Standard 102 yên tĩnh', 'Phòng gần biển nhưng yên tĩnh hơn 101. Giường êm. Đi công tác 4 đêm, làm việc trong phòng ổn. Wifi ổn định.', NULL, NULL, '2025-09-05 10:30:00', '2025-09-05 10:30:00'),
(27, 11, 5, 'Deluxe 207 cho 3 người', 'Đi với mẹ, đặt phòng có sofa bed. Rộng rãi. View biển đẹp. Mẹ rất thích. Nhân viên nhiệt tình.', NULL, NULL, '2025-09-15 11:45:00', '2025-09-15 11:45:00'),
(28, 23, 4, 'Suite 304 view biển', 'Phòng khách rộng, 2 phòng ngủ. Ban công lớn. View đẹp. Hơi tiếc là phòng tắm dùng chung chứ không riêng từng phòng ngủ. Nhưng vẫn hài lòng.', NULL, NULL, '2025-09-28 15:00:00', '2025-09-28 15:00:00'),
(29, 29, 5, 'VIP 504 – sinh nhật vợ', 'Đặt bất ngờ cho vợ. Nhân viên trang trí phòng và bánh. Jacuzzi view biển. Vợ rất cảm động. Cảm ơn team Light Hotel!', 'Chúc mừng sinh nhật chị! Chúc gia đình hạnh phúc.', '2025-10-08 09:30:00', '2025-10-08 10:20:00', '2025-10-08 09:30:00'),
(30, 2, 5, 'Deluxe 205 – lần thứ 2 quay lại', 'Lần trước ở phòng 7, lần này đặt 205. Đều đẹp. View biển, ban công rộng. Resort giữ được chất lượng. Sẽ quay lại lần 3.', NULL, NULL, '2025-10-18 14:15:00', '2025-10-18 14:15:00'),
(16, 34, 5, 'Deluxe 213 mới trang bị', 'Phòng mới, nội thất còn thơm. View biển trực diện. Nespresso miễn phí. Rất hài lòng.', NULL, NULL, '2025-10-28 11:00:00', '2025-10-28 11:00:00'),
(17, 4, 4, 'Family 402 cho 4 người', 'Phòng rộng, 2 giường. Góc vui chơi trẻ em ổn. Gần bể bơi. Chỉ hơi xa biển. Nhưng đi bộ 2 phút là tới.', NULL, NULL, '2025-11-05 16:30:00', '2025-11-05 16:30:00'),
(18, 20, 3, 'Standard 105 – giá rẻ nhưng...', 'Phòng rẻ nhất, view vườn. Sạch sẽ nhưng hơi cũ. Tủ quần áo có vết ố. Gọi dọn phòng thì xử lý nhanh. Tổng thể tạm được.', 'Xin lỗi anh/chị. Chúng tôi sẽ kiểm tra và bảo trì phòng.', '2025-11-13 10:15:00', '2025-11-13 09:20:00', '2025-11-13 10:15:00'),
(19, 26, 5, 'Standard 106 gần gym', 'Mình hay tập thể dục. Phòng này tiện. Sáng chạy bộ ra biển, chiều tập gym. Phòng sạch, giường êm.', NULL, NULL, '2025-11-23 10:45:00', '2025-11-23 10:45:00'),
(20, 30, 4, 'Junior Suite 403 cho 3 người', 'Đi với 2 con. Giường King + sofa bed. Rộng. View thành phố và biển. Giá hợp lý hơn Suite. Phù hợp gia đình nhỏ.', NULL, NULL, '2025-12-04 14:00:00', '2025-12-04 14:00:00'),
(21, 33, 5, 'Standard 107 yên tĩnh', 'Phòng tầng thấp, view vườn. Rất yên tĩnh. Gần lối ra biển. Giá tốt. Sẽ đặt lại.', NULL, NULL, '2025-12-13 11:20:00', '2025-12-13 11:20:00'),
(22, 35, 5, 'Family Ocean Garden – con thích mê', 'Phòng 407 gần hồ bơi và khu chơi. 2 con (6 và 9 tuổi) thích mê. Sáng bơi, chiều chơi. Bố mẹ thư giãn. View vườn và biển. Rất đáng.', 'Cảm ơn gia đình! Chúc các bé luôn vui khỏe.', '2025-12-24 09:00:00', '2025-12-24 11:30:00', '2025-12-24 09:00:00'),
(23, 13, 4, 'Family 404 cao cấp', 'Phòng có bếp nhỏ, phòng ăn riêng. Bồn tắm view biển. Rộng. Giá cao nhưng phù hợp dịp đặc biệt. Dịch vụ quản gia tốt.', NULL, NULL, '2026-01-08 10:15:00', '2026-01-08 10:15:00'),
(24, 16, 5, 'Junior Suite 401 – lựa chọn đúng', '3 người ở Junior Suite thoải mái. Sofa bed mở ra rộng. View thành phố và biển. Phòng tắm có vòi sen mưa. Giá tốt hơn Suite. Rất hài lòng.', NULL, NULL, '2026-01-18 15:30:00', '2026-01-18 15:30:00'),
(25, 24, 5, 'Junior 402 – đêm giao thừa có pháo hoa', 'Đặt phòng cho đêm 30 Tết. View pháo hoa từ ban công. Rất đẹp! Phòng rộng, 3 người ở ok. Đáng từng đồng cho dịp đặc biệt.', NULL, NULL, '2026-01-28 11:00:00', '2026-01-28 11:00:00'),
(26, 7, 5, 'Deluxe 206 lần 3', 'Khách quen của resort. Lần nào cũng đặt Deluxe. Phòng 206 view đẹp, gió mát. Chất lượng ổn định. Cảm ơn team!', NULL, NULL, '2026-02-08 09:45:00', '2026-02-08 09:45:00'),
(27, 19, 4, 'Deluxe 209 dịp Valentine', 'Phòng đẹp, view biển. Trang trí hoa trong phòng. Chỉ hơi đông khách nên bữa sáng phải xếp hàng. Nhưng đồ ăn ngon.', NULL, NULL, '2026-02-17 14:20:00', '2026-02-17 14:20:00');

-- Thêm reviews cho các phòng chưa có hoặc ít review
INSERT INTO `reviews` (`user_id`, `room_id`, `rating`, `title`, `comment`, `reply`, `replied_at`, `created_at`, `updated_at`) VALUES
(28, 8, 3, 'Suite 302 đang bảo trì', 'Đặt phòng nhưng được báo đang maintenance. Đổi sang phòng 304. Phòng 304 đẹp hơn. Nhân viên xử lý tốt.', 'Xin lỗi vì sự bất tiện. Cảm ơn anh/chị đã thông cảm.', '2025-10-02 09:00:00', '2025-10-01 16:30:00', '2025-10-02 09:00:00'),
(29, 32, 4, 'Suite 305 view 2 mặt', 'Phòng view biển và thành phố. Đêm ngồi ban công ngắm đèn đẹp. Phòng khách rộng. Có 1 vòi sen hơi yếu.', NULL, NULL, '2025-11-15 11:20:00', '2025-11-15 11:20:00'),
(31, 15, 5, 'Standard 104 – công tác 3 đêm', 'Đi họp công tác. Phòng gần lobby, tiện. View hồ bơi. Sạch sẽ. Bữa sáng có sớm từ 6h cho khách đi sớm.', NULL, NULL, '2025-12-05 08:45:00', '2025-12-05 08:45:00'),
(32, 10, 4, 'Lần 2 ở phòng 103', 'Quay lại đặt cùng phòng. Vẫn ổn. Gần nhà hàng tiện. Giá không đổi. Resort giữ chất lượng.', NULL, NULL, '2026-01-12 14:00:00', '2026-01-12 14:00:00'),
(33, 1, 4, 'Standard 101 giá tốt', 'Cặp đôi nghỉ 2 đêm. Phòng đủ dùng. View vườn xanh. Đi bộ ra biển gần. Hài lòng với giá 850k.', NULL, NULL, '2026-02-20 10:30:00', '2026-02-20 10:30:00'),
(34, 18, 5, 'Presidential cho sự kiện gia đình', 'Tổ chức sinh nhật bố 70 tuổi. Phòng lớn, view 360. Dịch vụ quản gia chu đáo. Cả nhà rất vui. Đáng đồng tiền.', NULL, NULL, '2026-03-05 15:45:00', '2026-03-05 15:45:00'),
(35, 9, 5, 'Family 403 – bé 1 tuổi', 'Đi với con 1 tuổi. Có nôi sẵn. Góc chơi an toàn. Gần bể bơi trẻ em. Mẹ yên tâm. Nhân viên hỗ trợ nhiệt tình.', 'Cảm ơn chị! Chúc bé hay ăn chóng lớn.', '2026-04-01 10:15:00', '2026-03-31 16:20:00', '2026-04-01 10:15:00');

SET FOREIGN_KEY_CHECKS = 1;

-- ============================================================
-- HOÀN TẤT
-- Tổng kết: +25 users, +50 bookings, +60 reviews, mô tả phòng/room_type chi tiết hơn
-- ============================================================
