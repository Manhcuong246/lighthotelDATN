-- ============================================================
-- FIX ENCODING PART 2: room names + all reviews
-- ============================================================
SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ============================================================
-- 1. ROOMS: fix tên phòng bị lỗi
-- ============================================================
UPDATE `rooms` SET `name` = 'Phòng 312'   WHERE `id` = 36;
UPDATE `rooms` SET `name` = 'Phòng 33443' WHERE `id` = 37;

-- ============================================================
-- 2. REVIEWS 1-10 (từ hotel_booking.sql gốc)
-- ============================================================
UPDATE `reviews` SET
  `title`   = 'Kỳ nghỉ tuyệt vời!',
  `comment` = 'Phòng sạch sẽ, view biển đẹp, nhân viên như người nhà. Chúng tôi cảm thấy thật thư giãn sau chuyến đi dài.',
  `reply`   = NULL
WHERE `id` = 1;

UPDATE `reviews` SET
  `title`   = 'Rất hài lòng',
  `comment` = 'Deluxe đúng chất 5 sao, chỉ hơi tiếc là bữa sáng chưa đa dạng lắm.',
  `reply`   = 'Cảm ơn anh chị! Lần sau chúng tôi sẽ cải thiện buffet.'
WHERE `id` = 2;

UPDATE `reviews` SET
  `title`   = 'Trải nghiệm hoàng gia',
  `comment` = 'Jacuzzi ngoài trời thật tuyệt vời, mình và vợ như đang ở thiên đường.',
  `reply`   = NULL
WHERE `id` = 3;

UPDATE `reviews` SET
  `title`   = 'Gia đình rất vui',
  `comment` = 'Con cái thích mê khu vui chơi, không gian rộng rãi. Sẽ quay lại hè năm sau!',
  `reply`   = NULL
WHERE `id` = 4;

UPDATE `reviews` SET
  `title`   = 'Suite đáng giá từng đồng',
  `comment` = 'Phòng khách riêng biệt, view toàn cảnh biển. Nhân viên phục vụ siêu tận tình!',
  `reply`   = NULL
WHERE `id` = 5;

UPDATE `reviews` SET
  `title`   = 'Sóng vỗ ngay ban công',
  `comment` = 'Phòng Ocean Front Deluxe quá xuất sắc, thức dậy nghe tiếng sóng, cà phê sáng ngắm bình minh — đáng giá từng đồng!',
  `reply`   = 'Rất vui khi anh/chị có trải nghiệm tuyệt vời!'
WHERE `id` = 6;

UPDATE `reviews` SET
  `title`   = 'Presidential Suite sang trọng',
  `comment` = 'Dịch vụ quản gia rất chuyên nghiệp, chỉ tiếc lần này ở có 2 đêm.',
  `reply`   = 'Cảm ơn phản hồi! Mong được đón tiếp anh/chị lần sau lâu hơn.'
WHERE `id` = 7;

UPDATE `reviews` SET
  `title`   = 'Gia đình hạnh phúc',
  `comment` = 'Phòng Family rộng rãi, con nhỏ thích mê. Nhân viên còn tặng quà nhỏ cho bé — cảm động lắm!',
  `reply`   = ''
WHERE `id` = 8;

UPDATE `reviews` SET
  `title`   = 'Jacuzzi view biển — đỉnh cao',
  `comment` = 'Đêm đầu tiên ngâm mình ngắm pháo hoa — kỷ niệm không quên được!',
  `reply`   = ''
WHERE `id` = 9;

UPDATE `reviews` SET
  `title`   = 'Junior Suite ấm cúng',
  `comment` = 'Phù hợp cho 3 người, view đẹp, nhân viên thân thiện',
  `reply`   = ''
WHERE `id` = 10;

-- ============================================================
-- 3. REVIEWS 11-53 (từ seed_realistic_data.sql)
-- ============================================================
UPDATE `reviews` SET
  `title`   = 'Cuối năm nghỉ dưỡng rất đáng',
  `comment` = 'Đặt phòng Standard 102 cho 3 đêm cuối tháng 12. Phòng sạch sẽ, view vườn và hồ bơi rất mát. Nhân viên lễ tân nhiệt tình, check-in nhanh. Bữa sáng buffet đa dạng, có cả phở và bánh mì. Chỉ tiếc là thời tiết hơi mưa 1 ngày nhưng không ảnh hưởng nhiều. Sẽ quay lại mùa hè.',
  `reply`   = 'Cảm ơn anh/chị đã lựa chọn Light Hotel! Chúng tôi mong được đón tiếp gia đình vào mùa hè.'
