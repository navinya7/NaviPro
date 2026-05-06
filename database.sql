-- ============================================================
-- FlashRide — Complete Database Setup
-- ============================================================
-- HOW TO IMPORT:
--   Option A (Recommended):
--     1. Open phpMyAdmin
--     2. Click "New" in left sidebar to create database named: flashride_db
--     3. Select flashride_db, then click Import tab
--     4. Choose this file → click Go
--
--   Option B (Command line):
--     mysql -u root -p < database.sql
-- ============================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;
SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";

-- Create & select database
CREATE DATABASE IF NOT EXISTS `flashride_db`
  DEFAULT CHARACTER SET utf8mb4
  DEFAULT COLLATE utf8mb4_unicode_ci;

USE `flashride_db`;

-- ============================================================
-- DROP TABLES (clean slate if re-importing)
-- ============================================================
DROP TABLE IF EXISTS `sos_alerts`;
DROP TABLE IF EXISTS `ride_tracking`;
DROP TABLE IF EXISTS `rides`;
DROP TABLE IF EXISTS `wallet_transactions`;
DROP TABLE IF EXISTS `notifications`;
DROP TABLE IF EXISTS `promo_codes`;
DROP TABLE IF EXISTS `ride_categories`;
DROP TABLE IF EXISTS `drivers`;
DROP TABLE IF EXISTS `users`;
DROP TABLE IF EXISTS `admins`;

