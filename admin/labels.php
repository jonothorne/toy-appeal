<?php
// Handle marking labels as printed BEFORE any output
if (isset($_GET['mark_printed']) && isset($_GET['ids'])) {
    // Need to load dependencies first for database access
    require_once __DIR__ . '/../includes/config.php';
    require_once __DIR__ . '/../includes/db.php';
    require_once __DIR__ . '/../includes/auth.php';

    // Check if user is logged in
    if (!isLoggedIn()) {
        header('Location: login.php');
        exit;
    }

    $currentUser = getCurrentUser();

    $ids = explode(',', $_GET['ids']);
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $types = str_repeat('i', count($ids));

    // Update all referrals as printed
    $conn = getDBConnection();
    $stmt = $conn->prepare("UPDATE referrals SET label_printed = TRUE, label_printed_at = NOW(), label_printed_by = ? WHERE id IN ($placeholders)");
    $params = array_merge([$currentUser['id']], $ids);
    $stmt->bind_param('i' . $types, ...$params);
    $stmt->execute();

    // Redirect back without mark_printed parameter
    $newUrl = 'labels.php?print=1&ids=' . $_GET['ids'];
    header('Location: ' . $newUrl);
    exit;
}

$pageTitle = "Print Labels";
require_once __DIR__ . '/includes/admin_header.php';

// Get filter preference
$labelFilter = $_GET['filter'] ?? 'unprinted';

// Get referrals to print
$referrals = [];

// Build WHERE clause for label filter
$labelFilterClause = '';
if ($labelFilter === 'unprinted') {
    $labelFilterClause = ' AND r.label_printed = FALSE';
} elseif ($labelFilter === 'printed') {
    $labelFilterClause = ' AND r.label_printed = TRUE';
}

if (isset($_GET['ids'])) {
    // Specific referral IDs
    $ids = explode(',', $_GET['ids']);
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $types = str_repeat('i', count($ids));

    $referrals = getRows(
        "SELECT r.*, h.referrer_name, h.referrer_organisation, z.zone_name
         FROM referrals r
         JOIN households h ON r.household_id = h.id
         LEFT JOIN zones z ON r.zone_id = z.id
         WHERE r.id IN ($placeholders)
         ORDER BY r.reference_number",
        $types,
        $ids
    );
} elseif (isset($_GET['household'])) {
    // All referrals from a household
    $householdId = intval($_GET['household']);
    $referrals = getRows(
        "SELECT r.*, h.referrer_name, h.referrer_organisation, z.zone_name
         FROM referrals r
         JOIN households h ON r.household_id = h.id
         LEFT JOIN zones z ON r.zone_id = z.id
         WHERE r.household_id = ? $labelFilterClause
         ORDER BY r.reference_number",
        "i",
        [$householdId]
    );
} elseif (isset($_GET['status'])) {
    // All referrals with a specific status
    $status = $_GET['status'];
    $referrals = getRows(
        "SELECT r.*, h.referrer_name, h.referrer_organisation, z.zone_name
         FROM referrals r
         JOIN households h ON r.household_id = h.id
         LEFT JOIN zones z ON r.zone_id = z.id
         WHERE r.status = ? $labelFilterClause
         ORDER BY r.reference_number",
        "s",
        [$status]
    );
} elseif (isset($_GET['filter'])) {
    // Filter by printed status
    if ($labelFilter === 'unprinted') {
        $referrals = getRows(
            "SELECT r.*, h.referrer_name, h.referrer_organisation, z.zone_name
             FROM referrals r
             JOIN households h ON r.household_id = h.id
             LEFT JOIN zones z ON r.zone_id = z.id
             WHERE r.label_printed = FALSE AND r.status != 'collected'
             ORDER BY r.reference_number"
        );
    } elseif ($labelFilter === 'printed') {
        $referrals = getRows(
            "SELECT r.*, h.referrer_name, h.referrer_organisation, z.zone_name
             FROM referrals r
             JOIN households h ON r.household_id = h.id
             LEFT JOIN zones z ON r.zone_id = z.id
             WHERE r.label_printed = TRUE
             ORDER BY r.reference_number"
        );
    }
} elseif (isset($_GET['all'])) {
    // All referrals
    $referrals = getRows(
        "SELECT r.*, h.referrer_name, h.referrer_organisation, z.zone_name
         FROM referrals r
         JOIN households h ON r.household_id = h.id
         LEFT JOIN zones z ON r.zone_id = z.id
         ORDER BY r.reference_number"
    );
}

