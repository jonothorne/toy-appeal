<?php
// Direct SES API test - bypasses all application logic
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/vendor/autoload.php';

use Aws\Ses\SesClient;
use Aws\Exception\AwsException;

echo "<h1>Testing Amazon SES API</h1>";
echo "<pre>";

// Get settings from database
$awsAccessKey = getSetting('smtp_username', '');
$awsSecretKey = getSetting('smtp_password', '');
$awsRegion = getSetting('aws_region', 'eu-west-2');
$fromEmail = getSetting('smtp_from_email', 'office@alive.me.uk');
$fromName = getSetting('smtp_from_name', 'Alive Church');
$emailMethod = getSetting('email_method', 'smtp');

echo "=== CONFIGURATION ===\n";
echo "Email Method: {$emailMethod}\n";
echo "AWS Region: {$awsRegion}\n";
echo "AWS Access Key: {$awsAccessKey}\n";
echo "AWS Secret Key: " . (strlen($awsSecretKey) > 0 ? str_repeat('*', 20) . ' (hidden)' : 'MISSING!') . "\n";
echo "From Email: {$fromEmail}\n";
echo "From Name: {$fromName}\n";
echo "\n";

if (empty($awsAccessKey) || empty($awsSecretKey)) {
    echo "❌ ERROR: Missing AWS credentials!\n";
    exit;
}

if ($emailMethod !== 'ses_api') {
    echo "⚠️ WARNING: email_method is set to '{$emailMethod}', not 'ses_api'\n";
    echo "The application will use SMTP instead of SES API!\n\n";
}

// Test email address - CHANGE THIS to your verified email
$testEmail = 'jonothorne@icloud.com';

echo "=== TESTING SES API ===\n";
echo "Sending test email to: {$testEmail}\n\n";

try {
    // Create SES client
    $sesClient = new SesClient([
        'version' => 'latest',
        'region' => $awsRegion,
        'credentials' => [
            'key' => $awsAccessKey,
            'secret' => $awsSecretKey,
        ],
    ]);

    echo "✅ SES Client created successfully\n";

    // Send test email
    $result = $sesClient->sendEmail([
        'Source' => "\"{$fromName}\" <{$fromEmail}>",
        'Destination' => [
            'ToAddresses' => [$testEmail],
        ],
        'Message' => [
            'Subject' => [
                'Data' => 'Test Email from Toy Appeal System',
                'Charset' => 'UTF-8',
            ],
            'Body' => [
                'Html' => [
                    'Data' => '<h1>Test Email</h1><p>This is a test email from the Amazon SES API.</p><p>If you receive this, the SES API is working correctly!</p>',
                    'Charset' => 'UTF-8',
                ],
                'Text' => [
                    'Data' => 'Test Email - This is a test email from the Amazon SES API. If you receive this, the SES API is working correctly!',
                    'Charset' => 'UTF-8',
                ],
            ],
        ],
    ]);

    $messageId = $result->get('MessageId');
    echo "✅ EMAIL SENT SUCCESSFULLY!\n";
    echo "Message ID: {$messageId}\n";
    echo "\nCheck your inbox at: {$testEmail}\n";
    echo "(Also check spam/junk folder)\n";

} catch (AwsException $e) {
    echo "❌ AWS ERROR:\n";
    echo "Error Code: " . $e->getAwsErrorCode() . "\n";
    echo "Error Message: " . $e->getMessage() . "\n";
    echo "\n";

    if ($e->getAwsErrorCode() === 'InvalidClientTokenId') {
        echo "💡 This means the AWS Access Key is invalid.\n";
        echo "   You need to create IAM credentials (not just SMTP credentials).\n";
    } elseif ($e->getAwsErrorCode() === 'SignatureDoesNotMatch') {
        echo "💡 This means the Secret Access Key is wrong.\n";
        echo "   You have the SMTP password, but need the IAM Secret Access Key.\n";
    } elseif ($e->getAwsErrorCode() === 'MessageRejected') {
        echo "💡 Check that:\n";
        echo "   1. {$fromEmail} is verified in SES\n";
        echo "   2. {$testEmail} is verified in SES (if in sandbox mode)\n";
        echo "   3. You're using the correct AWS region\n";
    }

} catch (Exception $e) {
    echo "❌ GENERAL ERROR:\n";
    echo $e->getMessage() . "\n";
}

echo "</pre>";
?>
