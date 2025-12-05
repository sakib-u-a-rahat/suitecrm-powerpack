-- =====================================================
-- Twilio Integration v2.4.0 - Safe Upgrade Migration
-- =====================================================
-- This script safely upgrades from any previous version to v2.4.0
-- Safe to run multiple times (idempotent)
-- Preserves ALL existing data
-- =====================================================

-- Audit Log Table (NEW in v2.4.0)
-- Creates only if it doesn't exist (preserves existing data)
CREATE TABLE IF NOT EXISTS `twilio_audit_log` (
    `id` CHAR(36) NOT NULL PRIMARY KEY,
    `action` VARCHAR(50) NOT NULL,
    `data` TEXT,
    `user_id` CHAR(36),
    `date_created` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_action` (`action`),
    INDEX `idx_user_id` (`user_id`),
    INDEX `idx_date_created` (`date_created`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Twilio Integration Audit Log';

-- Call Metrics View (safe to recreate)
DROP VIEW IF EXISTS `twilio_call_metrics`;
CREATE VIEW `twilio_call_metrics` AS
SELECT
    c.id,
    c.name,
    c.status,
    c.direction,
    c.date_start,
    c.duration_hours,
    c.duration_minutes,
    c.parent_type,
    c.parent_id,
    c.assigned_user_id,
    c.description,
    CONCAT(u.first_name, ' ', u.last_name) as assigned_user_name,
    CASE
        WHEN c.parent_type = 'Leads' THEN l.first_name
        WHEN c.parent_type = 'Contacts' THEN co.first_name
        ELSE NULL
    END as contact_first_name,
    CASE
        WHEN c.parent_type = 'Leads' THEN l.last_name
        WHEN c.parent_type = 'Contacts' THEN co.last_name
        ELSE NULL
    END as contact_last_name,
    CASE
        WHEN c.description LIKE '%Twilio Call SID:%' THEN 1
        ELSE 0
    END as is_twilio_call,
    CASE
        WHEN c.status = 'Held' THEN 1
        ELSE 0
    END as is_connected,
    (c.duration_hours * 3600 + c.duration_minutes * 60) as duration_seconds
FROM calls c
LEFT JOIN users u ON c.assigned_user_id = u.id
LEFT JOIN leads l ON c.parent_type = 'Leads' AND c.parent_id = l.id
LEFT JOIN contacts co ON c.parent_type = 'Contacts' AND c.parent_id = co.id
WHERE c.deleted = 0;

-- SMS Metrics View (safe to recreate)
DROP VIEW IF EXISTS `twilio_sms_metrics`;
CREATE VIEW `twilio_sms_metrics` AS
SELECT
    n.id,
    n.name,
    n.description,
    n.date_entered,
    n.parent_type,
    n.parent_id,
    n.assigned_user_id,
    CONCAT(u.first_name, ' ', u.last_name) as assigned_user_name,
    CASE
        WHEN n.parent_type = 'Leads' THEN l.first_name
        WHEN n.parent_type = 'Contacts' THEN co.first_name
        ELSE NULL
    END as contact_first_name,
    CASE
        WHEN n.parent_type = 'Leads' THEN l.last_name
        WHEN n.parent_type = 'Contacts' THEN co.last_name
        ELSE NULL
    END as contact_last_name,
    CASE
        WHEN n.name LIKE '%📤%' OR n.name LIKE 'SMS to%' THEN 'outbound'
        WHEN n.name LIKE '%📥%' OR n.name LIKE 'SMS from%' THEN 'inbound'
        ELSE 'unknown'
    END as direction,
    CASE
        WHEN n.description LIKE '%Twilio Message SID:%' THEN 1
        ELSE 0
    END as is_twilio_sms,
    CASE
        WHEN n.description LIKE '%Status: Delivered%' OR n.description LIKE '%✅%' THEN 'delivered'
        WHEN n.description LIKE '%Status: Failed%' OR n.description LIKE '%❌%' THEN 'failed'
        ELSE 'sent'
    END as delivery_status
FROM notes n
LEFT JOIN users u ON n.assigned_user_id = u.id
LEFT JOIN leads l ON n.parent_type = 'Leads' AND n.parent_id = l.id
LEFT JOIN contacts co ON n.parent_type = 'Contacts' AND n.parent_id = co.id
WHERE n.deleted = 0
AND (n.name LIKE '%SMS%' OR n.description LIKE '%Twilio Message SID%');

-- Add indexes for performance (only if they don't exist)
-- MySQL 5.7+ syntax with IF NOT EXISTS
-- For older MySQL, these will fail silently if index exists

-- Leads phone indexes
SET @exist_idx_phone_mobile_leads := (
    SELECT COUNT(*) FROM information_schema.statistics
    WHERE table_schema = DATABASE()
    AND table_name = 'leads'
    AND index_name = 'idx_phone_mobile'
);
SET @sql_idx_phone_mobile_leads := IF(
    @exist_idx_phone_mobile_leads = 0,
    'CREATE INDEX idx_phone_mobile ON leads(phone_mobile)',
    'SELECT "Index idx_phone_mobile already exists on leads"'
);
PREPARE stmt FROM @sql_idx_phone_mobile_leads;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @exist_idx_phone_work_leads := (
    SELECT COUNT(*) FROM information_schema.statistics
    WHERE table_schema = DATABASE()
    AND table_name = 'leads'
    AND index_name = 'idx_phone_work'
);
SET @sql_idx_phone_work_leads := IF(
    @exist_idx_phone_work_leads = 0,
    'CREATE INDEX idx_phone_work ON leads(phone_work)',
    'SELECT "Index idx_phone_work already exists on leads"'
);
PREPARE stmt FROM @sql_idx_phone_work_leads;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @exist_idx_phone_home_leads := (
    SELECT COUNT(*) FROM information_schema.statistics
    WHERE table_schema = DATABASE()
    AND table_name = 'leads'
    AND index_name = 'idx_phone_home'
);
SET @sql_idx_phone_home_leads := IF(
    @exist_idx_phone_home_leads = 0,
    'CREATE INDEX idx_phone_home ON leads(phone_home)',
    'SELECT "Index idx_phone_home already exists on leads"'
);
PREPARE stmt FROM @sql_idx_phone_home_leads;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Contacts phone indexes
SET @exist_idx_phone_mobile_contacts := (
    SELECT COUNT(*) FROM information_schema.statistics
    WHERE table_schema = DATABASE()
    AND table_name = 'contacts'
    AND index_name = 'idx_phone_mobile'
);
SET @sql_idx_phone_mobile_contacts := IF(
    @exist_idx_phone_mobile_contacts = 0,
    'CREATE INDEX idx_phone_mobile ON contacts(phone_mobile)',
    'SELECT "Index idx_phone_mobile already exists on contacts"'
);
PREPARE stmt FROM @sql_idx_phone_mobile_contacts;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @exist_idx_phone_work_contacts := (
    SELECT COUNT(*) FROM information_schema.statistics
    WHERE table_schema = DATABASE()
    AND table_name = 'contacts'
    AND index_name = 'idx_phone_work'
);
SET @sql_idx_phone_work_contacts := IF(
    @exist_idx_phone_work_contacts = 0,
    'CREATE INDEX idx_phone_work ON contacts(phone_work)',
    'SELECT "Index idx_phone_work already exists on contacts"'
);
PREPARE stmt FROM @sql_idx_phone_work_contacts;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @exist_idx_phone_home_contacts := (
    SELECT COUNT(*) FROM information_schema.statistics
    WHERE table_schema = DATABASE()
    AND table_name = 'contacts'
    AND index_name = 'idx_phone_home'
);
SET @sql_idx_phone_home_contacts := IF(
    @exist_idx_phone_home_contacts = 0,
    'CREATE INDEX idx_phone_home ON contacts(phone_home)',
    'SELECT "Index idx_phone_home already exists on contacts"'
);
PREPARE stmt FROM @sql_idx_phone_home_contacts;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Calls table indexes for metrics performance
SET @exist_idx_calls_date_start := (
    SELECT COUNT(*) FROM information_schema.statistics
    WHERE table_schema = DATABASE()
    AND table_name = 'calls'
    AND index_name = 'idx_date_start_deleted'
);
SET @sql_idx_calls_date_start := IF(
    @exist_idx_calls_date_start = 0,
    'CREATE INDEX idx_date_start_deleted ON calls(date_start, deleted)',
    'SELECT "Index idx_date_start_deleted already exists on calls"'
);
PREPARE stmt FROM @sql_idx_calls_date_start;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @exist_idx_calls_direction := (
    SELECT COUNT(*) FROM information_schema.statistics
    WHERE table_schema = DATABASE()
    AND table_name = 'calls'
    AND index_name = 'idx_direction_deleted'
);
SET @sql_idx_calls_direction := IF(
    @exist_idx_calls_direction = 0,
    'CREATE INDEX idx_direction_deleted ON calls(direction, deleted)',
    'SELECT "Index idx_direction_deleted already exists on calls"'
);
PREPARE stmt FROM @sql_idx_calls_direction;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Notes table indexes for SMS metrics
SET @exist_idx_notes_date_entered := (
    SELECT COUNT(*) FROM information_schema.statistics
    WHERE table_schema = DATABASE()
    AND table_name = 'notes'
    AND index_name = 'idx_date_entered_deleted'
);
SET @sql_idx_notes_date_entered := IF(
    @exist_idx_notes_date_entered = 0,
    'CREATE INDEX idx_date_entered_deleted ON notes(date_entered, deleted)',
    'SELECT "Index idx_date_entered_deleted already exists on notes"'
);
PREPARE stmt FROM @sql_idx_notes_date_entered;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- =====================================================
-- Verification Queries (Optional - for manual check)
-- =====================================================
-- Uncomment to verify upgrade was successful:

-- SELECT 'Audit Log Table' as Check_Item, COUNT(*) as Record_Count FROM twilio_audit_log;
-- SELECT 'Call Metrics View' as Check_Item, COUNT(*) as Record_Count FROM twilio_call_metrics WHERE is_twilio_call = 1;
-- SELECT 'SMS Metrics View' as Check_Item, COUNT(*) as Record_Count FROM twilio_sms_metrics WHERE is_twilio_sms = 1;
-- SHOW INDEX FROM leads WHERE Key_name LIKE 'idx_phone%';
-- SHOW INDEX FROM contacts WHERE Key_name LIKE 'idx_phone%';
-- SHOW INDEX FROM calls WHERE Key_name LIKE 'idx_%';

-- =====================================================
-- Migration Complete
-- =====================================================
-- All existing data preserved
-- New tables and indexes created
-- Ready for v2.4.0 features
-- =====================================================

SELECT 'Twilio Integration v2.4.0 - Upgrade migration completed successfully!' as Status;
