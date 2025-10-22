<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/email.php';

$pageTitle = "Make a Referral";
$success = false;
$error = '';

// Check if referrals are enabled
$referralsEnabled = getSetting('enable_referrals', '1');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $referralsEnabled == '1') {
    // Validate input
    $referrerData = [
        'name' => trim($_POST['referrer_name'] ?? ''),
        'organisation' => trim($_POST['referrer_organisation'] ?? ''),
        'team' => trim($_POST['referrer_team'] ?? ''),
        'secondary_contact' => trim($_POST['secondary_contact'] ?? ''),
        'phone' => trim($_POST['referrer_phone'] ?? ''),
        'email' => trim($_POST['referrer_email'] ?? ''),
        'postcode' => strtoupper(trim($_POST['postcode'] ?? '')),
        'duration_known' => $_POST['duration_known'] ?? '',
        'additional_notes' => trim($_POST['additional_notes'] ?? '')
    ];

    // Validate children data
    $childrenData = [];
    if (isset($_POST['children']) && is_array($_POST['children'])) {
        foreach ($_POST['children'] as $child) {
            if (!empty($child['initials']) && !empty($child['age']) && !empty($child['gender'])) {
                $childrenData[] = [
                    'initials' => strtoupper(trim($child['initials'])),
                    'age' => intval($child['age']),
                    'gender' => trim($child['gender']),
                    'special_requirements' => trim($child['special_requirements'] ?? '')
                ];
            }
        }
    }

    // Validate required fields
    if (empty($referrerData['name']) || empty($referrerData['organisation']) ||
        empty($referrerData['phone']) || empty($referrerData['email']) ||
        empty($referrerData['postcode']) || empty($referrerData['duration_known'])) {
        $error = "Please fill in all required fields.";
    } elseif (empty($childrenData)) {
        $error = "Please add at least one child to the referral.";
    } elseif (!filter_var($referrerData['email'], FILTER_VALIDATE_EMAIL)) {
        $error = "Please enter a valid email address.";
    } elseif (!isset($_POST['referral_agreement']) || $_POST['referral_agreement'] !== 'on') {
        $error = "You must read and agree to the Referral Agreement before submitting.";
    } elseif (!isset($_POST['gdpr_consent']) || $_POST['gdpr_consent'] !== 'on') {
        $error = "You must consent to data processing to submit a referral.";
    } else {
        // Add GDPR consent to referrer data
        $referrerData['gdpr_consent'] = 1;
        $referrerData['gdpr_consent_date'] = date('Y-m-d H:i:s');
        // Create referral
        $result = createReferral($referrerData, $childrenData);

        if ($result['success']) {
            // Send confirmation email
            sendReferralConfirmation(
                $referrerData['email'],
                $referrerData['name'],
                count($childrenData),
                $result['household_id']
            );

            $success = true;
        } else {
            // Show detailed error in development, generic in production
            if (error_reporting() > 0) {
                $error = "Error: " . ($result['error'] ?? 'Unknown error');
            } else {
                $error = "An error occurred while processing your referral. Please try again.";
            }
        }
    }
}

require_once __DIR__ . '/includes/header.php';
?>

