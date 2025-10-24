#!/usr/bin/php
<?php
/**
 * Collection Reminder Cron Job
 *
 * Checks for parcels ready for collection that haven't been collected
 * and sends reminder emails based on configured settings.
 *
 * Usage:
 * - Set up as a daily cron job: 0 9 * * * /path/to/cron_send_collection_reminders.php
 * - Or run manually: php cron_send_collection_reminders.php
 *
 * The script will:
 * 1. Check if collection reminders are enabled in settings
 * 2. Find parcels ready for collection for X days (configured in settings)
 * 3. Group by household to send one email per family
 * 4. Send reminder emails with QR codes
 * 5. Mark reminders as sent to avoid duplicates
 */

// Only allow command line execution
if (php_sapi_name() !== 'cli') {
    die("This script can only be run from the command line.\n");
}

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/email.php';

// Enable error logging
ini_set('display_errors', 0);
ini_set('log_errors', 1);
error_reporting(E_ALL);

echo "=== Collection Reminder Cron Job Started ===\n";
echo "Time: " . date('Y-m-d H:i:s') . "\n\n";

// Check if collection reminders are enabled
$remindersEnabled = getSetting('collection_reminders_enabled', '1');
if ($remindersEnabled !== '1') {
    echo "Collection reminders are disabled in settings. Exiting.\n";
    exit(0);
}

// Get number of days before sending reminder
$reminderDays = intval(getSetting('collection_reminder_days', '3'));
echo "Reminder settings: Send after {$reminderDays} days\n\n";

// Find parcels ready for collection that need reminders
// Group by household to send one email per family
$sql = "
    SELECT
        r.id,
        r.reference_number,
        r.household_id,
        r.created_at,
        h.referrer_name,
        h.referrer_email,
        h.referrer_organisation,
        MIN(r.created_at) as oldest_parcel_date,
        COUNT(*) as parcel_count
    FROM referrals r
    JOIN households h ON r.household_id = h.id
    WHERE r.status = 'ready_for_collection'
    AND r.collection_reminder_sent_at IS NULL
    AND r.created_at <= DATE_SUB(NOW(), INTERVAL ? DAY)
    GROUP BY r.household_id
    ORDER BY oldest_parcel_date ASC
";

$householdsNeedingReminders = getRows($sql, "i", [$reminderDays]);

if (empty($householdsNeedingReminders)) {
    echo "No parcels need reminders at this time.\n";
    echo "=== Cron Job Completed ===\n";
    exit(0);
}

echo "Found " . count($householdsNeedingReminders) . " household(s) with parcels needing reminders:\n\n";

$successCount = 0;
$failCount = 0;

foreach ($householdsNeedingReminders as $household) {
    $daysWaiting = floor((time() - strtotime($household['oldest_parcel_date'])) / (60 * 60 * 24));

    echo "Processing: {$household['referrer_organisation']} - {$household['referrer_name']}\n";
    echo "  Email: {$household['referrer_email']}\n";
    echo "  Parcels waiting: {$household['parcel_count']}\n";
    echo "  Days waiting: {$daysWaiting}\n";

    // Send reminder email (using the first referral ID from the household)
    $result = sendCollectionReminderEmail($household['id']);

    if ($result) {
        echo "  ✓ Reminder sent successfully\n\n";
        $successCount++;

        // Log success
        error_log("Collection reminder sent to {$household['referrer_email']} for {$household['parcel_count']} parcel(s)");
    } else {
        echo "  ✗ Failed to send reminder\n\n";
        $failCount++;

        // Log failure
        error_log("Failed to send collection reminder to {$household['referrer_email']}");
    }

    // Add a small delay to avoid overwhelming the email service
    usleep(500000); // 0.5 seconds
}

echo "\n=== Summary ===\n";
echo "Total households processed: " . count($householdsNeedingReminders) . "\n";
echo "Reminders sent successfully: {$successCount}\n";
echo "Failed to send: {$failCount}\n";
echo "\n=== Cron Job Completed ===\n";
echo "End time: " . date('Y-m-d H:i:s') . "\n";

// Exit with appropriate status code
exit($failCount > 0 ? 1 : 0);
