<?php
$pageTitle = "Referrals";
require_once __DIR__ . '/includes/admin_header.php';

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

<?php require_once __DIR__ . '/includes/admin_footer.php'; ?>
