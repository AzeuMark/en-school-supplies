-- E&N School Supplies — Full schema
-- Database: azeu_en_school_supplies
-- Charset:  utf8mb4
--
-- Run via setup.php or import directly in phpMyAdmin.

CREATE DATABASE IF NOT EXISTS `azeu_en_school_supplies`
  DEFAULT CHARACTER SET utf8mb4
  DEFAULT COLLATE utf8mb4_unicode_ci;

USE `azeu_en_school_supplies`;

-- ---------- users ----------
CREATE TABLE IF NOT EXISTS `users` (
  `id`               INT AUTO_INCREMENT PRIMARY KEY,
  `full_name`        VARCHAR(150) NOT NULL,
  `email`            VARCHAR(150) NOT NULL UNIQUE,
  `phone`            VARCHAR(20)  NOT NULL,
  `password`         TEXT         NOT NULL,
  `role`             ENUM('admin','staff','customer') NOT NULL,
  `status`           ENUM('active','pending','flagged') NOT NULL DEFAULT 'active',
  `flag_reason`      TEXT NULL,
  `profile_image`    VARCHAR(255) NULL,
  `theme_preference` ENUM('light','dark','auto') NOT NULL DEFAULT 'auto',
  `created_by`       INT NULL,
  `created_at`       TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`       TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  KEY `idx_users_role` (`role`),
  KEY `idx_users_status` (`status`),
  CONSTRAINT `fk_users_created_by` FOREIGN KEY (`created_by`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------- item_categories ----------
CREATE TABLE IF NOT EXISTS `item_categories` (
  `id`            INT AUTO_INCREMENT PRIMARY KEY,
  `category_name` VARCHAR(100) NOT NULL UNIQUE,
  `created_at`    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------- default_item_names ----------
CREATE TABLE IF NOT EXISTS `default_item_names` (
  `id`        INT AUTO_INCREMENT PRIMARY KEY,
  `item_name` VARCHAR(150) NOT NULL UNIQUE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------- inventory ----------
CREATE TABLE IF NOT EXISTS `inventory` (
  `id`             INT AUTO_INCREMENT PRIMARY KEY,
  `item_name`      VARCHAR(150) NOT NULL,
  `category_id`    INT NULL,
  `price`          DECIMAL(10,2) NOT NULL DEFAULT 0,
  `stock_count`    INT NOT NULL DEFAULT 0,
  `max_order_qty`  INT NOT NULL DEFAULT 10,
  `item_image`     VARCHAR(255) NULL,
  `created_at`     TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`     TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  KEY `idx_inventory_category` (`category_id`),
  KEY `idx_inventory_name` (`item_name`),
  CONSTRAINT `fk_inventory_category` FOREIGN KEY (`category_id`) REFERENCES `item_categories`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------- orders ----------
CREATE TABLE IF NOT EXISTS `orders` (
  `id`           INT AUTO_INCREMENT PRIMARY KEY,
  `order_code`   VARCHAR(20) NOT NULL UNIQUE,
  `user_id`      INT NULL,
  `guest_name`   VARCHAR(150) NULL,
  `guest_phone`  VARCHAR(20)  NULL,
  `guest_note`   TEXT NULL,
  `status`       ENUM('pending','ready','claimed','cancelled') NOT NULL DEFAULT 'pending',
  `total_price`  DECIMAL(10,2) NOT NULL DEFAULT 0,
  `claim_pin`    CHAR(4) NOT NULL,
  `processed_by` INT NULL,
  `created_at`   TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`   TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  KEY `idx_orders_user_created` (`user_id`, `created_at`),
  KEY `idx_orders_status` (`status`),
  KEY `idx_orders_order_code` (`order_code`),
  CONSTRAINT `fk_orders_user` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_orders_processed_by` FOREIGN KEY (`processed_by`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------- order_items ----------
CREATE TABLE IF NOT EXISTS `order_items` (
  `id`                 INT AUTO_INCREMENT PRIMARY KEY,
  `order_id`           INT NOT NULL,
  `item_id`            INT NOT NULL,
  `item_name_snapshot` VARCHAR(150) NOT NULL,
  `quantity`           INT NOT NULL,
  `unit_price`         DECIMAL(10,2) NOT NULL,
  KEY `idx_order_items_order` (`order_id`),
  KEY `idx_order_items_item` (`item_id`),
  CONSTRAINT `fk_order_items_order` FOREIGN KEY (`order_id`) REFERENCES `orders`(`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_order_items_item`  FOREIGN KEY (`item_id`)  REFERENCES `inventory`(`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------- staff_sessions ----------
CREATE TABLE IF NOT EXISTS `staff_sessions` (
  `id`               INT AUTO_INCREMENT PRIMARY KEY,
  `user_id`          INT NOT NULL,
  `login_time`       TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `logout_time`      TIMESTAMP NULL,
  `logout_type`      ENUM('manual','auto_system') NULL,
  `duration_minutes` INT NULL,
  `is_suspicious`    TINYINT(1) NOT NULL DEFAULT 0,
  KEY `idx_sessions_user` (`user_id`),
  CONSTRAINT `fk_sessions_user` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------- system_settings ----------
CREATE TABLE IF NOT EXISTS `system_settings` (
  `setting_key`   VARCHAR(100) PRIMARY KEY,
  `setting_value` TEXT NOT NULL,
  `updated_at`    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------- system_logs ----------
CREATE TABLE IF NOT EXISTS `system_logs` (
  `id`         INT AUTO_INCREMENT PRIMARY KEY,
  `level`      ENUM('info','warning','error') NOT NULL,
  `message`    TEXT NOT NULL,
  `context`    JSON NULL,
  `user_id`    INT NULL,
  `ip_address` VARCHAR(45) NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY `idx_logs_level` (`level`),
  KEY `idx_logs_created` (`created_at`),
  CONSTRAINT `fk_logs_user` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------- login_attempts (rate limit) ----------
CREATE TABLE IF NOT EXISTS `login_attempts` (
  `id`         INT AUTO_INCREMENT PRIMARY KEY,
  `email`      VARCHAR(150) NOT NULL,
  `ip_address` VARCHAR(45)  NOT NULL,
  `success`    TINYINT(1)   NOT NULL DEFAULT 0,
  `attempted_at` TIMESTAMP  NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY `idx_attempts_email` (`email`),
  KEY `idx_attempts_ip` (`ip_address`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
