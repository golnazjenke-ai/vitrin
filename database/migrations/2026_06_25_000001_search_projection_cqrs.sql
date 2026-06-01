-- =========================================================================
-- Migration: Search Projection CQRS Read-Model (C1 fix)
-- Description:
--   تکمیل و سخت‌سازی جدول search_projections به‌عنوان Read-Model یکپارچه‌ی جستجو.
--   ستون‌های owner/scope/module اضافه می‌شود تا ownership کاربر و فیلتر ماژول
--   مستقیماً از روی projection پاسخ داده شوند (بدون JOIN به جداول live).
--   همچنین FULLTEXT لازم برای جداول کاربری که فقط LIKE داشتند اضافه می‌شود
--   تا مسیر fallback (dual-read) نیز از Full Table Scan خارج شود.
--
--   این migration کاملاً idempotent است (IF NOT EXISTS / افزودن ستون امن).
-- =========================================================================

-- 1) جدول projection (اگر از migration قبلی وجود دارد، بازسازی نمی‌شود)
CREATE TABLE IF NOT EXISTS `search_projections` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `entity_type` VARCHAR(100) NOT NULL,
    `entity_id` BIGINT UNSIGNED NOT NULL,
    `title` TEXT NULL,
    `content` LONGTEXT NULL,
    `metadata` JSON NULL,
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_search_projection_entity` (`entity_type`, `entity_id`),
    KEY `idx_search_projection_active` (`entity_type`, `is_active`, `updated_at`),
    FULLTEXT KEY `ft_search_projection_text` (`title`, `content`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2) ستون owner_id (مالک رکورد برای ownership کاربر در User Search)
--    از روال safe-add استفاده می‌کنیم تا اجرای مجدد خطا ندهد.
SET @col_owner := (
    SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'search_projections'
      AND COLUMN_NAME = 'owner_id'
);
SET @sql_owner := IF(@col_owner = 0,
    'ALTER TABLE `search_projections` ADD COLUMN `owner_id` BIGINT UNSIGNED NULL AFTER `entity_id`',
    'SELECT 1');
PREPARE st FROM @sql_owner; EXECUTE st; DEALLOCATE PREPARE st;

-- 3) ستون scope (admin/user/module — تفکیک دامنه‌ی دید)
SET @col_scope := (
    SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'search_projections'
      AND COLUMN_NAME = 'scope'
);
SET @sql_scope := IF(@col_scope = 0,
    'ALTER TABLE `search_projections` ADD COLUMN `scope` VARCHAR(20) NOT NULL DEFAULT ''module'' AFTER `owner_id`',
    'SELECT 1');
PREPARE st FROM @sql_scope; EXECUTE st; DEALLOCATE PREPARE st;

-- 4) ستون module (نام ماژول منطقی: transactions, tickets, vitrines, ...)
SET @col_module := (
    SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'search_projections'
      AND COLUMN_NAME = 'module'
);
SET @sql_module := IF(@col_module = 0,
    'ALTER TABLE `search_projections` ADD COLUMN `module` VARCHAR(64) NULL AFTER `scope`',
    'SELECT 1');
PREPARE st FROM @sql_module; EXECUTE st; DEALLOCATE PREPARE st;

-- 5) ستون ref (شناسه‌ی نمایشی کوتاه مثل tracking_code/transaction_id برای جستجوی دقیق)
SET @col_ref := (
    SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'search_projections'
      AND COLUMN_NAME = 'ref'
);
SET @sql_ref := IF(@col_ref = 0,
    'ALTER TABLE `search_projections` ADD COLUMN `ref` VARCHAR(190) NULL AFTER `module`',
    'SELECT 1');
PREPARE st FROM @sql_ref; EXECUTE st; DEALLOCATE PREPARE st;

-- 6) ایندکس‌های دسترسی سریع برای مسیرهای ownership/module/scope
SET @idx_owner := (
    SELECT COUNT(*) FROM information_schema.STATISTICS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'search_projections'
      AND INDEX_NAME = 'idx_sp_owner_scope'
);
SET @sql_idx_owner := IF(@idx_owner = 0,
    'ALTER TABLE `search_projections` ADD INDEX `idx_sp_owner_scope` (`owner_id`, `scope`, `is_active`, `updated_at`)',
    'SELECT 1');
PREPARE st FROM @sql_idx_owner; EXECUTE st; DEALLOCATE PREPARE st;

SET @idx_module := (
    SELECT COUNT(*) FROM information_schema.STATISTICS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'search_projections'
      AND INDEX_NAME = 'idx_sp_module_scope'
);
SET @sql_idx_module := IF(@idx_module = 0,
    'ALTER TABLE `search_projections` ADD INDEX `idx_sp_module_scope` (`module`, `scope`, `is_active`, `updated_at`)',
    'SELECT 1');
PREPARE st FROM @sql_idx_module; EXECUTE st; DEALLOCATE PREPARE st;

SET @idx_ref := (
    SELECT COUNT(*) FROM information_schema.STATISTICS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'search_projections'
      AND INDEX_NAME = 'idx_sp_ref'
);
SET @sql_idx_ref := IF(@idx_ref = 0,
    'ALTER TABLE `search_projections` ADD INDEX `idx_sp_ref` (`ref`)',
    'SELECT 1');
PREPARE st FROM @sql_idx_ref; EXECUTE st; DEALLOCATE PREPARE st;

-- 7) FULLTEXT برای مسیر fallback (جداولی که قبلاً فقط LIKE داشتند)
--    وجود ایندکس قبل از افزودن بررسی می‌شود تا idempotent بماند.

-- vitrine_listings
SET @ft_vl := (
    SELECT COUNT(*) FROM information_schema.STATISTICS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'vitrine_listings'
      AND INDEX_NAME = 'ft_vitrine_listings_search'
);
SET @tbl_vl := (
    SELECT COUNT(*) FROM information_schema.TABLES
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'vitrine_listings'
);
SET @sql_ft_vl := IF(@ft_vl = 0 AND @tbl_vl = 1,
    'ALTER TABLE `vitrine_listings` ADD FULLTEXT INDEX `ft_vitrine_listings_search` (`title`, `description`, `username`)',
    'SELECT 1');
PREPARE st FROM @sql_ft_vl; EXECUTE st; DEALLOCATE PREPARE st;

-- direct_messages
SET @ft_dm := (
    SELECT COUNT(*) FROM information_schema.STATISTICS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'direct_messages'
      AND INDEX_NAME = 'ft_direct_messages_search'
);
SET @tbl_dm := (
    SELECT COUNT(*) FROM information_schema.TABLES
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'direct_messages'
);
SET @sql_ft_dm := IF(@ft_dm = 0 AND @tbl_dm = 1,
    'ALTER TABLE `direct_messages` ADD FULLTEXT INDEX `ft_direct_messages_search` (`message`)',
    'SELECT 1');
PREPARE st FROM @sql_ft_dm; EXECUTE st; DEALLOCATE PREPARE st;
