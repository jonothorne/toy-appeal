<?php
/**
 * Debug Status Change - See what happens when changing status
 * Add this code temporarily to view_referral.php to see debug output
 */

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/email.php';

// Get a referral ID to test with
$referralId = $_GET['id'] ?? null;

if (!$referralId) {
    echo "<h1>Debug Status Change</h1>\n";
    echo "<p>Usage: debug_status_change.php?id=REFERRAL_ID</p>\n";
    echo "<p>Pick a referral ID from your database and add it to the URL.</p>\n";
    exit;
}

echo "<h1>Debug Status Change for Referral #{$referralId}</h1>\n";
echo "<style>
    body { font-family: Arial; padding: 20px; }
    .good { color: green; font-weight: bold; }
    .bad { color: red; font-weight: bold; }
    pre { background: #f5f5f5; padding: 10px; border: 1px solid #ddd; }
    table { border-collapse: collapse; margin: 20px 0; }
    th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
    th { background: #f0f0f0; }
</style>\n";

// Get referral details
$referral = getReferralWithHousehold($referralId);

if (!$referral) {
    echo "<p class='bad'>Referral not found!</p>\n";
    exit;
}

echo "<h2>Current Referral Details</h2>\n";
echo "<table>\n";
echo "<tr><th>Field</th><th>Value</th></tr>\n";
echo "<tr><td>Reference Number</td><td>{$referral['reference_number']}</td></tr>\n";
echo "<tr><td>Current Status</td><td><strong>{$referral['status']}</strong></td></tr>\n";
echo "<tr><td>Household ID</td><td>{$referral['household_id']}</td></tr>\n";
echo "<tr><td>Referrer Name</td><td>{$referral['referrer_name']}</td></tr>\n";
echo "<tr><td>Referrer Email</td><td>{$referral['referrer_email']}</td></tr>\n";
echo "</table>\n";

// Get siblings
$siblings = getSiblings($referral['household_id']);

echo "<h2>All Siblings in Household #{$referral['household_id']}</h2>\n";
echo "<table>\n";
echo "<tr><th>Ref Number</th><th>Status</th><th>Ready?</th></tr>\n";
foreach ($siblings as $sib) {
    $isReady = in_array($sib['status'], ['ready_for_collection', 'collected']);
    $readyText = $isReady ? "<span class='good'>✓ Ready</span>" : "<span class='bad'>✗ Not ready</span>";
    echo "<tr><td>{$sib['reference_number']}</td><td>{$sib['status']}</td><td>{$readyText}</td></tr>\n";
}
echo "</table>\n";

// Check if all ready
$allReady = checkIfAllHouseholdChildrenReady($referral['household_id']);

echo "<h2>All Siblings Ready Check</h2>\n";
if ($allReady) {
    echo "<p class='good'>✓ ALL siblings are ready for collection</p>\n";
    echo "<p>Email SHOULD be sent when you change status to 'ready_for_collection'</p>\n";
} else {
    echo "<p class='bad'>✗ NOT all siblings are ready yet</p>\n";
    echo "<p>Email will NOT be sent until all siblings are ready</p>\n";
}

echo "<hr>\n";

echo "<h2>Simulate Status Change</h2>\n";
echo "<p>This will simulate changing the status to 'ready_for_collection'</p>\n";

if (isset($_GET['simulate']) && $_GET['simulate'] === 'yes') {
    echo "<h3>Running Simulation...</h3>\n";

    // Enable detailed logging
    ini_set('display_errors', 1);
    error_reporting(E_ALL);

    echo "<p>Calling updateReferralStatus()...</p>\n";

    // Temporarily modify to capture what happens
    $oldStatus = $referral['status'];

    $result = updateReferralStatus($referralId, 'ready_for_collection', 1);

    if ($result) {
        echo "<p class='good'>✓ updateReferralStatus() returned TRUE</p>\n";

        // Check if email was queued
        echo "<p>Checking logs for email queue...</p>\n";

        $errorLog = ini_get('error_log');
        if (file_exists($errorLog)) {
            $logs = file($errorLog);
            $recentLogs = array_slice($logs, -10);
            echo "<h4>Recent Log Entries:</h4>\n";
            echo "<pre>" . htmlspecialchars(implode("", $recentLogs)) . "</pre>\n";
        }

        // Revert status
        updateReferralStatus($referralId, $oldStatus, 1);
        echo "<p>Status reverted back to: {$oldStatus}</p>\n";

    } else {
        echo "<p class='bad'>✗ updateReferralStatus() returned FALSE</p>\n";
    }
} else {
    echo "<p><a href='?id={$referralId}&simulate=yes' style='display: inline-block; padding: 10px 20px; background: #2563eb; color: white; text-decoration: none; border-radius: 5px;'>Click to Simulate Status Change</a></p>\n";
    echo "<p><em>This is safe - it will revert the status back immediately after testing</em></p>\n";
}

echo "<hr>\n";

echo "<h2>Manual Email Test</h2>\n";

if (isset($_GET['send_email']) && $_GET['send_email'] === 'yes') {
    echo "<h3>Sending Collection Ready Email...</h3>\n";

    $result = sendCollectionReadyEmail($referralId);

    if ($result) {
        echo "<p class='good'>✓ sendCollectionReadyEmail() returned TRUE</p>\n";
        echo "<p>Check {$referral['referrer_email']} for the email</p>\n";
    } else {
        echo "<p class='bad'>✗ sendCollectionReadyEmail() returned FALSE</p>\n";
    }
} else {
    echo "<p><a href='?id={$referralId}&send_email=yes' style='display: inline-block; padding: 10px 20px; background: #059669; color: white; text-decoration: none; border-radius: 5px;'>Send Collection Ready Email Now</a></p>\n";
}
