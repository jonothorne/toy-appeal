-- Add Collection Reminder Feature
-- Safe to run multiple times

-- 1. Add column to track when collection reminder was sent
ALTER TABLE referrals
ADD COLUMN IF NOT EXISTS collection_reminder_sent_at DATETIME NULL
COMMENT 'Timestamp when collection reminder email was sent'
AFTER collection_date;

-- 2. Add settings for collection reminders
INSERT INTO settings (setting_key, setting_value)
VALUES ('collection_reminders_enabled', '1')
ON DUPLICATE KEY UPDATE setting_key = setting_key;

INSERT INTO settings (setting_key, setting_value)
VALUES ('collection_reminder_days', '3')
ON DUPLICATE KEY UPDATE setting_key = setting_key;

-- 3. Add index for efficient queries
ALTER TABLE referrals
ADD INDEX IF NOT EXISTS idx_reminder_check (status, collection_reminder_sent_at, created_at);
