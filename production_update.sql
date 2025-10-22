-- Production Database Update Script
-- Run this in phpMyAdmin on toyappeal_production database
-- Safe to run multiple times

-- 1. Add GDPR consent columns to households table
ALTER TABLE households
ADD COLUMN IF NOT EXISTS gdpr_consent TINYINT(1) NOT NULL DEFAULT 0 AFTER additional_notes,
ADD COLUMN IF NOT EXISTS gdpr_consent_date DATETIME NULL AFTER gdpr_consent;

-- 2. Create deletions_log table for GDPR compliance
CREATE TABLE IF NOT EXISTS deletions_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    deleted_referral_id INT NOT NULL COMMENT 'Original referral ID before deletion',
    reference_number VARCHAR(50) NOT NULL COMMENT 'Referral reference number (e.g., TOY-2025-0001)',
    child_initials VARCHAR(10) DEFAULT NULL COMMENT 'Child initials from deleted referral',
    referrer_name VARCHAR(255) DEFAULT NULL COMMENT 'Who made the original referral',
    referrer_organisation VARCHAR(255) DEFAULT NULL COMMENT 'Organisation that made referral',
    deleted_by INT COMMENT 'User ID who deleted the referral',
    deleted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT 'When the deletion occurred',
    reason TEXT COMMENT 'Reason for deletion provided by user',
    household_id INT COMMENT 'Original household ID',
    household_deleted TINYINT(1) DEFAULT 0 COMMENT 'Was the household also deleted?',

    INDEX idx_deleted_at (deleted_at),
    INDEX idx_deleted_by (deleted_by),
    INDEX idx_reference_number (reference_number),

    FOREIGN KEY (deleted_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Permanent audit log of deleted referrals for GDPR compliance';

-- 3. Add/update email_method setting to use mail() function
INSERT INTO settings (setting_key, setting_value)
VALUES ('email_method', 'mail')
ON DUPLICATE KEY UPDATE setting_value = 'mail';
