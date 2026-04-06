-- =============================================================================
-- Thay ảnh phòng thủ công (phpMyAdmin / MySQL)
-- =============================================================================
-- Laravel đọc:
--   - bảng `rooms`: cột `image` = ảnh đại diện (đường dẫn tương đối hoặc URL đầy đủ)
--   - bảng `images`: mỗi dòng 1 ảnh gallery, `room_id` + `image_url`, `image_type` = 'room'
-- Ảnh lưu trên đĩa (disk public): storage/app/public/...
-- Trên web: /storage/<image_url>   ví dụ: room_images/rooms/room_1_1.jpg
-- SAI thường gặp: đừng ghi storage/... trong DB (sẽ thành /storage/storage/...). Chỉ ghi phần trong storage/app/public.
--
-- Nếu dự án đổi ROOM_IMAGES_DIRECTORY trong .env, thay tiền tố `room_images/` bên dưới.
-- =============================================================================

SET NAMES utf8mb4;

-- -----------------------------------------------------------------------------
-- Bước 1: Xóa ảnh gallery cũ gắn với phòng (KHÔNG xóa ảnh hotel nếu có room_id NULL?)
-- Nếu bạn chỉ dùng images cho phòng, dòng này đủ:
-- -----------------------------------------------------------------------------
DELETE FROM `images` WHERE `room_id` IS NOT NULL;

-- -----------------------------------------------------------------------------
-- Bước 2: Gán ảnh đại diện mặc định = ảnh số 1 của từng phòng (theo id)
-- Điều chỉnh công thức nếu bạn đặt tên file khác trong storage/app/public
-- -----------------------------------------------------------------------------
UPDATE `rooms`
SET `image` = CONCAT('room_images/rooms/room_', `id`, '_1.jpg')
WHERE 1 = 1;

-- -----------------------------------------------------------------------------
-- Bước 3: Chèn lại 4 ảnh gallery / phòng (đường dẫn giống RoomImageStorage)
-- Yêu cầu: file thật phải tồn tại dưới storage/app/public/room_images/rooms/
-- Nếu bảng `images` có created_at / updated_at NOT NULL, giữ 2 cột trong INSERT;
-- nếu không có 2 cột này, xóa chúng khỏi INSERT.
-- -----------------------------------------------------------------------------
INSERT INTO `images` (`room_id`, `image_url`, `image_type`, `created_at`, `updated_at`)
SELECT `r`.`id`,
       CONCAT('room_images/rooms/room_', `r`.`id`, '_1.jpg'),
       'room',
       NOW(),
       NOW()
FROM `rooms` AS `r`;

INSERT INTO `images` (`room_id`, `image_url`, `image_type`, `created_at`, `updated_at`)
SELECT `r`.`id`,
       CONCAT('room_images/rooms/room_', `r`.`id`, '_2.jpg'),
       'room',
       NOW(),
       NOW()
FROM `rooms` AS `r`;

INSERT INTO `images` (`room_id`, `image_url`, `image_type`, `created_at`, `updated_at`)
SELECT `r`.`id`,
       CONCAT('room_images/rooms/room_', `r`.`id`, '_3.jpg'),
       'room',
       NOW(),
       NOW()
FROM `rooms` AS `r`;

INSERT INTO `images` (`room_id`, `image_url`, `image_type`, `created_at`, `updated_at`)
SELECT `r`.`id`,
       CONCAT('room_images/rooms/room_', `r`.`id`, '_4.jpg'),
       'room',
       NOW(),
       NOW()
FROM `rooms` AS `r`;

-- =============================================================================
-- Mẫu SỬA TỪNG PHÌNH (thay bằng URL ngoài — không cần file trên server)
-- =============================================================================
-- UPDATE `rooms` SET `image` = 'https://example.com/hinh/phong101.jpg' WHERE `id` = 1;
-- DELETE FROM `images` WHERE `room_id` = 1;
-- INSERT INTO `images` (`room_id`, `image_url`, `image_type`, `created_at`, `updated_at`) VALUES
-- (1, 'https://example.com/hinh/a.jpg', 'room', NOW(), NOW()),
-- (1, 'https://example.com/hinh/b.jpg', 'room', NOW(), NOW());

-- =============================================================================
-- Nếu INSERT báo lỗi thiếu cột: thử bản rút gọn (bỏ created_at, updated_at)
-- =============================================================================
-- INSERT INTO `images` (`room_id`, `image_url`, `image_type`)
-- SELECT `id`, CONCAT('room_images/rooms/room_', `id`, '_1.jpg'), 'room' FROM `rooms`;