WHERE `id` = 11;

UPDATE `reviews` SET
  `title`   = 'Phòng ổn, giá hợp lý',
  `comment` = 'Phòng 103 gần nhà hàng nên tiện. Sáng dậy đi vài bước là ăn sáng. View vườn bình thường nhưng không gian yên tĩnh. Giá 850k/đêm cho resort 5 sao là ok. Điểm trừ nhỏ: wifi hơi chậm vào giờ cao điểm.',
  `reply`   = NULL
WHERE `id` = 12;

UPDATE `reviews` SET
  `title`   = 'Gần gym, tiện cho người hay tập',
  `comment` = 'Mình hay đi công tác, lần nào cũng đặt phòng 104 vì gần phòng gym. Sáng dậy chạy bộ khuôn viên rồi về tập. Phòng sạch, giường êm. Nhân viên dọn phòng kỹ.',
  `reply`   = NULL
WHERE `id` = 13;

UPDATE `reviews` SET
  `title`   = 'Deluxe view biển đúng như hình',
  `comment` = 'Ảnh trên web đúng với thực tế. Ban công rộng, sáng ngồi uống cà phê ngắm biển rất đã. Phòng 206 tầng 2, gió mát. Vợ chồng mình rất hài lòng. Lần sau sẽ thử Suite.',
  `reply`   = 'Cảm ơn anh chị! Suite view còn đẹp hơn nữa ạ.'
WHERE `id` = 14;

UPDATE `reviews` SET
  `title`   = 'Phòng Deluxe tầng cao, view đẹp',
  `comment` = 'Đặt phòng 209 cho dịp Valentine. View biển đẹp, nội thất ấm cúng. Chỉ hơi tiếc là bữa tối tại nhà hàng resort hơi đắt so với ra ngoài ăn. Nhưng chất lượng đồ ăn tốt.',
  `reply`   = NULL
WHERE `id` = 15;

UPDATE `reviews` SET
  `title`   = 'Gia đình 4 người rất vui',
  `comment` = 'Đưa 2 con (5 tuổi và 8 tuổi) đi nghỉ 3 đêm. Phòng Family 405 rộng, có góc chơi cho bé. Con nhỏ thích mê hồ bơi trẻ em. Bố mẹ được thư giãn. Nhân viên còn tặng kem cho các con. Cảm ơn resort!',
  `reply`   = 'Rất vui khi các bé thích ạ! Chúc gia đình nhiều chuyến đi vui vẻ.'
WHERE `id` = 16;

UPDATE `reviews` SET
  `title`   = 'Ocean Front — đúng là sát biển',
  `comment` = 'Phòng 210 nghe rõ tiếng sóng vỗ. Sáng thức dậy mở cửa ban công là gió biển ùa vào. Cà phê Nespresso trong phòng ngon. Đáng từng đồng. Tuần trăng mật ngắn 3 đêm nhưng rất đáng nhớ.',
  `reply`   = NULL
WHERE `id` = 17;

UPDATE `reviews` SET
  `title`   = 'Suite rộng, phù hợp gia đình',
  `comment` = '4 người lớn ở Suite 301 thoải mái. Có 2 phòng ngủ riêng, phòng khách rộng. View biển 180 độ. Điểm trừ: phòng tắm thứ 2 hơi nhỏ. Nhưng tổng thể rất ổn.',
  `reply`   = NULL
WHERE `id` = 18;

UPDATE `reviews` SET
  `title`   = 'Deluxe tầng cao, gió mát',
  `comment` = 'Phòng 211 view biển và thành phố. Buổi tối ngồi ban công ngắm đèn rất đẹp. Máy pha cà phê dùng tốt. Nhân viên phục vụ chu đáo.',
  `reply`   = NULL
WHERE `id` = 19;

UPDATE `reviews` SET
  `title`   = 'Family có trẻ em rất tiện',
  `comment` = 'Phòng 403 có sẵn nôi và ghế ăn. Con 2 tuổi ngủ riêng được. Góc vui chơi trong phòng an toàn. Gần bể bơi trẻ em. Bố mẹ yên tâm. Sẽ quay lại.',
  `reply`   = 'Cảm ơn chị! Chúng tôi luôn cố gắng phục vụ tốt nhất cho gia đình.'
