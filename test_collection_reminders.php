<?php
/**
 * Test Collection Reminders
 * Manual testing tool for collection reminder system
 */

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/email.php';

echo "<h1>Test Collection Reminders</h1>\n";
echo "<style>
    body { font-family: Arial; padding: 20px; }
    .good { color: green; font-weight: bold; }
    .bad { color: red; font-weight: bold; }
    .warning { color: orange; font-weight: bold; }
    pre { background: #f5f5f5; padding: 10px; border: 1px solid #ddd; }
    table { border-collapse: collapse; margin: 20px 0; }
    th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
    th { background: #f0f0f0; }
</style>\n";

// Check if reminders are enabled
$remindersEnabled = getSetting('collection_reminders_enabled', '1');
$reminderDays = intval(getSetting('collection_reminder_days', '3'));

echo "<h2>Settings Check</h2>\n";
echo "<table>\n";
echo "<tr><th>Setting</th><th>Value</th><th>Status</th></tr>\n";
echo "<tr><td>Collection Reminders Enabled</td><td>" . ($remindersEnabled == '1' ? 'Yes' : 'No') . "</td><td>" . ($remindersEnabled == '1' ? "<span class='good'>✓ Enabled</span>" : "<span class='bad'>✗ Disabled</span>") . "</td></tr>\n";
echo "<tr><td>Send Reminder After (Days)</td><td>{$reminderDays}</td><td><span class='good'>✓</span></td></tr>\n";
echo "</table>\n";

if ($remindersEnabled !== '1') {
    echo "<p class='bad'>⚠ Collection reminders are disabled. Enable them in Settings to use this feature.</p>\n";
    exit;
}

// Find parcels that need reminders
$sql = "
    SELECT
        r.id,
        r.reference_number,
        r.household_id,
        r.child_initials,
        r.created_at,
        r.collection_reminder_sent_at,
        h.referrer_name,
        h.referrer_email,
        h.referrer_organisation,
        DATEDIFF(NOW(), r.created_at) as days_waiting
    FROM referrals r
    JOIN households h ON r.household_id = h.id
    WHERE r.status = 'ready_for_collection'
    AND r.collection_reminder_sent_at IS NULL
    AND r.created_at <= DATE_SUB(NOW(), INTERVAL ? DAY)
    ORDER BY r.created_at ASC
";

$parcelsNeedingReminders = getRows($sql, "i", [$reminderDays]);

echo "<hr>\n";
echo "<h2>Parcels Needing Reminders</h2>\n";
echo "<p>Checking for parcels ready for collection for {$reminderDays}+ days without a reminder sent...</p>\n";

if (empty($parcelsNeedingReminders)) {
    echo "<p class='good'>✓ No parcels currently need reminders.</p>\n";

    // Show parcels that are close to needing reminders
    $upcomingSql = "
        SELECT
            r.reference_number,
            r.child_initials,
            h.referrer_name,
            h.referrer_organisation,
            DATEDIFF(NOW(), r.created_at) as days_waiting,
            ({$reminderDays} - DATEDIFF(NOW(), r.created_at)) as days_until_reminder
        FROM referrals r
        JOIN households h ON r.household_id = h.id
        WHERE r.status = 'ready_for_collection'
        AND r.collection_reminder_sent_at IS NULL
        AND r.created_at > DATE_SUB(NOW(), INTERVAL ? DAY)
        ORDER BY r.created_at ASC
        LIMIT 10
    ";

    $upcomingReminders = getRows($upcomingSql, "i", [$reminderDays]);

    if (!empty($upcomingReminders)) {
        echo "<h3>Upcoming Reminders (within next few days)</h3>\n";
        echo "<table>\n";
        echo "<tr><th>Reference</th><th>Referrer</th><th>Organisation</th><th>Days Waiting</th><th>Days Until Reminder</th></tr>\n";
        foreach ($upcomingReminders as $parcel) {
            echo "<tr>";
            echo "<td>{$parcel['reference_number']}</td>";
            echo "<td>{$parcel['referrer_name']}</td>";
            echo "<td>{$parcel['referrer_organisation']}</td>";
            echo "<td>{$parcel['days_waiting']}</td>";
            echo "<td><span class='warning'>{$parcel['days_until_reminder']} days</span></td>";
            echo "</tr>\n";
        }
        echo "</table>\n";
    }
} else {
    echo "<p><strong class='warning'>Found " . count($parcelsNeedingReminders) . " parcel(s) needing reminders</strong></p>\n";

    // Group by household
    $households = [];
    foreach ($parcelsNeedingReminders as $parcel) {
        if (!isset($households[$parcel['household_id']])) {
            $households[$parcel['household_id']] = [
                'referrer_name' => $parcel['referrer_name'],
                'referrer_email' => $parcel['referrer_email'],
                'referrer_organisation' => $parcel['referrer_organisation'],
                'parcels' => []
            ];
        }
        $households[$parcel['household_id']]['parcels'][] = $parcel;
    }

    echo "<table>\n";
    echo "<tr><th>Household</th><th>Email</th><th>Organisation</th><th>Parcels</th><th>Days Waiting</th></tr>\n";
    foreach ($households as $householdId => $household) {
        $parcelCount = count($household['parcels']);
        $oldestDays = max(array_column($household['parcels'], 'days_waiting'));

        echo "<tr>";
        echo "<td>{$household['referrer_name']}</td>";
        echo "<td>{$household['referrer_email']}</td>";
        echo "<td>{$household['referrer_organisation']}</td>";
        echo "<td>{$parcelCount}</td>";
        echo "<td><span class='bad'>{$oldestDays} days</span></td>";
        echo "</tr>\n";
    }
    echo "</table>\n";

    echo "<hr>\n";
    echo "<h2>Test Send Reminder</h2>\n";

    if (isset($_GET['send_test']) && $_GET['send_test'] === 'yes') {
        echo "<h3>Sending Test Reminders...</h3>\n";

        $successCount = 0;
        $failCount = 0;

        foreach ($households as $householdId => $household) {
            // Get first parcel ID for this household
            $firstParcelId = $household['parcels'][0]['id'];

            echo "<p>Sending reminder to {$household['referrer_name']} ({$household['referrer_email']})...</p>\n";

            $result = sendCollectionReminderEmail($firstParcelId);

            if ($result) {
                echo "<p class='good'>✓ Reminder sent successfully!</p>\n";
                $successCount++;
            } else {
                echo "<p class='bad'>✗ Failed to send reminder</p>\n";
                $failCount++;
            }
        }

        echo "<hr>\n";
        echo "<h3>Summary</h3>\n";
        echo "<p>Total: " . count($households) . " household(s)</p>\n";
        echo "<p class='good'>Success: {$successCount}</p>\n";
        if ($failCount > 0) {
            echo "<p class='bad'>Failed: {$failCount}</p>\n";
        }
    } else {
        echo "<p>Click the button below to send test reminders to all households listed above:</p>\n";
        echo "<p><a href='?send_test=yes' style='display: inline-block; padding: 10px 20px; background: #F59E0B; color: white; text-decoration: none; border-radius: 5px; font-weight: bold;'>Send Test Reminders Now</a></p>\n";
        echo "<p><em>This will send actual emails to the referrers listed above.</em></p>\n";
    }
}

echo "<hr>\n";
echo "<h2>Parcels Already Sent Reminders</h2>\n";

$alreadySentSql = "
    SELECT
        r.reference_number,
        r.child_initials,
        h.referrer_name,
        h.referrer_organisation,
        r.collection_reminder_sent_at,
        DATEDIFF(NOW(), r.collection_reminder_sent_at) as days_since_reminder
    FROM referrals r
    JOIN households h ON r.household_id = h.id
    WHERE r.status = 'ready_for_collection'
    AND r.collection_reminder_sent_at IS NOT NULL
    ORDER BY r.collection_reminder_sent_at DESC
    LIMIT 10
";

$alreadySent = getRows($alreadySentSql);

if (empty($alreadySent)) {
    echo "<p>No reminders have been sent yet.</p>\n";
} else {
    echo "<table>\n";
    echo "<tr><th>Reference</th><th>Referrer</th><th>Organisation</th><th>Reminder Sent</th><th>Days Since</th></tr>\n";
    foreach ($alreadySent as $parcel) {
        echo "<tr>";
        echo "<td>{$parcel['reference_number']}</td>";
        echo "<td>{$parcel['referrer_name']}</td>";
        echo "<td>{$parcel['referrer_organisation']}</td>";
        echo "<td>" . date('d M Y H:i', strtotime($parcel['collection_reminder_sent_at'])) . "</td>";
        echo "<td>{$parcel['days_since_reminder']} days ago</td>";
        echo "</tr>\n";
    }
    echo "</table>\n";
}

echo "<hr>\n";
echo "<h2>Cron Job Setup</h2>\n";
echo "<p>To automate collection reminders, add this cron job to your server:</p>\n";
echo "<pre>0 9 * * * cd " . __DIR__ . " && php cron_send_collection_reminders.php >> logs/collection_reminders.log 2>&1</pre>\n";
echo "<p>This will check for reminders daily at 9:00 AM.</p>\n";
echo "<p>You can also run the cron script manually from command line:</p>\n";
echo "<pre>php " . __DIR__ . "/cron_send_collection_reminders.php</pre>\n";