// If we're not in print mode, show selection interface
$printMode = isset($_GET['print']) && !empty($referrals);
?>

<?php if (!$printMode): ?>
    <div class="space-y-6">
        <!-- Page Header -->
        <div>
            <h1 class="text-3xl font-bold text-gray-900">Print Labels</h1>
            <p class="mt-2 text-sm text-gray-600">Generate printable labels for A4 sticker sheets</p>
        </div>

        <!-- Label Filter Warning -->
        <?php
        $unprintedCount = getRow("SELECT COUNT(*) as count FROM referrals WHERE label_printed = FALSE AND status != 'collected'");
        $printedCount = getRow("SELECT COUNT(*) as count FROM referrals WHERE label_printed = TRUE AND status != 'collected'");
        ?>

        <?php if ($unprintedCount['count'] > 0): ?>
            <div class="bg-green-50 border-l-4 border-green-400 p-4 rounded">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-green-400" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
                        </svg>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm text-green-700">
                            <strong><?php echo $unprintedCount['count']; ?> referral(s)</strong> need labels printed.
                            Labels are automatically tracked to prevent duplicates.
                        </p>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <!-- Quick Print Options -->
            <div class="bg-white rounded-lg shadow p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Quick Print (Unprinted Only)</h3>
                <div class="space-y-3">
                    <a href="?status=pending&filter=unprinted&print=1" target="_blank"
                       class="block w-full px-4 py-3 bg-gray-100 text-gray-900 text-center rounded-lg hover:bg-gray-200 transition">
                        Pending (Not Printed)
                    </a>
                    <a href="?status=fulfilled&filter=unprinted&print=1" target="_blank"
                       class="block w-full px-4 py-3 bg-blue-100 text-blue-900 text-center rounded-lg hover:bg-blue-200 transition">
                        Fulfilled (Not Printed)
                    </a>
                    <a href="?status=located&filter=unprinted&print=1" target="_blank"
                       class="block w-full px-4 py-3 bg-purple-100 text-purple-900 text-center rounded-lg hover:bg-purple-200 transition">
                        Located (Not Printed)
                    </a>
                    <a href="?filter=unprinted&print=1" target="_blank"
                       class="block w-full px-4 py-3 bg-green-100 text-green-900 text-center rounded-lg hover:bg-green-200 transition font-semibold">
                        All Unprinted Labels
                    </a>
                </div>
            </div>

            <!-- Instructions -->
            <div class="bg-blue-50 rounded-lg border-2 border-blue-200 p-6">
                <h3 class="text-lg font-semibold text-blue-900 mb-4">Label Format</h3>
                <div class="text-sm text-blue-800 space-y-2">
                    <p><strong>Format:</strong> Avery-compatible 21-per-sheet (63.5mm x 38.1mm)</p>
                    <p><strong>Layout:</strong> 3 columns x 7 rows</p>
                    <p><strong>Each label contains:</strong></p>
                    <ul class="list-disc list-inside pl-2 space-y-1">
                        <li>Reference number</li>
                        <li>Child initials, age, and gender</li>
                        <li>Referrer name</li>
                        <li>Organisation</li>
                        <li>Special requirements (if any)</li>
                        <li>Warehouse zone (if assigned)</li>
                    </ul>
                </div>
            </div>

            <!-- Tips -->
            <div class="bg-green-50 rounded-lg border-2 border-green-200 p-6">
                <h3 class="text-lg font-semibold text-green-900 mb-4">Printing Tips</h3>
                <div class="text-sm text-green-800 space-y-2">
                    <ul class="list-disc list-inside space-y-2">
                        <li>Use Avery L7160 or compatible labels</li>
                        <li>Print at 100% scale (no shrink/fit)</li>
                        <li>Check alignment with a test sheet first</li>
                        <li>Use portrait orientation</li>
                        <li>Disable headers and footers in print settings</li>
                    </ul>
                </div>
            </div>
        </div>

        <!-- Search Referrals -->
        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Search Referrals to Print</h3>
            <p class="text-sm text-gray-600 mb-4">
                Use the <a href="referrals.php" class="text-blue-600 hover:underline">Referrals page</a> to search and filter,
                then click "Print Label" on individual referrals or "Print All Household Labels" to print multiple.
            </p>
            <a href="referrals.php"
               class="inline-block px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition">
                Go to Referrals
            </a>
        </div>
    </div>

