-- ===================================================================================
-- Migration: Add Enterprise Composite Indexes and FULLTEXT
-- Created At: 2026-06-30
-- Description: Solves N+1 scanning in Reporting and missing unique constraints.
-- ===================================================================================

-- 1. Score Events: Ensure no duplicate score events are processed (Idempotency)
ALTER TABLE `score_events`
    ADD UNIQUE INDEX `idx_score_events_unique_source` (`entity_type`, `entity_id`, `domain`, `source`, `created_at`);

-- 2. Interactions: Prevent multiple likes/ratings from same user on same entity
ALTER TABLE `interactions`
    ADD UNIQUE INDEX `idx_interactions_unique_user_entity` (`user_id`, `interactable_type`, `interactable_id`, `interaction_type`);

-- 3. Influencer Reputation Events: Optimize profile-based reporting scans
ALTER TABLE `influencer_reputation_events`
    ADD INDEX `idx_influencer_rep_profile_date` (`profile_id`, `created_at`);

-- 4. Event Failures (DLQ): Optimize queries looking for failed events
ALTER TABLE `event_failures`
    ADD INDEX `idx_event_failures_name_date` (`event_name`, `failed_at`);

-- 5. Search Projections: Ensure FULLTEXT index exists for BOOLEAN MODE queries
-- Note: Assuming search_projections is InnoDB 5.6+ or Aria/MyISAM
ALTER TABLE `search_projections`
    ADD FULLTEXT INDEX `ft_search_projections_title_content` (`title`, `content`);
