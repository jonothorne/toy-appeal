<?php
$pageTitle = "Deletion History";
require_once __DIR__ . '/includes/admin_header.php';

$message = '';
$error = '';

// Handle permanent deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'permanent_delete') {
    $deletionId = intval($_POST['deletion_id'] ?? 0);

    if ($deletionId) {
        // Get deletion details for confirmation
        $deletion = getRow("SELECT * FROM deletions_log WHERE id = ?", "i", [$deletionId]);

        if ($deletion) {
            // Permanently delete from deletions_log (removes last trace)
            $result = updateQuery("DELETE FROM deletions_log WHERE id = ?", "i", [$deletionId]);

            if ($result !== false) {
                $message = "Deletion record for {$deletion['reference_number']} has been permanently removed. All traces of this referral are now gone.";
            } else {
                $error = "Failed to permanently delete the record.";
            }
        } else {
            $error = "Deletion record not found.";
        }
    }
}

// Get filters
$search = trim($_GET['search'] ?? '');
$page = max(1, intval($_GET['page'] ?? 1));
$perPage = 20;
$offset = ($page - 1) * $perPage;

// Build WHERE clause
$where = [];
$types = "";
$params = [];

if (!empty($search)) {
    $where[] = "(dl.reference_number LIKE ? OR dl.child_initials LIKE ? OR dl.referrer_name LIKE ? OR dl.referrer_organisation LIKE ? OR dl.reason LIKE ?)";
    $searchTerm = "%{$search}%";
    $types .= "sssss";
    $params = array_merge($params, [$searchTerm, $searchTerm, $searchTerm, $searchTerm, $searchTerm]);
}

$whereClause = !empty($where) ? "WHERE " . implode(" AND ", $where) : "";

// Get total count
$countSql = "SELECT COUNT(*) as total FROM deletions_log dl {$whereClause}";
$countResult = getRow($countSql, $types, $params);
$total = $countResult['total'];

// Get deletions
$sql = "SELECT dl.*, u.full_name as deleted_by_name
        FROM deletions_log dl
        LEFT JOIN users u ON dl.deleted_by = u.id
        {$whereClause}
        ORDER BY dl.deleted_at DESC
        LIMIT ? OFFSET ?";

$types .= "ii";
$params[] = $perPage;
$params[] = $offset;

$deletions = getRows($sql, $types, $params);

$totalPages = ceil($total / $perPage);
?>

