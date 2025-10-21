<?php
$pageTitle = "Dashboard";
require_once __DIR__ . '/includes/admin_header.php';

$stats = getStatistics();
?>

<div class="space-y-6">
    <!-- Page Header -->
    <div>
        <h1 class="text-3xl font-bold text-gray-900">Dashboard</h1>
        <p class="mt-2 text-sm text-gray-600">Overview of the Christmas Toy Appeal referral system</p>
    </div>

    <!-- Key Stats -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center">
                <div class="flex-shrink-0 bg-blue-500 rounded-md p-3">
                    <svg class="h-6 w-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                </div>
                <div class="ml-5 w-0 flex-1">
                    <dl>
                        <dt class="text-sm font-medium text-gray-500 truncate">Total Referrals</dt>
                        <dd class="text-3xl font-semibold text-gray-900"><?php echo number_format($stats['total_referrals']); ?></dd>
                    </dl>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center">
                <div class="flex-shrink-0 bg-green-500 rounded-md p-3">
                    <svg class="h-6 w-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                    </svg>
                </div>
                <div class="ml-5 w-0 flex-1">
                    <dl>
                        <dt class="text-sm font-medium text-gray-500 truncate">Total Households</dt>
                        <dd class="text-3xl font-semibold text-gray-900"><?php echo number_format($stats['total_households']); ?></dd>
                    </dl>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center">
                <div class="flex-shrink-0 bg-yellow-500 rounded-md p-3">
                    <svg class="h-6 w-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <div class="ml-5 w-0 flex-1">
                    <dl>
                        <dt class="text-sm font-medium text-gray-500 truncate">Ready for Collection</dt>
                        <dd class="text-3xl font-semibold text-gray-900"><?php echo number_format($stats['ready_for_collection']); ?></dd>
                    </dl>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center">
                <div class="flex-shrink-0 bg-purple-500 rounded-md p-3">
                    <svg class="h-6 w-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/>
                    </svg>
                </div>
                <div class="ml-5 w-0 flex-1">
                    <dl>
                        <dt class="text-sm font-medium text-gray-500 truncate">This Week</dt>
                        <dd class="text-3xl font-semibold text-gray-900"><?php echo number_format($stats['recent_referrals']); ?></dd>
                    </dl>
                </div>
            </div>
        </div>
    </div>

    <!-- Status Breakdown -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Referrals by Status</h3>
            <div class="space-y-3">
                <?php
                $statusLabels = [
                    'pending' => 'Pending',
                    'fulfilled' => 'Fulfilled',
                    'located' => 'Located in Warehouse',
                    'ready_for_collection' => 'Ready for Collection',
                    'collected' => 'Collected'
                ];
                $statusColors = [
                    'pending' => 'bg-gray-200',
                    'fulfilled' => 'bg-blue-500',
                    'located' => 'bg-purple-500',
                    'ready_for_collection' => 'bg-yellow-500',
                    'collected' => 'bg-green-500'
                ];

                $totalForPercentage = $stats['total_referrals'] ?: 1;

                foreach ($statusLabels as $status => $label):
                    $count = $stats['by_status'][$status] ?? 0;
                    $percentage = ($count / $totalForPercentage) * 100;
                ?>
                    <div>
                        <div class="flex justify-between text-sm mb-1">
                            <span class="font-medium text-gray-700"><?php echo e($label); ?></span>
                            <span class="text-gray-600"><?php echo number_format($count); ?> (<?php echo number_format($percentage, 1); ?>%)</span>
                        </div>
                        <div class="w-full bg-gray-200 rounded-full h-2">
                            <div class="<?php echo $statusColors[$status]; ?> h-2 rounded-full" style="width: <?php echo $percentage; ?>%"></div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Top Referring Organisations</h3>
            <div class="space-y-3">
                <?php if (empty($stats['by_organisation'])): ?>
                    <p class="text-gray-500 text-sm">No referrals yet</p>
                <?php else: ?>
                    <?php foreach ($stats['by_organisation'] as $org): ?>
                        <div class="flex justify-between items-center py-2 border-b border-gray-100">
                            <span class="text-sm font-medium text-gray-700 truncate flex-1"><?php echo e($org['referrer_organisation']); ?></span>
                            <span class="ml-4 px-3 py-1 text-sm font-semibold bg-blue-100 text-blue-800 rounded-full">
                                <?php echo number_format($org['count']); ?>
                            </span>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- More Statistics -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Age Distribution</h3>
            <div class="space-y-2">
                <?php foreach ($stats['by_age_group'] as $age): ?>
                    <div class="flex justify-between text-sm">
                        <span class="text-gray-700"><?php echo e($age['age_group']); ?> years</span>
                        <span class="font-semibold text-gray-900"><?php echo number_format($age['count']); ?></span>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Gender Distribution</h3>
            <div class="space-y-2">
                <?php foreach ($stats['by_gender'] as $gender): ?>
                    <div class="flex justify-between text-sm">
                        <span class="text-gray-700"><?php echo e($gender['child_gender']); ?></span>
                        <span class="font-semibold text-gray-900"><?php echo number_format($gender['count']); ?></span>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Family Duration Known</h3>
            <div class="space-y-2">
                <?php foreach ($stats['by_duration'] as $duration): ?>
                    <div class="flex justify-between text-sm">
                        <span class="text-gray-700"><?php echo e($duration['duration_known']); ?></span>
                        <span class="font-semibold text-gray-900"><?php echo number_format($duration['count']); ?></span>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="bg-white rounded-lg shadow p-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">Quick Actions</h3>
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <a href="referrals.php" class="flex items-center justify-center px-4 py-3 border border-gray-300 rounded-lg hover:bg-gray-50 transition">
                <svg class="h-5 w-5 text-gray-600 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                </svg>
                <span class="text-sm font-medium text-gray-700">View All Referrals</span>
            </a>
            <a href="referrals.php?status=pending" class="flex items-center justify-center px-4 py-3 border border-gray-300 rounded-lg hover:bg-gray-50 transition">
                <svg class="h-5 w-5 text-gray-600 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <span class="text-sm font-medium text-gray-700">Pending Referrals</span>
            </a>
            <a href="labels.php" class="flex items-center justify-center px-4 py-3 border border-gray-300 rounded-lg hover:bg-gray-50 transition">
                <svg class="h-5 w-5 text-gray-600 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/>
                </svg>
                <span class="text-sm font-medium text-gray-700">Print Labels</span>
            </a>
            <a href="settings.php" class="flex items-center justify-center px-4 py-3 border border-gray-300 rounded-lg hover:bg-gray-50 transition">
                <svg class="h-5 w-5 text-gray-600 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                </svg>
                <span class="text-sm font-medium text-gray-700">Settings</span>
            </a>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/admin_footer.php'; ?>
