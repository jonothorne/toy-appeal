<?php
// Load required files BEFORE any output
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

// Require login
requireLogin();
$currentUser = getCurrentUser();

// Handle collection action
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reference_number'])) {
    $refNumber = trim($_POST['reference_number']);

    // Find referral by reference number
    $referral = getRow(
        "SELECT r.*, h.referrer_name, h.referrer_organisation
         FROM referrals r
         JOIN households h ON r.household_id = h.id
         WHERE r.reference_number = ?",
        "s",
        [$refNumber]
    );

    if ($referral) {
        // Mark as collected
        $success = updateReferralStatus($referral['id'], 'collected', $currentUser['id']);

        if ($success) {
            $_SESSION['success_message'] = "✓ Referral {$refNumber} marked as collected!";
            $_SESSION['collected_referral'] = $referral;
        } else {
            $_SESSION['error_message'] = "Failed to mark referral as collected.";
        }
    } else {
        $_SESSION['error_message'] = "Referral {$refNumber} not found.";
    }

    header('Location: ' . $_SERVER['PHP_SELF']);
    exit;
}

// Handle direct GET parameter for QR code scanning
if (isset($_GET['ref']) && !isset($_SESSION['success_message'])) {
    $refNumber = trim($_GET['ref']);

    // Find referral by reference number
    $referral = getRow(
        "SELECT r.*, h.referrer_name, h.referrer_organisation
         FROM referrals r
         JOIN households h ON r.household_id = h.id
         WHERE r.reference_number = ?",
        "s",
        [$refNumber]
    );

    if ($referral) {
        // Show confirmation page before marking as collected
        $showConfirmation = true;
    } else {
        $_SESSION['error_message'] = "Referral {$refNumber} not found.";
    }
}

$successMessage = $_SESSION['success_message'] ?? null;
$errorMessage = $_SESSION['error_message'] ?? null;
$collectedReferral = $_SESSION['collected_referral'] ?? null;
unset($_SESSION['success_message'], $_SESSION['error_message'], $_SESSION['collected_referral']);

// NOW load the header (after all redirects are done)
$pageTitle = "Quick Collection";
require_once __DIR__ . '/includes/admin_header.php';
?>

<style>
    #reader {
        width: 100%;
        max-width: 500px;
        margin: 0 auto;
    }
    #reader__scan_region {
        border-radius: 8px;
    }
    .scan-button {
        font-size: 18px;
        padding: 16px 32px;
    }
</style>

