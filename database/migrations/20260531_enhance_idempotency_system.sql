-- =============================================================================
-- Migration: enhance_idempotency_system
-- Purpose : Enhance Core\IdempotencyKey with improved status handling,
--           retry semantics, and TTL management.
--
-- Key Improvements:
-- 1. Status values: PENDING | COMPLETED | FAILED_RETRYABLE | FAILED_FINAL
-- 2. UNIQUE constraint on (key, user_id) to prevent race conditions
-- 3. retry_count tracking for automatic retry logic
-- 4. TTL/retention policy for cleanup
-- 5. Audit trail with timestamps and metadata
-- =============================================================================

-- ALTER existing table to add new columns and update enum
ALTER TABLE `idempotency_keys` 
MODIFY COLUMN `status` ENUM('pending','completed','failed_retryable','failed_final','processing','failed') 
  NOT NULL DEFAULT 'pending' 
  COMMENT 'PENDING: operation in progress | COMPLETED: success | FAILED_RETRYABLE: transient failure | FAILED_FINAL: unrecoverable failure',
ADD COLUMN IF NOT EXISTS `retry_count` TINYINT UNSIGNED DEFAULT 0 COMMENT 'Number of retry attempts',
ADD COLUMN IF NOT EXISTS `last_retry_at` DATETIME NULL COMMENT 'Timestamp of last retry attempt',
ADD COLUMN IF NOT EXISTS `error_type` VARCHAR(50) NULL COMMENT 'Classification of error (transient, business, network, etc)',
ADD COLUMN IF NOT EXISTS `http_status` SMALLINT UNSIGNED NULL COMMENT 'HTTP status code for external API failures',
ADD COLUMN IF NOT EXISTS `metadata` JSON NULL COMMENT 'Additional context: retry_after, elapsed_seconds, resource_ids, etc';

-- Ensure UNIQUE constraint exists (preventing race condition when two requests use same key)
ALTER TABLE `idempotency_keys` 
DROP INDEX IF EXISTS `uq_idem_key_user`,
ADD UNIQUE INDEX `uq_idem_key_user` (`key`, `user_id`) COMMENT 'Composite unique key ensures one operation per key per user';

-- Add index for cleanup queries
ALTER TABLE `idempotency_keys` 
ADD INDEX IF NOT EXISTS `ix_idem_expires_at` (`expires_at`) COMMENT 'For efficient cleanup of expired records',
ADD INDEX IF NOT EXISTS `ix_idem_user_status` (`user_id`, `status`) COMMENT 'For finding pending/failed operations by user',
ADD INDEX IF NOT EXISTS `ix_idem_action_created` (`action`, `created_at`) COMMENT 'For audit and statistics by action type';

-- Update indexes for performance
ALTER TABLE `idempotency_keys` 
DROP INDEX IF EXISTS `ix_idem_status_expiry`,
ADD INDEX IF NOT EXISTS `ix_idem_status_expiry` (`status`, `expires_at`) COMMENT 'For cleanup queries filtering by status and TTL';

-- Create retention policy comment (informational)
-- TTL Policy (set in application via CLEANUP_DAYS constant in Core\IdempotencyKey):
--   Financial Operations: 90+ days (for audit and compliance)
--   General Operations: 7-30 days
--   High-volume Operations: 3-7 days
-- Cleanup runs via scheduled job: App\Commands\IdempotencyCleanupCommand

-- Create audit columns if needed (optional, for even better tracking)
ALTER TABLE `idempotency_keys` 
ADD COLUMN IF NOT EXISTS `request_uri` VARCHAR(2048) NULL COMMENT 'Request URI for cross-endpoint collision detection',
ADD COLUMN IF NOT EXISTS `request_method` VARCHAR(10) NULL COMMENT 'HTTP method (GET, POST, etc)',
ADD COLUMN IF NOT EXISTS `request_ip` VARCHAR(45) NULL COMMENT 'Requester IP address for audit trail';

-- Verify schema integrity
SELECT 
  COLUMN_NAME,
  COLUMN_TYPE,
  IS_NULLABLE,
  COLUMN_DEFAULT,
  COLUMN_COMMENT
FROM INFORMATION_SCHEMA.COLUMNS
WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'idempotency_keys'
ORDER BY ORDINAL_POSITION;
