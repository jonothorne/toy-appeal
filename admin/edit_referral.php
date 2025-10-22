<?php
// Include dependencies BEFORE any output
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

// Check authentication (session already started in config.php)
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$currentUser = getRow("SELECT * FROM users WHERE id = ?", "i", [$_SESSION['user_id']]);
$referralId = intval($_GET['id'] ?? 0);

if (!$referralId) {
    header('Location: referrals.php');
    exit;
}

// Get referral and household data
$referral = getReferralWithHousehold($referralId);

if (!$referral) {
    $_SESSION['message'] = "Referral not found.";
    header('Location: referrals.php');
    exit;
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $conn = getDBConnection();
    $conn->begin_transaction();

    try {
        $changes = [];

        // Update household information
        $householdUpdates = [];

        if (trim($_POST['referrer_name']) !== $referral['referrer_name']) {
            $householdUpdates[] = "referrer_name = ?";
            $changes[] = "Referrer name changed from '{$referral['referrer_name']}' to '{$_POST['referrer_name']}'";
        }
        if (trim($_POST['referrer_organisation']) !== $referral['referrer_organisation']) {
            $householdUpdates[] = "referrer_organisation = ?";
            $changes[] = "Organisation changed from '{$referral['referrer_organisation']}' to '{$_POST['referrer_organisation']}'";
        }
        if (trim($_POST['referrer_team'] ?? '') !== ($referral['referrer_team'] ?? '')) {
            $householdUpdates[] = "referrer_team = ?";
            $changes[] = "Team changed";
        }
        if (trim($_POST['secondary_contact'] ?? '') !== ($referral['secondary_contact'] ?? '')) {
            $householdUpdates[] = "secondary_contact = ?";
            $changes[] = "Secondary contact changed";
        }
        if (trim($_POST['referrer_phone']) !== $referral['referrer_phone']) {
            $householdUpdates[] = "referrer_phone = ?";
            $changes[] = "Phone changed";
        }
        if (trim($_POST['referrer_email']) !== $referral['referrer_email']) {
            $householdUpdates[] = "referrer_email = ?";
            $changes[] = "Email changed from '{$referral['referrer_email']}' to '{$_POST['referrer_email']}'";
        }
        if (strtoupper(trim($_POST['postcode'])) !== $referral['postcode']) {
            $householdUpdates[] = "postcode = ?";
            $changes[] = "Postcode changed";
        }
        if ($_POST['duration_known'] !== $referral['duration_known']) {
            $householdUpdates[] = "duration_known = ?";
            $changes[] = "Duration known changed";
        }
        if (trim($_POST['additional_notes'] ?? '') !== ($referral['additional_notes'] ?? '')) {
            $householdUpdates[] = "additional_notes = ?";
            $changes[] = "Additional notes updated";
        }

        // Update household if there are changes
        if (!empty($householdUpdates)) {
            $sql = "UPDATE households SET " . implode(", ", $householdUpdates) . " WHERE id = ?";
            $types = str_repeat("s", count($householdUpdates)) . "i";
            $params = [
                trim($_POST['referrer_name']),
                trim($_POST['referrer_organisation']),
                trim($_POST['referrer_team'] ?? ''),
                trim($_POST['secondary_contact'] ?? ''),
                trim($_POST['referrer_phone']),
                trim($_POST['referrer_email']),
                strtoupper(trim($_POST['postcode'])),
                $_POST['duration_known'],
                trim($_POST['additional_notes'] ?? ''),
                $referral['household_id']
            ];

            // Only include params for fields that changed
            $filteredParams = [];
            $typeArray = str_split($types);
            $paramIndex = 0;

            foreach ($householdUpdates as $update) {
                $filteredParams[] = $params[$paramIndex];
                $paramIndex++;
            }
            $filteredParams[] = $referral['household_id']; // Always add household_id

            $filteredTypes = implode('', array_slice($typeArray, 0, count($filteredParams) - 1)) . 'i';

            updateQuery($sql, $filteredTypes, $filteredParams);
        }

        // Update referral information
        $referralUpdates = [];
        $referralParams = [];
        $referralTypes = "";

        if (strtoupper(trim($_POST['child_initials'])) !== $referral['child_initials']) {
            $referralUpdates[] = "child_initials = ?";
            $referralParams[] = strtoupper(trim($_POST['child_initials']));
            $referralTypes .= "s";
            $changes[] = "Child initials changed from '{$referral['child_initials']}' to '{$_POST['child_initials']}'";
        }
        if (intval($_POST['child_age']) !== intval($referral['child_age'])) {
            $referralUpdates[] = "child_age = ?";
            $referralParams[] = intval($_POST['child_age']);
            $referralTypes .= "i";
            $changes[] = "Age changed from {$referral['child_age']} to {$_POST['child_age']}";
        }
        if (trim($_POST['child_gender']) !== $referral['child_gender']) {
            $referralUpdates[] = "child_gender = ?";
            $referralParams[] = trim($_POST['child_gender']);
            $referralTypes .= "s";
            $changes[] = "Gender changed";
        }
        if (trim($_POST['special_requirements'] ?? '') !== ($referral['special_requirements'] ?? '')) {
            $referralUpdates[] = "special_requirements = ?";
            $referralParams[] = trim($_POST['special_requirements'] ?? '');
            $referralTypes .= "s";
            $changes[] = "Special requirements updated";
        }

        // Update referral if there are changes
        if (!empty($referralUpdates)) {
            $sql = "UPDATE referrals SET " . implode(", ", $referralUpdates) . " WHERE id = ?";
            $referralParams[] = $referralId;
            $referralTypes .= "i";

            updateQuery($sql, $referralTypes, $referralParams);
        }

        // Log all changes
        if (!empty($changes)) {
            foreach ($changes as $change) {
                logActivity($referralId, $currentUser['id'], 'Referral edited', null, $change);
            }
        }

        $conn->commit();

        $_SESSION['message'] = "Referral updated successfully!";
        header("Location: view_referral.php?id={$referralId}");
        exit;

    } catch (Exception $e) {
        $conn->rollback();
        $error = "Failed to update referral: " . $e->getMessage();
        error_log("Edit referral error: " . $e->getMessage());
    }
}

