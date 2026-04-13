-- Bảng pivot: dịch vụ đi kèm theo loại phòng (tương đương migration 2026_04_07_230000).
-- Chạy một lần nếu chưa chạy: php artisan migrate

SET FOREIGN_KEY_CHECKS = 0;

CREATE TABLE IF NOT EXISTS `room_type_service` (
  `room_type_id` BIGINT UNSIGNED NOT NULL,
  `service_id` BIGINT UNSIGNED NOT NULL,
  PRIMARY KEY (`room_type_id`, `service_id`),
  CONSTRAINT `room_type_service_room_type_id_foreign`
    FOREIGN KEY (`room_type_id`) REFERENCES `room_types` (`id`) ON DELETE CASCADE,
  CONSTRAINT `room_type_service_service_id_foreign`
    FOREIGN KEY (`service_id`) REFERENCES `services` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;
