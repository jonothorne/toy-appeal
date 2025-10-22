<?php
/**
 * Test PHP mail() function
 * This tests whether the server can send emails using PHP's built-in mail() function
 */

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/email.php';

// Test email address - CHANGE THIS TO YOUR EMAIL
$testEmail = 'jonothorne@icloud.com';

echo "<h1>Testing PHP mail() Function</h1>\n";
echo "<p>Testing email delivery using PHP's mail() function (bypasses SMTP ports)</p>\n";
echo "<hr>\n";

// Test 1: Basic mail() function test
echo "<h2>Test 1: Basic mail() Test</h2>\n";
$to = $testEmail;
$subject = "Test Email from PHP mail() - " . date('Y-m-d H:i:s');
$message = "This is a test email sent using PHP's mail() function.\n\nIf you receive this, the mail() function is working correctly on this server.";
$headers = "From: Alive Church <office@alive.me.uk>\r\n";
$headers .= "Reply-To: office@alive.me.uk\r\n";

$result = mail($to, $subject, $message, $headers);

if ($result) {
    echo "<p style='color: green;'>✓ Basic mail() test: Email sent successfully!</p>\n";
    echo "<p>Check {$testEmail} for the test email.</p>\n";
} else {
    echo "<p style='color: red;'>✗ Basic mail() test: Failed to send email.</p>\n";
}

echo "<hr>\n";

// Test 2: Using our sendEmailViaMailFunction()
echo "<h2>Test 2: sendEmailViaMailFunction() Test</h2>\n";

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
            <h1>Test Email</h1>
        </div>
        <div style='padding: 20px;'>
            <p>This is a <strong>HTML test email</strong> sent using the sendEmailViaMailFunction().</p>
            <p>If you receive this with proper HTML formatting, the function is working correctly!</p>
            <p>Sent at: " . date('Y-m-d H:i:s') . "</p>
        </div>
    </div>
</body>
</html>
";

$result2 = sendEmailViaMailFunction($testEmail, "HTML Test Email - " . date('Y-m-d H:i:s'), $htmlBody);

if ($result2) {
    echo "<p style='color: green;'>✓ HTML email test: Email sent successfully!</p>\n";
    echo "<p>Check {$testEmail} for the HTML test email.</p>\n";
} else {
    echo "<p style='color: red;'>✗ HTML email test: Failed to send email.</p>\n";
}

echo "<hr>\n";

// Test 3: Using main sendEmail() function (should use mail method)
echo "<h2>Test 3: sendEmail() with 'mail' method</h2>\n";
echo "<p>Current email_method setting: <strong>" . getSetting('email_method', 'not set') . "</strong></p>\n";

$result3 = sendEmail($testEmail, "Full System Test - " . date('Y-m-d H:i:s'), $htmlBody);

if ($result3) {
    echo "<p style='color: green;'>✓ System email test: Email sent successfully!</p>\n";
    echo "<p>Check {$testEmail} for the system test email.</p>\n";
} else {
    echo "<p style='color: red;'>✗ System email test: Failed to send email.</p>\n";
}

echo "<hr>\n";
echo "<h2>Summary</h2>\n";
echo "<p>All tests completed. Check {$testEmail} for test emails.</p>\n";
echo "<p><strong>Note:</strong> Emails sent via mail() may take a few minutes to arrive and might go to spam folder.</p>\n";
echo "<p><strong>Important for GoDaddy:</strong> The 'From' address (office@alive.me.uk) should ideally be hosted on the same GoDaddy account for best deliverability.</p>\n";
