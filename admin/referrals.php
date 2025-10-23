<?php
$pageTitle = "Referrals";
require_once __DIR__ . '/includes/admin_header.php';

// Handle bulk operations
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['bulk_action'])) {
    $bulkAction = $_POST['bulk_action'];
    $selectedIds = $_POST['selected_referrals'] ?? [];

    if (!empty($selectedIds) && is_array($selectedIds)) {
        $successCount = 0;
        $failCount = 0;

        foreach ($selectedIds as $refId) {
            $refId = intval($refId);

            switch ($bulkAction) {
                case 'mark_fulfilled':
                    if (updateReferralStatus($refId, 'fulfilled', $currentUser['id'])) {
                        $successCount++;
                    } else {
                        $failCount++;
                    }
                    break;

                case 'mark_located':
                    if (updateReferralStatus($refId, 'located', $currentUser['id'])) {
                        $successCount++;
                    } else {
                        $failCount++;
                    }
                    break;

                case 'mark_ready':
                    if (updateReferralStatus($refId, 'ready_for_collection', $currentUser['id'])) {
                        $successCount++;
                    } else {
                        $failCount++;
                    }
                    break;

                case 'mark_collected':
                    if (updateReferralStatus($refId, 'collected', $currentUser['id'])) {
                        $successCount++;
                    } else {
                        $failCount++;
                    }
                    break;

                case 'assign_zone':
                    $zoneId = intval($_POST['bulk_zone_id'] ?? 0);
                    if (updateReferralStatus($refId, null, $currentUser['id'], $zoneId, true)) {
                        $successCount++;
                    } else {
                        $failCount++;
                    }
                    break;
            }
        }

        $message = "Bulk operation completed: {$successCount} successful";
        if ($failCount > 0) {
            $message .= ", {$failCount} failed";
        }
        $_SESSION['message'] = $message;

        // Redirect to clear POST data
        header('Location: ' . $_SERVER['PHP_SELF'] . '?' . http_build_query($_GET));
        exit;
    }
}

// Get search parameters
$search = trim($_GET['search'] ?? '');
$status = $_GET['status'] ?? '';
$zone = $_GET['zone'] ?? '';
$page = max(1, intval($_GET['page'] ?? 1));
$perPage = 20;

// Get all zones for filter dropdown
$allZones = getRows("SELECT * FROM zones WHERE is_active = 1 ORDER BY zone_name");

// Get referrals
$result = searchReferrals($search, $status, $zone, $page, $perPage);
?>

