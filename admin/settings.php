<?php
$pageTitle = "Settings";
require_once __DIR__ . '/includes/admin_header.php';

$message = '';
$error = '';
$activeTab = $_GET['tab'] ?? 'general';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    switch ($action) {
        case 'update_general':
            updateSetting('site_name', $_POST['site_name'] ?? '');
            updateSetting('enable_referrals', $_POST['enable_referrals'] ?? '0');
            updateSetting('collection_location', $_POST['collection_location'] ?? '');
            updateSetting('collection_hours', $_POST['collection_hours'] ?? '');
            updateSetting('current_year', $_POST['current_year'] ?? date('Y'));
            updateSetting('collection_reminders_enabled', $_POST['collection_reminders_enabled'] ?? '0');
            updateSetting('collection_reminder_days', $_POST['collection_reminder_days'] ?? '3');
            $message = "General settings updated successfully!";
            break;

        case 'update_email':
            updateSetting('smtp_host', $_POST['smtp_host'] ?? '');
            updateSetting('smtp_port', $_POST['smtp_port'] ?? '');
            updateSetting('smtp_username', $_POST['smtp_username'] ?? '');
            if (!empty($_POST['smtp_password'])) {
                updateSetting('smtp_password', $_POST['smtp_password']);
            }
            updateSetting('smtp_from_email', $_POST['smtp_from_email'] ?? '');
            updateSetting('smtp_from_name', $_POST['smtp_from_name'] ?? '');
            $message = "Email settings updated successfully!";
            break;

        case 'add_user':
            $username = trim($_POST['username'] ?? '');
            $password = $_POST['password'] ?? '';
            $fullName = trim($_POST['full_name'] ?? '');
            $email = trim($_POST['email'] ?? '');

            if (empty($username) || empty($password) || empty($fullName) || empty($email)) {
                $error = "All fields are required for new user.";
            } else {
                $userId = createUser($username, $password, $fullName, $email);
                if ($userId) {
                    $message = "User created successfully!";
                } else {
                    $error = "Failed to create user. Username may already exist.";
                }
            }
            break;

        case 'update_user':
            $userId = intval($_POST['user_id'] ?? 0);
            $fullName = trim($_POST['full_name'] ?? '');
            $email = trim($_POST['email'] ?? '');
            $newPassword = !empty($_POST['new_password']) ? $_POST['new_password'] : null;

            if (updateUser($userId, $fullName, $email, $newPassword)) {
                $message = "User updated successfully!";
            } else {
                $error = "Failed to update user.";
            }
            break;

        case 'delete_user':
            $userId = intval($_POST['user_id'] ?? 0);
            if ($userId == $currentUser['id']) {
                $error = "You cannot delete your own account.";
            } elseif (deleteUser($userId)) {
                $message = "User deactivated successfully!";
            } else {
                $error = "Failed to delete user. Cannot delete the last admin.";
            }
            break;

        case 'add_zone':
            $zoneName = trim($_POST['zone_name'] ?? '');
            $location = trim($_POST['location'] ?? '');
            $description = trim($_POST['description'] ?? '');

            if (empty($zoneName)) {
                $error = "Zone name is required.";
            } else {
                $zoneId = insertQuery(
                    "INSERT INTO zones (zone_name, location, description) VALUES (?, ?, ?)",
                    "sss",
                    [$zoneName, $location, $description]
                );
                if ($zoneId) {
                    $message = "Zone created successfully!";
                } else {
                    $error = "Failed to create zone.";
                }
            }
            break;

        case 'update_zone':
            $zoneId = intval($_POST['zone_id'] ?? 0);
            $zoneName = trim($_POST['zone_name'] ?? '');
            $location = trim($_POST['location'] ?? '');
            $description = trim($_POST['description'] ?? '');
            $isActive = isset($_POST['is_active']) ? 1 : 0;

            if (updateQuery(
                "UPDATE zones SET zone_name = ?, location = ?, description = ?, is_active = ? WHERE id = ?",
                "sssii",
                [$zoneName, $location, $description, $isActive, $zoneId]
            ) !== false) {
                $message = "Zone updated successfully!";
            } else {
                $error = "Failed to update zone.";
            }
            break;

        case 'delete_zone':
            $zoneId = intval($_POST['zone_id'] ?? 0);
            if (updateQuery("UPDATE zones SET is_active = 0 WHERE id = ?", "i", [$zoneId])) {
                $message = "Zone deactivated successfully!";
            } else {
                $error = "Failed to deactivate zone.";
            }
            break;

        case 'reactivate_zone':
            $zoneId = intval($_POST['zone_id'] ?? 0);
            if (updateQuery("UPDATE zones SET is_active = 1 WHERE id = ?", "i", [$zoneId])) {
                $message = "Zone reactivated successfully!";
            } else {
                $error = "Failed to reactivate zone.";
            }
            break;

        case 'delete_zone_permanent':
            $zoneId = intval($_POST['zone_id'] ?? 0);

            // Check if any referrals are using this zone
            $referralCount = getRow(
                "SELECT COUNT(*) as count FROM referrals WHERE zone_id = ?",
                "i",
                [$zoneId]
            );

            if ($referralCount['count'] > 0) {
                $error = "Cannot delete zone: {$referralCount['count']} referral(s) are currently assigned to this zone. Deactivate the zone instead.";
            } else {
                if (updateQuery("DELETE FROM zones WHERE id = ?", "i", [$zoneId])) {
                    $message = "Zone permanently deleted!";
                } else {
                    $error = "Failed to delete zone.";
                }
            }
            break;
    }
}

