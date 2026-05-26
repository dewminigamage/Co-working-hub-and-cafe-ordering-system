-- ================================================================
--  Co-Working Hub & Café Ordering System  –  Database Schema
--  Run: mysql -u root -p < database/schema.sql
-- ================================================================

CREATE DATABASE IF NOT EXISTS `coworking_hub`
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE `coworking_hub`;

-- ----------------------------------------------------------------
--  USERS
-- ----------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `users` (
    `id`         INT UNSIGNED     AUTO_INCREMENT PRIMARY KEY,
    `name`       VARCHAR(100)     NOT NULL,
    `email`      VARCHAR(150)     NOT NULL UNIQUE,
    `password`   VARCHAR(255)     NOT NULL,
    `created_at` TIMESTAMP        DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP        DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------
--  BOOKINGS
-- ----------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `bookings` (
    `id`           INT UNSIGNED  AUTO_INCREMENT PRIMARY KEY,
    `user_id`      INT UNSIGNED  NOT NULL,
    `space_name`   VARCHAR(100)  NOT NULL,
    `booking_date` DATE          NOT NULL,
    `start_time`   TIME          NULL,
    `end_time`     TIME          NULL,
    `notes`        TEXT          NULL,
    `created_at`   TIMESTAMP     DEFAULT CURRENT_TIMESTAMP,
    `updated_at`   TIMESTAMP     DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    INDEX `idx_booking_date` (`booking_date`),
    INDEX `idx_user_id`      (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------
--  CAFÉ ITEMS
-- ----------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `cafe_items` (
    `id`           INT UNSIGNED   AUTO_INCREMENT PRIMARY KEY,
    `name`         VARCHAR(100)   NOT NULL,
    `description`  TEXT           NULL,
    `price`        DECIMAL(10,2)  NOT NULL,
    `image`        VARCHAR(500)   NULL,
    `category`     ENUM('coffee','tea','snacks','meals','drinks') DEFAULT 'snacks',
    `is_available` TINYINT(1)     DEFAULT 1,
    `created_at`   TIMESTAMP      DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_category`  (`category`),
    INDEX `idx_available` (`is_available`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------
--  CART
-- ----------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `cart` (
    `id`         INT UNSIGNED  AUTO_INCREMENT PRIMARY KEY,
    `user_id`    INT UNSIGNED  NOT NULL,
    `item_id`    INT UNSIGNED  NOT NULL,
    `quantity`   INT UNSIGNED  NOT NULL DEFAULT 1,
    `created_at` TIMESTAMP     DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP     DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY `unique_user_item` (`user_id`, `item_id`),
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`)       ON DELETE CASCADE,
    FOREIGN KEY (`item_id`) REFERENCES `cafe_items`(`id`)  ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------
--  ORDERS  (bonus – checkout receipt)
-- ----------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `orders` (
    `id`           INT UNSIGNED   AUTO_INCREMENT PRIMARY KEY,
    `user_id`      INT UNSIGNED   NOT NULL,
    `order_number` VARCHAR(20)    NOT NULL UNIQUE,
    `total_amount` DECIMAL(10,2)  NOT NULL,
    `status`       ENUM('pending','confirmed','preparing','ready','completed','cancelled') DEFAULT 'confirmed',
    `notes`        TEXT           NULL,
    `created_at`   TIMESTAMP      DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------
--  ORDER ITEMS
-- ----------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `order_items` (
    `id`         INT UNSIGNED   AUTO_INCREMENT PRIMARY KEY,
    `order_id`   INT UNSIGNED   NOT NULL,
    `item_id`    INT UNSIGNED   NULL,
    `item_name`  VARCHAR(100)   NOT NULL,
    `quantity`   INT UNSIGNED   NOT NULL,
    `unit_price` DECIMAL(10,2)  NOT NULL,
    `subtotal`   DECIMAL(10,2)  NOT NULL,
    FOREIGN KEY (`order_id`) REFERENCES `orders`(`id`)      ON DELETE CASCADE,
    FOREIGN KEY (`item_id`)  REFERENCES `cafe_items`(`id`)  ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------
--  SAMPLE CAFÉ MENU  (12 items)
-- ----------------------------------------------------------------
INSERT INTO `cafe_items` (`name`, `description`, `price`, `category`, `image`) VALUES
('Espresso',
 'Rich and bold single-shot espresso with a velvety crema.',
 2.50, 'coffee',
 'https://images.unsplash.com/photo-1514432324607-a09d9b4aefdd?w=600&q=80'),

('Cappuccino',
 'Espresso topped with perfectly steamed milk foam and a dusting of cocoa.',
 4.00, 'coffee',
 'https://images.unsplash.com/photo-1534778101976-62847782c213?w=600&q=80'),

('Iced Caramel Latte',
 'Chilled espresso with cold milk, caramel syrup, and ice.',
 4.80, 'coffee',
 'https://images.unsplash.com/photo-1461023058943-07fcbe16d735?w=600&q=80'),

('Flat White',
 'Double-shot espresso with velvety microfoamed whole milk.',
 4.50, 'coffee',
 'https://images.unsplash.com/photo-1572442388796-11668a67e53d?w=600&q=80'),

('Matcha Green Tea',
 'Ceremonial-grade Japanese matcha whisked to perfection.',
 3.50, 'tea',
 'https://images.unsplash.com/photo-1556679343-c7306c1976bc?w=600&q=80'),

('Butter Croissant',
 'Golden, flaky French pastry baked fresh every morning.',
 3.00, 'snacks',
 'https://images.unsplash.com/photo-1555507036-ab1f4038808a?w=600&q=80'),

('Avocado Toast',
 'Artisan sourdough topped with smashed avocado, chili flakes, and microgreens.',
 8.50, 'meals',
 'https://images.unsplash.com/photo-1588137378633-dea1336ce1e2?w=600&q=80'),

('Blueberry Muffin',
 'Freshly baked muffin bursting with plump blueberries.',
 3.00, 'snacks',
 'https://images.unsplash.com/photo-1607958996333-41aef7caefaa?w=600&q=80'),

('Fresh Lemonade',
 'Hand-squeezed lemonade with fresh mint and a hint of ginger.',
 3.50, 'drinks',
 'https://images.unsplash.com/photo-1541167760496-1628856ab772?w=600&q=80'),

('Club Sandwich',
 'Triple-decker toasted sandwich with grilled chicken, bacon, and fresh vegetables.',
 9.00, 'meals',
 'https://images.unsplash.com/photo-1567234669003-dce7a7a88821?w=600&q=80'),

('Granola & Yoghurt',
 'Creamy Greek yoghurt topped with house-made granola and seasonal berries.',
 5.50, 'snacks',
 'https://images.unsplash.com/photo-1504674900247-0877df9cc836?w=600&q=80'),

('Sparkling Water',
 'Chilled San Pellegrino sparkling mineral water (500 ml).',
 2.00, 'drinks',
 'https://images.unsplash.com/photo-1558618666-fcd25c85cd64?w=600&q=80');
