<?php
/**
 * Background Email Processor
 * This script runs in the background to send emails without blocking the user
 */

// This script should only be run from command line or via exec()
// Not directly accessible via browser for security

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/email.php';

// Get parameters from command line arguments
$emailType = $argv[1] ?? '';
$emailData = $argv[2] ?? '';

if (empty($emailType) || empty($emailData)) {
    error_log("Background email processor: Missing parameters");
    exit(1);
}

// Decode the email data
$data = json_decode($emailData, true);

if (!$data) {
    error_log("Background email processor: Invalid JSON data");
    exit(1);
}

// Process the email based on type
try {
    switch ($emailType) {
        case 'referral_confirmation':
            $result = sendReferralConfirmation(
                $data['email'],
                $data['name'],
                $data['child_count'],
                $data['household_id']
            );

            if ($result) {
                error_log("Background email: Referral confirmation sent to {$data['email']}");
            } else {
                error_log("Background email: Failed to send referral confirmation to {$data['email']}");
            }
            break;

        case 'collection_ready':
            $result = sendCollectionReadyEmail($data['referral_id']);

            if ($result) {
                error_log("Background email: Collection ready email sent for referral {$data['referral_id']}");
            } else {
                error_log("Background email: Failed to send collection ready email for referral {$data['referral_id']}");
            }
            break;

        default:
            error_log("Background email processor: Unknown email type: {$emailType}");
            exit(1);
    }

    exit(0);

} catch (Exception $e) {
    error_log("Background email processor error: " . $e->getMessage());
    exit(1);
}
