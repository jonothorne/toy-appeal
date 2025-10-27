<?php
// Load required files BEFORE any output
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

// Require login
requireLogin();
$currentUser = getCurrentUser();

// Get date range from request or use defaults
$startDate = $_GET['start_date'] ?? date('Y-01-01'); // Start of current year
$endDate = $_GET['end_date'] ?? date('Y-m-d'); // Today

// Handle CSV export BEFORE any HTML output
if (isset($_GET['action']) && $_GET['action'] === 'export_csv') {
    $exportData = getRows(
        "SELECT
            r.reference_number,
            r.child_initials,
            r.child_age,
            r.child_gender,
            r.status,
            r.zone_id,
            z.zone_name,
            r.created_at,
            r.collection_date,
            h.referrer_name,
            h.referrer_email,
            h.referrer_phone,
            h.referrer_organisation,
            h.postcode
         FROM referrals r
         JOIN households h ON r.household_id = h.id
         LEFT JOIN zones z ON r.zone_id = z.id
         WHERE r.created_at BETWEEN ? AND ?
         ORDER BY r.created_at DESC",
        "ss",
        [$startDate . ' 00:00:00', $endDate . ' 23:59:59']
    );

    // Set headers for CSV download
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=toy-appeal-report-' . date('Y-m-d') . '.csv');
    header('Pragma: no-cache');
    header('Expires: 0');

    // Create output stream
    $output = fopen('php://output', 'w');

    // Add BOM for Excel UTF-8 compatibility
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

    // CSV headers
    fputcsv($output, [
        'Reference Number',
        'Child Initials',
        'Age',
        'Gender',
        'Status',
        'Zone',
        'Created Date',
        'Collection Date',
        'Referrer Name',
        'Referrer Email',
        'Referrer Phone',
        'Organisation',
        'Postcode'
    ]);

    // CSV data rows
    foreach ($exportData as $row) {
        fputcsv($output, [
            $row['reference_number'],
            $row['child_initials'],
            $row['child_age'],
            ucfirst($row['child_gender']),
            ucwords(str_replace('_', ' ', $row['status'])),
            $row['zone_name'] ?? 'Unassigned',
            date('d/m/Y H:i', strtotime($row['created_at'])),
            $row['collection_date'] ? date('d/m/Y H:i', strtotime($row['collection_date'])) : '',
            $row['referrer_name'],
            $row['referrer_email'],
            $row['referrer_phone'],
            $row['referrer_organisation'],
            $row['postcode']
        ]);
    }

    fclose($output);
    exit;
}

// NOW load the header (after CSV export is handled)
$pageTitle = "Reports & Analytics";
require_once __DIR__ . '/includes/admin_header.php';

// Get statistics with date filtering
$stats = getStatistics();

// Get referrals by date range for detailed analysis
$dateFilteredReferrals = getRows(
    "SELECT r.*, h.referrer_organisation, h.postcode
     FROM referrals r
     JOIN households h ON r.household_id = h.id
     WHERE r.created_at BETWEEN ? AND ?
     ORDER BY r.created_at DESC",
    "ss",
    [$startDate . ' 00:00:00', $endDate . ' 23:59:59']
);

// Calculate date-filtered stats
$totalInPeriod = count($dateFilteredReferrals);
$householdsInPeriod = count(array_unique(array_column($dateFilteredReferrals, 'household_id')));

// Status breakdown
$statusBreakdown = [];
foreach ($dateFilteredReferrals as $ref) {
    $status = $ref['status'];
    $statusBreakdown[$status] = ($statusBreakdown[$status] ?? 0) + 1;
}

// Organization breakdown
$orgBreakdown = [];
foreach ($dateFilteredReferrals as $ref) {
    $org = $ref['referrer_organisation'];
    $orgBreakdown[$org] = ($orgBreakdown[$org] ?? 0) + 1;
}
arsort($orgBreakdown);

// Age group breakdown
$ageBreakdown = [];
foreach ($dateFilteredReferrals as $ref) {
    $age = intval($ref['child_age']);
    if ($age <= 2) $group = '0-2';
    elseif ($age <= 5) $group = '3-5';
    elseif ($age <= 8) $group = '6-8';
    elseif ($age <= 12) $group = '9-12';
    else $group = '13+';

    $ageBreakdown[$group] = ($ageBreakdown[$group] ?? 0) + 1;
}

// Gender breakdown
$genderBreakdown = [];
foreach ($dateFilteredReferrals as $ref) {
    $gender = $ref['child_gender'];
    $genderBreakdown[$gender] = ($genderBreakdown[$gender] ?? 0) + 1;
}