<div class="space-y-6">
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

    <!-- Page Header -->
    <div class="flex justify-between items-center">
        <div>
            <h1 class="text-3xl font-bold text-gray-900">Deletion History</h1>
            <p class="mt-2 text-sm text-gray-600">Permanent audit log of all deleted referrals (GDPR compliance)</p>
        </div>
    </div>

    <!-- Search -->
    <div class="bg-white rounded-lg shadow p-6">
        <form method="GET" action="" class="space-y-4">
            <div class="flex gap-4">
                <div class="flex-1">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Search</label>
                    <input type="text" name="search" placeholder="Reference number, child initials, referrer name, organisation, reason..."
                           value="<?php echo e($search); ?>"
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                </div>
                <div class="flex items-end">
                    <button type="submit" class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition">
                        Search
                    </button>
                    <?php if ($search): ?>
                        <a href="deletions.php" class="ml-2 px-6 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition">
                            Clear
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </form>
    </div>

    <!-- Statistics -->
    <div class="bg-blue-50 border-l-4 border-blue-500 p-4 rounded">
        <p class="text-sm text-blue-800">
            <strong>Total Deletions:</strong> <?php echo number_format($total); ?> referrals deleted
        </p>
    </div>

    <!-- Results -->
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Reference</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Child</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Referrer</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Deleted By</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Deleted At</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Reason</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php if (empty($deletions)): ?>
                        <tr>
                            <td colspan="7" class="px-6 py-8 text-center text-gray-500">
                                <?php if ($search): ?>
                                    No deletions found matching your search.
                                <?php else: ?>
                                    No referrals have been deleted yet.
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($deletions as $deletion): ?>
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-gray-900"><?php echo e($deletion['reference_number']); ?></div>
                                    <?php if ($deletion['household_deleted']): ?>
                                        <div class="text-xs text-orange-600 mt-1">
                                            <span class="bg-orange-100 px-2 py-0.5 rounded">Household Deleted</span>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900"><?php echo e($deletion['child_initials']); ?></div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="text-sm text-gray-900"><?php echo e($deletion['referrer_name']); ?></div>
                                    <div class="text-xs text-gray-500"><?php echo e($deletion['referrer_organisation']); ?></div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900"><?php echo e($deletion['deleted_by_name'] ?: 'Unknown'); ?></div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900"><?php echo formatDate($deletion['deleted_at']); ?></div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="text-sm text-gray-700"><?php echo e($deletion['reason']); ?></div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <button onclick="showPermanentDeleteModal(<?php echo $deletion['id']; ?>, '<?php echo e($deletion['reference_number']); ?>')"
                                            class="text-xs text-red-600 hover:text-red-800 font-medium bg-red-50 px-2 py-1 rounded border border-red-200 hover:bg-red-100">
                                        Permanent Delete
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <?php if ($totalPages > 1): ?>
            <div class="bg-white px-4 py-3 flex items-center justify-between border-t border-gray-200 sm:px-6">
                <div class="flex-1 flex justify-between sm:hidden">
                    <?php if ($page > 1): ?>
                        <a href="?page=<?php echo $page - 1; ?><?php echo $search ? '&search=' . urlencode($search) : ''; ?>"
                           class="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                            Previous
                        </a>
                    <?php endif; ?>
                    <?php if ($page < $totalPages): ?>
                        <a href="?page=<?php echo $page + 1; ?><?php echo $search ? '&search=' . urlencode($search) : ''; ?>"
                           class="ml-3 relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                            Next
                        </a>
                    <?php endif; ?>
                </div>
                <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
                    <div>
                        <p class="text-sm text-gray-700">
                            Showing <span class="font-medium"><?php echo $offset + 1; ?></span> to
                            <span class="font-medium"><?php echo min($offset + $perPage, $total); ?></span> of
                            <span class="font-medium"><?php echo $total; ?></span> results
                        </p>
                    </div>
                    <div>
                        <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px">
                            <?php if ($page > 1): ?>
                                <a href="?page=<?php echo $page - 1; ?><?php echo $search ? '&search=' . urlencode($search) : ''; ?>"
                                   class="relative inline-flex items-center px-2 py-2 rounded-l-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50">
                                    Previous
                                </a>
                            <?php endif; ?>

                            <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                                <a href="?page=<?php echo $i; ?><?php echo $search ? '&search=' . urlencode($search) : ''; ?>"
                                   class="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium <?php echo $i === $page ? 'bg-blue-50 text-blue-600' : 'bg-white text-gray-700 hover:bg-gray-50'; ?>">
                                    <?php echo $i; ?>
                                </a>
                            <?php endfor; ?>

                            <?php if ($page < $totalPages): ?>
                                <a href="?page=<?php echo $page + 1; ?><?php echo $search ? '&search=' . urlencode($search) : ''; ?>"
                                   class="relative inline-flex items-center px-2 py-2 rounded-r-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50">
                                    Next
                                </a>
                            <?php endif; ?>
                        </nav>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <!-- GDPR Note -->
    <div class="bg-gray-50 border-l-4 border-gray-400 p-4 rounded">
        <p class="text-xs text-gray-600">
            <strong>GDPR Compliance:</strong> This permanent log maintains a record of all deleted referrals for audit purposes.
            These records are independent of the referrals table and will not be deleted when referrals are removed.
            Deletion records include who deleted the referral, when, and why.
        </p>
        <p class="text-xs text-gray-600 mt-2">
            <strong>Permanent Delete:</strong> Use the "Permanent Delete" button to completely remove all traces of a referral from the system.
            This is irreversible and should only be used for GDPR "right to erasure" requests or to remove test data.
        </p>
    </div>
</div>

