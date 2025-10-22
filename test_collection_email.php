<?php
/**
 * Test Collection Ready Email
 * Tests if the collection ready email is sending correctly
 */

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/email.php';

echo "<h1>Test Collection Ready Email</h1>\n";
echo "<style>
    body { font-family: Arial; padding: 20px; }
    .good { color: green; font-weight: bold; }
    .bad { color: red; font-weight: bold; }
    pre { background: #f5f5f5; padding: 10px; border: 1px solid #ddd; }
</style>\n";

// Get a referral that's ready for collection
$referral = getRow(
    "SELECT r.id, r.reference_number, h.referrer_email
     FROM referrals r
     JOIN households h ON r.household_id = h.id
     WHERE r.status = 'ready_for_collection'
     LIMIT 1"
);

if (!$referral) {
    echo "<p class='bad'>No referrals found with status 'ready_for_collection'</p>\n";
    echo "<p>Please change a referral's status to 'Ready for Collection' first.</p>\n";
    exit;
}

echo "<h2>Testing with Referral: {$referral['reference_number']}</h2>\n";
echo "<p>Will send test email to: <strong>{$referral['referrer_email']}</strong></p>\n";
echo "<hr>\n";

// Test 1: Direct call to sendCollectionReadyEmail
echo "<h3>Test 1: Direct Call to sendCollectionReadyEmail()</h3>\n";
$result1 = sendCollectionReadyEmail($referral['id']);

if ($result1) {
    echo "<p class='good'>✓ sendCollectionReadyEmail() returned TRUE</p>\n";
} else {
    echo "<p class='bad'>✗ sendCollectionReadyEmail() returned FALSE</p>\n";
}

echo "<hr>\n";

// Test 2: Via queueEmailInBackground (synchronous mode)
echo "<h3>Test 2: Via queueEmailInBackground() - Synchronous Mode</h3>\n";
echo "<p>Temporarily disabling background emails...</p>\n";

// Temporarily disable background emails to test synchronous mode
updateQuery(
    "UPDATE settings SET setting_value = '0' WHERE setting_key = 'background_emails'",
    "",
    []
);

$result2 = queueEmailInBackground('collection_ready', [
    'referral_id' => $referral['id']
]);

if ($result2) {
    echo "<p class='good'>✓ queueEmailInBackground() synchronous returned TRUE</p>\n";
} else {
    echo "<p class='bad'>✗ queueEmailInBackground() synchronous returned FALSE</p>\n";
}

// Re-enable background emails
updateQuery(
    "UPDATE settings SET setting_value = '1' WHERE setting_key = 'background_emails'",
    "",
    []
);

echo "<p>Re-enabled background emails</p>\n";

echo "<hr>\n";

// Test 3: Via queueEmailInBackground (background mode)
echo "<h3>Test 3: Via queueEmailInBackground() - Background Mode</h3>\n";

$result3 = queueEmailInBackground('collection_ready', [
    'referral_id' => $referral['id']
]);

if ($result3) {
    echo "<p class='good'>✓ queueEmailInBackground() background returned TRUE</p>\n";
    echo "<p>Email should send in 1-2 seconds in background</p>\n";
} else {
    echo "<p class='bad'>✗ queueEmailInBackground() background returned FALSE</p>\n";
}

echo "<hr>\n";

echo "<h2>Summary</h2>\n";
echo "<p>Check <strong>{$referral['referrer_email']}</strong> for the test emails.</p>\n";
echo "<p>You should have received up to 3 emails (if all tests passed).</p>\n";
echo "<p><strong>IMPORTANT:</strong> Check that the emails say \"Parcel Ready for Collection!\" in the subject/header.</p>\n";
echo "<p>If you're receiving \"Referral Received\" emails instead, there's a bug in the email routing.</p>\n";

echo "<hr>\n";

echo "<h2>Check Recent Logs</h2>\n";
echo "<p>Checking error log for email-related messages...</p>\n";

$errorLogPath = ini_get('error_log');
if (!empty($errorLogPath) && file_exists($errorLogPath)) {
    $logLines = @file($errorLogPath);
    if ($logLines) {
        $relevantLines = [];
        foreach (array_reverse($logLines) as $line) {
            if (stripos($line, 'email') !== false || stripos($line, 'collection') !== false) {
                $relevantLines[] = $line;
                if (count($relevantLines) >= 10) break;
            }
        }

        if (!empty($relevantLines)) {
            echo "<pre>" . htmlspecialchars(implode("", array_reverse($relevantLines))) . "</pre>\n";
        } else {
            echo "<p>No email-related log entries found.</p>\n";
        }
    }
}
