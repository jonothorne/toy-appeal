-- Verify Email Settings for PHP mail() function
-- Run this in phpMyAdmin to check your settings

SELECT
    setting_key,
    setting_value,
    CASE
        WHEN setting_key = 'email_method' AND setting_value = 'mail' THEN '✓ Correct'
        WHEN setting_key = 'smtp_from_email' AND setting_value LIKE '%@%' THEN '✓ Valid email'
        WHEN setting_key = 'smtp_from_name' AND setting_value != '' THEN '✓ Has name'
        WHEN setting_key IN ('smtp_host', 'smtp_port', 'smtp_username', 'smtp_password') THEN '(Not used with mail())'
        ELSE '⚠ Check this'
    END as status
FROM settings
WHERE setting_key LIKE '%mail%'
   OR setting_key LIKE '%smtp%'
   OR setting_key LIKE '%email%'
ORDER BY setting_key;

-- If you need to fix the email_method setting, run this:
-- INSERT INTO settings (setting_key, setting_value) VALUES ('email_method', 'mail') ON DUPLICATE KEY UPDATE setting_value = 'mail';

-- If you need to update the From email, run this:
-- UPDATE settings SET setting_value = 'office@alive.me.uk' WHERE setting_key = 'smtp_from_email';

-- If you need to update the From name, run this:
-- UPDATE settings SET setting_value = 'Alive Church Christmas Toy Appeal' WHERE setting_key = 'smtp_from_name';