<div class="min-h-screen py-8 px-4">
    <div class="max-w-4xl mx-auto">
        <!-- Header -->
        <div class="bg-white rounded-lg shadow-lg p-8 mb-8 text-center">
            <img src="<?php echo SITE_URL; ?>/assets/imgs/logo.png" alt="Christmas Toy Appeal" class="mx-auto mb-4" style="max-height: 120px; width: auto;">
            <h1 class="text-3xl font-bold text-gray-900 mb-2">Alive Church Christmas Toy Appeal</h1>
            <h2 class="text-2xl font-bold text-gray-900 mb-2">Partner Organisation Referral Form</h1>
            <p class="text-gray-600">Please complete this form to refer a family for toy support</p>
        </div>

        <?php if ($referralsEnabled != '1'): ?>
            <div class="bg-yellow-50 border-l-4 border-yellow-400 p-4 rounded">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-yellow-400" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                        </svg>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm text-yellow-700">Referrals are currently closed. Please contact the administrator for more information.</p>
                    </div>
                </div>
            </div>
        <?php elseif ($success): ?>
            <!-- Success Message -->
            <div class="bg-green-50 border-l-4 border-green-400 p-6 rounded-lg shadow">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <svg class="h-8 w-8 text-green-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                    <div class="ml-4">
                        <h3 class="text-lg font-medium text-green-800">Referral Submitted Successfully!</h3>
                        <p class="mt-2 text-sm text-green-700">
                            Thank you for your referral. A confirmation email has been sent to <?php echo e($referrerData['email']); ?>.
                            You will receive another email when the parcel is ready for collection.
                        </p>
                        <a href="index.php" class="mt-4 inline-block text-sm font-medium text-green-600 hover:text-green-500">
                            Submit Another Referral &rarr;
                        </a>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <!-- Referral Form -->
            <div class="bg-white rounded-lg shadow-lg p-8">
                <?php if ($error): ?>
                    <div class="bg-red-50 border-l-4 border-red-400 p-4 mb-6 rounded">
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <svg class="h-5 w-5 text-red-400" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                                </svg>
                            </div>
                            <div class="ml-3">
                                <p class="text-sm text-red-700"><?php echo e($error); ?></p>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

                <form method="POST" action="" id="referralForm">
                    <!-- Referral Agreement Section -->
                    <div class="mb-8 bg-blue-50 border-2 border-blue-300 rounded-lg p-6">
                        <div class="flex items-start mb-4">
                            <svg class="h-6 w-6 text-blue-600 mt-1 mr-3 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                            <div class="flex-1">
                                <h3 class="text-lg font-semibold text-blue-900 mb-2">Important: Referral Agreement</h3>
                                <p class="text-blue-800 mb-3">
                                    Before making a referral, you must read and agree to our Referral Agreement. This document outlines the terms and conditions of our toy appeal service.
                                    <strong>You only need to read it once.</strong>
                                </p>
                                <a href="<?php echo SITE_URL; ?>/assets/Toy_Appeal_Referral_Agreement.pdf"
                                   target="_blank"
                                   class="inline-flex items-center px-4 py-2 bg-blue-600 text-white font-medium rounded-lg hover:bg-blue-700 transition shadow-sm">
                                    <svg class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                    </svg>
                                    Download & Read Referral Agreement (PDF)
                                </a>
                            </div>
                        </div>

                        <div class="border-t border-blue-200 pt-4 mt-4">
                            <div class="flex items-start">
                                <div class="flex items-center h-5">
                                    <input id="referral_agreement"
                                           name="referral_agreement"
                                           type="checkbox"
                                           required
                                           class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500">
                                </div>
                                <div class="ml-3">
                                    <label for="referral_agreement" class="text-sm font-medium text-blue-900">
                                        <span class="text-red-600">*</span> I have read and agree to the
                                        <a href="<?php echo SITE_URL; ?>/assets/Toy_Appeal_Referral_Agreement.pdf"
                                           target="_blank"
                                           class="text-blue-700 underline hover:text-blue-800">
                                            Referral Agreement
                                        </a>
                                    </label>
                                    <p class="text-xs text-blue-700 mt-1">
                                        You must read the agreement and check this box to proceed with your referral.
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Referrer Information -->
                    <div class="mb-8">
                        <h2 class="text-xl font-semibold text-gray-900 mb-4 pb-2 border-b">Your Information</h2>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Your Name <span class="text-red-500">*</span></label>
                                <input type="text" name="referrer_name" required
                                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                       value="<?php echo e($_POST['referrer_name'] ?? ''); ?>">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Organisation <span class="text-red-500">*</span></label>
                                <input type="text" name="referrer_organisation" required
                                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                       value="<?php echo e($_POST['referrer_organisation'] ?? ''); ?>">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Name of Team</label>
                                <input type="text" name="referrer_team"
                                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                       value="<?php echo e($_POST['referrer_team'] ?? ''); ?>">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Secondary Contact</label>
                                <input type="text" name="secondary_contact"
                                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                       value="<?php echo e($_POST['secondary_contact'] ?? ''); ?>">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Phone Number <span class="text-red-500">*</span></label>
                                <input type="tel" name="referrer_phone" required
                                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                       value="<?php echo e($_POST['referrer_phone'] ?? ''); ?>">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Email Address <span class="text-red-500">*</span></label>
                                <input type="email" name="referrer_email" required
                                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                       value="<?php echo e($_POST['referrer_email'] ?? ''); ?>">
                            </div>
                        </div>
                    </div>

                    <!-- Family Information -->
                    <div class="mb-8">
                        <h2 class="text-xl font-semibold text-gray-900 mb-4 pb-2 border-b">Family Information</h2>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Family Postcode <span class="text-red-500">*</span></label>
                                <input type="text" name="postcode" required
                                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                       value="<?php echo e($_POST['postcode'] ?? ''); ?>">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">How long has the family been known to your organisation? <span class="text-red-500">*</span></label>
                                <select name="duration_known" required
                                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                                    <option value="">Select...</option>
                                    <option value="<1 month">&lt;1 month</option>
                                    <option value="1-6 months">1-6 months</option>
                                    <option value="6-12 months">6-12 months</option>
                                    <option value="1-2 years">1-2 years</option>
                                    <option value="2+ years">2+ years</option>
                                </select>
                            </div>
                        </div>
                        <div class="mt-6">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Additional Notes</label>
                            <textarea name="additional_notes" rows="3"
                                      placeholder="Any additional information about the family's circumstances..."
                                      class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"><?php echo e($_POST['additional_notes'] ?? ''); ?></textarea>
                        </div>
                    </div>

                    <!-- Children Information -->
                    <div class="mb-8">
                        <h2 class="text-xl font-semibold text-gray-900 mb-4 pb-2 border-b">Children in Household<br /><small>Please refer <strong>all children</strong> who live in this household, not just the ones you work directly with.</small>
                        </h2>

                        <div id="childrenContainer">
                            <!-- Children will be added here via JavaScript -->
                        </div>
                        <button type="button" onclick="addChild()"
                                class="mt-4 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition">
                            + Add Child
                        </button>
                    </div>

                    <!-- GDPR Consent -->
                    <div class="mb-8 bg-blue-50 border border-blue-200 rounded-lg p-6">
                        <h2 class="text-xl font-semibold text-gray-900 mb-4">Data Protection & Privacy</h2>
                        <div class="space-y-4">
                            <div class="flex items-start">
                                <div class="flex items-center h-5">
                                    <input id="gdpr_consent" name="gdpr_consent" type="checkbox" required
                                           class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500">
                                </div>
                                <div class="ml-3">
                                    <label for="gdpr_consent" class="text-sm text-gray-700">
                                        <span class="font-medium">I consent to Alive Church collecting and processing the information provided in this referral form.</span>
                                        I understand that this data will be used solely for the purpose of processing this Christmas Toy Appeal referral and contacting me about collection arrangements.
                                        I understand that I have the right to request access to, correction of, or deletion of this data at any time by contacting
                                        <a href="mailto:office@alive.me.uk" class="text-blue-600 hover:text-blue-800 underline">office@alive.me.uk</a>.
                                        <span class="text-red-500">*</span>
                                    </label>
                                    <p class="text-xs text-gray-600 mt-2">
                                        For more information about how we handle your data, please read our
                                        <a href="privacy.php" target="_blank" class="text-blue-600 hover:text-blue-800 underline">Privacy Policy</a>.
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Submit Button -->
                    <div class="flex justify-end space-x-4">
                        <button type="reset" onclick="location.reload()"
                                class="px-6 py-3 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition">
                            Clear Form
                        </button>
                        <button type="submit"
                                class="px-6 py-3 bg-green-600 text-white rounded-lg hover:bg-green-700 transition font-medium">
                            Submit Referral
                        </button>
                    </div>
                </form>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