WHERE `id` = 20;

UPDATE `reviews` SET
  `title`   = 'Suite sát biển nhất — view tuyệt',
  `comment` = 'Phòng 303 đúng là gần biển nhất. Ban công cách sóng vài chục mét. Đêm ngủ nghe tiếng sóng ru. Sáng dậy bình minh đẹp. Đáng giá hơn cả Suite 301 302.',
  `reply`   = NULL
WHERE `id` = 21;

UPDATE `reviews` SET
  `title`   = 'Ocean Front 208 — trải nghiệm tuyệt vời',
  `comment` = 'Đúng như mô tả: nghe rõ sóng vỗ, ngửi mùi biển. Nội thất gỗ ấm cúng. Sáng cà phê trên ban công không muốn về. Một trong những phòng đẹp nhất từng ở.',
  `reply`   = NULL
WHERE `id` = 22;

UPDATE `reviews` SET
  `title`   = 'Standard 101 ổn cho cặp đôi',
  `comment` = 'Phòng tầng trệt view vườn. Giường King êm. Gần lối ra biển. Giá tốt. Chỉ hơi ồn vào sáng sớm vì có khách đi bộ qua. Nhưng tổng thể hài lòng.',
  `reply`   = NULL
WHERE `id` = 23;

UPDATE `reviews` SET
  `title`   = 'VIP Jacuzzi — kỷ niệm 10 năm cưới',
  `comment` = 'Đặt phòng 503 cho dịp kỷ niệm. Jacuzzi ngoài trời view hoàng hôn quá tuyệt. Nhân viên trang trí hoa và bánh trong phòng. Cảm động lắm. Cảm ơn Light Hotel!',
  `reply`   = 'Chúc mừng anh chị 10 năm hạnh phúc! Rất vinh dự được phục vụ.'
WHERE `id` = 24;

UPDATE `reviews` SET
  `title`   = 'Family 406 view biển xa',
  `comment` = 'Phòng rộng, 2 giường Queen. View vừa biển vừa vườn. Con cái thích. Bữa sáng có nhiều món cho trẻ. Nhân viên thân thiện.',
  `reply`   = NULL
WHERE `id` = 25;

UPDATE `reviews` SET
  `title`   = 'Ocean Front 212 — gần biển nhất',
  `comment` = 'Phòng tầng thấp, sát biển. Tiếng sóng vỗ rất rõ. Nội thất xanh biển dịu mát. Thức dậy với bình minh. Trải nghiệm đáng nhớ.',
  `reply`   = NULL
WHERE `id` = 26;

UPDATE `reviews` SET
  `title`   = 'VIP 501 — tuần trăng mật hoàn hảo',
  `comment` = 'Jacuzzi ngoài ban công, view biển. Dịch vụ quản gia chu đáo. Minibar miễn phí đầy đủ. Phòng trang trí hoa tươi. Đêm đầu tiên ngắm sao trong Jacuzzi. Không thể quên.',
  `reply`   = NULL
WHERE `id` = 27;

UPDATE `reviews` SET
  `title`   = 'VIP 502 tầng cao, view mênh mông',
  `comment` = 'Phòng đẹp, Jacuzzi ấm. View biển rộng. Chỉ tiếc 1 đêm bị mất nước nóng khoảng 30 phút, gọi kỹ thuật xử lý nhanh. Ngoài ra mọi thứ tốt.',
  `reply`   = 'Xin lỗi anh/chị về sự cố. Chúng tôi đã kiểm tra toàn bộ hệ thống.'
WHERE `id` = 28;

UPDATE `reviews` SET
  `title`   = 'Presidential Suite — trải nghiệm xa xỉ',
  `comment` = 'Đưa bố mẹ đi nghỉ. Đặt Presidential 601. Phòng khách lớn, phòng ăn riêng. View 360 độ. Dịch vụ quản gia 24/7. Bố mẹ rất vui. Giá cao nhưng xứng đáng cho dịp đặc biệt.',
  `reply`   = NULL
WHERE `id` = 29;

UPDATE `reviews` SET
  `title`   = 'Standard 102 yên tĩnh',
  `comment` = 'Phòng gần biển nhưng yên tĩnh hơn 101. Giường êm. Đi công tác 4 đêm, làm việc trong phòng ổn. Wifi ổn định.',
  `reply`   = NULL