// Postcode area breakdown
$postcodeBreakdown = [];
foreach ($dateFilteredReferrals as $ref) {
    $postcode = $ref['postcode'];
    $area = explode(' ', $postcode)[0] ?? 'Unknown';
    $postcodeBreakdown[$area] = ($postcodeBreakdown[$area] ?? 0) + 1;
}
arsort($postcodeBreakdown);
$postcodeBreakdown = array_slice($postcodeBreakdown, 0, 10, true); // Top 10
?>

<div class="space-y-6">
    <!-- Page Header -->
    <div class="flex justify-between items-center">
        <div>
            <h1 class="text-3xl font-bold text-gray-900">Reports & Analytics</h1>
            <p class="mt-2 text-sm text-gray-600">Comprehensive insights and data analysis</p>
        </div>
        <a href="reports.php?action=export_csv&start_date=<?php echo urlencode($startDate); ?>&end_date=<?php echo urlencode($endDate); ?>"
           class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition flex items-center">
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
            </svg>
            Export to CSV
        </a>
    </div>

    <!-- Date Range Filter -->
    <div class="bg-white rounded-lg shadow p-6">
        <form method="GET" action="" class="flex items-end space-x-4">
            <div class="flex-1">
                <label class="block text-sm font-medium text-gray-700 mb-2">Start Date</label>
                <input type="date" name="start_date" value="<?php echo e($startDate); ?>"
                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#eb008b]">
            </div>
            <div class="flex-1">
                <label class="block text-sm font-medium text-gray-700 mb-2">End Date</label>
                <input type="date" name="end_date" value="<?php echo e($endDate); ?>"
                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#eb008b]">
            </div>
            <button type="submit" class="px-6 py-2 bg-[#eb008b] text-white rounded-lg hover:bg-[#c00074] transition">
                Apply Filter
            </button>
            <a href="reports.php" class="px-6 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition">
                Reset
            </a>
        </form>
    </div>

    <!-- Summary Stats -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-600">Total Referrals</p>
                    <p class="text-3xl font-bold text-gray-900 mt-2"><?php echo number_format($totalInPeriod); ?></p>
                    <p class="text-xs text-gray-500 mt-1"><?php echo date('d M Y', strtotime($startDate)); ?> - <?php echo date('d M Y', strtotime($endDate)); ?></p>
                </div>
                <div class="p-3 bg-blue-100 rounded-lg">
                    <svg class="w-8 h-8 text-[#eb008b]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-600">Households Helped</p>
                    <p class="text-3xl font-bold text-gray-900 mt-2"><?php echo number_format($householdsInPeriod); ?></p>
                    <p class="text-xs text-gray-500 mt-1">Unique families</p>
                </div>
                <div class="p-3 bg-green-100 rounded-lg">
                    <svg class="w-8 h-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                    </svg>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-600">Collected</p>
                    <p class="text-3xl font-bold text-gray-900 mt-2"><?php echo number_format($statusBreakdown['collected'] ?? 0); ?></p>
                    <p class="text-xs text-gray-500 mt-1">
                        <?php
                        $collectionRate = $totalInPeriod > 0 ? round((($statusBreakdown['collected'] ?? 0) / $totalInPeriod) * 100) : 0;
                        echo $collectionRate . '% collection rate';
                        ?>
                    </p>
                </div>
                <div class="p-3 bg-purple-100 rounded-lg">
                    <svg class="w-8 h-8 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                    </svg>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-600">Organizations</p>
                    <p class="text-3xl font-bold text-gray-900 mt-2"><?php echo number_format(count($orgBreakdown)); ?></p>
                    <p class="text-xs text-gray-500 mt-1">Partner organizations</p>
                </div>
                <div class="p-3 bg-yellow-100 rounded-lg">
                    <svg class="w-8 h-8 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                    </svg>
                </div>
            </div>
        </div>
    </div>

    <!-- Charts Row -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Status Breakdown Chart -->
        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Status Breakdown</h3>
            <canvas id="statusChart"></canvas>
            <div class="mt-4 space-y-2">
                <?php foreach ($statusBreakdown as $status => $count): ?>
                    <div class="flex justify-between text-sm">
                        <span class="text-gray-600 capitalize"><?php echo str_replace('_', ' ', $status); ?></span>
                        <span class="font-semibold text-gray-900"><?php echo number_format($count); ?> (<?php echo round(($count / $totalInPeriod) * 100); ?>%)</span>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Age Group Chart -->
        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Age Distribution</h3>
            <canvas id="ageChart"></canvas>
            <div class="mt-4 space-y-2">
                <?php
                $ageOrder = ['0-2', '3-5', '6-8', '9-12', '13+'];
                foreach ($ageOrder as $age):
                    if (isset($ageBreakdown[$age])):
                        $count = $ageBreakdown[$age];
                ?>
                    <div class="flex justify-between text-sm">
                        <span class="text-gray-600"><?php echo $age; ?> years</span>
                        <span class="font-semibold text-gray-900"><?php echo number_format($count); ?> (<?php echo round(($count / $totalInPeriod) * 100); ?>%)</span>
                    </div>
                <?php
                    endif;
                endforeach;
                ?>
            </div>
        </div>
    </div>

    <!-- Top Organizations Table -->
    <div class="bg-white rounded-lg shadow">
        <div class="px-6 py-4 border-b border-gray-200">
            <h3 class="text-lg font-semibold text-gray-900">Top Partner Organizations</h3>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Organization</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Referrals</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Percentage</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Progress</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    <?php
                    $displayCount = 0;
                    foreach ($orgBreakdown as $org => $count):
                        if ($displayCount++ >= 10) break;
                        $percentage = round(($count / $totalInPeriod) * 100);
                    ?>
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 text-sm font-medium text-gray-900"><?php echo e($org); ?></td>
                            <td class="px-6 py-4 text-sm text-gray-900"><?php echo number_format($count); ?></td>
                            <td class="px-6 py-4 text-sm text-gray-600"><?php echo $percentage; ?>%</td>
                            <td class="px-6 py-4">
                                <div class="w-full bg-gray-200 rounded-full h-2">
                                    <div class="bg-[#eb008b] h-2 rounded-full" style="width: <?php echo $percentage; ?>%"></div>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Top Postcode Areas -->
    <div class="bg-white rounded-lg shadow">
        <div class="px-6 py-4 border-b border-gray-200">
            <h3 class="text-lg font-semibold text-gray-900">Top Postcode Areas</h3>
        </div>
        <div class="p-6">
            <div class="grid grid-cols-2 md:grid-cols-5 gap-4">
                <?php foreach ($postcodeBreakdown as $area => $count): ?>
                    <div class="text-center p-4 bg-gray-50 rounded-lg">
                        <div class="text-2xl font-bold text-[#eb008b]"><?php echo e($area); ?></div>
                        <div class="text-sm text-gray-600 mt-1"><?php echo number_format($count); ?> referrals</div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>

