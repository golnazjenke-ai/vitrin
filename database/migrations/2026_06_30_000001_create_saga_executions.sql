CREATE TABLE IF NOT EXISTS `saga_executions` (
  `id` CHAR(36) NOT NULL PRIMARY KEY,
  `saga_name` VARCHAR(100) NOT NULL,
  `status` ENUM('started', 'completed', 'compensated', 'failed_compensation') NOT NULL DEFAULT 'started',
  `payload` JSON NOT NULL,
  `executed_steps` JSON NOT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX `idx_saga_status_updated_at` (`status`, `updated_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