$pageTitle = "Edit Referral - " . $referral['reference_number'];
require_once __DIR__ . '/includes/admin_header.php';
?>

<div class="space-y-6">
    <!-- Back Button -->
    <div>
        <a href="view_referral.php?id=<?php echo $referralId; ?>" class="text-blue-600 hover:text-blue-800 flex items-center">
            <svg class="h-5 w-5 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
            </svg>
            Back to Referral
        </a>
    </div>

    <!-- Error Message -->
    <?php if (isset($error)): ?>
        <div class="bg-red-50 border-l-4 border-red-400 p-4 rounded">
            <p class="text-sm text-red-700"><?php echo e($error); ?></p>
        </div>
    <?php endif; ?>

    <!-- Edit Form -->
    <div class="bg-white rounded-lg shadow p-6">
        <h1 class="text-2xl font-bold text-gray-900 mb-6">Edit Referral: <?php echo e($referral['reference_number']); ?></h1>

        <form method="POST" action="">
            <!-- Household Information -->
            <div class="mb-8">
                <h2 class="text-xl font-semibold text-gray-900 mb-4 pb-2 border-b">Referrer Information</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Referrer Name <span class="text-red-500">*</span></label>
                        <input type="text" name="referrer_name" required
                               value="<?php echo e($referral['referrer_name']); ?>"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Organisation <span class="text-red-500">*</span></label>
                        <input type="text" name="referrer_organisation" required
                               value="<?php echo e($referral['referrer_organisation']); ?>"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Team Name</label>
                        <input type="text" name="referrer_team"
                               value="<?php echo e($referral['referrer_team'] ?? ''); ?>"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Secondary Contact</label>
                        <input type="text" name="secondary_contact"
                               value="<?php echo e($referral['secondary_contact'] ?? ''); ?>"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Phone Number <span class="text-red-500">*</span></label>
                        <input type="tel" name="referrer_phone" required
                               value="<?php echo e($referral['referrer_phone']); ?>"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Email Address <span class="text-red-500">*</span></label>
                        <input type="email" name="referrer_email" required
                               value="<?php echo e($referral['referrer_email']); ?>"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
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
                               value="<?php echo e($referral['postcode']); ?>"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">How long has the family been known? <span class="text-red-500">*</span></label>
                        <select name="duration_known" required
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                            <option value="<1 month" <?php echo $referral['duration_known'] === '<1 month' ? 'selected' : ''; ?>>&lt;1 month</option>
                            <option value="1-6 months" <?php echo $referral['duration_known'] === '1-6 months' ? 'selected' : ''; ?>>1-6 months</option>
                            <option value="6-12 months" <?php echo $referral['duration_known'] === '6-12 months' ? 'selected' : ''; ?>>6-12 months</option>
                            <option value="1-2 years" <?php echo $referral['duration_known'] === '1-2 years' ? 'selected' : ''; ?>>1-2 years</option>
                            <option value="2+ years" <?php echo $referral['duration_known'] === '2+ years' ? 'selected' : ''; ?>>2+ years</option>
                        </select>
                    </div>
                </div>
                <div class="mt-6">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Additional Notes</label>
                    <textarea name="additional_notes" rows="3"
                              class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"><?php echo e($referral['additional_notes'] ?? ''); ?></textarea>
                </div>
            </div>

            <!-- Child Information -->
            <div class="mb-8">
                <h2 class="text-xl font-semibold text-gray-900 mb-4 pb-2 border-b">Child Information</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Child Initials <span class="text-red-500">*</span></label>
                        <input type="text" name="child_initials" required maxlength="10"
                               value="<?php echo e($referral['child_initials']); ?>"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Age <span class="text-red-500">*</span></label>
                        <input type="number" name="child_age" required min="0" max="18"
                               value="<?php echo e($referral['child_age']); ?>"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Gender <span class="text-red-500">*</span></label>
                        <select name="child_gender" required
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                            <option value="Male" <?php echo $referral['child_gender'] === 'Male' ? 'selected' : ''; ?>>Male</option>
                            <option value="Female" <?php echo $referral['child_gender'] === 'Female' ? 'selected' : ''; ?>>Female</option>
                            <option value="Other" <?php echo $referral['child_gender'] === 'Other' ? 'selected' : ''; ?>>Other</option>
                            <option value="Prefer not to say" <?php echo $referral['child_gender'] === 'Prefer not to say' ? 'selected' : ''; ?>>Prefer not to say</option>
                        </select>
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Special Requirements</label>
                        <textarea name="special_requirements" rows="3"
                                  class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                  placeholder="e.g., learning difficulties, sensory needs, disabilities"><?php echo e($referral['special_requirements'] ?? ''); ?></textarea>
                    </div>
                </div>
            </div>

            <!-- Audit Note -->
            <div class="bg-blue-50 border-l-4 border-blue-400 p-4 rounded mb-6">
                <p class="text-sm text-blue-700">
                    <strong>Note:</strong> All changes will be logged in the activity log for audit purposes.
                </p>
            </div>

            <!-- Submit Buttons -->
            <div class="flex justify-end space-x-4">
                <a href="view_referral.php?id=<?php echo $referralId; ?>"
                   class="px-6 py-3 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition">
                    Cancel
                </a>
                <button type="submit"
                        class="px-6 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition font-medium">
                    Save Changes
                </button>
            </div>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/includes/admin_footer.php'; ?>