// Get all users
$users = getRows("SELECT * FROM users ORDER BY created_at DESC");

// Get all zones
$zones = getRows("SELECT * FROM zones ORDER BY zone_name");

// Get settings
$settings = [];
$settingsRows = getRows("SELECT * FROM settings");
foreach ($settingsRows as $row) {
    $settings[$row['setting_key']] = $row['setting_value'];
}
?>

<div class="space-y-6">
    <!-- Page Header -->
    <div>
        <h1 class="text-3xl font-bold text-gray-900">Settings</h1>
        <p class="mt-2 text-sm text-gray-600">Manage system configuration, users, and warehouse zones</p>
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

    <!-- Tabs -->
    <div class="border-b border-gray-200 overflow-x-auto">
        <nav class="-mb-px flex space-x-4 sm:space-x-8 px-1">
            <a href="?tab=general"
               class="<?php echo $activeTab == 'general' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'; ?> whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">
                General
            </a>
            <a href="?tab=email"
               class="<?php echo $activeTab == 'email' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'; ?> whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">
                Email
            </a>
            <a href="?tab=users"
               class="<?php echo $activeTab == 'users' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'; ?> whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">
                Users
            </a>
            <a href="?tab=zones"
               class="<?php echo $activeTab == 'zones' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'; ?> whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">
                Zones
            </a>
        </nav>
    </div>

    <!-- General Settings Tab -->
    <?php if ($activeTab == 'general'): ?>
        <div class="bg-white rounded-lg shadow p-4 sm:p-6">
            <h2 class="text-xl font-semibold text-gray-900 mb-6">General Settings</h2>
            <form method="POST" action="">
                <input type="hidden" name="action" value="update_general">
                <div class="space-y-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Site Name</label>
                        <input type="text" name="site_name" value="<?php echo e($settings['site_name'] ?? ''); ?>"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Current Year</label>
                        <input type="number" name="current_year" value="<?php echo e($settings['current_year'] ?? date('Y')); ?>"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        <p class="text-xs text-gray-500 mt-1">Used for generating reference numbers</p>
                    </div>

                    <div>
                        <label class="flex items-center">
                            <input type="checkbox" name="enable_referrals" value="1"
                                   <?php echo ($settings['enable_referrals'] ?? '1') == '1' ? 'checked' : ''; ?>
                                   class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                            <span class="ml-2 text-sm text-gray-700">Enable Referral Form</span>
                        </label>
                        <p class="text-xs text-gray-500 mt-1 ml-6">Uncheck to temporarily disable new referrals</p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Collection Location</label>
                        <input type="text" name="collection_location" value="<?php echo e($settings['collection_location'] ?? ''); ?>"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Collection Hours</label>
                        <input type="text" name="collection_hours" value="<?php echo e($settings['collection_hours'] ?? ''); ?>"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    </div>

                    <div class="border-t border-gray-200 pt-6">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">Collection Reminders</h3>

                        <div class="space-y-4">
                            <div>
                                <label class="flex items-center">
                                    <input type="checkbox" name="collection_reminders_enabled" value="1"
                                           <?php echo ($settings['collection_reminders_enabled'] ?? '1') == '1' ? 'checked' : ''; ?>
                                           class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
                                           id="collection_reminders_toggle">
                                    <span class="ml-2 text-sm text-gray-700">Enable Collection Reminders</span>
                                </label>
                                <p class="text-xs text-gray-500 mt-1 ml-6">Send automatic reminder emails for uncollected parcels</p>
                            </div>

                            <div id="reminder_days_field">
                                <label class="block text-sm font-medium text-gray-700 mb-2">Send Reminder After (Days)</label>
                                <input type="number" name="collection_reminder_days"
                                       value="<?php echo e($settings['collection_reminder_days'] ?? '3'); ?>"
                                       min="1" max="30"
                                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                                <p class="text-xs text-gray-500 mt-1">Number of days after parcel is ready for collection before sending reminder</p>
                            </div>

                            <div class="bg-blue-50 border-l-4 border-blue-400 p-4 rounded">
                                <p class="text-sm text-blue-700">
                                    <strong>How it works:</strong> A cron job will check daily for parcels that have been ready for collection
                                    for the specified number of days and send reminder emails automatically. Each parcel will only receive one reminder.
                                </p>
                            </div>
                        </div>
                    </div>

                    <button type="submit"
                            class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition">
                        Save Changes
                    </button>
                </div>
            </form>
        </div>
    <?php endif; ?>

    <!-- Email Settings Tab -->
    <?php if ($activeTab == 'email'): ?>
        <div class="bg-white rounded-lg shadow p-4 sm:p-6">
            <h2 class="text-xl font-semibold text-gray-900 mb-6">Email Settings</h2>
            <form method="POST" action="">
                <input type="hidden" name="action" value="update_email">
                <div class="space-y-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">SMTP Host</label>
                            <input type="text" name="smtp_host" value="<?php echo e($settings['smtp_host'] ?? ''); ?>"
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">SMTP Port</label>
                            <input type="number" name="smtp_port" value="<?php echo e($settings['smtp_port'] ?? ''); ?>"
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">SMTP Username</label>
                            <input type="text" name="smtp_username" value="<?php echo e($settings['smtp_username'] ?? ''); ?>"
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">SMTP Password</label>
                            <input type="password" name="smtp_password" placeholder="Leave blank to keep current"
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">From Email</label>
                            <input type="email" name="smtp_from_email" value="<?php echo e($settings['smtp_from_email'] ?? ''); ?>"
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">From Name</label>
                            <input type="text" name="smtp_from_name" value="<?php echo e($settings['smtp_from_name'] ?? ''); ?>"
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        </div>
                    </div>

                    <div class="bg-yellow-50 border-l-4 border-yellow-400 p-4 rounded">
                        <p class="text-sm text-yellow-700">
                            <strong>Note:</strong> The system uses PHP's mail() function. Configure your server's SMTP settings in php.ini for production use.
                        </p>
                    </div>

                    <button type="submit"
                            class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition">
                        Save Changes
                    </button>
                </div>
            </form>
        </div>
    <?php endif; ?>

    <!-- Users Tab -->
    <?php if ($activeTab == 'users'): ?>
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Add New User -->
            <div class="bg-white rounded-lg shadow p-4 sm:p-6">
                <h2 class="text-xl font-semibold text-gray-900 mb-6">Add New User</h2>
                <form method="POST" action="">
                    <input type="hidden" name="action" value="add_user">
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Username</label>
                            <input type="text" name="username" required
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Full Name</label>
                            <input type="text" name="full_name" required
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Email</label>
                            <input type="email" name="email" required
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Password</label>
                            <input type="password" name="password" required
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        </div>
                        <button type="submit"
                                class="w-full px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition">
                            Add User
                        </button>
                    </div>
                </form>
            </div>

            <!-- Existing Users -->
            <div class="bg-white rounded-lg shadow p-4 sm:p-6">
                <h2 class="text-xl font-semibold text-gray-900 mb-6">Existing Users</h2>
                <div class="space-y-4">
                    <?php foreach ($users as $user): ?>
                        <div class="border border-gray-200 rounded-lg p-4 <?php echo !$user['is_active'] ? 'bg-gray-50' : ''; ?>">
                            <div class="flex justify-between items-start mb-2">
                                <div>
                                    <p class="font-medium text-gray-900"><?php echo e($user['full_name']); ?></p>
                                    <p class="text-sm text-gray-600">@<?php echo e($user['username']); ?></p>
                                    <p class="text-xs text-gray-500"><?php echo e($user['email']); ?></p>
                                </div>
                                <?php if (!$user['is_active']): ?>
                                    <span class="px-2 py-1 text-xs bg-red-200 text-red-800 rounded">Inactive</span>
                                <?php endif; ?>
                            </div>
                            <div class="text-xs text-gray-500 mb-3">
                                Last login: <?php echo $user['last_login'] ? formatDate($user['last_login']) : 'Never'; ?>
                            </div>
                            <?php if ($user['is_active'] && $user['id'] != $currentUser['id']): ?>
                                <form method="POST" action="" onsubmit="return confirm('Are you sure you want to deactivate this user?');">
                                    <input type="hidden" name="action" value="delete_user">
                                    <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                    <button type="submit"
                                            class="text-red-600 hover:text-red-800 text-sm font-medium">
                                        Deactivate
                                    </button>
                                </form>
                            <?php elseif ($user['id'] == $currentUser['id']): ?>
                                <p class="text-xs text-gray-500 italic">Current user</p>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <!-- Zones Tab -->
    <?php if ($activeTab == 'zones'): ?>
        <?php
        // Get allocation counts for each zone
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
            <!-- Info Box -->
            <div class="bg-blue-50 border-l-4 border-blue-400 p-4 rounded">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-blue-400" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
                        </svg>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm text-blue-700">
                            <strong>Note:</strong> Zone allocations only count active referrals. When you mark a referral as "Collected", it automatically frees up that zone space for new referrals while keeping the record for your archives.
                        </p>
                    </div>
                </div>
            </div>

            <!-- Zone Allocation Overview -->
            <div class="bg-white rounded-lg shadow p-4 sm:p-6">
                <h2 class="text-xl font-semibold text-gray-900 mb-6">Zone Allocation Overview</h2>
                <p class="text-sm text-gray-600 mb-4">Recommended capacity: 30 referrals per zone (excluding collected)</p>

                <div class="space-y-4">
                    <?php foreach ($zones as $zone):
                        if (!$zone['is_active']) continue;
                        $allocation = $zoneAllocations[$zone['id']];
                        $percentage = ($allocation / 30) * 100;

                        // Determine color based on allocation
                        if ($percentage >= 100) {
                            $barColor = 'bg-red-500';
                            $textColor = 'text-red-700';
                            $bgColor = 'bg-red-50';
                        } elseif ($percentage >= 80) {
                            $barColor = 'bg-yellow-500';
                            $textColor = 'text-yellow-700';
                            $bgColor = 'bg-yellow-50';
                        } else {
                            $barColor = 'bg-green-500';
                            $textColor = 'text-green-700';
                            $bgColor = 'bg-green-50';
                        }
                    ?>
                        <div class="border border-gray-200 rounded-lg p-4 <?php echo $bgColor; ?>">
                            <div class="flex justify-between items-center mb-2">
                                <div>
                                    <h3 class="font-semibold text-gray-900"><?php echo e($zone['zone_name']); ?></h3>
                                    <?php if ($zone['description']): ?>
                                        <p class="text-xs text-gray-600"><?php echo e($zone['description']); ?></p>
                                    <?php endif; ?>
                                </div>
                                <div class="text-right">
                                    <p class="text-2xl font-bold <?php echo $textColor; ?>">
                                        <?php echo number_format($percentage, 0); ?>%
                                    </p>
                                    <p class="text-xs text-gray-600">
                                        <?php echo $allocation; ?> / 30 referrals
                                    </p>
                                </div>
                            </div>

                            <!-- Progress Bar -->
                            <div class="w-full bg-gray-200 rounded-full h-3 mt-3">
                                <div class="<?php echo $barColor; ?> h-3 rounded-full transition-all"
                                     style="width: <?php echo min($percentage, 100); ?>%"></div>
                            </div>

                            <div class="mt-3 flex justify-between items-center">
                                <a href="referrals.php?zone=<?php echo $zone['id']; ?>"
                                   class="text-sm text-blue-600 hover:text-blue-800 font-medium">
                                    View Referrals in this Zone →
                                </a>
                                <?php if ($percentage >= 100): ?>
                                    <span class="text-xs font-semibold text-red-600">⚠ Over Capacity</span>
                                <?php elseif ($percentage >= 80): ?>
                                    <span class="text-xs font-semibold text-yellow-600">⚠ Nearly Full</span>
                                <?php else: ?>
                                    <span class="text-xs font-semibold text-green-600">✓ Available</span>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- Add New Zone -->
                <div class="bg-white rounded-lg shadow p-4 sm:p-6">
                    <h2 class="text-xl font-semibold text-gray-900 mb-6">Add New Zone</h2>
                    <form method="POST" action="">
                        <input type="hidden" name="action" value="add_zone">
                        <div class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Zone Name <span class="text-red-500">*</span></label>
                                <input type="text" name="zone_name" required
                                       placeholder="e.g., Zone A, Shelf 1, Area B"
                                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                                <p class="text-xs text-gray-500 mt-1">Internal reference for warehouse organization</p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Location (shown to referrers)</label>
                                <input type="text" name="location"
                                       placeholder="e.g., Row 3, Shelf 2 / Near main entrance"
                                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                                <p class="text-xs text-gray-500 mt-1">This is shown in collection emails to help referrers find parcels</p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Description (internal only)</label>
                                <textarea name="description" rows="2"
                                          placeholder="e.g., Behind the blue shelving unit, next to the fire exit"
                                          class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"></textarea>
                                <p class="text-xs text-gray-500 mt-1">Internal notes for warehouse volunteers - NOT shown to referrers</p>
                            </div>
                            <button type="submit"
                                    class="w-full px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition">
                                Add Zone
                            </button>
                        </div>
                    </form>
                </div>

                <!-- All Zones List -->
                <div class="bg-white rounded-lg shadow p-4 sm:p-6">
                    <h2 class="text-xl font-semibold text-gray-900 mb-6">All Warehouse Zones</h2>
                    <div class="space-y-4">
                        <?php foreach ($zones as $zone): ?>
                            <div class="border border-gray-200 rounded-lg p-4 <?php echo !$zone['is_active'] ? 'bg-gray-50' : ''; ?>" id="zone-<?php echo $zone['id']; ?>">
                                <!-- View Mode -->
                                <div class="zone-view-<?php echo $zone['id']; ?>">
                                    <div class="flex justify-between items-start">
                                        <div class="flex-1">
                                            <p class="font-medium text-gray-900"><?php echo e($zone['zone_name']); ?></p>
                                            <?php if (!empty($zone['location'])): ?>
                                                <p class="text-sm text-blue-600 mt-1">
                                                    <span class="font-medium">📍 Location:</span> <?php echo e($zone['location']); ?>
                                                </p>
                                            <?php endif; ?>
                                            <?php if (!empty($zone['description'])): ?>
                                                <p class="text-sm text-gray-600 mt-1">
                                                    <span class="font-medium">📝 Internal:</span> <?php echo e($zone['description']); ?>
                                                </p>
                                            <?php endif; ?>
                                            <?php if ($zone['is_active']): ?>
                                                <p class="text-xs text-gray-500 mt-2">
                                                    <?php echo $zoneAllocations[$zone['id']]; ?> referrals allocated
                                                </p>
                                            <?php else: ?>
                                                <span class="inline-block mt-2 px-2 py-1 text-xs bg-red-200 text-red-800 rounded">Inactive</span>
                                            <?php endif; ?>
                                        </div>
                                        <div class="flex space-x-2">
                                            <?php if ($zone['is_active']): ?>
                                                <button onclick="toggleEditZone(<?php echo $zone['id']; ?>)"
                                                        class="text-blue-600 hover:text-blue-800 text-sm font-medium">
                                                    Edit
                                                </button>
                                                <form method="POST" action="" onsubmit="return confirm('Are you sure you want to deactivate this zone?');" class="inline">
                                                    <input type="hidden" name="action" value="delete_zone">
                                                    <input type="hidden" name="zone_id" value="<?php echo $zone['id']; ?>">
                                                    <button type="submit"
                                                            class="text-red-600 hover:text-red-800 text-sm font-medium">
                                                        Deactivate
                                                    </button>
                                                </form>
                                            <?php else: ?>
                                                <form method="POST" action="" class="inline">
                                                    <input type="hidden" name="action" value="reactivate_zone">
                                                    <input type="hidden" name="zone_id" value="<?php echo $zone['id']; ?>">
                                                    <button type="submit"
                                                            class="text-green-600 hover:text-green-800 text-sm font-medium">
                                                        Reactivate
                                                    </button>
                                                </form>
                                                <form method="POST" action="" onsubmit="return confirm('Are you sure you want to permanently delete this zone? This cannot be undone!');" class="inline">
                                                    <input type="hidden" name="action" value="delete_zone_permanent">
                                                    <input type="hidden" name="zone_id" value="<?php echo $zone['id']; ?>">
                                                    <button type="submit"
                                                            class="text-red-600 hover:text-red-800 text-sm font-medium">
                                                        Delete Permanently
                                                    </button>
                                                </form>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>

                                <!-- Edit Mode (Hidden by default) -->
                                <div class="zone-edit-<?php echo $zone['id']; ?>" style="display: none;">
                                    <form method="POST" action="">
                                        <input type="hidden" name="action" value="update_zone">
                                        <input type="hidden" name="zone_id" value="<?php echo $zone['id']; ?>">

                                        <div class="space-y-4">
                                            <div>
                                                <label class="block text-sm font-medium text-gray-700 mb-2">Zone Name <span class="text-red-500">*</span></label>
                                                <input type="text" name="zone_name" required
                                                       value="<?php echo e($zone['zone_name']); ?>"
                                                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                                            </div>
                                            <div>
                                                <label class="block text-sm font-medium text-gray-700 mb-2">Location (shown to referrers)</label>
                                                <input type="text" name="location"
                                                       value="<?php echo e($zone['location'] ?? ''); ?>"
                                                       placeholder="e.g., Row 3, Shelf 2"
                                                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                                                <p class="text-xs text-gray-500 mt-1">Shown in collection emails</p>
                                            </div>
                                            <div>
                                                <label class="block text-sm font-medium text-gray-700 mb-2">Description (internal only)</label>
                                                <textarea name="description" rows="2"
                                                          placeholder="Internal notes for volunteers"
                                                          class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"><?php echo e($zone['description'] ?? ''); ?></textarea>
                                                <p class="text-xs text-gray-500 mt-1">NOT shown to referrers</p>
                                            </div>
                                            <div>
                                                <label class="flex items-center">
                                                    <input type="checkbox" name="is_active" <?php echo $zone['is_active'] ? 'checked' : ''; ?>
                                                           class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                                    <span class="ml-2 text-sm text-gray-700">Active</span>
                                                </label>
                                            </div>
                                            <div class="flex space-x-2">
                                                <button type="submit"
                                                        class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition text-sm">
                                                    Save Changes
                                                </button>
                                                <button type="button" onclick="toggleEditZone(<?php echo $zone['id']; ?>)"
                                                        class="px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition text-sm">
                                                    Cancel
                                                </button>
                                            </div>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<script>
function toggleEditZone(zoneId) {
    const viewMode = document.querySelector('.zone-view-' + zoneId);
    const editMode = document.querySelector('.zone-edit-' + zoneId);

    if (viewMode.style.display === 'none') {
        // Currently in edit mode, switch to view mode
        viewMode.style.display = 'block';
        editMode.style.display = 'none';
    } else {
        // Currently in view mode, switch to edit mode
        viewMode.style.display = 'none';
        editMode.style.display = 'block';
    }
}

// Toggle collection reminder days field
document.addEventListener('DOMContentLoaded', function() {
    const reminderToggle = document.getElementById('collection_reminders_toggle');
    const reminderDaysField = document.getElementById('reminder_days_field');

    if (reminderToggle && reminderDaysField) {
        function updateReminderFieldVisibility() {
            reminderDaysField.style.display = reminderToggle.checked ? 'block' : 'none';
        }

        // Set initial state
        updateReminderFieldVisibility();

        // Update on change
        reminderToggle.addEventListener('change', updateReminderFieldVisibility);
    }
});
</script>

<?php require_once __DIR__ . '/includes/admin_footer.php'; ?>
