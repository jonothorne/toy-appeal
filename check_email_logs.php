<?php
/**
 * Check Email Logs and Recent Referrals
 * This helps diagnose why emails aren't sending
 */

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';

echo "<h1>Email & Referral Diagnostics</h1>\n";
echo "<style>
    body { font-family: Arial; padding: 20px; }
    table { border-collapse: collapse; width: 100%; margin: 20px 0; }
    th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
    th { background: #f0f0f0; }
    .good { color: green; font-weight: bold; }
    .bad { color: red; font-weight: bold; }
    pre { background: #f5f5f5; padding: 10px; border: 1px solid #ddd; overflow-x: auto; }
</style>\n";

// Check 1: Email settings
echo "<h2>1. Email Configuration</h2>\n";
$emailMethod = getSetting('email_method', 'NOT SET');
$fromEmail = getSetting('smtp_from_email', 'NOT SET');
$fromName = getSetting('smtp_from_name', 'NOT SET');

echo "<table>\n";
echo "<tr><th>Setting</th><th>Value</th><th>Status</th></tr>\n";
echo "<tr><td>email_method</td><td>{$emailMethod}</td><td>";
if ($emailMethod === 'mail') {
    echo "<span class='good'>✓ Correct</span>";
} else {
    echo "<span class='bad'>✗ Should be 'mail'</span>";
}
echo "</td></tr>\n";
echo "<tr><td>smtp_from_email</td><td>{$fromEmail}</td><td>";
if ($fromEmail !== 'NOT SET') {
    echo "<span class='good'>✓ Set</span>";
} else {
    echo "<span class='bad'>✗ Missing</span>";
}
echo "</td></tr>\n";
echo "<tr><td>smtp_from_name</td><td>{$fromName}</td><td>";
if ($fromName !== 'NOT SET') {
    echo "<span class='good'>✓ Set</span>";
} else {
    echo "<span class='bad'>✗ Missing</span>";
}
echo "</td></tr>\n";
echo "</table>\n";

// Check 2: Recent referrals
echo "<h2>2. Recent Referrals (Last 5)</h2>\n";
$recentReferrals = getRows(
    "SELECT r.id, r.reference_number, r.created_at, h.referrer_name, h.referrer_email, h.referrer_organisation
     FROM referrals r
     JOIN households h ON r.household_id = h.id
     ORDER BY r.created_at DESC
     LIMIT 5"
);

if ($recentReferrals) {
    echo "<table>\n";
    echo "<tr><th>ID</th><th>Reference</th><th>Referrer Email</th><th>Organisation</th><th>Created</th></tr>\n";
    foreach ($recentReferrals as $ref) {
        echo "<tr>";
        echo "<td>{$ref['id']}</td>";
        echo "<td>{$ref['reference_number']}</td>";
        echo "<td>{$ref['referrer_email']}</td>";
        echo "<td>{$ref['referrer_organisation']}</td>";
        echo "<td>{$ref['created_at']}</td>";
        echo "</tr>\n";
    }
    echo "</table>\n";
} else {
    echo "<p>No referrals found in database.</p>\n";
}

// Check 3: Test if sendEmail function exists
echo "<h2>3. Check Email Functions</h2>\n";
require_once __DIR__ . '/includes/email.php';

echo "<table>\n";
echo "<tr><th>Function</th><th>Status</th></tr>\n";
echo "<tr><td>sendEmail()</td><td>" . (function_exists('sendEmail') ? "<span class='good'>✓ Exists</span>" : "<span class='bad'>✗ Missing</span>") . "</td></tr>\n";
echo "<tr><td>sendReferralConfirmation()</td><td>" . (function_exists('sendReferralConfirmation') ? "<span class='good'>✓ Exists</span>" : "<span class='bad'>✗ Missing</span>") . "</td></tr>\n";
echo "<tr><td>sendEmailViaMailFunction()</td><td>" . (function_exists('sendEmailViaMailFunction') ? "<span class='good'>✓ Exists</span>" : "<span class='bad'>✗ Missing</span>") . "</td></tr>\n";
echo "</table>\n";

// Check 4: PHP error log
echo "<h2>4. Recent PHP Error Log</h2>\n";
echo "<p>Looking for email-related errors...</p>\n";

$errorLogPath = ini_get('error_log');
if (!empty($errorLogPath) && file_exists($errorLogPath)) {
    $logLines = @file($errorLogPath);
    if ($logLines) {
        $relevantLines = [];
        foreach (array_reverse($logLines) as $line) {
            if (stripos($line, 'email') !== false || stripos($line, 'mail') !== false || stripos($line, 'smtp') !== false) {
                $relevantLines[] = $line;
                if (count($relevantLines) >= 20) break;
            }
        }

        if (!empty($relevantLines)) {
            echo "<pre>" . htmlspecialchars(implode("", array_reverse($relevantLines))) . "</pre>\n";
        } else {
            echo "<p class='good'>No email-related errors in error log.</p>\n";
        }
    } else {
        echo "<p>Could not read error log.</p>\n";
    }
} else {
    echo "<p>Error log not found at: " . htmlspecialchars($errorLogPath) . "</p>\n";
}

// Check 5: Test email sending NOW
echo "<h2>5. Live Email Test</h2>\n";

if (!empty($recentReferrals)) {
    $latestRef = $recentReferrals[0];
    echo "<p>Testing email to: <strong>{$latestRef['referrer_email']}</strong></p>\n";

    $testResult = sendReferralConfirmation(
        $latestRef['referrer_email'],
        $latestRef['referrer_name'],
        1,
        $latestRef['id']
    );

    if ($testResult) {
        echo "<p class='good'>✓ sendReferralConfirmation() returned TRUE</p>\n";
        echo "<p>Check {$latestRef['referrer_email']} for the email (including spam folder)</p>\n";
    } else {
        echo "<p class='bad'>✗ sendReferralConfirmation() returned FALSE</p>\n";
        echo "<p>This means the email function failed. Check error log above for details.</p>\n";
    }
} else {
    echo "<p>No referrals to test with. Create a referral first.</p>\n";
}

echo "<hr>\n";

// Check 6: Simple direct test
echo "<h2>6. Direct mail() Function Test</h2>\n";
$testEmail = $fromEmail;
$testResult = mail(
    $testEmail,
    "Direct Test - " . date('H:i:s'),
    "This is a direct test of the mail() function from check_email_logs.php",
    "From: {$fromName} <{$fromEmail}>"
);

if ($testResult) {
    echo "<p class='good'>✓ Direct mail() call returned TRUE</p>\n";
    echo "<p>Check {$testEmail} for test email</p>\n";
} else {
    echo "<p class='bad'>✗ Direct mail() call returned FALSE</p>\n";
    echo "<p>mail() function is not working properly on this server</p>\n";
}

echo "<hr>\n";
echo "<h2>Next Steps</h2>\n";
echo "<ul>\n";
echo "<li>If direct mail() test worked but referral emails don't arrive, check if sendReferralConfirmation() is being called in index.php</li>\n";
echo "<li>If direct mail() test failed, we need to use SendGrid instead</li>\n";
echo "<li>Check spam folders thoroughly</li>\n";
echo "<li>Emails via mail() can take 1-5 minutes to arrive</li>\n";
echo "</ul>\n";
