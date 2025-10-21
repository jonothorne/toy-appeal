<?php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Send email using PHPMailer with SMTP
function sendEmail($to, $subject, $htmlBody, $textBody = '') {
    // Get email settings from database
    $fromEmail = getSetting('smtp_from_email', FROM_EMAIL);
    $fromName = getSetting('smtp_from_name', FROM_NAME);
    $smtpHost = getSetting('smtp_host', SMTP_HOST);
    $smtpPort = getSetting('smtp_port', SMTP_PORT);
    $smtpUsername = getSetting('smtp_username', SMTP_USERNAME);
    $smtpPassword = getSetting('smtp_password', SMTP_PASSWORD);

    $mail = new PHPMailer(true);

    try {
        // Debug output disabled for production - emails are working!
        // Uncomment these lines if you need to debug email issues:
        // if (error_reporting() > 0) {
        //     $mail->SMTPDebug = 2;
        //     $mail->Debugoutput = function($str, $level) {
        //         error_log("PHPMailer [$level]: $str");
        //     };
        // }

        // Server settings
        $mail->isSMTP();
        $mail->Host       = $smtpHost;

        // Only use authentication if credentials are provided
        if (!empty($smtpUsername) && !empty($smtpPassword)) {
            $mail->SMTPAuth   = true;
            $mail->Username   = $smtpUsername;
            $mail->Password   = $smtpPassword;
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        } else {
            $mail->SMTPAuth   = false;
        }

        $mail->Port       = $smtpPort;
        $mail->Timeout    = 30; // 30 second timeout

        // Recipients
        $mail->setFrom($fromEmail, $fromName);
        $mail->addAddress($to);
        $mail->addReplyTo($fromEmail, $fromName);

        // Additional headers to improve deliverability
        $mail->addCustomHeader('X-Priority', '1'); // High priority for collection emails
        $mail->addCustomHeader('X-MSMail-Priority', 'High');
        $mail->addCustomHeader('Importance', 'High');
        $mail->addCustomHeader('Organization', $fromName);

        // Content
        $mail->isHTML(true);
        $mail->CharSet = 'UTF-8';
        $mail->Subject = $subject;
        $mail->Body    = $htmlBody;
        $mail->AltBody = $textBody ?: strip_tags($htmlBody);

        $mail->send();
        error_log("Email sent to {$to}: Success");
        return true;
    } catch (Exception $e) {
        $errorMsg = "Email failed to {$to}: {$mail->ErrorInfo} | Exception: " . $e->getMessage();
        echo "\n\nERROR: $errorMsg\n\n";
        error_log($errorMsg);
        return false;
    }
}

