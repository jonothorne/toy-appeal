<?php
// PRODUCTION Configuration Template
// Copy this to includes/config.php and update with your production values

// Database Configuration
define('DB_HOST', 'localhost'); // Usually 'localhost' on GoDaddy
define('DB_USER', 'CHANGE_ME'); // Your cPanel database username (e.g., username_dbuser)
define('DB_PASS', 'CHANGE_ME'); // Your database password
define('DB_NAME', 'CHANGE_ME'); // Your database name (e.g., username_toyappeal)

// Site Configuration
define('SITE_URL', 'https://yourdomain.com'); // CHANGE TO YOUR DOMAIN
define('ADMIN_URL', SITE_URL . '/admin');

// Email Configuration - PRODUCTION (Gmail)
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_USERNAME', 'office@alive.me.uk');
define('SMTP_PASSWORD', 'fdvz poix dsaa thcu'); // Your Gmail App Password
define('FROM_EMAIL', 'office@alive.me.uk');
define('FROM_NAME', 'Alive Church Christmas Toy Appeal');

// Session Configuration
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_secure', 1); // Set to 1 for HTTPS (IMPORTANT!)
session_start();

// Error Reporting - PRODUCTION (DISABLE ERROR DISPLAY!)
error_reporting(E_ALL);
ini_set('display_errors', 0); // NEVER show errors to users in production
ini_set('log_errors', 1); // Log errors instead
ini_set('error_log', __DIR__ . '/../error.log'); // Log to file

// Timezone
date_default_timezone_set('Europe/London');
