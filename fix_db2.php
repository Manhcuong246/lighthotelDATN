<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

// Family
\App\Models\RoomType::where('id', 4)->update([
    'description' => 'Family Suite 55m² lý tưởng cho gia đình có trẻ em. Hai không gian ngủ độc lập, 1 giường lớn và 2 giường đơn đảm bảo sự thoải mái. Phòng có khu vui chơi nhỏ với sách truyện, đồ chơi an toàn. Phòng tắm đôi, bồn tắm nằm và bộ đồ dùng tắm hữu cơ riêng biệt cho bé. View vườn hoặc view biển, có ghế sofa giường rộng rãi hỗ trợ thêm không gian.'
]);

\App\Models\RoomType::where('id', 5)->update([
    'description' => 'Phòng Superior mang đến không gian 25m² nhỏ gọn, xinh xắn và hiện đại. Có lựa chọn 1 giường đôi lớn hoặc 2 giường đơn. Thiết kế chú trọng vào ánh sáng tự nhiên với cửa sổ kính lớn trần sàn. Trang bị đầy đủ tiện nghi cơ bản chất lượng dịch vụ 4 sao: TV màn hình phẳng, bàn làm việc, tủ quần áo thông minh... Lựa chọn tối ưu ngân sách mà vẫn sang trọng.'
]);

echo "Room Types descriptions updated successfully.\n";
