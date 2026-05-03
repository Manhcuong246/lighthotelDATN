<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

// Standard
\App\Models\RoomType::where('id', 1)->update([
    'description' => 'Phòng Standard tại Light Hotel được thiết kế tối giản nhưng đầy đủ tiện nghi 5 sao. Diện tích 28-30m² với giường King-size hoặc 2 giường đơn, chăn ga cotton Ai Cập 500 thread count, điều hòa hai chiều. Phòng hướng vườn nhiệt đới hoặc hồ bơi, có ban công nhỏ lý tưởng cho buổi sáng nhâm nhi cà phê. Phù hợp cho cặp đôi, khách công tác ngắn ngày hoặc du khách tiết kiệm muốn trải nghiệm resort biển mà không phải chi quá nhiều.'
]);

// Deluxe
\App\Models\RoomType::where('id', 2)->update([
    'description' => 'Phòng Deluxe rộng 35-42m², nổi bật với thiết kế nội thất gỗ tự nhiên và ban công riêng rộng rãi nhìn ra toàn cảnh đại dương. Phòng được trang bị giường King-size cao cấp, khu vực ghế ngồi thư giãn, bồn tắm nằm và phòng tắm đứng riêng biệt. Khách lưu trú còn được tận hưởng máy pha cà phê espresso xịn xò trong phòng, minibar miễn phí vào ngày nhận phòng. Đích đến hoàn hảo cho kỳ nghỉ trăng mật hoặc các gia đình nhỏ yêu thích không gian rộng mở và tiện nghi cao cấp.'
]);

// Executive Suite
\App\Models\RoomType::where('id', 3)->update([
    'description' => 'Suite Executive mang đến trải nghiệm lưu trú xa hoa với diện tích lên đến 60-70m², gồm phòng khách và phòng ngủ riêng biệt. Nằm ở các tầng cao nhất với tầm nhìn panorama 180 độ ra biển, suite được trang trí bằng nghệ thuật đương đại, nội thất phong cách hoàng gia. Khách được hưởng đặc quyền VIP như check-in riêng, sử dụng Executive Lounge với tiệc trà chiều và cocktail tối miễn phí, cùng dịch vụ quản gia cá nhân 24/7. Lựa chọn tuyệt đỉnh cho doanh nhân hoặc những ai tìm kiếm sự hoàn mỹ và không gian biệt lập.'
]);

echo "Room Types descriptions updated successfully.\n";
