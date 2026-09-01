CREATE DATABASE IF NOT EXISTS `website_monitor` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `website_monitor`;

CREATE TABLE IF NOT EXISTS `admins` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `username` VARCHAR(50) NOT NULL UNIQUE,
  `password` VARCHAR(255) NOT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `websites` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(100) NOT NULL,
  `url` VARCHAR(255) NOT NULL,
  `monitoring_interval` INT DEFAULT 5,
  `slow_threshold` INT DEFAULT 3000,
  `enabled` TINYINT(1) DEFAULT 1,
  `current_status` ENUM('UP', 'DOWN', 'SLOW', 'PENDING') DEFAULT 'PENDING',
  `last_checked` DATETIME DEFAULT NULL,
  `response_time` INT DEFAULT 0,
  `http_status_code` INT DEFAULT 0,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX (`enabled`),
  INDEX (`current_status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `monitoring_logs` (
  `id` BIGINT AUTO_INCREMENT PRIMARY KEY,
  `website_id` INT NOT NULL,
  `status` ENUM('UP', 'DOWN', 'SLOW') NOT NULL,
  `response_time` INT DEFAULT 0,
  `http_status_code` INT DEFAULT 0,
  `error_message` TEXT DEFAULT NULL,
  `checked_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`website_id`) REFERENCES `websites`(`id`) ON DELETE CASCADE,
  INDEX (`website_id`),
  INDEX (`checked_at`),
  INDEX (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `incidents` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `website_id` INT NOT NULL,
  `previous_status` VARCHAR(20) NOT NULL,
  `current_status` VARCHAR(20) NOT NULL,
  `response_time` INT DEFAULT 0,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `resolved_at` DATETIME DEFAULT NULL,
  FOREIGN KEY (`website_id`) REFERENCES `websites`(`id`) ON DELETE CASCADE,
  INDEX (`website_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `telegram_config` (
  `id` INT PRIMARY KEY DEFAULT 1,
  `bot_token` VARCHAR(255) DEFAULT '',
  `chat_id` VARCHAR(100) DEFAULT '',
  `enabled` TINYINT(1) DEFAULT 0,
  `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 初始插入设置
INSERT IGNORE INTO `telegram_config` (`id`, `bot_token`, `chat_id`, `enabled`) VALUES (1, '', '', 0);

-- 默认管理员账号 (账号: admin, 密码: admin123)
INSERT INTO `admins` (`username`, `password`) 
SELECT 'admin','$2y$10$dYvdAYxLsn/VGEX5t7Zh0uZhN83KsxtkLhepteWDwKlL8Rol6AlFu'
WHERE NOT EXISTS (SELECT 1 FROM `admins` WHERE `username` = 'admin');