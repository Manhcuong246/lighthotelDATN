-- SQLite Database Export
-- Generated: 2026-03-09 19:24:06
-- Database: hotel_booking

DROP TABLE IF EXISTS `migrations`;
CREATE TABLE "migrations" ("id" integer primary key autoincrement not null, "migration" varchar not null, "batch" integer not null);

INSERT INTO `migrations` (id,migration,batch) VALUES ('1','0001_01_01_000000_create_users_table','1');
INSERT INTO `migrations` (id,migration,batch) VALUES ('2','0001_01_01_000001_create_cache_table','1');
INSERT INTO `migrations` (id,migration,batch) VALUES ('3','0001_01_01_000002_create_jobs_table','1');
INSERT INTO `migrations` (id,migration,batch) VALUES ('4','2026_02_04_000000_create_hotel_booking_tables','1');
INSERT INTO `migrations` (id,migration,batch) VALUES ('5','2026_02_05_000003_add_updated_at_to_bookings_table','1');
INSERT INTO `migrations` (id,migration,batch) VALUES ('6','2026_02_13_150328_create_room_types_table','1');
INSERT INTO `migrations` (id,migration,batch) VALUES ('7','2026_02_13_150510_add_room_type_id_to_rooms_table','1');
INSERT INTO `migrations` (id,migration,batch) VALUES ('8','2026_02_04_000001_update_users_table_for_hotel_booking','2');
INSERT INTO `migrations` (id,migration,batch) VALUES ('9','2026_03_08_075909_add_capacity_and_price_to_room_types_table','2');
INSERT INTO `migrations` (id,migration,batch) VALUES ('10','2026_03_09_174922_add_room_number_to_rooms_table','3');
INSERT INTO `migrations` (id,migration,batch) VALUES ('11','2026_03_09_175622_add_beds_and_baths_to_room_types_table','4');
INSERT INTO `migrations` (id,migration,batch) VALUES ('12','2026_03_09_180812_add_image_to_room_types_table','5');
INSERT INTO `migrations` (id,migration,batch) VALUES ('13','2026_03_09_180906_add_image_to_rooms_table','5');

DROP TABLE IF EXISTS `users`;
CREATE TABLE "users" ("id" integer primary key autoincrement not null, "full_name" varchar not null, "email" varchar not null, "email_verified_at" datetime, "password" varchar not null, "remember_token" varchar, "created_at" datetime, "updated_at" datetime, "phone" varchar, "avatar_url" varchar, "status" varchar check ("status" in ('active', 'inactive', 'banned')) not null default 'active');

INSERT INTO `users` (id,full_name,email,email_verified_at,password,remember_token,created_at,updated_at,phone,avatar_url,status) VALUES ('1','Admin User','admin@hotel.local',NULL,'$2y$12$0u8o2SdNrR0Y4/aj8rqFl.QcwoahoXFQvF0oHbJKAlpDuML7rqTPC',NULL,'2026-03-09 17:32:37','2026-03-09 17:32:37','0123456789',NULL,'active');

DROP TABLE IF EXISTS `password_reset_tokens`;
CREATE TABLE "password_reset_tokens" ("email" varchar not null, "token" varchar not null, "created_at" datetime, primary key ("email"));

DROP TABLE IF EXISTS `sessions`;
CREATE TABLE "sessions" ("id" varchar not null, "user_id" integer, "ip_address" varchar, "user_agent" text, "payload" text not null, "last_activity" integer not null, primary key ("id"));

DROP TABLE IF EXISTS `cache`;
CREATE TABLE "cache" ("key" varchar not null, "value" text not null, "expiration" integer not null, primary key ("key"));

DROP TABLE IF EXISTS `cache_locks`;
CREATE TABLE "cache_locks" ("key" varchar not null, "owner" varchar not null, "expiration" integer not null, primary key ("key"));

DROP TABLE IF EXISTS `jobs`;
CREATE TABLE "jobs" ("id" integer primary key autoincrement not null, "queue" varchar not null, "payload" text not null, "attempts" integer not null, "reserved_at" integer, "available_at" integer not null, "created_at" integer not null);

DROP TABLE IF EXISTS `job_batches`;
CREATE TABLE "job_batches" ("id" varchar not null, "name" varchar not null, "total_jobs" integer not null, "pending_jobs" integer not null, "failed_jobs" integer not null, "failed_job_ids" text not null, "options" text, "cancelled_at" integer, "created_at" integer not null, "finished_at" integer, primary key ("id"));

