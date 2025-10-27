<?php
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';

requireLogin();
$currentUser = getCurrentUser();
$currentPage = basename($_SERVER['PHP_SELF'], '.php');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($pageTitle) ? e($pageTitle) . ' - ' : ''; ?>Admin - Christmas Toy Appeal</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @media print {
            .no-print { display: none !important; }
            body { margin: 0; padding: 0; }
        }
        .logo-nav {
            max-height: 50px;
            width: auto;
        }
    </style>
</head>
<body class="bg-gray-100">
    <!-- Navigation -->
    <nav class="bg-white shadow-lg no-print">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                <div class="flex">
                    <div class="flex-shrink-0 flex items-center">
                        <img src="<?php echo SITE_URL; ?>/assets/imgs/logo.png" alt="Christmas Toy Appeal" class="logo-nav mr-2 sm:mr-4">
                    </div>
                    <div class="hidden md:ml-6 md:flex md:space-x-8">
                        <a href="index.php"
                           class="<?php echo $currentPage == 'index' ? 'border-[#eb008b] text-gray-900' : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700'; ?> inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium">
                            Dashboard
                        </a>
                        <a href="referrals.php"
                           class="<?php echo $currentPage == 'referrals' ? 'border-[#eb008b] text-gray-900' : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700'; ?> inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium">
                            Referrals
                        </a>
                        <a href="collect.php"
                           class="<?php echo $currentPage == 'collect' ? 'border-[#eb008b] text-gray-900' : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700'; ?> inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium">
                            Quick Collect
                        </a>
                        <a href="labels.php"
                           class="<?php echo $currentPage == 'labels' ? 'border-[#eb008b] text-gray-900' : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700'; ?> inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium">
                            Labels
                        </a>
                        <a href="reports.php"
                           class="<?php echo $currentPage == 'reports' ? 'border-[#eb008b] text-gray-900' : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700'; ?> inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium">
                            Reports
                        </a>
                        <a href="deletions.php"
                           class="<?php echo $currentPage == 'deletions' ? 'border-[#eb008b] text-gray-900' : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700'; ?> inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium">
                            Deletions
                        </a>
                        <a href="settings.php"
                           class="<?php echo $currentPage == 'settings' ? 'border-[#eb008b] text-gray-900' : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700'; ?> inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium">
                            Settings
                        </a>
                    </div>
                </div>
                <div class="hidden md:flex items-center">
                    <span class="text-sm text-gray-700 mr-4 hidden lg:inline">
                        Welcome, <strong><?php echo e($currentUser['full_name']); ?></strong>
                    </span>
                    <a href="../index.php" target="_blank"
                       class="hidden lg:inline text-sm text-[#eb008b] hover:text-[#c00074] mr-4">
                        View Form
                    </a>
                    <a href="logout.php"
                       class="bg-red-600 text-white px-3 py-2 rounded-lg text-sm hover:bg-red-700 transition">
                        Logout
                    </a>
                </div>
                <!-- Mobile menu button -->
                <div class="flex items-center md:hidden">
                    <button id="mobile-menu-button" type="button"
                            class="inline-flex items-center justify-center p-2 rounded-md text-gray-400 hover:text-gray-500 hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-inset focus:ring-[#eb008b]">
                        <span class="sr-only">Open main menu</span>
                        <!-- Menu icon -->
                        <svg class="block h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        </svg>
                    </button>
                </div>
            </div>
        </div>

        <!-- Mobile menu -->
        <div id="mobile-menu" class="hidden md:hidden">
            <div class="pt-2 pb-3 space-y-1">
                <a href="index.php"
                   class="<?php echo $currentPage == 'index' ? 'bg-pink-50 border-[#eb008b] text-[#c00074]' : 'border-transparent text-gray-600 hover:bg-gray-50 hover:border-gray-300 hover:text-gray-800'; ?> block pl-3 pr-4 py-2 border-l-4 text-base font-medium">
                    Dashboard
                </a>
                <a href="referrals.php"
                   class="<?php echo $currentPage == 'referrals' ? 'bg-pink-50 border-[#eb008b] text-[#c00074]' : 'border-transparent text-gray-600 hover:bg-gray-50 hover:border-gray-300 hover:text-gray-800'; ?> block pl-3 pr-4 py-2 border-l-4 text-base font-medium">
                    Referrals
                </a>
                <a href="collect.php"
                   class="<?php echo $currentPage == 'collect' ? 'bg-pink-50 border-[#eb008b] text-[#c00074]' : 'border-transparent text-gray-600 hover:bg-gray-50 hover:border-gray-300 hover:text-gray-800'; ?> block pl-3 pr-4 py-2 border-l-4 text-base font-medium">
                    Quick Collection
                </a>
                <a href="labels.php"
                   class="<?php echo $currentPage == 'labels' ? 'bg-pink-50 border-[#eb008b] text-[#c00074]' : 'border-transparent text-gray-600 hover:bg-gray-50 hover:border-gray-300 hover:text-gray-800'; ?> block pl-3 pr-4 py-2 border-l-4 text-base font-medium">
                    Print Labels
                </a>
                <a href="reports.php"
                   class="<?php echo $currentPage == 'reports' ? 'bg-pink-50 border-[#eb008b] text-[#c00074]' : 'border-transparent text-gray-600 hover:bg-gray-50 hover:border-gray-300 hover:text-gray-800'; ?> block pl-3 pr-4 py-2 border-l-4 text-base font-medium">
                    Reports & Analytics
                </a>
                <a href="deletions.php"
                   class="<?php echo $currentPage == 'deletions' ? 'bg-pink-50 border-[#eb008b] text-[#c00074]' : 'border-transparent text-gray-600 hover:bg-gray-50 hover:border-gray-300 hover:text-gray-800'; ?> block pl-3 pr-4 py-2 border-l-4 text-base font-medium">
                    Deletion History
                </a>
                <a href="settings.php"
                   class="<?php echo $currentPage == 'settings' ? 'bg-pink-50 border-[#eb008b] text-[#c00074]' : 'border-transparent text-gray-600 hover:bg-gray-50 hover:border-gray-300 hover:text-gray-800'; ?> block pl-3 pr-4 py-2 border-l-4 text-base font-medium">
                    Settings
                </a>
            </div>
            <div class="pt-4 pb-3 border-t border-gray-200">
                <div class="px-4">
                    <div class="text-base font-medium text-gray-800"><?php echo e($currentUser['full_name']); ?></div>
                    <div class="text-sm font-medium text-gray-500"><?php echo e($currentUser['email']); ?></div>
                </div>
                <div class="mt-3 space-y-1">
                    <a href="../index.php" target="_blank"
                       class="block px-4 py-2 text-base font-medium text-[#eb008b] hover:text-[#c00074] hover:bg-gray-100">
                        View Referral Form
                    </a>
                    <a href="logout.php"
                       class="block px-4 py-2 text-base font-medium text-red-600 hover:text-red-800 hover:bg-gray-100">
                        Logout
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <script>
        // Mobile menu toggle
        document.addEventListener('DOMContentLoaded', function() {
            const menuButton = document.getElementById('mobile-menu-button');
            const mobileMenu = document.getElementById('mobile-menu');

            if (menuButton && mobileMenu) {
                menuButton.addEventListener('click', function() {
                    mobileMenu.classList.toggle('hidden');
                });
            }
        });
    </script>

    <!-- Main Content -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
