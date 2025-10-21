<?php
$pageTitle = "View Referral";
require_once __DIR__ . '/includes/admin_header.php';
require_once __DIR__ . '/../includes/email.php';

$referralId = intval($_GET['id'] ?? 0);
$message = '';
$error = '';

if (!$referralId) {
    header('Location: referrals.php');
    exit;
}

// Handle status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];

    switch ($action) {
        case 'update_status':
            $newStatus = $_POST['status'] ?? '';
            $zoneId = !empty($_POST['zone_id']) ? intval($_POST['zone_id']) : null;

            if (updateReferralStatus($referralId, $newStatus, $currentUser['id'], $zoneId)) {
                $message = "Status updated successfully!";
            } else {
                $error = "Failed to update status.";
            }
            break;

        case 'add_note':
            $note = trim($_POST['note'] ?? '');
            if (!empty($note)) {
                updateQuery(
                    "UPDATE referrals SET notes = CONCAT(COALESCE(notes, ''), ?, '\n---\n') WHERE id = ?",
                    "si",
                    ["[" . date('Y-m-d H:i:s') . "] " . $currentUser['full_name'] . ": " . $note, $referralId]
                );
                $message = "Note added successfully!";
            }
            break;
    }
}

// Get referral details
$referral = getReferralWithHousehold($referralId);

if (!$referral) {
    header('Location: referrals.php');
    exit;
}

// Get siblings
$siblings = getSiblings($referral['household_id'], $referralId);

// Get activity log
$activityLog = getRows(
    "SELECT al.*, u.full_name
     FROM activity_log al
     LEFT JOIN users u ON al.user_id = u.id
     WHERE al.referral_id = ?
     ORDER BY al.created_at DESC
     LIMIT 20",
    "i",
    [$referralId]
);

// Get zones for dropdown with allocation counts
$zones = getRows("SELECT * FROM zones WHERE is_active = 1 ORDER BY zone_name");
$zoneAllocations = [];
foreach ($zones as $zone) {
    $count = getRow(
        "SELECT COUNT(*) as count FROM referrals WHERE zone_id = ? AND status != 'collected'",
        "i",
        [$zone['id']]
    );
    $zoneAllocations[$zone['id']] = $count['count'];
}
?>