DROP TABLE IF EXISTS `failed_jobs`;
CREATE TABLE "failed_jobs" ("id" integer primary key autoincrement not null, "uuid" varchar not null, "connection" text not null, "queue" text not null, "payload" text not null, "exception" text not null, "failed_at" datetime not null default CURRENT_TIMESTAMP);

DROP TABLE IF EXISTS `roles`;
CREATE TABLE "roles" ("id" integer primary key autoincrement not null, "name" varchar not null, "created_at" datetime, "updated_at" datetime);

INSERT INTO `roles` (id,name,created_at,updated_at) VALUES ('1','admin',NULL,NULL);
INSERT INTO `roles` (id,name,created_at,updated_at) VALUES ('2','staff',NULL,NULL);
INSERT INTO `roles` (id,name,created_at,updated_at) VALUES ('3','guest',NULL,NULL);

DROP TABLE IF EXISTS `user_roles`;
CREATE TABLE "user_roles" ("user_id" integer not null, "role_id" integer not null, foreign key("user_id") references "users"("id") on delete cascade, foreign key("role_id") references "roles"("id") on delete cascade, primary key ("user_id", "role_id"));

INSERT INTO `user_roles` (user_id,role_id) VALUES ('1','1');

DROP TABLE IF EXISTS `hotel_info`;
CREATE TABLE "hotel_info" ("id" integer not null default '1', "name" varchar not null, "description" text, "address" varchar, "phone" varchar, "email" varchar, "latitude" numeric, "longitude" numeric, "rating_avg" numeric not null default '0', "created_at" datetime, "updated_at" datetime, primary key ("id"));

INSERT INTO `hotel_info` (id,name,description,address,phone,email,latitude,longitude,rating_avg,created_at,updated_at) VALUES ('1','Light Hotel','Khách sạn sang trọng hàng đầu với dịch vụ 5 sao và tiện nghi hiện đại.','123 Đường ABC, Quận 1, TP. Hồ Chí Minh','+84 123 456 789','info@lighthotel.com','10.776944','106.700973','0',NULL,NULL);

DROP TABLE IF EXISTS `room_prices`;
CREATE TABLE "room_prices" ("id" integer primary key autoincrement not null, "room_id" integer not null, "price" numeric not null, "start_date" date not null, "end_date" date not null, "created_at" datetime, "updated_at" datetime, foreign key("room_id") references "rooms"("id") on delete cascade);

DROP TABLE IF EXISTS `amenities`;
CREATE TABLE "amenities" ("id" integer primary key autoincrement not null, "name" varchar not null, "icon_url" varchar, "created_at" datetime, "updated_at" datetime);

INSERT INTO `amenities` (id,name,icon_url,created_at,updated_at) VALUES ('1','WiFi','wifi.png',NULL,NULL);
INSERT INTO `amenities` (id,name,icon_url,created_at,updated_at) VALUES ('2','Hồ bơi','pool.png',NULL,NULL);
INSERT INTO `amenities` (id,name,icon_url,created_at,updated_at) VALUES ('3','Gym','gym.png',NULL,NULL);
INSERT INTO `amenities` (id,name,icon_url,created_at,updated_at) VALUES ('4','Spa','spa.png',NULL,NULL);
INSERT INTO `amenities` (id,name,icon_url,created_at,updated_at) VALUES ('5','Nhà hàng','restaurant.png',NULL,NULL);
INSERT INTO `amenities` (id,name,icon_url,created_at,updated_at) VALUES ('6','Bar','bar.png',NULL,NULL);
INSERT INTO `amenities` (id,name,icon_url,created_at,updated_at) VALUES ('7','Đỗ xe miễn phí','parking.png',NULL,NULL);
INSERT INTO `amenities` (id,name,icon_url,created_at,updated_at) VALUES ('8','Điều hòa','ac.png',NULL,NULL);

DROP TABLE IF EXISTS `room_amenities`;
CREATE TABLE "room_amenities" ("room_id" integer not null, "amenity_id" integer not null, foreign key("room_id") references "rooms"("id") on delete cascade, foreign key("amenity_id") references "amenities"("id") on delete cascade, primary key ("room_id", "amenity_id"));

