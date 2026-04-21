-- Cập nhật sức chứa cho loại phòng Standard
-- Tiêu chuẩn: 2 người, Tối đa: 3 người
UPDATE room_types 
SET standard_capacity = 2, 
    capacity = 3,
    adult_surcharge_rate = 0.25,
    child_surcharge_rate = 0.125
WHERE name LIKE '%Standard%' OR name LIKE '%standard%' OR name = 'Standard';

-- Hoặc cập nhật tất cả các loại phòng nếu cần
UPDATE room_types 
SET standard_capacity = COALESCE(standard_capacity, 2), 
    capacity = COALESCE(capacity, 3)
WHERE capacity < 3 OR standard_capacity IS NULL;

-- Kiểm tra kết quả
SELECT id, name, standard_capacity, capacity 
FROM room_types;