<?php else: ?>
    <!-- Print Mode -->
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <title>Print Labels</title>
        <style>
            @page {
                size: A4 portrait;
                margin: 0;
            }

            * {
                margin: 0;
                padding: 0;
                box-sizing: border-box;
            }

            body {
                font-family: Arial, sans-serif;
                background: white;
            }

            .label-sheet {
                width: 210mm;
                height: 297mm;
                padding: 15.6mm 7mm 15.6mm 4.65mm;
                display: grid;
                grid-template-columns: repeat(3, 70mm);
                grid-template-rows: repeat(7, 38.1mm);
                gap: 0;
                page-break-after: always;
                page-break-inside: avoid;
                break-after: page;
                break-inside: avoid;
            }

            .label-sheet:last-child {
                page-break-after: auto;
                break-after: auto;
            }

            .label {
                width: 63.5mm;
                height: 38.1mm;
                border: 1px dashed #ccc;
                padding: 2mm;
                display: flex;
                flex-direction: column;
                justify-content: space-between;
                overflow: hidden;
                margin-right: 2.5mm;
            }

            .label-ref {
                font-size: 12pt;
                font-weight: bold;
                margin-bottom: 1mm;
                white-space: nowrap;
                overflow: hidden;
                text-overflow: ellipsis;
            }

            .label-child {
                font-size: 9pt;
                font-weight: bold;
                margin-bottom: 1mm;
                white-space: nowrap;
                overflow: hidden;
                text-overflow: ellipsis;
            }

            .label-info {
                font-size: 7pt;
                color: #333;
                margin-bottom: 1mm;
                line-height: 1.2;
                white-space: nowrap;
                overflow: hidden;
                text-overflow: ellipsis;
            }

            .label-zone {
                font-size: 10pt;
                font-weight: bold;
                color: #000;
                background: #f0f0f0;
                padding: 1mm 2mm;
                text-align: center;
                border-radius: 2mm;
                margin-top: 1mm;
                white-space: nowrap;
                overflow: hidden;
                text-overflow: ellipsis;
            }

            .label-special {
                font-size: 6pt;
                color: #d97706;
                font-weight: bold;
                margin-top: 1mm;
                white-space: nowrap;
                overflow: hidden;
                text-overflow: ellipsis;
            }

            .no-print {
                padding: 20px;
                background: #f3f4f6;
                border-bottom: 2px solid #e5e7eb;
            }

            @media print {
                .no-print {
                    display: none !important;
                }

                .label {
                    border: none;
                }

                body {
                    -webkit-print-color-adjust: exact;
                    print-color-adjust: exact;
                }
            }
        </style>
    </head>
    <body>
        <!-- Print Controls -->
        <div class="no-print">
            <div style="max-width: 1200px; margin: 0 auto; display: flex; justify-content: space-between; align-items: center;">
                <div>
                    <h2 style="font-size: 24px; font-weight: bold; margin-bottom: 8px;">Ready to Print</h2>
                    <p style="color: #666; font-size: 14px;"><?php echo count($referrals); ?> label(s) | <?php echo ceil(count($referrals) / 21); ?> sheet(s)</p>
                    <?php
                    $unprintedInList = 0;
                    $referralIds = [];
                    foreach ($referrals as $ref) {
                        if (!$ref['label_printed']) {
                            $unprintedInList++;
                            $referralIds[] = $ref['id'];
                        }
                    }
                    if ($unprintedInList > 0): ?>
                        <p id="print-status" style="color: #059669; font-size: 14px; margin-top: 4px; font-weight: 500;">
                            <?php echo $unprintedInList; ?> label(s) not yet marked as printed
                        </p>
                    <?php else: ?>
                        <p style="color: #9ca3af; font-size: 14px; margin-top: 4px;">
                            ✓ All labels already marked as printed
                        </p>
                    <?php endif; ?>
                </div>
                <div style="display: flex; gap: 12px;">
                    <button onclick="printLabels()"
                            style="padding: 12px 24px; background: #2563eb; color: white; border: none; border-radius: 8px; cursor: pointer; font-weight: 500;">
                        Print Labels
                    </button>
                    <button onclick="window.close()"
                            style="padding: 12px 24px; background: #6b7280; color: white; border: none; border-radius: 8px; cursor: pointer; font-weight: 500;">
                        Close
                    </button>
                </div>
            </div>
        </div>

        <script>
            const unprintedIds = <?php echo json_encode($referralIds); ?>;
            const hasUnprinted = unprintedIds.length > 0;

            function printLabels() {
                if (!hasUnprinted) {
                    // If no unprinted labels, just print
                    window.print();
                    return;
                }

                // Show printing status
                const statusEl = document.getElementById('print-status');
                if (statusEl) {
                    statusEl.textContent = 'Opening print dialog...';
                    statusEl.style.color = '#2563eb';
                }

                // Trigger print dialog
                window.print();

                // Wait for print dialog to close, then mark as printed
                // We use a slight delay to ensure the print was sent
                setTimeout(function() {
                    if (statusEl) {
                        statusEl.textContent = 'Marking labels as printed...';
                    }

                    // Mark as printed via redirect
                    window.location.href = '?mark_printed=1&ids=' + unprintedIds.join(',');
                }, 1000);
            }

            // Alternative: Use afterprint event (more reliable)
            let printDialogOpened = false;
            window.addEventListener('beforeprint', function() {
                printDialogOpened = true;
                const statusEl = document.getElementById('print-status');
                if (statusEl) {
                    statusEl.textContent = 'Printing...';
                    statusEl.style.color = '#2563eb';
                }
            });

            window.addEventListener('afterprint', function() {
                if (printDialogOpened && hasUnprinted) {
                    const statusEl = document.getElementById('print-status');
                    if (statusEl) {
                        statusEl.textContent = 'Print complete! Marking labels as printed...';
                        statusEl.style.color = '#059669';
                    }

                    // Mark as printed after a short delay
                    setTimeout(function() {
                        window.location.href = '?mark_printed=1&ids=' + unprintedIds.join(',');
                    }, 500);
                }
            });
        </script>

        <?php
        // Split referrals into sheets of 21
        $sheets = array_chunk($referrals, 21);

        foreach ($sheets as $sheetIndex => $sheetReferrals):
        ?>
            <div class="label-sheet">
                <?php foreach ($sheetReferrals as $referral): ?>
                    <div class="label">
                        <div>
                            <div class="label-ref"><?php echo htmlspecialchars($referral['reference_number']); ?></div>
                            <div class="label-child">
                                Child: <?php echo htmlspecialchars($referral['child_initials']); ?>
                                (<?php echo htmlspecialchars($referral['child_age']); ?>y, <?php echo htmlspecialchars(substr($referral['child_gender'], 0, 1)); ?>)
                            </div>
                            <div class="label-info"><?php echo htmlspecialchars($referral['referrer_name']); ?></div>
                            <div class="label-info"><?php echo htmlspecialchars($referral['referrer_organisation']); ?></div>
                            <?php if (!empty($referral['special_requirements'])): ?>
                                <div class="label-special">⚠ <?php echo htmlspecialchars(substr($referral['special_requirements'], 0, 30)); ?><?php echo strlen($referral['special_requirements']) > 30 ? '...' : ''; ?></div>
                            <?php endif; ?>
                        </div>
                        <?php if (!empty($referral['zone_name'])): ?>
                            <div class="label-zone">ZONE: <?php echo htmlspecialchars($referral['zone_name']); ?></div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>

                <?php
                // Fill remaining spaces with empty labels
                $remaining = 21 - count($sheetReferrals);
                for ($i = 0; $i < $remaining; $i++):
                ?>
                    <div class="label"></div>
                <?php endfor; ?>
            </div>
        <?php endforeach; ?>
    </body>
    </html>
    <?php
    exit; // Don't include footer in print mode
    ?>
<?php endif; ?>

<?php require_once __DIR__ . '/includes/admin_footer.php'; ?>