<div class="space-y-6">
    <!-- Success Message -->
    <?php if (isset($_SESSION['message'])): ?>
        <div class="bg-green-50 border-l-4 border-green-400 p-4 rounded">
            <p class="text-sm text-green-700"><?php echo e($_SESSION['message']); ?></p>
        </div>
        <?php unset($_SESSION['message']); ?>
    <?php endif; ?>

    <!-- Page Header -->
    <div class="flex justify-between items-center">
        <div>
            <h1 class="text-3xl font-bold text-gray-900">Referrals</h1>
            <p class="mt-2 text-sm text-gray-600">Manage all toy referrals</p>
        </div>
        <a href="../index.php" target="_blank"
           class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition">
            + New Referral
        </a>
    </div>

    <!-- Search and Filters -->
    <div class="bg-white rounded-lg shadow p-6">
        <form method="GET" action="" class="space-y-4">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Search</label>
                    <input type="text" name="search" placeholder="Reference number, child initials, referrer name, organisation, postcode..."
                           value="<?php echo e($search); ?>"
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Status</label>
                    <select name="status"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        <option value="">All Statuses</option>
                        <option value="pending" <?php echo $status === 'pending' ? 'selected' : ''; ?>>Pending</option>
                        <option value="fulfilled" <?php echo $status === 'fulfilled' ? 'selected' : ''; ?>>Fulfilled</option>
                        <option value="located" <?php echo $status === 'located' ? 'selected' : ''; ?>>Located</option>
                        <option value="ready_for_collection" <?php echo $status === 'ready_for_collection' ? 'selected' : ''; ?>>Ready for Collection</option>
                        <option value="collected" <?php echo $status === 'collected' ? 'selected' : ''; ?>>Collected</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Zone</label>
                    <select name="zone"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        <option value="">All Zones</option>
                        <option value="unassigned" <?php echo $zone === 'unassigned' ? 'selected' : ''; ?>>Unassigned</option>
                        <?php foreach ($allZones as $z): ?>
                            <option value="<?php echo $z['id']; ?>" <?php echo $zone == $z['id'] ? 'selected' : ''; ?>>
                                <?php echo e($z['zone_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="flex space-x-4">
                <button type="submit"
                        class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition">
                    Search
                </button>
                <a href="referrals.php"
                   class="px-6 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition">
                    Clear
                </a>
            </div>
        </form>
    </div>

    <!-- Bulk Operations (Desktop only) -->
    <?php if (!empty($result['results'])): ?>
    <div class="hidden md:block bg-white rounded-lg shadow p-4">
        <form method="POST" action="" id="bulkForm">
            <div class="flex items-center space-x-4">
                <div class="flex items-center space-x-2">
                    <input type="checkbox" id="selectAll" class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500">
                    <label for="selectAll" class="text-sm font-medium text-gray-700">Select All</label>
                    <span id="selectedCount" class="text-sm text-gray-500">(0 selected)</span>
                </div>

                <div class="flex-1 flex items-center space-x-2">
                    <label class="text-sm font-medium text-gray-700">Bulk Action:</label>
                    <select name="bulk_action" id="bulkAction" class="px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 text-sm">
                        <option value="">Choose action...</option>
                        <option value="mark_fulfilled">Mark as Fulfilled</option>
                        <option value="mark_located">Mark as Located</option>
                        <option value="mark_ready">Mark as Ready for Collection</option>
                        <option value="mark_collected">Mark as Collected</option>
                        <option value="assign_zone">Assign to Zone...</option>
                    </select>

                    <select name="bulk_zone_id" id="bulkZone" class="hidden px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 text-sm">
                        <option value="">Select Zone...</option>
                        <?php foreach ($allZones as $z): ?>
                            <option value="<?php echo $z['id']; ?>"><?php echo e($z['zone_name']); ?></option>
                        <?php endforeach; ?>
                    </select>

                    <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition text-sm font-medium disabled:opacity-50 disabled:cursor-not-allowed" id="bulkSubmit" disabled>
                        Apply to Selected
                    </button>
                </div>
            </div>
        </form>
    </div>
    <?php endif; ?>

    <!-- Results -->
    <div class="bg-white rounded-lg shadow">
        <div class="px-4 sm:px-6 py-4 border-b border-gray-200">
            <h3 class="text-lg font-semibold text-gray-900">
                <?php echo number_format($result['total']); ?> Referral<?php echo $result['total'] != 1 ? 's' : ''; ?> Found
            </h3>
        </div>

        <?php if (empty($result['results'])): ?>
            <div class="p-8 text-center text-gray-500">
                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
                <p class="mt-2 text-sm">No referrals found. Try adjusting your search criteria.</p>
            </div>
        <?php else: ?>
            <!-- Mobile Card View -->
            <div class="block md:hidden">
                <?php foreach ($result['results'] as $referral): ?>
                    <div class="border-b border-gray-200 p-4 hover:bg-gray-50">
                        <div class="flex justify-between items-start mb-2">
                            <a href="view_referral.php?id=<?php echo $referral['id']; ?>" class="text-blue-600 font-semibold hover:underline">
                                <?php echo e($referral['reference_number']); ?>
                            </a>
                            <?php echo getStatusBadge($referral['status']); ?>
                        </div>
                        <div class="space-y-1 text-sm">
                            <div>
                                <span class="font-medium text-gray-700">Child:</span>
                                <span class="text-gray-900"><?php echo e($referral['child_initials']); ?></span>
                                <span class="text-gray-500">(<?php echo e($referral['child_age']); ?>y, <?php echo e($referral['child_gender']); ?>)</span>
                            </div>
                            <div>
                                <span class="font-medium text-gray-700">Postcode:</span>
                                <span class="text-gray-900"><?php echo e($referral['postcode']); ?></span>
                            </div>
                            <div>
                                <span class="font-medium text-gray-700">Referrer:</span>
                                <span class="text-gray-900"><?php echo e($referral['referrer_name']); ?></span>
                            </div>
                            <div>
                                <span class="font-medium text-gray-700">Org:</span>
                                <span class="text-gray-900"><?php echo e($referral['referrer_organisation']); ?></span>
                            </div>
                            <div class="flex justify-between items-center pt-2">
                                <span class="text-xs text-gray-500">
                                    <?php echo $referral['zone_name'] ? 'Zone: ' . e($referral['zone_name']) : 'No zone'; ?> •
                                    <?php echo formatDate($referral['created_at'], 'd/m/Y'); ?>
                                </span>
                                <a href="view_referral.php?id=<?php echo $referral['id']; ?>"
                                   class="text-blue-600 hover:text-blue-900 font-medium text-sm">
                                    View →
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- Desktop Table View -->
            <div class="hidden md:block overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left">
                                <span class="sr-only">Select</span>
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Reference</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Child</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Referrer</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Organisation</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Zone</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Created</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php foreach ($result['results'] as $referral): ?>
                            <tr class="hover:bg-gray-50">
                                <td class="px-4 py-4 whitespace-nowrap">
                                    <input type="checkbox" name="selected_referrals[]" value="<?php echo $referral['id']; ?>"
                                           class="referral-checkbox w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500"
                                           form="bulkForm">
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-blue-600">
                                        <a href="view_referral.php?id=<?php echo $referral['id']; ?>" class="hover:underline">
                                            <?php echo e($referral['reference_number']); ?>
                                        </a>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900"><?php echo e($referral['child_initials']); ?></div>
                                    <div class="text-xs text-gray-500"><?php echo e($referral['child_age']); ?> years, <?php echo e($referral['child_gender']); ?></div>
                                    <div class="text-xs text-gray-500"><?php echo e($referral['postcode']); ?></div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900"><?php echo e($referral['referrer_name']); ?></div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="text-sm text-gray-900 max-w-xs truncate"><?php echo e($referral['referrer_organisation']); ?></div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <?php echo getStatusBadge($referral['status']); ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <?php echo $referral['zone_name'] ? e($referral['zone_name']) : '-'; ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <?php echo formatDate($referral['created_at'], 'd/m/Y'); ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm">
                                    <a href="view_referral.php?id=<?php echo $referral['id']; ?>"
                                       class="text-blue-600 hover:text-blue-900 font-medium">
                                        View
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <?php if ($result['total_pages'] > 1): ?>
                <div class="px-6 py-4 border-t border-gray-200 flex items-center justify-between">
                    <div class="flex-1 flex justify-between sm:hidden">
                        <?php if ($page > 1): ?>
                            <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page - 1])); ?>"
                               class="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                                Previous
                            </a>
                        <?php endif; ?>
                        <?php if ($page < $result['total_pages']): ?>
                            <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page + 1])); ?>"
                               class="ml-3 relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                                Next
                            </a>
                        <?php endif; ?>
                    </div>
                    <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
                        <div>
                            <p class="text-sm text-gray-700">
                                Showing
                                <span class="font-medium"><?php echo (($page - 1) * $perPage) + 1; ?></span>
                                to
                                <span class="font-medium"><?php echo min($page * $perPage, $result['total']); ?></span>
                                of
                                <span class="font-medium"><?php echo $result['total']; ?></span>
                                results
                            </p>
                        </div>
                        <div>
                            <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px">
                                <?php if ($page > 1): ?>
                                    <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page - 1])); ?>"
                                       class="relative inline-flex items-center px-2 py-2 rounded-l-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50">
                                        &larr; Previous
                                    </a>
                                <?php endif; ?>

                                <?php
                                $startPage = max(1, $page - 2);
                                $endPage = min($result['total_pages'], $page + 2);

                                for ($i = $startPage; $i <= $endPage; $i++):
                                ?>
                                    <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $i])); ?>"
                                       class="relative inline-flex items-center px-4 py-2 border <?php echo $i == $page ? 'bg-blue-50 border-blue-500 text-blue-600' : 'border-gray-300 bg-white text-gray-700 hover:bg-gray-50'; ?> text-sm font-medium">
                                        <?php echo $i; ?>
                                    </a>
                                <?php endfor; ?>

                                <?php if ($page < $result['total_pages']): ?>
                                    <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page + 1])); ?>"
                                       class="relative inline-flex items-center px-2 py-2 rounded-r-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50">
                                        Next &rarr;
                                    </a>
                                <?php endif; ?>
                            </nav>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const selectAllCheckbox = document.getElementById('selectAll');
    const referralCheckboxes = document.querySelectorAll('.referral-checkbox');
    const selectedCount = document.getElementById('selectedCount');
    const bulkSubmit = document.getElementById('bulkSubmit');
    const bulkAction = document.getElementById('bulkAction');
    const bulkZone = document.getElementById('bulkZone');

    // Update selected count
    function updateSelectedCount() {
        const checked = document.querySelectorAll('.referral-checkbox:checked').length;
        selectedCount.textContent = `(${checked} selected)`;
        bulkSubmit.disabled = checked === 0;
    }

    // Select all checkbox
    if (selectAllCheckbox) {
        selectAllCheckbox.addEventListener('change', function() {
            referralCheckboxes.forEach(checkbox => {
                checkbox.checked = this.checked;
            });
            updateSelectedCount();
        });
    }

    // Individual checkboxes
    referralCheckboxes.forEach(checkbox => {
        checkbox.addEventListener('change', function() {
            updateSelectedCount();

            // Update select all checkbox
            const allChecked = Array.from(referralCheckboxes).every(cb => cb.checked);
            const someChecked = Array.from(referralCheckboxes).some(cb => cb.checked);
            if (selectAllCheckbox) {
                selectAllCheckbox.checked = allChecked;
                selectAllCheckbox.indeterminate = someChecked && !allChecked;
            }
        });
    });

    // Show/hide zone dropdown based on action
    if (bulkAction) {
        bulkAction.addEventListener('change', function() {
            if (this.value === 'assign_zone') {
                bulkZone.classList.remove('hidden');
            } else {
                bulkZone.classList.add('hidden');
            }
        });
    }

    // Confirm before bulk action
    document.getElementById('bulkForm').addEventListener('submit', function(e) {
        const action = bulkAction.value;
        const count = document.querySelectorAll('.referral-checkbox:checked').length;

        if (!action) {
            e.preventDefault();
            alert('Please select an action');
            return false;
        }

        if (action === 'assign_zone' && !bulkZone.value) {
            e.preventDefault();
            alert('Please select a zone');
            return false;
        }

        const actionNames = {
            'mark_fulfilled': 'mark as Fulfilled',
            'mark_located': 'mark as Located',
            'mark_ready': 'mark as Ready for Collection',
            'mark_collected': 'mark as Collected',
            'assign_zone': 'assign to zone'
        };

        const confirmed = confirm(`Are you sure you want to ${actionNames[action]} ${count} referral(s)?`);
        if (!confirmed) {
            e.preventDefault();
            return false;
        }
    });
});
</script>

<?php require_once __DIR__ . '/includes/admin_footer.php'; ?>
