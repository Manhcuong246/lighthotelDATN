-- ============================================================
-- FIX ENCODING: Ghi đúng text UTF-8 vào các bảng bị lỗi font
-- Chạy trong Docker (khuyến nghị): php artisan db:apply-fix-encoding
-- Hoặc: docker exec -i <mysql_container> mysql -u app -papp_password hotel_booking < fix_encoding.sql
-- ============================================================
SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ============================================================
-- 1. SERVICES
-- ============================================================
UPDATE `services` SET
  `name`        = 'Bữa sáng buffet',
  `description` = 'Buffet sáng phong phú với hải sản tươi sống'
WHERE `id` = 1;

UPDATE `services` SET
  `name`        = 'Massage thư giãn 60 phút',
  `description` = 'Massage toàn thân tại spa resort'
WHERE `id` = 2;

UPDATE `services` SET
  `name`        = 'Xe đưa đón sân bay',
  `description` = 'Xe 7 chỗ cao cấp'
WHERE `id` = 3;

UPDATE `services` SET
  `name`        = 'Tour vịnh Bà Nà',
  `description` = 'Tour 1 ngày có hướng dẫn viên'
WHERE `id` = 4;

UPDATE `services` SET
  `name`        = 'Hoa chúc mừng',
  `description` = 'Bó hoa hồng tươi thơm'
WHERE `id` = 5;

UPDATE `services` SET
  `name`        = 'Thuê xe máy/scooter',
  `description` = 'Xe số hoặc tay ga mới, bảo hiểm đầy đủ'
WHERE `id` = 6;

UPDATE `services` SET
  `name`        = 'Lớp yoga bình minh trên bãi biển',
  `description` = '60 phút với huấn luyện viên chuyên nghiệp'
WHERE `id` = 7;

UPDATE `services` SET
  `name`        = 'Set BBQ hải sản ngoài trời',
  `description` = 'Cho 4 người, nguyên liệu tươi sống'
WHERE `id` = 8;

UPDATE `services` SET
  `name`        = 'Chụp ảnh cưới / kỷ niệm tại resort',
  `description` = 'Gói 2 giờ, nhiếp ảnh gia chuyên nghiệp'
WHERE `id` = 9;

UPDATE `services` SET
  `name`        = 'Early check-in / Late check-out',
  `description` = 'Phụ phí tùy thời gian'
WHERE `id` = 10;

-- ============================================================
-- 2. AMENITIES
-- ============================================================
UPDATE `amenities` SET `name` = 'Wi-Fi miễn phí tốc độ cao'        WHERE `id` = 1;
UPDATE `amenities` SET `name` = 'Bể bơi vô cực view biển'          WHERE `id` = 2;
UPDATE `amenities` SET `name` = 'Spa & Massage thư giãn'            WHERE `id` = 3;
UPDATE `amenities` SET `name` = 'Phòng gym hiện đại'                WHERE `id` = 4;
UPDATE `amenities` SET `name` = 'Nhà hàng Á - Âu'                  WHERE `id` = 5;
UPDATE `amenities` SET `name` = 'Bãi đỗ xe miễn phí'               WHERE `id` = 6;
UPDATE `amenities` SET `name` = 'Điều hòa trung tâm'               WHERE `id` = 7;
UPDATE `amenities` SET `name` = 'Bữa sáng buffet'                  WHERE `id` = 8;
UPDATE `amenities` SET `name` = 'Ban công view biển'               WHERE `id` = 9;
UPDATE `amenities` SET `name` = 'TV Smart 55 inch'                 WHERE `id` = 10;
UPDATE `amenities` SET `name` = 'Minibar miễn phí'                 WHERE `id` = 11;
UPDATE `amenities` SET `name` = 'Máy pha cà phê Nespresso'        WHERE `id` = 12;
UPDATE `amenities` SET `name` = 'Khăn tắm cao cấp & áo choàng'   WHERE `id` = 13;
UPDATE `amenities` SET `name` = 'Két an toàn điện tử'             WHERE `id` = 14;
UPDATE `amenities` SET `name` = 'Máy sấy tóc cao cấp'             WHERE `id` = 15;
UPDATE `amenities` SET `name` = 'Dịch vụ dọn phòng 2 lần/ngày'   WHERE `id` = 16;
UPDATE `amenities` SET `name` = 'Phòng tắm mưa & vòi sen riêng'  WHERE `id` = 17;
UPDATE `amenities` SET `name` = 'Hệ thống đèn thông minh'        WHERE `id` = 18;

-- ============================================================
-- 3. HOTEL_INFO
-- ============================================================
UPDATE `hotel_info` SET
  `name`        = 'Light Hotel',
  `description` = 'Light Hotel mang đến trải nghiệm lưu trú hiện đại: thiết kế tối giản, ánh sáng tự nhiên, tiện ích đầy đủ và đội ngũ phục vụ chuyên nghiệp. Phù hợp công tác, gia đình và kỳ nghỉ ngắn ngày — đặt phòng minh bạch, hỗ trợ 24/7.',
  `address`     = 'Khu trung tâm, Việt Nam'
WHERE `id` = 1;

-- ============================================================
-- 4. ROOM_TYPES (mô tả)
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
-- 5. ROOMS (mô tả – chỉ id 1-35 bị lỗi encoding)
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

SET FOREIGN_KEY_CHECKS = 1;