WHERE `id` = 30;

UPDATE `reviews` SET
  `title`   = 'Deluxe 207 cho 3 người',
  `comment` = 'Đi với mẹ, đặt phòng có sofa bed. Rộng rãi. View biển đẹp. Mẹ rất thích. Nhân viên nhiệt tình.',
  `reply`   = NULL
WHERE `id` = 31;

UPDATE `reviews` SET
  `title`   = 'Suite 304 view biển',
  `comment` = 'Phòng khách rộng, 2 phòng ngủ. Ban công lớn. View đẹp. Hơi tiếc là phòng tắm dùng chung chứ không riêng từng phòng ngủ. Nhưng vẫn hài lòng.',
  `reply`   = NULL
WHERE `id` = 32;

UPDATE `reviews` SET
  `title`   = 'VIP 504 — sinh nhật vợ',
  `comment` = 'Đặt bất ngờ cho vợ. Nhân viên trang trí phòng và bánh. Jacuzzi view biển. Vợ rất cảm động. Cảm ơn team Light Hotel!',
  `reply`   = 'Chúc mừng sinh nhật chị! Chúc gia đình hạnh phúc.'
WHERE `id` = 33;

UPDATE `reviews` SET
  `title`   = 'Deluxe 205 — lần thứ 2 quay lại',
  `comment` = 'Lần trước ở phòng 7, lần này đặt 205. Đều đẹp. View biển, ban công rộng. Resort giữ được chất lượng. Sẽ quay lại lần 3.',
  `reply`   = NULL
WHERE `id` = 34;

UPDATE `reviews` SET
  `title`   = 'Deluxe 213 mới trang bị',
  `comment` = 'Phòng mới, nội thất còn thơm. View biển trực diện. Nespresso miễn phí. Rất hài lòng.',
  `reply`   = NULL
WHERE `id` = 35;

UPDATE `reviews` SET
  `title`   = 'Family 402 cho 4 người',
  `comment` = 'Phòng rộng, 2 giường. Góc vui chơi trẻ em ổn. Gần bể bơi. Chỉ hơi xa biển. Nhưng đi bộ 2 phút là tới.',
  `reply`   = NULL
WHERE `id` = 36;

UPDATE `reviews` SET
  `title`   = 'Standard 105 — giá rẻ nhưng...',
  `comment` = 'Phòng rẻ nhất, view vườn. Sạch sẽ nhưng hơi cũ. Tủ quần áo có vết ố. Gọi dọn phòng thì xử lý nhanh. Tổng thể tạm được.',
  `reply`   = 'Xin lỗi anh/chị. Chúng tôi sẽ kiểm tra và bảo trì phòng.'
WHERE `id` = 37;

UPDATE `reviews` SET
  `title`   = 'Standard 106 gần gym',
  `comment` = 'Mình hay tập thể dục. Phòng này tiện. Sáng chạy bộ ra biển, chiều tập gym. Phòng sạch, giường êm.',
  `reply`   = NULL
WHERE `id` = 38;

UPDATE `reviews` SET
  `title`   = 'Junior Suite 403 cho 3 người',
  `comment` = 'Đi với 2 con. Giường King + sofa bed. Rộng. View thành phố và biển. Giá hợp lý hơn Suite. Phù hợp gia đình nhỏ.',
  `reply`   = NULL
WHERE `id` = 39;

UPDATE `reviews` SET
  `title`   = 'Standard 107 yên tĩnh',
  `comment` = 'Phòng tầng thấp, view vườn. Rất yên tĩnh. Gần lối ra biển. Giá tốt. Sẽ đặt lại.',
  `reply`   = NULL
WHERE `id` = 40;

UPDATE `reviews` SET
  `title`   = 'Family Ocean Garden — con thích mê',
  `comment` = 'Phòng 407 gần hồ bơi và khu chơi. 2 con (6 và 9 tuổi) thích mê. Sáng bơi, chiều chơi. Bố mẹ thư giãn. View vườn và biển. Rất đáng.',
  `reply`   = 'Cảm ơn gia đình! Chúc các bé luôn vui khỏe.'
WHERE `id` = 41;

