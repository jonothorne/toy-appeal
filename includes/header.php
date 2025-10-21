<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($pageTitle) ? e($pageTitle) . ' - ' : ''; ?>Christmas Toy Appeal</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @media print {
            .no-print { display: none !important; }
            body { margin: 0; padding: 0; }
        }
        .logo-header {
            max-height: 80px;
            width: auto;
        }
    </style>
</head>
<body class="bg-gray-50">
    <!-- Logo Header -->
    <div class="bg-white border-b shadow-sm no-print">
        <div class="container mx-auto px-4 py-4">
            <img src="<?php echo defined('SITE_URL') ? SITE_URL : ''; ?>/assets/imgs/logo.png" alt="Christmas Toy Appeal" class="logo-header">
        </div>
    </div>