<!-- Chart.js for visualizations -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
// Status Chart
const statusCtx = document.getElementById('statusChart').getContext('2d');
new Chart(statusCtx, {
    type: 'doughnut',
    data: {
        labels: <?php echo json_encode(array_map(function($s) { return ucwords(str_replace('_', ' ', $s)); }, array_keys($statusBreakdown))); ?>,
        datasets: [{
            data: <?php echo json_encode(array_values($statusBreakdown)); ?>,
            backgroundColor: [
                '#9CA3AF', // pending - gray
                '#eb008b', // fulfilled - brand pink
                '#8B5CF6', // located - purple
                '#EAB308', // ready - yellow
                '#10B981'  // collected - green
            ]
        }]
    },
    options: {
        responsive: true,
        plugins: {
            legend: {
                position: 'bottom'
            }
        }
    }
});

// Age Chart
const ageCtx = document.getElementById('ageChart').getContext('2d');
new Chart(ageCtx, {
    type: 'bar',
    data: {
        labels: ['0-2', '3-5', '6-8', '9-12', '13+'],
        datasets: [{
            label: 'Children',
            data: [
                <?php echo $ageBreakdown['0-2'] ?? 0; ?>,
                <?php echo $ageBreakdown['3-5'] ?? 0; ?>,
                <?php echo $ageBreakdown['6-8'] ?? 0; ?>,
                <?php echo $ageBreakdown['9-12'] ?? 0; ?>,
                <?php echo $ageBreakdown['13+'] ?? 0; ?>
            ],
            backgroundColor: '#eb008b'
        }]
    },
    options: {
        responsive: true,
        plugins: {
            legend: {
                display: false
            }
        },
        scales: {
            y: {
                beginAtZero: true,
                ticks: {
                    stepSize: 1
                }
            }
        }
    }
});
</script>

<?php require_once __DIR__ . '/includes/admin_footer.php'; ?>