-- ============================================================
-- TABLE: users (riders)
-- ============================================================
CREATE TABLE `users` (
  `id`             INT            NOT NULL AUTO_INCREMENT,
  `full_name`      VARCHAR(100)   NOT NULL,
  `email`          VARCHAR(150)   NOT NULL,
  `phone`          VARCHAR(15)    NOT NULL,
  `password_hash`  VARCHAR(255)   NOT NULL,
  `profile_pic`    VARCHAR(255)   DEFAULT 'default_user.png',
  `wallet_balance` DECIMAL(10,2)  NOT NULL DEFAULT 0.00,
  `total_rides`    INT            NOT NULL DEFAULT 0,
  `rating`         DECIMAL(3,2)   NOT NULL DEFAULT 5.00,
  `is_verified`    TINYINT(1)     NOT NULL DEFAULT 0,
  `otp_code`       VARCHAR(6)     DEFAULT NULL,
  `otp_expiry`     DATETIME       DEFAULT NULL,
  `created_at`     DATETIME       NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `last_login`     DATETIME       DEFAULT NULL,
  `status`         ENUM('active','suspended','deleted') NOT NULL DEFAULT 'active',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_users_email` (`email`),
  UNIQUE KEY `uq_users_phone` (`phone`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLE: drivers
-- ============================================================
CREATE TABLE `drivers` (
  `id`             INT            NOT NULL AUTO_INCREMENT,
  `full_name`      VARCHAR(100)   NOT NULL,
  `email`          VARCHAR(150)   NOT NULL,
  `phone`          VARCHAR(15)    NOT NULL,
  `password_hash`  VARCHAR(255)   NOT NULL,
  `profile_pic`    VARCHAR(255)   DEFAULT 'default_driver.png',
  `vehicle_type`   ENUM('bike','auto','cab') NOT NULL,
  `vehicle_number` VARCHAR(20)    NOT NULL,
  `vehicle_model`  VARCHAR(100)   DEFAULT NULL,
  `license_number` VARCHAR(50)    NOT NULL,
  `wallet_balance` DECIMAL(10,2)  NOT NULL DEFAULT 0.00,
  `total_rides`    INT            NOT NULL DEFAULT 0,
  `rating`         DECIMAL(3,2)   NOT NULL DEFAULT 5.00,
  `is_verified`    TINYINT(1)     NOT NULL DEFAULT 0,
  `is_available`   TINYINT(1)     NOT NULL DEFAULT 1,
  `current_lat`    DECIMAL(10,7)  DEFAULT NULL,
  `current_lng`    DECIMAL(10,7)  DEFAULT NULL,
  `otp_code`       VARCHAR(6)     DEFAULT NULL,
  `otp_expiry`     DATETIME       DEFAULT NULL,
  `created_at`     DATETIME       NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `last_login`     DATETIME       DEFAULT NULL,
  `status`         ENUM('active','suspended','deleted') NOT NULL DEFAULT 'active',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_drivers_email` (`email`),
  UNIQUE KEY `uq_drivers_phone` (`phone`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLE: ride_categories
-- ============================================================
CREATE TABLE `ride_categories` (
  `id`               INT           NOT NULL AUTO_INCREMENT,
  `name`             VARCHAR(50)   NOT NULL,
  `type`             ENUM('bike','auto','cab') NOT NULL,
  `icon`             VARCHAR(100)  DEFAULT NULL,
  `base_fare`        DECIMAL(10,2) NOT NULL,
  `per_km_rate`      DECIMAL(10,2) NOT NULL,
  `per_min_rate`     DECIMAL(10,2) NOT NULL,
  `min_fare`         DECIMAL(10,2) NOT NULL,
  `surge_multiplier` DECIMAL(4,2)  NOT NULL DEFAULT 1.00,
  `max_passengers`   INT           NOT NULL DEFAULT 1,
  `description`      VARCHAR(255)  DEFAULT NULL,
  `is_active`        TINYINT(1)    NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLE: rides
-- ============================================================
CREATE TABLE `rides` (
  `id`               INT           NOT NULL AUTO_INCREMENT,
  `ride_code`        VARCHAR(20)   NOT NULL,
  `user_id`          INT           NOT NULL,
  `driver_id`        INT           DEFAULT NULL,
  `category_id`      INT           NOT NULL,
  `pickup_address`   TEXT          NOT NULL,
  `pickup_lat`       DECIMAL(10,7) NOT NULL,
  `pickup_lng`       DECIMAL(10,7) NOT NULL,
  `dropoff_address`  TEXT          NOT NULL,
  `dropoff_lat`      DECIMAL(10,7) NOT NULL,
  `dropoff_lng`      DECIMAL(10,7) NOT NULL,
  `distance_km`      DECIMAL(8,2)  NOT NULL DEFAULT 0.00,
  `duration_mins`    INT           NOT NULL DEFAULT 0,
  `estimated_fare`   DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `final_fare`       DECIMAL(10,2) DEFAULT NULL,
  `surge_multiplier` DECIMAL(4,2)  NOT NULL DEFAULT 1.00,
  `payment_method`   ENUM('cash','wallet','upi') NOT NULL DEFAULT 'cash',
  `payment_status`   ENUM('pending','paid','refunded') NOT NULL DEFAULT 'pending',
  `status`           ENUM('searching','accepted','arrived','started','completed','cancelled') NOT NULL DEFAULT 'searching',
  `cancel_reason`    TEXT          DEFAULT NULL,
  `cancelled_by`     ENUM('user','driver','admin') DEFAULT NULL,
  `otp_for_start`    VARCHAR(4)    DEFAULT NULL,
  `driver_rating`    INT           DEFAULT NULL,
  `user_rating`      INT           DEFAULT NULL,
  `driver_review`    TEXT          DEFAULT NULL,
  `user_review`      TEXT          DEFAULT NULL,
  `requested_at`     DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `accepted_at`      DATETIME      DEFAULT NULL,
  `arrived_at`       DATETIME      DEFAULT NULL,
  `started_at`       DATETIME      DEFAULT NULL,
  `completed_at`     DATETIME      DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_rides_code` (`ride_code`),
  KEY `idx_rides_user`   (`user_id`),
  KEY `idx_rides_driver` (`driver_id`),
  KEY `idx_rides_status` (`status`),
  CONSTRAINT `fk_rides_user`     FOREIGN KEY (`user_id`)     REFERENCES `users`(`id`),
  CONSTRAINT `fk_rides_driver`   FOREIGN KEY (`driver_id`)   REFERENCES `drivers`(`id`),
  CONSTRAINT `fk_rides_category` FOREIGN KEY (`category_id`) REFERENCES `ride_categories`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLE: ride_tracking
-- ============================================================
CREATE TABLE `ride_tracking` (
  `id`          INT           NOT NULL AUTO_INCREMENT,
  `ride_id`     INT           NOT NULL,
  `lat`         DECIMAL(10,7) NOT NULL,
  `lng`         DECIMAL(10,7) NOT NULL,
  `speed`       DECIMAL(6,2)  NOT NULL DEFAULT 0.00,
  `recorded_at` DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_tracking_ride` (`ride_id`),
  CONSTRAINT `fk_tracking_ride` FOREIGN KEY (`ride_id`) REFERENCES `rides`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLE: wallet_transactions
-- ============================================================
CREATE TABLE `wallet_transactions` (
  `id`            INT           NOT NULL AUTO_INCREMENT,
  `user_type`     ENUM('user','driver') NOT NULL,
  `user_id`       INT           NOT NULL,
  `ride_id`       INT           DEFAULT NULL,
  `type`          ENUM('credit','debit') NOT NULL,
  `amount`        DECIMAL(10,2) NOT NULL,
  `balance_after` DECIMAL(10,2) NOT NULL,
  `description`   VARCHAR(255)  DEFAULT NULL,
  `reference_id`  VARCHAR(100)  DEFAULT NULL,
  `created_at`    DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_wallet_user` (`user_type`, `user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLE: promo_codes
-- ============================================================
CREATE TABLE `promo_codes` (
  `id`              INT           NOT NULL AUTO_INCREMENT,
  `code`            VARCHAR(30)   NOT NULL,
  `discount_type`   ENUM('percentage','fixed') NOT NULL,
  `discount_value`  DECIMAL(10,2) NOT NULL,
  `min_ride_amount` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `max_discount`    DECIMAL(10,2) DEFAULT NULL,
  `usage_limit`     INT           DEFAULT NULL,
  `used_count`      INT           NOT NULL DEFAULT 0,
  `valid_from`      DATE          NOT NULL,
  `valid_until`     DATE          NOT NULL,
  `is_active`       TINYINT(1)    NOT NULL DEFAULT 1,
  `created_at`      DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_promo_code` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLE: sos_alerts
-- ============================================================
CREATE TABLE `sos_alerts` (
  `id`         INT           NOT NULL AUTO_INCREMENT,
  `ride_id`    INT           NOT NULL,
  `user_id`    INT           NOT NULL,
  `lat`        DECIMAL(10,7) DEFAULT NULL,
  `lng`        DECIMAL(10,7) DEFAULT NULL,
  `message`    TEXT          DEFAULT NULL,
  `status`     ENUM('active','resolved') NOT NULL DEFAULT 'active',
  `created_at` DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  CONSTRAINT `fk_sos_ride` FOREIGN KEY (`ride_id`) REFERENCES `rides`(`id`),
  CONSTRAINT `fk_sos_user` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLE: notifications
-- ============================================================
CREATE TABLE `notifications` (
  `id`             INT          NOT NULL AUTO_INCREMENT,
  `recipient_type` ENUM('user','driver') NOT NULL,
  `recipient_id`   INT          NOT NULL,
  `title`          VARCHAR(100) NOT NULL,
  `message`        TEXT         NOT NULL,
  `type`           VARCHAR(50)  NOT NULL DEFAULT 'general',
  `is_read`        TINYINT(1)   NOT NULL DEFAULT 0,
  `created_at`     DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_notif_recipient` (`recipient_type`, `recipient_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLE: admins
-- ============================================================
CREATE TABLE `admins` (
  `id`            INT          NOT NULL AUTO_INCREMENT,
  `username`      VARCHAR(50)  NOT NULL,
  `email`         VARCHAR(150) NOT NULL,
  `password_hash` VARCHAR(255) NOT NULL,
  `role`          ENUM('superadmin','admin','support') NOT NULL DEFAULT 'admin',
  `created_at`    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_admins_username` (`username`),
  UNIQUE KEY `uq_admins_email`    (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- SEED DATA
-- ============================================================

-- Ride categories
INSERT INTO `ride_categories` (`name`, `type`, `icon`, `base_fare`, `per_km_rate`, `per_min_rate`, `min_fare`, `max_passengers`, `description`) VALUES
('Flash Bike',    'bike', 'bike',    15.00,  8.00, 1.00,  25.00, 1, 'Fast & affordable bike rides'),
('Flash Auto',    'auto', 'auto',    25.00, 12.00, 1.50,  40.00, 3, 'Comfortable auto-rickshaw rides'),
('Flash Cab',     'cab',  'cab',     50.00, 18.00, 2.00,  80.00, 4, 'Premium cab rides with AC'),
('Flash Premium', 'cab',  'premium', 80.00, 25.00, 3.00, 120.00, 4, 'Luxury sedans for premium travel'),
('Flash Pool',    'cab',  'pool',    30.00, 10.00, 1.20,  50.00, 4, 'Shared rides to save more');

-- Promo codes
INSERT INTO `promo_codes` (`code`, `discount_type`, `discount_value`, `min_ride_amount`, `max_discount`, `usage_limit`, `valid_from`, `valid_until`) VALUES
('FLASH50',    'percentage', 50.00, 100.00, 75.00,  1000, CURDATE(), DATE_ADD(CURDATE(), INTERVAL 30 DAY)),
('WELCOME100', 'fixed',     100.00, 150.00, 100.00, 5000, CURDATE(), DATE_ADD(CURDATE(), INTERVAL 60 DAY)),
('RIDE20',     'percentage', 20.00,  50.00,  40.00, 2000, CURDATE(), DATE_ADD(CURDATE(), INTERVAL 15 DAY));

-- Admin account  (password = Admin@123)
INSERT INTO `admins` (`username`, `email`, `password_hash`, `role`) VALUES
('superadmin', 'admin@flashride.com',
 '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
 'superadmin');

-- Sample riders  (password = Test@123)
INSERT INTO `users` (`full_name`, `email`, `phone`, `password_hash`, `wallet_balance`, `total_rides`, `is_verified`) VALUES
('Arjun Sharma', 'arjun@example.com', '9876543210',
 '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
 250.00, 12, 1),
('Priya Patel',  'priya@example.com', '9876543211',
 '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
 500.00,  8, 1),
('Rahul Verma',  'rahul@example.com', '9876543212',
 '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
 100.00,  3, 1);

-- Sample drivers (password = Test@123)
INSERT INTO `drivers` (`full_name`, `email`, `phone`, `password_hash`, `vehicle_type`, `vehicle_number`, `vehicle_model`, `license_number`, `wallet_balance`, `total_rides`, `is_verified`, `is_available`, `current_lat`, `current_lng`) VALUES
('Suresh Kumar', 'suresh@example.com', '9123456780',
 '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
 'bike', 'MH12AB1234', 'Honda Activa',  'MH0120101234567', 1200.00, 45, 1, 1, 18.5204, 73.8567),
('Mahesh Singh', 'mahesh@example.com', '9123456781',
 '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
 'auto', 'MH12CD5678', 'Bajaj Auto',    'MH0120109876543', 2400.00, 89, 1, 1, 18.5304, 73.8467),
('Vikram Rao',   'vikram@example.com', '9123456782',
 '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
 'cab',  'MH12EF9012', 'Maruti Swift',  'MH0120101122334', 3500.00, 120, 1, 1, 18.5104, 73.8667);

SET FOREIGN_KEY_CHECKS = 1;

-- ============================================================
-- Done! Visit: http://localhost/flashride
-- Login:  arjun@example.com  / Test@123   (rider)
--         suresh@example.com / Test@123   (driver)
--         admin@flashride.com/ Admin@123  (admin)
-- ============================================================