// Send referral confirmation email
function sendReferralConfirmation($referrerEmail, $referrerName, $childrenCount, $householdId) {
    $siteName = getSetting('site_name', 'Christmas Toy Appeal');

    $subject = "Referral Received - {$siteName}";

    $htmlBody = "
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset='UTF-8'>
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
            .header { background: #2563eb; color: white; padding: 20px; text-align: center; border-radius: 8px 8px 0 0; }
            .content { background: #f9fafb; padding: 30px; border-radius: 0 0 8px 8px; }
            .button { display: inline-block; background: #2563eb; color: white; padding: 12px 24px; text-decoration: none; border-radius: 6px; margin: 20px 0; }
            .footer { text-align: center; margin-top: 30px; color: #6b7280; font-size: 14px; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h1>{$siteName}</h1>
            </div>
            <div class='content'>
                <h2>Thank You for Your Referral</h2>
                <p>Dear {$referrerName},</p>
                <p>We have successfully received your referral for <strong>{$childrenCount}</strong> " . ($childrenCount == 1 ? 'child' : 'children') . ".</p>
                <p>Your referral has been assigned reference number: <strong>Household #{$householdId}</strong></p>
                <p>Our team will now process this referral and prepare the appropriate toys. You will receive another email when the parcel is ready for collection with full collection details.</p>
                <p>If you have any questions, please don't hesitate to contact us.</p>
                <p>Thank you for partnering with us to bring joy to children this Christmas!</p>
            </div>
            <div class='footer'>
                <p><strong>{$siteName}</strong></p>
                <p>Alive Church, High Street, Orpington, Kent BR6 0JY</p>
                <p>Email: office@alive.me.uk | Phone: 01689 762222</p>
                <p>&copy; " . date('Y') . " {$siteName}. All rights reserved.</p>
                <p style='font-size: 11px; color: #9ca3af; margin-top: 10px;'>
                    You are receiving this email because a referral was submitted through our Christmas Toy Appeal system.
                </p>
            </div>
        </div>
    </body>
    </html>
    ";

    return sendEmail($referrerEmail, $subject, $htmlBody);
}

// Send collection ready email
function sendCollectionReadyEmail($referralId) {
    // Get referral and household details
    $sql = "SELECT r.*, h.referrer_name, h.referrer_email, h.referrer_organisation, z.zone_name, z.location as zone_location
            FROM referrals r
            JOIN households h ON r.household_id = h.id
            LEFT JOIN zones z ON r.zone_id = z.id
            WHERE r.id = ?";

    $referral = getRow($sql, "i", [$referralId]);

    if (!$referral) {
        return false;
    }

    // Get all siblings in the same household
    $siblings = getRows(
        "SELECT reference_number, child_initials FROM referrals WHERE household_id = ? AND status = 'ready_for_collection'",
        "i",
        [$referral['household_id']]
    );

    $siteName = getSetting('site_name', 'Alive Church Christmas Toy Appeal');
    $collectionLocation = getSetting('collection_location', 'Main Warehouse');
    $collectionHours = getSetting('collection_hours', 'Monday-Friday 9am-5pm');

    $childCount = count($siblings);
    $subject = $childCount > 1
        ? "Parcels Ready for Collection - {$childCount} Children (Household #{$referral['household_id']})"
        : "Parcel Ready for Collection - {$referral['reference_number']}";

    $siblingsList = '';
    foreach ($siblings as $sibling) {
        $siblingsList .= "<li><strong>{$sibling['reference_number']}</strong> - Child initials: {$sibling['child_initials']}</li>";
    }

    $htmlBody = "
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset='UTF-8'>
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
            .header { background: #059669; color: white; padding: 20px; text-align: center; border-radius: 8px 8px 0 0; }
            .content { background: #f9fafb; padding: 30px; border-radius: 0 0 8px 8px; }
            .info-box { background: white; border-left: 4px solid #059669; padding: 15px; margin: 20px 0; }
            .footer { text-align: center; margin-top: 30px; color: #6b7280; font-size: 14px; }
            ul { padding-left: 20px; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h1>Parcel Ready for Collection!</h1>
            </div>
            <div class='content'>
                <p>Dear {$referral['referrer_name']},</p>
                <p>Great news! <strong>All {$childCount} toy parcel(s)</strong> for this family are now ready for collection.</p>

                <div class='info-box'>
                    <h3>Collection Details:</h3>
                    <p><strong>Location:</strong> {$collectionLocation}</p>
                    <p><strong>Hours:</strong> {$collectionHours}</p>
                    <p><strong>Organisation:</strong> {$referral['referrer_organisation']}</p>
                </div>

                <div class='info-box'>
                    <h3>All Parcels Ready to Collect ({$childCount} " . ($childCount == 1 ? 'Child' : 'Children') . "):</h3>
                    <ul>
                        {$siblingsList}
                    </ul>
                    " . (!empty($referral['zone_location']) ? "<p><strong>Warehouse Location:</strong> {$referral['zone_location']}</p>" : (!empty($referral['zone_name']) ? "<p><strong>Warehouse Location:</strong> {$referral['zone_name']}</p>" : "")) . "
                </div>

                <p>Please bring this email or note the reference numbers above when collecting.</p>
                <p>Thank you for your continued partnership in making Christmas special for these families!</p>
            </div>
            <div class='footer'>
                <p><strong>{$siteName}</strong></p>
                <p>Alive Church, High Street, Orpington, Kent BR6 0JY</p>
                <p>Email: office@alive.me.uk | Phone: 01689 762222</p>
                <p>&copy; " . date('Y') . " {$siteName}. All rights reserved.</p>
                <p style='font-size: 11px; color: #9ca3af; margin-top: 10px;'>
                    You are receiving this email because a referral was submitted through our Christmas Toy Appeal system.
                </p>
            </div>
        </div>
    </body>
    </html>
    ";

    return sendEmail($referral['referrer_email'], $subject, $htmlBody);
}

// Helper function to get email template
function getEmailTemplate($title, $content) {
    $siteName = getSetting('site_name', 'Christmas Toy Appeal');

    return "
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset='UTF-8'>
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
            .header { background: #2563eb; color: white; padding: 20px; text-align: center; border-radius: 8px 8px 0 0; }
            .content { background: #f9fafb; padding: 30px; border-radius: 0 0 8px 8px; }
            .footer { text-align: center; margin-top: 30px; color: #6b7280; font-size: 14px; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h1>{$title}</h1>
            </div>
            <div class='content'>
                {$content}
            </div>
            <div class='footer'>
                <p>&copy; " . date('Y') . " {$siteName}. All rights reserved.</p>
            </div>
        </div>
    </body>
    </html>
    ";
}