let childCount = 0;

function addChild() {
    childCount++;
    const container = document.getElementById('childrenContainer');

    const childDiv = document.createElement('div');
    childDiv.className = 'bg-gray-50 p-6 rounded-lg mb-4 border border-gray-200';
    childDiv.id = `child-${childCount}`;
    childDiv.innerHTML = `
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-lg font-medium text-gray-900">Child ${childCount}</h3>
            <button type="button" onclick="removeChild(${childCount})"
                    class="text-red-600 hover:text-red-800 font-medium text-sm">
                Remove
            </button>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Child Initials <span class="text-red-500">*</span></label>
                <input type="text" name="children[${childCount}][initials]" required maxlength="10"
                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                <p class="text-xs text-gray-500 mt-1">e.g., JD for John Doe</p>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Age <span class="text-red-500">*</span></label>
                <input type="number" name="children[${childCount}][age]" required min="0" max="18"
                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Gender <span class="text-red-500">*</span></label>
                <select name="children[${childCount}][gender]" required
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    <option value="">Select...</option>
                    <option value="Male">Male</option>
                    <option value="Female">Female</option>
                    <option value="Other">Other</option>
                    <option value="Prefer not to say">Prefer not to say</option>
                </select>
            </div>
            <div class="md:col-span-2">
                <label class="block text-sm font-medium text-gray-700 mb-2">Special Requirements</label>
                <textarea name="children[${childCount}][special_requirements]" rows="2"
                          class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                          placeholder="e.g., learning difficulties, sensory needs, disabilities"></textarea>
            </div>
        </div>
    `;

    container.appendChild(childDiv);
}

function removeChild(id) {
    const childDiv = document.getElementById(`child-${id}`);
    if (childDiv) {
        childDiv.remove();
    }
}

// Add first child automatically
document.addEventListener('DOMContentLoaded', function() {
    addChild();
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
