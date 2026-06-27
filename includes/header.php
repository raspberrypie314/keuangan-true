<?php
// includes/header.php

$currentPage = basename($_SERVER['PHP_SELF']);
$pageTitle = $pageTitle ?? 'DompetKu - Aplikasi Pengelolaan Keuangan';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f2f5f9;
        }
        .bg-primary { background-color: #5B84B6; }
        .text-primary { color: #5B84B6; }
        .border-primary { border-color: #5B84B6; }
        .sidebar-active {
            background-color: #ffffff;
            color: #5B84B6;
            border-radius: 12px;
            font-weight: 600;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
        }
        .hover\:bg-primary-dark:hover { background-color: #4f739c; }
    </style>
</head>
<body class="text-slate-750 antialiased">
    <div class="flex min-h-screen">
        <?php include __DIR__ . '/sidebar.php'; ?>
        <div class="flex-1 flex flex-col min-w-0">
            <header class="bg-primary border-b h-16 flex items-center justify-between px-8 shrink-0 text-white shadow-md">
                <h1 class="text-lg font-bold"><?php echo $pageTitle; ?></h1>
                <div class="flex items-center gap-5">
                    <div class="flex items-center gap-3 pl-3 border-l border-white/20">
                        <div class="w-8 h-8 rounded-full bg-white/20 flex items-center justify-center font-bold text-sm">
                            <?php echo strtoupper(substr($_SESSION['user_name'] ?? 'J', 0, 1)); ?>
                        </div>
                        <span class="text-sm font-semibold hidden sm:inline"><?php echo $_SESSION['user_name'] ?? 'Jhon'; ?></span>
                    </div>
                </div>
            </header>
            <main class="flex-1 p-8 overflow-y-auto">
                <?php if (isset($_SESSION['success'])): ?>
                    <div class="mb-6 p-4 bg-green-50 border border-green-200 text-green-700 rounded-2xl flex items-center gap-3 shadow-sm">
                        <i class="fa-solid fa-circle-check text-lg"></i>
                        <span class="text-sm font-medium"><?php echo $_SESSION['success']; unset($_SESSION['success']); ?></span>
                    </div>
                <?php endif; ?>
                <?php if (isset($_SESSION['error'])): ?>
                    <div class="mb-6 p-4 bg-red-50 border border-red-200 text-red-700 rounded-2xl flex items-center gap-3 shadow-sm">
                        <i class="fa-solid fa-circle-exclamation text-lg"></i>
                        <span class="text-sm font-medium"><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></span>
                    </div>
                <?php endif; ?>
