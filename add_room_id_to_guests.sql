-- Thêm cột room_id vào bảng guests
ALTER TABLE `guests` 
ADD COLUMN `room_id` BIGINT UNSIGNED NULL AFTER `booking_id`,
ADD INDEX `guests_room_id_index` (`room_id`),
ADD INDEX `guests_booking_id_room_id_index` (`booking_id`, `room_id`),
ADD CONSTRAINT `guests_room_id_foreign` 
    FOREIGN KEY (`room_id`) 
    REFERENCES `rooms` (`id`) 
    ON DELETE SET NULL;
