-- Schema database cho hệ thống đặt phòng khách sạn Light Hotel
-- Sao chép từ đề bài

DROP DATABASE IF EXISTS hotel_booking;
CREATE DATABASE hotel_booking
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;
USE hotel_booking;

CREATE TABLE users (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(150) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    phone VARCHAR(20),
    avatar_url VARCHAR(255),
    status ENUM('active','inactive','banned') DEFAULT 'active',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE roles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) UNIQUE
);

INSERT INTO roles (name) VALUES
('guest'),
('staff'),
('admin');

CREATE TABLE user_roles (
    user_id BIGINT UNSIGNED,
    role_id INT,
    PRIMARY KEY (user_id, role_id),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE CASCADE
);

CREATE TABLE hotel_info (
    id TINYINT PRIMARY KEY DEFAULT 1,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    address VARCHAR(255),
    phone VARCHAR(20),
    email VARCHAR(150),
    latitude DECIMAL(10,8),
    longitude DECIMAL(11,8),
    rating_avg DECIMAL(3,2) DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE rooms (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255),
    type VARCHAR(100),
    base_price DECIMAL(10,2),
    max_guests INT,
    beds INT,
    baths INT,
    area FLOAT,
    description TEXT,
    status ENUM('available','booked','maintenance')
        DEFAULT 'available',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE room_prices (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    room_id BIGINT UNSIGNED,
    price DECIMAL(10,2),
    start_date DATE,
    end_date DATE,
    FOREIGN KEY (room_id) REFERENCES rooms(id) ON DELETE CASCADE
);

CREATE TABLE amenities (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100),
    icon_url VARCHAR(255)
);

CREATE TABLE room_amenities (
    room_id BIGINT UNSIGNED,
    amenity_id INT,
    PRIMARY KEY (room_id, amenity_id),
    FOREIGN KEY (room_id) REFERENCES rooms(id) ON DELETE CASCADE,
    FOREIGN KEY (amenity_id) REFERENCES amenities(id) ON DELETE CASCADE
);

CREATE TABLE services (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100),
    price DECIMAL(10,2),
    description TEXT
);

CREATE TABLE images (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    room_id BIGINT UNSIGNED NULL,
    image_url VARCHAR(255),
    image_type ENUM('hotel','room'),
    FOREIGN KEY (room_id) REFERENCES rooms(id) ON DELETE CASCADE
);

CREATE TABLE bookings (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED,
    room_id BIGINT UNSIGNED,
    check_in DATE,
    check_out DATE,
    actual_check_in DATETIME,
    actual_check_out DATETIME,
    guests INT,
    total_price DECIMAL(12,2),
    status ENUM('pending','confirmed','cancelled','completed')
        DEFAULT 'pending',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (room_id) REFERENCES rooms(id) ON DELETE CASCADE
);

CREATE TABLE room_booked_dates (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    room_id BIGINT UNSIGNED,
    booked_date DATE,
    booking_id BIGINT UNSIGNED,
    UNIQUE (room_id, booked_date),
    FOREIGN KEY (room_id) REFERENCES rooms(id) ON DELETE CASCADE,
    FOREIGN KEY (booking_id) REFERENCES bookings(id) ON DELETE CASCADE
);

CREATE TABLE payments (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    booking_id BIGINT UNSIGNED,
    amount DECIMAL(12,2),
    method VARCHAR(50),
    status ENUM('pending','paid','failed'),
    paid_at DATETIME,
    FOREIGN KEY (booking_id) REFERENCES bookings(id) ON DELETE CASCADE
);

CREATE TABLE booking_logs (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    booking_id BIGINT UNSIGNED,
    old_status VARCHAR(50),
    new_status VARCHAR(50),
    changed_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (booking_id) REFERENCES bookings(id) ON DELETE CASCADE
);

CREATE TABLE reviews (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED,
    room_id BIGINT UNSIGNED,
    rating INT CHECK (rating BETWEEN 1 AND 5),
    comment TEXT,
    reply TEXT,
    replied_at DATETIME,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (room_id) REFERENCES rooms(id) ON DELETE CASCADE
);

CREATE TABLE coupons (
    id INT AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(50) UNIQUE,
    discount_percent INT,
    expired_at DATE,
    is_active BOOLEAN DEFAULT TRUE
);

CREATE TABLE site_contents (
    id INT AUTO_INCREMENT PRIMARY KEY,
    type ENUM('banner','policy','about','footer'),
    title VARCHAR(255),
    content TEXT,
    image_url VARCHAR(255),
    is_active BOOLEAN DEFAULT TRUE,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX idx_room_price ON rooms(base_price);
CREATE INDEX idx_booking_user ON bookings(user_id);
CREATE INDEX idx_booking_room ON bookings(room_id);
CREATE INDEX idx_booked_date ON room_booked_dates(booked_date);