<!-- Permanent Delete Confirmation Modal -->
<div id="permanentDeleteModal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-75 overflow-y-auto h-full w-full z-50">
    <div class="relative top-20 mx-auto p-5 border w-full max-w-md shadow-lg rounded-md bg-white">
        <div class="mt-3">
            <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-red-100">
                <svg class="h-6 w-6 text-red-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                </svg>
            </div>
            <h3 class="text-lg leading-6 font-medium text-gray-900 mt-5 text-center">⚠️ PERMANENT DELETE</h3>
            <div class="mt-2 px-7 py-3">
                <div class="bg-red-50 border-l-4 border-red-600 p-3 mb-4">
                    <p class="text-sm text-red-800 font-semibold">
                        ⚠️ THIS ACTION CANNOT BE UNDONE!
                    </p>
                </div>

                <p class="text-sm text-gray-700 mb-4">
                    You are about to <strong>permanently delete ALL traces</strong> of referral:
                </p>
                <p class="text-center text-lg font-bold text-red-600 mb-4" id="deletionRefNumber"></p>

                <div class="bg-yellow-50 border border-yellow-200 rounded p-3 mb-4">
                    <p class="text-xs text-yellow-800">
                        <strong>What will be deleted:</strong>
                    </p>
                    <ul class="text-xs text-yellow-800 list-disc ml-4 mt-1">
                        <li>This deletion record (last trace)</li>
                        <li>All audit trail information</li>
                        <li>No way to recover this data</li>
                    </ul>
                </div>

                <p class="text-xs text-gray-600 mb-4">
                    <strong>Use this only for:</strong> GDPR "right to erasure" requests or removing test data.
                </p>

                <form method="POST" action="" id="permanentDeleteForm">
                    <input type="hidden" name="action" value="permanent_delete">
                    <input type="hidden" name="deletion_id" id="deletionIdInput">

                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Type <span class="font-mono bg-gray-100 px-2 py-0.5 rounded text-red-600">DELETE</span> to confirm:
                        </label>
                        <input type="text" id="confirmInput"
                               class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm"
                               placeholder="Type DELETE here"
                               autocomplete="off">
                    </div>

                    <div class="flex gap-3">
                        <button type="button" onclick="hidePermanentDeleteModal()"
                                class="flex-1 px-4 py-2 bg-gray-100 text-gray-700 text-sm font-medium rounded-md hover:bg-gray-200 transition">
                            Cancel
                        </button>
                        <button type="submit" id="confirmDeleteBtn" disabled
                                class="flex-1 px-4 py-2 bg-red-600 text-white text-sm font-medium rounded-md hover:bg-red-700 transition disabled:opacity-50 disabled:cursor-not-allowed">
                            Permanent Delete
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
let currentDeletionId = null;
let currentRefNumber = null;

function showPermanentDeleteModal(deletionId, refNumber) {
    currentDeletionId = deletionId;
    currentRefNumber = refNumber;

    document.getElementById('deletionIdInput').value = deletionId;
    document.getElementById('deletionRefNumber').textContent = refNumber;
    document.getElementById('confirmInput').value = '';
    document.getElementById('confirmDeleteBtn').disabled = true;
    document.getElementById('permanentDeleteModal').classList.remove('hidden');
}

function hidePermanentDeleteModal() {
    document.getElementById('permanentDeleteModal').classList.add('hidden');
    currentDeletionId = null;
    currentRefNumber = null;
}

// Enable delete button only when user types "DELETE"
document.getElementById('confirmInput').addEventListener('input', function(e) {
    const confirmBtn = document.getElementById('confirmDeleteBtn');
    if (e.target.value === 'DELETE') {
        confirmBtn.disabled = false;
    } else {
        confirmBtn.disabled = true;
    }
});

// Close modal when clicking outside
document.getElementById('permanentDeleteModal').addEventListener('click', function(e) {
    if (e.target === this) {
        hidePermanentDeleteModal();
    }
});

// Close modal on Escape key
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        hidePermanentDeleteModal();
    }
});
</script>

<?php require_once __DIR__ . '/includes/admin_footer.php'; ?>