DROP TABLE IF EXISTS `services`;
CREATE TABLE "services" ("id" integer primary key autoincrement not null, "name" varchar not null, "price" numeric not null, "description" text, "created_at" datetime, "updated_at" datetime);

INSERT INTO `services` (id,name,price,description,created_at,updated_at) VALUES ('1','Đón sân bay','500000','Dịch vụ đón từ sân bay về khách sạn',NULL,NULL);
INSERT INTO `services` (id,name,price,description,created_at,updated_at) VALUES ('2','Giặt ủi','100000','Dịch vụ giặt ủi nhanh chóng',NULL,NULL);
INSERT INTO `services` (id,name,price,description,created_at,updated_at) VALUES ('3','Room Service','150000','Phục vụ ăn uống tại phòng 24/7',NULL,NULL);
INSERT INTO `services` (id,name,price,description,created_at,updated_at) VALUES ('4','Tour du lịch','1000000','Tour tham quan thành phố',NULL,NULL);

DROP TABLE IF EXISTS `images`;
CREATE TABLE "images" ("id" integer primary key autoincrement not null, "room_id" integer, "image_url" varchar not null, "image_type" varchar check ("image_type" in ('hotel', 'room')) not null, "created_at" datetime, "updated_at" datetime, foreign key("room_id") references "rooms"("id") on delete cascade);

DROP TABLE IF EXISTS `bookings`;
CREATE TABLE "bookings" ("id" integer primary key autoincrement not null, "user_id" integer not null, "room_id" integer not null, "check_in" date not null, "check_out" date not null, "actual_check_in" datetime, "actual_check_out" datetime, "guests" integer, "total_price" numeric not null, "status" varchar check ("status" in ('pending', 'confirmed', 'cancelled', 'completed')) not null default 'pending', "created_at" datetime, "updated_at" datetime, foreign key("user_id") references "users"("id") on delete cascade, foreign key("room_id") references "rooms"("id") on delete cascade);

DROP TABLE IF EXISTS `room_booked_dates`;
CREATE TABLE "room_booked_dates" ("id" integer primary key autoincrement not null, "room_id" integer not null, "booked_date" date not null, "booking_id" integer not null, "created_at" datetime, "updated_at" datetime, foreign key("room_id") references "rooms"("id") on delete cascade, foreign key("booking_id") references "bookings"("id") on delete cascade);

DROP TABLE IF EXISTS `payments`;
CREATE TABLE "payments" ("id" integer primary key autoincrement not null, "booking_id" integer not null, "amount" numeric not null, "method" varchar not null, "status" varchar check ("status" in ('pending', 'paid', 'failed')) not null, "paid_at" datetime, "created_at" datetime, "updated_at" datetime, foreign key("booking_id") references "bookings"("id") on delete cascade);

DROP TABLE IF EXISTS `booking_logs`;
CREATE TABLE "booking_logs" ("id" integer primary key autoincrement not null, "booking_id" integer not null, "old_status" varchar not null, "new_status" varchar not null, "changed_at" datetime not null default CURRENT_TIMESTAMP, "created_at" datetime, "updated_at" datetime, foreign key("booking_id") references "bookings"("id") on delete cascade);

DROP TABLE IF EXISTS `reviews`;
CREATE TABLE "reviews" ("id" integer primary key autoincrement not null, "user_id" integer not null, "room_id" integer not null, "rating" integer not null, "comment" text, "reply" text, "replied_at" datetime, "created_at" datetime, "updated_at" datetime, foreign key("user_id") references "users"("id") on delete cascade, foreign key("room_id") references "rooms"("id") on delete cascade);

DROP TABLE IF EXISTS `coupons`;
CREATE TABLE "coupons" ("id" integer primary key autoincrement not null, "code" varchar not null, "discount_percent" integer not null, "expired_at" date not null, "is_active" tinyint(1) not null default '1', "created_at" datetime, "updated_at" datetime);

INSERT INTO `coupons` (id,code,discount_percent,expired_at,is_active,created_at,updated_at) VALUES ('1','WELCOME10','10','2026-09-09 17:35:10','1','2026-03-09 17:35:10','2026-03-09 17:35:10');