UPDATE `reviews` SET
  `title`   = 'Family 404 cao cấp',
  `comment` = 'Phòng có bếp nhỏ, phòng ăn riêng. Bồn tắm view biển. Rộng. Giá cao nhưng phù hợp dịp đặc biệt. Dịch vụ quản gia tốt.',
  `reply`   = NULL
WHERE `id` = 42;

UPDATE `reviews` SET
  `title`   = 'Junior Suite 401 — lựa chọn đúng',
  `comment` = '3 người ở Junior Suite thoải mái. Sofa bed mở ra rộng. View thành phố và biển. Phòng tắm có vòi sen mưa. Giá tốt hơn Suite. Rất hài lòng.',
  `reply`   = NULL
WHERE `id` = 43;

UPDATE `reviews` SET
  `title`   = 'Junior 402 — đêm giao thừa có pháo hoa',
  `comment` = 'Đặt phòng cho đêm 30 Tết. View pháo hoa từ ban công. Rất đẹp! Phòng rộng, 3 người ở ok. Đáng từng đồng cho dịp đặc biệt.',
  `reply`   = NULL
WHERE `id` = 44;

UPDATE `reviews` SET
  `title`   = 'Deluxe 206 lần 3',
  `comment` = 'Khách quen của resort. Lần nào cũng đặt Deluxe. Phòng 206 view đẹp, gió mát. Chất lượng ổn định. Cảm ơn team!',
  `reply`   = NULL
WHERE `id` = 45;

UPDATE `reviews` SET
  `title`   = 'Deluxe 209 dịp Valentine',
  `comment` = 'Phòng đẹp, view biển. Trang trí hoa trong phòng. Chỉ hơi đông khách nên bữa sáng phải xếp hàng. Nhưng đồ ăn ngon.',
  `reply`   = NULL
WHERE `id` = 46;

UPDATE `reviews` SET
  `title`   = 'Suite 302 đang bảo trì',
  `comment` = 'Đặt phòng nhưng được báo đang maintenance. Đổi sang phòng 304. Phòng 304 đẹp hơn. Nhân viên xử lý tốt.',
  `reply`   = 'Xin lỗi vì sự bất tiện. Cảm ơn anh/chị đã thông cảm.'
WHERE `id` = 47;

UPDATE `reviews` SET
  `title`   = 'Suite 305 view 2 mặt',
  `comment` = 'Phòng view biển và thành phố. Đêm ngồi ban công ngắm đèn đẹp. Phòng khách rộng. Có 1 vòi sen hơi yếu.',
  `reply`   = NULL
WHERE `id` = 48;

UPDATE `reviews` SET
  `title`   = 'Standard 104 — công tác 3 đêm',
  `comment` = 'Đi họp công tác. Phòng gần lobby, tiện. View hồ bơi. Sạch sẽ. Bữa sáng có sớm từ 6h cho khách đi sớm.',
  `reply`   = NULL
WHERE `id` = 49;

UPDATE `reviews` SET
  `title`   = 'Lần 2 ở phòng 103',
  `comment` = 'Quay lại đặt cùng phòng. Vẫn ổn. Gần nhà hàng tiện. Giá không đổi. Resort giữ chất lượng.',
  `reply`   = NULL
WHERE `id` = 50;

UPDATE `reviews` SET
  `title`   = 'Standard 101 giá tốt',
  `comment` = 'Cặp đôi nghỉ 2 đêm. Phòng đủ dùng. View vườn xanh. Đi bộ ra biển gần. Hài lòng với giá 850k.',
  `reply`   = NULL
WHERE `id` = 51;

UPDATE `reviews` SET
  `title`   = 'Presidential cho sự kiện gia đình',
  `comment` = 'Tổ chức sinh nhật bố 70 tuổi. Phòng lớn, view 360. Dịch vụ quản gia chu đáo. Cả nhà rất vui. Đáng đồng tiền.',
  `reply`   = NULL
WHERE `id` = 52;

UPDATE `reviews` SET
  `title`   = 'Family 403 — bé 1 tuổi',
  `comment` = 'Đi với con 1 tuổi. Có nôi sẵn. Góc chơi an toàn. Gần bể bơi trẻ em. Mẹ yên tâm. Nhân viên hỗ trợ nhiệt tình.',
  `reply`   = 'Cảm ơn chị! Chúc bé hay ăn chóng lớn.'
WHERE `id` = 53;

SET FOREIGN_KEY_CHECKS = 1;
