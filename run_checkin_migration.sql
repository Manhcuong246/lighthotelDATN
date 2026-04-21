-- Migration: Thêm booking_room_id vào booking_guests
-- Chạy file này trong phpMyAdmin hoặc MySQL client

-- 1. Thêm cột booking_room_id
ALTER TABLE booking_guests 
ADD COLUMN booking_room_id BIGINT UNSIGNED NULL AFTER booking_id;

-- 2. Thêm index
ALTER TABLE booking_guests 
ADD INDEX booking_guests_booking_room_id_index (booking_room_id),
ADD INDEX booking_guests_booking_id_room_id_index (booking_id, booking_room_id);

-- 3. Thêm foreign key (tùy chọn - nếu gặp lỗi thì bỏ qua bước này)
-- ALTER TABLE booking_guests 
-- ADD CONSTRAINT booking_guests_booking_room_id_foreign 
--     FOREIGN KEY (booking_room_id) REFERENCES booking_rooms(id) 
--     ON DELETE SET NULL;

-- Kiểm tra kết quả
DESCRIBE booking_guests;