<div class="space-y-6">
    <!-- Back Button -->
    <div>
        <a href="referrals.php" class="text-blue-600 hover:text-blue-800 flex items-center">
            <svg class="h-5 w-5 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
            </svg>
            Back to Referrals
        </a>
    </div>

    <!-- Messages -->
    <?php if ($message): ?>
        <div class="bg-green-50 border-l-4 border-green-400 p-4 rounded">
            <p class="text-sm text-green-700"><?php echo e($message); ?></p>
        </div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div class="bg-red-50 border-l-4 border-red-400 p-4 rounded">
            <p class="text-sm text-red-700"><?php echo e($error); ?></p>
        </div>
    <?php endif; ?>

    <!-- Header -->
    <div class="bg-white rounded-lg shadow p-6">
        <div class="flex justify-between items-start">
            <div>
                <h1 class="text-3xl font-bold text-gray-900"><?php echo e($referral['reference_number']); ?></h1>
                <p class="mt-1 text-sm text-gray-600">Created: <?php echo formatDate($referral['referral_created']); ?></p>
            </div>
            <div class="text-right">
                <?php echo getStatusBadge($referral['status']); ?>
                <?php if ($referral['zone_name']): ?>
                    <p class="mt-2 text-sm text-gray-600">Zone: <strong><?php echo e($referral['zone_name']); ?></strong></p>
                <?php endif; ?>
                <?php if ($referral['label_printed']): ?>
                    <p class="mt-2 text-xs text-green-600">
                        ✓ Label Printed
                        <?php if ($referral['label_printed_at']): ?>
                            <span class="text-gray-500">(<?php echo formatDate($referral['label_printed_at'], 'd/m/Y'); ?>)</span>
                        <?php endif; ?>
                    </p>
                <?php else: ?>
                    <p class="mt-2 text-xs text-orange-600">⚠ Label Not Printed</p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Warehouse Zone Information (for volunteers) -->
    <?php if (!empty($referral['zone_name']) || !empty($referral['zone_description'])): ?>
        <div class="bg-purple-50 border-l-4 border-purple-500 rounded-lg shadow p-4 sm:p-6">
            <div class="flex items-start">
                <div class="flex-shrink-0">
                    <svg class="h-6 w-6 text-purple-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                    </svg>
                </div>
                <div class="ml-3 flex-1">
                    <h3 class="text-lg font-semibold text-purple-900 mb-2">📦 Warehouse Location for Volunteers</h3>
                    <?php if (!empty($referral['zone_name'])): ?>
                        <p class="text-sm text-purple-800">
                            <span class="font-semibold">Zone:</span> <?php echo e($referral['zone_name']); ?>
                        </p>
                    <?php endif; ?>
                    <?php if (!empty($referral['zone_location'])): ?>
                        <p class="text-sm text-purple-800 mt-1">
                            <span class="font-semibold">General Location:</span> <?php echo e($referral['zone_location']); ?>
                        </p>
                    <?php endif; ?>
                    <?php if (!empty($referral['zone_description'])): ?>
                        <div class="mt-2 p-3 bg-purple-100 rounded border border-purple-200">
                            <p class="text-sm font-semibold text-purple-900 mb-1">🔍 How to find it:</p>
                            <p class="text-sm text-purple-800"><?php echo nl2br(e($referral['zone_description'])); ?></p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Main Content -->
        <div class="lg:col-span-2 space-y-6">
            <!-- Child Information -->
            <div class="bg-white rounded-lg shadow p-4 sm:p-6">
                <h2 class="text-xl font-semibold text-gray-900 mb-4 pb-2 border-b">Child Information</h2>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <p class="text-sm text-gray-600">Initials</p>
                        <p class="font-medium text-gray-900"><?php echo e($referral['child_initials']); ?></p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-600">Age</p>
                        <p class="font-medium text-gray-900"><?php echo e($referral['child_age']); ?> years</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-600">Gender</p>
                        <p class="font-medium text-gray-900"><?php echo e($referral['child_gender']); ?></p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-600">Postcode</p>
                        <p class="font-medium text-gray-900"><?php echo e($referral['postcode']); ?></p>
                    </div>
                </div>
                <?php if ($referral['special_requirements']): ?>
                    <div class="mt-4 p-4 bg-yellow-50 rounded-lg border border-yellow-200">
                        <p class="text-sm font-medium text-yellow-800 mb-1">Special Requirements</p>
                        <p class="text-sm text-yellow-700"><?php echo nl2br(e($referral['special_requirements'])); ?></p>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Referrer Information -->
            <div class="bg-white rounded-lg shadow p-4 sm:p-6">
                <h2 class="text-xl font-semibold text-gray-900 mb-4 pb-2 border-b">Referrer Information</h2>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <p class="text-sm text-gray-600">Name</p>
                        <p class="font-medium text-gray-900"><?php echo e($referral['referrer_name']); ?></p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-600">Organisation</p>
                        <p class="font-medium text-gray-900"><?php echo e($referral['referrer_organisation']); ?></p>
                    </div>
                    <?php if ($referral['referrer_team']): ?>
                        <div>
                            <p class="text-sm text-gray-600">Team</p>
                            <p class="font-medium text-gray-900"><?php echo e($referral['referrer_team']); ?></p>
                        </div>
                    <?php endif; ?>
                    <?php if ($referral['secondary_contact']): ?>
                        <div>
                            <p class="text-sm text-gray-600">Secondary Contact</p>
                            <p class="font-medium text-gray-900"><?php echo e($referral['secondary_contact']); ?></p>
                        </div>
                    <?php endif; ?>
                    <div>
                        <p class="text-sm text-gray-600">Phone</p>
                        <p class="font-medium text-gray-900"><?php echo e($referral['referrer_phone']); ?></p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-600">Email</p>
                        <p class="font-medium text-gray-900">
                            <a href="mailto:<?php echo e($referral['referrer_email']); ?>" class="text-blue-600 hover:underline">
                                <?php echo e($referral['referrer_email']); ?>
                            </a>
                        </p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-600">Duration Known</p>
                        <p class="font-medium text-gray-900"><?php echo e($referral['duration_known']); ?></p>
                    </div>
                </div>
                <?php if ($referral['additional_notes']): ?>
                    <div class="mt-4">
                        <p class="text-sm text-gray-600 mb-1">Additional Notes</p>
                        <p class="text-sm text-gray-900"><?php echo nl2br(e($referral['additional_notes'])); ?></p>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Siblings in Same Household -->
            <?php if (!empty($siblings)): ?>
                <div class="bg-white rounded-lg shadow p-4 sm:p-6">
                    <h2 class="text-xl font-semibold text-gray-900 mb-4 pb-2 border-b">Siblings in Household</h2>
                    <div class="space-y-3">
                        <?php foreach ($siblings as $sibling): ?>
                            <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                                <div>
                                    <p class="font-medium text-gray-900"><?php echo e($sibling['reference_number']); ?></p>
                                    <p class="text-sm text-gray-600">
                                        <?php echo e($sibling['child_initials']); ?> -
                                        <?php echo e($sibling['child_age']); ?> years,
                                        <?php echo e($sibling['child_gender']); ?>
                                    </p>
                                </div>
                                <div class="flex items-center space-x-3">
                                    <?php echo getStatusBadge($sibling['status']); ?>
                                    <a href="view_referral.php?id=<?php echo $sibling['id']; ?>"
                                       class="text-blue-600 hover:text-blue-800 text-sm font-medium">
                                        View
                                    </a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Activity Log -->
            <?php if (!empty($activityLog)): ?>
                <div class="bg-white rounded-lg shadow p-4 sm:p-6">
                    <h2 class="text-xl font-semibold text-gray-900 mb-4 pb-2 border-b">Activity Log</h2>
                    <div class="space-y-3">
                        <?php foreach ($activityLog as $log): ?>
                            <div class="flex text-sm">
                                <div class="flex-shrink-0 w-32 text-gray-500">
                                    <?php echo formatDate($log['created_at'], 'd/m/Y H:i'); ?>
                                </div>
                                <div class="flex-1">
                                    <span class="font-medium text-gray-900"><?php echo e($log['full_name'] ?? 'System'); ?></span>
                                    <span class="text-gray-600"> - <?php echo e($log['action']); ?></span>
                                    <?php if ($log['old_value'] && $log['new_value']): ?>
                                        <span class="text-gray-500">
                                            (<?php echo e($log['old_value']); ?> → <?php echo e($log['new_value']); ?>)
                                        </span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Internal Notes -->
            <div class="bg-white rounded-lg shadow p-4 sm:p-6">
                <h2 class="text-xl font-semibold text-gray-900 mb-4 pb-2 border-b">Internal Notes</h2>
                <?php if ($referral['notes']): ?>
                    <div class="mb-4 p-4 bg-gray-50 rounded-lg whitespace-pre-wrap text-sm text-gray-700">
                        <?php echo nl2br(e($referral['notes'])); ?>
                    </div>
                <?php else: ?>
                    <p class="mb-4 text-sm text-gray-500 italic">No notes yet</p>
                <?php endif; ?>

                <form method="POST" action="">
                    <input type="hidden" name="action" value="add_note">
                    <textarea name="note" rows="3" required
                              placeholder="Add a note..."
                              class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"></textarea>
                    <button type="submit"
                            class="mt-2 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition">
                        Add Note
                    </button>
                </form>
            </div>
        </div>

        <!-- Sidebar -->
        <div class="space-y-6">
            <!-- Status Management -->
            <div class="bg-white rounded-lg shadow p-4 sm:p-6">
                <h2 class="text-lg font-semibold text-gray-900 mb-4">Update Status</h2>
                <form method="POST" action="">
                    <input type="hidden" name="action" value="update_status">

                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">New Status</label>
                        <select name="status" required
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                            <option value="pending" <?php echo $referral['status'] == 'pending' ? 'selected' : ''; ?>>Pending</option>
                            <option value="fulfilled" <?php echo $referral['status'] == 'fulfilled' ? 'selected' : ''; ?>>Fulfilled</option>
                            <option value="located" <?php echo $referral['status'] == 'located' ? 'selected' : ''; ?>>Located</option>
                            <option value="ready_for_collection" <?php echo $referral['status'] == 'ready_for_collection' ? 'selected' : ''; ?>>Ready for Collection</option>
                            <option value="collected" <?php echo $referral['status'] == 'collected' ? 'selected' : ''; ?>>Collected</option>
                        </select>
                    </div>

                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Warehouse Zone</label>
                        <select name="zone_id"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                            <option value="">Not Assigned</option>
                            <?php foreach ($zones as $zone):
                                $allocation = $zoneAllocations[$zone['id']];
                                $percentage = ($allocation / 30) * 100;
                                $capacityText = " ($allocation/30)";

                                if ($percentage >= 100) {
                                    $capacityText .= " ⚠ FULL";
                                } elseif ($percentage >= 80) {
                                    $capacityText .= " ⚠";
                                }
                            ?>
                                <option value="<?php echo $zone['id']; ?>" <?php echo $referral['zone_id'] == $zone['id'] ? 'selected' : ''; ?>>
                                    <?php echo e($zone['zone_name']); ?><?php echo $capacityText; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <p class="text-xs text-gray-500 mt-1">Recommended capacity: 30 referrals per zone</p>
                    </div>

                    <button type="submit"
                            class="w-full px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition font-medium">
                        Update Status
                    </button>
                </form>

                <div class="mt-4 p-3 bg-blue-50 rounded-lg text-xs text-blue-700">
                    <p class="font-medium mb-1">Status Guide:</p>
                    <ul class="space-y-1">
                        <li><strong>Pending:</strong> Just received</li>
                        <li><strong>Fulfilled:</strong> Parcel prepared</li>
                        <li><strong>Located:</strong> Assigned to zone</li>
                        <li><strong>Ready:</strong> Email sent to referrer</li>
                        <li><strong>Collected:</strong> Picked up</li>
                    </ul>
                </div>
            </div>

            <!-- Timeline -->
            <div class="bg-white rounded-lg shadow p-4 sm:p-6">
                <h2 class="text-lg font-semibold text-gray-900 mb-4">Timeline</h2>
                <div class="space-y-4">
                    <div class="flex items-start">
                        <div class="flex-shrink-0 w-2 h-2 mt-1 bg-green-500 rounded-full"></div>
                        <div class="ml-3 flex-1">
                            <p class="text-xs font-medium text-gray-900">Created</p>
                            <p class="text-xs text-gray-500"><?php echo formatDate($referral['referral_created']); ?></p>
                        </div>
                    </div>

                    <?php if ($referral['fulfilled_at']): ?>
                        <div class="flex items-start">
                            <div class="flex-shrink-0 w-2 h-2 mt-1 bg-green-500 rounded-full"></div>
                            <div class="ml-3 flex-1">
                                <p class="text-xs font-medium text-gray-900">Fulfilled</p>
                                <p class="text-xs text-gray-500"><?php echo formatDate($referral['fulfilled_at']); ?></p>
                                <?php if ($referral['fulfilled_by_name']): ?>
                                    <p class="text-xs text-gray-400">by <?php echo e($referral['fulfilled_by_name']); ?></p>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endif; ?>

                    <?php if ($referral['located_at']): ?>
                        <div class="flex items-start">
                            <div class="flex-shrink-0 w-2 h-2 mt-1 bg-green-500 rounded-full"></div>
                            <div class="ml-3 flex-1">
                                <p class="text-xs font-medium text-gray-900">Located</p>
                                <p class="text-xs text-gray-500"><?php echo formatDate($referral['located_at']); ?></p>
                            </div>
                        </div>
                    <?php endif; ?>

                    <?php if ($referral['ready_at']): ?>
                        <div class="flex items-start">
                            <div class="flex-shrink-0 w-2 h-2 mt-1 bg-green-500 rounded-full"></div>
                            <div class="ml-3 flex-1">
                                <p class="text-xs font-medium text-gray-900">Ready for Collection</p>
                                <p class="text-xs text-gray-500"><?php echo formatDate($referral['ready_at']); ?></p>
                            </div>
                        </div>
                    <?php endif; ?>

                    <?php if ($referral['collected_at']): ?>
                        <div class="flex items-start">
                            <div class="flex-shrink-0 w-2 h-2 mt-1 bg-green-500 rounded-full"></div>
                            <div class="ml-3 flex-1">
                                <p class="text-xs font-medium text-gray-900">Collected</p>
                                <p class="text-xs text-gray-500"><?php echo formatDate($referral['collected_at']); ?></p>
                                <?php if ($referral['collected_by_name']): ?>
                                    <p class="text-xs text-gray-400">by <?php echo e($referral['collected_by_name']); ?></p>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="bg-white rounded-lg shadow p-4 sm:p-6">
                <h2 class="text-lg font-semibold text-gray-900 mb-4">Quick Actions</h2>
                <div class="space-y-2">
                    <a href="labels.php?ids=<?php echo $referralId; ?>" target="_blank"
                       class="block w-full px-4 py-2 bg-purple-600 text-white text-center rounded-lg hover:bg-purple-700 transition text-sm">
                        Print Label
                    </a>
                    <?php if (!empty($siblings)): ?>
                        <a href="labels.php?household=<?php echo $referral['household_id']; ?>" target="_blank"
                           class="block w-full px-4 py-2 bg-purple-600 text-white text-center rounded-lg hover:bg-purple-700 transition text-sm">
                            Print All Household Labels
                        </a>
                    <?php endif; ?>
                    <a href="mailto:<?php echo e($referral['referrer_email']); ?>"
                       class="block w-full px-4 py-2 border border-gray-300 text-gray-700 text-center rounded-lg hover:bg-gray-50 transition text-sm">
                        Email Referrer
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/admin_footer.php'; ?>