<div class="max-w-4xl mx-auto space-y-6">
    <!-- Page Header -->
    <div class="text-center">
        <h1 class="text-3xl font-bold text-gray-900">Quick Collection</h1>
        <p class="mt-2 text-sm text-gray-600">Scan QR code or enter reference number manually</p>
    </div>

    <?php if ($successMessage): ?>
        <!-- Success Message -->
        <div class="bg-green-50 border-l-4 border-green-400 p-6">
            <div class="flex items-center mb-4">
                <svg class="w-12 h-12 text-green-400 mr-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <div>
                    <h3 class="text-lg font-bold text-green-800"><?php echo $successMessage; ?></h3>
                    <?php if ($collectedReferral): ?>
                        <p class="text-sm text-green-700 mt-1">
                            Child: <?php echo e($collectedReferral['child_initials']); ?>,
                            Age <?php echo e($collectedReferral['child_age']); ?> (<?php echo ucfirst($collectedReferral['child_gender']); ?>)
                        </p>
                        <p class="text-sm text-green-700">
                            Referrer: <?php echo e($collectedReferral['referrer_name']); ?>
                            (<?php echo e($collectedReferral['referrer_organisation']); ?>)
                        </p>
                    <?php endif; ?>
                </div>
            </div>
            <div class="flex space-x-4">
                <a href="collect.php" class="px-6 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition">
                    Collect Another
                </a>
                <a href="referrals.php" class="px-6 py-2 border border-green-600 text-green-700 rounded-lg hover:bg-green-50 transition">
                    View All Referrals
                </a>
            </div>
        </div>
    <?php endif; ?>

    <?php if ($errorMessage): ?>
        <!-- Error Message -->
        <div class="bg-red-50 border-l-4 border-red-400 p-6">
            <div class="flex items-center">
                <svg class="w-8 h-8 text-red-400 mr-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <div>
                    <h3 class="text-lg font-bold text-red-800"><?php echo $errorMessage; ?></h3>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <?php if (isset($showConfirmation) && $showConfirmation && $referral): ?>
        <!-- Confirmation Page -->
        <div class="bg-white rounded-lg shadow-lg p-6">
            <div class="text-center mb-6">
                <div class="inline-flex items-center justify-center w-16 h-16 bg-yellow-100 rounded-full mb-4">
                    <svg class="w-10 h-10 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                    </svg>
                </div>
                <h2 class="text-2xl font-bold text-gray-900 mb-2">Confirm Collection</h2>
                <p class="text-gray-600">Please verify this is the correct referral before marking as collected</p>
            </div>

            <div class="bg-gray-50 rounded-lg p-6 mb-6">
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <p class="text-sm text-gray-600">Reference Number</p>
                        <p class="font-bold text-lg text-gray-900"><?php echo e($referral['reference_number']); ?></p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-600">Current Status</p>
                        <p class="font-semibold text-gray-900">
                            <span class="px-3 py-1 rounded-full text-sm bg-yellow-100 text-yellow-800">
                                <?php echo ucwords(str_replace('_', ' ', $referral['status'])); ?>
                            </span>
                        </p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-600">Child Details</p>
                        <p class="font-semibold text-gray-900">
                            <?php echo e($referral['child_initials']); ?>, Age <?php echo e($referral['child_age']); ?>
                            (<?php echo ucfirst($referral['child_gender']); ?>)
                        </p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-600">Referrer</p>
                        <p class="font-semibold text-gray-900"><?php echo e($referral['referrer_name']); ?></p>
                    </div>
                    <div class="col-span-2">
                        <p class="text-sm text-gray-600">Organisation</p>
                        <p class="font-semibold text-gray-900"><?php echo e($referral['referrer_organisation']); ?></p>
                    </div>
                </div>
            </div>

            <form method="POST" action="" class="flex space-x-4">
                <input type="hidden" name="reference_number" value="<?php echo e($referral['reference_number']); ?>">
                <button type="submit" class="flex-1 px-6 py-3 bg-green-600 text-white rounded-lg hover:bg-green-700 transition font-semibold">
                    ✓ Confirm Collection
                </button>
                <a href="collect.php" class="flex-1 px-6 py-3 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition text-center font-semibold">
                    Cancel
                </a>
            </form>
        </div>
    <?php else: ?>
        <!-- QR Scanner Section -->
        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4 text-center">Scan QR Code</h3>

            <div id="reader" class="mb-4"></div>

            <div class="text-center">
                <button id="start-scan" class="scan-button px-8 py-4 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition font-semibold">
                    <svg class="w-6 h-6 inline-block mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"/>
                    </svg>
                    Start Camera
                </button>
                <button id="stop-scan" class="scan-button px-8 py-4 bg-red-600 text-white rounded-lg hover:bg-red-700 transition font-semibold hidden">
                    <svg class="w-6 h-6 inline-block mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                    Stop Camera
                </button>
            </div>
        </div>

        <!-- Manual Entry Section -->
        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4 text-center">Or Enter Manually</h3>

            <form method="POST" action="" class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Reference Number</label>
                    <input type="text"
                           name="reference_number"
                           placeholder="e.g. TOY-2025-0001"
                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 text-lg"
                           required
                           autofocus>
                </div>
                <button type="submit" class="w-full px-6 py-3 bg-green-600 text-white rounded-lg hover:bg-green-700 transition font-semibold">
                    Mark as Collected
                </button>
            </form>
        </div>
    <?php endif; ?>
</div>

<!-- QR Code Scanner Library -->
<script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>
<script>
let html5QrCode;
let isScanning = false;

const startButton = document.getElementById('start-scan');
const stopButton = document.getElementById('stop-scan');

startButton.addEventListener('click', function() {
    if (isScanning) return;

    html5QrCode = new Html5Qrcode("reader");

    html5QrCode.start(
        { facingMode: "environment" },
        {
            fps: 10,
            qrbox: { width: 250, height: 250 }
        },
        (decodedText, decodedResult) => {
            // Check if it's our collection URL
            if (decodedText.includes('collect.php?ref=')) {
                // Extract reference number
                const urlParams = new URLSearchParams(decodedText.split('?')[1]);
                const refNumber = urlParams.get('ref');

                if (refNumber) {
                    // Stop scanning
                    html5QrCode.stop().then(() => {
                        // Redirect to confirmation page
                        window.location.href = 'collect.php?ref=' + encodeURIComponent(refNumber);
                    });
                }
            } else if (decodedText.match(/TOY-\d{4}-\d{4}/)) {
                // Direct reference number in QR code
                html5QrCode.stop().then(() => {
                    window.location.href = 'collect.php?ref=' + encodeURIComponent(decodedText);
                });
            } else {
                alert('Invalid QR code. Please scan a Toy Appeal collection QR code.');
            }
        },
        (errorMessage) => {
            // Scanning errors - ignore
        }
    ).then(() => {
        isScanning = true;
        startButton.classList.add('hidden');
        stopButton.classList.remove('hidden');
    }).catch((err) => {
        alert('Unable to start camera. Please check permissions or enter reference number manually.');
        console.error(err);
    });
});

stopButton.addEventListener('click', function() {
    if (!isScanning) return;

    html5QrCode.stop().then(() => {
        isScanning = false;
        stopButton.classList.add('hidden');
        startButton.classList.remove('hidden');
    });
});
</script>

<?php require_once __DIR__ . '/includes/admin_footer.php'; ?>
