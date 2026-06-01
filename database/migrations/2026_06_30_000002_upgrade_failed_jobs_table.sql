-- ارتقای جدول failed_jobs برای پشتیبانی از سیستم هوشمند Poison Message Handler
ALTER TABLE failed_jobs
ADD COLUMN `error_classification` ENUM('transient', 'permanent', 'business', 'unknown') NOT NULL DEFAULT 'unknown' AFTER `exception`,
ADD COLUMN `status` ENUM('pending_analysis', 'retrying', 'quarantined', 'dead_letter', 'resolved') NOT NULL DEFAULT 'pending_analysis' AFTER `error_classification`,
ADD COLUMN `retry_count` INT NOT NULL DEFAULT 0 AFTER `status`,
ADD COLUMN `next_retry_at` DATETIME NULL AFTER `retry_count`,
ADD COLUMN `quarantine_reason` VARCHAR(255) NULL AFTER `next_retry_at`;

-- اضافه کردن ایندکس برای جستجو و کرون‌جاب سریع‌تر
CREATE INDEX idx_failed_jobs_status_retry ON failed_jobs (`status`, `next_retry_at`);
