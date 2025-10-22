<?php
/**
 * Email Diagnostics Script
 * This helps diagnose why emails aren't working
 */

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';

echo "<h1>Email System Diagnostics</h1>\n";
echo "<style>
    body { font-family: Arial, sans-serif; padding: 20px; }
    .good { color: green; font-weight: bold; }
    .bad { color: red; font-weight: bold; }
    .warning { color: orange; font-weight: bold; }
    table { border-collapse: collapse; width: 100%; margin: 20px 0; }
    th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
    th { background: #f0f0f0; }
    pre { background: #f5f5f5; padding: 10px; border: 1px solid #ddd; }
</style>\n";

echo "<hr>\n";

// Test 1: Check if mail() function exists
echo "<h2>1. PHP mail() Function Check</h2>\n";
if (function_exists('mail')) {
    echo "<p class='good'>✓ mail() function is available</p>\n";
} else {
    echo "<p class='bad'>✗ mail() function is NOT available (this is a problem!)</p>\n";
}

// Test 2: Check PHP mail configuration
echo "<h2>2. PHP Mail Configuration</h2>\n";
echo "<table>\n";
echo "<tr><th>Setting</th><th>Value</th><th>Status</th></tr>\n";

$mailSettings = [
    'sendmail_path' => ini_get('sendmail_path'),
    'SMTP' => ini_get('SMTP'),
    'smtp_port' => ini_get('smtp_port'),
    'sendmail_from' => ini_get('sendmail_from'),
];

foreach ($mailSettings as $key => $value) {
    $status = empty($value) ? "<span class='warning'>Not set</span>" : "<span class='good'>Set</span>";
    $displayValue = empty($value) ? '<em>(empty)</em>' : htmlspecialchars($value);
    echo "<tr><td>{$key}</td><td>{$displayValue}</td><td>{$status}</td></tr>\n";
}
echo "</table>\n";

// Test 3: Check database settings
echo "<h2>3. Database Email Settings</h2>\n";
$emailSettings = getRows(
    "SELECT setting_key, setting_value FROM settings
     WHERE setting_key LIKE '%mail%' OR setting_key LIKE '%smtp%' OR setting_key LIKE '%email%'
     ORDER BY setting_key"
);

if ($emailSettings) {
    echo "<table>\n";
    echo "<tr><th>Setting Key</th><th>Value</th><th>Status</th></tr>\n";

    $hasEmailMethod = false;
    $emailMethodValue = '';
    $hasFromEmail = false;
    $hasFromName = false;

    foreach ($emailSettings as $setting) {
        $value = htmlspecialchars($setting['setting_value']);
        $key = $setting['setting_key'];

        if ($key === 'email_method') {
            $hasEmailMethod = true;
            $emailMethodValue = $setting['setting_value'];
            $status = ($setting['setting_value'] === 'mail')
                ? "<span class='good'>✓ Correct</span>"
                : "<span class='bad'>✗ Should be 'mail'</span>";
        } elseif ($key === 'smtp_from_email') {
            $hasFromEmail = !empty($setting['setting_value']);
            $status = $hasFromEmail
                ? "<span class='good'>✓ Set</span>"
                : "<span class='bad'>✗ Missing</span>";
        } elseif ($key === 'smtp_from_name') {
            $hasFromName = !empty($setting['setting_value']);
            $status = $hasFromName
                ? "<span class='good'>✓ Set</span>"
                : "<span class='bad'>✗ Missing</span>";
        } else {
            $status = "<span class='warning'>(Not used with mail())</span>";
        }

        echo "<tr><td>{$key}</td><td>{$value}</td><td>{$status}</td></tr>\n";
    }
    echo "</table>\n";

    // Check for critical settings
    if (!$hasEmailMethod) {
        echo "<p class='bad'>✗ CRITICAL: 'email_method' setting is missing from database!</p>\n";
        echo "<p>Run this SQL: <code>INSERT INTO settings (setting_key, setting_value) VALUES ('email_method', 'mail');</code></p>\n";
    }

    if (!$hasFromEmail) {
        echo "<p class='bad'>✗ CRITICAL: 'smtp_from_email' setting is missing!</p>\n";
    }

    if (!$hasFromName) {
        echo "<p class='bad'>✗ CRITICAL: 'smtp_from_name' setting is missing!</p>\n";
    }

} else {
    echo "<p class='bad'>✗ No email settings found in database!</p>\n";
}

// Test 4: Check if email.php file exists and is readable
echo "<h2>4. Email Function Files</h2>\n";
$emailPhp = __DIR__ . '/includes/email.php';
if (file_exists($emailPhp)) {
    echo "<p class='good'>✓ /includes/email.php exists</p>\n";

    // Check if sendEmailViaMailFunction exists
    require_once $emailPhp;
    if (function_exists('sendEmailViaMailFunction')) {
        echo "<p class='good'>✓ sendEmailViaMailFunction() is defined</p>\n";
    } else {
        echo "<p class='bad'>✗ sendEmailViaMailFunction() is NOT defined</p>\n";
        echo "<p>The email.php file may be outdated. Pull latest code from GitHub.</p>\n";
    }
} else {
    echo "<p class='bad'>✗ /includes/email.php is missing!</p>\n";
}

// Test 5: Simple mail() test
echo "<h2>5. Simple mail() Test</h2>\n";
echo "<p>Attempting to send a basic test email...</p>\n";

$testTo = getSetting('smtp_from_email', 'office@alive.me.uk'); // Send to yourself
$testSubject = "Test from " . $_SERVER['HTTP_HOST'] . " - " . date('H:i:s');
$testMessage = "This is a test email from the diagnostic script.\n\nTime: " . date('Y-m-d H:i:s') . "\nServer: " . $_SERVER['HTTP_HOST'];
$testHeaders = "From: Test <{$testTo}>\r\n";

$result = @mail($testTo, $testSubject, $testMessage, $testHeaders);

if ($result) {
    echo "<p class='good'>✓ mail() function returned TRUE (email was accepted for delivery)</p>\n";
    echo "<p>Check your inbox at <strong>{$testTo}</strong> (may take a few minutes, check spam folder)</p>\n";
} else {
    echo "<p class='bad'>✗ mail() function returned FALSE (email was rejected)</p>\n";
    echo "<p>This usually means:</p>\n";
    echo "<ul>\n";
    echo "<li>The server's mail system is not configured</li>\n";
    echo "<li>The sendmail path is incorrect</li>\n";
    echo "<li>The hosting provider has disabled mail()</li>\n";
    echo "</ul>\n";
}

// Test 6: Check error log
echo "<h2>6. Recent Error Log</h2>\n";
$errorLog = ini_get('error_log');
if ($errorLog && file_exists($errorLog)) {
    echo "<p>Error log location: <code>{$errorLog}</code></p>\n";
    $lastLines = @file($errorLog);
    if ($lastLines) {
        $lastLines = array_slice($lastLines, -20); // Last 20 lines
        echo "<pre>" . htmlspecialchars(implode("", $lastLines)) . "</pre>\n";
    }
} else {
    echo "<p class='warning'>Could not locate error log file</p>\n";
}

echo "<hr>\n";
echo "<h2>Summary</h2>\n";
echo "<p>If all checks above passed but emails still aren't working, the issue is likely:</p>\n";
echo "<ol>\n";
echo "<li><strong>GoDaddy restrictions:</strong> Some GoDaddy plans disable mail() or require specific 'From' addresses</li>\n";
echo "<li><strong>From address:</strong> The 'From' email must be hosted on the same server/domain</li>\n";
echo "<li><strong>SPF/DKIM:</strong> Email authentication may be blocking messages</li>\n";
echo "<li><strong>Spam filters:</strong> Emails might be delivered but going to spam</li>\n";
echo "</ol>\n";

echo "<p><strong>Next steps:</strong></p>\n";
echo "<ul>\n";
echo "<li>Check if <code>office@alive.me.uk</code> is hosted on the same GoDaddy account</li>\n";
echo "<li>Try changing the From address to something like <code>noreply@yourdomain.com</code></li>\n";
echo "<li>Contact GoDaddy support to confirm mail() is enabled for your hosting plan</li>\n";
echo "<li>Check spam folders thoroughly</li>\n";
echo "</ul>\n";
