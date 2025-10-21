<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

// Redirect if already logged in
if (isLoggedIn()) {
    header('Location: index.php');
    exit;
}

$pageTitle = "Admin Login";
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($username) || empty($password)) {
        $error = "Please enter both username and password.";
    } else {
        if (loginUser($username, $password)) {
            header('Location: index.php');
            exit;
        } else {
            $error = "Invalid username or password.";
        }
    }
}

require_once __DIR__ . '/../includes/header.php';
?>

<div class="min-h-screen flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-md w-full space-y-8">
        <div>
            <div class="bg-white rounded-lg shadow-lg p-8 text-center">
                <img src="<?php echo SITE_URL; ?>/assets/imgs/logo.png" alt="Christmas Toy Appeal" class="mx-auto mb-6" style="max-height: 120px; width: auto;">
                <h1 class="text-2xl font-bold text-gray-900">Admin Portal</h1>
                <p class="text-gray-600 mt-2">Please sign in to continue</p>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow-lg p-8">
            <h2 class="text-2xl font-bold text-gray-900 mb-6">Sign In</h2>

            <?php if ($error): ?>
                <div class="bg-red-50 border-l-4 border-red-400 p-4 mb-6 rounded">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <svg class="h-5 w-5 text-red-400" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                            </svg>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm text-red-700"><?php echo e($error); ?></p>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <form method="POST" action="" class="space-y-6">
                <div>
                    <label for="username" class="block text-sm font-medium text-gray-700 mb-2">
                        Username
                    </label>
                    <input type="text" id="username" name="username" required autofocus
                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                           value="<?php echo e($_POST['username'] ?? ''); ?>">
                </div>

                <div>
                    <label for="password" class="block text-sm font-medium text-gray-700 mb-2">
                        Password
                    </label>
                    <input type="password" id="password" name="password" required
                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                </div>

                <button type="submit"
                        class="w-full py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition font-medium">
                    Sign In
                </button>
            </form>

            <div class="mt-6 text-center">
                <a href="../index.php" class="text-sm text-blue-600 hover:text-blue-500">
                    &larr; Back to Referral Form
                </a>
            </div>
        </div>

        <div class="text-center text-sm text-gray-600">
            <p></p>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