DROP TABLE IF EXISTS `site_contents`;
CREATE TABLE "site_contents" ("id" integer primary key autoincrement not null, "type" varchar check ("type" in ('banner', 'policy', 'about', 'footer')) not null, "title" varchar, "content" text, "image_url" varchar, "is_active" tinyint(1) not null default '1', "created_at" datetime, "updated_at" datetime);

INSERT INTO `site_contents` (id,type,title,content,image_url,is_active,created_at,updated_at) VALUES ('1','banner','Chào mừng đến với Light Hotel','Trải nghiệm nghỉ dưỡng đẳng cấp','banner.jpg','1',NULL,NULL);
INSERT INTO `site_contents` (id,type,title,content,image_url,is_active,created_at,updated_at) VALUES ('2','about','Về Light Hotel','Light Hotel là khách sạn 5 sao hàng đầu với dịch vụ chuyên nghiệp và tiện nghi hiện đại.',NULL,'1',NULL,NULL);

DROP TABLE IF EXISTS `room_types`;
CREATE TABLE "room_types" ("id" integer primary key autoincrement not null, "name" varchar not null, "description" text, "status" tinyint(1) not null default '1', "created_at" datetime, "updated_at" datetime, "capacity" integer not null, "price" numeric not null, "beds" integer not null default '1', "baths" integer not null default '1', "image" varchar);

INSERT INTO `room_types` (id,name,description,status,created_at,updated_at,capacity,price,beds,baths,image) VALUES ('1','Phòng đơn',NULL,'1',NULL,'2026-03-09 18:38:01','2','1000000','1','1','room_types/ambbdsTuISVuLhFe7A700yovyYS2BGYmoaj4Gb89.png');
INSERT INTO `room_types` (id,name,description,status,created_at,updated_at,capacity,price,beds,baths,image) VALUES ('2','Phòng đôi',NULL,'1',NULL,'2026-03-09 18:37:51','4','1500000','1','1','room_types/vCvp7sknh3565yOVaURxcluakMgRveKyckI60JI7.jpg');
INSERT INTO `room_types` (id,name,description,status,created_at,updated_at,capacity,price,beds,baths,image) VALUES ('3','Phòng VIP',NULL,'1',NULL,'2026-03-09 18:38:20','6','2500000','1','1','room_types/MCU51AKswYfcop53uMaYfdt5fS8JQgYboICA4X2Z.jpg');

DROP TABLE IF EXISTS `rooms`;
CREATE TABLE "rooms" ("id" integer primary key autoincrement not null, "name" varchar, "type" varchar, "base_price" numeric, "max_guests" integer, "beds" integer, "baths" integer, "area" float, "description" text, "status" varchar not null default ('available'), "created_at" datetime, "updated_at" datetime, "room_type_id" integer, "room_number" varchar, "image" varchar, foreign key("room_type_id") references "room_types"("id") on delete set null);

INSERT INTO `rooms` (id,name,type,base_price,max_guests,beds,baths,area,description,status,created_at,updated_at,room_type_id,room_number,image) VALUES ('5','Phòng 245','Phòng đơn','1000000','2','1','1','50','okok','available',NULL,NULL,'1','001',NULL);
INSERT INTO `rooms` (id,name,type,base_price,max_guests,beds,baths,area,description,status,created_at,updated_at,room_type_id,room_number,image) VALUES ('6','Phòng 110','Phòng đôi','1500000','4','1','1','75','hihi','available',NULL,NULL,'2','002',NULL);
INSERT INTO `rooms` (id,name,type,base_price,max_guests,beds,baths,area,description,status,created_at,updated_at,room_type_id,room_number,image) VALUES ('7','Phòng 010','Phòng đơn','1000000','2','1','1','55','hé hé','available',NULL,NULL,'1',NULL,NULL);
INSERT INTO `rooms` (id,name,type,base_price,max_guests,beds,baths,area,description,status,created_at,updated_at,room_type_id,room_number,image) VALUES ('8','Phòng 205','Phòng VIP','2500000','6','1','1','57','huhu','available',NULL,NULL,'3',NULL,NULL);
INSERT INTO `rooms` (id,name,type,base_price,max_guests,beds,baths,area,description,status,created_at,updated_at,room_type_id,room_number,image) VALUES ('9','Phòng 567','Phòng đôi','1500000','4','1','1','70','koko','available',NULL,NULL,'2',NULL,NULL);

