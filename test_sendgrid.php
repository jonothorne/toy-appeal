<?php
/**
 * SendGrid Email Test
 * Tests if SendGrid integration is working
 */

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/email.php';

echo "<h1>SendGrid Email Test</h1>\n";
echo "<style>
    body { font-family: Arial; padding: 20px; }
    .good { color: green; font-weight: bold; }
    .bad { color: red; font-weight: bold; }
    .warning { color: orange; font-weight: bold; }
    pre { background: #f5f5f5; padding: 10px; border: 1px solid #ddd; }
</style>\n";

// Test email address
$testEmail = 'jonothorne@icloud.com'; // CHANGE THIS

echo "<h2>1. Check SendGrid Configuration</h2>\n";

$apiKey = getSetting('sendgrid_api_key', '');
$emailMethod = getSetting('email_method', '');
$fromEmail = getSetting('smtp_from_email', '');
$fromName = getSetting('smtp_from_name', '');

echo "<table border='1' cellpadding='5'>\n";
echo "<tr><th>Setting</th><th>Value</th><th>Status</th></tr>\n";

echo "<tr><td>email_method</td><td>{$emailMethod}</td><td>";
if ($emailMethod === 'sendgrid') {
    echo "<span class='good'>✓ Correct</span>";
} else {
    echo "<span class='bad'>✗ Should be 'sendgrid'</span>";
}
echo "</td></tr>\n";

echo "<tr><td>sendgrid_api_key</td><td>";
if (empty($apiKey)) {
    echo "<em>(not set)</em></td><td><span class='bad'>✗ Missing</span>";
} else {
    echo substr($apiKey, 0, 15) . "...</td><td><span class='good'>✓ Set</span>";
}
echo "</td></tr>\n";

echo "<tr><td>smtp_from_email</td><td>{$fromEmail}</td><td>";
if (!empty($fromEmail)) {
    echo "<span class='good'>✓ Set</span>";
} else {
    echo "<span class='bad'>✗ Missing</span>";
}
echo "</td></tr>\n";

echo "<tr><td>smtp_from_name</td><td>{$fromEmail}</td><td>";
if (!empty($fromName)) {
    echo "<span class='good'>✓ Set</span>";
} else {
    echo "<span class='bad'>✗ Missing</span>";
}
echo "</td></tr>\n";

echo "</table>\n";

if (empty($apiKey)) {
    echo "<div style='background: #fee; padding: 15px; margin: 20px 0; border: 2px solid #f00;'>\n";
    echo "<h3>⚠️ SendGrid Not Configured</h3>\n";
    echo "<p>You need to:</p>\n";
    echo "<ol>\n";
    echo "<li>Sign up for free SendGrid account: <a href='https://signup.sendgrid.com/' target='_blank'>https://signup.sendgrid.com/</a></li>\n";
    echo "<li>Get your API key from Settings > API Keys</li>\n";
    echo "<li>Verify your sender email (office@alive.me.uk)</li>\n";
    echo "<li>Run this SQL in phpMyAdmin:</li>\n";
    echo "</ol>\n";
    echo "<pre>UPDATE settings SET setting_value = 'sendgrid' WHERE setting_key = 'email_method';\n";
    echo "INSERT INTO settings (setting_key, setting_value) VALUES ('sendgrid_api_key', 'YOUR_API_KEY_HERE')\n";
    echo "ON DUPLICATE KEY UPDATE setting_value = 'YOUR_API_KEY_HERE';</pre>\n";
    echo "<p>See SENDGRID_SETUP.md for full instructions.</p>\n";
    echo "</div>\n";
    exit;
}

echo "<hr>\n";

// Test 2: Send test email
echo "<h2>2. Send Test Email via SendGrid</h2>\n";
echo "<p>Sending test email to: <strong>{$testEmail}</strong></p>\n";

$htmlBody = "
<!DOCTYPE html>
<html>
<head>
    <meta charset='UTF-8'>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; background: #f9fafb; }
        .header { background: #2563eb; color: white; padding: 20px; text-align: center; }
    </style>
</head>
<body>
    <div class='container'>
        <div class='header'>
            <h1>SendGrid Test Email</h1>
        </div>
        <div style='padding: 20px;'>
            <p>✅ <strong>Success!</strong> This email was sent via SendGrid API.</p>
            <p>If you received this email, SendGrid is working correctly!</p>
            <p>Sent at: " . date('Y-m-d H:i:s') . "</p>
            <p>From: {$fromName} &lt;{$fromEmail}&gt;</p>
        </div>
    </div>
</body>
</html>
";

$result = sendEmailViaSendGrid($testEmail, "SendGrid Test - " . date('H:i:s'), $htmlBody);

if ($result) {
    echo "<p class='good'>✓ Email sent successfully via SendGrid!</p>\n";
    echo "<p>Check your inbox at <strong>{$testEmail}</strong></p>\n";
    echo "<p>It should arrive within seconds. Check spam folder if not in inbox.</p>\n";
} else {
    echo "<p class='bad'>✗ Failed to send email via SendGrid</p>\n";
    echo "<p>Check your error logs for details. Common issues:</p>\n";
    echo "<ul>\n";
    echo "<li>Invalid API key</li>\n";
    echo "<li>Sender email not verified in SendGrid</li>\n";
    echo "<li>SendGrid account suspended</li>\n";
    echo "</ul>\n";
}

echo "<hr>\n";

// Test 3: Test via main sendEmail function
echo "<h2>3. Test Main sendEmail() Function</h2>\n";
$result2 = sendEmail($testEmail, "System Test via SendGrid - " . date('H:i:s'), $htmlBody);

if ($result2) {
    echo "<p class='good'>✓ sendEmail() function working!</p>\n";
    echo "<p>Your referral system is ready to send emails!</p>\n";
} else {
    echo "<p class='bad'>✗ sendEmail() function failed</p>\n";
}

echo "<hr>\n";
echo "<h2>Summary</h2>\n";
if ($result && $result2) {
    echo "<div style='background: #dfd; padding: 15px; border: 2px solid #0a0;'>\n";
    echo "<h3 class='good'>✅ ALL TESTS PASSED</h3>\n";
    echo "<p>SendGrid is configured correctly and emails are being sent!</p>\n";
    echo "<p><strong>Your system is ready to launch!</strong></p>\n";
    echo "</div>\n";
} else {
    echo "<div style='background: #fee; padding: 15px; border: 2px solid #f00;'>\n";
    echo "<h3 class='bad'>❌ TESTS FAILED</h3>\n";
    echo "<p>Review the errors above and check:</p>\n";
    echo "<ul>\n";
    echo "<li>SendGrid API key is correct</li>\n";
    echo "<li>Sender email is verified in SendGrid</li>\n";
    echo "<li>email_method is set to 'sendgrid'</li>\n";
    echo "</ul>\n";
    echo "</div>\n";
}
