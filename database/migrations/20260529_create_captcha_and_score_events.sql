-- Migration: create captcha_attempts and score_events tables
-- Run: mysql -h 127.0.0.1 -P 3306 -u root -p chortk < 20260529_create_captcha_and_score_events.sql

CREATE TABLE IF NOT EXISTS `captcha_attempts` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT NULL,
  `session_id` VARCHAR(128) NULL,
  `type` VARCHAR(50) NOT NULL,
  `challenge` TEXT NULL,
  `response` TEXT NULL,
  `ip_address` VARCHAR(45) NULL,
  `user_agent` VARCHAR(255) NULL,
  `is_success` TINYINT(1) NOT NULL DEFAULT 0,
  `score` DOUBLE NULL,
  `solved_at` DATETIME NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX (`user_id`),
  INDEX (`ip_address`),
  INDEX (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `score_events` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `entity_type` VARCHAR(50) NOT NULL,
  `entity_id` INT NOT NULL,
  `domain` VARCHAR(50) NOT NULL,
  `delta` DOUBLE NOT NULL,
  `source` VARCHAR(128) NULL,
  `meta_json` JSON NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_entity` (`entity_type`, `entity_id`),
  INDEX `idx_domain` (`domain`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
