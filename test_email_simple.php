<?php
/**
 * SIMPLEST EMAIL TEST POSSIBLE
 * This tests if basic email sending works at all
 */

echo "<h1>Simple Email Test</h1>\n";
echo "<style>body { font-family: Arial; padding: 20px; } .good { color: green; } .bad { color: red; }</style>\n";

// CHANGE THIS TO YOUR EMAIL
$testEmail = "jonothorne@icloud.com";

echo "<h2>Test 1: Absolute Simplest mail() Test</h2>\n";

$result = mail(
    $testEmail,
    "Test from " . $_SERVER['HTTP_HOST'],
    "If you get this, mail() works!",
    "From: noreply@" . $_SERVER['HTTP_HOST']
);

if ($result) {
    echo "<p class='good'>✓ mail() returned TRUE</p>\n";
    echo "<p>Check {$testEmail} (including spam folder)</p>\n";
} else {
    echo "<p class='bad'>✗ mail() returned FALSE</p>\n";
    echo "<p><strong>PROBLEM:</strong> mail() is disabled or not configured on this server</p>\n";
}

echo "<hr>\n";

// Test 2: Check configuration
echo "<h2>Test 2: Server Configuration</h2>\n";
echo "<table border='1' cellpadding='5'>\n";
echo "<tr><th>Setting</th><th>Value</th></tr>\n";
echo "<tr><td>Server</td><td>" . $_SERVER['HTTP_HOST'] . "</td></tr>\n";
echo "<tr><td>PHP Version</td><td>" . phpversion() . "</td></tr>\n";
echo "<tr><td>mail() enabled</td><td>" . (function_exists('mail') ? 'YES' : 'NO') . "</td></tr>\n";
echo "<tr><td>sendmail_path</td><td>" . ini_get('sendmail_path') . "</td></tr>\n";
echo "<tr><td>SMTP</td><td>" . ini_get('SMTP') . "</td></tr>\n";
echo "<tr><td>smtp_port</td><td>" . ini_get('smtp_port') . "</td></tr>\n";
echo "</table>\n";

echo "<hr>\n";
echo "<h2>What to do if mail() doesn't work:</h2>\n";
echo "<ol>\n";
echo "<li><strong>Contact GoDaddy Support:</strong> Ask them to enable mail() function</li>\n";
echo "<li><strong>Use SendGrid:</strong> Free tier, 100 emails/day, works everywhere (I can set this up)</li>\n";
echo "<li><strong>Use Mailgun:</strong> Another reliable alternative</li>\n";
echo "<li><strong>Use AWS SES Sandbox:</strong> Doesn't require production approval for verified emails</li>\n";
echo "</ol>\n";
