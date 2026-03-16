-- MySQL Database Export
-- Generated: 2026-03-09 19:26:31
-- Database: hotel_booking
-- Engine: InnoDB

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+00:00";

--
-- Table structure for table `amenities`
--

DROP TABLE IF EXISTS `amenities`;
CREATE TABLE `amenities` (
  `id` int NOT NULL AUTO_INCREMENT NOT NULL,
  `name` varchar(255) NOT NULL,
  `icon_url` varchar(255),
  `created_at` datetime,
  `updated_at` datetime
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `amenities`
--

INSERT INTO `amenities` (`id`, `name`, `icon_url`, `created_at`, `updated_at`) VALUES ('1', 'WiFi', 'wifi.png', NULL, NULL);
INSERT INTO `amenities` (`id`, `name`, `icon_url`, `created_at`, `updated_at`) VALUES ('2', 'Hồ bơi', 'pool.png', NULL, NULL);
INSERT INTO `amenities` (`id`, `name`, `icon_url`, `created_at`, `updated_at`) VALUES ('3', 'Gym', 'gym.png', NULL, NULL);
INSERT INTO `amenities` (`id`, `name`, `icon_url`, `created_at`, `updated_at`) VALUES ('4', 'Spa', 'spa.png', NULL, NULL);
INSERT INTO `amenities` (`id`, `name`, `icon_url`, `created_at`, `updated_at`) VALUES ('5', 'Nhà hàng', 'restaurant.png', NULL, NULL);
INSERT INTO `amenities` (`id`, `name`, `icon_url`, `created_at`, `updated_at`) VALUES ('6', 'Bar', 'bar.png', NULL, NULL);
INSERT INTO `amenities` (`id`, `name`, `icon_url`, `created_at`, `updated_at`) VALUES ('7', 'Đỗ xe miễn phí', 'parking.png', NULL, NULL);
INSERT INTO `amenities` (`id`, `name`, `icon_url`, `created_at`, `updated_at`) VALUES ('8', 'Điều hòa', 'ac.png', NULL, NULL);

--
-- Table structure for table `booking_logs`
--

DROP TABLE IF EXISTS `booking_logs`;
CREATE TABLE `booking_logs` (
  `id` int NOT NULL AUTO_INCREMENT NOT NULL,
  `booking_id` int NOT NULL,
  `old_status` varchar(255) NOT NULL,
  `new_status` varchar(255) NOT NULL,
  `changed_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `created_at` datetime,
  `updated_at` datetime
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Table structure for table `bookings`
--

DROP TABLE IF EXISTS `bookings`;
CREATE TABLE `bookings` (
  `id` int NOT NULL AUTO_INCREMENT NOT NULL,
  `user_id` int NOT NULL,
  `room_id` int NOT NULL,
  `check_in` varchar(255) NOT NULL,
  `check_out` varchar(255) NOT NULL,
  `actual_check_in` datetime,
  `actual_check_out` datetime,
  `guests` int,
  `total_price` varchar(255) NOT NULL,
  `status` varchar(255) NOT NULL DEFAULT 'pending',
  `created_at` datetime,
  `updated_at` datetime
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Table structure for table `cache`
--

DROP TABLE IF EXISTS `cache`;
CREATE TABLE `cache` (
  `key` varchar(255) NOT NULL,
  `value` text NOT NULL,
  `expiration` int NOT NULL,
  PRIMARY KEY ("key")
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Table structure for table `cache_locks`
--

DROP TABLE IF EXISTS `cache_locks`;
CREATE TABLE `cache_locks` (
  `key` varchar(255) NOT NULL,
  `owner` varchar(255) NOT NULL,
  `expiration` int NOT NULL,
  PRIMARY KEY ("key")
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Table structure for table `coupons`
--

DROP TABLE IF EXISTS `coupons`;
CREATE TABLE `coupons` (
  `id` int NOT NULL AUTO_INCREMENT NOT NULL,
  `code` varchar(255) NOT NULL,
  `discount_percent` int NOT NULL,
  `expired_at` varchar(255) NOT NULL,
  `is_active` varchar(255) NOT NULL DEFAULT '1',
  `created_at` datetime,
  `updated_at` datetime
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `coupons`
--

INSERT INTO `coupons` (`id`, `code`, `discount_percent`, `expired_at`, `is_active`, `created_at`, `updated_at`) VALUES ('1', 'WELCOME10', '10', '2026-09-09 17:35:10', '1', '2026-03-09 17:35:10', '2026-03-09 17:35:10');

--
-- Table structure for table `failed_jobs`
--

DROP TABLE IF EXISTS `failed_jobs`;
CREATE TABLE `failed_jobs` (
  `id` int NOT NULL AUTO_INCREMENT NOT NULL,
  `uuid` varchar(255) NOT NULL,
  `connection` text NOT NULL,
  `queue` text NOT NULL,
  `payload` text NOT NULL,
  `exception` text NOT NULL,
  `failed_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Table structure for table `hotel_info`
--

DROP TABLE IF EXISTS `hotel_info`;
CREATE TABLE `hotel_info` (
  `id` int NOT NULL DEFAULT '1',
  `name` varchar(255) NOT NULL,
  `description` text,
  `address` varchar(255),
  `phone` varchar(255),
  `email` varchar(255),
  `latitude` varchar(255),
  `longitude` varchar(255),
  `rating_avg` varchar(255) NOT NULL DEFAULT '0',
  `created_at` datetime,
  `updated_at` datetime,
  PRIMARY KEY ("id")
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `hotel_info`
--

INSERT INTO `hotel_info` (`id`, `name`, `description`, `address`, `phone`, `email`, `latitude`, `longitude`, `rating_avg`, `created_at`, `updated_at`) VALUES ('1', 'Light Hotel', 'Khách sạn sang trọng hàng đầu với dịch vụ 5 sao và tiện nghi hiện đại.', '123 Đường ABC, Quận 1, TP. Hồ Chí Minh', '+84 123 456 789', 'info@lighthotel.com', '10.776944', '106.700973', '0', NULL, NULL);

--
-- Table structure for table `images`
--

DROP TABLE IF EXISTS `images`;
CREATE TABLE `images` (
  `id` int NOT NULL AUTO_INCREMENT NOT NULL,
  `room_id` int,
  `image_url` varchar(255) NOT NULL,
  `image_type` varchar(255) NOT NULL,
  `created_at` datetime,
  `updated_at` datetime
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Table structure for table `job_batches`
--

DROP TABLE IF EXISTS `job_batches`;
CREATE TABLE `job_batches` (
  `id` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  `total_jobs` int NOT NULL,
  `pending_jobs` int NOT NULL,
  `failed_jobs` int NOT NULL,
  `failed_job_ids` text NOT NULL,
  `options` text,
  `cancelled_at` int,
  `created_at` int NOT NULL,
  `finished_at` int,
  PRIMARY KEY ("id")
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Table structure for table `jobs`
--

DROP TABLE IF EXISTS `jobs`;
CREATE TABLE `jobs` (
  `id` int NOT NULL AUTO_INCREMENT NOT NULL,
  `queue` varchar(255) NOT NULL,
  `payload` text NOT NULL,
  `attempts` int NOT NULL,
  `reserved_at` int,
  `available_at` int NOT NULL,
  `created_at` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Table structure for table `migrations`
--

DROP TABLE IF EXISTS `migrations`;
CREATE TABLE `migrations` (
  `id` int NOT NULL AUTO_INCREMENT NOT NULL,
  `migration` varchar(255) NOT NULL,
  `batch` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `migrations`
--

INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES ('1', '0001_01_01_000000_create_users_table', '1');
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES ('2', '0001_01_01_000001_create_cache_table', '1');
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES ('3', '0001_01_01_000002_create_jobs_table', '1');
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES ('4', '2026_02_04_000000_create_hotel_booking_tables', '1');
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES ('5', '2026_02_05_000003_add_updated_at_to_bookings_table', '1');
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES ('6', '2026_02_13_150328_create_room_types_table', '1');
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES ('7', '2026_02_13_150510_add_room_type_id_to_rooms_table', '1');
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES ('8', '2026_02_04_000001_update_users_table_for_hotel_booking', '2');
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES ('9', '2026_03_08_075909_add_capacity_and_price_to_room_types_table', '2');
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES ('10', '2026_03_09_174922_add_room_number_to_rooms_table', '3');
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES ('11', '2026_03_09_175622_add_beds_and_baths_to_room_types_table', '4');
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES ('12', '2026_03_09_180812_add_image_to_room_types_table', '5');
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES ('13', '2026_03_09_180906_add_image_to_rooms_table', '5');

--
-- Table structure for table `password_reset_tokens`
--

DROP TABLE IF EXISTS `password_reset_tokens`;
CREATE TABLE `password_reset_tokens` (
  `email` varchar(255) NOT NULL,
  `token` varchar(255) NOT NULL,
  `created_at` datetime,
  PRIMARY KEY ("email")
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Table structure for table `payments`
--

DROP TABLE IF EXISTS `payments`;
CREATE TABLE `payments` (
  `id` int NOT NULL AUTO_INCREMENT NOT NULL,
  `booking_id` int NOT NULL,
  `amount` varchar(255) NOT NULL,
  `method` varchar(255) NOT NULL,
  `status` varchar(255) NOT NULL,
  `paid_at` datetime,
  `created_at` datetime,
  `updated_at` datetime
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Table structure for table `reviews`
--

DROP TABLE IF EXISTS `reviews`;
CREATE TABLE `reviews` (
  `id` int NOT NULL AUTO_INCREMENT NOT NULL,
  `user_id` int NOT NULL,
  `room_id` int NOT NULL,
  `rating` int NOT NULL,
  `comment` text,
  `reply` text,
  `replied_at` datetime,
  `created_at` datetime,
  `updated_at` datetime
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Table structure for table `roles`
--

DROP TABLE IF EXISTS `roles`;
CREATE TABLE `roles` (
  `id` int NOT NULL AUTO_INCREMENT NOT NULL,
  `name` varchar(255) NOT NULL,
  `created_at` datetime,
  `updated_at` datetime
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `roles`
--

INSERT INTO `roles` (`id`, `name`, `created_at`, `updated_at`) VALUES ('1', 'admin', NULL, NULL);
INSERT INTO `roles` (`id`, `name`, `created_at`, `updated_at`) VALUES ('2', 'staff', NULL, NULL);
INSERT INTO `roles` (`id`, `name`, `created_at`, `updated_at`) VALUES ('3', 'guest', NULL, NULL);

--
-- Table structure for table `room_amenities`
--

DROP TABLE IF EXISTS `room_amenities`;
CREATE TABLE `room_amenities` (
  `room_id` int NOT NULL,
  `amenity_id` int NOT NULL,
  PRIMARY KEY ("room_id", "amenity_id")
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Table structure for table `room_booked_dates`
--

DROP TABLE IF EXISTS `room_booked_dates`;
CREATE TABLE `room_booked_dates` (
  `id` int NOT NULL AUTO_INCREMENT NOT NULL,
  `room_id` int NOT NULL,
  `booked_date` varchar(255) NOT NULL,
  `booking_id` int NOT NULL,
  `created_at` datetime,
  `updated_at` datetime
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Table structure for table `room_prices`
--

DROP TABLE IF EXISTS `room_prices`;
CREATE TABLE `room_prices` (
  `id` int NOT NULL AUTO_INCREMENT NOT NULL,
  `room_id` int NOT NULL,
  `price` varchar(255) NOT NULL,
  `start_date` varchar(255) NOT NULL,
  `end_date` varchar(255) NOT NULL,
  `created_at` datetime,
  `updated_at` datetime
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Table structure for table `room_types`
--

DROP TABLE IF EXISTS `room_types`;
CREATE TABLE `room_types` (
  `id` int NOT NULL AUTO_INCREMENT NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` text,
  `status` varchar(255) NOT NULL DEFAULT '1',
  `created_at` datetime,
  `updated_at` datetime,
  `capacity` int NOT NULL,
  `price` varchar(255) NOT NULL,
  `beds` int NOT NULL DEFAULT '1',
  `baths` int NOT NULL DEFAULT '1',
  `image` varchar(255)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `room_types`
--

INSERT INTO `room_types` (`id`, `name`, `description`, `status`, `created_at`, `updated_at`, `capacity`, `price`, `beds`, `baths`, `image`) VALUES ('1', 'Phòng đơn', NULL, '1', NULL, '2026-03-09 18:38:01', '2', '1000000', '1', '1', 'room_types/ambbdsTuISVuLhFe7A700yovyYS2BGYmoaj4Gb89.png');
INSERT INTO `room_types` (`id`, `name`, `description`, `status`, `created_at`, `updated_at`, `capacity`, `price`, `beds`, `baths`, `image`) VALUES ('2', 'Phòng đôi', NULL, '1', NULL, '2026-03-09 18:37:51', '4', '1500000', '1', '1', 'room_types/vCvp7sknh3565yOVaURxcluakMgRveKyckI60JI7.jpg');
INSERT INTO `room_types` (`id`, `name`, `description`, `status`, `created_at`, `updated_at`, `capacity`, `price`, `beds`, `baths`, `image`) VALUES ('3', 'Phòng VIP', NULL, '1', NULL, '2026-03-09 18:38:20', '6', '2500000', '1', '1', 'room_types/MCU51AKswYfcop53uMaYfdt5fS8JQgYboICA4X2Z.jpg');

--
-- Table structure for table `rooms`
--

DROP TABLE IF EXISTS `rooms`;
CREATE TABLE `rooms` (
  `id` int NOT NULL AUTO_INCREMENT NOT NULL,
  `name` varchar(255),
  `type` varchar(255),
  `base_price` varchar(255),
  `max_guests` int,
  `beds` int,
  `baths` int,
  `area` double,
  `description` text,
  `status` varchar(255) NOT NULL DEFAULT ('available'),
  `created_at` datetime,
  `updated_at` datetime,
  `room_type_id` int,
  `room_number` varchar(255),
  `image` varchar(255)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `rooms`
--

INSERT INTO `rooms` (`id`, `name`, `type`, `base_price`, `max_guests`, `beds`, `baths`, `area`, `description`, `status`, `created_at`, `updated_at`, `room_type_id`, `room_number`, `image`) VALUES ('5', 'Phòng 245', 'Phòng đơn', '1000000', '2', '1', '1', '50', 'okok', 'available', NULL, NULL, '1', '001', NULL);
INSERT INTO `rooms` (`id`, `name`, `type`, `base_price`, `max_guests`, `beds`, `baths`, `area`, `description`, `status`, `created_at`, `updated_at`, `room_type_id`, `room_number`, `image`) VALUES ('6', 'Phòng 110', 'Phòng đôi', '1500000', '4', '1', '1', '75', 'hihi', 'available', NULL, NULL, '2', '002', NULL);
INSERT INTO `rooms` (`id`, `name`, `type`, `base_price`, `max_guests`, `beds`, `baths`, `area`, `description`, `status`, `created_at`, `updated_at`, `room_type_id`, `room_number`, `image`) VALUES ('7', 'Phòng 010', 'Phòng đơn', '1000000', '2', '1', '1', '55', 'hé hé', 'available', NULL, NULL, '1', NULL, NULL);
INSERT INTO `rooms` (`id`, `name`, `type`, `base_price`, `max_guests`, `beds`, `baths`, `area`, `description`, `status`, `created_at`, `updated_at`, `room_type_id`, `room_number`, `image`) VALUES ('8', 'Phòng 205', 'Phòng VIP', '2500000', '6', '1', '1', '57', 'huhu', 'available', NULL, NULL, '3', NULL, NULL);
INSERT INTO `rooms` (`id`, `name`, `type`, `base_price`, `max_guests`, `beds`, `baths`, `area`, `description`, `status`, `created_at`, `updated_at`, `room_type_id`, `room_number`, `image`) VALUES ('9', 'Phòng 567', 'Phòng đôi', '1500000', '4', '1', '1', '70', 'koko', 'available', NULL, NULL, '2', NULL, NULL);

--
-- Table structure for table `services`
--

DROP TABLE IF EXISTS `services`;
CREATE TABLE `services` (
  `id` int NOT NULL AUTO_INCREMENT NOT NULL,
  `name` varchar(255) NOT NULL,
  `price` varchar(255) NOT NULL,
  `description` text,
  `created_at` datetime,
  `updated_at` datetime
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `services`
--

INSERT INTO `services` (`id`, `name`, `price`, `description`, `created_at`, `updated_at`) VALUES ('1', 'Đón sân bay', '500000', 'Dịch vụ đón từ sân bay về khách sạn', NULL, NULL);
INSERT INTO `services` (`id`, `name`, `price`, `description`, `created_at`, `updated_at`) VALUES ('2', 'Giặt ủi', '100000', 'Dịch vụ giặt ủi nhanh chóng', NULL, NULL);
INSERT INTO `services` (`id`, `name`, `price`, `description`, `created_at`, `updated_at`) VALUES ('3', 'Room Service', '150000', 'Phục vụ ăn uống tại phòng 24/7', NULL, NULL);
INSERT INTO `services` (`id`, `name`, `price`, `description`, `created_at`, `updated_at`) VALUES ('4', 'Tour du lịch', '1000000', 'Tour tham quan thành phố', NULL, NULL);

--
-- Table structure for table `sessions`
--

DROP TABLE IF EXISTS `sessions`;
CREATE TABLE `sessions` (
  `id` varchar(255) NOT NULL,
  `user_id` int,
  `ip_address` varchar(255),
  `user_agent` text,
  `payload` text NOT NULL,
  `last_activity` int NOT NULL,
  PRIMARY KEY ("id")
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Table structure for table `site_contents`
--

DROP TABLE IF EXISTS `site_contents`;
CREATE TABLE `site_contents` (
  `id` int NOT NULL AUTO_INCREMENT NOT NULL,
  `type` varchar(255) NOT NULL,
  `title` varchar(255),
  `content` text,
  `image_url` varchar(255),
  `is_active` varchar(255) NOT NULL DEFAULT '1',
  `created_at` datetime,
  `updated_at` datetime
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `site_contents`
--

INSERT INTO `site_contents` (`id`, `type`, `title`, `content`, `image_url`, `is_active`, `created_at`, `updated_at`) VALUES ('1', 'banner', 'Chào mừng đến với Light Hotel', 'Trải nghiệm nghỉ dưỡng đẳng cấp', 'banner.jpg', '1', NULL, NULL);
INSERT INTO `site_contents` (`id`, `type`, `title`, `content`, `image_url`, `is_active`, `created_at`, `updated_at`) VALUES ('2', 'about', 'Về Light Hotel', 'Light Hotel là khách sạn 5 sao hàng đầu với dịch vụ chuyên nghiệp và tiện nghi hiện đại.', NULL, '1', NULL, NULL);

--
-- Table structure for table `user_roles`
--

DROP TABLE IF EXISTS `user_roles`;
CREATE TABLE `user_roles` (
  `user_id` int NOT NULL,
  `role_id` int NOT NULL,
  PRIMARY KEY ("user_id", "role_id")
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `user_roles`
--

INSERT INTO `user_roles` (`user_id`, `role_id`) VALUES ('1', '1');

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
  `id` int NOT NULL AUTO_INCREMENT NOT NULL,
  `full_name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `email_verified_at` datetime,
  `password` varchar(255) NOT NULL,
  `remember_token` varchar(255),
  `created_at` datetime,
  `updated_at` datetime,
  `phone` varchar(255),
  `avatar_url` varchar(255),
  `status` varchar(255) NOT NULL DEFAULT 'active'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `full_name`, `email`, `email_verified_at`, `password`, `remember_token`, `created_at`, `updated_at`, `phone`, `avatar_url`, `status`) VALUES ('1', 'Admin User', 'admin@hotel.local', NULL, '$2y$12$0u8o2SdNrR0Y4/aj8rqFl.QcwoahoXFQvF0oHbJKAlpDuML7rqTPC', NULL, '2026-03-09 17:32:37', '2026-03-09 17:32:37', '0123456789', NULL, 'active');

COMMIT;
