<?php
// includes/sidebar.php

$currentPage = basename($_SERVER['PHP_SELF']);
?>
<aside class="w-64 bg-[#f0f4f8] flex flex-col justify-between p-6 shrink-0 border-r border-slate-200/50 h-screen sticky top-0">
    <div>
        <div class="flex items-center gap-3 px-2 mb-8">
            <div class="w-10 h-10 bg-primary rounded-xl flex items-center justify-center text-white text-xl font-bold shadow-md shadow-indigo-100/50">
                <i class="fa-solid fa-wallet"></i>
            </div>
            <span class="text-xl font-bold text-slate-800 tracking-tight">Dompet<span class="text-primary">Ku</span></span>
        </div>

        <div class="flex items-center gap-3 p-3 bg-white/60 rounded-2xl mb-8 border border-slate-100">
            <div class="w-10 h-10 rounded-full bg-primary/15 text-primary flex items-center justify-center font-bold text-lg">
                <?php echo strtoupper(substr($_SESSION['user_name'] ?? 'J', 0, 1)); ?>
            </div>
            <div>
                <div class="font-bold text-slate-800 text-sm"><?php echo $_SESSION['user_name'] ?? 'Jhon'; ?></div>
                <div class="text-xs text-slate-400 font-medium">Online</div>
            </div>
        </div>

        <nav class="space-y-1">
            <a href="dashboard.php" class="flex items-center gap-3.5 px-4 py-3 text-slate-600 hover:bg-white/40 hover:text-primary rounded-xl transition duration-150 <?php echo $currentPage == 'dashboard.php' ? 'sidebar-active' : ''; ?>">
                <i class="fa-solid fa-chart-line text-lg w-5"></i>
                <span class="text-sm">Dashboard</span>
            </a>
            <a href="transaksi.php" class="flex items-center gap-3.5 px-4 py-3 text-slate-600 hover:bg-white/40 hover:text-primary rounded-xl transition duration-150 <?php echo $currentPage == 'transaksi.php' ? 'sidebar-active' : ''; ?>">
                <i class="fa-solid fa-receipt text-lg w-5"></i>
                <span class="text-sm">Data Transaksi</span>
            </a>
            <a href="kategori.php" class="flex items-center gap-3.5 px-4 py-3 text-slate-600 hover:bg-white/40 hover:text-primary rounded-xl transition duration-150 <?php echo $currentPage == 'kategori.php' ? 'sidebar-active' : ''; ?>">
                <i class="fa-solid fa-tags text-lg w-5"></i>
                <span class="text-sm">Kategori</span>
            </a>
            <a href="budgeting.php" class="flex items-center gap-3.5 px-4 py-3 text-slate-600 hover:bg-white/40 hover:text-primary rounded-xl transition duration-150 <?php echo $currentPage == 'budgeting.php' ? 'sidebar-active' : ''; ?>">
                <i class="fa-solid fa-sliders text-lg w-5"></i>
                <span class="text-sm">Budgeting</span>
            </a>
            <a href="laporan.php" class="flex items-center gap-3.5 px-4 py-3 text-slate-600 hover:bg-white/40 hover:text-primary rounded-xl transition duration-150 <?php echo $currentPage == 'laporan.php' ? 'sidebar-active' : ''; ?>">
                <i class="fa-solid fa-file-invoice-dollar text-lg w-5"></i>
                <span class="text-sm">Laporan</span>
            </a>
            <a href="pengaturan.php" class="flex items-center gap-3.5 px-4 py-3 text-slate-600 hover:bg-white/40 hover:text-primary rounded-xl transition duration-150 <?php echo $currentPage == 'pengaturan.php' ? 'sidebar-active' : ''; ?>">
                <i class="fa-solid fa-gear text-lg w-5"></i>
                <span class="text-sm">Pengaturan</span>
            </a>
            
            <a href="logout.php" class="flex items-center gap-3.5 px-4 py-3 text-rose-500 hover:bg-rose-50 rounded-xl transition duration-150">
                <i class="fa-solid fa-right-from-bracket text-lg w-5"></i>
                <span class="text-sm">Logout</span>
            </a>
        </nav>
    </div>

    <div class="text-xs text-slate-400 px-4 py-2 border-t border-slate-200 pt-4">
        <i class="fa-solid fa-database mr-1"></i> Supabase Cloud
    </div>
</aside>
